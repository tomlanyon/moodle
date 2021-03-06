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
 * @package    mod
 * @subpackage page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Page module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function page_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function page_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function page_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function page_get_view_actions() {
    return array('view','view all');
}

/**
 * List of update style log actions
 * @return array
 */
function page_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add page instance.
 * @param stdClass $data
 * @param mod_page_mod_form $mform
 * @return int new page instance id
 */
function page_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    if ($mform) {
        $data->content       = $data->page['text'];
        $data->contentformat = $data->page['format'];
    }

    $data->id = $DB->insert_record('page', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id'=>$cmid));
    $context = get_context_instance(CONTEXT_MODULE, $cmid);

    if ($mform and !empty($data->page['itemid'])) {
        $draftitemid = $data->page['itemid'];
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_page', 'content', 0, page_get_editor_options($context), $data->content);
        $DB->update_record('page', $data);
    }

    return $data->id;
}

/**
 * Update page instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function page_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid        = $data->coursemodule;
    $draftitemid = $data->page['itemid'];

    $data->timemodified = time();
    $data->id           = $data->instance;
    $data->revision++;

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth']  = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro']   = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    $data->content       = $data->page['text'];
    $data->contentformat = $data->page['format'];

    $DB->update_record('page', $data);

    $context = get_context_instance(CONTEXT_MODULE, $cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_page', 'content', 0, page_get_editor_options($context), $data->content);
        $DB->update_record('page', $data);
    }

    return true;
}

/**
 * Delete page instance.
 * @param int $id
 * @return bool true
 */
function page_delete_instance($id) {
    global $DB;

    if (!$page = $DB->get_record('page', array('id'=>$id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('page', array('id'=>$page->id));

    return true;
}

/**
 * Return use outline
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $page
 * @return object|null
 */
function page_user_outline($course, $user, $mod, $page) {
    global $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'page',
                                              'action'=>'view', 'info'=>$page->id), 'time ASC')) {

        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $result = new stdClass();
        $result->info = get_string('numviews', '', $numviews);
        $result->time = $lastlog->time;

        return $result;
    }
    return NULL;
}

/**
 * Return use complete
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $page
 */
function page_user_complete($course, $user, $mod, $page) {
    global $CFG, $DB;

    if ($logs = $DB->get_records('log', array('userid'=>$user->id, 'module'=>'page',
                                              'action'=>'view', 'info'=>$page->id), 'time ASC')) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);

    } else {
        print_string('neverseen', 'page');
    }
}

/**
 * Returns the users with data in one page
 *
 * @todo: deprecated - to be deleted in 2.2
 *
 * @param int $pageid
 * @return bool false
 */
function page_get_participants($pageid) {
    return false;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See {@link get_array_of_activities()} in course/lib.php
 *
 * @param cm_info $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function page_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$page = $DB->get_record('page', array('id'=>$coursemodule->instance),
            'id, name, display, displayoptions, intro, introformat')) {
        return NULL;
    }

    $info = new cached_cm_info();
    $info->name = $page->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('page', $page, $coursemodule->id, false);
    }

    if ($page->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/page/view.php?id=$coursemodule->id&amp;inpopup=1";
    $options = empty($page->displayoptions) ? array() : unserialize($page->displayoptions);
    $width  = empty($options['popupwidth'])  ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
    $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}


/**
 * Lists all browsable file areas
 *
 * @package  mod_page
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function page_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('content', 'page');
    return $areas;
}

/**
 * File browsing support for page module content area.
 *
 * @package  mod_page
 * @category files
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function page_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_page', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_page', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/page/locallib.php");
        return new page_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: page_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the page files.
 *
 * @package  mod_page
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function page_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_capability('mod/page:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    // $arg could be revision number or index.html
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve page content
        $filename = $arg;

        if (!$page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // remove @@PLUGINFILE@@/
        $content = str_replace('@@PLUGINFILE@@/', '', $page->content);

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $page->contentformat, $formatoptions);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_page/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            $page = $DB->get_record('page', array('id'=>$cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($page->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/'.$relativepath, $cm->id, $cm->course, 'mod_page', 'content', 0)) {
                return false;
            }
            //file migrate - update flag
            $page->legacyfileslast = time();
            $DB->update_record('page', $page);
        }

        // finally send the file
        send_stored_file($file, 86400, 0, $forcedownload, $options);
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function page_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-page-*'=>get_string('page-mod-page-x', 'page'));
    return $module_pagetype;
}

/**
 * Export page resource contents
 *
 * @return array of file content
 */
function page_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST);

    // page contents
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_page', 'content', 0, 'sortorder DESC, id ASC', false);
    foreach ($files as $fileinfo) {
        $file = array();
        $file['type']         = 'file';
        $file['filename']     = $fileinfo->get_filename();
        $file['filepath']     = $fileinfo->get_filepath();
        $file['filesize']     = $fileinfo->get_filesize();
        $file['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_page/content/'.$page->revision.$fileinfo->get_filepath().$fileinfo->get_filename(), true);
        $file['timecreated']  = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder']    = $fileinfo->get_sortorder();
        $file['userid']       = $fileinfo->get_userid();
        $file['author']       = $fileinfo->get_author();
        $file['license']      = $fileinfo->get_license();
        $contents[] = $file;
    }

    // page html conent
    $filename = 'index.html';
    $pagefile = array();
    $pagefile['type']         = 'file';
    $pagefile['filename']     = $filename;
    $pagefile['filepath']     = '/';
    $pagefile['filesize']     = 0;
    $pagefile['fileurl']      = file_encode_url("$CFG->wwwroot/" . $baseurl, '/'.$context->id.'/mod_page/content/' . $filename, true);
    $pagefile['timecreated']  = null;
    $pagefile['timemodified'] = $page->timemodified;
    // make this file as main file
    $pagefile['sortorder']    = 1;
    $pagefile['userid']       = null;
    $pagefile['author']       = null;
    $pagefile['license']      = null;
    $contents[] = $pagefile;

    return $contents;
}
