<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Workflow\Manager;

/**
 * @file
 */

function groupgrade_instructor_dash() {
  $sections = User::sectionsWithRole('instructor')->get();

  $return = '';
  //$return .= '<h2>Course <small>'.$course->course_name.' &mdash; '.$course->course_title.'</small></h2>';
  $return .= '<div class=" clearfix">';
  $return .= '<h3>Sections</h3>';
  $return .= sprintf('<p>%s</p>', t('Select the section to view and manage that section\'s assignments.'));

  $rows = array();
  if (count($sections) > 0) : foreach($sections as $section) :
    $semester = $section->semester()->first();
    $course = $section->course()->first();
    $semester = $section->semester()->first();

    $rows[] = array(
      '<a href="'.url('class/instructor/'.$section->section_id).'">'
        .$course->course_name.' '
        .$section->section_name
        .' &mdash; '.$semester->semester_name
        .'</a>',
      $section->section_description,
      number_format($section->students()->count())
    );
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Section Name', 'Description', 'Students'),
    'rows' => $rows,
    'attributes' => array('width' => '100%'),
    'empty' => 'No sections found.'
  ));
  
  $return .= '</div>';
  return $return;
}


function groupgrade_adminview_section($id) {
  $section = Section::find((int) $id);
  if ($section == NULL) return drupal_not_found();

  drupal_set_title(t('Section Dashboard'));

  $return = '';

  return $return;
}

function groupgrade_view_user($section_id) {
  $return = '';
  $section = Section::find($section_id);
  
  if ($section == NULL) return drupal_not_found();

  drupal_set_title(t('Section Users'));

  foreach(['instructor', 'student'] as $role):
    $return .= '<h4>'.ucfirst($role).'s</h4>';
    $students = $section->users($role)
      ->where('su_role', '=', $role)
      ->get();

    $rows = array();
    if (count($students) > 0) : foreach($students as $student) :
      $user = $student->user();
      $rows[] = array(
        sprintf('%s (<a href="%s">%s</a>)', ggPrettyName($user), $user->mail, $user->mail),
        $student->su_status,
        '<a href="'.url('class/instructor/'.$section->section_id.'/remove-from-section/'.$student->user_id).'">'.t('remove').'</a> &mdash;
        <a href="'.url('class/instructor/'.$section->section_id.'/swap-status/'.$student->user_id).'">change to '.(($student->su_status !== 'active') ? 'active' : 'inactive').'</a>',
      );
    endforeach; endif;

    $return .= theme('table', array(
      'rows' => $rows,
      'header' => array('User', 'Status', 'Operations'),
      'empty' => 'No users found.',
      'attributes' => array('width' => '100%'),
    ));
  endforeach;

  // Add User Form
  require_once (__DIR__.'/SectionAdmin.php');
  $form = drupal_get_form('groupgrade_add_student_form', $section->section_id);

  $return .= sprintf('<h5>%s</h5>', t('Add Users to Section'));
  $return .= drupal_render($form);

  return $return;
}

