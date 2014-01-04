<?php

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';

// Load libraries from API
use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

// Variable declarations
$i = 0;
$location_array = new array();
$build_location = 0;
$image_array = new array();
$build_image = 0;
$flavor_array = new array();
$build_flavor = 0;

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
	'username'	=> 'tforbes',
	'apiKey'	=> '1b0ef2ec2fdc6d657291430578a488a7'
));

// Create the compute object, specify the datacenter to work within
$compute = $client->computeService('cloudServersOpenStack', 'ORD');

// Grab a list of available images to build an instance with
$images = $compute->imageList();

$i=0;
// Cycle through images and grab the first one that matches on the name
while($image = $images->next()) {
	printf("%d - %s\n", $i, $image->name);
	$i++;
/*
	if(strpos($image->name, 'Scientific Linux') !== false) {
		$build_image = $image;
		break;
	}
*/
}

// Grab a list of available flavors of compute instances to build
$flavors = $compute->flavorList();

$i=0;
// Cycle through the list of flavors for the one that matches on the string
while($flavor = $flavors->next()) {
	printf("%d - %s\n", $i, $flavor->name);
	$i++;
	/*
	if(strpos($flavor->name, '512MB') !== false) {
		$server_flavor = $flavor;
		break;
	}
	*/
}

/*
// Instantiate the compute server class
$server = $compute->server();

try {
	// Create the compute instance
	$response = $server->create(array(
		'name'		=> 'DevOps Challenge 1',
		'image'		=> $build_image,
		'flavor'	=> $server_flavor,
		'networks'	=> array(
			$compute->network(Network::RAX_PUBLIC),
			$compute->network(Network::RAX_PRIVATE)
		)
	));;
} catch (\Guzzle\Http\Exception\BadResponseException $e) {
	$responseBody	= (string) $e->getResponse()->getBody();
	$statusCode	= $e->getResponse()->getStatusCode();
	$headers	= $e->getResponse()->getHeaderLines();

	echo sprintf('Status: %s\nBody: %s\nHeaders: %s', $statusCode, $responseBody, implode(', ', $headers));
}

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
