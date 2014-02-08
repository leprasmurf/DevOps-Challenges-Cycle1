<?php
/*
Challenge 2: Write a script that builds anywhere from 1 to 3 512MB cloud servers (the number is based on user input). Inject an SSH public key into the server for login. Return the IP addresses for the server. The servers should take their name from user input, and add a numerical identifier to the name. For example, if the user inputs "bob", the servers should be named bob1, bob2, etc... This must be done in PHP with php-opencloud.
*/

//error_reporting(0);

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';

// Load libraries from API
use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

// Variable declarations
$i = 0;
$location_array = array("DFW", "ORD", "IAD", "LON", "HKG", "SYD");
$build_location = 0;
$image_array = array();
$image_selection = -1;
$build_image = null;
$flavor_array = array();
$flavor_selection = 0;
$build_flavor = null;
$number_of_instances = 1;
$max_instances = 3;
$basename = null;
$servers = array();
$valid = false;
$handle = fopen("php://stdin", "r");
$ssh_key = "ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEA7m5peUIH9fjs81bI3jaj3Hbyi/FQC6RGHfp9YAOq1o2HoVBkb5ewMPZc3A+V2WvIuLmH5I6Epv3vKVhNmOXPiA0c/4AZTTz0QtHULbOINqa4bRw7KrnYD5U0Fr2MY9qaKuBqtk7I2dHWy0uvZW9l9bAmEj/WY4N9JwwnngQf48xqkldCKqf77BrGoTlVRekAQsxqOBb5ACJRzVLGuPL34P0AKNT0x2JpYFqiQQwu6n/XcpKQf+qMhtGkQ2UPofD5ml6pwSIUWAcMTUljOxopNsE4Prs/j4Adf6bOla2M1Bf01hXEovqqZ2PeO+/Y4O0rX9e//mRDeuN9wGSHLnqABQ== leprasmurf@gmail.com";

// Helper function to get numeric input up to $upper_limit
function get_numeric_input($upper_limit) {
	$valid = false;
	$handle = fopen("php://stdin", "r");

	while(!$valid) {
		$line = fgets($handle);
		$input = intval(trim($line));

		if(($input >= 0) && ($input < $upper_limit)) {
			$valid = true;
		} else {
			printf("Invalid selection: %s\n", trim($line));
		}
	}

	return $input;
}

// Helper function to display a list (array) and highlight the $default
function print_list($array_to_display, $default) {
	foreach($array_to_display as $key => $item) {
		if($key == $default) {
			// Ansi escape sequence to underline the default
			printf("\033[4;37;40m");
		}
		// Ansi escape sequence to reset any previous formatting
		printf("%d - %s\033[0;;m\n", $key, $item);
	}
}

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
	'username'	=> 'tforbes',
	'apiKey'	=> '1b0ef2ec2fdc6d657291430578a488a7'
));

// Request user input for the datacenter to buid in
// print out a list of datacenters
printf("Which Datacenter would you like to build the server in?\n");
print_list($location_array, $build_location);

$build_location = get_numeric_input(count($location_array));

printf("Retrieving list of Images...\n");

// Create the compute object, specify the datacenter to work within
$compute = $client->computeService('cloudServersOpenStack', $location_array[$build_location]);

// Grab a list of available images to build an instance with
$images = $compute->imageList();

// Cycle through images and add them to the array
while($image = $images->next()) {
	array_push($image_array, $image->name);
}

// Request user input for the Image to buid
printf("Which Image would you like to use to build the server?\n");
// print out a list of available images
print_list($image_array, $image_selection);
// Read user input and assign selection to variable
$image_selection = get_numeric_input(count($image_array));

// Cycle through the image array
foreach($images as $image) {
	if(strpos($image->name, $image_array[$image_selection]) !== false) {
		$build_image = $image;
		break;
	}
}

printf("Retrieving list of Flavors...\n");

// Grab a list of available flavors of compute instances to build
$flavors = $compute->flavorList();

// Cycle through images and add them to the array
while($flavor = $flavors->next()) {
        array_push($flavor_array, $flavor->name);
}

