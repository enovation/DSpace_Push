<?php


require_once( dirname(__FILE__)."/config.php"); // NDLR export config
require_once( dirname(__FILE__)."/../config.php"); //moodle config
require_once( $CFG->dirroot.'/lib/formslib.php');
require_once( $CFG->dirroot.'/lib/filestorage/file_storage.php');
require_once( dirname(__FILE__)."/lib.php");
require_once(dirname(__FILE__)."/plugins/plugin.php");


/*
 * Context and permissions
 */
$cmid     = optional_param('cmid', 0, PARAM_INT);
if (!$cm = $DB->get_record("course_modules", array("id"=>$cmid))) {
    throw new Exception( get_string('ndlrinvalidcoursemodule', 'block_ndlrexport') );
}

$context = get_context_instance(CONTEXT_COURSE, $cm->course);
if ( ! has_capability('moodle/backup:backupcourse', $context) ) throw new moodle_exception('invalidaccess');
$PAGE->set_context( $context );


/*
 * Page setup
 */
$url = new moodle_url('/ndlrexport/metadata.php', array(
           'cmid'=>$cmid,
           'contextid'=>$context->id,
           'courseid'=>$cm->course));

$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

$heading = get_string('ndlrmetadataheading', 'block_ndlrexport');

$PAGE->set_title($heading);
$PAGE->set_heading($heading);


/*
 * Form processing
 */
if (!$resourcePlugin = ndlrPluginFactory::getPlugin( $cm )) throw new Exception("Could not find an export plugin for the requested resource.");
$form = new metadata_form( $resourcePlugin );

if ( $form->is_cancelled() ) {
    redirect( $CFG->wwwroot . "/ndlrexport/?courseid={$cm->course}");
}elseif ( $metadata = $form->get_data() ) {
    try {
        ndlrexport_swordpush( $metadata, $resourcePlugin );
    } catch ( Exception $e ) {
        print_error( get_string('ndlrpushfailed', 'block_ndlrexport') . $e->getMessage() );
    }
    notice("OK: ".get_string('ndlrpushsuccess', 'block_ndlrexport'), $CFG->wwwroot . "/ndlrexport/?courseid={$cm->course}&cmid={$cm->id}");
}

/*
 * Page output
 */

echo $OUTPUT->header();

echo '
<script type="text/javascript" src="./lib/jquery.js"></script>
<script type="text/javascript" src="./lib/jquery.mcdropdown.js"></script>
<script type="text/javascript" src="./lib/jquery.bgiframe.js"></script>

<!---// load the mcDropdown CSS stylesheet //--->
<link type="text/css" href="./css/jquery.mcdropdown.css" rel="stylesheet" media="all" />
    <style type="text/css">
        input[type="text"] { width: 300px; }
        fieldset#ndlrmetadata textarea { width: 300px; height: 150px; }
        div#ndlrloading {
            position: absolute;
            z-index: 99;
            margin: 0;
            top: 0;
            right: 0;
            background-color: #FF9999;
            color: #333;
            border: 1px solid #FF0000;
            font-weight: bold;
            padding: 5px;
            visibility: hidden;
        }
        div#ndlrloading img {
            vertical-align:text-top;
            padding-left: 5px;
        }
        div#addNewKeyword {
            position: relative;
            width: 60px;
            left: 417px;
            top: 0px;
            height: 15px;
        }
        div#addNewKeyword input {
            height: 21px;
        }
    </style>
';

echo '<img src="'.$CFG->wwwroot.'/ndlrexport/images/ndlrlogo.png" style="width: 363px; height: 70px;" alt="NDLR Logo" title="NDLR - National Digital Learning Resources"/>';

$form->display();

include( dirname(__FILE__) . '/lib/isced_keywords.php');
echo '<div id="ndlrloading">Exporting to NDLR....please wait <img src="'.$CFG->wwwroot.'/ndlrexport/images/ajax-loader.gif"/></div>';

echo $OUTPUT->footer();
