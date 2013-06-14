<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Semester;

/**
 * @file
 */

function groupgrade_instructor_dash() {
  $sections = User::sectionsWithRole('instructor')->get();

  $return = '';
  //$return .= '<h2>Course <small>'.$course->course_name.' &mdash; '.$course->course_title.'</small></h2>';
  $return .= '<div class=" clearfix">';
  $return .= '<h3>Sections</h3>';

  $rows = array();
  if (count($sections) > 0) : foreach($sections as $section) :
    $semester = $section->semester()->first();

    $rows[] = array(
      '<a href="'.url('class/instructor/'.$section->section_id).'">'.$section->section_name.'</a>',
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
  
  $return .= '</div>';
  return $return;
}


function groupgrade_view_section($id) {
  $return = '';
  return $return;
}

function groupgrade_view_user($id) {
  $return = '';
  $section = Section::find($id);
  
  if ($section == NULL) return drupal_not_found();

  foreach(array('instructor', 'student') as $role):
    $return .= '<h4>'.ucfirst($role).'s</h4>';
    $students = $section->students()
      ->where('su_role', '=', $role)
      ->get();

    $rows = array();
    if (count($students) > 0) : foreach($students as $student) :
      $user = $student->user();
      $rows[] = array(
        ggPrettyName($user),
        $student->su_status//,
        //'<a href="'.url('admin/pla/section/remove-user/'.$student->user_id.'/'.$section->section_id).'">remove</a> &mdash;
        //<a href="'.url('admin/pla/section/change-status/'.$student->user_id.'/'.$section->section_id).'">change status</a>',
      );
    endforeach; endif;

    $return .= theme('table', array(
      'rows' => $rows,
      'header' => array('User', 'Status'/*, 'Operations'*/),
      'empty' => 'No users found.',
      'attributes' => array('width' => '100%'),
    ));
  endforeach;

  return $return;
}

function groupgrade_view_assignments($id) {
  $return = '';
  $return .= '<h3>Assignments</h3>';

  $section = Section::find($id);
  
  if ($section == NULL) return drupal_not_found();

  $assignments = $section->assignments()->get();
  $rows = array();

  if (count($assignments) > 0) : foreach($assignments as $assignment) :
    $rows[] = array($assignment->assignment_title, $assignment->start, '');
  endforeach; endif;

  $return .= theme('table', array(
    'rows' => $rows,
    'header' => array('Title', 'Start Date', 'Operations'),
    'empty' => 'No assignments found.',
    'attributes' => array('width' => '100%'),
  ));

  return $return;
}

function groupgrade_create_assignment($section)
{
  module_load_include('inc', 'node', 'node.pages');
  $form = node_add('assignment'); 
  return drupal_render($form);

  $items = array();
  $items['title'] = array('#type' => 'item', '#markup' => '<h4>Create new Assignment</h4>');
  $items['back-link'] = array(
    '#type' => 'item',
    '#markup' => '<a href="'.
      url('class/instructor/'.$section.'/assignments')
    .'">Back to Assignments</a>');


  return $items;
}
