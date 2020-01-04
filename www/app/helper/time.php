<?php

function currentTimeStamp($modify = null) {

   $stop_date = new DateTime();
   if ($modify) $stop_date->modify($modify); // For example: +1 day
   return $stop_date->format('Y-m-d H:i:s');

}


function timeago($date) {
   $timestamp = strtotime($date);

   $strTime = array("second", "minute", "hour", "day", "month", "year");
   $length = array("60","60","24","30","12","10");

   $currentTime = time();

   //return $timestamp." - ".$currentTime;
   if($currentTime >= $timestamp) {
		$diff     = time()- $timestamp;
		for($i = 0; $diff >= $length[$i] && $i < count($length)-1; $i++) {
		$diff = $diff / $length[$i];
		}

		$diff = round($diff);
		return $diff . " " . $strTime[$i] . ($diff > 1 ? 's' : '') . " ago";
   }
}