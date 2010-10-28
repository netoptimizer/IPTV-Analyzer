<?php /* -*- Mode: php; tab-width: 8; indent-tabs-mode: nil; c-basic-offset: 4 -*- */

#define("CFGFILE", "/etc/tvprobe/webfrontend.ini");
#define("CFGFILE", "../webfrontend.ini");
define("CFGFILE", "webfrontend.ini");

function getMicroTime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
    #$text='Generated in '. sprintf('%0.3f', $timeEnd - $timeStart) . ' sec';
}

function displayTimingInfo($timeStart, $timeEnd, $display=TRUE, $infotxt=NULL)
{
    $text='Elapsed time '. sprintf('%0.3f', $timeEnd - $timeStart) . ' sec';
    if (!$display) {
	echo "<!--\n";
    }
    echo "<br>";
    if ($infotxt) {
	echo "($infotxt) ";
    }
    echo "$text<br>\n";
    if (!$display) {
	echo "\n-->";
    }
}

# Function that shows secs in a human-readable format
#  coded by Jesper N Henriksen <jnh@comx.dk>
function timespan($sum)
{
    $seconds=$sum%60;
    $minutes=$sum/60%60;
    $hours=$sum/3600%24;
    $days=floor($sum/86400);

    $str_parts=array();

    if($days)
    {
	$str_parts[]=$days.' day'.($days==1?'':'s');
    }
    if($hours)
    {
	$str_parts[]=$hours.' hour'.($hours==1?'':'s');
    }
    if($minutes)
    {
	$str_parts[]=$minutes.' minute'.($minutes==1?'':'s');
    }
    if($seconds)
    {
	$str_parts[]=$seconds.' second'.($seconds==1?'':'s');
    }

    return(implode(', ',$str_parts));
}

function load_config($file = CFGFILE)
{
    global $CONFIG;
    /* Here the process_sections parameter is set to TRUE.  Thus, a
       multidimensional array is returned, with the section names and
       settings included.
    */
    $CONFIG = parse_ini_file($file, TRUE);

    /* TODO: Validate contents... here so its not needed everytime
             where its used. */
    return $CONFIG;
}

function config_read_value($key, $subkey)
{
    global $CONFIG;
    #echo "<br>" . __FUNCTION__ . ":"; print_r($CONFIG);

    if (!is_array($CONFIG)) {
	$err = "Must load config file before using this function!";
	trigger_error("$err", E_USER_ERROR);
    }
    if (!is_array($CONFIG[$key])) {
	$err = "Config is missing section [$key]";
	trigger_error("$err", E_USER_ERROR);
    }

    $value = $CONFIG[$key][$subkey];
    if (!isset($value)) {
	$err = "Config error, cannot read value [$key][$subkey]";
	trigger_error("$err", E_USER_ERROR);
    }
    return $value;
}

# TODO: Look into using PHP API 'mysqli' instead of 'mysql',
# as it allows usage of mysql prepare and execute statements.

function db_connect()
{
    global $CONFIG;
    global $DBH;

    /* load the config file if not loaded */
    if (!is_array($CONFIG)) {
	$config = load_config();
	if (!is_array($CONFIG)) {
	    $err = "No config file loaded!";
	    trigger_error("$err", E_USER_ERROR);
	}
    }
    #echo "<br>config:"; print_r($config);
    #echo "<br>CONFIG:"; print_r($CONFIG);

    $key = 'DB droplog';
    if (!is_array($config[$key])) {
        $err = "Config MUST have a database section [$key]";
        trigger_error("$err", E_USER_ERROR);
    }

    $dbhost = config_read_value($key, 'dbhost');
    $dbname = config_read_value($key, 'dbname');
    $dbuser = config_read_value($key, 'dbuser');
    $dbpass = config_read_value($key, 'dbpass');

    $DBH = mysql_connect($dbhost, $dbuser, $dbpass);
    if (!$DBH) {
	die('Could not connect: ' . mysql_error());
    }
    //echo 'Connected successfully<br>';

    $db_selected = mysql_select_db($dbname, $DBH);
    if (!$db_selected) {
	die ("Unable to use dbname:$dbname err: " . mysql_error());
    }
    return $DBH;
}

function db_disconnect($link=NULL)
{
    if (isset($link)) {
	mysql_close($link);
    } else {
	mysql_close();
    }
}


function cleanup_input()
{
    //Q: Does this work??? as the values are not returned?!
    //   rephrased; do we really modify the global values?

    // Cleanup input
    $_GET     = array_map('trim', $_GET);
    $_POST    = array_map('trim', $_POST);
    $_COOKIE  = array_map('trim', $_COOKIE);
    $_REQUEST = array_map('trim', $_REQUEST);
    if(get_magic_quotes_gpc()):
	$_GET     = array_map('stripslashes', $_GET);
        $_POST    = array_map('stripslashes', $_POST);
        $_COOKIE  = array_map('stripslashes', $_COOKIE);
        $_REQUEST = array_map('stripslashes', $_REQUEST);
    endif;
}

