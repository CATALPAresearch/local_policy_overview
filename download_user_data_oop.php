<?php
global $USER;
class DownloadUserData {
    private $databaseName;
    private $context;
    private $user;
    private $page;
    private $db;
    private $requested_fields;

    public function __construct() {
        require_once dirname(__FILE__) . '/../../config.php';
        $this->databaseName = $CFG->dbname;
        $this->user =  $USER->id;//$CFG->dbuser;
        $this->page = $GLOBALS['PAGE'];
        $this->db = $GLOBALS['DB'];
        $this->requested_fields = ['userid', 'user_id'];
        $this->page->set_context($this->context);
        require_login();
        $this->exportTables();
    }

    public function exportTables() {
       
        // Create temporary folder
        $tempDir = sys_get_temp_dir() . '/' . uniqid('temp_', true);
        mkdir($tempDir);

        // Get all tables
        $sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$this->databaseName'";
        $tableschema = $this->db->get_records_sql($sql3);

        // Create ZipArchive object
        $zip = new ZipArchive();

        // Set the name of the zip file
        $zipName = 'meine_zip_datei.zip';

        // Open or create zip file
        $zip->open( $zipName, ZipArchive::CREATE);

        // iterate over all tables
        foreach ($tableschema as $key => $tablename) {
            foreach ($tablename as $inner_key => $chosentable) {
                // get fields of the table
                $fields_query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:tablename";
                $columnExists = $this->db->get_records_sql($fields_query, [ "tablename" => sprintf($chosentable) ]);

                // check userid or user_id
                for($i=0; $i < count($this->requested_fields); $i++){
                    if(in_array($this->requested_fields[$i], array_keys($columnExists))){

                        // check whether fieldExistsInTable
                        $sqlCheck = "SHOW COLUMNS FROM `$chosentable` WHERE Field = :selectedcolumn ;";
                        $columnExists = $this->db->get_records_sql($sqlCheck, ["selectedcolumn" => sprintf($this->requested_fields[$i]) ]);

                        // exportTableToCSV
                        if(!empty($columnExists)){
                            $request = $this->requested_fields[$i];
                            $sql3 = "SELECT * FROM `$chosentable` WHERE $request = :current_user";
                            $results = $this->db->get_records_sql($sql3, [ "current_user" => sprintf($this->user) ]);

                            // Create and open CSV file
                            $csvName = $tempDir . '/' . $chosentable . ".csv";
                            $csvFile = fopen($csvName, 'w');

                            // Write the table headers to the CSV file
                            $header = array_keys((array) reset($results));
                            fputcsv($csvFile, $header, ";");

                            foreach ($results as $result) {
                                $row = [];
                                foreach ($result as $column => $value) {
                                    $row[] = $value;
                                }
                                fputcsv($csvFile, $row, ";");
                            }

                            // Close CSV file
                            fclose($csvFile);
                                
                            if (file_exists($csvName) && pathinfo($csvName, PATHINFO_EXTENSION) == 'csv') {
                                    // Check whether the table contains content
                                    $fileContents = file_get_contents($csvName);
                                    // A null must be filtered
                                    // because incorrectly written headers produce a null
                                    if (substr_count($fileContents, '0') > 1) {
                                    $zip->addFile($csvName, basename($csvName));
                                    }
                            }

                        }//END Request if

                    }//For each #2b
                }//For each #2a

            }//For each #1b
        }//For each #1a
        
            // Close zip file
            $zip->close();

            $application="application/zip";
            // Specify the format here
            header( "Content-Type: $application" ); 
            // Enter the file name here that is displayed as the default file name when downloading
            header( "Content-Disposition: attachment; filename= download.zip" ); 
            // Here the path + filename of the source image on the web server
            readfile($zipName); 
            unlink($zipName);
            // Delete the temporary folder
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);

    }//END exportTables
}//END CLASS

$page = new DownloadUserData();

?>