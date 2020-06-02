<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

header("Content-Type: text/plain");

$config = include('config.php');

$hub_signature = array_key_exists('HTTP_X_HUB_SIGNATURE', $_SERVER) ? $_SERVER['HTTP_X_HUB_SIGNATURE'] : '';
$hub_action = array_key_exists('HTTP_X_GITHUB_EVENT', $_SERVER) ? $_SERVER['HTTP_X_GITHUB_EVENT'] : null;

if (is_null($hub_action)) {
    http_response_code(403);
    die('no action defined');
}

$data = file_get_contents('php://input');
file_put_contents("log.json", $data);

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

$repository = $data['repository']['full_name'];

if (!array_key_exists($repository, $config['target'])) {
    http_response_code(403);
    die('unsupported repository: '. $repository);
}

$branch = explode('/', $data['ref'])[2];

if (!array_key_exists($branch, $config['target'][$repository])) {
    http_response_code(403);
    die('unsupported branch: '. $branch);
}

if (is_null($config['target'][$repository])) {
    die('ignored branch');
}

$path = rtrim($config['target'][$repository][$branch], '/');

if ($path === '' || !is_dir($path)) {
    http_response_code(403);
    die('invalid target path: '. $path);
}

// if (!file_exists($path.'/git-sync')) {
//     die('the target path is not managed through git-sync: '. $path);
// }

if (!array_key_exists('commits', $data) || empty($data['commits'])) {
    http_response_code(403);
    die('the request does not contain any commits');
}

$actions = [];

foreach ($data['commits'] as $commit) {
    foreach (array_merge($commit['added'], $commit['modified']) as $file) {
        // - https://raw.githubusercontent.com/aoloe/test-sync/master/README.md
        $tmp_file = tempnam(sys_get_temp_dir(), 'tmp-git-sync');
        $status = file_put_contents($tmp_file, fopen(
            "https://raw.githubusercontent.com/{$repository}/{$branch}/{$file}", 'rb'));
        if ($status === false) {
            http_response_code(403);
            die('could not get the file from the git repository');
        }
        // $file_data = file_get_contents(
        //     "https://raw.githubusercontent.com/{$repository}/{$branch}/{$file}");
        $action[$file] = $tmp_file;
    }
    foreach ($commit['removed'] as $file) {
        $action[$file] = null;
    }
}

file_put_contents("log-action.json", json_encode($action));


if (!is_dir($path.'/.git')) {
    http_response_code(403);
    die('the target path is not a git repository: '. $path);
}

shell_exec( 'cd '.$path.' && git reset --hard HEAD && git pull' );
