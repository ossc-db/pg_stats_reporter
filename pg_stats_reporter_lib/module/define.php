<?php
/*
 * define
 *
 * Copyright (c) 2012,2013, NIPPON TELEGRAPH AND TELEPHONE CORPORATION
 */

// Image File
define("IMAGE_FILE", "pgsql_banner01.png");

// Library path
define("SMARTY_PATH", "package/Smarty-3.1.12/libs/");
define("JQUERY_PATH", "package/jquery-1.8.2/");
define("JQUERYUI_PATH", "package/jquery-ui-1.9.1.custom/");
define("TIMEPICKER_PATH", "package/");
define("TABLESORTER_PATH", "package/tablesorter/");
define("SUPERFISH_PATH", "package/superfish/superfish-1.4.8/");
define("JQPLOT_PATH", "package/jqPlot-1.0.4r1121/");
define("DYGRAPHS_PATH", "package/danvk-dygraphs-4d3672f/");

// setting file
define("CONFIG_PATH", "../../pg_stats_reporter/");
define("CONFIG_FILENAME", "pg_stats_reporter.ini");
define("CACHE_CONFIG_PATH", "../../pg_stats_reporter/cache/");
define("CACHE_CONFIG_FILENAME", "pg_stats_reporter_cache.ini");

// message file
define("MESSAGE_PATH", "../../pg_stats_reporter/message/");
define("MESSAGE_PREFIX", "message_");
define("MESSAGE_SUFFIX", ".xml");

// pg_statsinfo's version
define("V23", "20300");
define("V24", "20400");

// Smarty cache, compile, template directory
define("CACHE_DIR", "../../pg_stats_reporter/cache");
define("COMPILE_DIR", "../../pg_stats_reporter/compiled");
define("TEMPLATE_DIR", "../../pg_stats_reporter/template");
define("TEMPLATE_FILE", "pg_stats_reporter.tpl");

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
  'summary'                   => true,
  'database_statistics'       => true,
  'transaction_statistics'    => true,
  'database_size'             => true,
  'recovery_conflicts'        => true,
  'wal_statistics'            => true,
  'instance_processes_ratio'  => true,
  'instance_processes'        => true,
  'cpu_usage'                 => true,
  'load_average'              => true,
  'io_usage'                  => true,
  'memory_usage'              => true,
  'disk_usage_per_tablespace' => true,
  'disk_usage_per_table'      => true,
  'heavily_updated_tables'    => true,
  'heavily_accessed_tables'   => true,
  'low_density_tables'        => true,
  'fragmented_tables'         => true,
  'functions'                 => true,
  'statements'                => true,
  'long_transactions'         => true,
  'lock_conflicts'             => true,
  'checkpoint_activity'       => true,
  'basic_statistics'          => true,
  'io_statistics'             => true,
  'current_replication_status' => true,
  'replication_delays'        => true,
  'database'                  => false,
  'schema'                    => false,
  'table'                     => true,
  'index'                     => true,
  'view'                      => false,
  'sequence'                  => false,
  'trigger'                   => false,
  'role'                      => false,
  'parameter'                 => true,
  'profiles'                  => false
);

