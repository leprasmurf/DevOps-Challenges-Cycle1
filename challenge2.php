<?php

error_reporting(0);

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
$valid = false;
$handle = fopen("php://stdin", "r");

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
printf("How many servers would you like to create? (0 - 3)\n");
$number_of_instances = get_numeric_input($max_instances+1);

printf("Please enter a base name for your new servers.\n");
$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = preg_replace('/[^\da-z]/i', '', trim($line));

	if(($input != '') && ($input !== null)) {
		$basename = $input;
//		printf("You selected the name %s.\n", $basename);
		$valid = true;
	} else {
		printf("Invalid input: %s\n", trim($line));
	}
}

printf("You have indicated that you want to build %d %s instances using the %s image in %s with a basename of %s.\n", $number_of_instances, $flavor_array[$flavor_selection], $image_array[$image_selection], $location_array[$build_location], $basename);

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

for($i=1; $i <= $number_of_instances; $i++) {
	try {
		$tmp_name = "$basename$i";

		// Create the compute instance
		$response = $server->create(array(
			'name'		=> $tmp_name,
			'image'		=> $build_image,
			'flavor'	=> $build_flavor,
			'networks'	=> array(
				$compute->network(Network::RAX_PUBLIC),
				$compute->network(Network::RAX_PRIVATE)
			)
		));;
		printf("The request to build %s%d has been submitted.  The root password is %s\n", $basename, $i, $server->adminPass);
	} catch (\Guzzle\Http\Exception\BadResponseException $e) {
		$responseBody	= (string) $e->getResponse()->getBody();
		$statusCode	= $e->getResponse()->getStatusCode();
		$headers	= $e->getResponse()->getHeaderLines();

		echo sprintf('Status: %s\nBody: %s\nHeaders: %s', $statusCode, $responseBody, implode(', ', $headers));
	}
}

/*
// Callback function to monitor build progress
$callback = function($server) {
	if(!empty($server->error)) {
		var_dump($server->error);
		exit;
	} else {
		echo "\033[80D";
		echo "\033[K";
		echo sprintf(
				"Waiting on %s/%-12s %4s%%",
				$server->name(),
				$server->status(),
				isset($server->progress)? $server->progress : 0
			    );
	}
};

// Loop to check on build progress
$server->waitFor(ServerState::ACTIVE, 600, $callback);

echo "\n";

printf("The new server has been created (ID: %s).\nThe Root password is %s\n", $server->id, $server->adminPass);

sleep(10);

printf("Deleting server %s.\n", $server->id);

$server->Delete();
*/
?>
