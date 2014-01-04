<?php

require 'vendor/autoload.php';

use OpenCloud\Rackspace;

$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
	'username'	=> 'foo',
	'apiKey'	=> 'bar'
));

/*
$client = new OpenStack('http://identity.my-openstack.com/v2.0/', array(
	'username'	=> 'foo',
	'password'	=> 'bar'
));
*/

$compute = $client->computeService('cloudServersOpenStack', 'ORD');


?>
