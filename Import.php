<?php

class Import extends Site {

    /**
     * constructs a new import instance
     */
    function __construct() {
        parent::Site();
        $this->mediaCnt = "";
        $this->layout = new Layout();
        $this->Assess = new Assessment();
        $this->Bank = new Bank();
        $this->auth = new Authoring();
        $this->media = new Media();
        $this->qst = new Question();
        $this->qsttemplate = new QuestionTemplate();

        $this->layout->setParameters();
    }

    /**
     * Used to upload zip file for importing question
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    array $input
     * @return   array (json)
     *
     */
    function importXml(array $input) {

        $guid = uniqid($input['importtype']);
        $file_ext = $this->media->findExt(basename($_FILES['uploadQTI']['name']));
        if(pathinfo($_FILES['uploadQTI']['name'], PATHINFO_EXTENSION)=="zip"){
        //$target_path_dir    = $this->cfg->rootPath.$this->cfgApp->importTempLocation.$this->session->getValue('userID');
        //$target_path_zip    = "{$target_path_dir}/".$guid.".{$file_ext}"; //....because we decided to allow user to upload the same multiple times.
            if ($input['importtype'] == "qti") {
                // $target_path_dir    = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->importData.$this->cfgApp->exportQti_v_1_2;
                $target_path_dir = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->exportQti_v_1_2)); // Changed by Moreshwar for Import
            } else if ($input['importtype'] == "qtipegasus") {
                // $target_path_dir    = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->importData.$this->cfgApp->exportQti_v_1_2;
                $target_path_dir = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->exportQti_v_1_2)); // Changed by Moreshwar for Import
            } else if ($input['importtype'] == "moodle") {
                $target_path_dir = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->importData . $this->cfgApp->exportMoodle_v_1_9_8;
            } else if ($input['importtype'] == "qti_v_2_1") {
                $target_path_dir = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->exportQti_v_2_1));
            } else if ($input['importtype'] == "qti_v_1_2_examView") {
                $target_path_dir = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->exportQti_v_1_2_examView));
            } else if ($input['importtype'] == "testbuilder_qti1_2") {
                $target_path_dir = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->exportTestBuilder_qti1_2));
            }
        
        $target_path_zip = "{$target_path_dir}" . $guid . ".{$file_ext}"; //....because we decided to allow user to upload the same multiple times.

        Site::myDebug('--uploadpath-***************************************************************---');
        Site::myDebug($this->cfgApp->importData . $this->cfgApp->exportQti_v_1_2);
        Site::myDebug($target_path_dir);

        if (!is_dir($target_path_dir)):
            @mkdir($target_path_dir, 0777);
        endif;

        if (file_exists($target_path_zip)) { ///this condition is irrelevant if we are allowing user to upload same file twice..still has been left uncommented just in case we need it in future...
            echo $error = "File [" . basename($_FILES['uploadQTI']['name']) . "] has already been uploaded.";
            $mesg = "";
            echo "{";
            echo "error: '" . $error . "',";
            echo "msg: '" . $mesg . "',";
            echo "file: ''";
            echo "}";
        } else {
            $fileuploaderror = $_FILES['uploadQTI']['error'];
            $sourcefile = $_FILES['uploadQTI']['tmp_name'];
            //$targetpath         = $target_path_zip;
            if (!empty($fileuploaderror)) {
                switch ($fileuploaderror) {
                    case '1':
                        $error = 'The uploaded file exceeds the upload max filesize';
                        break;
                    case '2':
                        $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                        break;
                    case '3':
                        $error = 'The uploaded file was only partially uploaded';
                        break;
                    case '4':
                        $error = 'No file was uploaded.';
                        break;
                    case '6':
                        $error = 'Missing a temporary folder';
                        break;
                    case '7':
                        $error = 'Failed to write file to disk';
                        break;
                    case '8':
                        $error = 'File upload stopped by extension';
                        break;
                    case '0':
                        $error = 'No error found...';
                    case '999':
                    default:
                        $error = 'No error code avaiable';
                }
            } elseif (empty($sourcefile) || $sourcefile == 'none') {
                $error = 'No file was uploaded..';
            } else {
                $status = move_uploaded_file($sourcefile, $target_path_zip);


                if ($this->cfg->S3bucket) {
                    $S3ZipPath = str_replace($this->cfg->rootPath . '/', "", $target_path_zip);
                    $S3ZipPath = str_replace("//", "/", $S3ZipPath);
                    s3uploader::upload($target_path_zip, $S3ZipPath);
                }
            }
            
            if(isset($input['source'])){
                $source = $input['source'];
            }else{
                if($input['type']==2){
                   $source = 'assessment'; 
                }else if($input['type']==1){
                   $source = 'bank';
                }
            }

            if ($status) {
                return "{error: '{$error}',msg:'',file:'" . $target_path_zip . "',id:'" . $input['id'] . "',name:'',noofquest:'',importtype:'" . $input['importtype'] . "',source:'" . $source . "'}";
            } else {
                return "{error: '{$error}',msg:'',file:'',id:'',name:'',noofquest:'',importtype:'',source:''}";
            }
        }
        }else{
            return "{error: 'invalid format',msg:'',file:'',id:'',name:'',noofquest:'',importtype:'',source:''}";
        }
        
    }

    function asycImport(array $input) {
        
        $toEmail = $_SESSION['emailID'];
        $fullName = $_SESSION['fullName'];
        $userID = $this->session->getValue('userID');
        if ($input['importtype'] == "qti") {
            if ($input['source'] == 'assessment') {
                $enType = 2;
            } else {
                $enType = 1;
            }
            if ($input['id'] != '') {                
                $out_data = $this->createQuestionFromImport($input['target_path_zip'], $input['id'], $enType);                
            } else {                               
                $out_data = $this->createAssessmentAndQuestion($input['target_path_zip'], $enType, $input['source']);                
            }
        } elseif ($input['importtype'] == "qtipegasus") {
            $out_data = $this->createPegasusAssessmentAndQuestion($input['target_path_zip']);
        } else if ($input['importtype'] == "moodle") {
            $out_data = $this->moddleImport($input['target_path_zip']);
        } elseif ($input['importtype'] == "qti_v_2_1") {
            $this->qti2v1_imports = new Qti2v1Imports();
            $out_data = $this->qti2v1_imports->createQti_2_1_AssessmentAndQuestion($input['target_path_zip']);
            if ($out_data[0] == 'error_404_manifest') {
                $out_data[0] = 'Manifest file not found';
            }
        } elseif ($input['importtype'] == "qti_v_1_2_examView") { // QTI1.2_examView
            $out_data = $this->createAsstAndQuesWithQti2_1ExamView($input['target_path_zip'], $uploaded_file_name);
        } elseif ($input['importtype'] == "testbuilder_qti1_2") {
            // TestBuilder_qti1_2                    
            $out_data = $this->createAsstAndQuesWithTestBuilder_qti1_2($input['target_path_zip'], $uploaded_file_name);
        }
        $mesg = '';
        
        $emailSubject = 'QuAD import notification.';
        $emailLogo = $this->registry->site->cfg->wwwroot.'/assets/imagesnxt/email-logo.png';
        $form='';
        $toEmail=$toEmail;
        $data = array();
        $data['email_logo'] = $emailLogo;
        $data['name'] = $fullName;
        $data['download_url'] = $this->registry->site->cfg->wwwroot ."/" . $input['source'] . "/question-list/" . $out_data[0];
        $verInfo = $this->getVerboseEmailTemplate('Import Notification',$userID);
        $templateInfo	= array_merge($data,$verInfo);
        $this->sendTemplateMail($emailSubject,$templateInfo,$toEmail,'importnotification.php',$form);
        
        return "{error: '{$error}',msg:'',file:'',id:'{$out_data[0]}',name:'{$out_data[1]}',noofquest:'{$out_data[2]}',noofimportquest:'{$out_data[3]}',returnCheckVal:'{$out_data[4]}'}";
    }

    /**
     * import question in moodle format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    array $qtifile_src   source zip file path
     * @return   array 
     *
     */
    function moddleImport($qtifile_src) {
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);
            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);

            $this->unzip($qtifile_src, $tar_path);
            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    unlink($qtifile_src);
                }
            }
            if ($handle1 = opendir($tar_path)) {
                while (false !== ($file = readdir($handle1))) {
                    if ((!is_dir($tar_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                        if (stripos($file, "manifest") > -1) {
                            $manifestfile = $file;
                        } else {
                            $moodlefile = $tar_path . "/" . $file;
                        }
                    }
                }
            }
        }
        if (file_exists($moodlefile)) {
            $xmldata = simplexml_load_file($moodlefile);
            $curdate = date("U");

            //Creating New Assessment....with Title only and all Default Setting....
            $assesment_name = $input["AssessmentName"] = ($entityName) ? $entityName : "Assessment " . $this->currentDate();
            $quizID = $this->Assess->save($input);
            //$this->auth->copyCss($quizID,2);
            $rootItems = $xmldata->xpath("question");

            if (!empty($rootItems)) {
                $this->moodleAddQuestion($rootItems, $quizID, 0);
                $i = count($rootItems);
            }
            $response_data[] = $quizID;
            $response_data[] = $assesment_name;
            $response_data[] = $i;
            return $response_data;
        }
    }

    /**
     * add question in assessment which is in moodle format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $APPCONFIG
     * @param    array $questionItems
     * @param    integer $quizID
     * @param    integer $sectionId
     * @return   void
     *
     */
    function moodleAddQuestion(array $questionItems, $quizID, $sectionId) {
        global $APPCONFIG;
        $qst = new Question();
        if (!empty($questionItems)) {
            foreach ($questionItems as $q) {
                $qt = '';
                $strxml = $q->asXML();
                $questTitle = $q->name->text;
                $questText = $q->questiontext->text;
                $questImage = $q->image;
                $multipleType = $q->single;
                $questType = $this->getAttribute($q, "type");
                $instID = $this->session->getValue('instID');

                /* code for fetching tenplate type */
                if ($DBCONFIG->dbType == 'Oracle') {
                    $cond = " qt.\"ID\" = mqt.\"QuestionTemplateID\" and qt.\"isEnabled\" = '1' ";
                    $query = " select qt.\"TemplateFile\" ,mql.\"QuestTemplateId\" from QuestionTemplates qt
                                                INNER JOIN MapClientQuestionTemplates mqt ON mqt.\"isEnabled\" = '1' AND mqt.\"isActive\" = 'Y' and mqt.\"ClientID\" = {$instID}
                                                inner join MapQuadLms  mql ON mql.\"QuestionTemplateID\" = mqt.\"ID\"  AND mql.\"MoodleQuestType\" = '{$questType}'
                                                        and mql.\"isMoodleImport\" ='Y' and mql.\"isEnabled\" = '1'
                                                where {$cond} ";
                } else {
                    $cond = " qt.ID = mqt.QuestionTemplateID and qt.isEnabled = '1'";
                    $query = " select qt.TemplateFile ,mql.QuestTemplateId from QuestionTemplates qt
                                            INNER JOIN MapClientQuestionTemplates mqt ON mqt.isEnabled = '1' AND mqt.isActive = 'Y' and mqt.ClientID = {$instID}
                                            inner join MapQuadLms  mql ON mql.QuestionTemplateID = mqt.ID  AND mql.MoodleQuestType = '{$questType}' and mql.isMoodleImport='Y'
                                                        and mql.isEnabled = '1'
                                            where {$cond} ";
                }

                $questionTemplateData = $this->db->getRows($query);

                if (count($questionTemplateData) > 1) {
                    $qtdetail = $questionTemplateData;
                    if ($this->getAttribute($q, "type") == "multichoice") {
                        $objJSONtmp = new Services_JSON();
                        if (!empty($qtdetail)) {
                            foreach ($qtdetail as $qtdetail1) {
                                $elemJsonroot = $objJSONtmp->decode(stripslashes($qtdetail1['MoodleIdentifier']));
                                if (!empty($elemJsonroot)) {
                                    foreach ($elemJsonroot as $elemJsonnode) {
                                        if ($multipleType == $elemJsonnode->{'single'}) {
                                            $qt = $qtdetail1['QuestTemplateId'];
                                            $qtFile = $qtdetail1['TemplateFile'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $qtdetail = $questionTemplateData[0];
                    $qt = $qtdetail['QuestTemplateId'];
                    $qtFile = $qtdetail['TemplateFile'];
                }
                /* end of code of template type nad name fetching */

                if ($qt != '') {
                    include_once($this->cfg->rootPath . "/" . $this->cfgApp->importMoodleFileLocation . "/" . $qtFile . ".php");
                    $class_name = $qtFile . "Import";
                    $template_file_obj = new $class_name();
                    $questJSON = $template_file_obj->moodleQuestionJson($q);
                    $sUserID = $this->session->getValue('userID');
                    if (!empty($questJSON)) {
                        $arrQuestion = array(
                            'Title' => $questTitle,
                            'XMLData' => 'NA',
                            'JSONData' => $questJSON, //$this->ChangeImages($questJSON),
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt
                        );
                        $questid = $this->qst->newQuestSave($arrQuestion);
                        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                            $questid,
                            $quizID,
                            2,
                            $sectionId, 'ADDQST', $sUserID,
                            $this->currentDate(), $sUserID,
                            $this->currentDate()), 'details');
                        $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
                        $this->qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                    }
                }
            }
        }
    }

    function createPegasusAssessmentAndQuestion($qtifile_src) {
        global $quest_json;
        global $actual_quest_import;
        $actual_quest_import = 0;
        //$qtifile is xml file where question is written
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);
            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);
            $this->unzip($qtifile_src, $tar_path);
            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }
            if ($handle1 = opendir($tar_path)) {
                while (false !== ($file = readdir($handle1))) {

                    if ((!is_dir($tar_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                        if (stripos($file, "manifest") > -1) {
                            $manifestfile = $file;
                        }
                    }
                }
            }
            $file_path = $tar_path . "/";
        }
        $manifestfilePath = $file_path . $manifestfile;

        if (file_exists($manifestfilePath)) {

            $filedata = file_get_contents($manifestfilePath);

            $xmldata = simplexml_load_string($filedata);

            $objxmlnode = (array) $this->registry->site->objectToArray($xmldata);

            $resource = $objxmlnode['resources'];

            $quizfile = $file_path . $resource['resource'][0]['file']['0']['@attributes']['href'];


            $qtifile = $file_path . str_replace(':', '_', $resource['resource'][0]['file']['1']['@attributes']['href']);

            $propertiesfile = $file_path . $resource['resource'][1]['file']['@attributes']['href'];


            if (!file_exists($qtifile)) {
                foreach ($this->registry->site->ListFiles($tar_path) as $key => $file) {

                    $fileName = basename($file);

                    if (strpos(basename($qtifile), $fileName) > -1) {

                        rename($file, $qtifile);
                    }
                }
            }


            if ($qtifile != '' && file_exists($qtifile)) {


                $filedata_qti = file_get_contents($qtifile);
                $xmldata_qti = simplexml_load_string($filedata_qti, 'SimpleXMLElement', LIBXML_NOCDATA);


                if ($this->getAttribute($xmldata_qti->section, "ident")) {

                    $entityName = $this->getAttribute($xmldata_qti->section, "title");
                }
                $assesment_name = $input["AssessmentName"] = ($entityName) ? $entityName : "Assessment " . $this->currentDate();
                /* ===================================================== */
                $aassessment = new Assessment();

                $assessmentcheck = $aassessment->assessmentExist($assesment_name);
                if (isset($assessmentcheck['RS'][0]['ID'])) {
                    $quizID = $assessmentcheck['RS'][0]['ID'];
                } else {
                    $quizID = $this->Assess->save($input);
                }

                //  $quizID=27;
                // $this->auth->copyCss($quizID,2);

                $sectionItems = $xmldata_qti->section;
                $rootItems = $xmldata_qti->section->item;

                $i = 0;
                if (count($sectionItems)) {
                    Site::myDebug('--$sectionItems');
                    Site::myDebug(count($sectionItems));

                    foreach ($sectionItems as $secItem) {

                        if (!empty($secItem) && count($secItem) > 0) {
                            $sectionTitle = $this->getAttribute($secItem, "title");
                            $sectionTitle = ($sectionTitle == "") ? "sec" : $sectionTitle;
                            //add section
                            /* Need to add section title */
                            //$sectionId     = $this->Assess->section($quizID,0,$sectionTitle,"ADDSEC");
                            /* Need to add section title */
                            //and then add its question in this section
                            $questionCount = count($secItem->item);

                            // $questionItems  = $secItem->item;
                            $questionItems = $secItem;

                            $questCntr = count($questionItems);
                            Site::myDebug('--$questionItems');
                            Site::myDebug(count($questionItems));

                            // if(!empty($sectionItems))
                            // {
                            $this->qtiPegasusAddQuestion($secItem, $quizID, $sectionId);
                            $i = $i + count($questionItems);
                            // }
                            //end of add quest     
                        }
                    }
                }
            }
        }



        /*
          $query = "SELECT Count FROM Assessments WHERE ID = {$quizID} and isEnabled = '1'  LIMIT 1";
          $result     = $this->db->getSingleRow($query);
         */

        $data = array('EntityType' => 2, 'ImportType' => 'QTI 1.2', 'TotalQuest' => $questionCount,
            'TotalQuestImported' => $actual_quest_import, 'Summary' => '', 'EntityID' => $quizID,
            'FileName' => basename($qtifile_src), 'AddDate' => $this->currentDate(), 'UserID' => $this->session->getValue('userID'));
        $this->addImportDetails($data);
        $response_data[] = $quizID;
        $response_data[] = $assesment_name;
        $response_data[] = $i;
        $response_data[] = $actual_quest_import;

        return $response_data;
    }

    /**
     * import question in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $quest_json
     * @param    array $qtifile_src  
     * @return   array
     *
     */
    function createAssessmentAndQuestion($qtifile_src, $eType, $source) {
        global $quest_json, $asset_json;
        global $actual_quest_import;
        $actual_quest_import = 0;
        //$qtifile is xml file where question is written
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);
            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);
            $this->unzip($qtifile_src, $tar_path);
            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }
            if ($handle1 = opendir($tar_path)) {
                while (false !== ($file = readdir($handle1))) {
                    if ((!is_dir($tar_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                        if (stripos($file, "manifest") > -1) {
                            $manifestfile = $file;
                        } else if (stripos($file, ".xml") > -1) {
                            $qtifile = $tar_path . "/" . $file;
                        }
                    }
                }
            }
            $file_path = $tar_path . "/";
            if (file_exists($file_path)) {

                if ($handle = opendir($file_path)) {
                    $empty_folder = 0;
                    while (false !== ($file = readdir($handle))) {
                        if ((!is_dir($file_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                            $empty_folder++;
                        }
                    }
                    if ($empty_folder) {
                        $response = '[';
                        $response .= $this->media->traverseDir($file_path, true);
                        $response .= ']';
                        $input["data"] = $response;

                        $quest_json = $this->media->upload1($input);
                    }
                }
            }
        }
        
        if (file_exists($qtifile)) {
            $filedata = file_get_contents($qtifile);

//            $this->myDebug("old XML");
//            $this->myDebug($filedata);
            // till here
            $filedata = $this->changeImages($filedata);           //Replace All images with new images.
//            $this->myDebug("New XML");
//            $this->myDebug($filedata);
            //$xmldata    = simplexml_load_string($filedata);
            $xmldata = simplexml_load_string($filedata, 'SimpleXMLElement', LIBXML_NOCDATA);

            //$quiz_arr   = $this->auth->parseQtiQuiz($xmldata);

            $curdate = date("U");
            if ($this->getAttribute($xmldata->assessment, "ident")) {
                $entityName = $this->getAttribute($xmldata->assessment, "title");
            }


            //Creating New Assessment....with Title only and all Default Setting....
            if ($source == 'assessment') {
                $assesment_name = $input["AssessmentName"] = ($entityName) ? $entityName : "Assessment " . $this->currentDate();
            } else {
                $assesment_name = $input["BankName"] = ($entityName) ? $entityName : "Bank " . $this->currentDate();
            }
            //name.replace(/[^a-zA-Z0-9]/gi, '').substring(0, length).toUpperCase();
            
            $assesmentShortName = preg_replace('/\s+/', '', $assesment_name); // removing all white spaces
            if (strlen($assesmentShortName) > 5) {
                $assesmentShortName = substr($assesmentShortName, 0, 5);
            }
            $assesmentShortName = strtoupper($assesmentShortName);
            $input['name'] = $assesmentShortName;
            $input['assessmentID'] = 0;
            $assesmentShortName = $this->Assess->getShortNameSuggestion($input);
//            $uniqueFound = false;
//            while (!$uniqueFound) {
//                $rowCount = $this->Assess->asmtShortNameCheck($assesmentShortName, 0);
//                if ($rowCount == 0) {
//                    $uniqueFound = true;
//                } else {
//                    $assesmentShortName = $this->Assess->randomString();
//                }
//            }
//            $assesmentShortName = substr($assesmentShortName, 0, 5);
            if ($source == 'assessment') {
                $input['AssessmentShortName'] = strtoupper($assesmentShortName);
                $quizID = $this->Assess->save($input);
            } else {
                $input['BankShortName'] = strtoupper($assesmentShortName);
                $quizID = $this->Bank->save($input);
            }

            
            
            Site::myDebug('-----$quizID');
            Site::myDebug($quizID);
            // $this->auth->copyCss($quizID,2);

            $sectionItems = $xmldata->xpath("assessment/section");
            $rootItems = $xmldata->xpath("assessment/item");
            $i = 0;


            if (count($sectionItems)) {
                Site::myDebug('--$sectionItems');
                Site::myDebug(count($sectionItems));


                foreach ($sectionItems as $sectionItem) {
                    $sectionTitle = $this->getAttribute($sectionItem, "title");
                    $sectionTitle = ($sectionTitle == "") ? "sec" : $sectionTitle;

                    //add section
                    // $sectionId     = $this->Assess->section($quizID,0,$sectionTitle,"ADDSEC");
                    //and then add its question in this section
                    $questionCount = count($sectionItem->xpath("item"));
                    $questionItems = $sectionItem->xpath("item");
                    $questCntr = count($questionItems);

                    Site::myDebug('--$questionItems');
                    Site::myDebug(count($questionItems));
                    if (!empty($questionItems)) {
                        $this->qtiAddQuestion($questionItems, $quizID, $sectionId, $eType);
                        $i = $i + count($questionItems);
                    }
                    //end of add quest     
                }
            }
            if (!empty($rootItems)) {
                Site::myDebug('-----$rootItems');
                Site::myDebug($rootItems);
                $this->qtiAddQuestion($rootItems, $quizID, 0);
                $i = $i + count($rootItems);
                $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
            }
        }
        /*
          $query = "SELECT Count FROM Assessments WHERE ID = {$quizID} and isEnabled = '1'  LIMIT 1";
          $result     = $this->db->getSingleRow($query);
         */

        $data = array('EntityType' => $eType, 'ImportType' => 'QTI 1.2', 'TotalQuest' => $questionCount,
            'TotalQuestImported' => $actual_quest_import, 'Summary' => '', 'EntityID' => $quizID,
            'FileName' => basename($qtifile_src), 'AddDate' => $this->currentDate(), 'UserID' => $this->session->getValue('userID'));

        /*
          echo "---<pre>";
          print_r($data);
          exit;
         */
        $this->addImportDetails($data);
        $response_data[] = $quizID;
        $response_data[] = $assesment_name;
        $response_data[] = $i;
        $response_data[] = $actual_quest_import;
        $response_data[] = '1';
        return $response_data;
    }

    /*
     * @manish<manish.kumar@learningmate.com>
     * 22-Sep-15
     * createQuestionFromImport
     * Import question in particular Assessment or Bank
     * return added question info.
     */

    function createQuestionFromImport($qtifile_src, $quizID, $eType) {
        global $quest_json, $asset_json;
        global $actual_quest_import;
        $actual_quest_import = 0;
        //$qtifile is xml file where question is written
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);
            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);
            $this->unzip($qtifile_src, $tar_path);
            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }
            if ($handle1 = opendir($tar_path)) {
                while (false !== ($file = readdir($handle1))) {
                    if ((!is_dir($tar_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                        if (stripos($file, "manifest") > -1) {
                            $manifestfile = $file;
                        } else if (stripos($file, ".xml") > -1) {
                            $qtifile = $tar_path . "/" . $file;
                        }
                    }
                }
            }
            $file_path = $tar_path . "/";
            if (file_exists($file_path)) {
                if ($handle = opendir($file_path)) {
                    $empty_folder = 0;
                    while (false !== ($file = readdir($handle))) {
                        if ((!is_dir($file_path . '/' . $file)) && ($file != ".") && ($file != "..")) {
                            $empty_folder++;
                        }
                    }
                    if ($empty_folder) {
                        $response = '[';
                        $response .= $this->media->traverseDir($file_path, true);
                        $response .= ']';
                        $input["data"] = $response;

                        $quest_json = $this->media->upload1($input);
                    }
                }
            }
        }

        if (file_exists($qtifile)) {
            $filedata = file_get_contents($qtifile);
            $filedata = $this->changeImages($filedata);
            $xmldata = simplexml_load_string($filedata, 'SimpleXMLElement', LIBXML_NOCDATA);
            $curdate = date("U");

            $sectionItems = $xmldata->xpath("assessment/section");
            $rootItems = $xmldata->xpath("assessment/item");
            $i = 0;


            if (count($sectionItems)) {
                foreach ($sectionItems as $sectionItem) {
                    $sectionTitle = $this->getAttribute($sectionItem, "title");
                    $sectionTitle = ($sectionTitle == "") ? "sec" : $sectionTitle;
                    //add section
                    // $sectionId     = $this->Assess->section($quizID,0,$sectionTitle,"ADDSEC");
                    //and then add its question in this section
                    $questionCount = count($sectionItem->xpath("item"));
                    $questionItems = $sectionItem->xpath("item");
                    $questCntr = count($questionItems);

                    Site::myDebug('--$questionItems');
                    Site::myDebug(count($questionItems));
                    if (!empty($questionItems)) {
                        $this->qtiAddQuestion($questionItems, $quizID, $sectionId, $eType);
                        $i = $i + count($questionItems);
                    }
                    //end of add quest     
                }
            }
            if (!empty($rootItems)) {
                $this->qtiAddQuestion($rootItems, $quizID, 0);
                $i = $i + count($rootItems);
                $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
            }
        }

        $data = array('EntityType' => $eType, 'ImportType' => 'QTI 1.2', 'TotalQuest' => $questionCount,
            'TotalQuestImported' => $actual_quest_import, 'Summary' => '', 'EntityID' => $quizID,
            'FileName' => basename($qtifile_src), 'AddDate' => $this->currentDate(), 'UserID' => $this->session->getValue('userID'));

        $this->addImportDetails($data);
        $response_data[] = $quizID;
        $response_data[] = $assesment_name;
        $response_data[] = $i;
        $response_data[] = $actual_quest_import;
        $response_data[] = '1';
        return $response_data;
    }

    /*
     * 	17-Jan-2014	
     * 	Search file in particluar folder path 
     * 	by Manjiri 
     * 	construct :: RecursiveDirectoryIterator::__construct � Constructs a RecursiveDirectoryIterator
     * 	Ref link :: http://www.php.net/manual/en/recursivedirectoryiterator.construct.php
     * 	
     */

    public function searchFileRecursivelyInFolder($tar_path, $searchFileName) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tar_path));
        while ($it->valid()) {
            if (!$it->isDot()) {
                //Sub path with file name :: $it->getSubPathName()
                //Only sub path :: $it->getSubPath()
                //Full path with file name :: $it->key()
                if (preg_match("/" . $searchFileName . "/", $it->getSubPathName())) {
                    $returnFileWithPath = $it->key();
                    return $returnFileWithPath;
                }
            }
            $it->next();
        }
    }

    /**
     * import question in QTI2.1 examView format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $quest_json
     * @param    array $qtifile_src  
     * @return   array
     *
     */
    function createAsstAndQuesWithQti2_1ExamView($qtifile_src, $uploaded_file_name) {
        global $quest_json;
        global $actual_quest_import;
        $actual_quest_import = 0;
        //$qtifile is xml file where question is written
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);

            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);
            $this->unzip($qtifile_src, $tar_path);

            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }


            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }
            $manifestfilePath = $tar_path . 'imsmanifest.xml';
        }
        if (!file_exists($manifestfilePath)) {
            $manifestfilePath = $this->searchFileRecursivelyInFolder($tar_path, 'imsmanifest.xml');
        }

        if (file_exists($manifestfilePath)) {

            $manifestFileObj = simplexml_load_file($manifestfilePath);

            /* Get Media from Manifest START */
            $MediaDetailArray = array();
            $getMediaCount = count($manifestFileObj->resources->resource[0]);

            for ($j = 0; $j < $getMediaCount; $j++) {
                $MediaDetailArray[$j]/* ['image'] */ = $this->getAttribute($manifestFileObj->resources->resource[0]->file[$j], "href");
            }
            $mediaArrWithxml = array();
            $mediaArr = array();
            foreach ($MediaDetailArray as $k => $v) {
                if (strpos($v, '.xml')) {
                    array_push($mediaArrWithxml, $v);
                } else {
                    array_push($mediaArr, $v);
                }
            }

            $finalMediaDetails = $this->createImageAndThumbnail($tar_path, $mediaArr);

            $getImgDetails = array();
            $getImgDir = array();
            foreach ($mediaArr as $g => $val) {
                $getImgDetails = pathinfo($val);
                $getImgDir = $tar_path . "/" . $getImgDetails['dirname'];
            }
            Site::myDebug('-------$getImgDir');
            Site::myDebug($getImgDir);

            /* Get Media from Manifest END */

            $itemDetailsArray = array();
            $itemPropertyArray = array();
            $resourceTypeForWebctquiz = $this->getAttribute($manifestFileObj->resources->resource[0], "type");
            if ($resourceTypeForWebctquiz == 'webctquiz') {
                $resourceTypeForWebctquizHref = $this->getAttribute($manifestFileObj->resources->resource[0], "href");
                $resourceTypeForWebctquizHrefPath = $tar_path . "/" . $resourceTypeForWebctquizHref;
                $quizFileDetails = pathinfo($resourceTypeForWebctquizHref);



                Site::myDebug('-----filepath');
                Site::myDebug($resourceTypeForWebctItemHrefPath);
                if (!file_exists($resourceTypeForWebctquizHrefPath)) {
                    $resourceTypeForWebctquizHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $quizFileDetails['basename']);
                }

                $quizQIZObj = simplexml_load_file($resourceTypeForWebctquizHrefPath);

                $assSectionCount = count($quizQIZObj->assessment->section);
                Site::myDebug('------$quizQIZObj');
                Site::myDebug($assSectionCount);

                for ($i = 0; $i < $assSectionCount; $i++) {

                    $itemDetailsArray[$i]['section'] = $this->getAttribute($quizQIZObj->assessment->section[$i], "ident"); // Section node
                    $itemDetailsArray[$i]['item'] = $this->getAttribute($quizQIZObj->assessment->section[$i]->itemref, "linkrefid"); // itemref node
                }
            }

            Site::myDebug('------$itemDetailsArray');
            Site::myDebug($itemDetailsArray);
            //exit;

            $resourceTypeForWebctprop = $this->getAttribute($manifestFileObj->resources->resource[1], "type");

            $resourceTypeForWebctprop1 = $this->getAttribute($manifestFileObj->resources->resource[1]->file, "href");

            if ($resourceTypeForWebctprop == 'webctproperties') {
                $resourceTypeForWebctpropHref = $this->getAttribute($manifestFileObj->resources->resource[1]->file, "href");

                //$resourceTypeForWebctpropHref = $this->getAttribute($manifestFileObj->resources->resource[1],"href");

                $resourceTypeForWebctpropHrefPath = $tar_path . "/" . $resourceTypeForWebctpropHref;
                $propFileDetails = pathinfo($resourceTypeForWebctpropHrefPath);

                if (!file_exists($resourceTypeForWebctpropHrefPath)) {
                    $resourceTypeForWebctpropHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $propFileDetails['basename']);
                }

                $propObj = simplexml_load_file($resourceTypeForWebctpropHrefPath);
                $proScoreObj = $propObj->processing->scores->score;



                for ($p = 0; $p < count($proScoreObj); $p++) {
                    $attribute = $this->getAttribute($proScoreObj[$p], "linkrefid");
                    $scoreVal = (string) $proScoreObj[$p];


                    $itemPropertyArray[$p]['linkrefid'] = $this->getAttribute($proScoreObj[$p], "linkrefid");
                    $itemPropertyArray[$p]['scoreVal'] = (string) $proScoreObj[$p];

                    $itemDetailsTempArr = $this->questSearch($itemDetailsArray, 'section', $attribute);
                    $itemDetailsArray[$itemDetailsTempArr]['score'] = $scoreVal;
                } //die;
                Site::myDebug('------Final$itemDetailsArray');
                Site::myDebug($itemDetailsArray);



                //create Assessment
                // for question file 
                $resourceTypeForWebctItemHref = $this->getAttribute($manifestFileObj->resources->resource[0]->file[1], "href");
                $resourceTypeForWebctItemHrefPath = $tar_path . "/" . $resourceTypeForWebctItemHref;
                $itemFileDetails = pathinfo($resourceTypeForWebctItemHrefPath);


                if (!file_exists($resourceTypeForWebctItemHrefPath)) {
                    $resourceTypeForWebctItemHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $itemFileDetails['basename']);
                }

                $xmlFileObj = $quizItemObj = simplexml_load_file($resourceTypeForWebctItemHrefPath);


                //========================== VALIDATIONS - start =========================================================
                $errorMsgArr = array();
                //echo "<pre>";       
                $continueFlag = true;
                // check if score for every item is exist or not start
                for ($k = 0; $k <= count($itemDetailsArray); $k++) {
                    if ($itemDetailsArray[$k]['item'] != "") {

                        if ($itemDetailsArray[$k]['score'] == "") {
                            $errorMsgArr['noScore'] = 'XML has no score for item ident: ' . $itemDetailsArray[$k]['item'];
                            $response_data[0] = $errorMsgArr['noScore'];
                            $continueFlag = false;
                        }
                    }
                }
                // check if score for every item is exist or not end
                if (isset($xmlFileObj->section->item)) {

                    foreach ($xmlFileObj->section->item as $xmlItem) {
                        $itemType = $xmlItem->itemmetadata->qmd_itemtype;
                        $multipleChoiceType = $this->getAttribute($xmlItem->presentation->flow->response_lid, 'rcardinality');
                        //check if question title is empty - start		

                        if (trim($this->getAttribute($xmlItem, 'title')) == '') {
                            $errorMsgArr['noTitle'] = 'XML has no Question Title for Item ident: ' . $this->getAttribute($xmlItem, 'ident');
                            $response_data[0] = $errorMsgArr['noTitle'];
                            $continueFlag = false;
                            //break;
                        }
                        //check if question title is empty - end
                        // check if question has to be MCSS and Essay type only start
                        if ($multipleChoiceType != 'Single' && $itemType == 'Logical Identifier') {
                            $errorMsgArr['typeDifferent'] = 'XML contains different Question type than MCSS and Essay. ';
                            $response_data[0] = $errorMsgArr['typeDifferent'];
                            $continueFlag = false;
                        }
                        // check if question has to be MCSS and Essay type only End
                        //For MCSS questions, check if the choices has any correct response - start	    
                        if ($itemType == 'Logical Identifier' && $multipleChoiceType == 'Single') {
                            $choicesAnsArr = array();
                            $choices = $xmlItem->resprocessing->respcondition;
                            foreach ($choices as $ch) {
                                array_push($choicesAnsArr, $ch->setvar);
                            }
                            if (!in_array('100', $choicesAnsArr)) {
                                $errorMsgArr['noCorrResp'] = 'XML has no Correct Response for Item: ' . $this->getAttribute($xmlItem, 'title');
                                $response_data[0] = $errorMsgArr['noCorrResp'];
                                $continueFlag = false;
                                //break;
                            }
                        }
                        //For MCSS questions, check if the choices has any correct response - end		
                    }
                } else {

                    //Xml has no item node - start
                    $errorMsgArr['noItem'] = 'XML has no Items';
                    $response_data[0] = $errorMsgArr['noItem'];
                    $continueFlag = false;
                    //break;
                    //Xml has no item node - end
                }

                //========================== VALIDATIONS - end =========================================================

                if ($continueFlag == true) {

                    /*                     * ************** Get Title from Manifest START *************************** */
                    //$assesment_name = (string)$manifestFileObj->organizations->organization->item->title;
                    $assesment_name = $this->specialCharCheck($uploaded_file_name);
                    Site::myDebug("------------ Assesment Name ---------------");
                    Site::myDebug($assesment_name);
                    /*                     * ************** Get Title from Manifest Ends *************************** */
                    //$assesment_name = $input["AssessmentName"] = "Assessment " . $this->currentDate();
                    $aassessment = new Assessment();
                    $assessmentcheck = $aassessment->assessmentExist($assesment_name);
                    /*                     * ** CODE commented as on 26th march 2014  as for the new requirement ** */
                    /* if (isset($assessmentcheck['RS'][0]['ID'])) {					
                      $quizID = $assessmentcheck['RS'][0]['ID'];
                      } else {
                      $quizID = $this->Assess->save($input);
                      } */
                    /*                     * ** CODE commented as on 26th march 2014  as for the new requirement ** */

                    /*                     * ********** New req for assessment name ****************** */
                    if (isset($assessmentcheck['RS'][0]['ID'])) {
                        $uniqtimestr = strtotime("now");
                        //$assesment_name .= '_'.$uniqtimestr;							
                        $assesment_name .= '_' . $this->currentDate();
                        $input["AssessmentName"] = $assesment_name;
                        $quizID = $this->Assess->save($input);
                    } else {
                        $input["AssessmentName"] = $assesment_name;
                        $quizID = $this->Assess->save($input);
                    }
                    /*                     * ********** New req for assessment name ****************** */

                    $qtiEntityTypeID = 2;      // Imported from Assesment module.
                    //$this->auth->copyCss($quizID, $qtiEntityTypeID);

                    $sectionItems = $quizItemObj->section;
                    $rootItems = $quizItemObj->section->item;

                    $i = 0;
                    if (count($sectionItems)) {

                        foreach ($sectionItems as $secItem) {

                            if (!empty($secItem) && count($secItem) > 0) {
                                $sectionTitle = $this->getAttribute($secItem, "title");
                                $sectionTitle = ($sectionTitle == "") ? "sec" : $sectionTitle;
                                //add section
                                //$sectionId     = $this->Assess->section($quizID,0,$sectionTitle,"ADDSEC");
                                /* Need to add section title */
                                //and then add its question in this section
                                $questionCount = count($secItem->item);

                                // $questionItems  = $secItem->item;
                                $questionItems = $secItem;

                                $questCntr = count($questionItems);
                                Site::myDebug('--$questionItems');
                                Site::myDebug(count($questionItems));

                                //In QTI Import for Pegasus, section node was getting added in questions node count so extra number was getting added in Questions imported into the system
                                $questionItems = count($questionItems) - count($sectionItems);
                                // if(!empty($sectionItems))
                                // {
                                $this->qti1_2AddQuestionWithExamView($secItem, $quizID, $sectionId, $getImgDir, $itemDetailsArray, $finalMediaDetails);
                                $i = $i + $questionItems;
                                // }
                                //end of add quest     
                            }
                        }
                    }
                }
            }
        } else {
            $continueFlag = false;
            $response_data[0] = 'Manifest file not found';
        }


        $data = array('ImportTitle' => $assesment_name, 'EntityType' => 2, 'ImportType' => 'QTI1.2 Exam View', 'TotalQuest' => $questionCount,
            'TotalQuestImported' => $actual_quest_import, 'Summary' => '', 'EntityID' => $quizID,
            'FileName' => basename($qtifile_src), 'AddDate' => $this->currentDate(), 'UserID' => $this->session->getValue('userID'));
        $this->addImportDetails($data);
        $response_data[] = $quizID;
        $response_data[] = $assesment_name;
        $response_data[] = $i;
        $response_data[] = $actual_quest_import;
        $response_data[] = $continueFlag;
        return $response_data;
        //exit;     
    }

    /**
     * import question in QTI2.1 TestBuilder format
     * PAI02 :: sprint 1 ::  QUADPS-3
     *
     * @access   private
     * @abstract
     * @static
     * @global   $quest_json
     * @param    array $qtifile_src  
     * @return   array
     *
     */
    function createAsstAndQuesWithTestBuilder_qti1_2($qtifile_src, $uploaded_file_name) {
        global $quest_json;
        global $actual_quest_import;
        $actual_quest_import = 0;
        $assesment_name = '';
        //$qtifile is xml file where question is written
        if ($this->media->findExt($qtifile_src) == 'zip') {
            $len = count($qtifile_src);

            $len = $len - 5;
            $tar_path = substr($qtifile_src, 0, $len);
            $this->unzip($qtifile_src, $tar_path);

            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }


            if (file_exists($qtifile_src)) {
                if (file_exists($tar_path)) {
                    //unlink($qtifile_src); So tht we can get the QTI file while showing Import history
                }
            }
            $manifestfilePath = $tar_path . 'imsmanifest.xml';
        }
        if (!file_exists($manifestfilePath)) {
            $manifestfilePath = $this->searchFileRecursivelyInFolder($tar_path, 'imsmanifest.xml');
        }

        if (file_exists($manifestfilePath)) {

            $manifestFileObj = simplexml_load_file($manifestfilePath);

            Site::myDebug('------ The $manifestFileObj inside function createAsstAndQuesWithTestBuilder_qti1_2 -----');
            //Site::myDebug($manifestFileObj);
            //Site::myDebug($manifestFileObj->organizations->organization->item->title);
            //Site::myDebug((string)$manifestFileObj->organizations->organization->item->title);


            /* Get Media from Manifest START */
            $MediaDetailArray = array();
            $getMediaCount = count($manifestFileObj->resources->resource[0]);
            // This is the count of number of files inside the zip, in all folders and subfolders

            for ($j = 0; $j < $getMediaCount; $j++) {
                $MediaDetailArray[$j]/* ['image'] */ = $this->getAttribute($manifestFileObj->resources->resource[0]->file[$j], "href");
            }
            //Site::myDebug('------- The $MediaDetailArray before createImageAndThumbnail function call --------');
            //Site::myDebug($MediaDetailArray);

            $mediaArrWithxml = array();
            $mediaArr = array();
            foreach ($MediaDetailArray as $k => $v) {
                if (strpos($v, '.xml')) {
                    array_push($mediaArrWithxml, $v);
                } else {
                    array_push($mediaArr, $v);
                }
            }
            //Site::myDebug('------- The $tar_path before createImageAndThumbnail function call --------');
            // Site::myDebug($tar_path); // In this path, the zip file, and the extracted files are saved
            //Site::myDebug('------- The $mediaArr before createImageAndThumbnail function call --------');
            //Site::myDebug($mediaArr);

            $finalMediaDetails = $this->createImageAndThumbnail($tar_path, $mediaArr);

            //Site::myDebug('------- The $finalMediaDetails after createImageAndThumbnail function call --------');
            //Site::myDebug($finalMediaDetails);
            // $getImgDetails = array();
            // $getImgDir = array();
            // foreach ($mediaArr as $g => $val) {
            // $getImgDetails = pathinfo($val);
            // $getImgDir = $tar_path . "/" . $getImgDetails['dirname'];
            // }
            // Site::myDebug('------- The $getImgDir --------');
            // Site::myDebug($getImgDir);
            ////////////////// THIS PART NEED to be shifted BELOW ////////////////////////

            /* Get Media from Manifest END */

            $itemDetailsArray = array();
            $itemPropertyArray = array();
            $resourceTypeForWebctquiz = $this->getAttribute($manifestFileObj->resources->resource[0], "type");
            //========================== VALIDATIONS - start =========================================================
            $errorMsgArr = array();
            $continueFlag = true;
            if ($resourceTypeForWebctquiz == 'webctquiz') {
                $resourceTypeForWebctquizHref = $this->getAttribute($manifestFileObj->resources->resource[0], "href");
                $resourceTypeForWebctquizHrefPath = $tar_path . "/" . $resourceTypeForWebctquizHref;
                $quizFileDetails = pathinfo($resourceTypeForWebctquizHref);

                if (!file_exists($resourceTypeForWebctquizHrefPath)) {

                    $resourceTypeForWebctquizHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $quizFileDetails['basename']);
                    if (empty($resourceTypeForWebctquizHrefPath)) {
                        $errorMsgArr['notCorrXmlFile'] = 'Package contains different ' . $resourceTypeForWebctquiz . ' XML file than mentioned in imsmanifest file. ';
                        $response_data[0] = $errorMsgArr['notCorrXmlFile'];
                        $continueFlag = false;
                    }
                }
                $quizQIZObj = simplexml_load_file($resourceTypeForWebctquizHrefPath);
                $assSectionCount = count($quizQIZObj->assessment->section);


                for ($i = 0; $i < $assSectionCount; $i++) {
                    $itemDetailsArray[$i]['section'] = $this->getAttribute($quizQIZObj->assessment->section[$i], "ident"); // Section node
                    $itemDetailsArray[$i]['item'] = $this->getAttribute($quizQIZObj->assessment->section[$i]->itemref, "linkrefid"); // itemref node
                }
            }

            $resourceTypeForWebctprop = $this->getAttribute($manifestFileObj->resources->resource[1], "type");

            $resourceTypeForWebctprop1 = $this->getAttribute($manifestFileObj->resources->resource[1]->file, "href");

            if ($resourceTypeForWebctprop == 'webctproperties') {
                $resourceTypeForWebctpropHref = $this->getAttribute($manifestFileObj->resources->resource[1]->file, "href");

                //$resourceTypeForWebctpropHref = $this->getAttribute($manifestFileObj->resources->resource[1],"href");

                $resourceTypeForWebctpropHrefPath = $tar_path . "/" . $resourceTypeForWebctpropHref;
                $propFileDetails = pathinfo($resourceTypeForWebctpropHrefPath);

                if (!file_exists($resourceTypeForWebctpropHrefPath)) {
                    $resourceTypeForWebctpropHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $propFileDetails['basename']);
                    if (empty($resourceTypeForWebctpropHrefPath)) {
                        $errorMsgArr['notCorrXmlFile'] = 'Package contains different ' . $resourceTypeForWebctprop . ' XML file than mentioned in imsmanifest file. ';
                        $response_data[0] = $errorMsgArr['notCorrXmlFile'];
                        $continueFlag = false;
                    }
                }

                $propObj = simplexml_load_file($resourceTypeForWebctpropHrefPath);
                $proScoreObj = $propObj->processing->scores->score;



                for ($p = 0; $p < count($proScoreObj); $p++) {
                    $attribute = $this->getAttribute($proScoreObj[$p], "linkrefid");
                    $scoreVal = (string) $proScoreObj[$p];


                    $itemPropertyArray[$p]['linkrefid'] = $this->getAttribute($proScoreObj[$p], "linkrefid");
                    $itemPropertyArray[$p]['scoreVal'] = (string) $proScoreObj[$p];

                    $itemDetailsTempArr = $this->questSearch($itemDetailsArray, 'section', $attribute);
                    $itemDetailsArray[$itemDetailsTempArr]['score'] = $scoreVal;
                }
                //Site::myDebug('------Final$itemDetailsArray');
                //Site::myDebug($itemDetailsArray);
                //create Assessment          // for question file 
                $resourceTypeForWebctItemHref = $this->getAttribute($manifestFileObj->resources->resource[0]->file[1], "href");
                $resourceTypeForWebctItemHrefPath = $tar_path . "/" . $resourceTypeForWebctItemHref;
                $itemFileDetails = pathinfo($resourceTypeForWebctItemHrefPath);


                if (!file_exists($resourceTypeForWebctItemHrefPath)) {
                    $resourceTypeForWebctItemHrefPath = $this->searchFileRecursivelyInFolder($tar_path, $itemFileDetails['basename']);
                }

                $xmlFileObj = $quizItemObj = simplexml_load_file($resourceTypeForWebctItemHrefPath);
                //Site::myDebug('------ Before the VALIDATION start quizItemObj ============= ');
                //Site::myDebug($quizItemObj);
                // check if score for every item is exist or not start
                for ($k = 0; $k <= count($itemDetailsArray); $k++) {
                    if ($itemDetailsArray[$k]['item'] != "") {

                        if ($itemDetailsArray[$k]['score'] == "") {
                            $errorMsgArr['noScore'] = 'XML has no score for item ident: ' . $itemDetailsArray[$k]['item'];
                            $response_data[0] = $errorMsgArr['noScore'];
                            $continueFlag = false;
                        }
                    }
                }
                // check if score for every item is exist or not end
                if (isset($xmlFileObj->section->item)) {

                    foreach ($xmlFileObj->section->item as $xmlItem) {
                        $cntItemSkill = '';

                        /*                         * ******** new validation for skill STARTS ******************** */

                        $cntItemSkill = count($xmlItem->itemmetadata->qtimetadata->qtimetadatafield);
                        //Site::myDebug('------ Count of skill data ============= ');
                        //Site::myDebug($cntItemSkill);
                        //Site::myDebug('------ Title of the node ============= ');
                        //Site::myDebug(trim($this->getAttribute($xmlItem, 'title')));

                        if (($cntItemSkill > 0) || (($cntItemSkill != ''))) {
                            $isSkill = 0;
                            foreach ($xmlItem->itemmetadata->qtimetadata->qtimetadatafield as $skillItem) {
                                Site::myDebug('------ Contents of itemSkill ============= ');
                                Site::myDebug($skillItem);
                                /*
                                  [fieldlabel] => skill
                                  [fieldentry] => MGS15_0000034
                                 */
                                if ($skillItem->fieldlabel == 'skill') {
                                    $isSkill = 1;
                                    if ($skillItem->fieldentry == '') {
                                        $errorMsgArr['noSkillEntry'] = 'No skill Entry associated with one of the questions  : ' . $this->getAttribute($xmlItem, 'title');
                                        $response_data[0] = $errorMsgArr['noSkillEntry'];
                                        $continueFlag = false;
                                    }
                                }
                                // if($skillItem->fieldlabel =='')
                                // {
                                // $errorMsgArr['noSkillLabel'] = 'No skill Label associated with the question : '.$this->getAttribute($xmlItem, 'title');
                                // $response_data[0] = $errorMsgArr['noSkillLabel'];
                                // $continueFlag = false;
                                // }
                            }
                            if ($isSkill == 0) {
                                $errorMsgArr['noSkill'] = 'No skill associated with one of the questions  : ' . $this->getAttribute($xmlItem, 'title');
                                $response_data[0] = $errorMsgArr['noSkill'];
                                $continueFlag = false;
                            }
                        } else {
                            $errorMsgArr['noSkill'] = 'No skill associated with one of the questions  : ' . $this->getAttribute($xmlItem, 'title');
                            $response_data[0] = $errorMsgArr['noSkill'];
                            $continueFlag = false;
                        }



                        /*                         * ******** new validation for skill Ends ********************* */




                        $itemType = $xmlItem->itemmetadata->qmd_itemtype;
                        $multipleChoiceType = $this->getAttribute($xmlItem->presentation->flow->response_lid, 'rcardinality');
                        //check if question title is empty - start		

                        if (trim($this->getAttribute($xmlItem, 'title')) == '') {
                            $errorMsgArr['noTitle'] = 'XML has no Question Title for Item ident: ' . $this->getAttribute($xmlItem, 'ident');
                            $response_data[0] = $errorMsgArr['noTitle'];
                            $continueFlag = false;
                            //break;
                        }
                        //check if question title is empty - end
                        // check if question has to be MCSS and Essay type only start
                        if ($multipleChoiceType != 'Single' && $itemType == 'Logical Identifier') {
                            $errorMsgArr['typeDifferent'] = 'XML contains different Question type than MCSS and Essay. ';
                            $response_data[0] = $errorMsgArr['typeDifferent'];
                            $continueFlag = false;
                        }
                        // check if question has to be MCSS and Essay type only End
                        //For MCSS questions, check if the choices has any correct response - start	    
                        if ($itemType == 'Logical Identifier' && $multipleChoiceType == 'Single') {
                            $choicesAnsArr = array();
                            $choices = $xmlItem->resprocessing->respcondition;
                            foreach ($choices as $ch) {
                                array_push($choicesAnsArr, $ch->setvar);
                            }
                            if (!in_array('100', $choicesAnsArr)) {
                                $errorMsgArr['noCorrResp'] = 'XML has no Correct Response for Item: ' . $this->getAttribute($xmlItem, 'title');
                                $response_data[0] = $errorMsgArr['noCorrResp'];
                                $continueFlag = false;
                                //break;
                            }
                        }
                        //For MCSS questions, check if the choices has any correct response - end		
                    }
                } else {

                    //Xml has no item node - start
                    $errorMsgArr['noItem'] = 'XML has no Items';
                    $response_data[0] = $errorMsgArr['noItem'];
                    $continueFlag = false;
                    //break;
                    //Xml has no item node - end
                }

                //========================== VALIDATIONS - end =========================================================

                if ($continueFlag == true) {

                    /*                     * ************** Get Title from Manifest START *************************** */
                    //$assesment_name = (string)$manifestFileObj->organizations->organization->item->title;
                    $assesment_name = $this->specialCharCheck($uploaded_file_name);
                    Site::myDebug("------------ Assesment Name ---------------");
                    Site::myDebug($assesment_name);
                    /*                     * ************** Get Title from Manifest Ends *************************** */
                    //$assesment_name = $input["AssessmentName"] = "Assessment " . $this->currentDate();

                    $aassessment = new Assessment();
                    $assessmentcheck = $aassessment->assessmentExist($assesment_name);

                    /*                     * ** CODE commented as on 26th march 2014  as for the new requirement ** */
                    /* if (isset($assessmentcheck['RS'][0]['ID'])) {					
                      $quizID = $assessmentcheck['RS'][0]['ID'];
                      } else {
                      $quizID = $this->Assess->save($input);
                      } */
                    /*                     * ** CODE commented as on 26th march 2014  as for the new requirement ** */

                    /*                     * ********** New req for assessment name ****************** */
                    if (isset($assessmentcheck['RS'][0]['ID'])) {
                        $uniqtimestr = strtotime("now");
                        //$assesment_name .= '_'.$uniqtimestr;							
                        $assesment_name .= '_' . $this->currentDate();
                        $input["AssessmentName"] = $assesment_name;
                        $quizID = $this->Assess->save($input);
                    } else {
                        $input["AssessmentName"] = $assesment_name;
                        $quizID = $this->Assess->save($input);
                    }
                    /*                     * ********** New req for assessment name ****************** */

                    $qtiEntityTypeID = 2;      // Imported from Assesment module.
                    //$this->auth->copyCss($quizID, $qtiEntityTypeID);

                    $sectionItems = $quizItemObj->section;
                    $rootItems = $quizItemObj->section->item;

                    $i = 0;
                    if (count($sectionItems)) {

                        foreach ($sectionItems as $secItem) {

                            if (!empty($secItem) && count($secItem) > 0) {
                                $sectionTitle = $this->getAttribute($secItem, "title");
                                $sectionTitle = ($sectionTitle == "") ? "sec" : $sectionTitle;
                                //add section
                                //$sectionId     = $this->Assess->section($quizID,0,$sectionTitle,"ADDSEC");
                                /* Need to add section title */
                                //and then add its question in this section
                                $questionCount = count($secItem->item);

                                // $questionItems  = $secItem->item;
                                $questionItems = $secItem;

                                $questCntr = count($questionItems);
                                //Site::myDebug('--$questionItems');
                                //Site::myDebug(count($questionItems)); //4
                                //In QTI Import for Pegasus, section node was getting added in questions node count so extra number was getting added in Questions imported into the system
                                //Site::myDebug(' ========== Calculations fo questionItems Starts ===============');
                                //Site::myDebug(count($questionItems)); 
                                //Site::myDebug(count($sectionItems)); 
                                $questionItems = count($questionItems) - count($sectionItems);
                                $getImgDir = '';
                                $this->qti1_2AddQsWithExam_TestBuilder($secItem, $quizID, $sectionId, $getImgDir, $itemDetailsArray, $finalMediaDetails);
                                $i = $i + $questionItems;
                                // }
                                //end of add quest     
                            }
                        }
                    }
                }
            }
        } else {
            $continueFlag = false;
            $response_data[0] = 'Manifest file not found';
        }


        $data = array('ImportTitle' => $assesment_name, 'EntityType' => 2, 'ImportType' => 'QTI1.2 Test Builder', 'TotalQuest' => $questionCount,
            'TotalQuestImported' => $actual_quest_import, 'Summary' => '', 'EntityID' => $quizID,
            'FileName' => basename($qtifile_src), 'AddDate' => $this->currentDate(), 'UserID' => $this->session->getValue('userID'));
        $this->addImportDetails($data);
        $response_data[] = $quizID;
        $response_data[] = $assesment_name;
        $response_data[] = $actual_quest_import; //$i;
        $response_data[] = $actual_quest_import;
        $response_data[] = $continueFlag;

        Site::myDebug(' Before returning to the response_data=====');
        Site::myDebug($response_data);

        return $response_data;
        //exit;     
    }

    /**
     * Asset handling for import question in QTI2.1 ExamView and TestBuilder format
     * PAI02 :: sprint 1
     *
     * @access   private
     * @abstract
     * @static
     * @param    array $MediaDetailArray  
     * @return   array
     *
     */
    function createImageAndThumbnail($tar_path, $MediaDetailArray) {
        //Site::myDebug(" ------- Inside function createImageAndThumbnail the MediaDetailArray === ");
        //Site::myDebug($MediaDetailArray);
        $imageobj = new SimpleImage();
        $media = new Media();
        $imageDetails = array();
        $i = 0;
        foreach ($MediaDetailArray as $k => $v) {
            $img_name = substr(strrchr($v, '/'), 1);
            $ext = substr(strrchr($img_name, "."), 1);
            //Site::myDebug(" ------- Inside foreach block === ");
            //Site::myDebug($img_name);//audio02.mp3
            //Site::myDebug($ext);//.mp3

            if (in_array($ext, $this->cfgApp->videoFormats)) {
                $imgDetails = pathinfo($v);
                $guid = uniqid("media");
                $mediaName = $guid . '.' . $ext;
                //Site::myDebug('==============DOLLERmediaName in VIDEO BLOCK======= '.$mediaName);

                $orgVidioLocation = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/video/';
                @mkdir($orgVidioLocation, 0777, true);

                $orgImgTargetPath = $tar_path . '/' . $v;
                $quadImgTargetPath = $orgVidioLocation . $img_name;
                copy($orgImgTargetPath, $quadImgTargetPath);

                Site::myDebug(" ####### Video S3 ######## ");
                if ($this->cfg->S3bucket) {
                    $S3VideoFilePath = str_replace($this->cfg->rootPath . '/', "", $quadImgTargetPath);
                    $S3VideoFilePath = str_replace("//", "/", $S3VideoFilePath);
                    s3uploader::upload($quadImgTargetPath, $S3VideoFilePath);
                    Site::myDebug("S3VideoFilePath= " . $S3VideoFilePath);
                }

                $imageDetails[$i]['FileName'] = $mediaName;
                $imageDetails[$i]['OriginalFileName'] = $img_name;
                $info = array(
                    'Title' => $img_name,
                    'Keywords' => "",
                    'ContentType' => 'Video',
                    'ContentInfo' => $mediaName,
                    'UserID' => $this->session->getValue('userID'),
                    'AddDate' => $this->currentDate(),
                    'ModBY' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'FileName' => $mediaName,
                    'isEnabled' => '1',
                    'Thumbnail' => '',
                    'ContentHeight' => '0',
                    'ContentWidth' => '0',
                    'Count' => '1',
                    'OriginalFileName' => $img_name
                );
                Site::myDebug('--- The Info array INSIDE VIDEO block ----');
                Site::myDebug($info);
                $mediaID = $media->add($info);
                $imageDetails[$i]['mediaID'] = $mediaID;
                $imageDetails[$i]['xmlMediaPath'] = $v;
            } elseif (in_array($ext, $this->cfgApp->audioFormats)) {
                $imgDetails = pathinfo($v);
                $guid = uniqid("media");
                $mediaName = $guid . '.' . $ext;
                //Site::myDebug('==============DOLLERmediaName IN AUDIO block======= '.$mediaName);

                $orgAudioLocation = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/audios/';
                @mkdir($orgAudioLocation, 0777, true);

                $orgImgTargetPath = $tar_path . '/' . $v;
                $quadImgTargetPath = $orgAudioLocation . $mediaName;
                copy($orgImgTargetPath, $quadImgTargetPath);

                Site::myDebug(" ####### Audio S3 ######## ");
                if ($this->cfg->S3bucket) {
                    $S3AudioFilePath = str_replace($this->cfg->rootPath . '/', "", $quadImgTargetPath);
                    $S3AudioFilePath = str_replace("//", "/", $S3AudioFilePath);
                    s3uploader::upload($quadImgTargetPath, $S3AudioFilePath);
                    Site::myDebug("S3AudioFilePath= " . $S3AudioFilePath);
                }

                $imageDetails[$i]['FileName'] = $mediaName;
                $imageDetails[$i]['OriginalFileName'] = $img_name;
                $info = array(
                    'Title' => $img_name,
                    'Keywords' => "",
                    'ContentType' => 'Audio',
                    'ContentInfo' => $mediaName,
                    'UserID' => $this->session->getValue('userID'),
                    'AddDate' => $this->currentDate(),
                    'ModBY' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'FileName' => $mediaName,
                    'isEnabled' => '1',
                    'Thumbnail' => '',
                    'ContentHeight' => '0',
                    'ContentWidth' => '0',
                    'Count' => '1',
                    'OriginalFileName' => $img_name
                );
                Site::myDebug('--- The Info array INSIDE AUDIO block ----');
                Site::myDebug($info);
                $mediaID = $media->add($info);
                $imageDetails[$i]['mediaID'] = $mediaID;
                $imageDetails[$i]['xmlMediaPath'] = $v;
            } elseif (in_array($ext, $this->cfgApp->imgFormats)) {
                $imgDetails = pathinfo($v);
                $guid = uniqid("media");
                $orgImageLocation = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/images/original/';
                $thumbImageLocation = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/images/thumb/';

                $orgImgTargetPath = $tar_path . '/' . $v;

                $mediaName = $guid . '.jpg';
                $quadImgTargetPath = $orgImageLocation . $mediaName;

                copy($orgImgTargetPath, $quadImgTargetPath);

                //Site::myDebug(" ####### test S3 ######## ");
                if ($this->cfg->S3bucket) {
                    $S3ImgFilePath = str_replace($this->cfg->rootPath . '/', "", $quadImgTargetPath);
                    $S3ImgFilePath = str_replace("//", "/", $S3ImgFilePath);
                    s3uploader::upload($quadImgTargetPath, $S3ImgFilePath);
                }

                $imageDetails[$i]['FileName'] = $mediaName;

                $thumbMediaName = "thumb_" . $guid . '.jpg';
                $quadthumbImageLocation = $thumbImageLocation . $thumbMediaName;

                /*                 * ** resize the image - start *** */
                $imageobj->load($orgImgTargetPath);
                $imageobj->resizeToHeight(100);
                $imageobj->resizeToWidth(100);
                $imageobj->save($quadthumbImageLocation);

                Site::myDebug(" ####### test S3 ######## ");
                if ($this->cfg->S3bucket) {
                    $S3ImgThumbFilePath = str_replace($this->cfg->rootPath . '/', "", $quadthumbImageLocation);
                    $S3ImgThumbFilePath = str_replace("//", "/", $S3ImgThumbFilePath);
                    s3uploader::upload($quadthumbImageLocation, $S3ImgThumbFilePath);
                }

                $imageDetails[$i]['thumb'] = $thumbMediaName;
                $imageDetails[$i]['OriginalFileName'] = $imgDetails['basename'];
                $info = array(
                    'Title' => $imgDetails['filename'],
                    'Keywords' => "",
                    'ContentType' => 'Image',
                    'ContentInfo' => $mediaName,
                    'UserID' => $this->session->getValue('userID'),
                    'AddDate' => $this->currentDate(),
                    'ModBY' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'FileName' => $mediaName,
                    'isEnabled' => '1',
                    'Thumbnail' => $thumbMediaName,
                    'ContentHeight' => '0',
                    'ContentWidth' => '0',
                    'Count' => '1',
                    'OriginalFileName' => $imgDetails['basename']
                );
                //Site::myDebug('--- The Info array after re-written ----');
                //Site::myDebug($info);

                $mediaID = $media->add($info);
                $imageDetails[$i]['mediaID'] = $mediaID;
                $imageDetails[$i]['xmlMediaPath'] = $v;
            } else {
                Site::myDebug(" ####### This Format is not supported. ######## ");
            }
            $i++;
        }
        return $imageDetails;
    }

    function questSearch($array, $key, $value) {
        foreach ($array as $k => $v) {

            if (isset($v[$key]) && $v[$key] == $value) {

                return $k;
            }
        }
    }

    /**
     * gets question tite used while importing question in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $q
     * @return   array
     *
     */
    function qtiQuestionTitle($q) {
        $data['title'] = $this->formatJson($this->getAttribute($q, "title"), 0); //	(string) $q['title'];
        $op = ($q->presentation->flow) ? $q->presentation->flow->material : $q->presentation->material;
        if (!empty($op)) {
            foreach ($op as $op1) {
                if ($op1->mattext) {
                    $data['qtext'] = $op1->mattext;
                }
                if ($op1->matimage) {
                    $data['media_img'] = $this->getAttribute($op1->matimage, "uri");
                }
                if ($op1->matvideo) {
                    $data['media_video'] = $this->getAttribute($op1->matvideo, "uri");
                }
            }
        }

        return $data;
    }

    /**
     * gets response type of question while importing question in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $type_data
     * @return   string
     *
     */
    function qtiRespType($type_data) {
        if ($type_data->matvideo) {
            $type = "matvideo";
        } else {
            $type = ($type_data->matimage) ? "matimage" : "mattext";
        }
        return $type;
    }

    /**
     * add question in assessment which is in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $questionItems
     * @param    integer $quizID
     * @param    integer $sectionId
     * @return   void
     *
     */
    function qtiAddQuestion(array $questionItems, $quizID, $sectionId, $eType) {
        global $APPCONFIG, $DBCONFIG;
        Site::myDebug('-------qtiAddQuestion');
        global $actual_quest_import;
        if (!empty($questionItems)) {
            foreach ($questionItems as $q) {
                $qt = '';
                $strxml = $q->asXML();
                $renderfib = strpos($strxml, "render_fib");
                $renderfib_str = "render_fib";

                if ($q->presentation->flow) {
                    $questTitle = addslashes($q->presentation->flow->material->mattext);
                    if ($renderfib) {

                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {

                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->flow->response_lid[0];

                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->flow_mat->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->flow_label->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                } else {
                    $questTitle = addslashes($q->presentation->material->mattext);

                    if ($renderfib) {
                        // essay section is coming here 
                        $rcardinality = $this->getAttribute($q->presentation->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                }


                $questType = $q->itemmetadata->qmd_itemtype; // questtype is coming for essay
                // if question type true /false and containing true anf false words then it treate as true false template else template type is MCSS
                if (stripos($questType, "true") > -1) {
                    if ($q->presentation->flow) {
                        $val = $q->presentation->flow->response_lid[0]->render_choice->response_label[0]->flow_mat->material->mattext;
                    } else {
                        $val = $q->presentation->response_lid[0]->render_choice->response_label[0]->material->mattext;
                    }

                    $questType = (stripos($val, "true") > -1 || stripos($val, "false") > -1) ? $questType : "Multiple Choice";
                }


                // end
                Site::myDebug('------$rcardinality-----');
                Site::myDebug($rcardinality);
                Site::myDebug('------$questType-----');
                Site::myDebug($questType);
                Site::myDebug('------$typeIdent-----');
                Site::myDebug($typeIdent);
//                echo $typeIdent."=======================<br>";
                if ($typeIdent == 'matvideo') {
                    continue;
                }


                $qtdetail = $this->getQuadQuestionTypeId($questType, $rcardinality, $typeIdent, $q);
//                    print "<pre>";
//                   print_r($qtdetail);
//                   echo "=========================";
//                    exit;
                Site::myDebug('----$qtdetail---');
                Site::myDebug($qtdetail);
                $qt = $qtdetail['ID'];
                if ($qt != '') {
                    $actual_quest_import = $actual_quest_import + 1;
                    //echo "<br>Include File NAme = ".$this->cfg->rootPath."/".$this->cfgApp->QuestionTemplateResourcePath."/".$qtdetail['TemplateFile'].".php";
                    include_once($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . "/" . $qtdetail['TemplateFile'] . ".php");


                    $template_file_obj = new $qtdetail['TemplateFile']();
                    Site::myDebug('----$template_file_obj----');
                    Site::myDebug($template_file_obj);


                    $questJSON = $template_file_obj->qti1v2QuestionJson($q);

                    $sUserID = $this->session->getValue('userID');
                    $presentation_data = $this->qtiQuestionTitle($q);
                    $questTitle = $presentation_data['title'];
                    Site::myDebug('---$questJSON');
                    Site::myDebug($questJSON);

                    if (!empty($questJSON)) {
                        // print_r($questJSON); ////
                        $newQuestionStruct = $this->getNewQstJsonStruct($qtdetail, $questJSON);
                        // for adding score in JSON TODO Score is not saving proper format
                        // $score= $q->itemmetadata->qtimetadata->qtimetadatafield[0]->fieldentry;                        
                        // $aaa=json_decode($newQuestionStruct,TRUE);                       
                        // $aaa['settings']['score']= '"'.$score.'"';                        
                        // $newQuestionStruct=  json_encode($aaa);
                        //Site::myDebug($oldQuestArr);
                        $arrQuestion = array(
                            'Title' => strip_tags(preg_replace('/\'/', '"', $questTitle)),
                            'XMLData' => 'NA',
                            'JSONData' => preg_replace('/\'/', '"', $questJSON),
                            'advJSONData' => preg_replace('/\'/', '"', $newQuestionStruct),
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt,
                            'Difficulty' => $this->qti1v2Difficulty($q, "qmd_levelofdifficulty")
                        );


                        $questid = $this->qst->newQuestSave($arrQuestion);

                        if ($DBCONFIG->dbType == 'Oracle') {
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
                        }
                        Site::myDebug('-----------newQuestSave');
                        Site::myDebug($questid);
                        if ($questid != '0') {
                            $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                                $questid,
                                $quizID,
                                $eType,
                                $sectionId, 'ADDQST', $sUserID,
                                $this->currentDate(), $sUserID,
                                $this->currentDate()), 'details');
                            // $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
                            $repositoryid = $result['Total_RepositoryID'];
                            Site::myDebug('---MapRepositoryQuestionsManage');
                            Site::myDebug($repositoryid);
                            $this->qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                            Site::myDebug('---questionActivityTrack');
                            Site::myDebug($result);
                        }
                    }
                }
            }
        }
    }

    function qtiPegasusAddQuestion($questionItems, $quizID, $sectionId) {
        global $APPCONFIG, $DBCONFIG;
        Site::myDebug('-------qtiAddQuestion');
        global $actual_quest_import;
        if (!empty($questionItems)) {
            foreach ($questionItems->{'item'} as $q) {

                $qt = '';
                $strxml = $q->asXML();
                $renderfib = strpos($strxml, "render_fib");
                $renderfib_str = "render_fib";

                if ($q->presentation->flow) {
                    $questTitle = addslashes($q->presentation->flow->material->mattext);
                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->flow->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->flow_mat->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->flow_label->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                } else {
                    $questTitle = addslashes($q->presentation->material->mattext);
                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                }
                $questType = $q->itemmetadata->qmd_itemtype;

                // if question type true /false and containing true anf false words then it treate as true false template else template type is MCSS
                if (stripos($questType, "true") > -1) {

                    if ($q->presentation->flow) {
                        //$val=$q->presentation->flow->response_lid[0]->render_choice->response_label[0]->flow_mat->material->mattext;
                        $val = $q->presentation->flow->response_lid[0]->render_choice->flow_label->response_label[0]->material->mattext;
                    } else {
                        $val = $q->presentation->response_lid[0]->render_choice->response_label[0]->material->mattext;
                    }
                    if ($questType != '' && $val != '') {
                        $questType = (stripos($val, "true") > -1 || stripos($val, "false") > -1) ? $questType : "Multiple Choice";
                    }
                }
                // end


                if ($questType == 'Multiple Choice Static') {
                    $questType = 'Multiple Choice';
                }


                $qtdetail = $this->getQuadQuestionTypeId($questType, $rcardinality, $typeIdent, $q);

                $qt = $qtdetail['ID'];

                if ($qt != '') {
                    $actual_quest_import = $actual_quest_import + 1;
                    if ($q->itemmetadata->qmd_itemtype == 'Multiple Choice Static') {
                        $qtdetail['TemplateFile'] = 'MCSSStaticText';
                    } else if (stripos($val, "true") > -1 == 'TrueFalse') {
                        $qtdetail['TemplateFile'] = 'TrueFalsePegasus';
                    }


                    include_once($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . $qtdetail['TemplateFile'] . ".php");
                    // echo $this->cfg->rootPath."/".$this->cfgApp->QuestionTemplateResourcePath."/".$qtdetail['TemplateFile'].".php";
                    $template_file_obj = new $qtdetail['TemplateFile']();

                    $questJSON = $template_file_obj->qti1v2QuestionJson($q);
                    $questMetaData = $template_file_obj->qti1v2QuestionMetaData($q);

                    $sUserID = $this->session->getValue('userID');

                    $presentation_data = $this->getAttribute($q, "title");
                    $questTitle = $presentation_data;
                    Site::myDebug('---$questJSON');
                    Site::myDebug($questJSON);
                    if (!empty($questJSON)) {
                        $arrQuestion = array(
                            'Title' => $questTitle, //strip_tags( preg_replace('/\'/', '"', $questTitle) ),
                            'XMLData' => 'NA',
                            'JSONData' => $questJSON, //preg_replace('/\'/', '"', $questJSON) ,
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt,
                            'Difficulty' => $this->qti1v2Difficulty($q, "qmd_levelofdifficulty")
                        );


                        $questid = $this->qst->newQuestSave($arrQuestion);

                        if ($DBCONFIG->dbType == 'Oracle') {
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
                        }
                        Site::myDebug('-----------newQuestSave');
                        Site::myDebug($questid);

                        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                            $questid,
                            $quizID,
                            2,
                            $sectionId, 'ADDQST', $sUserID,
                            $this->currentDate(), $sUserID,
                            $this->currentDate()), 'details');
                        // $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
                        $repositoryid = $result['Total_RepositoryID'];

                        /* Import Meta data start */
                        $str = '';
                        if ($questMetaData != '') {
                            $metaDataJSON = json_decode($questMetaData, true);

                            $metaDataClass = new Metadata();
                            $str = '';

                            for ($i = 0; $i < count($metaDataJSON); $i++) {


                                if ($metaDataJSON[$i]['text'] != '' && $metaDataJSON[$i]['val'] != '') {

                                    // $metaDataKeyCheck   = $this->db->getSingleRow("SELECT * FROM MetaDataKeys WHERE MetaDataName= '{$metaDataJSON[$i]->text}' and UserID = '{$this->session->getValue('userID')}' ");                                                    
                                    $metaDataKeyCheck = $this->db->getSingleRow(" SELECT mdk.ID,mdk.MetaDataName FROM MetaDataKeys mdk
                                     inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
                                     WHERE mdk.MetaDataName = '{$metaDataJSON[$i]['text']}' AND  mdk.isEnabled = '1'");

                                    $metaDataVal = $metaDataJSON[$i]['text'];
                                    $metaDataKeyValues = $metaDataJSON[$i]['val'];

                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        $metaKeyID = '';
                                        $metaKeyName = '';
                                    } else {
                                        $metaKeyID = $metaDataKeyCheck['ID'];
                                        $metaKeyName = $metaDataKeyCheck['MetaDataName'];
                                    }

                                    // $checkKeyName = $metaDataClass->checkMetadaDataKeyName($metaDataJSON[$i]->text,'' );                            

                                    $this->input = array(
                                        'pgncp' => '1',
                                        'pgnob' => '-1',
                                        'pgnot' => '-1',
                                        'pgndc' => '-1',
                                        'pgnstart' => '0',
                                        'pgnstop' => '-1',
                                        'rt' => 'metadata/metadata-save',
                                        'metaDataKeyName' => $metaDataJSON[$i]['text'],
                                        'metaDataKeyId' => '',
                                        'metaDataKeyType' => 'text_entry',
                                        'metaDataKeyValues' => '',
                                        'metaDataKeyValueDeletedList' => '',
                                        '' => ''
                                    );

                                    //if( $checkKeyName != true )
                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        $MetaDataDetail = $metaDataClass->metadataSaveForPegasus($this->input);
                                        $ID = $MetaDataDetail['ID'];

                                        if ($ID) {
                                            $keyName = $MetaDataDetail['KeyName'];
                                            $keyValues = $MetaDataDetail['KeyValues'];
                                            $useCount = $MetaDataDetail['UseCount'];
                                            $status = $MetaDataDetail['Status'];
                                            $modDate = $MetaDataDetail['ModDate'];
                                            $metaDataType = $MetaDataDetail['MetaDataType'];
                                        }
                                    } else {
                                        $ID = $metaKeyID;
                                        $keyName = $metaKeyName;
                                    }

                                    if ($ID != '' && $keyName != '') {
                                        //if the metadata value contains comma, then metadata value will get inserted but it wont show up
                                        //in classification pop up so comma is replaced by space
                                        if (strstr($metaDataJSON[$i]['val'], ',')) {
                                            $metaDataJsonVal = str_replace(',', ' ', $metaDataJSON[$i]['val']);
                                        } else {
                                            $metaDataJsonVal = $metaDataJSON[$i]['val'];
                                        }
                                        $str.=$ID . '|' . $keyName . '|||' . $metaDataJsonVal . '#';
                                    }
                                }
                            }

                            $assignMetaKVArray = array();
                            $assignMetaKVArray['manualKeysValues'] = $str;


                            $metaDataClass->assignedMetadataForPegasus($assignMetaKVArray, $repositoryid, 3);
                        }
                        /* Import meta data end */


                        Site::myDebug('---MapRepositoryQuestionsManage');
                        Site::myDebug($repositoryid);
                        $this->qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                        Site::myDebug('---questionActivityTrack');
                        Site::myDebug($result);
                    }
                }
            }
        }
    }

    /**
     * import question in QTI2.1 examView format
     *
     * @access   private     
     */
    function qti1_2AddQuestionWithExamView($questionItems, $quizID, $sectionId, $qtifile, $itemNodeArray, $qtimgdetail) {
        global $APPCONFIG, $DBCONFIG;
        global $actual_quest_import;

        if (!empty($questionItems)) {
            foreach ($questionItems->{'item'} as $q) {

                set_time_limit(0);
                $qt = '';
                $strxml = $q->asXML();
                $renderfib = strpos($strxml, "render_fib");
                $renderfib_str = "render_fib";

                if ($q->presentation->flow) {

                    /*                     * *********** NEW code **************************** */
                    /* Site::myDebug('------- Start new CODE block for replacing the image name FIRST ---------');
                      $mattext = $q->presentation->flow->material->mattext;
                      Site::myDebug('$mattext1111111==========  ' . $mattext);
                      if (strpos($mattext, 'img') !== false|| strpos($mattext, '<a') !== false) {
                      foreach ($qtimgdetail as $imgdt) {
                      Site::myDebug('------qtidetials');
                      Site::myDebug($imgdt);

                      if (strpos($mattext, $imgdt['OriginalFileName'])) {
                      $mattext_new = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $mattext);
                      Site::myDebug($mattext_new);

                      Site::myDebug('$mattext2222222==========  ' . $mattext_new);
                      $ret_data = $this->qst->addMediaPlaceHolder($mattext_new);
                      Site::myDebug('ret_data===================== ' . $ret_data);

                      $q->presentation->flow->material->mattext = $ret_data;
                      }
                      }
                      } */
                    /**                     * ********** NEW code **************************** */
                    /*                     * *********** NEW code **************************** */
                    $mattext = addslashes($q->presentation->flow->material->mattext);
                    $matAudioNode = $q->presentation->flow->material->altmaterial->mataudio;


                    preg_match('/<a[^>]+>(.*?)<\/a>/i', $mattext, $res);

                    if (!empty($res)) {
                        $this->qst->assetList = $qtimgdetail;
                        $mattext = $this->qst->addMediaPlaceHolder($mattext);
                        $q->presentation->flow->material->mattext = $mattext;
                        $this->qst->assetList = '';
                    }


                    if (strpos($mattext, 'img') !== false) {
                        foreach ($qtimgdetail as $imgdt) {
                            if (strpos($mattext, $imgdt['OriginalFileName'])) {
                                $mattext = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $mattext);
                            }
                        }
                        $mattext_new = $mattext;
                        $ret_data = $this->qst->addMediaPlaceHolder($mattext_new);
                        $mattext = $ret_data;
                        $q->presentation->flow->material->mattext = $ret_data;
                    }
                    if (!empty($matAudioNode)) {
                        foreach ($qtimgdetail as $imgdt) {
                            $matAudioNode = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $matAudioNode);
                        }
                        $mattext_new = $matAudioNode;
                        $mattext_new = " <img src='{$matAudioNode}' />";
                        $ret_data = $this->qst->addMediaPlaceHolder($mattext_new);
                        $q->presentation->flow->material->mattext = $mattext . $ret_data;
                    }

                    /*                     * *********** NEW code **************************** */

                    $questTitle = addslashes($q->presentation->flow->material->mattext);
                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->flow->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->flow_mat->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->flow_label->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                } else {
                    $questTitle = addslashes($q->presentation->material->mattext);

                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                }
                $questType = $q->itemmetadata->qmd_itemtype;


                // if question type true /false and containing true anf false words then it treate as true false template else template type is MCSS
                if (stripos($questType, "true") > -1) {

                    if ($q->presentation->flow) {
                        //$val=$q->presentation->flow->response_lid[0]->render_choice->response_label[0]->flow_mat->material->mattext;
                        $val = $q->presentation->flow->response_lid[0]->render_choice->flow_label->response_label[0]->material->mattext;
                    } else {
                        $val = $q->presentation->response_lid[0]->render_choice->response_label[0]->material->mattext;
                    }
                    if ($questType != '' && $val != '') {
                        $questType = (stripos($val, "true") > -1 || stripos($val, "false") > -1) ? $questType : "Multiple Choice";
                    }
                }



                if ($q->itemmetadata->qmd_itemtype == 'Logical Identifier') {
                    $questType = 'Multiple Choice';

                    /* For MCSSSelactable image start */
                    if ($q->presentation->flow) {
                        $resLabel = $q->presentation->flow->response_lid->render_choice->flow_label->response_label;
                        $resLabelCount = count($resLabel);
                        Site::myDebug('---rspo----');
                        for ($r = 0; $r < $resLabelCount; $r++) {
                            Site::myDebug('---rspo1----');
                            Site::myDebug($q->presentation->flow->response_lid->render_choice->flow_label->response_label[$r]->material->mattext);
                            if (strpos($q->presentation->flow->response_lid->render_choice->flow_label->response_label[$r]->material->mattext, 'img') > -1) {
                                $typeIdent = 'matimage';
                                Site::myDebug('---rspo3----');
                                Site::myDebug($typeIdent);
                            }
                        }
                    } else {
                        $resLabel = $q->presentation->response_lid->render_choice->flow_label->response_label;
                        $resLabelCount = count($resLabel);
                        Site::myDebug('---rspo----');
                        for ($r = 0; $r < $resLabelCount; $r++) {
                            Site::myDebug('---rspo1----');
                            Site::myDebug($q->presentation->response_lid->render_choice->flow_label->response_label[$r]->material->mattext);
                            if (strpos($q->presentation->response_lid->render_choice->flow_label->response_label[$r]->material->mattext, 'img') > -1) {
                                $typeIdent = 'matimage';
                                Site::myDebug('---rspo3----');
                                Site::myDebug($typeIdent);
                            }
                        }
                    } /* For MCSSSelactable image end  */
                } else if ($q->itemmetadata->qmd_itemtype == 'String') {
                    $questType = 'Essay';
                }

                /* else if($q->itemmetadata->qmd_itemtype == 'True / False')
                  {
                  $questType = 'True / False';
                  }
                 */
                $qtdetail = $this->getQuadQuestionTypeId($questType, $rcardinality, $typeIdent, $q);

                $qt = $qtdetail['ID'];

                if ($qt != '') {
                    $actual_quest_import = $actual_quest_import + 1;

                    if ($q->itemmetadata->qmd_itemtype == 'Logical Identifier') {
                        if ($typeIdent == 'matimage') {
                            $qtdetail['TemplateFile'] = 'MCSSSelectableImageExamView';
                        } else {
                            $qtdetail['TemplateFile'] = 'MCSSMediaExamView';
                        }
                    } else if ($q->itemmetadata->qmd_itemtype == 'String') { // For Essay
                        $qtdetail['TemplateFile'] = 'EssayExamView';
                    }
                    /* else if ($q->itemmetadata->qmd_itemtype == 'True / False')
                      {
                      $qtdetail['TemplateFile'] = 'TrueFalsePegasus';
                      } */


                    include_once($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . $qtdetail['TemplateFile'] . ".php");
                    Site::myDebug('---template object-----');
                    Site::myDebug($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . $qtdetail['TemplateFile'] . ".php");


                    $template_file_obj = new $qtdetail['TemplateFile']();

                    $questJSON = $template_file_obj->qti1v2QuestionJson($q, $itemNodeArray, 'ExamView', $qtimgdetail);
                    Site::myDebug('---template object-----questJSON1------');
                    Site::myDebug($questJSON);
                    $questMetaData = $template_file_obj->qti1v2QuestionMetaData($q);
                    Site::myDebug('----questmetadta');
                    Site::myDebug($questMetaData);

                    $sUserID = $this->session->getValue('userID');

                    $presentation_data = $this->getAttribute($q, "title");
                    $questTitle = $presentation_data;
                    Site::myDebug('---$questJSON');
                    Site::myDebug($questJSON);

                    if (!empty($questJSON)) {

                        $arrQuestion = array(
                            'Title' => $questTitle,
                            'XMLData' => 'NA',
                            'JSONData' => $questJSON,
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt
                        );


                        $questid = $this->qst->newQuestSave($arrQuestion);

                        if ($DBCONFIG->dbType == 'Oracle') {
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
                        }


                        // QUAD-146:- Ensure that the import feature adds questions directly into the Bank.       
                        if ($this->qtiImportFrom == 'qtiBank') {
                            $qtiEntityTypeID = 1;      // Imported from Bank module.
                        } else {
                            $qtiEntityTypeID = 2;      // Imported from Assesment module.
                        }

                        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                            $questid,
                            $quizID,
                            $qtiEntityTypeID,
                            '0', 'ADDQST', $sUserID,
                            $this->currentDate(), $sUserID,
                            $this->currentDate()), 'details');

                        $repositoryid = $result['Total_RepositoryID'];

                        /* Import Meta data start */
                        $str = '';
                        if ($questMetaData != '') {
                            Site::myDebug('---Inside Import Meta WithExamView data start-----');
                            Site::myDebug($questMetaData);
                            $metaDataJSON = json_decode($questMetaData, true);

                            Site::myDebug($metaDataJSON);
                            $metaDataClass = new Metadata();
                            $str = '';
                            $arr = '';

                            for ($i = 0; $i < count($metaDataJSON); $i++) {

                                if ($metaDataJSON[$i]['text'] != '' && $metaDataJSON[$i]['val'] != '') {

                                    // Added htmlentites for html entities 
                                    $metaDataJSON[$i]['val'] = htmlentities($metaDataJSON[$i]['val'], ENT_QUOTES, "UTF-8");
                                    Site::myDebug('================= metaDataJSON printed ===============');
                                    Site::myDebug($metaDataJSON);

                                    Site::myDebug('================= metaDataJSON Query ===============');
                                    Site::myDebug(" SELECT mdk.ID,mdk.MetaDataName FROM MetaDataKeys mdk
									inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
									WHERE mdk.MetaDataName = '{$metaDataJSON[$i]['text']}' AND  mdk.isEnabled = '1'");

                                    /*                                     * ** this following block for the new requirement -- topic == skill *** */
                                    if ($metaDataJSON[$i]['text'] == 'topic') {
                                        $metaDataJSON[$i]['text'] = 'skill';
                                    }
                                    /*                                     * ********************************************************************* */

                                    $metaDataKeyCheck = $this->db->getSingleRow(" SELECT mdk.ID,mdk.MetaDataName FROM MetaDataKeys mdk
                          inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
                          WHERE mdk.MetaDataName = '{$metaDataJSON[$i]['text']}' AND  mdk.isEnabled = '1'");

                                    $metaDataVal = $metaDataJSON[$i]['text'];
                                    $metaDataKeyValues = $metaDataJSON[$i]['val'];

                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        $metaKeyID = '';
                                        $metaKeyName = '';
                                    } else {
                                        $metaKeyID = $metaDataKeyCheck['ID'];
                                        $metaKeyName = $metaDataKeyCheck['MetaDataName'];
                                    }

                                    // $checkKeyName = $metaDataClass->checkMetadaDataKeyName($metaDataJSON[$i]->text,'' );

                                    $this->input = array(
                                        'pgncp' => '1',
                                        'pgnob' => '-1',
                                        'pgnot' => '-1',
                                        'pgndc' => '-1',
                                        'pgnstart' => '0',
                                        'pgnstop' => '-1',
                                        'rt' => 'metadata/metadata-save',
                                        'metaDataKeyName' => $metaDataJSON[$i]['text'],
                                        'metaDataKeyId' => '',
                                        'metaDataKeyType' => 'text_entry',
                                        'metaDataKeyValues' => '',
                                        'metaDataKeyValueDeletedList' => '',
                                        '' => ''
                                    );

                                    //if( $checkKeyName != true )
                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        //  $MetaDataDetail = $metaDataClass->metadataSaveForPegasus($this->input,$repositoryid,2);
                                        //Site::myDebug('---Before calling the function metadataSaveForPegasus in metedata KEY not EXISTS, the input array ----- ');
                                        //Site::myDebug($this->input);
                                        //$MetaDataDetail = $metaDataClass->metadataSaveForPegasus($this->input, $repositoryid, 3);
                                        $MetaDataDetail = $metaDataClass->metadataSaveForExamView($this->input, $repositoryid, 3);
                                        $ID = $MetaDataDetail['ID'];

                                        if ($ID) {
                                            $keyName = $MetaDataDetail['KeyName'];
                                            $keyValues = $MetaDataDetail['KeyValues'];
                                            $useCount = $MetaDataDetail['UseCount'];
                                            $status = $MetaDataDetail['Status'];
                                            $modDate = $MetaDataDetail['ModDate'];
                                            $metaDataType = $MetaDataDetail['MetaDataType'];
                                        }
                                    } else {
                                        $ID = $metaKeyID;
                                        $keyName = $metaKeyName;
                                    }

                                    if ($ID != '' && $keyName != '') {
                                        //if the metadata value contains comma, then metadata value will get inserted but it wont show up
                                        //in classification pop up so comma is replaced by space
                                        // if (strstr($metaDataJSON[$i]['val'], ',')) {
                                        //$metaDataJsonVal = str_replace(',', ' ', $metaDataJSON[$i]['val']);
                                        //} else {
                                        $metaDataJsonVal = $metaDataJSON[$i]['val'];
                                        //}
                                        /**                                         * ************ new code *************** */
                                        $arr[$ID . '$$' . $keyName][] = $metaDataJsonVal;

                                        /*                                         * ************* new code *************** */
                                        $str.=$ID . $this->cfgApp->metaDataValSeparator . $keyName . '|||' . $metaDataJsonVal . $this->cfgApp->hashSeparator;
                                    }
                                }
                            }
                            Site::myDebug($str);
                            $string = '';
                            foreach ($arr as $key => $value) {
                                $key_id = '';
                                foreach ($value as $k => $v) {
                                    if (empty($key_id)) {
                                        $key_id = $v;
                                    } else {
                                        $key_id.='&&&' . $v;
                                    }
                                }
                                //$string.=$key.'$$'.$key_name[$key].'|||'.$key_id.'|#|';  
                                $string.=$key . '|||' . $key_id . '|#|';
                            }

                            $assignMetaKVArray = array();
                            $str = $string;

                            $assignMetaKVArray['manualKeysValues'] = $str;

                            $metaDataClass->assignedMetadataForExamView($assignMetaKVArray, $repositoryid, 3);
                        }
                        /* Import meta data end */
                        Site::myDebug('---MapRepositoryQuestionsManage');
                        Site::myDebug($repositoryid);
                        $this->qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                        Site::myDebug('---questionActivityTrack');
                        Site::myDebug($result);
                    }
                }
            }
        }
        unset($this->qtiImportFrom);
        unset($this->qtiBankId);
        unset($this->qtiTaxoId);
    }

    /**
     * import question in QTI2.1 TestBuilder format
     *
     * @access   private     
     */
    function qti1_2AddQsWithExam_TestBuilder($questionItems, $quizID, $sectionId, $qtifile, $itemNodeArray, $qtimgdetail) {
        global $APPCONFIG, $DBCONFIG;
        global $actual_quest_import;


        if (!empty($questionItems)) {
            foreach ($questionItems->{'item'} as $q) {

                set_time_limit(0);
                $qt = '';
                $strxml = $q->asXML();
                $renderfib = strpos($strxml, "render_fib");
                $renderfib_str = "render_fib";


                if ($q->presentation->flow) {

                    /*                     * *********** NEW code **************************** */
                    $mattext = addslashes($q->presentation->flow->material->mattext);
                    $matAudioNode = $q->presentation->flow->material->altmaterial->mataudio;
                    preg_match('/<a[^>]+>(.*?)<\/a>/i', $mattext, $res);

                    if (!empty($res)) {
                        $this->qst->assetList = $qtimgdetail;
                        $mattext = $this->qst->addMediaPlaceHolder($mattext);
                        $q->presentation->flow->material->mattext = $mattext;
                        $this->qst->assetList = '';
                    }


                    if (strpos($mattext, 'img') !== false) {
                        foreach ($qtimgdetail as $imgdt) {
                            $mattext = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $mattext);
                        }
                        $mattext_new = $mattext;
                        $ret_data = $this->qst->addMediaPlaceHolder($mattext_new);
                        $mattext = $ret_data;
                        $q->presentation->flow->material->mattext = $ret_data;
                    }
                    if (!empty($matAudioNode)) {
                        foreach ($qtimgdetail as $imgdt) {
                            $matAudioNode = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $matAudioNode);
                        }
                        $mattext_new = $matAudioNode;
                        $mattext_new = " <img src='{$matAudioNode}' />";
                        $ret_data = $this->qst->addMediaPlaceHolder($mattext_new);
                        $q->presentation->flow->material->mattext = $mattext . $ret_data;
                    }

                    /*                     * *********** NEW code **************************** */

                    $questTitle = addslashes($q->presentation->flow->material->mattext);
                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->flow->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->flow_mat->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->flow_label->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                } else {
                    $questTitle = addslashes($q->presentation->material->mattext);

                    if ($renderfib) {
                        $rcardinality = $this->getAttribute($q->presentation->response_str[0], "rcardinality");
                        $typeIdent = $renderfib_str;
                    } else {
                        $rcardinality = $this->getAttribute($q->presentation->response_lid[0], "rcardinality");
                        $render_type = $q->presentation->response_lid[0];
                        if ($render_type->render_choice) {
                            $response_type = $render_type->render_choice->response_label[0]->material;
                            $typeIdent = $this->qtiRespType($response_type);
                        } else if ($render_type->render_extension) {
                            $response_type = $render_type->render_extension->ims_render_object->response_label->material;
                            $typeIdent = $this->qtiRespType($response_type); // for ordering image,orderingtext vertical
                        }
                    }
                }

                $questType = $q->itemmetadata->qmd_itemtype;



                // if question type true /false and containing true anf false words then it treate as true false template else template type is MCSS
                if (stripos($questType, "true") > -1) {

                    if ($q->presentation->flow) {
                        //$val=$q->presentation->flow->response_lid[0]->render_choice->response_label[0]->flow_mat->material->mattext;
                        $val = $q->presentation->flow->response_lid[0]->render_choice->flow_label->response_label[0]->material->mattext;
                    } else {
                        $val = $q->presentation->response_lid[0]->render_choice->response_label[0]->material->mattext;
                    }
                    if ($questType != '' && $val != '') {
                        $questType = (stripos($val, "true") > -1 || stripos($val, "false") > -1) ? $questType : "Multiple Choice";
                    }
                }
                // end
                //shuffle choice in metadata - start
                /* Site::myDebug("<------shuffle_choice_metadata---->");
                  $shuffle_choices_array = (array) $this->registry->site->objectToArray($q->presentation->flow->response_lid->render_choice);
                  $shuffle_choices_metadata_value = $shuffle_choices_array['@attributes']['shuffle'];
                  $shuffle_choices_metadata['text'] = 'shuffle_choices';
                  $shuffle_choices_metadata['val'] = $shuffle_choices_metadata_value;
                  Site::myDebug($shuffle_choices_metadata);
                  //shuffle choice in metadata - end
                 */
                /* if ($questType == 'Multiple Choice Static') {
                  $questType = 'Multiple Choice';
                  } */


                if (($q->itemmetadata->qmd_itemtype == 'Logical Identifier' || $q->itemmetadata->qmd_itemtype == 'String') && $rcardinality == 'Single') {
                    $questType = 'Multiple Choice';
                    /* For MCSSSelactable image/MCSSSelectable audio start */
                    if ($q->presentation->flow) {
                        $resLabel = $q->presentation->flow->response_lid->render_choice->response_label;

                        $resLabelCount = count($resLabel);

                        for ($r = 0; $r < $resLabelCount; $r++) {
                            Site::myDebug('---rspo1----');
                            Site::myDebug($q->presentation->flow->response_lid->render_choice->response_label[$r]->material->mattext);
                            if (strpos($q->presentation->flow->response_lid->render_choice->response_label[$r]->material->mattext, 'img') > -1) { //Image
                                $typeIdent = 'matimage';
                                Site::myDebug('---rspo3----');
                                Site::myDebug($typeIdent);
                            }
                            if (!empty($q->presentation->flow->response_lid->render_choice->response_label[$r]->material->altmaterial->mataudio)) { //Audio
                                $typeIdent = 'mataudio';
                                Site::myDebug('---mataudio----');
                                Site::myDebug($typeIdent);
                            }
                        }
                    } else {
                        $resLabel = $q->presentation->response_lid->render_choice->response_label;
                        $resLabelCount = count($resLabel);

                        for ($r = 0; $r < $resLabelCount; $r++) {

                            if (strpos($q->presentation->response_lid->render_choice->response_label[$r]->material->mattext, 'img') > -1) { //Image
                                $typeIdent = 'matimage';
                            }
                            if (!empty($q->presentation->response_lid->render_choice->response_label[$r]->material->altmaterial->mataudio)) { //Audio
                                $typeIdent = 'mataudio';
                            }
                        }
                    } /* For MCSSSelactable image/MCSSSelectable audio end  */
                } else if ($q->itemmetadata->qmd_itemtype == 'String' && $rcardinality == '') {
                    $questType = 'Essay';
                }

                /* else if($q->itemmetadata->qmd_itemtype == 'True / False')
                  {
                  $questType = 'True / False';
                  }
                 */
                $qtdetail = $this->getQuadQuestionTypeId($questType, $rcardinality, $typeIdent, $q);

                $qt = $qtdetail['ID'];

                if ($qt != '') {

                    $actual_quest_import = $actual_quest_import + 1;
                    if (($q->itemmetadata->qmd_itemtype == 'Logical Identifier' || $q->itemmetadata->qmd_itemtype == 'String') && $rcardinality == 'Single') {
                        //$qtdetail['TemplateFile'] = 'MCSSMediaExamView';
                        //$qtdetail['TemplateFile'] = 'MCSSMediaTestBuilder';
                        if ($typeIdent == 'matimage') {
                            $qtdetail['TemplateFile'] = 'MCSSSelectableImageTestBuilder';
                        } else if ($typeIdent == 'mataudio') {
                            $qtdetail['TemplateFile'] = 'MCSSSelectableAudioTestBuilder';
                        } else {
                            $qtdetail['TemplateFile'] = 'MCSSMediaTestBuilder';
                        }
                    } else if ($q->itemmetadata->qmd_itemtype == 'String' && $rcardinality == '') { // For Essay
                        $qtdetail['TemplateFile'] = 'EssayTestBuilder';
                    }
                    /* else if ($q->itemmetadata->qmd_itemtype == 'True / False')
                      {
                      $qtdetail['TemplateFile'] = 'TrueFalsePegasus';
                      } */

                    Site::myDebug('---template object-----');
                    Site::myDebug($qtdetail);
                    include_once($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . $qtdetail['TemplateFile'] . ".php");
                    Site::myDebug('---template object-----');
                    Site::myDebug($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePath . $qtdetail['TemplateFile'] . ".php");
                    // echo $this->cfg->rootPath."/".$this->cfgApp->QuestionTemplateResourcePath."/".$qtdetail['TemplateFile'].".php";

                    $template_file_obj = new $qtdetail['TemplateFile']();
                    SITE::myDebug("ranu1====================");
                    SITE::myDebug($q);
                    //$questJSON = $template_file_obj->qti1v2QuestionJson($q, $itemNodeArray, 'ExamTestBuilder');
                    $questJSON = $template_file_obj->qti1v2QuestionJson($q, $itemNodeArray, 'ExamTestBuilder', $qtimgdetail);
                    //Site::myDebug('---template object-----questJSON1------');
                    //Site::myDebug($questJSON);
                    $questMetaData = $template_file_obj->qti1v2QuestionMetaData($q);

                    /* merge shuffle choice & metadata array  - start
                      $new_array_for_shuffle_choices = array();
                      $metadata_arr_to_merge = json_decode($questMetaData, true);
                      $metadata_arr_count = count($metadata_arr_to_merge);
                      $new_array_for_shuffle_choices[$metadata_arr_count] = $shuffle_choices_metadata;
                      $questMetaData_array = array_merge($metadata_arr_to_merge, $new_array_for_shuffle_choices);
                      Site::myDebug('---$questMetaData_array-----');
                      Site::myDebug($questMetaData_array);

                      $questMetaData = json_encode($questMetaData_array);

                      Site::myDebug('---merged metadata with choices-----');
                      Site::myDebug($questMetaData);

                      Site::myDebug($metajson->text);
                      //merge shuffle choice & metadata array  - end */

                    $sUserID = $this->session->getValue('userID');

                    $presentation_data = $this->getAttribute($q, "title");
                    $questTitle = $presentation_data;
                    $questTitle = $this->replaceQuote(stripslashes($this->auth->hashCodeToHtmlEntity($questTitle)));

                    //Site::myDebug('---The questJSON variable =====');
                    //Site::myDebug($questJSON);

                    if (!empty($questJSON)) {

                        $arrQuestion = array(
                            'Title' => $questTitle,
                            'XMLData' => 'NA',
                            'JSONData' => $questJSON,
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt
                        );

                        //Site::myDebug('---Before Question Save in the DB arrQuestion=-----');
                        //Site::myDebug($arrQuestion);
                        Site::myDebug('============== Debugging the Question DETAIL array data ===========');
                        Site::myDebug($arrQuestion);

                        $questid = $this->qst->newQuestSave($arrQuestion);

                        if ($DBCONFIG->dbType == 'Oracle') {
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('IASMTCNT', $quizID));
                        }


                        // QUAD-146:- Ensure that the import feature adds questions directly into the Bank.       
                        if ($this->qtiImportFrom == 'qtiBank') {
                            $qtiEntityTypeID = 1;      // Imported from Bank module.
                        } else {
                            $qtiEntityTypeID = 2;      // Imported from Assesment module.
                        }

                        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                            $questid,
                            $quizID,
                            $qtiEntityTypeID,
                            '0', 'ADDQST', $sUserID,
                            $this->currentDate(), $sUserID,
                            $this->currentDate()), 'details');
                        // $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');

                        $repositoryid = $result['Total_RepositoryID'];


                        /* $classificationClass = new Classification();

                          // QUAD-146:- Ensure that the import feature adds questions directly into the Bank.
                          if($this->qtiImportFrom == 'qtiBank')
                          {
                          $qtiTaxoClassiId = ($this->qtiTaxoId=='NO')?'0': $this->qtiTaxoId;
                          if($qtiTaxoClassiId > 0)
                          {
                          $resParentNode =  $classificationClass->getAllParentTaxonomyNode($qtiTaxoClassiId);
                          if (!empty($resParentNode))
                          {
                          $resParentNode = $qtiTaxoClassiId . ',' . $resParentNode;
                          }
                          else
                          {
                          $resParentNode = $qtiTaxoClassiId;
                          }
                          $parentTaxoIds = explode(",", $resParentNode);
                          foreach ($parentTaxoIds as $taxoKey => $taxoId)
                          {
                          $insetIntoClassQ = "INSERT INTO Classification (EntityID, ClassificationID, ClassificationType, isEnabled, EntityTypeID, UserID, ADDDATE, ModBY, ModDate,isMovedToFolder)
                          VALUES ('" . $repositoryid . "', '" . $taxoId . "', 'Taxonomy', '1', '3', '" . $this->session->getValue('userID') . "', '" . $this->currentDate() . "', '" . $this->session->getValue('userID') . "', '" . $this->currentDate() . "',1)";
                          $this->db->execute($insetIntoClassQ);
                          }
                          }
                          else
                          {
                          $insetIntoClassQ = "INSERT INTO Classification (EntityID, ClassificationID, ClassificationType, isEnabled, EntityTypeID, UserID, ADDDATE, ModBY, ModDate,isMovedToFolder)
                          VALUES ('" . $repositoryid . "', '".$qtiTaxoClassiId."', 'Taxonomy', '1', '3', '" . $this->session->getValue('userID') . "', '" . $this->currentDate() . "', '" . $this->session->getValue('userID') . "', '" . $this->currentDate() . "', '0')";
                          $this->db->execute($insetIntoClassQ);
                          }

                          $bnk = new Bank();
                          $CommonMetadta = $bnk->getCommonMetadataForBank($this->qtiBankId);
                          $maxLength = sizeof($CommonMetadta);
                          for ($i = 0; $i <= $maxLength; $i++) {
                          $MetaDataName = $CommonMetadta[$i]['MetaDataName'];
                          $MetaDataValue = $CommonMetadta[$i]['MetaDataValue'];
                          $MetaDataKeyID = $CommonMetadta[$i]['ID'];
                          if ($MetaDataName != "" && $MetaDataValue != "")
                          {
                          $AddCommonMetaToQuestion = $bnk->addCommonMetadataToQuestion($MetaDataKeyID, $MetaDataValue,$repositoryid);
                          }
                          }

                          } */

                        /* -----------------Import Taxonomies----------------------------------- */

                        /* $retTaxs = array();
                          for ($p = 0; $p < count($questMetaData_array); $p++)
                          {
                          // if ($questMetaData_array[$p]->text != '' && $questMetaData_array[$p]->val != '') {
                          if ($questMetaData_array[$p]['text'] == 'qmd_BloomsID')
                          {
                          switch ($questMetaData_array[$p]['val'])
                          {
                          case '4':
                          $bloomsIDMetadata = 'Knowledge';
                          break;
                          case '5':
                          $bloomsIDMetadata = 'Comprehension';
                          break;
                          case '6':
                          $bloomsIDMetadata = 'Application';
                          break;
                          case '7':
                          $bloomsIDMetadata = 'Analysis';
                          break;
                          case '8':
                          $bloomsIDMetadata = 'Synthesis';
                          break;
                          case '9':
                          $bloomsIDMetadata = 'Evaluation';
                          break;
                          default:
                          $bloomsIDMetadata = '';
                          break;
                          }
                          Site::myDebug('---$bloomsIDMetadata-----');
                          Site::myDebug($bloomsIDMetadata);
                          //check if that taxonomy is already present in the database
                          $taxonomyKeyCheck = $this->db->getSingleRow("SELECT * FROM Taxonomies WHERE Taxonomy= '{$bloomsIDMetadata}' and UserID = '{$this->session->getValue('userID')}' ");
                          if ($taxonomyKeyCheck == NULL)
                          {
                          $taxText = $bloomsIDMetadata;

                          $taxArray = array(
                          'pgncp' => '1',
                          'pgnob' => '-1',
                          'pgnot' => '-1',
                          'pgndc' => '-1',
                          'pgnstart' => '0',
                          'pgnstop' => '10',
                          'rt' => 'classification/manage-taxonomy',
                          'act' => 'ADD',
                          'parentID' => '1',
                          'taxonomyID' => '',
                          'taxonomy' => $taxText,
                          'accessMode' => 'Private',
                          'belowTaxoID' => '',
                          '' => ''
                          );
                          $retTaxs[] = $classificationClass->manageTaxonomy($taxArray);
                          }
                          else
                          {
                          $retTaxs[] = $taxonomyKeyCheck['ID'];
                          }
                          }
                          //$retTaxs = implode(',', $retTaxs);
                          // $classificationClass->manageClassification($repositoryid, 3, '', $retTaxs);
                          //$newJsonData = $objJSONtmp->decode($quest['JsonData']);
                          //unset($newJsonData->taxonomy);
                          } */
                        /* -----------------Import Taxonomies End----------------------------------- */
                        /* Import Meta data start */

                        $str = '';
                        if ($questMetaData != '') {
                            //Site::myDebug('---Inside Import Meta data start-----');
                            //Site::myDebug('---$metaDataKeyValuesdee1-----');
                            //Site::myDebug($questMetaData);
                            $metaDataJSON = json_decode($questMetaData, true);
                            /*
                              questMetaData ==[
                              {"text":"skill","val":"01_03"},
                              {"text":"skill","val":"01_04"},
                              {"text":"standard","val":"CO|CO.ES.GLE.7.2.IQ.1"},
                              {"text":"standard","val":"CO|CO.ES.GLE.7.2.d"},
                              {"text":"standard","val":"NJ|01_01"},
                              {"text":"difficulty","val":"easy"},
                              {"text":"qmd_itemtype","val":"String"}]
                             */
                            Site::myDebug($metaDataJSON);
                            $metaDataClass = new Metadata();
                            $str = '';
                            $arr = '';

                            for ($i = 0; $i < count($metaDataJSON); $i++) {

                                if ($metaDataJSON[$i]['text'] != '' && $metaDataJSON[$i]['val'] != '') {

                                    // Added htmlentites for html entities 
                                    $metaDataJSON[$i]['val'] = htmlentities($metaDataJSON[$i]['val'], ENT_QUOTES, "UTF-8");
                                    //Site::myDebug('--htmlentities----');
                                    //Site::myDebug($metaDataJSON[$i]['val']);

                                    $metaDataKeyCheck = $this->db->getSingleRow(" SELECT mdk.ID,mdk.MetaDataName FROM MetaDataKeys mdk
									inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
									WHERE mdk.MetaDataName = '{$metaDataJSON[$i]['text']}' AND  mdk.isEnabled = '1'");

                                    $metaDataVal = $metaDataJSON[$i]['text'];
                                    $metaDataKeyValues = $metaDataJSON[$i]['val'];

                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        $metaKeyID = '';
                                        $metaKeyName = '';
                                    } else {
                                        $metaKeyID = $metaDataKeyCheck['ID'];
                                        $metaKeyName = $metaDataKeyCheck['MetaDataName'];
                                    }

                                    // $checkKeyName = $metaDataClass->checkMetadaDataKeyName($metaDataJSON[$i]->text,'' );

                                    $this->input = array(
                                        'pgncp' => '1',
                                        'pgnob' => '-1',
                                        'pgnot' => '-1',
                                        'pgndc' => '-1',
                                        'pgnstart' => '0',
                                        'pgnstop' => '-1',
                                        'rt' => 'metadata/metadata-save',
                                        'metaDataKeyName' => $metaDataJSON[$i]['text'],
                                        'metaDataKeyId' => '',
                                        'metaDataKeyType' => 'text_entry',
                                        'metaDataKeyValues' => '',
                                        'metaDataKeyValueDeletedList' => '',
                                        '' => ''
                                    );

                                    //if( $checkKeyName != true )
                                    if ($metaDataKeyCheck == NULL || $metaDataKeyCheck == '') {
                                        //  $MetaDataDetail = $metaDataClass->metadataSaveForPegasus($this->input,$repositoryid,2);
                                        //Site::myDebug('---Before calling the function metadataSaveForPegasus in metedata KEY not EXISTS, the input array ----- ');
                                        //Site::myDebug($this->input);
                                        //$MetaDataDetail = $metaDataClass->metadataSaveForPegasus($this->input, $repositoryid, 3);
                                        $MetaDataDetail = $metaDataClass->metadataSaveForTestBuilder($this->input, $repositoryid, 3);
                                        $ID = $MetaDataDetail['ID'];

                                        if ($ID) {
                                            $keyName = $MetaDataDetail['KeyName'];
                                            $keyValues = $MetaDataDetail['KeyValues'];
                                            $useCount = $MetaDataDetail['UseCount'];
                                            $status = $MetaDataDetail['Status'];
                                            $modDate = $MetaDataDetail['ModDate'];
                                            $metaDataType = $MetaDataDetail['MetaDataType'];
                                        }
                                    } else {
                                        $ID = $metaKeyID;
                                        $keyName = $metaKeyName;
                                    }

                                    if ($ID != '' && $keyName != '') {
                                        //if the metadata value contains comma, then metadata value will get inserted but it wont show up
                                        //in classification pop up so comma is replaced by space
                                        // if (strstr($metaDataJSON[$i]['val'], ',')) {
                                        //$metaDataJsonVal = str_replace(',', ' ', $metaDataJSON[$i]['val']);
                                        //} else {
                                        $metaDataJsonVal = $metaDataJSON[$i]['val'];
                                        //}
                                        /*                                         * ************* new code *************** */

                                        $arr[$ID . '$$' . $keyName][] = $metaDataJsonVal;

                                        /*                                         * ************* new code *************** */
                                        $str.=$ID . $this->cfgApp->metaDataValSeparator . $keyName . '|||' . $metaDataJsonVal . $this->cfgApp->hashSeparator;
                                    }
                                }
                            }
                            //Site::myDebug('---str-----');
                            //Site::myDebug('---arr-----');
                            //Site::myDebug(json_encode($arr));							
                            Site::myDebug($str);

                            $string = '';
                            foreach ($arr as $key => $value) {
                                $key_id = '';
                                foreach ($value as $k => $v) {
                                    if (empty($key_id)) {
                                        $key_id = $v;
                                    } else {
                                        $key_id.='&&&' . $v;
                                    }
                                }
                                //$string.=$key.'$$'.$key_name[$key].'|||'.$key_id.'|#|';  
                                $string.=$key . '|||' . $key_id . '|#|';
                            }
                            Site::myDebug('==============>>>>>>>>' . $string);

                            $assignMetaKVArray = array();
                            $str = $string;
                            /* "43$$skill|||01_03&&&01_04|#|22$$standard|||&&&CO|CO.ES.GLE.7.2.d|#|22$$standard|||NJ|01_01|#|23$$difficulty|||easy|#|24$$qmd_itemtype|||String|#|"; */

                            $assignMetaKVArray['manualKeysValues'] = $str;
                            //$metaDataClass->assignedMetadataForPegasus($assignMetaKVArray, $repositoryid, 3);
                            $metaDataClass->assignedMetadataForTestBuilder($assignMetaKVArray, $repositoryid, 3);
                        }
                        /* Import meta data end */
                        //Site::myDebug('---MapRepositoryQuestionsManage');
                        //Site::myDebug($repositoryid);
                        $this->qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                        //Site::myDebug('---questionActivityTrack');
                        //Site::myDebug($result);
                    }
                }
                //die;
            }
        }

        unset($this->qtiImportFrom);
        unset($this->qtiBankId);
        unset($this->qtiTaxoId);
    }

    /**
     * get difficulty in xml file for question  which is in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $q
     * @param    string $txtq
     * @return   string
     *
     */
    function qti1v2Difficulty($q, $txt) {
        $difficulty = '';
        if ($q->itemmetadata->qmd_levelofdifficulty && $q->itemmetadata->qmd_levelofdifficulty != '') {
            $difficulty = $q->itemmetadata->qmd_levelofdifficulty;
        } elseif ($q->itemmetadata->qtimetadata) {
            $qti_meta_data = $q->itemmetadata->qtimetadata;
            if (!empty($qti_meta_data)) {
                foreach ($qti_meta_data as $dt) {
                    $mtdat = $dt->qtimetadatafield;
                    if (!empty($mtdat)) {
                        foreach ($mtdat as $mtdat1) {
                            if ($mtdat1->fieldlabel == $txt) {
                                return $mtdat1->fieldentry;
                            }
                        }
                    }
                }
            }
        }
        return $difficulty;
    }

    /**
     * get question type id of question which is in QTI format
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $questType
     * @param    string $rcardinality
     * @param    string $typeIdent
     * @param    string $q
     * @return   integer
     *
     */
    function getQuadQuestionTypeId($questType, $rcardinality, $typeIdent, $q) {
        //        echo $questType."<br/>"; 
        //        echo $rcardinality."<br/>";
        //       echo $typeIdent."<br/>";
        // String
        // Essay
        // render_fib
        // print_r($q);
        // print "*****************";
        // die;
        global $DBCONFIG;

        $prestation_data = $this->qtiQuestionTitle($q);


        $instID = $this->session->getValue('instID');


        if ($DBCONFIG->dbType == 'Oracle') {
            $innertab = " INNER JOIN MapClientQuestionTemplates mqt ON mqt.\"isEnabled\" = '1' AND mqt.\"isActive\" = 'Y' and mqt.\"ClientID\" = {$instID}";
            $innertabcond = " qt.\"ID\" = mqt.\"QuestionTemplateID\" ";
            $displayfld = " mqt.\"ID\", qt.\"TemplateFile\", qt.\"TemplateCategoryID\" ";
        } else {
            $innertab = " INNER JOIN MapClientQuestionTemplates mqt ON mqt.isEnabled = '1' AND mqt.isActive = 'Y' and mqt.ClientID = {$instID}";
            $innertabcond = " qt.ID = mqt.QuestionTemplateID ";
            $displayfld = " mqt.ID, qt.TemplateFile, qt.TemplateCategoryID";
        }

        //for static condition // essay is not coming in this section
        if (!($q->presentation->flow->response_lid[0]) && !($q->presentation->flow->render_choice) && !($q->presentation->response_lid[0]) && !($q->presentation->render_choice) && !($q->presentation->response_str || $q->presentation->flow->response_str)) {

            if ($DBCONFIG->dbType == 'Oracle') {
                if ($prestation_data['media_img']) {
                    $cond = " qt.\"isStatic\" ='Y' and  qt.\"ID\" = 40 and qt.\"isImport\" ='Y' and {$innertabcond}";
                    $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                } elseif ($prestation_data['media_video']) {
                    $cond = " qt.\"isStatic\" ='Y' and  qt.\"ID\" = 43 and qt.\"isImport\" ='Y' and {$innertabcond}";
                    $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                } else {
                    $cond = "qt.\"isStatic\" ='Y' and qt.\"ID\" = 39 and qt.\"isImport\" ='Y' and {$innertabcond}";
                    $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                }
            } else {

                if ($prestation_data['media_img']) {
                    $cond = " qt.isStatic ='Y' and  qt.ID = 40 and qt.isImport='Y' and {$innertabcond}";
                    $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                } elseif ($prestation_data['media_video']) {
                    $cond = " qt.isStatic ='Y' and  qt.ID = 43 and qt.isImport='Y' and {$innertabcond}";
                    $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                } else {
                    if ($prestation_data['title'] == 'Standard FIB string Item') {
                        $cond = ($prestation_data['media_img']) ? " qt.ID= 17  and qt.isImport='Y' and qt.QTITypeIdent= 'render_fib' and {$innertabcond}" : " qt.ID= 17 and qt.isImport='Y' and qt.QTITypeIdent= 'render_fib' and {$innertabcond}";
                        $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                    } else {
                        $cond = "qt.isStatic ='Y' and qt.ID = 39 and qt.isImport='Y' and {$innertabcond}";
                        $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
                    }
                }
            }
            $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} and {$innertabcond}";
            //echo '***********--------------------**********<br>';
            $data = $this->db->getRows($query);
            return $data[0];
        }

