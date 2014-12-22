<?php

function getDefaults($type, $v){
	
	$defaults = array();
	
	$defaults[$v] = array(
	    '#type' => 'fieldset',
	    '#title' => t($type),
	    '#collapsible' => TRUE,
	    '#collapsed' => FALSE,
	    '#prefix' => '<div style="margin-bottom:50px">',
	    '#suffix' => '</div>',
    );
	
	$defaults[$v]['basic'] = array(
    	'#type' => 'fieldset',
    	'#title' => t('<font color = "#980000"> Basic </font>'),
	    '#collapsible' => TRUE,
	    '#collapsed' => TRUE,
	    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
	    '#suffix' => '</div>',
    );   
	
	$defaults[$v]['basic'][$v . '-TA_type'] = array(
		'#type' => 'hidden',
      	'#value' => $type,
	);
	
	$defaults[$v]['basic'][$v . '-TA_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Task Assignment Name:'),
      '#required' => TRUE,
      '#default_value' => "Create Problem",
      '#description' => "Please enter Task Assignment Name.",
    );
	
	$defaults[$v]['basic'][$v . '-TA_due'] = array(
	   '#type' => 'select',
       '#title' => t('When will this task be due?'),
       '#options' => array(
         0 => t('# of days after last task completes'),
         1 => t('Specific Date'),
		 ),
    );
	
	$defaults[$v]['basic'][$v . '-TA_due_select'] = array(
       '#type' => 'textfield',
       '#title' => 'Enter the amount of days:',
       '#default_value' => '3',
       '#states' => array(
			'visible' => array(
			':input[name="p1-TA_due"]' => array('value' => 0),
		),
	),
	);
	
	$dueInput = sprintf(':input[name="%s"]',$v . '-TA_due');
	
	$defaults[$v]['basic'][$v . '-TA_due_date_select'] = array(
		'#type' => 'date_select',
	    '#date_format' => 'Y-m-d H:i',
	    '#title' => t('Specific Date:'),
	    '#date_year_range' => '-0:+2',
	    // The minute increment.
	    '#date_increment' => '15',
	    '#default_value' => '',
	    '#states' => array(
			'visible' => array(
			$dueInput => array('value' => 1),
			),
		),
	);
	
	$defaults[$v]['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Advanced </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   
   $defaults[$v]['advanced'][$v . '-TA_start_time'] =array(
     '#type' => 'select',
     '#title' => t('Start when prior task completes?'),
     '#options' => array(
         0 => t('Yes'),
         1 => t('No'),
		 ),
   //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   // option "NO"
   $defaults[$v]['advanced'][$v . '-TA_start_time_date_select'] =array(
	   '#type' => 'date_select',
    '#date_format' => 'Y-m-d H:i',
    '#title' => t('Start no earlier than specific date/time'),
    '#date_year_range' => '-0:+2',
    // The minute increment.
    '#date_increment' => '15',
    '#default_value' => '',
    '#states' => array(
		'visible' => array(
		':input[name="p1-TA_start_time"]' => array('value' => 1),
		),
	),
   );
    $defaults[$v]['advanced'][$v . '-TA_at_duration_end'] =array(
	   '#type' => 'select',
       '#title' => t('Task status at duration end'),
       '#options' => array(
         0 => t('Late'),
         1 => t('Complete'),
         2 => t('Resolved'),
		 ),
       '#default_value' => 0,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
    $defaults[$v]['advanced'][$v . '-TA_what_if_late'] =array(
	   '#type' => 'select',
       '#title' => t('What if late?'),
       '#options' => array(
         0 => t('Keep same participant'),
         1 => t('Allocate new participant'),
         2 => t('Allocate to instructor'),
         3 => t('Allocate to different group member'),
	 	 4 => t('Consider resolved'),
		 ),
       '#default_value' => 'Keep same participant',
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $defaults[$v]['advanced'][$v . '-TA_display_name'] = array(
   	'#type' => 'textfield',
    	'#title' => t('Task Assignment Display Name:'),
    	//'#required' => TRUE,
    	'#default_value' => "Create Problem",
    	'#description' => "Please enter Task Assignment Display Name.",	
   );
   $defaults[$v]['advanced'][$v . '-TA_description'] = array(
   		'#type' => 'textarea',
    	'#title' => t('Description'),
    	//'#required' => FALSE,
    	'#description' => "Please enter Description",	
   );
   $defaults[$v]['advanced'][$v . '-TA_one_or_seperate'] = array(
       '#type' => 'select',
       '#title' => t('Everyone gets same problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
  );
   $defaults[$v]['advanced'][$v . '-TA_assignee_constraints'] = array(
       '#type' => 'select',
       '#title' => t('Who does?'),
       '#options' => array(
         0 => t('Student'),
         1 => t('Instructor'),
		 ),   
  );

// option 0

   $defaults[$v]['advanced'][$v . '-TA_assignee_constraints_select'] = array(
        '#type' => 'radios',
        '#title' => 'Individual or group task?',
	'#options' => array (
	  0 => t('Individual'),
	  1 => t('Group'),
		),
        '#default_value' => 0,
	);

   $defaults[$v]['advanced'][$v . '-TA_function_type'] = array(
	
   );

// template

   $defaults[$v]['template'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Template </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );
   $defaults[$v]['template'][$v . '-TA_instructions'] = array(
   	'#type' => 'textarea',
    '#title' => t('Instructions'),
    //'#required' => TRUE,
    '#default_value' => 'Read the assignment instructions and enter '
          .'a problem in the box below. Make your problem as clear as '
          .'possible so the person solving it will understand what you mean. '
          .'This solution is graded out of 100 points.',
    '#description' => "Please enter instructions.",	
   );
   $defaults[$v]['template'][$v . '-TA_rubric'] = array(
   	'#type' => 'textarea',
    '#title' => t('Rubric'),
    //'#required' => TRUE,
    '#default_value' => " ",
    	'#description' => "Please enter Rubric",	
   );


// Supplemental 

  $defaults[$v]['supplemental'] = array(
    '#type' => 'fieldset',
    '#title' => t('<font color = "#980000"> Supplemental </font>'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#prefix' => '<div style="margin-bottom:50px;margin-left:50px">',
    '#suffix' => '</div>',
   );   
   $defaults[$v]['supplemental'][$v . '-TA_comments'] = array(
       '#type' => 'select',
       '#title' => t('Comments?'),
       '#options' => array(
         0 => t('Edit and Comment'),
         1 => t('Just Comment'),
         2 => t('No'),
		 ),
       '#description' => t('Choose One'),
   );
   
   $defaults[$v]['supplemental'][$v . '-TA_allow_revisions'] = array(
       '#type' => 'select',
       '#title' => t('Optionally send back for revisions?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $defaults[$v]['supplemental'][$v . '-TA_allow_grade'] = array(
       '#type' => 'select',
       '#title' => t('Allow Grade?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );
   $defaults[$v]['supplemental'][$v . '-TA_number_participants'] = array(
       '#type' => 'hidden',
       '#title' => t('Number of participants'),
       '#options' => array(
         0 => t('1'),
         1 => t('2'),
         2 => t('3')
		 ),
		 '#default_value' => 1,
       //'#description' => t('Set this to <em>Yes</em> if you would like this category to be selected by default.'),
   );	

   $defaults[$v]['supplemental'][$v . '-TA_allow_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow Dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
   $defaults[$v]['supplemental'][$v . '-TA_allow_resolve_grades'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve grades?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
   $defaults[$v]['supplemental'][$v . '-TA_allow_resolve_dispute'] = array(
       '#type' => 'select',
       '#title' => t('Allow resolve dispute?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
	),
);
	$defaults[$v]['supplemental'][$v . '-TA_leads_to_new_problem'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new problem?'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	$defaults[$v]['supplemental'][$v . '-TA_leads_to_new_solution'] = array(
       '#type' => 'select',
       '#title' => t('Becomes input to a seperate new solution'),
       '#options' => array(
         0 => t('No'),
         1 => t('Yes'),
		 ),
	);
	
	
	
	return $defaults;
}

?>