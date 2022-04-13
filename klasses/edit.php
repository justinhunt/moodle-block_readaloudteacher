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
 * Klass related management functions
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../../config.php');

use block_readaloudteacher\constants;
use block_readaloudteacher\common;
use block_readaloudteacher\klass_custom;

$courseid = required_param('courseid',PARAM_INT);

$id        = optional_param('id', 0, PARAM_INT);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$show      = optional_param('show', 0, PARAM_BOOL);
$hide      = optional_param('hide', 0, PARAM_BOOL);
$confirm   = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$category = null;
$baseurl = constants::M_URL;
if ($id) {
    $klass = klass_custom::fetch_from_id($id);
} else {
    $klass = klass_custom::create($courseid);
}
$context = context_course::instance($klass->courseid, MUST_EXIST);
require_capability('block/readaloudteacher:manageklass', $context);

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url($baseurl . '/klasses/klasses.php', array('courseid'=>$klass->courseid));
}


$PAGE->set_context($context);
$baseurl = new moodle_url($baseurl . '/klasses/edit.php', array('id' => $klass->id, 'courseid'=>$klass->courseid));
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$renderer = $PAGE->get_renderer(constants::M_COMP);


if ($delete && $klass->id) {
    $PAGE->url->param('delete', 1);
    if ($confirm and confirm_sesskey()) {
        $klass->delete();
        redirect($returnurl);
    }
    $strheading = get_string('delklass', constants::M_COMP);
    $PAGE->navbar->add($strheading);
    $PAGE->set_title($strheading);
    $PAGE->set_heading($COURSE->fullname);
    echo $renderer->header();
    echo $renderer->heading($strheading);
    $yesurl = new moodle_url($baseurl . '/klasses/edit.php', array('id' => $klass->id, 'delete' => 1,
        'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl->out_as_local_url(),'courseid'=>$klass->courseid));
    $message = get_string('delconfirm', constants::M_COMP, format_string($klass->name));
    echo $renderer->confirm($message, $yesurl, $returnurl);
    echo $renderer->footer();
    die;
}

if ($show && $klass->id && confirm_sesskey()) {
    if (!$klass->visible) {
        $klass->visible=1;
        $klass->save();
    }
    redirect($returnurl);
}

if ($hide && $klass->id && confirm_sesskey()) {
    if ($klass->visible) {
        $klass->visible=0;
        $klass->save();
    }
    redirect($returnurl);
}


$editform = new \block_readaloudteacher\form\klass_edit_form(null, array( 'data'=>$klass,
        'returnurl'=>$returnurl, 'courseid'=>$klass->courseid));

if ($editform->is_cancelled()){
    redirect($returnurl);
}else if($data = $editform->get_data()) {
    if ($data->id) {
        $klass =  klass_custom::fetch_from_record($data);
        $klass->save();
    } else {
        $klass =  klass_custom::fetch_from_record($data);
        $data->id = $klass->save();
        $returnurl = new moodle_url(constants::M_URL . '/klasses/assign.php', array('id'=>$data->id,'courseid'=>$klass->courseid));
    }

    // Redirect to where we were before.
    redirect($returnurl);

}

if($klass->id>0){
    $strheading = get_string('editklass', constants::M_COMP);
}else{
    $strheading = get_string('addklass', constants::M_COMP);
}

$PAGE->set_title($strheading);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($strheading);



echo $renderer->header();
echo $renderer->heading($strheading);
echo $editform->display();
echo $renderer->footer();

