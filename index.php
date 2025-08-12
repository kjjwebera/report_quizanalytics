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

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/index_form.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require('../../mod/quiz/lib.php');

$PAGE->requires->jquery();
$PAGE->requires->js(new \moodle_url('https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js'), true);
$PAGE->requires->css(new \moodle_url('https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css'), true);
$PAGE->requires->css(new \moodle_url('https://cdn.datatables.net/buttons/1.6.4/css/buttons.dataTables.min.css'), true);
$PAGE->requires->js(new \moodle_url('https://cdn.datatables.net/buttons/1.6.4/js/dataTables.buttons.min.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdn.datatables.net/buttons/1.6.4/js/buttons.flash.min.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdn.datatables.net/buttons/1.6.4/js/buttons.html5.min.js'), true);
$PAGE->requires->js(new \moodle_url('https://cdn.datatables.net/buttons/1.6.4/js/buttons.print.min.js'), true);

/* **************Update*****************  */

// Ved 05-02-2021

global $USER, $DB, $SESSION;

$id = optional_param('id', 1, PARAM_INT); // course id

if($id == 1){
    $course_id = property_exists($SESSION, 'course_id') ? $SESSION->course_id : 1;
    $course = $DB->get_record('course', array('id'=>$course_id), '*', MUST_EXIST);
}else{
    $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
    $SESSION->course_id = $course->id;
}

 $PAGE->set_url('/report/quizanalytics/index.php');
 $PAGE->set_pagelayout('report');
 require_login($course);
 $context = context_course::instance($course->id);
 require_capability('report/quizanalytics:view', $context);

if($course->id == 1){
    admin_externalpage_setup('report_quizanalytics', '', null, '', array('pagelayout'=>'report'));

    $PAGE->set_title(get_string('heading', 'report_quizanalytics'));
    $PAGE->set_heading(get_string('heading', 'report_quizanalytics'));
    echo $OUTPUT->header();

}else{
    $PAGE->set_title(get_string('heading', 'report_quizanalytics'));
    $PAGE->set_heading(get_string('heading', 'report_quizanalytics'));
    echo $OUTPUT->header();
}

/* **************END*****************  */


$date = date('m-d-Y h:i:s a', time());
$quiz_name="";
$mform = new report_quizanalytics_form();

$mform->display();

if ($newreport = $mform->get_data()) {

        $submit_type=$newreport->submitbutton; // Get Complete Analytics or Get Overview
        $quiz_id=$newreport->quizid;

        $center_code = array();
        $batch_code = array();
 
        if (!has_capability('moodle/site:configview', context_system::instance())) {
            profile_load_custom_fields($USER);
            $logged_user_profiles = $USER->profile;
            $logged_user_centercode = trim($logged_user_profiles['centercode']);
            array_push($center_code, trim($logged_user_centercode));
        }
        
        if(property_exists($newreport, 'batchcode')){

            $batch_code = $newreport->batchcode;
 
            if (in_array("all", $batch_code)){

                if (!has_capability('moodle/site:configview', context_system::instance())) {
                    // non admins
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
                    $batch_codes = get_formatted_array($batch_codes);
                    $batch_code = $batch_codes;
               }else{
                    // admins
                    $batch_codes = report_timespent_get_code("batchcode");
                    $batch_codes = get_formatted_array($batch_codes);
                    $batch_code = $batch_codes;
               }
  
            }
             
        }

        if(property_exists($newreport, 'centercode')){

            $center_code = $newreport->centercode;

            if (in_array("all", $center_code)){

                if (!has_capability('moodle/site:configview', context_system::instance())) {
                    // non admins
                    profile_load_custom_fields($USER);
                    $logged_user_profiles = $USER->profile;
                    $logged_user_centercode = trim($logged_user_profiles['centercode']);
                    
                    $center_code = $logged_user_centercode;
               }else{
                    // admins
                    $center_codes = report_timespent_get_code("centercode");
                    $center_codes = get_formatted_array($center_codes);    
                    $center_code = $center_codes;
               }
               
            }
            
        }

        $html=report_quiz_records($quiz_id,$submit_type, $center_code, $batch_code);

        $quiz = $DB->get_record('quiz', array('id' => $quiz_id));

        $quiz_name=$quiz->name;

        echo $html;

}

function get_formatted_array($codes){

    $list = [];

    foreach($codes as $code){
        array_push($list, $code->data);
    }

    return $list;
}

/*
output:
    id of all question of whose quizid=19 i.e. 175,173,181,182

SELECT q.id
              FROM mdl_quiz_slots slot
              JOIN mdl_question q ON q.id = slot.questionid
              WHERE slot.quizid = 19 */

//use the question id fetched above and get the question category list
//SELECT * FROM `mdl_question` WHERE id=175

//from the category fetch the name of the question bank from where the actual question is been fetched
//SELECT * FROM `mdl_question_categories` WHERE id=27


//print_r($ques);die();


// Returns the list of user attempted the quiz

echo '

<style>

.dt-buttons{
    margin-bottom:10px;
}

.dt-button{
     background-color:#1177d1;
     padding:6px;
     padding-left:7px;
     padding-right:7px;
     border:none;
     color:white;
     border-radius:3px;
}

.dataTables_wrapper.no-footer .dataTables_scrollBody {
     border-bottom: 0px solid #111111;
}
</style>

';

echo $OUTPUT->footer();
?>

<script>
 $(document).ready(function() {
    $('#list').DataTable( {
        dom: 'Bfrtip',
        'scrollY': 400,
        'scrollX': true,
       buttons: [
            {
                extend: 'csvHtml5',
                text: 'CSV',
                filename: "<?php  echo $quiz_name."_".$date ?>"
            },
            {
                extend: 'excelHtml5',
                text: 'Excel',
                filename: "<?php echo $quiz_name."_".$date ?>"
            }
        ]
    });
} );

</script>
