<?php
/*
Challenge 3: Write a script that prints a list of all of the DNS domains on an account. Let the user select a domain from the list and add an "A" record to that domain by entering an IP Address TTL, and requested "A" record text. This must be done in PHP with php-opencloud.
*/

//error_reporting(0);

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';

// Load libraries from API
use OpenCloud\Rackspace;

?>
