<?php
/*
 * log_viewer
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// include sub module
require_once "../../pg_stats_reporter_lib/module/define.php";
require_once "../../pg_stats_reporter_lib/module/common.php";
require_once "../../pg_stats_reporter_lib/module/make_report.php";
require_once SMARTY_PATH . "Smarty.class.php";

/* global variable */
$help_message = array();
$error_message = array();

set_default_timezone();

/* Initial setting of Smarty */
$smarty = new Smarty();

/* キャッシュの設定と各ディレクトリの設定 */
$smarty->caching        = Smarty::CACHING_OFF;
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

/* pg_stats_reporterのバージョンを設定 */
$smarty->assign("program_version", PROGRAM_VERSION);

/* 生存期間を経過したキャッシュファイルを削除 */
$smarty->clearCache(LOG_VIEWER_TEMPLATE_FILE, null, null, $smarty->cache_lifetime);

/* パラメータの取得 */
$url_param = array();
$url_param['repodb'] = get_url_param('repodb');
$url_param['instid'] = get_url_param('instid');
$url_param['begin_date'] = get_url_param('begin');
$url_param['end_date'] = get_url_param('end');
$url_param['s_elevel'] = get_url_param('elevel');
$url_param['s_username'] = get_url_param('username');
$url_param['s_database'] = get_url_param('database');
$url_param['s_message'] = get_url_param('message');
$url_param['reload'] = get_url_param('reload');

/* リロードが指定されている場合はキャッシュを削除 */
if ($url_param['reload']) {
	deleteCacheFile($smarty);
}
unset($url_param['reload']);

/* 設定ファイルの読み込み */
if (!load_config($config, $err_msg)) {
	showConfigError($smarty, $config, $err_msg);
	exit;
}

/* キャッシュIDの生成 */
$cache_id = md5(serialize($url_param));

/* キャッシュが存在する場合はキャッシュを表示 */
if ($smarty->isCached(LOG_VIEWER_TEMPLATE_FILE, $cache_id)) {
	$smarty->display(LOG_VIEWER_TEMPLATE_FILE, $cache_id);
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

/* ログレポートのHTMLデータを生成 */
if (($html_string = makeLogReport($conn, $config, $url_param, $err_msg)) == null) {
	showErrorReport($smarty, $config, $url_param, $err_msg);
	exit;
}

/* リポジトリDBへのコネクションを切断 */
pg_close($conn);

/* ログレポートをブラウザに表示 */
$smarty->assign("header_menu", $html_string['header_menu']);
$smarty->assign("left_menu", $html_string['left_menu']);
$smarty->assign("page_total", $html_string['page_total']);
$smarty->assign("help_dialog", $html_string['help_dialog']);
$smarty->assign("message_dialog", "");
$smarty->display(LOG_VIEWER_TEMPLATE_FILE, $cache_id);

exit;


/* エラー画面の表示 */
function showErrorReport($smarty, $config, $url_param, $message)
{
	global $help_message;

	$smarty->assign("header_menu", makeHeaderMenu($config, $url_param));
	$smarty->assign("left_menu", makeLeftMenu($config, $url_param));
	$smarty->assign("page_total", 0);
	$smarty->assign("help_dialog", $help_message['log_viewer']);
	$smarty->assign("message_dialog", "<div id=\"message_dialog\">" . $message . "</div>\n");

	$smarty->caching = 0;
	$smarty->display(LOG_VIEWER_TEMPLATE_FILE);
}

/* 設定ファイルのエラー画面の表示 */
function showConfigError($smarty, $config, $message)
{
	$smarty->assign("header_menu", makePlainHeaderMenu());
	$smarty->assign("left_menu", makeLeftMenu($config, null));
	$smarty->assign("page_total", 0);
	$smarty->assign("help_dialog", "");
	$smarty->assign("message_dialog", "<div id=\"message_dialog\">" . $message . "</div>\n");

	$smarty->caching = 0;
	$smarty->display(LOG_VIEWER_TEMPLATE_FILE);
}

?>
