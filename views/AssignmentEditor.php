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
	  
	  if($TA_due == 0){
		$TA_due_select = $task[$key]['basic'][$key . '-TA_due_select']['#value'];
		drupal_set_message($TA_due_select . ' days');
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
	
	  $TA_due_date = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
	  drupal_set_message($TA_due_date);
	  }
	  
	  $TA_start_time = $task[$key]['advanced'][$key . '-TA_start_time']['#value'];
	  
	  if($TA_start_time == 0){
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
	
	    $TA_start_date = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
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
	  
	  $TA_instruction = $task[$key]['template'][$key . '-TA_instructions']['#value'];
	  drupal_set_message("Instructions: " . $TA_instruction);
	  
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
	}

	drupal_set_message(t('The form has been submitted.'));

	return;
}
