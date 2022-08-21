<?php namespace peer\server;

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
}