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

function report_quizanalytics_url($relativeurl) {
        global $CFG;
        return $CFG->wwwroot.'/report/quizanalytics/'.$relativeurl;
}

function report_quiz_records($quiz_id,$submit_type, $selected_center_code, $selected_batch_code){

global $DB, $CFG, $USER, $PAGE;

$remove_question_response=true;
if($submit_type=="Get Complete Analytics"){
    $remove_question_response=false;
}

//get the quiz object
$quiz_object=getQuizObject($quiz_id);

$question_bank_names=getQuestionBankName($quiz_id);
arsort($question_bank_names);

$user_ids=getUsers($quiz_id);

$details=array();

$center_codes=get_center_code();
$batch_codes=get_batch_code();

/* -----------------Update---------------------*/
    // @Ved 05 02 2021
    profile_load_custom_fields($USER);
    $logged_user_profiles = $USER->profile;
    $logged_user_batchcodes = $logged_user_profiles['batchcode'];

    $logged_user_batchcodes = array_map('trim', explode(',', $logged_user_batchcodes));

    $site_admin = false;
    if (has_capability('moodle/site:configview', context_system::instance())) {
        $site_admin = true;
    }

 /* -----------------End---------------------*/

foreach($user_ids as $user_id){

    if($user_id == 2)
        continue;

    $attempts=getQuizAttempts($quiz_id,$user_id);

    $user = $DB->get_record('user', array('id' => $user_id));

    if($user->deleted !=0){
        continue;
    }

    /* -----------------Update---------------------*/
    // @Ved 05 02 2021
    // implement batch_code based data retrieval


    profile_load_custom_fields($user);
    $current_user_profiles = $user->profile;
    $current_user_batchcode = trim($current_user_profiles['batchcode']);
    $current_user_centercode = trim($current_user_profiles['centercode']);

    $current_user_batchcode_array = array_map('trim', explode(",",$current_user_batchcode));
    $current_user_centercode_array = array_map('trim', explode(",",$current_user_centercode));
    
    if(count($selected_batch_code)!=0 || count($selected_center_code) !=0){
        if(array_intersect($current_user_centercode_array, $selected_center_code) ||
           array_intersect($current_user_batchcode_array, $selected_batch_code)){
        }else{
            /*echo 'firstcheck';
            echo '<br>';*/
            continue;
        }
    }else{
        if(!array_intersect($current_user_batchcode_array, $selected_batch_code)){
            /*echo 'secndcheck';
            echo '<br>';*/
            continue;
        }
    }
 
    if (!$site_admin && !in_array($current_user_batchcode, $logged_user_batchcodes)){
        //    continue;
        /*echo 'thirdcheck';
        echo '<br>';*/
    }

    /* -----------------End---------------------*/


    $username=$user->username;
    $firstname=$user->firstname;
    $lastname=$user->lastname;
    $email=$user->email;
    $idnumber=$user->idnumber;
    $institution=$user->institution;
    $department=$user->department;

    foreach($attempts as $attempt){

        $object=new stdClass();

        //userid and quiz
        $object->userid=$attempt->userid;
        $object->quizid=$attempt->quiz;
        $object->attempt_id=$attempt->id;
        $object->attempt=$attempt->attempt;

        //total mark obtained in an attempt
        $object->total_mark_obtained=$attempt->sumgrades;

        //user profile fields
        $object->id_number=$idnumber;
        $object->institution=$institution;
        $object->department=$department;

        $object->username=$username;
        $object->firstname=$firstname;
        $object->lastname=$lastname;
        $object->email=$email;
        $object->batch_code=$batch_codes[$attempt->userid];
        $object->center_code=$center_codes[$attempt->userid];

        //user attempts
        $object->attempt=$attempt->attempt;
        $object->state=$attempt->state;
        $object->state=$attempt->state;

        //date info
        $start_date=date('Y/m/d', $attempt->timestart);
        $start_time=date('H:i:s', $attempt->timestart);

        $finish_date=date('Y/m/d', $attempt->timefinish);
        $finish_time=date('H:i:s', $attempt->timefinish);

        $object->start_date=$start_date;
        $object->start_time=$start_time;
        $object->finish_date=$finish_date;
        $object->finish_time=$finish_time;

        $start=date('Y/m/d H:i:s', $attempt->timestart);
        $finish=date('Y/m/d H:i:s', $attempt->timefinish);
        $datetime1 = new DateTime($start);//start time
        $datetime2 = new DateTime($finish);//end time
        $interval = $datetime1->diff($datetime2);
        $time=$interval->format('%H:%i:%s');

        $object->time_in_seconds=$time;

        array_push($details,$object);
    }
}
//print_object($details);die;
$unique_courses = $question_bank_names;

$unique_courses=array_count_values($unique_courses);
 
$html="
    <div class='row'>
        <div class='col-md-12'>
             <table class='table table-striped table-bordered users dataTable no-footer' id='list'>
                <thead style=''>

                <tr>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>";

                      if(!$remove_question_response){
                            foreach($unique_courses as $key => $value){
                                 $value++;
                                 $html.="<th colspan=$value style='text-align:center; padding-right:25px; padding-left:25px;'> $key </th>";

                            }
                        }else{
                          foreach($unique_courses as $key => $value){

                                 $html.="<th colspan=1 style='text-align:center; padding-right:25px; padding-left:25px;'> $key </th>";

                            }
                      }


            $html.="<th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    <th> </th>
                    ";

            $html.="<th></th>
                    <th></th>
                    <th></th>

                </tr>

                  <tr>
                    <th>ID Number</th>
                    <th>Username</th>
                    <th>First Name</span></th>
                    <th>Last Name</th>
                    <th>Email Address</th>
                    <th>Center Code</th>
                    <th>Batch Code</th>
                    <th>Institution</th>
                    <th>Department</th>
                    ";
            $count=1;
            $count_c=1;


                    foreach($question_bank_names as $question_bank_name){

                        if(!$remove_question_response){
                            $html.="<th style='text-align:center;padding-left: 25px;
                                     padding-right: 25;'><span style='overflow: hidden;
           color:transparent; width: 20px;
           height: 20px; font-size:2px;'>$question_bank_name</span> Q. $count </th>";
                        }
                            $c=$unique_courses[$question_bank_name];
                            if($c == $count_c)
                            {
                                $count=1;
                                $html.="<th style='text-align:center;'> <span style='font-size:2px; display:none;'>$question_bank_name</span> Score (%)  </th>";
                                $count_c=1;
                            }else{
                               $count++;
                               $count_c++;
                            }

                        //$count++;
                    }


                    $grde=round($quiz_object->quiz_mark_grade,0);
                    $total_quiz_mark=round( $quiz_object->quiz_total_mark,0);


                    $html.="<th>Grade / $grde  </th>";

                    $html.="<th>Final Grade</th>";

                        $html.="<th> Total Score achieved / $total_quiz_mark </th>";

                    $html.="<th>Percentage</th>
                    <th>Grade Letter</th>";

                    $html.="<th>Started On</th>
                    <th>Time</th>

                    <th>Completed On</th>
                    <th>Time</th>

                    <th>Time Taken</th>";

                  $html.="</tr>
                </thead>

                <tbody>";

                $overview_arr=array();

                foreach($details as $detail){

            if(!$remove_question_response){
                    $html.="<tr>";

                        if(sizeof($detail->id_number)!=0)
                            $html.="<td style='text-align:center'>".$detail->id_number."</td>";
                        else
                            $html.="<td style='text-align:center'>-</td>";

                        $html.="<td>".$detail->username."</td>";
                        $html.="<td>".$detail->firstname."</td>";
                        $html.="<td>".$detail->lastname."</td>";
                        $html.="<td>".$detail->email."</td>";
                        $html.="<td>".$detail->center_code."</td>";
                        $html.="<td>".$detail->batch_code."</td>";
                        $html.="<td>".$detail->institution."</td>";
                        $html.="<td>".$detail->department."</td>";
            }else{
                        $detail_obj=new stdClass();
                        $detail_obj->id_number=$detail->id_number;
                        $detail_obj->username=$detail->username;
                        $detail_obj->firstname=$detail->firstname;
                        $detail_obj->lastname=$detail->lastname;
                        $detail_obj->email=$detail->email;
                        $detail_obj->center_code=$detail->center_code;
                        $detail_obj->batch_code=$detail->batch_code;
                        $detail_obj->institution=$detail->institution;
                        $detail_obj->department=$detail->department;
                        $detail_obj->attempt=$detail->attempt;
            }


                         /*  $html.="<td style='text-align:center'>".$detail->state."</td>";  */

                         $count=1;

                         $attempt_id=$detail->attempt_id;
                         $responses=getQuestionResponse($quiz_id,$attempt_id);
                         //print_r($responses);die();
                         $count_c=1;
                         $max_mark_with_question_id=array();
                         $module_maxmark_details=array();
                         $module_sum = [];
                         foreach($question_bank_names as $key => $question_bank_name){

                             $response=$responses[$key];

                             $quiz_grade=$quiz_object->quiz_mark_grade;
                             $total_mark=$quiz_object->quiz_total_mark;

                             $marked_obtained_on_attempt=$detail->total_mark_obtained;

                             $marked_obtained_on_attempt=($quiz_grade/$total_mark)*$marked_obtained_on_attempt;

                             $marked_obtained_on_attempt=round($marked_obtained_on_attempt,2);

                             $max_mark_with_question_id[$response->questionid]=$response->maxmark;

                             //$module_maxmark_details[$question_bank_name]=$max_mark_with_question_id

                            if(array_key_exists($question_bank_name,$module_maxmark_details)){
                                 $temp_obj=$module_maxmark_details[$question_bank_name];

                                    $mark_object_sub=new stdClass();
                                    $mark_object_sub->questionid=$response->questionid;
                                    $mark_object_sub->maxmark=$response->maxmark;
                                    $mark_object_sub->state=$response->state;

                                    array_push($mark_object->mark_lists,$mark_object_sub);

                            }else{
                               //print_r($question_bank_name);die();
                                 $mark_object=new stdClass();
                                 $mark_object->mark_lists=array();

                                    $mark_object_sub=new stdClass();
                                    $mark_object_sub->questionid=$response->questionid;
                                    $mark_object_sub->maxmark=$response->maxmark;
                                    $mark_object_sub->state=$response->state;

                                 array_push($mark_object->mark_lists,$mark_object_sub);

                                 $module_maxmark_details[$question_bank_name]=$mark_object;
                            }

                            $msg='';
                            if($response->state=="gradedright")
                            {
                                 $mark_obtained_on_question=$response->maxmark;

                                 $decimal_point=$quiz_object->quiz_decimalpoints;

                                 $result=($quiz_grade/$total_mark)*$mark_obtained_on_question;

                                 $final_res=round($result,2);

                                if(isset($module_sum[$question_bank_name])){
                                    $module_sum[$question_bank_name]+=$final_res;
                                }else{
                                    $module_sum[$question_bank_name]=$final_res;
                                }

                                 $msg="<span style='color:green; font-weight:bold;'>
                                        &#10004; $final_res</span>";

                             }else if($response->state=="gradedwrong"){

                                if(isset($module_sum[$question_bank_name])){
                                    $module_sum[$question_bank_name]+=0;
                                }else{
                                    $module_sum[$question_bank_name]=0;
                                }

                                 $msg="<span style='color:red; font-weight:bold;'>&#10007; 0.00</span>";
                             }else if($response->state=="gaveup"){
                                 $module_sum[$question_bank_name]+=0;

                                 $msg="<span style='color:#031408; font-weight:bold;'>&#10007;</span>";
                             }else if($response->state=="gradedpartial"){

                                $mark_obtained=$response->fraction;
                                $result=($quiz_grade/$total_mark)*$mark_obtained;

                                $final_res=round($result,2);
                                $module_sum[$question_bank_name]+=$final_res;

                                 $msg="<span style='color:white; font-weight:bold; border:1px solid black;    background: black;font-size: 14px;padding: 2px;'>&#10004; $final_res </span>";
                             }

                            if(sizeof($response->state)==0){
                                 $module_sum[$question_bank_name]+=0;

                                 $msg="<span style='color:red; font-weight:bold;'>&#10007;</span>";
                            }

                            if(!$remove_question_response){
                                         $html.="<td style='text-align:center'>";

                        $html.='<a style="text-decoration:none;" target="_blank" href="' . $CFG->wwwroot . '/mod/quiz/reviewquestion.php?attempt=' . $attempt_id .
                                      '&amp;slot=' . $count . '">' . $msg .'</a>';

                                         $html.="</td>";
                            }

                            $count++;

                            $c=$unique_courses[$question_bank_name];

                            if($c == $count_c)
                            {
                                //print_r($module_maxmark_details[$question_bank_name]);die();

                                $max_mark=$module_maxmark_details[$question_bank_name];
                                $total_mark_sum=0;
                                $mark_secured=0;
                                foreach($max_mark->mark_lists as $list){
                                    $total_mark_sum+=$list->maxmark;

                                    if($list->state=='gradedright'){
                                        $mark_secured+=$list->maxmark;
                                    }else if($list->state=='gradedpartial'){
                                        $mark_secured+=$response->fraction;
                                    }
                                }

                                $output=$mark_secured/$total_mark_sum * 100;
                                $output=round($output,2);

                                //$res=($quiz_grade/$total_mark)*$single_module_mark_obtained;

                                $single_module_mark_obtained=$module_sum[$question_bank_name];

                                //test
                                //$output=$single_module_mark_obtained/$quiz_grade * 100;
                                //$output=round($output,2);
                                //end-test

                                $res=($quiz_grade/$total_mark)*$single_module_mark_obtained;

                                $res=round($res,2);

                                $tm=round($total_mark,0);

                                $module_percentage=$single_module_mark_obtained/$tm*100;

                                 if(!$remove_question_response){
                                    $html.="<th style='text-align:center;'> $output % </th>";

                                 }else{
                                    $detail_obj->$question_bank_name=$output." %";
                                 }

                                $count_c=1;
                            }else{
                               $count_c++;
                            }

                        }//foreach($question_bank_names)

                        $quiz_total_mark=round($quiz_object->quiz_total_mark,0);

                        //$perct=$total_score_achieved/$quiz_total_mark;

                        $module_sum=null;
                        $module_maxmark_details=null;

                        $total_score_achieved=round($detail->total_mark_obtained,2);

                        $percentage=$total_score_achieved / $quiz_total_mark * 100;
                        $percentage=round($percentage,2);
                        $grade_letter=getGradeLetter($quiz_object->course_id,$percentage);

                        if(!$remove_question_response){
                            $html.="<td style='text-align:center'> $marked_obtained_on_attempt </td>";
                        }else{
                            #new
                            $detail_obj->marked_obtained_on_attempt=$marked_obtained_on_attempt;
                        }

                        //quiz final grade retrieval
                        $user_id=$detail->userid;
                        $quiz_id=$detail->quizid;
                        $quiz = $DB->get_record('quiz_grades', array('quiz' => $quiz_id,'userid'=>$user_id), 'id,grade', MUST_EXIST);

                        $quiz_final_grade=$quiz->grade;
                        $quiz_final_grade=round($quiz_final_grade,2);

                        if(!$remove_question_response){

                            $html.="<td style='text-align:center'> $quiz_final_grade </td>";
                            $html.="<td style='text-align:center'>$total_score_achieved</td>";
                            $html.="<td style='text-align:center'> $percentage % </td>";
                            $html.="<td style='text-align:center'> $grade_letter </td>";

                            $html.="<td style='text-align:center'>$detail->start_date</td>";
                            $html.="<td style='text-align:center'>$detail->start_time</td>";
                            $html.="<td style='text-align:center'>".$detail->finish_date."</td>";
                            $html.="<td style='text-align:center'>$detail->finish_time</td>";
                            $html.="<td style='text-align:center'>".$detail->time_in_seconds."</td>";
                            $html.="</tr>";
                        }else{
                              #new
                              $detail_obj->quiz_final_grade=$quiz_final_grade;
                              $detail_obj->total_score_achieved=$total_score_achieved;
                              $detail_obj->percentage=$percentage;
                              $detail_obj->grade_letter=$grade_letter;
                              $detail_obj->start_date=$detail->start_date;
                              $detail_obj->start_time=$detail->start_time;
                              $detail_obj->finish_date=$detail->finish_date;
                              $detail_obj->finish_time=$detail->finish_time;
                              $detail_obj->time_in_seconds=$detail->time_in_seconds;
                              array_push($overview_arr,$detail_obj);
                        }
                }//details-foreach(-)
            if($remove_question_response){
               $filtered_responses=getFilteredResponses($overview_arr,$quiz_object,$unique_courses);

                foreach($filtered_responses as $filtered_response){
                    //print_r($filtered_response);die();
                    //print_r($unique_courses);die();
                    $html.="<tr>";

                        $html.="<td>$filtered_response->id_number </td>";
                        $html.="<td>$filtered_response->username </td>";
                        $html.="<td>$filtered_response->firstname </td>";
                        $html.="<td>$filtered_response->lastname </td>";
                        $html.="<td>$filtered_response->email </td>";
                        $html.="<td>$filtered_response->center_code </td>";
                        $html.="<td>$filtered_response->batch_code </td>";
                        $html.="<td>$filtered_response->institution </td>";
                        $html.="<td>$filtered_response->department </td>";

                        foreach($unique_courses as $key => $value){
                              $html.="<td>{$filtered_response->$key}</td>";

                        }

                        $html.="<td>$filtered_response->marked_obtained_on_attempt </td>";
                        $html.="<td>$filtered_response->quiz_final_grade </td>";
                        $html.="<td>$filtered_response->total_score_achieved </td>";
                        $html.="<td>$filtered_response->percentage </td>";
                        $html.="<td>$filtered_response->grade_letter </td>";
                        $html.="<td>$filtered_response->start_date </td>";
                        $html.="<td>$filtered_response->start_time </td>";
                        $html.="<td>$filtered_response->finish_date </td>";
                        $html.="<td>$filtered_response->finish_time </td>";
                        $html.="<td>$filtered_response->time_in_seconds </td>";
                    $html.="</tr>";
                }
            }

            $html.="</tbody>
              </table>
        </div>
    </div>";

 return $html;

}

