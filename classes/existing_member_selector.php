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
 * Klass member picker for block_readaloudteacher plugin
 *
 * @package    block_readaloudteacher
 * @copyright  Justin Hunt (https://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_readaloudteacher;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/selector/lib.php');


class existing_member_selector extends \user_selector_base {

    protected $klassid;
    protected $courseid;

    public function __construct($name, $options) {
        $this->klassid = $options['klassid'];
        $this->courseid = $options['courseid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param string $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        // By default wherecondition retrieves all users except the deleted, not confirmed and guest.
        list($wherecondition, $params) = $this->search_sql($search, 'u');
        $params['klassid'] = $this->klassid;
        $params['courseid'] = $this->courseid;

        $fields      = 'SELECT ' . $this->required_fields_sql('u');
        $countfields = 'SELECT COUNT(1)';

        $sql = " FROM {user} u ";
        $sql .= " INNER JOIN {" . constants::M_MEMBERTABLE .  "} m ON (m.memberuserid = u.id AND m.klassid = $this->klassid) ";
        $sql .=" INNER JOIN {user_enrolments} ue ON u.id=ue.userid" ;
        $sql .=" INNER JOIN {enrol} e ON (e.id=ue.enrolid AND e.courseid= $this->courseid)" ;
        $sql .=" INNER JOIN {course} c ON (c.id= $this->courseid)" ;
        $sql .= " WHERE $wherecondition";

        list($sort, $sortparams) = users_order_by_sql('u', $search, $this->accesscontext);
        $order = ' ORDER BY ' . $sort;

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > $this->maxusersperpage) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, array_merge($params, $sortparams));

        if (empty($availableusers)) {
            return array();
        }


        if ($search) {
            $groupname = get_string('currentusersmatching', constants::M_COMP, $search);
        } else {
            $groupname = get_string('currentusers', constants::M_COMP);
        }

        return array($groupname => $availableusers);

    }

    protected function get_options() {
        $options = parent::get_options();
        $options['klassid'] = $this->klassid;
        $options['courseid'] = $this->courseid;
        $options['file'] = 'blocks/readaloudteacher/classes/existing_member_selector.php';
        return $options;
    }
}
