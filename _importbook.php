<?php
	header('Content-Type: application/json');

#	"_importbook.php?file=examples-books/candide.txt"

	session_start();
	include('_db/_db.php');
	include('_commontools.php');

	$jsonData = shell_exec('python python/characterStats.py --file="'.$_REQUEST['file'].'" --mincount=auto -a');
	$data = json_decode($jsonData, true);
	if ($jsonData!=='' && $data!==null) {
		$classes = $data['classes'];
		$substitutions = $data['substitutions'];
		$fullWordsAndClasses = array();
		db_d('entities', array('project_id' => $_SESSION['project_id'], 'status' => 'generated'));
		$found = 0;
		foreach ($classes as $class => $words) {
			foreach ($words as $word) {
				$data = array(
								'project_id' => $_SESSION['project_id'],
								'stats_key' => $word,
								'stats_keys' => $word,
								'name' => $substitutions[$word],
								'class' => $class,
								'status' => 'generated',
								);
				db_i('entities', $data);
				$found++;
			}
		}
		updateSentencesRefsCache();
		defineDefaultChapterPlacesAndEntityColors();
		echo json_encode(array('status' => 'ok', 'found' => $found));
	}
	else {
		echo json_encode(array('status' => 'error', 'returned' => $jsonData));
	}