// Request user input for the Flavor to buid
// print out a list of available flavors
printf("Which Flavor would you like to build?\n");
print_list($flavor_array, $flavor_selection);

$flavor_selection = get_numeric_input(count($flavor_array));

foreach($flavors as $flavor) {
	if(strpos($flavor->name, $flavor_array[$flavor_selection]) !== false) {
		$build_flavor = $flavor;
		break;
	}
}

// Request user input for the number of instances to create
printf("How many servers would you like to create? (0 - 3)\n");
$number_of_instances = get_numeric_input($max_instances+1);

// Request user input for the base name for servers (e.g., bob -> bob1, bob2, bob3)
printf("Please enter a base name for your new servers.\n");
$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = preg_replace('/[^\da-z]/i', '', trim($line));

	if(($input != '') && ($input !== null)) {
		$basename = $input;
		$valid = true;
	} else {
		printf("Invalid input: %s\n", trim($line));
	}
}

// Final verification before running
printf("You have indicated that you want to build %d x %s instances using the %s image in %s with a basename of %s.\n", $number_of_instances, $flavor_array[$flavor_selection], $image_array[$image_selection], $location_array[$build_location], $basename);

printf("Is this correct?\n(y/n) ");
$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = strtolower(trim($line));

	if($input == "y") {
		$valid = true;
	} else if($input == "n") {
		printf("Aborting!\n");
		exit;
	} else {
		printf("Invalid entry: %s\n", trim($line));
	}
}

// Instantiate the compute server class
$server = $compute->server();

// For loop to create $number_of_instances servers
for($i=1; $i <= $number_of_instances; $i++) {
	try {
		$tmp_name = "$basename$i";

		// Create the compute instance with the relevant variables
		$response = $server->create(array(
			'name'		=> $tmp_name,
			'image'		=> $build_image,
			'flavor'	=> $build_flavor,
			'networks'	=> array(
						$compute->network(Network::RAX_PUBLIC),
						$compute->network(Network::RAX_PRIVATE)
					   ),
			'personality'	=> $server->addFile('/root/.ssh/authorized_keys', "$ssh_key")
		));;
		array_push($servers, clone $server);
		//printf("The request to build %s%d has been submitted (ID: %s).  The root password is %s\n", $basename, $i, $server->id, $server->adminPass);
		printf("%s%d (ID: %s) is building.\n", $basename, $i, $server->id, $server->adminPass);
	} catch (\Guzzle\Http\Exception\BadResponseException $e) {
		$responseBody	= (string) $e->getResponse()->getBody();
		$statusCode	= $e->getResponse()->getStatusCode();
		$headers	= $e->getResponse()->getHeaderLines();

		echo sprintf('Status: %s\nBody: %s\nHeaders: %s', $statusCode, $responseBody, implode(', ', $headers));
	}
}

// Run until all instances are done building
while($completed != $number_of_instances) {
	$completed = 0;

	// Check each server and print status line
	for($j = 0; $j < $number_of_instances; $j++) {
		// Refresh server state to check
		$servers[$j]->refresh();

		// If done building, increment completed variable
		if($servers[$j]->status() == ServerState::ACTIVE) {
			$completed++;
		}

		// Check server build for errors
		if(!empty($servers[$j]->error)) {
			var_dump($servers[$j]->error);
			exit;
		} else {
			printf("%s is in state \"%s\" %10s%%\n", $servers[$j]->name(), $servers[$j]->status(), isset($servers[$j]->progress)? $servers[$j]->progress : 0);
		}
	}

	sleep(2);

	// move console up $number_of_instances lines
	for($k = 1; $k <= $number_of_instances; $k++) {
		// Move cursor left 80 spaces
		echo "\033[80D";
//		// Clear to end of line
//		echo "\033[K";
		// Move up a line
		echo "\033[A";
	}
}

for($j = 0; $j < $number_of_instances; $j++) {
	printf("%s%d (ID: %s) build is complete.  IP: %s; Root Password %s\n", $basename, ($j + 1), $servers[$j]->id, $servers[$j]->ip(), $servers[$j]->adminPass);
}

printf("End of Script\n");
?>
