<?php namespace peer\unittest;

use lang\{Runtime, IllegalStateException};
use peer\SocketEndpoint;
use test\Provider;

class StartServer implements Provider {
  private $process, $endpoint;

  public function __construct($implementation, $arguments= []) {
    $this->process= Runtime::getInstance()->newInstance(null, 'class', strtr($implementation, '\\', '.'), $arguments);
    $this->process->in->close();

    // Check if startup succeeded
    $status= $this->process->out->readLine();
    if (1 !== sscanf($status, '+ Service %[0-9.:]', $endpoint)) {
      $error= $this->process->err->readLine();
      throw new IllegalStateException('Cannot start server: '.$status.' '.$error);
    }

    $this->endpoint= SocketEndpoint::valueOf($endpoint);
  }

  public function values($type, $instance= null) {
    return [$this->process, $this->endpoint];
  }
}