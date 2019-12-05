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


function get_meta($placeID) {
  debug("Running `get_meta`<br>");
    if(!$placeID) return FALSE;
    $path = "cache/datausa/".$placeID.".json";

    if(@file_exists($path) && $_GET['nocache'] != 1) {
        $result = json_decode(file_get_contents($path), true);
        debug("Loading from cache <tt>$path</tt><br>");

    } else {
        debug("Downloading from API <tt>$placeID</tt><br>");
        $result = fetch_meta($placeID);
    }

    if(!$result) {
      debug("Error: no metadata found");
      return false;
    }

    $town_pieces = explode(",",$result['town_full']);

    $result['town_short'] = $town_pieces[0];

    // Add pretty versions of the numbers
    $result['income']  = "$" . number_format(floatval($result['income_raw']),0,0,",");
    $result['pop']  = number_format(floatval($result['pop_raw']),0,0,",");
    $result['poverty'] = number_format(floatval($result['poverty_raw'])*100,1,".",",")."%";

    $total = floatval($result['pop_raw'])*1000;
    $total = nice_number($total);
    $result['total'] = "$" . $total;
    debug("Result is " .print_r($result,TRUE));
    return $result;
}

function fetch_meta($placeID) {

    $downloadURL1 = "https://datausa.io/api/data?Geography=".$placeID."&measures=Household%20Income%20by%20Race,Birthplace,Poverty%20Population&year=latest";
    $downloadURL2 = "https://datausa.io/api/data?Geography=".$placeID."&measure=Poverty%20Population&year=latest&Poverty%20Status=0";
    $data1 = json_decode(@file_get_contents($downloadURL1),TRUE);
    $data2 = json_decode(@file_get_contents($downloadURL2),TRUE);

    if(!isset($data1['data']['0']['ID Geography'])) {
      debug("Error: datausa API did not return any data for placeID $placeID");
      return false;
    }

    debug("Received data from this URL 1: <tt>$downloadURL1</tt><pre>".print_r($data1,TRUE)."</pre><hr/>");
    debug("Received data from this URL 2: <tt>$downloadURL2</tt><pre>".print_r($data2,TRUE)."</pre><hr/>");

    // Data USA is weird, it reports duplicate keys with varrying data
    // So we merge them all together, and hope for the best

    $data1set = array_kmerge($data1['data']);
    $data2set = array_kmerge($data2['data']);

    //$data1set = $data1['data'][$offset1];
    //$data2set = $data2['data'][$offset2];

    debug("data1set: <pre>".print_r($data1set,TRUE)."</pre>");
    debug("data2set: <pre>".print_r($data2set,TRUE)."</pre>");


    $pov_lg = $data1set['Poverty Population'];
    $pov_sm = $data2set['Poverty Population'];

    $result['id'] = $data1set['ID Geography'];
    $result['town_full'] = $data1set['Geography'];
    $result['slug'] = $data1set['Slug Geography'];
    $result['year'] = $data1set['Year'];
    $result['income_raw']  = $data1set['Household Income by Race'];
    $result['full_pop_raw']  = $data1set['Poverty Population'];
    if($data1set['Birthplace']) $result['full_pop_raw']  = $data1set['Birthplace']; // idk why this exists for smaller cities, but it used the be the value we used
    $result['pop_raw']  = floatval($result['full_pop_raw'])  * .875848983900403;
    $result['poverty_raw']  = $pov_sm / $pov_lg;
    $result['image'] = "https://datausa.io/api/profile/geo/".$result['id']."/splash";

    $result['town_full'] = str_replace(" PUMA","",$result['town_full']);

    $jsonResult = json_encode($result);

    if($jsonResult == "") {
      debug("Error: <tt>fetch_meta</tt> generated an empty result. Aborting.");
      return false;
    }

    $cachePath = 'cache/datausa/'.$placeID.'.json';

    debug("Ready to write to cache file <tt>$cachePath</tt> the following data:<pre>$jsonResult</pre>");
    file_put_contents($cachePath,$jsonResult);

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
while ($tmp = each($array))
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
