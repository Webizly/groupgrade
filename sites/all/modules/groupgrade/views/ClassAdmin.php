<?php
use Drupal\ClassLearning\Models\Course;

/**
 * View an Class Overview
 * 
 * @param int
 */
function groupgrade_class_view($id)
{
  $course = Course::find($id);
  if ($course == NULL) return drupal_not_found();
  $sections = $course->sections()->get();

  $return = '';
  $return .= '<h2>Course <small>'.$course->course_name.' &mdash; '.$course->course_title.'</small></h2>';
  $return .= '<div class="admin clearfix"><div class=" clearfix">';
  $return .= '<h3>Sections</h3>';
  $return .= '<p><a href="'.url('admin/pla/sections/new/'.$course->course_id).'" class="btn btn-primary">Create Section</a></p>';

  $rows = array();
  if (count($sections) > 0) : foreach($sections as $section) :
    $semester = $section->semester()->first();

    $rows[] = array(
      '<a href="'.url('admin/pla/section/'.$section->section_id).'">'.$section->section_name.'</a>',
      $section->section_description,
      number_format($section->students()->count()),
      $semester->semester_name
    );
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Section Name', 'Description', 'Students', 'Semester'),
    'rows' => $rows,
    'attributes' => array('width' => '100%'),
    'empty' => 'No sections found.'
  ));
  
  $return .= '</div></div>';

  return $return;
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

  return drupal_set_message(sprintf('Course %d created', $course->course_id));
}
