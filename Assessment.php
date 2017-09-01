<?php

/**
 * This class handles all assessment/archive module related queries/requests
 * This class handles the business logic of listing/add/edit/delete/search/questionlist and other requests of assessment and archive.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */
class Assessment extends Site {

    public $id;
    public $questCnt;

    /**
     * constructs a new classname instance
     */
    function __construct() {
        parent::Site();
        $this->id = "";
        $this->questCnt = "";
    }

    /**
     * Saves the Assessment details.
     * @access   public
     * @param    array  $input
     * @return   int  $asmtID
     *
     */
    function save(array $input) {
        global $DBCONFIG;
        $CurrentTabNum = $input['CurrentTabNum'];
        $SettingIDArray = array();
        $SettingValueArray = array();
        if (!empty($_REQUEST)) {
            foreach ($_REQUEST as $key => $value) {
                if (strpos($key, "Setting_") > -1) {
                    $SettingIDArray[] = str_replace("Setting_", "", $key);
                    $SettingValueArray[] = (trim($value) != "") ? trim($value) : "NA";
                }
            }
        }
        $input["AssessmentID"] = (is_numeric($input['AssessmentID'])) ? $input['AssessmentID'] : "0";
        $Users = ($this->isVarSet('AssessmentUsers')) ? $input["AssessmentUsers"] : $this->session->getValue('userID');
        $tags = (array_key_exists("Tags", $input)) ? $input['Tags'] : $input['Tags'];
        $tags = trim($tags);


        $this->createTagRunTime($tags); // This will create tag runtime

        $input['AssessmentInfo'] = (array_key_exists('AssessmentInfo', $input)) ? $input['AssessmentInfo'] : $input['AssessmentName'];
        $input['AssessmentTitle'] = (array_key_exists('AssessmentTitle', $input)) ? $input['AssessmentTitle'] : $input['AssessmentName'];
        $input['QuestionUpdate'] = (array_key_exists('QuestionUpdate', $input)) ? $input['QuestionUpdate'] : "2";
        $input['Mode'] = (array_key_exists('Mode', $input)) ? $input['Mode'] : "1";
        $input['Type'] = (array_key_exists('Type', $input)) ? $input['Type'] : "1";
        // for shortName
        $input['AssessmentShortName'] = (array_key_exists('AssessmentShortName', $input)) ? $input['AssessmentShortName'] : "";
        $qtp = new QuestionTemplate();
        $Templates = (array_key_exists('AssessmentTemplates', $input) && (trim($input["AssessmentTemplates"]) != '')) ? $input["AssessmentTemplates"] : $qtp->questionTemplateIdString();
        $SettingID = implode(",", $SettingIDArray);

        if ($SettingID == "") {
            $SettingArray = $this->defaultSettings();
            $SettingID = $SettingArray['ID'];
            $SettingValue = $SettingArray['DefaultValue'];
        } else {
            $SettingID = implode(",", $SettingIDArray);
            $SettingValue = implode(",", $SettingValueArray);
        }
        $strQuestionUpdate = ( $input["QuestionUpdate"] == 1) ? 'Y' : 'N';
        
        $input['input-ace-is-publish'] = (array_key_exists('input-ace-is-publish', $input)) ? $input['input-ace-is-publish'] : "0";
        
        $dataArray = array(
            $input["AssessmentID"],
            $input["AssessmentName"],
            $input["AssessmentInfo"],
            $input["AssessmentTitle"],
            $input["Mode"],
            $input["Type"],
            "Private",
            $this->session->getValue('userID'),
            $this->currentDate(),
            1,
            $Users,
            $tags,
            $input["taxonomyNodeIds"],
            $SettingID,
            $SettingValue,
            $Templates,
            $strQuestionUpdate, $this->session->getValue('accessLogID'),
            $input["AssessmentShortName"],
            $input["input-ace-is-publish"],
            $input["input-ace-product"],
            $input["input-ace-taxonomy"],
            $input["input-ace-kvp"],
            $input["input-ace-tag"],
            $input["input-ace-product-name"]
        );
        

        $AssessmentDetail = $this->db->executeStoreProcedure('AssessmentManage', $dataArray, "nocount");

        $asmtID = $this->getValueArray($AssessmentDetail, "AssessmentID");
        $activityAction = ($input["AssessmentID"]) ? 'Edited' : 'Added';
        $Metadata = new Metadata();
        if ($input["AssessmentID"]) {
            $asmtID = $input["AssessmentID"];
        }

        $auth = new Authoring();
        $assmtTypeId = $this->getEntityId("Assessment");
        if (!isset($input["QuestID"])) {
            $Metadata->assignedMetadata($input, $asmtID, $assmtTypeId);
        }

        $data_val = array(
            0,
            $this->session->getValue('userID'),
            2,
            $asmtID,
            $input["AssessmentName"],
            $activityAction,
            $this->currentDate(),
            1, $this->session->getValue('accessLogID')
        );
        $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
        $addedusers_list = $Users;
        $addedusers_list = explode(",", $addedusers_list);

        $UserRightsString = $input["UserRight"];
        if ($UserRightsString) {
            $UserRightsRecords = explode("@", $UserRightsString);
            for ($i = 0; $i < count($UserRightsRecords); $i++) {
                $tmp = explode("-", $UserRightsRecords[$i]);
                $MemberIds[$i] = $tmp[0];
                if (in_array($MemberIds[$i], $addedusers_list)) {
                    $RightsString = $tmp[1];
                    $updated_data = array(
                        'ModDate' => $this->currentDate(),
                        'isEnabled' => '0'
                    );
                    $RightsValString = explode(",", $RightsString);
                    for ($j = 0; $j < count($RightsValString); $j++) {
                        $RightsVal = explode(":", $RightsValString[$j]);
                        $right_id = $RightId[$i][$j] = $RightsVal[0];
                        $right_val = $RightVal[$i][$j] = $RightsVal[1];
                        $data = array(
                            'MemberId' => $MemberIds[$i],
                            'EntityTypeId' => $assmtTypeId,
                            'EntityId' => $asmtID,
                            'UserRightsId' => $right_id,
                            'AddBy' => $this->session->getValue('userID'),
                            'AddDate' => $this->currentDate(),
                            'ModDate' => $this->currentDate(),
                            'isActive' => $right_val,
                            'isEnabled' => '1'
                        );
                        $this->myDebug($data);

                        if ($DBCONFIG->dbType == 'Oracle') {
                            $where = " \"EntityTypeId\"={$assmtTypeId} 
                                                and \"EntityId\" ={$asmtID} and \"MemberId\" ={$MemberIds[$i]} 
                                                and \"UserRightsId\"={$right_id} and \"AddBy\" ={$this->session->getValue('userID')} and \"isEnabled\" ='1' ";
                        } else {
                            $where = " EntityTypeId={$assmtTypeId} and EntityId={$asmtID} and MemberId={$MemberIds[$i]} and UserRightsId={$right_id} and AddBy={$this->session->getValue('userID')} and isEnabled='1' ";
                        }
                        $this->db->update("MapEntityRights", $updated_data, $where);
                        $map_entity_right_id = $this->db->insert("MapEntityRights", $data);
                    }
                }
            }
        }
        return $asmtID;
    }

    /*
     * @author Manish 30-july-2015
     * for generating ShortNameSuggestion
     * return random string
     */

