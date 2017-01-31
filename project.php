<?php

session_start();

require 'vendor/autoload.php';
require '_db/_db.php';
require '_commontools.php';
use Philo\Blade\Blade;

$views = __DIR__ . '/views';
$cache = __DIR__ . '/cache';
$key = 'OqsWo66JH24c87qrem7XcFXApz4pbe40';
$blade = new Blade($views, $cache);
$pageData = [];

$pageData['msg_errors'] = [];
$pageData['msg_success'] = [];
$pageData['styles'] = ['alertify/alertify.min', 'alertify/themes/semantic.min'];
$pageData['scripts'] = ['editor'];

require '_login.php';

if (isset($_REQUEST['new'])) {
	switch ($_REQUEST['new']) {
		case 'import':
			$txtFilePath = '';
			$chapterContents = array();
			$projectTitle = '';
			if (isset($_REQUEST['example'])) {
				$sample = db_fetch(db_s('projects', array('id' => $_REQUEST['example'], 'status' => 'sample')));
				$_SESSION['project_id'] = duplicateProject($sample['id']);
				$sagasId = db_fetch(db_s('projects', array(), array('saga_id' => 'DESC')));
				$updatedData = array(
										'user_id' => $_SESSION['logged']['id'],
										'version' => 1,
										'saga_id' => $sagasId['saga_id']+1,
										'author' => '',
										'summary' => '',
										'status' => 'open',
									);
				db_u('projects', array('id' => $_SESSION['project_id']), $updatedData);
			}
			elseif (isset($_FILES['book'])) {
				$tmp_name = $_FILES['book']['tmp_name'];
				$fileName = urlStr($_FILES['book']['name']);
				move_uploaded_file($tmp_name, 'users-books/'.$fileName);
				list($projectTitle, $_) = explode('.', $_FILES['book']['name']);
				shell_exec('python python/autoformat.py --file="'.'users-books/'.$fileName.'" --out="'.'tmp/'.'"');
				$txtFilePath = 'tmp/'.str_replace('.txt', '-compact.txt', $fileName);
				$chapterContents = json_decode(file_get_contents('tmp/'.str_replace('.txt', '.json', $fileName)), true);
				if (count($chapterContents)>0) {
					$maxSaga = db_fetch(db_s('projects', array(), array('saga_id' => 'DESC')));
					$projectData = array(
									'user_id' => $_SESSION['logged']['id'],
									'title' => $projectTitle,
									'status' => 'open',
									'version' => 1,
									'saga_id' => $maxSaga['id']+1
								);
					$_SESSION['project_id'] = db_i('projects', $projectData);

					foreach($chapterContents as $cData){
						$cData['project_id'] = $_SESSION['project_id'];
						if (!isset($cData['tome'])) {
							$cData['tome'] = '1';
						}
						db_i('chapters', $cData);
					}
					$pageData['extractEntities'] = $txtFilePath;
				}
				else {
					$_SESSION['project_id'] = 0;
					die('Erreur : aucun chapitre détecté.');
				}
			}

			break;
		case 'empty':
			$_SESSION['project_id'] = db_i('projects', array('user_id' => $_SESSION['logged']['id'], 'title' => $_REQUEST['title'], 'status' => 'open', 'version' => 1));
			db_i('chapters', array('project_id' => $_SESSION['project_id'], 'tome' => 1, 'number' => 1, 'title' => 'Chapitre premier', 'text' => 'C’est ici que tout commence...'));
			break;
		default:break;
	}
	$_REQUEST['load'] = $_SESSION['project_id'];
}

if (isset($_REQUEST['load'])) {
	if ($project = db_fetch(db_s('projects', array('id' => $_REQUEST['load'], 'user_id' => $_SESSION['logged']['id'])))) {
		$_SESSION['project_id'] = $project['id'];
		$_SESSION['project_title'] = $project['title'];
		$_SESSION['project_author'] = $project['author'];
		$_SESSION['project_version'] = $project['version'];
		loadStoryInSession();
	}
}

$pageData['project_title'] = $_SESSION['project_title'];
$pageData['project_version'] = $_SESSION['project_version'];

echo $blade->view()->make('project',$pageData)->render();

