<?php
use Drupal\ClassLearning\Models\WorkflowTask as Task,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Common\Accordion;

function groupgrade_tasks_dashboard() {
  if(isset($_SESSION['lti_tool_provider_context_info']['resource_link_title'])){
    return groupgrade_tasks_view_lti('pending');
  } else {
    return groupgrade_tasks_view_specific('pending');
  }
}

function groupgrade_tasks_get_moodle_id () {
	//used for the Drupal user	
	global $user;
	
	//gets the user id from Moodle and store it into a variable
	$moodle_id = $_SESSION['lti_tool_provider_context_info']['user_id'];
	
	//SELECT uid, mid FROM moodlelink_user_id WHERE mid = $moodle_id
	$record = db_select('moodlelink_user_id', 'ml')
		->fields('ml', array('uid', 'mid'))
		->condition('mid', $moodle_id)
		->execute()
		->fetch();

	//if the user has never logged into CLASS from Moodle, it adds Drupal user ID and Moodle user ID to the table
	$moodle_id = $_SESSION['lti_tool_provider_context_info']['user_id'];
  		if ($record == FALSE) {
			$record = new StdClass();
			$record->uid = $user->uid;
			$record->mid = $moodle_id;
		}
  		//INSERT/UPDATE into moodlelink ('uid, 'mid') VALUES ('uid', 'mid')
		$query = db_merge('moodlelink_user_id')
		->key(array('uid' => $record->uid))
		->key(array('mid' => $record->mid))
		->fields((array) $record)
		->execute();
}

function groupgrade_tasks_get_moodle_assignment_record () {
	//gets the assignment id from Moodle and stores it into a variable
	$moodle_assignment_id = $_SESSION['lti_tool_provider_context_info']['resource_link_id'];
	//SELECT maid, matitle FROM moodlelink2 WHERE maid = $moodle_assignment_id
	$record = db_select('moodlelink_assignment_title', 'ml2')
		->fields('ml2', array('maid', 'matitle'))
		->condition('maid', $moodle_assignment_id)
		->execute()
		->fetch();
	
	//gets the assignment id and assignment title from Moodle and stores it into a variable
	$moodle_assignment_title = $_SESSION['lti_tool_provider_context_info']['resource_link_title'];
	
	//if the record doesn't exist, add it to the table
	if ($record == FALSE) {
  		$record = new StdClass();
		$record->maid = $moodle_assignment_id;
		$record->matitle = $moodle_assignment_title;
  	}
  		//INSERT/UPDATE into moodlelink_assignment_title ('maid, 'matitle') VALUES ('maid', 'matitle')
	$query = db_merge('moodlelink_assignment_title')
		->key(array('maid' => $record->maid))
		->key(array('matitle' => $record->matitle))
		->fields((array) $record)
		->execute();
}

function groupgrade_tasks_get_moodle_lti_outcomes_id () {
	//gets the assignment id from Moodle and stores it into a variable
	$moodle_assignment_id = $_SESSION['lti_tool_provider_context_info']['resource_link_id'];
	
	//gets the Moodle user id from the SESSION variable
	$moodle_id = $_SESSION['lti_tool_provider_context_info']['user_id'];
		
	//SELECT lti_tool_provider_outcomes_id FROM lti_tool_provider_outcomes orderby DESC
	$lti_id = db_select('lti_tool_provider_outcomes', 'lti')
		->fields('lti', array('lti_tool_provider_outcomes_id'))
		->orderBy('lti_tool_provider_outcomes_id', 'DESC')
		->execute()
		->fetch();
	
	#krumo($lti_id->lti_tool_provider_outcomes_id);
	
	//SELECT lti_id FROM moodlelink_lti WHERE lti_id = $lti_id->lti_tool_provider_outcomes_id, mid = $moodle_id, maid = $moodle_assignment_id
	$record = db_select('moodlelink_lti', 'ml3')
		->fields('ml3', array('lti_id'))
		->condition('mid', $moodle_id)
		->condition('maid', $moodle_assignment_id)
		->execute()
		->fetch();	
	
	#krumo($record);	
	
	if($record == FALSE) {
		$record = new StdClass();
		$record->mid = $moodle_id;
		$record->maid = $moodle_assignment_id;
		$record->lti_id = $lti_id->lti_tool_provider_outcomes_id;
	
	//INSERT/UPDATE into moodlelink_lti ('uid, 'mid') VALUES ('uid', 'mid')
	$query = db_merge('moodlelink_lti')
		->key(array('mid' => $record->mid))
		->key(array('maid' => $record->maid))
		->key(array('lti_id' => $record->lti_id))
		->fields((array) $record)
		->execute();
	}
}

function groupgrade_tasks_view_lti($specific = '') {
  global $user;
  
  #drupal_set_message("CLASS is under maintenance until morning. Please do not save any work.");
  
  //links the moodle user ID with their user ID in Drupal
  groupgrade_tasks_get_moodle_id();
  
  //stores the moodle assignment ID and the Moodle assignment title into a second table
  groupgrade_tasks_get_moodle_assignment_record();
  
  groupgrade_tasks_get_moodle_lti_outcomes_id();
  
  $tasks = Task::queryByStatus($user->uid, $specific)->get();
  $rows = [];
  $return = '';

  switch($specific)
  {
    case 'pending' :
      $headers = ['Due Date', 'Type', 'Course', 'Assignment'];
      
      $return .= sprintf('<p>%s</p>', t('These are the pending tasks you need to do. Click on a due date to open the task.'));
      if (count($tasks) > 0) : foreach($tasks as $task) :
        $row_t = [];
        $row_t[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), groupgrade_carbon_span($task->forceEndTime()) .
            (($task->status == 'timed out') ? '(late)' : '')
        );

        $row_t[] = $task->humanTask();

        $section = $task->section()->first();
        $course = $section->course()->first();
        $assignment = $task->assignment()->first();
        $semester = $section->semester()->first();

        $row_t[] = sprintf('%s &mdash; %s &mdash; %s', 
          $course->course_name, 
          $section->section_name,
          $semester->semester_name
        );

        $row_t[] = $assignment->assignment_title;

        $rows[] = $row_t;
      endforeach; endif;
      break;

    // All/completed tasks
    default :
      $headers = array('Assignment', 'Task', 'Course', /*'Problem',*/ 'Date Completed');

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $rowt = [];
        $rowt[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), $task->assignment()->first()->assignment_title
        ); 

        $rowt[] = t(ucwords($task->type));

        // Course information
        $section = $task->section()->first();
        $course = $section->course()->select('course_name')->first();
        $semester = $section->semester()->select('semester_name')->first();

        $rowt[] = sprintf('%s &mdash; %s &mdash; %s',
          $course->course_name,
          $section->section_name,
          $semester->semester_name
        );

        $rowt[] = ($task->end == NULL) ? 'n/a' : gg_time_human($task->end);

        $rows[] = $rowt;
      endforeach; endif;
      break;
  }

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => ['width' => '100%'],
    'empty' => 'No tasks found.',
  ));

  return $return;
}

function groupgrade_tasks_view_specific($specific = '') {
  global $user;
  
  $tasks = Task::queryByStatus($user->uid, $specific)->get();
  $rows = [];
  $return = '';

  switch($specific)
  {
    case 'pending' :
      $headers = ['Due Date', 'Type', 'Course', 'Assignment'];
      
      $return .= sprintf('<p>%s</p>', t('These are the pending tasks you need to do. Click on a due date to open the task.'));
      if (count($tasks) > 0) : foreach($tasks as $task) :
        $row_t = [];
        $row_t[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), groupgrade_carbon_span($task->forceEndTime()) .
            (($task->status == 'timed out') ? '(late)' : '')
        );

        if($task->user_history == null)
		  $row_t[] = $task->humanTask();
		else
		  $row_t[] = $task->humanTask() . ' (Extra Credit)';

        $section = $task->section()->first();
        $course = $section->course()->first();
        $assignment = $task->assignment()->first();
        $semester = $section->semester()->first();

        $row_t[] = sprintf('%s &mdash; %s &mdash; %s', 
          $course->course_name, 
          $section->section_name,
          $semester->semester_name
        );

        $row_t[] = $assignment->assignment_title;

        $rows[] = $row_t;
      endforeach; endif;
      break;

    // All/completed tasks
    default :
      $headers = array('Assignment', 'Task', 'Course', /*'Problem',*/ 'Date Completed');

      if (count($tasks) > 0) : foreach($tasks as $task) :
        $rowt = [];
        $rowt[] = sprintf(
          '<a href="%s">%s</a>',
          url('class/task/'.$task->task_id), $task->assignment()->first()->assignment_title
        ); 

		if($task->user_history == null)
		  $rowt[] = $task->humanTask();
		else
		  $rowt[] = $task->humanTask() . ' (Extra Credit)';

        //$rowt[] = t(ucwords($task->type));

        // Course information
        $section = $task->section()->first();
        $course = $section->course()->select('course_name')->first();
        $semester = $section->semester()->select('semester_name')->first();

        $rowt[] = sprintf('%s &mdash; %s &mdash; %s',
          $course->course_name,
          $section->section_name,
          $semester->semester_name
        );

        $rowt[] = ($task->end == NULL) ? 'n/a' : gg_time_human($task->end);

        $rows[] = $rowt;
      endforeach; endif;
      break;
  }

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => ['width' => '100%'],
    'empty' => 'No tasks found.',
  ));

  return $return;
}

function groupgrade_user_grades() {
  global $user;
  $return = '';

  // Retrieve all of their workflows where they are assigned to "create solution"
  $tasks = Task::where('user_id', $user->uid)
    ->whereType('create solution')
    ->get();

  $rows = [];

  foreach ($tasks as $task) :
    $assignment = $task->assignment()->first();
    $asec = $task->assignmentSection()->first();
    $section = $asec->section()->first();
    $course = $section->course()->first();

    $workflow = $task->workflow()->first();
    $grade = (isset($workflow->data['grade'])) ? ((int) $workflow->data['grade']).'%' : 'n/a';
    $rows[] = [
      sprintf('%s %s', $course->course_name, $section->section_name),
      sprintf('<a href="%s">%s</a>', url('class/workflow/'.$task->workflow_id), $assignment->assignment_title),
      $grade
    ];

  endforeach;
  
  $return .= theme('table', array(
    'header' => ['Course', 'Assignment', 'Grade Recieved'],
    'rows' => $rows,
    'attributes' => ['width' => '100%'],
    'empty' => 'No grades recieved.',
  ));
  return $return;
}

/**
 * View a specific task
 *
 * @param int Task ID
 * @param string How to display it (default = everything, overview = Just submitted data, no other info)
 * @param bool View the task with admin permissions
 */
