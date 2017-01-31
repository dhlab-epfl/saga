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
$data['styles'] = ['alertify/alertify.min', 'alertify/themes/semantic.min'];
$data['scripts'] = ['home'];


require '_login.php';


$data['projects'] = array();
$projects_r = db_x('SELECT GROUP_CONCAT(DISTINCT CONCAT(id,",",version) ORDER BY version DESC SEPARATOR ";") ids, title FROM projects WHERE user_id="'.$_SESSION['logged']['id'].'" AND status="open" GROUP BY saga_id ORDER BY title ASC;');
while ($project = db_fetch($projects_r)) {
	$ids = explode(';', $project['ids']);
	$project['ids'] = array();
	foreach ($ids as $idpair) {
		$project['ids'][] = explode(',', $idpair);
	}
	$data['projects'][] = $project;
}

/*
							'candide.txt' => 'Candide de Voltaire',
							'20000lieuessouslesmers.txt' => '20000 lieues sous les mers',
							'lesmiserables.txt' => 'Les Misérables',
							'lerougeetlenoir.txt' => 'Le Rouge et le Noir',
							'madamebovary.txt' => 'Madame Bovary',
							'sh.txt' => 'La simulation humaine',
							'voyageauboutdelanuit.txt' => 'Voyage au bout de la nuit',
*/

$data['examples'] = array();
$exemples = db_s('projects', array('status' => 'sample'), array('title' => 'ASC'));
while ($e = db_fetch($exemples)) {
	$data['examples'][$e['id']] = $e['title'];
}

echo $blade->view()->make('home',$data)->render();