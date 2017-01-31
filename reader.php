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
	$data['styles'] = ['reader'];
	$data['scripts'] = ['Hyphenator','jquery.viewport','waypoints','reader'];

	define('COOKIE_KEY', 'readpath_id');

	if (isset($_COOKIE[COOKIE_KEY])) {
		$_SESSION['cookie_id'] = $_COOKIE[COOKIE_KEY];
	}
	else {
		$maxId = db_fetch(db_s('read_paths', array(), array('cookie_id' => 'DESC')));
	 	setcookie(COOKIE_KEY, $maxId['cookie_id']+1, time()+3600*24*365);
		$_SESSION['cookie_id'] = $maxId['cookie_id']+1;
	}

	if ($story = db_fetch(db_s('projects', array('id' => $_REQUEST['id'], 'status' => 'published')))) {
		$_SESSION['story'] = $_REQUEST['id'];
		$data['story'] = $story;
		$chapters_r = db_s('chapters', array('project_id' => $story['id']), array('tome' => 'ASC', 'number' => 'ASC'));
		$_SESSION['sequence'] = array();
		while ($chapter = db_fetch($chapters_r)) {
			$_SESSION['sequence'][] = $chapter['id'];
		}

		$data['sequence'] = $_SESSION['sequence'];
	}

/*
	$GLOBALS['js'] = array('Hyphenator.min.js','jquery.viewport.js','sh.js');	#,'jquery.mobile.min.js'


	if (isset($_REQUEST['s'])) {
		$_SESSION['story'] = 'xx';
		$_SESSION['sequence'] = explode('/', $_REQUEST['s']);
	}
	elseif (isset($_REQUEST['h'])) {
		$_SESSION['story'] = $_REQUEST['h'];
		$storiesStructure = json_decode(file_get_contents('stories.json'), true);
		$hs = array();
		foreach ($storiesStructure as $idx => $story) {
			$hs[$story['id']] = $story['contents'];
		}
		$_SESSION['sequence'] = explode('/', str_replace('-', '.', $hs[$_REQUEST['h']]));
#		db_i('read_paths', array('session_id' => session_id(), 'cookie_id' => $_SESSION['cookie_id'], 'story' => $_SESSION['story'], 'chapter' => str_replace('.', '-', $_SESSION['sequence'][0]), 'date' => date('Y-m-d H:i:s')));
	}
	else {
		unset($_SESSION['sequence']);
	}

*/



	echo $blade->view()->make('reader',$data)->render();