/*
 * common: Javascript
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

$(function(){
	/*** header menu ***/
	// maximum window button
	$("#jquery_ui_button_arrowthick").button({
		icons: {
			primary: 'ui-icon-arrowthick-2-ne-sw',
		},
		text: false
	})
	.click(function(){
		// close or open left sidebar 
		div_flip();
	});

	$("#jquery_ui_button_top").button({
		icons: {
			primary: 'ui-icon-arrowthickstop-1-n',
		},
		text: false
	})
	.click(function(){
		$("body, html").animate({ scrollTop: 0 }, 300);
		return false;
	});

	$("ul.sf-menu").supersubs({
		minWidth: 10,
		maxWidth: 11,
		extraWidth: 1
	})
	.superfish({
		delay: 200,
		speed: 'fast'
	});
	$("ul.sf-menu").addClass('ui-widget-content ui-corner-all');


	/** left menu ***/
	// accordion in left menu
	$("#accordion").accordion({
		icons: {
			header: "ui-icon-circle-arrow-e",
			headerSelected: "ui-icon-circle-arrow-s"
		},
		// you should delete these option, because these option is non-recommended option
		// autoHeight: false,
		// navigation: true,
		heightStyle: "content",
		collapsible: true
	});

	// datepicker setting in create report
	$("#begin_date").datetimepicker({
		defaultDate: -1,
		dateFormat: "yy-mm-dd",
		onClose: function(dateText, inst){
			// format check without blank
			if (document.getElementById("begin_date").value != '') {
				// format check (yyyy-mm-dd)
				var match_result = document.getElementById("begin_date").value.match(/^\d{4}\-\d{2}\-\d{2}/);
				if (match_result == null) {
					$("#begin_date").addClass("ui-state-error");
					// clear text box
					document.getElementById("begin_date").value = "";
				} else {
					$("#begin_date").removeClass("ui-state-error");
					var endDateTextBox = $("#end_date");
					if (endDateTextBox.val() != '') {
						if (compareDate(dateText, endDateTextBox.val()) >= 0)
							endDateTextBox.val(makeDateStr(dateText, 1));
					} else {
						endDateTextBox.val(makeDateStr(dateText, 1));
					}
				}
			} else {
				$("#begin_date").removeClass("ui-state-error");
			}
		},
		onSelect: function (selectedDateTime){
			var start = $(this).datetimepicker('getDate');
			// Note: The implementation here the processing to be done
			//       in the datetimepicker originally
			$("#end_date").datetimepicker('setDate', new Date($("#end_date").val().replace(/-/g, "/")));
			$("#end_date").datetimepicker('option', 'minDate', new Date(start.getTime()));
			$("#begin_date").removeClass("ui-state-error");
			$("#end_date").removeClass("ui-state-error");
		}
	});

	$("#end_date").datetimepicker({
		defaultDate: 0,
		dateFormat: "yy-mm-dd",
		onClose: function(dateText, inst){
			// format check without blank
			if (document.getElementById("end_date").value != '') {
				// format check (yyyy-mm-dd)
				var match_result = document.getElementById("end_date").value.match(/^\d{4}\-\d{2}\-\d{2}/);
				if (match_result == null) {
					 $("#end_date").addClass("ui-state-error");
					// clear text box
					document.getElementById("end_date").value = "";
				} else {
					$("#end_date").removeClass("ui-state-error");
					var startDateTextBox = $("#begin_date");
					if (startDateTextBox.val() != '') {
						if (compareDate(startDateTextBox.val(), dateText) >= 0)
							 startDateTextBox.val(makeDateStr(dateText, -1));
					} else {
						 startDateTextBox.val(makeDateStr(dateText, -1));
					}
				}
			} else {
				$("#end_date").removeClass("ui-state-error");
			}
		},
		onSelect: function (selectedDateTime){
			var end = $(this).datetimepicker('getDate');
			// Note: The implementation here the processing to be done
			//       in the datetimepicker originally
			$("#begin_date").datetimepicker('setDate', new Date($("#begin_date").val().replace(/-/g, "/")));
			$("#begin_date").datetimepicker('option', 'maxDate', new Date(end.getTime()) );
			$("#begin_date").removeClass("ui-state-error");
			$("#end_date").removeClass("ui-state-error");
		}
	});

	$.ui.dialog.prototype._focusTabbable = function(){
		this.uiDialogTitlebarClose.focus();
	};

	$("#report_range_dialog").dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		width: 450,
		// you should delete these option, because these option is non-recommended option
		//position: ['center',300],
		buttons: {
			"Create report": function(){
				$(this).dialog("close");
			},
			Cancel: function(){
				$(this).dialog("close");
			}
		}
	});

	$("#report_range").button()
	.click(function(){
		$("#report_range_dialog").dialog("open");
	});

	$("#reload_setting").button()
	.click(function(){});


	/*** query string dialog ***/
	$(".query_string_dialog").each(function(){
		$(this).dialog({
			autoOpen: false,
			modal: true,
			buttons: {
				OK: function() {
					$(this).dialog("close");
				}
			},
			width: 800,
			maxHeight: 600
		});
	});

	/*** help dialog ***/
	$(".help_dialog").each(function(){
		$(this).dialog({
			autoOpen: false,
			modal: true,
			resizable: false,
			maxHeight: 500,
			width: 600
		});
	});
	$(".help_button").each(function(){
		$(this).button({
			icons: {
				primary: 'ui-icon-info'
			},
			text: false
		}).click(function() {
			$($(this).attr("dialog")).dialog("open");
		});
	});


	/*** message dialog ***/
	$("#message_dialog").dialog({
		title: "Error Message",
		autoOpen: true,
		modal: true,
		resizable: false,
		width: 600,
		minHeight: 100,
		buttons: {
			OK: function() {
				$(this).dialog("close");
			}
		}
	});


	/*** static function ***/
	// get right string
	function rightStr(str, len){
		return str.substr(str.length - len, len);
	};

	// make date string
	function makeDateStr(datestr, interval){
		if (datestr == '')
			return '';

		var tmpdatestr = datestr;
		while (tmpdatestr.indexOf("-", 0) != -1)
			tmpdatestr = tmpdatestr.replace("-", "/");

		var tmpdate = new Date(tmpdatestr);
		var yy = tmpdate.getFullYear();
		var mm = tmpdate.getMonth() + 1;
		var dd = tmpdate.getDate() + interval;
		var hour = tmpdate.getHours();
		var min = tmpdate.getMinutes();

		if (dd == "0") {
			// 月跨ぎ処理(endが1日を指定している場合)
			dd = "1";
			var newdate = new Date( yy + "/" + rightStr("0" + mm, 2) + "/"
							+ rightStr("0" + dd, 2) + " "
							+ rightStr("0" + hour, 2) + ":"
							+ rightStr("0" + min, 2));
			// 現在の日付から一日前を指定する
			newdate.setDate(0);
		} else {
			if (mm == "12") {
				// 12月は31日を指定する
				var ddd = "31";
			} else {
				// それ以外の月は月末となる日を取得する
				var ddd = new Date(yy, mm, 0);
			}
			// 月跨ぎ処理(beginが月末を指定している場合)
			if (dd > ddd) {
				// 月跨ぎの場合
				dd = ddd;
				var newdate = new Date( yy + "/" + rightStr("0" + mm, 2) + "/"
								+ rightStr("0" + dd, 2) + " "
								+ rightStr("0" + hour, 2) + ":"
								+ rightStr("0" + min, 2));
				// 現在の日付から一日後を指定する
				newdate.setDate(32);
			} else {
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
	};

	// compare date
	function compareDate(startdatestr, enddatestr){
		var tmpstartstr = startdatestr;
		while (tmpstartstr.indexOf("-", 0) != -1)
			tmpstartstr = tmpstartstr.replace("-", "/");
		var testStartDate = new Date(tmpstartstr);

		var tmpendstr = enddatestr;
		while (tmpendstr.indexOf("-", 0) != -1)
			tmpendstr = tmpendstr.replace("-", "/");
		var testEndDate = new Date(tmpendstr);

		if (testStartDate > testEndDate)
			return 1;
		else if (testStartDate == testEndDate)
			return 0;
		else
			return -1;
	};

	// hide left menu
	function div_flip(){
		divname = "left_menu";
		main = "contents";
		header = "header_menu";
		vflg = document.getElementById(divname).style.visibility;

		if (vflg == 'hidden') {
			document.getElementById(divname).style.visibility = 'visible';
			document.getElementById(main).style.left = '15.5%';
			document.getElementById(main).style.width = '84.5%';
			document.getElementById(header).style.left = '15%';
			document.getElementById(header).style.width = '85%';
		} else {
			document.getElementById(divname).style.visibility = 'hidden';
			document.getElementById(main).style.left = '0.5%';
			document.getElementById(main).style.width = '99.5%';
			document.getElementById(header).style.left = '0px';
			document.getElementById(header).style.width = '100%';
		}
	};


	/***  global function ***/
	// create url parameter string
	$.fn.createUrlString = function(param){
		var url_param = "";
		var first_elem = true;

		$.each(param, function(key, value){
			if (first_elem) {
				url_param += key + "=" + encodeURIComponent(value);
				first_elem = false;
			} else {
				url_param += "&" + key + "=" + encodeURIComponent(value);
			}
		});
		return url_param;
	};

	// get url parameter
	$.fn.getUrlVars = function(){
		var url_param = location.search.substring(1).split('&');
		var arg = new Object;

		for (var i = 0; url_param[i]; i++) {
			var pair = url_param[i].split('=');
			arg[pair[0]] = decodeURIComponent(pair[1]);
		}
		delete arg["reload"];
		return arg;
	};
});
