<?php namespace peer;

use io\Channel;
use lang\Value;

/**
 * Socket class
 *
 * @test     xp://peer.unittest.sockets.SocketTest
 * @see      php://network
 */
class Socket implements Channel, Value {
  public
    $_eof     = false,
    $host     = '',
    $port     = 0;
    
  public
    $_sock    = null,
    $_prefix  = 'tcp://',
    $_timeout = 60;

  protected $context= null;
  
  /**
   * Constructor
   *
   * Note: When specifying a numerical IPv6 address (e.g. fe80::1)
   * as value for the parameter "host", you must enclose the IP in
   * square brackets.
   *
   * @param   string host hostname or IP address
   * @param   int port
   * @param   resource socket default NULL
   */
  public function __construct($host, $port, $socket= null) {
    $this->host= $host;
    $this->port= $port;
    $this->_sock= $socket;
    $this->context= stream_context_create();
  }

  /** @return peer.Sockets */
  public function kind() { return Sockets::$STREAM; }

  /** @return void */
  public function useNoDelay() {
    stream_context_set_option($this->context, 'socket', 'tcp_nodelay', true);
  }

  /**
   * Returns remote endpoint
   *
   * @return  peer.SocketEndpoint
   */
  public function remoteEndpoint() {
    return new SocketEndpoint($this->host, $this->port);
  }

  /**
   * Returns local endpoint
   *
   * @return  peer.SocketEndpoint
   * @throws  peer.SocketException
   */
  public function localEndpoint() {
    if (is_resource($this->_sock)) {
      if (false === ($addr= stream_socket_get_name($this->_sock, false))) {
        throw new SocketException('Cannot get socket name on '.$this->_sock);
      }
      return SocketEndpoint::valueOf($addr);
    }
    return null;    // Not connected
  }

  /**
   * Set option on socket context
   *
   * @param   string wrapper 'ssl', 'tcp', 'ftp'
   * @param   string option
   * @param   var value
   */
  protected function setSocketOption($wrapper, $option, $value) {
    stream_context_set_option($this->context, $wrapper, $option, $value);
  }
  
  /**
   * Retrieve option on socket context
   *
   * @param   string wrapper
   * @param   string option
   * @param   var
   */
  protected function getSocketOption($wrapper, $option) {
    $options= stream_context_get_options($this->context);
    return @$options[$wrapper][$option];
  }

  /**
   * Get last error. A very inaccurate way of going about error messages since
   * any PHP error/warning is returned - but since there's no function like
   * flasterror() we must rely on this
   *
   * @return  string error
   */  
  public function getLastError() {
    return isset(\xp::$errors[__FILE__]) ? trim(key(end(\xp::$errors[__FILE__]))) : 'unknown error';
  }
  
  /**
   * Returns whether a connection has been established
   *
   * @return  bool connected
   */
  public function isConnected() {
    return is_resource($this->_sock);
  }

  /**
   * Clone method. Ensure reconnect
   *
   */
  public function __clone() {
    if (!$this->isConnected()) return;
    $this->close();
    $this->connect();
  }
  
  /**
   * Connect
   *
   * @param   float timeout default 2.0
   * @see     php://fsockopen
   * @return  bool success
   * @throws  peer.ConnectException
   */
  public function connect($timeout= 2.0) {
    if ($this->isConnected()) return true;

    // Force IPv4 for localhost, see https://github.com/xp-framework/networking/issues/2
    $host= (string)$this->host;
    if (!$this->_sock= stream_socket_client(
      $this->_prefix.('localhost' === $host ? '127.0.0.1' : $host).':'.$this->port,
      $errno,
      $errstr,
      $timeout,
      STREAM_CLIENT_CONNECT,
      $this->context
    )) {
      $e= new ConnectException(sprintf(
        'Failed connecting to %s:%s within %s seconds [%d: %s]',
        $this->host,
        $this->port,
        $timeout,
        $errno,
        trim($errstr)
      ));
      \xp::gc(__FILE__);
      throw $e;
    }
    
    stream_set_timeout($this->_sock, $this->_timeout);
    return true;
  }

  /**
   * Close socket
   *
   * @return  bool success
   */
  public function close() {
    if (!is_resource($this->_sock)) return false;

    $res= fclose($this->_sock);
    $this->_sock= null;
    $this->_eof= false;
    return $res;
  }

  /**
   * Set timeout
   *
   * @param   var _timeout
   */
  public function setTimeout($timeout) {
    $this->_timeout= $timeout;
    
    // Apply changes to already opened connection
    if (is_resource($this->_sock)) {
      stream_set_timeout($this->_sock, $this->_timeout);
    }
  }

  /**
   * Get timeout
   *
   * @return  var
   */
  public function getTimeout() {
    return $this->_timeout;
  }

  /**
   * Set socket blocking
   *
   * @param   bool blocking
   * @return  bool success TRUE to indicate the call succeeded
   * @throws  peer.SocketException
   * @see     php://socket_set_blocking
   */
  public function setBlocking($blockMode) {
    if (false === stream_set_blocking($this->_sock, $blockMode)) {
      $e= new SocketException('Set blocking call failed: '.$this->getLastError());
      \xp::gc(__FILE__);
      throw $e;
    }
    
    return true;
  }
  
