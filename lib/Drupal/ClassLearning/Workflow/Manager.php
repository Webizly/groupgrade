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
      ],

      'edit problem' => [
        'pool' => 'instructor',
        'duration' => 1,
      ],

      'create solution' => [
        'duration' => 3,
      ],

      'grade solution' => [
        'count' => 2,
        'duration' => 3,
      ],

      // If grades are within margin of each other,
      // automatically resolve them taking in max, avg, etc.
      // If not, trigger the 'resolution grader' task
      'resolve grade' => [
        'internal' => true,
      ],

      'resolution grader' => [
        'force trigger' => true,
        'duration' => 3,
      ],

      'dispute' => [
        'duration' => 2,
        'alias user' => 'create solution',
      ],

      'resolve dispute' => [
        'pool' => 'instructor',
        'force trigger' => true,
        'duration' => 2,
      ],

      'end' => [
        'internal' => true,
      ]
    ];

    // Running count of time
    $bumpTime = Carbon::now();

    foreach($tasks as $name => $task) :
      $t = new WorkflowTask;
      $t->workflow_id = $workflow->workflow_id;

      // We're not assigning users at this stage
      
      $t->type = $name;
      $t->status = 'not triggered';
      
      // Calculate start time
      if (isset($tasks[$name]['duration'])) :
        $t->start = $bumpTime->toDateTimeString();
        $bumpTime->addDays($tasks[$name]['duration']);
      else :
        // No set duration
        $t->start = $bumpTime->toDateTimeString();
      endif;

      $t->settings = $tasks[$name];
      $t->data = [];
      $t->save();
    endforeach;
  }
}
