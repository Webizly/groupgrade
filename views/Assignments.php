<?php
use Drupal\ClassLearning\Models\User;

function groupgrade_assignments_dashboard() {
  global $user;
  $assignments = User::assignedAssignments()->get();

  $return = '';
  
  $rows = [];
  $headers = ['Assignment', 'Course', 'Start Date'];

  if (count($assignments) > 0) : foreach ($assignments as $assignment) :
    $rows[] = [
      sprintf('<a href="%s">%s</a>', url('class/assignments/'.$assignment->asec_id), $assignment->assignment_title),
      sprintf('%s &mdash; %s', $assignment->course_name, $assignment->section_name),
      $assignment->asec_start
    ];
  endforeach; endif;

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No assignments found.',
    'attributes' => array('width' => '100%')
  ));

  return $return;
}
