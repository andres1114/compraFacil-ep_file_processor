<?php
	
	function verbose($args) {
        switch ($args["outputMode"]) {
            case 0:
                echo $args["outputMessage"];
                break;
            case 1:
                echo $args["outputMessage"];
                file_put_contents(realpath(__DIR__)."/".$args["logName"].".log", $args["outputMessage"], FILE_APPEND);
                break;
        }
	}
?>