<?php
/*
 * make_report
 *
 * Copyright (c) 2012,2014, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

/* make error tag */
function makeErrorTag() {

	$message = call_user_func_array ("sprintf", func_get_args());

	return "<p class=\"error\">".$message."</p>\n";
}

function makeReport($conn, $config, $url_param, &$err_msg)
{
	global $help_message;
	global $error_message;

	$html_string = array();
	$err_msg = null;

	/* make header menu html */
	$html_string["header_menu"] = makeHeaderMenu($config, $url_param);
	
	/* make left menu html */
	$html_string["left_menu"] = makeLeftMenu($config, $url_param);

	/* get snapshot id */
	if (!getSnapshotID($conn, $url_param, &$snapids, &$snapdates)) {
		$err_msg = sprintf($error_message['query_error'], pg_last_error($conn));
		return null;
	} else if ($snapids[0] == $snapids[1] || is_null($snapids[0]) || is_null($snapids[1])) {
		/* check whether there are two more than a snapshot */
		$err_msg = $error_message['short_snapshots'];
		return null;
	} else {
		/* make contents html */
		$html_string["contents"] = makeContents($conn, $config, $url_param, $snapids);
	}
	return $html_string;
}

function makeLogReport($conn, $config, $url_param, &$err_msg)
{
	global $help_message;
	global $error_message;
	global $query_string;

	$query = $query_string['log_size'];
	$values = array($url_param['instid'], $url_param['begin_date'], $url_param['end_date']);
	$i = count($values);
	$html_string = array();
	$err_msg = null;

	$t_conf = $config[$url_param['repodb']];

	/* check repository version */
	if ($t_conf['repo_version'] < V30) {
		$err_msg = sprintf($error_message['st_version'], "3.0.0");
		return null;
	}

	/* get page total */
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

	pg_send_query_params($conn, $query, $values);
	$result = pg_get_result($conn);
	if (pg_result_status($result) != PGSQL_TUPLES_OK) {
		if (pg_result_error_field($result, PGSQL_DIAG_SQLSTATE) == '2201B') {
			$err_msg = sprintf($error_message['invalid_regex'],
				"MESSAGE='" . $url_param['s_message'] . "'" , pg_result_error($result));
		} else {
			$err_msg = sprintf($error_message['query_error'], pg_result_error($result));
		}
		return null;
	}
	$log_size = pg_fetch_result($result, 0, 0);
	pg_free_result($result);
	if ($log_size == 0) {
		$err_msg = $error_message['no_result'];
		return null;
	}
	$page_total = ceil($log_size / $config[GLOBAL_SECTION]['log_page_size']);

	/* make header menu html */
	$html_string["header_menu"] = makeHeaderMenu($config, $url_param);

	/* make left menu html */
	$html_string["left_menu"] = makeLeftMenu($config, $url_param);

	/* make contents html */
	$html_string['page_total'] = $page_total;
	$html_string["help_dialog"] = $help_message['log_report'];

	return $html_string;
}

/* make report for commandline mode*/
function makeReportForCommandline($conn, $infoData, $target_info, $snapids)
{
	$html_string = array();

	/* make header menu html */
	$html_string["header_menu"] = makeHeaderMenu($infoData, $target_info);

	/* make left menu html */
	$html_string["left_menu"] = "";

	/* make contents html */
	$html_string["contents"] = makeContents($conn, $infoData, $target_info, $snapids);

	return $html_string;
}

