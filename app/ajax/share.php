<?php

$data = array();
$status = "initiated";
$shareType = false;


// NONCE CHECK
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





	$data['data'] = array(
		'status' => 'found',
		'user_ID' => $user['user_ID'],
		'user_fullname' => User::ID($user['user_ID'])->fullName,
		'user_nameabbr' => substr(User::ID($user['user_ID'])->firstName, 0, 1).substr(User::ID($user['user_ID'])->lastName, 0, 1),
		'user_link' => site_url('profile/'.User::ID($user['user_ID'])->userName),
		'user_photo' => User::ID($user['user_ID'])->printPicture(),
		'user_avatar' => User::ID($user['user_ID'])->userPicUrl,
		'user_name' => '<span '.(User::ID($user['user_ID'])->userPic != "" ? "class='has-pic'" : "").'>'.(substr(User::ID($user['user_ID'])->firstName, 0, 1).substr(User::ID($user['user_ID'])->lastName, 0, 1)).'</span>',
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


}



// Add the share to DB
$dbData = Array(
	"share_type" => post('data-type'),
	"shared_object_ID" => post("object_ID"),
	"share_to" => $shareType == "email" ? post("email") : $user['user_ID'],
	"sharer_user_ID" => currentUserID()
);
$share_ID = $db->insert('shares', $dbData);

if ($share_ID) {

	$data['data']['status'] = $shareType."-added";


}



// CREATE THE RESPONSE
echo json_encode(array(
  'data' => $data
));
