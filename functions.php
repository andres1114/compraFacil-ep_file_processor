<?php

//Include the PHPMailer library
require("PHPMailer/PHPMailerAutoload.php");

function verbose($args) {
    switch ($args["outputMode"]) {
        case 0:
            echo "(".Date("Y-m-d H:i:s").") ".$args["outputMessage"]."\n";
            break;
        case 1:
            echo "(".Date("Y-m-d H:i:s").") ".$args["outputMessage"]."\n";
            if (!file_exists(realpath(__DIR__)."/logs")) {
                mkdir(realpath(__DIR__)."/logs");
            }
            file_put_contents(realpath(__DIR__)."/logs/".date("Y-m-d")."_".$args["logName"].".log", "(".Date("Y-m-d H:i:s").") ".$args["outputMessage"]."\n", FILE_APPEND);
            break;
    }
}
function doCurl($url,$headers,$rtype,$data) {//url, content type, request type,  data
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $rtype);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
function send_email($args) {

    //Define the SMTP email variables
    $smtp_email_username =      "demo@mailgun.eprensa.com";
    $smtp_email_password =      "Jwp3uByU";
    $smtp_email_smtp_name =     "smtp.mailgun.org";
    $smtp_email_smtp_port =     25;

    //Create the PHPMailer email object
    $smtp_email_object = new PHPMailer();

    //Define the email object properties
    $smtp_email_object->CharSet = "utf-8";
    $smtp_email_object->IsSMTP(true);
    $smtp_email_object->SMTPAuth = true;
    $smtp_email_object->Username = $smtp_email_username;
    $smtp_email_object->Password = $smtp_email_password;
    $smtp_email_object->SMTPSecure = "tls";
    $smtp_email_object->Host = $smtp_email_smtp_name;
    $smtp_email_object->Port = $smtp_email_smtp_port;
    $smtp_email_object->From = $args['email_from'];
    $smtp_email_object->FromName = $args['email_from_username'];

    foreach (explode(";",$args['email_to']) as $email_to) {
        $smtp_email_object->AddAddress($email_to, "");
    }

    $smtp_email_object->Subject = $args['email_subject'];
    $smtp_email_object->IsHTML(true);

    //Check for file attachment
    if (isset($args['attachment_type']) && isset($args['attachment'])) {
        switch ($args['attachment_type']) {
            case "text":
                $smtp_email_object->addStringAttachment($args['attachment'], $args['attachment_filename']);
                break;
            case "octet_stream":
                $smtp_email_object->addAttachment($args['attachment'], $args['attachment_filename'], 'base64', 'application/octet-stream');
                break;
        }

    }

    //Set the email body content
    $smtp_email_object->Body = $args['email_html_message'];

    //Send the email
    $smtp_email_status = $smtp_email_object->Send();

    //Check whether the email was sent
    if (!$smtp_email_status) {
        file_put_contents(realpath(__DIR__)."/error.log","(".Date("Y-m-d H:i:s").") mailgun_webhook_bounce.php > ERR_EMAIL_WAS_NOT_SENT There has been an error attempting to send an email with error message ".$smtp_email_object->ErrorInfo."\r",FILE_APPEND);
    }

}
function getDirectorySize($args) {
    if ($args["cross_server_checking"]) {
        $cmd = "ssh ".$args["server_name"]." du -bs ".$args["dir_path"];
    } else {
        $cmd = "du -bs ".$args["dir_path"];
    }

    $cmd_process = popen($cmd, 'r');
    $folder_size = fgets ($cmd_process, 4096);
    $folder_size = substr ($folder_size, 0, strpos ($folder_size, "\t" ) );
    pclose ($cmd_process);
    return $folder_size;
}
function getFileSize($args) {
    $cmd = "wc -c < ".$args["file_path"];

    $cmd_process = popen($cmd, 'r');
    $file_size = fgets ($cmd_process, 4096);
    $file_size = substr ($file_size, 0, strpos ($file_size, "\n" ) );
    pclose ($cmd_process);
    return $file_size;
}
function byteFormat($bytes, $unit = "", $decimals = 2) {
    $units = array('B' => 0, 'KB' => 1, 'MB' => 2, 'GB' => 3, 'TB' => 4, 'PB' => 5, 'EB' => 6, 'ZB' => 7, 'YB' => 8);

    $value = 0;
    if ($bytes > 0) {
        // Generate automatic prefix by bytes
        // If wrong prefix given
        if (!array_key_exists($unit, $units)) {
            $pow = floor(log($bytes)/log(1024));
            $unit = array_search($pow, $units);
        }

        // Calculate byte value by prefix
        $value = ($bytes/pow(1024,floor($units[$unit])));
    }

    // If decimals is not numeric or decimals is less than 0
    // then set default value
    if (!is_numeric($decimals) || $decimals < 0) {
        $decimals = 2;
    }

    // Format output
    return sprintf('%.' . $decimals . 'f '.$unit, $value);
}
?>