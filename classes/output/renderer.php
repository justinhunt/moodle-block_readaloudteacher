<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_readaloudteacher\output;

use block_readaloudteacher\constants;
use block_readaloudteacher\common;
use block_readaloudteacher\reports;

class renderer extends \plugin_renderer_base {


    // prepare and display the content that goes in the block: no klasses, all users
    function fetch_block_content_allusers($coursedata, $config) {
        global $USER;

        // show our intro text
        $content = '';
        if(!$coursedata || count($coursedata) < 0){
            $content .= \html_writer::div(get_string('nodataavailable', constants::M_COMP), 'alert alert-info');
        }else{
            foreach($coursedata as $thecourse){

                $tabpanels = [];

                // users report
                $exportbutton = $this->fetch_export_button(constants::M_REPORT_USERS, $thecourse->id);
                $tabpanels[] = $this->fetch_user_report($thecourse). $this->render($exportbutton);

                // readings report
                $exportbutton = $this->fetch_export_button(constants::M_REPORT_READINGS, $thecourse->id);
                $tabpanels[] = $this->fetch_by_readings($thecourse). $this->render($exportbutton);

                // Byuser report
                $exportbutton = $this->fetch_export_button(constants::M_REPORT_BYUSER, $thecourse->id);
                $tabpanels[] = $this->fetch_by_user($thecourse). $this->render($exportbutton);
                // $tabpanels[] = $this->fetch_course_users($thecourse);

                // course readings report
                $exportbutton = $this->fetch_export_button(constants::M_REPORT_COURSEREADINGS, $thecourse->id);
                $tabpanels[] = $this->fetch_course_readings($thecourse) . $this->render($exportbutton);

                // course readings report
                $exportbutton = $this->fetch_export_button(constants::M_REPORT_RUNNINGRECORDS, $thecourse->id);
                $tabpanels[] = $this->fetch_runningrecords($thecourse) . $this->render($exportbutton);

                $tabtitles = [];
                $tabtitles[] = get_string('tabtitle_summary', constants::M_COMP);
                $tabtitles[] = get_string('tabtitle_byreading', constants::M_COMP);
                $tabtitles[] = get_string('tabtitle_byuser', constants::M_COMP);
                // $tabtitles[]=get_string('tabtitle_courseusers',constants::M_COMP);
                $tabtitles[] = get_string('tabtitle_coursereadings', constants::M_COMP);
                $tabtitles[] = get_string('tabtitle_runningrecords', constants::M_COMP);

                $coursehtml = $this->render_tabs($tabtitles, $tabpanels);

                $onecoursecontainer = \html_writer::div($coursehtml, constants::M_COMP . '_onecoursecontainer');
                $coursetitle = $this->output->heading($thecourse->fullname, 5);

                $content .= $coursetitle . $onecoursecontainer;
            }
        }
        return $content;

    }

    // return a back to course button for a report
    function fetch_returntocourse_button($courseid) {
        $returnbutton = new \single_button(
                new \moodle_url(  '/course/view.php', ['id' => $courseid]),
                get_string('returntocourse', constants::M_COMP), 'get');
        return $this->render($returnbutton);
    }

    // return a back to to button for a report
    function fetch_return_button($returnurl) {
        $returnbutton = new \single_button(
                new \moodle_url(  $returnurl),
                get_string('backtotop', constants::M_COMP), 'get');
        return $this->render($returnbutton);
    }

    // return an export to csv button for a report
    function fetch_export_button($showreport, $courseid, $klass=false) {
        if($klass){
            $klassid = $klass->id;
            $klasstype = $klass->type;
        }else{
            $klassid = 0;
            $klasstype = 0;
        }
        $exportbutton = new \single_button(
            new \moodle_url(constants::M_URL . '/reports.php', ['format' => 'csv', 'showreport' => $showreport, 'courseid' => $courseid, 'klassid' => $klassid, 'klasstype' => $klasstype]),
            get_string('exportcsv', constants::M_COMP), 'get');
        return $exportbutton;
    }

    // In this function we prepare and display the content for the page
    function display_view_page($coursedata) {
        global $USER;

        $content = $this->fetch_block_content_allusers($coursedata);
        // a page must have a header
        echo $this->output->header();
        // and of course our page content
        echo $content;
        // a page must have a footer
        echo $this->output->footer();
    }

