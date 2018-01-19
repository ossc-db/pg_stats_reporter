<?php
/*
 * common
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

/* load setting from configuration file */
function load_config(&$config, &$err_msg)
{
	$err_msg = null;

	/* Abolished the cache configuration file */

	/* read config from configuration file */
	$config = readConfigFile($error);
	if (count($error) > 0) {
		$err_msg .= "Error in pg_stats_reporter.ini<br/>\n";
		foreach ($error as $val) {
			$err_msg .= " - " . $val . "<br/>\n";
		}
		return false;
	}

	return true;
}

/* read configuration file and make cache file */
function readConfigFile(&$err_msg)
{
	global $global_setting_list;
	global $conf_key_list;
	global $report_default;
	global $deleteConfigCache;

	$cache_contents = array();
	$err_msg = array();
	$setting = $report_default;

	/* read pg_stats_reporter.ini */
	if (!is_file(CONFIG_FILE)) {
		$err_msg[] = "Configration file \"pg_stats_reporter.ini\" not found.";
		return array();
	}

	/* read pg_stats_reporter.ini */
	$config = parse_ini_file(CONFIG_FILE, true);

	/* check format and data for "global" section */
	if (!array_key_exists(GLOBAL_SECTION, $config)) {
		$err_msg[] = "Does not contain a global setting section(".CONFIG_FILE.")";
		return array();
	}

	foreach ($global_setting_list as $item) {
		if (!array_key_exists($item, $config[GLOBAL_SECTION])) {
			// provisional cope
			if ($item == "log_page_size")
				$config[GLOBAL_SECTION]["log_page_size"] = 1000;
			else
				$err_msg[] = "[".GLOBAL_SECTION."]".$item.": Required item not exists.";
		}
	}
	foreach (array_keys($config[GLOBAL_SECTION]) as $item) {
		if (!in_array($item, $global_setting_list)) {
			$err_msg[] = "[".GLOBAL_SECTION."]".$item.": Item is invalid.";
		}
	}

	if (array_key_exists("log_page_size", $config[GLOBAL_SECTION])) {
		$log_page_size = $config[GLOBAL_SECTION]['log_page_size'];
		if (!is_numeric($log_page_size)) {
			$err_msg[] = "[".GLOBAL_SECTION."]log_page_size = ".$log_page_size.": Set value is invalid.";
		} else if ($log_page_size < 1 || $log_page_size > 1000) {
			$err_msg[] = "[".GLOBAL_SECTION."]log_page_size = ".$log_page_size.": Set value is outside the valid range (1 .. 1000).";
		}
	}

	/* create cache contents for "global" section */
	$cache_contents[] = "[".GLOBAL_SECTION."]\n";
	foreach ($config[GLOBAL_SECTION] as $key => $value) {
		$cache_contents[] = $key . " = " . $value . "\n";
	}

	// exclude "global" section
	unset($config[GLOBAL_SECTION]);

	/* check format and get data */
	foreach ($config as $repo_name => $data_array) {
		if (!is_array($data_array)) {
			$err_msg[] = "No section found in .ini file.(repositoryDB name:".$repo_name.").";
			return array();
		}

		// check key name
		foreach($data_array as $key => $val) {
			if (!array_key_exists($key, $report_default)
					&& !array_key_exists($key, $conf_key_list)) {
				$err_msg[] = "[".$repo_name."]".$key.": Item name is invalid.";
				continue;
			}
		}

		// make database connection string
		$connect_str = "";
		if (array_key_exists('host', $data_array))
			$connect_str = "host=".$data_array['host'];
		if (array_key_exists('port', $data_array))
			$connect_str .= " port=".$data_array['port'];
		if (array_key_exists('dbname', $data_array))
			$connect_str .= " dbname=".$data_array['dbname'];
		if (array_key_exists('username', $data_array))
			$connect_str .= " user=".$data_array['username'];
		if (array_key_exists('password', $data_array)
				&& $data_array['password'] != "")
			$connect_str .= " password=".$data_array['password'];

		// connect repository database and get target database information
		// and get pg_statsinfo version
		$conn = pg_connect($connect_str);
		if (!$conn) {
			$err_msg[] = "Connection failure.(repository database = ".$repo_name.")";
			continue;
		} else {
			pg_set_client_encoding($conn, "UTF-8");

			// statsrepo schema is not found
			$result = pg_query($conn, "SELECT nspname FROM pg_catalog.pg_namespace WHERE nspname = 'statsrepo'");
			if (!$result || !pg_num_rows($result)) {
				$err_msg[] = "statsrepo schema not found.(repository database = ".$repo_name.")";
				pg_free_result($result);
				pg_close($conn);
				continue;
			}

			$cache_contents[] = "[".$repo_name."]\n";
			$cache_contents[] = "connect_str = \"".$connect_str."\"\n";

			$result = pg_query($conn, "SELECT p.proname FROM pg_catalog.pg_proc p LEFT JOIN pg_catalog.pg_namespace n ON p.pronamespace = n.oid WHERE n.nspname = 'statsrepo' AND p.proname = 'get_version'");
			if (!$result) {
				$err_msg[] = "Query execution error. ".pg_last_error();
			}
			if (pg_num_rows($result) == 0) {
				$cache_contents[] = "repo_version = ".V23."\n";
			} else {
				pg_free_result($result);
				$result = pg_query($conn, "SELECT statsrepo.get_version()");
				if (!$result) {
					$err_msg[] = "Query execution error. ".pg_last_error();
				}
				$row_array = pg_fetch_array($result, NULL, PGSQL_NUM);
				$cache_contents[] = "repo_version = ".$row_array[0]."\n";
			}
			pg_free_result($result);

			$result = pg_query($conn, "SELECT instid, hostname, port, pg_version FROM statsrepo.instance");
			if (!$result) {
				$err_msg[] = "Query execution error. ".pg_last_error();
			} else if (pg_num_rows($result) == 0){
				$err_msg[] = "No target server registered (repository database = ".$repo_name.")";
			} else {
				for ($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
					$row_array = pg_fetch_array($result, NULL, PGSQL_NUM);
					$cache_contents[] = "monitor[".$row_array[0]."] = \"".$row_array[1].":".$row_array[2]."\"\n";
					$cache_contents[] = "pg_version[".$row_array[0]."] = ".convertPGVersionNum($row_array[3])."\n";
				}
				pg_free_result($result);
			}
			pg_close($conn);
		}

		// set language
		if (array_key_exists('language', $data_array))
			$cache_contents[] = "language = ".$data_array['language']."\n";
		else
			$cache_contents[] = "language = auto\n";

		// set report item
		$setting = $report_default;
		foreach ($setting as $key => $val) {
			if (array_key_exists($key, $data_array)) {
				switch($data_array[$key]) {
					case 1:
					case "":
						$setting[$key] = $data_array[$key];
						break;
					default:
						$err_msg[] = "[".$repo_name."]".$key." = ".$data_array[$key].": Set value is invalid.";
				}
			}

			$cache_contents[] = $key." = ".$setting[$key]."\n";
		}
	}

	if (count($cache_contents) == 0) {
		$err_msg[] = "No valid information.";
		return array();
	}

	// write cache file
	// For multiple executions, use an unique temporary file
	$tmpCacheFilename = tempnam(CONFIG_CACHE_DIR, CONFIG_FILENAME . ".");
	if (file_put_contents($tmpCacheFilename, $cache_contents) == false) {
		$err_msg[] = "Failed to write cache file(".$tmpCacheFilename.")";
		return array();
	}

	// read cache file
	$config = parse_ini_file($tmpCacheFilename, true);

	rename($tmpCacheFilename, CONFIG_CACHE_FILE);

	return $config;
}

