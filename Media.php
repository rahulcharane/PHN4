<?php
/**
 * This class handles all media module related queries/requests.
 * This class handles the business logic of listing/add/edit/delete/search/upload and other requests of media.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */

class Media extends Site
{
    public  $mediaCnt;
	public  $mediaCnt123;
    public  $layout;
    private $absPthToDstSvr = '';

    /**
    * Construct new media instance
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $APPCONFIG
    * @param
    * @return   void
    *
    */

    function __construct()
    {
        parent::Site();
        global $APPCONFIG;
        $this->mediaCnt = "";
        $this->layout = new Layout();
        $this->classification = new Classification();
        $this->layout->setParameters();
        $this->cfgApp = $APPCONFIG;
    }

    /**
    * gets the media list of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   array   $result     list of media rows return from DB.
    *
    */

    public function mediaList(array $input, $sWhere)
    {
        global $DBCONFIG;
        
        $Media_tags     = $input["hsel_tags"];
        $taxonomy       = $input["hidtaxonomyNodeIds"];
        $minheight      = ($input["minheight"] == "")? "0" : $input["minheight"];
        $maxheight      = ($input["maxheight"] == "")? "0" : $input["maxheight"];
        $minwidth       = ($input["minwidth"] == "")? "0" : $input["minwidth"];
        $maxwidth       = ($input["maxwidth"] == "")? "0" : $input["maxwidth"];
        $minsize        = ($input["minsize"] == "")? "0" : $input["minsize"];
        $maxsize        = ($input["maxsize"] == "")? "0" : $input["maxsize"];
        $Media_tags     = ($Media_tags == "")? "-1" : $Media_tags;
        $taxonomy       = ($taxonomy == "")? "-1" : $taxonomy;
        $search         = ($input["sSearch"] == "")? "-1" : $sWhere;       

        $ownerName      = ($input["ownerName"] == "")? "-1" : $input["ownerName"];

        $minusgcount    = ($input["minusgcount"] == "")? "0" : $input["minusgcount"];
        $maxusgcount    = ($input["maxusgcount"] == "")? "0" : $input["maxusgcount"];
        $mediaType      = ($input["mediaType"] == "")? "0" : $input["mediaType"];

        $minhr          = ($input["minhr"] == "")? "00" : $input["minhr"];
        $minmin         = ($input["minmin"] == "")? "00" : $input["minmin"];
        $minsec         = ($input["minsec"] == "")? "00" : $input["minsec"];
        $minDuration    = ($minhr == "00" && $minmin == "00" && $minsec == "00")? "-1" :$minhr.":".$minmin.":".$minsec;

        $maxhr          = ($input["maxhr"] == "")? "00" : $input["maxhr"];
        $maxmin         = ($input["maxmin"] == "")? "00" : $input["maxmin"];
        $maxsec         = ($input["maxsec"] == "")? "00" : $input["maxsec"];
        $maxDuration    = ($maxhr == "00" && $maxmin == "00" && $maxsec == "00")? "-1" :$maxhr.":".$maxmin.":".$maxsec;

        $extension      = ($input["extension"] == "")? "-1" : $input["extension"];
        $orientation    = ($input["orientation"] == "")? "-1" : $input["orientation"];

        $input['pgnob'] = ($input['pgnob'] == '"date"' || $input['pgnob'] == 'date' )? "cntt.AddDate" : (($input['pgnob'] == "") ?"-1" : $input['pgnob']);

        $keyId 		= "";
        $valueId 	= "";
        $whereCond 	= "";
        $valueStr 	= "";
		
         Site::mydebug('input---------keyvauepair');
        Site::mydebug($input);
        if( $input["keyvaluepair"] != "" ){
            $indiviualKVP           = explode("$$",$input["keyvaluepair"]);
            foreach( $indiviualKVP  as $key => $val ){
                $arrindiviualKVP    = explode("##",$val);
                $keyId              .= $arrindiviualKVP[1].',';
				if( $arrindiviualKVP[3] == 'text_entry'){
					$whereCond .= " mdk.MetaDataValue LIKE '%" . $arrindiviualKVP[2] . "%' OR ";
				}else{
					$valueStr	=	$valueStr.",".$arrindiviualKVP[2];
					
				}
                
            }
            $keyId = rtrim($keyId,",");
			$whereCond = rtrim($whereCond, " OR ");
			
			$getMetadataValueID = "SELECT group_concat( ID ) as valueList from MetaDataValues mdk where ".$whereCond." AND mdk.isEnabled = '1'";
			$getMetadataValueList = $this->db->getSingleRow($getMetadataValueID);
			
			if($getMetadataValueList['valueList']){
				$valueId	=	$getMetadataValueList['valueList'];
				if(trim($valueStr, ",")){
					$valueId	=	$valueId.$valueStr;
					$valueId	=	trim($valueId, ",");
				}
					
			}elseif(trim($valueStr, ",")){
				$valueId	=	trim($valueStr, ",");
			}else{
				$valueId	=	'';
				$keyId	=	'';
			}
			
			
			
			
        }
        
        
        
        /*Web service call*/

        $userID = $this->session->getValue('userID')=="" ? $this->user_info->userID : $this->session->getValue('userID');
        $instID = $this->session->getValue('instID')=="" ? $this->user_info->instId : $this->session->getValue('instID');
        Site::mydebug('keyvauepair');
        Site::mydebug($keyId);
        $result = $this->db->executeStoreProcedure('MediaSearch',
                array(
                    $input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],
                    $search,
                    $minheight,
                    $maxheight,
                    $minwidth,
                    $maxwidth,
                    $minsize,
                    $maxsize,
                    $Media_tags,
                    $taxonomy,
                    $ownerName,
                    $minusgcount,
                    $maxusgcount,
                    $minDuration,
                    $maxDuration,
                    $mediaType,
                    $extension,
                    $orientation ,
                    $userID,
                    $instID,
                    "-1",
                    "AND",
                    $keyId,
                    $valueId,
                    ));

