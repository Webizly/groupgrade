<?php
/**
 * @file
 * Task Factory to generate the tasks for a workflow
 *
 * @package groupgrade
 */
namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\AssignmentSection,

  Drupal\ClassLearning\Workflow\Allocator,
  Drupal\ClassLearning\Workflow\Manager,

  Illuminate\Database\Capsule\Manager as Capsule,
  Carbon\Carbon;

/**
 * Task Factory
 */
class TaskFactory {
  private $workflow;
  private $tasks;

  /**
   * Constructor
   */
  public function __construct($workflow, $tasks)
  {
    $this->tasks = $tasks;
    $this->workflow = $workflow;
  }

  /**
   * Create the tasks for that workflow
   * 
   * @return bool
   */
  public function createTasks()
  {
    if (count($this->tasks) == 0)
      throw new \Drupal\ClassLearning\Exception('No tasks defined for TaskFactory');

    foreach($this->tasks as $name => $task) :
      if (isset($task['count']))
        $count = $task['count'];
      else
        $count = 1;

      // Some need multiple tasks. Ex: grading a soln
      // This can be expanded to as many as needed
      for ($i = 0; $i < $count; $i++) :
        $t = new WorkflowTask;
        $t->workflow_id = $this->workflow->workflow_id;

        // We're not assigning users at this stage
        $t->type = $name;
        $t->status = 'not triggered';
        $t->start = NULL;

        $t->settings = $this->tasks[$name];
        $t->data = [];
        $t->save();
      endfor;
    endforeach;
  }
}
