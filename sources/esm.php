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




    $gallery = generate_gallery();

    $error = "";
    if(@$_GET['error'] == 'nocity') $error = "<p class=\"text-danger\">Sorry, the city you entered had no matches.</p>";



    $out = "";
    $ui_header = file_get_contents("templates/header.html");;
    $ui_header = str_replace("{CITY}","",$ui_header);
    $out .= $ui_header;
    $ui = file_get_contents("templates/home.html");
    $ui = str_replace("{CITY}","",$ui);
    $ui = str_replace("{CITY_NAME}","",$ui);
    $ui = str_replace("{GALLERY}",$gallery,$ui);
    $ui = str_replace("{ERROR}",$error,$ui);
    $out .= $ui;

    $footer = file_get_contents("templates/footer.html");
    $out .= $footer;

    echo $out;

}



function insert_data($data, $HTML) {
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
/*
    $generated_template = str_replace("{CITY_NAME}",$city_name,$generated_template);
    $generated_template = str_replace("{POP}",$data['pop'],$generated_template);
    $generated_template = str_replace("{TOTAL}",$data['total'],$generated_template);
*/
    $generated_template = str_replace("{DEBUG}",$debug,$generated_template);


    if(!$data) {
        echo "<br />Error: sorry, that didn't work. When you're typing, please actually click one of the items from the autocomplete dropdown.";
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


}