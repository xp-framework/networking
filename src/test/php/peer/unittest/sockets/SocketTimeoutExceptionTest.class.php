<?php namespace peer\unittest\sockets;

use peer\SocketTimeoutException;
use unittest\{Assert, Test};

class SocketTimeoutExceptionTest {

  #[Test]
  public function getTimeout() {
    Assert::equals(
      1.0, 
      (new SocketTimeoutException('', 1.0))->getTimeout()
    );
  }

  #[Test]
  public function compoundMessage() {
    Assert::equals(
      'Exception peer.SocketTimeoutException (Read failed after 1.000 seconds)',
      (new SocketTimeoutException('Read failed', 1.0))->compoundMessage()
    );
  }
}