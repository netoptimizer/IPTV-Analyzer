<?php
# Inspired from: ~/svn/development/php/udv/selfcare/inc/design.inc.php

function doHeader($title="", $options=FALSE) {
    $incdir="../";
    $enable_calender=FALSE;
    $include_javascript=TRUE;

    if (is_array($options)) {
	if (isset($options['staging'])  && $options['staging'] == TRUE) {
	    $incdir="../";
	}
	if (isset($options['calender']) && $options['calender'] == TRUE) {
	    $enable_calender = TRUE;
	}
	if (isset($options['javascript']) && $options['javascript'] == FALSE) {
	    $include_javascript = FALSE;
	}

    }
  ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <title>TVPROBE <?=$title?></title>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <meta name="keywords"    content="iptv,mpeg2,mpeg2-ts,tvprobe,drop detect" />
      <meta name="description" content="tvprobe webfrontend" />
      <meta name="publisher"   content="ComX Networks A/S" />

      <link rel="stylesheet" type="text/css" href="<?php echo $incdir; ?>css/motorola.css">
<?php
       if ($include_javascript == TRUE) {
?>
      <script
	 src="<?php echo $incdir; ?>functions.js" type="text/javascript" language='javascript'>
      </script>
<?php
       }
?>
<?php
       if ($enable_calender == TRUE) {
?>
      <!--Epoch's styles-->
      <link rel="stylesheet" type="text/css"
	    href="<?php echo $incdir; ?>js/epoch_v202_en/epoch_styles.css" />

      <!--Epoch's Code-->
      <script type="text/javascript" src="<?php echo $incdir; ?>js/epoch_v202_en/epoch_classes.js">
      </script>

      <script type="text/javascript">
	/*<![CDATA[*/
        /* You can also place this code in a separate file and link to it like
           epoch_classes.js*/
        var fromT_cal, toT_cal;
        window.onload = function () {
	  fromT_cal = new Epoch('epoch_popup','popup',
                                document.getElementById('fromT'));
	  toT_cal   = new Epoch('epoch_popup','popup',
                                document.getElementById('toT'));
          };
       /*]]>*/
      </script>
<?php
   }
?>
    </head>
<body>
<?php
}

function doFooter () {
    echo "\n";
    echo "</body>\n";
    echo "</html>\n";
}
?>
