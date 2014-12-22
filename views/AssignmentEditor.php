<?php

require('aeStuff.php');

class aeTask
{
	private $items = array();
	private $visualID;
	private $type;
	
	function __construct($type,$vID){
		$this->visualID = $vID;
		$this->type = $type;
	}
	
	public function fillWithDefaults(){
		//Fill in with defaults that EVERY task will have.
		$this->items = getDefaults($this->type,$this->visualID);
	}
	
	public function getForm(){
		return $this->items;
	}
	
	public function vID(){
		return $this->visualID;
	}
	
}

function groupgrade_ae($form, &$form_state){
	
	$form = array();
	
	/*
	$form['tasks'] = array(
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
  ); */

	$createProblem = new aeTask('create problem','p1');
	$createProblem->fillWithDefaults();
	$form = array_merge($form,$createProblem->getForm());
	
	$editComment = new aeTask('edit and comment','p11');
	$editComment->fillWithDefaults();
	$form = array_merge($form,$editComment->getForm());
	
	$justComment = new aeTask('comment only','p12');
	$justComment->fillWithDefaults();
	$form = array_merge($form,$justComment->getForm());
	
	/*
	$revise = new aeTask('revise and resubmit','p13');
	$revise->fillWithDefaults();
	$form = array_merge($form,$revise->getForm());
	*/
	
	$grade = new aeTask('grade','p13');
	$grade->fillwithDefaults();
	$form = array_merge($form,$grade->getForm());
	
	$resolveGrades = new aeTask('resolution grader','p14');
	$resolveGrades->fillwithDefaults();
	$form = array_merge($form,$resolveGrades->getForm());
	
	$dispute = new aeTask('dispute','p15');
	$dispute->fillwithDefaults();
	$form = array_merge($form,$dispute->getForm());
	
	$resolveDispute = new aeTask('resolve dispute','p16');
	$resolveDispute->fillwithDefaults();
	$form = array_merge($form,$resolveDispute->getForm());
	
	$form[] = array(
	  '#type' => 'submit',
	  '#value' => 'Submit'
	);
	
	return $form;
}

