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
$build_image = -1;
$flavor_array = array();
$build_flavor = 0;
$number_of_instances = 1;
$max_instances = 1000;
$basename = null;
$valid = false;

$handle = fopen("php://stdin", "r");

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
	'username'	=> 'tforbes',
	'apiKey'	=> '1b0ef2ec2fdc6d657291430578a488a7'
));

// Request user input for the datacenter to buid in
// print out a list of datacenters
printf("Which Datacenter would you like to build the server in?\n");
foreach ($location_array as $key => $location) {
	if($key == $build_location) {
		printf("\033[4;37;40m");
	}
	printf("%d - %s\033[0;;m\n", $key, $location);
}

while(!$valid) {
	$line = fgets($handle);
	$input = intval(trim($line));

	if(($input >= 0) && ($input < count($location_array))) {
		$build_location = $input;
//		printf("You selected to build the instance in %s\n", $location_array[$build_location]);
		$valid = true;
	} else {
		printf("Invalid selection: %s\n", trim($line));
	}
}

// Create the compute object, specify the datacenter to work within
$compute = $client->computeService('cloudServersOpenStack', $location_array[$build_location]);

// Grab a list of available images to build an instance with
$images = $compute->imageList();

// Cycle through images and add them to the array
while($image = $images->next()) {
	array_push($image_array, $image->name);
}

// Request user input for the Image to buid
// print out a list of available images
printf("Which Image would you like to use to build the server?\n");
foreach ($image_array as $key => $image) {
	if($key == $build_image) {
		printf("\033[4;37;40m");
	}
	printf("%d - %s\033[0;;m\n", $key, $image);
}

$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = intval(trim($line));

	if(($input >= 0) && ($input < count($image_array))) {
		$build_image = $input;
//		printf("You selected to build the %s image.\n", $image_array[$build_image]);
		$valid = true;
	} else {
		printf("Invalid selection: %s\n", trim($line));
	}
}

//echo gettype($images);
print_r($images);

// Grab a list of available flavors of compute instances to build
$flavors = $compute->flavorList();

// Cycle through images and add them to the array
while($flavor = $flavors->next()) {
        array_push($flavor_array, $flavor->name);
}

// Request user input for the Flavor to buid
// print out a list of available flavors
printf("Which Flavor would you like to build?\n");
foreach($flavor_array as $key => $flavor) {
	if($key == $build_flavor) {
		printf("\033[4;37;40m");
	}
	printf("%d - %s\033[0;;m\n", $key, $flavor);
}

$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = intval(trim($line));

	if(($input >= 0) && ($input < count($flavor_array))) {
		$build_flavor = $input;
//		printf("You selected to build a %s server.\n", $flavor_array[$build_flavor]);
		$valid = true;
	} else {
		printf("Invalid selection: %s\n", trim($line));
	}
}

printf("How many servers would you like to create?\n");
$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = intval(trim($line));
	
	if(($input >= 0) && ($input < $max_instances)) {
		$number_of_instances = $input;
//		printf("You selected to build %d servers.\n", $number_of_instances);
		$valid = true;
	} else {
		printf("Invalid selection: %s\n", trim($line));
	}
}

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

printf("You have indicated that you want to build %d %s instances using the %s image in %s with a basename of %s.\n", $number_of_instances, $flavor_array[$build_flavor], $image_array[$build_image], $location_array[$build_location], $basename);

printf("Is this correct?\n(y/n) ");
$valid = false;
while(!$valid) {
	$line = fgets($handle);
	$input = strtolower(trim($line));

	if($input == "y") {
		$valid = true;
	} else if($input == "n") {
		printf("Aborting!");
		exit;
	} else {
		printf("Invalid entry: %s\n", trim($line));
	}
}

// Instantiate the compute server class
$server = $compute->server();

for($i=0; $i < $number_of_instances; $i++) {
	try {
		$tmp_name = "$basename$i";

		// Create the compute instance
		$response = $server->create(array(
			'name'		=> $tmp_name,
			'image'		=> $images[$build_image],
			'flavor'	=> $flavors[$build_flavor],
			'networks'	=> array(
				$compute->network(Network::RAX_PUBLIC),
				$compute->network(Network::RAX_PRIVATE)
			)
		));;
		printf("The request to build %s%d has been submitted.  The root password is %s", $basename, $i, $server->adminPass);
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
