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
  //ldebug(true);
  $headers = array('Course', 'Semester', 'Role');
  $rows = array();
  if (count($classes) > 0) : foreach($classes as $class) :
    $rows[] = array($class['course_name'], '', '');
  endforeach; endif;

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No classes found.',
  ));
}


