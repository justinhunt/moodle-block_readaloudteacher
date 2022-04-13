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
 * Klasses manage
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt  {@link http://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_readaloudteacher\constants;
use block_readaloudteacher\common;
use block_readaloudteacher\klass_custom;
use block_readaloudteacher\klass_group;
require('../../../config.php');

$courseid = required_param('courseid',PARAM_INT);

//set the url of the $PAGE
//note we do this before require_login preferably
//so Moodle will send user back here if it bounces them off to login first
$PAGE->set_url(constants::M_URL . '/klasses/klasses.php',array('courseid'=>$courseid));
$course = get_course($courseid);
require_login($course);


$coursecontext = context_course::instance($course->id);
$PAGE->set_course($course);
$PAGE->set_context($coursecontext);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->set_title(get_string('pluginname', constants::M_COMP));
$PAGE->navbar->add(get_string('pluginname', constants::M_COMP));

//we could pass in $klassdisplay here and so not display klass_groups or not display klass_customs
//but then we would need to pass an extra param around lots of form codes. its probably better just not to add groups in courses
//that you do not want to show them in
$klasses = common::fetch_klasses($USER->id,$courseid,constants::M_KLASSDISPLAYGROUPCUSTOM);
foreach($klasses as $klass){
    $klass->membercount = $klass->count_klassmembers();
}


$klassmanager = has_capability('block/readaloudteacher:manageklass', $coursecontext);

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP,'klasses');
echo $renderer->header();
echo $renderer->heading($SITE->fullname);


if($klassmanager) {

    //display the content of this page from our nice renderer
    $klasstable = $renderer->fetch_klasses_table($klasses, $courseid);
    echo $klasstable;

}else{
    echo  get_string('noklasspermission', constants::M_COMP);
}
echo $renderer->fetch_returntocourse_button($courseid);
echo $renderer->footer();