<?php

namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Exception as AllocatorException,
  Drupal\ClassLearning\Models\TaskActivity,
  Drupal\ClassLearning\Models\WorkflowTask as Task;

class AllocatorTA{
	
	//key = uid, value = role
	protected $users = array();
	
	//Contains workflow IDs
	protected $workflows = array();
	
	//key = task id, value = uid. returned at the end of allocation
	protected $assignments = array();
	
	
	
	public function setupUsers($role, $user){
		$users[$user] = $role;
	}
	
	public function setupWorkflows($wf){
		$workflows[] = $wf;
	}
	
	public function allocate(){
		
		$used = array();
		$pointer = 0;
		
		foreach($workflows as $workflow){
			unset($used);
			
			$tasks = Task::where('workflow_id', '=', $workflow)
			  ->get();
			  
			foreach($tasks as $task){
				//Better get that TA
				$ta = $task['ta_id'];
				
				$ta = TaskActivity::where('TA_id', '=', $ta)
				  ->first();
			}
		}
		
	}
	
	
}

?>