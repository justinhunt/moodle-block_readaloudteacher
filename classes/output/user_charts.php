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
 * Read seed teacher block, user_charts.
 *
 * @package block_readaloudteacher
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_readaloudteacher\output;

use block_readaloudteacher\common;
use block_readaloudteacher\constants;
use block_readaloudteacher\highcharts_data_series;

defined('MOODLE_INTERNAL') || die();

/**
 * Prepares data for user report (section at top with overall stats then charts if required).
 *
 * @package block_readaloudteacher
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_charts implements \renderable, \templatable
{


    /**
     * Which user are we looking at.
     * @var mixed
     */
    private $user;


    /**
     * @var
     */
    private $courseid;

    /**
     * If we want to display a button to go back to a class report, what is the klass.
     * @var
     */
    private $returnklass;

    /**
     * Are we in charts view (or table view).
     * @var
     */
    private $ischartsview;

    /**
     * user_charts constructor
     */
    public function __construct($userid, $courseid, $returnklass, $ischartsview = true) {
        global $DB;
        $this->user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $this->courseid = $courseid;
        $this->returnklass = $returnklass;
        $this->ischartsview = $ischartsview;
    }

    /**
     * Export the data for the mustache template.
     * @param \renderer_base $output
     * @return array|\stdClass
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE;
        $context['ischartsview'] = $this->ischartsview;
        $totalscores = [];
        $totalreadings = 0;

        // Now iterate through all course readings and add data to the user of interest.
        // These are in ascending order so that the last one is always the latest.
        $strings = [];
        foreach(['colwpm', 'wordsperminute', 'quiz', 'accuracy'] as $key) {
            $strings[$key] = get_string($key, constants::M_CLASS);
            $totalscores[$key] = 0;
        }

        $highchartsdataobjects = $this->get_highcharts_objects($strings);

        // Now go through the data and build the series.
        $context['readings-completed'] = 0;
        $userattemnpthistory = [];
        $readings = common::fetch_user_readings($this->courseid, true);
        foreach($readings as $reading) {
            if ($reading->userid == $this->user->id) {
                $aihumanscores = $this->ai_human_scores($reading);
                $scores = array(
                    'wordsperminute' => $aihumanscores['wordsperminute'],
                    'quiz' => $aihumanscores['quiz'],
                    'accuracy' => $aihumanscores['accuracy']
                );

                // Now add the reading to each of the three data series.
                foreach(['wordsperminute', 'quiz', 'accuracy'] as $chart) {
                    $scoresuffix = $chart === 'wordsperminute' ? ' ' . $strings['colwpm'] : '%';
                    $highchartsdataobjects[$chart]->add_data_point(
                        $reading->name,
                        $reading->attemptid,
                        $scores[$chart],
                        \html_writer::div($reading->name) . \html_writer::div($scores[$chart] . $scoresuffix, 'tooltipscore') ,
                        ['isDrillDown' => false, 'isUserChart' => true]
                    );
                    $totalscores[$chart] += $scores[$chart];
                }
                $totalreadings++;

                // For user attempt table
                $userattemnpthistory[] = array(
                    'readingname' => $reading->name,
                    'date' => date("j/n/Y", $reading->timecreated),
                    'wordsperminute' => $aihumanscores['wordsperminute'],
                    'quiz' => $aihumanscores['quiz'],
                    'accuracy' => $aihumanscores['accuracy'],
                    'isaigrade' => $aihumanscores['gradedby'] === 'ai',
                    'attemptid' => $reading->attemptid,
                    'readaloudid' => $reading->readaloudid
                );
            }
        };

        $context['overview_data'] = array(
            'userid' => $this->user->id,
            'userfullname' => fullname($this->user),
            'courseid' => $this->courseid,
            'userimage' => $output->user_picture($this->user, array('size' => 100, 'link' => true)),
            'returnklass' => $this->returnklass,
            'indicators' => [
                [
                    'id' => 'totalreadings',
                    'title' => get_string('totalreadings',
                        constants::M_CLASS), 'value' => $totalreadings
                ],
                [
                    'id' => 'avwpm',
                    'title' => get_string('averagewordsperminute', constants::M_CLASS),
                    'value' => $this->average_score($totalscores['wordsperminute'], $totalreadings)
                ],
                ['id' => 'avaccuracy',
                    'title' => get_string('averageaccuracy',
                        constants::M_CLASS),
                    'value' => $this->average_score($totalscores['accuracy'], $totalreadings)]
                ,
                ['id' => 'avquiz',
                    'title' => get_string('averagequiz', constants::M_CLASS),
                    'value' => $this->average_score($totalscores['quiz'], $totalreadings)
                ]
            ]
        );
        foreach($highchartsdataobjects as $item) {
            $context['highchartsdatajson'][] = $item->export_json();
        }

        // WPM Benchmarks
        $context['wpmbenchmarks'] = common::fetch_wpmbenchmarks($this->courseid, true);
        $context['wpmbenchmarksjson'] = json_encode($context['wpmbenchmarks']);
        $context['userattempts'] = array_reverse($userattemnpthistory);
        $context['returnurl'] = urlencode($PAGE->url);
        return $context;
        }

    /**
     * Work out whether the scores were determied by human or AI and return them.
     * @param $reading
     * @return array
     */
        private function ai_human_scores($reading) {
            return array(
                'gradedby' => is_numeric($reading->h_wpm) && $reading->h_wpm ? 'human' : 'ai',
                'wordsperminute' => is_numeric($reading->h_wpm) && $reading->h_wpm ? (int)$reading->h_wpm : (int)$reading->ai_wpm,
                'quiz' => (int)$reading->qscore,
                'accuracy' => is_numeric($reading->h_accuracy) ? (int)$reading->h_accuracy : (int)$reading->ai_accuracy,
            );
        }

        private function average_score($value, $total) {
            if ($total == 0) {
                return 0;
            } else {
                return round($value / $total);
            }
        }
    /**
     * Prepare Highcharts data series objects.
     * @return highcharts_data_series[]
     */
    private function get_highcharts_objects($strings) {
        $objects = [];
        foreach(['wordsperminute', 'quiz', 'accuracy'] as $chart) {
            $objects[$chart] = new highcharts_data_series(
                $strings[$chart] . ' (' . fullname($this->user) . ')',
                'column', // This will change to area once there is > 1 point (see highcharts_data_series->add_data_point().
                $chart . '-user-' . $this->user->id,
                $chart,
                '',
                false,
                true,
                false
            );
        }
        return $objects;
    }
}
