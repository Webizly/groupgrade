<?php
namespace Drupal\ClassLearning\Workflow;
use Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\Semester,
  Illuminate\Database\Capsule\Manager as Capsule,
  Carbon\Carbon,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\WorkflowTask;

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
  public function checkAssignment(AssignmentSection &$assignment)
  {
    if ($this->isStarted($assignment))
      return TRUE;

    $date = Carbon::createFromFormat('Y-m-d H:i:s', $assignment->asec_start);

    // Did it pass yet?
    if ($date->isPast())
      return $this->trigger($assignment);
    else
      return FALSE;
  }

  /**
   * See if an assignment has already been triggered to start
   *
   * @param AssignmentSection
   * @return bool
   */
  public function isStarted(AssignmentSection $a)
  {
    return (Workflow::where('assignment_id', '=', $a->asec_id)->count() > 0) ? TRUE : FALSE;
  }

  /**
   * Trigger the start of a assignment's processing
   *
   * @param AssignmentSection
   * @return mixed
   */
  public function trigger(AssignmentSection &$a)
  {
    
  }
}
