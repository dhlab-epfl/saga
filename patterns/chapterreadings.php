<?php
	date_default_timezone_set('Europe/Zurich');
	header('Content-Type: text/plain');

	include('../_db/_db.php');

	$chaps = db_x('select distinct(chapter) as c, (0 + SPLIT_STR(chapter, "-", 1))  as c1, (0 + SPLIT_STR(chapter, "-", 2))  as c2 from read_paths order by c1 ASC, c2 ASC');
	while ($chap = db_fetch($chaps)) {
		echo $chap['c']."\t";
		$reads = db_count(db_s('read_paths', array('chapter' => $chap['c'], 'stat' => 'valid')));
		echo $reads."\t";
		$cparts = explode('-', $chap['c']);
		echo $cparts[0]."\t".$cparts[1]."\t";
		echo "\n";
	}
