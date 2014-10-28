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
	
	
	
	public function addUsers($role, $user){
		
		foreach($user as $u){
			$this->users[] = array(
				'user' => $u['user_id'],
				'role' => $role,
			);
			watchdog(WATCHDOG_INFO, "USER: " . $role . " " . $u['user_id']);
		}
	}
	
	public function addWorkflow($wf){
		$this->workflows[] = $wf;
		watchdog(WATCHDOG_INFO, "WF ID: " . $wf);
	}
	
	public function advancePointer($pointer){
		$pointer++;
		if($pointer > count($this->users) - 1){
			$pointer = 0;
		}
		
		return $pointer;
	}
	
	public function allocate(){
		
		//Don't forget to randomize users
		
		$used = array();
		$pointer = 0;
		$startPointer = 0;
		
		foreach($this->workflows as $workflow){
			unset($used);
			
			//watchdog(WATCHDOG_INFO, "USING WF: " . $workflow);
			
			$pointer = $startPointer;
			
			$tasks = Task::where('workflow_id', '=', $workflow)
			  ->get();
			  
			foreach($tasks as $task){
				//Better get that TA
				$ta = $task['ta_id'];
				
				$ta = TaskActivity::where('TA_id', '=', $ta)
				  ->first();
				
				//watchdog(WATCHDOG_INFO,"TASK: " . $task['task_id'] . " TA: " . $ta);
				
				$aJson = json_decode($ta['TA_assignee_constraints'],1);
				$aRole = $aJson['role'];
				//watchdog(WATCHDOG_INFO,$aRole);
				//Garbage for now!
				$aTitle = $aJson['title'];
				$aConst = $aJson['constraints'];
				
				//watchdog(WATCHDOG_INFO,"ACONST: " . $aRole . " " . $aTitle . " " . $aConst);
				
				//Same as constraint
				if(isset($aConst['same as'])){
					
					//Let's find that person in the $used arrays...
					$assignee = $used[$aRole][$aConst['same as']];
					$this->assignments[$task['task_id']] = $assignee;
					$used[$aRole][$ta['TA_visual_id']] = $assignee;
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee);
				}//Not constraint
				else if(isset($aConst['not'])){
					//Who do we want to avoid?
					$notMeArray = $aConst['not'];
					$assignee = null;
					
					$avoidThese = array();
					
					foreach($notMeArray as $vid){
						$avoidThese[] = $used[$aRole][$vid];
						//watchdog(WATCHDOG_INFO,"NOT THIS GUY: " . $used[$aRole][$vid]);
					}
					
					//watchdog(WATCHDOG_INFO, "GOING TO ASSIGN");
					
					while(!isset($assignee)){
						//$assignee = 0;
						//watchdog(WATCHDOG_INFO, "POINTING TO: " . $this->users[$pointer]['user']);
						//watchdog(WATCHDOG_INFO, $this->users[$pointer]['role'] . " = " . $aRole);
						if(!in_array($this->users[$pointer]['user'], $avoidThese) && $this->users[$pointer]['role'] == $aRole){
							watchdog(WATCHDOG_INFO,"HEY HEY HEY : " . $this->users[$pointer]['user']);
							$assignee = $this->users[$pointer]['user'];
							$used[$aRole][$ta['TA_visual_id']] = $assignee;
							$this->assignments[$task['task_id']] = $assignee;
						}
						
						$pointer = $this->advancePointer($pointer);
					}
					
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee);
					
				}//New to subwf constraint
				else if(isset($aConst['new to subwf'])){
					
					//Get subwf we want to avoid
					$avoid = $ta['TA_visual_id'];
					$avoidArray = array();
					
					//Get all the uid's that have that visual id
					foreach($used[$aRole] as $vid => $uid){
						if($vid == $avoid){
							$avoidArray[] = $uid;
						}
					}
					
					//Continue to advance the pointer until we are pointing at someone we want
					$assignee = null;
					while(!isset($assignee)){
						//$assignee = 0;
						if(!in_array($this->users[$pointer]['user'], $avoidArray) && $this->users[$pointer]['role'] == $aRole){
							$assignee = $this->users[$pointer]['user'];
							$used[$aRole][$ta['TA_visual_id']] = $assignee;
						}
						
						$pointer = $this->advancePointer($pointer);
					}
					
					$this->assignments[$task['task_id']] = $assignee;
					
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee); 
				}//Null constraint
				else{
					
					$assignee = null;
					while(!isset($assignee)){
						//$assignee = 0;
						//watchdog(WATCHDOG_INFO,$this->users[$pointer]['role'] . ' ' . $aRole);
						if($this->users[$pointer]['role'] == $aRole){
							$assignee = $this->users[$pointer]['user'];
							$used[$aRole][$ta['TA_visual_id']] = $this->users[$pointer]['user'];
						}
						
						$pointer = $this->advancePointer($pointer);
					}
					
					$this->assignments[$task['task_id']] = $assignee;
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee);
				}
				
			}

			$startPointer++;
		}
		
		return $this->assignments;
	}
	
	
}

?>