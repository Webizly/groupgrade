<?php

use Drupal\ClassLearning\Common\Modal,
Drupal\ClassLearning\Models\WorkflowTask as Task,
Drupal\ClassLearning\Models\Workflow;
  
function groupgrade_home(){
	return '
  <head>
  <style type="text/css">
        #div-image {
            float: right;
            border: 2px double maroon;
			padding-left: 10px;
			margin-top: 10px;
			margin-right: 10px;
			margin-left: 10px;
            }

		   
		li.blue {
			color:blue;
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
			}
		li.black {
			color:black;
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
			}
		li.orange {
			color:orange;
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
			}
		li.purple {
			color: purple;
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
			}
		li.green {
			color: green;
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
			}
	
    </style>
    </head>
    
    <body>
    
	<h1>Welcome to CLASS!</h1><hr>
	
    <div id="div-image">
        <p>
        '//<img src="sites/default/files/pictures/process-by-user.jpg">
        .'
        <img src="http://web.njit.edu/~bieber/outgoing/process-by-user.jpg">
        </p>
    </div>
    <div id="div-text">
    </div>
		<h3>
            Collaborative Learning Through Assessment (CLASS)
        </h3>
        <p>
            Traditionally, students only solve problems.   In the CLASS system, students learn so much more by engaging with most stages of an assignment (see right).  
        </p>
        <p>
            Students not only solve problems, but also create them, grade solutions from fellow students and optionally can dispute their grades.  
        </p>
        <p>
            Here\'s the process you\'ll follow.  Everything shows as anonymous:
        </p>
        <p>
            <ul>
				<li class = "blue">
					Each student creates a problem according to the instructions
				</li>	
				<li class = "black">
					The instructor optionally edits the problem to ensure quality
				</li>	
				<li class = "orange">
					Another student solves the problem
				</li>		
				<li class = "blue">
					Two students grade the solution, including the problem creator
				</li>	
				<li class = "green">	
					If the graders disagree, another student resolves the grade
				</li>
				<li class = "orange">
					Optionally, the problem solver can dispute the grade
				</li>
				<li class = "black">
					The instructor resolves any disputes
				</li>
				<li class = "purple">
					Students can see everything their peers have done anonymously
				</li>
            </ul>
            </p>
            <p>
            The instructor can add additional steps to match specific assignments, exams or projects.
            </p>
            <p>
			For more details <a href=' . url('about2') . '>click here</a>.
			</p>
  ';
}

function groupgrade_admin_dash()
{
  return ''; 
}

function groupgrade_about()
{
  return '<br><br>
  <head>
  <style type="text/css">
        #div-image {
            float: right;
            border: 2px double maroon;
			padding-left: 10px;
			margin-top: 10px;
			margin-right: 10px;
			margin-left: 10px;
            }
        li {
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
           }
		   
		li.blue {
			color:blue;
			}
		li.black {
			color:black;
			}
		li.orange {
			color:orange;
			}
		li.purple {
			color: purple;
			}
		li.green {
			color: green;
			}
	
    </style>
    </head>
    
    <body>
    <div id="div-image">
        <p>
        '//<img src="sites/default/files/pictures/process-by-user.jpg">
        .'
        <img src="http://web.njit.edu/~bieber/outgoing/process-by-user.jpg">
        </p>
    </div>
    <div id="div-text">
    </div>
		<h3>
            Collaborative Learning Through Assessment (CLASS)
        </h3>
        <p>
            Traditionally, students only solve problems.   In the CLASS system, students learn so much more by engaging with most stages of an assignment (see right).  
        </p>
        <p>
            Students not only solve problems, but also create them, grade solutions from fellow students and optionally can dispute their grades.  
        </p>
        <p>
            Here\'s the process you\'ll follow.  Everything shows as anonymous:
        </p>
        <p>
            <ul>
				<li class = "blue">
					Each student creates a problem according to the instructions
				</li>	
				<li class = "black">
					The instructor optionally edits the problem to ensure quality
				</li>	
				<li class = "orange">
					Another student solves the problem
				</li>		
				<li class = "blue">
					Two students grade the solution, including the problem creator
				</li>	
				<li class = "green">	
					If the graders disagree, another student resolves the grade
				</li>
				<li class = "orange">
					Optionally, the problem solver can dispute the grade
				</li>
				<li class = "black">
					The instructor resolves any disputes
				</li>
				<li class = "purple">
					Students can see everything their peers have done anonymously
				</li>
            </ul>
            </p>
            <p>
            The instructor can add additional steps to match specific assignments, exams or projects.
            </p>
            <p>
			For more details <a href=' . url('about2') . '>click here</a>.
			</p>
  ';
}