/* load message from message file */
function load_message($language, &$help_message, &$error_message)
{
	$msg_file_list = array();

	/* メッセージファイルの一覧作成 */
	createMessageFileList(MESSAGE_PATH, $msg_file_list);

	/* 言語の選定 */
	if ($language == 'auto') {
		if (extension_loaded('intl')) {
			$lang = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		} else {
			$lang = "en";
		}
	} else {
		$lang = $language;
	}

	/* メッセージファイルの読み込み */
	readMessageFile($lang, $msg_file_list, $help_message, $error_message);
}

/* create message file list */
function createMessageFileList($file_dir, &$msg_file_list)
{
	$msg_len = strlen(MESSAGE_PREFIX);

	if (!($dir = opendir($file_dir)))
		return false;

	/* TODO: Check out message_message_ja.xml */
	while($fn = readdir($dir)) {
		$path_parts = pathinfo($file_dir.$fn);
		if (strncmp(MESSAGE_PREFIX, $path_parts["filename"], $msg_len) == 0
			&& strcmp(".".$path_parts["extension"], MESSAGE_SUFFIX) == 0) {
			$lang = str_replace(MESSAGE_PREFIX, "", $path_parts["filename"]);
			$msg_file_list[$lang] = $file_dir.$fn;
		}
	}
	closedir($dir);
	return true;
}

