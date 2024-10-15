<?php

// Obtain the current Unraid state to get the CSRF token
if ( ! isset($var)) {
    $var = parse_ini_file('/usr/local/emhttp/state/var.ini');
}
$csrftoken = $var["csrf_token"];

// Load the session state into the environment for the CGI calls
foreach ($_SERVER as $key => $value) {
    if (is_array($value)) {
        $out = implode($value);
        putenv("{$key}={$out}");
    } else {
        putenv("{$key}={$value}");
    }
}
putenv("UNRAID_CSRF_TOKEN={$csrftoken}");

$cmd            = "tailscale --socket=/var/run/tailscale/tailscaled.sock web -cgi -prefix=/plugins/tailscale/interface.php/";
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
 );
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        fwrite($pipes[0], $_POST['ts_data']);
        fclose($pipes[0]);
    }

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $return_value = proc_close($process);
}

$out     = explode(PHP_EOL, $output ?? array());
$headers = true;
foreach ($out as $line) {
    if ($headers) {
        if (trim($line) == "") {
            $headers = false;
            continue;
        }
        header($line);
    } else {
        echo($line . PHP_EOL);
    }
}
