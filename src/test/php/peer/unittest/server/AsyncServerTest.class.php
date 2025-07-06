<?php namespace peer\unittest\server;

use peer\server\AsyncServer;
use peer\unittest\StartServer;
use test\{Assert, Test, Values};

/** @deprecated in favor of AsynchronousServerTest */
#[StartServer(protocol: TestingProtocol::class, implementation: AsyncServer::class)]
class AsyncServerTest extends AbstractServerTest {
  
  #[Test]
  public function scheduled_function_immediately_invoked() {
    $invoked= 0;
    $s= new AsyncServer();
    $s->schedule(1, function() use($s, &$invoked) {
      $invoked++;
      $s->shutdown();
    });

    $before= $invoked;
    $s->service();

    Assert::equals(0, $before, 'before service()');
    Assert::equals(1, $invoked, 'after service()');
  }

  #[Test, Values([1, 2, 3])]
  public function scheduled_function_invoked_after_delay($executions) {
    $delay= 0.05; // 50 ms

    $invoked= 0;
    $s= new AsyncServer();
    $s->schedule($delay, function() use($s, $executions, &$invoked) {
      $invoked++;
      if ($invoked >= $executions) $s->shutdown();
    });

    $start= microtime(true);
    $s->service();
    $time= microtime(true) - $start;
    $expected= $delay * ($executions - 1);

    Assert::equals($executions, $invoked);
    Assert::true($time >= $expected, $time.' >= '.$expected);
  }

  #[Test]
  public function connected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
  }

  #[Test]
  public function disconnected() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);
    $socket->close();

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
    Assert::equals('DISCONNECT '.$client, $this->server->err->readLine());
  }

  #[Test]
  public function read_synchronously_written_data() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    $socket->write("SYNC 3\n");
    $read= [];
    while ('.' !== ($line= $socket->readLine()) && !$socket->eof()) {
      $read[]= $line;
    }
    $socket->close();
    Assert::equals(['1', '2', '3'], $read);

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
    Assert::equals('DISCONNECT '.$client, $this->server->err->readLine());
  }

  #[Test]
  public function read_asynchronously_written_data() {
    $socket= $this->newSocket();
    $client= $this->connectTo($socket);

    $socket->write("ASNC 3\n");
    $read= [];
    while ('.' !== ($line= $socket->readLine()) && !$socket->eof()) {
      $read[]= $line;
    }
    $socket->close();
    Assert::equals(['1', '2', '3'], $read);

    Assert::equals('CONNECT '.$client, $this->server->err->readLine());
    Assert::equals('DISCONNECT '.$client, $this->server->err->readLine());
  }
}