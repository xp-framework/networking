<?php namespace peer\server;

use lang\{RuntimeError, Throwable};
use peer\server\protocol\SocketAcceptHandler;

/**
 * TCP/IP Server using pre-forking
 *
 * ```php
 * use peer\server\ForkedServer;
 *
 * $server= new ForkedServer(children: 20);
 * $server->listen(new ServerSocket('127.0.0.1', 6100), new MyProtocol());
 * $server->service();
 * ```
 *
 * @ext   pcntl
 * @see   peer.server.Server
 * @test  peer.unittest.server.ForkedServerTest
 */
class ForkedServer extends ServerImplementation {
  use Pcntl;

  private $parent, $children, $maxrequests;
  private $listen= [];
  private $tasks= [];
  private $select= [];

  /**
   * Constructor
   *
   * @param  int $children default 10 number of children to fork
   * @param  int $maxrequests default 1000 maxmimum # of requests per child
   * @throws lang.IllegalAccessException
   */
  public function __construct(int $children= 10, int $maxrequests= 1000) {
    self::extension();
    $this->parent= getmypid();
    $this->children= $children;
    $this->maxrequests= $maxrequests;
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

    $protocol->initialize($this);

    $this->listen[]= [$socket, $protocol];
    return $this;
  }

  /**
   * Adds socket to select, associating a function to call for data
   *
   * @param  peer.Socket|peer.BSDSocket $socket
   * @param  peer.ServerProtocol|function(peer.Socket|peer.BSDSocket): void $handler
   * @param  bool $timeout
   * @return self
   */
  public function select($socket, $handler, $timeout= false) {
    $next= $timeout ? microtime(true) + $socket->getTimeout() : null;
    if ($handler instanceof ServerProtocol) {
      $this->select[]= [$socket, $next, new Continuation(function($socket) use($handler) {
        try {

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
      })];
    } else {
      $this->select[]= [$socket, $next, new Continuation($handler)];
    }

    return $this;
  }

  /**
   * Schedule a given task to execute every given interval
   *
   * @param  int|float $interval
   * @param  function(): ?int|float
   * @return self
   */
  public function schedule($interval, $function) {
    $id= -sizeof($this->tasks) - 1;
    $this->tasks[$id]= function() use($interval, $function) {
      static $signals= [SIGINT, SIGHUP];

      try {
        while (($interval= $function() ?? $interval) >= 0) {
          $sec= (int)$interval;
          if (pcntl_sigtimedwait($signals, $_, $sec, 1000 * ($sec - $interval)) > 0) return 127;
        }
        $this->cat && $this->cat->info('Task finished');
        return 0;
      } catch (Throwable $t) {
        $this->cat && $this->cat->error('Task stopped by ', $t);
        return 255;
      }
    };

    return $this;
  }

  /**
   * Spawns a function in a new process and returns the PID
   *
   * @param  function(): int $function
   * @return int
   * @throws lang.RuntimeError
   */
  private function spawn($function) {
    $pid= pcntl_fork();
    if (-1 === $pid) {
      throw new RuntimeError('Could not fork');
    } else if (0 === $pid) {
      exit($function());
    }
    return $pid;
  }

  /**
   * Dispatches a given signal to the children
   *
   * @param  [:int] $children
   * @param  int $signal
   * @return void
   */
  private function dispatch($children, $signal) {
    foreach ($children as $pid => $i) {
      $this->cat && $this->cat->debugf('Dispatching signal %d -> pid %d', $signal, $pid);
      posix_kill($pid, $signal);
    }
  }

  /**
   * Service
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public function service() {
    $children= [];
    $child= function() {
      $sockets= current($this->listen)[0]->kind();
      $terminate= false;

      // Gracefully finish current request and exit when restarting or shutting down
      $handler= function($sig) use(&$terminate) {
        $this->cat && $this->cat->infof('Listener shutting down on signal %d', $sig);
        $terminate= true;
      };
      pcntl_signal(SIGHUP, $handler);
      pcntl_signal(SIGINT, $handler);
      pcntl_signal(SIGTERM, SIG_DFL);

      $null= null;
      for ($request= 1; $request <= $this->maxrequests; $request++) {
        $connection= null;
        do {
          pcntl_signal_dispatch();
          if ($terminate) return 1;

          $readable= [];
          foreach ($this->listen as $i => $listen) {
            $readable[$i]= $listen[0];
          }

          // The call to accept() w/o blocking will return null if there is no client available.
          // This is the case if select() detects activity but another child has already
          // accepted this client. In this case, retry.
          if ($sockets->select($readable, $null, $null, null)) {
            $selected= key($readable);
            $connection= $readable[$selected]->accept(false);
          }
        } while (null === $connection);

        $protocol= $this->listen[$selected][1];
        $this->cat && $this->cat->debugf('Child handling request #%d with protocol #%d', $request, $selected);
        if ($protocol instanceof SocketAcceptHandler && !$protocol->handleAccept($connection)) {
          $connection->close();
          continue;
        }

        $this->tcpnodelay && $connection->useNoDelay();
        $protocol->handleConnect($connection);

        try {
          do {
            pcntl_signal_dispatch();
            if ($terminate) break;

            foreach ($protocol->handleData($connection) ?? [] as $_) { }
          } while ($connection->isConnected() && !$connection->eof());

          // Handle disconnnect gracefully, ensure connection is closed
          $protocol->handleDisconnect($connection);
          $connection->close();
        } catch (Throwable $t) {

          // Handle any errors, then close connection
          $protocol->handleError($connection, $t);
          $connection->close();
        }
      }

      // Respawn listeners
      return 1;
    };

    // Spawn the specified number of children
    for ($i= 0; $i < $this->children; $i++) {
      $pid= $this->spawn($child);
      $this->cat && $this->cat->debugf('Spawned listener #%d (pid %d)', $i, $pid);
      $children[$pid]= $i;
    }

    // Spawn any tasks
    foreach ($this->tasks as $i => $task) {
      $pid= $this->spawn($task);
      $this->cat && $this->cat->debugf('Spawned task #%d (pid %d)', -($i + 1), $pid);
      $children[$pid]= $i;
    }

    $this->cat && $this->cat->info('Parent started with', array_keys($children));

    // Setup signal handlers for terminating and restarting
    $terminate= false;
    $restart= function($sig) use(&$children) {
      $this->cat && $this->cat->infof('Parent restarting children', $sig);
      $this->dispatch($children, SIGHUP);
      // Children will be respawned from within the main loop
    };
    $shutdown= function($sig) use(&$terminate) {
      $this->cat && $this->cat->infof('Parent shutting down on signal %d', $sig);
      $terminate= true;
    };
    pcntl_signal(SIGHUP, $restart);
    pcntl_signal(SIGINT, $shutdown);
    pcntl_signal(SIGTERM, $shutdown);

    // Main loop
    $null= null;
    do {
      if ($this->select) {
        $sockets ?? $sockets= current($this->listen)[0]->kind();
        $readable= [];
        foreach ($this->select as $i => $select) {
          $readable[$i]= $select[0];
        }

        // echo date('H:i:s'), " SELECT 1 @ {", \util\Objects::stringOf($readable), "}\n";
        if ($sockets->select($readable, $null, $null, 1)) {
          foreach ($readable as $i => $socket) {
            if (null === $this->select[$i][2]->continue($socket)) {
              unset($this->select[$i]);
            }
          }
        }
      } else {
        // echo date('H:i:s'), " SLEEP 1\n";
        sleep(1);
      }

      pcntl_signal_dispatch();
      if ($terminate) break;

      // Respawn children and tasks if necessary
      while (($exited= pcntl_wait($status, WNOHANG)) > 0) {
        $i= $children[$exited] ?? null;
        unset($children[$exited]);
        if (0 === $status || null === $i) continue;

        $pid= $this->spawn($this->tasks[$i] ?? $child);
        $this->cat && $this->cat->debugf('Respawned pid %d (exit status %d) -> %d)', $exited, $status, $pid);
        $children[$pid]= $i;
      }
    } while (true);

    // Tell all children to quit and wait 1 second for them to exit
    $this->dispatch($children, SIGINT);
    for ($i= 0; $i < 10; $i++) {
      while (($exited= pcntl_wait($status, WNOHANG)) > 0) {
        $this->cat && $this->cat->debugf('Waited for pid %d (exit status %d) after %d ms', $exited, $status, $i * 100);
        unset($children[$exited]);
      }

      if (empty($children)) break;
      usleep(100000);
    }

    // Forcefully terminate children which didn't exit yet
    $this->dispatch($children, SIGKILL);

    // Close all listening sockets in reverse order
    for ($i= array_key_last($this->listen); $i > 0; $i--) {
      $this->listen[$i][0]->close();
    }
    $this->cat && $this->cat->infof('Shutdown complete');
  }

  /**
   * Shutdown the server
   *
   * @return void
   */
  public function shutdown() {
    posix_kill($this->parent, SIGTERM);
  }
}