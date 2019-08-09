<?php

class Phase {


	public static $phase_ID;
	public static $phaseInfo;

	public $remoteUrl;
	public $cachedUrl;

	public $phaseDir;
	public $phaseFileName = "index.html";
	public $phaseFile;
	public $phaseUri;

	public $logDir;
	public $logFileName = "internalize-process-php";
	public $logFileExtension = "log";
	public $logFile;

	public $internalizeCount;
	public $phaseStatus;



	// SETTERS:

	public function __construct() {


		$page_ID = self::$phaseInfo['page_ID'];
		$pageData = Page::ID($page_ID);


		$this->remoteUrl = $pageData->getInfo('page_url');
		$this->internalizeCount = self::$phaseInfo['phase_internalized'];


		// Phase directory
		$this->phaseDir = $this->getDir();
        $this->phaseFile = $this->phaseDir."/".$this->phaseFileName;


        // Paths
		$fullPath = $this->phaseDir."/";
		$fullPath = str_replace(cache."/", "", $fullPath);


        // Set the phase cache directory URL
        $this->phaseUri = cache_url($fullPath, (substr($this->remoteUrl, 0, 8) == "https://" ? true : false));
        $this->cachedUrl = $this->phaseUri.$this->phaseFileName;


		// Log directory
		$this->logDir = $this->phaseDir."/logs";
        $this->logFile = $this->logDir."/".$this->logFileName.".".$this->logFileExtension;



        // Set the phase status
        $this->phaseStatus = $this->getPhaseStatus();



    }


	// ID Setter
    public static function ID($phase_ID = null) {
	    global $db;


	    // Set the phase ID
		if ($phase_ID != null && is_numeric($phase_ID)) {


			$db->where('phase_ID', $phase_ID);
			$phaseInfo = $db->getOne("phases");

			if ( $phaseInfo ) {

				self::$phase_ID = $phase_ID;
				self::$phaseInfo = $phaseInfo;
				return new static;

			}


		}


	    // For the new phase
		if ($phase_ID == null) {

			self::$phase_ID = "new";
			return new static;

		}

		return false;

    }




	// GETTERS:

    // Get phase info
    public function getInfo($column = null) {

	    return $column == null ? self::$phaseInfo : self::$phaseInfo[$column];

    }


    // Get the phase download status
    public function getPhaseStatus($static = false) {


		// 0% - WAITING FOR THE QUEUE
		$process_status = [
			"status" => "waiting",
			"description" => "Waiting for the queue",
			"percentage" => 0
		];


		if (!file_exists($this->logDir))
			$process_status = [
				"status" => "Ready to Download",
				"description" => "Phase needs to be downloaded",
				"percentage" => 0
			];


		if ($static) {

			// DAMAGED VERSIONS
			if (
				!file_exists($this->phaseFile) ||
				!file_exists($this->logDir."/html-filter.log") ||
				!file_exists($this->logDir."/css-filter.log")
			)
				$process_status = [
					"status" => "download-needed",
					"description" => "Download needed",
					"percentage" => 0
				];

		}


		// 10% - VERSION IS DOWNLOADING
		if (
			file_exists($this->logDir."/browser.log")
		)
			$process_status = [
				"status" => "downloading-phase",
				"description" => "Downloading the page",
				"percentage" => 25
			];


		// 25% - VERSION IS DOWNLOADED
		if (
			file_exists($this->phaseFile)
		)
			$process_status = [
				"status" => "downloaded-phase",
				"description" => "Page is downloaded",
				"percentage" => 25
			];


		// 50% - UPDATING THE VERSION
		if (file_exists($this->logDir."/_html-filter.log"))
			$process_status = [
				"status" => "updating-html",
				"description" => "Updating the page",
				"percentage" => 50
			];


		// 70% - VERSION UPDATED
		if (file_exists($this->logDir."/html-filter.log"))
			$process_status = [
				"status" => "updated-html",
				"description" => "Page updated",
				"percentage" => 70
			];

		// 0% - HTML Filter Error
		if (file_exists($this->logDir."/__html-filter.log"))
			$process_status = [
				"status" => "updating-html-error",
				"description" => "The page couldn't be updated",
				"percentage" => 0
			];


		// 75% - FIXING THE STYLES
		if (file_exists($this->logDir."/_css-filter.log"))
			$process_status = [
				"status" => "updating-css",
				"description" => "Fixing the styles",
				"percentage" => 75
			];


		// 95% - STYLES ARE PERFECTED
		if (file_exists($this->logDir."/css-filter.log"))
			$process_status = [
				"status" => "updated-css",
				"description" => "Styles are perfected",
				"percentage" => 95
			];

		// 0% - CSS Filter Error
		if (file_exists($this->logDir."/__css-filter.log"))
			$process_status = [
				"status" => "updating-css-error",
				"description" => "The styles couldn't be fixed",
				"percentage" => 0
			];



		// 100% - READY
		if (
			file_exists($this->phaseFile) &&
			file_exists($this->logDir."/html-filter.log") &&
			file_exists($this->logDir."/css-filter.log")
		)
			$process_status = [
				"status" => "ready",
				"description" => "Ready! Loading the site",
				"percentage" => 100
			];


		return $process_status;

    }


