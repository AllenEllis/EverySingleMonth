<?php

//phpinfo();
/*
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


//Create a S3Client
$s3 = new Aws\S3\S3Client([
    'profile' => 'default',
    'version' => 'latest',
    'region' => 'us-east-2'
]);
*/

include("config.php");

$baseURI = $_GLOBAL['baseURI'];

require('sources/datausa.php');
require('sources/google.php');

$action = @$_GET['action'];


if(!isset($_GET['action'])) {
    $ui_header = file_get_contents("templates/header.html");;
    $ui_header = str_replace("{CITY}","",$ui_header);
    echo $ui_header;
    $ui = file_get_contents("templates/ui.html");
    $ui = str_replace("{CITY}","",$ui);
    echo $ui;

}

if($action == 'process') {
    // we are processing the user's initial input

    $city = $_GET['city']; //todo: sanitize input

    $data = get_data($city);



    $ui_header = file_get_contents("templates/header.html");;
    $ui_header = str_replace("{CITY}",$city,$ui_header);
    echo $ui_header;

    $ui = file_get_contents("templates/ui.html");
    $ui = str_replace("{CITY}",$city,$ui);
    echo $ui;

    if(!$data) {
        echo "<br />Error: sorry, that didn't work. When you're typing, please actually click one of the items from the autocomplete dropdown.";
        return;
    }


    $city = $_GET['city'];

    echo "<br />Generating meme for <strong>".$data['town_full']."</strong><hr />";

    $iframe = file_get_contents("templates/iframe.html");
    $iframe = str_replace("{IMAGE}",$data['image'],$iframe);
    $iframe = str_replace("{BASEURI}",$baseURI,$iframe);
    $iframe = str_replace("{CITY}",$city,$iframe);
    echo $iframe;

    //echo "Don't like the photo?<br /><form action='?'> <input type='submit' value='Choose a Google Photo' /></form>";

    echo "<h3>Choose a background image:</h3>";
    echo "<input type='text' id='user-image' placeholder='Paste an image URL' oninput='updateBG(this.value)' size='65' />";

    //$googleimages = getAPIresult('googleimages',$city);
    $spinner = file_get_contents("templates/spinner.html");
    echo "<div id='google-container'>Loading Google images...<br />$spinner</div>";




}

if($action == 'google') {
    $city = $_GET['city'];
    $data = get_data($city);

    if(!$data) return false;

    $googleimages = get_google($data);

    echo "<h3>Or click a Google Images search</h3>";
    $out = "";
    foreach($googleimages as $image) {
        $out .= do_template("image", $image);
    }

    echo $out;

}

if($action == 'render') {
    $city = $_GET['city']; //todo: sanitize input
    $data = get_data($city);

    if(!$data) return "Sorry that is not a valid request.";

    echo generate_html($data);

}

if($action =='write') {
    $city = $_GET['city']; //todo: sanitize input
    // todo check to see if already exists at some point in this flow

    $data = get_data($city);
    if(!$data) return "Sorry that is not a valid request.";
    $data['image'] = $_GET['src'];
    $src = $_GET['src'];

    $path = write_html($data);
    write_png($data);


}

if($action == 'debug') {
    $city = $_GET['city']; //todo: sanitize input
    $data = get_data($city);
    var_dump($data);
}

if($action =='png') {

    $city = $_GET['city'];
    $src = $_GET['src'];
    $hash = hash_image($src);

    global $baseURI;

    $url = $baseURI . "/exports/html/" . $city . "_" . $hash . ".html";
    $path = "exports/png/" . $city . "_" . $hash . ".png";

    /*echo $url;
    echo "<hr />";
    echo $path;
    echo "<hr />";
*/

    // todo check to see if image exists first
    if(!file_exists($path)) {
        // todo so much user sanitization it's not even funny
        exec("/usr/bin/node screenshot.js " . escapeshellarg($url) . " " . escapeshellarg($path), $output);
        //echo implode("\n", $output);
    }

    $pngpath = $baseURI . "/" . $path;
    echo "PNG saved to: <a href='$pngpath'>$pngpath</a><hr />
   <a href='$pngpath'><img src='$pngpath' width='1080' height='1080' /></a>";

}


if($action == 'gallery') {

    echo "<h3>Existing images</h3>";
    echo "<p>Browse through existing images that other users have generated.</p>";
    echo "<p><a href='index.php'>Go back</a></p>";
    $files = getDirContents("exports/png");
    foreach($files as $file) {
        $url = $baseURI . "/" . $file;
        echo <<<END
<div class="thumbnail">
    <a href="$url">
        <img src="$url" style="width: 256px; height: 256px; float: left" />
    </a>
</div>
END;

    }
}


function getDirContents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        $fakepath = $dir.DIRECTORY_SEPARATOR.$value;
        $ext = pathinfo($value, PATHINFO_EXTENSION);
        if($ext != 'png') continue;

        if(!is_dir($path)) {
            $results[] = $fakepath;
        } else if($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $fakepath;
        }
    }

    return $results;
}



