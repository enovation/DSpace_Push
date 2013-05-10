<?php

class ndlrPluginResource extends ndlrPlugin {
    public function getFilename() {
        global $DB;

        $result = $DB->get_record( 'resource', array('id'=>$this->cm->instance,'course'=>$this->cm->course));
        if (!$result || empty($result->name)) return "Moodle ".$this->getComponentName()." Export";

        return $result->name;
    }
}