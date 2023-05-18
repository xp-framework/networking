<?php namespace peer\unittest\server;

use lang\{Runtime, IllegalStateException};
use peer\Socket;
use unittest\{After, AfterClass, PrerequisitesNotMetError};

abstract class AbstractServerTest {
  protected static $serverProcess;
  protected static $bindAddress= [null, -1];
  protected $sockets= [];

  /** @return peer.Socket */
  protected function newSocket() {
    $s= new Socket(self::$bindAddress[0], self::$bindAddress[1]);
    $this->sockets[]= $s;
    return $s;
  }

  /** Connects to a socket and returns the client */
  protected function connectTo(Socket $socket): string {
    $socket->connect();
    $socket->write("CLNT\n");
    return $socket->readLine();
  }

  /**
   * Starts server in background
   *
   * @param  string $protocol Protocol implementation
   * @param  string $server Server implementation
   * @throws unittest.PrerequisitesNotMetError
   * @return void
   */
  protected function startServerWith($protocol, $server) {

    // Start server process
    with ($rt= Runtime::getInstance()); {
      self::$serverProcess= $rt->getExecutable()->newInstance(array_merge(
        $rt->startupOptions()->asArguments(),
        [$rt->bootstrapScript('class')],
        ['peer.unittest.server.TestingServer', $protocol, $server]
      ));
    }
    self::$serverProcess->in->close();

    // Check if startup succeeded
    $status= self::$serverProcess->out->readLine();
    if (2 !== sscanf($status, '+ Service %[0-9.]:%d', self::$bindAddress[0], self::$bindAddress[1])) {
      try {
        self::shutdownServer();
      } catch (IllegalStateException $e) {
        $status.= $e->getMessage();
      }
      throw new PrerequisitesNotMetError('Cannot start server: '.$status, null);
    }
  }

  #[After]
  public function closeSockets() {
    foreach ($this->sockets as $socket) {
      $socket->isConnected() && $socket->close();
    }
  }

  #[After]
  public function shutdownServer() {

    // Tell the server to shut down
    try {
      $c= new Socket(self::$bindAddress[0], self::$bindAddress[1]);
      $c->connect();
      $c->write("HALT\n");
      $c->close();
    } catch (\lang\Throwable $ignored) {
      // Fall through, below should terminate the process anyway
    }
    $status= self::$serverProcess->out->readLine();
    if (!strlen($status) || '+' !== $status[0]) {
      while ($l= self::$serverProcess->out->readLine()) {
        $status.= $l;
      }
      while ($l= self::$serverProcess->err->readLine()) {
        $status.= $l;
      }
      self::$serverProcess->close();
      throw new \lang\IllegalStateException($status);
    }
    self::$serverProcess->close();
  }
}