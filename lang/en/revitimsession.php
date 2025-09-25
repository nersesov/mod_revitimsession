<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the revitimsession module
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Module information
$string['modulename'] = 'Revitim Session';
$string['modulenameplural'] = 'Revitim Sessions';
$string['modulename_help'] = 'The Revitim Session module allows students to participate in real-time collaborative sessions using Revit software.';
$string['pluginname'] = 'Revitim Session';
$string['pluginadministration'] = 'Revitim Session administration';

// Capabilities
$string['revitimsession:addinstance'] = 'Add a new Revitim Session';
$string['revitimsession:view'] = 'View Revitim Session';
$string['revitimsession:submit'] = 'Submit work to Revitim Session';
$string['revitimsession:grade'] = 'Grade Revitim Session submissions';

// General
$string['name'] = 'Name';
$string['name_help'] = 'Enter a name for this Revitim Session activity.';
$string['description'] = 'Description';
$string['intro'] = 'Introduction';
$string['intro_help'] = 'This description will be displayed to students when they access the Revitim Session.';

// Form fields
$string['sessionname'] = 'Session name';
$string['sessionname_help'] = 'Enter a descriptive name for this Revitim session.';
$string['sessiondescription'] = 'Session description';
$string['sessiondescription_help'] = 'Provide detailed instructions or information about this session.';
$string['starttime'] = 'Start time';
$string['starttime_help'] = 'When should this session begin?';
$string['endtime'] = 'End time';
$string['endtime_help'] = 'When should this session end?';

$string['allowguest'] = 'Allow guest access';
$string['allowguest_help'] = 'If enabled, guests can view the session without being enrolled in the course.';

// Status messages
$string['sessionnotstarted'] = 'Session has not started yet';
$string['sessioninprogress'] = 'Session is in progress';
$string['sessionended'] = 'Session has ended';
$string['sessionfull'] = 'Session is full';
$string['notenrolled'] = 'You are not enrolled in this course';
$string['nopermission'] = 'You do not have permission to access this session';

// Actions
$string['joinsession'] = 'Join session';
$string['leavesession'] = 'Leave session';
$string['submitsession'] = 'Submit session work';
$string['viewsession'] = 'View session';
$string['editsession'] = 'Edit session';
$string['deletesession'] = 'Delete session';
$string['perform_exam'] = 'Perform Exam';
$string['perform_study'] = 'Perform Study Session';
$string['study_unit_prefix'] = 'Study Unit';
$string['subunit_prefix'] = 'Subunit';
$string['study_session_heading'] = 'Study Session';
$string['study_session'] = 'Study Session';
$string['practice_exam'] = 'Practice Exam';
$string['practice:session_deleted_successfully'] = 'Study session deleted successfully.';
$string['practice:delete_error'] = 'Error deleting study session.';

// Grading
$string['grade'] = 'Grade';
$string['grading'] = 'Grading';
$string['nograde'] = 'No grade';
$string['graded'] = 'Graded';
$string['notgraded'] = 'Not graded';
$string['gradeitem'] = 'Grade item';
$string['gradeitem_help'] = 'Select a grade item for this session.';
$string['grade_help'] = 'The maximum grade for this session.';

// Access and timing
$string['access'] = 'Access';
$string['timing'] = 'Timing';

// Settings
$string['settings'] = 'Settings';
$string['generalsettings'] = 'General settings';
$string['sessionsettings'] = 'Session settings';
$string['gradingsettings'] = 'Grading settings';

// Errors
$string['error:invalidid'] = 'Invalid session ID';
$string['error:sessionnotfound'] = 'Session not found';
$string['error:alreadyjoined'] = 'You are already in this session';
$string['error:notjoined'] = 'You are not in this session';
$string['error:sessionfull'] = 'Session is full';
$string['error:sessionended'] = 'Session has ended';
$string['error:sessionnotstarted'] = 'Session has not started yet';
$string['error:nopermission'] = 'You do not have permission to perform this action';

// Success messages
$string['success:joined'] = 'Successfully joined the session';
$string['success:left'] = 'Successfully left the session';
$string['success:saved'] = 'Session saved successfully';
$string['success:deleted'] = 'Session deleted successfully';
$string['success:submitted'] = 'Work submitted successfully';

// Notifications
$string['notification:sessionstarting'] = 'Session starting soon';
$string['notification:sessionstarted'] = 'Session has started';
$string['notification:sessionending'] = 'Session ending soon';
$string['notification:sessionended'] = 'Session has ended';

// Help
$string['help:gettingstarted'] = 'Getting started with Revitim Session';
$string['help:gettingstarted_help'] = 'This module allows you to participate in real-time collaborative sessions using Revit software. Click "Join session" to begin.';

// Privacy
$string['privacy:metadata'] = 'The Revitim Session plugin stores session data and user participation records.';
$string['privacy:metadata:revitimsession'] = 'Information about Revitim sessions';
$string['privacy:metadata:revitimsession:name'] = 'The name of the session';
$string['privacy:metadata:revitimsession:description'] = 'The description of the session';
$string['privacy:metadata:revitimsession:timecreated'] = 'The time when the activity instance was created';
$string['privacy:metadata:revitimsession:timemodified'] = 'The time when the session was last modified';
$string['privacy:metadata:revitimsession_submissions'] = 'Information about user submissions to sessions';
$string['privacy:metadata:revitimsession_submissions:userid'] = 'The ID of the user who made the submission';
$string['privacy:metadata:revitimsession_submissions:sessionid'] = 'The ID of the session';
$string['privacy:metadata:revitimsession_submissions:timecreated'] = 'The time when the submission was made';
$string['privacy:metadata:revitimsession_submissions:timemodified'] = 'The time when the submission was last modified';

