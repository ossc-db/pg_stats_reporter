<?php
/*
 * pg_stats_reporter commandline mode
 * make_report_list
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

function makeReportList($dirString)
{
	/* make file table list */
	$fileTableList = array();

	$dir = opendir($dirString);
	if (!$dir)
		elog(ERROR,"directory(%s) open error", $dirString);

	while(($entry = readdir($dir)) != false) {
		if (preg_match("/(.*)_([a-zA-Z0-9\-\.]{1,63})_([0-9]+)_([0-9]+)_([0-9]{8})-([0-9]{4})_([0-9]{8})-([0-9]{4})(|_all)\.html$/", $entry, $reportInfo)) {

			/* index and factor of reportInfo
			 * 0: report file name
			 * 1: repository DB name
			 * 2: host name
			 * 3: port number
			 * 4: instance ID
			 * 5: begin date of report
			 * 6: begin time of report
			 * 7: end date of report
			 * 8: end time of report
			 */

			if (!array_key_exists($reportInfo[1], $fileTableList))
				$fileTableList[$reportInfo[1]] = array();
			$bdatetime = new Datetime($reportInfo[5]."T".$reportInfo[6]);
			$edatetime = new Datetime($reportInfo[7]."T".$reportInfo[8]);
			array_push($fileTableList[$reportInfo[1]],
				array("fname" => $reportInfo[0],
					 "host" => $reportInfo[2],
					 "port" => $reportInfo[3],
					 "instid" => $reportInfo[4],
					 "begin" => $bdatetime->format("Y-m-d H:i"),
					 "end" => $edatetime->format("Y-m-d H:i"),
					 "term" => $bdatetime->diff($edatetime)));
		}
	}
	closedir($dir);

	if (count($fileTableList) == 0) {
		elog(WARNING, "Could not create report list: Not exist report file in '%s'", $dirString);
		exit(0);
	}

	makeReportListParts($fileTableList, $html_head, $html_body);

	makeReportListHTML($html_string, $html_head, $html_body);

	// output file
	if (!($fp = fopen(joinPathComponents($dirString, "index.html"), "w")))
		elog(ERROR, "file open error");

	if (fwrite($fp, $html_string) == false)
		elog(ERROR, "file write error");
	fclose($fp);

}

function makeReportListParts($fileTableList, &$html_head, &$html_body)
{
	$html_head = "";
	$html_body = "<ul>\n";
	$html_div = "";

	foreach($fileTableList as $repo => $flist) {

		/* set tablesorter option */
		$html_head .= "\$(\"#".$repo."\")\n";
		$html_head .=
<<< EOD
.tablesorter({
	theme: 'blue',
	headerTemplate : '{content} {icon}',
	widthFixed: true,
	widgets: ['zebra'],
	widgetOptions: {
		zebra: ['odd', 'even']
	},
	sortList: [[2,1],[3,1],[0,0]],
	headers: {
		0: { sorter: "digit" },
		1: { sorter: "text" },
		2: { sorter: "text" },
		3: { sorter: "text" },
		4: { sorter: "interval" },
		5: { sorter: "text" },
	}
});

EOD;

		$html_body .= "<li><a href=\"#tabs_".$repo."\">".$repo."</a></li>\n";
		$html_div .= "<div id=\"tabs_".$repo."\"><table id=\"".$repo
			."\" class=\"tablesorter\">\n<thead><tr>\n<th>InstID</th><th>Host:Port</th><th>begin</th><th>end</th><th>term</th><th>report file</th>\n</tr></thead>\n<tbody>\n";

		foreach($flist as $val) {
			$html_div .="<tr><td class=\"num\">".$val["instid"]
				."</td><td class=\"str\">".$val["host"].":"
				.$val["port"]."</td><td class=\"str\">".$val["begin"]
				."</td><td class=\"str\">".$val["end"]
				."</td><td class=\"num\">".$val["term"]->format("%a days %H:%I")
				."</td><td class=\"str\"><a href=\"".$val["fname"]
				."\" target=\"_blank\">"
				.$val["fname"]."</a></td></tr>\n";
		}

		$html_div .= "</tbody>\n</table></div>\n";

	}

	$html_body .= "</ul>\n".$html_div."\n";

}

function makeReportListHTML(&$html_string, $html_head, $html_body)
{

	/* make HTML */
	$html_string =
<<< EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>pg_stats_reporter report list</title>
<!-- javascripts -->

EOD;
	$html_string .= "<script type=\"text/javascript\" src=\"".JQUERY_PATH."\"></script>\n";
	$html_string .= "<script type=\"text/javascript\" src=\"".TABLESORTER_PATH."js/jquery.tablesorter.js\"></script>\n";
	$html_string .= "<script type=\"text/javascript\" src=\"".JQUERYUI_PATH."jquery-ui.min.js\"></script>\n";

	$html_string .=
<<< EOD
<script type="text/javascript">
$(document).ready(function() {

// add parser through the tablesorter addParser method 
$.tablesorter.addParser({ 
	// set a unique id 
	id: 'interval', 
	is: function(s) { 
		// return false so this parser is not auto detected 
		return false; 
	}, 
	format: function(s) { 
		// format your data for normalization 
			return $.tablesorter.formatFloat($.trim(s.replace(/ days /,0).replace(/:/,0)));
	}, 
	// set type, either numeric or text 
	type: 'numeric' 
}); 


EOD;

		$html_string .= $html_head
			."\$(\"#tabs\").tabs({hide:{effect:'fade',duration:500}});\n});\n";
		//."\$(\"#tabs\").tabs({hide:{effect:'fadeOut',duration:800}});\n});\n";

		$html_string .=
<<< EOD
</script>


EOD;

		$html_string .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".JQUERYUI_PATH."jquery-ui.min.css\"/>\n";
		$html_string .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"".TABLESORTER_PATH."css/theme.blue.css\"/>\n";
		$html_string .=
<<< EOD

<style type="text/css">

.tablesorter tbody tr {
	font-size: 8pt;
}
.tablesorter tbody td.num {
	text-align: right;
	white-space: nowrap;
}
.tablesorter tbody td.str {
	text-align: left;
	white-space: normal;
	max-width: 50%;
}

</style>
</head>

<body>
<div id="tabs">
EOD;

	$html_string .= "<h1>pg_stats_reporter report list</h1><hr/>\n"
		.$html_body."\n";

	$html_string .=
<<< EOD
</div>
</body>
</html>
EOD;

}

?>
