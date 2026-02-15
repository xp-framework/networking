<?php namespace peer\unittest;

use lang\{Runtime, IllegalStateException};
use peer\SocketEndpoint;
use peer\server\AsynchronousServer;
use test\Provider;
use test\execution\Context;

class StartServer implements Provider {
  private $process, $endpoint;

  /**
   * Starts a testing server
   *
   * @param  string $protocol Protocol class
   * @param  string $implementation Server implementation class
   */
  public function __construct($protocol, $implementation= AsynchronousServer::class) {
    $this->process= Runtime::getInstance()->newInstance(null, 'class', 'peer.unittest.TestingServer', [
      $protocol,
      $implementation,
    ]);
    $this->process->in->close();

    // Check if startup succeeded
    $status= $this->process->out->readLine();
    if (1 !== sscanf($status, '+ Service %[0-9.:]', $endpoint)) {
      $error= $this->process->err->readLine();
      throw new IllegalStateException('Cannot start server: '.$status.' '.$error);
    }

    $this->endpoint= SocketEndpoint::valueOf($endpoint);
  }

  /** @return var */
  public function values(Context $context) {
    return [$this->process, $this->endpoint];
  }
}