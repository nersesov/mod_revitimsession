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
 * Create Practice Exam - Step 1: Select Approach and Categories
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course_module ID
$type = optional_param('type', 'practice', PARAM_TEXT); // Type: 'practice' or 'study'

$cm = get_coursemodule_from_id('revitimsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$revitimsession = $DB->get_record('revitimsession', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Check if user has permission to access practice exam
if (!has_capability('mod/revitimsession:view', $context)) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Get questions from question bank - traverse the category tree
$coursecontext = context_course::instance($course->id);

// First, get all category IDs in the course context (including subcategories)
$categoryids = $DB->get_fieldset_sql(
    "SELECT id FROM {question_categories} 
     WHERE contextid = ?",
    array($coursecontext->id)
);

if (!empty($categoryids)) {
    // Get number of questions of each category with parent category info
    $categorycounts = $DB->get_records_sql(
        "SELECT qbe.questioncategoryid, qc.name as categoryname, 
                parent.name as parentname, COUNT(*) as item_count
         FROM {question_bank_entries} qbe
         JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
         LEFT JOIN {question_categories} parent ON qc.parent = parent.id
         WHERE qc.id IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
         GROUP BY qbe.questioncategoryid, qc.name, parent.name
         HAVING COUNT(*) > 0
         ORDER BY COALESCE(parent.name, ''), qc.name",
        $categoryids
    );
} else {
    $categorycounts = array();
}

// Calculate sum of item_count
$sum_item_count = 0;
if (!empty($categorycounts)) {
    foreach ($categorycounts as $category) {
        $sum_item_count += $category->item_count;
    }
}

// Handle form submission from this step
if (optional_param('submit', 0, PARAM_INT)) {
    // Store selected data in session
    $selected_categories = optional_param_array('selected_categories', array(), PARAM_INT);
    $session_approach = optional_param('session_approach', 'gleim', PARAM_TEXT);
    $select_all = optional_param('select_all', 0, PARAM_INT);
    
    // Store in session
    $session_data = array(
        'selected_categories' => $selected_categories,
        'session_approach' => $session_approach,
        'select_all' => $select_all,
        'sum_item_count' => $sum_item_count,
        'step1_completed' => true,
        'type' => $type
    );
    
    // Use Moodle's session to store data
    $SESSION->revitimsession_practice_data = $session_data;
    
    // Redirect to step 2
    redirect(new moodle_url('/mod/revitimsession/create_practice_step2.php', array('id' => $cm->id, 'type' => $type)));
}

// Get stored data from session
$stored_data = isset($SESSION->revitimsession_practice_data) ? $SESSION->revitimsession_practice_data : array();
$selected_categories = isset($stored_data['selected_categories']) ? $stored_data['selected_categories'] : array();
$session_approach = isset($stored_data['session_approach']) ? $stored_data['session_approach'] : 'gleim';
$select_all = isset($stored_data['select_all']) ? $stored_data['select_all'] : 0;

// Print the page header
$PAGE->set_url('/mod/revitimsession/create_practice_step1.php', array('id' => $cm->id, 'type' => $type));
$PAGE->set_title(format_string($revitimsession->name));
$page_heading = ($type === 'study') ? 'Create Study Session' : 'Create Practice Exam';
$PAGE->set_heading($page_heading);

// Load CSS file
$PAGE->requires->css(new moodle_url('/mod/revitimsession/styles.css'));

// Output starts here
echo $OUTPUT->header();

// Prepare template data
$template_data = array(
    'id' => $cm->id,
    'type' => $type,
    'session_approach_gleim' => ($session_approach == 'gleim'),
    'session_approach_customized' => ($session_approach == 'customized'),
    'select_all' => $select_all,
    'has_categories' => !empty($categorycounts),
    'practice_create_session_using' => get_string('practice:create_session_using', 'revitimsession'),
    'practice_gleim_suggested_approach' => get_string('practice:gleim_suggested_approach', 'revitimsession'),
    'practice_customized_exam_options' => get_string('practice:customized_exam_options', 'revitimsession'),
    'practice_select_study_units' => get_string('practice:select_study_units', 'revitimsession'),
    'practice_select_study_units_desc' => get_string('practice:select_study_units_desc', 'revitimsession'),
    'practice_select_all_study_units' => get_string('practice:select_all_study_units', 'revitimsession'),
    'cancel_url' => new moodle_url('/mod/revitimsession/view.php', array('id' => $cm->id)),
    'cancel_text' => get_string('button:cancel', 'revitimsession'),
    'next_text' => get_string('button:next', 'revitimsession'),
    'organized_categories' => array()
);

if (!empty($categorycounts)) {
    // Organize categories by parent and calculate totals
    $organized_categories = array();
    $parent_totals = array();
    
    foreach ($categorycounts as $category) {
        $parentname = !empty($category->parentname) ? $category->parentname : 'root';
        if (!isset($organized_categories[$parentname])) {
            $organized_categories[$parentname] = array();
            $parent_totals[$parentname] = 0;
        }
        $organized_categories[$parentname][] = $category;
        $parent_totals[$parentname] += $category->item_count;
    }
    
    foreach ($organized_categories as $parentname => $categories) {
        if ($parentname === 'root') {
            // Top-level categories
            $template_data['organized_categories'][] = array(
                'is_root' => true,
                'categories' => array()
            );
            
            foreach ($categories as $category) {
                $template_data['organized_categories'][count($template_data['organized_categories']) - 1]['categories'][] = array(
                    'questioncategoryid' => $category->questioncategoryid,
                    'categoryname' => htmlspecialchars($category->categoryname),
                    'item_count' => $category->item_count,
                    'is_checked' => in_array($category->questioncategoryid, $selected_categories)
                );
            }
        } else {
            // Parent category with subcategories
            $parent_checked = false;
            foreach ($categories as $category) {
                if (in_array($category->questioncategoryid, $selected_categories)) {
                    $parent_checked = true;
                    break;
                }
            }
            
            $template_data['organized_categories'][] = array(
                'is_root' => false,
                'parentname' => htmlspecialchars($parentname),
                'parent_id' => md5($parentname),
                'parent_total' => $parent_totals[$parentname],
                'parent_checked' => $parent_checked,
                'categories' => array()
            );
            
            foreach ($categories as $category) {
                $template_data['organized_categories'][count($template_data['organized_categories']) - 1]['categories'][] = array(
                    'questioncategoryid' => $category->questioncategoryid,
                    'categoryname' => htmlspecialchars($category->categoryname),
                    'item_count' => $category->item_count,
                    'is_checked' => in_array($category->questioncategoryid, $selected_categories)
                );
            }
        }
    }
} else {
    // No categories available
    $template_data['no_categories_message'] = get_string('practice:no_categories_available', 'revitimsession');
    $template_data['has_question_permission'] = has_capability('moodle/question:add', $coursecontext);
    $template_data['go_to_question_bank_url'] = new moodle_url('/question/edit.php', array('courseid' => $course->id));
    $template_data['go_to_question_bank_text'] = get_string('practice:go_to_question_bank', 'revitimsession');
}

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/create_practice_step1', $template_data);

// Finish the page
echo $OUTPUT->footer();
