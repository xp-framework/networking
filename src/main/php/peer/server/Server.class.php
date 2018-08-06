<?php namespace peer\server;

use io\IOException;
use peer\BSDSocket;
use peer\ServerSocket;
use peer\SocketException;
use peer\SocketTimeoutException;
use peer\server\protocol\SocketAcceptHandler;

/**
 * Basic TCP/IP Server
 *
 * ```php
 * use peer\server\Server;
 *   
 * $server= new Server('127.0.0.1', 6100);
 * $server->setProtocol(new MyProtocol());
 * $server->init();
 * $server->service();
 * $server->shutdown();
 * ```
 *
 * @ext   sockets
 * @see   xp://peer.ServerSocket
 * @test  xp://peer.unittest.server.ServerTest
 */
class Server {
  private $handles= [], $actions= [];

  public
    $protocol   = null,
    $socket     = null,
    $server     = null,
    $terminate  = false,
    $tcpnodelay = false;


  /**
   * Constructor
   *
   * @param   string addr
   * @param   int port
   */
  public function __construct($addr, $port) {
    $this->socket= new ServerSocket($addr, $port, '[' === $addr{0} ? AF_INET6 : AF_INET);
  }
  
  /**
   * Initialize the server
   *
   * @return void
   */
  public function init() {
    $this->socket->create();
    $this->socket->bind(true);
    $this->socket->listen(SOMAXCONN);
  }
  
  /**
   * Shutdown the server
   *
   * @return void
   */
  public function shutdown() {
    $this->server->terminate= true;
    $this->socket->close();
    $this->server->terminate= false;
  }
  
  /**
   * Sets this server's protocol
   *
   * @param  peer.server.ServerProtocol $protocol
   * @return peer.server.ServerProtocol
   */
  public function setProtocol($protocol) {
    $protocol->server= $this;
    $this->protocol= $protocol;
    return $protocol;
  }

  /** @param bool $tcpnodelay */
  public function setTcpnodelay($tcpnodelay) { $this->tcpnodelay= $tcpnodelay; }

  /** @return bool */
  public function getTcpnodelay() { return $this->tcpnodelay; }

  /**
   * Adds a socket to select for read input and call the given action
   * if data becomes available.
   *
   * @param  peer.Socket $socket
   * @param  function(peer.Socket): void $action
   * @return void
   */
  public function listen($socket, $action) {
    $handle= $socket->getHandle();
    $index= (int)$handle;
    $this->handles[$index]= $handle;
    $this->actions[$index]= [$socket, $action];
  }

  /**
   * Service
   *
   * @return void
   */
  public function service() {
    if (!$this->socket->isConnected()) return false;

    $timeout= $this->socket->getTimeout();
    $null= null;
    $connections= [];

    // If there is data on the server socket, this means we have a new client.
    // In case the accept() call fails, break out of the loop and terminate
    // the server - this really should not happen!
    $this->listen($this->socket, function($socket, &$connections) {
      if (!($m= $this->socket->accept())) {
        throw new SocketException('Call to accept() failed');
      }

      // Handle accepted socket
      if ($this->protocol instanceof SocketAcceptHandler) {
        if (!$this->protocol->handleAccept($m)) {
          $m->close();
          return;
        }
      }

      $this->tcpnodelay && $m->setOption(getprotobyname('tcp'), TCP_NODELAY, true);
      $this->protocol->handleConnect($m);

      $handle= $m->getHandle();
      $index= (int)$handle;
      $this->handles[$index]= $handle;
      $connections[$index]= [$m, time()];
    });

    $this->protocol->initialize();

    // Loop
    while (!$this->terminate) {
      \xp::gc();

      // Check if accepted connections have disconnected and/or timed out.
      foreach ($connections as $h => $s) {
        if (!$s[0]->isConnected()) {
          $this->protocol->handleDisconnect($s[0]);
          unset($connections[$h], $this->handles[$h]);
        } else if ($currentTime - $s[1] > $timeout) {
          $this->protocol->handleError($s[0], new SocketTimeoutException('Timed out', $timeout));
          $s[0]->close();
          unset($connections[$h], $this->handles[$h]);
        }
      }

      // Check to see if there are sockets with data on it. In case we can
      // find some, loop over the returned sockets. In case the select() call
      // fails, break out of the loop and terminate the server - this really 
      // should not happen!
      $read= $this->handles;
      $currentTime= time();
      do {
        $interrupted= false;
        if (false === socket_select($read, $null, $null, $timeout)) {
        
          // If socket_select has been interrupted by a signal, it will return FALSE,
          // but no actual error occurred - so check for "real" errors before throwing
          // an exception. If no error has occurred, skip over to the socket_select again.
          if (0 !== socket_last_error($this->socket->_sock)) {
            throw new SocketException('Call to select() failed');
          } else {
            $interrupted= true;
          }
        }
      // if socket_select was interrupted by signal, retry socket_select
      } while ($interrupted);

      foreach ($read as $h => $handle) {
        if (isset($this->actions[$h])) {
          $this->actions[$h][1]($this->actions[$h][0], $connections);
          continue;
        }
        
        // Otherwise, a client is sending data. Let the protocol decide what do
        // do with it. In case of an I/O error, close the client socket and remove 
        // the client from the list.
        $connections[$h][1]= $currentTime;
        $socket= $connections[$h][0];
        try {
          $this->protocol->handleData($socket);
        } catch (IOException $e) {
          $this->protocol->handleError($socket, $e);
          $socket->close();
          unset($connections[$h], $this->handles[$h]);
          continue;
        }

        // Check if we got an EOF from the client - in this case the connection
        // was gracefully closed.
        if (!$socket->isConnected() || $socket->eof()) {
          $this->protocol->handleDisconnect($socket);
          $socket->close();
          unset($connections[$h], $this->handles[$h]);
        }
      }
    }
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<@'.$this->socket->toString().'>';
  }
}
