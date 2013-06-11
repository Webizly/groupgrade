<?php
/**
 * Proxy Function to view the current classes
 */
function groupgrade_classes_dashboard() {
  return groupgrade_classes_view_specific();
}

function groupgrade_classes_view_specific($which = 'current')
{
  $classes = Drupal\ClassLearning\Models\User::classes($which);

  $headers = array('Course', 'Semester', 'Role');
  $rows = array();
  if (count($classes) > 0) : foreach($classes as $class) :
    $rows[] = array($class->course_name, '', '');
  endforeach; endif;

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows
  ));
}

function groupgrade_classes_create($organization)
{
  return drupal_get_form('groupgrade_classes_create_form', $organization);
}

function groupgrade_classes_create_form($form, &$form_state, $org_id)
{
  $organization = Drupal\ClassLearning\Models\Organization::find($org_id);
  if ($organization == NULL) return drupal_not_found();
  
  $items = array();
  $items['back-text'] = array(
    '#type' => 'link',
    '#title' => 'Back to Organization',
    '#href' => 'admin/pla/organization/'.$org_id,
  );

  $items['name'] = array(
    '#title' => 'Course Name (ACCT 101, IS 101, etc.)',
    '#type' => 'textfield',
    '#required' => true,
  );

  $items['title'] = array(
    '#title' => 'Course Title (Intro to Accounting)',
    '#type' => 'textfield',
    '#required' => true,
  );

  $items['organization'] = array(
    '#value' => $organization->organization_id,
    '#type' => 'hidden'
  );

  $items['submit'] = array(
    '#value' => 'Create Course',
    '#type' => 'submit'
  );
  return $items;
}

function groupgrade_classes_create_form_submit($form, &$form_state)
{
  $name = $form['name']['#value'];
  $title = $form['title']['#value'];
  $organization = $form['organization']['#value'];

  if (empty($name) OR empty($title))
    return drupal_set_message('Name/title empty', 'error');

  $course = new Drupal\ClassLearning\Models\Course;
  $course->organization_id = (int) $organization;
  $course->course_name = $name;
  $course->course_title = $title;
  $course->save();

  drupal_set_message(sprintf('Course %d created', $course->course_id));
}