    // Get phase directory
    public function getDir() {

		// Paths
        $pagePath = Page::ID(self::$phaseInfo['page_ID'])->getDir();
        $phasePath = "phase-".self::$phase_ID;


        // Set the phase directory
        return "$pagePath/$phasePath";
    }


	// Get phase users
	public function getUsers($include_me = false) {
		global $db;


		$page_ID = self::$phaseInfo['page_ID'];
		$pageData = Page::ID($page_ID);


		$users = array();


		// Get the project users
		$users = array_merge($users, $pageData->getUsers($include_me));


		// Remove duplicates
		$users = array_unique($users);


		// Exclude myself
		if ( !$include_me && ($user_key = array_search(currentUserID(), $users)) !== false ) {
		    unset($users[$user_key]);
		}


		return $users;

	}




    // ACTIONS

    // Add a new phase
    public function addNew(
    	int $page_ID // The page_ID that new phase is belong to
    ) {
	    global $db, $log;



		// More DB Checks of arguments !!!



		// Add the phase
		$phase_ID = $db->insert('phases', array(
			"page_ID" => $page_ID
		));



		// If phase added
		if ($phase_ID) {


			$phase_link = site_url('phase/'.$phase_ID);

			$pageData = Page::ID($page_ID);
			$page_name = $pageData->getInfo('page_name');

			$project_ID = $pageData->getInfo('project_ID');
			$projectData = Project::ID($project_ID);
			$project_name = " [".$projectData->getInfo('project_name')."]";


			// Site log
			$log->info("Phase #$phase_ID Added in $page_name.$project_name | Page #$page_ID | Project #$project_ID | User #".currentUserID());


		}


		return $phase_ID;

	}



    // Edit a phase
    public function edit(
	    string $column,
	    $new_value
    ) {
	    global $db, $log;



		// More DB Checks of arguments !!!



		// Update the phase
		$db->where('phase_ID', self::$phase_ID);
		$phase_updated = $db->update('phases', array($column => $new_value));


		// Site log
		if ($phase_updated) $log->info("Phase #".self::$phase_ID." Updated: '$column => $new_value' | Page #".$this->getInfo('page_ID')." | User #".currentUserID());


		return $phase_updated;
    }



    // Remove a phase
    public function remove() {
	    global $db, $log;



		// More DB Checks of arguments !!! (Do I have access to this phase?!)



		// Get the page info
    	$page_ID = self::$phaseInfo['page_ID'];
    	$pageData = Page::ID($page_ID);
    	$pageInfo = $pageData->getInfo();


    	// Get the project info
    	$project_ID = $pageInfo['project_ID'];
    	$projectData = Project::ID($project_ID);
    	$projectInfo = $projectData->getInfo();



    	if ( !User::ID()->canAccess( self::$phase_ID, 'phase') ) return false;



		// VERSION REMOVAL
		$db->where('phase_ID', self::$phase_ID);
		$phase_removed = $db->delete('phases');



		// Delete the phase folder
		deleteDirectory( cache."/projects/project-$project_ID/page-$page_ID/phase-".self::$phase_ID."/" );



		// Delete the notifications if exists
		$db->where('object_type', 'phase');
		$db->where('object_ID', self::$phase_ID);
		$db->delete('notifications');



		// Site log
		if ($phase_removed) $log->info("Phase #".self::$phase_ID." Removed | Project Name: ".$projectData->getInfo('project_name')." | Project #".$projectData->getInfo('project_ID')." | Page Name: ".$pageData->getInfo('page_name')." | Page #".$this->getInfo('page_ID')."User #".currentUserID());


		return $phase_removed;

    }

}