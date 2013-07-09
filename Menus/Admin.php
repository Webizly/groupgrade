<?php
return array(
  //==================
  // Administrator
  //==================
  'admin/class' => array(
    'title' => 'CLASS Learning Method',
    'page callback' => 'groupgrade_organization_main',// 'groupgrade_admin_dash',
    'file' => 'Organization.php',// 'Admin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 1,

    'menu_name' => 'primary-links'
  ),
  /*
  // /class/
  'admin/class/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Dashboard',
    'weight' => 1,
  ),
  
  // Organization MGMT
  'admin/class/organization' => array(
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
  */
  'admin/class/organization/new' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Create New Organization',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('groupgrade_organization_new'),
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 2,
  ),
  'admin/class/organization/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'View Organization',
    'page callback' => 'groupgrade_organization_view',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
  ),

  'admin/class/courses' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Courses',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    //'access callback' => TRUE,
    'access arguments' => array('access administration pages'), 
    // The weight property overrides the default alphabetic ordering of menu
    // entries, allowing us to get our tabs in the order we want.
    'weight' => 5,
  ),

  'admin/class/section/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Sections',
    'page callback' => 'groupgrade_view_section',
    'file' => 'SectionAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/class/courses/new/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Create Course',
    'page callback' => 'groupgrade_classes_create',
    'file' => 'ClassAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/class/section/remove-user/%/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Create Course',
    'page callback' => 'groupgrade_remove_user_section',
    'file' => 'SectionAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4, 5),
    'access arguments' => array('access administration pages'), 
  ),
  
  'admin/class/courses/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'View Course',
    'page callback' => 'groupgrade_class_view',
    'file' => 'ClassAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/class/sections/new/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'New Section',
    'page callback' => 'groupgrade_section_view',
    'file' => 'SectionAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),

  'admin/class/courses/sections' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Sections',
    'page callback' => 'groupgrade_organization_main',
    'file' => 'Organization.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access arguments' => array('access administration pages'), 
  ),

  // Semester
  'admin/class/semester/new/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'Add Semester',
    'page callback' => 'groupgrade_semester_add',
    'file' => 'Semester.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(4),
    'access arguments' => array('access administration pages'), 
  ),
);
