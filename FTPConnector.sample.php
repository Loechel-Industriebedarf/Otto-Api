
<?php
    function saveToFTP($file){
        $remote_file = 'otto/' . $file;
    
        $ftp_server = "";
        $ftp_user_name = "";
        $ftp_user_pass = "";
    
        // Verbindung aufbauen
        $ftp = ftp_connect($ftp_server);
    
        // Login mit Benutzername und Passwort
        $login_result = ftp_login($ftp, $ftp_user_name, $ftp_user_pass);
    
        // Datei hochladen
        if (ftp_put($ftp, $remote_file, $file, FTP_BINARY)) {
        echo $file . "uploaded successfully!";
        } else {
        echo "Error while uploading " . $file;
        }
    
        // Verbindung schließen
        ftp_close($ftp);
    }
    
