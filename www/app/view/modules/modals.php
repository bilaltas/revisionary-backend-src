<div id="add-new" class="popup-window xl-center scrollable-content" data-type="" data-id="" data-iamowner="" data-currentuser-id="<?=currentUserID()?>">
	<h2>Add New <span class="data-type"></span></h2>
	<h5 class="to"></h5>

	<form action="<?=site_url('projects', true)?>" method="post" class="new-project-form" data-page-type="url" data-cat-id="0">


		<input type="hidden" name="add_new" value="true"/>

		<input type="hidden" name="project_ID" value="<?=$dataType == "page" ? $project_ID : "new"?>"/>

		<input type="hidden" name="category" value="0"/>
		<input type="hidden" name="order" value="0"/>


		<div class="wrap xl-center">
			<div class="col xl-6-7">


				<h4 class="section-title xl-left">Page Info</h4>

				<div class="top-option page-url">
					<h3><i class="fa fa-link"></i> <span class="first-page">First</span> Page URL <i class="fa fa-question-circle tooltip" data-tooltip="Enter the URL you want to revise" aria-hidden="true"></i></h3>
					<input type="url" name="page-url" placeholder="https://example.com/..." tabindex="1" autofocus required/>
				</div>
				<div class="top-option selected-image">
					<h3><i class="fa fa-image"></i> Selected Image</h3>
					<figure>
						<label for="reset" class="reset left-tooltip" data-tooltip="Cancel">&times;</label>
						<input id="reset" type="reset" class="xl-hidden">
						<img src="//:0">
					</figure>
				</div>


				<div class="bottom-option design-uploader">
					<small>or <label for="design-uploader"><b><u>Upload</u></b></label> your page design <i class="fa fa-question-circle tooltip bottom-tooltip" data-tooltip="Upload design images to add your comments."></i></small>
					<input type="file" name="design-upload" id="design-uploader" class="design-upload xl-hidden" accept=".gif,.jpg,.jpeg,.png" data-max-size="15000000">
				</div>
				<div class="bottom-option page-options">
					
					<div class="wrap xl-gutter-40 xl-center">
						<div class="col">
							<label class="bottom-tooltip" data-tooltip="This allows you to download the live URL and change the content."><input type="radio" name="page-type" value="url" checked>Live Mode <small>(Recommended)</small></label>
							<label class="xl-hidden"><input type="radio" name="page-type" value="image">Image Mode</label>
						</div>
						<div class="col">
							<label class="bottom-tooltip" data-tooltip="This mode will take full size picture of your page you entered. You can only put comments on it."><input type="radio" name="page-type" value="capture">Capture Mode</label>
						</div>
					</div>
					
				</div>


				<div class="screens">
					<h3 style="margin-bottom: 0">Screen Size <i class="fa fa-question-circle tooltip" data-tooltip="Add your screen size that you wish to edit your site." aria-hidden="true"></i></h3>
					<ul class="no-spacing selected-screens">
						<li>

							<input type="hidden" name="screens[]" value="11"/>
							<input type="hidden" name="page_width" value="1440"/>
							<input type="hidden" name="page_height" value="900"/>

							<i class="fa fa-window-maximize" aria-hidden="true"></i> <span>Current Window (<span class="screen-width">1440</span> x <span class="screen-height">900</span>)</span>
							<a href="#" class="remove-screen" style="display: none;"><i class="fa fa-times-circle" aria-hidden="true"></i></a>
						</li>
					</ul><br/>
					<span class="dropdown">
	
						<a href="#" class="add-screen"><i class="fa fa-plus" aria-hidden="true"></i> ADD ANOTHER SCREEN</a>
						<?php
							$blockPhase = ['phase_ID' => ""];
							require view('modules/add-screen');
						?>
	
					</span><br/><br/>
				</div>



				<a href="#" class="option-toggler more-options"><i class="fa fa-sliders-h"></i> More Options <i class="fa fa-caret-down" aria-hidden="true"></i></a>
				<a href="#" class="option-toggler less-options"><i class="fa fa-sliders-h"></i> Less Options <i class="fa fa-caret-up" aria-hidden="true"></i><hr/></a>


				<!-- More Options -->
				<div class="more-options-wrapper">



					<h3>Page Name <i class="fa fa-question-circle tooltip" data-tooltip="The name that describes this page like Home, About, ... (Autogenerated from the URL by default)" aria-hidden="true"></i></h3>
					<input type="text" name="page-name" placeholder="e.g. Home, About, ..." tabindex="2"/>


					<h3><i class="fa fa-share-alt"></i> Page Members <i class="fa fa-question-circle tooltip" data-tooltip="Users who can access only this page." aria-hidden="true"></i></h3>
					<div class="people">


						<!-- Owner -->
						<picture data-tooltip="Owner: <?=getUserInfo()['fullName']?>" class="profile-picture" <?=getUserInfo()['printPicture']?>>
							<span><?=getUserInfo()['nameAbbr']?></span>
						</picture>

						<ul class="shares page user">

						</ul>


						<!-- Add New -->
						<a href="#" class="new-member" data-tooltip="Add New Page Member"><i class="fa fa-plus"></i></a>

						<input class="share-email" type="email" data-type="page" placeholder='Type an e-mail address and hit "Enter"...' style="display: none; max-width: 75%;"/>


						<ul class="shares page email">

						</ul>


					</div><br/><br/>



					<div class="project-info">


						<h4 class="section-title xl-left">Project Info</h4>

						<h3>Project Name <i class="fa fa-question-circle tooltip" data-tooltip="The name that describes this project. (Autogenerated from the URL by default)" aria-hidden="true"></i></h3>
						<input type="text" name="project-name" placeholder="e.g. Google, BBC, ..." tabindex="3" />


						<h3><i class="fa fa-share-alt"></i> Project Members <i class="fa fa-question-circle tooltip" data-tooltip="Users who can access this project with all the pages in it." aria-hidden="true"></i></h3>
						<div class="people">


							<!-- Owner -->
							<picture data-tooltip="Owner: <?=getUserInfo()['fullName']?>" class="profile-picture" <?=getUserInfo()['printPicture']?>>
								<span><?=getUserInfo()['nameAbbr']?></span>
							</picture>

							<ul class="shares project user">

							</ul>


							<!-- Add New -->
							<a href="#" class="new-member" data-tooltip="Add New Project Member"><i class="fa fa-plus"></i></a>
							<input class="share-email" type="email" name="project-share-email" data-type="project" placeholder='Type an e-mail address and hit "Enter"...' style="display: none; max-width: 75%;"/>


							<ul class="shares project email">

							</ul>


						</div><br/>

					</div>


				</div>


				<!-- Actions -->
				<div class="wrap xl-2 xl-center xl-flexbox">
					<div class="col">

						<button class="dark small submitter">Add</button>

					</div>
					<div class="col xl-first">

						<button class="cancel-button light small">Cancel</button>

					</div>
				</div>
				<br/>


			</div>
		</div>

	</form>


