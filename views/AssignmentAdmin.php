<?php
/**
 * @file
 *
 * Assignment Management
 */

use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Common\Modal;

function groupgrade_assignment_dash() {
  global $user;

  drupal_set_title(t('Assignment Management'));

  $assignments = Assignment::where('user_id', '=', $user->uid)
    ->orderBy('assignment_id', 'desc')
    ->get();

  $return = '';
  $return .= '<h3>Your Assignments</h3>';
  $return .= sprintf('<p><a href="%s">%s</a></p>', url('class/instructor/assignments/new'), t('Create Assignment'));
  $return .= sprintf('<p>%s</p>', t('Select "View" to manage an existing assignment: edit it, assign it to or remove it from a section, change its start date, etc.'));
  
  $rows = array();

  if (count($assignments) > 0) : foreach($assignments as $assignment) :
    $rows[] = array($assignment->assignment_title, $assignment->sections()->count(),
        '<a href="'.url('class/instructor/assignments/'.$assignment->assignment_id).'">View</a>');
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Title', '# of Sections', 'Operations'),
    'empty' => 'No assignments found.',
    'attributes' => array('width' => '100%'),
  ));

  return $return;
}

function groupgrade_create_assignment()
{
  $items = array();
  $items[] = [
    '#markup' => sprintf('<p><a href="%s">%s</a></p>', url('class/instructor/assignments'), t('Back to Assignment Management'))
  ];
  $items['title'] = array(
    '#title' => 'Assignment Title',
    '#type' => 'textfield',
    '#required' => true, 
  );

  //To do: Query the database to return all the types of available usecases.

  $items['usecase'] = array(
    '#type' => 'select',
    '#title' => t("Problem type"),
    '#default_value' => variable_get("one_a", true),
    '#options' => array(
	  'one_a' => t("Usecase 1A"),
	  'special' => t("Special"),
	  ),
  );

  $items['description'] = array(
    '#title' => 'Assignment Instructions to Students',
    '#type' => 'textarea',
    '#required' => true, 
  );

  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create Assignment'),
  );

  return $items;
}

function groupgrade_create_assignment_submit($form, &$form_state)
{
  global $user;

  $title = $form['title']['#value'];
  $description = $form['description']['#value'];
  $usecase = $form['usecase']['#value'];

  $a = new Assignment;
  $a->user_id = $user->uid;
  $a->assignment_title = $title;
  $a->assignment_description = $description;
  $a->assignment_usecase = $usecase;
  $a->save();

  drupal_set_message(sprintf('Assignment "%s" created.  The assignment must be assigned to a section to initiate the workflow.', $a->assignment_title));
  return drupal_goto('class/instructor/assignments/'.$a->assignment_id);
}


function groupgrade_view_assignment($id) {
  global $user;
  $assignment = Assignment::find((int) $id);

  if ($assignment == NULL OR (int) $assignment->user_id !== (int) $user->uid)
    return drupal_not_found();

  drupal_set_title($assignment->assignment_title);

  $sections = $assignment->sections()->get();

  $return = '<p><a href="'.url('class/instructor/assignments').'">'.t('Back to Assignment Management').'</a></p>';
  $return .= '<div class="well">';
    $return .= '<h3>'.$assignment->assignment_title.'</h3>';
    $return .= '<p>'.nl2br($assignment->assignment_description).'</p>';
  $return .= '</div>';


  $rows = array();

  if (count($sections) > 0) : foreach($sections as $section) :

    $rows[] = array($section->section_name, gg_time_human($section->asec_start),
        '<a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/edit-section/'.$section->asec_id).'">'.t('Edit Start Date').'</a>'
        .' &mdash; <a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/remove-section/'.$section->asec_id).'">'.t('Remove Assignment from Section').'</a>'
      );
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Section', 'Start Date', 'Operations'),
    'empty' => t('This assignment has not been assigned to a section yet. Click on the "Assign to Section" tab above to do so.'),
    'attributes' => array('width' => '100%'),
  ));

  return $return;
}

function groupgrade_edit_assignment($form, &$form_state, $id)
{
  global $user;
  $assignment = Assignment::find((int) $id);

  if ($assignment == NULL OR (int) $assignment->user_id !== (int) $user->uid)
    return drupal_not_found();
  drupal_set_title('Edit '.$assignment->assignment_title);
  $items = array();

  $items['null'] = array(
    '#markup' => '<a href="'.url('class/instructor/assignments/'.$id).'">Back to Assignment</a>',
  );

  $items['title'] = array(
    '#title' => 'Assignment Title',
    '#type' => 'textfield',
    '#required' => true, 
    '#default_value' => $assignment->assignment_title,
  );

  $items['description'] = array(
    '#title' => 'Assignment Instructions to Students',
    '#type' => 'textarea',
    '#required' => true, 
    '#default_value' => $assignment->assignment_description,
  );

  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $assignment->assignment_id,
  );

  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update Assignment'),
  );

  $items['revert'] = [
    '#markup' => ' <input class="form-submit" type="reset" value="'.t('Revert to Last Saved').'" />',
  ];

  return $items;
}

function groupgrade_edit_assignment_submit($form, &$form_state)
{
  global $user;
  $id = $form['assignment_id']['#value'];
  $assignment = Assignment::find((int) $id);

  if ($assignment == NULL OR (int) $assignment->user_id !== (int) $user->uid)
    return drupal_not_found();

  $assignment->assignment_title = $form['title']['#value'];
  $assignment->assignment_description = $form['description']['#value'];
  $assignment->save();

  return drupal_set_message(sprintf('Assignment %d updated.', $id));
}

