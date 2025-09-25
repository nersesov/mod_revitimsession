<?php
require_once('../../config.php');

// Get parameters
$id = required_param('id', PARAM_INT);
$examid = required_param('examid', PARAM_INT);
$timeremaining = required_param('timeremaining', PARAM_INT);
$answers = required_param('answers', PARAM_TEXT);
$studyunit = optional_param('studyunit', '', PARAM_TEXT);
$status = optional_param('status', '{}', PARAM_TEXT);
$markedforreview = optional_param('markedforreview', '{}', PARAM_TEXT);
$correct = optional_param('correct', '{}', PARAM_TEXT);

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
    throw new moodle_exception('nopermission', 'revitimsession');
}

try {
    // Start transaction
    $transaction = $DB->start_delegated_transaction();
    
    // Parse JSON data
    $answers_array = json_decode($answers, true);
    if ($answers_array === null) {
        throw new moodle_exception('invalidjson', 'revitimsession');
    }
    
    $status_array = json_decode($status, true);
    $markedforreview_array = json_decode($markedforreview, true);
    $correct_array = json_decode($correct, true);
    
    // Get all questions for this exam
    $questions = $DB->get_records('revitimsession_practice_exam_questions', 
        array('practiceexamid' => $examid), 'questionorder ASC');
    
    // Process each question to save all data
    foreach ($questions as $question) {
        $questionorder = $question->questionorder;
        $user_answer = isset($answers_array[$questionorder]) ? $answers_array[$questionorder] : null;
        
        // Get values from JavaScript arrays (use existing values if not provided)
        $question_status = isset($status_array[$questionorder]) ? $status_array[$questionorder] : $question->status;
        $question_marked = isset($markedforreview_array[$questionorder]) ? $markedforreview_array[$questionorder] : $question->markedforreview;
        $question_correct = isset($correct_array[$questionorder]) ? $correct_array[$questionorder] : null;
        
        // JavaScript should provide the correct value with proper first-time logic
        // If not provided, use existing database value as fallback
        if ($question_correct === null) {
            $question_correct = $question->correct;
        }
        
        // Update all fields for this question
        $DB->update_record('revitimsession_practice_exam_questions', array(
            'id' => $question->id,
            'answer' => $user_answer,
            'status' => $question_status,
            'markedforreview' => $question_marked,
            'correct' => $question_correct
        ));
    }
    
    // Update practice exam status to finished (1), timeremaining, timefinished, and studyunit
    $DB->update_record('revitimsession_practice_exams', array(
        'id' => $examid,
        'status' => 1, // Finished
        'timeremaining' => $timeremaining,
        'timefinished' => time(), // Current timestamp when exam is finished
        'studyunit' => $studyunit // Study units covered in this session
    ));
    
    // Commit transaction
    $transaction->allow_commit();
    
    // Return success response
    $response = array(
        'success' => true,
        'message' => 'OK'
    );
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($transaction)) {
        $transaction->rollback($e);
    }
    
    // Return error response
    $response = array(
        'success' => false,
        'message' => get_string('exam_grading_error', 'revitimsession')
    );
}

// Set JSON header and output response
header('Content-Type: application/json');
echo json_encode($response);
?>
