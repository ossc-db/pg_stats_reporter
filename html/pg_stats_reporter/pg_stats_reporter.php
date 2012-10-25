<?php
/*
 * pg_stats_reporter
 *
 * Copyright (c) 2012, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// include sub module
require_once "../../pg_stats_reporter/lib/define.php";
require_once "../../pg_stats_reporter/lib/makeReport.php";

/* Initial setting of Smarty */
require_once SMARTY_PATH."Smarty.class.php";
$smarty = new Smarty();

/* キャッシュの設定と各ディレクトリの設定 */
$smarty->caching        = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->compile_check  = false;
// $smarty->cache_lifetime = 300;
$smarty->cache_lifetime = 5;
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
// TODO: Check out message_message_ja.xml
$locale_list = array();
$msg_len = strlen(MESSAGE_PREFIX);
$dir = opendir(MESSAGE_PATH);
while ($fn = readdir($dir)) {
	$pos = strpos($fn, ".");
	if (strncmp(MESSAGE_PREFIX, $fn, $msg_len) == 0
  	    && substr_compare($fn, MESSAGE_SUFFIX, $pos) == 0) {
		$locale_list[] = substr($fn, $msg_len, $pos - $msg_len);
	}
}
closedir($dir);

/* URLパラメータのreloadを確認し、キャッシュファイル削除 */
if (array_key_exists("reload", $_GET)) {
	deleteCacheFile();
}

/* 設定ファイルの読込み */
// エラーの時はエラーのみのページを表示
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
	exit;
}

/* レポート作成のための情報を設定 */
$target_data = array();
$report_cache_id = "";
if (count($_GET) == 4) {
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
}

/* キャッシュファイルの削除 */
$smarty->clearAllCache();

/* 設定ファイルの該当箇所取得 */
$target_info = $infoData[$target_data['repodb']];
$smarty->assign("target_info", $target_info);

/* メッセージファイル読込み */
if ($target_info['language'] == 'auto')
	$target_locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
else
	$target_locale = $target_info['language'];
$msg_file = MESSAGE_PATH.MESSAGE_PREFIX.locale_lookup($locale_list, $target_locale, false, "en").MESSAGE_SUFFIX;
readMessageFile($msg_file, $help_message, $error_message);

/* レポートページ作成 */
// DB接続
$conn = pg_connect($target_info['connect_str']);
if (!$conn) {
	die("connect error. repository database : ".$traget_data['repodb']);
}

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
$smarty->display("pg_stats_reporter.tpl", $report_cache_id);


/* delete information file */
function deleteCacheFile()
{
	if (is_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME)) {
		unlink(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME);
	}
}

/* read information file and make cache file */
function initInformationFile(&$info_data, &$err_msg)
{
	global $conf_key_list;
	global $report_default;

	$info_data = array();
	$err_msg = array();
	$cache_contents = array();
	$setting = $report_default;

	/* read cache file */
	if (is_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME)) {
		$info_data = parse_ini_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME, true);
		return $info_data;
	}

	/* read pg_stats_reporter.ini */
	if (!is_file(CONFIG_PATH.CONFIG_FILENAME)) {
		$err_msg[] = "pg_stats_reporter.ini not found.";
		return false;
	}
	$ini_data = parse_ini_file(CONFIG_PATH.CONFIG_FILENAME, true);

	/* check format and get data */
	foreach($ini_data as $repo_name => $data_array) {
		if (!is_array($data_array)) {
			$err_msg[] = "Does not contain a section(repositoryDB name:".$repo_name.").";
			return false;
		}

		foreach($data_array as $key => $val) {
			// check key name
			if (!array_key_exists($key, $report_default)
				&& !array_key_exists($key, $conf_key_list)) {
				$err_msg[] = "[".$repo_name."]".$key.": Item name is invalid.";
				continue;
			}
		}

		$cache_contents[] = "[".$repo_name."]\n";

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

		$cache_contents[] = "connect_str = \"".$connect_str."\"\n";

		// connect repository database and get target database information
		// and get pg_statsinfo version
		$conn = pg_connect($connect_str);
		if (!$conn) {
			$err_msg[] = "connect error.(repository database = ".$repo_name.")";
			$cache_contents[] = "repo_version = ".V23."\n";
		} else {
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
			} else {
				for ($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
					$row_array = pg_fetch_array($result, NULL, PGSQL_NUM);
					$cache_contents[] = "monitor[".$row_array[0]."] = \"".$row_array[1].":".$row_array[2]."\"\n";
					$ver_array = split("\.", $row_array[3]);
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

	// write cache file
	if (file_put_contents(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME, $cache_contents) == false) {
		$err_msg = "do not write cache file(".CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME.")";
		return false;
	}

	// read cache file
	$info_data = parse_ini_file(CACHE_CONFIG_PATH.CACHE_CONFIG_FILENAME, true);
	return $info_data;

}

/* read message file */
function readMessageFile($msg_file, &$help_message, &$error_message)
{
	global $help_list;

	$help_message = array();
	$error_message = array();

	if (!file_exists($msg_file)) {
		// メッセージファイルなしで英語ファイルにする場合、
		// メッセージはどこに出す?
		print "message file(".msg_file.") is not found.";
		$msg_file = MESSAGE_PATH.MESSAGE_PREFIX."en".MESSAGE_SUFFIX;
		if (!file_exists($msg_file)) {
			die("message file(".msg_file.") is not found.");
		}
	}

	$xml = simplexml_load_file($msg_file);
	if ($xml == false) {
		die("Access denied or invalid XML format.(".msg_file.")");
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

}

?> 
