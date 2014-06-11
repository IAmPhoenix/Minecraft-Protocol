<?php

class MinecraftAPI
{

    // Server variables
    private $server;
    private $port = 25565;

    // cacheName
    private $cacheName;

    // Timing variables
    private $start;
    private $latency;

    // Ping Variables
    private $socket;

    // Minecraft server stats
    public $mc_status = false;
    public $mc_playersOnline = 0;
    public $mc_playersLimit = 0;
    public $mc_latency = 0;
    public $mc_players = null;
    public $mc_motd = null;
    public $mc_version = null;
    public $mc_logo = null;
    public $protocl_json = null;

    // Cache handler
    public $cache;

    // Error handler
    public $error = null;

    /**
    * Minecraft Ping Protocol
    * Returns a JSON string, false on failure.
    *
    * This class is based off the Java Example ping class for 1.7
    * @link http://wiki.vg/Server_List_Ping
    *
    * @author Alexis Tan
    * @version 1.0
    */
    public function __construct (Cache $cache, $server = 'localhost:25565', $cacheName = 'cache')
    {
        $this->cache = $cache;

        $this->server = $server;
        $this->cacheName = $cacheName;

        if ( strlen( $this->server ) == 0 ) {
            throw new Exception( 'Invalid server address.' );
        }

        if( strpos( $server, ':' ) !== false ) {
            $parts = explode( ':', $server );
            $this->server = $parts[0];
            $this->port = (int) $parts[1];
        }
        
        $this->cache->setConfig( 'path', $this->cache->getConfig( 'path' ) . $this->getServer( ) . '/' );
    }

    /*
    * Get the cache object
    */
    public function getCache( )
    {
        return $this->cache;
    }

    /**
    * Get the cache name
    */
    public function getCacheName( )
    {
        return $this->cacheName;
    }

    /**
    * Get the server name
    */
    public function getServer( ) 
    {
        return strtolower( $this->server . '-' . $this->port );
    }

    /**
    * Pings a Minecraft server
    * Works with 1.6 & 1.7 versions of Minecraft.
    */
    public function ping( ) 
    {
        // Start the latencty listener
        $this->startLatencyListener();

        // Create socket and connect
        $socket = socket_create( AF_INET, SOCK_STREAM, getprotobyname( 'tcp' ) );
        socket_connect( $socket, $this->server, $this->port );

        // Stop the latencty listener
        $this->endLatencyListener();

        // Calculate size of packet
        $this->write_varint($socket, 6 + strlen($this->server));
        
        // Handshake packet
        socket_send($socket, chr(0), 1, null);
        // Protocol version
        socket_send($socket, chr(4), 1, null);
        // Server address
        $this->write_varint($socket, strlen($this->server));
        socket_send($socket, $this->server, strlen($this->server), null);
        // Server port (big endian)
        socket_send($socket, chr(($this->port >> 8) & 0xff), 1, null);
        socket_send($socket, chr($this->port & 0xff), 1, null);
        // Next state is status state
        $this->write_varint($socket, 1);

        // Size of packet
        $this->write_varint($socket, 1);
        
        // Status request packet
        socket_send($socket, chr(0), 1, null);

        // Read packet size (unsused)
        $this->read_varint($socket);
        // Read opcode, should be 0 for status resposne
        socket_recv($socket, $buf, 1, null);

        // Read JSON string length
        $len = $this->read_varint($socket);

        // Local Debugging
        //sleep(1);

        // Read JSON string
        socket_recv($socket, $buf, $len, MSG_WAITALL);

        // Cast the json to an array
        $data = json_decode( $buf, true );

        // Old ping protocol
        if( $data == null ) {
            // Connects to the server.
            $handle = fsockopen( $this->server, $this->port, $errno, $errstr, 0.7 );

            // Check if we connected correctly
            if( $handle ) {
                // Set timeout timer for the request
                stream_set_timeout( $handle, 1 );

                // Send ping packet
                fwrite( $handle, "\xFE\x01" );

                // Get the feedback
                $d = fread( $handle, 1024 );

                // Check if our response is valid
                if ( $d != false AND substr( $d, 0, 1 ) == "\xFF" ) { 
                    // Format the data and close our connection
                    $d = substr($d, 3); 
                    $d = mb_convert_encoding( $d, 'auto', 'UCS-2' ); 
                    $d = explode( "\x00", $d );       
                    fclose( $handle );

                    // Save all the information
                    $this->mc_status = true;
                    $this->mc_version = $d[2];
                    $this->mc_playersOnline = $d[4];
                    $this->mc_playersLimit = $d[5];
                    $this->mc_motd = $d[3];
                    return true;
                }
            }
            return false;
        }

        // Get the load time
        $this->mc_latency = $this->getLoadTime();

        // Safe check for the servers MoTD
        if( is_array( $data['description'] ) ) {
            $data['description'] = $data['description']['text'];
        }
        // Sets the MoTD
        $data['description'] = $this->stripMotdColorCodes( $data['description'] );

        // Saves all the infomation
        $this->mc_status = true;
        $this->mc_playersLimit = $data['players']['max'];
        $this->mc_playersOnline = $data['players']['online'];
        $this->mc_motd = $data['description'];
        $this->mc_version = $data['version']['name'];
        $this->mc_logo = ( isset( $data['favicon'] ) ) ? str_replace( 'data:image/png;base64,', '', $data['favicon'] ) : null;

        return true;
    }

    /**
    * PHP Varint read method based off the 1.7 Example Java class from
    * http://wiki.vg/Server_List_Ping
    *
    * @link https://gist.github.com/zh32/7190955#file-serverlistping17-java-L41
    */
    private function read_varint( $s ) 
    {
        $i = 0; $j = 0;
        while(true) {
            socket_recv($s, $buf, 1, null);
            $k = ord($buf[0]);

            $i |= ($k & 0x7F) << $j++ * 7;
            if ($j > 5) return -1;

            if (($k & 0x80) != 128) break;
        }

        return $i;
    }

    /**
    * PHP Varint write method based off the 1.7 Example Java class from
    * http://wiki.vg/Server_List_Ping
    *
    * @link https://gist.github.com/zh32/7190955#file-serverlistping17-java-L53
    */
    private function write_varint( $s, $val ) 
    {
        while( true ) {
            if( ( $val & 0xFFFFFF80 ) == 0) {
                socket_send( $s, chr( $val ), 1, null );
                return;
            }

            socket_send( $s, chr( $val & 0xF | 0x80 ), 1, null );
            $val >>= 7;
        }
    }

    /**
    * Removes all the colour codes from the Message of the Day.
    */
    private function stripMotdColorCodes( $motd )
    {
        $split = explode( '§', trim( $motd ) ); $motd = '';
        foreach ($split as $part) {
            if( strlen( $part ) == 0) continue;
            $motd .= substr( $part, 1, strlen( ( $part ) ) );
        }
        return $motd;
    }

    private function startLatencyListener( ) 
    {
        $this->start = microtime( true );
    }

    private function endLatencyListener( ) 
    {
        $this->latency = number_format( ( microtime( true ) - $this->start ), 3 );
    }

    private function getLoadTime( ) 
    {
        return $this->latency;
    }
}

?>
