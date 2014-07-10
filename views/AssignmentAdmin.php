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
  Drupal\ClassLearning\Models\WorkflowTask;

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
function groupgrade_add_assignment_section($form, &$form_state, $assignment)
{
  drupal_set_title(t('Add "@assignment-title" to Section', ['@assignment-title' => Assignment::find($assignment)->assignment_title]));

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
    '#markup' => '<hr><h3>Task Expiration Dates</h3>',
  );

  $items['task_expire'] = array(
    '#type' => 'fieldset',
  );

  add_task_field($items,'create problem');
  add_task_field($items,'edit problem',1);
  add_task_field($items,'create solution');
  add_task_field($items,'grade solution');
  add_task_field($items,'resolution grader');
  add_task_field($items,'dispute');
  add_task_field($items,'resolve dispute',1);

  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $assignment
  );

  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Add Section'),
  );
  
  return $items;
}

//A convenient function that adds tasks to the function above. Saves time!
function add_task_field(&$items, $type, $duration = '3'){
	
	$choices = array(0 => t('Set Duration'), 1 => t('Set Date'));
	
	$items['task_expire'][$type] = array(
    '#type' => 'fieldset',
    '#title' => ucwords($type),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px">',
    '#suffix' => '</div>',
  );
  
  $items['task_expire'][$type][$type . '-radio'] = array(
    '#type' => 'radios',
    '#title' => t('Expire After: '),
    '#default_value' => isset($node->radio) ? $node->radio : 0,
    '#options' => $choices,
  );

  $items['task_expire'][$type][$type . '-duration'] = array(
    '#type' => 'textfield',
    '#title' => '# of Days After Triggering',
    '#default_value' => $duration,
    '#states' => array(
	  'visible' => array(
	    ':input[name="' . $type . '-radio"]' => array('value' => 0),
	  ),
	),
  );

  $items['task_expire'][$type][$type . '-date'] = array(
    '#type' => 'date_select',

    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Expiration Date'),
    '#date_year_range' => '-0:+2', 

    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
	  'visible' => array(
	    ':input[name="' . $type . '-radio"]' => array('value' => 1),
	  ),
	),
  );
}

function groupgrade_add_assignment_section_submit($form, &$form_state)
{
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

    if ($i == 'year' AND intval($start[$i]) == 0)
      $start[$i] = '0000';
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
  
  foreach($tasks as $key => $value){
  	// Is this something we actually set, or is it a fieldset parameter?
	if(substr($key,0,1) == '#')
	  continue;
	
	// So will this expire after a set amount of days or on a certain date?
	
	if($tasks[$key][$key . '-radio']['#value'] == 0){
		// Duration
		$t = $tasks[$key][$key . '-duration'['#value'];
		if(!is_int($t) || $t <= 0)
		  return drupal_set_message('Invalid duration specified for ' . $key,'error');
		$task_expire[$key]['duration'] = $t;
	}
	else{
		// Date
		foreach (['year', 'month', 'day', 'hour', 'minute'] as $i) :
			$start = $tasks[$key][$key.'-date']['#value'];
		    if ($start[$i] == '')
		      $start[$i] = '00';
		    elseif ((int) $start[$i] < 9)
		      $start[$i] = '0'.intval($start[$i]);
		    else
		      $start[$i] = (string) $start[$i];
		
		    if ($i == 'year' AND intval($start[$i]) == 0)
		      $start[$i] = '0000';
			
			$task_expire[$key]['date'] = sprintf('%s-%s-%s %s:%s:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
			if($task_expire[$key]['date'] == '0000-00-00 00:00:00')
			  return drupal_set_message(t('Expiration date is not set for ' . $key),'error');
		endforeach;
		
	}
  }
  /*foreach($form['task_expire']['#value'] as $name => $task){
    print($task['duration']['#value']);
  }*/

  return drupal_set_message(sprintf('Added assignment "%s" to section "%s"', $assignment->assignment_title, $sectionObject->section_name));
}

/**
 * Edit a section on an assignment
 */
function groupgrade_edit_assignment_section($form, &$form_state, $assignment, $section)
{
  global $user;
  $section = AssignmentSection::find($section);
  if ($section == NULL) return drupal_not_found();

  drupal_set_title(sprintf('%s%s', t('Assignment Details for Section #'), $section->section_id));

  $items = array();
  $items['m'] = array(
    '#markup' => '<a href="'.url('class/instructor/assignments/'.$assignment).'">Back to Assignment Management</a>',
  );

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

  $items['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Change Assignment Details'),
  );
  return $items;
}

function groupgrade_edit_assignment_section_submit($form, &$form_state)
{
  $section = (int) $form['asec_id']['#value'];
  $start = $form['start-date']['#value'];

  $section = AssignmentSection::find($section);
  if ($section == NULL) return drupal_not_found();

  $section->asec_start = sprintf('%d-%d-%d %d:%d:00', $start['year'], $start['month'], $start['day'], $start['hour'], $start['minute']);
  $section->save();

  return drupal_set_message(sprintf('Updated assignment section %d on section %d', $section->asec_id, $section->section_id));
}

/**
 * Remove a section from an assignment
 */
function groupgrade_remove_assignment_section($form, &$form_state, $assignment, $section)
{
  global $user;
  $section = AssignmentSection::find($section);
  if ($section == NULL) return drupal_not_found();

  $items = array();
  $items['m'] = array(
    '#markup' => '<p><a href="'.url('class/instructor/assignments/'.$assignment).'">Back to Assignment</a></p>',
  );

  $items[] = [
    '#markup' => '<p>Are you <strong>sure</strong> you want to remove this assignment from the section? It is irreversible!</p>'
  ];

  $items['assignment_id'] = array(
    '#type' => 'hidden',
    '#value' => $assignment
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
  return drupal_goto(url('class/instructor/assignments/'.$assignment_id));
}

function groupgrade_view_allocation($assignment,$view_names){
  // Workflow's assignment_id is for assignment section, not assignment!
  
  drupal_set_title("View Allocation");

  $return = '';
  
  // Before anything, let's print out a legend
  
  $return .= "<h2>Legend</h2>";
  $return .= "<table><tr>";
  $return .= "<th style='background:#FFFFFF'>In Progress</th><th style='background:#B4F0BB'>Complete</th><th style='background:#FCC7C7'>Late</th><th style='background:#E8E8E8'>Inactive</th>";
  $return .= "</tr></table><br>";
  
  // We have assignment given to us. We need to find the workflows through assignment section!
  
  $asecs = AssignmentSection::where('assignment_id', '=', $assignment)
    ->get();
  
  // Our array that keeps users confidential
  $replacement = array();
  $numstudents = 0;
  $numinstructors = 0;
  
  $headers = array();
  $rows = array();
  
  foreach($asecs as $asec) :
  
  	  unset($rows);
  
	  $return .= "<h2>Assignment Section #" . $asec['asec_id'] . "</h2>";   
  
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
		
		foreach($tasks as $yawn => $task) :
	
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
				case 'expired': $color = "#FCC7C7"; break;
				default: $color = "#FFFFFF"; break;
			}
			
			if(!$hide)
			  $r['print'] = "<a href=" . url('class/task/' . $task['task_id']) . ">" . $printuser . "</a>";
			else
			  $r['print'] = $printuser;
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
	  
	  foreach($rows as $garbage => $row){
	  	$return .= "<tr>";
		foreach($row as $moregarbage => $data){
		  $return .= "<td style='background:" . $data['color'] . ";'>" . $data['print'] . "</td>";
		}
		$return .= "</tr>";
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
