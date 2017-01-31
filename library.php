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


$books = array();
$books_r = db_s('projects', array('status' => 'published'), array('title' => 'ASC'));
while ($book = db_fetch($books_r)) {
	$books[] = $book;
}

echo $blade->view()->make('library',$data)->render();