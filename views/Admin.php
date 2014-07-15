<?php
function groupgrade_admin_dash()
{
  return ''; 
}

function groupgrade_about()
{
  return '<br><br>
  <head>
  <style type="text/css">
        #div-image {
            float: right;
            border: 2px double maroon;
			padding-left: 10px;
			margin-top: 10px;
			margin-right: 10px;
			margin-left: 10px;
            }
        li {
			margin-left: 40px;
			margin-bottom: 10px;
			font-weight: bold;
           }
		   
		li.blue {
			color:blue;
			}
		li.black {
			color:black;
			}
		li.orange {
			color:orange;
			}
		li.purple {
			color: purple;
			}
		li.green {
			color: green;
			}
	
    </style>
    </head>
    
    <body>
    <div id="div-image">
        <p>
        '//<img src="sites/default/files/pictures/process-by-user.jpg">
        .'
        <img src="http://web.njit.edu/~bieber/outgoing/process-by-user.jpg">
        </p>
    </div>
    <div id="div-text">
    </div>
		<h3>
            Collaborative Learning Through Assessment (CLASS)
        </h3>
        <p>
            Traditionally, students only solve problems.   In the CLASS system, students learn so much more by engaging with most stages of an assignment (see right).  
        </p>
        <p>
            Students not only solve problems, but also create them, grade solutions from fellow students and optionally can dispute their grades.  
        </p>
        <p>
            Here\'s the process you\'ll follow.  Everything shows as anonymous:
        </p>
        <p>
            <ul>
				<li class = "blue">
					Each student creates a problem according to the instructions
				</li>	
				<li class = "black">
					The instructor optionally edits the problem to ensure quality
				</li>	
				<li class = "orange">
					Another student solves the problem
				</li>		
				<li class = "blue">
					Two students grade the solution, including the problem creator
				</li>	
				<li class = "green">	
					If the graders disagree, another student resolves the grade
				</li>
				<li class = "orange">
					Optionally, the problem solver can dispute the grade
				</li>
				<li class = "black">
					The instructor resolves any disputes
				</li>
				<li class = "purple">
					Students can see everything their peers have done anonymously
				</li>
            </ul>
            </p>
            <p>
            The instructor can add additional steps to match specific assignments, exams or projects.
            </p>
            <p>
			For more details <a href="http://ec2-54-81-177-27.compute-1.amazonaws.com/class/about2">click here</a>.
			</p>
  ';
}

function groupgrade_about2()
{
	return '
	<head>
    <title>About CLASS</title>
    <style type="text/css">
        #div-image {
            float: left;
            border: 2px double maroon;
			padding-right: 4px;
			padding-left: 4px;
			padding-top: 4px;
			padding-bottom: 4px;
%			margin-top: 4px;
			margin-right: 6px;
           }
    </style>
	
    </head>
    
    <body>
    <div id="div-image">
        <p>
        <img src="http://web.njit.edu/~bieber/outgoing/process-with-learning-types.jpg">
        </p>
    </div>
    <div id="div-text">
    </div>
		<h3>
            Collaborative Learning Through Assessment (CLASS) &ndash; Goals and Further Details
        </h3>
		<p> <a href="http://ec2-54-81-177-27.compute-1.amazonaws.com/class/about">(return to About CLASS  overview page)</a>
		</p>
        <p>
            CLASS is a framework designed to create learning opportunities and increase student motivation for learning through active participation in the entire Problem Life Cycle (PLC; see left )  
        </p>
        <p>
            Traditionally, students are only engaged during the problem solving stage. However, CLASS incorporates learning approaches such as problem-based learning, feedback, peer-assessment and self-assessment through allowing students to participate in each of the PLC stages.   
        </p>
        <p>
            In short, students not only solve problems, actively engage in creating the problems, grading solutions from fellow students, and optionally disputing grades, in which case they must grade their own solutions with written justifications.
        </p>

        <p>
			CLASS manages the process and frees instructors to focus on mentoring students where most helpful.    Instructors can customize and add additional tasks as appropriate for each activity.  CLASS thus provides a flexible environment for engaging their students in assignments, quizzes and other kinds of activities.
        </p>
        <p>
			For more information contact the NJIT CLASS team at <a href="mailto:bieber@njit.edu">bieber@njit.edu</a>. 
		</p>
    </body>
    ';
}