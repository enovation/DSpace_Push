<?php

require_once(dirname(__FILE__)."/plugins/plugin.php");

/**
 *
 * @global moodleDatabase $DB
 * @global stdClass $CFG
 * @param int $courseid
 * @return string HTML output
 */
function ndlrexport_selectionform( $courseid  = 0 ) {
    global $DB, $CFG;
    ob_start(); //buffering

    echo "<h2>".get_string('ndlrselectitem', 'block_ndlrexport')."</h2>";

    echo '
        <style type="text/css">
           table.mod_export_list { 
               width: 550px;
               padding: 0;
               border-collapse: collapse;
               margin-left: 40px;
           }
           table.mod_export_list td {
                border: 1px solid #AAAAAA;
                padding: 2px;
           }
           table.mod_export_list tr.data_item:hover {
                background-color: #FFC;
           }
           table.mod_export_list thead { font-weight: bold; }
           table.mod_export_list td.instance_name { width: 350px; }
           table.mod_export_list td.instance_timemodified { width: 100px; }
           table.mod_export_list td.instance_exportlink { width: 100px; text-align: center; }
        </style>
    ';

    $exportable_count = 0;
    foreach(ndlrPluginFactory::$supported as $mod) {

        if (!$instanceSet = $DB->get_records_sql("select cm.id,m.name,course,instance from {modules} m
                 left join {course_modules} cm on cm.module = m.id
                 where m.name='{$mod}' and course='{$courseid}'")) {
            continue;
        }

        $exportable_count++;
        echo "<h3>".ucfirst($mod)."</h3>\n";
        echo "<table class=\"mod_export_list\">
              <thead>
                <tr>
                    <td>".get_string('ndlritemname', 'block_ndlrexport')."</td>
                    <td>".get_string('ndlrlastmodified', 'block_ndlrexport')."</td>
                    <td> </td>
                </tr>
              </thead>
              ";
        foreach ( $instanceSet as $cm ) {
            if (!$cm->name || !$cm->instance) continue;
            if (!$instance = $DB->get_record( $cm->name, array('id'=>$cm->instance))) continue;
            echo "
              <tr class=\"data_item\">
                    <td class=\"instance_name\">{$instance->name}</td>
                    <td class=\"instance_timemodified\">".((!empty($instance->timemodified))?date("d/m/Y H:i", $instance->timemodified):'')."</td>
                    <td class=\"instance_exportlink\">
                        <a title=\"".get_string('ndlrexportinstructions', 'block_ndlrexport')."\" href=\"{$CFG->wwwroot}/ndlrexport/metadata.php?cmid={$cm->id}\">".
                        get_string('ndlrselectforexport', 'block_ndlrexport')."</a>
                    </td>
              </tr>
            ";
        }
        echo "</table>";

    }

    if ($exportable_count < 1) {
        ob_clean();
        notice(get_string('ndlrnocontent', 'block_ndlrexport'), $CFG->wwwroot . "/course/view.php?id={$courseid}");
    }

    $return = ob_get_contents();
    ob_clean();
    return $return;
}




require_once( $CFG->dirroot.'/lib/formslib.php');
/**
 * Metadata form class
 */
class metadata_form extends moodleform {

    /**
     *
     * @param ndlrPlugin $resourcePlugin
     */
    function __construct( &$resourcePlugin = false ) {
        $this->plugin = $resourcePlugin;
        parent::__construct();
    }
    
    /**
     * This function overrides the normal moodle form get_data
     * so that we can process dynamic fields
     * 
     * @return stdClass
     */
    public function get_data() {
        $mform =& $this->_form;

        if ($this->is_submitted() and $this->is_validated()) {
            $data = $mform->exportValues();
            unset($data['sesskey']); // we do not need to return sesskey
            unset($data['_qf__'.$this->_formname]);   // we do not need the submission marker too
            if (empty($data)) {
                return NULL;
            } else {
                $returndata = (object)$data;

                if ($_POST) {
                    $i = 1;
                    while ($i) {
                       $isced = 'isced_'.$i;
                       if (isset($_POST[$isced])) {
                           if (!isset($returndata->$isced)) $returndata->$isced = $_POST[$isced];
                           $i++;
                       }else { $i=0; }
                    }
                }

                return $returndata;
            }
        } else {
            return NULL;
        }
    }
    /**
     * Form definition
     *
     * @global stdClass $CFG
     * @global Course $COURSE
     */
    function definition() {
        global $CFG, $COURSE;

        $mform =& $this->_form;

        $mform->addElement('header', 'ndlrmetadata', get_string('ndlrmetadata', 'block_ndlrexport') .
                " - Exporting \"{$this->plugin->getFilename()}\" ({$this->plugin->getComponentName()})");

        //Title
        $mform->addElement('text', 'title', get_string('ndlrdtitle', 'block_ndlrexport'));
        $mform->addRule('title', get_string('required'), 'required', null, 'client');
        $mform->setType('title', PARAM_MULTILANG);

        //Authors
        $mform->addElement('text', 'author1', get_string('ndlrdauthor1', 'block_ndlrexport'));
        $mform->addRule('author1', get_string('required'), 'required', null, 'client');
        $mform->setType('author1', PARAM_MULTILANG);

        $mform->addElement('text', 'author2', get_string('ndlrdauthor2', 'block_ndlrexport'));
        $mform->setType('author2', PARAM_MULTILANG);

        $mform->addElement('text', 'author3', get_string('ndlrdauthor3', 'block_ndlrexport'));
        $mform->setType('author3', PARAM_MULTILANG);

        //Abstract (optional)
        $mform->addElement('textarea', 'abstract', get_string('ndlrdabstract', 'block_ndlrexport'));
        $mform->setType('abstract', PARAM_MULTILANG);

        //Type of items
        $options = array(
             '' => 'select...',
             'Learning Object' => 'Learning Object',
             'Animation' => 'Animation',
             'Article' => 'Article',
             'Book' => 'Book',
             'Book chapter' => 'Book chapter',
             'Dataset' => 'Dataset',
             'Image' => 'Image',
             'Image, 3-D' => 'Image, 3-D',
             'Map' => 'Map',
             'Musical score' => 'Musical score',
             'Plan or blueprint' => 'Plan or blueprint',
             'Preprint' => 'Preprint',
             'Presentation' => 'Presentation',
             'Recording, acoustical' => 'Recording, acoustical',
             'Recording, musical' => 'Recording, musical',
             'Recording, oral' => 'Recording,oral',
             'Software' => 'Software',
             'Technical Report' => 'Techical Report',
             'Thesis' => 'Thesis',
             'Video' => 'Video',
             'Working Paper' => 'Working Paper',
             'Other' => 'Other'
        );
        $mform->addElement('select', 'type', get_string('ndlrditemtype', 'block_ndlrexport'), $options);

        //Existing URL for item (optional):
        $mform->addElement('text', 'url', get_string('ndlrdurl', 'block_ndlrexport'));
        $mform->setType('url', PARAM_MULTILANG);
        $mform->setDefault('url', $CFG->wwwroot."/course/view.php?id={$this->plugin->course->id}");

        //Rights dropdown
        $rights_options = array(
            ''=>'select...',
            'Y'=>'NDLR Restricted License',
            'N'=>'Creative Commons Attribution-Non-Commercial-Share Alike 3.0 License'
        );
        $mform->addElement('select', 'rights', get_string('ndlrdrights', 'block_ndlrexport'), $rights_options);
        $mform->addRule('rights', get_string('required'), 'required', null, 'client');
        $mform->setType('rights', PARAM_MULTILANG);
        
        //ISCED Keywords (optional):
        $mform->addElement('text', 'isced_1', get_string('ndlrdiscedkeywords', 'block_ndlrexport'));
        $mform->addRule('isced_1', get_string('required'), 'required', null, 'client');
        $mform->setType('isced_1', PARAM_MULTILANG);

        //ISCED Keywords (optional):
        //$mform->addElement('text', 'isced_2', '');
        //$mform->setType('isced_2', PARAM_MULTILANG);






        $mform->addElement('hidden','cmid', $this->plugin->cm->id);
        $mform->setType('cmid', PARAM_INT);


        $buttonarray = array();
        //$buttonarray[] = &$mform->createElement('submit', 'preview', get_string('preview'), 'xx');
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('ndlrexportgo', 'block_ndlrexport'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }


    /**
     *
     * @global stdClass $CFG
     * @global Course $COURSE
     * @param array $data Form data fields/values
     * @param array $files Form files (if any)
     * @return string Errors
     */
    function validation($data, $files) {
        global $CFG, $COURSE;
        
        $errors = parent::validation($data, $files);

        return $errors;
    }
}

/**
 * ndlrexport interface to the swordlib.  Executes push to DSpace
 *
 * @global stdClass $cfgsword SWORD lib configuration
 * @global stdClass $CFG Moodle configuration
 * @param array $metadata Meta data fields and values
 * @param ndlrPlugin $resourcePlugin
 */
function ndlrexport_swordpush( &$metadata, &$resourcePlugin ) {
    global $cfgsword, $CFG;

    // Load the SWORD library and packager
    require_once($cfgsword->librarylocation . '/swordappclient.php');
    require_once($cfgsword->librarylocation . '/swordappentry.php');
    require_once($cfgsword->librarylocation . '/packager_mets_swap.php');

    if (!isset($metadata->title) || empty($metadata->title)) throw new Exception("Metadata missing required title");
    if (!isset($metadata->author1) || empty($metadata->author1)) throw new Exception("Metadata missing required author");

    $uid = md5(crypt(microtime(). rand( rand(1,9), rand(1000,9000)), microtime()));
    $base = ndlrexport_createdirs( $uid );

    $packagefilename = 'packager-' . $uid . '.zip';

    $package = new PackagerMetsSwap( $base , 'attachments/',
			            $base, $packagefilename);

    $package->setTitle($metadata->title);
    $package->addCreator( $metadata->author1 );
    if (isset($metadata->author2) && !empty($metadata->author2)) $package->addCreator( $metadata->author2 );
    if (isset($metadata->author3) && !empty($metadata->author3)) $package->addCreator( $metadata->author3 );
    if (isset($metadata->abstract) && !empty($metadata->abstract)) $package->setAbstract($metadata->abstract);
    if (isset($metadata->type) && !empty($metadata->type)) $package->setType( $metadata->type );
    //isced keywords
    $i = 1;
    while ($i) {
       $isced = 'isced_'.$i;
       if (isset($metadata->$isced)) {
           if (!empty($metadata->$isced)) $package->addKeyword( $metadata->$isced );
           $i++;
       }else { $i=0; }
    }
    if (isset($metadata->isced) && !empty($metadata->isced)) $package->addKeyword( $metadata->isced );
    //rights
    $license_file = false;
    switch ($metadata->rights) {
        case 'Y':
            $license_file = dirname(__FILE__).'/private/licenses/restrictivelicense.txt';
            break;
        case 'N':
            $license_file = dirname(__FILE__).'/private/licenses/cclicense.txt';
            break;
    }
    if ( $license_file && is_file($license_file) ) {
        $package->setLicense( $metadata->rights );
        file_put_contents( $base."attachments/license.txt", file_get_contents($license_file));
    }
    
    file_put_contents( $base."attachments/".$resourcePlugin->package['filename'], $resourcePlugin->package['content']);
    $package->addFile( $resourcePlugin->package['filename'], $resourcePlugin->package['mimetype']);
    $package->create();

    try {
        $client = new SWORDAPPClient();
        $response = $client->deposit($cfgsword->url, $cfgsword->user, $cfgsword->password, '',
                    $base . $packagefilename,
                    'http://purl.org/net/sword-types/METSDSpaceSIP',
                    'application/zip', false, true);
    } catch ( Exception $e ) {
        rrmdir( $base );
        throw $e;
    }
    rrmdir( $base );
    return true;
}

/**
 * Function to create temporary workin directories for the export
 * 
 * @global stdClass $CFG Moodle Config
 * @param string $uid Unique folder identifier
 * @return string Server temporary folder path
 */
function ndlrexport_createdirs( $uid ) {
    global $CFG;
    
    if (!is_dir($CFG->dataroot . "/ndlrexport"))
      mkdir( $CFG->dataroot . "/ndlrexport", 0777);

    if (!is_dir($CFG->dataroot . "/ndlrexport/{$uid}"))
      mkdir( $CFG->dataroot . "/ndlrexport/{$uid}", 0777);

    if (!is_dir($CFG->dataroot . "/ndlrexport/{$uid}/attachments"))
      mkdir( $CFG->dataroot . "/ndlrexport/{$uid}/attachments", 0777);

    return $CFG->dataroot . "/ndlrexport/{$uid}/";
}

/**
 * Function to recursively remove directories
 *
 * @param string $dir  Directory path
 */
 function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
         else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
 }