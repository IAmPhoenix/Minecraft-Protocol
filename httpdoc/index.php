<?php
// PHP Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Max-Age: 30');

// The IP to check
$server = 'localhost';

// Implements the Cache handler
require_once ('../classes/Cache.php');
// Implements the Minecraft API
require_once ('../classes/MinecraftAPI.php');

// Instantiate the Cache handler
$Cache = new Cache();
// Instantiate the API and set the desired variables
$MinecraftAPI = new MinecraftAPI( $Cache, $server, 'cache' );

// Set cache time
$MinecraftAPI->getCache()->setConfig( 'cacheTime', 30 );

// Initialize the cache folder
$MinecraftAPI->getCache()->init( );
// Get the cached information
$response = $MinecraftAPI->getCache()->get( $MinecraftAPI->getCacheName( ) );

// Check if we got something back from our Cache check
if( $response == null ) {
	// Ping the server starting from the latest protocol to the oldest to see if we can get a result
	$MinecraftAPI->ping( );

	// Chesk if there were any errors, if an error is found, print it out
	if( $MinecraftAPI->error != null ) {
		$error['error'] = $MinecraftAPI->error;
		die( json_encode( $error, JSON_PRETTY_PRINT ) );
	}

	// Checks if the server is offline
	if( !$MinecraftAPI->mc_status ) {
		$error['error'] = 'Failed to connect to ' . str_replace( '-', ':', $MinecraftAPI->getServer() );
		die( json_encode( $error, JSON_PRETTY_PRINT ) );
	}

	// Take the information from the MinecraftAPI and make up the output result set
	$output['status'] = $MinecraftAPI->mc_status;
	$output['players']['online'] = $MinecraftAPI->mc_playersOnline;
	$output['players']['max'] = $MinecraftAPI->mc_playersLimit;
	$output['motd'] = $MinecraftAPI->mc_motd;
	$output['version'] = $MinecraftAPI->mc_version;
	$output['latency'] = $MinecraftAPI->mc_latency;

	// Creates the JSON Output
	$response = json_encode( $output, JSON_PRETTY_PRINT );

	// Create a new cache file with the new server data
	$MinecraftAPI->getCache()->set( $MinecraftAPI->getCacheName( ), $response );
}

// Prints out the result set
die( $response );

?>