</div>





<div id="share" class="popup-window xl-center xl-5-12 scrollable-content" data-type="" data-id="" data-iamowner="" data-currentuser-id="<?=currentUserID()?>">
	<h2>Share</h2>
	<h5 class="to">The <b><span class="data-name"></b> <span class="data-type"></span></h5>



	<div class="wrap xl-center xl-gutter-8">
		<div class="col xl-9-10">




			<!-- THEAD -->
			<div class="wrap xl-table xl-gutter-24">
				<div class="col">

					<h4 style="margin-bottom: 10px;">Member</h4>

				</div>
				<div class="col xl-3-8 xl-right">

					<h4 style="margin-bottom: 10px;">Access Level</h4>

				</div>
			</div>





			<!-- MEMBERS -->
			<ul class="xl-left no-spacing members">

			</ul><br/>






			<form action="" method="post">
				<input type="hidden" name="add_new_nonce" value="<?=$_SESSION['add_new_nonce']?>"/>


				<!-- Add New -->
				<div class="wrap xl-table xl-gutter-24">
					<div class="col xl-3-8 hide-when-project">

						<h4 style="margin-bottom: 15px;">Access Level</h4>
						<span class="text-uppercase dropdown">

							<a href="#"><span class="new-access-type">THIS <span class="data-type"></span></span> <i class="fa fa-caret-down"></i></a>
							<ul class="selectable no-delay new-access-type-selector">
								<li class="selected"><a href="#" data-type="page">THIS PAGE</a></li>
								<li><a href="#" data-type="project">WHOLE PROJECT</a></li>
							</ul>

						</span>

					</div>
					<div class="col">

						<h4 class="xl-center" style="margin-bottom: 10px;">Add New User</h4>
						<input id="share-email" class="share-email" type="email" data-add-type="" placeholder='Type an e-mail address and hit "Enter"...' autofocus />

					</div>
				</div><br/>


				<!-- Actions -->
				<div class="wrap xl-2 xl-center xl-flexbox">
					<div class="col">

						<button class="dark small add-member" disabled>Add</button>

					</div>
					<div class="col xl-first">

						<button class="cancel-button light small">Close</button>

					</div>
				</div>
				<br/>


			</form>



		</div>
	</div>


	<div class="link" data-tooltip="Click to Copy">
		<b><span class="data-type"></span> link to Share:</b> <span class="value"><?=site_url()?><span class="data-type"></span>/<span class="data-id"></span></span>
	</div>


