<?php
return array(
// My Tasks/Classes/Assignment
  'class' => array(
    'title' => 'PLA Learning System Dashboard',
    'description' => 'Manage Web Services',
    'page callback' => 'groupgrade_base',
    'page arguments' => array('groupgrade_base'),
    'file' => 'groupgrade_form.inc',
    'file path' => drupal_get_path('module', 'groupgrade'),
    
    // Permissions
    'access callback' => 'groupgrade_custom_access',
    'access arguments' => array('authenticated user'),
  ),

  // Default Parent Task
  // /class/
  'class/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Dashboard',
    'weight' => 1,
  ),

  'class/tasks' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Tasks',
    'page callback' => 'groupgrade_tasks_dashboard',
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(),
    'access callback' => TRUE,

    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 2,
  ),

  'class/classes' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Classes',
    'page callback' => 'groupgrade_classes_dashboard',
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(t('This is the tab "@tabname" in the "basic tabs" example', array('@tabname' => $tabname))),
    'access callback' => TRUE,

    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
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

    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 4,
  ),

  'class/tasks/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Pending',
    'weight' => 1,
  ),

  'class/tasks/completed' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Completed',

    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_tasks_view_specific',
    'page arguments' => array('completed'),
    'access callback' => TRUE,
    'weight' => 2,
  ),

  'class/tasks/all' => array(
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
  ),

  'class/classes/past' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Past Classes',
    
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_classes_view_specific',
    'page arguments' => array('past'),

    'access callback' => TRUE,
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
    
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 5,
  ),

  // Semester
  'class/instructor/%' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'View Section',
    'page callback' => 'groupgrade_view_section',
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
  ),
);
