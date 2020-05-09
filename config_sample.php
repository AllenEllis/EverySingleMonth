<?php
/**
 * Created by PhpStorm.
 * User: Allen
 * Date: 8/29/2019
 * Time: 3:22 PM
 */

// Rename to config.php and fill out the variables below

global $baseURI;
global $basepath;
global $nodeExecPath;
global $zenserpkey;
global $IPInfotoken;
global $pushovertoken;
global $isPushEnabled;
global $googleAnalyticsId;
global $fbAppId;

$baseURI =  "";          // example: https://yourwebsite
$basepath = "";          // filesystem path (no trailing slash necessary)
$nodeExecPath = "";      // path to node executable (ex. "/usr/bin/node")
$zenserpkey = "";        // API Key from Zenserp (for doing Google Image search results)
$IPInfotoken = "";       // API Key from IP Info (for looking up IP addresses of visitors)
$pushovertoken = "";     // API Key from Pushover (for sending push notifications of site activity)
$isPushEnabled = False;  // Send user actions via Pushover if True
$googleAnalyticsId = ""; // Google Analytics tracking id (provided to frontend for GA tracking)
$fbAppId = "";           // Facebook App Id registered for this app. Required to use JS SDK and Share button.