function groupgrade_about2()
{
	return '
	<head>
    <title>About CLASS</title>
    <style type="text/css">
        #div-image {
            float: left;
            border: 2px double maroon;
			padding-right: 4px;
			padding-left: 4px;
			padding-top: 4px;
			padding-bottom: 4px;
%			margin-top: 4px;
			margin-right: 6px;
           }
    </style>
	
    </head>
    
    <body>
    <div id="div-image">
        <p>
        <img src="http://web.njit.edu/~bieber/outgoing/process-with-learning-types.jpg">
        </p>
    </div>
    <div id="div-text">
    </div>
		<h3>
            Collaborative Learning Through Assessment (CLASS) &ndash; Goals and Further Details
        </h3>
		<p> <a href=' . url('home') . '>(return to About CLASS  overview page)</a>
		</p>
        <p>
            CLASS is a framework designed to create learning opportunities and increase student motivation for learning through active participation in the entire Problem Life Cycle (PLC; see left )  
        </p>
        <p>
            Traditionally, students are only engaged during the problem solving stage. However, CLASS incorporates learning approaches such as problem-based learning, feedback, peer-assessment and self-assessment through allowing students to participate in each of the PLC stages.   
        </p>
        <p>
            In short, students not only solve problems, actively engage in creating the problems, grading solutions from fellow students, and optionally disputing grades, in which case they must grade their own solutions with written justifications.
        </p>

        <p>
			CLASS manages the process and frees instructors to focus on mentoring students where most helpful.    Instructors can customize and add additional tasks as appropriate for each activity.  CLASS thus provides a flexible environment for engaging their students in assignments, quizzes and other kinds of activities.
        </p>
        <p>
			For more information contact the NJIT CLASS team at <a href="mailto:bieber@njit.edu">bieber@njit.edu</a>. 
		</p>
    </body>
    ';
}

function task_activity_form($form, &$form_state){
	
	$items['title'] = array(
	  '#markup' => '<p>Use this form to add fake task activity instances</p>',
	);
	
	$items['type'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Type',
	);
	
	$items['name'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Name',
	);
	
	$items['due_date_duration'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Due Date Duration',
	  '#default_value' => 3,
	);
	
	$items['at_duration_end'] = array(
	  '#type' => 'textfield',
	  '#title' => 'At Duration End',
	  '#default_value' => 'late',
	);
	
	$items['what_if_late'] = array(
	  '#type' => 'textfield',
	  '#title' => 'What if Late?',
	  '#default_value' => 'keep',
	);
	
	//Display name?
	
	$items['description'] = array(
	  '#type' => 'textarea',
	  '#title' => 'Description',
	);
	
	//How should I set up assignee constraints?
	
	$items['function_type'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Function type',
	  '#default_value' => 'display',
	);
	
	$items['instructions'] = array(
	  '#type' => 'textarea',
	  '#title' => 'Instructions',
	);
	
	$items['rubric'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Rubric',
	);
	
	//Allow edit + comment?
	
	//Alow comment only?
	
	//Allow revisions?
	
	//Allow grade?
	
	$items['number_of_participants'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Number of Participants?',
	);
	
	//Trigger Resolution Threshold?
	
	//Allow Dispute?
	
	//Leads to new problem?
	
	//Leads to new solution?
	
	$items['visual_id'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Visual ID',
	);
	
	//ID?
	
	//WA ID?
	
	//A ID?
	
	//Version History?
	
	//Refers to which task? Should this be put here?
	
	//Trigger condition?
	
	//Next Task?
	
	$items['task_id'] = array(
	  '#type' => 'textfield',
	  '#title' => 'Task ID',
	);
	
	$items['submit'] = array(
	  '#type' => 'submit',
	  '#value' => 'Submit',
	);
	
	return $items;
}

