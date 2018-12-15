<?php
use Cocur\BackgroundProcess\BackgroundProcess;


class Screenshot {


	// The Page ID
	public $page_ID;

	// The Page data
	public $pageData;

	// The Device ID
	public $device_ID;

	// The Device data
	public $deviceData;

	// The Queue ID
	public $queue_ID;




	// When initialized
	public function __construct($page_ID, $device_ID, $queue_ID) {


		// Set the page ID
		$this->page_ID = $page_ID;

		// Get the page data
		$this->pageData = Page::ID($page_ID);


		// Set the device ID
		$this->device_ID = $device_ID;

		// Get the page data
		$this->deviceData = Device::ID($device_ID);


		// The current queue ID
		$this->queue_ID = $queue_ID;


	}




	// JOBS:


	// 1. Wait for the queue
	public function waitForQueue() {
		global $db, $queue, $logger;


		// 1.1. Check if current job is ready to be done
		$job_ready = $queue->isReady($this->queue_ID);
		$job_status = $queue->info($this->queue_ID)['queue_status'];


		// 1.2. Wait for the job availability in queue
		$interval = 2;
		while (!$job_ready && $job_status == "waiting") {

			$logger->info("Waiting $interval second(s) for the queue.");
			sleep($interval);
			$job_ready = $queue->isReady($this->queue_ID);
			$job_status = $queue->info($this->queue_ID)['queue_status'];

		}

		return true;
	}



	// 2. 	If job is ready to get done, open the site with Chrome
	// 2.1. Take a screenshot for the device
	public function browserWorks() {
		global $db, $queue, $logger, $config;


		// INITIAL LOGS

		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Browser job for screenshot is starting.");
		$logger->info("Browser job is starting.");


		// Page Info
		$page_ID = $this->page_ID;

		$url = $this->pageData->remoteUrl;
		$pageDir = $this->pageData->pageDir;


		// Device Info
		$device_ID = $this->device_ID;
		$deviceInfo = $this->deviceData->getInfo(null, true);


		// Screen info
		$screenInfo = Screen::ID($deviceInfo['screen_ID'])->getInfo(null, true);


		$width = $deviceInfo['device_width'] ? $deviceInfo['device_width'] : $screenInfo['screen_width'];
		$height = $deviceInfo['device_height'] ? $deviceInfo['device_height'] : $screenInfo['screen_height'];


		// Chrome container request link
		$processLink = "http://chrome:3000/";
		$processLink .= "?url=".urlencode($url);
		$processLink .= "&action=screenshot";
		$processLink .= "&width=$width&height=$height";
		$processLink .= "&page_ID=$page_ID";
		$processLink .= "&device_ID=$device_ID";
		$processLink .= "&sitedir=".urlencode($pageDir."/");


		// Send the request
		$data = getRemoteData($processLink);


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Browser job is done.");
		$logger->info("Browser job is done.");


		// If not successful
		if (!$data || $data->status != "success") {


			// Update the queue status
			$queue->update_status($this->queue_ID, "error", "Screenshot couldn't be downloaded. Trying one more time...");
			$logger->error("Screenshot couldn't be downloaded. Trying one more time...");



			// Send the request again after 2 seconds
			sleep(2);
			$data = getRemoteData($processLink);

			if (!$data || $data->status != "success") {


				// Update the queue status
				$queue->update_status($this->queue_ID, "error", "Screenshot couldn't be downloaded.");
				$logger->error("Screenshot couldn't be downloaded.");

				return false;

			}

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "working", "Screenshot Downloaded. Browser job is complete.");
		$logger->info("Screenshot Downloaded. Browser job is complete.");


		return true;

	}


	// 3. Complete the job!
	public function completeTheJob() {
		global $logger, $queue;


		// Current Queue Status Check
		if ( $queue->info($this->queue_ID)['queue_status'] != "working" ) {

			$logger->error("Queue isn't working.");
			return false;

		}


		// Update the queue status
		$queue->update_status($this->queue_ID, "done", "Screenshot taking is complete.");
		$logger->info("Screenshot taking is complete.");


		return true;
	}

}