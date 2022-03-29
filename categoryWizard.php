<?php
    $categoryList = "inc/categories.csv";
    $textList = "inc/texts.csv";

    $csv = "";

    //Read list of all categories
    $categories = array();
    $handle = fopen($categoryList, "r");
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        //Only add, if the category has no required features
        if(!isset($data[1])){
            //Categories are saved in ANSI
            array_push($categories, convertANSItoUTF8($data[0]));
        } 
    }

    //Read all texts
    $handle = fopen($textList, "r");
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        foreach($categories as &$value){
            //List is ansi encoded
            if(strpos(convertANSItoUTF8($data[0]), $value) !== false){
                $csv .= $data[1] . ';' . $value . ';' . $data[0] . PHP_EOL;
                echo $data[1] . ';' . $value . ';' . convertANSItoUTF8($data[0]) . "<br>";
            }
        }
    }

    //Write csv to file
    $fp = fopen('inc/categoryWizard.csv', 'w');
    fwrite($fp, $csv);
    fclose($fp);


    function convertANSItoUTF8($str) {
        return iconv("Windows-1252", "UTF-8", $str);
    }