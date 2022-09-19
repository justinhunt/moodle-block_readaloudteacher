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
 * @package   block_readaloudteacher
 * @since      Moodle 3.4
 * @copyright  2018 Justin Hunt (https://poodll,com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class common
{

    //this is a helper function to prepare data to be passed to the something_happened event
	public static function fetch_event_data($blockid=0){
        global $USER;
        $config = self::fetch_best_config($blockid);

        if($blockid==0) {
            $eventdata = array(
                'context' => \context_system::instance(0),
            'userid' => 0,
            'relateduserid' => 0,
            'other' => $config->sometext
            );
        }else{

            $eventdata = array(
                'context' => \context_block::instance($blockid),
            'userid' => $USER->id,
            'relateduserid'=> 0,
            'other' => $config->sometext
            );
        }
		return $eventdata;
    }

    public static function fetch_passage_picture($cm){
        global $DB;
       // $reading = $DB->get_record(constants::M_RSTABLE,array('course'=>$cm->course,'id'=>$cm->instance));
       // if(!$reading){return false;}
        $modulecontext = \context_module::instance($cm->id);
        //$filename = $reading->passagepicture;


        $fs = get_file_storage();
        $filearea='passagepicture';//\mod_readaloud\constants::PASSAGEPICTURE_FILEAREA;
        $files = $fs->get_area_files($modulecontext->id,  constants::M_RSCOMP,$filearea);
        foreach ($files as $file) {
            $filename = $file->get_filename();
            if($filename=='.' || $filename==''){continue;}
            $filepath = '/';
            $itemid=0;
            $mediaurl = \moodle_url::make_pluginfile_url($modulecontext->id, constants::M_RSCOMP,
                $filearea, $itemid,
                $filepath, $filename);
            return $mediaurl->__toString();

        }
        //We always take the first file and if we have none, thats not good.
        return false;
        // return "$this->context->id pp $filearea pp $item->id";
    }

    //this merges the local config and admin config settings to make it easy to assume there is a setting
    //and to get it.
    public static function fetch_best_config($blockid=0){
	    global $DB;

        $config = get_config(constants::M_COMP);
        $local_config = false;
        if($blockid > 0) {
            $configdata = $DB->get_field('block_instances', 'configdata', array('id' => $blockid));
            if($configdata){
                $local_config = unserialize(base64_decode($configdata));
            }

            if($local_config){
                $localvars = get_object_vars($local_config);
                foreach($localvars as $prop=>$value){
                    $config->{$prop}=$value;
                }
            }
        }
        return $config;
    }

    /**
     * Fetch all the users attempts on readalouds in a particular course.
     * @param $courseid
     * @param bool $ascendingorder if true, we put the latest attempt last.
     * @return array
     * @throws \dml_exception
     */
    public static function fetch_user_readings($courseid, $ascendingorder = false){
        global $DB;

        $sql ='SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm as h_wpm, rsa.accuracy as h_accuracy, rsa.sessionscore as h_sessionscore,rsa.qscore,rsa.timemodified,';
        $sql .=' rsa.timecreated, rsai.wpm as ai_wpm,rsai.accuracy as ai_accuracy, rsai.sessionscore as ai_sessionscore, rs.id as rsid, rs.name,rs.passagepicture, cm.id as cmid, u.firstname, u.lastname,u.id as userid';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .=' on rs.id=rsa.readaloudid';
        $sql .=' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .=' INNER JOIN {modules} m on m.name="readaloud" ';
        $sql .=' INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .=' INNER JOIN {user} u ON u.id = rsa.userid';

        $sql .=' INNER JOIN {user_enrolments} ue ON u.id=ue.userid' ;
        $sql .=' INNER JOIN {enrol} e ON e.id=ue.enrolid' ;
        $sql .=' INNER JOIN {course} c ON c.id=rsa.courseid AND e.courseid=c.id' ;

        $sql .=' WHERE rsa.courseid = :courseid AND u.deleted=0';
        $sql .=' AND rsa.id IN( SELECT MAX(att.id) FROM {' . constants::M_ATTEMPTTABLE . '}  att';
        $sql .=' WHERE att.userid = rsa.userid AND att.courseid = rsa.courseid  GROUP BY att.readaloudid)';
        $sql .=' ORDER BY timemodified ';
        $ascendingorder ? $sql .= 'ASC' : $sql .='DESC';
        $records = $DB->get_records_sql($sql,array('courseid'=>$courseid));


        //it would be more elegant to use $gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
        //to get grade data, but it would be heaps more DB calls so we fetch all the data in the single SQL above

        return $records;
    }

    /*
   * Fetch all the users attempts on readalouds in a particular course
   *
   */
    public static function fetch_all_attempts($courseid){
        global $DB;

        $sql ='SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm as h_wpm,rsa.sessionscore as h_sessionscore,rsa.qscore,rsa.timemodified,';
        $sql .=' rsa.timecreated, rsai.wpm as ai_wpm,rsai.sessionscore as ai_sessionscore, rs.name,rs.passagepicture, cm.id as cmid, u.firstname, u.lastname,u.id as userid';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .=' on rs.id=rsa.readaloudid';
        $sql .=' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .=' INNER JOIN {modules} m on m.name="readaloud" ';
        $sql .=' INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .=' INNER JOIN {user} u ON u.id = rsa.userid';

        $sql .=' INNER JOIN {user_enrolments} ue ON u.id=ue.userid' ;
        $sql .=' INNER JOIN {enrol} e ON e.id=ue.enrolid' ;
        $sql .=' INNER JOIN {course} c ON c.id=rsa.courseid AND e.courseid=c.id' ;

        $sql .=' WHERE rsa.courseid = :courseid AND u.deleted=0';
        $sql .=' AND rsa.id IN( SELECT MAX(att.id) FROM {' . constants::M_ATTEMPTTABLE . '}  att';
        $sql .=' WHERE att.userid = rsa.userid AND att.courseid = rsa.courseid  GROUP BY att.readaloudid)';
        $sql .=' ORDER BY timemodified DESC';
        $records = $DB->get_records_sql($sql,array('courseid'=>$courseid));


        //it would be more elegant to use $gradenow = new \mod_readaloud\gradenow($latestattempt->id,$modulecontext->id);
        //to get grade data, but it would be heaps more DB calls so we fetch all the data in the single SQL above

        return $records;
    }

    /*
  * Fetch the wpmbenchmarks.
     * we need to get benchmarks from somewhere, they used to be in labelreadseed
     * TODO: we need to store them somewhere with block_readaloudteacher
  *
  */
    public static function fetch_wpmbenchmarks($courseid, $fortemplate = false){
        global $DB;
        //$records = $DB->get_records('labelreadaloud',array('course'=>$courseid));
        $records = false;
        $benchmarks=[];
        if($records){
            $record = array_pop($records);
            $benchmarks[constants::M_WPMBENCHMARK_FALL]=$record->benchmarkfall;
            $benchmarks[constants::M_WPMBENCHMARK_WINTER]=$record->benchmarkwinter;
            $benchmarks[constants::M_WPMBENCHMARK_SPRING]=$record->benchmarkspring;
        }else{
            $benchmarks[constants::M_WPMBENCHMARK_FALL]=0;
            $benchmarks[constants::M_WPMBENCHMARK_WINTER]=0;
            $benchmarks[constants::M_WPMBENCHMARK_SPRING]=0;
        }
        if ($fortemplate) {
            return array(
                array('id' => 'fall', 'name' => get_string('fall', constants::M_CLASS), 'value' => $benchmarks[constants::M_WPMBENCHMARK_FALL]),
                array('id' => 'winter', 'name' => get_string('winter', constants::M_CLASS), 'value' => $benchmarks[constants::M_WPMBENCHMARK_WINTER]),
                array('id' => 'spring', 'name' => get_string('spring', constants::M_CLASS), 'value' => $benchmarks[constants::M_WPMBENCHMARK_SPRING])
            );
        }
        return $benchmarks;
    }

    /*
  * Fetch all the users attempts on readalouds in a particular course
  *
  */
    public static function fetch_runningrecords($courseid){
        global $DB;

        $sql ='SELECT rsa.id as attemptid,rsa.readaloudid,rsa.courseid,rsa.wpm as h_wpm,rsa.errorcount as h_errorcount';
        $sql .= ',rsa.sccount as h_sccount,rsa.sessiontime as sessiontime, rsa.sessionendword as h_sessionendword';
        $sql .= ',rsa.accuracy as h_accuracy, rsa.timecreated, rsai.wpm as ai_wpm, rsai.errorcount as ai_errorcount';
        $sql .= ',rsai.sccount as ai_sccount,rsai.sessionendword as ai_sessionendword,rsai.accuracy as ai_accuracy';
        $sql .= ',rsai.sessionscore as ai_sessionscore, rs.name as reading, cm.id as cmid, u.firstname, u.lastname,u.id as userid';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} rsa INNER JOIN {' . constants::M_RSTABLE . '} rs';
        $sql .=' on rs.id=rsa.readaloudid';
        $sql .=' INNER JOIN {' . constants::M_AITABLE . '} rsai on rsai.attemptid = rsa.id';
        $sql .=' INNER JOIN {modules} m on m.name="readaloud" ';
        $sql .=' INNER JOIN {course_modules} cm ON cm.module = m.id AND cm.instance = rsa.readaloudid';
        $sql .=' INNER JOIN {user} u ON u.id = rsa.userid';

        $sql .=' INNER JOIN {user_enrolments} ue ON u.id=ue.userid' ;
        $sql .=' INNER JOIN {enrol} e ON e.id=ue.enrolid' ;
        $sql .=' INNER JOIN {course} c ON c.id=rsa.courseid AND e.courseid=c.id' ;

        $sql .=' WHERE rsa.courseid = :courseid AND u.deleted=0';
        $sql .=' AND rsa.id IN( SELECT MAX(att.id) FROM {' . constants::M_ATTEMPTTABLE . '}  att';
        $sql .=' WHERE att.userid = rsa.userid AND att.courseid = rsa.courseid  GROUP BY att.readaloudid)';
        $sql .=' ORDER BY timecreated DESC';
        $records = $DB->get_records_sql($sql,array('courseid'=>$courseid));


        return $records;
    }

    /*
     * Fetch all the readings for a course
     */
    public static function fetch_course_readings($courseid){
        $course =get_course($courseid);
        $modinfo = get_fast_modinfo($course);
        $ret = array();

        foreach($modinfo->cms as $cm) {
            if (!($cm->modname==constants::M_RSTABLE)) {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }
            if ($cm->deletioninprogress) {
                continue;
            }
            $ret[]=$cm;
        }
        return $ret;
    }

    /*
    * Fetch all the readings for a course
     * TO DO test this
    */
    public static function fetch_course_users($courseid){
        global $DB;

        $sql ='SELECT DISTINCT u.* ';
        $sql .=' FROM {user} u';
        $sql .=' INNER JOIN {user_enrolments} ue ON u.id=ue.userid' ;
        $sql .=' INNER JOIN {enrol} e ON e.id=ue.enrolid' ;
        $sql .=' INNER JOIN {course} c ON e.courseid=c.id' ;
        $sql .=' WHERE c.id=? AND u.deleted=0';
        $records = $DB->get_records_sql($sql,array($courseid));

        //add full user name
        foreach ($records as $record){
            $record->fullname=fullname($record);
        }
        return $records;
    }

    /*
     * Fetch all the courses for which a user has readaloud attempts
     *
     */
    public static function fetch_courses_with_userattempts($userid){
        global $DB;

        $sql ='SELECT DISTINCT c.id, c.fullname ';
        $sql .=' FROM {' . constants::M_ATTEMPTTABLE . '} a';
        $sql .=' INNER JOIN {course} c ON a.courseid=c.id' ;
        $sql .=' WHERE a.userid =?';
        $records = $DB->get_records_sql($sql,array($userid));
        return $records;
    }

    /*
    * Fetch all the courses for which there are readalouds AND the user is enrolled
    *
    */
    public static function fetch_courses_userenrolled($userid){
        global $DB;

        $sql ='SELECT DISTINCT c.id, c.fullname ';
        $sql .=' FROM {' . constants::M_RSTABLE . '} rs';
        $sql .=' INNER JOIN {course} c ON rs.course=c.id' ;
        $sql .=' INNER JOIN {enrol} e ON e.courseid=c.id' ;
        $sql .=' INNER JOIN {user_enrolments} ue ON e.id=ue.enrolid' ;
        $sql .=' WHERE ue.userid=?';
        $records = $DB->get_records_sql($sql,array($userid));
        return $records;
    }

    public static function fetch_showcourses_options(){
        $options = Array(
            constants::M_THISCOURSE=>get_string('thiscourse',constants::M_COMP),
            constants::M_ENROLLEDCOURSES=>get_string('enrolledcourses',constants::M_COMP),
            constants::M_ACTIVECOURSES=>get_string('activecourses',constants::M_COMP)
        );
        return $options;
    }

    public static function fetch_showstudents_options(){
        $options = Array(
            constants::M_SHOWALLSTUDENTS=>get_string('showallstudents',constants::M_COMP),
            constants::M_SHOWACTIVESTUDENTS=>get_string('showactivestudents',constants::M_COMP)
        );
        return $options;
    }

    public static function fetch_klassdisplay_options(){
        $options = Array(
                constants::M_KLASSDISPLAYNONE=>get_string('dontshowklasses',constants::M_COMP),
                constants::M_KLASSDISPLAYCUSTOM=>get_string('showcustomklasses',constants::M_COMP),
                constants::M_KLASSDISPLAYGROUP=>get_string('showgroupklasses',constants::M_COMP),
                constants::M_KLASSDISPLAYGROUPCUSTOM=>get_string('showcustomgroupklasses',constants::M_COMP),

        );
        return $options;
    }

    /*
   * Fetch all the klasses for this teacher and course
   *
   */
    public static function fetch_klasses($userid,$courseid,$klassdisplay){

        switch($klassdisplay){
            case constants::M_KLASSDISPLAYCUSTOM:
                $klasses = klass_custom::fetch_klasses($userid,$courseid);
                break;
            case constants::M_KLASSDISPLAYGROUP:
                $klasses = klass_group::fetch_klasses($userid,$courseid);
                break;
            case constants::M_KLASSDISPLAYGROUPCUSTOM:
                $group_klasses = klass_group::fetch_klasses($userid,$courseid);
                $custom_klasses = klass_custom::fetch_klasses($userid,$courseid);
                $klasses = array_merge($group_klasses,$custom_klasses);
                break;
            case constants::M_KLASSDISPLAYNONE:
            default:
                $klasses = [];
        }
        return $klasses;
    }

    //calculate the Error rate
    //see https://www.readinga-z.com/helpful-tools/about-running-records/scoring-a-running-record/
    public static function calc_error_rate($errorcount,$wordcount){
        if($errorcount > 0 && $wordcount > 0) {
            $ret = "1:" . round($wordcount / $errorcount);
        }else if($wordcount > 0){
            $ret = "-:" . $wordcount;
        }else{
            $ret = "-:-";
        }
        return $ret;
    }

    //calculate the Self Correction rate
    //See https://www.readinga-z.com/helpful-tools/about-running-records/scoring-a-running-record/
    public static function calc_sc_rate($errorcount,$sccount){
        if($errorcount > 0 && $sccount > 0) {
            $ret = "1:" . round(($errorcount + $sccount) / $sccount);
        }else if($errorcount > 0){
            $ret = "-:" . $errorcount;
        }else{
            $ret = "-:-";
        }
        return $ret;
    }
}//end of class
