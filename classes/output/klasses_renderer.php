<?php
/**
 * Created by PhpStorm.
 * User: ishineguy
 * Date: 2018/06/26
 * Time: 13:16
 */

namespace block_readaloudteacher\output;

use \block_readaloudteacher\constants;
use \block_readaloudteacher\common;
use \block_readaloudteacher\reports;

class klasses_renderer extends \plugin_renderer_base {

    //prepare and display the content that goes in the block: by klasses
    function fetch_block_content_byklass($coursedata, $config){
        global $USER;

        //show our intro text
        $content = $this->output->heading(get_string('readaloudteacherarea',constants::M_COMP),3, 'blockheading');
        if(!$coursedata || count($coursedata)<0){
            $content .= \html_writer::div(get_string('nodataavailable',constants::M_COMP),'alert alert-info');
        }else{
            $manageclassesstr = get_string('manageklasses', constants::M_COMP);
            foreach($coursedata as $thecourse){

                //fetch course
                $coursetitle = $this->output->heading($thecourse->fullname,5);

                //fetch classes
                $klasses = common::fetch_klasses($USER->id,$thecourse->id,$config->klassdisplay);

                //If no classes we show a getting started panel with an add button
                if(!$klasses){
                    return  $coursetitle . $this->fetch_letsgetstarted_panel($thecourse->id);
                }

                //if classes and we have custom class ability, we show a manage button. And class tabs
                switch($config->klassdisplay){
                    case constants::M_KLASSDISPLAYCUSTOM:
                    case constants::M_KLASSDISPLAYGROUPCUSTOM:
                        $url = new \moodle_url('/blocks/readaloudteacher/klasses/klasses.php',array('courseid'=>$thecourse->id));
                        $managebutton =\html_writer::link(
                                $url->out(),
                                $this->pix_icon('cog', $manageclassesstr, 'block_readaloudteacher') . $manageclassesstr,
                                ['class' => 'btn btn-secondary pull-right', 'id' => 'manageclasses']
                        );
                        break;
                    case constants::M_KLASSDISPLAYGROUP:
                    case constants::M_KLASSDISPLAYNONE:
                    default:
                        $managebutton='';
                }


                $class_tabtitles=array();
                $class_tabpanels=array();
                foreach($klasses as $klass){
                    //klass buttons
                    $params['id']=$thecourse->id;

                    //klass header
                    $klass_overview_container = $this->fetch_klass_overview($klass,$thecourse);

                    //tab and tabpanel
                    $class_tabtitles[]=get_string('classtabheader',constants::M_COMP,$klass->name);
                    $klassmemberids= $klass->fetch_klassmemberids();
                    $class_tabpanels[]= $klass_overview_container  . $this->fetch_klass_summarytable($thecourse, $klassmemberids, $klass);
                }
                $classes_tabset = $this->render_tabs($class_tabtitles,$class_tabpanels);
                $classes_container = \html_writer::div($classes_tabset, constants::M_COMP . '_classescontainer');

                $content .= \html_writer::div($managebutton . $coursetitle . $classes_container, 'block_readaloudteacher_content');
            }
        }
        return $content;

    }

    //Fetch "Get Started" panel for when there are no klasses
    function fetch_letsgetstarted_panel($thecourseid){
        global $CFG;
        $addbutton = $this->fetch_addklass_button($thecourseid);
        $title = $this->output->heading(get_string('getstartedtitle',constants::M_COMP), 4);
        $message = get_string('getstartedmessage',constants::M_COMP);
        $pic =\html_writer::img($CFG->wwwroot . constants::M_URL . '/pix/mrseedstanding.png','Read Seed',array('style'=>'with: 100px'));
        $getstarted_container = \html_writer::div($title . $pic . $this->render($addbutton) . '<br/>' . $message , constants::M_COMP . '_getstartedcontainer');
        return $getstarted_container;

    }