//        print "<pre>";
//        print_r($prestation_data);
//        die;
        //check for mcss with image , mcss with video ,msms with imgs ,mcms with video // essay is not coming in this section
        if ($prestation_data['media_img'] || $prestation_data['media_video']) {
            if (($q->presentation->flow->response_lid[0]) || ($q->presentation->flow->response_lid[0]->render_choice)) {
                $rcardinality = $this->getAttribute($q->presentation->flow[0]->response_lid[0], "rcardinality");
            } elseif (($q->presentation->response_lid[0]) || ($q->presentation->response_lid[0]->render_choice)) {
                $rcardinality = $this->getAttribute($q->presentation->response_lid[0], "rcardinality");
            }

            if ($DBCONFIG->dbType == 'Oracle') {
                if (stripos($rcardinality, "Single") > -1) {
                    $cond = ($prestation_data['media_img']) ? " qt.\"ID\" = 6  and qt.\"isImport\" ='Y' and qt.\"QTITypeIdent\" = '{$typeIdent}' and {$innertabcond}" : " qt.\"ID\" = 8 and qt.\"isImport\" ='Y' and qt.\"QTITypeIdent\" = '{$typeIdent}' and {$innertabcond}";
                } else if (stripos($rcardinality, "Multiple") > -1) {
                    $cond = ($prestation_data['media_img']) ? " qt.\"ID\" = 7  and qt.\"isImport\" ='Y' and qt.\"QTITypeIdent\" = '{$typeIdent}' and {$innertabcond}" : " qt.\"ID\" = 9 and qt.\"isImport\" ='Y' and qt.\"QTITypeIdent\" = '{$typeIdent}' and {$innertabcond}";
                }
            } else {
                if (stripos($rcardinality, "Single") > -1) {
                    //$cond   = ($prestation_data['media_img']) ? " qt.ID= 6  and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}" : " qt.ID= 8 and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}"  ;
                    $cond = ($prestation_data['media_img']) ? " qt.ID= 2  and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}" : " qt.ID= 2 and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}";
                } else if (stripos($rcardinality, "Multiple") > -1) {
                    //$cond   = ($prestation_data['media_img']) ? " qt.ID= 7  and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}" : " qt.ID= 9 and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}"  ;
                    $cond = ($prestation_data['media_img']) ? " qt.ID= 2  and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}" : " qt.ID= 2 and qt.isImport='Y' and qt.QTITypeIdent= '{$typeIdent}' and {$innertabcond}";
                }
            }


            $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
            $data = $this->db->getRows($query);
            //print_r($data);
            //die('akhlack');
//            $this->myDebug("rashmi111");
//            $this->myDebug($query);
            return $data[0];
        }


        // Essay is coming in this section
        if ($questType) {

            if ($DBCONFIG->dbType == 'Oracle') {
                if ($rcardinality && $typeIdent) {
                    $cond = " qt.\"QTIQuestType\" = '{$questType}' and qt.\"QTIRcardinality\" = '{$rcardinality}' and qt.\"QTITypeIdent\" = '{$typeIdent}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                } else if ($typeIdent) {
                    $cond = " qt.\"QTIQuestType\" = '{$questType}' and qt.\"QTITypeIdent\" = '{$typeIdent}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                } else if ($rcardinality) {
                    $cond = " qt.\"QTIQuestType\" = '{$questType}' and qt.\"QTIRcardinality\" = '{$rcardinality}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                } else {
                    $cond = " qt.\"QTIQuestType\" = '{$questType}' and {$innertabcond} ";
                }

                //check for Drag and drop
                if ($q->presentation->flow->response_lid[0]->material->matimage || $q->presentation->response_lid[0]->material->matimage) {
                    $cond = " qt.\"ID\" =13 and " . $cond;
                } else {
                    $cond = " qt.\"ID\" != 13 and " . $cond;
                }
            } else {
                if ($rcardinality && $typeIdent) {
                    $cond = " qt.QTIQuestType= '{$questType}' and qt.QTIRcardinality = '{$rcardinality}' and qt.QTITypeIdent= '{$typeIdent}' and qt.isImport='Y' and {$innertabcond} ";
                } else if ($typeIdent) {

                    $cond = " qt.QTIQuestType= '{$questType}' and qt.QTITypeIdent= '{$typeIdent}' and qt.isImport='Y' and {$innertabcond} ";
                } else if ($rcardinality) {

                    $cond = " qt.QTIQuestType= '{$questType}' and qt.QTIRcardinality = '{$rcardinality}' and qt.isImport='Y' and {$innertabcond} ";
                } else {

                    $cond = " qt.QTIQuestType= '{$questType}' and {$innertabcond} ";
                }

                //check for Drag and drop
                if ($q->presentation->flow->response_lid[0]->material->matimage || $q->presentation->response_lid[0]->material->matimage) {

                    $cond = " qt.ID=13 and " . $cond;
                } else {
                    $cond = " qt.ID != 13 and " . $cond;
                }
            }

            $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond}";
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                if ($rcardinality && $typeIdent) {
                    $cond = " qt.\"QTIRcardinality\" = '{$rcardinality}' and qt.\"QTITypeIdent\" ='{$typeIdent}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                } else if ($typeIdent) {
                    $cond = " qt.\"QTITypeIdent\" = '{$typeIdent}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                } else if ($rcardinality) {
                    $cond = " qt.\"QTIRcardinality\" = '{$rcardinality}' and qt.\"isImport\" ='Y' and {$innertabcond} ";
                }
            } else {
                if ($rcardinality && $typeIdent) {
                    // $cond = " qt.QTIRcardinality = '{$rcardinality}' and qt.QTITypeIdent='{$typeIdent}' and qt.isImport='Y' and {$innertabcond} ";
                    $cond = ($prestation_data['media_img']) ? " qt.ID= 2  and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}" : " qt.ID= 2 and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}";
                } else if ($typeIdent) {
                    //$cond = " qt.QTITypeIdent= '{$typeIdent}' and qt.isImport='Y' and {$innertabcond} ";
                    $cond = ($prestation_data['media_img']) ? " qt.ID= 2  and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}" : " qt.ID= 2 and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}";
                } else if ($rcardinality) {
                    //$cond = " qt.QTIRcardinality = '{$rcardinality}' and qt.isImport='Y' and {$innertabcond} ";
                    $cond = ($prestation_data['media_img']) ? " qt.ID= 2  and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}" : " qt.ID= 2 and qt.isImport='Y' and qt.QTITypeIdent= 'mattext' and {$innertabcond}";
                }
            }


            $query = " select {$displayfld} from QuestionTemplates qt {$innertab} where {$cond} ";
            //echo '<br>===============changes new===============<br>';
            //exit;
        }
        $data = $this->db->getRows($query);


        return $data[0];
    }

    /**
     * used to change image name
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $json_data
     * @return   string (json)
     *
     */
    function changeImages($file_data) {
        global $quest_json, $asset_json;
        $data = html_entity_decode(strip_tags($quest_json));
        $objJSONtmp = new Services_JSON();
        $outObj = $objJSONtmp->decode($data);
        $this->myDebug("This is Json Object");
        $this->myDebug($outObj);
        if (!empty($outObj)) {
            foreach ($outObj as $outObj1) {
                $search_name = basename($outObj1->actual_img_name);
                $rplc_name = basename($outObj1->src);
                $file_data = str_replace($search_name, $rplc_name, $file_data);
                $asset_json[$rplc_name] = "{'asset_id':'" . $outObj1->asset_id . "','inst_id':'" . $this->session->getValue('instID') . "','asset_name':'" . $rplc_name . "','asset_type':'" . strtolower($outObj1->imgtype) . "','asset_other':''}";
            }
        }

        return $file_data;
    }

    /**
     *  get import history for an institute
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    aray $input
     * @return   string
     *
     */
    function importedHistory($input = array()) {
        global $DBCONFIG;
        //header('Content-type: text/xml; charset=UTF-8') ;
        $input['pgnot'] = ($input['pgnot'] != "-1") ? $input['pgnot'] : "desc";
        $condition = ($condition != '') ? $condition : '-1';
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : " imp.\"AddDate\" ";
            $arrHistory = $this->db->executeStoreProcedure('ImportHistoryDetails', array($input['EntityTypeID'], $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
        } else {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "imp.AddDate";
            $arrHistory = $this->db->executeStoreProcedure('ImportHistory', array($input['EntityTypeID'], $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
        }
        /*
          if(!empty($arrHistory['RS']))
          {
          $publishedlist = "<assessments>";
          $publishedlist          .= '<historycount>'.$arrHistory['TC'].'</historycount>';
          foreach($arrHistory['RS'] as $history)
          {
          $userName=$history["userFullName"];
          $viewlink = $this->cfg->wwwroot."/assessment/question-list/".$history['EntityID'];
          $downloadUrl =  $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'imports/qti_v_1_2/', 'protocol' => 'http' ) ).$history["FileName"];
          $importVersion = $history["ImportType"] . ' '.$history["ImportVersion"];
          $publishedlist .= "<assessment>
          <importtitle>{$history["Name"]}</importtitle>
          <entityname>{$history["EntityName"]}</entityname>
          <importversion>{$importVersion}</importversion>
          <username>{$userName}</username>
          <date>".date(  "F j, Y, g:i a", strtotime( $history["AddDate"] ) )."</date>
          <viewlink>{$viewlink}</viewlink>
          <viewdownload>{$downloadUrl}</viewdownload>
          <deleteid>{$history["ID"]}</deleteid>
          <totalcount>{$history["QuestionCount"]}</totalcount>
          <totalcountimported>{$history["QuestionCountImported"]}</totalcountimported>
          <summary>{$history["summary"]}</summary>
          <isactive>{$history["isEnabled"]}</isactive>
          </assessment>";
          }
          $publishedlist .= "</assessments>";
          echo $publishedlist;


          die;
          } */

        // Json data
        header('Content-type: application/json; charset=UTF-8');
        $data = $arrHistory['RS'];
        $jsonp = '[';
        if (!empty($data)) {
            $i = 0;
            $cnt = sizeof($data);
            foreach ($data as $key => $value) {
                $i ++;
                $srno = $key + 1;
                $date = date('M d,Y', strtotime($value['AddDate']));
                $viewlink = $this->cfg->wwwroot . "/assessment/question-list/" . $value['EntityID'];

                /*                 * ********* new code*************** */

                if ($value['ImportType'] == "qti") {
                    $subDirPath = 'imports/qti/';
                } else if ($value['ImportType'] == "QTI For Pegasus") {
                    $subDirPath = 'imports/qtipegasus/';
                } else if ($value['ImportType'] == "MOODLE") {
                    $subDirPath = 'imports/moodle/';
                } else if ($value['ImportType'] == "QTI 1.2") {
                    $subDirPath = 'imports/qti_v_1_2/';
                } else if ($value['ImportType'] == "QTI1.2 Exam View") {
                    $subDirPath = 'imports/qti_v_1_2_examView/';
                } else if ($value['ImportType'] == "QTI1.2 Test Builder") {
                    $subDirPath = 'imports/testbuilder_qti1_2/';
                } else {
                    $subDirPath = 'imports/qti_v_1_2/';
                }


                /*                 * ********* new code*************** */
                $downloadUrl = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $subDirPath, 'protocol' => 'http')) . $value["FileName"];

                if ($this->registry->site->cfg->S3bucket) {
                    $downloadUrl = str_replace($this->registry->site->cfg->wwwroot . '/', "", $downloadUrl);
                    $downloadUrl = str_replace($this->registry->site->cfg->wwwroot . '/', "", $downloadUrl);
                    $downloadUrl = s3uploader::getCloudFrontURL($downloadUrl);
                }

                $jsonp .=
                        "{
					\"item\":{
						\"importtitle\":\"" . htmlentities($value['Name']) . "\",
						\"entityname\":\"" . htmlentities($value['EntityName']) . "\",
						\"importtypee\":\"" . htmlentities($value['ImportType']) . "\",
						\"totalquest\":\"" . htmlentities($value['QuestionCount']) . "\",
						\"totalquestimported\":\"" . htmlentities($value['QuestionCountImported']) . "\",
						\"importedby\":\"" . htmlentities($value['userFullName']) . "\",
						\"date\":\"{$date}\",
						\"id\":\"" . $value['ID'] . "\",
						\"viewlink\":\"" . $viewlink . "\",
						\"viewdownload\":\"" . $downloadUrl . "\",
						\"deleteid\":\"" . $value['ID'] . "\"
					}
				}";
                if ($i < $cnt) {
                    $jsonp .= ',';
                }
            }
        }
        $jsonp .= ']';
        $jsonpresponse = "{\"results\":{$jsonp}, \"count\":{$cnt}}";
        echo $jsonpresponse;

        die;
    }

    /**
     *  rename title of imported Entity
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    integer $pubid
     * @param    string $publishedname
     * @return   stirng
     *
     */
    function renameImportEntity($pubid, $publishedname) {
        global $DBCONFIG;

        $query = "SELECT * FROM ImportHistory WHERE ID= '$pubid' ";
        $temppublish = $this->db->getSingleRow($query);
        if (!empty($temppublish)) {  //and isEnabled = '1'  $this->db->getCount($query) > 0
            $publishedname = addslashes($publishedname);
            if ($DBCONFIG->dbType == 'Oracle') {
                $sqlUpdate = "UPDATE ImportHistory SET \"ImportTitle\" = '{$publishedname}' WHERE \"ID\" ='$pubid'  ";
            } else {
                $sqlUpdate = "UPDATE ImportHistory SET ImportTitle = '{$publishedname}' WHERE ID='$pubid'  ";
            }

            $this->db->execute($sqlUpdate);
            return 'Import title has been renamed.';
        } else
            return 'No such import exist.';
    }

    /**
     *  delete import history
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    integer $pubid
     * @param    string $publishedname
     * @return   stirng
     *
     */
    function delImportHist($pubid) {
        global $DBCONFIG;
        
        if ($DBCONFIG->dbType == 'Oracle') {
            $updateQry = "UPDATE  ImportHistory SET \"isEnabled\" = '0' WHERE ID = '$pubid' ";
        } else {
           $updateQry = "UPDATE  ImportHistory SET isEnabled = '0' WHERE ID IN ( $pubid ) ";
        }
        $this->db->execute($updateQry);
        return true;
        //return 'You have deleted Import History';
       /* if ($DBCONFIG->dbType == 'Oracle') {
            $query = "SELECT * FROM ImportHistory WHERE ID = '$pubid' and \"isEnabled\" = '1' ";
        } else {
            $query = "SELECT * FROM ImportHistory WHERE ID = '$pubid' and isEnabled = '1'";
        }

        $temppublish = $this->db->getSingleRow($query);
        if (!empty($temppublish)) {
            if ($DBCONFIG->dbType == 'Oracle') {
                $updateQry = "UPDATE  ImportHistory SET \"isEnabled\" = '0' WHERE ID = '$pubid' ";
            } else {
                $updateQry = "UPDATE  ImportHistory SET isEnabled = '0' WHERE ID='$pubid' ";
            }

            $this->db->execute($updateQry);
            return 'You have deleted Import History';
        } else {
            return 'No such published';
        }*/
    }

    /**
     * This function will add import details
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @return   array   $data       import information
     *
     */
    function addImportDetails($data) {
        $boolData = $this->db->insert('ImportHistory', $data);
    }

    function uploadAssesment() {
        //C:/Users/manish.kumar/Desktop/381.zip
        //$target_path_zip='E:/xampp/htdocs/quad_next/project/assets/sample_assets/374.zip';
        //$target_path_zip='C:/Users/manish.kumar/Desktop/383.zip';
        // $target_path_zip='C:/Users/manish.kumar/Desktop/import Package/393.zip';
        //$target_path_zip='C:/Users/manish.kumar/Downloads/303.zip';
        // $target_path_zip='C:/Users/manish.kumar/Downloads/404.zip'; // MCMS import
        $target_path_zip = 'C:/Users/manish.kumar/Downloads/403.zip'; // essay import


        $out_data = $this->createAssessmentAndQuestion($target_path_zip);
        echo '<pre>';
        print_R($out_data);
        echo '</pre>';
        die;
    }

    function manageImage($q, $qtifile) {
        Site::myDebug('------ Inside manageImage function  ---------');
        Site::myDebug($q);

        Site::myDebug("============================================================");
        Site::myDebug("============================================================");

        Site::myDebug($qtifile);
        $temp_path_root_src = $qtifile . "/";
        if (is_dir($temp_path_root_src)) {
            $tar_path_web = $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/images/original/';
            /* 					E:\xampplite\htdocs\PAI02_QTI_1.2_export\project/data/persistent/institutes/25/imports/qti_v_1_2_examView/qti_v_1_2_examView5314765360549/QIZ_0_M/my_files/org0/images
             */
            Site::myDebug('-----tarpathweb');
            Site::myDebug($tar_path_web);
            /*
              http://localhost/PAI02_QTI_1.2_export/project/data/persistent/institutes/25/assets/images/original/
             */
            $temp_path_root_dest = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/images/original/';
            if ($q->presentation->flow) {
                $questText = trim(addslashes($q->presentation->flow->material->mattext));
                $q->presentation->flow->material->mattext = preg_replace("/(<img)([^>]*)(src=\\?'|\")([^>]*)(\/[^>]*)(\.png|jpg|jpeg|gif)/i", "$1$2$3" . $tar_path_web . "$5$6", $questText);
            } else {
                $questText = trim(addslashes($q->presentation->material->mattext));
                $q->presentation->material->mattext = preg_replace("/(<img)([^>]*)(src=\\?'|\")([^>]*)(\/[^>]*)(\.png|jpg|jpeg|gif)/i", "$1$2$3" . $tar_path_web . "$5$6", $questText);
            }

            if ($q->presentation->flow) {
                $qTitle = $q->presentation->flow->material->mattext;
                if ($q->presentation->flow->response_lid->render_choice->flow_label) {
                    $options = $q->presentation->flow->response_lid->render_choice->flow_label;
                } else {
                    $options = $q->presentation->flow->response_lid->render_choice;
                }
            } else {
                $qTitle = $q->presentation->material->mattext;
                $options = $q->presentation->response_lid->render_choice;
            }

            foreach ($options->response_label as $opt) {
                $val2 = ($opt->flow_mat) ? $opt->flow_mat->material->mattext : $opt->material->mattext;
                if ($opt->flow_mat) {
                    $opt->flow_mat->material->mattext = preg_replace("/(<img)([^>]*)(src=\\?'|\")([^>]*)(\/[^>]*)(\.png|jpg|jpeg|gif)/i", "$1$2$3" . $tar_path_web . "$5$6", $val2);
                } else {
                    $opt->material->mattext = preg_replace("/(<img)([^>]*)(src=\\?'|\")([^>]*)(\/[^>]*)(\.png|jpg|jpeg|gif)/i", "$1$2$3" . $tar_path_web . "$5$6", $val2);
                }
            }

            $this->copyImages($temp_path_root_src, $temp_path_root_dest);


            $guid = uniqid("media");
            $thumbMediaName = "thumb_" . $guid . '.jpg';
            $thumbMediaPathWithImage = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . 'assets/images/thumb/' . $thumbMediaName;
            Site::myDebug('-----$thumbMediaPathWithImage');
            Site::myDebug($thumbMediaPathWithImage);
            //resize the image - start					
            $imageobj = new SimpleImage();
            $imageobj->load($imgImportPath);
            $imageobj->resizeToHeight(100);
            $imageobj->resizeToWidth(100);
            $imageobj->save($thumbMediaPathWithImage);
            //resize the image - end

            copy($imgImportPath, $temp_path_root_dest_with_media);



            $cnt = preg_match_all("/<img[^>]*src=\\?'|\"[^>]*\/[^>]*\.png|jpg/i", $str, $matches1);
            $matches = $matches1[0];
            foreach ($matches as $match) {
                $img_name = basename($match);
                $info = array(
                    'Title' => substr($img_name, 0, strrpos($img_name, '.')),
                    'Keywords' => "",
                    'ContentType' => "Image",
                    'ContentInfo' => $img_name,
                    'UserID' => $this->session->getValue('userID'),
                    'AddDate' => $this->currentDate(),
                    'ModBY' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'FileName' => $img_name,
                    'isEnabled' => '1',
                    // 'ContentHeight' => $outObj1->imgheight,
                    // 'ContentWidth' => $outObj1->imgwidth,
                    // 'ContentSize' => $outObj1->imgsize,
                    // 'Thumbnail' => $thumb_file,
                    // 'Duration' => $duration,
                    'Count' => '1',
                    'OriginalFileName' => $img_name
                );
                $media = new Media();
                $media->add($info);
            }
        }
        return $q;
    }

    function copyImages($src_file, $dest_file) {
        $src = @opendir($src_file);
        $dest = @opendir($dest_file);
        while ($readFile = @readdir($src)) {
            //echo $readFile."<br>"; 
            if ($readFile != '.' && $readFile != '..') {
                if (@copy($src_file . $readFile, $dest_file . $readFile)) {
                    // Site::myDebug( "Copy file");
                } else {
                    // Site::myDebug( " not Copy file");
                }
            }
        }
    }

    function specialCharCheck($string) {
        $string = str_replace(' ', '_', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
        //return preg_replace('/_+/', '_', $string); // Replaces multiple hyphens with single one.
    }

    function importedHistoryNext($input = array()) {
        global $DBCONFIG;
        //header('Content-type: text/xml; charset=UTF-8') ;
        $input['pgnot'] = ($input['pgnot'] != "-1") ? $input['pgnot'] : "desc";
        $condition = ($condition != '') ? $condition : '-1';
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : " imp.\"AddDate\" ";
            $arrHistory = $this->db->executeStoreProcedure('ImportHistoryDetails', array($input['EntityTypeID'], $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
        } else {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "imp.AddDate";
            $arrHistory = $this->db->executeStoreProcedure('ImportHistory', array($input['EntityTypeID'], $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
        }
        return $arrHistory;
    }

    // Function to get New JSON Data format from Old JSON data
    // @input: $qtdetail(ARRAY)->Question Details
    // @input: $questJSON(JSON)->Old Question Json
    // @output:  $newQuestStruct(JSON)
    function getNewQstJsonStruct($qtdetail, $questJSON) {
        Site::myDebug("--------------getNewQstJsonStruct");
        $questionTemplateDetails = $this->qsttemplate->getTemplateCatDetById($qtdetail['TemplateCategoryID']);
        $templateName = (strtolower($questionTemplateDetails['CategoryCode']) == 'mcq' || strtolower($questionTemplateDetails['CategoryCode']) == 'mcms') ? 'mcss' : strtolower($questionTemplateDetails['CategoryCode']);
        $newTemplateStructureTxt = file_get_contents(JSPATH . '/authoring/' . $templateName . "/" . $templateName . ".js");     
        $newTemplateStructureJson = str_replace(";", "", str_replace("var ts =", "", $newTemplateStructureTxt));
        $newTemplateStructArr = json_decode($newTemplateStructureJson, true);
        if (strtolower($questionTemplateDetails['CategoryCode']) == "mcms") {
            $includeFileName = "mcms";
        } else {
            $includeFileName = $templateName;
        }
        include_once($this->registry->site->cfg->rootPath . "/" . $this->registry->site->cfgApp->QuestionTemplateResourcePathAdv . $includeFileName . ".php");
        //$templateClassName = $qtdetail['TemplateFile']."Adv";
        $templateClassName = $qtdetail['TemplateFile'];
        $templateFileObj = new $templateClassName();
        $oldQuestArr = json_decode($questJSON, true);
        $newQuestStruct = $templateFileObj->convertOldtoNewJSON($newTemplateStructArr, $oldQuestArr);
        return $newQuestStruct;
    }

    function getNewQst() {
        //MCSS
        $mcss = array("js" => "mcss", "template" => "mcss", "templateClassName" => "MCSSTextAdv", "jsonstruct" => '{"question_title":"Question&nbsp;Title&nbsp;123<br/>","question_text":[{"val1":"Question&nbsp;Stem&nbsp;123<br/>"}],"instruction_text":[{"val1":"123<br/>"}],"choices":[{"val1":false,"val2":"ch1<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch2<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch3<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch4<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0}],"correct_feedback":[{"val1":"Global&nbsp;Correct&nbsp;Feedback1<br/>"}],"incorrect_feedback":[{"val1":"Global&nbsp;Incorrect&nbsp;Feedback1<br/>"}],"hint":[{"val1":"hint&nbsp;11<br/>"}],"notes_editor":[{"val1":"notes&nbsp;editor&nbsp;11<br/>"}]}');

        //MCMS
        $mcms = array("js" => "mcss", "template" => "mcms", "templateClassName" => "MCMSTextAdv", "jsonstruct" => '{"question_title":"Question&nbsp;Title&nbsp;123<br/>","question_text":[{"val1":"Question&nbsp;Stem&nbsp;123<br/>"}],"instruction_text":[{"val1":"123<br/>"}],"choices":[{"val1":false,"val2":"ch1<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch2<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch3<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0},{"val1":false,"val2":"ch4<br/>","val3":"Indivudial&nbsp;Feedback1<br/>","val4":"Score1","val5":0}],"correct_feedback":[{"val1":"Global&nbsp;Correct&nbsp;Feedback1<br/>"}],"incorrect_feedback":[{"val1":"Global&nbsp;Incorrect&nbsp;Feedback1<br/>"}],"hint":[{"val1":"hint&nbsp;11<br/>"}],"notes_editor":[{"val1":"notes&nbsp;editor&nbsp;11<br/>"}]}');

        //ESSAY
        $essay = array("js" => "essay", "template" => "essay", "templateClassName" => "EssayAdv", "jsonstruct" => '{"question_title":"Question&nbsp;Title22<br/>","question_text":[{"val1":"Question&nbsp;Stem22<br/>"}],"instruction_text":[{"val1":"Instruction&nbsp;22<br/>"}],"essay":"Essay&nbsp;This&nbsp;is&nbsp;an&nbsp;lengthy&nbsp;essay.&nbsp;Essay&nbsp;content&nbsp;is&nbsp;here<br/>","correct_feedback":[{"val1":"Global&nbsp;Correct&nbsp;Feedback111<br/>"}],"incorrect_feedback":[{"val1":"Global&nbsp;Incorrect&nbsp;Feedback111<br/>"}],"hint":[{"val1":"Hint&nbsp;Essay<br/>"}],"notes_editor":[{"val1":"Notes&nbsp;Essay<br/>"}]}');

        //FIB
        $fib = array("js" => "fib", "template" => "fib", "templateClassName" => "FIBAdv", "jsonstruct" => '{"question_text":[{"val1":"Question&nbsp;Text11"}],"question_title":"Question&nbsp;Title22","instruction_text":[{"val1":"Instruction&nbsp;Text33"}],"textwith_blanks":"Text&nbsp;With&nbsp;Blanks44","choices":[{"val1":"[[1]]","val2":"right","val4":"11","val5":"22","val6":"sp","val7":"alpha","val8":"2"},{"val1":"[[2]]","val2":"right","val4":"22","val5":"433","val6":"sp","val7":"alpha","val8":"2"}],"correct_feedback":[{"val1":"Correct&nbsp;Feedback1"}],"incorrect_feedback":[{"val1":"Incorrect&nbsp;Feedback2"}],"partialcorrect_feedback":[{"val1":"Partial&nbsp;Correct&nbsp;Feedback3"}]}');

        ///////===========================================================================
        //Set the template here
        $opt = $fib;
        ///////===========================================================================


        $newTemplateStructureTxt = file_get_contents(JSPATH . '/authoring/' . $opt['js'] . '/' . $opt['js'] . '.js');
        $newTemplateStructureJson = str_replace(";", "", str_replace("var ts =", "", $newTemplateStructureTxt));
        $newTemplateStructArr = json_decode($newTemplateStructureJson, true);
        include($this->cfg->rootPath . "/" . $this->cfgApp->QuestionTemplateResourcePathAdv . $opt['template'] . ".php");
        $templateClassName = $opt['templateClassName'];
        $templateFileObj = new $templateClassName();

        $questOldJson = $opt['jsonstruct'];
        $oldQuestArr = json_decode($questOldJson, true);
        $newQuestArr = $templateFileObj->convertOldtoNewJSON($newTemplateStructArr, $oldQuestArr);

        echo "<table border='1' align='center' width='100%'>";
        echo "<tr><th width='50%'>INPUT " . strtoupper($opt['template']);
        echo "</td><th width='50%'>OUTPUT " . strtoupper($opt['template']);
        echo "</td></tr>";
        echo "<tr><td width='50%' valign='top'>";
        echo '<pre>';
        print_r($oldQuestArr);
        echo '</pre>';
        echo "</td><td width='50%' valign='top'>";
        echo '<pre>';
        print_r(json_decode($newQuestArr, true));
        echo '</pre>';
        echo "</td></tr>";
        echo "</table>";
    }

}

?>