    function generateRandomString($characters) {
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $charactersLength; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function randomString($length = 15) {
        $str = "";
        $characters = array_merge(range('A','Z'), range('0','9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
                $rand = mt_rand(0, $max);
                $str .= $characters[$rand];
        }
        return $str;
    }
    
    function getShortNameSuggestion($input) {
        
        $AsmtID = $input['assessmentID'];
        if (isset($input['name'])) {
            $shortName = $input['name'];
        } else {
            $AsmtDetails = $this->Assessment->asmtDetail($AsmtID);
            $shortName = strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", trim($AsmtDetails["AsmtName"])));
        }
        
        if(strlen($shortName)<5){
            $shortName = $this->randomString();
        }else{
            $shortName = str_shuffle($shortName);
        }
        
           
        $uniqueFound = false;
        while (!$uniqueFound) {
            $shortNamePrev[] = $shortName = substr($shortName, 0, 5);
            $rowCount = $this->asmtShortNameCheck($shortName, $AsmtID);
            if ($rowCount == 0) {
                $uniqueFound = true;
            } else {
                $shortName = $this->generateRandomString($shortName);
                //if($shortName == $shortNamePrev){
                if (in_array($shortName, $shortNamePrev)) {
                    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                    $shortName = str_shuffle($chars);
                    $shortName = $this->generateRandomString($shortName);
                }
            }
        }


        return strtoupper($shortName);
    }

    /*
     * Checks whether AssessmentName already exists
     *
     * @access  public
     * @param   array $input
     * @return  int $assessmentExists
     * 
     */

    function assessmentExist($input) {
        // $assessmentname[0] = $input;
        $data = array(
            $input,
            $this->session->getValue('instID')
        );
        $AssessmentExists = $this->db->executeStoreProcedure('AssessmentExists', $data, "list");
        return $AssessmentExists;
    }

    /**
     * Gets the default (system defined) settings  for Assessment
     *
     *
     * @access   public
     * @return   array  $dataArray
     *
     */
    function defaultSettings() {
        global $DBCONFIG;

        if ($DBCONFIG->dbType == 'Oracle') {
            $qry = "select \"ID\", \"SettingName\", dbms_lob.substr(\"SettingInfo\",32000,1) AS \"SettingInfo\",
                                dbms_lob.substr(\"DefaultValue\",32000,1) AS \"DefaultValue\", \"Level\",
                                \"UserID\", \"AddDate\", \"ModBY\", \"ModDate\", \"isEnabled\", \"EntityTypeID\"
                                   from DeliverySettings  WHERE \"isEnabled\" = '1' ";
        } else {
            $qry = "select * from DeliverySettings where isEnabled = '1' ";
        }

        $res = $this->db->getRows($qry);
        if (!empty($res)) {
            foreach ($res as $res1) {
                $Id[] = $res1['ID'];
                $DefaultValue[] = (trim($res1['DefaultValue']) != "") ? $res1['DefaultValue'] : "NA";
            }
        }
        $Id = implode(",", $Id);
        $DefaultValue = implode(",", $DefaultValue);
        $dataArray = array('ID' => $Id, 'DefaultValue' => $DefaultValue);
        return $dataArray;
    }

    /**
     * Get Archive List of an institute
     *
     *
     * @access   public
     * @param    array  	$input
     * @param    string  	$condition
     * @return   array         List of Archived List
     *
     */
    function archiveList(array $input, $condition = '') {
        $condition = ($condition != '') ? $condition : '-1';
        return $this->db->executeStoreProcedure('ArchiveList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('instID'), $this->session->getValue('isAdmin'), $this->session->getValue('userID'), $input['pgndc']));
    }

    /**
     * Get Archive List for the searched criteria
     *
     *
     * @access   public
     * @param    array  	$input
     * @param    string  	$condition      
     * @param    string(JSON)  $tags           
     * @param    string(JSON) 	$taxonomies
     * @return   array         $data
     *
     */
    function archiveSearchList(array $input, $condition, $tags, $taxonomies) {
        global $DBCONFIG;
        $condition = ($condition != '') ? $condition : '-1';
        $input['pgndc'] = ($DBCONFIG->dbType == 'Oracle' ) ? "u.\"UserName\"" : "u.UserName";
        $input['pgnob'] = ($input['pgnob'] == '') ? "-1" : $input['pgnob'];
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];

        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tags;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomies;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        $value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['ownerName'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['ownerName'];
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];
        $search = ($search == '') ? -1 : $search;
        $owner = ($owner == '') ? -1 : $owner;

        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';

        $data = $this->db->executeStoreProcedure('ArchiveSearchList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc'], $tags, $taxo, $owner, $key, $value, $startdate, $enddate, $searchtype, $title_filter, $users_filter, $date_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter));
        $input['entityid'] = 0;
        $input['entitytypeid'] = 8;
        $input['spcall'] = $data['QR'];
        $input['count'] = $data['TC'];

        if (trim($input['hdn_searchcrieteria']) != '') {
            $this->saveAdvSearchCrieteria($input);
        }
        return $data;
    }

    /**
     * Get Assessment List of an institute  which has sections  
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    string  	$condition
     * @return   array         Assessment List
     *
     */
    function AssessmentSecList(array $input, $condition = '') {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : 'ast."ModDate" ';
        } else {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "ast.ModDate";
        }

        $input['pgnot'] = ($input['pgnot'] != "-1") ? $input['pgnot'] : "desc";
        if (trim($this->getInput('orderBy')) == '') {
            $this->setInput('orderBy', 'ast.ModDate');
        }
        $condition = ($condition != '') ? $condition : '-1';
        return $this->db->executeStoreProcedure('AssessmentSecList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
    }

    /**
     * Get Assessment List of an institute  
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    string  	$condition
     * @return   array         Assessment List
     *
     */
    function assessmentList(array $input, $condition = '') {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : 'ast."ModDate" ';
        } else {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "ast.ModDate";
        }

        $input['pgnot'] = ($input['pgnot'] != "-1") ? $input['pgnot'] : "desc";
        if (trim($this->getInput('orderBy')) == '') {
            $this->setInput('orderBy', 'ast.ModDate');
        }
        $condition = ($condition != '') ? $condition : '-1';
        return $this->db->executeStoreProcedure('AssessmentList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $condition, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));
    }

    /**
     * Get Assessment List for the searched criteria
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    string  	$condition
     * @param    string(JSON)  $tags
     * @param    string(JSON) 	$taxonomies
     * @return   array         $data           Assessment List
     *
     */
    function assessmentSearchList(array $input, $condition, $tags, $taxonomies) {
        global $DBCONFIG;

        $condition = ($condition != '') ? $condition : '-1';

        $this->myDebug("----------Inputs----------");
        $this->myDebug($input);
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != '-1') ? $input['pgnob'] : ' ast."ModDate" ';
            $input['pgndc'] = ' usr."UserName" ';
        } else {
            $input['pgnob'] = ($input['pgnob'] != '-1') ? $input['pgnob'] : 'ast.ModDate';
            $input['pgndc'] = "usr.UserName";
        }


        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];

        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tags;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomies;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        $value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['ownerName'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['ownerName'];
		
		if ($startdate != "" || $enddate != "")
        {
            $newStartDate = $startdate;
            $newEndDate = $enddate;
            if ($startdate == $enddate)
            {
                $newStartDate = $startdate;
                $newEndDate = '';
            }
            else
            {
                $newStartDate = $startdate;
                $newEndDate = $enddate;
            }
        }
		
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];
		$question_status = ($json->keyinfo->question_status->id != '') ? ($json->keyinfo->question_status->id) : $input['ownerName'];
		$searchEntityIDs = ($json->searchInfo->searchResult->id != '') ? $json->searchInfo->searchResult->id : $searchInfoIDs;
        $search = ($search == '') ? -1 : str_replace("'", "''", $search);
        $owner = ($owner == '') ? -1 : $owner;

        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';
		$question_status_filter = ($json->keyinfo->question_status->filtertype == 'exclude') ? 'exclude' : 'include';   //QUAD-86
        $searchEntityIDs_filter = ($json->searchInfo->searchResult->filtertype == 'exclude') ? 'exclude' : 'include'; //QUAD-85	
        $searchBnkFilter = ($json->searchInfo->searchResultBank->filtertype == 'exclude') ? 'exclude' : 'include';
		
		if($input['sSearch']){
			$entityTypeID	=	'2';
			$entityID	=	'0';
			$advSearchBnkQuestIds	=	'-1';
			$bnkEntityTypeId = '-1';
			$advSearchAsmtQuestIds = '-1';
            $asmtEntityTypeId = '-1';
			$bulkEditEntityIds = '-1';
			$pFilterNewSearchValue=$input['sSearch'];
			$pFilterOldSearchValue=$input['search'];
			$storeProcedureName='AssessmentSearchListFilter';
                $storeProcedureArray=array($input['pgnob'],
                    $input['pgnot'],
                    $input['pgnstart'],
                    $input['pgnstop'],
                    '-1', //$search
                    '-1',
                    '-1', //this is an entity type id
                    $this->session->getValue('userID'),
                    $input['pgndc'],
                    $tags,
                    $taxo,
                    $owner,
                    $this->session->getValue('instID'),
                    $key,
                    $value,
                    $difficulty, $template, $newStartDate, $newEndDate, $searchtype, $question_status, $searchEntityIDs,
                    $title_filter, $users_filter, $templates_filter, $date_filter, $difficulty_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter,
                    $question_status_filter, $searchEntityIDs_filter,
                    $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds,$pFilterNewSearchValue,$pFilterOldSearchValue);
                
                    $data = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);
		
		}else{
			$data = $this->db->executeStoreProcedure('AssessmentSearchList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc'], $tags, $taxo, $owner, $key, $value, $startdate, $enddate, $searchtype, $title_filter, $users_filter, $date_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter));
		}
		
		
        $input['entityid'] = 0;
        $input['entitytypeid'] = 2;
        $input['spcall'] = $data['QR'];
        $input['count'] = $data['TC'];

        if (trim($input['hdn_searchcrieteria']) != '') {
            $this->saveAdvSearchCrieteria($input);
        } else {
            //Retain the current search context and the latest search string in the quick search field on each page
            //when searched from assessment listing page
            $quickSearchData = array('SearchText' => $input['search'],
                'SearchContext' => $input['SearchContext'],
                'SearchCount' => $input['count'],
                'EntityID' => 0,
                'EntityTypeID' => 0,
                'isEnabled' => 1
            );
            $this->saveQuickSearchCriteria($quickSearchData);
        }
        return $data;
    }

    /**
     * Update Assessment status to Active
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  	$input
     * @return   boolean
     *
     */
    function unarchive($input) {
        global $DBCONFIG;
        $quizIds = implode(',', $input);
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE Assessments SET \"Status\" = 'Active', \"ModBY\" ={$this->session->getValue('userID')} WHERE \"ID\" IN ($quizIds) AND \"isEnabled\" = '1' ";
        } else {
            $query = "UPDATE Assessments SET status = 'Active', ModBY={$this->session->getValue('userID')} WHERE ID IN ($quizIds) AND isEnabled = '1'";
        }

        return $this->db->execute($query);
    }

    /**
     * Update Assessment status to Archive
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  	$input
     * @return   boolean
     *
     */
    function archive($input) {
        global $DBCONFIG;
        $quizIds = implode(',', $input);
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE Assessments SET \"Status\" = 'Archive', \"ModBY\" = {$this->session->getValue('userID')} WHERE \"ID\" IN ($quizIds) AND \"isEnabled\" = '1' ";
        } else {
            $query = "UPDATE Assessments SET status = 'Archive', ModBY={$this->session->getValue('userID')} WHERE ID IN ($quizIds) AND isEnabled = '1'";
        }

        return $this->db->execute($query);
    }

    /**
     * Get Assessment details from Question list page.
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    mixed  	$asmtID
     * @return   array         $details
     *
     */
    function asmtDetailInfo($asmtID = "-1") {
        if (empty($details)) {

            $condition = 'ast.ID =' . $asmtID;
            $result = $this->db->executeStoreProcedure('AssessmentList', array(
                "-1", "-1", "-1", "-1",
                $condition,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID'), "-1"
                    ), "nocount");


            Site::myDebug('------AssessmentDetails');
            Site::myDebug($result);
            $usr = new User();
            $Metadata = new Metadata();
            $details = array(
                "AsmtID" => $asmtID,
                "AsmtName" => $this->getValueArray($result, "Name"),
                "AsmtTitle" => $this->getValueArray($result, "Title"),
                "AsmtInfo" => $this->getValueArray($result, "AssessmentInfo"),
                "AccessMode" => $this->getValueArray($result, "AccessMode"),
                "Tag" => $this->getValueArray($result, "Tag"),
                "Taxonomy" => $this->getValueArray($result, "Taxonomy"),
                'AllTag' => $this->getValueArray($result, 'AllTag'),
                'AllTaxonomy' => $this->getValueArray($result, 'AllTaxonomy'),
                "AsmtUsers" => $this->getValueArray($result, "AssessmentUsers"),
                "AsmtTemplates" => $this->getValueArray($result, "AssessmentTemplates"),
                "Mode" => $this->getValueArray($result, "Mode"),
                "Type" => $this->getValueArray($result, "Type"),
                "QuestionAutoUpdate" => $this->getValueArray($result, "QuestionAutoUpdate"),
                "SpecificRights" => $usr->getMapEntityRightDetails($asmtID, 2, $this->session->getValue('userID')),
                "nocount"
            );
            $details = $this->getAssessmentSettings($result, $details);
        }

        $data_val = array(0, $this->session->getValue('userID'), 2, $asmtID, $details['AsmtName'], 'Viewed', $this->currentDate(), 1, $this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);

        return $details;
    }

    /**
     * Get Assessment details.
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    mixed  	$asmtID
     * @return   array         $details
     *
     */
    function asmtDetail($asmtID = "-1") {
        if (empty($details)) {
            $result = $this->db->executeStoreProcedure('AssessmentDetails', array(
                $asmtID,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID')
                    ), "nocount");
            Site::myDebug('------AssessmentDetails');
            Site::myDebug($result);
            $usr = new User();
            $Metadata = new Metadata();
            $details = array(
                "AsmtID" => $asmtID,
                "AsmtName" => $this->getValueArray($result, "Name"),
                "AsmtTitle" => $this->getValueArray($result, "Title"),
                "AsmtInfo" => $this->getValueArray($result, "AssessmentInfo"),
                "AccessMode" => $this->getValueArray($result, "AccessMode"),
                "Tag" => $this->getValueArray($result, "Tag"),
                "Taxonomy" => $this->getValueArray($result, "Taxonomy"),
                'AllTag' => $this->getValueArray($result, 'AllTag'),
                'AllTaxonomy' => $this->getValueArray($result, 'AllTaxonomy'),
                "AsmtUsers" => $this->getValueArray($result, "AssessmentUsers"),
                "AsmtTemplates" => $this->getValueArray($result, "AssessmentTemplates"),
                "Mode" => $this->getValueArray($result, "Mode"),
                "Type" => $this->getValueArray($result, "Type"),
                "QuestionAutoUpdate" => $this->getValueArray($result, "QuestionAutoUpdate"),
                "SpecificRights" => $usr->getMapEntityRightDetails($asmtID, 2, $this->session->getValue('userID')),
                "nocount",
                "AsmtShortName" => $this->getValueArray($result, "ShortName")
            );
            $details = $this->getAssessmentSettings($result, $details);
        }

        $data_val = array(0, $this->session->getValue('userID'), 2, $asmtID, $details['AsmtName'], 'Viewed', $this->currentDate(), 1, $this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);

        return $details;
    }

    /**
     * Get Assessment Settings
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$arrSettings
     * @param    array  	$details
     * @return   array         $details
     *
     */
    function getAssessmentSettings($arrSettings, $details) {

        if (!empty($arrSettings)) {
            foreach ($arrSettings as $setting) {
                if (strpos($setting['SettingID'], "Setting_") > -1) {
                    $details[$setting['SettingID']] = $setting['SettingValue'];
                }
            }
        }
        return $details;
    }

    /*
     * @METHOD  : Return Total Question On Assessment
     * @param   : assessmentID
     *  
     */

    function getQuestionsCountInAssessment($assessmentID) {
        $res = $this->db->getSingleRow(" SELECT COUNT FROM `Assessments` WHERE ID  = " . $assessmentID . " ");
        $res2 = $this->db->getRows("SELECT ID FROM `MapRepositoryQuestions` WHERE SectionName!='' AND isEnabled=1 AND EntityID =" . $assessmentID);
        return ($res['COUNT'] + (int) count($res2));
    }

    /**
     * Get Question List for an Assessment
     *  PAI02::Sprint5:Fetch SectionInfo
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    integer  	$asmtID
     * @param    string  	$filter
     * @return   array         $questionlist
     *
     */
    function questionList(array $input, $asmtID, $filter = "-1") {
        Site::myDebug('------mydebugfilter');
        Site::myDebug($filter);

        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgndc'] = " ( CASE  WHEN  qtp.\"ID\" IN (37,38) THEN  ''quest-editor-ltd'' ELSE ''quest-editor''  END ) as \"EditPage\", mrq.\"SectionName\",  mrq.\"ParentID\"   ";
        } else {   // if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' )
            $input['pgndc'] = "'quest-editor' as EditPage, mrq.SectionName,mrq.ParentID, mrq.Sequence , mrq.EditStatus , qtp.isStatic , qtp.TemplateGroup ,qtp.TemplateTitle,qtp.RenditionMode, qst.Count, qst.AuthoringStatus, qst.AuthoringUserID ";
        }

        if ($filter == "-1") {
            $questionlist = $this->db->executeStoreProcedure('QuestionList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $asmtID, "2", //this is an entity type id
                $this->session->getValue('userID'), $input['pgndc']));
            //$questionlist =array();
            //$questionlist['RS'] = $questionlistRet;    
            $questionlist['TC'] = $this->getQuestionsCountInAssessment($asmtID);
        } else {
            $questionlist = $this->db->executeStoreProcedure('QuestionList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $asmtID, "2", //this is an entity type id
                $this->session->getValue('userID'), $input['pgndc']));
            //$questionlist   =array();     
            //$questionlist['RS'] = $questionlistRet;    
        }



        $qtp = new QuestionTemplate();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;

        if (!empty($questionlist['RS'])) {
            foreach ($questionlist['RS'] as $question) {
                $questionlist['RS'][$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["qtID"]);
                $i++;
            }
        }

        return $questionlist;
    }

    /**
     * Search Question as per user defined criteria
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    integer  	$asmtID
     * @param    string  	$filter
     * @param    string(JSON)  $tag
     * @param    string(JSON)  $taxonomy
     * @return   array         $questionlist
     *
     */
    function questionSearchList(array $input, $asmtID, $filter, $tag, $taxonomy) {
        global $DBCONFIG;
        Site::myDebug('------inputquestlist');
        Site::myDebug($input);
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $input['jsoncrieteria'] = utf8_encode($input['jsoncrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst.Count' : $input['pgndc'] . ',qst.Count';
        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tag;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomy;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        $value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['ownerName'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['ownerName'];
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];
        $search = ($search == '') ? -1 : str_replace("'", "''", $search);
        $owner = ($owner == '') ? -1 : $owner;

        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';

        $input['pgnob'] = '-1';
        if ($DBCONFIG->dbType == 'Oracle')
            $input['pgndc'] = "( CASE  WHEN  qtp.\"ID\" IN (37,38) THEN  ''quest-editor-ltd'' ELSE ''quest-editor''  END ) as \"EditPage\", mrq.\"SectionName\", mrq.\"ParentID\", mrq.\"Sequence\" , mrq.\"EditStatus\" , qtp.\"isStatic\" ,qst.\"Count\",  qst.\"AuthoringStatus\", qst.\"AuthoringUserID\" ";
        else
            $input['pgndc'] = "if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' )  as EditPage, mrq.SectionName, mrq.ParentID, mrq.Sequence , mrq.EditStatus , qtp.isStatic ,qst.Count,  qst.AuthoringStatus, qst.AuthoringUserID ";

        $questionlist = $this->db->executeStoreProcedure('QuestionSearchList', array($input['pgnob'],
            $input['pgnot'],
            "-1",
            "-1",
            $search,
            $asmtID,
            "2", //this is an entity type id
            $this->session->getValue('userID'),
            $input['pgndc'],
            $tags,
            $taxo,
            $owner,
            $this->session->getValue('instID'),
            $key,
            $value,
            $difficulty, $template, $startdate, $enddate, $searchtype,
            $title_filter, $users_filter, $templates_filter, $date_filter, $difficulty_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter
        ));
//        $questionlist['TC'] = $this->getValueArray($questionlist['RS'], "@QuestionIDCount");
//		unset($questionlist['RS'][$input['pgnstop']]);
        $qtp = new QuestionTemplate();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;
        if (!empty($questionlist['RS'])) {
            foreach ($questionlist['RS'] as $question) {
                $questionlist['RS'][$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
                $i++;
            }
        }

        $input['entityid'] = $asmtID;
        $input['entitytypeid'] = 2;
        $input['spcall'] = $questionlist['QR'];
        //$input['count']         = $questionlist['TC'];
        $questionlist['TC'] = count($questionlist['RS']); // Getting the exact count for the search result
        if (trim($input['hdn_searchcrieteria']) != '') {
            $this->saveAdvSearchCrieteria($input);
        }
        Site::myDebug('------mydebug');
        Site::myDebug($questionlist);
        return $questionlist;
    }

    /**
     * Update Question Order as per the sorting done by user
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @return   string
     *
     */
    public function updateSort(array $input) {
        global $DBCONFIG;

        $quizid = $input['quizid'];
        $listcontain = $input['listcontain'];
        $record = (int) $input['record'];

        if ($this->session->getValue('isAdmin')) {
            $query = "SELECT a.AssessmentName, a.ID FROM Assessments a  WHERE a.isEnabled = '1' AND a.ID = $quizid";
        } else {
            $query = "SELECT a.AssessmentName, a.ID FROM Assessments a , RepositoryMembers rm WHERE  a.isEnabled = '1' AND rm.isEnabled = '1' AND rm.UserID = '{$this->session->getValue('userID')}' and rm.EntityTypeID='2'  AND rm.EntityID = a.ID AND a.ID = $quizid";
        }

        if ($this->db->getCount($query) > 0) {
            $ilist = explode("|", $listcontain);
            $p = $record + 1;

            for ($i = 0; $i < sizeof($ilist); $i++) {
                if (strstr($ilist[$i], "-")) {
                    $jlist = explode("-", $ilist[$i]);
                    for ($j = 0; $j < sizeof($jlist); $j++) {
                        if ($j == 0) {
                            $sqlUpdate = "UPDATE MapRepositoryQuestions mrq SET mrq.Sequence='$p' WHERE mrq.ID='$jlist[0]' and mrq.EntityTypeID ='2' and mrq.isEnabled='1' ";
                            $this->db->execute($sqlUpdate);
                            $p++;
                        } else {
                            /* if parent id is not a section then DO no Update */
                            /* echo 'in if section '. */
                            $query_pchk = "SELECT mrq.QuestionID, mrq.QuestionTemplateID FROM MapRepositoryQuestions mrq	WHERE mrq.ID = '$jlist[0]' and mrq.QuestionID = '0' and mrq.QuestionTemplateID = '0'";
                            if ($this->db->getCount($query_pchk) > 0) {
                                $sqlUpdateSeq = "UPDATE MapRepositoryQuestions mrq  SET mrq.Sequence='$p',mrq.ParentID='$jlist[0]' WHERE mrq.ID='$jlist[$j]' and mrq.EntityTypeID ='2'  and mrq.isEnabled='1' ";
                                $this->db->execute($sqlUpdateSeq);
                                $p++;
                            } else {
                                return "update failed";
                            }
                        }
                    }
                } else {
                    $sqlUpdate = "UPDATE MapRepositoryQuestions mrq  SET mrq.Sequence='$p',mrq.ParentID='0' WHERE mrq.ID='$ilist[$i]' and mrq.EntityTypeID ='2'  and mrq.isEnabled='1' ";
                    $this->db->execute($sqlUpdate);
                    $p++;
                }
            }
            return "update success";
        } else {
            return "update failed";
        }
    }

    /**
     * Manage Assessment Section (Add, Edit, Delete)
     *
     * PAI02 Sprint5
     * Add new field for Section info
     * @access   public
     * @abstract
     * @static
     * @global	  object  	$DBCONFIG
     * @param    integer  	$asmtID
     * @param    integer  	$sectionid
     * @param    string  	$sectionname
     * @param    string  	$sectionop
     * @param    string  	$deleteop
     * @return   string        $returnValue
     *
     */
    public function section($asmtID, $sectionid, $sectionname, $sectionop = "", $deleteop = "", $sectionInfo) {

        global $DBCONFIG;
        Site::myDebug('----------section ---Question Delete ');
        Site::myDebug('$asmtID::' . $asmtID . '------$sectionid::' . $sectionid . '-------$sectionname::' . $sectionname . '----$sectionop::' . $sectionop . '------$deleteop' . $deleteop);
        if ($asmtID == 0) {
            $title = $this->registry->site->getEntityTitle(3, $sectionid);
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $query = "SELECT  qs.\"Title\" as \"EntityTitle\" , mr.ID as \"rid\"
                                from Questions qs
                                    inner join MapRepositoryQuestions mr on mr.\"EntityID\" ={$asmtID} and mr.\"EntityTypeID\" = 2
                                            and mr.\"QuestionID\" = qs.ID and mr.\"ParentID\" ={$sectionid} and  mr.\"isEnabled\" ='1'
                                    where qs.\"isEnabled\" ='1'";
            } else {
                $query = "SELECT  qs.Title as EntityTitle , mr.ID as rid from Questions qs
                            inner join MapRepositoryQuestions mr on mr.EntityID={$asmtID} and mr.EntityTypeID=2 and mr.QuestionID=qs.ID and mr.ParentID={$sectionid} and  mr.isEnabled='1'
                            where qs.isEnabled='1'";
            }

            $rt = $this->db->getRows($query);
        }

        $result = $this->db->executeStoreProcedure('AssessmentSectionManage', array($asmtID,
            2,
            $sectionid,
            $sectionname,
            $sectionInfo,
            $sectionop,
            $this->session->getValue('userID'),
            $this->currentDate(),
            $this->session->getValue('userID'),
            $this->currentDate(),
            $deleteop
                ), 'nocount');
        $result1 = $result[0];

        /* ====== Asset Counter Manage Start  ======== */
        $mediaModel = new Media();
        $mediaModel->assetUsageCounterManage($sectionid);

        /* ============Classification Usage Count Decrease ================ */
        $questionRepoid = $sectionid;
        $this->decreaseClassificationCount($questionRepoid, 3);
        /* ================================================================ */


        if ($result1["Status"] == "Section Deleted" && $deleteop != "MOVQST") {
            if ($asmtID == 0) {
                $data_val = array('', $this->session->getValue('userID'), 3, $sectionid, $title, 'Deleted', $this->currentDate(), 1, $this->session->getValue('accessLogID'));
                $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
            } else {
                if (!empty($rt)) {
                    foreach ($rt as $rt1) {
                        $title = $rt1['EntityTitle'];
                        $rid = $rt1['rid'];
                        $data_val = array('', $this->session->getValue('userID'), 3, $rid, $title, 'Deleted', $this->currentDate(), '1', $this->session->getValue('accessLogID'));
                        $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
                    }
                }
            }
        }
        $returnValue = (trim($result1["Status"]) == "Section Added") ? $result[1]["ID"] : $result1["Status"];
        return $returnValue;
    }

    /**
     * Get Assessment Section count
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @return   integer
     *
     */
    function sectionHtml(array $input) {
        global $DBCONFIG;
        $qry = "select * from MapRepositoryQuestions where ID={$input['rid']}";
        $result = $this->db->getRows($qry);
        if ($DBCONFIG->dbType == 'Oracle') {
            $qry1 = "select * from MapRepositoryQuestions where \"EntityTypeID\" =2 and \"EntityID\" ={$input['quizid']} and \"SectionName\" !='''' and \"isEnabled\" = '1' ";
        } else {
            $qry1 = "select * from MapRepositoryQuestions where EntityTypeID=2 and EntityID={$input['quizid']} and SectionName !='' and isEnabled='1' ";
        }

        $result1 = $this->db->getRows($qry1);
        $result[0]['totalSec'] = count($result1);
        return $result[0];
    }

    /**
     * Get Section List for an Assessment
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$asmtID
     * @return   array         $result
     *
     */
    function sectionList($asmtID) {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $result = $this->db->executeStoreProcedure('QuestionList', array("-1",
                "-1",
                "-1",
                "-1",
                " mrq.\"SectionName\"  IS NOT NULL  ",
                $asmtID,
                "2", //this is an entity type id
                $this->session->getValue('userID'),
                " mrq.\"SectionName\" "
                    ), 'nocount');
        } else {
            $result = $this->db->executeStoreProcedure('QuestionList', array("-1",
                "-1",
                "-1",
                "-1",
                "mrq.SectionName !='' group by mrq.SectionName ",
                $asmtID,
                "2", //this is an entity type id
                $this->session->getValue('userID'),
                "mrq.SectionName"
                    ), 'nocount');
        }

        return $result;
    }

    /**
     * Get All Assessment of an institute
     *    
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   array         $assessmentList
     *
     */
    function assessmentAllList() {
        $assessmentList = $this->db->executeStoreProcedure('AssessmentList', array(-1, -1, -1, -1, -1, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), -1), 'nocount');
        return $assessmentList;
    }

    /*
     *  Description :- Decrease Tag Count When Assessment , Bank , Question Delete 
     *  Author      :- Akhlack
     *  Create Date :- 29 October,2015    
     *  
     */

    public function decreaseClassificationCount($entityId, $entityTypeId) {
        $dataArray = array($entityId, $entityTypeId);
        $this->db->executeStoreProcedure('ClassificationDeleted', $dataArray);
    }

    /**
     * Delete Assessment
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$assessmentID
     * @return   mixed
     *
     */
    public function delete($assessmentID) {
        global $DBCONFIG;
        $assessmentIDs = $this->removeBlankElements($assessmentID);
        $entityTypeId = $this->registry->site->getEntityId('Assessment');
        $assessmentID = implode(',', (array) $this->removeUnAccessEnities('AsmtDelete', $entityTypeId, $assessmentIDs));

        if (!empty($assessmentID)) {
            if ($DBCONFIG->dbType == 'Oracle') {
                $query = "  UPDATE Assessments SET \"isEnabled\" = '0' WHERE ID IN($assessmentID) ";
            } else {
                $query = "  UPDATE Assessments SET isEnabled = '0' WHERE ID IN($assessmentID) ";
            }

            /* To avoid colllision with Entity function of database we are calling execute and not executeStoreProcedure.
             * by Nanda.
             */

            $astarr = $this->db->executeFunction('ENTITYTITLE', 'assessmentName', array($assessmentID, '2'));
            $data_val = array(
                0,
                $this->session->getValue('userID'),
                2,
                $assessmentID,
                $astarr['assessmentName'],
                'Deleted',
                $this->currentDate(),
                1, $this->session->getValue('accessLogID')
            );
            $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
            $val = $this->db->execute($query);
            //print_r($query); echo "<br/>"; print_r($val); die('inside delete');
            if ($val) {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $qry = "update MapRepositoryQuestions set \"isEnabled\" = '0' where  \"EntityTypeID\" =2 and \"EntityID\"  IN ({$assessmentID})";
                } else {
                    $qry = "update MapRepositoryQuestions set isEnabled = '0' where  EntityTypeID=2 and EntityID IN ({$assessmentID})";
                }

                $this->db->execute($qry);
            }

            //  $this->decreaseTagCount($assessmentID,'2');
            $this->decreaseClassificationCount($assessmentID, '2');

            return $val;
        } else {
            return false;
        }
    }

    /**
     * Get Total Assessment( Active, Published, All) Count
     *
     * @access   public
     * @abstract
     * @static
     * @global   object        $DBCONFIG
     * @param    string  	$case
     * @param    string  	$condition
     * @return   integer
     * @deprecated
     */
    function getTotalAssessmentsCount($case, $condition = '') {
        global $DBCONFIG;
        $condition = ($condition != '') ? $condition : '';
        switch ($case) {
            case 'Active':
                $query = "  SELECT ID FROM Assessments a WHERE isEnabled = '1' AND a.Status != 'Archive' $condition ";
                break;

            case 'Published':
                $query = "  SELECT distinct ID FROM Assessments a where  a.isEnabled='1'  AND a.Status = 'Published' $condition ";
                break;

            case 'All':
                $query = "  SELECT a.ID FROM Assessments a WHERE a.Status != 'Archive' $condition ";
                break;
        }
        return $this->db->getCount($query);
    }

    /**
     * Get Assessment(Active, Published, All ) Count of yesterday
     *
     * @access   public
     * @abstract
     * @static
     * @global   object        $DBCONFIG
     * @param    string  	$case
     * @return   integer
     * @deprecated
     *
     */
    function getYesterdaysAssessmentsCount($case) {
        global $DBCONFIG;
        switch ($case) {
            case 'Active':
                $query = "SELECT ID FROM Assessments a WHERE isEnabled = '1' AND DATE_SUB(AddDate,INTERVAL 1 DAY) <= CURDATE() AND a.Status != 'Archive' ";
                break;

            case 'Published':
                $query = "SELECT distinct ID FROM Assessments a where  a.isEnabled='1' AND DATE_SUB(AddDate,INTERVAL 1 DAY) <= CURDATE() AND a.Status = 'Published' ";
                break;

            case 'All':
                $query = "SELECT ID FROM Assessments a WHERE DATE_SUB(AddDate,INTERVAL 1 DAY) <= CURDATE() AND a.Status != 'Archive' ";
                break;
        }
        return $this->db->getCount($query);
    }

    /**
     * Get Assessment(Active, Published, All ) Count per Month
     *
     * @access   public
     * @abstract
     * @static
     * @global   object        $DBCONFIG
     * @param    string  	$case
     * @return   integer
     * @deprecated
     */
    function getMonthsAssessmentsCount($case) {
        global $DBCONFIG;
        switch ($case) {
            case 'Active':
                $query = "  SELECT ID,AddDate FROM Assessments a WHERE isEnabled = '1' AND DATE_SUB(AddDate,INTERVAL 1 MONTH) <= CURDATE() AND a.Status != 'Archive' ";
                break;

            case 'Published':
                $query = "  SELECT distinct a.ID FROM Assessments a where a.isEnabled='1' AND DATE_SUB(AddDate,INTERVAL 1 MONTH) <= CURDATE() AND a.Status = 'Published' ";
                break;

            case 'All':
                $query = "  SELECT ID FROM Assessments a WHERE DATE_SUB(AddDate,INTERVAL 1 MONTH) <= CURDATE() AND a.Status != 'Archive' ";
                break;
        }
        return $this->db->getCount($query);
    }

    /**
     * Get Average Questions per Assessments Count
     *
     * @access   public
     * @abstract
     * @static
     * @global   object        $DBCONFIG
     * @param    integer  	$instID
     * @return   integer       $result['averageCount']
     * @deprecated
     *
     */
    function getAvgQuestionsPerAssessmentsCount($instID = '') {
        global $DBCONFIG;
        $condition = ($instID != '') ? " AND c.ID = $instID " : '';
        $query = "  SELECT CEIL(COUNT(a.ID)/COUNT(distinct a.ID)) as averageCount
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID=mrq.EntityID
                            LEFT JOIN Questions que on mrq.QuestionID=que.ID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND mrq.isEnabled='1' AND mcu.isEnabled = '1' AND que.isEnabled='1' $condition
                         ";
        $result = $this->db->getSingleRow($query);
        return $result['averageCount'];
    }

    /**
     * Get Assessment List as per criteria
     *    
     * @access     public
     * @abstract
     * @static
     * @global     object      $APPCONFIG
     * @param      string      $orderColumn
     * @param      integer  	$clientID
     * @param      string  	$case
     * @return     array
     * @deprecated
     *
     */
    function getAssessments($orderColumn, $clientID = '', $case) {
        global $APPCONFIG;
        $condition = ($clientID != '') ? " AND c.ID = $clientID " : '';

        $this->getQueryParam(10);
        switch ($case) {
            case 'puball':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1'
                            AND a.Status = 'Published' AND mcu.isEnabled = '1'
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'actall':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1'
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'all':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE u.isEnabled='1'
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'pubmnth':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1' AND mcu.isEnabled = '1'
                            AND a.Status = 'Published'
                            DATE_SUB(a.AddDate,INTERVAL 1 MONTH) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'actmnth':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1'
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            DATE_SUB(a.AddDate,INTERVAL 1 MONTH) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'allmnth':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE u.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            DATE_SUB(a.AddDate,INTERVAL 1 MONTH) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'pubday':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1'
                            AND a.Status = 'Published' AND mcu.isEnabled = '1'
                            DATE_SUB(a.AddDate,INTERVAL 1 DAY) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'actday':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID, OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE a.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND u.isEnabled='1'
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            DATE_SUB(a.AddDate,INTERVAL 1 DAY) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;

            case 'allday':
                $query = "  SELECT SQL_CALC_FOUND_ROWS distinct a.ID,a.AssessmentName,count(mrq.EntityID) AS questionCount,a.AssessmentInfo,a.AddDate,a.Status,a.isEnabled,concat_ws(' ',FirstName,LastName) as userName, u.ID as userID , OrganizationName
                            FROM Assessments a left join MapRepositoryQuestions mrq on a.ID = mrq.EntityID
                            INNER JOIN Users u on  a.UserID = u.ID
                            INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                            INNER JOIN Clients c on mcu.ClientID = c.ID
                            WHERE u.isEnabled='1' AND mrq.EntityTypeID = EntityID('Assessment')
                            AND a.Status != 'Archive' AND mcu.isEnabled = '1'
                            DATE_SUB(a.AddDate,INTERVAL 1 DAY) <= CURDATE()
                            $condition
                            GROUP BY a.ID
                         ";
                break;
        }
        $query .= $this->orderBy . " " . $this->limit;
        return $this->db->getRows($query);
    }

    /**
     * Set Order column    
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   string        $orderColumn
     *
     */
    function setOrderColumn() {
        switch ($this->getInput('orderBy')) {
            case 'user':
                $orderColumn = 'userName';
                break;

            case 'institute':
                $orderColumn = 'OrganizationName';
                break;

            case 'date':
                $orderColumn = 'a.AddDate';
                break;

            case 'udate':
                $orderColumn = 'u.AddDate';
                break;

            case 'idate':
                $orderColumn = 'c.AddDate';
                break;

            case 'qname':
                $orderColumn = 'q.Title';
                break;

            case 'qcount':
                $orderColumn = 'questionCount';
                break;

            case 'bname':
                $orderColumn = 'b.BankName';
                break;

            case 'tname':
                $orderColumn = 't.Tag';
                break;

            case 'txname':
                $orderColumn = 't.Taxonomy';
                break;

            case 'ustatus':
                $orderColumn = 'u.isEnabled';
                break;

            case 'role':
                $orderColumn = 'RoleName';
                break;

            default:
                $orderColumn = 'a.AssessmentName';
                break;
        }
        return $orderColumn;
    }

    /**
     * Get Assessment Count as per criteria
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   integer       $count
     *
     */
    function getAssessmentsCount() {
        $count = 0;
        switch ($this->getInput('prm')) {
            case 'puball':
                $count = $this->getTotalAssessmentsCount('Published');
                break;

            case 'actall':
                $count = $this->getTotalAssessmentsCount('Active');
                break;

            case 'all':
                $count = $this->getTotalAssessmentsCount('All');
                break;

            case 'pubmnth':
                $count = $this->getMonthsAssessmentsCount('Published');
                break;

            case 'actmnth':
                $count = $this->getMonthsAssessmentsCount('Active');
                break;

            case 'allmnth':
                $count = $this->getMonthsAssessmentsCount('All');
                break;

            case 'pubday':
                $count = $this->getYesterdaysAssessmentsCount('Published');
                break;

            case 'actday':
                $count = $this->getYesterdaysAssessmentsCount('Active');
                break;

            case 'allday':
                $count = $this->getYesterdaysAssessmentsCount('All');
                break;
        }
        return $count;
    }

    /**
     * Disable Assessment
     *
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param    integer  	$quizID
     * @return   boolean
     *
     */
    function disableAssessment($quizID) {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE Assessments set \"isEnabled\" = '0' where ID = $quizID ";
        } else {
            $query = "UPDATE Assessments set isEnabled = '0' where ID = $quizID ";
        }

        return $this->db->execute($query);
    }

    /**
     * Enable Assessment
     *
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param    integer  	$quizID
     * @return   boolean
     *
     */
    function enableAssessment($quizID) {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE Assessments set \"isEnabled\" = '1' where ID = $quizID ";
        } else {
            $query = "UPDATE Assessments set isEnabled = '1' where ID = $quizID ";
        }

        return $this->db->execute($query);
    }

    /**
     * Get Question list for an Assessment
     *    
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @global   object        $APPCONFIG
     * @param    integer  	$quizID
     * @param    string  	$orderColumn
     * @return   array         Assessment List
     * @deprecated
     *
     */
    function getAssessmentQuestions($quizID, $orderColumn) {
        global $DBCONFIG, $APPCONFIG;
        $query = "SELECT a.AssessmentName, CONCAT_WS(' ',FirstName,LastName) as UserName, OrganizationName, a.ID , a.AddDate, mrq.SectionName, qt.TemplateTitle,  qt.TemplateFile
                        FROM  MapRepositoryQuestions  mrq
                        INNER JOIN Assessments a ON  a.ID = mrq.EntityID AND a.isEnabled = '1'
                        LEFT JOIN MapQuestionTemplates mqt ON  a.ID = mqt.EntityID AND mqt.isEnabled = '1'
                        INNER JOIN MapClientQuestionTemplates mcqt ON mcqt.ID = mrq.QuestionTemplateID AND mcqt.QuestionTemplateID =  mqt.QuestionTemplateID and mcqt.isEnabled = '1' AND mcqt.isActive = 'Y'
                        LEFT JOIN QuestionTemplates qt ON qt.ID = mcqt.QuestionTemplateID  AND qt.isEnabled = '1'
                        LEFT JOIN Users u ON a.UserID = u.ID AND u.isEnabled='1'
                        LEFT JOIN MapClientUser mcu ON u.ID = mcu.UserID AND mcu.isEnabled='1'
                        LEFT JOIN Clients c ON mcu.ClientID = c.ID AND c.isEnabled='1'
                        WHERE mrq.EntityID = '$quizID'  AND mrq.isEnabled = '1' AND mrq.EntityTypeID = EntityID('Assessment') AND mcu.isEnabled = '1'
                        GROUP BY mrq.ID ";
        return $this->db->getRows($query);
    }

    /**
     * Disable Question
     * 
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param    integer  	$questID
     * @return   boolean
     * @Depricated
     */
    function disableQuestion($questID) {
        global $DBCONFIG;
        $query = "UPDATE Questions set isEnabled = '0' where ID = $questID ";
        return $this->db->execute($query);
    }

    /**
     * Get Assessment details
     *    
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param    integer  	$quizID
     * @return   array
     *
     */
    function getInfo($quizID) {
        global $DBCONFIG;
        $query = "SELECT * FROM Assessments where ID = $quizID ";
        return $this->db->getSingleRow($query);
    }

    /**
     * Get All Assessment List
     *
     * @access     public
     * @abstract
     * @static
     * @global     array         $DBCONFIG
     * @param
     * @return     array         Assessment List
     * @deprecated
     *
     */
    function getAllAssessments() {
        global $DBCONFIG;
        $query = "  SELECT distinct c.OrganizationName as Name, c.ID
                        FROM Assessments a
                        INNER JOIN MapClientUser mcu ON mcu.UserID = a.UserID  AND mcu.isEnabled = '1'
                        INNER JOIN Clients c ON mcu.ClientID = c.ID  AND c.isEnabled = '1'
                        WHERE a.isEnabled='1' AND a.Status != 'Archive'
                        ORDER BY Name   ";
        return $this->db->getRows($query);
    }

    /**
     * Get Assessment published reports on the basis of time.
     *
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param
     * @return   array         Assessment List
     * @deprecated
     *
     */
    function getReports() {
        global $DBCONFIG;
        $condition = ($this->getInput('filter') != 'all' || $this->getInput('filter') > 0) ? " AND c.ID = {$this->getInput('filter')}" : '';
        switch ($this->getInput('periodicity')) {
            case 'yearly':
                $dateFormat = '%Y';
                break;

            case 'monthly':
                $dateFormat = '%b %Y';
                break;

            default:
                $dateFormat = '%d %b %Y';
                break;
        }
        $query = "  SELECT count(a.ID) as count,DATE_FORMAT(a.AddDate,'$dateFormat') as addedDate FROM Assessments a
                        INNER JOIN MapClientUser mcu ON mcu.UserID = a.UserID  AND mcu.isEnabled = '1'
                        INNER JOIN Clients c ON mcu.ClientID = c.ID  AND c.isEnabled = '1'
                        WHERE a.isEnabled = '1' AND a.Status != 'Archive'
                        AND a.AddDate between UNIX_TIMESTAMP(STR_TO_DATE('{$this->getInput('mbrstartdate')}','%d %M, %Y'))
                        AND UNIX_TIMESTAMP(STR_TO_DATE('{$this->getInput('mbrenddate')}','%d %M, %Y')) $condition GROUP BY addedDate ORDER BY a.AddDate
                        ";
        return $this->db->getRows($query);
    }

    /**
     * Get count of Assessment, Archived in last week
     *
     * @access   public
     * @abstract
     * @static
     * @global   array         $DBCONFIG
     * @param
     * @return   integer
     * @deprecated
     *   
     */
    function getLastWeekActCount() {
        global $DBCONFIG;
        $query = "  SELECT count(a.ID) as count FROM Assessments a
                        INNER JOIN MapClientUser mcu ON mcu.UserID = a.UserID  AND mcu.isEnabled = '1'
                        INNER JOIN Clients c ON mcu.ClientID = c.ID  AND c.isEnabled = '1'
                        WHERE a.isEnabled = '1' AND a.Status != 'Archive'
                        AND a.AddDate BETWEEN DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND CURDATE()
                     ";
        $result = $this->db->getSingleRow($query);
        return $result['count'];
    }

    /**
     * Get count of Assessment, Published in last week
     *
     * @access     public
     * @abstract
     * @static
     * @global     object      $DBCONFIG
     * @param
     * @return     integer
     * @deprecated
     *
     */
    function getLastWeekPubCount() {
        global $DBCONFIG;
        $query = "  SELECT count(a.ID) as count FROM Assessments a
                        INNER JOIN MapClientUser mcu ON mcu.UserID = a.UserID  AND mcu.isEnabled = '1'
                        INNER JOIN Clients c ON mcu.ClientID = c.ID  AND c.isEnabled = '1'
                        WHERE a.isEnabled = '1' AND a.Status = 'Published'
                        AND a.AddDate BETWEEN DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND CURDATE()
                     ";
        $result = $this->db->getSingleRow($query);
        return $result['count'];
    }

    /**
     * Get Assessment List for Admin
     *
     * @access     public
     * @abstract
     * @static
     * @global
     * @param      string  	$condition
     * @return     mixed
     * @deprecated
     *
     */
    function adminAssessmentList($condition = '') {
        $this->setPaginationParam($this->getSettingVal('AssessmentRPP'));
        $condition = ($condition != '') ? $condition : '-1';
        $asmtCount = $ast->getAssessmentsCount();
        if ($asmtCount > 0) {
            $this->registry->template->asmtCount = $asmtCount;
            $asmtLimitList = $ast->getAssessments($orderColumn, $instID);
            return $assessmentList;
        } else {
            return false;
        }
    }

    /**
     * Imports data from XML file into the system
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   string
     *
     */
    function uploadQtiXml() {
        $guid = uniqid("qti");

        $target_path_dir = $this->cfg->rootPath . "/" . $this->cfgApp->QTIFileLocation . "" . $this->session->getValue('userID');
        $target_path_zip = "{$target_path_dir}/" . $guid . ".xml"; //....because we decided to allow user to upload the same multiple times.

        if (!is_dir($target_path_dir)) {
            mkdir($target_path_dir, 0777);
        }

        if (file_exists($target_path_zip)) {   ///this condition is irrelevant if we are allowing user to upload same file twice..still has been left uncommented just in case we need it in future...
            $error = "File [" . basename($_FILES['uploadQTI']['name']) . "] has already been uploaded.";
            $mesg = "";
            echo "{";
            echo "error: '" . $error . "',";
            echo "msg: '" . $mesg . "',";
            echo "file: ''";
            echo "}";
        } else {
            $fileuploaderror = $_FILES['uploadQTI']['error'];
            $sourcefile = $_FILES['uploadQTI']['tmp_name'];
            $targetpath = $target_path_zip;
            if (!empty($fileuploaderror)) {
                switch ($fileuploaderror) {
                    case '1':
                        $error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
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
                $status = move_uploaded_file($sourcefile, $targetpath);
            }
            if ($status) {
                $mesg = "File has been successfully uploaded.<BR><BR>";
                echo "{";
                echo "error: '" . $error . "',";
                echo "msg: '" . $mesg . "',";
                echo "file: '" . $targetpath . "'";
                echo "}";
            }
        }
    }

    /**
     * Imports data from XML file into the system
     *
     * @access     public
     * @abstract
     * @static
     * @global
     * @param      array  	$input
     * @return     string
     *
     */
    function importQtiXml(array $input) {
        $qtifile = $input['qtifile'];
        $auth = new Authoring();
        $qst = new Question();
        $qtp = new QuestionTemplate();
        $TemplateIds = $qtp->questionTemplateIdString();
        $objJSONtmp = new Services_JSON();

        $questCntr = 0;
        $supportedFormats = "";
        $unsupportedFormats = "";

        if (file_exists($qtifile)) {
            $xmldata = simplexml_load_file($qtifile);
            $this->myDebug("Before parseQtiQuiz call" . date("M d, Y h:i:s A"));
            $quiz_arr = $auth->parseQtiQuiz($xmldata);
            $this->myDebug("After parseQtiQuiz call" . date("M d, Y h:i:s A"));
            $curdate = date("U");
            $entityName = $quiz_arr->info->title;
            //Creating New Assessment....with Title only and all Default Setting....
            $input["AssessmentName"] = $entityName;
            $quizID = $this->save($input);
            $quiz_url = $this->cfg->wwwroot . "/assessment/question-list/" . $quizID;
            $mesg = "<BR><a href='{$quiz_url}'>Assessment</a> has been created successfully.<BR>Proceeding with fetching Assessment Questions . ";
            echo $mesg;

            $questionCount = count($xmldata->assessment->section->item);

            $questionItems = $xmldata->xpath("assessment/section[1]/item[itemmetadata/qmd_itemtype='Multiple Choice' or itemmetadata/qmd_itemtype='Matching' ]");

            $questCntr = count($questionItems);
            if (!empty($questionItems)) {
                foreach ($questionItems as $q) {
                    $questTitle = addslashes($q->presentation->material->mattext);
                    $questType = $q->itemmetadata->qmd_itemtype;
                    $supportedFormats .= "{$questType},";
                    $qt = $auth->getQuadQuestionTypeId($questType);
                    $this->myDebug("XX: {$questType} | {$qt}");
                    $questJSON = $auth->getQtiQuestionJson($questType, $q);
                    //   $questJSON          = $qst->addMediaPlaceHolder($questJSON);
                    $sUserID = $this->session->getValue('userID');
                    $this->myDebug($questJSON);
                    if (!empty($questJSON)) {
                        $arrQuestion = array(
                            'Title' => $questTitle,
                            'XMLData' => 'NA',
                            'JSONData' => $questJSON,
                            'UserID' => $sUserID,
                            'QuestionTemplateID' => $qt,
                            'RID' => $quizID
                        );
                        $questid = $qst->newQuestSave($arrQuestion);

                        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
                            $questid,
                            $quizID,
                            2,
                            0, 'ADDQST', $sUserID,
                            $this->currentDate(), $sUserID,
                            $this->currentDate()), 'details');
                        $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
                        $qst->questionActivityTrack($repositoryid, "Added", $sUserID);
                    }
                }
            }
            $status = 1;
            $moreInfo = "";
            $supportedFormats = $auth->getSupportedFormats();
            $unsupportedFormats = $auth->getUnsupportedFormats();
            if ($status) {
                $mesg = "";
                $supportedFormats = $auth->getUniqueFormats($supportedFormats);
                $unsupportedFormats = $auth->getUniqueFormats($unsupportedFormats);
                $moreInfo .= "
                                        <div id=adtnInfo>
                                        <table width=450 border=0 cellspacing=2 cellpadding=1 align=center>
                                        <tr><td align=left width='70%' valign=top>Total Questions found in the Assessment:</td><td align=left class=boldtxt>{$questionCount}</td></tr>
                                        <tr><td align=left valign=top>Questions imported into the system:</td><td align=left class=boldtxt>{$questCntr}</td></tr>
                                        <tr><td align=left valign=top>Question Types supported:</td><td align=left class=boldtxt>{$supportedFormats}</td></tr>
                                        <tr id=lastrow><td align=left  valign=top>Question Types NOT supported currently:</td><td align=left class=boldtxt>{$unsupportedFormats}</td></tr>
                                        </table>
                                        </div>
                                        ";
                $error = "";
                $mesg = "
                                        Assessment has been successfully imported into QuAD<BR><BR>
                                        <a href='{$quiz_url}'>Click here</a> to view the Assessment details. <BR><BR>
                                        ";
                $mesg .= $moreInfo;
            }
            echo $mesg;
        } else {
            $error = "File does not exists.";
            $mesg = "";
            echo $error;
        }
    }

    /**
     * Get Quad Plus list for Published Assessment
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$pubID
     * @return   array         $getData    Quad Plus  List
     *
     */
    public function manageQpAsmtAccess($pubID) {
        $query = "SELECT mpaqp.ID AS MapPubAsmtID, mqqp.QuadID, mqqp.QuadPlusID as QPID, mpaqp.isActive AS mapStatus,
                    qpi.QPName, qpi.isDefault,
                    mpaqp.PublishAsmtID
                    FROM MapClientUser as mcu
                    INNER JOIN  MapQuadQuadPlus as mqqp   ON mqqp.QuadID = mcu.ID
                    LEFT JOIN QuadPlusInfo as qpi ON qpi.ID = mqqp.QuadPlusID
                    LEFT JOIN  MapPublishAsmtQuadPlus as mpaqp  ON  mpaqp.QuadPlusID = qpi.ID AND mpaqp.PublishAsmtID = '{$pubID}' AND mpaqp.isEnabled = '1'
                    WHERE mcu.isEnabled = '1'
                    AND mcu.ClientID  = '{$this->session->getValue('instID')}' AND qpi.isEnabled = '1'  ";
        Site::myDebug($query);
        return $getData = $this->db->getRows($query);
    }

    /**
     * Assign or UnAssign Published Assessment to Quad Plus
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$pubID
     * @param    string  	$selQPID
     * @return   boolean
     *
     */
    public function addAsmtAccessQuadPlus($pubID, $selQPID) {
        $selQPID = str_replace("||", ",", $selQPID);
        $selQPID = trim($selQPID, "|");
        $arrQPID = @explode(",", $selQPID);

        if (!empty($arrQPID)) {
            foreach ($arrQPID as $QPID) {
                $query = "SELECT ID, isActive FROM MapPublishAsmtQuadPlus WHERE 1 AND PublishAsmtID = {$pubID} AND QuadPlusId = {$QPID} ";
                $result = $this->db->getSingleRow($query);

                if ($result && $result['ID'] > 0) {
                    ($result['isActive'] == "Y" ) ? $newStatus = 'N' : $newStatus = 'Y';

                    // If Yes, Make Assignment disable for this QP
                    $query = "UPDATE MapPublishAsmtQuadPlus
                                SET 	isActive = '$newStatus'
                                WHERE ID ={$result['ID']} ";
                    $this->db->execute($query);
                } else {
                    $data = array(
                        'PublishAsmtID' => $pubID,
                        'QuadPlusId' => $QPID,
                        'AddBY' => $this->session->getValue('userID'),
                        'AddDate' => date('Y-m-d H:i:s'),
                        'ModBY' => $this->session->getValue('userID'),
                        'ModDate' => date('Y-m-d H:i:s'),
                        'isActive' => 'Y',
                        'isActive' => 'Y'
                    );
                    $mapPublishAsmtQuadPlus = $this->db->insert('MapPublishAsmtQuadPlus', $data);
                }
            }
        }
        return true;
    }

    /**
     * Get Quad Plus Usage list for the published Assessment
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$pubID
     * @return   array         $getData
     *
     */
    public function viewQpAsmtUsage($pubID) {
        $query = "SELECT pa.ID as PublishedID,  pa.PublishedTitle,
                    qpi.ID as QpID, qpi.QPName, mpaqp.ID as MapPaQpID, mpaqp.ModDate, mpaqp.isActive
                    FROM PublishAssessments  AS pa
                    LEFT JOIN MapPublishAsmtQuadPlus AS mpaqp ON mpaqp.PublishAsmtID = pa.ID AND mpaqp.isEnabled = '1'
                    LEFT JOIN QuadPlusInfo AS qpi             ON qpi.ID = mpaqp.QuadPlusId  AND qpi.isEnabled = '1'
                    WHERE pa.ID = '{$pubID}' AND pa.isEnabled = '1'
                    AND qpi.ID IS NOT NULL ";
        return $getData = $this->db->getRows($query);
    }

    /**
     * Assign/UnAssign "Published Assessment" to Quad Plus
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$input
     * @param    string  	$currStatus
     * @return   string        Assessment active/inactive Status
     *
     */
    public function toggleStatusPublishAsmtQuadPlus($mpaqpID, $currStatus) {
        ($currStatus == 'Y') ? $newStatus = 'N' : $newStatus = 'Y';
        $sqlUpdate = "UPDATE MapPublishAsmtQuadPlus SET isActive = '{$newStatus}' WHERE ID='$mpaqpID' and isEnabled = '1' ";
        $this->db->execute($sqlUpdate);
        return ( $newStatus == 'Y') ? "Activated" : "De-Activated";
    }

    /**
     * Delete "Published Assessment" Assigned to Quad Plus
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$mpaqpID
     * @return   string          Assessment delete status
     *
     */
    public function deletePubAsmtToQuadPlus($mpaqpID) {
        Site::myDebug("--------deletePubAsmtToQuadPlus");
        $sqlUpdate = "UPDATE MapPublishAsmtQuadPlus SET isEnabled = '0', isActive= 'N' WHERE ID='$mpaqpID'  ";
        Site::myDebug($sqlUpdate);
        $delStatus = $this->db->execute($sqlUpdate);
        return ( $delStatus ) ? "Assigned assessment deleted successfully." : "Assigned assessment could not be deleted.";
    }

    /**
     * Get detailed Usage Report of Published Assessment for Quad Plus
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array         $input
     * @return   string        $xmlData  (XML format)
     *
     */
    public function viewQpAsmtUsageDetails($input) {
        $usr = new User();
        $qpToken = $usr->getQpTokenByQpID($input['QuadPlusID']);
        Site::myDebug($qpToken);
        Site::myDebug($input['QuadPlusID']);

        $arrUsageList = array();
        $query = "  SELECT qpaud.UserInfo, qpaud.SecureCode, if(ur.AttemptStatus is null,'Pending', ur.AttemptStatus) as AttemptStatus,
                                count(ur.ID) as TotalAttempts,max(ur.UserScore) as Highest,  ur.TotalScore, min(ur.UserScore) as Lowest,
                                avg(ur.TimeTaken) as AvgTime, ur.FinishDate, ur.StartDate,
                                if(ur.FinishDate is null, qpaud.AddDate, ur.FinishDate) as LastAttemptDate,
                                ur.ClientToken  , ur.PublishedID, pa.PublishedTitle
                                FROM QPAsmtUserDetails as qpaud
                                LEFT JOIN UserReports  AS ur ON ur.UserID = qpaud.UserID AND  ur.PublishedID = qpaud.PublishedID  AND ur.isEnabled = '1' and qpaud.AssignmentID = ur.AssignmentID
                                LEFT JOIN PublishAssessments  AS pa ON pa.ID = ur.PublishedID AND pa.isEnabled = '1'
                                WHERE qpaud.ClientToken = '$qpToken' and qpaud.PublishedID = '{$input['PublishedID']}'
                                group by qpaud.AssignmentID, qpaud.UserID
                                ORDER BY qpaud.ID, ur.FinishDate DESC , ur.StartDate DESC  ";
        $arrUsageList = $this->db->getRows($query);

        $xmlData .= "<quadplususage>";
        if (!empty($arrUsageList)) {
            $i = 0;
            foreach ($arrUsageList as $usageList) {
                if ($usageList['UserInfo']) {
                    $i++;
                    if ($usageList["AttemptStatus"] == "Completed") {
                        $lattemptdate = date("F j, Y, g:i a", strtotime($usageList["FinishDate"]));
                    } elseif ($perma["AttemptStatus"] == "Incomplete") {
                        $lattemptdate = date("F j, Y, g:i a", strtotime($usageList["LastAttemptDate"]));
                    } else {
                        $lattemptdate = date("F j, Y, g:i a", strtotime($usageList["LastAttemptDate"]));
                    }

                    $userInfo = json_decode($usageList['UserInfo']);

                    $xmlData .= "<qpusage>
                                            <srno>{$i}</srno>
                                            <qpid>{$usageList["ID"]}</qpid>
                                            <qplearnername>" . $userInfo->{'learner_name'} . "</qplearnername>
                                            <date>" . date("F j, Y, g:i a", strtotime($usageList["ModDate"])) . "</date>
                                            <attemptstatus>{$usageList["AttemptStatus"]}</attemptstatus>
                                            <totalattempts>{$usageList["TotalAttempts"]}</totalattempts>
                                            <highest>" . (($usageList["Highest"]) ? $usageList["Highest"] : 0) . "</highest>
                                            <lowest>" . (($usageList["Lowest"]) ? $usageList["Lowest"] : 0) . "</lowest>
                                            <avgtime>{$this->sec2hms($usageList["AvgTime"])}</avgtime>
                                            <totalscore>{$usageList["TotalScore"]}</totalscore>
                                            <lattemptdate>{$lattemptdate}</lattemptdate>
                                            <securecode>{$usageList["SecureCode"]}</securecode>
                                            <pubid>{$usageList["PublishedID"]}</pubid>
                                          </qpusage>";
                }
            }
        }
        $xmlData .= "</quadplususage>";
        return $xmlData;
    }

    /**
     * Toggles (Activate or DeActivate) the status  of "Published Assessment"
     * Adds publish Assessment details for Quad Plus , if not present
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @return   boolean
     *
     */
    public function togglePublishAsmtStatus($input) {
        $curStatus = $input['status'];
        $mapPubAsmtID = (int) $input['mapPubAsmtID'];

        if ($mapPubAsmtID) {
            ($curStatus == 'Y') ? $newStatus = 'N' : $newStatus = 'Y';
            $sqlUpdate = "UPDATE MapPublishAsmtQuadPlus
                            SET isActive = '{$newStatus}', ModBY ='{$this->session->getValue('userID')}', ModDate='" . $this->currentDate() . "'
                            WHERE ID='$mapPubAsmtID' ";
            $this->db->execute($sqlUpdate);
        } else {
            $pubID = (int) $input['pubID'];
            $QPID = (int) $input['qpID'];
            $data = array(
                'PublishAsmtID' => $pubID,
                'QuadPlusId' => $QPID,
                'AddBY' => $this->session->getValue('userID'),
                'AddDate' => $this->currentDate(),
                'ModBY' => $this->session->getValue('userID'),
                'ModDate' => $this->currentDate(),
                'isActive' => 'Y',
                'isActive' => 'Y'
            );
            $mapPublishAsmtQuadPlus = $this->db->insert('MapPublishAsmtQuadPlus', $data);
        }
        return true;
    }

    public function asmtShortNameCheck($shortName, $id) {
        $sql = 'SELECT DISTINCT ass.ShortName, b.ShortName FROM `Assessments` ass, `Banks` b WHERE (ass.`ShortName` = ? AND ass.`ID` != ? ) OR ( b.`ShortName` = ? AND b.`ID` != ? )';
        //$sql = "SELECT DISTINCT ass.ShortName, b.ShortName FROM `Assessments` ass, `Banks` b WHERE (ass.`ShortName` = '".$shortName."' ) OR ( b.`ShortName` = '".$shortName."')";
        //$sql = 'SELECT ass.* FROM `Assessments` ass WHERE (ass.`ShortName` = ? AND ass.`ID` != ? )';
        $paramArr = Array($shortName, $id, $shortName, $id);
        $rowCount = $this->db->AsmtShortNameCheckExecPrepareStmt($sql, $paramArr);
        return $rowCount;
//        $totalRecord = $this->db->getRows($sql);
//        return count($totalRecord);
    }

    public function addQuestionSection($data) {
        $questid = $data['questid'];
        $deleteQuestionIdList = $data['deleteQuestionIdList'];
        //$EntityID               = $this->input['EntityID'];
        $sectionID = $data['sectionID'];

        if ($deleteQuestionIdList != "") {
            $updated_data = array(
                'ModDate' => $this->currentDate(),
                'ParentID' => 0
            );
            $where = " ID IN (" . $deleteQuestionIdList . ")";
            $this->db->update("MapRepositoryQuestions", $updated_data, $where);
        }
        if ($questid != "" && $sectionID != "") {
            $updated_data = array(
                'ModDate' => $this->currentDate(),
                'ParentID' => $sectionID
            );
            $where = " ID IN (" . $questid . ")";
            $this->db->update("MapRepositoryQuestions", $updated_data, $where);
        }
    }

    public function deleteSectionQuestion($data) {
        $sectionID = $data['sectionID'];
        $chooseOption = $data['chooseOption'];
        $ret = 0;
        if ($chooseOption == "only_section") {
            $updated_data = array(
                'ModDate' => $this->currentDate(),
                'isEnabled' => 0,
                'Sequence' => 0
            );
            $where = " ID ='" . $sectionID . "'";
            $this->db->update("MapRepositoryQuestions", $updated_data, $where);
            $updated_data2 = array(
                'ModDate' => $this->currentDate(),
                'ParentID' => 0,
                'isEnabled' => 1,
                'Sequence' => 0
            );
            $where = " ParentID ='" . $sectionID . "' AND isEnabled='1' ";
            $this->db->update("MapRepositoryQuestions", $updated_data2, $where);
            $ret = 1;
        }
        if ($chooseOption == "question_section") {
            $updated_data = array(
                'ModDate' => $this->currentDate(),
                'isEnabled' => 0,
                'Sequence' => 0
            );
            $where = " ID ='" . $sectionID . "'";
            $this->db->update("MapRepositoryQuestions", $updated_data, $where); // For Section
            $where = " ParentID ='" . $sectionID . "'";
            $this->db->update("MapRepositoryQuestions", $updated_data, $where); // For Question
            $ret = 1;
        }
        $this->questionSequenceRearrange($sectionID);
        return $ret;
    }

    public function questionPreview($params = '') {

        $questionID = $params['questionID'];
        $version = $params['version'];
        if ($version == 'current') {
            $questionResult = $this->db->executeStoreProcedure('GetQuestionDetailsPreview', array($questionID, '-1'));
        } else if ($version == 'clicked-version') {
            $questionResult = $this->db->executeStoreProcedure('QuestionDetails', array($questionID, '-1'));
        }
        return $questionResult;
    }

    /*
     * Description: get the question list for a perticular assessment 
     * Author: Balaram
     * Created: 26th Aug. 2015
     */

    public function questionListForAssessment($asmtShortName) {
        global $DBCONFIG;
        $questionIDs = array();
        $this->qst = new Question();
        $questionIDList = array();
        $questionArray = array();
        $questionCont = array();
        $questionNode = array();
        $question_params = array();

        $qry = "SELECT ID FROM Assessments WHERE ShortName='" . $asmtShortName . "'";
        $resultset = $this->db->getSingleRow($qry);
        $asmtID = $resultset['ID'];

        $assessmentID = $asmtID;
        $questionIDArr = $this->qst->getQuestionListOfAnAssessmentForPreview($assessmentID);

        $checkQuestionIDArr = array_filter($questionIDArr); // function's default behavior will remove all values from array which are equal to null, 0, '' or false.

        if (!empty($checkQuestionIDArr)) {

            foreach ($questionIDArr as $k => $v) {
                $questionIDList[$k] = $v['QuestionID'];
            }

            $questionID = implode(",", $questionIDList);
            $res = $this->qst->getQuestionAdvJson($questionID);
            $question_params['EntityID'] = $assessmentID;
            $question_params['EntityTypeID'] = '2';
            $settingArray['settings'] = "";

            foreach ($res as $key => $val) {

                $question_params['QuestionID'] = $val['ID'];
                $QuestionRepoId = $this->qst->getQuestionSearchName($question_params);
                $QuestionId = $this->registry->site->guid();
                $questionNode['QuestionId'] = $QuestionId;
                $questionNode['QuestionData'] = json_decode($this->qst->mmlToImg($this->qst->removeMediaPlaceHolder(($val['advJSONData']))));
                $questionNode['VersionNo'] = 0;
                $questionNode['DisplayQuestionId'] = $QuestionRepoId;
                $questionNode['OriginalQuestionId'] = $QuestionId;
                $questionNode['IsDeleted'] = false;
                $questionNode['SelectedOptions'] = null;
                $questionCont[] = $questionNode;
            }

            $questionArray['quiz'] = $questionCont;
            $inputQuestionToAPI = array_merge($questionArray, $settingArray);
        } else {
            $inputQuestionToAPI = array();
        }
        return $inputQuestionToAPI;
    }

    public function getQuestionListsFromAssessment($asmtID, $entityTypeID, $questionID) {
//        global $DBCONFIG;
//        $query = "SELECT  DISTINCT QuestionID FROM MapRepositoryQuestions WHERE EntityID = {$asmtID} AND EntityTypeID = {$entityTypeID} AND QuestionID IN ({$questionID}) AND isEnabled='1'";
//        return $this->db->getRows($query);

        global $DBCONFIG;
        $resArray = array();
        $query = "SELECT DISTINCT QuestionID FROM MapRepositoryQuestions WHERE EntityID = {$asmtID} AND EntityTypeID = {$entityTypeID} AND isEnabled='1'";
        //echo $query;die;
        $res = $this->db->getRows($query);
        foreach ($res as $val) {
            $resArray[] = $val['QuestionID'];
        }
        echo "|" . implode($resArray, "||") . "|";
    }

    /*
     *  Function Name   :- getAssessmentPreviewSettings
     *  Description     :- Return assesment related settings value 
     *  Author          :- Akhlack
     *  Date            :- 8 October , 2015
     */

    public function getAssessmentPreviewSettings($asmtID, $entityTypeID) {
        global $DBCONFIG;
        if ($asmtID != "" && $entityTypeID != "") {
            $asmtSet = $this->db->executeStoreProcedure('AssessmentPreviewSettings', array($asmtID, $entityTypeID));
            return $asmtSet;
        }
    }

    /*
     *  Function Name   :- checkAssessmentAccess
     *  Description     :- return true or false;
     *  Author          :- Akhlack
     *  Date            :- 16 October , 2015
     */

    public function checkAssessmentBankAccess($EntityTypeID, $EntityID, $userID = '') {

        if ($this->session->getValue('isAdmin')) {
            return true;
        }

        $userID = ( $userID != "" ? $userID : $this->session->getValue('userID') );
        $query = " SELECT ID FROM RepositoryMembers WHERE EntityTypeID = '" . $EntityTypeID . "' AND UserID='" . $userID . "' AND EntityID ='" . $EntityID . "' AND isEnabled=1 ";
        $res = $this->db->getRows($query);
        if ($res[0]['ID'] != "") {
            return true;
        } else {
            return false;
        }
    }

    /*
     * @ Description    : This method will rearrange the whole sequence of an assessment
     * @ Create Date    : 22th January,2016
     * @ author         : Akhlack     
     */

    public function questionSequenceRearrange($repoID) {
        $repoIDL = explode(",", $repoID);
        $sel = " SELECT EntityID FROM  MapRepositoryQuestions WHERE ID='" . $repoIDL[0] . "' ";
        $res = $this->db->getSingleRow($sel);
        $query = " SET @rank:=0; UPDATE MapRepositoryQuestions SET Sequence=@rank:=@rank+1 WHERE isEnabled=1 AND EntityTypeID='2' AND EntityID='" . $res['EntityID'] . "'  ORDER BY Sequence  ";
        //$query      = " SET @rank:=0; UPDATE MapRepositoryQuestions SET Sequence=@rank:=@rank+1 WHERE isEnabled=1 AND EntityTypeID='2' AND EntityID='".$res['EntityID']."'  ORDER BY Sequence ASC,ID DESC ";

        $this->db->execute($query);
    }

}

?>