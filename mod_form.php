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
 * The main mod_videoredirect configuration form.
 *
 * @package     mod_videoredirect
 * @copyright   2019, Creatic SAS <soporte@creatic.co>.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_videoredirect
 * @copyright  2019, Creatic SAS <soporte@creatic.co>.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videoredirect_mod_form extends moodleform_mod {

    /**
     * Form definition.
     *
     * @throws HTML_QuickForm_Error
     * @throws coding_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'mod_videoredirect'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adds video URL text field.
        $mform->addElement('text', 'videolink', get_string('videolink', 'mod_videoredirect'));
        $mform->setType('videolink', PARAM_TEXT);
        $mform->addRule('videolink', get_string('required'), 'required', null, 'client');

        // Adds width field.
        $mform->addElement('text', 'videowidth', get_string('videowidth', 'mod_videoredirect'));
        $mform->setType('videowidth', PARAM_INT);
        $mform->addRule('videowidth', get_string('maximumchars', '', 4), 'maxlength', 4, 'client');

        // Adds video aspect ratio field.
        $optionslist = ['16:9', '16:10', '4:3', '3:2', '2:1', '1:1'];
        $mform->addElement('select', 'videoaspect', get_string('videoaspect', 'mod_videoredirect'), $optionslist);
        $mform->setType('videoaspect', PARAM_INT);

        // Add fields that allow to set redirection time and URL if enabled.
        $mform->addElement('advcheckbox', 'redirectonend', get_string('redirectonend', 'mod_videoredirect'));

        $mform->addElement('text', 'redirecturl', get_string('redirecturl', 'mod_videoredirect'));
        $mform->setType('redirecturl', PARAM_TEXT);
        $mform->hideIf('redirecturl', 'redirectonend', 'notchecked');

        // Adds redirect button text field with its description
        $mform->addElement('text', 'redirecttext', get_string('redirecttext', 'mod_videoredirect'),
                array('size' => '64'));
        $mform->setType('redirecttext', PARAM_TEXT);
        $mform->addRule('redirecttext', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->hideIf('redirecttext', 'redirectonend', 'notchecked');
        $mform->addElement('static', 'description', '', get_string('redirecttextdescription', 'mod_videoredirect'));
        $mform->hideIf('description', 'redirectonend', 'notchecked');

        // Adds redirection seconds text field
        $mform->addElement('text', 'redirectsecs', get_string('redirectsecs', 'mod_videoredirect'));
        $mform->setType('redirectsecs', PARAM_INT);
        $mform->setDefault('redirectsecs', 0);
        $mform->addRule('redirectsecs', get_string('maximumchars', '', 2), 'maxlength', 2, 'client');
        $mform->hideIf('redirectsecs', 'redirectonend', 'notchecked');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Process data before showing it in the Add or Update Activity form.
     * @param array $default_values List of values that will be loaded to the form.
     */
    public function data_preprocessing(&$default_values) {
        if(!isset($default_values['videoaspect'])) {
            return;
        }

        switch($default_values['videoaspect']) {
            case '16:9';
                $default_values['videoaspect'] = 0;
                break;
            case '16:10';
                $default_values['videoaspect'] = 1;
                break;
            case '4:3';
                $default_values['videoaspect'] = 2;
                break;
            case '3:2';
                $default_values['videoaspect'] = 3;
                break;
            case '2:1';
                $default_values['videoaspect'] = 4;
                break;
            case '1:1';
                $default_values['videoaspect'] = 5;
                break;
        }
    }
}
