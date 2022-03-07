<a href="inc/productErrors.csv"><button>Error-File in Excel öffnen (Erst klicken, wenn Seite aufgehört hat zu "arbeiten"!)</button></a><br><br>

<?php
    require_once 'inc/config.php';

    $filepath = 'inc/uploadId.txt';

    $csv = "";
    echo "<h1>Uploadstatus</h1>";
    //Read upload ids from file
    $handle = fopen($filepath, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            
            echo "<h2>Update task</h2>";
            $utaskResult = getUpdateTasks($url, $accessToken, $line);
            echo $utaskResult["state"] . " - " . $utaskResult["message"] . "<br>";
            /*
            echo "<pre>";
            var_dump($utaskResult);
            echo "</pre>";
            */

            $utaskResult = getUpdateTasks($url, $accessToken, $line . '/failed');
            /*
            echo "<pre>";
            var_dump($utaskResult);
            echo "</pre>";
            */
            foreach($utaskResult["results"] as &$value){
                $csv .= $value["variation"] . ';' .  $value["errors"][0]["code"] . ';' .  $value["errors"][0]["title"] . ';' .  $value["errors"][0]["jsonPath"] . PHP_EOL;
            }
            
            $utaskResult = getUpdateTasks($url, $accessToken, $line . '/succeeded');
            /*
            echo "<pre>";
            var_dump($utaskResult);
            echo "</pre>";
            */

            $utaskResult = getUpdateTasks($url, $accessToken, $line . '/unchanged');
            /*
            echo "<pre>";
            var_dump($utaskResult);
            echo "</pre>";
            */
            foreach($utaskResult["results"] as &$value){
                $csv .= $value["variation"] . ';' .  $value["errors"][0]["code"] . ';' .  $value["errors"][0]["title"] . ';' .  $value["errors"][0]["jsonPath"] . PHP_EOL;
            }

            echo "<br><br>" . nl2br($csv);
        }
        $csv = "Variation;Error Code;Error Title;JSon Path" . PHP_EOL . $csv;
        $file = 'inc/productErrors.csv';
        //Write upload id to file
        file_put_contents($file, $csv);

        fclose($handle);
    } else {
        echo "Fehler beim Lesen der Datei mit Upload-Ids...";
    } 


    




    function getUpdateTasks($url, $accessToken, $task_url){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        echo "<br>" . $url . $task_url . "<br>";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . $task_url);
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