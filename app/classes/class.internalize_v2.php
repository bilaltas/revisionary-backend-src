<?php
use PHPHtmlParser\Dom;
use Cocur\BackgroundProcess\BackgroundProcess;


class Internalize_v2 {



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
	public function __construct($page_ID) {

		// Set the page ID
		$this->page_ID = $page_ID;

	}




	// JOBS:

	// 1. Add the job to the queue
	public function addToQueue() {
		global $db, $logger, $queue;

		$this->queue_ID = $queue->new_job('internalize', $this->page_ID, "Waiting other works to be done.");

		if ($this->queue_ID)
			return $this->queue_ID;

		return false;
	}


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


	// 3. 	If job is ready to get done, open the site with slimerJS
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
		$pageID = $this->page_ID;
		$projectID = Page::ID($pageID)->getPageInfo('project_ID');


		// Add image names to database
		if ( Page::ID($pageID)->getPageInfo('page_pic') == null ) {

			$db->where('page_ID', $pageID);
			$db->update('pages', array(
				'page_pic' => "page.jpg" // !!! Create a random number
			), 1);

		}

		if ( Project::ID( $projectID )->getProjectInfo('project_pic') == null ) {

			$db->where('project_ID', $projectID);
			$db->update('projects', array(
				'project_pic' => "proj.jpg" // !!! Create a random number
			), 1);

		}


		$page_image = Page::ID($pageID)->pageDeviceDir."/".Page::ID($pageID)->getPageInfo('page_pic');
		$project_image = Page::ID($pageID)->projectDir."/".Project::ID( $projectID )->getProjectInfo('project_pic');

		$page_captured = file_exists($page_image);
		$project_captured = file_exists($project_image);

		// If both already captured and page is already internalized, return
		if (
			$project_captured && $page_captured &&
			file_exists( Page::ID($pageID)->pageTempFile )
		)
			return false;


		// Get info
		$url = Page::ID($pageID)->remoteUrl;
		$logDir = Page::ID($pageID)->logDir;
		$deviceID = Page::ID($pageID)->getPageInfo('device_ID');
		$width = Device::ID($deviceID)->getDeviceInfo('device_width');
		$height = Device::ID($deviceID)->getDeviceInfo('device_height');
		$page_image = $page_captured ? "done" : $page_image;
		$project_image = $project_captured ? "done" : $project_image;

		// Process directories
		$slimerjs = realpath('..')."/bin/slimerjs-0.10.3/slimerjs";
		$capturejs = dir."/app/bgprocess/capture_v2.js";

		$process_string = "$slimerjs $capturejs $url $width $height $page_image $project_image $logDir";

		$process = new BackgroundProcess($process_string);
		$process->run($logDir."/capture.log", true);


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Browser job has started.", $process->getPid());


		// LOGS
		$logger->info("Browser jobs process string: ". $process_string);
		$logger->info("Browser jobs process ID: ". $process->getPid());


		// Wait for the process done
		while (
			$process->isRunning() &&
			$queue->info($this->queue_ID)['queue_status'] == "working"
		) {

			$logger->info("Waiting 2 seconds for the process to be complete");
			sleep(2);

		}


