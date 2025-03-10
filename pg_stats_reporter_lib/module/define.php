<?php
/*
 * define
 *
 * Copyright (c) 2012-2025, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// pg_stats_reporter's version
define("PROGRAM_VERSION", "17.0");

// Image File
define("IMAGE_FILE", "pgsql_banner01.png");

// Library path
define("SMARTY_PATH", "package/smarty-4.5.5/libs/");
define("JQUERY_PATH", "package/jquery-3.7.1.min.js");
define("JQUERYUI_PATH", "package/jquery-ui-1.14.1.custom/");
define("TIMEPICKER_PATH", "package/jquery-ui-timepicker-addon-1.6.3/");
define("TABLESORTER_PATH", "package/tablesorter-2.32.0/dist/");
define("SUPERFISH_PATH", "package/superfish-1.7.10/dist/");
define("JQPLOT_PATH", "package/jqPlot-1.0.9.d96a669/");
define("DYGRAPHS_PATH", "package/dygraph-2.2.1/");

// pg_statsinfo's version
define("V23", 20300);
define("V14", 140000);

// Smarty cache, compile, template directory
define("CACHE_DIR", "../../pg_stats_reporter_lib/cache");
define("COMPILE_DIR", "../../pg_stats_reporter_lib/compiled");
define("TEMPLATE_DIR", "../../pg_stats_reporter_lib/template");
define("CACHE_LIFETIME", 300);
define("TEMPLATE_FILE", "pg_stats_reporter.tpl");
define("LOG_VIEWER_TEMPLATE_FILE", "log_viewer.tpl");

// configuration file
define("CONFIG_DIR", "/etc");
define("CONFIG_CACHE_DIR", "../../pg_stats_reporter_lib/cache");
define("CONFIG_FILENAME", "pg_stats_reporter.ini");
define("CONFIG_FILE", CONFIG_DIR . "/" . CONFIG_FILENAME);
define("CONFIG_CACHE_FILE", CONFIG_CACHE_DIR . "/" . CONFIG_FILENAME);
define("GLOBAL_SECTION", "global_setting");

// message file
define("MESSAGE_PATH", "../../pg_stats_reporter_lib/message/");
define("MESSAGE_PREFIX", "message_");
define("MESSAGE_SUFFIX", ".xml");

// print query limit
define("PRINT_QUERY_LENGTH_LIMIT", 256);
define("PRINT_QUERY_LINE_LIMIT", 5);

// modified row's table count
define("PRINT_MODIFIED_ROWS_TABLES", 10);

// global setting list
$global_setting_list = array(
  'install_directory',
  'log_page_size',
);

// DB connect and language key list
$conf_key_list = array(
  'host'     => 'host',
  'port'     => 'port',
  'dbname'   => 'dbname',
  'username' => 'username',
  'password' => 'password',
  'language' => 'language'
);

// report list
$report_default = array(
  'overview'                  => true,
  'databases_statistics'      => true,
  'transactions'              => true,
  'database_size'             => true,
  'database_rusage'           => true,
  'recovery_conflicts'        => true,
  'wait_sampling_by_dbid'     => true,
  'write_ahead_logs'          => true,
  'wal_statistics'            => true,
  'backend_states_overview'   => true,
  'backend_states'            => true,
  'bgwriter_statistics'       => true,
  'wait_sampling_by_instid'   => true,
  'cpu_usage'                 => true,
  'load_average'              => true,
  'memory_usage'              => true,
  'disk_usage_per_tablespace' => true,
  'disk_usage_per_table'      => true,
  'io_usage'                  => true,
  'io_statistics'             => true,
  'heavily_updated_tables'    => true,
  'heavily_accessed_tables'   => true,
  'low_density_tables'        => true,
  'correlation'               => true,
  'functions'                 => true,
  'statements'                => true,
  'statements_rusage'         => true,
  'plans'					  => true,
  'wait_sampling'			  => true,
  'long_transactions'         => true,
  'lock_conflicts'            => true,
  'checkpoints'               => true,
  'autovacuum_overview'       => true,
  'autovacuum_io_summary'     => true,
  'vacuum_wal_statistics'     => true,
  'vacuum_index_statistics'   => true,
  'analyze_overview'          => true,
  'analyze_io_summary'        => true,
  'modified_rows'             => true,
  'cancellations'             => true,
  'replication_overview'      => true,
  'replication_delays'        => true,
  'replication_slots'         => false,
  'tables'                    => true,
  'indexes'                   => true,
  'runtime_params'            => true,
  'cpu_information'           => true,
  'memory_information'        => true,
  'alerts'                    => true,
  'profiles'                  => false
);

// help list
$help_list = array(
  'overview'                  => 'overview_dialog',
  'databases_statistics'      => 'databases_statistics_dialog',
  'transactions'              => 'transactions_dialog',
  'database_size'             => 'database_size_dialog',
  'database_rusage'           => 'database_rusage_dialog',
  'recovery_conflicts'        => 'recovery_conflicts_dialog',
  'wait_sampling_by_dbid'     => 'wait_sampling_by_dbid_dialog',
  'write_ahead_logs'          => 'write_ahead_logs_dialog',
  'wal_statistics'            => 'wal_statistics_dialog',
  'backend_states_overview'   => 'backend_states_overview_dialog',
  'backend_states'            => 'backend_states_dialog',
  'bgwriter_statistics'       => 'bgwriter_statistics_dialog',
  'wait_sampling_by_instid'   => 'wait_sampling_by_instid_dialog',
  'cpu_usage'                 => 'cpu_usage_dialog',
  'load_average'              => 'load_average_dialog',
  'memory_usage'              => 'memory_usage_dialog',
  'disk_usage_per_tablespace' => 'disk_usage_per_tablespace_dialog',
  'disk_usage_per_table'      => 'disk_usage_per_table_dialog',
  'io_usage'                  => 'io_usage_dialog',
  'io_statistics'             => 'io_statistics_dialog',
  'heavily_updated_tables'    => 'heavily_updated_tables_dialog',
  'heavily_accessed_tables'   => 'heavily_accessed_tables_dialog',
  'low_density_tables'        => 'low_density_tables_dialog',
  'correlation         '      => 'correlation_dialog',
  'functions'                 => 'functions_dialog',
  'statements'                => 'statements_dialog',
  'statements_rusage'         => 'statements_rusage_dialog',
  'plans'                     => 'plans_dialog',
  'wait_sampling'             => 'wait_sampling_dialog',
  'long_transactions'         => 'long_transactions_dialog',
  'lock_conflicts'            => 'lock_conflicts_dialog',
  'checkpoints'               => 'checkpoints_dialog',
  'autovacuum_overview'       => 'autovacuum_overview_dialog',
  'autovacuum_io_summary'     => 'autovacuum_io_summary_dialog',
  'vacuum_wal_statistics'     => 'vacuum_wal_statistics_dialog',
  'vacuum_index_statistics'   => 'vacuum_index_statistics_dialog',
  'analyze_overview'          => 'analyze_overview_dialog',
  'analyze_io_summary'        => 'analyze_io_summary_dialog',
  'modified_rows'             => 'modified_rows_dialog',
  'cancellations'             => 'cancellations_dialog',
  'replication_overview'      => 'replication_overview_dialog',
  'replication_delays'        => 'replication_delays_dialog',
  'replication_slots'         => 'replication_slots_dialog',
  'tables'                    => 'tables_dialog',
  'indexes'                   => 'indexes_dialog',
  'runtime_params'            => 'runtime_params_dialog',
  'cpu_information'           => 'cpu_information_dialog',
  'memory_information'        => 'memory_information_dialog',
  'alerts'                    => 'alerts_dialog',
  'profiles'                  => 'profiles_dialog',
  'log_viewer'                => 'log_viewer_dialog'
);

// query list
$query_string = array(
  /* checkpoint time */
  "checkpoint_time" =>
  "SELECT to_char(c.start, 'YYYY/MM/DD HH24:MI:SS') as begin, to_char(c.start + cast(c.total_duration::text as interval), 'YYYY/MM/DD HH24:MI:SS') as end FROM statsrepo.checkpoint c, (SELECT time FROM statsrepo.snapshot WHERE snapid=$2) s, (SELECT time FROM statsrepo.snapshot WHERE snapid=$3) e WHERE c.instid=$1 and c.start >= s.time and c.start < e.time",

  /* Report Overview */
  "overview" =>
  "SELECT instname AS \"Database system identifier\", hostname AS \"Host name\", port AS \"Port ID\", pg_version AS \"PostgreSQL version\", snap_begin AS \"Begins at\", snap_end AS \"Ends at\", duration AS \"Period\", total_dbsize AS \"Database size\", total_commits AS \"Number of commits\", total_rollbacks AS \"Number of rollbacks\" FROM statsrepo.get_summary($1, $2)",

  /* Statistics */
  // Databases Statistics
  "databases_statistics" =>
  "SELECT datname AS \"Database\", size AS \"MiB\", size_incr AS \"+MiB\", xact_commit_tps AS \"Commit/s\", xact_rollback_tps AS \"Rollback/s\", blks_hit_rate AS \"Hit%\", blks_hit_tps AS \"Gets/s\", blks_read_tps AS \"Reads/s\", tup_fetch_tps AS \"Rows/s\", temp_files AS \"Temporary files\", temp_bytes AS \"Temporary bytes (MiB)\",  deadlocks AS \"Deadlocks\", blk_read_time AS \"Read time (ms)\", blk_write_time AS \"Write time (ms)\" FROM statsrepo.get_dbstats($1, $2)",

  "transactions" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", datname, avg(commit_tps) AS commit_tps, avg(rollback_tps) AS rollback_tps FROM statsrepo.get_xact_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "database_size" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", datname, avg(size*1024*1024) AS size FROM statsrepo.get_dbsize_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "database_rusage" =>
  "SELECT datname AS \"Database\", plan_reads AS \"Plan reads (Bytes)\", plan_writes AS \"Plan writes (Bytes)\", plan_utime AS \"Plan user time (s)\", plan_stime AS \"Plan system time (s)\", exec_reads AS \"Execute reads (Bytes)\", exec_writes AS \"Execute writes (Bytes)\", exec_utime AS \"Execute user time (s)\", exec_stime AS \"Execute system time (s)\" FROM statsrepo.get_db_rusage_report($1, $2)",

  "recovery_conflicts" =>
  "SELECT datname AS \"Database\", confl_tablespace AS \"On tablespaces\", confl_lock AS \"On locks\", confl_snapshot AS \"On snapshots\", confl_bufferpin AS \"On bufferpins\", confl_deadlock AS \"On deadlocks\" FROM statsrepo.get_recovery_conflicts($1, $2)",

  "wait_sampling_by_dbid" =>
  "SELECT \"database\" AS \"Database\", event_type AS \"Event type\", event AS \"Event\", \"count\" AS \"Count\", ratio AS \"Ratio\", row_number AS \"Row number\" FROM statsrepo.get_wait_sampling_by_dbid($1, $2)",

  // Instance Statistics
  "write_ahead_logs" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", avg(write_size*1024*1024) AS \"Bytes/snapshot (Bytes)\", avg(write_size_per_sec*1024*1024) As \"Write rate (Bytes/s)\" FROM statsrepo.get_wal_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  "write_ahead_logs_stats" =>
  "SELECT write_total AS \"Total size (MiB)\", write_speed AS \"Average output rate (MiB/s)\", archive_total AS \"Number of archived files\", archive_failed AS \"Number of archiving errors\", last_wal_file AS \"Latest WAL file\", last_archive_file AS \"Last archived file\" FROM statsrepo.get_wal_stats($1, $2)",

  "wal_statistics" =>
  "SELECT replace(\"time\"::text, '-', '/') AS \"timestamp\", wal_fpi AS \"WAL full page images\", wal_bytes AS \"WAL bytes\", wal_buffers_full AS \"WAL buffers full\", wal_write AS \"WAL write\", wal_sync AS \"WAL sync\", wal_write_time AS \"WAL write time\", wal_sync_time AS \"WAL sync time\" FROM statsrepo.get_stat_wal2($1, $2)",

  "backend_states_overview" =>
  "SELECT idle AS \"idle (%)\", idle_in_xact AS \"idle in xact (%)\", waiting AS \"waiting (%)\", running AS \"running (%)\" FROM statsrepo.get_proc_ratio($1, $2)",

  "backend_states" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(idle) AS idle, avg(idle_in_xact) AS \"idle in xact\", avg(waiting) AS waiting, avg(running) AS running FROM statsrepo.get_proc_tendency_report($1, $2) GROUP BY 1 ORDER BY 1",

  "bgwriter_statistics_overview" =>
  "SELECT bgwriter_write_avg AS \"Written buffers by bgwriter\", bgwriter_stopscan_avg AS \"Bgwriter scans quitted earlier\", buffer_alloc_avg AS \"Allocated buffers\" FROM statsrepo.get_bgwriter_stats($1, $2)",

  "bgwriter_statistics" =>
  "SELECT replace(\"timestamp\", '-', '/'), bgwriter_write_tps AS \"Written buffers by bgwriter(L)\", buffer_alloc_tps AS \"Allocated buffers(L)\", bgwriter_stopscan_tps AS \"Bgwriter scans quitted earlier(R)\" FROM statsrepo.get_bgwriter_tendency($1, $2)",

  "wait_sampling_by_instid" =>
  "SELECT event_type AS \"Event type\", event AS \"Event\", \"count\" AS \"Count\", ratio AS \"Ratio\", row_number AS \"Row number\" FROM statsrepo.get_wait_sampling_by_instid($1, $2)",

  /* OS Resources */
  // CPU and Memory
  "cpu_usage" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(idle) AS idle, avg(iowait) AS iowait, avg(system) AS system, avg(\"user\") AS user FROM statsrepo.get_cpu_usage_tendency_report($1, $2) GROUP BY 1 ORDER BY 1",

  "load_average" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(\"1min\") AS \"1min\", avg(\"5min\") AS \"5min\", avg(\"15min\") AS \"15min\" FROM statsrepo.get_loadavg_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  "memory_usage" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(memfree*1024*1024) AS memfree, avg(buffers*1024*1024) AS buffers, avg(cached*1024*1024) AS cached, avg(swap*1024*1024) AS swap, avg(dirty*1024*1024) AS dirty FROM statsrepo.get_memory_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  // Disks
  "disk_usage_per_tablespace" =>
  "SELECT spcname AS \"Tablespace\", location AS \"Location\", device AS \"Device\", used AS \"Used (MiB)\", avail AS \"Avail (MiB)\", remain AS \"Remain (%)\" FROM statsrepo.get_disk_usage_tablespace($1, $2)",

  "disk_usage_per_table" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", size AS \"Size (MiB)\", table_reads AS \"Table reads\", index_reads AS \"Index reads\", toast_reads AS \"Toast reads\" FROM statsrepo.get_disk_usage_table($1, $2)",

  "table_size" =>
  "SELECT e.database || '.' || e.schema || '.' || e.table, e.size/1024/1024 AS \"MiB\" FROM statsrepo.tables e WHERE e.snapid = $1 ORDER BY 2 DESC LIMIT 15",

  "disk_read" =>
  "SELECT e.database || '.' || e.schema || '.' || e.table, statsrepo.sub(e.heap_blks_read, b.heap_blks_read) + statsrepo.sub(e.idx_blks_read, b.idx_blks_read) + statsrepo.sub(e.toast_blks_read, b.toast_blks_read) + statsrepo.sub(e.tidx_blks_read, b.tidx_blks_read) FROM statsrepo.tables e LEFT JOIN statsrepo.table b ON e.tbl = b.tbl AND e.nsp = b.nsp AND e.dbid = b.dbid AND b.snapid = $1 WHERE e.snapid = $2 ORDER BY 2 DESC limit 15",

  /* Activities */
  "io_usage" =>
  "SELECT device_name AS \"Device name\", device_tblspaces AS \"Containing table spaces\", total_read AS \"total read (MiB)\", read_size_tps_peak AS \"peak read\", total_read_time AS \"total read time (ms)\", total_write AS \"total write (MiB)\", write_size_tps_peak AS \"peak write\", total_write_time AS \"total write time (ms)\", io_queue AS \"Average I/O queue\", total_io_time AS \"Total I/O time (ms)\" FROM statsrepo.get_io_usage($1, $2)",

  "io_size" =>
  "SELECT replace(\"timestamp\", '-', '/'), device_name, avg(read_size_tps*1024) AS read, avg(write_size_tps*1024) AS write FROM statsrepo.get_io_usage_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "io_size_peak" =>
  "SELECT replace(\"timestamp\", '-', '/'), device_name, avg(read_size_tps_peak*1024) AS read, avg(write_size_tps_peak*1024) AS write FROM statsrepo.get_io_usage_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "io_time" =>
  "SELECT replace(\"timestamp\", '-', '/'), device_name, avg(read_time_rate) AS \"avg read time\", avg(write_time_rate) AS \"avg write time\" FROM statsrepo.get_io_usage_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "io_statistics1" =>
  "SELECT replace(\"time\", '-', '/') AS \"Time\", backend_type AS \"backend type\", object, context, reads, read_time AS \"read time(ms)\", writes, write_time AS \"write time(ms)\", writebacks, writeback_time AS \"writeback time(ms)\", extends, extend_time AS \"extend time(ms)\", hits, evictions, reuses, fsyncs, fsync_time AS \"fsync time(ms)\" FROM statsrepo.get_stat_io($1, $2) ORDER BY 1, 2, 3, 4",

  "io_statistics2" =>
  "SELECT DISTINCT stats_reset AS \"last reset time\" FROM statsrepo.stat_io WHERE snapid = $1",

  // Notable Tables
  "heavily_updated_tables" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", n_tup_ins AS \"INSERT\", n_tup_upd AS \"UPDATE\", n_tup_del AS \"DELETE\", n_tup_total AS \"Total\", hot_upd_rate AS \"HOT (%)\" FROM statsrepo.get_heavily_updated_tables($1, $2)",

  "heavily_accessed_tables" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", seq_scan AS \"Seq scan\", seq_tup_read AS \"Seq tup read\", tup_per_seq AS \"Tup per seq\", blks_hit_rate AS \"Hit ratio (%)\" FROM statsrepo.get_heavily_accessed_tables($1, $2)",

  "low_density_tables" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", n_live_tup AS \"Tuples\", logical_pages AS \"Logical pages\", physical_pages AS \"Physical pages\", tratio AS \"L/P ratio (%)\" FROM statsrepo.get_low_density_tables($1, $2) ORDER BY tratio",

  "correlation" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", attname AS \"Column\", correlation AS \"Correlation\"FROM statsrepo.get_correlation($1, $2)",

  // Query Acitvity
  "functions" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", proname AS \"Function\", calls AS \"Calls\", total_time AS \"Total time (ms)\", self_time AS \"Self time (ms)\", time_per_call AS \"Time/call (ms)\" FROM statsrepo.get_query_activity_functions($1, $2)",

  "statements" =>
  "SELECT rolname AS \"User\", datname AS \"Database\", query AS \"Query\", calls AS \"Calls\", total_exec_time AS \"Total execution time (s)\", time_per_call AS \"Average execution time (s)\", plans AS \"Plans\", total_plan_time AS \"Total planning time (s)\",  time_per_plan AS \"Average planning time (s)\" FROM statsrepo.get_query_activity_statements($1, $2)",

  "statements_rusage" =>
  "SELECT rolname AS \"User\", datname AS \"Database\", plan_reads AS \"Plan reads (Bytes)\", plan_writes AS \"Plan writes (Bytes)\", plan_user_times AS \"Plan user time (s)\", plan_sys_times AS \"Plan system time (s)\", exec_reads AS \"Execute reads (Bytes)\", exec_writes AS \"Execute writes (Bytes)\", exec_user_times AS \"Execute user time (s)\", exec_sys_times AS \"Execute system time (s)\", query AS \"Query\" FROM statsrepo.get_query_activity_statements_rusage($1, $2)",

  "plans" =>
  "SELECT * FROM statsrepo.get_query_activity_plans_report($1,$2) ORDER BY queryid, rolname, datname",

  "plans_exists_store_plans" =>
  "SELECT 1 FROM pg_proc WHERE proname='pg_store_plans_textplan'",

  "plans_get_plan" =>
  "SELECT pg_store_plans_textplan(plan) FROM statsrepo.plan WHERE snapid=$1 AND dbid=$2 AND userid=$3 AND planid=$4",

  "plans_get_plan_does_not_exist" =>
  "SELECT plan FROM statsrepo.plan WHERE snapid=$1 AND dbid=$2 AND userid=$3 AND planid=$4",

  "wait_sampling" =>
  "SELECT queryid AS \"Queryid\", \"database\" \"Database\", role AS \"Role\", backend_type AS \"Backend type\", event_type AS \"Event type\", event AS \"Event\", \"count\" AS \"Count\", ratio AS \"Ratio\", query AS \"Query\", row_number AS \"Row number\" FROM statsrepo.get_wait_sampling($1, $2)",
  // Long Transaction
  "long_transactions" =>
  "SELECT pid AS \"PID\", client AS \"Client address\", start AS \"Xact Start\", duration AS \"Duration (s)\", query AS \"Last query\" FROM statsrepo.get_long_transactions($1, $2)",

  // Lock Conflicts
  "lock_conflicts" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Relation\", duration AS \"Duration\", blockee_pid AS \"Blockee PID\", blocker_pid AS \"Blocker PID\", blocker_gid AS \"Blocker GID\", blockee_query AS \"Blockee query\", blocker_query AS \"Blocker query\" FROM statsrepo.get_lock_activity($1, $2)",

  /* Maintenance */
  // Checkpoints
  "checkpoints" =>
  "SELECT ckpt_total AS \"Number of checkpoints\", ckpt_time AS \"Caused by timeout\", ckpt_wal AS \"Caused by WALs\", avg_write_buff AS \"Average written buffers\", max_write_buff AS \"Maximum written buffers\", avg_duration AS \"Average checkpoint duration (s)\", max_duration AS \"Maximum checkpoint duration (s)\" FROM statsrepo.get_checkpoint_activity($1, $2)",

  // Autovacuums
  "autovacuum_overview" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", \"count\" AS \"Count\", sum_index_scans AS \"Index scans\", cancel AS \"Cancels\", tbl_scan_pages AS \"Table scan pages\", tbl_scan_pages_ratio AS \"Table scan pages ratio\", max_duration AS \"Max duration (s)\", avg_duration AS \"Avg duration (s)\", avg_tup_removed AS \"Avg removed rows\", avg_tup_remain AS \"Avg remain rows\", avg_tup_dead AS \"Avg remain dead\", index_scanned AS \"Count of \"\"Index scan needed\"\"\", index_skipped AS \"Count of \"\"Index scan bypassed by failsafe\"\"\", dead_lp_pages AS \"Avg dead tuple pages\", dead_lp_pages_ratio AS \"Avg dead tuple pages ratio\", dead_lp AS \"Avg dead line pointer\", max_cutoff_xid AS \"Max removable cutoff xid\", max_frozen_xid AS \"Max new relation frozen xid\", max_relmin_mxid AS \"Max new relation min mxid\", avg_tup_miss_dead AS \"Missed dead rows\", avg_tup_miss_dead_pages AS \"Pages left unclean\" FROM statsrepo.get_autovacuum_activity($1, $2)", 

  "cancellations" =>
  "SELECT timestamp::timestamp(0) AS \"Time\", database AS \"Database\", schema AS \"Schema\", \"table\" AS \"Table\", 'VACUUM' AS \"Activity\", query AS \"Causal query\" FROM statsrepo.autovacuum_cancel v WHERE timestamp BETWEEN (SELECT min(time) AS time FROM statsrepo.snapshot WHERE snapid >= $1) AND (SELECT max(time) AS time FROM statsrepo.snapshot WHERE snapid <= $2) AND instid = (SELECT instid FROM statsrepo.snapshot WHERE snapid = $2) UNION ALL SELECT timestamp::timestamp(0) AS \"Time\", database AS \"Database\", schema AS \"Schema\", \"table\" AS \"Table\", 'ANALYZE' AS \"Activity\", query AS \"Causal query\" FROM statsrepo.autoanalyze_cancel v WHERE timestamp BETWEEN (SELECT min(time) AS time FROM statsrepo.snapshot WHERE snapid >= $1) AND (SELECT max(time) AS time FROM statsrepo.snapshot WHERE snapid <= $2) AND instid = (SELECT instid FROM statsrepo.snapshot WHERE snapid = $2) ORDER By \"Time\"",

  "autovacuum_io_summary" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", avg_page_hit AS \"Page hit\", avg_page_miss AS \"Page miss\", avg_page_dirty AS \"Page dirtied\", avg_read_rate AS \"Read rate (MiB/s)\", avg_write_rate AS \"Write rate (MiB/s)\", avg_read_duration AS \"Read duration (ms)\", avg_write_duration AS \"Write duration (ms)\" FROM statsrepo.get_autovacuum_activity2($1, $2)", 

  "vacuum_wal_statistics" =>
  "SELECT replace(\"timestamp\", '-', '/') AS timestamp, wal_fpi AS \"WAL full page image\", wal_bytes AS \"WAL bytes\" FROM statsrepo.get_autovacuum_wal_activity_tendency($1, $2)",

  "vacuum_index_statistics" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", index_name AS \"Index\", \"count\" AS \"Count\", page_total AS \" Avg page total\", page_new_del AS \"Avg page new delete\", page_cur_del AS \"Avg page current delete\", page_reuse AS \"Avg page reuse\" FROM statsrepo.get_autovacuum_index_activity($1, $2)",

  "analyze_overview" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", \"count\" AS \"Count\", total_duration AS \"Total duration (s)\", avg_duration AS \"Avg duration (s)\", max_duration AS \"Max duration (s)\", last_analyze AS \"Last analyzed\", cancels AS \"Cancels\", mod_rows_max AS \"Max modified rows\" FROM statsrepo.get_autoanalyze_stats($1, $2)", 

  "analyze_io_summary" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", avg_page_hit AS \"Page hit\", avg_page_miss AS \"Page miss\", avg_page_dirty AS \"Page dirtied\", avg_read_rate AS \"Read rate (MiB/s)\", avg_write_rate AS \"Write rate (MiB/s)\", avg_read_duration AS \"Read duration (ms)\", avg_write_duration AS \"Write duration (ms)\" FROM statsrepo.get_autoanalyze_activity2($1, $2)",

  "modified_rows" =>
  "SELECT replace(\"timestamp\", '-', '/') AS timestamp, datname||'.'||nspname||'.'||relname, ratio FROM statsrepo.get_modified_row_ratio($1, $2, $3)",

  // Replication
  "replication_overview" =>
  "SELECT snapshot_time AS \"Snapshot time\", usename AS \"Session user\", application_name AS \"Application name\", client_addr AS \"Client address\", client_hostname AS \"Client host\", client_port AS \"Client port\", backend_start AS \"Started at\", state AS \"State\", current_location AS \"Current location\", sent_location AS \"Sent location\", write_location AS \"Write location\", flush_location AS \"Flush location\", replay_location AS \"Replay location\", to_char(write_lag_time, 'HH24:MI:SS.US') AS \"Write lag time\", to_char(flush_lag_time, 'HH24:MI:SS.US') AS \"Flush lag time\", to_char(replay_lag_time, 'HH24:MI:SS.US') AS \"Replay lag time\", pg_size_pretty(replay_delay_avg::bigint) AS \"Average replay delay\", pg_size_pretty(replay_delay_peak::bigint) AS \"Peak replay delay\", sync_priority AS \"Sync priority\", sync_state AS \"Sync state\" FROM statsrepo.get_replication_activity($1, $2)",

  "replication_delays" =>
  "SELECT replace(\"timestamp\", '-', '/'), client , flush_delay_size AS \"flush\", replay_delay_size AS \"replay\", sync_state FROM statsrepo.get_replication_delays($1, $2)",

  "replication_slots" =>
  "SELECT slot_name AS \"Slot name\", slot_type AS \"Slot type\", slot_datname AS \"Slot database\", spill_txns AS \"Spill txns\", spill_count AS \"Spill count\", spill_bytes AS \"Spill bytes\", stream_txns AS \"Stream txns\", stream_count AS \"Stream count\", stream_bytes AS \"Stream bytes\", total_txns AS \"Total txns\", total_bytes AS \"Total bytes\", stats_reset AS \"Replication slots reset\" FROM statsrepo.get_stat_replication_slots_report($1, $2)",
  
  /* Miscellaneous */
  // Tables and Indexes
  "tables" =>
  "SELECT datname AS \"Database\", nspname AS \"Schema\", relname AS \"Table\", attnum AS \"Columns\", tuples AS \"Rows\", size AS \"MiB\", size_incr AS \"+MiB\", seq_scan AS \"Table scans\", idx_scan AS \"Index scans\" FROM statsrepo.get_schema_info_tables($1, $2)",

  "indexes" =>
  "SELECT datname AS \"Database\", schemaname AS \"Schema\", indexname AS \"Index\", tablename AS \"Table\", size AS \"MiB\", size_incr AS \"+MiB\", scans AS \"Scans\", rows_per_scan AS \"Rows/scan\", blks_read AS \"Reads\", blks_hit AS \"Hits\", keys AS \"Keys\" FROM statsrepo.get_schema_info_indexes($1, $2)",

  // Settings
  "runtime_params" =>
  "SELECT name AS \"Name\", setting AS \"Setting\", unit AS \"Unit\", source AS \"Source\" FROM statsrepo.get_setting_parameters($1, $2)",

  // Hardware Information
  // CPU Information
  "cpu_information" =>
  "SELECT replace( \"timestamp\", '-', '/') AS \"Date time\", vendor_id AS \"Vendor\", model_name AS \"Model name\", cpu_mhz AS \"CPU MHz\", processors AS \"CPU\", threads_per_core AS \"Threads/core\", cores_per_socket AS \"Cores/socket\", sockets AS \"Socket\" FROM statsrepo.get_cpuinfo($1, $2)",
  
  // Memory Information
  "memory_information" =>
  "SELECT replace( \"timestamp\", '-', '/') AS \"Date time\", mem_total AS \"System memory\" FROM statsrepo.get_meminfo($1, $2)",
  
  // Alerts
  "alerts" =>
  "SELECT \"timestamp\" AS \"Time\", message AS \"Message\" FROM statsrepo.get_alert($1, $2)",

  // Profiles
  "profiles" =>
  "SELECT processing AS \"Prosessing\", executes AS \"Executes\" FROM statsrepo.get_profiles($1, $2)",

  // Snapshot List
  "snapshotlist" =>
  "SELECT s.snapid AS SnapID, i.instid AS instID, i.hostname AS Host, i.port AS Port, s.time::timestamp(0) AS Timestamp , s.comment AS Comment FROM statsrepo.snapshot s LEFT JOIN statsrepo.instance i ON s.instid = i.instid",

  // Snapshot Size
  "snapshotsize" =>
  "SELECT i.instid, i.name, i.hostname, i.port, count(s.snapid), sum(s.snapshot_increase_size)::numeric(1000), max(s.snapid), max(s.time)::timestamp(0) FROM statsrepo.instance i LEFT JOIN statsrepo.snapshot s ON i.instid = s.instid GROUP BY i.instid, i.name, i.hostname, i.port ORDER BY i.instid",

  /* Log Viewer */
  "log_size" =>
  "SELECT count(*) FROM statsrepo.log WHERE instid = $1 AND timestamp BETWEEN $2 AND $3",

  "log" =>
  "SELECT to_char(timestamp, 'YYYY-MM-DD HH24:MI:SS.MS') AS timestamp, username, database, pid, client_addr, session_id, session_line_num, ps_display, to_char(session_start, 'YYYY-MM-DD HH24:MI:SS.MS') AS session_start, vxid, xid, elevel, sqlstate, message, detail, hint, query, query_pos, context, user_query, user_query_pos, location, application_name, backend_type FROM statsrepo.log WHERE instid = $1 AND timestamp BETWEEN $2 AND $3"
);

?>
