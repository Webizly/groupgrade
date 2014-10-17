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
		$users[] = array(
			'user' => $user,
			'role' => $role,
		);
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
				  
				$aJson = json_decode($ta['TA_assignee_constraints'],1);
				$aRole = $aJson['role'];
				//Garbage for now!
				$aTitle = $aJson['title'];
				$aConst = $aJson['constraints'];
				
				//Let's do this from order of easiest to hardest.
				
				if(isset($aConst['same as'])){
					//Let's find that person in the $used arrays...
					$assignee = $used[$aTitle][$aConst['same as']];
					$assignments[$task['ta_id']] = $assignee;
				}
				
			}
		}
		
	}
	
	
}

?>