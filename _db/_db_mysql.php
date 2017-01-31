<?php
/* _db
 * Database wrapper for PHP
 * Version 2.2 - MySQL
 * Copyright (c) 2006-2013 Cyril Bornet, all rights reserved
 * ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
 * Historique des versions :
 *  05/03/2006 | 1.0 | Version initiale, comprenant méthodes openTable(), db_s(), db_g(), db_e(), db_i(), db_u() et db_d().
 *  25/05/2007 | 1.2 | Renommage des fonctions ci-dessus vers db_o(), db_s(), db_g(), db_e(), db_i(), db_u(), db_d(), pour compatibilité avec version publique.
 *  03/08/2007 | 1.3 | Correction affectant la fonction db_o() : suppression du paramètre $table (inutile) / ajout de la fonction ouverte db_x().
 *  03/08/2007 | 1.4 | Système de journalisation pour toutes les méthodes altérant des données.
 *  12/11/2010 | 1.5 | Potential security flaws fixes.
 *  04/01/2011 | 1.6 | Unique connection in global variable
 *  09/02/2012 | 2.0 | Updated sort arguments calls, added transactions support
 *  31/01/2013 | 2.1 | UPDATE/DELETE by References
 *  07/06/2013 | 2.2 | Operators in WHERE clauses (VB)
 *  28/10/2014 | 2.3 | Support for multiple LIKE on same field
 *  05/01/2015 | 2.4 | Support for reserved keywords as column names
 *  11/02/2016 | 2.5 | Full mysqli support
 */


// === Ouvre une CONNEXION globale au serveur de DB ============================================================================================================
mb_internal_encoding(DB_ENCODING);
$GLOBALS['db_link'] = false;
function db_o() {
	// MySQL 4.1 et supérieur
	if ($GLOBALS['db_link']===false) {
		$GLOBALS['db_link'] = mysqli_connect(DB_HOST, DB_USER, DB_PASS);					//  Connexion à MySQL
		if (!$GLOBALS['db_link']) {
			if ((@include 'inc_down.html')===false) {
				print('<h1>Down for maintenance</h1><p>This website is currently down for maintenance. We are currently working on it, so please come back in a few hours...</p><hr/>'.$_SERVER['HTTP_HOST'].'<i>');
			}
			die();																											//  En cas d'erreur de connexion, on arrête tout !
		}
		mysqli_select_db($GLOBALS['db_link'], DB_NAME);															// Sélectionne la base de données
		mysqli_set_charset($GLOBALS['db_link'], DB_ENCODING);
	}
	return $GLOBALS['db_link'];
}

// === Effectue une RECHERCHE dans la base de données du site ==================================================================================================
function db_s($table, $refs=array(), $sortParams=array()) {
	$link = db_o();																											// Ouvre une connexion
	$sql = 'SELECT * FROM '.$table.db_w($refs);
	// Sort parameters ______________________________________________________________________
	if (count($sortParams)>0) {
		$sort = array();
		foreach ($sortParams as $key => $dir) {
			$sort[] = $key.' '.$dir;
		}
		$sql.=' ORDER BY '.implode(', ', $sort);
	}
	//print_r("dbs");
	//print_r($sql);
    $result = mysqli_query($link, $sql);
 	if (mysqli_errno($link) != 0) {
 		dieWithError(mysqli_errno($link), mysqli_error($link), $sql);
 	}
 	//print_r($result);
    return $result;
}


function db_quote($val) {
	return (string)(is_string($val) ? '"'.db_escape($val).'"' : (is_null($val) ? 'null' : db_escape($val)) );
}

function db_w($refs) {
	// Filter parameters ____________________________________________________________________
	$link = db_o();																	// Ouvre une connexion
	if (count($refs)>0) {
		$where = array();
		$fieldOps = array('!'=>'!=','<'=>'<','>'=>'>','≤'=>'<=','≥'=>'>=');
		foreach ($refs as $key => $value) {
			if (mb_substr($key, 0, 1)=='%' || mb_substr($key, -1)=='%') {
				$proper_key = str_replace('%','',$key);
				$like = array();
				if (is_array($value)) {
					foreach ($value as $term) {
						$like[] = 'LOWER(`'.$proper_key.'`) LIKE '.db_quote(str_replace($proper_key,$term,$key));
					}
				}
				else {
					$like[] = 'LOWER(`'.$proper_key.'`) LIKE '.db_quote(str_replace($proper_key,$value,$key));
				}
				$where[] = implode(' OR ', $like);
			} elseif(in_array(mb_substr($key, 0, 1), array_keys($fieldOps))) {
				$proper_key = mb_substr($key, 1);
				$where[] = '`'.$proper_key.'`'.$fieldOps[mb_substr($key, 0, 1)].db_quote($value);
			} else {
				$where[] = '`'.$key.'`='.db_quote($value);
			}
		}
		return ' WHERE ('.implode(' AND ', $where).')';
	}
	else return '';
}