function get_center_code(){
    global $DB;

    $sql="
    SELECT
    d.id,d.userid,d.data
    FROM
    mdl_user_info_data d,
    mdl_user_info_field info

    WHERE
    d.fieldid=info.id AND
    info.shortname='centercode'
    ";

    //Get all records
    $records = $DB->get_records_sql($sql);
    $data=array();
    foreach($records as $record){
        $data[$record->userid]=$record->data;
    }

    return $data;
}


function get_batch_code(){
    global $DB;

    $sql="
    SELECT
    d.id,d.userid,d.data
    FROM
    mdl_user_info_data d,
    mdl_user_info_field info

    WHERE
    d.fieldid=info.id AND
    info.shortname='batchcode'
    ";

    //Get all records
    $records = $DB->get_records_sql($sql);
    $data=array();
    foreach($records as $record){
        $data[$record->userid]=$record->data;
    }

    return $data;
}

function getUsers($quiz_id){
     global $DB;
     $users=array();
     //$sql="SELECT * FROM `mdl_quiz_attempts` WHERE quiz=19 AND userid=6 ORDER BY userid ASC";
     $sql="SELECT DISTINCT userid FROM `mdl_quiz_attempts` WHERE quiz=$quiz_id";
     $records = $DB->get_records_sql($sql);

    foreach($records as $record){
        array_push($users,$record->userid);
    }
    return $users;
}

