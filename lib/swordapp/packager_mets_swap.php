<?php

class PackagerMetsSwap {

    // The location of the files (without final directory)
    public $sac_root_in;

    // The directroy to zip up in the $sac_root_in directory
    public $sac_dir_in;

    // The location to write the package out to
    public $sac_root_out;

    // The filename to save the package as
    public $sac_file_out;

    // The name of the metadata file
    public $sac_metadata_filename = "mets.xml";

    // The type (e.g. ScholarlyWork)
    public $sac_type;
    
    // The title of the item
    public $sac_title;

    // The abstract of the item
    public $sac_abstract;
    
    // Creators
    public $sac_creators;

    // Identifier
    public $sac_identifier;

    // Date made available
    public $sac_dateavailable;

    // Status
    public $sac_statusstatement;

    // Copyright holder
    public $sac_copyrightholder;
    
    // Custodian
    public $sac_custodian;

    // Bibliographic citation
    public $sac_citation;

    // Language
    public $sac_language;

    // File name
    public $sac_files;

    public $license_files;

    // MIME type
    public $sac_mimetypes;

    // Number of files added
    public $sac_filecount;
	
    //NDLR license	
    public $sac_license;
    public $sac_keywords;

    function __construct($sac_rootin, $sac_dirin, $sac_rootout, $sac_fileout) {
        // Store the values
        $this->sac_root_in = $sac_rootin;
        $this->sac_dir_in = $sac_dirin;
        $this->sac_root_out = $sac_rootout;
        $this->sac_file_out = $sac_fileout;
        $this->sac_creators = array();
	$this->sac_keywords = array();
        $this->sac_files = array();
        $this->sac_mimetypes = array();
        $this->sac_filecount = 0;
    }

    function setType($sac_thetype) {
        $this->sac_type = $sac_thetype;
	//error_log('setting type '.$sac_thetype);
    }

    function setTitle($sac_thetitle) {
        $this->sac_title = $this->clean($sac_thetitle);
    }

    function setAbstract($sac_thetitle) {
        $this->sac_abstract = $this->clean($sac_thetitle);
    }
	
    function addKeyword($sac_keyword){
	array_push($this->sac_keywords, $this->clean($sac_keyword));
    }

    function addCreator($sac_creator) {
        array_push($this->sac_creators, $this->clean($sac_creator));
    }
    
    function setIdentifier($sac_theidentifier) {
        $this->sac_identifier = $sac_theidentifier;
    }
    
    function setStatusStatement($sac_thestatus) {
        $this->sac_statusstatement = $sac_thestatus;
    }

    function setCopyrightHolder($sac_thecopyrightholder) {
        $this->sac_copyrightholder = $sac_thecopyrightholder;
    }
    
    function setCustodian($sac_thecustodian) {
        $this->sac_custodian = $this->clean($sac_thecustodian);
    }

    function setCitation($sac_thecitation) {
        $this->sac_citation = $this->clean($sac_thecitation);
    }

    function setLanguage($sac_thelanguage) {
        $this->sac_language = $this->clean($sac_thelanguage);
    }

    function setDateAvailable($sac_thedta) {
        $this->sac_dateavailable = $sac_thedta;
    }

    function setLicense($sac_license = false){
	$this->sac_license = $sac_license;
    }

    function addFile($sac_thefile, $sac_themimetype) {
        array_push($this->sac_files, $sac_thefile);
        array_push($this->sac_mimetypes, $sac_themimetype);
        $this->sac_filecount++;
    }

