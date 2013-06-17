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
  $headers = array('Course', 'Semester');
  $rows = array();
  if (count($classes) > 0) : foreach($classes as $class) :
    $rows[] = array(
      '<a href="'.url('class/section/'.$class['section_id']).'">'
        .$class['course_name'].' &mdash; '. $class['course_title']
      .'</a>', $class->semester_name);
  endforeach; endif;
 
  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No classes found.',
    'attributes' => array('width' => '100%')
  ));
}


function groupgrade_view_section($section)
{
  $return = '';

  return $return;
}

