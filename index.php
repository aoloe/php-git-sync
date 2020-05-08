<?php

$config = include('config.php');
// print_r($config);

shell_exec( 'cd .. && git reset --hard HEAD && git pull' );
