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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_videoredirect
 * @copyright   2019, Creatic SAS <soporte@creatic.co>.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoredirect;
defined('MOODLE_INTERNAL') || die;

use coding_exception;
use context_module;
use context_user;
use dml_exception;
use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;
use invalid_parameter_exception;
use moodle_exception;
use moodle_url;
use required_capability_exception;
use restricted_context_exception;
use stdClass;
use completion_info;

class external extends external_api {

    /**
     * Marks video with redirection activity as completed.
     *
     * @param %cmid Course module id.
     * @return array
     */
    public static function activity_completion($cmid) {
        global $USER;
        $warnings = [];
        $debug = '';

        $params = external_api::validate_parameters(self::activity_completion_parameters(), [
            'cm' => $cmid
        ]);

        // We get course and course module data for completion info.
        list($course, $cm) = get_course_and_cm_from_cmid($cmid);

        $completion = new completion_info($course);
        $completiondata = $completion->get_data($cm, false, $USER->id);


        // When an automatic completion tracking with view required is enabled,
        // and course module completion state is incomplete, it changes the
        // value to complete, and notifies a completion change.

        if($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC
                && $cm->completionview == COMPLETION_VIEW_REQUIRED
                && !$completiondata->completionstate) {
            $completiondata->completionstate = COMPLETION_COMPLETE;
            $completiondata->timemodified = time();
            $completion->internal_set_data($cm, $completiondata);
            $completionchanged = true;
        } else {
            $completionchanged = false;
        }

        return [
            'completionchanged' => $completionchanged,
            'completionvalue' => $completiondata->completionstate,
            'warnings' => $warnings
        ];
    }

    /**
     * Parameter description for activity_completion external function.
     *
     * @return external_function_parameters
     */

    public static function activity_completion_parameters() {
        return new external_function_parameters([
            'cm' => new external_value(PARAM_INT, 'Course module ID.')
        ]);
    }

    /**
     * Results description for activity_completion.
     *
     * @return external_single_structure
     */

    public static function activity_completion_returns() {
        return new external_single_structure(
            [
                'completionchanged' => new external_value(PARAM_BOOL, 'Returns if completion state has changed.'),
                'completionvalue' => new external_value(PARAM_INT, 'Returns new completion state.'),
                'warnings' => new external_warnings()
            ]
        );
    }
}
