<?php require view('static/header_html'); ?>

<script>
// When page is ready
$(function(){

	// Post the request to AJAX
	var processInterval = setInterval(function(){

		// Send the new data with process ID
		$.post(ajax_url, {
			'type':'internalize-status',
			'page_ID': <?=$_url[1]?>,
			'queue_ID': <?=is_numeric($queue_ID) ? $queue_ID : "''"?>,
			'processID' : <?=$process->getPid()?>
		}, function(result){


			$.each(result.data, function(key, data){

				// Append the log !!!
				if (key != "final")	console.log(key, data);


				// Update the proggress bar
				var width = 0;
				if (data.processStatus == 'downloading')
					width = 12.5;

				if (data.processStatus == 'downloading-html')
					width = 12.5;

				if (data.processStatus == 'downloaded-html')
					width = 25;

				if (data.processStatus == 'downloading-css')
					width = 25;

				if (parseFloat(data.totalCss) > 0)
					width = width + (35 * parseFloat(data.downloadedCss) / parseFloat(data.totalCss) );

				if (data.processStatus == 'downloaded-css')
					width = 60;


				if (data.processStatus == 'downloading-fonts')
					width = 60;

				if (parseFloat(data.totalFont) > 0)
					width = width + (40 * parseFloat(data.downloadedFont) / parseFloat(data.totalFont) );

				if (data.processStatus == 'downloaded-fonts')
					width = 100;


				if (data.processStatus == 'ready')
					width = 100;

				$('.progress').css('width', width + "%" );


				// Print the current status
				$('#loading-info').text( Math.round(width) + '% ' + data.processDescription + '...');


				// Don't repeat checking when done
				if (data.status == "not-running") {
					clearInterval(processInterval);
					if (width != 100) $('#loading-info').text( 'Error');
				}


				// If successfully downloaded
				if (data.processStatus == "ready") {

					// Update the iframe url
					$('iframe').attr('src', data.pageUrl);

					// Run the inspector
					runTheInspector();

					// When iframe loaded
					$('iframe').on('load', function(){

						// Close Pin Mode pinModeSelector - If on revise mode !!!
						toggleCursorActive();

						// Hide the loading overlay
						$('#loading').fadeOut();

						// Close all the tabs
						$('.opener').each(function() {

							toggleTab( $(this) );

						});

						// Body class
						$('body').addClass('ready');

					});

				}



			});

		}, 'json');


	}, 500); // Interval

});
</script>

<div id="loading" class="overlay">
	<div class="progress-bar">
		<div class="progress">
			<div class="gradient"></div>
		</div>
	</div>
	<div class="progress-info">
		<ul>
<!--
			<li>style.css - Downloading</li>
			<li>style.css - Downloaded</li>
-->
		</ul>
	</div>


	<span class="loading-text"><div class="gps_ring"></div> <span id="loading-info">LOADING...</span></span>
</div>

<div id="pin-mode-selector" class="overlay" style="display: none;"></div>

