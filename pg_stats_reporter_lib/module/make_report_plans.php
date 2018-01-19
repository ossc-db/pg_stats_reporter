<?php
/*
 * make "Plans" report
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

function makePlansString($conn, $query_string, $snapids, $errorMsg) {

	global $query_string;

	$htmlString = "";

	/* get data */
	$result = pg_query_params($conn, $query_string['plans'], $snapids);
	if (!$result) {
		return $htmlString.makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
	}

	if (pg_num_rows($result) == 0) {
		$htmlString .= makeErrorTag($errorMsg['no_result']);
	} else {
		$htmlString .= makePlansTablePagerHTML($conn, $result, $snapids, $errorMsg);
	}
	pg_free_result($result);

	return $htmlString;
}

function makePlansTablePagerHTML($conn, $result, $snapids, $errorMsg) {

	global $query_string;

	$rowData = array();
	$rowDataLabel = array(
		'qid',			// queryid
		'planid',		// planid
		'uname',		// user name
		'dname',		// database name
		'callcount',	// calls
		'ttime',		// total time
		'tpcall',		// time per call (not used)
		'bread',		// block read time
		'bwrite',		// block write time
		'fcall',		// first call (not used)
		'lcall',		// last call (not used)
		'qstr',			// query
		'pparam',		// array(snapid, dbid, userid)
		'pcount');		// plan count

	$fullQueryArray = array();
	$exists_pg_store_plans = "";
	$htmlString = "";

	/* pg_store_plans check */
	$result2 = pg_query($conn, $query_string['plans_exists_store_plans']);
	if (!$result2) {
		return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
	}
	if (pg_num_rows($result2)) {
		$exists_pg_store_plans = "plans_get_plan";
	} else {
		$exists_pg_store_plans = "plans_get_plan_does_not_exist";
		$htmlString = makeErrorTag($errorMsg['no_pg_store_plans']);
	}
	pg_free_result($result2);

	$htmlString .=
<<< EOD
<div><table id="plans_table" class="tablesorter" style="table-layout:fixed;">
<thead><tr>
  <th rowspan="3">Query ID</th>
  <th>User</th>
  <th>Database</th>
  <th>Plan count</th>
  <th>Calls</th>
  <th>Total time (sec)</th>
  <th>Time/call (sec)</th>
  <th>Block read time (ms)</th>
  <th>Block write time (ms)</th>
</tr>
<tr>
  <th colspan="8">Query (child row)</th>
</tr>
<tr>
  <th colspan="8">Plan details (child row)</th>
</tr></thead>
<tbody>

EOD;

	/* query information (first row) */
	for ($k = 0 ; $k < 12 ; $k++ )
		$rowData[$rowDataLabel[$k]] = pg_fetch_result($result, 0, $k);
	$rowData[$rowDataLabel[12]] = array(pg_fetch_result($result, 0, 12), // snapid
						 pg_fetch_result($result, 0, 13), // dbid
						 pg_fetch_result($result, 0, 14), // userid
						 $rowData['planid']);
	$rowData[$rowDataLabel[13]] = 1;

	/* make child row string (first row) */
	$childHtmlString = "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$rowData['planid']."</a></td>";
	for ($j = 4 ; $j < 11 ; $j++ ) {
		$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, 0, $j), ENT_QUOTES)."</td>";
	}

	/* get plan string (first row) */
	$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $rowData['pparam']) ;
	if (!$result2) {
		return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
	}
	$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\">";
	$childHtmlString .= makeFullstringDialog('plans', pg_fetch_result($result2, 0, 0), false);
	$childHtmlString .= "</td></tr>";
	pg_free_result($result2);

	for($i = 1 ; $i < pg_num_rows($result) ; $i++ ) {
		/* get queryid, dbname, username */
		$queryid = pg_fetch_result($result, $i, 0);
		$username = pg_fetch_result($result, $i, 2);
		$dbname = pg_fetch_result($result, $i, 3);

		if ($rowData['qid'] == $queryid
			&& strcmp($rowData['uname'], $username) == 0
			&& strcmp($rowData['dname'], $dbname) == 0) {
			$rowData['planid'] = pg_fetch_result($result, $i, 1); // planid
			$rowData['callcount'] += pg_fetch_result($result, $i, 4); // add calls
			$rowData['ttime'] +=  pg_fetch_result($result, $i, 5); // add total time
			$rowData['bread'] +=  pg_fetch_result($result, $i, 7); // add block read time
			$rowData['bwrite'] +=  pg_fetch_result($result, $i, 8); // add block write time
			$rowData['pparam'] = array(pg_fetch_result($result, $i, 12), // snapid
									   pg_fetch_result($result, $i, 13), // dbid
									   pg_fetch_result($result, $i, 14), // userid
									   $rowData['planid']);
			$rowData['pcount']++;

			/* make child row string */
			$childHtmlString .= "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$rowData['planid']."</a></td>";
			for ($j = 4 ; $j < 11 ; $j++ ) {
				$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES)."</td>";
			}

			/* get plan string */
			$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $rowData['pparam']) ;
			if (!$result2) {
				return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}
			$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\">";
			$childHtmlString .= makeFullstringDialog('plans', pg_fetch_result($result2, 0, 0), false);
			$childHtmlString .= "</td></tr>";
			pg_free_result($result2);
		} else {

			/* create row */
			createPlanRow($htmlString, $childHtmlString, $rowData);

			/* data initialize */
			for ($k = 0 ; $k < 12 ; $k++ )
				$rowData[$rowDataLabel[$k]] = pg_fetch_result($result, $i, $k);
			$rowData[$rowDataLabel[12]] = array(pg_fetch_result($result, $i, 12), // snapid
												pg_fetch_result($result, $i, 13), // dbid
												pg_fetch_result($result, $i, 14), // userid
												$rowData['planid']);
			$rowData[$rowDataLabel[13]] = 1;

			/* make child row string */
			$childHtmlString = "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$rowData['planid']."</a></td>";
			for ($j = 4 ; $j < 11 ; $j++ ) {
				$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES)."</td>";
			}
			/* get plan string */
			$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $rowData['pparam']) ;
			if (!$result2) {
				return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}
			$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\">";
			$childHtmlString .= makeFullstringDialog('plans', pg_fetch_result($result2, 0, 0), false);
			$childHtmlString .= "</td></tr>";
			pg_free_result($result2);
		}
	}

	/* create row from last data */
	createPlanRow($htmlString, $childHtmlString, $rowData);

	$htmlString .= "</tbody>\n</table>\n";
	$htmlString .= makePagerHTML("plans", 10)."</div>\n";

	return $htmlString;
}