/**
 * Add a section to an assignment
 */
function groupgrade_add_assignment_section($form, &$form_state, $assignment) {
  drupal_set_title(t('Add "@assignment-title" to Section', ['@assignment-title' => Assignment::find($assignment)->assignment_title]));

  //SELECT * FROM moodlelink2
  $records = db_select('moodlelink_assignment_title', 'ml2')
  	->fields('ml2')
    ->execute()
    ->fetchAll();

  //grabs each of the Moodle assignments from the moodlelink2 table, with the key as the assignment id and the title as the assignment title
  foreach($records as $value){
  	$options[$value->maid] = $value->matitle;
  }
  
  #krumo($records);

  global $user;
  $sections_q = User::sectionsWithRole('instructor')
    ->join('course', 'course.course_id', '=', 'section.course_id')
    ->addSelect('course.course_name')
    ->get();

  $sections = array();
  if (count($sections_q) > 0) : foreach($sections_q as $s) :
    $semester = $s->semester()->first();
    $sections[$s->section_id] = sprintf('%s-%s %s', $s->course_name, $s->section_name, $semester->semester_name);
  endforeach; endif;

  $items = array();
  $items['m'] = array(
    '#markup' => '<a href="'.url('class/instructor/assignments/'.$assignment).'">Back to Assignment</a>',
  );
  $items['section'] = array(
    '#type' => 'select',
    '#title' => 'Section',
    '#options' => $sections,
    '#required' => true
  );

  $items['start-now'] = [
    '#type' => 'checkbox',
    '#title' => 'Start Now',
    '#default_value' => 'yes'
  ];

  $items['start info'] = [
    '#markup' => '<p>or specify the start time below</p>',
  ];

  $items['start-date'] = array(
    '#type' => 'date_select',

    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Assignment Start Date'),
    '#date_year_range' => '-0:+2', 

    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
  );

  $items['duration_divider'] = array(
    '#type' => 'item',
    '#markup' => '<hr>',
  );

  $items['task_expire'] = array(
    '#type' => 'fieldset',
    '#title' => 'Task Expiration Dates',
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;">',
    '#suffix' => '</div>',
  );

  $choices = array(0 => t('Set Duration'), 1 => t('Set Date'));

  $types = array(
	'create problem' => 3,
	'edit problem' => 1,
	'create solution' => 3,
	'grade solution' => 3,
	'resolution grader' => 3,
	'dispute' => 3,
	'resolve dispute' => 1,
  );

  foreach($types as $type => $duration){

      $t = str_replace(' ','_',$type);

	  $items['task_expire'][$t] = array(
	    '#type' => 'fieldset',
	    '#title' => ucwords($type),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE,
	    '#prefix' => '<div style="margin-bottom:40px;margin-left:30px;">',
	    '#suffix' => '</div>',
	  );
	  
	  $items['task_expire'][$t][$t . '-radio'] = array(
	    '#type' => 'radios',
	    '#title' => t('Expire After: '),
	    '#default_value' => 0,
	    '#options' => $choices,
	  );
	
	  $items['task_expire'][$t][$t . '-duration'] = array(
	    '#type' => 'textfield',
	    '#title' => '# of Days After Triggering',
	    '#default_value' => $duration,
	    '#states' => array(
		  'visible' => array(
		    ':input[name="' . $t . '-radio"]' => array('value' => 0),
		  ),
		),
	  );
	
	  $items['task_expire'][$t][$t . '-date'] = array(
	    '#type' => 'date_select',
	
	    '#date_format' => 'Y-m-d H:i',
	    '#title' => t('Expiration Date'),
	    '#date_year_range' => '-0:+2', 
	
	    // The minute increment.
	    '#date_increment' => '15',
	    '#default_value' => '',
	    '#states' => array(
		  'visible' => array(
		    ':input[name="' . $t . '-radio"]' => array('value' => 1),
		  ),
		),
	  );

  }
	  
  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $assignment
  );

  $items['moodle'] = array(
  	'#type' => 'checkbox',
  	'#title' => t('Link Assignment to Moodle?'),
  );
  
  $items['moodlelink'] = array(
  	'#type' => 'select',
  	'#title' => t('Select Moodle Assignment'),
  	'#options' => $options,
  	'#states' => array(
  		'visible' => array(
			':input[name = "moodle"]' => array('checked' => TRUE),
		),
	),
  );


  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add Section'),
  );
  
  return $items;
}