function makeHeaderMenu($infoData, $targetInfo)
{
	if (empty($_SERVER['DOCUMENT_ROOT']))
		$html_string = "<div id=\"header_menu_commandline\">\n";
	else
		$html_string = "<div id=\"header_menu\">\n";

	$targetList = $infoData[$targetInfo['repodb']];

	$html_string .=
<<< EOD
<ul id="dropdown" class="sf-menu menu">

EOD;

	if ($targetList['summary']
		|| $targetList['alert']) {

		$html_string .= "<li><a href=\"#summary\">Summary</a>";

		/* Alert */
		if ($targetList['alert']) {
			$html_string .= "<ul>\n";
			$html_string .= "<li><a href=\"#alert\">Alert</a></li>\n";
			$html_string .= "</ul>";
		}

		$html_string .= "</li>\n";
	}

	/* Database System */
	if ($targetList['database_statistics']
		|| $targetList['transaction_statistics']
		|| $targetList['database_size']
		|| $targetList['recovery_conflicts']
		|| $targetList['wal_statistics']
		|| $targetList['instance_processes_ratio']
		|| $targetList['instance_processes']) {

		$html_string .= "<li><a href=\"#database_system\">Database System</a><ul>\n";

		/* Database Statistics */
		if ($targetList['database_statistics']
			|| $targetList['transaction_statistics']
			|| $targetList['database_size']
			|| $targetList['recovery_conflicts']) {

			$html_string .= "<li><a href=\"#database_statistics\">Database Statistics</a><ul>\n";

			if ($targetList['transaction_statistics'])
				$html_string .= "<li><a href=\"#transaction_statistics\">Transaction Statistics</a></li>\n";
			if ($targetList['database_size'])
				$html_string .= "<li><a href=\"#database_size\">Database Size</a></li>\n";
			if ($targetList['recovery_conflicts'])
				$html_string .= "<li><a href=\"#recovery_conflicts\">Recovery Conflicts</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Instance Activity */
		if ($targetList['wal_statistics']
			|| $targetList['instance_processes_ratio']
			|| $targetList['instance_processes']) {

			$html_string .= "<li><a href=\"#instance_activity\">Instance Activity</a><ul>\n";

			if ($targetList['wal_statistics'])
				$html_string .= "<li><a href=\"#wal_statistics\">WAL Statistics</a></li>\n";
			if ($targetList['instance_processes_ratio'])
				$html_string .= "<li><a href=\"#instance_processes_ratio\">Instance Processes Ratio</a></li>\n";
			if ($targetList['instance_processes'])
				$html_string .= "<li><a href=\"#instance_processes\">Instance Processes</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		$html_string .= "</ul></li>\n";
	}

	/* Operating System */
	if ($targetList['cpu_usage']
		|| $targetList['load_average']
		|| $targetList['io_usage']
		|| $targetList['memory_usage']
		|| $targetList['disk_usage_per_tablespace']
		|| $targetList['disk_usage_per_table']) {

		$html_string .= "<li><a href=\"#operating_system\">Operating System</a><ul>\n";

		/* OS Resource Usage */
		if ($targetList['cpu_usage']
			|| $targetList['load_average']
			|| $targetList['io_usage']
			|| $targetList['memory_usage']) {

			$html_string .= "<li><a href=\"#os_resource_usage\">OS Resource Usage</a><ul>\n";

			if ($targetList['cpu_usage'])
				$html_string .= "<li><a href=\"#cpu_usage\">CPU Usage</a></li>\n";
			if ($targetList['load_average'])
				$html_string .= "<li><a href=\"#load_average\">Load Average</a></li>\n";
			if ($targetList['io_usage'])
				$html_string .= "<li><a href=\"#io_usage\">I/O Usage</a></li>\n";
			if ($targetList['memory_usage'])
				$html_string .= "<li><a href=\"#memory_usage\">Memory Usage</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Disk Usage */
		if ($targetList['disk_usage_per_tablespace']
			|| $targetList['disk_usage_per_table']) {

			$html_string .= "<li><a href=\"#disk_usage\">Disk Usage</a><ul>\n";

			if ($targetList['disk_usage_per_tablespace'])
				$html_string .= "<li><a href=\"#disk_usage_per_tablespace\">Disk Usage per Tablespace</a></li>\n";
			if ($targetList['disk_usage_per_table'])
				$html_string .= "<li><a href=\"#disk_usage_per_table\">Disk Usage per Table</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		$html_string .= "</ul></li>\n";
	}

	/* SQL */
	if ($targetList['heavily_updated_tables']
		|| $targetList['heavily_accessed_tables']
		|| $targetList['low_density_tables']
		|| $targetList['fragmented_tables']
		|| $targetList['functions']
		|| $targetList['statements']
		|| $targetList['long_transactions']
		|| $targetList['lock_conflicts']) {

		$html_string .= "<li><a href=\"#sql\">SQL</a><ul>\n";

		/* Notable Tables */
		if ($targetList['heavily_updated_tables']
			|| $targetList['heavily_accessed_tables']
			|| $targetList['low_density_tables']
			|| $targetList['fragmented_tables']) {

			$html_string .= "<li><a href=\"#notable_tables\">Notable Tables</a><ul>\n";

			if ($targetList['heavily_updated_tables'])
				$html_string .= "<li><a href=\"#heavily_updated_tables\">Heavily Updated Tables</a></li>\n";
			if ($targetList['heavily_accessed_tables'])
				$html_string .= "<li><a href=\"#heavily_accessed_tables\">Heavily Accessed Tables</a></li>\n";
			if ($targetList['low_density_tables'])
				$html_string .= "<li><a href=\"#low_density_tables\">Low Density Tables</a></li>\n";
			if ($targetList['fragmented_tables'])
				$html_string .= "<li><a href=\"#fragmented_tables\">Fragmented Tables</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Query Activity */
		if ($targetList['functions']
			|| $targetList['statements']) {
			$html_string .= "<li><a href=\"#query_activity\">Query Activity</a><ul>\n";

			if ($targetList['functions'])
				$html_string .= "<li><a href=\"#qa_functions\">Functions</a></li>\n";
			if ($targetList['statements'])
				$html_string .= "<li><a href=\"#qa_statements\">Statements</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Long Transactions */
		if ($targetList['long_transactions'])
			$html_string .= "<li><a href=\"#long_transactions\">Long Transactions</a></li>\n";

		/* Lock Conflicts */
		if ($targetList['lock_conflicts'])
			$html_string .= "<li><a href=\"#lock_conflicts\">Lock Conflicts</a></li>\n";

		$html_string .= "</ul></li>\n";
	}

	/* Activities */
	if ($targetList['checkpoint_activity']
		|| $targetList['basic_statistics']
		|| $targetList['io_statistics']
		|| $targetList['current_replication_status']
		|| $targetList['replication_delays']) {

		$html_string .= "<li><a href=\"#operation_activity\">Operation Activity</a><ul>\n";

		/* Checkpoint Activity */
		if ($targetList['checkpoint_activity'])
			$html_string .= "<li><a href=\"#checkpoint_activity\">Checkpoint Activity</a></li>\n";

		/* Autovacuum Activity */
		if ($targetList['basic_statistics']
			|| $targetList['io_statistics']) {

			$html_string .= "<li><a href=\"#autovacuum_activity\">Autovacuum Activity</a><ul>\n";

			if ($targetList['basic_statistics'])
				$html_string .= "<li><a href=\"#basic_statistics\">Basic Statistics (Average)</a></li>\n";
			if ($targetList['io_statistics'])
				$html_string .= "<li><a href=\"#io_statistics\">I/O Statistics (Average)</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Replication Activity */
		if ($targetList['current_replication_status']
			|| $targetList['replication_delays'])

			$html_string .= "<li><a href=\"#replication_activity\">Replication Activity</a><ul>\n";

			if($targetList['current_replication_status'])
				$html_string .= "<li><a href=\"#current_replication_status\">Current Replication Status</a></li>\n";
			if($targetList['replication_delays'])
				$html_string .= "<li><a href=\"#replication_delays\">Replication Delays</a></li>\n";

			$html_string .= "</ul></li>\n";

		$html_string .= "</ul></li>\n";
	}

	/* Information */
	if ($targetList['database']
		|| $targetList['schema']
		|| $targetList['table']
		|| $targetList['index']
		|| $targetList['view']
		|| $targetList['sequence']
		|| $targetList['trigger']
		|| $targetList['role']
		|| $targetList['parameter']
		|| $targetList['alert']
		|| $targetList['profiles']) {

		$html_string .= "<li><a href=\"#information\">Information</a><ul>\n";

		/* Schema Information */
		if ($targetList['database']
			|| $targetList['schema']
			|| $targetList['table']
			|| $targetList['index']
			|| $targetList['view']
			|| $targetList['sequence']
			|| $targetList['trigger']) {

			$html_string .= "<li><a href=\"#schema_information\">Schema Information</a><ul>\n";
			if ($targetList['database'])
				$html_string .= "<li><a href=\"#database\">Database</a></li>\n";
			if ($targetList['schema'])
				$html_string .= "<li><a href=\"#schema\">Schema</a></li>\n";
			if ($targetList['table'])
				$html_string .= "<li><a href=\"#table\">Table</a></li>\n";
			if ($targetList['index'])
				$html_string .= "<li><a href=\"#index\">Index</a></li>\n";
			if ($targetList['view'])
				$html_string .= "<li><a href=\"#view\">View</a></li>\n";
			if ($targetList['sequence'])
				$html_string .= "<li><a href=\"#sequence\">Sequence</a></li>\n";
			if ($targetList['trigger'])
				$html_string .= "<li><a href=\"#trigger\">Trigger</a></li>\n";

			$html_string .= "</ul></li>\n";
		}


		/* Setting Parameters */
		if ($targetList['role']
			|| $targetList['parameter']) {

			$html_string .= "<li><a href=\"#setting_parameters\">Setting Parameters</a><ul>\n";

			if ($targetList['role'])
				$html_string .= "<li><a href=\"#role\">Role</a></li>\n";
			if ($targetList['parameter'])
				$html_string .= "<li><a href=\"#parameter\">Paramter</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Profiles */
		if ($targetList['profiles'])
			$html_string .= "<li><a href=\"#profiles\">Profiles</a></li>\n";

		$html_string .= "</ul></li>\n";
	}

	$html_string .=
<<< EOD
</ul>

EOD;
	if (!empty($_SERVER['DOCUMENT_ROOT'])) {
		$html_string .=
<<< EOD
<!-- Log Report -->
<ul id="dropdown2" class="sf-menu menu">
<li><a href="#log_report">Log Report</a></li>
</ul>
<!-- hide left menu button  -->
<div align="right" class="jquery_ui_button_max">
  <div><button id="jquery_ui_button_arrowthick"></button></div>
</div>

EOD;
	}

	$html_string .=
<<< EOD
<!-- top button -->
<div align="right" class="jquery_ui_button_top">
  <div><button id="jquery_ui_button_top"></button></div>
</div>

EOD;

	$html_string .= "</div> <!-- header menu end -->\n";

	return $html_string;
}

function makePlainHeaderMenu()
{
	/* 大項目レベルだけの方がいいかも */
	if (empty($_SERVER['DOCUMENT_ROOT'])) 
		$html_string = "<div id=\"header_menu_commandline\">";
	else
		$html_string = "<div id=\"header_menu\">";

	$html_string .=
<<< EOD
<ul id="dropdown" class="sf-menu">
<li><a>Summary</a><ul>
  <li><a>Alert</a><li>
</ul></li>
<li><a>Database System</a><ul>
  <li><a>Database Statistics</a><ul>
    <li><a>Transaction Statistics</a></li>
    <li><a>Database Size</a></li>
    <li><a>Recovery Conflicts</a></li>
  </ul></li>
  <li><a>Instance Activity</a><ul>
    <li><a>WAL Statistics</a></li>
    <li><a>Instance Processes Ratio</a></li>
    <li><a>Instance Processes</a></li>
  </ul></li>
</ul></li>
<li><a>Operating System</a><ul>
  <li><a>OS Resource Usage</a><ul>
    <li><a>CPU Usage</a></li>
    <li><a>Load Average</a></li>
    <li><a>I/O Usage</a></li>
    <li><a>Memory Usage</a></li>
  </ul></li>
  <li><a>Disk Usage</a><ul>
    <li><a>Disk Usage per Tablespace</a></li>
    <li><a>Disk Usage per Table</a></li>
  </ul></li>
</ul></li>
<li><a>SQL</a><ul>
  <li><a>Notable Tables</a><ul>
    <li><a>Heavily Updated Tables</a></li>
    <li><a>Heavily Accessed Tables</a></li>
    <li><a>Low Density Tables</a></li>
    <li><a>Fragmented Tables</a></li>
  </ul></li>
  <li><a>Query Activity</a><ul>
    <li><a>Functions</a></li>
    <li><a>Statements</a></li>
  </ul></li>
  <li><a>Long Transactions</a></li>
  <li><a>Lock Conflicts</a></li>
</ul></li>
<li><a>Activities</a><ul>
  <li><a>Checkpoint Activity</a></li>
  <li><a>Autovacuum Activity</a><ul>
    <li><a>Basic Statistics (Average)</a></li>
    <li><a>I/O Statistics (Average)</a></li>
  </ul></li>
  <li><a>Replication Activity</a><ul>
    <li><a>Current Replication Status</a></li>
    <li><a>Replication Delays</a></li>
  </ul></li>
</ul></li>
<li><a>Information</a><ul>
  <li><a>Schema Information</a><ul>
    <li><a>Database</a></li>
    <li><a>Schema</a></li>
    <li><a>Table</a></li>
    <li><a>Index</a></li>
    <li><a>View</a></li>
    <li><a>Sequence</a></li>
    <li><a>Trigger</a></li>
  </ul></li>
  <li><a>Setting Parameters</a><ul>
    <li><a>Role</a></li>
    <li><a>Parameter</a></li>
  </ul></li>
  <li><a>Alert</a></li>
  <li><a>Profiles</a></li>
</ul></li>
</ul>

EOD;
	if (!empty($_SERVER['DOCUMENT_ROOT'])) {
		$html_string .=
<<< EOD
<!-- Log Report -->
<ul id="dropdown2" class="sf-menu menu">
  <li><a>Log Report</a></li>
</ul>
<!-- hide left menu button  -->
<div align="right" class="jquery_ui_button_max"> 
  <div><button id="jquery_ui_button_arrowthick"></button></div>
</div>

EOD;
	}

	$html_string .=
<<< EOD
<!-- top button -->
<div align="right" class="jquery_ui_button_top">
  <div><button id="jquery_ui_button_top"></button></div>
</div>
	
EOD;

	$html_string .= "</div> <!-- header menu end -->\n";

	return $html_string;
}

function makeLeftMenu($infoData, $targetInfo)
{
	$begin_date = date('Y-m-d', time() - 24*60*60)." 00:00:00";
	$end_date = date('Y-m-d H:i:s');

	$html_string = "<div id=\"left_menu\" class=\"scrollbox\">\n";
	$html_string .= "<img width=\"100%\" src=\"".IMAGE_FILE."\"/>\n";

	/* report data information */
	if ($targetInfo && $targetInfo['repodb']) {
		$repoInfo = $infoData[$targetInfo['repodb']];
		$targetName = $repoInfo['monitor'][$targetInfo['instid']];
		$begin_date = $targetInfo['begin_date'];
		$end_date = $targetInfo['end_date'];

		$html_string .= "<p class=\"report_data\">\n";
		$html_string .= "[<span id=\"target_repodb\">" . $targetInfo['repodb'] . "</span>]<br/>";
		$html_string .= "<span id=\"target_name\">" . $targetName . "</span><br/>";
		$html_string .= "begin:<br/><span id=\"target_begin\">" . $begin_date . "</span><br/>";
		$html_string .= "end:<br/><span id=\"target_end\">" . $end_date . "</span><br/>";
		$html_string .= "<span id=\"target_instid\" style=\"display: none\">" . $targetInfo['instid'] . "</span></p>\n";
	} else {
		$html_string .= "<p class=\"report_data\">\n[ --- ]<br/>---<br/>begin: ---<br/>end: ---<br/>\n</p>\n";
	}

	/* change report range button */
	$html_string .=
<<< EOD
<div id="report_range_dialog" title="Create new report">
  <p align="center">
    <label for="begin_date">begin:</label>
    <input type="text" id="begin_date" name="begin_date" cheked/>
	<label for="end_date">end:</label>
    <input type="text" id="end_date" name="end_date"/>
  </p>
</div><br/>
<div align="center">
  <button id="report_range">Create new report</button>
</div>
EOD;

	/* accordion menu */
	$html_string .= "<br/>\n<div id=\"accordion\">\n";

	foreach($infoData as $repo => $val_array) {
		if ($repo == GLOBAL_SECTION) {
			continue;
		}
		$html_string .= "<h3>" . $repo . "</h3>\n<div>\n";

		if (array_key_exists("monitor", $val_array)) {
			foreach ($val_array['monitor'] as $id => $name) {
				$html_string .= "<a href=\"repodb=" . rawurlencode($repo) . "&instid=" . $id .
					"&begin=" . rawurlencode($begin_date) . "&end=" . rawurlencode($end_date) .
					"\">" . $name . "</a><br/>\n";
			}
		}

		$html_string .= "</div>\n";
	}
	$html_string .= "</div> <!-- accordion end -->\n";

	/* reload button */
	$html_string .=
<<< EOD
<br/><br/>
<div align="center">
  <button id="reload_setting">Reload config</button>
</div>
</div> <!-- left menu end -->

EOD;

	return $html_string;
}

function makeContents($conn, $infoData, $targetInfo, $snapids)
{
	global $help_message;
	global $error_message;
	global $query_string;

	$targetData = $infoData[$targetInfo['repodb']];

	/* Contents Header */
	if (empty($_SERVER['DOCUMENT_ROOT']))
		/* When it is run in command-line mode */
		$html_string = "<div id=\"contents_commandline\">\n";
	else
		$html_string = "<div id=\"contents\">\n";

	$html_string .=
<<< EOD
<div class="top_jump_margin"></div>

EOD;

	/* get checkpoint data */
	$result = pg_query_params($conn, $query_string['checkpoint_time'], array($targetInfo["instid"], $snapids[0], $snapids[1]));
	if (!$result) {
		return $htmlString."<br/><br/><br/>".makeErrorTag($error_message['query_error'], pg_last_error($conn));
	}

	$html_string .= "<!--\ncheckpoint date list -->\n<script type=\"text/javascript\">\nvar checkpoint_date_list = [\n";
	for ($i = 0 ; $i < pg_num_rows($result) ; $i++) {
		$html_string .= "[\"".pg_fetch_result($result, $i, 0)."\", \"".pg_fetch_result($result, $i, 1)."\"],\n";
	}
	$html_string .= "];\n</script>\n\n";
	pg_free_result($result);

	/* Summary */
	$html_string .= makeSummaryReport($conn, $targetData, $snapids, $error_message);

	/* Database System */
	$html_string .= makeDatabaseSystemReport($conn, $targetData, $snapids, $error_message);

	/* Operating System */
	$html_string .= makeOperatingSystemReport($conn, $targetData, $snapids, $error_message);

	/* SQL */
	$html_string .= makeSQLReport($conn, $targetData, $snapids, $error_message);

	/* Activities */
	$html_string .= makeActivitiesReport($conn, $targetData, $snapids, $error_message);

	/* Information */
	$html_string .= makeInformationReport($conn, $targetData, $snapids, $error_message);

	/* help dialog */
	foreach($help_message as $msg)
		$html_string .= $msg;

	/* Contents Footer */
	$html_string .=
<<< EOD
</div> <!-- contents end -->

EOD;

	return $html_string;
}

function makeSummaryReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['summary']
		&& !$target['alert'])
		return "";

	$htmlString =
<<< EOD

<div id="summary" class="jump_margin"></div>
<h1>Summary</h1>

EOD;

	if ($target['summary']) {
		$htmlString .=
<<< EOD
<div align="right" class="jquery_ui_button_info_h1">
  <div><button class="help_button" dialog="#summary_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['summary'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}
	
		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "summary");
		}
		pg_free_result($result);
	}

	if ($target['alert']) {
		$htmlString .=
<<< EOD

<div id="alert" class="jump_margin"></div>
<h2>Alert</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#alert_dialog"></button></div>
</div>

EOD;
		if ($target['repo_version'] >= V30) {
			$result = pg_query_params($conn, $query_string['alert'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}
	
			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "alert", 10, true);
			}
			pg_free_result($result);
		} else {
			$htmlString .= makeErrorTag($errorMsg['st_version'], "3.0.0");
		}
	}

	return $htmlString;
}

function makeDatabaseSystemReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['database_statistics']
		&& !$target['transaction_statistics']
		&& !$target['database_size']
		&& !$target['recovery_conflicts']
		&& !$target['wal_statistics']
		&& !$target['instance_processes_ratio']
		&& !$target['instance_processes'])
		return "";

	$htmlString =
<<< EOD

<div id="database_system" class="jump_margin"></div>
<h1>Database System</h1>

EOD;

	/* Database Statistics */
	if ($target['database_statistics']
		|| $target['transaction_statistics']
		|| $target['database_size']
		|| $target['recovery_conflicts']) {

		$htmlString .=
<<< EOD
<div id="database_statistics" class="jump_margin"></div>
<h2>Database Statistics</h2>

EOD;

		if ($target['database_statistics']) {
			$htmlString .=
<<< EOD
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#database_statistics_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['database_statistics'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "database_statistics", 5, true);
			}
			pg_free_result($result);

		}

		if ($target['transaction_statistics']) {
			$htmlString .=
<<< EOD
<div id="transaction_statistics" class="jump_margin"></div>
<h3>Transaction Statistics</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#transaction_statistics_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['transaction_statistics'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'Transaction Statistics'");
				array_push($opt, "ylabel: 'Transaction per second (xact/s)'");
				array_push($opt, "labelsKMB: true");
				$htmlString .= makeLineGraphHTML($name, $value, "transaction_statistics", $opt);
			}
			pg_free_result($result);

		}

		if ($target['database_size']) {
			$htmlString .=
<<< EOD
<div id="database_size" class="jump_margin"></div>
<h3>Database Size</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#database_size_dialog"></button></div>
</div>
EOD;

			$result = pg_query_params($conn, $query_string['database_size'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'Database Size'");
				array_push($opt, "ylabel: 'Database Size (Bytes)'");
				array_push($opt, "labelsKMG2: true");
				$htmlString .= makeLineGraphHTML($name, $value, "database_size", $opt);
			}
			pg_free_result($result);

		}

		if ($target['recovery_conflicts']) {
			$htmlString .=
<<< EOD
<div id="recovery_conflicts" class="jump_margin"></div>
<h3>Recovery Conflicts</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#recovery_conflicts_dialog"></button></div>
</div>

EOD;
			$result = pg_query_params($conn, $query_string['recovery_conflicts'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "recovery_conflicts", 5, true);
			}
			pg_free_result($result);
		}
	}

	/* Instance Activity */
	if ($target['wal_statistics']
		|| $target['instance_processes_ratio']
		|| $target['instance_processes']) {

		$htmlString .=
<<< EOD
<div id="instance_activity" class="jump_margin"></div>
<h2>Instance Activity</h2>

EOD;

		if ($target['wal_statistics']) {
			$htmlString .=
<<< EOD
<div id="wal_statistics" class="jump_margin"></div>
<h3>WAL Statistics</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#wal_statistics_dialog"></button></div>
</div>

EOD;
			if ($target['repo_version'] >= V24) {
				$result = pg_query_params($conn, $query_string['wal_statistics_stats'], $snapids);
				if (!$result) {
					return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}
				// データがない場合、カラムにはNULLが入っている
				if (is_null(pg_fetch_result($result,0,0)) == 1) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					$htmlString .= makeTablePagerHTML($result, "wal_statistics_stats", 5, false);
				}
				pg_free_result($result);

				$result = pg_query_params($conn, $query_string['wal_statistics'], $snapids);
				if (!$result) {
					return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}

				if (pg_num_rows($result) == 0) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					$htmlString .= makeWALStatisticsGraphHTML($result);
				}
				pg_free_result($result);
			} else {
				$htmlString .= makeErrorTag($errorMsg['st_version'], "2.4.0");
			}
		}

		if ($target['instance_processes_ratio']) {
			$htmlString .=
<<< EOD
<div id="instance_processes_ratio" class="jump_margin"></div>
<h3>Instance Processes Ratio</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#instance_processes_ratio_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['instance_processes_ratio'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (is_null(pg_fetch_result($result,0,0)) == 1) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "instance_processes_ratio", 5, false);
			}
			pg_free_result($result);
		}

		if ($target['instance_processes']) {
			$htmlString .=
<<< EOD
<div id="instance_processes" class="jump_margin"></div>
<h3>Instance Processes</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#instance_processes_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['instance_processes'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$opt = array();
				array_push($opt, "title: 'Instance Processes'");
				array_push($opt, "ylabel: 'Percent (%)'");
				$htmlString .= makeSimpleLineGraphHTML($result, "instance_processes", $opt, true, false);
			}
			pg_free_result($result);
		}
	}

	return $htmlString;
}

function makeOperatingSystemReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['cpu_usage']
		&& !$target['load_average']
		&& !$target['io_usage']
		&& !$target['memory_usage']
		&& !$target['disk_usage_per_tablespace']
		&& !$target['disk_usage_per_table'])
		return "";

	$htmlString =
<<< EOD
<div id="operating_system" class="jump_margin"></div>
<h1>Operating System</h1>

EOD;

	/* OS Resource Usage */
	if ($target['cpu_usage']
		|| $target['load_average']
		|| $target['io_usage']
		|| $target['memory_usage']) {

		$htmlString .=
<<< EOD
<div id="os_resource_usage" class="jump_margin"></div>
<h2>OS Resource Usage</h2>

EOD;

		if ($target['cpu_usage']) {
			$htmlString .=
<<< EOD
<div id="cpu_usage" class="jump_margin"></div>
<h3>CPU Usage</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#cpu_usage_dialog"></button></div>
</div>

EOD;
			$result = pg_query_params($conn, $query_string['cpu_usage'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$opt = array();
				array_push($opt, "title: 'CPU Usage'");
				array_push($opt, "ylabel: 'Percent (%)'");
				$htmlString .= makeSimpleLineGraphHTML($result, "cpu_usage", $opt, true, false);
			}
			pg_free_result($result);

		}

		if ($target['load_average']) {
			$htmlString .=
<<< EOD
<div id="load_average" class="jump_margin"></div>
<h3>Load Average</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#load_average_dialog"></button></div>
</div>

EOD;
			if ($target['repo_version'] >= V24) {
				$result = pg_query_params($conn, $query_string['load_average'], $snapids);
				if (!$result) {
					return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}

				if (pg_num_rows($result) == 0) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					$opt = array();
					array_push($opt, "title: 'Load Average'");
					array_push($opt, "ylabel: 'Load Average'");
					$htmlString .= makeSimpleLineGraphHTML($result, "load_average", $opt, false, false);
				}
				pg_free_result($result);
			} else {
				$htmlString .= makeErrorTag($errorMsg['st_version'], "2.4.0");
			}
		}

		if ($target['io_usage']) {
			$htmlString .=
<<< EOD
<div id="io_usage" class="jump_margin"></div>
<h3>I/O Usage</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#io_usage_dialog"></button></div>
</div>

EOD;

			// I/O Usage
			$result = pg_query_params($conn, $query_string['io_usage'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "io_usage", 5, true);
			}
			pg_free_result($result);

			// I/O Size
			$result = pg_query_params($conn, $query_string['io_size'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'I/O Size'");
				array_push($opt, "ylabel: 'Data Size (Bytes/s)'");
				array_push($opt, "labelsKMG2: true");
				$htmlString .= makeLineGraphHTML($name, $value, "io_size", $opt);
			}
			pg_free_result($result);

			// I/O Time
			$result = pg_query_params($conn, $query_string['io_time'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'I/O Time'");
				array_push($opt, "ylabel: 'Average Time (sec)'");
				array_push($opt, "labelsKMB: true");
				$htmlString .= makeLineGraphHTML($name, $value, "io_time", $opt);
			}
			pg_free_result($result);
		}

		if ($target['memory_usage']) {
			$htmlString .=
<<< EOD
<div id="memory_usage" class="jump_margin"></div>
<h3>Memory Usage</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#memory_usage_dialog"></button></div>
</div>

EOD;
			if ($target['repo_version'] >= V24) {
				$result = pg_query_params($conn, $query_string['memory_usage'], $snapids);
				if (!$result) {
					return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}

				if (pg_num_rows($result) == 0) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					$opt = array();
					array_push($opt, "title: 'Memory Usage (Linear Scale)'");
					array_push($opt, "ylabel: 'Size (Bytes)'");
					array_push($opt, "labelsKMG2: true");
					$htmlString .= makeSimpleLineGraphHTML($result, "memory_usage", $opt, false, true);
				}
				pg_free_result($result);
			} else {
				$htmlString .= makeErrorTag($errorMsg['st_version'], "2.4.0");
			}
		}
	}

	/* Disk Usage */
	if ($target['disk_usage_per_tablespace']
		|| $target['disk_usage_per_table']) {

	$htmlString .=
<<< EOD
<div id="disk_usage" class="jump_margin"></div>
<h2>Disk Usage</h2>

EOD;

		if ($target['disk_usage_per_tablespace']) {
			$htmlString .=
<<< EOD
<div id="disk_usage_per_tablespace" class="jump_margin"></div>
<h3>Disk Usage per Tablespace</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#disk_usage_per_tablespace_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['disk_usage_per_tablespace'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "disk_usage_per_tablespace", 5, true);
			}
			pg_free_result($result);
		}

		if ($target['disk_usage_per_table']) {
			$htmlString .=
<<< EOD
<div id="disk_usage_per_table" class="jump_margin"></div>
<h3>Disk Usage per Table</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#disk_usage_per_table_dialog"></button></div>
</div>

EOD;

			// Disk Usage per Table
			$result = pg_query_params($conn, $query_string['disk_usage_per_table'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "disk_usage_per_table", 10, true);
			}
			pg_free_result($result);

			// Table Size
			$result = pg_query_params($conn, $query_string['table_size'], array($snapids[1]));
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$value = makeTupleListForPieGraph($result);
				if (count($value) == 0)
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				else
					$htmlString .= makePieGraphHTML($value, "table_size", "Table Size");
			}
			pg_free_result($result);

			// Disk Read
			$result = pg_query_params($conn, $query_string['disk_read'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$value = makeTupleListForPieGraph($result);
				if (count($value) ==0)
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				else
					$htmlString .= makePieGraphHTML($value, "disk_read", "Disk Read");
			}
			pg_free_result($result);
		}
	}

	return $htmlString;
}

function makeSQLReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['heavily_updated_tables']
		&& !$target['heavily_accessed_tables']
		&& !$target['low_density_tables']
		&& !$target['fragmented_tables']
		&& !$target['functions']
		&& !$target['statements']
		&& !$target['long_transactions']
		&& !$target['lock_conflicts'])
		return "";

	$htmlString =
<<< EOD
<div id="sql" class="jump_margin"></div>
<h1>SQL</h1>

EOD;

	/* Notable Table */
	if ($target['heavily_updated_tables']
		|| $target['heavily_accessed_tables']
		|| $target['low_density_tables']
		|| $target['fragmented_tables']) {

		$htmlString .=
<<< EOD
<div id="notable_tables" class="jump_margin"></div>
<h2>Notable Tables</h2>

EOD;

		if ($target['heavily_updated_tables']){
			$htmlString .=
<<< EOD
<div id="heavily_updated_tables" class="jump_margin"></div>
<h3>Heavily Updated Tables</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#heavily_updated_tables_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['heavily_updated_tables'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "heavily_updated_tables", 10, true);
			}
			pg_free_result($result);

		}

		if ($target['heavily_accessed_tables']){
			$htmlString .=
<<< EOD
<div id="heavily_accessed_tables" class="jump_margin"></div>
<h3>Heavily Accessed Tables</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#heavily_accessed_tables_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['heavily_accessed_tables'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "heavily_accessed_tables", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['low_density_tables']){
			$htmlString .=
<<< EOD
<div id="low_density_tables" class="jump_margin"></div>
<h3>Low Density Tables</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#low_density_tables_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['low_density_tables'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "low_density_tables", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['fragmented_tables']) {
			$htmlString .=
<<< EOD
<div id="fragmented_tables" class="jump_margin"></div>
<h3>Fragmented Tables</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#fragmented_tables_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['fragmented_tables'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "fragmented_tables", 10, true);
			}
			pg_free_result($result);
		}
	}

	/* Query Activity */
	if ($target['functions']
		|| $target['statements']) {

		$htmlString .=
<<< EOD
<div id="query_activity" class="jump_margin"></div>
<h2>Query Activity</h2>

EOD;

		if ($target['functions']) {
			$htmlString .=
<<< EOD
<div id="qa_functions" class="jump_margin"></div>
<h3>Functions</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#functions_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['functions'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "functions", 10, true);
			}
			pg_free_result($result);

		}

		if ($target['statements']) {
			$htmlString .=
<<< EOD
<div id="qa_statements" class="jump_margin"></div>
<h3>Statements</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#statements_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['statements'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "statements", 10, true);
			}
			pg_free_result($result);
		}
	}

	/* Long Transaction */
	if ($target['long_transactions']) {
		$htmlString .=
<<< EOD
<div id="long_transactions" class="jump_margin"></div>
<h2>Long Transactions</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#long_transactions_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['long_transactions'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTablePagerHTML($result, "long_transactions", 10, true);
		}
		pg_free_result($result);
	}

	/* Lock Conflicts */
	if ($target['lock_conflicts']) {
		$htmlString .=
<<< EOD
<div id="lock_conflicts" class="jump_margin"></div>
<h2>Lock Conflicts</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#lock_conflicts_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['lock_conflicts'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTablePagerHTML($result, "lock_conflicts", 10, true);
		}
		pg_free_result($result);
	}

	return $htmlString;
}

function makeActivitiesReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['checkpoint_activity']
		&& !$target['basic_statistics']
		&& !$target['io_statistics']
		&& !$target['current_replication_status']
		&& !$target['replication_delays'])
		return "";

	$htmlString =
<<< EOD
<div id="operation_activity" class="jump_margin"></div>
<h1>Operation Activity</h1>

EOD;

	/* Checkpoint Activity */
	if ($target['checkpoint_activity']) {
		$htmlString .=
<<< EOD
<div id="checkpoint_activity" class="jump_margin"></div>
<h2>Checkpoint Activity</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#checkpoint_activity_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['checkpoint_activity'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}
		// データがない場合は4番目のカラムがNULLになるため
		if (is_null(pg_fetch_result($result,0,3)) == 1) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "checkpoint_activity");
		}
		pg_free_result($result);
	}

	/* Autovacuum Activity */
	if ($target['basic_statistics']
		|| $target['io_statistics']) {

		$htmlString .=
<<< EOD
<div id="autovacuum_activity" class="jump_margin"></div>
<h2>Autovacuum Activity</h2>

EOD;

		if ($target['basic_statistics']) {
			$htmlString .=
<<< EOD
<div id="basic_statistics" class="jump_margin"></div>
<h3>Basic Statistics (Average)</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#basic_statistics_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['basic_statistics'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "basic_statistics", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['io_statistics']) {
			$htmlString .=
<<< EOD
<div id="io_statistics" class="jump_margin"></div>
<h3>I/O Statistics (Average)</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#io_statistics_dialog"></button></div>
</div>


EOD;
			if ($target['repo_version'] >= V24) {
				$result = pg_query_params($conn, $query_string['io_statistics'], $snapids);
				if (!$result) {
					return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}

				if (pg_num_rows($result) == 0) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					$htmlString .= makeTablePagerHTML($result, "io_statistics", 10, true);
				}
				pg_free_result($result);
			} else {
				$htmlString .= makeErrorTag($errorMsg['st_version'], "2.4.0");
			}
		}
	}

	/* Replication Acivity */
	if ($target['current_replication_status']
		|| $target['replication_delays'])
                	$htmlString .=