</div>





<div id="video" class="popup-window xl-center xl-5-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<h2>Quick Start</h2><br>


	<iframe width="560" height="315" data-src="https://www.youtube.com/embed/a3ICNMQW7Ok" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="max-width: 100%;"></iframe>


</div>





<div id="feedback" class="popup-window xl-center xl-5-12" data-feedback-type="feedback" data-sent="no">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<div class="xl-left">
		<span>Tell Us</span>
		<h3 style="margin-top: 0;">How we are doing?</h3>
	</div>


	<form action="">
		<div class="wrap xl-1">
			<div class="col xl-1-4">
			
				<select name="feedback-type">
					<option value="feedback">Feedback</option>
					<option value="bug">Issue Reporting</option>
				</select>
	
			</div>
			<div class="col xl-3-4 xl-right stars">

				<small class="star-info">Excellent</small>
	
				<i class="fas fa-star" data-value="1"></i><i class="fas fa-star" data-value="2"></i><i class="fas fa-star" data-value="3"></i><i class="fas fa-star" data-value="4"></i><i class="fas fa-star" data-value="5"></i>

				<input type="hidden" name="stars" value="5">
	
			</div>
			<div class="col xl-3-4 xl-right screenshot">
	
				<label><small>Screenshot (Optional):</small> <input type="file" name="screenshot" accept=".gif,.jpg,.jpeg,.png" data-max-size="5000000"></label>
	
			</div>
			<div class="col xl-left">
	
				<textarea name="feedback" maxlength="1000" placeholder="Please explain us your thoughts..." style="margin-top: 20px; border-radius: 8px; outline: none;" autofocus></textarea>
	
			</div>
			<div class="col xl-3-4" style="margin-top: 20px;">
	
				<input type="submit" value="Send Feedback" class="invert">
	
			</div>
			<div class="col xl-1-4 xl-right character-limit">
			
				<span class="current-length">0</span>/<span class="current-limit">1000</span>
	
			</div>
		</div>
		<input type="hidden" name="feedback-url" value="<?=current_url()?>">
	</form>

	<div class="result-messages xl-left">
		<div class="thank-you"><p>Thanks for your feedback!</p></div>
	</div>


</div>





<div id="welcome" class="popup-window xl-center xl-6-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	
	<h3 style="margin-top: 0;">Welcome to Revisionary App</h3>

	<div class="wrap xl-1 xl-center">
		<div class="col">
		
			<p>Please enjoy using Revisionary App and let us know your thoughts!</p><br>

			<div>
			<?php if ( $_url[0] == "projects" && $projectsCount == 0 ): ?>
				<button class="dark small create-project">Create a Project Now</button>
			<?php else: ?>
				<button class="dark small cancel-button">Start Revising Now</button>
			<?php endif; ?>
			</div>

		</div>
	</div>


</div>





<div id="payment" class="popup-window xl-center xl-5-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	
	<div class="xl-left">
		<span>PLEASE ENTER YOUR</span>
		<h3 style="margin-top: 0;">CREDIT CARD INFORMATION</h3>
	</div>


	<div class="wrap xl-1 xl-center">
		<div class="col">
		
			<p>Payment gateway in development...</p><br>

		</div>
	</div>


</div>





<div id="trialstarted" class="popup-window xl-center xl-6-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<?php
		$left_day = getUserInfo()['trialAvailableDays'];
	?>
	
	<h3 style="margin-top: 0;">Your <b><?=getUserInfoDB()['trial_user_level_name']?> plan</b> has been activated for <b><?=$left_day?> day<?=$left_day > 1 ? "s" : ""?></b></h3>

	<div class="wrap xl-1 xl-center">
		<div class="col">
		
			<p>Enjoy using Revisionary App <b><?=getUserInfo()['trialUserLevelName']?></b> features and please let us know your thoughts!</p><br>

			<div>
			<?php if ( $_url[0] == "projects" && $projectsCount == 0 ): ?>
				<button class="dark small create-project">Create a Project Now</button>
			<?php else: ?>
				<button class="dark small cancel-button">Start Revising Now</button>
			<?php endif; ?>
			</div>

		</div>
	</div>


</div>





