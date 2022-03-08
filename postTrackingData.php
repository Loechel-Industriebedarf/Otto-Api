<?php
    require_once 'inc/config.php';

    $postfields = readShipmentsFromCsv("");

    $shipments = createNewShipments($url, $accessToken, $postfields);

    logMe($shipments);

    /*
    $lastShipments = getTodaysShipments($url, $accessToken);
    logMe($lastShipments);
    */








    function createNewShipments($url, $accessToken, $postfields){
        $json = JSONfyPostfields($postfields, "tracking");

        //Remove first and last char
        $json = substr($json, 1);
        $json = substr_replace($json, "", -1);  
        

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







    function readShipmentsFromCsv($csvPath){
        $postfields = array();
        array_push($postfields, generateShipmentJson("DHL", "123456", "1111", "22222"));
        array_push($postfields, generateShipmentJson("DHL", "1234567", "1111222", "222223333"));

        return $postfields;
    }







    function generateShipmentJson($carrier, $trackingNumber, $positionItemId, $salesOrderId){
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
                    "trackingNumber": "' . $trackingNumber . '0"
                }
            }]
        }';

        return $json;
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