function cleanup_input_request()
{
    $_REQUEST = array_map('trim', $_REQUEST);
    if(get_magic_quotes_gpc()):
        $_REQUEST = array_map('stripslashes', $_REQUEST);
    endif;
    return $_REQUEST;
}

#####
# Helper function for generating a "BETWEEN" query string
#

function helper_between_query($fromT=NULL, $toT=NULL, $interval=NULL, $col_name="record_time")
{
    $statement = "";
    $valid = false;
    $between = " AND $col_name BETWEEN ";
    if (isset($fromT)) {
	$between .= "'" . mysql_real_escape_string($fromT) . "' AND ";

	if (isset($toT)) {
	    $to = sprintf(" '%s' ", mysql_real_escape_string($toT));
	    $between .= $to;
	    $valid    = true;
	}
	elseif (isset($interval)) {
	    $between .= "DATE_ADD($col_name, INTERVAL $interval)";
	    $valid    = true;
	}
    } else {
        # From time is NOT set
	if (isset($toT) || isset($interval)) {
	    echo "ERROR: Need \$fromT in order to use \$toT or \$interval<br>\n";
	}
    }
    if ($valid == true) {
	$statement = $between;
    }
    return $statement;
}


#####
# Probe level data


function probe_data_query_ts($probeid, $bucketsz=86400,
                             $tsFrom=NULL, $tsTo=NULL)
{
    if (is_numeric($tsFrom)) {
        $fromT = date('Y-m-d H:i:s', $tsFrom);
    }
    if (is_numeric($tsTo)) {
        $toT   = date('Y-m-d H:i:s', $tsTo);
    }
    $result = & probe_data_query($probeid, $bucketsz, $fromT, $toT);
    return $result;
}


function probe_data_query($probeid, $bucketsz=86400,
                          $fromT=NULL, $toT=NULL, $interval=NULL)
{
  // Query
  $sec = $bucketsz;
  #$sec = 4 * 60 * 60;

  $query2 = sprintf(
   "SELECT UNIX_TIMESTAMP(record_time) DIV $sec as bucket,
           sum(delta_skips)  as skips,
           sum(delta_discon) as drops,
           probes.name,
           UNIX_TIMESTAMP(record_time) as timestamp,
           UNIX_TIMESTAMP(min(record_time)) as timemin,
           UNIX_TIMESTAMP(max(record_time)) as timemax,
           TIMESTAMPDIFF(SECOND, min(record_time), max(record_time)) as period,
           count(record_time) as records
    FROM   log_event, probes
    WHERE probes.id = probe_id
      AND probes.id = '%s' ",
   mysql_real_escape_string($probeid));

  $query2 .= query_str_remove_channels();

  $query2 .= helper_between_query($fromT, $toT, $interval, "record_time");
  $query2 .= " GROUP BY bucket";
  #print_r($query2);

  $db_result = mysql_query($query2);
  if (!$db_result) {
    $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
    $message .= 'Whole query: ' . $query2;
    die($message);
  }
  # DEBUG
  #echo $query2 . "<br>";

  $result = array();
  while ($row = mysql_fetch_assoc($db_result)) {
      #$id = $row['datoen'];
      #$result[$id] = $row;
      array_push($result, $row);
  }
  return $result;
}

function probe_data_show_table($result)
{
    echo "<TABLE border=\"1\">";
    echo "<TR>";
    echo "<TD>bucket</TD>";
    echo "<TD>date</TD>";
    echo "<TD>timestamp</TD>";
    echo "<TD>timemin</TD>";
    echo "<TD>timemax</TD>";
    echo "<TD>period</TD>";
    echo "<TD>probename</TD>";
    echo "<TD>skips</TD>";
    echo "<TD>drops</TD>";
    echo "</TR>";

    foreach ($result as $row) {
        echo "<TR>";
        echo "<TD>" . $row['bucket'] . "</TD>";
        echo "<TD>" . date('Y-m-d H:i:s', $row['timestamp']) . "</TD>";
        echo "<TD>" . $row['timestamp'] . "</TD>";
        echo "<TD>" . $row['timemin'] . "</TD>";
        echo "<TD>" . $row['timemax'] . "</TD>";
        echo "<TD>" . $row['period'] . "</TD>";
        echo "<TD>" . $row['name']   . "</TD>";
        echo "<TD>" . $row['skips']  . "</TD>";
        echo "<TD>" . $row['drops'] . "</TD>";
        echo "</TR>\n";
    }
    echo "</TABLE>\n\n";
}



#####
# Multicast channels per probe in a given time period
#  GROUP BY multicast_dst
#

function multicast_probe_data_query_ts($probeid,
                                       $tsFrom=NULL, $tsTo=NULL)
{
    if (is_numeric($tsFrom)) {
        $fromT = date('Y-m-d H:i:s', $tsFrom);
    }
    if (is_numeric($tsTo)) {
        $toT   = date('Y-m-d H:i:s', $tsTo);
    }
    $result = & multicast_probe_data_query($probeid, $fromT, $toT);
    return $result;
}


