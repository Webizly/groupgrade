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
      $w->type = "one_a";
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
	
	if($course->course_name == "IS 735"){
		return [
	      'create problem' => [
	        'duration' => 3,
	        'trigger' => [
	          [
	            'type' => 'first task trigger',
	          ]
	        ],

	        'user alias' => 'grade solution',

	        'instructions' => '
	        
			<p>
			
			<em>
			(The “Collaborative Exam forum gives more information, including an example question with points allocated to sections.   The following is just a summary.)
			</em>
			
			</p>
			
	        <ol>
	          <li>
	          Create 3 possible exam questions. Number them 1, 2 and 3. Post all 3 questions here and post the identical 3 questions in the Create Problem task for the other part of the exam.
	          </li>
	          
			  <li>
			  Each question should be from a different unit of the course.  Questions can cover any topic through educational applications.   They should ask only about things that "everybody" should have read or looked at, which includes:
			    <ul>
			      <li>
			      Lecture notes for any of the lectures.
			      </li>
			      
				  <li>
				  Any required article on the syllabus (with a star).
				  </li>
				  
				  <li>
				  Any article that was reviewed on Moodle.
				  </li>
			    </ul>
			  </li>
			  
			  <li>
			  Each question should require the person answering to draw on two or more sources.
			  </li>
			  
			  <li>
			  After each section of a question, state how many points that section is worth.   Sections should total 30 points per question.  (See example in the Collaborative Exam forum.)
			  </li>
			  
			  <li>
			  Do not include your name.  The exam is anonymous to fellow students.
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

	        'reference task' => 'create problem',
	        'instructions' => '
	        <ol>
	          <li>
	          Students submit the identical 3 questions in the Exam Part 1 and Exam Part 2.  Pick one of the 3 questions for this Exam Part, and another from the 3 questions for the other Exam Part. (Alternatively you can make up a different question and not use any of these 3 questions.)
	          </li>
	          
			  <li>
			  You may wish to print the questions and mark which one you use for each Exam Part. (You will access the other Exam Part from a different task.)
			  </li>
			  
			  <li>
			  Copy, paste and edit the question as necessary that you have chosen for this Exam Part.
			  </li>
			  
			  <li>
			  In the Comments box briefly explain why you chose this question and why you edited it as you did.
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

			'optional' => true,

	        'user alias' => 'dispute',

	        'reference task' => 'edit problem',
	        
	        'instructions' => '
	        <p>In a Word document, answer the question shown for this part of the exam.</p>
	        <p>A correct and complete answer will include consider all sides of issues, synthesize materials, etc.  This includes having a framing paragraph to open, providing justification to assertions made, having a conclusion, etc.</p>
	        <p>Make it clear which section of the question you are addressing, and be sure to address all sections of the question.</p>
	        <br>
	        <p>Notes:</p>
	        <ol>
	          <li>
	            <em>Length restriction:</em> Total answers should contain 750-1700 words (including tables but not including figures or bibliography section).
	          </li>
	          
			  <li>
			    <em>Bibliography:</em> Include a References section at the end of your answer. Every time you reference any class material, put a citation marker such as [last name of author, date] and then put the full bibliographic citation including page numbers in the bibliography.
			  </li>
			  
			  <li>
			    <em>Presentation:</em>  Your writing should be clear and readable, in your typing, formatting and content. Use 12 point font.  The answers must be in English with no more than minor problems with spelling or grammar.
			  </li>
	        </ol>
	        
			<p>
			<strong>Ensure Anonymity + Submit:</strong>  Ensure your Word document is anonymous by removing personal information from the document. See <a href = "http://alturl.com/babcg" target = "_blank">http://alturl.com/babcg</a> for detailed instructions.  Upload the document, and then click submit.
			</p>
	        ',
	      ],

	      'grade solution' => [
	        'count' => 2,
	        'duration' => 3,
	        'user alias' => 'create problem',
	        
			'criteria' => [
			  'Content' => [
			    'grade' => 0,
			    'justification' => 0,
			    'max' => 30,
			    'description' => 'Please grade the content of this solution.',
			  ],
			  'Presentation' => [
			    'grade' => 0,
			    'justification' => 0,
			    'min' => -6,
			    'max' => 0,
			    'description' => 'Please grade the presentation of this solution.',
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
	        'instructions' => '
	        <p>
	        <strong>Content (0-30):</strong> The correctness and completeness of the answer, including considering all sides of issues, synthesizing material, etc. Includes having a framing paragraph to open, providing justification to assertions made, having a conclusion, etc.
	        </p>
	        
			<p>
			Divide the 30 points across the question sections according to the number of points given in the problem statement for each section.  Grade each section separately out of its point allocation. Total the points over all sections and enter the total points here.
			</p>
			
			<p>
			<strong>Content Justification:</strong>  It must be clear how many points you gave for each section and why you graded that way.  Provide a full written explanation (justification) of your grading. For each section of the question:
			  <ol>
			    <li>
			      State your section.
			    </li>
			    
				<li>
				State your grade for that section.
				</li>
				
				<li>
				Write at least 2 full sentences explanation fully explaining and justifying the section’s grade.
				</li>
				
			  </ol>
			</p>
			
			<p>
			<strong>Important:</strong>  Your content justification should also point out what is good about the answer, as well as anything that seems to be a major omission or incompleteness in the answer.
			</p>
			
			<p>
			<strong>Presentation (up to 6 points can be DEDUCTED):</strong>  Deduct points to penalize the clarity of the writing, including:
			<ul>
			  <li>
			  Improper citations (deduct up to 3 points if these are missing or incomplete).
			  </li>
			  
			  <li>
			  Improper length (deduct 2 points if answer is not 750-1700 words, including tables but not including figures or bibliography; you can check this in Word)
			  </li>
			  
			  <li>
			  Poor readability and clearness in typing/format/ message (deduct points as necessary) if the answer does not:
			    <ul>
			      <li>
			      Use 12 point font.
			      </li>
			      
				  <li>
				  The answers must be in English.
				  </li>
				  
				  <li>
				  Minor problems with spelling or grammar should not be penalized. However, if the grammar and spelling problems are so pervasive that it is hard to understand what is being said, then points need to be deducted.
				  </li>
			    </ul>
			  </li> 
			</ul>
			</p>
			
			<p>
			<strong>Presentation Justification:</strong>  Clearly explain and justify all points deducted for presentation, or state “Good Presentation” if you deducted no points.
			</p>
	        ',
	      ],

	      // Resolve the grades
	      'resolve grades' => [
	        'internal' => true,

	        // Default value
	        'value' => true,
			
			'resolve range' => -100,
			
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
	          .'please provide an explanation.'
	          
	          .
	          
			  '
	        <p>
	        <strong>Content (0-30):</strong> The correctness and completeness of the answer, including considering all sides of issues, synthesizing material, etc. Includes having a framing paragraph to open, providing justification to assertions made, having a conclusion, etc.
	        </p>
	        
			<p>
			Divide the 30 points across the question sections according to the number of points given in the problem statement for each section.  Grade each section separately out of its point allocation. Total the points over all sections and enter the total points here.
			</p>
			
			<p>
			<strong>Content Justification:</strong>  It must be clear how many points you gave for each section and why you graded that way.  Provide a full written explanation (justification) of your grading. For each section of the question:
			  <ol>
			    <li>
			      State your section.
			    </li>
			    
				<li>
				State your grade for that section.
				</li>
				
				<li>
				Write at least 2 full sentences explanation fully explaining and justifying the section’s grade.
				</li>
				
			  </ol>
			</p>
			
			<p>
			<strong>Important:</strong>  Your content justification should also point out what is good about the answer, as well as anything that seems to be a major omission or incompleteness in the answer.
			</p>
			
			<p>
			<strong>Presentation (up to 6 points can be DEDUCTED):</strong>  Deduct points to penalize the clarity of the writing, including:
			<ul>
			  <li>
			  Improper citations (deduct up to 3 points if these are missing or incomplete).
			  </li>
			  
			  <li>
			  Improper length (deduct 2 points if answer is not 750-1700 words, including tables but not including figures or bibliography; you can check this in Word)
			  </li>
			  
			  <li>
			  Poor readability and clearness in typing/format/ message (deduct points as necessary) if the answer does not:
			    <ul>
			      <li>
			      Use 12 point font.
			      </li>
			      
				  <li>
				  The answers must be in English.
				  </li>
				  
				  <li>
				  Minor problems with spelling or grammar should not be penalized. However, if the grammar and spelling problems are so pervasive that it is hard to understand what is being said, then points need to be deducted.
				  </li>
			    </ul>
			  </li> 
			</ul>
			</p>
			
			<p>
			<strong>Presentation Justification:</strong>  Clearly explain and justify all points deducted for presentation, or state “Good Presentation” if you deducted no points.
			</p>
	        ',
	          
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
	          .'explain why the other graders were wrong.'
	          
	          .
	          
	          '
	        <p>
	        <strong>Content (0-30):</strong> The correctness and completeness of the answer, including considering all sides of issues, synthesizing material, etc. Includes having a framing paragraph to open, providing justification to assertions made, having a conclusion, etc.
	        </p>
	        
			<p>
			Divide the 30 points across the question sections according to the number of points given in the problem statement for each section.  Grade each section separately out of its point allocation. Total the points over all sections and enter the total points here.
			</p>
			
			<p>
			<strong>Content Justification:</strong>  It must be clear how many points you gave for each section and why you graded that way.  Provide a full written explanation (justification) of your grading. For each section of the question:
			  <ol>
			    <li>
			      State your section.
			    </li>
			    
				<li>
				State your grade for that section.
				</li>
				
				<li>
				Write at least 2 full sentences explanation fully explaining and justifying the section’s grade.
				</li>
				
			  </ol>
			</p>
			
			<p>
			<strong>Important:</strong>  Your content justification should also point out what is good about the answer, as well as anything that seems to be a major omission or incompleteness in the answer.
			</p>
			
			<p>
			<strong>Presentation (up to 6 points can be DEDUCTED):</strong>  Deduct points to penalize the clarity of the writing, including:
			<ul>
			  <li>
			  Improper citations (deduct up to 3 points if these are missing or incomplete).
			  </li>
			  
			  <li>
			  Improper length (deduct 2 points if answer is not 750-1700 words, including tables but not including figures or bibliography; you can check this in Word)
			  </li>
			  
			  <li>
			  Poor readability and clearness in typing/format/ message (deduct points as necessary) if the answer does not:
			    <ul>
			      <li>
			      Use 12 point font.
			      </li>
			      
				  <li>
				  The answers must be in English.
				  </li>
				  
				  <li>
				  Minor problems with spelling or grammar should not be penalized. However, if the grammar and spelling problems are so pervasive that it is hard to understand what is being said, then points need to be deducted.
				  </li>
			    </ul>
			  </li> 
			</ul>
			</p>
			
			<p>
			<strong>Presentation Justification:</strong>  Clearly explain and justify all points deducted for presentation, or state “Good Presentation” if you deducted no points.
			</p>
	        ',
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
	          .'an explanation.'
	          
	          .
	          
			  '
	        <p>
	        <strong>Content (0-30):</strong> The correctness and completeness of the answer, including considering all sides of issues, synthesizing material, etc. Includes having a framing paragraph to open, providing justification to assertions made, having a conclusion, etc.
	        </p>
	        
			<p>
			Divide the 30 points across the question sections according to the number of points given in the problem statement for each section.  Grade each section separately out of its point allocation. Total the points over all sections and enter the total points here.
			</p>
			
			<p>
			<strong>Content Justification:</strong>  It must be clear how many points you gave for each section and why you graded that way.  Provide a full written explanation (justification) of your grading. For each section of the question:
			  <ol>
			    <li>
			      State your section.
			    </li>
			    
				<li>
				State your grade for that section.
				</li>
				
				<li>
				Write at least 2 full sentences explanation fully explaining and justifying the section’s grade.
				</li>
				
			  </ol>
			</p>
			
			<p>
			<strong>Important:</strong>  Your content justification should also point out what is good about the answer, as well as anything that seems to be a major omission or incompleteness in the answer.
			</p>
			
			<p>
			<strong>Presentation (up to 6 points can be DEDUCTED):</strong>  Deduct points to penalize the clarity of the writing, including:
			<ul>
			  <li>
			  Improper citations (deduct up to 3 points if these are missing or incomplete).
			  </li>
			  
			  <li>
			  Improper length (deduct 2 points if answer is not 750-1700 words, including tables but not including figures or bibliography; you can check this in Word)
			  </li>
			  
			  <li>
			  Poor readability and clearness in typing/format/ message (deduct points as necessary) if the answer does not:
			    <ul>
			      <li>
			      Use 12 point font.
			      </li>
			      
				  <li>
				  The answers must be in English.
				  </li>
				  
				  <li>
				  Minor problems with spelling or grammar should not be penalized. However, if the grammar and spelling problems are so pervasive that it is hard to understand what is being said, then points need to be deducted.
				  </li>
			    </ul>
			  </li> 
			</ul>
			</p>
			
			<p>
			<strong>Presentation Justification:</strong>  Clearly explain and justify all points deducted for presentation, or state “Good Presentation” if you deducted no points.
			</p>
	        '
	          ,
	      ],
	    ];
	}
	
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
	



	//HOMEWORK 3
	if($asec->assignment_id == 79)//79
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
	
	        'instructions' => '<p><strong>Part 1: Come up with FIVE (5) arrays as explained below, but do not use MatLab yet. <em>(Homework 3 instructions on Moodle provide details for each step and examples. The following is just a summary)</em></strong></p>'
	        
	        .' 
			
				<ol>
				<li>The arrays should meet the following requirements:
				
				<ul>
					<li>The elements must be real numbers (integers or decimals).</li>
					<li>Each array must have at least 5 elements (numbers) and at most 20 elements.</li>
					<li>Arrays #1-3 are one-dimensional. Arrays #4 and #5 are two-dimensional.</li>
					<li>Each array must have:
						<ul>
							<li>At least three consecutive elements whose values are evenly spaced.</li>
							<li>Other elements: The other elements can continue this consecutive spacing. Alternatively in more challenging problems, the other elements do not need to be consecutive with these elements, and may have different spacing or have no even spacing. <em>(See the examples in the “Solving Problems” section.)</em></li>
						</ul>
					</li>
					<li>Each of the 5 arrays must use different steps for the evenly spaced elements. Make sure that the steps you choose include 1, positive steps, and negative steps.</li>
				</ul>
				
				</li>
				
				<li>List the elements of the arrays in a MS Word document. If the array is two-dimensional, use the equation editor.</li>
				
				<li>Clearly number the one-dimensional arrays in the document from 1 to 3, and the two-dimensional arrays from 4 to 5. Use the example document format on Moodle.</li>
				
				<li>Ensure your Word document is anonymous by removing personal information from the document, including the title, content and properties.</li>
				
				<li>Follow the Homework 3 link in Moodle, upload the document and then click submit.</li>
				
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
	        Edit any of the arrays as necessary, upload the edited document here, and in the comments box below explain why you made changes. If no edits are necessary, type “Approved” in the comments box.
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
	        <p><strong>Create solutions following the template in the instructions on Moodle. <em>(Homework 3 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em></strong></p>
	        
			<ol>
			<li>For each problem, you need to create two different MatLab commands, both of which can generate the array described in the problem
			
			<ul>
				<li>One command only uses shortcut expression(s) to generate all consecutive evenly spaced elements. It cannot use linspace function calls.</li>
				<li>The other only uses linspace function call(s) to generate those elements. It cannot use shortcut expressions.</li>
				<li>For any other single, non-evenly spaced elements, which cannot be generated with shortcut expressions or linspace function calls, you may list them in the command.</li>
			</ul>
			</li>
			
			<li>For each problem, execute each of your commands in MatLab to check them. Copy the commands from MatLab’s command window and paste them into a MS Word document under the corresponding problem.</li>
			
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
			  'Problem1-Shortcut_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 1 - Shortcut Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem1-Linspace_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 1 - Linspace Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem2-Shortcut_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 2 - Shortcut Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem2-Linspace_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 2 - Linspace Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem3-Shortcut_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 3 - Shortcut Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem3-Linspace_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 3 - Linspace Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem4-Shortcut_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 4 - Shortcut Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem4-Linspace_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 4 - Linspace Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem5-Shortcut_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 5 - Shortcut Expression.',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Problem5-Linspace_Expression' => [
			    'max' => 10,
			    'description' => 'Provide a grade for Problem 5 - Linspace Expression.',
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
	        
			<p><strong>Confirm that each MatLab command correctly generates the array listed in each problem.</strong></p>
			
			<br>
			
			<ol>
				<li>For each solution, check both MatLab commands:
					<ul>
						<li><strong>Shortcut Expressions:</strong> In MatLab, type in and execute the MatLab command with <em>shortcut expression(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em>
							</ul>
						
						</li>
						
						<li>
							<strong>Linspace function calls:</strong> In MatLab, type in and execute the MatLab command with <em>linspace function call(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em></li>
							</ul>
							
						</li>
						
						<li>
							<strong>Duplicates: </strong>If If there are two correct MatLab commands that both use shortcut expressions OR there are two correct MatLab commands that both use linspace function call(s), you will only give a score of 10 to one, and a score of 0 to the other. <em>Explain why in the justification box</em>.
						</li>
						
						<li>
							There is no other partial credit. Each solution gets a score of 20, 10, or 0.
						</li>
						
					</ul>
				</li>
			</ol>
			
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
	        'instructions' => '
	        
			<p><strong>Confirm that each MatLab command correctly generates the array listed in each problem.</strong></p>
			
			<br>
			
			<ol>
				<li>For each solution, check both MatLab commands:
					<ul>
						<li><strong>Shortcut Expressions:</strong> In MatLab, type in and execute the MatLab command with <em>shortcut expression(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em>
							</ul>
						
						</li>
						
						<li>
							<strong>Linspace function calls:</strong> In MatLab, type in and execute the MatLab command with <em>linspace function call(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em></li>
							</ul>
							
						</li>
						
						<li>
							<strong>Duplicates: </strong>If If there are two correct MatLab commands that both use shortcut expressions OR there are two correct MatLab commands that both use linspace function call(s), you will only give a score of 10 to one, and a score of 0 to the other. <em>Explain why in the justification box</em>.
						</li>
						
						<li>
							There is no other partial credit. Each solution gets a score of 20, 10, or 0.
						</li>
						
					</ul>
				</li>
			</ol>
			
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
	        
			<p><strong>Confirm that each MatLab command correctly generates the array listed in each problem.</strong></p>
			
			<br>
			
			<ol>
				<li>For each solution, check both MatLab commands:
					<ul>
						<li><strong>Shortcut Expressions:</strong> In MatLab, type in and execute the MatLab command with <em>shortcut expression(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em>
							</ul>
						
						</li>
						
						<li>
							<strong>Linspace function calls:</strong> In MatLab, type in and execute the MatLab command with <em>linspace function call(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em></li>
							</ul>
							
						</li>
						
						<li>
							<strong>Duplicates: </strong>If If there are two correct MatLab commands that both use shortcut expressions OR there are two correct MatLab commands that both use linspace function call(s), you will only give a score of 10 to one, and a score of 0 to the other. <em>Explain why in the justification box</em>.
						</li>
						
						<li>
							There is no other partial credit. Each solution gets a score of 20, 10, or 0.
						</li>
						
					</ul>
				</li>
			</ol>
			
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
	        
			<p><strong>Confirm that each MatLab command correctly generates the array listed in each problem.</strong></p>
			
			<br>
			
			<ol>
				<li>For each solution, check both MatLab commands:
					<ul>
						<li><strong>Shortcut Expressions:</strong> In MatLab, type in and execute the MatLab command with <em>shortcut expression(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em>
							</ul>
						
						</li>
						
						<li>
							<strong>Linspace function calls:</strong> In MatLab, type in and execute the MatLab command with <em>linspace function call(s)</em>, and then compare the array generated by the command against the array in the problem.
						
							<ul>
								<li>If the array is identical to the array in the problem, give a score of 10 AND in the justification box, write OK.</li>
								<li>If the MatLab command does not generate that array, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab command.</em></li>
							</ul>
							
						</li>
						
						<li>
							<strong>Duplicates: </strong>If If there are two correct MatLab commands that both use shortcut expressions OR there are two correct MatLab commands that both use linspace function call(s), you will only give a score of 10 to one, and a score of 0 to the other. <em>Explain why in the justification box</em>.
						</li>
						
						<li>
							There is no other partial credit. Each solution gets a score of 20, 10, or 0.
						</li>
						
					</ul>
				</li>
			</ol>
			
	        '
	          ,
	      ],
	    ];
	}
	
	if($asec->assignment_id == 82)//Homework 4
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
	
	        'instructions' => '
	        <p><strong>Preparation</strong></p>
	        <ol>
	        	<li>In MatLab, create an array arr = [11:15; 21:25; 31:35; 41:45; 51:55].</li>
	        	<li>Come up with 10 different subarrays of arr (e.g. arr(1:2:5,2:4)). The subarrays should meet the following requirements:
					<ul>
						<li>Each subarray must select at least 3 elements in array arr.</li>
						<li>Each of the 10 subarrays must select different sets of elements</li>
					</ul></li>
				<li>In MatLab command window, type in each subarray as a command and execute the command.</li>
				
	        </ol>
	        
			<p><strong>Part 1: Questions 1-5 include only Commands</strong></p>
			<p>Copy the first 5 subarrays (subarray expressions) from your MatLab command window and paste them into the Word document. See the example in the instructions. Only copy the commands. Do not include the output of the MatLab (i.e. the arrays printed out by MatLab for the corresponding commands).</p><br>
			<p><strong>Part 2: Questions 6-10 include only Results</strong></p>
			<p>For questions 6-10 do the opposite. Copy the arrays printed out by the MatLab when it executes the commands for subarrays 6-10, and paste these resulting arrays into the Word document. See the example in the instructions. Only copy the resulting arrays. Do not include the subarrays in the commands or the “ans=” part printed out by MatLab.</p><br>
			<p><strong>Ensure Anonymity + Submit:</p></strong>
			<p>Ensure your Word document is anonymous. Follow the Homework 4 link in Moodle, upload the document and then click submit.</p><br>
			',
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
	        'instructions' =>
	        '
			<ol>
				<li>Edit as necessary, upload the edited document here, and in the comments box below, explain why you made changes. If no edits are necessary, type "Approved" in the comments box.</li>
				<li>Click on the submit button.</li>
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
	
			'optional' => true,
	
	        'user alias' => 'dispute',
	
	        'reference task' => 'edit problem',
	        'instructions' => '
	        <p>Create solutions following the template in the instructions on Moodle. <em>Homework 4 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em></p><br>
	        <p><strong>Part 1: Questions include only Commands; Solutions are the results.</strong></p>
	        <p>For each question in part 1, determine the resulting elements selected by the corresponding MatLab subarray. Write the elements below the question. Put them in an array. When the elements form a 2-dimensional array, using MS Word equation editor is recommended. Alternatively, organize the elements clearly in rows and columns.</p><br>
	        <p><strong>Part 2: Questions include only Results; Solutions are the commands necessary.</strong></p>
	        <p>For each question in part 2, write a MatLab subarray (the expressions) that can select the elements specified in the question.</p><br>
	        <p><strong>Ensure Anonymity + Submit: </strong>Ensure your Word document is anonymous. Follow the Homework 4 link in Moodle, upload the document and then click submit.</p>
	        
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
			  'Part-1_Solution_1' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 1',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-1_Solution_2' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 2',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-1_Solution_3' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 3',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-1_Solution_4' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 4',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-1_Solution_5' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 5',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-2_Solution_6' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 6',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-2_Solution_7' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 7',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-2_Solution_8' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 8',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-2_Solution_9' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 9',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Part-2_Solution_10' => [
			    'max' => 10,
			    'description' => 'Provide a grade to solution 10',
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
			<p><strong>Part 1:</strong> For each question, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the solution.</p>
			<ul>
				<li>If the solution in the document is in the same as the solution in MatLab, give a score of 10 AND in the justification box, write "OK".</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab solution</em>.</li>
			</ul>
			
			<p><strong>Part 2:</strong> For each solution, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the question.</p>
			<ul>
				<li>If the solution in the document is the same as the solution in MatLab, give a score of 10 AND in the justification box, write “OK”.</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give a correct solution</em>.</li>
			</ul>
	        ',
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
	        'instructions' =>
	        '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong><br>'
	          . 
	        '
			<p><strong>Part 1:</strong> For each question, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the solution.</p>
			<ul>
				<li>If the solution in the document is in the same as the solution in MatLab, give a score of 10 AND in the justification box, write "OK".</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab solution</em>.</li>
			</ul>
			
			<p><strong>Part 2:</strong> For each solution, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the question.</p>
			<ul>
				<li>If the solution in the document is the same as the solution in MatLab, give a score of 10 AND in the justification box, write “OK”.</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give a correct solution</em>.</li>
			</ul>
	        ',
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
			<p><strong>Part 1:</strong> For each question, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the solution.</p>
			<ul>
				<li>If the solution in the document is in the same as the solution in MatLab, give a score of 10 AND in the justification box, write "OK".</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab solution</em>.</li>
			</ul>
			
			<p><strong>Part 2:</strong> For each solution, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the question.</p>
			<ul>
				<li>If the solution in the document is the same as the solution in MatLab, give a score of 10 AND in the justification box, write “OK”.</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give a correct solution</em>.</li>
			</ul>
	        ',
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
	          .'an explanation.'
	          
	          .
	          
	         '
			<p><strong>Part 1:</strong> For each question, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the solution.</p>
			<ul>
				<li>If the solution in the document is in the same as the solution in MatLab, give a score of 10 AND in the justification box, write "OK".</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give the corrected MatLab solution</em>.</li>
			</ul>
			
			<p><strong>Part 2:</strong> For each solution, copy the subarray. Paste it into the MatLab command window and hit “enter” to execute it. Compare the elements printed out by MatLab against those in the question.</p>
			<ul>
				<li>If the solution in the document is the same as the solution in MatLab, give a score of 10 AND in the justification box, write “OK”.</li>
				<li>If the solutions differ, give a score of 0, AND <em>in the justification box, clearly explain what was wrong AND give a correct solution</em>.</li>
			</ul>
	        ',
	      ],
	    ];
	}

if($asec->assignment_id == 85)//Homework 5
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
	
	        'instructions' => '
	        
			<p>
			<em>(Homework 5 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em>
			</p>
			
			<ol>
				<li>
				Build <strong>Ten (10)</strong> MatLab expressions. You can <strong>ONLY</strong> use <strong>numbers</strong> (<strong>NO variables</strong>), <strong>arithmetic operators</strong> including +, -, *, /, ^, <strong>relational operators</strong> including ==, ~=, <, <=, >, >= and <strong>logical operators</strong> including ~, &, &&, |, ||, xor. Each expression should meet the following requirements:
					<ul>
						<li>
							It should have <strong>at least 1 relational operator and/or logical operator</strong>;
						</li>
						
						<li>
							It should have <strong>at least 3 arithmetic, relational and/or logical</strong> operators and <strong>at most 5</strong> operators;
						</li>
						
						<li>
							It should be a legitimate MatLab expression.
						</li>
						
						<li>
							Try to use as many different operators as you can across your 10 expressions.
						</li>
					</ul>
				</li>
				
				<li>
					Use MatLab to evaluate each expression: Type in the expression in MatLab command window and let MatLab to evaluate the expression.  If MatLab prints out an error message, modify the expression to remove the error.
				</li>
				
				<li>
					Include all the MatLab expressions and their values into a table.  Follow the example in the instructions on Moodle.
				</li>
			</ol>
			
			<p>
				<strong>Ensure Anonymity + Submit: </strong>Ensure your Word document is anonymous.  Follow the Homework 5 link in Moodle, upload the document and then click submit.
			</p>
			
	        ',
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
	        'instructions' =>
	        '
			<ol>
				<li>Edit as necessary, upload the edited document here, and in the comments box below, explain why you made changes. If no edits are necessary, type "Approved" in the comments box.</li>
				<li>Click on the submit button.</li>
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
	
			'optional' => true,
	
	        'user alias' => 'dispute',
	
	        'reference task' => 'edit problem',
	        'instructions' => '
	        	<p>
	        	Create solutions following the template in the instructions on Moodle.  <em>(Homework 5 instructions on Moodle provide details for each step and examples.   The following is just a summary.)</em>
	        	</p>
	        	
				<p>
	        	For each question, write the <strong>steps</strong> to evaluate the MatLab expression in the Explanation column in the Word document. You need to pay attention to the precedence of the operators, <strong>carry out the corresponding operations in a correct order, and calculate intermediate results</strong> (you may use a calculator).
	        	</p>
	        	
				<p>
	        	<strong>Important: </strong> Make sure that you have at least one separate step for each operator in the answer.
	        	</p>
	        	
				<p>
	        	<strong>Ensure Anonymity + Submit: </strong> Ensure your Word document is anonymous.  Follow the Homework 5 link in Moodle, upload the document and then click submit.
	        	</p>
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
			  'Question1_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 1',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question1_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 1',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question2_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 2',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question2_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 2',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question3_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 3',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question3_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 3',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question4_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 4',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question4_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 4',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question5_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 5',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question5_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 5',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question6_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 6',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question6_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 6',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question7_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 7',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question7_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 7',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question8_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 8',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question8_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 8',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question9_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 9',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question9_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 9',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question10_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 10',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question10_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 10',
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
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	        'instructions' =>
	        '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong><br>'
	          . 
	        '
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	          .'an explanation.'
	          
	          .
	          
	         '
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
	      ],
	    ];
	}
	
	if($asec->assignment_id == 87)//Homework 6
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
	
	        'instructions' => '
	        
			<p>
			<em>(Homework 6 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em>
			</p>
			
			<ol>
				<li>
					Come up with <strong>FOUR</strong> programming exercises. To solve the problems in these exercises, students must use branching statement(s). You may create your own programming exercises. You may also modify the following programming exercises in 1 or more ways: 3.2, 3.4, 3.8, 3.11, and 3.12 in the exercises section of chapter 3 in the textbook.
				</li>
				
				<li>
					Whether you create your own or modify the textbook exercises, you need to clearly describe the background of the problem, specify what is required as the input for the calculation, and explain what operations should be carried out for each type of input, what is computed as the results, and how to display the results.
				</li>
				
				<li>
					Include the four exercises in a MS Word document.  Clearly number them 1, 2, 3 and 4.
				</li>
			</ol>
			
			<p>
				<strong>Ensure Anonymity + Submit: </strong>Ensure your Word document is anonymous.  Follow the Homework 6 link in Moodle, upload the document and then click submit.
			</p>
			
	        ',
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
	        'instructions' =>
	        '
			<ol>
				<li>Edit as necessary, upload the edited document here, and in the comments box below, explain why you made changes. If no edits are necessary, type "Approved" in the comments box.</li>
				<li>Click on the submit button.</li>
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
	
			'optional' => true,
	
	        'user alias' => 'dispute',
	
	        'reference task' => 'edit problem',
	        'instructions' => '
	        	<p>
	        	Create solutions following the template in the instructions on Moodle.  <em>(Homework 6 instructions on Moodle provide details for each step and examples.   The following is just a summary.)</em>
	        	</p>
	        	
				<ol>
					<li>
					Write a MatLab script for each problem.
					</li>
					
					<li>
					Make sure that it can run in MatLab without errors.
					</li>
					
					<li>
					When you finish, test your script: Run the script multiple times. Vary the inputs every time so that the executions can go through different branches in the script. Also make sure that all the branches are tested.
					<br>
					Pick a set of representative commands (one for each branch or condition) and its corresponding output that can show the script is fully correct.  Include only the commands and outputs for testing the final version of the script. Do not include redundant commands and outputs.
					</li>
					
					<li>
					Follow the format in the template in the Moodle instructions!  Copy the script, the representative commands and their text output from MatLab’s Command Window. Paste them into a <span style="font-weight:bold; color:red;">TEXT document (HW6.txt)</span>.   <strong>Do NOT use MS Word documents.</strong>
					</li>
				</ol>
	        	
				<p>
	        	<strong>Upload + Submit:</strong> Follow the Homework 6 link in Moodle, upload your HW6.txt and then click submit.
	        	</p>
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
			  
			  'Exercise_1-Script_Runs_in_MatLab' => [
			    'max' => 8,
			    'description' => 'Grade Exercise 1 - Script Runs in MatLab',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_1-Correct_Results' => [
			    'max' => 10,
			    'description' => 'Grade Exercise 1 - Correct Results',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_1-Testing_Quality' => [
			    'max' => 7,
			    'description' => 'Grade Exercise 1 - Testing Quality',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_2-Script_Runs_in_MatLab' => [
			    'max' => 8,
			    'description' => 'Grade Exercise 2 - Script Runs in MatLab',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_2-Correct_Results' => [
			    'max' => 10,
			    'description' => 'Grade Exercise 2 - Correct Results',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_2-Testing_Quality' => [
			    'max' => 7,
			    'description' => 'Grade Exercise 2 - Testing Quality',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_3-Script_Runs_in_MatLab' => [
			    'max' => 8,
			    'description' => 'Grade Exercise 3 - Script Runs in MatLab',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_3-Correct_Results' => [
			    'max' => 10,
			    'description' => 'Grade Exercise 3 - Correct Results',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_3-Testing_Quality' => [
			    'max' => 7,
			    'description' => 'Grade Exercise 3 - Testing Quality',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_4-Script_Runs_in_MatLab' => [
			    'max' => 8,
			    'description' => 'Grade Exercise 4 - Script Runs in MatLab',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_4-Correct_Results' => [
			    'max' => 10,
			    'description' => 'Grade Exercise 4 - Correct Results',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Exercise_4-Testing_Quality' => [
			    'max' => 7,
			    'description' => 'Grade Exercise 4 - Testing Quality',
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
	        <p>
	        <strong>Script Runs in MatLab (0 or 8 points):</strong> If the script runs without error messages in MatLab, give 8 points and write “Script OK” in the justification box.
	        </p>
	        
	        <p>
	        Otherwise, give 0 points.  In the justification box clearly explain what was wrong and give a working version of the script.
	        </p>

			<br>	        

	        <p>
	        <strong>Correct Results (0, 6, or 10 points):</strong> Give 10 points if the script produces correct results for ALL legal inputs.   Write “Correct for all legal inputs” in the justification box.
	        </p>
	        
			<p>
			Give 6 points if the script produces correct results for only SOME legal inputs.   In the justification box specify which legal inputs produce incorrect results and why.
			</p>
			
			<p>
			Give 0 points if the script does not produce correct results for any legal inputs, and explain in the justification box.
			</p>
			
			<br>
			
			<p>
	        <strong>Testing Quality (0, 4 or 7 points):</strong> Give 7 points if the script is well tested.  The solver has provided representative inputs that test EVERY branch and included the corresponding outputs.   Write “Well tested for every branch” in the justification box.
	        </p>
	        
			<p>
			Give 4 points if the script is partially tested.  The solver has provided representative inputs that test only SOME branches and included the corresponding outputs or has not provided corresponding outputs for some inputs.   In the justification box explain which branches were not tested.
			</p>
			
			<p>
			Give 0 points if no representative inputs are provided or no corresponding outputs for inputs are provided, and explain in the justification box.
			</p>
	        ',
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
	        'instructions' =>
	        '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong><br>'
	          . 
	        '
	        <p>
	        <strong>Script Runs in MatLab (0 or 8 points):</strong> If the script runs without error messages in MatLab, give 8 points and write “Script OK” in the justification box.
	        </p>
	        
	        <p>
	        Otherwise, give 0 points.  In the justification box clearly explain what was wrong and give a working version of the script.
	        </p>

			<br>	        

	        <p>
	        <strong>Correct Results (0, 6, or 10 points):</strong> Give 10 points if the script produces correct results for ALL legal inputs.   Write “Correct for all legal inputs” in the justification box.
	        </p>
	        
			<p>
			Give 6 points if the script produces correct results for only SOME legal inputs.   In the justification box specify which legal inputs produce incorrect results and why.
			</p>
			
			<p>
			Give 0 points if the script does not produce correct results for any legal inputs, and explain in the justification box.
			</p>
			
			<br>
			
			<p>
	        <strong>Testing Quality (0, 4 or 7 points):</strong> Give 7 points if the script is well tested.  The solver has provided representative inputs that test EVERY branch and included the corresponding outputs.   Write “Well tested for every branch” in the justification box.
	        </p>
	        
			<p>
			Give 4 points if the script is partially tested.  The solver has provided representative inputs that test only SOME branches and included the corresponding outputs or has not provided corresponding outputs for some inputs.   In the justification box explain which branches were not tested.
			</p>
			
			<p>
			Give 0 points if no representative inputs are provided or no corresponding outputs for inputs are provided, and explain in the justification box.
			</p>
	        ',
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
	        <p>
	        <strong>Script Runs in MatLab (0 or 8 points):</strong> If the script runs without error messages in MatLab, give 8 points and write “Script OK” in the justification box.
	        </p>
	        
	        <p>
	        Otherwise, give 0 points.  In the justification box clearly explain what was wrong and give a working version of the script.
	        </p>

			<br>	        

	        <p>
	        <strong>Correct Results (0, 6, or 10 points):</strong> Give 10 points if the script produces correct results for ALL legal inputs.   Write “Correct for all legal inputs” in the justification box.
	        </p>
	        
			<p>
			Give 6 points if the script produces correct results for only SOME legal inputs.   In the justification box specify which legal inputs produce incorrect results and why.
			</p>
			
			<p>
			Give 0 points if the script does not produce correct results for any legal inputs, and explain in the justification box.
			</p>
			
			<br>
			
			<p>
	        <strong>Testing Quality (0, 4 or 7 points):</strong> Give 7 points if the script is well tested.  The solver has provided representative inputs that test EVERY branch and included the corresponding outputs.   Write “Well tested for every branch” in the justification box.
	        </p>
	        
			<p>
			Give 4 points if the script is partially tested.  The solver has provided representative inputs that test only SOME branches and included the corresponding outputs or has not provided corresponding outputs for some inputs.   In the justification box explain which branches were not tested.
			</p>
			
			<p>
			Give 0 points if no representative inputs are provided or no corresponding outputs for inputs are provided, and explain in the justification box.
			</p>
	        ',
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
	          .'an explanation.'
	          
	          .
	          
	         '
	        <p>
	        <strong>Script Runs in MatLab (0 or 8 points):</strong> If the script runs without error messages in MatLab, give 8 points and write “Script OK” in the justification box.
	        </p>
	        
	        <p>
	        Otherwise, give 0 points.  In the justification box clearly explain what was wrong and give a working version of the script.
	        </p>

			<br>	        

	        <p>
	        <strong>Correct Results (0, 6, or 10 points):</strong> Give 10 points if the script produces correct results for ALL legal inputs.   Write “Correct for all legal inputs” in the justification box.
	        </p>
	        
			<p>
			Give 6 points if the script produces correct results for only SOME legal inputs.   In the justification box specify which legal inputs produce incorrect results and why.
			</p>
			
			<p>
			Give 0 points if the script does not produce correct results for any legal inputs, and explain in the justification box.
			</p>
			
			<br>
			
			<p>
	        <strong>Testing Quality (0, 4 or 7 points):</strong> Give 7 points if the script is well tested.  The solver has provided representative inputs that test EVERY branch and included the corresponding outputs.   Write “Well tested for every branch” in the justification box.
	        </p>
	        
			<p>
			Give 4 points if the script is partially tested.  The solver has provided representative inputs that test only SOME branches and included the corresponding outputs or has not provided corresponding outputs for some inputs.   In the justification box explain which branches were not tested.
			</p>
			
			<p>
			Give 0 points if no representative inputs are provided or no corresponding outputs for inputs are provided, and explain in the justification box.
			</p>
	        ',
	      ],
	    ];
	}

	if($asec->assignment_id == 83)//PHIL 334 Quiz
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
	
	        'instructions' => '<p><strong>Number your quiz questions 1, 2, and 3. (Do not include any answers.)</strong></p>'
	        
	        .'<strong>(1) True or False Question:</strong> Write a single sentence that tests knowledge from the chapter. Your question should require the quiz-taker to understand <em>the concepts behind</em> the terms and ideas you are using and doesn\'t just ask them to define the terms.
	        
			<div style="color:blue;">
			Example:
				<ul>
					<li>An okay but not great true or false question:</li>
					<li>(1)At NJIT, "CSLA" is an appreviation for "College of Science and Liberal Arts".</li>
					<ul><li>This sentence is true, but all you really need to look at to find the answer is the abbreviation. You don\'t need to really know what a College of Science and Liberal Arts is.</li></ul>
				</ul>
				
				<ul>
					<li>Here\'s a great true or false question:</li>
					<li>(1)At NJIT, CSLA is the home of the Physics Department and Humanities Department.</li>
					<ul><li>This sentence is true, but to answer it you need to know more than just what "CSLA" stands for - you need to actually know what it is and what departments are part of it.</li></ul>
				</ul>
			</div>
			
			<strong>(2) Matching Question:</strong> Find 3 terms you think are important in the chapter and write a simple definition for each term.
			<ul>
				<li>Number your 3 terms (T1), (T2), and (T3).</li>
				<li>Number your 3 definitions (D4), (D5), and (D6).</li>
				<li>Shuffle your terms and definitions in random orders so T1 <em>only maybe</em> matches D4, and so on.</li>
			</ul>
			
			<strong>(3) Short Answer Question:</strong> Write a question of substance that can be answered in two or three sentences.
	        
	        <div style="color:blue;">
			Example:
				<ul>
					<li>One good example of this would be to describe a situation and ask what term or concept the situation illustrates from the chapter.</li>
					<li>Consider the trolley problem from the first week of class. Here\'s a good short answer question and answer:
					<ul><li>Question: (3)Choosing to kill one innocent in the trolley case is best explained by what moral theory?</li>
					<li>Answer: Choosing to kill one innocent in the trolley case is best explained by utilitarianism. Utilitarianism determines the rightness of actions by consequences and it is better for one innocent to die than five. Killing one innocent is not a good act, but if there is a forced choice between killing one or five, killing one limits the bad consequences.</li></ul>
					<li>Post the question only, not your answer.</li>
				</ul>
			</div>
				
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
	        'instructions' => '
	        <strong>Number your quiz solutions 1, 2, and 3.</strong><br><br>
	        
			<strong>True or False question:</strong> Respond "(1)true" or "(1)false".<br>
			
			<strong>Matching question:</strong> Respond with the three matches in this format: (2) T1 = D4, T2 = D6, T3 = D5<br>
			
			<strong>Short Answer question:</strong> Answer in 2-3 sentences. Include the important points and explain your answer so it is clear that you understand the issues. Start your answer with "(3)".<br>
			
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
			  '(1)True_Or_False' => [
			    'max' => 15,
			    'description' => 'Provide a grade to solution (1)',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  '(2a)Match_for_T1' => [
			    'max' => 15,
			    'description' => 'Provide a grade to solution (T1)',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  '(2b)Match_for_T2' => [
			    'max' => 15,
			    'description' => 'Provide a grade to solution (T2)',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  '(2c)Match_for_T3' => [
			    'max' => 15,
			    'description' => 'Provide a grade to solution (T3)',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  '(3)Short_Answer' => [
			    'max' => 40,
			    'description' => 'Provide a grade to solution (3)',
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
	        
			<strong>Grade each quiz answer using only the scores shown. There is no partial credit.</strong>
			
			<ul>
				<li><strong>(1)True or False solution:</strong>
				<ul><li>If you agree, award 15 points AND write "Correct" (or optionally justify further why you agree).</li>
				<li>If you disagree, award 0 points AND justify why you disagree.</li></ul>
				</li>
				
				<li>
				<strong>(2)For each Matching solution T1, T2, and T3:</strong>
				<ul>
					<li>If you agree with the match, award 15 points AND write "Correct" (or optionally justify why you agree).</li>
					<li>If you disagree with the match, award 0 points AND justify why you disagree</li>
				</ul></li>
				
				<li>
				<strong>(3)Short Answer solution: Award the score shown here AND justify your score.</strong>
				<ul>
					<li>(0 Points) No response or the answer doesn\'t respond to the question at all.</li>
					<li>(20 Points) The answer misses a major point or shows the quiz-taker doesn\'t really understand the issues.</li>
					<li>(30 Points) The answer responds to the question, but misses a minor point or has a small misunderstanding.</li>
					<li>(40 Points) The answer completely responds to the question.</li>
				</ul>
				</li>
				
				</li>
			</ul>
			
	        ',
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
	        'instructions' =>
	        '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong>'
	          . 
	        '
	        
			<strong>Grade each quiz answer using only the scores shown. There is no partial credit.</strong>
			
			<ul>
				<li><strong>(1)True or False solution:</strong>
				<ul><li>If you agree, award 15 points AND write "Correct" (or optionally justify further why you agree).</li>
				<li>If you disagree, award 0 points AND justify why you disagree.</li></ul>
				</li>
				
				<li>
				<strong>(2)For each Matching solution T1, T2, and T3:</strong>
				<ul>
					<li>If you agree with the match, award 15 points AND write "Correct" (or optionally justify why you agree).</li>
					<li>If you disagree with the match, award 0 points AND justify why you disagree</li>
				</ul></li>
				
				<li>
				<strong>(3)Short Answer solution: Award the score shown here AND justify your score.</strong>
				<ul>
					<li>(0 Points) No response or the answer doesn\'t respond to the question at all.</li>
					<li>(20 Points) The answer misses a major point or shows the quiz-taker doesn\'t really understand the issues.</li>
					<li>(30 Points) The answer responds to the question, but misses a minor point or has a small misunderstanding.</li>
					<li>(40 Points) The answer completely responds to the question.</li>
				</ul>
				</li>
				
				</li>
			</ul>
			
	        ',
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
	        
			<strong>Grade each quiz answer using only the scores shown. There is no partial credit.</strong>
			
			<ul>
				<li><strong>(1)True or False solution:</strong>
				<ul><li>If you agree, award 15 points AND write "Correct" (or optionally justify further why you agree).</li>
				<li>If you disagree, award 0 points AND justify why you disagree.</li></ul>
				</li>
				
				<li>
				<strong>(2)For each Matching solution T1, T2, and T3:</strong>
				<ul>
					<li>If you agree with the match, award 15 points AND write "Correct" (or optionally justify why you agree).</li>
					<li>If you disagree with the match, award 0 points AND justify why you disagree</li>
				</ul></li>
				
				<li>
				<strong>(3)Short Answer solution: Award the score shown here AND justify your score.</strong>
				<ul>
					<li>(0 Points) No response or the answer doesn\'t respond to the question at all.</li>
					<li>(20 Points) The answer misses a major point or shows the quiz-taker doesn\'t really understand the issues.</li>
					<li>(30 Points) The answer responds to the question, but misses a minor point or has a small misunderstanding.</li>
					<li>(40 Points) The answer completely responds to the question.</li>
				</ul>
				</li>
				
				</li>
			</ul>
			
	        ',
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
	          .'an explanation.'
	          
	          .
	          
	          '
	        
			<strong>Grade each quiz answer using only the scores shown. There is no partial credit.</strong>
			
			<ul>
				<li><strong>(1)True or False solution:</strong>
				<ul><li>If you agree, award 15 points AND write "Correct" (or optionally justify further why you agree).</li>
				<li>If you disagree, award 0 points AND justify why you disagree.</li></ul>
				</li>
				
				<li>
				<strong>(2)For each Matching solution T1, T2, and T3:</strong>
				<ul>
					<li>If you agree with the match, award 15 points AND write "Correct" (or optionally justify why you agree).</li>
					<li>If you disagree with the match, award 0 points AND justify why you disagree</li>
				</ul></li>
				
				<li>
				<strong>(3)Short Answer solution: Award the score shown here AND justify your score.</strong>
				<ul>
					<li>(0 Points) No response or the answer doesn\'t respond to the question at all.</li>
					<li>(20 Points) The answer misses a major point or shows the quiz-taker doesn\'t really understand the issues.</li>
					<li>(30 Points) The answer responds to the question, but misses a minor point or has a small misunderstanding.</li>
					<li>(40 Points) The answer completely responds to the question.</li>
				</ul>
				</li>
				
				</li>
			</ul>
			
	        ',
	      ],
	    ];
	}
	
	
	if($asec->assignment_id == 84)//Homework 5
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
	
	        'instructions' => '
	        
			<p>
			<em>(Homework 5 instructions on Moodle provide details for each step and examples. The following is just a summary.)</em>
			</p>
			
			<ol>
				<li>
				Build <strong>Ten (10)</strong> MatLab expressions. You can <strong>ONLY</strong> use <strong>numbers</strong> (<strong>NO variables</strong>), <strong>arithmetic operators</strong> including +, -, *, /, ^, <strong>relational operators</strong> including ==, ~=, <, <=, >, >= and <strong>logical operators</strong> including ~, &, &&, |, ||, xor. Each expression should meet the following requirements:
					<ul>
						<li>
							It should have <strong>at least 1 relational operator and/or logical operator</strong>;
						</li>
						
						<li>
							It should have <strong>at least 3 arithmetic, relational and/or logical</strong> operators and <strong>at most 5</strong> operators;
						</li>
						
						<li>
							It should be a legitimate MatLab expression.
						</li>
						
						<li>
							Try to use as many different operators as you can across your 10 expressions.
						</li>
					</ul>
				</li>
				
				<li>
					Use MatLab to evaluate each expression: Type in the expression in MatLab command window and let MatLab to evaluate the expression.  If MatLab prints out an error message, modify the expression to remove the error.
				</li>
				
				<li>
					Include all the MatLab expressions and their values into a table.  Follow the example in the instructions on Moodle.
				</li>
			</ol>
			
			<p>
				<strong>Ensure Anonymity + Submit: </strong>Ensure your Word document is anonymous.  Follow the Homework 5 link in Moodle, upload the document and then click submit.
			</p>
			
	        ',
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
	        'instructions' =>
	        '
			<ol>
				<li>Edit as necessary, upload the edited document here, and in the comments box below, explain why you made changes. If no edits are necessary, type "Approved" in the comments box.</li>
				<li>Click on the submit button.</li>
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
	
			'optional' => true,
	
	        'user alias' => 'dispute',
	
	        'reference task' => 'edit problem',
	        'instructions' => '
	        	<p>
	        	Create solutions following the template in the instructions on Moodle.  <em>(Homework 5 instructions on Moodle provide details for each step and examples.   The following is just a summary.)</em>
	        	</p>
	        	
				<p>
	        	For each question, write the <strong>steps</strong> to evaluate the MatLab expression in the Explanation column in the Word document. You need to pay attention to the precedence of the operators, <strong>carry out the corresponding operations in a correct order, and calculate intermediate results</strong> (you may use a calculator).
	        	</p>
	        	
				<p>
	        	<strong>Important: </strong> Make sure that you have at least one separate step for each operator in the answer.
	        	</p>
	        	
				<p>
	        	<strong>Ensure Anonymity + Submit: </strong> Ensure your Word document is anonymous.  Follow the Homework 5 link in Moodle, upload the document and then click submit.
	        	</p>
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
			  'Question1_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 1',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question1_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 1',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question2_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 2',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question2_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 2',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question3_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 3',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question3_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 3',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question4_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 4',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question4_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 4',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question5_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 5',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question5_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 5',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question6_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 6',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question6_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 6',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question7_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 7',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question7_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 7',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question8_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 8',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question8_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 8',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question9_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 9',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question9_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 9',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question10_Correct_Order_of_Steps' => [
			    'max' => 8,
			    'description' => 'Grade the order of steps for question 10',
			    'grade' => 0,
			    'justification' => '',
			  ],
			  
			  'Question10_Correct_Calculations' => [
			    'max' => 2,
			    'description' => 'Grade the calculations for question 10',
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
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	        'instructions' =>
	        '<strong>Because the regular graders did not give the same '
	          .'grade, please resolve the grade disagreement. Assign your '
	          .'own score and justification for each part of the grade, and afterwards '
	          .'summarize why you resolved it this way.</strong><br>'
	          . 
	        '
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
	          .'an explanation.'
	          
	          .
	          
	         '
	        <p>
	        <strong>Correct Order of Steps (0-8 points):</strong> If all the steps are in a correct order, give 8 points. Otherwise, divide 8 points by the number of steps, and then give that number of points to each step in a correct order. (Sometimes all the steps following an out of order step will also be out of order, and sometimes just some steps in the middle of the process will be out of order. You will just deduct points for those out of order that lead to incorrect evaluation of the expression.)  Round the number of total points up to nearest integer.
	        </p>
	        
			<p>
	        If the total points is not 8, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        <br>
	        <p>
	        <strong>Correct Calculations (0-2 points):</strong> Give 2 points if the calculation in every step is correct; give 1 point if the calculation(s) in some steps (not all) are correct; and give 0 points if there is NOT a step with correct calculation.
	        </p>
	        
			<p>
	        If the total points is not 2, in the justification box clearly explain what was wrong and give a correct answer.
	        </p>
	        ',
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
			    'max' => 0,
			    'min' => -6,
			    'description' => 'How correct is this answer?',
			  ],
			  'somethingelse' => [
			    'grade' => 0,
			    'justification' => 0,
			    'max' => 10,
			    'description' => 'How whatever is this answer?',
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
			
			'resolve range' => 3,
			
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
