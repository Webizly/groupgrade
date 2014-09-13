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

  'home' => array(
    'page callback' => 'groupgrade_home',
    //'page arguments' => array('groupgrade_tasks_dashboard'),
    'file' => 'Admin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'access callback' => true,
  ),

  'about' => array(
    'title' => 'About CLASS',
    'page callback' => 'groupgrade_about',
    'file' => 'Admin.php', //Change later
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => true,
  ),
  
  'about2' => array(
    'title' => 'About CLASS',
    'page callback' => 'groupgrade_about2',
    'file' => 'Admin.php', //Change later
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => true,
  ),

  'taskactivity' => array(
    'title' => 'Task Activity Form',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('task_activity_form'),
    'file' => 'Admin.php', //Change later
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => true,
  ),

  'secretpage' => array(
    'title' => 'Secret Page!',
    'page callback' => 'secret_function',
    'page arguments' => array(),
    'file' => 'Admin.php', //Change later
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access callback' => true,
  ),
  /*
  'filetest' => array(
    'title' => 'File Uploading!',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('file_test_form'),
    'access callback' => true,
    'file' => 'Admin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
  ),
*/
  // My Tasks/Classes/Assignment
  'class/reallocate' => array(
    'title' => 'CLASS Learning System',
    'page callback' => 'groupgrade_reassign_to_contig',
    //'page arguments' => array('groupgrade_tasks_dashboard'),
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',
    
    // Permissions
    'access arguments' => array('access administration pages'), 
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

  'class/default/grades' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Grades',
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_user_grades',
    'page arguments' => [],
    'access callback' => TRUE,
    'weight' => 4,
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

  /*
  'class/instructor/assignments/%/allocation' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'View Allocation',
    
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_edit_assignment', 3),

    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 2,
  ),
*/
/*
  // For Instructors AND Administrators
  'class/instructor/assignments/%/allocation' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'View Allocation',
    'page callback' => 'groupgrade_view_allocation',
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3,false),
    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 3,
  ),
*/
  // For Administrators ONLY (Temporarily for instructors too)
  /*
  'class/instructor/assignments/%/administrator-allocation' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Allocation View',
    'page callback' => 'groupgrade_view_allocation',
    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page arguments' => array(3,true),
    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 5,
  ),
  */
  
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

  'class/instructor/%/reports' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Reports',
    
    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_reports',
    'page arguments' => array(2),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
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
  /*
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
 */
 
 //Operations -> Edit start date
 'class/instructor/%/assignment/%' => array(
    'title' => 'Assignment Properties',

    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_edit_assignment_section', 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),
  
  //Operations tab
  'class/instructor/%/assignment/%/operations' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Section-level Actions',
    'weight' => 1,
  ),
 
 //Operations -> Edit Start Date tab
  'class/instructor/%/assignment/%/operations/edit' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Edit Start Date',
    'weight' => 1,
  ),
 
 /*
 //Operations -> Edit start date
  'class/instructor/%/assignment/%/operations/edit' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Edit Start Date',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_fake_functionA',
    //'page arguments' => array(),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),
 */
 
 //Operations -> Remove
 'class/instructor/%/assignment/%/operations/remove' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Remove Assignment from Section',

    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form', //drupal_get_form',
    'page arguments' => array('groupgrade_remove_assignment_section', 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),
  
  //View + Reassign -> All Problem Sets
 'class/instructor/%/assignment/%/view-reassign' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'View + Reassign (V+R)',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_assignment',
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
  
 //All Problem Sets tab
  'class/instructor/%/assignment/%/view-reassign/all' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'V+R Each Problem Set',
    'weight' => 1,
  ),
  
 //View + Reassign -> Late Problem Sets
 'class/instructor/%/assignment/%/view-reassign/late' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'V+R Late Problem Sets',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_timedout',
    'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),
  
  //View and Reassign -> View Task Table (Anonymous)
 'class/instructor/%/assignment/%/view-reassign/table_anonymous' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'View Task Table (Anonymous)',

    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_allocation',
    'page arguments' => array(0, false, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ), 
  
  'class/instructor/%/assignment/%/view-reassign/table_anonymous/%' => array(
    'title' => 'Re-Open Task',
    
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

	'page callback' => 'drupal_get_form',
    'page arguments' => array('groupgrade_retrigger_task_form',7,4),
    
    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 5,
  ),
  
  'class/instructor/%/assignment/%/view-reassign/table/%' => array(
    'title' => 'Re-Open Task',
    
    'file' => 'Tasks.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

	'page callback' => 'drupal_get_form',
    'page arguments' => array('groupgrade_retrigger_task_form',7,4),
    
    'access callback' => 'gg_has_acl_role',
    'access arguments' => array('section-instructor'),
    'weight' => 5,
  ),
  
  //View and Reassign -> View Task Table
 'class/instructor/%/assignment/%/view-reassign/table' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'V+R Task Table',

    'file' => 'AssignmentAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_allocation',
    'page arguments' => array(0, true, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 4,
  ), 
  
  //View and Reassign -> Remove and Reassign
 'class/instructor/%/assignment/%/view-reassign/remove-reassign' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Remove And Reassign Participants',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'drupal_get_form',
    'page arguments' => array('groupgrade_remove_reassign_form',4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 5,
  ), 
  
 /*
 //View + Reassign -> All Problem Set
 'class/instructor/%/assignment/%/view-reassign/all' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'All Problem Sets',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_fake_function2',
    //'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 3,
  ),
  
 
  */
  //Reports
 'class/instructor/%/assignment/%/reports' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Reports',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_view_reports',
    'page arguments' => array(4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 4,
  ),
  
  //Reports -> Student Completed Tasks
 'class/instructor/%/assignment/%/reports/completed' => array(
    'type' => MENU_DEFAULT_LOCAL_TASK,
    'title' => 'Student Completed Tasks',
    'weight' => 1,
  ),
  
  /*
  //Reports -> Dummy!
 'class/instructor/%/assignment/%/reports/dummy' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Dummy!',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_fake_function4',
    //'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 2,
  ),
  */
  //Moodle Integration
 'class/instructor/%/assignment/%/moodle' => array(
    'type' => MENU_LOCAL_TASK,
    'title' => 'Moodle Integration',

    'file' => 'FrontendAdmin.php',
    'file path' => drupal_get_path('module', 'groupgrade').'/views',

    'page callback' => 'groupgrade_moodle',
    //'page arguments' => array(2, 4),

    'access arguments' => array('instructor', 2),
    'access callback' => 'gg_has_role_in_section',
    'weight' => 5,
  ),
 
 /*
 'class/instructor/%/assignment/%/view-reassign/reassign/%' => array(
   'type' => MENU_DEFAULT_LOCAL_TASK,
   'title' => 'Reassign Task',
   
   'file' => 'AssignmentAdmin.php',
   'file path' => drupal_get_path('module', 'groupgrade').'/views',

   'page callback' => 'drupal_get_form',
   'page arguments' => array('gg_reassign_form',4,7),
   
   'access arguments' => array('instructor', 2),
   'access callback' => 'gg_has_role_in_section',
 ),
 */
);