// Admin settings
$string['setting:enabled'] = 'Enable Revitim Session';
$string['setting:enabled_desc'] = 'Enable or disable the Revitim Session module globally.';

$string['setting:allowguestdefault'] = 'Allow guest access by default';
$string['setting:allowguestdefault_desc'] = 'Whether to allow guest access to sessions by default.';
$string['setting:sessiontimeout'] = 'Session timeout (minutes)';
$string['setting:sessiontimeout_desc'] = 'How long to keep sessions active before timing out (in minutes).';
$string['setting:maxsessionspercourse'] = 'Maximum sessions per course';
$string['setting:maxsessionspercourse_desc'] = 'Maximum number of Revitim Session activities that can be created per course.';
$string['setting:enablegrading'] = 'Enable grading';
$string['setting:enablegrading_desc'] = 'Enable grading functionality for Revitim sessions.';
$string['setting:grademethod'] = 'Default grading method';
$string['setting:grademethod_desc'] = 'Default grading method for new sessions.';
$string['setting:maxgrade'] = 'Default maximum grade';
$string['setting:maxgrade_desc'] = 'Default maximum grade for new sessions.';

// Admin capabilities
$string['setting:manageall'] = 'Manage all sessions';
$string['setting:manageall_desc'] = 'Allow administrators to manage all Revitim sessions across the site.';
$string['setting:viewreports'] = 'View reports';
$string['setting:viewreports_desc'] = 'Allow viewing of detailed reports for all sessions.';

// Admin actions
$string['admin:managesessions'] = 'Manage Revitim Sessions';
$string['admin:viewsessions'] = 'View all sessions';
$string['admin:deletesessions'] = 'Delete sessions';
$string['admin:exportdata'] = 'Export session data';
$string['admin:importdata'] = 'Import session data';

// Admin reports
$string['report:allsessions'] = 'All Revitim Sessions';
$string['report:sessionparticipation'] = 'Session Participation';
$string['report:sessiongrades'] = 'Session Grades';
$string['report:useractivity'] = 'User Activity';

// Admin notifications
$string['notification:sessioncreated'] = 'New activity instance created';
$string['notification:sessiondeleted'] = 'Session deleted';
$string['notification:sessionmodified'] = 'Session modified';
$string['notification:userjoined'] = 'User joined session';
$string['notification:userleft'] = 'User left session';

// Admin help
$string['help:administration'] = 'Revitim Session Administration';
$string['help:administration_help'] = 'This page allows you to configure global settings for the Revitim Session module.';
$string['help:reports'] = 'Session Reports';
$string['help:reports_help'] = 'View detailed reports about session usage and participation.';

// Form validation
$string['error:required'] = 'This field is required';
$string['error:invalidname'] = 'Invalid name format';
$string['error:invalidtime'] = 'Invalid time format';
$string['error:starttimeafterendtime'] = 'Start time must be before end time';

$string['error:invalidgrade'] = 'Invalid grade value';
$string['error:gradetoohigh'] = 'Grade cannot exceed maximum grade';
$string['error:gradetoolow'] = 'Grade cannot be negative';

// Form labels
$string['label:name'] = 'Name';
$string['label:description'] = 'Description';
$string['label:starttime'] = 'Start time';
$string['label:endtime'] = 'End time';

$string['label:allowguest'] = 'Allow guest access';
$string['label:grade'] = 'Grade';
$string['label:grademethod'] = 'Grading method';
$string['label:maxgrade'] = 'Maximum grade';
$string['label:scale'] = 'Scale';
$string['label:passgrade'] = 'Pass grade';

// Form help text
$string['help:name'] = 'Enter a descriptive name for this session.';
$string['help:description'] = 'Provide detailed instructions or information about this session.';
$string['help:starttime'] = 'Select when this session should begin.';
$string['help:endtime'] = 'Select when this session should end.';

$string['help:allowguest'] = 'If enabled, guests can view the session without being enrolled in the course.';
$string['help:grade'] = 'Grade for this submission.';
$string['help:grademethod'] = 'Method used to calculate the final grade.';
$string['help:maxgrade'] = 'Maximum grade possible for this session.';
$string['help:scale'] = 'Scale to use for grading.';
$string['help:passgrade'] = 'Minimum grade required to pass.';

// Form options
$string['option:yes'] = 'Yes';
$string['option:no'] = 'No';
$string['option:enabled'] = 'Enabled';
$string['option:disabled'] = 'Disabled';
$string['option:automatic'] = 'Automatic';
$string['option:manual'] = 'Manual';
$string['option:highest'] = 'Highest grade';
$string['option:lowest'] = 'Lowest grade';
$string['option:average'] = 'Average grade';
$string['option:first'] = 'First submission';
$string['option:last'] = 'Last submission';

// Form buttons
$string['button:save'] = 'Save';
$string['button:cancel'] = 'Cancel';
$string['button:submit'] = 'Submit';
$string['button:reset'] = 'Reset';
$string['button:preview'] = 'Preview';
$string['button:back'] = 'Back';
$string['button:next'] = 'Next';
$string['button:previous'] = 'Previous';
$string['button:finish'] = 'Finish';

// Form messages
$string['message:formsubmitted'] = 'Form submitted successfully';
$string['message:formsaved'] = 'Form saved successfully';
$string['message:formcancelled'] = 'Form cancelled';
$string['message:formreset'] = 'Form reset to default values';
$string['message:validationerrors'] = 'Please correct the following errors:';
$string['message:confirmdelete'] = 'Are you sure you want to delete this item?';
$string['message:confirmcancel'] = 'Are you sure you want to cancel? Any unsaved changes will be lost.';

