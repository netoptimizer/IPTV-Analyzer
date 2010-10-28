<?php /* -*- Mode: php; tab-width: 8; indent-tabs-mode: nil; c-basic-offset: 4 -*- */

/**
 * Graph over the drops on a probe
 *
 *  This script needs to be included from another script that has the
 *  DB connection and has selected array with the probes.
 *
 * Feature:
 *  Use of HTML map, which allow a clickable graph.
 *
 */

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

ini_set('include_path',ini_get('include_path').':../:staging/');
require_once("graphs.inc.php");
require_once("functions.inc.php");

$_REQUEST  = cleanup_input_request();
$probeid   = $_REQUEST['probeid'];
$maxy      = $_REQUEST['maxy'];
$bucketsz  = $_REQUEST['bucketsz'];
$tstampF   = $_REQUEST['tstampF'];
$tstampT   = $_REQUEST['tstampT'];


/* A check for DB connectivity */
global $DBH;
if (!$DBH) {
    $err = "No database connection";
    echo "<h3>$err</h3>\n";
    exit;
/*
    db_connect();
    $probes = probes_info_query();
    echo "\n<h4>Please select a probe:</h4>\n";
    probes_info_form_tabel($probes, $probeid);
*/
}

# INPUT parsing
# -------------
$valid = true;
if (!$probeid) {
    $valid = false;
    $err = "<h3>Please choose a valid probe</h3>";
    die("$err");
} else {
    $probe = $probes[$probeid];
    if (!is_array($probe)) {
        $valid = false;
    } else {
        // extract probename, switch and shortloc
        $probename = $probe['name'];
        $switch    = $probe['switch'];
        $shortloc  = $probe['shortloc'];
    }
}
if ($probename == "") {
    $probename = "probeID:$probeid";
}

$droptype = $_REQUEST['droptype'];
if ($droptype == "") {
    // Default drop type
    $droptype = 'drops';
} elseif (($droptype != 'skips') && ($droptype != 'drops')) {
    $droptype = "INVALID-DROPTYPE";
    $valid = false;
}
$title = "$droptype on $probename/$switch/$shortloc";


# GRAPH creation
# --------------
$Graph = & create_graph_usemap01();
$Font  = & $Graph->_font;
$Plotarea = &create_plotarea_with_title01($Graph, $Font, $title);
// Create the dataset
$Dataset =& Image_Graph::factory('dataset');
// Create the 1st plot as 'bar' chart using the 1st dataset
$Plot =& $Plotarea->addNew('bar', array(&$Dataset));

//$Plotarea->hideAxis('x');
$Plotarea->setAxisPadding(3);

$Fill =& Image_Graph::factory('Image_Graph_Fill_Array');
$Fill->addColor('red',     'DROPS');
$Fill->addColor('darkred', 'EXCESS');
$Plot->setFillStyle($Fill);

// set a line color
$Plot->setLineColor('darkred');
//set a standard fill style
//$Plot->setFillColor('red@0.9');

$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
$AxisX->setFontAngle(70);
#
# Needs auto adjustment
#$AxisX->setLabelOption('dateformat', 'Y-m-d (\HH)');
$AxisX->setLabelOption('dateformat', 'Y-m-d');
#$AxisX->setLabelInterval(200);

# DATA addPoints
# --------------
$max_y_value=5000;

# query data
if (is_numeric($bucketsz)) {
    $sec = $bucketsz;
} else {
    $sec    = 24 * 60 * 60; // One day
}
$data = probe_data_query_ts($probeid, $sec, $tstampF, $tstampT);
//probe_data_show_table($data);
//db_disconnect();

$urldata['bucketsz']=$bucketsz;
$urldata['probeid'] =$probeid;

if ($valid == true) {
    // Call data_addPoints()
    $records = data_addPoints01($Dataset, $data, $droptype, $max_y_value, $urldata);
} else {
    $err = "Invalid input data cannot proceed";
    trigger_error("$err", E_USER_ERROR);
}

$filename = generateFilename("probe_drops_bar01", $_REQUEST, 'png');

// Special output the Graph
$output = $Graph->done(
    array(
        'tohtml' => True,
	'showtime' => True,
        'border' => 0,
        'filename' => $filename,
        'filepath' => './graphs/',
        'urlpath' => 'graphs/'
        )
    );

if ($records > 0) {
    print $output;
} else {
    echo "<h3>Graph: No data available in choosen period</h3>";
}
?>
