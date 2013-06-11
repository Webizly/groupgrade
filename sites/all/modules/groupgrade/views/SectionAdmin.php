<?php
use Drupal\ClassLearning\Models\Section;

function groupgrade_section_view($course)
{
  return drupal_get_form('groupgrade_section_view_form', $course);
}

function groupgrade_section_view_form($form, &$form_state, $course)
{
  $items = array();
  $items['back-text'] = array(
    '#type' => 'link',
    '#title' => 'Back to Course',
    '#href' => 'admin/pla/courses/'.$course,
  );

  $items['name'] = array(
    '#title' => 'Section Name',
    '#type' => 'textfield',
    '#required' => true,
  );

  $items['course'] = array(
    '#value' => $course,
    '#type' => 'hidden'
  );

  $items['submit'] = array(
    '#value' => 'Create Course',
    '#type' => 'submit'
  );
  return $items;
  return $items;
}