// Form sections
$string['section:general'] = 'General information';
$string['section:timing'] = 'Timing';
$string['section:access'] = 'Access control';
$string['section:grading'] = 'Grading';
$string['section:advanced'] = 'Advanced settings';

// Form placeholders
$string['placeholder:entername'] = 'Enter session name';
$string['placeholder:enterdescription'] = 'Enter session description';

$string['placeholder:entergrade'] = 'Enter grade';
$string['placeholder:entermaxgrade'] = 'Enter maximum grade';
$string['placeholder:enterpassgrade'] = 'Enter pass grade';

// JavaScript alerts and confirmations
$string['js:confirmdelete'] = 'Are you sure you want to delete this session?';
$string['js:confirmleave'] = 'Are you sure you want to leave this session?';
$string['js:confirmcancel'] = 'Are you sure you want to cancel? Any unsaved changes will be lost.';
$string['js:confirmjoin'] = 'Do you want to join this session?';
$string['js:confirmsubmit'] = 'Are you sure you want to submit your work?';
$string['js:confirmgrade'] = 'Are you sure you want to Grade?';
$string['js:grade_session'] = 'Grade Session';
$string['js:button:goback'] = 'Go Back';
$string['js:button:grade'] = 'Grade';

// Study session specific JavaScript strings
$string['js:status:complete'] = 'Complete';
$string['js:status:incomplete'] = 'Incomplete';
$string['js:status:unseen'] = 'Unseen';
$string['js:feedback:correct'] = 'Correct!';
$string['js:feedback:incorrect'] = 'Incorrect';
$string['js:button:instructions'] = 'Instructions';
$string['js:button:grading'] = 'Grading...';
$string['js:button:resume'] = 'Resume';
$string['js:button:pause'] = 'Pause';
$string['js:search:no_matches'] = 'No matches found';
$string['js:review:no_incomplete'] = 'There are no incomplete questions to review.';
$string['js:review:no_marked'] = 'No questions have been marked for review. Please make your selections and try again.';
$string['js:confirm:save_logout'] = 'Are you sure you want to save your progress and logout? Your answers and remaining time will be saved.';
$string['js:feedback:no_feedback'] = 'No feedback available.';
$string['js:stats:summary'] = 'Complete: {$a->complete}, Incomplete: {$a->incomplete}, Unseen: {$a->unseen}';
$string['js:pause:message'] = 'Quiz is Paused. Click Unpause to continue.';
$string['js:pause:unpause'] = 'Unpause';
$string['js:delete:title'] = 'DISCARD SESSION';
$string['js:delete:message'] = 'Are you sure you want to discard this quiz?';
$string['js:delete:go_back'] = 'Go Back';
$string['js:delete:discard'] = 'Discard';
$string['js:error:prefix'] = 'Error: ';
$string['js:link:test_bank'] = 'Test Bank Home Screen';

// Exam grading messages
$string['exam_graded_successfully'] = 'Exam graded successfully';
$string['exam_grading_error'] = 'Error occurred while grading the exam';
$string['invalidjson'] = 'Invalid data format received';

// Performance analysis page
$string['performance_analysis'] = 'Performance Analysis';
$string['performance_analysis_title'] = 'Performance Analysis';
$string['grade_report_title'] = 'Grade Report';
$string['session_type_text'] = 'Session Type';
$string['session_type'] = 'Practice Exam';
$string['quiz_duration_text'] = 'Quiz Duration';
$string['per_question_text'] = 'Per Question';
$string['mins_text'] = 'MINS';
$string['score_total_questions_text'] = 'Score based on total questions';
$string['score_answered_questions_text'] = 'Score based on total answered questions';
$string['questions_seen_text'] = 'Questions Seen';
$string['questions_answered_text'] = 'Questions Answered';
$string['questions_incorrect_text'] = 'Questions Incorrect';
$string['study_score_caption'] = 'Your test score is determined by the number of questions answered correctly.<br><hr><br>Nothing from this point on is working!';
$string['view_review_session_text'] = 'View Review Session';
$string['skip_start_new_quiz_text'] = 'Skip & Start a new quiz';
$string['create_new_session_text'] = 'Create a new study session from';
$string['missed_or_marked_text'] = 'Missed or marked questions';
$string['questions_text'] = 'Questions';
$string['or_create_other_quizzes_text'] = 'Or create these other quizzes';
$string['total_questions_session_text'] = 'Total number of questions in this session';
$string['questions_correct_text'] = 'Number of questions answered correctly';
$string['questions_not_answered_text'] = 'Number of questions not answered';
$string['questions_marked_text'] = 'Number of questions marked';
$string['expand_other_quizzes_text'] = 'Expand Other Quizzes';
$string['detailed_breakdown_title'] = 'Detailed Quiz Breakdown';
$string['question_number_text'] = 'Question #';
$string['question_text'] = 'Question';
$string['status_text'] = 'Status';
$string['result_text'] = 'Result';
$string['correct_text'] = 'Correct';
$string['incorrect_text'] = 'Incorrect';
$string['unanswered_text'] = 'Unanswered';
$string['marked_text'] = 'Marked';

// Tab navigation
$string['grade_report_tab_text'] = 'Grade Report';
$string['study_session_tab_text'] = 'Study Session';
$string['exam_session_tab_text'] = 'Exam Session';
$string['history_tab_text'] = 'History';

