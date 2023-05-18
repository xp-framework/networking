<?php namespace peer\unittest\sockets;

use lang\Runtime;
use peer\{ConnectException, Socket, SocketEndpoint, SocketException, SocketTimeoutException};
use unittest\actions\IsPlatform;
use unittest\{Assert, After, Expect, Ignore, Test};

abstract class AbstractSocketTest {
  protected static $bindAddress= [null, -1];
  protected $sockets= [];

  /**
   * Callback for when server is connected
   *
   * @param  string $bindAddress
   * @return vid
   */
  public static function connected($bindAddress) {
    self::$bindAddress= explode(':', $bindAddress);
  }

  /**
   * Callback for when server should be shut down
   *
   * @return vid
   */
  public static function shutdown() {
    $c= new Socket(self::$bindAddress[0], self::$bindAddress[1]);
    $c->connect();
    $c->write("HALT\n");
    $c->close();
  }
  
  /**
   * Creates a new client socket
   *
   * @param   string addr
   * @param   int port
   * @return  peer.Socket
   */
  protected abstract function newSocket($addr, $port);

  /**
   * Creates a new client socket
   *
   * @return  peer.Socket
   */
  protected function newFixture() {
    $s= $this->newSocket(self::$bindAddress[0], self::$bindAddress[1]);
    $this->sockets[]= $s;
    return $s;
  }

  /**
   * Read exactly the specific amount of bytes.
   *
   * @param  peer.Socket $fixture
   * @param  int $num
   * @return string
   */
  protected function readBytes($fixture, $num) {
    $bytes= '';
    do {
      $bytes.= $fixture->readBinary($num- strlen($bytes));
    } while (strlen($bytes) < $num);
    return $bytes;
  }

  #[After]
  public function close() {
    foreach ($this->sockets as $socket) {
      $socket->isConnected() && $socket->close();
    }
  }

  #[Test]
  public function initiallyNotConnected() {
    $fixture= $this->newFixture();
    Assert::false($fixture->isConnected());
  }

  #[Test]
  public function connect() {
    $fixture= $this->newFixture();
    Assert::true($fixture->connect());
    Assert::true($fixture->isConnected());
  }

  #[Test, Expect(ConnectException::class)]
  public function connectInvalidPort() {
    $this->newSocket(self::$bindAddress[0], -1)->connect(0.1);
  }

  #[Test, Expect(ConnectException::class)]
  public function connectInvalidHost() {
    $this->newSocket('@invalid', self::$bindAddress[1])->connect(0.1);
  }

  #[Test, Expect(ConnectException::class)]
  public function connectIANAReserved49151() {
    $this->newSocket(self::$bindAddress[0], 49151)->connect(0.1);
  }

  #[Test]
  public function closing() {
    $fixture= $this->newFixture();
    Assert::true($fixture->connect());
    Assert::true($fixture->close());
    Assert::false($fixture->isConnected());
  }

  #[Test]
  public function closingNotConnected() {
    $fixture= $this->newFixture();
    Assert::false($fixture->close());
  }
  
  #[Test]
  public function eofAfterClosing() {
    $fixture= $this->newFixture();
    Assert::true($fixture->connect());
    
    $fixture->write("ECHO EOF\n");
    Assert::equals("+ECHO EOF\n", $fixture->readBinary());
    
    $fixture->write("CLOS\n");
    Assert::equals('', $fixture->readBinary());

    Assert::true($fixture->eof());
    $fixture->close();
    Assert::false($fixture->eof());
  }

  #[Test]
  public function write() {
    $fixture= $this->newFixture();
    $fixture->connect();
    Assert::equals(10, $fixture->write("ECHO data\n"));
  }

  #[Test, Expect(SocketException::class)]
  public function writeUnConnected() {
    $fixture= $this->newFixture();
    $fixture->write('Anything');
  }

