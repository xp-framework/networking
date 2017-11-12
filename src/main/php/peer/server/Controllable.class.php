<?php namespace peer\server;

interface Controllable {

  /**
   * Use the given address as control socket, e.g. for shutting down!
   *
   * @param  string $addr
   * @param  int $port
   * @param  [:function(string, peer.Socket, peer.server.Server)] $handlers
   * @return self
   */
  public function attach($addr, $port, $handlers= []);

}