// Tab titles and placeholders
$string['study_session_title'] = 'Study Session Analysis';
$string['exam_session_title'] = 'Exam Session Analysis';
$string['history_title'] = 'History';
$string['study_session_placeholder_text'] = 'Study session analysis content will be displayed here.';
$string['exam_session_placeholder_text'] = 'Exam session analysis content will be displayed here.';
$string['history_placeholder_text'] = 'History content will be displayed here.';
$string['no_practice_exam_found'] = 'No practice exam found for this user in this course.';

// JavaScript notifications
$string['js:sessionjoined'] = 'Successfully joined the session';
$string['js:sessionleft'] = 'Successfully left the session';
$string['js:workSubmitted'] = 'Work submitted successfully';
$string['js:sessionSaved'] = 'Session saved successfully';
$string['js:sessionDeleted'] = 'Session deleted successfully';
$string['js:connectionLost'] = 'Connection lost. Attempting to reconnect...';
$string['js:connectionRestored'] = 'Connection restored';
$string['js:reconnecting'] = 'Reconnecting...';
$string['js:reconnected'] = 'Reconnected successfully';

// JavaScript errors
$string['js:error:network'] = 'Network error occurred';
$string['js:error:timeout'] = 'Request timed out';
$string['js:error:server'] = 'Server error occurred';
$string['js:error:unauthorized'] = 'You are not authorized to perform this action';
$string['js:error:sessionfull'] = 'Session is full';
$string['js:error:sessionended'] = 'Session has ended';
$string['js:error:sessionnotstarted'] = 'Session has not started yet';
$string['js:error:alreadyjoined'] = 'You are already in this session';
$string['js:error:notjoined'] = 'You are not in this session';
$string['js:error:invalidinput'] = 'Invalid input provided';
$string['js:error:validation'] = 'Validation error occurred';

// JavaScript loading states
$string['js:loading'] = 'Loading...';
$string['js:saving'] = 'Saving...';
$string['js:submitting'] = 'Submitting...';
$string['js:joining'] = 'Joining session...';
$string['js:leaving'] = 'Leaving session...';
$string['js:connecting'] = 'Connecting...';
$string['js:disconnecting'] = 'Disconnecting...';

// JavaScript UI elements
$string['js:button:join'] = 'Join';
$string['js:button:leave'] = 'Leave';
$string['js:button:submit'] = 'Submit';
$string['js:button:save'] = 'Save';
$string['js:button:cancel'] = 'Cancel';
$string['js:button:close'] = 'Close';
$string['js:button:retry'] = 'Retry';
$string['js:button:reconnect'] = 'Reconnect';

// JavaScript status messages
$string['js:status:connected'] = 'Connected';
$string['js:status:disconnected'] = 'Disconnected';
$string['js:status:connecting'] = 'Connecting';
$string['js:status:reconnecting'] = 'Reconnecting';
$string['js:status:error'] = 'Error';
$string['js:status:ready'] = 'Ready';
$string['js:status:busy'] = 'Busy';
$string['js:status:waiting'] = 'Waiting';

// JavaScript tooltips
$string['js:tooltip:join'] = 'Join this session';
$string['js:tooltip:leave'] = 'Leave this session';
$string['js:tooltip:submit'] = 'Submit your work';
$string['js:tooltip:save'] = 'Save changes';
$string['js:tooltip:cancel'] = 'Cancel changes';
$string['js:tooltip:close'] = 'Close dialog';
$string['js:tooltip:retry'] = 'Retry operation';
$string['js:tooltip:reconnect'] = 'Reconnect to session';

// JavaScript placeholders
$string['js:placeholder:search'] = 'Search sessions...';
$string['js:placeholder:message'] = 'Type your message...';
$string['js:placeholder:comment'] = 'Add a comment...';
$string['js:placeholder:filename'] = 'Enter filename...';

// JavaScript accessibility
$string['js:aria:sessionlist'] = 'List of sessions';
$string['js:aria:sessionitem'] = 'Session item';
$string['js:aria:joinbutton'] = 'Join session button';
$string['js:aria:leavebutton'] = 'Leave session button';
$string['js:aria:submitbutton'] = 'Submit work button';
$string['js:aria:status'] = 'Connection status';
$string['js:aria:loading'] = 'Loading indicator';
$string['js:aria:error'] = 'Error message';
$string['js:aria:success'] = 'Success message';

// Email subjects
$string['email:subject:sessioncreated'] = 'New Revitim Session activity created: {$a}';
$string['email:subject:sessionstarting'] = 'Revitim Session starting soon: {$a}';
$string['email:subject:sessionstarted'] = 'Revitim Session has started: {$a}';
$string['email:subject:sessionending'] = 'Revitim Session ending soon: {$a}';
$string['email:subject:sessionended'] = 'Revitim Session has ended: {$a}';
$string['email:subject:userjoined'] = 'User joined Revitim Session: {$a}';
$string['email:subject:userleft'] = 'User left Revitim Session: {$a}';
$string['email:subject:workSubmitted'] = 'Work submitted to Revitim Session: {$a}';
$string['email:subject:gradeassigned'] = 'Grade assigned for Revitim Session: {$a}';
$string['email:subject:sessionreminder'] = 'Reminder: Revitim Session tomorrow: {$a}';

// Email greetings
$string['email:greeting:student'] = 'Dear {$a},';
$string['email:greeting:teacher'] = 'Dear {$a},';
$string['email:greeting:admin'] = 'Dear {$a},';
$string['email:greeting:general'] = 'Hello {$a},';

// Email content - Session created
$string['email:content:sessioncreated'] = 'A new Revitim Session activity has been created in the course "{$a->coursename}".';
$string['email:content:sessioncreated_details'] = 'Activity details:
- Name: {$a->sessionname}
- Description: {$a->description}
- Start time: {$a->starttime}
- End time: {$a->endtime}
';