<<< EOD
<div id="replication_activity" class="jump_margin"></div>
<h2>Replication Activity</h2>

EOD;

	if ($target['current_replication_status']) {
		$htmlString .=
<<< EOD
<div id="current_replication_status" class="jump_margin"></div>
<h3>Current Replication Status</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#current_replication_status_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['current_replication_status'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "current_replication_status");
		}
		pg_free_result($result);
	}

	/* Replication Delays */
	if ($target['replication_delays']) {
		$htmlString .=
<<< EOD
<div id="replication_delays" class="jump_margin"></div>
<h3>Replication Delays</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#replication_delays_dialog"></button></div>
</div>

EOD;
		if ($target['repo_version'] >= V25) {
			$result = pg_query_params($conn, $query_string['replication_delays'], $snapids);
				if (!$result) {
					if ($result)
						pg_free_result($result);
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
				}
				if (pg_num_rows($result) == 0) {
					$htmlString .= makeErrorTag($errorMsg['no_result']);
				} else {
					makeTupleListForDygraphs($result, $name, $value);
					$opt = array();
					array_push($opt, "title: 'Replication Delays'");
					array_push($opt, "ylabel: 'Delaying amount (Bytes)'");
					array_push($opt, "labelsKMG2: true");

					$result2 = pg_query_params($conn, $query_string['replication_delays_get_sync_host'], array($snapids[1]));
					if (pg_num_rows($result2) != 0) {
						$syncHost = pg_fetch_result($result2, 0, 0);

						$key = array_search($syncHost." flush_delay_size", $name);
						if ($key != false) {
							$name[$key] = "[sync]".$name[$key];
							array_push($opt, "'".$name[$key]."': {strokeWidth: 3, highlightCircleSize: 5}");
						} else
							array_push($opt, "'".$syncHost." flush_delay_size': {strokeWidth: 3, highlightCircleSize: 5},");

						$key = array_search($syncHost." replay_delay_size", $name);
						if ($key != false) {
							$name[$key] = "[sync]".$name[$key];
							array_push($opt, "'".$name[$key]."': {strokeWidth: 3, highlightCircleSize: 5}");
						} else
							array_push($opt, "'".$syncHost." replay_delay_size': {strokeWidth: 3, highlightCircleSize: 5},");
					}
					pg_free_result($result2);

					$htmlString .= makeLineGraphHTML($name, $value, "replication_delays", $opt);
				}
				pg_free_result($result);
			} else {
				$htmlString .= makeErrorTag($errorMsg['st_version'], "2.5.0");
			}
		}

	return $htmlString;
}

