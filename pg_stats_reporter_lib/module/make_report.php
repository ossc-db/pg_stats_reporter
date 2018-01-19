<?php
/*
 * make_report
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
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

	/* check repository version */
	if ($config[$url_param['repodb']]['repo_version'] < V10) {
		$err_msg = sprintf($error_message['st_version'], "10.x");
		return null;
	}

	/* make header menu html */
	$html_string["header_menu"] = makeHeaderMenu($config, $url_param);
	
	/* make left menu html */
	$html_string["left_menu"] = makeLeftMenu($config, $url_param);

	/* get snapshot id */
	if (!getSnapshotID($conn, $url_param, $snapids, $snapdates)) {
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
	if ($t_conf['repo_version'] < V10) {
		$err_msg = sprintf($error_message['st_version'], "10.x");
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
	$html_string["help_dialog"] = $help_message['log_viewer'];

	return $html_string;
}

/* make report for commandline mode*/
function makeReportForCommandline($conn, $infoData, $target_info, $snapids)
{
	$html_string = array();

	/* check repository version */
	if ($infoData[$target_info['repodb']]['repo_version'] < V10) {
		return null;
	}

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

	if ($targetList['overview']
		|| $targetList['alerts']) {

		$html_string .= "<li><a href=\"#overview\">Overview</a>";

		/* Alerts */
		if ($targetList['alerts']) {
			$html_string .= "<ul>\n";
			$html_string .= "<li><a href=\"#alerts\">Alerts</a></li>\n";
			$html_string .= "</ul>";
		}

		$html_string .= "</li>\n";
	}

	/* Statistics */
	if ($targetList['databases_statistics']
		|| $targetList['transactions']
		|| $targetList['database_size']
		|| $targetList['recovery_conflicts']
		|| $targetList['write_ahead_logs']
		|| $targetList['backend_states_overview']
		|| $targetList['backend_states']
    	|| $targetList['bgwriter_statistics']) {

		$html_string .= "<li><a href=\"#statistics\">Statistics</a><ul>\n";

		/* Databases Statistics */
		if ($targetList['databases_statistics']
			|| $targetList['transactions']
			|| $targetList['database_size']
			|| $targetList['recovery_conflicts']) {

			$html_string .= "<li><a href=\"#databases_statistics\">Databases Statistics</a><ul>\n";

			if ($targetList['transactions'])
				$html_string .= "<li><a href=\"#transactions\">Transactions</a></li>\n";
			if ($targetList['database_size'])
				$html_string .= "<li><a href=\"#database_size\">Database Size</a></li>\n";
			if ($targetList['recovery_conflicts'])
				$html_string .= "<li><a href=\"#recovery_conflicts\">Recovery Conflicts</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Instance Statistics */
		if ($targetList['write_ahead_logs']
			|| $targetList['backend_states_overview']
			|| $targetList['backend_states']
        	|| $targetList['bgwriter_statistics']) {

			$html_string .= "<li><a href=\"#instance_activity\">Instance Statistics</a><ul>\n";

			if ($targetList['write_ahead_logs'])
				$html_string .= "<li><a href=\"#write_ahead_logs\">Write Ahead Logs</a></li>\n";
			if ($targetList['backend_states_overview'])
				$html_string .= "<li><a href=\"#backend_states_overview\">Backend States Overview</a></li>\n";
			if ($targetList['backend_states'])
				$html_string .= "<li><a href=\"#backend_states\">Backend States</a></li>\n";
            if ($targetList['bgwriter_statistics'])
                $html_string .= "<li><a href=\"#bgwriter_statistics\">Background Writer Statistics</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		$html_string .= "</ul></li>\n";
	}

	/* OS Resource */
	if ($targetList['cpu_usage']
		|| $targetList['load_average']
		|| $targetList['io_usage']
		|| $targetList['memory_usage']
		|| $targetList['disk_usage_per_tablespace']
		|| $targetList['disk_usage_per_table']) {

		$html_string .= "<li><a href=\"#os\">OS</a><ul>\n";

		/* CPU and Memory */
		if ($targetList['cpu_usage']
			|| $targetList['load_average']
			|| $targetList['memory_usage']) {

			$html_string .= "<li><a href=\"#os_resource_usage\">CPU and Memory</a><ul>\n";

			if ($targetList['cpu_usage'])
				$html_string .= "<li><a href=\"#cpu_usage\">CPU Usage</a></li>\n";
			if ($targetList['load_average'])
				$html_string .= "<li><a href=\"#load_average\">Load Average</a></li>\n";
			if ($targetList['memory_usage'])
				$html_string .= "<li><a href=\"#memory_usage\">Memory Usage</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Disks */
		if ($targetList['disk_usage_per_tablespace']
			|| $targetList['disk_usage_per_table']
			|| $targetList['io_usage']) {

			$html_string .= "<li><a href=\"#disk_usage\">Disks</a><ul>\n";

			if ($targetList['disk_usage_per_tablespace'])
				$html_string .= "<li><a href=\"#disk_usage_per_tablespace\">Disk Usage per Tablespace</a></li>\n";
			if ($targetList['disk_usage_per_table'])
				$html_string .= "<li><a href=\"#disk_usage_per_table\">Disk Usage per Table</a></li>\n";
			if ($targetList['io_usage'])
				$html_string .= "<li><a href=\"#io_usage\">I/O Usage</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		$html_string .= "</ul></li>\n";
	}

	/* Activity */
	if ($targetList['heavily_updated_tables']
		|| $targetList['heavily_accessed_tables']
		|| $targetList['low_density_tables']
		|| $targetList['correlation']
		|| $targetList['functions']
		|| $targetList['statements']
		|| $targetList['long_transactions']
		|| $targetList['lock_conflicts']) {

		$html_string .= "<li><a href=\"#sql\">Activities</a><ul>\n";

		/* Notable Tables */
		if ($targetList['heavily_updated_tables']
			|| $targetList['heavily_accessed_tables']
			|| $targetList['low_density_tables']
			|| $targetList['correlation']) {

			$html_string .= "<li><a href=\"#notable_tables\">Notable Tables</a><ul>\n";

			if ($targetList['heavily_updated_tables'])
				$html_string .= "<li><a href=\"#heavily_updated_tables\">Heavily Updated Tables</a></li>\n";
			if ($targetList['heavily_accessed_tables'])
				$html_string .= "<li><a href=\"#heavily_accessed_tables\">Heavily Accessed Tables</a></li>\n";
			if ($targetList['low_density_tables'])
				$html_string .= "<li><a href=\"#low_density_tables\">Low Density Tables</a></li>\n";
			if ($targetList['correlation'])
				$html_string .= "<li><a href=\"#correlation\">Correlation</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Query Activity */
		if ($targetList['functions']
			|| $targetList['statements']
			|| $targetList['plans']) {
			$html_string .= "<li><a href=\"#query_activity\">Query Activity</a><ul>\n";

			if ($targetList['functions'])
				$html_string .= "<li><a href=\"#qa_functions\">Functions</a></li>\n";
			if ($targetList['statements'])
				$html_string .= "<li><a href=\"#qa_statements\">Statements</a></li>\n";
			if ($targetList['plans'])
				$html_string .= "<li><a href=\"#qa_plans\">Plans</a></li>\n";

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

	/* Maintenance */
	if ($targetList['checkpoints']
		|| $targetList['autovacuum_overview']
		|| $targetList['autovacuum_io_summary']
		|| $targetList['analyze_overview']
		|| $targetList['modified_rows']
		|| $targetList['cancellations']
		|| $targetList['replication_overview']
		|| $targetList['replication_delays']) {

		$html_string .= "<li><a href=\"#activities\">Maintenance</a><ul>\n";

		/* Checkpoints */
		if ($targetList['checkpoints'])
			$html_string .= "<li><a href=\"#checkpoints\">Checkpoints</a></li>\n";

		/* Autovacuums */
		if ($targetList['autovacuum_overview']
			|| $targetList['autovacuum_io_summary']
			|| $targetList['analyze_overview']
			|| $targetList['modified_rows']
			|| $targetList['cancellations']) {

			$html_string .= "<li><a href=\"#autovacuum_activity\">Autovacuums</a><ul>\n";

			if ($targetList['autovacuum_overview'])
				$html_string .= "<li><a href=\"#autovacuum_overview\">Overview</a></li>\n";
			if ($targetList['autovacuum_io_summary'])
				$html_string .= "<li><a href=\"#autovacuum_io_summary\">I/O Summary</a></li>\n";
			if ($targetList['analyze_overview'])
				$html_string .= "<li><a href=\"#analyze_overview\">Analyze Overview</a></li>\n";
			if ($targetList['modified_rows'])
				$html_string .= "<li><a href=\"#modified_rows\">Modified Rows</a></li>\n";
			if ($targetList['cancellations'])
				$html_string .= "<li><a href=\"#cancellations\">Cancellations</a></li>\n";

			$html_string .= "</ul></li>\n";
		}

		/* Replication */
		if ($targetList['replication_overview']
			|| $targetList['replication_delays'])

			$html_string .= "<li><a href=\"#replication_activity\">Replication</a><ul>\n";

			if($targetList['replication_overview'])
				$html_string .= "<li><a href=\"#replication_overview\">Overview</a></li>\n";
			if($targetList['replication_delays'])
				$html_string .= "<li><a href=\"#replication_delays\">Delays</a></li>\n";

			$html_string .= "</ul></li>\n";

		$html_string .= "</ul></li>\n";
	}

	/* Miscellaneous */
	if ($targetList['tables']
		|| $targetList['indexes']
		|| $targetList['runtime_params']
		|| $targetList['profiles']) {

		$html_string .= "<li><a href=\"#information\">Misc</a><ul>\n";

		/* Tables and Indexes */
		if ($targetList['tables']
			|| $targetList['indexes']) {

			$html_string .= "<li><a href=\"#schema_information\">Tables and Indexes</a><ul>\n";
			if ($targetList['tables'])
				$html_string .= "<li><a href=\"#tables\">Tables</a></li>\n";
			if ($targetList['indexes'])
				$html_string .= "<li><a href=\"#indexes\">Indexes</a></li>\n";

			$html_string .= "</ul></li>\n";
		}


		/* Settings */
		if ($targetList['runtime_params']) {

			$html_string .= "<li><a href=\"#setting_parameters\">Settings</a><ul>\n";

			$html_string .= "<li><a href=\"#runtime_params\">Run-time paramters</a></li>\n";

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
<!-- Log Viewer -->
<ul id="dropdown2" class="sf-menu menu">
<li><a href="#log_viewer">Log Viewer</a></li>
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
<li><a>Overview</a><ul>
  <li><a>Alerts</a><li>
</ul></li>
<li><a>Statistics</a><ul>
  <li><a>Databases Statistics</a><ul>
    <li><a>Transactions</a></li>
    <li><a>Database Size</a></li>
    <li><a>Recovery Conflicts</a></li>
  </ul></li>
  <li><a>Instance Statistics</a><ul>
    <li><a>Write Ahead Logs</a></li>
    <li><a>Backend States Overview</a></li>
    <li><a>Backend States</a></li>
    <li><a>Background Writer Statistics</a></li>
  </ul></li>
</ul></li>
<li><a>OS</a><ul>
  <li><a>CPU and Memory</a><ul>
    <li><a>CPU Usage</a></li>
    <li><a>Load Average</a></li>
    <li><a>Memory Usage</a></li>
  </ul></li>
  <li><a>Disks</a><ul>
    <li><a>Disk Usage per Tablespace</a></li>
    <li><a>Disk Usage per Table</a></li>
    <li><a>I/O Usage</a></li>
  </ul></li>
</ul></li>
<li><a>Activities</a><ul>
  <li><a>Notable Tables</a><ul>
    <li><a>Heavily Updated Tables</a></li>
    <li><a>Heavily Accessed Tables</a></li>
    <li><a>Low Density Tables</a></li>
    <li><a>Correlation</a></li>
  </ul></li>
  <li><a>Query Activity</a><ul>
    <li><a>Functions</a></li>
    <li><a>Statements</a></li>
    <li><a>Plans</a></li>
  </ul></li>
  <li><a>Long Transactions</a></li>
  <li><a>Lock Conflicts</a></li>
</ul></li>
<li><a>Maintenance</a><ul>
  <li><a>Checkpoints</a></li>
  <li><a>Autovacuums</a><ul>
    <li><a>Overview</a></li>
    <li><a>I/O Summary</a></li>
    <li><a>Analyze Overview</a></li>
    <li><a>Modified Rows</a></li>
    <li><a>Cancellations</a></li>
  </ul></li>
  <li><a>Replication</a><ul>
    <li><a>Overview</a></li>
    <li><a>Delays</a></li>
  </ul></li>
</ul></li>
<li><a>Misc</a><ul>
  <li><a>Tables and Indexes</a><ul>
    <li><a>Tables</a></li>
    <li><a>Indexes</a></li>
  </ul></li>
  <li><a>Settings</a><ul>
    <li><a>Run-time Parameters</a></li>
  </ul></li>
  <li><a>Profiles</a></li>
</ul></li>
</ul>

EOD;
	if (!empty($_SERVER['DOCUMENT_ROOT'])) {
		$html_string .=
<<< EOD
<!-- Log Viewer -->
<ul id="dropdown2" class="sf-menu menu">
  <li><a>Log Viewer</a></li>
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
		$html_string .= "[<span id=\"target_repodb\">" . htmlspecialchars($targetInfo['repodb'], ENT_QUOTES) . "</span>]<br/>";
		$html_string .= "<span id=\"target_name\">" . htmlspecialchars($targetName, ENT_QUOTES) . "</span><br/>";
		$html_string .= "begin:<br/><span id=\"target_begin\">" . $begin_date . "</span><br/>";
		$html_string .= "end:<br/><span id=\"target_end\">" . $end_date . "</span><br/>";
		$html_string .= "<span id=\"target_instid\" style=\"display: none\">" . $targetInfo['instid'] . "</span></p>\n";
	} else {
		$html_string .= "<p class=\"report_data\">\n[ --- ]<br/>---<br/>begin: ---<br/>end: ---<br/>\n</p>\n";
	}

	/* make date value for input tag */
	$begin_date_val = date('Y-m-d', time() - 24*60*60)." 00:00";
	$end_date_val = date('Y-m-d H:i');

	/* change report range button */
	$html_string .=
<<< EOD
<div id="report_range_dialog" title="Create new report">
  <p align="center">
    <label for="begin_date">begin:</label>
    <input type="text" id="begin_date" name="begin_date" value="$begin_date_val"/>
	<label for="end_date">end:</label>
    <input type="text" id="end_date" name="end_date" value="$end_date_val"/>
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
	global $fullquery_string;
	global $help_message;
	global $error_message;
	global $query_string;

	$targetData = $infoData[$targetInfo['repodb']];

    /* リポジトリのバージョン情報は5桁の数値しか入らないため数値型に強制設定 */
    settype($targetData['repo_version'], "integer");

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

	/* Report Overview */
	$html_string .= makeSummaryReport($conn, $targetData, $snapids, $error_message);

	/* Statistics */
	$html_string .= makeDatabaseSystemReport($conn, $targetData, $snapids, $error_message);

	/* OS Resources */
	$html_string .= makeOperatingSystemReport($conn, $targetData, $snapids, $error_message);

	/* Activities */
	$html_string .= makeSQLReport($conn, $targetData, $snapids, $error_message);

	/* Maintenance */
	$html_string .= makeActivitiesReport($conn, $targetData, $snapids, $error_message);

	/* Miscellaneous */
	$html_string .= makeInformationReport($conn, $targetData, $snapids, $error_message);

	/* full query string dialog */
	if (count($fullquery_string) != 0) {
		$html_string .= "\n<!-- full query string dialog -->\n";
		foreach($fullquery_string as $query)
			$html_string .= $query;
	}

	/* help dialog */
	$html_string .= "\n<!-- help dialog -->\n";
	foreach($help_message as $msg)
		$html_string .= $msg;

	/* Contents Footer */
	$html_string .=
<<< EOD
<hr/>
<p align="right">End&nbsp;of&nbsp;report</p>

</div> <!-- contents end -->

EOD;

	return $html_string;
}

/* Report Overview */
function makeSummaryReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['overview']
		&& !$target['alerts'])
		return "";

	$htmlString =
<<< EOD

<div id="overview" class="jump_margin"></div>
<h1>Report Overview</h1>

EOD;

	if ($target['overview']) {
		$htmlString .=
<<< EOD
<div align="right" class="jquery_ui_button_info_h1">
  <div><button class="help_button" dialog="#overview_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['overview'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}
	
		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "overview");
		}
		pg_free_result($result);
	}

	if ($target['alerts']) {
		$htmlString .=
<<< EOD

<div id="alerts" class="jump_margin"></div>
<h2>Alerts</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#alerts_dialog"></button></div>
</div>

EOD;
		$result = pg_query_params($conn, $query_string['alerts'], $snapids);
        if (!$result) {
            return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
        }
	
        if (pg_num_rows($result) == 0) {
            $htmlString .= makeErrorTag($errorMsg['no_result']);
        } else {
            $htmlString .= makeTablePagerHTML($result, "alerts", 10, true);
        }
        pg_free_result($result);
	}

	return $htmlString;
}

/* Statistics */
function makeDatabaseSystemReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['databases_statistics']
		&& !$target['transactions']
		&& !$target['database_size']
		&& !$target['recovery_conflicts']
		&& !$target['write_ahead_logs']
		&& !$target['backend_states_overview']
		&& !$target['backend_states']
    	&& !$target['bgwriter_statistics'])
		return "";

	$htmlString =
<<< EOD

<div id="statistics" class="jump_margin"></div>
<h1>Statistics</h1>

EOD;

	/* Database Statistics */
	if ($target['databases_statistics']
		|| $target['transactions']
		|| $target['database_size']
		|| $target['recovery_conflicts']) {

		$htmlString .=
<<< EOD
<div id="databases_statistics" class="jump_margin"></div>
<h2>Databases Statistics</h2>

EOD;

		if ($target['databases_statistics']) {
			$htmlString .=
<<< EOD
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#databases_statistics_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['databases_statistics'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "databases_statistics", 5, true);
			}
			pg_free_result($result);

		}

		if ($target['transactions']) {
			$htmlString .=
<<< EOD
<div id="transactions" class="jump_margin"></div>
<h3>Transactions</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#transactions_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['transactions'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'Transactions'");
				array_push($opt, "ylabel: 'Transactions per second'");
				array_push($opt, "labelsKMB: true");
				$htmlString .= makeLineGraphHTML($name, $value, "transactions", $opt);
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

	/* Instance Statistics */
	if ($target['write_ahead_logs']
		|| $target['backend_states_overview']
		|| $target['backend_states']
    	|| $target['bgwriter_statistics']) {
		$htmlString .=
<<< EOD
<div id="instance_activity" class="jump_margin"></div>
<h2>Instance Statistics</h2>

EOD;

		if ($target['write_ahead_logs']) {
			$htmlString .=
<<< EOD
<div id="write_ahead_logs" class="jump_margin"></div>
<h3>Write Ahead Logs</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#write_ahead_logs_dialog"></button></div>
</div>

EOD;
			$result = pg_query_params($conn, $query_string['write_ahead_logs_stats'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }
            // データがない場合、カラムにはNULLが入っている
            if (is_null(pg_fetch_result($result,0,0)) == 1) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $htmlString .= makeTableHTML($result, "write_ahead_logs_stats");
            }
            pg_free_result($result);

            $result = pg_query_params($conn, $query_string['write_ahead_logs'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $htmlString .= makeWALStatisticsGraphHTML($result);
            }
            pg_free_result($result);
		}

		if ($target['backend_states_overview']) {
			$htmlString .=
<<< EOD
<div id="backend_states_overview" class="jump_margin"></div>
<h3>Backend States Overview</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#backend_states_overview_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['backend_states_overview'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (is_null(pg_fetch_result($result,0,0)) == 1) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "backend_states_overview", 5, false);
			}
			pg_free_result($result);
		}

		if ($target['backend_states']) {
			$htmlString .=
<<< EOD
<div id="backend_states" class="jump_margin"></div>
<h3>Backend States</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#backend_states_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['backend_states'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$opt = array();
				array_push($opt, "title: 'Backend States'");
				array_push($opt, "ylabel: 'Percent'");
				$htmlString .= makeSimpleLineGraphHTML($result, "backend_states", $opt, true, false);
			}
			pg_free_result($result);
		}

        if ($target['bgwriter_statistics']) {
            $htmlString .=
<<< EOD
<div id="bgwriter_statistics" class="jump_margin"></div>
<h3>Background Writer Statistics</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#bgwriter_statistics_dialog"></button></div>
</div>


EOD;
            $result = pg_query_params($conn, $query_string['bgwriter_statistics_overview'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }
            $htmlString .= makeTableHTML($result, "bgwriter_statistics");
            pg_free_result($result);

            $result = pg_query_params($conn, $query_string['bgwriter_statistics'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }
            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $htmlString .= makebgwriterStatisticsGraphHTML($result);
            }
            pg_free_result($result);
        }
	}

	return $htmlString;
}

/* OS Resources */
function makeOperatingSystemReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['cpu_usage']
		&& !$target['load_average']
		&& !$target['memory_usage']
		&& !$target['disk_usage_per_tablespace']
		&& !$target['disk_usage_per_table']
		&& !$target['io_usage'])
		return "";

	$htmlString =
<<< EOD
<div id="os" class="jump_margin"></div>
<h1>OS Resources</h1>

EOD;

	/* CPU and Memory */
	if ($target['cpu_usage']
		|| $target['load_average']
		|| $target['memory_usage']) {

		$htmlString .=
<<< EOD
<div id="os_resource_usage" class="jump_margin"></div>
<h2>CPU and Memory</h2>

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
				array_push($opt, "ylabel: 'Percent'");
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
			$result = pg_query_params($conn, $query_string['memory_usage'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $opt = array();
                array_push($opt, "title: 'Memory Usage (Linear Scale)'");
                array_push($opt, "ylabel: 'Bytes'");
                array_push($opt, "labelsKMG2: true");
                $htmlString .= makeSimpleLineGraphHTML($result, "memory_usage", $opt, false, true);
            }
            pg_free_result($result);
		}
	}

	/* Disks */
	if ($target['disk_usage_per_tablespace']
		|| $target['disk_usage_per_table']
		|| $target['io_usage']) {

	$htmlString .=
<<< EOD
<div id="disk_usage" class="jump_margin"></div>
<h2>Disks</h2>

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
			$qstr = "";
			$qstr = $query_string['io_usage'];

			$result = pg_query_params($conn, $qstr, $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeIOUsageTablePagerHTML($result, "io_usage", 5, true, $target['repo_version'], array_fill(0, pg_num_fields($result), false));
			}
			pg_free_result($result);

			$htmlString .= "<br/>\n";

			// I/O rate
			$result = pg_query_params($conn, $query_string['io_size'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'I/O Rate'");
				array_push($opt, "ylabel: 'I/O Rate (Bytes/s)'");
				array_push($opt, "labelsKMG2: true");
				$htmlString .= makeLineGraphHTML_childrow($name, $value, "io_size", "I/O Rate", $opt);
			}
			pg_free_result($result);

			$htmlString .= "<br/>\n";

			// I/O Peak Rate
			$result = pg_query_params($conn, $query_string['io_size_peak'], $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                makeTupleListForDygraphs($result, $name, $value);
                $opt = array();
                array_push($opt, "title: 'I/O Peak Rate per Snapshot Interval'");
                array_push($opt, "ylabel: 'I/O Peak Rate (Bytes/s)'");
                array_push($opt, "labelsKMG2: true");
                $htmlString .= makeLineGraphHTML_childrow($name, $value, "io_size_peak", "I/O Peak Rate", $opt);
            }
            pg_free_result($result);

            $htmlString .= "<br/>\n";

			// I/O Time
			$qstr = "";
			$qstr = $query_string['io_time'];

			$result = pg_query_params($conn, $qstr, $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				makeTupleListForDygraphs($result, $name, $value);
				$opt = array();
				array_push($opt, "title: 'I/O Time'");
				array_push($opt, "ylabel: 'I/O Time (%)'");
				$htmlString .= makeLineGraphHTML_childrow($name, $value, "io_time", "I/O Time", $opt);
			}
			pg_free_result($result);
		}

	}

	return $htmlString;
}

/* Activities */
function makeSQLReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['heavily_updated_tables']
		&& !$target['heavily_accessed_tables']
		&& !$target['low_density_tables']
		&& !$target['correlation']
		&& !$target['functions']
		&& !$target['statements']
		&& !$target['plans']
		&& !$target['long_transactions']
		&& !$target['lock_conflicts'])
		return "";

	$htmlString =
<<< EOD
<div id="sql" class="jump_margin"></div>
<h1>Activities</h1>

EOD;

	/* Notable Table */
	if ($target['heavily_updated_tables']
		|| $target['heavily_accessed_tables']
		|| $target['low_density_tables']
		|| $target['correlation']) {

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

		if ($target['correlation']) {
			$htmlString .=
<<< EOD
<div id="correlation" class="jump_margin"></div>
<h3>Correlation</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#correlation_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['correlation'], $snapids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "correlation", 10, true);
			}
			pg_free_result($result);
		}
	}

	/* Query Activity */
	if ($target['functions']
		|| $target['statements']
		|| $target['plans']) {

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
				$qarray = array_fill(0, pg_num_fields($result), false);
				$qarray[2] = true;
				$htmlString .= makeTablePagerHTML_impl($result, "statements", 10, true, $qarray);
			}
			pg_free_result($result);
		}

		if ($target['plans']) {

			$htmlString .=
<<< EOD
<div id="qa_plans" class="jump_margin"></div>
<h3>Plans</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#plans_dialog"></button></div>
</div>

EOD;

			$htmlString .= makePlansString($conn, $query_string, $snapids, $errorMsg);
		}
	}

	/* Long Transactions */
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
			$qarray = array_fill(0, pg_num_fields($result), false);
			$qarray[4] = true;
			$htmlString .= makeTablePagerHTML_impl($result, "long_transactions", 10, true, $qarray);
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
			$qarray = array_fill(0, pg_num_fields($result), false);
			$qarray[7] = true;
			$qarray[8] = true;
			$htmlString .= makeTablePagerHTML_impl($result, "lock_conflicts", 10, true, $qarray);
		}
		pg_free_result($result);
	}

	return $htmlString;
}

