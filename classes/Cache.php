<?php 

class CacheException extends Exception
{
	// Used for Cache Exceptions
}


class Cache
{

	// Cache config default values
	private $config = array(
			'path' => '/path/to/folder/',
			'cacheTime' => 3600,
			'useExtension' => false
		);

	/**
	* Sime file cache class
    *
    * @author Alexis Tan
    * @version 1.0
	*/
	public function __construct()
	{
		// This does nothing right now..
	}

	/**
	* Sets a config value
	*/
	public function setConfig( $key, $value = null )
	{
		if( $key != null && array_key_exists( $key, $this->config ) ) {
			$this->config[$key] = $value;
		}
	}

	/**
	* Get the entire config array, or just one value
	*/
	public function getConfig( $key = null )
	{
		if( $key != null && array_key_exists( $key, $this->config ) ) {
			return $this->config[$key];
		}
		return $this->config;
	}

	/**
	* Initialize the cache folder, timer and extensions
	*/
	public function init( )
	{
		if( $this->config['path'] == null || !is_writable( $this->config['path']) ) {
			if(!mkdir( $this->config['path'], 0777) ) {
				throw new CacheException("Failed to create the server folder.");
			}
		}

		if( !is_int( $this->config['cacheTime']) ) {
			$this->config['cacheTime'] = 3600;
			throw new CacheException("The cache timer has to be an Integer!");
		}

		if( !is_bool( $this->config['useExtension'] ) ) {
			$this->config['useExtension'] = false;
			throw new CacheException("The extension propertie has to be a Boolean!");
		}
	}

	/**
	* Gets the cache content of the key given to the method
	* Will return null if nothing was found or the cache is older than the cache timer
	*/
	public function get( $key )
	{
		$hash = $this->config['path'] . $key . (($this->config['useExtension']) ? '.cache' : '');
		if( file_exists( $hash ) &&  (time() - filemtime( $hash ) ) <= $this->config['cacheTime'] ) {
			return file_get_contents( $hash );
		}
		return null;
	}

	/**
	* Saves the information a file
	*/
	public function set( $key, $value )
	{
		$hash =  $this->config['path'] . $key . (($this->config['useExtension']) ? '.cache' : '');
		file_put_contents( $hash, $value );
	}
}

?>