function getQuizAttempts($quiz_id, $user_id){
    global $DB;
    $attempts=quiz_get_user_attempts($quiz_id,$user_id);
    return $attempts;
}

function getQuestionResponse($quiz_id,$question_attempt_id){

    global $DB;
    $question_response=array();

    $sql="

        SELECT @c:=@c+1 AS serialNumber,t.*
         FROM
         (SELECT @c:= 0) AS c,
         (
            SELECT
            quiza.userid,
            quiza.quiz,
            quiza.id AS quizattemptid,
            quiza.attempt,
            quiza.sumgrades,

            qa.slot,

            qa.questionid,
            qa.maxmark,
            qa.minfraction,

            qas.state,
            qas.fraction,

            qas.userid as useridd,

            qa.questionsummary,
            qa.rightanswer,
            qa.responsesummary

        FROM mdl_quiz_attempts quiza
        JOIN mdl_question_usages qu ON qu.id = quiza.uniqueid
        JOIN mdl_question_attempts qa ON qa.questionusageid = qu.id
        JOIN mdl_question_attempt_steps qas ON qas.questionattemptid = qa.id
        LEFT JOIN mdl_question_attempt_step_data qasd ON qasd.attemptstepid = qas.id

        WHERE  quiza.quiz=$quiz_id AND qas.state IN ('gradedright','gradedwrong','gaveup','gradedpartial')  AND quiza.id=$question_attempt_id

        ORDER BY quiza.userid, quiza.attempt, qa.slot, qas.sequencenumber, qasd.name
        ) AS t";

     //Get all records
    $records = $DB->get_records_sql($sql);

    foreach($records as $record){
        $question_response[$record->slot]=$record;
    }

    $sortedQuiz=getQuestionBankName($quiz_id);
    arsort($sortedQuiz);

    $final_sorted_response=array();
    foreach($sortedQuiz as $key => $value){
         $final_sorted_response[$key]=$question_response[$key];
    }

    return $final_sorted_response;
}

