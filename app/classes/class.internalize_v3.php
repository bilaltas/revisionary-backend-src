<?php
use Cocur\BackgroundProcess\BackgroundProcess;


/*
	PLAN:

	1. Browser works
	1.1. Download HTML
	1.2. Download CSS files
	1.3. Download Fonts
	2. Parse the downloaded files logs
	2.1. Detect downloaded CSS files
	2.2. Detect downloaded fonts
	3. Filter and update HTML file
	4. Filter and update CSS files

*/


class Internalize_v3 {



	// The Page ID
	public $page_ID;


	// The Queue ID
	public $queue_ID;


	// The resources list
	public $resources = array();


	// HTML file to download
	public $htmlToDownload = array();

	// CSS files to download
	public $cssToDownload = array();

	// JS files to download !!! Not yet
	public $jsToDownload = array();

	// Fonts to download
	public $fontsToDownload = array();

	// Images to download !!! Not yet
	public $imagesToDownload = array();



	// When initialized
	public function __construct($page_ID, $queue_ID) {

		// Set the page ID
		$this->page_ID = $page_ID;
		$this->queue_ID = $queue_ID;

	}




	// JOBS:


	// 2. Wait for the queue
	public function waitForQueue() {
		global $db, $queue, $logger;


		// Is current job ready to be done
		$job_ready = $queue->isReady($this->queue_ID);
		$job_status = $queue->info($this->queue_ID)['queue_status'];


		// 2. Wait for the job availability in queue
		$interval = 2;
		while (!$job_ready && $job_status == "waiting") {

			$logger->info("Waiting $interval second(s) for the queue.");
			sleep($interval);
			$job_ready = $queue->isReady($this->queue_ID);
			$job_status = $queue->info($this->queue_ID)['queue_status'];

		}

		return true;

	}


	// 3. 	If job is ready to get done, open the site with Chrome
	// 3.1. Print all the loaded resources
	// 3.2. Take screenshots
	// 3.3. Close the site
	public function browserWorks() {
		global $db, $queue, $logger;


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Browser job is starting.");


		// Log
		$logger->info("Browser job is starting.");


		// Get page and project IDs
		$page_ID = $this->page_ID;
		$project_ID = Page::ID($page_ID)->getPageInfo('project_ID');


		// Screenshots and HTML file
		//$page_image = Page::ID($page_ID)->pageDeviceDir."/".Page::ID($page_ID)->getPageInfo('page_pic');
		$page_image_name = "page.jpg";
		$page_image = Page::ID($page_ID)->pageDeviceDir."/".$page_image_name;
		//$project_image = Page::ID($page_ID)->projectDir."/".Project::ID( $project_ID )->getProjectInfo('project_pic');
		$project_image_name = "proj.jpg";
		$project_image = Page::ID($page_ID)->projectDir."/".$project_image_name;

		$htmlFile = Page::ID($page_ID)->pageFile;
		$resourcesFile = Page::ID($page_ID)->logDir.'/resources.log';


		// Are they exist?
		$page_captured = file_exists($page_image);
		$project_captured = file_exists($project_image);
		$html_captured = file_exists($htmlFile);
		$resourcesFile_captured = file_exists($resourcesFile);


		// If both already captured and page is already internalized, return
		if ( $project_captured && $page_captured && $html_captured && $resourcesFile_captured ) {


			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "Browser job is skipped.");


			// Log
			$logger->info('HTML, Page/Project Screenshots and resources.log file are already exist. Browser job is skipped.');


			return true;
		}


		// Get info
		$url = Page::ID($page_ID)->remoteUrl;
		$logDir = Page::ID($page_ID)->logDir;
		$deviceID = Page::ID($page_ID)->getPageInfo('device_ID');
		$width = Device::ID($deviceID)->getDeviceInfo('device_width');
		$height = Device::ID($deviceID)->getDeviceInfo('device_height');
		$page_image = $page_captured ? "done" : $page_image;
		$project_image = $project_captured ? "done" : $project_image;
		$htmlFile = $html_captured ? "done" : $htmlFile;
		$resourcesFile = $resourcesFile_captured ? "done" : $resourcesFile;


/*
		// Process directories - SlimerJS - Firefox !!!
		$slimerjs = bindir."/slimerjs-0.10.3/slimerjs";
		$capturejs = dir."/app/bgprocess/firefox.js";

		$process_string = "$slimerjs $capturejs $url $width $height $page_image $project_image $logDir";
*/


