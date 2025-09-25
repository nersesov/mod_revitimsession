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
 * Create Practice Exam - Step 2: Configure Session Order
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course_module ID
$type = optional_param('type', 'practice', PARAM_TEXT); // Type: 'practice' or 'study'

$cm             = get_coursemodule_from_id('revitimsession', $id, 0, false, MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$revitimsession = $DB->get_record('revitimsession', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check if user has permission to view this activity.
if (!has_capability('mod/revitimsession:view', $context)) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Check if step 1 was completed
if (!isset($SESSION->revitimsession_practice_data) || !isset($SESSION->revitimsession_practice_data['step1_completed'])) {
    redirect(new moodle_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id, 'type' => $type)));
}

// Handle form submission from this step
if (optional_param('submit', 0, PARAM_INT)) {
    // Store selected data in session
    $question_order = optional_param('question_order', 'standard', PARAM_TEXT);
    $answer_order = optional_param('answer_order', 'standard', PARAM_TEXT);
    
    // Update session data
    $SESSION->revitimsession_practice_data['question_order'] = $question_order;
    $SESSION->revitimsession_practice_data['answer_order'] = $answer_order;
    $SESSION->revitimsession_practice_data['step2_completed'] = true;
    
    // Redirect to step 3
    redirect(new moodle_url('/mod/revitimsession/create_practice_step3.php', array('id' => $cm->id, 'type' => $type)));
}

// Get stored data from session
$stored_data = $SESSION->revitimsession_practice_data;
$question_order = isset($stored_data['question_order']) ? $stored_data['question_order'] : 'standard';
$answer_order = isset($stored_data['answer_order']) ? $stored_data['answer_order'] : 'standard';

// Print the page header.
$PAGE->set_url('/mod/revitimsession/create_practice_step2.php', array('id' => $cm->id, 'type' => $type));
$PAGE->set_title(format_string($revitimsession->name));
$page_heading = ($type === 'study') ? 'Create Study Session' : 'Create Practice Exam';
$PAGE->set_heading($page_heading);

// Load CSS file
$PAGE->requires->css(new moodle_url('/mod/revitimsession/styles.css'));

// Output starts here.
echo $OUTPUT->header();

// Prepare template data
$template_data = array(
    'id' => $cm->id,
    'type' => $type,
    'question_order_standard' => ($question_order == 'standard'),
    'question_order_random' => ($question_order == 'random'),
    'answer_order_standard' => ($answer_order == 'standard'),
    'answer_order_random' => ($answer_order == 'random'),
    'practice_question_order_title' => get_string('practice:question_order_title', 'revitimsession'),
    'practice_question_order_desc' => get_string('practice:question_order_desc', 'revitimsession'),
    'practice_answer_order_title' => get_string('practice:answer_order_title', 'revitimsession'),
    'practice_answer_order_desc' => get_string('practice:answer_order_desc', 'revitimsession'),
    'practice_standard_order' => get_string('practice:standard_order', 'revitimsession'),
    'practice_random_order' => get_string('practice:random_order', 'revitimsession'),
    'previous_url' => new moodle_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id, 'type' => $type)),
    'previous_text' => get_string('button:previous', 'revitimsession'),
    'cancel_url' => new moodle_url('/mod/revitimsession/view.php', array('id' => $cm->id)),
    'cancel_text' => get_string('button:cancel', 'revitimsession'),
    'next_text' => get_string('button:next', 'revitimsession')
);

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/create_practice_step2', $template_data);

// Finish the page.
echo $OUTPUT->footer();
