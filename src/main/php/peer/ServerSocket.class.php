<?php namespace peer;

use lang\IllegalAccessException;

/**
 * Socket server implementation
 *
 * @see   xp://peer.Socket
 * @see   php://stream_socket_server
 * @test  xp://peer.unittest.server.ServerTest
 */
class ServerSocket extends Socket {

  /**
   * Connect. Overwritten method from BSDSocket that will always throw
   * an exception because connect() doesn't make sense here!
   *
   * @param   float timeout default 2.0
   * @return  bool success
   * @throws  lang.IllegalAccessException
   */
  public function connect($timeout= 2.0) {
    throw new IllegalAccessException('Connect cannot be used on a ServerSocket');
  }
  
  /**
   * Create
   *
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function create() {
    return true;
  }
  
  /**
   * Bind
   *
   * @see     http://php.net/manual/en/context.socket.php
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function bind($reuse= false) {
    stream_context_set_option($this->context, 'socket', 'so_reuseport', $reuse);
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
   * @param   int backlog default 10
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function listen($backlog= 10) {
    stream_context_set_option($this->context, 'socket', 'backlog', $backlog);

    // Bind and listen
    if (!is_resource($this->_sock= stream_socket_server(
      'tcp://'.$this->host.':'.$this->port,
      $errno,
      $errstr,
      STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
      $this->context
    ))) {
      $this->_sock= null;
      throw new SocketException($errno.': '.$errstr);
    }

    // Returns "127.0.0.1:49910" (IPv4) or "fe80::b555:35a7:5026:2ebe:49919" (IPv6)
    $name= stream_socket_get_name($this->_sock, false);
    $p= strrpos($name, ':');
    $this->port= (int)substr($name, $p + 1);
    $this->host= substr($name, 0, $p);
    return true;
  }
  
  /**
   * Accept connection
   *
   * @param  bool $block Whether to use blocking
   * @return peer.Socket or NULL if accept() fails, e.g. due to timeout
   * @throws peer.SocketException in case of an error
   */
  public function accept($block= true) {
    $handle= stream_socket_accept($this->_sock, $block ? -1 : 0, $peer);
    if (false === $handle) {
      \xp::gc(__FILE__);
      return null;
    }

    $p= strrpos($peer, ':');
    return new Socket(substr($peer, 0, $p), (int)substr($peer, $p + 1), $handle);
  }
}
