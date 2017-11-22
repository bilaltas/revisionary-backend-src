<?php

// BG Process Settings
ignore_user_abort(true);
set_time_limit(0);


// Get the data
$page_ID = $argv[1];
$sessionID = $argv[2];
$project_ID = $argv[3];
$queue_ID = isset($argv[4]) && is_numeric($argv[4]) ? $argv[4] : "";
$need_to_wait = $queue_ID != "" ? true : false;


// Correct the session ID
session_id($sessionID);


// Call the system
require realpath('.').'/app/init.php';


// Needs to be closed to allow working other PHP codes
session_write_close();





// Logger
$logger = new Katzgrau\KLogger\Logger(
	Page::ID($page_ID)->logDir,
	Psr\Log\LogLevel::DEBUG,
	array(
		'filename' => Page::ID($page_ID)->logFileName,
	    'extension' => 'log', // changes the log file extension
	)
);

// Queue
$queue = new Queue();




// Initialize internalizator
$internalize = new Internalize_v3($page_ID, $queue_ID);

$job_ready = $browser_done = $files_detected = $html_filtred = $css_filtred = false;


echo "Page ID: $page_ID, ";
echo "SessionID: $sessionID, ";
echo "Project ID: $project_ID, ";
echo "Queue ID: $queue_ID, ";
echo "Need to wait: $need_to_wait ";



// JOBS:

// 1. Wait for the queue
if ($queue_ID) $job_ready = $internalize->waitForQueue();


// 2. 	If job is ready to get done, open the site with Chrome
// 2.1. Download the HTML file
// 2.2. Download the CSS files
// 2.3. Download the fonts
// 2.4. Print all the downloaded resources
// 2.5. Take screenshots
// 2.6. Close the site
if ($job_ready) $browser_done = $internalize->browserWorks();

/*
// 3. Parse and detect downloaded files
//if ($browser_done) $files_detected = $internalize->detectDownloadedFiles();


// 4. HTML absolute URL filter to correct downloaded URLs
//if ($files_detected) $html_filtred = $internalize->filterAndUpdateHTML();


// 5. Filter CSS files
// 5.1. Absolute URL filter to correct downloaded URLs
// 5.2. Detect fonts and correct with downloaded ones
//if ($html_filtred) $css_filtred = $internalize->filterAndUpdateCSSfiles();

$css_filtred = true;
// 6. Complete the job!
if ($css_filtred) $iframeLink = $internalize->completeTheJob();
*/