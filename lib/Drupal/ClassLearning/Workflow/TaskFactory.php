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
  Drupal\ClassLearning\Models\WorkflowActivity,
  Drupal\ClassLearning\Models\TaskActivity,

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
  private $assignmentActivity;
  
  /**
   * Constructor
   */
  /*
  public function __construct($workflow, $tasks)
  {
    $this->tasks = $tasks;
    $this->workflow = $workflow;
  }
  */
  
  public function __construct($workflow, $aa)
  {
    $this->assignmentActivity = $aa;
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
        $n = null;
        if(isset($task['behavior']))
        	$n = $task['behavior'];
		else
			$n = $name;
		
		$t->type = $n;
        $t->status = 'not triggered';
        $t->start = NULL;

        $t->settings = $this->tasks[$n];
        $t->data = [];
		
		if(isset($task['ta']))
		  $t->ta_id = $task['ta'];
		
		if(isset($task['criteria'])){
			$t->setData('grades',$task['criteria']);
		}
		
        $t->save();
      endfor;
    endforeach;
	
  }

  public function createTasksTA(){
  	//Let's get the task activities stored in the workflow activity.
  	db_set_active('activity');
	
	/*
	$wa = WorkflowActivity::where('WA_A_id','=',$this->assignmentActivity)
	  ->first();
	*/
	
	db_set_active('activity');
   
    $wa = db_select('pla_workflow_activity','wf')
      ->fields('wf')
	  ->condition('WA_A_id', $this->assignmentActivity, '=')
	  ->execute()
	  ->fetchAssoc();
    //Switch back
	  
	$taArray = array();
	
	foreach(json_decode($wa['WA_tasks'],1) as $ta){
		$taArray[] = db_select('pla_task_activity','ta')
		  ->fields('ta')
		  ->condition('TA_id', $ta, '=')
		  ->execute()
		  ->fetchAssoc();
		  
		//$taArray[] = TaskActivity::find($ta);
	}
	
	db_set_active('default');
	
	//Make tasks
	foreach($taArray as $ta){
		$t = new WorkflowTask;
        $t->workflow_id = $this->workflow->workflow_id;
		$t->type = $ta['TA_type'];
		//Worry about perfecting everything later. Let's just see if we can make tasks.
		$t->status = "not triggered";
		$t->start = null;
		$t->data = [];
		$t->ta_id = $ta['TA_id'];
		
		$t->save();
	}
	
  }

}
