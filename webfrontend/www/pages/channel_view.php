<?php
ini_set('include_path',ini_get('include_path').':../:../staging/');
require_once("design.inc.php");
$title="Channel view";
doHeader($title, array('calender' => TRUE, 'staging' => FALSE));

echo "<h1>Channel view</h1>\n";

require_once("functions.inc.php");
#$displayTiming = TRUE;
$starttime=getMicroTime();

$_REQUEST  = cleanup_input_request();
$probeid   = $_REQUEST['probeid'];
$maxy      = $_REQUEST['maxy'];
$bucketsz  = $_REQUEST['bucketsz'];
$tstampF   = $_REQUEST['tstampF'];
$tstampT   = $_REQUEST['tstampT'];


$channel = $_REQUEST['channel'];
if (!isset($channel)) {
    echo "<h2>Please select a channel</h2>\n";
} else {
    echo "<h2>Multicast channel:"
	." <a href='?channel=$channel'>$channel</a></h2>\n";
}

db_connect();

# Select all channel within the period
$channels =& multicast_list_query();
#multicast_list_form_select($channels);

$probesinfo = probes_info_query();

form_channel_selection($channels, $probesinfo);
#
# Side effects of form converts the input fromT and toT
#  to a timestamp and store its in the $_REQUEST variable
#
$tstampF = $_REQUEST['tstampF'];
$tstampT = $_REQUEST['tstampT'];


if (isset($channel)) {
    // Get info on multicast channel
    $startqt=getMicroTime();
    $data =& one_channel_info_query_ts($channel, $tstampF, $tstampT);
    displayTimingInfo($startqt, getMicroTime(), $displayTiming, "query_table");

    #print_r($data);

    one_channel_info_show_table($data, $tstampF, $tstampT);

    // Trick incl the graph php script
    include("include/graph_channel_drops_bar01.php");
} else {
    echo "<h4>Cannot generate a channel graph without a channel</h4>";
}

db_disconnect();

function linkto_probe_overview($probename, & $req)
{
    $query = http_build_query($req);
    echo "\n<p><h3>";
    echo "Goto Probe overview: ";
    echo "<a href=\"";
    echo "../pages/probe_overview.php?$query\">";
    echo $probename;
    echo "</a></h3></p>\n";
}
linkto_probe_overview($probename, $_REQUEST);

displayTimingInfo($starttime, getMicroTime(), $displayTiming, "php done");
?>