function get_data($city) {
    // Establish temporary data

    /*$data['id'] = 40668;
    $data['image'] = "https://images.unsplash.com/photo-1559873207-3a59620fa819?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1834&q=80";
    $data['town_full'] = "Des Moines, IA";
    $data['pop'] = "154,609";
    $data['income'] = "$49,850";
    $data['poverty'] = "18%";
    $data['total'] = "$154 million";
    $data['town_short'] = "Des Moines";*/

    //$placeID = "16000US1777005";

    $data = get_meta($city);

    if(!$data) return false;


    return $data;
}

function do_template($template, $data) {
    $html = file_get_contents("templates/".$template.".html");

    $html = str_replace("{ID}",$data['position'],$html);
    $html = str_replace("{THUMBNAIL}",$data['thumbnail'],$html);
    $html = str_replace("{SRC}",$data['sourceUrl'],$html);
    return $html;
}

function getAPIresult($service, $city) {


    if($service == 'wikipedia') {
        // http://techslides.com/grab-wikipedia-pictures-by-api-with-php

        $result = file_get_contents("cache/wikipedia-01.json");
        return $result;
    }

    if($service == 'unsplash') {


        return FALSE;
    }

    if($service == 'googleimages') {
        $result = file_get_contents("cache/googleimages-01.json");

        $result = json_decode($result, TRUE);
        $images = $result['image_results'];

        /*foreach($images as $image) {

        }*/

        return $images;
    }

}


//echo generate_html($data);
//write_html($data);

/*if($action == "jpg") {
    $id = 40668;
    write_jpg($id);
}
*/

function hash_image($image) {

    $image = urldecode($image);
    $out = substr(md5($image),0,6);
    //echo "I took $image and made $out";
    return $out;
}

function write_html($data) {
    if(!isset($data)) return false;
    global $baseURI;
    $data['image'] = $_GET['src']; // todo: sanatize input
    $data['imagehash'] = hash_image($data['image']);
    $data['path'] = "exports/html/".$data['id']."_".$data['imagehash'].".html";

    if(file_exists($data['path'])) {
        echo "File exists at <a href='$baseURI/".$data['path']."'>" . $data['path']."</a>";
        return $data['path'];
    }
    $html =  generate_html($data);

    file_put_contents($data['path'],$html) or die("Failed to write output");
    echo "Rendered output successfully to <a href='$baseURI/".$data['path']."'>" . $data['path']."</a>";



    return $data['path'];


}



function write_png($data)
{
    $spinner = file_get_contents("templates/spinner.html");
    echo "<h3>Generating PNG</h3>";
    echo "<div id='png-result'>Please wait...$spinner</div>";

    $city = $_GET['city'];
    $src = $_GET['src'];


    echo <<<END

<script>
window.onload = function pngCall(){


            // ajax update for Google image loading
            var objXMLHttpRequest = new XMLHttpRequest();
            var googleContainer = document.getElementById("png-result");
            objXMLHttpRequest.onreadystatechange = function() {
                if(objXMLHttpRequest.readyState === 4) {
                    if(objXMLHttpRequest.status === 200) {
                        //alert(objXMLHttpRequest.responseText);
                        googleContainer.innerHTML = objXMLHttpRequest.responseText;
                    } else {
                        //alert('Error Code: ' +  objXMLHttpRequest.status);
                        //alert('Error Message: ' + objXMLHttpRequest.statusText);

                        googleContainer.innerHTML = 'Error Code: ' +  objXMLHttpRequest.status + 'Error Message: ' + objXMLHttpRequest.statusText;
                    }
                }
            }
            objXMLHttpRequest.open('GET', 'index.php?action=png&city=$city&src=$src');
            objXMLHttpRequest.send();

        }
</script>

END;

}



function generate_html($data)
{

    global $baseURI;

    $html = file_get_contents("templates/render.html");

    $image = $data['image'];
    $town_full = $data['town_full'];
    $pop = $data['pop'];
    $income = $data['income'];
    $poverty = $data['poverty'];
    $total = $data['total'];
    $town_short = $data['town_short'];

    $legibilityURL = $baseURI . "/static/legibility.png";
    $logoURL = $baseURI . "/static/logo.png";


    $html = str_replace("{IMAGE}", $image, $html);
    $html = str_replace("{TOWN_FULL}", $town_full, $html);
    $html = str_replace("{POP}", $pop, $html);
    $html = str_replace("{INCOME}", $income, $html);
    $html = str_replace("{POVERTY}", $poverty, $html);
    $html = str_replace("{TOTAL}", $total, $html);
    $html = str_replace("{TOWN_SHORT}", $town_short, $html);
    $html = str_replace("{LOGOURL}", $logoURL, $html);
    $html = str_replace("{LEGIBILITYURL}", $legibilityURL, $html);

    return $html;

}