function groupgrade_view_task($task_id, $action = 'display', $admin = FALSE)
{
  global $user;

  if (is_object($task_id)) :
    $task = $task_id;
    $task_id = $task->task_id;
  else :
    $task = Task::find($task_id);
  endif;

  // Permissions
  if ($task == NULL OR (! $admin AND ! in_array($task->status, ['triggered', 'started', 'complete', 'timed out']) ))
    return drupal_not_found();

  if ($task->status !== 'complete' AND (int) $task->user_id !== (int) $user->uid AND ! $admin)
    return drupal_not_found();

  $anon = ((int) $task->user_id !== (int) $user->uid AND ! user_access('administer')) ? TRUE : FALSE;

  // Related Information
  $assignment = $task->assignment()->first();

  $return = '';
  drupal_set_title(t(sprintf('%s: %s', $task->humanTask(), $assignment->assignment_title)));

  if ($action == 'display') :
    $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/default/all'), HTML_BACK_ARROW, t('Back to All Tasks'));

    // Course information
    $section = $task->section()->first();
    $course = $section->course()->first();
    $semester = $section->semester()->first();

    $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s</p>',
      t('Course'),
      $course->course_name,
      $section->section_name,
      $semester->semester_name
    );

    $return .= '<hr />';
    
    $return .= sprintf('<h4>%s</h4>', t('Assignment Description'));
    $return .= sprintf('<p class="summary">%s</p>', nl2br($assignment->assignment_description));
    $return .= '<hr />';
  endif;

  $params = [];
  $params['task'] = $task;
  $params['anon'] = $anon;
  $params['action'] = $action;

  if ($task->type == 'edit problem')
  {
    $params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('create problem')
      ->first();
  } else {
    // Automatically include the edited problem working with
    // This has to be edited with respect to the new edit and approve task.
    $previous = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('edit problem')
      ->first();
	  
	// If not null, save to $params
	if(isset($previous)){
		$params['problem'] = $previous;
	}
	else{
		// No edit task found, it has to be an edit and approve task then.
		$params['problem'] = Task::where('workflow_id', '=', $task->workflow_id)
          ->whereType('edit and approve')
          ->first();
	}
  }
  
  // If this is an edit and approve task...
  if ($task->type == 'edit and approve'){
  	
	// We need to be able to have the history on hand. Does the history even exist?
	// Check for problem in a 'revise and resubmit' task. If it exists, use that instead of create problem
		
	$revise = Task::where('workflow_id', '=', $task->workflow_id)
	  ->whereType('revise and resubmit')
	  ->first();
	
	// So does the task exist? Check for history
	if(isset($revise->data['history']))
	  $params['previous task'] = $revise;
	else{
	  // No history exists, just get create problem then
	  $params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
        ->whereType('create problem')
        ->first();
	}
	
  }
  
  // If this is a revise and resubmit task
  if ($task->type == 'revise and resubmit'){
  	
	//edit and approve HAS to exist somewhere. Get it.
	
	$params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('edit and approve')
      ->first();
  }
  
  if ($task->type == 'grade solution' OR $task->type == 'dispute' OR $task->type == 'resolve dispute' OR $task->type == 'resolution grader')
  {
    $params['solution'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('create solution')
      ->first();
	  
  }

  if ($task->type == 'create solution')
  {
    $params['previous task'] = Task::where('workflow_id', '=', $task->workflow_id)
      ->whereType('edit problem')
      ->first();
  }

  $params['workflow'] = $task->workflow()->first();
  
  if (! $admin)
    $params['edit'] = ( in_array($task->status, ['triggered', 'started', 'timed out']) );
  else
    $params['edit'] = FALSE;

  $form = drupal_get_form('gg_task_'.str_replace(' ', '_', $task->type).'_form', $params);
  $return .= drupal_render($form);
  return $return;
}

/**
 * Impliments a create problem form
 */
function gg_task_create_problem_form($form, &$form_state, $params) {
  $items = [];

  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => '<strong>'.t('Problem').':</strong>',
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => (isset($params['task']->data['problem'])) ? nl2br($params['task']->data['problem']) : '',
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  if(isset($params['task']->settings['optional'])){
  	$items['body'] = [
    '#type' => 'hidden',
    '#required' => true,
    '#value' => "(This problem was a file upload. Please open the file to view the problem.)",
  ];
  }
  else{
	  $items['body'] = [
	    '#type' => 'textarea',
	    '#required' => true,
	    '#default_value' => (isset($params['task']->data['problem'])) ? $params['task']->data['problem'] : '',
	  ];
  }

  if(isset($params['task']->settings['file'])){
  	
	$m = 'You are required to submit a file for this assignment. Please upload one below.';
	if($params['task']->settings['file'] == 'optional')
	  $m = 'File uploading for this task is optional. If you wish to include a file, please upload one below.';
	
  	$items[] = array(
  	  '#markup' => sprintf('<strong style="color:red;">%s</strong>',$m),
	);
	
	$items['file'] = array(
	  '#type' => 'file',
	  '#title' => 'Please upload your file before submitting.',
	);
	
  }

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Problem',
  ];
  return $items;
}

