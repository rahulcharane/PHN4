<?php

class Authoring extends Site {

    public $filenames = array();
    public $cssList = array();
    public $versionFile = '';
    public $templateexpandibility;

    /**
     * constructs a new authoring instance
     *
     *
     * @access   public
     * @return   void
     *
     */
    function authoring() {
        parent::Site();
    }

    public function html5Rendition($input) {
        $template_types = array(
            '1' => 'base',
            '2' => 'rapid',
            '3' => 'bug'
        );

        $this->input = $input;

        $action = $this->input['action'];
        $inst_id = $this->session->getValue('instID');
        $qt = new Question();
        $qt_ids = trim($this->input['questids']);
        $qt_ids = explode('||', $qt_ids);
        $qt_ids = $this->removeBlankElements($qt_ids);
        $qt_ids = current($qt_ids);
        $total_ids = count(explode(',', $qt_ids));
        $quid = uniqid();
        $asmtid = $this->input['asmtid'];
        $page = 'publish';
        $platform = '';
        (isset($this->input['platform']) AND !empty($this->input['platform'])) AND $platform = strtolower($this->input['platform']);

        $sql = 'SELECT DISTINCT `q`.`ID`,`q`.`JSONData`,`qt`.`Url`, `qt`.`isStatic`,  `mrq`.`ParentID` FROM `Questions` AS `q`
INNER JOIN `MapRepositoryQuestions` AS `mrq` ON `mrq`.`QuestionID`=`q`.`ID`
INNER JOIN `MapClientQuestionTemplates` AS `mcqt` ON `mcqt`.`ID`=`mrq`.`QuestionTemplateID`
INNER JOIN `QuestionTemplates` AS `qt` ON `qt`.`ID` = `mcqt`.`QuestionTemplateID`
INNER JOIN `TemplateCategories` AS `tc` ON `tc`.`ID` = `qt`.`TemplateCategoryID`
        WHERE `mrq`.`ID` IN(' . $qt_ids . ') ORDER BY `mrq`.`Sequence`, `mrq`.`ParentID` ASC';

        $data = array();
        $meta = array();
        $n_static_pages = 0;
        $index = 1;
        $section = 0;
        $save_section = false;
	if (($questions = $this->db->getRows($sql))) {
	    foreach ($questions as $question) {
                $json = $qt->mmlToImg($qt->removeMediaPlaceHolder($question['JSONData']));
                $json = json_decode($json, true);
                $type = 'base/';
                isset($template_types[$row['TemplateTypeID']]) AND $type = $template_types[$row['TemplateTypeID']] . '/';
                ($type AND ($platform AND $platform == 'mobile')) AND $type = trim($type, '/') . '-mobile/';

		if ($question['ParentID'] != 0) {
		    if ($save_section === false) {
                        $section += 1;
                        $save_section = $question['ParentID'];
		    } else if ($save_section != $question['ParentID']) {
                        $section += 1;
                        $save_section = $question['ParentID'];
                    }
                }
                $is_static = ($question['isStatic'] == 'Y');
                $is_static AND $n_static_pages++;

                $meta[$index] = array(
                    'template' => $type . $question['Url'],
                    'id' => (int) $question['ID'],
                    'static' => ($is_static ? true : false)
                );

                ($section !== 0) AND $meta[$index]['activity'] = (int) $section;

		if (isset($json['metadata'])) {
		    foreach ($json['metadata'] as $item) {
			if (isset($item['text']) && isset($item['val'])) {
                            $meta[$index][strtolower($item['text'])] = $item['val'];
                        }
                    }
                }

		if (isset($meta[$index]['score'])) {
                    $meta[$index]['score'] = intval($meta[$index]['score']);
		} else {
                    $meta[$index]['score'] = 0;
                }
		$meta[$index]['ParentID'] = $question['ParentID'];

                $json['meta'] = $meta[$index];

                $data['pages'][$index] = json_encode($json);
                $index++;
            }
        }

        $AsmtDetail = $this->db->executeStoreProcedure(
                'AssessmentDetails', array(
	    $asmtid, $this->session->getValue('userID'),
	    $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount'
        );

        $asmt = false;
        $setting = false;

	if ($AsmtDetail) {
            $asmt = array_shift($AsmtDetail);
            $setting = array();
	    foreach ($AsmtDetail as $val) {
                $s_key = false;
                isset($val['SettingName']) AND $s_key = $val['SettingName'];
                $s_key AND $setting[$s_key] = $val['SettingValue'];
            }
        }

	if ($asmt) {
            $data['asmt']['asmt'] = $asmt;
        }

	if ($setting) {
            $data['asmt']['setting'] = $setting;
        }

	if ($meta) {
            $data['asmt']['setting']['pages'] = $meta;
        }

        $data['asmt']['setting']['isMultiscreen'] = ($section !== 0) ? true : false;
        $data['asmt']['setting']['totalStaticPages'] = $n_static_pages;
        $data['asmt']['asmt']['questionCount'] = count($meta);

        $key = md5(sha1(serialize($data)));
        $q['instance'] = $instance = substr($key, 0, 6);
        $filename = 'Assessment_' . $this->input['asmtid'] . '_' . $key . '.json';
        $json = json_encode($data);
        $path = $this->cfg->rootPath . '/' . $this->cfgApp->tempDataPath . $inst_id . '/renditions/html5/publish/';
       // echo $path; die;
          Site::myDebug('---');
        Site::myDebug($path);
	if (!file_exists($path . $filename)) {
	    if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

	    if (($handle = @fopen($path . $filename, "w")) !== false) {
                fwrite($handle, $json);
            }

	    if (is_resource($handle)) {
                fclose($handle);
            }
        }

	if (file_exists($path . $filename)) {
            $q['data'] = $filename;
        }

        $q['asmt_id'] = $asmtid;
        $q['client'] = $inst_id;
        $q['s3bk'] = $this->cfg->S3bucket; // Added for S3Bucket purpose

        $type = 'base/';
        isset($template_types[$asmt['TemplateTypeID']]) AND $type = $template_types[$asmt['TemplateTypeID']] . '/';
        ($type AND ($platform AND $platform == 'mobile')) AND $type = trim($type, '/') . '-mobile/';
        $page = $type . $page;

        $q = http_build_query($q);
        $preview_url = $this->cfg->wwwroot . '/assets/renditions/html5/' . $page . '?' . $q;

        $url_path = '/data/persistent/institutes/' . ($platform == 'mobile' ? 'mobile/' : '') . $inst_id . '/renditions/publish/' . $asmtid . '/' . $instance;

	if ($action == 'publishq') {
            $publish_type = ($platform == 'mobile') ? 'Online Mobile' : 'Online';
            $user_id = $this->session->getValue('userID');

            $sql = 'SELECT COUNT(*) AS `num_rows` 
                FROM `PublishAssessments`
                WHERE `UserID`=' . $user_id . ' AND `AssessmentID`=' . $asmtid;

            $data = array(
                'PublishMode' => $asmt['Mode'],
                'PublishType' => $this->input['publishtype'],
                'PublishedTitle' => $asmt['Title'],
                'RenditionType' => 'html5',
                'TotalQuestions' => $total_ids,
                'RandomQuestionCount' => $this->input['randquest'],
                'Url' => $url_path,
                'ModBy' => $this->session->getValue('userID'),
                'ModDate' => $this->currentDate()
            );

            $row = $this->db->getSingleRow($sql);
            $this->myDebug('==== Assessment Detail Row ==== ');
            $this->myDebug($row);
			/*
            if (isset($row['num_rows']) && $row['num_rows'] > 0)
            {
                $where = array(
                    'UserID' => $user_id,
                    'AssessmentID' => $asmtid
                );

                $this->db->update('PublishAssessments', $data, $where);
	      }
            else
            {
                $data['UserID'] = $this->session->getValue('userID');
                $data['AssessmentID'] = $this->input['asmtid'];
                $data['AddDate'] = $this->currentDate();
                $data['isActive'] = 'Y';
                $data['isEnabled'] = 1;
                $data['PublishType'] = 'Online';

                $this->db->insert('PublishAssessments', $data);
            }
			*/
			$data['UserID'] = $this->session->getValue('userID');
			$data['AssessmentID'] = $this->input['asmtid'];
			$data['AddDate'] = $this->currentDate();
			$data['isActive'] = 'Y';
			$data['isEnabled'] = 1;
			$data['PublishType'] = 'Online';
			$this->db->insert('PublishAssessments', $data);
        }
//exit($preview_url);
	$preview_url = urldecode($preview_url);
	$preview_url = str_replace("amp;", "", $preview_url);
	$preview_url = str_replace("amp;", "", $preview_url);
	$preview_url = str_ireplace("TrueFalse", "trueorfalse", $preview_url);
//echo $this->cfg->S3bucket;
        return $preview_url;
    }

    public function questionHtml5Preview($input) {
        $template_types = array(
            '1' => 'base',
            '2' => 'rapid',
            '3' => 'bug'
        );

        $this->input = $input;
        //print_r($input); die;
        $platform = '';
        (isset($this->input['platform']) AND !empty($this->input['platform'])) AND $platform = strtolower($this->input['platform']);

        $instID = $this->session->getValue('instID');
        $q = array();
        $q['client'] = $instID;
        $asmtid = $this->input['entityID'];
        $q['asmt_id'] = $asmtid;
        $id = isset($this->input['QTypeID']) ? $this->input['QTypeID'] : false;
        $sql = 'SELECT DISTINCT `qt`.`Url`,`qt`.`ID`  FROM `QuestionTemplates` AS `qt`
        INNER JOIN `TemplateCategories` AS `qtc` ON `qtc`.`ID`=`qt`.`TemplateCategoryID`
        WHERE  `qt`.`TemplateFile` IS NOT NULL ' . ($id ? 'AND `qt`.`ID` = ' . $id : '');

        $pages = array();
        if (($row = $this->db->getSingleRow($sql))) {
            $type = 'base/';

            isset($template_types[$row['TemplateTypeID']]) AND $type = $template_types[$row['TemplateTypeID']] . '/';
	    ($type AND ($platform AND $platform == 'mobile')) AND $type = trim($type, '/') . '-mobile/';
            $pages[$row['ID']] = $type . $row['Url'];
        }

		//echo $sql;
        //print_r($row);

        $prev_source = $this->input['prevSource'];
        $page = ($id && (isset($pages[$id]) && !empty($pages[$id]))) ? $pages[$id] : false;
        $json = false;
        $preview_url = false;
        $preview_available = false;
        //echo $page;

	if ($page) {
            switch ($prev_source) {
                case 'samplequest':
                    $is_json = true;
                    break;
                case 'editor':
                    $json = $this->input['questJson'];
                    break;
                case 'listing':
                    $qt_id = $this->input['questID'];
                    $qt = new Question();
                    $QuestDetail = $qt->questionData($qt_id);
                    if($row['Url']=='mcss'){
                        $advJSONData = $QuestDetail['advJSONData'];
                        $advJSONData = json_decode($advJSONData, true);
                        if($advJSONData['settings']['question_type'] && $advJSONData['settings']['question_type']=='mcq'){
                            $preview_available = true;
                            $QuestDetail AND $json = $qt->mmlToImg($qt->removeMediaPlaceHolder($QuestDetail['JSONData']));
                        }
                    }else if($row['Url']=='fib/text'){
                        $advJSONData = $QuestDetail['advJSONData'];
                        $advJSONData = json_decode($advJSONData, true);
                        if($advJSONData['templatetype']['text'] && $advJSONData['templatetype']['text']=='textentry'){
                            $preview_available = true;
                            $QuestDetail AND $json = $qt->mmlToImg($qt->removeMediaPlaceHolder($QuestDetail['JSONData']));
                        }
                    }else{
                        $preview_available = true;
                        $QuestDetail AND $json = $qt->mmlToImg($qt->removeMediaPlaceHolder($QuestDetail['JSONData']));
                    }                    
		    break;
                case 'wordtemplate':
                    $qt_id = $this->input['questID'];
		    $sql = 'SELECT `wtq`.`JSONData` FROM `WordTemplateQuestions` AS `wtq` WHERE `wtq`.`ID` = ' . $qt_id . ' AND  `wtq`.`isEnabled` = 1';
                    $row = $this->db->getSingleRow($sql);

		    $row AND $json = (string) $row['JSONData'];
                    break;
                default:
                    break;
            }


	    if($preview_available){
            if ($json) {
                $key = md5(sha1($json));
		$filename = 'Quest_' . $key . '.json';
		$path = $this->cfg->rootPath . '/' . $this->cfgApp->tempDataPath . $q['client'] . '/renditions/html5/preview/';
		/* echo $filename;
					echo "<br>";
					echo $path;
		  die('999'); */
		if (!file_exists($path . $filename)) {
		    if (!is_dir($path))
			mkdir($path, 0777, true);
		    if (($handle = @fopen($path . $filename, "w")) !== false) {
                        fwrite($handle, $json);
                    }
		    if (is_resource($handle)) {
                        fclose($handle);
                    }
                }
		if (file_exists($path . $filename)) {
                    $q['data'] = $filename;
                    $is_json = true;
                }

                $AsmtDetail = $this->db->executeStoreProcedure(
			'AssessmentDetails', array($asmtid, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount'
                );

                $asmt = false;
                $setting = false;
				//echo "<pre>";print_r($AsmtDetail);echo "</pre>";
                if ($AsmtDetail) {
                    $asmt = array_shift($AsmtDetail);
                    $setting = array();
                    foreach ($AsmtDetail as $val) {
                        $s_key = false;
                        isset($val['SettingName']) AND $s_key = $val['SettingName'];
                        $s_key AND $setting[$s_key] = $val['SettingValue'];
                    }

                    if ($asmt) {
                        $data['asmt']['asmt'] = $asmt;
                    }

                    if ($setting) {
                        $data['asmt']['setting'] = $setting;
                    }
                    //echo "data = <pre>";print_r($data);echo "</pre>";
                    $asmt_json = json_encode($data);

                    $key = md5(sha1($asmt_json));
		    $filename = 'Asmt_' . $key . '.json';
		    file_put_contents($path . $filename, $asmt_json);
                    $q['asmt_data'] = $filename;
                }
            }

            if (isset($this->input['tID'])) {

                $q['keystage'] = $this->input['KeyStage'];
                $q['year'] = $this->input['Year'];
                $q['audio'] = $this->input['Audio'];
            }
            //echo "<pre>";print_r($q);echo "</pre>----------";
            $q = http_build_query($q);
			//echo $q;
	    $preview_url = $this->cfg->wwwroot . '/assets/renditions/html5/' . $page . '?' . $q;
	    $preview_url = str_replace("amp;", "", $preview_url);
            }else {
                $preview_url = '';
            }
        } else {
            $preview_url = '';
        }

//        if($prev_source == 'wordtemplate') {
//            return $prev_source;
//        }

	header('Content-type: text/xml; charset=UTF-8');
        $xml = '
        <Response>
            <IsValid>' . ($preview_url ? 'true' : 'false') . '</IsValid>
            <Message></Message>
            <PreviewURL><![CDATA[' . $preview_url . ']]></PreviewURL>
        </Response>';

        return $xml;
    }

    /**
     * a function to create xml for online TN8 preview
     *
     *
     * @access   public
     * @param    array   $input
     * @return   void
     *
     */
    //function wordHtmlPreview($input)
    public function questionTN8Preview($input)
    {		
		if($input['Iscomp'] == 'composite'){
			$chID = '';
			$sqlUpdateSeq = "SELECT QuestionID from MapRepositoryQuestions where ParentID = ".$input['questID'];            
			$chIDres = $this->db->getRows($sqlUpdateSeq);
			foreach ($chIDres as $chID_arr) {
				if($chID == ''){ $chID = $chID_arr[QuestionID]; } 
				else { $chID .= '|$|'.$chID_arr[QuestionID]; }
			}
			$input['questID'] = $chID;
			
		}
		//[questID] => 4531|$|4532	
		//print_r($input);die('----------------');
        $errorMsg=array('400'=>"No item resources found in Zip file",
                        '401'=>"Please provide a ticket in the x-authorization header.",
                        '500'=>"Please try again later. An unexpected error occurred.");
        $errorMsgGet='';
        $export = New Export();
	$findstr = "|$|";
        $pos = strpos($input['questID'], $findstr);
	if($input['prevSource']=="editor") {
	     $data = $export->exportQuestionWithQti2_1_Zip_Json($input);
	}
	else if ($pos === false) {
		    $data = $export->exportQuestionWithQti2_1_Zip($input);
		} else {
			$data = $export->exportQuestionWithQti2_1_CompositeZip($input);
		}


        $TN8 = new TN8Preview();
	$resultData = json_decode($TN8->Tn8ItemShow($data), true);

        if ($resultData['success'] == 'yes') {
             $q['itemid'] = $resultData['msg']['itemId'];
        }

	if ($resultData['error']) {
	    $errorMsgGet = $errorMsg[$resultData['error']];
	}

        $q = http_build_query($q);
        $preview_url = $this->cfg->wwwroot . '/authoring/testnav-preview?' . $q;
        header('Content-type: text/xml; charset=UTF-8');
        $xml = '<Response>
                    <Success><![CDATA[' .  $resultData['success'] . ']]></Success>
                    <Message><![CDATA[' .  $errorMsgGet . ']]></Message>
                    <PreviewURL><![CDATA[' . $preview_url . ']]></PreviewURL>
                </Response>';

        return $xml;
    }

    /**
     * a function to create xml for offline html question preview
     *
     *
     * @access   public
     * @param    array   $input
     * @return   void
     *
     */
    //function wordHtmlPreview($input)
    function questionHtmlPreview($input) {
        global $DBCONFIG;
        try {
            Site::myDebug('-------questionHtmlPreview');
            Site::myDebug($input);

            $QuestTypeID = (string) $input['catID'];
            $qtp = new QuestionTemplate();
            $qt = new Question();
            if ($input['prevSource'] == "wordtemplate") {
                if ($DBCONFIG->dbType == 'Oracle') {
                    if ($input['sLayoutID'] != '') {
                        $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$input['sLayoutID']}'' ", " qt.\"HTMLStructure\" , qt.\"FlashStructure\" , qt.\"TemplateFile\", qt.\"HTMLTemplate\" ", 'details');
                    } else {
                        $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = ''{$QuestTypeID}'' ", " qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" , qt.\"XMLData\" , qt.\"JSONData\" ", 'details');
                    }

                    $sUserID = (string) $input['sUserID'];
                    if ($input['accessToken'] != "" && $input['accessLogID'] != "") {
                        $tokenInfo = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\" = '{$input['accessToken']}' and \"AccessLogID\" = '{$input['accessLogID']}' ");
                        $this->user_info = json_decode($tokenInfo['UserInfo']);
                        $instID = $this->user_info->instId;
                    } else if (strtolower($sUserID) != "admin") {
                        $qry = "SELECT \"ClientID\" FROM MapClientUser mcu WHERE \"UserID\" = {$sUserID} AND mcu.\"isEnabled\" = 1 ";
                        $result = $this->db->getSingleRow($qry);
                        $instID = $result['ClientID'];
                    } else {
                        $instID = 0;
                    }
                } else {
                    if ($input['sLayoutID'] != '') {
                        if ($DBCONFIG->dbType == 'Oracle') {
                            $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$input['sLayoutID']}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" , qt.\"TemplateFile\" ,qt.\"HTMLTemplate\" ", 'details');
                        } else {
                            $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$input['sLayoutID']}' ", "qt.HTMLStructure , qt.FlashStructure , qt.TemplateFile ,qt.HTMLTemplate", 'details');
                        }
                    } else {
                        if ($DBCONFIG->dbType == 'Oracle')
                            $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = {$QuestTypeID} ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" , qt.\"XMLData\" , qt.\"JSONData\" ", 'details');
                        else
                            $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile , qt.XMLData , qt.JSONData ", 'details');
                    }

                    $sUserID = (string) $input['sUserID'];
                    if ($input['accessToken'] != "" && $input['accessLogID'] != "") {
                        $tokenInfo = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$input['accessToken']}' and AccessLogID = '{$input['accessLogID']}' ");
                        $this->user_info = json_decode($tokenInfo['UserInfo']);
                        $instID = $this->user_info->instId;
                    } else if (strtolower($sUserID) != "admin") {
                        $qry = "SELECT ClientID FROM MapClientUser mcu WHERE UserID = {$sUserID} AND mcu.isEnabled = 1 ";
                        $result = $this->db->getSingleRow($qry);
                        $instID = $result['ClientID'];
                    } else {
                        $instID = 0;
                    }
                }
            } else {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = ''{$input['catID']}'' ", "qt.\"HTMLTemplate\" , qt.\"TemplateFile\", qt.\"JSONData\" ", 'details');
                } else {
                    $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$input['catID']}' ", "qt.HTMLTemplate , qt.TemplateFile , qt.JSONData ", 'details');
                }

                $instID = $this->session->getValue('instID');
            }

            if ($input['prevSource'] == "wordtemplate") {
                if ($input['questID']) {
                    $EntityID = '-1';
                    $EntityTypeID = '-1';
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE \"ID\" = {$input['questID']} AND wtq.\"isEnabled\" = 1";
                    } else {
                        $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE ID = {$input['questID']} AND wtq.isEnabled = '1'";
                    }
                    $result = $this->db->getSingleRow($qry);
                    $sJson = (string) $result['JSONData'];
                } else {
                    $QuestXML = $input['sXMLInput'];
                    $EntityID = (string) $input['entityID'];
                    $EntityTypeID = (string) $input['entityTypeID'];
                    $sJson = $this->getXmlToJson(0, $QuestXML, $queTemplate["ID"]);
                }
            } elseif ($input['prevSource'] == "editor") {
                $EntityID = ($input['entityID'] != "") ? $input['entityID'] : "-1";
                $EntityTypeID = ($input['entityTypeID'] != "") ? $input['entityTypeID'] : "-1";
                $this->myDebug("This is JSON ");
                $this->myDebug($input['questJson']);

                $sJson = (!preg_match('/<object/i', $input['questJson'])) ? $qt->addMediaPlaceHolder($input['questJson']) : $input['questJson'];
            } elseif ($input['prevSource'] == "customcss" || $input['prevSource'] == "samplequest") {
                $sampleMediaPath = $this->cfg->wwwroot . '/assets/sample_assets';
                $EntityID = ($input['entityID'] != "") ? $input['entityID'] : "-1";
                $EntityTypeID = ($input['entityTypeID'] != "") ? $input['entityTypeID'] : "-1";
                $sJson = $queTemplate['JSONData'];

                $sJson = preg_replace('/__PATH/', $sampleMediaPath, $sJson);
            } elseif ($input['prevSource'] == "listing") {
                $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($input['questID'], '-1'), 'details');
                $EntityID = $input['entityID'];
                $EntityTypeID = $input['entityTypeID'];
                $sJson = $QuestDetail['JSONData'];
            }
            $this->myDebug("Previous Source");
            $this->myDebug($input['prevSource']);
            $this->myDebug($sJson);
            $sJson = $qt->removeMediaPlaceHolder($sJson);
            $guid = uniqid();
            $jscontent = "var QuestionData = {
            \"TemplateTitle\" : \"{$queTemplate["TemplateTitle"]}\",
            \"QuestJSON\" : {$sJson}
            };
            eval('MainPanel.loadIndividualPreview();')";
            $this->myDebug('rashmi222');
            $this->myDebug($sJson);
//              $this->myDebug("rasmi");
//             $this->myDebug($queTemplate["HTMLTemplate"]);
            ///$jsfilelocation = $this->cfg->rootPath.'/'.$this->cfgApp->HtmlQuestionPreviewLocation;
            $jsfilelocation = $this->cfg->rootPath . '/' . $this->cfgApp->tempDataPath . $instID . "/" . $this->cfgApp->HtmlQuestionPreviewPath;
            if (!is_dir($jsfilelocation))
                mkdir($jsfilelocation, 0777);

            $jsfilelocation.= "/Question_{$guid}.js";
            $handle = fopen($jsfilelocation, "w");
            fwrite($handle, $jscontent);
            fclose($handle);
            ///$jflPath=$this->cfgApp->HtmlQuestionPreviewLocation."Question_".$guid.".js";
            $jflPath = $this->cfgApp->tempDataPath . $instID . "/" . $this->cfgApp->HtmlQuestionPreviewPath . "Question_" . $guid . ".js";




            if ($input['customCss'] != '') {
                $persistPath = $this->cfgApp->PersistDataPath . $instID . "/" . $this->cfgApp->UserQuizHtmlCSS . (($EntityTypeID == 1) ? "bank" : "assessment") . "/" . $EntityID . "/";
                ///$customCss = str_replace('../assessmentimages','../../renditions/html/css/'.strtolower($this->getEntityName($EntityTypeID)).'/'.$EntityID.'/assessmentimages',$input['customCss']);
                $customCss = str_replace('../assessmentimages', $this->cfg->wwwroot . "/" . $persistPath . 'assessmentimages', $input['customCss']);
                $unique = uniqid();
                ///$file   = $this->cfg->rootPath.'/'.$this->cfgApp->QuizCSSImageUnzipTempLocation.$unique.'.css';
                $tempPath = $this->cfgApp->tempDataPath . $instID . "/" . $this->cfgApp->UserQuizHtmlCSS . (($EntityTypeID == 1) ? "bank" : "assessment") . "/" . $EntityID . "/";
                if (is_dir($this->cfg->rootPath . "/" . $tempPath) == false) {
                    mkdir($this->cfg->rootPath . "/" . $tempPath, 0777, true);
                }
                $file = $tempPath . $unique . '.css';
                $handle = fopen($file, 'w');
                fwrite($handle, $customCss);
                fclose($handle);
                //$csspath = $this->cfgApp->QuizCSSImageUnzipTempLocation.$unique.'.css';
                $csspath = $tempPath . $unique . '.css';
            } else {
                if ($EntityTypeID != -1) {
                    ///$cssTempPath=$this->cfgApp->UserQuizCSSLocationforHtml.(($EntityTypeID==1)?"bank":"assessment")."/".$EntityID."/";
                    $cssTempPath = $this->cfgApp->PersistDataPath . $instID . "/" . $this->cfgApp->UserQuizHtmlCSS . (($EntityTypeID == 1) ? "bank" : "assessment") . "/" . $EntityID . "/";
                    if (is_dir($this->cfg->rootPath . "/" . $cssTempPath) == false) {
                        //$this->copyCss($EntityID, $EntityTypeID);
                    }
                } else {
                    $cssTempPath = $this->cfgApp->QuizCSSLocationforHtml;
                }
                $csspath = $cssTempPath . $queTemplate["HTMLTemplate"] . "/" . $queTemplate["HTMLTemplate"] . ".css";
            }

            $previewURL = $this->cfg->wwwroot . '/' . $this->cfgApp->HtmlAssessment . $queTemplate["HTMLTemplate"] . '/' . $queTemplate["HTMLTemplate"] . ".htm?jfl={$jflPath}&cssfl={$csspath}&" . uniqid();

            header('Content-type: text/xml; charset=UTF-8');
            $oXMLout = new XMLWriter();
            $oXMLout->openMemory();
            $oXMLout->startElement('Response');
            $oXMLout->writeElement('IsValid', 'true');
            $oXMLout->writeElement('Message', '');
            $oXMLout->writeElement('PreviewURL', $previewURL);
            $oXMLout->endElement();
            $this->myDebug($previewURL);
            print $oXMLout->outputMemory();
            die;
        } catch (exception $ex) {
            $this->myDebug('::Html Preview Exception');
            $this->myDebug($ex);
        }
    }

    /**
     * a function to create xml for offline flash question preview
     *
     *
     * @access   public
     * @param    array   $input
     * @return   void
     *
     */
    function questionFlashPreview($input) {
        try {
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $isOfffline = false;
            $QuestTypeID = (string) $input['catID'];
            $qtp = new QuestionTemplate();
            $qt = new Question();


            if ($input['prevSource'] == "wordtemplate") {
                if ($input['sLayoutID'] != "") {
                    if ($DBCONFIG->dbType == 'Oracle')
                        $QuestionTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$input['sLayoutID']}'' ", "qt.ID as \"qtID\"  ,qt.\"HTMLStructure\" , qt.\"FlashStructure\" , qt.\"TemplateFile\" ,qt.\"HTMLTemplate\" ", 'details');
                    else
                        $QuestionTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$input['sLayoutID']}' ", "qt.ID as qtID  ,qt.HTMLStructure , qt.FlashStructure , qt.TemplateFile ,qt.HTMLTemplate", 'details');
                    $QuestTypeID = $QuestionTemplate['ID'];
                    $qtID = $QuestionTemplate['qtID'];
                } else {
                    if ($DBCONFIG->dbType == 'Oracle')
                        $QuestionTemplate = $qtp->questionTemplate("qt.\"isDefault\" = ''Y'' and mqt.ID = {$QuestTypeID} ", "qt.ID as \"qtID\"  ,qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" , qt.\"XMLData\" , qt.\"JSONData\" ", 'details');
                    else
                        $QuestionTemplate = $qtp->questionTemplate("qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.ID as qtID  ,qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile , qt.XMLData , qt.JSONData ", 'details');
                    $qtID = $QuestionTemplate['qtID'];
                }

                $sUserID = (string) $input['sUserID'];
                if ($input['accessToken'] != "" && $input['accessLogID'] != "") {
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $WtSql = "SELECT * FROM AccessTokens WHERE \"AccessToken\" = '{$input['accessToken']}' and \"AccessLogID\" = '{$input['accessLogID']}' ";
                    } else {
                        $WtSql = "SELECT * FROM AccessTokens WHERE AccessToken= '{$input['accessToken']}' and AccessLogID = '{$input['accessLogID']}' ";
                    }
                    $tokenInfo = $this->db->getSingleRow($WtSql);
                    $this->user_info = json_decode($tokenInfo['UserInfo']);
                    $instID = $this->user_info->instId;
                } else if (strtolower($sUserID) != "admin") {

                    if ($DBCONFIG->dbType == 'Oracle') {
                        $qry = "SELECT ClientID FROM MapClientUser mcu WHERE \"UserID\" = {$sUserID} AND mcu.\"isEnabled\" = 1 ";
                    } else {
                        $qry = "SELECT ClientID FROM MapClientUser mcu WHERE UserID = {$sUserID} AND mcu.isEnabled = '1' ";
                    }
                    $result = $this->db->getSingleRow($qry);
                    $instID = $result['ClientID'];
                } else {
                    $instID = 0;
                }
            } else {
                //$QuestionTemplate   =  $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.ID = '{$QuestTypeID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate, qt.TemplateFile",'details');
                if ($DBCONFIG->dbType == 'Oracle')
                    $QuestionTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = {$QuestTypeID} ", "qt.ID as \"qtID \" , qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" , qt.\"XMLData\" , qt.\"JSONData\" ", 'details');
                else
                    $QuestionTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.ID as qtID  , qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile , qt.XMLData , qt.JSONData ", 'details');
                $instID = $this->session->getValue('instID');
                $qtID = $QuestionTemplate['qtID'];
            }
            //for online Preview from Quiz Details and Bank Details Page

            if ($input['prevSource'] == "editor") {//Question Editor
                /*
                 * DO NOT DELETE BELOW CODE  :: Chirag Modi
                 * $converter      = new DataConverter();
                  $jsonarray     = $converter->convertJsonToArray('{ "question" : '.$input['sJson'] .' }');
                  echo $xml     = $converter->convertArrayToXML($jsonarray);
                  die; */
                $this->myDebug('Editor page');
                $sJson = stripslashes($input['questJson']);
                $sJson = $input['questJson'];
                $sXMLInput = $this->getJsonToXml('', $sJson, $QuestTypeID);
                $this->myDebug("This xml before");
                $this->myDebug($sXMLInput);
                $sJson = (!preg_match('/<object/i', $input['questJson'])) ? $qt->addMediaPlaceHolder($input['questJson']) : $input['questJson'];
                $sXMLInput = (!preg_match('/<object/i', $input['questJson'])) ? $qt->addMediaPlaceHolder($sXMLInput) : $sXMLInput;
                $this->myDebug("This xml after");
                $this->myDebug($sXMLInput);
                $EntityID = ($input['entityID'] != "") ? $input['entityID'] : "-1";
                $EntityTypeID = ($input['entityTypeID'] != "") ? $input['entityTypeID'] : "-1";
            } elseif ($input['prevSource'] == "customcss" || $input['prevSource'] == "samplequest")/* custum css and sample preview */ {
                $EntityID = ($input['entityID'] != "") ? $input['entityID'] : "-1";
                $EntityTypeID = ($input['entityTypeID'] != "") ? $input['entityTypeID'] : "-1";
                $sJson = $QuestionTemplate['JSONData'];
                $sXMLInput = $QuestionTemplate['XMLData'];
                $sampleMediaPath = $this->cfg->wwwroot . '/assets/sample_assets';
                $sXMLInput = preg_replace('/__PATH/', $sampleMediaPath, $sXMLInput);
            } else if ($input['prevSource'] == "listing") {//questin list
                $this->myDebug('Question list');
                $questID = $input['questID'];
                $EntityID = $input['entityID'];
                $EntityTypeID = $input['entityTypeID'];
                if ($questID == 0) { // for not saved question
                    $sXMLInput = $QuestionTemplate['XMLData'];
                    if (strpos($sXMLInput, '<![CDATA[') > -1) {
                        $sXMLInput = utf8_encode($sXMLInput);
                    } else {
                        $isOfffline = true;
                    }
                    $sJson = $QuestionTemplate['JSONData'];
		} else { // for saved question
                    $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($questID, '-1'), 'details');
                    $sXMLInput = $QuestDetail['XMLData'];
                    if (strpos($sXMLInput, '<![CDATA[') > -1) {
                        $sXMLInput = utf8_encode($sXMLInput);
                    } else {
                        $isOfffline = true;
                    }
                    $sJson = $QuestDetail['JSONData'];
                }
            } else if ($input['prevSource'] == "wordtemplate") {//for offline Preview from Template with and without Offline Toolbar
                /* for wordtemplate use this condition
                  if($input['prevSource'] == "wordtemplate")
                 */
                if ($DBCONFIG->dbType == 'Oracle') {
                    $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE \"ID\" = {$input['questID']} AND wtq.\"isEnabled\" = 1 ";
                } else {
                    $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE ID = {$input['questID']} AND wtq.isEnabled = '1' ";
                }


                $result = $this->db->getSingleRow($qry);
                if ($input['questID']) {
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE \"ID\" = {$input['questID']} AND wtq.\"isEnabled\" = 1";
                    } else {
                        $qry = "SELECT * FROM WordTemplateQuestions wtq WHERE ID = {$input['questID']} AND wtq.isEnabled = '1' ";
                    }

                    $result = $this->db->getSingleRow($qry);
                    $sXMLInput = (string) $result['XMLData'];
                } else {
                    $sXMLInput = $input['sXMLInput'];
                }
                $this->myDebug('offline authoring');
                $isOfffline = true;
                $sJson = $this->getXmlToJson(0, $sXMLInput, $QuestTypeID);
                $EntityID = (string) $input['entityID'];
                $EntityTypeID = (string) $input['entityTypeID'];
            }
            $sXMLInput = stripslashes($sXMLInput);
            $sXMLInput = str_replace("''", '"', $sXMLInput);
            $this->myDebug('$sXMLInput');
            $this->myDebug($sXMLInput);
            $sXMLInput = $this->spanRemove($sXMLInput);
            $sJson = $qt->removeMediaPlaceHolder($sJson);
            $sXMLInput = trim($qt->removeMediaPlaceHolder($sXMLInput), "'");
            $this->myDebug('After spanRemove==>' . $sXMLInput);
            $guid = uniqid();
            //$xmlfilelocation    = $this->cfg->rootPath.'/'.$this->cfgApp->QuestionPreviewLocation.$guid.'/';
            $xmlfilelocation = $this->cfg->rootPath . '/' . $this->cfgApp->tempDataPath . $instID . "/" . $this->cfgApp->QuestionPreviewPath . $guid . '/';
            $this->myDebug($xmlfilelocation);
            $xmlfile = "{$xmlfilelocation}{$QuestionTemplate['TemplateFile']}Base.xml";
            if (!is_dir($xmlfilelocation)) {
                mkdir($xmlfilelocation, 0777);
            }
            $handle = fopen($xmlfile, 'w');
            fwrite($handle, stripslashes($sXMLInput));
            fclose($handle);
            $this->myDebug("New xml");
            $this->myDebug($sXMLInput);
            //Copy Images to Preview Folder
            ///$this->previewImageCopy($id,$sJson,$QuestTypeID,$xmlfilelocation);
            $xslDoc = new DOMDocument();
            $this->myDebug('XSL File --' . $this->cfg->rootPath . '/' . $this->cfgApp->RenditionXSLLocation . $QuestionTemplate['TemplateFile'] . '.xsl');
            $xslDoc->load($this->cfg->rootPath . '/' . $this->cfgApp->RenditionXSLLocation . $QuestionTemplate['TemplateFile'] . '.xsl');
            $xmlDoc = new DOMDocument();

            $xmlDoc->load($xmlfile);
            $proc = new XSLTProcessor();
            $proc->importStylesheet($xslDoc);
            $multi = $proc->transformToXML($xmlDoc);

            $this->myDebug("New Transform xml");
            $this->myDebug($multi);

            //Doing Right Path
            //$multi = $this->createImagePathPreview($multi,$guid.'/');
            $multi = str_replace('../../../../images/', '', $multi);
            $multi = str_replace('spacer.jpg', $this->cfg->wwwroot . '/assets/images/spacer.jpg', $multi);
            $multi = str_replace('spacer.flv', $this->cfg->wwwroot . '/assets/images/spacer.flv', $multi);
            //if($QuestTypeID == 22)
            if ($qtID == 22)
                $multi = str_replace('images/', '', $multi);

            //if($QuestTypeID == 10 || $QuestTypeID == 8 || $QuestTypeID == 9 ||  $QuestTypeID == 20 || $QuestTypeID == 43 || $QuestTypeID == 44 || $QuestTypeID == 47 || $QuestTypeID == 48)
            if ($qtID == 10 || $qtID == 8 || $qtID == 9 || $qtID == 20 || $qtID == 43 || $qtID == 44 || $qtID == 47 || $qtID == 48)
                $multi = str_replace($guid, '../' . $guid, $multi);

            $this->myDebug("rashmi----");
            $this->myDebug($qtID);
            $this->myDebug($multi);
            $multi = $this->htmlEntityToHashCode($multi, $isOfffline);
            $xmlfile = "{$xmlfilelocation}{$QuestionTemplate['TemplateFile']}.xml";
            $handle = fopen($xmlfile, 'w');
            fwrite($handle, $multi);
            fclose($handle);
            //copy file
            $sources = $this->cfg->rootPath . '/' . $this->cfgApp->FlashRenditionLocation . $QuestionTemplate['TemplateFile'];
            $dest = $xmlfilelocation;

            /// if(is_dir($sources))
            ///$num = $this->dirCopy($sources, $dest, 1);
            //$destcsspath= $this->cfg->rootPath.'/'.$this->cfgApp->QuestionPreviewLocation.$guid."/".$QuestionTemplate['TemplateFile'].'.css';
            if ($input['customCss'] != '') {
                $cssFolderLocation = $this->cfgApp->PersistDataPath . $instID . "/" . $this->cfgApp->UserQuizFlashCSS . strtolower($this->getEntityName($EntityTypeID)) . "/" . $EntityID;
                $customCss = str_replace("images/NVQ/", $this->registry->site->cfg->wwwroot . "/" . "{$cssFolderLocation}/images/NVQ/", $input['customCss']);
                $file = $this->cfg->rootPath . '/' . $this->cfgApp->QuestionPreviewLocation . $guid . "/" . $QuestionTemplate['TemplateFile'] . '.css';
                $tempPath = $this->cfgApp->tempDataPath . $instID . "/" . $this->cfgApp->UserQuizFlashCSS . (($EntityTypeID == 1) ? "bank" : "assessment") . "/" . $EntityID . "/";
                if (is_dir($this->cfg->rootPath . "/" . $tempPath) == false) {
                    mkdir($this->cfg->rootPath . "/" . $tempPath, 0777, true);
                }
                $file = $tempPath . $guid . '.css';
                $handle = fopen($file, 'w');
                fwrite($handle, $customCss);
                fclose($handle);
                $csspath = "&cssfl=" . $tempPath . $guid . '.css';
            }


            //$previewURL = $this->cfg->wwwroot.'/'.$this->cfgApp->PreviewPHPFileLocation."showflash.php?guid={$guid}&xmlfile={$QuestionTemplate['TemplateFile']}.xml&sLayoutID={$QuestionTemplate['TemplateFile']}&location={$QuestionTemplate['TemplateFile']}.swf";
            $previewURL = $this->cfg->wwwroot . "/authoring/question-flash-preview/?guid={$guid}&instID={$instID}&entityID={$EntityID}&sLayoutID={$QuestionTemplate['TemplateFile']}&entityTypeID={$EntityTypeID}{$csspath}";
            header('Content-type: text/xml; charset=UTF-8');
            $oXMLout = new XMLWriter();
            $oXMLout->openMemory();
            $oXMLout->startElement('Response');
            $oXMLout->writeElement('IsValid', 'true');
            $oXMLout->writeElement('Message', '');
            $oXMLout->writeElement('PreviewURL', $previewURL);
            $oXMLout->endElement();
            $this->myDebug($previewURL);
            print $oXMLout->outputMemory();
            die;
        } catch (exception $ex) {
            $this->myDebug('::Preview Exception');
            $this->myDebug($ex);
        }
    }

    /**
     * a function to create img to [[]]
     *
     *
     * @access   public
     * @param    string  $str
     * @return   mixed
     *
     */
    // covert img to [[]]
    function imgEncode($str) {
        $URLSearchString = " a-zA-Z0-9\:\&\/\-\?\.\=\_\~\#\'";
        $q = $str;
        $q = preg_replace("( alt=\"([$URLSearchString]*)\")", '', $q);
        $q = preg_replace("( title=\"([$URLSearchString]*)\")", '', $q);
        $q = preg_replace("( width=\"([$URLSearchString]*)\")", '', $q);
        $q = preg_replace("( height=\"([$URLSearchString]*)\")", '', $q);
        $q = preg_replace("(\<img src\=\"([$URLSearchString]*)\" /\>)", '[[$1]]', $q);
        return $q;
    }

    /**
     * a function to remove unwanted characters from before and after img tag
     *
     *
     * @access   public
     * @param    string  $str
     * @return   mixed
     *
     */
    function sourceImgEncode($str) {
        $URLSearchString = " a-zA-Z0-9\:\;\&\/\-\?\.\=\_\~\#\'/^\s*";
        $q = $str;
        $q = preg_replace("( /\>([$URLSearchString]*)\<img)", ' /><img', $q);
        $q = preg_replace("(\<p\>([$URLSearchString]*)\<img)", '<p><img', $q);
        $q = preg_replace("( /\>([$URLSearchString]*)\</p\>)", ' /></p>', $q);
        return $q;
    }

    /**
     * a function to covert [[]] to img
     *
     *
     * @access   public
     * @param    string  $str
     * @return   mixed
     *
     */
    // covert [[]] to img
    function imgDecode($str) {
        $URLSearchString = " a-zA-Z0-9\:\&\/\-\?\.\=\_\~\#\'";
        $q = $str;
        $q = preg_replace("(\[\[([$URLSearchString]*)\]\])", 'HECKSOURCEIMAGESTART src="$1" HECKSOURCEIMAGEEND', $q);
        return $q;
    }

    /**
     * a function to remove span tag/spaces from given string
     *
     *
     * @access   public
     * @param    string  $str
     * @param    string  $option
     * @return   string
     *
     */
    function spanRemove($str, $option = '') {
        $str = preg_replace("#(<span.*?>|</span>)#i", '', $str);
        return str_replace(array("\r\n", "\r", "\n", "\t"), '', $str);
    }

    /**
     * a function to remove span tag/spaces from each node of given string
     *
     *
     * @access   public
     * @param    string  $str
     * @param    string  $option
     * @return   string
     *
     */
    function spanRemoveEachNode($str, $option = '') {
        $str = preg_replace("#(<span.*?>|</span>)#i", '', $str);
        return str_replace(array("\n\t\t\t\t\t\t\t", "\r\n", "\r", "\n", "\t"), $option, $str);
    }

    /**
     * a function to copy media to its specified folder from given json of question for flash preview
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer $questid
     * @param    string  $objJson
     * @param    integer $QuestTypeID
     * @param    string  $imagelocation
     * @param    boolean $isQuizPreview
     * @return   void
     *
     */
    function previewImageCopy($questid = '', $objJson = '', $QuestTypeID = '', $imagelocation = '', $isQuizPreview = false) {
        try {
            $this->myDebug('previewImageCopy' . '===========' . $questid . '===========' . $objJson . '===========' . $QuestTypeID . '===========' . $imagelocation . '===========' . $isQuizPreview);
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $objJSONtmp = new Services_JSON();
            $objJsonTemp = $objJSONtmp->decode($objJson);

            $objMedia = new Media();
            $userImagePath = $objMedia->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/'));
            $userVideoPath = $objMedia->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/original/'));
            if (!isset($objJsonTemp))
                $objJsonTemp = $objJSONtmp->decode(stripslashes($objJson));

            if (is_dir($imagelocation) == false) {
                mkdir($imagelocation, 0777);
            }

            $objJson = $objJsonTemp;
            if (isset($objJson)) {
                if (isset($objJson->{'image'})) {
                    $xmlimages = $this->getImageUrl($objJson->{'image'});

                    if ($xmlimages != '') {
                        $getlast = strrpos($xmlimages, '/');
                        $imagename = substr($xmlimages, $getlast + 1);
                        ///$xmlimagesreal  = $CONFIG->rootPath.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                        $xmlimagesreal = $userImagePath . $imagename;
                        $imagepathdest = "{$imagelocation}$imagename";
                        $this->myDebug('image only' . $xmlimagesreal . '--' . $imagepathdest);

                        if (is_file($xmlimagesreal))
                            copy($xmlimagesreal, $imagepathdest);
                    }
                }


                if (isset($objJson->{'video'})) {
                    $xmlimages = $this->getVideoUrl($objJson->{'video'});
                    if ($xmlimages != '') {
                        $getlast = strrpos($xmlimages, '/');
                        $imagename = substr($xmlimages, $getlast + 1);

                        ///$xmlimagesreal = $CONFIG->rootPath.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                        $xmlimagesreal = $userVideoPath . $imagename;
                        $imagepathdest = "{$imagelocation}$imagename";
                        $this->myDebug('video only' . $xmlimagesreal . '--' . $imagepathdest);

                        if (is_file($xmlimagesreal))
                            copy($xmlimagesreal, $imagepathdest);
                    }
                }

                if (isset($objJson->{'choices'}) && is_array($objJson->{'choices'})) {
                    $objjsonchoices = $objJson->{'choices'};
                    if (!empty($objjsonchoices)) {
                        foreach ($objjsonchoices as $objjsonchoice) {
                            if ($QuestTypeID == 16 || $QuestTypeID == 17
			    )
				continue;

                            if ($QuestTypeID == 13 || $QuestTypeID == 14 || $QuestTypeID == 18 || $QuestTypeID == 19 || $QuestTypeID == 20 || $QuestTypeID == 22) {
                                if ($QuestTypeID == 18 || $QuestTypeID == 19 || $QuestTypeID == 20 || $QuestTypeID == 22)
                                    $optionstext = html_entity_decode($objjsonchoice->{'val2'});
                                else
                                    $optionstext = html_entity_decode($objjsonchoice->{'val1'});
                            }
                            else
                                $optionstext = html_entity_decode($objjsonchoice->{'val2'});

                            $this->myDebug('JsonImage----' . $QuestTypeID);
                            if ($QuestTypeID == 10 || $QuestTypeID == 20) {
                                $this->myDebug('Check if' . strpos($optionstext, '<param'));
                                $xmlimages = $this->getVideoUrl($optionstext);
                                if ($xmlimages != '') {
                                    $this->myDebug('Real Path ' . $xmlimages);
                                    $getlast = strrpos($xmlimages, '/');
                                    $imagename = substr($xmlimages, $getlast + 1);

                                    ///$xmlimagesreal = $CONFIG->rootPath.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                                    $xmlimagesreal = $userVideoPath . $imagename;
                                    $imagepathdest = "{$imagelocation}$imagename";
                                    $this->myDebug('select only' . $xmlimagesreal . '--' . $imagepathdest);

                                    if (is_file($xmlimagesreal))
                                        copy($xmlimagesreal, $imagepathdest);
                                }
                            }
                            else {

                                $xmlimages = $this->getImageUrl($optionstext);
                                if ($xmlimages != '') {
                                    $getlast = strrpos($xmlimages, '/');
                                    $imagename = substr($xmlimages, $getlast + 1);

                                    ///$xmlimagesreal = $CONFIG->rootPath.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                                    $xmlimagesreal = $userImagePath . $imagename;
                                    $imagepathdest = "{$imagelocation}$imagename";
                                    $this->myDebug('select only' . $xmlimagesreal . '--' . $imagepathdest);

                                    if (is_file($xmlimagesreal))
                                        copy($xmlimagesreal, $imagepathdest);
                                }
                            }
                        }
                    }
                }
            }

            copy($CONFIG->rootPath . '/assets/images/spacer.jpg', $imagelocation . 'spacer.jpg');
            copy($CONFIG->rootPath . '/assets/images/spacer.flv', $imagelocation . 'spacer.flv');
            return;
        } catch (exception $e) {
            return;
        }
    }

    /**
     * a function to convert applicable html entities to its equivalent hash code
     *
     *
     * @access   public
     * @global   array   $SPECIALCHARS
     * @global   array   $CHARMAP
     * @param    string  $input
     * @param    boolean $isOfffline
     * @return   string
     *
     */
    function htmlEntityToHashCode($input = '', $isOfffline = false) {
        global $SPECIALCHARS, $CHARMAP;

        if ($isOfffline) {
            /* $input = utf8_decode($input);
              $this->myDebug("input for charreplaceforflash1.1--".$input);
              $input = htmlentities($input,ENT_NOQUOTES, "ISO-8859-1");
              $this->myDebug("input for charreplaceforflash1.2--".$input);
              $input = str_replace("&lt;","<",$input);
              $input = str_replace("&gt;",">",$input);
              $input = str_replace("&amp;amp;","&amp;",$input);
              $input = str_replace("&amp;lt;","&lt;",$input);
              $input = str_replace("&amp;gt;","&gt;",$input); */
        } else {
            $input = html_entity_decode($input);
        }
        if (!empty($CHARMAP)) {
            foreach ($CHARMAP as $SPECIALCHAR) {
                if ($SPECIALCHAR[2] === true) {
                    $input = str_replace($SPECIALCHAR[0], $SPECIALCHAR[1], $input);
                }
                $i++;
            }
        }

        return $input;
    }

    /**
     * a function to convert back hash code to its equivalent html entity
     *
     *
     * @access   public
     * @global   array   $SPECIALCHARS
     * @global   array   $CHARMAP
     * @param    string  $input
     * @return   string
     *
     */
    function hashCodeToHtmlEntity($input = '') {
        global $SPECIALCHARS, $CHARMAP;

        $oUnicodeReplace = new unicode_replace_entities();
        $input = $oUnicodeReplace->UTF8entities($input);
        $input = str_replace("\"", '&quot;', $input);
        if (!empty($CHARMAP)) {
            foreach ($CHARMAP as $SPECIALCHAR) {
                if ($SPECIALCHAR[2] === true) {
                    $input = str_replace($SPECIALCHAR[1], $SPECIALCHAR[0], $input);
                }
            }
        }
        return $input;
    }

    /**
     * a function to set image path in the given string to the specified target path
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    string  $multi
     * @param    string  $targetpath
     * @return   string
     *
     */
    function createImagePathPreview($multi, $targetpath) {
        global $CONFIG, $APPCONFIG;
        $multi = str_replace('../../../../images/', '', $multi);
        $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;
        $objMedia = new Media();
        $userImagePath = $objMedia->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/', 'protocol' => 'http'));
        if (strpos($multi, 'HECKSOURCEIMAGESTART') > -1) {
            $multi = str_replace('HECKSOURCEIMAGESTART', '<img', $multi);
            $multi = str_replace('HECKSOURCEIMAGEEND', '/>', $multi);
            $multi = str_replace("&lt;img", "<img", $multi);
            $multi = str_replace("/&gt;", "/>", $multi);
        }

        if (strpos($multi, $CONFIG->wwwroot) > -1) {   ////if already contain web refernce path
            ///$multi = str_replace("{$CONFIG->wwwroot}/{$APPCONFIG->EditorImagesUpload}",$targetpath,$multi);
            $multi = str_replace($userImagePath, $targetpath, $multi);
        } else {
            if ($CONFIG->projectName == '') {   ////if project deployed at root
                ///$multi = str_replace("/{$APPCONFIG->EditorImagesUpload}",$targetpath,$multi);
                $multi = str_replace("/{$APPCONFIG->PersistDataPath}" . $instID . "/assets/images/original/", $targetpath, $multi);
            } else {
                ///$multi = str_replace("/{$CONFIG->projectName}/{$APPCONFIG->EditorImagesUpload}",$targetpath,$multi);
                $multi = str_replace("/{$CONFIG->projectName}/{$APPCONFIG->PersistDataPath}" . $instID . "/assets/images/original/", $targetpath, $multi);
            }
        }

        //code for spacer
        $multi = str_replace('spacer.', $targetpath . 'spacer.', $multi);
        return $multi;
    }

    /**
     * a function to add slashes to the given string for escaping it
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    string  $str
     * @return   string
     *
     */
    function wordToEditor($str) {
        global $CONFIG, $APPCONFIG;
        $str = addslashes($str);
        return $str;
    }

    /**
     * a function to convert all & to &amp; in the given string
     *
     *
     * @access   public
     * @param    string  $inputstr
     * @return   string
     *
     */
    function editorToWord($inputstr) {
        $inputstr = html_entity_decode($inputstr, ENT_NOQUOTES);
        $sfind1 = '&amp;';
        $sfind2 = '&';
        $inputstr = preg_replace("/$sfind1/", "$sfind2", $inputstr);
        $inputstr = preg_replace("/$sfind2/", "$sfind1", $inputstr);

        return $inputstr;
    }

    /**
     * a function to create dom element node from its equivalent simplexml node and append this element as CDATA to given node
     *
     *
     * @access   public
     * @param    mixed   $objxmlnode
     * @param    string  $jsontext
     * @return   mixed
     *
     */
    function createXmlnodeForwordTemp($objxmlnode = null, $jsontext = '') {
        if (isset($objxmlnode)) {
            $objxmlnode[0] = '';
            $objpara = $objxmlnode->addChild('para');
            $node = dom_import_simplexml($objpara);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($jsontext));
        }
        return $objxmlnode;
    }

    /**
     * a function to add image source as web path
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    string  $xmlimagespath
     * @return   string
     *
     */
    function getImageWebPath($xmlimagespath) {
        global $CONFIG, $APPCONFIG;

        if (strpos($xmlimagespath, $CONFIG->wwwroot) > -1) {
            
        } else {
            if ($CONFIG->projectName == '') {      ////if project deployed at root
                $xmlimagespath = str_replace("/{$APPCONFIG->EditorImagesUpload}", $CONFIG->wwwroot . '/' . $APPCONFIG->EditorImagesUpload, $xmlimagespath);
            } else {
                $xmlimagespath = str_replace("/{$CONFIG->projectName}/{$APPCONFIG->EditorImagesUpload}", $CONFIG->wwwroot . '/' . $APPCONFIG->EditorImagesUpload, $xmlimagespath);
            }
        }

        return $xmlimagespath;
    }

    /**
     * a function to convert html entities to its another equivalent html entity compatible to IE
     *
     *
     * @access   public
     * @param    string  $xmlimagespath
     * @return   string
     *
     */
    function formatforIEBack($xmlimagespath) {
        $xmlimagespath = str_replace("<strong>", "<b>", $xmlimagespath);
        $xmlimagespath = str_replace("</strong>", "</b>", $xmlimagespath);
        $xmlimagespath = str_replace("<em>", "<i>", $xmlimagespath);
        $xmlimagespath = str_replace("</em>", "</i>", $xmlimagespath);

        $xmlimagespath = str_replace("&lt;strong&gt;", "&lt;b&gt;", $xmlimagespath);
        $xmlimagespath = str_replace("&lt;/strong&gt;", "&lt;/b&gt;", $xmlimagespath);
        $xmlimagespath = str_replace("&lt;em&gt;", "&lt;i&gt;", $xmlimagespath);
        $xmlimagespath = str_replace("&lt;/em&gt;", "&lt;/i&gt;", $xmlimagespath);

        return $xmlimagespath;
    }

    /**
     * a function to get json element text
     *
     *
     * @access   public
     * @param    string  $objjsonelement
     * @param    boolean $striptag
     * @param    boolean $issource
     * @return   string
     *
     */
    function getJsonElementText($objjsonelement = '', $striptag = false, $issource = false) {
        return $objjsonelement;
    }

    /**
     * a function to get pure text (i.e. without html entities) from given element
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    object  $node
     * @param    boolean $striptaghtmldecode
     * @return   string
     *
     */
    function getRowTextFromWord($node, $striptaghtmldecode = false) {
        global $CONFIG, $APPCONFIG;
        $this->myDebug('Node');
        $this->mydebug($node);

        if (!isset($node)
	)
	    return '';
        $quest = '';
        $quest = $node->asXML();
        $this->myDebug('getRowTextFromWord==========>' . $quest);
        $quest = strip_tags($quest, '<b><u><i><sub><sup><a>');

        if ($striptaghtmldecode) {
            $quest = strip_tags(html_entity_decode($quest));
        } else {
            $this->myDebug('getRowTextFromWord==>' . $quest);
            $quest = $this->hashCodeToHtmlEntity($quest);
            $this->myDebug('getRowTextFromWord==>' . $quest);
        }
        // We have added htmlentities below so removed 
        // $quest = str_replace('"', "&quot;", $quest);
        // $quest = str_replace("'", "&#39;", $quest);
        $quest = preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/', '', $quest);


        $quest = htmlentities($quest, ENT_QUOTES);
        $quest = str_replace("'", "&#39;", $quest);


        // $quest = str_replace("\"", "&quote;", $quest);
//$quest = str_replace(chr(34), '@', $quest);
        return $quest;
    }

    /**
     * a function to convert applicable characters to the its equivalent entities except backslash in the given string if it is set
     *
     *
     * @access   public
     * @param    string  $objjsonelement
     * @return   string
     *
     */
    function getJsonElementTextNew($objjsonelement) {
        if (isset($objjsonelement)) {
            $this->myDebug('getjsonelementtextnew input' . $objjsonelement);

            $objjsonelement = str_replace("\\\"", '_HECKQUOTES_', $objjsonelement);
            $objjsonelement = htmlentities($objjsonelement);
            $objjsonelement = str_replace('_HECKQUOTES_', "\\\"", $objjsonelement);

            $this->myDebug('getjsonelementtextnew input final' . $objjsonelement);

            return $objjsonelement;
        } else {
            return '';
        }
    }

    /**
     * a function to create zip of the given source directory in the given destination directory
     *
     *
     * @access   public
     * @global   array   $filenames
     * @param    string  $sourcedir
     * @param    string  $destdir
     * @return   void
     *   Moved to  Site.php
     */
    /*
      function makeZip($sourcedir, $destdir)
      {
      global $filenames;
      $this->browse($sourcedir);
      $zip = new ZipArchive();

      error_reporting(E_ALL);

      if ($zip->open($destdir, ZIPARCHIVE::CREATE)!==TRUE)
      {
      exit("cannot open <$destdir>\n");
      }
      if(!empty($filenames))
      {
      foreach ($filenames as $filename)
      {
      $file_ext=strtolower($this->findExt($filename));
      if($file_ext != 'scc' )
      {
      $zip->addFile($filename, str_replace($sourcedir. '/'  , '', $filename));
      }
      }
      }
      $zip->close();
      }
     *
     */

    /**
     * a function to browse the given directory and collecting filenames in it recursively
     *
     *
     * @access   public
     * @global   array   $filenames
     * @param    string  $dir
     * @return   array
     *    Moved to  Site.php
     */
    /*
      function browse($dir)
      {
      global $filenames;

      if ($handle = opendir($dir))
      {
      while (false !== ($file = readdir($handle)))
      {
      if ($file != '.' && $file != '..' && is_file($dir.'/'.$file))
      {
      $filenames[] =  $dir.'/'.$file;
      }
      else if ($file != '.' && $file != '..' && is_dir($dir.'/'.$file))
      {
      $this->browse($dir.'/'.$file);
      }
      }
      closedir($handle);
      }
      return $filenames;
      }
     *
     */

    /**
     * a function to create html for video from the given parameters
     *
     *
     * @access   public
     * @param    string  $objecturl
     * @param    string  $objectdesc
     * @return   string
     *
     */
    function createVideoObject($objecturl, $objectdesc = '') {
        if ($this->registry->previewMode == 'OfflineHtml' && $objecturl == '') {
            $objecturl = $this->cfg->wwwroot . '/' . $this->cfgApp->AssetsLocation . 'images/video_not_available.jpg';
            $mediahtml = $this->createImageObject($objecturl, $objectdesc);
        } else {
            $mediahtml = "&lt;p&gt;&lt;object classid=&quot;clsid:d27cdb6e-ae6d-11cf-96b8-444553540000&quot; width=&quot;100&quot; height=&quot;100&quot; codebase=&quot;http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0&quot;&gt;&lt;param name=&quot;src&quot; value=&quot;{$objecturl}&quot; /&gt;&lt;embed type=&quot;application/x-shockwave-flash&quot; width=&quot;100&quot; height=&quot;100&quot; src=&quot;{$objecturl}&quot;&gt;&lt;/embed&gt;&lt;/object&gt;&lt;/p&gt;";
        }
        return ($objecturl == '') ? $objectdesc : $mediahtml;
    }

    /**
     * a function to create html for image from the given parameters
     *
     *
     * @access   public
     * @param    string  $objecturl
     * @param    string  $objectdesc
     * @return   string
     *
     */
    function createImageObject($objecturl, $objectdesc = '', $objectitle = "") {
        if ($this->registry->previewMode == 'OfflineHtml' && $objecturl == '') {
            $objecturl = $this->cfg->wwwroot . '/' . $this->cfgApp->AssetsLocation . 'images/image_not_available.jpg';
        }
        $mediahtml = "&lt;p&gt;&lt;img src=&quot;{$objecturl}&quot; alt=&quot;{$objectdesc}&quot; title=&quot;{$objectitle}&quot;/&gt;&lt;/p&gt;";

        return ($objecturl == '') ? $objectdesc : $mediahtml;
    }

    /**
     * a function to get video path as web path from the given element
     *
     *
     * @access   public
     * @param    string  $objectMedia
     * @return   string
     *
     */
    function getVideoUrl($objectMedia, $attr = '') {
        $mediaurl = '';
        $objectMedia = html_entity_decode($objectMedia);
        if ($attr != '') {
            if (strpos($objectMedia, '<object') > -1) {
                $objectMedia = (strpos($objectMedia, '<p>') > -1) ? $objectMedia : "<p>$objectMedia</p>";
                if ($this->validateXml($objectMedia)) {
                    $xmlimages = new SimpleXMLElement($objectMedia);
                    $mediaurl = $this->getAttribute($xmlimages->object, $attr);
                    $mediaurl = $this->getImageWebPath($mediaurl);
                }
            }
        } else {
            preg_match('/data=\"([^"]*)/', $objectMedia, $matches);
            $mediaurl = isset($matches[1]) ? $matches[1] : '';
        }
        return $mediaurl;
    }

    /**
     * a function to get image path as web path from the given element
     *
     *
     * @access   public
     * @param    string  $objectMedia
     * @param    string  $attr
     * @param    string  $imgname
     * @return   string
     *
     */
    function getImageUrl($objectMedia, $attr = '', $imgname = '') {
        $mediaurl = '';
        $objectMedia = html_entity_decode($objectMedia);
		/*	http://s3.amazonaws.com/PAI02/data/persistent/institutes/25/assets/images/original/media5375faf8a16a4.png?AWSAccessKeyId=AKIAIHV5AOSQRZZXBZHA&Expires=1400343709&Signature=uOjcmxVyBtSSH0o8JP7qfHQSbs0=
		*/
        $attr = ($attr != '') ? $attr : 'src';

        if (strpos($objectMedia, '<img') > -1) {
            $objectMedia = (strpos($objectMedia, '<p>') > -1) ? $objectMedia : "<p>$objectMedia</p>";

	    /* if ($this->validateXml($objectMedia)) {			
                $xmlimages = new SimpleXMLElement($objectMedia);
                /$mediaurl = $this->getAttribute($xmlimages->img, $attr);
                if ($attr == 'src') {
                    $mediaurl = $this->getimagewebpath($mediaurl);
            }
	      } */
        }

        if ($imgname != '') {
            $img_name = substr(strrchr($mediaurl, '/'), 1);
            return $img_name;
        }
        return $mediaurl;
    }

    /**
     * a function to validate given xml
     *
     *
     * @access   public
     * @param    string  $xml
     * @return   boolean
     *   Moved to  Site.php
     */
    function validateXml($xml) {
      // libxml_use_internal_errors(true);
      // $doc = new DOMDocument('1.0', 'utf-8');
      // $doc->loadXML($xml);
      // $errors = libxml_get_errors();
      // if (empty($errors))
      // {
      // return true;
      // }
      // $error = $errors[ 0 ];
      // if ($error->level < 3)
      // {
      // return true;
      // }
      // $lines  = explode('r', $xml);
      // $line   = $lines[($error->line)-1];

      return true;
      }

    /**
     * a function to copy all images from the given xml to the given destination
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    string  $imagehtml
     * @param    string  $imagelocation
     * @return   void
     *
     */
    function copyMultiImage($imagehtml, $imagelocation) {
        global $CONFIG, $APPCONFIG;

        $objMedia = new Media();
        $userImagewwwPath = $objMedia->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/', 'protocol' => 'http'));
        $userImagerootPath = $objMedia->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/'));
        $this->myDebug('mulitimage source1--' . $imagehtml);
        $imagehtml = html_entity_decode($imagehtml);
        $imagehtml = strip_tags(stripslashes($imagehtml), '<img>');

        if (strpos($imagehtml, '<img') > -1) {
            $imagehtml = (strpos($imagehtml, '<p>') > -1) ? $imagehtml : "<p>$imagehtml</p>";
            $imagehtml = stripslashes($imagehtml);
            $this->myDebug('mulitimage1--' . $imagehtml);
            $imagehtml = $this->sourceImgEncode($imagehtml);
            $this->myDebug('mulitimage2--' . $imagehtml);

            if (!$this->validateXml($imagehtml)) {
                $imagehtml = utf8_encode($imagehtml);
            }
            $this->myDebug('mulitimage3--' . $imagehtml);

            if ($this->validateXml($imagehtml)) {
                $xmlimages = new SimpleXMLElement($imagehtml);
                if (!empty($xmlimages->img)) {
                    foreach ($xmlimages->img as $xmlimage) {
                        $xmlimages = $this->getAttribute($xmlimage, 'src');
                        $getlast = strrpos($xmlimages, '/');
                        $imagename = substr($xmlimages, $getlast + 1);
                        ///$xmlimagesweb = $CONFIG->wwwroot.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                        $xmlimagesweb = $userImagewwwPath . $imagename;
                        ///$xmlimagesreal = $CONFIG->rootPath.'/'.$APPCONFIG->EditorImagesUpload.$imagename;
                        $xmlimagesreal = $userImagerootPath . $imagename;
                        $imagepathdest = "{$imagelocation}$imagename";
                        $this->myDebug('mulitimage---' . $imagepathdest . '---' . $xmlimagesreal);
                        if (is_file($xmlimagesreal)) {
                            copy($xmlimagesreal, $imagepathdest);
                        }
                    }
                }
            }
        }
    }

    /**
     * a function to get clean string (i.e: removing unwanted characters) from the given string
     *
     *
     * @access   public
     * @param    string  $str
     * @param    boolean $isXMLtoJSON
     * @return   string
     *
     */
    function cleanQuestionTitle($str, $isXMLtoJSON = false) {
        $URLSearchString = " a-zA-Z0-9\(\)\[\]\:\;\&\/\-\?\.\=\_\~\#\'/^\s*";
        $new_string = preg_replace("[^$URLSearchString]", '', $str);

        if ($isXMLtoJSON) {
            $new_string = str_replace('&amp;', '&', $new_string);
            $new_string = str_replace('&nbsp;', ' ', $new_string);
        } else {
            $new_string = str_replace(' ', '&nbsp;', $new_string);
            $new_string = str_replace('&', '&amp;', $new_string);
        }

        return $new_string;
    }

    /**
     * a function to get database xml as dom xml
     *
     *
     * @access   public
     * @param    integer $questid
     * @return   void
     *
     */
    function getQuestXmlAsDom($questid = 0, $return = false) {

        $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($questid, '-1'), 'details');
        return $this->FromatQuestionXml($QuestDetail['XMLData'], $return);
    }

    function FromatQuestionXml($sXMLInput, $return = false) {
        header('Content-type: text/xml; charset=UTF-8');
        $qt = new Question();
        $sXMLInput = $qt->removeMediaPlaceHolder($sXMLInput);
        ;
        $sXMLInput = $this->spanRemove($sXMLInput);

        $this->myDebug("This is xml input");
        $this->myDebug($sXMLInput);
        if (!$this->validateXml($sXMLInput)) {
            $sXMLInput = utf8_encode($sXMLInput);
        }
        if ($return) {
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $sXMLInput;
        } else {
            print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $sXMLInput;
            die;
        }
    }

    /** a function to get json data of a question from database
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer $QuestID
     * @param    integer $CatID
     * @param    string  $customCss
     * @return   string
     *
     */
    function getQuestionJson($QuestID, $CatID, $customCss = '', $entityID = '', $entityTypeID = '') {
        global $DBCONFIG;
        $qst = new Question();
        $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($QuestID, '-1'), 'details');
        $this->myDebug($QuestDetail);
        $qtp = new QuestionTemplate();
        if ($DBCONFIG->dbType == 'Oracle')
            $QuestionTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.ID = {$CatID} ", "qt.\"HTMLTemplate\" , qt.\"TemplateFile\" , qt.\"JSONData\" ", 'details');
        else
            $QuestionTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.ID = '{$CatID}' ", "qt.HTMLTemplate , qt.TemplateFile , qt.JSONData ", 'details');

        if ($customCss != '') {
            $customCss = str_replace('../assessmentimages', '../../renditions/html/css/' . strtolower($this->getEntityName($entityTypeID)) . '/' . $entityID . '/assessmentimages', $customCss);
            $unique = uniqid();
            $file = $this->cfg->rootPath . '/' . $this->cfgApp->QuizCSSImageUnzipTempLocation . $unique . '.css';
            $handle = fopen($file, 'w');
            fwrite($handle, $customCss);
            fclose($handle);
            $file = $this->cfg->wwwroot . '/' . $this->cfgApp->QuizCSSImageUnzipTempLocation . $unique . '.css';
        } else {
            $file = '';
        }

        $JSONData = ($QuestID == 0 ) ? $QuestionTemplate['JSONData'] : $QuestDetail['JSONData'];
        $JSONData = $qst->removeMediaPlaceHolder($JSONData);
        return $QuestionTemplate['TemplateTitle'] . '||||||||' . $JSONData . '||||||||' . $QuestionTemplate['HTMLTemplate'] . '||||||||' . $CatID . '||||||||' . $file;
    }

    /**
     * a function to copy css for an assessment to the required destination
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    integer $quizid
     * @return   void
     *
     */
    function copyCss($entityID, $entityTypeID = 2) {
        global $CONFIG, $APPCONFIG;
        $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;
        $entityType = strtolower($this->getEntityName($entityTypeID));
        //for flash
        //$csspath = $CONFIG->rootPath.'/'. $APPCONFIG->UserQuizCSSLocation.$entityType.'/';
        $csspath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizFlashCSS . $entityType . '/';
        //print "aa--".$csspath;die;
        if (!is_dir($csspath)) {
            mkdir($csspath, 0777, true);
        }

        if (!is_dir($csspath . $entityID)) {
            mkdir($csspath . $entityID, 0777);
            $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->QuizCSSLocation, $csspath . $entityID);
        }
        //for html
        //$csspath = $CONFIG->rootPath.'/'. $APPCONFIG->UserQuizCSSLocationforHtml.$entityType.'/';
        $this->myDebug("Css Path");
        $this->myDebug($csspath);
        $csspath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizHtmlCSS . $entityType . '/';

        if (!is_dir($csspath)) {
            mkdir($csspath, 0777);
        }

        $this->myDebug("This is css path");
        $this->myDebug($csspath . $entityID);
        if (!is_dir($csspath . $entityID)) {
            mkdir($csspath . $entityID, 0777, true);
            $this->dirCopy($APPCONFIG->QuizCSSLocationforHtml, $csspath . $entityID);
        }
    }

    /**
     * a function to convert html entities to its another equivalent html entity compatible to IE
     *
     *
     * @access   public
     * @param    string  $xmlimagespath
     * @return   string
     *
     */
    function formatIE($xmlimagespath) {
        $xmlimagespath = str_replace('<strong>', '<b>', $xmlimagespath);
        $xmlimagespath = str_replace('</strong>', '</b>', $xmlimagespath);
        $xmlimagespath = str_replace('<em>', '<i>', $xmlimagespath);
        $xmlimagespath = str_replace('</em>', '</i>', $xmlimagespath);

        $xmlimagespath = str_replace('&lt;strong&gt;', '&lt;b&gt;', $xmlimagespath);
        $xmlimagespath = str_replace('&lt;/strong&gt;', '&lt;/b&gt;', $xmlimagespath);
        $xmlimagespath = str_replace('&lt;em&gt;', '&lt;i&gt;', $xmlimagespath);
        $xmlimagespath = str_replace('&lt;/em&gt;', '&lt;/i&gt;', $xmlimagespath);

        return $xmlimagespath;
    }

    /**
     * a function to convert the xml of a question to its equivalent json
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $QuestID
     * @param    string  $QuestXml
     * @param    integer $QuestTypeID
     * @return   mixed
     *
     */
    //getPegasusXmlToJson
    function getXmlToJsonForPegasus($QuestID, $QuestXml = '', $QuestTypeID = '') {
        global $DBCONFIG;
        try {
            $this->myDebug("This is Question Type");
            $this->myDebug($QuestTypeID);
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $objJSONtmp = new Services_JSON();
            $qtp = new QuestionTemplate();
            $qt = new Question();
            if ((int) $QuestID > 0) {
                $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($QuestID, '-1'), 'details');
                $QuestXml = $qt->removeMediaPlaceHolder($QuestDetail['XMLData']);
                $QuestTypeID = $QuestDetail['QuestionTemplateID'];
                $QTitle = $QuestDetail['Title'];
            } else {
                if (!preg_match('/<screen[^>]*>(.*?)<\/screen>/i', $QuestXml)) {
                    $QuestXml = "<screen>{$QuestXml}</screen>";
                }
                $QTitle = '';
            }
            $this->myDebug("This is Question Xml");
            $this->myDebug($QuestXml);
            if ($DBCONFIG->dbType == 'Oracle') {
                $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = ''{$QuestTypeID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" ,qt.\"JSONSchema\" ,tc.\"CategoryCode\" ,qt.\"JSONStructure\" , qt.\"isStatic\" ", 'details');
            } else {
                $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile ,qt.JSONSchema ,tc.CategoryCode ,qt.JSONStructure , qt.isStatic ", 'details');
            }

            $QTypeShortName = $queTemplate['CategoryCode'];
            //$JsonXmlSchema  = $queTemplate['JSONSchema'];

            if ($QTypeShortName == 'ESSAY') {
                $JsonXmlSchema = '{
                                        "question_title" : 
                                        {
                                            "row_position" : "2" ,
                                            "row_text" : "question_title",
                                            "type" : "normal" 
                                        },
                                        "question_text" : 
                                        {
                                            "row_position" : "5" ,
                                            "row_text" : "question_text",
                                            "type" : "normal" 
                                        },     
                                        "essay" : 
                                        {
                                            "row_position" : "6" ,
                                            "row_text" : "essay",
                                            "type" : "normal" 
                                        },                                         
                                        "correct_feedback" : 
                                        {
                                            "row_position" : "7" ,
                                            "row_text" : "correct_feedback",
                                            "type" : "normal" 
                                        },
                                        "hint" : 
                                        {
                                            "row_position" : "16" ,
                                            "row_text" : "hint",
                                            "type" : "normal" 
                                        }
                                    }';
                $json1 = '{
                                  "question_title": "",
                                  "question_text": "",
                                  "instruction_text": "",
                                  "essay":"",
                                  "correct_feedback":"",
                                  "metadata": 
                                  [
                                     {
                                        "text": "Score",
                                        "val": ""
                                     },
                                     {
                                        "text": "Difficulty",
                                        "val": ""
                                     },
                                     {
                                        "text": "Page_Reference",
                                        "val": ""
                                     },
                                     {
                                        "text": "Topic",
                                        "val": ""
                                     },
                                     {
                                        "text": "Skill",
                                        "val": ""
                                     },
                                     {
                                        "text": "Objective",
                                        "val": ""
                                     }
                                  ],
                                  "hint": "",
                                  "notes_editor": ""
                            } ';
                $metaJson = '{
                                    "metadata": 
                                    [
                                        {
                                            "row_position": "9",
                                            "row_text": "Score"
                                        },
                                        {
                                            "row_position": "11",
                                            "row_text": "Difficulty"
                                        },
                                        {
                                            "row_position": "12",
                                            "row_text": "Page_Reference"
                                        },
                                        {
                                            "row_position": "13",
                                            "row_text": "Topic"
                                        },
                                        {
                                            "row_position": "14",
                                            "row_text": "Skill"
                                        },
                                        {
                                            "row_position": "15",
                                            "row_text": "Objective"
                                        }
                                    ]
                                 }';
                $taxonomyJson = '{
                                 "taxonomy" : 
                                 {
                                    "row_position" : "19" ,
                                    "row_text" : "Taxonomy"
                                 }   
                              }';
            }
            if ($QTypeShortName == 'MCSS' || $QTypeShortName == 'TF') {

                $JsonXmlSchema = '{
                                        "question_title" : 
                                        {
                                            "row_position" : "2" ,
                                            "row_text" : "question_title",
                                            "type" : "normal" 
                                        },
                                        "question_text" : 
                                        {
                                            "row_position" : "5" ,
                                            "row_text" : "question_text",
                                            "type" : "normal" 
                                        },
                                        "choices" : 
                                        {
                                            "row_position" : "6" ,
                                            "row_text" : "choices",
                                            "type" : "special" 
                                        },  
                                        "hint" : 
                                        {
                                            "row_position" : "14" ,
                                            "row_text" : "hint",
                                            "type" : "normal" 
                                        }
                                      }';

                $json1 = '{
                                  "question_title": "",
                                  "question_text": "",
                                  "instruction_text": "",
                                  "choices": true,
                                  "metadata": 
                                  [
                                     {
                                        "text": "Difficulty",
                                        "val": ""
                                     },
                                     {
                                        "text": "Page_Reference",
                                        "val": ""
                                     },
                                     {
                                        "text": "Topic",
                                        "val": ""
                                     },
                                     {
                                        "text": "Skill",
                                        "val": ""
                                     },
                                     {
                                        "text": "Objective",
                                        "val": ""
                                     }
                                  ],
                                  "hint": "",
                                  "notes_editor": ""
                               } ';
                $metaJson = '{
                                    "metadata": 
                                    [
                                        {
                                            "row_position": "9",
                                            "row_text": "Difficulty"
                                        },
                                        {
                                            "row_position": "10",
                                            "row_text": "Page_Reference"
                                        },
                                        {
                                            "row_position": "11",
                                            "row_text": "Topic"
                                        },
                                        {
                                            "row_position": "12",
                                            "row_text": "Skill"
                                        },
                                        {
                                            "row_position": "13",
                                            "row_text": "Objective"
                                        }
                                    ]
                                 }';
                $taxonomyJson = '{
                                 "taxonomy" : 
                                 {
                                    "row_position" : "17" ,
                                    "row_text" : "Taxonomy"
                                 }   
                              }';
            }



            //$json1          = $queTemplate['JSONStructure'];
            $LayoutXML = $queTemplate['TemplateFile'];
            $isStatic = $queTemplate['isStatic'];
            $objxmltable = simplexml_load_string($QuestXml);
            $objJson = $objJSONtmp->decode($json1);


            $elemJsonroot = $objJSONtmp->decode(stripslashes($JsonXmlSchema));
            $this->myDebug($elemJsonroot);
            $this->myDebug('Word XML object============');
            $this->myDebug($objxmltable);



            if (!empty($elemJsonroot)) {
                foreach ($elemJsonroot as $elemJsonnode) {

                    $this->mydebug("This is an element");
                    $this->mydebug($elemJsonnode);
                    $row_position = $elemJsonnode->{'row_position'};
                    $row_text = $elemJsonnode->{'row_text'};
                    $row_type = $elemJsonnode->{'type'};
                    //  echo ($row_position.'---'.$row_text.'---'.$row_type).'<br>';
                    //if($row_type == 'special')continue;
                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/para");

                    if ($row_text == 'choices') {
                        list($objxmlnode1) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/table/table_details");

                        if (isset($objxmlnode1)) {

                            $rownum = 0;
                            $str = "";

                            foreach ($objxmlnode1->row as $rowc) {
                                $rownum++;
                                if ($rownum == 1)
                                    continue;


                                if (in_array($QTypeShortName, array('TF'))) {
                                    list($tmpxml) = $rowc->xpath("col[@position='3']/para");

                                    $optionstext = $this->getRowTextFromWord($tmpxml);

                                    list($tmpxml) = $rowc->xpath("col[@position='2']/para");
                                    $optionsbool = $this->getRowTextFromWord($tmpxml, true);

                                    if (in_array($QTypeShortName, array('TF'))) {
                                        $optionsbool = ($optionsbool) ? true : false;
                                    }


                                    $arrChoice[] = array('val1' => $optionsbool, 'val2' => (string) $optionstext, 'val3' => '');
                                } else if ($QTypeShortName == 'MCSS') {

                                    list($tmpxml) = $rowc->xpath("col[@position='2']/para");

                                    $optionstext = $this->getRowTextFromWord($tmpxml);


                                    list($tmpxml) = $rowc->xpath("col[@position='3']/para");
                                    $optionsbool = $this->getRowTextFromWord($tmpxml, true);
                                    $optionsbool = ($optionsbool) ? true : false;


                                    list($tmpxml) = $rowc->xpath("col[@position='4']/para");
                                    $optionsFeedback = $this->getRowTextFromWord($tmpxml, true);

                                    $arrChoice[] = array('val1' => $optionsbool, 'val2' => (string) $optionstext, 'val3' => $optionsFeedback);
                                }
                            }
                        }
                    } else if (isset($objxmlnode)) {
                        $objJson->{$row_text} = $this->getRowTextFromWord($objxmlnode);
                        $this->myDebug('Question Json Loop=========');
                        $this->myDebug($objJson->{$row_text});
                    }

                    if ($QTypeShortName == 'MCSS' || $QTypeShortName == 'TF') {
                        $objJson->{'choices'} = $arrChoice;
                    }
                }
            }


            $objJson->{'question_title'} = $this->cleanQuestionTitle($objJson->{'question_title'}, true);
            $objJson->{'question_title'} = str_replace('&ldquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('&rdquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('"', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace("'", "&#39;", $objJson->{'question_title'});

            $objJson->{'question_title'} = str_replace('&ldquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('&rdquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('"', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace("'", "&#39;", $objJson->{'question_title'});

            $objJson->{'question_title'} = str_replace('&ldquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('&rdquo;', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace('"', "&quot;", $objJson->{'question_title'});
            $objJson->{'question_title'} = str_replace("'", "&#39;", $objJson->{'question_title'});



            //Scrore Details
            if (isset($metaJson)) {
                $m = 0;
                $metaJson = $objJSONtmp->decode(stripslashes($metaJson));
                $k = 0;
                foreach ($metaJson->metadata as $metaJsonnode) {

                    $this->mydebug("This is an element");
                    $this->mydebug($metaJsonnode);
                    $row_position = $metaJsonnode->{'row_position'};
                    $row_text = $metaJsonnode->{'row_text'};

                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/para");

                    if (isset($objxmlnode)) {
                        $quest = $this->getRowTextFromWord($objxmlnode, true);

                        if (($row_text != '') && $row_text != null && trim($quest) != '') {
                            $metaarr[$k]['text'] = $row_text;
                            $quest = str_replace('&ldquo;', "&quot;", $quest);
                            $quest = str_replace('&rdquo;', "&quot;", $quest);
                            $quest = str_replace('"', "&quot;", $quest);
                            $quest = str_replace("'", "&#39;", $quest);

                            $quest = str_replace('&ldquo;', "&quot;", $quest);
                            $quest = str_replace('&rdquo;', "&quot;", $quest);
                            $quest = str_replace('"', "&quot;", $quest);
                            $quest = str_replace("'", "&#39;", $quest);

                            $quest = str_replace('&ldquo;', "&quot;", $quest);
                            $quest = str_replace('&rdquo;', "&quot;", $quest);
                            $quest = str_replace('"', "&quot;", $quest);
                            $quest = str_replace("'", "&#39;", $quest);

                            $quest = str_replace('&ldquo;', "&quot;", $quest);
                            $quest = str_replace('&rdquo;', "&quot;", $quest);
                            $quest = str_replace('"', "&quot;", $quest);
                            $quest = str_replace("'", "&#39;", $quest);
                            $metaarr[$k]['val'] = (trim($quest) == '') ? '' : $quest;

                            /* $objJson->{'metadata'}[$m]->{'text'} = $row_text;
                              $objJson->{'metadata'}[$m]->{'val'}  = (trim($quest) == '')? '' : $quest;
                              $m++; */
                            $k++;
                        }
                    }
                }

                if ($QTypeShortName == 'MCSS' || $QTypeShortName == 'TF') {
                    $metaarr[$k]['text'] = 'Score';
                    $metaarr[$k]['val'] = 1;
                }
                $objJson->{'metadata'} = $metaarr;
            }


            if ($taxonomyJson) {

                $taxoJson = $objJSONtmp->decode(stripslashes($taxonomyJson));


                foreach ($taxoJson as $taxoJsonnode) {

                    $row_position = $taxoJsonnode->{'row_position'};
                    $row_text = $taxoJsonnode->{'row_text'};
                    $row_type = $taxoJsonnode->{'type'};

                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/para");
                    if ($row_text == 'Taxonomy') {
                        list($objxmlnode1) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/table/table_details");

                        if (isset($objxmlnode1)) {

                            $rownum = 0;
                            $str = "";
                            foreach ($objxmlnode1->row as $rowc) {

                                $rownum++;
                                $m = 0;
                                foreach ($rowc->col as $colp) {
                                    if ($rownum == 1) {
                                        $objJson->{'taxonomy'}[$m]->{'text'} = $this->getRowTextFromWord($colp->para);
                                    } else {
                                        $objJson->{'taxonomy'}[$m]->{'val'} = $this->getRowTextFromWord($colp->para);
                                    }
                                    $m++;
                                }
                            }
                        }
                    }
                }
            }

            //XML Conversion Code end
            $this->myDebug('Question Json=========');
            $this->myDebug($objJson);
            $this->myDebug('Question Json string=========');
            //$objJson = json_encode($objJson);
            // echo "<pre>";
            //echo "Array----<br />";
            //print_r($objJson);

            $objJson = $objJSONtmp->encode($objJson);

            //echo "JSON----<br />";
            //print_r($objJson);

            return $objJson;
            $this->myDebug($objJson);
        } catch (exception $ex) {
            $this->catchException($ex);
            return;
        }
    }

    /**
     * a function to convert the json of a question to its equivalent xml
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $QuestID
     * @param    string  $QuestJson
     * @param    integer $QuestTypeID
     * @return   mixed
     *
     */
    function getJsonToXml($QuestID, $QuestJson = '', $QuestTypeID = '') {
        global $DBCONFIG;
        try {
            $this->myDebug("GetJsonToXml");
            $this->myDebug($QuestJson);
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $QuestXML = 'NA';
            $this->myDebug($QuestID . '--' . $QuestTypeID);

            if ($QuestJson == '') {
                $QuestJson = $this->getQuestionJson($QuestID, $QuestTypeID);
                $arr = explode('||||||||', $QuestJson);
                $QuestJson = $arr[1];
                $QuestTypeID = $arr[3];
                $this->myDebug($QuestJson);
            }

            $qtp = new QuestionTemplate();
            if ($DBCONFIG->dbType == 'Oracle') {
                $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = ''{$QuestTypeID}'' ", " qt.\"HTMLStructure\" , qt.\"FlashStructure\", qt.\"HTMLTemplate\" , qt.\"TemplateFile\", qt.\"JSONSchema\" , qt.\"isStatic\" ", 'details');
            } else {
                $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile,qt.JSONSchema , qt.isStatic", 'details');
            }

            $JsonXmlSchema = $queTemplate['JSONSchema'];
            $TemplateFile = $queTemplate['TemplateFile'];
            $isStatic = $queTemplate['isStatic'];
            $objJSONtmp = new Services_JSON();
            $objJson = $objJSONtmp->decode($QuestJson);

            if (!isset($objJson)
	    )
		die('error');

            //Edit quest online creating error due to utf8 conversion, code will be corrected
            if (!file_exists($CONFIG->rootPath . '/' . $APPCONFIG->LayoutXMLLocation . $TemplateFile . '.xml')) {
                return 'NA';
            }
            $objxmltable = simplexml_load_file($CONFIG->rootPath . '/' . $APPCONFIG->LayoutXMLLocation . $TemplateFile . '.xml');
            list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='1']/col[@position='2']");
            $objxmlnode = $this->createXmlnodeForwordTemp($objxmlnode, $QuestID);
            $elemJsonroot = $objJSONtmp->decode(stripslashes($JsonXmlSchema));
            if (!empty($elemJsonroot)) {
                foreach ($elemJsonroot as $elemJsonnode) {
                    $row_position = $elemJsonnode->{'row_position'};
                    $row_text = $elemJsonnode->{'row_text'};
                    $row_type = $elemJsonnode->{'type'};

                    if ($row_type == 'special')
                        continue;
                if ($row_type == 'normaltab') {




                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']");

                        $objjsonTab = ($objJson->{$row_text});

                        if (count($objjsonTab) > 0) {



                            $tableheadoption = "";
                            foreach ($objjsonTab as $objTab) {




                                $text = $objTab->{"val1"};
                                $optionAlttag = $objTab->{"val2"};



                                $objxmlnode1 = $this->createXmlnodeForwordTemp($objxmlnode, $text);

				/* if ($optionAlttag != "") {


                                        $objxmlnode2 = $this->createXmlnodeForwordTemp($objxmlnode, $optionAlttag);

				  } */
                            }
                        }
                        //$objxmlnode = $this->createXmlnodeForwordTemp($tmpxml,$objJson->{$row_text});
		    } else {
                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']");
                    if (isset($objxmlnode)) {
//                        if($row_text == 'question_title')
//                        {
//                            $objxmlnode[0] = '';
//                            $getTitle = $this->cleanQuestionTitle($objJson->{$row_text},true);
//                          //  $getTitle = "<span><![CDATA[".$getTitle."]]></span>";
//                              $getTitle = $getTitle;
//                            $objxmlnode->addChild('para',$getTitle);
//                        }
//                        else
//                        { 
                        //$objxmlnode = $this->createXmlnodeForwordTemp($objxmlnode, $objJson->{$row_text});
                        //}
                    }
                    }
                }
            }

            //Scrore Details
            if (isset($elemJsonroot->{'score'})) {
                $scorepos = $elemJsonroot->{'score'}->{'row_position'};
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$scorepos}']/col[@position='2']");
                if (isset($objxmlnode)) {
                    $objxmlnode = $this->createXmlnodeForwordTemp($objxmlnode, $objJson->{'metadata'}[0]->{'val'});
                }
            }

            //Image Details
            if (isset($elemJsonroot->{'image'})) {
                $imagepos = $elemJsonroot->{'image'}->{'row_position'};
                $mediapath = $this->getImageUrl($objJson->{'image'}, 'src');
                $mediadesc = $this->getImageUrl($objJson->{'image'}, 'alt');
                $mediatitle = $this->getImageUrl($objJson->{'image'}, 'title');
                $this->myDebug("This is media title");
                $this->myDebug($mediatitle);
                //Image Description
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$imagepos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='1']");
                if (isset($objxmlnode)) {
                    $xmlimages = ($mediapath != '') ? $mediadesc : 'No Image Uploaded';
                    $objxmlnode = $this->createXmlnodeForwordTemp($objxmlnode, $xmlimages);
                }

                //Image View Link
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$imagepos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='2']");
                if (isset($objxmlnode)) {
                    if ($mediapath != '') {
                        $objxmlnode[0] = '';
                        $objxmlnode->addChild('para');
                        $objxmlnode->para->addChild('a', 'View');
                        $objxmlnode->para->a->addAttribute('href', $mediapath);
                        $objxmlnode->para->a->addAttribute('title', $mediatitle);
                    }
                }
            }

            //Video Detailsq
            if (isset($elemJsonroot->{'video'})) {
                $videopos = $elemJsonroot->{'video'}->{'row_position'};
                if (preg_match('/\<object/i', $objJson->{'video'})) {
                    $mediapath = $this->getVideoUrl($objJson->{'video'});
                    $mediatitle = $this->getVideoUrl($objJson->{'video'}, 'title');
                } else {
                    $mediapath = $this->getImageUrl($objJson->{'video'}, 'src');
                    $mediadesc = $this->getImageUrl($objJson->{'video'}, 'alt');
                    $mediatitle = $this->getImageUrl($objJson->{'video'}, 'title');
                }

                //Video Description
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$videopos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='1']");

                //Video View Link
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$videopos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='2']");

                if (isset($objxmlnode)) {
                    if ($mediapath != '') {
                        $objxmlnode[0] = '';
                        $objxmlnode->addChild('para');
                        $objxmlnode->para->addChild('a', 'View');
                        $objxmlnode->para->a->addAttribute('href', $mediapath);
                        $objxmlnode->para->a->addAttribute('title', $mediatitle);
                    }
                }
            }

            //XML Conversion Code
            if (is_file($CONFIG->rootPath . '/' . $APPCONFIG->QuestionTemplateResourcePath . "{$TemplateFile}.php") && $isStatic == 'N') {
                $this->myDebug("For choice.");
                $this->myDebug($CONFIG->rootPath . '/' . $APPCONFIG->QuestionTemplateResourcePath . "{$TemplateFile}.php");
                include_once $CONFIG->rootPath . '/' . $APPCONFIG->QuestionTemplateResourcePath . "{$TemplateFile}.php";
                $objQuesType = new $TemplateFile();
                $conversiondata = $objQuesType->conversionJsonToXml($objJson, $objxmltable, $elemJsonroot);
                $this->myDebug("Converted Js");
                $this->myDebug($conversiondata);
                $objxmltable = $conversiondata['objxmltable'];
                $tableheadoption = $conversiondata['choicestablexml'];
            } else {
                $tableheadoption = '';
            }

            //XML Conversion Code end
            list($objxmlnode) = $objxmltable->xpath('table');
            $QuestID = (isset($QuestID)) ? $QuestID : 0;
            $XMLData = "<screen template_id=\"{$TemplateFile}\" screen_id=\"{$QuestID}\">" . str_replace("__HeckGood__", $tableheadoption, $objxmlnode->asXML()) . '</screen>';

            return $XMLData;
        } catch (Exception $ex) {
            $this->catchException($ex);
            return 'NA';
        }
    }

    /**
     * a function to convert the xml of a question to its equivalent json
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $QuestID
     * @param    string  $QuestXml
     * @param    integer $QuestTypeID
     * @return   mixed
     *
     */
    function getXmlToJson($QuestID, $QuestXml = '', $QuestTypeID = '') {
        global $DBCONFIG;
        try {
            $this->myDebug("This is Question Type");
            $this->myDebug($QuestTypeID);
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $objJSONtmp = new Services_JSON();
            $qtp = new QuestionTemplate();
            $qt = new Question();
            if ((int) $QuestID > 0) {
                $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($QuestID, '-1'), 'details');
                $QuestXml = $qt->removeMediaPlaceHolder($QuestDetail['XMLData']);
                $QuestTypeID = $QuestDetail['QuestionTemplateID'];
                $QTitle = $QuestDetail['Title'];
            } else {
                if (!preg_match('/<screen[^>]*>(.*?)<\/screen>/i', $QuestXml)) {
                    $QuestXml = "<screen>{$QuestXml}</screen>";
                }
                $QTitle = '';
            }
            $this->myDebug("This is Question Xml");
            $this->myDebug($QuestXml);
            if ($DBCONFIG->dbType == 'Oracle') {
                $queTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.ID = ''{$QuestTypeID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"TemplateFile\" ,qt.\"JSONSchema\" ,tc.\"CategoryCode\" ,qt.\"JSONStructure\" , qt.\"isStatic\" ", 'details');
            } else {
                $queTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$QuestTypeID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.TemplateFile ,qt.JSONSchema ,tc.CategoryCode ,qt.JSONStructure , qt.isStatic ", 'details');
            }

            $QTypeShortName = $queTemplate['CategoryCode'];
            $JsonXmlSchema = $queTemplate['JSONSchema'];
            $json1 = $queTemplate['JSONStructure'];
            $LayoutXML = $queTemplate['TemplateFile'];
            $isStatic = $queTemplate['isStatic'];
            $objxmltable = simplexml_load_string($QuestXml);
            $objJson = $objJSONtmp->decode($json1);
            $elemJsonroot = $objJSONtmp->decode(stripslashes($JsonXmlSchema));
            $this->myDebug($elemJsonroot);
            $this->myDebug('Word XML object============');
            $this->myDebug($objxmltable);

            if (!empty($elemJsonroot)) {
                foreach ($elemJsonroot as $elemJsonnode) {
                    $this->mydebug("This is an element");
                    $this->mydebug($elemJsonnode);
                    $row_position = $elemJsonnode->{'row_position'};
                    $row_text = $elemJsonnode->{'row_text'};
                    $row_type = $elemJsonnode->{'type'};
                    $this->myDebug($row_position . '---' . $row_text . '---' . $row_type);

                    if ($row_type == 'special'
		    )
			continue;
                    list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$row_position}']/col[@position='2']/para");
                    $this->myDebug("Actual Values");
                    $this->myDebug($objxmlnode);
                    if (isset($objxmlnode)) {
                        $objJson->{$row_text} = $this->getRowTextFromWord($objxmlnode);
                        $this->myDebug('Question Json Loop=========');
                        $this->myDebug($objJson->{$row_text});
                    }
                }
            }


            if ($isStatic == 'Y') {
                $objJson->{'question_title'} = $QTitle;
            } else {
                $objJson->{'question_title'} = $this->cleanQuestionTitle($objJson->{'question_title'}, true);
            }
            $objJson->{'question_title'} = htmlspecialchars($objJson->{'question_title'});

            //Scrore Details
            if (isset($elemJsonroot->{'score'})) {
                $scorepos = $elemJsonroot->{'score'}->{'row_position'};
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$scorepos}']/col[@position='2']/para");
                if (isset($objxmlnode)) {
                    $quest = $this->getRowTextFromWord($objxmlnode, true);
                    $objJson->{'metadata'}[0]->{'text'} = 'Score';
                    $objJson->{'metadata'}[0]->{'val'} = (trim($quest) == '') ? 0 : $quest;
                    $objJson->{'metadata'}[1]->{'text'} = 'Difficulty';
                    $objJson->{'metadata'}[1]->{'val'} = 'easy';
                    $objJson->{'metadata'}[2]->{'text'} = 'Learning Object';
                    $objJson->{'metadata'}[2]->{'val'} = '';
                }
            }

            //Image Details
            if (isset($objJson->{'image'})) {
                $imagepos = $elemJsonroot->{'image'}->{'row_position'};
                //image url
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$imagepos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='2']/para/a");
                $imagehref = $this->getAttribute($objxmlnode, "href");
                $imagetitle = $this->getAttribute($objxmlnode, 'title');
                $this->myDebug("Image Tagging");
                $this->myDebug($objxmlnode);
                $this->myDebug($imagetitle);
                //image description
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$imagepos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='1']/para");
                $imagedesc = $this->getRowTextFromWord($objxmlnode, true);
                $objJson->{'image'} = $this->createImageObject($imagehref, $imagedesc, $imagetitle);
            }

            //Video Details
            if (isset($objJson->{'video'})) {
                $videopos = $elemJsonroot->{'video'}->{'row_position'};
                //image url
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$videopos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='2']/para/a");
                $imagehref = $this->getAttribute($objxmlnode, 'href');
                $imagetitle = $this->getAttribute($objxmlnode, 'title');
                //image description
                list($objxmlnode) = $objxmltable->xpath("table/table_details/row[@position='{$videopos}']/col[@position='2']/table/table_details/row[@position='2']/col[@position='1']/para");
                $imagedesc = $this->getRowTextFromWord($objxmlnode, true);
                $objJson->{'video'} = $this->createImageObject($imagehref, $imagedesc, $imagetitle);
                //$objJson->{'video'} = $this->createVideoObject($imagehref,$imagedesc);
            }

            //XML Conversion Code
            if (is_file($CONFIG->rootPath . '/' . $APPCONFIG->QuestionTemplateResourcePath . "/{$LayoutXML}.php") && $isStatic == 'N') {
                include_once $CONFIG->rootPath . '/' . $APPCONFIG->QuestionTemplateResourcePath . "/{$LayoutXML}.php";
                $objQuesType = new $LayoutXML();
                $conversiondata = $objQuesType->conversionXmlToJson($objJson, $objxmltable, $elemJsonroot);
                $objJson = $conversiondata['objJson'];
            }

            //XML Conversion Code end
            $this->myDebug('Question Json=========');
            $this->myDebug($objJson);
            $this->myDebug('Question Json string=========');
            $objJson = $objJSONtmp->encode($objJson);
            $this->myDebug($objJson);
            return $objJson;
        } catch (exception $ex) {
            $this->catchException($ex);
            return;
        }
    }

    /**
     * a function to handle flash rendition preview/publish
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   string
     *
     */
    function flashRendition($input, $quizid, $action, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        try {
            global $CONFIG, $APPCONFIG, $DBCONFIG;
            $qt = new Question();

            $this->myDebug("Parameter----$quizid--$action---$publishtype---$publishname--$mode---$noofquest");
            $random = ($mode == 2) ? 'yes' : 'no';
            $randomQuestCount = $randquest;
            $prevSource = $input['prevsource'];
            $this->myDebug('This is flash input');
            $this->myDEbug($input);
            if ($prevSource == "wordTemplate") {
                $tokenID = $input['accessLogID'];
                $tokenCode = $input['accessToken'];
                $transactionID = $input['transactionID'];
                $prevSource = "wordTemplate";
                $guid = uniqid();
                if ($DBCONFIG->dbType == 'Oracle') {
                    $sqlQry = "SELECT * FROM AccessTokens WHERE \"AccessToken\" = '{$tokenCode}' and \"AccessLogID\" = '{$tokenID}' ";
                } else {
                    $sqlQry = "SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenID}' ";
                }
                $tokenInfo = $this->db->getSingleRow($sqlQry);
                $this->user_info = json_decode($tokenInfo['UserInfo']);
                //Check Whether QuizCSS exist if not then copy default css folder and css.zip
                //$this->copyCss($quizid, 2);
                $eid = $this->getEntityId('Question');
                $this->myDebug("This is DB type");
                $this->myDebug($DBCONFIG);
                if ($DBCONFIG->dbType == 'Oracle') {
                    $filter .= " (qtp.\"RenditionMode\" != ''Html'' ) ";
                    $filter .= ( $questids != '') ? " and wtq.\"ID\" in ({$questids}) " : '';
                    $questions = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $filter, $tokenID, $transactionID, ' wtq."XMLData" , wtq."JSONData" , qtp."HTMLTemplate" , qtp."RenditionMode", qtp."isStatic" , tpc."CategoryCode" '), 'nocount');
                } else {
                    $filter .= " (qtp.Renditionmode!='Html') ";
                    $filter .= ( $questids != '') ? " and wtq.ID in ({$questids}) " : '';
                    $questions = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $filter, $tokenID, $transactionID, ' wtq.XMLData , wtq.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode '), 'nocount');
                }

                $i = 1;
                //files Copy start
                $pathname = $this->copyFilesFlashRendition($quizid, $guid, $publishtype);
                $this->myDebug('Path------' . $pathname);
                // shell xml creation start
                $topbanner = 106;
                $type = $input['Type'];
                $qformative = ($type == '2' ) ? 'yes' : 'no';
                $qsummative = ($type == '1' ) ? 'yes' : 'no';
                $qmoveback = ($input['MoveBack'] == '1' ) ? 'yes' : 'no';
                $qshuffle = ($input['ShuffleOptions'] == '1' ) ? 'yes' : 'no';
                $qskip = ($input['SkipQ'] == '1' ) ? 'yes' : 'no';
                $qscore = ($input['Score'] == '1' ) ? 'yes' : 'no';
                $qtotalscore = $input['TotalScore'];
                $qpassingscore = $input['PassingScore'];
                $qpartial = ($input['PartialScore'] == '1' ) ? 'yes' : 'no';
                $qattempt = $input['Tries'];
                $qqlevel = ($input['QLevel'] == '1' ) ? 'yes' : 'no';
                $qolevel = ($input['OLevel'] == '1' ) ? 'yes' : 'no';
                $qtryagain = $input['TryAgain'];
                $qper1 = $input['Scorebox1'];
                $qper2 = $input['Scorebox2'];
                $qper3 = $input['Scorebox3'];
                $qmessage1 = $input['Message1'];
                $qmessage2 = $input['Message2'];
                $qmessage3 = $input['Message3'];
                $action1 = $input['Action1'];
                $action2 = $input['Action2'];
                $action3 = $input['Action3'];
                $HelpMessage = $input['HelpMessage'];

                if ($DBCONFIG->dbType == 'Oracle') {
                    $stFilter = " qtp.\"isStatic\" = ''Y'' ";
                    $stFilter .= ( $questids != '') ? "  and wtq.ID  in ({$questids})" : '';
                    $questiontemps = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $stFilter, $tokenID, $transactionID, '-1'), 'list');
                } else {
                    $stFilter = " qtp.isStatic = 'Y' ";
                    $stFilter .= ( $questids != '') ? "  and wtq.ID  in ({$questids})" : '';
                    $questiontemps = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $stFilter, $tokenID, $transactionID, '-1'), 'list');
                }

                $staticpagecount = $questiontemps['TC'];
            } else {
                $AsmtDetail = $this->db->executeStoreProcedure('AssessmentDetails', array(
                            $quizid,
                            $this->session->getValue('userID'),
                            $this->session->getValue('isAdmin'),
                            $this->session->getValue('instID')
                                ), 'nocount');
                $AssessmentSettings = $AsmtDetail;
                $isDirector = 'no';
                if ($action == 'publishq') {
                    if ($this->checkRight('AsmtPublish') == false) {
                        return 'No Assessment access';
                    }

                    if ($DBCONFIG->dbType == 'Oracle') {
                        $data = array(
                            'UserID' => $this->session->getValue('userID'),
                            'AssessmentID' => $quizid,
                            'PublishMode' => $mode,
                            'PublishType' => $publishtype,
                            'PublishedTitle' => $publishname,
                            'RenditionType' => 'Flash',
                            'TotalQuestions' => $totalquest,
                            'RandomQuestionCount' => $randomQuestCount,
                            'AddDate' => $this->currentDate(),
                            'ModBY' => $this->session->getValue('userID'),
                            'ModDate' => $this->currentDate(),
                            'isActive' => 'Y',
                            'isEnabled' => 1
                        );
                    } else {
                        $data = array(
                            'UserID' => $this->session->getValue('userID'),
                            'AssessmentID' => $quizid,
                            'PublishMode' => $mode,
                            'PublishType' => $publishtype,
                            'PublishedTitle' => $publishname,
                            'RenditionType' => 'Flash',
                            'TotalQuestions' => $totalquest,
                            'RandomQuestionCount' => $randomQuestCount,
                            'AddDate' => $this->currentDate(),
                            'ModBy' => $this->session->getValue('userID'),
                            'ModDate' => $this->currentDate(),
                            'isActive' => 'Y',
                            'isEnabled' => '1'
                        );
                    }


                    $this->myDebug($data);
                    $publinkid = $this->db->insert('PublishAssessments', $data);
                    $guid = $publinkid;
                    $this->myDebug('Publish--' . $publinkid);
                }

                if ($action == 'previewq') {
                    $this->myDebug('Preview------');
                    $guid = uniqid();
                    if ($this->checkRight('AsmtPreview') == false) {
                        return 'No Assessment access';
                    }
                    $publishtype = 'previewq';
                }

                //Check Whether QuizCSS exist if not then copy default css folder and css.zip
                //$this->copyCss($quizid, 2);
                $eid = $this->getEntityId('Question');
                if ($DBCONFIG->dbType == 'Oracle') {
                    $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
                    $filter .= " AND ( qtp.\"RenditionMode\" != ''Html'' ) ";
                    $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
		    $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst."XMLData" , qst."JSONData" , qtp."HTMLTemplate" , qtp."RenditionMode", qtp."isStatic" , tpc."CategoryCode" '), 'nocount'
                    );
                } else {
                    $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
                    $filter .= " AND (qtp.RenditionMode !='Html') ";
                    $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
		    $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst.XMLData , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode '), 'nocount'
                    );
                }
                $i = 1;

                //files Copy start
                $pathname = $this->copyFilesFlashRendition($quizid, $guid, $publishtype);
                $this->myDebug('Path------' . $pathname);

                if ($publishtype == 'CDROM-SWF') {
                    $QuizName = $this->getValueArray($AsmtDetail, 'AssessmentName');
                    $QuizTitle = $this->getValueArray($AsmtDetail, 'AssessmentTitle');
                    $QuizDisplayName = ($QuizTitle == '') ? $QuizName : $QuizTitle;
                    $this->addQuizTitletoSwfpublish($pathname . '/Intro.html', $QuizDisplayName);
                    $this->addQuizTitletoSwfpublish($pathname . '/index.html', $QuizDisplayName);
                }
                // shell xml creation start
                $topbanner = 106;
                $type = $this->getValueArray($AssessmentSettings, 'Type');
                $qformative = ($type == '2' ) ? 'yes' : 'no';
                $qsummative = ($type == '1' ) ? 'yes' : 'no';
                $qmoveback = ($this->getAssociateValue($AssessmentSettings, 'MoveBack') == '1' ) ? 'yes' : 'no';
                $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == '1' ) ? 'yes' : 'no';
                $qskip = ($this->getAssociateValue($AssessmentSettings, 'SkipQ') == '1' ) ? 'yes' : 'no';
                $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == '1' ) ? 'yes' : 'no';
                $qtotalscore = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
                $qpassingscore = $this->getAssociateValue($AssessmentSettings, 'PassingScore');
                $qpartial = ($this->getAssociateValue($AssessmentSettings, 'PartialScore') == '1' ) ? 'yes' : 'no';
                $qattempt = $this->getAssociateValue($AssessmentSettings, 'Tries');
                $qqlevel = ($this->getAssociateValue($AssessmentSettings, 'QLevel') == '1' ) ? 'yes' : 'no';
                $qolevel = ($this->getAssociateValue($AssessmentSettings, 'OLevel') == '1' ) ? 'yes' : 'no';
                $qtryagain = $this->getAssociateValue($AssessmentSettings, 'TryAgain');
                $qper1 = $this->getAssociateValue($AssessmentSettings, 'Scorebox1');
                $qper2 = $this->getAssociateValue($AssessmentSettings, 'Scorebox2');
                $qper3 = $this->getAssociateValue($AssessmentSettings, 'Scorebox3');
                $qmessage1 = $this->getAssociateValue($AssessmentSettings, 'Message1');
                $qmessage2 = $this->getAssociateValue($AssessmentSettings, 'Message2');
                $qmessage3 = $this->getAssociateValue($AssessmentSettings, 'Message3');
                $action1 = $this->getAssociateValue($AssessmentSettings, 'Action1');
                $action2 = $this->getAssociateValue($AssessmentSettings, 'Action2');
                $action3 = $this->getAssociateValue($AssessmentSettings, 'Action3');
                $HelpMessage = $this->getAssociateValue($AssessmentSettings, 'HelpMessage');
                $staticpagecount = ($mode == 2) ? 0 : $this->getStaticPageCount($quizid, $questids);
            }
            $finalxml1 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                            <manifest>
                                <quiz>
                                    <summative>" . $qsummative . "</summative>
                                    <formative>" . $qformative . "</formative>
                                    <shuffle>" . $qshuffle . "</shuffle>
                                    <backMove>" . $qmoveback . "</backMove>
                                    <skip>" . $qskip . "</skip>
                                    <showTickCross>" . $TicksCross . "</showTickCross>
                                    <score>
                                        <questionScore>" . $qscore . "</questionScore>
                                        <specifyScore>
                                            <totalScore>" . $qtotalscore . "</totalScore>
                                            <passingScore>" . $qpassingscore . "</passingScore>
                                        </specifyScore>
                                    </score>
                                    <partial>" . $qpartial . "</partial>
                                    <attempt>" . $qattempt . "</attempt>
                                    <feedbackOption>
                                            <questionLevel>" . $qqlevel . "</questionLevel>
                                            <optionLevel>" . $qolevel . "</optionLevel>
                                    </feedbackOption>
                                    <tryAgain>" . $qtryagain . "</tryAgain>
                                    <audio>off</audio>
                                    <summaryOption>
                                        <condition1>
                                                <percent>" . $qper1 . "</percent>
                                                <message><![CDATA[" . $qmessage1 . "]]></message>
                                                <action>" . $action1 . "</action>
                                        </condition1>
                                        <condition2>
                                                <percent>" . $qper1 . "</percent>
                                                <message><![CDATA[" . $qmessage2 . "]]></message>
                                                <action>" . $action2 . "</action>
                                        </condition2>
                                        <condition3>
                                                <percent>" . $qper3 . "</percent>
                                                <message><![CDATA[" . $qmessage3 . "]]></message>
                                                <action>" . $action3 . "</action>
                                        </condition3>
                                    </summaryOption>
                                    <random>" . $random . "</random>
                                    <questionData><![CDATA[Data/QuestionDetails.js]]></questionData>
                                    <randomQuestCount><![CDATA[{$randomQuestCount}]]></randomQuestCount>
                                    <totalStaticPages><![CDATA[{$staticpagecount}]]></totalStaticPages>
                                    <helpText><![CDATA[" . $HelpMessage . "]]></helpText>";

            $finalxml2 = '';
            $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;

            if (!empty($questions)) {
                foreach ($questions as $questlist) {
                    //Editing the Questions Learning Object Data.
                    $objJSONtmp = new Services_JSON();
                    $metaData = new Metadata();
                    $objJson2 = $objJSONtmp->decode($questlist['JSONData']);
//                    //$QuestLobList   = $this->db->executeStoreProcedure('MapRepositoryLobList',array($questlist['ID']));
//                    //$objJson2->{'metadata'}[2]->{'val'} = $this->getValueArray($QuestLobList['RS'],'lobName','multiple');
                    $this->myDebug('before any opearation');
                    $this->myDebug($questlist['JSONData']);
                    $questlist['JSONData'] = $objJSONtmp->encode($objJson2);
                    $this->myDebug('after encode');
                    $this->myDebug($questlist['JSONData']);
                    $questlist['JSONData'] = $qt->removeMediaPlaceHolder($questlist['JSONData']);
                    $this->myDebug('after media');
                    $this->myDebug($questlist['JSONData']);
                    if ($questlist['RenditionMode'] == 'Html') {
                        continue;
                    }
                    $multi = '';
                    //Random Type Quiz
                    if ($random == 'yes') {
                        if ($questlist['isStatic'] == 'Y')
                            continue;
                    }

                    $pathname2 = $pathname . '/' . $questlist['TemplateFile'];
                    $this->myDebug('Path2------' . $pathname2);


                    if (!is_dir($pathname2)) {
                        mkdir($pathname2, 0777);
                        ///@copy($CONFIG->rootPath.'/'.$APPCONFIG->UserQuizCSSLocation.strtolower($this->getEntityName(2)).'/'.$quizid.'/'.$questlist['TemplateFile'].'/'.$questlist['TemplateFile'].'.css',$pathname2.'/'.$questlist['TemplateFile'].'.css');
                        $cssPath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizFlashCSS . strtolower($this->getEntityName(2)) . '/' . $quizid;
                        @copy($cssPath . '/' . $questlist['TemplateFile'] . '/' . $questlist['TemplateFile'] . '.css', $pathname2 . '/' . $questlist['TemplateFile'] . '.css');
                        @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . $questlist['TemplateFile'] . '/' . $questlist['TemplateFile'] . '.swf', $pathname2 . '/' . $questlist['TemplateFile'] . '.swf');
                    }

                    $myFile = $pathname2 . '/' . $i . '.xml';
                    $myFile3 = $questlist['TemplateFile'] . '/' . $i . '.xml';
                    $fh = fopen($myFile, 'w');

                    //if($questlist['CategoryCode'] == 'LTD')
                    if ($questlist['qtID'] == '37' || $questlist['qtID'] == '38') {
                        $multi = $qt->removeMediaPlaceHolder($questlist['XMLData']);
                    } else {
                        $xslDoc = new DOMDocument('1.0');

                        $this->myDebug($questlist['TemplateFile'] . '.xsl');
                        /* $xslDoc->load($CONFIG->wwwroot.'/'.$APPCONFIG->RenditionXSLLocation.$questlist['TemplateFile'].'.xsl');
                          $xmlDoc = new DOMDocument('1.0');
                          $xslDoc->load($CONFIG->wwwroot.'/'.$APPCONFIG->RenditionXSLLocation.$questlist['TemplateFile'].'.xsl');
                          $xmlDoc = new DOMDocument('1.0');
                          if($xmlDoc->load($CONFIG->wwwroot.'/authoring/get-quest-xml-as-dom/'.$questlist['QuestionID']) == FALSE ){
                          continue;
                          } */
                        $xslDoc->load($CONFIG->rootPath . '/' . $APPCONFIG->RenditionXSLLocation . $questlist['TemplateFile'] . '.xsl');
                        $xmlDoc = new DOMDocument('1.0');
                        $fw = fopen($pathname . '/questionData.xml', 'w');
                        if ($prevSource == "wordTemplate") {
                            fwrite($fw, $this->FromatQuestionXml($questlist['XMLData'], true));
                        } else {
                            fwrite($fw, $this->getQuestXmlAsDom($questlist['QuestionID'], true));
                        }
                        fclose($fw);
                        if ($xmlDoc->load($pathname . '/questionData.xml') == FALSE) {
                            continue;
                        }
                        // some tags were not closed, etc.
                        $proc = new XSLTProcessor();
                        $proc->importStylesheet($xslDoc);
                        $multi .= $proc->transformToXML($xmlDoc);
                        //Copy Images to Preview Folder
                    }
                    $questionimagepath = $pathname2 . '/';


                    $this->myDebug('$questlist');
                    $this->myDebug($questionimagepath);
                    $this->myDebug($questlist);
                    $this->previewImageCopy($questlist['QuestionID'], $questlist['JSONData'], $questlist['qtID'], $questionimagepath, true); //not class
                    //Replace Image path to local path
                    if ($questlist['CategoryCode'] == 'LTD') {
                        $questionimagepath = '';
                    } else {
                        $questionimagepath = $questlist['TemplateFile'] . '/';
                    }
                    $multi = $this->createImagePathPreview($multi, $questionimagepath); // not class
                    //for Special char and format
                    $multi = $this->htmlEntityToHashCode($multi); //not class

                    $stringData = $multi;
                    fwrite($fh, $stringData);
                    fclose($fh);
                    $finalxml2 .= "<question maxAttempt=\"" . $qattempt . "\">";
                    $finalxml2 .= "<quiztitle><![CDATA[" . $QuizDisplayName . "]]></quiztitle>";
                    $finalxml2 .= "<templateName><![CDATA[" . $questlist['TemplateFile'] . "]]></templateName>";
                    $finalxml2 .= "<xmlPath><![CDATA[" . $myFile3 . "]]></xmlPath>";
                    $finalxml2 .= "<marks><![CDATA[2]]></marks>";
                    $finalxml2 .= "</question>";

                    $i++;
                }
            }

            $finalxml3 = '</quiz></manifest>';
            $myFile = $pathname . '/Shell.xml';
            $fh2 = fopen($myFile, 'w');
            $finalxml = $finalxml1 . $finalxml2 . $finalxml3;
            fwrite($fh2, $finalxml);
            fclose($fh2);
            if ($prevSource == "wordTemplate") {
                //create config xml file
                if ($input['Help'] == '1') {
                    $qhelp = 'true';
                } else {
                    $qhelp = 'false';
                }
                if ($input['Timer'] == '1') {
                    $qtimer = 'true';
                    $qminutes = $input['Minutes'];
                } else {
                    $qtimer = 'false';
                    $qminutes = '0';
                }
                if ($input['Map'] == '1') {
                    $qmap = 'true';
                } else {
                    $qmap = 'false';
                }
                if ($input['Flag'] == '1') {
                    $qflag = 'true';
                } else {
                    $qflag = 'false';
                }
                if ($input['Hint'] == '1') {
                    $qhint = 'true';
                } else {
                    $qhint = 'false';
                }
                if ($input['ShowQNo'] == '1') {
                    $qpagination = 'true';
                } else {
                    $qpagination = 'false';
                }
            } else {
                if ($this->getAssociateValue($AssessmentSettings, 'Help') == '1') {
                    $qhelp = 'true';
                } else {
                    $qhelp = 'false';
                }
                if ($this->getAssociateValue($AssessmentSettings, 'Timer') == '1') {
                    $qtimer = 'true';
                    $qminutes = $this->getAssociateValue($AssessmentSettings, 'Minutes');
                } else {
                    $qtimer = 'false';
                    $qminutes = '0';
                }
                if ($this->getAssociateValue($AssessmentSettings, 'Map') == '1') {
                    $qmap = 'true';
                } else {
                    $qmap = 'false';
                }
                if ($this->getAssociateValue($AssessmentSettings, 'Flag') == '1') {
                    $qflag = 'true';
                } else {
                    $qflag = 'false';
                }
                if ($this->getAssociateValue($AssessmentSettings, 'Hint') == '1') {
                    $qhint = 'true';
                } else {
                    $qhint = 'false';
                }
                if ($this->getAssociateValue($AssessmentSettings, 'ShowQNo') == '1') {
                    $qpagination = 'true';
                } else {
                    $qpagination = 'false';
                }
            }
            $ExitMessage = html_entity_decode(html_entity_decode($this->getAssociateValue($AssessmentSettings, 'ExitMessage')));
            $this->CopyMultiImage($ExitMessage, $pathname . '/');
            ///$ExitMessage    = str_replace($CONFIG->wwwroot.'/'.$APPCONFIG->EditorImagesUpload,'',$ExitMessage);
            $objMedia = new Media();
            //$ExitMessage    = str_replace($objMedia->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/', 'protocol' => 'http') ),'',$ExitMessage);
            $ExitMessage = str_replace($CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $instID . "/assets/images/original/", '', $ExitMessage);
            $configFiled = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                            <config>
                                <assetPath></assetPath>
                                <topBanner>
                                        <height>106</height>
                                </topBanner>
                                <footerButton>
                                    <print>
                                            <xPosition>20</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </print>
                                    <reset>
                                            <xPosition>50</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </reset>
                                    <help>
                                            <xPosition>80</xPosition>
                                            <visible>" . $this->getBoolean($qhelp) . "</visible>
                                    </help>
                                    <submit>
                                            <xPosition>680</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </submit>
                                    <previous>
                                            <xPosition>902</xPosition>
                                            <visible>" . $this->getBoolean($qmoveback) . "</visible>
                                    </previous>
                                    <next>
                                            <xPosition>930</xPosition>
                                            <visible>true</visible>
                                    </next>
                                    <timer>
                                            <xPosition>450</xPosition>
                                            <visible>" . $this->getBoolean($qtimer) . "</visible>
                                            <totalTime>{$qminutes}</totalTime><!--expect Number, we assume that given value in minute.(Ex : 20, means 20min.) -->
                                    </timer>
                                    <pagination>
                                            <xPosition>780</xPosition>
                                            <visible>" . $this->getBoolean($qpagination) . "</visible>
                                    </pagination>
                                    <flag>
                                            <xPosition>250</xPosition>
                                            <visible>" . $this->getBoolean($qflag) . "</visible>
                                    </flag>
                                    <quizMap>
                                            <xPosition>150</xPosition>
                                            <visible>" . $this->getBoolean($qmap) . "</visible>
                                    </quizMap>
                                    <hintRP>
                                            <xPosition>570</xPosition>
                                            <visible>false</visible>
                                    </hintRP>
                                    <hint>
                                            <xPosition>570</xPosition>
                                            <visible>" . $this->getBoolean($qhint) . "</visible>
                                    </hint>
                                </footerButton>
                                <isDirector>{$isDirector}</isDirector><!--yes/no -->
                                <timeOutMessage><![CDATA[Ooops! Time's up, click submit to view your results.]]></timeOutMessage>
                                <ExitMessage><![CDATA[{$ExitMessage}]]></ExitMessage>
                                <quizSubmitMessage><![CDATA[Are you sure you want to submit the quiz? If you do you will not be able to change your answers.]]></quizSubmitMessage>
                                <copyRightText><![CDATA[Copyright]]></copyRightText>
                                <toolTip>
                                    <print>
                                            <![CDATA[Print]]>
                                    </print>
                                    <reset>
                                            <![CDATA[Reset]]>
                                    </reset>
                                    <help>
                                            <![CDATA[Help]]>
                                    </help>
                                    <submit>
                                            <![CDATA[Submit]]>
                                    </submit>
                                    <previous>
                                            <![CDATA[Previous]]>
                                    </previous>
                                    <next>
                                            <![CDATA[Next]]>
                                    </next>
                                    <flag>
                                            <![CDATA[Flag]]>
                                    </flag>
                                    <quizMap>
                                            <![CDATA[Quiz map]]>
                                    </quizMap>
                                    <hint>
                                            <![CDATA[Hint]]>
                                    </hint>
                                    <fullText>
                                            <![CDATA[Full text]]>
                                    </fullText>
                                    <optionalFeedback>
                                                    <![CDATA[Optional feedback]]>
                                    </optionalFeedback>
                                    <magnifyImage>
                                                    <![CDATA[Magnify image]]>
                                    </magnifyImage>
                                    <imageDescription>
                                                    <![CDATA[Image description]]>
                                    </imageDescription>
                                    <videoDescription>
                                                    <![CDATA[Video description]]>
                                    </videoDescription>

                                    <magnifyVideo>
                                                    <![CDATA[Magnify video]]>
                                    </magnifyVideo>
                                    <closePopup>
                                                    <![CDATA[Close]]>
                                    </closePopup>
                                    <audio>
                                                    <![CDATA[Play audio]]>
                                    </audio>
                                    <tryAgain>
                                                    <![CDATA[Try again!]]>
                                    </tryAgain>
                                    <showAnswer>
                                                    <![CDATA[Show answer]]>
                                    </showAnswer>
                                    <sources>
                                                    <![CDATA[Show sources]]>
                                    </sources>
                                    <examinerComment>
                                                    <![CDATA[Examiner's comments]]>
                                    </examinerComment>
                                </toolTip>
                            </config>";

            $configFile = $pathname . '/Config.xml';
            $fh3 = fopen($configFile, 'w');
            fwrite($fh3, $configFiled);
            fclose($fh3);

            if ($action == 'previewq') {
                $dataPath = $APPCONFIG->tempDataPath;
            } else {
                $dataPath = $APPCONFIG->PersistDataPath;
            }
            $rootPath = $CONFIG->rootPath . '/' . $dataPath . $instID . "/";
            $webPath = $CONFIG->wwwroot . '/' . $dataPath . $instID . "/";
            $this->myDEbug("Prev source" . $prevSource . "Action" . $action);
            $this->myDebug("{$webPath}{$APPCONFIG->QuizPreviewPath}{$guid}{$APPCONFIG->PreviewLink}{$quizid}");
            $this->mydebug("{$webPath}{$APPCONFIG->QuizPreviewPath}{$guid}{$APPCONFIG->PreviewLink}{$quizid}");
            if ($prevSource == "wordTemplate") {
                return "{$webPath}{$APPCONFIG->QuizPreviewPath}{$guid}{$APPCONFIG->PreviewLink}{$quizid}";
            } else {
                if ($action == 'publishq') {
                    if ($DBCONFIG->dbType == 'Oracle')
                        $this->db->execute("UPDATE Assessments SET \"Status\" ='Published' WHERE ID=$quizid and \"isEnabled\" = 1 ");  //Set status to published.
 else
                        $this->db->execute("UPDATE Assessments SET status='Published' WHERE ID='$quizid' and isEnabled = '1' ");  //Set status to published.
 if ($publishtype === 'CDROM-SWF' || $publishtype === 'CDROM-EXE') {
                        ///$zipfile    = $CONFIG->rootPath.'/'.$APPCONFIG->CdRomPublishPath."{$guid}.zip";
                        $zipfile = $rootPath . $APPCONFIG->CdRomPublishLocation . "{$guid}.zip";
                        ///$webzipfile = $CONFIG->wwwroot.'/'.$APPCONFIG->CdRomPublishPath."{$guid}.zip";
                        $webzipfile = $webPath . $APPCONFIG->CdRomPublishLocation . "{$guid}.zip";
                        $this->makeZip($pathname, $zipfile);
                        return $webzipfile;
                    } else {
                        ///return "{$CONFIG->wwwroot}/{$APPCONFIG->QuizPublishLocation}{$guid}{$APPCONFIG->PermaLink}{$guid}";
                        return "{$webPath}{$APPCONFIG->QuizPublishPath}{$guid}{$APPCONFIG->PermaLink}{$guid}";
                    }
                } else {
                    ///return "{$CONFIG->wwwroot}/{$APPCONFIG->QuizPreviewLocation}{$guid}{$APPCONFIG->PreviewLink}{$quizid}";
                    return "{$webPath}{$APPCONFIG->QuizPreviewPath}{$guid}{$APPCONFIG->PreviewLink}{$quizid}";
                }
            }
        } catch (exception $ex) {
            $this->catchException($ex);
            return;
        }
        //preview quiz end
    }

    /**
     * a function to handle html rendition preview/publish
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   void
     *
     */
    function htmlRendition($input, $quizid, $action, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        global $CONFIG, $APPCONFIG, $DBCONFIG;
        $qt = new Question();


        $randommetadatakey = ($input['randommetadatakey'] != "") ? json_decode($input['randommetadatakey']) : "";
        $randommetadataval = ($input['randommetadataval'] != "") ? json_decode($input['randommetadataval']) : "";
        $difficulty = ($input['randomdifficulty'] != "") ? json_decode($input['randomdifficulty']) : "";
        ;
        $queID = trim($questids, '|');
        $queID = explode('||', $queID);
        $queID = $this->removeBlankElements($queID);

        if (isset($input['quadplusids'])) {
            $quadplusids = $input['quadplusids'];
            $quadplusids = str_replace('||', ',', $quadplusids);
            $quadplusids = trim($quadplusids, '|');
        } else {
            $quadplusids = '';
        }
        $this->myDebug("---Action----");
        $this->myDebug($action);
        $questCount = array();
        $questionID = array();
        $randomCriteria = array();
        $questionIDs = array();

        if (!empty($randommetadatakey)) {
            foreach ($randommetadatakey as $key => $val) {
                $name = trim($val->name);
                $questCount['mk_' . $name] = trim($val->count);
                $questionID['mk_' . $name] = $this->getQuestionID($name, $quizid, 'metadatakey', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['mk_' . $name]));
                $randomCriteria['mkey'][$name] = array('randcount' => $questCount['mk_' . $name], 'queID' => array_keys($questionID['mk_' . $name]));
            }
        }

        if (!empty($randommetadataval)) {
            foreach ($randommetadataval as $key => $val) {
                $name = trim($val->name);
                $questCount['mv_' . $name] = trim($val->count);
                $questionID['mv_' . $name] = $this->getQuestionID($name, $quizid, 'metadataval', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['mv_' . $name]));
                $randomCriteria['mval'][$name] = array('randcount' => $questCount['mv_' . $name], 'queID' => array_keys($questionID['mv_' . $name]));
            }
        }

        $level = '';
        if (!empty($difficulty)) {
            foreach ($difficulty as $key => $val) {
                $level .= "'" . trim($val->name) . "',";
                $name = trim($val->name);
                $questCount['dl_' . $name] = trim($val->count);
                $questionID['dl_' . $name] = $this->getQuestionID($name, $quizid, 'difficulty', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['dl_' . $name]));
                $randomCriteria['diff'][$name] = array('randcount' => $questCount['dl_' . $name], 'queID' => array_keys($questionID['dl_' . $name]));
            }
            $level = rtrim($level, ',');
        }

        $randomCriteria['added'] = array_unique($questionIDs);
        $this->myDebug('level json');
        $this->myDebug($randomCriteria);
        $random = ($mode == 2) ? 'yes' : 'no';
        $randomQuestCount = $randquest;

        $prevSource = $input['prevsource'];

        $this->myDebug("Prev Source=====>" . $prevSource);
        $this->myDEbug('This is  html REndition Input');
        $this->myDEbug($input);
        if ($prevSource == "wordTemplate") {
            $this->myDebug("Next Resource ");
            $guid = uniqid();
            $publishtype = $action;
            $tokenID = $input['accessLogID'];
            $tokenCode = $input['accessToken'];
            $transactionID = $input['transactionID'];
            $prevSource = "wordTemplate";
            //$this->copyCss($quizid, 2);
            $eid = $this->getEntityId('Question');
            $this->myDebug('WordTemplate dbconfig');
            $this->myDebug($DBCONFIG);
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter .= " (qtp.\"RenditionMode\"!=''Flash'') ";
                $filter .= ( $questids != '') ? " and wtq.\"ID\" in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $filter, $tokenID, $transactionID, ' wtq."XMLData" , wtq."JSONData" , qtp."HTMLTemplate" , qtp."RenditionMode", qtp."isStatic" , tpc."CategoryCode" '), 'nocount');
            } else {
                $filter .= " (qtp.Renditionmode!='Flash') ";
                $filter .= ( $questids != '') ? " and wtq.ID in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $filter, $tokenID, $transactionID, ' wtq.XMLData , wtq.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode '), 'nocount');
            }
            $i = 1;
            //files Copy start
            $assessmentpath = $this->copyFilesHtmlRendition($quizid, $guid, $publishtype);
            // shell xml creation start
            $topbanner = 85;

            $type = $input['Type'];
            $qformative = ($type == '2' ) ? 'yes' : 'no';
            $qsummative = ($type == '1' ) ? 'yes' : 'no';
            $qmoveback = ($input['MoveBack'] == '1' ) ? 'yes' : 'no';
            $qshuffle = ($input['ShuffleOptions'] == '1' ) ? 'yes' : 'no';
            $qskip = ($input['SkipQ'] == '1' ) ? 'yes' : 'no';
            $qscore = ($input['Score'] == '1' ) ? 'yes' : 'no';
            $qtotalscore = $input['TotalScore'];
            $qpassingscore = $input['PassingScore'];
            $qpartial = ($input['PartialScore'] == '1' ) ? 'yes' : 'no';
            $qattempt = $input['Tries'];
            //$qattempt           = ($publishtype == 'CDROM-HTML' ) ? '3': $input['Tries'];
            $qqlevel = ($input['QLevel'] == '1' ) ? 'yes' : 'no';
            $qolevel = ($input['OLevel'] == '1' ) ? 'yes' : 'no';
            $qtryagain = $input['TryAgain'];
            $qper0 = 0;
            $qper1 = $input['Scorebox1'];
            $qper2 = $input['Scorebox2'];
            $qper3 = $input['Scorebox3'];
            $qper4 = $input['Scorebox4'];
            $qper5 = 100;
            $qmessage1 = $input['Message1'];
            $qmessage2 = $input['Message2'];
            $qmessage3 = $input['Message3'];
            $action1 = $input['Action1'];
            $action2 = $input['Action2'];
            $action3 = $input['Action3'];
            $HelpMessage = $input['HelpMessage'];
            $TicksCross = ( $input['TicksCross'] == '1' ) ? 'yes' : 'no';

            if ($DBCONFIG->dbType == 'Oracle') {
                $stFilter = " qtp.\"isStatic\" = ''Y'' ";
            } else {
                $stFilter = " qtp.isStatic = 'Y' ";
            }
            $stFilter .= ( $questids != '') ? "  and wtq.ID  in ({$questids})" : '';
            $questiontemps = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $stFilter, $tokenID, $transactionID, '-1'), 'list');
            $staticpagecount = $questiontemps['TC'];
        } else {

	    $AsmtDetail = $this->db->executeStoreProcedure('AssessmentDetails', array($quizid, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount');
            $AssessmentSettings = $AsmtDetail;
            $isDirector = 'no';

            if ($action == 'publishq') {
                // $isDirector = ($publishtype == 'CDROM-HTML' ) ? 'yes':'no';
                $isDirector = 'no'; // for demo answerverification from js

                if ($this->checkRight('AsmtPublish') == false) {
                    return 'No Assessment access';
                }

                if ($DBCONFIG->dbType == 'Oracle') {
                    $data = array(
                        'UserID' => $this->session->getValue('userID'),
                        'AssessmentID' => $quizid,
                        'PublishMode' => $mode,
                        'PublishType' => $publishtype,
                        'PublishedTitle' => $publishname,
                        'RenditionType' => 'Html',
                        'TotalQuestions' => $totalquest,
                        'RandomQuestionCount' => $randomQuestCount,
                        'AddDate' => $this->currentDate(),
                        'ModBY' => $this->session->getValue('userID'),
                        'ModDate' => $this->currentDate(),
                        'isActive' => 'Y',
                        'isEnabled' => 1
                    );
                } else {
                    $data = array(
                        'UserID' => $this->session->getValue('userID'),
                        'AssessmentID' => $quizid,
                        'PublishMode' => $mode,
                        'PublishType' => $publishtype,
                        'PublishedTitle' => $publishname,
                        'RenditionType' => 'Html',
                        'TotalQuestions' => $totalquest,
                        'RandomQuestionCount' => $randomQuestCount,
                        'AddDate' => $this->currentDate(),
                        'ModBy' => $this->session->getValue('userID'),
                        'ModDate' => $this->currentDate(),
                        'isActive' => 'Y',
                        'isEnabled' => 1,
                        'Url' => $urlhtml
                    );
                }

                $publinkid = $this->db->insert('PublishAssessments', $data);
                $guid = $publinkid;

                // check whether logged in user had Multiple Qaud Plus Access
                if ($this->registry->site->hasMultipleQuadPlus()) {
                    if (!$quadplusids) {
                        // get All the Quad Plus
                        $arrQuadPlusList = $this->getQuadPlusList();
                        if (!empty($arrQuadPlusList)) {
                            foreach ($arrQuadPlusList as $quadPlusList) {
                                $quadplusids .= $quadPlusList['QPID'] . ',';
                            }
                            $quadplusids = trim($quadplusids, ',');
                        }
                    }

                    if ($quadplusids) {
                        $arrQuadPlusID = @explode(',', $quadplusids);
                        if (!empty($arrQuadPlusID)) {
                            foreach ($arrQuadPlusID as $quadPlusID) {
                                $data = array(
                                    'PublishAsmtID' => $guid,
                                    'QuadPlusId' => $quadPlusID,
                                    'AddBY' => $this->session->getValue('userID'),
                                    'AddDate' => $this->currentDate(),
                                    'ModBY' => $this->session->getValue('userID'),
                                    'ModDate' => $this->currentDate(),
                                    'isActive' => 'Y',
                                    'isEnabled' => 1
                                );
                                $mapPublishAsmtQuadPlus = $this->db->insert('MapPublishAsmtQuadPlus', $data);
                            }
                        }
                    }
                }
            }//  if($action == 'publishq')

            if ($action == 'previewq') {
                $guid = uniqid();
                if ($this->checkRight('AsmtPreview') == false) {
                    return 'No Assessment access';
                }
                $publishtype = $action;
            }

            //Check Whether QuizCSS exist if not then copy default css folder and css.zip
            //$this->copyCss($quizid, 2);
            $eid = $this->getEntityId('Question');
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
                $filter .= " AND (qtp.\"RenditionMode\" != ''Flash'' ) ";
                $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst."XMLData" , qst."JSONData" , qtp."HTMLTemplate" , qtp."isStatic" , tpc."CategoryCode" '), 'nocount');
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
                $filter .= " AND (qtp.Renditionmode!='Flash') ";
                $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst.XMLData , qst.JSONData , qtp.HTMLTemplate , qtp.isStatic , tpc.CategoryCode '), 'nocount');
            }
            $i = 1;
            //files Copy start
            $assessmentpath = $this->copyFilesHtmlRendition($quizid, $guid, $publishtype);
            // shell xml creation start
            $topbanner = 85;
            $type = $this->getValueArray($AssessmentSettings, 'Type');
            $qformative = ($type == '2' ) ? 'yes' : 'no';
            $qsummative = ($type == '1' ) ? 'yes' : 'no';
            $qmoveback = ($this->getAssociateValue($AssessmentSettings, 'MoveBack') == '1' ) ? 'yes' : 'no';
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == '1' ) ? 'yes' : 'no';
            $qskip = ($this->getAssociateValue($AssessmentSettings, 'SkipQ') == '1' ) ? 'yes' : 'no';
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == '1' ) ? 'yes' : 'no';
            $qtotalscore = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $qpassingscore = $this->getAssociateValue($AssessmentSettings, 'PassingScore');
            $qpartial = ($this->getAssociateValue($AssessmentSettings, 'PartialScore') == '1' ) ? 'yes' : 'no';
            $qattempt = ($publishtype == 'CDROM-HTML' ) ? '3' : $this->getAssociateValue($AssessmentSettings, 'Tries');
            $qqlevel = ($this->getAssociateValue($AssessmentSettings, 'QLevel') == '1' ) ? 'yes' : 'no';
            $qolevel = ($this->getAssociateValue($AssessmentSettings, 'OLevel') == '1' ) ? 'yes' : 'no';
            $qtryagain = $this->getAssociateValue($AssessmentSettings, 'TryAgain');
            $qper0 = 0;
            $qper1 = $this->getAssociateValue($AssessmentSettings, 'Scorebox1');
            $qper2 = $this->getAssociateValue($AssessmentSettings, 'Scorebox2');
            $qper3 = $this->getAssociateValue($AssessmentSettings, 'Scorebox3');
            $qper4 = $this->getAssociateValue($AssessmentSettings, 'Scorebox4');
            $qper5 = 100;
            $qmessage1 = $this->getAssociateValue($AssessmentSettings, 'Message1');
            $qmessage2 = $this->getAssociateValue($AssessmentSettings, 'Message2');
            $qmessage3 = $this->getAssociateValue($AssessmentSettings, 'Message3');
            $action1 = $this->getAssociateValue($AssessmentSettings, 'Action1');
            $action2 = $this->getAssociateValue($AssessmentSettings, 'Action2');
            $action3 = $this->getAssociateValue($AssessmentSettings, 'Action3');
            $HelpMessage = $this->getAssociateValue($AssessmentSettings, 'HelpMessage');
            $TicksCross = ($this->getAssociateValue($AssessmentSettings, 'TicksCross') == '1' ) ? 'yes' : 'no';
            $staticpagecount = ($mode == 2) ? 0 : $this->getStaticPageCount($quizid, $questids);
        }
        $this->myDebug("End Resource ");

        if ($publishname == '') {
            $QuizTitle = $this->getValueArray($AssessmentSettings, 'Title');
            $QuizTitle = ($QuizTitle == '') ? $this->getValueArray($AssessmentSettings, 'Name') : $QuizTitle;
        } else {
            $QuizTitle = $publishname;
        }

        $this->myDebug('this is complete question list - starts');
        $randomCriteria['remained'] = array_merge(array(), array_diff(array_keys($this->getValueArray($questions, 'QuestionID', 'multiple', 'array')), $randomCriteria['added']));
        $this->myDebug($randomCriteria);
        $this->myDebug('this is complete question list - ends');
        $totalScore = 0;
        $questiondata = '';
        $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;
        if (!empty($questions)) {
            foreach ($questions as $questlist) {
                //Editing the Questions Learning Object Data.
                $this->mydebug("Original Data");
                $this->mydebug($questlist['XMLData']);
                $this->mydebug($questlist['JSONData']);
                $questlist['XMLData'] = $qt->removeMediaPlaceHolder($questlist['XMLData']);
                $questlist['JSONData'] = $qt->removeMediaPlaceHolder($questlist['JSONData']);
                $this->mydebug("Change Data");
                $this->mydebug($questlist['XMLData']);
                $this->mydebug($questlist['JSONData']);

                if ($questlist['isStatic'] != 'Y') {
                    $objJSONtmp = new Services_JSON();
                    $metaData = new Metadata();
                    $objJson2 = $objJSONtmp->decode($questlist['JSONData']);
                    $questlist['JSONData'] = $objJSONtmp->encode($objJson2);
                }
                $multi = '';
                if ($questlist['HTMLTemplate'] == '' || $questlist['HTMLTemplate'] == null) {
                    continue;
                }
                //Random Type Quiz
                if ($random == 'yes') {
                    if ($questlist['isStatic'] == 'Y')
                        continue;
                }
                $totalAssessmentScore += (int) $objJson2->{'metadata'}[0]->{'val'};
                $randomQuestionIds .= $questlist['QuestionID'] . ',';
                $layoutpath = $assessmentpath . '/' . $questlist['HTMLTemplate'];

                if (!is_dir($layoutpath)) {
                    mkdir($layoutpath, 0777);
                    $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . $questlist['HTMLTemplate'], $layoutpath);
                    if (is_file($layoutpath . '/' . $questlist['HTMLTemplate'] . '.css')) {
                        unlink($layoutpath . '/' . $questlist['HTMLTemplate'] . '.css');
                    }
                    //@copy($CONFIG->rootPath.'/'.$APPCONFIG->UserQuizCSSLocationforHtml.strtolower($this->getEntityName(2)).'/'.$quizid.'/'.$questlist['HTMLTemplate'].'/'.$questlist['HTMLTemplate'].'.css',$layoutpath.'/'.$questlist['HTMLTemplate'].'.css');
                    @copy($CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName(2)) . '/' . $quizid . '/' . $questlist['HTMLTemplate'] . '/' . $questlist['HTMLTemplate'] . '.css', $layoutpath . '/' . $questlist['HTMLTemplate'] . '.css');
                }

                $questiondata .= '{';
                $questiondata .= '"timetaken" : 0,';
                $questiondata .= '"state" : null,';
                $questiondata .= "type : '{$questlist['TemplateTitle']}',";
                $questiondata .= "htmltemplate : '{$questlist['HTMLTemplate']}',";
                $questiondata .= "sectionname : '{$questlist['Section']}',";
                $questiondata .= "questionid : '{$questlist['QuestionID']}',";
                $questiondata .= "data : {$questlist['JSONData']}";
                $questiondata .= '},';

                //Copy published question to tblpublishquest dbtable for answer verification
                if ($action == 'publishq') {
                    //ID, PublishAssessmentID, QuestionID, QuestionTitle, XMLData, JSONData, QuestionTemplateID, UserID, AddDate, ModBY, ModDate, isEnabled
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $questlist['XMLData'] = preg_replace("/>([^<\w]*)</", '><', $questlist['XMLData']);
                        $questlist['XMLData'] = str_replace("\'", "''", (stripslashes($questlist['XMLData'])));

                        $this->db->executeClobProcedure("MANAGEPUBLISHQUESTIONS", array(0, $guid, $questlist['QuestionID'], $qt->addMediaPlaceHolder($questlist['XMLData']), $qt->addMediaPlaceHolder($questlist['JSONData']), $questlist['QuestionTemplateID'], $questlist['Title'], $this->session->getValue('userID'), $this->currentDate(), $this->session->getValue('userID'), $this->currentDate(), '1'));

                        /* $arrPublishedQuestion = array(
                          'PublishAssessmentID'   => $guid,
                          'QuestionID'            => $questlist['QuestionID'],
                          'XMLData'               => $qt->addMediaPlaceHolder($questlist['XMLData']),
                          'JSONData'              => $qt->addMediaPlaceHolder($questlist['JSONData']),
                          'QuestionTemplateID'    => $questlist['QuestionTemplateID'],
                          'Title'                 => $questlist['Title'],
                          'UserID'                => $this->session->getValue('userID'),
                          'AddDate'               => $this->currentDate(),
                          'ModBY'                 => $this->session->getValue('userID'),
                          'ModDate'               => $this->currentDate(),
                          'isEnabled'             => '1'
                          ); */
                    } else {
                        $arrPublishedQuestion = array(
                            'PublishAssessmentID' => $guid,
                            'QuestionID' => $questlist['QuestionID'],
                            'XMLData' => $qt->addMediaPlaceHolder($questlist['XMLData']),
                            'JSONData' => $qt->addMediaPlaceHolder($questlist['JSONData']),
                            'QuestionTemplateID' => $questlist['QuestionTemplateID'],
                            'Title' => $questlist['Title'],
                            'UserID' => $this->session->getValue('userID'),
                            'AddDate' => $this->currentDate(),
                            'ModBy' => $this->session->getValue('userID'),
                            'ModDate' => $this->currentDate(),
                            'isEnabled' => '1'
                        );
                        $status = $this->db->insert('PublishQuestions', $arrPublishedQuestion);
                    }
                }
                //Copy question code end
                //Copy Images to Preview Folder
                $questionimagepath = $assessmentpath . '/userimages/';
                if ($publishtype === 'CDROM-HTML') {
                    $multi = $this->previewImageCopy($questlist['QuestionID'], $questlist['JSONData'], $questlist['QuestionTemplateID'], $questionimagepath, true);
                }

                //Replace Image path to local path
                $questionimagepath = '../userimages/';
                if ($publishtype === 'CDROM-HTML') {
                    $questiondata = $this->createImagePathPreview(str_replace('\\', '', $questiondata), $questionimagepath);
                }
                $i++;
            }
        }

        $randomCriteria['QuestionIds'] = rtrim($randomQuestionIds, ',');
        $randomCriteria = json_encode($randomCriteria);

        if ($action == 'publishq') {
            if ($DBCONFIG->dbType == 'Oracle')
                $this->db->execute("UPDATE PublishAssessments SET \"RandomQuestionCriteria\" = '$randomCriteria' WHERE ID = '$guid' ");
            else
                $this->db->execute("UPDATE PublishAssessments SET RandomQuestionCriteria='$randomCriteria' WHERE ID='$guid' ");
        }

        $shellxml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                            <manifest>
                            <quiz>
                                <assessmentTitle>" . $QuizTitle . "</assessmentTitle>
                                <summative>" . $qsummative . "</summative>
                                <formative>" . $qformative . "</formative>
                                <shuffle>" . $qshuffle . "</shuffle>
                                <backMove>" . $qmoveback . "</backMove>
                                <skip>" . $qskip . "</skip>
                                <showTickCross>" . $TicksCross . "</showTickCross>
                                <score>
                                    <questionScore>" . $qscore . "</questionScore>
                                    <specifyScore>
                                            <totalScore>" . $qtotalscore . "</totalScore>
                                            <passingScore>" . $qpassingscore . "</passingScore>
                                    </specifyScore>
                                </score>
                                <partial>" . $qpartial . "</partial>
                                <attempt>" . $qattempt . "</attempt>
                                <feedbackOption>
                                        <questionLevel>" . $qqlevel . "</questionLevel>
                                        <optionLevel>" . $qolevel . "</optionLevel>
                                </feedbackOption>
                                <tryAgain>" . $qtryagain . "</tryAgain>
                                <audio>off</audio>
                                <summaryOption>
                                    <condition1>
                                            <percent>" . $qper0 . "-" . $qper1 . "</percent>
                                            <message><![CDATA[" . $qmessage1 . "]]></message>
                                            <action>" . $action1 . "</action>
                                    </condition1>
                                    <condition2>
                                            <percent>" . $qper2 . "-" . $qper3 . "</percent>
                                            <message><![CDATA[" . $qmessage2 . "]]></message>
                                            <action>" . $action2 . "</action>
                                    </condition2>
                                    <condition3>
                                            <percent>" . $qper4 . "-" . $qper5 . "</percent>
                                            <message><![CDATA[" . $qmessage3 . "]]></message>
                                            <action>" . $action3 . "</action>
                                    </condition3>
                                </summaryOption>
                                <random>" . $random . "</random>
                                <randomCriteria><![CDATA[{$randomCriteria}]]></randomCriteria>
                                <questionData><![CDATA[Data/QuestionDetails.js]]></questionData>
                                <randomQuestCount><![CDATA[{$randomQuestCount}]]></randomQuestCount>
                                <totalStaticPages><![CDATA[{$staticpagecount}]]></totalStaticPages>
                                <helpText><![CDATA[" . $HelpMessage . "]]></helpText>
                                <totalquest><![CDATA[{$totalquest}]]></totalquest>
                                <totalAssessmentScore><![CDATA[{$totalAssessmentScore}]]></totalAssessmentScore>
                            </quiz></manifest>";
        $shellxmlpath = $assessmentpath . '/data/shell.xml';
        $fh2 = fopen($shellxmlpath, 'w');
        fwrite($fh2, $shellxml);
        fclose($fh2);

        $objshellxml = simplexml_load_string($shellxml, null, LIBXML_NOCDATA);
        $converter = new DataConverter();
        $shellarray = $converter->convertXmlToArray($objshellxml->asXML());
        $objJSON = new Services_JSON();
        $shellJSON = $objJSON->encode($shellarray);
        $shelljsonpath = $assessmentpath . '/data/Shell.js';
        $fh2 = fopen($shelljsonpath, 'w');
        fwrite($fh2, "var AstShellJSON= {$shellJSON};");
        fclose($fh2);
        if ($prevSource == "wordTemplate") {
            //create config xml file
            if ($input['Help'] == '1') {
                $qhelp = 'true';
            } else {
                $qhelp = 'false';
            }
            if ($input['Timer'] == '1') {
                $qtimer = 'true';
                $qminutes = $input['Minutes'];
            } else {
                $qtimer = 'false';
                $qminutes = '0';
            }
            if ($input['Map'] == '1') {
                $qmap = 'true';
            } else {
                $qmap = 'false';
            }
            if ($input['Flag'] == '1') {
                $qflag = 'true';
            } else {
                $qflag = 'false';
            }
            if ($input['Hint'] == '1') {
                $qhint = 'true';
            } else {
                $qhint = 'false';
            }
            if ($input['ShowQNo'] == '1') {
                $qpagination = 'true';
            } else {
                $qpagination = 'false';
            }
        } else {
            //create config xml file
            if ($this->getAssociateValue($AssessmentSettings, 'Help') == '1') {
                $qhelp = 'true';
            } else {
                $qhelp = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Timer') == '1') {
                $qtimer = 'true';
                $qminutes = $this->getAssociateValue($AssessmentSettings, 'Minutes');
            } else {
                $qtimer = 'false';
                $qminutes = '0';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Map') == '1') {
                $qmap = 'true';
            } else {
                $qmap = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Flag') == '1') {
                $qflag = 'true';
            } else {
                $qflag = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Hint') == '1') {
                $qhint = 'true';
            } else {
                $qhint = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'ShowQNo') == '1') {
                $qpagination = 'true';
            } else {
                $qpagination = 'false';
            }
        }
        $qseconds = (int) $qminutes * 60;

        $questiondata = substr($questiondata, 0, -1);

        if ($action == 'publishq') {
            //$xmlPath = ($publishtype == 'CDROM-HTML' )? "var xmlPath = '';" : "var xmlPath = '{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPublishLocation}{$guid}/';";
            $dataPath = $APPCONFIG->PersistDataPath;
            $xmlPath = ($publishtype == 'CDROM-HTML' ) ? "var xmlPath = '';" : "var xmlPath = '{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizHtmlPublishPath}{$guid}/';";
        } else {
            ///$xmlPath = "var xmlPath = '{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPreviewLocation}{$guid}/';";
            $dataPath = $APPCONFIG->tempDataPath;
            $xmlPath = "var xmlPath = '{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizHtmlPreviewPath}{$guid}/';";
        }

        $quadCookie = 'var quadCookie= "' . md5(uniqid()) . '";';
        $asmtInfo = "var AsmtInfoObject = {\"title\":\"{$QuizTitle}\",\"score\":\"{$totalAssessmentScore}\",\"time\":\"{$qseconds}\",\"questions\":\"{$totalquest}\"};";
        $this->mydebug("This is Question details.");
        $this->mydebug($asmtInfo);
        $this->mydebug("This is trial questions");
        $this->mydebug($questiondata);
        $questionjs = "var QuestionJS = [{$questiondata}];";
        $questiondetails = "{$xmlPath} {$quadCookie} {$asmtInfo} {$questionjs}";
        $questionjspath = $assessmentpath . '/data/QuestionDetails.js';
        $fh2 = fopen($questionjspath, 'w');
        fwrite($fh2, $questiondetails);
        fclose($fh2);

        $ExitMessage = html_entity_decode(html_entity_decode($this->getAssociateValue($AssessmentSettings, 'ExitMessage')));
        $this->CopyMultiImage($ExitMessage, $pathname . '/');
        ///$ExitMessage = str_replace($CONFIG->wwwroot.'/'.$APPCONFIG->EditorImagesUpload,'',$ExitMessage);
        $ExitMessage = str_replace($CONFIG->wwwroot . '/' . $dataPath . $instID . "/assets/images/original/", '', $ExitMessage);

        $configFiled = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><config>
                                <assetPath></assetPath>
                                <topBanner>
                                        <height>{$topbanner}</height>
                                </topBanner>
                                <footerButton>
                                        <print>
                                                <xPosition>20</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </print>
                                        <reset>
                                                <xPosition>50</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </reset>
                                        <help>
                                                <xPosition>80</xPosition>
                                                <visible>" . $this->getBoolean($qhelp) . "</visible>
                                        </help>
                                        <submit>
                                                <xPosition>680</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </submit>
                                        <previous>
                                                <xPosition>902</xPosition>
                                                <visible>" . $this->getBoolean($qmoveback) . "</visible>
                                        </previous>
                                        <next>
                                                <xPosition>930</xPosition>
                                                <visible>true</visible>
                                        </next>
                                        <timer>
                                                <xPosition>450</xPosition>
                                                <visible>" . $this->getBoolean($qtimer) . "</visible>
                                                <totalTime>{$qseconds}</totalTime><!--expect Number, we assume that given value in second.(Ex : 600, means 10min.) -->
                                        </timer>
                                        <pagination>
                                            <xPosition>780</xPosition>
                                                <visible>" . $this->getBoolean($qpagination) . "</visible>
                                        </pagination>
                                        <flag>
                                                <xPosition>250</xPosition>
                                                <visible>" . $this->getBoolean($qflag) . "</visible>
                                        </flag>
                                        <quizMap>
                                                <xPosition>150</xPosition>
                                                <visible>" . $this->getBoolean($qmap) . "</visible>
                                        </quizMap>
                                        <hintRP>
                                                <xPosition>570</xPosition>
                                                <visible>false</visible>
                                        </hintRP>
                                        <hint>
                                                <xPosition>570</xPosition>
                                                <visible>" . $this->getBoolean($qhint) . "</visible>
                                        </hint>
                                    </footerButton>
                                    <isDirector>{$isDirector}</isDirector><!--yes/no -->
                                    <timeOutMessage><![CDATA[Ooops! Time's up, click submit to view your results.]]></timeOutMessage>
                                    <ExitMessage><![CDATA[{$ExitMessage}]]></ExitMessage>
                                    <quizSubmitMessage><![CDATA[Are you sure you want to submit the quiz? If you do you will not be able to change your answers.]]></quizSubmitMessage>
                                    <copyRightText><![CDATA[Copyright]]></copyRightText>
                                    <toolTip>
                                        <print>
                                                <![CDATA[Print]]>
                                        </print>
                                        <reset>
                                                <![CDATA[Reset]]>
                                        </reset>
                                        <help>
                                                <![CDATA[Help]]>
                                        </help>
                                        <submit>
                                                <![CDATA[Submit]]>
                                        </submit>
                                        <previous>
                                                <![CDATA[Previous]]>
                                        </previous>
                                        <next>
                                                <![CDATA[Next]]>
                                        </next>
                                        <flag>
                                                <![CDATA[Flag]]>
                                        </flag>
                                        <quizMap>
                                                <![CDATA[Quiz map]]>
                                        </quizMap>
                                        <hint>
                                                <![CDATA[Hint]]>
                                        </hint>
                                        <fullText>
                                                <![CDATA[Full text]]>
                                        </fullText>
                                        <optionalFeedback>
                                                        <![CDATA[Optional feedback]]>
                                        </optionalFeedback>
                                        <magnifyImage>
                                                        <![CDATA[Magnify image]]>
                                        </magnifyImage>
                                        <imageDescription>
                                                        <![CDATA[Image description]]>
                                        </imageDescription>
                                        <videoDescription>
                                                        <![CDATA[Video description]]>
                                        </videoDescription>

                                        <magnifyVideo>
                                                        <![CDATA[Magnify video]]>
                                        </magnifyVideo>
                                        <closePopup>
                                                        <![CDATA[Close]]>
                                        </closePopup>
                                        <audio>
                                                        <![CDATA[Play audio]]>
                                        </audio>
                                        <tryAgain>
                                                        <![CDATA[Try again!]]>
                                        </tryAgain>
                                        <showAnswer>
                                                        <![CDATA[Show answer]]>
                                        </showAnswer>
                                        <sources>
                                                        <![CDATA[Show sources]]>
                                        </sources>
                                        <examinerComment>
                                                        <![CDATA[Examiner's comments]]>
                                        </examinerComment>
                                    </toolTip>
                                </config>";

        $configfilepath = $assessmentpath . '/data/config.xml';
        $fh3 = fopen($configfilepath, 'w');
        fwrite($fh3, $configFiled);
        fclose($fh3);

        $objconfigxml = simplexml_load_string($configFiled, null, LIBXML_NOCDATA);
        $configarray = $converter->convertXmlToArray($objconfigxml->asXML());
        $configJSON = $objJSON->encode($configarray);
        $configjsonpath = $assessmentpath . '/data/Config.js';
        $fh2 = fopen($configjsonpath, 'w');
        $configJSON = str_replace('\n', '', $configJSON);
        $configJSON = str_replace('\t', '', $configJSON);
        fwrite($fh2, "var AstConfigJSON= {$configJSON};");
        fclose($fh2);
        if ($prevSource == "wordTemplate") {
            print "{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizHtmlPreviewPath}{$guid}{$APPCONFIG->PreviewLinkHtml}{$quizid}&PublishedGuid={$guid}&State=false&accessToken={$input['accessToken']}&accessLogID={$input['accessLogID']}&prevSource={$prevSource}";
        } else {
            if ($action == "publishq") {
                if ($DBCONFIG->dbType == 'Oracle')
                    $this->db->execute("UPDATE Assessments SET \"Status\"='Published' WHERE ID='$quizid' and \"isEnabled\" = 1 ");  //Set status to published.
 else
                    $this->db->execute("UPDATE Assessments SET status='Published' WHERE ID='$quizid' and isEnabled = '1' ");  //Set status to published.
 if ($publishtype === 'CDROM-HTML') {
                    ///$zipfile = $CONFIG->rootPath.'/'.$APPCONFIG->CdRomHtmlPublishPath."{$guid}.zip";
                    ///$webzipfile = $CONFIG->wwwroot.'/'.$APPCONFIG->CdRomHtmlPublishPath."{$guid}.zip";
                    $zipfile = $CONFIG->rootPath . '/' . $dataPath . $instID . "/" . $APPCONFIG->CdRomHtmlPublishLocation . "{$guid}.zip";
                    $webzipfile = $CONFIG->wwwroot . '/' . $dataPath . $instID . "/" . $APPCONFIG->CdRomHtmlPublishLocation . "{$guid}.zip";
                    Site::myDebug('-------CDROM-HTML');
                    Site::myDebug($assessmentpath);
                    Site::myDebug($zipfile);
                    $this->makeZip($assessmentpath, $zipfile);
                    print $webzipfile;
                } else {
                    ///print "{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPublishLocation}{$guid}{$APPCONFIG->PermaLinkHtml}{$guid}&State=false";
                    print "{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizHtmlPublishPath}{$guid}{$APPCONFIG->PermaLinkHtml}{$guid}&State=false";
                }
            } else {
                //print "{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPreviewLocation}{$guid}{$APPCONFIG->PreviewLinkHtml}{$quizid}&PublishedGuid={$guid}&State=false";
                print "{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizHtmlPreviewPath}{$guid}{$APPCONFIG->PreviewLinkHtml}{$quizid}&PublishedGuid={$guid}&State=false";
            }
        }
        //preview quiz end
    }

    /**
     * a function to copy required files for flash rendition to the required destination
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    integer $quizid
     * @param    string  $guid
     * @param    string  $publishtype
     * @return   string
     *
     */
    function copyFilesFlashRendition($quizid, $guid, $publishtype) {
        global $CONFIG, $APPCONFIG;
        $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;
        if ($publishtype == 'previewq') {
            ///$pathname=$CONFIG->rootPath.'/'.$APPCONFIG->QuizPreviewLocation.$guid;
            $pathname = $CONFIG->rootPath . '/' . $APPCONFIG->tempDataPath . $instID . "/" . $APPCONFIG->QuizPreviewPath . $guid;
            mkdir($pathname, 0777);
            @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRepositoryLocation . 'preview.php', $pathname . '/preview.php');
            //copy scrom package
            $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRepositoryLocation . 'scorm', $pathname);
        } else {
            ///$pathname=$CONFIG->rootPath.'/'.$APPCONFIG->QuizPublishLocation.$guid;
            $pathname = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->QuizPublishPath . $guid;
            mkdir($pathname, 0777);

            if ($publishtype == 'Online') {
                $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRepositoryLocation . 'scorm', "{$pathname}"); //for both type
                @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRepositoryLocation . 'qpublish.php', $pathname . '/' . 'qpublish.php');
            }
            if ($publishtype == "CDROM-SWF") {
                $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRepositoryLocation . 'scorm', "{$pathname}"); //for both type
            }
            if ($publishtype == 'CDROM-EXE') {
                @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'Shell.exe', "{$pathname}/Shell.exe");
                $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'Xtras', "{$pathname}/Xtras");
            }
        }

        mkdir($pathname . '/images', 0777);
        ///$cssPath = $CONFIG->rootPath.'/'.$APPCONFIG->UserQuizCSSLocation.strtolower($this->getEntityName(2)).'/'.$quizid;
        $cssPath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizFlashCSS . strtolower($this->getEntityName(2)) . '/' . $quizid;
        $this->dirCopy($cssPath . '/images', $pathname . '/images');

        @copy($cssPath . '/Shell.css', $pathname . '/Shell.css');
        @copy($cssPath . '/Result.css', $pathname . '/Result.css');
        @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'Shell.swf', $pathname . '/' . 'Shell.swf');
        @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'Result_Complex.swf', $pathname . '/Result_Complex.swf');
        @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'Result_Simple.swf', $pathname . '/Result_Simple.swf');
        @copy($CONFIG->rootPath . '/' . $APPCONFIG->FlashRenditionLocation . 'SimpleResult.xml', $pathname . '/SimpleResult.xml');

        //Copy Video Player
        @copy("{$CONFIG->rootPath}{$APPCONFIG->VideoSkinLocation}SteelOverPlaySeekMute.swf", $pathname . "/SteelOverPlaySeekMute.swf");
        @copy("{$CONFIG->rootPath}{$APPCONFIG->VideoSkinLocation}SteelOverNoVol.swf", $pathname . '/SteelOverNoVol.swf');
        @copy("{$CONFIG->rootPath}{$APPCONFIG->VideoSkinLocation}Previews/SteelExternalPlaySeekMute.swf", $pathname . '/SteelExternalPlaySeekMute.swf');
        @copy("{$CONFIG->rootPath}{$APPCONFIG->VideoSkinLocation}SteelExternalAll.swf", $pathname . '/SteelExternalAll.swf');

        return $pathname;
    }

    /**
     * a function to add title of an assessment to the published flash rendition
     *
     *
     * @access   public
     * @param    string  $filepath
     * @param    string  $quiztitle
     * @return   void
     *
     */
    function addQuizTitletoSwfPublish($filepath, $quiztitle) {
        $theData = file_get_contents($filepath);
        $theData = str_replace('<title></title>', "<title>{$quiztitle}</title>", $theData);
        unlink($filepath);
        $fh10 = fopen($filepath, 'w');
        fwrite($fh10, $theData);
        fclose($fh10);
        return;
    }

    /**
     * a function to get the published list of an assessment
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $quizid
     * @return   void
     *
     */
    function publishedQuizList($input) {
        global $CONFIG, $APPCONFIG, $DBCONFIG;
        //header('Content-type: text/xml; charset=UTF-8');
        $publishedlist = '';
        Site::myDebug('-------publishedQuizList');


        $quizid = $input['EntityID'];
        //$start = $input['start'];
        $start = (isset($input['pgnstart'])) ? $input['pgnstart'] : $input['start'];
		//$stop = $input['limit'];
		$stop = ($input['pgnstop']) ? $input['pgnstop'] : $input['stop'];

		//echo "===".$start."====".$stop;
	if (!$start)
			$start = -1;
	if (!$stop)
			$stop = -1;
        /*
          $limit = ($stop !="") ? "LIMIT ".$start ." , ". $stop :"";
          $query = "  SELECT SQL_CALC_FOUND_ROWS pbs.*,usr.FirstName,usr.LastName
          FROM PublishAssessments pbs
          Left join Users usr on usr.ID=pbs.UserId and usr.isEnabled = '1'
          WHERE pbs.isEnabled = '1' and pbs.AssessmentID='$quizid' ORDER BY pbs.ModDate Desc $limit";
          $perm                   = $this->db->getRows($query);
          $qry2 = "SELECT FOUND_ROWS() as cnt";
          $perm2                  = $this->db->getRows("SELECT FOUND_ROWS() as cnt");
          $totalRec               = $perm2[0]['cnt'];
         */

        $getPubQuizList = $this->db->executeStoreProcedure('PublishedQuizList', array($quizid, '-1', '-1', $start, $stop, '-1', '-1'));
        Site::myDebug($getPubQuizList);
        $hasMultipleQuadPlus = $this->registry->site->hasMultipleQuadPlus();
        Site::myDebug($hasMultipleQuadPlus);
       // $publishedlist = '<assessments>';
       // $publishedlist .= '<historycount>' . $getPubQuizList['TC'] . '</historycount>';
	header('Content-type: application/json; charset=UTF-8');
	    $jsonp    = '[';

        if (!empty($getPubQuizList['RS'])) {
		    $data = $getPubQuizList['RS'];
			$i = 0;
			$cnt = sizeof($data);
            foreach ($getPubQuizList['RS'] as $perma) {
				$i++;
                $perma = array_map('trim', $perma);
                $userName = $perma['FirstName'] . ' ' . $perma['LastName'];
                if ($perma['PublishType'] == 'Online') {
                    if ($perma['RenditionType'] == 'Flash') {
                        ///$url = $CONFIG->wwwroot.'/'.$APPCONFIG->QuizPublishLocation.$perma['ID'].$APPCONFIG->PermaLink.$perma['ID']  ;
                        $url = $CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->QuizPublishPath . $perma['ID'] . $APPCONFIG->PermaLink . $perma['ID'];
                    } else {
                        if ($perma['RenditionType'] == 'Mobile') {
                            $url = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/mobile/', 'protocol' => 'http')) . $perma['ID'] . '.zip';
                        } else if ($perma['RenditionType'] == 'MarkLogic') {
                            $url = $CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->QuizMLPublishPath . $perma['ID'] . $APPCONFIG->PermaLinkHtml . $perma['ID'] . "&amp;State=false&amp;Target=MarkLogic";
                        } else {
                            $url = $CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->QuizHtmlPublishPath . $perma['ID'] . $APPCONFIG->PermaLinkHtml . $perma['ID'] . "&amp;State=false";
                        }
                    }
                } elseif ($perma['PublishType'] == 'CDROM-HTML') {
                    ///$url = $CONFIG->wwwroot.'/'.$APPCONFIG->CdRomHtmlPublishPath.$perma['ID'].'.zip'  ;
                    $url = $CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->CdRomHtmlPublishLocation . $perma['ID'] . '.zip';
                } else {
                    ///$url = $CONFIG->wwwroot.'/'.$APPCONFIG->CdRomPublishPath.$perma['ID'].'.zip'  ;
                    $url = $CONFIG->wwwroot . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->CdRomPublishLocation . $perma['ID'] . '.zip';
                }

                if ($perma['PublishMode'] == 1) {
                    $perma['PublishMode'] = 'Normal';
                } elseif ($perma['PublishMode'] == 2) {
                    $perma['PublishMode'] = 'Random';
                }

                $ptitle = htmlentities($perma['PublishedTitle']);
				$date   = date('M d,Y', strtotime($perma['ModDate']));
		$jsonp .=
				"{
					\"item\":{
						\"name\":\"" . $ptitle . "\",
						\"type\":\"" . $perma['PublishType'] . "\",
						\"publishedby\":\"" . $userName . "\",
						\"date\":\"" . $date . "\",
						\"viewdownload\":\"" . $url . "\",
						\"deleteid\":\"" . $perma['ID'] . "\",
						\"rendition\":\"" . $perma['RenditionType'] . "\",
						\"mode\":\"" . $perma['PublishMode'] . "\",
						\"randcount\":\"" . $perma['RandomQuestionCount'] . "\",
						\"totalcount\":\"" . $perma['TotalQuestions'] . "\",
						\"isactive\":\"" . $perma['isActive'] . "\",
						\"hasMultipleQuadPlus\":\"" . $hasMultipleQuadPlus . "\",
						\"id\":\"" . $perma['ID'] . "\",
						\"username\":\"" . $userName . "\"
					}
				}";
		if ($i < $cnt) {
		    $jsonp .= ',';
		}
		/* $publishedlist .= " <assessment>
                                        <name>{$ptitle}</name>
                                        <type>{$perma["PublishType"]}</type>
                                        <username>{$userName}</username>
                                        <date>" . date("F j, Y, g:i a", strtotime($perma["ModDate"])) . "</date>
                                        <viewdownload>{$url}</viewdownload>
                                        <deleteid>{$perma["ID"]}</deleteid>
                                        <rendition>{$perma["RenditionType"]}</rendition>
                                        <mode>{$perma["PublishMode"]}</mode>
                                        <randcount>{$perma["RandomQuestionCount"]}</randcount>
                                        <totalcount>{$perma["TotalQuestions"]}</totalcount>
                                        <isactive>{$perma["isActive"]}</isactive>
                                        <hasMultipleQuadPlus>{$hasMultipleQuadPlus}</hasMultipleQuadPlus>
		  </assessment>"; */
            }
	} else {
	    $jsonp .=
								"{
									\"item\":{
										\"name\":\"No data\",
										\"type\":\"" . $perma['ExportType'] . "\",
										\"username\":\"" . $userName . "\",
										\"date\":\"" . $date . "\",
										\"viewlink\":\"" . $viewlink . "\",
										\"viewquestionlink\":\"" . $viewquestionlink . "\",
										\"viewdownload\":\"" . $downloadUrl . "\",
										\"deleteid\":\"" . $perma['ID'] . "\",
										\"rendition\":\"" . $perma['RenditionType'] . "\",										
										\"totalcount\":\"" . $perma['QuestCount'] . "\",
										\"isactive\":\"" . $perma['isEnabled'] . "\",
										\"id\":\"0\"
																			
									}
								}";
								$cnt = 0;
		}

        //$publishedlist .= '</assessments>';
        //echo $publishedlist;
       // die;

	  $jsonp   .= ']';
      $jsonpresponse = "{\"results\":{$jsonp}, \"count\":{$cnt}}";
	echo $jsonpresponse;
      die;
    }

    /**
     * a function to get the static question count in an assessment
     *
     *
     * @access   public
     * @param    integer $quizid
     * @param    string  $questids
     * @return   integer
     *
     */
    function getStaticPageCount($quizid = 0, $questids = '') {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $filter = " qtp.\"isStatic\" = ''Y'' AND mrq.\"SectionName\" = '''' ";
            $filter .= ( $questids != '') ? " and mrq.\"ID\" in ({$questids})" : '';
            // $filter        .= ' group by mrq."QuestionID" ';
        } else {
            $filter = " qtp.isStatic = 'Y' AND mrq.SectionName='' ";
            $filter .= ( $questids != '') ? " and mrq.ID in ({$questids})" : '';
            $filter .= ' group by mrq.questionID ';
        }

        $questiontemps = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', '-1'), 'list');
        return $questiontemps['TC'];
    }

    /**
     * a function to copy required files for html rendition to the required destination
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $quizid
     * @param    string  $guid
     * @param    string  $publishtype
     * @return   string
     *
     */
    function copyFilesHtmlRendition($quizid, $guid, $publishtype = 'online', $publishmode = 'html') {
        global $CONFIG, $APPCONFIG, $DBCONFIG;

        $persistPath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/";
        $tempPath = $CONFIG->rootPath . '/' . $APPCONFIG->tempDataPath . $this->session->getValue('instID') . "/";
        if ($publishtype == 'previewq') {
            if ($publishmode == "html") {
                $pathname = $tempPath . $APPCONFIG->QuizHtmlPreviewPath . $guid;
            } elseif ($publishmode == "MarkLogic") {
                $pathname = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => $APPCONFIG->QuizMLPreviewPath . $guid));
                // $pathname   = $tempPath.$APPCONFIG->QuizMLPreviewPath.$guid;
            }
            $this->myDebug('copyFilesHtmlRendition---' . $pathname);
            if (!is_dir($pathname)) {
                @mkdir($pathname, 0777);
            }
            @copy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . $APPCONFIG->PermaLinkPhpFile, $pathname . '/' . $APPCONFIG->PermaLinkPhpFile);
        } else {
            if ($publishtype == 'Online') {
                if ($publishmode == "html") {
                    $pathname = $persistPath . $APPCONFIG->QuizHtmlPublishPath . $guid;
                } elseif ($publishmode == "MarkLogic") {
                    $pathname = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => $APPCONFIG->QuizMLPublishPath . $guid));
                    // $pathname   = $persistPath.$APPCONFIG->QuizMLPublishPath.$guid;
                }
                if (!is_dir($pathname)) {
                    @mkdir($pathname, 0777);
                }
                @copy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . $APPCONFIG->PermaLinkPhpFile, $pathname . '/' . $APPCONFIG->PermaLinkPhpFile);
            } elseif ($publishtype == 'CDROM-HTML') {
                $pathname = $persistPath . $APPCONFIG->CdRomHtmlPublishLocation . $guid;
                @mkdir($pathname, 0777);
                @copy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . $APPCONFIG->PermaLinkHtmlFile, $pathname . '/' . $APPCONFIG->PermaLinkHtmlFile);
            }
        }

        @copy($persistPath . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName(2)) . '/' . $quizid . '/Shell.css', $pathname . '/Shell.css');
        mkdir($pathname . '/assessmentimages', 0777);

        ///$this->dirCopy($CONFIG->rootPath.'/'.$APPCONFIG->UserQuizCSSLocationforHtml.strtolower($this->getEntityName(2)).'/'.$quizid.'/assessmentimages',$pathname.'/assessmentimages');
        $this->dirCopy($persistPath . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName(2)) . '/' . $quizid . '/assessmentimages', $pathname . '/assessmentimages');
        mkdir($pathname . '/ext2.2', 0777);
        $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . 'ext2.2', $pathname . '/ext2.2');

        mkdir($pathname . '/plugins', 0777);
        $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . 'plugins', $pathname . '/plugins');

        mkdir($pathname . '/images', 0777);
        $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . 'images', $pathname . '/images');

        mkdir($pathname . '/styles', 0777);
        $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . 'styles', $pathname . '/styles');
        mkdir($pathname . '/welcome', 0777);
        $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . 'welcome', $pathname . '/welcome');
        mkdir($pathname . '/data', 0777);
        return $pathname;
    }

    /**
     * a function to rename the published assessment title to the given title
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    integer $pubid
     * @param    string  $publishedname
     * @return   string
     *
     */
    function renamePubQuiz($pubid, $publishedname) {
        global $CONFIG, $APPCONFIG, $DBCONFIG;
        $ssn = new Session();
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "SELECT * FROM PublishAssessments WHERE ID = $pubid and \"isEnabled\" = 1 ";
        } else {
            $query = "SELECT * FROM PublishAssessments WHERE ID= '$pubid' and isEnabled = '1'";
        }
        $temppublish = $this->db->getSingleRow($query);
        if (!empty($temppublish)) {
            if ($DBCONFIG->dbType == 'Oracle') {
                $sqlUpdate = "UPDATE PublishAssessments SET \"PublishedTitle\" = '{$publishedname}' WHERE ID='$pubid' and \"isEnabled\" = '1' ";
            } else {
                $sqlUpdate = "UPDATE PublishAssessments SET PublishedTitle = '{$publishedname}' WHERE ID='$pubid' and isEnabled = '1' ";
            }
            $this->db->execute($sqlUpdate);
            return 'Assessment is renamed';
        }
        else
            return 'No such published assessment';
    }

    /**
     * a function to delete published assessment
     *
     *
     * @access   public
     * @global   object  $DBCONFIG
     * @global   object  $APPCONFIG
     * @param    integer $pubid
     * @return   string
     *
     */
    function delPublishQuiz($pubid) {
        global $DBCONFIG, $APPCONFIG, $CONFIG;
        $ssn = new Session();

        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "SELECT * FROM PublishAssessments WHERE ID= $pubid and \"isEnabled\" = 1 ";
        } else {
            $query = "SELECT * FROM PublishAssessments WHERE ID= '$pubid' and isEnabled = '1' ";
        }

        $temppublish = $this->db->getSingleRow($query);
        if (!empty($temppublish)) {
            //decide path for publish
            if (strtolower($temppublish['RenditionType']) == 'flash') {
                ///$onlinepublishpath  = $CONFIG->rootPath.'/'.$APPCONFIG->QuizPublishLocation.$temppublish['ID'];
                ///$cdrompublishpath   = $CONFIG->rootPath.'/'.$APPCONFIG->CdRomPublishPath.$temppublish['ID'].'.zip';
                $onlinepublishpath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->QuizPublishPath . $temppublish['ID'];
                $cdrompublishpath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->CdRomPublishLocation . $temppublish['ID'] . '.zip';
            }
            if (strtolower($temppublish['RenditionType']) == 'html') {
                ///$onlinepublishpath  = $CONFIG->rootPath.'/'.$APPCONFIG->QuizHtmlPublishLocation.$temppublish->PublishedGuid;
                ///$cdrompublishpath   = $CONFIG->rootPath.'/'.$APPCONFIG->CdRomHtmlPublishPath.$temppublish->PublishedGuid.'.zip';

                $onlinepublishpath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->QuizHtmlPublishPath . $temppublish['ID'];
                $cdrompublishpath = $CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->CdRomHtmlPublishLocation . $temppublish['ID'] . '.zip';
            }
            //remove published directory
            if ($temppublish['PublishType'] == 'Online') {

                if (is_dir($onlinepublishpath)) {

                    $this->rmDirRecurse($onlinepublishpath);
                    if (is_dir($onlinepublishpath))
                        rmdir($onlinepublishpath);
                }
            }
            if ($temppublish['PublishType'] == 'CD-ROM-SWF' || $temppublish['PublishType'] == 'CD-ROM-EXE') {
                unlink($cdrompublishpath);
            }

            if ($DBCONFIG->dbType == 'Oracle') {
                $sqlUpdate = "UPDATE  PublishAssessments SET \"isEnabled\" = '0' WHERE ID = '$pubid' ";
            } else {
                $sqlUpdate = "UPDATE  PublishAssessments SET isEnabled = '0' WHERE ID='$pubid' ";
            }


            $this->db->execute($sqlUpdate);
            return 'You have deleted published assessment';
        }
        else
            return 'No such published';
    }

    /**
     * a function to remove all contents recursively of the specified path
     *
     *
     * @access   public
     * @param    string  $path
     * @return   void
     *   Moved to  Site.php
     */
    /*
      function rmDirRecurse($path)
      {
      $path= rtrim($path, '/').'/';
      $handle = opendir($path);
      for (;false !== ($file = readdir($handle));)
      if($file != '.' and $file != '..' )
      {
      $fullpath= $path.$file;
      if( is_dir($fullpath) )
      {
      $this->rmDirRecurse($fullpath);
      rmdir($fullpath);
      }
      else
      {
      unlink($fullpath);
      }
      }
      closedir($handle);
      }
     *
     */

    /**
     * a function to set published assessment status as active/de-active
     *
     *
     * @access   public
     * @global   object  $DBCONFIG
     * @global   object  $APPCONFIG
     * @param    array   $input
     * @return   string
     *
     */
    function togglePublishStatus($input) {
        global $DBCONFIG, $APPCONFIG;

        $pubid = $input['PubID'];
        $isActive = $input['Status'];

        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "SELECT * FROM PublishAssessments WHERE ID= $pubid and \"isEnabled\" = 1 ";
        } else {
            $query = "SELECT * FROM PublishAssessments WHERE ID= '$pubid' and isEnabled = '1' ";
        }
        $temppublish = $this->db->getSingleRow($query);
        if (!empty($temppublish)) {
            if ($DBCONFIG->dbType == 'Oracle') {
                $sqlUpdate = "UPDATE PublishAssessments SET \"isActive\" = '{$isActive}' WHERE ID = '$pubid' and \"isEnabled\" = '1'";
            } else {
                $sqlUpdate = "UPDATE PublishAssessments SET isActive = '{$isActive}' WHERE ID = '$pubid' and isEnabled = '1'";
            }
            $this->db->execute($sqlUpdate);
            return ($isActive == 'Y') ? 'Assessment activated' : 'Assessment De-activated';
        }
        else
            return 'No such published assessment';
    }

    /**
     * a function to get the boolean status from yes/no
     *
     *
     * @access   public
     * @param    string  $valtobool
     * @return   string
     *
     */
    function getBoolean($valtobool) {
        if ($valtobool === 'yes' || $valtobool === 'YES' || $valtobool === 'Yes') {
            $valtobool = 'true';
        }
        if ($valtobool === 'no' || $valtobool === 'NO' || $valtobool === 'No') {
            $valtobool = 'false';
        }
        return $valtobool;
    }

    /**
     * a function to get base configuration xml for assessment
     *
     *
     * @access       public
     * @deprecated
     * @param        string  $AsmtDetail
     * @return       string
     *
     */
    function getConfigXml($AsmtDetail) {

        $configFiled = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                        <config>
                            <assetPath></assetPath>
                            <topBanner>
                                    <height>106</height>
                            </topBanner>
                            <footerButton>
                                    <print>
                                            <xPosition>20</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </print>
                                    <reset>
                                            <xPosition>50</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </reset>
                                    <help>
                                            <xPosition>80</xPosition>
                                            <visible>" . $this->getBoolean($qhelp) . "</visible>
                                    </help>
                                    <submit>
                                            <xPosition>680</xPosition>
                                            <visible>false</visible><!--Never -->
                                    </submit>
                                    <previous>
                                            <xPosition>902</xPosition>
                                            <visible>" . $this->getBoolean($qmoveback) . "</visible>
                                    </previous>
                                    <next>
                                            <xPosition>930</xPosition>
                                            <visible>true</visible>
                                    </next>
                                    <timer>
                                            <xPosition>450</xPosition>
                                            <visible>" . $this->getBoolean($qtimer) . "</visible>
                                            <totalTime>{$qminutes}</totalTime><!--expect Number, we assume that given value in minute.(Ex : 20, means 20min.) -->
                                    </timer>
                                    <pagination>
                                            <xPosition>780</xPosition>
                                            <visible>" . $this->getBoolean($qpagination) . "</visible>
                                    </pagination>
                                    <flag>
                                            <xPosition>250</xPosition>
                                            <visible>" . $this->getBoolean($qflag) . "</visible>
                                    </flag>
                                    <quizMap>
                                            <xPosition>150</xPosition>
                                            <visible>" . $this->getBoolean($qmap) . "</visible>
                                    </quizMap>
                                    <hintRP>
                                            <xPosition>570</xPosition>
                                            <visible>false</visible>
                                    </hintRP>
                                    <hint>
                                            <xPosition>570</xPosition>
                                            <visible>" . $this->getBoolean($qhint) . "</visible>
                                    </hint>
                                </footerButton>
                                <isDirector>{$isDirector}</isDirector><!--yes/no -->
                                <timeOutMessage><![CDATA[Ooops! Time's up, click submit to view your results.]]></timeOutMessage>
                                <ExitMessage><![CDATA[{$ExitMessage}]]></ExitMessage>
                                <quizSubmitMessage><![CDATA[Are you sure you want to submit the quiz? If you do you will not be able to change your answers.]]></quizSubmitMessage>
                                <copyRightText><![CDATA[Copyright]]></copyRightText>
                                <toolTip>
                                        <print>
                                                <![CDATA[Print]]>
                                        </print>
                                        <reset>
                                                <![CDATA[Reset]]>
                                        </reset>
                                        <help>
                                                <![CDATA[Help]]>
                                        </help>
                                        <submit>
                                                <![CDATA[Submit]]>
                                        </submit>
                                        <previous>
                                                <![CDATA[Previous]]>
                                        </previous>
                                        <next>
                                                <![CDATA[Next]]>
                                        </next>
                                        <flag>
                                                <![CDATA[Flag]]>
                                        </flag>
                                        <quizMap>
                                                <![CDATA[Quiz map]]>
                                        </quizMap>
                                        <hint>
                                                <![CDATA[Hint]]>
                                        </hint>
                                        <fullText>
                                                <![CDATA[Full text]]>
                                        </fullText>
                                        <optionalFeedback>
                                                        <![CDATA[Optional feedback]]>
                                        </optionalFeedback>
                                        <magnifyImage>
                                                        <![CDATA[Magnify image]]>
                                        </magnifyImage>
                                        <imageDescription>
                                                        <![CDATA[Image description]]>
                                        </imageDescription>
                                        <videoDescription>
                                                        <![CDATA[Video description]]>
                                        </videoDescription>

                                        <magnifyVideo>
                                                        <![CDATA[Magnify video]]>
                                        </magnifyVideo>
                                        <closePopup>
                                                        <![CDATA[Close]]>
                                        </closePopup>
                                        <audio>
                                                        <![CDATA[Play audio]]>
                                        </audio>
                                        <tryAgain>
                                                        <![CDATA[Try again!]]>
                                        </tryAgain>
                                        <showAnswer>
                                                        <![CDATA[Show answer]]>
                                        </showAnswer>
                                        <sources>
                                                        <![CDATA[Show sources]]>
                                        </sources>
                                        <examinerComment>
                                                        <![CDATA[Examiner's comments]]>
                                        </examinerComment>
                                </toolTip>
                            </config>";
        return $configFiled;
    }

    /**
     * a function to upload user defined css
     *
     *
     * @access   public
     * @global   object  $DBCONFIG
     * @global   object  $APPCONFIG
     * @global   object  $CONFIG
     * @param    integer $quizid
     * @param    string  $renditiontype
     * @return   string
     *
     */
    function uploadCss($EntityTypeId, $quizid, $renditiontype) {
        $this->myDebug("=================Start Upload Css==================");
        global $DBCONFIG, $APPCONFIG, $CONFIG;

        $fileElementName = 'cssfileupload';

        $d = date('U');

        if ($renditiontype == 1) {
            //$quizlocation =   $APPCONFIG->UserQuizCSSLocation.strtolower($this->getEntityName(2)).'/'.$quizid.'/';
            $quizlocation = $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->UserQuizFlashCSS . strtolower($this->getEntityName($EntityTypeId)) . '/' . $quizid . '/';
            $templocation = $CONFIG->rootPath . '/' . $APPCONFIG->tempDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->UserQuizFlashCSS . $quizid . strtolower($this->getEntityName($EntityTypeId)) . '/' . $d;
        } else {
            //$quizlocation =   $APPCONFIG->UserQuizCSSLocationforHtml.strtolower($this->getEntityName(2)).'/'.$quizid.'/';
            $quizlocation = $APPCONFIG->PersistDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName($EntityTypeId)) . '/' . $quizid . '/';
            $templocation = $CONFIG->rootPath . '/' . $APPCONFIG->tempDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName($EntityTypeId)) . '/' . $quizid . '/' . $d;
        }
        $this->myDebug("quizlocation=================$quizlocation");
        //$templocation =  $CONFIG->rootPath.'/'.$APPCONFIG->QuizCSSImageUnzipTempLocation.$d ;
        $this->myDebug("templocation=================$templocation");
        mkdir($templocation . '/', 0777, true);
        $status = $this->uploadFileToServer($_FILES[$fileElementName]['error'], $_FILES[$fileElementName]['tmp_name'], $templocation . '.zip');
        if ($status == false) {
            $error = 'Error';
        } else {

            $this->unzip($templocation . '.zip', $templocation . '/');
            $this->rmDirRecurse($quizlocation);
            $this->dirCopy($templocation . '/', $quizlocation);
            copy($templocation . '.zip', $quizlocation . 'css.zip');
            $this->rmDirRecurse($templocation . '/');
            unlink($templocation . '.zip');
            $msg = ' File Uploaded : ' . $_FILES[$fileElementName]['name'] . '';
            $msg .= '' . @filesize($_FILES[$fileElementName]['tmp_name']);
            $error = '';
        }
        $this->myDebug('=================End Upload Css==================');
        return "{'error': '$error', 'msg': '$msg'}";
    }

    /**
     * a function to cancel check out feature if user has checked out questions
     *
     *
     * @access   public
     * @param    array   $input
     * @return   boolean
     *
     */
    function undoCheckout($input) {
        global $DBCONFIG;

        if (trim($input['mapReposID']) != '') {
            if ($DBCONFIG->dbType == 'Oracle') {

                $queryMrq = " UPDATE MapRepositoryQuestions  SET \"EditStatus\" = 0  where ID IN({$input['mapReposID']}) and \"isEnabled\" = 1
                        and \"UserID\" in (select \"UserID\" from MapClientUser where \"isEnabled\" = 1 AND \"ClientID\" = {$this->session->getValue('instID')} )";
                $this->db->execute($queryMrq);

                $queryQst = "UPDATE Questions  SET \"AuthoringStatus\" = 0,  \"AuthoringUserID\" = 0
                        where ID IN (select \"QuestionID\" from MapRepositoryQuestions where ID IN({$input['mapReposID']}))";
                $this->db->execute($queryQst);
            } else {
                $query = "  UPDATE MapRepositoryQuestions mrq, MapClientUser mcu, Questions qst
                        SET mrq.EditStatus = '0', qst.AuthoringStatus = '0',  qst.AuthoringUserID = 0
                        WHERE
                            mrq.ID IN({$input['mapReposID']}) and mrq.isEnabled = '1' and mrq.UserID = mcu.UserID and mcu.isEnabled = '1'
                            AND qst.ID = mrq.QuestionID 
                            AND mcu.ClientID = {$this->session->getValue('instID')} ";
                $this->db->execute($query);
            }

            /*
              // Get Question ID's
              $arrDataID    = $this->getCheckedOutReposIdAndQuestId($input);
              if ( $arrDataID )
              {
              $qid    = $arrDataID['questID'];
              $qid    = array_values($qid);
              if(!empty($qid))
              {
              $this->updateCheckedOutQuestID($qid, $statusVal = 0);
              }
              }
             */
            return true;
        }
        return false;
    }

    /**
     * a function to handle word template feature
     *
     *
     * @access   public
     * @global   object  $DBCONFIG
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    array   $input
     * @param    integer $entityID
     * @return   array
     *
     */
    function wordTemplate($input, $entityID = 0) {
        global $DBCONFIG, $CONFIG, $APPCONFIG;

        $arrDataID = $this->getCheckedOutReposIdAndQuestId($input);

        if ($arrDataID['repID']) {
            $rid = $arrDataID['repID'];
            $rid = array_values($rid);
            $rid = $this->removeBlankElements($rid);
        }
        if (!empty($rid)) {
            $this->updateCheckedOutReposID($rid, $statusVal = 1);
        }

        // Update Question ID
        if ($arrDataID['questID']) {
            $qid = $arrDataID['questID'];
            $qid = array_values($qid);
        }
        $this->myDebug("These are Question id");
        $this->myDebug($qid);
        if (!empty($qid)) {
            $this->updateCheckedOutQuestID($qid, $statusVal = 1);
        }

        $checkedOut = (empty($rid)) ? 'false' : 'true';
        $checkedIn = (!empty($rid)) ? 'false' : 'true';

        $ssn = new Session();
        $ipDoc = simplexml_load_file($CONFIG->rootPath . '/' . $APPCONFIG->WordLocation . $APPCONFIG->DocWordtemplate);
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:Requires_x0020_CheckOut');
        $node[0] = "$checkedOut";
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:IsCheckedOut');
        $node[0] = "$checkedOut";
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:IsCheckedIn');
        $node[0] = "false";
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:Project_x0020_Code');
        $node[0] = $input['EntityTypeID'];
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:Storyboard_x0020_Code');
        $node[0] = $input['EntityID'];
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:Author_x0020_Code');
        $node[0] = $this->session->getValue('userID');
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:Location');
        $node[0] = $CONFIG->wwwroot . '/authoring/';
        list($node) = $ipDoc->xpath('//o:CustomDocumentProperties/o:TemplateType');
        $node[0] = '';
        $uniq = uniqid();
        $docname = (($input['EntityTypeID'] == 1) ? 'Bank' : 'Assessment') . "_withoutofftool_{$uniq}.doc";
        // $templatename   = $APPCONFIG->WordTempDownloadLocation.(($input['EntityTypeID'] == 1)?'Bank':'Assessment')."_withoutofftool_{$uniq}.doc";
        $wordTempDownloadLocation = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets/downloads/'));
        $templatename = $wordTempDownloadLocation . (($input['EntityTypeID'] == 1) ? 'Bank' : 'Assessment') . "_withoutofftool_{$uniq}.doc";
        $handle = fopen("{$templatename}", 'w');
        fwrite($handle, $ipDoc->asXML());
        fclose($handle);

        $arrData = array(
            'Success' => '1',
            'AuthorTemplate' => "{$CONFIG->wwwroot}/authoring/download/f:{$APPCONFIG->DotWordtemplate}|path:|rand:" . $uniq, // Dot file
            'IDTemplate' => "{$CONFIG->wwwroot}/authoring/download/f:{$docname}|path:" . $APPCONFIG->tempDataPath . $this->session->getValue('instID') . "/" . $APPCONFIG->tempAssetsDwn . "|rand:" . $uniq
        );

        return json_encode($arrData);
    }

    /**
     * a function to get repository IDs of checked out questions
     *
     *
     * @access   public
     * @param    array   $input
     * @return   array
     *
     */
    function getCheckedOutReposIdAndQuestId($input) {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $filter = (empty($input['mapReposID'])) ? " mrq.\"EditStatus\" = '1' " : "  mrq.\"ID\" IN({$input['mapReposID']}) ";
	    $data = $this->db->executeStoreProcedure('GETCHECKEDOUTREPOSIDANDQUESTID', array($filter,
                                $input["EntityID"],
                                $input["EntityTypeID"], //this is an entity type id
                                $this->session->getValue('userID'),
                                $this->session->getValue('instID'),
                                '-1'
                            ), 'nocount');
        } else {
            $filter = (empty($input['mapReposID'])) ? " AND mrq.EditStatus = '1' " : "  AND mrq.ID IN({$input['mapReposID']}) ";

            $query = " SELECT mrq.ID, mrq.QuestionID, qst.AuthoringStatus
                                FROM MapRepositoryQuestions mrq , MapClientUser mcu, Questions qst
                                WHERE mrq.EntityID = " . $input["EntityID"] . " and mrq.EntityTypeID = " . $input["EntityTypeID"] . " and mrq.isEnabled = '1'
                                    AND mrq.UserID = mcu.UserID AND mcu.isEnabled = '1' 
                                    AND mrq.QuestionID	= qst.ID
                                    AND mcu.ClientID = " . $this->session->getValue('instID') . " $filter  ";
            $data = $this->db->getRows($query);
        }

        Site::myDebug("--------getCheckedOutReposId");
        Site::myDebug($data);
        if ($data) {
            $arrData['repID'] = array();
            $arrData['questID'] = array();
            foreach ($data as $key => $value) {
                Site::myDebug("--------Each row");
                Site::myDebug($value);
                if ($value['AuthoringStatus'] == 0) {
                    Site::myDebug("--------Each row value");
                    Site::myDebug($value['ID']);
                    Site::myDebug($value['QuestionID']);
                    //$arrData['repID'][] = $value['ID'];
                    array_push($arrData['repID'], $value['ID']);
                    array_push($arrData['questID'], $value['QuestionID']);
                    // $arrData['questID'][] = $value['QuestionID'];
                }
            }
        }
        Site::myDebug("This is new values");
        Site::myDebug($arrData);
        // Site::myDebug( $this->getValueArray($data, 'ID', 'multiple', 'array') ); 
        return $arrData;
    }

    /**
     * a function to set checked out status of given repository IDs
     *
     *
     * @access   public
     * @param    mixed   $arr
     * @return   boolean
     *
     */
    function updateCheckedOutReposId($arr, $intStatus) {
        global $DBCONFIG;
        $id = '';
        if (is_array($arr)) {
            $id = implode(',', $arr);
        } else {
            $id = $arr;
        }

        if ($id != '') {
            if ($DBCONFIG->dbType == 'Oracle') {
                $query = "Update MapRepositoryQuestions SET \"EditStatus\" = '{$intStatus}' WHERE ID IN({$id})  "; //  1 => Checked Out AND EditStatus = 0
            } else {
                $query = "Update MapRepositoryQuestions SET EditStatus = '{$intStatus}' WHERE ID IN({$id})    "; //  1 => Checked Out AND EditStatus = 0
            }
            $this->db->execute($query);
        }
        return true;
    }

    function updateCheckedOutQuestID($arr, $intStatus) {
        global $DBCONFIG;
        $id = '';
        if (is_array($arr)) {
            $id = implode(',', $arr);
        } else {
            $id = $arr;
        }
        $this->myDebug("THis is updateCheckedOutQuestID:");
        if ($id != '') {
            if ($DBCONFIG->dbType == 'Oracle') {
                $authoringUserID = ( $this->session->getValue('userID') && $intStatus > 0 ) ? ' , "AuthoringUserID" = ' . $this->session->getValue('userID') : ' , "AuthoringUserID" = 0 '; //change for oracle
                $query = "Update Questions SET \"AuthoringStatus\" = '$intStatus' $authoringUserID WHERE ID IN ($id) "; //change for oracle
            } else {
                $authoringUserID = ( $this->session->getValue('userID') && $intStatus > 0 ) ? ' , AuthoringUserID = ' . $this->session->getValue('userID') : ' , AuthoringUserID = 0 ';
                $query = "Update Questions SET AuthoringStatus = '$intStatus' $authoringUserID WHERE ID IN ($id) "; //change for oracle
            }
            $this->db->execute($query);
        }
        return true;
    }

    /**
     * a function to handle download of word templates for offline
     *
     *
     * @access   public
     * @global   $CONFIG
     * @global   $APPCONFIG
     * @param    string  $fname
     * @param    string  $path
     * @return   void
     *
     */
    function download($fname, $path = '') {
        global $CONFIG, $APPCONFIG;
        define('ALLOWED_REFERRER', '');

        // Download folder, i.e. folder where you keep all files for download.
        // MUST end with slash (i.e. '/' )

        if ($path == '') {
            //$path = $APPCONFIG->WordTempLocation;
            $path = $APPCONFIG->WordLocation;
        }



        define('BASE_DIR', $CONFIG->rootPath . '/' . $path);

        // Allowed extensions list in format 'extension' => 'mime type'
        // If myme type is set to empty string then script will try to detect mime type
        // itself, which would only work if you have Mimetype or Fileinfo extensions
        // installed on server.
        $allowed_ext = array(
            // dot documents
            'dot' => 'application/msword',
            'doc' => 'application/msword',
            'docx' => 'application/msword',
            'odt' => 'application/msword',
            'odg' => 'application/msword',
            'xls' => 'application/excel',
            'xlsx' => 'application/excel',
            'ods' => 'application/excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.ms-powerpoint',
            'odp' => 'application/vnd.ms-powerpoint',
            'mp3' => 'audio/mpeg',
            'wma' => 'audio/mpeg',
            'wav' => 'audio/mpeg',
            'flv' => 'audio/mpeg',
            'mp4' => 'audio/mpeg',
            'mov' => 'audio/mpeg',
            'mpg' => 'audio/mpeg',
            'ram' => 'audio/mpeg',
            'wmv' => 'audio/mpeg',
            'swf' => 'audio/mpeg',
            '3g2' => 'audio/mpeg',
            'avi' => 'audio/mpeg',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'txt' => 'text/xml',
            'csv' => 'text/xml'
        );

        ####################################################################
        ###  DO NOT CHANGE BELOW
        ####################################################################
        // If hotlinking not allowed then make hackers think there are some server problems
	if (ALLOWED_REFERRER !== '' && (!isset($_SERVER['HTTP_REFERER']) || strpos(strtoupper($_SERVER['HTTP_REFERER']), strtoupper(ALLOWED_REFERRER)) === false)
        ) {
            die('Internal server error. Please contact system administrator.');
        }

        // Make sure program execution doesn't time out
        // Set maximum script execution time in seconds (0 means no limit)
        set_time_limit(0);
        /*
          if (!isset($_GET['f']) || empty($_GET['f'])) {
          die("Please specify file name for download.");
          }
         */
        // Get real file name.
        // Remove any path info to avoid hacking by adding relative path, etc.
        //$fname = basename($_GET['f']);
        // Check if the file exists
        // Check in subfolders too

        /* function find_file ($dirname, $fname, &$file_path) {

          $dir = opendir($dirname);

          while ($file = readdir($dir)) {
          if (empty($file_path) && $file != '.' && $file != '..') {
          if (is_dir($dirname.'/'.$file)) {
          find_file($dirname.'/'.$file, $fname, $file_path);
          }
          else {
          if (file_exists($dirname.'/'.$fname)) {
          $file_path = $dirname.'/'.$fname;
          return;
          }
          }
          }
          }

          } // find_file

          // get full file path (including subfolders)
          Site::myDebug("---------download(");
          Site::myDebug(BASE_DIR.'-----'.$fname.'---'.$file_path);

          $file_path = '';
          find_file(BASE_DIR, $fname, $file_path); */

        $file_path = BASE_DIR . $fname;


        if (!is_file($file_path)) {
            die(BASE_DIR . '-----' . $path . '---' . $file_path . '--File does not exist. Make sure you specified correct file name.');
        }

        // file size in bytes
        $fsize = filesize($file_path);

        // file extension
        $fext = strtolower(substr(strrchr($fname, '.'), 1));

        // check if allowed extension
        if (!array_key_exists($fext, $allowed_ext)) {
            die('Not allowed file type.');
        }

        // get mime type
        if ($allowed_ext[$fext] == '') {
            $mtype = '';
            // mime type is not set, get from server settings
            if (function_exists('mime_content_type')) {
                $mtype = mime_content_type($file_path);
            } else if (function_exists('finfo_file')) {
                $finfo = finfo_open(FILEINFO_MIME); // return mime type
                $mtype = finfo_file($finfo, $file_path);
                finfo_close($finfo);
            }
            if ($mtype == '') {
                $mtype = 'application/force-download';
            }
        } else {
            // get mime type defined by admin
            $mtype = $allowed_ext[$fext];
        }

        // Browser will try to save file with this filename, regardless original filename.
        // You can override it if needed.

        if (!isset($_GET['fc']) || empty($_GET['fc'])) {
            $asfname = $fname;
        } else {
            // remove some bad chars
            $asfname = str_replace(array('"', "'", '\\', '/'), '', $_GET['fc']);
            if ($asfname === '')
                $asfname = 'NoName';
        }

        // set headers
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header("Content-Type: $mtype");
        header("Content-Disposition: attachment; filename=\"$asfname\"");
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $fsize);

        // download
        // @readfile($file_path);
        $file = @fopen($file_path, 'rb');
        if ($file) {
            while (!feof($file)) {
                print(fread($file, 1024 * 8));
                flush();
                if (connection_status() != 0) {
                    @fclose($file);
                    die();
                }
            }
            @fclose($file);
        }
    }

    /**
     * a function to get the xml of checked out questions
     *
     *
     * @access   public
     * @param    array   $input
     * @return   void
     *
     */
    public function questionsCheckOut($input) {
        try {
            $qst = new Question();
            $sUserID = (int) $input['sUserID'];
            $EntityID = (int) $input['entityID'];
            $EntityTypeID = (int) $input['entityTypeID'];

            $this->myDebug('this is test satring');
            $this->myDebug('---------------QuestionsCheckOut start---------------');
	    $questionlist = $this->db->executeStoreProcedure('QuestionList', array(' mrq.Sequence ',
                                ' asc ',
                                '-1',
                                '-1',
                                " mrq.EditStatus = 1 ",
                                $EntityID,
                                $EntityTypeID, //this is an entity type id
                                $sUserID,
                                'mrq.ID , mrq.QuestionID , qst.XMLData '
                    ));
            $XML = "<screens id=\"{$EntityID}_{$EntityTypeID}\">";

            if (!empty($questionlist['RS'])) {
                foreach ($questionlist['RS'] as $question) {
                    $XMLtmp = $qst->removeMediaPlaceHolder($question['XMLData']);
                    ;
                    $pattern = '/screen_id=\"(\w+)\"/i';
                    $replacements = "screen_id=\"{$question["QuestionID"]}\"";
                    $XMLtmp = preg_replace($pattern, $replacements, $XMLtmp);
                    $XMLtmp1 = substr($XMLtmp, 0, strpos($XMLtmp, "<row position=\"2\">"));
                    $XMLtmp2 = str_replace("editable=\"1\"", "editable=\"0\"", $XMLtmp1);
                    $XMLtmp = str_replace($XMLtmp1, $XMLtmp2, $XMLtmp);
                    $XMLtmp = preg_replace('[\r\t\n]', '', $XMLtmp);
                    $this->myDebug("This is New Xml");
                    $this->myDebug($XMLtmp);
                    if (strpos($XMLtmp, '<![CDATA[') > -1) {
                        $XMLtmp = str_replace('&#x', '########', $XMLtmp);
                        $XMLtmp = str_replace('<![CDATA[', '', $XMLtmp);
                        $XMLtmp = str_replace(']]>', '', $XMLtmp);
                        $XMLtmp = str_replace('&', '&amp;', $XMLtmp);
                        $XMLtmp = str_replace('########', '&#x', $XMLtmp);
                    }
                    $XML .= $XMLtmp;
                }
            }
            $XML .= '</screens>';
            $this->myDebug('---------------Screen xml starts---------------');
            $this->myDebug($XML);
            $this->myDebug('---------------XML ends---------------');
        } catch (exception $ex) {
            $this->myDebug('---------------Exception start---------------');
            $this->myDebug($ex);
            $this->myDebug('---------------Exception end---------------');
            continue;
        }
        header('Content-type: text/xml; charset=UTF-8');
        print $XML;
        die;
    }

    /**
     * a function to handle word template check in and get response as xml
     *
     *
     * @access   public
     * @global   object  $APPCONFIG
     * @global   object  $CONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @return   void
     *
     */
    public function questionsCheckIn($input) {
        global $DBCONFIG;

        try {
            global $APPCONFIG, $CONFIG, $DBCONFIG;
            $qtp = new QuestionTemplate();
            $qst = new Question();
            $sUserID = (string) $input['sUserID'];
            $entityID = (string) $input['entityID'];
            $entityTypeID = (string) $input['entityTypeID'];
            $this->myDebug('this is Check in started');

            if ($DBCONFIG->dbType == 'Oracle') {
                if ($entityTypeID == 2) {
                    $result = $this->db->getSingleRow("Select ast.\"AssessmentName\" from Assessments ast where ast.ID = {$entityID} and ast.\"isEnabled\" = '1'");
                    $this->myDebug($detail);
                    $HeadingMessage = "Target Repository : '{$result["AssessmentName"]}' Assessment.";
                } elseif ($entityTypeID == 1) {
                    $result = $this->db->getSingleRow("Select bnk.\"BankName\" from Banks bnk where bnk.ID = {$entityID} and bnk.\"isEnabled\" = '1' ");
                    $HeadingMessage = "Target Repository : '{$result["BankName"]}' Bank.";
                }
            } else {
                if ($entityTypeID == 2) {
                    $result = $this->db->getSingleRow("Select ast.AssessmentName from Assessments ast where ast.ID = {$entityID} and ast.isEnabled = '1'");
                    $this->myDebug($detail);
                    $HeadingMessage = "Target Repository : '{$result["AssessmentName"]}' Assessment.";
                } elseif ($entityTypeID == 1) {
                    $result = $this->db->getSingleRow("Select bnk.BankName from Banks bnk where bnk.ID = {$entityID} and bnk.isEnabled = '1'");
                    $HeadingMessage = "Target Repository : '{$result["BankName"]}' Bank.";
                }
            }


            $sXMLInput = $input['sXMLInput'];
            $this->myDebug('::Checkin Start::');
            $this->myDebug("Input XML");
            $this->myDebug($sXMLInput);
            $sXMLInput = ($this->validateXml($sXMLInput)) ? $sXMLInput : stripslashes($sXMLInput);
            $this->myDebug($sXMLInput);

            $ipDoc = simplexml_load_string($sXMLInput);
            $i = 1;
            $this->myDebug($ipDoc);
            $checkinlog = '';
            if ($ipDoc) {
                foreach ($ipDoc->{'screen'} as $objQuestion) {
                    $questtitle = '';
                    list($node) = $objQuestion->xpath("table/table_details/row[@position='4']/col[@position='2']/para");
                    $questtitle = $this->cleanQuestionTitle($this->getRowTextFromWord($node, true));

                    try {
                        list($node) = $objQuestion->xpath("table/table_details/row[@position='4']/col[@position='2']/para");
                        $questtitle = $this->getRowTextFromWord($node, true);

                        //Get Question Type by Shortname
                        $sLayoutID = $this->getAttribute($objQuestion, 'template_id');
                        if ($DBCONFIG->dbType == 'Oracle')
                            $QuestionTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$sLayoutID}'' ", " qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"EditMode\" , qt.\"isStatic\" ", 'details');
                        else
                            $QuestionTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$sLayoutID}' ", " qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.EditMode , qt.isStatic ", 'details');

                        $EditMode = $QuestionTemplate['EditMode'];
                        $QTypeShortName = $QuestionTemplate['CategoryCode'];
                        $isStatic = $QuestionTemplate['isStatic'];
                        $QuestionTemplateID = $QuestionTemplate['ID'];

                        $questIDfromXml = $this->getAttribute($objQuestion, 'screen_id');
                        $questIDfromXmlnew = $questIDfromXml;
                        $this->myDebug('---------------Testing---------------' . $questIDfromXmlnew);
                        $questioncount = 0;

                        if (is_numeric($questIDfromXml)) {
                            $questioncount = $this->db->getCount("select * from Questions where ID = '$questIDfromXmlnew' and isEnabled = '1'");
                            $this->myDebug('---------------Testing---------------' . $questioncount);
                        }
                        $xml_to_insert = $objQuestion->asXML();
                        //Also Remove Tag PerNode First and then do the Below Remove Span Mass Level as the XSLT does not look for span anymore

                        $xml_to_insert = $this->spanRemoveEachNode($xml_to_insert, ' ');
                        //for Static Page offline without title
                        if ($isStatic == 'Y') {
                            $questtitle = 'Static Page offline - ' . date('F j, Y, g:i a');
                        }
                        $this->myDebug("-------------XML----------");
                        $this->myDebug($xml_to_insert);
                        $this->myDebug("------------End------------");

                        //for Updating Question Content
                        $questid = ($questioncount > 0) ? $questIDfromXml : '';
                        if ($DBCONFIG->dbType == 'Oracle') {
                            $query = "  SELECT DISTINCT ID FROM MapRepositoryQuestions WHERE \"EntityID\" = $entityID AND \"EntityTypeID\" = $entityTypeID AND \"isEnabled\" = '1' AND \"QuestionID\" =$questid ORDER BY ID ";
                        } else {
                            $query = "  SELECT DISTINCT ID FROM MapRepositoryQuestions WHERE EntityID = $entityID AND EntityTypeID = $entityTypeID AND isEnabled = '1' AND QuestionID =$questid ORDER BY ID ";
                        }

                        $data = $this->db->getRows($query);
                        $repoID = (!empty($data)) ? $this->getValueArray($data, 'ID') : 0;
                        $qstinput = array(
                            'Title' => $questtitle,
                            'JSONData' => 'NA',
                            'XMLData' => $xml_to_insert,
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $QuestionTemplateID,
                            'QuestID' => $questid,
                            'RID' => $repoID
                        );
                        $this->myDebug("This is an input data");
                        $this->myDebug($questtitle);
                        $this->myDebug($xml_to_insert);
                        $this->myDebug($sUserID);
                        $this->myDebug($QuestionTemplateID);
                        $this->myDebug($questid);
                        $this->myDebug($repoID);

                        $questid = $qst->newQuestSave($qstinput);
                        //Update XML
                        if ($questioncount > 0) {
                            $mapaction = 'CHECKEDIN';
                            $activity = 'Edited';
                        } else {
                            $mapaction = 'ADDQST';
                            $activity = 'Added';
                        }

			$result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                                            $questid,
                                            $entityID,
                                            $entityTypeID,
                                            0,
                                            $mapaction,
                                            $sUserID,
                                            $this->currentDate(),
                                            $sUserID,
                                            $this->currentDate()
				), 'details'
                        );
                        $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
                        $checkinlog .= '<QstMessage>' . $i . 'Check-In Success :: Title -> ' . $questtitle . '</QstMessage>';
                        $checkinlog .= $xml_to_insert;
                        $arrQuestion = null;
                        $i++;
                    } catch (exception $ex) {
                        $checkinlog .= "<QstMessage>{$i}. Check-In Failed  :: Title -> $questtitle</QstMessage>";
                        $this->myDebug('---------------Exception start---------------');
                        $this->myDebug($ex);
                        $this->myDebug('---------------Exception end---------------');
                        $i++;
                        continue;
                    }
                }
            }

            header('Content-type: text/xml; charset=UTF-8');
            print " <Response><IsValid>true</IsValid>
                        <Message>
                            <BoxHeading>Question(s) check in Information</BoxHeading>
                            <HeadingMessage>{$HeadingMessage}</HeadingMessage>
                            <QstMessages>{$checkinlog}</QstMessages>
                        </Message>
                        <PreviewURL></PreviewURL></Response>";
            die;
        } catch (exception $ex) {
            $this->myDebug("---------------Exception start---------------");
            $this->myDebug($ex);
            $this->myDebug('---------------Exception end---------------');
        }
    }

    /**
     * a function to add new user defined customized template
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    array   $input
     * @return   void
     *
     */
    public function addTemplate($input = array('act' => '')) {
        global $CONFIG, $APPCONFIG, $DBCONFIG;
        //upload zip files
        $guid = uniqid('template');
        $action = $input['act'];

        $target_path_zip = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets/downloads/')) . "{$guid}.zip";
        $target_path_dir = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets/downloads/' . $guid . '/'));

        $this->templateExpandSchema();
        $status = $this->uploadFileToServer($_FILES['templateToUpload']['error'], $_FILES['templateToUpload']['tmp_name'], $target_path_zip);

        //extract zip and verify directory structure
        $this->unzip($target_path_zip, $target_path_dir);
        $objJSONtmp = new Services_JSON();

        if (is_file($target_path_dir . '/Template.txt')) {
            $templatedata = file_get_contents($target_path_dir . '/Template.txt');
            $objTemplateData = $objJSONtmp->decode($templatedata);
            //json template details validate
            $templatedetailvalid = $this->validJson($objTemplateData);

            if ($templatedetailvalid == 'OK') {
                //create sql
                $sqljson = $objTemplateData->SQL;
                if ($DBCONFIG->dbType == 'Oracle') {
                    $query = "SELECT ID FROM QuestionTemplates WHERE \"TemplateFile\" = '{$sqljson->TemplateFile}' and \"isEnabled\" = '1' ";
                } else {
                    $query = "SELECT ID FROM QuestionTemplates WHERE TemplateFile = '{$sqljson->TemplateFile}' and isEnabled = '1' ";
                }

                if ($this->db->getCount($query) == 0) {
                    $verifystatus = $this->verficationAndUploadQuestTemplate($objTemplateData, $target_path_dir);
                    if ($action == 'upload' && $verifystatus['error'] == '') {
                        $verifystatus = $this->verficationAndUploadQuestTemplate($objTemplateData, $target_path_dir, true);
                    }
                    $error = $verifystatus['error'];
                    $uploadedtemplateverification = $verifystatus['uploadedtemplateverification'];
                } else {
                    $error = 'Template already exist';
                    $uploadedtemplateverification = '';
                }
            } else {
                $error = $templatedetailvalid;
                $uploadedtemplateverification = '';
            }
        } else {
            $error = 'Template Detail File is not present';
            $uploadedtemplateverification = '';
        }

        echo "{error: '$error',msg: '" . $uploadedtemplateverification . "'}";
    }

    /**
     * a function to verify and upload the question template if required to the given destination
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @param    object  $objTemplateData
     * @param    string  $target_path_dir
     * @param    boolean $isupload
     * @return   mixed
     *
     */
    public function verficationAndUploadQuestTemplate($objTemplateData, $target_path_dir, $isupload = false) {
        try {
            global $CONFIG;
            $objJSONtmp = new Services_JSON();
            $mandatoryfailed = 0;
            $uploadedtemplateverification = "";

            if ($isupload) {
                $arrquesttype = $this->objectToArray($objTemplateData->SQL);
                $category = $this->db->getSingleRow("select ID from TemplateCategories where CategoryName = '{$objTemplateData->SQL->CategoryCode}' ");
                $arrquesttype['QuestionCategoryID'] = $category['ID'];
                unset($arrquesttype['CategoryName']);
                unset($arrquesttype['CategoryCode']);

                $tbl_field = array_keys($arrquesttype);
                $tbl_value = array_values($arrquesttype);
                if (!empty($tbl_value)) {
                    foreach ($tbl_value as $val) {
                        $escape_arr[] = addslashes($val);
                    }
                }

                $sql = "insert into QuestionTemplates (" . implode(" , ", $tbl_field) . ") values ('" . implode("' , '", $escape_arr) . "')";
                $insertstatus = $this->db->execute($sql);
            }
            $objTemplateExpand = $objJSONtmp->decode(stripslashes($this->templateexpandibility));
            $resourcecount = 0;
            if (!empty($objTemplateExpand)) {
                foreach ($objTemplateExpand as $objTemplateResourceCategory) {
                    $resourcecount++;
                    $need = 'mandatory';
                    if ($resourcecount == 1) {
                        if ($objTemplateData->Configuration->FlashRendition == false)
                            $need = 'optional';
                    }
                    if ($resourcecount == 2) {
                        if ($objTemplateData->Configuration->HtmlRendition == false)
                            $need = 'optional';
                    }
                    if (!empty($objTemplateResourceCategory)) {
                        foreach ($objTemplateResourceCategory as $objTemplateFlashResource) {
                            $location = str_replace('_LAYOUTXML_', $objTemplateData->SQL->TemplateFile, $objTemplateFlashResource->{'location'});
                            $location = str_replace('_HTMLLAYOUT_', $objTemplateData->SQL->HTMLTemplate, $location);
                            $objTemplateFlashResource->{'location'} = $location;

                            $filename = str_replace('_LAYOUTXML_', $objTemplateData->SQL->TemplateFile, $objTemplateFlashResource->{'filename'});
                            $filename = str_replace('_HTMLLAYOUT_', $objTemplateData->SQL->HTMLTemplate, $filename);
                            $objTemplateFlashResource->{'filename'} = $filename;

                            $fullfilepath = $target_path_dir . '/' . $location . '/' . $filename;

                            if ($need == 'mandatory') {
                                if (is_dir($fullfilepath) || is_file($fullfilepath)) {
                                    $objTemplateFlashResource->{'status'} = 'OK';
                                    if ($isupload == true && is_numeric($insertstatus)) {
                                        $uploadrealpath = $CONFIG->rootPath . '/' . $location;
                                        if (!is_dir($uploadrealpath))
                                            mkdir($uploadrealpath, 0777);

                                        if ($filename != '') {
                                            if (is_file($uploadrealpath . '/' . $filename))
                                                unlink($uploadrealpath . '/' . $filename);
                                            copy($fullfilepath, $uploadrealpath . '/' . $filename);
                                        }
                                        else
                                            $this->dirCopy($location, $uploadrealpath);
                                    }
                                }
                                else {
                                    if ($objTemplateFlashResource->{'need'} == 'mandatory')
                                        $mandatoryfailed++;


                                    $objTemplateFlashResource->{'status'} = 'FAILED';
                                }
                            }
                            else {
                                $objTemplateFlashResource->{'need'} = $need;
                                $objTemplateFlashResource->{'status'} = 'N/A';
                            }

                            if ($objTemplateFlashResource->{'filename'} == '')
                                $objTemplateFlashResource->{'filename'} = 'NA';
                            if ($objTemplateFlashResource->{'location'} == '')
                                $objTemplateFlashResource->{'location'} = 'NA';
                            $uploadedtemplateverification .= $objTemplateFlashResource->{'name'} . '|' . $objTemplateFlashResource->{'filename'} . '|' . $objTemplateFlashResource->{'location'} . '|' . $objTemplateFlashResource->{'need'} . '|' . $objTemplateFlashResource->{'status'};
                            $uploadedtemplateverification .= '||';
                        }
                    }
                    $uploadedtemplateverification = rtrim($uploadedtemplateverification, '||');
                    $uploadedtemplateverification .= '|||';
                }
            }
            $uploadedtemplateverification = rtrim($uploadedtemplateverification, '|||');

            if ($mandatoryfailed > 0) {
                $error = 'Mandatory files missing';
            } else {
                if ($isupload == true && is_numeric($insertstatus)) {
                    $error = 'Question Template uploaded successfully';
                    $uploadedtemplateverification = '';
                }
                else
                    $error = '';
            }

            return array(
                'uploadedtemplateverification' => $uploadedtemplateverification,
                'mandatoryfailedcount' => $mandatoryfailed,
                'error' => $error
            );
        } catch (Exception $ex) {
            print $ex->getMessage();
        }
    }

    /**
     * a function to convert object to array
     *
     *
     * @access       public
     * @param        mixed   $object
     * @return       mixed
     *
     */
    function objectToArray($object) {
        if (is_array($object) || is_object($object)) {
            $array = array();
            if (!empty($object)) {
                foreach ($object as $key => $value) {
                    $array[$key] = $this->objectToArray($value);
                }
            }
            return $array;
        }
        return $object;
    }

    /**
     * a function to convert array to object
     *
     *
     * @access       public
     * @param        array   $array
     * @deprecated
     * @return       object
     *
     */
    // Funcion de Array a Objeto
    function arrayToObject($array = array()) {
        return (object) $array;
    }

    /**
     * a function to validate json of new user defined template
     *
     *
     * @access   public
     * @param    object  $objTemplateData
     * @return   string
     *
     */
    function validJson($objTemplateData) {
        if (!isset($objTemplateData))
            return 'malformed template details file';

        if (!isset($objTemplateData->Configuration))
            return 'configuration node is missing';
        else {
            if (!isset($objTemplateData->Configuration->FlashRendition))
                return 'In configuration FlashRendition node is missing';
            if (!isset($objTemplateData->Configuration->HtmlRendition))
                return 'In configuration HtmlRendition node is missing';
        }

        if (!isset($objTemplateData->SQL))
            return 'SQL node is missing';
        else {
            if (!isset($objTemplateData->SQL->TemplateTitle))
                return 'In SQL TemplateTitle node is missing';
            if (!isset($objTemplateData->SQL->CategoryCode))
                return 'In SQL CategoryCode node is missing';
            if (!isset($objTemplateData->SQL->isEnabled))
                return 'In SQL isEnabled node is missing';
            if (!isset($objTemplateData->SQL->FlashStructure))
                return 'In SQL FlashStructure node is missing';
            if (!isset($objTemplateData->SQL->HtmlStructure))
                return 'In SQL HtmlStructure node is missing';
            if (!isset($objTemplateData->SQL->TemplateFile))
                return 'In SQL TemplateFile node is missing';
            if (!isset($objTemplateData->SQL->CategoryName))
                return 'In SQL CategoryName node is missing';
            if (!isset($objTemplateData->SQL->JSONStructure))
                return 'In SQL JSONStructure node is missing';
            if (!isset($objTemplateData->SQL->JSONSchema))
                return 'In SQL JSONSchema node is missing';
            if (!isset($objTemplateData->SQL->EditMode))
                return 'In SQL EditMode node is missing';
            if (!isset($objTemplateData->SQL->isStatic))
                return 'In SQL isStatic node is missing';
            if (!isset($objTemplateData->SQL->isDefault))
                return 'In SQL isDefault node is missing';
        }
        return 'OK';
    }

    /**
     * a function to represent template schema for add template
     *
     *
     * @access   public
     * @global   object  $APPCONFIG
     * @return   void
     *
     */
    function templateExpandSchema() {
        global $APPCONFIG;
        $this->templateexpandibility = '{
            "flashrendition" : {
                    "resource1" : {
                        "name"      : "Rendition Base Xml" ,
                        "location"  : "' . $APPCONFIG->LayoutXMLLocation . '",
                        "filename"  : "_LAYOUTXML_.xml",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource2" : {
                        "name"      : "Rendition css" ,
                        "location"  : "' . $APPCONFIG->FlashRenditionLocation . '_LAYOUTXML_",
                        "filename"  : "_LAYOUTXML_.css",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource3" : {
                        "name"      : "Rendition swf file" ,
                        "location"  : "' . $APPCONFIG->FlashRenditionLocation . '_LAYOUTXML_",
                        "filename"  : "_LAYOUTXML_.swf",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource4" : {
                        "name"      : "Rendition xsl file" ,
                        "location"  : "' . $APPCONFIG->RenditionXSLLocation . '",
                        "filename"  : "_LAYOUTXML_.xsl",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource5" : {
                        "name"      : "Rendition CSS File" ,
                        "location"  : "' . $APPCONFIG->QuizCSSLocation . '_LAYOUTXML_",
                        "filename"  : "_LAYOUTXML_.css",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource6" : {
                        "name"      : "Rendition CSS zip" ,
                        "location"  : "' . $APPCONFIG->QuizCSSLocation . '",
                        "filename"  : "css.zip",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource7" : {
                        "name"      : "Rendition Images" ,
                        "location"  : "' . $APPCONFIG->FlashRenditionLocation . 'images/NVQ/Template_Images",
                        "filename"  : "",
                        "status"    : "",
                        "need"      : "optional"
                    }
                },
            "htmlrendition" : {
                    "resource1" : {
                        "name"      : "Rendition CSS" ,
                        "location"  : "' . $APPCONFIG->HtmlAssessment . '_HTMLLAYOUT_",
                        "filename"  : "_HTMLLAYOUT_.css",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource2" : {
                        "name"      : "Rendition  HTML files" ,
                        "location"  : "' . $APPCONFIG->HtmlAssessment . '_HTMLLAYOUT_",
                        "filename"  : "_HTMLLAYOUT_.htm",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource3" : {
                        "name"      : "Rendition JSfiles" ,
                        "location"  : "' . $APPCONFIG->HtmlAssessment . '_HTMLLAYOUT_/js",
                        "filename"  : "",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource4" : {
                        "name"      : "Preview - Rendition CSS" ,
                        "location"  : "' . $APPCONFIG->QuizCSSLocationforHtml . '_HTMLLAYOUT_",
                        "filename"  : "_HTMLLAYOUT_.css",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource5" : {
                        "name"      : "Rendition CSS Zip File" ,
                        "location"  : "' . $APPCONFIG->QuizCSSLocationforHtml . '",
                        "filename"  : "css.zip",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource6" : {
                        "name"      : "Rendition Images" ,
                        "location"  : "' . $APPCONFIG->HtmlAssessment . 'assessmentimages/template_images",
                        "filename"  : "",
                        "status"    : "",
                        "need"      : "optional"
                    }
                },
            "common" : {
                    "resource1" : {
                        "name"      : "WAT Template" ,
                        "location"  : "' . $APPCONFIG->WordTempDownloadLocation . '",
                        "filename"  : "WAT_QUAD_03_v5.dot",
                        "status"    : "",
                        "need"      : "optional"
                    },
                    "resource2" : {
                        "name"      : "XML to JSON and JSON to XML Conversion Business Logic and Answer verification Business Logic" ,
                        "location"  : "' . $APPCONFIG->QuestionTemplateResourcePath . '",
                        "filename"  : "_LAYOUTXML_.php",
                        "status"    : "",
                        "need"      : "mandatory"
                    },
                    "resource3" : {
                        "name"      : "Template Details" ,
                        "location"  : "",
                        "status"    : "",
                        "filename"  : "Template.txt",
                        "need"      : "mandatory"
                    }
                }
            }';
    }

    /**
     * a function to create assessment settings object from given xml
     *
     *
     * @access   public
     * @global   object  $QTIQuiz
     * @param    object  $xmldata
     * @return   object
     *
     */
    ///Following functions are used for QTI Import functionality    ...

    function parseQtiQuiz($xmldata) {
        global $QTIQuiz;
        $this->myDebug("Begin parseQTIQuiz ");
        $version = '1.2';

        $quiz_arr = $QTIQuiz;
        $quiz_arr->info->refID = $this->getAttribute($xmldata->assessment, 'ident');
        $quiz_arr->info->title = $this->getAttribute($xmldata->assessment, 'title');
        $quiz_arr->info->bannertitle = $this->getAttribute($xmldata->assessment, 'title');
        $quiz_arr->info->description = "QTI Import: v{$version} Ref ID: {$quiz_arr->info->refID}";
        $quiz_arr->info->tags = '';
        $quiz_arr->info->taxonomy = '';
        $quiz_arr->info->randomquestions = '0';
        $quiz_arr->info->type = '1';  // values: 1=summative | 2=formative   , default:1
        $quiz_arr->info->mode = '1'; // values: 1=regular | 2=random  , default:1
        $quiz_arr->settings->shuffle = '2';
        $quiz_arr->settings->timer = '2';
        $quiz_arr->settings->mins = '';
        $quiz_arr->settings->movement = '1';
        $quiz_arr->settings->skip = '1';
        $quiz_arr->settings->hint = '1';
        $quiz_arr->settings->help = '1';
        $quiz_arr->settings->questnum = '1';
        $quiz_arr->settings->map = '1';
        $quiz_arr->settings->flag = '2';
        $quiz_arr->scores->score1 = '';
        $quiz_arr->scores->score2 = '';
        $quiz_arr->scores->score3 = '';
        $quiz_arr->scores->message1 = '';
        $quiz_arr->scores->message2 = '';
        $quiz_arr->scores->message3 = '';
        $quiz_arr->scores->action1 = '';
        $quiz_arr->scores->action2 = '';
        $quiz_arr->scores->action3 = '';
        $quiz_arr->scores->scoretype = '1';
        $quiz_arr->scores->totalscore = '';
        $quiz_arr->scores->passscore = '';
        $quiz_arr->scores->partialscore = '2';
        $quiz_arr->scores->exitmesg = 'Are you sure you want to exit the quiz?';
        $quiz_arr->scores->quizhelp = '';
        $quiz_arr->scores->tries = '1';
        $quiz_arr->scores->showresult = '1';
        $quiz_arr->scores->questionfeedback = '1';
        $quiz_arr->scores->optionfeedback = '1';
        $quiz_arr->scores->tryagain = '1';
        $quiz_arr->scores->status = '1';
        $quiz_arr->scores->producttype = 0;

        $this->myDebug('End parseQTIQuiz ');
        return $quiz_arr;
    }

    /**
     * a function to get status of answer choice of the given option for QTI Multiple Choice type Question
     *
     *
     * @access   public
     * @param    object  $optresponse
     * @param    string  $refID
     * @return   string
     *
     */
    function getQtiQuestionFlagByRefId($optresponse, $refID) {
        list($optRefID) = $optresponse->xpath('conditionvar/varequal');
        if ($optRefID == $refID) {
            return 'true';
        }
        return 'false';
    }

    /**
     * a function to get value of answer choice of the given option for QTI Matching type Question
     *
     *
     * @access   public
     * @param    object  $optresponse
     * @param    string  $optRefID
     * @return   mixed
     *
     */
    function getQTIOptionValueByRefId($optresponse, $optRefID) {
        list($respnode) = $optresponse->xpath("conditionvar/varequal[@respident='$optRefID']");
        return $respnode;
    }

    /**
     * a function to get status of answer choice of the given option for QTI Matching type Question
     *
     *
     * @access   public
     * @param    string  $opt
     * @return   string
     *
     */
    function getQTIOptionValue($opt) {
        $isText = true;
        $isText = (strpos($opt, 'src=') > 1) ? false : true;
        if ($isText) {
            return $opt;
        } else {
            $opt = 'media file';
        }
        return $opt;
    }

    /**
     * a function to get feedback status of the given QTI question
     *
     *
     * @access   public
     * @param    array   $fdbkArr
     * @param    string  $stat
     * @return   array
     *
     */
    function getQtiQuestionFeeback($fdbkArr, $stat) {
        if (!empty($fdbkArr)) {
            foreach ($fdbkArr as $fdbk) {
                $quesStat = $this->getAttribute($fdbk, 'ident');
                if ($quesStat == $stat) {
                    $retVal = trim($fdbk->material->mattext);
                    return $retVal;
                }
            }
        }
    }

    /**
     * a function to get json of the question as required from QTI question
     *
     *
     * @access   public
     * @param    string  $questType
     * @param    string  $q
     * @return   string
     *
     */
    function getQtiQuestionJson($questType, $q) {
        $refID = $this->getAttribute($q, 'ident');

        $correctfeedback = $this->getQtiQuestionFeeback($q, 'correct');
        $incorrectfeedback = $this->getQtiQuestionFeeback($q, 'incorrect');

        if ($questType == 'Multiple Choice') {
            $qTitle = addslashes($q->presentation->material->mattext);
            $options = $q->presentation->response_lid->render_choice->response_label;
            $optresponse = $q->resprocessing->respcondition;

            $questJSON = '{';
            $questJSON .= '"question_title":"' . $qTitle . '",';
            $questJSON .= '"question_text":"' . $qTitle . '",';
            $questJSON .= '"instruction_text":"",';

            $questJSON .= '"choices":[';
            $optJSON = '';
            if (!empty($options)) {
                foreach ($options as $opt) {
                    $optRefID = $this->getAttribute($opt, "ident");
                    $stat = $this->getQtiQuestionFlagByRefId($optresponse, $optRefID);
                    $optJSON .= '{
                                        "val1":' . $stat . ',
                                        "val2":"' . $opt->material->mattext . '",
                                        "val3":""
                                    },';
                }
            }
            $optJSON = substr($optJSON, 0, strlen($optJSON) - 1);
            $questJSON .= $optJSON;
            $questJSON .= '],';

            $questJSON .= '"correct_feedback":"' . $correctfeedback . '",';
            $questJSON .= '"partialcorrect_feedback":"' . $incorrectfeedback . '",';
            $questJSON .= '"incorrect_feedback":"",';
            $questJSON .= '"metadata":[
                                        {
                                            "text":"Score",
                                            "val":"1"
                                        },{
                                            "text":"Difficulty",
                                            "val":""
                                        },{
                                            "text":"Learning Object",
                                            "val":""
                                        }
                                        ],
                            ';
            $questJSON .= '
                                "hint":"",
                                "notes_editor":""
                                }
                            ';
        } else if ($questType == 'Matching') {
            $qTitle = addslashes($q->presentation->material->mattext);
            $options = $q->presentation->response_lid;
            $optresponse = $q->resprocessing->respcondition;
            $questJSON = '{';
            $questJSON .= '"question_title" :"' . $qTitle . '",';
            $questJSON .= '"question_text"  :"' . $qTitle . '",';
            $questJSON .= '"column1_heading":"Column1",';
            $questJSON .= '"column2_heading":"Column2",';
            $questJSON .= '"instruction_text":"",';

            $questJSON .= '"choices":[';
            $optJSON = '';
            $ctr = 0;
            if (!empty($options)) {
                foreach ($options as $opt) {
                    $optatribs = $opt->attributes();
                    $optRefID = $optatribs['ident'];
                    $val1 = $this->getQTIOptionValue($opt->material->mattext);
                    $val2 = $this->getQTIOptionValueByRefID($optresponse, $optRefID);
                    $optJSON .= '{
                                        "val1":"' . $val1 . '",
                                        "val2":"' . $val2 . '"
                                   },';
                }
            }
            $optJSON = substr($optJSON, 0, strlen($optJSON) - 1);

            $questJSON .= $optJSON;
            $questJSON .= '],';
            $questJSON .= '"correct_feedback":"' . $correctfeedback . '",';
            $questJSON .= '"partialcorrect_feedback":"' . $incorrectfeedback . '",';
            $questJSON .= '"incorrect_feedback":"",';
            $questJSON .= '"metadata":[
                                            {
                                                "text"  :"Score",
                                                "val"   :"1"
                                            },{
                                                "text"  :"Difficulty",
                                                "val"   :""
                                            },{
                                                "text"  :"Learning Object",
                                                "val"   :""
                                            }
                                        ],
                            ';
            $questJSON .= '
                                "hint":"",
                                "notes_editor":""
                                }
                            ';
        }

        return $questJSON;
    }

    /**
     * a function to get question type ID as per QuAD Specification from given question type
     *
     *
     * @access   public
     * @param    string  $qType
     * @return   integer
     *
     */
    function getQuadQuestionTypeId($qType) {
        $qt = 1;
        switch ($qType) {
            case 'Multiple Choice': ///Multiple Choice Single Select [MCSS text without media]
                $qt = 2;
                break;

            case 'Multiple Response': ///Multiple Choice Multiple Select [MCMS text without media]
                $qt = 3;
                break;

            case 'Matching': ///Linking Lines
                $qt = 14;
                break;

            //case 'Multiple Choice':
            case 'Numeric':
            case 'Pull-down list':
            case 'Fill in Blanks, Matrix':
            case 'Select a Blank':
            case 'Essay':
            case 'Ranking':
            case 'Java':
            case 'Macromedia Flash':
                $qt = 0;
                break;

            default:
                $qt = 0;
                break;
        }

        return $qt;
    }

    /**
     * a function to check whether given question type is supported in QuAD
     *
     *
     * @access   public
     * @param    string  $qType
     * @return   boolean
     *
     */
    function supportedQuestionTypes($qType) {
        $qt = false;
        switch ($qType) {
            case 'Multiple Choice':
                $qt = true;
                break;

            case 'Multiple Response':
                $qt = false;
                break;

            case 'Matching':
                $qt = true;
                break;

            case 'Numeric':
            case 'Pull-down list':
            case 'Fill in Blanks, Matrix':
            case 'Select a Blank':
            case 'Essay':
            case 'Ranking':
            case 'Java':
            case 'Macromedia Flash':
                $qt = false;
                break;

            default:
                $qt = false;
                break;
        }

        return $qt;
    }

    /**
     * a function to get unique question types from given string
     *
     *
     * @access   public
     * @param    string  $arrayStr
     * @return   string
     *
     */
    function getUniqueFormats($arrayStr) {
        $arrList = explode(',', $arrayStr);
        $unq_arr = array_unique($arrList);
        $arrStr = implode(', ', $unq_arr);

        return $arrStr;
    }

    /**
     * a function to get array of valid question types supported in QuAD
     *
     *
     * @access   public
     * @return   array
     *
     */
    function validQuestionTypes() {
        $qTypes = array(
            'Multiple Choice',
            'Matching',
            'Essay', /*
                  'Multiple Response',
                  'Numeric',
                  'Pull-down list',
                  'Fill in Blanks, Matrix',
                  'Select a Blank',
                  'Ranking',
                  'Java',
                  'Macromedia Flash' */
        );
        return $qTypes;
    }

    /**
     * a function to get string of valid question types supported in QuAD
     *
     *
     * @access   public
     * @return   string
     *
     */
    function getSupportedFormats() {
        $qTypes = $this->validQuestionTypes();
        $retStr = "";
        if (!empty($qTypes)) {
            foreach ($qTypes as $q) {
                if ($this->supportedQuestionTypes($q)) { //true
                    $retStr .= $q . ",";
                }
            }
        }
        $retStr = substr($retStr, 0, strlen($retStr) - 1);
        return $retStr;
    }

    /**
     * a function to get string of not supported question types in QuAD
     *
     *
     * @access   public
     * @return   string
     *
     */
    function getUnsupportedFormats() {
        $qTypes = $this->validQuestionTypes();
        $retStr = "";
        if (!empty($qTypes)) {
            foreach ($qTypes as $q) {
                if (!$this->supportedQuestionTypes($q)) { //not true
                    $retStr .= $q . ",";
                }
            }
        }
        $retStr = substr($retStr, 0, strlen($retStr) - 1);
        return $retStr;
    }

    /**
     * a function to format the given json in unique pattern
     *
     *
     * @access   public
     * @param    string  $jsonData
     * @param    string  $cnvrtFlg
     * @return   string
     *
     */
    function formatJson($jsonData, $cnvrtFlg = '1') {
        if ($cnvrtFlg):
            $jsonData = str_replace('"', "'", $jsonData);
        else:
            $jsonData = str_replace("'", '"', $jsonData);
        endif;

        return $jsonData;
    }

    /**
     * a function to set question template type of the given repositories if given
     *
     *
     * @access   public
     * @param    integer $CateID
     * @param    integer $RepoID
     * @return   boolean
     *
     */
    function updateLayout($CateID = -1, $RepoID = -1) {
        global $DBCONFIG;
        if ($CateID != -1 && $RepoID != -1) {
            if ($DBCONFIG->dbType == 'Oracle') {
                $query = "UPDATE MapRepositoryQuestions SET \"QuestionTemplateID\" = $CateID WHERE ID = ($RepoID) ";
            } else {
                $query = "UPDATE MapRepositoryQuestions SET QuestionTemplateID = $CateID WHERE ID = ($RepoID) ";
            }

            return $this->db->execute($query);
        }
    }

    /**
     * a function to get the xml for label the diagram type question
     *
     *
     * @access   public
     * @param    array   $input
     * @return   array
     *
     */
    function getLableQuestXml_old($input) {
        $questJson = $input['JSONData'];
        $ath = new Authoring();
        $qst = new Question();
        $questJson = $ath->getImageWebPath($questJson);
        $questJson = $ath->formatIE($questJson);
        $questJson = stripslashes($questJson);

        $flashxml = html_entity_decode($input['imagexml']);
        $flashxml = str_replace('<options>', '<screen>', $flashxml);
        $flashxml = str_replace('</options>', '', $flashxml);

        $objJSONtmp2 = new Services_JSON();
        $objJson2 = $objJSONtmp2->decode($questJson);
        $flashxmlobj = simplexml_load_string(html_entity_decode($input['imagexml']), null, LIBXML_NOCDATA);
        $converter = new DataConverter();
        $flashxmlarray = $converter->convertXmlToArray($flashxmlobj->asXML());

        $flashJSON = $objJSONtmp2->encode($flashxmlarray);
        $flashJSON = $objJSONtmp2->decode($flashJSON);

        $this->myDebug($objJson2);
        $objJson2->{'imagexml'} = $flashJSON;
        $this->myDebug($objJson2);
        $jsondata = $objJSONtmp2->encode($objJson2);
        $this->myDebug($jsondata);

        $xmli = '<topicTitle><![CDATA[' . $objJson2->{'question_title'} . ']]> </topicTitle>';
        $xmli .= '<questionInstruction><![CDATA[' . $objJson2->{'instruction_text'} . ']]> </questionInstruction>';
        $xmli .= "<questionStem audioPath=\"_STEMAUDIOPATH_\"><![CDATA[" . $objJson2->{'question_text'} . ']]> </questionStem>';
        $xmli .= '<optionAudio >_OPTIONSAUDIOPATH_</optionAudio>';
        $xmli .= '<feedback>';
        $xmli .= '<correct><![CDATA[' . $objJson2->{'correct_feedback'} . ']]> </correct>';
        $xmli .= '<incorrect><![CDATA[' . $objJson2->{'incorrect_feedback'} . ']]> </incorrect>';
        $xmli .= '<partialcorrect><![CDATA[' . $objJson2->{'parcorrect_feedback'} . ']]> </partialcorrect>';
        $xmli .= '</feedback>';
        $xmli .= '<hint><![CDATA[' . $objJson2->{'hint'} . ']]> </hint>';
        $xmli .= '<maxAttempt>5</maxAttempt>';
        $xmli .= '<score>' . $objJson2->{'metadata'}[0]->{'val'} . '</score>';
        $xmli .= '</screen>';

        $flashxml .= $xmli;
        $flashxml = str_replace('&lt;p&gt;', '', $flashxml);
        $flashxml = str_replace('&lt;/p&gt;', '', $flashxml);
        $flashxml = stripslashes($flashxml);

        return array(
            'XMLData' => $qst->removeMediaPlaceHolder($flashxml),
            'JSONData' => $qst->removeMediaPlaceHolder($jsondata)
        );
    }

	function getLableQuestXml($input) {

		$questJson = $input['JSONData'];
        $ath = new Authoring();
        $qst = new Question();
        $questJson = $ath->getImageWebPath($questJson);
        $questJson = $ath->formatIE($questJson);
        $questJson = stripslashes($questJson);

        $objJSONtmp2 = new Services_JSON();
        $objJson2 = $objJSONtmp2->decode($questJson);

        $objJson2->{'imagexml'} = '';

        $jsondata = $objJSONtmp2->encode($objJson2);
        $this->myDebug($jsondata);
 $object = json_decode($jsondata, TRUE);
        $hotspot_choices = array();
        $dist_choices = array();
        $hschoices = array();
        $dchoices = array();
        foreach ($object as $key => $value) {
            if ($key == "appLevel") {
                if (!empty($value['hot_spot_details']['hot_spot'])) {
                    foreach ($value['hot_spot_details']['hot_spot'] as $hotspot) {
                        $label = $hotspot['label'];
                        $id = $hotspot['-id'];
                        $position = $hotspot['-position'];
                        $box_position = $hotspot['box_position'];
                        $hotspot_choices['val1'] = "1";
                        $hotspot_choices['val2'] = $label;
                        $hotspot_choices['val3'] = $id;
                        $hotspot_choices['val4'] = $position;
                        $hotspot_choices['val5'] = $box_position;
                        $hschoices[] = $hotspot_choices;
                    }
                } else {
                    $hschoices[] = '';
                }
                if (!empty($value['distractor_details']['distractor'])) {
                    foreach ($value['distractor_details']['distractor'] as $dist) {
                        $label = $dist['label'];
                        $id = $dist['-id'];
                        $dist_choices['val1'] = "";
                        $dist_choices['val2'] = $label;
                        $dist_choices['val3'] = $id;
                        $dchoices[] = $dist_choices;
                    }
                } else {
                    $dchoices[] = '';
                }
                $choices = array_merge($hschoices, $dchoices);
                $object['choices'] = $choices;
                array_push($choices, $object);
            }
        }
        //echo json_encode($object);
        //$object = convert_splchars_to_entity($object);
        $jsondata = json_encode($object);
        $this->myDebug("=====LTD==new====end=");
        $this->myDebug($jsondata);
        return array(
         // 'XMLData' => $qst->removeMediaPlaceHolder($flashxml),
          'XMLData' => '',
          'JSONData' => $qst->removeMediaPlaceHolder($jsondata)
	);
    }

    /*     * *    Code for Online Css Starts  ** */

    /**
     * a function to add given css file in css file array
     *
     *
     * @access   public
     * @param    string  $filename
     * @return   void
     *
     */
    public function getCssFile($filename) {
        if ($filename != '') {
            $this->cssList[] = $filename;
        }
    }

    /**
     * a function to add given file in css version files array
     *
     *
     * @access   public
     * @param    string  $filename
     * @return   void
     *
     */
    public function getVersionFiles($filename) {
        if ($filename != '' && strstr($filename, $this->versionFile)) {
            $this->cssList[] = $filename;
        }
    }

    /**
     * a function to get the list of css files as xml
     *
     *
     * @access   public
     * @param    array   $param
     * @return   void
     *
     */
    public function cssList($param) {
        header('Content-type: application/json; charset=UTF-8');
        $xml = '';
        $path = $this->cfg->rootPath . '/';

        //$path          .= (strtolower($param['renditionType']) == 'html') ? $this->cfgApp->QuizCSSLocationforHtml:$this->cfgApp->QuizCSSLocation;

        $r_type = strtolower($param['renditionType']);

        switch ($r_type) {
            case 'html':
                $path .= $this->cfgApp->QuizCSSLocationforHtml;
                break;
            case 'html5':
                //return $this->getHtml5CssList();
                //include $this->cfg->rootPath . 'models' . DIRECTORY_SEPARATOR . 'Authoring' . DIRECTORY_SEPARATOR . 'Html5'  . DIRECTORY_SEPARATOR . 'CssEditor.php';
                $_REQUEST['EntityID'] OR $_REQUEST['EntityID'] = $this->recordid;
                return Authoring_Html5_CssEditor::getInstance()->getAll();
                break;
            default:
                $path .= $this->cfgApp->QuizCSSLocation;
                break;
					}


        $cssfiles = $this->getCssFiles($path);

        array_walk_recursive($cssfiles, array($this, 'getCssFile'));

        if (!empty($this->cssList)) {
            foreach ($this->cssList as $key => $css) {
                ob_start();
                include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/authoring/CssXml.php';
                $xml.= ob_get_contents();
                ob_end_clean();
            }
        }
        $xmlresponse = "<cssfiles>$xml</cssfiles>";
        echo $xmlresponse;
        die;
    }

    /**
     * a function to get the directory name of the given css file
     *
     *
     * @access   public
     * @param    string  $fileName
     * @return   string
     *
     */
    function getDirName($fileName) {
        $dir = '';
        switch (strtolower($fileName)) {
            case 'shell.css':
            case 'result.css':
                $dir = '';
                break;

            default:
                $dir = substr($fileName, 0, -4);
                $dir.= '/';
                break;
        }
        return $dir;
    }

    /**
     * a function to get the list of css files in the given path directory
     *
     *
     * @access   public
     * @param    string  $path
     * @return   array
     *
     */
    public function getCssFiles($path) {
        $cssfiles = array();

        if (is_dir($path)) {
            $dir = opendir($path);
            while (false != ($file = readdir($dir))) {
                if (is_dir($path . '/' . $file) && $file != '.' && $file != '..') {
                    $cssfiles[] = $this->getCssFiles($path . '/' . $file);
                } else {
                    if (($file != '.') and ($file != '..')) {
                        if (strstr($file, '.css')) {
                            $cssfiles[] = basename($file);
                        }
                    }
                }
            }
            closedir($dir);
        }
        return $cssfiles;
    }

    /**
     * a function to get the content of the css file
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function displayCss(array $params) {
        $content = '';
        switch (strtolower($params['action'])) {
            case 'view':
                $content = Authoring_Html5_CssEditor::getInstance()->view($params); //$content = $this->getCssView($params);
                break;

            case 'edit':
                $content = Authoring_Html5_CssEditor::getInstance()->edit($params); //$content = $this->getCssEdit($params);
                break;
        }
        return $content;
    }

    /**
     * a function to get the formatted html content for view of the css file
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function getCssView($params) {
        $content = '';
        //$pluginPath     = $this->cfg->rootPath.'/plugins/geshi/';
        //$pluginWebPath  = $this->cfg->wwwroot.'/plugins/geshi/';
        $file = $this->getCssFileName($params);

        ob_start();
        //include $pluginPath.'/index.php';
        echo '<pre>';
        echo file_get_contents($file);
        echo '</pre>';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * a function to get the html content for edit of the css file
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function getCssEdit($params) {
        $file = $this->getCssFileName($params);
        $content = file_get_contents($file);
        $verbose = new Verbose();

        ob_start();
        include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/authoring/CssEdit.php';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * a function to get css file name with complete path
     *
     *
     * @access   public
     * @param    array   $params
     * @param    string  $param
     * @return   string
     *
     */
    function getCssFileName($params, $param = '') {
        $path = $this->cfg->rootPath . '/';

        if ($params['version'] == 'version') {
            $filep = ($param != '') ? $param : 'file';
            ///$path  .= (strtolower($params['rendition']) == 'html') ? $this->cfgApp->UserQuizCSSLocationforHtml:$this->cfgApp->UserQuizCSSLocation;
            $path .= $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/";
            $path .= ( strtolower($params['rendition']) == 'html') ? $this->cfgApp->UserQuizHtmlCSS : $this->cfgApp->UserQuizFlashCSS;
            if ($params['entityTypeID'] == 1) {
                $path .= strtolower($this->getEntityName(1)) . '/';
            } elseif ($params['entityTypeID'] == 2) {
                $path .= strtolower($this->getEntityName(2)) . '/';
            }
            $path .= $params['entityID'] . '/cssversion/' . $params[$filep];
            $file = $path;
        } else {
            if ($params['type'] == 'default') {
                $path .= ( strtolower($params['rendition']) == 'html') ? $this->cfgApp->QuizCSSLocationforHtml : $this->cfgApp->QuizCSSLocation;
            } else {
                $path .= $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/";
                ///$path   .= (strtolower($params['rendition']) == 'html')?$this->cfgApp->UserQuizCSSLocationforHtml:$this->cfgApp->UserQuizCSSLocation;
                $path .= ( strtolower($params['rendition']) == 'html') ? $this->cfgApp->UserQuizHtmlCSS : $this->cfgApp->UserQuizFlashCSS;
                if ($params['entityTypeID'] == 1) {
                    $path .= strtolower($this->getEntityName(1)) . '/';
                } elseif ($params['entityTypeID'] == 2) {
                    $path .= strtolower($this->getEntityName(2)) . '/';
                }
                $path .= $params['entityID'];
            }

            $dir = $this->getDirName($params['file']);
            $file = $path . '/' . $dir . $params['file'];
        }
        return $file;
    }

    /**
     * a function to get css file name for next version
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function getCssVersionFile($params) {
        $path = $this->cfg->rootPath . '/' . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/";
        ///$path  .= (strtolower($params['rendition']) == 'html') ? $this->cfgApp->UserQuizCSSLocationforHtml:$this->cfgApp->UserQuizCSSLocation;
        $path .= ( strtolower($params['rendition']) == 'html') ? $this->cfgApp->UserQuizHtmlCSS : $this->cfgApp->UserQuizFlashCSS;
        if ($params['entityTypeID'] == 1) {
            $path .= strtolower($this->getEntityName(1)) . '/';
        } elseif ($params['entityTypeID'] == 2) {
            $path .= strtolower($this->getEntityName(2)) . '/';
        }
        $path .= $params['entityID'] . '/cssversion';
        $index = 0;

        if (!is_dir($path)) {
            mkdir($path, 0777);
        }

        $dir = opendir($path);
        while (false != ($file = readdir($dir))) {
            if (($file != '.') and ($file != '..')) {
                $files = explode('_', $file);

                if ($files[0] . '.css' == $params['file']) {
                    $curindex = explode('.', $files[1]);
                    if ($curindex[0] > $index) {
                        $index = $curindex[0];
                    }
                }
            }
        }
        closedir($dir);
        $files = explode('.css', $params['file']);
        $filename = $path . '/' . $files[0] . '_' . ($index + 1) . '.css';
        return $filename;
    }

    /**
     * a function to handle edit action of css file
     *
     *
     * @access   public
     * @param    array   $params
     * @return   boolean
     *
     */
    function cssEditSubmit($params) {
        Authoring_Html5_CssEditor::getInstance()->save($params);

        return true;
        try {
            $file = $this->getCssFileName($params);
            $this->myDebug("This is file.");
            $this->myDebug($file);
            $handle = fopen($file, 'w');
            fwrite($handle, $params['csscontent']);
            fclose($handle);

            $this->writeToVersion($params);
        } catch (exception $ex) {
            $this->myDebug('::Error in Css editing');
            $this->myDebug($ex);
        }
        return true;
    }

    /**
     * a function to write next css version file
     *
     *
     * @access   public
     * @param    array   $params
     * @return   void
     *
     */
    function writeToVersion($params) {
        try {
            $file = $this->getCssVersionFile($params);
            $handle = fopen($file, 'w');
            fwrite($handle, $params['csscontent']);
            fclose($handle);
        } catch (exception $ex) {
            $this->myDebug('::Error in file writing');
            $this->myDebug($ex);
        }
    }

    /**
     * a function to get difference of given css files as html content
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function showCssDiff($params) {
        if ($params['version'] != 'version') {
            $file2 = $this->getCssFileName($params);
            $dest = basename($file2) . ' (Custom)';
            $params['type'] = 'default';
            $file1 = $this->getCssFileName($params);
            $source = basename($file1) . ' (Default)';
        } else {
            $file1 = $this->getCssFileName($params, 'file2');
            $basefile1 = basename($file1);

            preg_match('/\d/', $basefile1, $matches);

            $basefile1 = preg_replace('/_(\d*)/', '', $basefile1);
            $source = $basefile1 . " Version({$matches[0]})";

            $file2 = $this->getCssFileName($params);
            $basefile2 = basename($file2);

            preg_match('/\d/', $basefile2, $matches);

            $basefile2 = preg_replace('/_(\d*)/', '', $basefile2);
            $dest = $basefile2 . " Version({$matches[0]})";
        }

        $compare = new Compare();
        $response = $compare->compareCSS($file1, $file2);

        if ($response == 1 || $response == true) {
            ob_start();
            include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/CssDiff.php';
            $content = ob_get_contents();
            ob_end_clean();
        } else {
            $content = 1;
        }
        return $content;
    }

    /**
     * a function to handle css files download
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @param    array   $params
     * @return   string
     *
     */
    public function downloadCss($params) {
        global $CONFIG, $APPCONFIG;
        $type = $params['type'];
        $guid = uniqid();
        $tempfolder = "css" . $guid;
        $tmp_file = $tempfolder . ".zip";
        if ($type == "default") {
            $source_dir = $CONFIG->rootPath . '/' . $APPCONFIG->QuizCSSLocationforHtml;
        }
        if ($type == "uploaded") {
            if ($params['entityTypeID'] == 1) {
                $source_dir = $CONFIG->rootPath . '/' . $APPCONFIG->UserQuizCSSLocationforHtml . strtolower($this->getEntityName(1)) . '/' . $params['entityID'];
            } elseif ($params['entityTypeID'] == 2) {
                $source_dir = $CONFIG->rootPath . '/' . $APPCONFIG->UserQuizCSSLocationforHtml . strtolower($this->getEntityName(2)) . '/' . $params['entityID'];
            }
            //$source_dir=$CONFIG->rootPath.'/'.$APPCONFIG->UserQuizCSSLocationforHtml.$params['quizid'];
        }
        $source_temp_dir = $CONFIG->rootPath . '/' . $APPCONFIG->QuizCSSImageUnzipTempLocation . '/' . $tempfolder;
        mkdir($source_temp_dir, 0777);
        $this->dirCopy($source_dir, $source_temp_dir);
        if (file_exists($source_temp_dir . "/vssver.scc")) {
            unlink($source_temp_dir . "/vssver.scc");
        }
        if (file_exists($source_temp_dir . "/css.zip")) {
            unlink($source_temp_dir . "/css.zip");
        }
        $tardir = $APPCONFIG->QuizCSSImageUnzipTempLocation . '/' . $tmp_file;
        $this->makeZip($source_temp_dir, $CONFIG->rootPath . '/' . $tardir);
        if (is_dir($source_temp_dir)) {
            $this->rmDirRecurse($source_temp_dir . '/');
            if (is_dir($source_temp_dir))
                rmdir($source_temp_dir);
        }
        //$cssurl=$CONFIG->wwwroot.'/'.$tardir;
        $cssurl = $CONFIG->wwwroot . '/authoring/download/f:' . $tmp_file . '|path:' . $APPCONFIG->QuizCSSImageUnzipTempLocation . '|rand:' . $guid;

        return $cssurl;
    }

    /**
     * a function to upload new image for the image specified in css
     *
     *
     * @access   public
     * @param    integer $assessmentID
     * @param    string  $origImageFolder
     * @param    string  $origImageName
     * @param    string  $rendition
     * @return   string
     *
     */
    public function uploadCssImage($assessmentID, $origImageFolder, $origImageName, $rendition, $entityTypeID = 2) {
        $user = new user();
        $msg = '';
        $fileElementName = 'cssimage';
        $error = $user->validateUpload($fileElementName);

        if ($error == '') {
            $image = new SimpleImage();

            $imageName = substr_replace($this->fileParam($fileElementName, 'name'), uniqid(), 0, strrpos($this->fileParam($fileElementName, 'name'), '.'));
            list($width, $height, $type, $attr) = getimagesize($this->fileParam($fileElementName, 'tmp_name'));

            if ($origImageFolder != 'csstmpimages') {
                $cssImagePath = $this->cfg->rootPath . '/' . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/" . $this->cfgApp->UserQuizHtmlCSS . strtolower($this->getEntityName($entityTypeID)) . '/' . $assessmentID . '/assessmentimages/' . $origImageFolder . '/' . $origImageName;
                /* if($entityTypeID == 1)
                  {
                  list($origWidth, $origHeight)       = getimagesize($this->cfg->rootPath.'/'.$this->cfgApp->UserQuizCSSLocationforHtml.strtolower($this->getEntityName(1)).'/'.$assessmentID.'/assessmentimages/'.$origImageFolder.'/'.$origImageName);
                  }
                  elseif($entityTypeID == 2)
                  {
                  list($origWidth, $origHeight)       = getimagesize($this->cfg->rootPath.'/'.$this->cfgApp->UserQuizCSSLocationforHtml.strtolower($this->getEntityName(2)).'/'.$assessmentID.'/assessmentimages/'.$origImageFolder.'/'.$origImageName);
                  } */
                if ($entityTypeID == 1 || $entityTypeID == 2) {
                    list($origWidth, $origHeight) = getimagesize($cssImagePath);
                }
            } else {
                list($origWidth, $origHeight) = getimagesize($this->cfg->rootPath . '/data/renditions/' . $origImageFolder . '/' . $origImageName);
            }

            //print $this->cfg->rootPath.'/'.$this->cfgApp->UserQuizCSSLocation.$this->session->getValue('instID')."/".$this->cfgApp->UserQuizHtmlCSS.strtolower($this->getEntityName($entityTypeID)).'/'.$assessmentID.'/assessmentimages/'.$origImageFolder.'/'.$origImageName;
            //print $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->tempCssImages.$imageName;

            $flag = 0;
            if ($origWidth != $width) {
                $error = "Width should be {$origWidth}";
                $flag = 1;
            } elseif ($origHeight != $height) {
                $error = "Height should be {$origHeight}";
                $flag = 1;
            }

            if ($flag == 0) {
                $image->load($this->fileParam($fileElementName, 'tmp_name'));
                //$image->save($this->cfg->rootPath.'/data/renditions/csstmpimages/'.$imageName);
                $cssTempPtah = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . "/" . $this->cfgApp->tempCssImages;
                if (!is_dir($cssTempPtah)) {
                    mkdir($cssTempPtah, 0777);
                }
                $image->save($cssTempPtah . $imageName);
                //$image->save($this->cfg->rootPath.$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->tempCssImages.$imageName);
            }

            $msg .= $imageName;
        }

        return "{error: '" . $error . "',\n msg: '" . $msg . "'\n }";
    }

    /**
     * a function to save uploaded new image for the image specified in css
     *
     *
     * @access   public
     * @param    array   $param
     * @return   void
     *
     */
    function saveCssImage(array $param) {
        $scrImage = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . "/" . $this->cfgApp->tempCssImages . $param['tempimage'];
        if (file_exists($scrImage)) {
            if ($param['entityTypeID'] == 1 || $param['entityTypeID'] == 2) {
                $destImage = $this->cfg->rootPath . '/' . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/" . $this->cfgApp->UserQuizHtmlCSS . strtolower($this->getEntityName($param['entityTypeID'])) . '/' . $param['entityID'] . '/assessmentimages/' . $param['origfolder'] . '/' . $param['origimage'];
                copy($scrImage, $destImage);
            }
        }
    }

    /**
     * a function to get the content of css version file as xml
     *
     *
     * @access   public
     * @param    array   $param
     * @return   void
     *
     */
    function cssVersion(array $param) {
        //header('Content-type: text/xml; charset=UTF-8');
        $xml = '';
        //$path               = $this->cfg->rootPath.'/';
        //$path              .= (strtolower($param['renditionType']) == 'html') ? $this->cfgApp->UserQuizCSSLocationforHtml:$this->cfgApp->UserQuizCSSLocation;
        $path = $this->cfg->rootPath . '/' . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . "/";
        $path .= ( strtolower($param['renditionType']) == 'html') ? $this->cfgApp->UserQuizHtmlCSS : $this->cfgApp->UserQuizFlashCSS;
        $this->myDebug('rashmi----');
        $this->myDebug($path);
        if ($param['entityTypeID'] == 1) {
            $path .= strtolower($this->getEntityName(1)) . '/';
        } elseif ($param['entityTypeID'] == 2) {
            $path .= strtolower($this->getEntityName(2)) . '/';
        }
        $path .= $param['entityID'] . '/cssversion';
        $cssfiles = $this->getCssFiles($path);
        $this->versionFile = explode('.css', $param['file']);
        $this->versionFile = $this->versionFile[0];




        array_walk_recursive($cssfiles, array($this, 'getVersionFiles'));
		//echo "<pre>";print_r($this->cssList);echo "</pre>";//die('11');


	header('Content-type: application/json; charset=UTF-8');
        $jsonp    = '[';
	if (!empty($this->cssList)) {
			$i = 0;
			$cnt = sizeof($this->cssList);
	    foreach ($this->cssList as $key => $value) {
		$i++;
		$srno = $key + 1;
		$date = date('F j, Y, g:i a', strtotime($value['AddDate']));
		$version = explode('_', $value);
				$version = explode('.', $version[1]);
		$version = $version[0];
				include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/authoring/CssVersion.php';
				//echo $moddate;die();

		$jsonp .=
				"{
					\"item\":{
					\"srno\":\"{$srno}\",
					\"moddate\":\"{$moddate}\",
					\"name\":\"{$value}\",
					\"ver\":\"{$version}\"
					}
				}";
		if ($i < $cnt) {
		    $jsonp .= ',';
			}
	    }
			$jsonp   .= ']';
			$jsonpresponse = "{\"results\":{$jsonp}, \"count\":{$cnt}}";
			echo $jsonpresponse;
	} else {
			$cnt = 0;
	    $jsonp .=
				"{
					\"item\":{
					\"srno\":\"{}\",
					\"moddate\":\"{}\",
					\"name\":\"{}\",
					\"ver\":\"{}\"
					}
				}";
			$jsonp   .= ']';
			$jsonpresponse = "{\"results\":{$jsonp}, \"count\":{$cnt}}";
			echo $jsonpresponse;
		}


		//echo "<pre>";print_r($jsonpresponse);echo "</pre>";
		die;

		/*
        if (!empty($this->cssList)) {
            foreach ($this->cssList as $key => $css) {
                $version = explode('_', $css);
                $version = explode('.', $version[1]);
                ob_start();
                include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/authoring/CssVersion.php';
                $xml.= ob_get_contents();
                ob_end_clean();
            }
        }
        $xmlresponse = "<cssfiles>$xml</cssfiles>";
        //echo $xmlresponse;
		echo "<pre>";print_r($xmlresponse);echo "</pre>";
        die;
		*/
    }

    /**
     * a function to get the content of css version file for view
     *
     *
     * @access   public
     * @param    array   $params
     * @return   string
     *
     */
    function displayVersionCss(array $params) {
        return $this->getCssView($params);
    }

    /**
     * a function to handle roll back of css file to the specified version
     *
     *
     * @access   public
     * @param    array   $params
     * @return   void
     *
     */
    function rollBack(array $params) {
        $source = $this->getCssFileName($params);
        $params['version'] = '';
        $basefile = basename($params['file']);
        $basefile = preg_replace('/_(\d*)/', '', $basefile);
        $params['file'] = $basefile;
        $params['csscontent'] = file_get_contents($source);
        $this->cssEditSubmit($params);
    }

    /*     * *    Code for Online Css Ends  ** */

    /**
     * a function to get filtered metadata with question count for random preview/publish
     *
     *
     * @access   public
     * @param    array   $input
     * @return   string
     *
     */
    function getFilteredQuestions(array $input) {
        $metadata = new Metadata();
        if ($input['queID'] != '') {
            $condition = " and mrq.ID in({$input['queID']}) ";
        } else {
            $condition = '';
        }

        if ($input['type'] == 1) {
            /*
              $query  = "select mlo.LearningobjectName as name,count(mlo.LearningobjectName) as qcount
              from MapLearningObject mlo, MapRepositoryLearningObject mrlo, MapRepositoryQuestions mrq
              where mlo.LearningobjectName like '{$input['keyword']}%' and mlo.ID = mrlo.LearningObjectID and mrlo.RepositoryID = mrq.ID
              and mlo.EntityID = {$input['asmtID']} and mlo.isEnabled = '1' and mrlo.isEnabled = '1' and mrq.isEnabled = '1' $condition
              group by mlo.ID having qcount > 0  ";
              $data   = $this->db->getRows($query);
             */
	    $getFilteredQuestions = $this->db->executeStoreProcedure('GetFilteredQuestions', array('1', $input['asmtID'], $input['keyword'], ' mlo.ID having qcount > 0 ', $condition));
            $data = $getFilteredQuestions['RS'];
        } elseif ($input['type'] == 2) {
            /*
              $query = "select q.DifficultyLevel as name ,count(q.DifficultyLevel) as qcount
              from Questions q, MapClientQuestionTemplates mqt , MapRepositoryQuestions mrq,  QuestionTemplates qt
              where mrq.QuestionID = q.ID and q.QuestionTemplateID = mqt.ID AND mqt.isEnabled = '1' AND mqt.isActive = 'Y'
              and mqt.QuestionTemplateID = qt.ID and qt.HTMLTemplate is not null and qt.isStatic = 'N'
              and mrq.EntityID = {$input['asmtID']} and q.isEnabled = '1'  and mrq.isEnabled = '1' $condition
              group by  q.DifficultyLevel ";
              $data   = $this->db->getRows($query);
             */
	    $getFilteredQuestions = $this->db->executeStoreProcedure('GetFilteredQuestions', array('2', $input['asmtID'], '', ' q.DifficultyLevel ', $condition));
            $data = $getFilteredQuestions['RS'];
        } elseif ($input['type'] == 3) {
            /* $query = "  select count(distinct mmdkv.EntityID) as qcount, mdk.MetaDataName as name from MetaDataKeys mdk, MapMetaDataKeyValues mmdkv where mdk.isEnabled = '1' and mmdkv.isEnabled = '1'
              and mdk.ID = mmdkv.KeyID and mmdkv.EntityTypeID = 3 and mdk.MetaDataName like '%".$input['keyword']."%' AND mmdkv.EntityID IN ({$input['queID']}) group by mdk.ID ";
              $data   = $this->db->getRows($query); */
            $input['entityid'] = $input['queID'];
            $input['entitytypeid'] = 3;
            $input['search'] = $input['keyword'];
            $input['searchtype'] = "key";
            $metadataList = $metadata->getSearchResult($input);
            $data = $metadataList['RS'];
        } elseif ($input['type'] == 4) {
            /* $query = "  select count(mdv.ID) as qcount, mdv.MetaDataValue as name from MetaDataValues mdv, MapMetaDataKeyValues mmdkv where mdv.isEnabled = '1' and mmdkv.isEnabled = '1'
              and mdv.ID = mmdkv.ValueID and mmdkv.EntityTypeID = 3 and mdv.MetaDataValue like '%".$input['keyword']."%' AND mmdkv.EntityID IN ({$input['queID']}) group by mdv.ID ";
              $data   = $this->db->getRows($query); */
            $input['entityid'] = $input['queID'];
            $input['entitytypeid'] = 3;
            $input['search'] = $input['keyword'];
            $input['searchtype'] = "value";
            $metadataList = $metadata->getSearchResult($input);
            $data = $metadataList['RS'];
        }

        $response = '<div style="overflow-y: auto; height: 100px; overflow-x: hidden;"><table width="506" height="100%"><tr><td  class="popUpGray" valign="top"><div id="filterresults"><table width="500" cellspacing="5">';

        if (!empty($data)) {
            $cnt = count($data);
            for ($i = 0, $k = 0; $i < $cnt; $i++) {
                //<td width='50%'><span><input type='checkbox' name='learnObject' id='learnobject_$i'</span><span>{$data[$i+1]['name']}({$data[$i+1]['qcount']})</span></td>
                if ($input['type'] == 2) {
                    $searchRes = $data[$i]['name'];
                    $qcount = $data[$i]['qcount'];
                } else if ($input['type'] == 3) {
                    $searchRes = $data[$i]['name'];
                    $qcount = $data[$i]['qcount'];
                } else if ($input['type'] == 4) {
                    $searchRes = $data[$i]['name'];
                    $qcount = $data[$i]['qcount'];
                }

                //$searchRes = preg_quote($searchRes);
                //$searchRes = preg_replace("/{$input['keyword']}/i","<span class='searchhighlight'>{$input['keyword']}</span>", $searchRes);
                $response .= "<tr><td width='70%'><span style='padding-right:5px;'><input type='checkbox' name='randomtypes[]' id='randomtypes_$k'></span>
                                    <span id='randomnames_$k' style='vertical-align:top;'>{$searchRes}({$qcount})</span> </td>
                                   <td width='30%'><span><input type='text' name='userval_$k' id='userval_$k' value='' size='4' onblur='setTotalQuestions(\"userval_$k\",\"{$qcount}\")'></span></td><tr>";
                $k++;
            }
            //$response .= "<tr><td width='70%' align='right'><span>Total Questions</span></td><td width='30%'><span><input type='text' name='totalquestions' id='totalquestions' value='0' size='4' disabled></span></td></tr>";
        } else {
            $response .= "<tr><td align='center'>No Records Found.</td></tr>";
        }
        $response .= '</table></div></td></tr></table></div>';

        if (!empty($data)) {
            $response .= '<div><table width="500"><tr><td><table width="500" cellspacing="0">';
            $response .= "<tr><td align='right'><input type='hidden' name='resultcnt' id='resultcnt' value='$k'><input class='clsSteButton' type='button' value='Add All' name='addalluser' tabindex='2' onclick='addRandomResults(\"all\")'/>&nbsp;&nbsp;<input class='clsSteButton' type='button' value='Add' name='adduser' tabindex='3' onclick='addRandomResults()'/></td></tr>";
            $response .= '</table></td></tr></table></div>';
        }

        return $response;
    }

    /**
     * a function to get the question IDs from the filtered repository IDs for random preview/publish
     *
     *
     * @access   public
     * @param    string  $name
     * @param    integer $assessmentID
     * @param    string  $type
     * @param    string  $queID
     * @param    boolean $isDefault
     * @return   array
     *
     */
    function getQuestionId($name, $assessmentID, $type, $queID, $isDefault = false) {
        $query = '';

        if (!empty($queID)) {
            if (is_array($queID)) {
                $queID = implode(',', $queID);
            }
            $condition = " and mrq.ID in ($queID)";
        } else {
            $condition = '';
        }

        switch ($type) {
            case 'learnobj':
                if ($isDefault) {
                    $query = " select mrq.QuestionID as ID from MapRepositoryQuestions mrq
                                left join MapRepositoryLearningObject mrlo on mrlo.RepositoryID = mrq.ID  and mrlo.isEnabled = '1'
                                left join QuestionTemplates qt on mrq.QuestionTemplateID = qt.ID
                                where mrq.EntityID = $assessmentID and mrq.EntityTypeID = 2 and mrq.isEnabled = '1' and qt.isStatic = 'N' and qt.RenditionMode != 'Flash' and mrlo.ID is null and mrq.QuestionID > 0 $condition ";
                } else {
                    $query = " select mrq.QuestionID as ID from MapLearningObject mlo, MapRepositoryLearningObject mrlo, MapRepositoryQuestions mrq, QuestionTemplates qt
                                where mlo.LearningobjectName like '%$name%' and mlo.ID = mrlo.LearningObjectID and mrlo.RepositoryID = mrq.ID and qt.ID = mrq.QuestionTemplateID
                                and mlo.EntityID = $assessmentID and mrq.EntityTypeID = 2 and qt.isStatic = 'N' and mlo.isEnabled = '1' and mrlo.isEnabled = '1' and qt.RenditionMode != 'Flash' and mrq.isEnabled = '1' and mrq.QuestionID > 0 $condition ";
                }
                break;

            case 'difficulty':
                if ($isDefault) {
                    if ($name != '') {
                        $query = " select q.ID from Questions q, MapClientQuestionTemplates mqt, MapRepositoryQuestions mrq, QuestionTemplates qt
                                    where mrq.QuestionID = q.ID and mrq.EntityTypeID = 2 and mrq.EntityID = $assessmentID and mqt.ID = mrq.QuestionTemplateID AND mqt.isEnabled = '1' AND mqt.isActive = 'Y' and qt.ID = mqt.QuestionTemplateID and q.isEnabled = '1' and mrq.isEnabled = '1' and qt.isStatic = 'N' and qt.RenditionMode != 'Flash' and q.DifficultyLevel not in($name) and mrq.QuestionID > 0 $condition ";
                    }
                } else {
                    $query = " select q.ID from Questions q, MapClientQuestionTemplates mqt, MapRepositoryQuestions mrq, QuestionTemplates qt
                                where mrq.QuestionID = q.ID and mrq.EntityTypeID = 2 and mrq.EntityID = $assessmentID and mqt.ID = mrq.QuestionTemplateID AND mqt.isEnabled = '1' AND mqt.isActive = 'Y' and qt.ID = mqt.QuestionTemplateID and q.isEnabled = '1' and mrq.isEnabled = '1' and qt.isStatic = 'N' and qt.RenditionMode != 'Flash'  and q.DifficultyLevel in('$name') and mrq.QuestionID > 0 $condition ";
                }
                break;

            case 'metadatakey':
                $query = " select distinct mrq.QuestionID as ID from MapRepositoryQuestions mrq
                            inner join MapMetaDataKeyValues mmdkv on mrq.ID = mmdkv.EntityID and mmdkv.EntityTypeID = 3
                            inner join MetaDataKeys mdk on mmdkv.KeyID = mdk.ID and mdk.MetaDataName IN ('$name')
                            left join QuestionTemplates qt on mrq.QuestionTemplateID = qt.ID
                            where mrq.EntityID = $assessmentID and mrq.EntityTypeID = 2 and mrq.isEnabled = '1' and mmdkv.isEnabled = '1' and mdk.isEnabled = '1'
                            and qt.isStatic = 'N' and qt.RenditionMode != 'Flash' and mrq.QuestionID > 0 $condition ";
                break;

            case 'metadataval':
                $query = " select distinct mrq.QuestionID as ID from MapRepositoryQuestions mrq
                            inner join MapMetaDataKeyValues mmdkv on mrq.ID = mmdkv.EntityID and mmdkv.EntityTypeID = 3
                            inner join MetaDataValues mdv on mmdkv.ValueID = mdv.ID and mdv.MetaDataValue IN ('$name')
                            left join QuestionTemplates qt on mrq.QuestionTemplateID = qt.ID
                            where mrq.EntityID = $assessmentID and mrq.EntityTypeID = 2 and mrq.isEnabled = '1' and mmdkv.isEnabled = '1' and mdv.isEnabled = '1'
                            and qt.isStatic = 'N' and qt.RenditionMode != 'Flash' and mrq.QuestionID > 0 $condition ";
                break;
        }

        if ($query != '') {
            $data = $this->db->getRows($query);
            return $this->getValueArray($data, 'ID', 'multiple', 'array');
        }
    }

    /**
     * a function to get random question IDs of the given assessment
     *
     *
     * @access   public
     * @param    integer $assessmentID
     * @param    integer $diff
     * @param    string  $queID
     * @return   array
     *
     */
    function getRandomQueId($assessmentID, $diff, $queID) {
        $data = array();
        if (!empty($queID)) {
            $q = implode(',', $queID);
            $condition = " AND QuestionID NOT IN($q) ";
        } else {
            $condition = '';
        }
        /*
          $query  = "  SELECT DISTINCT QuestionID
          FROM MapRepositoryQuestions
          WHERE EntityID = $assessmentID AND EntityTypeID = 2 AND isEnabled = '1' $condition ORDER BY RAND() LIMIT 0,$diff   ";
          $data   = $this->db->getRows($query);
         */
	$getPromoCodeList = $this->db->executeStoreProcedure('GetRandomQuestID', array($assessmentID, '0', $diff, $condition, '-1'));
        if (!empty($getPromoCodeList['RS'])) {
            $data = $getPromoCodeList['RS'];
        }

        return explode(',', $this->getValueArray($data, 'QuestionID', 'multiple'));
    }

    /**
     *   a function to handle mobile rendition publish
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   void
     *
     */
    function mobileRendition($input, $quizid, $action, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        global $CONFIG, $APPCONFIG, $DBCONFIG;

        if ($this->checkRight('AsmtPublish') == false) {
            return 'No Assessment access';
        }

        $qt = new Question();
        $queID = trim($questids, '|');
        $queID = explode('||', $queID);
        $queID = $this->removeBlankElements($queID);
        $entityTypeId = 2;
        $quadplusids = '';
        $randomQuestCount = 0;

        $Assessment = new Assessment();
        $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array($quizid, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount');
        $this->myDebug('----mobileRendition---');
        $this->myDebug($input);
        $this->myDebug($quizid);

        $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
        $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
        $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
        $Entity_name = $this->getValueArray($AssessmentSettings, "AsmtName");

        $eid = $this->getEntityId('Question');

        if ($DBCONFIG->dbType == 'Oracle') {
            $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            $filter .= " AND ( qtp.\"TemplateFile\" = ''MCSSText'' OR qtp.\"TemplateFile\" = ''MCMSText'' ) ";
            $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
	    $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $quizid, $entityTypeId, "0", " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" "), 'nocount'
            );
        } else {
            $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            $filter .= " AND ( qtp.TemplateFile = 'MCSSText' OR qtp.TemplateFile = 'MCMSText' ) ";
            $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
	    $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $quizid, $entityTypeId, "0", " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport "), 'nocount'
            );
        }

        $this->myDebug('this is complete question list - ends');
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $qst = new Question();
        $objJSONtmp = new Services_JSON();

        if (!empty($questions)) {
            $data = array(
                'UserID' => $this->session->getValue('userID'),
                'AssessmentID' => $quizid,
                'PublishMode' => $mode,
                'PublishType' => $publishtype,
                'PublishedTitle' => $publishname,
                'RenditionType' => 'Mobile',
                'TotalQuestions' => $totalquest,
                'RandomQuestionCount' => $randomQuestCount,
                'AddDate' => $this->currentDate(),
                'ModBY' => $this->session->getValue('userID'),
                'ModDate' => $this->currentDate(),
                'isActive' => 'Y',
                'isEnabled' => '1'
            );
            $publinkid = $this->db->insert('PublishAssessments', $data);
            $guid = $publinkid;

            // create folder for mobile publish
            $mobilePublishRootPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/mobile/' . $guid));
            $mobilePublishWebPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/mobile/' . $guid, 'protocol' => 'http'));

            $qtifol = "{$mobilePublishRootPath}/{$guid}.xml";
            $menifest_resources = "{$guid}.xml";

            // check whether logged in user had Multiple QuAD Plus Access
            if ($this->registry->site->hasMultipleQuadPlus()) {
                if (!$quadplusids) {
                    // get All the Quad Plus
                    $arrQuadPlusList = $this->getQuadPlusList();
                    if (!empty($arrQuadPlusList)) {
                        foreach ($arrQuadPlusList as $quadPlusList) {
                            $quadplusids .= $quadPlusList['QPID'] . ',';
                        }
                        $quadplusids = trim($quadplusids, ',');
                    }
                }

                if ($quadplusids) {
                    $arrQuadPlusID = @explode(',', $quadplusids);
                    if (!empty($arrQuadPlusID)) {
                        foreach ($arrQuadPlusID as $quadPlusID) {
                            $data = array(
                                'PublishAsmtID' => $guid,
                                'QuadPlusId' => $quadPlusID,
                                'AddBY' => $this->session->getValue('userID'),
                                'AddDate' => $this->currentDate(),
                                'ModBY' => $this->session->getValue('userID'),
                                'ModDate' => $this->currentDate(),
                                'isActive' => 'Y',
                                'isEnabled' => '1'
                            );
                            $mapPublishAsmtQuadPlus = $this->db->insert('MapPublishAsmtQuadPlus', $data);
                        }
                    }
                }
            }
            /* $xmlStr='<?xml version="1.0" encoding="ISO-8859-1"?>';  */
            $xmlStr = "<questestinterop>";
            $xmlStr .=" <assessment title='" . htmlspecialchars($publishname) . "' ident='Asses_{$quizid}' >";
            $rootSecInc = 1;
            $sec = "";
            $total_quest = 0;
            $totalquestions = count($questions);
            $qst = new Question();
            $objJSONtmp = new Services_JSON();

            foreach ($questions as $questlist) {
                $question_xml = $questlist;
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["JSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                $this->myDebug("This is New Json---mobileRendition");
                $this->myDebug($sJson);
                $objJsonTemp = $objJSONtmp->decode($sJson);
                if (!isset($objJsonTemp))
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                $objJson = $objJsonTemp;
                $this->myDebug($objJson);

                $optionsList = $objJson->{'choices'};
                $Quest_title = $this->formatJson($objJson->{'question_title'});
                $Quest_Inst_text = $this->formatJson($objJson->{'instruction_text'});
                $Quest_text = $objJson->{'question_text'} . "<br/>" . $objJson->{'instruction_text'};
                $Quest_text = $this->formatJson($Quest_text);
                $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $correct_feedback = $this->formatJson($objJson->{'correct_feedback'});
                $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);
                $i++;


                if ($questlist['ParentID'] != 0 && $secid != $questlist['ParentID']) {
                    if ($sec == "innst" || $tempsec == "innst") {
                        $xmlStr .="</section>";
                        $sec = "";
                        $tempsec = "";
                    }
                    if ($sec != "innst") {
                        $secid = $questlist['ParentID'];
                        $xmlStr .='<section title="' . $questlist["SectionName"] . '" ident="SEC_INDENT_' . $questlist['ParentID'] . '">';
                        $sec = "innst";
                    }
                } else {
                    if ($sec == "innst" && $questlist['ParentID'] == 0) {
                        $xmlStr .="</section>";
                        $sec = "";
                    }
                    if ($tempsec != "innst") {
                        $xmlStr .="<section title=\"test" . $i . "\" ident=\"SEC_INDENT_" . $i . "\">";
                        $tempsec = "innst";
                    }
                }

                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti1_2/" . $TemplateFile . ".php";
                ob_start();
                if (file_exists($templateFilePath) && ($isExport == 'Y')) {
                    include($templateFilePath);
                    $total_quest++;
                }
                $xmlStr .=ob_get_contents();
                ob_end_clean();


                //ID, PublishAssessmentID, QuestionID, QuestionTitle, XMLData, JSONData, QuestionTemplateID, UserID, AddDate, ModBY, ModDate, isEnabled
                $arrPublishedQuestion = array(
                    'PublishAssessmentID' => $guid,
                    'QuestionID' => $questlist['QuestionID'],
                    'XMLData' => $qt->addMediaPlaceHolder($questlist['XMLData']),
                    'JSONData' => $qt->addMediaPlaceHolder($questlist['JSONData']),
                    'QuestionTemplateID' => $questlist['QuestionTemplateID'],
                    'Title' => $questlist['Title'],
                    'UserID' => $this->session->getValue('userID'),
                    'AddDate' => $this->currentDate(),
                    'ModBy' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'isEnabled' => '1'
                );
                $status = $this->db->insert('PublishQuestions', $arrPublishedQuestion);
            }

            if ($sec == "innst" || $tempsec == "innst") {
                $xmlStr .="</section>";
            }
        } else {
            return '-1'; //Please select MCSS and MCMS Questions Without Media.
        }
        $xmlStr .= "</assessment>";
        $xmlStr .= "</questestinterop>";

        $myFile = $qtifol;
        $fh2 = fopen($myFile, 'w');
        $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
        fwrite($fh2, $xmlStr);
        fclose($fh2);

        /* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti1_2/AssessmentImsManifest.php");
        $xmlmaniStr .= ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($mobilePublishRootPath . "/imsmanifest.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);
        /* end of creating manifest */
        $this->db->execute("UPDATE PublishAssessments SET TotalQuestions=$i WHERE ID='$guid' ");

        $mobileRenditionRootPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/mobile/'));
        $zipfile = "$mobileRenditionRootPath{$guid}.zip";
        $zipPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/mobile/' . $guid . '/'));

        $this->makeZip($zipPath, $zipfile);
        return true;
    }

// Fn mobileRendition

    /**
     *   a function to handle marklogic publish
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   void
     *
     */
    function marklogicQTIRendition($guid, $input, $quizid, $actionName, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        global $CONFIG, $APPCONFIG, $DBCONFIG;

        $qt = new Question();
        $queID = trim($questids, '|');
        $queID = explode('||', $queID);
        $queID = $this->removeBlankElements($queID);
        $entityTypeId = 2;
        $quadplusids = '';
        $randomQuestCount = 0;

        $Assessment = new Assessment();
        $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array($quizid, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount');
        $this->myDebug('----marklogicRendition');

        $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
        $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
        $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
        $Entity_name = $this->getValueArray($AssessmentSettings, "AsmtName");

        $eid = $this->getEntityId('Question');
        if ($DBCONFIG->dbType == 'Oracle') {
            $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            $filter .= " AND ( qtp.\"TemplateFile\" = ''MCSSText'' OR qtp.\"TemplateFile\" = ''MCMSText'' ) ";
            $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
	    $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $quizid, $entityTypeId, "0", " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" "), 'nocount');
        } else {
            $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            $filter .= " AND ( qtp.TemplateFile = 'MCSSText' OR qtp.TemplateFile = 'MCMSText' ) ";
            $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
	    $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $quizid, $entityTypeId, "0", " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport "), 'nocount');
        }

        $this->myDebug('this is complete question list - ends');
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $qst = new Question();
        $objJSONtmp = new Services_JSON();


        if (!empty($questions)) {
            if ($actionName == 'previewq') {
                $markLogicPublishRootPath = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'renditions/marklogic/assessment_preview/' . $guid));
                $markLogicPublishWebPath = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'renditions/marklogic//assessment_preview/' . $guid, 'protocol' => 'http'));
                $publishname = 'Preview of MarkLogic';
            } else {
                $markLogicPublishRootPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/marklogic/assessment_publish/' . $guid));
                $markLogicPublishWebPath = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'renditions/marklogic/assessment_publish/' . $guid, 'protocol' => 'http'));
            }

            $qtifol = "{$markLogicPublishRootPath}/{$guid}.xml";
            $menifest_resources = "{$guid}.xml";

            /* $xmlStr='<?xml version="1.0" encoding="ISO-8859-1"?>';  */

            $xmlStr = "<questestinterop>";
            $xmlStr .=" <assessment title='" . htmlspecialchars($publishname) . "' ident='Asses_{$quizid}' >";
            $rootSecInc = 1;
            $sec = "";
            $total_quest = 0;
            $totalquestions = count($questions);
            $qst = new Question();
            $objJSONtmp = new Services_JSON();

            foreach ($questions as $questlist) {
                $question_xml = $questlist;
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["JSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                $this->myDebug("This is New Json---marklogicRendition");
                $this->myDebug($sJson);
                $objJsonTemp = $objJSONtmp->decode($sJson);
                if (!isset($objJsonTemp))
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                $objJson = $objJsonTemp;
                $metadata = new Metadata();

                $optionsList = $objJson->{'choices'};
                $Quest_title = $this->formatJson($objJson->{'question_title'});
                $Quest_Inst_text = $this->formatJson($objJson->{'instruction_text'});
                $Quest_text = $objJson->{'question_text'} . "<br/>" . $objJson->{'instruction_text'};
                $Quest_text = $this->formatJson($Quest_text);
                $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $correct_feedback = $this->formatJson($objJson->{'correct_feedback'});
                $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);
                $i++;
                $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");

                if ($questlist['ParentID'] != 0 && $secid != $questlist['ParentID']) {
                    if ($sec == "innst" || $tempsec == "innst") {
                        $xmlStr .="</section>";
                        $sec = "";
                        $tempsec = "";
                    }
                    if ($sec != "innst") {
                        $secid = $questlist['ParentID'];
                        $xmlStr .='<section title="' . $questlist["SectionName"] . '" ident="SEC_INDENT_' . $questlist['ParentID'] . '">';
                        $sec = "innst";
                    }
                } else {
                    if ($sec == "innst" && $questlist['ParentID'] == 0) {
                        $xmlStr .="</section>";
                        $sec = "";
                    }
                    if ($tempsec != "innst") {
                        $xmlStr .="<section title=\"test" . $i . "\" ident=\"SEC_INDENT_" . $i . "\">";
                        $tempsec = "innst";
                    }
                }

                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti1_2/" . $TemplateFile . ".php";
                ob_start();
                if (file_exists($templateFilePath) && ($isExport == 'Y')) {
                    include($templateFilePath);
                    $total_quest++;
                }
                $xmlStr .=ob_get_contents();
                ob_end_clean();

                /*
                  if ($actionName == 'publishq')
                  {
                  //ID, PublishAssessmentID, QuestionID, QuestionTitle, XMLData, JSONData, QuestionTemplateID, UserID, AddDate, ModBY, ModDate, isEnabled
                  $arrPublishedQuestion = array(  'PublishAssessmentID'   => $guid,   'QuestionID'            => $questlist['QuestionID'],
                  'XMLData'               => $qt->addMediaPlaceHolder($questlist['XMLData']),
                  'JSONData'              => $qt->addMediaPlaceHolder($questlist['JSONData']),
                  'QuestionTemplateID'    => $questlist['QuestionTemplateID'],    'Title'                 => $questlist['Title'],
                  'UserID'                => $this->session->getValue('userID'),  'AddDate'               => $this->currentDate(),
                  'ModBy'                 => $this->session->getValue('userID'),  'ModDate'               => $this->currentDate(),
                  'isEnabled'             => '1'      );
                  $status = $this->db->insert('PublishQuestions', $arrPublishedQuestion);
                  }
                 */
            }


            if ($sec == "innst" || $tempsec == "innst") {
                $xmlStr .="</section>";
            }

            if ($actionName == 'publishq') {
                $this->db->execute("UPDATE PublishAssessments SET TotalQuestions=$i WHERE ID='$guid' ");
            }
        } else {
            return '-1'; //Please select MCSS and MCMS Questions Without Media.
        }
        $xmlStr .= "</assessment>";
        $xmlStr .= "</questestinterop>";

        $myFile = $qtifol;
        $fh2 = fopen($myFile, 'w');
        $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
        fwrite($fh2, $xmlStr);
        fclose($fh2);

        /* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti1_2/AssessmentImsManifest.php");
        $xmlmaniStr .= ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($markLogicPublishRootPath . "/imsmanifest.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);
        /* end of creating manifest */
        Site::myDebug('---$xmlStr');
        Site::myDebug($xmlStr);
        return $xmlStr;
    }

// Fn marlogicRendition

    /**
     * a function to handle marklogic rendition
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   void
     *
     */
    function markLogicRendition($input, $quizid, $action, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        global $CONFIG;
        $arrData = $this->htmlMarklogicRendition($input, $quizid, $action, $publishtype, $publishname, $mode, $totalquest, $randquest, $questids);
        $xmlStr = $this->marklogicQTIRendition($arrData['guid'], $input, $quizid, $action, $publishtype, $publishname, $mode, $totalquest, $randquest, $questids);
        // Set Curl Call
        $param = array('url' => $CONFIG->marklogicServer . 'assessment-publish',
            'fields' => array('publish_content' => $xmlStr, 'publish_ident' => $arrData['guid'])
        );

        $response = $this->curlCall($param);
        $this->myDebug("-----response");
        $this->myDebug($param);
        $this->myDebug($response);

        print $arrData['renditionurl'];
    }

    /**
     * a function to handle Marklogic html Rendition
     *
     *
     * @access   public
     * @global   object  $CONFIG
     * @global   object  $APPCONFIG
     * @global   object  $DBCONFIG
     * @param    array   $input
     * @param    integer $quizid
     * @param    string  $action
     * @param    string  $publishtype
     * @param    string  $publishname
     * @param    string  $mode
     * @param    integer $totalquest
     * @param    integer $randquest
     * @param    string  $questids
     * @return   void
     *
     */
    function htmlMarklogicRendition($input, $quizid, $action, $publishtype = '', $publishname = '', $mode = '', $totalquest = 0, $randquest = 0, $questids = '') {
        global $CONFIG, $APPCONFIG, $DBCONFIG;
        $qt = new Question();
        $randommetadatakey = ($input['randommetadatakey'] != "") ? json_decode($input['randommetadatakey']) : "";
        $randommetadataval = ($input['randommetadataval'] != "") ? json_decode($input['randommetadataval']) : "";
        $difficulty = ($input['randomdifficulty'] != "") ? json_decode($input['randomdifficulty']) : "";
        ;
        $queID = trim($questids, '|');
        $queID = explode('||', $queID);
        $queID = $this->removeBlankElements($queID);

        if (isset($input['quadplusids'])) {
            $quadplusids = $input['quadplusids'];
            $quadplusids = str_replace('||', ',', $quadplusids);
            $quadplusids = trim($quadplusids, '|');
        } else {
            $quadplusids = '';
        }

        $questCount = array();
        $questionID = array();
        $randomCriteria = array();
        $questionIDs = array();

        if (!empty($randommetadatakey)) {
            foreach ($randommetadatakey as $key => $val) {
                $name = trim($val->name);
                $questCount['mk_' . $name] = trim($val->count);
                $questionID['mk_' . $name] = $this->getQuestionID($name, $quizid, 'metadatakey', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['mk_' . $name]));
                $randomCriteria['mkey'][$name] = array('randcount' => $questCount['mk_' . $name], 'queID' => array_keys($questionID['mk_' . $name]));
            }
        }

        if (!empty($randommetadataval)) {
            foreach ($randommetadataval as $key => $val) {
                $name = trim($val->name);
                $questCount['mv_' . $name] = trim($val->count);
                $questionID['mv_' . $name] = $this->getQuestionID($name, $quizid, 'metadataval', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['mv_' . $name]));
                $randomCriteria['mval'][$name] = array('randcount' => $questCount['mv_' . $name], 'queID' => array_keys($questionID['mv_' . $name]));
            }
        }

        $level = '';
        if (!empty($difficulty)) {
            foreach ($difficulty as $key => $val) {
                $level .= "'" . trim($val->name) . "',";
                $name = trim($val->name);
                $questCount['dl_' . $name] = trim($val->count);
                $questionID['dl_' . $name] = $this->getQuestionID($name, $quizid, 'difficulty', $input['questids']);
                $questionIDs = array_merge($questionIDs, array_keys($questionID['dl_' . $name]));
                $randomCriteria['diff'][$name] = array('randcount' => $questCount['dl_' . $name], 'queID' => array_keys($questionID['dl_' . $name]));
            }
            $level = rtrim($level, ',');
        }

        $randomCriteria['added'] = array_unique($questionIDs);
        $this->myDebug('level json');
        $this->myDebug($randomCriteria);
        $random = ($mode == 2) ? 'yes' : 'no';
        $randomQuestCount = $randquest;

        $prevSource = $input['prevsource'];

        $this->myDebug("Prev Source" + $prevSource);
        if ($prevSource == "wordTemplate") {
            $this->myDebug("Next Resource ");
            $guid = uniqid();
            $publishtype = $action;

            //$this->copyCss($quizid, 2);
            $eid = $this->getEntityId('Question');
            $filter .= " (qtp.Renditionmode!='Flash') ";
            $filter .= ( $questids != '') ? " and wtq.ID in ({$questids}) " : '';
            $questions = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $filter, $tokenID, $transactionID, ' wtq.XMLData , wtq.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode '), 'nocount');
            $i = 1;
            //files Copy start
            $assessmentpath = $this->copyFilesHtmlRendition($quizid, $guid, $publishtype);
            // shell xml creation start
            $topbanner = 85;

            $type = $input['Type'];
            $qformative = ($type == '2' ) ? 'yes' : 'no';
            $qsummative = ($type == '1' ) ? 'yes' : 'no';
            $qmoveback = ($input['MoveBack'] == '1' ) ? 'yes' : 'no';
            $qshuffle = ($input['ShuffleOptions'] == '1' ) ? 'yes' : 'no';
            $qskip = ($input['SkipQ'] == '1' ) ? 'yes' : 'no';
            $qscore = ($input['Score'] == '1' ) ? 'yes' : 'no';
            $qtotalscore = $input['TotalScore'];
            $qpassingscore = $input['PassingScore'];
            $qpartial = ($input['PartialScore'] == '1' ) ? 'yes' : 'no';
            $qattempt = $input['Tries'];
            //$qattempt           = ($publishtype == 'CDROM-HTML' ) ? '3': $input['Tries'];
            $qqlevel = ($input['QLevel'] == '1' ) ? 'yes' : 'no';
            $qolevel = ($input['OLevel'] == '1' ) ? 'yes' : 'no';
            $qtryagain = $input['TryAgain'];
            $qper0 = 0;
            $qper1 = $input['Scorebox1'];
            $qper2 = $input['Scorebox2'];
            $qper3 = $input['Scorebox3'];
            $qper4 = $input['Scorebox4'];
            $qper5 = 100;
            $qmessage1 = $input['Message1'];
            $qmessage2 = $input['Message2'];
            $qmessage3 = $input['Message3'];
            $action1 = $input['Action1'];
            $action2 = $input['Action2'];
            $action3 = $input['Action3'];
            $HelpMessage = $input['HelpMessage'];
            $TicksCross = ( $input['TicksCross'] == '1' ) ? 'yes' : 'no';
            $stFilter = " qtp.isStatic = 'Y' ";
            $stFilter .= ( $questids != '') ? "  and wtq.ID  in ({$questids})" : '';
            $questiontemps = $this->db->executeStoreProcedure('WTQuestionList', array('-1', '-1', '-1', '-1', $stFilter, $tokenID, $transactionID, '-1'), 'list');
            $staticpagecount = $questiontemps['TC'];
        } else {

	    $AsmtDetail = $this->db->executeStoreProcedure('AssessmentDetails', array($quizid, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID')), 'nocount');
            $AssessmentSettings = $AsmtDetail;
            $isDirector = 'no';

            if ($action == 'publishq') {
                $isDirector = ($publishtype == 'CDROM-HTML' ) ? 'yes' : 'no';

                if ($this->checkRight('AsmtPublish') == false) {
                    return 'No Assessment access';
                }

                $data = array(
                    'UserID' => $this->session->getValue('userID'),
                    'AssessmentID' => $quizid,
                    'PublishMode' => $mode,
                    'PublishType' => $publishtype,
                    'PublishedTitle' => $publishname,
                    'RenditionType' => 'MarkLogic',
                    'TotalQuestions' => $totalquest,
                    'RandomQuestionCount' => $randomQuestCount,
                    'AddDate' => $this->currentDate(),
                    'ModBY' => $this->session->getValue('userID'),
                    'ModDate' => $this->currentDate(),
                    'isActive' => 'Y',
                    'isEnabled' => '1'
                );
                $publinkid = $this->db->insert('PublishAssessments', $data);
                $guid = $publinkid;
            }//  if($action == 'publishq')

            if ($action == 'previewq') {
                $guid = uniqid();
                if ($this->checkRight('AsmtPreview') == false) {
                    return 'No Assessment access';
                }
                $publishtype = $action;
            }

            //Check Whether QuizCSS exist if not then copy default css folder and css.zip
            //$this->copyCss($quizid, 2);
            $eid = $this->getEntityId('Question');

            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
                $filter .= " AND (qtp.\"RenditionMode\" != ''Flash'') ";
                $filter .= " AND ( qtp.\"TemplateFile\" = ''MCSSText'' OR qtp.\"TemplateFile\" = ''MCMSText'' ) ";
                $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst."XMLData" , qst."JSONData" , qtp."HTMLTemplate" , qtp."isStatic" , tpc."CategoryCode" '), 'nocount');
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
                $filter .= " AND (qtp.Renditionmode!='Flash') ";
                $filter .= " AND ( qtp.TemplateFile = 'MCSSText' OR qtp.TemplateFile = 'MCMSText' ) ";
                $filter .= ( $questids != '') ? " and mrq.ID in ({$questids}) " : '';
                $questions = $this->db->executeStoreProcedure('QuestionList', array('-1', '-1', '-1', '-1', $filter, $quizid, '2', '0', ' qst.XMLData , qst.JSONData , qtp.HTMLTemplate , qtp.isStatic , tpc.CategoryCode '), 'nocount');
            }

            $i = 1;
            //files Copy start
            $assessmentpath = $this->copyFilesHtmlRendition($quizid, $guid, $publishtype, "MarkLogic");
            // shell xml creation start
            $topbanner = 85;
            $type = $this->getValueArray($AssessmentSettings, 'Type');
            $qformative = ($type == '2' ) ? 'yes' : 'no';
            $qsummative = ($type == '1' ) ? 'yes' : 'no';
            $qmoveback = ($this->getAssociateValue($AssessmentSettings, 'MoveBack') == '1' ) ? 'yes' : 'no';
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == '1' ) ? 'yes' : 'no';
            $qskip = ($this->getAssociateValue($AssessmentSettings, 'SkipQ') == '1' ) ? 'yes' : 'no';
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == '1' ) ? 'yes' : 'no';
            $qtotalscore = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $qpassingscore = $this->getAssociateValue($AssessmentSettings, 'PassingScore');
            $qpartial = ($this->getAssociateValue($AssessmentSettings, 'PartialScore') == '1' ) ? 'yes' : 'no';
            $qattempt = ($publishtype == 'CDROM-HTML' ) ? '3' : $this->getAssociateValue($AssessmentSettings, 'Tries');
            $qqlevel = ($this->getAssociateValue($AssessmentSettings, 'QLevel') == '1' ) ? 'yes' : 'no';
            $qolevel = ($this->getAssociateValue($AssessmentSettings, 'OLevel') == '1' ) ? 'yes' : 'no';
            $qtryagain = $this->getAssociateValue($AssessmentSettings, 'TryAgain');
            $qper0 = 0;
            $qper1 = $this->getAssociateValue($AssessmentSettings, 'Scorebox1');
            $qper2 = $this->getAssociateValue($AssessmentSettings, 'Scorebox2');
            $qper3 = $this->getAssociateValue($AssessmentSettings, 'Scorebox3');
            $qper4 = $this->getAssociateValue($AssessmentSettings, 'Scorebox4');
            $qper5 = 100;
            $qmessage1 = $this->getAssociateValue($AssessmentSettings, 'Message1');
            $qmessage2 = $this->getAssociateValue($AssessmentSettings, 'Message2');
            $qmessage3 = $this->getAssociateValue($AssessmentSettings, 'Message3');
            $action1 = $this->getAssociateValue($AssessmentSettings, 'Action1');
            $action2 = $this->getAssociateValue($AssessmentSettings, 'Action2');
            $action3 = $this->getAssociateValue($AssessmentSettings, 'Action3');
            $HelpMessage = $this->getAssociateValue($AssessmentSettings, 'HelpMessage');
            $TicksCross = ($this->getAssociateValue($AssessmentSettings, 'TicksCross') == '1' ) ? 'yes' : 'no';
            $staticpagecount = ($mode == 2) ? 0 : $this->getStaticPageCount($quizid, $questids);
        }
        $this->myDebug("End Resource ");

        if ($publishname == '') {
            $QuizTitle = $this->getValueArray($AssessmentSettings, 'Title');
            $QuizTitle = ($QuizTitle == '') ? $this->getValueArray($AssessmentSettings, 'Name') : $QuizTitle;
        } else {
            $QuizTitle = $publishname;
        }

        $this->myDebug('this is complete question list - starts');
        $randomCriteria['remained'] = array_merge(array(), array_diff(array_keys($this->getValueArray($questions, 'QuestionID', 'multiple', 'array')), $randomCriteria['added']));
        $this->myDebug($randomCriteria);
        $this->myDebug('this is complete question list - ends');
        $totalScore = 0;
        $questiondata = '';
        $instID = ($this->session->getValue('instID') != "") ? $this->session->getValue('instID') : $this->user_info->instId;
        if (!empty($questions)) {
            foreach ($questions as $questlist) {
                //Editing the Questions Learning Object Data.
                $this->mydebug("Original Data");
                $this->mydebug($questlist['XMLData']);
                $this->mydebug($questlist['JSONData']);
                $questlist['XMLData'] = $qt->removeMediaPlaceHolder($questlist['XMLData']);
                $questlist['JSONData'] = $qt->removeMediaPlaceHolder($questlist['JSONData']);
                $this->mydebug("Change Data");
                $this->mydebug($questlist['XMLData']);
                $this->mydebug($questlist['JSONData']);

                if ($questlist['isStatic'] != 'Y') {
                    $objJSONtmp = new Services_JSON();
                    $metaData = new Metadata();
                    $objJson2 = $objJSONtmp->decode($questlist['JSONData']);
                    $questlist['JSONData'] = $objJSONtmp->encode($objJson2);
                }
                $multi = '';
                if ($questlist['HTMLTemplate'] == '' || $questlist['HTMLTemplate'] == null) {
                    continue;
                }
                //Random Type Quiz
                if ($random == 'yes') {
                    if ($questlist['isStatic'] == 'Y')
                        continue;
                }
                $totalAssessmentScore += (int) $objJson2->{'metadata'}[0]->{'val'};
                $randomQuestionIds .= $questlist['QuestionID'] . ',';
                $layoutpath = $assessmentpath . '/' . $questlist['HTMLTemplate'];

                if (!is_dir($layoutpath)) {
                    mkdir($layoutpath, 0777);
                    $this->dirCopy($CONFIG->rootPath . '/' . $APPCONFIG->HtmlAssessment . $questlist['HTMLTemplate'], $layoutpath);
                    if (is_file($layoutpath . '/' . $questlist['HTMLTemplate'] . '.css')) {
                        unlink($layoutpath . '/' . $questlist['HTMLTemplate'] . '.css');
                    }
                    @copy($CONFIG->rootPath . '/' . $APPCONFIG->PersistDataPath . $instID . "/" . $APPCONFIG->UserQuizHtmlCSS . strtolower($this->getEntityName(2)) . '/' . $quizid . '/' . $questlist['HTMLTemplate'] . '/' . $questlist['HTMLTemplate'] . '.css', $layoutpath . '/' . $questlist['HTMLTemplate'] . '.css');
                }

                //Copy published question to tblpublishquest dbtable for answer verification
                if ($action == 'publishq') {
                    //ID, PublishAssessmentID, QuestionID, QuestionTitle, XMLData, JSONData, QuestionTemplateID, UserID, AddDate, ModBY, ModDate, isEnabled
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $questlist['XMLData'] = preg_replace("/>([^<\w]*)</", '><', $questlist['XMLData']);
                        $questlist['XMLData'] = str_replace("\'", "''", addslashes(stripslashes($questlist['XMLData'])));
                    }
                    $arrPublishedQuestion = array(
                        'PublishAssessmentID' => $guid,
                        'QuestionID' => $questlist['QuestionID'],
                        'XMLData' => $qt->addMediaPlaceHolder($questlist['XMLData']),
                        'JSONData' => $qt->addMediaPlaceHolder($questlist['JSONData']),
                        'QuestionTemplateID' => $questlist['QuestionTemplateID'],
                        'Title' => $questlist['Title'],
                        'UserID' => $this->session->getValue('userID'),
                        'AddDate' => $this->currentDate(),
                        'ModBy' => $this->session->getValue('userID'),
                        'ModDate' => $this->currentDate(),
                        'isEnabled' => '1'
                    );
                    $status = $this->db->insert('PublishQuestions', $arrPublishedQuestion);
                }
                //Copy question code end
                //Copy Images to Preview Folder
                $questionimagepath = $assessmentpath . '/userimages/';
                if ($publishtype === 'CDROM-HTML') {
                    $multi = $this->previewImageCopy($questlist['QuestionID'], $questlist['JSONData'], $questlist['QuestionTemplateID'], $questionimagepath, true);
                }

                //Replace Image path to local path
                $questionimagepath = '../userimages/';
                if ($publishtype === 'CDROM-HTML') {
                    $questiondata = $this->createImagePathPreview(str_replace('\\', '', $questiondata), $questionimagepath);
                }
                $i++;
            }
        }
        $totalquest = ($i - 1);
        Site::myDebug('---$totalquest');
        Site::myDebug($totalquest);

        $randomCriteria['QuestionIds'] = rtrim($randomQuestionIds, ',');
        $randomCriteria = json_encode($randomCriteria);

        if ($action == 'publishq') {
            $this->db->execute("UPDATE PublishAssessments SET RandomQuestionCriteria='$randomCriteria' WHERE ID='$guid' ");
        }

        $shellxml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                            <manifest>
                            <quiz>
                                <assessmentTitle>" . $QuizTitle . "</assessmentTitle>
                                <summative>" . $qsummative . "</summative>
                                <formative>" . $qformative . "</formative>
                                <shuffle>" . $qshuffle . "</shuffle>
                                <backMove>" . $qmoveback . "</backMove>
                                <skip>" . $qskip . "</skip>
                                <showTickCross>" . $TicksCross . "</showTickCross>
                                <score>
                                    <questionScore>" . $qscore . "</questionScore>
                                    <specifyScore>
                                            <totalScore>" . $qtotalscore . "</totalScore>
                                            <passingScore>" . $qpassingscore . "</passingScore>
                                    </specifyScore>
                                </score>
                                <partial>" . $qpartial . "</partial>
                                <attempt>" . $qattempt . "</attempt>
                                <feedbackOption>
                                        <questionLevel>" . $qqlevel . "</questionLevel>
                                        <optionLevel>" . $qolevel . "</optionLevel>
                                </feedbackOption>
                                <tryAgain>" . $qtryagain . "</tryAgain>
                                <audio>off</audio>
                                <summaryOption>
                                    <condition1>
                                            <percent>" . $qper0 . "-" . $qper1 . "</percent>
                                            <message><![CDATA[" . $qmessage1 . "]]></message>
                                            <action>" . $action1 . "</action>
                                    </condition1>
                                    <condition2>
                                            <percent>" . $qper2 . "-" . $qper3 . "</percent>
                                            <message><![CDATA[" . $qmessage2 . "]]></message>
                                            <action>" . $action2 . "</action>
                                    </condition2>
                                    <condition3>
                                            <percent>" . $qper4 . "-" . $qper5 . "</percent>
                                            <message><![CDATA[" . $qmessage3 . "]]></message>
                                            <action>" . $action3 . "</action>
                                    </condition3>
                                </summaryOption>
                                <random>" . $random . "</random>
                                <randomCriteria><![CDATA[{$randomCriteria}]]></randomCriteria>
                                <questionData><![CDATA[Data/QuestionDetails.js]]></questionData>
                                <randomQuestCount><![CDATA[{$randomQuestCount}]]></randomQuestCount>
                                <totalStaticPages><![CDATA[{$staticpagecount}]]></totalStaticPages>
                                <helpText><![CDATA[" . $HelpMessage . "]]></helpText>
                                <totalquest><![CDATA[{$totalquest}]]></totalquest>
                                <totalAssessmentScore><![CDATA[{$totalAssessmentScore}]]></totalAssessmentScore>
                            </quiz></manifest>";
        $shellxmlpath = $assessmentpath . '/data/shell.xml';
        $fh2 = fopen($shellxmlpath, 'w');
        fwrite($fh2, $shellxml);
        fclose($fh2);

        Site::myDebug("--------RandomVal");
        Site::myDebug($randomQuestCount);
        Site::myDebug($randomQuestCount);


        $objshellxml = simplexml_load_string($shellxml, null, LIBXML_NOCDATA);
        $converter = new DataConverter();
        $shellarray = $converter->convertXmlToArray($objshellxml->asXML());
        $objJSON = new Services_JSON();
        $shellJSON = $objJSON->encode($shellarray);
        $shelljsonpath = $assessmentpath . '/data/Shell.js';
        $fh2 = fopen($shelljsonpath, 'w');
        fwrite($fh2, "var AstShellJSON= {$shellJSON};");
        fclose($fh2);
        if ($prevSource == "wordTemplate") {
            //create config xml file
            if ($input['Help'] == '1') {
                $qhelp = 'true';
            } else {
                $qhelp = 'false';
            }
            if ($input['Timer'] == '1') {
                $qtimer = 'true';
                $qminutes = $input['Minutes'];
            } else {
                $qtimer = 'false';
                $qminutes = '0';
            }
            if ($input['Map'] == '1') {
                $qmap = 'true';
            } else {
                $qmap = 'false';
            }
            if ($input['Flag'] == '1') {
                $qflag = 'true';
            } else {
                $qflag = 'false';
            }
            if ($input['Hint'] == '1') {
                $qhint = 'true';
            } else {
                $qhint = 'false';
            }
            if ($input['ShowQNo'] == '1') {
                $qpagination = 'true';
            } else {
                $qpagination = 'false';
            }
        } else {
            //create config xml file
            if ($this->getAssociateValue($AssessmentSettings, 'Help') == '1') {
                $qhelp = 'true';
            } else {
                $qhelp = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Timer') == '1') {
                $qtimer = 'true';
                $qminutes = $this->getAssociateValue($AssessmentSettings, 'Minutes');
            } else {
                $qtimer = 'false';
                $qminutes = '0';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Map') == '1') {
                $qmap = 'true';
            } else {
                $qmap = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Flag') == '1') {
                $qflag = 'true';
            } else {
                $qflag = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'Hint') == '1') {
                $qhint = 'true';
            } else {
                $qhint = 'false';
            }
            if ($this->getAssociateValue($AssessmentSettings, 'ShowQNo') == '1') {
                $qpagination = 'true';
            } else {
                $qpagination = 'false';
            }
        }
        $qseconds = (int) $qminutes * 60;

        $questiondata = substr($questiondata, 0, -1);

        if ($action == 'publishq') {
            //$xmlPath = ($publishtype == 'CDROM-HTML' )? "var xmlPath = '';" : "var xmlPath = '{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPublishLocation}{$guid}/';";
            $dataPath = $APPCONFIG->PersistDataPath;
            $xmlPath = ($publishtype == 'CDROM-HTML' ) ? "var xmlPath = '';" : "var xmlPath = '{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizMLPublishPath}{$guid}/';";
        } else {
            ///$xmlPath = "var xmlPath = '{$CONFIG->wwwroot}/{$APPCONFIG->QuizHtmlPreviewLocation}{$guid}/';";
            $dataPath = $APPCONFIG->tempDataPath;
            $xmlPath = "var xmlPath = '{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizMLPreviewPath}{$guid}/';";
        }

        $quadCookie = 'var quadCookie= "' . md5(uniqid()) . '";';
        $asmtInfo = "var AsmtInfoObject = {\"title\":\"{$QuizTitle}\",\"score\":\"{$totalAssessmentScore}\",\"time\":\"{$qseconds}\",\"questions\":\"{$totalquest}\"};";
        $this->mydebug("This is Question details.");
        $this->mydebug($asmtInfo);
        $this->mydebug("This is trial questions");
        $this->mydebug($questiondata);
        $questionjs = "var QuestionJS = __QUESTION-DATA__ ;";
        $questiondetails = "{$xmlPath} {$quadCookie} {$asmtInfo} {$questionjs}";
        $questionjspath = $assessmentpath . '/data/QuestionDetails.js';
        $fh2 = fopen($questionjspath, 'w');
        fwrite($fh2, $questiondetails);
        fclose($fh2);

        $ExitMessage = html_entity_decode(html_entity_decode($this->getAssociateValue($AssessmentSettings, 'ExitMessage')));
        $this->CopyMultiImage($ExitMessage, $pathname . '/');
        ///$ExitMessage = str_replace($CONFIG->wwwroot.'/'.$APPCONFIG->EditorImagesUpload,'',$ExitMessage);
        $ExitMessage = str_replace($CONFIG->wwwroot . '/' . $dataPath . $instID . "/assets/images/original/", '', $ExitMessage);

        $configFiled = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><config>
                                <assetPath></assetPath>
                                <topBanner>
                                        <height>{$topbanner}</height>
                                </topBanner>
                                <footerButton>
                                        <print>
                                                <xPosition>20</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </print>
                                        <reset>
                                                <xPosition>50</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </reset>
                                        <help>
                                                <xPosition>80</xPosition>
                                                <visible>" . $this->getBoolean($qhelp) . "</visible>
                                        </help>
                                        <submit>
                                                <xPosition>680</xPosition>
                                                <visible>false</visible><!--Never -->
                                        </submit>
                                        <previous>
                                                <xPosition>902</xPosition>
                                                <visible>" . $this->getBoolean($qmoveback) . "</visible>
                                        </previous>
                                        <next>
                                                <xPosition>930</xPosition>
                                                <visible>true</visible>
                                        </next>
                                        <timer>
                                                <xPosition>450</xPosition>
                                                <visible>" . $this->getBoolean($qtimer) . "</visible>
                                                <totalTime>{$qseconds}</totalTime><!--expect Number, we assume that given value in second.(Ex : 600, means 10min.) -->
                                        </timer>
                                        <pagination>
                                            <xPosition>780</xPosition>
                                                <visible>" . $this->getBoolean($qpagination) . "</visible>
                                        </pagination>
                                        <flag>
                                                <xPosition>250</xPosition>
                                                <visible>" . $this->getBoolean($qflag) . "</visible>
                                        </flag>
                                        <quizMap>
                                                <xPosition>150</xPosition>
                                                <visible>" . $this->getBoolean($qmap) . "</visible>
                                        </quizMap>
                                        <hintRP>
                                                <xPosition>570</xPosition>
                                                <visible>false</visible>
                                        </hintRP>
                                        <hint>
                                                <xPosition>570</xPosition>
                                                <visible>" . $this->getBoolean($qhint) . "</visible>
                                        </hint>
                                    </footerButton>
                                    <isDirector>{$isDirector}</isDirector><!--yes/no -->
                                    <timeOutMessage><![CDATA[Ooops! Time's up, click submit to view your results.]]></timeOutMessage>
                                    <ExitMessage><![CDATA[{$ExitMessage}]]></ExitMessage>
                                    <quizSubmitMessage><![CDATA[Are you sure you want to submit the quiz? If you do you will not be able to change your answers.]]></quizSubmitMessage>
                                    <copyRightText><![CDATA[Copyright]]></copyRightText>
                                    <toolTip>
                                        <print>
                                                <![CDATA[Print]]>
                                        </print>
                                        <reset>
                                                <![CDATA[Reset]]>
                                        </reset>
                                        <help>
                                                <![CDATA[Help]]>
                                        </help>
                                        <submit>
                                                <![CDATA[Submit]]>
                                        </submit>
                                        <previous>
                                                <![CDATA[Previous]]>
                                        </previous>
                                        <next>
                                                <![CDATA[Next]]>
                                        </next>
                                        <flag>
                                                <![CDATA[Flag]]>
                                        </flag>
                                        <quizMap>
                                                <![CDATA[Quiz map]]>
                                        </quizMap>
                                        <hint>
                                                <![CDATA[Hint]]>
                                        </hint>
                                        <fullText>
                                                <![CDATA[Full text]]>
                                        </fullText>
                                        <optionalFeedback>
                                                        <![CDATA[Optional feedback]]>
                                        </optionalFeedback>
                                        <magnifyImage>
                                                        <![CDATA[Magnify image]]>
                                        </magnifyImage>
                                        <imageDescription>
                                                        <![CDATA[Image description]]>
                                        </imageDescription>
                                        <videoDescription>
                                                        <![CDATA[Video description]]>
                                        </videoDescription>

                                        <magnifyVideo>
                                                        <![CDATA[Magnify video]]>
                                        </magnifyVideo>
                                        <closePopup>
                                                        <![CDATA[Close]]>
                                        </closePopup>
                                        <audio>
                                                        <![CDATA[Play audio]]>
                                        </audio>
                                        <tryAgain>
                                                        <![CDATA[Try again!]]>
                                        </tryAgain>
                                        <showAnswer>
                                                        <![CDATA[Show answer]]>
                                        </showAnswer>
                                        <sources>
                                                        <![CDATA[Show sources]]>
                                        </sources>
                                        <examinerComment>
                                                        <![CDATA[Examiner's comments]]>
                                        </examinerComment>
                                    </toolTip>
                                </config>";

        $configfilepath = $assessmentpath . '/data/config.xml';
        $fh3 = fopen($configfilepath, 'w');
        fwrite($fh3, $configFiled);
        fclose($fh3);

        $objconfigxml = simplexml_load_string($configFiled, null, LIBXML_NOCDATA);
        $configarray = $converter->convertXmlToArray($objconfigxml->asXML());
        $configJSON = $objJSON->encode($configarray);
        $configjsonpath = $assessmentpath . '/data/Config.js';
        $fh2 = fopen($configjsonpath, 'w');
        $configJSON = str_replace('\n', '', $configJSON);
        $configJSON = str_replace('\t', '', $configJSON);
        fwrite($fh2, "var AstConfigJSON= {$configJSON};");
        fclose($fh2);

        if ($action == "publishq") {
            if ($DBCONFIG->dbType == 'Oracle')
                $this->db->execute("UPDATE Assessments SET \"Status\" = 'Published' WHERE ID=$quizid and \"isEnabled\" = 1 ");  //Set status to published.
 else
                $this->db->execute("UPDATE Assessments SET status='Published' WHERE ID='$quizid' and isEnabled = '1' ");  //Set status to published.
 $renditionurl = "{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizMLPublishPath}{$guid}{$APPCONFIG->PermaLinkHtml}{$guid}&State=false&Target=MarkLogic";
        }
        else {
            $renditionurl = "{$CONFIG->wwwroot}/{$dataPath}{$instID}/{$APPCONFIG->QuizMLPreviewPath}{$guid}{$APPCONFIG->PreviewLinkHtml}{$quizid}&PublishedGuid={$guid}&State=false&Target=MarkLogic";
        }
        return array(
            "guid" => $guid,
            "renditionurl" => $renditionurl);
    }

    public function updateQuestionStatus($newQuestID, $oldQuestID, $repID) {
        global $DBCONFIG;
        Site::myDebug('---------updateQuestionStatus');
        if ($DBCONFIG->dbType == 'Oracle') {
            $sqlQry = "SELECT mrq.\"ID\", mrq.\"EntityTypeID\", mrq.\"EntityID\", mrq.\"EditStatus\"
                                FROM MapRepositoryQuestions mrq
                                WHERE mrq.\"QuestionID\" = $newQuestID AND mrq.\"isEnabled\" = 1  ";
        } else {
            $sqlQry = "SELECT mrq.ID, mrq.EntityTypeID, mrq.EntityID, mrq.EditStatus
                                FROM MapRepositoryQuestions mrq
                                WHERE mrq.QuestionID = $newQuestID AND mrq.isEnabled = 1  ";
        }

        $result = $this->db->getRows($sqlQry);
        $updateCheckoutInfo = false;
        if ($result) {
            foreach ($result as $key => $data) {
                if ($data['EditStatus'] == 1) {
                    $updateCheckoutInfo = true;
                    break;
                }
            }
        }
        if ($updateCheckoutInfo) {
            Site::myDebug('---------$updateCheckoutInfo');

            $this->updateCheckedOutQuestID($newQuestID, $statusVal = 1);
            $this->updateCheckedOutQuestID($oldQuestID, $statusVal = 0);
        }
    }
    /**
    * * 
    * * PAI02 :: sprint 4 ::  QUADPS-91
    *  Add Composite item(Questions) in to an assessment 
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    *
    *
    */
   function hasDuplicateSection($array) {
        $dupeArray = array();
        foreach ($array as $val) {
            if (++$dupeArray[$val] > 1) {
                return true;
            }
        }
        return false;
    }
     public function addCompositeItemAndQuestion($input) {
		//echo "<pre>"; print_r($input);//die();
        global $DBCONFIG;        
        $userID = ($input["UserID"] == "" ) ? $this->session->getValue('userID') : $input["UserID"];
        $EntityTypeID = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $MapRepIDs = $input['MapRepIDs'];
        $MapRepIDVal = explode(",", $MapRepIDs);
		//print_r($MapRepIDVal);echo count($MapRepIDVal);die('-----------------------------');
        //Warning for the same section name
        $secArray = array();
        for ($i = 0; $i < count($MapRepIDVal); $i++) 
		{
			$SectionNameQuery1 = "SELECT SectionName FROM MapRepositoryQuestions  WHERE isEnabled=1 AND ID=".$MapRepIDVal[$i];
            $getSecName1 = $this->db->getSingleRow($SectionNameQuery1);
            $getSecCount1 = count($getSecName1);
            array_push($secArray, $getSecName1['SectionName']);
            
        }
        $duplicateSection = $this->hasDuplicateSection(array_filter($secArray));        
		if($duplicateSection == 1)
		{        
           $input['secStatus']= '1';
        }
		else
		{        
			for ($i = 0; $i < count($MapRepIDVal); $i++) 
			{            
            //Fetch section name for the repository id 
            $SectionNameQuery = "SELECT SectionName FROM MapRepositoryQuestions  WHERE isEnabled=1 AND ID=" . $MapRepIDVal[$i];
            $getSecName = $this->db->getSingleRow($SectionNameQuery);
				$getSecCount = count($getSecName);            

            $data = array(
                $EntityID,
                $EntityTypeID,
                0,
                $MapRepIDVal[$i],
                $getSecName['SectionName'],
                $userID,
                $this->currentDate(),
                $this->currentDate(),
                $userID,
                '1'
				);            
            $sectionDetails = $this->db->executeStoreProcedure('AddSectionManage', $data);
            $sectionID = $sectionDetails['RS'];
				
				/*
				*   QUADPS-128
				*	Search Composite Item - On adding section with same name 2nd time message not displayed.
				*/
				if(isset($sectionID[0]['Status']) && ($sectionID[0]['Status'] == 'SectionExists'))
				{
					$input['secStatus']= '1';break;
				}
				
				$newSecID = $sectionID[0]['SecID'];				
            $maxSequence = $sectionID[0]['maxSequence'];

				if (!empty($newSecID)) 
				{
                //Fetch questions for the repository id to add into new composite item
                $getQuestQuery = "SELECT QuestionID, QuestionTemplateID FROM MapRepositoryQuestions  WHERE isEnabled=1 AND ParentID=" . $MapRepIDVal[$i];
                $getQuestRes = $this->db->getRows($getQuestQuery);
                $getQuestCount = count($getQuestRes);
                
                //Add question 
					foreach ($getQuestRes as $secKey => $secValue) 
					{
                        $maxSequence++;
                        $addQuestInNewSec = array('EntityTypeID' => $EntityTypeID,
                            'EntityID' => $EntityID,
                            'QuestionID' => $secValue['QuestionID'],
                            'QuestionTemplateID' => $secValue['QuestionTemplateID'],
                            'EditStatus' => '0',
                            'SectionName' => '',
                            'ParentID' => $newSecID,
                            'Sequence' => $maxSequence,
                            'UserID' => $userID,
                            'ADDDATE' => $this->currentDate(),
                            'ModBY' => $userID,
                            'ModDate' => $this->currentDate(),
                            'isEnabled' => '1'
                        );
                        $questId = $this->db->insert("MapRepositoryQuestions", $addQuestInNewSec);                    
           }
        }
        $input['secStatus']= '2';
   }
  
		}  
        return $input['secStatus'] ; 
    }
}

//simple task: convert everything from utf-8 into an NCR[numeric character reference]
class unicode_replace_entities {

    /**
     * a function to get the content as utf8 as applicable after conversion
     *
     *
     * @access   public
     * @param    string  $content
     * @return   string
     *
     */
    public function UTF8entities($content = "") {
        $contents = $this->unicodeStringToArray($content);
        $swap = "";
        $iCount = count($contents);
        for ($o = 0; $o < $iCount; $o++) {
            $contents[$o] = $this->unicodeEntityReplace($contents[$o]);
            $swap .= $contents[$o];
        }
        return mb_convert_encoding($swap, "UTF-8"); //not really necessary, but why not.
    }

    /**
     * a function to get each character of string as utf8 converted
     *
     *
     * @access   public
     * @param    string  $string
     * @return   array
     *
     */
    public function unicodeStringToArray($string) { //adjwilli
        $strlen = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "UTF-8");
            $string = mb_substr($string, 1, $strlen, "UTF-8");
            $strlen = mb_strlen($string);
        }
        return $array;
    }

    /**
     * a function to get unicode of the gievn character
     *
     *
     * @access   public
     * @param    string  $c
     * @return   string
     *
     */
    public function unicodeEntityReplace($c) { //m. perez
        $h = ord($c{0});
        if ($h <= 0x7F) {
            return $c;
        } else if ($h < 0xC2) {
            return $c;
        }

        if ($h <= 0xDF) {
            $h = ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h;
        } else if ($h <= 0xEF) {
            $h = ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h;
        } else if ($h <= 0xF4) {
            $h = ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
            $h = "&#" . $h . ";";
            return $h;
        }
    }
     

}

//
?>