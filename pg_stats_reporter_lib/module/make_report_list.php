<?php
/*
 * pg_stats_reporter commandline mode
 * make_report_list
 *
 * Copyright (c) 2013, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

function makeReportList($dirString)
{
	/* make file list */
	$fileList = array();

	$dir = opendir($dirString);
	if (!$dir)
		die("directory(".$dirString.") open error");

	while(($entry = readdir($dir)) != false) {
		if (is_dir($entry))
			continue;

		$fileList[] = $entry;
	}

	closedir($dir);

	/* make file table list */
	$fileTableList = array();
	foreach($fileList as $fname) {
		$path_parts = pathinfo(joinPathComponents($dirString, $fname));
		if (strcmp($path_parts["extension"], "html") != 0) {
			continue;
		}

		$parts = explode("_", $path_parts["filename"]);
		if (count($parts) != 6 && count($parts) != 7) {
			continue;
		}

		list($repo, $host, $port, $inst, $bdate, $edate) = $parts;
		if (!array_key_exists($repo, $fileTableList))
			$fileTableList[$repo] = array();
		$bdatetime = new Datetime(str_replace("-", "T", $bdate));
		$edatetime = new Datetime(str_replace("-", "T", $edate));
		array_push($fileTableList[$repo],
				   array("fname" => $fname,
						 "host" => $host,
						 "port" => $port,
						 "instid" => $inst,
						 "begin" => $bdatetime->format("Y-m-d H:i"),
						 "end" => $edatetime->format("Y-m-d H:i"),
						 "term" => $bdatetime->diff($edatetime)));

	}

	makeReportListParts($fileTableList, $html_head, $html_body);

	makeReportListHTML($html_string, $html_head, $html_body);

	// output file
	if (!($fp = fopen("index.html", "w")))
		die("file open error");

	if (fwrite($fp, $html_string) == false)
		die("file write error");
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
    widthFixed: true,
    widgets: ['zebra'],
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
<script type="text/javascript" src="package/jquery-1.8.2/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="package/tablesorter/jquery.tablesorter.js"></script>
<script type="text/javascript" src="package/jquery-ui-1.9.1.custom/js/jquery-ui-1.9.1.custom.min.js"></script>

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

<link rel="stylesheet" type="text/css" href="package/jquery-ui-1.9.1.custom/development-bundle/themes/start/jquery.ui.all.css"/>
<link rel="stylesheet" type="text/css" href="package/tablesorter/themes/blue/style.css"/>
<style type="text/css">

.tablesorter tbody td.num {
	text-align: right;
	white-space: nowrap;
}
.tablesorter tbody td.str {
	text-align: left;
	white-space: normal;
	max-width: 50%;
}
.tablesorter caption { text-align:left; }
.tablesorter tbody th {
	text-align:left;
	background-color: #e6eeee;
	width: 50%;
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
