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
  Drupal\ClassLearning\Models\AssignmentSection;

function groupgrade_assignment_dash() {
  global $user;

  $assignments = Assignment::where('user_id', '=', $user->uid)
    ->orderBy('assignment_id', 'desc')
    ->get();

  $return = '';
  $return .= '<h3>Your Assignments</h3>';

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
  $items['title'] = array(
    '#title' => 'Assignment Title',
    '#type' => 'textfield',
    '#required' => true, 
  );

  $items['description'] = array(
    '#title' => 'Assignment Description',
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

  $a = new Assignment;
  $a->user_id = $user->uid;
  $a->assignment_title = $title;
  $a->assignment_description = $description;
  $a->save();

  drupal_set_message(sprintf('Assignment %d created.', $a->assignment_id));
  return drupal_goto('class/instructor/assignments/'.$a->assignment_id);
}


function groupgrade_view_assignment($id) {
  global $user;
  $assignment = Assignment::find((int) $id);

  if ($assignment == NULL OR (int) $assignment->user_id !== (int) $user->uid)
    return drupal_not_found();

  drupal_set_title($assignment->assignment_title);

  $sections = $assignment->sections()->get();

  $return = '<p><a href="'.url('class/instructor/assignments').'">Back to Assignments</a></p>';
  $return .= '<div class="well">';
    $return .= '<h3>'.$assignment->assignment_title.'</h3>';
    $return .= '<p>'.$assignment->assignment_description.'</p>';
  $return .= '</div>';


  $rows = array();

  if (count($sections) > 0) : foreach($sections as $section) :

    $rows[] = array($section->section_name, gg_time_human($section->asec_start),
        '<a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/edit-section/'.$section->asec_id).'">Edit</a>'
        .' &mdash; <a href="'.url('class/instructor/assignments/'.$assignment->assignment_id.'/remove-section/'.$section->asec_id).'">Delete</a>'
      );
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Section', 'Start Date', 'Operations'),
    'empty' => 'No sections found for assignment.',
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
    '#title' => 'Assignment Description',
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
  drupal_set_title(t('Add Section to Assignment'));

  global $user;
  $sections_q = User::sectionsWithRole('instructor')
    ->join('course', 'course.course_id', '=', 'section.course_id')
    ->addSelect('course.course_name')
    ->get();

  $sections = array();
  if (count($sections_q) > 0) : foreach($sections_q as $s) :
    $sections[$s->section_id] = sprintf('%s-%s', $s->course_name, $s->section_name);
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

  return drupal_set_message(sprintf('Added assignment section %d to section %d', $s->asec_id, $section));
}

/**
 * Edit a section on an assignment
 */
function groupgrade_edit_assignment_section($form, &$form_state, $assignment, $section)
{
  global $user;
  $section = AssignmentSection::find($section);
  if ($section == NULL) return drupal_not_found();

  $items = array();
  $items['m'] = array(
    '#markup' => '<a href="'.url('class/instructor/assignments/'.$assignment).'">Back to Assignment</a>',
  );

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
    '#value' => t('Update Section'),
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