// === INSERE les données $datas dans la table $table de la base de donnés de ce site ==========================================================================
function db_i($table, $datas, $do_log=true) {
	$link = db_o();																	// Ouvre une connexion
	$keys = array();
	$values = array();
	foreach ($datas as $key => $value) {											// \
		$keys[] = $key;																//  |
		$values[] = '"'.mysqli_real_escape_string($link, $value).'"';				//  Parcourt les données en paramètres pour les réarranger conformément à la requête SQL
	}																				// /
	$sql = 'INSERT INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).');';				// Requête SQL

    if ($do_log) { db_log($sql); }
    $result = mysqli_query($link, $sql);												//

 	if (mysqli_errno($link) == 0) { return mysqli_insert_id($link); } else { dieWithError(mysqli_errno($link), mysqli_error($link), $sql); return false; }	// Témoin d'enregistrement (true = OK)
}

// === REMPLACE la ligne avec sélecteur spécifié ===============================================================================================================
function db_r($table, $datas, $do_log=true) {
	$link = db_o();																	// Ouvre une connexion
	$keys = array();
	$values = array();
	foreach ($datas as $key => $value) {											// \
		$keys[] = $key;																//  |
		$values[] = '"'.mysqli_real_escape_string($link, $value).'"';				//  Parcourt les données en paramètres pour les réarranger conformément à la requête SQL
	}																				// /
	$sql = 'REPLACE INTO '.$table.' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).');';				// Requête SQL

    if ($do_log) { db_log($sql); }
    $result = mysqli_query($link, $sql);												//

 	if (mysqli_query($link) == 0) { return mysqli_query($link); } else { dieWithError(mysqli_query($link), mysqli_query($link), $sql); return false; }	// Témoin d'enregistrement (true = OK)
}


// === MODIFIE la ligne avec id=$id dans la table $table de la base de donnés de ce site =======================================================================
function db_u($table, $refs, $datas, $do_log=true) {
	$link = db_o();																		// Ouvre une connexion

	$toChange = array();															// \
	foreach ($datas as $key => $value) {											//  |
		$str_val = ($value===null)?'null':'"'.mysqli_real_escape_string($link, $value).'"';						//  |
		$toChange[] = $key.'='.$str_val;											//  |
    }																				// /
	$sql = 'UPDATE '.$table.' SET '.implode(',',$toChange).db_w($refs);				// Requête SQL

    if ($do_log) { db_log($sql); }
    $result = mysqli_query($link, $sql);												//

 	if (mysqli_errno($link) == 0) { return mysqli_affected_rows($link); } else { dieWithError(mysqli_errno($link), mysqli_error($link), $sql); }	// Témoin d'enregistrement (true = OK)
}

// === SUPPRIME la ligne avec id=$id dans la table $table de la base de donnés de ce site ======================================================================
function db_d($table, $refs, $do_log=true) {
	$link = db_o();																	// Ouvre une connexion

	$sql = 'DELETE FROM '.$table.db_w($refs);

	if ($do_log) { db_log($sql); }
	$result = mysqli_query($link, $sql);

	if (mysqli_errno($link) == 0) { return mysqli_affected_rows($link); } else { dieWithError(mysqli_errno($link), mysqli_error($link), $sql); }	// Témoin d'enregistrement (true = OK)
}

// === EXECUTE la requête passée en paramètre ==================================================================================================================
function db_x($request, $do_log=true, $qParams=array()) {
	$link = db_o();
	$result = mysqli_query($link, $request);
	if ($do_log && substr($request, 0, 7) != 'SELECT ' && mysqli_errno($link)>0) { db_log($request); }

	if (mysqli_errno($link)) {
		dieWithError(mysqli_errno($link), mysqli_error($link), $request);
	}
	else {
		return $result;
	}
}

// === TRANSACTIONS ============================================================================================================================================

function db_begin($title='default') {
	$link = db_o();
	$sql = 'BEGIN TRANSACTION '.$title.'';
	$result = mysqli_query($link, $sql);
}

function db_commit($title='default') {
	$link = db_o();
	$sql = 'COMMIT TRANSACTION '.$title.'';
	$result = mysqli_query($link, $sql);
}

// === TOOLS & Helpers =========================================================================================================================================

function db_escape($string) {
	$link = db_o();
	return mysqli_real_escape_string($link, $string);
}

function db_fetch($src) {
	return mysqli_fetch_assoc($src);
}

function db_seek($src, $offset = 0) {
	return mysqli_data_seek($src, $offset);
}

function db_count($src) {
	return mysqli_num_rows($src);
}

function dieWithError($code, $msg, $stmt) {
	echo('<br/><br/><b>MySQL error '.$code.': '.$msg.'</b><br/>When executing : <pre style="background:#CCC;padding:5px;">'.$stmt.'</pre>');
}

// === JOURNALISE la requête en paramètre, selon variables courantes ===========================================================================================

function db_log($request) {
	$link = db_o();
	$result = mysqli_query($link, 'INSERT INTO db_log (user_id, date, query) VALUES ("'.@$_SESSION['user_id'].'", CURRENT_TIMESTAMP, "'.db_escape($request, $link).'");');
}



?>
