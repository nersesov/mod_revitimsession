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

// Get practice exam
$practice_exam = $DB->get_record('revitimsession_practice_exams', array('id' => $examid), '*', MUST_EXIST);

// Verify user owns this exam
if ($practice_exam->userid != $USER->id) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Handle save and logout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_and_logout') {
    
    try {
        // Update timeremaining in practice_exams table
        $timeremaining = optional_param('timeremaining', 0, PARAM_INT);
        $DB->update_record('revitimsession_practice_exams', array(
            'id' => $examid,
            'timeremaining' => $timeremaining
        ));
        
        // Update answers, status and marked for review in practice_exam_questions table
        $answers_json = optional_param('answers', '{}', PARAM_TEXT);
        $answers = json_decode($answers_json, true);
        
        $status_json = optional_param('status', '{}', PARAM_TEXT);
        $status = json_decode($status_json, true);
        
        $markedforreview_json = optional_param('markedforreview', '{}', PARAM_TEXT);
        $markedforreview = json_decode($markedforreview_json, true);
        
        $correct_json = optional_param('correct', '{}', PARAM_TEXT);
        $correct = json_decode($correct_json, true);
        
        foreach ($answers as $questionorder => $answerid) {
            $DB->set_field('revitimsession_practice_exam_questions', 'answer', $answerid, 
                array('practiceexamid' => $examid, 'questionorder' => $questionorder));
        }
        
        // Update status for all questions
        foreach ($status as $questionorder => $questionstatus) {
            $DB->set_field('revitimsession_practice_exam_questions', 'status', $questionstatus, 
                array('practiceexamid' => $examid, 'questionorder' => $questionorder));
        }
        
        // Update marked for review for all questions
        foreach ($markedforreview as $questionorder => $marked) {
            $DB->set_field('revitimsession_practice_exam_questions', 'markedforreview', $marked, 
                array('practiceexamid' => $examid, 'questionorder' => $questionorder));
        }
        
        // Update correct status for all questions
        foreach ($correct as $questionorder => $correctvalue) {
            $DB->set_field('revitimsession_practice_exam_questions', 'correct', $correctvalue, 
                array('practiceexamid' => $examid, 'questionorder' => $questionorder));
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

// Get all questions for this exam with question content
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
$PAGE->set_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $examid));
$PAGE->set_title(get_string('perform_exam', 'revitimsession'));
$PAGE->set_heading($course->fullname);

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
    'timeremaining' => $practice_exam->timeremaining,
    'review' => $review,
    'grade_session_title' => get_string('js:grade_session', 'revitimsession'),
    'grade_confirm_message' => get_string('js:confirmgrade', 'revitimsession'),
    'go_back_text' => get_string('js:button:goback', 'revitimsession'),
    'grade_text' => get_string('js:button:grade', 'revitimsession'),
    'exam_graded_successfully' => get_string('exam_graded_successfully', 'revitimsession'),
    'exam_grading_error' => get_string('exam_grading_error', 'revitimsession')
);