function multicast_probe_data_query($probeid,
                                    $fromT=NULL, $toT=NULL, $interval=NULL)
{
  // Query
    $query = sprintf(
   "SELECT multicast_dst, count(multicast_dst) as records,
           sum(delta_skips)  as skips,
           sum(delta_discon) as drops,
	   probes.name, probes.switch, probes.id, ip_src
    FROM   log_event, probes
    WHERE probes.id = probe_id
      AND delta_discon > 0
      AND probes.id = '%s' ",
   mysql_real_escape_string($probeid));

    $query .= helper_between_query($fromT, $toT, $interval, "record_time");

    $query .= " GROUP BY multicast_dst";
    $query .= " ORDER BY drops DESC";
    //print_r($query);

    $db_result = mysql_query($query);
    if (!$db_result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
        $message .= 'Whole query: ' . $query;
        die($message);
    }

    $result = array();
    while ($row = mysql_fetch_assoc($db_result)) {
        #$id = $row['datoen'];
        #$result[$id] = $row;
        array_push($result, $row);
    }
    return $result;
}


#####
# Selecting a single multicast channel in a given time period
#  with the possible for limiting it per probeid.
#

function one_channel_data_query_ts($channel, $probeid=NULL, $bucketsz=3600,
                                   $tsFrom=NULL, $tsTo=NULL)
{
    if (is_numeric($tsFrom))
        $fromT = date('Y-m-d H:i:s', $tsFrom);
    if (is_numeric($tsTo))
        $toT   = date('Y-m-d H:i:s', $tsTo);
    $result = & one_channel_data_query($channel, $probeid, $bucketsz,
                                       $fromT, $toT);
    return $result;
}

function one_channel_data_query($channel, $probeid=NULL, $bucketsz=3600,
				$fromT=NULL, $toT=NULL, $interval=NULL)
{
  // Query
    $query = sprintf(
   "SELECT UNIX_TIMESTAMP(record_time) DIV $bucketsz as bucket,
           probe_id,
 	   multicast_dst,
 	   sum(delta_skips)  as skips,
           sum(delta_discon) as drops,
	   count(multicast_dst) as records,
	   UNIX_TIMESTAMP(record_time) as timestamp,
           UNIX_TIMESTAMP(min(record_time)) as timemin,
           UNIX_TIMESTAMP(max(record_time)) as timemax,
           TIMESTAMPDIFF(SECOND, min(record_time), max(record_time)) as period
    FROM   log_event
    WHERE  multicast_dst = '%s' ",
   mysql_real_escape_string($channel));

    // Limit to a probeid if given as argument
    if (is_numeric($probeid)) {
        $query .= sprintf(" AND probe_id = '%s' ",
                          mysql_real_escape_string($probeid));
    }

    $query .= helper_between_query($fromT, $toT, $interval, "record_time");

    $query .= " GROUP BY bucket";
    $query .= " ORDER BY probe_id, timestamp";
    //print_r($query);

    $db_result = mysql_query($query);
    if (!$db_result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
        $message .= 'Whole query: ' . $query;
        die($message);
    }

    $result = array();
    while ($row = mysql_fetch_assoc($db_result)) {
        #$id = $row['datoen'];
        #$result[$id] = $row;
        array_push($result, $row);
    }
    return $result;
}


#####
#  Selecting getting overview info on a single multicast channel
#  - in a given time period
#  - on all probes.
#

function one_channel_info_query_ts($channel, $tsFrom=NULL, $tsTo=NULL)
{
    if (is_numeric($tsFrom)) {
        $fromT = date('Y-m-d H:i:s', $tsFrom);
    }
    if (is_numeric($tsTo)) {
        $toT   = date('Y-m-d H:i:s', $tsTo);
    }
    $result = & one_channel_info_query($channel, $fromT, $toT);
    return $result;
}


