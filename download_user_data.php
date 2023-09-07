<?php

require_once dirname(__FILE__) . '/../../config.php';
$context = context_system::instance();
global $USER, $PAGE, $DB, $USER2;
$PAGE->set_context($context);
require_login();


// eine Liste aller DB-Tabellen von Moodle erstellen
$sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'moodle'";
$tableschema = $DB->get_records_sql($sql3);

foreach ($tableschema as $key => $tablename) {
    foreach ($tablename as $inner_key => $chosentable) {

      //  Prüfe für jede Tabelle, ob es eine Spalte 'user_id' oder 'userid' exsitiert
      $selectedcolumn =  'userid';
      $selectedcolumn2 = 'user_id';
      $sqlCheck = "SHOW COLUMNS FROM `$chosentable` 
                  WHERE Field = '$selectedcolumn' 
                  OR Field = '$selectedcolumn2'";

      // Falls die Spalten userid/user_id existiert, prüfe ob die user_id des aktuellen Nutzers 
      // in diesen Tabellen vorhanden ist
      $currentuser =  $USER->id;
      $columnExists = $DB->get_records_sql($sqlCheck);

      if (!empty($columnExists)) {
         // The column exists, execute the SQL command
         $sql4 = "SELECT * FROM `$chosentable` WHERE $selectedcolumn = ?";
         $results = $DB->get_records_sql($sql4, array($currentuser));

         // Falls user_id des aktuellen Nutzers in diesen Tabellen vorhanden ist, 
         // extrahiere alle Einträge des Nutzers aus den Tabellen und 
         // generiere daraus Daten (keine Dateien!) im CSV-Format. 
         $filename = $chosentable.".csv";
         $file = fopen($filename, 'w');
 
         // Write HTML request to CSV
         foreach ($results as $result) {

               foreach ($result as $column => $value) {
                  $myvalue = $column.";".$value;
                  $myvalue = (array) $myvalue;
                  fputcsv($file, $myvalue);
               }
         }

        fclose($file);

        // create Zip-File
        $zip = new ZipArchive();
        $zip->open('download.zip', ZipArchive::CREATE);
        $zip->addFile($filename);
        $zip->close();

      } else {
      // echo "Spalten nicht vorhanden";
      }
         
      }
   }

// Konvertiere die CSV-Daten so, dass der Nutzer die Daten in _einer_ Zip-Datei 
// herunterladen kann, die je Tabelle genau eine CSV-Datei mit ausschließlich seinen 
// Daten enthält. Die Benennung der CSV-Dateien entspricht dabei den Bezeichnern der Tabellen
 $zipname = "download.zip";
 $application="application/zip";
 header( "Content-Type: $application" ); // Specify the format here
 header( "Content-Disposition: attachment; filename= download.zip" ); // Enter the file name here that is displayed as the default file name when downloading
 //header("Content-Length: ". filesize($filename));
 readfile($zipname); // Here the path + filename of the source image on the web server
   

?>