<?php namespace peer\server;

use lang\{IllegalStateException, Throwable};
use peer\server\protocol\SocketAcceptHandler;
use peer\{ServerSocket, SocketTimeoutException};

/**
 * Asynchronous TCP/IP Server
 *
 * ```php
 * use peer\server\AsyncServer;
 *   
 * $server= new AsyncServer();
 * $server->listen(new ServerSocket('127.0.0.1', 6100), new MyProtocol());
 * $server->service();
 * $server->shutdown();
 * ```
 *
 * @see   xp://peer.ServerSocket
 * @test  xp://peer.unittest.server.ServerTest
 */
class AsyncServer extends Server {
  private $select= [], $handle= [], $tasks= [];

  /**
   * Adds server socket to listen on, associating protocol handler with it
   *
   * @param  peer.ServerSocket|peer.BSDServerSocket $socket
   * @param  peer.server.ServerProtocol $protocol
   * @return self
   */
  public function listen($socket, $protocol) {
    $protocol->server= $this;

    $socket->create();
    $socket->bind(true);
    $socket->listen();

    $this->select[]= $socket;
    $this->handle[]= [function($socket) use($protocol) {
      $connection= $socket->accept();
      if ($protocol instanceof SocketAcceptHandler && !$protocol->handleAccept($connection)) {
        $connection->close();
        return;
      }

      $this->tcpnodelay && $connection->useNoDelay();
      $protocol->handleConnect($connection);

      $this->select[]= $connection;
      $this->handle[]= [
        [$protocol, 'handleData'],
        [$protocol, 'handleDisconnect'],
        [$protocol, 'handleError'],
        [$protocol, 'initialize']
      ];
    }];

    return $this;
  }

  /**
   * Shutdown the server
   *
   * @return void
   */
  public function shutdown() {
    $this->terminate= true;
  }

  /**
   * Adds socket to select, associating a function to call for data
   *
   * @param  peer.Socket|peer.BSDSocket $socket
   * @param  function(peer.Socket|peer.BSDSocket): void $function
   * @return peer.Socket|peer.BSDSocket
   */
  public function select($socket, $function) {
    $this->select[]= $socket;
    $this->handle[]= [$function];
    return $socket;
  }

  /**
   * Schedule a given task to execute every given seconds
   *
   * @param  int $seconds
   * @param  function(): void
   */
  public function schedule($seconds, $function) {
    $this->tasks[-1 - sizeof($this->tasks)]= [$seconds, $function];
  } 

  /**
   * Runs service until shutdown() is called.
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function service() {
    if (empty($this->select)) {
      throw new IllegalStateException('No sockets to select on');
    }

    // Initialize handles if necessary
    foreach ($this->handle as $handle) {
      if ($f= $handle[3] ?? null) $f();
    }

    // Set up scheduled tasks
    $time= time();
    $next= $cont= [];
    foreach ($this->tasks as $i => $task) {
      $next[$i]= $time + $task[0];
    }

    $null= null;
    $sockets= $this->select[0]->kind();
    do {

      // Build array of sockets that we want to check for data. If one of them
      // has disconnected in the meantime, notify the listeners (socket will be
      // already invalid at that time) and remove it from the clients list.
      $read= [];
      foreach ($this->select as $i => $socket) {
        if (!$socket->isConnected() || $socket->eof()) {
          if ($f= $this->handle[$i][1] ?? null) $f($socket);
          unset($this->select[$i], $this->handle[$i], $next[$i]);
          continue;
        }

        // Do not re-enter handler as long as we have a continuation
        if (isset($cont[$i])) continue;

        // Handle timeouts manually instead of leaving this up to the sockets
        // themselves - the latter has proven not to be 100% reliable.
        if (isset($next[$i]) && $next[$i] <= $time) {
          if ($f= $this->handle[$i][2] ?? null) {
            $f($socket, new SocketTimeoutException('Timed out', $socket->getTimeout()));
            $socket->close();
            unset($this->select[$i], $this->handle[$i], $next[$i]);
            continue;
          }
          $next[$i]= $time + $socket->getTimeout();
        }

        $read[$i]= $socket;
      }
      // echo '* SELECT (', \util\Objects::stringOf($next), ' -> ', $next ? max(0, min($next) - $time) : null, ")\n";
      $sockets->select($read, $null, $null, $next ? max(0, min($next) - $time) : null);

      // Run scheduled tasks, recording their next run immediately thereafter
      $time= time();
      foreach ($this->tasks as $i => $task) {
        if ($next[$i] <= $time) {
          $next[$i]= $time + $task[0];
          $task[1]();
        }
      }

      // There is data on the server socket (meaning a client connection is
      // waiting to be socket), or on any of the other sockets, so we'll call
      // into their respective data handler
      foreach ($read as $i => $socket) {
        try {
          $r= $this->handle[$i][0]($socket);
          if ($r instanceof \Generator) {
            $cont[$i]= true;
            $task= -1 - sizeof($this->tasks);
            $next[$task]= $time;

            $this->tasks[$task]= [0, function() use($r, $i, $task, &$cont, &$next) {
              if ($r->valid()) {
                $r->next();
              } else {
                unset($this->tasks[$task], $next[$task], $cont[$i]);
              }
            }];
          }

          $next[$i]= $time + $socket->getTimeout();
        } catch (Throwable $t) {
          if ($f= $this->handle[$i][2] ?? null) $f($socket, $t);
        }
      }

      $time= time();
    } while (!$this->terminate);

    // Close all accepted sockets first, then the listening sockets
    for ($i= sizeof($this->select) - 1; $i >= 0; $i--) {
      $this->select[$i]->close();
      if ($f= $this->handle[$i][1] ?? null) $f($this->select[$i]);
    }
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this);
  }
}