function gg_task_create_problem_form_validate($form, &$form_state) {
	
	$save = ($form_state['clicked_button']['#id'] == 'edit-save');
	$task = $form_state['build_info']['args'][0]['task'];
	
	if($save || !isset($task->settings['file'])){
		//Don't even bother...
		return;
	}
	
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
			form_set_error('file', "The file failed to save. Please notify your instructor right away.");
		}
	}
	else{
		if($task->settings['file'] == 'mandatory')
		  form_set_error('file','No file was uploaded. Please use the upload form to upload your file.');
	}
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_create_problem_form_submit($form, &$form_state) {
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute(['problem' =>  $form['body']['#value']]);
  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
  $task->save();

  if (! $save){
  	if(isset($form_state['storage']['file'])){
  	  $file = $form_state['storage']['file'];
	  $url = $file->uri;
	  $url = str_replace('public://','sites/default/files/',$url);
	  $task->task_file = $url;
  	} 
    $task->complete();
  }
  
  drupal_set_message(sprintf('%s %s', t('Problem'), ($save) ? 'saved. (You must submit this still to complete the task.)' : 'created.'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Impliments a edit problem form
 */
function gg_task_edit_problem_form($form, &$form_state, $params) {
  $problem = $comment = '';
  $problem = $params['previous task']->data['problem'];

  if (! empty($params['task']->data['problem']))
    $problem = $params['task']->data['problem'];

  if (! empty($params['task']->data['comment']))
    $comment = $params['task']->data['comment'];

  $items = [];

  if ($params['action'] == 'display'){
    $items['original problem'] = [
      '#markup' => sprintf('<p><strong>%s:</strong></p><p>%s</p>',
        t('Original Problem'),
        nl2br($params['previous task']->data['problem'])
      )
    ];
	
	if(isset($params['previous task']->task_file)){
	  $items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a><hr>',url($params['previous task']->task_file),t('A file was uploaded with this problem. Click here to view it.')),
	  ];
	  
	}
  }
  
  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Problem')),
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Comments')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (empty($comment)) ? sprintf('<em>%s</em>', t('none')) : nl2br($comment),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#title' => 'Edited Problem',
    '#default_value' => $problem,
  ];

  if(isset($params['task']->settings['file'])){
		$m = 'You are required to submit a file for this assignment. Please upload one below. Because you are editing this problem, your uploaded file will take the place of the problem creator\'s file when others attempt to view it.';
		if($params['task']->settings['file'] == 'optional')
		  $m = 'File uploading for this task is optional. If you wish to include a file, please upload one below. Because you are editing this problem, your uploaded file will take the place of the problem creator\'s file when others attempt to view it.';
		
	  	$items[] = array(
	  	  '#markup' => sprintf('<strong style="color:red;">%s</strong>',$m),
		);
		
		$items['file'] = array(
		  '#type' => 'file',
		  '#title' => 'Please upload your file before submitting.',
		);
	}

  $items['comment'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#title' => 'Editing Comments',
    '#default_value' => $comment,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Edited Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Edited Problem',
  ];
  return $items;
}

function gg_task_edit_problem_form_validate($form, &$form_state) {
	
	$save = ($form_state['clicked_button']['#id'] == 'edit-save');
	$task = $form_state['build_info']['args'][0]['task'];
	
	if($save || !isset($task->settings['file'])){
		//Don't even bother...
		return;
	}
	
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
			form_set_error('file', "The file failed to save. Please notify your instructor right away.");
		}
	}
	else{
		if($task->settings['file'] == 'mandatory')
		  form_set_error('file','No file was uploaded. Please use the upload form to upload your file.');
	}
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_edit_problem_form_submit($form, &$form_state) {
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute([
    'problem' =>  $form['body']['#value'],
    'comment' => $form['comment']['#value'],
  ]);

  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
  $task->save();

  if (! $save){
  	if(isset($form_state['storage']['file'])){
  	  $file = $form_state['storage']['file'];
	  $url = $file->uri;
	  $url = str_replace('public://','sites/default/files/',$url);
	  $task->task_file = $url;
  	} 
    $task->complete();
  }
  
  drupal_set_message(sprintf('Edited problem %s', ($save) ? 'saved. (You must submit this still to complete the task.)' : 'completed.'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Impliments a edit problem form
 */
function gg_task_create_solution_form($form, &$form_state, $params) {
  $problem = (isset($params['task']->data['solution'])) ? $params['task']->data['solution'] : '';
  $items = [];

  $original_problem = Task::whereType('edit problem')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if(!isset($original_problem->task_file)){

	  $original_problem = Task::whereType('create problem')
	    ->where('workflow_id','=',$params['task']->workflow_id)
		->first();
  }

  if ($params['action'] == 'display'){
    $items['original problem'] = [
      '#markup' => '<p><strong>'.t('Problem').':</strong></p><p>'.nl2br($params['previous task']->data['problem']).'</p>'
    ];
	
	if(isset($original_problem->task_file)){
	  $items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a><hr>',url($original_problem->task_file),t('A file was uploaded with this problem. Click here to view it.')),
	  ];
	  
	}
	
  }
  if (! $params['edit']) :
    $items['problem lb'] = [
      '#markup' => '<strong>'.t('Solution').':</strong>',
    ];
    $items['problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items[] = ['#markup' => sprintf('<p><strong>%s</strong></p>', t('Create Solution'))];

  if(isset($params['task']->settings['optional'])){
  	$items['body'] = [
    '#type' => 'hidden',
    '#required' => true,
    '#value' => "(This solution was a file upload. Please open the file to view the solution.)",
  ];
  }
  else{
	  $items['body'] = [
	    '#type' => 'textarea',
	    '#required' => true,
	    '#default_value' => (isset($params['task']->data['solution'])) ? $params['task']->data['solution'] : '',
	  ];
  }
  
  if(isset($params['task']->settings['file'])){
		$m = 'You are required to submit a file for this assignment. Please upload one below.';
		if($params['task']->settings['file'] == 'optional')
		  $m = 'File uploading for this task is optional. If you wish to include a file, please upload one below.';
		
	  	$items[] = array(
	  	  '#markup' => sprintf('<strong style="color:red;">%s</strong>',$m),
		);
		
		$items['file'] = array(
		  '#type' => 'file',
		  '#title' => 'Please upload your file before submitting.',
		);
  }

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Solution For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Solution',
  ];
  return $items;
}

function gg_task_create_solution_form_validate($form, &$form_state) {
	
	$save = ($form_state['clicked_button']['#id'] == 'edit-save');
	$task = $form_state['build_info']['args'][0]['task'];
	
	if($save || !isset($task->settings['file'])){
		//Don't even bother...
		return;
	}
	
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
			form_set_error('file', "The file failed to save. Please notify your instructor right away.");
		}
	}
	else{
		if($task->settings['file'] == 'mandatory')
		  form_set_error('file','No file was uploaded. Please use the upload form to upload your file.');
	}
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_create_solution_form_submit($form, &$form_state) {
  global $user;	
  $task = $form_state['build_info']['args'][0]['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  $task->setDataAttribute(['solution' =>  $form['body']['#value']]);
  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
    $task->save();
  
  if (! $save){
  	if(isset($form_state['storage']['file'])){
  	  $file = $form_state['storage']['file'];
	  $url = $file->uri;
	  $url = str_replace('public://','sites/default/files/',$url);
	  $task->task_file = $url;
  	} 
    $task->complete();
  }
  
  drupal_set_message(sprintf(t('Solution').' %s', ($save) ? 'saved. (You must submit this still to complete the task.)' : 'completed.'));
  
  //gets the task id of the user
  $class_task_id = $task->task_id;
  
  #krumo($class_task_id);
  
  //SELECT workflow_id FROM pla_task WHERE task_id = $class_task_id
  $class_workflow_id = db_select('pla_task', 'pla_t')
	->fields('pla_t', array('workflow_id'))
	->condition('task_id', $class_task_id)
	->execute()
	->fetch();
  
  #krumo($class_workflow_id->workflow_id);
  
 //SELECT assignment_id FROM pla_workflow WHERE workflow id = $class_workflow_id
  $class_assignment_section_id = db_select('pla_workflow', 'pla_w')
  	->fields('pla_w', array('assignment_id'))
	->condition('workflow_id', $class_workflow_id->workflow_id)
	->execute()
	->fetch();
  
  #krumo($class_assignment_section_id->assignment_id);
  
  //SELECT atitle FROM moodlelink3 WHERE asecid = $class_assignment_section_id
  $class_assignment_title = db_select('moodlelink_assignment', 'ml4')
  	->fields('ml4', array('atitle'))
	->condition('asecid', $class_assignment_section_id->assignment_id)
	->execute()
	->fetch();
  
  #krumo($class_assignment_title->atitle);
  
  $class_id = $user->uid;
  	
  //SELECT * FROM moodlelink4 WHERE workflowid = $class_workflow_id AND uid = $class_id
  $record = db_select('moodlelink_workflow', 'ml5')
	->fields('ml5')
	->condition('workflowid', $class_workflow_id->workflow_id)
	->condition('uid', $class_id)
	->execute()
	->fetch();
	
  //if the record doesn't exist, add it to the table
  if ($record == FALSE) {
  	$record = new StdClass();
	$record->workflowid = $class_workflow_id->workflow_id;
	$record->uid = $class_id;
	$record->asecid = $class_assignment_section_id->assignment_id;
	$record->atitle = $class_assignment_title->atitle;
  }
	
  //INSERT/UPDATE into moodlelink4 ('workflowid, 'uid', 'asecid', 'atitle') VALUES ('workflowid', 'uid', 'asecid', 'atitle')
  $query = db_merge('moodlelink_workflow')
	->key(array('workflowid' => $record->workflowid))
	->key(array('uid' => $record->uid))
	->key(array('asecid' => $record->asecid))
	->key(array('atitle' => $record->atitle))
	->fields((array) $record)
	->execute();

  if (! $save)
    return drupal_goto('class');
}

/**
 * Implements a grade solution form
 */
function gg_task_grade_solution_form($form, &$form_state, $params) {
  $problem = $params['problem'];
  $solution = $params['solution'];
  $task = $params['task'];

  $original_problem = Task::whereType('edit problem')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if(!isset($original_problem->task_file)){

	  $original_problem = Task::whereType('create problem')
	    ->where('workflow_id','=',$params['task']->workflow_id)
		->first();
  }

  $items = [];

  // If we aren't editing anything, just viewing the task
  if (! $params['edit']) :
	// For each category of grades...
	foreach($task->data['grades'] as $category => $grade){
		// Print the grade
	    $items[$category . ' lb'] = [
	      '#markup' => '<strong>'.t(ucwords($category) . ' Grades').':</strong>',
	    ];
	    $items[$category . '-grade'] = [
	      '#type' => 'item',
	      '#markup' => (((isset($grade['grade'])) ? $grade['grade'] : '')),
	    ];
		
		//And the justification
		$items[$category . '-justification lb'] = [
      	  '#markup' => '<strong>'.t(ucwords($category) . ' Justification').':</strong>',
    	];
    	$items[$category . '-justification'] = [
      	  '#type' => 'item',
      	  '#markup' => (! isset($grade['justification'])) ? '' : nl2br($grade['justification']),
    	];
		
	}
	
	return $items;
  endif;

  $items['problem'] = [
    '#markup' => '<h4>'.t('Problem').'</h4><p>'.nl2br($problem->data['problem']).'</p>',
  ];
  
  if(isset($original_problem->task_file)){
	  $items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a>',url($original_problem->task_file),t('A file was uploaded with this problem. Click here to view it.')),
	  ];
	  
	}
  
  $items['solution'] = [
    '#markup' => '<hr><h4>'.t('Solution').'</h4><p>'.nl2br($solution->data['solution']).'</p>',
  ];

  if(isset($params['solution']->task_file)){
  	$items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a>',url($params['solution']->task_file),t('A file was uploaded with this solution. Click here to view it.')),
	  ];
  }

  $items[] = ['#markup' => sprintf('<hr><h4>%s: %s</h4>', t('Current Task'), t($params['task']->humanTask()))];

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  
  //Set up another for each loop, this time for fields
  foreach($task->data['grades'] as $category => $g){
  	
	  //ADDITIONAL INSTRUCTIONS
	  if(isset($g['additional-instructions'])){
	  	
		$items[$category . '-title'] = [
		  '#type' => 'item',
		  '#markup' => '<strong><h2>' . ucfirst($category) . '</h2></strong>',
		];
		
		$items[$category . '-ai-fieldset'] = [
		  '#type' => 'fieldset',
		  '#title' => 'How to Grade',
		  '#collapsible' => true,
		  '#collapsed' => true,
		  '#prefix' => '<div style="margin-bottom:50px;">',
		  '#suffix' => '</div>',
		];
		
		$items[$category . '-ai-fieldset'][$category . '-additional-instructions'] = [
		  '#type' => 'item',
		  '#markup' => $g['additional-instructions'],
		];
		
	  }
	
	  $items[$category . '-grade'] = [
	    '#type' => 'textfield',
	    '#title' => $g['description'],
	    '#required' => true,
	    '#default_value' => (isset($g['grade'])) ? $g['grade'] : '',
	    '#description' => 'Grade Range: 0 - ' . $g['max'],
	  ];
	
	  $items[$category . '-justification'] = [
	    '#type' => 'textarea',
	    '#title' => 'Justify your grade',
	    '#required' => true,
	    '#default_value' => (isset($g['justification'])) ? $g['justification'] : '',
	  ];
	  
	  $items[$category . '-end-divider'] = [
	    '#type' => 'item',
	    '#markup' => '<hr>',
	  ];
	
  }
  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Grade For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Grade',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_grade_solution_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  // For each grade category...
  
  foreach ($task->data['grades'] as $category => $grade) :
	if(is_numeric($form[$category . '-grade']['#value']) == false)
	  return drupal_set_message(t('Please only input number grades.'), 'error');
  	$form[$category . '-grade']['#value'] = (int) $form[$category . '-grade']['#value'];
	
	// Is this bad data?
	if ($form[$category . '-grade']['#value'] !== abs($form[$category . '-grade']['#value'])
      OR $form[$category . '-grade']['#value'] < 0 OR $form[$category . '-grade']['#value'] > $grade['max']) :
        return drupal_set_message(t('Invalid grade: ' . $form[$category . '-grade']['#value']), 'error');
    endif;
	
	// It's good. Save.
	$grade['grade'] = $form[$category . '-grade']['#value'];
	$grade['justification'] = $form[$category . '-justification']['#value'];
	
	$task->setGrades($category,$grade);
	
  endforeach;
  
  // Did we hit the save button or the submit button?
  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );

  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
  $task->save();

  if (! $save)
    $task->complete();
  
  drupal_set_message(sprintf(t('Grade').' %s', ($save) ? 'saved. (You must submit this still to complete the task.)' : 'submitted.'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Dispute
 */
function gg_task_dispute_form($form, &$form_state, $params)
{
  $items = [];
  $task = $params['task'];
  $workflow = $params['workflow'];
  $grades = Task::whereType('grade solution')
    ->where('workflow_id', '=', $task->workflow_id)
    ->get();

  $original_problem = Task::whereType('edit problem')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if(!isset($original_problem->task_file)){

	  $original_problem = Task::whereType('create problem')
	    ->where('workflow_id','=',$params['task']->workflow_id)
		->first();
  }

  $solution = Task::whereType('create solution')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if (! $params['edit']) :
    $items[] = [
      '#markup' => sprintf('<p>%s <strong>%s</strong>.</p>',
        t('The total grade was'),
        (($task->data['value']) ? 'disputed' : 'not disputed')
      )
    ];

    // It was disputed, show the proposed grade and justification
    if ($task->data['value']) :
      foreach ($grades as $grade) :
		  foreach ($grade->data['grades'] as $aspect => $junk) :
	        $g = (isset($task->data['proposed-'.$aspect.'-grade'])) ? $task->data['proposed-'.$aspect.'-grade'] : '';
	
	        $items['proposed-'.$aspect.'-grade'] = [
	          '#markup' => '<h5>Proposed '.ucfirst($aspect).' Grade: '.$g.'</h5>'
	        ];
	
	        $items['proposed-'.$aspect] = [
	          '#markup' => '<h5>Proposed '.ucfirst($aspect).' Justification:</h5> <p>'
	          .((isset($task->data['proposed-'.$aspect])) ? nl2br($task->data['proposed-'.$aspect]) : '').'</p>',
	        ];
		  endforeach;

      endforeach;
      $items['justice lb'] = [
        '#markup' => '<p><strong>'.t('Grade Justification').':</strong></p>',
      ];
      $items['justice'] = [
        '#type' => 'item',
        '#markup' => '<p>'.nl2br($task->data['justification']).'</p>',
      ];
    endif;
    return $items;
  endif;

  $a = new Accordion('dispute-'.$task->task_id);

  $f = '';

  if(isset($original_problem->task_file)){
	  $f = sprintf('<br><a href="%s" style="font-weight:bold;">%s</a>',url($original_problem->task_file),t('A file was uploaded with this problem. Click here to view it.'));
  }
  
  $sf = '';

  if(isset($solution->task_file)){
	  $sf = sprintf('<br><a href="%s" style="font-weight:bold;">%s</a>',url($solution->task_file),t('A file was uploaded with this solution. Click here to view it.'));
  }

  // Problem for the Workflow
  $a->addGroup('Problem', 'problem-'.$task->task_id, sprintf('<h4>%s:</h4><p>%s %s</p>',
    t('Problem'),
    nl2br($params['problem']->data['problem']),$f
  ), true);

  // Solution for the Workflow
  $a->addGroup('Solution', 'solution-'.$task->task_id, sprintf('<h4>%s:</h4><p>%s %s</p><hr />',
    t('Solution'),
    nl2br($params['solution']->data['solution']),$sf
  ), true);

  $graderCount = 1;

  if (count($grades) > 0) : foreach ($grades as $grade) : 
	
	$c = '';
	
  	foreach ($grade->data['grades'] as $category => $g) :
	
      $c .= '<h4>'.t('Grade '.ucfirst($category)).': '.((isset($g['grade'])) ? $g['grade'] : '').'</h4>';

      if (isset($g['justification']))
        $c .= '<p>'.nl2br($g['justification']).'</p>';

	endforeach; 
	
	$a->addGroup('Grader #'.$graderCount, 'grade-'.$graderCount, $c);
	$graderCount++;
	
  endforeach; endif;

  // Resolution Grader
  $resolutionGrader = Task::whereType('resolution grader')
    ->where('workflow_id', '=', $task->workflow_id)
    ->whereStatus('complete')
    ->first();

  if ($resolutionGrader) :
    
	$c = '';
	
    foreach($grades[0]->data['grades'] as $category => $g) :
      $c .= '<h4>'.t('Grade '.ucfirst($category)).': '.(isset($resolutionGrader->data[$category . '-grade']) ? $resolutionGrader->data[$category . '-grade'] : '').'</h4>';

      if (isset($resolutionGrader->data[$category]))
        $c .= '<p>'.nl2br($resolutionGrader->data[$category]).'</p>';
	endforeach;

	  $c .= t('<h4>Justification</h4>' . (isset($resolutionGrader->data['comment']) ? $resolutionGrader->data['comment'] : '').'</h4>');

    $a->addGroup('Resolution Grader', 'grade-'.$resolutionGrader->task_id, $c);
  endif;

  // Resolved Grade
  // It's unknown at the moment where this grade will be received from. Leave it like this for now.
  $c = '';
  $c .= sprintf('<h4>%s: %d%%</h4>', t('Grade Recieved'), $workflow->data['grade']);
  $a->addGroup('Resolved Grade', $task->task_id.'-resolved-grade', $c, true);

  // Add accordion to form
  $items[] = ['#markup' => $a];
  $items[] = ['#markup' => sprintf('<h5>%s</h5>', t('Current Task: Decide Whether to Dispute'))];

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items['no-dispute'] = [
    '#type' => 'submit',
    '#value' => 'Do Not Dispute',
  ];

  $items[] = [
    '#markup' => sprintf('<hr /><p>%s</p>', t('Complete the following only if you are going to dispute.'))
  ];
  
  foreach ($grades as $grade) : foreach ($grade->data['grades'] as $category => $g) :
	
	  //ADDITIONAL INSTRUCTIONS
	  if(isset($g['additional-instructions'])){
	  	
		$items[$category . '-title'] = [
		  '#type' => 'item',
		  '#markup' => '<strong><h2>' . ucfirst($category) . '</h2></strong>',
		];
		
		$items[$category . '-ai-fieldset'] = [
		  '#type' => 'fieldset',
		  '#title' => 'How to Grade',
		  '#collapsible' => true,
		  '#collapsed' => true,
		  '#prefix' => '<div style="margin-bottom:50px;">',
		  '#suffix' => '</div>',
		];
		
		$items[$category . '-ai-fieldset'][$category . '-additional-instructions'] = [
		  '#type' => 'item',
		  '#markup' => $g['additional-instructions'],
		];
		
	  }  
	  
    $items['proposed-'.$category.'-grade'] = [
      '#type' => 'textfield',
      '#title' => 'Proposed '.ucfirst($category).' Grade (0-' . $g['max'] . ')',
      '#default_value' => (isset($task->data['proposed-'.$category.'-grade'])) ? $task->data['proposed-'.$category.'-grade'] : '',
    ];

    $items['proposed-'.$category] = [
      '#type' => 'textarea',
      '#title' => 'Proposed '.ucfirst($category).' Justification',
      '#default_value' => (isset($task->data['proposed-'.$category])) ? $task->data['proposed-'.$category] : '',
    ];
  endforeach; endforeach;

  $items[] = ['#markup' => '<h3>Justify your dispute</h3>'];

  $items['justification'] = [
    '#type' => 'textarea',
    '#title' => 'Explain fully why all prior graders were wrong, and your regrading is correct.',
    '#default_value' => (isset($task->data['justification'])) ? $task->data['justification'] : '',
  ];

  $items['no-dispute-two'] = [
    '#type' => 'submit',
    '#value' => 'Do Not Dispute',
  ];

  $items['dispute-save'] = [
    '#type' => 'submit',
    '#value' => 'Save Dispute',
  ];

  $items['dispute-submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Dispute',
  ];

  return $items;
}

function gg_task_dispute_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];
  // Just get one, we only need the criteria here.
  $grade = Task::whereType('grade solution')
    ->where('workflow_id', '=', $task->workflow_id)
    ->first();

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();

  if (in_array($form_state['clicked_button']['#id'], ['edit-dispute-save', 'edit-dispute-submit']))
    $dispute = true;
  else
    $dispute = false;

  $task->setData('value', $dispute);

  if ($dispute) :
    foreach ($grade->data['grades'] as $aspect => $g) :
	
    // Is anything empty?
    if ($form['proposed-'.$aspect.'-grade']['#value'] == '' || $form['proposed-'.$aspect]['#value'] == '')
		return drupal_set_message(t('Please fill in all fields if you wish to dispute your grade.'), 'error');
	
      if ($form['proposed-'.$aspect.'-grade']['#value'] < 0 || $form['proposed-'.$aspect.'-grade']['#value'] > $g['max']
	  || is_numeric($form['proposed-'.$aspect.'-grade']['#value']) == false)
        return drupal_set_message(t('Incorrect value inserted for ' . $aspect . ' grade.'), 'error');

      // Save the fields
      $form['proposed-'.$aspect.'-grade']['#value'] = (int) $form['proposed-'.$aspect.'-grade']['#value'];

      if (
        $form['proposed-'.$aspect.'-grade']['#value'] !== abs($form['proposed-'.$aspect.'-grade']['#value'])
      OR
        $form['proposed-'.$aspect.'-grade']['#value'] < 0
      OR
        $form['proposed-'.$aspect.'-grade']['#value'] > 100
      )
        return drupal_set_message(t('Invalid grade: '.$form['proposed-'.$aspect.'-grade']['#value']));
      
      $task->setData('proposed-'.$aspect.'-grade', $form['proposed-'.$aspect.'-grade']['#value']);
      $task->setData('proposed-'.$aspect, trim($form['proposed-'.$aspect]['#value']));
    endforeach;

    // Overall Justice.
    if ($form['justification']['#value'] == '')
      return drupal_set_message(t('You didn\'t pass the justification.'), 'error');
    else
      $task->setData('justification', trim($form['justification']['#value']));

    // Are they saving or doing it now
    $submit = ($form_state['clicked_button']['#id'] == 'edit-dispute-submit') ? TRUE : FALSE;

    if ($submit) :
      $task->complete();

      drupal_set_message(t('Your dispute has been submitted.'));
      return drupal_goto('class');
    else :
      $task->status = 'started';
      $task->save();

      drupal_set_message(t('Your dispute has been saved. (You must submit this still to complete the task.)'));
    endif;
  else :
    $task->save();
	$task->complete();	
    drupal_set_message(t('Your decision to not dispute has been submitted.'));
	
	$class_task_id = $task->task_id;
  #drupal_set_message($class_task_id);
  
  //SELECT workflow_id FROM pla_task WHERE task_id = $class_task_id
  $class_workflow_id = db_select('pla_task', 'pla_t')
	->fields('pla_t', array('workflow_id'))
	->condition('task_id', $class_task_id)
	->execute()
	->fetch();

  #drupal_set_message($class_workflow_id->workflow_id);
  	
  //SELECT uid, asecid FROM moodlelink_workflow WHERE workflowid = $class_workflow_id->workflow_id
  $class_id = db_select('moodlelink_workflow', 'ml5')
	->fields('ml5', array('uid', 'asecid'))
	->condition('workflowid', $class_workflow_id->workflow_id)
	->execute()
	->fetch();
  
  #drupal_set_message($class_id->uid);
  #drupal_set_message($class_id->asecid);
  
  //SELECT data FROM pla_workflow WHERE workflow_id = $class_workflow_id->workflow_id
  $newgrade = db_select('pla_workflow', 'pla_w')
  	->fields('pla_w', array('data'))
  	->condition('workflow_id', $class_workflow_id->workflow_id)
	->execute()
	->fetch();
	
  if ($newgrade == TRUE) {
  	$mygrade = json_decode($newgrade->data, true);
  }
  
  $actualgrade = (string) $mygrade['grade'] / 100;
  	
  #drupal_set_message($actualgrade);
  
  //SELECT maid FROM moodlelink_assignment WHERE asecid = $class_id->asecid
  $moodle_assignment_id = db_select('moodlelink_assignment', 'ml4')
  	->fields('ml4', array('maid'))
	->condition('asecid', $class_id->asecid)
	->execute()
	->fetch();
  
  #drupal_set_message($moodle_assignment_id->maid);
  	
  //SELECT mid FROM moodlelink_user_id WHERE uid = $class_id->uid
  $moodle_id = db_select('moodlelink_user_id', 'ml')
  	->fields('ml', array('mid'))
	->condition('uid', $class_id->uid)
	->execute()
	->fetch();
  
  #drupal_set_message($moodle_id->mid);
  
  //SELECT lti_id FROM moodlelink_lti WHERE mid = $moodle_id->mid AND maid = $moodle_assignment_id->maid
  $lti = db_select('moodlelink_lti', 'ml2')
  	->fields('ml2', array('lti_id'))
	->condition('mid', $moodle_id->mid)
	->condition('maid', $moodle_assignment_id->maid)
	->execute()
	->fetch();
  
  #drupal_set_message($lti->lti_id);
   
  /*SELECT lti_tool_provider_outcomes_score FROM lti_tool_provider_outcomes
   *WHERE lti_tool_provider_outcomes_id = $lti->lti_id
   */
  
  $record = db_select('lti_tool_provider_outcomes', 'lti')
  	->fields('lti', array('lti_tool_provider_outcomes_score'))
	->condition('lti_tool_provider_outcomes_id', $lti->lti_id)
	->execute()
	->fetch();
	
  #krumo($record);
  
  //if the record doesn't exist, add it to the table
  if ($record->lti_tool_provider_outcomes_score == '0.0') {
  	$record = new StdClass();
	$record->lti_tool_provider_outcomes_score = $actualgrade;
	
  //UPDATE lti_tool_provider_outcomes SET lti_tool_provider_outcomes_score = $record->lti_tool_provider_outcomes_score WHERE lti_tool_provider_outcomes_id = $lti->lti_id
  $query = db_update('lti_tool_provider_outcomes')
	->fields(array('lti_tool_provider_outcomes_score' => $record->lti_tool_provider_outcomes_score))
	->condition('lti_tool_provider_outcomes_id', $lti->lti_id)
	->execute();
  }
	
  endif;
}