		// Process Check
		if ( !$process->isRunning() ) {

			// Update the queue status
			$queue->update_status($this->queue_ID, "working", "Browser job is done.");


			// Log
			$logger->info("Browser job is done.");

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


		// Wait for the resources completely written
		$resources = preg_split('/\r\n|[\r\n]/', trim(file_get_contents($resources_file)));
		$last_line = end($resources);

		while ( $last_line != "DONE" && $job_status == "working" ) {

			$logger->info("Waiting 2 seconds for the resources file to complete. Last Resource: ". $last_line);
			sleep(2);
			$job_status = $queue->info($this->queue_ID)['queue_status'];
			$resources = preg_split('/\r\n|[\r\n]/', trim(file_get_contents($resources_file)));
			$last_line = end($resources);

		}
		$resources = array_unique($resources);
		array_pop($resources);
		$this->resources = $resources;



		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Resources list is ready.", $process->getPid());

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

			$resource = explode(' -> ', $resource);
			$content_type = trim($resource[0]);
			$resource_url = trim($resource[1]);

			$url = new \Purl\Url($resource_url);
			$path_parts = pathinfo($url->path);
			$extension = isset($path_parts['extension']) ? strtolower($path_parts['extension']) : "";


			// If the resource is not belong to the domain, pass it.
			if ( parseUrl($resource_url)['domain'] != parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain'] ) {

				$logger->info("Resource skipped: $resource_url");
				continue;
			}



			// Register the HTML file
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
		if ( file_exists( Page::ID($this->page_ID)->pageTempFile ) ) {

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
	            "user_agent" => "Mozilla/5.0"
	        )
		);

		// Get the HTML
		$content = @file_get_contents(Page::ID($this->page_ID)->remoteUrl, FILE_TEXT, stream_context_create($ContextOptions));
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
		if ( !file_exists( Page::ID($this->page_ID)->pageTempFile ) )
			$saved = file_put_contents( Page::ID($this->page_ID)->pageTempFile, $html, FILE_TEXT);



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


	// 6. Filter the HTML to correct URLs
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
		if ( !file_exists( Page::ID($this->page_ID)->pageTempFile ) ) {

			// Log
			$logger->error("HTML file is not exist.");

			// Update the queue status
			$queue->update_status($this->queue_ID, "error", "HTML couldn't be filtred.");

			return false;

		}

		// Get the HTML from the downloaded file
		$html = file_get_contents(Page::ID($this->page_ID)->pageTempFile);


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
	        '/<(?<tagname>link|a|script|img)\s+[^<]*?(?:href|src)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) {

		        $the_url = isset($urls['value2']) ? $urls['value2'] : $urls['value'];
		        $new_url = url_to_absolute(Page::ID($this->page_ID)->remoteUrl, $the_url);

		        if (parseUrl($the_url)['host'] != "" )
		        	$new_url = url_to_absolute(parseUrl($the_url)['full_host'], $the_url);

		        // If not on our server, don't do it
		        if (parseUrl($the_url)['domain'] != "" && parseUrl($the_url)['domain'] != parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain'] )
		        	$new_url = $the_url;


		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Absoluted: '".$the_url."' -> '".$new_url."' \r\n", FILE_APPEND);


	            return str_replace(
	            	$the_url,
	            	$new_url,
	            	$urls[0]
	            );
	        },
	        $html
	    );


/*
	    // INTERNALIZE CSS FILES
		$count_css = 0;
		$html = preg_replace_callback(
	        '/<(?<tagname>link)\s+[^<]*?(?:href)=(?:(?:[\"](?<value>[^<]*?)[\"])|(?:[\'](?<value2>[^<]*?)[\'])).*?>/i',
	        function ($urls) use(&$count_css) {

		        $the_url = isset($urls['value2']) ? $urls['value2'] : $urls['value'];



				// If file is from the remote url
		        if ( parseUrl($the_url)['domain'] == parseUrl(Page::ID($this->page_ID)->remoteUrl)['domain']

		        && ( strpos($urls[0], 'rel="stylesheet"') !== false || strpos($urls[0], "rel='stylesheet'") !== false || strpos($urls[0], "rel=stylesheet") !== false )

		        ) {

			        $count_css++;
		        	$css_file_name = $count_css.".css";

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
*/


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


/*
	    // IN PAGE STYLES
		$html = preg_replace_callback(
	        '/(?<tag><style+[^<]*?>)(?<content>[^<>]++)<\/style>/i',
	        function ($urls) {

		        // Specific Log
				file_put_contents( Page::ID($this->page_ID)->logDir."/_filter.log", "[".date("Y-m-d h:i:sa")."] - Inpage Style Filtred \r\n", FILE_APPEND);

		        return $urls['tag'].$this->filter_css($urls['content'])."</style>";

	        },
	        $html
	    );
*/


