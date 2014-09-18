<?php
namespace Drupal\ClassLearning\Workflow;

use Drupal\ClassLearning\Models\SectionUsers,
  Drupal\ClassLearning\Models\Section,
  Drupal\ClassLearning\Models\Semester,
  Drupal\ClassLearning\Models\Workflow,
  Drupal\ClassLearning\Models\WorkflowTask,
  Drupal\ClassLearning\Models\AssignmentSection,
  Drupal\ClassLearning\Models\Assignment,
  Drupal\ClassLearning\Models\Course,

  Drupal\ClassLearning\Workflow\Allocator,
  Drupal\ClassLearning\Workflow\TaskFactory,

  Drupal\ClassLearning\Exception as ManagerException,
  Illuminate\Database\Capsule\Manager as Capsule,
  Carbon\Carbon;

/**
 * Manager of the Workflow
 *
 * @package groupgrade
 * @subpackage workflows
 */
class Manager {
  /**
   * Check to see if a task should timeout
   *
   * Tasks have a certain time length to them and will timeout
   * if not completed within a certain time frame.
   *
   * @access public
   */
  public static function checkTimeoutTasks()
  {
    $tasks = WorkflowTask::whereIn('status', ['triggered', 'started'])
      ->get();

    if (count($tasks) > 0) : foreach ($tasks as $task)
      self::checkTimeoutTask($task);
    endif;
  }

  /**
   * Check the timeout of an individual task instance
   *
   * @access public
   * @param WorkflowTask
   */
  public static function checkTimeoutTask(WorkflowTask $task)
  {
    $forceEnd = $task->timeoutTime();

    if ($forceEnd->isPast())
    {
      // It's timed out
      $task->timeout();
    }
  }

  /**
   * Check on Task Instances
   * 
   * Checks on the non-trigger tasks to see if they should be
   * triggered to start.
   *
   * Checks on the triggered to tasks to see if they should
   * be expiring.
   *
   * This is handled by the cron
   * 
   * @access public
   */
  public static function checkTaskInstances()
  {
    // Check expired
    $tasksOngoing = WorkflowTask::whereIn('status', [
        'not triggered', 'triggered', 'started',
    ])
      ->get();

    if (count($tasksOngoing) > 0) {
      foreach ($tasksOngoing as $task)
        self::checkTaskInstance($task);
    }
  }

  /**
   * Check on a Task Instance
   * 
   * Checks on a task to see if it should be triggered to start.
   * 
   * @access public
   */
  public static function checkTaskInstance(WorkflowTask $task)
  {
    if ($task->triggerConditionsAreMet())
      $task->trigger();

    if ($task->expireConditionsAreMet())
      $task->expire();
  }

  /**
   * Notify the user of a status change on their assigned tasks
   *
   * @param string Type of change (triggered/expiring/expired)
   * @param WorkflowTask Workflow task to work with
   * @throws Drupal\ClassLearning\Exception
   */
  public static function notifyUser($event, &$task)
  {
    global $base_url;

	$asec = $task->assignmentSection->first();
	
	//Don't even
	if($asec->asec_id == 81 || $asec->asec_id == 90)
	  return;

    // Nobody to notify
    if ($task->user_id == NULL) return;

    // Determine a few things
    $subject = $body = '';
    $user = user_load($task->user_id);

    // Determine if they're a real user or a test dummy user
    $email = $user->mail;

    if (empty($email) OR $email == NULL OR ! filter_var($email, FILTER_VALIDATE_EMAIL)) return;
    list($base, $domain) = explode('@', $email, 2);

    $noSendDomains = [
      'njit-class.seanfisher.co',
      'groupgrade.dev',
      'email.com',
      'fakeemail.com',
    ];

    //if (in_array($domain, $noSendDomains))
    //  return;
    //  
    $action_human = self::humanTaskName($task->type);

    // Run all the queries and get all the information we need!
    $workflow = $task->workflow()->first();

    if (! is_object($workflow))
      die(var_dump('workflow not object', $workflow, $task));

    $assignmentSection = $workflow->assignmentSection()->first();
    $assignment = $assignmentSection->assignment()->first();
    $section = $assignmentSection->section()->first();
    $course = $section->course()->first();
    $semester = $section->semester()->first();

    $courseSectionSemester = sprintf('%s &mdash; %s &mdash; %s',
      $course->course_name,
      $section->section_name,
      $semester->semester_name
    );

    $endDate = $task->timeoutTime();
    $dueDate = $endDate->toDayDateTimeString() . ' ('.$endDate->diffForHumans().')';

    $taskURL = $base_url.url('class/task/'.$task->task_id);

    // triggered/expiring/expired
    switch ($event)
    {
      case 'triggered' :
        $subject = sprintf('[%s] %s %s %s %s',
          variable_get('site_name', 'CLASS Development'),
          t('New'),
          $action_human,
          t('task assigned for'),
          $courseSectionSemester
        );
		if($course->course_name == ' PHIL 334' || $course->course_name == 'CS 101')
		{
	        $body = sprintf('Hello,
	
			This is a notification from %s. You have been assigned to the following task.
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			Log in from Moodle to complete your task.
			
			Please complete this as soon as possible.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
			        );
		}
		else{
			$body = sprintf('Hello,

			This is a notification from %s. You have been assigned to the following task.
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			<a href="%s">Click here</a> to work on your task.
			
			Please complete this as soon as possible.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
        );
		}
        break;

      case 'expiring' :
        $subject = sprintf('[%s] %s %s %s %s',
          variable_get('site_name', 'CLASS Development'),
          t('Now late for'),
          $action_human,
          t('for'),
          $courseSectionSemester
        );
		if($course->course_name == ' PHIL 334' || $course->course_name == 'CS 101')
		{
			$body = sprintf('Hello,
			
			This is a notification from %s that you are late for the following task:
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			Log in from Moodle to complete your task.
			
			Please complete this as soon as possible. You are now holding up your peers, who need to work on the task that follows yours.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
			        );
		}
		else{
			$body = sprintf('Hello,
			
			This is a notification from %s that you are late for the following task:
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			<a href="%s">Click here</a> to work on your task.
			
			Please complete this as soon as possible. You are now holding up your peers, who need to work on the task that follows yours.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
			        );
		}
        break;

      case 'expired' :
        $subject = sprintf('[%s] %s %s %s %s',
          variable_get('site_name', 'CLASS Development'),
          t('Now late for'),
          $action_human,
          t('for for'),
          $courseSectionSemester
        );
		if($course->course_name == ' PHIL 334' || $course->course_name == 'CS 101')
		{
			$body = sprintf('Hello,
			
			This is a notification from %s. You are now marked as late for the task. You may still continue working on the task and submit it, though it will have 
			a late flag appended to it.
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			Log in from Moodle to complete your task.
			
			If you have any questions, please contact your instructor.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
			        );
		}
		else{
			$body = sprintf('Hello,
			
			This is a notification from %s. You are now marked as late for the task. You may still continue working on the task and submit it, though it will have 
			a late flag appended to it.
			
			Course: %s
			Assignment: %s
			Task: %s
			Due: %s
			<a href="%s">Click here</a> to work on your task.
			
			If you have any questions, please contact your instructor.
			
			Thanks!',
			          variable_get('site_name', 'CLASS Development'),
			          $courseSectionSemester,
			          $assignment->assignment_title,
			          $action_human,
			          $dueDate,
			          $taskURL
			        );
		}
        break;

      default :
        throw new ManagerException(sprintf('Unknown event type %s to notify for task %d', $event, $task->task_id));
    }

    $from = variable_get('site_mail', 'noreply@groupgrade.dev');

    // Pass the body though nl2br to make new lines to cut back on HTML above.
    $body = nl2br($body);
    $params = compact('body', 'subject', 'event', 'task');

    $result = drupal_mail('groupgrade', 'notify '.$event, $user->mail, language_default(), $params, $from, TRUE);
    
    if (! $result['result'])
      throw new ManagerException(
        sprintf('Error notifing user for task %s %s : %s', $event, $task->task_id, print_r($result))
      );
    else
      return TRUE;
  }

