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
 * Library of functions and constants for module revitimsession
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a new revitimsession instance
 *
 * @param stdClass $instancedata An object from the form in mod_form.php
 * @param mod_revitimsession_mod_form $mform
 * @return int The id of the newly inserted revitimsession record
 */
function revitimsession_add_instance($instancedata, $mform = null): int {
    global $DB;

    $instancedata->timecreated = time();
    $instancedata->timemodified = time();

    // Insert the record into the database
    $id = $DB->insert_record('revitimsession', $instancedata);

    // Trigger module_created event


    return $id;
}

/**
 * Update a revitimsession instance
 *
 * @param stdClass $instancedata An object from the form in mod_form.php
 * @param mod_revitimsession_mod_form $mform
 * @return bool Success/Failure
 */
function revitimsession_update_instance($instancedata, $mform): bool {
    global $DB;

    $instancedata->timemodified = time();
    $instancedata->id = $instancedata->instance;

    // Update the record in the database
    $result = $DB->update_record('revitimsession', $instancedata);

    if ($result) {
        // Trigger module_updated event

    }

    return $result;
}

/**
 * Delete a revitimsession instance
 *
 * @param int $id Id of the module instance
 * @return bool Success/Failure
 */
function revitimsession_delete_instance($id): bool {
    global $DB;

    // Get the instance data before deletion for the event
    $instance = $DB->get_record('revitimsession', ['id' => $id], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('revitimsession', $id, 0, false, MUST_EXIST);

    // Delete related records first (if any)
    // Delete submissions
    $DB->delete_records('revitimsession_submissions', ['sessionid' => $id]);
    
    // Delete participants
    $DB->delete_records('revitimsession_participants', ['sessionid' => $id]);
    
    // Delete grades
    $DB->delete_records('revitimsession_grades', ['sessionid' => $id]);

    // Delete the main instance record
    $result = $DB->delete_records('revitimsession', ['id' => $id]);

    if ($result) {
        // Trigger module_deleted event

    }

    return $result;
}

/**
 * Get the latest question IDs for a list of question bank entries.
 *
 * @param array $entryids List of question_bank_entries.id
 * @return array question.id values
 */
function get_latest_questionids_for_entries(array $entryids): array {
    global $DB;

    if (empty($entryids)) {
        return [];
    }

    list($insql, $params) = $DB->get_in_or_equal($entryids, SQL_PARAMS_NAMED);

    $sql = "SELECT qv.questionid
              FROM {question_versions} qv
              JOIN (
                   SELECT questionbankentryid, MAX(version) AS maxversion
                     FROM {question_versions}
                    WHERE questionbankentryid $insql
                 GROUP BY questionbankentryid
              ) latest
                ON latest.questionbankentryid = qv.questionbankentryid
               AND latest.maxversion = qv.version";

    return $DB->get_fieldset_sql($sql, $params);
}
