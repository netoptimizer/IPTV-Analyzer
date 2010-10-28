<?php
ini_set('include_path',ini_get('include_path').':../:');
require_once("design.inc.php");
$title="Probe overview";
doHeader($title, array('calender' => TRUE));

echo "<h1>$title</h1>";

require_once("functions.inc.php");
#$displayTiming = TRUE;
$starttime=getMicroTime();

db_connect();

$probes = probes_info_query();
echo "\n<h4>Please select a probe:</h4>\n";
probes_info_form_tabel($probes, $probeid);

# Trick incl the graph php script
#include("../include/graph_probe_drops_bar01.php");
include("../include/graph_probe_drops_bar02.php");

include("../staging/pie01.php");

db_disconnect();

doFooter();
displayTimingInfo($starttime, getMicroTime(), $displayTiming, "php done");
?>
