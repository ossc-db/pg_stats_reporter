<?php
/*
 * common
 *
 * Copyright (c) 2013, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// create message file list
function createMessageFileList($file_dir, &$locale_list, &$msg_file_list)
{
	$msg_len = strlen(MESSAGE_PREFIX);

	if (!($dir = opendir($file_dir)))
		return false;

	while($fn = readdir($dir)) {
		$path_parts = pathinfo($file_dir."/".$fn);
		if (strncmp(MESSAGE_PREFIX, $path_parts["filename"], $msg_len) == 0
			&& strcmp(".".$path_parts["extension"], MESSAGE_SUFFIX) == 0) {
			$lang = str_replace(MESSAGE_PREFIX, "", $path_parts["filename"]);
			$locale_list[] = $lang;
			$msg_file_list[$lang] = $file_dir."/".$fn;
		}
	}
	closedir($dir);
	return true;
}

/* read message file */
function readMessageFile($language, $locale_list, $msg_file_list,
							&$help_message, &$error_message)
{
	global $help_list;

	$help_message = array();
	$error_message = array();
	$locale = locale_lookup($locale_list, $language, false, "en");
	$msgfile = $msg_file_list[$locale];
	if (!file_exists($msgfile)) {
		$msg = "message file(".msg_file.") is not found.";
		if (!empty($_SERVER['DOCUMENT_ROOT']))
			die($msg);
		else
			die2($msg, 1, ERROR);
	}

	$xml = simplexml_load_file($msgfile);
	if ($xml == false) {
		$msg = "Access denied or invalid XML format.(".$msgfile.")";
		if (!empty($_SERVER['DOCUMENT_ROOT']))
			die($msg);
		else
			die2($msg, 1, ERROR);
	}

	// make help message
	$err_val = $xml->xpath("/document/help/div[@id=\"error\"]");
	if (count($err_val) == 0) {
		$err_val[0] = "help item is not found.";
	}
	
	foreach($help_list as $id_key => $id_val) {
		$val = $xml->xpath("/document/help/div[@id=\"".$id_val."\"]");
		if (count($val) == 0) {
			$help_message[$id_key] = "<div id=\"".$id_val."\">".$err_val[0]."</div>";
		} else {
			$help_message[$id_key] = $val[0]->asXML();
		}
	}
	// get error message
	foreach($xml->error->p as $error) {
		$key = $error['id'];
		$error_message["$key"] = $error;
	}
	return true;
}

function getSnapshotID($conn, $targetData, &$snapids, &$snapdates)
{
	$queryString = "SELECT min(snapid), max(snapid), to_char(min(time), 'YYYYMMDD-HH24MI'), to_char(max(time), 'YYYYMMDD-HH24MI') FROM statsrepo.snapshot WHERE instid = $1 AND ";
	$setdate = false;
	if (isset($targetData["begin_date"]) || isset($targetData["end_date"])) {
		$setdate = true;
		if (!isset($targetData["begin_date"]))
			$targetData["begin_date"] = "0001-01-01 00:00:00";
		if (!isset($targetData["end_date"]))
			$targetData["end_date"] = "9999-12-31 23:59:59";
	} else {
		if (!isset($targetData["begin_id"]))
			$targetData["begin_id"] = 0;
		if (!isset($targetData["end_id"]))
			$targetData["end_id"] = PHP_INT_MAX;
	}
	/** prepare query params **/
	$queryParams = array ($targetData["instid"], $targetData["begin_date"], $targetData["end_date"]);

	if ($setdate) {
		$queryString .= "time BETWEEN $2 AND $3";
		$queryParams = array ($targetData["instid"], $targetData["begin_date"], $targetData["end_date"]);
		$result = pg_query_params($conn, $queryString, $queryParams);
		if (!$result)
			return false;
	} else {
		$queryString .= "snapid BETWEEN $2 AND $3";
		$queryParams = array ($targetData["instid"], $targetData["begin_id"], $targetData["end_id"]);
		$result = pg_query_params($conn, $queryString, $queryParams);
		if (!$result)
			return false;
	}

	$resultData = pg_fetch_array($result, NULL, PGSQL_NUM);
	$snapids = array_slice($resultData, 0, 2);
	$snapdates = array_slice($resultData, 2);

	pg_free_result($result);

	return true;
}
?>
