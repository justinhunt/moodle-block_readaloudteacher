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
 * Highcharts data series.
 *
 * @package block_readaloudteacher
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_readaloudteacher;

use block_readaloudteacher\common;
use block_readaloudteacher\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * Prepares data into a series suitable for Highcharts JS display.
 *
 * @package block_readaloudteacher
 * @copyright 2019 David Watson {@link http://evolutioncode.uk}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class highcharts_data_series {

    private $name;

    /**
     * Highcharts type of chart - column, area or line.
     * @var
     */
    private $type;

    /**
     * Class of chart - readingscompleted, wordsperminute, quiz etc.
     * @var
     */
    private $chartclass;

    /**
     * Type of data series - average or latest.
     * @var
     */
    private $seriestype;

    /**
     * ID of this data series.
     * @var
     */
    private $id;

    /**
     * Hex colour for the bar (if bar chart).
     * @var string
     */
    private $barcolour;

    /**
     * Data values in the series.
     * @var array
     */
    private $data;

    /**
     * Whether we are displaying points only (diamond points for the series no bars or line).
     * @var bool
     */
    private $pointsonly;

    /**
     * Whether this series should use an area chart (instead of default column).
     * @var
     */
    private $areachart;

    /**
     * Whether this chart represents drilldown data.
     * @var
     */
    private $isdrilldown;



    public function __construct($name, $type, $id, $chartclass, $seriestype = '', $datapointsonly = false, $areachart = false, $isdrilldown = false) {
        $this->name = $name;
        $this->type = $type;
        $this->id = $id;
        $this->barcolour = '#5cb85c'; // default.
        $this->chartclass = $chartclass;
        $this->seriestype = $seriestype;
        $this->pointsonly = $datapointsonly;
        $this->areachart = $areachart;
        $this->isdrilldown = $isdrilldown;
        $this->data = [];
    }

    /**
     * @param string $columnlabel X axis (column) label.
     * @param int $xid the id of the column (i.e. user id as they are on x-axis).
     * @param int $y height of column.
     * @param string $label label for the tooltip for the point.
     * @param array $options additional options for the point
     */
    public function add_data_point($columnlabel, $xid, $y, $label = '', $options = []) {
        $datapoint = array(
            'name' => $columnlabel,
            'y' => $y,
            'xid' => $xid,
            'label' => $label,
            'color' => $this->pointColour($y)
        );
        if (!empty($options)) {
            foreach($options as $k => $v) {
                if (!in_array($k, ['drilldown', 'photoUrl', 'color', 'isDrillDown', 'isUserChart'])){
                    debugging('Invalid option ' . $k, DEBUG_DEVELOPER);
                }
                $datapoint[$k] = $v;
            }
        }

        $this->data[] = $datapoint;

        // If this is a drilldown chart and it has more than one point, make sure it's an area chart not column.
        // We start drilldown charts off as column charts since one point on a area chart is no good.
        if ($this->type === 'column' && $this->areachart && count($this->data) > 1) {
            $this->type = 'area';
        }
    }

    private function pointColour($value) {
        switch ($this->chartclass) {
            case 'quiz':
                if ($value <= 50) {
                    return 'tomato';
                } else if ($value <= 75) {
                    return 'gold';
                } else return '#5cb85c';
            case 'accuracy':
                if ($value <= 70) {
                    return 'tomato';
                } else if ($value <= 85) {
                    return 'gold';
                } else return '#5cb85c';
        }
        return '#5cb85c';
    }

    /**
     * Set the bar colours for this data series.
     * @param $colour
     */
    public function set_bar_colour($colour){
        $this->barcolour = $colour;
    }

    /**
     * Export this data series to JSON so that we can can embed it into the HTML for JS to pick up.
     * @return array
     */
    public function export_json() {
        $data = array(
            'name' => $this->name,
            'type' => $this->type,
            'id' => $this->id,
            'color' => $this->barcolour,
            'data' => $this->data,
            'showInLegend' => false
        );
        if ($this->areachart) {
//            unset($data['color']);
            $data['fillColor'] = array(
                'linearGradient' => ['x1' => 0, 'y1' => 0, 'x2' => 0, 'y2' => 1],
                'stops' => [
                    [0, 'rgba(90, 180, 90, 1)'],
                    [1, 'rgba(90, 180, 90,.1)']
                ]
            );
        }
        if ($this->pointsonly) {
            // Our data is not to be shown as a column or line chart but just diamond points.
            $data['dataLabels'] = array('enabled' => false);
            $data['type'] = 'line';
            $data['name'] = get_string('latest', constants::M_CLASS);
            $data['lineWidth'] = '0';
            $data['marker'] = array(
                'enabled' => true,
                'radius' => 12,
                'lineColor' => '#909090',
                'fillColor' => '#d89b03',
                'symbol' => 'diamond'
            );
            $data['showInLegend'] = true;
        }
        $data['states'] = array(
            'hover' => array('enabled' => false)
        );

        return array(
            'chartid' => $this->id,
            'chartclass' => $this->chartclass,
            'seriestype' => $this->seriestype,
            'isdrilldown' => $this->isdrilldown,
            'json' => json_encode($data),
            'ispointsonly' => $this->pointsonly
        );
    }
}