/**
 * Resolve Dispute
 */
function gg_task_resolve_dispute_form($form, &$form_state, $params)
{
  $task = $params['task'];

  $grades = Task::whereType('grade solution')
    ->where('workflow_id', '=', $task->workflow_id)
    ->get();

  $original_problem = Task::whereType('edit problem')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if(!isset($original_problem->task_file)){

	  $original_problem = Task::whereType('create problem')
	    ->where('workflow_id','=',$params['task']->workflow_id)
		->first();
  }

  $solution = Task::whereType('create solution')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  $f = '';

  if(isset($original_problem->task_file)){
	  $f = sprintf('<a href="%s" style="font-weight:bold;">%s</a><hr>',url($original_problem->task_file),t('A file was uploaded with this problem. Click here to view it.'));
  }

  $sf = '';

  if(isset($solution->task_file)){
	  $sf = sprintf('<a href="%s" style="font-weight:bold;">%s</a>',url($solution->task_file),t('A file was uploaded with this solution. Click here to view it.'));
  }

  $items = [];

  if (! $params['edit']) :
    
    $data = [];
    foreach ($grades[0]->data['grades'] as $field => $g){
	    //$data[$field] = (isset($task->data[$field])) ? $task->data[$field] : '';
	
	    $items[$field . '-grade'] = [
	      '#type' => 'item',
	      '#markup' => sprintf('<p><strong>%s:</strong> %d%%', t(ucfirst($field). ' Grade'), $task->data[$field . '-grade'])
	    ];
	
	    $items[$field] = [
	      '#type' => 'item',
	      '#markup' => sprintf('<p><strong>%s:</strong><br /> %s', t(ucfirst($field)), nl2br($task->data[$field]))
	    ];
		
	}
    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items[] = [
    '#markup' => '<h4>'.t('Problem').':</h4>'
    .'<p>'.nl2br($params['problem']->data['problem']).'</p>'
  ];

  $items[] = [
    '#markup' => $f
  ];

  $items[] = [
    '#markup' => '<h4>'.t('Solution').':</h4>'
    .'<p>'.nl2br($params['solution']->data['solution']).'</p>'
  ];

  $items[] = [
    '#markup' => $sf . "<hr>"
  ];

  $a = new Drupal\ClassLearning\Common\Accordion('resolve-dispute');


  $graderCount = 1;
  
  if (count($grades) > 0) : foreach ($grades as $grade) :
    
	$c = '';
    foreach ($grade->data['grades'] as $aspect => $g) :
		
      $c .= '<h4>'.t('Grade '.ucfirst($aspect)).': '.((isset($g['grade'])) ? $g['grade'] : '').'</h4>';

      if (isset($g['justification']))
        $c .= '<p>'.nl2br($g['justification']).'</p>';

    
    endforeach;
	 $a->addGroup('Grader #'.$graderCount, 'grade-'.$graderCount, $c);
	 $graderCount++;
  endforeach; endif;

  // Resolution Grader
  $resolutionGrader = Task::whereType('resolution grader')
    ->where('workflow_id', '=', $task->workflow_id)
    ->first();

  if ($resolutionGrader) :
    
	$c = '';
	
    foreach($grades[0]->data['grades'] as $category => $g) :
      $c .= '<h4>'.t('Grade '.ucfirst($category)).': '.(isset($resolutionGrader->data[$category . '-grade']) ? $resolutionGrader->data[$category . '-grade'] : '').'</h4>';

      if (isset($resolutionGrader->data[$category]))
        $c .= '<p>'.nl2br($resolutionGrader->data[$category]).'</p>';
	endforeach;

	  $c .= t('<h4>Justification</h4>' . (isset($resolutionGrader->data['comment']) ? $resolutionGrader->data['comment'] : '').'</h4>');

    $a->addGroup('Resolution Grader', 'grade-'.$resolutionGrader->task_id, $c);
  endif;

  


  // Resolved Grade (automatically or via resolution grader)
  $c = '';
  $c .= '<h4>'.t('Grade Recieved').': '.$params['workflow']->data['grade'].'%</h4>';
  $a->addGroup('Resolved Grade', 'resolved-grade', $c);

  // Dispute Grader
  $disputeTask = Task::whereType('dispute')
    ->where('workflow_id', '=', $task->workflow_id)
    ->first();

  // Dispute grades aren't set the same way as a grade solution's grades
  // Grade solutions are arrays while every other task stores them as plain
  // values
  if ($disputeTask) :
    $c = '';
    foreach ($grades[0]->data['grades'] as $aspect => $g) :
      $c .= '<h4>'.t('Proposed '.ucfirst($aspect).' Grade').': '.$disputeTask->data['proposed-'.$aspect.'-grade'].'</h4>';
      $c .= '<p>'.nl2br($disputeTask->data['proposed-'.$aspect]).'</p>';
    endforeach;
    
    $c .= '<h4>'.t('Explain fully why all prior graders were wrong, and your regrading is correct').':</h4>';
    $c .= '<p>'.nl2br($disputeTask->data['justification']).'</p>';

    $a->addGroup('Disputer', 'grade-'.$disputeTask->task_id, $c);
  endif;

  // Accordion
  $items[] = [
    '#markup' => $a.'<hr />',
  ];

  $items[] = ['#markup' => sprintf('<h4>%s: %s</h4>', t('Current Task'), t($params['task']->humanTask()))];

  foreach($grades as $grade){
  	foreach($grade->data['grades'] as $category => $g){
  		
	  //ADDITIONAL INSTRUCTIONS
	  if(isset($g['additional-instructions'])){
	  	
		$items[$category . '-title'] = [
		  '#type' => 'item',
		  '#markup' => '<strong><h2>' . ucfirst($category) . '</h2></strong>',
		];
		
		$items[$category . '-ai-fieldset'] = [
		  '#type' => 'fieldset',
		  '#title' => 'How to Grade',
		  '#collapsible' => true,
		  '#collapsed' => true,
		  '#prefix' => '<div style="margin-bottom:50px;">',
		  '#suffix' => '</div>',
		];
		
		$items[$category . '-ai-fieldset'][$category . '-additional-instructions'] = [
		  '#type' => 'item',
		  '#markup' => $g['additional-instructions'],
		];
		
	  }	
		
	  $items[$category . '-grade'] = [
	    '#type' => 'textfield',
	    '#title' => ucfirst($category) . ' Grade (0-' . $g['max'] . ')',
	    '#required' => true,
	    '#default_value' => (isset($task->data[$category . '-grade'])) ? $task->data[$category . '-grade'] : '',
	  ];
	  
	  $items[$category] = [
	    '#type' => 'textarea',
	    '#title' => ucfirst($category) . ' Justification',
	    '#required' => true,
	    '#default_value' => (isset($task->data[$category])) ? $task->data[$category] : '',
	  ];
	
	}
  }
  
  $items[] = ['#markup' => '<h3>Summary</h3>',];
  
  $items['justification'] = [
	    '#type' => 'textarea',
	    '#title' => 'Grade Justification',
	    '#required' => true,
	    '#default_value' => (isset($task->data['justification'])) ? $task->data['justification'] : '',
	  ];
  
  $items['save'] = [
	'#type' => 'submit',
	'#value' => 'Save Grade For Later',
  ];
  $items['submit'] = [
	'#type' => 'submit',
	'#value' => 'Submit Grade',
  ];

  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_resolve_dispute_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
  
  // Just get one of them, we only need to get the criteria
  $grade = Task::whereType('grade solution')
    ->where('workflow_id', '=', $task->workflow_id)
    ->first();
  
  $gradeSum = 0;

  foreach ($grade->data['grades'] as $category => $g) :
	if(is_numeric($form[$category . '-grade']['#value']) == false)
	  return drupal_set_message(t('Please enter only numerical grades.'),'error');
    $form[$category . '-grade']['#value'] = (int) $form[$category . '-grade']['#value'];

    if ($form[$category . '-grade']['#value'] !== abs($form[$category . '-grade']['#value'])
      OR $form[$category . '-grade']['#value'] < 0 OR $form[$category . '-grade']['#value'] > $g['max'])
      return drupal_set_message(t('Invalid grade: '.$category . '-grade'),'error');
    else
      $gradeSum += $form[$category . '-grade']['#value'];
  endforeach;

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );

  $dataFields = ['justification'];
  
  foreach($grade->data['grades'] as $category => $g){
  	$dataFields[] = $category;
	$dataFields[] = $category . '-grade';
  }
  
  foreach ($dataFields as $field)
    $task->setData($field, $form[$field]['#value']);

  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
  $task->save();
  
  drupal_set_message(sprintf('%s %s', t('Grade'), ($save) ? 'saved. (You must submit this still to complete the task.)' : 'submitted.'));

  if (! $save) :
    $task->complete();

    // Save to the workflow
    $params['workflow']->setData('grade', $gradeSum);
    $params['workflow']->save();
    
  $class_task_id = $task->task_id;
  #drupal_set_message($class_task_id);
  
  //SELECT workflow_id FROM pla_task WHERE task_id = $class_task_id
  $class_workflow_id = db_select('pla_task', 'pla_t')
	->fields('pla_t', array('workflow_id'))
	->condition('task_id', $class_task_id)
	->execute()
	->fetch();

  #drupal_set_message($class_workflow_id->workflow_id);
  	
  //SELECT uid, asecid FROM moodlelink4 WHERE workflowid = $class_workflow_id->workflow_id
  $class_id = db_select('moodlelink_workflow', 'ml5')
	->fields('ml5', array('uid', 'asecid'))
	->condition('workflowid', $class_workflow_id->workflow_id)
	->execute()
	->fetch();
  
  #drupal_set_message($class_id->uid);
  #drupal_set_message($class_id->asecid);
  
  //SELECT data FROM pla_workflow WHERE workflow_id = $class_workflow_id->workflow_id
  $newgrade = db_select('pla_workflow', 'pla_w')
  	->fields('pla_w', array('data'))
  	->condition('workflow_id', $class_workflow_id->workflow_id)
	->execute()
	->fetch();
	
  if ($grade == TRUE) {
  	$mygrade = json_decode($newgrade->data, true);
  }
  
  $actualgrade = (string) $mygrade['grade'] / 100;
  	
  #drupal_set_message($actualgrade);
  
  //SELECT maid FROM moodlelink_assignment WHERE asecid = $class_id->asecid
  $moodle_assignment_id = db_select('moodlelink_assignment', 'ml4')
  	->fields('ml4', array('maid'))
	->condition('asecid', $class_id->asecid)
	->execute()
	->fetch();
  
  #drupal_set_message($moodle_assignment_id->maid);
  	
  //SELECT mid FROM moodlelink_user_id WHERE uid = $class_id->uid
  $moodle_id = db_select('moodlelink_user_id', 'ml')
  	->fields('ml', array('mid'))
	->condition('uid', $class_id->uid)
	->execute()
	->fetch();
  
  #drupal_set_message($moodle_id->mid);
  
  //SELECT lti_id FROM moodlelink_lti WHERE mid = $moodle_id->mid AND maid = $moodle_assignment_id->maid
  $lti = db_select('moodlelink_lti', 'ml2')
  	->fields('ml2', array('lti_id'))
	->condition('mid', $moodle_id->mid)
	->condition('maid', $moodle_assignment_id->maid)
	->execute()
	->fetch();
  
  #drupal_set_message($lti->lti_id);
   
  /*SELECT lti_tool_provider_outcomes_score FROM lti_tool_provider_outcomes
   *WHERE lti_tool_provider_outcomes_id = $lti->lti_id
   */
  
  $record = db_select('lti_tool_provider_outcomes', 'lti')
  	->fields('lti', array('lti_tool_provider_outcomes_score'))
	->condition('lti_tool_provider_outcomes_id', $lti->lti_id)
	->execute()
	->fetch();
	
  #krumo($record);
  
  //if the record doesn't exist, add it to the table
  if ($record->lti_tool_provider_outcomes_score == '0.0') {
  	$record = new StdClass();
	$record->lti_tool_provider_outcomes_score = $actualgrade;
	
  //UPDATE lti_tool_provider_outcomes SET lti_tool_provider_outcomes_score = $record->lti_tool_provider_outcomes_score WHERE lti_tool_provider_outcomes_id = $lti->lti_id
  $query = db_update('lti_tool_provider_outcomes')
	->fields(array('lti_tool_provider_outcomes_score' => $record->lti_tool_provider_outcomes_score))
	->condition('lti_tool_provider_outcomes_id', $lti->lti_id)
	->execute();
  }
	
    return drupal_goto('class');
  endif;
}

