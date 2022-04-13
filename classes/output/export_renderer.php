<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_readaloudteacher\output;

use \block_readaloudteacher\constants;
use \block_readaloudteacher\common;
use \block_readaloudteacher\reports;

class export_renderer extends \plugin_renderer_base {

    function export_csv($showreport, $thecoursedata,$klass=false){
        if($klass){
            $klassmemberids = $klass->fetch_klassmemberids();
        }else{
            $klassmemberids =false;
        }
        switch ($showreport){
            case constants::M_REPORT_USERS:
                $this->csv_users($thecoursedata,$klassmemberids);
                break;
            case constants::M_REPORT_COURSEUSERS:
                $this->csv_course_users($thecoursedata,$klassmemberids);
                break;
            case constants::M_REPORT_COURSEREADINGS:
                $this->csv_course_readings($thecoursedata,$klassmemberids);
                break;
            case constants::M_REPORT_BYUSER:
                $this->csv_by_user($thecoursedata,$klassmemberids);
                break;
            case constants::M_REPORT_READINGS:
                $this->csv_readings($thecoursedata,$klassmemberids);
                break;
        }

    }

    function csv_users($thecoursedata,$klassmemberids){
        $tabletitle = get_string('tabtop_summary',constants::M_COMP);
        $withlinks=false;
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_USERS,$thecoursedata,$withlinks,$klassmemberids);
        $this->render_section_csv($tabletitle,$reportname,$head,$fields,$rows);
    }

    function csv_course_users($thecoursedata,$klassmemberids){
        $withlinks=false;
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_COURSEUSERS,$thecoursedata,$withlinks,$klassmemberids);
        $tabletitle = get_string('tabtop_courseusers',constants::M_COMP);
        $this->render_section_csv($tabletitle,$reportname,$head,$fields,$rows);
    }
    function csv_course_readings($thecoursedata,$klassmemberids){
        $withlinks=false;
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_COURSEREADINGS,$thecoursedata,$withlinks,$klassmemberids);
        $tabletitle = get_string('tabtop_coursereadings',constants::M_COMP);
        $this->render_section_csv($tabletitle,$reportname,$head,$fields,$rows);
    }

    function csv_by_user($thecoursedata,$klassmemberids){
        $withlinks=false;
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_BYUSER,$thecoursedata,$withlinks,$klassmemberids);
        $tabletitle = get_string('tabtop_byuser',constants::M_COMP);
        $this->render_section_csv($tabletitle,$reportname,$head,$fields,$rows);
    }

    function csv_readings($thecoursedata,$klassmemberids){
        $withlinks=false;
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_READINGS,$thecoursedata,$withlinks,$klassmemberids);
        $tabletitle = get_string('tabtop_byreading',constants::M_COMP);
        $this->render_section_csv($tabletitle,$reportname,$head,$fields,$rows);
    }


    public function render_section_csv($sectiontitle, $reportname, $head, $fields, $rows)
    {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
        $quote = '"';
        $delim = ",";//"\t";
        $newline = "\r\n";

        header("Content-Disposition: attachment; filename=$name.csv");
        header("Content-Type: text/comma-separated-values");

        //echo header
        $heading = "";
        foreach ($head as $headfield) {
            $heading .= $quote . $headfield . $quote . $delim;
        }
        echo $heading . $newline;

        //echo data rows
        foreach ($rows as $row) {
            $datarow = "";
            foreach ($fields as $field) {
                $datarow .= $quote . $row->{$field} . $quote . $delim;
            }
            echo $datarow . $newline;
        }
        exit();
    }
}