function groupgrade_add_assignment_section_submit($form, &$form_state) {
	
  global $user;	
  $section = $form['section']['#value'];
  $start = $form['start-date']['#value'];

  $s = new AssignmentSection;
  $s->assignment_id = (int) $form['assignment_id']['#value'];
  $s->section_id = (int) $section;

  foreach (['year', 'month', 'day', 'hour', 'minute'] as $i) :
    if ($start[$i] == '')
      $start[$i] = '00';
    elseif ((int) $start[$i] < 9)
      $start[$i] = '0'.intval($start[$i]);
    else
      $start[$i] = (string) $start[$i];

  endforeach;

  if ($form['start-now']['#checked'])
    $s->asec_start = Carbon\Carbon::now()->toDateTimeString();
  else
    $s->asec_start = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
  
  if ($s->asec_start == '0000-00-00 00:00:00')
    return drupal_set_message(t('Start time not specified.'), 'error');
  else
    $s->save();

  $sectionObject = Section::find($section);
  $assignment = Assignment::find((int) $form['assignment_id']['#value']);

  // Setting task expiration dates. These are set up as follows:
  // $assignment->assignment_settings['task_expire'][$type][values]
  // When setting expiration dates for tasks, use these instead!
  
  // Code worth keeping track of:
  // --WorkflowTask.php timeoutTime()
  
  $task_expire = array();
  
  $tasks = form_process_fieldset($form['task_expire'],$form_state);
  // We saved an assignment section earlier, let's find the assignment
  // section with the highest id.
  $asec = AssignmentSection::orderBy('asec_id','desc')
	->first();
  
  foreach($tasks as $key => $value){
  	// Is this something we actually set, or is it a fieldset parameter?
	if(substr($key,0,1) == '#')
	  continue;
	
	// So will this expire after a set amount of days or on a certain date?
	
	if($form['task_expire'][$key][$key . '-radio']['#value'] == 0){
		// Duration
		$t = $form['task_expire'][$key][$key . '-duration']['#value'];
		//if(intval($t) <= 0)
		//  return drupal_set_message('Invalid duration specified for ' . $key,'error');
		$task_expire[$key]['duration'] = $t;
	} else {
		// Date
		$start = $form['task_expire'][$key][$key.'-date']['#value'];
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
		
		$task_expire[$key]['date'] = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
	}
  }

  db_insert('pla_task_times')
	  ->fields(array(
	  'asec_id' => $asec->asec_id,
	  'data' => serialize($task_expire),
	  ))
	  ->execute();

//get the current assignment section id
  $class_assignment_section_id = AssignmentSection::orderBy('asec_id', 'DESC')->first();
  #krumo($class_assignment_section_id);
  
  //checks if the instructor wants to change Moodle assignment that is linked to CLASS assignment
  if ($form['moodle']['#checked']) {
  	//grabs the assignment id from the selected Moodle assignment
  	$moodle_assignment_id = $form['moodlelink']['#value'];
	
	#krumo($moodle_assignment_id);
	
	//SELECT matitle FROM moodlelink2 where maid = $moodle_assignment_id
	$moodle_assignment_title = db_select('moodlelink_assignment_title', 'ml2')
		->fields('ml2', array('matitle'))
		->condition('maid', $moodle_assignment_id)
		->execute()
		->fetch();
	
	#krumo($moodle_assignment_title);
	
	//gets the assignment id for the class assignment id
	$class_assignment_id = $class_assignment_section_id->assignment_id;
	
	//SELECT assignment_title FROM pla_assignment where assignment_id = $class_assignment_id
	$class_assignment_title = db_select('pla_assignment', 'pla_a')
		->fields('pla_a', array('assignment_title'))
		->condition('assignment_id', $class_assignment_id)
		->execute()
		->fetch(); 
	
	//gets the user id of the current user in Drupal
  	$class_id = $user->uid;  
	
  	//SELECT * FROM moodlelink3 where maid = $moodle_assignment_id and aid = $class_assignment_id and uid = $class_id and asecid = $class_assignment_section_id
  	$record = db_select('moodlelink_assignment', 'ml4')
  		->fields('ml4')
		->condition('maid', $moodle_assignment_id)
		->condition('aid', $class_assignment_id)
		->condition('uid', $class_id)
		->condition('asecid', $class_assignment_section_id->asec_id)
    	->execute()
    	->fetch();
	
	/*
	 * $record = new array();
	 * $record['maid'] = $moodle_assignment_id;
	 * $abc = $record['maid'];
	 */
	
	//if the record doesn't exist, add it to the table
	if($record == false) {
		$record = new StdClass();
		$record->maid = $moodle_assignment_id;
		$record->matitle = $moodle_assignment_title->matitle;
		$record->aid = $class_assignment_id;
		$record->atitle = $class_assignment_title->assignment_title;
		$record->uid = $class_id;
		$record->asecid = $class_assignment_section_id->asec_id;
	}
	
	#krumo($record);
	
	//INSERT/UPDATE into moodlelink3 ('maid, 'matitle', 'aid', 'atitle', 'uid', 'asecid') VALUES ('maid, 'matitle', 'aid', 'atitle', 'uid', 'asecid')
	
	$query = db_merge('moodlelink_assignment')
		->key(array('maid' => $record->maid))
		->key(array('matitle' => $record->matitle))
		->key(array('aid' => $record->aid))
		->key(array('atitle' => $record->atitle))
		->key(array('uid' => $record->uid))
		->key(array('asecid' => $record->asecid))
		->execute();
  }

  return drupal_set_message(sprintf('Added assignment "%s" to section "%s"', $assignment->assignment_title, $sectionObject->section_name));
}

/**
 * Edit a section on an assignment
 */
