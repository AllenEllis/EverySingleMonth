<?php

/*
// median income
// https://datausa.io/api/data?measure=Household%20Income%20by%20Race&Geography=16000US1777005&year=latest
// {
    //data: [
    //{
    //ID Year: 2017,
    //Year: "2017",
    //Household Income by Race: 34273,
    //Geography: "Urbana, IL",
    //ID Geography: "16000US1777005",
    //Slug Geography: "urbana-il"
    //}
    //],



// population
// https://datausa.io/api/data?Geography=16000US1777005&measures=Birthplace,Birthplace%20Moe&year=latest
// {
    //data: [
    //{
    //ID Year: 2017,
    //Year: "2017",
    //Birthplace: 42141,
    //Birthplace Moe: 1383.6191672566554,
    //Geography: "Urbana, IL",
    //ID Geography: "16000US1777005",
    //Slug Geography: "urbana-il"
    //}
    //],


// poverty
// https://graphite.datausa.io/api/data?Geography=16000US1777005&measure=Poverty%20Population&Poverty%20Status=0&year=latest
//{
    //data: [
    //{
    //ID Year: 2017,
    //Year: "2017",
    //ID Poverty Status: 0,
    //Poverty Status: "Income In The Past 12 Months Below Poverty Level",
    //Poverty Population: 11162,
    //Poverty Population Moe: 809.5548159328064,
    //Geography: "Urbana, IL",
    //ID Geography: "16000US1777005",
    //Slug Geography: "urbana-il"
    //}
    //],


// poverty bigger number
// https://datausa.io/api/data?Geography=16000US1777005&measure=Poverty%20Population&year=latest
//{
    //data: [
    //{
    //ID Year: 2017,
    //Year: "2017",
    //ID Poverty Status: 0,
    //Poverty Status: "Income In The Past 12 Months Below Poverty Level",
    //Poverty Population: 11162,
    //Geography: "Urbana, IL",
    //ID Geography: "16000US1777005",
    //Slug Geography: "urbana-il"
    //}
    //],

*/


// Usage:
 #$placeID = "16000US4261000";
 #$result = get_meta($placeID);
 #var_dump($result);


// WARNING - this code is copy & pasted from working code at autocomplete_cityname.php
// This copy is only used internally for a single call. The other code returns results
// in JSON for the autocomplete. Sorry for the redundancy. Obviously it would be better
// to combine the code bases at some point
function call_API($query) {
    // if the 'term' variable is not sent with the request, exit
    if ( !isset($query) ) {
        exit;
    } else {
        $acTerm = trim($query);
        $acTerm = urlencode(filter_var($acTerm, FILTER_SANITIZE_STRING));
    }

    #$acTerm = "harmony";
    $cityQueryURL = "https://datausa.io/api/search/?kind=geo&hierarchy=place&q=";
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


    foreach ($queryResponse["results"] as $qResp) {

        // DEBUG - Each QueryResponse:
        //print_r($qResp);

        $acData[] = array(
            // cull the desired data from the datausa.io query and add it to the acData array...
            #'label' => $qResp[4],
            #'value' => $qResp[0]."||".$qResp[1]."||".$qResp[2]."||".$qResp[3]."||".$qResp[4]
            'label' => $qResp['name'],
            'value' => $qResp['id'] ."||".$qResp['id']."||".$qResp['id']."||".$qResp['id']."||".$qResp['name'],
            'ID' => $qResp['id']
        );
    }

    if(!isset($acData[0])) return false;

    // code wants a city ID
    if(@$_GET['debug']) var_dump($acData);
    return $acData[0]['ID'];


    // jQuery wants JSON data
    //$out = json_encode($acData);
    //print $out;
    //flush();
    //file_put_contents("test.log"," - $out\r\n",FILE_APPEND);
}


function get_meta($placeID) {
  debug("Running `get_meta`<br>");
    if(!$placeID) return FALSE;

    $cachePath1 = 'cache/datausa/raw_'.$placeID.'_1.json';
    $cachePath2 = 'cache/datausa/raw_'.$placeID.'_2.json';

    if(@file_exists($cachePath1) && @file_exists($cachePath2) && @$_GET['nocache'] != 1) {
        $data1 = file_get_contents($cachePath1);
        $data2 = file_get_contents($cachePath2);
        debug("Loading from cache <tt>$cachePath1</tt> and <tt>$cachePath2</tt><br>");

        if(!$data1 || !$data2) {
            if(!$data1) debug("Error: no metadata found for <tt>data1</tt>, even though there was a cache file. Downloading from API again.");
            if(!$data2) debug("Error: no metadata found for <tt>data2</tt>, even though there was a cache file.  Downloading from API again.");

            debug("Downloading from API <tt>$placeID</tt><br>");
            $data = datausa_curl($placeID);
            $data1 = $data[0];
            $data2 = $data[1];

            //return false;
        }


    } else {
        debug("Downloading from API <tt>$placeID</tt><br>");
        $data = datausa_curl($placeID);
        $data1 = $data[0];
        $data2 = $data[1];
    }

    if(!$data1 || !$data2) {
        if(!$data1) debug("Error: no metadata found for <tt>data1</tt>");
        if(!$data2) debug("Error: no metadata found for <tt>data2</tt>");
      return false;
    }

    $result = datausa_parse($data1, $data2);

    $town_pieces = explode(",",$result['town_full']);

    $result['town_short'] = $town_pieces[0];

    // Add pretty versions of the numbers
    $result['income']  = "$" . number_format(floatval($result['income_raw']),0,0,",");
    $result['pop']  = number_format(floatval($result['pop_raw']),0,0,",");
    $result['poverty'] = number_format(floatval($result['poverty_raw'])*100,1,".",",")."%";

    $total = round(floatval($result['pop_raw']))*1000;
    $total = nice_number($total);
    $result['total'] = "$" . $total;
    debug("Result is " .print_r($result,TRUE));
    return $result;
}

