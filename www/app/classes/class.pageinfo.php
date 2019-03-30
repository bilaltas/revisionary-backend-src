<?php

class Page {


	public static $page_ID;
	public static $pageInfo;

	public $remoteUrl;
	public $cachedUrl;

	public $pageDir;
	public $pageFileName = "index.html";
	public $pageFile;
	public $pageUri;

	public $logDir;
	public $logFileName = "internalize-process-php";
	public $logFileExtension = "log";
	public $logFile;

	public $internalizeCount;
	public $pageStatus;



	// SETTERS:

	public function __construct() {


		$this->remoteUrl = self::$pageInfo['page_url'];
		$this->internalizeCount = self::$pageInfo['page_internalized'];


		// Page directory
		$this->pageDir = $this->getDir();
        $this->pageFile = $this->pageDir."/".$this->pageFileName;


        // Paths
        $projectPath = "project-".self::$pageInfo['project_ID'];
        $pagePath = "page-".self::$page_ID;
		$fullPath = "projects/$projectPath/$pagePath/";


        // Set the page cache directory URL
        $this->pageUri = cache_url($fullPath, (substr($this->remoteUrl, 0, 8) == "https://" ? true : false));
        $this->cachedUrl = $this->pageUri.$this->pageFileName;


		// Log directory
		$this->logDir = $this->pageDir."/logs";
        $this->logFile = $this->logDir."/".$this->logFileName.".".$this->logFileExtension;



        // Set the page status
        $this->pageStatus = $this->getPageStatus();



    }


	// ID Setter
    public static function ID($page_ID = null) {
	    global $db;


	    // Set the page ID
		if ($page_ID != null && is_numeric($page_ID)) {


			$db->where('page_ID', $page_ID);
			$pageInfo = $db->getOne("pages");

			if ( $pageInfo ) {

				self::$page_ID = $page_ID;
				self::$pageInfo = $pageInfo;
				return new static;

			}


		}


	    // For the new page
		if ($page_ID == null) {

			self::$page_ID = "new";
			return new static;

		}

		return false;

    }




	// GETTERS:

    // Get page info
    public function getInfo($column = null) {

	    return $column == null ? self::$pageInfo : self::$pageInfo[$column];

    }


