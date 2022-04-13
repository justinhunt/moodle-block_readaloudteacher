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
use block_readaloudteacher\klass_custom;
use block_readaloudteacher\klass_group;

require('../../../config.php');

$klassid = required_param('id', PARAM_INT);
$klasstype = required_param('klasstype', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

require_login();

$context = context_course::instance($courseid, MUST_EXIST);
$course = get_course($courseid);
require_capability('block/readaloudteacher:manageklass', $context);

$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_url(constants::M_URL . '/klasses/view.php', array('id'=>$klassid,'klasstype'=>$klasstype,'courseid'=>$courseid));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('course');
$PAGE->navbar->add(get_string('viewklass', constants::M_COMP));

//get our class and fix our redirect url
if($klasstype==constants::M_KLASS_CUSTOM) {
    $klass = klass_custom::fetch_from_id($klassid);
}else{
    $klass = klass_group::fetch_from_id($klassid);
}
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else{
    $returnurl = new moodle_url( constants::M_URL . '/klasses/klasses.php', array('id' => $courseid));
}

//if no klass we redirect
if(!$klass){
    redirect($returnurl);
}

//get klass members
$klassmembers = $klass->fetch_klassmembers();


//get ready to output
$PAGE->set_title(get_string('viewklassmembers', constants::M_COMP));

//get our renderer
$renderer = $PAGE->get_renderer(constants::M_COMP, 'klasses');
echo $renderer->header();
echo $renderer->heading($COURSE->fullname);
echo $renderer->fetch_klassmember_table($klassmembers,$klass,$courseid);
echo $renderer->footer();