    //Fetch klasses table
    function fetch_klasses_table($klasses, $courseid){
        global $DB;

        $params=[];
        $params['courseid']=$courseid;
        $baseurl = new \moodle_url(constants::M_URL . '/klasses/klasses.php', $params);

        //header
        $course =get_course($courseid);
        $courseheader =  \html_writer::tag('h3',get_string('classesfor',constants::M_COMP,$course->fullname));

        //add class button
        $addbutton = $this->fetch_addklass_button($courseid);

        $data = array();
        foreach($klasses as $klass) {
            $fields = array();

            //Klass name
            $tmpl = new \block_readaloudteacher\output\klassname($klass);
            $fields[] = $this->output->render_from_template('core/inplace_editable', $tmpl->export_for_template( $this->output));

            //ID Number
            $fields[] = $klass->idnumber;

            //member count
            $fields[] = $klass->membercount;

            $buttons = array();

            $urlparams = array('id' => $klass->id,'klasstype'=>$klass->type,'courseid'=>$courseid, 'returnurl' => $baseurl->out_as_local_url());

            /*
             * //we use inplace editing for class name, and thats all we need unless we add more attributes to the "class"
            $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/klasses/edit.php', $urlparams),
                    $this->output->pix_icon('t/edit', get_string('edit')),
                    array('title' => get_string('edit')));
            */
            if($klass->can_manage_members()) {
                $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/klasses/edit.php',
                        $urlparams + array('delete' => 1)),
                        $this->output->pix_icon('t/delete', get_string('delete')),
                        array('title' => get_string('delete')));
                $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/klasses/assign.php', $urlparams),
                        $this->output->pix_icon('i/users', get_string('assign', constants::M_COMP)),
                        array('title' => get_string('assign', constants::M_COMP)));
                $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/klasses/view.php', $urlparams),
                        $this->output->pix_icon('i/preview', get_string('view', constants::M_COMP)),
                        array('title' => get_string('view', constants::M_COMP)));
            }else{
                $buttons[] = \html_writer::link(new \moodle_url(constants::M_URL . '/klasses/view.php', $urlparams),
                        $this->output->pix_icon('i/preview', get_string('view', constants::M_COMP)),
                        array('title' => get_string('view', constants::M_COMP)));
            }



            $fields[] = implode(' ', $buttons);

            $data[] = $row = new \html_table_row($fields);
            if (!$klass->visible) {
                $row->attributes['class'] = 'dimmed_text';
            }
        }

        $table = new \html_table();
        $table->head  = array(get_string('klassname', constants::M_COMP),
                get_string('idnumber', constants::M_COMP),
                get_string('membercount', constants::M_COMP),get_string('action'));
        $table->colclasses = array('leftalign name', 'leftalign size','centeralign action');

        $table->id = 'klasses';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;

        //return add button and table
        return $courseheader . $this->render($addbutton) .  \html_writer::table($table);

    }

    //return the left <-> right toggle that allows user to maanage class members
    function fetch_klasses_assigncomponent($data){
        return $this->render_from_template('block_readaloudteacher/assigncomponent', $data);
    }

    //Fetch klasses table
    function fetch_klassmember_table($klassmembers, $klass, $courseid){
        global $DB;

        //header
        $courseheader =  \html_writer::tag('h3',get_string('membersfor',constants::M_COMP,$klass->name));


        $data = array();
        foreach($klassmembers as $member) {
            $fields = array();
            $fields[] = fullname($member);
            $data[] = $row = new \html_table_row($fields);

        }

        $table = new \html_table();
        $table->head  = array(get_string('fullname'));
        $table->colclasses = array('leftalign name');

        $table->id = 'klass_members';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data  = $data;

        //back to top button
        $returnurl =new \moodle_url(constants::M_URL . '/klasses/klasses.php',array('courseid'=>$courseid));
        $backbutton = $this->fetch_return_button($returnurl);

        //return add button and table
        return $courseheader .   $backbutton . \html_writer::table($table) . $backbutton;

    }


    //return a button that will allow user to add a new class
    function fetch_addklass_button($courseid){
        $thebutton = new \single_button(
                new \moodle_url(constants::M_URL . '/klasses/edit.php',array('courseid'=>$courseid)),
                get_string('addklass', constants::M_COMP), 'get');
        return $thebutton;
    }

    //return a button that will allow user to edit a class
    function fetch_manageklass_button($klass, $courseid,$returnurl){
        $buttonparams = array('id' => $klass->id,'courseid'=>$courseid, 'returnurl' => $returnurl);
        $thebutton = new \single_button(
                new \moodle_url(constants::M_URL . '/klasses/assign.php',$buttonparams),
                get_string('manageklass', constants::M_COMP, $klass->name), 'get');
        return $thebutton;
    }

    //return a button that will take you top klass reports page
    function fetch_klassreports_button($klass, $courseid,$returnurl){
        $buttonparams = array('klassid' => $klass->id,'klasstype' => $klass->type,'courseid'=>$courseid, 'returnurl' => $returnurl);
        $thebutton = new \single_button(
                new \moodle_url(constants::M_URL . '/klasses/klassreports.php',$buttonparams),
                get_string('klassreportsbutton', constants::M_COMP), 'get');
        return $thebutton;
    }

    //return a back to course button for a report
    function fetch_returntocourse_button($courseid){
        $returnbutton = new \single_button(
                new \moodle_url(  '/course/view.php',array('id'=>$courseid)),
                get_string('returntocourse', constants::M_COMP), 'get');
        return $this->render($returnbutton);
    }

    //return a back to to button for a report
    function fetch_return_button($returnurl){
        $returnbutton = new \single_button(
                new \moodle_url(  $returnurl),
                get_string('backtotop', constants::M_COMP), 'get');
        return $this->render($returnbutton);
    }

    //return an export to csv button for a report
    function fetch_export_button($showreport,$courseid,$klass=false){
        if($klass){
            $klassid=$klass->id;
            $klasstype=$klass->type;
        }else{
            $klassid=0;
            $klasstype=0;
        }
        $exportbutton = new \single_button(
            new \moodle_url(constants::M_URL . '/reports.php',
                    array('format'=>'csv','showreport'=>$showreport,'courseid'=>$courseid,'klassid'=>$klassid,'klasstype'=>$klasstype)),
            get_string('exportcsv', constants::M_COMP), 'get');
        return $exportbutton;
    }

    // Fetch an overview of the klass stats.
    function fetch_klass_overview($klass, $thecoursedata){
        $iscoursescreen = optional_param('klassid', 0, PARAM_INT) ? false : true;
        $templateable = new \block_readaloudteacher\output\klass_charts($klass, $thecoursedata, true, $iscoursescreen, false);
        $data = $templateable->export_for_template($this);
        return $this->render_from_template('block_readaloudteacher/dash-top', $data);
    }

    function fetch_klass_summarytable($thecoursedata, $klassmemberids=false, $klass=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_klass_summary',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_KLASSSUMMARY,$thecoursedata,true, $klassmemberids, $klass);
        foreach($rows as $row) {
            if (!empty($row->go)) {
                $row->go = $this->render_from_template(
                    'block_readaloudteacher/go-buttons',
                    array_merge(['userid' => $row->userid], $row->go)
                );
            } else {
                $row->go = '';
            }
        }
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        //apply data table, order by date desc
        $filtercolumn=false;
        $filterlabel=false;
        $order=array();
        $order[0] =array(1, "asc"); //last name asc
        $order[1] =array(2, "asc"); //first name asc
        $this->setup_datatables($tableid,$filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function fetch_klassreport_readingscomplete($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_klassreport_readingscomplete',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_KLASSREADINGSCOMPLETE,$thecoursedata,true, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        //apply data table, order by date desc
        $filtercolumn=false;
        $filterlabel=false;
        $order=array();
        $order[0] =array(1, "asc"); //last name asc
        $order[1] =array(2, "asc"); //first name asc
        $this->setup_datatables($tableid,$filtercolumn, $filterlabel, $order);
        return $thetable;
    }

    function fetch_klassreport_wpm($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_klassreport_wpm',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_KLASSWPM,$thecoursedata,true, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        //add datatables to report
        $filtercolumn=false;
        $filterlabel=false;
        $order=array();
        $order[0] =array(1, "asc"); //last name asc
        $order[1] =array(2, "asc"); //first name asc
        $this->setup_datatables($tableid,$filtercolumn, $filterlabel, $order);

        return $thetable;
    }

    function fetch_klassreport_accuracy($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_klassreport_accuracy',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_KLASSACCURACY,$thecoursedata,true, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        //add datatables to report
        $filtercolumn=false;
        $filterlabel=false;
        $order=array();
        $order[0] =array(1, "asc"); //last name asc
        $order[1] =array(2, "asc"); //first name asc
        $this->setup_datatables($tableid,$filtercolumn, $filterlabel, $order);

        return $thetable;
    }

    function fetch_klassreport_qscore($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_klassreport_qscore',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_KLASSQSCORE,$thecoursedata,true, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        //add datatables to report
        $filtercolumn=false;
        $filterlabel=false;
        $order=array();
        $order[0] =array(1, "asc"); //last name asc
        $order[1] =array(2, "asc"); //first name asc
        $this->setup_datatables($tableid,$filtercolumn, $filterlabel, $order);

        return $thetable;
    }

    //fetch a little graphical overview of the user
    function fetch_user_overview($userid, $thecoursedata, $klass=false){
        global $DB;
        $klassmemberids = array($userid);
        $totalwpm = 0;
        $totalquiz=0;
        $totalaccuracy=0;
        $totalreadings=0;
        foreach($thecoursedata->userreadings as $thereading){
            if($thereading->userid!=$userid){continue;}

            //total readings
            $totalreadings++;

            //WPM
            if ($thereading->h_wpm) {
                $totalwpm+= $thereading->h_wpm;
            } else if ($thereading->ai_wpm) {
                $totalwpm+= $thereading->ai_wpm;
            }

            //Accuracy
            if ($thereading->h_accuracy) {
                $totalaccuracy+= $thereading->h_accuracy;
            } else if ($thereading->ai_accuracy) {
                $totalaccuracy+= $thereading->ai_accuracy;
            }

            //qscore
            if($thereading->qscore != null && $thereading->qscore != '') {
                $totalquiz+= $thereading->qscore;
            }
        }
        $avwpm = $totalwpm;
        $avquiz=$totalquiz;
        $avaccuracy = $totalaccuracy;
        if($avwpm>0){$avwpm=round($avwpm/$totalreadings,0);}
        if($avaccuracy>0){$avaccuracy=round($avaccuracy/$totalreadings,0);}
        if($avquiz>0){$avquiz=round($avquiz/$totalreadings,0);}
        $user = get_complete_user_data('id', $userid);

        $context = array(
            'userid' => $userid,
            'userfullname' => fullname($user),
            'courseid' => $thecoursedata->id,
            'userimage' => $this->output->user_picture($user, array('size' => 100, 'link' => true)),
            'returnklass' => $klass,
            'indicators' => [
                ['id' => 'totalreadings', 'title' => get_string('totalreadings', constants::M_CLASS), 'value' => $totalreadings],
                ['id' => 'avwpm', 'title' => get_string('averagewordsperminute', constants::M_CLASS), 'value' => $avwpm],
                ['id' => 'avaccuracy', 'title' => get_string('averageaccuracy', constants::M_CLASS), 'value' => $avaccuracy],
                ['id' => 'avquiz', 'title' => get_string('averagequiz', constants::M_CLASS), 'value' => $avquiz]
            ]
        );
        return $this->render_from_template('block_readaloudteacher/user-report-top', $context);
    }

    function fetch_userreport_attempts($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_USERATTEMPTS,$thecoursedata,true,$klassmemberids);
        $tabletitle = get_string('tabtop_oneuserattempts',constants::M_COMP);
        $thetable = $this->render_table($tableid,$tabletitle,$reportname,$head,$fields,$rows);

        //set up a datatables for the report
        $order=array();
        $order[3] =array(1, "desc"); //full name asc
        $this->setup_datatables($tableid, false,false,$order);

        return $thetable;
    }


    function fetch_userreport_wpm($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_userreport_wpm',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_USERWPM,$thecoursedata,false, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        $order=array();
        $order[3] =array(1, "desc"); //full name asc
        $this->setup_datatables($tableid, false,false,$order);

        return $thetable;
    }

    function fetch_userreport_accuracy($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_userreport_accuracy',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_USERACCURACY,$thecoursedata,false, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        $order=array();
        $order[3] =array(1, "desc"); //full name asc
        $this->setup_datatables($tableid, false,false,$order);

        return $thetable;
    }

    function fetch_userreport_qscore($thecoursedata, $klassmemberids=false){
        $tableid = \html_writer::random_id(constants::M_COMP);

        //make table
        $tabletitle = get_string('tabtop_userreport_qscore',constants::M_COMP);
        list($reportname,$head,$fields,$rows) = reports::fetch_report_data(constants::M_REPORT_USERQSCORE,$thecoursedata,false, $klassmemberids);
        $thetable = $this->render_table($tableid, $tabletitle,$reportname,$head,$fields,$rows);

        $order=array();
        $order[3] =array(1, "desc"); //full name asc
        $this->setup_datatables($tableid, false,false,$order);

        return $thetable;
    }

    function render_tabs($labels,$panels){
        //paneids tie menu to panels
        $panelids=array();

        //build the menu from labels
        $itemtemplate = '<li class="nav-item"><a class="nav-link @active@" id="@panelid@-tab" data-toggle="tab" href="#@panelid@" role="tab" aria-controls="@label@" aria-selected="false">@label@</a></li>';
        $itemcount=0;
        $items_html='';
        foreach($labels as $label){
            $panelid= \html_writer::random_id(constants::M_COMP);
            $item = str_replace('@panelid@',$panelid,$itemtemplate);
            $item = str_replace('@label@',$label,$item);
            $item = str_replace('@active@',$itemcount==0?'active':'',$item);
            $panelids[$itemcount]=$panelid;
            $items_html .=$item;
            $itemcount++;
        }
        $menu_container = \html_writer::tag('ul',$items_html,
            array('class'=>'nav nav-tabs','id'=>\html_writer::random_id(constants::M_COMP),'role'=>'tablist'));

        //build panels from ...panels (duh)
        $panelcount=0;
        $panels_html='';
        foreach($panels as $panel){
            $panelid =$panelids[$panelcount];
            $active = $panelcount==0?'active':'';
            $paneldiv= \html_writer::div($panel,'tab-pane ' . $active ,array('id'=>$panelid, 'role'=>'tabpanel','aria-labelledby'=>$panelid .'-tab'));
            $panels_html .=$paneldiv;
            $panelcount++;
        }
        $panels_container= \html_writer::div($panels_html,'tab-content');

        return $menu_container . $panels_container;
    }


    function setup_datatables($tableid, $filtercolumn=false, $filterlabel=false, $order=false, $columns=false){

        $tableprops = new \stdClass();
        if($order){
            $tableprops->order=$order;
        }
        if($columns){
            $tableprops->columns=$columns;
        }


        //here we set up any info we need to pass into javascript
        $opts =Array();
        $opts['tableid']=$tableid;
        $opts['filtercolumn']=$filtercolumn;
        $opts['filterlabel']=$filterlabel;
        $opts['tableprops']=$tableprops;
        $this->page->requires->js_call_amd( constants::M_COMP . "/datatables", 'init', array($opts));
        if (!$this->page->headerprinted && !$this->page->requires->is_head_done()) {
            $this->page->requires->css( new \moodle_url('https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css'));
        }
    }

    /*
     * Make a table out of some data
     * sectiontitle = heading
     * report = report display name
     * head = array of title for the columns
     * rows = array of data objects
     * fields - array of names for each field(row object data members)
     *
     */
    public function render_table($tableid,$sectiontitle, $reportname, $head, $fields,$rows)
    {
        global $CFG;
        if (empty($rows)) {
            $sectiontitle = $this->output->heading($sectiontitle, 5);
            $message = \html_writer::div(get_string('nodataavailable', constants::M_COMP), 'alert alert-info');
            return $sectiontitle . $message;
        }

        //set up our table and head attributes
        $tableattributes = array('class' => 'generaltable ' . constants::M_COMP . '_table');
        $headrow_attributes = array('class' => constants::M_COMP . '_headrow');

        $htmltable = new \html_table();
        $htmltable->id = $tableid;
        $htmltable->attributes = $tableattributes;
        $htmltable->head =$head;


        foreach ($rows as $row) {
            $htr = new \html_table_row();
            //set up descrption cell
            $cells = array();
            foreach ($fields as $field) {
                $cell = new \html_table_cell($row->{$field});
                $cell->attributes = array('class' => constants::M_COMP . '_cell_' . $reportname . '_' . $field);
                $htr->cells[] = $cell;
            }

            $htmltable->data[] = $htr;
        }
        $html = $this->output->heading($sectiontitle, 5);
        $html .= \html_writer::table($htmltable);
        return $html;

    }
}