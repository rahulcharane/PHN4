<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class Question extends Site
{

    public $entityTypeID;
    public $entityID;
    public $bankID;
    public $asmtID;
    public $bankList;
    public $asmtList;
    public $questTypesList;
    public $sectionList;

    /* variables for question version difference */
    public $str1 = '';
    public $str2 = '';
    public $flashstruct = '';
    public $array1 = '';
    public $array2 = '';
    public $refarr = '';
    public $result1 = '';
    public $result2 = '';
    public $key = '';
    public $labels = '';
    public $assetList = '';

    /**
     * constructs a new classname instance
     */
    function __construct()
    {
        parent::Site();
    }

    /**
     * Search Question from Home page
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @param    array  	$input
     *
     * @return   array         $questionResult
     *
     */
    function searchHomeList(array $input)
    {
        global $DBCONFIG;
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json = json_decode(stripslashes($input['jsoncrieteria']));
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : str_replace("'", "''", $input['search']);
        $searchtype = ($input['hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst."Count"' : $input['pgndc'] . ',qst."Count"';
        }
        else
        {
            $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst.Count' : $input['pgndc'] . ',qst.Count';
        }


        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $input['tags'];
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $input['taxonomies'];
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        $value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['ownerName'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['ownerName'];
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];


        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';

        
        $storeProcedureName='QuestionSearchList';
        $storeProcedureArray=array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, -1, -1, $this->session->getValue('userID'), $input['pgndc'], $tags, $taxo, $owner, $this->session->getValue('instID'), $key, $value, $difficulty, $template, $startdate, $enddate, $searchtype, $title_filter, $users_filter, $templates_filter, $date_filter, $difficulty_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter);
        
        //CALL `QuestionSearchList`(IN pOrderBy VARCHAR (50), IN pOrderDir VARCHAR (50), IN pLimitStart INT (10), IN pLimitStop INT (10), IN pFilter VARCHAR (500), IN pEntityID int, IN pEntityTypeID int, IN pUserID int, IN pDisplayFields VARCHAR (500), pTags VARCHAR(255), pTaxonomies VARCHAR(255),pOwnerName VARCHAR(255), pClientID INT, pKey VARCHAR(255), pValue VARCHAR(255), pDifficulty VARCHAR(25), pTemplateID VARCHAR(100),pStartDate VARCHAR(25), pEndDate VARCHAR(25))
        $questionResult = $this->db->executeStoreProcedure('QuestionSearchList', array($input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $search, -1, -1, $this->session->getValue('userID'), $input['pgndc'], $tags, $taxo, $owner, $this->session->getValue('instID'), $key, $value, $difficulty, $template, $startdate, $enddate, $searchtype, $title_filter, $users_filter, $templates_filter, $date_filter, $difficulty_filter, $tags_filter, $taxonomy_filter, $key_filter, $value_filter));
        // $questionResult['TC'] = $this->getValueArray($questionResult['RS'], "@QuestionIDCount");
        //unset($questionResult['RS'][$input['pgnstop']]);
        //  @array_pop($questionResult['RS']);  /*Commented in case if it is not in use.*/
        $questionlist = $questionResult['RS'];
        $qtp = new QuestionTemplate();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;
        if (!empty($questionlist))
        {
            foreach ($questionlist as $question)
            {
                $questionlist[$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
                $i++;
            }
        }
        $questionResult['RS'] = $questionlist;

        if ($searchtype == 'advanced')
        {
            $input['entityid'] = 0;
            $input['entitytypeid'] = 3;
            $input['spcall'] = $questionResult['QR'];
            $input['count'] = $questionResult['TC'];
            $input['storeProcedureArray']= json_encode($storeProcedureArray);
            $input['storeProcedureName']=$storeProcedureName;
            $this->saveAdvSearchCrieteria($input);
        }
        return $questionResult;
    }

    /**
     * Search List 
     *
     * @deprecated
     * @access   public
     * @param    array  	$input
     * @return   void
     *
     */
    function searchList(array $input)
    {
        $bank = new Bank();
        $asmt = new Assessment();
        $this->bankList = $bank->bankList($input, "bnk.ID !={$input['ID']}");
        $this->asmtList = $asmt->assessmentList($input, "ast.ID !={$input['ID']}");
        $this->asmtSecList = $asmt->AssessmentSecList($input, "ast.ID !={$input['ID']}");
        //echo "<pre>";print_r($this->asmtSecList);die('**********');
        $this->questTypesList = $this->questTypesList();
    }

    /**
     * Save New Question details
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   array         $res
     *
     */
    function save(array $input)
    {
        $queIDs = $input['queIDs'];
        $qtp = new QuestionTemplate();
        //For getting By default All template listing.
        $TemplateIds = $qtp->questionTemplateIdString();
        $input['Templates'] = $TemplateIds;

        switch ($input['rd'])
        {
            case 1:
                list($entityID, $entityTypeID) = $this->addBankQuestion($input);
                break;

            case 2:
                list($entityID, $entityTypeID) = $this->addAssessmentQuestion($input);
                break;
        }
        $res = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array($queIDs, $entityID, $entityTypeID, 0, 'ADDQST', $this->session->getValue('userID'), $this->currentDate(), $this->session->getValue('userID'), $this->currentDate()));
        $repositoryid = $this->getValueArray($res, 'Total_RepositoryID');
        $questionid = $this->getValueArray($res, 'Total_QuestionID');

        //Manage Inherited metadata.
        if ($input['isInheritMetaData'] == 1)
        {
            $input["entityID"] = $entityID;
            $input["EntityTypeID"] = $entityTypeID;
            $input['RepositoryIds'] = $repositoryid;
            $input['AllQuestIds'] = $questionid;

            $metadata = new Metadata();
            $metadata->inheritMetadata($input);
        }
        $this->questionActivityTrack($repositoryid, "Added");
        return $res;
    }

    /**
     * Save Selected Question to Bank or Assessment
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   array         $data
     *
     */
    function assignQuestSave($input)
    {
        //Site::myDebug("--------assignQuestSaveAkhlack");
        //Site::myDebug($input);
        
      

        $qt = $input["qt"];
        $entityid = $input["ID"];
        $entitytypeid = $input["eID"];
        $questid = $input["QuestID"];
        $sectid = $input["secID"];
        $tags = $input["Tags"];
        
        
        $this->createTagRunTime($tags); // This will create tag runtime
        
        

        $taxonomyNodeIds = $input["taxonomyNodeIds"];
        
       
        $entityName = $input["EntityName"];
        $flag = 0;
        if ($entityid == 0)//when user create new bank and assessment
        {
            $auth = new Authoring();
            if ($entitytypeid == 1)
            {
                $bank = new Bank();
                $input["BankName"] = $entityName;
                $entityid = $bank->save($input);
                //$auth->copyCss($entityid,1);
            }
            else if ($entitytypeid == 2)
            {
                $asmt = new Assessment();
                $input["AssessmentName"] = $entityName;
                $entityid = $asmt->save($input);

                //$auth->copyCss($entityid,2);
            }
            $flag = 1;
        }
        else
        {
            if (isset($input['TemplId']))// when user click on save and template and save question from template popup
            {
                $this->templateAndQuestionSave($input['TemplId'], $questid, $entitytypeid, $entityid);
            }
        }
        $data = $this->questVerify($questid, $entityid, $entitytypeid);
        Site::myDebug('-----------data');
        Site::myDebug($data);
        
        if ($data['QID'] != "")
        {
            $this->addQuestionandClassification($data['QID'], $entityid, $entitytypeid, $sectid, $tags, $taxonomyNodeIds, $input['isInheritMetaData'], $input);
           /*== This Code will rearrange The Question Sequence under the  section ==*/
            if( $sectid != 0 && $sectid != '' ){
                $assessment = new Assessment();
                $assessment->questionSequenceRearrange($sectid);
            }
            
            /*====================================================*/
        }
        if ($data['temp_quest'])
        {
            return $data;
        }
        return $entityid;
    }

    /**
     * Assign Question template for a question
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @param    string  	$TemplId
     * @param    integer  	$QuId
     * @param    integer  	$eID
     * @param    integer  	$ID                 
     *
     * @return   array         $res
     *
     */
    function templateAndQuestionSave($TemplId, $QuId, $eID, $ID)
    {
        $result = $this->db->executeStoreProcedure('MapQuestionTemplateList', array($ID, $eID), 'details');
        $Templates = ($eID == 1) ? $result['BankTemplates'] : $result['AssessmentTemplates'];
        if (!empty($Templates))
        {
            $TemplId = $TemplId . "," . $Templates;
        }
        $TemplId = trim($TemplId, ',');
        $dataArray = array(
            $TemplId,
            $ID,
            $eID,
            $this->session->getValue('userID'),
            $this->currentDate()
        );
        $res = $this->db->executeStoreProcedure('MapQuestionTemplatesManage', $dataArray);
      
        return $res;
    }
    

    /**
     * Add Classification and Metadata to question(s)
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @param    integer  	$questid
     * @param    integer  	$entityid
     * @param    integer  	$entitytypeid
     * @param    integer  	$sectid
     * @param    string  	$tags
     * @param    string  	$taxonomyNodeIds
     * @param    boolean  	$isInherit
     * @param    array  	$input
     *
     * @return   void
     *
     */
    function addQuestionandClassification($questid, $entityid, $entitytypeid, $sectid = 0, $tags = '', $taxonomyNodeIds = '', $isInherit = 0, $input)
    {
      
        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
            $questid,
            $entityid, $entitytypeid, $sectid, 'ADDQST', $this->session->getValue('userID'),
            $this->currentDate(), $this->session->getValue('userID'),
            $this->currentDate()), 'nocount');
        $this->myDebug('##$#$ addQuestionandClassification  #$#$##');
        $this->myDebug($input);
        $this->myDebug($result);

        $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');
        $questionid = $this->getValueArray($result, 'Total_QuestionID');
       /*
        * This chunk of code is used for copy metadata for add existing question :: Akhlack
        */
        if( trim( $input['AddQuestionSource'] ) == 'add-existing' ){
            
            $questionIds            = $input['QuestID'];
            $sourceRepoSql          = "SELECT MIN(ID) as ID FROM MapRepositoryQuestions WHERE QuestionID IN (".$questionIds.") AND isEnabled = '1' GROUP BY QuestionID";            
            $sourceRepo             = $this->db->getRows( $sourceRepoSql );

            $destinationRepoSql     = "SELECT MAX(ID) as ID FROM MapRepositoryQuestions WHERE QuestionID IN (".$questionIds.") AND isEnabled = '1' GROUP BY QuestionID";            
            $destinationRepo        = $this->db->getRows( $destinationRepoSql );

            $sourceDestinationRepoId = array();
            foreach( $sourceRepo as $key => $val ){
                array_push( $sourceDestinationRepoId ,$val['ID'].'#'.$destinationRepo[$key]['ID']  );
            }

            $sourceDestinationRepoIdStr =  implode($sourceDestinationRepoId,',');
            $this->duplicateQuestionsMetaData($sourceDestinationRepoIdStr);
              
        }
       
        // 
        if ($repositoryid == "")
        {
            $repositoryid = $input['RepoID'];
        }

        if ($repositoryid != "")
        {
            //Manage Inherited metadata.
            if ($isInherit == 1)
            {
                $input["EntityID"] = $entityid;
                $input["EntityTypeID"] = $entitytypeid;
                $input['RepositoryIds'] = $repositoryid;
                $input['AllQuestIds'] = $questionid;
                $metadata = new Metadata();
                //$metadata->inheritMetadata($input);
            }
            //
            
            $this->questionActivityTrack($repositoryid, "Added");
            $repositoryids = explode(",", $repositoryid);
            $cls = new Classification();
           
          // // if (!(trim($tags) == '' && trim($taxonomyNodeIds) == ''))
            
            if( is_array( $tags )){  
                $tags = implode(",",$tags);
                $tags = rtrim($tags,",");
            }
            if( is_array( $taxonomyNodeIds ) ){  
                $taxonomyNodeIds    = implode(",",$taxonomyNodeIds);
                $taxonomyNodeIds = rtrim($taxonomyNodeIds,",");
            }
                        
            for ($i = 0; $i < count($repositoryids); $i++)
            {

                $result = $cls->manageClassification($repositoryids[$i], '3', $tags, $taxonomyNodeIds);

            }
            
            
            
            
            
            
            if (count($repositoryids) > 0)
            {
                $this->metadata = new Metadata();
                $input['strRepositoryID'] = $repositoryid;
                for ($i = 0; $i < count($repositoryids); $i++)
                {
                    $this->metadata->assignedMetadata($input, $repositoryids[$i], '3');
                }
                /* $this->metadata->saveMetadata($input, $repositoryid, '3' );
                  $this->metadata->manageQuestionMetadata($input); */
            }
        }
    }

    /**
     * Saves New Question 
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @param    array  	$input
     * @return   integer       $questid 
     *
     */
    function newQuestSave(array $input)
    {
        //echo "INSIDE function newQuestSave == <pre>";print_r($input);die('-------------------');
        global $DBCONFIG;
        global $userID;
        
        $PreviousQuestionId=0;
        if( $input['RepoID'] != ""){
            $PreviousQuestionId = $input['QuestID'];
        }
        
        
        
        
        
        
        
       // $questtitle = $input["Title"];
        $questtitle = $input["advJSONData"];
        $questtitle = json_decode($questtitle, true);
        $questtitle = $questtitle['question_title'];
        $questtitle = $questtitle['text'];
        Site::myDebug('Question Title');
        Site::myDebug( $questtitle);
        
        $auth = new Authoring();

        //$questtitle     = strip_tags(html_entity_decode(html_entity_decode($questtitle)));
        $questjson = ($input["advJSONData"] == "" ) ? "NA" : $input["advJSONData"];
        $oldQuestJson = ($input["JSONData"] == "" ) ? "NA" : $input["JSONData"];
        //Code for Add/Edit difficuilty and Learning Object
        $objJSONtmp = new Services_JSON();
        // $questjson = str_replace("&nbsp;", " ", $questjson); -- Code Commited BY Akhlack
        $questjson = str_replace("'", "&#39;", $questjson);
	$questjson = str_replace("&amp;nbsp;", " ", $questjson);		
	$questjson = str_replace("&amp;amp;nbsp;", " ", $questjson);
        $questjson = str_replace('&amp;#34;', "&#34;", $questjson);
        $questjson = str_replace("'", "&#39;", $questjson);
        $objJson2 = $objJSONtmp->decode($questjson);
        $this->myDebug('#$#$  $objJson2 #$#');
        $this->myDebug($objJson2);
         /*===========*/
        if(empty($questtitle)){
            $questtitle = $objJson2->{'question_title'}->{'text'};
        }
        /*===========*/
        $cnt_obj_json_hspt = count($objJson2->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'});
        for ($cnt_ch = 0; $cnt_ch < $cnt_obj_json_hspt; $cnt_ch++)
        {
            if (strpos($objJson2->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'}[$cnt_ch]->{'label'}, "&quot;"))
            {
                $objJson2->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'}[$cnt_ch]->{'label'} = htmlspecialchars($objJson2->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'}[$cnt_ch]->{'label'});
            }
            else
            {
                
            }
        }

        // for sic neha
        $cnt_obj_json_con = count($objJson2->{'containers'});
        for ($cnt_con = 0; $cnt_con < $cnt_obj_json_con; $cnt_con++)
        {
            if (strpos($objJson2->{'containers'}[$cnt_con]->{'val2'}, "&quot;"))
            {
                $objJson2->{'containers'}[$cnt_con]->{'val2'} = htmlspecialchars($objJson2->{'containers'}[$cnt_con]->{'val2'});
            }
            else
            {
                
            }
        }

        $cnt_obj_json_ch = count($objJson2->{'choices'});
        for ($cnt_ch = 0; $cnt_ch < $cnt_obj_json_ch; $cnt_ch++)
        {
            if (strpos($objJson2->{'choices'}[$cnt_ch]->{'val2'}, "&quot;"))
            {
                $objJson2->{'choices'}[$cnt_ch]->{'val2'} = htmlspecialchars($objJson2->{'choices'}[$cnt_ch]->{'val2'});
            }
            else
            {
                
            }
        }

        //$questtitle = 	$objJson2 ->question_title->text;
        // $objJson2 ->question_title->text = $this->replaceQuote(stripslashes($auth->hashCodeToHtmlEntity( $objJson2 ->question_title->text )));
//echo "============== 11111111 == <pre>";print_r($objJson2->{'metadata'});
        if (isset($objJson2->{'metadata'}))
        {
            $difficulty = ($input['Difficulty'] == '') ? "easy" : (string) $input['Difficulty'];
            $RepoID = $input['RepoID'];
            $this->myDebug($objJson2);
            if (!isset($objJson2->{'metadata'}[1]->{'text'}))
            {
                $objJson2->{'metadata'}[1]->{'text'} = 'Difficulty';
                $objJson2->{'metadata'}[1]->{'val'} = $difficulty;
            }
            //$objJson2->{'metadata'}[1]->{'val'} = $difficulty;
        }
//echo "============== 22222222 == <pre>";print_r($objJson2->{'metadata'});die('-------------------');
        if ($objJson2)
        {
            $questjson = $objJSONtmp->encode($objJson2);
        }

        $questxml = ($input["XMLData"] == "" ) ? "NA" : $input["XMLData"];
        $userID = ($input["UserID"] == "" ) ? $this->session->getValue('userID') : $input["UserID"];
        $qt = $input["QuestionTemplateID"];
        $questid = $input["QuestID"];
        $questid = $oldQuestID = ($questid != "") ? $questid : "";
        $rid = $input["RID"];
        if ($rid != 0)
        {
            $this->questionActivityTrack($rid, "Edited", $userID);
        }
        if ($questjson != "NA")
        {
             // during video saving cursor is coming in this section --Manish
            //echo "<pre>"; print_r($questjson); echo "</pre>";
            $questionjson = $this->addMediaPlaceHolder($questjson);
            $questionjson = preg_replace('/\s+/', ' ', $questionjson);
            //echo "----<pre>"; echo $questionjson; echo "</pre>";die('after coming back from addMediaPlaceHolder');
        }
        if ($questxml != "NA")
        {   
            $questiontxml = $this->addMediaPlaceHolder($questxml);
            $questiontxml = str_replace("'", '"', $questiontxml);
        }

//echo "INSIDE function newQuestSave Before executeStoreProcedure QuestionManage == <pre>";print_r($questionjson);die('-------------------');

        $result = $this->db->executeStoreProcedure('QuestionManage', array(intval($questid), "1.0", $questtitle, $questiontxml, $oldQuestJson, $questionjson, $qt, $userID, $this->currentDate(), "HTML", strtolower($difficulty), "1"), "details");
        //$questid = $this->getValueArray($result, 'QuestID');
        $questid = $result['QuestID'];
        $this->myDebug('$questID1===>');
        $this->myDebug($questid);
        $this->myDebug('$questID stop ===>');
        //$questid = ($oldQuestID > 0)?$oldQuestID : $questid;

        if ($rid > 0)
        {
            $this->updateRepositoryID($questid, $rid);
            $this->db->executeStoreProcedure('AutoUpdateEntities', array($oldQuestID, $questid, $rid));
        }

        $qtp = new QuestionTemplate();

        if ($DBCONFIG->dbType == 'Oracle')
        {
            $QuestionTemplate = $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and mqt.\"ID\" = ''{$qt}'' ", " qt.\"RenditionMode\" ", 'details');
        }
        else
        {
            $QuestionTemplate = $qtp->questionTemplate(" qt.isDefault = 'Y' and mqt.ID = '{$qt}' ", "qt.RenditionMode", 'details');
        }

        if ($QuestionTemplate["RenditionMode"] != "Html" && $questxml == "NA")
        {
            $questxml = $auth->getJsonToXml($questid, stripslashes($questjson), $qt);
            if (trim($questxml) != 'NA')
            {
                $sXMLInput = stripslashes($questxml);
                $ipDoc = simplexml_load_string($sXMLInput);
                $sXMLInput = $ipDoc->asXML();
                $sXMLInput = trim(preg_replace('/<\?xml.*\?>/', '', $sXMLInput, 1));
                $sXMLInput = $this->addMediaPlaceHolder($sXMLInput);
                $sXMLInput = str_replace("\'", "''", addslashes(stripslashes($sXMLInput)));
                //$this->db->executeClobProcedure("SETQUESTIONXML", array($questid, stripslashes($sXMLInput))); -- Commented by Akhlack
            }
        }

        if ($QuestionTemplate["RenditionMode"] != "Flash" && $questjson == "NA" && $QuestionTemplate["EditMode"] != "Offline")
        {
            $questjson = $auth->getXmlToJson($questid, $questxml, $qt);
            $questjson = $this->addMediaPlaceHolder($questjson);
            // $status     = $this->db->update("Questions",array("JSONData"=>$questjson),array("ID"=>$questid));
            $sXMLInput = str_replace("'", '"', $sXMLInput);
            //$this->db->executeClobProcedure("SETQUESTIONXML", array($questid, stripslashes($sXMLInput)));  -- Commented by Akhlack
        }

        $map[$questid] = $this->updateMediaCount($this->addMediaPlaceHolder($questjson), 'json');
        $this->mapQuestionMedia($map,$PreviousQuestionId);
        // Update Question ID STatus
        if ($questid)
        {
            // Update Question Chdcked Status 
            $auth->updateQuestionStatus($questid, $oldQuestID, $rid);
        }
        return $questid;
    }

    function addMediaPlaceHolder($questionContent)
    {
        //echo "<BR><BR>##### "; print_r($questionContent);
        
        $this->mydebug("---------video--support");
        $this->mydebug($questionContent);
        
        
        //$questionContent = str_replace('&#160;','&amp;#160;',$questionContent);
        //$questionContent = stripslashes(html_entity_decode($questionContent));
        
         
        if (preg_match("/^{/i", $questionContent))
        {
           //echo 'hi-0';
            //$questionContent = preg_replace_callback('/img[^>]+}/i',Array(&$this,"replace_media"),$questionContent);			
            //$questionContent = html_entity_decode($questionContent, ENT_QUOTES, 'UTF-8');
            //echo "<BR><BR>##### ".$questionContent; 
            $questionContent = preg_replace_callback("/<img[^>]+>/i", Array(&$this, "replace_media"), $questionContent);
            //$questionContent = preg_replace_callback("/<video[^>]+>/i", Array(&$this, "replace_media"), $questionContent);
            //$questionContent = preg_replace_callback('#<video[^>]+>(.*?)<\/video>#s', Array(&$this, "replace_media"), $questionContent);
            $questionContent = preg_replace_callback('~<video[^>]+>(.*?)<\\\/video>~i', Array(&$this, "replace_media"), $questionContent);
            
            $this->mydebug("---------after -preg- video--support-start");
            $this->mydebug($questionContent);
            $this->mydebug("---------after -preg- video--support-end");
        }
        elseif (preg_match("/^</i", $questionContent))
        {
            // echo 'hi-10';
            $questionContent = preg_replace_callback('/<a[^>]+>(.*?)<\/a>/i', Array(&$this, "replace_media"), $questionContent);
            $questionContent = preg_replace_callback('/<img[^>]+>/i', Array(&$this, "replace_media"), $questionContent);
            //$questionContent = preg_replace_callback("/<video[^>]+>/i", Array(&$this, "replace_media"), $questionContent);
            //$questionContent = preg_replace_callback("#<video[^>]+>(.*?)<\/video>#s", Array(&$this, "replace_media"), $questionContent);
            $questionContent = preg_replace_callback('~<video[^>]+>(.*?)<\\\/video>~i', Array(&$this, "replace_media"), $questionContent);
            
        }
        else
        {
            //echo 'hi-20';
            $questionContent = preg_replace_callback('/<a[^>]+>(.*?)<\/a>/i', Array(&$this, "replace_media"), $questionContent);
            $questionContent = preg_replace_callback("/<img[^>]+>/i", Array(&$this, "replace_media"), $questionContent);
           // $questionContent = preg_replace_callback("/<video[^>]+>/i", Array(&$this, "replace_media"), $questionContent);
                                                        
            //$questionContent = preg_replace_callback("#<video[^>]+>(.*?)<\/video>#s", Array(&$this, "replace_media"), $questionContent);
            $questionContent = preg_replace_callback('~<video[^>]+>(.*?)<\\\/video>~i', Array(&$this, "replace_media"), $questionContent);
        }
//        print "<pre>";
//        print_r($questionContent);
//        die('akh');
         
        return $questionContent;
    }

    function replace_media($matches)
    {

        
        global $userID, $DBCONFIG;

        $qtimgdetail = $this->assetList;
        if (is_array($matches))
        {
            foreach ($matches as $key => $value)
            {
                $matches[$key] = stripslashes(html_entity_decode($value));
            }
        }

        //For audio Support I remove the Code
        /* if($this->cfg->S3bucket) {
          $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $matches[0], $matches);
          $first_img = $matches [1] [0];
          $first_img=  explode('?', $first_img);
          $matches[0]='<img src="'.$first_img[0].'" />';
          } */

        preg_match('/MathMLtoImage.php/i', $matches[0], $matmlRes);
        if (!empty($matmlRes))
        {
            preg_match('/(src)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res);
            $mathmlarr = explode('?mml=', $res[3]);
            return urldecode($mathmlarr[1]);
        }
        else
        {
           // preg_match('/(title)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res); -- Comment By Akhlack;
            preg_match('/(data-metadata)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res);
            preg_match('/(width)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res_w);
            preg_match('/(height)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res_h);
            //echo "<pre>";print_r($res_w);print_r($res_h);

            if (!empty($res))
            {
                //echo "<pre>===";print_r($res[3]);echo "<br>";
                preg_match_all('/{(.*?)}/', $res[3], $matches);
                //preg_match_all('/\{([^}]*)\}/', $res[3], $matches);
                //print_r($matches);
                $arr_img = explode(",", $matches[1][0]);
                $cnt = count($arr_img);
                
                ############### Code Added By Akhlack -> Remove Duplicate asset_width  , asset_height ###########
                foreach( $arr_img as $key=>$val ){
                   $find_width     = strpos( $val, 'asset_width' );
                   $find_height    = strpos( $val, 'asset_height' );
                    if( $find_width === false ){
                        
                    }else{
                         unset($arr_img[$key]);
                         $find_width=false;
                    }
                    if( $find_height === false ){
                        
                    }else{
                         unset($arr_img[$key]);
                         $find_height=false;
                    }
                }
                #############################################################


                if ($res_w[0])
                {
                    $arr_img[$cnt] = "'asset_width':'" . $res_w[3] . "'";
                }
                $cnt++;
                if ($res_h[0])
                {
                    $arr_img[$cnt] = "'asset_height':'" . $res_h[3] . "'";
                }
                //print_r($arr_img);

                $res[3] = $str_img = "{" . implode(",", $arr_img) . "}";
                //echo "<pre>===";print_r($str_img);echo "<br>";

                return "___ASSETINFO" . urldecode($res[3]) . "___";
            }
            else
            {
                preg_match('/(src|href)=([\'|\"])([^"|^\']*)(([\'|\"])\s?)/i', $matches[0], $res);


                if (!empty($qtimgdetail))
                {
                    foreach ($qtimgdetail as $imgdt)
                    {
                        if (strpos($res[3], $imgdt['OriginalFileName']))
                        {
                            $res[3] = str_replace($imgdt['OriginalFileName'], $imgdt['FileName'], $res[3]);
                            break;
                        }
                    }
                }

                $userID = ($userID != "" ) ? $userID : $this->session->getValue('userID');
                $filenames = preg_split("/\//", $res[3]);
                $fileName = $filenames[count($filenames) - 1];
                if ($DBCONFIG->dbType == 'Oracle')
                {
                    $query = "SELECT \"ID\",\"FileName\",\"ContentType\",\"ContentSize\",\"ContentHeight\",\"ContentWidth\", ClientID({$userID}) as \"inst_id\" from  Content WHERE \"FileName\" = '$fileName' ";
                }
                else
                {
                    $query = "SELECT ID,FileName,ContentType,ContentSize,ContentHeight,ContentWidth, ClientID({$userID}) as inst_id from  Content WHERE fileName = '$fileName' ";
                }
                $result = $this->db->getRows($query);
                if (!empty($result))
                {
                    $result = $result[0];
                    $asset_type = strtolower($result['ContentType']);
                    $jsonStr = "{\"asset_id\":{$result['ID']},\"inst_id\":{$result['inst_id']},\"asset_name\":\"{$result['FileName']}\",\"asset_type\":\"{$asset_type}\",\"asset_other\":\"\"}";
                    //return "___ASSETINFO" . $jsonStr . "___";
                   
                   return "___ASSETINFO" . str_replace("\"", "&#39;", stripslashes($jsonStr)) . "___";
                }
                else
                {
                    return $matchesp[0];
                }
            }
        }
    }

    function replace_info($matches)
    {
        global $questionInput;
        $str_decode = html_entity_decode($matches[3], ENT_QUOTES, "UTF-8");
        $str_decode = str_replace(array("'", "&quot;"), "\"", $str_decode);
        //stripslashes(html_entity_decode($matches[3]));//$matches[3];//html_entity_decode($matches[3],ENT_QUOTES,"UTF-8");

        $objJSONtmp = new Services_JSON();
        $objJson2 = $objJSONtmp->decode($str_decode);

        $assetPath = $this->cfg->wwwroot . "/";
        $viewTitle = addslashes($str_decode);


        $viewTitle = str_replace("\"", "&#39;", stripslashes($str_decode));
//var_dump($viewTitle);		
        $flashpath = $this->cfg->wwwroot . "/views/templates/default/images/flash.gif";
        $transpath = $this->cfg->wwwroot . "/views/templates/default/images/trans.gif";
        if ($questionInput == 'json')
        {
            if (strtolower($objJson2->asset_type) == 'image')
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/images/original/' . $objJson2->asset_name;

                if ($this->cfg->S3bucket)
                {
                    $fileUrlPath = str_replace($this->cfg->wwwroot . '/', "", $assetPath);
                    $assetPath = s3uploader::getCloudFrontURL($fileUrlPath);
                }
               ######################################################################
                $testViewTitle = $viewTitle;
                $objJSONtmp = new Services_JSON();
                $testViewTitle = html_entity_decode($testViewTitle, ENT_QUOTES, "UTF-8");
                $testViewTitle = str_replace(array("'", "&quot;"), "\"", $testViewTitle);
                $objAstInfo = $objJSONtmp->decode($testViewTitle);
              
               /* $testViewTitle = ltrim($testViewTitle,'{');
                $testViewTitle = rtrim($testViewTitle,'}');
                $assetInfo= explode(",",$testViewTitle);
                
                foreach( $assetInfo as $k=>$val ){
                     $width = strpos( $val, 'asset_width' );
                     $height = strpos( $val, 'asset_height' );
                     if( $width === false){
                         
                     }else{
                         $img_width = end(explode(":",$val));
                         $img_width = html_entity_decode($img_width, ENT_QUOTES);
                         $img_width = ltrim($img_width,"'");
                         $img_width = rtrim($img_width,"'");
                         //$img_width = substr(,1,-1);
                         $width=false;
                     }
                     if( $height === false){
                         
                     }else{
                         $img_height = end(explode(":",$val));
                         $img_height = html_entity_decode($img_height, ENT_QUOTES);
                         $img_height = ltrim($img_height,"'");
                         $img_height = rtrim($img_height,"'");
                         //$img_width = substr(,1,-1);
                         $height=false;
                     }
                }*/
                $img_width  = $objAstInfo->asset_width;
                $img_height = $objAstInfo->asset_height;

                return "<img alt='" . $objJson2->alt_tag . "'  src='{$assetPath}' title='" . $objJson2->alt_tag . "' data-metadata='".$viewTitle."' height='".$img_height."' width='".$img_width."' />";
                
                ##############################################################################
            }
            elseif (strtolower($objJson2->asset_type) == 'audio' || strtolower($objJson2->asset_type) == 'file')
            {

                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/audios/' . $objJson2->asset_name;

                if ($this->cfg->S3bucket)
                {
                    $fileUrlPath = str_replace($this->cfg->wwwroot . '/', "", $assetPath);
                    $assetPath = s3uploader::getCloudFrontURL($fileUrlPath);
                }
                return "<img alt='" . $objJson2->alt_tag . "' class='mediaClass videoAsset' width='100' height='100' src='{$flashpath}'  title=" . $objJson2->alt_tag . " data-metadata='".$viewTitle."'    mce_src='{$flashpath}' style='background:url('" . $transpath . "') no-repeat' />";


                //return  "<img class='mediaClass videoAsset' width='200' height='200' src='{$flashpath}' title='{$viewTitle}' mce_src='{$flashpath}' style='background:url('". $transpath . "') no-repeat'  />";
            }
            else if (strtolower($objJson2->asset_type) == 'video')
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/videos/original/' . $objJson2->asset_name;
                 if ($this->cfg->S3bucket)
                {
                    $fileUrlPath = str_replace($this->cfg->wwwroot . '/', "", $assetPath);
                    $assetPath = s3uploader::getCloudFrontURL($fileUrlPath);
                }
               // return '<object width=&quot;200&quot; height=&quot;200&quot; data=&quot;' . $assetPath . '&quot; type=&quot;application/x-shockwave-flash&quot; title=&quot;' . $viewTitle . '&quot;><param name=&quot;src&quot; value=&quot;' . $assetPath . '&quot; /></object>';
                
                
                 $this->mydebug($assetPath);
        $this->mydebug("---------video--support-newadded-matches-assetPath-karate");
        $this->mydebug( "<video controls='controls' height='150' width='150' poster='".$assetPath."' title='" . $viewTitle . "'><source src='".$assetPath."' type='video/mp4' /></video>");
                return "<video controls='controls' height='150' width='150' poster='".$assetPath."' title='" . $viewTitle . "'><source src='".$assetPath."' type='video/mp4' /></video>";
                
                /*
                  if(!isset($objJson2->asset_other)){
                    $imgSrc = $this->registry->site->cfg->wwwroot."/views/templates/default/images/video_not_available_new.jpg";
                    return "<img src='{$imgSrc}' title=" . $viewTitle . " />";
                }else{
                    return "<img src='{$objJson2->asset_other}' title=" . $viewTitle . " />";
                }
                */
                
            }
            /* else if(strtolower($objJson2->asset_type)=='audio' )
              {
              $assetPath .= $this->cfgApp->PersistDataPath.$objJson2->inst_id.'/assets/audios/'.$objJson2->asset_name;
              return '<object width=&quot;200&quot; height=&quot;200&quot; data=&quot;'.$assetPath.'&quot; type=&quot;application/x-shockwave-flash&quot; title=&quot;'.$viewTitle.'&quot;><param name=&quot;src&quot; value=&quot;'.$assetPath.'&quot; /></object>';
              } */
            else
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/' . $objJson2->asset_type . '/' . $objJson2->asset_name;
                return "<img src='{$assetPath}' title=" . $objJson2->alt_tag . " data-metadata='".$viewTitle."' />";
            }
        }
        else if ($questionInput == 'xml')
        {
            if (strtolower($objJson2->asset_type) == 'image')
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/images/original/' . $objJson2->asset_name;
            }
            else if (strtolower($objJson2->asset_type) == 'video')
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/videos/original/' . $objJson2->asset_name;
            }
            else
            {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/' . $objJson2->asset_type . '/' . $objJson2->asset_name;
            }
            return "<a href='{$assetPath}' title='" . urlencode($matches[3]) . "'>view</a>";
        }
        $questionInput = "";
    }

    function removeMediaPlaceHolder($questionContent)
    {
        //echo "<pre>";print_r($questionContent);
        global $DBCONFIG;
        $questionContent = trim($questionContent, "'");
        if ($DBCONFIG->dbType == 'Oracle')
            $questionContent = str_replace("''", '"', $questionContent);
        global $questionInput;
        //$this->myDebug('inside remove media place holder');
        //print_r($questionInput);echo "<br>";
        //print_r($questionContent);
        if (preg_match("/^{/i", $questionContent))
        {
            if ($DBCONFIG->dbType == 'Oracle')
                $questionContent = str_replace('\"', '"', $questionContent);
            $questionInput = 'json';
        }elseif (preg_match("/^</i", $questionContent))
        {
            $questionInput = 'xml';
        }
        $this->myDebug('$questionInput');
        $this->myDebug($questionInput);
        $this->myDebug($questionContent);
        $questionContent = preg_replace_callback('/___(ASSETINFO)("?)([^}]*})("?)___/i', Array(&$this, "replace_info"), $questionContent);
        
        //Site::myDebug('calling QQRRR');
        //Site::myDebug( $questionContent);  
        return $questionContent;
    }

    /**
     * Updates the Question ID of a repository
     *
     * @access   public
     * @param    $questid    int
     * @param    $rid        int
     * @return   integer
     *
     */
    public function replace_mmml($matches)
    {
        return "<img src='" . $this->registry->site->cfg->wwwroot . "/plugins/editor/ext2.2/Widgets/Editor/plugins/mathml/MathMLtoImage.php?mml=" . urlencode($matches[0]) . "' />";
    }

    function mmlToImg($questionContent)
    {
        $questionContent = preg_replace_callback('/<math[^>]*>(.*?)<\/math>/i', Array(&$this, "replace_mmml"), $questionContent);
        return $questionContent;
    }

    function updateRepositoryID($questid, $rid)
    {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  UPDATE MapRepositoryQuestions SET \"QuestionID\" = {$questid} WHERE \"ID\" = $rid ";
        }
        else
        {
            /*==== For MapQuestionContent Table isEnabled 0 Asset Usage ====*/
            $CheckQry              = "SELECT QuestionID From MapRepositoryQuestions WHERE  ID = '".$rid."' ";
            $res                   = $this->db->getRows( $CheckQry );
            $prevoiusQuestionId    = $res[0]['QuestionID'];
             
            if( $prevoiusQuestionId > 0 ){
                
                /* Check duplicate for add existing question */
                    $csql           = 'SELECT count(*) as CNT from MapRepositoryQuestions WHERE QuestionID = "'.$prevoiusQuestionId.'" AND isEnabled=1';
                    $result         = $this->db->getRows( $csql );
                    $this->myDebug("Check duplicate for add existing question");
                    $this->myDebug($result);
                    
                    
                    if($result[0]['CNT'] == '1'){
                        $query = "UPDATE MapQuestionContent set isEnabled = '0' WHERE QuestionID = '".$prevoiusQuestionId."' ";
                        $this->db->execute($query);
                    }
                    /* End*/
                    
                
                
//                $query = "UPDATE MapQuestionContent set isEnabled = '0' where QuestionID = '".$prevoiusQuestionId."' ";
//                $this->db->execute($query);
                
                
                
                $query = "UPDATE MapQuestionContent set isEnabled = '1' WHERE QuestionID = '".$questid."' ";
                $this->db->execute($query);
                
                
            }
            
            /*=========*/
            
            $query = "  UPDATE MapRepositoryQuestions SET QuestionID = {$questid} WHERE ID = $rid ";
        }

        return $this->db->execute($query);
    }

    /**
     * Updates the media count of a question
     *
     * @access   public
     * @abstract
     * @static
     * @global	  string(JSON) 	$data
     * @param    string  	$type
     * @return   integer       
     *
     */
    function updateMediaCount($data, $type = 'json')
    {
        $media = array();
        $data = html_entity_decode($data);
        return $this->getMediaId($this->getUsedFields($data));
    }

    /**
     * Extract image data and maintains their count from JSON
     *
     * @access   public
     * @abstract
     * @static
     * @global	  string(JSON) 	$data
     * @return   array         
     *
     */
    function getUsedFields($data)
    {

        $result = array();
        $media = array();
        $json = new Services_JSON();
        $elements = $json->decode(stripslashes($data));

        if (!empty($elements))
        {
            foreach ($elements as $element => $value)
            {
                if (is_array($value))
                {
                    if (!empty($value))
                    {
                        foreach ($value as $key => $val)
                        {
                            if (is_object($val))
                            {
                                if (!empty($val))
                                {
                                    foreach ($val as $k => $v)
                                    {
                                        
                                            $v = html_entity_decode($v,ENT_QUOTES); 
                                             $this->myDebug("AAAAAA");
                                            $this->myDebug($v);
                                        @preg_match_all('/___(ASSETINFO)("?)([^}]*})("?)___/', $v, $media);
                                        $cnt = count($media) - 2;
                                        if (!empty($media[$cnt]))
                                        {
                                            //$result[$element][$key][$k] = $media[$cnt];
                                            if( $element == 'choices'){
                                                $keyy = $element.'-'.$k;
                                                $result[$keyy][$key][$k] = $media[$cnt];
                                            }else{
                                                $result[$element][$key][$k] = $media[$cnt];
                                            }
                                            $media = array();
                                        }
                                    }
                                }
                            }
                            else
                            {
                                
                                $this->myDebug("BBBBBBBBBBBBBB");
                                $this->myDebug($val);
                                $val = html_entity_decode($val,ENT_QUOTES);
                                @preg_match_all('/___(ASSETINFO)("?)([^}]*})("?)___/', $val, $media);
                                $cnt = count($media) - 2;
                                if (!empty($media[$cnt]))
                                {
                                    $result[$element][$key] = $media[$cnt];
                                    $media = array();
                                }
                            }
                        }
                    }
                }
                else
                {
                    //@preg_match_all('/___(ASSETINFO)("?)([^}]*})("?)___/', $value, $media);
                    
//                    $cnt = count($media) - 2;
//                    if (!empty($media[$cnt]))
//                    {
//                        $result[$element] = $media[$cnt];
//                        $media = array();
//                    }
                        if(is_object($value)){
                            foreach($value as $key => $objValue) {
                                $objValue = html_entity_decode($objValue,ENT_QUOTES);                                  
                                @preg_match_all('/___(ASSETINFO)("?)([^}]*})("?)___/', $objValue, $media);
                                $cnt = count($media) - 2;
                                if (!empty($media[$cnt]))
                                {
    //                                $this->myDebug("987456321-----****else999-balaram");
    //                                $this->myDebug($media);
                                    $result[$element] = $media[$cnt];
    //                                $this->myDebug("987456321-----****else999-result");
    //                                $this->myDebug($result);
                                    $media = array();
                                }
                            }
                        }
                }
            }
        }
        $this->myDebug("987456321-----result");
        $this->myDebug($result);
        //$this->myDebug("987456321-----aftre array unique result");
       // $this->myDebug(array_unique($result));
          $this->myDebug("987456321-----aftre format result");
        $this->myDebug($this->formatResult($result));
        //return $this->formatResult(array_unique($result));
        return $this->formatResult($result);
    }

    /**
     * It formats the data for internal use
     *
     * @access   public
     * @abstract
     * @static
     * @global	  array  	$data
     * @return   array         $result
     *
     */
    function formatResult(array $data)
    {
        $result = array();
        if (!empty($data))
        {
            foreach ($data as $key => $value)
            {
                if (is_array($value))
                {
                    foreach ($value as $index => $val)
                    {
                        if (is_array($val))
                        {
                            foreach ($val as $k => $v)
                            {
                                //$keyval = $this->createKey($key . ' ' . ($index + 1));
                                //$result[$keyval] = $v[0];
                                /* Code Added by Akhlack . For Multiple Image in Choice Field */
                                if(is_array($v)){                                   
                                    foreach ($v as $kk => $vv){
                                        if( count($v) > 1){
                                            $keyval = $this->createKey($key . ' ' . ($index + 1).' '.$kk);
                                        }else{
                                            $keyval = $this->createKey($key . ' ' . ($index + 1));
                                        }
                                        $result[$keyval] = $vv;
                                    }                                    
                                }else{
                                    $keyval = $this->createKey($key . ' ' . ($index + 1));
                                    $result[$keyval] = $v[0];
                                }
                                
                            }
                        }
                        else
                        {
                            //$key = $this->createKey($key);
                            $key_new = $this->createKey($key).'-'.$index; // Change BY Akhlack , For Multpile image , video in same field .
                            $this->myDebug("987456321-----inside format result block -key -1 ");
                            $this->myDebug($key_new);
                            $result[$key_new] = $val;
                              $this->myDebug("987456321-----inside format result block");
                            $this->myDebug($result);
                        }
                    }
                }
                else
                {
                    $key = $this->createKey($key);
                    $result[$key] = $value;
                }
            }
        }
        
       
        return $result;
    }

    /**
     * Creates heading from string key
     *
     * @access     public
     * @abstract
     * @static
     * @global     string  	$data
     * @return     string      $data
     *
     */
    function createKey($data)
    {
        $data = str_replace('_', ' ', $data);
        return ucwords($data);
    }

    /**
     * Gets ID for the given media filename
     *
     * @access   public
     * @abstract
     * @static
     * @global	  string  	$filepath
     * @return   integer       
     *
     */
    function getMediaList($filepath)
    {
        $media = new Media();
        $filepath = explode('/', $filepath);
        $filepath = $filepath[count($filepath) - 1];
        return $media->getIdByName($filepath);
    }

    /**
     * Get Media Files with their ID's 
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @return   array         $files
     *
     */
    function getMediaId(array $content)
    {
        $this->myDebug("This is MediaID");
        $this->myDebug($content);
        $media = new Media();
        $files = array();
        if (!empty($content))
        {
            foreach ($content as $key => $val)
            {
                $json = new Services_JSON();
                $mediaJson = $json->decode($val);
                $this->myDebug("This is question media json");
                $this->myDebug($mediaJson);
                if ($media->filterFileName($mediaJson->asset_name))
                {
                    $files[$key] = $mediaJson->asset_id;
                }
            }
        }
        $this->myDebug("This is question media array");
        $this->myDebug($files);
        return $files;
    }

    /**
     * It maps question with media data
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @return   void          
     *
     */
    function mapQuestionMedia(array $map,$PreviousQuestionId)
    {
        global $DBCONFIG;
        site::myDebug('printing map array');
        site::myDebug($map);
        $values = array();
        if (!empty($map))
        {
            foreach ($map as $questionID => $contentID)
            {
                if (!empty($contentID))
                {
                    foreach ($contentID as $key => $val)
                    {
                        $values[] = array(
                            'QuestionID' => $questionID,
                            'ContentID' => $val,
                            'UsedField' => $key,
                            'UserID' => $this->session->getValue('userID'),
                            'AddDate' => $this->currentDate(),
                            'ModBY' => $this->session->getValue('userID'),
                            'ModDate' => $this->currentDate(),
                            'isActive' => 'Y',
                            'isEnabled' => '1'
                        );
                        if ($DBCONFIG->dbType == 'Oracle')
                        {
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('ICONTENTCNT', $val));
                            $this->db->executeStoreProcedure('SYNCCOUNT', array('IQUESTCNT', $questionID));
                        }
                    }
                }
            }
        }
        if (!empty($values))
        {
            if ($DBCONFIG->dbType == 'Oracle')
            {
                $query = "UPDATE MapQuestionContent set \"isEnabled\" = '0' where \"QuestionID\" = $questionID ";
            }
            else
            {
                if( $PreviousQuestionId > 0 ){
                   // $query = "UPDATE MapQuestionContent set isEnabled = '0' where QuestionID = $PreviousQuestionId ";
                }
                
                //$query = "UPDATE MapQuestionContent set isEnabled = '0' where QuestionID = $questionID ";
            }

            $this->db->execute($query);
            $this->myDebug("This is multi insert");
            $this->myDebug($values);
            $this->db->multipleInsert('MapQuestionContent', $values);
        }
    }

    /**
     * Adds Question to a Bank.    
     *
     * @access     public
     * @abstract
     * @static
     * @global     
     * @return     array       $result 
     *
     */
    function addBankQuestion(array $input)
    {
        $bank = new Bank();
        $input['BankID'] = 0;
        $input['BankUsers'] = $this->session->getValue('userID');
        $input['BankTemplates'] = $input['Templates'];
        $result['entityID'] = $bank->save($input);
        $result['entityTypeID'] = $this->getEntityId('bank');
        return $result;
    }

    /**
     * Get Question Type List (MCSS, MCMS, etc) 
     *
     * @access     public
     * @abstract
     * @static
     * @global	  
     * @param    
     * @return     array
     *
     */
    function questTypesList()
    {
        $qtp = new QuestionTemplate();
        return $qtp->questionTemplate();
    }

    /**
     * Adds Question to a Assessment.
     *
     * @access   public
     * @abstract
     * @static
     * @global	  
     * @param    
     * @return   array     $result
     *
     */
    function addAssessmentQuestion()
    {
        $asmt = new Assessment();
        $input['AssessmentID'] = 0;
        $input['AssessmentUsers'] = $this->session->getValue('userID');
        $input['AssessmentTemplates'] = $input['Templates'];
        $result['entityID'] = $asmt->save($input);
        $result['entityTypeID'] = $this->getEntityId('assessment');
        return $result;
    }

    /**
     * Advance Search for Question
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @return   array         $questionResult
     *
     */
    function advSearch(array $input)
    {
        $EntityTypeID = $input["EntityTypeID"];
        $EntityID = $input["EntityID"];

        $SecID = $input["SecID"];
        $question_title = $input["question_title"];
        $qtype = $input["qtype"];
        $question_tags = $input["question_tags"];
        $sel_banks = $input["sel_banks"];
        $sel_quiz = $input["sel_quiz"];
        $taxonomy = $input["taxonomyNodeIds"];

        $questionResult = $this->db->executeStoreProcedure('QuestionSearch', array(
            $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'],
            $question_title,
            $sel_banks,
            $sel_quiz,
            $qtype,
            $question_tags,
            $taxonomy,
            $this->session->getValue('userID'),
            $this->session->getValue('instID'),
            $EntityID,
            $EntityTypeID
        ));

        $questionlist = $questionResult['RS'];
        Site::myDebug('------questionlist1');
        Site::myDebug($questionlist);
        $qtp = new QuestionTemplate();
        $templateLayouts = $qtp->templateLayout();

        $i = 0;
        if (!empty($questionlist))
        {
            foreach ($questionlist as $question)
            {
                $questionlist[$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
                $i++;
            }
        }
        $questionResult['RS'] = $questionlist;
        Site::myDebug('------questionlist2');
        Site::myDebug($questionResult);
        return $questionResult;
    }

    /**
     * Saves the "Question Advance Search" criteria.
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @return   mixed         
     *
     */
    function advQuestionSave(array $input)
    {
        $data = $this->questVerify($input['QuestIDs'], $input["EntityID"], $input["EntityTypeID"]);
        $userID = ($input["UserID"] == "" ) ? $this->session->getValue('userID') : $input["UserID"];
        $result = $this->db->executeStoreProcedure('MapRepositoryQuestionsManage', array(
            $data['QID'],
            $input["EntityID"],
            $input["EntityTypeID"],
            $input["SecID"], 'ADDQST', $userID,
            $this->currentDate(), $userID,
            $this->currentDate()), 'nocount');
        $repositoryid = $this->getValueArray($result, 'Total_RepositoryID');

        //Need to pass in Inherit metadata
        $questionid = $this->getValueArray($result, 'Total_QuestionID');

        //Manage Inherited metadata.
        if ($input['isInheritMetaData'] == 1)
        {
            $input['RepositoryIds'] = $repositoryid;
            $input['AllQuestIds'] = $questionid;
            $metadata = new Metadata();
            $metadata->inheritMetadata($input);
        }
        $this->questionActivityTrack($repositoryid, "Added", $userID);

        if ($data['temp_quest'])
        {
            return $data;
        }
        else
        {
            return false;
        }
    }

    /**
     * It Tracks actvity related to Question
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  	$repositoryids
     * @param    string  	$action
     * @param    integer  	$userID         
     * @return   void  
     *
     */
    function questionActivityTrack($repositoryids, $action, $userID = 0)
    {
        $userID = ($userID == 0) ? $this->session->getValue('userID') : $userID;
        $repositoryids = explode(",", $repositoryids);
        for ($i = 0; $i < count($repositoryids); $i++)
        {
            if ($repositoryids[$i] > 0)
            {
                $title = $this->getEntityTitle(3, $repositoryids[$i]);
                $data_val = array(
                    0,
                    $userID,
                    3,
                    $repositoryids[$i],
                    $title,
                    $action,
                    $this->currentDate(),
                    '1',
                    $this->session->getValue('accessLogID')
                );
               
                $this->db->executeStoreProcedure('ActivityTrackManage', $data_val);
            }
        }
        
    }

    /**
     * This verifies whether the question that is going to be added in
     * Assessment/Bank has the access of the question template
     *
     * @access     public
     * @abstract
     * @static
     * @global
     * @param      string  	$QuestIDs
     * @param      integer  	$EntityID
     * @param      integer  	$EntityTypeID
     * @return     array       $data
     *
     */
    function questVerify($QuestIDs, $EntityID, $EntityTypeID)
    {
        global $DBCONFIG;

        $this->myDebug('$QuestIDs start');
        $this->myDebug($QuestIDs);
        $this->myDebug('$QuestIDs end');

        if (trim($QuestIDs) != '')
        {
            $result = $this->db->executeStoreProcedure('MapQuestionTemplateList', array($EntityID, $EntityTypeID), 'details');
            $Templates = ($EntityTypeID == 1) ? $result['BankTemplates'] : $result['AssessmentTemplates'];
            $Templates = trim($Templates, ',');
            $templateCond = '';
            $questIDCond = '';
            if ($DBCONFIG->dbType == 'Oracle')
            {
                if ($Templates != '')
                {
                    $templateCond = 'and "QuestionTemplateID"  in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }
                $qry = "select * from Questions where  \"isEnabled\" = 1  " . $templateCond . "  " . $questIDCond;
            }
            else
            {

                if ($Templates != '')
                {
                    $templateCond = 'and QuestionTemplateID in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }
                
                $qry = "select * from Questions where isEnabled='1'  " . $templateCond . "  " . $questIDCond;
            }
            $rtq = $this->db->getRows($qry);
            $this->myDebug("Result One");
            $this->myDebug($rtq);
            if (!empty($rtq))
            {
                foreach ($rtq as $rt1)
                {
                    $id = $rt1['ID'] . ",";
                    $QID = $QID . $id;
                }
                $QID = trim($QID, ",");
            }

            if ($DBCONFIG->dbType == 'Oracle')
            {
                if ($Templates != '')
                {
                    $templateCond = 'and "QuestionTemplateID"  not in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }

                $qry1 = "select * from Questions where  \"isEnabled\" = 1  " . $templateCond . "  " . $questIDCond;
            }
            else
            {
                if ($Templates != '')
                {
                    $templateCond = 'and QuestionTemplateID not in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }
                $qry1 = "select * from Questions where isEnabled = '1'  " . $templateCond . "  " . $questIDCond;
            }
            $rtq1 = $this->db->getRows($qry1);
            $this->myDebug("Result Two");
            $this->myDebug($rtq1);
            if (!empty($rtq1))
            {
                foreach ($rtq1 as $rt1)
                {
                    $id = $rt1['ID'] . ",";
                    $Nqid = $Nqid . $id;
                }
                $Nqid = trim($Nqid, ",");
            }

            if ($DBCONFIG->dbType == 'Oracle')
            {
                if ($Templates != '')
                {
                    $templateCond = 'and "QuestionTemplateID"  not in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }
                $qry2 = "select * from Questions where  \"isEnabled\" = 1  " . $templateCond . "  " . $questIDCond;
            }
            else
            {
                if ($Templates != '')
                {
                    $templateCond = 'and QuestionTemplateID not in (' . $Templates . ')';
                }
                if ($QuestIDs != '')
                {
                    $questIDCond = 'and ID in (' . $QuestIDs . ')';
                }
               // echo 'lassttt';
               $qry2 = "select * from Questions where isEnabled = '1'  " . $templateCond . "  " . $questIDCond . " group by QuestionTemplateID";
            }
            $rtq2 = $this->db->getRows($qry2);
            $i = 0;
            $this->myDebug("Result Three");
            $this->myDebug($rtq2);
            if (!empty($rtq2))
            {
                foreach ($rtq2 as $rt1)
                {
                    if ($DBCONFIG->dbType == 'Oracle')
                    {
                        $qry3 = "select qs.ID ,qs.\"Title\" as \"Title\", qt.ID as \"Tid\", qt.\"TemplateTitle\" from Questions qs, QuestionTemplates qt , MapClientQuestionTemplates mqt where qs.\"QuestionTemplateID\" ={$rt1['QuestionTemplateID']} AND mqt.\"QuestionTemplateID\" = qt.ID  and qs.ID in ({$Nqid}) and mqt.ID={$rt1['QuestionTemplateID']} ";
                    }
                    else
                    {
                        $qry3 = "select qs.ID ,qs.Title as Title, qt.ID as Tid ,qt.TemplateTitle from Questions qs, QuestionTemplates qt , MapClientQuestionTemplates mqt where qs.QuestionTemplateID ={$rt1['QuestionTemplateID']} AND mqt.QuestionTemplateID = qt.ID  and qs.ID in ({$Nqid}) and mqt.ID={$rt1['QuestionTemplateID']}";
                    }

                    $rt3 = $this->db->getRows($qry3);
                    $T_Q_ID = '';
                    $j = 0;
                    $data2 = '';
                    if (!empty($rt3))
                    {
                        foreach ($rt3 as $r)
                        {
                            $temp_id = $r['ID'] . ",";
                            $T_Q_ID = $T_Q_ID . $temp_id;
                            $data2[$j] = array('qid' => $r['ID'], 'qtitle' => $r['Title']);
                            $j++;
                        }
                    }
                    $ntemplateId = $rt1['QuestionTemplateID'];
                    $ntname = $rt3[0]['TemplateTitle'];
                    $T_Q_ID = trim($T_Q_ID, ",");
                    $data1[$i] = array('ntemplateId' => $ntemplateId, 'ntname' => $ntname, 'quesdetail' => $data2);
                    $i++;
                }
            }
        }
        $rightstr = ($EntityTypeID == 1) ? 'BankEdit' : 'AsmtEdit';
        if ($this->checkRight($rightstr, $EntityTypeID, $EntityID))
        {
            $right = 1;
        }
        else
        {
            $right = 0;
        }
        $data = array(
            'QID' => $QID,
            'right' => $right,
            'temp_quest' => $data1);
        return $data;
    }

    /**
     * Get Question Information
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$questID
     * @return   array         
     *
     */
    function getInfo($questID)
    {
        $query = "  SELECT * FROM Questions WHERE ID = $questID ";
        return $this->db->getSingleRow($query);
    }

    /**
     * Disables Question (soft delete)
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$questID      
     * @return   boolean
     *
     */
    function disable($questID)
    {
        global $DBCONFIG;

        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  UPDATE Questions set \"isEnabled\" = '0' where ID = $questID ";
        }
        else
        {
            $query = "  UPDATE Questions set isEnabled = '0' where ID = $questID ";
        }


        return $this->db->execute($query);
    }

    /**
     * Enables Question
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer  	$questID
     * @return   boolean
     *
     */
    function enable($questID)
    {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  UPDATE Questions set \"isEnabled\" = '1' where ID = $questID ";
        }
        else
        {
            $query = "  UPDATE Questions set isEnabled = '1' where ID = $questID ";
        }

        return $this->db->execute($query);
    }

    /**
     * Get Assessment List for the searched criteria
     *
     * @deprecated
     * @access     public
     * @abstract
     * @static
     * @global
     * @param    
     * @return     array         Assessment List
     *
     */
    function getAllQuestions()
    {
        $query = "  SELECT distinct a.ID, a.AssessmentName as Name
                    FROM Questions que, MapRepositoryQuestions mrq, Assessments a
                    WHERE que.ID = mrq.QuestionID AND a.ID = mrq.EntityID AND mrq.EntityTypeID = EntityID('Assessment')
                    AND que.isEnabled = '1' ORDER BY Name
                 ";
        return $this->db->getRows($query);
    }

    /**
     * Get Question Reports as per the criteria
     *
     * @deprecated
     * @access     public
     * @abstract
     * @static
     * @global
     * @param    
     * @return     array
     *
     */
    function getReports()
    {
        global $DBCONFIG;
        $condition = ($this->getInput('filter') != 'all' || $this->getInput('filter') > 0) ? " AND a.ID = {$this->getInput('filter')}" : '';
        switch ($this->getInput('periodicity'))
        {
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
        $query = "  SELECT count(mrq.EntityID) as count,DATE_FORMAT(que.AddDate,'$dateFormat') as addedDate,a.AssessmentName FROM Questions que, MapRepositoryQuestions mrq, Assessments a WHERE que.ID = mrq.QuestionID AND a.ID = mrq.EntityID and que.isEnabled = '1'
                AND mrq.EntityTypeID = EntityID('Assessment') AND que.AddDate between DATE_FORMAT(STR_TO_DATE('{$this->getInput('mbrstartdate')}','%d %M, %Y'),'%Y-%m-%d')
                AND DATE_FORMAT(STR_TO_DATE('{$this->getInput('mbrenddate')}','%d %M, %Y'),'%Y-%m-%d') $condition GROUP BY addedDate,mrq.EntityID ORDER BY que.AddDate
                ";
        return $this->db->getRows($query);
    }

    /**
     * Get last week Question count
     *
     * @deprecated
     * @access     public
     * @abstract
     * @static
     * @global
     * @param    
     * @return     integer
     *
     */
    function getLastWeekCount()
    {
        global $DBCONFIG;
        $query = "  SELECT count(mrq.EntityID) as count FROM Questions que, MapRepositoryQuestions mrq,Assessments a WHERE que.ID = mrq.QuestionID AND a.ID = mrq.EntityID and que.isEnabled = '1'
                    AND mrq.EntityTypeID = EntityID('Assessment') AND que.AddDate BETWEEN DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND CURDATE()
                    ";
        $result = $this->db->getSingleRow($query);
        return $result['count'];
    }

    /**
     * Checks the question access rights
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    array  	$input
     * @param    array  	$right
     * @return   boolean       $result
     *
     */
    function checkQuestionAccess(array $input, $right)
    {
        $result = false;
        if (($input['QuestID'] > 0) && ($input['rID'] > 0))
        {
            if ($this->isMappingExist($input['rID'], $input['eID'], $input['ID'], $input['QuestID']) == true) //check for proper mapping in Questions and entity.
            {				
                if ($this->checkRight($right, $input['eID'], $input['ID']) == true) //Check for accessibility for specified entity   //if ($this->checkRight($right) == true) //Check for accessibility for specified entity
                {
					$result = true;
                }
				$this->myDebug($result);
            }
        }
        else
        {
            if ($this->checkRight($right, $input['eID'], $input['ID']) == true) //Check for accessibility for specified entity
            {
                $result = true;
            }
        }
        return $result;
    }

    /**
     * To check the owner of a Question
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  integer  	$questID
     * @param    integer  	$userID
     * @return   boolean
     *
     */
    function checkQuestionOwner($questID, $userID)
    {
        $query = " SELECT qt.ID FROM Questions qt WHERE  qt.ID = '{$questID}' AND qt.UserID = '{$userID}'  and qt.isEnabled = '1'";
        if ($this->db->getCount($query) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * To check whether mapping exist for a Question
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  integer  	$rID
     * @param    integer  	$eID
     * @param    integer  	$ID
     * @param    integer  	$QuestID      
     * @return   boolean       
     *
     */
    function isMappingExist($rID, $eID, $ID, $QuestID)
    {
        global $DBCONFIG;

        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = " select \"ID\" from MapRepositoryQuestions where \"ID\" = '{$rID}' AND \"EntityTypeID\" = '{$eID}' AND
                \"EntityID\" = '{$ID}' AND \"QuestionID\" = '{$QuestID}' and \"isEnabled\" = '1'";
        }
        else
        {
            $query = " select ID from MapRepositoryQuestions where ID = '{$rID}' AND EntityTypeID = '{$eID}' AND
                EntityID = '{$ID}' AND QuestionID = '{$QuestID}' and isEnabled = '1'";
        }


        if ($this->db->getCount($query) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * To get section details
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global	  integer  	$rID
     * @return   array         $result
     *
     */
    function getSectionDetails($rID)
    {
        $qry = "select * from MapRepositoryQuestions where ID={$rID}";
        $result = $this->db->getSingleRow($qry);
        return $result;
    }

    /**
     * To get question id as per the criteria      
     *
     * @access   public
     * @global	  array  	$params
     * @return   integer       $questID
     *
     */
    function addQuestionByArgs($params)
    {
        $result = $this->db->executeStoreProcedure('QuestionManage', array(
            0,
            $params["QVersion"],
            $params["QTitle"],
            $params["QXML"],
            $params["QJSON"],
            $params["QTypeID"],
            $params["QUserID"],
            $this->currentDate(),
            $params["QRendition"],
            "1"
                ), "nocount"
        );
        $this->myDebug("XYZ007");
        $this->myDebug($result);
        $this->myDebug("XYZ008");
        $questID = $result[0]['QuestID'];
        return $questID;
    }

    /**
     * To get the media list
     *      
     * @access   public
     * @return   array
     *
     */
    function mediaList(array $input)
    {
        global $DBCONFIG;
        $arrMediaList = array();
        $user = new User();

        if ($DBCONFIG->dbType == 'Oracle')
        {
            $displayFields = " cntt.\"ID\", cntt.\"ContentType\", cntt.\"FileName\", cntt.\"OriginalFileName\", cntt.\"Title\", cntt.\"ContentInfo\", cntt.\"Keywords\",
                            to_char(cntt.\"ModDate\", 'YYYY-MM-DD HH:MI:SS' ) AS \"ModDate\",  mqc.\"UsedField\", mqc.\"ContentID\",mqc.\"Description\", (usr.\"FirstName\" || ' ' || usr.\"LastName\") as \"FullName\",
                            cntt.\"ContentHeight\" as \"Height\",cntt.\"ContentWidth\" as \"Width\" , cntt.\"ContentSize\" as \"Size\" ,to_char(cntt.\"AddDate\", 'YYYY-MM-DD HH:MI:SS' ) AS \"AddDate\", cntt.\"Thumbnail\" as \"Thumbnail\", cntt.\"Count\", cntt.\"Duration\", (SELECT MAX(cntt.ID) FROM Content cntt WHERE cntt.\"isEnabled\" = 1 GROUP BY cntt.\"ID\") as \"ID\" ";
        }
        else
        {
            $displayFields = "    SQL_CALC_FOUND_ROWS cntt.ID, cntt.ContentType, cntt.FileName, cntt.OriginalFileName, cntt.Title, cntt.ContentInfo, cntt.Keywords,
                            date_format(cntt.ModDate,'%a %D %b %Y %H:%i') as ModDate,  mqc.UsedField, mqc.ContentID,mqc.Description, concat(usr.FirstName,' ',usr.LastName) as FullName,
                            cntt.ContentHeight as Height,cntt.ContentWidth as Width , cntt.ContentSize as Size ,cntt.AddDate as AddDate, cntt.Thumbnail as Thumbnail, cntt.Count, cntt.Duration ";
        }
        if ($user->getDefaultClientID() == $this->session->getValue('instID'))
        {
            if ($DBCONFIG->dbType == 'Oracle')
            {
                $query = "SELECT {$displayFields}
                            FROM Content cntt
                            INNER JOIN MapQuestionContent mqc on cntt.\"ID\" = mqc.\"ContentID\"
                            left join ContentMembers cm on cntt.\"ID\" = cm.\"ContentID\" and cm.\"isEnabled\"= '1'
                            left join Users usr on usr.\"ID\" = mqc.\"UserID\"
                            left join MapClientUser mcu on mcu.\"UserID\" = usr.\"ID\" and  mcu.\"isEnabled\"= '1'
                            WHERE  mcu.\"ClientID\" = {$this->session->getValue('instID')} AND mcu.\"UserID\" = {$this->session->getValue('userID')} AND mqc.\"QuestionID\" = {$input['questionID']} and mqc.\"isEnabled\" = '1' and mqc.\"isActive\" = 'Y' AND cntt.\"isEnabled\" = '1'";
            }
            else
            {
                $query = "SELECT {$displayFields}
                            FROM Content cntt
                            INNER JOIN MapQuestionContent mqc on cntt.ID = mqc.ContentID
                            left join ContentMembers cm on cntt.ID = cm.ContentID and cm.isEnabled= '1'
                            left join Users usr on usr.ID = mqc.UserID
                            left join MapClientUser mcu on mcu.UserID = usr.ID and  mcu.isEnabled= '1'
                            WHERE  mcu.ClientID = {$this->session->getValue('instID')} AND mcu.UserID = {$this->session->getValue('userID')} AND mqc.QuestionID = {$input['questionID']} and mqc.isEnabled = '1' and mqc.isActive = 'Y' AND cntt.isEnabled = '1'  group by cntt.ID ";
            }
        }
        else
        {
            if ($DBCONFIG->dbType == 'Oracle')
            {
                $query = "   SELECT {$displayFields}
                                FROM Content cntt
                                INNER JOIN MapQuestionContent mqc on cntt.\"ID\" = mqc.\"ContentID\"
                                left join ContentMembers cm on cntt.\"ID\" = cm.\"ContentID\" and cm.\"isEnabled\"= '1'
                                left join Users usr on usr.\"ID\" = mqc.\"UserID\"
                                left join MapClientUser mcu on mcu.\"UserID\" = usr.\"ID\" and  mcu.\"isEnabled\" = '1'
                                WHERE  mcu.\"ClientID\" = {$this->session->getValue('instID')}  AND mqc.\"QuestionID\" = {$input['questionID']}  and mqc.\"isEnabled\" = '1' and mqc.\"isActive\" = 'Y' AND cntt.\"isEnabled\" = '1'  ";
            }
            else
            {
                $query = "   SELECT {$displayFields}
                                FROM Content cntt
                                INNER JOIN MapQuestionContent mqc on cntt.ID = mqc.ContentID
                                left join ContentMembers cm on cntt.ID = cm.ContentID and cm.isEnabled= '1'
                                left join Users usr on usr.ID = mqc.UserID
                                left join MapClientUser mcu on mcu.UserID = usr.ID and  mcu.isEnabled= '1'
                                WHERE  mcu.ClientID = {$this->session->getValue('instID')}  AND mqc.QuestionID = {$input['questionID']}  and mqc.isEnabled = '1' and mqc.isActive = 'Y' AND cntt.isEnabled = '1'  ";

                $query .= "  group by cntt.ID ";
            }
        }

        $getMediaList = $this->db->executeStoreProcedure('GetMediaList', array($this->session->getValue('instID'), $input['questionID'], '-1', '-1'));


        return $getMediaList['RS'];
    }

    function getSupplementData(array $input)
    {
        $user = new User();
        $arrSupplementData = array();
        $this->mydebug("---------inputdata::model");
        $this->mydebug($input);

        $getSupplementData = $this->db->executeStoreProcedure('GetSupplementData', array($this->session->getValue('instID'), $input['QuestionID'], $input['usedField'], '-1', '-1'));

        if (!empty($getSupplementData['RS']))
        {
            $arrSupplementData = $getSupplementData['RS'];
        }
        return $arrSupplementData;
    }

    /**
     * To get the search criteria in XML format      
     *
     * @access   public
     * @return   string(XML)   $xmlresponse
     *
     */
    function getSearchCrieteria(array $input)
    {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {

            $query = "select \"ID\", \"Title\",dbms_lob.substr(\"Crieteria\",32000,1) as \"Crieteria\",dbms_lob.substr(\"ProcedureCall\",32000,1) as \"ProcedureCall\",\"SearchCount\",\"MapClientUserID\",\"Type\", \"EntityID\",\"EntityTypeID\",to_char(\"AddDate\", 'YYYY-MM-DD HH:MI:SS' ) AS \"AddDate\", to_char(\"ModDate\", 'YYYY-MM-DD HH:MI:SS' ) AS \"ModDate\" from SearchCrieteria where \"isEnabled\" = '1' AND \"Type\" = '{$input['searchtype']}'
                                    AND \"EntityID\" = {$input['entityid']} AND \"EntityTypeID\" = {$input['entitytypeid']} AND \"MapClientUserID\" = {$this->session->getValue('mapUserID')}
                                    ORDER BY \"ModDate\" DESC ";
        }
        else
        {
            //$query = "select * from SearchCrieteria where isEnabled = '1' AND Type = '{$input['searchtype']}' AND EntityID = {$input['entityid']} AND EntityTypeID = {$input['entitytypeid']} AND MapClientUserID = {$this->session->getValue('mapUserID')}   ORDER BY ModDate DESC  "; //AND (ID >'1048' AND ID<'1079') \
            
            $query = "select * from SearchCrieteria where isEnabled = '1' AND Type = '0' AND EntityID = '0' AND EntityTypeID = '3' AND MapClientUserID = '190'   ORDER BY ModDate DESC  "; //AND (ID >'1048' AND ID<'1079') 
        }
        
   
        $data = $this->db->getRows($query);
        
        $this->myDebug($data);

        /* header('Content-type: text/xml; charset=UTF-8') ;
          $xml    = '';
          if(!empty($data))
          {
          foreach($data as $key=>$value)
          {
          $srno   = $key+1;
          $date   = date('F j, Y, g:i a', strtotime($value['AddDate']));
          $xml   .= "<searchresult><srno>{$srno}</srno><title>{$value['Title']}</title><crieteria>".htmlentities($value['Crieteria'])."</crieteria><procedurecall>".htmlentities($value['ProcedureCall'])."</procedurecall><searchid>{$value['ID']}</searchid><searchcount>{$value['SearchCount']}</searchcount><searchdate>{$date}</searchdate><entitytypeid>{$value['EntityTypeID']}</entitytypeid></searchresult>";
          }
          }
          $xmlresponse    = "<searchcrieteria>$xml</searchcrieteria>";
          echo $xmlresponse;
         */
        header('Content-type: application/json; charset=UTF-8');
        $jsonp = '[';
        if (!empty($data))
        {
            $i = 0;
            $cnt = sizeof($data);
            foreach ($data as $key => $value)
            {
                $i ++;
                $srno = $key + 1;
                $date = date('F j, Y, g:i a', strtotime($value['AddDate']));
                $jsonp .=
                        "{
					\"item\":{
						\"srno\":\"{$srno}\",
						\"title\":\"{$value['Title']}\",
						\"crieteria\":\"" . htmlentities($value['Crieteria']) . "\",
						\"procedurecall\":\"" . htmlentities($value['ProcedureCall']) . "\",
						\"searchid\":\"{$value['ID']}\",
						\"searchcount\":\"{$value['SearchCount']}\",
						\"searchdate\":\"{$date}\",
						\"entitytypeid\":\"{$value['EntityTypeID']}\"
					}
				}";
                
             /*   $jsonp .=
                        "{
					\"item\":{
						\"srno\":\"{$srno}\",
						\"title\":\"{$value['Title']}\",
						\"crieteria\":\"" . $value['Crieteria'] . "\",
						\"procedurecall\":\"" . $value['ProcedureCall'] . "\",
						\"searchid\":\"{$value['ID']}\",
						\"searchcount\":\"{$value['SearchCount']}\",
						\"searchdate\":\"{$date}\",
						\"entitytypeid\":\"{$value['EntityTypeID']}\"
					}
				}";*/
                if ($i < $cnt)
                {
                    $jsonp .= ',';
                }
            }
            $jsonp .= ']';
            $jsonpresponse = "{\"results\":{$jsonp}, \"count\":{$cnt}}";
        }
        else
        {
            $jsonp .=
                    "{
					\"item\":{
						\"srno\":\"\",
						\"title\":\"\",
						\"crieteria\":\"\",
						\"procedurecall\":\"\",
						\"searchid\":\"\",
						\"searchcount\":\"\",
						\"searchdate\":\"\",
						\"entitytypeid\":\"\"
					}
				}";
            $jsonpresponse = "{\"results\":[{$jsonp}], \"count\":0}";
        }
        echo $jsonpresponse;
        die;
    }

    
    
    /*
     * manish
     * Getting saved search
     * return array
     */
    
    
    function getSearchCrieteriaNew(array $input, $condition='')
    {
        
		$input['pgnob'] = ($input['pgnob']!="-1")?$input['pgnob']:"sc.ModDate";
		$input['pgnot'] = ($input['pgnot']!="-1")?$input['pgnot']:"desc";
        $condition      = ($condition != '')?$condition:'-1';
        return  $this->db->executeStoreProcedure('SearchCrieteriaList', array($input['pgnob'], $input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('mapUserID'),$this->session->getValue('instID') , $input['pgndc'], $condition)); 
    }
    
    /*
     * Enabling save search 
     * Manish
     * return search id
     */
    
    function savedSearchCrieteriaNew($searchTitle=''){
        
        $fetchQuery="select id from SearchCrieteria where  MapClientUserID = {$this->session->getValue('mapUserID')}   ORDER BY ModDate DESC limit 1 ";
        $data = $this->db->getRows($fetchQuery);
        $searchId= $data['0']['id'];
       	$status     = $this->db->update("SearchCrieteria",array("isEnabled"=>'1',"Title"=>$searchTitle,"ModDate"=>$this->currentDate()),array("ID"=>$searchId));
       	return $status;
    }
    
    /*
     * Get saved search details
     * Manish
     * return array
     */
    function getSavedSearchDetails($sId){
        
         $fetchQuery="select * from SearchCrieteria where  ID=".$sId;
         return $data = $this->db->getRows($fetchQuery);
        
    }
    
    /*
     * Manish
     * getLastSearchDetails
     * for taking last saved search
     * return array
     */
    function getLastSearchDetails($type=0){
		if($type){
			$fetchQuery="select * from SearchCrieteria where  MapClientUserID = {$this->session->getValue('mapUserID')} AND Type = '0' ORDER BY ModDate DESC limit 1 ";
		}else{
         $fetchQuery="select * from SearchCrieteria where  MapClientUserID = {$this->session->getValue('mapUserID')} AND Type = '{$type}' ORDER BY ModDate DESC limit 1 ";
		}
         return $data = $this->db->getRows($fetchQuery);
    }
    
    
    /**
     * Delete the saved Search criteria
     *
     *
     * @access   public
     * @global	  integer  	$id
     * @return   boolean         
     *
     */
    function deleteSavedSearch($id)
    {
        global $DBCONFIG;
        $savedIDs = $this->removeBlankElements($id);
        $savedID = implode(',', (array) $savedIDs);
        
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "UPDATE SearchCrieteria set \"isEnabled\" = '0' where \"ID\" = $id ";
        }
        else
        {
            $query = "UPDATE SearchCrieteria set isEnabled = '0'  WHERE ID IN($savedID) ";
        }

        return $this->db->execute($query);
    }

    /**
     * Rename the search criteria 
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   boolean        
     *
     */
    function renameSearchCrieteria(array $input)
    {
        global $DBCONFIG;
        if (trim($input['value']) != '')
        {
            if ($DBCONFIG->dbType == 'Oracle')
            {
                $query = "UPDATE SearchCrieteria set \"Title\" = '{$input['value']}', \"Type\" = '1' where \"ID\" = {$input['id']} ";
            }
            else
            {
                $query = "UPDATE SearchCrieteria set Title = '{$input['value']}', Type = '1' where ID = {$input['id']} ";
            }
        }
        else
        {
            if ($DBCONFIG->dbType == 'Oracle')
            {
                $query = "UPDATE SearchCrieteria set \"Title\" = '', \"Type\" = '0' where \"ID\" = {$input['id']} ";
            }
            else
            {
                $query = "UPDATE SearchCrieteria set Title = '', Type = '0' where ID = {$input['id']} ";
            }
        }
        
      
        return $this->db->execute($query);
    }

    /**
     * Get Question as per difficulty (easy, nedium, hard)
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   array
     *
     */
    function getDifficultyQuestionCount(array $input = array())
    {
        /* if($input["EntityID"] > 0 && $input["EntityTypeID"] > 0)
          {
          $query = "  select qst.DifficultyLevel , count(qst.DifficultyLevel) as qstdiffcnt  from Questions qst
          inner join MapRepositoryQuestions mrq on mrq.EntityID = '{$input["EntityID"]}' and mrq.EntityTypeID = '{$input["EntityTypeID"]}' and mrq.QuestionID <> 0 and mrq.isEnabled= '1'
          inner join MapClientUser mcu on mcu.UserID = mrq.UserID and  mcu.ClientID = '{$this->session->getValue('instID')}' and  mcu.isEnabled= '1'
          where qst.isEnabled = '1' and qst.ID = mrq.QuestionID group by qst.DifficultyLevel";
          }else
          {
          $query = "  select qst.DifficultyLevel , count(qst.DifficultyLevel) as qstdiffcnt  from Questions qst
          inner join MapClientUser mcu on mcu.UserID = qst.UserID and  mcu.ClientID = '{$this->session->getValue('instID')}' and  mcu.isEnabled= '1'
          where qst.isEnabled = '1' group by qst.DifficultyLevel";
          }
          $data   = $this->db->getRows($query);
          return  array(
          "easy_count"    => (int)implode("",$this->getValuesByKeyValue($data,"DifficultyLevel","qstdiffcnt", "easy")),
          "medium_count"  => (int)implode("",$this->getValuesByKeyValue($data,"DifficultyLevel","qstdiffcnt","medium")),
          "hard_count"    => (int)implode("",$this->getValuesByKeyValue($data,"DifficultyLevel","qstdiffcnt", "hard"))
          ); */
    }

    /**
     * a function to update version title of the question
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function updateVersionTitle(array $input)
    {
        global $DBCONFIG;

        $title = addslashes($input['title']);
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = " update Questions set \"VersionTitle\" = '{$title}' where ID = {$input['qid']} ";
        }
        else
        {
            $query = " update Questions set VersionTitle = '{$title}' where ID = {$input['qid']} ";
        }

        return $this->db->execute($query);
    }

    /**
     * a function to get the question version list
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function getVersionList($input)
    {
        //header('Content-type: application/json; charset=UTF-8');
        $questions = $this->db->executeStoreProcedure('GetQuestionVersionList', array('-1', '-1', $input['start'], $input['limit'], $input['questionid'], $this->session->getValue('instID')));
        $versionCnt = $questions['TC'];
        $latestQuestVersion = $this->getLatestQuestVersion($input['questionid']);
        $questions['RS'] = $this->addComment($questions['RS']);

        $cnt = sizeof($questions['RS']);
   
        if (!empty($questions['RS']))
        {
//            include $this->cfg->rootPath.'/views/templates/'.$this->quadtemplate.'/question/VersionList.php';
            $i = 0;
            foreach ($questions['RS'] as $value)
            {
                $i++;
                $itemArr = array("srno" => $value['Version'],
                    "questionid" => $value['ID'],
                    "latestquestid" => $latestQuestVersion,
                    "versiontitle" => $value['VersionTitle'],
                    "questiontitle" => htmlentities(trim($value['Title']), ENT_QUOTES, "UTF-8"),
                    "templateid" => $value['QuestionTemplateID'],
                    "renditiontype" => $value['RenditionType'],
                    "renditionmode" => $value['RenditionMode'],
                    "username" => $value['Name'],
                    "comment" => $value['comment'],
                    "date" => date("F j, Y, g:i a", strtotime($value['AddDate'])));
                $jsonArr["results"][] = $itemArr;
            }
            $jsonArr["count"] = $cnt;
            //$jsonresponse = json_encode($jsonArr);
            return $jsonArr;
        }
        else
        {
            $itemArr = array("srno" => "",
                "questionid" => "",
                "latestquestid" => "",
                "versiontitle" => "",
                "questiontitle" => "",
                "templateid" => "",
                "renditiontype" => "",
                "renditionmode" => "",
                "username" => "",
                "comment" => "",
                "date" => "");
            $jsonArr["results"][]["item"] = $itemArr;
            $jsonArr["count"] = $cnt;
            //$jsonresponse = json_encode($jsonArr);
            echo $jsonArr;
            die;
        }
    }

    function addComment(array $questions)
    {
        if (!empty($questions))
        {
            $json = new Services_JSON();
            foreach ($questions as $key => $question)
            {
                $jsonData = stripslashes($question['JSONData']);
                $jsonData = $json->decode($jsonData);
                $questions[$key]['comment'] = html_entity_decode($jsonData->notes_editor);
            }
            return $questions;
        }
        else
        {
            return $questions;
        }
    }

    function getLatestQuestVersion($questionID)
    {
        /* $questVersion       = $this->db->getSingleRow(" SELECT max(q.ID) as latestVersion FROM Questions q, Questions q1, Users u
          WHERE q.RefID = q1.RefID AND q1.ID = '{$questionID}' AND q.UserID = u.ID group by q1.RefID limit 1"); */

        $questVersion = $this->db->executeFunction('LatestVersion', 'latestVersion', array($questionID));
        return $questVersion['latestVersion'];
    }

    /**
     * sets the latest version of a file
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function setLatestVersion(array $input)
    {
      
        $this->db->executeStoreProcedure('SetToLatestVersion', array($input['qid'], $input['rid'], $this->session->getValue('userID'), $this->currentDate()));
    }

    /* Code for question difference starts */

    /**
     * sets the latest version of a file
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function objectToArray($object)
    {
        if (!is_object($object) && !is_array($object))
        {
            return $object;
        }
        if (is_object($object))
        {
            $object = get_object_vars($object);
        }
        return array_map(array($this, 'objectToArray'), $object);
    }

    /**
     * a function which operates on given question arrays for difference
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function compare_old($array3, $array4)
    {
        $this->key = array_search($array3, $this->refarr);
        $this->key = strtolower(str_replace('_', ' ', $this->key));
        $key1 = str_replace(' ', '_', $this->key);
        array_shift($this->refarr);
        if (is_array($array3))
        {
            switch (trim(strtolower($key1)))
            {
                case 'question_stem':
                case 'question_title':
                case 'hint':
                case 'image':
                case 'global_correct_feedback':
                case 'global_incorrect_feedback':
                case 'notes_editor':
                    $this->manageChoices($array3, $array4, 'text');   // text is used to avoid counter variable in case of array
                    break;

                case 'choices':
                case 'container_text':
                case 'column_text':
                    $this->manageChoices($array3, $array4, 'columns');
                    break;

                case 'metadata':

                    $this->manageChoices($array3, $array4, 'metas');
                    break;
            }
        }
        else
        {
            $tmpArr = array_diff((array) $array3, (array) $array4);

            $cnt = count($this->result1);
            if (strstr($array3, '<img') || strstr($array3, '&lt;img') || strstr($array3, 'data=') || strstr($array3, 'src='))
            {
                $array3 = $this->getMediaHtml($array3);
            }
            else
            {
                $array3 = '<span title="' . $array3 . '">' . $this->wrapText($array3, 48) . '</span>';
            }
            if (strstr($array4, '<img') || strstr($array4, '&lt;img') || strstr($array4, 'data=') || strstr($array4, 'src='))
            {
                $array4 = $this->getMediaHtml($array4);
            }
            else
            {
                $array4 = '<span title="' . $array4 . '">' . $this->wrapText($array4, 48) . '</span>';
            }
            if (!empty($tmpArr))
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'green';
                $this->result1[$cnt]['type'] = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'green';
                $this->result2[$cnt]['type'] = 'string';
            }
            else
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'nochange';
                $this->result1[$cnt]['type'] = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'nochange';
                $this->result2[$cnt]['type'] = 'string';
            }
        }

        $this->labels = array_keys($this->getValueArray($this->result1, 'label', 'multiple', 'array'));
        $this->result1 = array_merge(array(), $this->result1);
        $this->result2 = array_merge(array(), $this->result2);
        return 0;
    }
    function compare($array3,$array4)
    {
        $this->key    = array_search($array3,$this->refarr);
        $this->key    = ucwords(str_replace('_',' ',$this->key));
        $key1   = str_replace(' ','_',$this->key);
        array_shift($this->refarr);
        //echo strtolower($key1).'**<br>';
        if(is_array($array3))
        {
            switch (trim(strtolower($key1)))
            {
                case 'question_text':
               // case 'question_title':                   
                case 'instruction_text':                
                    $this->manageChoices($array3,$array4,'text');   // text is used to avoid counter variable in case of array
                break;
                    
                case 'choices':
                case 'container_text':
                case 'column_text':
                    $this->manageChoices($array3,$array4,'columns');
                break;
                
                case 'correct_feedback':
                case 'incorrect_feedback':
                case 'hint':
                case 'notes_editor':
                    $this->manageChoices($array3,$array4,'text');
                break;
            
                case 'metadata':
                
                    $this->manageChoices($array3,$array4,'metas');
                break;
            }
        }
        else
        {
            $tmpArr = array_diff((array) $array3, (array) $array4);

            $cnt = count($this->result1);
            if(strstr($array3, '<img') || strstr($array3, '&lt;img') || strstr($array3, 'data=') || strstr($array3, 'src='))
            {
                $array3=$this->getMediaHtml($array3);
            }else{
                        $array3 = '<span title="'.$array3.'">'.$this->wrapText($array3, 48).'</span>';
                    }
            if(strstr($array4, '<img') || strstr($array4, '&lt;img') || strstr($array4, 'data=') || strstr($array4, 'src='))
            {
                $array4=$this->getMediaHtml($array4);
            }else{
                $array4 = '<span title="'.$array4.'">'.$this->wrapText($array4, 48).'</span>';
            }
            if(!empty ($tmpArr))
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'green';
                $this->result1[$cnt]['type']  = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'green';
                $this->result2[$cnt]['type']  = 'string';
            }
            else
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'nochange';
                $this->result1[$cnt]['type']  = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'nochange';
                $this->result2[$cnt]['type']  = 'string';
            }
        }
       
        $this->labels = array_keys($this->getValueArray($this->result1,'label','multiple','array'));
        $this->result1    = array_merge(array(),$this->result1);
        $this->result2    = array_merge(array(),$this->result2);
        return 0;
    }

    /**
     * a function which operates on given question array for view
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function getView($array)
    {
        $this->key = array_search($array, $this->refarr);
        $this->key = ucwords(str_replace('_', ' ', $this->key));
        $key1 = str_replace(' ', '_', $this->key);
        array_shift($this->refarr);
        if (is_array($array))
        {
            switch (trim(strtolower($key1)))
            {
                case 'question_text':
                case 'instruction_text':
                    $this->getChoices($array, 'text');   // text is used to avoid counter variable in case of array
                    break;
                case 'choices':
                case 'container_text':
                case 'column_text':
                    $this->getChoices($array, 'columns');
                    break;
                case 'correct_feedback':
                case 'incorrect_feedback':
                case 'hint':
                case 'notes_editor':
                    $this->getChoices($array, 'text');
                    break;
                case 'metadata':
                    $this->getChoices($array, 'metas');
                    break;
            }
        }
        else
        {
            if (strstr($array, '<img') || strstr($array, '&lt;img') || strstr($array, 'data=') || strstr($array, 'src='))
            {
                //$array = $this->getMediaHtml($array);
            }
            else
            {
                $array = '<span title="' . $array . '">' . $this->wrapText($array, 100) . '</span>';
            }
            $cnt = count($this->result1);
            $this->result1[$cnt]['label'] = $this->key;
            $this->result1[$cnt]['value'] = $array;
            $this->result1[$cnt]['class'] = 'nochange';
            $this->result1[$cnt]['type'] = 'string';
        }

        $this->labels = array_keys($this->getValueArray($this->result1, 'label', 'multiple', 'array'));
        $this->result1 = array_merge(array(), $this->result1);

        return 0;
    }

    /**
     * creates the array of choices for difference
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function manageChoices(array $arr1, array $arr2, $header)
    {
       
        $index  = $this->arraySearchKey($this->key,$this->flashstruct['data']);
        $struct = $this->flashstruct['data'][$index][$header];

        $count  = (count($arr1)>count($arr2))?count($arr1):count($arr2);
        $arr    = (count($arr1)>count($arr2))?$arr1:$arr2;
        $k      = 0;
        $n      = 0;
        $objAuthoring = new Authoring();
        for($i=0;$i<$count;$i++)
        {
            if(is_array($arr[$i]))
            {
                $j      = 0;
                $flag   = 0;
                $cnt    = count($this->result1);
                $cnt    = ($k>0)?$k:$cnt;

                foreach($arr[$i] as $k=>$v)
                {
                    switch ($header)
                    {
                        case 'columns':
                            $this->result1[$cnt]['label'] = $this->key.' '.($i+1);
                            $text   = $struct[$j]['header'];
                            $val1   = $this->filterTextToDisplay($arr1[$i][$k]);
                            $val2   = $this->filterTextToDisplay($arr2[$i][$k]);
                        break;
                        // text is used to avoid counter variable in case of array
                        case 'text':
                            $this->result1[$cnt]['label'] = $this->key;
                            $text   = $struct[$j]['header'];
                            $val1   = $this->filterTextToDisplay($arr1[$i][$k]);
                            $val2   = $this->filterTextToDisplay($arr2[$i][$k]);
                        break;

                        case 'metas':
                            if($k == 0) {$k = $cnt;}
                            $this->result1[$cnt]['label'] = $this->key;
                            $text   = $arr1[$i]['text'];
                            $val1   = $this->filterTextToDisplay($arr1[$i]['val']);
                            $val2   = $this->filterTextToDisplay($arr2[$i]['val']);
                            $flag   = 1;
                        break;
                    }
                    if(strstr($val1, '<img') || strstr($val1, '&lt;img') || strstr($val1, 'data=') || strstr($val1, 'src='))
                    {
                        //$val1=$this->getMediaHtml($val1);
                    }else{
                        $val1 = '<span title="'.$val1.'">'.$this->wrapText($val1, 48).'</span>';
                    }
                    if(strstr($val2, '<img') || strstr($val2, '&lt;img') || strstr($val2, 'data=') || strstr($val2, 'src='))
                    {
                        //$val2=$this->getMediaHtml($val2);
                    }else{
                        $val2 = '<span title="'.$val2.'">'.$this->wrapText($val2, 48).'</span>';
                    }


                    if(!is_array($arr1[$i])) {$val1 = 'NA'; }
                    if(!is_array($arr2[$i])) {$val2 = 'NA'; }

                    if($val1 === $val2)
                    {
                        if($header == 'metas')
                        {
                            $this->result1[$cnt]['fields'][$n]['label'] = $text;
                            $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$n]['class'] = 'nochange';
                            $this->result2[$cnt]['fields'][$n]['label'] = $text;
                            $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$n]['class'] = 'nochange';
                            $n++;
                        }
                        else
                        {
                            $this->result1[$cnt]['fields'][$j]['label'] = $text;
                            $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$j]['class'] = 'nochange';
                            $this->result2[$cnt]['fields'][$j]['label'] = $text;
                            $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$j]['class'] = 'nochange';
                        }

                        $this->result1[$cnt]['type']  = 'array';
                        $this->result2[$cnt]['type']  = 'array';
                    }
                    else
                    {
                        $class1     = (trim($val2) == 'NA')?'red':'green';
                        $class1     = (trim($val1) == '' && trim($val2) == '1')?'green':$class1;
                        $class1     = (trim($val1) == '1' && trim($val2) == '')?'green':$class1;
                        $class1     = (trim($val1) == 'NA')?'nochange':$class1;
                        
                        $class2     = (trim($val1) == 'NA')?'blue':'green';
                        $class2     = (trim($val1) == '' && trim($val2) == '1')?'green':$class2;
                        $class2     = (trim($val1) == '1' && trim($val2) == '')?'green':$class2;
                        $class2     = (trim($val2) == 'NA')?'nochange':$class2;

                        if($header == 'metas')
                        {
                            $this->result1[$cnt]['fields'][$n]['label'] = $text;
                            $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$n]['class'] = $class1;
                            $this->result2[$cnt]['fields'][$n]['label'] = $text;
                            $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$n]['class'] = $class2;
                            $n++;
                        }
                        else
                        {
                            $this->result1[$cnt]['fields'][$j]['label'] = $text;
                            $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$j]['class'] = $class1;
                            $this->result2[$cnt]['fields'][$j]['label'] = $text;
                            $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$j]['class'] = $class2;
                        }

                        $this->result1[$cnt]['type']  = 'array';
                        $this->result2[$cnt]['type']  = 'array';
                    }
                    $j++;
                    if($flag == 1) {break;}
                }
            }
        }
//        $this->result1 = array_unique($this->result1);
//        $this->result2 = array_unique($this->result2);
    }
    function manageChoices_old(array $arr1, array $arr2, $header)
    {
        $index = $this->arraySearchKey($this->key, $this->flashstruct['data']);
        $struct = $this->flashstruct['data'][$index][$header];

        $count = (count($arr1) > count($arr2)) ? count($arr1) : count($arr2);
        $arr = (count($arr1) > count($arr2)) ? $arr1 : $arr2;
        $k = 0;
        $n = 0;
        $objAuthoring = new Authoring();
        for ($i = 0; $i < $count; $i++)
        {
            if (is_array($arr[$i]))
            {
                $j = 0;
                $flag = 0;
                $cnt = count($this->result1);
                $cnt = ($k > 0) ? $k : $cnt;

                foreach ($arr[$i] as $k => $v)
                {
                    switch ($header)
                    {
                        case 'columns':
                            $this->result1[$cnt]['label'] = $this->key . ' ' . ($i + 1);
                            $text = $struct[$j]['header'];
                            $val1 = $this->filterTextToDisplay($arr1[$i][$k]);
                            $val2 = $this->filterTextToDisplay($arr2[$i][$k]);
                            break;
                        // text is used to avoid counter variable in case of array
                        case 'text':
                        case 'question_text':
                        case 'instruction_text':
                            $this->result1[$cnt]['label'] = $this->key;
                            $text = $struct[$j]['header'];
                            $val1 = $this->filterTextToDisplay($arr1[$i][$k]);
                            $val2 = $this->filterTextToDisplay($arr2[$i][$k]);
                            break;

                        case 'metas':
                            if ($k == 0)
                            {
                                $k = $cnt;
                            }
                            $this->result1[$cnt]['label'] = $this->key;
                            $text = $arr1[$i]['text'];
                            $val1 = $this->filterTextToDisplay($arr1[$i]['val']);
                            $val2 = $this->filterTextToDisplay($arr2[$i]['val']);
                            $flag = 1;
                            break;
                    }
                    if (strstr($val1, '<img') || strstr($val1, '&lt;img') || strstr($val1, 'data=') || strstr($val1, 'src='))
                    {
                        $val1 = $this->getMediaHtml($val1);
                    }
                    else
                    {
                        $val1 = '<span title="' . $val1 . '">' . $this->wrapText($val1, 48) . '</span>';
                    }
                    if (strstr($val2, '<img') || strstr($val2, '&lt;img') || strstr($val2, 'data=') || strstr($val2, 'src='))
                    {
                        $val2 = $this->getMediaHtml($val2);
                    }
                    else
                    {
                        $val2 = '<span title="' . $val2 . '">' . $this->wrapText($val2, 48) . '</span>';
                    }


                    if (!is_array($arr1[$i]))
                    {
                        $val1 = 'NA';
                    }
                    if (!is_array($arr2[$i]))
                    {
                        $val2 = 'NA';
                    }

                    if ($val1 === $val2)
                    {
                        if ($header == 'metas')
                        {
                            $this->result1[$cnt]['fields'][$n]['label'] = $text;
                            $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$n]['class'] = 'nochange';
                            $this->result2[$cnt]['fields'][$n]['label'] = $text;
                            $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$n]['class'] = 'nochange';
                            $n++;
                        }
                        else
                        {
                            $this->result1[$cnt]['fields'][$j]['label'] = $text;
                            $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$j]['class'] = 'nochange';
                            $this->result2[$cnt]['fields'][$j]['label'] = $text;
                            $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$j]['class'] = 'nochange';
                        }

                        $this->result1[$cnt]['type'] = 'array';
                        $this->result2[$cnt]['type'] = 'array';
                    }
                    else
                    {
                        $class1 = (trim($val2) == 'NA') ? 'red' : 'green';
                        $class1 = (trim($val1) == '' && trim($val2) == '1') ? 'green' : $class1;
                        $class1 = (trim($val1) == '1' && trim($val2) == '') ? 'green' : $class1;
                        $class1 = (trim($val1) == 'NA') ? 'nochange' : $class1;

                        $class2 = (trim($val1) == 'NA') ? 'blue' : 'green';
                        $class2 = (trim($val1) == '' && trim($val2) == '1') ? 'green' : $class2;
                        $class2 = (trim($val1) == '1' && trim($val2) == '') ? 'green' : $class2;
                        $class2 = (trim($val2) == 'NA') ? 'nochange' : $class2;

                        if ($header == 'metas')
                        {
                            $this->result1[$cnt]['fields'][$n]['label'] = $text;
                            $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$n]['class'] = $class1;
                            $this->result2[$cnt]['fields'][$n]['label'] = $text;
                            $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$n]['class'] = $class2;
                            $n++;
                        }
                        else
                        {
                            $this->result1[$cnt]['fields'][$j]['label'] = $text;
                            $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                            $this->result1[$cnt]['fields'][$j]['class'] = $class1;
                            $this->result2[$cnt]['fields'][$j]['label'] = $text;
                            $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                            $this->result2[$cnt]['fields'][$j]['class'] = $class2;
                        }

                        $this->result1[$cnt]['type'] = 'array';
                        $this->result2[$cnt]['type'] = 'array';
                    }
                    $j++;
                    if ($flag == 1)
                    {
                        break;
                    }
                }
            }
        }
//        $this->result1 = array_unique($this->result1);
//        $this->result2 = array_unique($this->result2);
    }

    /**
     * creates the array of choices for view
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */

    function getChoices(array $arr1, $header)
    {
      
       $index = $this->arraySearchKey($this->key, $this->flashstruct['data']);       
       $struct = array_keys($this->flashstruct['item']['choices']['fields']);

        $count = count($arr1);
        $arr = $arr1;
        $k = 0;
        $n = 0;

        for ($i = 0; $i < $count; $i++)
        {
            
            
            if (is_array($arr[$i]))
            {
                $j = 0;
                $flag = 0;
                $cnt = count($this->result1);
                $cnt = ($k > 0) ? $k : $cnt;

                      
                foreach ($arr[$i] as $k => $v)
                {
                     
                    switch ($header)
                    {
                     
                        // text is used to avoid counter variable in case of array
                        case 'text':
                            $this->result1[$cnt]['label'] = $this->key;
                            $text = $struct[$j]['header'];
                            $val1 = $this->filterTextToDisplay($arr1[$i][$k]);
                            break;

                        case 'columns':
                            
                            $this->result1[$cnt]['label'] = $this->key . ' ' . ($i + 1);
                            $text = $struct[$j];
                            $val1 = $this->filterTextToDisplay($arr1[$i][$k]);
                            break;

                        case 'metas':
                            if ($k == 0)
                            {
                                $k = $cnt;
                            }
                            $this->result1[$cnt]['label'] = $this->key;
                            $text = $arr1[$i]['text'];
                            $val1 = $arr1[$i]['val'];
                            $flag = 1;
                            break;
                    }
                    if (strstr($val1, '<img') || strstr($val1, '&lt;img') || strstr($val1, 'data=') || strstr($val1, 'src='))
                    {
                       // $val1 = $this->getMediaHtml($val1);
                    }
                    else
                    {
                        $val1 = '<span title="' . $val1 . '">' . $this->wrapText($val1, 100) . '</span>';
                    }

                    if (!is_array($arr1[$i]))
                    {
                        $val1 = 'NA';
                    }

                    if ($header == 'metas')
                    {
                        $this->result1[$cnt]['fields'][$n]['label'] = $text;
                        $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                        $this->result1[$cnt]['fields'][$n]['class'] = 'nochange';
                        $n++;
                    }
                    else
                    {
                        $this->result1[$cnt]['fields'][$j]['label'] = $text;
                        $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                        $this->result1[$cnt]['fields'][$j]['class'] = 'nochange sublabel';
                    }

                    $this->result1[$cnt]['type'] = 'array';

                    $j++;
                    if ($flag == 1)
                    {
                        break;
                    }
                }
            }
        }
        //$this->result1 = array_unique($this->result1);
    }

    /**
     * searches the array for specified key
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function arraySearchKey($needle_key, $array)
    {
        if (is_array($array) && !empty($array))
        {
            foreach ($array AS $key => $value)
            {
                if ($value['headerText'] == $needle_key)
                    return $key;

                if (is_array($value))
                {
                    if (($result = $this->arraySearchKey($needle_key, $value)) !== false)
                        return $result;
                }
            }
        }
        return false;
    }

    /**
     * sets the question details thats needs to be operated for version difference/view
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function formatAdvJsonToOldJson($tempAdvJson){
        $jsn            = new Services_JSON();
        $finalAdvJson   = $this->objectToArray($jsn->decode($tempAdvJson));
        foreach( $finalAdvJson as $k => $v ){
            if( strtolower($k) != 'choices' && strtolower($k) != 'hints' && strtolower($k) != 'exhibit' ){
               $onlyTextValue       = array_values($v);
               $finalAdvJson[$k]    = $onlyTextValue[0];
            }
        }
        return $finalAdvJson;
    }
    function getQuestionDetails(array $input)
    {
       
      
        $questionResult = $this->db->executeStoreProcedure('QuestionDetails', array($input['qid'], '-1'));
        $questionDetails = $questionResult['RS'];
        $str = $questionDetails[0]['FlashStructure'];
       
      
        //$this->str1 =  stripslashes($this->removeMediaPlaceHolder($questionDetails[0]['JSONData']));   
        //$this->str2 = stripslashes($this->removeMediaPlaceHolder($questionDetails[1]['JSONData']));
        
        $this->str1 =  stripslashes($this->removeMediaPlaceHolder($questionDetails[0]['advJSONData']));   
        $this->str2 = stripslashes($this->removeMediaPlaceHolder($questionDetails[1]['advJSONData']));
        
        
        $this->qsttemplate = new QuestionTemplate();
        $questionTemplateDetails = $this->qsttemplate->getTemplateCatDetById($questionDetails[0]['TemplateCategoryID']);
        $templateName = (strtolower($questionTemplateDetails['CategoryCode']) == 'mcq' || strtolower($questionTemplateDetails['CategoryCode']) == 'mcms') ? 'mcss' : strtolower($questionTemplateDetails['CategoryCode']);
        $newTemplateStructureTxt = file_get_contents(JSPATH . '/authoring/' . $templateName . "/" . $templateName . ".js");
        $str = str_replace(";", "", str_replace("var ts =", "", $newTemplateStructureTxt));      
        
        
        $jsn = new Services_JSON();
        $this->flashstruct =$this->objectToArray($jsn->decode($str));
        //$arr1 = $jsn->decode($this->str1);
        //$this->array1 = $this->objectToArray($arr1);
        
        
        $this->array1 = $this->formatAdvJsonToOldJson($this->str1); 
        unset($this->array1['correct_answer']);
       
        $this->refarr = $this->array1;

        //$arr2 = $jsn->decode($this->str2);
        //$this->array2 = $this->objectToArray($arr2);
        $this->array2 = $this->formatAdvJsonToOldJson($this->str2);
        
        $this->result1 = array();
        $this->result2 = array();
        $this->key = '';
    }

    /**
     * gets the version difference between specified questions
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function getVersionDiff(array $input)
    {
        $this->getQuestionDetails($input);
        if (!is_array($this->array2))
        {
            $this->array2 = $this->array1;
        }
     
        
        array_udiff_assoc($this->array1, $this->array2, array($this, 'compare'));        
        $this->showVersionHtml('diff', $input);
    }

    /**
     * creates view of version question
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function viewVersionQuestion(array $input)
    {
        $this->getQuestionDetails($input);
        array_map(array($this, 'getView'), $this->array1);
        
        $this->showVersionHtml('view', $input);
        Site::myDebug('----$inputviewVersionQuestion----');
        Site::myDebug($input);
    }
   
    /**
     * shows the formatted html
     *
     *
     * @access   public
     * @param    array  	$input
     * @return   mixed
     *
     */
    function showVersionHtml($type, $input)
    {
        header('Content-Type: text/html');
        $labels = array_keys($this->getValueArray($this->result1, 'label', 'multiple', 'array'));
        $result1 = array_merge(array(), $this->result1);
        $result2 = array_merge(array(), $this->result2);
        $lbl_count = count($labels);
        list($version_no1, $version_no2) = explode(',', $input['versionno']);
        $backlink = ($input['backlink'] != "" ) ? $input['backlink'] : "";
       include $this->cfg->rootPath . '/views/templates/' . $this->quadtemplate . '/question/QuestionVersion.php';
    }

    /* Code for question difference ends */

    /**
     * get image html
     *
     *
     * @access   public
     * @param    array  	$mediacontent
     * @return   string
     *
     */
    function getMediaHtml($mediacontent)
    {
        if (!strstr($mediacontent, 'MathMLtoImage.php'))
        {
            global $CONFIG, $APPCONFIG;
            $auth = new Authoring();
            $mediaObj = new Media();
            $mediacontent = html_entity_decode($mediacontent);
            if (strstr($mediacontent, '<img') || strstr($mediacontent, '&lt;img'))
            {
                $imgname = "thumb_" . $auth->getImageUrl($mediacontent, '', '1');
                $path = $mediaObj->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/thumb', 'protocol' => 'http'));
                $mediastr = $auth->createImageObject($path . $imgname, '');
                return $mediastr;
            }
            elseif (strstr($mediacontent, 'data=') || strstr($mediacontent, '<object') || strstr($mediacontent, '&lt;object') || strstr($mediacontent, 'src=') || strstr($mediacontent, 'value='))
            {
                @preg_match_all("/(value|data|src)=\"([^\"]*)/", $mediacontent, $media);
                $cnt = count($media) - 1;
                $count = count($media[$cnt]);
                $video = $media[$cnt][$count - 1];
                $videourl = 'http://' . $_SERVER['HTTP_HOST'] . $video;

                return '<embed height="180" width="225" wmode="transparent" src="' . $this->cfg->wwwroot . '/assets/flash player/flowplayer-3.0.2.swf" allowfullscreen="true" allowscriptaccess="always" quality="high"\n\
                type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" id="player_api" bgcolor="#000000" name="player_api" \n\
                flashvars=\'config={"clip":{"autoPlay":false},"playerId":"player","playlist":[{"url":"' . $video . '","autoPlay":false}]}\'/>';
            }
        }
        else
        {
            return $mediacontent;
        }
    }

    public function questionData($QuestID)
    {
        $QuestDetail = $this->db->executeStoreProcedure('QuestionDetails', array($QuestID, '-1'), 'details');
        return $QuestDetail;
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
    public function searchCompositeSection(array $input)
    {
        global $DBCONFIG;
        Site::myDebug('-----compoiste');
        Site::myDebug($input);
        $EntityTypeID = $input["EntityTypeID"];
        $EntityID = $input["EntityID"];
        $SectionTitle = $input['SectionTitle'];
        $SecID = $input["SecID"];
        //$question_title = $input["question_title"];
        $qtype = $input["qtype"];
        $question_tags = $input["question_tags"];
        $sel_banks = $input["sel_banks"];
        $sel_quiz = $input["sel_quiz"];
        $taxonomy = $input["taxonomyNodeIds"];
        $orderBy = $input["orderBy"];
        $orderType = $input["orderType"];

        if (!empty($SectionTitle))
        { // Search by Name
            $getSection = "SELECT mrq.ID AS MapRepID ,mrq.SectionName,ass.ID, ass.AssessmentName FROM Assessments AS ass LEFT JOIN MapRepositoryQuestions AS mrq ON ass.ID = mrq.EntityID 
                                     WHERE mrq.isEnabled = 1 AND mrq.SectionName <> '' AND ass.ID <> '" . $EntityID . "' AND mrq.SectionName LIKE '%" . $SectionTitle . "%' GROUP BY mrq.ID
                                    ORDER BY {$orderBy} {$orderType}
                                    LIMIT {$input['pgnstart']},{$input['pgnstop']} ";
            $getSectionList = $this->db->getRows($getSection);
            $getSecCount = count($getSectionList);

            $questionResult['RS'] = $getSectionList;

            $questionResult['secCount'] = $getSecCount;

            return $questionResult;
        }
        else
        { // Search by Assessment Name
            $getSection = "SELECT mrq.ID AS MapRepID ,mrq.SectionName,ass.ID, ass.AssessmentName FROM Assessments AS ass LEFT JOIN MapRepositoryQuestions AS mrq ON ass.ID = mrq.EntityID 
            WHERE mrq.isEnabled = 1 AND mrq.SectionName <> ''  AND ass.ID IN (" . $sel_quiz . ")GROUP BY mrq.ID
                                    ORDER BY {$orderBy} {$orderType}
                                    LIMIT {$input['pgnstart']},{$input['pgnstop']} ";
            $getSectionList = $this->db->getRows($getSection);
            $getSecCount = count($getSectionList);
            Site::myDebug('------getSecCount');
            Site::myDebug($getSecCount);
            // $getSectionList       =  $questionResult['RS'];
            /* $qtp = new QuestionTemplate();
              $templateLayouts = $qtp->templateLayout();

              $i = 0;
              if (!empty($questionlist)) {
              foreach ($questionlist as $question) {
              $questionlist[$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
              $i++;
              }
              } */
            $questionResult['RS'] = $getSectionList;
            $questionResult['secCount'] = $getSecCount;


            Site::myDebug('------sectionList2');
            Site::myDebug($questionResult);
            $questionResult['secCount'] = $getSecCount;


            Site::myDebug('------sectionList2');
            Site::myDebug($questionResult);
            return $questionResult;
        }
    }

    function filterTextToDisplay($text)
    {
        Site::myDebug('-----$text------');
        Site::myDebug($text);
        $objAuthoring = new Authoring();
        if ($text != "")
        {
            $text = trim($text);
            $text = $objAuthoring->hashCodeToHtmlEntity($text);
            $text = preg_replace("/<span[^>]+\>/i", "", $text);
            $text = str_replace("</span>", "", $text);
            $text = preg_replace("/<SPAN[^>]+\>/i", "", $text);
            $text = str_replace("</SPAN>", "", $text);
            $text = str_replace(' "=""', ' ', $text);
            $text = str_replace("&nbsp;", " ", $text);
        }
        if ($text == "")
        {
            $text = " ";
        }
        unset($objAuthoring);
        Site::myDebug($text);
        Site::myDebug('----------------');
        return $text;
    }

    /*
     * Search Question as per user defined criteria and with bank/assessment ids 
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
     *  @return   array         $serachInfo
     *
     */

    function questionSearchListAddQuestion(array $input, $entityID, $entityTypeID, $asmtID, $filter, $tag, $taxonomy)
    {
        $auth = new Authoring();
        global $DBCONFIG;
        
        //$searchcrieteria = '{"keyinfo":{"title":{"val":"Français","mainfilter":"none","filtertype":"include"},"users":{"id":null,"name":"","mainfilter":"none","filtertype":"include"},"templates":{"id":null,"name":"","mainfilter":"none","filtertype":"include"},"date":{"start":"","end":"","mainfilter":"none","filtertype":"include"},"usagecount":{"minusagecount":"","maxusagecount":"","mainfilter":"none","filtertype":"exclude"},"difficulty":{"val":"","mainfilter":"none","filtertype":"exclude"}},"classification":{"tags":{"val":"","mainfilter":"none","filtertype":"include"},"taxonomy":{"id":"","name":"","mainfilter":"none","filtertype":"include"}},"metadata":{"key":{"id":"","name":"","mainfilter":"none","filtertype":"include"},"value":{"id":"","name":"","type":"input","mainfilter":"none"}}}';
        //$searchcrieteria = addslashes($searchcrieteria);        
        
        if ($input['hdn_searchcrieteria'] != '')
        {
//            $input['jsoncrieteria'] = utf8_encode(urldecode($input['hdn_searchcrieteria']));
            $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        }
        else if ($input['par_hdn_searchcrieteria'] != '')
        {
//            $input['jsoncrieteria'] = utf8_encode(urldecode($input['par_hdn_searchcrieteria']));
            $input['jsoncrieteria'] = urldecode($input['par_hdn_searchcrieteria']);
        }
        $json = json_decode($input['jsoncrieteria']); //echo "<pre>"; print_r($json);  die();
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '' || $input['par_hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst.Count' : $input['pgndc'] . ',qst.Count';
        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tag;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomy;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1'; //echo $json->metadata->value->id; die();
		$valueStr	=	"";
		if( $searchtype == 'advanced'){
			//if($json->metadata->value->type == 'input'){
				$valueString = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
				if($valueString != "-1"){
					$valueArray	=	explode(",",$valueString);
					$whereCond = '';
					foreach ($valueArray as $v)
					{
						if(!is_numeric($v)){
							$whereCond .= " mdk.MetaDataValue LIKE '%" . $v . "%' OR ";
						}else{
							$valueStr	=	$valueStr.",".$v;
						}
												
					}
					$whereCond = rtrim($whereCond, " OR ");
					
					$getMetadataValueID = "SELECT group_concat( ID ) as valueList from MetaDataValues mdk where ".$whereCond." AND mdk.isEnabled = '1'";
					$getMetadataValueList = $this->db->getSingleRow($getMetadataValueID);
					
					if($getMetadataValueList['valueList']){
						$value	=	$getMetadataValueList['valueList'];
						if(trim($valueStr, ",")){
							$value	=	$value.$valueStr;
						}
							
					}elseif(trim($valueStr, ",")){
						$value	=	trim($valueStr, ",");
					}else{
						$value	=	'1';
						$key	=	'1';
					}
											
				}
			/* }else{
				$value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
			} */
		}else{ //PED96 : Sprint 2 : QUAD-310 - start
			$value = ($json->metadata->value->name != '') ? ($json->metadata->value->name) : '-1';
		}
       // $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchMetadataArr = array('value' => $value, 'value_filter' => $value_filter);
		if( $searchtype == 'basic'){
			$value = $this->searchMetadataValue($searchMetadataArr); //echo "<pre>"; print_r($value); die();
		}
        //PED96 : Sprint 2 : QUAD-310 - end
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['fromsearchdate'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['tosearchdate'];

        //Added condition for similar date issue
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
        /* Added for Last update in search*/
        $startModDate =($input['fromlastdate']!='')? $input['fromlastdate'] : -1;
        $endModDate   =($input['tolastdate']!='')? $input['tolastdate'] : -1;
        $dateModFilter  = $input['lastDate'];
        if ($startModDate != "" || $endModDate != "")
        {
            if ($startModDate == $endModDate)
            {
                $newStartModDate = $startModDate;
                $newEndModDate = '';
            }
            else
            {
                 $newStartModDate = $startModDate;
                 $newEndModDate = $endModDate;
            }
        }
         /* END -Added for Last update in search */
        
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];
        $question_status = ($json->keyinfo->question_status->id != '') ? ($json->keyinfo->question_status->id) : $input['ownerName'];  //QUAD-86
        //QUAD-85 :: Start
        $searchEntityIDs = ($json->searchInfo->searchResult->id != '') ? $json->searchInfo->searchResult->id : $searchInfoIDs;
        $searchEntityType = ($json->searchInfo->searchResult->EntityTypeID != '') ? $json->searchInfo->searchResult->EntityTypeID : '-1';
        if ($searchEntityType != '-1' && $searchEntityType == 'Assessment')
        {
            $searchEntityTypeID = '2';
        }
        if ($searchEntityType != '-1' && $searchEntityType == 'Bank')
        {
            $searchEntityTypeID = '1';
        }
        //QUAD-85 :: End
        $search = ($search == '') ? -1 : addslashes(trim($search));
        //$search = $this->replaceQuoteForAuthoring(stripslashes($auth->hashCodeToHtmlEntity($search)));

        $owner = ($owner == '') ? -1 : $owner;
        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $value_filter = $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
		$question_status_filter = ($json->keyinfo->question_status->filtertype == 'exclude') ? 'exclude' : 'include';   //QUAD-86
        $searchEntityIDs_filter = ($json->searchInfo->searchResult->filtertype == 'exclude') ? 'exclude' : 'include'; //QUAD-85

      //  $input['pgnob'] = '-1';

        //$input['pgndc']         = "if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' )  as EditPage, mrq.ParentID, qtp.isStatic ,qst.Count,  qst.AuthoringStatus, qst.AuthoringUserID ";
        $input['pgndc'] = "-1";

        // Advanced Search with Program and Product

        $searchProgramIDs = ($json->searchInfo->searchResultProgram->id != '') ? $json->searchInfo->searchResultProgram->id : '-1';
        $searchProgramFilter = ($json->searchInfo->searchResultProgram->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchProductIDs = ($json->searchInfo->searchResultProduct->id != '') ? $json->searchInfo->searchResultProduct->id : '-1';
        $searchProductFilter = ($json->searchInfo->searchResultProduct->filtertype == 'exclude') ? 'exclude' : 'include';

        $searchAsmtIDs = ($json->searchInfo->searchResultAssessment->id != '') ? $json->searchInfo->searchResultAssessment->id : '-1';
        $searchAsmtFilter = ($json->searchInfo->searchResultAssessment->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchBnkIDs = ($json->searchInfo->searchResultBank->id != '') ? $json->searchInfo->searchResultBank->id : '-1';
        $searchBnkFilter = ($json->searchInfo->searchResultBank->filtertype == 'exclude') ? 'exclude' : 'include';


        $bulkEditEntityIds = '-1';
        $bnkQuestionResult = array();
        $asmtQuestionResult = array();
        if ($searchProgramIDs != '-1' || $searchProductIDs != '-1' || $searchAsmtIDs != '-1' || $searchBnkIDs != '-1')
        {
            $searchDataArr = array('searchProgramIDs' => $searchProgramIDs,
                'searchProgramFilter' => $searchProgramFilter,
                'searchProductIDs' => $searchProductIDs,
                'searchProductFilter' => $searchProductFilter,
                'searchAsmtIDs' => $searchAsmtIDs,
                'searchAsmtFilter' => $searchAsmtFilter,
                'searchBnkIDs' => $searchBnkIDs,
                'searchBnkFilter' => $searchBnkFilter,
                'bankEntityTypeId' => '1',
                'asmtEntityTypeId' => '2',
            );
            $progCls = new Program();
            $advSearchQuestIds = $progCls->globalAdvSearchAsmtBnkProdQuestIds($searchDataArr);
            $storeProcedureName='';
            $storeProcedureArray='';
            if (($advSearchQuestIds['bnkQuest_entityTypeId'] == '1' && !empty($advSearchQuestIds['bnkQuestIds'])) || ($advSearchQuestIds['asmtQuest_entityTypeId'] == '2' && !empty($advSearchQuestIds['asmtQuestIds'])))
            {
                if (!empty($advSearchQuestIds['bnkQuestIds']))
                {
                    $advSearchBnkQuestIds = implode(",", $advSearchQuestIds['bnkQuestIds']);
                    $bnkEntityTypeId = 1;
                }
                else
                {
                    $advSearchBnkQuestIds = '-1';
                    $bnkEntityTypeId = '-1';
                }

                if (!empty($advSearchQuestIds['asmtQuestIds']))
                {
                    $advSearchAsmtQuestIds = implode(",", $advSearchQuestIds['asmtQuestIds']);
                    $asmtEntityTypeId = 2;
                }
                else
                {
                    $advSearchAsmtQuestIds = '-1';
                    $asmtEntityTypeId = '-1';
                }
                
                 $storeProcedureName='QuestionSearchListAddQuestion';

                 $storeProcedureArray=array($input['pgnob'],
                    $input['pgnot'],
                    $input['pgnstart'],
                    $input['pgnstop'],
                    $search,
                    '-1',
                    $searchEntityTypeID, //this is an entity type id
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
                    $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds ,$startModDate,$endModDate,$dateModFilter);
                
                 $questionlist = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);
                 
                /*
                $questionlist = $this->db->executeStoreProcedure('QuestionSearchListAddQuestion', array($input['pgnob'],
                    $input['pgnot'],
                    $input['pgnstart'],
                    $input['pgnstop'],
                    $search,
                    '-1',
                    $searchEntityTypeID, //this is an entity type id
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
                    $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds
                ));
                 
                 */
            }
        }
        else
        {

            $advSearchAsmtQuestIds = '-1';
            $searchAsmtFilter = '-1';
            $asmtEntityTypeId = '-1';
            $advSearchBnkQuestIds = '-1';
            $searchBnkFilter = '-1';
            $bnkEntityTypeId = '-1';

            
             $storeProcedureName='QuestionSearchListAddQuestion';
             $storeProcedureArray=array($input['pgnob'],
                $input['pgnot'],
                $input['pgnstart'],
                $input['pgnstop'],
                $search,
                '-1',
                $searchEntityTypeID, //this is an entity type id
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
                $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds,$startModDate,$endModDate,$dateModFilter);
             
               $questionlist = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);
             /*
            $questionlist = $this->db->executeStoreProcedure('QuestionSearchListAddQuestion', array($input['pgnob'],
                $input['pgnot'],
                $input['pgnstart'],
                $input['pgnstop'],
                $search,
                '-1',
                $searchEntityTypeID, //this is an entity type id
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
                $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds
            ));
              
            */
        }

        // End of Advanced Search with Program and Product		
        //$questionlist['TC'] = $this->getValueArray($questionlist['RS'], "@QuestionIDCount");
        //unset($questionlist['RS'][$input['pgnstop']]);

        $qtp = new QuestionTemplate();
        $usr = new User();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;
        if (!empty($questionlist['RS']))
        {
            foreach ($questionlist['RS'] as $question)
            {
                $questionlist['RS'][$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
                $questionlist['RS'][$i]["QuesPreviewRight"] = $usr->getMapEntityRightDetails($question['EntityID'],2,$this->session->getValue('userID'));
                $i++;
            }
        }
        /* PEARSON QUAD-170  START */
        //$input['entityid']      = $asmtID;
        $input['entityid'] = $entityID;
        /* PEARSON QUAD-170  END */
        $input['entitytypeid'] = $entityTypeID;
        $input['spcall'] = $questionlist['QR'];
        $input['count'] = $questionlist['TC'];
        $input['storeProcedureArray']= json_encode($storeProcedureArray);
        $input['storeProcedureName']=$storeProcedureName;
        $input['searchtype']=0;
        
        if (trim($input['hdn_searchcrieteria']) != '')
        {
            $this->saveAdvSearchCrieteria($input);
        }
        return $questionlist;
    }
	
	
	/* For Get Advanced Search Data set */
	/* @created on  : 17th June , 2016
     * @Author      : Moumita 
	 */
	
	
	function getAdvSearchData(array $input, $entityID, $entityTypeID, $asmtID, $filter, $tag, $taxonomy)
    {
        $auth = new Authoring();
        global $DBCONFIG;
        
        if ($input['hdn_searchcrieteria'] != '')
        {
            $input['jsoncrieteria'] = $jsoncrieteria	=	urldecode($input['hdn_searchcrieteria']);
			$searchtype				= 'advanced';
        }else{
			$searchtype				= 'basic';
		}
        
        $json = json_decode($jsoncrieteria);  //echo "<pre>"; print_r($json);  //print_r($input);
		$criteriaArray = array();
		$inputArray = array();
		foreach($json as $outerKey => $outerVal){
			foreach($outerVal as $innerKey => $innerVal){
				foreach($innerVal as $key => $val){
					if($key == 'filtertype'){
						$filterArray[$innerKey.'_filter']	=	($val == 'exclude')?'exclude':'include';						
					}
				}
			}
			
		}
		
		foreach($input as $key => $val){
			$inputArray[$key]	=	($val != "")?$val:'-1';
		}
		
        $search 			= ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];    
        
        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tag;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomy;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1'; //echo $json->metadata->value->id; die();
		$valueStr	=	"";
		if( $searchtype == 'advanced'){
			//if($json->metadata->value->type == 'input'){
				$valueString = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
				if($valueString != "-1"){
					$valueArray	=	explode(",",$valueString);
					$whereCond = '';
					foreach ($valueArray as $v)
					{
						if(!is_numeric($v)){
							$whereCond .= " mdk.MetaDataValue LIKE '%" . $v . "%' OR ";
						}else{
							$valueStr	=	$valueStr.",".$v;
						}
												
					}
					$whereCond = rtrim($whereCond, " OR ");
					
					$getMetadataValueID = "SELECT group_concat( ID ) as valueList from MetaDataValues mdk where ".$whereCond." AND mdk.isEnabled = '1'";
					$getMetadataValueList = $this->db->getSingleRow($getMetadataValueID);
					
					if($getMetadataValueList['valueList']){
						$value	=	$getMetadataValueList['valueList'];
						if(trim($valueStr, ",")){
							$value	=	$value.$valueStr;
						}
							
					}elseif(trim($valueStr, ",")){
						$value	=	trim($valueStr, ",");
					}else{
						$value	=	'1';
						$key	=	'1';
					}
											
				}
			/* }else{
				$value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
			} */
		}else{ //PED96 : Sprint 2 : QUAD-310 - start
			$value = ($json->metadata->value->name != '') ? ($json->metadata->value->name) : '-1';
			$searchMetadataArr = array('value' => $value, 'value_filter' => $value_filter);
			$value = $this->searchMetadataValue($searchMetadataArr);
		}
      
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        
        $startdate = ($json->keyinfo->createddate->start != '') ? ($json->keyinfo->createddate->start) : -1;
        $enddate = ($json->keyinfo->createddate->end != '') ? ($json->keyinfo->createddate->end) : -1;

        //Added condition for similar date issue
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
        		
		$startModDate = ($json->keyinfo->modifieddate->start != '') ? ($json->keyinfo->modifieddate->start) :  -1;
        $endModDate = ($json->keyinfo->modifieddate->end != '') ? ($json->keyinfo->modifieddate->end) :  -1;
		
        if ($startModDate != "" || $endModDate != "")
        {
            if ($startModDate == $endModDate)
            {
                $newStartModDate = $startModDate;
                $newEndModDate = '';
            }
            else
            {
                 $newStartModDate = $startModDate;
                 $newEndModDate = $endModDate;
            }
        }
         /* END -Added for Last update in search */
        
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : '-1' ;
       
        $search = ($search == '') ? -1 : addslashes(trim($search));
        
        $owner = ($owner == '') ? -1 : $owner;
       
        $input['pgndc'] = "-1";
		
		if($entityTypeID == '1'){
			$storeProcedureName='BankSearchListAdvSearch'; 
			
		}else if($entityTypeID == '2'){
			$storeProcedureName='AssessmentSearchListAdvSearch'; 
			
		}else if($entityTypeID == '3'){
			$storeProcedureName='QuestionSearchListAdvSearch'; //'QuestionSearchListAddQuestion';
		}
        
		$storeProcedureArray=array($input['pgnob'],
			$input['pgnot'],
			$input['pgnstart'],
			$input['pgnstop'],
			$search,
			//'-1',
			//$searchEntityTypeID, //this is an entity type id  for basic search (need to check)
			$this->session->getValue('userID'),
			$input['pgndc'],
			$tags,
			$taxo,
			$owner,
			$this->session->getValue('instID'),
			$key,
			$value,
			$difficulty, $template, $newStartDate, $newEndDate, $searchtype,
			$filterArray['title_filter'], $filterArray['users_filter'], $filterArray['templates_filter'], $filterArray['createddate_filter'], $filterArray['difficulty_filter'], $filterArray['tags_filter'], $filterArray['taxonomy_filter'], $filterArray['key_filter'], $filterArray['value_filter'],
			$startModDate,$endModDate,$filterArray['modifieddate_filter'],addslashes($input['sSearch']));
		 
		   $questionlist = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);

        $qtp = new QuestionTemplate();
        $usr = new User();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;
        if (!empty($questionlist['RS']))
        {
            foreach ($questionlist['RS'] as $question)
            {
                $questionlist['RS'][$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
				if($question['EntityTypeID'] == 1){
					$questionlist['RS'][$i]["QuesPreviewRight"] = $usr->getMapEntityRightDetails($question['EntityID'],1,$this->session->getValue('userID'));
				}else if($question['EntityTypeID'] == 2){
					$questionlist['RS'][$i]["QuesPreviewRight"] = $usr->getMapEntityRightDetails($question['EntityID'],2,$this->session->getValue('userID'));
				}
                
                $i++;
            }
        }
        /* PEARSON QUAD-170  START */
        //$input['entityid']      = $asmtID;
        $input['entityid'] = $entityID;
        /* PEARSON QUAD-170  END */
        $input['entitytypeid'] = $entityTypeID;
        $input['spcall'] = $questionlist['QR'];
        $input['count'] = $questionlist['TC'];
        $input['storeProcedureArray']= json_encode($storeProcedureArray);
        $input['storeProcedureName']=$storeProcedureName;
        $input['searchtype']=0;
        
        /* if (trim($input['hdn_searchcrieteria']) != '')
        {
            $this->saveAdvSearchCrieteria($input);
        } */
        return $questionlist;
    }

    
	function saveAdvSearch(array $input){
		global $DBCONFIG;
		//$searchtype = (trim($input['savesearch']) != '')?1:0;
		if($DBCONFIG->dbType=='Oracle')
		{
			$input['spcall']=(String)htmlentities($input['spcall'],ENT_QUOTES);
		}else{
			//$input['spcall']=(String)addslashes(stripslashes($input['spcall']));
		}
           
        $array      = array(intval($input['searchid']), $input['searchname'], (string)$input['hdn_searchcrieteria'], intval($this->session->getValue('mapUserID')), $input['entitytypeid'], $this->currentDate(), '1', intval($input['resultCount']),$input['storeProcedureName']);		
        $this->db->executeStoreProcedure('ManageSearchCrieteriaAdvSearch', $array);
		
	}
	
    
     /*@Manish
     * Saved advance search
     * SavedAdvanceSearch
     */
    
    function savedAdvanceSearch($procedure,$proArray,$searchId=''){
        
        $questionlist = $this->db->executeStoreProcedure($procedure,$proArray);
		$SearchCount	=	($questionlist['TC'])?$questionlist['TC']:0;
		if($searchId && ($procedure	==	'QuestionSearchListAddQuestion')){
			$updateQuery = "UPDATE SearchCrieteria set SearchCount = '{$SearchCount}', ModDate = '{$this->currentDate()}' where ID = $searchId ";
			$this->db->execute($updateQuery);
		}
		 
		return $questionlist;
        
    }
    
    function getVersionDiffAdv(array $input)
    {
        $this->getQuestionDetailsAdv($input);
        if (!is_array($this->array2))
        {
            $this->array2 = $this->array1;
        }

        array_udiff_assoc($this->array1, $this->array2, array($this, 'compareAdv'));
        $this->showVersionHtml('diff', $input);
    }

    function getQuestionDetailsAdv(array $input)
    {
        $questionResult = $this->db->executeStoreProcedure('QuestionDetails', array($input['qid'], '-1'));
        $questionDetails = $questionResult['RS'];

        $this->qsttemplate = new QuestionTemplate();
        $questionTemplateDetails = $this->qsttemplate->getTemplateCatDetById($questionDetails[0]['TemplateCategoryID']);
        $templateName = (strtolower($questionTemplateDetails['CategoryCode']) == 'mcq' || strtolower($questionTemplateDetails['CategoryCode']) == 'mcms') ? 'mcss' : strtolower($questionTemplateDetails['CategoryCode']);
        $newTemplateStructureTxt = file_get_contents(JSPATH . '/authoring/' . $templateName . "/" . $templateName . ".js");
        $str = str_replace(";", "", str_replace("var ts =", "", $newTemplateStructureTxt));

        //$str                = $questionDetails[0]['FlashStructure'];
        $this->str1 = stripslashes($this->removeMediaPlaceHolder($questionDetails[0]['advJSONData']));
        $this->str2 = stripslashes($this->removeMediaPlaceHolder($questionDetails[1]['advJSONData']));

        $jsn = new Services_JSON();
        $this->flashstruct = $this->objectToArray($jsn->decode($str));

        $arr1 = $jsn->decode($this->str1);
        $this->array1 = $this->objectToArray($arr1);
        $this->refarr = $this->array1;

        $arr2 = $jsn->decode($this->str2);
        $this->array2 = $this->objectToArray($arr2);
        $this->result1 = array();
        $this->result2 = array();
        $this->key = '';
    }

    function compareAdv($array3, $array4)
    {
        $this->key = array_search($array3, $this->refarr);
        $this->key = strtolower(str_replace('_', ' ', $this->key));
        $key1 = str_replace(' ', '_', $this->key);
        array_shift($this->refarr);
        if (is_array($array3))
        {
            switch (trim(strtolower($key1)))
            {
                case 'question_stem':
                case 'question_title':
                case 'hint':
                case 'image':
                case 'global_correct_feedback':
                case 'global_incorrect_feedback':
                case 'notes_editor':
                    $this->manageQuestionVersionData($array3, $array4, 'text');   // text is used to avoid counter variable in case of array
                    break;

                case 'choices':
                case 'container_text':
                case 'column_text':
                    $this->manageQuestionVersionChoices($array3, $array4, 'columns');
                    break;

                case 'metadata':
                    $this->manageQuestionVersionData($array3, $array4, 'metas');
                    break; 
            }
        }
        else
        {
            $tmpArr = array_diff((array) $array3, (array) $array4);

            $cnt = count($this->result1);
            if (strstr($array3, '<img') || strstr($array3, '&lt;img') || strstr($array3, 'data=') || strstr($array3, 'src='))
            {
                $array3 = $this->getMediaHtml($array3);
            }
            else
            {
                $array3 = '<span title="' . $array3 . '">' . $this->wrapText($array3, 48) . '</span>';
            }
            if (strstr($array4, '<img') || strstr($array4, '&lt;img') || strstr($array4, 'data=') || strstr($array4, 'src='))
            {
                $array4 = $this->getMediaHtml($array4);
            }
            else
            {
                $array4 = '<span title="' . $array4 . '">' . $this->wrapText($array4, 48) . '</span>';
            }
            if (!empty($tmpArr))
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'green';
                $this->result1[$cnt]['type'] = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'green';
                $this->result2[$cnt]['type'] = 'string';
            }
            else
            {
                $this->result1[$cnt]['label'] = $this->key;
                $this->result1[$cnt]['value'] = $array3;
                $this->result1[$cnt]['class'] = 'nochange';
                $this->result1[$cnt]['type'] = 'string';
                $this->result2[$cnt]['label'] = $this->key;
                $this->result2[$cnt]['value'] = $array4;
                $this->result2[$cnt]['class'] = 'nochange';
                $this->result2[$cnt]['type'] = 'string';
            }
        }

        $this->labels = array_keys($this->getValueArray($this->result1, 'label', 'multiple', 'array'));
        $this->result1 = array_merge(array(), $this->result1);
        $this->result2 = array_merge(array(), $this->result2);
        return 0;
    }

    function manageQuestionVersionData(array $arr1, array $arr2, $header)
    {
        foreach ($this->flashstruct['item'] as $eachStructKey => $eachStructVal)
        {
			foreach ($eachStructVal['fields'] as $eachSubStructKey => $eachSubStructVal)
			{
				if ($eachSubStructKey == str_replace(" ", "_", $this->key))
				{
					$mainKey = $eachStructKey;
					$textKey = $eachSubStructVal['key'];
					break;
				}
			}
        }

        $count = (count($arr1) > count($arr2)) ? count($arr1) : count($arr2);
        $arr = (count($arr1) > count($arr2)) ? $arr1 : $arr2;
        $k = 0;
        $n = 0;
        $objAuthoring = new Authoring();
        $j = 0;
        $flag = 0;
        $cnt = count($this->result1);
        $cnt = ($k > 0) ? $k : $cnt;

        switch ($header)
        {
            case 'text':
                $this->result1[$cnt]['label'] = ucwords($this->key);
                $text = ucwords($this->key);
                $val1 = $this->filterTextToDisplay($arr1[$textKey]);
                $val2 = $this->filterTextToDisplay($arr2[$textKey]);
                break;

            case 'metas':
                if ($k == 0)
                {
                    $k = $cnt;
                }
                $this->result1[$cnt]['label'] = ucwords($this->key);
                $text = ucwords($this->key);
                $val1 = $this->filterTextToDisplay($arr1[$textKey]);
                $val2 = $this->filterTextToDisplay($arr2[$textKey]);
                $flag = 1;
                break;
        }
        if (strstr($val1, '<img') || strstr($val1, '&lt;img') || strstr($val1, 'data=') || strstr($val1, 'src='))
        {
            //$val1 = $this->getMediaHtml($val1);
        }
        else
        {
            $val1 = '<span title="' . $val1 . '">' . $this->wrapText($val1, 48) . '</span>';
        }
        if (strstr($val2, '<img') || strstr($val2, '&lt;img') || strstr($val2, 'data=') || strstr($val2, 'src='))
        {
            //$val2 = $this->getMediaHtml($val2);
        }
        else
        {
            $val2 = '<span title="' . $val2 . '">' . $this->wrapText($val2, 48) . '</span>';
        }

		if(!($isChoiceFlag))
		{
			if (!($arr1[$textKey]))
			{
				$val1 = 'NA';
			}
			if (!($arr2[$textKey]))
			{
				$val2 = 'NA';
			}
		}
        
        if ($val1 === $val2)
        {
            if ($header == 'metas')
            {
                $this->result1[$cnt]['fields'][$n]['label'] = $text;
                $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                $this->result1[$cnt]['fields'][$n]['class'] = 'nochange';
                $this->result2[$cnt]['fields'][$n]['label'] = $text;
                $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                $this->result2[$cnt]['fields'][$n]['class'] = 'nochange';
                $n++;
            }
            else
            {
                $this->result1[$cnt]['fields'][$j]['label'] = $text;
                $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                $this->result1[$cnt]['fields'][$j]['class'] = 'nochange';
                $this->result2[$cnt]['fields'][$j]['label'] = $text;
                $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                $this->result2[$cnt]['fields'][$j]['class'] = 'nochange';
            }

            $this->result1[$cnt]['type'] = 'array';
            $this->result2[$cnt]['type'] = 'array';
        }
        else
        {
            $class1 = (trim($val2) == 'NA') ? 'red' : 'green';
            $class1 = (trim($val1) == '' && trim($val2) == '1') ? 'green' : $class1;
            $class1 = (trim($val1) == '1' && trim($val2) == '') ? 'green' : $class1;
            $class1 = (trim($val1) == 'NA') ? 'nochange' : $class1;

            $class2 = (trim($val1) == 'NA') ? 'blue' : 'green';
            $class2 = (trim($val1) == '' && trim($val2) == '1') ? 'green' : $class2;
            $class2 = (trim($val1) == '1' && trim($val2) == '') ? 'green' : $class2;
            $class2 = (trim($val2) == 'NA') ? 'nochange' : $class2;

            if ($header == 'metas')
            {
                $this->result1[$cnt]['fields'][$n]['label'] = $text;
                $this->result1[$cnt]['fields'][$n]['value'] = $val1;
                $this->result1[$cnt]['fields'][$n]['class'] = $class1;
                $this->result2[$cnt]['fields'][$n]['label'] = $text;
                $this->result2[$cnt]['fields'][$n]['value'] = $val2;
                $this->result2[$cnt]['fields'][$n]['class'] = $class2;
                $n++;
            }
            else
            {
                $this->result1[$cnt]['fields'][$j]['label'] = $text;
                $this->result1[$cnt]['fields'][$j]['value'] = $val1;
                $this->result1[$cnt]['fields'][$j]['class'] = $class1;
                $this->result2[$cnt]['fields'][$j]['label'] = $text;
                $this->result2[$cnt]['fields'][$j]['value'] = $val2;
                $this->result2[$cnt]['fields'][$j]['class'] = $class2;
            }

            $this->result1[$cnt]['type'] = 'array';
            $this->result2[$cnt]['type'] = 'array';
        }
        $j++;
        if ($flag == 1)
        {
            break;
        }
    }
	
    function manageQuestionVersionChoices(array $arr1, array $arr2, $header)
	{
            //print "<pre>";
           
            //print_r($header);
            //echo count($arr1);
            //echo "****";
            $first_array_count      = (int)count($arr1);
            $second_array_count     = (int)count($arr2);
            $main_looping_array     =  $arr1;
            $sub_looping_array      =  $arr2;
            
            if( $first_array_count < $second_array_count ){
                $main_looping_array   = $arr2;
                $sub_looping_array    = $arr1;
            }
            //echo count($arr2);
		foreach ($this->flashstruct['item'] as $eachStructKey => $eachStructVal)
        {
			if ($eachStructKey == str_replace(" ", "_", $this->key))
			{
				$mainKey = $eachStructKey;
				$isChoiceFlag = true;
				break;
			}
        }
		
		if($header == "columns")
		{
                    if( $first_array_count >= $second_array_count ){
                            foreach($arr1 as $eachArrKey => $eachArrVal)
                            {
                                $cnt = count($this->result1);$i = 0;
                                $this->result1[$cnt]['label'] = ucwords($this->key) . ' ' . ($eachArrKey + 1);
                                $this->result2[$cnt]['label'] = ucwords($this->key) . ' ' . ($eachArrKey + 1);
                                foreach($eachArrVal as $eachSubArrKey => $eachSubArrVal)
                                {
                                        $text = ucwords($eachSubArrKey);					
                                        $val1 = $this->filterTextToDisplay($eachSubArrVal);
                                        $val2 = $this->filterTextToDisplay($arr2[$eachArrKey][$eachSubArrKey]);
                                        //echo '$arr1val1-'.$val1.'<br>';
                                          //echo 'val2-'.$val2.'<br>';
                                        $class1     = (trim($val2) == '')?'red':'green';
                                        $class1     = (trim($val1) == '' && trim($val2) == '1')?'green':$class1;
                                        $class1     = (trim($val1) == '1' && trim($val2) == '')?'green':$class1;
                                        $class1     = (trim($val1) == '')?'nochange':$class1;

                                        $class2     = (trim($val1) == '')?'blue':'green';
                                        $class2     = (trim($val1) == '' && trim($val2) == '1')?'green':$class2;
                                        $class2     = (trim($val1) == '1' && trim($val2) == '')?'green':$class2;
                                        $class2     = (trim($val2) == '')?'nochange':$class2;

                                        $this->result1[$cnt]['fields'][$i]['label'] = $text;
                                        $this->result1[$cnt]['fields'][$i]['value'] = $val1;
                                        $this->result1[$cnt]['fields'][$i]['class'] = $class1;
                                        $this->result1[$cnt]['type'] = 'array';

                                        $this->result2[$cnt]['fields'][$i]['label'] = $text;
                                        $this->result2[$cnt]['fields'][$i]['value'] = $val2;
                                        $this->result2[$cnt]['fields'][$i]['class'] = $class2;
                                        $this->result2[$cnt]['type'] = 'array';					

                                        $i++;
                                }
                            }
                      }else{
                            /**********/
                            foreach($arr2 as $eachArrKey => $eachArrVal)
                            {
                                $cnt = count($this->result1);$i = 0;
                                $this->result1[$cnt]['label'] = ucwords($this->key) . ' ' . ($eachArrKey + 1);
                                $this->result2[$cnt]['label'] = ucwords($this->key) . ' ' . ($eachArrKey + 1);
                                foreach($eachArrVal as $eachSubArrKey => $eachSubArrVal)
                                {
                                        $text = ucwords($eachSubArrKey);					
                                        $val2 = $this->filterTextToDisplay($eachSubArrVal);
                                        $val1 = $this->filterTextToDisplay($arr1[$eachArrKey][$eachSubArrKey]);
                                        //echo 'Val1-'.$val1.'<br>';
                                         //echo 'Val2-'.$val2.'<br>';
                                        $class1     = (trim($val2) == '')?'red':'nochange';
                                        $class1     = (trim($val1) == '' && trim($val2) == '1')?'green':$class1;
                                        $class1     = (trim($val1) == '1' && trim($val2) == '')?'green':$class1;
                                        $class1     = (trim($val1) == '')?'nochange':$class1;

                                        $class2     = (trim($val1) == '')?'blue':'green';
                                        $class2     = (trim($val1) == '' && trim($val2) == '1')?'green':$class2;
                                        $class2     = (trim($val1) == '1' && trim($val2) == '')?'green':$class2;
                                        $class2     = (trim($val2) == '')?'nochange':$class2;

                                        $this->result1[$cnt]['fields'][$i]['label'] = $text;
                                        $this->result1[$cnt]['fields'][$i]['value'] = $val1;
                                        $this->result1[$cnt]['fields'][$i]['class'] = $class1;
                                        $this->result1[$cnt]['type'] = 'array';

                                        $this->result2[$cnt]['fields'][$i]['label'] = $text;
                                        $this->result2[$cnt]['fields'][$i]['value'] = $val2;
                                        $this->result2[$cnt]['fields'][$i]['class'] = $class2;
                                        $this->result2[$cnt]['type'] = 'array';					

                                        $i++;
                                }
                            }
                    }
                        
                        
                        
                        
		}
                // print_r($this->result1);
            //print_r($this->result2);
                
	}
        
    public function globalQuestionSearchListQuestionModel(array $input, $entityID, $entityTypeID, $asmtID, $filter, $tag, $taxonomy){
      
        $auth = new Authoring();
        global $DBCONFIG;
        if ($input['hdn_searchcrieteria'] != '')
        {
            $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        }
        else if ($input['par_hdn_searchcrieteria'] != '')
        {
            $input['jsoncrieteria'] = urldecode($input['par_hdn_searchcrieteria']);
        }
        $json = json_decode($input['jsoncrieteria']);
        $search = ($json->keyinfo->title->val != '') ? $json->keyinfo->title->val : $input['search'];
        $searchtype = ($input['hdn_searchcrieteria'] != '' || $input['par_hdn_searchcrieteria'] != '') ? 'advanced' : 'basic';
        $input['ownerName'] = ($input['ownerName'] == '') ? -1 : $input['ownerName'];
        $input['pgndc'] = ($input['pgndc'] == '-1') ? 'qst.Count' : $input['pgndc'] . ',qst.Count';
        $cls = new Classification();
        $tags = ($json->classification->tags->val != '') ? $json->classification->tags->val : $tag;
        $taxo = ($json->classification->taxonomy->id != '') ? $json->classification->taxonomy->id : $taxonomy;
        $owner = ($json->keyinfo->users->id != '') ? ($json->keyinfo->users->id) : $input['ownerName'];
        $key = ($json->metadata->key->id != '') ? ($json->metadata->key->id) : '-1';
        //$value = ($json->metadata->value->id != '') ? ($json->metadata->value->id) : '-1';
        //PED96 : Sprint 2 : QUAD-310 - start
        $value = ($json->metadata->value->name != '') ? ($json->metadata->value->name) : '-1';
        $value_filter = ($json->metadata->value->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchMetadataArr = array('value' => $value, 'value_filter' => $value_filter);

        $value = $this->searchMetadataValue($searchMetadataArr);
        //PED96 : Sprint 2 : QUAD-310 - end
        $difficulty = ($json->keyinfo->difficulty->val != '') ? ($json->keyinfo->difficulty->val) : '-1';
        $startdate = ($json->keyinfo->date->start != '') ? ($json->keyinfo->date->start) : $input['fromsearchdate'];
        $enddate = ($json->keyinfo->date->end != '') ? ($json->keyinfo->date->end) : $input['tosearchdate'];

        //Added condition for similar date issue
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
        
        /*Added for including last updates records in advance search  */
        $startModDate =($input['fromlastdate']!='')? $input['fromlastdate'] : -1;
        $endModDate   =($input['tolastdate']!='')? $input['tolastdate'] : -1;
        $dateModFilter  = $input['lastDate'];
         //Added condition for last updated date
        if ($startModDate != "" || $endModDate != "")
        {
           
            if ($startModDate == $endModDate)
            {
                $newStartModDate = $startModDate;
                $newEndModDate = '';
            }
            else
            {
                 $newStartModDate = $startModDate;
                 $newEndModDate = $endModDate;
            }
        }
        /* End - Last Update*/
        $template = ($json->keyinfo->templates->id != '') ? ($json->keyinfo->templates->id) : $input['ownerName'];
        $question_status = ($json->keyinfo->question_status->id != '') ? ($json->keyinfo->question_status->id) : $input['ownerName'];  //QUAD-86
        //QUAD-85 :: Start
        $searchEntityIDs = ($json->searchInfo->searchResult->id != '') ? $json->searchInfo->searchResult->id : $searchInfoIDs;
        $searchEntityType = ($json->searchInfo->searchResult->EntityTypeID != '') ? $json->searchInfo->searchResult->EntityTypeID : '-1';
        if ($searchEntityType != '-1' && $searchEntityType == 'Assessment')
        {
            $searchEntityTypeID = '2';
        }
        if ($searchEntityType != '-1' && $searchEntityType == 'Bank')
        {
            $searchEntityTypeID = '1';
        }
        //QUAD-85 :: End
        
       /*
        * This is for filter on search result pgae on title coulmn added by Akhlack
        */
        
        if( isset( $input['search'] ) && $input['search'] != '' ){
           $search              = '-1';
           //$filterSearchCond    = rtrim($input['search'], ")");
           //$filterSearchCond    = $filterSearchCond . " AND qst.Title LIKE '%" . $json->keyinfo->title->val . "%' ) ";
           $pFilterNewSearchValue=$input['search'];
		   (($searchtype == 'basic')?($pFilterOldSearchValue=$input['osearch']): ($pFilterOldSearchValue=$json->keyinfo->title->val));
           //$pFilterOldSearchValue=$json->keyinfo->title->val;
        }else{
            $filterSearchCond   = '-1';            
        }
      // echo $filterSearchCond;
       /**************************************************************/
        $search = ($search == '') ? -1 : addslashes(trim($search));
        $search = $this->replaceQuoteForAuthoring(stripslashes($auth->hashCodeToHtmlEntity($search)));
        $owner = ($owner == '') ? -1 : $owner;
        $title_filter = ($json->keyinfo->title->filtertype == 'exclude') ? 'exclude' : 'include';
        $users_filter = ($json->keyinfo->users->filtertype == 'exclude') ? 'exclude' : 'include';
        $templates_filter = ($json->keyinfo->templates->filtertype == 'exclude') ? 'exclude' : 'include';
        $date_filter = ($json->keyinfo->date->filtertype == 'exclude') ? 'exclude' : 'include';
        $difficulty_filter = ($json->keyinfo->difficulty->filtertype == 'exclude') ? 'exclude' : 'include';
        $tags_filter = ($json->classification->tags->filtertype == 'exclude') ? 'exclude' : 'include';
        $taxonomy_filter = ($json->classification->taxonomy->filtertype == 'exclude') ? 'exclude' : 'include';
        $key_filter = ($json->metadata->key->filtertype == 'exclude') ? 'exclude' : 'include';
        $question_status_filter = ($json->keyinfo->question_status->filtertype == 'exclude') ? 'exclude' : 'include';   //QUAD-86
        $searchEntityIDs_filter = ($json->searchInfo->searchResult->filtertype == 'exclude') ? 'exclude' : 'include'; //QUAD-85

      //  $input['pgnob'] = '-1';

        //$input['pgndc']         = "if(qtp.ID IN (37,38) , 'quest-editor-ltd', 'quest-editor' )  as EditPage, mrq.ParentID, qtp.isStatic ,qst.Count,  qst.AuthoringStatus, qst.AuthoringUserID ";
        $input['pgndc'] = "-1";

        // Advanced Search with Program and Product

        $searchProgramIDs = ($json->searchInfo->searchResultProgram->id != '') ? $json->searchInfo->searchResultProgram->id : '-1';
        $searchProgramFilter = ($json->searchInfo->searchResultProgram->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchProductIDs = ($json->searchInfo->searchResultProduct->id != '') ? $json->searchInfo->searchResultProduct->id : '-1';
        $searchProductFilter = ($json->searchInfo->searchResultProduct->filtertype == 'exclude') ? 'exclude' : 'include';

        $searchAsmtIDs = ($json->searchInfo->searchResultAssessment->id != '') ? $json->searchInfo->searchResultAssessment->id : '-1';
        $searchAsmtFilter = ($json->searchInfo->searchResultAssessment->filtertype == 'exclude') ? 'exclude' : 'include';
        $searchBnkIDs = ($json->searchInfo->searchResultBank->id != '') ? $json->searchInfo->searchResultBank->id : '-1';
        $searchBnkFilter = ($json->searchInfo->searchResultBank->filtertype == 'exclude') ? 'exclude' : 'include';


        $bulkEditEntityIds = '-1';
        $bnkQuestionResult = array();
        $asmtQuestionResult = array();
        if ($searchProgramIDs != '-1' || $searchProductIDs != '-1' || $searchAsmtIDs != '-1' || $searchBnkIDs != '-1')
        {
            $searchDataArr = array('searchProgramIDs' => $searchProgramIDs,
                'searchProgramFilter' => $searchProgramFilter,
                'searchProductIDs' => $searchProductIDs,
                'searchProductFilter' => $searchProductFilter,
                'searchAsmtIDs' => $searchAsmtIDs,
                'searchAsmtFilter' => $searchAsmtFilter,
                'searchBnkIDs' => $searchBnkIDs,
                'searchBnkFilter' => $searchBnkFilter,
                'bankEntityTypeId' => '1',
                'asmtEntityTypeId' => '2',
            );
            $progCls = new Program();
            $advSearchQuestIds = $progCls->globalAdvSearchAsmtBnkProdQuestIds($searchDataArr);
            $storeProcedureName='';
            $storeProcedureArray='';
            if (($advSearchQuestIds['bnkQuest_entityTypeId'] == '1' && !empty($advSearchQuestIds['bnkQuestIds'])) || ($advSearchQuestIds['asmtQuest_entityTypeId'] == '2' && !empty($advSearchQuestIds['asmtQuestIds'])))
            {
                if (!empty($advSearchQuestIds['bnkQuestIds']))
                {
                    $advSearchBnkQuestIds = implode(",", $advSearchQuestIds['bnkQuestIds']);
                    $bnkEntityTypeId = 1;
                }
                else
                {
                    $advSearchBnkQuestIds = '-1';
                    $bnkEntityTypeId = '-1';
                }

                if (!empty($advSearchQuestIds['asmtQuestIds']))
                {
                    $advSearchAsmtQuestIds = implode(",", $advSearchQuestIds['asmtQuestIds']);
                    $asmtEntityTypeId = 2;
                }
                else
                {
                    $advSearchAsmtQuestIds = '-1';
                    $asmtEntityTypeId = '-1';
                }
                $storeProcedureName='QuestionSearchListFilter';
                $storeProcedureArray=array($input['pgnob'],
                    $input['pgnot'],
                    $input['pgnstart'],
                    $input['pgnstop'],
                    $search,
                    '-1',
                    $searchEntityTypeID, //this is an entity type id
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
                
                    $questionlist = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);
                /*
                    $questionlist = $this->db->executeStoreProcedure('QuestionSearchListFilter', array($input['pgnob'],
                    $input['pgnot'],
                    $input['pgnstart'],
                    $input['pgnstop'],
                    $search,
                    '-1',
                    $searchEntityTypeID, //this is an entity type id
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
                    $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds,$pFilterNewSearchValue,$pFilterOldSearchValue
                ));
                    */
            }
        }
        else
        {

            $advSearchAsmtQuestIds = '-1';
            $searchAsmtFilter = '-1';
            $asmtEntityTypeId = '-1';
            $advSearchBnkQuestIds = '-1';
            $searchBnkFilter = '-1';
            $bnkEntityTypeId = '-1';
                $storeProcedureName='QuestionSearchListFilter';
                $storeProcedureArray=array($input['pgnob'],
                $input['pgnot'],
                $input['pgnstart'],
                $input['pgnstop'],
                $search,
                '-1',
                $searchEntityTypeID, //this is an entity type id
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
                $questionlist = $this->db->executeStoreProcedure($storeProcedureName,$storeProcedureArray);
                
                /*
                $questionlist = $this->db->executeStoreProcedure('QuestionSearchListFilter', array($input['pgnob'],
                $input['pgnot'],
                $input['pgnstart'],
                $input['pgnstop'],
                $search,
                '-1',
                $searchEntityTypeID, //this is an entity type id
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
                $entityTypeID, $entityID, $advSearchBnkQuestIds, $searchBnkFilter, $bnkEntityTypeId, $advSearchAsmtQuestIds, $searchAsmtFilter, $asmtEntityTypeId, $bulkEditEntityIds,$pFilterNewSearchValue,$pFilterOldSearchValue
            ));
                */
        }

        // End of Advanced Search with Program and Product		
        //$questionlist['TC'] = $this->getValueArray($questionlist['RS'], "@QuestionIDCount");
        //unset($questionlist['RS'][$input['pgnstop']]);

        $qtp = new QuestionTemplate();
        $usr = new User();
        $templateLayouts = $qtp->templateLayout();
        $i = 0;
        if (!empty($questionlist['RS']))
        {
            foreach ($questionlist['RS'] as $question)
            {
                $questionlist['RS'][$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts, $question["QuestionTemplateID"]);
                $questionlist['RS'][$i]["QuesPreviewRight"] = $usr->getMapEntityRightDetails($question['EntityID'],2,$this->session->getValue('userID'));
                $i++;
            }
        }
        /* PEARSON QUAD-170  START */
        //$input['entityid']      = $asmtID;
        $input['entityid'] = $entityID;
        /* PEARSON QUAD-170  END */
        $input['entitytypeid'] = $entityTypeID;
        $input['spcall'] = $questionlist['QR'];
        $input['count'] = $questionlist['TC'];
        $input['storeProcedureArray']= json_encode($storeProcedureArray);
        $input['storeProcedureName']=$storeProcedureName;
        $input['searchtype']=1;
        if (trim($input['hdn_searchcrieteria']) != '')
        {
            $this->saveAdvSearchCrieteria($input);
        }
        return $questionlist;
            
        }
        
     /**
     * Get question Search Name Form maprepositoryquestions
     *
     * @author  Md Akhlack<akhlack.md@learningmate.com>
     * @access  public
     * @param   array      $pEntityID,$pEntityTypeID,$pQuestionID
     * @return  varchar      $question Search Name
     *
     */
    public function getQuestionSearchName($question_details = array ()){
         
        $pEntityID      = $question_details['EntityID'];
        $pEntityTypeID  = $question_details['EntityTypeID'];
        $pQuestionID    = $question_details['QuestionID'];
        $retValue       = $this->db->executeFunction('GetQuestionSearchName', 'SearchNameRet', array($pEntityID,$pEntityTypeID,$pQuestionID));	      
        return $retValue['SearchNameRet'];
         
    }  
    
    /**
     * Get question advancedJson  Form question table For ACE Integration
     *
     * @author  Md Akhlack<akhlack.md@learningmate.com>
     * @access  public
     * @param   array      $questionID
     * @return  varchar      $question Search Name
     *
     */
    
    public function getQuestionAdvJson($questionID){
        if( $questionID != '' ){
           $sql        = 'SELECT ID,advJSONData,Version from Questions WHERE ID IN('.$questionID.') AND isEnabled=1 ORDER BY FIELD(ID,'.$questionID.')';
            $result     = $this->db->getRows( $sql );
            return $result;
        }else{
            return 0;
        }
    }
	
		
    public function getSelectedTaxoID($question_details = array ()){
	
        $pEntityID      = $question_details['EntityID'];
        $pEntityTypeID  = $question_details['EntityTypeID'];
        $pQuestionID    = $question_details['QuestionID'];
		
        if( $pQuestionID != '' ){
           $sql        = "SELECT GROUP_CONCAT(c.ClassificationID) as CID
FROM MapRepositoryQuestions mrq
INNER JOIN Classification c ON ( mrq.ID=c.EntityID AND c.EntityTypeID=3 AND c.ClassificationType = 'Taxonomy' AND c.isEnabled=1)
WHERE  
mrq.EntityID = ".$pEntityID." AND mrq.EntityTypeID=".$pEntityTypeID."  AND mrq.QuestionID=".$pQuestionID." AND mrq.isEnabled = 1 ";
            $result     = $this->db->getSingleRow( $sql );
            return $result;
        }else{
            return array();
        }
    }
	
	
	
	
	
    /**
     * Get question List  Form An Assessment For Preview 
     *
     * @author  Md Akhlack<akhlack.md@learningmate.com>
     * @access  public
     * @param   array      $assessmentID
     * @return  varchar      $questionID
     *
     */
    
    public function getQuestionListOfAnAssessmentForPreview($assessmentID){
        //$previewLimit = $this->registry->site->cfg->bulkQuestionPreviewLimit;
        $previewLimit = $this->cfgApp->bulkQuestionPreviewLimit;
        if( $assessmentID != '' ){
            $pvSet      = $this->previewSettingsAssesment( $assessmentID );    
            if($pvSet['mode'] == 2){
                
               // $orderBy    = ($pvSet['limit'] > 0 ? "ORDER BY RAND() LIMIT ".$pvSet['limit'] : "ORDER BY RAND() LIMIT 100"); 
                if($pvSet['limit'] > 0 && $pvSet['limit'] > $previewLimit ){
                   $orderBy    =  "ORDER BY RAND() LIMIT  ".$previewLimit ;  
                }else if($pvSet['limit'] > 0 && $pvSet['limit'] <= $previewLimit ){
                    $orderBy    =  "ORDER BY RAND() LIMIT ".$pvSet['limit'] ;  
                }else{
                    $orderBy    = "ORDER BY RAND() LIMIT ".$previewLimit;
                }
                
            }else{
                $orderBy    = "ORDER BY Sequence ASC LIMIT  ".$previewLimit; 
            }
            
            $sql        = 'SELECT ID,QuestionID,SearchName from MapRepositoryQuestions where EntityID = "'.$assessmentID.'" AND  EntityTypeID=2 AND QuestionID > 0 AND isEnabled = 1 '.$orderBy;
            $result     = $this->db->getRows( $sql );
            return $result;
        }else{
            return 0;
        }
    }
    
    /*
     * Description: Get the settings of Assessment 
     * Author: Akhlack
     * Created: 22th Sept. 2015
     */
    public function previewSettingsAssesment($assessmentID){
        if( $assessmentID != '' ){
            
            $respone            = array();
            $respone['limit']   = 0;
            $respone['mode']    = 1;
            $sql                = 'SELECT mode from Assessments where ID = "'.$assessmentID.'" ';
            $result             = $this->db->getRows( $sql );
            
            if( $result[0]['mode'] == '2' ){
                // this query received from AssessmentDetails->DeliverySettings SP
                $reSql          = 'SELECT dset.SettingName, mds.SettingValue, concat("Setting_",mds.DeliverySettingID ) as SettingID FROM MapDeliverySettings mds LEFT JOIN DeliverySettings dset ON dset.ID = mds.DeliverySettingID AND dset.isEnabled = "1" WHERE mds.EntityID = "'.$assessmentID.'" AND  dset.SettingName="RandomizeQuestion" AND mds.isEnabled = "1" '; 
                $nwresult       = $this->db->getRows( $reSql );
                $nwresult       = $nwresult[0];
                $respone['mode']   = 2;
                //NoofRandomQuest
                if( $nwresult['SettingValue'] > 1 ){
                    $respone['limit']  = $nwresult['SettingValue'];
                }
            }
            
            return $respone;
        }
    }
    

	public function getQuestionId( $repoId ){
		 if( $repoId != '' ){
            $sql        = 'SELECT QuestionID from MapRepositoryQuestions WHERE SearchName = "'.$repoId.'" AND isEnabled=1';
			$result     = $this->db->getRows( $sql );
            return $result;
        }else{
            return 0;
        }
	
	}
        
        
     /* Question Update To latest*/
    public function questionUpdateToLatest( $input ){
        $questionID     = explode('||',$input['questionID']); 
        /*======= Asset Count Set Minus For Inactive Question =====*/
        if( $questionID != ""){ 
            foreach( $questionID as $k=>$val ){
                //$query  = "SELECT MAX(ID) as LatestQuestionID FROM Questions WHERE RefID  = ( SELECT RefID FROM Questions WHERE ID = '".$val."') ";
                $query = "SELECT ID FROM MapRepositoryQuestions WHERE QuestionID = (SELECT MAX(ID) as LatestQuestionID FROM Questions  WHERE RefID  = ( SELECT RefID FROM Questions WHERE ID = '".$val."')) AND isEnabled=1 AND EntityID = '".$input['EntityID']."' AND EntityTypeID = '".$input['EntityTypeID']."'";                
                $res    = $this->db->execute( $query );
                if( $res[0]['ID'] != '' ){
                    $query = "UPDATE MapQuestionContent set isEnabled = '0' WHERE QuestionID  IN (".$val.") ";        
                    $this->db->execute($query);
                }
            }
            //--- After UPDATE MapQuestionContent AssetUsage Count Will update by automatically by TRIGGER;
        }
        
        /*=========================================================*/
        
        
        $questionID      = implode(",",$questionID);
        $assessmentID   = $input['EntityID'];
        $entityTypeID   = $input['EntityTypeID'];
        
      
      
        $ret            = $this->db->executeStoreProcedure('QuestionUpdateToLatest', array($questionID, $assessmentID,$entityTypeID)); 
       
        return 1;
        /*if( $assessmentID != '' ){
            foreach( $questionID as $k => $val ){
                if( $val != "" ){
                    //$sql        = "UPDATE MapRepositoryQuestions  SET QuestionID = ( SELECT MAX(ID) FROM Questions WHERE RefID  = ( SELECT RefID FROM Questions WHERE ID ='".$val."')  )  WHERE EntityID = '".$assessmentID."' AND EntityTypeID = '".$entityTypeID."' AND  QuestionID = '".$val."'";                
                    $getSql     = "SELECT MAX(ID) as ID FROM Questions WHERE RefID  = ( SELECT RefID FROM Questions WHERE ID ='".$val."')  "; 
                    $result     = $this->db->getRows( $getSql );
                    if($val != $result[0]['ID']){
                        $sql        = "UPDATE MapRepositoryQuestions  SET QuestionID = '".$result[0]['ID']."'  WHERE EntityID = '".$assessmentID."' AND EntityTypeID = '".$entityTypeID."' AND  QuestionID = '".$val."'";                
                        $result     = $this->db->execute( $sql );    
                    }
                }
            }
            return $result;
        }else{
            return 0;
        }*/
        
        
            
    }
    
    /**** Check the update version indicator according to the question *****/
    
    public function getLatestVersionIndicator($qid, $EntityTypeID, $asmtID) {
        $query = "SELECT ID AS LQID FROM Questions WHERE RefID  = ( SELECT RefID FROM Questions WHERE ID = {$qid}) ORDER BY Version DESC LIMIT 1";
        Site::myDebug("Latest update query");
        Site::myDebug($query);
        $res = $this->db->getSingleRow($query);
        if ( $res['LQID'] == $qid )
            return 1;
        else 
            return 0;
    }
    
    
    /**
     * Get question List  Form An Bank For Preview 
     *
     * @author  Md Akhlack<akhlack.md@learningmate.com>
     * @access  public
     * @param   array      $bankID
     * @return  varchar      $questionID
     *
     */
    
    public function getQuestionListOfBankForPreview($bankID){
        if( $bankID != '' ){            
            //$sql        = 'SELECT ID,QuestionID,SearchName from MapRepositoryQuestions where EntityID = "'.$bankID.'" AND  EntityTypeID=1  AND QuestionID > 0 AND isEnabled = 1 ORDER BY Sequence ASC LIMIT '.$this->registry->site->cfg->bulkQuestionPreviewLimit;
            $sql        = 'SELECT QuestionID from MapRepositoryQuestions where EntityID = "'.$bankID.'" AND  EntityTypeID=1  AND QuestionID > 0 AND isEnabled = 1 ORDER BY Sequence ASC LIMIT '.$this->cfgApp->bulkQuestionPreviewLimit;
            $result     = $this->db->getRows( $sql );
            return $result;
        }else{
            return 0;
        }
    }
        
}

?>