    function create() {
        // Write the metadata (mets) file
        $fh = @fopen($this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename, 'w');
        if (!$fh) {
            throw new Exception("Error writing metadata file (" . 
                                $this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename . ")");
        }
        $this->writeHeader($fh);
        $this->writeDmdSec($fh);
	//$this->writeLicenseText($fh);
        $this->writeFileGrp($fh);
        $this->writeStructMap($fh);
        $this->writeFooter($fh);    
        fclose($fh);
        
        // Create the zipped package
        $zip = new ZipArchive();
        $zip->open($this->sac_root_out . '/' . $this->sac_file_out, ZIPARCHIVE::CREATE);
        $zip->addFile($this->sac_root_in . '/' . $this->sac_dir_in . '/mets.xml', 
                     'mets.xml');
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            $zip->addFile($this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_files[$i], 
                          $this->sac_files[$i]);
        }
        if ( file_exists($this->sac_root_in . '/' . $this->sac_dir_in . '/license.txt') ) {
            $zip->addFile($this->sac_root_in . '/' . $this->sac_dir_in . '/license.txt',
                          'license.txt');
        }
        $zip->close();
    }

    function writeheader($fh) {
        fwrite($fh, "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"no\" ?" . ">\n");
        fwrite($fh, "<mets ID=\"sort-mets_mets\" OBJID=\"sword-mets\" LABEL=\"DSpace SWORD Item\" PROFILE=\"DSpace METS SIP Profile 1.0\" xmlns=\"http://www.loc.gov/METS/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/mets.xsd\">\n");
        fwrite($fh, "\t<metsHdr CREATEDATE=\"2008-09-04T00:00:00\">\n");
        fwrite($fh, "\t\t<agent ROLE=\"CUSTODIAN\" TYPE=\"ORGANIZATION\">\n");
        if (isset($this->sac_custodian)) { fwrite($fh, "\t\t\t<name>$this->sac_custodian</name>\n"); }
        else { fwrite($fh, "\t\t\t<name>Unknown</name>\n"); }
        fwrite($fh, "\t\t</agent>\n");
        fwrite($fh, "\t</metsHdr>\n");
    }

    function writeDmdSec($fh) {
        fwrite($fh, "<dmdSec ID=\"sword-mets-dmd-1\" GROUPID=\"sword-mets-dmd-1_group-1\">\n");
        fwrite($fh, "<mdWrap LABEL=\"SWAP Metadata\" MDTYPE=\"OTHER\" OTHERMDTYPE=\"EPDCX\" MIMETYPE=\"text/xml\">\n");
        fwrite($fh, "<xmlData>\n");
        fwrite($fh, "<epdcx:descriptionSet xmlns:epdcx=\"http://purl.org/eprint/epdcx/2006-11-16/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://purl.org/eprint/epdcx/2006-11-16/ http://purl.org/eprint/epdcx/xsd/2006-11-16/epdcx.xsd\">\n");
        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-epdcx-1\">\n");

	
	//NDLR are not using DCMI Types
        //if (isset($this->sac_type)) {
        //    $this->statementValueURI($fh, 
        //                             "http://purl.org/dc/elements/1.1/type", 
       //                      $this->sac_type);    
       // }

        if (isset($this->sac_type)) {
            $this->statement($fh, 
                                    "http://purl.org/dc/elements/1.1/type", 
                             $this->valueString($this->sac_type));    
        }
	if (isset($this->sac_title)) {
            $this->statement($fh, 
                             "http://purl.org/dc/elements/1.1/title", 
                     $this->valueString($this->sac_title));    
        }

        if (isset($this->sac_abstract)) {
            $this->statement($fh, 
                             "http://purl.org/dc/terms/abstract", 
                     $this->valueString($this->sac_abstract));    
        }

        foreach ($this->sac_creators as $sac_creator) {
            $this->statement($fh, 
                             "http://purl.org/dc/elements/1.1/creator", 
                     $this->valueString($sac_creator));    
        }

        foreach ($this->sac_keywords as $sac_keyword) {
            //error_log('got a keyword');
            $this->statement($fh,
                             "http://purl.org/dc/elements/1.1/isced",
                     $this->valueString($sac_keyword));
        }


        if (isset($this->sac_identifier)) {
            $this->statement($fh, 
                             "http://purl.org/dc/elements/1.1/identifier", 
                     $this->valueString($this->sac_identifier));    
        }

	if(isset($this->sac_license)){
            //error_log('mark_log packager setting xml license');
	    $this->statement($fh,
			    "site/access/restriction",
		    $this->valueString($this->sac_license));
	}

        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"http://purl.org/eprint/terms/isExpressedAs\" " .
                "epdcx:valueRef=\"sword-mets-expr-1\" />\n");

        fwrite($fh, "</epdcx:description>\n");
        
        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-expr-1\">\n");
        
        $this->statementValueURI($fh,
                                 "http://purl.org/dc/elements/1.1/type", 
                         "http://purl.org/eprint/entityType/Expression");
        
        if (isset($this->sac_language)) {
	    $this->statementVesURI($fh, 
                               "http://purl.org/dc/elements/1.1/language",
                       "http://purl.org/dc/terms/RFC3066",
                       $this->valueString($this->sac_language));   
	}
        
        $this->statementVesURIValueURI($fh,
                                       "http://purl.org/dc/elements/1.1/type",
                               "http://purl.org/eprint/terms/Type",
                           "http://purl.org/eprint/entityType/Expression"); 
    
        if (isset($this->sac_dateavailable)) {
            $this->statement($fh, 
                             "http://purl.org/dc/terms/available",
                         $this->valueStringSesURI("http://purl.org/dc/terms/W3CDTF",
                                              $this->sac_dateavailable));    
        }

        if (isset($this->sac_statusstatement)) {
            $this->statementVesURIValueURI($fh, 
                                           "http://purl.org/eprint/terms/Status",
                                   "http://purl.org/eprint/terms/Status",
                                   $this->sac_statusstatement);    
        }

        if (isset($this->sac_copyrightholder)) {
            $this->statement($fh, 
                             "http://purl.org/eprint/terms/copyrightHolder", 
                     $this->valueString($this->sac_copyrightholder));
        }

        if (isset($this->sac_citation)) {
            $this->statement($fh, 
                             "http://purl.org/eprint/terms/bibliographicCitation", 
                     $this->valueString($this->sac_citation));
        }

        fwrite($fh, "</epdcx:description>\n");
        
        fwrite($fh, "</epdcx:descriptionSet>\n");
        fwrite($fh, "</xmlData>\n");
        fwrite($fh, "</mdWrap>\n");
        fwrite($fh, "</dmdSec>\n");
    }

    function writeFileGrp($fh) {
        fwrite($fh, "\t<fileSec>\n");
        if (isset($this->sac_license) && !empty($this->sac_license)) $this->writeLicenseText($fh);
        fwrite($fh, "\t\t<fileGrp ID=\"sword-mets-fgrp-1\" USE=\"CONTENT\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite($fh, "\t\t\t<file GROUPID=\"sword-mets-fgid-0\" ID=\"sword-mets-file-" . $i ."\" " .
                        "MIMETYPE=\"" . $this->sac_mimetypes[$i] . "\">\n");
            fwrite($fh, "\t\t\t\t<FLocat LOCTYPE=\"URL\" xlink:href=\"" . $this->clean($this->sac_files[$i]) . "\" />\n");
            fwrite($fh, "\t\t\t</file>\n");
        }
        fwrite($fh, "\t\t</fileGrp>\n");
        fwrite($fh, "\t</fileSec>\n");
    }

    function writeLicenseText($fh) {
        //fwrite($fh, "\t<fileSec>\n");
        fwrite($fh, "\t\t<fileGrp ID=\"sword-mets-fgrp-2\" USE=\"LICENSE\">\n");
        fwrite($fh, "\t\t\t<file GROUPID=\"sword-mets-license-0\" ID=\"sword-mets-license\" MIMETYPE=\"text/plain\">\n");
        fwrite($fh, "\t\t\t\t<FLocat LOCTYPE=\"URL\" xlink:href=\"license.txt\" />\n");
        fwrite($fh, "\t\t\t</file>\n");
        fwrite($fh, "\t\t</fileGrp>\n");
        //fwrite($fh, "\t</fileSec>\n");
    }

    function writeStructMap($fh) {
        fwrite($fh, "\t<structMap ID=\"sword-mets-struct-1\" LABEL=\"structure\" TYPE=\"LOGICAL\">\n");
        fwrite($fh, "\t\t<div ID=\"sword-mets-div-1\" DMDID=\"sword-mets-dmd-1\" TYPE=\"SWORD Object\">\n");
        fwrite($fh, "\t\t\t<div ID=\"sword-mets-div-2\" TYPE=\"File\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite($fh, "\t\t\t\t<fptr FILEID=\"sword-mets-file-" . $i . "\" />\n");
        }
        fwrite($fh, "\t\t\t</div>\n");
        fwrite($fh, "\t\t</div>\n");
        fwrite($fh, "\t</structMap>\n");
    }

    function writeFooter($fh) {
        fwrite($fh, "</mets>\n");
    }

    function valueString($value) {
        return "<epdcx:valueString>" .
               $value . 
               "</epdcx:valueString>\n";
    }

    function valueStringSesURI($sesURI, $value) {
        return "<epdcx:valueString epdcx:sesURI=\"" . $sesURI . "\">" .
               $value . 
               "</epdcx:valueString>\n";
    }

    function statement($fh, $propertyURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\">\n" .
               $value .
               "</epdcx:statement>\n");
    }

    function statementValueURI($fh, $propertyURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:valueURI=\"" . $value . "\" />\n");
    }

    function statementVesURI($fh, $propertyURI, $vesURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:vesURI=\"" . $vesURI . "\">\n" .
               $value . 
               "</epdcx:statement>\n");
    }
    
    function statementVesURIValueURI($fh, $propertyURI, $vesURI, $value) {
        fwrite($fh, "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
               "epdcx:vesURI=\"" . $vesURI . "\" " .
               "epdcx:valueURI=\"" . $value . "\" />\n");
    }

    function clean($data) {
            return str_replace('&#039;', '&apos;', htmlspecialchars($data, ENT_QUOTES));
    }
}
?>
