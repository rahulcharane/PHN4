<?php

class Metadata extends Site
{
    public $taxStr;

    /**
      * constructs a new classname instance
      */
    
    function __construct()
    {
        parent::Site();
        $this->taxStr   =   "";
    }

    /**
    * Upload Metadata from CSV file
    *
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     string
    *
    */
    
    function uploadMetadataCsv()
    {
        $target_path_dir    = $this->cfg->rootPath."/".$this->cfgApp->TempLocation;
        // $target_path_dir    = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'others') );
        $target_path_zip    = "{$target_path_dir}/".$guid.".xml"; //....because we decided to allow user to upload the same multiple times.
        move_uploaded_file($sourcefile, $targetpath);

        $fileElementName    = 'uploadCSV';
        $file_error         = $this->fileParam($fileElementName, 'error');
        $file_temp_name     = $this->fileParam($fileElementName, 'tmp_name');        
        $input  =   array();
        if( $this->verifyUploadedFile($input, $fileElementName)==1 )
        {
            $filedata   = file_get_contents($file_temp_name, true);            
            $mesg       = "File has been successfully uploaded.<BR><BR>";
                            echo "{";
                            echo	   "error: '".$error."'," ;
                            echo	   "msg: '".$mesg."'," ;
                            echo	   "valstr: '".$filedata."'" ;
                            echo "}";
        }
        else
        {
            $mesg       = "File is not uploaded.<BR><BR>";
                            echo "{";
                            echo	   "error: '".$error."'," ;
                            echo	   "msg: '".$mesg."'," ;
                            echo "}";
        }
    }

    /**
    * Saves Metadata
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  	$input
    * @param    integer  	$EntityID
    * @param    integer  	$EntityTypeID
    * @return   void
    *
    */
    
    function saveMetadata($input, $EntityID, $EntityTypeID)
    {       
       Site::myDebug($input['metaData']);
       if( $input['metaData'] != "" )
       {
            $metaDataJson  = json_decode($input['metaData']);
            if(!empty($metaDataJson) )
            {
               foreach($metaDataJson as $key=>$val)
                {
                    $value                 = (array) $val;
                    $value['KeyType']      = $key;
                    $value['EntityID']     = $EntityID;
                    $value['EntityTypeID'] = $EntityTypeID;
                    $value['CreationType'] = '-1';
                    $data                  = $this->getParsedMetadata($value);
                    $this->db->executeStoreProcedure('MetaDataManage', $data, 'nocount');
                }
            }
       }
    }
    
    /**
    * Parses Metadata
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$value
    * @return     array       $data
    *
    */
    
    function getParsedMetadata($value)
    {
        $metaDataKeyIds          = "";
        $metaDataKeys            = "";
        $metaDataDeletedKeys     = "";
        $metadaUpdatedKeys       = "";
        $metaDataInstertedValues = "";
        $metaDataDeletedValues   = "";
        $metaDataUpdatedValues   = "";
        $metaDataUpdatedValueIds = "";
        $metaDataUpdatedKeys     = "";

        if(!empty($value['Keys']))
        {
            foreach($value['Keys'] as $metaKey=>$metaVals)
            {
                $metaValue            = (array) $metaVals;
                $metaDataKeyId        = ($metaValue['KeyId']!="")?$metaValue['KeyId']:0;
                $metaDataKeyIds      .= $metaDataKeyId."|";

                $metaDataKey          = ($metaValue['KeyName']!="")?$metaValue['KeyName']:'#';
                $metaDataKeys        .= $metaDataKey."|";

                $metaDataValue        = ($metaValue['NewKeyName']!="")?$metaValue['NewKeyName']:'#';
                $metaDataUpdatedKeys .= $metaDataValue."|";

                $metaDataValue = ($metaValue['SourceID'] != "") ? $metaValue['SourceID'] : '#';
                
                $metaDataSourceEntity .= $metaDataValue . "|";

                $metaDataValue = ($metaValue['SourceTypeID'] != "") ? $metaValue['SourceTypeID'] : '#';
                
                $metaDataSourceEntityType.= $metaDataValue . "|";

                $metaDataValue            = ($metaValue['InsertedValues']!="")?$metaValue['InsertedValues']:'#';
                $metaDataInstertedValues .= $metaDataValue."|";

                $metaDataValue          = ($metaValue['DeletedValues']!="")?$metaValue['DeletedValues']:'#';
                $metaDataDeletedValues .= $metaDataValue."|";

                $metaDataValue      = ($metaValue['Status']!="")?$metaValue['Status']:'N';
                $metaDataKeysStatus.= $metaDataValue."|";

                $metaDataValue            = ($metaValue['UpdatedValueIds']!="")?$metaValue['UpdatedValueIds']:'#';
                $metaDataUpdatedValueIds .= $metaDataValue."|";

                $metaDataValue          = ($metaValue['UpdatedValues']!="")?$metaValue['UpdatedValues']:'#';
                $metaDataUpdatedValues .= $metaDataValue."|";
            }
        }
        $metaDataDeletedKeys = ($value['DeletedKeys']!="")?$value['DeletedKeys']:'-1';

        $data = array(      $value['CreationType'],
                            $value['KeyType'],
                            trim($metaDataKeyIds,'|'),
                            trim($metaDataKeys,'|'),
                            trim($metaDataKeysStatus,'|'),
                            $metaDataDeletedKeys,
                            trim($metaDataUpdatedKeys,'|'),
                            trim($metaDataSourceEntity,'|'),
                            trim($metaDataSourceEntityType,'|'),
                            trim($metaDataInstertedValues,'|'),
                            trim($metaDataDeletedValues,'|'),
                            trim($metaDataUpdatedValueIds,'|'),
                            trim($metaDataUpdatedValues,'|'),
                            $this->session->getValue('userID'),
                            $value['EntityID'],
                            $value['EntityTypeID'],
                            $this->currentDate(),
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            '#');
        return $data;
    }

     /**
    * Saves Metadata
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  	$input
    * @return   array   $metadata
    *
    */
   function metadataSave($input) {

        global $DBCONFIG;

        $metaDataKeyId = ($input['metaDataKeyId'] != "") ? $input['metaDataKeyId'] : '0';
        $metaDataKeyValues = ($input['metaDataKeyValues'] != "") ? $input['metaDataKeyValues'] : '';

        /* Added url decode to get proper esacpe charecters like &,+ etc  */
        $metaDataKeyName = rawurldecode($input['metaDataKeyName']);
        $metaDataKeyValues = rawurldecode($input['metaDataKeyValues']);
       
        $metaDataKeyValues = ($input['metaDataKeyValues'] != "") ? $input['metaDataKeyValues'] : '';


        $metaDataKeyValueDeletedList = ($input['metaDataKeyValueDeletedList'] != "") ? $input['metaDataKeyValueDeletedList'] : '';
        $isEnabled = 1;
        $data = array(
            $input['metaDataKeyType'],
            $metaDataKeyId,
            $input['metaDataKeyName'],
            $metaDataKeyValues, // addslashes($metaDataKeyValues) added addslashes as we have to add '/' for qti2.1 realize export 
            $metaDataKeyValueDeletedList,
            $this->session->getValue('userID'),
            $this->currentDate(),
            $this->session->getValue('userID'),
            $this->currentDate(),
            '#', $isEnabled);
        
        $result = $this->db->executeStoreProcedure('MetaDataManage', $data, 'nocount');
        
        Site::myDebug('----MetaDataManage------');
        Site::myDebug($result);
        
        if ($result[0]["pMetaDataKeyName"]) {
            $passResult = $result[1]["MeatadataID"];
        } else {
            $passResult = $result[0]["MeatadataID"];
        }

        
        $input['keyDetail'] = 1;
        $input['filterCond'] =-1;
        $resultList = $this->metaDataAssignedList($input,"list");
        Site::myDebug('----$resultList------');
        Site::myDebug($resultList);
        
        return $resultList['RS'];
    }
	
