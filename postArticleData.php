<?php
    require_once 'inc/config.php';

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $filename = htmlspecialchars(basename( $_FILES["fileToUpload"]["name"]));
            echo "Die Datei ". $filename . " wurde hochgeladen!";

            if(isset($_POST["uploadProducts"])){
                //Show button to check upload status
                echo '<a href="getUploadStatus.php"><button>Uploadstatus prüfen</button></a>';

                //Read csv content
                $postfields = readDataFromCSV($url, $accessToken, $target_dir . $filename, "products");  
                uploadProductData($url, $accessToken, $postfields, $username, $password);
            }   
            else if(isset($_POST["uploadQuantity"])){
                echo '<a href="inc/quantityErrors.csv"><button>Error-File in Excel öffnen</button></a><br><br>';

                $postfields = readDataFromCSV($url, $accessToken, $target_dir . $filename, "quantity");  
                uploadQuantityData($url, $accessToken, $postfields, $username, $password);
            }       
          } else {
            echo "Fehler beim Upload...";
          }
    }



    



    
    
    /*
    * TODO: Write documentation
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    Array
    * @input    String
    * @return   Array   Decoded json result
    */
    function readDataFromCSV($url, $accessToken, $filepath, $identifier){
		$brands_json = file_get_contents('inc/brands.json');
		$brands = json_decode($brands_json);
		
		$weeCategories = explode("\n", file_get_contents('inc/weeeCategories.csv'));
		
        $csv = file_get_contents($filepath);
        
        $postfields = array();

        if (($handle = fopen($filepath, "r")) !== FALSE) {
            fgetcsv($handle); //Skip first line
			$i = 0; // Count lines
            while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
                if($identifier === "products"){
                    array_push($postfields, getProductJsonFromCSVData($data, $brands, $weeCategories));
                }
                else if($identifier === "quantity"){
                    array_push($postfields, getQuantityJsonFromCSVData($data));
                }
				
				$i++;
				if($i % 1000 === 0){ LogMe("readDataFromCSV " . $i); }
            }
            fclose($handle);
        } else {
            echo "Fehler beim Lesen der Datei...";
        } 

        return $postfields;      
    }



    
    
    
    
    
    
    /*
    * TODO: Write documentation
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    Array
    * @return   Array   Decoded json result
    */
    function uploadProductData($url, $accessToken, $postfields, $username, $password){       
        //Split in chunks with 500 articles (max per request)
        $chunksize = 500;
        $chunkPostfields = array_chunk($postfields, $chunksize);

        $uploadIds = "";
        $i = 0;
        //Start upload
        echo "<h1>Uploadinfo</h1>";
        foreach ($chunkPostfields as &$value) {
            //Get new token every 25000 requests
            if($i % 25000 || $i == 0){
                $accessToken = getAccessToken($url, $username, $password);
            }

            echo $i . " - " . getCurrentDateTimeOtto();
            echo "<br>";
            $spResult = uploadProducts($url, $accessToken, $value, 'product_' . $i);

            $uploadIds .= $spResult["links"][0]["href"] . "\r\n";

            $i += $chunksize;
			
			LogMe("uploadProductData " . $i);

            //Wait 100ms
            //usleep(100000);

            echo "<br><br>";
        }
        

        $file = 'inc/uploadId.txt';
        //Write upload id to file
        file_put_contents($file, $uploadIds);
    }


    
    





    /*
    * TODO: Write documentation
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    Array
    * @return   Array   Decoded json result
    */
    function uploadQuantityData($url, $accessToken, $postfields, $username, $password){
        $chunksize = 200;
        //Split in chunks with 200 articles (max per request)
        $chunkPostfields = array_chunk($postfields, $chunksize);

        $i = 0;
        $csv = 0;
        //Start upload
        echo "<h1>Uploadinfo</h1>";
        foreach ($chunkPostfields as &$value) {			
            //Get new token every 25000 requests
            if($i % 25000){
                $accessToken = getAccessToken($url, $username, $password);
            }

            echo $i . " - " . getCurrentDateTimeOtto();
            echo "<br>";
            $quantityResult = uploadQuantities($url, $accessToken, $value, 'quantity_' . $i);

            if(isset($quantityResult["errors"])){
                logMe($quantityResult["errors"]);
                foreach($quantityResult["errors"] as &$value){
                    $csv .= $value["logref"] . ";" . $value["title"] . ";" . $value["detail"] . PHP_EOL;
                }
            }
            

            $i += $chunksize;
			
			LogMe("uploadQuantityData " . $i . " - " . var_dump($quantityResult));

            //Wait 100ms
            usleep(100000);
        }	

        $csv = 'Logref;Title;Detail' . PHP_EOL . $csv;
        $file = 'inc/quantityErrors.csv';
        //Write upload id to file
        file_put_contents($file, $csv);
    }





    
    
    
    
    /*
    * TODO: Write documentation
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    Array
    * @return   Array   Decoded json result
    */
    function uploadProducts($url, $accessToken, $postfields, $filenameJson){
        $json = JSONfyPostfields($postfields, $filenameJson);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v3/products');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'X-Request-Timestamp: SOME_STRING_VALUE';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $jsonDecode = json_decode($result, true);

        if($jsonDecode["state"] != "pending"){
            logMe($result);
        }
        

        return $jsonDecode;
    }


    






    /*
    * TODO: Write documentation
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    Array
    * @return   Array   Decoded json result
    */
    function uploadQuantities($url, $accessToken, $postfields, $filenameJson){
        $json = JSONfyPostfields($postfields, $filenameJson);

        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v2/quantities');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'Content-Type: application/json;charset=UTF-8';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return json_decode($result, true);
    }

   
    

















    /*
    * Formats the csv data as json. 
    * Replaces line breaks, tab stops, <ul> and <li> tags
    * Checks, if base unit is existent; If not, it's not included in the json
    * Prices and sales amounts can have a , as decimal seperator; The function changes commas with dots
    *
    *
    * Format:
    * SKU [0];EAN [1];Herstellernr [2];Marke angepasst [3];Bezeichnung [4];
    * Beschreibung [5];Bilddateiname [6];Lieferzeit [7];Preis [8];Grundeinheit [9];
    * Grundmenge [10];Kategorie [11];Marke Original [12]; VPE [13]; Farbe [14];
    * Größe [15]; Norm [16]; Material [17]; Produktart [18]; Ausführung [19];
    * Zertifikat [20]; Gefahrgut [21]; MOIN [22]; WEEE-Number [23]
    *
    * @input    String  Csv line in the correct format (generated via fgetcsv)
    * @input    String  Array with all the available brands
    * @input    String  Array with all categories that require a weee number
    * @return   String  Json string for product data uploads
    */
    function getProductJsonFromCSVData($csvData, $brands, $weeeCategories){		
        $baseUnitJson = '';
        $baseUnit = getOttoBaseUnit($csvData[9]);

        //Only add base unit, if it actually exists
        if($baseUnit != null && $baseUnit != ""){
            $baseUnitJson = ',
            "normPriceInfo":{
                "normAmount":1,
                "normUnit":"' . $baseUnit . '",
                "salesAmount":' . str_replace(',', '.', $csvData[10]) . ',
                "salesUnit":"' . $baseUnit . '"
            }';
        }

        $vpeString = "";
        //Support for multiple units in one package
        if($csvData[13] != null && $csvData[13] != ""  && $csvData[13] != "1"){
            $vpeString = $csvData[13]. 'er Pack ';
        }

        $bulletpoints = "  ";
        //Bulletpoints
        for($i = 14; $i < 20; $i++){
            if($csvData[$i] != null && $csvData[$i] != ""){
                $bulletpoints .= $csvData[$i] . ' | ';
            }
        }
        //Remove last 2 chars from bulletpoints
        $bulletpoints = substr_replace($bulletpoints, "", -2);
		//Replace " with ''
		$bulletpoints = str_replace('"', "''", $bulletpoints);

        $attributes = "";
        //Set attributes, if Werkzeugset
        if($csvData[11] == "Werkzeugset"){
            //Is the product dangerous?
            $dangerGood = "Produkt fällt nicht unter die Gefahrgutvorschriften.";
            if($csvData[21] != null && $csvData[21] != ""){
                $dangerGood = "Produkt fällt unter die Gefahrgutvorschriften.";
            }
            $attributes = ',
            "attributes":[{
                "name":"Relevanz Gefahrgut",
                "values":["' . $dangerGood . '"]
            }]';
        }
		//Add attributes for weee number if a weee number is listed in the csv
		else {
			//Check categories
			foreach($weeeCategories as $weeeCategory){				
				//Category found
				if(trim(strtolower($weeeCategory)) == trim(strtolower($csvData[11]))){
					//Check if a weee number is listed
					if($csvData[23] != null || $csvData[23] != ""){
						$attributes = ',
						"attributes":[{
							"name":"Geräteart laut ElektroG",
							"values":["Kleingeräte, die in privaten Haushalten genutzt werden können."]
						},
						{
							"name":"WEEE-Reg.-Nr. DE",
							"values":["' . preg_replace('/\D/', '', $csvData[23]) . '"]
						}]'; //Removes the DE from the weee number
					}
					else{
						$attributes = ',
						"attributes":[{
							"name":"Geräteart laut ElektroG",
							"values":["Produkt fällt nicht unter das ElektroG."]
						}]';
					}
					break;
				}
			}
		}
		
		
        

        //Only transfer moin, if it was listed in the csv
        $moin = "";
        if($csvData[22] != null && $csvData[22] != ""){
            $moin = '"moin":"' . $csvData[22] . '",';
        }

		
		//"ohne Marke"
		$productBrandId = "00000000";
		
		//Search for BrandId
		foreach($brands as $brand){		
			if(strtolower($brand->name) === strtolower($csvData[3])){
				$productBrandId = $brand->id;
				break;
			}
		}		
		
		//logMe($csvData[0] . " - " . strtolower($csvData[3]) . " - " . $productBrandId);
        
        //Generate json
        $json = 
        '{
            "productReference":"' . $csvData[0] . '",
            "sku":"' . $csvData[0] . '",
            "ean":"' . $csvData[1] . '",
            "pzn":"0",
            "mpn":"' . $csvData[2] . '", ' . $moin . '
            "releaseDate":"1970-01-01T00:00:00.000Z",
            "productDescription":{
                "category":"' . $csvData[11] . '",
                "brandId":"' . $productBrandId . '",
                "productLine":"' . substr($vpeString . $csvData[4], 0, 70) . '",
                "manufacturer":"' . $csvData[12] . '",
                "productionDate":"1970-01-01T00:00:00.000Z",
                "multiPack":false,
                "bundle":false,
                "fscCertified":false,
                "disposal":false,
                "productUrl":"https://www.loechel-industriebedarf.de/nwsearch/execute?query=' . $csvData[0] . '",
                "description":"' . substr($csvData[5], 0, 2000) . '",
                "bulletPoints":[
                    "' . $csvData[12] . '", 
                    "' . $vpeString . '",
                    "' . $bulletpoints . '"
                ]' . $attributes . '
            },
            "mediaAssets":[{
                "type":"IMAGE",
                "location":"' . $csvData[6] . '"
            }],
            "delivery":{
                "type":"PARCEL",
                "deliveryTime":' . $csvData[7] . '
            },
            "pricing":{
                "standardPrice":{
                    "amount":' . str_replace(',', '.', $csvData[8]) . ',
                    "currency":"EUR"
                },
                "vat":"FULL" 
                ' . $baseUnitJson .   '
            },
            "logistics":{
                "packingUnitCount":1
            }
        }';

        $json = str_replace('<br>', ' ', $json);
        $json = str_replace('<BR>', ' ', $json);
        $json = str_replace('<ul>', ' ', $json);
        $json = str_replace('<UL>', ' ', $json);
        $json = str_replace('<li>', ' ', $json);
        $json = str_replace('<LI>', ' ', $json);
        $json = preg_replace('/\t+/', ' ', $json); //Remove tab
        $json = str_replace('\\r\\n', ' ', $json); //Remove line breaks
        

        return $json;
    }









    /*
    * Formats the csv data as json. 
    *
    * Format:
    * SKU [0];Bestand [1]
    *
    * @input    String  Csv line in the correct format (generated via fgetcsv)
    * @return   String  Json string for quantity uploads
    */
    function getQuantityJsonFromCSVData($csvData){
        $json = '
            {
                "lastModified": "' . getDateTimeOttoOneHourAgo() . '",
                "quantity": ' . $csvData[1] . ',
                "sku": "' . $csvData[0] . '"
            }
        ';

        return $json;
    }









    /*
    * Gets the quantity from a single sku.
    *
    * @input    String  Base api url (productive or test)
    * @input    String  Access token for the api
    * @input    String  The sku number, we want to get the quantity from
    * @return   Array   Decoded json result
    */
    function getQuantityFromSKU($url, $accessToken, $sku){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . '/v2/quantities/' . $sku);
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




    /*
    * Transform base units into the correct format
    * 
    * Allowed units
    * 'Stk', 'qm', 'kg', 'l', 'm', 'ml', 'g', 'Paar', 'RM', 'dm3'
    *
    * @return   String    Base unit, included in the source csv file
    * @return   String    Base unit, accepted by Otto
    */
    function getOttoBaseUnit($unitOriginal){
        switch($unitOriginal){
            case "L":
                $baseUnit = "l";
                break;
            case "ST":
                $baseUnit = "Stk";
                break;
            case "m²":
                $baseUnit = "qm";
                break;
            case "M":
                $baseUnit = "m";
                break;
            case "ML":
                $baseUnit = "ml";
                break;
            case "KG":
                $baseUnit = "kg";
                break;
            case "G":
                $baseUnit = "g";
                break;
            case "RL":
                $baseUnit = "Stk";
                break;
            case "RL":
                $baseUnit = "Stk";
                break;
            default:
                $baseUnit = $unitOriginal;
        }

        return $baseUnit;
    }





































