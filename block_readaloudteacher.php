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
 * readaloudteacher block.
 *
 * @package    block_readaloudteacher
 * @copyright  Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use block_readaloudteacher\constants;
use block_readaloudteacher\common;

class block_readaloudteacher extends block_base {

    function init() {
        $this->title = get_string('pluginname', constants::M_COMP);
    }

    function get_content() {
        global $CFG, $OUTPUT, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->text = '';

        //get the course this block is on
        $course = $this->page->course;

        //get the block instance settings (position , id  etc)
        $instancesettings = $this->instance;
        $bestconfig = common::fetch_best_config($instancesettings->id);

        //get the courses we could show for this user
        switch ($bestconfig->showcourses) {
            case constants::M_THISCOURSE:
                $possiblecourses = [$course];
                break;

            case constants::M_ENROLLEDCOURSES:
                $possiblecourses = common::fetch_courses_userenrolled($USER->id);
                break;

            case constants::M_ACTIVECOURSES:
            DEFAULT:
            $possiblecourses = common::fetch_courses_with_userattempts($USER->id);
                break;

        }

        //from the possible courses, choose just the ones we have permission for, else exit
        $courses = [];
        foreach($possiblecourses as $thecourse ){
            if (has_capability('block/' . constants::M_NAME. ':manageklass', context_course::instance($thecourse->id))) {
                $courses[] = $thecourse;
            }
        }
        if(!count($courses)){
            $this->content = '';
            return $this->content;
        }

        //for each course get the set of attempts
        $coursedata = array();
        if ($courses) {
            foreach ($courses as $course) {
                $thecourse = new \stdClass();
                $thecourse->id = $course->id;
                $thecourse->fullname = $course->fullname;
                $thecourse->courseusers = common::fetch_course_users($course->id);
                $thecourse->userreadings = common::fetch_user_readings($course->id);
                $thecourse->coursereadings = common::fetch_course_readings($course->id);
                $thecourse->runningrecords = common::fetch_runningrecords($course->id);
                $thecourse->wpmbenchmarks=common::fetch_wpmbenchmarks($course->id);
                $coursedata[] = $thecourse;
            }
        }


        //show content, either klasses or just tables
        if ($bestconfig->klassdisplay != constants::M_KLASSDISPLAYNONE) {
            $renderer = $this->page->get_renderer(constants::M_COMP, 'klasses');
            $content = $renderer->fetch_block_content_byklass($coursedata, $bestconfig);
        } else {
            $renderer = $this->page->get_renderer(constants::M_COMP);
            $content = $renderer->fetch_block_content_allusers($coursedata, $bestconfig);
        }

        $this->content->text = $content;
    }

    //This is a list of places where the block may or may not be added by the admin
    public function applicable_formats() {
        return array('all' => false,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true, 
                     'course-view-social' => false,
                     'mod' => true, 
                     'mod-quiz' => false,
                    'my'=>true);
    }

    //Can we have more than one instance of the block?
    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}

    public function hide_header() {
        return true;
    }

}
