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
 * Lists all instances of revitimsession in a given course
 *
 * @package     mod_revitimsession
 * @copyright   2025 Marcelo A. R. Schmitt <marcelo.rauh@gmail.com> lern.link
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');



$strrevitimsession = get_string('modulename', 'revitimsession');
$strrevitimsessions = get_string('modulenameplural', 'revitimsession');
$strsectionname = get_string('sectionname', 'format_'.$course->format);
$strname = get_string('name');
$strintro = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/revitimsession/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strrevitimsessions);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strrevitimsessions);
echo $OUTPUT->header();

if (!$revitimsessions = get_all_instances_in_course('revitimsession', $course)) {
    notice(get_string('thereareno', 'moodle', $strrevitimsessions), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head = array($strsectionname, $strname, $strintro);
    $table->align = array('center', 'left', 'left');
} else {
    $table->head = array($strlastmodified, $strname, $strintro);
    $table->align = array('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';

foreach ($revitimsessions as $revitimsession) {
    $cm = $modinfo->cms[$revitimsession->coursemodule];
    
    if ($usesections) {
        $printsection = '';
        if ($revitimsession->section !== $currentsection) {
            if ($revitimsession->section) {
                $printsection = get_section_name($course, $revitimsession->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $revitimsession->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($revitimsession->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $icon = $OUTPUT->pix_icon($cm->icon, get_string('modulename', $cm->modname)) . ' ';
    }

    $class = $revitimsession->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.
    
    $table->data[] = array(
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($revitimsession->name)."</a>",
        format_module_intro('revitimsession', $revitimsession, $cm->id)
    );
}

echo html_writer::table($table);

echo $OUTPUT->footer();
