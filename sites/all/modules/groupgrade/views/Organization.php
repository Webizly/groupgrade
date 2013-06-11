<?php
use Drupal\ClassLearning\Models\User,
  Drupal\ClassLearning\Models\Organization;

function groupgrade_organization_main()
{
  $organizations = Organization::all();

  $return = '';
  $return .= '<div class="admin clearfix"><div class="left clearfix">';
  $return .= '<h3>Organizations</h3>';

  $headers = array('Name');
  $rows = array();

  if (count($organizations) > 0) : foreach($organizations as $org)
    $rows[] = array('<a href="'.url('admin/pla/organization/'.$org->organization_id).'">'.
      $org->organization_name.'</a>');
  endif;

  $return .= theme('table', array(
    'header' => $headers, 
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));

  $return .= '</div><div class="right clearfix">';

  $return .= '</div></div>';

  return $return;
}

/**
 * View an Organization Overview
 * 
 * @param int
 */
function groupgrade_organization_view($id)
{
  $org = Organization::find($id);
  if ($org == NULL) return drupal_not_found();

  $courses = $org->courses()->get();

  $return = '';
  $return .= '<h2>Organization <small>'.$org->organization_name.'</small></h2>';
  $return .= '<div class="admin clearfix"><div class="left clearfix">';
  $return .= '<h3>Courses</h3>';
  $return .= '<p><a href="'.url('class/classes/new/'.$org->organization_id).'" class="btn btn-primary">Create Class</a></p>';

  $rows = array();
  if (count($courses) > 0) : foreach($courses as $course) :
    $rows[] = array($course->course_name);
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Course Name'),
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));
  
  $return .= '</div><div class="right clearfix">';
  $return .= '<h3>Semesters</h3>';
  $return .= '<p><a href="'.url('admin/pla/semester/new/'.$org->organization_id).'" class="btn btn-primary">Add Semester</a></p>';

  // Semesters
  $semesters = $org->semesters()->get();
  $rows = array();
  if (count($semesters) > 0) : foreach($semesters as $sem) :
    $rows[] = array($sem->semester_name, $sem->semester_start, $sem->semester_end);
  endforeach; endif;
  $return .= theme('table', array(
    'header' => array('Name', 'Start', 'End'),
    'rows' => $rows,
    'attributes' => array('width' => '100%')
  ));

  $return .= '</div></div>';

  return $return;

}