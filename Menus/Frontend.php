<?php
return array(
// My Tasks/Classes/Assignment
  'class' => array(
    'title' => 'PLA Learning System Dashboard',
    'page callback' => 'groupgrade_tasks_dashboard',
    //'page arguments' => array('groupgrade_tasks_dashboard'),
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    //'access callback' => 'groupgrade_custom_access',
    'access arguments' => array('authenticated user'),
  ),

  // Default Parent Task
  // /class/
  'class/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Tasks',
    'weight' => 1,
  ),

  'class/classes' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Classes',
    'page callback' => 'groupgrade_classes_dashboard',
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(t('This is the tab "@tabname" in the "basic tabs" example', array('@tabname' => $tabname))),
    'access callback' => TRUE,

    'weight' => 3,
  ),

  'class/assignments' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Assignments',
    'page callback' => 'groupgrade_assignments_dashboard',
    'file' => 'Assignments.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(t('This is the tab "@tabname" in the "basic tabs" example', array('@tabname' => $tabname))),
    'access callback' => TRUE,

    'weight' => 4,
  ),

  'class/default/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Pending',
    'weight' => 1,
  ),

  'class/default/completed' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Completed',

    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_tasks_view_specific',
    'page arguments' => array('completed'),
    'access callback' => TRUE,
    'weight' => 2,
  ),

  'class/default/all' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'All',
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_tasks_view_specific',
    'page arguments' => array('all'),
    'access callback' => TRUE,
    'weight' => 3,
  ),

  'class/classes/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Current Classes',
    'weight' => 1,
  ),
  /*
  'class/classes/past' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Past Classes',
    
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_classes_view_specific',
    'page arguments' => array('past'),

    'access callback' => TRUE,
    'weight' => 2,
  ),  */

  'class/classes/all' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'All Classes',
    
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_classes_view_specific',
    'page arguments' => array('all'),

    'access callback' => TRUE,
    'weight' => 3,
  ),

  'class/test' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Testing PLA',
    'page callback' => 'groupgrade_test_form',
    'access callback' => TRUE
  ),

  'class/instructor' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Instructor Management',
    'page callback' => 'groupgrade_instructor_dash',
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),

    'weight' => 5,
  ),

  'class/instructor/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'Dashboard',
  ),

  'class/instructor/assignments' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Assignment Management',
    'page callback' => 'groupgrade_assignment_dash',
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),

    'weight' => 5,
  ),

  'class/instructor/assignments/new' => array(
    'type' => MENU_LOCAL_ACTION,
    'title' => 'New Assignment',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_create_assignment', //drupal_get_form',
    'page arguments' => array(),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),

    'weight' => 3,
  ),

  // ===============================
  // Manage the section
  // ===============================
  'class/instructor/%' => array(
    //'type' => MENU_NORMAL_ITEM,
    'title' => 'Section View',
    'page callback' => 'groupgrade_view_section',
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2),
    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
  ),

  'class/instructor/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'Dashboard',
  ),

  'class/instructor/%/users' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Users',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_user',
    'page arguments' => array(2),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),

  'class/instructor/%/assignments' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Assignments',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_assignments',
    'page arguments' => array(2),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),
);
