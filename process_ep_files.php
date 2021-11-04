<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');

//Include the db connection file
include_once "db_connection.php";
//Include the functions file
include_once "functions.php";

$verbose_output_mode = 0;

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Starting the ep_file process script", "logName" => "process_ep_files_php"));

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Connecting to the CompraFacil database...", "logName" => "process_ep_files_php"));
//Create the PDO connection objects
$pdo_sqlite_db = pdoCreateConnection(array('db_type' => "sqlite", 'db_host' => realpath(__DIR__).'/misc_database.sqlite3', 'db_user' => "root", 'db_pass' => "", 'db_name' => ""));
$pdo_mysql = pdoCreateConnection(array('db_type' => "mysql", 'db_host' => "192.168.10.14", 'db_user' => "root", 'db_pass' => "admin", 'db_name' => "compraFacil"));
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done", "logName" => "process_ep_files_php"));

//Define the constants
$current_script_path = realpath(__DIR__)."/";
$epfile_folder = "ep_files";
$ep_file_path = "/mnt/nfs/";
$ignored_files = array('.', '..');

//Get files to process
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Getting the ep_files to process...", "logName" => "process_ep_files_php"));
$query_args = array();
$query = "SELECT * FROM epfiles_queue WHERE is_in_process = 0";
$queryData = pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_1");
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done, found ".$queryData[1]." files to process", "logName" => "process_ep_files_php"));

$id_array = cast_assoc_array_to_array("id", $queryData[0]);

$query_args = array();
$query = "UPDATE epfiles_queue SET is_in_process = 1 WHERE id IN (".implode(",",$id_array).")";
#pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_2");

for ($x = 0; $x < $queryData[1]; $x++) {
    $filename = $queryData[0][$x]["ep_file_name"];
    $folderName = $queryData[0][$x]["date_folder"];

    verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Processing file '".$filename."'", "logName" => "process_ep_files_php"));
    $file_content = file_get_contents($ep_file_path.$epfile_folder."/".$folderName."/".$filename);

    $domain_id_start_str = "DomainId:";
    $domain_id_end_str = ":DomainId";

    $domain_id = get_string_between($file_content, $domain_id_start_str, $domain_id_end_str);
    verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Domain id: $domain_id", "logName" => "process_ep_files_php"));

    $query_args = array(
        "domainid" => $domain_id
    );
    $query = "SELECT * FROM scrapped_file_configuration WHERE domain_id = :domainid AND active IS TRUE";
    $inner_queryData = pdoExecuteQuery($pdo_mysql, $query, $query_args, "query_3");

    $product_name = get_string_between($file_content, $inner_queryData[0][0]["item_1_start_string"], $inner_queryData[0][0]["item_1_end_string"]);
    $product_price = get_string_between($file_content, $inner_queryData[0][0]["item_2_start_string"], $inner_queryData[0][0]["item_2_end_string"]);

    if ($product_name != false && $product_price != false) {
        $product_price = preg_replace("/\s/","",$product_price);
        $product_price = preg_replace("/\./","",$product_price);
        $product_price = preg_replace("/,/","",$product_price);
        $product_price = preg_replace("/\$/","",$product_price);

        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Product name: '$product_name'", "logName" => "process_ep_files_php"));
        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Product price: '$product_price'", "logName" => "process_ep_files_php"));

        verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Checking whether the product already exists in DB...", "logName" => "process_ep_files_php"));
        $query_args = array(
            "productname" => $product_name
            ,"idalmacen" => $domain_id
        );
        $query = "SELECT id FROM producto WHERE nombre_producto = :productname AND id_almacen = :idalmacen";
        $inner_queryData = pdoExecuteQuery($pdo_mysql, $query, $query_args, "query_3");
        if ($inner_queryData[1] == 0) {
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done, the product doesn't exist", "logName" => "process_ep_files_php"));

            $query_args = array(
                "idalmacen" => $domain_id
                ,"productname" => $product_name
                ,"productprice" => $product_price
            );
            $query = "INSERT INTO producto (id_almacen, nombre_producto, precio_producto, id_imagen, activo) VALUES (:idalmacen, :productname, :productprice, NULL, 1)";
            #pdoExecuteQuery($pdo_mysql, $query, $query_args, "query_4");

        } else {
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done, the product already exists", "logName" => "process_ep_files_php"));
            if ($inner_queryData[0][0]["precio_producto"] != $product_price) {
                verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "The product price has been updated, from '$product_price' to '".$inner_queryData[0][0]["precio_producto"]."', updating in DB...", "logName" => "process_ep_files_php"));

                $query_args = array(
                    "productid" => $inner_queryData[0][0]["id"]
                    ,"productprice" => $product_price
                );
                $query = "UPDATE producto SET precio_producto = :productprice WHERE id = :productid";
                #pdoExecuteQuery($pdo_mysql, $query, $query_args, "query_5");
            } else {
                verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "No product price update found, skipping", "logName" => "process_ep_files_php"));
            }
        }
    } else {
        if ($product_name != false) {
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Product name found: '$product_name', however, no product price found", "logName" => "process_ep_files_php"));
        }
        if ($product_price != false) {
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Product price found: '$product_price', however, no product name found", "logName" => "process_ep_files_php"));
        }
        if ($product_name == false && $product_price == false) {
            verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Neither product name nor price were found", "logName" => "process_ep_files_php"));
        }
    }

    verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Deleting file...", "logName" => "process_ep_files_php"));
    $query_args = array(
        "fileid" => $queryData[0][$x]["id"]
    );
    $query = "UPDATE epfiles_queue SET has_been_proccesed = 1 WHERE id = :fileid";
    pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_6");
    #unlink($ep_file_path.$epfile_folder."/".$folderName."/".$filename);
    verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done", "logName" => "process_ep_files_php"));
}

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Removing processed files from DB...", "logName" => "process_ep_files_php"));
$query_args = array();
$query = "DELETE FROM epfiles_queue WHERE has_been_proccesed = 1";
pdoExecuteQuery($pdo_sqlite_db, $query, $query_args, "query_7");
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done", "logName" => "process_ep_files_php"));

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Script done, exiting", "logName" => "process_ep_files_php"));

?>