<form action="postArticleData.php" method="post" enctype="multipart/form-data">
    CSV Datei für Bestandsupload auswählen
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="hidden" name="uploadQuantity">
    <input type="submit" value="Bestandsupload starten" name="submit">
</form>
<a href="inc/quantityErrors.csv"><button>Error-File in Excel öffnen</button></a><br><br>

<br><br>
<br><br>
<br><br>



<form action="postArticleData.php" method="post" enctype="multipart/form-data">
    CSV Datei für Produktupload auswählen (WICHTIG: UTF-8 Kodierung)
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="hidden" name="uploadProducts">
    <input type="submit" value="Produktupload starten" name="submit">
</form>


<a href="getUploadStatus.php"><button>Uploadstatus prüfen</button></a><br><br>
<a href="inc/productErrors.csv"><button>Error-File in Excel öffnen (ZUERST "Uploadstatus prüfen")</button></a><br><br>

<br><br>
<br><br>
<br><br>

<form action="postTrackingData.php" method="post" enctype="multipart/form-data">
    Datei mit Trackingnummern auswählen
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="hidden" name="uploadProducts">
    <input type="submit" value="Tracking-Upload starten" name="submit">
</form>

<br><br>
<br><br>
<br><br>
<br><br>
<br><br>

<a href="getRestrictedProducts.php"><button>RESTRICTED Produkte prüfen</button></a><br><br>

<br><br>
<br><br>




<a href="getAllBrands.php"><button>Markenliste anzeigen und abspeichern</button></a><br><br>
<a href="getAllCategories.php"><button>Kategorienliste anzeigen und abspeichern</button></a><br><br>
<form action="getSingleProduct.php" method="get">
    Sku?
    <input type="text" name="sku" id="sku">
    <input type="submit" value="Informationen zu einem Produkt anzeigen" name="submit">
</form>
<form action="deactivateProducts.php" method="post" enctype="multipart/form-data">
    Produkte deaktivieren
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Produkte deaktivieren" name="submit">
</form>
<form action="deactivateProducts.php" method="post" enctype="multipart/form-data">
    Produkte aktivieren
    <input type="hidden" name="activate" id="activate">
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Produkte aktivieren" name="submit">
</form>
<a href="categoryWizard.php"><button>Category Wizard</button></a><br><br>
<a href="getOrders.php"><button>Bestellabholung testen</button></a><br><br><br><br><br><br>
<a href="generateTestOrders.php"><button>Testbestellungen generieren</button></a><br><br>