<div id="page" class="site">

	<div class="iframe-container">

		<iframe id="the-page" src="" data-url="" width="<?=$width?>" height="<?=$height?>" scrolling="auto" style="min-width: <?=$width?>px; min-height: <?=$height?>px;"></iframe>

	</div>

	<main>

		<div id="revise-sections">
			<div class="top-left pins">

				<div class="tab wrap open">
					<div class="col xl-1-1">

						<div class="pins-filter">
							<a href="#" class="selected">Show All</a> |
							<a href="#">Only Incompleted</a> |
							<a href="#">Only Completed</a>
						</div>

						<div class="scrollable-content">
							<div class="pins-list">

								<div class="pin standard incomplete">

									<a href="#" class="pin-locator">
										<pin class="mid" data-pin-mode="standard">1
											<div class="notif-no">2</div>
										</pin>
									</a>

									<a href="#" class="pin-title close">Comment Pin <i class="fa fa-caret-up" aria-hidden="true"></i></a>
									<div class="pin-comments">

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Ike Elimsa</a> <span class="comment-date">32 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12 xl-last xl-right" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(1)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap xl-flexbox user-info">
													<div class="col xl-8-12 xl-last xl-right comment-user-name">
														<span class="comment-date">29 minutes ago</span> <a href="#">Bilal TAS</a>
													</div>
													<div class="col xl-4-12 xl-left comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col xl-right">
														Alright, updated on the site!
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(4)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Matt Pasaoglu</a> <span class="comment-date">22 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur.
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
								<div class="pin live incomplete">

									<a href="#" class="pin-locator">
										<pin class="mid" data-pin-mode="live">2
											<!-- <div class="notif-no">2</div> -->
										</pin>
									</a>

									<a href="#" class="pin-title close">Live Edit and Comment Pin <i class="fa fa-caret-up" aria-hidden="true"></i></a>
									<div class="pin-comments">

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Ike Elimsa</a> <span class="comment-date">32 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12 xl-last xl-right" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(1)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap xl-flexbox user-info">
													<div class="col xl-8-12 xl-last xl-right comment-user-name">
														<span class="comment-date">29 minutes ago</span> <a href="#">Bilal TAS</a>
													</div>
													<div class="col xl-4-12 xl-left comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col xl-right">
														Alright, updated on the site!
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(4)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Matt Pasaoglu</a> <span class="comment-date">22 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur.
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
								<div class="pin live complete">

									<a href="#" class="pin-locator">
										<pin class="complete mid" data-pin-mode="live">3
											<!-- <div class="notif-no">1</div> -->
										</pin>
									</a>

									<a href="#" class="pin-title close">Live Edit and Comment Pin <i class="fa fa-caret-up" aria-hidden="true"></i></a>
									<div class="pin-comments">

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Ike Elimsa</a> <span class="comment-date">32 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12 xl-last xl-right" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(1)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap xl-flexbox user-info">
													<div class="col xl-8-12 xl-last xl-right comment-user-name">
														<span class="comment-date">29 minutes ago</span> <a href="#">Bilal TAS</a>
													</div>
													<div class="col xl-4-12 xl-left comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col xl-right">
														Alright, updated on the site!
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(4)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Matt Pasaoglu</a> <span class="comment-date">22 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur.
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
								<div class="pin private incomplete">

									<a href="#" class="pin-locator">
										<pin class="mid" data-pin-mode="private">4
											<!-- <div class="notif-no">2</div> -->
										</pin>
									</a>

									<a href="#" class="pin-title close">Private Comment Pin <i class="fa fa-caret-up" aria-hidden="true"></i></a>
									<div class="pin-comments">

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Ike Elimsa</a> <span class="comment-date">32 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12 xl-last xl-right" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(1)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap xl-flexbox user-info">
													<div class="col xl-8-12 xl-last xl-right comment-user-name">
														<span class="comment-date">29 minutes ago</span> <a href="#">Bilal TAS</a>
													</div>
													<div class="col xl-4-12 xl-left comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col xl-right">
														Alright, updated on the site!
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(4)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Matt Pasaoglu</a> <span class="comment-date">22 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur.
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
								<div class="pin private incomplete">

									<a href="#" class="pin-locator">
										<pin class="mid" data-pin-mode="private-live">5
											<!-- <div class="notif-no">2</div> -->
										</pin>
									</a>

									<a href="#" class="pin-title close">Private Live Edit and Comment Pin <i class="fa fa-caret-up" aria-hidden="true"></i></a>
									<div class="pin-comments">

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Ike Elimsa</a> <span class="comment-date">32 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12 xl-last xl-right" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(1)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap xl-flexbox user-info">
													<div class="col xl-8-12 xl-last xl-right comment-user-name">
														<span class="comment-date">29 minutes ago</span> <a href="#">Bilal TAS</a>
													</div>
													<div class="col xl-4-12 xl-left comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col xl-right">
														Alright, updated on the site!
													</div>
												</div>
											</div>
										</div>

										<div class="comment wrap xl-flexbox xl-top">
											<a class="col xl-2-12" href="#">
												<picture class="profile-picture big square"
													style="background-image: url(<?=User::ID(4)->userPicUrl?>);">
													<div class="new-comment-icon">*</div>
												</picture>
											</a>
											<div class="col xl-10-12 comment-inner-wrapper">
												<div class="wrap user-info">
													<div class="col xl-8-12 comment-user-name">
														<a href="#">Matt Pasaoglu</a> <span class="comment-date">22 minutes ago</span>
													</div>
													<div class="col xl-4-12 xl-right comment-date"></div>
												</div>
												<div class="wrap xl-1 comment-text">
													<div class="col">
														Lorem ipsum dolor sit amet, consectetur.
													</div>
												</div>
											</div>
										</div>

									</div>
								</div>
								<div class="pin live incomplete">

									<a href="#" class="pin-locator">
										<pin class="mid" data-pin-mode="live">6
											<!-- <div class="notif-no">2</div> -->
										</pin>
									</a>

									<a href="#" class="pin-title">New Pin</a>
								</div>

							</div>
						</div>
					</div>
					<div class="opener">
						<a href="#">PINS</a>
					</div>
				</div>

			</div>


			<div class="top-right share xl-right">

				<div class="tab wrap open">
					<div class="col xl-1-1 xl-center">

						<div class="share-tab-content">

							<div class="pin-share">
								<a href="#">Share only specific Pins <i class="fa fa-question-circle" aria-hidden="true"></i></a>
								<div class="shared-members">
									<span class="people light-border">

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(1)->userPicUrl?>);"></picture>
										</a>

									</span>

									<a class="member-selector" href="#">
										<i class="fa fa-plus" aria-hidden="true"></i>
									</a>
								</div>
							</div>

							<div class="page-share">
								<a href="#">Share only this Page <i class="fa fa-question-circle" aria-hidden="true"></i></a>
								<div class="shared-members">
									<span class="people light-border">

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(5)->userPicUrl?>);"></picture>
										</a>

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(4)->userPicUrl?>);"></picture>
										</a>

									</span>

									<a class="member-selector" href="#">
										<i class="fa fa-plus" aria-hidden="true"></i>
									</a>
								</div>
							</div>

							<div class="project-share">
								<a href="#">Share this Project  <i class="fa fa-question-circle" aria-hidden="true"></i></a>
								<div class="shared-members">
									<span class="people light-border">

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(1)->userPicUrl?>);"></picture>
										</a>

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(2)->userPicUrl?>);"></picture>
										</a>

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(3)->userPicUrl?>);"></picture>
										</a>

										<a href="#">
											<picture class="profile-picture" style="background-image: url(<?=User::ID(4)->userPicUrl?>);"></picture>
										</a>

									</span>

									<a class="member-selector" href="#">
										<i class="fa fa-plus" aria-hidden="true"></i>
									</a>
								</div>
							</div>

							<div class="link-share">
								<a href="#"><i class="fa fa-link" aria-hidden="true"></i> Get Shareable Link... <i class="fa fa-question-circle" aria-hidden="true"></i></a>
							</div>

							<div class="link-share">
								<a href="#"><i class="fa fa-link" aria-hidden="true"></i> Share on my public profile... <i class="fa fa-question-circle" aria-hidden="true"></i></a>
							</div>

						</div>
					</div>
					<div class="opener">
						<a href="#">SHARE</a>
					</div>
				</div>

			</div>


			<div class="bottom-left info">

				<div class="tab wrap open">
					<div class="col xl-8-12 xl-left">


						<div class="breadcrumbs">
							<a href="<?=site_url('projects')?>" class="projects">
								<?=Project::ID($projectID)->getProjectInfo('project_name')?> <i class="fa fa-caret-down" aria-hidden="true"></i>
							</a>
							<sep>></sep>

							<?php
							if ($pageCat['cat_name'] != "") {
							?>
							<a href="<?=site_url('project/'.$projectID.'/'.permalink($pageCat['cat_name']))?>" class="pages">
								<?=$pageCat['cat_name']?> <i class="fa fa-caret-down" aria-hidden="true"></i>
							</a>
							<sep>></sep>
							<?php
							}
							?>

							<a href="<?=site_url('project/'.$projectID)?>" class="sections">
								<?=Page::ID($page_ID)->getPageInfo('page_name')?> <i class="fa fa-caret-down" aria-hidden="true"></i>
							</a>
						</div>


						<div class="date created">Date Created: <span><?=date( "d M Y, g:i A", strtotime(Page::ID($page_ID)->getPageInfo('page_created')) )?></span></div>
						<div class="date updated">Last Updated: <span><?=date( "d M Y, g:i A", strtotime(Page::ID($page_ID)->getPageInfo('page_modified')) )?></span></div>


					</div>
					<div class="col xl-4-12 xl-center">


						<div class="device-selector">
							<a href="#" class="select-device">Device <i class="fa fa-caret-down" aria-hidden="true"></i></a>
							<div class="device-icon"><i class="fa <?=$deviceIcon?>" aria-hidden="true"></i></div>
						</div>
						<a href="#" class="version-selector"><?=Page::ID($page_ID)->pageVersion?></a>


					</div>
					<div class="opener">
						<a href="#">INFO</a>
					</div>
				</div>


			</div>


			<div class="bottom-right help xl-right">

				<div class="tab wrap open">
					<div class="col xl-1-1">
						Help Content<br/><br/><br/>
					</div>
					<div class="opener" style="transform: skewX(-45deg);background-color: black;padding-right: 10px;right: 95%;">
						<a href="#" style="transform: skewX(45deg);font-size: 10px;padding-bottom: 0;padding-left: 3px;padding-right: 16px;">?</a>
					</div>
				</div>

			</div>
		</div>


		<div class="add-pin-options">

			<a href="#" class="inspect-activator">
				<pin class="add-new big" data-pin-mode="live">+</pin>
			</a>
			<a href="#" class="pin-mode-selector">
				<i class="fa fa-caret-down" aria-hidden="true"></i>
				<i class="fa fa-caret-up open-icon" aria-hidden="true"></i>
			</a>

			<div class="pin-modes">
				<a href="#" class="activate-live-mode">
					LIVE PREFERRED COMMENT <pin class="add-new" data-pin-mode="live">+</pin>
				</a>
				<a href="#" class="activate-standard-mode">
					ONLY COMMENT <pin class="add-new" data-pin-mode="standard">+</pin>
				</a>
				<a href="#" class="activate-private-live-mode">
					LIVEABLE PRIVATE COMMENT <pin class="add-new" data-pin-mode="private-live">+</pin>
				</a>
			</div>

		</div>
		<pin class="mouse-cursor big" data-pin-mode="live">1</pin>


	</main> <!-- main -->
</div> <!-- #page.site -->

<?php require view('static/footer_html'); ?>