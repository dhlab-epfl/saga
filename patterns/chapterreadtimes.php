<?php
	header('Content-Type: text/plain');

	include('../_db/_db.php');
	$patterns = db_x('select * from (select count(chapter) as cc, group_concat(chapter order by date asc) as chapters, group_concat(UNIX_TIMESTAMP(date) order by date asc) as dates, session_id from read_paths group by session_id order by date asc, session_id asc) as t where cc>1;');

	$allChapters = array();
	while ($pattern = db_fetch($patterns)) {
		$x = explode(',', $pattern['dates']);
		$chaps = explode(',', $pattern['chapters']);
		sort($x);
		$timesRep = array();
		for ($i=1; $i<count($x); $i++) {
			$time = $x[$i]-$x[$i-1];
			if ($time < 60) {
				$timesRep[] = '-->';
			}
			else if ($time > 60*60) {
				$timesRep[] = '///';
			}
			else {
				$timesRep[] = $time;
			@$allChapters[$chaps[$i-1]][] = $time;
			}
		}
		if (array_sum($timesRep)>0) {
#			echo implode("\t", $timesRep)."\n";
		}
	}

	foreach ($allChapters as $chapter => $counts) {
		echo $chapter."\t".implode("\t", $counts)."\n";
	}

