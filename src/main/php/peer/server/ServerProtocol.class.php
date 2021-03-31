<?php namespace peer\server;

/**
 * Server Protocol
 *
 * @see   xp://peer.server.Server#listen
 */
interface ServerProtocol {

  /**
   * Initialize Protocol
   *
   * @return  bool
   */
  public function initialize();

  /**
   * Handle client connect
   *
   * @param  peer.Socket $socket
   */
  public function handleConnect($socket);

  /**
   * Handle client disconnect
   *
   * @param  peer.Socket $socket
   */
  public function handleDisconnect($socket);

  /**
   * Handle client data. Can return a generator to yield control back
   * to the server.
   *
   * @param  peer.Socket $socket
   * @return ?Generator
   */
  public function handleData($socket);

  /**
   * Handle I/O error
   *
   * @param  peer.Socket $socket
   * @param  lang.Throwable $e
   */
  public function handleError($socket, $e);

}