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
 * Upgrade script for the quiz module.
 *
 * @package    block_readaloudteacher
 * @copyright  2019 Justin Hunt (https://poodll.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \block_readaloudteacher\constants;

/**
 * ReadAloud Teacher block upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_block_readaloudteacher_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019071800) {

        // Define table klass to be created.
        $table = new xmldb_table(constants::M_KLASSTABLE);

        // Adding fields to table klass
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');


        // Adding keys to table klass
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        //not brave enough to do these
        //$table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        //$table->add_index('courseid-userid', XMLDB_INDEX_UNIQUE, array('courseid', 'userid'));

        // Conditionally launch create table
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table for klass member
        $table = new xmldb_table(constants::M_MEMBERTABLE);

        // Adding fields to table klass member
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('klassid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('memberuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');


        // Adding keys to table klass member
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for klass member
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // savepoint reached.
        upgrade_plugin_savepoint(true, 2019071800, 'block', constants::M_NAME);
    }
    if ($oldversion < 2019122600) {

        // Define table klass to be created.
        $table = new xmldb_table(constants::M_KLASSTABLE);

        //add idnumber
        $field = new xmldb_field('idnumber', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // savepoint reached.
        upgrade_plugin_savepoint(true, 2019122600, 'block', constants::M_NAME);
    }

        return true;
}
