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
 * This file contains the definition for the library class for file submission plugin
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/eventslib.php');

defined('MOODLE_INTERNAL') || die();

// File areas for file submission assignment.
define('ASSIGNSUBMISSION_FILE_MAXFILES', 20);
define('ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES', 5);
define('ASSIGNSUBMISSION_FILE_FILEAREA', 'submission_files');

/**
 * Library class for file submission plugin extending submission plugin base class
 *
 * @package   assignsubmission_file
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_submission_file extends assign_submission_plugin {

    /**
     * Get the name of the file submission plugin
     * @return string
     */
    public function get_name() {
        return get_string('file', 'assignsubmission_file');
    }

    /**
     * Get file submission information from the database
     *
     * @param int $submissionid
     * @return mixed
     */
    private function get_file_submission($submissionid) {
        global $DB;
        return $DB->get_record('assignsubmission_file', array('submission'=>$submissionid));
    }

    /**
     * Get the default setting for file submission plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {
        global $CFG, $COURSE;

        $defaultmaxfilesubmissions = $this->get_config('maxfilesubmissions');
        $defaultmaxsubmissionsizebytes = $this->get_config('maxsubmissionsizebytes');
        $defaultrestricttypes = $this->get_config('restricttypes');

        $settings = array();
        $options = array();
        for ($i = 1; $i <= ASSIGNSUBMISSION_FILE_MAXFILES; $i++) {
            $options[$i] = $i;
        }

        $name = get_string('maxfilessubmission', 'assignsubmission_file');
        $mform->addElement('select', 'assignsubmission_file_maxfiles', $name, $options);
        $mform->addHelpButton('assignsubmission_file_maxfiles',
                              'maxfilessubmission',
                              'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_maxfiles', $defaultmaxfilesubmissions);
        $mform->disabledIf('assignsubmission_file_maxfiles', 'assignsubmission_file_enabled', 'notchecked');

        $choices = get_max_upload_sizes($CFG->maxbytes,
                                        $COURSE->maxbytes,
                                        get_config('assignsubmission_file', 'maxbytes'));

        $settings[] = array('type' => 'select',
                            'name' => 'maxsubmissionsizebytes',
                            'description' => get_string('maximumsubmissionsize', 'assignsubmission_file'),
                            'options'=> $choices,
                            'default'=> $defaultmaxsubmissionsizebytes);

        $name = get_string('maximumsubmissionsize', 'assignsubmission_file');
        $mform->addElement('select', 'assignsubmission_file_maxsizebytes', $name, $choices);
        $mform->addHelpButton('assignsubmission_file_maxsizebytes',
                              'maximumsubmissionsize',
                              'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_maxsizebytes', $defaultmaxsubmissionsizebytes);
        $mform->disabledIf('assignsubmission_file_maxsizebytes',
                           'assignsubmission_file_enabled',
                           'notchecked');
        
        $name = get_string('restricttypes', 'assignsubmission_file');
        $mform->addElement('selectyesno', 'assignsubmission_file_restricttypes', $name);
        $mform->addHelpButton('assignsubmission_file_restricttypes', 'restricttypes', 'assignsubmission_file');
        $mform->setDefault('assignsubmission_file_restricttypes', $defaultrestricttypes);
        $mform->disabledIf('assignsubmission_file_restricttypes', 'assignsubmission_file_enabled', 'notchecked');

        $groupels = array();
        foreach ($this->get_all_typesets() as $k => $v) {
            $elem = $mform->createElement('checkbox', $k, '', $v);
            $groupels[] = $elem;
        }
        $name = get_string('acceptedfiletypes', 'assignsubmission_file');
        $mform->addGroup($groupels, 'assignsubmission_file_filetypes', $name, '<br/>');
        $mform->disabledIf('assignsubmission_file_filetypes', 'assignsubmission_file_restricttypes', 'eq', '0');
        $mform->disabledIf('assignsubmission_file_filetypes', 'assignsubmission_file_enabled', 'notchecked');

        $name = get_string('filetypesother', 'assignsubmission_file');
        $mform->addElement('text', 'assignsubmission_file_filetypesother', $name, 'size="30"');
        $mform->setType('assignsubmission_file_filetypesother', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('assignsubmission_file_filetypesother', 'filetypesother', 'assignsubmission_file');
        $mform->disabledIf('assignsubmission_file_filetypesother', 'assignsubmission_file_restricttypes', 'eq', '0');
        $mform->disabledIf('assignsubmission_file_filetypesother', 'assignsubmission_file_enabled', 'notchecked');
    }

    /**
     * Save the settings for file submission plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        $this->set_config('maxfilesubmissions', $data->assignsubmission_file_maxfiles);
        $this->set_config('maxsubmissionsizebytes', $data->assignsubmission_file_maxsizebytes);
        $this->set_config('restricttypes', $data->assignsubmission_file_restricttypes);

        $filetypeslist = array();
        if (isset($data->assignsubmission_file_filetypes) &&
                is_array($data->assignsubmission_file_filetypes)) {
            $filetypeslist = array_keys($data->assignsubmission_file_filetypes, 1);
        }
        if ($data->assignsubmission_file_filetypesother !== '') {
            $filetypeslist[] = $this->normalise_filetypelist($data->assignsubmission_file_filetypesother);
        }
        $this->set_config('filetypeslist', implode(';', $filetypeslist));

        return true;
    }

    /**
     * File format options
     *
     * @return array
     */
    private function get_file_options() {
        $fileoptions = array('subdirs'=>1,
                                'maxbytes'=>$this->get_config('maxsubmissionsizebytes'),
                                'maxfiles'=>$this->get_config('maxfilesubmissions'),
                                'accepted_types'=>$this->get_accepted_types(),
                                'return_types'=>FILE_INTERNAL);
        if ($fileoptions['maxbytes'] == 0) {
            // Use module default.
            $fileoptions['maxbytes'] = get_config('assignsubmission_file', 'maxbytes');
        }
        return $fileoptions;
    }

    /**
     * Add elements to submission form
     *
     * @param mixed $submission stdClass|null
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool
     */
    public function get_form_elements($submission, MoodleQuickForm $mform, stdClass $data) {

        if ($this->get_config('maxfilesubmissions') <= 0) {
            return false;
        }

        $fileoptions = $this->get_file_options();
        $submissionid = $submission ? $submission->id : 0;

        $data = file_prepare_standard_filemanager($data,
                                                  'files',
                                                  $fileoptions,
                                                  $this->assignment->get_context(),
                                                  'assignsubmission_file',
                                                  ASSIGNSUBMISSION_FILE_FILEAREA,
                                                  $submissionid);
        $mform->addElement('filemanager', 'files_filemanager', $this->get_name(), null, $fileoptions);
        
        if ($this->get_config('restricttypes')) {
            $text = html_writer::tag('p', get_string('filesofthesetypes', 'assignsubmission_file'));
            $text .= html_writer::start_tag('ul');
            $typesets = $this->get_configured_typesets();
            foreach ($typesets['sets'] as $description) {
                $text .= html_writer::tag('li', s($description));
            }
            if ($typesets['other']) {
                $text .= html_writer::tag('li', get_string('filetypesotherlist', 'assignsubmission_file',
                        str_replace(',', ', ', $typesets['other'])));
            }
            $text .= html_writer::end_tag('ul');
            $mform->addElement('static', '', '', $text);
        }
        
        return true;
    }

    /**
     * Count the number of files
     *
     * @param int $submissionid
     * @param string $area
     * @return int
     */
    private function count_files($submissionid, $area) {

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     $area,
                                     $submissionid,
                                     'id',
                                     false);

        return count($files);
    }

    /**
     * Save the files and trigger plagiarism plugin, if enabled,
     * to scan the uploaded files via events trigger
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $submission, stdClass $data) {
        global $USER, $DB;

        $fileoptions = $this->get_file_options();

        $data = file_postupdate_standard_filemanager($data,
                                                     'files',
                                                     $fileoptions,
                                                     $this->assignment->get_context(),
                                                     'assignsubmission_file',
                                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                                     $submission->id);

        $filesubmission = $this->get_file_submission($submission->id);

        // Plagiarism code event trigger when files are uploaded.

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'id',
                                     false);

        $count = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        $params = array(
            'context' => context_module::instance($this->assignment->get_course_module()->id),
            'courseid' => $this->assignment->get_course()->id,
            'objectid' => $submission->id,
            'other' => array(
                'content' => '',
                'pathnamehashes' => array_keys($files)
            )
        );
        if (!empty($submission->userid) && ($submission->userid != $USER->id)) {
            $params['relateduserid'] = $submission->userid;
        }
        $event = \assignsubmission_file\event\assessable_uploaded::create($params);
        $event->set_legacy_files($files);
        $event->trigger();

        $groupname = null;
        $groupid = 0;
        // Get the group name as other fields are not transcribed in the logs and this information is important.
        if (empty($submission->userid) && !empty($submission->groupid)) {
            $groupname = $DB->get_field('groups', 'name', array('id' => $submission->groupid), '*', MUST_EXIST);
            $groupid = $submission->groupid;
        } else {
            $params['relateduserid'] = $submission->userid;
        }

        // Unset the objectid and other field from params for use in submission events.
        unset($params['objectid']);
        unset($params['other']);
        $params['other'] = array(
            'submissionid' => $submission->id,
            'submissionattempt' => $submission->attemptnumber,
            'submissionstatus' => $submission->status,
            'filesubmissioncount' => $count,
            'groupid' => $groupid,
            'groupname' => $groupname
        );

        if ($filesubmission) {
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           ASSIGNSUBMISSION_FILE_FILEAREA);
            $updatestatus = $DB->update_record('assignsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignsubmission_file\event\submission_updated::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $updatestatus;
        } else {
            $filesubmission = new stdClass();
            $filesubmission->numfiles = $this->count_files($submission->id,
                                                           ASSIGNSUBMISSION_FILE_FILEAREA);
            $filesubmission->submission = $submission->id;
            $filesubmission->assignment = $this->assignment->get_instance()->id;
            $filesubmission->id = $DB->insert_record('assignsubmission_file', $filesubmission);
            $params['objectid'] = $filesubmission->id;

            $event = \assignsubmission_file\event\submission_created::create($params);
            $event->set_assign($this->assignment);
            $event->trigger();
            return $filesubmission->id > 0;
        }
    }

    /**
     * Produce a list of files suitable for export that represent this feedback or submission
     *
     * @param stdClass $submission The submission
     * @param stdClass $user The user record - unused
     * @return array - return an array of files indexed by filename
     */
    public function get_files(stdClass $submission, stdClass $user) {
        $result = array();
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->assignment->get_context()->id,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $submission->id,
                                     'timemodified',
                                     false);

        foreach ($files as $file) {
            $result[$file->get_filename()] = $file;
        }
        return $result;
    }

    /**
     * Display the list of files  in the submission status table
     *
     * @param stdClass $submission
     * @param bool $showviewlink Set this to true if the list of files is long
     * @return string
     */
    public function view_summary(stdClass $submission, & $showviewlink) {
        $count = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        // Show we show a link to view all files for this plugin?
        $showviewlink = $count > ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES;
        if ($count <= ASSIGNSUBMISSION_FILE_MAXSUMMARYFILES) {
            return $this->assignment->render_area_files('assignsubmission_file',
                                                        ASSIGNSUBMISSION_FILE_FILEAREA,
                                                        $submission->id);
        } else {
            return get_string('countfiles', 'assignsubmission_file', $count);
        }
    }

    /**
     * No full submission view - the summary contains the list of files and that is the whole submission
     *
     * @param stdClass $submission
     * @return string
     */
    public function view(stdClass $submission) {
        return $this->assignment->render_area_files('assignsubmission_file',
                                                    ASSIGNSUBMISSION_FILE_FILEAREA,
                                                    $submission->id);
    }



    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type
     * @param int $version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        $uploadsingletype ='uploadsingle';
        $uploadtype ='upload';

        if (($type == $uploadsingletype || $type == $uploadtype) && $version >= 2011112900) {
            return true;
        }
        return false;
    }


    /**
     * Upgrade the settings from the old assignment
     * to the new plugin based one
     *
     * @param context $oldcontext - the old assignment context
     * @param stdClass $oldassignment - the old assignment data record
     * @param string $log record log events here
     * @return bool Was it a success? (false will trigger rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {
        global $DB;

        if ($oldassignment->assignmenttype == 'uploadsingle') {
            $this->set_config('maxfilesubmissions', 1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);
            return true;
        } else if ($oldassignment->assignmenttype == 'upload') {
            $this->set_config('maxfilesubmissions', $oldassignment->var1);
            $this->set_config('maxsubmissionsizebytes', $oldassignment->maxbytes);

            // Advanced file upload uses a different setting to do the same thing.
            $DB->set_field('assign',
                           'submissiondrafts',
                           $oldassignment->var4,
                           array('id'=>$this->assignment->get_instance()->id));

            // Convert advanced file upload "hide description before due date" setting.
            $alwaysshow = 0;
            if (!$oldassignment->var3) {
                $alwaysshow = 1;
            }
            $DB->set_field('assign',
                           'alwaysshowdescription',
                           $alwaysshow,
                           array('id'=>$this->assignment->get_instance()->id));
            return true;
        }
    }

    /**
     * Upgrade the submission from the old assignment to the new one
     *
     * @param context $oldcontext The context of the old assignment
     * @param stdClass $oldassignment The data record for the old oldassignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $submission The data record for the new submission
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $submission,
                            & $log) {
        global $DB;

        $filesubmission = new stdClass();

        $filesubmission->numfiles = $oldsubmission->numfiles;
        $filesubmission->submission = $submission->id;
        $filesubmission->assignment = $this->assignment->get_instance()->id;

        if (!$DB->insert_record('assignsubmission_file', $filesubmission) > 0) {
            $log .= get_string('couldnotconvertsubmission', 'mod_assign', $submission->userid);
            return false;
        }

        // Now copy the area files.
        $this->assignment->copy_area_files_for_upgrade($oldcontext->id,
                                                        'mod_assignment',
                                                        'submission',
                                                        $oldsubmission->id,
                                                        $this->assignment->get_context()->id,
                                                        'assignsubmission_file',
                                                        ASSIGNSUBMISSION_FILE_FILEAREA,
                                                        $submission->id);

        return true;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('assignsubmission_file',
                            array('assignment'=>$this->assignment->get_instance()->id));

        return true;
    }

    /**
     * Formatting for log info
     *
     * @param stdClass $submission The submission
     * @return string
     */
    public function format_for_log(stdClass $submission) {
        // Format the info for each submission plugin (will be added to log).
        $filecount = $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA);

        return get_string('numfilesforlog', 'assignsubmission_file', $filecount);
    }

    /**
     * Return true if there are no submission files
     * @param stdClass $submission
     */
    public function is_empty(stdClass $submission) {
        return $this->count_files($submission->id, ASSIGNSUBMISSION_FILE_FILEAREA) == 0;
    }

    /**
     * Get file areas returns a list of areas this plugin stores files
     * @return array - An array of fileareas (keys) and descriptions (values)
     */
    public function get_file_areas() {
        return array(ASSIGNSUBMISSION_FILE_FILEAREA=>$this->get_name());
    }

    /**
     * Copy the student's submission from a previous submission. Used when a student opts to base their resubmission
     * on the last submission.
     * @param stdClass $sourcesubmission
     * @param stdClass $destsubmission
     */
    public function copy_submission(stdClass $sourcesubmission, stdClass $destsubmission) {
        global $DB;

        // Copy the files across.
        $contextid = $this->assignment->get_context()->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid,
                                     'assignsubmission_file',
                                     ASSIGNSUBMISSION_FILE_FILEAREA,
                                     $sourcesubmission->id,
                                     'id',
                                     false);
        foreach ($files as $file) {
            $fieldupdates = array('itemid' => $destsubmission->id);
            $fs->create_file_from_storedfile($fieldupdates, $file);
        }

        // Copy the assignsubmission_file record.
        if ($filesubmission = $this->get_file_submission($sourcesubmission->id)) {
            unset($filesubmission->id);
            $filesubmission->submission = $destsubmission->id;
            $DB->insert_record('assignsubmission_file', $filesubmission);
        }
        return true;
    }

    /**
     * Return a description of external params suitable for uploading a file submission from a webservice.
     *
     * @return external_description|null
     */
    public function get_external_parameters() {
        return array(
            'files_filemanager' => new external_value(
                PARAM_INT,
                'The id of a draft area containing files for this submission.'
            )
        );
    }
    

    /**
     * Set default values that demand a little extra effort.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        $typesconfig = $this->get_configured_typesets();

        $defaultvalues['assignsubmission_file_filetypes'] = array();
        foreach (array_keys($typesconfig['sets']) as $extensions) {
            $defaultvalues['assignsubmission_file_filetypes'][$extensions] = 1;
        }
        $defaultvalues['assignsubmission_file_filetypesother'] = str_replace(',', ', ', $typesconfig['other']);
    }


    /**
     * Load and parse the type sets configuration.
     *
     * @return array
     */
    private function get_all_typesets() {
        $typesets = array();
        $config = get_config('assignsubmission_file', 'filetypes');
        if ($config) {
            foreach (explode("\n", $config) as $line) {
                list ($extensions, $description) = explode(';', $line, 2);
                $typesets[$extensions] = $description;
            }
        } else {
            $helper = new assignsubmission_file\filetypes_helper();
            foreach ($helper->default_types() as $type) {
                $typesets[$type->extensions] = $type->description;
            }
        }
        return $typesets;
    }

    /**
     * Get the type sets configured for this assignment.
     *
     * @return array('sets' => array(extensions => description), 'other' => extensions)
     */
    private function get_configured_typesets() {
        $typeslist = (string)$this->get_config('filetypeslist');
        $typesets = $this->get_all_typesets();

        $sets = array();
        $other = '';

        if ($typeslist !== '') {
            $other = array();
            foreach (explode(';', $typeslist) as $type) {
                if (isset($typesets[$type])) {
                    $sets[$type] = $typesets[$type];
                } else {
                    $other = array_merge($other, explode(',', $type));
                }
            }
            $other = implode(',', array_unique($other));
        }

        return compact('sets', 'other');
    }


    /**
     * Sanitise user input of file types into a consistent internal format.
     *
     * @param string $typestring a list of file types.
     * @return string a normalised type string.
     */
    private function normalise_filetypelist($typestring) {
        $types = preg_split('/[,;\s]+/', $typestring);
        natsort($types);
        $types = array_map(function ($t) { return ltrim($t, '*.'); }, $types);
        return implode(',', $types);
    }

    /**
     * Return the accepted types list for the file manager component.
     *
     * @return array
     */
    private function get_accepted_types() {
        $accepted_types = '*';
        $restricttypes = $this->get_config('restricttypes');
        $types = (string)$this->get_config('filetypeslist');

        if ($restricttypes && $types !== '') {
            $types = explode(',', strtr($types, ';', ','));
            $types = array_map(function ($a) { return ".$a"; }, $types);
            $accepted_types = $types;
        }

        return $accepted_types;
    }
}