function groupgrade_ae_submit($form, &$form_state){
	
	$task = form_process_fieldset($form,$form_state);

	foreach($task as $key => $value){
    
	  if(substr($key,0,1) == '#' || strlen($key) < 2 || substr($key,0,1) == 'f')
	    continue;

	  $TA_name = $task[$key]['basic'][$key . '-TA_name']['#value'];
	  drupal_set_message($TA_name);
	  
	  $TA_due = $task[$key]['basic'][$key . '-TA_due']['#value'];
	  $TA_due_value = 0;
	  
	  if($TA_due == 0){
		$TA_due_value = $task[$key]['basic'][$key . '-TA_due_select']['#value'];
		drupal_set_message($TA_due_value . ' days');
	  }
	  else {
		// Date
		$start = $task[$key]['basic'][$key.'-TA_due_date_select']['#value'];		
		foreach (['year', 'month', 'day', 'hour', 'minute'] as $i) :
		    if ($start[$i] == '')
		      return drupal_set_message("Invalid date setting for " . $key . " task",'error');
		    elseif ((int) $start[$i] < 9)
		      $start[$i] = '0'.intval($start[$i]);
		    else
		      $start[$i] = (string) $start[$i];
			
			if ($i == 'year' AND intval($start[$i]) == 0)
      		  $start['year'] = (string) date('Y');
			
		endforeach;		
	
	  $TA_due_value = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
	  drupal_set_message($TA_due_date);
	  }
	  
	  $TA_start_time = $task[$key]['advanced'][$key . '-TA_start_time']['#value'];
	  
	  if($TA_start_time == 0){
	  	$TA_start_time = '1000-01-01 00:00:00';
	  	drupal_set_message("Start when prior task completes");
	  }
	  else{
	  	// Date
		$start = $task[$key]['basic'][$key.'-TA_due_date_select']['#value'];		
		foreach (['year', 'month', 'day', 'hour', 'minute'] as $i) :
		    if ($start[$i] == '')
		      return drupal_set_message("Invalid date setting for " . $key . " task",'error');
		    elseif ((int) $start[$i] < 9)
		      $start[$i] = '0'.intval($start[$i]);
		    else
		      $start[$i] = (string) $start[$i];
			
			if ($i == 'year' AND intval($start[$i]) == 0)
      		  $start['year'] = (string) date('Y');
			
		endforeach;		
	
	    $TA_start_time = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
	    drupal_set_message("Task starts on " . $TA_start_date);
	  }
	  
	  $TA_duration_end = $task[$key]['advanced'][$key . '-TA_at_duration_end']['#value'];
	  drupal_set_message("At duration end: " . $TA_duration_end);
	  
	  $TA_what_if_late = $task[$key]['advanced'][$key . '-TA_what_if_late']['#value'];
	  drupal_set_message("When late: " . $TA_what_if_late);
	  
	  $TA_display_name = $task[$key]['advanced'][$key . '-TA_display_name']['#value'];
	  drupal_set_message("Display name" . $TA_display_name);
	  
	  $TA_description = $task[$key]['advanced'][$key . '-TA_description']['#value'];
	  drupal_set_message("Description: " . $TA_description);
	  
	  $TA_same_problem = $task[$key]['advanced'][$key . '-TA_one_or_seperate']['#value'];
	  drupal_set_message("Everyone gets same problem: " . $TA_same_problem);
	  
	  $TA_who_does = $task[$key]['advanced'][$key . '-TA_assignee_constraints']['#value'];
	  drupal_set_message("Who does task: " . $TA_who_does);
	  
	  $TA_group = $task[$key]['advanced'][$key . '-TA_assignee_constraints_select']['#value'];
	  drupal_set_message("Individual or group: " . $TA_group);
	  
	  $TA_instructions = $task[$key]['template'][$key . '-TA_instructions']['#value'];
	  drupal_set_message("Instructions: " . $TA_instructions);
	  
	  //Rubric goes here, but that can wait...
	  
	  $TA_comments = $task[$key]['supplemental'][$key . '-TA_comments']['#value'];
	  drupal_set_message("Comments: " . $TA_comments);
	  
	  $TA_revise = $task[$key]['supplemental'][$key . '-TA_allow_revisions']['#value'];
	  drupal_set_message("Revise: " . $TA_revise);
	  
	  $TA_grade = $task[$key]['supplemental'][$key . '-TA_allow_grade']['#value'];
	  drupal_set_message("Grade: " . $TA_grade);
	  
	  $TA_dispute = $task[$key]['supplemental'][$key . '-TA_allow_dispute']['#value'];
	  drupal_set_message("Dispute: " . $TA_dispute);
	  
	  $TA_resolve_grades = $task[$key]['supplemental'][$key . '-TA_allow_resolve_grades']['#value'];
	  drupal_set_message("Resolve grades: " . $TA_resolve_grades);
	  
	  $TA_resolve_dispute = $task[$key]['supplemental'][$key . '-TA_allow_resolve_dispute']['#value'];
	  drupal_set_message("Resolve dispute : " . $TA_resolve_dispute);
	  
	  $TA_new_problem = $task[$key]['supplemental'][$key . '-TA_leads_to_new_problem']['#value'];
	  drupal_set_message("New problem: " . $TA_new_problem);
	  
	  $TA_new_solution = $task[$key]['supplemental'][$key . '-TA_leads_to_new_solution']['#value'];
	  drupal_set_message("New solution: " . $TA_new_solution);
	  
	  $due = array();
	  if(!$TA_due)
	    $due['type'] = 'duration';
	  else
	  	$due['type'] = 'date';
	  
	  $due['value'] = $TA_due_value;
	  
	  switch($TA_duration_end){
	  	case 0: $TA_duration_end = 'late'; break;
		case 1: $TA_duration_end = 'complete'; break;
		case 2: $TA_duration_end = 'resolved'; break;
	  };
		
	  switch($TA_what_if_late){
	  	case 0: $TA_what_if_late = 'keep'; break;
		case 1: $TA_what_if_late = 'new_student'; break;
		case 2: $TA_what_if_late = 'instructor'; break;
		case 3: $TA_what_if_late = 'new_group_member'; break;
		case 4: $TA_what_if_late = 'resolved'; break;
	  };
	  
	  switch($TA_comments){
	  	case 0: $TA_comments = 'edit and comment'; break;
		case 1: $TA_comments = 'comment only'; break;
		case 2: $TA_comments = 'no comments'; break;
	  };
	  
	  $resolution = array();
	  $resolution['amount'] = 15;
	  $resolution['type'] = 'points';
	  
	  $rubric = array();
	  $rubric[] = array(
	    'criteria' => 'Completeness',
	    'instructions' => 'Do this!',
	    'default value' => '???',
	    'value' => 'points',
	  );
	  
	  db_set_active('activity');
	  
	  $ta = db_insert('pla_task_activity')
	  ->fields(array(
	    'TA_type' => $task[$key]['basic'][$key . '-TA_type']['#value'],
	    'TA_name' => $TA_name,
	    'TA_due' => json_encode($due),
		'TA_start_time' => $TA_start_time,
		'TA_at_duration_end' => $TA_duration_end,
		'TA_what_if_late' => $TA_what_if_late,
		'TA_display_name' => $TA_display_name,
		'TA_description' => $TA_description,
		'TA_one_or_separate' => $TA_same_problem,
		'TA_assignee_constraints' => '???',
		'TA_function_type' => '???',
		'TA_instructions' => $TA_instructions,
		'TA_rubric' => json_encode($rubric),
		'TA_allow_edit_and_comment' => $TA_comments,
		'TA_allow_revisions' => $TA_revise,
		'TA_allow_grade' => $TA_grade,
		'TA_number_participants' => 2,
		'TA_trigger_resolution_threshold' => json_encode($resolution),
		'TA_allow_dispute' => $TA_dispute,
		'TA_leads_to_new_problem' => $TA_new_problem,
		'TA_leads_to_new_solution' => $TA_new_solution,
		'TA_WA_id' => -1,
		'TA_A_id' => -1,
		'TA_version_history' => -1,
		'TA_refers_to_which_task' => -1,
		'TA_trigger_condition' => '???',
		'TA_next_task' => '???',
		'TA_visual_id' => $key,

	  ))
	  ->execute();
	  
	  db_set_active('default');
	}

	drupal_set_message(t('The form has been submitted.'));

	return;
}