  /**
   * Check on all assignments
   *
   * @return void
   */
  public static function checkAssignments()
  {
    // Remove all workflows
    // For debugging the allocator
    // 
    // $c = Capsule::connection();
    // $c->statement('delete from `pla_task` WHERE `workflow_id` IN ( SELECT `workflow_id` FROM `pla_workflow` WHERE `assignment_id` = 6 );');
    // $c->statement('delete  FROM `pla_workflow` WHERE `assignment_id` = 6;');

    $assignments = AssignmentSection::whereNull('asec_end')
      ->get();

    if (count($assignments) > 0) :
      foreach ($assignments as $a)
        self::checkAssignment($a);
    endif;
  }

  /**
   * Check to see if an assignment section should be triggered to start
   * 
   * @param AssignmentSection
   * @return void
   */
  public static function checkAssignment(AssignmentSection &$assignment)
  {
    $date = Carbon::createFromFormat('Y-m-d H:i:s', $assignment->asec_start);

    // Did it pass yet?
    if ($date->isPast() AND ! self::isStarted($assignment))
      return self::trigger($assignment);
    else
      return FALSE;
  }

  /**
   * See if an assignment has already been triggered to start
   *
   * @param AssignmentSection
   * @return bool
   */
  public static function isStarted(AssignmentSection $a)
  {
    return (Workflow::where('assignment_id', '=', $a->asec_id)->count() > 0) ? TRUE : FALSE;
  }

  /**
   * Trigger the start of a assignment's processing
   *
   * @param AssignmentSection
   * @return mixed
   */
  public static function trigger(AssignmentSection &$a)
  {
    $workflows = [];

	//This might not be the best choice of implementation, but query assignment table to get assignment_usecase
	$assignment = Assignment::where('assignment_id', '=', $a->assignment_id)
	  ->first(); 

    $users = SectionUsers::where('section_id', '=', $a->section_id)
      ->where('su_status', '=', 'active')
      ->where('su_role', '=', 'student')
      ->get();


    // We're just creating a workflow for each user
    // They're not actually assigned to this workflow
    foreach($users as $null) :
      $w = new Workflow;
      $w->type = $assignment->assignment_usecase;
      $w->assignment_id = $a->asec_id;
      $w->workflow_start = Carbon::now()->toDateTimeString();
      $w->save();

      // Create the workflows tasks
      self::triggerTaskCreation($w, $a, $users);

      $workflows[] = $w;
    endforeach;

    // New Allocator
    $allocator = new Allocator();

    // Add the roles
    foreach (self::getTasks($a) as $role_name => $role)
    {
      if (! isset($role['internal']) OR ! $role['internal']) :
        $count = 1;

        if (isset($role['count']))
          $count = (int) $role['count'];

		// With the new 'behavior' feature we're adding, this is bound to cause problems.
		$n = null;
		if(isset($role['behavior']))
			$n = $role['behavior'];
		else
			$n = $role_name;
		
        for ($i = 0; $i < $count; $i++)
          $allocator->createRole($n, $role);
      endif;
    }

    // Setup the user pools for the allocation
    foreach (self::getUserRoles() as $role) :
      $users = SectionUsers::where('section_id', '=', $a->section_id)
        ->where('su_status', '=', 'active')
        ->where('su_role', '=', $role)
        ->get();

      $allocator->addPool($role, $users);
    endforeach;

    // Add the workflows from the database
    foreach ($workflows as $workflow)
      $allocator->addWorkflow($workflow->workflow_id);

    $run = $allocator->assignmentRun();
    //$allocator->dump();

    // Now we have to intepert the response of the allocator
    $taskInstances = $run->getTaskInstanceStorage();
    $allocatorWorkflows = $run->getWorkflows();

    foreach ($allocatorWorkflows as $workflow_id => $workflow)
    {
      foreach ($workflow as $role_id => $assigned_user)
      {
        $taskInstanceId = $taskInstances[$workflow_id][$role_id];
        $taskInstance = WorkflowTask::find($taskInstanceId);

        if ($taskInstance == NULL)
          throw new ManagerException(
            sprintf('Task instance %d cannot be found for workflow %d', $taskInstanceId, $workflow_id));

        if (! is_object($assigned_user))
          watchdog(WATCHDOG_INFO, 'Assigned user type is not object', [$assigned_user, $taskInstance]);

        $taskInstance->user_id = (is_object($assigned_user)) ? $assigned_user->user_id : NULL;
        echo "Assigning user ".print_r($assigned_user, true).' to task #'.$taskInstance->task_id;
        $taskInstance->save();
      }
    }
  }