<div id="trialreminder" class="popup-window xl-center xl-5-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<?php
		$left_day = getUserInfo()['trialAvailableDays'];


		$left_text = "$left_day DAYS LEFT";
		if ($left_day == 1) $left_text = "$left_day DAY LEFT";
		elseif ($left_day == 0) $left_text = "LAST DAY";
	?>


	<div class="xl-left">
		<span><b><?=$left_text?> ON YOUR</b></span>
		<h3 style="margin-top: 0;">Trial <b><?=getUserInfo()['trialUserLevelName']?> Plan</b></h3>
	</div>

	<div class="wrap xl-1">
		<div class="col">
		
			<p>Enjoy using Revisionary App <b><?=getUserInfo()['trialUserLevelName']?></b> features and please let us know your thoughts.</p><br>

			<div class="wrap xl-flexbox xl-between">
				<div class="col"><button class="dark small create-project" data-modal="upgrade">UPGRADE NOW</button></div>
				<div class="col"><button class="light small" data-modal="feedback">Any Feedback?</button></div>
				<div class="col"><a href="#" class="cancel-button">Continue with Trial</a></div>
			</div>

		</div>
	</div>


</div>





<div id="trialexpired" class="popup-window xl-center xl-5-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>


	<div class="xl-left">
		<span>Your <b><?=getUserInfo()['trialUserLevelName']?> Plan</b></span>
		<h3 style="margin-top: 0;"><b>Trial Has Expired</b></h3>
	</div>

	<div class="wrap xl-1">
		<div class="col">
		
			<p>You need to upgrade your account to be able to continue using pro features like: </p>

			<ul>
				<li>Unlimited Projects</li>
				<li>Unlimited Pages/Phases</li>
				<li>Unlimited Devices</li>
				<li>Unlimited Live, Style and Comment Pins</li>
				<li>Ability to see content differences</li>
				<li>And many more features...</li>
			</ul><br>

			<div class="wrap xl-flexbox xl-between">
				<div class="col"><button class="dark small create-project" data-modal="upgrade">UPGRADE NOW</button></div>
				<div class="col"><button class="light small" data-modal="feedback">Any Feedback?</button></div>
				<div class="col"><a href="#" class="cancel-button">Continue as Free</a></div>
			</div>

		</div>
	</div>


</div>





<div id="trialcanceled" class="popup-window xl-center xl-5-12">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>


	<div class="xl-left">
		<span>Your <b><?=getUserInfo()['trialUserLevelName']?> Plan</b></span>
		<h3 style="margin-top: 0;"><b>Trial Has Been Cancelled</b></h3>
	</div>

	<div class="wrap xl-1">
		<div class="col">
		
			<p>Would you like to share us your experience with Revisionary App?</p><br>

			<div class="wrap xl-flexbox xl-between">
				<div class="col"><button class="dark small" data-modal="feedback">Give Us a Feedback</button></div>
				<div class="col"><button class="light small cancel-button">Continue Revising</button></div>
			</div>

		</div>
	</div>


</div>





<div id="upgrade" class="popup-window xl-11-12" data-current-plan="<?=getUserInfo()['userLevelName']?>">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<h2 class="xl-center">Choose Your Plan</h2>

	<?php require view('modules/pricing-table'); ?>


</div>





<div id="limit-warning" class="popup-window xl-5-12" data-current-plan="<?=getUserInfo()['userLevelName']?>" data-current-pin-mode="" data-allowed-live-pin="<?=$pinsLeft?>" data-allowed-comment-pin="<?=$commentPinsLeft?>" data-allowed-phase="<?=$phasesLeft?>">
	<a href="#" class="cancel-button" style="position: absolute; right: 20px; top: 20px;"><i class="fa fa-times"></i></a>

	<div class="xl-center">
		<p class="limit-text">
			<b>You have reached your live pin limit.</b> <br> 
			To be able to continue changing content of the page, please upgrade your account.
		</p>
	
		<div class="wrap xl-2 xl-gutter-16 xl-center">
			<div class="col">

				<a href="<?=site_url('upgrade')?>" data-modal="upgrade" class="upgrade-button" style="background-color: green;"><i class="fa fa-angle-double-up"></i> UPGRADE NOW</a>

			</div>
			<div class="col recommendation recommend-live-mode">

				<a href="#" class="upgrade-button invert" data-switch-pin-type="live" data-switch-pin-private="0"><i class="fa fa-dot-circle"></i> Continue with Live Mode</a>

			</div>
			<div class="col recommendation recommend-comment-mode">

				<a href="#" class="upgrade-button invert" data-switch-pin-type="comment" data-switch-pin-private="0"><i class="fa fa-comment"></i> Continue with Comment Mode</a>

			</div>
			<div class="col recommendation recommend-browse-mode">

				<a href="#" class="upgrade-button invert" data-switch-pin-type="browse" data-switch-pin-private="0"><i class="fa fa-mouse-pointer"></i> Continue with Browse Mode</a>

			</div>
		</div>
	</div>


</div>