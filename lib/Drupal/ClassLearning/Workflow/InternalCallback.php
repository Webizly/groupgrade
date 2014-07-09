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
  	
	watchdog(WATCHDOG_INFO, 'Task #' . $task->task_id);
    if (! isset($task->settings['reference task']))
      throw new CallbackException('Workflow task resolve_grades does not have reference task.');

    // Get the grades
    // We need to redefine how this works since we could be using more than 2 values
    // Get all grades, add, then compare highest with lowest. Just looking for a difference.
    $tasks = WorkflowTask::where('workflow_id', '=', $task->workflow_id)
      ->whereType($task->settings['reference task'])
      ->get();

    $highest = -1;
	$lowest = 999;
    $range = (isset($task->settings['resolve range'])) ? (int) $task->settings['resolve range'] : 15;
	
	foreach($tasks as $t){
		//Array of scores
		$scores = array();
		foreach($t->data['grades'] as $category => $g){
			$scores[] = $g['grade'];
		}
		//Add these scores up
		$total = 0;
		foreach($scores as $s){
			$total+=$s;
		}
		
		//Is this the highest we have received so far? The lowest?
		if($total > $highest)
		  $highest = $total;
		if($total < $lowest)
		  $lowest = $total;
	}
	
	if(($highest-$lowest) > $range){
		$task->setData('value', false);
		return $task->complete();
	}
	
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

    foreach ($tasks as $task){
      $total = 0;
	  foreach($task->data['grades'] as $g){
        $total += intval($g['grade']);
	  }
	  $index[] = $total;
    }

    $finalGrade = max($index);
    //array_sum($index)/count($index);

    $task->setData('value', $finalGrade);

    // Update the workflow
    $workflow = $task->workflow()->first();
    var_dump($workflow);
    $workflow->setData('grade', $finalGrade);
    $workflow->save();
    var_dump($workflow);

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
