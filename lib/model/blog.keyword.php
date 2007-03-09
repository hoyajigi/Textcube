<?php
/// Copyright (c) 2004-2007, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

function getKeywordByName($owner, $name) {
	global $database;
	$name = mysql_tt_escape_string($name);
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	if ($result = DBQuery::query("SELECT * FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category = -1 AND title = '$name'"))
		return mysql_fetch_array($result);
	return false;	
}

function getKeywordCount($owner) {
	global $database;
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	return DBQuery::queryCell("SELECT COUNT(*) FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category = -1");
}

function getKeywordNames($owner) {
	global $database;
	$names = array();
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	$result = DBQuery::query("SELECT title FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category = -1 ORDER BY char_length(title) DESC");
	while (list($name) = mysql_fetch_array($result))
		array_push($names, $name);
	return $names;
}

function getKeywords($owner) {
	global $database;
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	return DBQuery::queryAll("SELECT * FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category = -1 ORDER BY published DESC");
}

function getKeywordsWithPaging($owner, $search, $page, $count) {
	global $database, $folderURL, $suri;
	$aux = '';
	if (($search !== true) && $search) {
		$search = mysql_tt_escape_string($search);
		$aux = "AND (title LIKE '%$search%' OR content LIKE '%$search%')";
	}

	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 0';
	$sql = "SELECT * FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category = -1 $aux ORDER BY published DESC";
	return fetchWithPaging($sql, $page, $count, "$folderURL/{$suri['value']}");
}

function getKeylog($owner, $keyword) {	
	global $database;
	$keyword = mysql_tt_escape_string($keyword);
	$visibility = doesHaveOwnership() ? '' : 'AND visibility > 1';
	return DBQuery::queryAll("SELECT id, title, content, published, comments FROM {$database['prefix']}Entries WHERE owner = $owner AND draft = 0 $visibility AND category >= 0 AND (title LIKE '%$keyword%' OR content LIKE '%$keyword%') ORDER BY published DESC");
}
?>
