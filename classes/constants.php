<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/16
 * Time: 19:31
 */

namespace block_readaloudteacher;

defined('MOODLE_INTERNAL') || die();

class constants
{
//component name, db tables, things that define app
const M_COMP='block_readaloudteacher';
const M_NAME='readaloudteacher';
const M_URL='/blocks/readaloudteacher';
const M_CLASS='block_readaloudteacher';
const M_RSTABLE='readaloud';
const M_RSCOMP='mod_readaloud';
const M_RSURL='/mod/readaloud/view.php?n=';
const M_RSGRADEURL='/mod/readaloud/grading.php?n=';
const M_RSREPORTSURL='/mod/readaloud/reports.php?report=';
const M_ATTEMPTTABLE='readaloud_attempt';
const M_KLASSTABLE='block_readaloudteacher_klass';
const M_MEMBERTABLE='block_readaloudteacher_memb';
const M_AITABLE='readaloud_ai_result';
const M_THISCOURSE=0;
const M_ENROLLEDCOURSES=1;
const M_ACTIVECOURSES=2;
const M_SHOWACTIVESTUDENTS=0;
const M_SHOWALLSTUDENTS=1;

const M_KLASSDISPLAYNONE=0;
const M_KLASSDISPLAYCUSTOM=1;
const M_KLASSDISPLAYGROUP=2;
const M_KLASSDISPLAYGROUPCUSTOM=3;

const M_KLASS_NONE =0;
const M_KLASS_GROUP =1;
const M_KLASS_CUSTOM =2;

const M_WPMBENCHMARK_FALL=0;
const M_WPMBENCHMARK_WINTER=1;
const M_WPMBENCHMARK_SPRING=2;

const M_REPORT_USERS =0;
const M_REPORT_READINGS =1;
const M_REPORT_BYUSER =2;
const M_REPORT_COURSEUSERS =3;
const M_REPORT_COURSEREADINGS =4;
const M_REPORT_RUNNINGRECORDS = 5;
const M_REPORT_KLASSSUMMARY = 6;
const M_REPORT_USERATTEMPTS = 7;
const M_REPORT_ALLUSERREPORTS = 8;

const M_REPORT_KLASSWPM= 9;
const M_REPORT_KLASSACCURACY= 10;
const M_REPORT_KLASSQSCORE= 11;
const M_REPORT_KLASSREADINGSCOMPLETE= 12;

const M_REPORT_USERWPM = 13;
const M_REPORT_USERACCURACY =14;
const M_REPORT_USERQSCORE =15;


}