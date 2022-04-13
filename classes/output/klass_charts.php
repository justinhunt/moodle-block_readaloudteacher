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
 * Read seed teacher block, klass charts.
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
 * Prepares data for klass report (section at top with overall stats then charts if required).
 *
 * @package block_readaloudteacher
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class klass_charts implements \renderable, \templatable
{

    /**
     * The details relating to this klass (moodle group) of students.
     * @var
     */
    private $klass;

    /**
     * Data relating to student performance in this course.
     * @var
     */
    private $thecoursedata;

    /**
     * If we just want to generate data for the top section of the report.
     * @var bool
     */
    private $overviewonly;

    /**
     * Whether we are on the course screen (multiple klasses) or not.
     * @var bool
     */
    private $iscoursescreen;

    /**
     * The ids of the members of this klass (Moodle group).
     * @var
     */
    private $klassmemberids;

    /**
     * @var
     */
    private $context;

    /**
     * Whether we are showig charts view (or tables view).
     * @var
     */
    private $ischartsview;

    /**
     * klass_charts constructor
     */
    public function __construct($klass, $thecoursedata, $overviewonly = false, $iscoursescreen = false, $ischartsview = true)
    {
        $this->klass = $klass;
        $this->thecoursedata = $thecoursedata;
        $this->iscoursescreen = $iscoursescreen;
        $this->overviewonly = $overviewonly;
        $this->klassmemberids= $klass->fetch_klassmemberids();
        $this->context = \context_course::instance($thecoursedata->id);
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

        $context['klass_overview_data'] = $this->get_klass_overview_data();
        $context['ischartsview'] = $this->ischartsview;
        if ($this->overviewonly) {
            return $context;
        }

        $classmembers = $this->get_class_members($output);
        $data = array();
        $data['users'] = $classmembers;

        // Populate empty data.
        foreach ($classmembers as $classmember) {
            $data['wordsperminute'][$classmember['userid']] = array(
                'totalscore' => 0,
                'all' => [],
                'latestscore' => null,
                'average' => null
            );
            $data['quiz'][$classmember['userid']] = array(
                'totalscore' => 0,
                'all' => [],
                'latestscore' => null,
                'average' => null
            );
            $data['accuracy'][$classmember['userid']] = array(
                'totalscore' => 0,
                'all' => [],
                'latestscore' => null,
                'average' => null
            );
        }

        $highchartsdataobjects = $this->get_highcharts_objects();

        // Now iterate through all course readings and add data to each user of interest.
        // These are in ascending order so that the last one is always the latest.
        $strings = [];
        foreach(['colwpm', 'wordsperminute', 'quiz', 'accuracy'] as $key) {
            $strings[$key] = get_string($key, constants::M_CLASS);
        }

        foreach(common::fetch_user_readings($this->thecoursedata->id, true) as $reading) {
            if (in_array($reading->userid, $this->klassmemberids)){
                $data['users'][$reading->userid]['readings-completed']++;

                // Add WPM to data.
                $data['wordsperminute'][$reading->userid];
                if (is_numeric($reading->h_wpm) && $reading->h_wpm) {
                    $data['wordsperminute'][$reading->userid]['totalscore'] += (int)$reading->h_wpm;
                    $data['wordsperminute'][$reading->userid]['all'][] = (int)$reading->h_wpm;
                    $data['wordsperminute'][$reading->userid]['latestscore'] = (int)$reading->h_wpm;
                } else if (is_numeric($reading->ai_wpm)) {
                    $data['wordsperminute'][$reading->userid]['totalscore'] += (int)$reading->ai_wpm;
                    $data['wordsperminute'][$reading->userid]['all'][] = (int)$reading->ai_wpm;
                    $data['wordsperminute'][$reading->userid]['latestscore'] = (int)$reading->ai_wpm;
                }

                // Add Accuracy to data.

                if (is_numeric($reading->h_accuracy)) {
                    $data['accuracy'][$reading->userid]['totalscore'] += (int)$reading->h_accuracy;
                    $data['accuracy'][$reading->userid]['all'][] = (int)$reading->h_accuracy;
                    $data['accuracy'][$reading->userid]['latestscore'] = (int)$reading->h_accuracy;
                } else if (is_numeric($reading->ai_wpm)) {
                    $data['accuracy'][$reading->userid]['totalscore'] += (int)$reading->ai_accuracy;
                    $data['accuracy'][$reading->userid]['all'][] = (int)$reading->ai_accuracy;
                    $data['accuracy'][$reading->userid]['latestscore'] = (int)$reading->ai_accuracy;
                }

                // Add Quiz to data.

                if (is_numeric($reading->qscore)) {
                    $data['quiz'][$reading->userid]['totalscore'] += $reading->qscore;
                    $data['quiz'][$reading->userid]['latestscore'] = (int)$reading->qscore;
                    $data['quiz'][$reading->userid]['all'][] = (int)$reading->qscore;
                }

                $data['users'][$reading->userid]['latestattemptid'] = $reading->attemptid;
                $data['users'][$reading->userid]['latestattemptname'] = $reading->name;
                $data['users'][$reading->userid]['latestattemptreadaloudid'] = $reading->readaloudid;
            }

            // Add drilldown data for each user.
            foreach(['wordsperminute', 'quiz', 'accuracy'] as $chart) {
                if (isset($data[$chart][$reading->userid]['latestscore'])) {
                    // We have a score so add it to the drilldown data for this user.
                    $userfullname = \html_writer::div($classmembers[$reading->userid]['fullname']);

                    if (!isset($highchartsdataobjects[$chart . '-user-' . $reading->userid])) {
                        // Create the data series for this user if we don't have it yet.
                        $highchartsdataobjects[$chart . '-user-' . $reading->userid] = new highcharts_data_series(
                            $userfullname . ' ' . $strings[$chart],
                            'column', // This will change to area once there is > 1 point (see highcharts_data_series->add_data_point().
                            $chart . '-class-' . $this->klass->id . '-user-' . $reading->userid,
                            $chart,
                            '',
                            false,
                            true,
                            true
                        );
                    }

                    $scoresuffix = $chart === 'wordsperminute' ? ' ' . $strings['colwpm'] : '%';

                    // Now add the user's data point to the series.
                    $highchartsdataobjects[$chart . '-user-' . $reading->userid]->add_data_point(
                        $reading->name,
                        $reading->attemptid,
                        $data[$chart][$reading->userid]['latestscore'],
                        \html_writer::div($reading->name)
                            . \html_writer::div($data[$chart][$reading->userid]['latestscore'] . $scoresuffix, 'tooltipscore'),
                        ['photoUrl' => $this->user_photo_url($output, $reading->userid), 'isDrillDown' => true]
                    );
                }


            }
        };

        // Compute averages for each user.
        foreach ($data['users'] as $userid => $user) {
            $userfullname = $classmembers[$userid]['firstname'] . ' ' .  $classmembers[$userid]['lastname'] ;
            $countaccuracy = count($data['accuracy'][$userid]['all']);
            $countquiz = count($data['quiz'][$userid]['all']);
            $countwpm = count($data['wordsperminute'][$userid]['all']);
            if ($countaccuracy) {
                $data['accuracy'][$userid]['average'] = $countaccuracy === 0 ? 0 : round($data['accuracy'][$userid]['totalscore'] / $countaccuracy, 0);
            }
            if ($countquiz){
                $data['quiz'][$userid]['average'] = $countquiz === 0 ? 0 : round($data['quiz'][$userid]['totalscore'] / $countquiz, 0);
            }
            if ($countwpm) {
                $data['wordsperminute'][$userid]['average'] = $countwpm === 0 ? 0 : round($data['wordsperminute'][$userid]['totalscore'] / $countwpm, 0);
            }

            // Add the stats to our Highcharts data so we can add to the page as JSON for JS.
            $datapoints = [
                'readingscompleted' => $data['users'][$userid]['readings-completed'],
                'wordsperminute-average' => $data['wordsperminute'][$userid]['average'],
                'wordsperminute-latest' => $data['wordsperminute'][$userid]['latestscore'],
                'quiz-average' => $data['quiz'][$userid]['average'],
                'quiz-latest' => $data['quiz'][$userid]['latestscore'],
                'accuracy-average' => $data['accuracy'][$userid]['average'],
                'accuracy-latest' => $data['accuracy'][$userid]['latestscore']
            ];

            $datapointtitles = array(
                'readingscompleted' => \html_writer::div(
                    \html_writer::div('<strong>' . get_string('readingscompletedsplit', constants::M_CLASS) . '</strong>', 'text-center')
                        . \html_writer::div($datapoints['readingscompleted'], 'text-center tooltipscore')
                ),
                'wordsperminute' => \html_writer::div(get_string('average', constants::M_CLASS). ': '
                        . \html_writer::div($datapoints['wordsperminute-average']), 'tooltipscore')
                    . \html_writer::div(get_string('latest', constants::M_CLASS). ': '
                        . $datapoints['wordsperminute-latest']),
                'quiz' => \html_writer::div(get_string('average', constants::M_CLASS). ': '
                        . \html_writer::div($datapoints['quiz-average']), 'tooltipscore')
                    . \html_writer::div(get_string('latest', constants::M_CLASS). ': '
                        . $datapoints['quiz-latest']),
                'accuracy' => \html_writer::div(get_string('average', constants::M_CLASS). ': '
                        . \html_writer::div($datapoints['accuracy-average']), 'tooltipscore')
                    . \html_writer::div(get_string('latest', constants::M_CLASS). ': '
                        . $datapoints['accuracy-latest']),
            );

            foreach($datapoints as $key => $value) {
                $chartclass = explode('-', $key)[0];
                $drilldownid = $chartclass . '-class-' . $this->klass->id . '-user-' . $userid;
                $highchartsdataobjects[$key]->add_data_point(
                    $userfullname,
                    $userid,
                    $value,
                    $datapointtitles[$chartclass],
                    ['photoUrl' => $this->user_photo_url($output, $userid), 'drilldown' => $drilldownid, 'isDrillDown' => false]
                );
            }

        }
        $context['userdatajson'] = json_encode(($data));
        foreach($highchartsdataobjects as $item) {
            $context['highchartsdatajson'][] = $item->export_json();
        }
        $context['wpmbenchmarks'] = common::fetch_wpmbenchmarks($this->thecoursedata->id, true);
        $context['wpmbenchmarksjson'] = json_encode($context['wpmbenchmarks']);
        return $context;
    }

    private function get_class_members($output) {
        $members = [];
        $column = 0;
        foreach($this->thecoursedata->courseusers as $courseuser){
            if (isset($courseuser->id) && in_array($courseuser->id, $this->klassmemberids)){
                $userdata = ['column' => $column];
                $userdata['userid'] = $courseuser->id;
                $userdata['firstname'] = $courseuser->firstname;
                $userdata['lastname'] = $courseuser->lastname;
                $userdata['fullname'] = fullname($courseuser);
                $userdata['readings-completed'] = 0;
                $userdata['latestattemptid'] = 0;
                $userdata['latestattemptname'] = '';
                $userdata['latestattemptreadaloudid'] = 0;
                $userdata['photoUrl'] = $this->user_photo_url($output, $courseuser->id);

                $members[$courseuser->id] = $userdata;
                $column++;
            }
        }
        return $members;
    }

    /**
     * @return highcharts_data_series[]
     * @throws \coding_exception
     */
    private function get_highcharts_objects() {

        $highchartsobjects = [
            'readingscompleted' =>
                new highcharts_data_series(
                    get_string('readingscompleted', constants::M_CLASS) . ' (' .  $this->klass->name . ')',
                    'column',
                    'readingscompleted-class-' . $this->klass->id,
                    'readingscompleted'
                )
        ];

        foreach(['wordsperminute', 'accuracy', 'quiz'] as $chart) {
            $highchartsobjects[$chart . '-average'] = new highcharts_data_series(
                get_string($chart, constants::M_CLASS) . ' (' .  $this->klass->name . ')',
                'column',
                $chart . '-average-class-' . $this->klass->id,
                $chart,
                'average'
            );

            $highchartsobjects[$chart . '-latest'] = new highcharts_data_series(
                '',
                'line',
                $chart .'-latest-class-' . $this->klass->id,
                $chart,
                'latest',
                true,
                false
            );
        }
        return $highchartsobjects;
    }

    private function user_photo_url($output, $userid) {
        global $DB;
        $user = $DB->get_field('user', 'picture', array('id' => $userid));
        if (!$user) {
            // Default image.
            return $output->image_url('u/f1')->out();
        }
        $url = \moodle_url::make_pluginfile_url(
            \context_user::instance($userid)->id,
            'user',
            'icon',
            null,
            '/',
            'f1'
        );
        return $url->out();
    }

    private function get_klass_overview_data() {
        $klassmemberids = $this->klass->fetch_klassmemberids();
        $totalwpm = 0;
        $totalquiz = 0;
        $totalreadings = 0;
        $totalaccuracy = 0;
        foreach ($this->thecoursedata->userreadings as $thereading) {
            //if this has klassmembers but the current user is not one, we do not count it
            if (($klassmemberids && !in_array($thereading->userid, $klassmemberids))) {
                continue;
            }
            //if this klass is empty, show no data
            if (!$klassmemberids) {
                continue;
            }

            //total readings
            $totalreadings++;

            //WPM
            if ($thereading->h_wpm) {
                $totalwpm += $thereading->h_wpm;
            } else if ($thereading->ai_wpm) {
                $totalwpm += $thereading->ai_wpm;
            }
            //Accuracy
            if ($thereading->h_accuracy) {
                $totalaccuracy += $thereading->h_accuracy;
            } else if ($thereading->ai_accuracy) {
                $totalaccuracy += $thereading->ai_accuracy;
            }

            //qscore
            if ($thereading->qscore != null && $thereading->qscore != '') {
                $totalquiz += $thereading->qscore;
            }
        }
        $avwpm = $totalwpm;
        $avquiz = $totalquiz;
        $avaccuracy = $totalaccuracy;
        if ($avwpm > 0) {
            $avwpm = round($avwpm / $totalreadings, 0);
        }
        if ($avaccuracy > 0) {
            $avaccuracy = round($avaccuracy / $totalreadings, 0);
        }
        if ($avquiz > 0) {
            $avquiz = round($avquiz / $totalreadings, 0);
        }
        return array(
            'klass' => $this->klass,
            'courseid' => $this->thecoursedata->id,
            'indicators' => [
                ['id' => 'totalreadings', 'title' => get_string('totalreadings', constants::M_CLASS), 'value' => $totalreadings],
                ['id' => 'avwpm', 'title' => get_string('averagewordsperminute', constants::M_CLASS), 'value' => $avwpm],
                ['id' => 'avaccuracy', 'title' => get_string('averageaccuracy', constants::M_CLASS), 'value' => $avaccuracy],
                ['id' => 'avquiz', 'title' => get_string('averagequiz', constants::M_CLASS), 'value' => $avquiz]
            ],
            'iscoursescreen' => $this->iscoursescreen
        );
    }
}