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


$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$report = optional_param('report',constants:: M_REPORT_ALLUSERREPORTS, PARAM_INT);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$showtables = optional_param('showtables', false, PARAM_BOOL);
$returnklassid = optional_param('returnklassid', 0, PARAM_INT);
$returnklasstype = optional_param('returnklasstype', constants::M_KLASS_NONE, PARAM_INT);

require_login();

$user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
$context = context_course::instance($courseid, MUST_EXIST);
$course = get_course($courseid);
require_capability('block/readaloudteacher:manageklass', $context);

$PAGE->set_course($course);
$PAGE->set_context($context);

$pageparams =  array('courseid'=>$courseid,'userid'=>$userid);
if(!empty($report)){$pageparams['report']=$report;}
if(!empty($returnurl)){$pageparams['returnurl']=$returnurl;}
if ($returnklassid) {
    switch($returnklasstype){
        case constants::M_KLASS_CUSTOM:
            $returnklass = klass_custom::fetch_from_id($returnklassid);
            break;

        case constants::M_KLASS_GROUP:
            $returnklass = klass_group::fetch_from_id($returnklassid);
            break;

         default:
             //this should never happen really
             $returnklass = false;
    }
    $PAGE->navbar->add(
        $returnklass->name,
        new moodle_url('/blocks/readaloudteacher/klasses/klassreports.php',
                array('klassid' => $returnklassid,'klasstype' => $returnklasstype, 'courseid' => $courseid))
    );
    $pageparams['returnklassid'] = $returnklassid;
    $pageparams['returnklasstype'] = $returnklasstype;
} else {
    $returnklass = false;
}
$PAGE->set_url(constants::M_URL . '/klasses/userreports.php', $pageparams);

$PAGE->set_heading(fullname($user));

$PAGE->set_pagelayout('course');

$PAGE->navbar->add(get_string('userreport', constants::M_COMP, fullname($user)));
$PAGE->set_title(get_string('userreport', constants::M_COMP, fullname($user)));
$PAGE->requires->css(new \moodle_url('/blocks/readaloudstudent/fonts/fonts.css'));

//prepare return url
if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
} else {
    $returnurl = new moodle_url( '/course/view.php', array('id' => $courseid));
}


if ($showtables) {
//prepare data for reports
    $thecourse =new \stdClass();
    $thecourse->id =  $course->id;
    $thecourse->fullname=$course->fullname;
    $thecourse->courseusers=common::fetch_course_users($course->id);
    $thecourse->userreadings=common::fetch_user_readings($course->id);
    $thecourse->coursereadings=common::fetch_course_readings($course->id);
    $thecourse->runningrecords=common::fetch_runningrecords($course->id);
    $thecourse->wpmbenchmarks=common::fetch_wpmbenchmarks($course->id);

//just for now we only want one user's data
    $klassmemberids=array($userid);

//get our renderer
    $renderer = $PAGE->get_renderer(constants::M_COMP, 'klasses');

//prepare reports (before printing page header ..since we req css and js)
    switch($report){
        case constants::M_REPORT_USERATTEMPTS:
            $reports = $renderer->fetch_userreport_attempts($thecourse,$klassmemberids);
            break;

        case constants::M_REPORT_ALLUSERREPORTS:
            $reports = $renderer->fetch_user_overview($userid,$thecourse, $returnklass);
            $reports .= $renderer->fetch_userreport_wpm($thecourse,$klassmemberids);
            $reports .= $renderer->fetch_userreport_accuracy($thecourse,$klassmemberids);
            $reports .= $renderer->fetch_userreport_qscore($thecourse,$klassmemberids);
            $reports .= $renderer->fetch_userreport_attempts($thecourse,$klassmemberids);

        default:
    }


    echo $renderer->header();
//    echo $renderer->fetch_return_button($returnurl);
    echo html_writer::start_div('block_readaloudteacher_content');
    echo $reports;
    echo html_writer::end_div();
//    echo $renderer->fetch_return_button($returnurl);
    echo $renderer->footer();

} else {
    // If we want to show Highcharts (not tables).
    $PAGE->requires->js_call_amd("block_readaloudteacher/readaloudhighcharts", 'init', [$courseid, $returnklass, true]);

    echo $OUTPUT->header();
    $renderer = $PAGE->get_renderer(constants::M_COMP,'klasses');
    $templateable = new \block_readaloudteacher\output\user_charts($userid, $courseid, $returnklass, true);
    $data = $templateable->export_for_template($renderer);
    echo $renderer->render_from_template('block_readaloudteacher/user-reports', $data);
    echo $OUTPUT->footer();
}