function groupgrade_edit_assignment_section($form, &$form_state, $asec)
{
	
	//SELECT * FROM moodlelink2
  $records = db_select('moodlelink_assignment_title', 'ml2')
  	->fields('ml2')
    ->execute()
    ->fetchAll();
  
  //grabs each of the Moodle assignments from the moodlelink2 table, with the key as the assignment id and the title as the assignment title
  if($records != false){
	  foreach($records as $value){
	  	$options[$value->maid] = $value->matitle;
	  }
  }
  
  #krumo($records);
  global $user;
  $section = AssignmentSection::find($asec);
  if ($section == NULL) return drupal_not_found();

  
  $assignment = $section->assignment_id;
  $a = Assignment::find($assignment);

  drupal_set_title(sprintf('%s%s', $a->assignment_title,t(': Section-level Operations')));

  $items = array();
  $items['m'] = array(
    '#markup' => '<a href="'.url('class/instructor/'.$section->section_id).'">Back to Select Assignment (this section)</a>',
  );

/*$items['moodle'] = array(
  	'#type' => 'checkbox',
  	'#title' => t('Link Assignment to Moodle?'),
  );
  
  $items['moodlelink'] = array(
  	'#type' => 'select',
  	'#title' => t('Select Moodle Assignment'),
  	'#options' => $options,
  	'#states' => array(
  		'visible' => array(
			':input[name = "moodle"]' => array('checked' => TRUE),
		),
	),
  );*/

  /*
  $answer = array(0 => t('Yes'), 1 => t('No'));
  
  $items['linkcheck'] = array(
  	'#type' => 'radios',
  	'#title' => t('Did you link this assignment to a Moodle assignment?'),
  	'#options' => $answer,
  	);
  
  $items['moodlechange'] = array(
  	'#type' => 'radios',
  	'#title' => t('Do you want to change the Moodle assignment?'),
  	'#states' => array(
  		'visible' => array(
			':input[name = linkcheck, value = Yes]' => array('checked' => TRUE),
		),
	),
  );
  
  
  $items['moodlechange'] = array(
  	'#type' => 'checkbox',
  	'#title' => t('Do you want to change the Moodle assignment?'),
  	'#states' => array(
  		'visible' => array(
			':input[name = "linkcheck"]' => array('checked' => FALSE),
		),
	),
  );
  
  $items['newmoodlelink'] = array(
  	'#type' => 'select',
  	'#title' => t('Select Moodle Assignment'),
  	'#options' => $options,
  	'#states' => array(
  		'visible' => array(
			':input[name = "moodlechange"]' => array('checked' => TRUE),
		),
	),
  );*/
  
  $theSection = $section->section()->first();
  $course = $theSection->course()->first();
  $semester = $theSection->semester()->first();

  // Information about this course
  $items[] = [
    '#markup' => sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s</p>',
      t('Course'),
      $course->course_name,
      $theSection->section_name,
      $semester->semester_name
    )
  ];
  $items['start-date'] = array(
    '#type' => 'date_select',

    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Assignment Start Date'),
    '#date_year_range' => '-0:+2', 

    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => $section->asec_start,
    '#required' => TRUE,
  );


  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $assignment
  );

  $items['asec_id'] = array(
    '#type' => 'hidden',
    '#value' => $section->asec_id
  );

  $items['moodle'] = array(
  	'#type' => 'checkbox',
  	'#title' => t('Link Assignment to Moodle?'),
  );
  
  $items['moodlelink'] = array(
  	'#type' => 'select',
  	'#title' => t('Select Moodle Assignment'),
  	'#options' => $options,
  	'#states' => array(
  		'visible' => array(
			':input[name = "moodle"]' => array('checked' => TRUE),
		),
	),
  );
  
  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Change Assignment Details'),
  );
  return $items;
}

