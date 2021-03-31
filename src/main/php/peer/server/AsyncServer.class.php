<?php namespace peer\server;

use lang\{IllegalStateException, Throwable};
use peer\server\protocol\SocketAcceptHandler;
use peer\{ServerSocket, SocketException, SocketTimeoutException};

/**
 * Asynchronous TCP/IP Server
 *
 * ```php
 * use peer\server\AsyncServer;
 *   
 * $server= new AsyncServer();
 * $server->listen(new ServerSocket('127.0.0.1', 6100), new MyProtocol());
 * $server->service();
 * ```
 *
 * @see   xp://peer.ServerSocket
 * @test  xp://peer.unittest.server.AsyncServerTest
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
    $socket->create();
    $socket->bind(true);
    $socket->listen();

    $protocol->server= $this;
    $protocol->initialize();

    $this->select($socket, function($socket) use($protocol) {
      $connection= $socket->accept();
      if ($protocol instanceof SocketAcceptHandler && !$protocol->handleAccept($connection)) {
        $connection->close();
        return;
      }

      $this->tcpnodelay && $connection->useNoDelay();
      $protocol->handleConnect($connection);
      $this->select($connection, [
        [$protocol, 'handleData'],
        [$protocol, 'handleDisconnect'],
        [$protocol, 'handleError'],
        [$protocol, 'initialize']
      ]);
    });

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
   * @param  callable[]|function(peer.Socket|peer.BSDSocket): void $function
   * @return peer.Socket|peer.BSDSocket
   */
  public function select($socket, $function) {
    if ($this->select) {
      end($this->select);
      $i= key($this->select) + 1;
    } else {
      $i= 0;
    }

    $this->select[$i]= $socket;
    $this->handle[$i]= is_array($function) ? $function : [$function];
    return $socket;
  }

  /**
   * Schedule a given task to execute every given seconds. The task
   * function can return an integer to indicate in how many seconds
   * its next invocation should occur, overwriting the default value
   * given here. If this integer is negative, the task stops running.
   * Returns the added task's ID.
   *
   * @param  int $seconds
   * @param  function(): ?int
   * @return int
   */
  public function schedule($seconds, $function) {
    if ($this->tasks) {
      end($this->tasks);
      $i= key($this->tasks) - 1;
    } else {
      $i= -1;
    }

    $this->tasks[$i]= [$seconds, $function];
    return $i;
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

    // Set up scheduled tasks
    $time= time();
    $next= $continuation= [];
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
          unset($this->select[$i], $this->handle[$i], $next[$i], $continuation[$i]);
          continue;
        }

        // Do not re-enter handler as long as we have a continuation
        if (isset($continuation[$i])) continue;

        // Handle timeouts manually instead of leaving this up to the sockets
        // themselves - the latter has proven not to be 100% reliable.
        if (isset($next[$i]) && $next[$i] <= $time) {
          if ($f= $this->handle[$i][2] ?? null) {
            $f($socket, new SocketTimeoutException('Timed out', $socket->getTimeout()));
            $socket->close();
            unset($this->select[$i], $this->handle[$i], $next[$i], $continuation[$i]);
            continue;
          }
          $next[$i]= $time + $socket->getTimeout();
        }

        $read[$i]= $socket;
      }
      // echo '* SELECT ([', implode(', ', array_keys($next)), '] -> ', $next ? max(0, min($next) - $time) : null, ")\n";
      $sockets->select($read, $null, $null, $next ? max(0, min($next) - $time) : null);

      // Run scheduled tasks, recording their next run immediately thereafter
      $time= time();
      foreach ($this->tasks as $i => $task) {
        if ($next[$i] <= $time) {
          $n= $task[1]();
          if ($n < 0) {
            unset($this->tasks[$i], $next[$i]);
          } else {
            $next[$i]= $time + ($n ?? $task[0]);
          }
        }
      }

      // There is data on the server socket (meaning a client connection is
      // waiting to be socket), or on any of the other sockets, so we'll call
      // into their respective data handler. If a generator is returned,
      // schedule its continuation for the next possible point.
      foreach ($read as $i => $socket) {
        try {
          $r= $this->handle[$i][0]($socket);
          if ($r instanceof \Generator) {
            $continuation[$i]= $r;
            $task= $this->schedule(0, function() use(&$continuation, $i) {
              try {
                if ($continuation[$i]->valid()) {
                  $continuation[$i]->next();
                  return;
                }
              } catch (SocketException $t) {
                if ($f= $this->handle[$i][2] ?? null) $f($this->select[$i], $t);
                $this->select[$i]->close();
              }

              unset($continuation[$i]);
              return -1;
            });
            $next[$task]= $time;
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