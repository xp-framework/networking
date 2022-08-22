<?php namespace peer\server;

use Throwable;
use lang\IllegalStateException;
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
  private $select= [], $tasks= [], $continuation= [];

  static function __static() {

    // For PHP < 7.3.0
    if (!function_exists('array_key_last')) {
      function array_key_last(&$array) {
        return key(array_slice($array, -1, 1, true));
      }
    }
  }

  /**
   * Adds server socket to listen on, associating protocol handler with it
   *
   * @param  peer.ServerSocket|peer.BSDServerSocket $socket
   * @param  peer.server.ServerProtocol $protocol
   * @return self
   */
  public function listen($socket, ServerProtocol $protocol) {
    $socket->create();
    $socket->bind(true);
    $socket->listen();

    $protocol->server= $this;
    $protocol->initialize();

    $i= $this->select ? array_key_last($this->select) + 1 : 1;
    $this->select[$i]= $socket;
    $this->continuation[$i]= new Continuation(function($socket) use($protocol) {
      do {
        $connection= $socket->accept();
        if ($protocol instanceof SocketAcceptHandler && !$protocol->handleAccept($connection)) {
          $connection->close();
          return;
        }

        $this->tcpnodelay && $connection->useNoDelay();
        yield 'accept' => $this->select($connection, $protocol);
      } while (!$this->terminate);
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
   * @param  peer.Protocol|function(peer.Socket|peer.BSDSocket): void $handler
   * @return peer.Socket|peer.BSDSocket
   */
  public function select($socket, $handler) {
    $i= $this->select ? array_key_last($this->select) + 1 : 1;
    $this->select[$i]= $socket;
    if ($handler instanceof ServerProtocol) {
      $this->continuation[$i]= new Continuation(function($socket) use($handler) {
        try {
          $handler->handleConnect($socket);

          // Check for readability, then handle incoming data
          while ($socket->isConnected() && !$socket->eof()) {
            yield 'read' => $socket;
            yield from $handler->handleData($socket) ?? [];
          }

          // Handle disconnnect gracefully, ensure socket is closed
          $handler->handleDisconnect($socket);
          $socket->close();
        } catch (Throwable $t) {

          // Handle any errors, then close socket
          $handler->handleError($socket, $t);
          $socket->close();
        }
      });
    } else {
      $this->continuation[$i]= new Continuation($handler);
    }
    return $socket;
  }

  /**
   * Schedule a given task to execute every given seconds. The task
   * function can return how many seconds its next invocation should
   * occur, overwriting the default value given here. If this number
   * is negative, the task stops running. Returns the added task's ID.
   *
   * Note: If the task function raises any exception the task stops
   * running. To continue executing, exceptions must be caught and
   * handled within the function!
   *
   * @param  int|float $seconds
   * @param  function(): ?int|float
   * @return int
   */
  public function schedule($seconds, $function) {
    $i= $this->tasks ? array_key_last($this->tasks) - 1 : -1;
    $this->tasks[$i]= $function;
    $this->continuation[$i]= new Continuation(function($function) use($seconds) {
      try {
        while (($seconds= $function() ?? $seconds) >= 0) {
          yield 'delay' => $seconds * 1000;
        }
      } catch (Throwable $t) {
        // Not displayed, simply stops execution
      }
    });
    return $i;
  } 

  /**
   * Runs service until shutdown() is called.
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function service() {
    if (empty($this->select) && empty($this->tasks)) {
      throw new IllegalStateException('No sockets or tasks to execute');
    }

    $readable= $writeable= $waitable= $write= [];
    $sockets= $errors= null;
    do {
      $time= microtime(true);
      $wait= [];
      foreach ($this->continuation as $i => $continuation) {
        if ($continuation->next >= $time) {
          $wait[]= $continuation->next - $time;
          continue;
        } else if (isset($this->tasks[$i])) {
          $execute= $continuation->continue($this->tasks[$i]);
          unset($waitable[$i]);
        } else if (isset($readable[$i]) || isset($writeable[$i]) || isset($waitable[$i])) {
          $execute= $continuation->continue($this->select[$i]);
          unset($readable[$i], $writeable[$i], $waitable[$i]);
        } else {
          isset($write[$i]) ? $writeable[$i]= $this->select[$i] : $readable[$i]= $this->select[$i];
          continue;
        }

        // Check whether execution has finished
        if (null === $execute) {
          unset($this->tasks[$i], $this->select[$i], $this->continuation[$i], $write[$i]);
          continue;
        }

        // `yield 'accept' => $socket`: Check for being able to read from socket
        // `yield 'read' => $_`: Continue as soon as the socket becomes readable
        // `yield 'write' => $_`: Continue as soon as the socket becomes writeable
        // `yield 'delay' => $millis`: Wait a specified number of milliseconds
        // `yield`: Continue at the next possible execution slot (`delay => 0`)
        switch ($execute->key()) {
          case 'accept':
            $socket= $execute->current();
            $readable[array_key_last($this->select)]= $socket;
            $readable[$i]= $this->select[$i];
            $wait[]= $socket->getTimeout();
            break;

          case 'write':
            $write[$i]= true;
            $writeable[$i]= $this->select[$i];
            $wait[]= $this->select[$i]->getTimeout();
            break;

          case 'read':
            unset($write[$i]);
            $readable[$i]= $this->select[$i];
            $wait[]= $this->select[$i]->getTimeout();
            break;

          case 'delay': default:
            $delay= $execute->current() / 1000;
            $continuation->next= $time + $delay;
            $waitable[$i]= true;
            $wait[]= $delay;
            break;
        }
      }

      // When asked to terminate, close sockets in reverse order
      if ($this->terminate) {
        for ($i= array_key_last($this->select); $i > 0; $i--) {
          isset($this->select[$i]) && $this->select[$i]->close();
        }
        break;
      }

      if ($this->select) {
        // echo date('H:i:s'), " SELECT ", \util\Objects::stringOf($wait), " @ {\n",
        //   "  R: ", \util\Objects::stringOf($readable), "\n",
        //   "  W: ", \util\Objects::stringOf($writeable), "\n",
        // "}\n";
        $sockets ?? $sockets= current($this->select)->kind();
        $sockets->select($readable, $writeable, $errors, $wait ? min($wait) : null);
      } else {
        // echo date('H:i:s'), " SLEEP ", \util\Objects::stringOf($wait), "\n";
        $wait && usleep(1000000 * (int)min($wait));
      }
    } while ($this->select || $this->tasks);
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