<?php
/*
 * ajax_log_data
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// include sub module
require_once "../../pg_stats_reporter_lib/module/define.php";
require_once "../../pg_stats_reporter_lib/module/common.php";

/* パラメータの取得 */
$url_param = array();
$url_param['repodb'] = get_url_param('repodb');
$url_param['instid'] = get_url_param('instid');
$url_param['begin_date'] = get_url_param('begin');
$url_param['end_date'] = get_url_param('end');
$url_param['page'] = get_url_param('page');
$url_param['s_elevel'] = get_url_param('elevel');
$url_param['s_username'] = get_url_param('username');
$url_param['s_database'] = get_url_param('database');
$url_param['s_message'] = get_url_param('message');

/* 設定ファイルの読み込み */
if (!load_config($config, $err_msg)) {
	abort($err_msg);
}

/* レポート対象の設定を取得 */
$t_conf = $config[$url_param['repodb']];

/* ログデータの取得範囲を算出 */
$page_size = $config[GLOBAL_SECTION]['log_page_size'];
$offset = $page_size * ($url_param['page'] - 1);
$limit = $page_size;

/* リポジトリDBへのコネクションを取得 */
$conn = pg_connect($t_conf['connect_str']);
if (!$conn) {
	abort("connect error. repository database: " . $url_param['repodb']);
}
pg_set_client_encoding($conn, "UTF-8");

/* ログデータを取得する */
$query = $query_string['log'];
$values = array($url_param['instid'], $url_param['begin_date'], $url_param['end_date']);
$i = count($values);

if ($url_param['s_elevel']) {
	array_push($values, $url_param['s_elevel']);
	$query .= " AND elevel = $" . ++$i;
}
if ($url_param['s_username']) {
	array_push($values, $url_param['s_username']);
	$query .= " AND username = $" . ++$i;
}
if ($url_param['s_database']) {
	array_push($values, $url_param['s_database']);
	$query .= " AND database = $" . ++$i;
}
if ($url_param['s_message']) {
	array_push($values, $url_param['s_message']);
	$query .= " AND message ~* $" . ++$i;
}

array_push($values, $offset, $limit);
$query .= " ORDER BY timestamp OFFSET $" . ++$i . " LIMIT $" . ++$i;

$result = pg_query_params($conn, $query, $values);
if (!$result) {
	abort("query failed: " . pg_last_error());
}

/* ログデータのHTMLを生成 */
$html_string = "";
for ($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
	$html_string .= "<tr>";
	for ($j = 0 ; $j < pg_num_fields($result) ; $j++ ) {
		$html_string .= "<td>" . htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES) . "</td>";
	}
	$html_string .= "</tr>\n";
}

print $html_string;

exit;


function abort($message)
{
	/* HTTPステータスに内部エラーを設定 */
	header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal Server Error");
	die($message);
}

?>