		// Process directories - NodeJS - Chrome
		$nodejs = bindir."/nodejs-mac/bin/node";
		$scriptFile = dir."/app/bgprocess/chrome_v2.js";
		$process_string = "$nodejs $scriptFile --url=$url --viewportWidth=$width --viewportHeight=$height --pageScreenshot=$page_image --projectScreenshot=$project_image --htmlFile=$htmlFile --resourcesFile=$resourcesFile --logDir=$logDir --delay=1000";


		// Do the process
		$process = new BackgroundProcess($process_string);
		$process->run($logDir."/browser.log", true);


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Browser job has started.");


		// LOGS
		$logger->info("Browser jobs process string: ". $process_string);
		$logger->info("Browser jobs process ID: ". $process->getPid());


		// Wait for the process done
		$timeout = 30; // seconds
		$eta = 0;
		while (
			$process->isRunning() &&
			$queue->info($this->queue_ID)['queue_status'] == "working"
		) {

			$waitfor = 2; // seconds

			$logger->info("Waiting $waitfor seconds for the process to be complete");
			sleep($waitfor);

			$eta = $eta + $waitfor;

			if ($eta >= $timeout) {

				// Update the queue status
				$queue->update_status($this->queue_ID, "error", "Resource file timeout.");


				// Log
				$logger->error("Resource file timeout.");

				break;

				return false;
			}
		}


		// Process Check
		if ( !$process->isRunning() ) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "Browser job is done.");


			// Log
			$logger->info("Browser job is done.");

		}


		// Re-check the files
		$page_captured = file_exists($page_image);
		$project_captured = file_exists($project_image);
		$html_captured = file_exists($htmlFile);
		$resourcesFile_captured = file_exists($resourcesFile);



		// Add image names to database
		if ( $page_captured ) {

			$db->where('page_ID', $page_ID);
			$db->update('pages', array(
				'page_pic' => $page_image_name
			), 1);


			// Log
			$logger->info("Page screenshot is taken: ".$page_image_name);

		}

		if ( $project_captured ) {

			$db->where('project_ID', $project_ID);
			$db->update('projects', array(
				'project_pic' => $project_image_name
			), 1);


			// Log
			$logger->info("Project screenshot is taken: ".$project_image_name);

		}



		// Wait for the resources file creation
		$resources_file = $logDir."/resources.log";
