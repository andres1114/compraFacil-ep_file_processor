<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
date_default_timezone_set('America/New_York');

//Include the db connection file
include_once "db_connection.php";
//Include the functions file
include_once "functions.php";

$verbose_output_mode = 1;

$script_1 = "queue_ep_files.php";
$script_2 = "process_ep_files.php";

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Starting the compraFacil main ep_file processor script", "logName" => "main_php"));
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Executing '$script_1' script...", "logName" => "main_php"));

$cmd = "php -q ".realpath(__DIR__)."/$script_1 2>&1 &";
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Used cmd: '$cmd'", "logName" => "main_php"));

$output = shell_exec($cmd);

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done", "logName" => "main_php"));
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "cmd response:\n$output", "logName" => "main_php"));
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Executing '$script_2' script...", "logName" => "main_php"));

$cmd = "php -q ".realpath(__DIR__)."/$script_2 2>&1 &";
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Used cmd: '$cmd'", "logName" => "main_php"));

$output = shell_exec($cmd);

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Done", "logName" => "main_php"));
verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "cmd response:\n$output", "logName" => "main_php"));

verbose(array("outputMode" => $verbose_output_mode, "outputMessage" => "Script done, exiting", "logName" => "main_php"));
?>