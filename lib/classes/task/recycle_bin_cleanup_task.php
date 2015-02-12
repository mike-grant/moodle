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
 * A scheduled task.
 *
 * @package    core
 * @copyright  2015 Michael Grant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Simple task to run the backup cron.
 */
class recycle_bin_cleanup_task extends scheduled_task {
    
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return 'Recycle Bin';
        //return get_string('taskrecyclebackup', 'admin');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        $deletefrom = time();//-(60*60*24);
        
        if ($delete = $DB->get_records_select("course_modules", "deleted < ? AND deleted > 0", array($deletefrom))) {
            mtrace("Emptying recycle bins",'...');
            foreach($delete as $mod) {
                mtrace('Deleting course module '.$mod->id);
                course_delete_module($mod->id);
            }
            mtrace('Emptied recycle bins.');
        }
    }    
}