/* Maintenance */
function makeActivitiesReport($conn, $target, $snapids, $errorMsg)
{
	global $query_string;

	if (!$target['checkpoints']
		&& !$target['autovacuum_overview']
		&& !$target['autovacuum_io_summary']
		&& !$target['analyze_overview']
		&& !$target['cancellations']
		&& !$target['replication_overview']
		&& !$target['replication_delays'])
		return "";

	$htmlString =
<<< EOD
<div id="activities" class="jump_margin"></div>
<h1>Maintenances</h1>

EOD;

	/* Checkpoints */
	if ($target['checkpoints']) {
		$htmlString .=
<<< EOD
<div id="checkpoints" class="jump_margin"></div>
<h2>Checkpoints</h2>
<div align="right" class="jquery_ui_button_info_h2">
  <div><button class="help_button" dialog="#checkpoints_dialog"></button></div>
</div>

EOD;

		$result = pg_query_params($conn, $query_string['checkpoints'], $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}
		// データがない場合は4番目のカラムがNULLになるため
		if (is_null(pg_fetch_result($result,0,3)) == 1) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "checkpoints");
		}
		pg_free_result($result);
	}

	/* Autovacuums */
	if ($target['autovacuum_overview']
		|| $target['autovacuum_io_summary']
		|| $target['analyze_overview']) {

		$htmlString .=
<<< EOD
<div id="autovacuum_activity" class="jump_margin"></div>
<h2>Autovacuums</h2>

EOD;

		if ($target['autovacuum_overview']) {
			$htmlString .=
<<< EOD
<div id="autovacuum_overview" class="jump_margin"></div>
<h3>Overview</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#autovacuum_overview_dialog"></button></div>
</div>

EOD;
			$result = pg_query_params($conn, $query_string['autovacuum_overview'], $snapids);

			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "autovacuum_overview", 10, true);
			}
			pg_free_result($result);

		}

		if ($target['autovacuum_io_summary']) {
			$htmlString .=
<<< EOD
<div id="autovacuum_io_summary" class="jump_margin"></div>
<h3>I/O Summary</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#autovacuum_io_summary_dialog"></button></div>
</div>


EOD;
			$result = pg_query_params($conn, $query_string['autovacuum_io_summary'], $snapids);
			if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $htmlString .= makeTablePagerHTML($result, "autovacuum_io_summary", 10, true);
            }
            pg_free_result($result);
		}

		if ($target['analyze_overview']) {
			$htmlString .=
<<< EOD
<div id="analyze_overview" class="jump_margin"></div>
<h3>Analyze Overview</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#analyze_overview_dialog"></button></div>
</div>


EOD;
			// if repository database version >= 3.0, add last analyze time
			$qstr = "";
            $qstr = $query_string['analyze_overview'];

            $result = pg_query_params($conn, $qstr, $snapids);
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $htmlString .= makeTablePagerHTML($result, "analyze_overview", 10, true);
            }
            pg_free_result($result);
		}

		if ($target['modified_rows']) {
			$htmlString .=
<<< EOD
<div id="modified_rows" class="jump_margin"></div>
<h3>Modified Rows</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#modified_rows_dialog"></button></div>
</div>


EOD;

			$qstr = $query_string['modified_rows'];
            $result = pg_query_params($conn, $qstr, array_merge($snapids, (array)PRINT_MODIFIED_ROWS_TABLES));
            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                makeTupleListForDygraphs($result, $name, $value);
                $opt = array();
                array_push($opt, "title: 'Modified Rows'");
                array_push($opt, "ylabel: 'Modified rows (%)'");
                $htmlString .= makeLineGraphHTML($name, $value, "modified_rows", $opt);
            }
            pg_free_result($result);
		}

		if ($target['cancellations']) {
			$htmlString .=
<<< EOD
<div id="cancellations" class="jump_margin"></div>
<h3>Cancellations</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#cancellations_dialog"></button></div>
</div>


EOD;

			$result = pg_query_params($conn, $query_string['cancellations'], $snapids);

            if (!$result) {
                return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
            }

            if (pg_num_rows($result) == 0) {
                $htmlString .= makeErrorTag($errorMsg['no_result']);
            } else {
                $qarray = array_fill(0, pg_num_fields($result), false);
                $qarray[5] = true;
                $htmlString .= makeTablePagerHTML_impl($result, "cancellations", 10, true, $qarray);
            }
            pg_free_result($result);
		}
	}

	/* Replication */
	if ($target['replication_overview']
		|| $target['replication_delays'])
                	$htmlString .=
