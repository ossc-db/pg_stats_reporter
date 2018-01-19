<?php
/*
 * pg_stats_reporter
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// include sub module
require_once "../../pg_stats_reporter_lib/module/define.php";
require_once "../../pg_stats_reporter_lib/module/common.php";
require_once "../../pg_stats_reporter_lib/module/make_report.php";
require_once "../../pg_stats_reporter_lib/module/make_report_plans.php";
require_once SMARTY_PATH."Smarty.class.php";

$fullquery_string = array(); /* id -> div tag (associative arrays) */
$help_message = array();
$error_message = array();

set_default_timezone();

/* Initial setting of Smarty */
$smarty = new Smarty();

/* キャッシュの設定と各ディレクトリの設定 */
$smarty->caching        = Smarty::CACHING_LIFETIME_CURRENT;
$smarty->compile_check  = true;
$smarty->cache_lifetime = CACHE_LIFETIME;
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

/* pg_stats_reporterのバージョンを設定 */
$smarty->assign("program_version", PROGRAM_VERSION);

/* 生存期間を経過したキャッシュファイルを削除 */
$smarty->clearCache(TEMPLATE_FILE, null, null, $smarty->cache_lifetime);

/* パラメータの取得 */
$url_param = array();
$url_param['repodb'] = get_url_param('repodb');
$url_param['instid'] = get_url_param('instid');
$url_param['begin_date'] = get_url_param('begin');
$url_param['end_date'] = get_url_param('end');
$url_param['reload'] = get_url_param('reload');

/* リロードが指定されている場合はキャッシュを削除 */
if ($url_param['reload']) {
	deleteCacheFile($smarty);
}
unset($url_param['reload']);

/* 設定ファイルの読み込み */
if (!load_config($config, $err_msg)) {
	/* 1つでもエラーが発生したら、レポート表示可能なセクションがあっても */
	/* エラーで終了する(リポジトリDB接続エラー等も含む) */
	showConfigError($smarty, $config, $err_msg);
	exit;
}

/* 表示中のリポジトリが設定に存在しなければエラー */
if ($url_param['repodb'] && $url_param['instid']) {
	$repo_exists = false;
	foreach ($config as $repodb => $val_array) {
		if ($repodb == $url_param['repodb'] &&
			array_key_exists($url_param['instid'], $val_array['monitor'])) {
			$repo_exists = true;
			break;
		}
	}

	if (!$repo_exists) {
		$err_msg = "No report available<br/>";
		$err_msg .= "- Repository database not found : ".$url_param['repodb']."<br/>";
		showConfigError($smarty, $config, $err_msg);
		exit;
	}
}

/* パラメータ未指定の場合、デフォルトを設定 */
if (!$url_param['repodb']) {
	foreach ($config as $repodb => $val_array) {
		if (!array_key_exists('monitor', $val_array)) {
			continue;
		}
		$url_param['repodb'] = $repodb;
		break;
	}
}
if (!$url_param['instid']) {
	$monitor = $config[$url_param['repodb']]['monitor'];
	$url_param['instid'] = key($monitor);
}
if (!$url_param['begin_date']) {
	$url_param['begin_date'] = date('Y-m-d', time() - 24 * 60 * 60) . " 00:00:00";
}
if (!$url_param['end_date']) {
	$url_param['end_date'] = date('Y-m-d H:i:s');
}

/* キャッシュIDの生成 */
$cache_id = md5(serialize($url_param));

/* キャッシュが存在する場合はキャッシュを表示 */
if ($smarty->isCached(TEMPLATE_FILE, $cache_id)) {
	$smarty->display(TEMPLATE_FILE, $cache_id);
	exit;
}

/* レポート対象の設定を取得 */
$t_conf = $config[$url_param['repodb']];

/* メッセージファイルの読み込み */
load_message($t_conf['language'], $help_message, $error_message);

/* リポジトリDBへのコネクションを取得 */
$conn = pg_connect($t_conf['connect_str']);
if (!$conn) {
	showErrorReport($smarty, $config, $url_param,
		"connect error. repository database : " . $url_param['repodb']);
	exit;
}
pg_set_client_encoding($conn, "UTF-8");

/* トランザクション開始とロックの取得 */
$result = pg_query("BEGIN");
pg_free_result($result);
$result = pg_query("LOCK TABLE statsrepo.instance IN SHARE MODE");
if (!$result) {
	showErrorReport($smarty, $config, $url_param, pg_last_error());
	exit;
}
pg_free_result($result);

/* ヘッダからコンテンツまで作成 */
if (($html_string = makeReport($conn, $config, $url_param, $err_msg)) == null) {
	pg_close($conn);
	showErrorReport($smarty, $config, $url_param, $err_msg);
	exit;
}

pg_query("COMMIT");

/* リポジトリDBへのコネクションを切断 */
pg_close($conn);

/* レポートをブラウザに表示 */
$smarty->assign("header_menu", $html_string['header_menu']);
$smarty->assign("left_menu", $html_string['left_menu']);
$smarty->assign("contents", $html_string['contents']);
$smarty->display(TEMPLATE_FILE, $cache_id);

exit;


/* エラー画面の表示 */
function showErrorReport($smarty, $config, $url_param, $message)
{
	$smarty->assign("header_menu", makeHeaderMenu($config, $url_param));
	$smarty->assign("left_menu", makeLeftMenu($config, $url_param));
	$smarty->assign("contents",
		"<div id=\"contents\">\n<div id=\"message_dialog\">" . $message . "</div></div>\n");

	$smarty->caching = 0;
	$smarty->display(TEMPLATE_FILE);
}

/* 設定ファイルのエラー画面の表示 */
function showConfigError($smarty, $config, $message)
{
	$message .= "<br/>Note: Need to manually update the pg_stats_reporter.ini if have upgraded.";

	$smarty->assign("header_menu", makePlainHeaderMenu());
	$smarty->assign("left_menu", makeLeftMenu($config, null));
	$smarty->assign("contents",
		"<div id=\"contents\">\n<div id=\"message_dialog\">" . $message . "</div></div>\n");

	$smarty->caching = 0;
	$smarty->display(TEMPLATE_FILE);
}

?> 