/*
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
*/



		// SAVING:

		// Save the file if not exists
		if ( file_exists( Page::ID($this->page_ID)->pageTempFile ) )
			$updated = file_put_contents( Page::ID($this->page_ID)->pageTempFile, $html, FILE_TEXT);


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


	// 7. Download the CSS files
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


	// 9. Update HTML with the downloaded CSS files, and font files?
	public function updateHtmlWithNewCss() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "HTML Updating started.");


		// Init Log
		$logger->info("HTML Updating started.");





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

		// Internalize Fonts !!!
		//$css = $this->detectFonts($css, $url);


		// Log
		$logger->info('CSS Filtred: '.$url);


		// All url()s
		$css = preg_replace_callback(
	        '%url\s*\(\s*[\\\'"]?(?!(((?:https?:)?\/\/)|(?:data:?:)))([^\\\'")]+)[\\\'"]?\s*\)%',
	        function ($css_urls) use($url) {
        		global $logger;


				// LOG:
		        file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Absoluted: '".$css_urls[3]."' -> '".url_to_absolute($url, $css_urls[3])."' \r\n", FILE_APPEND);

		        // General Log
				$logger->info('URL absoluted in CSS: '.$css_urls[3].' -> '.url_to_absolute($url, $css_urls[3]));


	            return "url('".url_to_absolute($url, $css_urls[3])."')";
	        },
	        $css
	    );


		return $css;

	}


	// FILTER JS ?? !!!
	function filter_js() {

	}


	// DETECT FONTS
	function detectFonts($css, $url = "") {

	$pattern = <<<'LOD'
~
(?(DEFINE)
    (?<quoted_content>
        (["']) (?>[^"'\\]++ | \\{2} | \\. | (?!\g{-1})["'] )*+ \g{-1}
    )
    (?<comment> /\* .*? \*/ )
    (?<url_skip> (?: https?: | data: ) [^"'\s)}]*+ )
    (?<other_content>
        (?> [^u}/"']++ | \g<quoted_content> | \g<comment>
          | \Bu | u(?!rl\s*+\() | /(?!\*)
          | \g<url_start> \g<url_skip> ["']?+
        )++
    )
    (?<anchor> \G(?<!^) ["']?+ | @font-face \s*+ { )
    (?<url_start> url\( \s*+ ["']?+ )
)

\g<comment> (*SKIP)(*FAIL) |

\g<anchor> \g<other_content>?+ \g<url_start> \K [./]*+

( [^"'\s)}]*+ )    # url
~xs
LOD;

		$css = preg_replace_callback(
	        $pattern,
	        function ($urls) use($url) {

		        $font_remote_url = url_to_absolute($url, $urls[0]);

		        $parsed_url = parseUrl($font_remote_url);
		        $font_file_name = basename($parsed_url['path']);
		        $font_file_name_hash = $font_file_name.($parsed_url['hash'] != "" ? "#".$parsed_url['hash'] : "");


				// Add the file to quee
	            $this->fontsToDownload["fonts/".$font_file_name] = $font_remote_url;


	            if ($this->debug) echo "detectFonts: ".$url." - ".$urls[0]."<br>";


	            // LOG:
	            file_put_contents( Page::ID($this->page_ID)->logDir."/filter-css.log", "[".date("Y-m-d h:i:sa")."] - Font Detected: '".$url."' -> '".$urls[0]."' \r\n", FILE_APPEND);


				// Change the URL
	            //return site_url("get-font/?font=".$font_file_name_hash);
	            return Page::ID($this->page_ID)->pageUri."fonts/".$font_file_name_hash;

	        },
	        $css
	    );


	return $css;

	}




}