function groupgrade_view_reports($section_id){
	
	$return = '';
	$return .= '<h1>Student Reports</h1><br>';
	
	// Get all assignment section objects
	$asecs = AssignmentSection::where('section_id','=',$section_id)
	  ->get();
	
	$section = Section::where('section_id','=',$section_id)
	  ->first();
	
	// Get all the students
	  
	$students = array();  
	  
	$section_users = $section->students()->get();
	if(count($section_users) > 0) { foreach($section_users as $i){
		$students[] = user_load($i->user_id);
	}
	}
	else{
		$return .= "No students found.";
		return $return;
	}
	
	//For each assignment section object...
	foreach($asecs as $asec){
		$assignment = Assignment::where('assignment_id','=',$asec->assignment_id)
		  ->first();
		
		$workflows = Workflow::where('assignment_id','=',$asec->asec_id)
		  ->get();
		  
		$return .= "<h3>" . $assignment->assignment_title . "</h3>";
		
		//For each student...
		$return .= "<table><tr><th>UCID</th><th>Name</th><th>Tasks Completed</th><th>Extra Credit Completed</th></tr>";
		foreach($students as $student){
			
			$return .= "<tr>";
			$return .= "<td>" . $student->name . "</td>";
			$return .= "<td>" . ggPrettyName($student) . "</td>";
			
			// Get EVERY task done by this student.
			$normalTasks = array();
			$extraTasks = array();
			
			foreach($workflows as $workflow){
				$tasks = WorkflowTask::where('workflow_id','=',$workflow->workflow_id)
				  ->where('user_id','=',$student->uid)
				  ->get();
				  
				foreach($tasks as $task){
					if($task->status != 'complete')
					  continue;
					if($task->user_history == null)
					  $normalTasks[] = $task;
					else
					  $extraTasks[] = $task;
				}
			}
			
			// Now that we have every task from all workflows, print out tasks completed
			$return .= "<td>";
			foreach($normalTasks as $task){
				$return .= "<a href=" . url('class/task/' . $task->task_id) . ">" . Manager::humanTaskName($task->type) . "</a><br>";
			}
			$return .= "</td>";
			
			// Finally, print out extra credit tasks
			$return .= "<td>";
			foreach($extraTasks as $task){
				
				$return .= "<a href=" . url('class/task/' . $task->task_id) . ">" . Manager::humanTaskName($task->type) . "</a><br>";
			}
			$return .= "</td>";
			
			$return .= "</tr>";
			
			unset($normalTasks);
			unset($extraTasks);
			
		}
		
		$return .= "</table>";
	}
	
	return $return;
}

function groupgrade_view_assignments($id) {
  $return = '';
  drupal_set_title(t('Section Assignments'));

  $section = Section::find($id);
  
  if ($section == NULL) return drupal_not_found();

  $assignments = $section->assignments()->get();
  $rows = array();

  $course = $section->course()->first();
  $semester = $section->semester()->first();

  // Information about this course
  $return .= sprintf('<p><strong>%s</strong>: %s &mdash; %s &mdash; %s</p>',
    t('Course'),
    $course->course_name,
    $section->section_name,
    $semester->semester_name
  );

  $return .= sprintf('<p><a href="%s">%s %s</a></p>',
    url('class/instructor'),
    HTML_BACK_ARROW,
    t('Back to Section Management')
  );

  $return .= sprintf('<p><a href="%s">%s</a></p>',
    url('class/instructor/assignments/new'),
    t('Create new Assignment')
  );

  $return .= sprintf('<p>%s</p>', t('Select an assignment title to see all student entries. Select an operation to manage the assignment.'));

  if (count($assignments) > 0) : foreach($assignments as $assignment) :
    $rows[] = [
      sprintf('<a href="%s">%s</a>',
        url('class/instructor/'.$assignment->section_id.'/assignment/'.$assignment->asec_id),
        $assignment->assignment_title
      ),
      gg_time_human($assignment->asec_start), 
       '<a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/edit-section/'.$assignment->asec_id).'">'.t('Edit Start Date').'</a>'
        .' &mdash; <a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/remove-section/'.$assignment->asec_id).'">'.t('Remove Assignment from Section').'</a>'
    ];
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Title', 'Start Date', 'Operations'),
    'empty' => 'No assignments found.',
    'attributes' => array('width' => '100%'),
  ));

  return $return;
}


/**
 * View an Assignment
 *
 * @param int Section ID
 * @param int AssignmentSection ID
 */
