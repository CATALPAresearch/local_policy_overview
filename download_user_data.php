<?php

require_once dirname(__FILE__) . '/../../config.php';
$context = context_system::instance();
global $USER, $PAGE, $DB;
$PAGE->set_context($context);
require_login();

// transfer of the columns to be checked
$selectedcolumn =  $_POST['selectedcolumn'];
$columnvalue =  $_POST['columnvalue'];

// Create an empty string
$sql1 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'moodle'";

// Get a list of all tables in the database
$tableschema = $DB->get_records_sql($sql1);

// For each table, add a separate SELECT query
foreach ($tableschema as $key => $tablename) {
    foreach ($tablename as $inner_key => $chosentable) {

        // Run the SQL query and get the results
        $sql2 = "SELECT * FROM mdl_adminpresets  where $selectedcolumn  = $columnvalue ";
        $results = $DB->get_records_sql($sql2);

         // save Table in File
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
    }
}

 // donwload Zip-File
 $zipname = "download.zip";
 $application="application/zip";
 header( "Content-Type: $application" ); // Specify the format here
 header( "Content-Disposition: attachment; filename= download.zip" ); // Enter the file name here that is displayed as the default file name when downloading
 //header("Content-Length: ". filesize($filename));
 readfile($zipname); // Here the path + filename of the source image on the web server
   
?>