function one_channel_info_query($channel, $fromT=NULL, $toT=NULL, $interval=NULL)
{
  // Query
    $query = sprintf(
   "SELECT probe_id,
           daemon_session_id,
           probes.distance,
	   probes.name,
	   probes.switch,
	   probes.shortloc,
 	   multicast_dst,
 	   sum(delta_skips)  as skips,
           sum(delta_discon) as drops,
	   count(multicast_dst) as records,
           UNIX_TIMESTAMP(min(record_time)) as timemin,
           UNIX_TIMESTAMP(max(record_time)) as timemax,
           TIMESTAMPDIFF(SECOND, min(record_time), max(record_time)) as period
    FROM   log_event, probes
    WHERE  probe_id = probes.id
      AND  multicast_dst = '%s' ",
   mysql_real_escape_string($channel));

    $query .= helper_between_query($fromT, $toT, $interval, "record_time");

    #$query .= " AND prev_id > 0"; // Exclude the initial records, on daemon start
    #$query .= " AND event_type = 2"; // Only include drop events
#    $query .= " AND delta_discon > 0"; // Exclude none drop records

    $query .= " GROUP BY daemon_session_id, probe_id";
    $query .= " ORDER BY probes.distance, probe_id, timemin";
    //print_r($query);

    $db_result = mysql_query($query);
    if (!$db_result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
        $message .= 'Whole query: ' . $query;
        die($message);
    }
    # DEBUG
#    echo __FUNCTION__ . ' QUERY: <p>' . $query . "</p><br>";

    $result = array();
    $probekey_prev = "";
    while ($row = mysql_fetch_assoc($db_result)) {
        $probekey  = "probe_id" . $row['probe_id'];
        /* Save an info record */
        if ("$probekey" != "$probekey_prev" ) {
            $result["$probekey"]['info']['name']          = $row['name'];
            $result["$probekey"]['info']['switch']        = $row['switch'];
            $result["$probekey"]['info']['shortloc']      = $row['shortloc'];
            $result["$probekey"]['info']['distance']      = $row['distance'];
            $result["$probekey"]['info']['multicast_dst'] = $row['multicast_dst'];
            $result["$probekey"]['info']['timemin']       = $row['timemin'];
            $result["$probekey"]['info']['probe_id']      = $row['probe_id'];
        }
        $probekey_prev = $probekey;

        // Always save the last timemax
        $result["$probekey"]['info']['timemax'] = $row['timemax'];

        // Counting the total value
        $result["$probekey"]['info']['skips']   += $row['skips'];
        $result["$probekey"]['info']['drops']   += $row['drops'];
        $result["$probekey"]['info']['records'] += $row['records'];
        $result["$probekey"]['info']['period']  += $row['period'];
        // Counting the number of period recordings
        $result["$probekey"]['info']['count']++;


        // Save the entire row under the $daemon_id
        $daemon_id = $row['daemon_session_id'];
        $result["$probekey"]["$daemon_id"] = $row;
        //OLD: array_push($result, $row);
    }
#    print_r($result);
    return $result;
}

// The selected{F,T} option are timestamps which are used for
// generating correct URLs
function one_channel_info_show_table(& $result, $selectedF=NULL, $selectedT=NULL)
{
    $selected_id = $_REQUEST['probeid'];

    echo "<TABLE width=100% border=\"1\">";
    echo "<TR>";
    echo "<TD>sub-periods</TD>";

    # Make it possible to select all probes
    echo "<TD "
        . "onClick=\"document.getElementById('probeid').value = '';"
        . "document.getElementById('frmChannel').submit();"
        . "this.style.backgroundColor='red';"
        . "\"\n";
    echo "onMouseOver=\"this.style.backgroundColor='yellow';\"\n";

    if (!isset($selected_id) || $selected_id == "" || $selected_id == "all") {
        echo " style=\"background-color:yellow;cursor:crosshair\"\n";
        echo " onMouseOut=\"this.style.backgroundColor='yellow';\"\n";
    } else {
        echo " style=\"cursor:pointer\"\n";
        echo " onMouseOut=\"this.style.backgroundColor='white';\"\n";
    }
    echo ">probe (all)</TD>";


    echo "<TD><b>drops</b></TD>";
    echo "<TD><b>average sec between drops</b></TD>";
    echo "<TD>measurement <b>period</b></TD>";
    echo "<TD>from</TD>";
    echo "<TD>to</TD>";
    echo "<TD>records</TD>";
    echo "</TR>\n";

    foreach ($result as $prikey => $probe) {

	$cnt = 0;

	foreach ($probe as $key => $row) {

            $probe_id = $row['probe_id'];

            /*
	    if ($row['records'] < 2) {
		continue; //break the loop and get the next value.
	    }
            */

	    if ($key == "info") {
                /* Notice expandRow() is defined in function.js */
		echo "\n<TR ";
		echo ">\n";

		echo " <TD "
		    . "onclick=\"javascript: expandRow('$prikey');\""
		    . "onMouseOver=\"this.style.backgroundColor='gray';\""
		    . "onMouseOut=\"this.style.backgroundColor='white';\"";
                echo ">" . "<b>" . $row['count'] . "</b>" . "</TD>\n";

		$theprobe = $row['name'] . "/" . $row['switch'];
		echo " <TD "
                    . "onClick=\"document.getElementById('probeid').value ="
                    . $probe_id . ";"
                    . "document.getElementById('frmChannel').submit();"
                    . "this.style.backgroundColor='red';"
                    . "\"\n";
                echo "onMouseOver=\"this.style.backgroundColor='yellow';\"\n";

                if ($selected_id == $probe_id) {
                    echo " style=\"background-color:yellow;cursor:crosshair\"\n";
                    echo " onMouseOut=\"this.style.backgroundColor='yellow';\"\n";
                } else {
                    echo " style=\"cursor:pointer\"\n";
                    echo " onMouseOut=\"this.style.backgroundColor='white';\"\n";
                }

                echo "><b>";
                echo $theprobe;
                echo "</b></TD>\n";
		unset($theprobe);
	    } else {
		$cnt++;
		$rowid = $prikey ."_" . $cnt;
		echo "<TR id=\"$rowid\" style=\"display:none;\">\n";

		echo " <TD> sub-" . $cnt . "</TD>\n";
		echo " <TD>" . "measurement period" . "</TD>\n";
	    }

	    echo ' <TD align="right">' . $row['drops'] . "</TD>\n";
	    #echo '<TD align="right">' . $row['skips'] . "</TD>";

	    /* This is a strange number, but  that might no be correct if a
	     * probes has been offline a long periode.
	     *
	     * The period (in sec) divided by number of drops, given
	     * an average of sec between drop (if they were spaced
	     * perfect in time)
	     */
	    if ($row['drops'] > 0) {
		$average_sec_between_drops = $row['period'] / $row['drops'];
		$formatted = sprintf("%.2f", $average_sec_between_drops);
	    } else {
		$formatted = "none";
	    }
	    echo ' <TD align="right">' . $formatted . "</TD>\n";

	    // Create a link to self which limits the timeperiod
	    $tsF=$row['timemin'];
	    $tsT=$row['timemax'];

            // Hack?
            $bucketsz= $_REQUEST['bucketsz'];

	    $channel = $row['multicast_dst'];
	    $link    = '<A HREF="?channel=' . $channel;
            $link   .= '&bucketsz='         . $bucketsz;
	    $linkF   = "tstampF=$tsF";
	    $linkT   = "tstampT=$tsT";
	    $linkend = '">';
	    echo ' <TD align="right">';
            echo "$link&$linkF&$linkT$linkend";
	    echo timespan($row['period']);
            echo "</A>";
	    echo "</TD>\n";

	    $datemin = date('Y-m-d H:i', $row['timemin']);
	    $datemax = date('Y-m-d H:i', $row['timemax']);

	    echo " <TD>" . "$link&$linkF&tstampT=$selectedT$linkend"
		. $datemin . "</TD>";
	    echo "<TD>" . "$link&$linkT&tstampF=$selectedF$linkend"
		. $datemax . "</TD>\n";

	    // echo "<TD>" . $row['timemax'] . "</TD>";

	    echo ' <TD align="right">' . $row['records'] . "</TD>\n";

	    echo "</TR>\n";
	}

#	echo "</div>";

    }
    echo "</TABLE>\n\n";
}


