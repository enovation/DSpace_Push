<?php

/**
 * Plugin to handle moodle file resources
 *
 * @package ndlrexport
 */
class ndlrPluginFile extends ndlrPlugin {

    /**
     *
     * @global MoodleDatabase $DB
     * @return String Resource name 
     */
    public function getFilename() {
        global $DB;

        $result = $DB->get_record( 'resource', array('id'=>$this->cm->instance,'course'=>$this->cm->course));
        if (!$result || empty($result->name)) return "Moodle ".$this->getComponentName()." Export";

        return $result->name;
    }
    
}