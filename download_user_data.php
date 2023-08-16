<?php

require_once dirname(__FILE__) . '/../../config.php';
$context = context_system::instance();
global $USER, $PAGE, $DB;
$PAGE->set_context($context);
require_login();


  // read DB content
  $DBListe = $DB->get_records_sql(
    '
    SELECT TABLE_NAME, COLUMN_NAME  
    FROM INFORMATION_SCHEMA.COLUMNS  
    WHERE column_name LIKE "userid" OR
    column_name LIKE "user_id"
    and "userid" IS NOT NULL
    and "user_id" IS NOT NULL
    AND TABLE_SCHEMA="moodle"
    '
  );
   

  // save Table in File
  $filename = "hallo.csv";
  $file = fopen($filename, 'w');
  foreach ($DBListe as $fields) {
  if( is_object($fields) )
     $fields = (array) $fields;
     fputcsv($file, $fields, ";");
  }   
  fclose($file);


  //$filename = "datenbankoverview.csv";


  // create Zip-File
  $zip = new ZipArchive();
  $zip->open('download.zip', ZipArchive::CREATE);
  $zip->addFile($filename);
  $zip->close();


  // donwload Zip-File
  $zipname = "download.zip";
  $application="application/zip";
  header( "Content-Type: $application" ); // Hier das Format des Bilds angeben
  header( "Content-Disposition: attachment; filename= download.zip" ); // Hier den Dateinamen angeben, der als Standarddateiname beim Download angezeigt wird
  header("Content-Length: ". filesize($filename));
  readfile($zipname); // Hier der Pfad + Dateinamen des Quellbilds auf dem Webserver
      
?>