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
 * Create Practice Exam - Step 3: Configure Session Size
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

// Check if step 2 was completed
if (!isset($SESSION->revitimsession_practice_data) || !isset($SESSION->revitimsession_practice_data['step2_completed'])) {
    redirect(new moodle_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id, 'type' => $type)));
}

// Get stored data from session
$stored_data = $SESSION->revitimsession_practice_data;
$selected_categories = isset($stored_data['selected_categories']) ? $stored_data['selected_categories'] : array();

// Get number of questions of each category with parent category info (only selected categories)
if (!empty($selected_categories)) {
    // Get the sum of item_count directly from the database
    $sum_item_count = $DB->get_field_sql(
        "SELECT SUM(item_count) as total
         FROM (
             SELECT COUNT(*) as item_count
             FROM {question_bank_entries} qbe
             JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
             WHERE qc.id IN (" . implode(',', array_fill(0, count($selected_categories), '?')) . ")
             GROUP BY qbe.questioncategoryid
         ) as subquery",
        $selected_categories
    );
} else {
    $categorycounts = array();
    $sum_item_count = 0;
}

// Handle form submission from this step
if (optional_param('submit', 0, PARAM_INT)) {
    // Store selected data in session
    $question_count = optional_param('question_count', 20, PARAM_INT);
    
    // Update session data
    $SESSION->revitimsession_practice_data['question_count'] = $question_count;
    $SESSION->revitimsession_practice_data['step3_completed'] = true;
    
    if ($type === 'study') {
        // For study sessions, process creation directly here
        $practice_data = $SESSION->revitimsession_practice_data;
        
        // Get all questions from question_bank_entries where categoryid is in selected_categories
        $selected_categories = isset($practice_data['selected_categories']) ? $practice_data['selected_categories'] : array();
        
        if (!empty($selected_categories)) {
            // Step 1: Get all question IDs from selected categories, ordered by latest question name
            $all_question_ids = $DB->get_fieldset_sql(
                "SELECT qv.questionid 
                 FROM {question_bank_entries} qbe
                 JOIN {question_versions} qv ON qbe.id = qv.questionbankentryid
                 JOIN {question} q ON qv.questionid = q.id
                 JOIN (
                     SELECT questionbankentryid, MAX(version) AS maxversion
                     FROM {question_versions}
                     GROUP BY questionbankentryid
                 ) latest ON latest.questionbankentryid = qv.questionbankentryid 
                          AND latest.maxversion = qv.version
                 WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($selected_categories), '?')) . ")
                 ORDER BY q.name ASC",
                 $selected_categories
            );
            
            // Step 2: Use PHP to randomly select the required number of questions
            if (count($all_question_ids) > $question_count) {
                $question_ids = array_values(array_rand(array_flip($all_question_ids), $question_count));
            } else {
                $question_ids = $all_question_ids;
            }
            
            // Note: question_ids already contain the latest question IDs from the query above
            
            // Step 3: Apply question order based on user selection
            $question_order = isset($practice_data['question_order']) ? $practice_data['question_order'] : 'standard';
            if ($question_order === 'random') {
                // Questions are already randomized by array_rand above
                shuffle($question_ids);
            }
            // For 'standard' order, keep the natural order from the database
            
        } else {
            $question_ids = array();
        }
        
        // Insert practice exam record
        $practice_exam_data = new stdClass();
        $practice_exam_data->userid = $USER->id;
        $practice_exam_data->courseid = $course->id;
        $practice_exam_data->status = 0; // 0 = not finished, 1 = finished
        $practice_exam_data->timeremaining = 0; // Study sessions start with 0 elapsed time (countup)
        $practice_exam_data->totalquestions = $question_count; // Store total number of questions
        $practice_exam_data->randomanswers = isset($practice_data['answer_order']) && $practice_data['answer_order'] === 'random' ? 1 : 0;
        $practice_exam_data->studysession = 1; // This is a study session
        $practice_exam_data->timecreated = time();
        $practice_exam_data->timemodified = time();
        
        $practice_exam_id = $DB->insert_record('revitimsession_practice_exams', $practice_exam_data);
        
        // Insert questions into revitimsession_practice_exam_questions table
        foreach ($question_ids as $index => $question_id) {
            $question_data = new stdClass();
            $question_data->practiceexamid = $practice_exam_id;
            $question_data->questionid = $question_id;
            $question_data->questionorder = $index + 1; // Order starts from 1
            $question_data->timecreated = time();
            
            $DB->insert_record('revitimsession_practice_exam_questions', $question_data);
        }
        
        // Clear the session data
        unset($SESSION->revitimsession_practice_data);
        
        // Redirect to perform_study.php for study sessions
        redirect(new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $cm->id, 'examid' => $practice_exam_id)), 
            get_string('practice:exam_created_successfully', 'revitimsession'), 
            null, 
            \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // For practice exams, redirect to step4
        redirect(new moodle_url('/mod/revitimsession/create_practice_step4.php', array('id' => $cm->id, 'type' => $type)));
    }
}

// Get stored data from session
$stored_data = $SESSION->revitimsession_practice_data;
$question_count = isset($stored_data['question_count']) ? $stored_data['question_count'] : $sum_item_count;

// Calculate total questions from selected categories (for display purposes)
$totalquestions = $sum_item_count;

// Print the page header.
$PAGE->set_url('/mod/revitimsession/create_practice_step3.php', array('id' => $cm->id, 'type' => $type));
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
    'question_count' => $question_count,
    'sum_item_count' => $sum_item_count,
    'totalquestions' => $totalquestions,
    'practice_session_size_desc' => get_string('practice:session_size_desc', 'revitimsession'),
    'practice_gleim_recommendation' => get_string('practice:gleim_recommendation', 'revitimsession'),
    'practice_number_of_questions' => get_string('practice:number_of_questions', 'revitimsession'),
    'practice_maximum_info' => get_string('practice:maximum_info', 'revitimsession', array('max' => $sum_item_count, 'total' => $totalquestions)),
    'previous_url' => new moodle_url('/mod/revitimsession/create_practice_step2.php', array('id' => $cm->id, 'type' => $type)),
    'previous_text' => get_string('button:previous', 'revitimsession'),
    'cancel_url' => new moodle_url('/mod/revitimsession/view.php', array('id' => $cm->id)),
    'cancel_text' => get_string('button:cancel', 'revitimsession'),
    'next_text' => get_string('button:next', 'revitimsession'),
    'finish_text' => get_string('button:finish', 'revitimsession'),
    'is_study_session' => ($type === 'study')
);

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/create_practice_step3', $template_data);

// Finish the page.
echo $OUTPUT->footer();
