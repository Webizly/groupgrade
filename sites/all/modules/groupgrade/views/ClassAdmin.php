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
  $sections = $course->sections();

  $return = '';
  $return .= '<h2>Course <small>'.$course->course_name.' &mdash; '.$course->course_title.'</small></h2>';
  $return .= '<div class="admin clearfix"><div class="left clearfix">';
  $return .= '<h3>Sections</h3>';
  $return .= '<p><a href="'.url('class/sections/new/'.$course->course_id).'" class="btn btn-primary">Create Section</a></p>';

  $rows = array();
  if (count($sections) > 0) : foreach($sections as $section) :
    $rows[] = array($section->section_name, $section->section_description, number_format($section->students()->count()));
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Section Name', 'Description', 'Students'),
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));
  
  $return .= '</div></div>';

  return $return;
}