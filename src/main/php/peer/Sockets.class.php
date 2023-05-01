<?php namespace peer;

use lang\Enum;

abstract class Sockets extends Enum {
  public static $STREAM, $BSD;

  static function __static() {
    self::$STREAM= new class(0, 'STREAM') extends Sockets {
      static function __static() { }

      public function select0(&$r, &$w, &$e, $timeout= null) {
        if (null === $timeout) {
          $tv_sec= null;
          $tv_usec= null;
        } else {
          $tv_sec= (int)floor($timeout);
          $tv_usec= (int)(($timeout - $tv_sec)  * 1000000);
        }

        $n= stream_select($r, $w, $e, $tv_sec, $tv_usec);

        // Implementation vagaries:
        // * For Windows, when using the VC9 binaries, get rid of "Invalid CRT 
        //   parameters detected" warning which is no error, see PHP bug #49948
        // * On Un*x OS flavors, when select() raises a warning, this *is* an 
        //   error (regardless of the return value)
        if (isset(\xp::$errors[__FILE__])) {
          $msg= key(\xp::$errors[__FILE__][__LINE__ - 8]);
          if (stristr($msg, 'Interrupted system call')) {
            \xp::gc(__FILE__);
            return null;
          } else if (stristr($msg, 'Invalid CRT parameters detected')) {
            \xp::gc(__FILE__);
          } else {
            $n= false;
          }
        }

        if (false === $n || null === $n) {
          $e= new SocketException("Select($tv_sec, $tv_usec) failed");
          \xp::gc(__FILE__);
          throw $e;
        }

        return $n;
      }
    };
    self::$BSD= new class(1, 'BSD') extends Sockets {
      static function __static() { }

      public function select0(&$r, &$w, &$e, $timeout= null) {
        if (null === $timeout) {
          $tv_sec= $tv_usec= null;
        } else {
          $tv_sec= (int)floor($timeout);
          $tv_usec= (int)(($timeout - $tv_sec)  * 1000000);
        }

        if (false === ($n= socket_select($r, $w, $e, $tv_sec, $tv_usec))) {
          switch ($error= socket_last_error()) {
            case SOCKET_EINTR:
              return null;

            default:
              $e= new SocketException("Select($tv_sec, $tv_usec) failed: #$error ".socket_strerror($error));
              \xp::gc(__FILE__);
              throw $e;
          }
          return 0;
        }

        return $n;
      }
    };
  }

  /** Maps sockets -> handles */
  private function handles($sockets) {
    $r= [];
    foreach ($sockets as $key => $socket) {
      $r[$key]= $socket->getHandle();
    }
    return $r;
  }

  /** Maps handles -> socket */
  private function search($handles, $sockets) {
    $r= [];
    foreach ($sockets as $key => $socket) {
      if (false !== array_search($socket->getHandle(), $handles, true)) {
        $r[$key]= $socket;
      }
    }
    return $r;
  }

  /** Maps handles -> socket */
  private function map($handles, $sockets) {
    $r= [];
    foreach ($handles as $key => $_) {
      $r[$key]= $sockets[$key];
    }
    return $r;
  }

  /**
   * Raw select on handles
   *
   * @param  resource[] $read
   * @param  resource[] $write
   * @param  resource[] $except
   * @param  float $timeout
   * @return int
   * @throws peer.SocketException
   */
  public abstract function select0(&$read, &$write, &$except, $timeout= null);

  /**
   * Select on sockets
   *
   * @param  peer.Socket[] $read
   * @param  peer.Socket[] $write
   * @param  peer.Socket[] $except
   * @param  float $timeout
   * @return int
   * @throws peer.SocketException
   */
  public function select(&$read, &$write, &$except, $timeout= null) {

    // Map sockets to handles
    $r= null === $read ? null : $this->handles($read);
    $w= null === $write ? null : $this->handles($write);
    $e= null === $except ? null : $this->handles($except);

    // Call "raw" select on handles
    $n= $this->select0($r, $w, $e, $timeout);

    // Map handles to sockets. For PHP < 7.2, use less-performant search-based lookup
    // as stream_select() sometimes (?!) doesn't preserve the keys even though PHP bug
    // #53427 was fixed in PHP 5.4.1, see https://bugs.php.net/bug.php?id=53427 and
    // https://github.com/php/php-src/commit/22d461df621a1c059800a50c9d5c8bba41a14f16
    if (PHP_VERSION_ID < 70200) {
      $read= null === $r ? null : $this->search($r, $read);
      $write= null === $w ? null : $this->search($w, $write);
      $except= null === $e ? null : $this->search($e, $except);
    } else {
      $read= null === $r ? null : $this->map($r, $read);
      $write= null === $w ? null : $this->map($w, $write);
      $except= null === $e ? null : $this->map($e, $except);
    }
    
    return $n;
  }
}