#####
# Simply get a list of multicast channels for choose from
#

function multicast_list_query()
{
    $query  = "SELECT distinct(multicast_dst) FROM log_event";
    $query .= " ORDER BY INET_ATON(multicast_dst)";
    //print_r($query);

    $db_result = mysql_query($query);
    if (!$db_result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
        $message .= 'Whole query: ' . $query;
        die($message);
    }

    $result = array();
    while ($row = mysql_fetch_assoc($db_result)) {
        #$id = $row['datoen'];
        #$result[$id] = $row;
        array_push($result, $row);
    }
    return $result;
}

# Present a form for selecting/choosing a channel
function multicast_list_form_select(& $channel_list_data) {
     # Channel list data from:
     #  multicast_list_form_select($fromT, $toT);

     # Get hold of the currently selected channel
     $channel = $_REQUEST['channel'];
     $bucketsz= $_REQUEST['bucketsz'];

     $default_bucketsz=3600;
     if (!is_numeric($bucketsz)) {
         $bucketsz = $default_bucketsz;
     }

     echo '<form method="get" id="choose_channel" action="">';
     echo "  <p>Choose a channel:<br />\n";

     echo " <select name=\"channel\">\n";
     foreach ($channel_list_data as $row) {
	  $multicast_dst = $row['multicast_dst'];

	  echo '   <option ';
	  echo " value=\"$multicast_dst\"";
	  if ($channel == $multicast_dst) {
	       echo ' selected="selected"';
	  }
	  echo ">$multicast_dst";
          echo "</option>\n";
	  unset($multicast_dst);
     }
     echo " </select>\n";

     echo " <input type=\"submit\" value=\"Select\" /><br>\n";

     # Selection of bucketsz
     echo '<br>Aggregation interval/period (bucket size) in sec:';
     echo ' <input type="text"';
     echo ' value="' . $bucketsz . '"';
     echo ' name="bucketsz" id="bucketsz"/>';
     echo " (" . timespan($bucketsz) . ")<br>";

     echo "</form>\n";
}


# Form element for selecting/choosing a channel
function form_elem_select_multicast_list(& $channel_list_data) {
    // Channel list data from:
    //  multicast_list_query($fromT, $toT);

    // Get hold of the currently selected channel
    $channel = $_REQUEST['channel'];

    echo " <select name=\"channel\">\n";
    foreach ($channel_list_data as $row) {
        $multicast_dst = $row['multicast_dst'];

        echo '   <option ';
        echo " value=\"$multicast_dst\"";
        if ($channel == $multicast_dst) {
            echo ' selected="selected"';
        }
        echo ">$multicast_dst";
        echo "</option>\n";
        unset($multicast_dst);
    }
    echo " </select>\n";
}


function form_elem_bucketsz($period, $elems=120)
{
    // Get hold of the currently setting
    $bucketsz= $_REQUEST['bucketsz'];

    // Selection of bucketsz
    echo 'Aggregation interval/period (bucket size) in sec:';
    echo ' <input size="8" type="text"';
    echo ' value="';
    if (is_numeric($bucketsz)) {
        echo $bucketsz . '"';
    } else {
        $default_bucketsz= floor($period / $elems);
        echo $default_bucketsz . '"';
        echo 'style="background-color:pink" ';
        # Hack to avoid reloading page
        $_REQUEST['bucketsz'] = $default_bucketsz;
        $bucketsz = $default_bucketsz;
    }
    echo ' name="bucketsz" id="bucketsz"';
    echo '/>';
    echo " (" . timespan($bucketsz) . ")\n";
}

