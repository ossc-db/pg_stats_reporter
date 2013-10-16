<?php
/*
 * pg_stats_reporter
 *
 * Copyright (c) 2012,2013, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

define("GLOBAL_SECTION", "global_setting");
define("INSTALL_DIR", "install_directory");
$global_setting_list = array(
	INSTALL_DIR,
);

/* set default timezone */
$timezone = ini_get('date.timezone');
if (!$timezone) {
	$timezone_abbr = exec('date +%Z');
	$timezone = timezone_name_from_abbr($timezone_abbr);
	if (!$timezone)
		$timezone = "UTC";
}       
date_default_timezone_set($timezone);

// this is flag to judge whether delete cache file of pg_stats_reporter.ini
$deleteConfigCache = false;

// include sub module
require_once "../../pg_stats_reporter_lib/module/define.php";
require_once "../../pg_stats_reporter_lib/module/common.php";
require_once "../../pg_stats_reporter_lib/module/make_report.php";

/* Initial setting of Smarty */
require_once SMARTY_PATH."Smarty.class.php";
$smarty = new Smarty();

/* キャッシュの設定と各ディレクトリの設定 */
$smarty->caching        = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->compile_check  = true;
$smarty->cache_lifetime = 300;
$smarty->cache_dir      = CACHE_DIR;
$smarty->template_dir   = TEMPLATE_DIR;
$smarty->compile_dir    = COMPILE_DIR;

/* テンプレートに渡す各ライブラリのパスを設定 */
$smarty->assign("jquery_path", JQUERY_PATH);
$smarty->assign("jquery_ui_path", JQUERYUI_PATH);
$smarty->assign("timepicker_path", TIMEPICKER_PATH);
$smarty->assign("tablesorter_path", TABLESORTER_PATH);
$smarty->assign("superfish_path", SUPERFISH_PATH);
$smarty->assign("jqplot_path", JQPLOT_PATH);
$smarty->assign("dygraphs_path", DYGRAPHS_PATH);

/* メッセージファイルの一覧作成 */
createMessageFileList(MESSAGE_PATH, $locale_list, $msg_file_list);

/* URLパラメータのreloadを確認し、キャッシュファイル削除 */
if (array_key_exists("reload", $_GET)) {
	deleteCacheFile();
}

/* 設定ファイルの読込み */
// エラーの時はエラーのみのページを表示
if (is_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME)) {
	$infoData = parse_ini_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME, true);
} else {
	if (readGlobalSetting($global_setting, $infoData, $errormsg) == false) {
		print "An error has occurred in pg_stats_reporter.ini<br/>\n";
		foreach($errormsg as $val) {
			print " - ".$val."<br/>\n";
		}
		exit;
	}
	if (initInformationFile($infoData, $errormsg) == false) {
		print "An error has occurred in pg_stats_reporter.ini<br/>\n";
		foreach($errormsg as $val) {
			print " - ".$val."<br/>\n";
		}
		exit;
	}
	if (count($errormsg) != 0) {
		$html_string = makeErrorReport($infoData, $errormsg);
		$smarty->assign("header_menu", $html_string['header_menu']);
		$smarty->assign("left_menu", $html_string['left_menu']);
		$smarty->assign("contents", $html_string['contents']);
		$smarty->display(TEMPLATE_FILE, 0);

		if ($deleteConfigCache == true)
			deleteCacheFile();

		exit;
	}
}

/* レポート作成のための情報を設定 */
$target_data = array();
$report_cache_id = "";
if (count($_GET) >= 4) {
	$target_data['repodb'] = $_GET['repodb'];
	$target_data['instid'] = $_GET['instid'];
	$target_data['begin_date'] = trim($_GET['begin'], "\"'");
	$target_data['end_date'] = trim($_GET['end'], "\"'");
} else {
	foreach($infoData as $repodb => $val_array) {
		$target_data['repodb'] = $repodb;
		if (!array_key_exists('monitor', $val_array))
			continue;
		foreach($val_array['monitor'] as $instid => $val) {
			$target_data['instid'] = $instid;
			break;
		}
		break;
	}
	$target_data['begin_date'] = date('Y-m-d', time() - 24*60*60)." 00:00:00";
	$target_data['end_date'] = date('Y-m-d H:i:s');
}

