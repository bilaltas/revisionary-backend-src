<?php

$status = "initiated";



// NONCE CHECK !!! Disabled for now!
// if ( request("nonce") !== $_SESSION["js_nonce"] ) return;



// Global Vars
$type = ucfirst(request('data-type'));
$action = request('action');
$id = request('id');
$first_parameter = request('firstParameter');
$second_parameter = request('secondParameter');



// Security Check !!!
if (
	   ( // Types
	       $type != "Category"
	       && $type != "Project"
	       && $type != "Page"
	       && $type != "Device"
	       && $type != "User"
	   )

	|| ( // Actions
		   $action != "addNew"
		   && $action != "projectNew"
		   && $action != "pageNew"
		   && $action != "archive"
		   && $action != "delete"
		   && $action != "remove"
		   && $action != "recover"
		   && $action != "rename"
		   && $action != "reorder"
		   && $action != "unshare"
		)
	|| (!is_numeric( $id ) && !filter_var($id, FILTER_VALIDATE_EMAIL) )
) {
	$status = "fail";
}



// If no problem, DB Update
if ($status != 'fail') {

	// Do the action
	$result = $type::ID($id)->$action($first_parameter, $second_parameter);
	$status = $result ? "successful" : "fail-db";

}



// Redirect if not ajax
if ( request('ajax') != true ) {

	header('Location: '.$_SERVER['HTTP_REFERER']);
	die();

}



// CREATE THE RESPONSE
$data = array(

	'status' => $status,

	'data-type' => $type,
	'action' => $action,
	'id' => $id,
	'parameter' => $first_parameter,
	'second-parameter' => $second_parameter,

	'nonce' => request('nonce'),
	'S_nonce' => $_SESSION['js_nonce'],

);
die(json_encode(array(
  'data' => $data
)));
