<?php
/**
 * This class handles all classification(tag/taxonomy) module related queries/requests.
 * This class handles the business logic of listing/add/edit/delete/search and other requests of tag and taxonomies.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */

class Classification extends Site
{
    public $taxStr;
    /**
    * constructs a new classification instance 
    */
    function __construct()
    {
        parent::Site();
        $this->taxStr   = '';
    }

    /**
    * gets the Tag list 
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input   
    * @return   array
    *
    */
    function tagList(array $input,$condition)
    {       
       global $DBCONFIG;
       Site::myDebug('------tagListModel');
        Site::myDebug($input);
        
        $input['filter']    = ($condition != '')?$condition:'-1';
        $input['pgnob']     = ($input['pgnob'] != '')?$input['pgnob']:"ModDate";
        $input['pgnot']     = ($input['pgnot'] != '')?$input['pgnot']:"DESC";
        $input['pgnstart']  = ($input['pgnstart'] != '')?$input['pgnstart']: '0';
        $input['pgnstop']   = ($input['pgnstop'] != '')?$input['pgnstop']:'-1';
                    
        
        if($this->getSettingVal('ShowPubDat') == 'Y')
        {
            $tagList = $this->db->executeStoreProcedure('TagsList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'),$this->session->getValue('userID') , $this->displayColumn,$input['filter']) );
        }
        else
        {            
            $tagList = $this->db->executeStoreProcedure('TagsList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'),$this->session->getValue('userID') , $this->displayColumn,$input['filter']) ); 

            /* Code Commeneted as we are not using DefaultClientID() function
            $selectedtag        = $input['selectedtag'];
            $input['filter']    = ($input['filter'] != '-1')?" AND ".$input['filter']:'';
            $orderBy            = ($input['pgnob'] != '-1')?$input['pgnob']:'t.ModDate';
            $orderType          = ($input['pgnot'] != '-1')?$input['pgnot']:'DESC';
            $pgnstart           = ($input['pgnstart'] >= 0 ) ? $input['pgnstart'] : "0" ;
            $condition          = (!isset($selectedtag))? " GROUP BY t.ID ORDER BY {$orderBy} {$orderType} LIMIT {$pgnstart},{$input['pgnstop']}" : "";
            $query              = " SELECT  SQL_CALC_FOUND_ROWS t.ID,t.Tag,t.AccessMode,t.Count,t.UserID,t.ModDate,mcu.ClientID FROM Tags t, MapClientUser mcu
                                    WHERE t.UserID = mcu.UserID AND mcu.clientID = {$this->session->getValue('instID')} AND mcu.isEnabled = '1' AND t.isEnabled = '1' AND
                                    IF(mcu.clientID = DefaultClientID(),IF(t.UserID != {$this->session->getValue('userID')},t.AccessMode != 'Private','1=1' ),'1=1' ) {$input['filter']} $condition ";
            $tagList['RS']      = $this->db->getRows($query);
            $tagList['TC']      = $this->rowsCount();
             * 
             */
            
        }
     
        return $tagList;
    }

    /**
    * gets the Tag list with search criteria 
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array
    *
    */
    function tagSearchList(array $input)
    {
           
        global $DBCONFIG;
        $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
        $json                   = json_decode(stripslashes($input['jsoncrieteria']));
        Site::myDebug('-------tagSearchList');
        Site::myDebug($json);
        $search                 = ($json->keyinfo->title->val != '')?$json->keyinfo->title->val: $input['search'];
        $searchtype             = ($input['hdn_searchcrieteria'] != '')?'advanced':'basic';
        $input['ownerName']     = ($input['ownerName']== '')? -1 : $input['ownerName'];
        $input['pgndc']         = ($input['pgndc'] == '-1')?'qst."Count"':$input['pgndc'].',qst."Count"';

        $owner      = ($json->keyinfo->users->id != '')?($json->keyinfo->users->id):$input['ownerName'];        
        $startdate  = ($json->keyinfo->date->start != '')?($json->keyinfo->date->start):'-1';
        $enddate    = ($json->keyinfo->date->end != '')?($json->keyinfo->date->end):'-1';        
        $mincount   = ($json->keyinfo->usagecount->minusagecount != '')?($json->keyinfo->usagecount->minusagecount ):'0';
        $maxcount   = ($json->keyinfo->usagecount->maxusagecount != '')?($json->keyinfo->usagecount->maxusagecount):'-1';
        
        $search = ($search== '') ? -1 : $search;
        $owner  = ($owner== '')  ? -1 : $owner;


        $title_filter       = ($json->keyinfo->title->filtertype == 'exclude')?'exclude': 'include';
        $users_filter       = ($json->keyinfo->users->filtertype == 'exclude')?'exclude': 'include';
        $usagecount_filter  = ($json->keyinfo->usagecount->filtertype == 'exclude')?'exclude': 'include';
        $date_filter        = ($json->keyinfo->date->filtertype == 'exclude')?'exclude': 'include';

$searchCond = ($DBCONFIG->dbType == 'Oracle' ) ?  ' 1 = 1 '  : ' 1 ';
        
        if( $search != "-1") 
        {        
            $searchArr  = explode(' ',$search);
            if(count($searchArr) > 1)
            {
                $search = ($DBCONFIG->dbType == 'Oracle' ) ? implode("'',''",(array)$searchArr) : implode("','",(array)$searchArr);
                $search = ($DBCONFIG->dbType == 'Oracle' ) ? "''$search''" : "'$search'";
                if($title_filter == 'exclude')
                {
                    $searchCond  .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Tag\" NOT IN ($search)":" AND t.Tag NOT IN ($search)  ";
                }
                else
                {
                    $searchCond  .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Tag\" IN ($search)  ":" AND t.Tag IN ($search)  ";
                }
            }
            else
            {
                if($title_filter == 'exclude')
                {
                    $searchCond  .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Tag\" NOT LIKE ''%{$search}%''  " : " AND t.Tag NOT LIKE '%{$search}%'  ";
                }
                else
                {
                    $searchCond  .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Tag\" LIKE ''%{$search}%''  " : " AND t.Tag LIKE '%{$search}%'  ";
                }
            }
        }
        
        if( $mincount >= 0 &&  $maxcount >= 0 )
        {
            if($usagecount_filter == 'exclude')
            {
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND ( t.\"Count\" < {$mincount} " : " AND ( t.Count < {$mincount} ";
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " OR t.\"Count\" > {$maxcount} ) " : " OR t.Count > {$maxcount} ) ";
            }
            else
            {
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Count\" >= {$mincount} " : " AND t.Count >= {$mincount} ";
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"Count\" <= {$maxcount} " : " AND t.Count <= {$maxcount} ";
            }
        }

        if ( $owner != "-1" )
        {
            if($users_filter == 'exclude')
            {
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"UserID\" NOT IN ($owner)  " : " AND t.UserID NOT IN ($owner)  ";
            }
            else
            {
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND t.\"UserID\" IN ($owner)  " : " AND t.UserID IN ($owner)  ";
            }
        }

        if ( $startdate != "-1" )
        {
            if($date_filter == 'exclude')
            {
                // $searchCond .= " AND ( date_format(t.ModDate,'%m-%d-%Y' ) < '{$startdate}'   ";
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND  t.\"ModDate\"  < ''{$this->getFormatDate($startdate)}''   " : " AND  t.ModDate  < '{$this->getFormatDate($startdate)}'   ";
            }
            else
            {
                 $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND  t.\"ModDate\"  >= ''{$this->getFormatDate($startdate)}''   " : " AND  t.ModDate  >= '{$this->getFormatDate($startdate)}'   ";
                // $searchCond .= " AND date_format(t.ModDate,'%m-%d-%Y' ) >= '{$startdate}'   ";
            }
        }

        if ( $enddate != "-1" )
        {
            if($date_filter == 'exclude')
            {
                //$searchCond .= " OR date_format(t.ModDate,'%m-%d-%Y' ) > '{$enddate}'  )   ";
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND  t.\"ModDate\"  > ''{$this->getFormatDate($enddate)}''   " : " AND  t.ModDate  > '{$this->getFormatDate($enddate)}'   ";
            }
            else
            {
               // $searchCond .= " AND date_format(t.ModDate,'%m-%d-%Y' ) <= '{$enddate}'     ";
                $searchCond .= ($DBCONFIG->dbType == 'Oracle' ) ? " AND  t.\"ModDate\"  <= ''{$this->getFormatDate($enddate)}''   " : " AND  t.ModDate  <= '{$this->getFormatDate($enddate)}'   ";
            }
        }

        if($DBCONFIG->dbType == 'Oracle' && !$searchCond)
        {
            $searchCond = '-1';
        }

        
        if($this->getSettingVal('ShowPubDat') == 'Y')
        {
            $tagList    = $this->db->executeStoreProcedure('TagsList',
                                    array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],
                                          $this->session->getValue('instID'),$this->session->getValue('userID') ,
                                          $this->displayColumn,$searchCond) );
        }
        else
        {
            $orderBy    = ($input['pgnob'] != '-1')?$input['pgnob']: ($DBCONFIG->dbType == 'Oracle' ) ? 't."ModDate"' : 't.ModDate';
            $orderType  = ($input['pgnot'] != '-1')?$input['pgnot']:'DESC';
            
            
            $tagList    = $this->db->executeStoreProcedure('TagsList',
                                    array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],
                                          $this->session->getValue('instID'),$this->session->getValue('userID') ,
                                          $this->displayColumn,$searchCond) );

                    
            /*
            $query      = "SELECT  SQL_CALC_FOUND_ROWS t.ID,t.Tag, t.AccessMode, t.Count, t.UserID,t.ModDate, mcu.ClientID
                            FROM Tags t, MapClientUser mcu
                            WHERE {$searchCond}
                            AND t.UserID = mcu.UserID AND mcu.clientID = {$this->session->getValue('instID')}
                            AND mcu.isEnabled = '1' AND t.isEnabled = '1'
                            AND IF(mcu.clientID = DefaultClientID(), IF(t.UserID != {$this->session->getValue('userID')},t.AccessMode != 'Private','1=1' ) ,'1=1' )
                            GROUP BY t.ID
                            ORDER BY {$orderBy} {$orderType}
                            LIMIT {$input['pgnstart']},{$input['pgnstop']} ";
             $tagList['RS'] = $this->db->getRows($query);
            $tagList['TC'] = $this->rowsCount();            
             * 
             */
            // Site::myDebug($query);
            
            
        }
        if ( isset($input["searchstart"]) && $input["searchstart"] == 1)  // STore Search Criteria
        {
            Site::myDebug("----------STore Search Criteria");
            Site::myDebug(htmlentities($tagList['QR'],ENT_QUOTES));
            Site::myDebug(html_entity_decode("'".$tagList['QR']."'",ENT_QUOTES));
            $input['entityid']      = '0';
            $input['entitytypeid']  = 11;
            $input['spcall']        = $tagList['QR'];
            $input['count']         = $tagList['TC'];
           
            if(trim($input['hdn_searchcrieteria']) != '')
            {
                $this->saveAdvSearchCrieteria($input);
            }
        }
        return $tagList;
    }

    /**
    * get Min and Max Count of Tags For the Logged in Institute    
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    
    * @return   array
    *
    */
    public function tagMinAndMaxCount()
    {        
      global $DBCONFIG;
        /*  $query    = "SELECT MIN(Count) as mincount, MAX(Count) as maxcount
                    FROM Tags t,  MapClientUser mcu
                    WHERE t.UserID = mcu.UserID
                    AND mcu.clientID = {$this->session->getValue('instID')} ";*/
        $arrCount = $this->db->executeStoreProcedure('TAGMINMAXCOUNT', array($this->session->getValue('instID')));
        return $arrCount;
    }

    /**
    * get Min and Max Count of Metadata For the Logged in Institute
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   array
    *
    */
    public function metadataMinAndMaxCount()
    {
       global $DBCONFIG;
        /* $query    = "SELECT MIN(UseCount) as mincount, MAX(UseCount) as maxcount
                    FROM MetaDataKeys m,  MapClientUser mcu
                    WHERE m.UserID = mcu.UserID
                    AND mcu.clientID = {$this->session->getValue('instID')} ";*/
        $arrCount = $this->db->executeStoreProcedure('METADATAMINMAXCOUNT', array($this->session->getValue('instID')));
        return $arrCount;
    }
    

    /**
    * gets the classification assigned to Entity(Bank ,Assesment ,Question,Media)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $entityID 
    * @param    integer $entityTypeID
    * @param    string $flag    
    * @return   array
    *
    */
    public function getClassification($entityID,$entityTypeID,$flag='Single')
    {        
       global $DBCONFIG;
        $procedure  = ($flag == "Single")?'ClassificationAssignedList':'RepositoryClassfication';
        $clsfcnList = $this->db->executeStoreProcedure($procedure, array($entityID,$entityTypeID),'details');
        return $clsfcnList;
    }

     /**
    * gets the classification assigned to Entity(Bank ,Assesment ,Question,Media)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array
    *  @deprecated
    */
    public function getRepositoryTag(array $input)
    {
       global $DBCONFIG;
        $repositoryID   = $input['RepositoryID'];
        $entityType     = $input['EntityType'];
        $clsfcnList     = $this->db->executeStoreProcedure('ClassificationAssignedList', array($repositoryID,$entityType),'nocount');

        if(!empty($clsfcnList)):
            $clsfcnList = $clsfcnList[0];
            $tagList    = $clsfcnList['Tag'];
            $taxList    = $clsfcnList['Taxonomy'];
        else:
            $tagList    = '';
        endif;

        $result['tagID']    = 0;
        $result['tagName']  = $tagList;
        $result['page']     = 1;
        $result['fields']   = array(
                                'EntityTypeID'      => "{$entityType}",
                                'EntityID'          => "{$repositoryID}",
                                'taxonomyNodeIds'   => $taxList
                                );
        return $result;
    }

    /**
    * gets the TaxonomyList
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global 
    * @param    array   $input
    * @return   array
    *
    */
    function taxonomyList(array $input)
    {
       global $DBCONFIG;
       
        $input['filter']    = ($input['filter'] != '')?$input['filter']:'-1';
        $input['parentID']  = ($input['parentID'] != '')?$input['parentID']:1;
        $taxonomyList       = array();
        /*
         * This code will be enabled in feature.
         * if($this->getSettingVal('ShowPubDat') == 'Y')
        {
            $taxonomyList = $this->db->executeStoreProcedure('TaxonomyList', array($input['parentID'],$this->session->getValue('userID') ,$this->session->getValue('instID'),$input['filter']));
        }
        else
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS txn.ID, txn.Taxonomy, txn.ParentID FROM Taxonomies txn WHERE txn.ParentID =1 AND txn.isEnabled = '1' AND txn.UserID = {$this->session->getValue('userID')}";
            $taxonomyList['RS'] = $this->db->getRows($query);
            $taxonomyList['TC'] = $this->rowsCount();
        }*/
        $taxonomyList       = $this->db->executeStoreProcedure('TaxonomyList', array($input['parentID'],$this->session->getValue('userID') ,$this->session->getValue('instID'),$input['filter'],'-1','-1'));
        return $taxonomyList;
    }

    /**
    * Function used to delete tags
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer  $tagID
    * @return   boolean
    *
    */
    function deleteTag($tagID)
    {
       global $DBCONFIG;
        $tagID  = implode(',',(array)$this->removeBlankElements($tagID));
        $query  = ($DBCONFIG->dbType == 'Oracle' ) ? "  UPDATE Tags SET \"isEnabled\" = 0 WHERE \"ID\" IN ($tagID) " : "  UPDATE Tags SET isEnabled = '0' WHERE ID IN ($tagID) ";
        $status = $this->db->execute($query);        
       
        $tagID  = explode(',',$tagID);
        
        if($status)
        {
            if(!empty($tagID))
            {
                $data   = array();
                foreach($tagID as $id)
                {
                    if($id > 0)
                    {
                        $tagInfo = $this->getTagInfo($id);
                        Site::myDebug('---------$arrTagInfo');
                        Site::myDebug($arrTagInfo);
                        if ( $tagInfo )
                        {
                            if($DBCONFIG->dbType == 'Oracle')
                            {
                                $data[] = array(
                                                "UserID"        => $this->session->getValue('userID'),
                                                "EntityTypeID"  => 11,
                                                "EntityID"      => $id,
                                                "EntityName"    => $tagInfo['Tag'],
                                                "Action"        => 'Deleted',
                                                "ActionDate"    => $this->currentDate(),
                                                "isEnabled"     => '1',
                                                "AccessLogID"     => $this->session->getValue('accessLogID')
                                            );
                            }
                            else
                            {
                                $data[] = array(
                                            'UserID'        => $this->session->getValue('userID'),
                                            'EntityTypeID'  => 11,
                                            'EntityID'      => $id,
                                            'EntityName'    => $tagInfo['Tag'],
                                            'Action'        => 'Deleted',
                                            'ActionDate'    => $this->currentDate(),
                                            'isEnabled'     => '1',
                                            'AccessLogID'     => $this->session->getValue('accessLogID')
                                        );
                            }
                        }
                    }
                }                
                $this->db->multipleInsert('ActivityTrack',$data);
            }
        }
       // return false;
        return $status;
    }

    /**
    * Function used to delete Taxonomies
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input   
    * @return   boolean
    *
    */
    function deleteTaxonomy(array $input)
    {
       global $DBCONFIG;
        $taxonomyID = implode(',',(array)$this->removeBlankElements($input['taxonomyID']));
        $taxonomyID = (empty($taxonomyID))?$input['deleteIds']:$taxonomyID;
        $query      = ($DBCONFIG->dbType == 'Oracle') ? " UPDATE Taxonomies SET \"isEnabled\" = '0' WHERE \"ID\" IN ($taxonomyID) " : " UPDATE Taxonomies SET isEnabled = '0' WHERE ID IN ($taxonomyID) ";
        $status     = $this->db->execute($query);
        $tagID      = explode(',',$taxonomyID);

        if($status)
        {
            if(!empty($tagID))
            {
                $data   = array();
                foreach($tagID as $id)
                {
                    if($id > 0)
                    {
                        if($DBCONFIG->dbType == 'Oracle')
                        {
                            $data[] = array(
                                            "UserID"        => $this->session->getValue('userID'),
                                            "EntityTypeID"  => 12,
                                            "EntityID"      => $id,
                                            "EntityName"    => $this->getTaxonomyInfo($id,'object')->Taxonomy,
                                            "Action"        => 'Deleted',
                                            "ActionDate"    => $this->currentDate(),
                                            "isEnabled"     => '1',
                                            "AccessLogID"     => $this->session->getValue('accessLogID')
                                        );
                        }
                        else
                        {
                            $data[] = array(
                                            'UserID'        => $this->session->getValue('userID'),
                                            'EntityTypeID'  => 12,
                                            'EntityID'      => $id,
                                            'EntityName'    => $this->getTaxonomyInfo($id,'object')->Taxonomy,
                                            'Action'        => 'Deleted',
                                            'ActionDate'    => $this->currentDate(),
                                            'isEnabled'     => '1',
                                            'AccessLogID'     => $this->session->getValue('accessLogID')
                                        );
                        }
                    }
                }
                $this->db->multipleInsert('ActivityTrack',$data);
            }
        }
        return $status;
    }
    
    /**
    * Function used to Add and Edit Tag
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $tagID
    * @param    string $tag
    * @param    string $tagMode
    * @return   array
    *
    */
    function manageTag($tagID = '0', $tag = '', $tagMode = 'Private')
    {
        global $DBCONFIG;
        //Added to handle Multiple tags seperated by comma or space.
        //$tag = trim(addslashes($tag));
        $tag = trim($tag);

        $this->myDebug("TagPos--" . strpos($tag, "\"") . "---" . strpos($tag, "\"", 1));
        /*
          if(strpos($tag,"\"") === 0 && strpos($tag,"\"",1) === (strlen($tag) - 1))
          {
          $this->myDebug("1--");
          $tag = trim($tag,"\"");
          $tag = str_replace(",", "", $tag);
          }
          else
          {
          $this->myDebug("2--");
          $tag    = str_replace(" ", ",", $tag);
          $tags   = explode(',', $tag);
          $tag    = $tags[0];
          } */
        
        $tagArray=explode(",", $tag);
        $uniqueTag='';
        $sepraterCount=0;
        $duplicateTagPresent = 0;
        $newTagAdded = 0;
        $tagArray =array_unique($tagArray); // removing duplicate value
        foreach($tagArray as $sepTag){            
             $tCount=$this->tagCount($sepTag);
               $this->myDebug("unique tag--".$sepTag."tag-count".$tCount);
             if($tCount==0)  {    
                 $newTagAdded=1;
                $uniqueTag.=trim($sepTag);
                if($sepraterCount <  count($tagArray)-1){
                    $uniqueTag.=",";
                    }
             }else{
                 $duplicateTagPresent=1;
             }
              $sepraterCount++;
        }
        
         $this->myDebug("unique tag--".$uniqueTag);
        
        if ($this->validateTag($tag) > 0)
        {
            $this->myDebug("validateTag--");
            return false;
        }
        /*Added a condition for single/double qoute which we are supporting now START*/
        /*
        if(strpos($tag, "'") !== false) {
                $newTag = str_replace("'", "\'", $tag);
            } else if (strpos($tag, '"') !== false) {
                $newTag= str_replace('"', '\"', $tag);
            } else {
                $newTag = $tag;
            }
           */ 
            
               if(strpos($uniqueTag, "'") !== false) {
                $newTag = str_replace("'", "\'", $uniqueTag);
            } if (strpos($uniqueTag, '"') !== false) {
                $newTag= str_replace('"', '\"', $uniqueTag);
            } else {
                $newTag = $uniqueTag;
            }
             $this->myDebug("validateTagnewTag-sasasasasasa-".$newTag);
        
            
            
        /*Added a condition for single/double qoute which we are supporting now END*/
        $data = array($tagID, $newTag, $tagMode, $this->session->getValue('userID'), $this->currentDate(), '1', $this->session->getValue('accessLogID'));
        
          $this->myDebug("unique tag-xxxxxxxxxxxxxxxxxxxxx-".$data);
          $this->myDebug($data);
       
       // return $this->db->storeProcedureManage('TagManage', $data);
        $this->db->storeProcedureManage('TagManage', $data);
        $ret = array();
        $ret['insert'] = $newTagAdded;
        $ret['failed'] = $duplicateTagPresent;
        return $ret;
    }

    /**
    * Function used to assign classification to given entity (Assessment,Bank,Question,Media)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $tagID
    * @param    string $tag
    * @param    string $tagMode
    * @return   void
    *
    */

    public function manageClassification($EntityID,$EntityTypeID,$tag = "",$taxonomy = "")
    {
		Site::myDebug( '-----------function manageClassification' );
        Site::myDebug( $tag );
        		Site::myDebug( '-----------function manageClassification' );
        Site::myDebug( $taxonomy );
   
        global $DBCONFIG;
        //$tag = trim($tag);
        
        $this->createTagRunTime($tags); // This will create tag runtime
       
        //if( $tag != '' ){
            $this->db->storeProcedureManage('ClassificationManage', array(
                $tag,
                $this->session->getValue('userID'),
                $EntityID,
                $EntityTypeID,
                'Tag',
                $this->currentDate(),
                $this->session->getValue('userID'),
                $this->currentDate(),
                $this->session->getValue('accessLogID')
            )); 
        //}
        
        //if( $taxonomy != '' ){

            $this->db->storeProcedureManage('ClassificationManage', array(
                $taxonomy,
                $this->session->getValue('userID'),
                $EntityID,
                $EntityTypeID,
                'Taxonomy',
                $this->currentDate(),
                $this->session->getValue('userID'),
                $this->currentDate(),
                $this->session->getValue('accessLogID')
            ));
        //}
        return;
    }
    
    
    
    /**
    * Function used to assign classification to given entity (Bulk) (Assessment,Bank,Question,Media)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $tagID
    * @param    string $tag
    * @param    string $tagMode
    * @return   void
    *
    */

    public function manageClassificationBulk($EntityID,$EntityTypeID,$tag = "",$taxonomy = "")
    {
	//	Site::myDebug( '-----------function manageClassification' );
      //  Site::myDebug( $tag );
    //    Site::myDebug( $taxonomy );
     //   Site::myDebug("akhlack-debug");
        global $DBCONFIG;
        //$tag = trim($tag);
        
        
        $this->createTagRunTime($tags); // This will create tag runtime
        //print_r($tag);die;
           
               Site::myDebug( $tag );
        Site::myDebug("akhlack-debug");
      
        $this->db->storeProcedureManage('ClassificationManageBulk', array(
                $tag,
                $this->session->getValue('userID'),
                $EntityID,
                $EntityTypeID,
                'Tag',
                $this->currentDate(),
                $this->session->getValue('userID'),
                $this->currentDate(),
                $this->session->getValue('accessLogID')
            ));

        $this->db->storeProcedureManage('ClassificationManageBulk', array(
                $taxonomy,
                $this->session->getValue('userID'),
                $EntityID,
                $EntityTypeID,
                'Taxonomy',
                $this->currentDate(),
                $this->session->getValue('userID'),
                $this->currentDate(),
                $this->session->getValue('accessLogID')
            ));

        return;
    }
    
    

    /**
    * check existence of specified tag for loggedin user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string $pTag
    * @return   integer
    *
    */
    
    public function validateTag($pTag)
    {
       global $DBCONFIG;
        $tagName    = (!empty($pTag))?$pTag:'';
		$this->myDebug("###########".$tagName."##############");
        $tag        = $this->db->executeFunction('TagCount', 'cnt', array($tagName,$this->session->getValue('userID')));
        $this->myDebug("*******AAAA**********".$tag['cnt']."*********AAA****");
		return $tag['cnt'];
    }

    /**
    * function used to Add ,Rename,Delete taxonomy
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   integer
    *
    */
    
    function manageTaxonomy(array $input) //act=DELETE&parentID=&taxonomyID=9&taxonomy=&accessMode=Private&belowTaxoID=
    
    { //act=RENAME&parentID=&taxonomyID=152&EntityID=&EntityTypeID=&taxonomy=s1u&accessMode=&belowTaxoID=
        global $DBCONFIG;
        Site::myDebug('----managetaxonomyinput');
        Site::myDebug($input);
        $input['isEnabled'] = "1";
        $input['belowTaxoID']=($input['belowTaxoID']!='')?$input['belowTaxoID']:'-1';
        
        if($input["act"] == "ADD")
        {
            $checkDuplicateTexonomy = $this->db->executeFunction('TaxonomyID', 'tid', array($input['taxonomy'],  $input['taxonomyID']));
            if ( $input['taxonomy'] == "" ) {
                return 'empty';
            }
            else if ($checkDuplicateTexonomy['tid'] != "")
            {
                return 'dupTexonomyName';
            } 
            else 
            {
                $input['taxonomyID']        = 0;
                $search["searchparam"]      = $input['taxonomy'];
                $search["isExactSearch"]    = 1;
                /* Commented for unique check*/
                $result = $this->searchTaxonomy($search);
                if(!empty($result))
                {
                    return $result;
                }
            }
        }
        if ($input["act"] == "RENAME")
        { // put up $checkDuplicateTexonomy function with texonomyID as we need to check if tht root node is exist or not
            
            $checkDuplicateTexonomy = $this->db->executeFunction('TaxonomyID', 'tid', array($input['taxonomy'],  $input['taxonomyID']));
            if ($checkDuplicateTexonomy['tid'] != "")
            {
                return 'dupTexonomyName';
            }
            else
            {

                $search["searchparam"] = rawurldecode($input['taxonomy']);
                $search["isExactSearch"] = 1;
                $search['taxonomyID'] = $input['taxonomyID'];
                /* Commented for unique check
                  $result                     = $this->searchTaxonomy($search);
                  if(!empty($result))
                  {
                  return $result;
                  } */
            }
        }
        if($input["act"] == "DELETE")
        {
            $input['isEnabled'] = '0';
        }
        if($input["act"] == "REORDER")
        {
            //code in feature.
        }
        $result =  $this->db->executeStoreProcedure('TaxonomyManage', array($input['taxonomyID'],$input['taxonomy'],$input['parentID'],$input['belowTaxoID'],$input['accessMode'],$this->session->getValue('userID'),$this->currentDate(),$input['isEnabled'],$this->session->getValue('accessLogID'),$input['Description']),'details');
        return $result["TaxonomyID"];
    }
  
    /**
    * function used to manage tag
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array
    *@deprecated
    */
    
    function manage(array $input)
    {
       global $DBCONFIG;
        $tagID      = $input['tagID'];
        $tagName    = $input['tagName'];
        $tagName    = ($tagName != '')? urldecode($tagName) : '';
        $page       = $input['page'];
        return $this->tagHtml($tagID, $tagName, $page);
    }
    
    /**
    * get html for tag
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $tagID
    * @param    string $tagName
    * @param    integer $page
    * @param    string $fields
    * @return   array
    *@deprecated
    */
    
    function tagHtml($tagID,$tagName,$page,$fields='')
    {
        global $DBCONFIG;
        $retVal  = "";
        $retVal .= "
                    <div id='edittagdiv'>
                    <form action='' method='post' name='frmTagInfo' id='frmTagInfo' >
                    <input type='hidden' name='tagID' id='tagID' value='$tagID'>";
        $retVal .= "<input type='hidden' name='orignalTag' id='orignalTag' value='{$tagName}'>";
        
        if(!empty($fields)):
        foreach($fields as $fld=>$val):
        $retVal .= "<input type='hidden' name='{$fld}' value='{$val}'>";
        endforeach;
        endif;

        $id      = !empty($fields)?'':$page;
        $retVal .= "
                         <input type='hidden' name='accessMode'  value='Private'>
                        <div id='idFrmTags' >
                        <table border='0' cellpadding='0' cellspacing='0' width='100%' style=''>
                        <tr>
                        <td class='clsFrmElementBoxTD' style='vertical-align:top;font-weight:bold;font-family:Arial;font-size:11px;'>
                        Tag
                        <br><INPUT type='text' name= 'tagName' id='tagName' size='44' title='Tag Name' value='{$tagName}'  style=\width: 317px;\" maxlength=\"30\" onKeyDown=\"return checkEnterKeyPress(event,'saveTagInfo(\'$page\')');\">
                        </td>
                        </tr>
                        <tr>
                        <td class='clsFrmElementBoxTD'>
                        <div class='clsFrmElementY'  style='float:right;padding:10px;padding-right:0px;'>";
        
        if(!empty($fields)):
        $retVal .= "<input type='button'  name='btnaddtag'  value='Add Tags' class='clsFrmButtonExp' onclick=\"getCloud('tagName');return false;\">";
        $retVal .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $retVal .= "<input type='button'  name='btnaddtaxonomy'  value='Add Taxonomy' class='clsFrmButtonExpSup' onclick='showTaxonomy();return false;'>";
        $retVal .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $retVal .= "<input type='button'  name='btnsave'  value='Save' class='clsFrmButton' onclick='return saveTag();'>";
        else:
        $retVal .= "<input type='button'  name='btnsave'  value='Save' class='clsFrmButton' onclick='return saveTagInfo($page);'>";
        endif;
        
        $retVal .= "
                        </div></td>
                        </tr></table>
                        </div>
                        </form></div>
                        ";

        return $retVal;
    }

    /**
    * To create tag cloud html
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   string
    *
    */

    public function getCloud(array $input = array())
    {
       global $DBCONFIG;
        $p                          = $input['selectedtag'];
        $clsfctnList                = $this->tagList($input);
        $input['ClsUsedforFlag']    = (!isset($input['ClsUsedforFlag']))? "Question" : $input['ClsUsedforFlag'];
        $taget                      = (empty($input["target"]))?"SelectedTag":$input["target"];

        if(!empty($clsfctnList['RS']))
        {
            foreach($clsfctnList['RS'] as $clsfctn)
            {
                $tags[$clsfctn["Tag"]]= $clsfctn["Count"];
            }

            $maxSize    = 300; // max font size in %
            $minSize    = 100; // min font size in %
            $maxQty     = max(array_values($tags));
            $minQty     = min(array_values($tags));

            $spread = $maxQty - $minQty;
            if (0 == $spread) // we don't want to divide by zero
            {
                $spread = 1;
            }
            $step   = ($maxSize - $minSize)/($spread);
            $p2     = explode(",",$p);
            $i      = 0;
            $j      = 0;
			// echo "<pre>";print_r($tags);
            $tagStr  = "";
			$tagStr .= '<select class="e1" multiple="multiple" id="tags1">';
			foreach($clsfctnList['RS'] as $tagListArr)
            {
				// $tagStr .= '<option value='.$tagListArr["ID"].'>'.$tagListArr["Tag"].'</option>';
                                if($tagListArr["Tag"]!=''){
				$tagStr .= '<option value="'.htmlentities($tagListArr["Tag"]).'">'.$tagListArr["Tag"].'</option>';
                                }
			}	
                        $tagStr .='</select>';
            // $tagStr .= "<div class='tagCloudDiv'><table border='0' width='100%' height='100%'><tr><td valign='middle' align='center'>";
            
            // if(!empty($tags)){
                // foreach ($tags as $key => $value)
                // {
                    // $i++; $j++;
                    // if($i==4)
                    // {
                        // $tagStr .="<br/>"; $i=0;
                    // }
                    // $size   = $minSize + (($value - $minQty) * $step);
                    // $sp     = $key.$j;

                    // $cl = "highlight";
                    // $k  = array_search($key, $p2);
                    // if($k === 0 || $k > 0)
                    // {
                        // $cl = "highlight2";
                    // }
                    // $tagStr .="<span style=\"margin-left:3px;margin-right:3px;\"><span style='font-size:".$size."%!important;' id='".$sp."' class='$cl'><a onClick=\"toggleSelectedTag('$taget','$key','{$input['ClsUsedforFlag']}')\" style='cursor:pointer;'>$key</a></span></span>";
                // }
            // }
            // $tagStr .="</td></tr>
            // </table></div>";

            return $tagStr;
        }
        else
        {
            return '<select class="e1" multiple="multiple" id="tags1"></select>';
          //  return "<span style='font-size:12px;font-weight:bold;'>".NORECORDS."</span>";
            //return 0;

        }
    }

    /**
    * get classification list assiged to entity( bank, assessment, question ,media )
    *
    *
    * @access       private
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        integer $EntityID
    * @param        integer $EntityTypeID
    * @return       array
    *
    */
    function classificationAssignedList($EntityID , $EntityTypeID)
    {
        global $DBCONFIG;
        $ClassificationList = $this->db->executeStoreProcedure('ClassificationAssignedList',  array($EntityID,$EntityTypeID),'nocount');
        $this->myDebug($ClassificationList);
        return array(
            'Tag'       => $this->getValueArray($ClassificationList, "TagFinal"),
            'Taxonomy'  => $this->getValueArray($ClassificationList, "TaxonomyFinal")
        );
    }
    /**
    * Update classification  assiged to entity( bank, assessment, question ,media )
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $EntityID
    * @param    integer $EntityTypeID
    * @return   array
    *
    */
    public function updateClassification($entityID,$entityTypeID,$classifyType='Tag')
    {
        global $DBCONFIG;
        if($DBCONFIG->dbType == 'Oracle')
        {
            $query = "UPDATE Classification SET \"isEnabled\" = '0' WHERE \"ClassificationType\" = '$classifyType' and \"EntityID\" = '$entityID' and \"EntityTypeID\" = '$entityTypeID' ";
        }
        else
        {
            $query = "UPDATE Classification SET isEnabled = '0' WHERE ClassificationType='$classifyType' and EntityID='$entityID' and EntityTypeID='$entityTypeID' ";
        }

        return $this->db->execute($query);
    }
    /**
    * get tag count
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string $tagName
    * @return   integer
    *
    */
    public  function tagCount($tagName)
    {
        global $DBCONFIG;
        $query  = ($DBCONFIG->dbType == 'Oracle') ? "SELECT * FROM Tags WHERE \"Tag\" = '$tagName' " :"SELECT * FROM Tags WHERE Tag='".addslashes($tagName)."' AND isEnabled='1'";
        $this->myDebug("unique tag--tagCount".$query);
        $result = $this->db->getCount($query);
        $this->myDebug("unique tag--tagCount-result".$result);
        $result = (empty($result))?0:$result;        
        return $result;
    }
    /**
    * get tag information (like tag Id,name, accessmode(private,public),adddate ,modification date)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string $tagName
    * @param    string $type
    * @return   array
    *
    */
    public function getTagInfo($tagID,$type='array')
    {
        global $DBCONFIG;
        $query = ($DBCONFIG->dbType == 'Oracle') ? "  SELECT * FROM Tags WHERE \"ID\" = $tagID " : "  SELECT * FROM Tags WHERE ID = $tagID ";
        return $this->db->getSingleRow($query);
    }
    
    /**
    * get taxonomy information (like taxonomy Id,name, accessmode(private,public),adddate ,modification date)
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string $taxonomyID
    * @param    string $type
    * @return   array
    *
    */
    public function getTaxonomyInfo($taxonomyID,$type='array')
    {
       global $DBCONFIG;
        if($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  SELECT t1.\"Taxonomy\",t2.\"Taxonomy\" as parent from Taxonomies t1,Taxonomies t2 WHERE t1.\"ID\" = $taxonomyID and t1.\"ParentID\" = t2.\"ID\" ";
        }
        else
        {
            $query = "  SELECT t1.Taxonomy,t2.Taxonomy as parent from Taxonomies t1,Taxonomies t2 WHERE t1.ID = $taxonomyID and t1.ParentID = t2.ID ";
        }
        return $this->db->getSingleRow($query,$type);
    }
    
    /**
    * function used to disabled tag
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    string $tagID
    * @return   integer
    *
    */
    function disableTag($tagID)
    {
       global $DBCONFIG;
        $query  = ($DBCONFIG->dbType == 'Oracle') ? "UPDATE Tags SET \"isEnabled\" = '0' WHERE \"ID\" = $tagID " : "UPDATE Tags SET isEnabled = '0' WHERE ID=$tagID ";
        $status = $this->db->execute($query);
        $data   = array(0,$this->session->getValue('userID'),11,$tagID,$this->getTagInfo($tagID,'object')->Tag,'Deleted',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage',$data);
        return $status;
    }

    /**
    * function used to enable tag
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    string $tagID
    * @return   integer
    *
    */
    function enableTag($tagID)
    {
        global $DBCONFIG;
        $query  = ($DBCONFIG->dbType == 'Oracle') ? "UPDATE Tags SET \"isEnabled\" = '1' WHERE \"ID\" = $tagID " : "UPDATE Tags SET isEnabled = '1' WHERE ID=$tagID ";
        $status = $this->db->execute($query);
        $data   = array(0,$this->session->getValue('userID'),11,$tagID,$this->getTagInfo($tagID,'object')->Tag,'Edited',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage',$data);
        return $status;
    }

    /**
    * function used to delete unused tag (which is not used in any bank,assesment,question,media)
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    
    * @return   boolean
    *
    */
    function deleteUnusedTag()
    {
        global $DBCONFIG;
        $tagIDs    = $this->getUnusedTags();
        if(!empty($tagIDs))
        {
            $tagIDs         = $this->getValueArray($tagIDs,'ID','multiple');
            $deleteQuery    = ($DBCONFIG->dbType == 'Oracle') ? "  UPDATE Tags SET \"isEnabled\" = '0' WHERE \"ID\" IN ({$tagIDs}) " : "  UPDATE Tags SET isEnabled = '0' WHERE ID IN ({$tagIDs}) ";
            $status         = $this->db->execute($deleteQuery);
            $tagID          = explode(',',$tagIDs);

            if($status)
            {
                if(!empty($tagID))
                {
                    $data   = array();
                    foreach($tagID as $id)
                    {
                        if($id > 0)
                        {
                            if ($DBCONFIG->dbType == 'Oracle')
                            {
                                $data[] = array(
                                                "UserID"        => $this->session->getValue('userID'),
                                                "EntityTypeID"  => 11,
                                                "EntityID"      => $id,
                                                "EntityName"    => $this->getTagInfo($id,'object')->Tag,
                                                "Action"        => 'Deleted',
                                                "ActionDate"    => $this->currentDate(),
                                                "isEnabled"     => '1',
                                        );
                            }
                         else
                         {
                             $data[] = array(
                                                'UserID'        => $this->session->getValue('userID'),
                                                'EntityTypeID'  => 11,
                                                'EntityID'      => $id,
                                                'EntityName'    => $this->getTagInfo($id,'object')->Tag,
                                                'Action'        => 'Deleted',
                                                'ActionDate'    => $this->currentDate(),
                                                'isEnabled'     => '1',
                                        );
                         }

                        
                        }
                    }
                    $this->db->multipleInsert('ActivityTrack',$data);
                }
            }
            return $status;
        }
        else
        {
            return true;
        }
    }

    /**
    * get list of unused tags (which is not used in any bank,assesment,question,media)
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param
    * @return   array
    *
    */
    function getUnusedTags()
    {
        
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query      = "SELECT t.\"ID\" FROM Tags t, MapClientUser mcu WHERE t.\"UserID\" = mcu.\"UserID\"
                            AND mcu.\"ClientID\" = {$this->session->getValue('instID')} AND mcu.\"isEnabled\" = '1' AND t.\"isEnabled\" = '1' AND
                            t.\"Count\" = 0 ";
        }
        else
        {
            $query      = "SELECT t.ID FROM Tags t, MapClientUser mcu WHERE t.UserID = mcu.UserID
                            AND mcu.ClientID = {$this->session->getValue('instID')} AND mcu.isEnabled = '1' AND t.isEnabled = '1' AND
                            t.Count = 0 ";
        }
       
        $tagIDs     = $this->db->getRows($query);
        
        return $tagIDs;
    }

    /**
    * get list of taxonomies for entity
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @return   array
    *
    */
    
    
    function getTax($parentID,$EntityTypeID = "-1")
    {
        global $DBCONFIG;        
        //$this->myDebug("GetTax--".$EntityTypeID);
        $taxList = $this->db->executeStoreProcedure('TaxonomyList',array($parentID,$this->session->getValue('userID'),$this->session->		
		getValue('instID'),$EntityTypeID,"-1",'-1'));
		//$this->myDebug("Taxlist--".$taxList);
        return $taxList["RS"];
    }
    
    
    
    /**
    * return hierarchically structure of taxonomy for entity(bank,assessment,question,media)
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @param    string $ClsUsedforFlag
    * @return   array (json format)
    *
    */
    function taxonomyTreeAddNode($id, $text) {
        global $DBCONFIG;
       // echo $id;
//        $checkDuplicateTexonomy = $this->db->executeFunction('TaxonomyID', 'tid', array($text,  $id));
//        if ($checkDuplicateTexonomy['tid'] != "")
//        {
//            return 'dupTexonomyName';
//        } else {
//            $queryForLeaf = "SELECT ParentID AS pid, sequence AS seq FROM Taxonomies WHERE isEnabled=1 AND ID=" . $id;
//            $queryResLeaf = $this->db->getSingleRow($queryForLeaf);
//            //echo $queryResLeaf['pid'];die;
//            $parentID = (int)$queryResLeaf['pid'];
//            $seq = (int)$queryResLeaf['seq'];
//
//            $taxValues = array('Taxonomy' => trim($text),
//                'ParentID' => $parentID,
//                'Sequence' => (int)$seq + 1,
//                            'Description'=> $taxonomyDesc,
//                'AccessMode' => 'Private',
//                'Count' => 0,
//                'UserID' => $this->session->getValue('userID'),
//                'AddDate' => $this->currentDate(),
//                'ModBY' => $this->session->getValue('userID'),
//                'ModDate' => $this->currentDate(),
//                'isEnabled' => '1'
//            );
//            $taxoID = $this->db->insert("Taxonomies", $taxValues);
//            return $taxoID;
//        }
        
        $queryForLeaf = "SELECT ParentID AS pid, sequence AS seq FROM Taxonomies WHERE isEnabled=1 AND ID=" . $id;
            $queryResLeaf = $this->db->getSingleRow($queryForLeaf);
            //echo $queryResLeaf['pid'];die;
            $parentID = (int)$queryResLeaf['pid'];
            $seq = (int)$queryResLeaf['seq'];

            $taxValues = array('Taxonomy' => trim($text),
                'ParentID' => $parentID,
                'Sequence' => (int)$seq + 1,
                'Description'=> $taxonomyDesc,
                'AccessMode' => 'Private',
                'Count' => 0,
                'UserID' => $this->session->getValue('userID'),
                'AddDate' => $this->currentDate(),
                'ModBY' => $this->session->getValue('userID'),
                'ModDate' => $this->currentDate(),
                'isEnabled' => '0',
                'guid' => $this->registry->site->UUIDv4()
            );
            $taxoID = $this->db->insert("Taxonomies", $taxValues);
            return $taxoID;
        
    }
    
    
    /**
    * Rename tree Node of the Taxonomy Tree
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @param    string $ClsUsedforFlag
    * @return   array (json format)
    * UpdateCode for taxonomy
    */
    function taxonomyTreeRenameNode($id,$parent,$text) {
        global $DBCONFIG;
        
        /* Already added taxnomoy updated check */
        $queryForLeaf       = "SELECT Taxonomy FROM Taxonomies WHERE  isEnabled = '1' AND ID = '".$id."'"  ;
        $queryResLeafResult = $this->db->getSingleRow($queryForLeaf);
        
        if( $queryResLeafResult['Taxonomy'] == trim( $text) ){
          return 1;  
        }
        /*#################################*/
        
       $checkDuplicateTexonomy = $this->db->executeFunction('TaxonomyID', 'tid', array($text,  $parent));
        if ($checkDuplicateTexonomy['tid'] != "" )//&& $checkDuplicateTexonomy['tid'] != $id
        {
            //$this->taxonomyTreeDeleteNode($id);
            return 'dupTexonomyName';
        } 
        else 
        {
            if ( $parent == '#' )   
               $parent = 1;
            $updated_data   =   array(
                                     'Taxonomy'=> $text,
                                     'ParentID'=>$parent,
                                        'isEnabled'=>'1',
                                     );
            $where                 =   " ID =".$id;
            $status= $this->db->update("Taxonomies",$updated_data, $where );
            return $status;
        }
        
    }
    
    
    
    /**
    * Delete tree Node of the Taxonomy Tree
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @param    string $ClsUsedforFlag
    * @return   array (json format)
    *
    */
    function taxonomyTreeDeleteNode($id, $parent, $position) {
        global $DBCONFIG;
        $query = "UPDATE Taxonomies SET isEnabled=0 WHERE isEnabled=1 AND ID IN(" . $id . ")";
        
        $status = $this->db->execute($query);
        
        //$querySelect = "SELECT ID FROM Taxonomies WHERE isEnabled=0 AND parentID=" . $id;
        //$res = $this->db->execute($query);
        
        return $status;
    }
    
    
    /**
    * Move tree Nodes of the Taxonomy Tree
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @param    string $ClsUsedforFlag
    * @return   array (json format)
    *
    */
    function taxonomyTreeMoveNode($id, $parent) {
        global $DBCONFIG;
        $parent = ( $parent == '#'?1: $parent );
        $checkMove = "SELECT ID FROM Taxonomies WHERE isEnabled = '1' AND ParentID = ".$parent." AND Taxonomy = ( SELECT Taxonomy FROM Taxonomies WHERE  isEnabled = '1' AND ID = '".$id."') ;";
         $MoveStatus = $this->db->getSingleRow($checkMove);
         $IDPresent = $MoveStatus['ID'];
         if( $MoveStatus['ID'] == "" ){
            $query = "UPDATE Taxonomies SET ParentID=" . $parent . " WHERE isEnabled=1 AND ID=" . $id;        
            $status = $this->db->execute($query);
         }else{
             $status = 0;
         }
         
        
        
        return $status;
    }
    

    /**
    * return hierarchically structure of taxonomy for entity(bank,assessment,question,media)
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $parentID
    * @param    integer $EntityTypeID
    * @param    string $ClsUsedforFlag
    * @return   array (json format)
    *
    */
   function taxonomyTree($parentID = "1", $EntityTypeID = "-1", $ClsUsedforFlag = "Question",$QuestionsOnly="", $page= '', $action = '',$level=0,$nodeid) {
        global $DBCONFIG;
        //echo $nodeid;
        //die;
        $resultArr=array();
        $newTaxArr = array();
        $newTaxArrDetailed = array();
        $ClsUsedforFlag = ($ClsUsedforFlag == "") ? "Question" : $ClsUsedforFlag;
        $this->myDebug("TaxonomyTree--" . $EntityTypeID . "TaxonomyTree11--" . $parentID);
        if($nodeid == '#' || $parentID == 1) {
            $result = $this->getTax($parentID, $EntityTypeID);
            //print_r($result);die;
            //print_r($result);
            foreach($result as $key => $value) {
                $newTaxArr['id'] = (int)$value['ID'];
                if ( strpos($page, 'classification') !== false )
                    $newTaxArr['text'] = $value['Taxonomy'] . " (" . $value['TotalUsed'] . ")";
                else
                    $newTaxArr['text'] = $value['Taxonomy'];
                if ( $value['isLeaf'] == 1 ) {
                    $newTaxArr['children'] = false;
                } else {
                    $newTaxArr['children'] = true;   
                }
                $newTaxArrDetailed[] = $newTaxArr;
            }
            //print_r($newTaxArrDetailed);die;
            
        } else {
            $result = $this->getTax($nodeid, $EntityTypeID);
            
            foreach($result as $key => $value) {
                $newTaxArr['id'] = (int)$value['ID'];
                if ( strpos($page, 'classification') !== false )
                    $newTaxArr['text'] = $value['Taxonomy'] . " (" . $value['TotalUsed'] . ")";
                else
                    $newTaxArr['text'] = $value['Taxonomy'];
                if ( $value['isLeaf'] == 1 ) {
                    $newTaxArr['children'] = false;
                    //$newTaxArrDetailed[] = $newTaxArr;
                } else {
                    $newTaxArr['children'] = true;
                    //$newTaxArrDetailed[] = $newTaxArr;  
                }
                $newTaxArrDetailed[] = $newTaxArr;
            }
        }
		
		$levelOn=0;
		if($level==1 && $parentID != 1)
		{
		   $Parrent = trim($this->getTaxonmyParent($parentID), "/");
		   $parrentCount= count(explode('/',$Parrent));

		   if($parrentCount>2)
		   {
			$levelOn=1;
			foreach($result as $key=>$value)
                        {
                                $value['isLeaf']=1;
                                $resultArr[$key]=$value;
                                
                                
                        }
				$result =$resultArr;
		   }
		//	print_r($result);
		}
		
		if($QuestionsOnly!="")
		{
			$QuestionsOnly=explode(',',$QuestionsOnly);
		}
        $i = 1;
        if ($result) {
            $this->taxStr .= "[";
            foreach ($result as $rs) {
                $taxonomy_id = $rs['ID'];
                //Remove Decription from Title.
				//$node_label = $rs['Taxonomy'].':'.$rs['Description'];
				$node_label = $rs['Taxonomy'];
                if (preg_match('/\</', $node_label)) {
                    $node_label = htmlentities($node_label, ENT_QUOTES, "UTF-8");
                }
                $usedcount = $rs['UsedCount'];
                if ($rs['ParentID'] == '1' && $rs['isLeaf'] == '0') {
                    $rel = "drive";
                } else if ($rs['ParentID'] >= '1' && $rs['isLeaf'] == '1') {
                    $rel = "folder";
                } else if ($rs['isLeaf'] == '1' && $rs['UsedCount'] == '0' && $rs['ParentID'] == '1') {
                    $rel = "default";
                }
                // $data = $rs['Taxonomy'];
                $title = $node_label;
                $cssClass = "highlight";
                $checkedStatus = 'no';
                if (($page == 'authoring' && $action == 'quest-editor') && in_array($taxonomy_id, $QuestionsOnly)) {
                    $checkedStatus = 'yes';
                } else if ((($page == 'assessment' || $page == 'bank') && $action == 'question-list') && in_array($taxonomy_id, $QuestionsOnly)) {
                    $checkedStatus = 'yes';
                } else if ((($page == 'assessment' || $page == 'bank') && $action == 'edit') && in_array($taxonomy_id, $AssessBankOnly)) {
                    $checkedStatus = 'yes';
                } else if ($page == 'media' && in_array($taxonomy_id, $EntityTaxoArr)) {
                    $checkedStatus = 'yes';
                } else {
                    $checkedStatus = 'no';
                }
                Site::myDebug($taxonomy_id."--------".$checkedStatus);
                if (in_array($taxonomy_id, $EntityTaxoArr)) {
                    $ClsUsedforFlag = "Entity";
                }
                if (in_array($taxonomy_id, $AllTaxoArr)) {
                    $ClsUsedforFlag = "Question";
                }
                if ($ClsUsedforFlag == "Question") {
                    if (in_array($taxonomy_id, $AllTaxoArr)) {
                        if ($cssClass == "highlight") {
                            $cssClass = "highlightQuestion";
                        } else if ($cssClass == "highlightEntity") {
                            $cssClass = "highlightBoth";
                        }
                    } else {
                        if ($cssClass == "highlightQuestion") {
                            $cssClass = "highlight";
                        } else if ($cssClass == "highlightBoth") {
                            $cssClass = "highlightEntity";
                        }
                    }

                    if ((in_array($taxonomy_id, $EntityTaxoArr)) && (in_array($taxonomy_id, $AllTaxoArr))) {
                        $cssClass = "highlightBoth";
                    }
                } else if ($ClsUsedforFlag == "Entity") {
                    if (in_array($taxonomy_id, $EntityTaxoArr)) {
                        if ($cssClass == "highlight") {
                            $cssClass = "highlightEntity";
                        } else if ($cssClass == "highlightEntity") {
                            $cssClass = "highlightBoth";
                        }
                    } else {
                        if ($cssClass == "highlightEntity") {
                            $cssClass = "highlight";
                        } else if ($cssClass == "highlightBoth") {
                            $cssClass = "highlightQuestion";
                        }
                    }
                    //If the taxonomy id is in both hidden input type which is EnitityTaxo & AllTaxo then it is assigned to both Bank/Assessment & Question - green color
                    if ((in_array($taxonomy_id, $EntityTaxoArr)) && (in_array($taxonomy_id, $AllTaxoArr))) {
                        $cssClass = "highlightBoth";
                    }
                }

                // Query for checking the leaf node
                $queryForLeaf = "SELECT COUNT(*) AS isLeafNode FROM Taxonomies WHERE isEnabled=1 AND ParentID=" . $rs['ID'];
                $queryResLeaf = $this->db->getSingleRow($queryForLeaf);
                $treeLeafNode = ($queryResLeaf['isLeafNode'] > 0) ? 'noleaf' : 'isleaf';
				
				if($levelOn)
				{
					 $treeLeafNode = 'isleaf';
				}
                
                                
                
                                
                /*                
                $result[] = array(
                    "attr" => array("ID" => $rs['ID'], "id" => "node_" . $rs['ID'], "rel" => $rel, "title" => $node_label, 'class' => $cssClass, "checkedStatus" => $checkedStatus, "treeLeafNode" => $treeLeafNode),
                    "data" => $node_label,
                    "ID" => $rs['ID'],
                    "state" => $rs['ParentID'] ? "closed" : "closed"
                );
                */
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($newTaxArrDetailed);
    }

    /**
    * return parentId of taxonomy
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $taxonomyIds
    * @return   string
    *
    */
    function getTaxonmyParent($taxonomyIds)
    {
       global $DBCONFIG;
        $fstr   = '';
        $this->mydebug("selected taxonomy".$taxonomyIds);
        if($taxonomyIds!="")
        {
            $dataArray  = array($taxonomyIds,$this->session->getValue('instID'));
            $resultRows = $this->db->executeStoreProcedure('GetTaxonmyParent',$dataArray, 'nocount');
            $fstr =$this->getValueArray($resultRows, "TaxoParentStr");
        }else{
            $fstr ="";
        }
       
        $this->mydebug("OUTOPUT selected taxonomy".$fstr);
        return $fstr;
    }

    /**
    * search taxonomy in given institute
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $taxonomyIds
    * @return   string
    *
    */
    function searchTaxonomy($input)
    {
        global $DBCONFIG;
        $fstr   = '';
        $this->mydebug("selected taxonomy".$input);
      //  $input["searchparam"]       = trim($input["searchparam"]);
         $input["searchparam"]       = trim(addslashes($input["searchparam"]));
        $input["isExactSearch"]     = (isset($input["isExactSearch"]))?$input["isExactSearch"]:0;
       
        if(!empty($input["searchparam"]))
        {
            if($input["isExactSearch"] == "1"  )
            {
               if ($DBCONFIG->dbType == 'Oracle')
               {
                $qry =  "   SELECT  txn.\"ID\" as taxoId
                            FROM Taxonomies txn, MapClientUser mcu
                            WHERE LOWER(txn.\"Taxonomy\") = LOWER('{$input["searchparam"]}') AND txn.\"UserID\" = mcu.\"UserID\"
                            AND mcu.\"ClientID\" = {$this->session->getValue('instID')}
                            AND mcu.\"isEnabled\" = '1' AND txn.\"isEnabled\" = '1'
                            GROUP BY txn.\"ID\"";
               }
             else
             {

                 $cond = ($input['taxonomyID'] != 0)?' AND txn.ID != '.$input['taxonomyID'].'':'';
                
                 $qry =  "   SELECT  txn.ID as taxoId
                            FROM Taxonomies txn, MapClientUser mcu
                            WHERE txn.Taxonomy = '{$input["searchparam"]}' AND txn.UserID = mcu.UserID
                            AND mcu.ClientID = {$this->session->getValue('instID')}
                            AND mcu.isEnabled = '1' AND txn.isEnabled = '1' {$cond}
                            GROUP BY txn.ID";
             }


            }
            else
            {
                if ($DBCONFIG->dbType == 'Oracle')
               {
                
                $qry =  "   SELECT  txn.\"ID\" as taxoId
                            FROM Taxonomies txn, MapClientUser mcu
                            WHERE LOWER(txn.\"Taxonomy\") like LOWER('{$input["searchparam"]}%') AND txn.\"UserID\" = mcu.\"UserID\"
                            AND mcu.\"ClientID\" = {$this->session->getValue('instID')}
                            AND mcu.\"isEnabled\" = '1' AND txn.\"isEnabled\" = '1'
                            GROUP BY txn.\"ID\"";
               }
               else
               {
                    $qry =  "   SELECT  txn.ID as taxoId
                            FROM Taxonomies txn, MapClientUser mcu
                            WHERE txn.Taxonomy like '{$input["searchparam"]}%' AND txn.UserID = mcu.UserID
                            AND mcu.ClientID = {$this->session->getValue('instID')}
                            AND mcu.isEnabled = '1' AND txn.isEnabled = '1'
                            GROUP BY txn.ID";
               }
            }
           
            $result     = $this->db->getRows($qry);
            $taxoId     = $this->getValueArray($result,'taxoId','multiple');
            $fstr       = $this->getTaxonmyParent($taxoId);
        }
        return $fstr;
    }

    /**
    * get list of used taxonomies for entity
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    integer $taxoId
    * @return   string
    *
    */
    function checkUsedTaxnomy($taxoId)
    {
       global $DBCONFIG;
        $result = $this->getTax($taxoId);
        if($result)
        {            
            foreach($result as $rs)
            {
               if($rs['Count'] > 0)
               {
                   return $rs['Count'];
               }else{
                   return $this->checkUsedTaxnomy($rs['ID']);
               }               
            }
        }
        return 0;
    }

    /**
    * get full classification details for specified Entity.
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array $details
    *
    */
    function getEntityClassification(array $input){
        global $DBCONFIG;
        if(isset($input['Taxonomy']) && isset($input['Taxonomy']))
        {
            $classificationdetails['Taxonomy']  = $input["Taxonomy"];
            $classificationdetails['Tag']       = $input["Tag"];
        }
        else
        {
            if( $input['EntityContainerTypeID'] != $input['EntityTypeID'] &&
                $input['EntityID'] != '-1' && empty($input['EntityID']) == false &&
                $input['EntityContainerID'] != '-1' && empty($input['EntityContainerID']) == false)
            {
                $classificationdetails   = $this->getClassification($input['EntityID'],$input['EntityTypeID']);
                $containerclsdetails     = $this->getClassification($input['EntityContainerID'],$input['EntityContainerTypeID']);

            }
            elseif($input['EntityContainerTypeID'] == $input['EntityTypeID'])
            {
                $classificationdetails = $containerclsdetails     = $this->getClassification($input['EntityContainerID'],$input['EntityContainerTypeID']);
            }
            elseif($input['EntityID'] != '-1' && empty($input['EntityID']) == false )
            {
                $classificationdetails   = $this->getClassification($input['EntityID'],$input['EntityTypeID']);
            }
            elseif($input['EntityContainerID'] != '-1' && empty($input['EntityContainerID']) == false)
            {
                $containerclsdetails     = $this->getClassification($input['EntityContainerID'],$input['EntityContainerTypeID']);
            }
        }

        
            $details = array(
            'Tag'                   => $classificationdetails['Tag'],
            'Taxo'                  => $classificationdetails['Taxonomy'],
            'TaxoPath'              => $this->getTaxonmyParent($classificationdetails['Taxonomy']),
            'EntityContainerTypeID' => $input['EntityContainerTypeID'],
            // 'EntityTags'            => $containerclsdetails['Tag'],
			"EntityTags"            => $classificationdetails['Tag'],
            'EntityTaxo'            => $containerclsdetails['Taxonomy'],
            'EntityTaxoPath'        => $this->getTaxonmyParent($containerclsdetails['Taxonomy']),
            'EntityID'              => $input['EntityID'],
            'TaxoUsageEntityTypeID' => $input['TaxoUsageEntityTypeID'],
            'EntityTypeID'          => $input['EntityTypeID'],
            'rendition'             => $input['rendition'],
            'ClassificationMode'    => 'assign',
            'ClsUsedforFlag'        => ($input['ClsUsedforFlag'] == '')?'Question':$input['ClsUsedforFlag']
        );
        
        return $details;
    }
    
	
	 /**
    * get selected Taxonomy details for specified Entity.
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array $details
    *
    */
    function getAllEntityClassification(array $input){
        global $DBCONFIG;
        
		 $questids = str_replace("||", ",", $input['QuestionID']);
         $questids = trim($questids, "|");
		 $questids = explode(",",$questids);
		 	
		
		 foreach($questids as $key=>$value)
		 {
		    $classificationdetails[$key]   = $this->getClassification($value,$input['EntityTypeID']);
			if($key==0)
			{
				$Taxonomy = $classificationdetails[$key]['Taxonomy'];	
			} else {
				$Taxonomy.=','.$classificationdetails[$key]['Taxonomy'];	
			}
			
		 }
		 if($Taxonomy) {
			 $Taxonomy=implode(',',array_unique(explode(',',$Taxonomy)));
		 }	
            
        $details = array(
         'Taxo'                  => $Taxonomy,
         'TaxoPath'              => $this->getTaxonmyParent($Taxonomy),
		 'PartialState'          => $this->getTaxonmyParent($Taxonomy),
        );

        return $details;
    }
    
	
    /**
    * get Supplement details for specified Entity
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   array $arrSupplementDetails
    *
    */
    public function getEntitySupplement(array $input)
    {
       global $DBCONFIG;
        Site::myDebug("--------GetQuestSupplement");
        $arrSupplementDetails = array();        	
        $dataArray  = array($input['QuestionID']);
        $resultRows = $this->db->executeStoreProcedure('GetQuestSupplement',$dataArray, 'details');

        if ($resultRows)
        {            
            $arrSupplementDetails = $resultRows;
            if ( $arrSupplementDetails['ContentID'] > 0  )
            {
                $arrSupplementDetails['ContentType'] = 2;
            }
            else if ( $arrSupplementDetails['Description'] != '' )
            {
                $arrSupplementDetails['ContentType'] = 1;
            }            
        }
        else
        {
            $arrSupplementDetails['ContentType'] = 3;
            $arrSupplementDetails['ContentID']   = 0;
            $arrSupplementDetails['Description'] = '';            
        }
        Site::myDebug( $arrSupplementDetails );
        $dataJson = json_encode($arrSupplementDetails);
        return $dataJson;
    }

    /**
    * Save Supplement details 
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array $input
    * @return   
    *
    */
    public function saveEntitySuplement($input,$QuestionID )
    {
       global $DBCONFIG;
        Site::myDebug("--------saveEntitySuplement");
	Site::myDebug( $input );          

        if ( $input['contentTypeID'] == 1 ) 
        {
            $input['ContentID']      = 0;
        }
        else if ( $input['contentTypeID'] == 2  )
        {
            $input['supplement_text'] = '';
            $input['ContentID'] = trim($input['ContentID']);
        }
        else
        {
            $input['ContentID']      = 0;
            $input['supplement_text'] = '';
        }        
        $isActive   = 'Y';
        $isEnabled  = '1';

       if ($DBCONFIG->dbType == 'Oracle')
       {
        $data = array(
                    "ContentTypeID" => $input['contentTypeID'],
                    "QuestionID"    => $QuestionID,
                    "ContentID"     => $input['ContentID'],
                    "UsedField"     => 'Supplement',
                    "Description"   => $input['supplement_text'],
                    "UserID"        => $this->session->getValue('userID'),
                    "AddDate"       => $this->currentDate(),
                    "ModBY"         => $this->session->getValue('userID'),
                    "ModDate"       => $this->currentDate(),
                    "isActive"      => $isActive,
                    "isEnabled"     => $isEnabled,
                );
       }
         else
         {
             $data = array(
                    'ContentTypeID' => $input['contentTypeID'],
                    'QuestionID'    => $QuestionID,
                    'ContentID'     => $input['ContentID'],
                    'UsedField'     => 'Supplement',
                    'Description'   => $input['supplement_text'],
                    'UserID'        => $this->session->getValue('userID'),
                    'AddDate'       => $this->currentDate(),
                    'ModBY'         => $this->session->getValue('userID'),
                    'ModDate'       => $this->currentDate(),
                    'isActive'      => $isActive,
                    'isEnabled'     => $isEnabled,
                );
         }
        $this->db->executeStoreProcedure('ManageQuestSupplement',$data);        
    }    

	function getTaxall($parentID,$EntityTypeID = "-1")
    {
        global $DBCONFIG;
        
        $this->myDebug("GetTaxall--".$EntityTypeID);
		$taxList = $this->db->executeStoreProcedure('TaxonomyListall',array($parentID,$this->session->getValue('userID'),$this->session->getValue('instID'),$EntityTypeID,"-1",'-1'));
		$this->myDebug("Taxlist in function getTaxall --".$taxList);
        return $taxList["RS"];
    }
    
    function taxonomyTreexml($parentID=1,$EntityTypeID = "-1",$ClsUsedforFlag = "Question")
    {
		global $DBCONFIG;
        $ClsUsedforFlag = ($ClsUsedforFlag == "")? "Question" : $ClsUsedforFlag;
        $this->myDebug("TaxonomyTreexml in function taxonomyTreexml -- ".$EntityTypeID);
        $result         = $this->getTaxall($parentID,$EntityTypeID);
        $i              = 1;
		
        if($result)
        {
			$taxXML  = '<root>';
            $returnXML = '<root>';
            $retXmlData = $result;
            $newXmlArr = array();
            $allXmlId = array();
            
            foreach($retXmlData as $eachXmlElement)
            {				
				$newXmlArr[$eachXmlElement['ID']]['ID'] = $eachXmlElement['ID'];                
                $newXmlArr[$eachXmlElement['ID']]['ParentId'] = $eachXmlElement['ParentID'];
                $newXmlArr[$eachXmlElement['ID']]['isLeaf'] = $eachXmlElement['isLeaf'];
                $newXmlArr[$eachXmlElement['ID']]['Title'] = $eachXmlElement['Taxonomy'];
                $allXmlId[] = $eachXmlElement['ID'];				
            }
			//echo "<pre>";print_r($newXmlArr);echo "<pre>";			
            $firstElementFlag = 1;            
            foreach($newXmlArr as $eachXmlData)
            {
                $rel = "folder";
				if($eachXmlData['isLeaf'] == '1')
                {
                    $rel = "file";
                }
                // else if ($eachXmlData['IsParent'] && $eachXmlData['TotalBankCount']>0)
                // {
                    // $rel = "folder";
                // }
                else
                {
                    $rel = "folder";
					//$rel = "default";
                }

                $titleData = "";     
                $XmlNodeId = "node_" . $eachXmlData['ID'];
                $XmlParentNodeId = "node_" . $eachXmlData['ParentId'];

                $returnXML .= '<item parent_id="'.$XmlParentNodeId.'" id="'.$XmlNodeId.'" title="'.$eachXmlData['Title'].'" rel="'.$rel.'">';
                $returnXML .= '<content>';
                
				$returnXML .= '<name><![CDATA['.htmlspecialchars_decode($eachXmlData['Title'],ENT_QUOTES).']]>'.$titleData;
                $returnXML .= '</name>';
                $returnXML .= '</content>';
                $returnXML .= '</item>';

                $taxXML   .= '<item parent_id="'.$XmlParentNodeId.'" id="'.$XmlNodeId.'"  rel="'.$rel.'"  >';
                $taxXML   .= '<content>';
                
				$taxXML   .= '<name><![CDATA['. htmlspecialchars_decode($eachXmlData['Title'],ENT_QUOTES).']]>';				
                $taxXML   .= '</name>';
                $taxXML   .= '</content>';
                $taxXML   .= '</item>';                
            }
            $returnXML .= '</root>';
            $taxXML   .= '</root>';	

        }
		$arrXmlData['tax_item'] = $returnXML;
        $arrXmlData['tax'] = $taxXML;
        unset ($returnXML);
        unset ($bankXML);
        //echo "<pre>";print_r($arrXmlData);echo "</pre>";die('789456123');		
		return $arrXmlData;		
    }
	
	 
	 /**
	 * * PAI02 :: sprint 3 ::  QUADPS-36
     * Upload Taxonomy a xlsx file. 
     * @access   public
     * @param    $input array
     * @return   details
    */
	 

    function uploadTaxonomyFromXlsx(array $input)
    {
        $mda = new Media();
        $guid = substr($_FILES['iduploadValueXLSX']['name'], 0, -4); //remove .CSV file extension
        $guid = uniqid($guid);
        $file_ext = $mda->findExt(basename($_FILES['iduploadValueXLSX']['name']));
        $target_path_dir = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->importTaxonomy));
        $target_path_xlxs = "{$target_path_dir}" . $guid . ".{$file_ext}";
		
		$originalfile = $_FILES['iduploadValueXLSX']['name'];

        if (!is_dir($target_path_dir)):
            mkdir($target_path_dir, 0777);
        endif;

        if (file_exists($target_path_xlxs))
        { ///this condition is irrelevant if we are allowing user to upload same file twice..still has been left uncommented just in case we need it in future...
            $error = "File [" . basename($_FILES['iduploadValueXLSX']['name']) . "] has already been uploaded.";
            $mesg = "";
            echo "{";
            echo "error: '" . $error . "',";
            echo "msg: '" . $mesg . "',";
            echo "file: ''";
            echo "}";
        }
        else
        {
            Site::myDebug($_FILES);
            $fileuploaderror = $_FILES['iduploadValueXLSX']['error'];
            $sourcefile = $_FILES['iduploadValueXLSX']['tmp_name'];
            //$targetpath         = $target_path_zip;
            if (!empty($fileuploaderror))
            {
                switch ($fileuploaderror)
                {
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
            }
            elseif (empty($sourcefile) || $sourcefile == 'none')
            {
                $error = 'No file was uploaded..';
            }
            else
            {
                $status = move_uploaded_file($sourcefile, $target_path_xlxs);
            }

            if ($status)
            {
              $out_data = json_decode($this->importTaxonomyFromXLSX($target_path_xlxs,$originalfile), true);
              
                switch ($out_data['status'])
                {
                    case '1':
                        $msg = 'No Taxonomy Imported';
                    break;
                    case '2':
                        $msg = 'The uploaded file exceeds Maximum number of Topic Levels that is 24.';
                    break;
                    default:
                        $msg = "Taxonomy Import details: Successful: " . $out_data['successCounter'] ." Failure : ".$out_data['failureCounter'];
                }
                
                
                return "{error: '{$error}',msg:'{$msg}',file:'',id:'{$out_data[0]}',name:'{$out_data[1]}',noofquest:'{$out_data[2]}',noofimportquest:'{$out_data[3]}'}";
            }
            else
            {
                return "{error: '{$error}',msg:'',file:'',id:'',name:'',noofquest:''}";
            }
        }
    }

	/**
	 * * PAI02 :: sprint 3 ::  QUADPS-36
     * Import Taxonomy from the CSV file. 
     * @access   public
     * @param    $taxoID
     * @return   details
    */

    public function importTaxonomyFromXLSX($csvFile,$originalfile)
    {
        if (file_exists($csvFile))
        {
            $successCounter = 0;
            $failureCounter = 0;
            $file = basename($originalfile, ".csv");
            //$getTaxonomyParent = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".trim($file)."' AND UserID = '". $this->session->getValue('userID') . "' AND isEnabled = '1'";
            $getTaxonomyParent = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".addslashes(trim($file))."' AND isEnabled = '1'";
            $getTaxonomyArrParent = $this->db->getSingleRow($getTaxonomyParent);
            if(!count($getTaxonomyArrParent)) {
                    $parentID = $this->bulkInsertionTaxonomyFromXlsx(1,$file,'');
                    $successCounter++;
            } else {
                    $parentID = $getTaxonomyArrParent['ID'];
                    $Taxonomy = $getTaxonomyArrParent['Taxonomy'];
            }


            $csvData = $this->readCSV($csvFile);
            for($i=1;count($csvData)>$i;$i++)
            {

                if(trim($csvData[$i][1])!='')
                {
                    //$getTaxonomies = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".trim($csvData[$i][1])."' AND UserID = '". $this->session->getValue('userID') . "' AND isEnabled = '1' order by ID desc";
                    $getTaxonomies = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".addslashes(trim($csvData[$i][1]))."'  AND isEnabled = '1' order by ID desc";
                    $getTaxonomiesArr = $this->db->getSingleRow($getTaxonomies);
                    if ( count($getTaxonomiesArr) > 0 ) {
                        $parentID = ($getTaxonomiesArr['ID'] == '') ? $parentID : $getTaxonomiesArr['ID'];
                    }
                }


                //$getTaxonomy = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".trim($csvData[$i][0])."' AND ParentID='".$parentID."'  AND UserID = '". $this->session->getValue('userID') . "' AND isEnabled = '1'";
                $getTaxonomy = "SELECT ID FROM Taxonomies WHERE Taxonomy = '".addslashes(trim($csvData[$i][0]))."' AND ParentID='".$parentID."'   AND isEnabled = '1'";
                $getTaxonomyArr = $this->db->getSingleRow($getTaxonomy);
                if( !count($getTaxonomyArr) ) {
                        $taxoIDPar = $this->bulkInsertionTaxonomyFromXlsx($parentID,$csvData[$i][0],$csvData[$i][4]);
                        if ( $taxoIDPar == 1 || $taxoIDPar == 2 )
                            $failureCounter++;
                        else
                            $successCounter++;
                }else{
                     $failureCounter++; 
                }

            }
            $returnData = json_encode(array('status' => $taxoIDPar, 'successCounter' => $successCounter, 'failureCounter' => $failureCounter));
            return $returnData;
			
        }
    }
	/**
	 * * PAI02 :: sprint 3 ::  QUADPS-36
     * Read CSV file. 
     * @access   public
     * @param    $csvFile
     * @return   Array
    */
	function readCSV($csvFile){
		$mycsvfile = array(); //define the main array.
		$row = 0;
		if (($handle = fopen($csvFile, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$num = count($data);
				for ($c=0; $c < $num; $c++) {
					 $mycsvfile[$row][]=$data[$c];
				}
				$row++;
			}
			fclose($handle);
		}
		return $mycsvfile;
	}

	/**
	 * * PAI02 :: sprint 3 ::  QUADPS-36
     * Import Taxonomy from the CSV file. 
     * Calling for bulk insertion of taxonomy.
     * @access   public
     * @param    $parentID, $taxonomy
     * @return   $taxoID
    */
	 
    function bulkInsertionTaxonomyFromXlsx($parentID, $taxonomy,$taxonomyDesc)
    {
        //$getSequenceQ = "SELECT MAX(Sequence)+1 AS seq FROM Taxonomies WHERE ParentID = '" . $parentID . "' AND UserID = '" . $this->session->getValue('userID') . "' AND isEnabled = '1'";
        $getSequenceQ = "SELECT MAX(Sequence)+1 AS seq FROM Taxonomies WHERE ParentID = '" . $parentID . "'  AND isEnabled = '1'";
        $getSequenceArr = $this->db->getSingleRow($getSequenceQ);
        $sequence = ($getSequenceArr['seq'] == '') ? 1 : $getSequenceArr['seq'];


        $taxValues = array('Taxonomy' => trim($taxonomy),
            'ParentID' => $parentID,
            'Sequence' => $sequence,
			'Description'=> $taxonomyDesc,
            'AccessMode' => 'Private',
            'Count' => 0,
            'UserID' => $this->session->getValue('userID'),
            'AddDate' => $this->currentDate(),
            'ModBY' => $this->session->getValue('userID'),
            'ModDate' => $this->currentDate(),
            'isEnabled' => '1'
        );
        $taxoID = $this->db->insert("Taxonomies", $taxValues);
        return $taxoID;
    }
    
    function checkDuplicateTag($tagName=''){
       $sql = "SELECT ID  FROM Tags WHERE Tag = '" . trim($tagName) . "' AND isEnabled = '1'";
        $res = $this->db->getSingleRow($sql);
        if($res['ID']!=''){
            return 0;
        }else {
            return 1;
        }
    }
    
    // Function for checking the current usage of the tag in questios/assessments
    function checkTagAvailInQuestion($tagID) {
        global $DBCONFIG;
        //$tagID  = implode(',',(array)$this->removeBlankElements($tagID));
        $query_check_existance = "SELECT COUNT(*) AS cnt FROM Classification WHERE ClassificationID IN ($tagID) AND ClassificationType='Tag' AND isEnabled='1'";
        $resultset = $this->db->getSingleRow($query_check_existance);
        return $resultset['cnt'];
    }
    
    /*===========================key Value Pair Added =======================*/
     function uploadKVPFromCSV(array $input)
    {
        $mda = new Media();
        $guid = substr($_FILES['iduploadValueXLSXKVP']['name'], 0, -4); //remove .CSV file extension
        $guid = uniqid($guid);
        $file_ext = $mda->findExt(basename($_FILES['iduploadValueXLSXKVP']['name']));
        $target_path_dir = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => $this->cfgApp->importData . $this->cfgApp->importTaxonomy));
        $target_path_xlxs = "{$target_path_dir}" . $guid . ".{$file_ext}";		
        $originalfile = $_FILES['iduploadValueXLSXKVP']['name'];

        if (!is_dir($target_path_dir)):
            mkdir($target_path_dir, 0777);
        endif;
          
        if (file_exists($target_path_xlxs))
        { ///this condition is irrelevant if we are allowing user to upload same file twice..still has been left uncommented just in case we need it in future...
            $error = "File [" . basename($_FILES['iduploadValueXLSXKVP']['name']) . "] has already been uploaded.";
            $mesg = "";
            echo "{";
            echo "error: '" . $error . "',";
            echo "msg: '" . $mesg . "',";
            echo "file: ''";
            echo "}";
        }
        else
        {
           
            Site::myDebug($_FILES);
            $fileuploaderror = $_FILES['iduploadValueXLSXKVP']['error'];
            $sourcefile = $_FILES['iduploadValueXLSXKVP']['tmp_name'];
            //$targetpath         = $target_path_zip;
            
            if (!empty($fileuploaderror))
            {
                
                switch ($fileuploaderror)
                {
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
            }
            elseif (empty($sourcefile) || $sourcefile == 'none')
            {
                $error = 'No file was uploaded..';
            }
            else
            {
                $this->registry->site->myDebug("sourcefile-akhlack");
                $this->registry->site->myDebug($sourcefile);
                $this->registry->site->myDebug("target_path_xlxs-akhlack");
                $this->registry->site->myDebug($target_path_xlxs);
                
                $status = move_uploaded_file($sourcefile, $target_path_xlxs);
            }
            if ($status)
            {
              $out_data = json_decode($this->importKVPFromCSV($target_path_xlxs,$originalfile), true);
              $this->registry->site->myDebug("out_data-akhlack");
              $this->registry->site->myDebug($out_data);
            
                switch ($out_data['status'])
                {
                    case '1':
                        $msg = 'No Key value pair Imported';
                    break;
                    case '2':
                        $msg = 'The uploaded file exceeds.';
                    break;
                    default:
                        $msg = $out_data['successCounter']." Key Value pair successfully added";
                        if( $out_data['failureCounter'] > 0  ){
                          $msg = "".$out_data['successCounter'] ." Key Value pair successfully added.<br> ".$out_data['failureCounter']." Key Value pair addition failed."  ;
                        }
                        
                }
                
                
                return "{error: '{$error}',msg:'{$msg}',file:'',id:'{$out_data[0]}',name:'{$out_data[1]}',noofquest:'{$out_data[2]}',noofimportquest:'{$out_data[3]}'}";
            }
            else
            {
                return "{error: '{$error}',msg:'',file:'',id:'',name:'',noofquest:''}";
            }
        }
    }
    
    public function importKVPFromCSV($csvFile,$originalfile)
    {
     
        if (file_exists($csvFile))
        {
            $successCounter = 0;
            $failureCounter = 0;
            $file = basename($originalfile, ".csv");

            $csvData = $this->readCSV($csvFile);
//            $this->registry->site->myDebug("csvData-akhlack");
//            $this->registry->site->myDebug($csvData);
            
           // $convertCSVDataIntoString = implode("@#$$#@",$csvData);
            //$this->registry->site->myDebug("convertCSVDataIntoString-akhlack");
            //$this->registry->site->myDebug($convertCSVDataIntoString);
            for($i=1;count($csvData)>$i;$i++){
                $convertCSVDataIntoString .= implode("@@@",$csvData[$i]).'***';                
            
                if(trim($csvData[$i][0])!=''){
                        $getKVP         = "SELECT ID FROM MetaDataKeys WHERE MetaDataName = '".trim($csvData[$i][0])."'  AND isEnabled = '1' order by ID desc";
                        $getKVPArr      = $this->db->getSingleRow($getKVP);
                        if ( count($getKVPArr) > 0 ) {
                            $failureCounter++;
                        }else{
                            $taxoIDPar = $this->bulkInsertionKPFromCSV($csvData[$i]);
                            if ( $taxoIDPar == 1  )
                                $successCounter++;
                            else
                                $failureCounter++;
                        }
                }else{
                     $failureCounter++;
                }

            }
            
//            $this->registry->site->myDebug("convertCSVDataIntoString-akhlack");
//            $this->registry->site->myDebug(rtrim($convertCSVDataIntoString,'***'));
            $returnData = json_encode(array('status' => '', 'successCounter' => $successCounter, 'failureCounter' => $failureCounter));
            return $returnData;
			
        }
    }
    function bulkInsertionKPFromCSV($kvp)
    {
//        $this->registry->site->myDebug("bulkInsertionKPFromCSV-csvData-start");
//        $this->registry->site->myDebug($kvp);
//        $this->registry->site->myDebug("bulkInsertionKPFromCSV-csvData-end");
        if( strtolower($kvp[1]) == 'select' ){
            if( trim( $kvp[2] ) != "" ){
                $insertMetaDataKeys = array(
                                'MetaDataName'  => trim($kvp[0]),
                                'MetaDataType'  => 'select_list',
                                'UserID'        => $this->session->getValue('userID'),
                                'UseCount'      => 0,                
                                'AddDate'       => $this->currentDate(),
                                'ModBY'         => $this->session->getValue('userID'),
                                'ModDate'       => $this->currentDate(),
                                'isActive'      => 'Y',
                                'isEnabled'     => '1'
                            );
                $KVPID              = $this->db->insert("MetaDataKeys", $insertMetaDataKeys);                
                $metaDataValuesArr  = explode("|||",trim( $kvp[2] ));
                $metaDataValuesArr  = array_unique($metaDataValuesArr);
//                 $this->registry->site->myDebug("metaDataValuesAr-bulkInsertionKPFromCSV-csvData-end");
//                 $this->registry->site->myDebug($metaDataValuesArr);
       
                /*============ values ==*/
               foreach( $metaDataValuesArr as $key => $val ){
                    if( trim( $val ) != "" ){
                        $insertMetaDataValues = array(
                                     'MetaDataValue'  => trim($val),
                                     'UserID'        => $this->session->getValue('userID'),
                                     'UseCount'      => 0,                
                                     'AddDate'       => $this->currentDate(),
                                     'ModBY'         => $this->session->getValue('userID'),
                                     'ModDate'       => $this->currentDate(),    
                                     'isEnabled'     => '1'
                                 );
//                        $this->registry->site->myDebug("insertMetaDataValues-bulkInsertionKPFromCSV-csvData-end");
//                        $this->registry->site->myDebug($insertMetaDataValues);
                        $KVPVALUEID = $this->db->insert("MetaDataValues", $insertMetaDataValues);
                         $insertMapMetaDataKeyValue = array(
                                     'KeyID'         => trim($KVPID),
                                     'ValueID'       => trim($KVPVALUEID),
                                     'UserID'        => $this->session->getValue('userID'),                                              
                                     'AddDate'       => $this->currentDate(),
                                     'ModBY'         => $this->session->getValue('userID'),
                                     'ModDate'       => $this->currentDate(),    
                                     'isEnabled'     => '1'
                                 );
//                        $this->registry->site->myDebug("insertMapMetaDataKeyValue-bulkInsertionKPFromCSV-csvData-end");
//                        $this->registry->site->myDebug($insertMapMetaDataKeyValue);
                        $KVPVALUEID = $this->db->insert("MapMetaDataKeyValues", $insertMapMetaDataKeyValue);
                         
                    }                   
               }
              return 1;
            }else{
                return 0;
            }
            
        }else if( strtolower( $kvp[1] ) == 'text field') {
            
            $insertKVP = array(
                                'MetaDataName'  => trim($kvp[0]),
                                'MetaDataType'  => 'text_entry',
                                'UserID'        => $this->session->getValue('userID'),
                                'UseCount'      => 0,                
                                'AddDate'       => $this->currentDate(),
                                'ModBY'         => $this->session->getValue('userID'),
                                'ModDate'       => $this->currentDate(),
                                'isActive'      => 'Y',
                                'isEnabled'     => '1'
                            );
            $taxoID = $this->db->insert("MetaDataKeys", $insertKVP);
            return 1;
        }else{
            return 0;
        }
        
        
//        $getSequenceQ = "SELECT MAX(Sequence)+1 AS seq FROM Taxonomies WHERE ParentID = '" . $parentID . "' AND UserID = '" . $this->session->getValue('userID') . "' AND isEnabled = '1'";
//        $getSequenceArr = $this->db->getSingleRow($getSequenceQ);
//        $sequence = ($getSequenceArr['seq'] == '') ? 1 : $getSequenceArr['seq'];
//
//
//        $taxValues = array('Taxonomy' => trim($taxonomy),
//            'ParentID' => $parentID,
//            'Sequence' => $sequence,
//			'Description'=> $taxonomyDesc,
//            'AccessMode' => 'Private',
//            'Count' => 0,
//            'UserID' => $this->session->getValue('userID'),
//            'AddDate' => $this->currentDate(),
//            'ModBY' => $this->session->getValue('userID'),
//            'ModDate' => $this->currentDate(),
//            'isEnabled' => '1'
//        );
//        $taxoID = $this->db->insert("Taxonomies", $taxValues);
//        return $taxoID;
    }
 
//    function taxonomyNextLevelChild($parentId=''){
//      
//        $query = "SELECT  distinct   txn.ID as id FROM Taxonomies txn  INNER JOIN MapClientUser mcu on txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."'  LEFT JOIN Taxonomies txnsub on txnsub.ParentID = txn.ID and txnsub.isEnabled = '1'  WHERE  txn.isEnabled = '1' and txn.ParentID='".$parentId."'   ORDER BY txn.ID DESC ";        
//        return $this->db->getRows($query);
//    }
    function rootTaxonomy($page='',$parentId=1){       
       
        $taxonomyCountField = ( strpos($page, 'classification') !== false ) ? " CONCAT_WS('', txn.Taxonomy,'(', txn.Count,')') as text " : " txn.Taxonomy as text ";
        $query              = "SELECT  distinct   txn.ID as id,  txn.ParentID, ".$taxonomyCountField." , IF ( txnsub.ID , 0 , 1) as isLeaf FROM Taxonomies txn  INNER JOIN MapClientUser mcu on txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."'  LEFT JOIN Taxonomies txnsub on txnsub.ParentID = txn.ID and txnsub.isEnabled = '1'  WHERE  txn.isEnabled = '1' and txn.ParentID='".$parentId."' ORDER BY txn.ID ASC ";
        return $this->db->getRows($query);
    }
    
   
 
    function taxonomyParentChildTree($parent,$page='',$selecetdNode)
    {
        
        $taxonomyCountField = ( strpos($page, 'classification') !== false ) ? " CONCAT_WS('', txn.Taxonomy,'(', txn.Count,')') as text " : " txn.Taxonomy as text ";      
        $query              = "SELECT  distinct   txn.ID as id,  txn.ParentID, ".$taxonomyCountField." , IF ( txnsub.ID , 0 , 1) as isLeaf FROM Taxonomies txn  INNER JOIN MapClientUser mcu on txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."'  LEFT JOIN Taxonomies txnsub on txnsub.ParentID = txn.ID and txnsub.isEnabled = '1'  WHERE  txn.isEnabled = '1' and txn.ParentID='".$parent."' ORDER BY txn.ID ASC ";
        $rowCategories      = $this->db->getRows($query);    

        $thisLevel = array();
        
        foreach ($rowCategories as $child) 
        {
                if(in_array($child['id'],$selecetdNode)){
                    $thisLevel[$child['id']]    = $child;
                    $childExist                 = $this->taxonomyParentChildTree($child['id'],$page,$selecetdNode);
                    $childExist                = array_filter($childExist);
                    if(!empty($childExist)){
                        $thisLevel[$child['id']]['children'] = $childExist;

                    }
                }else{
                    $thisLevel[$child['id']]    = $child;   
                    
                    if ($child['isLeaf']==0 ) { // Donot Load those childen whose parent is not choosen by user 
                        $thisLevel[$child['id']]['children'] = true;
                    } 
                }

        }
     
        return array_values($thisLevel);


    }
    /*
     * Method Desc :- Will Return All The Taxonomy 
     * Create date :- 1st Dec,2015
     * Author      :- Akhlack 
     */
    public function allTaxonomy($page=''){        
        $query         = " SELECT txn.ID as id,txn.Taxonomy as text,txn.ParentID FROM Taxonomies txn INNER JOIN MapClientUser mcu on txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE  txn.isEnabled=1 ";
        if ( strpos($page, 'classification') !== false ){           
            $query     = " SELECT txn.ID as id,CONCAT_WS('', txn.Taxonomy,'(', txn.Count,')') as text ,txn.ParentID FROM Taxonomies txn INNER JOIN MapClientUser mcu on txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE  txn.isEnabled=1 ";
        }
        return $this->db->getRows($query);
    }
    /*
     * Method Desc :- Will generate Taxonomy Parent Child Tree usging Array 
     * Create date :- 1st Dec,2015
     * Author      :- Akhlack 
     */
    public function taxonomyParentChildTreeArray($ar,$id){
      
        $rowCategories  = $this->searchArray($ar,'ParentID',$id);
        $thisLevel      = array();
        foreach ($rowCategories as $child){
              
            $thisLevel[$child['id']]    = $child;
            $childExist                 = $this->taxonomyParentChildTreeArray($ar, $child['id']);
            $childExist                 = array_filter($childExist);
            if(!empty($childExist)){
                $thisLevel[$child['id']]['children'] = $childExist;
            }

        }
        return array_values($thisLevel);


    }
    /*
     * Method Desc :- This function search array From another array
     * Create date :- 1st Dec,2015
     * Author      :- Saikat 
     */
    
    public function searchArray($array, $key, $value){
        $results = array();

        if (is_array($array)) {
            if (isset($array[$key]) && $array[$key] == $value) {
                $results[] = $array;
            }
            foreach ($array as $subarray) {
                $results = array_merge($results, $this->searchArray($subarray, $key, $value));
            }
        }

        return $results;
    }

    function initializeTaxnomoyForBank(){
        $resOutput      = array();
        $rootParent     = $this->allTaxonomy();       
        $resOutput      = $this->taxonomyParentChildTreeArray($rootParent, 1);                      
        return $resOutput;
        
    }
    function initializeTaxnomoyForBankACEPublish($parentId){
        ini_set('xdebug.max_nesting_level', 5000);
        $resOutput     = array();
        $rootParent     = $this->rootTaxonomyACEPublish($parentId);       
        foreach( $rootParent as $key => $val ){
             $ret = array ();
             $childReturn  = $this->taxonomyParentChildTreeACEPublish($val['ID'],'');
             $ret['id'] = $val['ID'];
             //$ret['text'] = $val['Taxonomy'];
             $ret['text'] = $val['text'];
             $ret['guid'] = $val['guid'];
             $childReturn = array_filter($childReturn);

            if (!empty($childReturn)) {
                $ret['children'] = array($childReturn);
            }
             
            
             array_push($resOutput,$ret);
        }        
        
         /* $this->registry->template->ajaxresult = json_encode($resOutput);
         $this->registry->template->show('Ajax', 'ajax'); */
		 //return json_encode($resOutput);
         return $resOutput;
        
    }
    function taxonomyParentChildTreeACEPublish($parent,$page='')
    {
        //$query      = "SELECT ID as id ,Taxonomy as text,ParentID FROM Taxonomies WHERE ParentID=".$parent." AND isEnabled=1 ";                
        $query      = "SELECT txn.ID as id,txn.Taxonomy AS text,txn.ParentID as parentid,txn.guid FROM Taxonomies txn INNER JOIN MapClientUser mcu ON txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE txn.ParentID=".$parent."  AND txn.isEnabled=1 ";                
        if ( strpos($page, 'classification') !== false ){
            //$query     = "SELECT ID as id ,CONCAT_WS('', Taxonomy,'(', Count,')') as text,ParentID FROM Taxonomies WHERE ParentID=".$parent." AND isEnabled=1 ";                
            $query      = "SELECT txn.ID as id ,CONCAT_WS('', txn.Taxonomy,'(', txn.Count,')') as text,txn.ParentID as parentid,txn.guid FROM Taxonomies txn INNER JOIN MapClientUser mcu ON txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE txn.ParentID=".$parent."  AND txn.isEnabled=1 ";                
        }
        $rowCategories = $this->db->getRows($query);    

        $thisLevel = array();
        foreach ($rowCategories as $child) 
        {
                $thisLevel[$child['id']] = $child;
                $childExist = $this->taxonomyParentChildTreeACEPublish($child['id'],$page);
                 $childExist = array_filter($childExist);
                if(!empty($childExist)){
                    $thisLevel[$child['id']]['children'] = $childExist;
                }

        }
        return array_values($thisLevel);
    }
    function rootTaxonomyACEPublish($parentId=''){
        //$query      = "SELECT ID,Taxonomy as text,ParentID FROM Taxonomies WHERE ParentID=1 AND isEnabled=1 ";
        if( $parentId != ''){
            $query      = "SELECT txn.ID,txn.Taxonomy AS text,txn.ParentID as parentid,txn.guid FROM Taxonomies txn INNER JOIN MapClientUser mcu ON txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE txn.ParentID = 1 AND txn.ID IN (".$parentId.")   AND txn.isEnabled=1 ";                            
        }else{
            $query      = "SELECT txn.ID,txn.Taxonomy AS text,txn.ParentID as parentid,txn.guid FROM Taxonomies txn INNER JOIN MapClientUser mcu ON txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE txn.ParentID=1  AND txn.isEnabled=1 ";                
        }
        
//        if ( strpos($page, 'classification') !== false ){
//           //$query      = "SELECT ID,CONCAT_WS('', Taxonomy,'(', Count,')') as text,ParentID FROM Taxonomies WHERE ParentID=1 AND isEnabled=1 ";                
//            $query      = "SELECT txn.ID,CONCAT_WS('', txn.Taxonomy,'(', txn.Count,')') as text,txn.ParentID FROM Taxonomies txn INNER JOIN MapClientUser mcu ON txn.UserID = mcu.UserID AND mcu.clientID = '".$this->session->getValue('instID')."' WHERE txn.ParentID=1  AND txn.isEnabled=1 ";                
//        }
        return $this->db->getRows($query);
    }
    
    /*
     * @method name     :: getSelectedTaxonomyPath
     * @author name     :: Akhlack
     * @created date    :: 13th June,2016 
     */
    public function getSelectedTaxonomyPath( $entityID, $entityTypeID ){
        $taxonomy = $this->db->executeStoreProcedure('ClassificationAssignedList', array( $entityID, $entityTypeID ), "nocount");
        return $this->getTaxonmyParent( $taxonomy[0]['Taxonomy']);               
    }
    
    /*
     * @method name     :: getTaxonomyACEPublish
     * @description     :: This will return all root taxonomy For ACE Publish
     * @author name     :: Akhlack
     * @created date    :: 13th June,2016 
     */
    public function getTaxonomyACEPublish(){        
        $resOutput      = array();        
        $rootParent     = $this->rootTaxonomyACEPublish();        
        $rootParent     = array_filter($rootParent);

        if ( !empty( $rootParent ) ) {
            $productStr = "";
            $productStr .= '<select class="e1"  id="ace-taxonomy">';
            foreach ($rootParent as $k => $val) {
                if( trim( $val['text'] ) != '' ){
                    $productStr .= '<option value="' . htmlentities($val['ID']) . '">' . $val['text'] . '</option>';
                }
            }
            $productStr .='</select>';
            return $productStr;
        }else{
               return '<select class="e1"   id="ace-taxonomy"></select>';
        }
    }
    
    /*
     * @method name     :: getTagACEPublish
     * @description     :: This will return Tag For ACE Publish
     * @author name     :: Akhlack
     * @created date    :: 4th July,2016 
     */
    public function getTagACEPublish(array $input = array()){ 
                
        global $DBCONFIG;        
        $clsfctnList                = $this->tagList($input);          
        if(!empty($clsfctnList['RS']))
        {
            foreach($clsfctnList['RS'] as $clsfctn){
                $tags[$clsfctn["Tag"]]= $clsfctn["Count"];
            }
            $tagStr  = "";
            $tagStr .= '<select class="ace-tag-cls" multiple="multiple"  id="ace-tag">';
            foreach($clsfctnList['RS'] as $tagListArr){                
                if($tagListArr["Tag"]!=''){
                    $tagStr .= '<option value="'.$tagListArr["ID"].'">'.$tagListArr["Tag"].'</option>';
                }
            }	
            $tagStr .='</select>';
            return $tagStr;
        }
        else{
            return '<select class="ace-tag-cls" id="ace-tag"></select>';
        }
    }
    
    
//     public function categoryChild($id) {
//          global $DBCONFIG;
//        $s = "SELECT ID FROM Taxonomies WHERE ParentID = '1388'";
//        $r =  $this->db->getRows($s);
//
//        $children = array();
//
//        if(count($r) > 0) {
//            # It has children, let's get them.
////            while($row = mysql_fetch_array($r)) {
////                # Add the child to the list of children, and get its subchildren
////                $children[$row['ID']] = $this->categoryChild($row['ID']);
////            }
//            
//            foreach ( $r as $key => $val ){
//               $children[$val['ID']] = $this->categoryChild($val['ID']); 
//            }
//        }
//      
//        return $children;
//    }
    
    
    public function updatePrevQuestionTaxo($EntityTypeID, $EntityID)
    {
        if ( $EntityTypeID > 0 &&  $EntityID > 0 )
        {
            $sqlUpdateTaxo  =   "UPDATE `Classification` SET `isEnabled` = 0  "
                                    . " WHERE `isEnabled`= 1 AND `EntityTypeID` = ".$EntityTypeID. " AND `EntityID` = ". $EntityID ; 
            // $this->registry->site->myDebug('--------------$sqlUpdateTaxo');
            // $this->registry->site->myDebug($sqlUpdateTaxo);
            $result         =   $this->db->execute($sqlUpdateTaxo);	
        }            
        return;
    }               
    
}
?>