function groupgrade_edit_assignment_section_submit($form, &$form_state)
{
  global $user;
  $section = (int) $form['asec_id']['#value'];
  $start = $form['start-date']['#value'];

  $section = AssignmentSection::find($section);
  if ($section == NULL) return drupal_not_found();

  $section->asec_start = sprintf('%d-%d-%d %d:%d:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
  $section->save();

  //get the current assignment section id
  $class_assignment_section_id = $section->asec_id;
  #krumo($class_assignment_section_id->asec_id);
  
  //checks if the instructor wants to change Moodle assignment that is linked to CLASS assignment
  if ($form['moodle']['#checked']) {
  	//grabs the assignment id from the selected Moodle assignment
  	$moodle_assignment_id = $form['moodlelink']['#value'];
	
	#krumo($moodle_assignment_id);
	
	//SELECT matitle FROM moodlelink2 where maid = $moodle_assignment_id
	$moodle_assignment_title = db_select('moodlelink_assignment_title', 'ml2')
		->fields('ml2', array('matitle'))
		->condition('maid', $moodle_assignment_id)
		->execute()
		->fetch();
	
	#krumo($moodle_assignment_title->matitle);
	
	//SELECT assignment_id FROM pla_assignment_section WHERE asec_id = $class_assignment_section_id
	$class_assignment_id = db_select('pla_assignment_section', 'pla_a_s')
		->fields('pla_a_s', array('assignment_id'))
		->condition('asec_id', $class_assignment_section_id)
		->execute()
		->fetch(); 
	
	//SELECT assignment_title FROM pla_assignment where assignment_id = $class_assignment_id
	$class_assignment_title = db_select('pla_assignment', 'pla_a')
		->fields('pla_a', array('assignment_title'))
		->condition('assignment_id', $class_assignment_id->assignment_id)
		->execute()
		->fetch(); 
	
	#krumo($class_assignment_id);
	#krumo($class_assignment_title);
	
	//gets the user id of the current user in Drupal
  	$class_id = $user->uid;  
	
  	//SELECT * FROM moodlelink3 where maid = $moodle_assignment_id and aid = $class_assignment_id and uid = $class_id and asecid = $class_assignment_section_id
  	$record = db_select('moodlelink_assignment', 'ml4')
  		->fields('ml4')
		->condition('asecid', $class_assignment_section_id)
    	->execute()
    	->fetch();
	
	/*
	 * $record = new array();
	 * $record['maid'] = $moodle_assignment_id;
	 * $abc = $record['maid'];
	 */
	
	//if the record doesn't exist, add it to the table
	if($record == false) {
		$record = new StdClass();
		$record->maid = $moodle_assignment_id;
		$record->matitle = $moodle_assignment_title->matitle;
		$record->aid = $class_assignment_id->assignment_id;
		$record->atitle = $class_assignment_title->assignment_title;
		$record->uid = $class_id;
		$record->asecid = $class_assignment_section_id;
	
	#krumo($record);
	
	//INSERT/UPDATE into moodlelink3 ('maid, 'matitle', 'aid', 'atitle', 'uid', 'asecid') VALUES ('maid, 'matitle', 'aid', 'atitle', 'uid', 'asecid')
	
	$query = db_merge('moodlelink_assignment')
		->key(array('maid' => $record->maid))
		->key(array('matitle' => $record->matitle))
		->key(array('aid' => $record->aid))
		->key(array('atitle' => $record->atitle))
		->key(array('uid' => $record->uid))
		->key(array('asecid' => $record->asecid))
		->execute();
  	} else {
  		$query = db_update('moodlelink_assignment')
		->fields(array(
		'maid' => $moodle_assignment_id,
		'matitle' => $moodle_assignment_title->matitle,
		'aid' => $class_assignment_id->assignment_id,
	    'atitle' => $class_assignment_title->assignment_title,
		'uid' => $class_id,
		'asecid' => $class_assignment_section_id))
		->condition('asecid', $class_assignment_section_id)
		->execute();
	}
  }

  return drupal_set_message(sprintf('Updated assignment section %d on section %d', $section->asec_id, $section->section_id));
}

/**
 * Remove a section from an assignment
 */
function groupgrade_remove_assignment_section($form, &$form_state, $section, $r)
{
  global $user;
  
  $items = array();
  
  $items['r'] = [
    '#type' => 'hidden',
    '#value' => $r,
  ];
  
  $section = AssignmentSection::find($section);
  
  $a = Assignment::find($section->assignment_id);
  
  drupal_set_title(sprintf('%s: %s', $a->assignment_title, t('Section-level Operations')));
  
  if ($section == NULL) return drupal_not_found();
  
  $theSection = $section->section()->first();
  $course = $theSection->course()->first();
  $semester = $theSection->semester()->first();

  $items['m'] = array(
    '#markup' => '<a href="'.url('class/instructor/'.$section->section_id).'">Back to Select Assignment (this section)</a>',
  );

  // Information about this course
  $items[] = [
    '#markup' => sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s</p>',
      t('Course'),
      $course->course_name,
      $theSection->section_name,
      $semester->semester_name
    )
  ];
  
  /*
  $items['m'] = array(
    '#markup' => '<p><a href="'.url('class/instructor/assignments/' . $section->assignment_id).'">Back to Assignment</a></p>',
  );
  */
  $items[] = [
    '#markup' => '<p>Are you <strong>sure</strong> you want to remove this assignment from the section? It is irreversible!</p>'
  ];

  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $section->assignment_id
  );

  $items['asec_id'] = array(
    '#type' => 'hidden',
    '#value' => $section->asec_id
  );

  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Remove Assignment from Section'),
  );
  
  return $items;
}

function groupgrade_remove_assignment_section_submit($form, &$form_state)
{
  // Remove everything
  $asec_id = $form['asec_id']['#value'];
  $r = $form['r']['#value'];
  $assignment_id = $form['assignment_id']['#value'];

  $workflows = Drupal\ClassLearning\Models\Workflow::where('assignment_id', '=', $asec_id)
    ->get();

  $asec = Drupal\ClassLearning\Models\AssignmentSection::find($asec_id);
  if ($asec == NULL)
    return drupal_not_found();

  if (count($workflows) > 0) : foreach ($workflows as $workflow) :
    $workflow->tasks()->delete();

    $workflow->delete();
  endforeach; endif;

  $asec->delete();

  drupal_set_message(t('Assignment Section and all related tasks/workflows deleted.'));
  return drupal_goto('class/instructor/' . $r);
}

