<?php

function scandir_by_mtime($folder) {
    $dircontent = scandir($folder);
    $arr = array();
    foreach($dircontent as $filename) {
        if ($filename != '.' && $filename != '..' && $filename != '.gitignore') {
            if (filemtime($folder.$filename) === false) return false;
            $dat = date("YmdHis", filemtime($folder.$filename));
            $arr[$dat] = $filename;
        }
    }
    if (!krsort($arr)) return false;
    return $arr;
}


function generate_gallery() {
    global $baseURI;
    $out = "";
    $folder = "exports/png/";

    $image_gallery_HTML = file_get_contents("templates/image_gallery.html");
    $files = scandir_by_mtime($folder);
    $i = 1;
    foreach($files as $file) {
        if($i > 8) break;
        $url = $baseURI . "/" . $folder . "" . $file;
        $out .= str_replace("{URL}",$url,$image_gallery_HTML);
        $i++;
    }
    return $out;
}


function do_home() {


    $error = "";
    if(@$_GET['error'] == 'nocity') $error = "<p class=\"text-danger\">Sorry, the city you entered had no matches.</p>";

    $out = "";
    $ui_header = file_get_contents("templates/header.html");;
    $ui_header = insert_data(array(),$ui_header);
    $out .= $ui_header;
    $ui = file_get_contents("templates/home.html");

    $data['city'] = '';
    $data['city_name'] = '';
    $data['gallery'] = generate_gallery();
    $data['error'] = $error;

    $ui = insert_data($data,$ui);

    $out .= $ui;

    $footer = file_get_contents("templates/footer.html");
    $out .= $footer;

    echo $out;

    //push("Home");

}



function insert_data($data, $HTML) {
    global $baseURI;

    $data['baseURI'] = $baseURI;
    if(@!$data['city']) $data['city'] = "";
    //var_dump($data);
    foreach($data as $var=>$value) {
        $HTML = str_replace("{".strtoupper($var)."}", $value, $HTML);
        //echo("Replacing {".strtoupper($var)."} with $value<br>");
    }
    return $HTML;
}


function do_error($data,$title="",$message="") {
    $header = file_get_contents("templates/header.html");
    $errorpage = file_get_contents("templates/error.html");
    $footer = file_get_contents("templates/footer.html");

    $ui = $header . $errorpage . $footer;

    if(@$title) $data['ERROR_TITLE'] = $title;
    if(@$message) $data['ERROR_MESSAGE'] = $message;

    $ui = insert_data($data,$ui);

    echo $ui;
    push("Error",$message);
    die;
}

function do_citation() {
    $header = file_get_contents("templates/header.html");
    $citation = file_get_contents("templates/citation.html");
    $footer = file_get_contents("templates/footer.html");

    $ui = $header . $citation . $footer;


    $data = array();

    $ui = insert_data($data,$ui);

    echo $ui;
    push("Citation");
    die;
}

