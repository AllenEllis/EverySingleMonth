<?php

function get_google($data) {
    if(!$data) return FALSE;
    if(!$data['id']) return FALSE;
    $city = $data['id'];
    $data['path'] = "cache/google/".$city."-".$data['slug'].".json";

    debug($data['path']);
    if(@file_exists($data['path'])) {
        $result = json_decode(file_get_contents($data['path']), true);

    } else {
        $result = fetch_google($data);
    }

    return $result['image_results'];
}

function fetch_google($data) {

    global $zenserpkey;
    $key = $zenserpkey;
    $town_full = $data['town_full'];
    $id = $data['id'];
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $opts = [
        'q' => $town_full,
        'location' => 'United States',
        'search_engine' => 'google.com',
        'gl' => 'US',
        'hl' => 'en',
        'tbm' => 'isch'
    ];

    curl_setopt($ch, CURLOPT_URL, "https://app.zenserp.com/api/v2/search?" . http_build_query($opts));

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "apikey: $key",
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, TRUE);

    //var_dump($json);

//echo "I would be writing now to ". $data['path'];
    file_put_contents('cache/google/'.$id."-".$data['slug'].'.json',json_encode($json));
    return $json;

    // check remaining requests with
    // https://app.zenserp.com/api/v2/status?apikey=864a7730-ca4d-11e9-8d89-a56264a7866d

}