<?php
/*
 * make "Plans" report
 *
 * Copyright (c) 2012-2015, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
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
<div><table id="plans_table" class="tablesorter">
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
	$qid = pg_fetch_result($result, 0, 0); // queryid
	$planid = pg_fetch_result($result, 0, 1); // planid
	$uname = pg_fetch_result($result, 0, 2); // user name
	$dname = pg_fetch_result($result, 0, 3); // database name
	$pcount = 1;
	$callcount = pg_fetch_result($result, 0, 4); // calls
	$ttime =  pg_fetch_result($result, 0, 5); // total time
	$bread =  pg_fetch_result($result, 0, 7); // block read time
	$bwrite =  pg_fetch_result($result, 0, 8); // block write time
	$qstr =  pg_fetch_result($result, 0, 11); // query
	$pparam = array(pg_fetch_result($result, 0, 12), // snapid
					pg_fetch_result($result, 0, 13), // dbid
					pg_fetch_result($result, 0, 14), // userid
					$planid);
	$childHtmlString = "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$planid."</a></td>";
	for ($j = 4 ; $j < 11 ; $j++ ) {
		$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, 0, $j), ENT_QUOTES)."</td>";
	}

	/* get plan string (first row) */
	$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $pparam) ;
	if (!$result2) {
		return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
	}
	$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\"><pre>".pg_fetch_result($result2, 0, 0)."</pre></td></tr>";
	pg_free_result($result2);

	for($i = 1 ; $i < pg_num_rows($result) ; $i++ ) {
		/* get queryid, dbname, username */
		$queryid = pg_fetch_result($result, $i, 0);
		$username = pg_fetch_result($result, $i, 2);
		$dbname = pg_fetch_result($result, $i, 3);

		if ($qid == $queryid
			&& strcmp($uname, $username) == 0
			&& strcmp($dname, $dbname) == 0) {
			$planid = pg_fetch_result($result, $i, 1); // planid
			$pcount++;
			$callcount += pg_fetch_result($result, $i, 4); // calls
			$ttime +=  pg_fetch_result($result, $i, 5); // total time
			$bread +=  pg_fetch_result($result, $i, 7); // block read time
			$bwrite +=  pg_fetch_result($result, $i, 8); // block write time
			$pparam = array(pg_fetch_result($result, $i, 12), // snapid
							pg_fetch_result($result, $i, 13), // dbid
							pg_fetch_result($result, $i, 14), // userid
							$planid);
			$childHtmlString .= "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$planid."</a></td>";
			for ($j = 4 ; $j < 11 ; $j++ ) {
				$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES)."</td>";
			}
			$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $pparam) ;
			if (!$result2) {
				return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}
			$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\"><pre>".pg_fetch_result($result2, 0, 0)."</pre></td></tr>";
			pg_free_result($result2);
		} else {

			/* create row */
			$htmlString .= "<tr><td rowspan=\"3\" class=\"num\"><a href=\"#\" class=\"toggle\">".$qid."</a></td>";
			$htmlString .="<td class=\"str\">".htmlspecialchars($uname, ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"str\">".htmlspecialchars($dname, ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars($pcount, ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars($callcount, ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($ttime, 3, '.', ''), ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars(number_format(($callcount==0?0:$ttime/$callcount), 3, '.', ''), ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($bread, 3, '.', ''), ENT_QUOTES)."</td>";
			$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($bwrite, 3, '.', ''), ENT_QUOTES)."</td>";

			/* query (child row) */
			$htmlString .= "</tr>\n<tr class=\"tablesorter-childRow\"><td colspan=\"8\" class=\"str\">";
			$htmlString .= makeFullstringDialog("plans", $qstr, true);
			$htmlString .="</td></tr>\n";

			/* plan data (child row) */
			$htmlString .= "<tr class=\"tablesorter-childRow\">\n";
			$htmlString .= "<td colspan=\"8\"><table class=\"tablesorter childRowTable\"><thead><tr><th rowspan=\"2\">Plan ID</th><th>Calls</th><th>Total time (sec)</th><th>Time/call (sec)</th><th>Block read time (ms)</th><th>Block write time (ms)</th><th>First call</th><th>Last call</th></tr><tr><th colspan=\"7\">Plan (child row)</th></tr></thead><tbody>\n";
			$htmlString .= $childHtmlString;
			$htmlString .= "</tbody></table></td>\n";
			$htmlString .= "</tr>\n\n";

			/* data initialize */
			$qid = $queryid;
			$planid = pg_fetch_result($result, $i, 1); // planid
			$uname = $username;
			$dname = $dbname;
			$pcount = 1;
			$callcount = pg_fetch_result($result, $i, 4); // calls
			$ttime =  pg_fetch_result($result, $i, 5); // total time
			$bread =  pg_fetch_result($result, $i, 7); // block read time
			$bwrite =  pg_fetch_result($result, $i, 8); // block write time
			$qstr =  pg_fetch_result($result, $i, 11); // query
			$pparam = array(pg_fetch_result($result, $i, 12), // snapid
							pg_fetch_result($result, $i, 13), // dbid
							pg_fetch_result($result, $i, 14), // userid
							$planid);
			$childHtmlString = "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$planid."</a></td>";
			for ($j = 4 ; $j < 11 ; $j++ ) {
				$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i, $j), ENT_QUOTES)."</td>";
			}
			/* get plan string (first row) */
			$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $pparam) ;
			if (!$result2) {
				return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
			}
			$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\"><pre>";
			$childHtmlString .= makeFullstringDialog("plans", pg_fetch_result($result2, 0, 0), false);
			$childHtmlString .= "</pre></td></tr>";
			pg_free_result($result2);
		}
	}

	/* output last data */
	$childHtmlString = "<tr><td rowspan=\"2\" class=\"num\"><a href=\"#\" class=\"toggle\">".$planid."</a></td>";
	for ($j = 4 ; $j < 11 ; $j++ ) {
		$childHtmlString .= "<td class=\"".getDataTypeClass(pg_field_type($result, $j))."\">".htmlspecialchars(pg_fetch_result($result, $i-1, $j), ENT_QUOTES)."</td>";
	}
	/* get plan string */
	$result2 = pg_query_params($conn, $query_string[$exists_pg_store_plans], $pparam) ;
	if (!$result2) {
		return makeErrorTag($errorMsg['query_error'], pg_last_error($conn));
	}
	$childHtmlString .= "</tr><tr class=\"tablesorter-childRow\"><td colspan=\"7\" class=\"str\"><pre>".pg_fetch_result($result2, 0, 0)."</pre></td></tr>";
	pg_free_result($result2);
	$htmlString .= "<tr><td rowspan=\"3\" class=\"num\"><a href=\"#\" class=\"toggle\">".$qid."</a></td>";
	$htmlString .="<td class=\"str\">".htmlspecialchars($uname, ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"str\">".htmlspecialchars($dname, ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars($pcount, ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars($callcount, ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($ttime, 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format(($callcount==0?0:$ttime/$callcount), 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($bread, 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .="<td class=\"num\">".htmlspecialchars(number_format($bwrite, 3, '.', ''), ENT_QUOTES)."</td>";
	$htmlString .= "</tr>\n<tr class=\"tablesorter-childRow\"><td colspan=\"8\" class=\"str\">";
	$htmlString .= makeFullstringDialog("plans", $qstr, false);
	$htmlString .="</td></tr>\n";

	$htmlString .= "<tr class=\"tablesorter-childRow\">\n";
	$htmlString .= "<td colspan=\"8\"><table class=\"tablesorter childRowTable\"><thead><tr><th rowspan=\"2\">Plan ID</th><th>Calls</th><th>Total time (sec)</th><th>Time/call (sec)</th><th>Block read time (ms)</th><th>Block write time (ms)</th><th>First call</th><th>Last call</th></tr><tr><th colspan=\"7\">Plan (child row)</th></tr></thead><tbody>\n";
	$htmlString .= $childHtmlString;
	$htmlString .= "</tbody></table></td>\n";
	$htmlString .= "</tr>\n\n";

	$htmlString .= "</tbody>\n</table>\n";
	$htmlString .= makePagerHTML("plans", 10)."</div>\n";

	return $htmlString;
}

?>