/*
function gg_task_edit_and_approve_form($form, &$form_state, $params){	
	
  // We could either have a revise and resubmit, or a create problem.
  // If we're coming from a problem just display problem.
  // Otherwise display LATEST problem and then accordion the others.
  $previous;
  $previous = $params['previous task'];
  
  $items = array();
  
  if($previous->type = 'create problem'){
  	
  	// Print out the problem
  	$items['highlight'] = array(
  	  '#markup' => sprintf('<p><strong>%s:</strong></p><p>%s</p><hr />',
        t('Original Problem'),
        nl2br($previous->data['problem']))
	);
	
  }
  
  if($previous->type = 'edit and submit'){
  	
	// We need to print out the history too.
	// Print it out in reverse order (latest to oldest).
	
	$a = new Accordion('History');
	
	// Get a hold of that history array (in reverse!)
	$history = array_reverse($previous->data['history']);
	
	foreach($history as $revision){
		$title = 'revision' . $revision['num'];
		$id = 'r' . $revision['num'];
		$contents = sprintf('<p><strong>%s:</strong></p> %s<br><br><p><strong>%s:</strong><br> %s</p>'
		$a->addGroup($title, $id, $contents, false);
	}
  }

  if ($params['action'] == 'display')
    $items['original problem'] = [
      '#markup' => sprintf('<p><strong>%s:</strong></p><p>%s</p><hr />',
        t('Original Problem'),
        nl2br($params['previous task']->data['problem'])
      )
    ];

  if (! $params['edit']) :
    $items['edited problem lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Problem')),
    ];
    $items['edited problem'] = [
      '#type' => 'item',
      '#markup' => nl2br($problem),
    ];

    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Edited Comments')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (empty($comment)) ? sprintf('<em>%s</em>', t('none')) : nl2br($comment),
    ];

    return $items;
  endif;

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  $items['body'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#title' => 'Edited Problem',
    '#default_value' => $problem,
  ];

  $items['comment'] = [
    '#type' => 'textarea',
    '#required' => true,
    '#title' => 'Editing Comments',
    '#default_value' => $comment,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Edited Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Edited Problem',
  ];
  return $items;
}
*/

