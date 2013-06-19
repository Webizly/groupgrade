<?php
function gg_view_problem()
{
  $return = '';
  $return .= '<h4>Assignment: History of the United States</h4>';
  $return .= '<p><em>Problem created by <strong>John Doe</strong></em></p>';

  $return .= '<p class="lead">What were the major effects of World War II for the United States?</p>';
  return $return;
}

function gg_submit_problem()
{
  $return = '';
  $return .= '<h4>Assignment: History of the United States</h4>';
  $return .= '<p><em>Problem created by <strong>John Doe</strong></em></p>';

  $return .= '<p class="lead">What were the major effects of World War II for the United States?</p>';
  $return .= '<hr />';
  $return .= '<h5>Create Problem</h5>';

  $soln_form = drupal_get_form('gg_submit_problem_form');
  $return .= drupal_render($soln_form);
  return $return; 
}


function gg_submit_problem_form($form, &$form_state)
{
  $items = array();
  $items['solutions-body'] = [
    '#type' => 'textarea',
    '#required' => true,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Problem For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Problem',
  ];
  return $items;
}


function gg_create_solution()
{
  $return = '';
  $return .= '<h4>Assignment: History of the United States</h4>';
  $return .= '<p><em>Problem created by <strong>John Doe</strong></em></p>';

  $return .= '<p class="lead">What were the major effects of World War II for the United States?</p>';
  $return .= '<hr />';
  $return .= '<h5>Create Solution</h5>';

  $soln_form = drupal_get_form('gg_create_solution_form');
  $return .= drupal_render($soln_form);
  return $return;
}

function gg_create_solution_form($form, &$form_state)
{
  $items = array();
  $items['solutions-body'] = [
    '#type' => 'textarea',
    '#required' => true,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Solution For Later',
  ];
  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Solution',
  ];
  return $items;
}


function gg_grade_solution()
{
  $return = '';
  $return .= '<h4>Assignment: History of the United States</h4>';
  $return .= '<p><em>Problem created by <strong>John Doe</strong></em></p>';

  $return .= '<p class="lead">What were the major effects of World War II for the United States?</p>';
  $return .= '<hr />';
  $return .= '<p><em>Solution created by <strong>Jane Adams</strong></em></p>';
  $return .= '<p>Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Donec eget faucibus enim, at luctus libero. Nam a metus aliquet, volutpat quam vel, placerat lectus. Nulla mauris sem, iaculis vel sem id, auctor mattis purus. Etiam imperdiet velit eu commodo pretium. Sed sollicitudin quam urna, non commodo urna dapibus et. Aenean non mi dui. Sed sapien lorem, sodales at elementum vitae, molestie vitae neque. Nam vitae dolor odio. Vivamus dictum diam ut euismod facilisis. Sed facilisis ornare porta. Etiam volutpat, enim a ultricies convallis, felis lacus sodales nibh, id mattis libero nisl ut ligula.</p>';
  $return .= '<hr />';
  $return .= '<h5>Grade Solution</h5>';

  $soln_form = drupal_get_form('gg_grade_solution_form');
  $return .= drupal_render($soln_form);
  return $return;
}

function gg_grade_solution_form($form, &$form_state)
{
  $items = array();
  $items['grade'] = [
    '#type' => 'textfield',
    '#title' => 'Grade (0-100)',
    '#required' => true,
  ];

  $items['justification'] = [
    '#type' => 'textarea',
    '#title' => 'Grade Justification',
    '#required' => true,
  ];

  $items['save'] = [
    '#type' => 'submit',
    '#value' => 'Save Grading For Later',
  ];

  $items['submit'] = [
    '#type' => 'submit',
    '#value' => 'Submit Grading',
  ];
  return $items;
}

function gg_dummy_main()
{
  $return = '';

  return $return;
}
