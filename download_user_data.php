<?php

require_once dirname(__FILE__) . '/../../config.php';
$context = context_system::instance();
global $USER, $PAGE, $DB, $USER2;
$PAGE->set_context($context);
require_login();

// Temporären Ordner erstellen
$tempDir = sys_get_temp_dir() . '/' . uniqid('temp_', true);
mkdir($tempDir);

// Set variables and initializes files
$requested_fields = ['userid', 'user_id']; // We'll possible add some more columns containing user-related data
$zip = new ZipArchive();
$zip->open('download.zip', ZipArchive::CREATE);

/**
 * Check if field exists in Table
 * @DB
 * @$selectedcolumn
 * @$chosentable
 * @return Bool
 */
function fieldExistsInTable($DB, $selectedcolumn, $chosentable){
   $sqlCheck = "SHOW COLUMNS FROM `$chosentable` WHERE Field = :selectedcolumn ;";
   $columnExists = $DB->get_records_sql($sqlCheck, ["selectedcolumn" => $selectedcolumn ]);
   return !empty($columnExists);
}

/**
 * Exports user-related rows of a table to csv file
 * @DB
 * @$selectedcolumn
 * @$chosentable
 * @return Bool
 */
function exportTableToCSV($DB, $selectedcolumn, $chosentable, $USER, $tempDir){
   $sql4 = "SELECT * FROM `$chosentable` WHERE $selectedcolumn = :current_user";
   $results = $DB->get_records_sql($sql4, [ "current_user" => $USER ]);
  
   // File name for the CSV file in the temporary folder
   $filename = $tempDir . '/' . $chosentable . ".csv";

   // Create CSV file in temporary folder
   $file = fopen($filename, 'w');

   // Write the table headers to the CSV file
   $header = array_keys((array) reset($results));
   fputcsv($file, $header, ";");

   foreach ($results as $result) {
      $row = [];
      foreach ($result as $column => $value) {
         $row[] = $value;
      }
      fputcsv($file, $row, ";");
   }

   fclose($file);
   return $filename;
}

// Path to the config.php file
$configPath = $_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php';

// Read the contents of the config.php file
$configContent = file_get_contents($configPath);

// Search for the database name
preg_match("/\$CFG->dbname\s*=\s*'([^']+)'/", $configContent, $matches);

// The database name is in the first capturing group match
$databaseName = $matches[1];

// Get all tables
$sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$databaseName'";
$tableschema = $DB->get_records_sql($sql3);

// iterate over all tables
foreach ($tableschema as $key => $tablename) {
    foreach ($tablename as $inner_key => $chosentable) {
      // get fields of the table
      $fields_query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:tablename";
      $columnExists = $DB->get_records_sql($fields_query, [ "tablename" => $chosentable ]);
      for($i=0; $i < count($requested_fields); $i++){
         if(in_array($requested_fields[$i], array_keys($columnExists))){
            if(fieldExistsInTable($DB, $requested_fields[$i], $chosentable)){
               //echo 'found ' . $requested_fields[$i] . '  in table ' . $chosentable . '<br>';
               $filename = exportTableToCSV($DB, $requested_fields[$i], $chosentable, 0,$tempDir);
               if (file_exists($filename) && pathinfo($filename, PATHINFO_EXTENSION) == 'csv') {
                  // Check whether the table contains content
                  $fileContents = file_get_contents($filename);
                  // It just needs to be fliered zero because incorrectly 
                  // writing the header writes a zero.
                  if (substr_count($fileContents, '0') > 1) {
                     $zip->addFile($filename, basename($filename));
                  }
                  }
       
            }
         }
      }
   }
}
$zip->close();

$zipname = "download.zip";
$application="application/zip";
header( "Content-Type: $application" ); // Specify the format here
header( "Content-Disposition: attachment; filename= download.zip" ); // Enter the file name here that is displayed as the default file name when downloading
 //header("Content-Length: ". filesize($filename));
readfile($zipname); // Here the path + filename of the source image on the web server
unlink($zipname);

// Delete the temporary folder
array_map('unlink', glob("$tempDir/*"));
rmdir($tempDir);