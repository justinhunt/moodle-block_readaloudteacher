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
 * Klass reports
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use block_readaloudteacher\constants;
use block_readaloudteacher\common;
use block_readaloudteacher\klass_custom;
use block_readaloudteacher\klass_group;

require('../../../config.php');

$klassid = required_param('klassid', PARAM_INT);
$klasstype = required_param('klasstype', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$showtables = optional_param('showtables', false, PARAM_BOOL);

require_login();

$context = context_course::instance($courseid, MUST_EXIST);
$course = get_course($courseid);
require_capability('block/readaloudteacher:manageklass', $context);

switch($klasstype){
    case constants::M_KLASS_CUSTOM:
        $klass= klass_custom::fetch_from_id($klassid);
        break;
    case constants::M_KLASS_GROUP:
        $klass= klass_group::fetch_from_id($klassid);
        break;
    case constants::M_KLASS_NONE:
    default:
        $klass=false;
}

if(!$klass){
    redirect($returnurl);
}
$klassmemberids = $klass->fetch_klassmemberids();
$PAGE->requires->css(new \moodle_url('/blocks/readaloudstudent/fonts/fonts.css'));
$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_url(constants::M_URL . '/klasses/klassreports.php', array('klassid'=>$klassid,'klasstype'=>$klasstype,'courseid'=>$courseid));
$PAGE->set_pagelayout('course');
$reporttitle = get_string('readingprogressreport', constants::M_COMP, $klass->name);
$PAGE->navbar->add($reporttitle);
$PAGE->set_heading($klass->name);

//prepare return url
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else if(false){
    $returnurl = new moodle_url(constants::M_URL . '/klasses/klasses.php', array('courseid' => $klass->courseid));
} else {
    $returnurl = new moodle_url( '/course/view.php', array('id' => $klass->courseid));
}

//prepare data for reports
$thecourse = new \stdClass();
$thecourse->id =  $course->id;
$thecourse->fullname=$course->fullname;
$thecourse->courseusers=common::fetch_course_users($course->id);
$thecourse->userreadings=common::fetch_user_readings($course->id);
$thecourse->coursereadings=common::fetch_course_readings($course->id);
$thecourse->runningrecords=common::fetch_runningrecords($course->id);
$thecourse->wpmbenchmarks=common::fetch_wpmbenchmarks($course->id);


if ($showtables) {
    // If we want to show legacy tables (not charts).

    $renderer = $PAGE->get_renderer(constants::M_COMP,'klasses');
    $klass_overview = $renderer->fetch_klass_overview($klass, $thecourse, false);
    $klassreport_readingscomplete = $renderer->fetch_klassreport_readingscomplete($thecourse,$klassmemberids);
    $klassreport_wpm = $renderer->fetch_klassreport_wpm($thecourse,$klassmemberids);
    $klassreport_accuracy = $renderer->fetch_klassreport_accuracy($thecourse,$klassmemberids);
    $klassreport_qscore = $renderer->fetch_klassreport_qscore($thecourse,$klassmemberids);
    $klassreport_summarytable = $renderer->fetch_klass_summarytable($thecourse,$klassmemberids);

    echo $renderer->header();
//    echo $renderer->heading($COURSE->fullname);
//    echo html_writer::tag('h4', $klass->name);
//    echo $renderer->fetch_return_button($returnurl);
    echo html_writer::start_div('block_readaloudteacher_content');
    echo $klass_overview;
    echo $klassreport_readingscomplete;
    echo $klassreport_wpm;
    echo $klassreport_accuracy;
    echo $klassreport_qscore;
    echo $klassreport_summarytable;
    echo $renderer->fetch_return_button($returnurl);
    echo html_writer::end_div();
    echo $renderer->footer();
} else {
    // If we want to show Highcharts (not tables).

    $PAGE->requires->js_call_amd("block_readaloudteacher/readaloudhighcharts", 'init', [$courseid, $klass, false]);

    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer(constants::M_COMP,'klasses');
    $iscoursescreen = $klassid ? false : true;
    $templateable = new \block_readaloudteacher\output\klass_charts($klass, $thecourse, false, $iscoursescreen, true);
    $data = $templateable->export_for_template($renderer);
    echo $renderer->render_from_template('block_readaloudteacher/klass-reports', $data);
    echo $OUTPUT->footer();
}
