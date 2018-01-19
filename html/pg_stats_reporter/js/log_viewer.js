/*
 * log_viewer: Javascript
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

$(function(){
	var url_param = $.fn.getUrlVars();

	/*** header menu setting **/
	$("#dropdown li a").each(function(){
		var url_param = new Object;
		url_param["repodb"] = $("#target_repodb").text();
		url_param["instid"] = $("#target_instid").text();
		url_param["begin"] = $("#target_begin").text();
		url_param["end"] = $("#target_end").text();
		if ($(this).attr('href'))
			$(this).attr('href', "pg_stats_reporter.php?" +
					$.fn.createUrlString(url_param) + $(this).attr('href'));
	});


	/*** tablesorter setting ***/
	$("#log_viewer_table").tablesorter({
		theme: 'blue',
		headerTemplate : '{content} {icon}',
		widthFixed: false,
		sortReset: false,
		sortRestart: false,
		emptyTo: 'bottom',
		showProcessing: true,
		headers: {
			0: { sorter: 'text' },   // timestamp
			1: { sorter: 'text' },   // username
			2: { sorter: 'text' },   // database
			3: { sorter: 'digit' },  // pid
			4: { sorter: 'text' },   // client_addr
			5: { sorter: 'text' },   // session_id
			6: { sorter: 'text' },   // session_line_num
			7: { sorter: 'text' },   // ps_display
			8: { sorter: 'text' },   // session_start
			9: { sorter: 'text' },   // vxid
			10: { sorter: 'digit' }, // xid
			11: { sorter: 'text' },  // elevel
			12: { sorter: 'text' },  // sqlstate
			13: { sorter: 'text' },  // message
			14: { sorter: 'text' },  // detail
			15: { sorter: 'text' },  // hint
			16: { sorter: 'text' },  // query
			17: { sorter: 'digit' }, // query_pos
			18: { sorter: 'text' },  // context
			19: { sorter: 'text' },  // user_query
			20: { sorter: 'digit' }, // user_query_pos
			21: { sorter: 'text' },  // location
			22: { sorter: 'text' }   // application_name
		},
		sortList: [[0,0]],
		widgets: [ 'zebra', 'filter', 'columnSelector' ],
		widgetOptions: {
			// zebra options
			zebra: [ 'odd', 'even' ],
			// filter options
			filter_columnFilters: true,	
			filter_childRows: true,
			filter_cssFilter: 'tablesorter-filter',
			filter_startsWith: false,
			filter_ignoreCase: true,
			filter_reset: 'button.filter_reset',
			filter_hideFilters: false,
			filter_searchDelay: 300,
			filter_functions: {
				1: true,   // username
				2: true,   // database
				4: true,   // client_addr
				11: true,  // elevel
				12: true   // sqlstate
			},
			// columnSelector options
			columnSelector_container: $('#columnSelector'),
			columnSelector_columns: {
				0: 'disable', /* set to disabled no allowed to unselect it */
				3: false,   // pid
				5: false,   // session_id
				6: false,   // session_line_num
				7: false,   // ps_display
				8: false,   // session_start
				9: false,   // vxid
				10: false,  // xid
				15: false,  // hint
				16: false,  // query
				17: false,  // query_pos
				18: false,  // context
				19: false,  // user_query
				20: false,  // user_query_pos
				21: false,  // location
				22: false   // application_name
			},
			columnSelector_saveColumns: false,
			columnSelector_layout: '<label><input type="checkbox">{name}</label>',
			columnSelector_mediaquery: false
		}
	});
	$("#column_select").button();
	$("#filter_reset").button();


	/*** search option ***/
	$("#search").toggle(true);
	$("#search_form [name=elevel]").val(url_param["elevel"]);
	$("#search_form [name=username]").val(url_param["username"]);
	$("#search_form [name=database]").val(url_param["database"]);
	$("#search_form [name=message]").val(url_param["message"]);
	$("#search_option").button()
	.click(function(){
		$("#search").slideToggle("fast");
	});
	$("#search_submit").button()
	.click(function(){
		var param = new Object;

		param["repodb"] = url_param["repodb"];
		param["instid"] = url_param["instid"];
		param["begin"] = url_param["begin"];
		param["end"] = url_param["end"];
		$.each($("#search_form").serializeArray(), function(i, field){
			var val = $.trim(field.value);
			if (val != ""){
				param[field.name] = val;
			}
		});

		document.location = "log_viewer.php?" + $.fn.createUrlString(param);
	});
	$("#search_clear").button()
	.click(function(){
		$("#search_form").each(function(){
			this.reset();
		});
	});
});
