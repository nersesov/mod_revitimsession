<?php
require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Get parameters
$id = required_param('id', PARAM_INT);
$examid = required_param('examid', PARAM_INT);
$review = optional_param('review', '', PARAM_TEXT);

// Get course module and course
$cm = get_coursemodule_from_id('revitimsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Require login
require_login($course, true, $cm);

// Get context
$context = context_module::instance($cm->id);

// Get study session (practice exam with studysession=1)
$study_session = $DB->get_record('revitimsession_practice_exams', array('id' => $examid), '*', MUST_EXIST);

// Verify user owns this study session
if ($study_session->userid != $USER->id) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Verify this is actually a study session
if ($study_session->studysession != 1) {
    notice(get_string('error:invalidid', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Handle delete session request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session'])) {
    // Enhanced validation for delete session
    $required_fields = ['id', 'examid', 'delete_session'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            debugging("Missing required field for session deletion: {$field}", DEBUG_DEVELOPER);
            redirect(new moodle_url('/mod/revitimsession/view.php', array('id' => $id)),
                get_string('practice:delete_error', 'revitimsession') . ' (Missing required data)',
                null,
                \core\output\notification::NOTIFY_ERROR);
        }
    }
    
    try {
        // Validate that the exam belongs to the current user
        $exam_record = $DB->get_record('revitimsession_practice_exams', 
            array('id' => $examid, 'userid' => $USER->id), '*', MUST_EXIST);
        
        // Delete all questions from practice_exam_questions table
        $DB->delete_records('revitimsession_practice_exam_questions', array('practiceexamid' => $examid));
        
        // Delete the practice exam from practice_exams table
        $DB->delete_records('revitimsession_practice_exams', array('id' => $examid));
        
        debugging("Session deleted successfully: examid={$examid}, userid={$USER->id}", DEBUG_NORMAL);
        
        // Redirect to view.php with success message
        redirect(new moodle_url('/mod/revitimsession/view.php', array('id' => $id)),
            get_string('practice:session_deleted_successfully', 'revitimsession'),
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (Exception $e) {
        debugging("Error deleting session: " . $e->getMessage(), DEBUG_DEVELOPER);
        // Redirect with error message
        redirect(new moodle_url('/mod/revitimsession/view.php', array('id' => $id)),
            get_string('practice:delete_error', 'revitimsession'),
            null,
            \core\output\notification::NOTIFY_ERROR);
    }
}

// Handle save and logout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'save_and_logout' || isset($_POST['redirect_to_view']))) {
    
    
    try {
        // Update timeremaining in practice_exams table (Test Bank save - temporary)
        $timeremaining = optional_param('timeremaining', 0, PARAM_INT);
        $DB->update_record('revitimsession_practice_exams', array(
            'id' => $examid,
            'timeremaining' => $timeremaining
        ));
        
        // Enhanced JSON validation and sanitization
        $answers_json = optional_param('answers', '{}', PARAM_TEXT);
        $answers = validate_and_decode_json($answers_json, 'answers');
        
        $status_json = optional_param('status', '{}', PARAM_TEXT);
        $status = validate_and_decode_json($status_json, 'status');
        
        $markedforreview_json = optional_param('markedforreview', '{}', PARAM_TEXT);
        $markedforreview = validate_and_decode_json($markedforreview_json, 'markedforreview');
        
        $correct_json = optional_param('correct', '{}', PARAM_TEXT);
        $correct = validate_and_decode_json($correct_json, 'correct');
        
        
        // Enhanced data processing with validation
        if (!empty($answers) && is_array($answers)) {
            foreach ($answers as $questionorder => $answerid) {
                // Additional validation for answer data
                if (is_numeric($questionorder) && is_numeric($answerid)) {
                    $DB->set_field('revitimsession_practice_exam_questions', 'answer', $answerid, 
                        array('practiceexamid' => $examid, 'questionorder' => $questionorder));
                } else {
                    debugging("Invalid answer data: questionorder={$questionorder}, answerid={$answerid}", DEBUG_DEVELOPER);
                }
            }
        }
        
        // Update status for all questions with validation
        if (!empty($status) && is_array($status)) {
            foreach ($status as $questionorder => $questionstatus) {
                if (is_numeric($questionorder) && is_numeric($questionstatus) && $questionstatus >= 0 && $questionstatus <= 2) {
                    $DB->set_field('revitimsession_practice_exam_questions', 'status', $questionstatus, 
                        array('practiceexamid' => $examid, 'questionorder' => $questionorder));
                } else {
                    debugging("Invalid status data: questionorder={$questionorder}, status={$questionstatus}", DEBUG_DEVELOPER);
                }
            }
        }
        
        // Update marked for review for all questions with validation
        if (!empty($markedforreview) && is_array($markedforreview)) {
            foreach ($markedforreview as $questionorder => $marked) {
                if (is_numeric($questionorder) && is_numeric($marked) && ($marked == 0 || $marked == 1)) {
                    $DB->set_field('revitimsession_practice_exam_questions', 'markedforreview', $marked, 
                        array('practiceexamid' => $examid, 'questionorder' => $questionorder));
                } else {
                    debugging("Invalid markedforreview data: questionorder={$questionorder}, marked={$marked}", DEBUG_DEVELOPER);
                }
            }
        }
        
        // Update correct status for all questions with validation
        if (!empty($correct) && is_array($correct)) {
            foreach ($correct as $questionorder => $correctstatus) {
                if (is_numeric($questionorder) && is_numeric($correctstatus) && $correctstatus >= 0 && $correctstatus <= 2) {
                    $DB->set_field('revitimsession_practice_exam_questions', 'correct', $correctstatus, 
                        array('practiceexamid' => $examid, 'questionorder' => $questionorder));
                } else {
                    debugging("Invalid correct data: questionorder={$questionorder}, correct={$correctstatus}", DEBUG_DEVELOPER);
                }
            }
        }
        
        // Redirect to view page with success message
        redirect(new moodle_url('/mod/revitimsession/view.php', array('id' => $id)), 
            get_string('practice:progress_saved', 'revitimsession'), 
            null, 
            \core\output\notification::NOTIFY_SUCCESS);
        
    } catch (Exception $e) {
        // Redirect to view page with error message
        redirect(new moodle_url('/mod/revitimsession/view.php', array('id' => $id)), 
            get_string('practice:save_error', 'revitimsession'), 
            null, 
            \core\output\notification::NOTIFY_ERROR);
    }
}

// Get all questions for this study session with question content
$sql = "SELECT peq.*, q.questiontext, q.questiontextformat, q.qtype, q.name, qv.questionbankentryid
        FROM {revitimsession_practice_exam_questions} peq
        JOIN {question} q ON peq.questionid = q.id
        JOIN {question_versions} qv ON q.id = qv.questionid
        WHERE peq.practiceexamid = :examid
        ORDER BY peq.questionorder ASC";
$questions = $DB->get_records_sql($sql, array('examid' => $examid));

// Calculate total time (1 minute per question)
$total_time_minutes = count($questions);
$total_time_seconds = $total_time_minutes * 60;

// Start output
$PAGE->set_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $examid));
$PAGE->set_title(get_string('perform_study', 'revitimsession'));
$PAGE->set_heading(get_string('study_session_heading', 'revitimsession'));

// Load CSS file
$PAGE->requires->css(new moodle_url('/mod/revitimsession/styles.css'));

echo $OUTPUT->header();

// Prepare template data
$template_data = array(
    'coursename' => htmlspecialchars($course->fullname),
    'username' => fullname($USER),
    'totaltimeminutes' => $total_time_minutes,
    'totaltimeseconds' => $total_time_seconds,
    'totalquestions' => count($questions),
    'examid' => $examid,
    'id' => $id,
    'questions' => array(),
    'timeremaining' => $study_session->timeremaining, // Use saved elapsed time from database
    'timeremaining_formatted' => sprintf('%02d:%02d', floor($study_session->timeremaining / 60), $study_session->timeremaining % 60), // Format as MM:SS
    'marked_data_json' => '',
    'review' => $review,
    'grade_session_title' => get_string('js:grade_session', 'revitimsession'),
    'grade_confirm_message' => get_string('js:confirmgrade', 'revitimsession'),
    'go_back_text' => get_string('js:button:goback', 'revitimsession'),
    'grade_text' => get_string('js:button:grade', 'revitimsession'),
    'exam_graded_successfully' => get_string('exam_graded_successfully', 'revitimsession'),
    'exam_grading_error' => get_string('exam_grading_error', 'revitimsession'),
    
    // JavaScript strings for dynamic content
    'js_strings' => array(
        'status_complete' => get_string('js:status:complete', 'revitimsession'),
        'status_incomplete' => get_string('js:status:incomplete', 'revitimsession'),
        'status_unseen' => get_string('js:status:unseen', 'revitimsession'),
        'feedback_correct' => get_string('js:feedback:correct', 'revitimsession'),
        'feedback_incorrect' => get_string('js:feedback:incorrect', 'revitimsession'),
        'button_instructions' => get_string('js:button:instructions', 'revitimsession'),
        'button_grading' => get_string('js:button:grading', 'revitimsession'),
        'button_resume' => get_string('js:button:resume', 'revitimsession'),
        'button_pause' => get_string('js:button:pause', 'revitimsession'),
        'search_no_matches' => get_string('js:search:no_matches', 'revitimsession'),
        'review_no_incomplete' => get_string('js:review:no_incomplete', 'revitimsession'),
        'review_no_marked' => get_string('js:review:no_marked', 'revitimsession'),
        'confirm_save_logout' => get_string('js:confirm:save_logout', 'revitimsession'),
        'feedback_no_feedback' => get_string('js:feedback:no_feedback', 'revitimsession'),
        'pause_message' => get_string('js:pause:message', 'revitimsession'),
        'pause_unpause' => get_string('js:pause:unpause', 'revitimsession'),
        'delete_title' => get_string('js:delete:title', 'revitimsession'),
        'delete_message' => get_string('js:delete:message', 'revitimsession'),
        'delete_go_back' => get_string('js:delete:go_back', 'revitimsession'),
        'delete_discard' => get_string('js:delete:discard', 'revitimsession'),
        'error_prefix' => get_string('js:error:prefix', 'revitimsession'),
        'link_test_bank' => get_string('js:link:test_bank', 'revitimsession')
    )
);

// Process questions for template
$first_question = true;
$subunit = array(); // Initialize subunit array
foreach ($questions as $question) {
    $question_num = $question->questionorder;
    
    // Get answers for this question
    $answers = $DB->get_records('question_answers', array('question' => $question->questionid), 'id ASC');
    
    // Apply answer order based on study session configuration
    if ($study_session->randomanswers == 1) {
        // Convert to array, shuffle, and convert back to maintain object structure
        $answers_array = array_values($answers);
        shuffle($answers_array);
        $answers = array();
        foreach ($answers_array as $answer) {
            $answers[$answer->id] = $answer;
        }
    }
    // For standard order (randomanswers = 0), keep the natural order from the database (id ASC)
    
    // Check if there's a saved answer for this question
    $saved_answer = $question->answer;
    $saved_status = $question->status;
    $saved_markedforreview = $question->markedforreview;
    $saved_correct = $question->correct;
    
    // Query to get parent category using question_bank_entries
    $category_sql = "SELECT qc.name as category_name, qc.parent as parent_id
                    FROM {question_bank_entries} qbe
                    JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
                    WHERE qbe.id = :questionbankentryid";
    $category_info = $DB->get_record_sql($category_sql, array('questionbankentryid' => $question->questionbankentryid));

    // Modify parent name: remove first number and dot, add colon after second number, prefix with "Subunit"
    $parent_modified = preg_replace('/^\d+\.(\d+)\s*(.+)/', '$1: $2', $category_info->category_name);
    $question_parent_name = get_string('subunit_prefix', 'revitimsession') . " " . $parent_modified;
    $question_parent_id = $category_info->parent_id;

    // Get grandparent category name using Moodle's get_record function
    $grandparent_info = $DB->get_record('question_categories', array('id' => $question_parent_id));
    $subunit_index = (int) $grandparent_info->name;
    $subunit[$subunit_index] = true;

    // Add colon after number in the string and prefix with "Study Unit"
    $modified_name = preg_replace('/(\d+)/', '$1:', $grandparent_info->name);
    $question_grandparent_name = get_string('study_unit_prefix', 'revitimsession') . " " . $modified_name;

    // Create question title with category hierarchy
    $question_title = '';
    if (!empty($question_grandparent_name) && !empty($question_parent_name)) {
        $question_title = $question_grandparent_name . ' | ' . $question_parent_name;
    } elseif (!empty($question_parent_name)) {
        $question_title = $question_parent_name;
    } else {
        $question_title = $question->name; // Fallback to original name
    }

    $question_data = array(
        'questionorder' => $question_num,
        'questionname' => $question_title,
        'questionname_sidebar' => $question->name, // Original name for sidebar
        'questiontext' => format_text($question->questiontext, $question->questiontextformat),
        'first' => $first_question,
        'saved_answer' => $saved_answer,
        'saved_status' => $saved_status,
        'saved_markedforreview' => $saved_markedforreview,
        'saved_correct' => $saved_correct,
        'question_id' => $question->id, // ID for database updates
        'answers' => array()
    );
    
    $letter = 'A';
    foreach ($answers as $answer) {
        $first_part = substr($answer->answer, 0, 3);
        $last_part = substr($answer->answer, 3);

        $answer->answer = "{$letter}. " . $last_part;
        $question_data['answers'][] = array(
            'id' => $answer->id,
            'answer' => format_text($answer->answer, $answer->answerformat),
            'feedback' => format_text($answer->feedback, $answer->feedbackformat),
            'fraction' => $answer->fraction,
            'checked' => ($saved_answer == $answer->id) ? 'checked' : ''
        );
        $letter++; // Increment letter (A -> B -> C -> D)
    }
    
    $template_data['questions'][] = $question_data;
    $first_question = false;
}

// Create ordered comma-separated string from $subunit indices
$subunit_indices = array_keys($subunit);
sort($subunit_indices);
$subunit_string = implode(', ', $subunit_indices);

// Add subunit string to template data for JavaScript
$template_data['subunit_string'] = $subunit_string;

// Generate marked data JSON for JavaScript
$marked_data = array();
$status_data = array();
foreach ($template_data['questions'] as $question) {
    $marked_data[$question['questionorder']] = $question['saved_markedforreview'] ? 1 : 0;
    $status_data[$question['questionorder']] = $question['saved_status'];
}
$template_data['marked_data_json'] = json_encode($marked_data);
$template_data['status_data_json'] = json_encode($status_data);

// Generate questions data JSON for JavaScript (including feedback and fraction)
$questions_json = array();
foreach ($template_data['questions'] as $question) {
    $questions_json[$question['questionorder']] = $question;
}
$template_data['questions_json'] = json_encode($questions_json);

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/perform_study', $template_data);

echo $OUTPUT->footer();

/**
 * Enhanced JSON validation and decoding function
 *
 * @param string $json_string The JSON string to validate and decode
 * @param string $field_name The field name for error logging
 * @return array The decoded array or empty array if invalid
 */
function validate_and_decode_json($json_string, $field_name) {
    if (empty($json_string)) {
        return array();
    }
    
    // Basic sanitization - remove potential harmful characters
    $json_string = trim($json_string);
    
    // Validate JSON structure before decoding
    if (!is_string($json_string) || strlen($json_string) > 10000) {
        debugging("Invalid JSON size or type in field {$field_name}", DEBUG_DEVELOPER);
        return array();
    }
    
    // Check for basic JSON structure
    if (!preg_match('/^\{.*\}$/', $json_string) && !preg_match('/^\[.*\]$/', $json_string)) {
        debugging("Invalid JSON structure in field {$field_name}", DEBUG_DEVELOPER);
        return array();
    }
    
    $decoded = json_decode($json_string, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        debugging("JSON decode error in field {$field_name}: " . json_last_error_msg(), DEBUG_DEVELOPER);
        return array();
    }
    
    // Additional validation - ensure it's an array
    if (!is_array($decoded)) {
        debugging("Decoded JSON is not an array in field {$field_name}", DEBUG_DEVELOPER);
        return array();
    }
    
    // Validate array contents for specific fields
    if (in_array($field_name, ['answers', 'status', 'markedforreview', 'correct'])) {
        foreach ($decoded as $key => $value) {
            // Validate key is numeric (question order)
            if (!is_numeric($key) || $key < 1) {
                debugging("Invalid question order key in {$field_name}: {$key}", DEBUG_DEVELOPER);
                unset($decoded[$key]);
                continue;
            }
            
            // Validate value based on field type
            if ($field_name === 'answers' && !is_numeric($value)) {
                debugging("Invalid answer value in {$field_name}: {$value}", DEBUG_DEVELOPER);
                unset($decoded[$key]);
            } elseif (in_array($field_name, ['status', 'markedforreview', 'correct']) && (!is_numeric($value) || $value < 0 || $value > 2)) {
                debugging("Invalid {$field_name} value: {$value}", DEBUG_DEVELOPER);
                unset($decoded[$key]);
            }
        }
    }
    
    return $decoded;
}
?>