<<< EOD
<div id="replication_activity" class="jump_margin"></div>
<h2>Replication</h2>

EOD;

	if ($target['replication_overview']) {
		$htmlString .=
<<< EOD
<div id="replication_overview" class="jump_margin"></div>
<h3>Overview</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#replication_overview_dialog"></button></div>
</div>

EOD;
		$qstr = "";
		$qstr = $query_string['replication_overview'];
		$result = pg_query_params($conn, $qstr, $snapids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTableHTML($result, "replication_overview");
		}
		pg_free_result($result);
	}

	/* Replication Delays */
	if ($target['replication_delays']) {
		$htmlString .=
<<< EOD
<div id="replication_delays" class="jump_margin"></div>
<h3>Delays</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#replication_delays_dialog"></button></div>
</div>

EOD;
		$result = pg_query_params($conn, $query_string['replication_delays'], $snapids);
        if (!$result) {
            if ($result)
                pg_free_result($result);
            return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
        }
        if (pg_num_rows($result) == 0) {
            $htmlString .= makeErrorTag($errorMsg['no_result']);
        } else {
            makeTupleListForDygraphs_delays($result, $name, $value, $sync);
            $opt = array();
            array_push($opt, "title: 'Replication Delays'");
            array_push($opt, "ylabel: 'Delay (Bytes)'");
            array_push($opt, "labelsKMG2: true");

            for ($i = 0 ; $i < count($sync) ; $i++) {
                $key = array_search($sync[$i], $name);
                if ($key != false) {
                    $name[$key] = "[sync]".$name[$key];
                    array_push($opt, "'".$name[$key]."': {strokeWidth: 3, highlightCircleSize: 5}");
                }
            }

            $htmlString .= makeLineGraphHTML($name, $value, "replication_delays", $opt);
        }
        pg_free_result($result);
    }

	return $htmlString;
}

