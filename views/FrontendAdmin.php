<?php
use Drupal\ClassLearning\Models\User;

/**
 * @file
 */

function groupgrade_instructor_dash() {
  $sections = User::sectionsWithRole('instructor')->get();
  return '';
}