  /**
   * Returns whether there is data that can be read
   *
   * @param   float timeout default NULL Timeout value in seconds (e.g. 0.5)
   * @return  bool there is data that can be read
   * @throws  peer.SocketException in case of failure
   */
  public function canRead($timeout= null) {
    if (!$this->_sock) {
      throw new SocketException('Socket not connected');
    }

    $r= [$this->_sock]; $w= null; $e= null;
    $n= Sockets::$STREAM->select0($r, $w, $e, $timeout);
    return $n > 0;
  }

  /**
   * Reading helper function
   *
   * @param   int maxLen
   * @param   int type ignored
   * @param   bool chop
   * @return  string data
   */
  protected function _read($maxLen, $type, $chop= false) {
    $res= fgets($this->_sock, $maxLen);
    if (false === $res || null === $res) {

      // fgets returns FALSE on eof, this is particularily dumb when 
      // looping, so check for eof() and make it "no error"
      if (feof($this->_sock)) {
        $this->_eof= true;
        return null;
      }
      
      $m= stream_get_meta_data($this->_sock);
      if ($m['timed_out']) {
        $e= new SocketTimeoutException('Read of '.$maxLen.' bytes failed', $this->_timeout);
        \xp::gc(__FILE__);
        throw $e;
      } else {
        $e= new SocketException('Read of '.$maxLen.' bytes failed: '.$this->getLastError());
        \xp::gc(__FILE__);
        throw $e;
      }
    } else {
      return $chop ? chop($res) : $res;
    }
  }

  
  /**
   * Read data from a socket
   *
   * @param   int maxLen maximum bytes to read
   * @return  string data
   * @throws  peer.SocketException
   */
  public function read($maxLen= 4096) {
    return $this->_read($maxLen, -1, false);
  }

  /**
   * Read line from a socket
   *
   * @param   int maxLen maximum bytes to read
   * @return  string data
   * @throws  peer.SocketException
   */
  public function readLine($maxLen= 4096) {
    return $this->_read($maxLen, -1, true);
  }

  /**
   * Read data from a socket (binary-safe)
   *
   * @param   int maxLen maximum bytes to read
   * @return  string data
   * @throws  peer.SocketException
   */
  public function readBinary($maxLen= 4096) {
    $res= fread($this->_sock, $maxLen);
    if (false === $res || null === $res) {
      $e= new SocketException('Read of '.$maxLen.' bytes failed: '.$this->getLastError());
      \xp::gc(__FILE__);
      throw $e;
    } else if ('' === $res) {
      $m= stream_get_meta_data($this->_sock);
      if ($m['timed_out']) {
        $e= new SocketTimeoutException('Read of '.$maxLen.' bytes failed: '.$this->getLastError(), $this->_timeout);
        \xp::gc(__FILE__);
        throw $e;
      }
      $this->_eof= true;
    }
    
    return $res;
  }
  
  /**
   * Checks if EOF was reached
   *
   * @return  bool
   */
  public function eof() {
    return $this->_eof;
  }
  
  /**
   * Write a string to the socket
   *
   * @param   string str
   * @return  int bytes written
   * @throws  peer.SocketException in case of an error
   */
  public function write($str) {
    if (false === ($bytesWritten= fputs($this->_sock, $str, $len= strlen($str)))) {
      $e= new SocketException('Write of '.$len.' bytes to socket failed: '.$this->getLastError());
      \xp::gc(__FILE__);
      throw $e;
    }
    
    return $bytesWritten;
  }

  /**
   * Retrieve socket handle
   *
   * @return  resource
   */
  public function getHandle() {
    return $this->_sock;
  }

  /**
   * Retrieve input stream
   *
   * @deprecated Use in() instead
   * @return  io.streams.InputStream
   */
  public function getInputStream() {
    return $this->in();
  }

  /**
   * Retrieve output stream
   *
   * @deprecated Use out() instead
   * @return  io.streams.OutputStream
   */
  public function getOutputStream() {
    return $this->out();
  }

  /** @return io.streams.InputStream */
  public function in() { return new SocketInputStream($this); }

  /** @return io.streams.OutputStream */
  public function out() { return new SocketOutputStream($this); }
  
  /**
   * Destructor
   *
   */
  public function __destruct() {
    $this->close();
  }
  
  /** @return string */
  public function toString() {
    return sprintf(
      '%s(%s -> %s%s:%d)',
      nameof($this),
      null === $this->_sock ? '(closed)' : (string)$this->_sock,
      $this->_prefix,
      $this->host,
      $this->port
    );
  }

  /** @return string */
  public function hashCode() {
    return md5($this->_prefix.'>'.$this->host.':'.$this->port);
  }

  /**
   * Compares this socket to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self
      ? strcmp(
        $this->_prefix.'>'.$this->host.':'.$this->port,
        $value->_prefix.'>'.$value->host.':'.$value->port
      )
      : 1
    ;
  }
}
