<?php
/**
 * @file
 * Internal Callback Storage
 *
 * @package groupgrade
 */
namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Models\WorkflowTask;

/**
 * Internal Callback Class
 *
 * Used to handle internal tasks function
 *
 * The WorkflowTask model will call this class to see if a method exists
 * to handle certain types of events for a task type. They look for a function
 * like this:
 * task type: resolve grades
 * callback name: resolve_grades
 */
class InternalCallback {
  /**
   * Impliments hook for resolve_grades
   */
  public static function resolve_grades(WorkflowTask $task)
  {
    var_dump($task);
    exit;
  }

  /**
   * Unknown method handler
   *
   * @param void
   */
  public static function __callStatic($name, $arguments = [])
  {
    // Ignore...
  }
}
