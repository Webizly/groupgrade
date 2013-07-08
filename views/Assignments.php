<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\AssignmentSection;

function groupgrade_assignments_dashboard() {
  global $user;
  $assignments = User::assignedAssignments()->get();

  $return = '';
  
  $rows = [];
  $headers = ['Assignment', 'Course', 'Start Date'];

  if (count($assignments) > 0) : foreach ($assignments as $assignment) :
    $rows[] = [
      sprintf('<a href="%s">%s</a>', url('class/assignments/'.$assignment->section_id.'/'.$assignment->asec_id), $assignment->assignment_title),
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

function gg_view_assignment_listing($section_id, $asec_id)
{
  if (! gg_in_section($section_id))
    return drupal_not_found();
  
  $asec = AssignmentSection::find($asec_id);
  $section = $asec->section()->first();

  if ((int) $section->section_id !== (int) $section_id) return drupal_not_found();

  $assignment = $asec->assignment()->first();

  $createProblems = WorkflowTask::whereIn('workflow_id', function($query) use ($asec_id)
  {
    $query->select('workflow_id')
      ->from('workflow')
      ->where('assignment_id', '=', $asec_id);
  })
    ->whereType('edit problem')
    ->whereStatus('complete')
    ->get();

  $headers = ['Problem'];
  $rows = [];

  if (count($createProblems) > 0) : foreach ($createProblems as $t) :
    $rows[] = [sprintf('<a href="%s">%s</a>', url('class/task/'.$t->task_id), $t->data['problem'])];
  endforeach; endif;

  return theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'empty' => 'No problems found.',
    'attributes' => array('width' => '100%')
  ));
}