// help list
$help_list = array(
  'summary'                   => 'summary_dialog',
  'database_statistics'       => 'database_statistics_dialog',
  'transaction_statistics'    => 'transaction_statistics_dialog',
  'database_size'             => 'database_size_dialog',
  'recovery_conflicts'        => 'recovery_conflicts_dialog',
  'wal_statistics'            => 'wal_statistics_dialog',
  'instance_processes_ratio'  => 'instance_processes_ratio_dialog',
  'instance_processes'        => 'instance_processes_dialog',
  'cpu_usage'                 => 'cpu_usage_dialog',
  'load_average'              => 'load_average_dialog',
  'io_usage'                  => 'io_usage_dialog',
  'memory_usage'              => 'memory_usage_dialog',
  'disk_usage_per_tablespace' => 'disk_usage_per_tablespace_dialog',
  'disk_usage_per_table'      => 'disk_usage_per_table_dialog',
  'heavily_updated_tables'    => 'heavily_updated_tables_dialog',
  'heavily_accessed_tables'   => 'heavily_accessed_tables_dialog',
  'low_density_tables'        => 'low_density_tables_dialog',
  'fragmented_tables'         => 'fragmented_tables_dialog',
  'functions'                 => 'functions_dialog',
  'statements'                => 'statements_dialog',
  'long_transactions'         => 'long_transactions_dialog',
  'lock_conflicts'             => 'lock_conflicts_dialog',
  'checkpoint_activity'       => 'checkpoint_activity_dialog',
  'basic_statistics'          => 'basic_statistics_dialog',
  'io_statistics'             => 'io_statistics_dialog',
  'current_replication_status' => 'current_replication_status_dialog',
  'replication_delays'        => 'replication_delays_dialog',
  'database'                  => 'database_dialog',
  'schema'                    => 'schema_dialog',
  'table'                     => 'table_dialog',
  'index'                     => 'index_dialog',
  'view'                      => 'view_dialog',
  'sequence'                  => 'sequence_dialog',
  'trigger'                   => 'trigger_dialog',
  'role'                      => 'role_dialog',
  'parameter'                 => 'parameter_dialog',
  'profiles'                  => 'profiles_dialog'
);

