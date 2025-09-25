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
 * Event observers for the revitimsession module
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_revitimsession;

defined('MOODLE_INTERNAL') || die();

/**
 * Observer class for handling course events
 */
class observer {
    
    /**
     * Handle course created event
     * 
     * @param \core\event\course_created $event The course created event
     * @return void
     */
    public static function course_created(\core\event\course_created $event) {
        global $DB, $CFG, $OUTPUT;
        
        // Include required files
        require_once(__DIR__ . '/../lib.php');
        
        $courseid = $event->objectid;
        
        // Get the revitimsession module record   
        if (! $module = $DB->get_record("modules", array("name" => "revitimsession"))) {
            echo $OUTPUT->notification("Could not find revitimsession module!!");
            return false;
        }

        // Create the instance data directly in database to avoid session issues
        $instancedata = new \stdClass();
        $instancedata->course = $courseid;
        $instancedata->name = get_string('default_resource_name', 'revitimsession');
        $instancedata->intro = "";
        $instancedata->introformat = 1;
        $instancedata->starttime = 0;
        $instancedata->endtime = 0;
        $instancedata->allowguest = 0;
        $instancedata->grade=0;

        $instanceid = revitimsession_add_instance($instancedata, null);

        // Insert the course module record
        $mod = new \stdClass();
        $mod->course = $courseid;
        $mod->module = $module->id;
        $mod->instance = $instanceid;
        $mod->section = 0;
        
        // Include course lib for add_course_module and course_add_cm_to_section functions
        include_once("$CFG->dirroot/course/lib.php");
        
        if (! $mod->coursemodule = add_course_module($mod) ) {
            echo $OUTPUT->notification("Could not add a new course module to the course '" . $courseid . "'");
            return false;
        }

        // Add the course module to the course section
        course_add_cm_to_section($courseid, $mod->coursemodule, 0);
    }
}
