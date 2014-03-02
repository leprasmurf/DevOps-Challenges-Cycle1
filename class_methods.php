<?php
/*
Display the Service Catalog available (https://github.com/rackspace/php-opencloud/blob/master/docs/userguide/Clients.md)
*/

//error_reporting(0);

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';
// Grab Username and API key
require '/home/leprasmurf/.rackspace_api.php';

// Load libraries from API
use OpenCloud\Rackspace;

function list_class_methods($obj) {
	foreach(get_class_methods($obj) as $method) {
		echo "\t$method\n";
	}
}

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
        'username'      => $username,
        'apiKey'        => $apikey
));

$client->authenticate();

/** @var OpenCloud\Common\Service\Catalog */
$catalog = $client->getCatalog();

echo "Client Methods:\n";
list_class_methods($client);

$dns = $client->dnsService();
echo "DNS Methods:\n";
list_class_methods($dns);

$domain_list = $dns->domainList();
echo "Domain List:\n";
list_class_methods($domain_list);

foreach($domain_list as $domain) {
	echo "Domain:\n";
	list_class_methods($domain);
	break;
}

// Return a list of OpenCloud\Common\Service\CatalogItem objects
foreach ($catalog->getItems() as $catalogItem) {

	$name = $catalogItem->getName();
	$type = $catalogItem->getType();

	if ($name == 'cloudServersOpenStack' && $type == 'compute') {
		break;
	}

#	// Array of OpenCloud\Common\Service\Endpoint objects
#	$endpoints = $catalogItem->getEndpoints();
#	foreach ($endpoints as $endpoint) {
#		if ($endpoint->getRegion() == 'DFW') {
#			echo $endpoint->getPublicUrl();
#		}
#	}

	echo "$name\t - $type\n";
}

?>
