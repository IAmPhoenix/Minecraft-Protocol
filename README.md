# Minecraft Protocol

Minecraft-Protocol is a PHP script based of the source code from http://minecraft-api.com/

## Setup Guide

Comming soon..


## Example

If you don't care for caching data and just want a fresh result every time, you could use the code in the example below, however it's recommended that you use [this](https://github.com/IAmPhoenix/Minecraft-Protocol/blob/master/httpdoc/index.php) instead.

```php
<?php 

  require_once ('../classes/MinecraftAPI.php');
  
  $MinecraftAPI = new MinecraftAPI(null, 'localhost');
  $MinecraftAPI->ping( );
  
  if( $MinecraftAPI->error != null ) {
  	$error['error'] = $MinecraftAPI->error;
  	die( json_encode( $error, JSON_PRETTY_PRINT ) );
  }
  
  if( !$MinecraftAPI->mc_status ) {
  	$error['error'] = 'Failed to connect to ' . str_replace( '-', ':', $MinecraftAPI->getServer() );
  	die( json_encode( $error, JSON_PRETTY_PRINT ) );
  }
  
  $output['status'] = $MinecraftAPI->mc_status;
  $output['players']['online'] = $MinecraftAPI->mc_playersOnline;
  $output['players']['max'] = $MinecraftAPI->mc_playersLimit;
  $output['motd'] = $MinecraftAPI->mc_motd;
  $output['version'] = $MinecraftAPI->mc_version;
  $output['latency'] = $MinecraftAPI->mc_latency;
  
  $response = json_encode( $output, JSON_PRETTY_PRINT );
?>
```

## License
> *This work is licensed under a Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Unported License.<br>
> To view a copy of this license, visit http://creativecommons.org/licenses/by-nc-sa/3.0/*