      /**
       * Embed the Read Aloud Teacher block.
       *
       * @param int $courseid The ID of the course.
       * @param int $blockid The ID of the block.
       * @return string The HTML content of the block.
       * @throws \coding_exception
       * @throws \dml_exception
       */
    public function embed_blockreadaloudteacher($courseid=0, $blockid=0) {
        global $COURSE, $USER;
        // Get course.
        if ($courseid) {
            $thecourse = get_course($courseid);
        } else {
            $thecourse = $COURSE;
        }
        // get context
        if ($blockid) {
            $usecontext = \context_block::instance($blockid);
        } else {
            $usecontext = \context_course::instance($thecourse->id);
        }

        // Fetch config. using our helper class which merges admin and local settings.
        $bestconfig = common::fetch_best_config($blockid);

        // from the possible courses, choose just the ones we have permission for, else exit
        $courses = [];
        if (has_capability('block/' . constants::M_NAME. ':manageklass', $usecontext)) {
            $courses[] = $thecourse;
        }

        if (!count($courses)) {
            return '';
        }

        // for each course get the set of attempts
        $coursedata = [];
        if ($courses) {
            foreach ($courses as $course) {
                $thecourse = new \stdClass();
                $thecourse->id = $course->id;
                $thecourse->fullname = $course->fullname;
                $thecourse->courseusers = common::fetch_course_users($course->id);
                $thecourse->userreadings = common::fetch_user_readings($course->id);
                $thecourse->coursereadings = common::fetch_course_readings($course->id);
                $thecourse->runningrecords = common::fetch_runningrecords($course->id);
                $thecourse->wpmbenchmarks = common::fetch_wpmbenchmarks($course->id);
                $coursedata[] = $thecourse;
            }
        }

        // show content, either klasses or just tables
        if ($bestconfig->klassdisplay != constants::M_KLASSDISPLAYNONE) {
            $renderer = $this->page->get_renderer(constants::M_COMP, 'klasses');
            $content = $renderer->fetch_block_content_byklass($coursedata, $bestconfig);
        } else {
            $renderer = $this->page->get_renderer(constants::M_COMP);
            $content = $renderer->fetch_block_content_allusers($coursedata, $bestconfig);
        }
        return $content;

    }

