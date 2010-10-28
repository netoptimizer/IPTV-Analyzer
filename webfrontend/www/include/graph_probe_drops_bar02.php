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

#$displayTiming = TRUE;
$startg=getMicroTime();

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

ini_set('include_path',ini_get('include_path').':../:staging/');
require_once("graphs.inc.php");
require_once("functions.inc.php");

$_REQUEST  = cleanup_input_request();
$probeid   = $_REQUEST['probeid'];
#$maxy      = $_REQUEST['maxy'];
$bucketsz  = $_REQUEST['bucketsz'];


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
    $probes = probes_info_query();
    echo "\n<h4>Please select a probe:</h4>\n";
    probes_info_form_tabel($probes, $probeid);

}
$tstampF   = $_REQUEST['tstampF'];
$tstampT   = $_REQUEST['tstampT'];

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

$Plotarea = &create_plotarea_with_title02($Graph, $Font, $title);
#$Title    = $Graph->addNew('title', array("$title", 10));
#$Plotarea = $Title->addNew('plotarea', array('axis', 'axis'));
#$Plotarea->setFont($Font);

// Create the dataset
$Dataset      =& Image_Graph::factory('dataset');
#$Dataset_fake =& Image_Graph::factory('dataset');


# DATA addPoints
# --------------
$max_y_value = $_REQUEST['maxy'];

# query data
if (is_numeric($bucketsz)) {
    $sec = $bucketsz;
} else {
    $sec    = 24 * 60 * 60; // One day
}
$startq=getMicroTime();
$data = probe_data_query_ts($probeid, $sec, $tstampF, $tstampT);
displayTimingInfo($startq, getMicroTime(), $displayTiming, "probe_data_query");
//probe_data_show_table($data);
if ($localdb == TRUE)
    db_disconnect();

$urldata['bucketsz']=$bucketsz;
$urldata['probeid'] =$probeid;

if ($valid == true) {

    // Trick we add two datapoints, Start and End of the period we are plotting.
    $Dataset->addPoint($tstampF, 0);

    // Call data_addPoints()
    $records = data_addPoints01($Dataset, $data, $droptype,
                                $max_y_value, $urldata);

    // End datapoint
    $Dataset->addPoint($tstampT, 0);

#    $fakerec = data_addPoints_fake($Dataset_fake, $bucketsz,
#                                   $tstampF, $tstampT, $urldata);

} else {
    $err = "Invalid input data cannot proceed";
    trigger_error("$err", E_USER_ERROR);
}





// Create the 1st plot as 'bar' chart using the 1st dataset
#$Plot  =& $Plotarea->addNew('line', array(&$Dataset));
#$Plot  =& $Plotarea->addNew('area', array(&$Dataset));
#$Plot  =& $Plotarea->addNew('step', array(&$Dataset));
#$Plot  =& $Plotarea->addNew('Image_Graph_Plot_Band', array(&$Dataset));
#$Plot  =& $Plotarea->addNew('impulse', array(&$Dataset));
$Plot  =& $Plotarea->addNew('bar', array(&$Dataset));
#$Plot2 =& $Plotarea->addNew('bar', array(&$Dataset_fake));

#$percent = calcBarWidthPercentage($bucketsz, $tstampF, $tstampT, $records);
#$Plot->setBarWidth($percent,"%");
setBarWidth($Plot, $bucketsz, $tstampF, $tstampT, $records);

#echo "procent: $procent (rec: $records)<br>";

#$AllDatasets[0] =& $Dataset;
#$AllDatasets[1] =& $Dataset_fake;
#$Plot  =& $Plotarea->addNew('Image_Graph_Plot_Bar', array($AllDatasets, 'stacked'));

//$Plotarea->hideAxis('x');
$Plotarea->setAxisPadding(3);

$Fill =& Image_Graph::factory('Image_Graph_Fill_Array');
$Fill->addColor('red',     'DROPS');
$Fill->addColor('darkred', 'EXCESS');
$Fill->addColor('yellow',  'NODROPS');
$Plot->setFillStyle($Fill);

// set a line color
$Plot->setLineColor('darkred');
//set a standard fill style
//$Plot->setFillColor('red@0.9');

$AxisX =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
#$AxisX->setFontAngle(70);
#
# Needs auto adjustment
$AxisX->setLabelOption('dateformat', "Y-m-d\nH:i:s");
#$AxisX->setLabelOption('dateformat', 'Y-m-d (\HH)');
#$AxisX->setLabelOption('dateformat', 'Y-m-d');
#$AxisX->setLabelInterval(200);

// Fix the Y-axis max
$AxisY =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
if ($_REQUEST['maxy_fixed'] == "fixed") {
    if (is_numeric($max_y_value)) {
        $AxisY->forceMaximum($max_y_value);
    }
}

$filename = generateFilename("probe_drops_bar02", $_REQUEST, 'png');

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
    echo "<h3>Graph: No data available in choosen period</h3>";
}
displayTimingInfo($startg, getMicroTime(), $displayTiming, "graph_bar02 done");
?>