/* read message file */
function readMessageFile($language, $msg_file_list,
							&$help_message, &$error_message)
{
	global $help_list;

	$locale_list = array_keys($msg_file_list);

	/*
	 * if php-intl extension is available,
	 * searches the locale list for the best match to the language.
	 */
	if (extension_loaded('intl')) {
		$locale = locale_lookup($locale_list, $language, false, "en");
		$msgfile = $msg_file_list[$locale];
	} else {
		if (array_key_exists($language, $msg_file_list))
			$msgfile = $msg_file_list[$language];
		else
			$msgfile = $msg_file_list["en"];
	}

	if (!file_exists($msgfile)) {
		$msg = "message file(".$msgfile.") is not found.";
		if (!empty($_SERVER['DOCUMENT_ROOT']))
			die($msg);
		else
			elog(ERROR, $msg);
	}

	$xml = simplexml_load_file($msgfile);
	if ($xml == false) {
		$msg = "Access denied or invalid XML format (".$msgfile.")";
		if (!empty($_SERVER['DOCUMENT_ROOT']))
			die($msg);
		else
			elog(ERROR, $msg);
	}

	// make help message
	$err_val = $xml->xpath("/document/help/div[@id=\"error\"]");
	if (count($err_val) == 0) {
		$err_val[0] = "No help item found";
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

function get_url_param($name)
{
	if (array_key_exists($name, $_GET)) {
		return $_GET[$name];
	}
	return null;
}

/* set default timezone */
function set_default_timezone()
{
	$timezone = ini_get('date.timezone');
	if (!$timezone) {
		$timezone_abbr = exec('date +%Z');
		$timezone = timezone_name_from_abbr($timezone_abbr);
		if (!$timezone)
			$timezone = "UTC";
	}
	date_default_timezone_set($timezone);
}

/* delete configuration file cache and report cache */
function deleteCacheFile(&$smarty)
{
	@unlink(CONFIG_CACHE_FILE);
	$smarty->clearAllCache();
}

/* convert the version string of PostgreSQL to version number */
function convertPGVersionNum($version_str)
{
	$vmaj = 0;
	$vmin = 0;
	$vrev = 0;

	$ver_array = explode(".", $version_str);
	$ver_array_size = count($ver_array);

	if ($ver_array_size == 3) {
		$vmaj = $ver_array[0];
		$vmin = $ver_array[1];
		$vrev = $ver_array[2];
	} else if ($ver_array_size == 2) {
		$vmaj = $ver_array[0];
		if ($vmaj >= 10) {
			$vrev = $ver_array[1];
		} else {
			$vmin = preg_replace('/^([0-9]+).*/', '\1', $ver_array[1]);
		}
	} else {
		$vmaj = preg_replace('/^([0-9]+).*/', '\1', $ver_array[0]);
	}
	return ($vmaj * 100 + $vmin) * 100 + $vrev;
}

?>
