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
 * Contains class core_cohort\output\cohortname
 *
 * @package   block_readaloudteacher
 * @copyright 2019 Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_readaloudteacher\output;

use \block_readaloudteacher\constants;
use \block_readaloudteacher\klass_custom;

use lang_string;

/**
 * Class to prepare a klass name for display.
 *
 * @package   block_readaloudteacher
 * @copyright 2019 Justin Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class klassname extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param stdClass $klass
     */
    public function __construct($klass) {
        $coursecontext = \context_course::instance($klass->courseid);
        $editable = has_capability('block/readaloudteacher:manageklass', $coursecontext) &&
                $klass->can_manage_members();
        $displayvalue = format_string($klass->name, true, array('context' => $coursecontext));
        parent::__construct(constants::M_COMP, 'klassname', $klass->id, $editable,
            $displayvalue,
            $klass->name,
            new lang_string('editklassname', constants::M_COMP),
            new lang_string('newnamefor', constants::M_COMP, $displayvalue));
    }

    /**
     * Updates klass name and returns instance of this object
     *
     * @param int $klassid
     * @param string $newvalue
     * @return static
     */
    public static function update($klassid, $newvalue) {
        $klass = klass_custom::fetch_from_id($klassid);
        $coursecontext = \context_course::instance($klass->courseid);
        require_capability('block/readaloudteacher:manageklass', $coursecontext);
        $newvalue = clean_param($newvalue, PARAM_TEXT);
        if (strval($newvalue) !== '') {
            $klass->name = $newvalue;
            $klass->save();
        }
        $tmpl = new self($klass);
        return $tmpl;
    }
}
