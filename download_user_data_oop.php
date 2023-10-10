<?php
require_once dirname(__FILE__) . '/../../config.php';

class MyPage {
    private $context;
    private $user;
    private $page;
    private $db;
    private $CFG;
    private $tableschema;
    private $selectedcolumn;
    private $chosentable;
    private $tempDir;
    private $zip;
    private $zipname;
    private $requested_fields;
    private $columnExists;
    private $filename;
    private $file;
    private $tablename;


    public function __construct() {
        $this->context = context_system::instance();
        $this->user = $GLOBALS['USER'];
        $this->page = $GLOBALS['PAGE'];
        $this->db = $GLOBALS['DB'];

        $this->tempDir = sys_get_temp_dir() . '/' . uniqid('temp_', true);
        mkdir($this->tempDir);
    }
    

    /**
     * create a Zipfile
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return
     */
    public function createZipFile() {
      $this->requested_fields = ['userid', 'user_id'];
      $this->zip = new ZipArchive();
      $this->zip->open($this->tempDir . '/download.zip', ZipArchive::CREATE);

      // Add files to the zip archive
      foreach ($this->requested_fields as $field) {
          $filename = $this->tempDir . '/' . $field . '.csv';
          $file = fopen($filename, 'w');
          fputcsv($file, [$field]);
          fclose($file);

          $this->zip->addFile($filename);
       }
       $this->zip->close();
    }
  

    /**
     * download selected tables to a zip folder
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return
     */
    public function downloadFile() {
        $this->zipname = $this->tempDir . '/download.zip';

        $application = "application/zip";
        header("Content-Type: $application");
        header("Content-Disposition: attachment; filename=download.zip");
        readfile($this->zipname);
        unlink($this->zipname);
        array_map('unlink', glob("$this->tempDir/*"));
        rmdir($this->tempDir);
    }   


    /**
     * Check if field exists in Table
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return Bool
     */
    public function fieldExistsInTable(){
        $sqlCheck = "SHOW COLUMNS FROM `$this->chosentable` WHERE Field = :selectedcolumn ;";
        $this->columnExists = $this->db->get_records_sql($sqlCheck, ["selectedcolumn" => $this->selectedcolumn]);
        //return !empty($columnExists);
     }

    
    /**
     * Exports user-related rows of a table to csv file
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return Bool
     */
    public function exportTableToCSV() {
      $sql4 = "SELECT * FROM `$this->chosentable` WHERE $this->selectedcolumn = :current_user";
      $results = $this->db->get_records_sql($sql4, ["current_user" => $this->user]);
  
      $this->filename = $this->tempDir . '/' . $this->chosentable . ".csv";
      $this->file = fopen($this->filename, 'w');
  
      // Write the table headers to the CSV file
      $header = array_keys((array) reset($results));
      fputcsv($this->file, $header, ";");
  
      foreach ($results as $result) {
          $row = [];
          foreach ($result as $column => $value) {
              $row[] = $value;
          }
          fputcsv($this->file, $row, ";");
      }
  
      fclose($this->file);
    }


    /**
     * select and download tables
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return 
     */
   public function exportTables() {
      $databaseName = $this->CFG->dbname;
      $sql3 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$databaseName'";
      $this->tableschema = $this->db->get_records_sql($sql3);

      foreach ($this->tableschema as $key => $this->tablename) {
        foreach ($this->tablename as $inner_key => $this->chosentable) {
            $fields_query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:tablename";
            $this->columnExists = $this->db->get_records_sql($fields_query, [ "tablename" => $this->chosentable ]);

            for($i=0; $i < count($this->requested_fields); $i++){
                if(in_array($this->requested_fields[$i], array_keys($this->columnExists))){
                    if($this->fieldExistsInTable()){
                      
                       $this->exportTableToCSV();
                        if (file_exists($this->filename) && pathinfo($this->filename, PATHINFO_EXTENSION) == 'csv') {
                            $fileContents = file_get_contents($this->filename);

                            if (substr_count($fileContents, '0') > 1) {
                                $this->zip->addFile($this->filename, basename($this->filename));
                            }
                        }
                    }
                }
            }
        }
      }
    }
  

    /**
     * start function
     * @DB
     * @$selectedcolumn
     * @$chosentable
     * @return 
     */
    public function run() {
        $this->page->set_context($this->context);
        require_login();
        $this->createZipFile();
        $this->exportTables();
        $this->downloadFile();
    }
} //Ende of class

$page = new MyPage();
$page->run();

?>