        return $result;
    }

    /**
    * gets the min and max values for media of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   array   $result     row of media min max values return from DB.
    *
    */

    public function mediaMinMaxValues($input)
    {
        global $DBCONFIG;
        switch($input['mediaType']){
            case 1:
                    $search = "cntt.ContentType='Image'";
                    break;
            case 2:
                    $search = "cntt.ContentType='Video'";
                    break;
            case 3:
                    $search = "cntt.ContentType='Audio'";
                    break;
            case 4:
                    $search = "cntt.ContentType='File'";
                    break;
            Default:
                    $search = "-1";
                    break;
        }
        $result = $this->db->executeStoreProcedure('MediaMinMaxCount', array($search,$this->session->getValue('userID'),$this->session->getValue('instID')));
        return $result['RS'];
    }

    /**
    * the specified media details to edit.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input     list of all input values.
    * @return   array   $rs        media details list with tag and taxonomy.
    *
    */

    public function edit(array $input)
    {
        global $DBCONFIG;
        $mediaID        = $input["mediaID"];
        $tagList        = $this->classification->getClassification($mediaID,"6");
        $qry            = "select * from Content where ID='{$mediaID}' and isEnabled = '1'";
        $rs             = $this->db->getSingleRow($qry);
        $rs['Tag']      = $tagList['Tag'];
        $rs['Taxonomy'] = $tagList['Taxonomy'];
        return $rs;
    }

    /**
    * delete the specified media.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   boolean $status     media deleted status.
    *
    */

    public function delete(array $input)
    {
        global $DBCONFIG;
        $mediaID    = $input["mediaID"];
        $qry        = " update Content set isEnabled = '0' where ID={$mediaID} and Count = 0";
        $status     = $this->db->execute($qry);
        /*============Classification Usage Count Decrease ================*/
        $assesmentModel = new Assessment();
        $assesmentModel->decreaseClassificationCount($mediaID,6);
        /*================================================================*/
        $data       = array(0,$this->session->getValue('userID'),6,$mediaID,$this->getTitleByID($mediaID),'Deleted',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage',$data);
        return $status;
    }
    
/*  
 *  @Manish (16-09-15)
 *  bulkDelete
 *  Delete media file on bulk delete event
 *  return status
*/
    
public function bulkDelete($mediaID)
{
   global $DBCONFIG;
   $mediaIDs      = $this->removeBlankElements($mediaID);
   $entityTypeId       = $this->registry->site->getEntityId('media');
   $mediaID       = implode(',',(array)$this->removeUnAccessEnities('mediaDelete',$entityTypeId,$mediaIDs));
   $qry      = "  UPDATE Content SET isEnabled = '0' WHERE ID IN($mediaID) ";
   $this->myDebug($qry);
   $status     = $this->db->execute($qry);
   
   /*============Classification Usage Count Decrease ================*/
    $assesmentModel = new Assessment();
    $assesmentModel->decreaseClassificationCount($mediaID,6);
    /*================================================================*/
   
   $data       = array(0,$this->session->getValue('userID'),6,$mediaID,$this->getTitleByID($mediaID),'Deleted',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
   $this->db->executeStoreProcedure('ActivityTrackManage',$data);
   return $status;
}
    

     /**
    * gets tag list for specified media.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    int     $mediaID    media id to get tag list of specified media.
    * @return   string  $tagList    all tags seperated by comma.
    * @deprecated
    */

    public function mediaTagList($mediaID)
    {
        $clsfcnList     = $this->db->executeStoreProcedure('ClassificationAssignedList', array($mediaID,'6'),"nocount");
        if(!empty($clsfcnList)):
            $clsfcnList = $clsfcnList[0];
            $tagList    = $clsfcnList['Tag'];
        else:
            $tagList    = "";
        endif;
        return $tagList;
    }

    /**
    * save the edited details of media.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   string  $status     string in json format.
    *
    */

    public function save(array $input)
    {
        $mediaID            = $input['mediaID'];
        $title              = addslashes($input['title']);
        $keywords           = addslashes($input['keywords']);
        $tags               = $input['tags'];
       
        $taxonomyNodeIds    = $input['taxonomyNodeIds'];
        $curDate            =  $this->currentDate();
        $ContentSize        = $input['size'];
        $Thumbnail          = $input['thumb'];
        $OriginalFileName   = $input['originalFile'];
        $FileName           = $input['fileName'];
        $result             = $this->nxtUpdate($mediaID, $title, $keywords, $ContentSize, $Thumbnail, $OriginalFileName, $FileName);
        /*Code Added By Akhlack For New tag created when selecting tag */       
//           $this->classification  = New Classification();
//           $tagList               = explode(",",$tags);
//           foreach($tagList as $key=>$val){
//               $ret = $this->classification->checkDuplicateTag($val);
//               if($ret==1){
//                   $this->classification->manageTag(0,$val);
//               }
//           }
        /* End */
           
           
        /* Code for Key Value Pair Add / Update */   
        $Metadata           = new Metadata();
                        
        $mediaTypeId    = $this->getEntityId("Media");        
        if(!isset($input["QuestID"] ))
        {
            $Metadata->assignedMetadata($input,$mediaID,$mediaTypeId);
        }
        /* End */
        
        if($result):
            $result1    = $this->classification->manageClassification($mediaID,'6',$tags,$taxonomyNodeIds);
            $status     = "{";
            $status    .= "'MediaID':'$mediaID',";
            $status    .= "'Msg':'Success'";
            $status    .= "}";
        else:
            $status     = "{";
            $status    .= "'MediaID':'$mediaID',";
            $status    .= "'Msg':'Fail'";
            $status    .= "}";
        endif;
        return $status;
    }

    /**
    * Add new media details.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $info      list of all input values.
    * @return   mixed
    *
    */

    public function add(array $info)
    {
        $columns = '';
        $values  = '';
        if(!empty($info))
        {
            $mediaID    = $this->db->insert("Content",$info);
            $data       = array(0,$this->session->getValue('userID'),6,$mediaID,$info['Title'],'Added',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
            $this->db->executeStoreProcedure('ActivityTrackManage',$data);
            return $mediaID;
        }
        return false;
    }

     /**
    * Add user details of user who upload media in the current institution.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $info      list of all input values.
    * @return   mixed              .
    *
    */
    public function addMember(array $info)
    {
        $columns = '';
        $values  = '';
        if(!empty($info))
        {
            return $this->db->insert("ContentMembers",$info);
        }
        return false;
    }

    /**
    * Update title,keywords of specified media.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    int     $mediaID    media id to update details.
    * @param    string  $title      updated media title.
    * @param    string  $keywords   updated media keywords.
    * @return   mixed   $status
    *
    */

    public function update($mediaID, $title, $keywords)
    {
        global $DBCONFIG;
        $query      = "UPDATE Content SET Title='{$title}' , Keywords='{$keywords}', ModDate='{$this->currentDate()}'";
        
        "WHERE ID='{$mediaID}' and isEnabled = '1' ";
        $status     = $this->db->execute($query);
        $data       = array(0,$this->session->getValue('userID'),6,$mediaID,$title,'Edited',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage',$data);
        return $status;
    }

    /**
    * calculate duration of specified media in case of video.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $file   media file name.
    * @return   string  $time   media duration in hh:mm:ss format in case of video else 0.
    *
    */

    function calcDuration($file)
    {
        $imageExt = $this->findExt($file);
        if( in_array($imageExt, array_merge($this->cfgApp->videoFormats, $this->cfgApp->audioFormats) ) && $this->cfg->ffmpegIsInstalled)
        {
            $percent = 100;
            ob_start();
            if($this->cfg->os == 'WIN')
            {
                $command = "{$this->cfg->rootPath}/plugins/ffmpeg/ffmpeg -i \"". $file . "\" 2>&1";
            }
            else
            {
                $command = "{$this->cfg->linuximagecommand} -i \"". $file . "\" 2>&1";
            }

            passthru($command);
            $duration = ob_get_contents();
            ob_end_clean();

            preg_match('/Duration: (.*?),/', $duration, $matches);
            $duration       = $matches[1];
            $duration_array = explode(':', $duration);
            $duration       = $duration_array[0] * 3600 + $duration_array[1] * 60 + $duration_array[2];
            $time           = $duration * $percent / 100;
            $time           = intval($time/3600) . ":" . intval(($time-(intval($time/3600)*3600))/60) . ":" . intval(($time-(intval($time/60)*60)));
            return $time;
        }
        else
        {
            return "00:00:00";
        }
    }

    /**
    * upload media at temporary location for current institution.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   string  $response   uploaded media details in JSON format.
    *
    */

    public function upload(array $input)
    {        
		Site::myDebug("--------MediaUpload222222");
        //Site::myDebug($input);
        Site::myDebug($_FILES);
                      
        $title              = (isset($input["Title"]))? $input["Title"] : "";
        $keywords           = (isset($input["Keywords"]))? $input["Keywords"] : "";
        $tags               = (isset($input["Tags"]))? $input["Tags"] : "";
        $taxonomyNodeIds    = (isset($input["taxonomyNodeIds"]))? $input["taxonomyNodeIds"] : "";
        $msg                = '';
        $fileElementName    = 'Filedata';
        $Temp_flag          = $input["Temp_flag"];
        $imageobj           = new SimpleImage();             
        
        //$_FILES['Filedata']['name']
        
        
        if($Temp_flag ==  true)
        {            
            if($this->verifyUploadedFile($input,$fileElementName))
            {
                $fileName       = basename($this->fileParam($fileElementName,'name'));
                if($this->fileParam($fileElementName,'tmp_name'))
                {
                    $size=$this->fileParam($fileElementName,"size");
                    
                    if($this->filterFileName($fileName))
                    {
                        if($this->checkFileNameForSpecialChars($fileName))
                        {    
                            $guid           = uniqid("media");
                            $imageExt       = $this->findExt($fileName);

                           // $assetTmpPath   = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets') );
                            //$assetTmpHttpPath = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets', 'protocol' => 'http'  ) );
                            $assetTmpPath = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets','instID' => $input['assetUpInstId']));
                            $assetTmpHttpPath = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets', 'protocol' => 'http','instID' => $input['assetUpInstId']));
                            $path_system    = $assetTmpPath.$guid.".".$imageExt;
                            $path_web       = $assetTmpHttpPath.$guid.".".$imageExt;

                            $thumb_img      = $assetTmpPath."thumb_".$guid.".".$imageExt;
                            $name           = $assetTmpPath."thumb_".$guid.'.jpg';
                            $tempname       = $assetTmpPath."thumbtemp_".$guid.'.jpg';

                            if( in_array($imageExt, $this->cfgApp->imgFormats)  )
                            {
                                $thumb_img_web_path = $assetTmpHttpPath."thumb_".$guid.".".$imageExt;
                            }
                            move_uploaded_file($this->fileParam($fileElementName,'tmp_name'), $path_system);
                            $file_path  = $assetTmpPath.$guid;
                            chmod($path_system, 0777);

                            if($imageExt == 'zip')
                            {
                                mkdir($file_path,0777);
                                $this->unzip($path_system,$file_path);
                                if(file_exists($path_system))
                                {
                                    unlink($path_system);
                                }
                                $response  = '[';
                                $response .= $this->traverseDir($file_path);
                                $response .= ']';
                                $this->rmDirRecurse($file_path);
                                rmdir($file_path);
                            }
                            else
                            {
                                    Site::myDebug("---------TESTupload");
                                    $size=$this->fileParam($fileElementName, 'size');

                                    if( in_array($imageExt, $this->cfgApp->imgFormats) )
                                    {
                                        @copy($path_system,$thumb_img);
                                        $cntType = "Image";
                                        $dimension = getimagesize($path_system);
                                        $imageobj->load($thumb_img);
                                        if( (int)$dimension[0] > (int)$dimension[1] || (int)$dimension[0] == (int)$dimension[1] )
                                        {
                                            $imageobj->resizeToWidth("100");
                                        }
                                        else
                                        {
                                            $imageobj->resizeToHeight("100");
                                        }
                                        $imageobj->save($thumb_img);
                                        $thumb_dimension = getimagesize($thumb_img);
                                    }
                                    elseif( in_array($imageExt, $this->cfgApp->audioFormats) )
                                    {
                                        $cntType   = "Audio";
                                        $dimension = array('', '');
                                    }
                                    elseif(in_array($imageExt, $this->cfgApp->videoFormats))
                                    {
                                        $cntType   = "Video";
                                        $dimension = array('', '');
                                    }
                                    else
                                    {
                                        $cntType = "File";
                                        $dimension = array('', '');
                                    }

                                    if($cntType == 'Video' )
                                    {
                                        if($this->cfg->ffmpegIsInstalled){
                                            if($this->cfg->os == 'WIN')
                                            {
                                                $command = "{$this->cfg->winimagecommand} -i $path_system -an -y -f mjpeg -ss 00:00:01 -vframes 1 $tempname ";
                                            }
                                            else
                                            {
                                                $command = "{$this->cfg->linuximagecommand} -i $path_system -an -y -f mjpeg -ss 00:00:01 -vframes 1 $tempname";
                                            }
                                            $this->myDebug('single command===>' . $command);
                                            exec($command);
                                            if(file_exists($tempname) &&  filesize($tempname) >0){
                                                $imageobj->load($tempname);
                                                $imageobj->save($name);
                                                unlink($tempname);
                                                $thumb_img_web_path     = $assetTmpHttpPath."thumb_".$guid.".jpg";
                                            }
                                        }
                                        else{
                                            $thumb_img_web_path = '';
                                        }
                                        $duration               = $this->calcDuration($path_system);
                                    }
    //                                else{
    //                                    $duration = "00:00:00";
    //                                }

                                    $response  = '[{';
                                    $response .= "\"error\":\"".$this->error."\",\n";
                                    $response .= "\"msg\": \"\",\n";
                                    $response .= "\"src\": \"".$path_web."\",\n";
                                    $response .= "\"actual_img_name\": \"".$this->fileParam($fileElementName,'name')."\",\n";
                                    $response .= "\"imgheight\": \"".$dimension[1]."\",\n";
                                    $response .= "\"imgwidth\": \"".$dimension[0]."\",\n";
                                    $response .= "\"thumb_src\": \"".$thumb_img_web_path."\",\n";
                                    $response .= "\"thumb_height\": \"".$thumb_dimension[1]."\",\n";
                                    $response .= "\"thumb_width\": \"".$thumb_dimension[0]."\",\n";
                                    $response .= "\"imgsize\": \"".round($size/1024)."\",\n";
                                    $response .= "\"imgtype\": \"".$cntType."\",\n";
                                    $response .= "\"imgext\": \"".$imageExt."\",\n";
                                    $response .= "\"title\": \"".basename(strtolower($this->fileParam($fileElementName,'name')),".".$imageExt). "\",\n";
                                    $response .= "\"keyword\": \"\",\n";
                                    $response .= "\"tag\": \"\",\n";
                                    $response .= "\"taxonomy\": \"\",\n";
                                    $response .= "\"duration\": \"{$duration}\",\n";
                                    $response .= "\"flag\": \"\" \n";
                                    $response .= '}]';
                            }
                        }
                        else
                        {
                            $this->error = ALLOWEDMEDIANAMEERROR;
                        } 
                    }
                    else
                    {
                        $this->error = ALLOWEDMEDIAERROR;
                    }
                }
            }
        }
        else
        {                    
            if($this->verifyUploadedFile($input,$fileElementName))
            {
                $absPthToDst    = $this->cfg->wwwroot.'/'.$this->cfgApp->EditorImagesUpload;
                $this->absPthToDstSvr = $this->cfg->rootPath.'/'.$this->cfgApp->EditorImagesUpload;
                $fileName       = basename($this->fileParam($fileElementName,'name'));
                $dbPath         = $fileName;

                if($this->fileParam($fileElementName,'tmp_name'))
                {
                    $size=$this->fileParam($fileElementName,"size");
                    if($this->filterFileName($fileName))
                    {
                        $targetPath = $this->absPthToDstSvr.$fileName;
                        if($this->findExt($fileName) == 'zip')
                        {
                            $this->uploadZip($input, $fileName, $fileElementName);
                            $msg = $this->checkDirectory($fileName,$title,$keywords,$tags,$taxonomyNodeIds); ///coding to be continued from here....
                        }else{
                                if(file_exists($targetPath))
                                {
                                    $this->error .= SAMEFILENAMEERROR;//$this->myDebug("Stage 55: ".$this->error);
                                }
                                else
                                {
                                    move_uploaded_file($this->fileParam($fileElementName,'tmp_name'), $targetPath);
                                    if(in_array($this->findExt($fileName), $this->cfgApp->imgFormats))
                                    {
                                        $cntType = "Image";
                                        $dimension = getimagesize($targetPath);
                                    }
                                    elseif(in_array($this->findExt($fileName), $this->cfgApp->videoFormats))
                                    {
                                         $cntType = "Video";
                                    }
                                    elseif(in_array($this->findExt($fileName), $this->cfgApp->audioFormats))
                                    {
                                         $cntType = "Audio";
                                    }
                                    elseif(in_array($this->findExt($fileName), $this->cfgApp->fileFormats))
                                    {
                                         $cntType = "File";
                                    }
                                    elseif(in_array($this->findExt($fileName), $this->cfgApp->otherFormats))
                                    {
                                        $cntType = "Other";
                                    }
                                    $info = array(
                                            'Title'         => $title,
                                            'Keywords'      => $keywords,
                                            'ContentType'   => $cntType,
                                            'ContentInfo'   => $dbPath,
                                            'UserID'        => $this->session->getValue('userID'),
                                            'AddDate'       => $this->currentDate(),
                                            'ModBY'         => $this->session->getValue('userID'),
                                            'ModDate'       => $this->currentDate(),
                                            'FileName'      => $fileName,
                                            'isEnabled'     => '1',
                                            'ContentHeight' => $dimension[1],
                                            'ContentWidth'  => $dimension[0],
                                            'ContentSize'   => $size
                                    );
                                    $mediaID    = $this->add($info);
                                    $cntMmbr    = array(
                                        'ContentID' => $mediaID,
                                        'UserID'    => $this->session->getValue('userID'),
                                        'AddDate'   => $this->currentDate(),
                                        'ModBY'     => $this->session->getValue('userID'),
                                        'ModDate'   => $this->currentDate(),
                                        'isEnabled' => '1'
                                    );
                                    $memberID   = $this->addMember($cntMmbr);
                                    if(!(trim($tags) == '' && trim($taxonomyNodeIds) ==''))
                                    {
                                        $this->classification->manageClassification($mediaID,'6',$tags,$taxonomyNodeIds);
                                    }
                                    $msg .= "Uploaded File::{$fileName}||";
                                    $msg .= 'Total Files::1||';
                                    $msg .= 'Copied::1||';
                                    $msg .= 'Failed::0||';
                                }
                        }
                     }else{
                        $this->error = ALLOWEDMEDIAERROR;
                    }
                }
            }
            $response  = '{';
            $response .= "error: '" . $this->error . "',\n";
            $response .= "msg: '" . $msg . "'\n";
            $response .= '}';
        }
        if($this->error)
        {
            $response  = '[{';
            $response .= "\"error\":\"" . $this->error ."\",\n";
            $response .= "\"msg\": \"".$msg."\"\n";
            $response .= '}]';
        }
        return $response;
    }

    /**
    * move uploaded media to permanent location from temporary location for current institution.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   void
    *
    */

    public function upload1(array $input)
    {
        $this->tempPath = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets') );
        $this->absPthToDstSvr   = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets') );
        
        //Site::myDebug($this->cfg->rootPath.'/'.$this->cfgApp->EditorImagesUpload);        
        //Site::myDebug(s3uploader::getCloudFrontURL('test.jpg'));
        
        $data                   = html_entity_decode(strip_tags($input["data"]));
        $objJSONtmp             = new Services_JSON();
        $outObj                 = $objJSONtmp->decode($data);
		Site::myDebug("--------mediaupload1 function SANTANU");
        Site::myDebug( $outObj );
		
        if(!empty($outObj))
        {
            foreach($outObj as $key=>$outObj1)
            {
               // Site::myDebug("--------mediaupload1");
              //  Site::myDebug( $outObj );
                $thumb_file="";
                if($outObj1->flag !='N')
                {
                    if(!empty($outObj1->src))
                    {
                        $file = basename($outObj1->src);
                        $fileTempPath = $this->tempPath.$file;
                    }
                    if(!empty($outObj1->thumb_src))
                    {
                        $thumb_file = basename($outObj1->thumb_src);
                        $thumbTempPath = $this->tempPath.$thumb_file;
                    }
                    if ( $outObj1->imgtype == 'Image' )
                    {
                        $origFilePath  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/') );
                        $thumbFilePath = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/thumb/') );
                    }
                    elseif ( $outObj1->imgtype == 'Video' )
                    {
                        $origFilePath  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/original/') );
                        $thumbFilePath = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/thumb/') );
                    }
                    elseif( $outObj1->imgtype == 'Audio' )
                    {
                         $origFilePath  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/audios/') );
                    }
                    elseif($outObj1->imgtype == 'File')
                    {
                        $origFilePath  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/files/') );
                    }
                    else
                    {
                        $origFilePath  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/others/') );
                    }



                    $fileExt    = $outObj1->imgext;
                    Site::myDebug("Upload Path :::::::");
                    Site::myDebug($fileTempPath.":::::::::".$this->absPthToDstSvr.$file);
                    if(@copy($fileTempPath,$origFilePath.$file))
                    {       
                         /***amazon S3 server file upload***/
                         $S3origFilePath = str_replace($this->cfg->rootPath.'/', "", $origFilePath);
                         Site::myDebug("S3 Orig File Path=".$S3origFilePath);
                         s3uploader::upload($origFilePath.$file, $S3origFilePath.$file);
                        /*** code for calculating duration - starts ***/
                        $duration = $this->calcDuration($origFilePath.$file);
                        /*** code for calculating duration - ends ***/
                        Site::myDebug("-----duration");
                        Site::myDebug($duration);
                        Site::myDebug($origFilePath.$file);
                        $contentType = $outObj1->imgtype;
                        if(!empty($thumb_file)){

                            if(@copy($thumbTempPath, $thumbFilePath.$thumb_file))
                            {
                                /***amazon S3 server file upload***/
                                 $S3thumbFilePath = str_replace($this->cfg->rootPath.'/', "", $thumbFilePath);
                                 Site::myDebug("S3 Thumd File Path=".$S3thumbFilePath);
                                 s3uploader::upload($thumbFilePath.$thumb_file, $S3thumbFilePath.$thumb_file);
                         
                                if( $outObj1->imgtype == "Image" )
                                {
                                    // list($width, $height, $type, $attr) = getimagesize($this->absPthToDstSvr.$file);
                                    list($width, $height, $type, $attr) = getimagesize($origFilePath.$file);
                                    $image = new SimpleImage();
                                    // $image->load($this->absPthToDstSvr.$file);
                                    $image->load($origFilePath.$file);
                                    if($width > 300)
                                    {
                                        $image->resizeToWidth(300);
                                    }
                                    else
                                    {
                                        $image->resize($width, $height);
                                    }
                                    // Currently not in use
                                    // $image->save($this->absPthToDstSvr.'/preview/'.$file);
                                }
                            }
                            // unlink($this->cfg->rootPath."/".$this->cfgApp->QuizCSSImageUnzipTempLocation.$thumb_file);
                            unlink($this->tempPath.$thumb_file);
                        }
                        // unlink($this->cfg->rootPath."/".$this->cfgApp->QuizCSSImageUnzipTempLocation.$file);
                        unlink($this->tempPath.$file);

                        $info = array(
                                'Title'         => $outObj1->title,
                                'Keywords'      => $outObj1->keyword,
                                'ContentType'   => $contentType,
                                'ContentInfo'   => $file,
                                'UserID'        => $this->session->getValue('userID'),
                                'AddDate'       => $this->currentDate(),
                                'ModBY'         => $this->session->getValue('userID'),
                                'ModDate'       => $this->currentDate(),
                                'FileName'      => $file,
                                'isEnabled'     => '1',
                                'ContentHeight' => $outObj1->imgheight,
                                'ContentWidth'  => $outObj1->imgwidth,
                                'ContentSize'   => $outObj1->imgsize,
                                'Thumbnail'     => $thumb_file,
                                'Duration'      => $duration,
                                'OriginalFileName'=>$outObj1->actual_img_name
                                );
                        $mediaID    = $this->add($info);
                        $outObj[$key]->asset_id = $mediaID ;
                        $cntMmbr    = array(
                                    'ContentID' => $mediaID,
                                    'UserID'    => $this->session->getValue('userID'),
                                    'AddDate'   => $this->currentDate(),
                                    'ModBY'     => $this->session->getValue('userID'),
                                    'ModDate'   => $this->currentDate(),
                                    'isEnabled' => '1'
                                    );
                        $memberID          = $this->addMember($cntMmbr);
                        $tags              = $outObj1->tag;
                        $taxonomyNodeIds   = $outObj1->taxonomy;
                        if(!(trim($tags) =='' && trim($taxonomyNodeIds) ==''))
                        {
                            $this->classification->manageClassification($mediaID,'6',$tags,$taxonomyNodeIds);
                        }
                    }
                }
            }
            $this->mydebug("Assest json with ID");
            $this->mydebug($outObj);
            return $objJSONtmp->encode($outObj);
        }
    }

    /**
    * traverse the folder for zip media upload or to get media from uploaded media location.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string      $file_path      media uploaded path inacese of zip file or just media location path.
    * @param    boolean     $media          flag to avoid files other then media (like image,video).
    * @return   string      $response       all media details in JSON format.
    *
    */

    function traverseDir($file_path,$media=false)
    {
        $imageobj   = new SimpleImage();
        $dir        = opendir($file_path);

        while(false != ($file = readdir($dir)))
        {
            $flag   = '';
            if(is_dir($file_path.'/'.$file) && $file != "." && $file != "..")
            {
                $response .= $this->traverseDir($file_path.'/'.$file,$media).',';
            }
            else
            {
                $media_flag = 0;
                if(($file != ".") and ($file != ".."))
                {
                    $assetTmpHttpPath = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets', 'protocol' => 'http'  ) );
                    $assetTmpRootPath = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets' ) );

                    $ext                = $this->findExt($file);
                    $zipuniqid          = uniqid("media");
                    $zip_path           = $assetTmpRootPath.$zipuniqid.".".$ext;
                    $zip_web_path       = $assetTmpHttpPath.$zipuniqid.".".$ext;
                    $thumb_img          = $assetTmpRootPath."thumb_".$zipuniqid.".".$ext;
                    $name               = $assetTmpRootPath."thumb_".$zipuniqid.'.jpg';
                    $thumb_img_web_path = $assetTmpHttpPath."thumb_".$zipuniqid.".".$ext;

                    if($this->filterFileName($file))
                    {
                        @copy($file_path."/".$file,$zip_path);
                        $size  = filesize($zip_path);

                        if( in_array($ext, $this->cfgApp->imgFormats) )
                        {
                            @copy($zip_path,$thumb_img);
                            $media_flag = 1;
                            $cntType    = "Image";
                            $dimension  = getimagesize($zip_path);
                            $imageobj->load($thumb_img);

                            if( (int)$dimension[0] > (int)$dimension[1] || (int)$dimension[0] == (int)$dimension[1] )
                            {
                                $imageobj->resizeToWidth("100");
                            }
                            else
                            {
                                $imageobj->resizeToHeight("100");
                            }
                            $imageobj->save($thumb_img);
                            $thumb_dimension = getimagesize($thumb_img);
                        }
                        elseif( in_array($ext, $this->cfgApp->videoFormats))
                        {
                            // @copy($zip_path, $thumb_img);
                            $media_flag = 1;
                            $cntType    = "Video";
                            $dimension  = array('','');
                            if($this->cfg->ffmpegIsInstalled){
                                if($this->cfg->os == 'WIN')
                                {
                                    $command = "{$this->cfg->winimagecommand} -i $zip_path -an -y -f mjpeg -ss 00:00:03 -vframes 1 $name";
                                }
                                else
                                {
                                    $command = "{$this->cfg->linuximagecommand} -i $zip_path -an -y -f mjpeg -ss 00:00:03 -vframes 1 $name";
                                }
                                exec($command);
                                $thumb_img_web_path = $assetTmpHttpPath."thumb_".$zipuniqid.".jpg";
                            }
                            else{
                                $thumb_img_web_path="";
                            }
                            $duration           = $this->calcDuration($zip_path);
                        }
                        else
                        {
                            $media_flag = 0;
                            $cntType    = "File";
                        }
                    }
                    else
                    {
                        $media_flag     = 0;
                        $flag           = "N";
                        $zip_web_path   = $dimension[0] = $dimension[1] = $thumb_img_web_path = $thumb_dimension[0] = $thumb_dimension[1] = $size = $cntType = "";
                    }

                    if(($media_flag ==1 && $media === true) || $media === false )
                    {
                        $response .= '{';
                        $response .= "\"error\": \"".$this->error."\",\n";
                        $response .= "\"msg\": \"".$msg."\",\n";
                        $response .= "\"src\": \"".$zip_web_path."\",\n";
                        $response .= "\"actual_img_name\": \"".$file."\",\n";
                        $response .= "\"imgheight\": \"".$dimension[1]."\",\n";
                        $response .= "\"imgwidth\": \"".$dimension[0]."\",\n";
                        $response .= "\"thumb_src\": \"".$thumb_img_web_path."\",\n";
                        $response .= "\"thumb_height\": \"".$thumb_dimension[1]."\",\n";
                        $response .= "\"thumb_width\": \"".$thumb_dimension[0]."\",\n";
                        $response .= "\"imgsize\": \"".round($size/1024)."\",\n";
                        $response .= "\"imgtype\": \"".$cntType."\",\n";
                        $response .= "\"imgext\": \"".$ext."\",\n";
                        $response .= "\"title\": \"" .basename(strtolower($file),".".$ext)."\",\n";
                        $response .= "\"keyword\": \"\",\n";
                        $response .= "\"tag\": \"\",\n";
                        $response .= "\"taxonomy\": \"\",\n";
                        $response .= "\"duration\": \"{$duration}\",\n";
                        $response .= "\"flag\": \"".$flag."\"\n";
                        $response .= '},';
                        $flag      = "";
                    }
                }
            }
        }
        $response   = rtrim($response,",");
        closedir($dir);
        return $response;
    }

    /**
    * check media file image or video exist or not and add specified details for those media.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string      $fileName           uploaded media file name.
    * @param    boolean     $title              title for uploaded media.
    * @param    boolean     $keywords           keywords for uploaded media.
    * @param    boolean     $tags               tag list for uploaded media.
    * @param    boolean     $taxonomyNodeIds    taxonomy id list for uploaded media.
    * @return   string      $msg                all media details in JSON format.
    *
    */

    function checkDirectory($fileName,$title,$keywords,$tags,$taxonomyNodeIds)
    {
        $extensions     = $this->cfgApp->uploadFormats;
        $k              = 0;
        $failed         = 0;
        $success        = 0;
        $msg            = '';

        $dirName    = $this->targetPathDir;
        $dir        = opendir($dirName);

        while(false != ($file = readdir($dir)))
        {
            if(($file != ".") and ($file != ".."))
            {
                $ext = $this->findExt($file);
                if(in_array($ext, $extensions))
                {
                    if(file_exists($this->absPthToDstSvr.$file))
                    {
                        $pending[] = $file;
                        $failed++;
                    }
                    else
                    {
                        @copy($dirName."/".$file,$this->absPthToDstSvr.$file);
                        $size=filesize($dirName."/".$file);

                        if(in_array($this->findExt($file), $this->cfgApp->imgFormats)){
                        $cntType    = "Image";
                        $dimension  = getimagesize($dirName."/".$file);
                        }
                        elseif(in_array($this->findExt($file), $this->cfgApp->videoFormats)){
                        $cntType = "Video";
                        }
                        else{
                        $cntType = "File";
                        }
                        $success++;
                        $dbPath     = $file;
                        $title      = ($title == "")? $file : $title;
                        $keywords   = ($keywords == "")? $file : $keywords;
                        $info       = array(
                                        'Title'         => $title,
                                        'Keywords'      => $keywords,
                                        'ContentType'   => $cntType,
                                        'ContentInfo'   => $dbPath,
                                        'UserID'        => $this->session->getValue('userID'),
                                        'AddDate'       => $this->currentDate(),
                                        'ModBY'         => $this->session->getValue('userID'),
                                        'ModDate'       => $this->currentDate(),
                                        'FileName'      => $dbPath,
                                        'isEnabled'     => '1',
                                        'ContentHeight' => $dimension[1],
                                        'ContentWidth'  => $dimension[0],
                                        'ContentSize'   => $size
                                        );
                        $mediaID = $this->add($info);
                        $cntMmbr = array(
                                        'ContentID' => $mediaID,
                                        'UserID'    => $this->session->getValue('userID'),
                                        'AddDate'   => $this->currentDate(),
                                        'ModBY'     => $this->session->getValue('userID'),
                                        'ModDate'   => $this->currentDate(),
                                        'isEnabled' => '1'
                                        );
                        $memberID = $this->addMember($cntMmbr);
                        if(!(trim($tags) =='' && trim($taxonomyNodeIds) ==''))
                        {
                            $this->classification->manageClassification($mediaID,'6',$tags,$taxonomyNodeIds);
                        }
                    }
                    $k++;
                }
            }
        }
        closedir($dir);

        $msg .="Uploaded File::".$fileName."||";
        $msg .="Total Files::".$k."||";
        $msg .="Copied::".$success."||";

        if($failed > 0)
        {
            $failedfiles = "";
            if(!empty($pending))
            {
                foreach($pending as $msgp)
                {
                    $failedfiles .= $msgp."<br/>";
                }
            }
            $msg .="Failed::".$failed."||";
            $msg .="Reason::File with same name already exists||";
            $msg .="Failed Files::".$failedfiles."||";
        }
        else
        {
            $msg .="Failed::".$failed."||";
        }
        return $msg;
}

    /**
    * upadte tags for specified media.
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    string  $tagStr         tag list seperated by comma
    * @param    int     $entityID       entity id i.e media id.
    * @param    int     $entityTypeID   entityType id i.e id of entity type 'Media'is 6.
    * @return   void
    *
    */

    private function manageMediaTags($tagStr, $entityID, $entityTypeID)
    {
         $result = $this->db->executeStoreProcedure('ClassificationManage',
                                                array(
                                                    $tagStr,
                                                    $this->session->getValue('userID'),
                                                    $entityID, $entityTypeID, 'Tag',
                                                    $this->currentDate(),$this->session->getValue('userID'),
                                                    $this->currentDate(),$this->session->getValue('accessLogID')),'nocount');
         return;
   }

    /**
    * upload zip file having multiple media.
    *
    *
    * @access   private
    * @abstract
    * @static
    * @global
    * @param    array   $input               array of input
    * @param    string  $fileName            entity i.e media id.
    * @param    string  $fileElementName     name of input field FILE.
    * @return   void
    *
    */

    private  function uploadZip(array $input,$fileName,$fileElementName)
    {
        $guid       = uniqid();
        $file_path  = $this->cfg->rootPath."/".$this->cfgApp->QuizCSSImageUnzipTempLocation.$guid;

        mkdir($file_path,0777);

        $targetPathZip          = $file_path."/".$fileName;
        $this->targetPathDir    = $file_path;

        move_uploaded_file($this->fileParam($fileElementName,'tmp_name'), $targetPathZip);
        $this->unzip($targetPathZip,$this->targetPathDir);

        if(file_exists($targetPathZip))
        {
            unlink($targetPathZip);
        }
    }

    /**
    * check for specified file format is valid or not.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $var    file name.
    * @return   boolean         return flag file format is valid or not.
    *
    */

    function filterFileName($var)
    {
        $extensions = $this->cfgApp->uploadFormats;
        if(in_array($this->findExt($var), $extensions))
        {
            return true;
        }else{
            return false;
        }
    }
    
    /**
    * check for specified file name contains special characters or not. The uploaded file with the combination of alphabets, numbers, hypne and underscore in file name are allowed.
    * 
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $fileName    file name.
    * @return   boolean         return flag file format is valid or not.
    *
    */
    function checkFileNameForSpecialChars($fileName)
    {
        $fileName   = strtolower($fileName) ;
        $name_ext   = explode(".", $fileName);
        $only_file_name = $name_ext[0];
        if((preg_match('/[\'\/~`\!@#\$%\^&\*\(\)\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $only_file_name) ) == true )
        {
            return false;
        }
        else
        {
            return true;
        }
        
    }

    /**
    * find the extension of the specified file name.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $var    file name.
    * @return   string  $exts   extension of the given file name.
    *
    */

    function findExt($fileName)
    {
        $fileName   = strtolower($fileName) ;
        $exts       = explode(".", $fileName) ;
        $n          = count($exts)-1;
        $exts       = $exts[$n];
        return $exts;
    }

    /**
    * get media id for sepcified file name.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $filename    file name.
    * @return   string  $mediaID['ID']   id of specified filename .
    *
    */

    function getIdByName($filename)
    {
        global $DBCONFIG;
        $query   = "select ID from Content where FileName = '$filename'";
        $mediaID = $this->db->getSingleRow($query);
        return $mediaID['ID'];
    }

    /**
    * get media title for sepcified file name.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $filename    file name.
    * @return   string  $mediaID['Title']   title of specified filename .
    *
    */

    function getTitleByID($id)
    {
        global $DBCONFIG;
        $query   = "select Title from Content where ID = '$id'";
        $mediaID = $this->db->getSingleRow($query);
        return $mediaID['Title'];
    }

    /**
    * gets list of question for specified content id i.e media id.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  $input           list of all input values.
    * @return   array  $questionlist    array of list of questions with details.
    *
    */
    function questionList(array $input,$condition='')
    {
//        $start=$input['start']; $stop=$input['limit'];
//        $limit=($stop !="")? " LIMIT ".$start ." , ". $stop :"";
//        header('Content-type: text/xml; charset=UTF-8') ;

        /*
        $user   = new User();
        if($user->getDefaultClientID() == $this->session->getValue('instID'))
        {
            $query  = "   SELECT SQL_CALC_FOUND_ROWS qst.ID, qst.Title, qst.QuestionTemplateID ,qtp.TemplateTitle,qtp.ID as qtID , qtp.TemplateGroup , qtp.RenditionMode  , qtp.TemplateFile, qst.ModDate , tc.CategoryCode , tc.CategoryName ,mqc.UsedField, qst.Count
                            FROM Questions qst
                            INNER JOIN MapQuestionContent mqc on qst.ID = mqc.QuestionID
                            INNER JOIN MapClientQuestionTemplates mcqt ON  qst.QuestionTemplateID = mcqt.ID AND mcqt.isEnabled = '1' AND mcqt.isActive = 'Y'
                            LEFT JOIN QuestionTemplates qtp ON mcqt.QuestionTemplateID = qtp.ID  AND qtp.isEnabled = '1'
                            LEFT JOIN TemplateCategories tc ON tc.ID = qtp.TemplateCategoryID AND tc.isEnabled = '1'
                            left join Users usr on usr.ID = mqc.UserID and  usr.isEnabled = '1'
                            left join MapClientUser mcu on mcu.UserID = usr.ID and  mcu.isEnabled = '1'
                            WHERE  mcu.ClientID = {$this->session->getValue('instID')} AND mcu.UserID = {$this->session->getValue('userID')} AND mqc.ContentID = {$input['contentID']} and mqc.isEnabled = '1' and mqc.isActive = 'Y' AND qst.isEnabled = '1'
                            group by qst.ID
                        ";
        }
        else
        {
            $query  = "   SELECT SQL_CALC_FOUND_ROWS qst.ID, qst.Title, qst.QuestionTemplateID ,qtp.TemplateTitle,qtp.ID as qtID , qtp.TemplateGroup , qtp.RenditionMode  , qtp.TemplateFile, qst.ModDate , tc.CategoryCode , tc.CategoryName ,mqc.UsedField, qst.Count
                            FROM Questions qst
                            INNER JOIN MapQuestionContent mqc on qst.ID = mqc.QuestionID
                            INNER JOIN MapClientQuestionTemplates mcqt ON  qst.QuestionTemplateID = mcqt.ID AND mcqt.isEnabled = '1' AND mcqt.isActive = 'Y'
                            LEFT JOIN QuestionTemplates qtp ON mcqt.QuestionTemplateID = qtp.ID  AND qtp.isEnabled = '1'
                            LEFT JOIN TemplateCategories tc ON tc.ID = qtp.TemplateCategoryID AND tc.isEnabled = '1'
                            left join Users usr on usr.ID = mqc.UserID and  usr.isEnabled = '1'
                            left join MapClientUser mcu on mcu.UserID = usr.ID and  mcu.isEnabled = '1'
                            WHERE  mcu.ClientID = {$this->session->getValue('instID')} AND mqc.ContentID = {$input['contentID']} and mqc.isEnabled = '1' and mqc.isActive = 'Y' AND qst.isEnabled = '1'
                            group by qst.ID
                        ";
        }

        Site::myDebug("--------questionListModel");
        Site::myDebug($questionlist);

        $query = $query.$limit;
        $this->myDebug("THis is my debug");
        $this->myDebug($query);
        $questionlist       = $this->db->getRows($query);
        $perm2                   = $this->db->getRows("SELECT FOUND_ROWS() as cnt");
        $totalRec               = $perm2[0]['cnt'];
        */
		$condition      = ($condition != '')?$condition:'-1';
        $questionlist   = $this->db->executeStoreProcedure('AssetUsageQuestionList',   array( $this->session->getValue('instID'), $input['contentID'],'-1','-1',$input['pgnstart'],$input['pgnstop'],$condition ) );
        return $questionlist;
	/*$questlist          = '<questions>';

        $qtp                = new QuestionTemplate();
        $templateLayouts    = $qtp->templateLayout();
        $verbose = new Verbose();

        $i=0;
        if(!empty($questionlist) && ! empty( $questionlist['RS'] ) )
        {
            $questlist         .= '<questcount>'.$questionlist['TC'].'</questcount>';
            foreach($questionlist['RS'] as $question)
            {
                $questionlist[$i]["TemplateLayout"] = $this->getAssociateValue($templateLayouts,$question["QuestionTemplateID"]);

                $url = $this->layout->imagePath.'/questiontemplate/'.$question['TemplateFile'].'.png';
                $url ='<div class="RecInfo questtypeicon listicon pngfixicon" style="float:left;width:30px;background-image:url(\''. $this->layout->imagePath.'/questiontemplate/'.$question['TemplateFile'].'.png\');" title="'.$question['TemplateTitle'].'" ></div>';

                if($this->checkRight('QuestPreview'))
                {
                    if($question['qtID'] == 37 ||$question['qtID'] == 38)
                    {
                        $previewUrl='<a class="previewicon listicon pngfixicon" href="javascript:void(0);"  onclick="javascript:ltdPreview(\''.$question['ID'].'\',\''.$question['QuestionTemplateID'].'\');return false;"></a>';
                    }
                    else
                    {
                        $previewUrl='<a class="previewicon listicon pngfixicon" href="javascript:void(0);" onclick="javascript:preview(\'listing\',\''.$question['ID'].'\',\''.$question['QuestionTemplateID'].'\',\''.$question['RenditionMode'].'\');return false;" title="'.$verbose->getConstVal(array('help','ttp-preview')).'"></a>';
                    }
                }
                else
                {
                    $previewUrl='<span class="dspreviewicon listicon pngfixicon" title = "No Access"></span>';
                }
                $questPreviewRight= ($this->checkRight('QuestPreview'))?1:0;
                $usedColumn = ($question['UsedField'] != '')?$question['UsedField']:'NA';
                $questlist .= " <question>
                                <title>".$this->wrapText($question['Title'], TEXTWRAPLENGTH)."</title>
                                <questId>{$question['ID']}</questId>
                                <templateId>{$question['QuestionTemplateID']}</templateId>
                                <renditionmode>{$question['RenditionMode']}</renditionmode>
                                <questpreviewright>{$questPreviewRight}</questpreviewright>
                                <templatefile>{$question['TemplateFile']}</templatefile>
                                <qtId>{$question['qtID']}</qtId>
                                <usedcolumn>{$usedColumn}</usedcolumn>
                                </question>";
            }
        }
	$questlist .= '</questions>';
	echo $questlist;
        die;*/
        //return $questionlist;
   }

    /**
    * gets list of media from specified directory path.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $dirPath        directory filepath.
    * @return   array   $mediafound     list of media file names.
    *
    */

   function getMediaListFromDir($dirPath)
   {
        $extensions     = $this->cfgApp->uploadFormats;
        $dir            = opendir($dirPath);
        while(false != ($file = readdir($dir)))
        {
            if(($file != '.') and ($file != '..'))
            {
                $ext = $this->findExt($file);
                if(in_array($ext, $extensions))
                {
                    $mediafound[] = $file;
                }
            }
        }
        return $mediafound;
   }

   /**
    * Get Asset details from asset id
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array   $arrAssetInfo   Asset(s) Details
    *
    */
    function assetInfo($input)
    {
        $arrAssetInfo = array();
        Site::myDebug("--------assetInfo");
        Site::myDebug( $this->input );
        $input['assetID'] = trim($input['assetID'], ',');


        /*
        $displayFields = "SQL_CALC_FOUND_ROWS cntt.ID, cntt.ContentType, cntt.FileName, cntt.Title, cntt.ContentInfo, cntt.Keywords,date_format(cntt.ModDate,'%a %D %b %Y %H:%i') as ModDate,
                            concat(usr.FirstName,' ',usr.LastName) as FullName, cntt.ContentHeight as Height,cntt.ContentWidth as Width, cntt.ContentSize as Size, cntt.AddDate as AddDate,
                            cntt.Thumbnail as Thumbnail, cntt.Count, cntt.Duration, cntt.OriginalFileName ";
        $qry          = "SELECT {$displayFields}
                            FROM    Content cntt
                            LEFT JOIN ContentMembers cm on cm.ContentID = cntt.ID AND cm.isEnabled = '1'
                            LEFT JOIN Users usr on usr.ID = cntt.UserID
                            WHERE cntt.ID IN ({$input['assetID']}) AND cntt.isEnabled = '1'
                            GROUP BY cntt.ID ";
         $arrAssetInfo = $this->db->getRows($qry);
         */
        $assetInfo          = $this->db->executeStoreProcedure('GetAssetInfo',array( $input['assetID'] ),'nocount');       
        return $assetInfo;
    }

    /**
    * GET URL to Download asset.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   json    {assetUrl:'".$destPath."'}  Asset URL
    *
    */
    public function downloadMedia(Array $input)
    {

        $input['assetType'] = strtolower($input['assetType']);
		$flag	=	0;
    
        if ( $input['assetType'] == "image"  ||  $input['assetType'] == "video" )
        {
            $fileRootPath        = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => "assets/{$input['assetType']}s/original/") );
        }
        else
        {
            $fileRootPath        = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => "assets/{$input['assetType']}s/") );
        }

        $source = $fileRootPath.$input['currentname'];
       
       if ($this->registry->site->cfg->S3bucket) {
            $source = str_replace($this->registry->site->cfg->rootPath . '/', "", $source);
            $source1 = str_replace($this->registry->site->cfg->rootPath . '/', "", $source);
            $source = s3uploader::getCloudFrontURL($source);
        }   
        Site::myDebug("--------downloadMedia");
        Site::myDebug($source);

        if ( is_file($source) && file_exists($source) )
        {
             if($input['oldname'] == "")
            {
                $oldname = $input['currentname'];
            }
            $oldname = ($input['oldname'] == "") ? $input['currentname'] : $input['oldname'];
            $guid           = uniqid('Asset');
            $imageExt       = $this->findExt($oldname);
            $fileTempPath   = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets/downloads/') );
            $folder_path    = $fileTempPath.$guid."/";
           
           
             $updatetitle = $input['title'].".".$imageExt;
            // Site::myDebug('---- '.__METHOD__.' KHOJARE19 --- '.$updatetitle);
             //Site::myDebug($input);
           // }            
            $dest = $folder_path.$updatetitle;      
            //print_r($folder_path); 
            //print_r($source);  print_r($dest); 
            @mkdir($folder_path,0777);
            @copy($source, $dest);
            
           // exit;
            // $APPCONFIG->tempDataPath             $this->cfgApp->QuizCSSImageUnzipTempLocation
            $destPath = 'authoring/download/f:'.$updatetitle.'|path:'.$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->tempAssetsDwn.$guid.'/|rand:'.$guid;            
        }
        else
        {
            
            if ($this->registry->site->cfg->S3bucket) {
				
				$objInfo = s3uploader::getObject(awsBucketName, $source1);
				if(!empty($objInfo)){
					if ($input['oldname'] == "") {
						$oldname = $input['currentname'];
					}
					$oldname = ($input['oldname'] == "") ? $input['currentname'] : $input['oldname'];
					$guid = uniqid('Asset');
					$imageExt = $this->findExt($oldname);
					$fileTempPath = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'assets/downloads/'));
					$folder_path = $fileTempPath . $guid . "/";
					$updatetitle = $input['title'] . "." . $imageExt;

					$dest = $folder_path . $updatetitle;
					@mkdir($folder_path, 0777);
					@copy($source, $dest);
					
					//$destPath = 'authoring/download/f:'.$updatetitle.'|path:'.$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->tempAssetsDwn.$guid.'/|rand:'.$guid;

					$destPath =  $source;
					$flag	=	1;
				}else{
					$destPath = '';
				}
            }
            else
            {
                $destPath = '';
            }
        }
        return "{'assetUrl':'".$destPath."','flag':'".$flag."'}";
    }


    public function saveAssetContent(array $input)
    {
       global $DBCONFIG;
        $assetTmpPath   = $this->getDataPath( array('mainDirPath' => 'temp', 'subDirPath' => 'assets') );

        $this->tempPath         = $assetTmpPath; // $this->cfg->rootPath.'/'.$this->cfgApp->QuizCSSImageUnzipTempLocation;


        $data                   = html_entity_decode(strip_tags($input["data"]));
        $objJSONtmp             = new Services_JSON();
        $outObj                 = $objJSONtmp->decode($data);
        $assetUrl               = '';
        $assetThumbUrl          = '';

        Site::myDebug("--------saveAssetContentModel");
        Site::myDebug($input);
        Site::myDebug($outObj);


        if( !empty($outObj) )
        {
            if( $outObj->flag != 'N' )
            {
                // imgtype
                $this->absPthToDstSvr   = $this->cfg->rootPath.'/'.$this->cfgApp->EditorImagesUpload;
                $outObj->imgtype  = strtolower( $outObj->imgtype );
                if ( $outObj->imgtype == "image" || $outObj->imgtype == "video"  )
                {
                    $this->absPthToDstSvr  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.$outObj->imgtype.'s/original/') );
                    $this->absPthToDstSvrThumb  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.$outObj->imgtype.'s/thumb/') );
                }
                else
                {
                    $this->absPthToDstSvr  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.$outObj->imgtype.'s/') );
                }


                if(!empty($outObj->src))
                {
                    $file   = basename($outObj->src);
                    $fileTempPath = $this->tempPath.$file;
                }
                if( !empty($outObj->thumb_src) )
                {
                    $thumb_file = basename($outObj->thumb_src);
                    $thumbTempPath = $this->tempPath.$thumb_file;
                }
                $fileExt = $outObj->imgext;
                if( copy($fileTempPath, $this->absPthToDstSvr.$file) )
                {
                    /*** code for calculating duration - starts ***/
                    $duration = $this->calcDuration($this->absPthToDstSvr.$file);
                    /*** code for calculating duration - ends ***/

                    $contentType = $outObj->imgtype;
                    if( !empty($thumb_file) )
                    {
                        if(@copy($thumbTempPath, $this->absPthToDstSvrThumb.$thumb_file))
                        {
                            @unlink($assetTmpPath.$thumb_file);
                        }
                    }
                    @unlink($assetTmpPath.$file);
                    // Delete Old Values
                    $arrAssetInfo = $this->assetInfo( $input );
                    if ( $arrAssetInfo )
                    {
                        $assetInfo = $arrAssetInfo['0'];
                        // Take bkp of current Files
                        $bkpMainMediaDirectory      = $this->absPthToDstSvr."backup/";
                        $bkpMainMediaDirectoryThumb      = $this->absPthToDstSvrThumb."backup/";
                        // $bkpPreviewMediaDirectory   = $this->absPthToDstSvr."mediabkp/preview/";
                        if ( ! file_exists($bkpMainMediaDirectory)  )
                        {
                            mkdir($bkpMainMediaDirectory,0777);
                            mkdir($bkpMainMediaDirectoryThumb,0777);
                        }
                        $bkpDate = time();

                        // copies  and renames original file to bkp location
                        rename( $this->absPthToDstSvr.$assetInfo['FileName'], $bkpMainMediaDirectory.$bkpDate.'_'.$assetInfo['FileName'] );


                        if (  in_array($assetInfo['ContentType'], array('Image', 'Video' )  )  )
                        {
                            // copies and renames thumbnail file to bkp location
                            rename( $this->absPthToDstSvrThumb.$assetInfo['Thumbnail'], $bkpMainMediaDirectoryThumb.$bkpDate."_".$assetInfo['Thumbnail'] );
                        }

                    }

                    // Update DB with new Values
                    $query  = "UPDATE Content
                                    SET ModBY = '{$this->session->getValue('userID')}', ModDate = '{$this->currentDate()}',
                                        isEnabled = '1',
                                        ContentHeight = '{$outObj->imgheight}', ContentWidth = '{$outObj->imgwidth}', ContentSize ='{$outObj->imgsize}',
                                        Duration = '{$duration}'
                                    WHERE ID = {$input['assetID']} ";
                                    // , OriginalFileName = '{$outObj->actual_img_name}'    FileName = '{$file}', ContentInfo = '{$file}',

                    $this->db->execute($query);
                    if ( in_array($assetInfo['ContentType'], array('Image', 'Video' )  )  )
                    {
                        // $assetThumbUrl  =   $this->cfg->wwwroot.'/'.$this->cfgApp->EditorImagesUpload.$thumb_file;
                        // $assetUrl       =   $this->cfg->wwwroot.'/'.$this->cfgApp->EditorImagesUpload.$file;
                        $assetThumbUrl = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.$outObj->imgtype.'s/thumb/', 'protocol' => 'http' ) ).$assetInfo['Thumbnail'];
                        $assetUrl = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.$outObj->imgtype.'s/original/', 'protocol' => 'http') ).$assetInfo['FileName'];
                    }
                    if ( $assetInfo['ContentType'] == 'Image' )
                    {
                        $margin = (100 - (int)$outObj->thumb_height) / 2;
                    }
                    else
                    {
                        $margin = 0;
                    }
                }
            }
        }

        if(strtolower($input['ContentType'])=="image")
        {
            if( !empty($outObj) )
            {
                $file_name=basename($outObj->src);
            }
            else
            {
                $file_name=$input['ImgName'];
                // $assetUrl       = $this->cfg->wwwroot.'/'.$this->cfgApp->EditorImagesUpload.$input['ImgName'];
                // $assetThumbUrl  = $this->cfg->wwwroot.'/'.$this->cfgApp->EditorImagesUpload."thumb_".$input['ImgName'];
                $assetUrl       = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.strtolower($input['ContentType']).'s/original/', 'protocol' => 'http' ) ).$input['ImgName'];
                $assetThumbUrl  = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/'.strtolower($input['ContentType']).'s/thumb/', 'protocol' => 'http' ) )."thumb_".$input['ImgName'];

            }
             $imgRootPath =  $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/') ).$file_name;
             $thumbRootPath = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/thumb/') )."thumb_".$file_name;

            if(intval($input['w']) > 0 && intval($input['h']) > 0)
            {
                $imageobj = new SimpleImage();
                // $imgRootPath =  $fileTempPath;
                list($width, $height, $type, $attr) = getimagesize($imgRootPath);
                $imageobj->load($imgRootPath);
                $crop = array(
                    'x' => $input['x'],
                    'y' => $input['y'],
                    'w' => $input['w'],
                    'h' => $input['h']
                );

                $imageobj->resize($input['w'], $input['h'],$crop);
                $imageobj->save($imgRootPath);
                 //for creating previe image
                /*
                if(strtolower($input['ContentType'])=="image")
                {
                    $imageobj->load($this->absPthToDstSvr.$file_name);
                    if($width > 300)
                    {
                        $imageobj->resizeToWidth(300);
                    }
                    else
                    {
                        $imageobj->resize($width, $height);
                    }
                    // $imageobj->save($this->absPthToDstSvr.'/preview/'.$file_name);
                }
                 */
                //previes creation code end

                //for thumbimage
                $imageobj->load($imgRootPath);
                if((int)$width > (int)$height || (int)$width == (int)$height)
                {
                    $imageobj->resizeToWidth("100");
                }
                else
                {
                    $imageobj->resizeToHeight("100");
                }
                $imageobj->save($thumbRootPath);
                //for thumbimage END

                // Update DB with new Values for new width and height
                list($new_width, $new_height, $new_type, $new_attr) = getimagesize($imgRootPath);
                $new_size = round(filesize($imgRootPath)/1024);
                $query  = "UPDATE Content SET ModBY = '{$this->session->getValue('userID')}', ModDate = '{$this->currentDate()}',
                            ContentHeight = '{$new_height}', ContentWidth = '{$new_width}', ContentSize ='{$new_size}' WHERE ID = {$input['assetID']} ";

                $this->db->execute($query);
            }
        }
        // When new file uploaded, rename the new file as per old file name in database
        if( !empty($outObj) )
        {
            // Rename Uplaoded File to original File and thumbnail
            rename($this->absPthToDstSvr.$file, $this->absPthToDstSvr.$assetInfo['FileName']);

            // Rename Uplaoded File to original File and thumbnail
            rename($this->absPthToDstSvrThumb.$thumb_file, $this->absPthToDstSvrThumb.$assetInfo['Thumbnail']);
        }

        return "{'assetID':'{$input['assetID']}', 'assetUrl':'{$assetUrl}', 'assetThumbUrl':'{$assetThumbUrl}', 'thumbWidth':'{$outObj->thumb_width}', 'thumbHeight':'{$outObj->thumb_height}', 'margin':'{$margin}', 'OriginalFileName':'{$assetInfo['OriginalFileName']}', 'FileName':'{$assetInfo['OriginalFileName']}', 'FileExt':'{$outObj->imgext}' , 'FileType':'{$outObj->imgtype}'  }";
    }


    public function getViewType($input)
    {
        $cookie = new Cookie();
        // $viewType = 1;
        Site::myDebug("------getViewType");
        Site::myDebug($input);
        Site::myDebug("----TESTgetViewType");
        Site::myDebug( $cookie->getValue('mediaViewType') );
        if ( $input['viewType'] != '' )
        {
            $viewType = $input['viewType'];
            if ( $cookie->getValue('mediaViewType') != $viewType )
            {
                $cookie->write('mediaViewType', $viewType);
            }
        }
        else
        {
            if ( $cookie->getValue('mediaViewType') )
            {
                $viewType = $cookie->getValue('mediaViewType');
            }
            else
            {
                $cookie->write('mediaViewType', 2); // 1 => List View
                $viewType = 2;
            }
        }
        return $viewType;
    }
    
    public function nxtUpdate($mediaID, $title, $keywords, $ContentSize, $Thumbnail, $OriginalFileName, $FileName)
    {
        global $DBCONFIG;
        $query      = "UPDATE Content SET Title='{$title}' , Keywords='{$keywords}', ModDate='{$this->currentDate()}'";
        if($FileName != ''){
            $query .= ", FileName='{$FileName}'";
        }
        if($ContentSize != ''){
            $query .= ", ContentSize='{$ContentSize}'";
        }
        if($Thumbnail != ''){
            $query .= ", Thumbnail='{$Thumbnail}'";
        }
        if($OriginalFileName != ''){
            $query .= ", OriginalFileName='{$OriginalFileName}'";
        }
        $query .= " WHERE ID='{$mediaID}' and isEnabled = '1' ";
        $status     = $this->db->execute($query);
        $data       = array(0,$this->session->getValue('userID'),6,$mediaID,$title,'Edited',$this->currentDate(),'1',$this->session->getValue('accessLogID'));
        $this->db->executeStoreProcedure('ActivityTrackManage',$data);
        return $status;
    }

    /*
     *  Description :- When Question Delete Asset using in that question will be remove from asset usage
     *  Author      :- Akhlack
     *  Date        :- 1st October , 2015
     */
    public function assetUsageCounterManage( $mapRepoId = '' ){
        
       if( $mapRepoId > 0 ){
           $updateQuery     = "";
           $excuteQuery     = "";
           //$query           = "SELECT MPQ.QuestionID,Q.RefId FROM MapRepositoryQuestions as MPQ INNER JOIN Questions as Q ON MPQ.QuestionID=Q.ID WHERE  MPQ.ID='".$mapRepoId."' ";
           $query           ="SELECT QuestionID FROM MapRepositoryQuestions as MPQ WHERE  MPQ.ID='".$mapRepoId."' ";
           $result          = $this->db->getRows($query);
           $questionId      = $result[0]['QuestionID'];
          // $questionRefId   = $result[0]['RefId'];
         
            if(  $questionId > 0 ){
               
               $CheckQry    = "SELECT ID From MapRepositoryQuestions WHERE  QuestionId = '".$questionId."' AND isEnabled=1";
               $res         = $this->db->getRows( $CheckQry );
               if( $res[0]['ID'] == '' ){
                    $query       = "SELECT COUNT(*) as CNT,ContentId,QuestionId FROM MapQuestionContent WHERE QuestionId='".$questionId."' AND isEnabled=1 GROUP BY ContentId";
                    $response    = $this->db->getRows( $query );
                    
                    if( is_array( $response ) && !empty( $response )){                    
                        $updateQuery = "";
                        foreach( $response as $key => $val  ){
                            //$updateQuery.= " UPDATE Content SET Count = Count -".$val['CNT']." WHERE ID='".$val['ContentId']."' ; ";
                            $updateQueryMapQuestionContent.= " UPDATE MapQuestionContent SET isEnabled = '0' WHERE ContentId='".$val['ContentId']."' AND QuestionId='".$val['QuestionId']."' ; ";
                        }
                        if( $updateQueryMapQuestionContent != "" ){
                            $status     = $this->db->execute( $updateQueryMapQuestionContent );                        
                        }
                    }
               }
           }
       } 
    }
    
    
}// Class Defn: Media
?>