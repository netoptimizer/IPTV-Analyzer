<?php

/**
 * Graph over the drops from a single channel
 *
 *  This script needs to be included from another script that has the
 *  DB connection and has selected array with the probes.
 *
 */

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

require_once("../functions.inc.php");
require_once("../graphs.inc.php");

#$displayTiming = TRUE;
//$DEBUG=TRUE;

/* A check for DB connectivity */
global $DBH;
if (!$DBH) {
    $err1 = "WARNING - This script is intended to be include, not used directly";
    $err2 = "Allowing your to continue, for testing purposes, establish DB conn";
    echo "<h3>$err1</h3>\n";
    echo "<h4>$err2</h4>\n";
    #exit;
    db_connect();
    $localdb = TRUE;
}

if (is_array($probesinfo)) {
    if ($DEBUG == TRUE)
	echo "Probes info already available<br>\n";
} else {
    // This file is intended to be include, thus this query might
    // already have been performed by the including script
    $probesinfo = probes_info_query();
}

if ($localdb == TRUE) {
    echo "\n<h4>Please select a channel:</h4>\n";
    $channels =& multicast_list_query();
    form_channel_selection($channels, $probesinfo);
}


$_REQUEST  = cleanup_input_request();
$probeid   = $_REQUEST['probeid'];
$maxy      = $_REQUEST['maxy'];
$fromT     = $_REQUEST['fromT'];
$toT       = $_REQUEST['toT'];
$bucketsz  = $_REQUEST['bucketsz'];
$tstampF   = $_REQUEST['tstampF'];
$tstampT   = $_REQUEST['tstampT'];

$channel   = $_REQUEST['channel'];


# INPUT parsing
# -------------
$valid = true;
$probename = get_probename($probeid, $probesinfo);

if (!$channel) {
    echo "<h3>ERROR: cannot generate a channel graph without a channel</h3>\n";
    die();
}

$droptype = $_REQUEST['droptype'];
if (!isset($droptype))
    $droptype = "drops";

$title = "$droptype on $channel (on $probename)";


# GRAPH creation
# --------------
$Graph = & create_graph_usemap01();
$Font  = & $Graph->_font;
$Plotarea = &create_plotarea_with_title02($Graph, $Font, $title);
// Create the dataset
$Dataset =& Image_Graph::factory('dataset');
// Create the 1st plot as 'bar' chart using the 1st dataset
$Plot =& $Plotarea->addNew('bar', array(&$Dataset));

//$Plotarea->hideAxis('x');
$Plotarea->setAxisPadding(3);

$Fill =& Image_Graph::factory('Image_Graph_Fill_Array');
$Fill->addColor('red@0.2', 'DROPS');
$Fill->addColor('darkred', 'EXCESS');
$Plot->setFillStyle($Fill);

// set a line color
//$Plot->setLineColor('blue@0.7');
//$Plot->setLineColor('black');
$Plot->setLineColor('darkred');
//set a standard fill style
//$Plot->setFillColor('red@0.9');

#$percent = calcBarWidthPercentage($bucketsz, $tstampF, $tstampT, $records);
#$Plot->setBarWidth($percent,"%");
setBarWidth($Plot, $bucketsz, $tstampF, $tstampT, $records);

# DATA addPoints
# --------------
$max_y_value=5000;

# query data
if (is_numeric($bucketsz)) {
     $sec = $bucketsz;
} else {
     $sec    = 60 * 60; // One hour
}
if ($DEBUG == TRUE) {
    echo "bucketsz:$bucketsz<br>\n";
    echo "tstampF:$tstampF ". date('Y-m-d H:i:s', $tstampF) ."<br>\n";
    echo "tstampT:$tstampT ". date('Y-m-d H:i:s', $tstampT) ."<br>\n";
    echo "probeid:$probeid<br>\n";
}

$startqd=getMicroTime();
$data = one_channel_data_query_ts($channel, $probeid, $sec, $tstampF, $tstampT);
displayTimingInfo($startqd, getMicroTime(), $displayTiming, "query_data");

//probe_data_show_table($data);
#print_r($data);
if ($localdb == TRUE) db_disconnect();

$urldata['bucketsz']=$bucketsz;
$urldata['probeid'] =$probeid;
$urldata['channel'] =$channel;

/*** Data Points ***/
// Trick we add two datapoints, Start and End of the period we are plotting.
$Dataset->addPoint($tstampF, 0);

// Call data_addPoints()
$records = data_addPoints01($Dataset, $data, $droptype,
			    $maxy, $urldata);
// End datapoint
$Dataset->addPoint($tstampT, 0);

// Fix the Y-axis max
$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
if ($_REQUEST['maxy_fixed'] == "fixed") {
    if (is_numeric($maxy)) {
        $AxisY->forceMaximum($maxy);
    }
}


$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
#$AxisX->setFontAngle(70);
#
# Needs auto adjustment
$AxisX->setLabelOption('dateformat', "Y-m-d\nH:i:s");
#$AxisX->setLabelOption('dateformat', 'Y-m-d');
#$AxisX->setLabelInterval("auto");
#$AxisX->setLabelInterval(200);

$filename = generateFilename("channel_drops_bar01", $_REQUEST, 'png');

// Special output the Graph
$output = $Graph->done(
    array(
        'tohtml' => True,
	'showtime' => $displayTiming,
        'border' => 0,
        'filename' => $filename,
        'filepath' => './graphs/',
        'urlpath' => 'graphs/'
    )
);

if ($records > 0) {
    print $output;
} else {
  echo "<h3>Graph: No data available";
  echo " in choosen period on probe: <em>$probename</em></h3>";
  echo "<h4>Have you choosen a probe in the table?</h4>";
}
?>
