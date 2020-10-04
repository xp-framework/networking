<?php namespace peer\unittest\sockets;

use peer\SocketTimeoutException;
use unittest\Test;

class SocketTimeoutExceptionTest extends \unittest\TestCase {

  #[Test]
  public function getTimeout() {
    $this->assertEquals(
      1.0, 
      (new SocketTimeoutException('', 1.0))->getTimeout()
    );
  }

  #[Test]
  public function compoundMessage() {
    $this->assertEquals(
      'Exception peer.SocketTimeoutException (Read failed after 1.000 seconds)',
      (new SocketTimeoutException('Read failed', 1.0))->compoundMessage()
    );
  }
}