// キャッシュID(report_cache_id)の作成
foreach($target_data as $val) {
	$report_cache_id .= $val;
}

/* キャッシュ確認 */
if ($smarty->isCached(TEMPLATE_FILE, $report_cache_id)) {
	$smarty->display(TEMPLATE_FILE, $report_cache_id);
	exit;
}

/* キャッシュファイルの削除 */
eraseReportCache($smarty->cache_lifetime);

/* 設定ファイルの該当箇所取得 */
$target_info = $infoData[$target_data['repodb']];
$smarty->assign("target_info", $target_info);

/* メッセージファイル読込み */
if ($target_info['language'] == 'auto') {
	if (extension_loaded('intl')) {
		$language = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
	} else {
		$language = "en";
	}
} else {
	$language = $target_info['language'];
}
readMessageFile($language, $locale_list, $msg_file_list,
				$help_message, $error_message);

/* レポートページ作成 */
// DB接続
$conn = pg_connect($target_info['connect_str']);
if (!$conn) {
	die("connect error. repository database : ".$target_data['repodb']);
}
pg_set_client_encoding($conn, "UTF-8");

// トランザクション開始とロックの取得
$result = pg_query("BEGIN");
pg_free_result($result);
$result = pg_query("LOCK TABLE statsrepo.instance IN SHARE MODE");
if (!$result) {
	die(pg_last_error());
}
pg_free_result($result);

// ヘッダからコンテンツまで作成
$html_string = makeReport($conn, $infoData, $target_data,
				$help_message, $error_message);
$smarty->assign("header_menu", $html_string['header_menu']);
$smarty->assign("left_menu", $html_string['left_menu']);
$smarty->assign("contents", $html_string['contents']);

// 切断
pg_query("COMMIT");
pg_close($conn);

// 表示
$smarty->display(TEMPLATE_FILE, $report_cache_id);


/* delete information and report cache file */
function deleteCacheFile()
{
	if (is_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME)) {
		unlink(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME);
	}
	eraseReportCache(0);
}

/* read global_setting from information file */
function readGlobalSetting(&$global_setting, &$infoData, &$err_msg)
{
	global $global_setting_list;

	$ini_data = array();
	$err_msg = array();

	/* read pg_stats_reporter.ini */
	if (!is_file(CONFIG_PATH.CONFIG_FILENAME)) {
		$err_msg[] = "pg_stats_reporter.ini is not found.";
		return false;
	}

	/* read pg_stats_reporter.ini */
	$ini_data = parse_ini_file(CONFIG_PATH.CONFIG_FILENAME, true);

	// pick up "global" section
	if (!array_key_exists(GLOBAL_SECTION, $ini_data)) {
		$err_msg[] = "Does not contain a global setting section(".CONFIG_PATH.CONFIG_FILENAME.")";
		return false;
	}

	// validate check
	foreach ($global_setting_list as $item) {
		if (!array_key_exists($item, $ini_data[GLOBAL_SECTION]))
			$err_msg[] = "[".GLOBAL_SECTION."]".$item.": Required item not exists.";
	}
	foreach (array_keys($ini_data[GLOBAL_SECTION]) as $item) {
		if (!in_array($item, $global_setting_list)) {
			$err_msg[] = "[".GLOBAL_SECTION."]".$item.": Item is invalid.";
		}
	}

	$global_setting = $ini_data[GLOBAL_SECTION];
	$infoData = $ini_data;
	return $infoData;
}