function getQuizObject($quiz_id){
    global $DB;
    $quiz = $DB->get_record('quiz', array('id' => $quiz_id), '*', MUST_EXIST);

    $object=new stdClass();
    $object->course_id=$quiz->course;
    $object->quiz_total_mark=$quiz->sumgrades;
    $object->quiz_mark_grade=$quiz->grade;
    $object->quiz_id=$quiz->id;
    $object->quiz_attempts=$quiz->attempts;
    $object->quiz_decimalpoints=$quiz->decimalpoints;
    $object->grademethod=$quiz->grademethod;

    return $object;
}

function getGradeLetter($course_id,$percentage){

        global $DB;
        $sql="SELECT * FROM {grade_letters} WHERE contextid=$course_id";
        $sql_default="SELECT * FROM {grade_letters} WHERE contextid=1";

        $letters=array();
        try{
            $letters =$DB->get_records_sql($sql);
            if(sizeof($letters)==0){

                $letters =$DB->get_records_sql($sql_default);
            }
            }catch(Exception $e) {
                   return "Internal Error Contact IT Dept";
            }

        $lett='';
            foreach($letters as $letter){
                if($percentage>round($letter->lowerboundary,2))
                    $lett=$letter->letter;
            }
    return $lett;
}

function getSlots($quiz_id){
     global $DB;
     $quiz_slots=array();
     $sql="SELECT q.id,slot,category
              FROM mdl_quiz_slots slot
              JOIN mdl_question q ON q.id = slot.questionid
              WHERE slot.quizid = $quiz_id";

     $slots = $DB->get_records_sql($sql);

     foreach($slots as $slot){

         $object=new stdClass();
         $object->slotid=$slot->id;//182
         $object->slot=$slot->slot;//1
         $object->category=$slot->category;//33

         array_push($quiz_slots,$object);
     }

    return $quiz_slots;
}
 /*
function getQuestion($slot_id){
    global $DB;

    $question = $DB->get_record('question', array('id' => $slot_id), '*', MUST_EXIST);

    $question_category_id=$question->category;
    return $question_category_id;
} */

