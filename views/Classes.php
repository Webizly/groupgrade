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
  $headers = ['Course', 'Semester', 'Role'];
  $rows = array();
  if (count($classes) > 0) : foreach($classes as $class) :
    $rows[] = [
      (($class->su_role == 'instructor') ? '<a href="'.url('class/instructor/'.$class['section_id']).'">' : '')
        .$class['course_name'].' &mdash; '. $class['section_name'] .' &mdash; '. $class['course_title']
      .(($class->su_role == 'instructor') ? '</a>' : ''),
      $class->semester_name,
      t(ucwords($class->su_role))
    ];
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