/*
		while (
			!file_exists($resources_file) &&
			$queue->info($this->queue_ID)['queue_status'] == "working"
		) {

			$logger->info("Waiting 2 seconds for the resources.log file");
			sleep(2);

		}
*/


		// Resources file check
		if (!file_exists($resources_file)) {


			// Update the queue status
			$queue->update_status($this->queue_ID, "error", "Resource file is not exist.");


			// Log
			$logger->error("Resource file is not exist.");


			return false;
		}


		// HTML file check
		if (!file_exists($htmlFile)) {


			// Update the queue status
			$queue->update_status($this->queue_ID, "error", "HTML file is not exist.");


			// Log
			$logger->error("HTML file is not exist.");


			return false;
		}


		// Parse the resources
		$resources = preg_split('/\r\n|[\r\n]/', trim(file_get_contents($resources_file)));
		$last_line = end($resources);

		// Wait for the resources completely written
		while ( $last_line != "DONE" && $queue->info($this->queue_ID)['queue_status'] == "working" ) {

			$logger->info("Waiting 2 seconds for the resources file to complete. Last Resource: ". $last_line);
			sleep(2);
			$resources = preg_split('/\r\n|[\r\n]/', trim(file_get_contents($resources_file)));
			$last_line = end($resources);

		}

		$resources = array_unique($resources);
		array_pop($resources);
		$this->resources = $resources;


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Resources list is ready.");

		// Log
		$logger->info("Resources list is ready.");


		return true;

	}


	// 4. Parse and detect files to download
	public function detectFilesToDownload() {
		global $db, $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Started parsing the resources.");

		// Log
		$logger->info("Start parsing the resources.", $this->resources);

		$count = 0;
		foreach ($this->resources as $resource) {

			$content_type = "";
			$resource_url = trim($resource);

			// If content type is specified
			if ( strpos($resource, ' -> ') !== false ) {

				$resource = explode(' -> ', $resource);
				$content_type = trim($resource[0]);
				$resource_url = trim($resource[1]);

			}


			// Is valid URL?
			$url = parseUrl($resource_url);
			if ( !$url ) {

				$logger->info("Invalid URL skipped: $resource_url");
				continue;
			}


			// Parse the URL
			$path_parts = pathinfo($url['path']);
			$extension = isset($path_parts['extension']) ? strtolower($path_parts['extension']) : "";


			// If the resource is not belong to the domain, pass it.
			if ( $url['domain'] != parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain'] ) {

				$logger->info("Resource skipped: $resource_url ***".$url['domain']."--".parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain']);
				continue;
			}



			// Register the HTML file !!! NO NEED FOR NOW
			if ($content_type == "text/html") {


				// If redirected?
				if ( $resource_url != Page::ID($this->page_ID)->remoteUrl ) {

					$old_url = Page::ID($this->page_ID)->remoteUrl;

					// Update the remote URL on database
					$data = array(
						'page_url' => $resource_url
					);
					$db->where ('page_ID', $this->page_ID);
					if ( $db->update('pages', $data) )
					    $logger->info("Page URL has updated. Old URL: $old_url", $data);
					else
					    $logger->error("Page URL couldn't be updated. ".$db->getLastError()." - Old URL: $old_url", $data);
				}

				// Add to the list
				$this->htmlToDownload[] = $resource_url;
				$logger->info("HTML page added to the download queue: $resource_url");


			} elseif (
				$content_type == "text/css" ||
				$extension == "css"
			) {


				// Add to the list
				$this->cssToDownload[] = $resource_url;
				$logger->info("CSS file added to the download queue: $resource_url");


			} elseif (
				strpos($content_type, 'javascript') !== false ||
				$content_type == "application/javascript" ||
				$extension == "js"
			) {


				// Add to the list
				$this->jsToDownload[] = $resource_url;
				$logger->info("JS file added to the download queue: $resource_url");


			} elseif (
				strpos($content_type, 'font') !== false ||
				$content_type == "font/otf" ||
				$content_type == "font/ttf" ||
				$content_type == "font/woff" ||
				$content_type == "font/woff2" ||
				$content_type == "image/svg+xml" ||
				$content_type == "application/x-font-ttf" ||
				$content_type == "application/x-font-truetype" ||
				$content_type == "application/x-font-opentype" ||
				$content_type == "application/font-woff" ||
				$content_type == "application/font-woff2" ||
				$content_type == "application/octet-stream" ||
				$content_type == "application/vnd.ms-fontobject" ||
				$content_type == "application/font-sfnt" ||
				$extension == "ttf" ||
				$extension == "otf" ||
				$extension == "woff" ||
				$extension == "woff2" ||
				$extension == "svg" ||
				$extension == "eot"
			) {


				// Add to the list
				$this->fontsToDownload[] = $resource_url;
				$logger->info("Font file added to the download queue: $resource_url");


			} elseif (
				strpos($content_type, 'image/') !== false ||
				$extension == "jpg" ||
				$extension == "jpeg" ||
				$extension == "png" ||
				$extension == "gif"
			) {


				// Add to the list
				$this->imagesToDownload[] = $resource_url;
				$logger->info("Image file added to the download queue: $resource_url");


			} else {


				// Add to the list
				$logger->info("Couldn't added to any list: $resource_url");


			}


			$count++;
		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Parsing $count resources finished.");

		// Log
		$logger->info("Parsing $count resources finished.");


		return true;
	}


	/*
		DEPRECATED !!! Browser will do the job.
	*/
	// 5. Download HTML
	public function downloadHtml() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Started downloading HTML file.");

		// Log
		$logger->info("Started downloading HTML file.");


		// Do nothing if already saved
		if ( file_exists( Page::ID($this->page_ID)->pageFile ) ) {

			// Log
			$logger->info("HTML file is already downloaded");

			return false;

		}


		// Create the log folder if not exists
		if ( !file_exists(Page::ID($this->page_ID)->logDir) )
			mkdir(Page::ID($this->page_ID)->logDir, 0755, true);
		@chmod(Page::ID($this->page_ID)->logDir, 0755);


		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_html.log", "[".date("Y-m-d h:i:sa")."] - Started \r\n", FILE_APPEND);



		// PHP METHOD

		// For the SSL Problem
		$ContextOptions = array(
		    "ssl" => array(
		        "verify_peer" => false,
		        "verify_peer_name" => false,
		    ),
	        "http" => array (
	            "follow_location" => true, // follow redirects
	            "user_agent" => "Mozilla/5.0" // Use the current user's agent !!!
	        )
		);

		// Get the HTML
		$content = @file_get_contents(Page::ID($this->page_ID)->remoteUrl, FILE_TEXT, stream_context_create($ContextOptions));


		// If fails, try without context
		if (!$content)
			$content = @file_get_contents(Page::ID($this->page_ID)->remoteUrl, FILE_TEXT);


		$html = $content;


		// Extract the encode
		$charset = "";
		$headers = @get_headers(Page::ID($this->page_ID)->remoteUrl, 1);
		$content_type = $headers['Content-Type'];
		if ( is_array($content_type) )
			$content_type = end($content_type);
		$parsed_content_type = explode(';', $content_type);
		if (count($parsed_content_type) > 1)
			$charset = strtoupper(substr(array_values(array_filter($parsed_content_type, function ($v) {
				return substr($v, 0, 9) === ' charset=';
			}))[0], 9));

		// Log the headers
		file_put_contents(Page::ID($this->page_ID)->logDir."/headers.log", print_r($headers, true), FILE_APPEND);


		// Correct the charset
		if ($charset != "" )
			$html = mb_convert_encoding($content, "UTF-8", $charset);



		// SAVING:

		// Create the folder if not exists
		if ( !file_exists(Page::ID($this->page_ID)->pageDir."/") )
			mkdir(Page::ID($this->page_ID)->pageDir."/", 0755, true);
		@chmod(Page::ID($this->page_ID)->pageDir."/", 0755);


		// Save the file if not exists - PHP METHOD
		if ( !file_exists( Page::ID($this->page_ID)->pageFile ) )
			$saved = file_put_contents( Page::ID($this->page_ID)->pageFile, $html, FILE_TEXT);



		// LOG:
		$logger->info("PROJECT ID: ".Page::ID($this->page_ID)->projectId." | PAGE ID: ".$this->page_ID." | DEVICE: ".Page::ID($this->page_ID)->pageDevice." | VERSION: ".Page::ID($this->page_ID)->pageVersion);



		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_html.log", "[".date("Y-m-d h:i:sa")."] - Finished".(!$saved ? " <b>WITH ERRORS</b>":'')." \r\n", FILE_APPEND);
		rename(Page::ID($this->page_ID)->logDir."/_html.log", Page::ID($this->page_ID)->logDir.(!$saved ? '/__' : '/')."html.log");



		if ($saved) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "HTML Saved.");


			$logger->info("HTML Downloaded: ".Page::ID($this->page_ID)->remoteUrl);
			return true;

		}

		// Update the queue status
		$queue->update_status($this->queue_ID, "error", "HTML couldn't saved.");
		$logger->error("HTML couldn't downloaded: ".Page::ID($this->page_ID)->remoteUrl);
		return false;
	}


	// 6. HTML absolute URL filter to correct downloaded URLs
	public function filterAndUpdateHTML() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "HTML Filtering started.");


		// Log
		$logger->info("HTML Filter started.");


		// Do nothing if already saved
		if ( !file_exists( Page::ID($this->page_ID)->pageFile ) ) {

			// Log
			$logger->error("HTML file is not exist.");

			// Update the queue status
			$queue->update_status($this->queue_ID, "error", "HTML couldn't be filtred.");

			return false;

		}

		// Get the HTML from the downloaded file
		$html = file_get_contents(Page::ID($this->page_ID)->pageFile);


		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Started \r\n", FILE_APPEND);



		// Add Necessary Spaces - done for a bug
		function placeNeccessarySpaces($contents) {
			$quotes = 0; $flag = false;
			$newContents = '';
			for($i = 0; $i < strlen($contents); $i++){
			    $newContents .= $contents[$i];
			    if(is_array($contents) && $contents[$i] == '"') $quotes++;
			    if($quotes%2 == 0){
			        if(is_array($contents) && $contents[$i+1] !== ' ' && $flag == true) {
			            $newContents .= ' ';
			            $flag = false;
			        }
			    }
			    else $flag = true;
			}
			return $newContents;
		}
		$html = placeNeccessarySpaces($html);


		// INCLUDE THE BASE
		$html = preg_replace_callback(
	        '/<head([\>]|[\s][^<]*?\>)/i',
	        function ($urls) {

		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Base Added: '".Page::ID($this->page_ID)->remoteUrl."' \r\n", FILE_APPEND);

		        return $urls[0]."<base href='".Page::ID($this->page_ID)->remoteUrl."'>";

	        },
	        $html
	    );


		// CONVERT ALL HREF, SRC ATTRIBUTES TO ABSOLUTE  !!! - Correct with existing revisionary page urls ??? (target="_parent")
		$html = preg_replace_callback(
	        '/<(?<tagname>link|a|script|img)\s+[^<]*?(?<attr>href|src)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) {


		        // Found parts
		        $full_tag = $urls[0];
		        $attribute = $urls['attr'];
		        $the_url = isset($urls['value2']) ? $urls['value2'] : $urls['value'];


		        // Absoluted URL
		        $new_url = url_to_absolute(Page::ID($this->page_ID)->remoteUrl, $the_url);


		        if (parseUrl($the_url)['host'] != "" )
		        	$new_url = url_to_absolute(parseUrl($the_url)['full_host'], $the_url);

		        // If not on our server, don't do it
		        if (parseUrl($the_url)['domain'] != "" && parseUrl($the_url)['domain'] != parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain'] )
		        	$new_url = $the_url;


		        // Update the HTML element
	            $new_full_tag = str_replace(
	            	"$attribute='$the_url", // with single quote
	            	"$attribute='$new_url",
	            	$full_tag
	            );

	            $new_full_tag = str_replace(
	            	"$attribute=\"$the_url", // with double quotes
	            	"$attribute=\"$new_url",
	            	$new_full_tag
	            );


		        // TEMP Specific Log !!!
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - ABSSSS: '".print_r( $urls, true)."' \r\n", FILE_APPEND);


		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Absoluted: '".$the_url."' -> '".$new_url."' \r\n", FILE_APPEND);
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Absoluted HTML: '".$full_tag."' -> '".$new_full_tag."' \r\n", FILE_APPEND);


	            return $new_full_tag;
	        },
	        $html
	    );


	    // INTERNALIZE CSS FILES
		$count_css = 0;
		$html = preg_replace_callback(
	        '/<(?<tagname>link)\s+[^<]*?(?:href)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) use(&$count_css) {

				// The found URL
		        $the_url = isset($urls['value2']) ? $urls['value2'] : $urls['value'];


		        // Find in downloads
		        $file_name = array_search($the_url, $this->cssToDownload);


				// If file is from the remote url
		        if (
		        	parseUrl($the_url)['domain'] == parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain'] &&
		        	$file_name !== false &&
		        	(
		        		strpos($urls[0], 'rel="stylesheet"') !== false ||
		        		strpos($urls[0], "rel='stylesheet'") !== false ||
		        		strpos($urls[0], "rel=stylesheet") !== false
		        	)

		        ) {

			        $count_css++;
		        	$css_file_name = $file_name.".css";


					// Add the file to download list !!! NO NEED
			        //$this->cssToDownload["css/".$css_file_name] = $the_url;


			        // Specific Log
					file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - CSS Internalized: '".$the_url."' -> '".Page::ID($this->page_ID)->pageUri."css/".$css_file_name."' \r\n", FILE_APPEND);


			        // Change the URL
					return str_replace(
		            	$the_url,
		            	Page::ID($this->page_ID)->pageUri."css/".$css_file_name,
		            	$urls[0]
		            );

				}


				return $urls[0];


	        },
	        $html
	    );


		// CONVERT ALL SRCSET ATTRIBUTES TO ABSOLUTE
		$html = preg_replace_callback(
	        '/<(?:img)\s+[^<]*?(?:srcset)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) {

		        $the_url = isset($urls['value2']) ? $urls['value2'] : $urls['value'];

				$attr = explode(',', $the_url);

			    $new_srcset = "";

				foreach ( $attr as $src ) {

					$url_exp = array_filter(explode(' ', trim($src)));
					$url = $url_exp[0];
					$size = $url_exp[1];

					$new_srcset .= url_to_absolute(Page::ID($this->page_ID)->remoteUrl, $url)." ".$size.(end($attr) != $src ? ", " : "");

				}


				// Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Srcset Absoluted: '".$the_url."' -> '".$new_srcset."' \r\n", FILE_APPEND);


	            return str_replace(
	            	$the_url,
	            	$new_srcset,
	            	$urls[0]
	            );
	        },
	        $html
	    );


	    // IN PAGE STYLES
		$html = preg_replace_callback(
	        '/(?<tag><style+[^<]*?>)(?<content>[^<>]++)<\/style>/i',
	        function ($style) {

		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Inpage Style Filtred \r\n", FILE_APPEND);

		        return $style['tag'].$this->filter_css($style['content'])."</style>";

	        },
	        $html
	    );


	    // INLINE STYLES
		$html = preg_replace_callback(
	        '/<(?:[a-z0-9]*)\s+[^<]*?(?:style)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) {

		        $the_css = isset($urls['value2']) ? $urls['value2'] : $urls['value'];
		        $filtred_css = $this->filter_css($the_css);

		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Inline Style Filtred: '".$the_css."' -> '".$filtred_css."' \r\n", FILE_APPEND);


	            return str_replace(
	            	$the_css,
	            	$filtred_css,
	            	$urls[0]
	            );
	        },
	        $html
	    );



		// SAVING:

		// Save the file if not exists
		if ( file_exists( Page::ID($this->page_ID)->pageFile ) )
			$updated = file_put_contents( Page::ID($this->page_ID)->pageFile, $html, FILE_TEXT);


		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Finished".(!$updated ? " <b>WITH ERRORS</b>":'')." \r\n", FILE_APPEND);
		rename(Page::ID($this->page_ID)->logDir."/_filter.log", Page::ID($this->page_ID)->logDir.(!$updated ? '/__' : '/')."filter.log");


		if ($updated) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "HTML Filtred.");


			$logger->info("HTML Filtred.");
			return true;

		}

		// Update the queue status
		$queue->update_status($this->queue_ID, "error", "HTML couldn't be filtred.");
		$logger->error("HTML couldn't be filtred.");
		return false;

	}


	// 7.   Download the CSS files
	// 7.1. CSS absolute URL filter to correct downloaded URLs
	// 7.1. Detect fonts and correct with downloaded ones
	public function downloadCssFiles() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "CSS downloading started.");


		// Init Log
		$logger->info("CSS downloading started.");


		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_css.log", "[".date("Y-m-d h:i:sa")."] - Started {TOTAL:".count($this->cssToDownload)."} \r\n", FILE_APPEND);


		// Download them
		$count = 0;
		$css_downloaded_has_error = false;
		foreach ($this->cssToDownload as $fileName => $url) {

			$fileName = $fileName.".css";


			$css_downloaded = $this->download_remote_file($url, $fileName, "css");

			// In case of error, try non-ssl if it's ssl
			if (!$css_downloaded && substr($url, 0, 8) == "https://") {
				$url = "http://".substr($url, 8);
				$css_downloaded = $this->download_remote_file($url, $fileName, "css");
			}


			// Specific Log
			file_put_contents( Page::ID($this->page_ID)->logDir."/_css.log", "[".date("Y-m-d h:i:sa")."] -".(!$css_downloaded ? " <b>NOT</b>":'')." Downloaded: '".$url."' -> '".$fileName."' \r\n", FILE_APPEND);


			if (!$css_downloaded) $css_downloaded_has_error = true;

			$count++;
		}



		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_css.log", "[".date("Y-m-d h:i:sa")."] - Finished".($css_downloaded_has_error ? " <b>WITH ERRORS</b>":'')." \r\n", FILE_APPEND);
		rename(Page::ID($this->page_ID)->logDir."/_css.log", Page::ID($this->page_ID)->logDir.($css_downloaded_has_error ? '/__' : '/')."css.log");


		// Return true if no error
		if (!$css_downloaded_has_error) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "$count CSS downloads finished.");

			$logger->info("$count CSS Downloads finished");
			return true;
		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "$count CSS downloads finished with error(s).");


		$logger->error("$count CSS Downloads finished with error(s).");
		return false;

	}


	// 8. Download the font files
	public function downloadFontFiles() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Font downloading started.");


		// Init Log
		$logger->info("Font downloading started.");


		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_font.log", "[".date("Y-m-d h:i:sa")."] - Started {TOTAL:".count($this->fontsToDownload)."} \r\n", FILE_APPEND);


		// Download them
		$count = 0;
		$font_downloaded_has_error = false;
		foreach ($this->fontsToDownload as $fileName => $url) {

			$resource_url = new \Purl\Url($url);
			$path_parts = pathinfo($resource_url->path);
			$extension = isset($path_parts['extension']) ? strtolower($path_parts['extension']) : "";

			$fileName = $path_parts['filename'];
			$fileName = $fileName.".".$extension;


			$font_downloaded = $this->download_remote_file($url, $fileName, "fonts");


			// Specific Log
			file_put_contents( Page::ID($this->page_ID)->logDir."/_font.log", "[".date("Y-m-d h:i:sa")."] -".(!$font_downloaded ? " <b>NOT</b>":'')." Downloaded: '".$url."' \r\n", FILE_APPEND);


			if (!$font_downloaded) $font_downloaded_has_error = true;

			$count++;
		}



		// Specific Log
		file_put_contents( Page::ID($this->page_ID)->logDir."/_font.log", "[".date("Y-m-d h:i:sa")."] - Finished".($font_downloaded_has_error ? " <b>WITH ERRORS</b>":'')." \r\n", FILE_APPEND);
		rename(Page::ID($this->page_ID)->logDir."/_font.log", Page::ID($this->page_ID)->logDir.($font_downloaded_has_error ? '/__' : '/')."font.log");


		// Return true if no error
		if (!$font_downloaded_has_error) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "$count font downloads finished.");

			$logger->info("$count font Downloads finished");
			return true;
		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "$count font downloads finished with error(s).");


		$logger->error("$count Font downloads finished with error(s).");
		return false;

	}


	// 9. Complete the job!
	public function completeTheJob() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "done", "Internalization is complete.");


		// Init Log
		$logger->info("Internalization is complete.");


		return Page::ID($this->page_ID)->cachedUrl;
	}




	// DOWNLOAD FILES
	function download_remote_file($url, $fileName, $folderName = "other") {
		global $logger;


		$fileContent = "";


		// Check the url
		if ( get_http_response_code($url) == "200" )
	    	$fileContent .= @file_get_contents($url, FILE_BINARY);


		if ( $folderName == "css" )
			$fileContent = $this->filter_css($fileContent, $url);



		// SAVING:

		// Create the folder if not exists
		if ( !file_exists(Page::ID($this->page_ID)->pageDir."/$folderName/") )
			mkdir(Page::ID($this->page_ID)->pageDir."/$folderName/", 0755, true);
		@chmod(Page::ID($this->page_ID)->pageDir."/$folderName/", 0755);

		// Save the file if not exists
		$downloaded = false;
		if ( !file_exists( Page::ID($this->page_ID)->pageDir."/$folderName/".$fileName ) )
			$downloaded = file_put_contents( Page::ID($this->page_ID)->pageDir."/$folderName/".$fileName, $fileContent, FILE_BINARY);


		// Return true if successful
		if ($downloaded) {

			$logger->info("File downloaded: '".$url."' -> '".$fileName."'");
			return true;
		}

		$logger->error("File couldn't be downloaded: '".$url."' -> '".$fileName."'");
		return false;

	}



	// FILTERS:

	// FILTER CSS
	function filter_css($css, $url = "") {
		global $logger;


		if (empty($url))
			$url = Page::ID($this->page_ID)->remoteUrl;


		// Internalize Fonts - No Need for now !!!
		//$css = $this->detectFonts($css, $url);


		// Log
		$logger->info('CSS filtering started: '.$url);


		// All url()s
		$count = 0;
		$css = preg_replace_callback(
	        '/url\s*\(\s*[\\\'"]?(?<url>[^\\\'")]+)[\\\'"]?\s*\)/',
	        function ($css_urls) use($url) {
        		global $logger, $count;

				$url_found = $css_urls['url'];

        		$relative_url = $url_found;
        		$absolute_url = url_to_absolute($url, $url_found);
				$new_url = $absolute_url;


        		// Absolution Logs
				$logger->info('URL absoluted in CSS: '.$relative_url.' -> '.$new_url);
		        file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Absoluted: '".$relative_url."' -> '".$new_url."' \r\n", FILE_APPEND);


				$parsed_url = parseUrl($absolute_url);
				$parsed_path = pathinfo($parsed_url['path']);
		        $file_name = $parsed_path['filename'];
		        $file_hash = isset($parsed_url['hash']) ? $parsed_url['hash'] : "";
		        $file_extension_with_hash = isset($parsed_path['extension']) ? $parsed_path['extension'] : "";
		        $file_extension = str_replace('#'.$file_hash, '', $file_extension_with_hash);
		        $file_name_hashed = $file_name.".".$file_extension_with_hash;


				// If not valid URL
				if (
					$file_name == "" ||
					$file_extension == ""
				) {

					$logger->info('Invalid URL skipped in CSS: '.$url_found);

					return "url('".$url_found."')";
				}


				// If not same domain URL
				if (
					$parsed_url['domain'] != parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain']
				) {

					$logger->info('Different domain URL skipped in CSS: '.$absolute_url);

					return "url('".$url_found."')";
				}


				// Find in downloads
				$downloaded_font = array_search($absolute_url, $this->fontsToDownload);
				$downloaded_css = array_search($absolute_url, $this->cssToDownload);
				$downloaded_image = array_search($absolute_url, $this->imagesToDownload);


				if (
					$file_extension == "ttf" ||
					$file_extension == "otf" ||
					$file_extension == "woff" ||
					$file_extension == "woff2" ||
					$file_extension == "svg" ||
					$file_extension == "eot"

					//$downloaded_font !== false
				) {


					$new_url = Page::ID($this->page_ID)->pageUri."fonts/".$file_name_hashed;


					// Font Logs
					$logger->info('Font Detected: '.$relative_url.' -> '.$new_url);
			        file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Font Detected: '".$relative_url."' -> '".$new_url."' \r\n", FILE_APPEND);


				} elseif ( $downloaded_css !== false ) {


					$new_url = Page::ID($this->page_ID)->pageUri."css/".$downloaded_css.".css";


					// CSS Import Logs
					$logger->info('Imported CSS Detected: '.$relative_url.' -> '.$new_url);
			        file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Imported CSS Detected: '".$relative_url."' -> '".$new_url."' \r\n", FILE_APPEND);


				} elseif ( $downloaded_image !== false ) {

					// Image Detected
					$logger->info('Image Detected: '.$relative_url.' -> '.$new_url);
			        file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Image Detected: '".$relative_url."' -> '".$new_url."' \r\n", FILE_APPEND);

				}

				$count++;

	            return "url('".$new_url."')";
	        },
	        $css
	    );



		// Log
		$logger->info('CSS filtering finished: '.$url);


		return $css;

	}


	// FILTER JS ?? !!!
	function filter_js() {

	}

}