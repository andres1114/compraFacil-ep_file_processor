<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');

//Include the db connection file
include_once "db_connection.php";
//Include the functions file
include_once "functions.php";

$verbose_output_mode = 0;

verbose(array("outputMode" => 0, "outputMessage" => "Starting the ep_file process script", "logName" => "main_php"));

verbose(array("outputMode" => 0, "outputMessage" => "Connecting to the CompraFacil database...", "logName" => "main_php"));
//Create the PDO connection objects
$pdo_sqlite_db = pdoCreateConnection(array('db_type' => "sqlite", 'db_host' => realpath(__DIR__).'/misc_database.sqlite3', 'db_user' => "root", 'db_pass' => "", 'db_name' => ""));
verbose(array("outputMode" => 0, "outputMessage" => "Done", "logName" => "main_php"));

//Define the constants
$current_script_path = realpath(__DIR__)."/";
$epfile_folder = "ep_files";
$ep_file_path = "/mnt/nfs/";
$ignored_files = array('.', '..');

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Getting the directory listing from path '".$ep_file_path.$epfile_folder."'", "logName" => "main_php"));
$main_directory_listing = scandir($ep_file_path.$epfile_folder,SCANDIR_SORT_ASCENDING);

//Go through the folders and files
for ($x = 0; $x < sizeof($main_directory_listing); $x++) {
    if (in_array($main_directory_listing[$x], $ignored_files)) {
        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "'".$main_directory_listing[$x]."' is not an accepted file or directory, skipping", "logName" => "main_php"));
        continue;
    }
    $folder_nydate = $main_directory_listing[$x];

    verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Checking if folder ".$folder_nydate." is empty...", "logName" => "main_php"));
    $temp_directory_counter = 0;
    $is_folder_empty = true;
    $temp_file_listing = scandir($ep_file_path.$epfile_folder."/".$folder_nydate,SCANDIR_SORT_ASCENDING);

    for ($z = 0; $z < sizeof($temp_file_listing); $z++) {
        $temp_file = $temp_file_listing[$z];

        if (!in_array($temp_file, $ignored_files)) {
            $temp_directory_counter++;
            break;
        }
    }

    if ($temp_directory_counter > 0) {
        $is_folder_empty = false;
    } else {
        $is_folder_empty = true;
    }

    if ($is_folder_empty) {
        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Directory '$folder_nydate' is empty, skipping", "logName" => "main_php"));
        continue;
    } else {
        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Directory '$folder_nydate' is not empty", "logName" => "main_php"));

        $temp_file_listing = scandir($ep_file_path.$epfile_folder."/".$folder_nydate,SCANDIR_SORT_ASCENDING);
        for ($z = 0; $z < sizeof($temp_file_listing); $z++) {
            $temp_file = $temp_file_listing[$z];

            if (in_array($temp_file, $ignored_files)) {
                verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "$temp_file' is not an accepted file or directory, skipping", "logName" => "main_php"));
                continue;
            }
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Checking whether file '$temp_file' is already queued", "logName" => "main_php"));

            $query_args = array(
                'filename' => $temp_file
            );
            $query = "SELECT id FROM epfiles_queue WHERE ep_file_name = :filename";
            $queryData = pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_1");

            if ($queryData[1] == 0) {
                verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "The file has not been queued", "logName" => "main_php"));
                $query_args = array(
                    'filename' => $temp_file,
                    'datefolder' => $folder_nydate
                );
                $query = "INSERT INTO epfiles_queue (ep_file_name, has_been_proccesed, date_folder, is_in_process) VALUES (:filename, 0, :datefolder, 0)";
                pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_2");
            } else {
                verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "The file has already been queued, skipping", "logName" => "main_php"));
                continue;
            }
        }
    }
}

?>