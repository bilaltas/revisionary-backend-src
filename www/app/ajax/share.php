<?php

$data = array();
$status = "initiated";
$shareType = false;
$shareTo = "";

$data['data']['status'] = $status;


/*
// NONCE CHECK !!! Disabled for now!
if ( post("nonce") !== $_SESSION["js_nonce"] ) {

	$data['data']['status'] = "security-error";
	$data['data']['posted_nonce'] = post("nonce");
	$data['data']['session_nonce'] = $_SESSION["js_nonce"];


	// CREATE THE ERROR RESPONSE
	echo json_encode(array(
	  'data' => $data
	));

	return;
}
*/


// Valid e-mail?
if (!filter_var(post("email"), FILTER_VALIDATE_EMAIL)) {

	$data['data']['status'] = "invalid-email";
	$data['data']['email'] = post("email");
	$data['data']['posted_nonce'] = post("nonce");
	$data['data']['session_nonce'] = $_SESSION["js_nonce"];


	// CREATE THE ERROR RESPONSE
	echo json_encode(array(
	  'data' => $data
	));

	return;
}


// Valid ID?
if ( !is_numeric(post("object_ID")) ) {

	$data['data']['status'] = "invalid-id";


	// CREATE THE ERROR RESPONSE
	echo json_encode(array(
	  'data' => $data
	));

	return;
}



// DB Check for existing user
$db->where('user_email', post("email"));
$user = $db->getOne('users');


// If found
if ( $user !== null ) {


	// If current user
	if ($user['user_ID'] == currentUserID()) {

		$data['data']['status'] = "invalid-email";


		// CREATE THE ERROR RESPONSE
		echo json_encode(array(
		  'data' => $data
		));

		return;
	}


	// Check if already shared
	$db->where( 'share_to', $user['user_ID'] );
	$db->where( 'shared_object_ID', post('object_ID') );
	$shares = $db->get('shares');

	if ( count($shares) > 0 ) {

		$data['data']['status'] = "already-exist";


		// CREATE THE ERROR RESPONSE
		echo json_encode(array(
		  'data' => $data
		));

		return;
	}



	// Change the share type
	$shareType = 'user';
	$shareTo = $user['user_ID'];





	$data['data'] = array(
		'status' => 'found',
		'user_ID' => $user['user_ID'],
		'user_fullname' => getUserData($user['user_ID'])['fullName'],
		'user_nameabbr' => getUserData($user['user_ID'])['nameAbbr'],
		'user_link' => site_url('profile/'.getUserData($user['user_ID'])['userName']),
		'user_photo' => getUserData($user['user_ID'])['printPicture'],
		'user_avatar' => getUserData($user['user_ID'])['userPicUrl'],
		'user_name' => '<span '.(getUserData($user['user_ID'])['userPic'] != "" ? "class='has-pic'" : "").'>'.(getUserData($user['user_ID'])['nameAbbr']).'</span>',
	);

} else { // Not found

	$data['data'] = array(
		'status' => 'not-found'
	);


	// Check if already shared
	$db->where( 'share_to', post('email') );
	$db->where( 'shared_object_ID', post('object_ID') );
	$shares = $db->get('shares');

	if ( count($shares) > 0 ) {

		$data['data']['status'] = "already-exist";


		// CREATE THE ERROR RESPONSE
		echo json_encode(array(
		  'data' => $data
		));

		return;
	}



	// Change the share type
	$shareType = 'email';
	$shareTo = post("email");


}



// Add the share to DB
$share_ID = $db->insert('shares', array(

	"share_type" => post('data-type'),
	"shared_object_ID" => post("object_ID"),
	"share_to" => $shareTo,
	"sharer_user_ID" => currentUserID()

));

if ($share_ID) {

	$data['data']['status'] = $shareType."-added";


	// Notify the user
	if ( post('data-type') == "page" || post('data-type') == "project" ) {


		$object_link = site_url(post('data-type').'/'.post("object_ID"));


		Notify::ID($shareTo)->mail(
			getUserData()['fullName']." shared a ".post('data-type')." with you.",

			"Hello, ".getUserData()['fullName']."(".getUserData()['email'].") shared a ".post('data-type')." with you from Revisionary App. Here is the link to access this ".post('data-type').": <br>

<a href='$object_link' target='_blank'>$object_link</a>"
		);


	}


} else $data['data']['status'] = "not-added";



// CREATE THE RESPONSE
echo json_encode(array(
  'data' => $data
));