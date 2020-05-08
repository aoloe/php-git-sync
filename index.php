<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Content-Type: text/plain");

$config = include('config.php');

$hub_signature = array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER) ? $_SERVER['HTTP_X_HUB_SIGNATURE'] : '';
$hub_action = array_key_exists('HTTP_X_GITHUB_EVENT', $_SERVER) ? $_SERVER['HTTP_X_GITHUB_EVENT'] : null; //  => 'push'

if (is_null($hub_action)) {
    http_response_code(403);
    die('no action defined');
}

$data = file_get_contents('php://input');

$signature = hash_hmac('sha1', $data, $config['secret']);

if ($hub_signature !== 'sha1='.$signature) {
    http_response_code(403);
    die('invalid signature');
}

if ($hub_action === 'ping') {
    die('got the ping');
}

if ($hub_action !== 'push') {
    http_response_code(403);
    die('unsupported action: '. $hub_action);
}

$data = json_decode($data, true);

// 'HTTP_X_HUB_SIGNATURE' => 'sha1=' . $signature,


$branch = explode('/', $data['ref'])[2];

if (!array_key_exists($branch, $config['target'])) {
    http_response_code(403);
    die('unsupported branch: '. $branch);
}

if (is_null($config['target'])) {
    die('ignored branch');
}

$path = rtrim($config['target'][$branch], '/');

if ($path === '' || !is_dir($path)) {
    http_response_code(403);
    die('invalid target path: '. $path);
}

if (!is_dir($path.'/.git')) {
    http_response_code(403);
    die('the target path is not a git repository: '. $path);
}

// logging
// file_put_contents("log.txt", json_encode($_REQUEST));
// file_put_contents("json.txt", json_encode($data));

shell_exec( 'cd '.$path.' && git reset --hard HEAD && git pull' );