function datausa_curl($placeID) {
    $downloadURL1 = "https://datausa.io/api/data?Geography=".$placeID."&measures=Household%20Income%20by%20Race,Birthplace,Poverty%20Population&year=latest";
    $downloadURL2 = "https://datausa.io/api/data?Geography=".$placeID."&measure=Poverty%20Population&year=latest&Poverty%20Status=0";
    $data1 = @file_get_contents($downloadURL1);
    $data2 = @file_get_contents($downloadURL2);

    // Analyze the data for some integrity before we blindly save it to the cache
    $data1_json = json_decode($data1, TRUE);


    if(!isset($data1_json['data']['0']['ID Geography'])) {
        debug("Error: datausa API did not return valid data at these URLs: <pre>$downloadURL1</pre> <pre>$downloadURL2</pre>");
        do_error(array() ,"Server Error","Sorry, our API provider (DataUSA) is not responding at this time. Please try again later.","503");
        push("Error with DataUSA",$downloadURL1);
        return false;
    }

    debug("Received data from this URL 1: <tt>$downloadURL1</tt><pre>".print_r($data1,TRUE)."</pre><hr/>");
    debug("Received data from this URL 2: <tt>$downloadURL2</tt><pre>".print_r($data2,TRUE)."</pre><hr/>");

    $cachePath1 = 'cache/datausa/raw_'.$placeID.'_1.json';
    $cachePath2 = 'cache/datausa/raw_'.$placeID.'_2.json';

    debug("Ready to write to cache file <tt>$cachePath1</tt> the following data:<pre>$data1</pre>");
    debug("Ready to write to cache file <tt>$cachePath2</tt> the following data:<pre>$data2</pre>");

    // write data to cache
    file_put_contents($cachePath1,$data1);
    file_put_contents($cachePath2,$data2);

    return array($data1, $data2);

}

function datausa_parse($data1,$data2) {

    $data1 = json_decode($data1, TRUE);
    $data2 = json_decode($data2, TRUE);

    // Data USA is weird, it reports duplicate keys with varrying data
    // So we merge them all together, and hope for the best

    $data1set = array_kmerge($data1['data']);
    $data2set = array_kmerge($data2['data']);

    //$data1set = $data1['data'][$offset1];
    //$data2set = $data2['data'][$offset2];

    debug("data1set: <pre>".print_r($data1set,TRUE)."</pre>");
    debug("data2set: <pre>".print_r($data2set,TRUE)."</pre>");

    $pov_lg = $data1set['Poverty Population']; // This is the "total population". Very confusing on DataUSA's part
    $pov_sm = $data2set['Poverty Population']; // This is the "poverty population". We have to divide these two to get ratio

    $result['id'] = $data1set['ID Geography'] ?? NULL;
    $result['town_full'] = $data1set['Geography'] ?? NULL;
    $result['slug'] = $data1set['Slug Geography'] ?? NULL;
    $result['year'] = $data1set['Year'] ?? NULL;
    $result['income_raw']  = $data1set['Household Income by Race'] ?? NULL;
    $result['full_pop_raw']  = $data1set['Poverty Population'] ?? NULL;
    if(@$data1set['Birthplace']) $result['full_pop_raw'] = $data1set['Birthplace']; // idk why this exists for smaller cities, but it used the be the value we used

    if($result['full_pop_raw'] == 0 || !is_int($result['full_pop_raw'])) {
        // We don't have a valid number for population. There's no sense in continuing.
        do_error($result,"No population information found","Sorry, we could not determine the population for <strong>".$result['town_full']."</strong>","410");
    }
    $result['pop_raw']  = floor(floatval($result['full_pop_raw']))  * .714;

    if(!$pov_sm > 0) $result['poverty_raw'] = 0;
    else             $result['poverty_raw']  = $pov_sm / $pov_lg;

    $result['image'] = "https://datausa.io/api/profile/geo/".$result['id']."/splash";

    $result['town_full'] = str_replace(" PUMA","",$result['town_full']);

    $jsonResult = json_encode($result);

    if($jsonResult == "") {
        debug("Error: <tt>datausa_parse()</tt> generated an empty result. Aborting.");
        return false;
    }



    return $result;
}



function nice_number($n) {
    // first strip any formatting;
    $n = (0+str_replace(",", "", $n));

    // is this a number?
    if (!is_numeric($n)) return false;

    $precision = 1;
    //$n = 534680000000;
    if(($n>100000000)) $precision = 0;
    if($n>1000000000)$precision = 1;

    // now filter it;
    if ($n > 1000000000000) return round(($n/1000000000000), $precision).' trillion';
    elseif ($n > 1000000000) return round(($n/1000000000), $precision).' billion';
    elseif ($n > 1000000) return round(($n/1000000), $precision).' million';
    //elseif ($n > 1000) return round(($n/1000), $precision).' thousand';

    return number_format($n);
}


function array_kmerge ($array) {
  $start = 0;
reset($array);
while ($tmp = @each($array))
{
  if(count($tmp['value']) > 0)
  {
   $k[$tmp['key']] = array_keys($tmp['value']);
   $v[$tmp['key']] = array_values($tmp['value']);
  }
}
while($tmp = each($k))
{
  for ($i = $start; $i < $start+count($tmp['value']); $i ++)$r[$tmp['value'][$i-$start]] = $v[$tmp['key']][$i-$start];
  $start = count($tmp['value']);
}
return $r;
}
