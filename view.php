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
 * View page for a single revitimsession instance
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... revitimsession instance ID - it should be named as the first character of the module.

if ($id) {
    $cm             = get_coursemodule_from_id('revitimsession', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $revitimsession = $DB->get_record('revitimsession', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $revitimsession = $DB->get_record('revitimsession', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $revitimsession->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('revitimsession', $revitimsession->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);



// Print the page header.
$PAGE->set_url('/mod/revitimsession/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($revitimsession->name));
$PAGE->set_heading(format_string($course->fullname));

// Set the course format to use the default course layout with blocks
$PAGE->set_pagelayout('incourse');

// Output starts here.
echo $OUTPUT->header();

// Check if user has permission to view this activity.
if (!has_capability('mod/revitimsession:view', $context)) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Display activity information.
// Removed the activity name heading to avoid showing "REVITM" above "MULTIPLE-CHOICE"

// Display activity description.
if (trim(strip_tags($revitimsession->intro))) {
    echo $OUTPUT->box_start('mod_introbox', 'revitimsessionintro');
    echo format_module_intro('revitimsession', $revitimsession, $cm->id);
    echo $OUTPUT->box_end();
}

// Get current user's participation status.
$userid = $USER->id;
$isparticipant = $DB->record_exists('revitimsession_participants', 
    array('sessionid' => $revitimsession->id, 'userid' => $userid));

// Check activity status.
$now = time();
$sessionstatus = '';

if (!empty($revitimsession->starttime) && $now < $revitimsession->starttime) {
    $sessionstatus = 'notstarted';
    $statusmessage = get_string('sessionnotstarted', 'revitimsession');
} else if (!empty($revitimsession->endtime) && $now > $revitimsession->endtime) {
    $sessionstatus = 'ended';
    $statusmessage = get_string('sessionended', 'revitimsession');
} else {
    $sessionstatus = 'inprogress';
    $statusmessage = get_string('sessioninprogress', 'revitimsession');
}

// Main menu interface
echo $OUTPUT->box_start('revitimsession-main-menu', 'revitimsession-menu');

// Menu header
echo $OUTPUT->heading('MULTIPLE-CHOICE QUESTION TEST BANK', 3, 'menu-title');

// Menu options container
echo '<div class="menu-options-container">';

// Check if user has an unfinished practice exam for this course (only practice exams, not study sessions)
$unfinished_exam = $DB->get_record('revitimsession_practice_exams', 
    array('userid' => $USER->id, 'courseid' => $course->id, 'status' => 0, 'studysession' => 0));

// Practice Exam option
echo '<div class="menu-option" id="menu-practice-exam">';
echo '<div class="menu-option-icon">';
echo $OUTPUT->pix_icon('t/edit', get_string('menu:practiceexam', 'revitimsession'));
echo '</div>';
echo '<div class="menu-option-content">';
echo '<h4 class="menu-option-title">' . get_string('menu:practiceexam', 'revitimsession') . '</h4>';
echo '<p class="menu-option-description">' . get_string('menu:practiceexam_desc', 'revitimsession') . '</p>';
echo '</div>';
echo '<div class="menu-option-action">';

if ($unfinished_exam) {
    // Show Resume Practice Exam button
    echo html_writer::link(
        new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $cm->id, 'examid' => $unfinished_exam->id)),
        get_string('menu:resumepracticeexam', 'revitimsession'),
        array('class' => 'btn btn-warning menu-btn')
    );
} else {
    // Show Create Practice Exam button
    echo html_writer::link(
        new moodle_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id)),
        get_string('menu:createpracticeexam', 'revitimsession'),
        array('class' => 'btn btn-primary menu-btn')
    );
}

echo '</div>';
echo '</div>';

// Check if user has an unfinished study session for this course (only study sessions, not practice exams)
$unfinished_study = $DB->get_record('revitimsession_practice_exams', 
    array('userid' => $USER->id, 'courseid' => $course->id, 'status' => 0, 'studysession' => 1));

// Study Session option
echo '<div class="menu-option" id="menu-study-session">';
echo '<div class="menu-option-icon">';
echo $OUTPUT->pix_icon('t/play', get_string('menu:studysession', 'revitimsession'));
echo '</div>';
echo '<div class="menu-option-content">';
echo '<h4 class="menu-option-title">' . get_string('menu:studysession', 'revitimsession') . '</h4>';
echo '<p class="menu-option-description">' . get_string('menu:studysession_desc', 'revitimsession') . '</p>';
echo '</div>';
echo '<div class="menu-option-action">';

if ($unfinished_study) {
    // Show Resume Study Session button
    echo html_writer::link(
        new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $cm->id, 'examid' => $unfinished_study->id)),
        get_string('menu:resumestudysession', 'revitimsession'),
        array('class' => 'btn btn-warning menu-btn')
    );
} else {
    // Show Create Study Session button
    echo html_writer::link(
        new moodle_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id, 'type' => 'study')),
        get_string('menu:createstudysession', 'revitimsession'),
        array('class' => 'btn btn-success menu-btn')
    );
}

echo '</div>';
echo '</div>';

// Performance Stats option
echo '<div class="menu-option" id="menu-performance-stats">';
echo '<div class="menu-option-icon">';
echo $OUTPUT->pix_icon('t/chart', get_string('menu:performancestats', 'revitimsession'));
echo '</div>';
echo '<div class="menu-option-content">';
echo '<h4 class="menu-option-title">' . get_string('menu:performancestats', 'revitimsession') . '</h4>';
echo '<p class="menu-option-description">' . get_string('menu:performancestats_desc', 'revitimsession') . '</p>';
echo '</div>';
echo '<div class="menu-option-action">';
echo html_writer::link(
    new moodle_url('/mod/revitimsession/stats.php', array('id' => $cm->id)),
    get_string('menu:view', 'revitimsession'),
    array('class' => 'btn btn-info menu-btn')
);
echo '</div>';
echo '</div>';

echo '</div>'; // menu-options-container

echo $OUTPUT->box_end();

// Display participants list (if user has permission).
if (has_capability('mod/revitimsession:view', $context)) {
    $participants = $DB->get_records_sql(
        "SELECT u.id, u.firstname, u.lastname, u.email, p.timejoined
         FROM {revitimsession_participants} p
         JOIN {user} u ON p.userid = u.id
         WHERE p.sessionid = ?
         ORDER BY p.timejoined ASC",
        array($revitimsession->id)
    );

    if (!empty($participants)) {
        echo $OUTPUT->box_start('session-participants');
        echo $OUTPUT->heading(get_string('participants', 'revitimsession'), 3);
        
        $participantstable = new html_table();
        $participantstable->head = array(
            get_string('name'),
            get_string('email'),
            get_string('joined', 'revitimsession')
        );
        $participantstable->align = array('left', 'left', 'left');

        foreach ($participants as $participant) {
            $participantstable->data[] = array(
                fullname($participant),
                $participant->email,
                userdate($participant->timejoined, get_string('strftimedatetime', 'langconfig'))
            );
        }

        echo html_writer::table($participantstable);
        echo $OUTPUT->box_end();
    }
}

// Finish the page.
echo $OUTPUT->footer();
