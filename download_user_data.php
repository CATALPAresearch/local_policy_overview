<?php
global $USER;

class DownloadUserData
{
    private $databaseName;
    private $context;
    private $user;
    private $page;
    private $db;
    private $requestedFields;
    private $zip;
    private $zipName;
    private $tempDir;

    public function __construct()
    {
        require_once dirname(__FILE__) . '/../../config.php';
        $this->databaseName = $CFG->dbname;
        $this->user =  $USER->id;
        $this->page = $GLOBALS['PAGE'];
        $this->db = $GLOBALS['DB'];
        $this->requestedFields = ['userid', 'user_id'];
        $this->page->set_context($this->context);
        require_login();
        $this->setup();
        $this->exportTables();
    }

    /**
     * 
     */
    private function setup(){
        // Create temporary folder
        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('temp_', true);
        mkdir($this->tempDir);

        // Create and open zip file
        $this->zip = new ZipArchive();
        $this->zipName = 'tmp_Moodle_User_Data_' . $this->user . '.zip';
        $this->zip->open($this->zipName, ZipArchive::CREATE);
    }

    /**
     * 
     */
    private function exportTables()
    {
        
        // Get all tables
        $sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$this->databaseName'";
        $tableschema = $this->db->get_records_sql($sql3);

        // iterate over all tables
        foreach ($tableschema as $tablename) {
            foreach ($tablename as $chosentable) {
                // get fields of the table
                $fields_query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:tablename";
                $columnExists = $this->db->get_records_sql($fields_query, ["tablename" => sprintf($chosentable)]);

                // check userid or user_id
                for ($i = 0; $i < count($this->requestedFields); $i++) {
                    if (in_array($this->requestedFields[$i], array_keys($columnExists))) {

                        // check whether fieldExistsInTable
                        $sqlCheck = "SHOW COLUMNS FROM `$chosentable` WHERE Field = :selectedcolumn ;";
                        $columnExists = $this->db->get_records_sql($sqlCheck, [
                            "selectedcolumn" => sprintf($this->requestedFields[$i])
                        ]);

                        if (!empty($columnExists)) {
                            $request = $this->requestedFields[$i];
                            $sql3 = "SELECT * FROM `$chosentable` WHERE $request = :current_user";
                            $results = $this->db->get_records_sql($sql3, ["current_user" => sprintf($this->user)]);
                            
                            // write to file
                            $this->writeTableAsCSV($results, $chosentable);
                        }
                    }
                }
            }
        }

        // Close zip file
        $this->zip->close();

        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=Moodle_User_Data_" . $this->user . ".zip");
        
        readfile($this->zipName);
        unlink($this->zipName);
        
        // Delete the temporary folder
        array_map('unlink', glob($this->tempDir."/*"));
        rmdir($this->tempDir);

    }

    /**
     * 
     */
    private function writeTableAsCSV($results, $chosentable){
        // Create and open CSV file
        $csvName = $this->tempDir . '/' . $chosentable . ".csv";
        $csvFile = fopen($csvName, 'w');

        // Write the table headers to the CSV file
        $header = array_keys((array) reset($results));
        fputcsv($csvFile, $header, ";");

        foreach ($results as $result) {
            $row = [];
            foreach ($result as $value) {
                $row[] = $value;
            }
            fputcsv($csvFile, $row, ";");
        }

        fclose($csvFile);

        // Add the file to the zip archive if it contains any data
        if (file_exists($csvName) && pathinfo($csvName, PATHINFO_EXTENSION) == 'csv') {
            $fileContents = file_get_contents($csvName);
            if (substr_count($fileContents, '0') > 1) {
                $this->zip->addFile($csvName, basename($csvName));
            }
        }
    }
}

$page = new DownloadUserData();