/**
 * View a workflow
 * @param int
 */
function gg_view_workflow($workflow_id, $admin = false)
{
  global $user;

  if ($admin AND is_object($workflow_id)) :
    $workflow = $workflow_id;
    $workflow_id = $workflow->workflow_id;
  else :
    $workflow = Workflow::find($workflow_id);
    if ($workflow == NULL) return drupal_not_found();
  endif;

  $tasks = $workflow->tasks();

  if (! $admin)
    $tasks->whereStatus('complete');

  $tasks = $tasks->get();

  $return = '';

  $asec = $workflow->assignmentSection()->first();
  $assignment = $asec->assignment()->first();

  // Back Link
  if (! $admin)
    $return .= sprintf(
      '<p><a href="%s">%s %s</a></p>', url('class/assignments/'.$asec->section_id.'/'.$asec->asec_id), 
      HTML_BACK_ARROW,
      t('Back to Problem Listing')
    );

  // Course/section/semester
  $section = $asec->section()->first();
  $course = $section->course()->first();
  $semester = $section->semester()->first();
  $students = $section->students()->get();

  if (! $admin)
    $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s',
      t('Course'),
      $course->course_name,
      $section->section_name,
      $semester->semester_name
    );

  $return .= '<p class="summary">'.nl2br($assignment->assignment_description).'</p><hr />';

  // Special ADMIN instructions
  if ($admin)
  {
    $return .= sprintf('<p>%s</p>',
      t('Below you see the tasks that are part of this single '
        .'problem from this assignment. Some tasks may not have been completed or even '
        .'started or assigned yet. Notes:'
      ));

    $return .= '<ol>';
    $strings = [
      'Any tasks with a yellow background require your attention in that they have timed out, thus halting the flow of the problem.',
'The status "triggered" means the task has been assigned but not completed yet. It is not necessarily late.',
'The status "not triggered" means the task has been allocated but is not ready to be started yet, because a prior task first needs to complete.',
'The status "task expired (skipped)" means that this task was not needed and was skipped over.',
'You have permission reallocate participants to do a task, but be very careful since this could cause confusion and unintended complications.',
    ];

    foreach ($strings as $s)
      $return .= sprintf('<li>%s</li>', $s);

    $return .= '</ol>';
  }

  // Wrap it all inside an accordion
  $a = new Accordion('workflow-'.$workflow->workflow_id);
  $graderCount = 0;
  if (count($tasks) > 0) :
	
	$manualForm = drupal_get_form('gg_manual_reassign', $section, $students);
    $return .= drupal_render($manualForm);
	
	foreach ($tasks as $task) :
    if (! $admin AND $task->type !== 'grades ok' AND isset($task->settings['internal']) AND $task->settings['internal'])
      continue;

    // Options passed to the accordion
    $options = [];
    $panelContents = '';

    // Add user information if they're an admin
    if ($admin) :
      if ($task->user_id !== NULL) :
        $taskUser = user_load($task->user_id);

        $panelContents .= sprintf('<p><strong>%s:</strong> <a href="%s">%s</a></p>',
          t('Assigned User'),
          url('user/'.$task->user_id),
          ggPrettyName($taskUser)
        );

        $form = drupal_get_form('gg_reassign_task', $task, $section, $students);
        $panelContents .= drupal_render($form);
      endif;

      $panelContents .= sprintf('<p><strong>%s:</strong> %s</p>', t('Status'), t(ucwords($task->status)));
      $panelContents .= '<hr />';

      if ($task->status == 'timed out')
        $options['style'] = 'background-color: yellow;';
    endif;

    if ($task->user_id == $user->uid)
      $panelContents .= sprintf('<p><em>%s</em></p>', t('You performed this task!'));
    // Determine the panel contents
    if (in_array($task->status, ['triggered', 'complete', 'started']))
      {
      	//$panelContents .= groupgrade_view_task($task, 'overview', $admin);
      	// We don't want to print out EXACTLY what appears on the view task screen all the time.
      	
      	switch($task->type){
			case 'create problem' : {
				$panelContents .= "<h4>Problem: </h4>";
				$panelContents .= $task->data['problem'];
				$panelContents .= "<hr><h4>Instructions: </h4>";
				$panelContents .= $task->settings['instructions'];
				break;
			}
			
			case 'edit problem' : {
				$panelContents .= "<h4>Edited Problem: </h4>";
				$panelContents .= $task->data['problem'];
				$panelContents .= "<hr><h4>Comments: </h4>";
				$panelContents .= $task->data['comment'];
				$panelContents .= "<hr><h4>Instructions: </h4>";
				$panelContents .= $task->settings['instructions'];
				break;
			}
			
			case 'create solution' : {
				$panelContents .= "<h4>Solution: </h4>";
				$panelContents .= $task->data['solution'];
				$panelContents .= "<hr><h4>Instructions: </h4>";
				$panelContents .= $task->settings['instructions'];
				break;
			}
			
			case 'grade solution' : {
				foreach($task->data['grades'] as $category => $g){
					$panelContents .= "<h4>" . ucfirst($category) . "</h4>";
					$panelContents .= "<strong>Grade: </strong>" . $g['grade'] . "<br>";
					$panelContents .= "<strong>Justification: </strong>" . $g['justification'] . "<br>";
					if(isset($g['additional-instructions'])){
						$panelContents .= "<strong>Additional Instructions: </strong>" . $g['additional-instructions'] . "<br>";
					}
					$panelContents .= "<hr>";
				}
				$panelContents .= "<h4>Instructions: </h4>";
				$panelContents .= $task->settings['instructions'];
				$graderCount++;
				break;
			}
			
			case 'resolution grader' : {
			}
			
      	}
      }
    elseif ($task->status == 'not triggered')
      $panelContents .= sprintf('<div class="alert">%s</div>', t('Task not triggered.'));
    elseif ($task->status == 'expired')
      $panelContents .= sprintf('<div class="alert">%s</div>', t('Task bypassed.'));
    elseif ($task->status == 'timed out')
      $panelContents .= sprintf('<div class="alert">%s</div>', t('Task timed out (failed to submit).'));

    if($task->type == 'grade solution')
    	$displayGrader = ' ' . $graderCount;
	else
		$displayGrader = '';

    $a->addGroup(t(ucwords($task->type) . $displayGrader), $workflow->workflow_id.'-'.$task->task_id, $panelContents, false, $options);
  endforeach; endif;

  // Append the accordions
  $return .= $a;

  drupal_set_title(sprintf('%s: %s', t('Assignment'), $assignment->assignment_title));

  return $return;
}

