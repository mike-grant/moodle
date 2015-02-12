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
 * Display soft deleted course items
 *
 * @package    core_course
 * @copyright  2015 Michael Grant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../config.php');
require_once($CFG->dirroot.'/course/lib.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
$PAGE->set_pagelayout('admin');
require_course_login($course, true);

$strresources    = 'Recycle Bin'; // TODO: Change this

$PAGE->set_url('/course/recycle.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strresources);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strresources);

echo $OUTPUT->header();

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

$table->head  = array ('Name', 'Date Deleted', 'Availble until', '');
$table->align = array ('center', 'left', 'left', 'center');

$deleted = get_soft_deleted_modules($course->id);

foreach ($deleted as $module) {
    
    $row = array();
            
    $modname = $module->modname;
    $functionname = $modname."_get_coursemodule_info";

    if (!file_exists("$CFG->dirroot/mod/$modname/lib.php")) {
        continue;
    }

    include_once("$CFG->dirroot/mod/$modname/lib.php");

    $name = '';
    
    if ($hasfunction = function_exists($functionname)) {
        if ($info = $functionname($module)) {
            $name = format_string($info->name);
        }
    }
    
    $row[] = $name;
    
    $actions = array();
    
    $actions[] = $OUTPUT->action_icon(
        new moodle_url('/course/mod.php', array('restore' => $module->id, 'sesskey' => sesskey())),
        new pix_icon('t/restore', get_string('restore')),
        null,
        array('class' => 'action-restore')
    );
    
    $actions[] = $OUTPUT->action_icon(
        new moodle_url('/course/mod.php', array('delete' => $module->id, 'sesskey' => sesskey())),
        new pix_icon('t/delete', get_string('delete')),
        null,
        array('class' => 'action-delete')
    );

    $row[] = userdate($module->deleted);
    $row[] = userdate($module->deleted+(60*60*24*30));
    $row[] = html_writer::span(join('', $actions), 'course-item-actions item-actions');
    $table->data[] = $row;    
}

echo html_writer::table($table);

echo $OUTPUT->footer();