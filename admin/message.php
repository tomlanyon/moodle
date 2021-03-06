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
 * Message outputs configuration page
 *
 * @package    message
 * @copyright  2011 Lancaster University Network Services Limited
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/message/lib.php');
require_once($CFG->libdir.'/adminlib.php');

// This is an admin page
admin_externalpage_setup('managemessageoutputs');

// Require site configuration capability
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

// Get the submitted params
$disable    = optional_param('disable', 0, PARAM_INT);
$enable     = optional_param('enable', 0, PARAM_INT);
$uninstall  = optional_param('uninstall', 0, PARAM_INT);
$confirm  = optional_param('confirm', false, PARAM_BOOL);

$headingtitle = get_string('managemessageoutputs', 'message');

if (!empty($disable) && confirm_sesskey()) {
    if (!$processor = $DB->get_record('message_processors', array('id'=>$disable))) {
        print_error('outputdoesnotexist', 'message');
    }
    $DB->set_field('message_processors', 'enabled', '0', array('id'=>$processor->id));      // Disable output
}

if (!empty($enable) && confirm_sesskey()) {
    if (!$processor = $DB->get_record('message_processors', array('id'=>$enable))) {
        print_error('outputdoesnotexist', 'message');
    }
    $DB->set_field('message_processors', 'enabled', '1', array('id'=>$processor->id));      // Enable output
}

if (!empty($uninstall) && confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($headingtitle);

    if (!$processor = $DB->get_record('message_processors', array('id'=>$uninstall))) {
        print_error('outputdoesnotexist', 'message');
    }

    $processorname = get_string('pluginname', 'message_'.$processor->name);

    if (!$confirm) {
        echo $OUTPUT->confirm(get_string('processordeleteconfirm', 'message', $processorname), 'message.php?uninstall='.$processor->id.'&confirm=1', 'message.php');
        echo $OUTPUT->footer();
        exit;

    } else {
        message_processor_uninstall($processor->name);
        $a->processor = $processorname;
        $a->directory = $CFG->dirroot.'/message/output/'.$processor->name;
        notice(get_string('processordeletefiles', 'message', $a), 'message.php');
    }
}

if ($disable || $enable || $uninstall) {
    $url = new moodle_url('message.php');
    redirect($url);
}
// Page settings
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));

// Grab the renderer
$renderer = $PAGE->get_renderer('core', 'message');

// Display the manage message outputs interface
$processors = get_message_processors();
$messageoutputs = $renderer->manage_messageoutputs($processors);

// Display the page
echo $OUTPUT->header();
echo $OUTPUT->heading($headingtitle);
echo $messageoutputs;
echo $OUTPUT->footer();