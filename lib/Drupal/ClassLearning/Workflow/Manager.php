<?php
namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\AssignmentSection,

  Drupal\ClassLearning\Workflow\Allocator,

  Illuminate\Database\Capsule\Manager as Capsule,
  Carbon\Carbon;

/**
 * Manager of the Workflow
 *
 * @package groupgrade
 * @subpackage workflows
 */
class Manager {
  /**
   * Check to see if an assignment section should be triggered to start
   * 
   * @param AssignmentSection
   * @return boolean
   */
  public static function checkAssignment(AssignmentSection &$assignment)
  {
    if (self::isStarted($assignment))
      return TRUE;

    $date = Carbon::createFromFormat('Y-m-d H:i:s', $assignment->asec_start);

    // Did it pass yet?
    if ($date->isPast())
      return self::trigger($assignment);
    else
      return FALSE;
  }

  /**
   * See if an assignment has already been triggered to start
   *
   * @param AssignmentSection
   * @return bool
   */
  public static function isStarted(AssignmentSection $a)
  {
    return (Workflow::where('assignment_id', '=', $a->asec_id)->count() > 0) ? TRUE : FALSE;
  }

  /**
   * Trigger the start of a assignment's processing
   *
   * @param AssignmentSection
   * @return mixed
   */
  public static function trigger(AssignmentSection &$a)
  {
    // Let's get all the users and all the different roles
    $users = SectionUsers::where('section_id', '=', $a->section_id)
      ->where('su_status', '=', 'active')
      ->get();

    // We're just creating a workflow for each user
    // They're not actually assigned to this workflow
    foreach($users as $null) :
      $w = new Workflow;
      $w->type = 'one_a';
      $w->assignment_id = $a->asec_id;
      $w->workflow_start = Carbon::now()->toDateTimeString();
      $w->save();

      // Create the workflows tasks
      self::triggerTaskCreation($w, $a);
    endforeach;
  }

  /**
   * Trigger Task Creation
   *
   * @todo Make this dynamic in that it will trigger tasks based upon the workflow type. Also specify task time
   * @access protected
   * @param Workflow
   * @param AssignmentSection
   */
  protected static function triggerTaskCreation(&$workflow, &$assignment)
  {
    $tasks = [
      'create problem' => [
        'duration' => 3,
        'trigger' => [

        ],
      ],

      'edit problem' => [
        'pool' => 'instructor',
        'duration' => 1,
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'create problem',
            'task status' => 'complete',
          ],
        ],
      ],

      'create solution' => [
        'duration' => 3,
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'edit problem',
            'task status' => 'complete',
          ],
        ],
      ],

      'grade solution' => [
        'count' => 2,
        'duration' => 3,
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'create solution',
            'task status' => 'complete',
          ],
        ],
      ],

      // Resolve the grades
      'resolve grades' => [
        'internal' => true,

        // Default value
        'value' => true,

        // Trigger once all the grades are submitted
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'grade solution',
            'task status' => 'complete',
          ],
        ],
      ],

      // Grades are fine, store them in the workflow
      'grades ok' => [
        'internal' => true,
        'trigger' => [
          [
            'type' => 'value of task in range',
            'task type' => 'resolve grades',

            // Range of 15 points
            'range' => 15,
          ]
        ],
        
        // Expire if grades are out of range
        'expire' => [
          [
            'type' => 'value of task out of range',
            'task type' => 'resolve grades',

            // Range of 15 points
            'range' => 15,
          ]
        ],
      ],

      // Grades are out of a range and we need a second grader
      'resolution grader' => [
        'trigger' => [
          [
            'type' => 'value of task out of range',
            'task type' => 'resolve grades',

            // Range of 15 points
            'range' => 15,
          ]
        ],

        // Expire if grades are in range
        'expire' => [
          [
            'type' => 'value of task in range',
            'task type' => 'resolve grades',

            // Range of 15 points
            'range' => 15,
          ]
        ],
      ],

      // Dispute grades
      // This step gives the option to dispute the grade they have recieved on their
      // soln to yet-another-grader
      'dispute' => [
        'duration' => 2,
        'alias user' => 'create solution',
        
        // Default value
        'value' => false,

        // Trigger this if one of the tasks "resolution grader" or
        // "grades ok" is complete.
        'trigger' => [
          [
            'type' => 'check tasks for status',
            'task types' => ['resolution grader', 'grades ok'],
            'task status' => 'complete'
          ],
        ],
      ],

      // Resolve a dispute and end the workflow
      // Trigger only if the "dispute" task has a value of true
      'resolve dispute' => [
        'pool' => 'instructor',
        'duration' => 2,

        'trigger' => [
          [
            'type' => 'compare value of task',
            'task type' => 'dispute',
            'compare value' => true,
          ],
        ],
      ],
    ];

    foreach($tasks as $name => $task) :
      if (isset($tasks[$name]['count']))
        $count = $tasks[$name]['count'];
      else
        $count = 1;

      // Some need multiple tasks. Ex: grading a soln
      // This can be expanded to as many as needed
      for ($i = 0; $i < $count; $i++) :
        $t = new WorkflowTask;
        $t->workflow_id = $workflow->workflow_id;

        // We're not assigning users at this stage
        $t->type = $name;
        $t->status = 'not triggered';
        $t->start = NULL;

        $t->settings = $tasks[$name];
        $t->data = [];
        $t->save();
      endfor;
    endforeach;
  }
}
