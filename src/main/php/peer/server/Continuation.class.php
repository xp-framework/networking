<?php namespace peer\server;

/**
 * Continuable coroutine. Not a public API!
 *
 * @see  peer.server.AsyncServer
 */
class Continuation {
  private $function;
  private $continuation= null;
  public $next;

  /** @param function(var): Generator $function */
  public function __construct($function) {
    $this->function= $function;
    $this->next= microtime(true) - 1;
  }

  /**
   * Continue executing
   *
   * @param  var $arg
   * @return ?Generator
   */
  public function continue($arg) {
    if (null === $this->continuation) {
      $this->continuation= ($this->function)($arg);
    } else {
      $this->continuation->next();
    }

    return $this->continuation->valid() ? $this->continuation : $this->continuation= null;
  }

  /**
   * Throw an exception into the execution flow
   *
   * @param  var $arg
   * @param  Throwable $t
   * @return ?Generator
   */
  public function throw($arg, $t) {
    if (null === $this->continuation) {
      $this->continuation= ($this->function)($arg);
    }
    $this->continuation->throw($t);

    return $this->continuation->valid() ? $this->continuation : $this->continuation= null;
  }
}
