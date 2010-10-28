<?php

/**
 * Common graph functions.
 *
 * Mostly based upon PEAR module Image_Graph.
 *
 */

require_once 'Image/Graph.php';
require_once 'Image/Canvas.php';

######
# Helper function for calculating the width for the "bar" (for graphs
#  using the Bar Plot), which is a percentage,
#
# The width is adjusted with:  $Plot->setBarWidth($res, "%")
#
function calcBarWidthPercentage($bucketsz, $tstampF, $tstampT, $records=NULL)
{
    $min_bucketsz = 10;
    $percent      = 1.0;

    # Calc based on the number of data records
    if (is_numeric($records) && $records > 0) {
	$percent = 100 / $records;
    }

    # Calc based on the timeperiod and bucket size
    if (is_numeric($bucketsz) && $bucketsz > 0) {
	$period = $tstampT - $tstampF;
	if ($period > 0) {
	    if ($bucketsz < $min_bucketsz)
		$bucketsz = $min_bucketsz;
	    // Calc the max possible date records
	    $max_records = $period / $bucketsz;
	    if ($max_records > 0 && $max_records > $records) {
		// This is often the case
		$percent = 100 / $max_records;
	    }
	}
    }

    return $percent;
}

function setBarWidth(& $Plot, $bucketsz, $tstampF, $tstampT, $records=NULL)
{
    $percent = calcBarWidthPercentage($bucketsz, $tstampF, $tstampT, $records);
    $Plot->setBarWidth($percent,"%");
}

######
# Generate graph names

function generateFilename($prefix, & $input, $suffix = 'png')
{
    $str_parts=array();

    if (!is_array($input)) {
	$err = "Cannot generate a filename, \$input must be an array";
	trigger_error("$err", E_USER_ERROR);
    }

    if(isset($input['probeid'])) {
	$str_parts[]= 'probeid'.$input['probeid'];
    }
    if(isset($input['tstampF'])) {
	$str_parts[]= 'tstampF'.$input['tstampF'];
    }
    if(isset($input['tstampT'])) {
	$str_parts[]= 'tstampT'.$input['tstampT'];
    }
    if(isset($input['bucketsz'])) {
	$str_parts[]= 'bucketsz'.$input['bucketsz'];
    }
    if(isset($input['maxy'])) {
	$str_parts[]= 'maxy'.$input['maxy'];
    }

    $generated_string = implode('_',$str_parts);
    $name = $prefix . "__" . $generated_string .".$suffix";
    return $name;
}

######
# Creating the graph elements

function create_graph_usemap01($width=700, $height=160, $fontsize=7)
{
     $Canvas =& Image_Canvas::factory('png',
				      array('width' => $width,
					    'height' => $height,
					    'usemap' => true));

     // This is how you get the ImageMap object,
     // fx. to save map to file (using toHtml())
     $Imagemap = $Canvas->getImageMap();

     // Create the graph
     //$Graph =& Image_Graph::factory('graph', array(600, 140));
     $Graph =& Image_Graph::factory('graph', $Canvas);

     // add a TrueType font
     //$myfont = '/usr/share/fonts/truetype/freefont/FreeSans.ttf';
     $myfont = '/usr/share/fonts/truetype/freefont/FreeSerif.ttf';

     $Font =& $Graph->addNew('font', $myfont);
     //$Font =& $Graph->addNew('font', 'Verdana');
     //$Font =& $Graph->addNew('font', 'Helvetica');

     // set the font size
     $Font->setSize($fontsize);

     $Graph->setFont($Font);
     #return array(&$Graph, &$Font);
     return $Graph;
}

function create_plotarea_with_title01(&$Graph, &$Font, $title)
{
/* How element are connected is a bit hard to understand, please read:
   http://pear.veggerby.dk/wiki/
    image_graph:getting_started_guide#creating_the_building_blocks
 */

     /*
     $Graph->add(
	  Image_Graph::vertical(
	       Image_Graph::factory('title', array("$title", 10)),
	       Image_Graph::vertical(
		    $Plotarea = Image_Graph::factory('plotarea'),
		    $Legend   = Image_Graph::factory('legend'),
		    90
		    ),
	       5
	       )
	  );
     $Legend->setPlotarea($Plotarea);
     */

     /* Here we start with the Title and add the Plotarea to the Title,
        its might seem a little odd, but it works...
      */
     $Title    = $Graph->addNew('title', array("$title", 10));
     $Plotarea = $Title->addNew('plotarea');
     $Plotarea->setFont($Font);

     return $Plotarea;
}


