<?php

/**
 * NaadConnector class connects to the NAAD socket and logs its output.
 */
class NaadConnector {

    protected static $MAX_MESSAGE_SIZE = 5000000;
    protected string $name;
    protected string $address;
    protected int $port;

    public function __construct( string $name, string $url, int $port = 8080 ) {
        $this->name = $name;
        $this->address = $url;
        $this->port = $port;
        $this->connect();
    }

    public function connect() {
        $this->logger( 'starting' );

        /* Create a TCP/IP socket. */
        $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
        if ( $socket === false ) {
            $this->logger( 'socket_create() failed: reason: ' . socket_strerror( socket_last_error() ) );
        } else {
            $this->logger( 'OK.' );
        }

        $address = $this->address;
        $port = $this->port;

        $this->logger( "Attempting to connect to '$address' on port '$port'..." );
        $result = socket_connect( $socket, $address, $port );
        if ( $result === false ) {
            $this->logger( "socket_connect() failed.\nReason: ($result) " . socket_strerror( socket_last_error( $socket ) ) );
        } else {
            $this->logger( 'OK.' );
        }

        $this->logger( 'Reading response:' );
        while ( $out = socket_read( $socket, self::$MAX_MESSAGE_SIZE ) ) {
            $this->handle_event( $out );
        }

        $this->logger( 'Closing socket...' );
        socket_close( $socket );
        $this->logger( 'OK.' );
    }

    public function handle_event( string $event ) {
        $this->logger( $event );


    }

    private function logger( $msg ) {
        $s = sprintf( '[%s %s] ', $this->name, date( 'm/d/Y h:i:s a', time() ) );
        error_log( $s . print_r( $msg, true ) );
    }
}