function gg_task_grades_ok_form($form, &$form_state, $params) {
  $workflow = $params['task']->workflow()->first();

  $items = [];
  $items['final grade'] = [
    '#markup' => sprintf('<p><strong>%s:</strong> %d', t('Final Grade (Highest grade used)'), $workflow->data['grade']),
  ];
  return $items;
}


/**
 * Impliments a edit problem form
 */
function gg_task_resolution_grader_form($form, &$form_state, $params) {
  $problem = $params['problem'];
  $solution = $params['solution'];
  $task = $params['task'];

  $original_problem = Task::whereType('edit problem')
    ->where('workflow_id','=',$params['task']->workflow_id)
	->first();

  if(!isset($original_problem->task_file)){

	  $original_problem = Task::whereType('create problem')
	    ->where('workflow_id','=',$params['task']->workflow_id)
		->first();
  }

  // Previous Grades
  $grades = Task::where('workflow_id', '=', $task->workflow_id)
    ->whereType('grade solution')
    ->whereStatus('complete')
    ->get();

  $items = [];
  $items['problem'] = [
    '#markup' => '<h4>Problem</h4><p>'.nl2br($problem->data['problem']).'</p>',
  ];
  
  if(isset($original_problem->task_file)){
	  $items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a>',url($original_problem->task_file),t('A file was uploaded with this problem. Click here to view it.')),
	  ];
	  
	}
  
  $items['solution'] = [
    '#markup' => '<hr><h4>Solution</h4><p>'.nl2br($solution->data['solution']).'</p>',
  ];

  if(isset($solution->task_file)){
	  $items[] = [
	    '#markup' => sprintf('<a href="%s" style="font-weight:bold;">%s</a>',url($solution->task_file),t('A file was uploaded with this solution. Click here to view it.')),
	  ];
	  
	}

  $items[] = [
    '#markup' => '<hr><h4>Grades</h4>',
  ];

  $a = new Accordion('previous-graders');
  $graderCount = 1;
  // Previous grades
  if (count($grades) > 0) : foreach ($grades as $grade) : 
	  
	  $c = '';
	  foreach($grade->data['grades'] as $category => $g) :
	
	  
	    $c .= sprintf('<h4>%s: %s</h4>', t(ucfirst($category) . ' Grade'), $g['grade']);
	
	    $c .= sprintf('<p><strong>%s</strong>: %s</p>', t(ucfirst($category)), nl2br($g['justification']));
	
		$c .= '<hr />';
		
	  endforeach;
	  
  $a->addGroup('Grader #' . $graderCount, 'grader-' . $graderCount, $c, false);
  $graderCount++;
  
  endforeach; endif;

  $items[] = [
    '#markup' => $a . '<hr>',
  ];

  if (! $params['edit']) :
/* Old content
    $items['grade lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Grade')),
    ];
    $items['grade'] = [
      '#type' => 'item',
      '#markup' => (isset($task->data['grade'])) ? $task->data['grade'] : '',
    ];

    $items['justice lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Grade Justification')),
    ];
    $items['justice'] = [
      '#type' => 'item',
      '#markup' => (isset($task->data['justification'])) ? nl2br($task->data['justification']) : '',
    ];

    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Why was it resolved it this way?')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (isset($task->data['comment'])) ? nl2br($task->data['comment']) : '',
    ];
*/

	foreach($grades as $grade){
		foreach($grade->data['grades'] as $category => $g){
			$items[$category. '-grade lb'] = [
		      '#markup' => sprintf('<strong>%s:</strong>', t(ucfirst($category) . ' Grade')),
		    ];
		    $items[$category. '-grade'] = [
		      '#type' => 'item',
		      '#markup' => (isset($task->data[$category. '-grade'])) ? $task->data[$category. '-grade'] : '',
		    ];
			//Completeness Justification
			$items[$category. ' lb'] = [
		      '#markup' => sprintf('<strong>%s:</strong>', t(ucfirst($category) . ' Justification')),
		    ];
		    $items[$category] = [
		      '#type' => 'item',
		      '#markup' => (isset($task->data[$category])) ? $task->data[$category] : '',
		    ];
		}
	}


	//Why was it resolved this way?
    $items['comment lb'] = [
      '#markup' => sprintf('<strong>%s:</strong>', t('Why was it resolved it this way?')),
    ];
    $items['comment'] = [
      '#type' => 'item',
      '#markup' => (isset($task->data['comment'])) ? nl2br($task->data['comment']) : '',
    ];
    return $items;
  endif;

  $items[] = ['#markup' => sprintf('<h4>%s: %s</h4>', t('Current Task'), t($params['task']->humanTask()))];

  if (isset($params['task']->settings['instructions']))
    $items[] = [
      '#markup' => sprintf('<p>%s</p>', t($params['task']->settings['instructions']))
    ];

  


foreach($grades as $grade) : foreach($grade->data['grades'] as $category => $g) :
	
	//ADDITIONAL INSTRUCTIONS
	  if(isset($g['additional-instructions'])){
	  	
		$items[$category . '-title'] = [
		  '#type' => 'item',
		  '#markup' => '<strong><h2>' . ucfirst($category) . '</h2></strong>',
		];
		
		$items[$category . '-ai-fieldset'] = [
		  '#type' => 'fieldset',
		  '#title' => 'How to Grade',
		  '#collapsible' => true,
		  '#collapsed' => true,
		  '#prefix' => '<div style="margin-bottom:50px;">',
		  '#suffix' => '</div>',
		];
		
		$items[$category . '-ai-fieldset'][$category . '-additional-instructions'] = [
		  '#type' => 'item',
		  '#markup' => $g['additional-instructions'],
		];
		
	  }
	
	$items[$category . '-grade'] = [
	    '#type' => 'textfield',
	    '#title' => $g['description'],
	    '#required' => true,
	    '#description' => 'Grade Scale: 0 - ' . $g['max'],
	    '#default_value' => (isset($task->data[$category . '-grade'])) ? $task->data[$category . '-grade'] : '',
	  ];
	
	  $items[$category] = [
	    '#type' => 'textarea',
	    '#title' => 'Justify your grade of the solution\'s ' . $category,
	    '#required' => true,
	    '#default_value' => (isset($task->data[$category])) ? $task->data[$category] : '',
	  ];
endforeach; endforeach;

  $items[] = ['#markup' => '<h2>Summary</h2>',];

  $items['comment'] = [
    '#type' => 'textarea',
    '#title' => 'Why did you resolve it this way?',
    '#required' => true,
    '#default_value' => (isset($task->data['comment'])) ? $task->data['comment'] : '',
  ];
  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Grade For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Grade',
  ];
  return $items;
}

/**
 * Callback submit function for class/task/%
 */
function gg_task_resolution_grader_form_submit($form, &$form_state) {
  $params = $form_state['build_info']['args'][0];
  $task = $params['task'];

  if (! $form_state['build_info']['args'][0]['edit'])
    return drupal_not_found();
    
	// Previous Grades
  $grades = Task::where('workflow_id', '=', $task->workflow_id)
    ->whereType('grade solution')
    ->whereStatus('complete')
    ->get();
	
/* Old code
  $grade = (int) $form['grade']['#value'];
  if ($grade !== abs($grade) OR $grade < 0 OR $grade > 100)
    return drupal_set_message(t('Invalid grade: '.$grade));
*/

  //We will be inserting this into task->setDataAttribute
  $data = array();
  
  $total = 0;
  
  foreach($grades[0]->data['grades'] as $category => $g) :
	  
	  $score = $form[$category . '-grade']['#value'];
	  if(is_numeric($score) == FALSE)
	    return drupal_set_message(t('Invalid grade: ' . $score),'error');
	  if($score < 0 || $score > $g['max']){
	    return drupal_set_message(t('Invalid grade: ' . $score),'error');
	  }
	  $total += $score;
	  $data[$category . '-grade'] = $form[$category . '-grade']['#value'];
	  $data[$category] = $form[$category]['#value'];
  endforeach;

  $data['comment'] = $form['comment']['#value'];

  $save = ($form_state['clicked_button']['#id'] == 'edit-save' );
  
  $task->setDataAttribute($data);

  if ($task->status !== 'timed out') $task->status = ($save) ? 'started' : 'complete';
  $task->save();

  if (! $save) :
    $task->complete();

    $workflow = $task->workflow()->first();
    $workflow->setData('grade', $total);
    $workflow->save();
  endif;
  
  drupal_set_message(sprintf('%s %s', t('Grade'), ($save) ? 'saved. (You must submit this still to complete the task.)' : 'submitted.'));

  if (! $save)
    return drupal_goto('class');
}

