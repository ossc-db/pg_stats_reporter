/*
 * log_report_ajax: Javascript
 *
 * Copyright (c) 2012,2014, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

$(function(){
	var url_param = $.fn.getUrlVars();
	var page_total = parseInt($("#page_total").val());
	var page_curr = 1;

	/*** pager setting ***/
	$(".page_prev").button()
	.hide()
	.click(function(){
		pageChange(parseInt($(this).val()) - 1);
	});
	$(".page_next").button()
	.hide()
	.click(function(){
		pageChange(parseInt($(this).val()) + 1);
	});


	/*** main ***/
	/* show first page */
	if (page_total > 0) {
		$("#log_report_table").hide();
		$("#f-navi").hide();
		pageChange(1);
		$("#log_report_table").show();
		$("#f-navi").show();
	} else {
		$("#log_report_table").hide();
		$(".page_info").hide();
		$("#column_select").hide();
		$("#filter_reset").hide();
	}


	function pageChange(page){
		/* show loading message to the page information box */
		$(".page_info").html("Now Loading");

		/* create table data */
		if (!getAjaxData(page)) {
			$(".page_info").html("Page " + page_curr + " of " + page_total);
			return;
		}

		/* update the page number of the page information box */
		$(".page_info").html("Page " + page + " of " + page_total);

		/* create botton for pager */
		if (page > 1) {
			$(".page_prev").val(page);
			$(".page_prev").show();
		} else {
			$(".page_prev").hide();
		}
		if (page < page_total) {
			$(".page_next").val(page);
			$(".page_next").show();
		} else {
			$(".page_next").hide();
		}

		page_curr = page;
	}


	function getAjaxData(page){
		var req = $.ajax({
			url: "../../pg_stats_reporter_lib/module/ajax_log_data.php",
			data: $.extend({}, url_param, {page: page}),
			dataType: 'html',
			async: false,
			cache: true,
			success: outputSuccess,
			error: outputError
		});

		return req.status == 200;
	};

	function outputSuccess(data, dataType){
		$("#log_report_table tbody").html(data);
		$("#log_report_table").trigger("update");
		$("#log_report_table").trigger("filterReset");
	};

	function outputError(XMLHttpRequest, textStatus, errorThrown){
		alert(textStatus + ": " + errorThrown + "\n" + XMLHttpRequest.responseText);
	};
});
