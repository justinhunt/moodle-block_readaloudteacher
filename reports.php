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
 * Reports for readaloud
 *
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

use \block_readaloudteacher\constants;
use \block_readaloudteacher\common;
use \block_readaloudteacher\reports;
use \block_readaloudteacher\klass_group;
use \block_readaloudteacher\klass_custom;

$courseid = required_param('courseid' ,PARAM_INT); // course ID
$userid = optional_param('userid',0 ,PARAM_INT); // user ID
$readingid = optional_param('readingid',0 ,PARAM_INT); // reading ID
$klassid = optional_param('klassid',0 ,PARAM_INT); // klass ID
$klasstype = optional_param('klasstype',constants::M_KLASS_NONE ,PARAM_INT); // klass type
$format = optional_param('format', 'csvl', PARAM_TEXT); //export format csv or html
$showreport = optional_param('showreport', 0, PARAM_INT); // report type


//paging details
$paging = new stdClass();
$paging->perpage = optional_param('perpage',10, PARAM_INT);
$paging->pageno = optional_param('pageno',0, PARAM_INT);
$paging->sort  = optional_param('sort','iddsc', PARAM_TEXT);


$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_url(constants::M_URL . '/reports.php',
	array('courseid' => $course->id,'report'=>$showreport,'format'=>$format));
require_login($course, true);
$coursecontext = context_course::instance($course->id);

require_capability('moodle/course:viewparticipants', $coursecontext);

//Get an admin settings 
$config = get_config(constants::M_COMP);


//for each course get the set of attempts
$thecoursedata = new \stdClass();
$thecoursedata ->fullname = $course->fullname;
$thecoursedata ->courseusers = common::fetch_course_users($course->id);
$thecoursedata ->userreadings = common::fetch_user_readings($course->id);
$thecoursedata ->coursereadings = common::fetch_course_readings($course->id);

$exportrenderer = $PAGE->get_renderer(constants::M_COMP,'export');
//Do the export
//TO DO: Add PDF export here.
switch($format){
    case 'csv':
    default:
        $klass=false;
        if($klassid){
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
        }
        $exportrenderer->export_csv($showreport,$thecoursedata,$klass);
}

