<pre>
<?php
$mc = new Memcached();
$mc->addServer("memcached", 11211);


$mc->set("foo", "Hello!");
$mc->set("bar", "Memcached...");

$arr = array(
    $mc->get("foo"),
    $mc->get("bar")
);
var_dump($arr);



exit;
echo get_redirect_final_target('http://bilaltas.net');


die();

use Aws\S3\S3Client;



// Configure a client using Spaces
$key = $_ENV['S3_KEY'];
$secret = $_ENV['S3_SECRET'];

$space_name = $_ENV['S3_BUCKET'];
$region = $_ENV['S3_REGION'];

$client = new SpacesConnect($key, $secret, $space_name, $region);



/*
//List all files and folders
$result = $space->ListObjects();
*/



/*
//Delete a file/folder.
$result = $space->DeleteObject("test");
*/




// Listing all Spaces in the region
$spaces = $client->ListSpaces();
//die_to_print( $spaces );

foreach ($spaces['Buckets'] as $space){
    echo $space['Name']."\n";
}



// $client->DeleteObject("asd/", true);





//var_dump($result);
exit;






/*
$full_url = 'http://user:pass@www.pref.okinawa.jp:8080/path/to/page.html?query=string#fragment';
$parsed_url = parse_url($full_url);

print_r($parsed_url);


use Pdp\Cache;
use Pdp\CurlHttpClient;
use Pdp\Manager;
use Pdp\Rules;


$manager = new Manager(new Cache(), new CurlHttpClient());
$rules = $manager->getRules(); //$rules is a Pdp\Rules object
$parsed_domain = $rules->resolve($parsed_url['host']); //$domain is a Pdp\Domain object

echo "REG: ".$parsed_domain->getRegistrableDomain();

die_to_print($parsed_domain);
*/





/*
var_dump(

	getUserInfo(3)

); exit;



die_to_print(

	getUserInfo(123)

);
*/



//die_to_print( parseUrl("http://lori.twelve12.co/wp-content/plugins/goodlayers-core/plugins/combine/fontawesome/fontawesome-webfont.eot#1552307384")['full_path'] );


/*
var_dump( Page::ID(6)->getUsers(true) );
exit;
*/

/*
var_dump( User::ID()->canAccess(23, "device") );
exit;
*/


/*
$date = new DateTime('2019-03-10 21:22:36');
echo $date->format('g:i A - F j, Y');
exit;
*/

/*
var_dump( checkAvailableUserName('bill-tas') );

exit;



$notifications = User::ID()->getNotifications();
die_to_print($db->totalCount);

var_dump( $notifications );
exit;

$i = 0;
while ($i < 10) { $i++;


	//var_dump( User::ID($i)->canAccess(33, "pin") );

	Notify::ID(6)->web("sent test notification $i", "pin", 1);
	sleep(2);

}

exit;
*/

/*
$parsed_url = User::ID()->getNotifications();
die_to_print( $parsed_url );
*/



//echo Project::ID(6)->getInfo('user_ID');

/*
$the_user = User::ID();

print_r($the_user);

if ( $the_user ) echo "YES";
else var_dump($the_user);
*/



/*
// Your crawler was sent to this page.
$url = 'http://example.com/page';

// Example of a relative link of the page above.
$relative = '/hello/index.php';

// Parse the URL the crawler was sent to.
$url = parse_url($url);

if(FALSE === filter_var($relative, FILTER_VALIDATE_URL))
{
    // If the link isn't a valid URL then assume it's relative and
    // construct an absolute URL.
    print $url['scheme'].'://'.$url['host'].'/'.ltrim($relative, '/');
}
exit;
*/





/*
var_dump(split_url('https://www.apple.com/wss/fonts?families=SF+Pro,v2|SF+Pro+Icons,v1'));
die();
*/

// var_dump(url_to_absolute('https://www.apple.com/wss/fonts?families=SF+Pro,v2|SF+Pro+Icons,v1', '/wss/fonts/SF-Pro-Display/v2/sf-pro-display_regular-italic.ttf'));


/*
Mail::ASYNCSEND(
	'bilalltas@gmail.com,bill@twelve12.com',
	urlencode('Bulk mail test'),
	urlencode('Test mail')
);
die();
*/

/*
Mail::SEND(
	'bilalltas@gmail.com,bill@twelve12.com',
	'asdasd',
	'Test mail'
);
die();
*/

/*
$message = "Test Message çıktı";
//die_to_print( urldecode( urlencode($message) ) );



$sent = Notify::ID([1, 5])->mail(
	'Konu test',
	$message
);
if ($sent) echo "SENT! $sent";
*/