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
 
class klass_group extends klass
{

    public $id=0;
    public $courseid=0;
    public $name='';
    public $visible=true;
    public $timecreated=0;
    public $timemodified=0;
    public $type = constants::M_KLASS_GROUP;
    public $typestring = 'group';

    /**
     * Creates a new Klass activity from a klass row
     *
     * @param stdclass $id a klass row id
     * @return klass the new klass object
     */
    static public function fetch_from_id($groupid) {
        global $DB;

        $group = groups_get_group($groupid, $fields='*', $strictness=IGNORE_MISSING);
        if($group) {
            $klass =  new klass_group(null,$group->courseid,$group->name,true,$group->idnumber);
            $klass->id=$group->id;
            return $klass;
        }else{
            return false;
        }
    }

    /**
     * Creates a new Klass activity from a klass row
     *
     * @param stdclass $instance a row from the  klass
     * @return klass the new klass object
     */
    static public function fetch_from_record($group) {
        $group->visible=true;
        return new klass_group($group);
    }

    /**
     * Can the members of this class be administered from within app (ie this class has 'save' etc)
     *
     * @return boolean whether this can be administered
     */
    public function can_manage_members(){
        return false;
    }

    /*
      * Fetch all the klass member ids
      *
      */
    public function fetch_klassmemberids(){
        $ret = [];
        $fields =groups_get_members($this->id);
        if($fields){
            $ret = array_keys($fields);
        }
        return $ret;
    }

    /*
    * Fetch all the klass members
    *
    */
    public function fetch_klassmembers(){
        $members = groups_get_members($this->id);
        return $members;

    }


    /*
     * Count the number of members in the klass
     */
    public function count_klassmembers(){
        global $DB;

        $count = count(groups_get_members($this->id));

        return $count;
    }

    /*
    * Fetch all the klasses for this teacher and course
    *
    */
    public static function fetch_klasses($userid,$courseid){
        $group_records = groups_get_all_groups($courseid, $userid);
        $klasses=[];
        foreach($group_records as $group){
            $klasses[] = self::fetch_from_record($group);
        }
        return $klasses;
    }

}//end of class
