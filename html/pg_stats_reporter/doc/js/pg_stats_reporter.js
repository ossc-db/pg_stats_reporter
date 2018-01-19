/*
 * pg_stats_reporter: Javascript
 *
 * Copyright (c) 2012-2018, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

$(function(){
  /*** scale change button ***/
  $("#memory_usage_scale").
  button()
  .click( function(){
    setLogScale();
  });

  /*** tablesorter setting ***/
  var tablesorterDefaultOptions = {
    theme: 'blue',
    headerTemplate : '{content} {icon}',
    widthFixed: true,
    sortReset: false,
    sortRestart: false,
    emptyTo: 'bottom',
    showProcessing: true,
    widgets: [ 'zebra' ],
    widgetOptions: {
      zebra: [ 'odd', 'even' ]
    }
  };

  var pagerDefaultOptions = {
    page: 0,
    size: 10,
    output: '{page}/{totalPages}',
    fixedHeight: false
  };

  // Summary
  $("#overview_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      widgets: [ ]
    })
  );

  // Database Statistics
  $("#databases_statistics_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
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
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_databases_statistics'),
      size: 5
    })
  );

  // Recovery Conflicts
  $("#recovery_conflicts_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[1,1]],
      headers: {
        1: { sorter: "digit" },
        2: { sorter: "digit" },
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_recovery_conflicts'),
      size: 5
    })
  );

  // WAL Statistics
  $("#write_ahead_logs_stats_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      widgets: [ ]
    })
  );

  // Instance Processes Raito
  $("#backend_states_overview_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      headers: {
        0: { sorter: false },
        1: { sorter: false },
        2: { sorter: false },
        3: { sorter: false }
      }
    })
  );

  // bgwriter Statistics
  $("#bgwriter_statistics_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      widgets: [ ]
    })
  );

  // IO Usage
  $("#io_usage_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      headers: {
        2: { sorter: false },
        3: { sorter: false },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" },
        8: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_io_usage'),
      size: 5
    })
  );

  // Disk Usage per Tablespace
  $("#disk_usage_per_tablespace_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[5,1]],
      headers: {
        2: { sorter: "digit" },
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_disk_usage_per_tablespace'),
      size: 5
    })
  );

  // Disk Usage per Table
  $("#disk_usage_per_table_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[3,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_disk_usage_per_table'),
      size: 10
    })
  );

  // Heavily Updated tables
  $("#heavily_updated_tables_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[6,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_heavily_updated_tables'),
      size: 10
    })
  );

  // Heavily Accessed tables
  $("#heavily_accessed_tables_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[3,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_heavily_accessed_tables'),
      size: 10
    })
  );

  // Low Density Tables
  $("#low_density_tables_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[3,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_low_density_tables'),
      size: 10
    })
  );

  // Correlation
  $("#correlation_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[4,1]],
      headers: {
        4: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_correlation'),
      size: 10
    })
  );

  // Query Activity Functions
  $("#functions_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[6,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_functions'),
      size: 10
    })
  );

  // Query Activity Statements
  $("#statements_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[4,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_statements'),
      size: 10
    })
  );

  // Query Activity Plans
  $("#plans_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
	  cssChildRow: "tablesorter-childRow",
	  sortList: [[5,1]],
      headers: {
        0: { sorter: "digit" },
        3: { sorter: "digit" },
        4: { sorter: "digit" },
		5: { sorter: "digit" },
		6: { sorter: "digit" },
		7: { sorter: "digit" },
		8: { sorter: "digit" },
		9: { sorter: false },
		10: { sorter: false }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
	  container: $('#pager_plans'),
	  size: 10
    })
  );

  $(".childRowTable").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
	  cssChildRow: "tablesorter-childRow",
	  sortList: [[2,1]],
      headers: {
        0: { sorter: "digit" },
		1: { sorter: "digit" },
		2: { sorter: "digit" },
        3: { sorter: "digit" },
        4: { sorter: "digit" },
		5: { sorter: "digit" },
		8: { sorter: false }
      }
	})
  );

  $('.tablesorter').delegate('.toggle', 'click' ,function(){
    $(this).closest('tr').nextUntil('tr:not(.tablesorter-childRow)').find('td').toggle();

    return false;
  });

  $('.tablesorter-childRow td').hide();

  // Long Transaction
  $("#long_transactions_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[3,1]],
      headers: {
        0: { sorter: "digit" },
        3: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_long_transactions'),
      size: 10
    })
  );

  // Lock Conflicts
  $("#lock_conflicts_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[3,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_lock_conflicts'),
      size: 5
    })
  );

  // Checkpoint Activity
  $("#checkpoints_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      widgets: [ ]
    })
  );

  // Autovacuum Activity(Basic Statistics)
  $("#autovacuum_overview_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[4,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" },
        8: { sorter: "digit" },
        9: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_autovacuum_overview'),
      size: 10
    })
  );

  // Autovacuum Activity(Vacuum Cancels)
  $("#cancellations_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[0,1]]
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_cancellations'),
      size: 10
    })
  );

  // Autovacuum Activity(I/O Statistics)
  $("#autovacuum_io_summary_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[4,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_autovacuum_io_summary'),
      size: 10
    })
  );

  // Autovacuum Activity(Analyze Statistics)
  $("#analyze_overview_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[4,1]],
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_analyze_overview'),
      size: 10
    })
  );

  // Replication Activity
  $("#replication_overview_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      widgets: [ ]
    })
  );

  // Table (Schema Information)
  $("#tables_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      headers: {
        3: { sorter: "digit" },
        4: { sorter: "digit" },
        5: { sorter: "digit" },
        6: { sorter: "digit" },
        7: { sorter: "digit" },
        8: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_tables'),
      size: 10
    })
  );

  // Index (Schema Information)
  $("#indexes_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
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
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_indexes'),
      size: 10
    })
  );

  // Parameter (Setting Parameters)
  $("#runtime_params_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      headers: {
        2: { sorter: false }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_runtime_params'),
      size: 10
    })
  );

  // Alert
  $("#alerts_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      sortList: [[0,0]]
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_alerts'),
      size: 10
    })
  );

  // Profiles
  $("#profiles_table").tablesorter(
    $.extend({}, tablesorterDefaultOptions, {
      headers: {
        1: { sorter: "digit" }
      }
    })
  )
  .tablesorterPager(
    $.extend({}, pagerDefaultOptions, {
      container: $('#pager_profiles'),
      size: 10
    })
  );


  // switch scaling
  function setLogScale(){
    val = !memory_usage.getOption('logscale');
    memory_usage.updateOptions({ logscale: val });
    if (val)
      memory_usage.updateOptions({ title: 'Memory Usage (Log Scale)' });
    else
      memory_usage.updateOptions({ title: 'Memory Usage (Linear Scale)' });
  };
});
