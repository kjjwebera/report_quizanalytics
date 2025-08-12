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
 * @package    report_quizanalytics
 * @copyright  2018 Web Era Technology Pvt. Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings = null;


if (is_null($ADMIN->locate('wetpl'))) {

    $ADMIN->add( 'reports', new admin_category( 'wetpl', 'WETPL'));

}
 

$ADMIN->add('wetpl', new admin_externalpage('report_quizanalytics',
        get_string('pluginname', 'report_quizanalytics'),
        new moodle_url('/report/quizanalytics/index.php'),'report/quizanalytics:view'));
 