// Email content - Session starting
$string['email:content:sessionstarting'] = 'The Revitim Session "{$a->sessionname}" will start in {$a->timeuntilstart}.';
$string['email:content:sessionstarting_details'] = 'Please make sure you are ready to join the session at the scheduled time.';

// Email content - Session started
$string['email:content:sessionstarted'] = 'The Revitim Session "{$a->sessionname}" has now started.';
$string['email:content:sessionstarted_details'] = 'You can now join the session and begin collaborating.';

// Email content - Session ending
$string['email:content:sessionending'] = 'The Revitim Session "{$a->sessionname}" will end in {$a->timeuntilend}.';
$string['email:content:sessionending_details'] = 'Please save your work and prepare to leave the session.';

// Email content - Session ended
$string['email:content:sessionended'] = 'The Revitim Session "{$a->sessionname}" has now ended.';
$string['email:content:sessionended_details'] = 'Thank you for participating in the session.';

// Email content - User joined
$string['email:content:userjoined'] = '{$a->username} has joined the Revitim Session "{$a->sessionname}".';
$string['email:content:userjoined_details'] = 'User joined at: {$a->jointime}';

// Email content - User left
$string['email:content:userleft'] = '{$a->username} has left the Revitim Session "{$a->sessionname}".';
$string['email:content:userleft_details'] = 'User left at: {$a->leavetime}';

// Email content - Work submitted
$string['email:content:workSubmitted'] = 'Work has been submitted to the Revitim Session "{$a->sessionname}".';
$string['email:content:workSubmitted_details'] = 'Submitted by: {$a->username}
Submitted at: {$a->submissiontime}
Comments: {$a->comments}';

// Email content - Grade assigned
$string['email:content:gradeassigned'] = 'A grade has been assigned for your work in the Revitim Session "{$a->sessionname}".';
$string['email:content:gradeassigned_details'] = 'Grade: {$a->grade}
Maximum grade: {$a->maxgrade}
Feedback: {$a->feedback}
Graded by: {$a->grader}
Graded at: {$a->gradetime}';

// Email content - Session reminder
$string['email:content:sessionreminder'] = 'This is a reminder that the Revitim Session "{$a->sessionname}" will start tomorrow.';
$string['email:content:sessionreminder_details'] = 'Session details:
- Start time: {$a->starttime}
- End time: {$a->endtime}
- Location: {$a->location}';

// Email footers
$string['email:footer:course'] = 'Course: {$a}';
$string['email:footer:session'] = 'Session: {$a}';
$string['email:footer:contact'] = 'If you have any questions, please contact your instructor.';
$string['email:footer:technical'] = 'If you experience technical difficulties, please contact technical support.';
$string['email:footer:unsubscribe'] = 'To unsubscribe from these notifications, please update your notification preferences.';

// Email signatures
$string['email:signature:system'] = 'Best regards,
Moodle System';
$string['email:signature:instructor'] = 'Best regards,
{$a}';
$string['email:signature:admin'] = 'Best regards,
Moodle Administrator';

// Email placeholders
$string['email:placeholder:username'] = 'User name';
$string['email:placeholder:coursename'] = 'Course name';
$string['email:placeholder:sessionname'] = 'Session name';
$string['email:placeholder:description'] = 'Session description';
$string['email:placeholder:starttime'] = 'Start time';
$string['email:placeholder:endtime'] = 'End time';

$string['email:placeholder:timeuntilstart'] = 'Time until start';
$string['email:placeholder:timeuntilend'] = 'Time until end';
$string['email:placeholder:jointime'] = 'Join time';
$string['email:placeholder:leavetime'] = 'Leave time';
$string['email:placeholder:submissiontime'] = 'Submission time';
$string['email:placeholder:comments'] = 'Comments';
$string['email:placeholder:grade'] = 'Grade';
$string['email:placeholder:maxgrade'] = 'Maximum grade';
$string['email:placeholder:feedback'] = 'Feedback';
$string['email:placeholder:grader'] = 'Grader';
$string['email:placeholder:gradetime'] = 'Grade time';
$string['email:placeholder:location'] = 'Location';

// Events
$string['event:sessioncreated'] = 'Activity instance created';
$string['event:sessionupdated'] = 'Activity instance updated';
$string['event:sessiondeleted'] = 'Activity instance deleted';
$string['event:coursemoduleviewed'] = 'Course module viewed';
$string['event:coursemoduleinstancelistviewed'] = 'Course module instance list viewed';

// Menu interface
$string['menu:title'] = 'Choose your activity';
$string['menu:practiceexam'] = 'Practice Exam';
$string['menu:practiceexam_desc'] = 'Take a practice exam to test your knowledge and prepare for the real assessment.';
$string['menu:resumestudy'] = 'Resume Study Session';
$string['menu:resumestudy_desc'] = 'Continue your previous study session or start a new collaborative learning experience.';
$string['menu:performancestats'] = 'Performance Stats';
$string['menu:performancestats_desc'] = 'View your performance statistics, progress reports, and learning analytics.';
$string['menu:start'] = 'Start';
$string['menu:createpracticeexam'] = 'Create Practice Exam';
$string['menu:resumepracticeexam'] = 'Resume Practice Exam';
$string['menu:studysession'] = 'Study Session';
$string['menu:studysession_desc'] = 'Create or resume a study session with practice questions';
$string['menu:createstudysession'] = 'Create Study Session';
$string['menu:resumestudysession'] = 'Resume Study Session';
$string['practice:progress_saved'] = 'Your progress has been saved successfully.';
$string['practice:save_error'] = 'Error saving your progress. Please try again.';
$string['menu:resume'] = 'Resume';
$string['menu:view'] = 'View';
$string['menu:teacheractions'] = 'Teacher Actions';

