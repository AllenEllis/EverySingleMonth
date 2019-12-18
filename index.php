<?php

//phpinfo();

include("config.php");

$baseURI = $_GLOBAL['baseURI'];

require('sources/datausa.php');
require('sources/google.php');
require('sources/esm.php');

$action = @$_GET['action'];


if(!isset($_GET['action'])) {
    do_home();
}

if($action == 'process') {

    do_process();


}

if($action == 'citation') {
    do_citation();
    exit;
}

if($action == 'google') {
    $city = $_GET['city'];
    $data = get_data($city);

    if(!$data) return false;

    $googleimages = get_google($data);

    $images = "";
    $i = 1;
    foreach($googleimages as $image) {
        if($i > 20) break;
        $images .= do_template("image", $image);
        $i++;
    }

    $image_parent = file_get_contents("templates/image_parent.html");
    $image_parent = str_replace("{IMAGES}",$images,$image_parent);

    $out = $image_parent;

    echo $out;

}

if($action == 'render') {
    $city = $_GET['city']; //todo: sanitize input
    $data = get_data($city);

    if(!$data) return "Sorry that is not a valid request.";

    echo generate_html($data);

}

if($action == 'doPNG') {
    //sleep(2);


    global $baseURI;

    $id = $_GET['id']; //todo: sanitize input
    $src = $_GET['src'];
    $hash = hash_image($src);
    debug("Image source is $src <br>");
    // todo check to see if already exists at some point in this flow

    $data = get_data($id);
    if(!$data) return "Sorry that is not a valid request.";
    $data['image'] = $src;

    $HTMLpath = write_html($data);
    //write_png($data);



    $HTMLURI = $baseURI . "/exports/html/" . $id . "_" . $hash . ".html";
    $PNGpath = "exports/png/" . $id . "_" . $hash . ".png";

    /*echo $url;
    echo "<hr />";
    echo $path;
    echo "<hr />";
*/
// Todo after reprocessing old images, I could reintroduce this cache
    if(!file_exists($PNGpath)) {
        unlink($PNGpath);
    }

        // todo so much user sanitization it's not even funny
        $args = "/usr/bin/node screenshot.js " . escapeshellarg($HTMLURI) . " " . escapeshellarg($PNGpath);
        debug("Trying " . $args . " <hr />");
        exec($args, $output);
        //echo implode("\n", $output);


    $PNGURI = $baseURI . "/" . $PNGpath;
    debug( "PNG saved to: <a href='$PNGURI'>$PNGpath</a><hr />
   <a href='$PNGURI'><img src='$PNGURI' width='1080' height='1080' /></a>");

    $data['pnguri'] = $PNGURI;

    $HTML = file_get_contents("templates/generated_saved.html");

    $HTML = insert_data($data,$HTML);

    echo $HTML;

    push("Saved",$data['town_full']);


}
if($action == 'debug') {
    $city = $_GET['city']; //todo: sanitize input
    $data = get_data($city);
    var_dump($data);
}
/*

if($action =='write') {
    // todo - this whole block is deprecated
    $city = $_GET['city']; //todo: sanitize input
    // todo check to see if already exists at some point in this flow

    $data = get_data($city);
    if(!$data) return "Sorry that is not a valid request.";
    $data['image'] = $_GET['src'];
    $src = $_GET['src'];

    $path = write_html($data);
    write_png($data);


}

if($action =='png') {
    // todo - this whole block is deprecated
    $city = $_GET['city'];
    $src = $_GET['src'];
    $hash = hash_image($src);

    global $baseURI;

    $url = $baseURI . "/exports/html/" . $city . "_" . $hash . ".html";
    $path = "exports/png/" . $city . "_" . $hash . ".png";


    // todo check to see if image exists first
    if(!file_exists($path)) {
        // todo so much user sanitization it's not even funny
        $args = "/usr/bin/node screenshot.js " . escapeshellarg($url) . " " . escapeshellarg($path);
        echo "Trying " . $args . " <hr />";
        exec($args, $output);
        //echo implode("\n", $output);
    }

    $pngpath = $baseURI . "/" . $path;
    echo( "PNG saved to: <a href='$pngpath'>$pngpath</a><hr />
   <a href='$pngpath'><img src='$pngpath' width='1080' height='1080' /></a>");

}
*/




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
    //echo "Trying to write HTML<br>";
    global $baseURI;
    $data['image'] = $_GET['src']; // todo: sanatize input
    $data['imagehash'] = hash_image($data['image']);
    $data['path'] = "exports/html/".$data['id']."_".$data['imagehash'].".html";

    if(file_exists($data['path'])) {
        debug( "File exists at <a href='$baseURI/".$data['path']."'>" . $data['path']."</a>");
        return $data['path'];
    }
    $html =  generate_html($data);

    file_put_contents($data['path'],$html) or die("Failed to write output");
    debug("Rendered output successfully to <a href='$baseURI/".$data['path']."'>" . $data['path']."</a>");

    //echo "I made it through<br>";

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



function debug($string) {
    if(@$_GET['debug'] == 1) echo $string;
}

function get_param($param) {
    $param_val = $_GET[$param];

    if($param == "city"){
    }

    return $param_val;
}
