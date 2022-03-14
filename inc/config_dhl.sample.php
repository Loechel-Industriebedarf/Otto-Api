<?php
    $sandbox = true;

    $dhl_username = "";
    $dhl_password = "";
    $dhl_api_username = "";
    $dhl_api_password = "";
    $receiver_id = "";
    if($sandbox){
        $dhl_api_username  = "loechelindustriebedarf";
        $dhl_api_password = "Loechel!124!!";
        //Base64: MjIyMjIyMjIyMl9jdXN0b21lcjp1QlFiWjYyIVppQmlWVmJoYw==
        $dhl_username = "2222222222_customer";
        $dhl_password = "uBQbZ62!ZiBiVVbhc";
        $receiver_id = "DE";
    }
    $dhl_base64 = base64_encode($dhl_username . ":" . $dhl_password);
    $dhl_api_base64 = base64_encode($dhl_api_username . ":" . $dhl_api_password);