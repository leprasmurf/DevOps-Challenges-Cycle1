<?php
/*
Challenge 3: Write a script that prints a list of all of the DNS domains on an account. Let the user select a domain from the list and add an "A" record to that domain by entering an IP Address TTL, and requested "A" record text. This must be done in PHP with php-opencloud.
*/

//error_reporting(0);

// Require Autoload for composer tool to include Rackspace API
require 'vendor/autoload.php';
// Grab Username and API key
require '/home/leprasmurf/.rackspace_api.php';

// Load libraries from API
use OpenCloud\Rackspace;
use OpenCloud\DNS;
use OpenCloud\DNS\Resource\Record;

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

function get_text_input() {
	$valid = false;
	$handle = fopen("php://stdin", "r");

	while(!$valid) {
		$line = fgets($handle);
		$input = trim($line);

		if(($input != '') && ($input !== null)) {
			$valid = true;
		} else {
			printf("Invalid input: %s\n", $input);
		}
	}

	return $input;
}

// ANSI Escape sequences
// http://ascii-table.com/ansi-escape-sequences.php
// Clear the screen
echo "\033[2J";

echo "Retrieving list of DNS Domains.\n";

// Setup the client with the appropriate credentials
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array (
        'username'      => $username,
        'apiKey'        => $apikey
));

// Create the DNS object to interact with
$dns = $client->dnsService();

// Grab a list of all domains
$domain_list = $dns->domainList();

// Counter variable
$domain_count = 0;

// Check the size of the domain list
if($domain_list->count() == 0) {
	// No Domains listed
	printf("There are no domains associated with this account.\n");
	exit;
}

$valid = false;
$selected_domain;

// Ask the user to select one of the listed domains by count
echo "Select the domain to add an 'A' record to\n";
// cycle through each domain and print it out
foreach($domain_list as $domain) {
	echo "\t$domain_count - " . $domain->name . "\n";
	$domain_count++;
}
echo ": ";

$selection = get_numeric_input($domain_count);
// Reset the domain count
$domain_count = 0;

// Cycle through the domains again to find the domain selected
foreach($domain_list as $domain) {
	if($domain_count == $selection) {
		//printf("You selected: %s\n", $domain->name);
		$selected_domain = $domain;
	}
}

/*
echo "Enter the IP address for the 'A' record\n";
echo ": ";
$ip = get_text_input();

echo "Enter the Text address for the 'A' record\n";
echo ": ";
$dest = get_text_input();

echo "Enter the TTL for the 'A' record\n";
echo ": ";
// Theoretical max size of a PHP int on a 32 bit system
$ttl = get_numeric_input(2000000000);

printf("You want to add an 'A' record to %s.  %s -> %s (TTL: %d)\n", $selected_domain->name, $ip, $dest, $ttl);

/*
// Create the new Record to add to the domain
$record = new Record();
$record->ttl = $ttl;
$record->type = 'A';
$record->name = $dest;
$record->data = $ip;
*/
$record = new Record();
$record->ttl = '3600';
$record->type = 'A';
$record->name = 'devops.geekforbes.com';
$record->data = '1.2.3.4';


$selected_domain->addRecord($record);
$selected_domain->update();
//var_dump($selected_domain);

echo "Done.\n";
?>
