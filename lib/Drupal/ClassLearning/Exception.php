<?php
namespace Drupal\ClassLearning;

class Exception extends \Exception {
  /**
   * Handle the Exception
   * 
   * @param string Message from exception
   * @param integer The Exception Code
   * @param Exception The previous exception used for the exception chaining.
   * @param mixed Data used to pass to exception class for debugging
   */
  public function __construct($message = '', $code = 0, $previous = NULL, $data = NULL)
  {
    drupal_set_message(sprintf('Exception thown inside groupgrade module: %s &mdash; %s', $message, print_r($data)), 'error');

    // Pass to watchdog
    watchdog('groupgrade',
      sprintf('Exception thown inside groupgrade module: %s &mdash; %s', $message, print_r($data)), WATCHDOG_ERROR);

    
    parent::__construct($message, $code, $previous);
  } 
}