function task_activity_form_submit($form, &$form_state){
	
	$due = array();
	
	$due['type'] = 'duration';
	$due['value'] = $form['due_date_duration']['#value'];
	
	$aconstr = array();
	$aconstr['role'] = 'student';
	$aconstr['title'] = 'individual';
	$aconstr['constraints'] = null;
	
	$rubric = array();
	$rubric[] = array(
	  'criteria' => 'Completeness',
	  'instructions' => 'Do this!',
	  'default value' => '???',
	  'value' => 'points',
	);
	
	$rubric[] = array(
	  'criteria' => 'Correctness',
	  'instructions' => 'Do this now!',
	  'default value' => '???',
	  'value' => 'points',
	);
	
	$resolution = array();
	$resolution['amount'] = 15;
	$resolution['type'] = 'points';
	
	$trigger = array();
	$trigger[] = array(
	  'task' => 'P1',
	  'status' => 'complete',
	);
	
	
	$ta = db_insert('pla_task_activity')
	  ->fields(array(
	    'TA_type' => $form['type']['#value'],
	    'TA_name' => $form['name']['#value'],
	    'TA_due' => json_encode($due),
		'TA_start_time' => '2014-12-25 00:00:01',
		'TA_at_duration_end' => $form['at_duration_end']['#value'],
		'TA_what_if_late' => $form['what_if_late']['#value'],
		'TA_display_name' => 'Dummy!',
		'TA_description' => $form['description']['#value'],
		'TA_one_or_separate' => 1,
		'TA_assignee_constraints' => json_encode($aconstr),
		'TA_function_type' => $form['function_type']['#value'],
		'TA_instructions' => $form['instructions']['#value'],
		'TA_rubric' => json_encode($rubric),
		'TA_allow_edit_and_comment' => 'comment only',
		'TA_allow_revisions' => 0,
		'TA_allow_grade' => 1,
		'TA_number_participants' => 2,
		'TA_trigger_resolution_threshold' => json_encode($resolution),
		'TA_allow_dispute' => 1,
		'TA_leads_to_new_problem' => 0,
		'TA_leads_to_new_solution' => 0,
		'TA_WA_id' => -1,
		'TA_A_id' => -1,
		'TA_version_history' => -1,
		'TA_refers_to_which_task' => -1,
		'TA_trigger_condition' => json_encode($trigger),
		'TA_next_task' => '???',
		'TA_task_id' => $form['task_id']['#value'],
	  ))
	  ->execute();
	
	drupal_set_message("Task Activity created");
	
}

function secret_function(){
	$return = '';
	
	$m = new Modal("abc");
	$m->setTitle("ABC");
	$m->setBody("Does this work well?");
	$return .= $m->build();
	
	$return .= $m->printLink();
	
	$m = new Modal("123");
	$m->setTitle("123");
	$m->setBody("I hope so...");
	$return .= $m->build();
	
	$return .= $m->printLink();
	
	$m = new Modal("xyz");
	$m->setTitle("XYZ");
	$m->setBody("It better");
	$return .= $m->build();
	
	$return .= $m->printLink();
	
	return $return;
}

function file_test_form($form, &$form_state){
	$return = array();
	
	$return['file'] = array(
	  '#type' => 'file',
	  '#title' => 'Upload',
	);
	
	$return['submit'] = array(
	  '#type' => 'submit',
	  '#value' =>  'Submit',
	  //'#validate' => 'file_test_form_validate',
	);
	
	$return[] = array(
	  '#markup' => sprintf('<br><br><a href="%s">Worms.docx</a>',url('sites/default/files/CLASS/Worms.docx')),
	);
	
	return $return;
}