    function fetch_user_report($thecoursedata, $klassmemberids=false) {
        $tableid = \html_writer::random_id(constants::M_COMP);

        // make table
        $tabletitle = get_string('tabtop_summary', constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_USERS, $thecoursedata, true, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);

        // apply data table, order by date desc
        $filtercolumn = false;
        $filterlabel = false;
        $order = [];
        $order[0] = [9, "desc"]; // lastdate desc
        $this->setup_datatables($tableid, $filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function fetch_course_users($thecoursedata) {
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_COURSEUSERS, $thecoursedata);
        $tabletitle = get_string('tabtop_courseusers', constants::M_COMP);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);
        $this->setup_datatables($tableid);
        return $thetable;
    }
    function fetch_course_readings($thecoursedata, $klassmemberids=false) {
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_COURSEREADINGS, $thecoursedata, true, $klassmemberids);
        $tabletitle = get_string('tabtop_coursereadings', constants::M_COMP);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);
        // order
        $order[0] = [1, "asc"]; // Reading asc
        $this->setup_datatables($tableid, false, false, $order);
        return $thetable;
    }

    function fetch_by_user($thecoursedata, $klassmemberids=false) {
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_BYUSER, $thecoursedata, true, $klassmemberids);
        $tabletitle = get_string('tabtop_byuser', constants::M_COMP);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);

        // set up a filter for the users
        $filtercolumn = 1;
        $filterlabel = get_string('usertoshow', constants::M_COMP);
        // sort last name first name
        $order = [];
        $order[0] = [1, "asc"]; // full name asc
        $order[1] = [2, "asc"]; // reading name asc
        $this->setup_datatables($tableid, $filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function fetch_by_readings($thecoursedata, $klassmemberids=false) {
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_READINGS, $thecoursedata, true, $klassmemberids);
        $tabletitle = get_string('tabtop_byreading', constants::M_COMP);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);

        // set up a filter for the readings
        $filtercolumn = 3;
        $filterlabel = get_string('readingtoshow', constants::M_COMP);
        // order
        $order[0] = [3, "asc"]; // Reading asc
        $order[1] = [6, "asc"]; // date desc
        $order[2] = [1, "asc"]; // last name asc
        $order[3] = [2, "asc"]; // last name asc
        $order[4] = [0, "asc"]; // userid asc
        $this->setup_datatables($tableid, $filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function fetch_runningrecords($thecoursedata, $klassmemberids=false) {
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname, $head, $fields, $rows) = reports::fetch_report_data(constants::M_REPORT_RUNNINGRECORDS, $thecoursedata, true, $klassmemberids);
        $tabletitle = get_string('tabtop_runningrecords', constants::M_COMP);
        $thetable = $this->render_table($tableid, $tabletitle, $reportname, $head, $fields, $rows);
        // set up a filter for the users
        $filtercolumn = 1;
        $filterlabel = get_string('usertoshow', constants::M_COMP);
        // sort last name first name
        $order = [];
        $order[0] = [1, "asc"]; // full name asc
        $order[1] = [2, "asc"]; // reading name asc
        $this->setup_datatables($tableid, $filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function render_tabs($labels, $panels) {
        // paneids tie menu to panels
        $panelids = [];

        // build the menu from labels
        $itemtemplate = '<li class="nav-item"><a class="nav-link @active@" id="@panelid@-tab" data-toggle="tab" href="#@panelid@" role="tab" aria-controls="@label@" aria-selected="false">@label@</a></li>';
        $itemcount = 0;
        $itemshtml = '';
        foreach($labels as $label){
            $panelid = \html_writer::random_id(constants::M_COMP);
            $item = str_replace('@panelid@', $panelid, $itemtemplate);
            $item = str_replace('@label@', $label, $item);
            $item = str_replace('@active@', $itemcount == 0 ? 'active' : '', $item);
            $panelids[$itemcount] = $panelid;
            $itemshtml .= $item;
            $itemcount++;
        }
        $menucontainer = \html_writer::tag('ul', $itemshtml,
            ['class' => 'nav nav-tabs', 'id' => \html_writer::random_id(constants::M_COMP), 'role' => 'tablist']);

        // build panels from ...panels (duh)
        $panelcount = 0;
        $panelshtml = '';
        foreach($panels as $panel){
            $panelid = $panelids[$panelcount];
            $active = $panelcount == 0 ? 'active' : '';
            $paneldiv = \html_writer::div($panel, 'tab-pane ' . $active , ['id' => $panelid, 'role' => 'tabpanel', 'aria-labelledby' => $panelid .'-tab']);
            $panelshtml .= $paneldiv;
            $panelcount++;
        }
        $panelscontainer = \html_writer::div($panelshtml, 'tab-content');

        return $menucontainer . $panelscontainer;
    }


    function setup_datatables($tableid, $filtercolumn=false, $filterlabel=false, $order=false, $columns=false) {

        // columns
        /*
        $columns[0]=null;
        $columns[1]=null;
        $columns[2]=null;
        $columns[3]=null;
        $columns[4]=array('orderable'=>false);
        $columns[5]=array('orderable'=>false);
        */

        // default ordering
        /* $order[0] =array(3, "desc"); */

        $tableprops = new \stdClass();
        if($order){
            $tableprops->order = $order;
        }
        if($columns){
            $tableprops->columns = $columns;
        }

        // here we set up any info we need to pass into javascript
        $opts = [];
        $opts['tableid'] = $tableid;
        $opts['filtercolumn'] = $filtercolumn;
        $opts['filterlabel'] = $filterlabel;
        $opts['tableprops'] = $tableprops;
        $this->page->requires->js_call_amd( constants::M_COMP . "/datatables", 'init', [$opts]);
        if (!$this->page->headerprinted && !$this->page->requires->is_head_done()) {
            $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
        }
    }

    /*
     * Make a table out of some data
     * sectiontitle = heading
     * report = report display name
     * head = array of title for the columns
     * rows = array of data objects
     * fields - array of names for each field(row object data members)
     *
     */
    public function render_table($tableid, $sectiontitle, $reportname, $head, $fields, $rows) {
        global $CFG;
        if (empty($rows)) {
            $sectiontitle = $this->output->heading($sectiontitle, 5);
            $message = \html_writer::div(get_string('nodataavailable', constants::M_COMP), 'alert alert-info');
            return $sectiontitle . $message;
        }

        // set up our table and head attributes
        $tableattributes = ['class' => 'generaltable ' . constants::M_COMP . '_table'];
        $headrowattributes = ['class' => constants::M_COMP . '_headrow'];

        $htmltable = new \html_table();
        $htmltable->id = $tableid;
        $htmltable->attributes = $tableattributes;

        // the old way which datatables did not like
        /*
        $htr = new \html_table_row();
        $htr->attributes = $headrow_attributes;
        foreach ($head as $headcell) {
            $htr->cells[] = new \html_table_cell($headcell);
        }
        //$htmltable->data[] = $htr;
        */

        // new way (ok with datatables)
        $htmltable->head = $head;

        foreach ($rows as $row) {
            $htr = new \html_table_row();
            // set up descrption cell
            $cells = [];
            foreach ($fields as $field) {
                $cell = new \html_table_cell($row->{$field});
                $cell->attributes = ['class' => constants::M_COMP . '_cell_' . $reportname . '_' . $field];
                $htr->cells[] = $cell;
            }

            $htmltable->data[] = $htr;
        }
        $html = $this->output->heading($sectiontitle, 5);
        $html .= \html_writer::table($htmltable);
        return $html;

    }
}
