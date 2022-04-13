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
 * Block readaloudteacher
 *
 * @package    block_readaloudteacher
 * @copyright  Justin Hunt <justin@poodll.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_readaloudteacher\constants;
use block_readaloudteacher\common;

defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(constants::M_COMP . '_config_header',
        get_string('headerconfig', constants::M_COMP),
        get_string('descconfig', constants::M_COMP)));

    $options= common::fetch_showcourses_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/showcourses',
        get_string('showcourses', constants::M_COMP),
        get_string('showcourses_desc', constants::M_COMP),
        constants::M_THISCOURSE,$options));

    $options= common::fetch_showstudents_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/showstudents',
        get_string('showstudents', constants::M_COMP),
        get_string('showstudents_desc', constants::M_COMP),
        constants::M_SHOWALLSTUDENTS,$options));

    $options= common::fetch_klassdisplay_options();
    $settings->add(new admin_setting_configselect(constants::M_COMP . '/klassdisplay',
            get_string('klassdisplay', constants::M_COMP),
            '',
            constants::M_KLASSDISPLAYNONE,$options));


}