/*******************************************************************************************************
 * 
 * Debug and non working stuff~ Ignore plz.
 * 
 *******************************************************************************************************/

















































    
    /*
    * Generates a json array for two test products.
    *
    * @return   Array    Two test products with data
    */
    function getTestProducts(){
        $postfields = array();
        array_push($postfields, '{
            "productReference":"39976396283",
            "sku":"39976396283",
            "ean":"4270002512702",
            "isbn":"",
            "upc":"",
            "pzn":"0",
            "mpn":"39976396283",
            "moin":"",
            "releaseDate":"1970-01-01T00:00:00.000Z",
            "productDescription":{
                "category":"Elektrowerkzeug-Set",
                "brand":"Löchel Industriebedarf",
                "productLine":"LÖCHEL Industriebedarf Qualitäts-Bremsenreiniger 500 ml",
                "manufacturer":"WEICON",
                "productionDate":"1970-01-01T00:00:00.000Z",
                "multiPack":false,
                "bundle":false,
                "fscCertified":false,
                "disposal":false,
                "productUrl":"https://www.loechel-industriebedarf.de/nwsearch/execute?query=39976396283",
                "description":"LÖCHEL Industriebedarf Qualitäts-Bremsenreiniger 500 ml",
                "bulletPoints":["Löchel Industriebedarf"]
            },
            "mediaAssets":[{
                "type":"IMAGE",
                "location":"https://www.loechel-industriebedarf.de/upload/shoppictures_95/39976396283.jpg"
            }],
            "delivery":{
                "type":"PARCEL",
                "deliveryTime":2
            },
            "pricing":{
                "standardPrice":{
                    "amount":7.79,
                    "currency":"EUR"
                },
                "vat":"FULL",
                "normPriceInfo":{
                    "normAmount":1,
                    "normUnit":"l",
                    "salesAmount":500,
                    "salesUnit":"ml"
                }
            },
            "logistics":{
                "packingUnitCount":1
            }
        }');
        array_push($postfields, '{
            "productReference":"39976396284",
            "sku":"39976396284",
            "ean":"4270002512719",
            "isbn":"",
            "upc":"",
            "pzn":"0",
            "mpn":"39976396284",
            "moin":"",
            "offeringStartDate":"1970-01-01T00:00:00.000Z",
            "releaseDate":"1970-01-01T00:00:00.000Z",
            "productDescription":{
                "category":"Elektrowerkzeug-Set",
                "brand":"Löchel Industriebedarf",
                "productLine":"LÖCHEL Industriebedarf Qualitäts-Sprühreiniger Entfetter S 500 ml",
                "manufacturer":"WEICON",
                "productionDate":"1970-01-01T00:00:00.000Z",
                "multiPack":false,
                "bundle":false,
                "fscCertified":false,
                "disposal":false,
                "productUrl":"https://www.loechel-industriebedarf.de/nwsearch/execute?query=39976396284",
                "description":"LÖCHEL Industriebedarf Sprühreiniger S 500 ml Der Sprühreiniger S von LÖCHEL Industriebedarf entfernt ölige und fettige Verschmutzungen von allen Metallen sowie von Keramik, Glas und den meisten Kunststoffen. Er kann jedoch die Oberfläche von Thermoplasten wie PVC, Plexiglas, Polystyrol und einfachen Lackschichten angreifen. LÖCHEL Industriebedarf Reiniger S verdunstet schnell und im Gegensatz zu herkömmlichen Verdünnern rückstandsfrei. Er kann zur Reinigung und Entfettung vor dem Grundieren und Lackieren, zur Reinigung von Maschinenteilen oder vor dem Auftragen anderer LÖCHEL Industriebedarf Produkte auf Oberflächen eingesetzt werden, wo ein fettiger Untergrund deren Wirkung beeinträchtigen würde.",
                "bulletPoints":["Löchel Industriebedarf"]
            },
            "mediaAssets":[{
                "type":"IMAGE",
                "location":"https://www.loechel-industriebedarf.de/upload/shoppictures_95/39976396284.jpg"
            }],
            "delivery":{
                "type":"PARCEL",
                "deliveryTime":2
            },
            "pricing":{
                "standardPrice":{
                    "amount":7.55,
                    "currency":"EUR"
                },
                "vat":"FULL",
                "normPriceInfo":{
                    "normAmount":1,
                    "normUnit":"l",
                    "salesAmount":500,
                    "salesUnit":"ml"
                }
            },
            "logistics":{
                "packingUnitCount":1
            }
        }');

        /*
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
        '[{
            "productReference":"UBN-11779",
            "sku":"3858389911564",
            "ean":"3858389911564",
            "isbn":"978-3-16-148410-0",
            "upc":"042100005264",
            "pzn":"PZN-4908802",
            "mpn":"H2G2-42",
            "moin":"M00A1234BC",
            "offeringStartDate":"2019-10-19T09:30:00.000Z",
            "releaseDate":"2019-10-19T09:30:00.000Z",
            "maxOrderQuantity":5,
            "productDescription":{
                "category":"Outdoorjacke",
                "brand":"Adidas",
                "productLine":"501",
                "manufacturer":"3M",
                "productionDate":"2021-07-02T09:30:52.093Z",
                "multiPack":true,
                "bundle":false,
                "fscCertified":true,
                "disposal":false,
                "productUrl":"http://myproduct.somewhere.com/productname/",
                "description":"<p>Some example words...<b>in bold</b>...some more</p>",
                "bulletPoints":["My top key information..."],
                "attributes":[{
                    "name":"Bundweite",
                    "values":["34"],"additional":true
                }]
            },
            "mediaAssets":[{
                "type":"IMAGE",
                "location":"http://apartners.url/image-location"
            }],
            "delivery":{
                "type":"PARCEL",
                "deliveryTime":1
            },
            "pricing":{
                "standardPrice":{
                    "amount":19.95,
                    "currency":"EUR"
                },
                "vat":"FULL",
                "msrp":{
                    "amount":19.95,
                    "currency":"EUR"
                },
                "sale":{
                    "salePrice":{
                        "amount":19.95,
                        "currency":"EUR"
                    },
                "startDate":"2019-10-19T09:30:00.000Z",
                "endDate":"2019-10-19T09:30:00.000Z"
                },
                "normPriceInfo":{
                    "normAmount":100,
                    "normUnit":"g",
                    "salesAmount":500,
                    "salesUnit":"g"
                }
            },
            "logistics":{
                "packingUnitCount":3,
                "packingUnits":[{
                    "weight":365,
                    "width":600,
                    "height":200,
                    "length":300
                }]
            }
        }]');
        */

        return $postfields;
    }









    /*
    * PRICE UPLOAD NOT WORKING
    *   string(47) "could not convert parameter and/or header value"
    */
    /*
    function uploadPricesQuantities($url, $accessToken, $postfields){
        //Split in chunks with 500 articles (max per request)
        $chunkPostfields = array_chunk($postfields, 200);

        $uploadIds = "";
        //Start upload
        echo "<h1>Uploadinfo</h1>";
        foreach ($chunkPostfields as &$value) {
            $priceResult = uploadPrices($url, $accessToken, $value);	
            echo "<pre>";
            var_dump($priceResult);
            echo "</pre>";
            $uploadIds .= $priceResult["links"][0]["href"] . "\r\n";

            $quantityResult = uploadQuantities($url, $accessToken, $value);
            echo "<pre>";
            var_dump($quantityResult);
            echo "</pre>";
            $uploadIds .= $quantityResult["links"][0]["href"] . "\r\n";

            //Wait 100ms
            //usleep(100000);
        }	

        $file = 'inc/uploadId.txt';
        //Write upload id to file
        file_put_contents($file, $uploadIds);
    }
    */









    /*
    * CURRENTLY NOT WORKING!
    */
    /*
    function uploadPrices($url, $accessToken, $postfields){
        // Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
        $ch = curl_init();

        $json = '[
            {
                "sku":"39976396283",
                "standardPrice":{
                    "amount":19.99,
                    "currency":"EUR"
                }
            },
            {
                "sku":"39976396284",
                "standardPrice":{
                    "amount":19.99,
                    "currency":"EUR"
                }
            }
        ]';


        echo $json;

        curl_setopt($ch, CURLOPT_URL, $url . '/v2/products/prices');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $accessToken;
        $headers[] = 'X-Request-Timestamp: ' . getCurrentDateTimeOtto();
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return json_decode($result, true);
    }
    */