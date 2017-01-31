<?php
require_once('_db/_db.php');

function fileExtension($str) {
	$name_parts = explode('.', $str);
	if (count($name_parts)==1) {
		return '';
	}
	else {
		return $name_parts[count($name_parts)-1];
	}
}

function urlStr($str, $keepExtension=true) {
	// Supprime les caractères non-ASCII, remplace les espaces par des underscores et retourne le string en caractères minuscules
	$extension = fileExtension($str);
	if ($extension!=='') {
		$str = substr($str, 0, strlen($str)-strlen($extension)-1);
	}
	$url = strtolower( preg_replace('/[^\a-zA-Z0-9_]/', '', str_replace(' ', '_', stripslashes(trim($str)))) );
	return $url.($keepExtension?'.'.$extension:'');
}

function splitToSentences($text, $implodeDelimiters = true) {
	$sentences = preg_split('/([^M][\.…!?])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	if ($implodeDelimiters && count($sentences)>1) {
		for ($i=0; $i<count($sentences); $i+=2) {
			$sentences[$i] = trim($sentences[$i].$sentences[$i+1]);		// Get back separators and append them to end of sentences (otherwise we lose the "M." and punctuation marks)
			$sentences[$i+1] = '';								// By definition, separators are odd-numbered
		}
		$sentences = array_values(array_filter($sentences));		// Renumber indexes
	}
	return $sentences;
}

function readerFormat($text) {
	return preg_replace('/_([^\s][^_]*[^\s])_/', '<em>$1</em>', $text);
}

function updateSentencesRefsCache($chapter_id = -1, $project_id = -1) {
	if ($project_id==-1) {
		$project_id = $_SESSION['project_id'];
	}
	$cacheFilter = array('project_id' => $project_id);
	if ($chapter_id>-1) {
		$cacheFilter['chapter_id'] = $chapter_id;
	}
	db_d('sentences_refs_cache', $cacheFilter);
	$chapterFilter = array('project_id' => $project_id);
	if ($chapter_id>-1) {
		$chapterFilter['id'] = $chapter_id;
	}
	$entities = array();
	$entities_r = db_s('entities', array('project_id' => $project_id, '!status' => 'rejected'));
	while ($ent = db_fetch($entities_r)) {
		$entities[$ent['name']] = $ent['id'];
		$synonyms = explode(',', $ent['synonyms']);
		foreach ($synonyms as $syn) {
			if ($syn!='') {
				$entities[$syn] = $ent['id'];
			}
		}
	}
	$chapters_r = db_s('chapters', $chapterFilter);

	db_x('ALTER TABLE sentences_refs_cache DISABLE KEYS;');
	while ($chap = db_fetch($chapters_r)) {
		$sentences = array_merge(array($chap['title']), splitToSentences($chap['text']));
		foreach ($sentences as $sidx => $sent) {
			$sentEntities = array();
			foreach ($entities as $ent => $ent_id) {
				if (strstr($sent, $ent)) {
					$sentEntities[] = $ent_id;
				}
			}
			$sentEntities = array_unique($sentEntities);
			foreach ($sentEntities as $ent_id) {
				db_i('sentences_refs_cache', array('entity_id' => $ent_id, 'project_id' => $project_id, 'chapter_id' => $chap['id'], 'sentence' => $sidx));
			}
		}
	}
	db_x('ALTER TABLE sentences_refs_cache ENABLE KEYS;');
}

function duplicateProject($refProjectId) {
	$currentProject = db_fetch(db_s('projects', array('id' => $refProjectId)));
	$currentProject['version'] = $currentProject['version']+1;
	unset($currentProject['id']);
	$newProjectId = db_i('projects', $currentProject);
	$chapters_r = db_s('chapters', array('project_id' => $refProjectId), array('id' => 'ASC'));
	while ($chapter = db_fetch($chapters_r)) {
		unset($chapter['id']);
		$chapter['project_id'] = $newProjectId;
		db_i('chapters', $chapter);
	}
	$entities_r = db_s('entities', array('project_id' => $refProjectId), array('id' => 'ASC'));
	while ($entity = db_fetch($entities_r)) {
		$oldEntityId = $entity['id'];
		unset($entity['id']);
		$entity['project_id'] = $newProjectId;
		$newEntityId = db_i('entities', $entity);
		db_u('chapters', array('project_id' => $newProjectId, 'place' => $oldEntityId), array('place' => $newEntityId));
	}
	updateSentencesRefsCache(-1, $newProjectId);
	return $newProjectId;
}

