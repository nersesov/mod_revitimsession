<?php
require_once('../../config.php');

// Get parameters
$id = required_param('id', PARAM_INT);
$examid = optional_param('examid', 0, PARAM_INT);

// Get course module and course
$cm = get_coursemodule_from_id('revitimsession', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// Require login
require_login($course, true, $cm);

// Get context
$context = context_module::instance($cm->id);

// If no examid provided, get the latest finished practice exam for this user in this course
if (!$examid) {
    $sql = "SELECT * FROM {revitimsession_practice_exams} 
            WHERE userid = :userid AND courseid = :courseid AND status = 1
            ORDER BY timefinished DESC 
            LIMIT 1";
    $latest_exam = $DB->get_record_sql($sql, array('userid' => $USER->id, 'courseid' => $course->id));
    
    if ($latest_exam) {
        $examid = $latest_exam->id;
        $practice_exam = $latest_exam;
    } else {
        // No practice exam found, redirect to view page with error message
        notice(get_string('no_practice_exam_found', 'revitimsession'), 
            new moodle_url('/mod/revitimsession/view.php', array('id' => $id)));
    }
} else {
    // Get practice exam by provided examid
    $practice_exam = $DB->get_record('revitimsession_practice_exams', array('id' => $examid), '*', MUST_EXIST);
}

// Verify user owns this exam
if ($practice_exam->userid != $USER->id) {
    notice(get_string('nopermission', 'revitimsession'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Handle creation of new exam with marked questions
$create_marked = optional_param('create_marked', 0, PARAM_INT);
if ($create_marked) {
    createMarkedQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Handle creation of new exam with incorrect questions
$create_incorrect = optional_param('create_incorrect', 0, PARAM_INT);
if ($create_incorrect) {
    createIncorrectQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Handle creation of new exam with all questions
$create_all = optional_param('create_all', 0, PARAM_INT);
if ($create_all) {
    createAllQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Handle creation of new exam with correct questions
$create_correct = optional_param('create_correct', 0, PARAM_INT);
if ($create_correct) {
    createCorrectQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Handle creation of new exam with unanswered questions
$create_unanswered = optional_param('create_unanswered', 0, PARAM_INT);
if ($create_unanswered) {
    createUnansweredQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Handle creation of new exam with marked questions only
$create_marked_only = optional_param('create_marked_only', 0, PARAM_INT);
if ($create_marked_only) {
    createMarkedOnlyQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id);
}

// Get all questions for this exam with their results
$sql = "SELECT peq.*, q.questiontext, q.questiontextformat, q.qtype
        FROM {revitimsession_practice_exam_questions} peq
        JOIN {question} q ON peq.questionid = q.id
        WHERE peq.practiceexamid = :examid
        ORDER BY peq.questionorder ASC";
$questions = $DB->get_records_sql($sql, array('examid' => $examid));

// Get total questions from the practice exam record (more efficient)
$total_questions = $practice_exam->totalquestions;
$answered_questions = 0;
$correct_answers = 0;
$incorrect_answers = 0;
$marked_questions = 0;
$unanswered_questions = 0;

foreach ($questions as $question) {
    if ($question->answer !== null) {
        $answered_questions++;
        if ($question->correct == 1 || $question->correct == 2) {
            $correct_answers++;
        } else {
            $incorrect_answers++;
        }
    } else {
        $unanswered_questions++;
    }
    
    if ($question->markedforreview == 1) {
        $marked_questions++;
    }
}

// Calculate percentages
$score_percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;
$answered_score_percentage = $answered_questions > 0 ? round(($correct_answers / $answered_questions) * 100) : 0;

// Get course context for question categories (used by generateSessionData function)
$coursecontext = context_course::instance($course->id);

// Get category IDs for Grade Report (organized_categories) - used by generateSessionData function
$categoryids = $DB->get_fieldset_sql(
    "SELECT id FROM {question_categories} 
     WHERE contextid = ?",
    array($coursecontext->id)
);

// Get cumulative statistics for each category (study sessions only)
$category_stats = array();
if (!empty($categoryids)) {
    // Get cumulative stats for each category from study sessions only
    // Need to join with question table to get category information
    $stats_query = "
        SELECT 
            qbe.questioncategoryid,
            COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
            COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
        FROM {revitimsession_practice_exam_questions} peq
        JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
        JOIN {question_versions} qv ON peq.questionid = qv.questionid
        JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
        WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
        AND pe.studysession = 1
        GROUP BY qbe.questioncategoryid
    ";
    
    $stats_results = $DB->get_records_sql($stats_query, $categoryids);
    
    // Convert to associative array for easy lookup
    foreach ($stats_results as $stat) {
        $category_stats[$stat->questioncategoryid] = array(
            'answered' => $stat->answered_count,
            'correct' => $stat->correct_count
        );
    }
}

// Get statistics for last 3 study sessions for each category
$last_3_sessions_stats = array();
if (!empty($categoryids)) {
    // Get the last 3 study session IDs
    $last_3_sessions_query = "
        SELECT id
        FROM {revitimsession_practice_exams}
        WHERE studysession = 1
        AND timefinished IS NOT NULL
        AND timefinished > 0
        ORDER BY timefinished DESC
        LIMIT 3
    ";
    
    $last_3_sessions = $DB->get_records_sql($last_3_sessions_query);
    
    if (!empty($last_3_sessions)) {
        $session_ids = array_keys($last_3_sessions);
        
        // Get stats for the last 3 sessions by category
        $last_3_stats_query = "
            SELECT 
                qbe.questioncategoryid,
                COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
            FROM {revitimsession_practice_exam_questions} peq
            JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
            JOIN {question_versions} qv ON peq.questionid = qv.questionid
            JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
            WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
            AND pe.id IN (" . implode(',', array_fill(0, count($session_ids), '?')) . ")
            GROUP BY qbe.questioncategoryid
        ";
        
        $params = array_merge($categoryids, $session_ids);
        $last_3_results = $DB->get_records_sql($last_3_stats_query, $params);
        
        // Convert to associative array for easy lookup
        foreach ($last_3_results as $stat) {
            $last_3_sessions_stats[$stat->questioncategoryid] = array(
                'answered' => $stat->answered_count,
                'correct' => $stat->correct_count
            );
        }
    }
}

// Get statistics for most recent study session for each category
$most_recent_session_stats = array();
if (!empty($categoryids)) {
    // Get the most recent study session ID
    $most_recent_session_query = "
        SELECT id
        FROM {revitimsession_practice_exams}
        WHERE studysession = 1
        AND timefinished IS NOT NULL
        AND timefinished > 0
        ORDER BY timefinished DESC
        LIMIT 1
    ";
    
    $most_recent_session = $DB->get_record_sql($most_recent_session_query);
    
    if ($most_recent_session) {
        // Get stats for the most recent session by category
        $most_recent_stats_query = "
            SELECT 
                qbe.questioncategoryid,
                COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
            FROM {revitimsession_practice_exam_questions} peq
            JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
            JOIN {question_versions} qv ON peq.questionid = qv.questionid
            JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
            WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
            AND pe.id = ?
            GROUP BY qbe.questioncategoryid
        ";
        
        $params = array_merge($categoryids, array($most_recent_session->id));
        $most_recent_results = $DB->get_records_sql($most_recent_stats_query, $params);
        
        // Convert to associative array for easy lookup
        foreach ($most_recent_results as $stat) {
            $most_recent_session_stats[$stat->questioncategoryid] = array(
                'answered' => $stat->answered_count,
                'correct' => $stat->correct_count
            );
        }
    }
}

// Get date range for study sessions (first and last study session dates)
$date_range_query = "
    SELECT 
        MIN(timefinished) as first_study_date,
        MAX(timefinished) as last_study_date
    FROM {revitimsession_practice_exams}
    WHERE studysession = 1
    AND timefinished IS NOT NULL
    AND timefinished > 0
";

$date_range_result = $DB->get_record_sql($date_range_query);

// Get the 3 most recent study session dates
$recent_dates_query = "
    SELECT DISTINCT timefinished
    FROM {revitimsession_practice_exams}
    WHERE studysession = 1
    AND timefinished IS NOT NULL
    AND timefinished > 0
    ORDER BY timefinished DESC
    LIMIT 3
";

$recent_dates_result = $DB->get_records_sql($recent_dates_query);

// Format dates for display
$first_study_date = '';
$last_study_date = '';
$date_range_text = '';
$most_recent_date = '';
$last_3_dates_text = '';

if ($date_range_result && $date_range_result->first_study_date && $date_range_result->last_study_date) {
    $first_study_date = date('n/j', $date_range_result->first_study_date); // Format: M/D (e.g., 9/1)
    $last_study_date = date('n/j', $date_range_result->last_study_date);   // Format: M/D (e.g., 9/21)
    
    if ($first_study_date === $last_study_date) {
        // Same date
        $date_range_text = $first_study_date;
    } else {
        // Date range
        $date_range_text = $first_study_date . '-' . $last_study_date;
    }
}

// Format most recent date
if ($date_range_result && $date_range_result->last_study_date) {
    $most_recent_date = date('n/j', $date_range_result->last_study_date);
}

// Format last 3 dates
if (!empty($recent_dates_result)) {
    $last_3_dates = array();
    foreach ($recent_dates_result as $date_record) {
        $last_3_dates[] = date('n/j', $date_record->timefinished);
    }
    $last_3_dates_text = implode(', ', $last_3_dates);
}

// Get number of questions of each category with parent category info (same as create_practice_step1)
if (!empty($categoryids)) {
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

// Get organized categories for Grade Report (with full statistics)
$organized_categories = array();

if (!empty($categorycounts)) {
    // Group categories by parent
    $parent_groups = array();
    foreach ($categorycounts as $category) {
        $parent_name = $category->parentname ?: '';
        if (!isset($parent_groups[$parent_name])) {
            $parent_groups[$parent_name] = array();
        }
        $parent_groups[$parent_name][] = $category;
    }
    
    // Create organized structure
    foreach ($parent_groups as $parent_name => $categories) {
        if (empty($parent_name)) {
            // Root categories (no parent)
            foreach ($categories as $category) {
                // Get stats for this category (cumulative - 5th column)
                $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                
                // Get most recent session stats (3rd column)
                $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                
                // Get last 3 sessions stats (4th column)
                $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                
                // Determine percentage class for visual representation (cumulative)
                $percentage_class = 'low'; // red
                if ($percentage == 100) {
                    $percentage_class = 'perfect'; // blue for 100%
                } elseif ($percentage >= 80) {
                    $percentage_class = 'high'; // green for high scores
                } elseif ($percentage >= 60) {
                    $percentage_class = 'medium'; // yellow for medium scores
                }
                
                // Determine percentage class for most recent session
                $most_recent_percentage_class = 'low';
                if ($most_recent_percentage == 100) {
                    $most_recent_percentage_class = 'perfect';
                } elseif ($most_recent_percentage >= 80) {
                    $most_recent_percentage_class = 'high';
                } elseif ($most_recent_percentage >= 60) {
                    $most_recent_percentage_class = 'medium';
                }
                
                // Determine percentage class for last 3 sessions
                $last_3_percentage_class = 'low';
                if ($last_3_percentage == 100) {
                    $last_3_percentage_class = 'perfect';
                } elseif ($last_3_percentage >= 80) {
                    $last_3_percentage_class = 'high';
                } elseif ($last_3_percentage >= 60) {
                    $last_3_percentage_class = 'medium';
                }
                
                $organized_categories[] = array(
                    'is_root' => true,
                    'categoryname' => $category->categoryname,
                    'item_count' => $category->item_count,
                    // 3rd column - Most Recent
                    'most_recent_answered' => $most_recent['answered'],
                    'most_recent_correct' => $most_recent['correct'],
                    'most_recent_percentage' => $most_recent_percentage,
                    'most_recent_percentage_class' => $most_recent_percentage_class,
                    // 4th column - Last 3 Attempts
                    'last_3_answered' => $last_3['answered'],
                    'last_3_correct' => $last_3['correct'],
                    'last_3_percentage' => $last_3_percentage,
                    'last_3_percentage_class' => $last_3_percentage_class,
                    // 5th column - Cumulative Score
                    'answered_count' => $stats['answered'],
                    'correct_count' => $stats['correct'],
                    'percentage' => $percentage,
                    'percentage_class' => $percentage_class
                );
            }
        } else {
            // Parent categories with subcategories
            $parent_total = 0;
            $parent_answered_total = 0;
            $parent_correct_total = 0;
            $parent_most_recent_answered_total = 0;
            $parent_most_recent_correct_total = 0;
            $parent_last_3_answered_total = 0;
            $parent_last_3_correct_total = 0;
            
            // Calculate parent totals and add stats to each subcategory
            foreach ($categories as $category) {
                $parent_total += $category->item_count;
                
                // Get stats for this subcategory (cumulative - 5th column)
                $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $parent_answered_total += $stats['answered'];
                $parent_correct_total += $stats['correct'];
                
                // Get most recent session stats (3rd column)
                $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $parent_most_recent_answered_total += $most_recent['answered'];
                $parent_most_recent_correct_total += $most_recent['correct'];
                
                // Get last 3 sessions stats (4th column)
                $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                $parent_last_3_answered_total += $last_3['answered'];
                $parent_last_3_correct_total += $last_3['correct'];
                
                // Calculate percentages for subcategory
                $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                
                // Determine percentage classes for subcategory
                $percentage_class = 'low'; // red
                if ($percentage == 100) {
                    $percentage_class = 'perfect'; // blue for 100%
                } elseif ($percentage >= 80) {
                    $percentage_class = 'high'; // green for high scores
                } elseif ($percentage >= 60) {
                    $percentage_class = 'medium'; // yellow for medium scores
                }
                
                $most_recent_percentage_class = 'low';
                if ($most_recent_percentage == 100) {
                    $most_recent_percentage_class = 'perfect';
                } elseif ($most_recent_percentage >= 80) {
                    $most_recent_percentage_class = 'high';
                } elseif ($most_recent_percentage >= 60) {
                    $most_recent_percentage_class = 'medium';
                }
                
                $last_3_percentage_class = 'low';
                if ($last_3_percentage == 100) {
                    $last_3_percentage_class = 'perfect';
                } elseif ($last_3_percentage >= 80) {
                    $last_3_percentage_class = 'high';
                } elseif ($last_3_percentage >= 60) {
                    $last_3_percentage_class = 'medium';
                }
                
                // Add stats to category object
                $category->answered_count = $stats['answered'];
                $category->correct_count = $stats['correct'];
                $category->percentage = $percentage;
                $category->percentage_class = $percentage_class;
                $category->most_recent_answered = $most_recent['answered'];
                $category->most_recent_correct = $most_recent['correct'];
                $category->most_recent_percentage = $most_recent_percentage;
                $category->most_recent_percentage_class = $most_recent_percentage_class;
                $category->last_3_answered = $last_3['answered'];
                $category->last_3_correct = $last_3['correct'];
                $category->last_3_percentage = $last_3_percentage;
                $category->last_3_percentage_class = $last_3_percentage_class;
            }
            
            // Calculate parent percentages
            $parent_percentage = $parent_answered_total > 0 ? round(($parent_correct_total / $parent_answered_total) * 100) : 0;
            $parent_most_recent_percentage = $parent_most_recent_answered_total > 0 ? round(($parent_most_recent_correct_total / $parent_most_recent_answered_total) * 100) : 0;
            $parent_last_3_percentage = $parent_last_3_answered_total > 0 ? round(($parent_last_3_correct_total / $parent_last_3_answered_total) * 100) : 0;
            
            // Determine parent percentage classes
            $parent_percentage_class = 'low'; // red
            if ($parent_percentage == 100) {
                $parent_percentage_class = 'perfect'; // blue for 100%
            } elseif ($parent_percentage >= 80) {
                $parent_percentage_class = 'high'; // green for high scores
            } elseif ($parent_percentage >= 60) {
                $parent_percentage_class = 'medium'; // yellow for medium scores
            }
            
            $parent_most_recent_percentage_class = 'low';
            if ($parent_most_recent_percentage == 100) {
                $parent_most_recent_percentage_class = 'perfect';
            } elseif ($parent_most_recent_percentage >= 80) {
                $parent_most_recent_percentage_class = 'high';
            } elseif ($parent_most_recent_percentage >= 60) {
                $parent_most_recent_percentage_class = 'medium';
            }
            
            $parent_last_3_percentage_class = 'low';
            if ($parent_last_3_percentage == 100) {
                $parent_last_3_percentage_class = 'perfect';
            } elseif ($parent_last_3_percentage >= 80) {
                $parent_last_3_percentage_class = 'high';
            } elseif ($parent_last_3_percentage >= 60) {
                $parent_last_3_percentage_class = 'medium';
            }
            
            $organized_categories[] = array(
                'is_root' => false,
                'parent_id' => 'parent_' . md5($parent_name), // Generate unique ID
                'parentname' => $parent_name,
                'parent_total' => $parent_total,
                // 3rd column - Most Recent
                'parent_most_recent_answered_total' => $parent_most_recent_answered_total,
                'parent_most_recent_correct_total' => $parent_most_recent_correct_total,
                'parent_most_recent_percentage' => $parent_most_recent_percentage,
                'parent_most_recent_percentage_class' => $parent_most_recent_percentage_class,
                // 4th column - Last 3 Attempts
                'parent_last_3_answered_total' => $parent_last_3_answered_total,
                'parent_last_3_correct_total' => $parent_last_3_correct_total,
                'parent_last_3_percentage' => $parent_last_3_percentage,
                'parent_last_3_percentage_class' => $parent_last_3_percentage_class,
                // 5th column - Cumulative Score
                'parent_answered_total' => $parent_answered_total,
                'parent_correct_total' => $parent_correct_total,
                'parent_percentage' => $parent_percentage,
                'parent_percentage_class' => $parent_percentage_class,
                'categories' => $categories
            );
        }
    }
}

// Calculate time statistics
// Quiz duration = (number of questions × 1 minute) - timeremaining
$total_allotted_time_seconds = $total_questions * 60; // 1 minute per question

// Handle timeremaining correctly:
// - If positive: user finished before time expired
// - If negative: time expired, user went over time
if ($practice_exam->timeremaining >= 0) {
    // User finished before time expired
    $time_taken_seconds = $total_allotted_time_seconds - $practice_exam->timeremaining;
} else {
    // Time expired, user went over time
    $time_taken_seconds = $total_allotted_time_seconds + abs($practice_exam->timeremaining);
}

// Ensure time taken is not negative
$time_taken_seconds = max(0, $time_taken_seconds);

$total_time_hours = floor($time_taken_seconds / 3600);
$total_time_minutes = floor(($time_taken_seconds % 3600) / 60);
$total_time_seconds_remainder = $time_taken_seconds % 60;
$time_per_question = $answered_questions > 0 ? round($time_taken_seconds / $answered_questions) : 0;
$time_per_question_hours = floor($time_per_question / 3600);
$time_per_question_minutes = floor(($time_per_question % 3600) / 60);
$time_per_question_seconds = $time_per_question % 60;

// Format time strings
$total_time_formatted = sprintf("%d:%02d:%02d", $total_time_hours, $total_time_minutes, $total_time_seconds_remainder);
$time_per_question_formatted = sprintf("%d:%02d:%02d", $time_per_question_hours, $time_per_question_minutes, $time_per_question_seconds);

// Calculate date/time range
$start_time = $practice_exam->timecreated;
$end_time = $practice_exam->timefinished;

// Format dates for display
$start_date_formatted = date('n/j/Y g:i A', $start_time);
$end_date_formatted = date('n/j/Y g:i A', $end_time);
$date_time_range = get_string('date_time_label', 'revitimsession') . ": {$start_date_formatted} - {$end_date_formatted}";

// Start output
$PAGE->set_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid));
$PAGE->set_title(get_string('performance_analysis', 'revitimsession'));
$PAGE->set_heading($course->fullname);

// Load CSS file
$PAGE->requires->css(new moodle_url('/mod/revitimsession/styles.css'));

echo $OUTPUT->header();

// Navigation breadcrumb removed - using Test Bank Home Screen link in template instead

// Calculate additional statistics for template - missed or marked questions
$missed_or_marked_count = 0;
foreach ($questions as $question) {
    // Count questions that are either MISSED (answer is null) OR MARKED (markedforreview = 1)
    // Do NOT count incorrect answers
    if ($question->answer === null || $question->markedforreview == 1) {
        $missed_or_marked_count++;
    }
}

$has_incorrect_or_marked = ($missed_or_marked_count > 0);
$has_incorrect = ($incorrect_answers > 0);


// Determine session type and appropriate URLs
$is_study_session = ($practice_exam->studysession == 1);
$session_type_text = $is_study_session ? get_string('study_session', 'revitimsession') : get_string('practice_exam', 'revitimsession');
$perform_url = $is_study_session ? 'perform_study.php' : 'perform_exam.php';

// Get alternative session (opposite type) for the toggle button
$alternative_studysession = $is_study_session ? 0 : 1; // Opposite of current
$alternative_exam_sql = "SELECT id FROM {revitimsession_practice_exams} 
                        WHERE userid = :userid AND courseid = :courseid AND studysession = :studysession AND status = 1
                        ORDER BY timefinished DESC LIMIT 1";
$alternative_exam = $DB->get_record_sql($alternative_exam_sql, array(
    'userid' => $USER->id, 
    'courseid' => $course->id, 
    'studysession' => $alternative_studysession
));

// Determine button text and alternative examid
if ($is_study_session) {
    $view_grade_report_text = get_string('view_exam_session_grade_report', 'revitimsession');
    $alternative_examid = $alternative_exam ? $alternative_exam->id : 0;
} else {
    $view_grade_report_text = get_string('view_study_session_grade_report', 'revitimsession');
    $alternative_examid = $alternative_exam ? $alternative_exam->id : 0;
}

/**
 * Check if there's an unfinished exam/session of the same type
 * @param object $DB Database object
 * @param int $courseid Course ID
 * @param int $userid User ID
 * @param int $studysession 1 for study session, 0 for exam
 * @return bool True if there's an unfinished exam, false otherwise
 */
/**
 * Helper function to create a new exam/session with given questions
 */
function createNewExam($DB, $course, $USER, $studysession, $questions, $id) {
    global $CFG;
    
    // Create new practice exam record
    $new_exam = new stdClass();
    $new_exam->userid = $USER->id;
    $new_exam->courseid = $course->id;
    $new_exam->studysession = $studysession;
    $new_exam->status = 0; // 0 = not finished
    $new_exam->timecreated = time();
    $new_exam->timefinished = null;
    $new_exam->timemodified = time();
    
    $new_exam_id = $DB->insert_record('revitimsession_practice_exams', $new_exam);
    
    if (!$new_exam_id) {
        return false;
    }
    
    // Add questions to the new exam
    foreach ($questions as $index => $question) {
        $exam_question = new stdClass();
        $exam_question->practiceexamid = $new_exam_id;
        $exam_question->questionid = $question->questionid;
        $exam_question->questionorder = $index + 1; // Start from 1
        $exam_question->status = 0; // 0 = unseen
        $exam_question->correct = 0; // 0 = not answered
        $exam_question->markedforreview = 0; // 0 = not marked
        $exam_question->timecreated = time();
        $exam_question->timemodified = time();
        
        $DB->insert_record('revitimsession_practice_exam_questions', $exam_question);
    }
    
    return $new_exam_id;
}

/**
 * Create a new exam/session with marked or not fully answered questions
 */
function createMarkedQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get marked or not fully answered questions
    $marked_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.markedforreview
                            FROM {revitimsession_practice_exam_questions} peq
                            JOIN {question} q ON peq.questionid = q.id
                            WHERE peq.practiceexamid = :examid AND (peq.markedforreview = 1 OR peq.status <> 2)
                            ORDER BY peq.questionorder ASC";
    $marked_questions = array_values($DB->get_records_sql($marked_questions_sql, array('examid' => $examid)));
    
    if (empty($marked_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_marked_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with marked questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $marked_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('marked_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

/**
 * Create a new exam/session with incorrect questions
 */
function createIncorrectQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get incorrect questions
    $incorrect_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.correct
                               FROM {revitimsession_practice_exam_questions} peq
                               JOIN {question} q ON peq.questionid = q.id
                               WHERE peq.practiceexamid = :examid AND peq.correct = 0 AND peq.status = 2
                               ORDER BY peq.questionorder ASC";
    $incorrect_questions = array_values($DB->get_records_sql($incorrect_questions_sql, array('examid' => $examid)));
    
    if (empty($incorrect_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_incorrect_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with incorrect questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $incorrect_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('incorrect_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

/**
 * Create a new exam/session with all questions from current session
 */
function createAllQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get all questions from current session
    $all_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.correct, peq.markedforreview
                         FROM {revitimsession_practice_exam_questions} peq
                         JOIN {question} q ON peq.questionid = q.id
                         WHERE peq.practiceexamid = :examid
                         ORDER BY peq.questionorder ASC";
    $all_questions = array_values($DB->get_records_sql($all_questions_sql, array('examid' => $examid)));
    
    if (empty($all_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with all questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $all_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('all_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

/**
 * Create a new exam/session with correct questions
 */
function createCorrectQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get correct questions
    $correct_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.correct, peq.markedforreview
                             FROM {revitimsession_practice_exam_questions} peq
                             JOIN {question} q ON peq.questionid = q.id
                             WHERE peq.practiceexamid = :examid AND peq.correct <> 0 AND peq.status = 2
                             ORDER BY peq.questionorder ASC";
    $correct_questions = array_values($DB->get_records_sql($correct_questions_sql, array('examid' => $examid)));
    
    if (empty($correct_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_correct_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with correct questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $correct_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('correct_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

/**
 * Create a new exam/session with unanswered questions
 */
function createUnansweredQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get unanswered questions
    $unanswered_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.correct, peq.markedforreview
                                FROM {revitimsession_practice_exam_questions} peq
                                JOIN {question} q ON peq.questionid = q.id
                                WHERE peq.practiceexamid = :examid AND peq.status <> 2
                                ORDER BY peq.questionorder ASC";
    $unanswered_questions = array_values($DB->get_records_sql($unanswered_questions_sql, array('examid' => $examid)));
    
    if (empty($unanswered_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_unanswered_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with unanswered questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $unanswered_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('unanswered_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

/**
 * Create a new exam/session with only marked questions
 */
function createMarkedOnlyQuestionsExam($DB, $course, $USER, $practice_exam, $examid, $id) {
    // Check if there's an unfinished exam of the same type
    if (hasUnfinishedExam($DB, $course->id, $USER->id, $practice_exam->studysession)) {
        $session_type = $practice_exam->studysession == 1 ? get_string('study_session_type', 'revitimsession') : get_string('exam_type', 'revitimsession');
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('unfinished_exam_exists', 'revitimsession', $session_type), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Get only marked questions
    $marked_only_questions_sql = "SELECT peq.questionid, peq.questionorder, q.questiontext, peq.status, peq.correct, peq.markedforreview
                                 FROM {revitimsession_practice_exam_questions} peq
                                 JOIN {question} q ON peq.questionid = q.id
                                 WHERE peq.practiceexamid = :examid AND peq.markedforreview = 1
                                 ORDER BY peq.questionorder ASC";
    $marked_only_questions = array_values($DB->get_records_sql($marked_only_questions_sql, array('examid' => $examid)));
    
    if (empty($marked_only_questions)) {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('no_marked_only_questions_found', 'revitimsession'), null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Create new exam with only marked questions
    $new_exam_id = createNewExam($DB, $course, $USER, $practice_exam->studysession, $marked_only_questions, $id);
    
    if ($new_exam_id) {
        // Redirect to the appropriate page based on session type
        $redirect_url = $practice_exam->studysession == 1 ? 
            new moodle_url('/mod/revitimsession/perform_study.php', array('id' => $id, 'examid' => $new_exam_id)) :
            new moodle_url('/mod/revitimsession/perform_exam.php', array('id' => $id, 'examid' => $new_exam_id));
        
        redirect($redirect_url, get_string('marked_only_questions_exam_created', 'revitimsession'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/mod/revitimsession/stats.php', array('id' => $id, 'examid' => $examid)), 
            get_string('exam_creation_error', 'revitimsession'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

function hasUnfinishedExam($DB, $courseid, $userid, $studysession) {
    $unfinished_exam_sql = "SELECT id FROM {revitimsession_practice_exams} 
                            WHERE userid = :userid AND courseid = :courseid AND studysession = :studysession AND status = 0
                            LIMIT 1";
    $unfinished_exam = $DB->get_record_sql($unfinished_exam_sql, array(
        'userid' => $userid, 
        'courseid' => $courseid, 
        'studysession' => $studysession
    ));
    
    return $unfinished_exam ? true : false;
}

/**
 * Create a new exam/session with marked questions from current session
 * @param object $DB Database object
 * @param int $current_examid Current exam ID
 * @param int $courseid Course ID
 * @param int $userid User ID
 * @param int $studysession 1 for study session, 0 for exam
 * @return int New exam ID
 */
function createExamWithMarkedQuestions($DB, $current_examid, $courseid, $userid, $studysession) {
    // Get marked questions and unanswered questions from current exam
    $marked_questions_sql = "SELECT questionid FROM {revitimsession_practice_exam_questions} 
                            WHERE practiceexamid = :examid AND (markedforreview = 1 OR status <> 2)
                            ORDER BY questionorder ASC";
    $marked_questions = array_values($DB->get_records_sql($marked_questions_sql, array('examid' => $current_examid)));
    
    if (empty($marked_questions)) {
        return 0; // No marked questions found
    }
    
    // Create new exam/session
    $new_exam_data = new stdClass();
    $new_exam_data->userid = $userid;
    $new_exam_data->courseid = $courseid;
    $new_exam_data->status = 0; // 0 = not finished, 1 = finished
    $new_exam_data->timeremaining = count($marked_questions) * 60; // 1 minute per question
    $new_exam_data->totalquestions = count($marked_questions);
    $new_exam_data->randomanswers = 0; // Keep original order
    $new_exam_data->studysession = $studysession;
    $new_exam_data->timecreated = time();
    $new_exam_data->timemodified = time();
    
    $new_exam_id = $DB->insert_record('revitimsession_practice_exams', $new_exam_data);
    
    // Insert marked questions into new exam
    foreach ($marked_questions as $index => $question) {
        $question_data = new stdClass();
        $question_data->practiceexamid = $new_exam_id;
        $question_data->questionid = $question->questionid;
        $question_data->questionorder = $index + 1;
        $question_data->timecreated = time();
        
        $DB->insert_record('revitimsession_practice_exam_questions', $question_data);
    }
    
    return $new_exam_id;
}

/**
 * Generate Grade Report data with full statistics
 * @param object $DB Database object
 * @param int $courseid Course ID
 * @return array Grade Report data
 */
function generateGradeReportData($DB, $courseid) {
    // Get course context for question categories
    $coursecontext = context_course::instance($courseid);
    
    // Get the most recently finished practice exam
    $most_recent_exam_query = "
        SELECT id
        FROM {revitimsession_practice_exams}
        WHERE courseid = ?
        AND timefinished IS NOT NULL
        AND timefinished > 0
        ORDER BY timefinished DESC
        LIMIT 1
    ";
    
    $most_recent_exam = $DB->get_record_sql($most_recent_exam_query, array($courseid));
    
    if (!$most_recent_exam) {
        // No finished exams found, return empty array
        return array();
    }
    
    // Get category IDs that have questions in the most recent exam
    $categoryids = $DB->get_fieldset_sql(
        "SELECT DISTINCT qbe.questioncategoryid
         FROM {revitimsession_practice_exam_questions} peq
         JOIN {question_versions} qv ON peq.questionid = qv.questionid
         JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
         JOIN {question_categories} qc ON qbe.questioncategoryid = qc.id
         WHERE peq.practiceexamid = ?
         AND qc.contextid = ?",
        array($most_recent_exam->id, $coursecontext->id)
    );
    
    // Get cumulative statistics for each category (study sessions only)
    $category_stats = array();
    if (!empty($categoryids)) {
        $stats_query = "
            SELECT 
                qbe.questioncategoryid,
                COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
            FROM {revitimsession_practice_exam_questions} peq
            JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
            JOIN {question_versions} qv ON peq.questionid = qv.questionid
            JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
            WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
            AND pe.studysession = 1
            GROUP BY qbe.questioncategoryid
        ";
        
        $stats_results = $DB->get_records_sql($stats_query, $categoryids);
        
        foreach ($stats_results as $stat) {
            $category_stats[$stat->questioncategoryid] = array(
                'answered' => $stat->answered_count,
                'correct' => $stat->correct_count
            );
        }
    }
    
    // Get statistics for last 3 study sessions for each category
    $last_3_sessions_stats = array();
    if (!empty($categoryids)) {
        $last_3_sessions_query = "
            SELECT id
            FROM {revitimsession_practice_exams}
            WHERE studysession = 1
            AND timefinished IS NOT NULL
            AND timefinished > 0
            ORDER BY timefinished DESC
            LIMIT 3
        ";
        
        $last_3_sessions = $DB->get_records_sql($last_3_sessions_query);
        
        if (!empty($last_3_sessions)) {
            $session_ids = array_keys($last_3_sessions);
            
            $last_3_stats_query = "
                SELECT 
                    qbe.questioncategoryid,
                    COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                    COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
                FROM {revitimsession_practice_exam_questions} peq
                JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
                JOIN {question_versions} qv ON peq.questionid = qv.questionid
                JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
                AND pe.id IN (" . implode(',', array_fill(0, count($session_ids), '?')) . ")
                GROUP BY qbe.questioncategoryid
            ";
            
            $params = array_merge($categoryids, $session_ids);
            $last_3_results = $DB->get_records_sql($last_3_stats_query, $params);
            
            foreach ($last_3_results as $stat) {
                $last_3_sessions_stats[$stat->questioncategoryid] = array(
                    'answered' => $stat->answered_count,
                    'correct' => $stat->correct_count
                );
            }
        }
    }
    
    // Get statistics for most recent study session for each category
    $most_recent_session_stats = array();
    if (!empty($categoryids)) {
        $most_recent_session_query = "
            SELECT id
            FROM {revitimsession_practice_exams}
            WHERE studysession = 1
            AND timefinished IS NOT NULL
            AND timefinished > 0
            ORDER BY timefinished DESC
            LIMIT 1
        ";
        
        $most_recent_session = $DB->get_record_sql($most_recent_session_query);
        
        if ($most_recent_session) {
            $most_recent_stats_query = "
                SELECT 
                    qbe.questioncategoryid,
                    COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                    COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
                FROM {revitimsession_practice_exam_questions} peq
                JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
                JOIN {question_versions} qv ON peq.questionid = qv.questionid
                JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
                AND pe.id = ?
                GROUP BY qbe.questioncategoryid
            ";
            
            $params = array_merge($categoryids, array($most_recent_session->id));
            $most_recent_results = $DB->get_records_sql($most_recent_stats_query, $params);
            
            foreach ($most_recent_results as $stat) {
                $most_recent_session_stats[$stat->questioncategoryid] = array(
                    'answered' => $stat->answered_count,
                    'correct' => $stat->correct_count
                );
            }
        }
    }
    
    // Get number of questions of each category with parent category info
    if (!empty($categoryids)) {
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
    
    // Get organized categories for Grade Report (with full statistics)
    $organized_categories = array();
    
    if (!empty($categorycounts)) {
        // Group categories by parent
        $parent_groups = array();
        foreach ($categorycounts as $category) {
            $parent_name = $category->parentname ?: '';
            if (!isset($parent_groups[$parent_name])) {
                $parent_groups[$parent_name] = array();
            }
            $parent_groups[$parent_name][] = $category;
        }
        
        // Create organized structure
        foreach ($parent_groups as $parent_name => $categories) {
            if (empty($parent_name)) {
                // Root categories (no parent)
                foreach ($categories as $category) {
                    // Get stats for this category (cumulative - 5th column)
                    $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                    
                    // Get most recent session stats (3rd column)
                    $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                    
                    // Get last 3 sessions stats (4th column)
                    $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                    
                    // Determine percentage classes
                    $percentage_class = 'low';
                    if ($percentage == 100) {
                        $percentage_class = 'perfect';
                    } elseif ($percentage >= 80) {
                        $percentage_class = 'high';
                    } elseif ($percentage >= 60) {
                        $percentage_class = 'medium';
                    }
                    
                    $most_recent_percentage_class = 'low';
                    if ($most_recent_percentage == 100) {
                        $most_recent_percentage_class = 'perfect';
                    } elseif ($most_recent_percentage >= 80) {
                        $most_recent_percentage_class = 'high';
                    } elseif ($most_recent_percentage >= 60) {
                        $most_recent_percentage_class = 'medium';
                    }
                    
                    $last_3_percentage_class = 'low';
                    if ($last_3_percentage == 100) {
                        $last_3_percentage_class = 'perfect';
                    } elseif ($last_3_percentage >= 80) {
                        $last_3_percentage_class = 'high';
                    } elseif ($last_3_percentage >= 60) {
                        $last_3_percentage_class = 'medium';
                    }
                    
                    $organized_categories[] = array(
                        'is_root' => true,
                        'categoryname' => $category->categoryname,
                        'item_count' => $category->item_count,
                        // 3rd column - Most Recent
                        'most_recent_answered' => $most_recent['answered'],
                        'most_recent_correct' => $most_recent['correct'],
                        'most_recent_percentage' => $most_recent_percentage,
                        'most_recent_percentage_class' => $most_recent_percentage_class,
                        // 4th column - Last 3 Attempts
                        'last_3_answered' => $last_3['answered'],
                        'last_3_correct' => $last_3['correct'],
                        'last_3_percentage' => $last_3_percentage,
                        'last_3_percentage_class' => $last_3_percentage_class,
                        // 5th column - Cumulative Score
                        'answered_count' => $stats['answered'],
                        'correct_count' => $stats['correct'],
                        'percentage' => $percentage,
                        'percentage_class' => $percentage_class
                    );
                }
            } else {
                // Parent categories with subcategories
                $parent_total = 0;
                $parent_answered_total = 0;
                $parent_correct_total = 0;
                $parent_most_recent_answered_total = 0;
                $parent_most_recent_correct_total = 0;
                $parent_last_3_answered_total = 0;
                $parent_last_3_correct_total = 0;
                
                // Calculate parent totals and add stats to each subcategory
                foreach ($categories as $category) {
                    $parent_total += $category->item_count;
                    
                    // Get stats for this subcategory (cumulative - 5th column)
                    $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_answered_total += $stats['answered'];
                    $parent_correct_total += $stats['correct'];
                    
                    // Get most recent session stats (3rd column)
                    $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_most_recent_answered_total += $most_recent['answered'];
                    $parent_most_recent_correct_total += $most_recent['correct'];
                    
                    // Get last 3 sessions stats (4th column)
                    $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_last_3_answered_total += $last_3['answered'];
                    $parent_last_3_correct_total += $last_3['correct'];
                    
                    // Calculate percentages for subcategory
                    $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                    $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                    $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                    
                    // Determine percentage classes for subcategory
                    $percentage_class = 'low';
                    if ($percentage == 100) {
                        $percentage_class = 'perfect';
                    } elseif ($percentage >= 80) {
                        $percentage_class = 'high';
                    } elseif ($percentage >= 60) {
                        $percentage_class = 'medium';
                    }
                    
                    $most_recent_percentage_class = 'low';
                    if ($most_recent_percentage == 100) {
                        $most_recent_percentage_class = 'perfect';
                    } elseif ($most_recent_percentage >= 80) {
                        $most_recent_percentage_class = 'high';
                    } elseif ($most_recent_percentage >= 60) {
                        $most_recent_percentage_class = 'medium';
                    }
                    
                    $last_3_percentage_class = 'low';
                    if ($last_3_percentage == 100) {
                        $last_3_percentage_class = 'perfect';
                    } elseif ($last_3_percentage >= 80) {
                        $last_3_percentage_class = 'high';
                    } elseif ($last_3_percentage >= 60) {
                        $last_3_percentage_class = 'medium';
                    }
                    
                    // Add stats to category object
                    $category->answered_count = $stats['answered'];
                    $category->correct_count = $stats['correct'];
                    $category->percentage = $percentage;
                    $category->percentage_class = $percentage_class;
                    $category->most_recent_answered = $most_recent['answered'];
                    $category->most_recent_correct = $most_recent['correct'];
                    $category->most_recent_percentage = $most_recent_percentage;
                    $category->most_recent_percentage_class = $most_recent_percentage_class;
                    $category->last_3_answered = $last_3['answered'];
                    $category->last_3_correct = $last_3['correct'];
                    $category->last_3_percentage = $last_3_percentage;
                    $category->last_3_percentage_class = $last_3_percentage_class;
                }
                
                // Calculate parent percentages
                $parent_percentage = $parent_answered_total > 0 ? round(($parent_correct_total / $parent_answered_total) * 100) : 0;
                $parent_most_recent_percentage = $parent_most_recent_answered_total > 0 ? round(($parent_most_recent_correct_total / $parent_most_recent_answered_total) * 100) : 0;
                $parent_last_3_percentage = $parent_last_3_answered_total > 0 ? round(($parent_last_3_correct_total / $parent_last_3_answered_total) * 100) : 0;
                
                // Determine parent percentage classes
                $parent_percentage_class = 'low';
                if ($parent_percentage == 100) {
                    $parent_percentage_class = 'perfect';
                } elseif ($parent_percentage >= 80) {
                    $parent_percentage_class = 'high';
                } elseif ($parent_percentage >= 60) {
                    $parent_percentage_class = 'medium';
                }
                
                $parent_most_recent_percentage_class = 'low';
                if ($parent_most_recent_percentage == 100) {
                    $parent_most_recent_percentage_class = 'perfect';
                } elseif ($parent_most_recent_percentage >= 80) {
                    $parent_most_recent_percentage_class = 'high';
                } elseif ($parent_most_recent_percentage >= 60) {
                    $parent_most_recent_percentage_class = 'medium';
                }
                
                $parent_last_3_percentage_class = 'low';
                if ($parent_last_3_percentage == 100) {
                    $parent_last_3_percentage_class = 'perfect';
                } elseif ($parent_last_3_percentage >= 80) {
                    $parent_last_3_percentage_class = 'high';
                } elseif ($parent_last_3_percentage >= 60) {
                    $parent_last_3_percentage_class = 'medium';
                }
                
                $organized_categories[] = array(
                    'is_root' => false,
                    'parent_id' => 'parent_' . md5($parent_name),
                    'parentname' => $parent_name,
                    'parent_total' => $parent_total,
                    // 3rd column - Most Recent
                    'parent_most_recent_answered_total' => $parent_most_recent_answered_total,
                    'parent_most_recent_correct_total' => $parent_most_recent_correct_total,
                    'parent_most_recent_percentage' => $parent_most_recent_percentage,
                    'parent_most_recent_percentage_class' => $parent_most_recent_percentage_class,
                    // 4th column - Last 3 Attempts
                    'parent_last_3_answered_total' => $parent_last_3_answered_total,
                    'parent_last_3_correct_total' => $parent_last_3_correct_total,
                    'parent_last_3_percentage' => $parent_last_3_percentage,
                    'parent_last_3_percentage_class' => $parent_last_3_percentage_class,
                    // 5th column - Cumulative Score
                    'parent_answered_total' => $parent_answered_total,
                    'parent_correct_total' => $parent_correct_total,
                    'parent_percentage' => $parent_percentage,
                    'parent_percentage_class' => $parent_percentage_class,
                    'categories' => $categories
                );
            }
        }
    }
    
    return $organized_categories;
}

/**
 * Generate session data for Study Session or Exam Session tabs
 * @param int $studysession 1 for study session, 0 for exam session
 * @param object $DB Database object
 * @param int $courseid Course ID
 * @return array Session data
 */
function generateSessionData($studysession, $DB, $courseid) {
    // Get course context for question categories
    $coursecontext = context_course::instance($courseid);
    
    // Get category IDs
    $categoryids = $DB->get_fieldset_sql(
        "SELECT id FROM {question_categories} 
         WHERE contextid = ?",
        array($coursecontext->id)
    );
    
    // Get number of questions of each category with parent category info
    if (!empty($categoryids)) {
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
    
    // Get cumulative statistics for each category (filtered by studysession)
    $category_stats = array();
    if (!empty($categoryids)) {
        $stats_query = "
            SELECT 
                qbe.questioncategoryid,
                COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
            FROM {revitimsession_practice_exam_questions} peq
            JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
            JOIN {question_versions} qv ON peq.questionid = qv.questionid
            JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
            WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
            AND pe.studysession = ?
            GROUP BY qbe.questioncategoryid
        ";
        
        $params = array_merge($categoryids, array($studysession));
        $stats_results = $DB->get_records_sql($stats_query, $params);
        
        // Convert to associative array for easy lookup
        foreach ($stats_results as $stat) {
            $category_stats[$stat->questioncategoryid] = array(
                'answered' => $stat->answered_count,
                'correct' => $stat->correct_count
            );
        }
    }
    
    // Get statistics for last 3 sessions for each category (filtered by studysession)
    $last_3_sessions_stats = array();
    if (!empty($categoryids)) {
        // Get the last 3 session IDs
        $last_3_sessions_query = "
            SELECT id
            FROM {revitimsession_practice_exams}
            WHERE studysession = ?
            AND timefinished IS NOT NULL
            AND timefinished > 0
            ORDER BY timefinished DESC
            LIMIT 3
        ";
        
        $last_3_sessions = $DB->get_records_sql($last_3_sessions_query, array($studysession));
        
        if (!empty($last_3_sessions)) {
            $session_ids = array_keys($last_3_sessions);
            
            // Get stats for the last 3 sessions by category
            $last_3_stats_query = "
                SELECT 
                    qbe.questioncategoryid,
                    COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                    COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
                FROM {revitimsession_practice_exam_questions} peq
                JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
                JOIN {question_versions} qv ON peq.questionid = qv.questionid
                JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
                AND pe.id IN (" . implode(',', array_fill(0, count($session_ids), '?')) . ")
                GROUP BY qbe.questioncategoryid
            ";
            
            $params = array_merge($categoryids, $session_ids);
            $last_3_results = $DB->get_records_sql($last_3_stats_query, $params);
            
            // Convert to associative array for easy lookup
            foreach ($last_3_results as $stat) {
                $last_3_sessions_stats[$stat->questioncategoryid] = array(
                    'answered' => $stat->answered_count,
                    'correct' => $stat->correct_count
                );
            }
        }
    }
    
    // Get statistics for most recent session for each category (filtered by studysession)
    $most_recent_session_stats = array();
    if (!empty($categoryids)) {
        // Get the most recent session ID
        $most_recent_session_query = "
            SELECT id
            FROM {revitimsession_practice_exams}
            WHERE studysession = ?
            AND timefinished IS NOT NULL
            AND timefinished > 0
            ORDER BY timefinished DESC
            LIMIT 1
        ";
        
        $most_recent_session = $DB->get_record_sql($most_recent_session_query, array($studysession));
        
        if ($most_recent_session) {
            // Get stats for the most recent session by category
            $most_recent_stats_query = "
                SELECT 
                    qbe.questioncategoryid,
                    COUNT(CASE WHEN peq.status != 0 THEN 1 END) as answered_count,
                    COUNT(CASE WHEN peq.status != 0 AND peq.correct != 0 THEN 1 END) as correct_count
                FROM {revitimsession_practice_exam_questions} peq
                JOIN {revitimsession_practice_exams} pe ON peq.practiceexamid = pe.id
                JOIN {question_versions} qv ON peq.questionid = qv.questionid
                JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                WHERE qbe.questioncategoryid IN (" . implode(',', array_fill(0, count($categoryids), '?')) . ")
                AND pe.id = ?
                GROUP BY qbe.questioncategoryid
            ";
            
            $params = array_merge($categoryids, array($most_recent_session->id));
            $most_recent_results = $DB->get_records_sql($most_recent_stats_query, $params);
            
            // Convert to associative array for easy lookup
            foreach ($most_recent_results as $stat) {
                $most_recent_session_stats[$stat->questioncategoryid] = array(
                    'answered' => $stat->answered_count,
                    'correct' => $stat->correct_count
                );
            }
        }
    }
    
    // Organize categories (same logic as Grade Report)
    $organized_categories = array();
    
    if (!empty($categorycounts)) {
        // Group categories by parent
        $parent_groups = array();
        foreach ($categorycounts as $category) {
            $parent_name = $category->parentname ?: '';
            if (!isset($parent_groups[$parent_name])) {
                $parent_groups[$parent_name] = array();
            }
            $parent_groups[$parent_name][] = $category;
        }
        
        // Create organized structure
        foreach ($parent_groups as $parent_name => $categories) {
            if (empty($parent_name)) {
                // Root categories (no parent)
                foreach ($categories as $category) {
                    // Get stats for this category (cumulative - 5th column)
                    $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                    
                    // Get most recent session stats (3rd column)
                    $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                    
                    // Get last 3 sessions stats (4th column)
                    $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                    
                    // Determine percentage classes
                    $percentage_class = 'low';
                    if ($percentage == 100) {
                        $percentage_class = 'perfect';
                    } elseif ($percentage >= 80) {
                        $percentage_class = 'high';
                    } elseif ($percentage >= 60) {
                        $percentage_class = 'medium';
                    }
                    
                    $most_recent_percentage_class = 'low';
                    if ($most_recent_percentage == 100) {
                        $most_recent_percentage_class = 'perfect';
                    } elseif ($most_recent_percentage >= 80) {
                        $most_recent_percentage_class = 'high';
                    } elseif ($most_recent_percentage >= 60) {
                        $most_recent_percentage_class = 'medium';
                    }
                    
                    $last_3_percentage_class = 'low';
                    if ($last_3_percentage == 100) {
                        $last_3_percentage_class = 'perfect';
                    } elseif ($last_3_percentage >= 80) {
                        $last_3_percentage_class = 'high';
                    } elseif ($last_3_percentage >= 60) {
                        $last_3_percentage_class = 'medium';
                    }
                    
                    $organized_categories[] = array(
                        'is_root' => true,
                        'categoryname' => $category->categoryname,
                        'item_count' => $category->item_count,
                        // 3rd column - Most Recent
                        'most_recent_answered' => $most_recent['answered'],
                        'most_recent_correct' => $most_recent['correct'],
                        'most_recent_percentage' => $most_recent_percentage,
                        'most_recent_percentage_class' => $most_recent_percentage_class,
                        // 4th column - Last 3 Attempts
                        'last_3_answered' => $last_3['answered'],
                        'last_3_correct' => $last_3['correct'],
                        'last_3_percentage' => $last_3_percentage,
                        'last_3_percentage_class' => $last_3_percentage_class,
                        // 5th column - Cumulative Score
                        'answered_count' => $stats['answered'],
                        'correct_count' => $stats['correct'],
                        'percentage' => $percentage,
                        'percentage_class' => $percentage_class
                    );
                }
            } else {
                // Parent categories with subcategories
                $parent_total = 0;
                $parent_answered_total = 0;
                $parent_correct_total = 0;
                $parent_most_recent_answered_total = 0;
                $parent_most_recent_correct_total = 0;
                $parent_last_3_answered_total = 0;
                $parent_last_3_correct_total = 0;
                
                // Calculate parent totals and add stats to each subcategory
                foreach ($categories as $category) {
                    $parent_total += $category->item_count;
                    
                    // Get stats for this subcategory (cumulative - 5th column)
                    $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_answered_total += $stats['answered'];
                    $parent_correct_total += $stats['correct'];
                    
                    // Get most recent session stats for this subcategory (3rd column)
                    $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_most_recent_answered_total += $most_recent['answered'];
                    $parent_most_recent_correct_total += $most_recent['correct'];
                    
                    // Get last 3 sessions stats for this subcategory (4th column)
                    $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                    $parent_last_3_answered_total += $last_3['answered'];
                    $parent_last_3_correct_total += $last_3['correct'];
                }
                
                // Calculate parent percentages
                $parent_percentage = $parent_answered_total > 0 ? round(($parent_correct_total / $parent_answered_total) * 100) : 0;
                $parent_most_recent_percentage = $parent_most_recent_answered_total > 0 ? round(($parent_most_recent_correct_total / $parent_most_recent_answered_total) * 100) : 0;
                $parent_last_3_percentage = $parent_last_3_answered_total > 0 ? round(($parent_last_3_correct_total / $parent_last_3_answered_total) * 100) : 0;
                
                // Determine percentage classes for parent
                $parent_percentage_class = 'low';
                if ($parent_percentage == 100) {
                    $parent_percentage_class = 'perfect';
                } elseif ($parent_percentage >= 80) {
                    $parent_percentage_class = 'high';
                } elseif ($parent_percentage >= 60) {
                    $parent_percentage_class = 'medium';
                }
                
                $parent_most_recent_percentage_class = 'low';
                if ($parent_most_recent_percentage == 100) {
                    $parent_most_recent_percentage_class = 'perfect';
                } elseif ($parent_most_recent_percentage >= 80) {
                    $parent_most_recent_percentage_class = 'high';
                } elseif ($parent_most_recent_percentage >= 60) {
                    $parent_most_recent_percentage_class = 'medium';
                }
                
                $parent_last_3_percentage_class = 'low';
                if ($parent_last_3_percentage == 100) {
                    $parent_last_3_percentage_class = 'perfect';
                } elseif ($parent_last_3_percentage >= 80) {
                    $parent_last_3_percentage_class = 'high';
                } elseif ($parent_last_3_percentage >= 60) {
                    $parent_last_3_percentage_class = 'medium';
                }
                
                $organized_categories[] = array(
                    'is_root' => false,
                    'parent_id' => 'parent_' . md5($parent_name),
                    'parentname' => $parent_name,
                    'parent_total' => $parent_total,
                    // 3rd column - Most Recent
                    'parent_most_recent_answered_total' => $parent_most_recent_answered_total,
                    'parent_most_recent_correct_total' => $parent_most_recent_correct_total,
                    'parent_most_recent_percentage' => $parent_most_recent_percentage,
                    'parent_most_recent_percentage_class' => $parent_most_recent_percentage_class,
                    // 4th column - Last 3 Attempts
                    'parent_last_3_answered_total' => $parent_last_3_answered_total,
                    'parent_last_3_correct_total' => $parent_last_3_correct_total,
                    'parent_last_3_percentage' => $parent_last_3_percentage,
                    'parent_last_3_percentage_class' => $parent_last_3_percentage_class,
                    // 5th column - Cumulative Score
                    'parent_answered_total' => $parent_answered_total,
                    'parent_correct_total' => $parent_correct_total,
                    'parent_percentage' => $parent_percentage,
                    'parent_percentage_class' => $parent_percentage_class,
                    'categories' => array_map(function($category) use ($category_stats, $most_recent_session_stats, $last_3_sessions_stats) {
                        // Get stats for this subcategory (cumulative - 5th column)
                        $stats = isset($category_stats[$category->questioncategoryid]) ? $category_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                        $percentage = $stats['answered'] > 0 ? round(($stats['correct'] / $stats['answered']) * 100) : 0;
                        
                        // Get most recent session stats (3rd column)
                        $most_recent = isset($most_recent_session_stats[$category->questioncategoryid]) ? $most_recent_session_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                        $most_recent_percentage = $most_recent['answered'] > 0 ? round(($most_recent['correct'] / $most_recent['answered']) * 100) : 0;
                        
                        // Get last 3 sessions stats (4th column)
                        $last_3 = isset($last_3_sessions_stats[$category->questioncategoryid]) ? $last_3_sessions_stats[$category->questioncategoryid] : array('answered' => 0, 'correct' => 0);
                        $last_3_percentage = $last_3['answered'] > 0 ? round(($last_3['correct'] / $last_3['answered']) * 100) : 0;
                        
                        // Determine percentage classes
                        $percentage_class = 'low';
                        if ($percentage == 100) {
                            $percentage_class = 'perfect';
                        } elseif ($percentage >= 80) {
                            $percentage_class = 'high';
                        } elseif ($percentage >= 60) {
                            $percentage_class = 'medium';
                        }
                        
                        $most_recent_percentage_class = 'low';
                        if ($most_recent_percentage == 100) {
                            $most_recent_percentage_class = 'perfect';
                        } elseif ($most_recent_percentage >= 80) {
                            $most_recent_percentage_class = 'high';
                        } elseif ($most_recent_percentage >= 60) {
                            $most_recent_percentage_class = 'medium';
                        }
                        
                        $last_3_percentage_class = 'low';
                        if ($last_3_percentage == 100) {
                            $last_3_percentage_class = 'perfect';
                        } elseif ($last_3_percentage >= 80) {
                            $last_3_percentage_class = 'high';
                        } elseif ($last_3_percentage >= 60) {
                            $last_3_percentage_class = 'medium';
                        }
                        
                        return array(
                            'categoryname' => $category->categoryname,
                            'item_count' => $category->item_count,
                            // 3rd column - Most Recent
                            'most_recent_answered' => $most_recent['answered'],
                            'most_recent_correct' => $most_recent['correct'],
                            'most_recent_percentage' => $most_recent_percentage,
                            'most_recent_percentage_class' => $most_recent_percentage_class,
                            // 4th column - Last 3 Attempts
                            'last_3_answered' => $last_3['answered'],
                            'last_3_correct' => $last_3['correct'],
                            'last_3_percentage' => $last_3_percentage,
                            'last_3_percentage_class' => $last_3_percentage_class,
                            // 5th column - Cumulative Score
                            'answered_count' => $stats['answered'],
                            'correct_count' => $stats['correct'],
                            'percentage' => $percentage,
                            'percentage_class' => $percentage_class
                        );
                    }, $categories)
                );
            }
        }
    }
    
    return $organized_categories;
}

// Prepare template data
$template_data = array(
    'coursename' => htmlspecialchars($course->fullname),
    'username' => fullname($USER),
    'examid' => $examid,
    'id' => $id,
    'total_questions' => $total_questions,
    'answered_questions' => $answered_questions,
    'correct_answers' => $correct_answers,
    'incorrect_answers' => $incorrect_answers,
    'marked_questions' => $marked_questions,
    'unanswered_questions' => $unanswered_questions,
    'score_percentage' => $score_percentage,
    'answered_score_percentage' => $answered_score_percentage,
    'total_time_formatted' => $total_time_formatted,
    'time_per_question_formatted' => $time_per_question_formatted,
    'date_time_range' => $date_time_range,
    'incorrect_plus_marked_count' => $missed_or_marked_count,
    'has_incorrect_or_marked' => $has_incorrect_or_marked,
    'has_incorrect' => $has_incorrect,
    'questions' => array(),
    // Language strings
    'performance_analysis_title' => get_string('performance_analysis_title', 'revitimsession'),
    'grade_report_title' => get_string('grade_report_title', 'revitimsession'),
    'session_type_text' => $session_type_text,
    'perform_url' => $perform_url,
    'is_study_session' => $is_study_session,
    'quiz_duration_text' => get_string('quiz_duration_text', 'revitimsession'),
    'per_question_text' => get_string('per_question_text', 'revitimsession'),
    'mins_text' => get_string('mins_text', 'revitimsession'),
    'score_total_questions_text' => get_string('score_total_questions_text', 'revitimsession'),
    'score_answered_questions_text' => get_string('score_answered_questions_text', 'revitimsession'),
    'questions_seen_text' => get_string('questions_seen_text', 'revitimsession'),
    'questions_answered_text' => get_string('questions_answered_text', 'revitimsession'),
    'questions_incorrect_text' => get_string('questions_incorrect_text', 'revitimsession'),
    'study_score_caption' => get_string('study_score_caption', 'revitimsession'),
    'view_review_session_text' => get_string('view_review_session_text', 'revitimsession'),
    'skip_start_new_quiz_text' => get_string('skip_start_new_quiz_text', 'revitimsession'),
    'create_new_session_text' => get_string('create_new_session_text', 'revitimsession'),
    'missed_or_marked_text' => get_string('missed_or_marked_text', 'revitimsession'),
    'questions_text' => get_string('questions_text', 'revitimsession'),
    'or_create_other_quizzes_text' => get_string('or_create_other_quizzes_text', 'revitimsession'),
    'total_questions_session_text' => get_string('total_questions_session_text', 'revitimsession'),
    'questions_correct_text' => get_string('questions_correct_text', 'revitimsession'),
    'questions_not_answered_text' => get_string('questions_not_answered_text', 'revitimsession'),
    'questions_marked_text' => get_string('questions_marked_text', 'revitimsession'),
    'expand_other_quizzes_text' => get_string('expand_other_quizzes_text', 'revitimsession'),
    'detailed_breakdown_title' => get_string('detailed_breakdown_title', 'revitimsession'),
    'question_number_text' => get_string('question_number_text', 'revitimsession'),
    'question_text' => get_string('question_text', 'revitimsession'),
    'status_text' => get_string('status_text', 'revitimsession'),
    'result_text' => get_string('result_text', 'revitimsession'),
    'correct_text' => get_string('correct_text', 'revitimsession'),
    'incorrect_text' => get_string('incorrect_text', 'revitimsession'),
    'unanswered_text' => get_string('unanswered_text', 'revitimsession'),
    'marked_text' => get_string('marked_text', 'revitimsession'),
    // Tab navigation
    'grade_report_tab_text' => get_string('grade_report_tab_text', 'revitimsession'),
    'study_session_tab_text' => get_string('study_session_tab_text', 'revitimsession'),
    'exam_session_tab_text' => get_string('exam_session_tab_text', 'revitimsession'),
    'history_tab_text' => get_string('history_tab_text', 'revitimsession'),
    // Tab titles and placeholders
    'study_session_title' => get_string('study_session_title', 'revitimsession'),
    'exam_session_title' => get_string('exam_session_title', 'revitimsession'),
    'history_title' => get_string('history_title', 'revitimsession'),
    'study_session_placeholder_text' => get_string('study_session_placeholder_text', 'revitimsession'),
    'exam_session_placeholder_text' => get_string('exam_session_placeholder_text', 'revitimsession'),
    'history_placeholder_text' => get_string('history_placeholder_text', 'revitimsession'),
    // Study Session Tab strings
    'learning_progress' => get_string('learning_progress', 'revitimsession'),
    'questions_mastered' => get_string('questions_mastered', 'revitimsession'),
    'questions_for_review' => get_string('questions_for_review', 'revitimsession'),
    'needs_practice' => get_string('needs_practice', 'revitimsession'),
    'study_recommendations' => get_string('study_recommendations', 'revitimsession'),
    'focus_on_questions' => get_string('focus_on_questions', 'revitimsession', $missed_or_marked_count),
    'study_problem_areas' => get_string('study_problem_areas', 'revitimsession'),
    'great_job_mastered' => get_string('great_job_mastered', 'revitimsession'),
    'start_new_session' => get_string('start_new_session', 'revitimsession'),
    'study_session_analysis_placeholder' => get_string('study_session_analysis_placeholder', 'revitimsession'),
    'create_custom_quiz' => get_string('create_custom_quiz', 'revitimsession'),
    'table_help' => get_string('table_help', 'revitimsession'),
    'test_bank_home_screen_text' => get_string('test_bank_home_screen_text', 'revitimsession'),
    'view_study_session_grade_report' => get_string('view_study_session_grade_report', 'revitimsession'),
    'view_grade_report_text' => $view_grade_report_text,
    'alternative_examid' => $alternative_examid,
    // Exam Session Tab strings
    'final_score' => get_string('final_score', 'revitimsession'),
    'questions_answered' => get_string('questions_answered', 'revitimsession'),
    'time_taken' => get_string('time_taken', 'revitimsession'),
    'exam_performance_breakdown' => get_string('exam_performance_breakdown', 'revitimsession'),
    'correct' => get_string('correct', 'revitimsession'),
    'incorrect' => get_string('incorrect', 'revitimsession'),
    'unanswered' => get_string('unanswered', 'revitimsession'),
    'avg_time_per_question' => get_string('avg_time_per_question', 'revitimsession'),
    'exam_session_analysis_placeholder' => get_string('exam_session_analysis_placeholder', 'revitimsession'),
    // History Tab strings
    'session_history' => get_string('session_history', 'revitimsession'),
    'filters' => get_string('filters', 'revitimsession'),
    'show_only_last_3' => get_string('show_only_last_3', 'revitimsession'),
    'view_only_study_sessions' => get_string('view_only_study_sessions', 'revitimsession'),
    'view_only_practice_exams' => get_string('view_only_practice_exams', 'revitimsession'),
    'session_type' => get_string('session_type', 'revitimsession'),
    'related_study_units' => get_string('related_study_units', 'revitimsession'),
    'completion_date' => get_string('completion_date', 'revitimsession'),
    'total_qs' => get_string('total_qs', 'revitimsession'),
    'avg_time_per_question_header' => get_string('avg_time_per_question_header', 'revitimsession'),
    'time_of_session' => get_string('time_of_session', 'revitimsession'),
    'questions_answered_header' => get_string('questions_answered_header', 'revitimsession'),
    'questions_correct_header' => get_string('questions_correct_header', 'revitimsession'),
    'percent_correct' => get_string('percent_correct', 'revitimsession'),
    'review' => get_string('review', 'revitimsession'),
    'no_completed_sessions_found' => get_string('no_completed_sessions_found', 'revitimsession'),
    // Detailed Quiz Breakdown Table Headers
    'detailed_breakdown_category' => get_string('detailed_breakdown_category', 'revitimsession'),
    'detailed_breakdown_questions_available' => get_string('detailed_breakdown_questions_available', 'revitimsession'),
    'detailed_breakdown_most_recent' => get_string('detailed_breakdown_most_recent', 'revitimsession') . ($most_recent_date ? ' ' . $most_recent_date : ''),
    'detailed_breakdown_last_3_attempts' => get_string('detailed_breakdown_last_3_attempts', 'revitimsession') . ($last_3_dates_text ? ' ' . $last_3_dates_text : ''),
    'detailed_breakdown_cumulative_score' => get_string('detailed_breakdown_cumulative_score', 'revitimsession') . ($date_range_text ? ' ' . $date_range_text : ''),
    // JavaScript data
    'score_percentage' => $score_percentage,
    
    // Study Session hierarchical data
    'study_categories' => generateSessionData(1, $DB, $course->id),
    
    // Exam Session hierarchical data (filtered by studysession=0)
    'exam_categories' => generateSessionData(0, $DB, $course->id),
    
    // Grade Report category breakdown data
    'organized_categories' => generateGradeReportData($DB, $course->id)
);

// Process questions for template
foreach ($questions as $question) {
    $question_data = array(
        'questionorder' => $question->questionorder,
        'questiontext' => format_text($question->questiontext, $question->questiontextformat),
        'is_correct' => $question->correct == 1 || $question->correct == 2,
        'is_answered' => $question->answer !== null,
        'is_marked' => $question->markedforreview == 1,
        'status_class' => ($question->correct == 1 || $question->correct == 2) ? 'correct' : ($question->answer !== null ? 'incorrect' : 'unanswered')
    );
    
    $template_data['questions'][] = $question_data;
}

// Get historical data for this user and course - WITH QUESTION COUNTS AND TIME
$historical_exams = $DB->get_records_sql(
    "SELECT pe.id, pe.studysession, pe.studyunit, pe.timefinished, pe.timecreated, pe.timeremaining, pe.totalquestions,
            COUNT(peq.id) as total_questions,
            SUM(CASE WHEN peq.answer IS NOT NULL THEN 1 ELSE 0 END) as answered_questions,
            SUM(CASE WHEN peq.correct IN (1, 2) THEN 1 ELSE 0 END) as correct_questions
     FROM {revitimsession_practice_exams} pe
     LEFT JOIN {revitimsession_practice_exam_questions} peq ON pe.id = peq.practiceexamid
     WHERE pe.userid = :userid AND pe.courseid = :courseid AND pe.status = 1 AND pe.timefinished IS NOT NULL
     GROUP BY pe.id, pe.studysession, pe.studyunit, pe.timefinished, pe.timecreated, pe.timeremaining, pe.totalquestions
     ORDER BY pe.timefinished ASC",
    array('userid' => $USER->id, 'courseid' => $course->id)
);

// Process historical data - WITH QUESTION COUNTS AND PERCENTAGE
$history_data = array();
foreach ($historical_exams as $exam) {
    // Calculate percentage based on answered questions
    $percent_correct = 0;
    if ($exam->answered_questions > 0) {
        $percent_correct = round(($exam->correct_questions / $exam->answered_questions) * 100);
    }
    
    // Calculate time taken for the session
    $time_taken_seconds = 0;
    if ($exam->timeremaining !== null && $exam->totalquestions > 0) {
        $total_allotted_time = $exam->totalquestions * 60; // 1 minute per question
        if ($exam->timeremaining >= 0) {
            // User finished before time expired
            $time_taken_seconds = $total_allotted_time - $exam->timeremaining;
        } else {
            // Time expired, user went over time
            $time_taken_seconds = $total_allotted_time + abs($exam->timeremaining);
        }
    }
    $time_taken_seconds = max(0, $time_taken_seconds);
    $session_time_formatted = gmdate("H:i:s", $time_taken_seconds);
    
    // Calculate average time per question
    $avg_time_per_question = 0;
    if ($exam->answered_questions > 0 && $time_taken_seconds > 0) {
        $avg_time_per_question = $time_taken_seconds / $exam->answered_questions;
    }
    $avg_time_formatted = gmdate("H:i:s", max(0, $avg_time_per_question));
    
    // Determine percentage indicator class for visual representation
    $percentage_class = 'low'; // red
    if ($percent_correct == 100) {
        $percentage_class = 'perfect'; // blue for 100%
    } elseif ($percent_correct >= 80) {
        $percentage_class = 'high'; // green for high scores
    } elseif ($percent_correct >= 60) {
        $percentage_class = 'medium'; // yellow for medium scores
    }
    
    $history_data[] = array(
        'exam_id' => $exam->id,
        'exam_type' => $exam->studysession == 1 ? get_string('study_session', 'revitimsession') : get_string('practice_exam', 'revitimsession'),
        'is_study_session' => $exam->studysession == 1,
        'completion_date' => userdate($exam->timefinished, '%m/%d/%Y'),
        'studyunit' => !empty($exam->studyunit) ? $exam->studyunit : '-',
        'total_questions' => $exam->total_questions,
        'answered_questions' => $exam->answered_questions,
        'correct_questions' => $exam->correct_questions,
        'avg_time_per_question' => $avg_time_formatted,
        'session_time' => $session_time_formatted,
        'percent_correct' => $percent_correct,
        'percentage_class' => $percentage_class,
        'perform_url' => $exam->studysession == 1 ? 'perform_study.php' : 'perform_exam.php'
    );
}

$template_data['history_data'] = $history_data;
$template_data['has_history'] = !empty($history_data);

// Render template
echo $OUTPUT->render_from_template('mod_revitimsession/stats', $template_data);

echo $OUTPUT->footer();
?>
