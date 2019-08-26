<?php namespace peer\server;

use peer\BSDServerSocket;
use peer\ServerSocket;

/**
 * Basic TCP/IP Server
 *
 * ```php
 * use peer\server\Server;
 *   
 * $server= new Server();
 * $server->listen(new ServerSocket('127.0.0.1', 6100), new MyProtocol());
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
  public
    $protocol   = null,
    $socket     = null,
    $server     = null,
    $terminate  = false,
    $tcpnodelay = false;

  private $select= [];

  /**
   * Constructor
   *
   * @deprecated Use listen() instead
   * @param  string addr
   * @param  int port
   */
  public function __construct($addr= null, $port= null) {
    if (null === $addr) return;

    // Deprecated two-arg constructor used, use backwards compatible version
    if (extension_loaded('sockets')) {
      $this->socket= new BSDServerSocket($addr, $port, '[' === $addr[0] ? AF_INET6 : AF_INET);
    } else {
      $this->socket= new ServerSocket($addr, $port);
    }
  }

  /**
   * Sets socket to listen on and protocol to implement
   *
   * @param  peer.ServerSocket|peer.BSDServerSocket $socket
   * @param  peer.server.ServerProtocol $protocol
   * @return self
   */
  public function listen($socket, ServerProtocol $protocol) {
    $protocol->server= $this;
    $this->socket= $socket;
    $this->protocol= $protocol;
    return $this;
  }

  public function select($socket, $action) {
    $this->select[(int)$socket->getHandle()]= [$socket, $action];
    return $this;
  }

  /**
   * Initialize the server
   *
   */
  public function init() {
    $this->socket->create();
    $this->socket->bind(true);
    $this->socket->listen(SOMAXCONN);
  }
  
  /**
   * Shutdown the server
   *
   */
  public function shutdown() {
    $this->server->terminate= true;
    $this->socket->close();
    $this->server->terminate= false;
  }
  
  /**
   * Sets this server's protocol
   *
   * @deprecated Use listen() instead
   * @param   peer.server.ServerProtocol protocol
   * @return  peer.server.ServerProtocol protocol
   */
  public function setProtocol($protocol) {
    $protocol->server= $this;
    $this->protocol= $protocol;
    return $protocol;
  }

  /**
   * Set TCP_NODELAY
   *
   * @param   bool tcpnodelay
   */
  public function setTcpnodelay($tcpnodelay) {
    $this->tcpnodelay= $tcpnodelay;
  }

  /**
   * Get TCP_NODELAY
   *
   * @return  bool
   */
  public function getTcpnodelay() {
    return $this->tcpnodelay;
  }
  
  /**
   * Service
   *
   */
  public function service() {
    if (!$this->socket->isConnected()) return false;

    $handles= $lastAction= [];
    foreach ($this->select as $index => $select) {
      $handles[$index]= $select[0];
      $lastAction[$index]= time();
    }

    $null= null;
    $accepting= $this->socket->getHandle();
    $sockets= $this->socket->kind();
    $this->protocol->initialize();

    // Loop
    $timeout= null;
    while (!$this->terminate) {
      \xp::gc();

      // Build array of sockets that we want to check for data. If one of them
      // has disconnected in the meantime, notify the listeners (socket will be
      // already invalid at that time) and remove it from the clients list.
      do {
        $read= [$this->socket->getHandle()];
        $currentTime= time();
        foreach ($handles as $h => $handle) {
          if (!$handle->isConnected()) {
            $this->protocol->handleDisconnect($handle);
            unset($handles[$h]);
            unset($lastAction[$h]);
          } else if (isset($this->select[$h])) {
            $read[]= $handle->getHandle();
          } else if ($currentTime - $lastAction[$h] > $handle->getTimeout()) {
            $this->protocol->handleError($handle, new \peer\SocketTimeoutException('Timed out', $handle->getTimeout()));
            $handle->close();
            unset($handles[$h]);
            unset($lastAction[$h]);
          } else {
            $read[]= $handle->getHandle();
          }
        }

        // Check to see if there are sockets with data on it.
        $n= $sockets->select0($read, $null, $null, $timeout);
      } while (0 === $n);

      foreach ($read as $i => $handle) {

        // If there is data on the server socket, this means we have a new client.
        // In case the accept() call fails, break out of the loop and terminate
        // the server - this really should not happen!
        if ($handle === $accepting) {
          if (!($m= $this->socket->accept())) {
            throw new \peer\SocketException('Call to accept() failed');
          }

          // Handle accepted socket
          if ($this->protocol instanceof \peer\server\protocol\SocketAcceptHandler) {
            if (!$this->protocol->handleAccept($m)) {
              $m->close();
              continue;
            }
          }
          
          $this->tcpnodelay && $m->useNoDelay();
          $this->protocol->handleConnect($m);
          $index= (int)$m->getHandle();
          $handles[$index]= $m;
          $lastAction[$index]= $currentTime;
          $timeout= $m->getTimeout();
          continue;
        }

        $index= (int)$handle;
        $lastAction[$index]= $currentTime;

        if (isset($this->select[$index])) {
          $this->select[$index][1]($this, $handles[$index]);
          continue;
        }

        // Otherwise, a client is sending data. Let the protocol decide what do
        // do with it. In case of an I/O error, close the client socket and remove 
        // the client from the list.
        try {
          $this->protocol->handleData($handles[$index]);
        } catch (\io\IOException $e) {
          $this->protocol->handleError($handles[$index], $e);
          $handles[$index]->close();
          unset($handles[$index]);
          unset($lastAction[$index]);
          continue;
        }
        
        // Check if we got an EOF from the client - in this case the connection
        // was gracefully closed.
        if (!$handles[$index]->isConnected() || $handles[$index]->eof()) {
          $this->protocol->handleDisconnect($handles[$index]);
          $handles[$index]->close();
          unset($handles[$index]);
          unset($lastAction[$index]);
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