    // Get the page download status
    public function getPageStatus($static = false) {


		// 0% - WAITING FOR THE QUEUE
		$process_status = [
			"status" => "waiting",
			"description" => "Waiting for the queue",
			"percentage" => 0
		];


		if (!file_exists($this->logDir))
			$process_status = [
				"status" => "Ready to Download",
				"description" => "Page needs to be downloaded",
				"percentage" => 0
			];


		if ($static) {

			// DAMAGED PAGES
			if (
				!file_exists($this->pageFile) ||
				!file_exists($this->logDir."/html-filter.log") ||
				!file_exists($this->logDir."/css-filter.log")
			)
				$process_status = [
					"status" => "download-needed",
					"description" => "Download needed",
					"percentage" => 0
				];

		}


		// 10% - PAGE IS DOWNLOADING
		if (
			file_exists($this->logDir."/browser.log")
		)
			$process_status = [
				"status" => "downloading-page",
				"description" => "Downloading the page",
				"percentage" => 25
			];


		// 25% - PAGE IS DOWNLOADED
		if (
			file_exists($this->pageFile)
		)
			$process_status = [
				"status" => "downloaded-page",
				"description" => "Page is downloaded",
				"percentage" => 25
			];


		// 50% - UPDATING THE PAGE
		if (file_exists($this->logDir."/_html-filter.log"))
			$process_status = [
				"status" => "updating-html",
				"description" => "Updating the page",
				"percentage" => 50
			];


		// 70% - PAGE UPDATED
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
			file_exists($this->pageFile) &&
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


    // Get page directory
    public function getDir() {

		// Paths
        $projectPath = Project::ID(self::$pageInfo['project_ID'])->getDir();
        $pagePath = "page-".self::$page_ID;


        // Set the page directory
        return "$projectPath/$pagePath";
    }


	// Get page users
	public function getUsers($include_me = false) {
		global $db;


		$project_ID = $this->getInfo('project_ID');
		$projectData = Project::ID($project_ID);


		$users = array();


		// Get the page owner
		$users[] = $this->getInfo('user_ID');

		// Get the shared people of the page
		$db->where('share_type', 'page');
		$db->where('shared_object_ID', self::$page_ID);
		$db->where("share_to REGEXP '^[0-9]+$'");
		$shared_IDs = array_column($db->get('shares', null, 'share_to'), 'share_to');
		$users = array_merge($users, $shared_IDs);


		// Get the project users
		$users = array_merge($users, $projectData->getUsers($include_me));


		// Remove duplicates
		$users = array_unique($users);


		// Exclude myself
		if ( !$include_me && ($user_key = array_search(currentUserID(), $users)) !== false ) {
		    unset($users[$user_key]);
		}


		return $users;

	}




    // ACTIONS

    // Add a new page
    public function addNew(
    	int $project_ID = 0, // The project_ID that new page is belong to
    	string $page_url,
    	string $page_name = '',
    	array $page_shares = array(), // Array of users that needs to be shared to
    	int $category_ID = 0, // The category_ID that new page is belong to
    	int $order_number = 0 // The order number
    ) {
	    global $db, $log;



		// More DB Checks of arguments !!!



		// URL check
		if (!filter_var($page_url, FILTER_VALIDATE_URL)) return false;
		$parsed_url = parseUrl($page_url);



		// Standardize the URL before saving
		$page_url = urlStandardize($page_url);



		// Create a project
		if ($project_ID == 0) {

			$project_ID = Project::ID()->addNew(
				$project_name,
				$project_shares,
				$project_category_ID,
				$project_order_number,
				$page_url
			);

		}



		// If no name added, try finding page name from URL
		if ($page_name == '') {
			$parsed_path = pathinfo($parsed_url['path']);
			$file_name = isset($parsed_path['filename']) ? $parsed_path['filename'] : "";
			$page_name = ucwords(str_replace('-', ' ', $file_name));
		}


		// If still empty, name it as 'Home'
		if ($page_name == '')
			$page_name = 'Home';



		// If Category ID is zero, make it null
		if ($category_ID == 0) $category_ID = null;



		// Add the page
		$page_ID = $db->insert('pages', array(
			"page_url" => $page_url,
			"page_name" => $page_name,
			"project_ID" => $project_ID,
			"order_number" => $order_number,
			"cat_ID" => $category_ID,
			"user_ID" => currentUserID()
		));



		// If page added
		if ($page_ID) {



			$page_link = site_url('page/'.$page_ID);
			$project_name = " [".Project::ID($project_ID)->getInfo('project_name')."]";



			// Get the users to notify
			$users = Page::ID($page_ID)->getUsers();


			// Web notification
			Notify::ID($users)->web("new", "page", $page_ID);


			// Email notification
			Notify::ID($users)->mail(
				getUserInfo()['fullName']." added a new page: ".$page_name.$project_name,
				getUserInfo()['fullName']." added a new page: ".$page_name.$project_name." <br>
				<b>Page URL</b>: $page_url <br><br>
				<a href='$page_link' target='_blank'>$page_link</a>"
			);



			// SHARE - Use share API later !!!
			if ( count($page_shares) > 0 ) {

				foreach ($page_shares as $user_ID) {


					// Don't add the user to the shares if already shared
					if ( in_array($user_ID, $users) ) continue;


					// Share
					$share_ID = $db->insert('shares', array(
						"share_type" => 'page',
						"shared_object_ID" => $page_ID,
						"share_to" => $user_ID,
						"sharer_user_ID" => currentUserID()
					));


					// Web notification
					if ( is_integer($user_ID) )
						Notify::ID($user_ID)->web("share", "page", $page_ID);


					// Email notification
					Notify::ID($user_ID)->mail(
						getUserInfo()['fullName']." shared the \"$page_name"."$project_name\" page with you.",

						"Hello, ".
						getUserInfo()['fullName']." shared the \"$page_name"."$project_name\" page with you from Revisionary App. Here is the link to access this page: <br>
						<a href='$page_link' target='_blank'>$page_link</a>",
						true // Important
					);

				}

			}



			// Site log
			$log->info("Page #$page_ID Added: $page_name($page_url) | Project #$project_ID | Project Name:$project_name | User #".currentUserID());



		}


		return $page_ID;

	}



    // Edit a page
    public function edit(
	    string $column,
	    $new_value
    ) {
	    global $db, $log;



		// More DB Checks of arguments !!!



		// Update the page
		$db->where('page_ID', self::$page_ID);
		$page_updated = $db->update('pages', array($column => $new_value));


		// Site log
		if ($page_updated) $log->info("Page #".self::$page_ID." Updated: '$column => $new_value' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


		return $page_updated;
    }


    // Archive a page
    public function archive() {
	    global $db, $log;



		// More DB Checks of arguments !!!



		$archived = $this->edit("page_archived", 1);


		// Site log
		if ($archived) $log->info("Page #".self::$page_ID." Archived: '".$this->getInfo('page_name')."' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


		return $archived;

    }


    // Delete a page
    public function delete() {
	    global $db, $log;



		// More DB Checks of arguments !!!



		$deleted = $this->edit("page_deleted", 1);


		// Site log
		if ($deleted) $log->info("Page #".self::$page_ID." Deleted: '".$this->getInfo('page_name')."' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


		return $deleted;

    }


    // Recover a page
    public function recover() {
	    global $db, $log;



		// More DB Checks of arguments !!!



		// Remove from archives
		$arc_recovered = $this->edit("page_archived", 0);


		// Remove from deletes
		$del_recovered = $this->edit("page_deleted", 0);



		if ($arc_recovered && $del_recovered) {


			// Site log
			$log->info("Page #".self::$page_ID." Recovered: '".$this->getInfo('page_name')."' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


			return true;
		}


		return false;

    }


    // Remove a page
    public function remove() {
	    global $db, $log;



		// More DB Checks of arguments !!! (Do I have access to this page?!)



		// Get the page info
    	$page_user_ID = self::$pageInfo['user_ID'];
    	$project_ID = self::$pageInfo['project_ID'];
    	$projectData = Project::ID($project_ID);


    	$iamadmin = getUserInfo()['userLevelID'] == 1;
    	$iamowner = $page_user_ID == currentUserID();
    	$iamprojectowner = $projectData->getInfo('user_ID') == currentUserID();
    	//$iamshared = false;


    	//if (!$iamadmin && !$iamowner && !$iamprojectowner) return false; // !!!




		// SHARE REMOVAL
		$db->where('share_type', 'page');
		$db->where('shared_object_ID', self::$page_ID);
		if (!$iamowner && !$iamadmin) $db->where('(sharer_user_ID = '.currentUserID().' OR share_to = '.currentUserID().')');
		$db->delete('shares');



		// PAGE REMOVAL
		$db->where('page_ID', self::$page_ID);
		$page_removed = $db->delete('pages');



		// Delete the page folder
		deleteDirectory( cache."/projects/project-$project_ID/page-".self::$page_ID."/" );



		// Delete the notifications if exists
		$db->where('object_type', 'page');
		$db->where('object_ID', self::$page_ID);
		$db->delete('notifications');



		// Site log
		if ($page_removed) $log->info("Page #".self::$page_ID." Removed: '".$this->getInfo('page_name')."' | Project Name: ".$projectData->getInfo('project_name')." | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


		return $page_removed;

    }


    // Rename a page
    public function rename(
	    string $text
    ) {
	    global $db, $log;



		// More DB Checks of arguments !!!



		$current_page_name = $this->getInfo('page_name');


    	$db->where('page_ID', self::$page_ID);
		//$db->where('user_ID', currentUserID()); // !!! Only rename my page?

		$page_renamed = $db->update('pages', array(
			'page_name' => $text
		));


		// Site log
		if ($page_renamed) $log->info("Page #".self::$page_ID." Renamed: '$current_page_name => $text' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());



		return $page_renamed;

    }


    // Change owner
    public function changeownership(
	    int $user_ID
    ) {
		global $db, $log;



		// More DB Checks of arguments !!!



		$old_owner_ID = self::$pageInfo['user_ID'];


		// Share to old owner
		$db->insert('shares', array(

			"share_to" => $old_owner_ID,
			"share_type" => 'page',
			"shared_object_ID" => self::$page_ID,
			"sharer_user_ID" => $user_ID

		));



		$db->where('page_ID', self::$page_ID);
		$db->where('user_ID', currentUserID());
		$ownership_changed = $db->update('pages', array(
			'user_ID' => $user_ID
		));


		// Site log
		if ($ownership_changed) $log->info("Page #".self::$page_ID." Ownership Changed: '$old_owner_ID => $user_ID' | Project #".$this->getInfo('project_ID')." | User #".currentUserID());


		return $ownership_changed;

    }

}