// Practice exam strings
$string['practice:description'] = 'Practice exams help you prepare for the real assessment by testing your knowledge in a simulated environment.';
$string['practice:new'] = 'Start New Practice Exam';
$string['practice:new_desc'] = 'Begin a fresh practice exam with new questions and scenarios.';
$string['practice:continue'] = 'Continue Practice Exam';
$string['practice:continue_desc'] = 'Resume an existing practice exam where you left off.';
$string['practice:startnew'] = 'Start New';
$string['practice:continue_exam'] = 'Continue';

// Question bank strings
$string['practice:available_questions'] = 'Available Questions from Question Bank';
$string['practice:available_categories'] = 'Available Question Categories';
$string['practice:question_name'] = 'Question Name';
$string['practice:question_type'] = 'Type';
$string['practice:question_category'] = 'Category';
$string['practice:category_name'] = 'Category Name';
$string['practice:question_count'] = 'Question Count';
$string['practice:last_modified'] = 'Last Modified';
$string['practice:actions'] = 'Actions';
$string['practice:preview'] = 'Preview';
$string['practice:view_questions'] = 'View Questions';
$string['practice:create_exam'] = 'Create Exam';
$string['practice:create_from_category'] = 'Create from Category';
$string['practice:bulk_actions'] = 'Bulk Actions';
$string['practice:select_questions'] = 'Select number of questions:';
$string['practice:questions'] = 'questions';
$string['practice:create_random_exam'] = 'Create Random Practice Exam';
$string['practice:no_questions_available'] = 'No questions are available in the question bank for this course.';
$string['practice:no_categories_available'] = 'No question categories are available in the question bank for this course.';
$string['practice:go_to_question_bank'] = 'Go to Question Bank';

// Approach selection strings
$string['practice:create_session_using'] = 'I want to create my session using:';
$string['practice:gleim_suggested_approach'] = 'The Gleim suggested approach';
$string['practice:customized_exam_options'] = 'Customized exam options (choose unique questions, source, and more)';

// Step titles
$string['practice:step1_title'] = 'Select Approach and Categories';
$string['practice:step2_title'] = 'Configure Session Order';
$string['practice:step3_title'] = 'Configure Session Size';
$string['practice:step4_title'] = 'Review and Create';

// Selection header strings
$string['practice:select_study_units'] = 'Select Study Unit(s) for your Session';
$string['practice:select_study_units_desc'] = 'Choose any study units or subunits below to add the topics into your custom quiz.';
$string['practice:select_all_study_units'] = 'Select All Study Units';

// Session order strings
$string['practice:session_order'] = 'Session Order';
$string['practice:question_order_title'] = 'In what order should these questions appear?';
$string['practice:question_order_desc'] = 'Choose how you want the questions to be presented in your practice exam.';
$string['practice:answer_order_title'] = 'In what order should the answer choices appear?';
$string['practice:answer_order_desc'] = 'Choose how you want the answer choices to be presented for each question.';
$string['practice:standard_order'] = 'In the standard order';
$string['practice:random_order'] = 'In random order';

// Session size strings
$string['practice:session_size'] = 'Session Size';
$string['practice:session_size_desc'] = 'Enter the number of questions you want for your session.';
$string['practice:gleim_recommendation'] = 'It is recommended to have an optimal session size of 20 questions; however, sessions can be 1-100 questions.';
$string['practice:number_of_questions'] = 'Number of questions';
$string['practice:maximum_info'] = 'Maximum: {$a->max} out of {$a->total} matching criteria';

// Exam information strings
$string['practice:exam_information'] = 'Exam Information';
$string['practice:exam_description'] = 'You have chosen to create a CIA Practice Exam. This Practice Exam will emulate the Pearson VUE testing environment.';
$string['practice:gleim_features_title'] = 'The following options are not present in the actual vendor\'s testing environment:';
$string['practice:feature_discard'] = 'The ability to discard your Exam without grading it';
$string['practice:feature_timeout'] = 'The ability to run out of time and continue the Exam';
$string['practice:feature_review'] = 'The ability to review your Exam after grading it';
$string['practice:exam_created_successfully'] = 'Practice exam created successfully!';

// Practice exam list strings
$string['practice:my_exams'] = 'My Practice Exams';
$string['practice:no_exams_created'] = 'You haven\'t created any practice exams yet.';
$string['practice:exam_created'] = 'Created';
$string['practice:exam_questions'] = 'Questions';
$string['practice:exam_status'] = 'Status';
$string['practice:exam_status_completed'] = 'Completed';
$string['practice:exam_status_in_progress'] = 'In Progress';
$string['practice:exam_status_not_started'] = 'Not Started';
$string['practice:exam_take'] = 'Take Exam';
$string['practice:exam_view'] = 'View Exam';
$string['practice:exam_delete'] = 'Delete Exam';
$string['practice:exam_delete_confirm'] = 'Are you sure you want to delete this practice exam? This action cannot be undone.';
$string['practice:exam_deleted'] = 'Practice exam deleted successfully.';
$string['practice:create_new_exam'] = 'Create New Practice Exam';

