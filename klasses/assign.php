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
 * Class management functions
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use block_readaloudteacher\constants;
use block_readaloudteacher\common;
use block_readaloudteacher\klass_custom;

require('../../../config.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$context = context_course::instance($courseid, MUST_EXIST);
$course = get_course($courseid);
require_capability('block/readaloudteacher:manageklass', $context);

$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_url(constants::M_URL . '/klasses/assign.php', array('id'=>$id,'courseid'=>$courseid));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->navbar->add(get_string('assign', constants::M_COMP));

//get our class and fix our redirect url
$klass = klass_custom::fetch_from_id($id);
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} elseif($klass) {
    $returnurl = new moodle_url( '/course/view.php', array('id' => $klass->courseid));
} else{
    $returnurl = new moodle_url( '/course/view.php', array('id' => $courseid));
}

//if no klass we redirect
if(!$klass){
    redirect($returnurl);
}

//if we are cancelling
if (optional_param('cancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}



$PAGE->set_title(get_string('assignmembers', constants::M_COMP));

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP, 'klasses');
echo $renderer->header();
echo $renderer->heading($COURSE->fullname);


$tmpl = new \block_readaloudteacher\output\klassname($klass);
$klassname = $renderer->render_from_template('core/inplace_editable', $tmpl->export_for_template($renderer));
echo $renderer->heading(get_string('assignto', constants::M_COMP, $klassname));


//echo $OUTPUT->heading(get_string('assignto', constants::M_COMP, format_string($klass->name)));

//echo $OUTPUT->notification(get_string('removeuserwarning', constants::M_COMP));

// Get the user_selector we will need.
$potentialmemberselector = new \block_readaloudteacher\potential_member_selector('addselect', array('klassid'=>$klass->id, 'courseid'=>$courseid, 'accesscontext'=>$context));
$existingmemberselector =  new \block_readaloudteacher\existing_member_selector('removeselect', array('klassid'=>$klass->id, 'courseid'=>$courseid, 'accesscontext'=>$context));

// Process incoming user assignments to the cohort
if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoassign = $potentialmemberselector->get_selected_users();
    if (!empty($userstoassign)) {

        foreach ($userstoassign as $adduser) {
            $klass->add_member($adduser->id);
        }

        $potentialmemberselector->invalidate_selected_users();
        $existingmemberselector->invalidate_selected_users();
    }
}

// Process removing user assignments to the cohort
if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
    $userstoremove = $existingmemberselector->get_selected_users();
    if (!empty($userstoremove)) {
        foreach ($userstoremove as $removeuser) {
            $klass->remove_member($removeuser->id);
        }
        $potentialmemberselector->invalidate_selected_users();
        $existingmemberselector->invalidate_selected_users();
    }
}

$data= new stdClass();
$data->pageurl = $PAGE->url;
$data->sesskey=sesskey();
$data->returnurl=$returnurl->out_as_local_url();
$data->existingmemberselector = $existingmemberselector->display(true);
$data->potentialmemberselector = $potentialmemberselector->display(true);
$data->arrowadd = $renderer->larrow().' '.s(get_string('add'));
$data->arrowremove = s(get_string('remove')).' '.$renderer->rarrow();
echo $renderer->fetch_klasses_assigncomponent($data);
echo $renderer->footer();