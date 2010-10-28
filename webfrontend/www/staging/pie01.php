<?php
/**
 * Show pie chart of "Channel Drop Proportion"
 *
 * - Needs to be included from another file which has setup the
 *   DB-connection.
 *
 */

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

ini_set('include_path',ini_get('include_path').':../:staging/');
require_once("functions.inc.php");
# $displayTiming = TRUE;

#echo "<br> TEST:"; var_dump($CONFIG);

//$_REQUEST  = cleanup_input_request();
$probeid   = $_REQUEST['probeid'];
if (!is_numeric($probeid)) {
     die("Cannot proceed -- Missing probeid input");
}

$tstampF = $_REQUEST['tstampF'];
$tstampT = $_REQUEST['tstampT'];
$bucketsz = $_REQUEST['bucketsz'];

# Select all channel within the period
$data =& multicast_probe_data_query_ts($probeid, $tstampF, $tstampT);

//$data = multicast_probe_data_query($probeid, $fromT, $toT);
//db_disconnect();
#print_r($data);

$Canvas =& Image_Canvas::factory('png',
				 array('width' => 400,
				       'height' => 350,
				       'usemap' => true));

// This is how you get the ImageMap object,
// fx. to save map to file (using toHtml())
$Imagemap = $Canvas->getImageMap();

// create the graph
//$Graph =& Image_Graph::factory('graph', array(400, 350));
$Graph =& Image_Graph::factory('graph', $Canvas);

// add a TrueType font
$myfont = '/usr/share/fonts/truetype/freefont/FreeSerif.ttf';
$Font =& $Graph->addNew('font', $myfont);
//$Font =& $Graph->addNew('font', 'Verdana');
// set the font size to 11 pixels
$Font->setSize(8);

$Graph->setFont($Font);

// setup the plotarea, legend and their layout
$Graph->add(
   Image_Graph::vertical(
      Image_Graph::factory('title', array('Channel Drop Proportion', 12)),
      Image_Graph::vertical(
         $Plotarea = Image_Graph::factory('plotarea'),
         $Legend = Image_Graph::factory('legend'),
         70
      ),
      5
   )
);
$Legend->setPlotArea($Plotarea);
$Plotarea->hideAxis();

// create the dataset
$Dataset =& Image_Graph::factory('dataset');

function point($channel, $value, $Dataset, $urldata)
{
     $title = "$channel (drops:$value)";
     $colorid = NULL;
     if ($value < 10) {
          // BAD: This should really be dependend on the period...
	  $colorid = "low";
     }

     $url = "../pages/channel_view.php?channel=$channel";

     $url .= "&tstampF=" . $urldata['tstampF'];
     $url .= "&tstampT=" . $urldata['tstampT'];

     $url .= "&probeid=" . $urldata['probeid'];

     $title .= " records:" . $urldata['records'];
     $title .= " period:"  . $urldata['period'] ."s";

     $Dataset->addPoint($channel, $value,
		     array(
			   'id'  => $colorid,
			   'url' => $url,
			   'alt' => $value,
			   'target' => '_blank',
			   'htmltags' => array('title' => $title)
			   )
		     );
}

# Get hold of the currently deselected channels
$remove_channels =& $_POST['remove_channels'];

$cnt = 0;
$cnt_removed = 0;
foreach ($data as $row) {
    $skips = $row['skips'];
    $drops = $row['drops'];

    $multicast_dst = $row['multicast_dst'];

    if (isset($remove_channels["$multicast_dst"])) {
	// Skip
	$cnt_removed++;
	continue;
    }

    #$urldata['bucketsz']= $bucketsz;
    $urldata['probeid'] = $probeid;
    $urldata['tstampF'] = $tstampF;
    $urldata['tstampT'] = $tstampT;

    $urldata['period'] = $tstampT - $tstampF;
    $urldata['records'] = $row['records'];;

    $cnt++;
    if ($cnt < 50 ){
	point("$multicast_dst", $drops, $Dataset, $urldata);
    }
}
#echo "cnt:$cnt<br>\n";

// create the 1st plot as smoothed area chart using the 1st dataset
$Plot =& $Plotarea->addNew('Image_Graph_Plot_Pie', $Dataset);

//$Plot->setRestGroup(11, 'Other animals');

//FRA: plot_pie_rotate/
// create a Y data value marker
$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
// create a pin-point marker type
$PointingMarker =& $Plot->addNew('Image_Graph_Marker_Pointing_Angular', array(20, &$Marker));
// and use the marker on the 1st plot
$Plot->setMarker($PointingMarker);
// format value marker labels as percentage values
$Marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));



$Plot->Radius = 2;

// set a line color
$Plot->setLineColor('gray');

$Plot->setStartingAngle(-180);

// set a standard fill style
$FillArray =& Image_Graph::factory('Image_Graph_Fill_Array');
$Plot->setFillStyle($FillArray);

$FillArray->addColor('red@0.2');
$FillArray->addColor('yellow@0.2');
$FillArray->addColor('orange@0.2');
$FillArray->addColor('blue@0.2');
$FillArray->addColor('black@0.2', 'rest');
$FillArray->addColor('green@0.2', 'low');

#$FillArray->addColor('green@0.2');
#$FillArray->addColor('blue@0.2');
#$FillArray->addColor('yellow@0.2');
#$FillArray->addColor('red@0.2');
#$FillArray->addColor('orange@0.2');
#$FillArray->addColor('black@0.2', 'rest');


$Plot->explode(10);


// create a Y data value marker
$Marker =& $Plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
// fill it with white
$Marker->setFillColor('white');
// and use black border
$Marker->setBorderColor('black');
// and format it using a data preprocessor
$Marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
$Marker->setFontSize(7);

// create a pin-point marker type
$PointingMarker =& $Plot->addNew('Image_Graph_Marker_Pointing_Angular',
				 array(20, &$Marker));
// and use the marker on the plot
$Plot->setMarker($PointingMarker);

// output the Graph
//$Graph->done();
$filename = generateFilename("pie01", $_REQUEST, 'png');

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

print $output;
#print '<pre>' . htmlspecialchars($output) . '</pre>';

echo "<p>Channels in period:$cnt<br>\n";
echo "Removed channels:$cnt_removed\n";
echo "<p>\n";

form_remove_channels($data);

?>
