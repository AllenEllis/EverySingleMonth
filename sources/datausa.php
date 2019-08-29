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
    if(!$placeID) return FALSE;
    $path = "cache/datausa/".$placeID.".json";
    if(@file_exists($path)) {
        $result = json_decode(file_get_contents($path), true);

    } else {
        $result = fetch_meta($placeID);
    }

    if(!$result) return false;

    $town_pieces = explode(",",$result['town_full']);

    $result['town_short'] = $town_pieces[0];

    // Add pretty versions of the numbers
    $result['income']  = "$" . number_format(floatval($result['income_raw']),0,0,",");
    $result['pop']  = number_format(floatval($result['pop_raw']),0,0,",");
    $result['poverty'] = number_format(floatval($result['poverty_raw'])*100,1,".",",")."%";

    $total = floatval($result['pop_raw'])*1000;
    $total = nice_number($total);
    $result['total'] = "$" . $total;

    return $result;
}

function fetch_meta($placeID) {

    $data1 = json_decode(@file_get_contents("https://datausa.io/api/data?Geography=".$placeID."&measures=Household%20Income%20by%20Race,Birthplace,Poverty%20Population&year=latest"),TRUE);
    $data2 = json_decode(@file_get_contents("https://datausa.io/api/data?Geography=".$placeID."&measure=Poverty%20Population&year=latest&Poverty%20Status=0"),TRUE);

    if(!isset($data1['data']['0']['ID Geography'])) return false; // for some reason there was a failure

    $pov_lg = $data1['data']['0']['Poverty Population'];
    $pov_sm = $data2['data']['0']['Poverty Population'];

    $result['id'] = $data1['data']['0']['ID Geography'];
    $result['town_full'] = $data1['data']['0']['Geography'];
    $result['slug'] = $data1['data']['0']['Slug Geography'];
    $result['year'] = $data1['data']['0']['Year'];
    $result['income_raw']  = $data1['data']['0']['Household Income by Race'];
    $result['full_pop_raw']  = $data1['data']['0']['Birthplace'];
    $result['pop_raw']  = floatval($result['full_pop_raw'])  * .875848983900403;
    $result['poverty_raw']  = $pov_sm / $pov_lg;
    $result['image'] = "https://datausa.io/api/profile/geo/".$result['id']."/splash";

    $result['town_full'] = str_replace(" PUMA","",$result['town_full']);

    file_put_contents('cache/datausa/'.$placeID.'.json',json_encode($result));
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