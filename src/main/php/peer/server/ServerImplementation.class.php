<?php namespace peer\server;

use util\log\Traceable;

/**
 * Abstract base class for TCP/IP server implementations
 *
 * @see   peer.ServerSocket
 * @see   peer.ServerProtocol
 * @test  peer.unittest.server.AbstractServerTest
 */
abstract class ServerImplementation implements Traceable {
  protected $tcpnodelay= false;
  protected $cat= null;

  /**
   * Set a trace for debugging
   *
   * @param   util.log.LogCategory cat
   */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /**
   * Set TCP_NODELAY
   *
   * @param  bool $tcpnodelay
   * @return self
   */
  public function useNoDelay($tcpnodelay) {
    $this->tcpnodelay= $tcpnodelay;
    return $this;
  }

  /**
   * Sets socket to listen on and protocol to implement
   *
   * @param  peer.ServerSocket|peer.BSDServerSocket $socket
   * @param  peer.ServerProtocol $protocol
   * @return self
   */
  public abstract function listen($socket, ServerProtocol $protocol);

  /**
   * Adds socket to select, associating a function to call for data
   *
   * @param  peer.Socket|peer.BSDSocket $socket
   * @param  peer.ServerProtocol|function(peer.Socket|peer.BSDSocket): void $handler
   * @param  bool $timeout
   * @return self
   */
  public abstract function select($socket, $handler, $timeout= false);

  /**
   * Schedule a given task to execute every given interval. The task
   * function can return how many seconds its next invocation should
   * occur, overwriting the default value given here. If this number
   * is negative, the task stops running. Returns the added task's ID.
   *
   * Note: If the task function raises any exception the task stops
   * running. To continue executing, exceptions must be caught and
   * handled within the function!
   *
   * @param  int|float $interval
   * @param  function(): ?int|float
   * @return self
   */
  public abstract function schedule($interval, $function);

  /**
   * Service
   *
   * @return void
   * @throws lang.IllegalStateException
   */
  public abstract function service();

  /**
   * Shutdown the server
   *
   * @return void
   */
  public abstract function shutdown();
}