  #[Test, Ignore('Writes still succeed after close - no idea why...')]
  public function writeAfterEof() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("CLOS\n");
    try {
      $fixture->write('Anything');
      $this->fail('No exception raised', null, 'peer.SocketException');
    } catch (SocketException $expected) {
      // OK
    }
  }

  #[Test]
  public function readLine() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("ECHO data\n");
    Assert::equals("+ECHO data", $fixture->readLine());
  }

  #[Test, Expect(SocketException::class)]
  public function readLineUnConnected() {
    $fixture= $this->newFixture();
    $fixture->readLine();
  }

  #[Test]
  public function readLineOnEof() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("CLOS\n");
    Assert::null($fixture->readLine());
    Assert::true($fixture->eof(), '<EOF>');
  }

  #[Test]
  public function readLinesWithLineFeed() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("LINE 5 %0A\n");
    for ($i= 0; $i < 5; $i++) {
      Assert::equals('+LINE '.$i, $fixture->readLine(), 'Line #'.$i);
    }
    Assert::equals('+LINE .', $fixture->readLine());
  }

  #[Test, Ignore('readLine() only works for \n or \r\n at the moment')]
  public function readLinesWithCarriageReturn() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("LINE 5 %0D\n");
    for ($i= 0; $i < 5; $i++) {
      Assert::equals('+LINE '.$i, $fixture->readLine(), 'Line #'.$i);
    }
    Assert::equals('+LINE .', $fixture->readLine());
  }

  #[Test]
  public function readLinesWithCarriageReturnLineFeed() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("LINE 5 %0D%0A\n");
    for ($i= 0; $i < 5; $i++) {
      Assert::equals('+LINE '.$i, $fixture->readLine(), 'Line #'.$i);
    }
    Assert::equals('+LINE .', $fixture->readLine());
  }
  
  #[Test]
  public function readLineAndBinary() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("LINE 3 %0D%0A\n");
    Assert::equals('+LINE 0', $fixture->readLine());
    Assert::equals("+LINE 1\r\n+LINE 2\r\n+LINE .\n", $this->readBytes($fixture, 26));
  }

  #[Test]
  public function readLineAndBinaryWithMaxLen() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("LINE 3 %0D%0A\n");
    Assert::equals('+LINE 0', $fixture->readLine());
    Assert::equals("+LINE 1\r\n", $this->readBytes($fixture, 9));
    Assert::equals("+LINE 2\r\n", $this->readBytes($fixture, 9));
    Assert::equals('+LINE .', $fixture->readLine());
  }

  #[Test]
  public function read() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("ECHO data\n");
    Assert::equals("+ECHO data\n", $fixture->read());
  }

  #[Test, Expect(SocketException::class)]
  public function readUnConnected() {
    $fixture= $this->newFixture();
    $fixture->read();
  }

  #[Test]
  public function readOnEof() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("CLOS\n");
    Assert::null($fixture->read());
    Assert::true($fixture->eof(), '<EOF>');
  }

  #[Test]
  public function readBinary() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("ECHO data\n");
    Assert::equals("+ECHO data\n", $fixture->read());
  }

  #[Test, Expect(SocketException::class)]
  public function readBinaryUnConnected() {
    $fixture= $this->newFixture();
    $fixture->readBinary();
  }

  #[Test]
  public function readBinaryOnEof() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("CLOS\n");
    Assert::equals('', $fixture->readBinary());
    Assert::true($fixture->eof(), '<EOF>');
  }

  #[Test]
  public function canRead() {
    $fixture= $this->newFixture();
    $fixture->connect();
    Assert::false($fixture->canRead(0.1));
  }

  #[Test, Expect(SocketException::class)]
  public function canReadUnConnected() {
    $fixture= $this->newFixture();
    $fixture->canRead(0.1);
  }

  #[Test]
  public function canReadWithData() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->write("ECHO data\n");
    Assert::true($fixture->canRead(0.1));
  }

  #[Test]
  public function getHandle() {
    $fixture= $this->newFixture();
    $fixture->connect();
    Assert::notequals(null, $fixture->getHandle());
  }

  #[Test]
  public function getHandleAfterClose() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->close();
    Assert::null($fixture->getHandle());
  }

  #[Test]
  public function getHandleUnConnected() {
    $fixture= $this->newFixture();
    Assert::null($fixture->getHandle());
  }

  #[Test, Expect(SocketTimeoutException::class)]
  public function readTimeout() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setTimeout(0.1);
    $fixture->read();
  }

  #[Test, Expect(SocketTimeoutException::class)]
  public function readBinaryTimeout() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setTimeout(0.1);
    $fixture->readBinary();
  }

  #[Test, Expect(SocketTimeoutException::class)]
  public function readLineTimeout() {
    $fixture= $this->newFixture();
    $fixture->connect();
    $fixture->setTimeout(0.1);
    $fixture->readLine();
  }

  #[Test]
  public function inputStream() {
    $fixture= $this->newFixture();
    $expect= '<response><type>status</type><payload><bool>true</bool></payload></response>';
    $fixture->connect();
    $fixture->write('ECHO '.$expect."\n");
    
    $si= $fixture->getInputStream();
    Assert::true($si->available() > 0, 'available() > 0');
    Assert::equals('+ECHO '.$expect, $si->read(strlen($expect)+ strlen('+ECHO ')));
  }

  #[Test]
  public function remoteEndpoint() {
    $fixture= $this->newFixture();
    Assert::equals(
      new SocketEndpoint(self::$bindAddress[0], self::$bindAddress[1]),
      $fixture->remoteEndpoint()
    );
  }

  #[Test]
  public function localEndpointForUnconnectedSocket() {
    $fixture= $this->newFixture();
    Assert::null($fixture->localEndpoint());
  }

  #[Test]
  public function localEndpointForConnectedSocket() {
    $fixture= $this->newFixture();
    $fixture->connect();
    Assert::instance(SocketEndpoint::class, $fixture->localEndpoint());
  }

  #[Test]
  public function select_when_nothing_can_be_read() {
    $fixture= $this->newFixture();
    $fixture->connect();
    
    $r= [$fixture]; $w= null; $e= null;
    $fixture->kind()->select($r, $w, $e, 0.1);

    Assert::equals([], $r);
  }

  #[Test]
  public function select_when_data_is_available() {
    $fixture= $this->newFixture();
    $fixture->connect();
    
    $fixture->write("ECHO EOF\n");

    $r= [$fixture]; $w= null; $e= null;
    $fixture->kind()->select($r, $w, $e, 0.1);

    Assert::equals([$fixture], $r);
  }

  #[Test]
  public function select_from_two_sockets() {
    $fixture= $this->newFixture();
    $a= clone $fixture;
    $b= clone $fixture;

    $a->connect();
    $b->connect();
    $a->write("ECHO EOF\n");

    $r= [$b, $a]; $w= null; $e= null;
    $fixture->kind()->select($r, $w, $e, 0.1);

    Assert::equals([1 => $a], $r);
  }

  #[Test]
  public function select_keyed_array() {
    $fixture= $this->newFixture();
    $fixture->connect();
    
    $fixture->write("ECHO EOF\n");

    $r= ['fixture' => $fixture]; $w= null; $e= null;
    $fixture->kind()->select($r, $w, $e, 0.1);

    Assert::equals(['fixture' => $fixture], $r);
  }
}