/*
function groupgrade_view_allocation($assignment,$view_names = false,$asec_view = null){
  // Workflow's assignment_id is for assignment section, not assignment!
  

  $return = '';
  
  // We have assignment given to us. We need to find the workflows through assignment section!
  if($asec_view == null){
    $asecs = AssignmentSection::where('assignment_id', '=', $assignment)
      ->get();
  }
  else {
    $asecs = AssignmentSection::where('asec_id', '=', $asec_view)
      ->get();
  }
  
  //This is bad practice, but at the moment it seems that we won't need to see an assignment in every section.
  //Fix this function up later.
  $section = Section::find($asecs[0]->section_id);
  $course = $section->course()->first();
  $semester = $section->semester()->first();
  $assign = $asecs[0]->assignment()->first();
  
  drupal_set_title(sprintf('%s: %s', $assign->assignment_title, t('View + Reassign')));
  
  $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/instructor/'.$asecs[0]->section_id), HTML_BACK_ARROW, t('Back to Select Assignment (this section)'));
  
  
  $return .= sprintf('<p><strong>%s:</strong> %s &mdash; %s &mdash; %s</p>', 
    t('Course'),
    $course->course_name, 
    $section->section_name,
    $semester->semester_name
  );
  
  // Before anything, let's print out a legend
  
  $return .= "<h2>Legend</h2>";
  $return .= "<table><tr>";
  $return .= "<th style='background:#FFFFFF'>In Progress</th><th style='background:#B4F0BB'>Complete</th><th style='background:#FCC7C7'>Late</th><th style='background:#F5F598'>Not Needed</th><th style='background:#E8E8E8'>Inactive</th>";
  $return .= "</tr></table><br>";
  
  // Our array that keeps users confidential
  $replacement = array();
  $numstudents = 0;
  $numinstructors = 0;
  
  $headers = array();
  $rows = array();
  
  foreach($asecs as $asec) :
  
  if($asec_view != null)
    $assignment = $asec->assignment_id;
  
  	  unset($rows);
  
	  $return .= "<h2>" . $assign->assignment_title . "</h2>";   
  
	  $workflows = Workflow::where('assignment_id', '=', $asec['asec_id'])
	    ->get();
	  
	  if(count($workflows) == 0)
	    return "No workflows for this assignment found.";
	  
	  foreach($workflows as $whocares => $workflow) :  
		  
		$tasks = WorkflowTask::where('workflow_id', '=', $workflow['workflow_id'])
		  ->get();
		
		if(count($tasks) == 0)
	      return "No tasks found for this assignment.";  
		
		$i = 0;
		$results = array();
		
		foreach($tasks as $task) :
	
		  $printuser = '';
		  if(!isset($task['user_id'])){
		  	$printuser = 'AUTOMATIC';
		  }
		  else{
		    $user = user_load($task['user_id']);
			// Does this user exist in our array?
			if(isset($replacement[$user->name])){
		      $printuser = $replacement[$user->name];
		      //print "USER FOUND, " . $user->name . " EXISTS<br>";
			}
			else{
			  // The user doesn't, let's put them in the array then
			  // But first, is this a user or an instructor?
			  $num = $numstudents;
			  $title = "Student";
			  $numstudents++;
			  if(isset($task['settings']['pool']['name']))
			    if($task['settings']['pool']['name'] == "instructor")
				{
				  $num = $numinstructors;
				  $title = "Instructor";
			      $numinstructors++;
				  //False alarm on the user, it was actually a student!
				  $numstudents--;
				}
			  // Is $view_names on? Then we're displaying real names.
			  // Else, only display aliases.
			  if(!$view_names)
			    $replacement[$user->name] = $title . ' ' . $num;
			  else
			  	$replacement[$user->name] = $user->name;
			  $printuser = $replacement[$user->name];
			  
			  //print "USER NOT FOUND, SETTING " . $user->name . " AS USER " . $num . "<br>";
			}
		    
		  }
			
			$headers[$i] = $task['type'];
			$i+=1;
			$color;
			$hide = true;
			
			switch($task['status']){
				
				case 'complete': $color = "#B4F0BB"; $hide = false; break;
				case 'not triggered': $color = "#E8E8E8"; break;
				case 'timed out': $color = "#FCC7C7"; break;
				case 'expired': $color = "#F5F598"; break;
				default: $color = "#FFFFFF"; break;
			}
			
			if($task['status'] == 'complete' && $view_names)
			  $r['retrigger'] = "<br><br><a href=" . url(current_path() . '/' . $task['task_id']) . ">" . 'Re-Open</a>';
			  //$r['retrigger'] = "<br><br><a href=" . url('class/instructor/assignments/' . $assignment . '/administrator-allocation/retrigger/' . $task['task_id']) . ">" . 'Re-Open</a>';
			else
			  $r['retrigger'] = null;
			
			//$r['type'] = $task['type'];
			$r['task_id'] = $task['task_id'];
			if(!$hide)
			  $r['print'] = "<a title='" . $task['type'] . "' href=" . url('class/task/' . $task['task_id']) . ">" . $printuser . "<br>(" . $task['task_id'] . ")</a>";
			else
			  $r['print'] = "<span title = '" . $task['type'] . "'>" . $printuser . "<br>(" . $task['task_id'] . ")</span>";
			$r['color'] = $color;
			$results[] = $r;
			
		endforeach;
	  	
		$rows[] = $results;
		unset($results);
		
	  endforeach;
	
	  $return .= "<table><tr>";
	  
	  foreach($headers as $header => $head){
	  	$return .= "<th>" . $head . "</th></span>";
	  }
	  
	  $return .= "</tr>";
	  
	  foreach($rows as $row){
	  	$return .= "<tr>";
		foreach($row as $data){
		  $return .= "<td style='background:" . $data['color'] . ";'>" . $data['print']
		  . ((isset($data['retrigger'])) ? $data['retrigger'] : '') . '</td>';
		}
		$return .= "</tr>";
	  }
	
	  /*$return = theme('table', array(
	    'rows' => $rows,
	    'header' => $headers,
	    'empty' => t('Nothing to display.'),
	    //'attributes' => array('width' => '100%'),
	  )); STOP COMMENT HERE
	
	  $return .= "</table><br><br>";
	  
  endforeach;

  return $return;
}
*/