function file_test_form_validate($form, &$form_state){
	
	drupal_set_message("Validate Function");
	
	$file = file_save_upload('file', array(
	  //'file_validate_is_image' => array(),
	  'file_validate_extensions' => array('docx doc'),
	));
	
	if($file){
		$file->status = 1;
		file_save($file);
		if($file = file_move($file, 'public://CLASS')) {
			$form_state['storage']['file'] = $file;
		}
		else{
			form_set_error('file', "Failed to write the uploaded file to the site's file folder.");
		}
	}
	else{
		form_set_error('file','No file was uploaded');
	}
}

function file_test_form_submit($form, &$form_state){
	
	$file = $form_state['storage']['file'];
	
	return drupal_set_message(sprintf("<a href='%s'>%s</a>",url('sites/default/files/CLASS/' . $file->filename),$file->uri));
}

function fix_these_grades(){
	//This was used to fix the LTI issue where grades would all appear as 0's.
	
	return "Hold it!";
	
	//Get all the tasks
	$tasks = Task::where('task_id','>=',22791)
	->where('task_id','<',23970)
	->where('type','=','create solution')
	->get();
	
	echo "---" . count($tasks) . "---<br>";
	
	//Get the workflow id of the first task and then
	//increment it every iteration.
	$workflow_id = 2674;
	//2555
	$missing = 0;
	
	foreach($tasks as $t){
		//Get the user's id
		$user = $t->user_id;
		$workflow = Workflow::where('workflow_id','=',$workflow_id)
		->first();
		
		$workflow_id++;
		
		if(!isset($workflow->data['grade'])){
			$missing++;
			continue;
		}
		
		$grade = $workflow->data['grade'];
		
		$existingGrade = db_select('lti_tool_provider_outcomes', 'lti')
		  ->fields('lti',array('lti_tool_provider_outcomes_score'))
		  ->condition('lti_tool_provider_outcomes_user_id',$user)
		  ->condition('lti_tool_provider_outcomes_resource_entity_id_fk',26)
		  ->execute()
		  ->fetchAssoc();
		
		echo $user . " " . "EXISTING: " . $existingGrade['lti_tool_provider_outcomes_score'] . " " . "CURRENT: " . ($grade / 100) . "<br>";
		
		if(($grade / 100) > $existingGrade['lti_tool_provider_outcomes_score']){
			db_update('lti_tool_provider_outcomes')
			  ->fields(array('lti_tool_provider_outcomes_score' => $grade / 100))
			  ->condition('lti_tool_provider_outcomes_resource_entity_id_fk',26)
			  ->condition('lti_tool_provider_outcomes_user_id',$user)
			  ->execute();
		}

	}
	
	return "" . $missing;
}

