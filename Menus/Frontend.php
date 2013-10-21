<?php
return array(
// My Tasks/Classes/Assignment
  'class' => array(
    'title' => 'CLASS Learning System',
    'page callback' => 'groupgrade_tasks_dashboard',
    //'page arguments' => array('groupgrade_tasks_dashboard'),
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => 'groupgrade_baseaccess',
  ),

  // My Tasks/Classes/Assignment
  'class/reallocate' => array(
    'title' => 'CLASS Learning System',
    'page callback' => 'groupgrade_reassign_to_contig',
    //'page arguments' => array('groupgrade_tasks_dashboard'),
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => 'groupgrade_baseaccess',
  ),

  // Default Parent Task
  // /class/
  'class/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'My Tasks',
    'weight' => 1,
  ),

  'class/classes' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'My Classes',
    'page callback' => 'groupgrade_classes_dashboard',
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(),
    'access callback' => TRUE,

    'weight' => 3,
  ),

  'class/assignments' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Everyone\'s Work',
    'page callback' => 'groupgrade_assignments_dashboard',
    'file' => 'Assignments.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(),
    'access callback' => TRUE,

    'weight' => 2,
  ),

  'class/assignments/%/%' => array(
    //'type' => MENU_LOCAL_TASK,
    'title' => 'View Assignment',
    'page callback' => 'gg_view_assignment_listing',
    'file' => 'Assignments.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2, 3),
    'access callback' => true,
    'access arguments' => array(2),

  ),

  'class/workflow/%' => array(
    //'type' => MENU_LOCAL_TASK,
    'title' => 'View Workflow',
    'page callback' => 'gg_view_workflow',
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2),
    'access callback' => true,
    'access arguments' => array(2),

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
    'page callback' => 'groupgrade_cron',
    'access callback' => TRUE
  ),

  'class/mockup' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Testing PLA',
    'page callback' => 'groupgrade_test_form',
    'access callback' => TRUE
  ),

  // ==========================
  // Section View
  // ==========================
  /*
  'class/section/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'View Section',
    'page callback' => 'groupgrade_view_section',
    'file' => 'Classes.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2),
    'access callback' => 'gg_has_role_in_section',
    'access arguments' => array('student', 2),

    'weight' => 4,
  ),

  'class/section/%/default' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'View Section',
    'weight' => 1,
  ),
  */

  // ==========================
  // Task View
  // ==========================
  'class/task/%' => array(
    //'type' => MENU_NORMAL_ITEM,
    'title' => 'View Task',
    'page callback' => 'groupgrade_view_task',
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2),
    'access arguments' => array(2),
    'access callback' => true,
  ),
  /*
  'class/task/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'Task',
  ),

  'class/task/%/options' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Options',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_user',
    'page arguments' => array(2),

    'access arguments' => array('instructor', 2),
    'access callback' => true,
    'weight' => 2,
  ),
  */
  // =================================
  // Instructor Mgmt
  // =================================
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
    'title' => 'Sections',
  ),

  // ===================
  // Assignment Mgmt
  // ===================
  'class/instructor/assignments' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Assignments',
    'page callback' => 'groupgrade_assignment_dash',
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),

    'weight' => 5,
  ),

  'class/instructor/assignments/new' => array(
   // 'type' => MENU_LOCAL_ACTION,
    'title' => 'Create Assignment',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_create_assignment'),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
  ),

  'class/instructor/assignments/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'View Assignment',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_assignment', //drupal_get_form',
    'page arguments' => array(3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 1,
  ),

  'class/instructor/assignments/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'View Assignment',
  ),


  'class/instructor/assignments/%/edit' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Edit Assignment',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_edit_assignment', 3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 2,
  ),

  'class/instructor/assignments/%/edit-section/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Edit Section',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_edit_assignment_section', 3, 5),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
  ),

   'class/instructor/assignments/%/remove-section/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Remove Section',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_remove_assignment_section', 3, 5),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
  ),

  'class/instructor/assignments/%/add-section' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Assign to Section',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_add_assignment_section', 3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 3,
  ),

  /**
   * ===============================
   * Manage the Workflows
   * ===============================
   */
  'class/instructor/workflows' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Workflows',
    'page callback' => 'groupgrade_workflow_index',
    'file' => 'Workflows.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access callback' => 'gg_on_local', //'gg_has_acl_role',
    'access arguments' => array('section-instructor'),

    'weight' => 5,
  ),

  'class/instructor/workflows/new' => array(
   // 'type' => MENU_LOCAL_ACTION,
    'title' => 'Create Workflow',
    
    'file' => 'Workflows.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_create_workflow'),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
  ),

  'class/instructor/workflows/%' => array(
    'type' => MENU_NORMAL_ITEM,
    'title' => 'View Workflow',
    
    'file' => 'Workflows.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_workflow', //drupal_get_form',
    'page arguments' => array(3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 1,
  ),

  'class/instructor/workflows/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'View Workflow',
  ),


  'class/instructor/workflows/%/edit' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Edit Workflow',
    
    'file' => 'Workflows.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_edit_workflow', 3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 2,
  ),
  

  // ===============================
  // Manage the section
  // ===============================
  'class/instructor/%' => array(
    //'type' => MENU_NORMAL_ITEM,
    'title' => 'View Section',
    'page callback' => 'groupgrade_view_assignments',
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(2),
    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
  ),

  'class/instructor/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'weight' => 1,
    'title' => 'Assignments',
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

  'class/instructor/%/swap-status/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Users',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_frontend_swap_status',
    'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),

  'class/instructor/%/remove-from-section/%' => array(
    'type' => MENU_CALLBACK,
    'title' => 'Users',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_frontend_remove_user_section',
    'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),

  // View Section Assignments
  'class/instructor/%/assignment/%' => array(
    'title' => 'View Assignment',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_assignment',
    'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),

  'class/instructor/%/assignment/%/first' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'View Assignment',
    'weight' => 1,
  ),

  'class/instructor/%/assignment/%/timed-out' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Late Workflows',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_timedout',
    'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),

   // View a workflow inside of an assignment
   'class/instructor/%/assignment/%/%' => array(
    'title' => 'View Workflow',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_assignmentworkflow',
    'page arguments' => array(2, 4, 5),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),

   'class/instructor/%/reports' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Reports',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_user',
    'page arguments' => array(2),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),
);
