<?php
session_start();
header('Vary: Accept');
header('Cache-Control: no-cache, must-revalidate');

require 'vendor/autoload.php';
require '_db/_db.php';
use Philo\Blade\Blade;

$views = __DIR__ . '/views';
$cache = __DIR__ . '/cache';
$key = 'OqsWo66JH24c87qrem7XcFXApz4pbe40';
$blade = new Blade($views, $cache);

define('CR', "\n");
define('TAB', "\t");
$colors_ref = array(
'#d8d8d8',
'#ffbffb',
'#e53950',
'#00becc',
'#468c7e',
'#262b4d',
'#8800ff',
'#b073e6',
'#c9cc99',
'#8c7c69',
'#1b4d13',
'#ffcc00',
'#acdae6',
'#bf8f9c',
'#8c3f23',
'#4d4439',
'#ffc480',
'#39e695',
'#bf0099',
'#003380',
'#3d1040',
'#ff8c40',
'#d96c98',
'#2d86b3',
'#731d34',
'#402200',
'#0081f2',
'#b8d936',
'#30b300',
'#000066',
'#000033',
'#b6f2de',
'#d91d00',
'#a66f00',
'#594355',
'#0d2b33',
'#f2c6b6',
'#9999cc',
'#732699',
'#595300',
'#330e00'
);



