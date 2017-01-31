<?php

session_start();

require 'vendor/autoload.php';
require '_db/_db.php';
use Philo\Blade\Blade;

$views = __DIR__ . '/views';
$cache = __DIR__ . '/cache';
$key = 'OqsWo66JH24c87qrem7XcFXApz4pbe40';
$blade = new Blade($views, $cache);
$data = [];
$data['msg_errors'] = [];
$data['msg_success'] = [];
$data['scripts'] = ['home'];

if(isset($_REQUEST['logout'])){
	session_destroy();
	header('Location: /');
	die();
}

$lastBooks = array();
$published_r = db_x('SELECT * FROM projects WHERE status="published" ORDER BY title ASC LIMIT 5;');
while ($pub = db_fetch($published_r)) {
	$lastBooks[] = $pub;
}
$data['lastBooks'] = $lastBooks;

echo $blade->view()->make('welcome',$data)->render();