function groupgrade_view_assignment($section_id, $asec_id, $type = NULL)
{
  $section_id = (int) $section_id;
  $assignmentSection = AssignmentSection::find($asec_id);
  if ($assignmentSection == NULL) return drupal_not_found();

  $section = $assignmentSection->section()->first();

  // Logic Check
  if ((int) $section->section_id !== (int) $section_id) return drupal_not_found();

  $course = $section->course()->first();
  $semester = $section->semester()->first();
  $assignment = $assignmentSection->assignment()->first();

  // Specify the title
  drupal_set_title($assignment->assignment_title);

  $return = '';
  // Back Link
  $return .= sprintf('<p><a href="%s">%s %s</a>', url('class/instructor/'.$section_id), HTML_BACK_ARROW, t('Back to View Section'));

  $return .= sprintf('<p><strong>%s:</strong> %s &mdash; %s &mdash; %s</p>', 
    t('Course'),
    $course->course_name, 
    $section->section_name,
    $semester->semester_name
  );

  // Data for the table (the workflows)
  $workflows = WorkflowTask::whereIn('workflow_id', function($query) use ($asec_id)
  {
    $query->select('workflow_id')
      ->from('workflow')
      ->where('assignment_id', '=', $asec_id);
  });

  if ($type == 'timed out') :
    $workflows->whereStatus('timed out')
      ->groupBy('workflow_id');
  else :
    $workflows->whereType('create problem');
  endif;

  $workflows = $workflows->get();

  $headers = ['Problems'];
  $rows = [];

  if (count($workflows) > 0) : foreach ($workflows as $t) :
    $url = url(
      sprintf('class/instructor/%d/assignment/%d/%d',
        $section_id,
        $asec_id,
        $t->workflow_id
      )
    );

    $rows[] = [
      sprintf(
        '<a href="%s">%s</a>',
        $url,
        (isset($t->data['problem'])) ? word_limiter($t->data['problem'], 20) : 'Workflow #'.$t->workflow_id
      )
    ];
  endforeach; endif;

  // Assignment Description
  $return .= sprintf('<p class="summary">%s</p>', nl2br($assignment->assignment_description));
  $return .= '<hr />';
    
  // Instructions
  $return .= sprintf('<p>%s<p>',
    t('Select a question to see the work on that question so far.')
  );

  // Render the workflow
  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No problems found.',
    'attributes' => array('width' => '100%')
  ));

  return $return;
}

function groupgrade_view_timedout($section_id, $asec_id)
{
  return groupgrade_view_assignment($section_id, $asec_id, 'timed out');
}


/**
 * View an Assignment Workflow
 *
 * @param int Section ID
 * @param int AssignmentSection ID
 */
function groupgrade_view_assignmentworkflow($section_id, $asec_id, $workflow_id)
{
  $assignmentSection = AssignmentSection::find($asec_id);
  if ($assignmentSection == NULL) return drupal_not_found();

  $workflow = Workflow::find($workflow_id);
  if ($workflow == NULL OR $workflow->assignment_id != $asec_id)
    return drupal_not_found();

  $section = $assignmentSection->section()->first();
  $course = $section->course()->first();
  $semester = $section->semester()->first();
  $assignment = $assignmentSection->assignment()->first();
  $tasks = $workflow->tasks()->get();

  // Set the Page title
  drupal_set_title($assignment->assignment_title.': '.t('Workflow').' #'.$workflow_id);

  $return = '';

  // Information about the course
  $return .= sprintf('<p><strong>%s:</strong> %s &mdash; %s &mdash; %s</p>', 
    t('Course'),
    $course->course_name, 
    $section->section_name,
    $semester->semester_name
  );

  // Call on a common function so we don't duplicate things
  require_once (__DIR__.'/Tasks.php');
  $return .= gg_view_workflow($workflow, true);

  return $return;
}

function groupgrade_frontend_remove_user_section($section, $user)
{
  SectionUsers::where('section_id', '=', $section)
    ->where('user_id', '=', $user)
    ->delete();

  foreach(array('student', 'instructor') as $role)
    gg_acl_remove_user('section-'.$role, $user, $section);

  drupal_set_message(sprintf('User %d removed from section %d', $user, $section));
  return drupal_goto('class/instructor/'.$section.'/users');
}

/**
 * Swap a user's status in a section between active and inactive
 */
function groupgrade_frontend_swap_status($section, $user)
{
  $userInSection = SectionUsers::where('section_id', '=', $section)
    ->where('user_id', '=', $user)
  ->first();

  if (! $userInSection) return drupal_not_found();

  $userInSection->su_status = ($userInSection->su_status == 'active') ? 'inactive' : 'active';
  $userInSection->save();
  $userData = user_load($user);

  drupal_set_message(sprintf('%s %d %s %s.',
    t('User'),
    ggPrettyName($userData),
    t('status set to'),
    $userInSection->su_status
  ));

  return drupal_goto('class/instructor/'.$section.'/users');
}

function groupgrade_fake_function1(){
	return '1';
}

function groupgrade_fake_function2(){
	return '2';
}

function groupgrade_fake_function3(){
	return '3';
}

function groupgrade_fake_function4(){
	return '4';
}

function groupgrade_fake_functionA(){
	return 'A';
}

function groupgrade_fake_functionB(){
	return 'B';
}