<?php namespace peer;

/**
 * BSDSocket server implementation
 *
 * @see      peer.BSDSocket
 * @ext      sockets
 */
class BSDServerSocket extends BSDSocket {
  public
    $domain   = 0,
    $type     = 0,
    $protocol = 0;

  /**
   * Constructor
   *
   * @param   string host
   * @param   int port
   * @param   int domain default AF_INET (one of AF_INET or AF_UNIX)
   * @param   int type default SOCK_STREAM (one of SOCK_STREAM | SOCK_DGRAM | SOCK_RAW | SOCK_SEQPACKET | SOCK_RDM)
   * @param   int protocol default SOL_TCP (one of SOL_TCP or SOL_UDP)
   */
  public function __construct($host, $port, $domain= AF_INET, $type= SOCK_STREAM, $protocol= SOL_TCP) {
    $this->domain= $domain;
    $this->type= $type;
    $this->protocol= $protocol;
    parent::__construct($host, $port);
  }
  
  /**
   * Connect. Overwritten method from BSDSocket that will always throw
   * an exception because connect() doesn't make sense here!
   *
   * @param   float timeout default 2.0
   * @return  bool success
   * @throws  lang.IllegalAccessException
   */
  public function connect($timeout= 2.0) {
    throw new \lang\IllegalAccessException('Connect cannot be used on a ServerSocket');
  }
  
  /**
   * Create
   *
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function create() {
    if (!is_resource($this->_sock= socket_create($this->domain, $this->type, $this->protocol))) {
      throw new SocketException(sprintf(
        'Creating socket failed: %s',
        socket_strerror(socket_last_error())
      ));
    }
    
    return true;
  }

  /**
   * Bind
   *
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function bind($reuse= false) {
    if (
      (false === socket_setopt($this->_sock, SOL_SOCKET, SO_REUSEADDR, $reuse)) ||
      (false === socket_bind($this->_sock, $this->host, $this->port))
    ) {
      throw new SocketException(sprintf(
        'Binding socket to '.$this->host.':'.$this->port.' failed: %s',
        socket_strerror(socket_last_error())
      ));
    }
    
    // Update socket host and port
    socket_getsockname($this->_sock, $this->host, $this->port);
    return true;
  }      

  /**
   * Listen on this socket
   *
   * <quote>
   * A maximum of backlog incoming connections will be queued for processing. 
   * If a connection request arrives with the queue full the client may receive an 
   * error with an indication of ECONNREFUSED, or, if the underlying protocol 
   * supports retransmission, the request may be ignored so that retries may 
   * succeed. 
   * </quote>
   *
   * @param   int backlog default SOMAXCONN
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function listen($backlog= SOMAXCONN) {
    if (false === socket_listen($this->_sock, $backlog)) {
      throw new SocketException(sprintf(
        'Listening on socket failed: %s',
        socket_strerror(socket_last_error())
      ));
    }
    
    return true;
  }

  /**
   * Accept connection
   *
   * <quote>
   * This function will accept incoming connections on that socket. Once a 
   * successful connection is made, a new socket object is returned, which 
   * may be used for communication. If there are multiple connections queued 
   * on the socket, the first will be used. If there are no pending connections, 
   * socket_accept() will block until a connection becomes present.
   * </quote> 
   *
   * Note: If this socket has been made non-blocking, NULL will be returned.
   *
   * @param  bool $block Whether to use blocking
   * @return peer.BSDSocket object or NULL
   * @throws peer.SocketException in case of an error
   */
  public function accept($block= true) {
    if ($block) {
      $socket= socket_accept($this->_sock);
    } else {
      socket_set_nonblock($this->_sock);
      $socket= socket_accept($this->_sock);
      socket_set_block($this->_sock);
    }

    if (is_resource($socket)) {
      socket_getpeername($socket, $host, $port);
      return new BSDSocket($host, $port, $socket);
    } else if ($e= socket_last_error($this->_sock)) {
      throw new SocketException('Accept failed: #'.$e.': '.socket_strerror($e));
    } else {
      return null;
    }
  }
}