function create_plotarea_with_title02(&$Graph, &$Font, $title)
{
    $Title    = $Graph->addNew('title', array("$title", 10));
    /* Notice the Axis settings on plotarea */
    $Plotarea = $Title->addNew('plotarea', array('axis', 'axis'));
    $Plotarea->setFont($Font);
    return $Plotarea;
}


function data_addPoints01(& $Dataset, & $data, $droptype, $max_y_value, $urldata)
{
    //Data comes from: $data = probe_data_query($probeid);

    $cnt=0;
    # LOOP: addPoints
    foreach ($data as $row) {
	$cnt++;

	#$date   = $row['datoen'];
	$date   = $row['timestamp'];
	$skips  = $row['skips'];
	$drops  = $row['drops'];
	$value  = $row["$droptype"];

	$timemin = $row['timemin'];
	$timemax = $row['timemax'];
	$period  = $timemax - $timemin;

	$records = $row['records'];

	# "title" info in html map
	$title  = "$droptype:$value";
	$title .= " hour:" . date("H:i:s", $date);
	$title .= " (day:" . date("j.M", $date) . ")";
	$title .= " period:{$period}s";

	if (isset($records)) {
	    $title .= " records:$records";
	}

	//$title  = "drops:$drops skips:$skips";
	//echo "TEST: value:$value $title<br>\n";

	# Color code if data value exceed max allowed data value
	$colorid = 'DROPS';
	if ($value > $max_y_value) {
	      $value  = $max_y_value;
	      $title .= " EXCESSIVE";
	      $colorid = 'EXCESS';
	}

	# Create an URL
	$bucketsz = $urldata['bucketsz'];
	$probeid  = $urldata['probeid'];
	$channel  = $urldata['channel'];

	#echo "bucketsz:$bucketsz ";
	#echo "timemin:$timemin ";
	#echo "timemax:$timemax ";
	# For the link adjust $timemin/max, e.g. sub/add bucketsz period
	if (is_numeric($bucketsz)) {
	    $timemin -= floor($bucketsz / 2);
	    $timemax += floor($bucketsz / 2);
	}
	#echo "timemin2:$timemin ";
	#echo "timemax2:$timemax<br>\n";


	# Calc new bucketsz, by saying how many elements I want on the graph
	$wanted_elements = 50;
	$zoom_period     = $timemax - $timemin;
	$newsz = floor($zoom_period / $wanted_elements);
	if ($newsz < 10) {
	    $newsz = 10;
	}

	$URL = "?tstampF=$timemin&tstampT=$timemax"
	     . "&probeid=$probeid"
	     . "&bucketsz=" . $newsz;

	if (isset($channel)) {
	    $URL .= "&channel=$channel";
	}

        # Add value points to the dataset
	$Dataset->addPoint($date, $value,
			   array(
			       'id'     => "$colorid",
			       'url'    => "$URL",
			       'alt'    => $value,
			       'target' => '_self',
			       'htmltags' => array('title' => $title)
			       )
	    );
    }
    return $cnt;
}

function data_addPoints_fake(& $Dataset, $bucketsz,
			     $tstampF, $tstampT, $urldata=NULL)
{
    $cnt=0;
    # LOOP: addPoints
    for ($i = $tstampF; $i < $tstampT; $i = $i + $bucketsz) {
	$cnt++;

	$date   = $i;
	$value  = 1;

	# "title" info in html map
	$title  = "no drops";
	$title .= " hour:" . date("H:i:s", $date);
	$title .= " (day:" . date("j.M", $date) . ")";

	# Color code
	$colorid = 'NODROPS';

	# Create an URL
	$bucketsz = $urldata['bucketsz'];
	$probeid  = $urldata['probeid'];

	$URL = "?tstampF=$tstampF&tstampT=$tstampT"
	     . "&probeid=$probeid&bucketsz="
	     . $bucketsz;

        # Add value points to the dataset
	$Dataset->addPoint($date, $value,
			   array(
			       'id'     => "$colorid",
			       'url'    => "$URL",
			       'alt'    => $value,
			       'target' => '_self',
			       'htmltags' => array('title' => $title)
			       )
	    );
    }
    return $cnt;
}



?>