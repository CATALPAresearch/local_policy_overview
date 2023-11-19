<?php
class DSGVOrequirements {
    private $context;
    private $user;
    private $page;
    private $db;
    private $requested_fields;

    public function __construct() {
        require_once dirname(__FILE__) . '/../../config.php';
        $this->databaseName = $CFG->dbname;
        //$this->context = context_system::instance();
        $this->user =  $CFG->dbuser;
        $this->page = $GLOBALS['PAGE'];
        $this->db = $GLOBALS['DB'];
        $this->requested_fields = ['userid', 'user_id'];
        //$this->tempDir = sys_get_temp_dir() . '/' . uniqid('temp_', true);
        //mkdir($this->tempDir);
        $this->page->set_context($this->context);
        require_login();
        $this->exportTables();
    }

    public function exportTables() {
       
    // Get all tables
    $sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$this->databaseName'";
    $tableschema = $this->db->get_records_sql($sql3);

    // ZipArchive-Objekt erstellen
    $zip = new ZipArchive();

    // Name der Zip-Datei festlegen
    $zipName = 'meine_zip_datei.zip';

    // Zip-Datei öffnen oder erstellen
    if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {

    // iterate over all tables
    foreach ($tableschema as $key => $tablename) {
        foreach ($tablename as $inner_key => $chosentable) {
            // get fields of the table
            $fields_query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:tablename";
            $columnExists = $this->db->get_records_sql($fields_query, [ "tablename" => $chosentable ]);


            for($i=0; $i < count($this->requested_fields); $i++){
                if(in_array($this->requested_fields[$i], array_keys($columnExists))){

                    $sqlCheck = "SHOW COLUMNS FROM `$chosentable` WHERE Field = :selectedcolumn ;";
                    $columnExists = $this->db->get_records_sql($sqlCheck, ["selectedcolumn" => $this->requested_fields[$i] ]);

                    if(!empty($columnExists)){
                        $request = $this->requested_fields[$i];
                        $sql3 = "SELECT * FROM `$chosentable` WHERE $request = :current_user";
                        $results = $this->db->get_records_sql($sql3, [ "current_user" => $this->user]);

                        ///????????????????????????????????????????
                        //$sql4 = "SELECT * FROM `$chosentable` WHERE 'userid = 2'";
                        //$sql5 = 'SELECT * FROM `mdl_adminpresets` WHERE userid=0';
                        //$resultss = $this->db->get_records_sql($sql5);

                        // CSV-Datei erstellen und öffnen
                        $csvName = $chosentable . ".csv";
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

                        // CSV-Datei schließen
                        fclose($csvFile);

                        // CSV-Datei zur Zip-Datei hinzufügen
                        //$zip->addFile($csvName);
                            
                        if (file_exists($csvName) && pathinfo($csvName, PATHINFO_EXTENSION) == 'csv') {
                                // Check whether the table contains content
                                $fileContents = file_get_contents($csvName);
                                // It just needs to be fliered zero because incorrectly 
                                // writing the header writes a zero.
                                if (substr_count($fileContents, '0') > 1) {
                                   $zip->addFile($csvName, basename($csvName));
                                }
                        }

                     }//END Request if

                }//For each #2b
            }//For each #2a

            }//For each #1b
        }//For each #1a
    


        // Zip-Datei schließen
        $zip->close();

        // Header für den Download setzen
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($zipName));
    
        // Zip-Datei ausgeben
        readfile($zipName);
    
        // Temporäre Dateien löschen
        unlink($zipName);
        unlink($csvName);
    } else {
        echo "Fehler beim Erstellen der Zip-Datei.";
    }// End if Zip

  }//END exportTables
}//END CLASS

$page = new DSGVOrequirements();
?>