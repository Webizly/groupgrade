<?php
use Drupal\ClassLearning\Models\Semester;

function groupgrade_semester_add($organization)
{
  return drupal_get_form('groupgrade_semester_add_form', (int) $organization);
}

function groupgrade_semester_add_form($form, &$form_state, $org_id)
{
  $organization = Drupal\ClassLearning\Models\Organization::find($org_id);
  if ($organization == NULL) return drupal_not_found();

  $items = array();
  $items['back-text'] = array(
    '#type' => 'link',
    '#title' => 'Back to Organization',
    '#href' => 'admin/class/organization/'.$org_id,
  );
  $items['name'] = array(
    '#title' => 'Semester Name (Fall 2013, Spring 2014, etc.)',
    '#type' => 'textfield',
    '#required' => true,
  );

  $items['start-date'] = array(
    '#title' => 'Semester Start',
    '#type' => 'date',
    '#required' => true,
  );

  $items['end-date'] = array(
    '#title' => 'Semester End',
    '#type' => 'date',
    '#required' => true,
  );

  $items['organization'] = array(
    '#value' => $organization->organization_id,
    '#type' => 'hidden'
  );

  $items['submit'] = array(
    '#value' => 'Add Semester',
    '#type' => 'submit'
  );
  return $items;
}

function groupgrade_semester_add_form_submit($form, &$form_state)
{
  $name = $form['name']['#value'];
  $end = $form['end-date']['#value'];
  $end = sprintf('%s-%s-%s', $end['year'], $end['month'], $end['day']);

  $start = $form['start-date']['#value'];
  $start = sprintf('%s-%s-%s', $start['year'], $start['month'], $start['day']);
  $organization = $form['organization']['#value'];

  $semester = new Semester;
  $semester->semester_name = $name;
  $semester->semester_start = $start;
  $semester->semester_end = $end;
  $semester->organization_id = $organization;
  $semester->save();

  drupal_set_message(sprintf('%s created.', $name));
}
