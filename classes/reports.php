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

namespace block_readaloudteacher;

use block_readaloudteacher\constants;

defined('MOODLE_INTERNAL') || die();


/**
 *
 * This is a class containing constants and static functions for general use around the plugin
 *
 * @package   block_readseeedteacher
 * @since      Moodle 3.4
 * @copyright  2018 Justin Hunt (https://poodll,com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class reports
{

    //link to the grading page for an attempt
    public static function make_grading_link($activityid,$attemptid=false,$text=''){
        global $CFG;
        $returnurl = self::make_returnurl();

        if(!$attemptid) {
            $url = $CFG->wwwroot . constants::M_RSGRADEURL . $activityid . $returnurl;
        }else{
            $url = $CFG->wwwroot . constants::M_RSGRADEURL . $activityid . '&action=gradenow&attemptid=' . $attemptid . $returnurl;
        }
        return \html_writer::link($url,$text);
    }

    //return links appropriate for user row in klass summary
    public static function go_buttons_data($courseid, $userid, $attemptid, $activityid=0, $klass=false){
        global $CFG;
        if($klass){
            $klassid=$klass->id;
            $klasstype=$klass->type;
        }else{
            $klassid=0;
            $klasstype=0;
        }
        return [
            'gousergrading' =>
            array(
                'url' => $CFG->wwwroot . constants::M_RSGRADEURL . $activityid . '&action=gradenow&attemptid=' . $attemptid
                    . '&returnurl=' . self::make_returnurl(),
                'id' => 'gousergrading',
                'title' => get_string('gousergrading', constants::M_CLASS),
                'icon' => 'star-o'
            ),
            'gouserreports' => array(
                'url' => $CFG->wwwroot . constants::M_URL
                    . "/klasses/userreports.php?userid=$userid&courseid=$courseid&report=" . constants::M_REPORT_ALLUSERREPORTS .
                        '&returnklassid=' . $klassid . '&returnklasstype=' . $klasstype ,
                'id' => 'gouserreports',
                'title' => get_string('gouserreports', constants::M_CLASS),
                'icon' => 'bar-chart'
            )
// Commenting out as excluded from specification doc August 2019.
//            'gouserattempts' => array(
//                'url' => $CFG->wwwroot . constants::M_URL . "/klasses/userreports.php?userid=$userid&courseid=$courseid&report=" . constants::M_REPORT_USERATTEMPTS,
//                'id' => 'gouserattempts',
//                'title' => get_string('gouserattempts', constants::M_CLASS)
//            )
        ];

    }

    //return grade links appropriate for user row in klass summary
    public static function make_grade_button($courseid, $userid, $attemptid, $activityid=0){
        global $CFG;
        $returnurl = self::make_returnurl();
        $gradingurl = $CFG->wwwroot . constants::M_RSGRADEURL . $activityid . '&action=gradenow&attemptid=' . $attemptid . $returnurl;

        return \html_writer::link($gradingurl,get_string('gousergrading',constants::M_COMP),array('class'=>'btn  btn-secondary'));
    }

    public static function make_returnurl(){
        global $PAGE;
        $returnurl = '&returnurl=' . urlencode($PAGE->url->out());
        return $returnurl;
    }

   //nmake report links
    public static function make_report_link($report,$activityid,$text=''){
        global $CFG;
        switch($report){

            case constants::M_REPORT_RUNNINGRECORDS:
                $reportkey = 'runningrecords';
                break;

            case constants::M_REPORT_USERS:
            case constants::M_REPORT_READINGS:
            case constants::M_REPORT_BYUSER:
            case constants::M_REPORT_COURSEUSERS:
            case constants::M_REPORT_COURSEREADINGS:
            default;
                $reportkey = 'attempts';

        }
        $url = $CFG->wwwroot . constants::M_RSREPORTSURL . $reportkey .'&n=' . $activityid;
        return \html_writer::link($url,$text);

    }


    //This returns report data
    public static function fetch_report_data($report,$thecoursedata,$links=true, $klassmemberids=false, $klass=false)
    {
        switch($report){

            case constants::M_REPORT_USERS:
                return self::fetch_report_users($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_READINGS:
                return self::fetch_report_readings($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_BYUSER:
                return self::fetch_report_byuser($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_USERATTEMPTS:
                return self::fetch_userreport_attempts($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_COURSEUSERS:
                return self::fetch_report_courseusers($thecoursedata,$links);

            case constants::M_REPORT_COURSEREADINGS:
                return self::fetch_report_coursereadings($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_RUNNINGRECORDS:
                return self::fetch_report_runningrecords($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_KLASSSUMMARY:
                return self::fetch_klassreport_summary($thecoursedata,$links, $klassmemberids, $klass);

            case constants::M_REPORT_KLASSREADINGSCOMPLETE:
                return self::fetch_klassreport_readingscomplete($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_KLASSWPM:
                return self::fetch_klassreport_wpm($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_KLASSACCURACY:
                return self::fetch_klassreport_accuracy($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_KLASSQSCORE:
                return self::fetch_klassreport_qscore($thecoursedata,$links, $klassmemberids);

            case constants::M_REPORT_USERWPM:
                return self::fetch_userreport_wpm($thecoursedata, $klassmemberids[0]);

            case constants::M_REPORT_USERACCURACY:
                return self::fetch_userreport_accuracy($thecoursedata, $klassmemberids[0]);

            case constants::M_REPORT_USERQSCORE:
                return self::fetch_userreport_qscore($thecoursedata, $klassmemberids[0]);

        }

    }

    //Users
    public static function fetch_report_runningrecords($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'runningrecords_report';
        $head=array();//'First Name','Last Name','Completed','Av. WPM','Av. Quiz','Last Reading','Last WPM','Last Quiz','Last Date'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('colfullname',constants::M_COMP);
        $head[]=get_string('colreading',constants::M_COMP);
        $head[]=get_string('colwpm',constants::M_COMP);
        $head[]=get_string('colaccuracy',constants::M_COMP);
        $head[]=get_string('colerrorrate',constants::M_COMP);
        $head[]=get_string('colscrate',constants::M_COMP);
        $head[]=get_string('coldate',constants::M_COMP);

        $fields=array('userid','fullname','reading','wpm','accuracy','errorrate','scrate','date');
        $rows=array();
        foreach($thecoursedata->runningrecords as $therecord){
            if(($klassmemberids && !in_array($therecord->userid,$klassmemberids))){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$therecord->userid;
            $onedata->fullname=$thecoursedata->courseusers[$therecord->userid]->fullname;
            $onedata->reading=$therecord->reading;
            $onedata->date =date("Y-m-d H:i:s", $therecord->timecreated);

            //sessiontime is our indicator that a human eval has been saved.
            //otherwise use AI eval
            if (!$therecord->sessiontime) {
                $onedata->wpm = $therecord->ai_wpm;
                $onedata->accuracy = $therecord->ai_accuracy;
                $onedata->errorrate = common::calc_error_rate( $therecord->ai_errorcount,$therecord->ai_sessionendword);
                $onedata->scrate=common::calc_sc_rate($therecord->ai_errorcount,$therecord->ai_sccount);
            }else{
                $onedata->wpm = $therecord->h_wpm;
                $onedata->accuracy = $therecord->h_accuracy;
                $onedata->errorrate = common::calc_error_rate( $therecord->h_errorcount,$therecord->h_sessionendword);
                $onedata->scrate=common::calc_sc_rate($therecord->h_errorcount,$therecord->h_sccount);
            }
            //linkify WPM(gradenow page) and RecordName(RunningRecord actrivity report)
            if($links) {
                $onedata->wpm=self::make_grading_link($therecord->readaloudid,$therecord->attemptid,$onedata->wpm );
                $onedata->reading=self::make_report_link(constants::M_REPORT_RUNNINGRECORDS,$therecord->readaloudid,$onedata->reading);
            }
            $rows[]=$onedata;
        }
        return [$reportname,$head,$fields,$rows];
    }

    //Users
    public static function fetch_report_users($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_report';
        $head=array();//'First Name','Last Name','Completed','Av. WPM','Av. Quiz','Last Reading','Last WPM','Last Quiz','Last Date'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('colcompleted',constants::M_COMP);
        $head[]=get_string('colavwpm',constants::M_COMP);
        $head[]=get_string('colavqscore',constants::M_COMP);
        $head[]=get_string('collastreading',constants::M_COMP);
        $head[]=get_string('collastwpm',constants::M_COMP);
        $head[]=get_string('collastqscore',constants::M_COMP);
        $head[]=get_string('collastdate',constants::M_COMP);

        $fields=array('userid','lastname','firstname','completed','av_wpm','av_qscore','lastreading','lastwpm','lastqscore','lastdate');
        $rows=array();
        foreach($thecoursedata->courseusers as $theuser){
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}

            $total_wpm= 0;
            $count_wpm= 0;
            $total_qscore= 0;
            $count_qscore= 0;
            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->completed=0;
            $onedata->av_wpm='';
            $onedata->av_qscore='';
            $onedata->lastreading = ' ';
            $onedata->lastwpm = ' ';
            $onedata->lastqscore = ' ';
            $onedata->lastdate = ' ';

            foreach($thecoursedata->userreadings as $thereading){
                if($thereading->userid == $theuser->id){
                    //WPM
                    if($thereading->h_wpm) {
                        $usewpm = $thereading->h_wpm;
                        $total_wpm += $usewpm;
                        $count_wpm++;
                    }else if($thereading->ai_wpm) {
                        $usewpm = $thereading->ai_wpm;
                        $total_wpm += $usewpm;
                        $count_wpm++;
                    }else{
                        $usewpm = '';
                    }

                    //Quiz
                    if($thereading->qscore != null && $thereading->qscore != '') {
                        $total_qscore += $thereading->qscore;
                        $count_qscore++;
                    }
                    $onedata->completed++;
                    $onedata->lastreading = $thereading->name;
                    //linkify WPM
                    if($links) {
                        $onedata->lastwpm = self::make_grading_link($thereading->readaloudid,$thereading->attemptid,$usewpm);
                    }else{
                        $onedata->lastwpm = $usewpm;
                    }
                    $onedata->lastqscore = $thereading->qscore . '%';
                    $onedata->lastdate = date("Y-m-d H:i:s",$thereading->timecreated);
                }
            }
            //compile averages
            if($count_wpm){
                if($total_wpm > 0){
                    $onedata->av_wpm=round($total_wpm/$count_wpm,0);
                }
            }
            if($count_qscore){
                if($total_qscore > 0){
                    $onedata->av_qscore=round($total_qscore/$count_qscore,0) . '%';
                }else{
                    $onedata->av_qscore = '0%';
                }
            }

            $rows[]=$onedata;
        }

        return [$reportname,$head,$fields,$rows];
    }

    //Users
    public static function fetch_klassreport_summary($thecoursedata,$links, $klassmemberids=false, $klass)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_report';
        $head=array();//'First Name','Last Name','Completed','Av. WPM','Av. Quiz','Last Reading','Last WPM','Last Quiz','Last Date'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('collastreading',constants::M_COMP);
        $head[]=get_string('collastwpm',constants::M_COMP);
        $head[]=get_string('collastqscore',constants::M_COMP);
        $head[]=get_string('collastdate',constants::M_COMP);
        $head[]=get_string('colgo',constants::M_COMP);

        $fields=array('userid','lastname','firstname','lastreading','lastwpm','lastqscore','lastdate','go');
        $rows=array();
        foreach($thecoursedata->courseusers as $theuser){
            //if we have klass members but this is not one of them, we do not show it... so continue
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}
            //if we have no klass members at all we quit
            if(!$klassmemberids){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->lastreading = ' ';
            $onedata->lastwpm = ' ';
            $onedata->lastqscore = ' ';
            $onedata->lastdate = ' ';
            $onedata->go = [];

            foreach($thecoursedata->userreadings as $thereading){
                if($thereading->userid == $theuser->id){

                    $onedata->lastreading = $thereading->name;

                    //WPM
                    if ($thereading->h_wpm) {
                        $usewpm = $thereading->h_wpm;
                    } else if ($thereading->ai_wpm) {
                        $usewpm = $thereading->ai_wpm;
                    } else {
                        $usewpm = '';
                    }
                    if($links) {
                        $onedata->lastwpm = self::make_grading_link($thereading->readaloudid,$thereading->attemptid,$usewpm);
                    }else{
                        $onedata->lastwpm = $usewpm;
                    }
                    $onedata->lastqscore = $thereading->qscore . '%';
                    if($links) {
                        $onedata->go = self::go_buttons_data($thereading->courseid, $thereading->userid, $thereading->attemptid, $thereading->readaloudid, $klass);
                    }else{
                        $onedata->go = array();
                    }
                    $onedata->lastdate = \html_writer::div(
                        get_string('ago', 'message', format_time(time() - $thereading->timecreated)),
                        '',
                        array('title' => date("Y-m-d H:i:s",$thereading->timecreated))
                    );
                    break;
                }
            }
            $rows[]=$onedata;
        }

        return [$reportname,$head,$fields,$rows];
    }

    //Klass WPM report
    public static function fetch_klassreport_readingscomplete($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'klass_report_readingscomplete';
        $head=array();//'userid','Last Name','First Name','Total Readings'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('coltotalreadings',constants::M_COMP);

        $fields=array('userid','lastname','firstname','totalreadings');
        $rows=array();

        //loop through users in the course
        foreach($thecoursedata->courseusers as $theuser){
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->totalreadings= ' ';

            //loop through attempts of current user
            $readings_count=0;
            foreach($thecoursedata->userreadings as $thereading){

                if($thereading->userid == $theuser->id){

                    //WPM
                    if ($thereading->qscore == null) {
                        //in this case the attempt is somehow incomplete. Lets bypass it
                        continue;
                    }
                    $readings_count++;

                }
            }
            $onedata->totalreadings= $readings_count;
            $rows[]=$onedata;
        }
        return [$reportname,$head,$fields,$rows];
    }

    //Klass WPM report
    public static function fetch_klassreport_wpm($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'klass_report_wpm';
        $head=array();//'userid','Last Name','First Name','Av. WPM','Last WPM','Benchmark'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('colavwpm',constants::M_COMP);
        $head[]=get_string('collastwpm',constants::M_COMP);
        $head[]=get_string('colbenchmarkfall',constants::M_COMP);
        $head[]=get_string('colbenchmarkwinter',constants::M_COMP);
        $head[]=get_string('colbenchmarkspring',constants::M_COMP);

        $fields=array('userid','lastname','firstname','avwpm','lastwpm','benchmarkfall','benchmarkwinter','benchmarkspring');
        $rows=array();

        //loop through users in the course
        foreach($thecoursedata->courseusers as $theuser){
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->lastwpm = ' ';
            $onedata->avwpm = ' ';
            $onedata->benchmarkfall = ' ';
            $onedata->benchmarkwinter = ' ';
            $onedata->benchmarkspring = ' ';

            //loop through attempts of current user
            $count_wpm=0;
            $total_wpm=0;
            foreach($thecoursedata->userreadings as $thereading){

                if($thereading->userid == $theuser->id){

                    //WPM
                    if ($thereading->h_wpm) {
                        $thewpm = $thereading->h_wpm;
                    } else if ($thereading->ai_wpm) {
                        $thewpm = $thereading->ai_wpm;
                    } else {
                        //in this case the attempt is somehpw incomplete. Lets bypass it
                        continue;
                    }
                    if($count_wpm==0) {
                        $onedata->lastwpm = $thewpm;
                    }
                    $total_wpm+=  is_numeric($thewpm) ? $thewpm : 0;
                    $count_wpm++;

                }
            }
            //compile averages
            if($count_wpm){
                if($total_wpm > 0){
                    $onedata->avwpm=round($total_wpm/$count_wpm,0);
                }
            }
            $onedata->benchmarkfall=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_FALL];
            $onedata->benchmarkwinter=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_WINTER];
            $onedata->benchmarkspring=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_SPRING];
            $rows[]=$onedata;
        }

        return [$reportname,$head,$fields,$rows];
    }

    //Klass accuracy report
    public static function fetch_klassreport_accuracy($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'klass_report_accuracy';
        $head=array();//'userid','Last Name','First Name','Av. WPM','Last WPM','Benchmark'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('colavaccuracy',constants::M_COMP);
        $head[]=get_string('collastaccuracy',constants::M_COMP);


        $fields=array('userid','lastname','firstname','avaccuracy','lastaccuracy');
        $rows=array();

        //loop through users in the course
        foreach($thecoursedata->courseusers as $theuser){
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->lastaccuracy = ' ';
            $onedata->avaccuracy = ' ';

            //loop through attempts of current user
            $count_accuracy=0;
            $total_accuracy=0;
            foreach($thecoursedata->userreadings as $thereading){

                if($thereading->userid == $theuser->id){

                    //WPM
                    if ($thereading->h_accuracy) {
                        $theaccuracy = $thereading->h_accuracy;
                    } else if ($thereading->ai_accuracy) {
                        $theaccuracy = $thereading->ai_accuracy;
                    } else {
                        //in this case the attempt is somehpw incomplete. Lets bypass it
                        continue;
                    }
                    if($count_accuracy==0) {
                        $onedata->lastaccuracy = $theaccuracy;
                    }
                    $total_accuracy+=  is_numeric($theaccuracy) ? $theaccuracy : 0;
                    $count_accuracy++;

                }
            }
            //compile averages
            if($count_accuracy){
                if($total_accuracy > 0){
                    $onedata->avaccuracy=round($total_accuracy/$count_accuracy,0);
                }
            }
            $rows[]=$onedata;
        }

        return [$reportname,$head,$fields,$rows];
    }

    //Users
    public static function fetch_klassreport_qscore($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'klass_report_qscore';
        $head=array();//'userid','Last Name','First Name','Av. QScore','Last QScore','Benchmark'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('colavqscore',constants::M_COMP);
        $head[]=get_string('collastqscore',constants::M_COMP);

        $fields=array('userid','lastname','firstname','avqscore','lastqscore');
        $rows=array();

        //loop through users in the course
        foreach($thecoursedata->courseusers as $theuser){
            if(($klassmemberids && !in_array($theuser->id,$klassmemberids))){continue;}

            $onedata = new \stdClass();
            $onedata->userid=$theuser->id;
            $onedata->fullname=$theuser->fullname;
            $onedata->firstname=$theuser->firstname;
            $onedata->lastname=$theuser->lastname;
            $onedata->lastqscore = ' ';
            $onedata->avqscore = ' ';


            //loop through attempts of current user
            $count_qscore=0;
            $total_qscore=0;
            foreach($thecoursedata->userreadings as $thereading){

                if($thereading->userid == $theuser->id){

                    //Quiz score
                    if ($thereading->qscore !== null) {
                        $theqscore = $thereading->qscore;
                    } else {
                        //in this case the attempt is somehpw incomplete. Lets bypass it
                        continue;
                    }
                    if($count_qscore==0) {
                        $onedata->lastqscore = $theqscore;
                    }
                    $total_qscore+=  is_numeric($theqscore) ? $theqscore : 0;
                    $count_qscore++;

                }
            }
            //compile averages
            if($count_qscore){
                if($total_qscore > 0){
                    $onedata->avqscore=round($total_qscore/$count_qscore,0);
                }else if($count_qscore>0){
                    $onedata->avqscore=0;
                }
            }
            $rows[]=$onedata;
        }

        return [$reportname,$head,$fields,$rows];
    }

    //User WPM report
    public static function fetch_userreport_wpm($thecoursedata,$userid)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_report_wpm';
        $head=array();//'readingid','reading Name','WPM','Benchmark'
        $head[]=get_string('colreadingid',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colwpm',constants::M_COMP);
        $head[]=get_string('colbenchmarkfall',constants::M_COMP);
        $head[]=get_string('colbenchmarkwinter',constants::M_COMP);
        $head[]=get_string('colbenchmarkspring',constants::M_COMP);

        $fields=array('readingid','readingname','wpm','benchmarkfall','benchmarkwinter','benchmarkspring');
        $rows=array();

            //loop through readings in the course
            foreach($thecoursedata->userreadings as $thereading){

                if($thereading->userid == $userid){

                    $onedata = new \stdClass();
                    $onedata->readingid=$thereading->rsid;

                    //WPM
                    if ($thereading->h_wpm) {
                        $thewpm = $thereading->h_wpm;
                    } else if ($thereading->ai_wpm) {
                        $thewpm = $thereading->ai_wpm;
                    } else {
                        //in this case the attempt is somehow incomplete. Lets bypass it
                        continue;
                    }
                    $onedata->wpm = $thewpm;
                    $onedata->readingname = $thereading->name;
                    $onedata->benchmarkfall=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_FALL];
                    $onedata->benchmarkwinter=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_WINTER];
                    $onedata->benchmarkspring=$thecoursedata->wpmbenchmarks[constants::M_WPMBENCHMARK_SPRING];
                    $rows[]=$onedata;
                }
            }



        return [$reportname,$head,$fields,$rows];
    }

    //User Accuracy report
    public static function fetch_userreport_accuracy($thecoursedata,$userid)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_report_accuracy';
        $head=array();//'readingid','reading Name','accuracy','Benchmark'
        $head[]=get_string('colreadingid',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colaccuracy',constants::M_COMP);

        $fields=array('readingid','readingname','accuracy');
        $rows=array();

        //loop through readings in the course
        foreach($thecoursedata->userreadings as $thereading){

            if($thereading->userid == $userid){

                $onedata = new \stdClass();
                $onedata->readingid=$thereading->rsid;

                //Accuracy
                if ($thereading->h_accuracy) {
                    $theaccuracy = $thereading->h_accuracy;
                } else if ($thereading->ai_accuracy) {
                    $theaccuracy = $thereading->ai_accuracy;
                } else {
                    //in this case the attempt is somehow incomplete. Lets bypass it
                    continue;
                }
                $onedata->accuracy = $theaccuracy;
                $onedata->readingname = $thereading->name;
                $rows[]=$onedata;
            }
        }



        return [$reportname,$head,$fields,$rows];
    }

    //User QScore report
    public static function fetch_userreport_qscore($thecoursedata,$userid)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_report_qscore';
        $head=array();//'readingid','reading Name','qscore','Benchmark'
        $head[]=get_string('colreadingid',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colqscore',constants::M_COMP);

        $fields=array('readingid','readingname','qscore');
        $rows=array();

        //loop through readings in the course
        foreach($thecoursedata->userreadings as $thereading){

            if($thereading->userid == $userid){

                $onedata = new \stdClass();
                $onedata->readingid=$thereading->rsid;

                //Accuracy
                if ($thereading->qscore == null) {
                    //in this case the attempt is somehow incomplete. Lets bypass it
                    continue;
                } else {
                    $theqscore = $thereading->qscore;
                }
                $onedata->qscore = $theqscore;
                $onedata->readingname = $thereading->name;
                $rows[]=$onedata;
            }
        }



        return [$reportname,$head,$fields,$rows];
    }



    //Readings
    public static function fetch_report_readings($thecoursedata,$links, $klassmemberids=false)
    {
        $sectiontitle = $thecoursedata->fullname;
        $reportname = 'user_readings_report';
        $head=array();//'First Name','Last Name','Name','WPM',"Quiz","Date");
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colwpm',constants::M_COMP);
        $head[]=get_string('colqscore',constants::M_COMP);
        $head[]=get_string('coldate',constants::M_COMP);

        //massage data a little
        $rows =[];
        foreach($thecoursedata->userreadings as $thereading) {
            if(($klassmemberids && !in_array($thereading->userid,$klassmemberids))){continue;}
            //WPM
            if ($thereading->h_wpm) {
                $usewpm = $thereading->h_wpm;
            } else if ($thereading->ai_wpm) {
                $usewpm = $thereading->ai_wpm;
            } else {
                $usewpm = '';
            }
            //linkify WPM
            if($links) {
                $thereading->usewpm=self::make_grading_link($thereading->readaloudid,$thereading->attemptid,$usewpm);
            }else{
                $thereading->usewpm=$usewpm;
            }

            //qscore
            if($thereading->qscore != null && $thereading->qscore != '') {
                $useqscore = $thereading->qscore . '%';
            }else{
                $useqscore = '';
            }
            $thereading->useqscore=$useqscore;

            //usedata
            $thereading->usedate = date("Y-m-d H:i:s",$thereading->timecreated);

            //users full name
            $thereading->fullname = $thecoursedata->courseusers[$thereading->userid]->fullname;

            $rows[]=$thereading;

        }

        $fields=array('userid','lastname','firstname','name','usewpm','useqscore','usedate');
        return [$reportname,$head,$fields,$rows];
    }


    //By User
    public static function fetch_report_byuser($thecoursedata,$links, $klassmemberids=false)
    {
        $reportname = 'user_readings_report';
        $head=array();//'First Name','Last Name','Name','WPM',"Quiz","Date");
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('colfullname',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colwpm',constants::M_COMP);
        $head[]=get_string('colqscore',constants::M_COMP);
        $head[]=get_string('coldate',constants::M_COMP);

        //massage data a little
        $rows =[];
        foreach($thecoursedata->userreadings as $thereading) {
            if(($klassmemberids && !in_array($thereading->userid,$klassmemberids))){continue;}

            //WPM
            if ($thereading->h_wpm) {
                $usewpm = $thereading->h_wpm;
            } else if ($thereading->ai_wpm) {
                $usewpm = $thereading->ai_wpm;
            } else {
                $usewpm = '';
            }
            //linkify WPM
            if($links) {
                $thereading->usewpm=self::make_grading_link($thereading->readaloudid,$thereading->attemptid,$usewpm);
            }else{
                $thereading->usewpm=$usewpm;
            }

            //qscore
            if($thereading->qscore != null && $thereading->qscore != '') {
                $useqscore = $thereading->qscore . '%';
            }else{
                $useqscore = '';
            }
            $thereading->useqscore=$useqscore;

            //usedata
            $thereading->usedate = date("Y-m-d H:i:s",$thereading->timecreated);

            //users full name
            $thereading->fullname = $thecoursedata->courseusers[$thereading->userid]->fullname;

            $rows[] = $thereading;
        }

        $fields=array('userid','fullname','name','usewpm','useqscore','usedate');
        return [$reportname,$head,$fields,$rows];
    }

    //Course users
    public static function fetch_report_courseusers($thecoursedata,$links)
    {
        $reportname = 'course_users_report';

        $head=array();//'id','First Name','Last Name'
        $head[]=get_string('coluserid',constants::M_COMP);
        $head[]=get_string('collastname',constants::M_COMP);
        $head[]=get_string('colfirstname',constants::M_COMP);


        $fields=array('id','lastname','firstname');
        $rows=$thecoursedata->courseusers;


        return [$reportname,$head,$fields,$rows];

    }

    //Course readings
    public static function fetch_report_coursereadings($thecoursedata,$links, $klassmemberids=false)
    {
        $reportname = 'course_readings_report';
        $head=array();
        $head[]=get_string('colpassagepicture',constants::M_COMP);
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colcompleted',constants::M_COMP);
        $head[]=get_string('colavwpm',constants::M_COMP);
        $head[]=get_string('colavqscore',constants::M_COMP);


        $fields=array('passagepicture','name','completed','av_wpm','av_qscore');
        $rows=array();
        foreach($thecoursedata->coursereadings as $coursereading){
            $row= new \stdClass();
            if($links) {
                $row->name = self::make_grading_link($coursereading->instance, false, $coursereading->name);
            }else{
                $row->name = $coursereading->name;
            }

            //init aggregate values
            $row->av_wpm='';
            $row->av_qscore='';
            $row->completed=0;

            $picurl= common::fetch_passage_picture($coursereading);
            if($picurl) {
                $row->passagepicture = \html_writer::img($picurl, $coursereading->name, array('class' => constants::M_COMP . '_passagepicture'));
            }else{
                $row->passagepicture ='';
            }
            $total_wpm= 0;
            $count_wpm= 0;
            $total_qscore= 0;
            $count_qscore= 0;
            foreach($thecoursedata->userreadings as $userreading) {
                if(($klassmemberids && !in_array($userreading->userid,$klassmemberids))){continue;}

                if ($userreading->readaloudid == $coursereading->instance) {
                    //WPM
                    if ($userreading->h_wpm) {
                        $usewpm = $userreading->h_wpm;
                        $total_wpm += $usewpm;
                        $count_wpm++;
                    } else if ($userreading->ai_wpm) {
                        $usewpm = $userreading->ai_wpm;
                        $total_wpm += $usewpm;
                        $count_wpm++;
                    } else {
                        $usewpm = '';
                    }

                    //Quiz
                    if ($userreading->qscore != null && $userreading->qscore != '') {
                        $total_qscore += $userreading->qscore;
                        $count_qscore++;
                    }
                }
            }
            //compile averages
            if($count_wpm){
                if($total_wpm > 0){
                    $row->av_wpm=round($total_wpm/$count_wpm,0);
                }
            }
            if($count_qscore){
                if($total_qscore > 0){
                    $row->av_qscore=round($total_qscore/$count_qscore,0) . '%';
                }else{
                    $row->av_qscore = '0%';
                }
                $row->completed=$count_qscore;
            }

            $rows[]=$row;
        }
        return [$reportname,$head,$fields,$rows];
    }

    //User Attempts (one user)
    public static function fetch_userreport_attempts($thecoursedata,$links, $klassmemberids=false)
    {
        $reportname = 'one_user_attempts_report';
        $head=array();//'Name','WPM',"Quiz","Date");
        $head[]=get_string('colreadingname',constants::M_COMP);
        $head[]=get_string('colwpm',constants::M_COMP);
        $head[]=get_string('colqscore',constants::M_COMP);
        $head[]=get_string('coldate',constants::M_COMP);
        $head[]=get_string('colgo',constants::M_COMP);

        //the user id. We pass it around in klassmember ids though its a but hacky
        $theuserid= $klassmemberids[0];

        //massage data a little
        $rows =[];
        foreach($thecoursedata->userreadings as $thereading) {
            if($thereading->userid!=$theuserid){continue;}

            //WPM
            if ($thereading->h_wpm) {
                $usewpm = $thereading->h_wpm;
            } else if ($thereading->ai_wpm) {
                $usewpm = $thereading->ai_wpm;
            } else {
                $usewpm = '';
            }
            //WPM
            $thereading->usewpm=$usewpm;


            //qscore
            if($thereading->qscore != null && $thereading->qscore != '') {
                $useqscore = $thereading->qscore . '%';
            }else{
                $useqscore = '';
            }
            $thereading->useqscore=$useqscore;

            //usedata
            $thereading->usedate = date("Y-m-d H:i:s",$thereading->timecreated);

            //go

            if($links) {
              $thereading->go =   self::make_grade_button($thereading->courseid, $thereading->userid, $thereading->attemptid, $thereading->readaloudid);
            }else{
                $thereading->go='';
            }

            $rows[] = $thereading;
        }

        $fields=array('name','usewpm','useqscore','usedate', 'go');
        return [$reportname,$head,$fields,$rows];
    }


}//end of class
