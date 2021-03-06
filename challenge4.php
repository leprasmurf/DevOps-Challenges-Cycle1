<?php
/*
Challenge 4: Write a script that creates a Cloud Files Container. If the container already exists, exit and let the user know. The script should also upload a directory from the local filesystem to the new container, and enable CDN for the new container. The script must return the CDN URL. This must be done in PHP with php-opencloud.
*/

//error_reporting(0);

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';
// Grab Username and API key
require '/home/leprasmurf/.rackspace_api.php';

// Load libraries from API
use OpenCloud\Rackspace;

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
        'username'      => $username,
        'apiKey'        => $apikey
));

?>