function form_elem_timeperiod_from()
{
    $fromT = $_REQUEST['fromT'];

    # Use the get timestamp if $fromT is not set
    if (!isset($fromT)) {
        $tstampF = $_REQUEST['tstampF'];
        if (is_numeric($tstampF)) {
            $fromT = date('Y-m-d H:i:s', $tstampF);
        }
    }

    $timestampF;
    echo '<input type="text" size="19"';
    if (($timestampF = strtotime($fromT)) === false) {
        $fromT = "- 2 days";
        $timestampF = strtotime($fromT);
        // Indicate the date was not valid
        echo ' style="background-color:pink" ';
    }
    ###$calc_fromT = date('Y-m-d H:i:s', $timestampF);
    #### Overwrite input to avoid reload of page
    ###$_REQUEST['fromT'] = $calc_fromT;
    # Store the timestamp in the global request
    $_REQUEST['tstampF'] = $timestampF;
    echo 'value="' . htmlspecialchars($fromT);
    echo "\" name='fromT' id='fromT' />\n";

    return $timestampF;
}

function form_elem_timeperiod_to()
{
    $toT = $_REQUEST['toT'];

    # Use the get timestamp if $toT is not set
    if (!isset($toT)) {
        $tstampT = $_REQUEST['tstampT'];
        if (is_numeric($tstampT)) {
            $toT = date('Y-m-d H:i:s', $tstampT);
        }
    }

    $timestampT;
    echo '<input type="text" size="19"';
    if (($timestampT = strtotime($toT)) === false) {
        $toT = "now";
        $timestampT = strtotime($toT);
	// Indicate the date was not valid
        echo ' style="background-color:pink" ';
    }
    ###$calc_toT = date('Y-m-d H:i:s', $timestampT);
    #### Overwrite input to avoid reload of page
    ###$_REQUEST['toT'] = $calc_toT;
    # Store the timestamp in the global request
    $_REQUEST['tstampT'] = $timestampT;
    echo 'value="' . htmlspecialchars($toT);
    echo "\" name='toT' id='toT' />\n";

    return $timestampT;
}

function form_elem_maxy()
{
    $maxy_default = 5000;
    $maxy       = $_REQUEST['maxy'];
    $maxy_fixed = $_REQUEST['maxy_fixed'];

    echo ' <input ';
    echo 'type="text" size="5"';
    echo 'value="';
    if (is_numeric($maxy)) {
        echo $maxy . '" ';
    } else {
        echo $maxy_default . '" ';
        echo 'style="background-color:pink" ';
        # Hack to avoid reloading page
        $_REQUEST['maxy'] = $maxy_default;
    }
    echo ' name="maxy" id="maxy" />';

    echo ' <input type="checkbox" name="maxy_fixed"';
    echo "value=\"fixed\"";
    if ($maxy_fixed == "fixed") {
        echo " checked ";
    }
    echo "/>";
    #echo "force\n";
}

function form_elem_probeid($options)
{
    $probeid = $_REQUEST['probeid'];

    echo ' <input ';
    if ($options['hidden'] == TRUE) {
        echo 'type="hidden" ';
    } else {
        echo 'type="text" ';
    }
    echo "value=\"$probeid\" name='probeid' id='probeid' />";
}


function form_channel_selection(& $channels, & $probesinfo)
{
    echo "<fieldset>\n";
    echo "<legend>\n";
    echo "Choose a channel and adjust period\n";
    echo "</legend>\n";

    echo '<form name="frmChannel" id="frmChannel"';
    echo ' method="get" id="choose_channel" action="">';
    echo "  <p>Choose a channel:<br />\n";
    form_elem_select_multicast_list($channels);

    echo " <input type=\"submit\" value=\"Select\" /><br>\n";

    $probeid = $_REQUEST['probeid'];
    $probename = get_probename($probeid, $probesinfo);
    echo "Selected probe: <b>$probename</b><br>\n";
    form_elem_probeid(array('hidden' => TRUE));

    echo "From: ";
    $timestampF = form_elem_timeperiod_from();
    echo date('Y-m-d H:i:s', $timestampF);
    echo "<br>\n";

    echo "To:&nbsp&nbsp&nbsp&nbsp&nbsp";
    $timestampT = form_elem_timeperiod_to();
    echo date('Y-m-d H:i:s', $timestampT);
    echo "<br>\n";

    $period = ($timestampT - $timestampF)  ;
    $readable_period = timespan($period);
    echo " Period: $readable_period";
    echo " (sec:$period)";
    echo "<br>\n";

    form_elem_bucketsz($period);

    echo "<br>\n";
    echo "Excessive level";
    form_elem_maxy();
    echo "fix graph";
    #echo "<br>\n";


    echo "</form>\n";
    echo "</fieldset>\n";
    echo "<br>\n";
}


#####
# Info on the probes