function groupgrade_view_allocation($assignment,$view_names = false,$asec_view = null){
  // Workflow's assignment_id is for assignment section, not assignment!
  

  $return = '';
  
  // We have assignment given to us. We need to find the workflows through assignment section!
  if($asec_view == null){
    $asecs = AssignmentSection::where('assignment_id', '=', $assignment)
      ->get();
  }
  else {
    $asecs = AssignmentSection::where('asec_id', '=', $asec_view)
      ->get();
  }
  
  //This is bad practice, but at the moment it seems that we won't need to see an assignment in every section.
  //Fix this function up later.
  $section = Section::find($asecs[0]->section_id);
  $course = $section->course()->first();
  $semester = $section->semester()->first();
  $assign = $asecs[0]->assignment()->first();
  
  drupal_set_title(sprintf('%s: %s', $assign->assignment_title, t('View + Reassign')));
  
  $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/instructor/'.$asecs[0]->section_id), HTML_BACK_ARROW, t('Back to Select Assignment (this section)'));
  
  
  $return .= sprintf('<p><strong>%s:</strong> %s &mdash; %s &mdash; %s</p>', 
    t('Course'),
    $course->course_name, 
    $section->section_name,
    $semester->semester_name
  );
  
  // Before anything, let's print out a legend
  
  $return .= "<h2>Legend</h2>";
  $return .= "<table><tr>";
  $return .= "<th style='background:#FFFFFF'>In Progress</th><th style='background:#B4F0BB'>Complete</th><th style='background:#FCC7C7'>Late</th><th style='background:#F5F598'>Not Needed</th><th style='background:#E8E8E8'>Inactive</th>";
  $return .= "</tr></table><br>";
  
  // Our array that keeps users confidential
  $replacement = array();
  $numstudents = 0;
  $numinstructors = 0;
  
  $headers = array();
  $rows = array();
  
  foreach($asecs as $asec) :
  
  if($asec_view != null)
    $assignment = $asec->assignment_id;
  
  	  unset($rows);
  
	  $return .= "<h2>" . $assign->assignment_title . "</h2>";   
  
	  $workflows = Workflow::where('assignment_id', '=', $asec['asec_id'])
	    ->get();
	  
	  if(count($workflows) == 0)
	    return "No workflows for this assignment found.";
	  
	  foreach($workflows as $whocares => $workflow) :  
		  
		$tasks = WorkflowTask::where('workflow_id', '=', $workflow['workflow_id'])
		  ->get();
		
		if(count($tasks) == 0)
	      return "No tasks found for this assignment.";  
		
		$i = 0;
		$results = array();
		
		foreach($tasks as $task) :
		
		  $printuser = '';
		  if(!isset($task['user_id'])){
		  	$printuser = 'AUTOMATIC';
		  }
		  else{
		    $user = user_load($task['user_id']);
			// Does this user exist in our array?
			if(isset($replacement[$user->name])){
		      $printuser = $replacement[$user->name];
		      //print "USER FOUND, " . $user->name . " EXISTS<br>";
			}
			else{
			  // The user doesn't, let's put them in the array then
			  // But first, is this a user or an instructor?
			  $num = $numstudents;
			  $title = "Student";
			  $numstudents++;
			  if(isset($task['settings']['pool']['name']))
			    if($task['settings']['pool']['name'] == "instructor")
				{
				  $num = $numinstructors;
				  $title = "Instructor";
			      $numinstructors++;
				  //False alarm on the user, it was actually a student!
				  $numstudents--;
				}
			  // Is $view_names on? Then we're displaying real names.
			  // Else, only display aliases.
			  if(!$view_names)
			    $replacement[$user->name] = $title . ' ' . $num;
			  else
			  	$replacement[$user->name] = $user->name;
			  $printuser = $replacement[$user->name];
			  
			  //print "USER NOT FOUND, SETTING " . $user->name . " AS USER " . $num . "<br>";
			}
		    
		  }
			
			$headers[$i] = $task['type'];
			$i+=1;
			$color;
			$hide = true;
			
			switch($task['status']){
				
				case 'complete': $color = "#B4F0BB"; $hide = false; break;
				case 'not triggered': $color = "#E8E8E8"; break;
				case 'timed out': $color = "#FCC7C7"; break;
				case 'expired': $color = "#F5F598"; break;
				default: $color = "#FFFFFF"; break;
			}
			
			$m = new Modal($task['task_id']);
			$m->setTitle(sprintf('%s (%s)',ucfirst($task['type']),$printuser));
			
			$fakeStatus = '';
			
			switch($task['status']){
				
				case 'complete': $fakeStatus = 'Complete'; break;
				case 'timed out': $fakeStatus = 'Late'; break;
				case 'expired': $fakeStatus = 'Not Needed'; break;
				case 'triggered': $fakeStatus = 'In Progress'; break;
				case 'not triggered': $fakeStatus = 'Inactive'; break;
			};
			
			$view = '';
			if($task['status'] == 'complete')
			  $view = sprintf('<a style="font-weight:bold;" href="%s">View task in greater detail</a>',url('class/task/' . $task['task_id']));
			else
			  $view = '<strong>This task is not completed yet and cannot be viewed.</strong>';
			
			$retrigger = '';
			
			if($task['status'] == 'complete' && $view_names)
			  $retrigger = "<a style='font-weight:bold;' href=" . url(current_path() . '/' . $task['task_id']) . ">" . 'Re-open task for user</a><br>';
			  //$r['retrigger'] = "<br><br><a href=" . url('class/instructor/assignments/' . $assignment . '/administrator-allocation/retrigger/' . $task['task_id']) . ">" . 'Re-Open</a>';
			
			$reassignHistory = '<strong>This task has never been reassigned to anybody else.</strong>';
			
			if($task['user_history'] != null){
				if($view_names){
					$reassignHistory = '<ul>';
					$history = json_decode($task['user_history'],1);
					foreach($history as $h){
						$reassignHistory .= sprintf('<li>%s replaced %s on %s.</li>',
						$h['new_name'],$h['previous_name'],$h['time']
						);
					}
					
					$reassignHistory .= '</ul>';
				}
				else
					$reassignHistory = "<strong>This task has been reassigned. To view identities, please view the V+R Task Table.</strong>";
			}
			
			$quickReassign = '';
			
			if($view_names){
			  //$quickForm = drupal_get_form('gg_quick_reassign_form',$asec['asec_id'],$task['task_id']);
			  //$quickReassign .= drupal_render($quickForm);
			  $url = sprintf('class/instructor/%d/assignment/%d/reassigntask/%d',$section->section_id,$asec_view,$task['task_id']);
			  $quickReassign = sprintf('<a style="font-weight:bold;" href="%s">Reassign this task to another user</a>',url($url));
			  
			}
			
			if(isset($task['user_id'])){
				$body = sprintf('
				<h4>Task Properties</h4>
				<strong>Task Type: </strong>%s<br>
				<strong>Assigned to: </strong>%s<br>
				<strong>Status: </strong>%s
				<hr>
				<h4>Reassign History</h4>
				%s
				<hr>
				<h4>Task Actions</h4>
				%s<br>
				%s
				%s
				
				',$task['type'],$printuser,$fakeStatus,$reassignHistory,$view,$retrigger,$quickReassign);
				
				$m->setBody($body);
				
				$return .= $m->build();
			}
			else{
				$body = sprintf('
				<h4>Task Properties</h4>
				<strong>Task Type: </strong>%s<br>
				<strong>Assigned to: </strong><em>This task is handled by the server.</em><br>
				<strong>Status: </strong>%s
				
				',$task['type'],$fakeStatus);
				
				$m->setBody($body);
				
				$return .= $m->build();
			}
			
			//$r['type'] = $task['type'];
			$r['task_id'] = $task['task_id'];
			//$r['print'] = "<a title='" . $task['type'] . "' href=" . url('class/task/' . $task['task_id']) . ">" . $printuser . "<br>(" . $task['task_id'] . ")</a>";
			$r['print'] = $m->printLink($printuser . "<br>(" . $task['task_id'] . ")");
			$r['color'] = $color;
			$results[] = $r;
			
			
		endforeach;
	  	
		$rows[] = $results;
		unset($results);
		
	  endforeach;
	
	  $return .= "<table><tr>";
	  
	  foreach($headers as $header => $head){
	  	$return .= "<th>" . $head . "</th>";
	  }
	  
	  $return .= "</tr>";
	  
	  $rowCount = 0;
	  
	  foreach($rows as $row){
	  	
		if($rowCount == 15){
			$rowCount = 0;
			foreach($headers as $header => $head){
	  			$return .= "<th>" . $head . "</th>";
	  		}
			
			//continue;
		}
		
	  	$return .= "<tr>";
		foreach($row as $data){
		  $return .= "<td style='background:" . $data['color'] . ";'>" . $data['print']
		  . ((isset($data['retrigger'])) ? $data['retrigger'] : '') . '</td>';
		}
		$return .= "</tr>";
		
		$rowCount++;
	  }
	
	  /*$return = theme('table', array(
	    'rows' => $rows,
	    'header' => $headers,
	    'empty' => t('Nothing to display.'),
	    //'attributes' => array('width' => '100%'),
	  ));*/
	
	  $return .= "</table><br><br>";
	  
  endforeach;

  return $return;
}

function gg_reassign_form($form, &$form_state, $asec, $task)
{

  $asec = AssignmentSection::find($asec);
  
  $section = $asec->section()->first();
  $students = $section->students()->get();
	
  $items = $index = [];

  if (count($students) > 0) : foreach($students as $student) :
    $user = user_load($student->user_id);
    if (! $user) continue;

    $index[$student->user_id] = ggPrettyName($user);
  endforeach; endif;

  $items['task'] = array(
    '#type' => 'hidden',
    '#value' => $task,
  );
  
  $items['returnUrl'] = array(
    '#type' => 'hidden',
    '#value' => sprintf('class/instructor/%d/assignment/%d/view-reassign/table',$section->section_id,$asec->asec_id),
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

  return $items;
}

function gg_reassign_form_submit($form, &$form_state)
{
  $task_id = $form['task']['#value'];

  $task = WorkflowTask::where('task_id','=',$task_id)
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

  $returnUrl = $form['returnUrl']['#value'];

  drupal_set_message(sprintf("%s was replaced with %s for task %d",$user_object->name,$new_user->name,$task_id));
  drupal_goto($returnUrl);
}
