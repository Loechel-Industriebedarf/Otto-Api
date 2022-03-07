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
<br><br>
<br><br>


<a href="getAllBrands.php"><button>Markenliste anzeigen und abspeichern</button></a><br><br>
<a href="getAllCategories.php"><button>Kategorienliste anzeigen und abspeichern</button></a><br><br>
<a href="getSingleProduct.php"><button>Informationen zu einem Produkt anzeigen</button></a><br><br>
<a href="getOrders.php"><button>Bestellabholung testen</button></a><br><br>