function makeInformationReport($conn, $target, $ids, $errorMsg)
{
	global $query_string;

	if (!$target['database']
		&& !$target['schema']
		&& !$target['table']
		&& !$target['index']
		&& !$target['view']
		&& !$target['sequence']
		&& !$target['trigger']
		&& !$target['role']
		&& !$target['parameter']
		&& !$target['profiles'])
		return "";

	$htmlString =
<<< EOD
<div id="information" class="jump_margin"></div>
<h1>Information</h1>
EOD;

	/* Schema Information */
	if ($target['database']
		|| $target['schema']
		|| $target['table']
		|| $target['index']
		|| $target['view']
		|| $target['sequence']
		|| $target['trigger']) {

		$htmlString .=
<<< EOD
<div id="schema_information" class="jump_margin"></div>
<h2>Schema Information</h2>

EOD;

		if ($target['database']) {
			$htmlString .=
<<< EOD
<div id="database" class="jump_margin"></div>
<h3>Database</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#database_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}

		if ($target['schema']) {
			$htmlString .=
<<< EOD
<div id="schema" class="jump_margin"></div>
<h3>Schema</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#schema_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}

		if ($target['table']) {
			$htmlString .=
<<< EOD
<div id="table" class="jump_margin"></div>
<h3>Table</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#table_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['table'], $ids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "table", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['index']) {
			$htmlString .=
<<< EOD
<div id="index" class="jump_margin"></div>
<h3>Index</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#index_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['index'], $ids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "index", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['view']) {
			$htmlString .=
<<< EOD
<div id="view" class="jump_margin"></div>
<h3>View</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#view_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}

		if ($target['sequence']) {
			$htmlString .=
<<< EOD
<div id="sequence" class="jump_margin"></div>
<h3>Sequence</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#sequence_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}

		if ($target['trigger']) {
			$htmlString .=
<<< EOD
<div id="trigger" class="jump_margin"></div>
<h3>Trigger</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#trigger_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}
	}

	/* Setting Parameters */
	if ($target['role']
		|| $target['parameter']) {

		$htmlString .=
<<< EOD
<div id="setting_parameters" class="jump_margin"></div>
<h2>Setting Parameters</h2>

EOD;

		if ($target['role']) {
			$htmlString .=
<<< EOD
<div id="role" class="jump_margin"></div>
<h3>Role</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#role_dialog"></button></div>
</div>

EOD;

			$htmlString .= makeErrorTag($errorMsg['no_info']);
		}

		if ($target['parameter']) {
			$htmlString .=
<<< EOD
<div id="parameter" class="jump_margin"></div>
<h3>Parameter</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#parameter_dialog"></button></div>
</div>

EOD;
			if ($target['repo_version'] >= V25) {
				$result = pg_query_params($conn, $query_string['parameter2'], $ids);
			} else { 
				$result = pg_query_params($conn, $query_string['parameter'], $ids);
			}
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "parameter", 10, true);
			}
			pg_free_result($result);
		}
	}

	/* Profiles */
	if ($target['profiles']) {
		$htmlString .=
<<< EOD
<div id="profiles" class="jump_margin"></div>
<h2>Profiles</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#profiles_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['profiles'], $ids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTablePagerHTML($result, "profiles", 10, true);
		}
		pg_free_result($result);
	}

	return $htmlString;
}

function getDataTypeClass($type)
{
	switch($type) {
	case "text":
	case "timestamp":
	case "interval":
	case "name":
	case "_name":
		return "str";
	default:
		return "num";
	}
}

function makePagerHTML($id, $default)
{

	$htmlString = "<div id=\"pager_".$id."\"><form>\n<img src=\""
		.TABLESORTER_PATH."addons/pager/icons/first.png\" class=\"first\"/>\n<img src=\""
		.TABLESORTER_PATH."addons/pager/icons/prev.png\" class=\"prev\"/>\n<input type=\"text\" class=\"pagedisplay\"/>\n<img src=\""
		.TABLESORTER_PATH."addons/pager/icons/next.png\" class=\"next\"/>\n<img src=\""
		.TABLESORTER_PATH."addons/pager/icons/last.png\" class=\"last\"/>\n";

	$htmlString .= "<select class=\"pagesize\">\n";
	switch($default) {
	case 5:
		$htmlString .=
<<< EOD
<option selected="selected" value="5">5</option>
<option value="10">10</option>
<option value="20">20</option>

EOD;
		break;
	case 20:
		$htmlString .=
<<< EOD
<option value="5">5</option>
<option value="10">10</option>
<option selected="selected" value="20">20</option>
<option value="30">30</option>
<option value="50">50</option>

EOD;
		break;
	default:
		$htmlString .=
<<< EOD
<option value="5">5</option>
<option selected="selected" value="10">10</option>
<option value="20">20</option>
<option value="30">30</option>

EOD;
	}
	$htmlString .= "</select>\n";

	return $htmlString."</form></div>\n";
}

function makeTableHTML($result, $id)
{
	$htmlString = "<div><table id=\"".$id."_table\" class=\"tablesorter table\">\n<thead></thead>\n<tbody>\n";
	for ($i = 0 ; $i < pg_num_fields($result) ; $i++) {
		$htmlString .= "<tr><th>".htmlspecialchars(pg_field_name($result, $i), ENT_QUOTES)."</th>";
		for ($j = 0 ; $j < pg_num_rows($result) ; $j++ )
			$htmlString .="<td class=\""
						.getDataTypeClass(pg_field_type($result, $i))
						."\">".htmlspecialchars(pg_fetch_result($result, $j, $i), ENT_QUOTES)."</td>";
		$htmlString .= "</tr>\n";
	}

	return $htmlString."</tbody>\n</table></div>\n";
}

function makeTablePagerHTML($result, $id, $default, $pagerOn)
{
	$htmlString = "<div><table id=\"".$id."_table\" class=\"tablesorter\">\n<thead><tr>\n";

	for ($i = 0 ; $i < pg_num_fields($result) ; $i++) {
		$htmlString .= "<th>".htmlspecialchars(pg_field_name($result, $i), ENT_QUOTES)."</th>";
	}

	$htmlString .= "\n</tr></thead>\n<tbody>\n";


	for($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
		$htmlString .= "<tr>";

		for($j = 0 ; $j < pg_num_fields($result) ; $j++ ) {
			$htmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES)."</td>";
		}

		$htmlString .= "</tr>\n";
	}

	$htmlString .= "</tbody>\n</table>\n";

	if ($pagerOn)
		$htmlString .= makePagerHTML($id, $default);

	return $htmlString."</div>\n";

}

// legend with search results
function makeLineGraphHTML($labelNames, $values, $id, $options)
{
	$htmlString = "<table><tr><td rowspan=\"2\">\n<div id=\""
		.$id."_graph\" class=\"linegraph\"></div>\n</td><td>\n<div id=\""
		.$id."_status\" class=\"labels\"></div>\n</td></tr>\n"
		."<tr><td><div class=\"graph_button\">\n<button id=\""
		.$id."_line\">checkpoint highlight switch</button>\n"
		."</div></td></tr>\n</table>\n";

	$htmlString .= "<script type=\"text/javascript\">\n";
	$htmlString .= "var ".$id."_highlight = false;\n\n";
	$htmlString .= "var ".$id." = new Dygraph(document.getElementById('"
		.$id."_graph'),[\n";

	foreach($values as $row) {
		$htmlString .= "    [new Date('".$row[0]."'), ";
		foreach($row[1] as $val)
			$htmlString .= $val.", ";
		$htmlString .= " ],\n";
	}

	$htmlString .= "  ],\n";

	/* Dygraphs options */
	$htmlString .= "  {\n    labelsDivStyles: { border: '1px solid black' } ,\n";
	$htmlString .= "    labelsDiv: document.getElementById('".$id."_status'),\n";
	$htmlString .=
<<< EOD
    labelsSeparateLines: true,
    hideOverlayOnMouseOut: false,
    legend: 'always',
    xlabel: 'Timestamp',
    yAxisLabelWidth: 70,
	animatedZooms: true,

EOD;
	foreach($options as $opt)
		$htmlString .= $opt.",\n";
	$htmlString .= "    labels: [ ";
	foreach($labelNames as $col)
		$htmlString .="\"". $col."\", ";
	$htmlString .= " ],\n".makeCheckpointSetting($id);

	return $htmlString."</script>\n";
}

// simple legend (use stacked, scale switching)
function makeSimpleLineGraphHTML($results, $id, $options, $stack, $changeScale)
{

	$htmlString = "<table>";

	$htmlString .= "<tr><td rowspan=\"2\">\n";

	$htmlString .= "<div id=\""
		.$id."_graph\" class=\"linegraph\"></div>\n</td><td>\n<div id=\""
		.$id."_status\" class=\"labels\"></div>\n</td></tr>\n";

	$htmlString .= "<tr><td><div class=\"graph_button\">\n<button id=\""
		.$id."_line\">checkpoint highlight switch</button>\n</div>";

	if ($changeScale)
		$htmlString .= "<div class=\"graph_button\">\n<button id=\""
			.$id."_scale\">change scale</button>\n</div>\n";

	$htmlString .= "</td></tr>\n</table>\n";

	$htmlString .= "<script type=\"text/javascript\">\n";
	$htmlString .= "var ".$id."_highlight = false;\n\n";
	$htmlString .= "var ".$id." = new Dygraph(document.getElementById('"
		.$id."_graph'),[\n";

	for($i = 0 ; $i < pg_num_rows($results) ; $i++) {
		$row = pg_fetch_array($results, NULL, PGSQL_NUM);
		$htmlString .= "    [new Date('".$row[0]."'), ";
		for($j = 1 ; $j < pg_num_fields($results) ; $j++)
			$htmlString .= $row[$j].", ";
		$htmlString .= " ],\n";
	}

	$htmlString .= "  ],\n";

	/* Dygraphs options */
	$htmlString .= "  {\n    labelsDivStyles: { border: '1px solid black' } ,\n";
	$htmlString .= "    labelsDiv: document.getElementById('".$id."_status'),\n";
	$htmlString .=
<<< EOD
    labelsSeparateLines: true,
    hideOverlayOnMouseOut: false,
    legend: 'always',
    xlabel: 'Timestamp',
    yAxisLabelWidth: 70,
	animatedZooms: true,

EOD;
	foreach($options as $opt)
		$htmlString .= $opt.",\n";

	$htmlString .= "    labels: [ ";
	for($i = 0 ; $i < pg_num_fields($results) ; $i++)
		$htmlString .= "\"".pg_field_name($results, $i)."\", ";
	$htmlString .= " ],\n";

	if ($changeScale)
		$htmlString .=
<<< EOD
    logscale: false,
    gridLineColor: 'rgb(196,196,196)',

EOD;

	if ($stack)
		$htmlString .=
<<< EOD
    stackedGraph: true,
    highlightCircleSize: 2,
    strokeWidth: 1,
    strokeBorderWidth: null,
    highlightSeriesOpts: {
      strokeWidth: 3,
      strokeBorderWidth: 1,
      highlightCircleSize: 5,
	},

EOD;

	return $htmlString.makeCheckpointSetting($id)."</script>\n";
}

// WAL Statistics 2-Axes Line Graph
function makeWALStatisticsGraphHTML($results)
{
	$htmlString = 
<<< EOD
<table><tr><td rowspan="2">
<div id="wal_statistics_graph" class="linegraph"></div>
</td><td>
<div id="wal_statistics_status" class="labels"></div>
</td></tr>
<tr><td><div class="graph_button">
<button id="wal_statistics_line">checkpoint highlight switch</button>
</div></td></tr>
</table>
<script type="text/javascript">
var wal_statistics_highlight = false;
var wal_statistics = new Dygraph(document.getElementById('wal_statistics_graph'),[

EOD;

	$high2ndaxes = 0;
	for($i = 0 ; $i < pg_num_rows($results) ; $i++) {
		$row = pg_fetch_array($results, NULL, PGSQL_NUM);
		$htmlString .= "    [new Date('".$row[0]."'), ";
		for($j = 1 ; $j < pg_num_fields($results) ; $j++) {
			$htmlString .= $row[$j].", ";

			// get second axes' max value
			if ($j >= 2 && $high2ndaxes < $row[$j])
				$high2ndaxes = $row[$j];
		}
		$htmlString .= " ],\n";
	}

	$htmlString .= "  ],\n";

	/* Dygraphs options */
	$htmlString .=
<<< EOD
  {
    labelsDivStyles: { border: '1px solid black' },
    labelsDiv: document.getElementById('wal_statistics_status'),
    labelsSeparateLines: true,
    hideOverlayOnMouseOut: false,
    legend: 'always',
    xlabel: 'Timestamp',
    yAxisLabelWidth: 70,
	title: 'WAL Statistics',
	ylabel: 'Size (Bytes)',
	y2label: 'Speed (Bytes/s)',
	labelsKMG2: true,
	animatedZooms: true,

EOD;

	$htmlString .= "    '".pg_field_name($results, 2)."': {axis: { } },\n";
	$htmlString .= "    axes: { y2: { valueRange: [0, ".ceil($high2ndaxes*2)." ] } } ,\n";

	$htmlString .= "    labels: [ ";
	for($i = 0 ; $i < pg_num_fields($results) ; $i++)
		$htmlString .= "\"".pg_field_name($results, $i)."\", ";
	$htmlString .= " ],\n".makeCheckpointSetting("wal_statistics");

	return $htmlString."</script>\n";

}

function makePieGraphHTML($value, $id, $title)
{

	$htmlString = "<div id=\"".$id."_pie\" class=\"piegraph\"></div>\n";

	$htmlString .= "<script type=\"text/javascript\">\n$.jqplot( \""
		.$id."_pie\",\n[[\n";

	foreach($value as $val)
		$htmlString .= "[\"".$val[0]."\", ".$val[1]."],\n";

	$htmlString .= "]], {\n     title: {\n       text:  '".$title."',\n";

	$htmlString .=
<<< EOD
       fontSize: '18px'
     },
     seriesDefaults: {
       renderer: $.jqplot.PieRenderer,
       rendererOptions: {
         showDataLabels: true,
         startAngle: '-90'
       }
     } ,
     grid: {
       drawBorder: true,
       shadow: false
     } ,
     legend: { show: true, location: 'e', fontSize: '12px', placement: 'outside', marginLeft: '20px',renderer: $.jqplot.PieLegendRenderer, rendererOptions: { numberColumns: 1 } } ,
   }
)
</script>

EOD;

	return $htmlString;
}

function makeTupleListForDygraphs($result, &$name, &$value)
{
	$name = array();
	$value = array();
	$col_array = array();

	// count target
	for ($i = 0 ; $i < pg_num_rows($result) ; $i++) {
		$col_array[pg_fetch_result($result, $i, 1)] = 1;
	}
	$col_names = array_keys($col_array);

	// set column name
	$name[0] = pg_field_name($result, 0);

	for ($i = 0 ; $i < count($col_names) ; $i++) {
		for($j = 2 ; $j < pg_num_fields($result) ; $j++ ) {
			array_push($name, $col_names[$i]." ".pg_field_name($result, $j));
		}
	}

	$col_array = array_flip($col_names);

	$snapshot_time = "";
	$tuple = array_fill(0, count($name)-1, "null"); // Fill an array with null
	for ($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
		$row = pg_fetch_array($result, $i, PGSQL_NUM);
		if ($snapshot_time != $row[0]) {
			if ($snapshot_time != "") {
				array_push($value, array($snapshot_time, $tuple));
				$tuple = array_fill(0, count($name)-1, "null");
			}
			$snapshot_time = $row[0];
		}
	  	for ($j = 2 ; $j < pg_num_fields($result) ; $j++ ) {
			$pos = $col_array[$row[1]]*(pg_num_fields($result)-2)+($j-2);
			$tuple[$pos] = $row[$j];
	  	}
	}
	array_push($value, array($snapshot_time, $tuple));

}

function makeTupleListForPieGraph($result)
{
	$value = array();
	$rowValue = array();
	$etc = 0;

	// get value from result
	for ($i = 0 ; $i < pg_num_rows($result) ; $i++)
		$rowValue[pg_fetch_result($result, $i, 0)]
			= pg_fetch_result($result, $i, 1);

	$sum = array_sum($rowValue);
	if ($sum == 0) return array();
	$threshold = $sum * 0.05;
	$count = 0;

	// data rounding (data < 5% and count > 10 => etc)
	foreach($rowValue as $key => $val)
		if ($val >= $threshold && $count < 9) {
			array_push($value, array($key, $val));
			$count++;
		} else
			$etc += $val;
	array_push($value, array("other", $etc));

	return $value;
}

function makeCheckpointSetting($id)
{
	$style = $id."_highlight";
	$html_string =
<<< EOD
	underlayCallback: function(canvas, area, g) {

EOD;
	$html_string .= "		if (".$style.") {\n";
	$html_string .=
<<< EOD
			for (i=0 ; i<checkpoint_date_list.length ; i++) {
				var bdate = new Date(checkpoint_date_list[i][0]);
				var edate = new Date(checkpoint_date_list[i][1]);

				var left = g.toDomXCoord(bdate.getTime());
				var right = g.toDomXCoord(edate.getTime());
				var width = right - left;
				if (right - left < 1)
					width = 1;

EOD;

	$html_string .= "				canvas.fillStyle = \"rgba(255, 102, 102, 1.0)\";\n";

	$html_string .=
<<< EOD
				canvas.fillRect(left, area.y, width, area.h);
			}
		}
	}
  } );

EOD;

	$html_string .= "$(\"#".$id."_line\").button().click( function() {\n";
	$html_string .= "	".$style." = !".$style.";\n";
	$html_string .= "	".$id.".updateOptions({\n		animatedZooms: true\n	});\n} );\n";

	return $html_string;
}
