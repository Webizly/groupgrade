<?php
return array(
  //==================
  // Administrator
  //==================
  'admin/pla' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'PLA Learning Method',
    'page callback' => 'groupgrade_admin_dash',
    'file' => 'Admin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 1,
  ),

  // /class/
  'admin/pla/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Dashboard',
    'weight' => 1,
  ),

  // Organization MGMT
  'admin/pla/organization/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'View Organization',
    'page callback' => 'groupgrade_organization_view',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
  ),

  'admin/pla/organization' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Organizations',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 2,
  ),

  'admin/pla/courses' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Courses',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 3,
  ),

  'admin/pla/courses/sections' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Sections',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access arguments' => array('access administration pages'), 
  ),

  'admin/pla/courses/new/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Create Course',
    'page callback' => 'groupgrade_classes_create',
    'file' => 'ClassAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/pla/courses/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'View Course',
    'page callback' => 'groupgrade_class_view',
    'file' => 'ClassAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/pla/sections/new/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'New Section',
    'page callback' => 'groupgrade_section_view',
    'file' => 'SectionAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/pla/courses/sections' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Sections',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access arguments' => array('access administration pages'), 
  ),

  // Semester
  'admin/pla/semester/new/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Add Semester',
    'page callback' => 'groupgrade_semester_add',
    'file' => 'Semester.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),
);