  /**
   * Trigger Task Creation
   *
   * @todo Make this dynamic in that it will trigger tasks based upon the workflow type. Also specify task time
   * @access protected
   * @param Workflow
   * @param AssignmentSection
   * @param SectionUsers
   */
  protected static function triggerTaskCreation($workflow, $assignment, $users)
  {
    $factory = new TaskFactory($workflow, self::getTasks($assignment));
    $factory->createTasks();
  }

  /**
   * Get the workflow tasks
   *
   * @return array
   */
  public static function getTasks($asec)
  {
	/*
	$sql = "select data from pla_usecase where type = :type";
	$query = db_query($sql, array(':type' => $type));
	
	$result = $query->fetchAssoc();
	//watchdog(WATCHDOG_INFO, 'What is stuff? ' . print_r(unserialize($result['data'])));
	
	return unserialize($result['data']);
	*/
	
  	//In the future, this should query the database for sets of tasks, but for now...
  	
  	//Find section
    $sec = Section::where('section_id','=', $asec->section_id)
	  ->first();
	
	//Use section to find course
	$course = Course::where('course_id','=', $sec->course_id)
	  ->first();
	
	if($course->course_name == 'IS 402')
	{
		return [
      'create problem' => [
        'duration' => 3,
        'trigger' => [
          [
            'type' => 'first task trigger',
          ]
        ],
        'user alias' => 'grade solution',
        'instructions' => '<p><strong>Write a question about a societal issue that involves computing.</strong></p>'
        
        .'<u>General guidelines for creating a question:</u>
 
<ul><li>Pick a topic that has something to do with computing.</li>

<li>The topic should have some interesting issues concerning how it affects people or society as a whole.  You don\'t have to actually mention these issues in your question.</li>

<li>Is there enough substance to the topic for someone to write a thoughtful reply?  You are looking for a substantial enough question that someone should take two to three paragraphs to respond to.</li>

<li>Write an interesting question about the topic.</li></ul>',
      ],
      'edit problem' => [
        'pool' => [
          'name' => 'instructor',
          'pull after' => false,
        ],
        'duration' => 2,
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'create problem',
            'task status' => 'complete',
          ],
        ],
        'reference task' => 'create problem',
        'instructions' => 'Rephrase the problem (if necessary) so it is '
          .'appropriate to the assignment and clear to the person solving '
          .'it. The solver and graders will only see your edited version, not '
          .'the original version. (Others not involved in solving or grading '
          .'will see both the original and edited versions.) You can also '
          .'leave a comment to explain any rephrasing.',
      ],
      'create solution' => [
        'duration' => 3,
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'edit problem',
            'task status' => 'complete',
          ],
        ],
        'user alias' => 'dispute',
        'reference task' => 'edit problem',
        'instructions' => '<p><strong>Respond to the question in 2 to 3 paragraphs.</strong></p>
        
<ul><li>Your response should include:</li>

<ul><li><span style="color:#FF0000;">Accuracy:</span> Does your response specifically concern the situation that you have been asked about? Is it complete enough?</li>

<li><span style="color:#FF0000;">Issue:</span> Do you accurately describe and discuss a societal or ethical aspect of the situation that you have been asked about in some depth?</li>

<li><span style="color:#FF0000;">Writing:</span> Is your writing well organized? Are there grammatical errors? </li></ul></ul>',
      ],
      'grade solution' => [
        'count' => 2,
        'duration' => 3,
        'user alias' => 'create problem',
        // This configuration variable defines if the role of the grade solution
        // should take over multiple instances of the task instance.
        // 
        // If there are two instances of 'grade solution', setting this to true will
        // make sure that only one get's an alias. Setting it to false will make it
        // it an alias for all the roles.
        'user alias all types' => true,
// Just for grade solution tasks. How should this grade be set up?
'criteria' => [
  'Accuracy' => [
    'max' => 40,
    'description' => 'Judge the accuracy of this response.',
    'grade' => 0,
    'justification' => '',
    'additional-instructions' => '
    <p><strong>A Level (score = 40):</strong>All of the information necessary for the answer is present and correct.  The response specifically concerns the situation posed in the question.</p>

<p><strong>B Level (score = 34):</strong> Most of the information necessary for the answer is present and correct. The response specifically concerns the situation posed in the question.</p>

<p><strong>C Level (score = 30):</strong> (any of the following) Some of the information is incorrect. Some of the information necessary for the answer is missing. The response does not concern the situation posed in the question. OR Details may be missing that are required for the reader to understand the proposed solution.</p>

<p><strong>D Level (score = 24):</strong> Most of the information is inaccurate or missing.</p>

<p><strong>F Level (score = 0):</strong> No attempt is made to explain the situation that was asked about.</p>
    ',
  ],
  
  'Issue' => [
    'max' => 40,
    'description' => 'Judge the societal or ethical aspect of this response.',
    'grade' => 0,
    'justification' => '',
    'additional-instructions' => '
    <p><strong>A Level (score = 40):</strong> The response accurately describes and discusses a societal or ethical aspect of the situation asked about in enough depth.</p>

<p><strong>B Level (score = 34):</strong> The response describes and discusses a societal or ethical aspect of the situation asked about, but with one or two minor errors, or not in quite enough depth.</p>

<p><strong>C Level (score = 30):</strong> The response describes and discusses a societal or ethical aspect of the situation asked about, but with several errors, or not in enough depth. </p>

<p><strong>D Level (score = 24):</strong> Most of the discussion about a societal or ethical issue is inaccurate or missing.</p>
<p><strong>F Level (score = 0):</strong> No attempt is made to discuss a societal or ethical issue.</p>
',
  ],
  
  'Writing' => [
    'max' => 20,
    'description' => 'Judge how well the response is written.',
    'grade' => 0,
    'justification' => '',
    'additional-instructions' => '
    <p><strong>A Level (score = 20):</strong> No grammatical errors and at most 2 proof reading errors, and paragraphs are significantly rich enough to answer the question fully.</p>
<p><strong>B Level (score = 17):</strong> Three or four grammatical, spelling or proofreading errors, and paragraphs are organized and mostly stay on topic.</p>
<p><strong>C Level (score = 15):</strong> Five to ten grammatical, spelling or proof reading errors, or the answer is divided into paragraphs but the paragraphs are not tightly focused and stray from the question\'s topic.</p>
<p><strong>D Level (score = 12):</strong> Many grammatical or spelling errors, or no paragraph development and no development of argumentation.</p>
<p><strong>F Level (score = 0):</strong> The writing is incoherent to the point of not making sense.</p>
',
  ],
],
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'create solution',
            'task status' => 'complete',
          ],
        ],
        'reference task' => 'create solution',
        'instructions' => '<p>Grade the solution to the specific problem shown '
          .'above. (There are several different problems so be sure to read '
          .'the one being solved here.) Each grade has several parts. Give '
          .'a score and an explanation of that score for each part of the '
          .'grade. Your explanation should be detailed, and several sentences '
          .'long.</p>'
          
          .'<p>Evaluate these questions on three criteria:</p>
          <ul><li>Accuracy (40 Points)</li>
          <li>Issue (40 Points)</li>
          <li>Writing (20 Points)</li></ul>',
      ],
      // Resolve the grades
      'resolve grades' => [
        'internal' => true,
        // Default value
        'value' => true,
        // Trigger once all the grades are submitted
        'trigger' => [
          [
            'type' => 'reference task status',
            'task type' => 'grade solution',
            'task status' => 'complete',
          ],
        ],
        'reference task' => 'grade solution',
      ],
      // Grades are fine, store them in the workflow
      'grades ok' => [
        'internal' => true,
        'trigger' => [
          [
            'type' => 'compare value of task',
            'task type' => 'resolve grades',
            'compare value' => true,
          ]
        ],
        'reference task' => 'grade solution',
        
        // Expire if grades are out of range
        'expire' => [
          [
            'type' => 'compare value of task',
            'task type' => 'resolve grades',
            'compare value' => false,
          ]
        ],
      ],
      // Grades are out of a range and we need a second grader
      'resolution grader' => [
        'duration' => 3,
        'trigger' => [
          [
            'type' => 'compare value of task',
            'task type' => 'resolve grades',
            'compare value' => false,
          ]
        ],
        // Expire if grades are in range
        'expire' => [
          [
            'type' => 'compare value of task',
            'task type' => 'resolve grades',
            'compare value' => true,
          ]
        ],
        'reference task' => 'create solution',
        'instructions' => 'Because the regular graders did not give the same '
          .'grade, please resolve the grade disagreement. Assign your '
          .'own score and justification for each part of the grade, and afterwards '
          .'summarize why you resolved it this way.',
      ],
      // Dispute grades
      // This step gives the option to dispute the grade they have recieved on their
      // soln to yet-another-grader
      'dispute' => [
        'duration' => 2,
        'user alias' => 'create solution',
        // Trigger this if one of the tasks "resolution grader" or
        // "grades ok" is complete.
        'trigger' => [
          [
            'type' => 'check tasks for status',
            'task types' => ['resolution grader', 'grades ok'],
            'task status' => 'complete'
          ],
        ],
        'instructions' => 'You have the option to dispute your grade. To do '
          .'so, you need to fully grade your own solution. Assign your own '
          .'score and justification for each part of the grade. You must also '
          .'explain why the other graders were wrong.',
      ],
      // Resolve a dispute and end the workflow
      // Trigger only if the "dispute" task has a value of true
      'resolve dispute' => [
        'pool' => [
          'name' => 'instructor',
          'pull after' => false,
        ],
        'duration' => 2,
        'trigger' => [
          [
            'type' => 'compare value of task',
            'task type' => 'dispute',
            'compare value' => true,
          ],
        ],
        'instructions' => 'The problem solver is disputing his or her grade. '
          .'You need to provide the final grade. Assign a final score with '
          .'justification for each part of the grade, and also please provide '
          .'an explanation.',
      ],
    ];
}
	
	if($asec->assignment_id == 77) //76 - 1
	{
		return [
	      'create problem' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'first task trigger',
	          ]
	        ],
	
			'file' => 'mandatory',
	
			'optional' => true,
	
	        'user alias' => 'grade solution',
	
	        'instructions' => '<p><strong>Create variable names and algebraic expressions as explained below, but do not use MatLab yet.<em>(Homework 2 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em></strong></p>'
	        
	        .' 
			
				<ol>
				<li><strong>Part 1:</strong> Create 5 variable names. Three names must be invalid MatLab variable names, and two names must be valid MatLab variable names. Each of the three invalid variable names must violate a different rule, e.g. starting with a digit, starting with an underscore, or having punctuation characters.</li>
				
				<li>Mix these up so the valid and invalid names match the example document\'s format on Moodle.</li>
				
				
				
				<li><strong>Part 2:</strong> Build 8 algebraic expressions with numbers, variables, algebraic operations, and/or functions. Each expression should meet the following requirements:
				<ul>
				<li>It should be at least 10 characters long.</li>
				<li>The numbers must be single-digit integers.</li>
				<li>The algebraic operations in it can be chosen from addition, subtraction, multiplication, division, exponentiation and absolute value.</li>
				<li>If it includes functions, the functions should be selected from sin, cos, tan, log (base 10 logarithm), and ln (natural logarithm).</li>
				<li>It should be mathematically legitimate.</li>
				</ul>
				</li>
				
				<li>
				Type the expressions into a MS Word document using its equation editor.
				</li>
				
				<li>
				Number these 6-13 as shown in the example\'s document format on Moodle.
				</li>
				
				<li>
				Ensure your Word document is anonymous.
				</li>
				
				<li>
				Upload the document and then click submit.
				</li>
				
				</ol>
				
				',
	      ],
	
	      'edit problem' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],
	
	        'duration' => 2,
	
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create problem',
	            'task status' => 'complete',
	          ],
	        ],
	
			'file' => 'optional',
	
	        'reference task' => 'create problem',
	        'instructions' => '
	        <ol>
	        
			<li>
	        Edit any of the algebraic instructions as necessary, upload the edited document here, and in the comments box below explain why you made changes. If no edits are necessary, type “Approved” in the comments box.
	        </li>
	        
			<li>
			Click on the submit button.
			</li>
			
	        </ol>
	        ',
	      ],
	
	      'create solution' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'edit problem',
	            'task status' => 'complete',
	          ],
	        ],
	
			'file' => 'mandatory',
	
	        'user alias' => 'dispute',
	
			'optional' => true,
	
	        'reference task' => 'edit problem',
	        'instructions' => '
	        <p><strong>Create solutions to Part 1 and Part 2 following the template in the instructions on Moodle.<em>(Homework 2 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em></strong></p>
	        
			<ol>
			<li>For each question in part 1, determine whether the name is a valid or invalid MatLab variable name. <strong>If it is invalid, clearly explain why it is not valid.</strong></li>
			<li>For each question in part 2, write the corresponding MatLab expression under each algebraic equation in the Word document. (If you want to use MatLab to evaluate the expression, you need to initialize the corresponding variables first (i.e. assign values to them) before you type in the expression and press ‘enter’.)</li>
			<li>Confirm that this Word document is anonymous.</li>
			<li>Upload the document and then click submit.</li>
			</ol>
	        ',
	      ],
	
	      'grade solution' => [
	        'count' => 2,
	        'duration' => 3,
	        'user alias' => 'create problem',
	
	        // This configuration variable defines if the role of the grade solution
	        // should take over multiple instances of the task instance.
	        // 
	        // If there are two instances of 'grade solution', setting this to true will
	        // make sure that only one get's an alias. Setting it to false will make it
	        // it an alias for all the roles.
	        'user alias all types' => true,
	
			// Just for grade solution tasks. How should this grade be set up?
			'criteria' => [
			  'Question1' => [
			    'max' => 4,
			    'description' => 'Provide a grade for Question 1.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question2' => [
			    'max' => 4,
			    'description' => 'Provide a grade for Question 2.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question3' => [
			    'max' => 4,
			    'description' => 'Provide a grade for Question 3.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question4' => [
			    'max' => 4,
			    'description' => 'Provide a grade for Question 4.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question5' => [
			    'max' => 4,
			    'description' => 'Provide a grade for Question 5.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question6' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 6.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question7' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 7.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question8' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 8.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question9' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 9.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question10' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 10.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question11' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 11.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question12' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 12.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question13' => [
			    'max' => 8,
			    'description' => 'Provide a grade for Question 13.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			],
	
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create solution',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'reference task' => 'create solution',
	        'instructions' => '
	        
			<p><strong>Grade each solution in Part 1 and in Part 2. There is no partial credit.</strong></p>
			
			<br><p><strong>Part 1:</strong> For each variable name, check the answers:</p>
			<ul>
			
			<li>
			For a valid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is valid. (The solution does not need to explain why the name is valid.)</li>
				<li>Give 0 points if the solution states that the name is invalid.</li>
				</ul>
			</li>
			
			<li>
			For an invalid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is invalid, AND explains the correct reason why it is invalid.</li>
				<li>Give 2 points if the solution states that the name is invalid, but the correct reason is not given.</li>
				<li>Give 0 points if the solution states that the name is valid.</li>
				</ul>
			</li>
			
			</ul>
			
			<br><p><strong>Part 2:</strong> For each expression, carefully compare the MatLab expression with the original algebraic expression. Does the MatLab expression correctly represent the original algebraic expression?</p>
			<ul>
			
			<li>
			If the solution is correct:
				<ul>
				<li>Give 8 points.</li>
				<li>State OK in the justification box.</li>
				</ul>
			</li>
			
			<li>
			If the MatLab expression does not match the original algebraic expression OR is otherwise incorrect:
				<ul>
				<li>Give 0 points.</li>
				<li>In the justification box, clearly explain what was wrong and give a correct MatLab expression.</li>
				</ul>
			</li>
			
			</ul>
			
	        '
	      ],
	
	      // Resolve the grades
	      'resolve grades' => [
	        'internal' => true,
	
	        // Default value
	        'value' => true,
	
	        // Trigger once all the grades are submitted
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'grade solution',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'reference task' => 'grade solution',
	      ],
	
	      // Grades are fine, store them in the workflow
	      'grades ok' => [
	        'internal' => true,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],
	
	        'reference task' => 'grade solution',
	        
	        // Expire if grades are out of range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],
	      ],
	
	      // Grades are out of a range and we need a second grader
	      'resolution grader' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],
	
	        // Expire if grades are in range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],
	
	        'reference task' => 'create solution',
	        'instructions' => '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong><br>'
	          
	          .
	          
			  '
	        
			<p><strong>Grade each solution in Part 1 and in Part 2. There is no partial credit.</strong></p>
			
			<br><p><strong>Part 1:</strong> For each variable name, check the answers:</p>
			<ul>
			
			<li>
			For a valid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is valid. (The solution does not need to explain why the name is valid.)</li>
				<li>Give 0 points if the solution states that the name is invalid.</li>
				</ul>
			</li>
			
			<li>
			For an invalid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is invalid, AND explains the correct reason why it is invalid.</li>
				<li>Give 2 points if the solution states that the name is invalid, but the correct reason is not given.</li>
				<li>Give 0 points if the solution states that the name is valid.</li>
				</ul>
			</li>
			
			</ul>
			
			<br><p><strong>Part 2:</strong> For each expression, carefully compare the MatLab expression with the original algebraic expression. Does the MatLab expression correctly represent the original algebraic expression?</p>
			<ul>
			
			<li>
			If the solution is correct:
				<ul>
				<li>Give 8 points.</li>
				<li>State OK in the justification box.</li>
				</ul>
			</li>
			
			<li>
			If the MatLab expression does not match the original algebraic expression OR is otherwise incorrect:
				<ul>
				<li>Give 0 points.</li>
				<li>In the justification box, clearly explain what was wrong and give a correct MatLab expression.</li>
				</ul>
			</li>
			
			</ul>
			
	        '
	          ,
	      ],
	
	      // Dispute grades
	      // This step gives the option to dispute the grade they have recieved on their
	      // soln to yet-another-grader
	      'dispute' => [
	        'duration' => 2,
	        'user alias' => 'create solution',
	
	        // Trigger this if one of the tasks "resolution grader" or
	        // "grades ok" is complete.
	        'trigger' => [
	          [
	            'type' => 'check tasks for status',
	            'task types' => ['resolution grader', 'grades ok'],
	            'task status' => 'complete'
	          ],
	        ],
	
	        'instructions' => '<strong>You have the option to dispute your grade. To do '
	          .'so, you need to fully grade your own solution. Assign your own '
	          .'score and justification for each part of the grade. You must also '
	          .'explain why the other graders were wrong.</strong><br>'
	          
	          .
	          
			  '
	        
			<p><strong>Grade each solution in Part 1 and in Part 2. There is no partial credit.</strong></p>
			
			<br><p><strong>Part 1:</strong> For each variable name, check the answers:</p>
			<ul>
			
			<li>
			For a valid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is valid. (The solution does not need to explain why the name is valid.)</li>
				<li>Give 0 points if the solution states that the name is invalid.</li>
				</ul>
			</li>
			
			<li>
			For an invalid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is invalid, AND explains the correct reason why it is invalid.</li>
				<li>Give 2 points if the solution states that the name is invalid, but the correct reason is not given.</li>
				<li>Give 0 points if the solution states that the name is valid.</li>
				</ul>
			</li>
			
			</ul>
			
			<br><p><strong>Part 2:</strong> For each expression, carefully compare the MatLab expression with the original algebraic expression. Does the MatLab expression correctly represent the original algebraic expression?</p>
			<ul>
			
			<li>
			If the solution is correct:
				<ul>
				<li>Give 8 points.</li>
				<li>State OK in the justification box.</li>
				</ul>
			</li>
			
			<li>
			If the MatLab expression does not match the original algebraic expression OR is otherwise incorrect:
				<ul>
				<li>Give 0 points.</li>
				<li>In the justification box, clearly explain what was wrong and give a correct MatLab expression.</li>
				</ul>
			</li>
			
			</ul>
			
	        '
	          ,
	      ],
	
	      // Resolve a dispute and end the workflow
	      // Trigger only if the "dispute" task has a value of true
	      'resolve dispute' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],
	
	        'duration' => 2,
	
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'dispute',
	            'compare value' => true,
	          ],
	        ],
	
	        'instructions' => '<strong>The problem solver is disputing his or her grade. '
	          .'You need to provide the final grade. Assign a final score with '
	          .'justification for each part of the grade, and also please provide '
	          .'an explanation.</strong><br>'
	          
	          .
	          
			  '
	        
			<p><strong>Grade each solution in Part 1 and in Part 2. There is no partial credit.</strong></p>
			
			<br><p><strong>Part 1:</strong> For each variable name, check the answers:</p>
			<ul>
			
			<li>
			For a valid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is valid. (The solution does not need to explain why the name is valid.)</li>
				<li>Give 0 points if the solution states that the name is invalid.</li>
				</ul>
			</li>
			
			<li>
			For an invalid MatLab variable name:
				<ul>
				<li>Give 4 points if the solution states that the name is invalid, AND explains the correct reason why it is invalid.</li>
				<li>Give 2 points if the solution states that the name is invalid, but the correct reason is not given.</li>
				<li>Give 0 points if the solution states that the name is valid.</li>
				</ul>
			</li>
			
			</ul>
			
			<br><p><strong>Part 2:</strong> For each expression, carefully compare the MatLab expression with the original algebraic expression. Does the MatLab expression correctly represent the original algebraic expression?</p>
			<ul>
			
			<li>
			If the solution is correct:
				<ul>
				<li>Give 8 points.</li>
				<li>State OK in the justification box.</li>
				</ul>
			</li>
			
			<li>
			If the MatLab expression does not match the original algebraic expression OR is otherwise incorrect:
				<ul>
				<li>Give 0 points.</li>
				<li>In the justification box, clearly explain what was wrong and give a correct MatLab expression.</li>
				</ul>
			</li>
			
			</ul>
			
	        '
	          ,
	      ],
	    ];
	}
	
	if($course->course_name == ' PHIL 334')
	{
		return [
	      'create problem' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'first task trigger',
	          ]
	        ],
	
	        'user alias' => 'grade solution',
	
	        'instructions' => '<p><strong>Write a question about the course material.</strong></p>'
	        
	        .'<u>General guidelines for creating a question:</u>
 
				<ul><li>Pick a topic that plays a major role in one of the chapters.</li>
					<ul><li>Is there a section of the chapter devoted to the topic?</li>

					<li>Is there enough substance to the topic for someone to write a thoughtful reply?</li></ul> 

				<li>Write an interesting question about the topic, which has an intriguing philosophical aspect.</li>

					<ul><li>You are looking for a substantial enough question that someone should take two to three paragraphs to respond to.</li></ul>

				<li>Pick something that actually interests you—what did you find interesting in the chapter? Try to come up with a question that someone can have an interest in.</li></ul>',
	      ],
	
	      'edit problem' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],
	
	        'duration' => 2,
	
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create problem',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'reference task' => 'create problem',
	        'instructions' => 'Rephrase the problem (if necessary) so it is '
	          .'appropriate to the assignment and clear to the person solving '
	          .'it. The solver and graders will only see your edited version, not '
	          .'the original version. (Others not involved in solving or grading '
	          .'will see both the original and edited versions.) You can also '
	          .'leave a comment to explain any rephrasing.',
	      ],
	
	      'create solution' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'edit problem',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'user alias' => 'dispute',
	
	        'reference task' => 'edit problem',
	        'instructions' => '<p><strong>Respond to the question in 2 to 3 paragraphs.</strong></p>
	        
			<ul><li>Your response should display:</li>

				<ul><li><span style="color:#FF0000;">Factual Accuracy:</span> Does your response correctly describe	the situation that you have been asked about? Does it accurately define terms? Have you left out facts that	should be addressed in a complete solution?</li>

				<li><span style="color:#FF0000;">Philosophical Accuracy:</span> Do you accurately describe and use the philosophical material and problem solving techniques from the textbook?</li>

				<li><span style="color:#FF0000;">Writing:</span> Is your writing organized? Are there grammatical errors? Have you cited sources where that is appropriate?</li></ul></ul>',
	      ],
	
	      'grade solution' => [
	        'count' => 2,
	        'duration' => 3,
	        'user alias' => 'create problem',
	
	        // This configuration variable defines if the role of the grade solution
	        // should take over multiple instances of the task instance.
	        // 
	        // If there are two instances of 'grade solution', setting this to true will
	        // make sure that only one get's an alias. Setting it to false will make it
	        // it an alias for all the roles.
	        'user alias all types' => true,
	
			// Just for grade solution tasks. How should this grade be set up?
			'criteria' => [
			  'Factual_Accuracy' => [
			    'max' => 40,
			    'description' => 'Judge the factual accuracy of this response.',
			    'grade' => 0,
			    'justification' => '',
			    'additional-instructions' => '
			    <p><strong>40 points (equivalent to an A):</strong> All of the factual information necessary for the answer is present, terms are defined correctly, and facts of the case or issue are accurately described.</p>

				<p><strong>35 points (equivalent to a B):</strong> Most of the factual information necessary for the answer is present. One or two terms may be left undefined or assumed to be understood by the reader. One or two facts of the case may be missing.</p>

				<p><strong>30 points (equivalent to a C):</strong> (any of the following) Some of the factual information is incorrect. Terms may be defined incorrectly or facts of the case are presented incorrectly. Details may be missing that are required for the reader to understand the proposed solution.</p>

				<p><strong>25 points (equivalent to a D):</strong> Most of the factual information is inaccurate or missing.</p>

				<p><strong>0 points (equivalent to an F):</strong> No attempt is made to explain the terms or situation that is being discussed.</p>
			    ',
			  ],
			  
			  'Philosophical_Accuracy' => [
			    'max' => 40,
			    'description' => 'Judge the philosophical accuracy of this response.',
			    'grade' => 0,
			    'justification' => '',
			    'additional-instructions' => '
			    <p><strong>40 points (equivalent to an A):</strong> All of the philosophical concepts and problem solving techniques necessary for the answer are present, concepts are used correctly; and theories and techniques are accurately employed.</p>

				<p><strong>35 points (equivalent to a B):</strong> Most of the philosophical concepts and problem solving techniques necessary for the answer are present and, any of the following: concepts are used correctly with one or two minor errors; theories and techniques are employed with minor omissions.</p>

				<p><strong>30 points (equivalent to a C):</strong> Some of the philosophical concepts and problem solving techniques necessary for the answer are present. And, any of the following: concepts are used but not always correctly or not at all; theories and techniques are not employed or not correctly employed.</p>

				<p><strong>25 points (equivalent to a D):</strong> Most of the philosophical analysis is inaccurate or missing.</p>
				
				<p><strong>0 points (equivalent to an F):</strong> No attempt is made to offer a philosophical analysis.</p>
				',
			  ],
			  
			  'Writing' => [
			    'max' => 20,
			    'description' => 'Judge how well the response is written.',
			    'grade' => 0,
			    'justification' => '',
			    'additional-instructions' => '
			    <p><strong>20 points (equivalent to an A):</strong> No grammatical errors and at most 2 proof	reading errors, and paragraphs are significantly rich enough to	answer the question fully.</p>
				
				<p><strong>17 points (equivalent to a B):</strong> Three or Four grammatical, spelling or proofreading errors, and paragraphs are organized and mostly stay on topic.</p>
				
				<p><strong>15 points (equivalent to a C):</strong> Five to ten grammatical, spelling or proof reading errors, or the answer is divided into paragraphs but the paragraphs are not tightly focused and stray from the question’s topic.</p>
				
				<p><strong>12 points (equivalent to a D):</strong> Many grammatical or spelling errors, or no paragraph development and no development of argumentation.</p>
							
				<p><strong>0 points (equivalent to an F):</strong> The writing is incoherent to the point of not making sense.</p>
				',
			  ],
			],
	
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create solution',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'reference task' => 'create solution',
	        'instructions' => '<p>Grade the solution to the specific problem shown '
	          .'above. (There are several different problems so be sure to read '
	          .'the one being solved here.) Each grade has several parts. Give '
	          .'a score and an explanation of that score for each part of the '
	          .'grade. Your explanation should be detailed, and several sentences '
	          .'long.</p>'
	          
	          .'<p>Evaluate these questions on three criteria:</p>
	          <ul><li>Factual Accuracy (40 Points)</li>
	          <li>Philosophical Accuracy (40 Points)</li>
	          <li>Writing (20 Points)</li></ul>',
	      ],
	
	      // Resolve the grades
	      'resolve grades' => [
	        'internal' => true,
	
	        // Default value
	        'value' => true,
	
	        // Trigger once all the grades are submitted
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'grade solution',
	            'task status' => 'complete',
	          ],
	        ],
	
	        'reference task' => 'grade solution',
	      ],
	
	      // Grades are fine, store them in the workflow
	      'grades ok' => [
	        'internal' => true,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],
	
	        'reference task' => 'grade solution',
	        
	        // Expire if grades are out of range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],
	      ],
	
	      // Grades are out of a range and we need a second grader
	      'resolution grader' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],
	
	        // Expire if grades are in range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],
	
	        'reference task' => 'create solution',
	        'instructions' => 'Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.',
	      ],
	
	      // Dispute grades
	      // This step gives the option to dispute the grade they have recieved on their
	      // soln to yet-another-grader
	      'dispute' => [
	        'duration' => 2,
	        'user alias' => 'create solution',
	
	        // Trigger this if one of the tasks "resolution grader" or
	        // "grades ok" is complete.
	        'trigger' => [
	          [
	            'type' => 'check tasks for status',
	            'task types' => ['resolution grader', 'grades ok'],
	            'task status' => 'complete'
	          ],
	        ],
	
	        'instructions' => 'You have the option to dispute your grade. To do '
	          .'so, you need to fully grade your own solution. Assign your own '
	          .'score and justification for each part of the grade. You must also '
	          .'explain why the other graders were wrong.',
	      ],
	
	      // Resolve a dispute and end the workflow
	      // Trigger only if the "dispute" task has a value of true
	      'resolve dispute' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],
	
	        'duration' => 2,
	
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'dispute',
	            'compare value' => true,
	          ],
	        ],
	
	        'instructions' => 'The problem solver is disputing his or her grade. '
	          .'You need to provide the final grade. Assign a final score with '
	          .'justification for each part of the grade, and also please provide '
	          .'an explanation.',
	      ],
	    ];
	}
	else{
		return [
	      'create problem' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'first task trigger',
	          ]
	        ],

			'file' => 'mandatory',

	        'user alias' => 'grade solution',

	        'instructions' => 'Read the assignment instructions and enter '
	          .'a problem in the box below. Make your problem as clear as '
	          .'possible so the person solving it will understand what you mean. '
	          .'This solution is graded out of 100 points.',
	      ],

	      'edit problem' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],

			'file' => 'optional',

	        'duration' => 2,

	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create problem',
	            'task status' => 'complete',
	          ],
	        ],

	        'reference task' => 'create problem',
	        'instructions' => 'Rephrase the problem (if necessary) so it is '
	          .'appropriate to the assignment and clear to the person solving '
	          .'it. The solver and graders will only see your edited version, not '
	          .'the original version. (Others not involved in solving or grading '
	          .'will see both the original and edited versions.) You can also '
	          .'leave a comment to explain any rephrasing.',
	      ],

	      'create solution' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'edit problem',
	            'task status' => 'complete',
	          ],
	        ],

			'file' => 'optional',

	        'user alias' => 'dispute',

	        'reference task' => 'edit problem',
	        'instructions' => 'Solve the problem as fully and as clearly as you '
	          .'can. Explain your reasoning (if necessary).',
	      ],

	      'grade solution' => [
	        'count' => 2,
	        'duration' => 3,
	        'user alias' => 'create problem',
	        
			'criteria' => [
			  'correctness' => [
			    'grade' => 0,
			    'justification' => 0,
			    'max' => 100,
			    'description' => 'How correct is this answer?',
			  ],
			],

	        // This configuration variable defines if the role of the grade solution
	        // should take over multiple instances of the task instance.
	        // 
	        // If there are two instances of 'grade solution', setting this to true will
	        // make sure that only one get's an alias. Setting it to false will make it
	        // it an alias for all the roles.
	        'user alias all types' => true,

	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'create solution',
	            'task status' => 'complete',
	          ],
	        ],

	        'reference task' => 'create solution',
	        'instructions' => 'Grade the solution to the specific problem shown '
	          .'above. (There are several different problems so be sure to read '
	          .'the one being solved here.) Each grade has several parts. Give '
	          .'a score and an explanation of that score for each part of the '
	          .'grade. Your explanation should be detailed, and several sentences '
	          .'long.',
	      ],

	      // Resolve the grades
	      'resolve grades' => [
	        'internal' => true,

	        // Default value
	        'value' => true,

	        // Trigger once all the grades are submitted
	        'trigger' => [
	          [
	            'type' => 'reference task status',
	            'task type' => 'grade solution',
	            'task status' => 'complete',
	          ],
	        ],

	        'reference task' => 'grade solution',
	      ],

	      // Grades are fine, store them in the workflow
	      'grades ok' => [
	        'internal' => true,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],

	        'reference task' => 'grade solution',

	        // Expire if grades are out of range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],
	      ],

	      // Grades are out of a range and we need a second grader
	      'resolution grader' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => false,
	          ]
	        ],

	        // Expire if grades are in range
	        'expire' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'resolve grades',
	            'compare value' => true,
	          ]
	        ],

	        'reference task' => 'create solution',
	        'instructions' => 'Because the regular graders did give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and also '
	          .'please provide an explanation.',
	      ],

	      // Dispute grades
	      // This step gives the option to dispute the grade they have recieved on their
	      // soln to yet-another-grader
	      'dispute' => [
	        'duration' => 2,
	        'user alias' => 'create solution',

	        // Trigger this if one of the tasks "resolution grader" or
	        // "grades ok" is complete.
	        'trigger' => [
	          [
	            'type' => 'check tasks for status',
	            'task types' => ['resolution grader', 'grades ok'],
	            'task status' => 'complete'
	          ],
	        ],

	        'instructions' => 'You have the option to dispute your grade. To do '
	          .'so, you need to fully grade your own solution. Assign your own '
	          .'score and justification for each part of the grade. You must also '
	          .'explain why the other graders were wrong.',
	      ],

	      // Resolve a dispute and end the workflow
	      // Trigger only if the "dispute" task has a value of true
	      'resolve dispute' => [
	        'pool' => [
	          'name' => 'instructor',
	          'pull after' => false,
	        ],

	        'duration' => 2,

	        'trigger' => [
	          [
	            'type' => 'compare value of task',
	            'task type' => 'dispute',
	            'compare value' => true,
	          ],
	        ],

	        'instructions' => 'The problem solver is disputing his or her grade. '
	          .'You need to provide the final grade. Assign a final score with '
	          .'justification for each part of the grade, and also please provide '
	          .'an explanation.',
	      ],
	    ];
	}
		
		
  	
    
  }

  /**
   * Resolve a Human Task Name
   *
   * @return string The Human Version of the Type
   * @param string The type
   */
  public static function humanTaskName($type)
  {
    switch ($type)
    {
      case 'create problem' :
        $action_human = 'Create a Problem';
        break;

      case 'edit problem' :
        $action_human = 'Edit a Problem';
        break;
      
      case 'grade solution' :
        $action_human = 'Grade a Solution';
        break;
      
      case 'create solution' :
        $action_human = 'Create a Solution';
        break;
      
      case 'resolution grader' :
        $action_human = 'Resolve Grades';
        break;

      case 'dispute' :
        $action_human = 'Decide Whether to Dispute';
        break;

      case 'resolve dispute' :
        $action_human = 'Resolve Dispute';
        break;  

      default :
        $action_human = 'Unknown Action';    
    }

    return t($action_human);
  }

  /**
   * Retrieve the roles a user can have in a section
   *
   * @return array
   */
  public static function getUserRoles()
  {
    return ['student', 'instructor'];
  }

  /**
   * Cleanup tasks
   *
   * @return void
   * @access public
   */
  public static function cleanupTasks()
  {
    return \Illuminate\Database\Capsule\Manager::connection()->statement('DELETE FROM `pla_task` WHERE (SELECT count(1) FROM `pla_workflow` WHERE workflow_id = pla_task.workflow_id) < 1;');
  }
}
