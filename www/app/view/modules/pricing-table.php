<table class="pricing">
	<tr>
		<th class="feature"><span>Features</span></th>
		<?php foreach ($user_levels as $user_level) { ?>
		<th class="align-center"><?=$user_level['user_level_name']?></th>
		<?php } ?>
	</tr>
	<tr>
		<td># of Projects</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center"><?=$user_level['user_level_max_project'] == 99999 ? "Unlimited" : $user_level['user_level_max_project']?></td>
		<?php } ?>
	</tr>
	<tr>
		<td># of Pages & Phases & Design Uploads</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center"><?=$user_level['user_level_max_page'] == 99999 ? "Unlimited" : $user_level['user_level_max_project']?></td>
		<?php } ?>
	</tr>
	<tr>
		<td># of Screens</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center">Unlimited</td>
		<?php } ?>
	</tr>
	<tr>
		<td># of Live Content & Style Pins</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center"><?=$user_level['user_level_max_live_pin'] == 99999 ? "Unlimited" : $user_level['user_level_max_live_pin']?></td>
		<?php } ?>
	</tr>
	<tr>
		<td># of Only Comment Pins</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center"><?=$user_level['user_level_max_comment_pin'] == 99999 ? "Unlimited" : $user_level['user_level_max_comment_pin']?></td>
		<?php } ?>
	</tr>
	<tr>
		<td># of Users</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center">Unlimited</td>
		<?php } ?>
	</tr>
	<tr>
		<td>Max Load</td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center"><?=$user_level['user_level_max_load'] == 2048 ? "2GB" : $user_level['user_level_max_load']."MB"?></td>
		<?php } ?>
	</tr>
	<tr>
		<td>See Content Differences</td>
		<td class="align-center">-</td>
		<td class="align-center">Yes</td>
		<td class="align-center">Yes</td>
	</tr>
	<tr>
		<td>FreeHand Draw (Coming Soon)</td>
		<td class="align-center">-</td>
		<td class="align-center">Yes</td>
		<td class="align-center">Yes</td>
	</tr>
	<tr>
		<td>Site Backup (Coming Soon)</td>
		<td class="align-center">-</td>
		<td class="align-center">-</td>
		<td class="align-center">Yes</td>
	</tr>


	<tr>
		<td></td>
		<?php foreach ($user_levels as $user_level) { ?>
		<td class="align-center">
			$<?=$user_level['user_level_price']?><span><?=$user_level['user_level_price'] != 0 ? "/m" : ""?></span><br><br>
			<?php
			if ( getUserInfo()['userLevelName'] == $user_level['user_level_name'] && $page_title == "Upgrade" ) {
				
				echo "<b>Your Current Plan</b>";

				if (getUserInfo()['userLevelName'] != "Free") echo "<a href='#' class='cancel-plan' data-tooltip='In development...'>Cancel</a>";

			} else {
			?>
			<a href='#' class='upgrade-button <?=$user_level['user_level_name'] == "Free" ? "invisible" : ""?>' data-tooltip='In development...'>Upgrade to <?=strtoupper($user_level['user_level_name'])?></a>
			<?php } ?>
		</td>
		<?php } ?>
	</tr>
</table>