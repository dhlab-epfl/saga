<?php
	date_default_timezone_set('Europe/Zurich');
	header('Content-Type: text/plain');

	include('../../3n-webapp/_db/_db.php');
	$stories = json_decode(file_get_contents('../../3n-webapp/stories.json'), true);
	db_x('SET SESSION group_concat_max_len = 1000000;');
	$patterns = db_x('select * from (select
										count(chapter) as cc,
										group_concat(chapter order by date asc) as chapters,
										group_concat(UNIX_TIMESTAMP(date) order by date asc) as times,
										group_concat(date order by date asc) as dates,
										group_concat(story order by date asc) as stories,
										group_concat(distinct hour(date) order by hour(date) ASC) as hours,
										"" as avghour,
										session_id,
										seq_count
									from read_paths
									group by session_id
									order by date asc, session_id asc) as t
								where seq_count>1 ORDER BY seq_count DESC;');
	$allChapters = array();
	$allUsers = array();
	$case = 0;
	$allTimes = array();
	$allSequences = array();
	$timesByChapters = array();
	db_u('read_paths', array(), array('stat' => 'invalid'));
	while ($pattern = db_fetch($patterns)) {
		$x = explode(',', $pattern['times']);
		$dates = explode(',', $pattern['dates']);
		$chaps = explode(',', $pattern['chapters']);
		$chapStories = explode(',', $pattern['stories']);
		$timesRep = array();
		$storyRep = array();
		for ($i=1; $i<count($x); $i++) {
			$time = $x[$i]-$x[$i-1];
			$allTimes[] = $time;

			$chapNum = 0;
			foreach ($stories as $_ => $fields) {
				$storyKey = $fields['id'];
				$storyChapters = explode('/', $fields['contents']);
				if (in_array($chaps[$i], $storyChapters)) {
					$chapNum = array_search($chaps[$i], $storyChapters)+1;
					break;
				}
			}

			$storyRep[] = $chapStories[$i];
			if (false) {
				$timesRep[] = round($time/60);
			}
			else {
				if ($time < 60) {
					if (false&&substr($timesRep[count($timesRep)-1], 0, 1)=='-') {
						$timesRep[count($timesRep)-1] = '---->';
					}
					else {
						$timesRep[] = '-->';
					}
					if (count($currentSeq['t'])>0) {
						$allSequences[] = $currentSeq;
					}
					$currentSeq = array('t'=>array(),'c'=>array());
				}
				else if ($time > 60*60) {
					$timesRep[] = '///';
					if (count($currentSeq['t'])>0) {
						$allSequences[] = $currentSeq;
					}
					$currentSeq = array('t'=>array(),'c'=>array());
				}
				else {
					$timesRep[] = round($time/60);
					$currentSeq['t'][] = $time;
					$currentSeq['c'][] = $chapStories[$i].'-'.$chapNum;
					@$allUsers[$pattern['session_id']][] = $time;
					$timesByChapters[$chaps[$i]][] = $time;
				}
			}
		}
		if (array_sum($timesRep)>0) {
			db_u('read_paths', array('session_id' => $pattern['session_id']), array('stat' => 'valid'));
			echo (++$case)."\t".$pattern['avghour']."\t".implode("\t", $timesRep)."\n";
			echo "\t"."\t".implode("\t", $storyRep)."\n";
			echo str_repeat('-', 250)."\n";
		}
	}
	echo '-------------------------------------------------------'."\n";
	foreach ($allSequences as $seq) {
		echo count($seq['c'])."\t";
		echo implode("\t", $seq['c'])."\n";
	}
	echo '======================================================='."\n";
	foreach ($timesByChapters as $chapter => $times) {
		list($st, $sc) = explode('-', $chapter);
		$physChap = db_fetch(db_s('sequences', array('tome' => $st, 'chapter' => $sc)));
		echo $chapter."\t".(array_sum($times)/count($times))."\t".count($times)."\t".$physChap['words']."\n";
	}
/*
	foreach ($allUsers as $user => $counts) {
		echo substr($user, 0, 5)."\t".implode("\t", $counts)."\n";
	}
*/