// query list
$query_string = array(
  /* Summary */
  "summary" =>
  "SELECT * FROM statsrepo.get_summary($1, $2)",

  /* Database System */
  // Database Statistics
  "database_statistics" =>
  "SELECT datname AS \"database\", size AS \"MiB\", size_incr AS \"+MiB\", xact_commit_tps AS \"commit/s\", xact_rollback_tps AS \"rollback/s\", blks_hit_rate AS \"hit%\", blks_hit_tps AS \"gets/s\", blks_read_tps AS \"reads/s\", tup_fetch_tps AS \"rows/s\" FROM statsrepo.get_dbstats($1, $2)",

  "transaction_statistics" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", datname, avg(commit_tps) AS commit_tps, avg(rollback_tps) AS rollback_tps FROM statsrepo.get_xact_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "database_size" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", datname, avg(size*1024*1024) AS size FROM statsrepo.get_dbsize_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "recovery_conflicts" =>
  "SELECT datname AS \"database\", confl_tablespace AS \"conflict tblspc\", confl_lock AS \"conflict lock\", confl_snapshot AS \"conflict snapshot\", confl_bufferpin AS \"conflict bufferpin\", confl_deadlock AS \"conflict deadlock\" FROM statsrepo.get_recovery_conflicts($1, $2)",

  // Instance Activity
  "wal_statistics" =>
  "SELECT replace(\"timestamp\", '-', '/') AS \"timestamp\", avg(write_size*1024*1024) AS \"write_size (Bytes)\", avg(write_size_per_sec*1024*1024) As \"write_size_per_sec (Bytes/s)\" FROM statsrepo.get_xlog_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  "wal_statistics_stats" =>
  "SELECT * FROM statsrepo.get_xlog_stats($1, $2)",

  "instance_processes_ratio" =>
  "SELECT idle AS \"idle (%)\", idle_in_xact AS \"idle in xact (%)\", waiting AS \"waiting (%)\", running AS \"running (%)\" FROM statsrepo.get_proc_ratio($1, $2)",

  "instance_processes" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(idle) AS idle, avg(idle_in_xact) AS \"idle in xact\", avg(waiting) AS waiting, avg(running) AS running FROM statsrepo.get_proc_tendency_report($1, $2) GROUP BY 1 ORDER BY 1",

  /* Operating System */
  // OS Resource Usage
  "cpu_usage" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(idle) AS idle, avg(iowait) AS iowait, avg(system) AS system, avg(\"user\") AS user FROM statsrepo.get_cpu_usage_tendency_report($1, $2) GROUP BY 1 ORDER BY 1",

  "load_average" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(\"1min\") AS \"1min\", avg(\"5min\") AS \"5min\", avg(\"15min\") AS \"15min\" FROM statsrepo.get_loadavg_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  "io_usage" =>
  "SELECT device_name, device_tblspaces AS \"including TableSpaces\", total_read AS \"total read (MiB)\", total_write AS \"total write (MiB)\", total_read_time AS \"total read time (ms)\", total_write_time AS \"total write time (ms)\", io_queue AS \"current I/O queue\", total_io_time AS \"total I/O time (ms)\" FROM statsrepo.get_io_usage($1, $2)",

  "io_size" =>
  "SELECT replace(\"timestamp\", '-', '/'), device_name, avg(read_size_tps*1024) AS read, avg(write_size_tps*1024) AS write FROM statsrepo.get_io_usage_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "io_time" =>
  "SELECT replace(\"timestamp\", '-', '/'), device_name, avg(read_time_tps)/1000 AS \"avg read time\", avg(write_time_tps)/1000 AS \"avg write time\" FROM statsrepo.get_io_usage_tendency_report($1, $2) GROUP BY 1,2 ORDER BY 1,2",

  "memory_usage" =>
  "SELECT replace(\"timestamp\", '-', '/'), avg(memfree*1024*1024) AS memfree, avg(buffers*1024*1024) AS buffers, avg(cached*1024*1024) AS cached, avg(swap*1024*1024) AS swap, avg(dirty*1024*1024) AS dirty FROM statsrepo.get_memory_tendency($1, $2) GROUP BY 1 ORDER BY 1",

  // Disk Usage
  "disk_usage_per_tablespace" =>
  "SELECT spcname AS tablespace, location, device, used AS \"used (MiB)\", avail AS \"avail (MiB)\", remain AS \"remain (%)\" FROM statsrepo.get_disk_usage_tablespace($1, $2)",

  "disk_usage_per_table" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", size AS \"size (MiB)\", table_reads AS \"table reads\", index_reads AS \"index reads\", toast_reads AS \"toast reads\" FROM statsrepo.get_disk_usage_table($1, $2)",

  "table_size" =>
  "SELECT e.database || '.' || e.schema || '.' || e.table, e.size/1024/1024 AS \"MiB\" FROM statsrepo.tables e WHERE e.snapid = $1 ORDER BY 2 DESC LIMIT 15",

  "disk_read" =>
  "SELECT e.database || '.' || e.schema || '.' || e.table, statsrepo.sub(e.heap_blks_read, b.heap_blks_read) + statsrepo.sub(e.idx_blks_read, b.idx_blks_read) + statsrepo.sub(e.toast_blks_read, b.toast_blks_read) + statsrepo.sub(e.tidx_blks_read, b.tidx_blks_read) FROM statsrepo.tables e LEFT JOIN statsrepo.table b ON e.tbl = b.tbl AND e.nsp = b.nsp AND e.dbid = b.dbid AND b.snapid = $1 WHERE e.snapid = $2 ORDER BY 2 DESC limit 15",

  /* SQL */
  // Notable Tables
  "heavily_updated_tables" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", n_tup_ins AS \"INSERT\", n_tup_upd AS \"UPDATE\", n_tup_del AS \"DELETE\", n_tup_total AS total, hot_upd_rate AS \"HOT (%)\" FROM statsrepo.get_heavily_updated_tables($1, $2)",

  "heavily_accessed_tables" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", seq_scan, seq_tup_read, tup_per_seq, blks_hit_rate AS \"hit (%)\" FROM statsrepo.get_heavily_accessed_tables($1, $2)",

  "low_density_tables" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", n_live_tup AS tuples, logical_pages, physical_pages, tratio FROM statsrepo.get_low_density_tables($1, $2) ORDER BY tratio",

  "fragmented_tables" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", attname AS \"column\", correlation FROM statsrepo.get_flagmented_tables($1, $2)",

  // Query Acitvity
  "functions" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", proname AS \"function\", calls, total_time AS \"total time (ms)\", self_time AS \"self time (ms)\", time_per_call AS \"time/call (ms)\" FROM statsrepo.get_query_activity_functions($1, $2)",

  "statements" =>
  "SELECT rolname AS \"user\", datname AS \"database\", query, calls, total_time AS \"total time (sec)\", time_per_call AS \"time/call (sec)\" FROM statsrepo.get_query_activity_statements($1, $2)",

  // Long Transaction
  "long_transactions" =>
  "SELECT pid, client AS \"client address\", start AS \"when to start\", duration AS \"duration (sec)\", query FROM statsrepo.get_long_transactions($1, $2)",

  // Lock Conflicts
  "lock_conflicts" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"relation\", duration, blockee_pid AS \"blockee pid\", blocker_pid AS \"blocker pid\", blocker_gid AS \"blocker gid\", blockee_query AS \"blockee query\", blocker_query AS \"blocker query\" FROM statsrepo.get_lock_activity($1, $2)",

  /* Operation Activity */
  // Checkpoint Activity
  "checkpoint_activity" =>
  "SELECT ckpt_total AS \"total checkpoints\", ckpt_time AS \"checkpoints by time\", ckpt_xlog AS \"checkpoints by xlog\", avg_write_buff AS \"avg written buffers\", max_write_buff AS \"max written buffers\", avg_duration AS \"avg duration (sec)\", max_duration AS \"max duration (sec)\" FROM statsrepo.get_checkpoint_activity($1, $2)",

  // Autovacuum Activity
  "basic_statistics" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", \"count\", avg_index_scans AS \"avg index scans\", avg_tup_removed AS \"avg removed rows\", avg_tup_remain AS \"avg remain rows\", avg_duration AS \"avg duration (sec)\", max_duration AS \"max duration (sec)\" FROM statsrepo.get_autovacuum_activity($1, $2)", 

  "io_statistics" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", avg_page_hit AS \"avg page hit\", avg_page_miss AS \"avg page miss\", avg_page_dirty AS \"avg page dirty\", avg_read_rate AS \"avg read rate\", avg_write_rate AS \"avg write rate\" FROM statsrepo.get_autovacuum_activity2($1, $2)", 

  // Replication Activity
  "current_replication_status" =>
  "SELECT usename AS \"user\", application_name AS \"appname\", client_addr AS \"client addr\", client_hostname AS \"client_host\", client_port, backend_start AS \"backend start\", state, current_location AS \"current location\", sent_location AS \"sent location\", write_location AS \"write location\", flush_location AS \"flush location\", replay_location AS \"replay location\", sync_priority AS \"sync priority\", sync_state AS \"sync state\" FROM statsrepo.get_replication_activity($1, $2)",

  // Replication Delays
  "replication_delays" =>
  "SELECT replace(\"timestamp\", '-', '/'), client , flush_delay_size , replay_delay_size FROM statsrepo.get_replication_delays($1, $2)",

  "replication_delays_get_sync_host" =>
  "SELECT host(client_addr) || ':' || client_port FROM statsrepo.replication WHERE snapid = $1 AND sync_state = 'sync'",

  /* Information */
  // Schema Information 
  "table" =>
  "SELECT datname AS \"database\", nspname AS \"schema\", relname AS \"table\", attnum AS columns, avg_width AS \"row width\", size AS \"MiB\", size_incr AS \"+MiB\", seq_scan AS \"table scans\", idx_scan AS \"index scans\" FROM statsrepo.get_schema_info_tables($1, $2)",

  "index" =>
  "SELECT datname AS \"database\", schemaname AS \"schema\", indexname AS \"index\", tablename AS \"table\", size AS \"MiB\", size_incr AS \"+MiB\", scans, rows_per_scan AS \"rows/scan\", blks_read AS reads, blks_hit AS hits, keys FROM statsrepo.get_schema_info_indexes($1, $2)",

  // Setting Parameters
  "parameter" =>
  "SELECT name, setting, unit, source FROM statsrepo.get_setting_parameters($1, $2)",

  // Profiles
  "profiles" =>
  "SELECT processing, executes FROM statsrepo.get_profiles($1, $2)"
);

?>