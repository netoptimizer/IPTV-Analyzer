<?php
ini_set('include_path',ini_get('include_path').':../:');
require_once("design.inc.php");
$title="Index page";
doHeader($title, array('calender' => FALSE));

echo "<h1>$title</h1>";

echo "<h2>IPTV drops statistics</h2>";

echo "<a href='pages/probe_overview.php?probeid=1'>\n";
echo "Probe overview</a>";
echo " collective signal per probe<br>\n";

echo "<a href='pages/channel_view.php'>\n";
echo "View a specific channel\n";
echo "</a><br>\n";


doFooter();
?>
