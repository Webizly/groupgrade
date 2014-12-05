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
	
	$form['tasks'] = array(
    '#type' => 'fieldset',
    '#collapsible' => FALSE,
  );

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
	
	
	return $form;
}