// Take exam strings
$string['practice:question_of'] = 'Question {$a->current} of {$a->total}';
$string['practice:question_type'] = 'Type: {$a}';
$string['practice:question_navigation'] = 'Question Navigation';
$string['practice:previous_question'] = '← Previous';
$string['practice:next_question'] = 'Next →';
$string['practice:grade_exam'] = 'Grade Exam';
$string['practice:progress_text'] = '{$a->current} of {$a->total} questions';
$string['practice:no_questions_found'] = 'No questions found for this exam.';
$string['practice:question_not_found'] = 'Question not found.';

// Question type strings
$string['practice:qtype_multichoice'] = 'Multiple Choice';
$string['practice:qtype_truefalse'] = 'True/False';
$string['practice:qtype_shortanswer'] = 'Short Answer';
$string['practice:qtype_numerical'] = 'Numerical';
$string['practice:qtype_essay'] = 'Essay';
$string['practice:qtype_matching'] = 'Matching';
$string['practice:qtype_gapselect'] = 'Gap Select';
$string['practice:qtype_ddwtos'] = 'Drag and Drop';

// Resume study session strings
$string['resume:description'] = 'Continue your collaborative learning experience or start a new study session with your peers.';
$string['resume:existing_sessions'] = 'Your Previous Sessions';
$string['resume:joined'] = 'Joined';
$string['resume:left'] = 'Left';
$string['resume:continue_session'] = 'Continue Session';
$string['resume:restart_session'] = 'Restart Session';
$string['resume:new_session'] = 'Start New Study Session';
$string['resume:new_session_desc'] = 'Begin a new collaborative learning session with your classmates.';
$string['resume:start_new'] = 'Start New Session';

// Performance stats strings
$string['stats:description'] = 'Track your progress, view your performance metrics, and analyze your learning journey.';
$string['stats:participation'] = 'Participation';
$string['stats:total_sessions'] = 'Total Sessions';
$string['stats:active_sessions'] = 'Active Sessions';
$string['stats:avg_duration_min'] = 'Avg Duration (min)';
$string['stats:submissions'] = 'Submissions';
$string['stats:total_submissions'] = 'Total Submissions';
$string['stats:last_submission'] = 'Last Submission';
$string['stats:grades'] = 'Grades';
$string['stats:avg_grade'] = 'Average Grade';
$string['stats:highest_grade'] = 'Highest Grade';
$string['stats:lowest_grade'] = 'Lowest Grade';
$string['stats:no_grades'] = 'No Grades Yet';
$string['stats:detailed_stats'] = 'Detailed Statistics';
$string['stats:recent_activity'] = 'Recent Activity';
$string['stats:no_recent_activity'] = 'No recent activity found.';

// Navigation strings
$string['backtosession'] = 'Back to Session';

// Auto-creation strings
$string['default_resource_name'] = 'Revitim';

// Practice Exam JavaScript strings
$string['practice:exam_graded_successfully'] = 'Exam graded successfully';
$string['practice:exam_grading_error'] = 'Error grading exam';
$string['practice:review'] = 'Review';
$string['practice:instructions'] = 'Instructions';
$string['practice:incomplete'] = 'Incomplete';
$string['practice:complete'] = 'Complete';
$string['practice:unseen'] = 'Unseen';
$string['practice:grading'] = 'Grading...';
$string['practice:grade'] = 'Grade';
$string['practice:no_incomplete_questions'] = 'There are no incomplete questions to review.';
$string['practice:no_marked_questions'] = 'No questions have been marked for review. Please make your selections and try again.';
$string['practice:time_expired'] = 'Time for this Practice Exam has expired. We have suggested a budgeted time of 1 minute per question. Time will now count up so you can evaluate your time management.';
$string['practice:confirm_finish'] = 'Are you sure you want to finish the exam?';
$string['practice:exam_submitted'] = 'Exam submitted!';
$string['practice:confirm_save_logout'] = 'Are you sure you want to save your progress and logout? Your answers and remaining time will be saved.';
$string['practice:calculator_error'] = 'Error';

// Practice Exam Interface Strings
$string['practice:time_remaining'] = 'Time Remaining:';
$string['practice:section_review'] = 'Section Review';
$string['practice:practice_exam'] = 'Practice Exam';
$string['practice:review_instructions'] = 'Review Instructions';
$string['practice:calculator'] = 'Calculator';
$string['practice:navigator_title'] = 'Navigator - select a question to go to it';
$string['practice:marked_for_review'] = 'Marked for Review';
$string['practice:save_logout'] = 'SAVE & LOGOUT';
$string['practice:instructions'] = 'Instructions';
$string['practice:previous'] = 'Previous';
$string['practice:next'] = 'Next';
$string['practice:navigator'] = 'Navigator';
$string['practice:end_review'] = 'End Review';
$string['practice:review_all'] = 'Review All';
$string['practice:review_incomplete'] = 'Review Incomplete';
$string['practice:review_marked'] = 'Review Marked';
$string['practice:review_screen'] = 'Review Screen';
$string['practice:basic'] = 'Basic';
$string['practice:scientific'] = 'Scientific';

// Additional Interface Strings
$string['practice:marked_for_review_text'] = 'Marked for review';
$string['practice:question_number'] = 'Question #';
$string['practice:status'] = 'Status';

// Instructions Text
$string['practice:instructions_summary'] = 'Below is a summary of your answers. You can review your questions in three (3) different ways.';
$string['practice:instructions_buttons'] = 'The buttons in the lower right-hand corner correspond to these choices:';
$string['practice:instructions_review_all'] = 'Review all of your questions and answers.';
$string['practice:instructions_review_incomplete'] = 'Review questions that are incomplete.';
$string['practice:instructions_review_marked'] = 'Review questions that are marked for review. (Click the \'flag\' icon to change the mark for review status.)';
$string['practice:instructions_click_question'] = 'You may also click on a question number to link directly to its location in the exam.';

