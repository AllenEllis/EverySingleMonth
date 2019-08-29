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

    write_html($data);

}

if($action == 'debug') {
    $city = $_GET['city']; //todo: sanitize input
    $data = get_data($city);
    var_dump($data);
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

if($action == "jpg") {
    $id = 40668;
    write_jpg($id);
}


function write_html($data) {

    $filepath = "exports/html/".$data['id'].".html";
    $html =  generate_html($data);
echo $html;
    //file_put_contents($filepath,$html) or die("Failed to write output");

    //echo "Rendered output sucessfully to " . $filepath;


}



function write_jpg($id) {
    // Use the first autoload instead if you don't want to install composer

    $url = "exports/html/" . $id;
    $w = '1080';
    $h = '1080';
    $format = 'jpg';

    require_once 'vendor/autoload.php';
    if (!isset($url)) {
        exit;
    }
    $screen = new Screen\Capture($url);
    if (isset($_GET['w'])) { // Width
        $screen->setWidth(intval($w));
    }
    if (isset($_GET['h'])) { // Height
        $screen->setHeight(intval($h));
    }
   /* if (isset($_GET['clipw'])) { // Clip Width
        $screen->setClipWidth(intval($_GET['clipw']));
    }
    if (isset($_GET['cliph'])) { // Clip Height
        $screen->setClipHeight(intval($_GET['cliph']));
    }
    if (isset($_GET['user-agent'])) { // User Agent String
        $screen->setUserAgentString($_GET['user-agent']);
    }
    if (isset($_GET['bg-color'])) { // Background Color
        $screen->setBackgroundColor($_GET['bg-color']);
    }*/
    if (isset($format)) { // Format
        $screen->setImageType($format);
    }

    global $baseURI;
    $fileLocation = $baseURI . 'exports/html/'.$id.".html";
    //echo file_get_contents($fileLocation);die;
    $screen->save($fileLocation);
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
    header("Pragma: no-cache"); // HTTP 1.0.
    header("Expires: 0"); // Proxies.
    header('Content-Type:' . $screen->getImageType()->getMimeType());
    header('Content-Length: ' . filesize($screen->getImageLocation()));
    readfile($screen->getImageLocation());
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