/**
 * Used only to display to instructors wheater grades were automatically resolved 
 */
function gg_task_resolve_grades_form($form, &$form_state, $params)
{
  $task = $params['task'];

  $items = [];
  $items[] = [
    '#markup' => sprintf('<p>Workflow grades <strong>%s</strong> automatically resolved.</p>',
      ($task->data['value']) ? 'were' : 'were not'
    )
  ];

  return $items;
}

/**
 * Form to handle reassigning a task
 */
function gg_reassign_task($form, &$form_state, $task, $section, $students)
{
  $items = $index = [];

  if (count($students) > 0) : foreach($students as $student) :
    $user = user_load($student->user_id);
    if (! $user) continue;

    $index[$student->user_id] = ggPrettyName($user);
  endforeach; endif;

  $items['user'] = array(
     '#type' => 'select',
     '#title' => t('Reassign Task to User'),
     '#options' => $index,
     '#default_value' => $task->user_id,
 );

  $items['section'] = array(
    '#value' => $section->section_id,
    '#type' => 'hidden'
  );

  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Reassign Task (Will re-start the task)',
  ];

  $items[] = [
    '#markup' => '<hr />'
  ];

  return $items;
}

/**
 * Form Submit to handle reassigning a task
 */
function gg_reassign_task_submit($form, &$form_state)
{
  $task = $form_state['build_info']['args'][0];
  $section = $form_state['build_info']['args'][1];

  $user = (int) $form['user']['#value'];

  if ($user == $task->user_id)
    return drupal_set_message(t('You cannot reassign the same user to the task.'), 'error');

  $update = null;
  if($task->user_history == '')
	$update = array();
  else
  	$update = json_decode($task->user_history,true);
  
    $user_object = user_load($task->user_id);
    $new_user = user_load($user);
	$ar = array();
	$ar['previous_uid'] = $user_object->uid;
	$ar['previous_name'] = $user_object->name;
	$ar['time'] = Carbon\Carbon::now()->toDateTimeString();
	$ar['new_uid'] = $new_user->uid;
	$ar['new_name'] = $new_user->name;
	$update[] = $ar;
	$task->user_history = json_encode($update);

  $task->user_id = $user;
  $task->trigger(true);

  return drupal_set_message('User reassigned and task re-triggered.');
}

function gg_manual_reassign($form, &$form_state, $section, $students)
{
  $items = $index = [];

  if (count($students) > 0) : foreach($students as $student) :
    $user = user_load($student->user_id);
    if (! $user) continue;

    $index[$student->user_id] = ggPrettyName($user);
  endforeach; endif;

  $items['task'] = array(
    '#type' => 'textfield',
    '#title' => 'Manually Reassign Task',
    '#description' => 'Please enter task id',
  );

  $items['user'] = array(
     '#type' => 'select',
     '#title' => t('Reassign Task to User'),
     '#options' => $index,
 );

  $items['section'] = array(
    '#value' => $section->section_id,
    '#type' => 'hidden'
  );
  
  $items['forcetrigger'] = array(
    '#type' => 'checkbox',
    '#title' => 'Force Trigger',
  );

  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Reassign Task (Will re-start the task)',
  ];

  $items[] = [
    '#markup' => '<hr />'
  ];

  return $items;
}

function gg_manual_reassign_submit($form, &$form_state)
{
  $task_id = $form['task']['#value'];

  $task = Task::where('task_id','=',$task_id)
    ->first();
  
  $section = $form_state['build_info']['args'][0];

  $user = (int) $form['user']['#value'];

  if ($user == $task->user_id)
    return drupal_set_message(t('You cannot reassign the same user to the task.'), 'error');

  $update = null;
  if($task->user_history == '')
	$update = array();
  else
  	$update = json_decode($task->user_history,true);
  
    $user_object = user_load($task->user_id);
    $new_user = user_load($user);
	$ar = array();
	$ar['previous_uid'] = $user_object->uid;
	$ar['previous_name'] = $user_object->name;
	$ar['time'] = Carbon\Carbon::now()->toDateTimeString();
	$ar['new_uid'] = $new_user->uid;
	$ar['new_name'] = $new_user->name;
	$update[] = $ar;
	$task->user_history = json_encode($update);

  $task->user_id = $user;
  
  if($form['forcetrigger']['#value'] == 1)
    $task->trigger(true);
  else
  	$task->save();

  return drupal_set_message('User reassigned and task re-triggered.');
}

function groupgrade_retrigger_task_form($form, &$form_state, $task_id, $asec, $section){
	
	drupal_set_title('Re-Open Task');
	
	$task = Task::where('task_id','=',$task_id)
	  ->first();
	
	// Print task details
	
	$items['details'] = array(
	  '#markup' => '
	  Task ID : '.$task->task_id.'<br>
	  Task Type : '.$task->type.'<br>
	  Task Status : '.$task->status.'<br>
	  ',
	);
	
	$items['task'] = array(
	  '#type' => 'hidden',
	  '#value' => $task_id,
	);
	
	$items['section'] = array(
	  '#type' => 'hidden',
	  '#value' => $section,
	);
	
	$items['asec'] = array(
	  '#type' => 'hidden',
	  '#value' => $asec,
	);

	$items['warning'] = array(
	 '#markup' => '<br>Re-opening this task will reset its status so that it may be worked on again.
	   The user responsible for this task will be alerted.<br><br> 
	  Are you sure you wish to re-open this task?<br><br>',
	);
	
	$items['yes'] = array(
	 '#type' => 'submit',
	 '#value' => 'Yes',
	);
	
	$items['no'] = array(
	 '#type' => 'submit',
	 '#value' => 'No',
	);
	
	return $items;
}

function groupgrade_retrigger_task_form_submit($form, &$form_state){
	
	if($form_state['clicked_button']['#value'] == 'Yes')
	  $retrigger = true;
	else
	  $retrigger = false;
	
	if($retrigger){
		$task_id = $form['task']['#value'];
		$task = Task::where('task_id','=',$task_id)
		  ->first();
		  
		$task->trigger(true);
		$task->save();
		
		drupal_set_message("Task " . $task_id . " has been re-opened for the user.");
	}
	else{
		drupal_set_message("Task not re-opened.");
	}
	
	$asec = $form['asec']['#value'];
	$section = $form['section']['#value'];
	
	drupal_goto(sprintf('class/instructor/%d/assignment/%d/view-reassign/table',$section,$asec));
}
/**
 * Reassign to Contingency Tasks
 */
function groupgrade_reassign_to_contig() {
  $pool = $removePool = [];
  foreach ([
    'ydm2', 'krt6', 'vg88', 'md287', 'jmm63', 'jn72', 'mhs38', 'pp389', 'em65', 'gks25','ep39'
  ] as $u)
    $pool[] =  user_load_by_name($u);

  // Let's find the people we're going to remove
  foreach ([
    'arp53', 'rhm9', 'aay5', 'oa45', 'clm2', 'spw5', 'itp3', 'ms695', 'rap48'
  ] as $u)
    $removePool[] = user_load_by_name($u);

  // Get all of their tasks and reassign them randomly
  if ($removePool) : foreach ($removePool as $removeUser) :
    echo "Removing tasks for ".$removeUser->name.PHP_EOL;

    $tasks = Task::where('user_id', $removeUser->uid)
      ->groupBy('workflow_id')
      ->whereIn('status', ['not triggered', 'triggered', 'started', 'timed out','expired'])
      ->get();

    // They're not assigned any tasks that we're going to change
    if (count($tasks) == 0) :
      echo "No tasks to remove!".PHP_EOL;
      continue;
    endif;

    // Go though all assigned tasks
    foreach ($tasks as $task)
    {
    
	  $a = $task->assignment()->first();
	
	  if($a->assignment_id != 68)
	    continue;	
		
      echo "Removing task ".$task->id.PHP_EOL;
      $foundUser = false;
      $i = 0;
      while (! $foundUser) {
        $i++;

        // We cannot continue since we've gone through all the users
        if ($i > count($pool))
          throw new \Exception('Contingency exception: cannot assign user due to unavailable users.');

        $reassignUser = $pool[array_rand($pool)];

        // Let's check if the user we found is in the workflow
        if (Task::where('workflow_id', $task->workflow_id)
          ->where('user_id', $reassignUser->uid)
          ->count() == 0)
          // They're not in the workflow!
          $foundUser = TRUE;
      }

      // Now that we've found the user, let's reassign it
      // We're going to reassign all tasks assigned to this user in the workflow
      
      // Before anything, let's update the user history field.
      $update = null;
	  if($task->user_history == ''){
	  	$update = array();
		$ar = array();
		$ar[] = $removeUser->uid;
		$ar[] = $removeUser->name;
		$ar[] = Carbon\Carbon::now()->toDateTimeString();
		$update[] = $ar;
		$task->user_history = json_encode($update);
	  }
	  else{
	  	$update = json_decode($task->user_history,true);
		$ar = array();
		$ar[] = $removeUser->uid;
		$ar[] = $removeUser->name;
		$ar[] = Carbon\Carbon::now()->toDateTimeString();
		$update[] = $ar;
		$task->user_history = json_encode($update);
	  }
      
      Task::where('user_id', $removeUser->uid)
        ->where('workflow_id', $task->workflow_id)
        ->whereIn('status', ['triggered', 'started', 'timed out', 'expired'])
        ->update([
          'user_id' => $reassignUser->uid,
          'status' => 'triggered',
          'start' => Carbon\Carbon::now()->toDateTimeString(),
          'user_history' => $task->user_history,
         // 'force_end' => $this->timeoutTime()->toDateTimeString()
        ]);
      
      /*
      Task::where('user_id', $removeUser->uid)
        ->where('workflow_id', $task->workflow_id)
        ->whereIn('status', ['triggered', 'started', 'timed out'])
        ->update([
          'user_id' => $reassignUser->uid,
          'status' => 'triggered',
          'start' => Carbon\Carbon::now()->toDateTimeString(),
         // 'force_end' => $this->timeoutTime()->toDateTimeString()
        ]);
	  */
        // Different for non-triggered already
        Task::where('user_id', $removeUser->uid)
        ->where('workflow_id', $task->workflow_id)
        ->where('status', 'not triggered')
        ->update([
          'user_id' => $reassignUser->uid,
          'user_history' => $task->user_history,
        ]);
    }
  endforeach; endif;
  echo PHP_EOL.PHP_EOL."DONE!!!!";
  exit;
}