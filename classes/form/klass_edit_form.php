<?php

namespace block_readaloudteacher\form;

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Form for Alternatives
 *
 * @package    block_readaloudteacher
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');

use \block_readaloudteacher\constants;

class klass_edit_form extends \moodleform {

    /**
     * Define the klass edit form
     */
    public function definition() {

        $mform = $this->_form;
        $klass = $this->_customdata['data'];
        $courseid = $this->_customdata['courseid'];

        $mform->addElement('text', 'name', get_string('klassname', constants::M_COMP), 'maxlength="254" size="50"');
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        /*
         * These (idnumber and visible( could be exposed to user (currently they are hidden).
         * If you do this you need to enable the edit page for a klass
         * Currently we just use an inplace edit for Klassname.
         */
         /*
        $mform->addElement('text', 'idnumber', get_string('idnumber', constants::M_COMP), 'maxlength="254" size="50"');
        $mform->setType('idnumber', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'visible', get_string('visible', constants::M_COMP));
        $mform->setDefault('visible', 1);
        */

        $mform->addElement('hidden', 'idnumber',$courseid);
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->setDefault('idnumber', '');

        $mform->addElement('hidden', 'visible',$courseid);
        $mform->setType('visible', PARAM_INT);
        $mform->setDefault('visible', 1);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);



        $mform->addElement('hidden', 'courseid',$courseid);
        $mform->setType('courseid', PARAM_INT);


        if (isset($this->_customdata['returnurl'])) {
            $mform->addElement('hidden', 'returnurl', $this->_customdata['returnurl']->out_as_local_url());
            $mform->setType('returnurl', PARAM_LOCALURL);
        }

        $this->add_action_buttons();

        $this->set_data($klass);
    }

}