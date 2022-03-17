<?php
    require_once 'inc/config.php';
    require_once 'getDHLRetoure.php';

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $filename = htmlspecialchars(basename( $_FILES["fileToUpload"]["name"]));
            echo "Die Datei ". $filename . " wurde hochgeladen!<br><br>";

            $postfields = readShipmentsFromCsv($url, $accessToken, $target_file);    

            echo "<br><br><br><br><br>POSTFIELDS: ";
            logMe($postfields);

            logMe(getTodaysShipments($url, $accessToken));
          } else {
            echo "Fehler beim Upload...";
          }
    }






    function createNewShipment($url, $accessToken, $json){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v1/shipments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return json_decode($result, true);
    }






    /*
    * Format:
    * Carrier [0]; TrackingNumber[1]; PositionItemId [2]; SalesOrderId[3]; OrderId[4]; OwnOrderId[5]
    */
    function readShipmentsFromCsv($url, $accessToken, $csvPath){
        $lastOrder = "";
        $postfields = array();

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            fgetcsv($handle); //Skip first line
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                echo $data[0] . ";" . $data[1] . ';' . $data[2] . ';' . $data[3] . ";" . $data[4] . "<br>";

                //Support for multiple shipments, I guess?
                //Sets carrier to "OTHER_FORWARDER", if a order number appears multiple times
                $carrier = $data[0];
                $trackingNumber = $data[1];
                if($lastOrder == $data[4]){
                    $carrier = "OTHER_FORWARDER";
                    $trackingNumber .= "0" . count($postfields);
                }

                $json = generateShipmentJson($url, $accessToken, $carrier, $trackingNumber, $data[2], $data[3], $data[5]. '_' . $data[4]);

                if($json !== null){
                    array_push($postfields, $json);
                    $shipments = createNewShipment($url, $accessToken, $json);

                    if(isset($shipments["errors"])){
                        logMe($json);
                    }

                    logMe($shipments);
                } 
                
                $lastOrder = $data[4];
            }
            fclose($handle);
        } else {
            echo "Fehler beim Lesen der Datei...";
        } 

        return $postfields;
    }







    function generateShipmentJson($url, $accessToken, $carrier, $trackingNumber, $positionItemId, $salesOrderId, $labelNameBase){
        $orderData = getOrderData($url, $accessToken, $salesOrderId);
        $orderStatus = $orderData["positionItems"][0]["fulfillmentStatus"];

        //Only mark position as ship, if it wasn't shipped/returned already
        if($orderStatus == "PROCESSABLE"){    
            include 'inc/config_dhl.php';     
            $dhl_return_number = postDHLRetoure($sandbox, $dhl_base64, $dhl_api_base64, $receiver_id, $orderData, date('Y-m-d') . '_' . $labelNameBase);


            $json = '{
                "trackingKey":{
                    "carrier":"' . $carrier . '",
                    "trackingNumber":"' . $trackingNumber . '"
                },
                "shipDate":"' . getCurrentDateTimeOtto() . '",
                "shipFromAddress":{
                    "city":"Sulingen",
                    "countryCode":"DEU",
                    "zipCode":"27232"
                },
                "positionItems":[{
                    "positionItemId":"' . $positionItemId . '",
                    "salesOrderId":"' . $salesOrderId . '",
                    "returnTrackingKey": {
                        "carrier": "DHL",
                        "trackingNumber": "' . $dhl_return_number . '"
                    }
                }]
            }';

            return $json;
        }
        return null;
    }



    function getOrderData($url, $accessToken, $order_id){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v4/orders/' . $order_id);
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

        return json_decode($result, true);
    }







    function getTodaysShipments($url, $accessToken){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        $requestUrl = $url . '/v1/shipments?datefrom=' . date('Y-m-d');
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

        return json_decode($result, true);
    }


    function getShipmentById($url, $accessToken, $shipmentId){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v1/shipments/' . $shipmentId);
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

        return json_decode($result, true);
    }