// Process questions for template
$first_question = true;
$subunit = array(); // Initialize subunit array
foreach ($questions as $question) {
    $question_num = $question->questionorder;
    
    // Get answers for this question
    $answers = $DB->get_records('question_answers', array('question' => $question->questionid), 'id ASC');
    
    // Apply answer order based on exam configuration
    if ($practice_exam->randomanswers == 1) {
        // Convert to array, shuffle, and convert back to maintain object structure
        $answers_array = array_values($answers);
        shuffle($answers_array);
        $answers = array();
        foreach ($answers_array as $answer) {
            $answers[$answer->id] = $answer;
        }
    }
    // For standard order (randomanswers = 0), keep the natural order from the database (id ASC)
    
    // Query to get parent category using question_bank_entries
    $category_sql = "SELECT qc.name as category_name, qc.parent as parent_id
                    FROM {question_bank_entries} qbe
                    JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
                    WHERE qbe.id = :questionbankentryid";
    $category_info = $DB->get_record_sql($category_sql, array('questionbankentryid' => $question->questionbankentryid));

    // Get grandparent category name using Moodle's get_record function
    $grandparent_info = $DB->get_record('question_categories', array('id' => $category_info->parent_id));
    $subunit_index = (int) $grandparent_info->name;
    $subunit[$subunit_index] = true;

    // Check if there's a saved answer for this question
    $saved_answer = $question->answer;
    $saved_status = $question->status;
    $saved_markedforreview = $question->markedforreview;
    $saved_correct = $question->correct;
    
    $question_data = array(
        'questionorder' => $question_num,
        'questionname' => $question->name,
        'questiontext' => format_text($question->questiontext, $question->questiontextformat),
        'first' => $first_question,
        'saved_answer' => $saved_answer,
        'saved_status' => $saved_status,
        'saved_markedforreview' => $saved_markedforreview,
        'saved_correct' => $saved_correct,
        'answers' => array()
    );
    
    foreach ($answers as $answer) {
        $question_data['answers'][] = array(
            'id' => $answer->id,
            'answer' => format_text($answer->answer, $answer->answerformat),
            'fraction' => $answer->fraction,
            'checked' => ($saved_answer == $answer->id) ? 'checked' : ''
        );
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
$correct_data = array();
foreach ($template_data['questions'] as $question) {
    $marked_data[$question['questionorder']] = $question['saved_markedforreview'] ? 1 : 0;
    $status_data[$question['questionorder']] = $question['saved_status'];
    $correct_data[$question['questionorder']] = $question['saved_correct'];
}
$template_data['marked_data_json'] = json_encode($marked_data);
$template_data['status_data_json'] = json_encode($status_data);
$template_data['correct_data_json'] = json_encode($correct_data);


// Generate questions data JSON for JavaScript (including feedback and fraction)
$questions_json = array();
foreach ($template_data['questions'] as $question) {
    $questions_json[$question['questionorder']] = $question;
}
$template_data['questions_json'] = json_encode($questions_json);

// Add language strings for JavaScript
$lang_strings_array = array(
    'exam_graded_successfully' => get_string('practice:exam_graded_successfully', 'revitimsession'),
    'exam_grading_error' => get_string('practice:exam_grading_error', 'revitimsession'),
    'review' => get_string('practice:review', 'revitimsession'),
    'instructions' => get_string('practice:instructions', 'revitimsession'),
    'incomplete' => get_string('practice:incomplete', 'revitimsession'),
    'complete' => get_string('practice:complete', 'revitimsession'),
    'unseen' => get_string('practice:unseen', 'revitimsession'),
    'grading' => get_string('practice:grading', 'revitimsession'),
    'grade' => get_string('practice:grade', 'revitimsession'),
    'no_incomplete_questions' => get_string('practice:no_incomplete_questions', 'revitimsession'),
    'no_marked_questions' => get_string('practice:no_marked_questions', 'revitimsession'),
    'time_expired' => get_string('practice:time_expired', 'revitimsession'),
    'confirm_finish' => get_string('practice:confirm_finish', 'revitimsession'),
    'exam_submitted' => get_string('practice:exam_submitted', 'revitimsession'),
    'confirm_save_logout' => get_string('practice:confirm_save_logout', 'revitimsession'),
    'calculator_error' => get_string('practice:calculator_error', 'revitimsession'),
    'time_remaining' => get_string('practice:time_remaining', 'revitimsession'),
    'section_review' => get_string('practice:section_review', 'revitimsession'),
    'practice_exam' => get_string('practice:practice_exam', 'revitimsession'),
    'review_instructions' => get_string('practice:review_instructions', 'revitimsession'),
    'calculator' => get_string('practice:calculator', 'revitimsession'),
    'navigator_title' => get_string('practice:navigator_title', 'revitimsession'),
    'marked_for_review' => get_string('practice:marked_for_review', 'revitimsession'),
    'save_logout' => get_string('practice:save_logout', 'revitimsession'),
    'previous' => get_string('practice:previous', 'revitimsession'),
    'next' => get_string('practice:next', 'revitimsession'),
    'navigator' => get_string('practice:navigator', 'revitimsession'),
    'end_review' => get_string('practice:end_review', 'revitimsession'),
    'review_all' => get_string('practice:review_all', 'revitimsession'),
    'review_incomplete' => get_string('practice:review_incomplete', 'revitimsession'),
    'review_marked' => get_string('practice:review_marked', 'revitimsession'),
    'review_screen' => get_string('practice:review_screen', 'revitimsession'),
    'basic' => get_string('practice:basic', 'revitimsession'),
    'scientific' => get_string('practice:scientific', 'revitimsession'),
    'marked_for_review_text' => get_string('practice:marked_for_review_text', 'revitimsession'),
    'question_number' => get_string('practice:question_number', 'revitimsession'),
    'status' => get_string('practice:status', 'revitimsession'),
    'instructions_summary' => get_string('practice:instructions_summary', 'revitimsession'),
    'instructions_buttons' => get_string('practice:instructions_buttons', 'revitimsession'),
    'instructions_review_all' => get_string('practice:instructions_review_all', 'revitimsession'),
    'instructions_review_incomplete' => get_string('practice:instructions_review_incomplete', 'revitimsession'),
    'instructions_review_marked' => get_string('practice:instructions_review_marked', 'revitimsession'),
    'instructions_click_question' => get_string('practice:instructions_click_question', 'revitimsession'),
    'calc_clear' => get_string('practice:calc_clear', 'revitimsession'),
    'calc_equals' => get_string('practice:calc_equals', 'revitimsession'),
    'calc_sin' => get_string('practice:calc_sin', 'revitimsession'),
    'calc_cos' => get_string('practice:calc_cos', 'revitimsession'),
    'calc_tan' => get_string('practice:calc_tan', 'revitimsession'),
    'calc_pi' => get_string('practice:calc_pi', 'revitimsession'),
    'calc_log' => get_string('practice:calc_log', 'revitimsession'),
    'calc_ln' => get_string('practice:calc_ln', 'revitimsession'),
    'calc_sqrt' => get_string('practice:calc_sqrt', 'revitimsession'),
    'calc_power' => get_string('practice:calc_power', 'revitimsession'),
    'calc_factorial' => get_string('practice:calc_factorial', 'revitimsession'),
    'calc_e' => get_string('practice:calc_e', 'revitimsession')
);
$template_data['lang_strings'] = json_encode($lang_strings_array);

// Add language strings directly to template data
$template_data['time_remaining'] = get_string('practice:time_remaining', 'revitimsession');
$template_data['section_review'] = get_string('practice:section_review', 'revitimsession');
$template_data['practice_exam'] = get_string('practice:practice_exam', 'revitimsession');
$template_data['review_instructions'] = get_string('practice:review_instructions', 'revitimsession');
$template_data['calculator'] = get_string('practice:calculator', 'revitimsession');
$template_data['navigator_title'] = get_string('practice:navigator_title', 'revitimsession');
$template_data['marked_for_review'] = get_string('practice:marked_for_review', 'revitimsession');
$template_data['save_logout'] = get_string('practice:save_logout', 'revitimsession');
$template_data['instructions'] = get_string('practice:instructions', 'revitimsession');
$template_data['previous'] = get_string('practice:previous', 'revitimsession');
$template_data['next'] = get_string('practice:next', 'revitimsession');
$template_data['navigator'] = get_string('practice:navigator', 'revitimsession');
$template_data['end_review'] = get_string('practice:end_review', 'revitimsession');
$template_data['review_all'] = get_string('practice:review_all', 'revitimsession');
$template_data['review_incomplete'] = get_string('practice:review_incomplete', 'revitimsession');
$template_data['review_marked'] = get_string('practice:review_marked', 'revitimsession');
$template_data['review_screen'] = get_string('practice:review_screen', 'revitimsession');
$template_data['basic'] = get_string('practice:basic', 'revitimsession');
$template_data['scientific'] = get_string('practice:scientific', 'revitimsession');
$template_data['marked_for_review_text'] = get_string('practice:marked_for_review_text', 'revitimsession');
$template_data['question_number'] = get_string('practice:question_number', 'revitimsession');
$template_data['status'] = get_string('practice:status', 'revitimsession');
$template_data['instructions_summary'] = get_string('practice:instructions_summary', 'revitimsession');
$template_data['instructions_buttons'] = get_string('practice:instructions_buttons', 'revitimsession');
$template_data['instructions_review_all'] = get_string('practice:instructions_review_all', 'revitimsession');
$template_data['instructions_review_incomplete'] = get_string('practice:instructions_review_incomplete', 'revitimsession');
$template_data['instructions_review_marked'] = get_string('practice:instructions_review_marked', 'revitimsession');
$template_data['instructions_click_question'] = get_string('practice:instructions_click_question', 'revitimsession');
$template_data['calc_clear'] = get_string('practice:calc_clear', 'revitimsession');
$template_data['calc_equals'] = get_string('practice:calc_equals', 'revitimsession');
$template_data['calc_sin'] = get_string('practice:calc_sin', 'revitimsession');
$template_data['calc_cos'] = get_string('practice:calc_cos', 'revitimsession');
$template_data['calc_tan'] = get_string('practice:calc_tan', 'revitimsession');
$template_data['calc_pi'] = get_string('practice:calc_pi', 'revitimsession');
$template_data['calc_log'] = get_string('practice:calc_log', 'revitimsession');
$template_data['calc_ln'] = get_string('practice:calc_ln', 'revitimsession');
$template_data['calc_sqrt'] = get_string('practice:calc_sqrt', 'revitimsession');
$template_data['calc_power'] = get_string('practice:calc_power', 'revitimsession');
$template_data['calc_factorial'] = get_string('practice:calc_factorial', 'revitimsession');
$template_data['calc_e'] = get_string('practice:calc_e', 'revitimsession');
$template_data['question'] = get_string('practice:question', 'revitimsession');
$template_data['current'] = get_string('practice:current', 'revitimsession');
$template_data['unseen'] = get_string('practice:unseen', 'revitimsession');

// JavaScript will be loaded in template

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/perform_exam', $template_data);

echo $OUTPUT->footer();
?>