function probes_info_query()
{
  // Query
    $query = sprintf(
   "SELECT *
    FROM   probes
    WHERE  probes.hidden <> 'yes'
    ORDER BY probes.distance");

    $result = mysql_query($query);
    if (!$result) {
        $message  = 'Invalid query: ' . mysql_error() . "\n<br>";
        $message .= 'Whole query: ' . $query;
        die($message);
    }

    $probes = array();

    while ($row = mysql_fetch_assoc($result)) {
        $id = $row['id'];
        $probes[$id]=$row;
        // array_push($probes, $row);
    }
    // print_r($probes);

    return $probes;
}

function get_probename($probeid, & $probesinfo)
{
    $name="Unknown";

    # If not probeid is selected then assume
    #  the selects will return data for all probes
    #
    if (!isset($probeid) || $probeid == "" || $probeid == "all") {
        $name="ALL";
        return $name;
    }

    if (is_array($probesinfo)) {

        $probe = $probesinfo[$probeid];

        if (!is_array($probe)) {
            # No probe existed with that $probeid
            echo "<h3>". __FUNCTION__ ."() ERROR: Invalid probeid:$probeid</h3>";
        } else {
            // extract probename, switch and short location
            $probename = $probe['name'];
            $switch    = $probe['switch'];
            $shortloc  = $probe['shortloc'];

            $name = "$probename/$switch/$shortloc";
            #$name = "$probename/$switch";
        }
    } else {
        $name = "ProbeID:$probeid";
    }
    return $name;
}

function probes_info_show_table($probes)
{
    echo "\n<TABLE border=\"1\">";
    echo "<TR>";
    echo "<TD>id</TD>";
    echo "<TD>probename</TD>";
    echo "<TD>distance</TD>";
    echo "<TD>connected to switch</TD>";
    echo "<TD>short location</TD>";
    echo "<TD>location</TD>";
    echo "<TD>description</TD>";
    echo "</TR>\n";

    foreach ($probes as $row) {
        echo "<TR>";
        echo "<TD>" . $row['id'] . "</TD>";
        echo "<TD>" . $row['name']   . "</TD>";
        echo "<TD>" . $row['distance']  . "</TD>";
        echo "<TD>" . $row['switch'] . "</TD>";
        echo "<TD>" . $row['shortloc']  . "</TD>";
        echo "<TD>" . $row['location']  . "</TD>";
        echo "<TD>" . $row['description']  . "</TD>";
        echo "</TR>\n";
        // print_r($probes);
    }
    echo "</TABLE>\n\n";
}

function probes_info_form_radio($probes, $probeid=0, $page="page01.php") {
    if (!is_array($probes)) {
	echo "\n<br>INVALID INPUT \$probes is not an array<br>\n";
	return;
    }

    echo "<form method=\"get\" action=\"$page\">\n";

    foreach ($probes as $row) {

	#$id       = $row['id'];
	#$name     = $row['name'];
	#$shortloc = $row['shortloc'];
	#$switch   = $row['switch'];
	extract($row, EXTR_OVERWRITE);

	echo "<input name=probeid";
	echo " type=\"radio\" ";
	if ($probeid == $id) {
	    echo "checked ";
	}
	echo " value=$id /> ";
	# The description
	echo "$name@$shortloc switch:$switch (distance:$distance)";
	echo "<br>\n";

#	print_r($row);
    }

    echo "<input type=\"submit\" value=\"Select probe\" />";
#   echo "</p>";
    echo "</form>\n";
}


function probes_info_form_tabel($probes, $selected_id=0, $page="#") {
    if (!is_array($probes)) {
	echo "\n<br>INVALID INPUT \$probes is not an array<br>\n";
	return;
    }

    # Extract stuff from the global _REQUEST array;
    $probeid   = $_REQUEST['probeid'];
    $maxy      = $_REQUEST['maxy'];
    $fromT     = $_REQUEST['fromT'];
    $toT       = $_REQUEST['toT'];
    $bucketsz  = $_REQUEST['bucketsz'];
    $tstampF   = $_REQUEST['tstampF'];
    $tstampT   = $_REQUEST['tstampT'];
    #var_dump($_REQUEST);

    # Finding the probeid
    $selected_id=0;
    if (is_numeric($probeid)) {
	$selected_id = $probeid;
    }

    $default_bucketsz=86400;
    if (!is_numeric($bucketsz)) {
	$bucketsz = $default_bucketsz;
    }

    # Calc dates from Unix timestamps in $tstampX
    if (!isset($fromT) && is_numeric($tstampF)) {
	$fromT = date('Y-m-d H:i:s', $tstampF);
    }
    if (!isset($toT) && is_numeric($tstampT)) {
	$toT = date('Y-m-d H:i:s', $tstampT);
    }

    echo "<fieldset>\n";
    echo "<legend>\n";
    echo "Choose a probe and adjust period\n";
    echo "</legend>\n";

    #echo "selected_id:$selected_id\n";
    echo "<form name='frmProbe' id='frmProbe' method=\"get\" action=\"$page\">\n";

    echo "<TABLE border=\"1\">\n";
    echo "<TR>\n";
    echo " <TD>distance</TD>\n";
    echo " <TD>probename</TD>\n";
    echo " <TD>connected to switch</TD>\n";
    echo " <TD>short location</TD>\n";
    echo " <TD>location</TD>\n";
    echo " <TD>description</TD>\n";
    echo " <TD>id</TD>\n";
    echo "</TR>\n";

    foreach ($probes as $row) {

	extract($row, EXTR_OVERWRITE);

	echo "<TR id='r$id'\n"
	    . " onClick=\"document.getElementById('probeid').value = $id;\n"
	    . " document.getElementById('frmProbe').submit();\n"
            . "               this.style.backgroundColor='red';\"\n"
	    . " onMouseOver=\"this.style.backgroundColor='gray';\"\n";
	if ($selected_id == $id) {
	    echo " style=\"background-color:yellow;cursor:crosshair\"\n";
	    echo " onMouseOut=\"this.style.backgroundColor='yellow';\"\n";
	} else {
	    echo " style=\"cursor:pointer\"\n";
	    echo " onMouseOut=\"this.style.backgroundColor='white';\"\n";
	}
	echo ">\n";

	echo " <TD>$distance" . "</TD>\n";
	echo " <TD>$name"     . "</TD>\n";
	echo " <TD>$switch"   . "</TD>\n";
	echo " <TD>$shortloc" . "</TD>\n";
	echo " <TD>$location" . "</TD>\n";
	echo " <TD>$description  </TD>\n";
	echo " <TD>$id"       . "</TD>\n";
	echo "</TR>\n";
    }
    echo "</TABLE>\n";

    # Call form_elem_timeperiod_from()
    echo "From: ";
    $timestampF = form_elem_timeperiod_from();
    echo date('Y-m-d H:i:s', $timestampF);
    echo "<br>\n";

    # Call form_elem_timeperiod_to()
    echo "To:&nbsp&nbsp&nbsp&nbsp&nbsp";
    $timestampT = form_elem_timeperiod_to();
    echo date('Y-m-d H:i:s', $timestampT);
    echo "<br>\n";

    # Time conversion to human readable
    $period = ($timestampT - $timestampF);
    $readable_period = timespan($period);
    echo " Period: $readable_period\n";
#   echo " (sec:$period)<br>\n";

    echo " <input type=\"hidden\" value=\"$selected_id\" name='probeid' id='probeid' />";

    echo "<br>\n";
    # Aggregation interval
    # Call form_elem_bucketsz()
    form_elem_bucketsz($period);
    /*
    echo 'Aggregation interval/period (bucket size) in sec: <input type="text"';
    echo ' value="' . $bucketsz . '"';
    echo ' name="bucketsz" id="bucketsz"/>';
    echo " (" . timespan($bucketsz) . ")<br>";
    */

    echo "<br>";
    echo "Excessive level";
    form_elem_maxy();
    echo "fix graph";
    echo "<br>\n";

    echo '<input type="submit" value="Submit" />';

    echo "</form>\n";
    echo "</fieldset>\n\n\n";

}

#####
# Functions providing facilities for removing channels from the dataset.
#
#  Global var:
#    $_POST['remove_channels'];
#

/* // Example code:
$remove_channels =& $_POST['remove_channels'];
foreach ($data as $row) {
    $multicast_dst = $row['multicast_dst'];
    if (isset($remove_channels["$multicast_dst"])) {
        // Skip
        $cnt_removed++;
        continue;
    }
}
*/

# Present a form for de-selecting channels
function form_remove_channels(& $channel_list_data) {
     # Channel list data from:
     #  multicast_probe_data_query($probeid, $fromT, $toT);

     # Get hold of the currently checked elements
     $checked =& $_POST['remove_channels'];
     #print_r($checked);

     echo '<form method="post" id="remove_channels" action="">';
     echo "  <p>Remove the following channels:<br />\n";
     echo "  <input type=\"submit\" value=\"Remove\" /><br>\n";

     foreach ($channel_list_data as $row) {
	  $drops         = $row['drops'];
	  $multicast_dst = $row['multicast_dst'];
	  $records       = $row['records'];
	  $streamer_src  = $row['ip_src'];

	  echo '  <input name="';
	  echo "remove_channels[$multicast_dst]\"";
	  echo ' value="remove" type="checkbox" ';
	  if (isset($checked["$multicast_dst"])) {
	       echo "checked ";
	  }
	  echo "/>$multicast_dst (drops:$drops, records:$records";
          echo ", streamer:$streamer_src";
          echo ")<br />\n";
	  unset($drops);
	  unset($multicast_dst);
	  unset($records);
          unset($streamer_src);
     }
     echo "</form>\n";
}

function query_str_remove_channels()
{
    $remove_channels =& $_POST['remove_channels'];
    if (!isset($remove_channels)) {
        return "";
    }

    $str_parts=array();

    foreach ($remove_channels as $key => $value) {
        $str = sprintf("'%s'", mysql_real_escape_string($key));
        array_push($str_parts, $str);
    }
    $str_elems = implode(', ',$str_parts);

    $query = " AND multicast_dst NOT IN ($str_elems)";
    return $query;
}

?>
