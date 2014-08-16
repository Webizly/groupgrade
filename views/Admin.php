<?php

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
	  array(
	    'visual_id' => 'P1',
	    'status' => 'complete',
	  ),
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
		'TA_visual_id' => $form['visual_id']['#value'],
	  ))
	  ->execute();
	
	drupal_set_message("Task Activity created");
	
}