// Calculator Strings
$string['practice:calc_clear'] = 'C';
$string['practice:calc_equals'] = '=';
$string['practice:calc_sin'] = 'sin';
$string['practice:calc_cos'] = 'cos';
$string['practice:calc_tan'] = 'tan';
$string['practice:calc_pi'] = 'π';
$string['practice:calc_log'] = 'log';
$string['practice:calc_ln'] = 'ln';
$string['practice:calc_sqrt'] = '√';
$string['practice:calc_power'] = '^';
$string['practice:calc_factorial'] = 'n!';
$string['practice:calc_e'] = 'e';

// Question and Status Strings
$string['practice:question'] = 'Question';
$string['practice:current'] = 'Current';
$string['practice:unseen'] = 'Unseen';

// Stats page hardcoded strings - Date/Time
$string['date_time_label'] = 'Date/Time';

// Stats page hardcoded strings - Study Session Tab
$string['learning_progress'] = 'Learning Progress';
$string['questions_mastered'] = 'Questions Mastered:';
$string['questions_for_review'] = 'Questions for Review:';
$string['needs_practice'] = 'Needs Practice:';
$string['study_recommendations'] = 'Study Recommendations';
$string['focus_on_questions'] = 'Focus on {$a} questions that need attention.';
$string['study_problem_areas'] = 'Study Problem Areas';
$string['great_job_mastered'] = 'Great job! You\'ve mastered all questions in this session.';
$string['start_new_session'] = 'Start New Session';
$string['study_session_analysis_placeholder'] = 'This analysis is available for Study Sessions. The current session is a Practice Exam.';

// Stats page hardcoded strings - Exam Session Tab
$string['final_score'] = 'Final Score';
$string['questions_answered'] = 'Questions Answered';
$string['time_taken'] = 'Time Taken';
$string['exam_performance_breakdown'] = 'Exam Performance Breakdown';
$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';
$string['unanswered'] = 'Unanswered';
$string['avg_time_per_question'] = 'Avg Time/Question';
$string['exam_session_analysis_placeholder'] = 'This analysis is available for Practice Exams. The current session is a Study Session.';

// Stats page hardcoded strings - History Tab
$string['session_history'] = 'Session History';
$string['filters'] = 'Filters:';
$string['show_only_last_3'] = 'Show only last 3';
$string['view_only_study_sessions'] = 'View only Study Sessions';
$string['view_only_practice_exams'] = 'View only Practice Exams';
$string['session_type'] = 'Session Type';
$string['related_study_units'] = 'Related Study Unit(s)';
$string['completion_date'] = 'Completion Date';
$string['total_qs'] = 'Total Q\'s';
$string['avg_time_per_question_header'] = 'Avg. Time per Question';
$string['time_of_session'] = 'Time of Session';
$string['questions_answered_header'] = 'Questions Answered';
$string['questions_correct_header'] = 'Questions Correct';
$string['percent_correct'] = 'Percent Correct';
$string['review'] = 'Review';
$string['no_completed_sessions_found'] = 'No completed sessions found';

// Study Session Hierarchical Table
$string['create_custom_quiz'] = 'Create a Custom Quiz';
$string['table_help'] = 'Table Help';
$string['questions_answered_right'] = 'Questions Answered Right';
$string['questions_seen'] = 'Questions Seen';
$string['quiz_session_score'] = 'Quiz Session Score';
$string['close_window'] = 'Close Window';
$string['test_bank_home_screen_text'] = 'Test Bank Home Screen';
$string['view_study_session_grade_report'] = 'View Study Session Grade Report';
$string['view_exam_session_grade_report'] = 'View Exam Session Grade Report';
$string['marked_questions_exam_created'] = 'New exam/session created with marked questions. Loading...';
$string['no_marked_questions_found'] = 'No marked questions found in this session.';
$string['no_incorrect_questions_found'] = 'No incorrect questions found in this session.';
$string['incorrect_questions_exam_created'] = 'New exam/session created with incorrect questions. Loading...';
$string['no_questions_found'] = 'No questions found in this session.';
$string['all_questions_exam_created'] = 'New exam/session created with all questions. Loading...';
$string['no_correct_questions_found'] = 'No correct questions found in this session.';
$string['correct_questions_exam_created'] = 'New exam/session created with correct questions. Loading...';
$string['no_unanswered_questions_found'] = 'No unanswered questions found in this session.';
$string['unanswered_questions_exam_created'] = 'New exam/session created with unanswered questions. Loading...';
$string['no_marked_only_questions_found'] = 'No marked questions found in this session.';
$string['marked_only_questions_exam_created'] = 'New exam/session created with marked questions. Loading...';
$string['study_session_type'] = 'study session';
$string['exam_type'] = 'exam';
$string['exam_creation_error'] = 'Error creating new exam. Please try again.';
$string['unfinished_exam_exists'] = 'You already have an unfinished {$a}. Please complete it before creating a new one.';
$string['category'] = 'Category';
$string['questions_available'] = 'Questions Available';
$string['most_recent'] = 'Most Recent';
$string['last_3_attempts'] = 'Last 3 Attempts';
$string['cumulative_score'] = 'Cumulative Score';

// Detailed Quiz Breakdown Table Headers
$string['detailed_breakdown_category'] = 'Category';
$string['detailed_breakdown_questions_available'] = 'Questions Available';
$string['detailed_breakdown_most_recent'] = 'Most Recent';
$string['detailed_breakdown_last_3_attempts'] = 'Last 3 Attempts';
$string['detailed_breakdown_cumulative_score'] = 'Cumulative Score';
