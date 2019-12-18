<?php

// His purpose is to regenerate all existing images
// The process is to first regenerate all HTML files
// And then to trigger a write_png of all HTML files

include("config.php");
include("sources/esm.php");

global $baseURI;

$baseURI = $_GLOBAL['baseURI'];

//$action = $_GET['action'];

//if(!$action) render_ui();

start();

function render_ui() {

}


function start() {

    global $argv;

    $start = $argv[1];
    $end = $argv[2];
    echo "Beginning crawl between $start and $end\r\n";

    // get a list of all HTML files
    $htmls = crawl("prod_exports/html/");

    $i=1;
    foreach($htmls as $html) {
        $i++;
        if($i<$start) continue;
        $result = process($html);
        if(!$result) echo("Failed to pre-processs $html\r\n");
        else echo "\r\n"; // success is implied
        if($i >= $end) exit;
    }

    var_dump($html);
}


function process($html) {
    global $baseURI;
    $url = $baseURI . "/prod_exports/html/" . $html;
    debug("My local HTML copy (old data) <a href='$url'>$html</a><br />");
    debug("See if it exists at <a href='https://everysinglemonth.org/exports/png/".substr($html,0,-5).".png'>online</a><br />");

    // identify city name
    preg_match("/.+?(?=_)/",$html,$city);
    $city = $city[0];

    //$city = "16000US2255000";

    // identify image hash
    preg_match("/(?<=_)[^\.]+/",$html,$image);

    $imagehash = $image[0];

    debug("Found city: $city");
    debug("Found image hash: $imagehash");

    // get the relevant google cache data
    $google_json = find_google($city);
    //debug("I have the data. <pre>$google_json</pre>");

    $image_url = match_hash($google_json, $imagehash);

    if(!$image_url) return false;

    debug("Image URL determined to be <a href='$image_url'><tt>$image_url</tt></a>");

    // next I should run the HTML writer again with new data
    // it lives at index.php?action=doPNG&id=16000US2743000&src='+imageSrc+debugStr

    $writeurl = "http://old.everysinglemonth.org/index.php?action=doPNG&id=$city&src=$image_url";
    $verifyurl = "http://old.everysinglemonth.org/exports/png/".$city."_".$imagehash.".png";

    //echo "Should request this URL: $writeurl \r\n";
    //echo "Should verify at this URL: $verifyurl \r\n";

    reproduce($writeurl, $verifyurl);

    //return(array($writeurl,$verifyurl));

    debug("Click here to regenerate: <a href='$writeurl'>$writeurl</a><hr />");

    //$google_cache = file_get_contents("cache/google/".);
    return true;


}

function mstime() {
    return round(microtime(true) * 1000);
}

function reproduce($writeurl, $verifyurl, $attempt=0) {
    if($attempt > 3) {
        echo "FATAL - reached attempt number $attempt. Aborting. \r\n";
        die;
    }
    $acceptable_delay = 8000; // time in miliseconds. If we don't get a response within this time, assume failure and try again
    $starttime = mstime();
    //echo "The time is $starttime, now sleeping\r\n";
    if($attempt>0) echo "Note: on attempt # ". $attempt . "\r\n";
    echo "Requesting $writeurl \r\n";
    //sleep(rand(0,1));
    $is_ok = http_response($writeurl);
    //sleep(4);

    //$is_ok = TRUE;
    if(!$is_ok) {
        //echo "Fail: got an HTTP error $is_ok with that URL. Trying again... \r\n";
        //reproduce($writeurl,$verifyurl,$attempt+1);
    }
//sleep(100);
    //echo "The time is " . mstime() . "\r\n";

    $time_elapsed = mstime() - $starttime;
    if($time_elapsed > $acceptable_delay) {
        //echo "You were asleep for $time_elapsed, which is longer than $acceptable_delay s, this is bad\r\n";
        $message = "Failed to get a response within $acceptable_delay (took $time_elapsed). Aborting \r\n";
        echo $message;
        file_put_contents("errors.log","$writeurl - $message",FILE_APPEND);
        //reproduce($writeurl,$verifyurl,$attempt+1);
    } else {
        $acceptable_size = 100000;
        //echo "The write request happened in $time_elapsed, which is less than than $acceptable_delay s, continuing as normal\r\n";
        echo "Write completed in $time_elapsed.\r\n";
        echo "Checking image at $verifyurl \r\n";

        //sleep(2);
        $size = get_remote_size($verifyurl);

        echo "Remote size is $size bytes \r\n";

        if($size <$acceptable_size) {
            echo "Error: filesize was only $size, which is less than $acceptable_size. Failing.";
            //reproduce($writeurl,$verifyurl,$attempt+1);
            die;
        } else {
            echo "Verification complete. Advancing to next image... \r\n";
        }

    }
}


function http_response($url, $status = null, $wait = 3)
{
    $time = microtime(true);
    $expire = $time + $wait;

    // we fork the process so we don't have to wait for a timeout
    $pid = pcntl_fork();
    if ($pid == -1) {
        die('could not fork');
    } else if ($pid) {
        // we are the parent
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $head = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(!$head)
        {
            return FALSE;
        }

        if($status === null)
        {
            if($httpCode < 400)
            {
                return TRUE;
            }
            else
            {
                return FALSE;
            }
        }
        elseif($status == $httpCode)
        {
            return TRUE;
        }

        return FALSE;
        pcntl_wait($status); //Protect against Zombie children
    } else {
        // we are the child
        while(microtime(true) < $expire)
        {
            sleep(0.5);
        }
        return FALSE;
    }
}

function get_remote_size($url) {
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    curl_setopt($ch, CURLOPT_NOBODY, TRUE);

    $data = curl_exec($ch);
    $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

    curl_close($ch);
    return $size;
}


function match_hash($json, $imagehash) {
    $json = json_decode($json,TRUE);
    //var_dump($json);

    foreach($json['image_results'] as $image_result) {
        $sourceUrl = $image_result['sourceUrl'];
        $thishash = hash_image($sourceUrl);
        //debug("Checking to see if the hash I'm looking for <tt>$imagehash</tt> matches <tt>$thishash</tt>");
        if($imagehash == $thishash) {
            //debug("Found the match! <tt>$sourceUrl</tt> matches $thishash");
            return $sourceUrl;
        }

        //debug("Checking ".$image_result['sourceUrl']);
    }
    debug("No match found for $imagehash :-(");
    return false;
}

function find_google($city) {
    global $baseURI;
    debug("Attempting to find google data for $city");
    $path = "prod_cache/google/";

    $googles = crawl($path);
    $i = 0;
    foreach($googles as $google) {

        preg_match("/.+?(?=-)/",$google,$result);
        //debug("Check to see if $city matches $google (".$result[0]);
        if($result[0] == $city) {
            $url = $url = $baseURI . "/" . $path . $google;
            debug("I found a matching Google cache for that city at <a href='$url'><tt>$google</tt></a>");
            $json = file_get_contents($path . $google);
            return $json;
        }
        //if($i>0) exit;
    }
}

function crawl($path) {
    debug("Crawling <tt>$path</tt>");
    global $baseURI;
    $files = scandir_by_mtime($path);
    /*$i = 1;
    foreach($files as $file) {
        if($i > 8) break;
        $url = $baseURI . "/" . $folder . "" . $file;
        $out .= str_replace("{URL}",$url,$image_gallery_HTML);
        $i++;
    }*/
    return $files;
}