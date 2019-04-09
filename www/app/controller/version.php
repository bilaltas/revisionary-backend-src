<?php


// SECURITY CHECKS

// If not logged in, go login page !!! Change when public revising available
if (!userloggedIn()) {
	header('Location: '.site_url('login?redirect='.urlencode( current_url() )));
	die();
}

// Current user level ID
$currentUserLevel_ID = getUserInfo()['userLevelID'];


// If no page specified or not numeric, go projects page
if ( !isset($_url[1]) || !is_numeric($_url[1]) ) {
	header('Location: '.site_url('projects?invalidversion'));
	die();
}



// Get the version ID
$version_ID = $_url[1];



// THE VERSION INFO

// All my versions
$versionData = Version::ID($version_ID);

// If the specified device doesn't exist, go projects page
if ( !isset($versionData) ) {
	header('Location: '.site_url("project/$project_ID?versiondoesntexist"));
	die();
}




// THE DEVICE INFO

// All my devices
$db->where('version_ID', $version_ID);
$devices = $db->get('devices');
$firstDevice = reset($devices);
//die_to_print($firstDevice);

// If the specified device doesn't exist, go projects page
if ( !isset($firstDevice) ) {
	header('Location: '.site_url("projects?devicedoesntexist"));
	die();
}



$url_to_redirect = site_url('revise/'.$firstDevice['device_ID']);
if ( get('pinmode') == "standard" || get('pinmode') == "browse" ) $url_to_redirect = queryArg('pinmode='.get('pinmode'), $url_to_redirect);
if ( get('privatepin') == "1" ) $url_to_redirect = queryArg('privatepin=1', $url_to_redirect);
if ( get('filter') == "incomplete" || get('filter') == "complete" ) $url_to_redirect = queryArg('filter='.get('filter'), $url_to_redirect);
if ( get('new') == "page" ) $url_to_redirect = queryArg('new=page', $url_to_redirect);



//die_to_print($url_to_redirect);


// If nothing goes wrong, open the first device
header('Location: '.$url_to_redirect);
die();