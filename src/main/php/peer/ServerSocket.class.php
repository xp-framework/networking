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

  static function __static() {
    if (defined('SOMAXCONN')) return;

    // Discover SOMAXCONN depending on platform, using 128 as fallback
    // See https://stackoverflow.com/q/1198564
    if (0 === strncasecmp(PHP_OS, 'Win', 3)) {
      $value= 0x7fffffff;
    } else if (file_exists('/proc/sys/net/core/somaxconn')) {
      $value= (int)file_get_contents('/proc/sys/net/core/somaxconn');
    } else if (file_exists('/etc/sysctl.conf')) {
      $value= 128;
      foreach (file('/etc/sysctl.conf') as $line) {
        if (0 === strncmp($line, 'kern.ipc.somaxconn=', 19)) {
          $value= (int)substr($line, 19);
          break;
        }
      }
    } else {
      $value= 128;
    }
    define('SOMAXCONN', $value);
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
   * @param   int backlog default SOMAXCONN
   * @return  bool success
   * @throws  peer.SocketException in case of an error
   */
  public function listen($backlog= SOMAXCONN) {
    stream_context_set_option($this->context, 'socket', 'backlog', $backlog);

    // Force IPv4 for localhost, see https://github.com/xp-framework/networking/issues/0
    $host= (string)$this->host;
    if (!is_resource($this->_sock= stream_socket_server(
      $this->_prefix.('localhost' === $host ? '127.0.0.1' : $host).':'.$this->port,
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

      // We have no way of getting the errno here, otherwise we could check for EINTR,
      // ETIMEDOUT, etcetera. Checking for error *messages* is too risky.
      \xp::gc(__FILE__);
      return null;
    }

    $p= strrpos($peer, ':');
    return new Socket(substr($peer, 0, $p), (int)substr($peer, $p + 1), $handle);
  }
}