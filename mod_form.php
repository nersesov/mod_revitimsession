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
 * Form for editing revitimsession instances
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_revitimsession_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'name', 'revitimsession');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the "timing" fieldset.
        $mform->addElement('header', 'timing', get_string('timing', 'revitimsession'));

        // Start time.
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'revitimsession'), array('optional' => true));
        $mform->addHelpButton('starttime', 'starttime', 'revitimsession');

        // End time.
        $mform->addElement('date_time_selector', 'endtime', get_string('endtime', 'revitimsession'), array('optional' => true));
        $mform->addHelpButton('endtime', 'endtime', 'revitimsession');

        // Adding the "access" fieldset.
        $mform->addElement('header', 'access', get_string('access', 'revitimsession'));

        // Allow guest access.
        $mform->addElement('advcheckbox', 'allowguest', get_string('allowguest', 'revitimsession'));
        $mform->addHelpButton('allowguest', 'allowguest', 'revitimsession');

        // Adding the "grading" fieldset.
        $mform->addElement('header', 'grading', get_string('grading', 'revitimsession'));

        // Grade.
        $mform->addElement('text', 'grade', get_string('grade', 'revitimsession'), array('size' => 10));
        $mform->setType('grade', PARAM_FLOAT);
        $mform->addHelpButton('grade', 'grade', 'revitimsession');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate start time is before end time.
        if (!empty($data['starttime']) && !empty($data['endtime'])) {
            if ($data['starttime'] >= $data['endtime']) {
                $errors['endtime'] = get_string('error:starttimeafterendtime', 'revitimsession');
            }
        }



        // Validate grade.
        if (!empty($data['grade'])) {
            if ($data['grade'] < 0) {
                $errors['grade'] = get_string('error:gradetoolow', 'revitimsession');
            }
        }

        return $errors;
    }
}