/* Miscellaneous */
function makeInformationReport($conn, $target, $ids, $errorMsg)
{
	global $query_string;

	if (!$target['tables']
		&& !$target['indexes']
		&& !$target['runtime_params']
		&& !$target['profiles'])
		return "";

	$htmlString =
<<< EOD
<div id="information" class="jump_margin"></div>
<h1>Miscellaneous</h1>
EOD;

	/* Tables and Indexes */
	if ($target['tables']
		|| $target['indexes']) {

		$htmlString .=
<<< EOD
<div id="schema_information" class="jump_margin"></div>
<h2>Tables and Indexes</h2>

EOD;

		if ($target['tables']) {
			$htmlString .=
<<< EOD
<div id="tables" class="jump_margin"></div>
<h3>Tables</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#tables_dialog"></button></div>
</div>

EOD;
			$result = pg_query_params($conn, $query_string['tables'], $ids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "tables", 10, true);
			}
			pg_free_result($result);
		}

		if ($target['indexes']) {
			$htmlString .=
<<< EOD
<div id="indexes" class="jump_margin"></div>
<h3>Indexes</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#indexes_dialog"></button></div>
</div>

EOD;

			$result = pg_query_params($conn, $query_string['indexes'], $ids);
			if (!$result) {
				return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}

			if (pg_num_rows($result) == 0) {
				$htmlString .= makeErrorTag($errorMsg['no_result']);
			} else {
				$htmlString .= makeTablePagerHTML($result, "indexes", 10, true);
			}
			pg_free_result($result);
		}
	}

	/* Settings */
	if ($target['runtime_params']) {

		$htmlString .=
<<< EOD
<div id="setting_parameters" class="jump_margin"></div>
<h2>Settings</h2>
<div id="runtime_params" class="jump_margin"></div>
<h3>Run-time parameters</h3>
<div align="right" class="jquery_ui_button_info_h3">
  <div><button class="help_button" dialog="#runtime_params_dialog"></button></div>
</div>

EOD;
		$result = pg_query_params($conn, $query_string['runtime_params'], $ids);
		if (!$result) {
			return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
		}

		if (pg_num_rows($result) == 0) {
			$htmlString .= makeErrorTag($errorMsg['no_result']);
		} else {
			$htmlString .= makeTablePagerHTML($result, "runtime_params", 10, true);
		}
		pg_free_result($result);
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

	$qarray = array_fill(0, pg_num_fields($result), false);

	return makeTablePagerHTML_impl($result, $id, $default, $pagerOn, $qarray);
}