/* read information file and make cache file */
function initInformationFile(&$info_data, &$err_msg)
{
	global $conf_key_list;
	global $report_default;
	global $deleteConfigCache;

	$cache_contents = array();
	$setting = $report_default;

	// exclude "global" section
	assert(array_key_exists(GLOBAL_SECTION, $info_data));
	unset($info_data[GLOBAL_SECTION]);

	/* check format and get data */
	$repositoryNum = count($info_data);
	$invalidInfoNum = 0;
	foreach($info_data as $repo_name => $data_array) {
		if (!is_array($data_array)) {
			$err_msg[] = "Does not contain a section(repositoryDB name:".$repo_name.").";
			return false;
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
			$err_msg[] = "connect error.(repository database = ".$repo_name.")";
			$cache_contents[] = "[".$repo_name."]\n";
			$invalidInfoNum++;
			continue;
		} else {
			pg_set_client_encoding($conn, "UTF-8");
			$cache_contents[] = "[".$repo_name."]\n";
			$cache_contents[] = "connect_str = \"".$connect_str."\"\n";

			$result = pg_query($conn, "SELECT p.proname FROM pg_catalog.pg_proc p LEFT JOIN pg_catalog.pg_namespace n ON p.pronamespace = n.oid WHERE n.nspname = 'statsrepo' AND p.proname = 'get_version'");
			if (!$result) {
				$err_msg[] = "execute query error. ".pg_last_error();
			}
			if (pg_num_rows($result) == 0) {
				$cache_contents[] = "repo_version = ".V23."\n";
			} else {
				pg_free_result($result);
				$result = pg_query($conn, "SELECT statsrepo.get_version()");
				if (!$result) {
					$err_msg[] = "execute query error. ".pg_last_error();
				}
				$row_array = pg_fetch_array($result, NULL, PGSQL_NUM);
				$cache_contents[] = "repo_version = ".$row_array[0]."\n";
			}
			pg_free_result($result);

			$result = pg_query($conn, "SELECT instid, hostname, port, pg_version FROM statsrepo.instance");
			if (!$result) {
				$err_msg[] = "execute query error. ".pg_last_error();
			} else if (pg_num_rows($result) == 0){
				$err_msg[] = "monitored database is not registered at all.(repository database = ".$repo_name.")";
				$invalidInfoNum++;
			} else {
				for ($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
					$row_array = pg_fetch_array($result, NULL, PGSQL_NUM);
					$cache_contents[] = "monitor[".$row_array[0]."] = \"".$row_array[1].":".$row_array[2]."\"\n";
					$ver_array = explode(".", $row_array[3]);
					$ver = $ver_array[0]*10000 + $ver_array[1]*100 + $ver_array[2];
					$cache_contents[] = "pg_version[".$row_array[0]."] = ".$ver."\n";
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

	// determinded whether to delete the cache file
	if ($invalidInfoNum == $repositoryNum)
		$deleteConfigCache = true;

	// write cache file
	if (count($cache_contents) == 0) {
		$err_msg[] = "No valid information.";
		return false;
	}

	$tmpCacheFilename = tempnam(CACHE_CONFIG_PATH, CACHE_CONFIG_FILENAME.".");
	if (file_put_contents($tmpCacheFilename, $cache_contents) == false) {
		$err_msg[] = "do not write cache file(".$tmpCacheFilename.")";
		return false;
	}

	// read cache file
	$info_data = parse_ini_file($tmpCacheFilename, true);
	rename($tmpCacheFilename, CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME);
	return $info_data;

}

/* erase report cache file */
function eraseReportCache($lifetime)
{
	$now = time();

	$dir = opendir(CACHE_DIR);

	while( ($entry = readdir($dir)) !== false) {
		if ($entry == CACHE_CONFIG_FILENAME	|| !is_file(CACHE_DIR."/".$entry))
			continue;

		$stats = stat(CACHE_DIR."/".$entry);

		if ($stats[9] < $now - $lifetime)
			unlink(CACHE_DIR."/".$entry);
	}

	closedir($dir);
}

?> 
