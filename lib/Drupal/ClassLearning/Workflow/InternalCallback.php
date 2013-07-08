<?php
/**
 * @file
 * Internal Callback Storage
 *
 * @package groupgrade
 */
namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Exception as CallbackException;

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
    if (! isset($task->settings['reference task']))
      throw new CallbackException('Workflow task resolve_grades does not have reference task.');

    // Get the grades
    $tasks = WorkflowTask::where('workflow_id', '=', $task->workflow_id)
      ->whereType($task->settings['reference task'])
      ->get();

    $num = -1;
    $range = (isset($task->settings['resolve range'])) ? (int) $task->settings['resolve range'] : 15;

    foreach ($tasks as $it) :
      if ($num === -1) :
        $num = (int) $it->data['grade'];
      else :
        if (abs($num-(int) $it->data['grade']) > $range)
        {
          // Not in range
          $task->setData('value', false);
          return $task->complete();
        }
      endif;
    endforeach;

    $task->setData('value', true);
    return $task->complete();
  }

  /**
   * Impliments hook for type "grades ok"
   */
  public static function grades_ok(WorkflowTask $task)
  {
    if (! isset($task->settings['reference task']))
      throw new CallbackException('Workflow task grades_ok does not have reference task.');

    // Get the grades
    $tasks = WorkflowTask::where('workflow_id', '=', $task->workflow_id)
      ->whereType($task->settings['reference task'])
      ->get();

    $index = [];

    foreach ($tasks as $it)
      $index[] = (int) $it->data['grade'];

    $average = array_sum($index)/count($index);

    $task->setData('value', $average);

    // Update the workflow
    $workflow = $task->workflow()->first();
    $workflow->setSetting('grade', $average);
    
    return $task->complete();
  }

  /**
   * Unknown method handler
   *
   * @param void
   */
  public static function __callStatic($name, $arguments = [])
  {
    // Ignore...
    echo sprintf('No callback function for %s \n', $name);
  }
}
