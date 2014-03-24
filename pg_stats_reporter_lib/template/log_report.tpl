<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>pg_stats_reporter 3.0.0</title>

<!-- javascripts -->
<script type="text/javascript" src="{$jquery_path}jquery-2.0.1.min.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.core.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.widget.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.accordion.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.mouse.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.slider.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.button.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.draggable.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.position.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.resizable.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/jquery.ui.dialog.js"></script>
<script type="text/javascript" src="{$jquery_ui_path}ui/i18n/jquery.ui.datepicker-ja.js"></script>
<script type="text/javascript" src="{$timepicker_path}jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="{$superfish_path}js/hoverIntent.js"></script>
<script type="text/javascript" src="{$superfish_path}js/superfish.js"></script>
<script type="text/javascript" src="{$superfish_path}js/supersubs.js"></script>
<script type="text/javascript" src="{$tablesorter_path}js/jquery.tablesorter.min.js"></script>
<script type="text/javascript" src="{$tablesorter_path}js/jquery.tablesorter.widgets.min.js"></script>
<script type="text/javascript" src="{$tablesorter_path}js/widgets/widget-columnSelector.js"></script>
<!-- pg_stats_reporter's javascript -->
<script type="text/javascript" src="js/common.js"></script>
<script type="text/javascript" src="js/log_report.js"></script>
<script type="text/javascript" src="js/log_report_ajax.js"></script>

<!-- stylesheets -->
<link rel="stylesheet" type="text/css" href="{$superfish_path}css/superfish.css"/>
<link rel="stylesheet" type="text/css" href="{$jquery_ui_path}themes/start/jquery.ui.all.css"/>
<link rel="stylesheet" type="text/css" href="{$tablesorter_path}css/theme.blue.css"/>
<!-- pg_stats_reporter's stylesheets -->
<link rel="stylesheet" type="text/css" href="css/pg_stats_reporter.css"/>
</head>

<body>
<!-- header menu -->
{$header_menu}

<!-- left menu -->
{$left_menu}

<!-- contents -->
<div id="contents">
  <div class="top_jump_margin"></div>

  <div id="log_report" class="jump_margin"></div>
  <h1>Log Report</h1>
  <div align="right" class="jquery_ui_button_info_h1">
    <button class="help_button" dialog="#log_report_dialog"></button>
  </div>

  <input id="page_total" type="hidden" value="{$page_total}">

  <div id="func_menu">
    <button id="search_option">Search Option</button>
    <div id="search">
      <div id="search_menu">
        <form id="search_form">
          <fieldset>
            <span>
              <label for="elevel">ELEVEL:</label>
              <select name="elevel">
                <option value=""></option>
                <option value="DEBUG">DEBUG</option>
                <option value="INFO">INFO</option>
                <option value="NOTICE">NOTICE</option>
                <option value="WARNING">WARNING</option>
                <option value="ERROR">ERROR</option>
                <option value="LOG">LOG</option>
                <option value="FATAL">FATAL</option>
                <option value="PANIC">PANIC</option>
              </select>
            </span>
            <span>
              <label for="username">USERNAME:</label>
              <input type="text" name="username" size="20" maxlength="64" autocomplete="off">
            </span>
            <span>
              <label for="database">DATABASE:</label>
              <input type="text" name="database" size="20" maxlength="64" autocomplete="off">
            </span>
          </fieldset>
          <fieldset>
            <span>
              <label for="message">MESSAGE:</label>
              <input type="text" name="message" size="80" maxlength="100" autocomplete="off">
            </span>
          </fieldset>
        </form>
        <button id="search_submit">Search</button>
        <button id="search_clear">Clear</button>
      </div>
    </div>
  </div>
  <div id="t-navi">
    <div class="columnSelectorWrapper">
      <input id="colSelect" type="checkbox" class="hidden">
      <label id="column_select" for="colSelect">Column</label>
      <div id="columnSelector" class="columnSelector"></div>
    </div>
    <button id="filter_reset" class="filter_reset">Filter Reset</button>
      
    <div class="pagerWrapper">
      <span class="page_info"></span>
      <div class="pager">
        <button class="page_prev">Prev</button>
        <button class="page_next">Next</button>
      </div>
    </div>
  </div>
  <table id="log_report_table" class="tablesorter">
    <thead>
      <tr>
        <th>timestamp</th>
        <th class="filter-onlyAvail">username</th>
        <th class="filter-onlyAvail">database</th>
        <th>pid</th>
        <th class="filter-onlyAvail">client_addr</th>
        <th>session_id</th>
        <th>session_line_num</th>
        <th>ps_display</th>
        <th>session_start</th>
        <th>vxid</th>
        <th>xid</th>
        <th class="filter-onlyAvail">elevel</th>
        <th class="filter-onlyAvail">sqlstate</th>
        <th>message</th>
        <th>detail</th>
        <th>hint</th>
        <th>query</th>
        <th>query_pos</th>
        <th>context</th>
        <th>user_query</th>
        <th>user_query_pos</th>
        <th>location</th>
        <th>application_name</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
  <div id="f-navi">
    <div class="pagerWrapper">
      <span class="page_info"></span>
      <div class="pager">
        <button class="page_prev">Prev</button>
        <button class="page_next">Next</button>
      </div>
    </div>
  </div>
{$help_dialog}
{$message_dialog}
</div>
<!-- contents end -->

</body>
</html>