    function metadataSaveForPegasus($input)
    {

       global $DBCONFIG;
       Site::myDebug('----metadataSave');
       // Site::myDebug($input);
            $metaDataKeyId = ($input['metaDataKeyId']!="") ? $input['metaDataKeyId'] :'0';
            $metaDataKeyValues = ($input['metaDataKeyValues']!="") ? $input['metaDataKeyValues'] :'';
            $metaDataKeyValueDeletedList = ($input['metaDataKeyValueDeletedList']!="") ? $input['metaDataKeyValueDeletedList'] :'';
            $isEnabled = 1;
            $data = array(  
                            $input['metaDataKeyType'],
                            $metaDataKeyId,
                            $input['metaDataKeyName'],
                            $metaDataKeyValues,
                            $metaDataKeyValueDeletedList,
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            '#',$isEnabled);	
            
            $result = $this->db->executeStoreProcedure('MetaDataManageForPegasus', $data, 'nocount');
            Site::myDebug('----$resultMetaDataManage');
            Site::myDebug($input['metaDataKeyType']);
            Site::myDebug($input['metaDataKeyValues']);
            
            if($input['metaDataKeyType'] == "text_entry" && $input['metaDataKeyValues'] !="" )
            {
                Site::myDebug('----$resultMetaDataManageIF');
                return $result[0]["MapkeyValueID"];
            }else{
                Site::myDebug('----$resultMetaDataManageELSE');
                // return $this->metadataKeyDetail($result[0]["MeatadataID"]);
                $input['keyDetail']=1;
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $input['filterCond']= " AND mdk.ID = {$result[0]["MeatadataID"]} ";
                }
                else
                {
                    $input['filterCond']= " mdk.ID = {$result[0]["MeatadataID"]} ";
                }                
                $result1 = $this->metaDataAssignedList($input);
                return $result1['RS'][0];
            }
    }
	function metadataSaveForTestBuilder($input)
    {

       global $DBCONFIG;
       Site::myDebug('----metadataSave');
       // Site::myDebug($input);
            $metaDataKeyId = ($input['metaDataKeyId']!="") ? $input['metaDataKeyId'] :'0';
            $metaDataKeyValues = ($input['metaDataKeyValues']!="") ? $input['metaDataKeyValues'] :'';
            $metaDataKeyValueDeletedList = ($input['metaDataKeyValueDeletedList']!="") ? $input['metaDataKeyValueDeletedList'] :'';
            $isEnabled = 1;
            $data = array(  
                            $input['metaDataKeyType'],
                            $metaDataKeyId,
                            $input['metaDataKeyName'],
                            addslashes($metaDataKeyValues), // added addslashes as we have to add '/' for qti2.1 realize export 
                            $metaDataKeyValueDeletedList,
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            '#',$isEnabled); 
            
            $result = $this->db->executeStoreProcedure('MetaDataManageForTestBuilder', $data, 'nocount');
            Site::myDebug('----$resultMetaDataManage');
            Site::myDebug($input['metaDataKeyType']);
            Site::myDebug($input['metaDataKeyValues']);
            
            if($input['metaDataKeyType'] == "text_entry" && $input['metaDataKeyValues'] !="" )
            {
                Site::myDebug('----$resultMetaDataManageIF');
                return $result[0]["MapkeyValueID"];
            }else{
                Site::myDebug('----$resultMetaDataManageELSE');
                // return $this->metadataKeyDetail($result[0]["MeatadataID"]);
                $input['keyDetail']=1;
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $input['filterCond']= " AND mdk.ID = {$result[0]["MeatadataID"]} ";
                }
                else
                {
                    $input['filterCond']= " mdk.ID = {$result[0]["MeatadataID"]} ";
                }                
                $result1 = $this->metaDataAssignedList($input);
                return $result1['RS'][0];
            }
    }
	function metadataSaveForExamView($input)
    {

       global $DBCONFIG;
       Site::myDebug('----metadataSave');
       // Site::myDebug($input);
            $metaDataKeyId = ($input['metaDataKeyId']!="") ? $input['metaDataKeyId'] :'0';
            $metaDataKeyValues = ($input['metaDataKeyValues']!="") ? $input['metaDataKeyValues'] :'';
            $metaDataKeyValueDeletedList = ($input['metaDataKeyValueDeletedList']!="") ? $input['metaDataKeyValueDeletedList'] :'';
            $isEnabled = 1;
            $data = array(  
                            $input['metaDataKeyType'],
                            $metaDataKeyId,
                            $input['metaDataKeyName'],
                            addslashes($metaDataKeyValues), // added addslashes as we have to add '/' for qti2.1 realize export 
                            $metaDataKeyValueDeletedList,
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            $this->session->getValue('userID'),
                            $this->currentDate(),
                            '#',$isEnabled);	
            
            $result = $this->db->executeStoreProcedure('MetaDataManageForTestBuilder', $data, 'nocount');
            Site::myDebug('----$resultMetaDataManage');
            Site::myDebug($input['metaDataKeyType']);
            Site::myDebug($input['metaDataKeyValues']);
            
            if($input['metaDataKeyType'] == "text_entry" && $input['metaDataKeyValues'] !="" )
            {
                Site::myDebug('----$resultMetaDataManageIF');
                return $result[0]["MapkeyValueID"];
            }else{
                Site::myDebug('----$resultMetaDataManageELSE');
                // return $this->metadataKeyDetail($result[0]["MeatadataID"]);
                $input['keyDetail']=1;
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $input['filterCond']= " AND mdk.ID = {$result[0]["MeatadataID"]} ";
                }
                else
                {
                    $input['filterCond']= " mdk.ID = {$result[0]["MeatadataID"]} ";
                }                
                $result1 = $this->metaDataAssignedList($input);
                return $result1['RS'][0];
            }
    }
	
    function checkMetadaDataKeyName($keyName,$keyID)
    {
        global $DBCONFIG;
        $keyName = addslashes($keyName);
        
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            if($keyID != "")
            {
                $query = " SELECT * FROM MetaDataKeys mdk
                    inner join MapClientUser mcu on ( (mdk.\"UserID\" = mcu.\"UserID\"  ) AND mcu.\"ClientID\" = '{$this->session->getValue('instID')}' AND mcu.\"isEnabled\" = '1')
                        WHERE mdk.\"MetaDataName\" = '{$keyName}' and mdk.ID != '{$keyID}' AND  mdk.\"isEnabled\" = '1' ";
            }
            else
            {
                $query = " SELECT * FROM MetaDataKeys mdk
                    inner join MapClientUser mcu on ((mdk.\"UserID\" = mcu.\"UserID\"  ) AND mcu.\"ClientID\" = '{$this->session->getValue('instID')}' AND mcu.\"isEnabled\" = '1')
                        WHERE mdk.\"MetaDataName\" = '{$keyName}' AND  mdk.\"isEnabled\" = '1' ";
            }
        }
        else
        {
            if($keyID != "")
            {
                $query = " SELECT * FROM MetaDataKeys mdk
                    inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
                        WHERE mdk.MetaDataName = '{$keyName}' and mdk.ID != '{$keyID}' AND  mdk.isEnabled = '1';";
            }
            else
            {
                $query = " SELECT * FROM MetaDataKeys mdk
                    inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
                        WHERE mdk.MetaDataName = '{$keyName}' AND  mdk.isEnabled = '1';";
            }            
        }
        $cnt  = $this->db->getCount($query);   

        if($cnt > 0) 
         return ($cnt > 0 ) ? true : false;
    }
     /**
    * This function is used to delete Metadata
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array           $metaDataKeyIds
    
    * @return   void            $status
    *
    */
    function metadataDelete($metaDataKeyIds)
    {
        global $DBCONFIG;
        Site::myDebug('---------$metaDataKeyIds');
        Site::myDebug($metaDataKeyIds);
        $metaDataKeyIds  = implode(',',(array)$this->removeBlankElements($metaDataKeyIds));
        $modDate = $this->currentDate();
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = "  UPDATE MetaDataKeys SET \"isEnabled\" = '0', \"ModBY\" ={$this->session->getValue('userID')}, \"ModDate\" = '$modDate'
                                    WHERE ID IN ($metaDataKeyIds)  AND  \"UseCount\" = '0' ";
        }
        else
        {
            //$query  = "  UPDATE MetaDataKeys SET isEnabled = '0', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  WHERE ID IN ($metaDataKeyIds) AND  UseCount = '0'";
            $query  = "  UPDATE MetaDataKeys SET isEnabled = '0', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  WHERE ID IN ($metaDataKeyIds)";
        }
        
        $status = $this->db->execute($query);
        if($status)
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $qry =  " SELECT \"ValueID\" FROM MapMetaDataKeyValues  WHERE  \"KeyID\" IN($metaDataKeyIds)";
            }
            else
            {
                $qry =  " SELECT ValueID FROM MapMetaDataKeyValues  WHERE  KeyID IN($metaDataKeyIds)";
            }            
            $result = $this->db->getRows($qry);
            $result_new =$this->getValueArray($result,'ValueID','multiple');
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $qry1 = " UPDATE MapMetaDataKeyValues SET \"isEnabled\" = '0', \"ModBY\" ={$this->session->getValue('userID')},
                                \"ModDate\" = '$modDate'  WHERE \"KeyID\" IN ($metaDataKeyIds) ";
            }
            else
            {
                $qry1 = " UPDATE MapMetaDataKeyValues SET isEnabled = '0', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  WHERE KeyID IN ($metaDataKeyIds) ";
            }
            
            $this->db->execute($qry1);
            if($result_new != "")
            {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $qry2 = " UPDATE MetaDataValues Set \"isEnabled\" = '0', \"ModBY\" ={$this->session->getValue('userID')}, \"ModDate\" = '$modDate'  Where ID IN ($result_new)";
                }
                else
                {
                    $qry2 = " UPDATE MetaDataValues Set isEnabled = '0', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  Where ID IN ($result_new)";
                }                
                $this->db->execute($qry2);
            }
       }        
        return $status;

    }
        /**
    * This function is used to active/deactive Metadata
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array           $input

    * @return   void            $status
    *
    */
   function metadataStatus($input)
   {
       global $DBCONFIG;
       $id = $input["id"];
       $status = $input["status"];
       $modDate = $this->currentDate();
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = "  UPDATE MetaDataKeys SET \"isActive\" = '$status', \"ModBY\" ={$this->session->getValue('userID')}, \"ModDate\" = '$modDate'  
                                WHERE ID = {$id} AND \"isEnabled\" = '1'";
        }
        else
        {
            $query  = "  UPDATE MetaDataKeys SET isActive = '$status', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  WHERE ID = {$id} AND isEnabled = '1'";
        }
       
       $status = $this->db->execute($query);
       return $status;
       
   }
    /**
    * Get Metadata list
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     array         $meatadata
    *
    */
    
    function metadataList($input)
    {
        global $DBCONFIG;
        if($input['FilterOn'] != "" && $input['Filter'] != "" )
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                if($input['FilterOn']=='1')
                {
                    $filter = " mdk.\"MetaDataName\" LIKE ''%".$input['Filter']."%'' ";
                }
                elseif( $input['FilterOn']=='2' )
                {
                    $filter = " mdv.\"MetaDataValue\" like ''%".$input['Filter']."%'' and mdkv.ID IS NOT NULL";
                }
            }
            else
            {
                if($input['FilterOn']=='1')
                {
                    $filter = " AND  mdk.MetaDataName LIKE '%".$input['Filter']."%' ";
                }
                elseif( $input['FilterOn']=='2' )
                {
                    $filter = " AND mdv.MetaDataValue like '%".$input['Filter']."%' and mdkv.ID IS NOT NULL";
                }
            }
             
        }
        else
        {
                $filter='-1';
        }       
        $input['pgnob'] = (isset($input['pgnob'])) ? $input['pgnob'] : "-1";
        $input['pgnot'] = (isset($input['pgnot'])) ? $input['pgnot'] :"-1" ;
        $input['pgnstart'] = (isset($input['pgnstart'])) ? $input['pgnstart'] :"-1" ;
        $input['pgnstop'] = (isset($input['pgnstop'])) ? $input['pgnstop'] :"-1" ;   
        $dataArray      = Array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'], $filter, $input['EntityID'], $input['EntityTypeID'], $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), "-1");        
        $resultRows1     = $this->db->executeStoreProcedure('MetaDataList', $dataArray);
        $resultRows      = $resultRows1["RS"];
        Site::myDebug("-------metaDataList::model");
        Site::myDebug($resultRows1['RS']);
        Site::myDebug($resultRows1['TC']);

        $MetaDataTypes  = $this->getValueArray($resultRows, 'MetaDataType', 'multiple');
        $MetaDataTypes  = array_unique(explode(',', $MetaDataTypes));

        $EntityIds      = $this->getValueArray($resultRows, 'SourceID', 'multiple');
        $EntityIds      = array_filter(array_unique(explode(',', $EntityIds)));

        if((!empty($resultRows)))
        {
            foreach($resultRows as $row)
            {
                if( $row['MetaDataType'] == 'text_entry' )
                {
                    if(!empty($MetadataJson['text_entry']['Keys']))
                    {
                        array_push($MetadataJson['text_entry']['Keys'], $row);
                    }
                    else
                    {
                        $MetadataJson['text_entry']['Keys'][0]  = $row;
                    }
                }
                else if( $row['MetaDataType']=='select_list' )
                {
                    if( !empty($MetadataJson['select_list']['Keys']) )
                    {
                        array_push($MetadataJson['select_list']['Keys'], $row);
                    }
                    else
                    {
                        $MetadataJson['select_list']['Keys'][0] = $row;
                    }
                }
                $entityKey = $row['SourceID'];
                if( in_array($row['SourceID'], $EntityIds, true) )
                {
                    $inheritedMetaData[$entityKey]['EntityID']      =   $row['SourceID'];
                    $inheritedMetaData[$entityKey]['EntityName']    =   $row['EntityName'];
                    $inheritedMetaData[$entityKey]['EntityType']    =   $row['SourceTypeID'];
                    if(in_array($row['MetaDataType'], $MetaDataTypes, true))
                    {
                         if(!empty($MetadataJson[$row['MetaDataType']]))
                        {
                            array_push($MetadataJson[$row['MetaDataType']], $row);
                        }
                        else
                        {
                            $MetadataJson[$row['MetaDataType']][0] = $row;
                        }
                        if( !empty($inheritedMetaData[$entityKey]['MetaDataKeys']) )
                        {
                            array_push($inheritedMetaData[$entityKey]['MetaDataKeys'], $row);
                        }
                        else
                        {
                            $inheritedMetaData[$entityKey]['MetaDataKeys'][0] = $row;
                        }
                    }
                }
            }
            $meatadata['MetaData']    = $inheritedMetaData;
            $MetadataJson             = $this->parseValues($MetadataJson, 'View');
            $meatadata['Json']        = json_encode($MetadataJson);
            $meatadata['DisplayJson'] = json_encode($resultRows);
            $meatadata['result'] = $resultRows;
            $meatadata['TC']          = $resultRows1['TC'];
        }
        return $meatadata;
    }

    /**
    * Format Metadata array
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$metaData
    * @param      string  	$parseType
    * @return     array       $newMetaData
    *
    */
    
    function parseValues($metaData, $parseType)
    {
        $newMetaData = $metaData;
        if(!empty($metaData))
        {
            foreach($metaData as $keyType=>$data)
            {
                $arrangeData = array();
                if(!empty($data['Keys']))
                {
                    foreach($data['Keys'] as $key=>$value)
                    {
                        $arrangeData[$key]['KeyId']         = $value['ID'];
                        $arrangeData[$key]['KeyName']       = $value['KeyName'];
                        $arrangeData[$key]['Status']        = ($value['Status']!="")?$value['Status']:'Y';
                        $arrangeData[$key]['CreationType']  = ($value['CreationType']!="")?$value['CreationType']:'Owned';
                        $arrangeData[$key]['SourceID']      = ($value['SourceID']!="")?$value['SourceID']:0;
                        $arrangeData[$key]['SourceTypeID']  = ($value['SourceTypeID']!="")?$value['SourceTypeID']:0;
                        if( key_exists('KeyValues', $value) )
                        {
                            if( $value['KeyValues']!="" && (preg_match('/\|/', $value['KeyValues'])>0) )
                            {
                                $valArr             = explode(",",$value['KeyValues']);
                                $arrangeDataMapIds  = "";
                                $arrangeDataNames   = "";
                                $arrangeDataValIds  = "";
                                $vals               = array();
                                if(!empty($valArr))
                                {
                                    foreach($valArr as $val)
                                    {
                                        list($mapId, $valId, $name, $usgCnt) = explode("|", $val);
                                        $arrangeDataMapIds                  .= $mapId.",";
                                        $arrangeDataValIds                  .= $valId.",";
                                        $arrangeDataNames                   .= $name.",";
                                        $row                                 = array('ValName'=>$name, 'UsgCnt'=>$usgCnt);
                                        array_push($vals, $row);
                                    }
                                }
                                $arrangeData[$key]['MapIds']    = rtrim($arrangeDataMapIds, ',');
                                $arrangeData[$key]['ValueIds']  = rtrim($arrangeDataValIds, ',');
                                $arrangeData[$key]['SubData']   = $vals;
                            }
                            if( $parseType == 'Inherit' )
                            {
                                $arrangeData[$key]['InsertedValues']    = $value['KeyValues'];
                            }
                            else
                            {
                                $arrangeData[$key]['AllValues']         = $value['KeyValues'];
                            }
                        }
                    }
                }
                $newMetaData[$keyType]['Keys'] = $arrangeData;
            }
         }   
        return $newMetaData;
    }
    
    /**
    * Get Question Metadata List
    *
    * @access     public
    * @abstract
    * @static
    * @global     array       $CONFIG
    * @global     array       $APPCONFIG
    * @global     array       $DBCONFIG
    * @param      integer  	$QuestionID
    * @param      integer  	$RepID
    * @param      integer  	$EntityID
    * @param      integer  	$EntityTypeID
    * @return     array       $metadata
    *
    */
    
    function questionMetadataList($QuestionID, $RepID, $EntityID, $EntityTypeID)
    {
        global $CONFIG,$APPCONFIG,$DBCONFIG;
        
        $dataArray      = Array("-1", "-1", "-1", "-1", "-1", $QuestionID, $RepID, $EntityID, $EntityTypeID, $this->session->getValue('userID') );
        $resultRows     = $this->db->executeStoreProcedure('QuestionMetaDataList',$dataArray, 'nocount');
        Site::myDebug($resultRows);        

        // $questmetadata = $result['RS'];
        $MetaDataTypes = $this->getValueArray($resultRows, 'MetaDataType','multiple');
        $MetaDataTypes = array_unique(explode(',', $MetaDataTypes));

        $EntityIds     = $this->getValueArray($resultRows, 'SourceID', 'multiple');
        $EntityIds     = array_filter(array_unique(explode(',', $EntityIds)));

        if((!empty($resultRows)))
        {
            foreach($resultRows as $row)
            {
                if($row['MetaDataType']=='text_entry')
                {
                    if(!empty($MetadataJson['text_entry']['Keys']))
                    {
                        array_push($MetadataJson['text_entry']['Keys'], $row);
                    }
                    else
                    {
                        $MetadataJson['text_entry']['Keys'][0] = $row;
                    }
                }
                else if( $row['MetaDataType'] == 'select_list' )
                {
                    if(!empty($MetadataJson['select_list']['Keys']))
                    {
                        array_push($MetadataJson['select_list']['Keys'], $row);
                    }
                    else
                    {
                        $MetadataJson['select_list']['Keys'][0] = $row;
                    }
                }
                $entityKey = $row['SourceID'];
                if( in_array($row['SourceID'], $EntityIds, true) )
                {
                    $inheritedMetaData[$entityKey]['EntityID']   = $row['SourceID'];
                    $inheritedMetaData[$entityKey]['EntityName'] = $row['EntityName'];
                    $inheritedMetaData[$entityKey]['EntityType'] = $row['SourceTypeID'];
                    if( in_array($row['MetaDataType'], $MetaDataTypes, true) )
                    {
                        if( !empty($MetadataJson[$row['MetaDataType']]) )
                        {
                            array_push($MetadataJson[$row['MetaDataType']], $row);
                        }
                        else
                        {
                            $MetadataJson[$row['MetaDataType']][0] = $row;
                        }
                        if( !empty($inheritedMetaData[$entityKey]['MetaDataKeys']) )
                        {
                            array_push($inheritedMetaData[$entityKey]['MetaDataKeys'], $row);
                        }
                        else
                        {
                            $inheritedMetaData[$entityKey]['MetaDataKeys'][0] = $row;
                        }
                    }
                }
            }
            
            $metadata['MetaData']    = $inheritedMetaData;
            $MetadataJson            = $this->parseValuesQuestionMeta($MetadataJson);
            $metadata['Json']        = json_encode($MetadataJson);
            $metadata['DisplayJson'] = json_encode($resultRows);
        }
        return $metadata;
    }

    /**
    * Format Question Metadata List
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$metaData
    * @return     array       $newMetaData
    *
    */
    
    function parseValuesQuestionMeta($metaData)
    {
        $newMetaData = $metaData;
        if(!empty($metaData))
        {
            foreach($metaData as $keyType=>$data)
            {
                $arrangeData = array();
                if(!empty ($data['Keys']))
                    {
                        foreach($data['Keys'] as $key=>$value)
                        {
                            $arrangeData[$key]['KeyId']         = $value['mdkID'];
                            $arrangeData[$key]['mdkID']         = $value['mdkID'];
                            $arrangeData[$key]['SourceID']      = $value['SourceID'];
                            $arrangeData[$key]['SourceTypeID']  = $value['SourceTypeID'];
                            $arrangeData[$key]['KeyName']       = $value['KeyName'];
                            $arrangeData[$key]['Status']        = ($value['Status']!="")?$value['Status']:'Y';
                            $arrangeData[$key]['CreationType']  = ($value['CreationType']!="")?$value['CreationType']:'Owned';
                            $arrangeData[$key]['SourceID']      = ($value['SourceID']!="")?$value['SourceID']:0;
                            $arrangeData[$key]['SourceTypeID']  = ($value['SourceTypeID']!="")?$value['SourceTypeID']:0;
                            if( key_exists('KeyValues', $value) )
                            {
                                if($value['KeyValues']!="" && (preg_match('/\|/',$value['KeyValues'])>0))
                                {
                                    $valArr             = explode(",",$value['KeyValues']);
                                    $arrangeDataMapIds  = "";
                                    $arrangeDataNames   = "";
                                    $arrangeDataValIds  = "";
                                    $vals               = array();
                                    if(!empty($valArr))
                                    {
                                        foreach($valArr as $val)
                                        {
                                            list($mapId,$valId, $valId2, $name, $usgCnt) = explode("|",$val);
                                            $arrangeDataMapIds                          .= $mapId.",";
                                            $arrangeDataValIds                          .= $valId.",";
                                            $arrangeDataNames                           .= $name.",";
                                            $row                                         = array('ValName'=>$name, 'UsgCnt'=>$usgCnt);
                                            array_push($vals, $row);
                                        }
                                    }
                                    $arrangeData[$key]['MapIds']    = rtrim($arrangeDataMapIds,',');
                                    $arrangeData[$key]['ValueIds']  = rtrim($arrangeDataValIds,',');
                                    $arrangeData[$key]['SubData']   = $vals;
                                }
                                $arrangeData[$key]['AllValues'] = $value['KeyValues'];
                            }
                        }
                    }
                    $newMetaData[$keyType]['Keys'] = $arrangeData;
                }
            }
        return $newMetaData;
    }

    /**
    * Get Question Metadata Inherit List
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     array       $metadata
    *  
    */
    
    function questionMetadataInheritList(array $input)
    {        
        $metadata             = array();
        $input['AllQuestIds'] = $input['QuestionID']!=""?$input['QuestionID']:"-1";
        $QuestIds             = explode(',', $input['AllQuestIds']);

        // For Display JSON Getting Inherited Keys and Values with there respective EntityID and EntityTypeID
        $dataArray = array( "mdkv.EntityID,mdk.ID",
                            "-1", "-1", "-1", "-1", "-1",
                            $input['AllQuestIds'],
                            "3",
                            $input['EntityID'],
                            $input['EntityTypeID'],
                            $this->session->getValue('userID'),
                            $this->session->getValue('isAdmin'),
                            $this->session->getValue('instID'),
                            " qtn.ID as QuestionID , qtn.Title, group_concat( concat( mdv.ID,'|', mdv.MetaDataValue,'|',mdv.UseCount,'|',mdv.AddDate  )SEPARATOR ',') as KeyValues, mdk.ID AS mdkID, mdk.AddDate as CreateDate, mdk.\"MetaDataName\" as KeyName, mdk.MetaDataType, mdk.CreationType as CreateType , mdk.UseCount "
                          );
        $resultRows = $this->db->executeStoreProcedure('InheritMetaDataList', $dataArray, 'nocount');
        $resultRows = $this->arrayMsort( $resultRows, array('QuestionID'=> array(SORT_DESC, SORT_REGULAR), 'SourceEntityID'=>SORT_ASC) );
        
        if ($resultRows)
        {
            $arrResultData  = array();
            $keyTitle       = "";
            $arrData        = array();
            foreach ($resultRows as $key => $data)
            {
                if ( !empty( $arrResultData[$data["Title"]] )  )
                {
                    array_push($arrResultData[$data["Title"]], $data);
                }
                else
                {
                    $arrResultData[$data["Title"]][0] = $data;
                }                
            }            
        }        
        $arrFinalResultData = array();
        if(!empty($arrResultData))
        {
            foreach ($arrResultData as $keyResult => $dataResult )
            {
                if(!empty($dataResult))
                {
                    foreach ( $dataResult  as $key => $data )
                    {
                        if ( !empty( $arrFinalResultData[$keyResult][$data["SourceEntityName"]] )  )
                        {
                            array_push($arrFinalResultData[$keyResult][$data["SourceEntityName"]], $data);
                        }
                        else
                        {
                            $arrFinalResultData[$keyResult][$data["SourceEntityName"]][0] = $data;
                        }
                    }
                }
            }
        }       
        $metadata['arrFinalResultData'] = $arrFinalResultData;
        return $metadata;
    } 
    
    /**
    * Adds Inherit Metadata List
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     void
    *
    */
    
    function inheritMetadata(array $input)
    {
        $this->mydebug("---------inheritMetaData::model");
        $this->mydebug($input);
        
        $input['AllQuestIds']       = $input['AllQuestIds']!=""?$input['AllQuestIds']:"-1";
        $input['RepositoryIds']     = $input['RepositoryIds']!=""?$input['RepositoryIds']:"-1";
        $input['CreationType']      = 'Inherited';
        $input['strInheritedMetaDataValues'] = ( trim($input['strInheritedMetaDataValues']) ) ? trim($input['strInheritedMetaDataValues'], ",") : "-1";
        $input['strTmpUnSelectedQuestID']    = ( trim($input['strTmpUnSelectedQuestID']) ) ? trim($input['strTmpUnSelectedQuestID'], ",") : "-1";
        
        
        //Get Repository with respective question ID.
        $QuestIds       = explode(',', $input['AllQuestIds']);
        $RepositoryIds  = explode(',', $input['RepositoryIds']);
        if(!empty($QuestIds))
        {
            foreach($QuestIds as $index=>$Quest)
            {
                $repositryData[$Quest] = $RepositoryIds[$index];
            }
        }
        //Getting Inherited Keys and Values
        $dataArray = array("mdk.ID","-1","-1","-1","-1","-1",
                                $input['AllQuestIds'],"3",
                                $input['EntityID'],
                                $input['EntityTypeID'],
                                $this->session->getValue('userID'),
                                $this->session->getValue('isAdmin'),
                                $this->session->getValue('instID'),
                                "group_concat(distinct mdv.MetaDataValue SEPARATOR ',') as KeyValues",
                                $input['strInheritedMetaDataValues'],
                                $input['strTmpUnSelectedQuestID']  
                            ); 
        $resultKeys = $this->db->executeStoreProcedure('InheritMetaData', $dataArray, 'nocount');

        $this->mydebug("---------resultKeys");
        $this->mydebug($resultKeys);
        
        if(!empty($resultKeys))
        {
            foreach($resultKeys as $row)
            {
                if( $row['KeyType'] == 'text_entry' )
                {
                    if(!empty($InheritedMetadata['text_entry']['Keys']))
                    {
                        array_push($InheritedMetadata['text_entry']['Keys'], $row);
                    }
                    else
                    {
                        $InheritedMetadata['text_entry']['Keys'][0] = $row;
                    }
                }
                else if($row['KeyType']=='select_list')
                {
                    if(!empty($InheritedMetadata['select_list']['Keys']))
                    {
                        array_push($InheritedMetadata['select_list']['Keys'], $row);
                    }
                    else
                    {
                        $InheritedMetadata['select_list']['Keys'][0] = $row;
                    }
                }
            }
        }        
        
        //Storing Inherited Keys and Values
        $MetadataJson   = $this->parseValues($InheritedMetadata, 'Inherit');
        $KeyValData     = array();

        $this->mydebug("MEdatadata JSon");
        $this->mydebug($MetadataJson);

        if(!empty($MetadataJson))
        {
            foreach($MetadataJson as $key=>$val)
            {
                $value                  = (array) $val;
                $value['KeyType']       = $key;
                $value['EntityID']      = $input['EntityID'];
                $value['EntityTypeID']  = $input['EntityTypeID'];
                $value['CreationType']  = $input['CreationType'];
                $data                   = $this->getParsedMetadata($value);
                $result                 = $this->db->executeStoreProcedure('MetaDataManage', $data, 'nocount');   // Key and Value Creation

                $KeyNames               = explode('|',$this->getValueArray($result, 'KeyNames'));
                $KeyIds                 = explode('|',$this->getValueArray($result, 'KeyIds'));
                $ValueIds               = explode('|',$this->getValueArray($result, 'ValueIds'));
                $valueStr               = explode('|',$this->getValueArray($result, 'ValueStr'));

               if(!empty($KeyNames))
               {
                   foreach($KeyNames as $index=>$KeyName)
                   {
                        $KeyValData[$KeyName]['KeyId'] = $KeyIds[$index];
                        if( $ValueIds[$index] != "" )
                        {
                             $ValIds    = explode(',',$ValueIds[$index]);
                             $valStr    = explode(',',$valueStr[$index]);
                             if(!empty($ValIds))
                             {
                                 foreach($ValIds as $valKey=>$valId)
                                 {
                                    $KeyValData[$KeyName]['Values'][$valStr[$valKey]] = $ValIds[$valKey];
                                 }
                             }
                        }
                        else
                        {
                                $KeyValData[$KeyName]['Values'] = $valueStr[$index];
                        }
                        $KeyValData[$KeyName]['ValueId'] = $ValueIds[$index];
                        $KeyValData[$KeyName]['KeyType'] = $key;
                   }
               }
            }
            $this->mydebug("This is values");
            $this->mydebug($KeyValData);
            //Getting Inherited Keys and Values with there respective EntityID and EntityTypeID
            $dataArray  = array("mdkv.EntityID,mdk.ID", "-1", "-1", "-1", "-1", "-1",
                                $input['AllQuestIds'], "3",
                                $input['EntityID'],
                                $input['EntityTypeID'],
                                $this->session->getValue('userID'),
                                $this->session->getValue('isAdmin'),
                                $this->session->getValue('instID'),
                                "mrq.QuestionID,mdkv.EntityID, group_concat(distinct mdv.MetaDataValue SEPARATOR ',') as ValueStr,group_concat(distinct mdv.ID SEPARATOR ',') as ValueID",
                                $input['strInheritedMetaDataValues'], 
                                $input['strTmpUnSelectedQuestID']  
                            );
            $resultRows = $this->db->executeStoreProcedure('InheritMetaData', $dataArray, 'nocount');  

            if(!empty($resultRows))
            {
                foreach($resultRows as $QKey=>$Quest)
                {
                    if( $Quest['KeyType'] == $KeyValData[$Quest['KeyName']]['KeyType'] )
                    {
                        $resultRows[$QKey]['KeyId'] = $KeyValData[$Quest['KeyName']]['KeyId'];
                        $AssignVals                 = explode(',', $Quest['ValueStr']);
                        if(is_array($KeyValData[$Quest['KeyName']]['Values']))
                        {
                            $newValIds = array();
                            if(!empty($AssignVals))
                            {
                                foreach($AssignVals as $valStr)
                                {
                                    array_push($newValIds, $KeyValData[$Quest['KeyName']]['Values'][$valStr]);
                                }
                            }
                            $resultRows[$QKey]['ValueID']   = implode(',', $newValIds);
                       }
                       else
                       {
                            $resultRows[$QKey]['ValueStr']  = $Quest['ValueStr'];
                            $resultRows[$QKey]['ValueID']   = '#';
                       }
                       unset($resultRows[$QKey]['QuestionID']);
                       unset($resultRows[$QKey]['CreationType']);
                       unset($resultRows[$QKey]['KeyName']);
                    }
                    $resultRows[$QKey]['EntityID'] = $repositryData[$Quest['QuestionID']];
                    //Start Diffrentiate as text_entry and select_list
                    if( $resultRows[$QKey]['KeyType'] == 'text_entry' )
                    {
                        unset ($resultRows[$QKey]['KeyType']);
                        if(!empty($RepositoryJson['text_entry']))
                        {
                            array_push($RepositoryJson['text_entry'], $resultRows[$QKey]);
                        }
                        else
                        {
                            $RepositoryJson['text_entry'][0] = $resultRows[$QKey];
                        }
                    }
                    else if( $resultRows[$QKey]['KeyType'] == 'select_list' )
                    {
                        unset($resultRows[$QKey]['KeyType']);
                        if(!empty($RepositoryJson['select_list']))
                        {
                            array_push($RepositoryJson['select_list'], $resultRows[$QKey]);
                        }
                        else
                        {
                            $RepositoryJson['select_list'][0] = $resultRows[$QKey];
                        }
                    }
                    //End Diffrentiate as text_entry and select_list
                }
                $this->mydebug("Keys");
                $this->mydebug($RepositoryJson);
                
                //Storing Inherited Keys and Values mapping for eachc repository
                $Keys = array_keys($resultRows[0]);
                $this->mydebug("Keys");
                $this->mydebug($Keys);
                if(!empty($RepositoryJson))
                {
                    foreach($RepositoryJson as $Key=>$MetaDataMaps)
                    {
                        if(!empty($MetaDataMaps))
                        {
                            foreach($MetaDataMaps as $Val)
                            {
                                if(!empty($Keys))
                                {
                                    foreach($Keys as $arrKey)
                                    {
                                        $Val[$arrKey]             = ($Val[$arrKey]!="")?$Val[$arrKey]:'#';
                                        $JsonData[$Key][$arrKey] .= $Val[$arrKey]."|";
                                    }
                                }
                            }
                        }
                    }
                }
                $this->mydebug("Json Data");
                $this->mydebug($JsonData);
                if(!empty($JsonData))
                {
                    foreach($JsonData as $Key=>$Val)
                    {
                        $data   =   array($Key, trim($Val['EntityID'], "|"), trim($Val['KeyId'], "|"), trim($Val['ValueID'], "|"), trim($Val['ValueStr'], "|"), trim($Val['SourceID'], "|"), trim($Val['SourceTypeID'], "|"), $this->session->getValue('userID'), $this->currentDate(), $this->session->getValue('userID'), $this->currentDate(), '#');
                        $this->db->executeStoreProcedure('InheritMetaDataManage', $data, 'nocount');  //  Question Meta key & Value Assignment
                    }
                }
            }
        }
    }

    /**
    * Adds Metadata to Question
    *
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     void
    *
    */
    
    public function manageQuestionMetadata($input)
    {
        Site::myDebug("-----manageQuestionMetaData");
        
        Site::myDebug($input);
        $strRepositoryID                = $input['strRepositoryID'];
        $strAllKeys                     = $input['strAllKeys'];
        $strAllKeyValues                = $input['strAllKeyValues'];
        $strSelectedKeys                = $input['strSelectedKeys'];
        $strSelectedKeyValues           = $input['strSelectedKeyValues'];
        $strUnSelectedKeys              = $input['strUnSelectedKeys'];
        $strUnSelectedKeyValues         = $input['strUnSelectedKeyValues'];        

        // From RepositoryID Get its Entity and EntityTypeID
        $getRepInfo = $this->db->getSingleRow("SELECT EntityID, EntityTypeID FROM MapRepositoryQuestions WHERE ID= '$strRepositoryID' and isEnabled = '1' ");
        if ( $getRepInfo )
        {
            $EntityID       = $getRepInfo['EntityID'];
            $EntityTypeID   = $getRepInfo['EntityTypeID'];
        }
        
        // All Keys And Values 
        if ($strAllKeys)
        {   
            $strAllKeys = str_replace("||", "|", $strAllKeys);           
            $strAllKeys = trim($strAllKeys, "|" );            
        }
        if ($strAllKeyValues)
        {
            $strAllKeyValues = str_replace(",|", "|", $strAllKeyValues);
            $strAllKeyValues = str_replace("||", "|", $strAllKeyValues);
            $strAllKeyValues = trim($strAllKeyValues, "|" );                    
        }

        // SELECTED Keys And Values
        if ($strSelectedKeys)
        {
            $strSelectedKeys = str_replace("||", "|", $strSelectedKeys);
            $strSelectedKeys = trim($strSelectedKeys, "|" );
        }
        if ($strSelectedKeyValues)
        {
            $strSelectedKeyValues = str_replace(",|", "|", $strSelectedKeyValues);
            $strSelectedKeyValues = str_replace("||", "|", $strSelectedKeyValues);
            $strSelectedKeyValues = trim($strSelectedKeyValues, "|" );
        }

        // UNSELECTED Keys And Values
        if ($strUnSelectedKeys)
        {
            $strUnSelectedKeys = str_replace("||", "|", $strUnSelectedKeys);
            $strUnSelectedKeys = trim($strUnSelectedKeys, "|" );
        }
        if ($strUnSelectedKeyValues)
        {
            $strUnSelectedKeyValues = str_replace(",|", "|", $strUnSelectedKeyValues);
            $strUnSelectedKeyValues = str_replace("||", "|", $strUnSelectedKeyValues);
            $strUnSelectedKeyValues = trim($strUnSelectedKeyValues, "|" );
        }
        
        $this->db->executeStoreProcedure('MapQuestionMetaDataManage',
                                                array(  $strAllKeys,
                                                        $strSelectedKeyValues,
                                                        $strUnSelectedKeyValues, 
                                                        $input['strRepositoryID'],
                                                        '3',
                                                        $input['strRepositoryID'],
                                                        '3',
                                                        $this->session->getValue('userID'),
                                                        $this->currentDate(),
                                                        $this->session->getValue('userID'),
                                                        $this->currentDate(), '#'), 'nocount');
    }

    /**
    * Get Metadata as per Search criteria
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     array
    *
    */
    
    function getSearchResult(array $input)
    {
        return $this->db->executeStoreProcedure('MetaDataSearch',
                                                    array(  $input['pgnob'],
                                                            $input['pgnot'],
                                                            $input['pgnstart'],
                                                            $input['pgnstop'],
                                                            $this->session->getValue('userID'),
                                                            $this->session->getValue('instID'),
                                                            trim( $input['entityid'], ","),
                                                            $input['entitytypeid'],
                                                            $input['search'],
                                                            $input['searchtype'], '-1', '-1') );
    }

    /**
    * Customized Multiple Sort Array
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$array
    * @param      array  	$cols
    * @return     array       $ret
    *
    */
    
    function arrayMsort($array, $cols)
    {
        $colarr = array();
        if(!empty($cols)){
            foreach ($cols as $col => $order)
            {
                $colarr[$col] = array();
                if(!empty($array)){
                    foreach ($array as $k => $row)
                    {
                        $colarr[$col]['_'.$k] = strtolower($row[$col]);
                    }
                }
            }
        }
        $params = array();
        if(!empty($cols)){
            foreach ($cols as $col => $order)
            {
                $params[]   =& $colarr[$col];
                $params     = array_merge($params, (array)$order);
            }
        }
        call_user_func_array('array_multisort', $params);
        $ret    = array();
        $keys   = array();
        $first  = true;
        if(!empty($colarr)){
            foreach ($colarr as $col => $arr)
            {
                if(!empty($arr)){
                    foreach ($arr as $k => $v)
                    {
                        if ($first)
                        {
                            $keys[$k]   = substr($k, 1);
                        }
                        $k = $keys[$k];
                        if (!isset($ret[$k]))
                        {
                            $ret[$k]    = $array[$k];
                        }
                        $ret[$k][$col]  = $array[$k][$col];
                    }
                }
                $first = false;
            }
        }
        return $ret;
    }

    /**
    * Convert Array to XML
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$array
    * @param      integer  	$level
    * @return     string(XML) $xml
    *
    */
    
    function arrayToXml($array, $level=1)
    {
        $xml = '';
        if(!empty($array))
        {
            foreach ($array as $key=>$value)
            {
                $key = strtolower($key);
                if (is_object($value))
                {
                    $value = get_object_vars($value);
                }// convert object to array

                if (is_array($value))
                {
                    $multi_tags = false;
                    foreach($value as $key2=>$value2)
                    {
                        if (is_object($value2))
                        {
                            $value2 = get_object_vars($value2);
                        } // convert object to array

                        if (is_array($value2))
                        {
                            $xml        .= str_repeat("\t",$level)."<$key>\n";
                            $xml        .= $this->arrayToXml($value2, $level+1);
                            $xml        .= str_repeat("\t",$level)."</$key>\n";
                            $multi_tags  = true;
                        }
                        else
                        {
                            if (trim($value2)!='')
                            {
                                if ( htmlspecialchars($value2)!=$value2 )
                                {
                                    $xml .= str_repeat("\t",$level).
                                            "<$key2><![CDATA[$value2]]>". // changed $key to $key2... didn't work otherwise.
                                            "</$key2>\n";
                                }
                                else
                                {
                                    $xml .= str_repeat("\t",$level).
                                            "<$key2>$value2</$key2>\n";  // changed $key to $key2
                                }
                            }
                            $multi_tags = true;
                        }
                    }
                    if (!$multi_tags and count($value)>0)
                    {
                        $xml .= str_repeat("\t",$level)."<$key>\n";
                        $xml .= $this->arrayToXml($value, $level+1);
                        $xml .= str_repeat("\t",$level)."</$key>\n";
                    }
                }
                else
                {
                    if (trim($value)!='')
                    {
                        echo "value=$value<br>";
                        if (htmlspecialchars($value)!=$value)
                        {
                            $xml .= str_repeat("\t", $level)."<$key>".
                                    "<![CDATA[$value]]></$key>\n";
                        }
                        else
                        {
                            $xml .= str_repeat("\t", $level).
                                    "<$key>$value</$key>\n";
                        }
                    }
                }
            }
        }
        return $xml;
    }

    function metaDataAssignedList($input,$diplaytype = "list")
    {
        global $DBCONFIG;
        Site::myDebug('----------metadataListModel');
        Site::myDebug($input);
        $EntityID = $input['EntityID'];
        $EntityTypeID = $input['EntityTypeID'];
        $filterOn = $input['SearchOn'];
        $filterStr = $input['metaDataSearch'];

        if($filterOn !="" && $filterStr !="")
        {
             if($filterOn =='1')
             {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $filter = " AND LOWER(mdk.\"MetaDataName\") like LOWER(''%".$filterStr."%'') ";
                }
                else
                {
                    $filter = "  mdk.MetaDataName like '%".$filterStr."%'";
                }

                 
             }
             elseif( $filterOn =='2' )
             {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                        $filter = " AND LOWER(mdv.\"MetaDataValue\") like LOWER(''%".$filterStr."%'') and mdkv.\"ID\" IS NOT NULL";
                }
                else
                {
                        $filter = "  mdv.MetaDataValue like '%".$filterStr."%' and mdkv.ID IS NOT NULL";
                }
                 
             }
             elseif( $filterOn =='3' )
             {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $filter = " AND LOWER(mdk.\"MetaDataName\") like LOWER(''%".$filterStr."%'') or LOWER(mdv.\"MetaDataValue\") like LOWER(''%".$filterStr."%'')
                                        and mdkv.\"ID\" IS NOT NULL";
                }
                else
                {
                    $filter = " mdk.MetaDataName like '%".$filterStr."%' or mdv.MetaDataValue like '%".$filterStr."%' and mdkv.ID IS NOT NULL";
                }
                 
             }
        }
        else if ( $input['hdn_searchcrieteria'] != '' )
        {
            Site::myDebug('----------hdn_searchcrieteria');
            $input['jsoncrieteria'] = urldecode($input['hdn_searchcrieteria']);
            $json                   = json_decode(stripslashes($input['jsoncrieteria']));
            Site::myDebug($json);
            $searchtype             = ($input['hdn_searchcrieteria'] != '') ? 'advanced':'basic';

            $metadatakey            = ($json->keyinfo->metadatakey->val != '')?$json->keyinfo->metadatakey->val: '-1';
            $metadatavalue          = ($json->keyinfo->metadatavalue->val != '')?$json->keyinfo->metadatavalue->val: '-1';
            $input['ownerName']     = ($input['ownerName']== '')? -1 : $input['ownerName'];
            // $input['pgndc']         = ($input['pgndc'] == '-1')?'qst.Count':$input['pgndc'].',qst.Count';

            $owner      = ($json->keyinfo->users->id != '')?($json->keyinfo->users->id):'-1';
            $startdate  = ($json->keyinfo->date->start != '')?($json->keyinfo->date->start):'-1';
            $enddate    = ($json->keyinfo->date->end != '')?($json->keyinfo->date->end):'-1';
            $mincount   = ($json->keyinfo->usagecount->minusagecount != '')?($json->keyinfo->usagecount->minusagecount ):'0';
            $maxcount   = ($json->keyinfo->usagecount->maxusagecount != '')?($json->keyinfo->usagecount->maxusagecount):'-1';

            $metadatakey_filter     = ($json->keyinfo->metadatakey->filtertype == 'exclude')?'exclude': 'include';
            $metadatavalue_filter   = ($json->keyinfo->metadatavalue->filtertype == 'exclude')?'exclude': 'include';
            $users_filter           = ($json->keyinfo->users->filtertype == 'exclude')?'exclude': 'include';
            $usagecount_filter      = ($json->keyinfo->usagecount->filtertype == 'exclude')?'exclude': 'include';
            $date_filter            = ($json->keyinfo->date->filtertype == 'exclude')?'exclude': 'include';


            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $filter = " ";
            }
            else
            {
                $filter = " 1 ";
            }
            if( $metadatakey != "-1")
            {
                if($metadatakey_filter == 'exclude')
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .=  "    AND LOWER(mdk.\"MetaDataName\") NOT LIKE LOWER(''%{$metadatakey}%'')  ";
                    }
                    else
                    {
                        $filter .=  " AND   mdk.MetaDataName NOT LIKE '%{$metadatakey}%'  ";
                    }
                    
                }
                else
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .=  "  AND  LOWER( mdk.\"MetaDataName\" )  LIKE LOWER( ''%{$metadatakey}%'' )  ";
                    }
                    else
                    {
                        $filter .=  "   AND mdk.MetaDataName LIKE '%{$metadatakey}%'  ";
                    }
                }
            }
            if( $metadatavalue != "-1")
            {
                if($metadatavalue_filter == 'exclude')
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .=  " AND ( LOWER( mdv.\"MetaDataValue\" )  NOT LIKE LOWER( ''%{$metadatavalue}%'' )  ) ";
                    }
                    else
                    {
                        $filter .=  " AND ( mdv.MetaDataValue NOT LIKE '%{$metadatavalue}%' ) ";
                    }
                }
                else
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .=  " AND ( LOWER( mdv.\"MetaDataValue\" ) LIKE LOWER( ''%{$metadatavalue}%'' ) AND mdkv.\"ID\" IS NOT NULL ) ";
                    }
                    else
                    {
                        $filter .=  " AND ( mdv.MetaDataValue LIKE '%{$metadatavalue}%' AND mdkv.ID IS NOT NULL ) ";
                    }
                    
                }
            }

            if( $mincount >= 0 &&  $maxcount >= 0 )
            {
                if($usagecount_filter == 'exclude')
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $searchCond .= " AND ( mdk.\"UseCount\" < {$mincount} ";
                        $filter .= " OR mdk.\"UseCount\" > {$maxcount} ) ";
                    }
                    else
                    {
                        $searchCond .= " AND ( mdk.UseCount < {$mincount} ";
                        $filter .= " OR mdk.UseCount > {$maxcount} ) ";
                    }                    
                }
                else
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .= " AND mdk.\"UseCount\" >= {$mincount} ";
                        $filter .= " AND mdk.\"UseCount\" <= {$maxcount} ";
                    }
                    else
                    {
                        $filter .= " AND mdk.UseCount >= {$mincount} ";
                        $filter .= " AND mdk.UseCount <= {$maxcount} ";
                    }
                    
                }
            }

            if ( $owner != "-1" )
            {
                if($users_filter == 'exclude')
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .= " AND mdk.\"UserID\" NOT IN ($owner)  ";
                    }
                    else
                    {
                        $filter .= " AND mdk.UserID NOT IN ($owner)  ";
                    }
                }
                else
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .= " AND mdk.\"UserID\" IN ($owner)  ";
                    }
                    else
                    {
                        $filter .= " AND mdk.UserID IN ($owner)  ";
                    }
                    
                }
            }

            if ( $startdate != "-1" )
            {
                if($date_filter == 'exclude')
                {
                        if ( $DBCONFIG->dbType == 'Oracle' )
                        {
                            // $filter .= " AND ( date_format(mdk.AddDate,'%m-%d-%Y' ) < '{$startdate}'   ";
                            $filter .= " AND TO_CHAR(mdk.\"AddDate\", ''MM-DD-YYYY'')    < ''{$startdate}''    ";
                        }
                        else
                        {
                            $filter .= " AND ( date_format(mdk.AddDate,'%m-%d-%Y' ) < '{$startdate}'   ";
                        }
                        
                }
                else
                {
                        if ( $DBCONFIG->dbType == 'Oracle' )
                        {
                            $filter .= " AND TO_CHAR(mdk.\"AddDate\", ''MM-DD-YYYY'')  >= ''{$startdate}''   ";
                        }
                        else
                        {
                            $filter .= " AND date_format(mdk.AddDate, '%m-%d-%Y' ) >= '{$startdate}'   ";
                        }
                        
                }
            }

            if ( $enddate != "-1" )
            {
                if($date_filter == 'exclude')
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .= "  OR TO_CHAR(mdk.\"AddDate\", ''MM-DD-YYYY'')  > ''{$enddate}''     "; 
                    }
                    else
                    {
                        $filter .= " OR date_format(mdk.ModDate,'%m-%d-%Y' ) > '{$enddate}'     ";
                    }
                        
                }
                else
                {
                    if ( $DBCONFIG->dbType == 'Oracle' )
                    {
                        $filter .= " AND TO_CHAR(mdk.\"AddDate\", ''MM-DD-YYYY'')  <= ''{$this->getFormatDate($enddate)}''     ";
                    }
                    else
                    {
                        $filter .= " AND date_format(mdk.ModDate,'%m-%d-%Y' ) <= '{$enddate}'     ";
                    }
                }
            }
        }
        else if($input['keyDetail'] == 1)
        {
            $filter= $input['filterCond'];
        }
        else
        {
                $filter='-1';
        }
                
        if(!empty($input['ignoredIDs'])){
            $filterIds  = $input['ignoredIDs'];
        }else{
            $filterIds="-1";
        }

        if($diplaytype == "assign"){
             $input['pgnob'] = $input['pgnot'] = $input['pgnstart'] = $input['pgnstop'] = "-1";
        }else{
            $input['pgnob'] = (isset($input['pgnob']) || $input['pgnob'] == "" ) ? $input['pgnob'] : "-1";
            $input['pgnob'] = ($input['pgnob'] == "date") ? "-1" : $input['pgnob'];
            $input['pgnot'] = (isset($input['pgnot']) || $input['pgnot']) ? $input['pgnot'] :"-1" ;
            $input['pgnstart'] = (isset($input['pgnstart']) || $input['pgnstart']) ? $input['pgnstart'] :"-1" ;
            $input['pgnstop'] = (isset($input['pgnstop']) || $input['pgnstop']) ? $input['pgnstop'] :"-1" ;
            if($diplaytype == "list" ) {
                $EntityID = $EntityTypeID = "-1";
            }
            if($diplaytype == "metadata-list" ) {
                $EntityID = $EntityTypeID = "-1";
            }
        }

        if ($input['QuestionID'] == -1 && $input['RepID'] == -1  )
        {
            $EntityTypeID  = $EntityID = -1;
        }
        Site::myDebug("rashmi--");
        Site::myDebug($filter);
        Site::myDebug($this->input['ignoredIDs']);
        $dataArray      = Array(
                            $EntityID,
                            $EntityTypeID,
                            $input['pgnob'],
                            $input['pgnot'],
                            $input['pgnstart'],
                            $input['pgnstop'],
                            $filter,
                            "-1",
                            $this->session->getValue('instID'),
                            $diplaytype,
                            $filterIds
                         );
        $resultRows     = $this->db->executeStoreProcedure('MetaDataAssignedList', $dataArray);
        
        if ( isset($input["searchstart"]) && $input["searchstart"] == 1)  // STore Search Criteria
	{
		Site::myDebug("----------STore Search Criteria");
		Site::myDebug($input);
		$input['entityid']      = 0;
		$input['entitytypeid']  = 13;
		$input['spcall']        = $resultRows['QR'];
		$input['count']         = $resultRows['TC'];

		if( trim($input['hdn_searchcrieteria']) != '' )
		{
			$this->saveAdvSearchCrieteria($input);
		}
	}        

        return $resultRows;
    }

    /**
    *  Return Assignment of  Metadata to entity
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  	$input
    * @param    integer  	$EntityID
    * @param    integer  	$EntityTypeID
    * @return   array
    *
    */
    public function metaDataAssignedListBy($input, $diplaytype = "list") {
        $EntityID = $input['EntityID'];
        $EntityTypeID = $input['EntityTypeID'];
        $filterOn = $input['SearchOn'];
        $filterStr = $input['metaDataSearch'];
        if ($diplaytype == "assign") {
            $input['pgnob'] = $input['pgnot'] = $input['pgnstart'] = $input['pgnstop'] = "-1";
        } else {
            $input['pgnob'] = (isset($input['pgnob']) || $input['pgnob'] == "" ) ? $input['pgnob'] : "-1";
            $input['pgnob'] = ($input['pgnob'] == "date") ? "-1" : $input['pgnob'];
            $input['pgnot'] = (isset($input['pgnot']) || $input['pgnot']) ? $input['pgnot'] : "-1";
            $input['pgnstart'] = (isset($input['pgnstart']) || $input['pgnstart']) ? $input['pgnstart'] : "-1";
            $input['pgnstop'] = (isset($input['pgnstop']) || $input['pgnstop']) ? $input['pgnstop'] : "-1";
            if ($diplaytype == "list") {
                $EntityID = $EntityTypeID = "-1";
            }
        }

        if ($input['QuestionID'] == -1 && $input['RepID'] == -1) {
            $EntityTypeID = $EntityID = -1;
        }
        
         if(!empty($input['ignoredIDs'])){
            $filterIds  = $input['ignoredIDs'];
        }else{
            $filterIds="-1";
        }
        $filter='-1';

        $dataArray = Array(
            $EntityID,
            $EntityTypeID,
            $input['pgnob'],
            $input['pgnot'],
            $input['pgnstart'],
            $input['pgnstop'],
            $filter,
            "-1",
            $this->session->getValue('instID'),
            $diplaytype,
            $filterIds
        );
        
        Site::myDebug("MetadataListORG test ---");
        Site::myDebug($dataArray);
        $resultRows = $this->db->executeStoreProcedure('MetaDataAssignedListORG', $dataArray);
        
        Site::myDebug($resultRows);
        return $resultRows;
    }

    /**
    *  Assignment of  Metadata to entity
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  	$input
    * @param    integer  	$EntityID
    * @param    integer  	$EntityTypeID
    * @return   void
    *
    */

   function assignedMetadata($input, $EntityID, $EntityTypeID) {
        Site::myDebug('INSIDE function assignedMetadata');
        Site::myDebug($input);
        if ($input['manualKeysValues'] != "") {
            $manualKeysValuesStr = trim($input['manualKeysValues'],'$$');
            $manualKeysValuesStr = trim($input['manualKeysValues'],'"');            
            $manualKeysValues = explode($this->cfgApp->metaDataValSeparator, $manualKeysValuesStr);
            $newAssingedId = '';
            $AssessmentID = '';
            $countManualKeysValues = count($manualKeysValues);
            for ($i = 0; $i < $countManualKeysValues; $i++) {
                if (trim($manualKeysValues[$i]) != '') {
                    $manualkeyvalarray = explode($this->cfgApp->metaDataKeyValSeparator, $manualKeysValues[$i]);
                    $metaDataKeyValues="0$$0$$".$manualkeyvalarray[2]."$$0";
                    // ISBN##1##1231231231232##text_entry$$Author##2##Rabi##text_entry$$Country##3##2##select_list$$
                    //0 1 2 3 assignedMetadata
                    $isEnabled = 1;
                    $data = array($manualkeyvalarray[3],
                        $manualkeyvalarray[1],
                        $manualkeyvalarray[0],
                        //addslashes($metaDataKeyValues),
                        htmlentities($metaDataKeyValues),
                        $metaDataKeyValueDeletedList,
                        $this->session->getValue('userID'),
                        $this->currentDate(),
                        $this->session->getValue('userID'),
                        $this->currentDate(),
                        '#', $isEnabled);
                    if($manualkeyvalarray[3]=='text_entry') {
                        $result = $this->db->executeStoreProcedure('MetaDataManage', $data, 'nocount');
                        $newAssingedId .= $result[0]['MapkeyValueID'] . ",";
                    }
                    else
                    {
                       $newAssingedId .= $manualkeyvalarray[2] . ","; 
                    }
                }
            }
            
            //print_r($input);
            if (isset($newAssingedId)) 
		{
                if (isset($input['QuestID']))
                    $questID = $input['QuestID'];
                else
                    $questID = $input['QuestionID'];
                if ( isset($input['AssessmentID']) )
                    $AssessmentID = $input['AssessmentID'];
                elseif( isset($input['mediaID']) )
                    $AssessmentID = $input['mediaID'];
                else
                    $AssessmentID = $input['BankID'];
                $totalAssingnedIDs = trim($newAssingedId, ",");
                $dataArray = array(
                    $totalAssingnedIDs,
                    $EntityID,
                    $EntityTypeID,
                    $this->session->getValue('userID'),
                    $this->currentDate(),
                    $this->session->getValue('userID'),
                    $this->currentDate());
                //$this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
                //echo (int)$AssessmentID."%%%";
                //echo (int)$questID;
                
                if ( (int)$questID > 1 || (int)$AssessmentID > 1 ) {
                    //echo "Individual";
                    $this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
                } else {
                    //echo "Bulk";
                    //$status = $this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
                    $this->db->executeStoreProcedure('MetaDataAssignBulk', $dataArray, 'nocount');
                }
                
                
            }
        }
      
    }

    function assignedMetadataForPegasus($input, $EntityID, $EntityTypeID)
    {       
        Site::myDebug('-------assignedMetadata');
        if($input['manualKeysValues'] !="")
        {
            $input['manualKeysValues']=str_replace("&#39;", "'", $input['manualKeysValues']);
           $manualKeysValuesStr = $input['manualKeysValues'];
           
           $manualKeysValues =  explode($this->cfgApp->hashSeparator, $manualKeysValuesStr);
          
           for( $i=0;$i<count($manualKeysValues);$i++ )
           {    
               if($manualKeysValues[$i]!='')
               {
                   $manualkeyvalarray = explode("|||", $manualKeysValues[$i]);               
                   $KeyStr = explode($this->cfgApp->metaDataValSeparator, $manualkeyvalarray[0]);              
                   $input['metaDataKeyId'] = $KeyStr[0];
                   $input['metaDataKeyName'] = $KeyStr[1];
                   $input['metaDataKeyValues'] = $manualkeyvalarray[1];
                   $input['metaDataKeyType']="text_entry";
                   $input['metaDataKeyValueDeletedList']="";

                   Site::myDebug('--------metadataSave before call to metadataSaveForPegasus func');
                   Site::myDebug($input);

                   $newAssingedId .= $this->metadataSaveForPegasus($input).",";
               }
           }
           
           Site::myDebug('-------$newAssingedId');
           Site::myDebug($newAssingedId);
           $newAssingedId = trim($newAssingedId, ",");
           Site::myDebug($newAssingedId);
          
       }
       
       if (  isset($input['metadataAssignedIds'])  || isset($newAssingedId ) )
       {           
            $totalAssingnedIDs = ($newAssingedId !="") ? $newAssingedId.",".$input['metadataAssignedIds'] :  $input['metadataAssignedIds'] ;
            $totalAssingnedIDs = trim($totalAssingnedIDs, ",");
            Site::myDebug('-------$totalAssingnedIDs');
            Site::myDebug($totalAssingnedIDs);            
            $dataArray      = array(
                                $totalAssingnedIDs,
                                $EntityID,
                                $EntityTypeID,
                                $this->session->getValue('userID'),
                                $this->currentDate(),
                                $this->session->getValue('userID'),
                                $this->currentDate()
                             );
		
           
            $this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
       }
       
    }
	
	function assignedMetadataForTestBuilder($input, $EntityID, $EntityTypeID)
    {       
        Site::myDebug('-------assignedMetadata');
        if($input['manualKeysValues'] !="")
        {
            $input['manualKeysValues']=str_replace("&#39;", "'", $input['manualKeysValues']);
           $manualKeysValuesStr = $input['manualKeysValues'];
           
           $manualKeysValues =  explode($this->cfgApp->hashSeparator, $manualKeysValuesStr);
          
           for( $i=0;$i<count($manualKeysValues);$i++ )
           {    
               if($manualKeysValues[$i]!='')
               {
                   $manualkeyvalarray = explode("|||", $manualKeysValues[$i]);               
                   $KeyStr = explode($this->cfgApp->metaDataValSeparator, $manualkeyvalarray[0]);              
                   $input['metaDataKeyId'] = $KeyStr[0];
                   $input['metaDataKeyName'] = $KeyStr[1];
                   $input['metaDataKeyValues'] = $manualkeyvalarray[1];
                   $input['metaDataKeyType']="text_entry";
                   $input['metaDataKeyValueDeletedList']="";

                   Site::myDebug('--------metadataSave before call to metadataSaveForTestBuilder func');
                   Site::myDebug($input);

                   $newAssingedId .= $this->metadataSaveForTestBuilder($input).",";
               }
           }
           
           Site::myDebug('-------$newAssingedId');
           Site::myDebug($newAssingedId);
           $newAssingedId = trim($newAssingedId, ",");
           Site::myDebug($newAssingedId);
          
       }
       
       if (  isset($input['metadataAssignedIds'])  || isset($newAssingedId ) )
       {           
            $totalAssingnedIDs = ($newAssingedId !="") ? $newAssingedId.",".$input['metadataAssignedIds'] :  $input['metadataAssignedIds'] ;
            $totalAssingnedIDs = trim($totalAssingnedIDs, ",");
            Site::myDebug('-------$totalAssingnedIDs');
            Site::myDebug($totalAssingnedIDs);            
            $dataArray      = array(
                                $totalAssingnedIDs,
                                $EntityID,
                                $EntityTypeID,
                                $this->session->getValue('userID'),
                                $this->currentDate(),
                                $this->session->getValue('userID'),
                                $this->currentDate()
                             );
		
           
            $this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
       }
       
    }
    
	function assignedMetadataForExamView($input, $EntityID, $EntityTypeID)
    {       
        Site::myDebug('-------assignedMetadata');
        if($input['manualKeysValues'] !="")
        {
            $input['manualKeysValues']=str_replace("&#39;", "'", $input['manualKeysValues']);
           $manualKeysValuesStr = $input['manualKeysValues'];
           
           $manualKeysValues =  explode($this->cfgApp->hashSeparator, $manualKeysValuesStr);
          
           for( $i=0;$i<count($manualKeysValues);$i++ )
           {    
               if($manualKeysValues[$i]!='')
               {
                   $manualkeyvalarray = explode("|||", $manualKeysValues[$i]);               
                   $KeyStr = explode($this->cfgApp->metaDataValSeparator, $manualkeyvalarray[0]);              
                   $input['metaDataKeyId'] = $KeyStr[0];
                   $input['metaDataKeyName'] = $KeyStr[1];
                   $input['metaDataKeyValues'] = $manualkeyvalarray[1];
                   $input['metaDataKeyType']="text_entry";
                   $input['metaDataKeyValueDeletedList']="";

                   Site::myDebug('--------metadataSave before call to metadataSaveForTestBuilder func');
                   Site::myDebug($input);

                   $newAssingedId .= $this->metadataSaveForTestBuilder($input).",";
               }
           }
           
           Site::myDebug('-------$newAssingedId');
           Site::myDebug($newAssingedId);
           $newAssingedId = trim($newAssingedId, ",");
           Site::myDebug($newAssingedId);
          
       }
       
       if (  isset($input['metadataAssignedIds'])  || isset($newAssingedId ) )
       {           
            $totalAssingnedIDs = ($newAssingedId !="") ? $newAssingedId.",".$input['metadataAssignedIds'] :  $input['metadataAssignedIds'] ;
            $totalAssingnedIDs = trim($totalAssingnedIDs, ",");
            Site::myDebug('-------$totalAssingnedIDs');
            Site::myDebug($totalAssingnedIDs);            
            $dataArray      = array(
                                $totalAssingnedIDs,
                                $EntityID,
                                $EntityTypeID,
                                $this->session->getValue('userID'),
                                $this->currentDate(),
                                $this->session->getValue('userID'),
                                $this->currentDate()
                             );
		
           
            $this->db->executeStoreProcedure('MetaDataAssign', $dataArray, 'nocount');
       }
       
    }
	
	
	
	/**
    * Get Metadata detail for key
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      array  	$input
    * @return     array         $meatadata
    *
    */

    function metadataKeyDetail($metaDataKeyID)
    {      
        $input['pgnob'] = (isset($input['pgnob'])) ? $input['pgnob'] : "-1";
        $input['pgnot'] = (isset($input['pgnot'])) ? $input['pgnot'] :"-1" ;
        $input['pgnstart'] = (isset($input['pgnstart'])) ? $input['pgnstart'] :"-1" ;
        $input['pgnstop'] = (isset($input['pgnstop'])) ? $input['pgnstop'] :"-1" ;
        $filter = "mdk.ID = '{$metaDataKeyID}'";
        $dataArray      = Array("-1","-1","-1","-1", $filter, "-1", "-1", $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), "-1");

        $resultRow     = $this->db->executeStoreProcedure('MetaDataList', $dataArray);
        return $resultRow["RS"][0];
    }

    public function metadataSearchList($input)
    {
        Site::myDebug($json);
        $search                 = ($json->keyinfo->title->val != '')?$json->keyinfo->title->val: $input['search'];
        $searchtype             = ($input['hdn_searchcrieteria'] != '')?'advanced':'basic';
        $input['ownerName']     = ($input['ownerName']== '')? -1 : $input['ownerName'];
        $input['pgndc']         = ($input['pgndc'] == '-1')?'qst.Count':$input['pgndc'].',qst.Count';

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


        $searchCond = " 1 ";
        if( $search != "-1")
        {
            $searchArr  = explode(' ',$search);
            if(count($searchArr) > 1)
            {
                $search = implode("','",(array)$searchArr);
                $search = "'$search'";
                if($title_filter == 'exclude')
                {
                    $searchCond  .= " AND t.Tag NOT IN ($search)  ";
                }
                else
                {
                    $searchCond  .= " AND t.Tag IN ($search)  ";
                }
            }
            else
            {
                if($title_filter == 'exclude')
                {
                    $searchCond  .= " AND t.Tag NOT LIKE '%{$search}%'  ";
                }
                else
                {
                    $searchCond  .= " AND t.Tag LIKE '%{$search}%'  ";
                }
            }
        }

        if( $mincount >= 0 &&  $maxcount >= 0 )
        {
            if($usagecount_filter == 'exclude')
            {
                $searchCond .= " AND ( t.Count < {$mincount} ";
                $searchCond .= " OR t.Count > {$maxcount} ) ";
            }
            else
            {
                $searchCond .= " AND t.Count >= {$mincount} ";
                $searchCond .= " AND t.Count <= {$maxcount} ";
            }
        }

        if ( $owner != "-1" )
        {
            if($users_filter == 'exclude')
            {
                $searchCond .= " AND t.UserID NOT IN ($owner)  ";
            }
            else
            {
                $searchCond .= " AND t.UserID IN ($owner)  ";
            }
        }

        if ( $startdate != "-1" )
        {
            if($date_filter == 'exclude')
            {
                $searchCond .= " AND ( date_format(t.ModDate,'%m-%d-%Y' ) < '{$startdate}'   ";
            }
            else
            {
                $searchCond .= " AND date_format(t.ModDate,'%m-%d-%Y' ) >= '{$startdate}'   ";
            }
        }

        if ( $enddate != "-1" )
        {
            if($date_filter == 'exclude')
            {
                $searchCond .= " OR date_format(t.ModDate,'%m-%d-%Y' ) > '{$enddate}'  )   ";
            }
            else
            {
                $searchCond .= " AND date_format(t.ModDate,'%m-%d-%Y' ) <= '{$enddate}'     ";
            }
        }

        if ( $searchtype == 'basic')
        {
            $filter = " (  ( mdv.MetaDataValue like '%".$search."%' and mdkv.ID IS NOT NULL )  OR  mdk.\"MetaDataName\" like '%".$search."%' ) ";
        }
        $input['pgnob'] = (isset($input['pgnob'])) ? $input['pgnob'] : "-1";
        $input['pgnot'] = (isset($input['pgnot'])) ? $input['pgnot'] :"-1" ;
        $input['pgnstart'] = (isset($input['pgnstart'])) ? $input['pgnstart'] :"-1" ;
        $input['pgnstop'] = (isset($input['pgnstop'])) ? $input['pgnstop'] :"-1" ;
        $dataArray      = Array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'], $filter, $input['EntityID'], $input['EntityTypeID'], $this->session->getValue('userID'), $this->session->getValue('isAdmin'), $this->session->getValue('instID'), "-1");
        $resultRows1     = $this->db->executeStoreProcedure('MetaDataList', $dataArray);
        
        $MetaDataTypes  = $this->getValueArray($resultRows, 'MetaDataType', 'multiple');
        $MetaDataTypes  = array_unique(explode(',', $MetaDataTypes));

        $EntityIds      = $this->getValueArray($resultRows, 'SourceID', 'multiple');
        $EntityIds      = array_filter(array_unique(explode(',', $EntityIds)));

        if ( isset($input["searchstart"]) && $input["searchstart"] == 1)  // STore Search Criteria
        {
            Site::myDebug("----------STore Search Criteria");
            Site::myDebug($input);
            $input['entityid']      = '';
            $input['entitytypeid']  = 11;
            $input['spcall']        = '';
            $input['count']         = $metadata['TC'];

            if(trim($input['hdn_searchcrieteria']) != '')
            {
                $this->saveAdvSearchCrieteria($input);
            }
        }
        return $resultRows1;
    }
    function assignedValueDetail($input, $type='',$condition='')
    {
		$condition      = ($condition != '')?$condition:'-1';
        $dataArray = array($input['KeyID'], '-1','-1',$input['pgnstart'],$input['pgnstop'],$condition);       
        if($type=='Tag'){         
            //echo "asasd";
            $resultRows = $this->db->executeStoreProcedure('TagAssignedValueDetail', $dataArray); 
            //echo "<pre>";print_r($resultRows);die("zsdsd");
        }else if($type=='Taxonomy'){         
            $resultRows = $this->db->executeStoreProcedure('TaxonomyAssignedValueDetail', $dataArray);   
        }else{  
            $resultRows = $this->db->executeStoreProcedure('assignedValueDetail', $dataArray); 
        }
        return $resultRows;
    }
    public function getMetaDataKey(){
        $unAssignedMetadata     = $this->metaDataAssignedList($input, "list");
        $unAssignedMetadata     = array_filter($unAssignedMetadata['RS']);
        if ( !empty( $unAssignedMetadata ) ) {
            $productStr = "";
            $productStr .= '<select class="ace-kvp-cls" multiple="multiple"   id="ace-kvp">';
            foreach ($unAssignedMetadata as $k => $val) {
                if( trim( $val['KeyName'] ) != '' ){
                    $productStr .= '<option value="' . $val['ID'] . '">' . $val['KeyName'] . '</option>';
                }
            }
            $productStr .='</select>';
            return $productStr;
        }else{
               return '<select class="ace-kvp-cls" multiple="multiple"  id="ace-kvp"></select>';
        }
    }
} 
?>
