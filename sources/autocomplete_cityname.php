<?php
	// if the 'term' variable is not sent with the request, exit
	if ( !isset($_REQUEST['term']) ) { 
		exit; 
	} else {
		$acTerm = trim($_REQUEST['term']);
		$acTerm = filter_var($acTerm, FILTER_SANITIZE_STRING);
	}

	$cityQueryURL = "https://api.datausa.io/attrs/search/?kind=geo&q=";
	$acData = array();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $cityQueryURL.$acTerm);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json; charset=utf-8'));
	
	$queryResponseJSON = curl_exec($ch);
	
	if (curl_error($ch)) {
		print "Error info: ".curl_error($ch);
	}

	curl_close($ch);

	// Convert to array 
	$queryResponse = json_decode($queryResponseJSON, true);

	foreach ($queryResponse["data"] as $qResp) {
		
		// DEBUG - Each QueryResponse:
		//print_r($qResp);
		
		$acData[] = array(
			// cull the desired data from the datausa.io query and add it to the acData array...
			'label' => $qResp[4],
			'value' => $qResp[0]."||".$qResp[1]."||".$qResp[2]."||".$qResp[3]."||".$qResp[4]
		);
	}

	// jQuery wants JSON data
	print json_encode($acData);
	flush();

// EOF
?>