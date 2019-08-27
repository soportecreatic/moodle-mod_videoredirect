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
 * Library of interface functions and constants.
 *
 * @package     mod_videoredirect
 * @copyright   2019, Creatic SAS <soporte@creatic.co>.
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function videoredirect_supports($feature) {
    switch($feature) {
        case FEATURE_NO_VIEW_LINK:            return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        default:                              return null;
    }
}

/**
 * Returns a string with an aspect ratio value based on a integer.
 *
 * @param int $value Value sent from Add or Update Activity form.
 * @return string Aspect ratio value.
 */
function videoredirect_set_aspect_ratio($value) {
    switch($value) {
        case 1:
            $videoaspect = '16:10';
            break;
        case 2:
            $videoaspect = '4:3';
            break;
        case 3:
            $videoaspect = '3:2';
            break;
        case 4:
            $videoaspect = '2:1';
            break;
        case 5:
            $videoaspect = '1:1';
            break;
        default:
            $videoaspect = '16:9';
    }

    return $videoaspect;
}

/**
 * Saves a new instance of the mod_videoredirect into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_videoredirect_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function videoredirect_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();
    $moduleinstance->videoaspect = videoredirect_set_aspect_ratio($moduleinstance->videoaspect);
    $id = $DB->insert_record('videoredirect', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_videoredirect in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_videoredirect_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function videoredirect_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;
    $moduleinstance->videoaspect = videoredirect_set_aspect_ratio($moduleinstance->videoaspect);
    return $DB->update_record('videoredirect', $moduleinstance);
}

/**
 * Removes an instance of the mod_videoredirect from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function videoredirect_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('videoredirect', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('videoredirect', array('id' => $id));

    return true;
}

/**
 * Shows video on course view.
 *
 * @param object $coursemodule Course module information.
 * @return object $info. Returns a course module cached info object.
 */

function videoredirect_get_coursemodule_info($coursemodule) {
    global $DB, $PAGE;
    $PAGE->requires->css(new moodle_url('/mod/videoredirect/styles.css'));

    $videoredirect = $DB->get_record('videoredirect', ['id' => $coursemodule->instance]);
    $videourl = $videoredirect->videolink;
    $videowidth = $videoredirect->videowidth;

    $aspectratio = explode(':', $videoredirect->videoaspect);
    $videoheight = $aspectratio[1] * $videowidth / $aspectratio[0];
    $videopercheight = $aspectratio[1] * 100 / $aspectratio[0];

    // Variable $html will show renderized information.
    $html = '';

    // Variable $videoattributes is used to put attributes to the video object if user was set it.
    $videoattributes['controls'] = true;

    /**
     * Variable $notifnextvideoatts and $videocontaineratts are used to put
     * additional attributes to video superior div and notification elements
     * when is needed.
     */

    $notifnextvideoatts = ['class' => 'notif-next-video-container'];
    $videocontaineratts = ['class' => 'video-container'];
    $videoareaatts = ['class' => 'video-area'];

    // Checks if data are defined to use it on HTML video attributes.
    if($videoredirect->redirectonend) {
        $videocontaineratts['data-seconds-to-go'] = $videoredirect->redirectsecs;
        $videocontaineratts['data-url'] = $videoredirect->redirecturl;
    }

    if($videoredirect->videowidth) {
        $videoattributes['width'] = $videowidth;
        $notifnextvideoatts['style'] = 'max-width: ' . $videowidth . 'px;';
    }

    if($videoredirect->videohtmlid) {
        $videoattributes['id'] = '#' . $videoredirect->videohtmlid;
    }

    if($videoredirect->videohtmlclass) {
        $videoattributes['class'] = $videoredirect->videohtmlclass;
    }

    // If the video is a Vimeo's link, returns an iframe, otherwise, let VideoJS
    // to process the content.
    if(strpos($videourl, 'vimeo.com/') && !strpos($videourl, 'external/')) {
        $videocontaineratts['class'] = 'video-container video-vimeo';
        $videocontaineratts['style'] = 'max-width: ' . $videowidth . 'px';
        $videocontaineratts['data-video-type'] = 'vimeo';
        $videoareaatts['style'] = 'padding-top: ' . $videopercheight . '%;';
        $videohtml = html_writer::tag('iframe', '',
            [
                'src'                   => $videourl,
                'width'                 => $videowidth,
                'height'                => $videoheight,
                'webkitallowfullscreen' => '',
                'mozallowfullscreen'    => '',
                'allowfullscreen'       => '',
                'frameborder'           => 0
            ]
        );
    } else {
        $tagvideosource = html_writer::empty_tag('source', ['src' => $videourl]);
        $videohtml = html_writer::tag('video', $tagvideosource . $videourl, $videoattributes);
    }

    // Notification that appears at the end of the video
    $html .= html_writer::start_div('', $videocontaineratts);
    $html .= html_writer::start_div('', $videoareaatts);
    $html .= $videohtml;

    if($videoredirect->redirectonend) {
        $html .= html_writer::start_div('', ['class' => 'notif-next-video']);
        $html .= html_writer::start_div('', $notifnextvideoatts);
        $html .= html_writer::start_div('', ['class' => 'options']);
        $buttonhtml = html_writer::link('#', get_string('cancelreturnvideo', 'mod_videoredirect'),
                ['class' => 'button cancel']);
        $html .= html_writer::tag('div', $buttonhtml, ['class' => 'button-container']);
        $buttontext = $videoredirect->redirecttext ? $videoredirect->redirecttext
                : get_string('gotonexttopic', 'mod_videoredirect');
        $buttonhtml = html_writer::link($videoredirect->redirecturl, $buttontext, ['class' => 'button']);

        $html .= html_writer::tag('div', $buttonhtml, ['class' => 'button-container']);
        $spanhtml = html_writer::tag('span', $videoredirect->redirectsecs, ['class' => 'countdown']);
        $html .= html_writer::tag('h5', sprintf(get_string('nexttopicstart', 'mod_videoredirect'),
                $spanhtml), ['class' => 'active']);
        $html .= html_writer::end_div();
        $html .= html_writer::end_div();
        $html .= html_writer::tag('div', get_string('authorinfo', 'mod_videoredirect'), ['class' => 'author-info']);
        $html .= html_writer::end_div();
    }

    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    $info = new cached_cm_info();
    $info->content = $html;

    return $info;
}

/**
 * Loads countdown frame JavaScript when there's a course module in a course or section.
 */
function videoredirect_cm_info_view() {
    global $PAGE;
    $PAGE->requires->js_call_amd('mod_videoredirect/countdown-lazy', 'init');
}

/**
 * Loads Vimeo SDK JavaScript to use countdown with Vimeo's videos.
 */
function videoredirect_cm_info_dynamic() {
    global $PAGE;
    $PAGE->requires->js(new moodle_url('https://player.vimeo.com/api/player.js'), true);
}
