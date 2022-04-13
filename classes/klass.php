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
 
abstract class klass
{

    public $id=0;
    public $courseid=0;
    public $name='';
    public $idnumber='';
    public $visible=true;
    public $timecreated=0;
    public $timemodified=0;

    /**
     * Creates a new Klass class
     *
     * @param stdclass $instance a row from the  klass
     * @param stdclass $courseid
     * @param stdclass $name
     * @param stdclass $visible
     * @param stdclass $idnumber
     * @return klass the new klass object
     */
    public function __construct($instance=null, $courseid=null, $name=null, $visible=true,$idnumber='') {

        $fields= self::get_fields();

        if ($instance) {
            foreach($fields as $field){
                if(isset($instance->{$field})) {
                    $this->$field = $instance->$field;
                }
            }
        }else {
            if ($courseid) {
                $this->courseid = $courseid;
            }
            if ($name) {
                $this->name = $name;
            }
            if ($visible) {
                $this->visible = $visible;
            }
            if ($idnumber) {
                $this->idnumber = $idnumber;
            }
        }
        $this->timecreated=time();
        $this->timemodified=$this->timecreated;
    }

    public static function get_fields(){
        return ['id','courseid','name','idnumber','visible','timecreated','timemodified'];
    }

    public function can_manage_members(){}


    /**
     * Fetches a Klass activity from a klass id
     *
     * @param stdclass $id a klass row id
     * @return klass the new klass object
     */
    static public function fetch_from_id($klassid) {}

    /**
     * Fetches a new Klass activity from a klass row
     *
     * @param stdclass $record a klass record
     * @return klass the new klass object
     */
    static public function fetch_from_record($record) {}

    /*
      * Fetch all the klass member ids
      *
      */
    public function fetch_klassmemberids(){}

    /*
     * Count the number of members in the klass
     */
    public function count_klassmembers(){}

    /*
      * Fetch all the klass members
      *
      */
    public function fetch_klassmembers(){}

    /*
    * Fetch all the klasses for this teacher and course
    *
    */
    public static function fetch_klasses($userid,$courseid){}

}//end of class
