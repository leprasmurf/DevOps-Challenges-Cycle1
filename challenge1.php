<?php
/*
Challenge 1: Write a script that builds a 512MB Cloud Server and returns the root password and IP address for the server. This must be done in PHP with php-opencloud
*/
// Initial setup: https://github.com/rackspace/php-opencloud/blob/master/docs/getting-started.md

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';
// Grab Username and API key
require '/home/leprasmurf/.rackspace_api.php';

// Load libraries from API
use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\Network;
use OpenCloud\Compute\Constants\ServerState;

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
	'username'	=> $username,
	'apiKey'	=> $apikey
));

// Create the compute object, specify the datacenter to work within
$compute = $client->computeService('cloudServersOpenStack', 'ORD');

// Grab a list of available images to build an instance with
$images = $compute->imageList();

// Cycle through images and grab the first one that matches on the name
while($image = $images->next()) {
	if(strpos($image->name, 'Scientific Linux') !== false) {
		$build_image = $image;
		break;
	}
}

// Grab a list of available flavors of compute instances to build
$flavors = $compute->flavorList();

// Cycle through the list of flavors for the one that matches on the string
while($flavor = $flavors->next()) {
	if(strpos($flavor->name, '512MB') !== false) {
		$server_flavor = $flavor;
		break;
	}
}

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

/*
// Delete command added for script debugging
sleep(10);

printf("Deleting server %s.\n", $server->id);

$server->Delete();
*/
?>
