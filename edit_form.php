<?php

use block_readaloudteacher\constants;
use block_readaloudteacher\common;

class block_readaloudteacher_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        //get admin settings config
        $config =get_config(constants::M_COMP);

        $options = common::fetch_showcourses_options();
        $mform->addElement('select', 'config_showcourses', get_string('showcourses', constants::M_COMP),$options);
        $mform->setDefault('config_showcourses',constants::M_THISCOURSE);

        $options = common::fetch_showstudents_options();
        $mform->addElement('select', 'config_showreadings', get_string('showreadings', constants::M_COMP),$options);
        $mform->setDefault('config_showreadings',constants::M_SHOWACTIVESTUDENTS);

        $options = common::fetch_klassdisplay_options();
        $mform->addElement('select', 'config_klassdisplay', get_string('klassdisplay', constants::M_COMP),$options);
        $mform->setDefault('config_klassdisplay',constants::M_KLASSDISPLAYNONE);

    }
}