function getQuestionCategoryName($question_category_id){
     global $DB;
     //SELECT * FROM `mdl_question_categories` WHERE id=27
     $question_category_name = $DB->get_record('question_categories', array('id' => $question_category_id), '*', MUST_EXIST);

     $category_name=$question_category_name->name;
    return $category_name;
}

function getQuestionBankName($quiz_id){

    $slots=getSlots($quiz_id);
    $list=array();

    foreach($slots as $obj){

         $question_bank_name=getQuestionCategoryName($obj->category);

         $list[$obj->slot]=$question_bank_name;
    }

    return $list;
}

function getFilteredResponses($overview_arr,$quiz_object,$unique_courses){
    $temp_arr=array();

    //print_r($overview_arr);die();
    $gradingMethod=$quiz_object->grademethod;

    foreach($overview_arr as $overview_ar)
    {
        if($gradingMethod!=2){
            if($overview_ar->marked_obtained_on_attempt==$overview_ar->quiz_final_grade){
                $temp_arr[$overview_ar->email]=$overview_ar;
            }
        }else{

               foreach($unique_courses as $key => $value){
                 $overview_ar->$key="NA";
                }
                $overview_ar->finish_date="NA";
                $overview_ar->finish_time="NA";
                $overview_ar->time_in_seconds="NA";
                $overview_ar->start_time="NA";
                $overview_ar->start_date="NA";
                $overview_ar->percentage="NA";
                $overview_ar->total_score_achieved="NA";
                $overview_ar->marked_obtained_on_attempt="NA";
                $overview_ar->grade_letter="NA";
                $temp_arr[$overview_ar->email]=$overview_ar;
        }


        /*if(array_key_exists($overview_ar->email,$temp_arr)){

             //get stored array
             $stored_obj=$temp_arr[$overview_ar->email];

             $current_object=$overview_ar;

             $stored_marked=$stored_obj->marked_obtained_on_attempt;
             $current_marked=$current_object->marked_obtained_on_attempt;

             if($stored_marked<$current_marked){
                $temp_arr[$overview_ar->email]=$overview_ar;
             }

        }else{
            $temp_arr[$overview_ar->email]=$overview_ar;
        }*/
    }
    return $temp_arr;
}

function report_timespent_get_code($codetype){

    global $DB;

    $sql = "SELECT
                d.id,
                d.data
            FROM mdl_user_info_data d,
                mdl_user_info_field info
            WHERE d.fieldid=info.id
            AND info.shortname='$codetype'
            GROUP BY d.data";

    $records = $DB->get_records_sql($sql);
    return $records;
}


