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
 * Library of functions and constants for block_readaloudteacher
 *
 * @package block_readaloudteacher
 * @copyright  2019 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function block_readaloudteacher_inplace_editable($itemtype, $itemid, $newvalue) {
    \external_api::validate_context(context_system::instance());
    if ($itemtype === 'klassname') {
        return \block_readaloudteacher\output\klassname::update($itemid, $newvalue);
    }
}

function block_readaloudteacher_get_fontawesome_icon_map() {
    return [
        'block_readaloudteacher:bar-chart' => 'fa-bar-chart',
        'block_readaloudteacher:bar-cog' => 'fa-cog',
        'block_readaloudteacher:bar-star-o' => 'fa-star-o',
        'block_readaloudteacher:users' => 'fa-users',
        'block_readaloudteacher:chevron-up' => 'fa-chevron-up',
        'block_readaloudteacher:chevron-left' => 'fa-chevron-left',
        'block_readaloudteacher:cogs' => 'fa-cogs',
        'block_readaloudteacher:pencil' => 'fa-pencil',
    ];
}