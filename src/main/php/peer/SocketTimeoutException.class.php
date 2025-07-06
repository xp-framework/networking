<?php namespace peer;

/**
 * Indicate a timeout occurred on a socket read
 *
 * @test      peer.unittest.sockets.SocketTimeoutExceptionTest
 * @see      peer.Socket#setTimeout
 * @see      peer.SocketException
 */
class SocketTimeoutException extends SocketException {
  protected $timeout= 0.0;
  
  /**
   * Constructor
   *
   * @param   string message
   * @param   float timeout
   */
  public function __construct($message, $timeout) {
    parent::__construct($message);
    $this->timeout= $timeout;
  }

  /**
   * Get timeout
   *
   * @return  float
   */
  public function getTimeout() {
    return $this->timeout;
  }
  
  /**
   * Return compound message of this exception.
   *
   * @return  string
   */
  public function compoundMessage() {
    return sprintf(
      'Exception %s (%s after %.3f seconds)',
      nameof($this),
      $this->message,
      $this->timeout
    );
  }
}