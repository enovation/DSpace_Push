<?php


require_once( dirname(__FILE__)."/config.php"); // NDLR export config
require_once( dirname(__FILE__)."/../config.php"); //moodle config
require_once( dirname(__FILE__)."/lib.php");

$courseid        = optional_param('courseid', 0, PARAM_INT);
if (!$course = $DB->get_record('course', array('id'=>$courseid))) throw new Exception("Invalid course ID provided.");

$context = get_context_instance(CONTEXT_COURSE, $course->id);
if ( ! has_capability('moodle/backup:backupcourse', $context) ) throw new moodle_exception('invalidaccess');
$PAGE->set_context( $context );

$url = new moodle_url('/ndlrexport/index.php', array(
    'contextid'=>$context->id,
    'courseid'=>$course->id,
    )
);


$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$heading = get_string('ndlrexportlistheader', 'block_ndlrexport');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);



echo $OUTPUT->header();
echo '<img src="'.$CFG->wwwroot.'/ndlrexport/images/ndlrlogo.png" style="width: 363px; height: 75px;" alt="NDLR Logo" title="NDLR - National Digital Learning Resources"/>';
echo "<h1>".get_string('ndlrexporting', 'block_ndlrexport')." <a href=\"{$CFG->wwwroot}/course/view.php?id={$courseid}\">{$course->fullname}</a></h1>";

echo ndlrexport_selectionform( $course->id );
echo $OUTPUT->footer();
