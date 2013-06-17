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
  Drupal\ClassLearning\Models\Assignment;

function groupgrade_assignment_dash() {
  global $user;

  $assignments = Assignment::where('user_id', '=', $user->uid)
    ->orderBy('assignment_id', 'desc')
    ->get();

  $return = '';
  $return .= '<h3>Assignments</h3>';

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

  $sections = $assignment->sections();

  $return = '<div class="well">';
    $return .= '<h3>'.$assignment->assignment_title.'</h3>';
    $return .= '<p>'.$assignment->assignment_description.'</p>';
  $return .= '</div>';


  return $return;
}

function groupgrade_edit_assignment($form, &$form_state, $id)
{
  global $user;
  $assignment = Assignment::find((int) $id);

  if ($assignment == NULL OR (int) $assignment->user_id !== (int) $user->uid)
    return drupal_not_found();

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
