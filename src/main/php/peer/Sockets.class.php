<?php namespace peer;

use lang\Enum;

abstract class Sockets extends Enum {
  public static $STREAM, $BSD;

  static function __static() {
    self::$STREAM= newinstance(self::class, [0, 'STREAM'], '{
      static function __static() { }

      public function select0(&$r, &$w, &$e, $timeout= null) {
        if (null === $timeout) {
          $tv_sec= $tv_usec= null;
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
          $l= __LINE__ - 8;
          if (isset(\xp::$errors[__FILE__][$l]["Invalid CRT parameters detected"])) {
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
    }');
    self::$BSD= newinstance(self::class, [1, 'BSD'], '{
      static function __static() { }

      public function select0(&$r, &$w, &$e, $timeout= null) {
        if (null === $timeout) {
          $tv_sec= $tv_usec= null;
        } else {
          $tv_sec= (int)floor($timeout);
          $tv_usec= (int)(($timeout - $tv_sec)  * 1000000);
        }

        if (false === ($n= socket_select($r, $w, $e, $tv_sec, $tv_usec))) {
          if (0 !== ($error= socket_last_error())) {
            $e= new SocketException("Select($tv_sec, $tv_usec) failed: #$error ".socket_strerror($error));
            \xp::gc(__FILE__);
            throw $e;
          }

          $n= 0;
        }

        return $n;
      }
    }');
  }

  /** Maps sockets -> handles */
  private function handles($sockets, &$lookup) {
    $r= [];
    foreach ($sockets as $socket) {
      $handle= $socket->getHandle();
      $r[]= $handle;
      $lookup[(int)$handle]= $socket;
    }
    return $r;
  }

  /** Maps handles -> socket */
  private function sockets($handles, &$lookup) {
    $r= [];
    foreach ($handles as $handle) {
      $r[]= $lookup[(int)$handle];
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
    $sockets= [];

    // Map sockets to handles
    $r= null === $read ? null : $this->handles($read, $sockets);
    $w= null === $write ? null : $this->handles($write, $sockets);
    $e= null === $except ? null : $this->handles($except, $sockets);

    // Call "raw" select on handles
    $n= $this->select0($r, $w, $e, $timeout);

    // Map handles to sockets
    $read= null === $r ? null : $this->sockets($r, $sockets);
    $write= null === $w ? null : $this->sockets($w, $sockets);
    $except= null === $e ? null : $this->sockets($e, $sockets);
    
    return $n;
  }
}