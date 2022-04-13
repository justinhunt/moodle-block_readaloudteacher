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
 * This is a class containing management features for Klasses
 *
 * @package   block_readaloudteacher
 * @since      Moodle 3.4
 * @copyright  2018 Justin Hunt (https://poodll,com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class klass_custom extends klass
{
    public $id=0;
    public $courseid=0;
    public $name='';
    public $idnumber='';
    public $visible=true;
    public $timecreated=0;
    public $timemodified=0;

    public $type = constants::M_KLASS_CUSTOM;
    public $typestring = 'custom';

    /**
     * Creates a new Klass class from attributes passed in
     *
     * @param stdclass $courseid
     * @param stdclass $name
     * @param stdclass $isgroups
     * @param stdclass $visible
     * @param stdclass $idnumber
     * @return klass the new klass object
     */
    static public function create($courseid=null, $name=null, $visible=null,$idnumber=null) {
        return new klass_custom(null, $courseid, $name, $visible,$idnumber);
    }

    /**
     * Creates a new Klass activity from a klass row
     *
     * @param stdclass $instance a row from the  klass
     * @return klass the new klass object
     */
    static public function fetch_from_record($record=null) {
        return new klass_custom($record);
    }

    /**
     * Creates a new Klass activity from a klass row
     *
     * @param stdclass $id a klass row id
     * @return klass the new klass object
     */
    static public function fetch_from_id($klassid) {
        global $DB;

        $record = $DB->get_record(constants::M_KLASSTABLE, array('id'=>$klassid), '*', MUST_EXIST);
        if($record) {
            return new klass_custom($record);
        }else{
            return false;
        }
    }

    /**
     * Can the members of this class be administered from within app (ie this class has 'save' etc)
     *
     * @return boolean whether this can be administered
     */
    public function can_manage_members(){
        return true;
    }

         /*
         * Save this class member
         *
         */
    public function save($userid=0) {
        global $DB, $USER;
        $fields = self::get_fields();
        $klass= new \stdClass();
         foreach($fields as $field){
           $klass->$field = $this->$field;
         }
        if($klass->id){
            $klass->timemodified=time();
            $ret = $DB->update_record(constants::M_KLASSTABLE,$klass);
        }else{
            if ($userid) {
                $klass->userid = $userid;
            } else {
                $klass->userid = $USER->id;
            }
            $klass->id=null;
            $klass->timecreated=time();
            $klass->timemodified=$klass->timecreated;
            $ret = $DB->insert_record(constants::M_KLASSTABLE,$klass);
            if($ret) {
                $this->id = $ret;
            }
        }

        return $ret;
    }

    /*
     * Remove this klass
     *
     */
    public function delete(){
        global $DB;
        if($this->id) {
            $DB->delete_records(constants::M_MEMBERTABLE, array('klassid' => $this->id));
            $DB->delete_records(constants::M_KLASSTABLE, array('id' => $this->id));
        }
        return;
    }

    /*
     * Add class member
     *
     */
    public function add_member($memberuserid){
        global $DB;
        $ret = false;

        //if klass was not saved yet, we save it.
        if(!$this->id){
            $saved_ok =$this->save();
            if(!$saved_ok){return $ret;}
        }

        if($DB->record_exists(constants::M_MEMBERTABLE,array('klassid'=>$this->id,'memberuserid'=>$memberuserid))){
            return $ret;
        }
        $member = new \stdClass();
        $member->klassid=$this->id;
        $member->memberuserid=$memberuserid;
        $member->courseid=$this->courseid;
        $member->timemodified=time();
        $member->timecreated=time();
        $ret = $DB->insert_record(constants::M_MEMBERTABLE,$member);

        return $ret;
    }

    /*
      * Remove class member
      *
      */
    public function remove_member($memberuserid){
        global $DB;
        $ret = false;
        if(!$this->id){
            return $ret;
        }
        if(!$DB->record_exists(constants::M_MEMBERTABLE,array('klassid'=>$this->id,'memberuserid'=>$memberuserid))){
            return $ret;
        }

        $ret = $DB->delete_records(constants::M_MEMBERTABLE,array('klassid'=>$this->id,'memberuserid'=>$memberuserid));

        return $ret;
    }


    /*
      * Fetch all the klass member ids
      *
      */
    public function fetch_klassmemberids(){
        global $DB;

        $fields = $DB->get_fieldset_select(constants::M_MEMBERTABLE, 'memberuserid', 'klassid=:klassid',
                array('klassid' => $this->id));
        if ($fields) {
            $ret = array_values($fields);
        } else {
            $ret = [];
        }

        return $ret;
    }

    /*
    * Fetch all the klass members
    *
    */
    public function fetch_klassmembers(){
        global $DB;

        $memberids=$this->fetch_klassmemberids();
        //Moodle stringifies the params to sql call [sigh] so we have to hard code it in the sql
        /*
        $members = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id in (:memberids)',
                array('memberids'=>implode(',',$memberids)));
        */
        $members = $DB->get_records_sql('SELECT * FROM {user} u WHERE u.id in (' .implode(',',$memberids) . ')');
        return $members;

    }

    /*
     * Count the number of members in the klass
     */
    public function count_klassmembers(){
        global $DB;
        $count = $DB->count_records(constants::M_MEMBERTABLE, array('klassid' => $this->id));
        return $count;
    }

    /*
    * Fetch all the klasses for this teacher and course
    *
    */
    public static function fetch_klasses($userid,$courseid){
        global $DB;
        $records = $DB->get_records(constants::M_KLASSTABLE,array('userid'=>$userid,'courseid'=>$courseid));
        $klasses=[];
        foreach($records as $record){
            $klasses[] = self::fetch_from_record($record);
        }
        return $klasses;
    }

}//end of class