function makeTablePagerHTML_impl($result, $id, $default, $pagerOn, $qarray)
{
	$htmlString = "<div><table id=\"".$id."_table\" class=\"tablesorter\">\n<thead><tr>\n";

	for ($i = 0 ; $i < pg_num_fields($result) ; $i++) {
		$htmlString .= "<th>".htmlspecialchars(pg_field_name($result, $i), ENT_QUOTES)."</th>";
	}

	$htmlString .= "\n</tr></thead>\n<tbody>\n";


	for($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
		$htmlString .= "<tr>";

		for($j = 0 ; $j < pg_num_fields($result) ; $j++ ) {
			$htmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">";
			if ($qarray[$j] == true) {
				$htmlString .= makeFullstringDialog($id, pg_fetch_result($result, $i, $j), true);
			} else {
				$htmlString .= htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES);
			}
			$htmlString .= "</td>";
		}

		$htmlString .= "</tr>\n";
	}

	$htmlString .= "</tbody>\n</table>\n";

	if ($pagerOn)
		$htmlString .= makePagerHTML($id, $default);

	return $htmlString."</div>\n";

}

// I/O Usage table pager HTML
function makeIOUsageTablePagerHTML($result, $id, $default, $pagerOn, $statsinfo_version, $qarray)
{
	$htmlString = "<div><table id=\"".$id."_table\" class=\"tablesorter\">\n<thead><tr>\n";

	// Be careful if you add more the number of display items
	$htmlString .= "<th rowspan=\"2\">".htmlspecialchars(pg_field_name($result, 0), ENT_QUOTES)."</th>";
	$htmlString .= "<th rowspan=\"2\">".htmlspecialchars(pg_field_name($result, 1), ENT_QUOTES)."</th>";
	$htmlString .= "<th colspan=\"3\" align=\"center\">Read</th>";
    $htmlString .= "<th colspan=\"3\" align=\"center\">Write</th>";
    $htmlString .= "<th rowspan=\"2\">".htmlspecialchars(pg_field_name($result, 8), ENT_QUOTES)."</th>";
    $htmlString .= "<th rowspan=\"2\">".htmlspecialchars(pg_field_name($result, 9), ENT_QUOTES)."</th>";
    $htmlString .= "\n</tr><tr>\n";
    $htmlString .= "<th>Total bytes (MiB)</th>";
    $htmlString .= "<th>Peak rate (KiB/s)</th>";
    $htmlString .= "<th>Total time (ms)</th>";
    $htmlString .= "<th>Total bytes (MiB)</th>";
    $htmlString .= "<th>Peak rate (KiB/s)</th>";
    $htmlString .= "<th>Total time (ms)</th>";

	$htmlString .= "\n</tr></thead>\n<tbody>\n";


	for($i = 0 ; $i < pg_num_rows($result) ; $i++ ) {
		$htmlString .= "<tr>";

		for($j = 0 ; $j < pg_num_fields($result) ; $j++ ) {
			$htmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">";
			if ($qarray[$j] == true) {
				$htmlString .= makeFullstringDialog($id, pg_fetch_result($result, $i, $j), true);
			} else {
				$htmlString .= htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES);
			}
			$htmlString .= "</td>";
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
		.$id."_line\">toggle checkpoint highlight</button>\n"
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
    xlabel: 'Time',
    axes: {
      y: {axisLabelWidth: 70}
    },
	animatedZooms: true,

EOD;
	foreach($options as $opt)
		$htmlString .= $opt.",\n";
	$htmlString .= "    labels: [ ";
	foreach($labelNames as $col)
		$htmlString .="\"". $col ."\", ";
	$htmlString .= " ],\n".makeCheckpointSetting($id);

	return $htmlString."</script>\n";
}

// legend with search results(hide line graph)
function makeLineGraphHTML_childrow($labelNames, $values, $id, $title, $options)
{
	$htmlString = "<table class=\"tablesorter\">"
		."<tr><td colspan=\"2\"><a href=\"#\" class=\"toggle\">Toggle "
		.$title." Graph</a></td></tr>"
		."<tr class=\"tablesorter-childRow\"><td rowspan=\"2\">\n<div id=\""
		.$id."_graph\" class=\"linegraph\"></div>\n</td><td>\n<div id=\""
		.$id."_status\" class=\"labels\"></div>\n</td></tr>\n"
		."<tr class=\"tablesorter-childRow\">"
		."<td><div class=\"graph_button\">\n<button id=\""
		.$id."_line\">toggle checkpoint highlight</button>\n"
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
    xlabel: 'Time',
    axes: {
      y: {axisLabelWidth: 70}
    },
	animatedZooms: true,

EOD;
	foreach($options as $opt)
		$htmlString .= $opt.",\n";
	$htmlString .= "    labels: [ ";
	foreach($labelNames as $col)
		$htmlString .="\"". $col ."\", ";
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
		.$id."_line\">toggle checkpoint highlight</button>\n</div>";

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
    xlabel: 'Time',
    axes: {
      y: {axisLabelWidth: 70}
    },
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
<div id="write_ahead_logs_graph" class="linegraph"></div>
</td><td>
<div id="write_ahead_logs_status" class="labels"></div>
</td></tr>
<tr><td><div class="graph_button">
<button id="write_ahead_logs_line">toggle checkpoint highlight</button>
</div></td></tr>
</table>
<script type="text/javascript">
var write_ahead_logs_highlight = false;
var write_ahead_logs = new Dygraph(document.getElementById('write_ahead_logs_graph'),[

EOD;

	for($i = 0 ; $i < pg_num_rows($results) ; $i++) {
		$row = pg_fetch_array($results, NULL, PGSQL_NUM);
		$htmlString .= "    [new Date('".$row[0]."'), ";
		for($j = 1 ; $j < pg_num_fields($results) ; $j++) {
			$htmlString .= $row[$j].", ";
		}
		$htmlString .= " ],\n";
	}

	$htmlString .= "  ],\n";

	/* Dygraphs options */
	$htmlString .=
<<< EOD
  {
    labelsDivStyles: { border: '1px solid black' },
    labelsDiv: document.getElementById('write_ahead_logs_status'),
    labelsSeparateLines: true,
    hideOverlayOnMouseOut: false,
    legend: 'always',
    xlabel: 'Time',
	title: 'WAL Write Rate',
	ylabel: 'Bytes per snapshot',
	y2label: 'Write rate (Bytes/s)',
	animatedZooms: true,
    axes: {
		  y: {labelsKMG2: true, axisLabelWidth: 70},
	  	 y2: {labelsKMG2: true, axisLabelWidth: 80}
	   },

EOD;

    $htmlString .= "    series : {\n";
	$htmlString .= "      '".pg_field_name($results, 2)."': {axis: 'y2' },\n";
    $htmlString .= "    },\n";
	$htmlString .= "    labels: [ ";
	for($i = 0 ; $i < pg_num_fields($results) ; $i++)
		$htmlString .= "\"".pg_field_name($results, $i)."\", ";
	$htmlString .= " ],\n".makeCheckpointSetting("write_ahead_logs");

	return $htmlString."</script>\n";

}

// bgwriter Statistics 2-Axes Line Graph
function makebgwriterStatisticsGraphHTML($results)
{
	$htmlString = 
<<< EOD
<table><tr><td rowspan="2">
<div id="bgwriter_statistics_graph" class="linegraph"></div>
</td><td>
<div id="bgwriter_statistics_status" class="labels"></div>
</td></tr>
<tr><td><div class="graph_button">
<button id="bgwriter_statistics_line">toggle checkpoint highlight</button>
</div></td></tr>
</table>
<script type="text/javascript">
var bgwriter_statistics_highlight = false;
var bgwriter_statistics = new Dygraph(document.getElementById('bgwriter_statistics_graph'),[

EOD;

	for($i = 0 ; $i < pg_num_rows($results) ; $i++) {
		$row = pg_fetch_array($results, NULL, PGSQL_NUM);
		$htmlString .= "    [new Date('".$row[0]."'), ";
		for($j = 1 ; $j < pg_num_fields($results) ; $j++) {
			$htmlString .= $row[$j].", ";
		}
		$htmlString .= " ],\n";
	}

	$htmlString .= "  ],\n";

	/* Dygraphs options */
	$htmlString .=
<<< EOD
  {
    labelsDivStyles: { border: '1px solid black' },
    labelsDiv: document.getElementById('bgwriter_statistics_status'),
    labelsSeparateLines: true,
    hideOverlayOnMouseOut: false,
    legend: 'always',
    xlabel: 'Time',
	title: 'Background Writer Statistics',
	ylabel: 'Buffer rate (buffers/s)',
	y2label: 'Frequency (s<sup>-1</sup>)',
	animatedZooms: true,
    axes: {
		  y: {axisLabelWidth: 70},
	  	 y2: {axisLabelWidth: 80}
	   },

EOD;

    $htmlString .= "    series : {\n";
	$htmlString .= "      '".pg_field_name($results, 4)."': {axis: 'y2' },\n";
	$htmlString .= "      '".pg_field_name($results, 5)."': {axis: 'y2' },\n";
    $htmlString .= "    },\n";
	$htmlString .= "    labels: [ ";
	for($i = 0 ; $i < pg_num_fields($results) ; $i++)
		$htmlString .= "\"".pg_field_name($results, $i)."\", ";
	$htmlString .= " ],\n".makeCheckpointSetting("bgwriter_statistics");

	return $htmlString."</script>\n";

}

function makePieGraphHTML($value, $id, $title)
{

	$htmlString = "<div id=\"".$id."_pie\" class=\"piegraph\"></div>\n";

	$htmlString .= "<script type=\"text/javascript\">\n$.jqplot( \""
		.$id."_pie\",\n[[\n";

	foreach($value as $val)
		$htmlString .= "[\"".htmlspecialchars($val[0], ENT_QUOTES)."\", ".htmlspecialchars($val[1], ENT_QUOTES)."],\n";

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

function makeTupleListForDygraphs_delays($result, &$name, &$value, &$sync)
{
	$name = array();
	$value = array();
	$sync = array();
	$col_array = array();
	$sync_array = array();

	// count target
	for ($i = 0 ; $i < pg_num_rows($result) ; $i++) {
		$col_array[pg_fetch_result($result, $i, 1)] = 1;
		if (pg_fetch_result($result, $i, 4) == 'sync') {
		    $sync_array[pg_fetch_result($result, $i, 1)] = 1;
		} else {
			$sync_array[pg_fetch_result($result, $i, 1)] = 0;
		}
	}
	$col_names = array_keys($col_array);

	// set column name
	$name[0] = pg_field_name($result, 0);

	for ($i = 0 ; $i < count($col_names) ; $i++) {
		for($j = 2 ; $j < pg_num_fields($result)-1 ; $j++ ) {
			array_push($name, $col_names[$i]." ".pg_field_name($result, $j));
		}
	}

	// value count : pg_num_fields($result) - (timestamp, client, sync_state)
	$value_count = pg_num_fields($result)-3;

	// set sync column name
	foreach($sync_array as $id => $val) {
		if ($val == 1) {
			for($j = 2 ; $j < pg_num_fields($result)-1 ; $j++ ) {
				array_push($sync, $id." ".pg_field_name($result, $j));
			}
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
	  	for ($j = 2 ; $j < pg_num_fields($result)-1 ; $j++ ) {
			$pos = $col_array[$row[1]]*($value_count)+($j-2);
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

function makeFullstringDialog($header, $qstr, $isQuery)
{
	global $fullquery_string;
	static $num = 0;
	$htmlSubStr = "";

	if (strlen($qstr) > PRINT_QUERY_LENGTH_LIMIT
		|| substr_count($qstr, "\n") >= PRINT_QUERY_LINE_LIMIT) {

		$dialogid = "dialog_".$header.sprintf("%05d", $num);
		$pos = 0;

		if (substr_count($qstr, "\n") >= PRINT_QUERY_LINE_LIMIT) {
			for ($i=0 ; $i<PRINT_QUERY_LINE_LIMIT ; $i++) {
				$pos = strpos($qstr, "\n", $pos)+1;
			}
		} else {
			$pos = PRINT_QUERY_LENGTH_LIMIT;
		}

		if (substr_count($qstr, "\n")) {
			// 無条件にoverflow-xを設定するが、table-layout:fixedのテーブル内
			// のpreタグのみ有効なので問題なし
			// dialogありの場合、はみ出た部分を隠す
			$htmlSubStr = "<pre style=\"overflow-x: hidden\">".htmlspecialchars(substr($qstr, 0, $pos), ENT_QUOTES)."</pre>";
		} else {
			$htmlSubStr = "<font style=\"font-family: monospace;\">".htmlspecialchars(substr($qstr, 0, $pos), ENT_QUOTES)."</font><br/>";
		}
		$htmlSubStr .= "<a href=\"javascript:void(0)\" onclick=\"$('#".$dialogid."').dialog('open');return false;\">";
		if ($isQuery) {
			$htmlSubStr .= "display full query string</a>";
			$fullquery_string[$dialogid] = "<div title=\"Query String\"";
		} else{
			$htmlSubStr .= "display full plan string</a>";
			$fullquery_string[$dialogid] = "<div title=\"Plan String\"";
		}

		$fullquery_string[$dialogid] .= " id=\"".$dialogid."\" class=\"query_string_dialog\"><font size=\"-1\">";
		if (substr_count($qstr, "\n")) {
			$fullquery_string[$dialogid] .= "<pre class=\"query_dialog\">".htmlspecialchars($qstr, ENT_QUOTES)."</pre>";
		} else {
			$fullquery_string[$dialogid] .= "<font style=\"font-family: monospace;\">".htmlspecialchars($qstr, ENT_QUOTES)."</font>";
		}
		$fullquery_string[$dialogid] .= "</font></div>\n";
		$num++;
	} else {
		if (substr_count($qstr, "\n")) {
			// 無条件にoverflow-xを設定するが、table-layout:fixedのテーブル内
			// のpreタグのみ有効なので問題なし
			// dialogなしの場合、横スクロールさせる
			$htmlSubStr = "<pre style=\"overflow-x: scroll\">".htmlspecialchars($qstr, ENT_QUOTES)."</pre>";
		} else {
			$htmlSubStr = "<font style=\"font-family: monospace;\">".htmlspecialchars($qstr, ENT_QUOTES)."</font>";
		}
	}

	return $htmlSubStr;

}
?>
