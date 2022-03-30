
<head>
    <meta charset="Windows-1252">
</head>


<?php
    //Both lists must have the same encoding
    //categories.csv will always be ansi encoded
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
            array_push($categories, $data[0]);
        } 
    }

    //var_dump($categories);

    //Read all texts
    $handle = fopen($textList, "r");
    while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
        foreach($categories as &$value){
            //List is ANSI encoded; Value is ANSI
            if(strpos(strtolower($data[0]), strtolower($value)) !== false){
                $csv .= $data[1] . ';' . $value . ';' . $data[0] . PHP_EOL;
                echo $data[1] . ';' . convertANSItoUTF8($value) . ';' . convertANSItoUTF8($data[0]) . "<br>";
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