<?php

require_once dirname(__FILE__) . '/../../config.php';
$context = context_system::instance();
global $USER, $PAGE, $DB;
$PAGE->set_context($context);
require_login();

// transfer of the columns to be checked
$userid =  $_POST['userid'];

// Create an empty string
$sql = '';

// Get a list of all tables in the database
$tables = $DB->get_records_sql("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'moodle'");

// For each table, add a separate SELECT query
foreach ($tables as $key => $inside) {
    foreach ($inside as $inner_key => $value1) {

        // Run the SQL query and get the results
        $sql1 = "SELECT * FROM $value1";
        $results = $DB->get_records_sql($sql1);

        // save Table in File
        $filename = $value1.".csv";
        $file = fopen($filename, 'w');

        foreach ($results as $fields) {
        if( is_object($fields) )
        $fields = (array) $fields;
        fputcsv($file, $fields, ";");
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