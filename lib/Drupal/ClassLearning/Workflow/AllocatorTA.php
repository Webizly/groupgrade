<?php

namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Exception as AllocatorException,
  Drupal\ClassLearning\Models\TaskActivity,
  Drupal\ClassLearning\Models\WorkflowTask as Task,
  Drupal\ClassLearning\Models\AssignmentActivity,
  Drupal\ClassLearning\Models\WorkflowActivity;

class AllocatorTA{
	
	//key = uid, value = role
	protected $users = array();
	
	//Contains workflow IDs
	protected $workflows = array();
	
	//key = task id, value = uid. returned at the end of allocation
	protected $assignments = array();
	
	//Assignment activity
	protected $aa_id;
	
	public function addUsers($role, $user){
		/*
		foreach($user as $u){
			$this->users[] = array(
				'user' => $u['user_id'],
				'role' => $role,
			);
			watchdog(WATCHDOG_INFO, "USER: " . $role . " " . $u['user_id']);
		}
		*/
		
		$people = array();
		
		foreach($user as $u){
			$people[] = $u['user_id'];
		}
		$this->users[$role]['users'] = $people;
		$this->users[$role]['pointer'] = 0;
	}
	
	public function addWorkflow($wf){
		$this->workflows[] = $wf;
		watchdog(WATCHDOG_INFO, "WF ID: " . $wf);
	}
	
	public function advancePointer(&$pointer, $array){
		$pointer++;
		if($pointer > count($array) - 1){
			$pointer = 0;
		}

	}
	
	public function setAssignmentActivity($a){
		$this->aa_id = $a;
	}
	
	public function allocate(){
		
		//Don't forget to randomize users
		
		$used = array();
		$pointer = 0;
		$loops = 0;
		
		foreach($this->workflows as $workflow){
			unset($used);
			
			//watchdog(WATCHDOG_INFO, "USING WF: " . $workflow);
			
			$tasks = Task::where('workflow_id', '=', $workflow)
			  ->get();
			  
			foreach($tasks as $task){
				//Better get that TA
				$ta = $task['ta_id'];
				
				db_set_active('activity');
				
				$ta = TaskActivity::where('TA_id', '=', $ta)
				  ->first();
				
				db_set_active('default');
				
				//watchdog(WATCHDOG_INFO,"TASK: " . $task['task_id'] . " TA: " . $ta);
				
				$aJson = json_decode($ta['TA_assignee_constraints'],1);
				$aRole = $aJson['role'];
				//watchdog(WATCHDOG_INFO,$aRole);
				//Garbage for now!
				$aTitle = $aJson['title'];
				$aConst = $aJson['constraints'];
				//watchdog(WATCHDOG_INFO,"ACONST: " . $aRole . " " . $aTitle . " " . $aConst);
				
				//Should this even be assigned?
				if($aRole == "nobody"){
					$this->assignments[$task['task_id']] = null;
				}
				else if(isset($aConst['same as'])){
					
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
						if(!in_array($this->users[$aRole]['users'][$this->users[$aRole]['pointer']], $avoidThese)){
							//watchdog(WATCHDOG_INFO,"HEY HEY HEY : " . $this->users[$pointer]['user']);
							$assignee = $this->users[$aRole]['users'][$this->users[$aRole]['pointer']];
							$used[$aRole][$ta['TA_visual_id']] = $assignee;
							$this->assignments[$task['task_id']] = $assignee;
						}
						
						$this->advancePointer($this->users[$aRole]['pointer'],$this->users[$aRole]['users']);
					}
					
					//watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee);
					
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
						if(!in_array($this->users[$aRole]['users'][$this->users[$aRole]['pointer']], $avoidArray)){
							$assignee = $this->users[$aRole]['users'][$this->users[$aRole]['pointer']];
							$used[$aRole][$ta['TA_visual_id']] = $assignee;
						}
						
						$this->advancePointer($this->users[$aRole]['pointer'],$this->users[$aRole]['users']);
					}
					
					$this->assignments[$task['task_id']] = $assignee;
					
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee); 
				}//Null constraint
				else{
					
					$assignee = null;
					while(!isset($assignee)){
						//$assignee = 0;
						//watchdog(WATCHDOG_INFO,$this->users[$pointer]['role'] . ' ' . $aRole)
						$assignee = $this->users[$aRole]['users'][$this->users[$aRole]['pointer']];
						$used[$aRole][$ta['TA_visual_id']] = $assignee;
						
						$this->advancePointer($this->users[$aRole]['pointer'],$this->users[$aRole]['users']);
					}
					
					$this->assignments[$task['task_id']] = $assignee;
					watchdog(WATCHDOG_INFO,"TASK " . $task['task_id'] . " GOES TO " . $assignee);
				}
				
			}
			
			$loops++;
			foreach($this->users as $role => $garbage){
				/*
				$roles['pointer'] = 0;
				for($i = 0; $i < $loops; $i++){
					$this->advancePointer($roles['pointer'],$roles['users']);
					watchdog(WATCHDOG_INFO, $roles['pointer']);
				}
				 * 
				 */
				
				$this->users[$role]['pointer'] = 0;
				for($i = 0; $i < $loops; $i++){
					$this->advancePointer($this->users[$role]['pointer'],$this->users[$role]['users']);
					watchdog(WATCHDOG_INFO, $role . ":" . $this->users[$role]['pointer']);
				}
			}
		}
		
		return $this->assignments;
	}
	
	
}

?>