<?php
    include 'inc/config.php';

    

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $filename = htmlspecialchars(basename( $_FILES["fileToUpload"]["name"]));
            echo "Die Datei ". $filename . " wurde hochgeladen!<br><br>";

            $products = readProductsFromCsv($target_file);
            $result = deactivateProducts($url, $accessToken, $products);
            logMe($result);
          } else {
            echo "Fehler beim Upload...";
          }
    }

    
    function readProductsFromCsv($csvPath){
        $products = array();
        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            fgetcsv($handle); //Skip first line
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                array_push($products, $data[0]);
            }
            fclose($handle);
        } else {
            echo "Fehler beim Lesen der Datei...";
        } 

        return $products;
    }
        



    function deactivateProducts($url, $accessToken, $products){
        $json = generateProductDeactivateJsonFromArray($products);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v2/products/active-status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'X-Request-Timestamp: ' . str_replace('0100', '01:00', getCurrentDateTimeOtto());
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return json_decode($result, true);
    }




    function generateProductDeactivateJsonFromArray($products){
        $json = '{"status": [';

        foreach($products as &$value){
            $json .= '{"sku": "' . $value . '","active": false},';
        }

        //Remove last comma from json
        $json = substr_replace($json, "", -1);

        $json .= ']}';

        return $json;
    }