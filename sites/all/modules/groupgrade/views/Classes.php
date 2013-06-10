<?php
/**
 * Proxy Function to view the current classes
 */
function groupgrade_classes_dashboard() {
  return groupgrade_classes_view_specific();
}

function groupgrade_classes_view_specific($which = 'current')
{
  $classes = Drupal\ClassLearning\Models\User::classes();
  return '';
}