function get_csv(){
	
	return "!!!";
	
	//Get every workflow from 2198 to 2317
	$workflows = Workflow::where('workflow_id','>=',2174)
	  ->where('workflow_id','<=',2197)
	  ->get();
	
	$headers = array(
	'Workflow ID',
	'Problem Creator',
	'Solution Creator',
	'Extra Credit',
	'Grader 1',
	'Grade 1',
	'Grader 2',
	'Grade 2',
	'Disputer',
	'Dispute Grade',
	'Instructor Resolved Grade',
	'Final Grade',
	);
	
	$fn = "CS101Homework6_FALL2014.csv";
	
	$fp = fopen('sites/default/files/' . $fn,'w');
	
	fputcsv($fp,$headers);
	
	foreach($workflows as $wf){
		
		$tasks = Task::where('workflow_id','=',$wf->workflow_id)
		  ->get();
		  
		$csv_entry = array(
			$wf->workflow_id,
		);
		
		foreach($tasks as $t){
			
			$user = user_load($t->user_id);
			
			switch($t->type){
				case 'create problem':
					$csv_entry[] = $user->name;
					break;
				case 'create solution':
					$csv_entry[] = $user->name;
					if(isset($t->user_history))
						$csv_entry[] = "Yes";
					else
						$csv_entry[] = "No";
					break;
				case 'grade solution':
					$csv_entry[] = $user->name;
					$csv_entry[] = $t->data['grades']['Exercise_1-Script_Runs_in_MatLab']['grade'] + $t->data['grades']['Exercise_1-Correct_Results']['grade'] + $t->data['grades']['Exercise_1-Testing_Quality']['grade'] + $t->data['grades']['Exercise_2-Script_Runs_in_MatLab']['grade'] + $t->data['grades']['Exercise_2-Correct_Results']['grade'] + $t->data['grades']['Exercise_2-Testing_Quality']['grade'] + $t->data['grades']['Exercise_3-Script_Runs_in_MatLab']['grade'] + $t->data['grades']['Exercise_3-Correct_Results']['grade'] + $t->data['grades']['Exercise_3-Testing_Quality']['grade'] + $t->data['grades']['Exercise_4-Script_Runs_in_MatLab']['grade'] + $t->data['grades']['Exercise_4-Correct_Results']['grade'] + $t->data['grades']['Exercise_4-Testing_Quality']['grade'];
					break;
				case 'dispute':
					$csv_entry[] = $user->name;
					if(isset($t->data['value'])){
						if($t->data['value'] == true){
							$csv_entry[] = $t->data['proposed-Exercise_1-Script_Runs_in_MatLab-grade'] + $t->data['proposed-Exercise_1-Correct_Results-grade'] + $t->data['proposed-Exercise_1-Testing_Quality-grade'] + $t->data['proposed-Exercise_2-Script_Runs_in_MatLab-grade'] + $t->data['proposed-Exercise_2-Correct_Results-grade'] + $t->data['proposed-Exercise_2-Testing_Quality-grade'] + $t->data['proposed-Exercise_3-Script_Runs_in_MatLab-grade'] + $t->data['proposed-Exercise_3-Correct_Results-grade'] + $t->data['proposed-Exercise_3-Testing_Quality-grade'] + $t->data['proposed-Exercise_4-Script_Runs_in_MatLab-grade'] + $t->data['proposed-Exercise_4-Correct_Results-grade'] + $t->data['proposed-Exercise_4-Testing_Quality-grade'];
						}
						else
							$csv_entry[] = 'No Dispute';
					}
					else {
						$csv_entry[] = 'No Dispute';
					}
					break;
				case 'resolve dispute':
					if(isset($t->data['Exercise_1-Script_Runs_in_MatLab-grade'])){
						$csv_entry[] = $t->data['Exercise_1-Script_Runs_in_MatLab-grade'] + $t->data['Exercise_1-Correct_Results-grade'] + $t->data['Exercise_1-Testing_Quality-grade'] + $t->data['Exercise_2-Script_Runs_in_MatLab-grade'] + $t->data['Exercise_2-Correct_Results-grade'] + $t->data['Exercise_2-Testing_Quality-grade'] + $t->data['Exercise_3-Script_Runs_in_MatLab-grade'] + $t->data['Exercise_3-Correct_Results-grade'] + $t->data['Exercise_3-Testing_Quality-grade'] + $t->data['Exercise_4-Script_Runs_in_MatLab-grade'] + $t->data['Exercise_4-Correct_Results-grade'] + $t->data['Exercise_4-Testing_Quality-grade'];
					}
					else
						$csv_entry[] = 'No resolution';
					break;
			}
			
		}

		if(isset($wf->data['grade']))
			$csv_entry[] = $wf->data['grade'];
			else
			$csv_entry[] = 'No grade';
			
		fputcsv($fp,$csv_entry);
		$csv_entry = null;
		
	}  
	 
	fclose($fp);
	 
	return "<a href = 'sites/default/files/" . $fn . "'>Download</a>";
	
	/*
	//Get every workflow from 1488 to 1541
	$workflows = Workflow::where('workflow_id','>=',2174)
	  ->where('workflow_id','<=',2197)
	  ->get();
	
	$headers = array(
	'Question No.',
	'Original Problem Upload',
	'Edited Problem Upload',
	'Solution URL',
	'Grade 1 URL',
	'Grade 2 URL',
	'Dispute URL',
	'Instructor Resolved Grade',
	'Final Grade',
	);
	
	$fp = fopen('sites/default/files/csfiles-hw6.csv','w');
	
	fputcsv($fp,$headers);
	
	foreach($workflows as $wf){
		
		$tasks = Task::where('workflow_id','=',$wf->workflow_id)
		  ->get();
		  
		$csv_entry = array(
			$wf->workflow_id,
		);
		
		foreach($tasks as $t){
			
			switch($t->type){
				case 'create problem':
					if(isset($t->task_file))
					  $csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/" . $t->task_file;
					else
					  $csv_entry[] = "Not found";
					break;
				case 'edit problem':
					if(isset($t->task_file))
					  $csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/" . $t->task_file;
					else
					  $csv_entry[] = "Not found";
					break;
				case 'create solution':
					$csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/class/task/" . $t->task_id;
					break;
				case 'grade solution':
					$csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/class/task/" . $t->task_id;
					break;
				case 'dispute':
					$csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/class/task/" . $t->task_id;
					break;
				case 'resolve dispute':
					$csv_entry[] = "http://ec2-54-81-177-27.compute-1.amazonaws.com/class/task/" . $t->task_id;
					break;
			}
			
		}

		if(isset($wf->data['grade']))
			$csv_entry[] = $wf->data['grade'];
			else
			$csv_entry[] = 'No grade';
			
		fputcsv($fp,$csv_entry);
		$csv_entry = null;
		
	}  
	 
	 
	fclose($fp);
	 
	return '???';
	 * 
	 */
}

