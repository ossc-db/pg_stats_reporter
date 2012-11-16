/*
 * pg_stats_reporter: Javascript
 *
 * Copyright (c) 2012, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

$(function() {
  var icons = {
    header: "ui-icon-circle-arrow-e",
    headerSelected: "ui-icon-circle-arrow-s"
  };

  // maximum window button
  $( '#jquery_ui_button_arrowthick' ) . button( {
    icons: {
      primary: 'ui-icon-arrowthick-2-ne-sw',
      },
    text: false
  } ).click(function() {
      // close or open left sidebar 
      div_flip();
    } );

  // accordion in left menu
  $( "#accordion" ).accordion( {
    icons: icons,
    autoHeight: false,
    navigation: true,
    collapsible: true
  } );

  // datepicker setting in create report
  $('#begin_date').datetimepicker({
    defaultDate: -7,
    dateFormat: "yy-mm-dd",
    onClose: function(dateText, inst) {
        // format check without blank
        if(document.getElementById("begin_date").value != ''){
            // format check (yyyy-mm-dd)
            var match_result = document.getElementById("begin_date").value.match(/^\d{4}\-\d{2}\-\d{2}/);
            if(match_result == null){
                $('#begin_date').addClass("ui-state-error");
                // clear text box
                document.getElementById("begin_date").value = "";
            }else{
                $('#begin_date').removeClass("ui-state-error");
                var endDateTextBox = $('#end_date');
                if (endDateTextBox.val() != '') {
                    if (compareDate(dateText, endDateTextBox.val()) >= 0)
		        endDateTextBox.val(makeDateStr(dateText, 1));
                }
                else {
                    endDateTextBox.val(makeDateStr(dateText, 1));
                }
            }
      }else{
          $('#begin_date').removeClass("ui-state-error");
      }
      
    },
    onSelect: function (selectedDateTime){
        var start = $(this).datetimepicker('getDate');
        $('#end_date').datetimepicker('option', 'minDate', new Date(start.getTime()));
        $('#begin_date').removeClass("ui-state-error");
        $('#end_date').removeClass("ui-state-error");
    }
  });

  $('#end_date').datetimepicker({
    defaultDate: 0,
    dateFormat: "yy-mm-dd",
    onClose: function(dateText, inst) {
        // format check without blank
        if(document.getElementById("end_date").value != ''){
             // format check (yyyy-mm-dd)
             var match_result = document.getElementById("end_date").value.match(/^\d{4}\-\d{2}\-\d{2}/);
             if(match_result == null){
                 $('#end_date').addClass("ui-state-error");
                // clear text box
                document.getElementById("end_date").value = "";
             }else{
                 $('#end_date').removeClass("ui-state-error");
                 var startDateTextBox = $('#begin_date');
                 if (startDateTextBox.val() != '') {
                     if (compareDate(startDateTextBox.val(), dateText) >= 0)
                         startDateTextBox.val(makeDateStr(dateText, -1));
                 }else {
                     startDateTextBox.val(makeDateStr(dateText, -1));
                 }
             }
        }else{
            $('#end_date').removeClass("ui-state-error");
        }
    },
    onSelect: function (selectedDateTime){
        var end = $(this).datetimepicker('getDate');
        $('#begin_date').datetimepicker('option', 'maxDate', new Date(end.getTime()) );
        $('#begin_date').removeClass("ui-state-error");
        $('#end_date').removeClass("ui-state-error");
    }
  });

  $('#report_range_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 450,
    position: ['center',300],
    buttons: {
      "Create report": function() {
		// datetimepicker blank check
		var date_b = document.getElementById("begin_date").value;
		var date_e = document.getElementById("end_date").value;
		if(date_b == ''||date_e == ''){
			if(date_b == '') $('#begin_date').addClass("ui-state-error"); 
			if(date_e == '') $('#end_date').addClass("ui-state-error"); 
		}else{
		// URLパラメータの解析
		var uparam = location.search.substring(1).split('&');
		var arg = new Object;

		// 同じ項目を複数指定されると面倒なので、連想配列に展開
		for ( i = 0 ; uparam[i] ; i++ ) {
		  var pair = uparam[i].split('=');
		  arg[pair[0]] = pair[1];
		}

		$(this).dialog("close");
		var gourl = "pg_stats_reporter.php?repodb=";

		if (("repodb" in arg) && ("instid" in arg)) {

		  // URLパラメータ作成
		  gourl += arg["repodb"] + "&instid=" + arg["instid"];
		} else {
		  // アコーディオンの先頭の情報を取得する
		  var divtag = document.getElementById("accordion").getElementsByTagName("div");
		  var atag = divtag[0].getElementsByTagName("a");

		  // hrefからrepodbとinstidを取り出す
		  var hparam = atag[0].search.substring(1).split('&');
		  var harg = new Object;
		  for ( i = 0 ; hparam[i] ; i++ ) {
		    var hpair = hparam[i].split('=');
			harg[hpair[0]] = hpair[1];
		  }

		  // URLパラメータ作成
		  gourl += harg["repodb"] + "&instid=" + harg["instid"];

		}
		gourl += "&begin=\"" + document.getElementById("begin_date").value
		  + "\"&end=\"" + document.getElementById("end_date").value + "\"";
		document.location = gourl;

		}

	  },
	  Cancel: function() {
		$(this).dialog("close");
	  }
    }
  } );

  $('#report_range')
    .button()
    .click(function() {
      $('#report_range_dialog').dialog("open");
    } );

  $('#reload_setting')
    .button()
    .click(function() {
	  document.location = "pg_stats_reporter.php?reload=1";
    } );

  $('ul.sf-menu').supersubs( {
    minWidth: 10,
    maxWidth: 11,
    extraWidth: 1
  } ).superfish( {
    delay: 200,
    speed: 'fast'
  } );
  $('ul.sf-menu').addClass('ui-widget-content ui-corner-all');


// get right string
function rightStr(str, len) {
  return str.substr(str.length - len, len);
}

// make date string
function makeDateStr(datestr, interval) {

  if (datestr == '')
	return '';

  var tmpdatestr = datestr;
  while(tmpdatestr.indexOf("-", 0) != -1)
	tmpdatestr = tmpdatestr.replace("-", "/");

  var tmpdate = new Date(tmpdatestr);
  var yy = tmpdate.getFullYear();
  var mm = tmpdate.getMonth() + 1;
  var dd = tmpdate.getDate() + interval;
  var hour = tmpdate.getHours();
  var min = tmpdate.getMinutes();

  if (dd == "0"){
	// 月跨ぎ処理(endが1日を指定している場合)
	dd = "1";
	var newdate = new Date( yy + "/" + rightStr("0" + mm, 2) + "/"
					+ rightStr("0" + dd, 2) + " "
					+ rightStr("0" + hour, 2) + ":"
					+ rightStr("0" + min, 2));
	// 現在の日付から一日前を指定する
	newdate.setDate(0);
  }else{
	if (mm == "12"){
		// 12月は31日を指定する
		var ddd = "31";
	}else{
		// それ以外の月は月末となる日を取得する
		var ddd = new Date(yy, mm, 0);
	}
	// 月跨ぎ処理(beginが月末を指定している場合)
	if (dd > ddd){
		// 月跨ぎの場合
		dd = ddd;
		var newdate = new Date( yy + "/" + rightStr("0" + mm, 2) + "/"
						+ rightStr("0" + dd, 2) + " "
						+ rightStr("0" + hour, 2) + ":"
						+ rightStr("0" + min, 2));
		// 現在の日付から一日後を指定する
		newdate.setDate(32);
	}else{
		// 月跨ぎでない場合
	        var newdate = new Date( yy + "/" + rightStr("0" + mm, 2) + "/"
						+ rightStr("0" + dd, 2) + " "
						+ rightStr("0" + hour, 2) + ":"
						+ rightStr("0" + min, 2));
	}
  }

  yy = newdate.getFullYear();
  mm = newdate.getMonth() + 1;
  dd = newdate.getDate();
  return yy + "-" + rightStr("0" + mm, 2) + "-" + rightStr("0" + dd, 2) + " "
	+ rightStr("0" + hour, 2) + ":" + rightStr("0" + min, 2);
}

// compare date
function compareDate(startdatestr, enddatestr) {

  var tmpstartstr = startdatestr;
  while(tmpstartstr.indexOf("-", 0) != -1)
	tmpstartstr = tmpstartstr.replace("-", "/");
  var testStartDate = new Date(tmpstartstr);

  var tmpendstr = enddatestr;
  while(tmpendstr.indexOf("-", 0) != -1)
	tmpendstr = tmpendstr.replace("-", "/");
  var testEndDate = new Date(tmpendstr);

  if (testStartDate > testEndDate)
	return 1;
  else if (testStartDate == testEndDate)
	return 0;
  else
	return -1;
}

// hide left menu
function div_flip() {
  divname = "left_menu";
  main = "contents";
  header = "header_menu";
  vflg = document.getElementById(divname).style.visibility;
  if (vflg == 'hidden'){
    document.getElementById(divname).style.visibility = 'visible';
    document.getElementById(main).style.left = '150px';
    document.getElementById(main).style.width = '100%';
    document.getElementById(header).style.left = '140px';
    document.getElementById(header).style.width = '100%';
  }else{
    document.getElementById(divname).style.visibility = 'hidden';
    document.getElementById(main).style.left = '10px';
    document.getElementById(main).style.width = '100%';
    document.getElementById(header).style.left = '0%';
    document.getElementById(header).style.width = '100%';
  }
}

  /*** scale change button ***/
  $( '#memory_usage_scale' ). button().click( function() {
		  setLogScale();
	  } );

  // switch scaling
  function setLogScale() {
	  val = !memory_usage.getOption('logscale');
	  memory_usage.updateOptions( { logscale: val } );
	  if (val)
		  memory_usage.updateOptions( { title: 'Memory Usage (Log Scale)' } );
	  else
		  memory_usage.updateOptions( { title: 'Memory Usage (Linear Scale)' } );

  }


  /*** tablesorter setting ***/
  // Summary
  $("#summary_table").tablesorter( {
    widthFixed: true,
    widgets: ['zebra']
  } );

  // Database Statistics
  $("#database_statistics_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
    sortList: [[1,1]],
    headers: {
	1: { sorter: "digit" },
	2: { sorter: "digit" },
	3: { sorter: "digit" },
	4: { sorter: "digit" },
	5: { sorter: "digit" },
	6: { sorter: "digit" },
	7: { sorter: "digit" },
	8: { sorter: "digit" }
    }
  } )
  .tablesorterPager( {
	container: $('#pager_database_statistics'),
	size: 5,
	positionFixed: false
  } );

  // Recovery Conflicts
  $("#recovery_conflicts_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[1,1]],
    headers: {
	 1: { sorter: "digit" },
	 2: { sorter: "digit" },
	 3: { sorter: "digit" },
	 4: { sorter: "digit" },
	 5: { sorter: "digit" }
    }
  } )
  .tablesorterPager( {
	container: $('#pager_recovery_conflicts'),
	size: 5,
	positionFixed: false
  } );

  // Instance Processes Raito
  $("#instance_processes_ratio_table").tablesorter( {
    widthFixed: true,
    headers: {
	0: { sorter: false },
	1: { sorter: false },
	2: { sorter: false },
	3: { sorter: false }
    }
  } );

  // IO Usage
  $("#io_usage_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[7,1]],
	headers: {
		2: { sorter: "digit" },
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_io_usage'),
	size: 5,
	positionFixed: false
  } );

  // Disk Usage per Tablespace
  $("#disk_usage_per_tablespace_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[5,1]],
	headers: {
		2: { sorter: "digit" },
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_disk_usage_per_tablespace'),
	size: 5,
	positionFixed: false
  } );

  // Disk Usage per Table
  $("#disk_usage_per_table_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[3,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_disk_usage_per_table'),
	size: 10,
	positionFixed: false
  } );

  // Heavily Updated tables
  $("#heavily_updated_tables_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[6,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_heavily_updated_tables'),
	size: 10,
	positionFixed: false
  } );

  // Heavily Accessed tables
  $("#heavily_accessed_tables_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[3,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_heavily_accessed_tables'),
	size: 10,
	positionFixed: false
  } );

  // Low Density Tables
  $("#low_density_tables_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[3,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_low_density_tables'),
	size: 10,
	positionFixed: false
  } );

  // Fragmented Tables
  $("#fragmented_tables_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[4,1]],
	headers: {
		4: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_fragmented_tables'),
	size: 10,
	positionFixed: false
  } );

  // Query Activity Functions
  $("#functions_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[6,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_functions'),
	size: 10,
	positionFixed: false
  } );

  // Query Activity Statements
  $("#statements_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[4,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_statements'),
	size: 10,
	positionFixed: false
  } );

  // Long Transaction
  $("#long_transactions_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[3,1]],
	headers: {
		0: { sorter: "digit" },
		3: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_long_transactions'),
	size: 10,
	positionFixed: false
  } );

  // Lock Conflicts
  $("#lock_conflicts_table").tablesorter( {
	widthFixed: true,
	widgets: ['zebra'],
	sortList: [[3,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_lock_conflicts'),
	size: 5,
	positionFixed: false
  } );

  // Checkpoint Activity
  $("#checkpoint_activity_table").tablesorter( {
    widthFixed: true
  } );

  // Autovacuum Activity(Basic Statistics)
  $("#basic_statistics_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[4,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" },
		8: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_basic_statistics'),
	size: 10,
	positionFixed: false
  } );

  // Autovacuum Activity(I/O Statistics)
  $("#io_statistics_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	sortList: [[4,1]],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_io_statistics'),
	size: 10,
	positionFixed: false
  } );

  // Replication Activity
  $("#replication_activity_table").tablesorter( {
    widthFixed: true
  } );

  // Table (Schema Information)
  $("#table_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" },
		8: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_table'),
	size: 10,
	positionFixed: false
  } );

  // Index (Schema Information)
  $("#index_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	headers: {
		3: { sorter: "digit" },
		4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" },
		8: { sorter: "digit" },
		9: { sorter: "digit" },
		10: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_index'),
	size: 10,
	positionFixed: false
  } );

  // Parameter (Setting Parameters)
  $("#parameter_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
  } )
  .tablesorterPager( {
	container: $('#pager_parameter'),
	size: 10,
	positionFixed: false
  } );

  // Profiles
  $("#profiles_table").tablesorter( {
    widthFixed: true,
	widgets: ['zebra'],
	headers: {
		1: { sorter: "digit" }
	}
  } )
  .tablesorterPager( {
	container: $('#pager_profiles'),
	size: 10,
	positionFixed: false
  } );


  /*** help dialog ***/
  // Summary help dialog button
  $( '#summary_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#summary_dialog').dialog("open");
    } );

  // Summary help dialog
  $('#summary_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  //Database Statistics help dialog button
  $( '#database_statistics_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#database_statistics_dialog').dialog("open");
    } );

  // Database Statistics help dialog
  $('#database_statistics_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Transaction Statistics help dialog button
  $( '#transaction_statistics_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#transaction_statistics_dialog').dialog("open");
    } );

  // Transaction Statistics help dialog
  $('#transaction_statistics_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Database Size help dialog button
  $( '#database_size_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#database_size_dialog').dialog("open");
    } );

  // Database Size help dialog
  $('#database_size_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Recovery Conflicts help dialog button
  $( '#recovery_conflicts_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#recovery_conflicts_dialog').dialog("open");
    } );

  // Recovery Conflicts help dialog
  $('#recovery_conflicts_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // WAL Statistics help dialog button
  $( '#wal_statistics_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#wal_statistics_dialog').dialog("open");
    } );

  // WAL Statistics help dialog
  $('#wal_statistics_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Instance Processes Ratio help dialog button
  $( '#instance_processes_ratio_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#instance_processes_ratio_dialog').dialog("open");
    } );

  // Instance Processes Ratio help dialog
  $('#instance_processes_ratio_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Instance Processes help dialog button
  $( '#instance_processes_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#instance_processes_dialog').dialog("open");
    } );

  // Instance Processes help dialog
  $('#instance_processes_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // CPU Usage help dialog button
  $( '#cpu_usage_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#cpu_usage_dialog').dialog("open");
    } );

  // CPU Usage help dialog
  $('#cpu_usage_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Load Average help dialog button
  $( '#load_average_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#load_average_dialog').dialog("open");
    } );

  // Load Average help dialog
  $('#load_average_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // IO Usage help dialog button
  $( '#io_usage_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#io_usage_dialog').dialog("open");
    } );

  // IO Usage help dialog
  $('#io_usage_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Memory Usage help dialog button
  $( '#memory_usage_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#memory_usage_dialog').dialog("open");
    } );

  // Memory Usage help dialog
  $('#memory_usage_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Disk Usage per Tablespace help dialog button
  $( '#disk_usage_per_tablespace_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#disk_usage_per_tablespace_dialog').dialog("open");
    } );

  // Disk Usage per Tablespace help dialog
  $('#disk_usage_per_tablespace_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Disk Usage per table help dialog button
  $( '#disk_usage_per_table_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#disk_usage_per_table_dialog').dialog("open");
    } );

  // Disk Usage per Table help dialog
  $('#disk_usage_per_table_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Heavily Updated Tables help dialog button
  $( '#heavily_updated_tables_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#heavily_updated_tables_dialog').dialog("open");
    } );

  // Heavily Updated Tables help dialog
  $('#heavily_updated_tables_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Heavily Accessed Tables help dialog button
  $( '#heavily_accessed_tables_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#heavily_accessed_tables_dialog').dialog("open");
    } );

  // Heavily Accessed Tables help dialog
  $('#heavily_accessed_tables_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Low Density Tables help dialog button
  $( '#low_density_tables_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#low_density_tables_dialog').dialog("open");
    } );

  // Low Density Tables help dialog
  $('#low_density_tables_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Fragumented Tables help dialog button
  $( '#fragmented_tables_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#fragmented_tables_dialog').dialog("open");
    } );

  // Fragmented Tables help dialog
  $('#fragmented_tables_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Functions help dialog button
  $( '#functions_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#functions_dialog').dialog("open");
    } );

  // Functions help dialog
  $('#functions_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Statements help dialog button
  $( '#statements_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#statements_dialog').dialog("open");
    } );

  // Statements help dialog
  $('#statements_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Long Transactions help dialog button
  $( '#long_transactions_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#long_transactions_dialog').dialog("open");
    } );

  // Long Transactions help dialog
  $('#long_transactions_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Lock Conflicts help dialog button
  $( '#lock_conflicts_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#lock_conflicts_dialog').dialog("open");
    } );

  // Lock Conflicts help dialog
  $('#lock_conflicts_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Checkpoint Activity help dialog button
  $( '#checkpoint_activity_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#checkpoint_activity_dialog').dialog("open");
    } );

  // Checkpoint Activity help dialog
  $('#checkpoint_activity_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Basic Statistics(Autovacuum Activity) help dialog button
  $( '#basic_statistics_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#basic_statistics_dialog').dialog("open");
    } );

  // Basic Statistics(Autovacuum Activity) help dialog
  $('#basic_statistics_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // I/O Statistics(Autovacuum Activity) help dialog button
  $( '#io_statistics_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#io_statistics_dialog').dialog("open");
    } );

  // I/O Statistics(Autovacuum Activity) help dialog
  $('#io_statistics_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Replication Activity help dialog button
  $( '#replication_activity_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#replication_activity_dialog').dialog("open");
    } );

  // Replication Activity help dialog
  $('#replication_activity_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Database help dialog button
  $( '#database_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#database_dialog').dialog("open");
    } );

  // Database help dialog
  $('#database_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Schema help dialog button
  $( '#schema_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#schema_dialog').dialog("open");
    } );

  // Schema help dialog
  $('#schema_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Talbe help dialog button
  $( '#table_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#table_dialog').dialog("open");
    } );

  // Table help dialog
  $('#table_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Index help dialog button
  $( '#index_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#index_dialog').dialog("open");
    } );

  // Index help dialog
  $('#index_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // View help dialog button
  $( '#view_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#view_dialog').dialog("open");
    } );

  // View help dialog
  $('#view_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Sequence help dialog button
  $( '#sequence_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#sequence_dialog').dialog("open");
    } );

  // Sequence help dialog
  $('#sequence_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Trigger help dialog button
  $( '#trigger_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#trigger_dialog').dialog("open");
    } );

  // Trigger help dialog
  $('#trigger_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Role help dialog button
  $( '#role_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#role_dialog').dialog("open");
    } );

  // Role help dialog
  $('#role_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Parameter help dialog button
  $( '#parameter_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#parameter_dialog').dialog("open");
    } );

  // Parameter help dialog
  $('#parameter_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );

  // Profiles help dialog button
  $( '#profiles_button_info' ) . button( {
    icons: {
      primary: 'ui-icon-info',
      },
    text: false
  } ).click(function() {
      $('#profiles_dialog').dialog("open");
    } );

  // Profiles help dialog
  $('#profiles_dialog').dialog( {
    autoOpen: false,
    modal: true,
    resizable: false,
    width: 600
  } );


});

