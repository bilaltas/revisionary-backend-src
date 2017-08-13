<?php

// BG Process Settings
ignore_user_abort(true);
set_time_limit(0);


// Get the data
$page_ID = $argv[1];
$sessionID = $argv[2];
$project_ID = $argv[3];
$queue_ID = $argv[4];
$need_to_wait = isset($queue_ID) && is_numeric($queue_ID) ? true : false;


// Correct the session ID
session_id($sessionID);


// Call the system
require realpath('.').'/app/init.php';


// Needs to be closed to allow working other PHP codes
session_write_close();





// Logger
$logger = new Katzgrau\KLogger\Logger(Page::ID($page_ID)->logDir, Psr\Log\LogLevel::DEBUG, array(
	'filename' => Page::ID($page_ID)->logFileName,
    'extension' => 'log', // changes the log file extension
));

// Queue
$queue = new Queue();




// Initialize internalizator
$internalize = new Internalize_v2($page_ID, $queue_ID);

$job_ready = $browser_done = $files_detected = $html_downloaded = $html_filtred = $css_downloaded = $fonts_downloaded = false;






// JOBS:


// 2. Wait for the queue
if ($queue_ID) $job_ready = $internalize->waitForQueue();


// 3. 	If job is ready to get done, open the site with Chrome
// 3.1. Print all the loaded resources
// 3.2. Take screenshots
// 3.3. Close the site
if ($job_ready) $browser_done = $internalize->browserWorks();


// 4. Parse and detect files to download
if ($browser_done) $files_detected = $internalize->detectFilesToDownload();


// 5. Download HTML
if ($files_detected) $html_downloaded = $internalize->downloadHtml();


// 6. HTML absolute URL filter to correct downloaded URLs
if ($html_downloaded) $html_filtred = $internalize->filterAndUpdateHTML();


// 7.   Download the CSS files
// 7.1. CSS absolute URL filter to correct downloaded URLs
// 7.1. Detect fonts and correct with downloaded ones
if ($html_filtred) $css_downloaded = $internalize->downloadCssFiles();


// 8. Download the font files
if ($css_downloaded) $fonts_downloaded = $internalize->downloadFontFiles();


// 9. Complete the job!
if ($fonts_downloaded) $iframeLink = $internalize->completeTheJob();