function fix_times(){
	
	$newtimes = array(
		'create_problem' => array(
			'date' => '2014-11-19 23:45:00',
			),
		'edit_problem' => array(
			'date' => '2014-11-22 23:45:00',
			),
		'create_solution' => array(
			'date' => '2014-11-26 23:45:00',
			),
		'grade_solution' => array(
			'date' => '2014-12-03 23:45:00',
			),
		'resolution_grader' => array(
			'date' => '2014-12-06 23:45:00',
			),
		'dispute' => array(
			'date' => '2014-12-09 23:45:00',
			),
		'resolve_dispute' => array(
			'date' => '2014-12-12 23:45:00',
			),
	);
	
	$sernewtimes = serialize($newtimes);
	
	db_update('pla_task_times')
	  ->fields(array('data' => $sernewtimes))
	  ->condition('asec_id',107)
	  ->execute();
  
  
  return "OK";
}


function add_student(){
	
	$wf = new Workflow();
	$wf->type = "one_a";
	$wf->assignment_id = 107;
	$wf->workflow_start = 1;
	$wf->workflow_end = null;
	$wf->data = null;
	$wf->save();
	
	$workflow = db_select('pla_workflow','wf');
	$workflow->fields('wf',array('workflow_id'))
	  ->orderBy('workflow_id','DESC')
	  ->range(0,1);
	  
	$result = $workflow->execute();
	$result = $result->fetchAssoc();
	
	$useThisWF = $result['workflow_id'];
	
	$tasks = Task::where('workflow_id', '=', 2195)
	  ->get();
	
	foreach($tasks as $task){
		$t = new Task();
		$t->workflow_id = $useThisWF;
		if(!isset($task['user_id']))
		  $t->user_id = null;
		else
		  $t->user_id = 0;
		$t->group_id = null;
		$t->type = $task['type'];
		$t->referenced_task = $task['referenced_task'];
		$t->status = 'not triggered';
		$t->start = null;
		$t->end = null;
		$t->force_end = null;
		$t->data = $task['data'];
		$t->settings = $task['settings'];
		$t->user_history = null;
		$t->ta_id = $task['ta_id'];
		$t->task_file = null;
		
		$t->save();
	}
	
	return "OK! " . $useThisWF;  
	  
}

