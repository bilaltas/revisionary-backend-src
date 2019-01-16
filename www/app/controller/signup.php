<?php

// If already logged in, go projects page
if (userloggedIn()) {

	if (get('redirect') != "") {
		header("Location: ".htmlspecialchars_decode(get('redirect'))); // !!! Check security
		die();
	}

	header('Location: '.site_url('projects'));
	die();

}


$eMail = "";
$fullName = "";
$nonceError = $emptyError = $mailError = $mailExistError = $nameError = $dbError = false;
$errors = [];

// If submitted
if ( post('user-submit') == "Register" ) {

/*
	if ( post("nonce") !== $_SESSION["signup_nonce"] ) {
		$nonceError = true;
		$errors[] = "Please try again";
	}
*/


	$eMail = post('email');
	$fullName = post('full_name');
	$password = post('password');


	// Check if any empty field
	if (!$nonceError && (empty($eMail) || empty($fullName) || empty($password)) ) {
		$emptyError = true;
		$errors[] = "Please don't leave fields blank";
	}


	// E-Mail check
	if (!$nonceError && !$emptyError && !filter_var($eMail, FILTER_VALIDATE_EMAIL)) {
		$mailError = true;
		$errors[] = "Invalid email format";
	}


	// Check if e-mail already exists
	if (!$nonceError && !$emptyError && !$mailError) {
		$db->where("user_email", $eMail);
		$user = $db->getOne("users", "user_ID");
		if ($user) {
			$mailExistError = true;
			$errors[] = "This e-mail address is already registered, please login or <a href='#'>reset</a> your password.";
		}
	}


	// Full name check
	if (!$nonceError && !$emptyError && !$mailError && !$mailExistError && !preg_match("/^[\p{Latin}[A-Za-z ]+$/u", $fullName)) {
		$nameError = true;
		$errors[] = "Only letters and white space allowed on your name";
	}


	// If no error
	if( !$nonceError && !$emptyError && !$mailError && !$mailExistError && !$nameError ) {


		$user_ID = User::ID('new')->addNew(
		    $eMail,
		    $fullName,
		    $password
		);

		if ($user_ID) {

			// Create the session
			$_SESSION['user_ID'] = $user_ID;


			// Update the shares
			$db->where('share_to', $eMail);
			$db->update ('shares', array(
				'share_to' => $user_ID
			));


			if (post('redirect_to') != "") {
				header("Location: ".htmlspecialchars_decode(post('redirect_to'))); // !!! Check security
				die();
			}

			header("Location: ".site_url('projects'));
			die();

		}

		// If not inserted
		$dbError = true;
		$errors[] = "Registration failed: ".$db->getLastError();

	}

}

// Generate new nonce for form
$_SESSION["signup_nonce"] = uniqid(mt_rand(), true);

$page_title = "Signup - Revisionary App";
require view('signup');