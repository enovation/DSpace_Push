<?php

/**
 * Core plugin class providing common plugin methods
 *
 * @package ndlrexport
 */
class ndlrPlugin {

    /**
     *
     * @param stdClass $course
     * @param stdClass $module
     * @param stdClass $context
     * @param stdClass $cm
     */
    public function __construct( stdClass &$course, stdClass &$module, stdClass &$context, stdClass &$cm ) {
        $this->course = $course;
        $this->module = $module;
        $this->context = $context;
        $this->cm = $cm;
        $this->package = $this->getPackage();
    }

    /**
     *
     * @global stdClass $CFG
     * @return Array File details
     */
    public function getPackage() {
        global $CFG;

        if (!$file_set = $this->getFilesByContextId()) {
            throw new Exception("There were no files to package in getPackage()");
        }

        check_dir_exists($CFG->dataroot.'/temp/zip');
        $tmpfile = tempnam($CFG->dataroot.'/temp/zip', 'zipstor');
        $zipfile = new ZipArchive();
        $zipfile->open( $tmpfile, 0 );

        foreach ( $file_set as $fileObj ) {
            if ($fileObj->get_filesize() == 0 && $fileObj->get_filename() == '.') continue;
            $zipfile_filename = trim($fileObj->get_filepath() . $fileObj->get_filename(), '/');
            if ( ! $zipfile->addFromString($zipfile_filename, $fileObj->get_content()) ) {
                //echo "Could not add: {$zipfile_filename}<br/>\n";
            }
        }

        $zipfile->close();
        $content = file_get_contents($tmpfile);
        return array('mimetype'=>'application/zip',
                     'content'=>$content,
                     'tmpfile'=>$tmpfile,
                     'filename'=>$this->getFilename().".zip",
                     'size' => sizeof($content));
    }

    /**
     *
     * @return string mod_modulename
     */
    public function getComponentName() {
        return "mod_{$this->module->name}";
    }

    /**
     *
     * @global MoodleDatabase $DB
     * @return Array File instance
     */
    public function getFilesByContextId() {
        global $DB;

        $fs = get_file_storage();
        $file_collection = array();
        if (!$file_set = $DB->get_records( 'files',
                array("contextid"=>$this->context->id,
                      "component"=>$this->getComponentName(),
                      "filearea"=>'content'
                      ) )) return false;

        foreach ($file_set as $stdFile) {
            $file_collection[] = $fs->get_file_instance( $stdFile );
        }

        return $file_collection;
    }

    /**
     *
     * @global stdClass $CFG
     * @global MoodleDatabase $DB
     * @return string module instance name detail
     */
    public function getFilename() {
        global $CFG, $DB;

        $result = $DB->get_record( $this->module->name, array('id'=>$this->cm->instance,'course'=>$this->cm->course));
        if (!$result || empty($result->name)) return "Moodle ".$this->getComponentName()." Export";

        return $result->name;
    }
}

/**
 * Core plugin factory class
 *
 * @package ndlrexport
 * @var array $supported Supported modules
 */
class ndlrPluginFactory {

   static $supported = array('file','folder','imscp','scorm','resource');

   /**
    * Function to include plugin class files
    */
   static public function loadPlugins() {
       /* include all plugins */
       $plugindirectory = dirname(__FILE__);
       $dh = opendir( $plugindirectory );
       while ( $fn = readdir( $dh )) {
           $file_path = $plugindirectory . DIRECTORY_SEPARATOR . $fn;

           if (!is_file($file_path)) continue;
           if ( $fn == "plugin.php") continue;
           if ( $fn == "." || $fn == "..") continue;
           if (strlen($fn) < 5) continue;
           if (substr($fn, (strlen($fn)-4), 4) != '.php') continue;

           if (!include_once( $file_path )) {
               throw new Exception("Could not include file {$file_path}");
           }
       }
   }

   /**
    *
    * @global MoodleDatabase $DB
    * @param stdClass $cm Course module
    * @return ndlrPlugin Plugin instance
    */
   static public function getPlugin( stdClass &$cm ) {
       global $DB;
       
       if (!$cm) throw new Exception("Plugin requested without providing course module object");

       $course = $DB->get_record("course", array("id"=>$cm->course));
       $module = $DB->get_record("modules", array("id"=>$cm->module));
       $context = get_context_instance(CONTEXT_MODULE, $cm->id);

       if (!$module) throw new Exception("Requested module could not be found for cm {$cm->id}.");
       if (!$course) throw new Exception("Requested course could not be found for cm {$cm->id}.");
       if (!$context) throw new Exception("Requested context could not be found for cm {$cm->id}.");


       $class_name = "ndlrPlugin" . ucfirst(strtolower($module->name));
       
       ndlrPluginFactory::loadPlugins();

       if ( class_exists($class_name) ) {
           $plugin = new $class_name($course, $module, $context, $cm);
           return $plugin;
       }else{
           throw new Exception("Class {$class_name} not found in plugin load request");
       }
   }

}