function execute($f) {
	global $blade;
	global $colors_ref;
	switch ($f) {
		/* LIBRARY ********************************************************************************************************************************************/
		case 'searchBooks':
			$books = db_s('projects', array('%title%' => explode(' ', $_REQUEST['q']), 'status' => 'published'));
			while ($book = db_fetch($books)) {
				echo $blade->view()->make('partials.bookItem', array('book' => $book))->render();
			}
			break;

		/* ENTITIES *******************************************************************************************************************************************/

		case 'getEntityOverlay':
			require '_commontools.php';
			header('Content-Type: text/html; charset=utf-8');

			$entity = db_fetch(db_s('entities', array('id' => $_REQUEST['entity_id'], 'project_id' => $_SESSION['project_id'])));
			$entity['synonyms'] = array_filter(explode(',', $entity['synonyms']));

			$otherEntities = array();
			$others_r = db_s('entities', array('class' => $entity['class'], 'project_id' => $_SESSION['project_id'], '!status' => 'rejected'), array('name' => 'ASC'));
			while ($e = db_fetch($others_r)) {
				if ($e['id']!=$entity['id']) {
					$otherEntities[] = $e;
				}
			}

			// Gather excerpts for each reference ____________________________________________________________________________________________________________
			$entity_refs_r = db_x('SELECT DISTINCT(chapter_id) cid, c.text, CONCAT_WS(".", c.tome, c.number) cnum, src.sentence FROM (sentences_refs_cache src JOIN chapters c ON c.id=src.chapter_id) WHERE entity_id='.$entity['id'].' ORDER BY c.tome, c.number;', false);
			$excerpts = array();
			while ($e = db_fetch($entity_refs_r)) {
				$sentences = splitToSentences($e['text']);
				$sIdx = $e['sentence'];
				$excerpt = ($sIdx==0?'':$sentences[$sIdx-1]).' '.$sentences[$sIdx].' '.($sIdx==count($sentences)-1?'':$sentences[$sIdx+1]);
				$excerpts[] = array('text' => readerFormat($excerpt), 'ref' => $e['cnum'], 'sentence' => $e['sentence']);
			}

			echo $blade->view()->make('overlays.entity', array('e' => $entity, 'otherEntities' => $otherEntities, 'excerpts' => $excerpts))->render();
			break;

		case 'getEntities':
			header('Content-Type: application/json');
			$entities = array();
			$entities_r = db_s('entities', array('project_id' => $_SESSION['project_id'], '!status' => 'rejected'), array('name' => 'ASC'));
			while ($ent = db_fetch($entities_r)) {
				$ent['citations'] = db_count(db_s('sentences_refs_cache', array('project_id' => $_SESSION['project_id'], 'entity_id' => $ent['id'])));
				$entities[$ent['class']][] = $ent;
			}
			echo json_encode($entities);
			break;

		case 'newEntity':
			require '_commontools.php';
			$_REQUEST['name'] = trim($_REQUEST['name']);
			if ($test = db_fetch(db_s('entities', array('project_id' => $_SESSION['project_id'], 'name' => $_REQUEST['name'])))) {
				if ($test['status']=='rejected') {
					db_u('entities', array('id' => $test['id'], 'name' => $_REQUEST['name']), array('status' => 'corrected'));
				}
				echo $test['id'];
			}
			else {
				echo db_i('entities', array('project_id' => $_SESSION['project_id'], 'name' => $_REQUEST['name'], 'class' => $_REQUEST['class']));
			}
			updateSentencesRefsCache();
			break;

		case 'changeColorEntity':
			db_u('entities', array('id' => $_REQUEST['entity_id'], 'project_id' => $_SESSION['project_id']), array('color' => $_REQUEST['color']));
			break;

		case 'changeTypeEntity':
			$entity = db_fetch(db_s('entities', array('id' => $_REQUEST['entity_id'])));
			db_u('entities', array('id' => $_REQUEST['entity_id'], 'project_id' => $_SESSION['project_id']), array('class' => $_REQUEST['type']));
			if ($entity['class']=='place' && $_REQUEST['type']!='place') {
				db_u('chapters', array('place' => $entity['id'], 'project_id' => $_SESSION['project_id']), array('place' => 0));
			}
			break;

		case 'updateEntity':
			$data = array('status' => $_REQUEST['status']);
			if (isset($_REQUEST['class'])) {
				$data['class'] = $_REQUEST['class'];
			}
			db_u('entities', array('id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id']), $data);
			break;

		case 'deleteEntity':
			db_u('entities', array('id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id']), array('status' => 'rejected'));
			db_u('chapters', array('place' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id']), array('place' => 0));
			break;

		case 'removeChapterEntity':
			db_d('sentences_refs_cache', array('project_id' => $_SESSION['project_id'], 'entity_id' => $_REQUEST['entity_id'], 'chapter_id' => $_REQUEST['chapter_id']));
			break;

		case 'getEntityNgram':
			header('Content-Type: text/plain; charset=utf-8');
			echo 'chapter'.TAB.'n'.TAB.'cid'.CR;
			if ($entity = db_fetch(db_s('entities', array('id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id'])))) {
				$tot = db_count(db_s('sentences_refs_cache', array('entity_id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id'])));
				$occurrences_r = db_x('SELECT c.id, c.tome, c.number AS chapter, SUM(IF(entity_id='.$_REQUEST['id'].', 1, 0)) AS n FROM (sentences_refs_cache src LEFT OUTER JOIN chapters c ON src.chapter_id=c.id) WHERE src.project_id='.$_SESSION['project_id'].' AND c.project_id='.$_SESSION['project_id'].' AND c.tome>0 GROUP BY chapter_id ORDER BY tome ASC, number ASC', false);
				while ($o = db_fetch($occurrences_r)) {
					echo $o['tome'].'.'.$o['chapter'].TAB.(($tot>0)?$o['n']/$tot:0).TAB.$o['id'].CR;
				}
			}
			break;

		case 'saveEntity':
			db_u('entities', array('id' => $_REQUEST['entity_id'], 'project_id' => $_SESSION['project_id']), array('notice' => $_REQUEST['notice']));
			break;

		case 'mergeEntities':
			require '_commontools.php';
			mergeEntities($_SESSION['project_id'], $_REQUEST['main_id'], $_REQUEST['other_id']);
			break;

		case 'deleteSynonym':
			$entity = db_fetch(db_s('entities', array('project_id' => $_SESSION['project_id'], 'id' => $_REQUEST['entity_id'])));
			$synonyms = explode(',', $entity['synonyms']);
			foreach (array_keys($synonyms, $_REQUEST['form']) as $idx) {
				unset($synonyms[$idx]);
			}
			db_u('entities', array('id' => $entity['id']), array('synonyms' => implode(',', $synonyms)));
			break;

		/* CHAPTERS *******************************************************************************************************************************************/

		case 'getChapterOverlay':
			header('Content-Type: text/html; charset=utf-8');
			$chapter = db_fetch(db_s('chapters', array('id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id'])));
			// Compute date/time information _________________________________________________________________________________________________________________
			$start = preg_split('/[\s\-:]/', $chapter['time_start']);
			$dateMarkers = ['y','m','d','h','i'];
			if ($chapter['time_start']>0) {
				$end = preg_split('/[\s\-:]/', $chapter['time_end']);
				foreach ($dateMarkers as $idx => $d) {
					$chapter[$d] = ($start[$idx]==$end[$idx]?$start[$idx]:'');
				}
			}
			else {
				foreach ($dateMarkers as $idx => $d) {
					$chapter[$d] = '';
				}
			}
			// Gather all quoted entities ____________________________________________________________________________________________________________________
			$entities = array('character' => array(), 'place' => array(), 'other' => array());
			$entities_r = db_x('SELECT DISTINCT(entity_id) e FROM sentences_refs_cache WHERE chapter_id='.db_escape($chapter['id']).';', false);
			while ($e = db_fetch($entities_r)) {
				if ($c = db_fetch(db_s('entities', array('id' => $e['e'], '!status' => 'rejected')))) {
					$entities[$c['class']][] = $c;
				}
			}
			// Format text in HTML, underline entities _______________________________________________________________________________________________________
			function sortByWordLength($a,$b){
				return strlen($b)-strlen($a);
			}

			$textHTML = str_replace("\n", "", nl2br($chapter['text']));
			foreach ($entities as $class => $classEntities) {
				foreach ($classEntities as $e) {
					$surfaceForms = array_filter(array_merge(explode(',', $e['synonyms']), array($e['name'])));
					usort($surfaceForms, 'sortByWordLength');
					foreach ($surfaceForms as $sf) {
						$textHTML = preg_replace('/([\*_\s\(\'’“])'.$sf.'([\*_\s\.,;:\)”])/', '\\1<em class="'.$class.' color_'.$e['color'].'">'.$sf.'</em>\\2', $textHTML);
					}
				}
			}
			$chapter['textHTML'] = $textHTML;
			// Gather all available places ___________________________________________________________________________________________________________________
			$allPlaces = array(array('id' => 0, 'name' => '-'));
			$entities_r = db_s('entities', array('project_id' => $_SESSION['project_id'], 'class' => 'place', '!status' => 'rejected'), array('name' => 'ASC'));
			while ($e = db_fetch($entities_r)) {
				$allPlaces[$e['id']] = $e;
			}
			// _______________________________________________________________________________________________________________________________________________
			$data = array('c' => $chapter, 'chapterChars' => $entities['character'], 'allPlaces' => $allPlaces);
			echo $blade->view()->make('overlays.chapter',$data)->render();
			break;

		case 'newChapter':
			require '_commontools.php';
			$_REQUEST['name'] = trim($_REQUEST['name']);
			$id = db_i('chapters', array('project_id' => $_SESSION['project_id'], 'tome' => null, 'number' => $_REQUEST['number'], 'title' => $_REQUEST['name']));
			loadStoryInSession();
			echo $id;
			break;

		case 'saveChapter':
			header('Content-Type: application/json');
			require '_commontools.php';
			$data = array('date_modif' => date('Y-m-d H:i:s'));
			if (isset($_REQUEST['title'])) {
				$data['title'] = $_REQUEST['title'];
			}
			if (isset($_REQUEST['text'])) {
				$data['text'] = strip_tags(preg_replace(array("/(<br\s*\/?\>)/", "/(<div\s*\/?\>)/", "/(<p\s*\/?\>)/"), "\n", $_REQUEST['text']));
			}
			if (isset($_REQUEST['place'])) {
				$data['place'] = $_REQUEST['place'];
			}
			if (isset($_REQUEST['y'])) {
				$start = array($_REQUEST['y']);
				$end = array($_REQUEST['y']);
				if ($_REQUEST['m']>0) {
					array_push($start, $_REQUEST['m']);
					if ($_REQUEST['d']>0) {
						array_push($start, $_REQUEST['d'], $_REQUEST['h'], $_REQUEST['i']);
						array_push($end, $_REQUEST['m'], $_REQUEST['d']);
						if ($_REQUEST['h']>0) {
							array_push($end, $_REQUEST['h']);
							array_push($end, ($_REQUEST['i']>0)?$_REQUEST['i']:59);
						}
						else {
							array_push($end, 23,59);
						}
					}
					else {
						array_push($start, 1,0,0);
						array_push($end, $_REQUEST['m']+1,1,0,0);		// TODO: should be last second of the preceding day instead…
					}
				}
				else {
					array_push($start, 1,1,0,0);
					array_push($end, 12,31,23,59);
				}
				$data['time_start'] = $start[0].'-'.sprintf('%02d', $start[1]).'-'.sprintf('%02d', $start[2]).' '.sprintf('%02d', $start[3]).':'.sprintf('%02d', $start[4]).':00';
				$data['time_end'] = $end[0].'-'.sprintf('%02d', $end[1]).'-'.sprintf('%02d', $end[2]).' '.sprintf('%02d', $end[3]).':'.sprintf('%02d', $end[4]).':59';
			}
			db_u('chapters', array('id' => $_REQUEST['chapter_id'], 'project_id' => $_SESSION['project_id']), $data);
			updateSentencesRefsCache($_REQUEST['chapter_id']);
			echo json_encode(array('status' => 'ok'));
			break;

		case 'deleteChapter':
			require '_commontools.php';
			db_d('chapters', array('id' => $_REQUEST['id'], 'project_id' => $_SESSION['project_id']));
			loadStoryInSession();
			break;

		/* GRAPHS DATA ****************************************************************************************************************************************/
		case 'getIOGraph':
			header('Content-Type: text/plain; charset=utf-8');
			echo 'chapter'.TAB.'idem'.TAB.'S'.TAB.'E'.CR;
			$chapters_r = db_s('chapters', array('project_id' => $_SESSION['project_id'], '>tome' => 0), array('tome' => 'ASC', 'number' => 'ASC'));
			$prevChapterChars = array();
			while ($c = db_fetch($chapters_r)) {
				$chapterChars = array();
				$chapterChars_r = db_x('SELECT DISTINCT(e.id) AS id, e.color FROM sentences_refs_cache src LEFT OUTER JOIN entities e ON src.entity_id=e.id WHERE src.chapter_id='.db_escape($c['id']).' AND e.class="character";', false);
				while ($char = db_fetch($chapterChars_r)) {
					if (@$_REQUEST['includeNonColored'] || $char['color']>0) {
						$chapterChars[] = $char['id'];
					}
				}
				echo $c['tome'].'.'.$c['number'].TAB.count(array_intersect($chapterChars, $prevChapterChars)).TAB.count(array_diff($prevChapterChars, $chapterChars)).TAB.count(array_diff($chapterChars, $prevChapterChars)).CR;
				$prevChapterChars = $chapterChars;
			}
			break;

		case 'getCharsGraph':
			header('Content-Type: application/json');
			/*
			$characters = array();
			$entities_r = db_s('entities', array('project_id' => $_SESSION['project_id'], 'class' => 'character', '!status' => 'rejected'));
			while ($ent = db_fetch($entities_r)) {
				if (@$_REQUEST['includeNonColored'] || $ent['color']>0) {
					$characters[$ent['id']] = $ent;
				}
			}
			$results = array('nodes' => array(), 'links' => array(), 'chapters' => array());
			foreach ($characters as $c1_id => $c1) {
				$results['nodes'][] = array('id' => $c1['name'], 'color' => $colors_ref[$c1['color']]);
			}
*/
			$cooccurrences = db_x('SELECT COUNT(*) n, GROUP_CONCAT(e.name) AS entities, GROUP_CONCAT(e.color) AS e_colors, c.tome*1000+c.number as seq_number FROM ((sentences_refs_cache src LEFT OUTER JOIN entities e on src.entity_id=e.id) LEFT OUTER JOIN chapters c on src.chapter_id=c.id) WHERE src.project_id='.$_SESSION['project_id'].' AND c.tome>0 AND e.status!="rejected" AND e.class="character" GROUP BY CONCAT_WS(",",src.project_id,chapter_id,sentence) HAVING n>1', false);
			$simpleOccurences = array();
			while ($cc = db_fetch($cooccurrences)) {
				$seq_numbers[$cc['seq_number']] = true;
				if ($_REQUEST['seq_start']=='undefined' || ($cc['seq_number']>=$_REQUEST['seq_start'] && $cc['seq_number']<=$_REQUEST['seq_end'])) {
					$ent_ids = explode(',', $cc['entities']);
					$ent_colors = explode(',', $cc['e_colors']);
					$pairs = array();
					for ($i=0; $i<count($ent_ids); $i++) {
						if (@$_REQUEST['includeNonColored'] || $ent_colors[$i]>0) {
							$simpleOccurences[] = $ent_ids[$i];
						}
						for ($j=$i+1;$j<count($ent_ids);$j++) {
							if (@$_REQUEST['includeNonColored'] || ($ent_colors[$i]>0 && $ent_colors[$j]>0)) {
								$pairs[] = array($ent_ids[$i], $ent_ids[$j]);
							}
						}
					}
					foreach ($pairs as $pair) {
						$results['links'][] = array('source' => $pair[0], 'target' => $pair[1], 'value' => $cc['n']/10);
					}
				}
			}
			$results['nodes'] = array();
			$characters = array_values(array_unique($simpleOccurences));
			foreach ($characters as $char) {
				$charEntity = db_fetch(db_s('entities', array('project_id' => $_SESSION['project_id'], 'name' => $char)));
				$results['nodes'][] = array('id' => $char, 'color' => $colors_ref[$charEntity['color']]);
			}
			$results['chapters'] = @array_keys($seq_numbers);
			echo json_encode($results);
			break;

		case 'getTimelineGraph':
			header('Content-Type: application/json');
			$graph = array();
			$colors = array();
			$entities_r = db_s('entities', array('project_id' => $_SESSION['project_id'], '!status' => 'rejected'), array('id' => 'ASC'));
			$entities = array('characters' => array(), 'places' => array());
			while ($ent = db_fetch($entities_r)) {
				$entities[$ent['class']][$ent['id']] = $ent;
				if ($ent['class']=='character') {
					$colors[$ent['name']] = $colors_ref[$ent['color']];
				}
			}

			$chapterOccurences_r = db_x('SELECT c.id, c.tome, c.number, c.place, c.title, GROUP_CONCAT(entity_id) AS entities FROM (sentences_refs_cache src JOIN chapters c ON src.chapter_id=c.id) WHERE src.project_id='.$_SESSION['project_id'].' AND c.tome>0 GROUP BY chapter_id ORDER BY (c.tome*1000+c.number) ASC', false);
			@asort($entities['character']);
			while ($chap = db_fetch($chapterOccurences_r)) {
				$chapNumber = $chap['tome'].'.'.$chap['number'];
				$chaptersMap[$chapNumber] = array($chap['id'], $chap['title']);
				$chapterEntities = explode(',', $chap['entities']);
				$chars = array();
				foreach ($entities['character'] as $char_id => $entity) {
					if (in_array($char_id, $chapterEntities) && (@$_REQUEST['includeNonColored'] || $entity['color']>0)) {
						$chars[] = $entity['name'];
					}
				}
				$placeName = '';
				if ($chap['place']>0){
					$place = db_fetch(db_s('entities', array('id' => $chap['place'])));
					$placeName = $place['name'];
					$graph[$chapNumber] = array('place' => $placeName, 'characters' => $chars);
				}
				else {
					$graph[$chapNumber] = array('place' => '', 'characters' => array());
				}
			}
			echo json_encode(array('graph' => $graph, 'chaptersMap' => $chaptersMap, 'colors' => $colors));
			break;

		/* OPTIONS & SETTINGS *********************************************************************************************************************************/

		case 'getOptionsOverlay':
			$fileFormats = array(
								['id' => 'txt', 'label' => 'Texte'],
								['id' => 'xml', 'label' => 'XML']
								);
			$data = array(
							'project_id' => $_SESSION['project_id'],
							'project_title' => $_SESSION['project_title'],
							'project_author' => $_SESSION['project_author'],
							'fileFormats' => $fileFormats
						);
			$currentProject = db_fetch(db_s('projects', array('id' => $_SESSION['project_id'])));
			$data['project_version'] = $currentProject['version'];
			$data['summary'] = $currentProject['summary'];
			$projects = array();
			$projects_r = db_s('projects', array('saga_id' => $currentProject['saga_id'], 'user_id' => $_SESSION['logged']['id']), array('version' => 'DESC'));
			while ($project = db_fetch($projects_r)) {
				$lastModifChapter = db_fetch(db_s('chapters', array('project_id' => $_SESSION['project_id']), array('date_modif' => 'DESC')));
				$project['date_modif'] = $lastModifChapter['date_modif'];
				$projects[] = $project;
			}
			$data['projects'] = $projects;
			echo $blade->view()->make('overlays.options',$data)->render();

			break;

		case 'newVersion':
			require '_commontools.php';
			duplicateProject($_SESSION['project_id']);
			break;

		case 'saveOptions':
			$_SESSION['project_title'] = htmlspecialchars($_REQUEST['title']);
			$_SESSION['project_author'] = htmlspecialchars($_REQUEST['author']);
			$project_data = array('title' => $_SESSION['project_title'], 'author' => $_SESSION['project_author'], 'summary' => htmlspecialchars($_REQUEST['summary']));
			db_u('projects', array('id' => $_SESSION['project_id']), $project_data);
			break;

		/* PUBLISH ********************************************************************************************************************************************/

		case 'getPublishOverlay':
			$data = array(
							'project_title' => $_SESSION['project_title'],
							'project_author' => $_SESSION['project_author']
						);
			echo $blade->view()->make('overlays.publish',$data)->render();
			break;

		case 'publishProject':
			require '_commontools.php';
			$newProjectId = duplicateProject($_SESSION['project_id']);
			db_d('chapters', array('project_id' => $newProjectId, '<tome' => 1));
			$projectUpdate = array(
							'status' => 'published',
							'icon' => $_REQUEST['icon'],
							'title' => $_REQUEST['title'],
							'author' => $_REQUEST['author'],
							'summary' => $_REQUEST['summary'],
							);
			db_u('projects', array('id' => $newProjectId), $projectUpdate);
			$data['published_url'] = 'http'.($_SERVER['HTTPS']?'s':'').'://'.$_SERVER['SERVER_NAME'].'/reader.php?id='.$newProjectId;
			$data['published_id'] = $newProjectId;
			echo $blade->view()->make('overlays.publish-success',$data)->render();
			break;
		/******************************************************************************************************************************************************/

		case 'loadStock':
		case 'loadStory':
			$data = [];

			/*$chapters = db_s('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => 'asc', 'number' => 'asc'));
			while($c = db_fetch($chapters)){
				@$data['chapters'][$c['tome']][$c['number']] = $c;
			}*/

			$coloredEntities = db_s('entities', array('project_id' => $_SESSION['project_id'], '!color' => 0));
			while($cE = db_fetch($coloredEntities)){
				@$data['coloredEntities'][$cE['id']] = $cE['color'];
			}
			$entitiesByChapter = db_x('select chapter_id, entity_id, count(*) as c from sentences_refs_cache where project_id='.$_SESSION['project_id'].' and entity_id in (select id from entities where class="character" AND project_id='.$_SESSION['project_id'].' and color!=0) group by CONCAT(chapter_id,"__",entity_id) ORDER by chapter_id, entity_id');
			while($e = db_fetch($entitiesByChapter)){
				@$data['entitiesByChapter'][$e['chapter_id']][$e['entity_id']] = $e['c'];
				@$data['totalOccurences'][$e['chapter_id']] += $e['c'];
			}

			$chapters_r = db_s('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => 'ASC', 'number' => 'ASC'));
			while ($p = db_fetch($chapters_r)) {
				if ($p['place']>0) {
					$data['placeOfChapter'][$p['id']] = $p['place'];
				}
			}
			if ($f == 'loadStory') echo $blade->view()->make('partials.story',$data)->render();
			if ($f == 'loadStock') echo $blade->view()->make('partials.stock',$data)->render();
			break;


		case 'saveStory':
			require('_commontools.php');
			db_u('chapters', array('project_id' => $_SESSION['project_id']), array('tome' => null, 'number' => null));
			foreach($_REQUEST['story'] as $tome => $chapters){
				$z = 0;
				foreach(explode(',', $chapters) as $c){
					db_u('chapters', array('project_id' => $_SESSION['project_id'], 'id' => $c), array('tome' => $tome, 'number' => ++$z));
				}
			}
			loadStoryInSession();

			break;

		case 'deleteSaga':
			if ($sagaProject = db_fetch(db_s('projects', array('user_id' => $_SESSION['logged']['id'], 'id' => $_REQUEST['id'])))) {
				$projects_r = db_s('projects', array('saga_id' => $sagaProject['saga_id']));
				while ($project = db_fetch($projects_r)) {
					$_REQUEST['id'] = $project['id'];
					execute('deleteVersion');
				}
			}
			break;

		case 'deleteVersion':
			if ($project = db_fetch(db_s('projects', array('user_id' => $_SESSION['logged']['id'], 'id' => $_REQUEST['id'])))) {
				db_d('sentences_refs_cache', array('project_id' => $project['id']));
				db_d('entities', array('project_id' => $project['id']));
				db_d('chapters', array('project_id' => $project['id']));
				db_d('projects', array('id' => $project['id']));
			}
			break;

		/* READER / FLOW MODE *********************************************************************************************************************************/

		case 'getNextChapter':
			header('Content-Type: text/plain; charset=utf-8');
			require_once('_commontools.php');
			if (isset($_SESSION['sequence'])) {
				$next = @array_shift($_SESSION['sequence']);
				$c = db_fetch(db_s('chapters', array('id' => $next)));
				$c['more'] = count($_SESSION['sequence']);
				db_i('read_paths', array('session_id' => session_id(), 'cookie_id' => $_SESSION['cookie_id'], 'story' => $_SESSION['story'], 'chapter' => $next[0].'-'.$next[1], 'date' => date('Y-m-d H:i:s')));
				$c['text'] = '<h3 class="text">'.$c['title'].'</h3>'.'<p>'.readerFormat($c['text']).'</p>';
				@$_SESSION['history'][] = $c['tome'].'.'.$c['number'];
				$_SESSION['history'] = array_unique($_SESSION['history']);
				echo json_encode($c);
			}
			break;

		case 'getInfo':
			$nom = db_fetch(db_s('nomenclature', array('id' => $_REQUEST['k'])));
			echo '<b>'.$nom['title'].'</b>'.$nom['text'];
			break;

		default:break;
	}
}

execute($_REQUEST['f']);

