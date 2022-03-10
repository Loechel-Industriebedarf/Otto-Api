<?php
    require_once 'inc/config.php';

    //Check, if the csv file was already processed
    if(file_exists($csvPathOrders)){
		echo getCurrentDateTimeOtto() . " CSV file was not processed yet!";
	}
    else{
        //Read date of last operation
        $lastDate =  file_get_contents('inc/lastDate.txt');
        //$lastDate = "1970-01-01T01:00:00+02:00";

        //Check if new orders exist
        $orders = getOrders($url, $accessToken, $lastDate);

        //Make csv out of the api request
        $csv = convertOrdersToCsv($orders);

        //Write orders and last date to file. Display error message, if there are no new orders
        if($csv == null){
            echo "No new orders...";
        }
        else{
            echo $csv;

            writeToCsv($csvPathOrders, $csv);
        }
    }



    
    function getOrders($url, $accessToken, $lastDate){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        $requestUrl = $url . '/v4/orders?fromDate=' . urlencode($lastDate);
        echo $requestUrl;

        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        //logMe($result);

        return json_decode($result, true);
    }



    /*
    * Format
    * Bestellnr [0]; Bestelldatum [1]; E-Mail [2]; Artikelnr [3]; Menge[4]; Preis [5]; 
    * V-Firma1 [6]; V-Strasse [7]; V-Firma2 [8]; V-PLZ [9]; V-Ort [10]; V-LKZ [11]; 
    * R-Firma1 [12]; R-Strasse [13]; R-Firma2 [14]; R-PLZ [15]; R-Ort [16]; R-LKZ [17]; 
    * Telefonnr [18]; Zahlungsart [19]; Zahlungsnr [20]; Porto [21]; Nebenkosten [22];
    * LastModified [23]; PositionItemId [24]
    */
    function convertOrdersToCsv($orders){
        if(!isset($orders["resources"][0])){
            return null;
        }
        else{
            $csv = "";

            foreach($orders["resources"] as &$value){
                foreach($value["positionItems"] as &$item){
                    $quantity = 1;
                    $price = $item["itemValueGrossPrice"]["amount"];
                    $title = strtolower($item["product"]["title"]);
                    $fees = $price * 0.15;
                    //"Pack" articles (multiple articles in one listing)
                    if(strpos($title, 'er pack')){
                        $strpostitle = substr($title,0,strpos(strtolower($title),"er pack")); //Cut everything after "er Pack"
                        $lastspace = strrpos($strpostitle, ' '); //Search for last space
                        if($lastspace > 0){
                            $strpostitle = substr($strpostitle, $lastspace, strlen($strpostitle)); //Cut everything before last space
                        }	
                        $quantity *= intval($strpostitle); //Get "real" quantity
                        $price = doubleval($price) / doubleval($strpostitle); //Get "real" price
                        $fees = $fees / doubleval($strpostitle) + 0.01; //Get "real" fees
                    }

                    $csv .= $value["orderNumber"] . ';';
                    $csv .= $value["orderDate"] . ';';
                    $csv .= '' . ';'; //Mail does not exist
                    $csv .= $item["product"]["sku"] . ';';
                    $csv .= $quantity . ';'; //MENGE???
                    $csv .= $price . ';';
                    $csv .= $value["deliveryAddress"]["firstName"] . " " . $value["deliveryAddress"]["lastName"] . ';';
                    $csv .= $value["deliveryAddress"]["street"] . " " . $value["deliveryAddress"]["houseNumber"] . ';';
                    $csv .= $value["deliveryAddress"]["addition"] . ';';
                    $csv .= $value["deliveryAddress"]["zipCode"] . ';';
                    $csv .= $value["deliveryAddress"]["city"] . ';';
                    $csv .= $value["deliveryAddress"]["countryCode"] . ';';
                    $csv .= $value["invoiceAddress"]["firstName"] . " " . $value["invoiceAddress"]["lastName"] . ';';
                    $csv .= $value["invoiceAddress"]["street"] . " " . $value["invoiceAddress"]["houseNumber"] . ';';
                    $csv .= $value["invoiceAddress"]["addition"] . ';';
                    $csv .= $value["invoiceAddress"]["zipCode"] . ';';
                    $csv .= $value["invoiceAddress"]["city"] . ';';
                    $csv .= $value["invoiceAddress"]["countryCode"] . ';';
                    if(isset($value["deliveryAddress"]["phoneNumber"])){ $csv .= $value["deliveryAddress"]["phoneNumber"] . ';'; } else { $csv .= ';'; } //Not every customer has a phone number
                    $csv .= $value["payment"]["paymentMethod"] . ';';
                    $csv .= $value["salesOrderId"] . ';';
                    $csv .= $value["initialDeliveryFees"][0]["deliveryFeeAmount"]["amount"] . ';';
                    $csv .= $fees . ';'; //TODO Nebenkosten
                    $csv .= $value["lastModifiedDate"] . ';';
                    $csv .= $item["positionItemId"] . ';' . PHP_EOL;
                }    
            }

            return generateHeadline() . PHP_EOL . $csv;
        }
    }



    function generateHeadline(){
        return 'Bestellnr [0]; Bestelldatum [1]; E-Mail [2]; Artikelnr [3]; Menge[4]; Preis [5]; V-Firma1 [6]; V-Strasse [7]; V-Firma2 [8]; V-PLZ [9]; V-Ort [10]; V-LKZ [11]; L-Firma1 [12]; L-Strasse [13]; L-Firma2 [14]; L-PLZ [15]; L-Ort [16]; L-LKZ [17]; Telefonnr [18]; Zahlungsart [19]; Zahlungsnr [20]; Porto [21]; Nebenkosten [22]; Last Modified [23]; PositionItemId [24]';
    }


    function writeToCsv($csvPath, $csv){
        $file = $csvPath;
        //Write upload id to file
        file_put_contents($file, $csv);

        writeLastDate();
    }



    function writeLastDate(){
        $file = 'inc/lastDate.txt';
        //Write upload id to file
        file_put_contents($file, getCurrentDateTimeOtto());
    }