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

require_once($CFG->libdir . '/formslib.php');
require_once(dirname(__FILE__) . '/locallib.php');

class report_quizanalytics_form extends moodleform {

     public function definition() {
        global $CFG;
        global $DB;
        global $PAGE, $USER;

        $mform = $this->_form;

        $quizz=$DB->get_records('quiz');

        //prepare an array for select option
        $areanames=array();

        $enrolled_courses_sql = "SELECT c.id AS courseid
        FROM mdl_role_assignments ra, mdl_user u, mdl_course c, mdl_context cxt
        WHERE ra.userid = u.id
        AND ra.contextid = cxt.id
        AND cxt.contextlevel =50
        AND cxt.instanceid = c.id
        AND u.id = ?";

        $enrolled_courses = [];
        if (!has_capability('moodle/site:configview', context_system::instance())){
            $enrolled_courses = $DB->get_records_sql($enrolled_courses_sql, [$USER->id]);
        }
       
        $enrolled_course_list = [];
        
        foreach($enrolled_courses as $enrolled_course){
            array_push($enrolled_course_list, $enrolled_course->courseid);
        }
        
		foreach($quizz as $quiz){
              
            if (!has_capability('moodle/site:configview', context_system::instance())){
                // non admins
                if(!in_array($quiz->course, $enrolled_course_list)){
                    continue;
                }
            }  
                
                  
            $areanames[$quiz->id]=$quiz->name;
        }

        $options = array(
            'multiple' => false,
        );
        $mform->addElement('autocomplete', 'quizid',get_string('selectquiz', 'report_quizanalytics'), $areanames, $options);

         /* ---------------- Ehancement 23 Nov 2020 ---------------------------*/

         $batch_codes = report_timespent_get_code("batchcode");

         /* ---------------- Ehancement 05 FEB 2021 ---------------------------*/
         if (!has_capability('moodle/site:configview', context_system::instance())) {
              profile_load_custom_fields($USER);
              $logged_user_profiles = $USER->profile;
              $logged_user_batchcodes = array_map('trim', explode(',', $logged_user_profiles['batchcode']));
  
              $list_temp = array();
  
              foreach($logged_user_batchcodes as $batch_code){
                  $object = new stdClass;
                  $object->data = $batch_code;
                  array_push($list_temp, $object);
              }
  
              $batch_codes = $list_temp;
         }
  
           /* ---------------- Ehancement 05 FEB 2021 END ---------------------------*/
  
         $center_codes = report_timespent_get_code("centercode");
  
         $search_batch = [];
         $flag = true;
         foreach($batch_codes as $batchcode){
              $lists = explode(",", $batchcode->data);
              foreach($lists as $list){

                if($flag == true){
                    $search_batch["all"] = "All Batch Codes";
                    $flag = false;
                }

                  $list = trim($list);
                  $search_batch[$list] = $list;
              }
         }
  
         $search_centercode = [];
         $flag = true;
         foreach($center_codes as $centercode){
              $lists = explode(",", $centercode->data);
              foreach($lists as $list){

                  if($flag == true){
                    $search_centercode["all"] = "All Center Codes";
                    $flag = false;
                  }

                  $list = trim($list);
                  $search_centercode[$list] = $list;
              }
         }
  
          $options = array(
              'multiple' => true,
          );
  
          if (has_capability('moodle/site:configview', context_system::instance())) {
                $mform->addElement('autocomplete', 'centercode', get_string('centercode', 'report_timespent'), $search_centercode, $options);
          }
           
          $mform->addElement('autocomplete', 'batchcode', get_string('batchcode', 'report_timespent'), $search_batch, $options);
          $mform->addRule('batchcode', get_string('required'), 'required');
  
          /* --------------------------- Ehancement 23 Nov 2020 END---------------------------------------- */
  

         //customizing action button
         //disabling cancel button
         //changing the label of submit button

        $this->add_action_buttons($cancel = false,get_string('completeanalytics', 'report_quizanalytics'));
        //$this->add_action_buttons($cancel = false,get_string('overview', 'report_quizanalytics'));
        $this->add_action_buttons($cancel = false,"Get Overview");

    }

  public function validation($data, $files) {

        $errors= array();
        $errors = parent::validation($data, $files);

        return $errors;
    }
}

