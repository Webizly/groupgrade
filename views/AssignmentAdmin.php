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

function groupgrade_create_assignment()
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
