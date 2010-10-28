/* -*- Mode: C++; tab-width: 8; indent-tabs-mode: nil; c-basic-offset: 2 -*- */

/*
 * This file contains some common javascript function used by the
 * tvprobe webfrontend.
 */

/* This file can be included from another file using:

  <script type="text/javascript" src="../functions.js">
  </script>

*/

function expandRow(probeId) {
    var i = 1;
    while (document.getElementById(probeId+'_'+i) != null) {
	if (document.getElementById(probeId+'_'+i).style.display == 'none') {
	    document.getElementById(probeId+'_'+i).style.display = 'table-row';
	} else {
	    document.getElementById(probeId+'_'+i).style.display = 'none';
	}
	i++;
    }
    return true;
}