// we are processing the user's initial input
function do_process() {


    global $baseURI;


    $out = "";
    $city = get_param('city');
    $city_name = get_param('city_name');

    if(!$city) {

        if($city_name) {
            // Looks like they didn't use the autocomplete correctly. We don't know the ID but we have
            // a string, so we'll try one last lookup to get their data for them.
            debug("No city ID provided but we have a city name ($city_name). Attemping a lookup.");

            $cityID = call_API($city_name);

            if($cityID) {
                debug("It looks like we got cityID of ". $cityID);
                $city = $cityID;
            }
            else {
                // Still no match
                do_error(array(),"No town found","Sorry, we could not find a town named <strong>$city_name</strong>.");
            }


        }
        else {
            // No city name or ID provided
            header("Location:$baseURI?error=nocity");
        }

    }

    $ui_header = file_get_contents("templates/header.html");;
    $ui_header = str_replace("{CITY}",$city,$ui_header);

    if(@$_GET['debug'] == 1) $debug = "1"; else $debug="0";

    $out .= $ui_header;




    $data = get_data($city);



    $generated_template = file_get_contents("templates/generated.html");
    $generated_template = insert_data($data,$generated_template);

    $generated_template = str_replace("{DEBUG}",$debug,$generated_template);


    if(!$data) {
        echo "<br />Error: sorry, that didn't work. When you're typing, please actually click one of the items from the autocomplete dropdown.";
        push("Error","Code 101");
        return; // todo this all looks like it's in the wrong place. Better way of handling errors?
    }

    $iframe = file_get_contents("templates/iframe.html");
    $iframe = str_replace("{IMAGE}",$data['image'],$iframe);
    $iframe = str_replace("{BASEURI}",$baseURI,$iframe);
    $iframe = str_replace("{CITY}",$city,$iframe);
    //$out .= $iframe;

    $spinner = file_get_contents("templates/spinner.html");

    $generated_template = str_replace("{IFRAME}",$iframe,$generated_template);
    $generated_template = str_replace("{SPINNER}",$spinner,$generated_template);

    $out .= $generated_template;


    //$out .= "<h3>Choose a background image:</h3>";
    //$out .= "<input type='text' id='user-image' placeholder='Paste an image URL' oninput='updateBG(this.value)' size='65' />";

    //$googleimages = getAPIresult('googleimages',$city);

    $footer = file_get_contents("templates/footer.html");
    $out .= $footer;
    echo $out;

    push("Searched",$data['town_full']);

}

function push($title="",$text="") {

    //return;
    $token = "0ac60c7acf80b8"; //todo move to config.php

    if($text != "") $text = $text . " | ";


    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP']; // get Cloudflare original IPs
    $ip = $_SERVER['REMOTE_ADDR'];

    // check the cache
    $cache_path = "cache/ipinfo/" . urlencode($ip) . ".json";
    //echo "loading from $cache_path<br>";
    $ipi = @file_get_contents($cache_path);
    //echo "result is <pre>$queryResponseJSON</pre>";
    if(!$ipi){
        $curl_req = "https://ipinfo.io/$ip?token=$token";
        debug("This is a new IP, querying IPinfo with $curl_req");
        $ipi = @file_get_contents($curl_req);
        debug("I'm writing to " . $cache_path . " with data: <pre>$ipi</pre>");
        @file_put_contents($cache_path,$ipi);
    } else {
        debug("Loaded IP data from cache at <tt>" . $cache_path . "</tt> with data: <pre>$ipi</pre>");
    }

    // make the request

    $ipi = json_decode($ipi,TRUE);

    $org = $ipi['org'];


    $org = preg_replace("(AS([0-9]+) )","",$org);

    // Whitelist certain sources
    if($org == "TeraSwitch Networks Inc.") return;
    if($org == "Amazon.com, Inc.") return;
    if($org == "Google LLC") return;
    if($org == "Facebook, Inc.") return;
    if($org == "Shenzhen Tencent Computer Systems Company Limited") return;
    if($org == "DigitalOcean, LLC") return;
    if($org == "OVH SAS") return;
    if($org == "Apple Inc.") return;
    if($org == "Hetzner Online GmbH") return;

    //$message = $text . " | " . $ipi['city'].", ".$ipi['region']."\r\n".$ipi['org']."\r\n"."https://ipinfo.io/$ip";
    $message = $text . "(" . $ipi['city'].")\r\n".$org."\r\n"."https://ipinfo.io/$ip";
    curl_setopt_array($ch = curl_init(), array(
        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
        CURLOPT_POSTFIELDS => array(
            "token" => "axi4m2oh3m98f41ha2j7y6pap9cwv1",
            "user" => "uuet8bfx4sdt7y57x8sjkhgcbrt85b",
            "title" => "ESM " . $title,
            "message" => $message //"Here We Go!\r\nIP: $ip",
        ),                                                                                                                      CURLOPT_SAFE_UPLOAD => true,
        CURLOPT_RETURNTRANSFER => true,
    ));
    curl_exec($ch);
    curl_close($ch);
    debug("Going to push " . $message);
}