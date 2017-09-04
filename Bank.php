<?php

/**
 * This class handles all bank module related queries/requests.
 * This class handles the business logic of listing/add/edit/delete/search/questionlist and other requests of banks.
 * this is rahul
 * @access   public
 * @abstract
 * @static
 * @global
 * @star
 */
class Bank extends Site {

    public $id;

    /**
     * Construct new bank instance
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   void
     *
     */
    function __construct() {
        parent::Site();
        $this->id = '';
    }

    /**
     * gets banks list of the current institution.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $input      list of all input values.
     * @param    array   $filter     string value for filter.
     * @return   array               list of banks.
     *
     */
    public function bankList(array $input, $filter = '-1') {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "bnk.\"ModDate\"";
        } else {
            $input['pgnob'] = ($input['pgnob'] != "-1") ? $input['pgnob'] : "bnk.ModDate";
        }

        $input['pgnot'] = ($input['pgnot'] != "-1") ? $input['pgnot'] : "desc";
        $data = $this->db->executeStoreProcedure('BankList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc']));

        return $data;
    }

    /**
     * search for banks in the current institution.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $input          list of all input values.
     * @param    string  $condition      search condition.
     * @param    string  $tags           comma seperated tag list to search bank.
     * @param    string  $taxonomies     comma seperated taxonomy id to search.
     * @return   array   $data           list of searched banks.
     *
     */
    function bankSearchList(array $input, $condition, $tags, $taxonomies) {
        $this->myDebug("----------Inputs----------");
        $this->myDebug($input);
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgnob'] = ($input['pgnob'] != '-1') ? $input['pgnob'] : 'bnk.\"ModDate\"';
        } else {
            $input['pgnob'] = ($input['pgnob'] != '-1') ? $input['pgnob'] : 'bnk.ModDate';
        }
        $condition = ($condition != '') ? $condition : '-1';
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        $input['pgndc'] = '-1';

        $cls = new Classification();

        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tags;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomies;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        $value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['fromsearchdate'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['tosearchdate'];
		
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
			$entityTypeID	=	'1';
			$entityID	=	'0';
			$advSearchBnkQuestIds	=	'-1';
			$bnkEntityTypeId = '-1';
			$advSearchAsmtQuestIds = '-1';
            $asmtEntityTypeId = '-1';
			$bulkEditEntityIds = '-1';
			$pFilterNewSearchValue=$input['sSearch'];
			$pFilterOldSearchValue=$input['search'];
			$storeProcedureName='BankSearchListFilter';
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
			
			$data = $this->db->executeStoreProcedure('BankSearchList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), $input['pgndc'], $tags, $taxo, $owner, $key, $value, $startdate, $enddate, $searchtype, $title_filter, $users_filter, $date_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter));
		}
		
		
		
		
        $input['entityid'] = 0;
        $input['entitytypeid'] = 1;
        $input['spcall'] = $data['QR'];
        $input['count'] = $data['TC'];

        if (trim($input['hdn_searchcrieteria']) != '') {
            $this->saveAdvSearchCrieteria($input);
        } else {
            //Retain the current search context and the latest search string in the quick search field on each page
            //when searched from bank listing page
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
     * save bank details.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $input          list of all input values.
     * @return   int     $bankID         bank id.
     *
     */
    function save(array $input) {
        $tags = (array_key_exists("Tags", $input)) ? $input['Tags'] : $input['Tags'];
        $tags = trim($tags);
        $users = ($this->isVarSet('BankUsers')) ? $input['BankUsers'] : $this->session->getValue('userID');

        $qtp = new QuestionTemplate();
        $templates = (array_key_exists('BankTemplates', $input) && (trim($input["AssessmentTemplates"]) == '')) ? $input['BankTemplates'] : $qtp->questionTemplateIdString(); //changed for quadnext

        $input['BankInfo'] = (array_key_exists('BankInfo', $input)) ? $input['BankInfo'] : $input['BankName'];
        $input['QuestionUpdate'] = (array_key_exists('QuestionUpdate', $input)) ? $input['QuestionUpdate'] : "2";
        $input['BankID'] = (is_numeric($input['BankID'])) ? $input['BankID'] : "0";

        $input['BankShortName'] = (array_key_exists('BankShortName', $input)) ? $input['BankShortName'] : "";
        $input['input-ace-is-publish'] = (array_key_exists('input-ace-is-publish', $input)) ? $input['input-ace-is-publish'] : "0";
        $dataArray = array(
                $input['BankID'], 
                $input['BankName'], 
                $input['BankInfo'], 
                'Private', 
                $this->session->getValue('userID'), 
                $this->currentDate(),   
                '1', 
                $users, 
                $tags, 
                $input['taxonomyNodeIds'], 
                $templates, 
                $input["QuestionUpdate"], 
                $this->session->getValue('accessLogID'), 
                $input['BankShortName'],
                $input["input-ace-is-publish"],
                $input["input-ace-product"],
                $input["input-ace-taxonomy"],
                $input["input-ace-kvp"],
                $input["input-ace-tag"],
                $input["input-ace-product-name"]
                );

        $activityAction = ($input['BankID'] ) ? 'Edited' : 'Added';
        //$checkDuplicateBankName = $this->db->executeFunction('BankExist', 'cnt', array($input['BankID'],$input['BankName'],''));
        //if($checkDuplicateBankName['cnt'] == 0)
        //{
       
        $bankDetail = $this->db->executeStoreProcedure('BankManage', $dataArray, 'nocount');

        $bankID = $this->getValueArray($bankDetail, 'BankID');

        $Metadata = new Metadata();
        if ($input["BankID"]) {
            $bankID = $input["BankID"];
        }

        $auth = new Authoring();
        //$auth->copyCss($bankID,1);

        $bankTypeId = $this->getEntityId('Bank');
        //$Metadata->saveMetadata($input,$bankID,$bankTypeId);
        if (!isset($input["QuestID"])) {
            $Metadata->assignedMetadata($input, $bankID, $bankTypeId);
        }

        $data_val = array(
            0,
            $this->session->getValue('userID'),
            1,
            $bankID,
            $input['BankName'],
            $activityAction,
            $this->currentDate(),
            '1', $this->session->getValue('accessLogID')
        );
        $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);

        $userRightsString = $input['UserRight'];
        $this->manageUsers($userRightsString, $users, $bankID, $bankTypeId);

        return $bankID;
        /* }
          else
          {
          return FALSE;
          } */
    }

    /**
     * Manage access information of specified bank for given list of user with their rights.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $UserRightsString   user right string.
     * @param    array   $users              user list seperated by comma.
     * @param    array   $bankID             bank id .
     * @param    array   $bankTypeId         entityType id i.e id of entity type 'bank' is 2.
     * @return   void
     *
     */
    function manageUsers($UserRightsString, $users, $bankID, $bankTypeId) {
        $addedusers_list = explode(',', $users);
        if ($UserRightsString) {
            $UserRightsRecords = explode('@', $UserRightsString);
            $count = count($UserRightsRecords);

            for ($i = 0; $i < $count; $i++) {
                $tmp = explode('-', $UserRightsRecords[$i]);
                $MemberIds[$i] = $tmp[0];

                if (in_array($MemberIds[$i], $addedusers_list)) {
                    $RightsString = $tmp[1];
                    $updated_data = array(
                        'ModDate' => $this->currentDate(),
                        'isEnabled' => '0'
                    );

                    $RightsValString = explode(',', $RightsString);
                    $cnt = count($RightsValString);

                    for ($j = 0; $j < $cnt; $j++) {
                        $RightsVal = explode(':', $RightsValString[$j]);
                        $right_id = $RightId[$i][$j] = $RightsVal[0];
                        $right_val = $RightVal[$i][$j] = $RightsVal[1];

                        $data = array(
                            'MemberId' => $MemberIds[$i],
                            'EntityTypeId' => $bankTypeId,
                            'EntityId' => $bankID,
                            'UserRightsId' => $right_id,
                            'AddBy' => $this->session->getValue('userID'),
                            'AddDate' => $this->currentDate(),
                            'ModDate' => $this->currentDate(),
                            'isActive' => $right_val,
                            'isEnabled' => '1'
                        );

                        $where = " EntityTypeId={$bankTypeId} and EntityId={$bankID} and MemberId={$MemberIds[$i]} and UserRightsId={$right_id}  and isEnabled = '1'";
                        $this->db->update('MapEntityRights', $updated_data, $where);
                        $this->db->insert('MapEntityRights', $data);
                    }
                }
            }
        }
        return;
    }

    /**
     * get details for specified bank id from question list page.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    int     $bankID     bank id.
     * @return   array   $details    all details of specified bank id.
     *
     */
    function bankDetailInfo($bankID = '-1') {
        $details = array();
        if (intval($bankID) > 0) {

            //$array  = array($bankID,$this->session->getValue('userID'),$this->session->getValue('isAdmin'),$this->session->getValue('instID'));
            //CALL BankList("-1", "-1", "-1", "-1", CONCAT("bnk.ID = ", pBankID), pUserID, pisAdmin, pInstID, "-1");

            $condition = 'bnk.ID =' . $bankID;
            $array = array(
                "-1", "-1", "-1", "-1",
                $condition,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID'), "-1"
            );
            $result = $this->db->executeStoreProcedure('BankList', $array, 'nocount');
            $usr = new User();
            $details = array(
                'BankID' => $bankID,
                'BankName' => $this->getValueArray($result, 'Name'),
                'BankInfo' => $this->getValueArray($result, 'BankInfo'),
                'AccessMode' => $this->getValueArray($result, 'AccessMode'),
                'Tag' => $this->getValueArray($result, 'Tag'),
                'Taxonomy' => $this->getValueArray($result, 'Taxonomy'),
                'AllTag' => $this->getValueArray($result, 'AllTag'),
                'AllTaxonomy' => $this->getValueArray($result, 'AllTaxonomy'),
                'BankUsers' => $this->getValueArray($result, 'BankUsers'),
                'BankTemplates' => $this->getValueArray($result, 'BankTemplates'),
                "QuestionAutoUpdate" => $this->getValueArray($result, "QuestionAutoUpdate"),
                'SpecificRights' => $usr->getMapEntityRightDetails($bankID, 1, $this->session->getValue('userID'))
            );
            $data_val = array(
                0,
                $this->session->getValue('userID'),
                1,
                $bankID,
                $details["BankName"],
                'Viewed',
                $this->currentDate(),
                '1', $this->session->getValue('accessLogID')
            );
            $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
        }
        return $details;
    }

    /**
     * get details for specified bank id.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    int     $bankID     bank id.
     * @return   array   $details    all details of specified bank id.
     *
     */
    function bankDetail($bankID = '-1') {
        $details = array();
        if (intval($bankID) > 0) {

            $array = array($bankID, $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'));
            $result = $this->db->executeStoreProcedure('BankDetails', $array, 'nocount');
            $usr = new User();
            $details = array(
                'BankID' => $bankID,
                'BankName' => $this->getValueArray($result, 'Name'),
                'BankInfo' => $this->getValueArray($result, 'BankInfo'),
                'AccessMode' => $this->getValueArray($result, 'AccessMode'),
                'Tag' => $this->getValueArray($result, 'Tag'),
                'Taxonomy' => $this->getValueArray($result, 'Taxonomy'),
                'AllTag' => $this->getValueArray($result, 'AllTag'),
                'AllTaxonomy' => $this->getValueArray($result, 'AllTaxonomy'),
                'BankUsers' => $this->getValueArray($result, 'BankUsers'),
                'BankTemplates' => $this->getValueArray($result, 'BankTemplates'),
                "QuestionAutoUpdate" => $this->getValueArray($result, "QuestionAutoUpdate"),
                'SpecificRights' => $usr->getMapEntityRightDetails($bankID, 1, $this->session->getValue('userID')),
                "BankShortName" => $this->getValueArray($result, "ShortName")
            );
            $data_val = array(
                0,
                $this->session->getValue('userID'),
                1,
                $bankID,
                $details["BankName"],
                'Viewed',
                $this->currentDate(),
                '1', $this->session->getValue('accessLogID')
            );
            $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
        }
        return $details;
    }

    /**
     * generate string of user listing with their bank specific rights.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    int     $BankID     bank id.
     * @return   array   $userstr    string of user list with bank specific rights..
     *
     */
    function createRightsStr($bankUsers, $BankID) {
        global $DBCONFIG;
        $tmpUser = explode(',', $bankUsers);
        $count = count($tmpUser);

        for ($i = 0; $i < $count; $i++) {
            if ($DBCONFIG->dbType == 'Oracle')
                $query = " select * from MapEntityRights where \"MemberId\"='$tmpUser[$i]' and \"EntityTypeId\"=2 and \"EntityId\"='{$BankID}' and  \"isEnabled\" = '1'";
            else
                $query = " select * from MapEntityRights where MemberId='$tmpUser[$i]' and EntityTypeId=2 and EntityId='{$BankID}' and  isEnabled = '1'";
            $result = $this->db->getRows($query);
            $noofrows = count($result);
            $str = '';

            for ($j = 0; $j < $noofrows; $j++) {
                $RightId = $result[$j]['UserRightsId'];
                $Rightval = $result[$j]['isActive'];
                $str .= $RightId . ':' . $Rightval . ',';
            }
            $str = trim($str, ',');

            if ($str) {
                $userstr.= $tmpUser[$i] . '-';
                $userstr.= $str . '@';
            }
        }
        return trim($userstr, '@');
    }

    /**
     * gets question list for specified bank.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $input      list of all input values..
     * @param    int     $BankID     bank id.
     * @param    string  $filter     filter string.
     * @return   array               list of question in bank.
     *
     */
    function questionList(array $input, $BankID, $filter = '-1') {
        global $DBCONFIG;

        if ($DBCONFIG->dbType == 'Oracle')
            $input['pgndc'] = "case when qtp.\"ID\" IN (37,38) then   ''quest-editor-ltd'' else ''quest-editor'' end as \"EditPage\" , qtp.\"TemplateGroup\" , qtp.\"RenditionMode\",qst.\"Count\" ,  mrq.\"EditStatus\", qst.\"AuthoringStatus\", qst.\"AuthoringUserID\" ";
        else
            $input['pgndc'] = "if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' ) as EditPage, qtp.TemplateGroup ,qtp.RenditionMode,qst.Count ,  mrq.EditStatus, qst.AuthoringStatus, qst.AuthoringUserID ";

        $array = array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $BankID, '1', $this->session->getValue('userID'), $input['pgndc']);

        // $data =  $this->db->executeStoreProcedure('RepositoryQuestionList',$array);
        /* start: Bank listing and search optimisation */
        if ($filter == "-1") {
            $questionlist = $this->db->executeStoreProcedure('QuestionList', $array);
            $questionlist['TC'] = $this->getQuestionsCountInBank($BankID);
        } else {
            $questionlist = $this->db->executeStoreProcedure('QuestionList', $array);
        }
        /* ends here */
        //$data['TC'] = $this->getValueArray($data['RS'], "@QuestionIDCount"); change for oracle
        //unset($data['RS'][$input['pgnstop']]);
        //@array_pop($data['RS']); change for oracle
        return $questionlist;
    }

    /**
     * add template layout of each question in question list.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $questionlist   question list without TemplateLayout.
     * @return   array   $questionlist   question list with TemplateLayout.
     *
     */
    function getTemplateLayout(array $questionlist) {
        $qtp = new QuestionTemplate();
        $templateLayouts = $qtp->templateLayout();

        $i = 0;
        if (!empty($questionlist)) {
            foreach ($questionlist as $question) {
                $questionlist[$i]['TemplateLayout'] = $this->getAssociateValue($templateLayouts, $question['qtID']);
                $i++;
            }
        }
        return $questionlist;
    }

    /**
     * add template layout of each question in question list.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array   $input      list of all input values.
     * @param    array   $BankID     bank id.
     * @param    string  $tags       tag list seperated by comma.
     * @param    string  $taxonomies taxonomy id list seprated by comma.
     * @param    string  $filter     search filter.
     * @return   array   $data       searched question list from the specified bank.
     *
     */
    function questionSearchList(array $input, $BankID, $tags, $taxonomies, $filter = '-1') {
        global $DBCONFIG;
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst.Count' : $input['pgndc'] . ',qst.Count';

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
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';

        $input['pgnob'] = '-1';
        if ($DBCONFIG->dbType == 'Oracle') {
            $input['pgndc'] = "(case when qtp.\"ID\" IN (37,38) then ''quest-editor-ltd'' ELSE ''quest-editor''  end) as \"EditPage\" ,qst.\"Count\", mrq.\"EditStatus\",  qst.\"AuthoringStatus\", qst.\"AuthoringUserID\"  ";
        } else {
            $input['pgndc'] = "if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' ) as EditPage ,qtp.RenditionMode,qst.Count, mrq.EditStatus,  qst.AuthoringStatus, qst.AuthoringUserID  ";
        }
        $array = array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, $BankID, '1', $this->session->getValue('userID'),
            $input['pgndc'],
            $tags,
            $taxo,
            $owner,
            $this->session->getValue('instID'),
            $key,
            $value,
            $difficulty, $template, $startdate, $enddate, $searchtype,
            $title_filter, $users_filter, $templates_filter, $date_filter, $difficulty_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter
        );

        $data = $this->db->executeStoreProcedure('QuestionSearchList', $array);
        // $data['TC'] = $this->getValueArray($data['RS'], "@QuestionIDCount");
        //unset($data['RS'][$input['pgnstop']]);
        //@array_pop($data['RS']);
        $input['entityid'] = $BankID;
        $input['entitytypeid'] = 1;
        $input['count'] = $data['TC'];
        $input['spcall'] = $data['QR'];

        if (trim($input['hdn_searchcrieteria']) != '') {
            $this->saveAdvSearchCrieteria($input);
        }
        return $data;
    }

    /**
     * get all banks list for which logged in user had access in current institution.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    
     * @return   array   bank list.
     *
     */
    function bankAllList() {
        return $this->db->executeStoreProcedure('BankList', array("-1", "-1", "-1", "-1", "-1", $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), "-1"), 'nocount');
    }

    /**
     * delete specified bank.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    int     $bankID     bank id.
     * @return   mixed   $val        either integer entityid or true/false bollean value.
     *
     */
    public function delete($bankID) {
        global $DBCONFIG;
        $bankIDs = $this->removeBlankElements($bankID);
        $entityTypeId = $this->getEntityId('Bank');
        $bankID = implode(',', (array) $this->removeUnAccessEnities('BankDelete', $entityTypeId, $bankIDs));

        if (!empty($bankID)) {
            if ($DBCONFIG->dbType == 'Oracle')
                $query = "  UPDATE Banks SET \"isEnabled\" = '0' WHERE ID IN($bankID) ";
            else
                $query = "  UPDATE Banks SET isEnabled = '0' WHERE ID IN($bankID) ";


            $bnkarr = $this->db->executeFunction('ENTITYTITLE', 'bankName', array($bankID, '1'));

            //  $accesslog = ($this->session->getValue('accessLogID')) ? $this->session->getValue('accessLogID') : '';
            //  $this->db->execute("CALL ActivityTrackManage(0,{$this->session->getValue('userID')},1,".intval($bankID).",Entity(".intval($bankID).",1),'Deleted','{$this->currentDate()}','1','{$accesslog}');");


            $data_val = array(
                0,
                $this->session->getValue('userID'),
                1,
                $bankID,
                $bnkarr['bankName'],
                'Deleted',
                $this->currentDate(),
                '1', $this->session->getValue('accessLogID')
            );
            $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);

            $val = $this->db->execute($query);
            //print_r($query); echo "<br/>"; print_r($val); die('inside delete');
            if ($val) {
                if ($DBCONFIG->dbType == 'Oracle')
                    $qry = "update MapRepositoryQuestions set \"isEnabled\" = '0' where  \"EntityTypeID\" = 1 and \"EntityID\" IN ( {$bankID} )";
                else
                    $qry = "update MapRepositoryQuestions set isEnabled = '0' where  EntityTypeID=1 and EntityID IN ( {$bankID} )";
                $this->db->execute($qry);
            }
            /* ============Classification Usage Count Decrease ================ */
            $assesmentModel = new Assessment();
            $assesmentModel->decreaseClassificationCount($bankID, '1');
            /* ================================================================ */

            return $val;
        }
        else {
            return false;
        }
    }

    /**
     * get full bank information for specified bank id.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    int     $bankID     bank id.
     * @return   array               information for specified bank id.
     *
     */
    function getInfo($bankID) {
        $query = "  SELECT * FROM Banks WHERE ID = $bankID LIMIT 1 ";
        return $this->db->getSingleRow($query);
    }

    public function bankShortNameCheck($shortName, $id) {
        $sql = 'SELECT DISTINCT ass.ShortName, b.ShortName FROM `Assessments` ass, `Banks` b WHERE (ass.`ShortName` = ? AND ass.`ID` != ? ) OR ( b.`ShortName` = ? AND b.`ID` != ? )';
        $paramArr = Array($shortName, $id, $shortName, $id);
        $rowCount = $this->db->AsmtShortNameCheckExecPrepareStmt($sql, $paramArr);
        return $rowCount;
    }

    function generateRandomString($characters) {
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $charactersLength; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function getShortNameSuggestion($input) {
        
        $BankID = $input['bankID'];
        if (isset($input['name']))
            $shortName = $input['name'];
        else {
            $BankDetails = $this->bankDetail($BankID);
            $shortName = strtoupper(preg_replace("/[^a-zA-Z0-9]+/", "", trim($BankDetails["BankName"])));
        }
        
        if(strlen($shortName)<5){
            $shortName = $this->randomString();
        }else{
            $shortName = str_shuffle($shortName);
        }

        $uniqueFound = false;
        while (!$uniqueFound) {
            $shortNamePrev[] = $shortName = substr($shortName, 0, 5);
            $rowCount = $this->bankShortNameCheck($shortName, $BankID);
            if ($rowCount == 0) {
                $uniqueFound = true;
            } else {
                $shortName = $this->generateRandomString($shortName);
                if (in_array($shortName, $shortNamePrev)) {
                    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
                    $shortName = str_shuffle($chars);
                    $shortName = $this->generateRandomString($shortName);
                }
            }
        }
        return strtoupper($shortName);
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

    public function delBulkQuestion($sectionid, $sectionop = "") {
        global $DBCONFIG;
        $title = $this->registry->site->getEntityTitle(3, $sectionid);
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE MapRepositoryQuestions SET MapRepositoryQuestions.\"isEnabled\" = '0', \"ModBY\" = {$this->session->getValue('userID')}, \"ModDate\" = '{$this->currentDate()}' WHERE \"ID\" = {$sectionid}";
        } else {

            $query = "UPDATE MapRepositoryQuestions SET MapRepositoryQuestions.isEnabled = '0', ModBY = {$this->session->getValue('userID')}, ModDate = '{$this->currentDate()}' WHERE ID = {$sectionid}";
        }

        $val = $this->db->execute($query);


        /* ====== Asset Counter Manage Start  ======== */
        $mediaModel = new Media();
        $mediaModel->assetUsageCounterManage($sectionid);
        /* ============Classification Usage Count Decrease ================ */
        $questionRepoid = $sectionid;
        $assesmentModel = new Assessment();
        $assesmentModel->decreaseClassificationCount($questionRepoid, 3);
        /* ================================================================ */

        if ($val) {
            $result1["Status"] = "Section Deleted";
            $data_val = array('', $this->session->getValue('userID'), 3, $sectionid, $title, 'Deleted', $this->currentDate(), 1, $this->session->getValue('accessLogID'));
            $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
        } else {
            $result1["Status"] = "Section Not Deleted";
        }
        $returnValue = $result1["Status"];
        return $returnValue;
    }

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

    /*
     * @manish<manish.kumar@learningmate.com>
     * 23-sep-2015
     * @archive
     * bank archive
     * TODO: Not implemented yet
     */

    function archive($input) {
        global $DBCONFIG;
        $quizIds = implode(',', $input);
        if ($DBCONFIG->dbType == 'Oracle') {
            $query = "UPDATE Banks SET \"Status\" = 'Archive', \"ModBY\" = {$this->session->getValue('userID')} WHERE \"ID\" IN ($quizIds) AND \"isEnabled\" = '1' ";
        } else {
            $query = "UPDATE Banks SET status = 'Archive', ModBY={$this->session->getValue('userID')} WHERE ID IN ($quizIds) AND isEnabled = '1'";
        }

        return $this->db->execute($query);
    }

    /*
     * @manish<manish.kumar@learningmate.com>
     * @28-Sep-2015
     * @updateSort
     * managing bank question order
     * @return bollean (update status)
     */

    public function updateSort(array $input) {
        global $DBCONFIG;

        $quizid = $input['quizid'];
        $listcontain = $input['listcontain'];
        $record = (int) $input['record'];

        if ($this->session->getValue('isAdmin')) {
            $query = "SELECT a.BankName, a.ID FROM Banks a  WHERE a.isEnabled = '1' AND a.ID = $quizid";
        } else {
            $query = "SELECT a.BankName, a.ID FROM Banks a , RepositoryMembers rm WHERE  a.isEnabled = '1' AND rm.isEnabled = '1' AND rm.UserID = '{$this->session->getValue('userID')}' and rm.EntityTypeID='1'  AND rm.EntityID = a.ID AND a.ID = $quizid";
        }

        if ($this->db->getCount($query) > 0) {
            $ilist = explode("|", $listcontain);
            $p = $record + 1;

            for ($i = 0; $i < sizeof($ilist); $i++) {
                if (strstr($ilist[$i], "-")) {
                    $jlist = explode("-", $ilist[$i]);
                    for ($j = 0; $j < sizeof($jlist); $j++) {
                        if ($j == 0) {
                            $sqlUpdate = "UPDATE MapRepositoryQuestions mrq SET mrq.Sequence='$p' WHERE mrq.ID='$jlist[0]' and mrq.EntityTypeID ='1' and mrq.isEnabled='1' ";
                            $this->db->execute($sqlUpdate);
                            $p++;
                        } else {
                            /* if parent id is not a section then DO no Update */
                            /* echo 'in if section '. */
                            $query_pchk = "SELECT mrq.QuestionID, mrq.QuestionTemplateID FROM MapRepositoryQuestions mrq	WHERE mrq.ID = '$jlist[0]' and mrq.QuestionID = '0' and mrq.QuestionTemplateID = '0'";
                            if ($this->db->getCount($query_pchk) > 0) {
                                $sqlUpdateSeq = "UPDATE MapRepositoryQuestions mrq  SET mrq.Sequence='$p',mrq.ParentID='$jlist[0]' WHERE mrq.ID='$jlist[$j]' and mrq.EntityTypeID ='1'  and mrq.isEnabled='1' ";
                                $this->db->execute($sqlUpdateSeq);
                                $p++;
                            } else {
                                return "update failed";
                            }
                        }
                    }
                } else {
                    $sqlUpdate = "UPDATE MapRepositoryQuestions mrq  SET mrq.Sequence='$p',mrq.ParentID='0' WHERE mrq.ID='$ilist[$i]' and mrq.EntityTypeID ='1'  and mrq.isEnabled='1' ";
                    $this->db->execute($sqlUpdate);
                    $p++;
                }
            }
            return "update success";
        } else {
            return "update failed";
        }
    }

    public function getQuestionListsFromBank($bankID, $entityTypeID) {
        global $DBCONFIG;
        $resArray = array();
        $query = "SELECT DISTINCT QuestionID FROM MapRepositoryQuestions WHERE EntityID = {$bankID} AND EntityTypeID = {$entityTypeID} AND isEnabled='1'";
        //echo $query;die;
        $res = $this->db->getRows($query);
        foreach ($res as $val) {
            $resArray[] = $val['QuestionID'];
        }
        echo "|" . implode($resArray, "||") . "|";
    }

    function getQuestionsCountInBank($bankID) {
        $res = $this->db->getSingleRow(" SELECT COUNT FROM `Banks` WHERE ID  = " . $bankID . " ");
        return $res['COUNT'];
    }
    
    function getMrqId($entityID, $entityTypeID, $questionID)
    {
        $query  = "SELECT ID FROM  MapRepositoryQuestions WHERE "
		. " QuestionID = ".$questionID." AND EntityID = ".$entityID." AND EntityTypeID =".$entityTypeID;
        $res = $this->db->getSingleRow($query);
        return $res['ID'];
    }       

}

?>
