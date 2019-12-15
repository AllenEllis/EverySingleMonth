<?php


// do the request
function do_request($acTerm)
{
	$cityQueryURL = "https://datausa.io/api/search/?kind=geo&hierarchy=place&q=";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $cityQueryURL . $acTerm);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json; charset=utf-8'));

	$queryResponseJSON = curl_exec($ch);

	if (curl_error($ch)) {
		print "Error info: " . curl_error($ch);
	}

	curl_close($ch);

	$cache_path = "../cache/datausa/q_" . $acTerm . ".json";
	#echo "I'm writing to " . $cache_path . " with data: <pre>$queryResponseJSON</pre>";
	file_put_contents($cache_path,$queryResponseJSON);
	#die;
	return $queryResponseJSON;
}

function do_cache($acTerm){
	$cache_path = "../cache/datausa/q_" . $acTerm . ".json";
	//echo "loading from $cache_path<br>";
	$queryResponseJSON = @file_get_contents($cache_path);
	//echo "result is <pre>$queryResponseJSON</pre>";
	if($queryResponseJSON) return $queryResponseJSON;
	else	return false;
}


// main code here


	// if the 'term' variable is not sent with the request, exit
	if ( !isset($_REQUEST['term']) ) {
		exit;
	} else {
		$acTerm = trim($_REQUEST['term']);
		$acTerm = urlencode(filter_var($acTerm, FILTER_SANITIZE_STRING));
	}

  #$acTerm = "harmony";

	$acData = array();


	// try to get a cached result
    $queryResponseJSON = do_cache($acTerm);

    // if false, there wasn't a cache, so do a request
    if(!$queryResponseJSON) $queryResponseJSON = do_request($acTerm);

	// Convert to array
	$queryResponse = json_decode($queryResponseJSON, true);


	foreach ($queryResponse["results"] as $qResp) {

		// DEBUG - Each QueryResponse:
		//print_r($qResp);

		$acData[] = array(
			// cull the desired data from the datausa.io query and add it to the acData array...
			#'label' => $qResp[4],
			#'value' => $qResp[0]."||".$qResp[1]."||".$qResp[2]."||".$qResp[3]."||".$qResp[4]
			'label' => $qResp['name'],
			'value' => $qResp['id'] ."||".$qResp['id']."||".$qResp['id']."||".$qResp['id']."||".$qResp['name']
		);
	}

	// jQuery wants JSON data
	$out = json_encode($acData);
	print $out;
	flush();
	//file_put_contents("test.log"," - $out\r\n",FILE_APPEND);

// EOF
?>