function createPlanRow(&$htmlString, $childHtmlString, $rowData)
{
	/* create row */
	$htmlString .= "<tr><td rowspan=\"3\" class=\"num\"><a href=\"#\" class=\"toggle\">".$rowData['qid']."</a></td>";
	$htmlString .="<td class=\"str\">".htmlspecialchars($rowData['uname'], ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"str\">".htmlspecialchars($rowData['dname'], ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars($rowData['pcount'], ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars($rowData['callcount'], ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($rowData['ttime'], 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format(($rowData['callcount']==0?0:$rowData['ttime']/$rowData['callcount']), 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($rowData['bread'], 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($rowData['bwrite'], 3, '.', ''), ENT_QUOTES)."</td>";

	/* query (child row) */
	$htmlString .= "</tr>\n<tr class=\"tablesorter-childRow\"><td colspan=\"8\" class=\"str\">";
	$htmlString .= makeFullstringDialog("plans", $rowData['qstr'], true);
	$htmlString .="</td></tr>\n";

	/* plan data (child row) */
	$htmlString .= "<tr class=\"tablesorter-childRow\">\n";
	$htmlString .= "<td colspan=\"8\"><table style=\"table-layout:fixed\" class=\"tablesorter childRowTable\"><thead><tr><th rowspan=\"2\">Plan ID</th><th>Calls</th><th>Total time (sec)</th><th>Time/call (sec)</th><th>Block read time (ms)</th><th>Block write time (ms)</th><th>First call</th><th>Last call</th></tr><tr><th colspan=\"7\">Plan (child row)</th></tr></thead><tbody>\n";
	$htmlString .= $childHtmlString;
	$htmlString .= "</tbody></table></td>\n";
	$htmlString .= "</tr>\n\n";
}

?>
