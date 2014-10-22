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
	
	public function advancePointer(){
		$pointer++;
		if($pointer >= count($users) - 1){
			$pointer = 0;
		}
	}
	
	public function allocate(){
		
		//Don't forget to randomize users
		
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
				
				//Same as constraint
				if(isset($aConst['same as'])){
					//Let's find that person in the $used arrays...
					$assignee = $used[$aTitle][$aConst['same as']];
					$assignments[$task['task_id']] = $assignee;
				}//Not constraint
				else if(isset($aConst['not'])){
					//GIVE SUPPORT FOR ARRAYS, DUMMY!
					//Who do we want to avoid?
					$notMeArray = $used[$aTitle][$aConst['not']];
					
					//Set up a while loop here.
					$ok = false;
					while(!$ok){
						$fail = false;
						foreach($notMeArray as $notMe){
							if($users[$pointer]['user'] == $used[$aTitle][$notMe] && $users[$pointer]['role'] != $aTitle){
								$fail = true;
							}
							/*
							if($users[$pointer]['user'] != $used[$aTitle][$notMe] && $users[$pointer]['role'] == $aTitle){
								$ok = true;
								$assignments[$task['ta_id']] = $users[$pointer['user']];
								advancePointer();
							}
							 *
							 */
						}
						
						if(!$fail){
							$ok = true;
							$assignments[$task['task_id']] = $users[$pointer['user']];
						}
					
						advancePointer();
					}
					
				}//New to subwf constraint
				else if(isset($aConst['new to subwf'])){
					
					//Get subwf we want to avoid
					$avoid = $ta['TA_visual_id'];
					$avoidArray = array();
					
					//Get all the uid's that have that visual id
					foreach($used[$aTitle] as $vid => $uid){
						if($vid == $avoid){
							$avoidArray[] = $uid;
						}
					}
					
					//Continue to advance the pointer until we are pointing at someone we want
					$assignee = null;
					while(!isset($assignee)){
						if(!in_array($users[$pointer]['user'], $avoidArray) && $users[$pointer]['role'] == $aTitle){
							$assignee = $users[$pointer]['user'];
						}
						
						advancePointer();
					}
					
					$assignments[$task['task_id']] = $assignee; 
				}//Null constraint
				else{
					//assign and advance pointer
					$assignments[$task['ta_id']] = $users[$pointer['user']];
					advancePointer();
				}
				

				
			}
		}
		
		return $assignments;
	}
	
	
}

?>