<?php
	header('Content-Type: application/json');

#	"test-python.php?file=examples-books/candide.txt"

	$jsonData = shell_exec('python python/characterStats.py --file="'.$_REQUEST['file'].'" --mincount=auto -ag');
	if ($jsonData!=='') {
		$data = json_decode($jsonData, true);

		$classes = $data['classes'];
		$substitutions = $data['substitutions'];
		$fullWordsAndClasses = array();
		foreach ($classes as $class => $words) {
			foreach ($words as $word) {
				$fullWordsAndClasses[$substitutions[$word]] = $class;
			}
		}

		echo '<table>';
		foreach ($fullWordsAndClasses as $word => $class) {
			echo '<tr><td>'.$word.'</td><td>'.$class.'</td></tr>';
		}
		echo '</table>';


		$charsGraph = $data['charsGraph'];		# https://nylen.io/d3-process-map/graph.php?dataset=les-mis
		$bipGraph = $data['bipGraph'];
		$eventGraph = $data['eventGraph'];		# http://csclub.uwaterloo.ca/~n2iskand/?page_id=13
	}