function mergeEntities($project_id, $id_main, $id_synonym) {
	$main = db_fetch(db_s('entities', array('id' => $id_main)));
	$synonym = db_fetch(db_s('entities', array('id' => $id_synonym)));
	$oldSynonyms = explode(',', $main['synonyms']);
	$synonyms = array_filter(array_merge($oldSynonyms, array($synonym['name'])));
	db_u('entities', array('id' => $id_main), array('synonyms' => implode(',', $synonyms), 'status' => 'corrected'));
	db_u('entities', array('id' => $id_synonym), array('status' => 'rejected'));
	db_u('sentences_refs_cache', array('entity_id' => $id_synonym, 'project_id' => $project_id), array('entity_id' => $id_main));
}


function defineDefaultChapterTimes() {
	$mois = array('janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre');
	$chapters_r = db_s('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => 'ASC', 'number' => 'ASC'));
	while ($chapter = db_fetch($chapters_r)) {
		$words = preg_split('/[\s\.,;:\?!«»\(\)"]/', $chapter['text']);
		foreach ($words as $w) {
			if (is_numeric($w)) {
				if ($w>1500 && $w<2500) {
				}
			}
			elseif (in_array($w, $mois)) {

			}
		}
	}
}

function defineDefaultChapterPlacesAndEntityColors() {
	// Set default colors to the 2/3 most represented entities _________________________________________________________________________________________________
	$entities = array();
	$ent_r = db_s('entities', array('project_id' => $_SESSION['project_id']));
	while ($ent = db_fetch($ent_r)) {
		$quotes = db_count(db_s('sentences_refs_cache', array('entity_id' => $ent['id'], 'project_id' => $_SESSION['project_id'])));
		@$entities[$ent['class']][$ent['id']] = $quotes;
	}
	$classes = array('place', 'character');
	foreach ($classes as $class) {
		$i = 1;
		$sub = $entities[$class];
		arsort($sub);
		foreach ($sub as $e => $_) {
			if ($i<16) {
				db_u('entities', array('id' => $e), array('color' => $i));
			}
			$i++;
		}
	}
	// Guess places of each chapter from the most cited ones ___________________________________________________________________________________________________
	$chapters_r = db_s('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => 'ASC', 'number' => 'ASC'));
	$lastGuess = '';
	while ($chapter = db_fetch($chapters_r)) {
		$placeGuess_r = db_x('select count(entity_id) c, entity_id from (sentences_refs_cache src left outer join entities e on src.entity_id=e.id) where src.project_id='.$_SESSION['project_id'].' and chapter_id='.$chapter['id'].' and e.class="place" group by entity_id order by c desc', false);
		if ($placeGuess = db_fetch($placeGuess_r)) {
			db_u('chapters', array('id' => $chapter['id']), array('place' => $placeGuess['entity_id']));
			$lastGuess = $placeGuess['entity_id'];
		}
		else {
			db_u('chapters', array('id' => $chapter['id']), array('place' => $lastGuess));
		}
	}
	// Fill-in default notices _________________________________________________________________________________________________________________________________
	$ent_r = db_s('entities', array('project_id' => $_SESSION['project_id']));
	while ($ent = db_fetch($ent_r)) {
		if ($firstSentence = db_fetch(db_s('sentences_refs_cache', array('entity_id' => $ent['id'], 'project_id' => $_SESSION['project_id']), array('chapter_id' => 'ASC', 'sentence' => 'ASC')))) {
			$chap = db_fetch(db_s('chapters', array('id' => $firstSentence['chapter_id'])));
			$chapSent = splitToSentences($chap['text']);
			db_u('entities', array('id' => $ent['id']), array('notice' => $chapSent[$firstSentence['sentence']].' ('.$chap['tome'].'.'.$chap['chapter'].'/'.$firstSentence['sentence'].')'));
		}
	}
}

function loadStoryInSession(){
	$_SESSION['story'] = array('stock' => array(), 'tomes' => array());
	$chapters_r = db_s('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => 'ASC', 'number' => 'ASC'));
	while ($chapter = db_fetch($chapters_r)) {
		if ($chapter['tome'] == null) {
			$_SESSION['story']['stock'][] = $chapter;
		}
		else {
			@$_SESSION['story']['tomes'][$chapter['tome']][] = $chapter;
		}
	}
}