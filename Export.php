<?php

/**
 * Description of export
 *
 * @author moreshwar.madaye
 */
class Export extends Site {  

    /**
     * Construct new export instance
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return
     *
     */
    function export() {
        parent::Site();
    }

    /**
     * generate csv file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    array $reports
     * @return   void
     *
     */
    function generateCsv($reports) {
        if (!empty($reports)) {
            global $VERBOSECONST;
            $module = strtoupper($this->getInput('reportType')) . 'REPORT';
            $csv = '';

            for ($i = 0; $i < $VERBOSECONST[$module]['columns']; $i++) {
                $csv .= "{$VERBOSECONST[$module][label][$i]},";
            }
            $csv .="\n";
            if (!empty($reports)) {
                foreach ($reports as $row) {
                    for ($i = 0; $i < $VERBOSECONST[$module]['columns']; $i++) {
                        $value = $row->{$VERBOSECONST[$module]['field'][$i]};
                        $csv .= "'$value',";
                    }
                    $csv .="\n";
                }
            }

            header("Content-type: text/x-csv");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment; filename=report.csv");
            header("Content-Length: " . strlen($csv));
            echo $csv;
            exit;
        }
    }

    /**
     * copy images in zip folder when export questions in zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $imgpath
     * @param    string $temp_path
     * @return   void
     *
     */
    function createImage($imgpath, $temp_path) {
        global $Total_Img;
        $mda = new Media();
	$imgpath=str_replace("&quot;","",$imgpath);
	$img_name=explode('?',$imgpath);
	$img_name =  basename($img_name[0]); 
	        //$sp=$this->cfg->rootPath."/".$this->cfgApp->EditorImagesUpload.$img_name;
        $ext = substr(strrchr($img_name, "."), 1);
        if (in_array($ext, $this->cfgApp->videoFormats)) {
            $sp = $mda->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/original/')) . $img_name;
        } else if (in_array($ext, $this->cfgApp->audioFormats)) {
            $sp = $mda->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/audios/')) . $img_name;
        } else if (in_array($ext, $this->cfgApp->imgFormats)) {
            $sp = $mda->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/')) . $img_name;
        }
	
        $dp = $temp_path . "/media/" . $img_name;
        $Total_Img[] = $img_name;

        /*         * * File export from S3 Bucket  ** */
        if ($this->registry->site->cfg->S3bucket) {
	    
            /* $img_name = explode('?', $img_name);
            $img_name = $img_name[0];
            $dp = $temp_path . "/media/" . $img_name;
            $sp = $imgpath; */
			$source = str_replace($this->registry->site->cfg->rootPath . '/', "", $sp);
			
			$fp = fopen($dp, "wb");
			if (($object = s3uploader::getObject(awsBucketName, $source, $fp)) !== false) {
				Site::myDebug('------- object == ');
				Site::myDebug($object);
			}else{
				Site::myDebug('------- Unable to copy image from s3 ------------');
			}
		
        }else{
			if($imgpath) {
		
			   if(!copy($sp, $dp)){
					Site::myDebug('------- false copy== ');
			    }
			}
		}		

    }

    /**
     * format image tag and return imagename,path and extenstion.
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $imgtag
     * @param    string $temp_path
     * @return   void
     *
     */
    function getImageDetail($imagetag) {
        preg_match('/(src)=([\'|\"])([^"|^\']*)(([\'|\"])\s)/i', $imagetag, $res);
        $imgpath = $res[3];
        $imgArr = explode("/", $imgpath);
        $imgname = $imgArr[count($imgArr) - 1];
        $ext = substr(strrchr($imgname, "."), 1);
        return array($imgpath, $imgname, $ext);
    }

    /**
     * format image tag and return videoname,path and extenstion.
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string $imgtag
     * @param    string $temp_path
     * @return   void
     *
     */
    function getVideoDetail($mediatag) {
        preg_match('/(data)=([\'|\"])([^"|^\']*)(([\'|\"])\s)/i', $mediatag, $res);
        $mediapath = $res[3];
        $mediaArr = explode("/", $mediapath);
        $medianame = $mediaArr[count($mediaArr) - 1];
        $ext = substr(strrchr($medianame, "."), 1);
        return array($mediapath, $medianame, $ext);
    }

    /**
     * question exported in QTI2.1 format in zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $Total_Img
     * @param    array $input
     * @param    interger $asmtID
     * @return   stirng
     *
     */
	
	function saveExportHistory(array $input, $EntityID, $entityTypeId){
		$QuestIDRes = $input['questids'];
		$QuestIDRes = substr($QuestIDRes,1,-1);
		if ($input['selectall'] == "true") {
			$questionsListSql = "SELECT `ID` as TotalQuestionCnt FROM `MapRepositoryQuestions` WHERE EntityID=".$EntityID." AND EntityTypeID = ".$entityTypeId." AND QuestionID !=0 AND isEnabled=1";
			$questionsLists = $this->registry->site->db->getRows($questionsListSql);
			$QuestIDResCnt = $questionsLists;         
		}else{
			$QuestIDResCnt = explode('||',$QuestIDRes);
		}
		Site::myDebug("Question Count --------");
		Site::myDebug(count($QuestIDResCnt));
		
		$data = array(
			'ExportTitle' => $input['exportname'],
			'EntityName' => $this->getEntityTitle($entityTypeId, $EntityID),
			'ExportType' => $input['exporttype'],
			'EntityTypeID' => $entityTypeId,
			'EntityID' => $EntityID,
			'ExportBy' => $this->session->getValue('userID'),
			'ExportDate' => $this->currentDate(),
			'QuestCount' => count($QuestIDResCnt),
			'status' 	=> 'processing',
			'isEnabled' => '0'
		);
        $Exportid = $this->db->insert("ExportHistory", $data);
		return $Exportid;
	}
	
	function getQuestionDetailsGenerateXML(array $input, $AssessmentID, $EntityID, $entityTypeId){
		
		if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.advJSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
		
		if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                //$filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                $filter = "mrq.QuestionID in ({$questids})  AND mrq.entityID in (".$AssessmentID.") AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
			
			$questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
			
			$site1 = & $this->registry->site;
            $questionsListSql = "SELECT QuestionID FROM `MapRepositoryQuestions` WHERE EntityID=".$AssessmentID." AND EntityTypeID=".$entityTypeId." AND QuestionID !=0  AND isEnabled=1";
            $questionsLists = $site1->db->getRows($questionsListSql);
            $QuestionIDs = implode(",",$this->registry->site->arrayColumn($questionsLists, 'QuestionID'));          
            $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", "mrq.QuestionID in (".$QuestionIDs.") AND mrq.entityID in (".$AssessmentID.")", $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        }

        return $questions; 
    }
	
	function generateExportXML(array $questions, $temp_path_root, $temp_path_web, $Exportid){
		$qst 				= new Question();
		$objJSONtmp 		= new Services_JSON();
		$metadata 			= new Metadata();
		$auth 				= new Authoring();
		$i					=0;
		$fail_cnt 			= 0;
		$imsManifestArray 	= array();
        $metadataArray 		= array();
        $ExportDetails 		= array();
        
		
		foreach ($questions as $questlist) {
			$TemplateFile = $questlist['TemplateFile'];
			$isExport = $questlist['isExport'];
			$sJson = $questlist["advJSONData"];
			$sJson = $qst->removeMediaPlaceHolder($sJson);
			//$objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
			$objJsonTemp = $objJSONtmp->decode($sJson);
			Site::myDebug("Export advJsonData --------");
			Site::myDebug($objJSONtmp->decode(($sJson)));
			$objJson = $objJsonTemp;
			//print_r($objJson); die();
			
			$Quest_title = $this->formatJson($this->replaceQuot($objJson->{'question_title'}->{'text'}), 0);
			
			$Quest_stem 	=	$objJson->{'question_stem'}->{'text'};  //Question Text 
			$Quest_stem		= 	$this->cleanData($objJson->{'question_stem'}->{'text'}, $temp_path_root, $temp_path_web, 0);
			$Quest_text 	= 	$Quest_title;
			
			$incorrectFeedback		= 	$this->cleanData($objJson->{'global_incorrect_feedback'}->{'text'}, $temp_path_root, $temp_path_web); // global_incorrect_feedback
			$correctFeedback		= 	$this->cleanData($objJson->{'global_correct_feedback'}->{'text'}, $temp_path_root, $temp_path_web); // global_correct_feedback
			
			//$hint = $this->formatJson($objJson->{'hint'}->{'text'},0);
			
			$exhibit_type = $this->formatJson($objJson->{'exhibit'}[0]->{'exhibit_type'},0);
			if($exhibit_type){
				$exhibit		= 	$this->cleanData($objJson->{'exhibit'}[0]->{'path'}, $temp_path_root, $temp_path_web);
				
			}else{
				$exhibit = $this->formatJson($objJson->{'exhibit'}[0]->{'path'},1,0);
			}
			$notes_editor = $this->formatJson($objJson->{'notes_editor'}->{'text'},0);
						
			// Hotspot
			$Quest_media		= 	$this->cleanData($objJson->{'question_media'}->{'media'}, $temp_path_root, $temp_path_web); //Question Media 		
			$maxChoiceSelection = $this->formatJson($objJson->{'max_choice_selection'}->{'text'}, 0);

			//For Eassy
			$essayText = $this->formatJson($objJson->{'essay_text'}->{'text'},0);

			//Score
			$totalScore = $objJson->{'settings'}->{'score'};
			
			if($objJson->{'templatetype'}->{'text'})
			{
				$TemplateFile=$objJson->{'templatetype'}->{'text'};
			}

			$templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1/" . $TemplateFile . ".php";
			
			Site::myDebug("templateFilePath --------");
			Site::myDebug($templateFilePath);
			
			ob_start();
			if (file_exists($templateFilePath)) { //commented isExport condition as we are supporting all templates for QTI2.1 
				include($templateFilePath);
				$xmlStr = ob_get_contents();
				ob_end_clean();
					
				/* create multiple xml files with each question */
				
				Site::myDebug("questions-IDDD");
				Site::myDebug($questlist['ID']);
				
				$myFile = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
				$menifest_resources = "{$Exportid}.xml";
				
				$xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
				$domxml = new DOMDocument('1.0');
				$domxml->preserveWhiteSpace = false;
				$domxml->formatOutput = true;
				$domxml->loadXML($xmlStr);
				$domxml->save($myFile);
				
				//Question level Metadata code starts
				//  Get Assigned Metadata for the question
				$arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
				$QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
				if (!empty($QuestAssignedMetadata['RS'])) {
					foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
						$arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
						if ($arrMetadataValues) {
							foreach ($arrMetadataValues as $metadataValues) {
								$arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
								if ($arrValue['4'] >= 1) {
									$mkeyname = $arrMetadata['KeyName'];
									$metadataArray[$mkeyname] = $arrValue['2'];
								}
							}
						}
					}
				}
				//Question level Metadata code ends here
				//Question Level Taxonomy Code starts
				$site = & $this->registry->site;
				
				$entityTaxoListSql = "SELECT t.Taxonomy,t.ID,t.ParentID  FROM Classification c INNER JOIN Taxonomies t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 AND c.ClassificationType= 'taxonomy' and c.EntityID=" . $questlist['ID'];
				$entityTaxoList = $site->db->getRows($entityTaxoListSql); //print_r($entityTaxoList); die();
				$taxonomyArray = array();
				foreach ($entityTaxoList as $taxKey => $taxValue) {
						$this->idAllStr = '';
						if($taxValue['Taxonomy']){
							$taxonomyArray[$taxKey]['Taxonomy'] = $taxValue['Taxonomy']; //"skill";
							$taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
						}
				}
				
				$entityTagListSql = "SELECT t.Tag,t.ID  FROM Classification c INNER JOIN Tags t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 AND c.ClassificationType= 'tag' and c.EntityID=" . $questlist['ID'];
				$entityTagList = $site->db->getRows($entityTagListSql); //print_r($entityTagList); die();
				Site::myDebug("entityTagList");
				Site::myDebug($entityTagList);
				$tagArray = array();
				foreach ($entityTagList as $tagKey => $tagValue) {
						$this->idAllStr = '';
						if($tagValue['Tag']){
							$tagArray[$tagKey]['Tag'] = $tagValue['Tag']; //"skill";
							//$taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
						}
				}
				//Question Level Taxonomy code ends here

				$imsManifestArray[$i]['question_title_identifier'] = strip_tags($Quest_text);
				$imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
				$imsManifestArray[$i]['question_text'] = strip_tags($Quest_text);
				//$imsManifestArray[$i]['entity_metadata_array'] = '';  // Assessment/Bank level metadata
				//$imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata
				//$imsManifestArray[$i]['entity_taxonomy_string'] = '';  //Assessment/Bank level taxonomy
				//$imsManifestArray[$i]['taxonomy_string'] = $taxonomyArray;  // Question level taxonomy
				//$imsManifestArray[$i]['tag_array'] 		= $tagArray;  // Question level Tag
				//print_r($imsManifestArray); die();
				$questionTextImagesArray[$i] = $this->getAssetsFromJSONdata($Quest_stem);
				$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($correctFeedback));
				$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($incorrectFeedback));
				
				Site::myDebug('choices Image');
				Site::myDebug($objJson->{'choices'});
				
				if (!empty($objJson->{'choices'})) {
					foreach ($objJson->{'choices'} as $key => $value) {
						$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->media));
						//$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->text));
						$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
						$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->correct_feedback));
						$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->incorrect_feedback));
						/* Matching Choice Answer Image */
						if(!empty($value->textforeachblank)){
							$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->textforeachblank[0]->text));
						}
						/**/
					   
					}
				}
				// HotSpot Media 
				if ( !empty( $Quest_media )  ){
					$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($Quest_media));
				}
				
				if($exhibit_type){
					$exhibit = $this->formatJson($objJson->{'exhibit'}[0]->{'path'},0);
					$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($exhibit));
				}
				
		/** Add Media in manifest file  For Hints **/
					if (!empty($objJson->{'hints'})) {
						foreach ($objJson->{'hints'} as $key => $value) {                                
							$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
						}
					}           
					/***/ 
					
					/** Add Media in manifest file  For Distractors **/
					if (!empty($objJson->{'distractors'})) {
						foreach ($objJson->{'distractors'} as $key => $value) {                                
							$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
						}
					}           
					/***/   
					
				$i++;	
				$data	=	array(
							'exportHistoryID' 	=> 	$Exportid,
							'sequence' 			=> 	$questlist['QSequence'],
							'mapRepoID' 		=> 	$questlist['ID'],
							'shortcode' 		=> 	$questlist['SearchName'],
							'questionID' 		=> 	$questlist['QuestionID'],
							'version' 			=> 	$questlist['Version'],
							'questionTitle' 	=> 	$questlist['Title'],
							'templateType' 		=> 	$questlist['CategoryName'],
							'questionModifiedDate' => 	$questlist['ModDate'],
							'advJSONData' 		=> 	$questlist['advJSONData'],
							'ModBY' 			=> 	$this->session->getValue('userName'),
				);
				$ExportDetails[$Exportid][] = $this->db->insert("ExportDetails", $data);
			}else{
				$fail_cnt=$fail_cnt+1;
			}
		}		
		Site::myDebug("ExportDetails --------");
		Site::myDebug($ExportDetails);
		/* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1/ImsManifest.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $myFileI = "{$temp_path_root}/imsmanifest.xml";
        $xmlmaniStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlmaniStr);
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xmlmaniStr);
        $domxml->save($myFileI);
		
		 /* end of creating manifest */
		 
		$webpath = $temp_path_web . ".zip";
		$zipfile = $temp_path_root . ".zip";
		$srczippath = $temp_path_root;
		$auth->makeZip($srczippath, $zipfile);
		
		return $webpath;
	}
	
	function sendExportNotification($requestuseremailID, $requestuserfullName, $webpath){
		$emailSubject = 'QuAD export notification.';
		$emailLogo = $this->registry->site->cfg->wwwroot.'/assets/imagesnxt/email-logo.png';
		$form='';
		$toEmail=$requestuseremailID;
		$data = array();
		$data['email_logo'] = $emailLogo;
		$data['name'] = $requestuserfullName;
		$data['download_url'] = "{$webpath}";
		
		$verInfo		=	$this->getVerboseEmailTemplate('Export Notification',$this->session->getValue('userID'));
		$templateInfo	= array_merge($data,$verInfo);
		
		return $this->sendTemplateMail($emailSubject,$templateInfo, $toEmail, 'exportnotification.php',$form);
		
	}
	
	
	function exportQuestionWithQti2_1(array $input) {
		
		$metadata 			= new Metadata();
        $qst 				= new Question();
        $objJSONtmp 		= new Services_JSON();
		$auth 				= new Authoring();
        $imsManifestArray 	= array();
        $metadataArray 		= array();
        $entityMetaArray 	= array();
        $entityTypeId 		= $input['EntityTypeID'];
        $EntityID 			= $input['EntityID'];
        $requestuserfullName = $input['requestuserfullName'];
        $requestuseremailID = $input['requestuseremailID']; 
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }
		
		$ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;
        if ($input['action'] == "exportq") {
            $Exportid = $this->saveExportHistory($input, $EntityID, $entityTypeId);
        }else {
            $guid = uniqid();
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/temp.xml";
            $menifest_resources = "temp.xml";
        }
		
		$AssessmentID = $EntityID;
        $EntityID = -1;
//        $entityTypeId = -1;           
		$questions =	$this->getQuestionDetailsGenerateXML($input, $AssessmentID, $EntityID, $entityTypeId);
		$rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
        $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
        mkdir($temp_path_root, 0777, true);
        mkdir($temp_path_root . "/media", 0777);
        $fail_cnt = 0;
        Site::myDebug("questions-List");
		Site::myDebug($questions);
		
		if (!empty($questions)) {
			$webpath	=	$this->generateExportXML($questions, $temp_path_root, $temp_path_web, $Exportid);
		}
				
        if (!isset($input['opt'])) {
            if ($input['action'] == "exportq") {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
                } else {
                    $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
                }

                $dbdata1 = array(
                    'QuestCount' => $total_quest
                );
                //$this->db->update('ExportHistory', $dbdata1, $condition1);
            }

			$sendMaliFlag	=	$this->sendExportNotification($requestuseremailID, $requestuserfullName, $webpath);    
			$exportStatus	=	($webpath)?'success':'failed';	
            $dataExportHistory = array(
                'isEnabled' => '1',
                'status' => $exportStatus
            );            
            $conditionExportHistory = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '0'", "status = 'processing'"));
            $this->db->update("ExportHistory", $dataExportHistory, $conditionExportHistory);
                    
           
            print "{$webpath}";
        } else {
            return $guid;
        }
	}
	 
	
	 
	 
	 
    function exportQuestionWithQti2_1_bk(array $input) {
//        ini_set('error_reporting', E_ALL);
//        $start = microtime(true); 
        
      
       
        $metadata = new Metadata();
        $qst = new Question();
        $objJSONtmp = new Services_JSON();
        $imsManifestArray = array();
        $metadataArray = array();
        $entityMetaArray = array();
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $requestuserfullName = $input['requestuserfullName'];
        $requestuseremailID = $input['requestuseremailID']; 
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }
        
        $auth = new Authoring();
//        if ($entityTypeId == 2) {
//            $Assessment = new Assessment();
//            $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
//                $EntityID,
//                $this->session->getValue('userID'),
//                $this->session->getValue('isAdmin'),
//                $this->session->getValue('instID')
//            ), 'nocount');
//            $end = microtime(true);
//            $time = number_format(($end - $start), 2);
//            print("<p>AssessmentSettings loaded in <b>". $time. "</b> seconds</p>");
//            print("<pre>");
//            print_r($AssessmentSettings);
//            print("</pre>");            
//            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
//            $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
//            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
//            $Entity_name = $this->getValueArray($AssessmentSettings, "Name");
//            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
//            $settingTimer = $this->getAssociateValue($AssessmentSettings, 'Minutes'); //maxTime
//            $attempts = $this->getAssociateValue($AssessmentSettings, 'Tries'); // maxAttempts 
//        } else if ($entityTypeId == 1) {
//            $Bank = new Bank();
//            $BankSettings = $Bank->bankDetail($EntityID);
//            $qshuffle = "yes";
//            $Entity_score_flag = "yes";
//            $Entity_score = "";
//            $Entity_name = $this->getValueArray($BankSettings, "BankName");
//        } else {
//            $qshuffle = "yes";
//            $Entity_score_flag = "yes";
//            $Entity_score = "";
//        }        
        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;
        if ($input['action'] == "exportq") {
            $QuestIDRes = $input['questids'];
            $QuestIDRes = substr($QuestIDRes,1,-1);
            if ($input['selectall'] == "true") {
                $questionsListSql = "SELECT `ID` as TotalQuestionCnt FROM `MapRepositoryQuestions` WHERE EntityID=".$EntityID." AND EntityTypeID = ".$entityTypeId." AND QuestionID !=0 AND isEnabled=1";
                $questionsLists = $this->registry->site->db->getRows($questionsListSql);
                $QuestIDResCnt = $questionsLists;         
            }else{
                $QuestIDResCnt = explode('||',$QuestIDRes);
            }
            Site::myDebug("Question Count --------");
            Site::myDebug(count($QuestIDResCnt));
            $data = array(
                'ExportTitle' => $input['exportname'],
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => count($QuestIDResCnt),
                'isEnabled' => '0'
            );
            $Exportid = $this->db->insert("ExportHistory", $data);
        } else {
            $guid = uniqid();
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/temp.xml";
            $menifest_resources = "temp.xml";
        }
        $AssessmentID = $EntityID;
        $EntityID = -1;
//        $entityTypeId = -1;
        if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                //$filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                $filter = "mrq.QuestionID in ({$questids})  AND mrq.entityID in (".$AssessmentID.") AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.advJSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
       
        if ($input['selectall'] != "true") {            
            $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        }else{
            $site1 = & $this->registry->site;
            $questionsListSql = "SELECT QuestionID FROM `MapRepositoryQuestions` WHERE EntityID=".$AssessmentID." AND EntityTypeID=".$entityTypeId." AND QuestionID !=0  AND isEnabled=1";
            $questionsLists = $site1->db->getRows($questionsListSql);
            $QuestionIDs = implode(",",$this->registry->site->arrayColumn($questionsLists, 'QuestionID'));          
            $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", "mrq.QuestionID in (".$QuestionIDs.") AND mrq.entityID in (".$AssessmentID.")", $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        }
//        $end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>QuestionsLists loaded in <b>". $time. "</b> seconds</p>");
            
        //$questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        //Site::myDebug('-------- DeliveryQuestionList--------');
        //Site::myDebug($questions);
        //die();
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
        $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
        mkdir($temp_path_root, 0777, true);
        mkdir($temp_path_root . "/media", 0777);
        $fail_cnt = 0;
        
        
                 Site::myDebug("questions-List");
            Site::myDebug($questions);
        if (!empty($questions)) {
            $i=0;
            foreach ($questions as $questlist) {
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["advJSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                //$objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                $objJsonTemp = $objJSONtmp->decode($sJson);
                  Site::myDebug("Export advJsonData --------");
            Site::myDebug($objJSONtmp->decode(($sJson)));
                $objJson = $objJsonTemp;
                //print_r($objJson); die();
				
				$Quest_title = $this->formatJson($this->replaceQuot($objJson->{'question_title'}->{'text'}), 0);
				
				$Quest_stem =$objJson->{'question_stem'}->{'text'};  //Question Text 
				$textDocumentArray = array('textDocument' => $Quest_stem, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'question_stem'}->{'text'});
				$Quest_stem = $this->changeImageSRC($textDocumentArray); 
				$Quest_stem = $this->remove_img_titles($Quest_stem);
				$Quest_stem = $this->remove_img_metadata($Quest_stem);
				$Quest_stem	=	$this->addCdataInText($Quest_stem); 
				                
//               	$Quest_Inst_text 	=	$objJson->{'instruction_text'}->{'text'};  //instruction_text
//				$textDocumentArray 	= 	array('textDocument' => $Quest_Inst_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'instruction_text'}->{'text'});
//				$Quest_Inst_text 	= 	$this->changeImageSRC($textDocumentArray);
//				$Quest_Inst_text 	= 	$this->remove_img_titles($Quest_Inst_text);
//				$Quest_Inst_text 	= 	$this->remove_img_metadata($Quest_Inst_text);
//				$Quest_Inst_text	=	$this->addCdataInText($Quest_Inst_text); 
				
                $Quest_text = $Quest_title;
				
                $incorrectFeedback 	= $this->formatJson($objJson->{'global_incorrect_feedback'}->{'text'},0);  // global_incorrect_feedback
				$textDocumentArray 	= 	array('textDocument' => $incorrectFeedback, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'global_incorrect_feedback'}->{'text'});
				$incorrectFeedback 	= 	$this->changeImageSRC($textDocumentArray);
				$incorrectFeedback 	= 	$this->remove_img_titles($incorrectFeedback);
				$incorrectFeedback 	= 	$this->remove_img_metadata($incorrectFeedback);
				$incorrectFeedback	=	$this->addCdataInText($incorrectFeedback); 
				
                $correctFeedback = $this->formatJson($objJson->{'global_correct_feedback'}->{'text'},0); // global_correct_feedback
				$textDocumentArray 	= 	array('textDocument' => $correctFeedback, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'global_correct_feedback'}->{'text'});
				$correctFeedback 	= 	$this->changeImageSRC($textDocumentArray);
				$correctFeedback 	= 	$this->remove_img_titles($correctFeedback);
				$correctFeedback 	= 	$this->remove_img_metadata($correctFeedback);
				$correctFeedback	=	$this->addCdataInText($correctFeedback); 
				
                //$hint = $this->formatJson($objJson->{'hint'}->{'text'},0);
                
                $exhibit_type = $this->formatJson($objJson->{'exhibit'}[0]->{'exhibit_type'},0);
				if($exhibit_type){
					$exhibit = $this->formatJson($objJson->{'exhibit'}[0]->{'path'},0);
					$textDocumentArray 	= 	array('textDocument' => $exhibit, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $exhibit);
					$exhibit 	= 	$this->changeImageSRC($textDocumentArray);
					$exhibit 	= 	$this->remove_img_titles($exhibit);
					$exhibit 	= 	$this->remove_img_metadata($exhibit);
					$exhibit	=	$this->addCdataInText($exhibit); 
				}else{
					$exhibit = $this->formatJson($objJson->{'exhibit'}[0]->{'path'},1,0);
				}
                $notes_editor = $this->formatJson($objJson->{'notes_editor'}->{'text'},0);
                
                
                // Hotspot
                $Quest_media = $this->formatJson($objJson->{'question_media'}->{'media'}, 0);//Question Media 				
				$textDocumentArray = array('textDocument' => $Quest_media, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $Quest_media);
				$Quest_media = $this->changeImageSRC($textDocumentArray); 
				$Quest_media = $this->remove_img_titles($Quest_media);
				$Quest_media = $this->remove_img_metadata($Quest_media);
				$Quest_media	=	$this->addCdataInText($Quest_media); 
				
                $maxChoiceSelection = $this->formatJson($objJson->{'max_choice_selection'}->{'text'}, 0);

                //For Eassy
                $essayText = $this->formatJson($objJson->{'essay_text'}->{'text'},0);

                //Score
                $totalScore = $objJson->{'settings'}->{'score'};
                
                if($objJson->{'templatetype'}->{'text'})
                {
                    $TemplateFile=$objJson->{'templatetype'}->{'text'};
                }

                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1/" . $TemplateFile . ".php";
                
                Site::myDebug("templateFilePath --------");
                Site::myDebug($templateFilePath);
                
                ob_start();
                if (file_exists($templateFilePath)) { //commented isExport condition as we are supporting all templates for QTI2.1 
                    include($templateFilePath);
                    $xmlStr = ob_get_contents();
                    ob_end_clean();
                        
                    /* create multiple xml files with each question */
                    
                    Site::myDebug("questions-IDDD");
            Site::myDebug($questlist['ID']);
                    
                    $myFile = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
                    $menifest_resources = "{$Exportid}.xml";
                    /*
                    $fh2 = fopen($myFile, 'w');
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                    fwrite($fh2, $xmlStr);
                    fclose($fh2);
                    */
//                    print "<pre>";
//                    print_r($xmlStr);
//                    die('abc');
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                    $domxml = new DOMDocument('1.0');
                    $domxml->preserveWhiteSpace = false;
                    $domxml->formatOutput = true;
                    $domxml->loadXML($xmlStr);
                    $domxml->save($myFile);
                    
                    //Question level Metadata code starts
                    //  Get Assigned Metadata for the question
                    $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                    $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                    if (!empty($QuestAssignedMetadata['RS'])) {
                        foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                            $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                            if ($arrMetadataValues) {
                                foreach ($arrMetadataValues as $metadataValues) {
                                    $arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
                                    if ($arrValue['4'] >= 1) {
                                        $mkeyname = $arrMetadata['KeyName'];
                                        $metadataArray[$mkeyname] = $arrValue['2'];
                                    }
                                }
                            }
                        }
                    }
                    //Question level Metadata code ends here
                    //Question Level Taxonomy Code starts
                    $site = & $this->registry->site;
                    
                    $entityTaxoListSql = "SELECT t.Taxonomy,t.ID,t.ParentID  FROM Classification c LEFT JOIN Taxonomies t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 and c.EntityID=" . $questlist['ID'];
                    $entityTaxoList = $site->db->getRows($entityTaxoListSql); //print_r($entityTaxoList); die();
                    $taxonomyArray = array();
                    foreach ($entityTaxoList as $taxKey => $taxValue) {
                            $this->idAllStr = '';
							if($taxValue['Taxonomy']){
								$taxonomyArray[$taxKey]['Taxonomy'] = $taxValue['Taxonomy']; //"skill";
								$taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
							}
                    }
                    //Question Level Taxonomy code ends here

                    $imsManifestArray[$i]['question_title_identifier'] = strip_tags($Quest_text);
                    $imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
                    $imsManifestArray[$i]['question_text'] = strip_tags($Quest_text);
                    //$imsManifestArray[$i]['entity_metadata_array'] = '';  // Assessment/Bank level metadata
                    //$imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata
                    //$imsManifestArray[$i]['entity_taxonomy_string'] = '';  //Assessment/Bank level taxonomy
                    //$imsManifestArray[$i]['taxonomy_string'] = $taxonomyArray;  // Question level taxonomy
					//print_r($imsManifestArray); die();
                    $questionTextImagesArray[$i] = $this->getAssetsFromJSONdata($Quest_stem);
                    $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($correctFeedback));
                    $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($incorrectFeedback));
                    
                    Site::myDebug('choices Image');
                    Site::myDebug($objJson->{'choices'});
                    
                    if (!empty($objJson->{'choices'})) {
                        foreach ($objJson->{'choices'} as $key => $value) {
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->media));
                            //$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->text));
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->correct_feedback));
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->incorrect_feedback));
                            /* Matching Choice Answer Image */
                            if(!empty($value->textforeachblank)){
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->textforeachblank[0]->text));
                            }
                            /**/
                           
                        }
                    }
                    // HotSpot Media 
                    if ( !empty( $Quest_media )  ){
                        $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($Quest_media));
                    }
                    
					if($exhibit_type){
						$exhibit = $this->formatJson($objJson->{'exhibit'}[0]->{'path'},0);
						$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($exhibit));
					}
					
			/** Add Media in manifest file  For Hints **/
                        if (!empty($objJson->{'hints'})) {
                            foreach ($objJson->{'hints'} as $key => $value) {                                
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
                            }
                        }           
                        /***/ 
                        
                        /** Add Media in manifest file  For Distractors **/
                        if (!empty($objJson->{'distractors'})) {
                            foreach ($objJson->{'distractors'} as $key => $value) {                                
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getImagesFromJSONdata($value->text));
                            }
                        }           
                        /***/   
                        
                        
                        
                        
                      
                                        
					
                    $i++;			
					
                }else{
                    $fail_cnt=$fail_cnt+1;
                }
            }
            
        }
//	$end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>Media and Question XML exported in <b>". $time. "</b> seconds</p>");
        //print_r($questionTextImagesArray); die('hi');
		
        /* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1/ImsManifest.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $myFileI = "{$temp_path_root}/imsmanifest.xml";
        $xmlmaniStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlmaniStr);
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xmlmaniStr);
        $domxml->save($myFileI);
        
//        $end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>Manifest XML exported in <b>". $time. "</b> seconds</p>");

        /* end of creating manifest */

        if (!isset($input['opt'])) {
            if ($input['action'] == "exportq") {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
                } else {
                    $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
                }

                $dbdata1 = array(
                    'QuestCount' => $total_quest
                );
                //$this->db->update('ExportHistory', $dbdata1, $condition1);
            }

            $webpath = $temp_path_web . ".zip";
            $zipfile = $temp_path_root . ".zip";
            $srczippath = $temp_path_root;
            $auth->makeZip($srczippath, $zipfile);
            
//            $end = microtime(true);
//            $time = number_format(($end - $start), 2);
//            print("<p>ZIP created in <b>". $time. "</b> seconds</p>");
            
        /*    if ($this->cfg->S3bucket) {
                $webpath = $temp_path_web . ".zip";
                $S3webpath = str_replace($this->registry->site->cfg->wwwroot.'/', "", $webpath);
                $S3webpath = str_replace("//", "/", $S3webpath);
                $webpath = s3uploader::getCloudFrontURL($S3webpath);
                $S3ExportPath = str_replace($this->cfg->rootPath . '/', "", $zipfile);
                $S3ExportPath = str_replace("//", "/", $S3ExportPath);
                s3uploader::upload($zipfile, $S3ExportPath);
                unlink($zipfile);
                unlink($temp_path_root); 
            }   */
            
            $emailSubject = 'QuAD export notification.';
            $emailLogo = $this->registry->site->cfg->wwwroot.'/assets/imagesnxt/email-logo.png';
            $form='';
            $toEmail=$requestuseremailID;
            $data = array();
            $data['email_logo'] = $emailLogo;
            $data['name'] = $requestuserfullName;
			$data['download_url'] = "{$webpath}";
			
			$verInfo		=	$this->getVerboseEmailTemplate('Export Notification',$this->session->getValue('userID'));
			$templateInfo	= array_merge($data,$verInfo);
			
            $this->sendTemplateMail($emailSubject,$templateInfo, $toEmail, 'exportnotification.php',$form);
            
            $dataExportHistory = array(
                'isEnabled' => '1'
            );
            
            $conditionExportHistory = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '0'"));
         
            $this->db->update("ExportHistory", $dataExportHistory, $conditionExportHistory);
                    
//            $end = microtime(true);
//            $time = number_format(($end - $start), 2);
//            print("<p>Email sent in <b>". $time. "</b> seconds</p>");
//            print("<p>Total questions: ". count($questions) ."</p>");
//            print("<p>Question(s) successfully exported: ". (count($questions)-$fail_cnt) ."</p>");
//            print("<p>Question(s) failed: ". $fail_cnt ."</p>");
//            
//            die();
            
            print "{$webpath}";
        } else {
            return $guid;
        }
    }
	
	function exportQuestionWithQti2_1_Custom(array $input) {
//        ini_set('error_reporting', E_ALL);
//        $start = microtime(true);        
        $metadata = new Metadata();
        $qst = new Question();
        $objJSONtmp = new Services_JSON();
        $imsManifestArray = array();
        $metadataArray = array();
        $entityMetaArray = array();
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $requestuserfullName = $input['requestuserfullName'];
        $requestuseremailID = $input['requestuseremailID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }
        
        $auth = new Authoring();
       
        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;
        if ($input['action'] == "exportq") {
            $QuestIDRes = $input['questids'];
            $QuestIDRes = substr($QuestIDRes,1,-1);
            $QuestIDResCnt = explode('||',$QuestIDRes);
            Site::myDebug("Question Count --------");
            Site::myDebug(count($QuestIDResCnt));
            $data = array(
                'ExportTitle' => $input['exportname'],
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => count($QuestIDResCnt),
                'isEnabled' => '0'
            );
            $Exportid = $this->db->insert("ExportHistory", $data);
        } else {
            $guid = uniqid();
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Custom . $guid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Custom . $guid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/temp.xml";
            $menifest_resources = "temp.xml";
        }
        $AssessmentID = $EntityID;
        $EntityID = -1;
//        $entityTypeId = -1;
        if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                //$filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                $filter = "mrq.QuestionID in ({$questids})  AND mrq.entityID in (".$AssessmentID.") AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.advJSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
       
        if ($input['selectall'] != "true") {            
            $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        }else{
            $site1 = & $this->registry->site;
            $questionsListSql = "SELECT QuestionID FROM `MapRepositoryQuestions` WHERE EntityID=".$AssessmentID." AND EntityTypeID=".$entityTypeId." AND isEnabled=1";
            $questionsLists = $site1->db->getRows($questionsListSql);
            $QuestionIDs = implode(",",$this->registry->site->arrayColumn($questionsLists, 'QuestionID'));
            $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", "mrq.QuestionID in (".$QuestionIDs.") AND mrq.entityID in (".$AssessmentID.")", $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        }
//        $end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>QuestionsLists loaded in <b>". $time. "</b> seconds</p>");
            
        //$questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        //Site::myDebug('-------- DeliveryQuestionList--------');
        //Site::myDebug($questions);
        //die();
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Custom . $Exportid;
        $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Custom . $Exportid;
        mkdir($temp_path_root, 0777, true);
        mkdir($temp_path_root . "/media", 0777);
        $fail_cnt = 0;
        if (!empty($questions)) {
            $i=0;
            foreach ($questions as $questlist) {
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["advJSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                $objJson = $objJsonTemp;
                //print_r($objJson); die();
               
                //$Quest_stem = $this->formatJson($objJson->{'question_stem'}->{'text'}, 0);
                
				$Quest_text =$objJson->{'question_stem'}->{'text'};  //Question Text
				$textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'question_stem'}->{'text'});
				$Quest_text = $this->changeImageSRC($textDocumentArray);
				$Quest_text	=	$this->addCdataInText($Quest_text);
				
				$Quest_Inst_text = $objJson->{'instruction_text'}->{'text'};
                $textDocumentArray = array('textDocument' => $Quest_Inst_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => $objJson->{'instruction_text'}->{'text'});
				$Quest_Inst_text = $this->changeImageSRC($textDocumentArray);
				$Quest_Inst_text	=	$this->addCdataInText($Quest_Inst_text);
				
				$Quest_title = $this->formatJson($this->replaceQuot($objJson->{'question_title'}->{'text'}), 0);
				
                $incorrectFeedback = $this->formatJson($objJson->{'global_incorrect_feedback'}->{'text'},1);
                $correctFeedback = $this->formatJson($objJson->{'global_correct_feedback'}->{'text'},1);
                $hint = $this->formatJson($objJson->{'hint'}->{'text'},0);
                $notes_editor = $this->formatJson($objJson->{'notes_editor'}->{'text'},0);
                
                
                // Hotspot
                $Quest_media = $this->formatJson($objJson->{'question_media'}->{'media'}, 0);
                $maxChoiceSelection = $this->formatJson($objJson->{'max_choice_selection'}->{'text'}, 0);

                //For Eassy
                $essayText = $this->formatJson($objJson->{'essay_text'}->{'text'},0);

                //Score
                $totalScore = $objJson->{'settings'}->{'score'};
                
                if($objJson->{'templatetype'}->{'text'})
                {
                    $TemplateFile=$objJson->{'templatetype'}->{'text'};
                }

                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Custom/" . $TemplateFile . ".php";
                
                ob_start();
                if (file_exists($templateFilePath)) { //commented isExport condition as we are supporting all templates for QTI2.1 
                    include($templateFilePath);
                    $xmlStr = ob_get_contents();
                    ob_end_clean();
                        
                    /* create multiple xml files with each question */
                    $myFile = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
                    $menifest_resources = "{$Exportid}.xml";
                    /*
                    $fh2 = fopen($myFile, 'w');
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                    fwrite($fh2, $xmlStr);
                    fclose($fh2);
                    */
                    
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                    $domxml = new DOMDocument('1.0');
                    $domxml->preserveWhiteSpace = false;
                    $domxml->formatOutput = true;
                    $domxml->loadXML($xmlStr);
                    $domxml->save($myFile);
                    
                    //Question level Metadata code starts
                    //  Get Assigned Metadata for the question
                    $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                    $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                    if (!empty($QuestAssignedMetadata['RS'])) {
                        foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                            $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                            if ($arrMetadataValues) {
                                foreach ($arrMetadataValues as $metadataValues) {
                                    $arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
                                    if ($arrValue['4'] >= 1) {
                                        $mkeyname = $arrMetadata['KeyName'];
                                        $metadataArray[$mkeyname] = $arrValue['2'];
                                    }
                                }
                            }
                        }
                    }
                    //Question level Metadata code ends here
                    //Question Level Taxonomy Code starts
                    $site = & $this->registry->site;
                    
                    $entityTaxoListSql = "SELECT t.Taxonomy,t.ID,t.ParentID  FROM Classification c LEFT JOIN Taxonomies t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 and c.EntityID=" . $questlist['ID'];
                    $entityTaxoList = $site->db->getRows($entityTaxoListSql);
                    $taxonomyArray = array();
                    foreach ($entityTaxoList as $taxKey => $taxValue) {
                            $this->idAllStr = '';
                            $taxonomyArray[$taxKey]['Taxonomy'] = "skill";
                            $taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
                    }
                    //Question Level Taxonomy code ends here

                    $imsManifestArray[$i]['question_title_identifier'] = strip_tags($Quest_text);
                    $imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
                    $imsManifestArray[$i]['question_text'] = strip_tags($Quest_text);
                    $imsManifestArray[$i]['entity_metadata_array'] = '';  // Assessment/Bank level metadata
                    $imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata
                    $imsManifestArray[$i]['entity_taxonomy_string'] = '';  //Assessment/Bank level taxonomy
                    $imsManifestArray[$i]['taxonomy_string'] = $taxonomyArray;  // Question level taxonomy
					
                    $questionTextImagesArray[$i] = $this->getAssetsFromJSONdata($Quest_stem);

                    if (!empty($objJson->{'choices'})) {
                        foreach ($objJson->{'choices'} as $key => $value) {
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->media));
                            Site::myDebug($value->media);
                        }
                    }
                    // HotSpot Media 
                    if ( !empty( $Quest_media )  ){
                        $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($Quest_media));
                    }
                    
                    $i++;			
					
                }else{
                    $fail_cnt=$fail_cnt+1;
                }
            }
            
        }
//	$end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>Media and Question XML exported in <b>". $time. "</b> seconds</p>");
        //print_r($questionTextImagesArray); die('hi');
		
        /* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Custom/ImsManifest.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $myFileI = "{$temp_path_root}/imsmanifest.xml";
        $xmlmaniStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlmaniStr);
        $domxml = new DOMDocument('1.0');
        $domxml->preserveWhiteSpace = false;
        $domxml->formatOutput = true;
        $domxml->loadXML($xmlmaniStr);
        $domxml->save($myFileI);
        
//        $end = microtime(true);
//        $time = number_format(($end - $start), 2);
//        print("<p>Manifest XML exported in <b>". $time. "</b> seconds</p>");

        /* end of creating manifest */

        if (!isset($input['opt'])) {
            if ($input['action'] == "exportq") {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
                } else {
                    $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
                }

                $dbdata1 = array(
                    'QuestCount' => $total_quest
                );
                //$this->db->update('ExportHistory', $dbdata1, $condition1);
            }

            $webpath = $temp_path_web . ".zip";
            $zipfile = $temp_path_root . ".zip";
            $srczippath = $temp_path_root;
            $auth->makeZip($srczippath, $zipfile);
            
//            $end = microtime(true);
//            $time = number_format(($end - $start), 2);
//            print("<p>ZIP created in <b>". $time. "</b> seconds</p>");
            
            if ($this->cfg->S3bucket) {
                $webpath = $temp_path_web . ".zip";
                $S3webpath = str_replace($this->registry->site->cfg->wwwroot.'/', "", $webpath);
                $S3webpath = str_replace("//", "/", $S3webpath);
                $webpath = s3uploader::getCloudFrontURL($S3webpath);
                $S3ExportPath = str_replace($this->cfg->rootPath . '/', "", $zipfile);
                $S3ExportPath = str_replace("//", "/", $S3ExportPath);
                s3uploader::upload($zipfile, $S3ExportPath);
                unlink($zipfile);
                unlink($temp_path_root);
            }
            
            $emailSubject = 'QuAD export notification.';
            $emailLogo = $this->registry->site->cfg->wwwroot.'/assets/imagesnxt/email-logo.png';
            $form='';
            $toEmail=$requestuseremailID;
            $data = array();
            $data['email_logo'] = $emailLogo;
            $data['name'] = $requestuserfullName;
            $data['download_url'] = "{$webpath}";
            
            $this->sendTemplateMail($emailSubject,$data, $toEmail, 'exportnotification.php',$form);
            
            $dataExportHistory = array(
                'isEnabled' => '1'
            );
            
            $conditionExportHistory = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '0'"));
            
            $Exportid = $this->db->update("ExportHistory", $dataExportHistory, $conditionExportHistory);

//            $end = microtime(true);
//            $time = number_format(($end - $start), 2);
//            print("<p>Email sent in <b>". $time. "</b> seconds</p>");
//            print("<p>Total questions: ". count($questions) ."</p>");
//            print("<p>Question(s) successfully exported: ". (count($questions)-$fail_cnt) ."</p>");
//            print("<p>Question(s) failed: ". $fail_cnt ."</p>");
//            
//            die();
            
            print "{$webpath}";
        } else {
            return $guid;
        }
    }

    /**
     * * PAI02 :: sprint 3 ::  QUADPS-45
     * question exported in QTI2.1 for Realize format in zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $Total_Img
     * @param    array $input
     * @param    interger $asmtID
     * @return   stirng
     *
     */
    function exportQuestionWithQti2_1_Realize(array $input) {
        $metadata = new Metadata();
        $auth = new Authoring();
        $class = new Classification();

        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }


        if ($entityTypeId == 2) {
            $Assessment = new Assessment();
            //$AssessmentSettings = $Assessment->asmtDetail($EntityID);
            $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
                $EntityID,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID')
                    ), 'nocount');
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
            $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $Entity_name = $imsManifestArray['AssessmentName'] = $this->getValueArray($AssessmentSettings, "Name");
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $settingTimer = $this->getAssociateValue($AssessmentSettings, 'Minutes'); //maxTime
            $attempts = $this->getAssociateValue($AssessmentSettings, 'Tries'); // maxAttempts 
        } else if ($entityTypeId == 1) {
            $Bank = new Bank();
            $BankSettings = $Bank->bankDetail($EntityID);
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
            $Entity_name = $this->getValueArray($BankSettings, "BankName");
        } else {
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
        }
        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;

        if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName,qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');

        //echo "==========<pre>";print_r($questions);die('**--**');
        //========================== VALIDATIONS - Start =======================================================
        $qst = new Question();
        $responseData = array();
        $objJSONtmp = new Services_JSON();
        $responseData['status'] = true;
        $responseData['msgRespWar'] = '';
       
        //========================== VALIDATIONS - End =========================================================


        if ($responseData['status'] == true) {
            if ($input['action'] == "exportq") {
                $data = array(
                    'ExportTitle' => $input['exportname'],
                    //'ExportPackageName' => $imsManifestArray['AssessmentName'],
                    'ExportType' => $input['exporttype'],
                    'EntityTypeID' => $entityTypeId,
                    'EntityID' => $EntityID,
                    'ExportBy' => $this->session->getValue('userID'),
                    'ExportDate' => $this->currentDate(),
                    'QuestCount' => $i,
                    'isEnabled' => '1'
                );
                $Exportid = $this->db->insert("ExportHistory", $data);
            } else {
                $guid = uniqid();
                $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $guid;
                $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $guid;
                mkdir($temp_path_root, 0777, true);
                mkdir($temp_path_root . "/media", 0777);
                $qtifol = "{$temp_path_root}/temp.xml";
                $menifest_resources = "temp.xml";
            }
            $rootSecInc = 1;
            $sec = "";
            $total_quest = 0;
            $totalquestions = $imsManifestArray['totalquestions'] = count($questions);

            $this->myDebug("This is NEWEST Question Count");
            $this->myDebug($totalquestions);


            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $imsManifestArray['AssessmentName'] . '_' . $Exportid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $imsManifestArray['AssessmentName'] . '_' . $Exportid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/images", 0777);
            // mkdir($temp_path_root . "/testitems", 0777);
            //Assessment/Bank level Metadata code starts
            //  Get Assigned Metadata for Assessment/Bank
            $entityMetadata = array("EntityID" => $EntityID, "EntityTypeID" => $entityTypeId);
            $entityAssignedMetadata = $metadata->metaDataAssignedList($entityMetadata, "assign");

            if (!empty($entityAssignedMetadata['RS'])) {
                foreach ($entityAssignedMetadata['RS'] as $assignMetadata) {
                    $arrMetadataVal = @explode($this->cfgApp->metaDataKeyValSeparator, $assignMetadata['KeyValues']);
                    if ($arrMetadataVal) {
                        foreach ($arrMetadataVal as $metadataVal) {
                            $entityArrValue = @explode($this->cfgApp->metaDataKeyValSeparator, $metadataVal);
                            if ($entityArrValue['4'] >= 1) {
                                $mkeyname = $assignMetadata['KeyName'];
                                $entityMetaArray[$mkeyname] = $entityArrValue['2'];
                            }
                        }
                    }
                }
            }
            //Assessment/Bank level Metadata code ends here

            if (!empty($questions)) {
                set_time_limit(0);
                if ($questionType == "C") {
					//  * * PAI02 :: sprint 3 ::  QUADPS-45
                    /* Composite package Start */

                    $sections = $this->arraySorting($questions);
                    $this->myDebug('Composite package Array');
                    $this->myDebug($sections);
                    $sect = 0;
                    foreach ($sections as $keySec => $questions) {
                        $this->myDebug($questions);
                        $itemBody = array();
                        $responseProcessing = '';
                        $responseDeclaration = '';
						$maxScoreQuest=0;
						
                        foreach ($questions as $questlist) {
                            $question_xml = $questlist;
                            $TemplateFile = $questlist['TemplateFile'];
                            $isExport = $questlist['isExport'];
                            $sJson = $questlist["JSONData"];
                            $sJson = $this->removeMediaPlaceHolderExport($sJson);
                            $objJsonTemp = $objJSONtmp->decode($sJson);
                            if (!isset($objJsonTemp))
                                $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));

                            $objJson = $objJsonTemp;
							
							// Composite package Settings
							$sqlMRQ ="SELECT SectionInfo from MapRepositoryQuestions where ID = ".$questlist['ParentID'];
							$rowInfo = $this->db->getSingleRow($sqlMRQ);
							$SectionInfo = $rowInfo['SectionInfo'];
					
                            /* RESPONSE */

                            $optionsList = $objJson->{'choices'};
                            $responseDeclaration.=$this->createResponseXml($optionsList, $TemplateFile, $questlist['ID'],$objJson);


                            /* ItemBody Start */

                            $Quest_title = $this->formatJson(htmlspecialchars($objJson->{'question_title'}), 0, 0);
                            $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'}, 0);

                            $Quest_text = $objJson->{'question_text'}[0]->{'val1'};  //Question Text
                            $QuesttextAltTagVal = $objJson->{'question_text'}[0]->{'val2'}; //Alt Tag
                            // Alter Image src from question text 
                            if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
                                $textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $objJson->{'question_text'}[0]->{'val1'});

                                $Quest_text = $this->changeImageSRC($textDocumentArray);

                                $regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
                                if (preg_match_all($regex, $Quest_text, $QuestTextNew)) {
                                    if ($QuestTextNew) {
                                        foreach ($QuestTextNew as $val) {
                                            $Quest_text = str_replace($val, '$$$', $Quest_text);
                                        }
                                    }
                                }$newQuestionText = explode("$$$", $Quest_text);
                                $p = 0;
                                $Quest_text = '';
                                foreach ($newQuestionText as $val) {
				    $valnew = $this->formatJson($val, 1, 0);
                                    $valnew = $valnew . '' . $QuestTextNew[0][$p] . '';
                                    $Quest_text.=$valnew;
                                    $p++;
                                }
                            } else {
				$Quest_text = $this->formatJson($objJson->{'question_text'}[0]->{'val1'}, 1, 1);
                            }
							$ItemTemplateFile=$TemplateFile;	
							if($TemplateFile=="StaticPageTextTab") {
								$ItemTemplateFile="StaticPageText";	
							}
							$itemBody[]=array('TemplateFile' => $ItemTemplateFile,
                            'ItemData'=>$this->createItemBodyXml($Quest_text, $optionsList, $TemplateFile, $questlist['ID'], $objJson, $temp_path_root,$questlist["JSONData"]));

                            /* ItemBody End */


						/* ResponseProcessing */
						$ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
						$responseProcessing.=$this->createResponseProcessingXml($ind_quesScore, $TemplateFile, $questlist['ID'],$objJson);


                            $Quest_title = $this->formatJson($objJson->{'question_title'}, 0);
                            $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'},0,0);

                            $correctFeedback = $objJson->{'correct_feedback'}[0]->{'val1'};  //Correct Feedback text
                            $corrFedAltTagVal = $objJson->{'correct_feedback'}[0]->{'val2'}; //Alt Tag

                            $incorrectFeedback = $objJson->{'incorrect_feedback'}[0]->{'val1'};  //Incorrect Feedback text
                            $incorrFedAltTagVal = $objJson->{'incorrect_feedback'}[0]->{'val2'}; //Alt Tag
                            // For Hint 
                            //$hint = $this->formatJson($objJson->{'hint'});
                            $hint = $objJson->{'hint'}[0]->{'val1'};  //Hint text
                            $hintAltTagVal = $objJson->{'hint'}[0]->{'val2'}; //Alt Tag
                            //For Eassy
                            $essayText = $this->formatJson($objJson->{'essay'});

                            //For LTD
                            $imageSrc = $this->formatJson($objJson->{'image'});

                            $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                            
                            $ind_quesdifficulty = $objJson->{'metadata'}[1]->{'val'};
                            $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);
                            foreach ($objJson->{'metadata'} as $metaSettings) {
                                if ($metaSettings->text == "Max_Score") {
									$maxScoreQuest+=$metaSettings->val; //Max Score
								}
							}

                            if ($TemplateFile == 'Essay') {
                                $maxScoreQuest+= $objJson->{'metadata'}[1]->{'val'};
                                ; //Max Score
                                                        }


                            //Question level Metadata code starts
                            //  Get Assigned Metadata for the question
                            $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                            $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                            Site::myDebug('------QuestAssignedMetadata');
                            Site::myDebug($QuestAssignedMetadata);
                            if (!empty($QuestAssignedMetadata['RS'])) {
                                foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                                    $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                                    if ($arrMetadataValues) {
                                        foreach ($arrMetadataValues as $metadataValues) {
                                            $arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
                                            if ($arrValue['4'] >= 1) {
                                                $mkeyname = $arrMetadata['KeyName'];
                                                $metadataArray[$mkeyname] = $arrValue['2'];
                                            }
                                        }
                                    }
                                }
                            }
                            Site::myDebug('------metadataArray');

                            if (!preg_grep("/skill/i", array_keys($metadataArray))) {
                                $responseData['msgRespWar'] = 'Some Question has no skill metadata';
                                Site::myDebug('------metadataArray--- skill');
                            }
                            Site::myDebug($metadataArray);
                            //Question level Metadata code ends here
                            //Question Level Taxonomy Code starts
                            $site = & $this->registry->site;
                            $taxonomyListSql = "SELECT GROUP_CONCAT(SUBSTRING_INDEX( Taxonomy, ' ',1 )) AS taxo FROM Classification
				LEFT JOIN Taxonomies ON Taxonomies.ID=Classification.ClassificationID AND Taxonomies.isEnabled=1
				WHERE Classification.isEnabled=1 and EntityID=$questlist[ID]";
                            $taxonomyList = $site->db->getSingleRow($taxonomyListSql);
                            //Question Level Taxonomy code ends here

                            $i++;
                            //$questionList[] = $objJson->{'question_title'};
                            //$imsManifestArray[$i]['entity_title'] = $Entity_name;
                            $imsManifestArray[$sect + 1]['question_title_identifier'] = $objJson->{'question_title'};
                        $imsManifestArray[$sect + 1]['question_id_identifier'] = "QUE_" .$keySec; /*$questlist['ParentID'];*/
                            $imsManifestArray[$sect + 1]['question_text'] = $objJson->{'question_text'}[0]->{'val1'};
                            $imsManifestArray[$sect + 1]['image'] = $objJson->{'image'}[0]->{'val1'};
                            $imsManifestArray[$sect + 1]['entity_metadata_array'] = $entityMetaArray;  // Assessment/Bank level metadata
                            $imsManifestArray[$sect + 1]['metadata_array'] = $metadataArray;           // Question level metadata
                            // $imsManifestArray[$i]['entity_taxonomy_string'] = explode_filtered(',', $entityTaxoList['taxo']);  //Assessment/Bank level taxonomy
                            // $imsManifestArray[$i]['taxonomy_string'] = explode_filtered(',', $taxonomyList['taxo']);  // Question level taxonomy


                            $questionTextImagesArray[$sect + 1][$i] = $this->getAssetsFromJSONdata($imsManifestArray[$sect + 1]['question_text']);
							
							if (!empty($Quest_Inst_text)) { // For Static Page.
							$questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($Quest_Inst_text));
							}
							
                            if (!empty($objJson->{'choices'})) {
                                foreach ($objJson->{'choices'} as $key => $value) {
                                    $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($value->val2));
                                    Site::myDebug($value->val2);
                                }
                            }
							if (!empty($objJson->{'image'}) && $TemplateFile == 'LabelDiagram') { // for LabelDiagram
                                $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($objJson->{'image'}));
                            }
							
                            if (!empty($objJson->{'image'}[0]->{'val1'})) { // For MCSS/MCMS text with Image template
                                $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($objJson->{'image'}[0]->{'val1'}));
                                Site::myDebug($objJson->{'image'}[0]->{'val1'});
                            }
                            unset($entityMetaArray);
                            unset($metadataArray);
                        }
                        //Site::myDebug("imsManifestArray File Data");					 
                        //$this->myDebug($questionTextImagesArray);

                        $m = 1;
                        foreach ($questionTextImagesArray as $key => $value) {

                            if (!empty($value)) {
                                $ka = 0;
                                foreach ($value as $k => $v) {
                                    if (!empty($v)) {
                                        foreach ($v as $a => $b) {
                                            $compositAsset[$key][$ka++] = $b;
                                        }
                                    }
                                }
                                $m++;
                            }
                        }
                        unset($questionTextImagesArray);
                        $questionTextImagesArray = $compositAsset;		
						
                        $sect++;
                        $TemplateFile = 'CompositePackage'; //$questlist['TemplateFile'];
                        $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/" . $TemplateFile . ".php";

                        ob_start();
			if (file_exists($templateFilePath)) {
							//commented isExport condition as we are supporting all templates for QTI2.1 
                            include($templateFilePath);
                            $xmlStr = ob_get_contents();
                            //echo " =========== "; echo "<pre>";print_r($xmlStr);die('---------');
                            ob_end_clean();

                            /* create multiple xml files with each question */

                            $qtifol = "{$temp_path_root}/QUE_{$keySec}.xml";/*$questlist['ParentID']*/

                            Site::myDebug($temp_path_root);
                            Site::myDebug($qtifol);
                            $myFile = $qtifol;
							/*
							$fh2 = fopen($myFile, 'w');
                            $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
							fwrite($fh2, $xmlStr);
							fclose($fh2);
							*/
                            $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
							$domxml = new DOMDocument('1.0');
							$domxml->preserveWhiteSpace = false;
							$domxml->formatOutput = true;
							$domxml->loadXML($xmlStr);
							$domxml->save($myFile);
                        }
                    }

                    /* Composite package End */
                } else {
                    foreach ($questions as $questlist) {
                        $question_xml = $questlist;
                        $TemplateFile = $questlist['TemplateFile'];
                        $isExport = $questlist['isExport'];
                        $sJson = $questlist["JSONData"];
                        $sJson = $this->removeMediaPlaceHolderExport($sJson);
                        $this->myDebug("This is New Json");
                        $this->myDebug($sJson);
                        $this->myDebug($question_xml);

                        $objJsonTemp = $objJSONtmp->decode($sJson);
                        if (!isset($objJsonTemp))
                            $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));

                        $objJson = $objJsonTemp;
						//print_r($objJson);die('**************');

                        if ($objJson->{'choices'}) {
                            foreach ($objJson->{'choices'} as $choice) {
                                // Alter Image src from question text 
                                $choiceArray = array('textDocument' => $this->formatCdata($choice->val2, 0), 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $this->formatCdata($choice->val2, 0));
                                $val2 = $this->changeImageSRC($choiceArray);
                                $choicesOpt[] = $choice->val1;
                                $choices[] = array(
                                    'val1' => ($choice->val1),
                                    'val2' => $val2,
                                    'val3' => $this->formatCdata($choice->val3, 0),
                                    'val4' => $choice->val4 /* ,
                                          'val5' => ""  Alt Tag val */
                                );
                            }
                        }


                        $optionsList = $objJson->{'choices'};

                        $metedataList = $objJson->{'metadata'};

                        $metadataClassVal = '';
                        foreach ($metedataList as $mdt) {
                            if (($mdt->{'text'}) == "Class") {
                                $metadataClassVal = $mdt->{'val'};
                            }
                        }

                        $Quest_title = $this->formatJson(htmlspecialchars($objJson->{'question_title'}), 0, 0);
                        $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'},0,0);

			$Quest_text = $this->formatJson($objJson->{'question_text'}[0]->{'val1'}, 0, 0);  //Question Text
			  // Alter Image src from question text                       
                        if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
                            $textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $objJson->{'question_text'}[0]->{'val1'});

                            $Quest_text = $this->changeImageSRC($textDocumentArray);

                            $regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
                            if (preg_match_all($regex, $Quest_text, $QuestTextNew)) {
                                Site::myDebug('---QuestTextNew');
                                Site::myDebug($QuestTextNew);
                                if ($QuestTextNew) {
                                    foreach ($QuestTextNew as $val) {
                                        $Quest_text = str_replace($val, '$$$', $Quest_text);
                                    }
                                    Site::myDebug('---firstformatjson11111');
                                    Site::myDebug($Quest_text);
                                }
                            }$newQuestionText = explode("$$$", $Quest_text);
                            $p = 0;
                            $Quest_text = '';
                            foreach ($newQuestionText as $val) {
                                Site::myDebug('---firstformatjson111112222');
                                Site::myDebug($val);
                                //$valnew = htmlspecialchars($val);
				$valnew = $this->formatJson($val, 1, 0);
                                $valnew = $valnew . '' . $QuestTextNew[0][$p] . '';
                                $Quest_text.=$valnew;
                                $p++;
                            }
                            //$Quest_text.=implode(" ", $QuestTextNew[0]);  
                            Site::myDebug('---firstformatjson111112222');
                            Site::myDebug($Quest_text);
                        } else {
                            $Quest_text = $this->formatJson($objJson->{'question_text'}[0]->{'val1'}, 1, 0);
                        }

                        $correctFeedback = $objJson->{'correct_feedback'}[0]->{'val1'};  //Correct Feedback text
                        // $corrFedAltTagVal = $objJson->{'correct_feedback'}[0]->{'val2'}; //Alt Tag

                        $incorrectFeedback = $objJson->{'incorrect_feedback'}[0]->{'val1'};  //Incorrect Feedback text
                        //$incorrFedAltTagVal = $objJson->{'incorrect_feedback'}[0]->{'val2'}; //Alt Tag
                        // For Hint 
                        //$hint = $this->formatJson($objJson->{'hint'});
                        $hint = $objJson->{'hint'}[0]->{'val1'};  //Hint text
                        // $hintAltTagVal = $objJson->{'hint'}[0]->{'val2'}; //Alt Tag
                        //For Eassy
                        //$essayText = $this->formatJson($objJson->{'essay'});
						/*
						*	QUADPS-106 
						*	This is santanu Committing for 
						*	1. special character issue with html_entity_decode has been commented for question text
						*/
                        $essayText = $this->formatJson($objJson->{'essay'}, 1, 0);

                        //For LTD
                        $imageSrc = $this->formatJson($objJson->{'image'});

                        $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                        $ind_quesdifficulty = $objJson->{'metadata'}[1]->{'val'};
                        $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);

                        //Question level Metadata code starts
                        //  Get Assigned Metadata for the question
                        $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                        $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                        Site::myDebug('------QuestAssignedMetadata');
                        Site::myDebug($QuestAssignedMetadata);
                        if (!empty($QuestAssignedMetadata['RS'])) {
                            foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                                $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                                if ($arrMetadataValues) {
                                    foreach ($arrMetadataValues as $metadataValues) {
                                        $arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
                                        if ($arrValue['4'] >= 1) {
                                            $mkeyname = $arrMetadata['KeyName'];
                                            $metadataArray[$mkeyname] = $arrValue['2'];
                                        }
                                    }
                                }
                            }
                        }
                        Site::myDebug('------metadataArray');

                        if (!preg_grep("/skill/i", array_keys($metadataArray))) {
                            $responseData['msgRespWar'] = 'Some Question has no skill metadata';
                            Site::myDebug('------metadataArray--- skill');
                        }
                        Site::myDebug($metadataArray);
                        //Question level Metadata code ends here
                        //Question Level Taxonomy Code starts
                        $site = & $this->registry->site;
                        $selectedTaxonomy = explode(',', $input['Taxonomy']);

                        $entityTaxoListSql = "SELECT t.Taxonomy,t.ID,t.ParentID  FROM Classification c LEFT JOIN Taxonomies t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 and c.EntityID=" . $questlist['ID'];
                        ;
                        $entityTaxoList = $site->db->getRows($entityTaxoListSql);
                        $taxonomyArray = array();
                        foreach ($entityTaxoList as $taxKey => $taxValue) {
                            $Parrent = trim($class->getTaxonmyParent($taxValue['ID']), "/");
                            $parrentIDs = explode('/', $Parrent);
                            $matchData = array_intersect($parrentIDs, $selectedTaxonomy);

                            if (!empty($matchData)) {
                                $this->idAllStr = '';
                                $taxonomyArray[$taxKey]['Taxonomy'] = "skill";
                                $taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
                            }
                        }
                        //$this->myDebug($taxonomyArray);
                        //Question Level Taxonomy code ends here

                        $i++;

                        Site::myDebug('----------textwith_blanks------------------------------');
                        Site::myDebug($objJson->{'textwith_blanks'});

                        $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/" . $TemplateFile . ".php";
                        Site::myDebug('----------templateFilePath-------------');
                        Site::myDebug($templateFilePath);
                        ob_start();
                        if (file_exists($templateFilePath) /* && ($isExport == 'Y') */) { //commented isExport condition as we are supporting all templates for QTI2.1 
                            Site::myDebug('-----m inside-----------');
                            //echo "Quest_text  ".$Quest_text;
                            include($templateFilePath);

                            $xmlStr = ob_get_contents();


                            //echo ">>>>>>"; echo "<pre>";print_r($xmlStr);die('------************---'); 
                            ob_end_clean();

                            /* create multiple xml files with each question */

                            $qtifol = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
                            Site::myDebug('------questionxml');
                            Site::myDebug($temp_path_root);
                            Site::myDebug($qtifol);
                            $menifest_resources = "{$Exportid}.xml";
                            $myFile = $qtifol;
                            $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
							$domxml = new DOMDocument('1.0');
							$domxml->preserveWhiteSpace = false;
							$domxml->formatOutput = true;
							$domxml->loadXML($xmlStr);
							$domxml->save($myFile);
                            //$questionList[] = $objJson->{'question_title'};
                            //$imsManifestArray[$i]['entity_title'] = $Entity_name;
                            $imsManifestArray[$i]['question_title_identifier'] = $objJson->{'question_title'};
                            $imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
                            $imsManifestArray[$i]['question_text'] = $objJson->{'question_text'}[0]->{'val1'};
                            $imsManifestArray[$i]['image'] = $objJson->{'image'}[0]->{'val1'};
                            $imsManifestArray[$i]['entity_metadata_array'] = $entityMetaArray;  // Assessment/Bank level metadata
                            $imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata
                            $imsManifestArray[$i]['entity_taxonomy'] = $taxonomyArray; // Question level taxonomy

                            $questionTextImagesArray[$i] = $this->getAssetsFromJSONdata($imsManifestArray[$i]['question_text']);
			    
			    if (!empty($Quest_Inst_text)) { // For Static Page.
				$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($Quest_Inst_text));
			    }

			if (!empty($objJson->{'choices'}[0]->{'val4'}) && $TemplateFile == 'FIBDropDown') { // for FIBDropDown
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($objJson->{'choices'}[0]->{'val4'}));
                            }

                            if (!empty($objJson->{'image'}) && $TemplateFile == 'LabelDiagram') { // for LabelDiagram
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($objJson->{'image'}));
                            }

                            if (!empty($objJson->{'choices'})) {
                                foreach ($objJson->{'choices'} as $key => $value) {
                                    $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->val2));
                                    Site::myDebug($value->val2);
                                }
                            }
                            if (!empty($objJson->{'image'}[0]->{'val1'})) { // For MCSS/MCMS text with Image template
                                $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($objJson->{'image'}[0]->{'val1'}));
                                Site::myDebug($objJson->{'image'}[0]->{'val1'});
                            }
                            if (!empty($objJson->{'choices'}) && $TemplateFile == 'DragDropText') { // for drag drop
                                foreach ($objJson->{'choices'} as $key => $value) {
                                    $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->val1));
                                    Site::myDebug($value->val1);
                                }
                            }
                            unset($entityMetaArray);
                            unset($metadataArray);
                        }
                    }
                }
            }

            Site::myDebug('----------imsManifestArray------1');
            Site::myDebug($imsManifestArray);
            /* for manifest file */
            ob_start();

            include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/ImsManifest.php");
            $xmlmaniStr = ob_get_contents();
            ob_end_clean();
            $fh2 = fopen($temp_path_root . "/imsmanifest.xml", 'w');
            fwrite($fh2, $xmlmaniStr);
            fclose($fh2);

            include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/AssesmentExamView.php");
            $xmlmaniStr = ob_get_contents();
            ob_end_clean();
            $fh2 = fopen($temp_path_root . "/assesmentExamView.xml", 'w');
            fwrite($fh2, $xmlmaniStr);
            fclose($fh2);

            /* end of creating manifest */

            if (!isset($input['opt'])) {
                if ($input['action'] == "exportq") {
                    if ($DBCONFIG->dbType == 'Oracle') {
                        $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
                    } else {
                        $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
                    }

                    $dbdata1 = array(
                        'QuestCount' => $totalquestions,
                        'ExportPackageName' => $imsManifestArray['AssessmentName'] . '_' . $Exportid
                    );
                    $this->db->update('ExportHistory', $dbdata1, $condition1);
                }


                $webpath = $temp_path_web . ".zip";
                $zipfile = $temp_path_root . ".zip";
                $srczippath = $temp_path_root;


                $this->myDebug("==============This Export name issue======================");
                $this->myDebug($webpath);
                $this->myDebug($zipfile);
                $this->myDebug($srczippath);


                $auth->makeZip($srczippath, $zipfile);
                $this->myDebug("This is Web Path");
                $this->myDebug($webpath);
                /*                 * Zip Upload to the S3 Bucket * */
                if ($this->cfg->S3bucket) {
                    $S3ZipFilePath = str_replace($this->cfg->rootPath . '/', "", $zipfile);
                    $S3ZipFilePath = str_replace("//", "/", $S3ZipFilePath);
                    s3uploader::upload($zipfile, $S3ZipFilePath);
                }
                $responseData['webpath'] = $webpath;
                echo json_encode($responseData);
            } else {
                return $guid;
            }
        } else {
            echo json_encode($responseData);
        }
    }

    /**
     * * PAI02 :: sprint 3 ::  QUADPS-40
     * a function to handle request for creating new format Array
     * for composite packages
     *
     * @access   Public
     * @return   array
     *
     */
    function arraySorting($question) {
        $question = $this->array_msort($question, array('Sequence' => SORT_ASC, 'QSequence' => SORT_ASC));
        $outputData = array();
        $k = 0;
        foreach ($question as $key => $value) {
            if ($value['ParentID'] == 0) {
                $value['ParentID'] = $value['QuestionID'];
            }
            $outputData[$value['ParentID']][$k++] = $value;
        }
		$outputData=$this->arrayComplexSection($outputData);
        return $outputData;
    }
	
	/**
     * * PAI02 :: sprint 5 ::  QUADPS-107
     * a function creating for Composit package new Complex format Array
     * for composite packages
     *
     * @access   Public
     * @return   array
     *
     */
	function arrayComplexSection($sections)
	{	
		$newSection=array();
		
		foreach($sections as $key=>$secItem) {
			unset($firstElem);
			
			// Composite package Settings
			$sqlMRQ ="SELECT SectionInfo from MapRepositoryQuestions where ID = ".$key;
			$rowInfo = $this->db->getSingleRow($sqlMRQ);
			$SectionInfo = json_decode($rowInfo['SectionInfo'],true);
			
			if($rowInfo['QuestionIDs']=='')
			{
			 $combinArr[0]=$this->fetchCombiningTabStaticPage($SectionInfo['QuestionIDs']);
			}
			

			if(!empty($SectionInfo) && $SectionInfo['SectionType']=="Complex")
			{
				
				if(!empty($combinArr[0])) {
					$firstElem[0]=$combinArr[0];
				}
				else {
				$firstElem[0]= array_shift($secItem);
				//print_r($firstElem);//1st Element
				//print_r($secItem);//Rest of the Element
				}
				

				$arrChunkSect=array_chunk($secItem, $SectionInfo['SectionGroup']);
				
				foreach($arrChunkSect as $keyItem=>$item) {
					unset($arrItem);
					$arrItem=array_merge($firstElem,$item);
					$newSection[$key.$keyItem]=$arrItem;
			   }
			}
			else
			{
				if(!empty($combinArr[0])) {
					$secItem=array_merge($combinArr,$secItem);
				}
			 $newSection[$key.$keyItem]=$secItem;
			}

		}
		return $newSection;
	}
	
	function fetchCombiningTabStaticPage($QuestionIDs)
	{
		$sqlQuest ="select JSONData from Questions where ID in (".$QuestionIDs.");";
		$rowInfo = $this->db->getRows($sqlQuest);
		$combinArr=array();
		if(!empty($rowInfo)) {
			$combinArr['TemplateFile']='StaticPageTextTab';
			$combinArr['HTMLTemplate']='StaticPageTextTab';
			$combinArr['ParentID']=time();
			$combinArr['ID']=time();
			foreach($rowInfo as $key=>$Quest)
			{
				$combinArr['JSONData'][$key]=$Quest['JSONData'];
			}
		}	
		return $combinArr;
	}
	
    /**
     * * PAI02 :: sprint 3 ::  QUADPS-40
     * a function to handle request for Array sorting by key Value
     * 
     *
     * @access   Public
     * @return   stirng
     *
     */
    function array_msort($array, $cols) {
        $colarr = array();
        foreach ($cols as $col => $order) {
            $colarr[$col] = array();
            foreach ($array as $k => $row) {
                $colarr[$col]['_' . $k] = strtolower($row[$col]);
            }
        }
        $eval = 'array_multisort(';
        foreach ($cols as $col => $order) {
            $eval .= '$colarr[\'' . $col . '\'],' . $order . ',';
        }
        $eval = substr($eval, 0, -1) . ');';
        eval($eval);
        $ret = array();
        foreach ($colarr as $col => $arr) {
            foreach ($arr as $k => $v) {
                $k = substr($k, 1);
                if (!isset($ret[$k]))
                    $ret[$k] = $array[$k];
                $ret[$k][$col] = $array[$k][$col];
            }
        }
        return $ret;
    }

    /**
     * * PAI02 :: sprint 2 ::  QUADPS-45
     * * PAI02 :: sprint 4 ::  QUADPS-90
     * a function to handle request for creating QTI2.1 Template Item Body 
     * for composite packages
     *
     * @access   Public
     * @return   stirng
     *
     */
    function createItemBodyXml($Quest_text, $optionsList, $TemplateFile, $questlistID, $objJson, $temp_path_root, $questlistJson = '') {

        $returnHtml = '';
        switch ($TemplateFile) {
            case 'MCSSImage' :
            case 'MCSSSelectableAudio' :
            case 'MCSSSelectableImage' :
            case 'MCSSText' :
                $returnHtml = '<div style="clear:both;">';
                $returnHtml.='<div class="stem"><p>' . $this->replaceQuot($Quest_text) . '</p></div>';
		$returnHtml.='<div label="response"><choiceInteraction responseIdentifier="RESPONSE_' . $questlistID . '" shuffle="false" maxChoices="1">';
                $j = 1;
                foreach ($optionsList as $option) {
                    $val1 = $val2 = '';
                    $val1 = $this->formatJson($option->{'val1'}, 0); // val 
                    $choiceArray = array('textDocument' => $this->formatCdata($option->{'val2'}, 0, 0), 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $this->formatCdata($option->{'val2'}, 0, 0));

                    $val2 = $this->changeImageSRC($choiceArray);

                    $val3 = $this->formatJson($option->{'val3'}); // choice feedback

                    $imagetag = $val2;
                    list($imgpath, $imgname, $ext) = $this->getImageDetail($imagetag);
                    $this->createImage($imgpath, $temp_path_root);

                    if ($val1 == 1 || $val1 == 'true') {
                        $correct_choice = $val2;
                        $correct_choice_id = $j;
                    }

                    if (!empty($val2)) {
			$val2 = $this->getChoiceFormatData($val2);
                        $returnHtml.='<simpleChoice identifier="QUE_' . $questlistID . '_C' . $j . '">' . $val2 . '</simpleChoice>';
                    }
                    $j++;
                }

                $returnHtml.='</choiceInteraction></div></div>';
                break;
          //QUADPS-90 : For MCMSS Composite
	    case 'MCMSText' :
	    case 'MCMSSelectableImage' :
                $returnHtml = '<div style="clear:both;">';
		$returnHtml.='<div label="response"><choiceInteraction responseIdentifier="RESPONSE_' . $questlistID . '" shuffle="false" maxChoices="1">';
                $returnHtml.='<div class="stem"><prompt>' . $this->replaceQuot($Quest_text) . '</prompt></div>';
                $j = 1;
                foreach ($optionsList as $option) {
                    $choiceArray = array('textDocument' => $this->formatCdata($option->{'val2'}, 0, 0), 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $this->formatCdata($option->{'val2'}, 0, 0));

                    $this->changeImageSRC($choiceArray);
		    $correctResponseValue = $correctResponseValue = $this->getChoiceFormatData($this->formatJson($option->{'val2'}, 0, 0));

                    $returnHtml.='<simpleChoice identifier="QUE_' . $questlistID . '_C' . $j . '">' . $correctResponseValue . '</simpleChoice>';
                    $j++;
                }

		$returnHtml.='</choiceInteraction></div></div>';
                break;

            case 'FIBDragDrop':
                $textwith_blanks = $objJson->{'textwith_blanks'};
                $splitTextWithBrackets = explode("[[", $textwith_blanks);
                $patterns = "/([^\]\]]*)\]\]/";  //this will search string with xyz]] pattern
                $patternCnt = 0;
                $final_text_with_blanks = '';

                preg_match_all($patterns, $textwith_blanks, $textwith_blanksNew);
                $t = 1;
                $chVal = 'G' . $t;
                foreach ($optionsList as $option) {
                    $str = '[[' . $t . ']]';
                    $extendedTextInteraction = '<gap class="gap-width-80px" identifier="' . $chVal . '" />';
                    $textwith_blanks = str_replace($str, $extendedTextInteraction, $textwith_blanks);
					$textwith_blanks = $this->replaceQuot($textwith_blanks);
                    $t++;
                    $chVal = 'G' . $t;
                }
                $returnHtml = '<div style="clear:both;">';
                $returnHtml.='<gapMatchInteraction responseIdentifier="RESPONSE_' . $questlistID . '"><prompt>' . $this->replaceQuot($Quest_text) . '</prompt>';
                $chCount = 1;
                foreach ($optionsList as $option) {
                    $returnHtml.='<gapText identifier="M'.$chCount.'" matchMax="1">'.strip_tags($option->val2).'</gapText>';
                    $chCount++;
                }

                $returnHtml.='<blockquote>';
                $returnHtml.='<p>' . $textwith_blanks . '</p>';
                $returnHtml.='</blockquote>';
                $returnHtml.='</gapMatchInteraction>';
                $returnHtml.='</div>';
                break;
            case 'FIBTextInput':
                $textwith_blanks = $objJson->{'textwith_blanks'};
                $splitTextWithBrackets = explode("[[", $textwith_blanks);
                $patterns = "/([^\]\]]*)\]\]/";  //this will search string with xyz]] pattern
                $patternCnt = 0;
                $final_text_with_blanks = '';
                foreach ($splitTextWithBrackets as $splitText) {
                    $extendedTextInteraction = '<textEntryInteraction responseIdentifier="RESPONSE_' . $patternCnt . '_' . $questlistID . '" expectedLength="10" />';
                    $textwith_blanks = preg_replace($patterns, $extendedTextInteraction, $splitText);
                    $final_text_with_blanks .= $textwith_blanks;
                    $patternCnt++;
                }
                $returnHtml = '<div style="clear:both;"><p>' . $this->replaceQuot($Quest_text) . '</p>';
				$returnHtml.='<p>' . $this->replaceQuot($final_text_with_blanks) . '</p>';
                $returnHtml.='</div>';
                break;
	    case 'FIBDropDown':
				$returnHtml = '<div style="clear:both;">';	
                $textwith_blanks = $objJson->{'textwith_blanks'};
                $isDash = 0;
		if (strpos($textwith_blanks, '{{dash1}}') !== false) {
					//echo "<pre>";print_r($questlistJson);echo "<br><br><br><br>";print_r($objJson);die('-----');
		    if (trim($optionsList[0]->{'val4'})) {
						$isDash = 2;
			$final_textwith_blanks = $this->replace_blank_test($textwith_blanks, $optionsList, $isDash, $questlistID);

						$objJSONtmp = new Services_JSON();
			$JsonTemp_db = $objJSONtmp->decode($questlistJson);
			$JsonTemp_choiceImgInline = $JsonTemp_db->{'choices'}[0]->{'val4'};
						preg_match_all('/___(ASSETINFO)("?)([^}]*})("?)___/i', $JsonTemp_choiceImgInline, $JsonTemp_chImgInlnArr);
			$JsonTemp_chImgInlnArr_JSON = html_entity_decode($JsonTemp_chImgInlnArr[3][0], ENT_QUOTES, "UTF-8");

						$chImgDetailArr = $objJSONtmp->decode($JsonTemp_chImgInlnArr_JSON);
						//echo "<pre>";print_r($chImgDetailArr);die('===============');
						$chImgDetail_alt = $chImgDetailArr->alt_tag;
						$chImgDetail_width =  $chImgDetailArr->asset_width;

			$imagetag = html_entity_decode($this->formatJson($optionsList[0]->{'val4'}, 0, 1));
			list($imgpath, $imgname, $ext) = $this->getImageDetail($imagetag);
			$this->createImage($imgpath, $temp_path_root);

			if ($imgname) {

			    if ($this->cfg->S3bucket) {
				$first_img = explode('?', $imgname);
				$imgname = $first_img[0];
							}
			}
			$img_pos_arr = explode(',', $optionsList[0]->{'val5'}); //print_r($img_pos_arr);
			$final_textwith_blanks .= '<div style="position:relative;margin:0 auto;width:' . $chImgDetail_width . 'px;"><img alt="' . $chImgDetail_alt . '" src="images/' . $imgname . '"/><div id="sign" style="width:' . $img_pos_arr[0] . 'px;left:' . $img_pos_arr[1] . 'px;top:' . $img_pos_arr[2] . 'px;"/></div>';

						//die('--------==========------');				
		    } else {
						$isDash = 1;
			$final_textwith_blanks = $this->replace_blank_test($textwith_blanks, $optionsList, $isDash, $questlistID);
					}
		} else {
		    $final_textwith_blanks = $this->replace_blank_test($textwith_blanks, $optionsList, $isDash, $questlistID);
				}
				$returnHtml.='<p>' . $this->replaceQuot($Quest_text) . '</p>';
				$returnHtml.='<blockquote>';
		        $returnHtml.='<p>' . $this->replaceQuot($final_textwith_blanks) . '</p>';
		        $returnHtml.='</blockquote>';
				$returnHtml.='</div>';
				//echo $returnHtml;die('**********************');
                break;
            case 'Essay':
                $returnHtml ='<div style="clear:both;"><div class="well">';
				$returnHtml.='<div class="prompt">' . $this->replaceQuot($Quest_text) . '</div>';
				$returnHtml.='<extendedTextInteraction class="editor-basic height-xtratall" expectedLength="700" responseIdentifier="RESPONSE_' . $questlistID . '">';
                $returnHtml.='</extendedTextInteraction></div></div>';
                break;
            case 'StaticPageText':
				$page_title=$this->getChoiceFormatData($objJson->{'passage_title'});
				$Quest_Inst_text=$objJson->{'introduction_text'};
				$statisPage = array('textDocument' => $Quest_Inst_text, 'imageSRC' => 'images/', 'temp_path_root'=> $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $Quest_Inst_text);
				$this->changeImageSRC($statisPage);
			    $Quest_Inst_text= $this->getChoiceFormatData($Quest_Inst_text);
                $returnHtml = '<div class="span6">';			
				$returnHtml.='<div class="stimulus">'.$page_title.'<br/><br/></div>';
                $returnHtml.='<div class="passage-scrolling">';
                $returnHtml.='<p>' . $Quest_Inst_text . '</p></div>';
                $returnHtml.='</div>';
                break;
			case 'StaticPageTextTab':	
				$passages='';
				$tabContents='';
				$objJSONtmp = new Services_JSON();
				foreach($questlistJson as $keyTab=>$questJson) {
					$sJson = $this->removeMediaPlaceHolderExport($questJson);
                    $questObjJson = $objJSONtmp->decode($sJson);
					$Quest_Inst_text=$questObjJson->{'introduction_text'};
					
					$statisPage = array('textDocument' => $Quest_Inst_text, 'imageSRC' => 'images/', 'temp_path_root'=> $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $Quest_Inst_text);
				    $this->changeImageSRC($statisPage);
					
					$Quest_Inst_text= $this->getChoiceFormatData($Quest_Inst_text);
					$Quest_title_text = $this->getChoiceFormatData($questObjJson->{'question_title'});	

					$page_title=$this->getChoiceFormatData($questObjJson->{'passage_title'});
					if($keyTab==0) {
						$passages.='<li class="active"><a href="#firstStimulus">'.$page_title.'</a></li>';
						$tabContents.='<div class="tab-pane active" id="firstStimulus">';
						$tabContents.='<div class="tab-scrolling passage440">';
						$tabContents.='<p>'.$Quest_Inst_text.'</p>';
						$tabContents.='</div></div>';
					} else {
						$passages.='<li><a href="#secondStimulus">'.$page_title.'</a></li>';
						$tabContents.='<div class="tab-pane" id="secondStimulus">';
						$tabContents.='<div class="tab-scrolling passage440">';
						$tabContents.='<p>'.$Quest_Inst_text.'</p>';
						$tabContents.='</div></div>';
					}
					
				}
				$returnHtml ='<div class="span6">';
				$returnHtml.='<div class="stimulus">';
				$returnHtml.='<ul class="nav nav-tabs tabbed-passages" id="tabControl">';
				$returnHtml.=$passages;
				$returnHtml.='</ul>';
				$returnHtml.='<div class="tab-content">';
				$returnHtml.=$tabContents;				
				$returnHtml.='</div>';	
				$returnHtml.='</div>';
				$returnHtml.='</div>';
                break;
				
            case 'SortingContainers':
				$arr_scoreval  = array( 'val5', 'val6', 'val7', 'val8' );
                $returnHtml ='<div style="clear:both;">';
                $returnHtml.='<p>' . $this->replaceQuot($Quest_text) . '</p>';
                $returnHtml.='<matchInteraction maxAssociations="4" responseIdentifier="RESPONSE_' . $questlistID . '">';
                $returnHtml.='<simpleMatchSet>';
                $containerTextAll = $objJson->{'choices'};
                $matchMax = 0; //count($containerTextAll);
                foreach ($containerTextAll as $keyText => $valueText):
                    foreach ($valueText as $keyRes => $valueRes) {
						if (trim($valueRes) != "" && ! in_array( $keyRes, $arr_scoreval )) {
							$returnHtml.='<simpleAssociableChoice identifier="' . $keyRes . '_' . $keyText . '" matchMax="1">' . htmlspecialchars($valueRes) . '</simpleAssociableChoice>';
                                                        $matchMax++;
						}
                    }
                endforeach;
                $returnHtml.='</simpleMatchSet>';
                $returnHtml.='<simpleMatchSet>';
                if ($objJson->{container_one}) {
		    $returnHtml.='<simpleAssociableChoice identifier="B1" matchMax="' . $matchMax . '">' . strip_tags($objJson->{container_one}) . '</simpleAssociableChoice>';
                }
                if ($objJson->{container_two}) {
		    $returnHtml.='<simpleAssociableChoice identifier="B2" matchMax="' . $matchMax . '">' . strip_tags($objJson->{container_two}) . '</simpleAssociableChoice>';
                }
                if ($objJson->{container_three}) {
		    $returnHtml.='<simpleAssociableChoice identifier="B3" matchMax="' . $matchMax . '">' . strip_tags($objJson->{container_three}) . '</simpleAssociableChoice>';
                }
                if ($objJson->{container_four}) {
		    $returnHtml.='<simpleAssociableChoice identifier="B4" matchMax="' . $matchMax . '">' . strip_tags($objJson->{container_four}) . '</simpleAssociableChoice>';
                }
                $returnHtml.='</simpleMatchSet>';
                $returnHtml.='</matchInteraction>';
                $returnHtml.='</div>';
                break;
                
                case 'LabelDiagram':
                   /*Image Details start*/
                   $imagetag = html_entity_decode($this->formatJson($objJson->{'image'}, 0, 1));
                    list($imgpath, $imgname, $ext) = $this->getImageDetail($imagetag);

                    $this->createImage($imgpath, $temp_path_root);
                    if ($imgname) {

                        if ($this->cfg->S3bucket) {
                            $first_img = explode('?', $imgname);
                            $imgname = $first_img[0];
        }
                    }
                    $cnt_tot = count($objJson->{'choices'});
                    $cnt_pin = count($objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'});
                    $pinArr = $objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'};
                    $cnt_dist = count($objJson->{'appLevel'}->{'distractor_details'}->{'distractor'});
                /*Image Details End*/ 
                    $imagePath = "images/".htmlentities($imgname);
				$returnHtml.='<div style="clear:both;"><p>' . $this->replaceQuot($Quest_text) . '</p></div>';
                    $returnHtml.='<graphicGapMatchInteraction class="choices-top sourcechoices-width-376px" responseIdentifier="RESPONSE_' . $questlistID . '">';
                    $returnHtml .= '<object alt="timeline" data="'.$imagePath.'" height="326" type="image/png" width="558" />';
                     for ($ii = 0; $ii < $cnt_pin; $ii++) {
                        $imagetag = html_entity_decode($this->formatJson($objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'}[$ii]->{'label'}, 0, 1));
                        list($imgpath, $pin_imgname, $ext) = $this->getImageDetail($imagetag);

                        $this->createImage($imgpath, $temp_path_root);
                        if ($pin_imgname) {

                            if ($this->cfg->S3bucket) {
                                $first_img = explode('?', $pin_imgname);
                                $pin_imgname = $first_img[0];
                            }
                        }
                        $draggerVal = 'Dragger' . $ii;
                        $pinImageName = "images/" . htmlentities($pin_imgname);
                        $returnHtml.=' <gapImg identifier="' . $draggerVal . '" matchMax="1">';
                        $returnHtml.='<object data="' . $pinImageName . '" height="40" type="image/png" width="95"/>';
                        $returnHtml.='</gapImg>   ';
                 }
                 for ($j = 0; $j < $cnt_dist; $j++) {
                    $imagetag = html_entity_decode($this->formatJson($objJson->{'appLevel'}->{'distractor_details'}->{'distractor'}[$j]->{'label'}, 0, 1));
                    list($imgpath, $dist_imgname, $ext) = $this->getImageDetail($imagetag);

                    $this->createImage($imgpath, $temp_path_root);
                    if ($dist_imgname) {

                        if ($this->cfg->S3bucket) {
                            $first_img = explode('?', $dist_imgname);
                            $dist_imgname = $first_img[0];
                        }
                    }
                    $draggerVal = 'Dragger' . $ii;
                    $distImageName = "images/" . htmlentities($dist_imgname);
                    $returnHtml.=' <gapImg identifier="' . $draggerVal . '" matchMax="1">';
                    $returnHtml.='<object data="' . $distImageName . '" height="40" type="image/png" width="95"/>';
                    $returnHtml.='</gapImg>';
                    $ii++;
                }
                
                for ($ii = 0; $ii < $cnt_pin; $ii++) {
                    $pos_arr = $this->formatJson(htmlspecialchars($objJson->{'choices'}[$ii]->{'val4'}), 0, 0);
                    $pos_arr_new = explode(',', $pos_arr);
                    $wdth = ($this->formatJson(htmlspecialchars($objJson->{'metadata'}[2]->{'val'}), 0, 0) + $pos_arr_new[0]);
                    $hgt = ($this->formatJson(htmlspecialchars($objJson->{'metadata'}[3]->{'val'}), 0, 0) + $pos_arr_new[1]);

                    //echo "POS==".$pos_arr.','.$wdth.','.$hgt;
                    $coOrds = $objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'}[$ii]->{'-position'};
                    $coOrds .= ',' . $wdth . ',' . $hgt;
                    $counter = 'W' . $ii;
                    $returnHtml.=' <associableHotspot coords="' . $coOrds . '" identifier="' . $counter . '" matchMax="1" shape="rect"/>';
                }
                    $returnHtml.='</graphicGapMatchInteraction>';
                break;
        }

        return $returnHtml;
    }

    /**
     * * PAI02 :: sprint 2 ::  QUADPS-45
     * * PAI02 :: sprint 4 ::  QUADPS-90
     * a function to handle request for creating QTI2.1 Template Response XML 
     * for composite packages
     *
     * @access   Public
     * @return   stirng
     *
     */
    function createResponseXml($optionsList, $TemplateFile, $questlistID,$objJson = null) {
        $responseDeclaration = '';
        switch ($TemplateFile) {
            case 'MCSSImage' :
            case 'MCSSSelectableAudio' :
            case 'MCSSSelectableImage' :
            case 'MCSSText' :
                $responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $questlistID . '" cardinality="single" baseType="identifier">';
                $simpleChoiceNode = '';
                if (!empty($optionsList)) {
                    $j = 1;
                    foreach ($optionsList as $option) {
                        $val1 = $val2 = '';
                        $val1 = $this->formatJson($option->{'val1'}, 0);
                        $val2 = $this->formatJson($option->{'val2'}); // choicess
                        $val3 = $this->formatJson($option->{'val3'}); // choice feedback
                        $identifier = "Choice_" . $j;
                        if ($val1 == 1 || $val1 == 'true') {
                            $correct_choice = $val2;
                            $correct_choice_id = $j;
			}
                        $j++;
                    }
                }
                $responseDeclaration .= '<correctResponse> <value>QUE_' . $questlistID . '_C' . $correct_choice_id . '</value></correctResponse>';
                $responseDeclaration .= '</responseDeclaration>';
                break;
            //QUADPS-90 : For MCMSS Composite
			case 'MCMSText' :
			case 'MCMSSelectableImage' :
                $responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $questlistID . '" cardinality="multiple" baseType="identifier">';
                $mappingEntryNode = '';
                $correctResponseNode = '';
				$simpleChoiceNode = '';
				$totalScore=0;
				
                if (!empty($optionsList)) {
                    $j = 1;
                    foreach ($optionsList as $option) {
                        $correctResponseValue = str_replace("<br/>", "", $option->{'val2'});    //value
                        $simpleChoiceValue =  strip_tags($option->{'val3'});    //Score
						if(!is_numeric($simpleChoiceValue)){     
							$simpleChoiceValue = 1;
						}
                        $simpleChoiceAns = $option->{'val1'};                           //true or false   
                        $identifier = "answer_" . $j;
                        if ($simpleChoiceAns == true || $simpleChoiceAns == '1') {
                            $mappingEntryNode .= '<mapEntry mapKey="QUE_' . $questlistID . '_C' . $j . '" mappedValue="'.$simpleChoiceValue.'"/>';
                            $correctResponseNode .= '<value>QUE_' . $questlistID . '_C' . $j . '</value>';
							$totalScore+=$simpleChoiceValue;
                        } 
                        $j++;
                    }
                }
                $responseDeclaration .= '<correctResponse>';
                $responseDeclaration .=  $correctResponseNode;
                $responseDeclaration .= '</correctResponse>';
                $responseDeclaration .= '<mapping lowerBound="0" upperBound="'.$totalScore.'" defaultValue="-'.$totalScore.'">';
				$responseDeclaration .= $mappingEntryNode;
                $responseDeclaration .= '</mapping>';

                $responseDeclaration .= '</responseDeclaration>';
				break;
            case 'FIBDragDrop':
                $fibCnt = 0;
                $responseCondition = '';
                $chCount = 1;
                $chVal = 'G' . $chCount;
                $responseDeclaration .= '<responseDeclaration baseType="directedPair" cardinality="multiple" identifier="RESPONSE_' . $questlistID . '">';
                $responseDeclaration .= '<correctResponse>';

                $chCount = 1;
				$mappingEntryNode = '';
				$correctResponseNode = '';
				foreach ($optionsList as $option) {

				$simpleChoiceValue = strip_tags($option->{'val3'});    //Score
                                if(!empty($simpleChoiceValue)){
                                    $simpleChoiceValue = strip_tags($option->{'val3'});
                                }else{
                                    $simpleChoiceValue = 1;
                                }
				$chKey = 'M' . $chCount;
				$chVal = 'G' . $chCount;
				if($simpleChoiceValue) {
					$mappingEntryNode.='<mapEntry mapKey="' . $chKey . ' ' . $chVal . '" mappedValue="' . $simpleChoiceValue . '" /> ';
					$correctResponseNode.='<value>' . $chKey . ' ' . $chVal . '</value>';
					$chCount++;
				}
				}
				$responseDeclaration .=$correctResponseNode;
                $responseDeclaration .= '</correctResponse>';
                $responseDeclaration .= '<mapping defaultValue="-1" lowerBound="0">';
                $responseDeclaration .=$mappingEntryNode;
                $responseDeclaration .= '</mapping>';
                $responseDeclaration .= '</responseDeclaration>';
                break;
            case 'FIBTextInput':
                $fibCnt = 0;
                foreach ($optionsList as $option) {
                    $fibCnt++;
                    $fibAns = $option->{'val2'};
                    $fibAnsArray = explode(",", $fibAns);
                    $fibChoiceScore = $option->{'val3'};
                    if (!empty($fibChoiceScore)) {
                        $fibChoiceScoreArray = explode(",", $fibChoiceScore);
                        $fibMaxChoicevalue = max($fibChoiceScoreArray);
                    } else {
                        $fibMaxChoicevalue = 1;
                    }

                    $responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $fibCnt . '_' . $questlistID . '" cardinality="single" baseType="string">';
                    $responseDeclaration .= '<correctResponse>';

                    $responseDeclaration .= '<value>' . str_replace("<br/>", "", $fibAnsArray[0]) . '</value>';
                    $responseDeclaration .= '</correctResponse>';
                    $responseDeclaration .= '<mapping defaultValue="0">';

                    $fibScoreCnt = 0;
                    foreach ($fibAnsArray as $fibVal) {
                        $responseDeclaration .= '<mapEntry mappedValue="' . $fibMaxChoicevalue . '" mapKey="' . str_replace("<br/>", "", $fibVal) . '"/>';

                        $fibScoreCnt++;
                    }
                    $responseDeclaration .= '</mapping>';
                    $responseDeclaration .= '</responseDeclaration>';
                }
                break;
			case 'FIBDropDown':
                $fibCnt = '';
				//$optionsList = $objJson->{'choices'};

		$chCount = 1;
		$chVal = 'G' . $chCount;
				//echo "<pre>";print_r($optionsList);//die('-------------------');
		foreach ($optionsList as $option) {
		    if ($option->{'val4'}) {
			$responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $chCount . '_' . $questlistID . '" cardinality="single" baseType="identifier">';
						$responseDeclaration .= '<correctResponse>';
			$responseDeclaration .= '<value>' . $chVal . '</value>';
							$chCount++;
			$chVal = 'G' . $chCount;
						$responseDeclaration .= '</correctResponse>';
						$responseDeclaration .= '</responseDeclaration>';
		    } else {
			$responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $chCount . '_' . $questlistID . '" cardinality="single" baseType="identifier">';
						$responseDeclaration .= '<correctResponse>';
			$responseDeclaration .= '<value>' . $chVal . '</value>';
							$chCount++;
			$chVal = 'G' . $chCount;
						$responseDeclaration .= '</correctResponse>';
						$responseDeclaration .= '</responseDeclaration>';
					}
		    if ($fibCnt == '') {
			$fibCnt = 0;
		    }
					$fibCnt++;
		}
                break;
            case 'Essay':
                $responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $questlistID . '" cardinality="single" baseType="string"/>';
                break;
            case 'SortingContainers':
				$arr_scoreval  = array( 'val5', 'val6', 'val7', 'val8' );
		$responseDeclaration .= '<responseDeclaration identifier="RESPONSE_' . $questlistID . '" cardinality="multiple" baseType="directedPair">';
                $responseDeclaration .= '<correctResponse>';
                $containerTextAll = $optionsList;
		foreach ($containerTextAll as $keyText => $valueText):
		    $res = 1;
				foreach ($valueText as $keyRes => $valueRes) {
					 if (trim($valueRes) != "" && ! in_array( $keyRes, $arr_scoreval )) {
						$responseDeclaration .= '<value>' . $keyRes . '_' . $keyText . ' B' . $res . '</value>';
						$res++;
					}	
				}
			endforeach;
		$responseDeclaration .= '</correctResponse>';
             $responseDeclaration .= '</responseDeclaration>';
		break;
            case 'LabelDiagram':
                /*Pin Details start*/
                $cnt_tot = count($objJson->{'choices'});
                /***  TOTAL CHOICE ARRAY pin ***/
                //print_r($objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'});
                $cnt_pin = count($objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'});
                $pinArr = $objJson->{'appLevel'}->{'hot_spot_details'}->{'hot_spot'};
                /***  TOTAL CHOICE ARRAY distractor ***/
                //print_r($objJson->{'appLevel'}->{'distractor_details'}->{'distractor'});
                $cnt_dist = count($objJson->{'appLevel'}->{'distractor_details'}->{'distractor'});
                /*Pin Details end*/
                
                $responseDeclaration .= '<responseDeclaration baseType="directedPair" cardinality="multiple" identifier="RESPONSE_' . $questlistID . '">';
                $responseDeclaration .= '<correctResponse>';
               
		$correctResponseNode = '';
		$mapKeyEntry='';
			for($ii=0;$ii<$cnt_pin; $ii++){ 	
                            $correctResponseNode.='<value>'.'Dragger' . $ii . ' W' . $ii . '</value>';
                            //$mapKeyEntry.='<mapEntry mapKey="Dragger'.$ii.' W'.$ii.'" mappedValue="'.$pinArr[$ii]->pscore.'"/>';
                        }     
                           
		$responseDeclaration .=$correctResponseNode;
                //echo $responseDeclaration;
                //exit;
                $responseDeclaration .= '</correctResponse>';
               // $responseDeclaration .= '<mapping defaultValue="0">';
                //$responseDeclaration .=$mapKeyEntry;
                //$responseDeclaration .= '</mapping>';
                $responseDeclaration .= '</responseDeclaration>';
                break;

            default:

                break;
        }
        return $responseDeclaration;
    }
	
	/**
     * * PAI02 :: sprint 2 ::  QUADPS-
     * * PAI02 :: sprint 4 ::  QUADPS-
     * a function to handle request for creating QTI2.1 Template outcomeDeclaration XML 
     * for composite packages
     *
     * @access   Public
     * @return   stirng
     *
     */
	function outcomeDeclaration($outcome)
	{
		$outcomeDeclaration = '<outcomeDeclaration baseType="float" cardinality="single" identifier="SCORE'.$outcome.'">';
        $outcomeDeclaration .= '<defaultValue>';
        $outcomeDeclaration .= '<value>0.0</value>';
        $outcomeDeclaration .= '</defaultValue>';
		$outcomeDeclaration .= '</outcomeDeclaration>';
		return $outcomeDeclaration;
	}
    /**
     * * PAI02 :: sprint 2 ::  QUADPS-45
     * a function to handle request for creating QTI2.1 Template Response Processing XML 
     * for composite packages
     *
     * @access   Public
     * @return   stirng
     *
     */
    function createResponseProcessingXml($ind_quesScore, $TemplateFile, $questlistID,$objJson=null) {
        $returnHtml = '';
        switch ($TemplateFile) {
            case 'MCSSImage' :
            case 'MCSSSelectableAudio' :
            case 'MCSSSelectableImage' :
            case 'MCSSText' :
			case 'MCMSText' :
			case 'MCMSSelectableImage' :
                $returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
				$returnHtml.='<sum>';
				$returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $ind_quesScore . '</baseValue>';
				$returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
                break;
            case 'FIBDragDrop':
				$returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
				$returnHtml.='<sum>';
				$returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $ind_quesScore . '</baseValue>';
				$returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
				break;
            case 'SortingContainers':
                $returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
				$returnHtml.='<sum>';
				$returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $ind_quesScore . '</baseValue>';
				$returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
				break;
            case 'FIBTextInput':
				$fibCnt = 0;
				$optionsList = $objJson->{'choices'};
                foreach ($optionsList as $option) {
                $fibCnt++;
                $fibChoiceScore = $option->{'val3'};   
                 if (!empty($fibChoiceScore)) {
                    $fibChoiceScoreArray = explode(",", $fibChoiceScore);
                    $fibMaxChoicevalue = max($fibChoiceScoreArray);
                } else {
                    $fibMaxChoicevalue = 1;
                }
                $returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_'.$fibCnt.'_'. $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_'.$fibCnt.'_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
				$returnHtml.='<sum>';
				$returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $fibMaxChoicevalue . '</baseValue>';
				$returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
				}
                break;
			case 'FIBDropDown':
				$fibCnt = 0;
				$optionsList = $objJson->{'choices'};
                
                foreach ($optionsList as $option) {
                if(!empty($option->{'val6'})){ $scoreVal = $option->{'val6'};}else { $scoreVal = 1;}
                $fibCnt++;
				$returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_'.$fibCnt.'_'. $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_'.$fibCnt.'_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
				$returnHtml.='<sum>';
				$returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $scoreVal . '</baseValue>';
				$returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
				}
				break;
                case 'LabelDiagram':
                $returnHtml.='<responseCondition>';
                $returnHtml.='<responseIf>';
                $returnHtml.='<match>';
                $returnHtml.='<variable identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='<correct identifier="RESPONSE_' . $questlistID . '"/>';
                $returnHtml.='</match>';
                $returnHtml.='<setOutcomeValue identifier="SCORE">';
                $returnHtml.='<sum>';
                $returnHtml.='<variable identifier="SCORE"/>';
                $returnHtml.='<baseValue baseType="float">' . $ind_quesScore . '</baseValue>';
                $returnHtml.='</sum>';
                $returnHtml.='</setOutcomeValue>';
                $returnHtml.='</responseIf>';
                $returnHtml.='</responseCondition>';
                break;
        }
        return $returnHtml;
    }

    /**
     * * PAI02 :: sprint 2 ::  QUADPS-46
     * Question exported in QTI2.1 for Realize format in zip file
     *
     *
     * @access   Public
     * @return   stirng
     *
     */
    function exportQuestionWithQti2_1_Zip(array $input) {
        global $Total_Img, $DBCONFIG;

        $metadata = new Metadata();
        $imsManifestArray = array();
        $auth = new Authoring();
        $qst = new Question();
        $objJSONtmp = new Services_JSON();

        $metadataArray = array();
        $entityMetaArray = array();

        $entityTypeId = $input['entityTypeID'];
        $EntityID = $input['entityID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }


        if ($entityTypeId == 2) {
            $Assessment = new Assessment();
            $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
                $EntityID,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID')
                    ), 'nocount');
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
            $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $Entity_name = $imsManifestArray['AssessmentName'] = $this->getValueArray($AssessmentSettings, "Name");
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $settingTimer = $this->getAssociateValue($AssessmentSettings, 'Minutes'); //maxTime
            $attempts = $this->getAssociateValue($AssessmentSettings, 'Tries'); // maxAttempts 
        } else if ($entityTypeId == 1) {
            $Bank = new Bank();
            $BankSettings = $Bank->bankDetail($EntityID);
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
            $Entity_name = $this->getValueArray($BankSettings, "BankName");
        } else {
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
        }

        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;
        $input['selectall'] = false;
        if ($input['selectall'] != "true") {

            $questids = $input['questID'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.QuestionID in ({$questids}) AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');

        $Exportid = $input['questID'];
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = $imsManifestArray['totalquestions'] = count($questions);
        $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
        $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
        mkdir($temp_path_root, 0777, true);
        mkdir($temp_path_root . "/images", 0777);


        //  Get Assigned Metadata for Assessment/Bank
        $entityMetadata = array("EntityID" => $EntityID, "EntityTypeID" => $entityTypeId);
        $entityAssignedMetadata = $metadata->metaDataAssignedList($entityMetadata, "assign");

        if (!empty($entityAssignedMetadata['RS'])) {
            foreach ($entityAssignedMetadata['RS'] as $assignMetadata) {
                $arrMetadataVal = @explode($this->cfgApp->metaDataKeyValSeparator, $assignMetadata['KeyValues']);
                if ($arrMetadataVal) {
                    foreach ($arrMetadataVal as $metadataVal) {
                        $entityArrValue = @explode($this->cfgApp->metaDataKeyValSeparator, $metadataVal);
                        if ($entityArrValue['4'] >= 1) {
                            $mkeyname = $assignMetadata['KeyName'];
                            $entityMetaArray[$mkeyname] = $entityArrValue['2'];
                        }
                    }
                }
            }
        }


        if (!empty($questions)) {
            set_time_limit(0);
            foreach ($questions as $questlist) {
                $question_xml = $questlist;
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["JSONData"];
                $sJson = $this->removeMediaPlaceHolderExport($sJson);
                $objJsonTemp = $objJSONtmp->decode($sJson);
                if (!isset($objJsonTemp)) {
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                }
                $objJson = $objJsonTemp;


                if ($objJson->{'choices'}) {
                    foreach ($objJson->{'choices'} as $choice) {
                        // Alter Image src from question text 
                        $choiceArray = array('textDocument' => $this->formatCdata($choice->val2), 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $this->formatCdata($choice->val2));
                        $val2 = $this->changeImageSRC($choiceArray);
                        $choicesOpt[] = $choice->val1;
                        $choices[] = array(
                            'val1' => ($choice->val1),
                            'val2' => $val2,
                            'val3' => $this->formatCdata($choice->val3),
                            'val4' => $choice->val4,
                            'val5' => "" /* Alt Tag val */
                        );
                    }
                }

                $optionsList = $objJson->{'choices'};

                $Quest_title = $this->formatJson($objJson->{'question_title'}, 0);
                $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'}, 0);


                $Quest_text = $objJson->{'question_text'}[0]->{'val1'};  //Question Text
                $QuesttextAltTagVal = $objJson->{'question_text'}[0]->{'val2'}; //Alt Tag
                // Alter Image src from question text 
                if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
                    $textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $objJson->{'question_text'}[0]->{'val1'});
                    $Quest_text = $this->changeImageSRC($textDocumentArray);
                    $regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
                    if (preg_match_all($regex, $Quest_text, $QuestTextNew)) {
                        Site::myDebug('---QuestTextNew');
                        Site::myDebug($QuestTextNew);
                        if ($QuestTextNew) {
                            foreach ($QuestTextNew as $val) {
                                $Quest_text = str_replace($val, '$$$', $Quest_text);
                            }
                            Site::myDebug('---firstformatjson11111');
                            Site::myDebug($Quest_text);
                        }
                    }$newQuestionText = explode("$$$", $Quest_text);
                    $p = 0;
                    $Quest_text = '';
                    foreach ($newQuestionText as $val) {
                        Site::myDebug('---firstformatjson111112222');
                        Site::myDebug($val);
                        if ($val) {
                            $valnew = '<![CDATA[' . $val . ']]>';
                        } else {
                            $valnew = '';
                        }
			$valnew = $this->formatJson($valnew, 0, 0);
                        $valnew = $valnew . '' . $QuestTextNew[0][$p] . '';
                        $Quest_text.=$valnew;
                        $p++;
                    }
                    //$Quest_text.=implode(" ", $QuestTextNew[0]);  
                    Site::myDebug('---firstformatjson111112222');
                    Site::myDebug($Quest_text);
                } else {
                    $Quest_text = $this->formatJson($objJson->{'question_text'}[0]->{'val1'}, 1, 0);
                }

                $correctFeedback = $objJson->{'correct_feedback'}[0]->{'val1'};  //Correct Feedback text
                $corrFedAltTagVal = $objJson->{'correct_feedback'}[0]->{'val2'}; //Alt Tag

                $incorrectFeedback = $objJson->{'incorrect_feedback'}[0]->{'val1'};  //Incorrect Feedback text
                $incorrFedAltTagVal = $objJson->{'incorrect_feedback'}[0]->{'val2'}; //Alt Tag
                // For Hint 
                //$hint = $this->formatJson($objJson->{'hint'});
                $hint = $objJson->{'hint'}[0]->{'val1'};  //Hint text
                $hintAltTagVal = $objJson->{'hint'}[0]->{'val2'}; //Alt Tag
                //For Eassy
                $essayText = $this->formatJson($objJson->{'essay'});

                //For LTD
                $imageSrc = $this->formatJson($objJson->{'image'});

                $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                $ind_quesdifficulty = $objJson->{'metadata'}[1]->{'val'};
                $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);


                //Question level Metadata code starts
                //  Get Assigned Metadata for the question
                $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                Site::myDebug('------QuestAssignedMetadata');
                Site::myDebug($QuestAssignedMetadata);
                if (!empty($QuestAssignedMetadata['RS'])) {
                    foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                        $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                        if ($arrMetadataValues) {
                            foreach ($arrMetadataValues as $metadataValues) {
                                $arrValue = @explode($this->cfgApp->metaDataKeyValSeparator, $metadataValues);
                                if ($arrValue['4'] >= 1) {
                                    $mkeyname = $arrMetadata['KeyName'];
                                    $metadataArray[$mkeyname] = $arrValue['2'];
                                }
                            }
                        }
                    }
                }
                Site::myDebug('------metadataArray');

                if (!preg_grep("/skill/i", array_keys($metadataArray))) {
                    $responseData['msgRespWar'] = 'Some Question has no skill metadata';
                    Site::myDebug('------metadataArray--- skill');
                }
                Site::myDebug($metadataArray);
                //Question level Metadata code ends here

                $i++;

                Site::myDebug('----------textwith_blanks------------------------------');
                Site::myDebug($objJson->{'textwith_blanks'});


                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/" . $TemplateFile . ".php";
                Site::myDebug('----------assignedmeytadata');
                Site::myDebug($templateFilePath);
                ob_start();
                if (file_exists($templateFilePath) /* && ($isExport == 'Y') */) { //commented isExport condition as we are supporting all templates for QTI2.1 
                    Site::myDebug('-----m inside');
                    include($templateFilePath);

                    $xmlStr = ob_get_contents();
                    ob_end_clean();

                    /* create multiple xml files with each question */

                    $qtifol = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
                    Site::myDebug('------questionxml');
                    Site::myDebug($temp_path_root);
                    Site::myDebug($qtifol);
                    $menifest_resources = "{$Exportid}.xml";
                    $myFile = $qtifol;
					/*$fh2 = fopen($myFile, 'w');
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
					fwrite($fh2, $xmlStr);
					fclose($fh2);*/
					
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
					$domxml = new DOMDocument('1.0');
					$domxml->preserveWhiteSpace = false;
					$domxml->formatOutput = true;
					$domxml->loadXML($xmlStr);
					$domxml->save($myFile);
                    //$questionList[] = $objJson->{'question_title'};
                    //$imsManifestArray[$i]['entity_title'] = $Entity_name;
                    $imsManifestArray[$i]['question_title_identifier'] = $objJson->{'question_title'};
                    $imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
                    $imsManifestArray[$i]['question_text'] = $objJson->{'question_text'}[0]->{'val1'};
                    $imsManifestArray[$i]['entity_metadata_array'] = $entityMetaArray;  // Assessment/Bank level metadata
                    $imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata

                    Site::myDebug('----------imsManifest Array choices------');

                    Site::myDebug($objJson->{'choices'});

                    $questionTextImagesArray[$i] = $this->getAssetsFromJSONdata($imsManifestArray[$i]['question_text']);
		    
		    if (!empty($Quest_Inst_text)) { // For Static Page.
			$questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($Quest_Inst_text));
		    }

                    if (!empty($objJson->{'image'}) && $TemplateFile == 'LabelDiagram') { // for LabelDiagram
                        $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($objJson->{'image'}));
                    }

		    if (!empty($objJson->{'choices'}[0]->{'val4'}) && $TemplateFile == 'FIBDropDown') {
						// for FIBDropDown
                        $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($objJson->{'choices'}[0]->{'val4'}));
                    }

                    if (!empty($objJson->{'choices'})) {
                        foreach ($objJson->{'choices'} as $key => $value) {
                            $questionTextImagesArray[$i] = array_merge($questionTextImagesArray[$i], $this->getAssetsFromJSONdata($value->val2));
                            Site::myDebug($value->val2);
                        }
                    }
                    Site::myDebug($questionTextImagesArray);

                    unset($entityMetaArray);
                    unset($metadataArray);
                }
            }
        }

        Site::myDebug('----------imsManifestArray------1');
        Site::myDebug($imsManifestArray);
        /* for manifest file */
        ob_start();

        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/ImsManifest.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($temp_path_root . "/imsmanifest.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);

        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/AssesmentExamView.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($temp_path_root . "/assesmentExamView.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);

        /* end of creating manifest */

        $webpath = $temp_path_web . ".zip";
        $zipfile = $temp_path_root . ".zip";
        $srczippath = $temp_path_root;

        $auth->makeZip($srczippath, $zipfile);
        $this->myDebug("This is Web Path");
        $this->myDebug($webpath);

        return $zipfile;
    }

    /**
     * * PAI02 :: sprint 2 ::  QUADPS-45
     * Question exported in QTI2.1 for Realize format in zip file
     *
     *
     * @access   Public
     * @return   stirng
     *
     */
    function exportQuestionWithQti2_1_CompositeZip(array $input) {
        global $DBCONFIG;

        /* $input=array('QTypeID'=>2,'RenditionMode'=>'Both','RenditionType'=>'','catID'=>820,'customCss'=>'','entityID'=>'449','entityTypeID'=>2,'output'=>'TN8','platform'=>'','prevSource'=>'listing','questID'=>'3054','questJson'=>''); */

        $metadata = new Metadata();
        $imsManifestArray = array();
        $auth = new Authoring();
        $qst = new Question();
        $objJSONtmp = new Services_JSON();

        $metadataArray = array();
        $entityMetaArray = array();

        $entityTypeId = $input['entityTypeID'];
        $EntityID = $input['entityID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }


        if ($entityTypeId == 2) {
            $Assessment = new Assessment();
            $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
                $EntityID,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID')
                    ), 'nocount');
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
            $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $Entity_name = $imsManifestArray['AssessmentName'] = $this->getValueArray($AssessmentSettings, "Name");
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $settingTimer = $this->getAssociateValue($AssessmentSettings, 'Minutes'); //maxTime
            $attempts = $this->getAssociateValue($AssessmentSettings, 'Tries'); // maxAttempts 
        } else if ($entityTypeId == 1) {
            $Bank = new Bank();
            $BankSettings = $Bank->bankDetail($EntityID);
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
            $Entity_name = $this->getValueArray($BankSettings, "BankName");
        } else {
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
        }

        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $i = $i > 0 ? $i : 0;
        $input['selectall'] = false;
        if ($input['selectall'] != "true") {

            $questids = $input['questID'];
            $questids = str_replace("|$|", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.QuestionID in ({$questids}) AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');

        $Exportid = $questions[0]['ParentID'];
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = $imsManifestArray['totalquestions'] = count($questions);
        $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
        $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
        mkdir($temp_path_root, 0777, true);
        mkdir($temp_path_root . "/images", 0777);
        // mkdir($temp_path_root . "/testitems", 0777);
        //Assessment/Bank level Metadata code starts
        //  Get Assigned Metadata for Assessment/Bank
        $entityMetadata = array("EntityID" => $EntityID, "EntityTypeID" => $entityTypeId);
        $entityAssignedMetadata = $metadata->metaDataAssignedList($entityMetadata, "assign");

        if (!empty($entityAssignedMetadata['RS'])) {
            foreach ($entityAssignedMetadata['RS'] as $assignMetadata) {
                $arrMetadataVal = @explode($this->cfgApp->metaDataKeyValSeparator, $assignMetadata['KeyValues']);
                if ($arrMetadataVal) {
                    foreach ($arrMetadataVal as $metadataVal) {
                        $entityArrValue = @explode($this->cfgApp->metaDataKeyValSeparator, $metadataVal);
                        if ($entityArrValue['4'] >= 1) {
                            $mkeyname = $assignMetadata['KeyName'];
                            $entityMetaArray[$mkeyname] = $entityArrValue['2'];
                        }
                    }
                }
            }
        }
        //Assessment/Bank level Metadata code ends here
        //Question Level Taxonomy Code starts
        $site = & $this->registry->site;
        $entityTaxoListSql = "SELECT GROUP_CONCAT(SUBSTRING_INDEX( Taxonomy, ' ',1 )) AS taxo FROM Classification
				LEFT JOIN Taxonomies ON Taxonomies.ID=Classification.ClassificationID AND Taxonomies.isEnabled=1
				WHERE Classification.isEnabled=1 and EntityID=$EntityID";
        $entityTaxoList = $site->db->getSingleRow($entityTaxoListSql);

        //Question Level Taxonomy code ends here

        if (!empty($questions)) {
            set_time_limit(0);
            /* Composite package Start */

            $sections = $this->arraySorting($questions);
            $this->myDebug('Composite package Array');
            $this->myDebug($sections);
            $sect = 0;
            foreach ($sections as $keySec => $questions) {
                $this->myDebug($questions);
                $itemBody = array();
                $responseProcessing = '';
                $responseDeclaration = '';
				$maxScoreQuest=0;
                foreach ($questions as $questlist) {
                    $question_xml = $questlist;
                    $TemplateFile = $questlist['TemplateFile'];
                    $isExport = $questlist['isExport'];
                    $sJson = $questlist["JSONData"];
                    $sJson = $this->removeMediaPlaceHolderExport($sJson);
                    $objJsonTemp = $objJSONtmp->decode($sJson);
                    if (!isset($objJsonTemp))
                        $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));

                    $objJson = $objJsonTemp;
					
					// Composite package Settings
					$sqlMRQ ="SELECT SectionInfo from MapRepositoryQuestions where ID = ".$questlist['ParentID'];
					$rowInfo = $this->db->getSingleRow($sqlMRQ);
					$SectionInfo = $rowInfo['SectionInfo'];

                    /* RESPONSE */
                    $optionsList = $objJson->{'choices'};
                    $responseDeclaration.=$this->createResponseXml($optionsList, $TemplateFile, $questlist['ID'],$objJson);
		
                    /* ItemBody Start */
                    $Quest_title = $this->formatJson(htmlspecialchars($objJson->{'question_title'}), 0, 0);
                    $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'}, 0);

                    $Quest_text = $objJson->{'question_text'}[0]->{'val1'};  //Question Text
                    $QuesttextAltTagVal = $objJson->{'question_text'}[0]->{'val2'}; //Alt Tag
                    // Alter Image src from question text 
                    if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
                        $textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $objJson->{'question_text'}[0]->{'val1'});

                        $Quest_text = $this->changeImageSRC($textDocumentArray);

                        $regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
                        if (preg_match_all($regex, $Quest_text, $QuestTextNew)) {
                            if ($QuestTextNew) {
                                foreach ($QuestTextNew as $val) {
                                    $Quest_text = str_replace($val, '$$$', $Quest_text);
                                }
                            }
                        }$newQuestionText = explode("$$$", $Quest_text);
                        $p = 0;
                        $Quest_text = '';
                        foreach ($newQuestionText as $val) {

			    $valnew = $this->formatJson($val, 1, 0);
                            $valnew = $valnew . '' . $QuestTextNew[0][$p] . '';
                            $Quest_text.=$valnew;
                            $p++;
                        }
                    } else {
			$Quest_text = $this->formatJson($objJson->{'question_text'}[0]->{'val1'}, 1, 1);
                    }

					
					$ItemTemplateFile=$TemplateFile;	
					if($TemplateFile=="StaticPageTextTab") {
						$ItemTemplateFile="StaticPageText";	
					}
					$itemBody[]=array('TemplateFile' => $ItemTemplateFile,
                            'ItemData'=>$this->createItemBodyXml($Quest_text, $optionsList, $TemplateFile, $questlist['ID'], $objJson, $temp_path_root,$questlist["JSONData"]));


                    /* ItemBody End */

                    /* ResponseProcessing */
                    $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                    $responseProcessing.=$this->createResponseProcessingXml($ind_quesScore, $TemplateFile, $questlist['ID'],$objJson);
					

                    $Quest_title = $this->formatJson($objJson->{'question_title'}, 0);
                    $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'},0,0);

                    $correctFeedback = $objJson->{'correct_feedback'}[0]->{'val1'};  //Correct Feedback text
                    $corrFedAltTagVal = $objJson->{'correct_feedback'}[0]->{'val2'}; //Alt Tag

                    $incorrectFeedback = $objJson->{'incorrect_feedback'}[0]->{'val1'};  //Incorrect Feedback text
                    $incorrFedAltTagVal = $objJson->{'incorrect_feedback'}[0]->{'val2'}; //Alt Tag
                    // For Hint 
                    //$hint = $this->formatJson($objJson->{'hint'});
                    $hint = $objJson->{'hint'}[0]->{'val1'};  //Hint text
                    $hintAltTagVal = $objJson->{'hint'}[0]->{'val2'}; //Alt Tag
                    //For Eassy
                    $essayText = $this->formatJson($objJson->{'essay'});

                    //For LTD
                    $imageSrc = $this->formatJson($objJson->{'image'});

                    $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                    $ind_quesdifficulty = $objJson->{'metadata'}[1]->{'val'};
                    $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);

                    foreach ($objJson->{'metadata'} as $metaSettings) {
                        if ($metaSettings->text == "Max_Score") {
							$maxScoreQuest+=$metaSettings->val; //Max Score
						}
					}

                    if ($TemplateFile == 'Essay') {
                        $maxScoreQuest+= $objJson->{'metadata'}[1]->{'val'};//Max Score
                                        }

                    //  Get Assigned Metadata for the question
                    $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                    $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                    Site::myDebug('------QuestAssignedMetadata');
                    Site::myDebug($QuestAssignedMetadata);
                    if (!empty($QuestAssignedMetadata['RS'])) {
                        foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                            $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                            if ($arrMetadataValues) {
                                foreach ($arrMetadataValues as $metadataValues) {
                                    $arrValue = @explode($this->cfgApp->metaDataKeyValSeparator, $metadataValues);
                                    if ($arrValue['4'] >= 1) {
                                        $mkeyname = $arrMetadata['KeyName'];
                                        $metadataArray[$mkeyname] = $arrValue['2'];
                                    }
                                }
                            }
                        }
                    }
                    Site::myDebug('------metadataArray');

                    if (!preg_grep("/skill/i", array_keys($metadataArray))) {
                        $responseData['msgRespWar'] = 'Some Question has no skill metadata';
                        Site::myDebug('------metadataArray--- skill');
                    }
                    Site::myDebug($metadataArray);
                    //Question level Metadata code ends here
                    //Question Level Taxonomy Code starts
                    $site = & $this->registry->site;
                    $taxonomyListSql = "SELECT GROUP_CONCAT(SUBSTRING_INDEX( Taxonomy, ' ',1 )) AS taxo FROM Classification
				LEFT JOIN Taxonomies ON Taxonomies.ID=Classification.ClassificationID AND Taxonomies.isEnabled=1
				WHERE Classification.isEnabled=1 and EntityID=$questlist[ID]";
                    $taxonomyList = $site->db->getSingleRow($taxonomyListSql);
                    //Question Level Taxonomy code ends here

                    $i++;
                    //$questionList[] = $objJson->{'question_title'};
                    //$imsManifestArray[$i]['entity_title'] = $Entity_name;
                    $imsManifestArray[$sect + 1]['question_title_identifier'] = $objJson->{'question_title'};
                    $imsManifestArray[$sect + 1]['question_id_identifier'] = "QUE_" . $questlist['ParentID'];
                    $imsManifestArray[$sect + 1]['question_text'] = $objJson->{'question_text'}[0]->{'val1'};
                    $imsManifestArray[$sect + 1]['image'] = $objJson->{'image'}[0]->{'val1'};
                    $imsManifestArray[$sect + 1]['entity_metadata_array'] = $entityMetaArray;  // Assessment/Bank level metadata
                    $imsManifestArray[$sect + 1]['metadata_array'] = $metadataArray;           // Question level metadata
                    // $imsManifestArray[$i]['entity_taxonomy_string'] = explode_filtered(',', $entityTaxoList['taxo']);  //Assessment/Bank level taxonomy
                    // $imsManifestArray[$i]['taxonomy_string'] = explode_filtered(',', $taxonomyList['taxo']);  // Question level taxonomy
//------------------------------------------------------------------------------------


                    $questionTextImagesArray[$sect + 1][$i] = $this->getAssetsFromJSONdata($imsManifestArray[$sect + 1]['question_text']);
					
					if (!empty($Quest_Inst_text)) { // For Static Page.
							$questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($Quest_Inst_text));
							}

					if (!empty($objJson->{'choices'}[0]->{'val4'}) && $TemplateFile == 'FIBDropDown') { // for FIBDropDown
                                $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($objJson->{'choices'}[0]->{'val4'}));
                            }
                      if (!empty($objJson->{'image'}) && $TemplateFile == 'LabelDiagram') { // for LabelDiagram
                                $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($objJson->{'image'}));
                            }           
                    if (!empty($objJson->{'choices'})) {
                        foreach ($objJson->{'choices'} as $key => $value) {
                            $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($value->val2));
                            Site::myDebug($value->val2);
                        }
                    }
                    if (!empty($objJson->{'image'}[0]->{'val1'})) { // For MCSS/MCMS text with Image template
                        $questionTextImagesArray[$sect + 1][$i] = array_merge($questionTextImagesArray[$sect + 1][$i], $this->getAssetsFromJSONdata($objJson->{'image'}[0]->{'val1'}));
                        Site::myDebug($objJson->{'image'}[0]->{'val1'});
                    }
                    unset($entityMetaArray);
                    unset($metadataArray);
                }
                Site::myDebug("imsManifestArray File Data");
                $this->myDebug($questionTextImagesArray);
                $m = 1;
                foreach ($questionTextImagesArray as $key => $value) {

                    if (!empty($value)) {
                        $ka = 0;
                        foreach ($value as $k => $v) {
                            if (!empty($v)) {
                                foreach ($v as $a => $b) {
                                    $compositAsset[$m][$ka++] = $b;
                                }
                            }
                        }
                        $m++;
                    }
                }
                unset($questionTextImagesArray);
                $questionTextImagesArray = $compositAsset;
                $this->myDebug($questionTextImagesArray);
                $this->myDebug($imsManifestArray);
                $sect++;
                $TemplateFile = 'CompositePackage'; //$questlist['TemplateFile'];
                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/" . $TemplateFile . ".php";

                ob_start();
                if (file_exists($templateFilePath)) { //commented isExport condition as we are supporting all templates for QTI2.1 
                    include($templateFilePath);
                    $xmlStr = ob_get_contents();

                    ob_end_clean();

                    /* create multiple xml files with each question */

                    $qtifol = "{$temp_path_root}/QUE_{$questlist['ParentID']}.xml";

                    Site::myDebug($temp_path_root);
                    Site::myDebug($qtifol);
                    $myFile = $qtifol;
					/*$fh2 = fopen($myFile, 'w');
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
					fwrite($fh2, $xmlStr);
					fclose($fh2);*/
					
                    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
					$domxml = new DOMDocument('1.0');
					$domxml->preserveWhiteSpace = false;
					$domxml->formatOutput = true;
					$domxml->loadXML($xmlStr);
					$domxml->save($myFile);
                }
            }

            /* Composite package End */
        }

        Site::myDebug('----------imsManifestArray------1');
        Site::myDebug($imsManifestArray);
        /* for manifest file */
        ob_start();

        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/ImsManifest.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($temp_path_root . "/imsmanifest.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);

        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/AssesmentExamView.php");
        $xmlmaniStr = ob_get_contents();
        ob_end_clean();
        $fh2 = fopen($temp_path_root . "/assesmentExamView.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);

        /* end of creating manifest */

        $webpath = $temp_path_web . ".zip";
        $zipfile = $temp_path_root . ".zip";
        $srczippath = $temp_path_root;

        $auth->makeZip($srczippath, $zipfile);
        $this->myDebug("This is Web Path");
        $this->myDebug($webpath);

        return $zipfile;
    }

    /**
     * question exported in QTI1.2 format in zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   $Total_Img
     * @param    array $input
     * @param    interger $asmtID
     * @return   stirng
     *
     */
    //function exportQuestion(array $input,$asmtID)
    function exportQuestion(array $input) {
        global $Total_Img, $DBCONFIG;
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }

        $auth = new Authoring();
        if ($entityTypeId == 2) {
            $Assessment = new Assessment();
            //$AssessmentSettings = $Assessment->asmtDetail($EntityID);
            $AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
                $EntityID,
                $this->session->getValue('userID'),
                $this->session->getValue('isAdmin'),
                $this->session->getValue('instID')
                    ), 'nocount');
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
            $Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
            $Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
            $Entity_name = $this->getValueArray($AssessmentSettings, "AsmtName");
        } else if ($entityTypeId == 1) {
            $Bank = new Bank();
            $BankSettings = $Bank->bankDetail($EntityID);
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
            $Entity_name = $this->getValueArray($BankSettings, "BankName");
        } else {
            $qshuffle = "yes";
            $Entity_score_flag = "yes";
            $Entity_score = "";
        }
        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
        $ExportName = htmlentities($ExportName, ENT_QUOTES);
        $i = $i > 0 ? $i : 0;
        if ($input['action'] == "exportq") {
            $data = array(
                'ExportTitle' => $input['exportname'],
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => $i,
                'isEnabled' => '1'
            );
            $Exportid = $this->db->insert("ExportHistory", $data);
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $Exportid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $Exportid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/{$Exportid}.xml";
            $menifest_resources = "{$Exportid}.xml";
        } else {
            $guid = uniqid();
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $guid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $guid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/temp.xml";
            $menifest_resources = "temp.xml";
        }

        if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
                $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        } else {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            } else {
                $filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            }
        }
        $xmlStr = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $xmlStr .="<questestinterop>";
        $xmlStr .="<assessment title='{$ExportName}' ident='Asses_{$EntityID}' >";
        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
        Site::myDebug('---question data---');
        Site::myDebug($questions);
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;
        $totalquestions = count($questions);
        $qst = new Question();
        $objJSONtmp = new Services_JSON();

        if (!empty($questions)) {
            $metadata = new Metadata();
            foreach ($questions as $questlist) {
                $question_xml = $questlist;
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["JSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                $this->myDebug("This is New Json");
                $this->myDebug($sJson);
                $objJsonTemp = $objJSONtmp->decode($sJson);
                if (!isset($objJsonTemp))
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));

                $objJson = $objJsonTemp;
                $optionsList = $objJson->{'choices'};



                //  Get Assigned Metadata for this question
                //commented by nanda uncomment  as metaDataAssignedList is done.
                /*  $arrInputMetadata = array ("EntityID" => $questlist['ID'] , "EntityTypeID" => 3 );
                  $QuestAssignedMetadata      = $metadata->metaDataAssignedList($arrInputMetadata,"assign");
                  Site::myDebug('----------assignedmeytadata');
                  Site::myDebug($QuestAssignedMetadata);
                 */

                //Change it get Metadata Values
                //                $QuestLobList= $this->db->executeStoreProcedure('MapRepositoryLobList',array($questlist['ID']));
                //                $objJson->{'metadata'}[2]->{'val'}=$this->getValueArray($QuestLobList['RS'],'lobName','multiple');

                $Quest_title = $this->formatJson($objJson->{'question_title'}, 0);
                $Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'});
                $Quest_text = $objJson->{'question_text'};  
                $Quest_text = $this->formatJson($Quest_text);
                // $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $correct_feedback = $this->formatJson($objJson->{'correct_feedback'});
                $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                $qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);


                $i++;
                //$this->myDebug("sdfs---".$questlist['ParentID']."--".$questlist['SectionName']);

                if ($questlist['ParentID'] != 0 && $secid != $questlist['ParentID']) {
                    if ($sec == "innst" || $tempsec == "innst") {
                        $xmlStr .="</section>";
                        $sec = "";
                        $tempsec = "";
                    }
                    if ($sec != "innst") {
                        $secid = $questlist['ParentID'];
                        $xmlStr .='<section title="' . $questlist["SectionName"] . '" ident="SEC_INDENT_' . $questlist['ParentID'] . '">';
                        /* $xmlStr .='<objectives view = "Candidate"><flow_mat><material><mattext>To assess knowledge of the capital cities in Europe.
                          </mattext></material></flow_mat></objectives>'; */
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
                //  echo  $templateFilePath; die; exit;
                Site::myDebug('----------assignedmeytadata');
                Site::myDebug($templateFilePath);
                ob_start();
                if (file_exists($templateFilePath) && ($isExport == 'Y')) {
                    include($templateFilePath);
                    //include($this->cfg->rootPath.$this->cfgApp->exportStrGen."qti1_2/MCSSText.php");
                    $total_quest++;
                }
                $xmlStr .=ob_get_contents();
                ob_end_clean();
            }
            if ($sec == "innst" || $tempsec == "innst") {
                $xmlStr .="</section>";
            }
        }

        $xmlStr .= "</assessment>";
        $xmlStr .= "</questestinterop>";
        //$qtifol="temp/temp.xml";
        $myFile = $qtifol;
        //$myFile = $this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/".$qtifol;

        $fh2 = fopen($myFile, 'w');
        //$this->myDebug($xmlStr);
        $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
        fwrite($fh2, $xmlStr);
        fclose($fh2);

        /* for manifest file */
        ob_start();
        include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti1_2/ImsManifest.php");
        $xmlmaniStr .=ob_get_contents();
        ob_end_clean();
        //$fh2 = fopen($this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/{$temp_path}/ImsManifest.xml",'w');
        $fh2 = fopen($temp_path_root . "/ImsManifest.xml", 'w');
        fwrite($fh2, $xmlmaniStr);
        fclose($fh2);
        /* end of creating manifest */

        if (!isset($input['opt'])) {
            if ($input['action'] == "exportq") {
                if ($DBCONFIG->dbType == 'Oracle') {
                    $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
                } else {
                    $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
                }

                $dbdata1 = array(
                    'QuestCount' => $total_quest
                );
                $this->db->update('ExportHistory', $dbdata1, $condition1);
            }

            /* $webpath=$this->cfg->wwwroot.$this->cfgApp->exportDataGen."qti1_2/".$temp_path.".zip";
              $zipfile = $this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/{$temp_path}.zip";
              $srzippath=$this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/".$temp_path;
              $auth->makeZip($srzippath,$zipfile); */
            $webpath = $temp_path_web . ".zip";
            $zipfile = $temp_path_root . ".zip";
            $srczippath = $temp_path_root;
            $auth->makeZip($srczippath, $zipfile);
            $this->myDebug("This is Web Path");
            $this->myDebug($webpath);
            print "{$webpath}";
        } else {
            return $guid;
        }
    }

    public function formatCdata($str, $cdata = 1, $decode = 1) {
        if ($str != "") {
            if ($decode == 1) {
                $str = html_entity_decode($str);
            }
            $str = str_replace("<p>", "", $str);
            $str = str_replace("</p>", "", $str);
            $str = str_replace("&lt;p&gt;", "", $str);
            $str = str_replace("&lt;/p&gt;", "", $str);
			$str = trim($str, "<br>");
			$str = trim($str, "<br/>");
			$str = trim($str, "<br />");
            if ($cdata == 1) {
                $str = "<![CDATA[<span style='font-size:12pt'>" . $str . "</span>]]>";
            }
        }
        return $str;
    }

    public function exportPegasus(array $input) {
        global $DBCONFIG;
        $EntityTypeID = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $action = $input['action'];
        $questids = $input['questids'];
        $questids = str_replace("||", ",", $questids);
        $questids = trim($questids, '|');
        $questNos = @count(@explode(',', $questids));
        $ExportTitle = $input['exportname'];

        $qst = new Question();

        if ($action == 'exportq') {
            $data = array(
                'ExportTitle' => $ExportTitle,
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $EntityTypeID,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => $questNos,
                'isEnabled' => '1'
            );

            $guid = $this->db->insert('ExportHistory', $data);
            $arrData = array(
                'mainDirPath' => 'persistent',
                'subDirPath' => 'exports/pegasus/' . $guid . '/'
            );
            $destpath = $this->getDataPath($arrData);
        }

        if ($action == 'testq') {
            $quid = uniqid();
            $arrData = array(
                'mainDirPath' => 'temp',
                'subDirPath' => 'exports/pegasus/' . $guid . '/'
            );
            $destpath = $this->getDataPath($arrData);
        }

        $quid OR $quid = uniqid();
        $filter = '';

        if ($input['selectall'] != 'true') {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter .= ( $EntityTypeID == '-1') ? 'mrq."QuestionID" in (' . $questids . ') AND ' : 'mrq."ID" in (' . $questids . ') AND ';
            } else {
                $filter .= ( $EntityTypeID == '-1') ? 'mrp.QuestionID in (' . $questids . ') AND ' : 'mrq.ID in (' . $questids . ') AND ';
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $filter .= ' ( mrq."SectionName" = ""  OR   mrq."SectionName" is null) ';
        } else {
            $filter .= ' ( mrq.SectionName = ""  OR   mrq.SectionName is null) ';
        }



        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = array(
                'mrq."ParentID"',
                'mrq."SectionName"',
                'qst."JSONData"',
                'qtp."HTMLTemplate"',
                'qtp."RenditionMode"',
                //'qto."isStatic"',
                'tpc."CategoryCode"',
                'tpc."CategoryName"',
                'qst."XMLData"'
            );
        } else {
            $displayField = array(
                'mrq.ParentID',
                'mrq.SectionName',
                'qst.JSONData',
                'qtp.HTMLTemplate',
                'qtp.RenditionMode',
                //'qto.isStatic',
                'tpc.CategoryCode',
                'tpc.CategoryName',
                'qst.XMLData'
            );
        }

        $displayField = implode(', ', $displayField);

        $questions = $this->db->executeStoreProcedure(
                'DeliveryQuestionList', array(
            '-1', '-1', '-1', '-1',
            $filter, $EntityID, $EntityTypeID, '0', $displayField
                ), 'nocount'
        );


//print_r($questions); die;
        $n_questions = count($questions);

        $assessment = new Assessment();

        if ($EntityTypeID == 2) {
            $assessment_settings = $assessment->asmtDetail($EntityID);
        } else {
            $default_settings = $assessment->defaultSettings();
            $default_settings_id = explode(',', $default_settings['ID']);
            $default_settings_value = explod(',', $default_settings['DefaultValue']);

            if (!empty($default_settings_id)) {
                $i = 0;

                foreach ($default_settings_id as $int) {
                    $default_settings['Setting_' . $int] = $default_settings_value[$i];
                    $i++;
                }
            }

            $assessment_settings = $default_settings;
        }

        $qscore_flag = ($this->getAssociateValue($assessment_settings, 'Score') == '1') ? 'yes' : 'no';
        $qhint = ($this->getAssociateValue($assessment_settings, 'Hint') == '1') ? 'yes' : 'no';
        $minutes = ($this->getAssociateValue($assessment_settings, 'Minutes')) ? $this->getAssociateValue($assessment_settings, 'Minutes') : 0;

        $qscore = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
        $entity_name = $this->getValueArray($AssessmentSettings, "AsmtName");


        if ($questions) {
            $json_ws = new Services_JSON();
            //array_shift($questions);
            $ret_questions = array();
            foreach ($questions as $question) {
                //$question = $question[];

                $json_data = $question['JSONData'];
                $json_data = str_replace("&#39;", "'", $json_data);
                $json_data = str_replace("&#039;", "'", $json_data);
                $json_data = $qst->removeMediaPlaceHolder($json_data);
                $json_data_tmp = $json_ws->decode($json_data);
                $json_data_tmp OR $json_data_tmp = $json_obj->decode(stripslashes($json_data));
                $json_data_tmp AND $question['JSONData'] = $json_data_tmp;
                $json_data_tmp AND $objJson = $json_data_tmp;
                $question['Title'] = htmlentities($question['Title'], ENT_COMPAT);
                //   $question['Title']= htmlentities( $question['Title']);

                $Quest = new stdClass();

                $Quest->title = $this->formatJson(htmlentities($objJson->{'question_title'}, ENT_COMPAT), 0);
                //   $Quest->title = $this->formatJson(htmlentities($objJson->{'question_title'}), 0);
                $Quest->Inst_text = $this->formatJson($objJson->{'instruction_text'});
                $Quest->text = $this->formatCdata($objJson->{'question_text'});
                $Quest->shortanswer = $this->formatCdata($objJson->{'shortanswer'});
                $Quest->descriptive = $this->formatCdata($objJson->{'descriptive'});
                //$Quest->text = $this->formatJson($Quest->text);
                $Quest->incorrect_feedback = $this->formatCdata($objJson->{'incorrect_feedback'});
                $Quest->correct_feedback = $this->formatCdata($objJson->{'correct_feedback'});
                $Quest->ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
                $Quest->qusetionScore = $this->qtiGetQuesScore($qscore_flag, $qscore, $n_questions, $Quest->ind_quesScore);
                $question['hint'] = $this->formatJson($objJson->{'hint'});
                //$Quest->choices = $this->formatJson($objJson->{'choices'});
                $choices = array();
                // $metadata = array();

                if ($objJson->{'choices'}) {
                    foreach ($objJson->{'choices'} as $choice) {
                        $choices[] = array(
                            'val1' => ($choice->val1),
                            'val2' => $this->formatCdata($choice->val2),
                            'val3' => $this->formatCdata($choice->val3)
                        );
                    }
                }
                //       foreach ($objJson->{'metadata'} as $meta)
                //     {
                //     $metadata[] = array(
                //       'text' => $meta->text,
                //       'val' => $meta->val
                //   );
                //  }

                $sql = "SELECT  EntityID,MetaDataValue AS MetaDataValue,KeyID,MetaDataName,ValueID
				FROM MapMetaDataEntity
					LEFT JOIN  MetaDataValues ON MetaDataValues.ID= MapMetaDataEntity.KeyValueID AND MetaDataValues.isEnabled=1
					LEFT JOIN MapMetaDataKeyValues ON MapMetaDataKeyValues.ID= MapMetaDataEntity.KeyValueID AND MapMetaDataKeyValues.isEnabled=1
					LEFT JOIN MetaDataKeys ON MetaDataKeys.ID=MapMetaDataKeyValues.KeyID AND MetaDataKeys.isEnabled=1
				WHERE  MapMetaDataEntity.isEnabled=1    AND EntityID=$question[ID]
				ORDER BY KeyValueID DESC";


                $site = & $this->registry->site;
                $question['metadata'] = array();
                $question['additional_metadata'] = array();
                $question['static_metadata'] = array();
                $metas = $site->db->getRows($sql);

                $question['static_metadata'] = array("qmd_itemtype", "qmd_levelofdifficulty", "qmd_maximumscore", "qmd_minimumscore", "qmd_status", "qmd_toolvendor", "qmd_topic", "qmd_weighting", "qmd_HighStake", "qmd_Hidden", "qmd_BloomsID", "qmd_CopyRight");
///if(is_array($metas) && count($metas)>0)
//{
                foreach ($metas as $meta) {
                    Site::myDebug('----------MetadataRash1414');
                    Site::myDebug($meta['MetaDataValue']);
                    $meta['MetaDataValue'] = str_replace("&", "and", $meta['MetaDataValue']);
                    if (preg_match("/^qmd/", $meta['MetaDataName'])) {

                        $question['additional_metadata'][$meta['MetaDataName']] = $meta['MetaDataValue'];
                    } else {
                        $question['metadata'][$meta['MetaDataName']] = $meta['MetaDataValue'];
                    }
                }

                $sql = "SELECT GROUP_CONCAT(SUBSTRING_INDEX( Taxonomy, ' ',1 )) AS sq FROM Classification
				LEFT JOIN Taxonomies ON Taxonomies.ID=Classification.ClassificationID AND Taxonomies.isEnabled=1
				WHERE Classification.isEnabled=1 and EntityID=$question[ID] ";
                $res_seq = $site->db->getSingleRow($sql);
                $question['sequs'] = explode_filtered(',', $res_seq['sq']);
                //  $choices      = str_replace( "&#039;", "'", $choices);
                $question['choices'] = $choices;
                // $question['metadata'] = $metadata;
                // $Quest      = str_replace( "&#039;", "'", $Quest);
                $question['Q'] = $Quest;
                Site::myDebug('----------Choices Rashmita99');
                Site::myDebug($Quest);
                $ret_questions[] = $question;
                Site::myDebug('----------Metadat Rash12');
                Site::myDebug($question['static_metadata']);
            }

            $questions = $ret_questions;
        }


        $data = array(
            'asmt' => $assessment_settings,
            'quests' => $questions,
            'lms' => array('id' => $quid)
        );
        Site::myDebug('peagaus data');
        Site::myDebug($data);
        return $data;
    }

    /**
     * To generate Question and answers in pdf format and given url to download as zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    array $input
     * @param    interger $asmtID
     * @return   stirng
     *
     */
    //function exportPdf(array $input, $asmtID) {
    function exportPdf(array $input) {
        global $DBCONFIG;
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $action = $input['action'];
        $questids = $input['questids'];
        $questids = str_replace("||", ",", $questids);
        $questids = trim($questids, "|");
        $questNos = @count(@explode(",", $questids));
        $expTitle = $input['exportname'];
        $expTitle = htmlentities($expTitle, ENT_QUOTES);
        $qst = new Question();
        if ($action == "exportq") {
            $data = array(
                'ExportTitle' => $expTitle,
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => $questNos,
                'isEnabled' => '1'
            );
            $guid = $this->db->insert("ExportHistory", $data);
            $pdfDestFolder = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'exports/pdf/' . $guid . '/'));
        }

        if ($action == "testq") {// test export of questions
            $guid = uniqid();
            $pdfDestFolder = $this->getDataPath(array('mainDirPath' => 'temp', 'subDirPath' => 'exports/pdf/' . $guid . '/'));
        }
        if ($input['selectall'] != "true") {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
        } else {
            $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
        }
        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\", tpc.\"CategoryCode\", tpc.\"CategoryName\", qst.\"XMLData\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic, tpc.CategoryCode, tpc.CategoryName, qst.XMLData ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');

        $Assessment = new Assessment();
        if ($entityTypeId == 2) {
            $AssessmentSettings = $Assessment->asmtDetail($EntityID);

            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
        } else {
            $DefaultSettings = $Assessment->defaultSettings();
            $DefaultSettingsID = explode(',', $DefaultSettings['ID']);
            $DefaultSettingsValue = explode(',', $DefaultSettings['DefaultValue']);

            $i = 0;
            $DefaultSettings = '';
            if (!empty($DefaultSettingsID)) {
                foreach ($DefaultSettingsID as $int) {
                    $DefaultSettings["Setting_" . $int] = $DefaultSettingsValue[$i];
                    $i++;
                }
            }
            $AssessmentSettings = $DefaultSettings;
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
        }

        // Parameters initialized for PDF genration
        $strQuestionTemplate = '';
        $strAnsTemplate = '';
        $questionCnt = 1;
        $answerCnt = 1;
        $sectionCnt = 0;
        $staticCnt = 0;
        $arrSection = array();
        $objJSONtmp = new Services_JSON();
        if (!empty($questions)) {
            foreach ($questions as $questlist) {
                $JsonPdf = $this->stripJsonData($questlist['JSONData']);
                $JsonPdf = $qst->removeMediaPlaceHolder($JsonPdf);
                $this->myDebug("This is JSON");
                $this->myDebug($JsonPdf);
                $objJsonTemp = $objJSONtmp->decode($JsonPdf);
                if (!isset($objJsonTemp)) {
                    Site::myDebug("---------No Object Genrated::objJsonTemp");
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($JsonPdf));
                }
                $dataJson = $objJsonTemp;
                Site::myDebug("---------After Trsanformation");
                Site::myDebug($dataJson);
				
		$Quest_text = $dataJson->{'question_text'}[0]->{'val1'};  //Question Text
                // Alter Image src from question text 
                if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
                        $textDocumentArray = array('textDocument' => $Quest_text, 'imageSRC' => 'images/', 'temp_path_root' => $pdfDestFolder, 'temp_path_web' => $pdfDestFolder, 'assetinfo_question' => $Quest_text);

					$Quest_text = $this->changeImageSRC($textDocumentArray);

					$regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
					if (preg_match_all($regex, $Quest_text, $QuestTextNew)) {
						if ($QuestTextNew) {
							foreach ($QuestTextNew as $val) {
								$Quest_text = str_replace($val, '$$$', $Quest_text);
							}
						}
					}$newQuestionText = explode("$$$", $Quest_text);
					$p = 0;
					$Quest_text = '';
					foreach ($newQuestionText as $val) {
						$valnew = $val . '' . $QuestTextNew[0][$p] . '';
						$Quest_text.=$valnew;
						$p++;
					}
                }

                $dataJson->templateName = $questlist['TemplateFile'];
                $questTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "pdf/templates/" . $dataJson->templateName . ".php";
                $ansTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "pdf/templates/" . $dataJson->templateName . "Ans.php";

                if (file_exists($questTemplatePath)) {
                    // Get Meta data for Score and Learning Object
                    if (!empty($dataJson->metadata)) {
                        foreach ($dataJson->metadata as $dataJsonMetaData) {
                            if ($dataJsonMetaData->text == "Score") {
                                $dataJson->score = $score = $dataJsonMetaData->val;
                            } else if ($dataJsonMetaData->text == "Learning Object") {
                                $dataJson->learningObject = $learningObject = $dataJsonMetaData->val;
                            }
                        }
                    }

                    if (isset($dataJson->{'video'})) { // For Video
                        $xmlData = html_entity_decode($dataJson->{'video'});
                        if ((strpos($xmlData, "<param") > -1) || (strpos($xmlData, "<video") > -1) || (strpos($xmlData, "<img") > -1)) {
                            Site::myDebug("--------videoxmlpath");
                            Site::myDebug($xmlData);

                            if (strpos($xmlData, "<param") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<param");
                            } else if (strpos($xmlData, "<video") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<video");
                            } else if (strpos($xmlData, "<img") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<img", "src");
                            } else if (strpos($xmlData, "<img") > -1) {
                                $filepath = $this->getImageUrl($xmlData);
                            }

                            $arrVideoAndImgUrl = $this->getVideoAndImgUrl($filepath);

                            $dataJson->imageUrl = $imageUrl = $arrVideoAndImgUrl['imageUrl'];
                            $dataJson->videoUrl = $videoUrl = $arrVideoAndImgUrl['videoUrl'];
                            unset($arrVideoandImgUrl);
                        }
                    }  // if(isset($dataJson->{'video'}))

                    if (isset($dataJson->{'image'})) {
                        $xmlData = html_entity_decode(urldecode($dataJson->{'image'}));
                        $dataJson->imageUrl = $this->getImgHttpUrl($xmlData);
                    }
                    Site::myDebug("------JSONDATA");

                    // INC Answer Template
                    if (file_exists($ansTemplatePath)) {
                        if (($answerCnt - 1) > 0 && ( $answerCnt - 1 ) % 2 == 0) {
                            $strAnsTemplate .= "<tr><td><DIV style='page-break-before: always;'>&nbsp;</DIV></td></tr>";
                        }
                        ob_start();
                        include( $ansTemplatePath );
                        $strAnsTemplate .= ob_get_contents();
                        ob_end_clean();
                        $answerCnt++;
                    }

                    // INC Question Template
                    if (file_exists($questTemplatePath)) {
                        if (($questionCnt - 1) > 0 && ($questionCnt - 1) % 2 == 0) {
                            $strQuestionTemplate .= "<tr><td><DIV style='page-break-before: always;'>&nbsp;</DIV></td></tr>";
                        }

                        ob_start();
                        include( $questTemplatePath );
                        $strQuestionTemplate .= ob_get_contents();
                        ob_end_clean();
                        $questionCnt++;

                        // Cnt for Static Cointent
                        if ($questlist['isStatic'] == "Y") {
                            $staticCnt++;
                        }

                        // Cnt for Section
                        if ($questlist['SectionName'] != "" && $questlist['ParentID'] != 0 && (!in_array($questlist['SectionName'], $arrSection))) {
                            $sectionCnt++;
                            $arrSection[] = $questlist['SectionName'];
                        }
                    }
                    unset($dataJson);
                }// if ( file_exists($questTemplatePath) ) {
            } // foreach($questions as $questlist)
        }


        $arrHeaderData = array();
        $arrHeaderData['asmtName'] = $expTitle;
        $arrHeaderData['minutes'] = $minutes;
        $arrHeaderData['instName'] = $this->session->getValue('instName');
        $arrHeaderData['questionsCnt'] = ( $questionCnt - 1 ) - $staticCnt;
        $arrHeaderData['sectionCnt'] = $sectionCnt;
        $arrHeaderData['staticCnt'] = $staticCnt;
        $this->makeQuestionAndAnswerPdf($pdfDestFolder, $strQuestionTemplate, $strAnsTemplate, $arrHeaderData);
        unset($arrHeaderData);
        // IN case any Buffer
        ob_end_clean();
        if ($action == "exportq") { // export of questions
            $sourceFolder = $pdfDestFolder;
            $zipfile = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $guid . ".zip";
            $webzipfile = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $guid . ".zip";
            $this->makeZip($sourceFolder, $zipfile);
            //$fileDownloadPath = trim ($this->cfgApp->exportDataGen."pdf/finalexport", "\/" );
            $fileDownloadPath = $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportPdf;
        } else {
            $sourceFolder = $pdfDestFolder;
            $zipfile = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $guid . ".zip";
            $webzipfile = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $guid . ".zip";
            $this->makeZip($sourceFolder, $zipfile);
            //$fileDownloadPath = trim ($this->cfgApp->exportDataGen."pdf/temp", "\/" );
            $fileDownloadPath = $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportPdf;
        }

        $urlToDownload = $this->cfg->wwwroot . "/authoring/download/f:{$guid}.zip|path:{$fileDownloadPath}|rand:" . uniqid();
        print "{$urlToDownload}";
        //preview quiz end
    }

    /**
     *  export assessment to word file
      1.create word html
      2. create file
      3. download word file ..
     * */
    function exportWordPDF(array $input) {

        $this->setWordInitial($input);

        //get questions
        $questions = $this->getQuestions($input);
        // print_r($questions);
        Site::myDebug('--RRRR');
        Site::myDebug($questions);
        foreach ($questions as & $questlist) {
            Site::myDebug('--RRRRSS');
            Site::myDebug($questlist[ID]);
            // echo "<pre>";
            /* $sql="SELECT   EntityID,MAX(MetaDataValue) AS MetaDataValue,KeyID,MetaDataName,MAX(ValueID)
              FROM MapMetaDataEntity
              LEFT JOIN  MetaDataValues ON MetaDataValues.ID= MapMetaDataEntity.KeyValueID
              LEFT JOIN MapMetaDataKeyValues ON MapMetaDataKeyValues.ID= MapMetaDataEntity.KeyValueID
              LEFT JOIN MetaDataKeys ON MetaDataKeys.ID=MapMetaDataKeyValues.KeyID
              WHERE EntityID=$questlist[ID]
              GROUP BY MetaDataName
              ORDER BY KeyValueID DESC "; */

            $sql = "SELECT  EntityID,MetaDataValue AS MetaDataValue,KeyID,MetaDataName,ValueID
				FROM MapMetaDataEntity
					LEFT JOIN  MetaDataValues ON MetaDataValues.ID= MapMetaDataEntity.KeyValueID AND MetaDataValues.isEnabled=1
					LEFT JOIN MapMetaDataKeyValues ON MapMetaDataKeyValues.ID= MapMetaDataEntity.KeyValueID AND MapMetaDataKeyValues.isEnabled=1
					LEFT JOIN MetaDataKeys ON MetaDataKeys.ID=MapMetaDataKeyValues.KeyID AND MetaDataKeys.isEnabled=1
				WHERE  MapMetaDataEntity.isEnabled=1    AND EntityID=$questlist[ID]
				ORDER BY KeyValueID DESC";


            $site = & $this->registry->site;
            $questlist['meta_data'] = array();
            $questlist['meta_data_allowed'] = array();
            $metas = $site->db->getRows($sql);

            $metaDataIncludeList = array('Page-Reference', 'Topic', 'Skill', 'Difficulty', 'Objective', 'Rationale', 'Item_Analysis');
            foreach ($metas as $meta) {

                if (!preg_match("/^qmd/", $meta['MetaDataName'])) {
                    $questlist['meta_data'][$meta['MetaDataName']] = $meta['MetaDataValue'];
                }

                if (in_array($meta['MetaDataName'], $metaDataIncludeList)) {
                    $questlist['meta_data_allowed'][$meta['MetaDataName']] = $meta['MetaDataValue'];
                }
                /*   else
                  {
                  $question['metadata'][$meta['MetaDataName']] = $meta['MetaDataValue'];
                  }
                  $questlist['meta_data'][$meta['MetaDataName']] = $meta['MetaDataValue']; */
            }

            Site::myDebug('----meta_data-------------');
            Site::myDebug($questlist['meta_data']);
            Site::myDebug('----meta_data_allowed-------------');
            Site::myDebug($questlist['meta_data_allowed']);

            $sql = "SELECT GROUP_CONCAT(SUBSTRING_INDEX( Taxonomy, ' ',1 )) AS sq FROM Classification
				LEFT JOIN Taxonomies ON Taxonomies.ID=Classification.ClassificationID AND Taxonomies.isEnabled=1
				WHERE Classification.isEnabled=1 and EntityID=$questlist[ID] ";
            $res_seq = $site->db->getSingleRow($sql);

	    if ($res_seq['sq']) {
		$questlist['sequs'] = explode_filtered(',', $res_seq['sq']);
        }
	}
        list($qscore, $qhint, $minutes, $AssessmentSettings) = $this->getAssessmentSetting($input);

        list($strQuestionTemplate, $strAnsTemplate, $arrHeaderData) = $this->createStringTemplate($questions, $input['file']);
        // $arrHeaderData['asmtName']     = $input['exportname'];
        $arrHeaderData['asmtName'] = $AssessmentSettings['AsmtTitle'];
        $arrHeaderData['minutes'] = $minutes;


        $strQuestionHeader = $this->getWordHtmlContents($this->cfg->rootPath . $this->cfgApp->exportStrGen . "word/templates/" . "Header.php", $arrHeaderData);
        // Footer Contents
        $strQuestionFooter = $this->getWordHtmlContents($this->cfg->rootPath . $this->cfgApp->exportStrGen . "word/templates/" . "Footer.php", $arrHeaderData);

        $strQuestionContent = $strQuestionHeader . $strQuestionTemplate . $strQuestionFooter;

        $this->createWordPDFFile($strQuestionContent, $input);

        $this->downloadWordFile($input);
    }

    /**
      get html content of word
     * */
    function getWordHtmlContents($filename, &$arrHeaderData) {

        if (is_file($filename)) {
            ob_start();
            include $filename;
            return ob_get_clean();
        }
        return false;
    }

    /**

      create string from  template of question and answer

      from question array..
     * */
    function createStringTemplate($questions, $exportFile) {

        $objJSONtmp = new Services_JSON();
        $questionCnt = 1;
        $answerCnt = 1;
        $sectionCnt = 0;
        $staticCnt = 0;
        $strQuestionTemplate = '';
        $strAnsTemplate = '';
        $arrSection = array();
        $strPegasus = '';
        $tempStrPegasus = 1;
        $queTitleArr = array('ce', 'qr');
        foreach ($questions as $questlist) {
            $JsonPdf = $this->stripJsonData($questlist['JSONData']);
            // $JsonPdf = $qst->removeMediaPlaceHolder($JsonPdf);
            // Site::myDebug("------makeQuestionHTML1json");
            Site::myDebug('--QQQQTT');
            Site::myDebug($questlist['JSONData']);
            $dataJson = $objJSONtmp->decode($JsonPdf);
            $question_title = $dataJson->question_title;

            if ($exportFile == 'doc' || $exportFile == 'pdf') {
                $queStrTitle = @explode(",", $question_title);

                $questionCnt = $queStrTitle[0];
                //  if (preg_match("/^QQ1/", $questionCnt)) {
                $questionCnt = str_replace("QQ", "PQ", $questionCnt);
                //  }
                $newLine = '<br />';
            } else {
                $questionCnt = $questionCnt;
                $newLine = '.&nbsp;';
            }
            $questionCnt = str_replace("QQ", "PQ", $questionCnt);
            $strCusTitle = strtolower(substr($question_title, 0, 2)); // for CE and QR
            $strCusTitlePre = strtolower(substr($question_title, 0, 3)); // for Pre
            $strCusTitlePost = strtolower(substr($question_title, 0, 4)); // for Post
            $categoryName = strtolower($questlist['CategoryName']);

            if (in_array($strCusTitle, $queTitleArr)) {
                if ($strCusTitle != strtolower($tempStrPegasus)) {
                    $strPegasus = strtolower($strCusTitle);
                } else {
                    $strPegasus = strtolower($tempStrPegasus);
                }
            } elseif ($strCusTitlePre == 'pre') {
                if ($strCusTitlePre != strtolower($tempStrPegasus)) {
                    $strPegasus = strtolower($strCusTitlePre);
                } else {
                    $strPegasus = strtolower($tempStrPegasus);
                }
            } elseif ($strCusTitlePost == 'post') {
                if ($strCusTitlePost != strtolower($tempStrPegasus)) {
                    $strPegasus = strtolower($strCusTitlePost);
                } else {
                    $strPegasus = strtolower($tempStrPegasus);
                }
            } else {
                if ($categoryName != strtolower($tempStrPegasus)) {
                    $strPegasus = $categoryName;
                } else {
                    $strPegasus = strtolower($tempStrPegasus);
                }
            }

            if ($strPegasus != strtolower($tempStrPegasus)) {
                if ($strPegasus == 'ce') {
                    $customQueTitle = $this->cfgApp->arrShortForms['CE'];
                } else if ($strPegasus == 'qr') {
                    $customQueTitle = $this->cfgApp->arrShortForms['QR'];
                } else if ($strPegasus == 'pre') {
                    $customQueTitle = $this->cfgApp->arrShortForms['Pre'];
                } else if ($strPegasus == 'post') {
                    $customQueTitle = $this->cfgApp->arrShortForms['Post'];
                } else {
                    $customQueTitle = $questlist['CategoryName'];
                    $strPegasus = strtolower($customQueTitle);
                }
            } else {
                $customQueTitle = '';
            }
            $tempStrPegasus = $strPegasus;

            // Site::myDebug($dataJson);
            $questTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "word/templates/" . $questlist['TemplateFile'] . ".php";
            if (file_exists($questTemplatePath)) {
                $this->getMetadataScoreLO($dataJson);

                ob_start();
                include($questTemplatePath);
                $strQuestionTemplate .= "<p style='height:6px;'>&nbsp;</p>";
                $strQuestionTemplate .= "<p class=MsoNormal><b style='mso-bidi-font-weight:normal'><span
                                            style='font-size:11.0pt;mso-bidi-font-size:10.0pt'>" . $customQueTitle . "<o:p></o:p></span></b></p>
                                            <p class=MsoNormal><b style='mso-bidi-font-weight:normal'><span
                                            style='font-size:10.0pt'><o:p>&nbsp;</o:p></span></b></p>";
                $strQuestionTemplate .= ob_get_contents();
                //$strQuestionTemplate .= "<p class=MsoNormal><b style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt'><o:p>&nbsp;</o:p></span></b></p>";
                $strQuestionTemplate .= "";

                ob_end_clean();
                $questionCnt++;

                // Cnt for Static Cointent
                if ($questlist['isStatic'] == "Y") {
                    $staticCnt++;
                }

                // Cnt for Section
                if ($questlist['SectionName'] != "" && $questlist['ParentID'] != 0 && (!in_array($questlist['SectionName'], $arrSection))) {
                    $sectionCnt++;
                    $arrSection[] = $questlist['SectionName'];
                }
            }
        }
        $arrHeaderData = array();
        $arrHeaderData['instName'] = $this->session->getValue('instName');
        $arrHeaderData['questionsCnt'] = ( $questionCnt - 1 ) - $staticCnt;
        $arrHeaderData['sectionCnt'] = $sectionCnt;
        $arrHeaderData['staticCnt'] = $staticCnt;
        unset($dataJson);
        return array($strQuestionTemplate, $strAnsTemplate, $arrHeaderData);
    }

    /**

      get metadata score and learning object ..

     * */
    function getMetadataScoreLO(&$dataJson) {

        if (!empty($dataJson->metadata)) {

            foreach ($dataJson->metadata as $dataJsonMetaData) {
                if ($dataJsonMetaData->text == "Score") {
                    $dataJson->score = $dataJsonMetaData->val;
                } else if ($dataJsonMetaData->text == "Learning Object") {
                    $dataJson->learningObject = $dataJsonMetaData->val;
                }
            }
        }
    }

    /**
     * set initial for word export

     * */
    function setWordInitial($input) {
        if ($input['action'] == "exportq") {

            $this->guidWord = $this->saveExport($input);
            $this->wordDestFolder = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportWord;
            $this->fileDownloadPath = $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportWord;
        } else if ($input['action'] == "testq") { // test export of questions
            $this->guidWord = uniqid();
            $this->wordDestFolder = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportWord;
            $this->fileDownloadPath = $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . $this->cfgApp->exportWord;
        }
    }

    /**
     * save export data

     * */
    function saveExport($input) {
        $questNos = @count(@explode(",", $this->getQuestionIds($input['questids'])));
        $data = array(
            'ExportTitle' => $input['exportname'],
            'ExportType' => $input['exporttype'],
            'EntityTypeID' => $input['EntityTypeID'],
            'EntityID' => $input['EntityID'],
            'ExportBy' => $this->session->getValue('userID'),
            'ExportDate' => $this->currentDate(),
            'QuestCount' => $questNos,
            'isEnabled' => '1'
        );
        $guid = $this->db->insert("ExportHistory", $data);
        return $guid;
    }

    /**
     * get question ids
     * */
    function getQuestionIds($questids) {
        $questids = str_replace("||", ",", $questids);
        $questids = trim($questids, "|");
        return $questids;
    }

    /**
     * get assessment setting
     * */
    function getAssessmentSetting($input) {
        $Assessment = new Assessment();
        if ($input['EntityTypeID'] == 2) {
            $AssessmentSettings = $Assessment->asmtDetail($input['EntityID']);

            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
        } else {
            $DefaultSettings = $Assessment->defaultSettings();
            $DefaultSettingsID = explode(',', $DefaultSettings['ID']);
            $DefaultSettingsValue = explode(',', $DefaultSettings['DefaultValue']);
            $i = 0;
            $DefaultSettings = '';
            if (!empty($DefaultSettingsID)) {
                foreach ($DefaultSettingsID as $int) {
                    $DefaultSettings["Setting_" . $int] = $DefaultSettingsValue[$i];
                    $i++;
                }
            }
            $AssessmentSettings = $DefaultSettings;
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
        }
        return array($qscore, $qhint, $minutes, $AssessmentSettings);
    }

    /**
     * get questions of particular assessment
     * */
    function getQuestions($input) {
        global $DBCONFIG;
        $questids = $this->getQuestionIds($input['questids']);
        if ($input['selectall'] != "true") {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($input['EntityTypeID'] == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
            } else {
                $filter = ($input['EntityTypeID'] == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
                // $filter.="qtp.TemplateFile in ('MCSSText', 'TrueFalse', 'FIBTextInput', 'MCMSText') AND ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\", tpc.\"CategoryCode\", tpc.\"CategoryName\", qst.\"XMLData\" ";
        } else {
            $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic, tpc.CategoryCode, tpc.CategoryName, qst.XMLData ";
        }
        $filter.=" and qtp.ID in (3,4, 2,16, 17,49,50,51)  ";
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("mrq.ParentID, mrq.Sequence", " ASC ", "-1", "-1", $filter, $input['EntityID'], $input['EntityTypeID'], "0", $displayField), 'nocount');

        return $questions;
    }

    /**
     *
     * create word file for export of assessment questions

     * */
    function createWordPDFFile($html, $input) {

        if ($input['file'] == 'pdf') {
            require_once( $this->cfg->rootPath . "/plugins/dompdf/dompdf_config.inc.php");
            spl_autoload_register('DOMPDF_autoload');
            Site::myDebug("------makeQuestionHTMLpdf");
            Site::myDebug($html);
            // Make Question PDF
            $html = $this->formatDataToPdf($html);
        }

        $questionFile = $this->wordDestFolder . $this->guidWord . "/question." . $input['file'];
        mkdir($this->wordDestFolder . $this->guidWord);
        file_put_contents($questionFile, $html);
        $questionFileHTML = $this->wordDestFolder . $this->guidWord . "/question.html";
        file_put_contents($questionFileHTML, $html);
    }

    /**
     *
     * will provide download and save option in browser of zip file of export word doc
     * */
    function downloadWordFile() {
        $zipfile = $this->wordDestFolder . $this->guidWord . ".zip";
        $this->makeZip($this->wordDestFolder . $this->guidWord, $zipfile);
        // $fileDownloadPath =$this->wordDestFolder;//.$this->guidWord."/";
        $urlToDownload = $this->cfg->wwwroot . "/authoring/download/f:{$this->guidWord}.zip|path:{$this->fileDownloadPath}|rand:" . uniqid();
        print "{$urlToDownload}";
    }

    /**
     * question exported in Moodle format in zip file
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    array $input
     * @param    interger $asmtID
     * @return   stirng
     *
     */
    //function exportMoodle(array $input,$asmtID)
    function exportMoodle(array $input) {
        $moodle = "moodle/";
        global $DBCONFIG;
        $qst = new Question();
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
            $this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
        }
        $auth = new Authoring();
        if ($entityTypeId == 2) {
            $Assessment = new Assessment();
            $AssessmentSettings = $Assessment->asmtDetail($EntityID);
            $qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
            $Entity_name = $this->getValueArray($AssessmentSettings, "AsmtName");
        } else if ($entityTypeId == 1) {
            $Bank = new Bank();
            $BankSettings = $Bank->bankDetail($EntityID);
            $qshuffle = "yes";
            $qscore = "yes";
            $Entity_name = $this->getValueArray($BankSettings, "BankName");
        } else {
            $qshuffle = "yes";
            $qscore = "yes";
        }
        $ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();

        if ($input['action'] == "exportq") {
            $data = array(
                'ExportTitle' => $input['exportname'],
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => $i,
                'isEnabled' => '1'
            );
            $Exportid = $this->db->insert("ExportHistory", $data);
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $Exportid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $Exportid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);

            $qtifol = "{$temp_path_root}/{$Exportid}.xml";
        } else {
            $guid = uniqid();
            // $temp_path="temp/".$guid;
            $temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $guid;
            $temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $guid;
            mkdir($temp_path_root, 0777, true);
            mkdir($temp_path_root . "/media", 0777);
            $qtifol = "{$temp_path_root}/temp.xml";
        }

        if ($input['selectall'] != "true") {
            $questids = $input['questids'];
            $questids = str_replace("||", ",", $questids);
            $questids = trim($questids, "|");

            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
            }
        }
        if ($DBCONFIG->dbType == 'Oracle') {
            $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
        } else {
            $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
        }
        $xmlStr = "<?xml version='1.0' encoding='UTF-8'?>";
        $xmlStr .="<quiz>";
        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\" , tpc.\"CategoryCode\" , qst.\"XMLData\" , qtp.\"isExport\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount'
        );
        $rootSecInc = 1;
        $sec = "";
        $total_quest = 0;

        $objJSONtmp = new Services_JSON();
        if (!empty($questions)) {
            foreach ($questions as $questlist) {
                $question_xml = $questlist;
                $TemplateFile = $questlist['TemplateFile'];
                $isExport = $questlist['isExport'];
                $sJson = $questlist["JSONData"];
                $sJson = $qst->removeMediaPlaceHolder($sJson);
                $objJsonTemp = $objJSONtmp->decode($sJson);
                if (!isset($objJsonTemp)) {
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                }
                $objJson = $objJsonTemp;
                $optionsList = $objJson->{'choices'};
                //Chage it to get MetaData values.
                //                $QuestLobList= $this->db->executeStoreProcedure('MapRepositoryLobList',array($questlist['ID']));
                //                $objJson->{'metadata'}[2]->{'val'}=$this->getValueArray($QuestLobList['RS'],'lobName','multiple');

                $Quest_title = $this->formatJson($objJson->{'question_text'});
                $Quest_text = $this->formatJson($objJson->{'question_text'});
                $Quest_Inst_text = $this->formatJson($objJson->{'instruction_text'});
                $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $correct_feedback = $this->formatJson($objJson->{'correct_feedback'});
                $incorrect_feedback = $this->formatJson($objJson->{'incorrect_feedback'});
                $i++;
                /* if($questlist['ParentID'] != 0 && $secid != $questlist['ParentID']){
                  if($sec == "innst"){
                  $xmlStr .="</section>";
                  $sec  = "";
                  }
                  if($sec != "innst"){
                  $secid = $questlist['ParentID'];
                  $xmlStr .="<section ident ='SEC_INDENT_".$questlist['ParentID']."' title='".$questlist["SectionName"]."'>";
                  $xmlStr .='<objectives view = "Candidate"><flow_mat><material><mattext>To assess knowledge of the capital cities in Europe.
                  </mattext></material></flow_mat></objectives>';
                  $sec = "innst";
                  }
                  }
                  else{
                  if($sec == "innst" && $questlist['ParentID'] == 0){
                  $xmlStr .="</section>";
                  $sec  = "";
                  }
                  } */
                $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . $moodle . $TemplateFile . ".php";
                ob_start();
                if (file_exists($templateFilePath)) {
                    include($templateFilePath);
                    $total_quest++;
                }
                $xmlStr .=ob_get_contents();
                ob_end_clean();
            }
            /* if($sec == "innst"){
              $xmlStr .="</section>";
              } */
        }

        $xmlStr .= "</quiz>";
        //$qtifol="temp/temp.xml";
        $myFile = $qtifol;
        // $myFile = $this->cfg->rootPath.$this->cfgApp->exportDataGen."$moodle".$qtifol;
        $fh2 = fopen($myFile, 'w');
        //$this->myDebug($xmlStr);
        fwrite($fh2, $xmlStr);
        fclose($fh2);

        if ($input['action'] == "exportq") {
            if ($DBCONFIG->dbType == 'Oracle') {
                $condition1 = $this->db->getCondition('and', array("\"ID\" = {$Exportid}", "\"isEnabled\" = '1'"));
            } else {
                $condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
            }
            $dbdata1 = array(
                'QuestCount' => $total_quest
            );
            $this->db->update('ExportHistory', $dbdata1, $condition1);
        }
        /* $webpath=$this->cfg->wwwroot.$this->cfgApp->exportDataGen.$moodle.$temp_path.".zip";
          $zipfile = $this->cfg->rootPath.$this->cfgApp->exportDataGen.$moodle."{$temp_path}.zip";
          $srczippath=$this->cfg->rootPath.$this->cfgApp->exportDataGen.$moodle.$temp_path; */
        $webpath = $temp_path_web . ".zip";
        $zipfile = $temp_path_root . ".zip";
        $srczippath = $temp_path_root;
        $auth->makeZip($srczippath, $zipfile);
        print "{$webpath}";
    }

    /**
     * remove <p>,</p>  tag in string
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  $JsonPdf
     * @return   stirng
     *
     */
    public function stripJsonData($JsonPdf) {

        $JsonPdf = str_replace("<p>", "", $JsonPdf);
        $JsonPdf = str_replace("</p>", "", $JsonPdf);
        $JsonPdf = str_replace("&lt;p&gt;", "", $JsonPdf);
        $JsonPdf = str_replace("&lt;/p&gt;", "", $JsonPdf);
        $JsonPdf = str_replace("&lt;/p&gt;", "", $JsonPdf);
        return $JsonPdf;
    }

    /**
     * generate question and answer in pdf format
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  $pdfDestFolder
     * @param    string  $strQuestionTemplate
     * @param    string  $strAnsTemplate
     * @param    string  $arrHeaderData
     * @return   void
     *
     */
    public function makeQuestionAndAnswerPdf(&$pdfDestFolder, &$strQuestionTemplate, &$strAnsTemplate, &$arrHeaderData) {

        // Question  HTML
        // Get Header Contents
        $strQuestionHeader = $this->getHtmlContents("Header.php", $arrHeaderData);
        // Footer Contents
        $strQuestionFooter = $this->getHtmlContents("Footer.php", $arrHeaderData);
        $strQuestionContent = $strQuestionHeader . $strQuestionTemplate . $strQuestionFooter;


        require_once( $this->cfg->rootPath . "/plugins/dompdf/dompdf_config.inc.php");
        spl_autoload_register('DOMPDF_autoload');

        // Make Question PDF
        Site::myDebug("------makeQuestionHTML");
        Site::myDebug($strQuestionContent);
        $strQuestionContentPdf = $this->formatDataToPdf($strQuestionContent);
        $questionFile = $pdfDestFolder . "question.pdf";
        $fQuestion = fopen($questionFile, 'w') or Site::myDebug("----- FIle Error");
        fwrite($fQuestion, $strQuestionContentPdf);
        fclose($fQuestion);

        // Maqke Question HTML
        $questionFileHtml = $pdfDestFolder . "question.html";
        $fQuestionHtml = fopen($questionFileHtml, 'w') or Site::myDebug("----- FIle Error HTML");
        fwrite($fQuestionHtml, $strQuestionContent);
        fclose($fQuestionHtml);

        // Answer HTML
        // Get Header Contents
        $strAnsHeader = $this->getHtmlContents("HeaderAns.php", $arrHeaderData);
        // Footer Contents
        $strAnsFooter = $this->getHtmlContents("FooterAns.php", $arrHeaderData);
        $strAnsContent = $strAnsHeader . $strAnsTemplate . $strAnsFooter;

        Site::myDebug("------makeAnswerHTML");
        Site::myDebug($strAnsContent);
        $strAnsContentPdf = $this->formatDataToPdf($strAnsContent);
        $answerFile = $pdfDestFolder . "answer.pdf";
        $fAns = fopen($answerFile, 'w') or Site::myDebug("----- FIle Error ANSSWER");
        fwrite($fAns, $strAnsContentPdf);
        fclose($fAns);

        // Make Answer HTML
        $answerFileHtml = $pdfDestFolder . "answer.html";
        $fAnsHtml = fopen($answerFileHtml, 'w') or Site::myDebug("----- FIle Error ANSSWER HTML");
        fwrite($fAnsHtml, $strAnsContent);
        fclose($fAnsHtml);
    }

    /**
     * show the images for correct and incorrect answer in pdf file
     *
     *
     * @access   public
     * @abstract
     * @static
     * @param    inetger  $val
     * @return   stirng
     *
     */
    public function showAnswer($val) {
        $imgUrl = '';
        if ($val) {
            $imgUrl = $this->cfg->fileroot . $this->cfgApp->exportStrGen . 'pdf/images/correct.jpg';
        } else {
            $imgUrl = $this->cfg->fileroot . $this->cfgApp->exportStrGen . 'pdf/images/incorrect.jpg';
        }
        $imgPath = "<img src='$imgUrl' />";
        return $imgPath;
    }

    /**
     * get video and images url form given path string
     *
     *
     * @access   public
     * @abstract
     * @static
     * @param    string  $filepath
     * @return   array
     *
     */
    public function getVideoAndImgUrl($filepath) {
        $getlast = strrpos($filepath, "/");
        $filename = substr($filepath, $getlast + 1);
        $filemameWithoutExt = substr($filename, 0, strrpos($filename, "."));
        $imageFileName = "thumb_" . $filemameWithoutExt . ".jpg";

        //Make Video and Image URL    EditorImagesUpload
        // $arrUrl["videoUrl"]  =  $this->cfg->wwwroot."/".$this->cfgApp->EditorImagesUpload.$filename;
        // $arrUrl["imageUrl"]  =  $this->cfg->fileroot."/".$this->cfgApp->EditorImagesUpload.$imageFileName;
        $arrUrl["videoUrl"] = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/original/', 'protocol' => 'http')) . $filename;
        // $arrUrl["videoUrl"]  =  $this->wrapText($arrUrl["videoUrl"], "80", "\r\n" );
        $arrUrl["imageUrl"] = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/videos/thumb/', 'protocol' => 'fileroot')) . $imageFileName;
        return $arrUrl;
    }

    /**
     * get images http url form given string contain image tag
     *
     *
     * @access   public
     * @abstract
     * @static
     * @param    string  $xmlData
     * @return   string
     *
     */
    public function getImgHttpUrl(&$xmlData) {

        $httpUrl = '';
        $xmlHttpPath = $this->getImageUrl($xmlData);
        Site::myDebug("------------getImgHttpUrl");
        Site::myDebug($xmlHttpPath);

        $getlast = strrpos($xmlHttpPath, "/");
        $filename = substr($xmlHttpPath, ($getlast + 1));
        $filemameWithoutExt = substr($filename, 0, strrpos($filename, "."));
        // $httpUrl  =  $this->cfg->fileroot."/".$this->cfgApp->EditorImagesUpload.$filename;
        $httpUrl = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/images/original/', 'protocol' => 'fileroot')) . $filename;
        return $httpUrl;
    }

    /**
     * get images url form given string contain image tag
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string  $objectMedia
     * @return   string
     *
     */
    function getImageUrl($objectMedia) {
        $mediaurl = "";
        $objectMedia = html_entity_decode($objectMedia);
        if (strpos($objectMedia, "<img") > -1) {
            $objectMedia = (strpos($objectMedia, "<p>") > -1) ? $objectMedia : "<p>$objectMedia</p>";
            Site::myDebug("----------------getImageUrl");
            Site::myDebug($objectMedia);
            if ($this->validateXml($objectMedia)) {
                Site::myDebug("----------------getImageUrl222222222");
                Site::myDebug($objectMedia);
                $xmlimages = new SimpleXMLElement($objectMedia);
                $mediaurl = $this->getAttribute($xmlimages->img, "src");
                Site::myDebug($mediaurl);
                unset($xmlimages);
            }
        }
        return $mediaurl;
    }

    /**
     * conversion of html data to pdf form
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  $strContent
     * @return   stirng
     *
     */
    public function formatDataToPdf(&$strContent) {
        global $_dompdf_warnings;

        $dompdf = new DOMPDF();

        //$strContent = str_replace('/>','>',$strContent);
        $search = array('<br/>', '<br />');
        $replace = array('', '');
        $strContent = str_replace($search, $replace, $strContent);
        $strContent = preg_replace("/(<img([^>]*))\/>/i", "$1 alt='' >", $strContent);
        $strContent = preg_replace("/(<hr([^>]*))\/>/i", "$1>", $strContent);
       // Site::myDebug("----Start html result");
       // Site::myDebug($strContent);
       // Site::myDebug("----End html result");
		try{
        $dompdf->load_html($strContent);
        $dompdf->render();
        $strPdfContents = $dompdf->output();
		} 
		catch(Exception$e)
		{
		  Site::myDebug($e->getMessage());
		}
       // Site::myDebug("----dompdf_warnings");
       // Site::myDebug($_dompdf_warnings);
        // unset($dompdf);
        return $strPdfContents;
    }

    /**
     * get question text,learning object,score
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string  $dataJson
     * @param    string  $contentFor
     * @return   void
     * @deprecated
     */
    function getContentForPdf(&$dataJson, $contentFor) {

        $arrDefaultText = array("question_text" => NOQUESTIONTEXT,
            "Learning Object" => NOLEARNINGOBJECTADDED,
            "Score" => NOSCOREGIVEN
        );
        $strText = '';
        if ($contentFor == "Score" || $contentFor == "Learning Object") {
            if (!empty($dataJson->metadata)) {
                foreach ($dataJson->metadata as $dataJsonMetaData) {
                    if ($dataJsonMetaData->text == $contentFor) {
                        $strText = $dataJsonMetaData->val;
                        break;
                    }
                }
            }
        } else {
            $strText = html_entity_decode($dataJson->{$contentFor});
        }
        echo ($strText ) ? $strText : $arrDefaultText[$contentFor];
    }

    /**
     * get video url form given string contain flash object
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string  $objectMedia
     * @param    string  $paramVideoExp
     * @param    string  $extractAttribute
     * @return   string
     *
     */
    public function getVideoUrl($objectMedia, $paramVideoExp, $extractAttribute = "data") {
        $mediaurl = "";
        $objectMedia = html_entity_decode($objectMedia);
        if (strpos($objectMedia, $paramVideoExp) > -1) {
            $objectMedia = (strpos($objectMedia, "<p>") > -1) ? $objectMedia : "<p>$objectMedia</p>";
            if ($this->validateXml($objectMedia)) {
                Site::myDebug("----------------getVideoUrl");
                Site::myDebug($objectMedia);
                $xmlimages = new SimpleXMLElement($objectMedia);
                Site::myDebug($xmlimages);
                if (isset($xmlimages->object)) {
                    $mediaurl = $this->getAttribute($xmlimages->object, $extractAttribute);
                } else if (isset($xmlimages->img)) {
                    $mediaurl = $this->getAttribute($xmlimages->img, $extractAttribute);
                }
                // $mediaurl = $this->getimagewebpath($mediaurl);
                Site::myDebug($mediaurl);
            }
        }
        return $mediaurl;
    }

    /**
     * decode html string
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    string  $strVal
     * @param    string  $strVal
     * @return   stirng
     * @deprecated
     */
    function getHtmlEntityDecode($strVal, $defaultText = "") {
        if ($strVal) {
            $strVal = html_entity_decode($strVal, ENT_QUOTES);
            return $strVal;
        } else {
            return $defaultText;
        }
    }

    /**
     * get html contents for header and footer in question and answer for pdf generation
     *
     *
     * @access   public
     * @abstract
     * @static
     * @param    string  $fileName
     * @param    string  $arrHeaderData
     * @return   stirng
     *
     */
    public function getHtmlContents($fileName, &$arrHeaderData) {
        $fileContents = "";
        ob_start();
        // Include Header File
        include( $this->cfg->rootPath . $this->cfgApp->exportStrGen . "pdf/templates/" . $fileName );
        $fileContents .= ob_get_contents();
        ob_end_clean();
        return $fileContents;
    }

    /**
     * get data in xml format used in export history
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    integer $quizid
     * @return   stirng
     *
     */
    function exportedQuizList($input) {
        //header('Content-type: text/xml; charset=UTF-8');
        $exportListArr = array();
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        //$start = $input['start'];
        //$stop = $input['limit'];
        $start = (isset($input['pgnstart'])) ? $input['pgnstart'] : $input['start'];
        $stop = ($input['pgnstop']) ? $input['pgnstop'] : $input['stop'];
        $limit = ($stop != "") ? "LIMIT " . $start . " , " . $stop : "";        
        $publishedlist = "";
        $userID = $this->session->getValue('userID');
        $exportType ='-1';
        $this->myDebug('ext');        
        $this->myDebug($input);
        if( isset($input['ExportType'])){
            if( trim($input['ExportType']) == 'QTI2.1,PUBLISH'){
                $exportType = " ExportType IN ( 'QTI2.1','PUBLISH') ";
            }else if( $input['ExportType'] == 'PUBLISH' ){
                $exportType = " ExportType = 'PUBLISH' ";
            }else if( $input['ExportType'] == 'QTI2.1' ){
                $exportType = " ExportType ='QTI2.1' ";
            }else{
                $exportType = " ExportType IN ('QTI2.1','PUBLISH') ";
            }               
        }else{
             $exportType = " ExportType IN ('QTI2.1','PUBLISH') ";
        }
        $exportResult = $this->db->executeStoreProcedure('ExportedQuizList', array($input['EntityID'], $input['EntityTypeID'], $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'],
            $this->session->getValue('instID'), $this->session->getValue('userID'), '-1', $exportType));

        /*
          $sqlExpAssmt = "SELECT SQL_CALC_FOUND_ROWS eps.*,usr.FirstName,usr.LastName
          FROM ExportHistory eps
          Left join Users usr on usr.ID=eps.ExportBy and usr.isEnabled = '1'
          WHERE eps.isEnabled = '1' and eps.EntityID='$EntityID' and eps.EntityTypeID='$entityTypeId'
          and eps.ExportBy = '$userID' ORDER BY eps.ExportDate Desc  $limit";

          $perm=$this->db->getRows($sqlExpAssmt);
          $perm2                   = $this->db->getRows("SELECT FOUND_ROWS() as cnt");
          $totalRec=$perm2[0]['cnt'];
         *
         */
        if ($exportResult['TC'] > 0)
        {
                foreach ($exportResult['RS'] as $export)
                {
                        $data = array();
                        $userName = $export["FirstName"] . " " . $export["LastName"];
                        if ($export["ExportType"] == "PDF") {

                                $downloadUrl = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'exports/pdf/', 'protocol' => 'http')) . $export["ID"] . ".zip";
                                $viewquestionlink = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'exports/pdf/' . $export['ID'], 'protocol' => 'http')) . "question.pdf";
                                $viewanswerlink = $this->getDataPath(array('mainDirPath' => 'persistent', 'subDirPath' => 'exports/pdf/' . $export['ID'], 'protocol' => 'http'));

                                $viewlink = "viewlink";
                        } else {
                                if ($export["ExportType"] == "QTI1.2") {
                                        //$typefolder="qti1_2/";
                                        $downloadUrl = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $export["ID"] . ".zip";
                                } elseif ($export["ExportType"] == "QTI2v1_Realize") {
                                        //$typefolder="qti1_2/";
                                        $downloadUrl = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $export["ExportPackageName"] . ".zip";
                                }elseif ($export["ExportType"] == "QTI2.1") {
                                        //$typefolder="qti1_2/";
                                        $downloadUrl = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $export["ID"] . ".zip";
                                } else {
                                        //$typefolder="moodle/";
                                        $downloadUrl = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $export["ID"] . ".zip";
                                }
                                $viewquestionlink = "0";
                                $viewanswerlink = "0";
                                //$downloadUrl    = $this->cfg->wwwroot.$this->cfgApp->exportDataGen.$typefolder."finalexport/".$perma["ID"].".zip";
                                $viewlink = "viewlink";
                        }

                        if ($this->registry->site->cfg->S3bucket) {
                                $downloadUrl = str_replace($this->registry->site->cfg->wwwroot . '/', "", $downloadUrl);
                                $downloadUrl = str_replace($this->registry->site->cfg->wwwroot . '/', "", $downloadUrl);
                                $downloadUrl = str_replace("//", "/", $downloadUrl);
                                $downloadUrl = s3uploader::getCloudFrontURL($downloadUrl);
                        }

                        $date = date('M d,Y h:i:s', strtotime($export['ExportDate']));

                        $data['name'] = $export["ExportTitle"];
                        $data['type'] = $export["ExportType"];
                        $data['username'] = $userName;
                        $data['date'] = $date;
                        $data['viewlink'] = $viewlink;
                        $data['viewquestionlink'] = $viewquestionlink;
                        $data['viewdownload'] = $downloadUrl;
                        $data['deleteid'] = $export["ID"];
                        $data['rendition'] = $export["RenditionType"];
                        $data['totalcount'] = $export["QuestCount"];
                        $data['isactive'] = $export["isEnabled"];
                        $data['entitytypeid'] = $export["EntityTypeID"];
                        $data['entityid'] = $export["EntityID"];
                        $data['id'] = $export["ID"];
                        $exportListArr[] = $data;
                }
        }
        $output = array(
                "iTotalRecords" => $exportResult['TC'],
                "iTotalDisplayRecords" => $exportResult['TC'],
                "aaData" => $exportListArr,
                "entityTypeId" => $entityTypeId
        );
        return $output;
    }

    /**
     *  rename title of exported assessment
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
    function renameExportQuiz($pubid, $publishedname) {

        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle') {
            if ($this->db->getCount("SELECT * FROM ExportHistory WHERE \"ID\"= '$pubid' and \"isEnabled\" = '1'") > 0) {
                $publishedname = addslashes($publishedname);
                $sqlUpdate = "UPDATE ExportHistory SET \"ExportTitle\" = '{$publishedname}' WHERE \"ID\"='$pubid' and \"isEnabled\" = '1' ";
                $this->db->execute($sqlUpdate);
                return MSGEXPASSMTREN;
            }
            else
                return MSGNOEXPASSMT;
        }else {
            if ($this->db->getCount("SELECT * FROM ExportHistory WHERE ID= '$pubid' and isEnabled = '1'") > 0) {
                $publishedname = addslashes($publishedname);
                $sqlUpdate = "UPDATE ExportHistory SET ExportTitle = '{$publishedname}' WHERE ID='$pubid' and isEnabled = '1' ";
                $this->db->execute($sqlUpdate);
                return MSGEXPASSMTREN;
            }
            else
                return MSGNOEXPASSMT;
        }
    }

    /**
     *  deletion of exported assessment
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    integer $pubid
     * @return   stirng
     *
     */
    function delExportQuiz($pubid) {
        global $DBCONFIG;

        $ssn = new Session();

        if ($DBCONFIG->dbType == 'Oracle') {
            $query = " SELECT * FROM ExportHistory WHERE ID = '$pubid' and \"isEnabled\" = '1' ";
        } else {
            $query = " SELECT * FROM ExportHistory WHERE ID= '$pubid' and isEnabled = '1' ";
        }
        $tempexport = $this->db->getSingleRow($query);
        if (!empty($tempexport)) {
            //decide path for publish
            if ($tempexport["ExportType"] == "QTI1.2") {// $this->cfg->rootPath.$this->cfgApp->exportStrGen.
                /* $onlineexportpath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/finalexport/".$tempexport['ID'];
                  $onlineexportzippath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/finalexport/".$tempexport['ID'].".zip"; */
                $onlineexportpath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $tempexport['ID'];
                $onlineexportzippath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $tempexport['ID'] . ".zip";
            }
            if ($tempexport["ExportType"] == "PDF") {
                /* $onlineexportpath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."pdf/finalexport/".$tempexport['ID'];
                  $onlineexportzippath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."pdf/finalexport/".$tempexport['ID'].".zip"; */
                $onlineexportpath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $tempexport['ID'];
                $onlineexportzippath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportPdf . $tempexport['ID'] . ".zip";
            }
            if ($tempexport["ExportType"] == "MOODLE") {
                /* $onlineexportpath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."moodle/finalexport/".$tempexport['ID'];
                  $onlineexportzippath = $this->cfg->rootPath.$this->cfgApp->exportDataGen."moodle/finalexport/".$tempexport['ID'].".zip"; */
                $onlineexportpath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $tempexport['ID'];
                $onlineexportzippath = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportMoodle_v_1_9_8 . $tempexport['ID'] . ".zip";
            }

            // remove published directory
            if (is_dir($onlineexportpath)) {
                $this->rmDirRecurse($onlineexportpath);
                if (is_dir($onlineexportpath))
                    @rmdir($onlineexportpath);
            }
            @unlink($onlineexportzippath);
            if ($DBCONFIG->dbType == 'Oracle') {
                $sqlUpdate = "UPDATE ExportHistory SET \"isEnabled\" = '0' WHERE ID = '$pubid' ";
            } else {
                $sqlUpdate = "UPDATE ExportHistory SET isEnabled = '0' WHERE ID = '$pubid' ";
            }

            $this->db->execute($sqlUpdate);
            return MSGDELEXPASSMT; // EXP assessment deleted
        } else {
            return MSGNOEXPASSMT; // NO susch assessment
        }
    }

    /**
     *  rename MetaDataKeyName
     *
     *
     * @access   private
     * @abstract
     * @static
     * @param    string $keyID
     * @param    string $keyName
     * @return   string
     *
     */
    function renameMetadataKeyName($keyID, $keyName) {

        Site::myDebug("------------------renameMetaDataKeyName");
        Site::myDebug("SELECT * FROM MetaDataKeys WHERE ID= '$keyID' and isEnabled = '1'");

        if ($this->db->getCount("SELECT * FROM MetaDataKeys WHERE ID= '$keyID' and isEnabled = '1'") > 0) {
            $keyName = addslashes($keyName);
            $sqlUpdate = "UPDATE MetaDataKeys SET MetaDataName = '{$keyName}' WHERE ID='$keyID' and isEnabled = '1' ";
            $this->db->execute($sqlUpdate);
            return MSGMETADATAREN;
        } else {
            return MSGNOMETADATA;
        }
    }

    /**
     *  Export course to Angel
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    aray $input
     * @return   mixed
     *
     */
    function exportAngel(array $input) {
        $localDirectory = getcwd() . '/' . $this->cfgApp->EditorImagesUpload;
        //most importent curl assues @filed as file field
        $post_array = array(
            'angel_filename' => '@' . $localDirectory . $input['image'],
            'angel_submit_field' => 'angel_field_value'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible;)');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $this->angelUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_array);
        $response = curl_exec($ch);

        echo $response;
    }

    /**
     * add and edit lms information
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param   array $input
     * @return  array
     *
     */
    function manageLmsInfo(array $input) {

        $input['lmsport'] = ($input['lmsport'] > 0) ? $input['lmsport'] : 80;
        $input['lmsssl'] = (isset($input['lmsssl'])) ? 'Y' : 'N';

        if ($input['act'] == 'del') {
            $info = array($input['lmsid'],
                $input['lmssettingtitle'],
                $input['lmsurl'],
                $input['lmsservername'],
                $input['lmsbaseurl'],
                $input['lmsport'],
                $input['lmsssl'],
                $input['lmsusername'],
                $input['lmspassword'],
                $input['lmstype'],
                "",
                $this->session->getValue('userID'),
                $this->currentDate(),
                $this->session->getValue('userID'),
                $this->currentDate(),
                'N',
                '0');
        } else {
            $url = $this->getLmsUrl($input);
            //Authenticate LMS
            $postargs = array(
                "apiaction" => "VALIDATE_ACCOUNT",
                "apiuser" => $input["lmsusername"],
                "apiPwd" => $input["lmspassword"],
                "user" => $input["lmsusername"],
                "password" => $input["lmspassword"]
            );
            $param = array(
                'url' => $url,
                'postargs' => $postargs,
                "fileupload" => "false"
            );
            $response = $this->curlExportToLms($param, "jsonobject");
            if (!isset($response->{"success"})) {
                $summary = array(
                    'error' => 'true',
                    'response' => addslashes($response->{"error"}),
                    'msg' => 'Authentication Failed'
                );
            } else {
                $postargs = array(
                    "apiaction" => "VALIDATE_APPLICATION_VERSION",
                    "apiuser" => $input["lmsusername"],
                    "apiPwd" => $input["lmspassword"]
                );
                $param = array(
                    'url' => $url,
                    'postargs' => $postargs,
                    "fileupload" => "false"
                );
                $response = $this->curlExportToLms($param, "jsonobject");
                if (!isset($response->{"success"})) {
                    $summary = array(
                        'error' => 'true',
                        'response' => addslashes($response->{"error"}),
                        'msg' => 'Failed to get Version'
                    );
                } else {
                    $info = array(
                        ($input['lmsid'] > 0) ? $input['lmsid'] : 0, // check edit or insert
                        $input['lmssettingtitle'],
                        $input['lmsurl'],
                        $input['lmsservername'],
                        $input['lmsbaseurl'],
                        $input['lmsport'],
                        $input['lmsssl'],
                        $input['lmsusername'],
                        $input['lmspassword'],
                        $input['lmstype'],
                        $response->{'success'},
                        $this->session->getValue('userID'),
                        $this->currentDate(),
                        $this->session->getValue('userID'),
                        $this->currentDate(),
                        'Y',
                        '1'
                    );
                }
            }
            // Get Version of LMS
        }


        if (isset($summary['error'])) {
            return $summary;
        } else {
            $data = $this->db->executeStoreProcedure('ManageLmsAccess', $info, 'nocount');
            $input['lmsid'] = ($input['lmsid'] > 0) ? $input['lmsid'] : $this->getValueArray($data, 'ExportID');
            return ($input['act'] == 'del') ? $input['lmsid'] : $this->getLmsInfo($input);
        }
    }

    /**
     *  get Lms information
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    aray $input
     * @return   array
     *
     */
    function getLmsInfo(array $input) {
        return $this->db->executeStoreProcedure('LmsAccessDetails', array($input['lmsid'], $this->session->getValue('userID'), $this->session->getValue('instID')));
    }

    /**
     *  export to angel
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    aray $input
     * @return   array
     *
     */
    function exportToLms(array $input) {

        //$exportlocationguid = $this->exportQuestion($input,$input['asmtid']);
        $exportlocationguid = $this->exportQuestion($input);
        //$exportlocationguid = "4c6bb9bb06b05";
        //$exportlocation = $this->cfg->rootPath.$this->cfgApp->exportDataGen."qti1_2/temp/".$exportlocationguid."/";
        $exportlocation = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_1_2 . $exportlocationguid . "/";
        rename($exportlocation . "temp.xml", $exportlocation . $exportlocationguid . ".xml");
        //export for media and xml
        $mda = new Media();
        $medialist = $mda->getMediaListFromDir($exportlocation);
        $medialist[] = "{$exportlocationguid}.xml";
        $this->myDebug("File Summary::");
        $this->myDebug($medialist);
        if (!empty($medialist)) {
            foreach ($medialist as $media) {
                $postargs = array(
                    "apiaction" => "SECTION_FILES_UPLOAD_FILE",
                    "apiuser" => $input["lmsusername"],
                    "apiPwd" => $input["lmspassword"],
                    "user" => $input["lmsusername"],
                    "section" => $input["lmscourseexport"],
                    "relpath" => "_assoc\\{$input["lmsassessmentselected"]}",
                    "filename" => $media,
                    "force" => "1",
                    "uploadfile" => "@" . $exportlocation . $media
                );
                $param = array(
                    'url' => $input["url"],
                    'postargs' => $postargs,
                    "fileupload" => "true"
                );
                $exportsummary["ExportResponse"][] = array(
                    "resp" => $this->curlExportToLms($param),
                    "file" => $media
                );
            }
        }
        //import exported content as assessment
        $postargs = array(
            "apiaction" => "QUIZ_QTI_IMPORT",
            "apiuser" => $input["lmsusername"],
            "apiPwd" => $input["lmspassword"],
            "user" => $input["lmsusername"],
            "section" => $input["lmscourseexport"],
            "parent" => $input["lmsassessmentselected"],
            "serverbase" => "%24COURSE_PATH%24",
            "basepath" => "_NULL_",
            "filename" => "_assoc%5C{$input["lmsassessmentselected"]}%5C{$exportlocationguid}.xml",
            "data" => ""
        );
        $param = array(
            'url' => $input["url"],
            'postargs' => $postargs,
            "fileupload" => "false"
        );
        $exportsummary["ApplyResponse"] = $this->curlExportToLms($param);
        $exportsummary["LMSContentUrl"] = $this->getLmsContentUrl($input);
        $this->myDebug("Export Summary::");
        $this->myDebug($exportsummary);
        return $exportsummary;
    }

    /**
     *  curl request for export to lms
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global
     * @param    aray $input
     * @param    aray $responsetype
     * @return   array
     *
     */
    function curlExportToLms($input, $responsetype = "jsonstring") {

        if ($input["fileupload"] == "true") {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_URL, $input["url"] . "?uploading=1");
            //most importent curl assues @filed as file field
            curl_setopt($ch, CURLOPT_POSTFIELDS, $input["postargs"]);
            $response = curl_exec($ch);
        } else {
            $postparam = "";
            if (!empty($input["postargs"])) {
                foreach ($input["postargs"] as $key => $val) {
                    $postparam .= "&{$key}={$val}";
                }
            }

            $param = array(
                'url' => $input["url"],
                'fields' => trim($postparam, "&"),
                "headers" => array(
                    "Accept" => "image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*",
                    "Accept-Language" => "en-us",
                    "Content-Type" => "application/x-www-form-urlencoded",
                    "Cache-Control" => "no-cache"
                )
            );
            $this->myDebug("::::::Curl Param:::::::");
            $this->myDebug($param);
            $response = $this->curlCall($param);
        }

        $this->myDebug("::::::::Response from curl::::::::");
        $this->myDebug($response);
        if ($response != "") {

            if ($responsetype == "xmlstring") {
                $resultJSON = $response;
            } else {
                $objconfigxml = simplexml_load_string($response, null, LIBXML_NOCDATA);
                if ($responsetype == "xmlobject") {
                    $resultJSON = $objconfigxml;
                } else {

                    $converter = new DataConverter();
                    $configarray = $converter->convertXmlToArray($objconfigxml->asXML());
                    $objJSON = new Services_JSON();
                    $resultJSON = $objJSON->encode($configarray);
                    if ($responsetype == "jsonstring") {
                        
                    }
                    if ($responsetype == "jsonobject") {
                        $resultJSON = $objJSON->decode($resultJSON);
                    }
                }
            }
        }
        $this->myDebug("::::::::Response {$responsetype}::::::::");
        $this->myDebug($resultJSON);
        return $resultJSON;
    }

    /**
     *  get Lms url
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
    function getLmsUrl($input) {
        if ($input["lmstype"] == "angel") {
            $url = ((isset($input["lmssecure"])) ? "https" : "http" ) . "://{$input["lmsservername"]}" . (($input["lmsport"] != "80") ? $input["lmsport"] : "") . "/api/default.asp";
        }
        return $url;
    }

    /**
     *  get Lms Content Upload url
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
    function getLmsContentUrl($input) {
        if ($input["lmstype"] == "angel") {
            $url = ((isset($input["lmssecure"])) ? "https" : "http" ) . "://{$input["lmsservername"]}" . (($input["lmsport"] != "80") ? $input["lmsport"] : "") . "/AngelUploads/Content/" . $input["lmscourseexport"] . "/_assoc/" . $input["lmsassessmentselected"] . "/";
        }
        return $url;
    }

    function updateItemAnalysisData($strDataXML) {
        Site::myDebug("----------------updateItemAnalysisData");
        // CReate Dir Item analysis
        $itemAnalysisPath = $this->cfg->rootPath . "/data/itemanalysis/";

        if (!is_dir($itemAnalysisPath)) {
            mkdir($itemAnalysisPath, 0777);
        }

        // GET question ID
        $xml = simplexml_load_string($strDataXML);
        // Site::myDebug($xml);
        Site::myDebug("-----------2222222222");
        $questionid = $this->getAttribute($xml, 'ID');

        if ($questionid > 0) {
            $fileName = $itemAnalysisPath . "questionid_" . $questionid . ".xml";
            Site::myDebug($fileName);

            $fh2 = fopen($fileName, 'w');
            if ($fh2) {
                fwrite($fh2, $strDataXML);
                fclose($fh2);
                Site::myDebug("---------FIleWritten");
                return 1;
            } else {
                Site::myDebug("---------NOTFILEWRITTEN");
                return '-1';
            }
        } else {
            return '-2';
        }
    }

    function getQuestItemAnalysis($input) {
        Site::myDebug("---------getQuestItemAnalysis");
        Site::myDebug($input);
        $xml = '';
        $itemAnalysisPath = $this->cfg->rootPath . "/data/itemanalysis/";
        $filePath = $itemAnalysisPath . "questionid_" . $input['questID'] . ".xml";
        if (file_exists($filePath)) {
            $xml = simplexml_load_file($filePath);
            Site::myDebug($xml);
        }
        return $xml;
    }

    function deleteItemAnalysis($input) {
        Site::myDebug("---------deleteItemAnalysis");
        Site::myDebug($input);

        $questID = $input['questID'];
        $itemAnalysisPath = $this->cfg->rootPath . "/data/itemanalysis/";
        $fileName = $itemAnalysisPath . "questionid_" . $questID . ".xml";
        Site::myDebug($fileName);

        if (file_exists($fileName)) {
            Site::myDebug('-------deleteItemAnalyssiTRUE');
            @unlink($fileName);
            return true;
        } else {
            Site::myDebug('-------deleteItemAnalyssiFalse');
            return false;
        }
    }

    /**
     * question exported in epub format 
     *
     *
     * @access   private
     * @abstract
     * @static
     * @global   
     * @param    array $input
     * @param    interger $asmtID
     * @return   
     *
     */
    function exportEpub(array $input) {
        global $DBCONFIG;
        $entityTypeId = $input['EntityTypeID'];
        $EntityID = $input['EntityID'];
        $action = $input['action'];
        $questids = $input['questids'];
        $questids = str_replace("||", ",", $questids);
        $questids = trim($questids, "|");
        $questNos = @count(@explode(",", $questids));
        $expTitle = $input['exportname'];
        $qst = new Question();
        if ($action == "exportq") {
            $data = array(
                'ExportTitle' => $expTitle,
                'ExportType' => $input['exporttype'],
                'EntityTypeID' => $entityTypeId,
                'EntityID' => $EntityID,
                'ExportBy' => $this->session->getValue('userID'),
                'ExportDate' => $this->currentDate(),
                'QuestCount' => $questNos,
                'isEnabled' => '1'
            );
            $guid = $this->db->insert("ExportHistory", $data);
            $path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportEpub . $guid;
            $path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportEpub . $guid;
            mkdir($path_root, 0777, true);
        }

        if ($action == "testq") {// test export of questions
            $guid = uniqid();
            $path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportEpub . $guid;
            $path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportEpub . $guid;
            mkdir($path_root, 0777, true);
        }
        if ($input['selectall'] != "true") {
            if ($DBCONFIG->dbType == 'Oracle') {
                $filter = ($entityTypeId == "-1") ? "mrq.\"QuestionID\" in ({$questids}) AND " : "mrq.\"ID\" in ({$questids}) AND ";
            } else {
                $filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
            }
        }

        if ($DBCONFIG->dbType == 'Oracle') {
            $filter .= " ( mrq.\"SectionName\" = ''''  OR   mrq.\"SectionName\" is null) ";
        } else {
            $filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
        }
        if ($DBCONFIG->dbType == 'Oracle') {
            $displayField = " mrq.\"ParentID\", mrq.\"SectionName\" , qst.\"JSONData\" , qtp.\"HTMLTemplate\" , qtp.\"RenditionMode\", qtp.\"isStatic\", tpc.\"CategoryCode\", tpc.\"CategoryName\", qst.\"XMLData\" ";
        } else {
            $displayField = " mrq.ParentID, mrq.SectionName , qst.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic, tpc.CategoryCode, tpc.CategoryName, qst.XMLData ";
        }
        $questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');

        $Assessment = new Assessment();
        if ($entityTypeId == 2) {  // for assessments
            $AssessmentSettings = $Assessment->asmtDetail($EntityID);

            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
            $entity_name = ($this->getAssociateValue($AssessmentSettings, 'AsmtName') ) ? $this->getAssociateValue($AssessmentSettings, 'AsmtName') : 0;
        } else {      //	for banks
            $DefaultSettings = $Assessment->defaultSettings();
            $DefaultSettingsID = explode(',', $DefaultSettings['ID']);
            $DefaultSettingsValue = explode(',', $DefaultSettings['DefaultValue']);

            $i = 0;
            $DefaultSettings = '';
            if (!empty($DefaultSettingsID)) {
                foreach ($DefaultSettingsID as $int) {
                    $DefaultSettings["Setting_" . $int] = $DefaultSettingsValue[$i];
                    $i++;
                }
            }
            $AssessmentSettings = $DefaultSettings;
            $qscore = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? 'yes' : 'no';
            $qhint = ($this->getAssociateValue($AssessmentSettings, 'Hint') == "1" ) ? 'yes' : 'no';
            $minutes = ($this->getAssociateValue($AssessmentSettings, 'Minutes') ) ? $this->getAssociateValue($AssessmentSettings, 'Minutes') : 0;
        }

        // Parameters initialized
        $questionCnt = 1;
        $answerCnt = 1;
        $sectionCnt = 0;
        $staticCnt = 0;
        $arrSection = array();
        $objJSONtmp = new Services_JSON();

        if (!empty($questions)) {
            //	include epub class.
            $pluginDir = __SITE_PATH . '/plugins/epub/';
            $file = $pluginDir . 'EPub.php';
            require_once($file);
            $book = new EPub();

            $qstCnt = count($questions);
            $arrHeaderData = array();
            $arrHeaderData['asmtName'] = $expTitle;
            $arrHeaderData['minutes'] = $minutes;
            $arrHeaderData['instName'] = $this->session->getValue('instName');
            $arrHeaderData['questionsCnt'] = $qstCnt;

            // Creating header and footer
            $headerTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "epub/templates/Header.php";
            $footerTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "epub/templates/Footer.php";
            $coverHeaderTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "epub/templates/HeaderCover.php";
            ob_start();
            include( $headerTemplatePath );
            $bookStart .= ob_get_clean();
            ob_start();
            include( $footerTemplatePath );
            $bookEnd .= ob_get_clean();
            ob_start();
            include( $coverHeaderTemplatePath );
            $coverStart .= ob_get_clean();

            // Title and Identifier are mandatory!
            $book->setTitle('EPUB Export');
            $book->setIdentifier($this->cfg->wwwroot, EPub::IDENTIFIER_URI); // Could also be the ISBN number, prefered for published books, or a UUID.
            $book->setLanguage("en"); // Not needed, but included for the example, Language is mandatory, but EPub defaults to "en". Use RFC3066 Language codes, such as "en", "da", "fr" etc.
            //$book->setDescription("This is a brief description\nA test ePub book as an example of building a book in PHP");
            //$book->setAuthor("John Doe Johnson", "Johnson, John Doe"); 
            $book->setPublisher("LearningMate Solutions Private Limited.", "http://www.learningmate.com/"); // I hope this is a non existant address :) 
            $book->setDate(time()); // Strictly not needed as the book date defaults to time().
            //$book->setRights("Copyright and licence information specific for the book."); // As this is generated, this _could_ contain the name or licence information of the user who purchased the book, if needed. If this is used that way, the identifier must also be made unique for the book.
            //$book->setSourceURL("http://JohnJaneDoePublications.com/books/TestBook.html");
            $book->setHeaderImage("Cover.jpg", file_get_contents($this->cfg->fileroot . $this->cfgApp->exportStrGen . 'epub/images/header.jpg'), "image/jpeg");

            $cssData = file_get_contents($pluginDir . 'css/epub.css');
            $book->addCSSFile("css/styles.css", "css1", $cssData);

            $cover = $coverStart;
            $book->addChapter("Cover", "Cover.html", $cover . $bookEnd);

            foreach ($questions as $questlist) {
                $strQuestionTemplate = '';
                $strAnsTemplate = '';
                $JsonPdf = $this->stripJsonData($questlist['JSONData']);
                $JsonPdf = $qst->removeMediaPlaceHolder($JsonPdf);
                $objJsonTemp = $objJSONtmp->decode($JsonPdf);
                //$this->myDebug("This is JSON");
                //$this->myDebug($questlist);
                if (!isset($objJsonTemp)) {
                    //Site::myDebug("---------No Object Genrated::objJsonTemp");
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($JsonPdf));
                }
                $dataJson = $objJsonTemp;

                $dataJson->templateName = $questlist['TemplateFile'];
                $questTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "epub/templates/" . $dataJson->templateName . ".php";
                $ansTemplatePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "epub/templates/" . $dataJson->templateName . "Ans.php";

                if (file_exists($questTemplatePath)) {
                    // Get Meta data for Score and Learning Object
                    if (!empty($dataJson->metadata)) {
                        foreach ($dataJson->metadata as $dataJsonMetaData) {
                            if ($dataJsonMetaData->text == "Score") {
                                if ($input['ScoreOptions'] == 1) {
                                    $dataJson->score = $score = $dataJsonMetaData->val;
                                } else if ($input['ScoreOptions'] == 2) {
                                    $dataJson->score = $score = ($input['TotalScore'] / $qstCnt);
                                }
                            } else if ($dataJsonMetaData->text == "Learning Object") {
                                $dataJson->learningObject = $learningObject = $dataJsonMetaData->val;
                            }
                        }
                    }
                    if (isset($dataJson->{'video'})) { // For Video
                        $xmlData = html_entity_decode($dataJson->{'video'});
                        if ((strpos($xmlData, "<param") > -1) || (strpos($xmlData, "<video") > -1) || (strpos($xmlData, "<img") > -1)) {
                            Site::myDebug("--------videoxmlpath");
                            Site::myDebug($xmlData);

                            if (strpos($xmlData, "<param") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<param");
                            } else if (strpos($xmlData, "<video") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<video");
                            } else if (strpos($xmlData, "<img") > -1) {
                                $filepath = $this->getVideoUrl($xmlData, "<img", "src");
                            } else if (strpos($xmlData, "<img") > -1) {
                                $filepath = $this->getImageUrl($xmlData);
                            }

                            $arrVideoAndImgUrl = $this->getVideoAndImgUrl($filepath);

                            $dataJson->imageUrl = $imageUrl = $arrVideoAndImgUrl['imageUrl'];
                            $dataJson->videoUrl = $videoUrl = $arrVideoAndImgUrl['videoUrl'];
                            unset($arrVideoandImgUrl);
                        }
                    }  // if(isset($dataJson->{'video'}))

                    if (isset($dataJson->{'image'})) {
                        // $dataJson->{'image'} = "<p><img src='http://moreshwar/quad/project/data/persistent/institutes//2/assets/images/original/media4d24670472717.jpg' title=\"{'asset_id':'707','inst_id':'2','asset_name':'media4d24670472717.jpg','asset_type':image','asset_other':''}\" class='mediaClass' /></p>  ";
                        $xmlData = html_entity_decode(urldecode($dataJson->{'image'}));
                        $dataJson->imageUrl = $this->getImgHttpUrl($xmlData);
                    }
                    Site::myDebug("------JSONDATA");

                    // INC Answer Template
                    if (file_exists($ansTemplatePath)) {
                        if (($answerCnt - 1) > 0 && ( $answerCnt - 1 ) % 2 == 0) {
                            $strAnsTemplate .= "<tr><td><DIV style='page-break-before: always;'>&nbsp;</DIV></td></tr>";
                        }
                        ob_start();
                        include( $ansTemplatePath );
                        $strAnsTemplate .= ob_get_clean();
                        $answerCnt++;
                    }

                    // INC Question Template
                    if (file_exists($questTemplatePath)) {
                        ob_start();
                        include( $questTemplatePath );
                        $strQuestionTemplate .= ob_get_clean();
                        ob_end_flush();
                        $book->addChapter("Question " . $questionCnt, "Question" . $questionCnt . ".html", $bookStart . $strQuestionTemplate . $bookEnd);
                        $questionCnt++;
                    }
                    unset($dataJson);
                } // end file exists.
            } // end foreach
        }
        $book->finalize(); // Finalize the book, and build the archive.

        $fileName = $book->saveBook($guid, $path_root);
        $webpath = $path_web . '/' . $fileName;
        print "{$webpath}";
        // print base64_encode($path_root.'/'.$fileName);
    }

    function createMedaiDirAndStorImages($x) {
        Site::myDebug('------- createMedaiDirAndStorImages== ');
        Site::myDebug($x);
    }

    /*     * *
     * This function is build to alter src for image tag
     * Function::changeImageSRC()
     * Arguments : array(
     *                      'textDocument'=>'$any_Text_Containging_Image_Tag',
     *                      'imageSRC'=>'media/',
     *                      'temp_path_root'=>$temp_path_root, 
     *                  )
     * Return: html 
     */

    function changeImageSRC($textDocumentArray = array()) {


        if (isset($textDocumentArray['textDocument']) && isset($textDocumentArray['imageSRC'])) {
            $doc = new DOMDocument();


            @$doc->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $textDocumentArray['textDocument']);

            $imageTags = $doc->getElementsByTagName('img');

            foreach ($imageTags as $tag) {
                $imgSrc = $tag->getAttribute('src');
                $imageName = basename($imgSrc);
                $tag->setAttribute('src', $textDocumentArray['imageSRC'] . $imageName);

                //Saving images on media folder 
                $imagetag = html_entity_decode('<img src="' . $imgSrc . '" />');
                list($imgpath, $imgname, $ext) = $this->getImageDetail($imagetag);
                $this->createImage($imgpath, $textDocumentArray['temp_path_root']);
                //Site::myDebug("--------Koushik---");
                //Site::myDebug($imagetag.' [-] '.$imgpath.' [-] '.$textDocumentArray['temp_path_root']);
            }
            //$body = $doc->getElementsByTagName('p')->item(0);
            //$newTextDocument = $doc->saveXML($body);
            //$innerHTML = '';
            if (!empty($doc->getElementsByTagName('p')->item(0)->childNodes)) {
                foreach ($doc->getElementsByTagName('p')->item(0)->childNodes as $child) {
                    $newTextDocument .= $doc->saveXML($child);
                }
            }

            $qst = new Question();

            $datasan = array(
                'newImageSRC' => 'images/',
                'keepImageinMedia' => true,
                'temp_path_root' => $textDocumentArray['temp_path_root']
            );
            Site::myDebug('------- datasan== ');
            Site::myDebug($datasan);



            $newTextDocument = $textQuest = $textDocumentArray['textDocument'];
            Site::myDebug('------- assetinfo_question== ');
            Site::myDebug($textDocumentArray['assetinfo_question']);
			Site::myDebug('------- textDocument1== ');
            Site::myDebug($textDocumentArray['textDocument']);
			Site::myDebug('------- temp_path_root== ');
            Site::myDebug($textDocumentArray['temp_path_root']);

            $imageSrcPrePath = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/assets/images/original/';
            Site::myDebug('$imageSrcPrePath=>' . $imageSrcPrePath);
            $searchImageSRC = "src='" . $imageSrcPrePath;
            $newImageSRC = "src='media/";
            $newTextDocument = str_replace($searchImageSRC, $newImageSRC, $newTextDocument);

            $assetData = array();
            $patterns = array();
            $patterns[0] = '/src=/';
            $patterns[1] = '/data=/';
            $patterns[2] = "/'/";
            preg_match_all('/src=([\'"])?((?(1).*?|\S+))(?(1)\1)|data=([\'"])?((?(1).*?|\S+))(?(1)\1)/i', $textDocumentArray['assetinfo_question'], $assetData);

            foreach ($assetData[0] as $key => $value) {
                $value = preg_replace($patterns, '', $value);
                $oldImageSRC = $value;
                if ($this->cfg->S3bucket) {
                    $oldImageSRC = $value;
                }
				 Site::myDebug('------- oldImageSRC== ');
				 Site::myDebug($oldImageSRC);
                $img = explode('?', $value);
                $newTextDocument = str_replace($oldImageSRC, basename($img[0]), $newTextDocument);
                $this->createImage($oldImageSRC, $textDocumentArray['temp_path_root']);
            }
        }
        return $newTextDocument;
    }

    function explode_filtered_empty($var) {
        if ($var == "")
            return(false);
        return(true);
    }

    function explode_filtered($delimiter, $str) {
        $parts = explode($delimiter, $str);
        return(array_filter($parts, "explode_filtered_empty"));
    }

    function replace_info_export($matches) {
        global $questionInput;
        $str_decode = html_entity_decode($matches[3], ENT_QUOTES, "UTF-8");
        $str_decode = str_replace(array("'", "&quot;"), "\"", $str_decode);
        //stripslashes(html_entity_decode($matches[3]));//$matches[3];//html_entity_decode($matches[3],ENT_QUOTES,"UTF-8");

        $objJSONtmp = new Services_JSON();
        $objJson2 = $objJSONtmp->decode($str_decode);

        $assetPath = $this->cfg->wwwroot . "/";
        $viewTitle = addslashes($str_decode);
        //var_dump($viewTitle);
        if ($questionInput == 'json') {
            if (strtolower($objJson2->asset_type) == 'image') {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/images/original/' . $objJson2->asset_name;

                if ($this->cfg->S3bucket) {
                    $fileUrlPath = str_replace($this->cfg->wwwroot . '/', "", $assetPath);
                    $assetPath = s3uploader::getCloudFrontURL($fileUrlPath);
                }
                if (empty($objJson2->alt_tag)) { // Added this condition for imported packages - while exporting these packages does not contain alt tag value so as of now adding it as image name 
                    $objJson2->alt_tag = $objJson2->asset_name;
                }
                return "<img alt='{$objJson2->alt_tag}' src='{$assetPath}'  />";
            } else if (strtolower($objJson2->asset_type) == 'video') {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/videos/original/' . $objJson2->asset_name;
                return '<object width=&quot;200&quot; height=&quot;200&quot; data=&quot;' . $assetPath . '&quot; type=&quot;application/x-shockwave-flash&quot; title=&quot;' . $viewTitle . '&quot;><param name=&quot;src&quot; value=&quot;' . $assetPath . '&quot; /></object>';
            } else if (strtolower($objJson2->asset_type) == 'audio') {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/audios/' . $objJson2->asset_name;
                if ($this->cfg->S3bucket) {
                    $fileUrlPath = str_replace($this->cfg->wwwroot . '/', "", $assetPath);
                    $assetPath = s3uploader::getCloudFrontURL($fileUrlPath);
                }

                // QUADPS-73 :: Add div for  size of the audio player
				// Added this condition for positioning of an Audio 
				if (empty($objJson2->alt_tag)) { 				
                return "<div style='width: 200px;'><object data='{$assetPath}'  type='audio/mp3'></object></div>";
            } else {
					$objJson2->alt_tag = strtolower($objJson2->alt_tag);
					if (($objJson2->alt_tag == 'top') || ($objJson2->alt_tag == 'bottom')){ 	
						return "<div style='width: 200px;'><object data='{$assetPath}'  type='audio/mp3'></object></div>";
					} else {
						return "<div style='padding-right: 50px; padding-left: 10px; width: 200px;float: ".$objJson2->alt_tag."'><object data='{$assetPath}'  type='audio/mp3'></object></div>";
					}
				}
				
            } else {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/' . $objJson2->asset_type . '/' . $objJson2->asset_name;
                return "<img src='{$assetPath}'  />";
            }
        } else if ($questionInput == 'xml') {
            if (strtolower($objJson2->asset_type) == 'image') {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/images/original/' . $objJson2->asset_name;
            } else if (strtolower($objJson2->asset_type) == 'video') {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/videos/original/' . $objJson2->asset_name;
            } else {
                $assetPath .= $this->cfgApp->PersistDataPath . $objJson2->inst_id . '/assets/' . $objJson2->asset_type . '/' . $objJson2->asset_name;
            }
            return "<a href='{$assetPath}' title='" . urlencode($matches[3]) . "'>view</a>";
        }
        $questionInput = "";
    }

    function removeMediaPlaceHolderExport($questionContent) {
        //echo "<pre>";print_r($questionContent);
        global $DBCONFIG;
        $questionContent = trim($questionContent, "'");
        if ($DBCONFIG->dbType == 'Oracle')
            $questionContent = str_replace("''", '"', $questionContent);
        global $questionInput;
        //$this->myDebug('inside remove media place holder');
        //print_r($questionInput);echo "<br>";
        //print_r($questionContent);
        if (preg_match("/^{/i", $questionContent)) {
            if ($DBCONFIG->dbType == 'Oracle')
                $questionContent = str_replace('\"', '"', $questionContent);
            $questionInput = 'json';
        }elseif (preg_match("/^</i", $questionContent)) {
            $questionInput = 'xml';
        }
        $this->myDebug('$questionInput');
        $this->myDebug($questionInput);
        $this->myDebug($questionContent);
        $questionContent = preg_replace_callback('/___(ASSETINFO)("?)([^}]*})("?)___/i', Array(&$this, "replace_info_export"), $questionContent);
        //Site::myDebug('calling QQRRR');
        //Site::myDebug( $questionContent);
        return $questionContent;
    }

    function getImagesFromJSONdata($quest_text, $onlyImageName = true) {
        $imagesArray = array();
        $images = array();
        preg_match_all('/src=([\'"])?((?(1).*?|\S+))(?(1)\1)/', $quest_text, $images);

        if ($onlyImageName == true) {
            // return only image name
            if (!empty($images[2])) {
                foreach ($images[2] as $key => $value) {
                    $img = explode('?', $value);
                    $imagesArray[$key] = basename($img[0]);
                }
            }
        } else {
            // return Full path
            if (!empty($images[2])) {
                $imagesArray = $images[2];
            }
        }

        return $imagesArray;
    }

    function getAssetsFromJSONdata($quest_text, $onlyImageName = true) {
        $imagesArray = array();
        $assetData = array();
        $patterns = array();
        $patterns[0] = '/src=/';
        $patterns[1] = '/data=/';
        $patterns[2] = "/'/";
        preg_match_all('/src=([\'"])?((?(1).*?|\S+))(?(1)\1)|data=([\'"])?((?(1).*?|\S+))(?(1)\1)/i', $quest_text, $assetData);

        foreach ($assetData[0] as $key => $value) {
            $value = preg_replace($patterns, '', $value);
            $img = explode('?', $value);
            $imagesArray[$key] = basename($img[0]);
        }

        return $imagesArray;
    }

    /*
     * function to get all Parent node of taxonomy
     * * PAI02 :: sprint 3 ::  QUADPS-36
     * @access   public
     * @param    $parentId
     * @return   comma seperated child Ids string  
     */

    public function getAllParentTaxonomyNode($taxonomyId) {
        if ($taxonomyId != '') {
            $taxonomyIdquery = "SELECT ParentID,Taxonomy FROM Taxonomies WHERE isEnabled=1 AND ID=" . $taxonomyId;
            $resTaxonomyIdArr = $this->db->getRows($taxonomyIdquery);
            $parentCount = count($resTaxonomyIdArr);
            if ($parentCount > 0) {
                foreach ($resTaxonomyIdArr as $key => $value) {
                    $taxonomyIdqueryCheck = "SELECT ParentID,Taxonomy FROM Taxonomies WHERE isEnabled=1 AND ID=" . $value['ParentID'];
                    $resTaxonomyIdArrCheck = $this->db->getSingleRow($taxonomyIdqueryCheck);
                    $this->idAllStr .= $value['Taxonomy'];
                    $this->idAllStr .='//';
                    if ($resTaxonomyIdArrCheck['ParentID'] != '0') {
                        $this->getAllParentTaxonomyNode($value['ParentID']);
                    }
                }
            }
        }
        $outPut = substr($this->idAllStr, 0, -2);
        $outPut = explode('//', $outPut);
        if (count($outPut) > 1) {
            $outPut = array_reverse($outPut);
            $outPut = implode("//", $outPut);
        } else {
            $outPut = substr($this->idAllStr, 0, -2);
        }
        return $outPut;
    }

    /*
     *   function to get XML formate asset
     * * PAI02 :: sprint 3 ::  QUADPS-70
     * * PAI02 :: sprint 4 ::  QUADPS-73
     *   @access   public
     *   @param    $data
     *   @return   String
     */

    public function getChoiceFormatData($data) {
	
	$allAssetArr = $this->getAssetsFromJSONdata($data);
	$assetAll = '';
	if (count($allAssetArr)) {
	    foreach ($allAssetArr as $key => $value) {
		$fileExt = end(explode('.', $value));

		if (in_array($fileExt, $this->cfgApp->videoFormats)) {
		    $assetVar = 'images/' . $value;
		} elseif (in_array($fileExt, $this->cfgApp->audioFormats)) {
		    $assetVar = 'images/' . $value;
		    // QUADPS-73 :: Add div for  size of the audio player
		} elseif (in_array($fileExt, $this->cfgApp->imgFormats)) {
		    $assetVar = 'images/' . $value;
		} else {
		    
		}
		$assetAll .= $this->getChoiceAssetsdata($data, $assetVar);
	    }
	} else {
	    $assetAll .= $data;
	}
	$output = $this->addCdataInText($assetAll);
	return $output;
    }

     /*
     *   function to get XML formate asset
     * * PAI02 :: sprint 4 ::  QUADPS-73
     *   @access   public
     *   @param    $data
     *   @return   String
     */

    function getChoiceAssetsdata($choicesText, $assetName) {
	$assetData = array();
	$patterns = array();
	$patterns[0] = '/src=/';
	$patterns[1] = '/data=/';
	$patterns[2] = "/'/";
	$patterns[3] = '/"/';
	preg_match_all('/src=([\'"])?((?(1).*?|\S+))(?(1)\1)|data=([\'"])?((?(1).*?|\S+))(?(1)\1)/i', $choicesText, $assetData);
	
	foreach ($assetData[0] as $key => $value) {
	    $value = preg_replace($patterns, '', $value);
	    $choicesText = str_replace($value, $assetName, $choicesText);
	}
	return $choicesText;
    }

    /*
     *   function to get XML formate data
     * * PAI02 :: sprint 4 ::  QUADPS-73
     *   @access   public
     *   @param    $data
     *   @return   String
     */

    public function replaceQuot($questText) {
	//preg_replace('/[^\x00-\x7F]+/', '&nbsp;', $questText);	  
	$search = array("&nbsp;", "&amp;nbsp;", "&amp;rsquo;", "&quot;", "&amp;quot;","\xA0");
	$replace = array("", "", "&rsquo;", '"', '"',"&nbsp;");
	$questText = str_replace($search, $replace, $questText);
         return $questText;
     }


   /*
    * * PAI02 :: sprint 4 ::  QUADPS-78
    * * For Author and export inline choice item type 
	*/
    function replace_blank_test($textwith_blanks, $optionsList, $isDash, $isQuestid = '') { Site::myDebug('------------777111');
	if ($isDash == 1) {
			preg_match_all("/\{\{dash[0-9]*\}\}/", $textwith_blanks, $textwith_blanks_dash_arr);
			//echo "<pre>";print_r($textwith_blanks_dash_arr);die('123456');
	    foreach ($textwith_blanks_dash_arr[0] as $key1 => $value1) {


				preg_match_all("/\[\[[0-9]*\]\]/", $textwith_blanks, $textwith_blanks_arr);
				$fibCnt = 1; //'';
		/*		 * ***************** */
				$chCount_q = 1;
		$chVal_q = 'W' . $chCount_q;

				$chCount=1;
				$chVal='G'.$chCount;
				/********************/
				foreach($textwith_blanks_arr[0] as $key=>$value)
				{		Site::myDebug('------------777');		
					if($isQuestid==''){ Site::myDebug('--------------hhhhhhhhhh');
                                         Site::myDebug($fibCnt);
					//	if($fibCnt==''){
					//		$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE" class="clozeinline-foo clozeblank-width-100px input-medium baseline" shuffle="false">';
					//	} else {
							$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'" class="clozeinline-foo clozeblank-width-100px input-medium baseline" shuffle="false">';
					//	}
					} else {
						$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'_' . $isQuestid . '" class="clozeinline-foo clozeblank-width-100px input-medium baseline" shuffle="false">';	
					}

					$option = $optionsList[$key];
		    $optionsListCorrect = $option->{'val3'};
		    $optionsListArr = explode(",", $option->{'val2'});
			$extendedTextInteraction .='<inlineChoice fixed="true" identifier="P2">Please choose...</inlineChoice>';
		    foreach ($optionsListArr as $opt) {
			if (trim($optionsListCorrect) == trim($opt)) {
			    $extendedTextInteraction .= '<inlineChoice identifier="' . $chVal . '">' . $opt . '</inlineChoice>';
							$chCount++;
			    $chVal = 'G' . $chCount;
			} else {
			    $extendedTextInteraction .= '<inlineChoice identifier="' . $chVal_q . '">' . $opt . '</inlineChoice>';
							$chCount_q++;
			    $chVal_q = 'W' . $chCount_q;
					}
		    }
					$extendedTextInteraction .= '</inlineChoiceInteraction>';
					//$extendedTextInteraction_arr[$patternCnt] = $extendedTextInteraction;

		    if ($fibCnt == '') {
			$fibCnt = 0;
				}
		    $fibCnt++;
		    $textwith_blanks = str_replace($value, $extendedTextInteraction, $textwith_blanks);
		}

		$textwith_blanks = str_replace($value1, '<span id="foo" />', $textwith_blanks);
		}
	} else if ($isDash == 2) { 
			preg_match_all("/\{\{dash[0-9]*\}\}/", $textwith_blanks, $textwith_blanks_dash_arr);
			//echo "<pre>";print_r($textwith_blanks_dash_arr);die('123456');
	    foreach ($textwith_blanks_dash_arr[0] as $key1 => $value1) {


				preg_match_all("/\[\[[0-9]*\]\]/", $textwith_blanks, $textwith_blanks_arr);
				$fibCnt = 1; //'';
		/*		 * ***************** */
				$chCount_q = 1;
		$chVal_q = 'W' . $chCount_q;

				$chCount=1;
				$chVal='G'.$chCount;
				/********************/
				foreach($textwith_blanks_arr[0] as $key=>$value)
				{				
					if($isQuestid==''){
						//if($fibCnt==''){
						//	$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE" class="clozeblock-sign clozeblank-width-60px input-medium baseline" shuffle="false">';
						//} else {
							$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'" class="clozeblock-sign clozeblank-width-60px input-medium baseline" shuffle="false">';
						//}
					} else {
						$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'_' . $isQuestid . '" class="clozeblock-sign clozeblank-width-60px input-medium baseline" shuffle="false">';	
					}

					$option = $optionsList[$key];
		    $optionsListCorrect = $option->{'val3'};
		    $optionsListArr = explode(",", $option->{'val2'});

		    foreach ($optionsListArr as $opt) {
			if (trim($optionsListCorrect) == trim($opt)) {
			    $extendedTextInteraction .= '<inlineChoice identifier="' . $chVal . '">' . $opt . '</inlineChoice>';
							$chCount++;
			    $chVal = 'G' . $chCount;
			} else {
			    $extendedTextInteraction .= '<inlineChoice identifier="' . $chVal_q . '">' . $opt . '</inlineChoice>';
							$chCount_q++;
			    $chVal_q = 'W' . $chCount_q;
					}
		    }
					$extendedTextInteraction .= '</inlineChoiceInteraction>';
					//$extendedTextInteraction_arr[$patternCnt] = $extendedTextInteraction;

		    if ($fibCnt == '') {
			$fibCnt = 0;
				}
		    $fibCnt++;
		    $textwith_blanks = str_replace($value, $extendedTextInteraction, $textwith_blanks);
		}

		$textwith_blanks = str_replace($value1, '', $textwith_blanks);
		}
	} else {
			preg_match_all("/\[\[[0-9]*\]\]/", $textwith_blanks, $textwith_blanks_arr);

			//print_r($textwith_blanks_arr); //Array([0] => Array	([0] => [[1]]	[1] => [[2]]	)	)
			//print_r($optionsList[0]->{'val2'}); //Gloucester,Lancaster,York
			//print_r($optionsList); 
			$fibCnt = 1 ;//'';
	    /*	     * ***************** */
			$chCount_q = 1;
	    $chVal_q = 'W' . $chCount_q;

			$chCount=1;
			$chVal='G'.$chCount;
			/********************/
			foreach($textwith_blanks_arr[0] as $key=>$value)
			{				
				if($isQuestid==''){
					//if($fibCnt==''){
					//	$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE" shuffle="false">';
					//} else {
						$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'" shuffle="false">';
					//}
				} else {
					$extendedTextInteraction = '<inlineChoiceInteraction responseIdentifier="RESPONSE_'.$fibCnt.'_' . $isQuestid . '" shuffle="false">';	
				}

				$option = $optionsList[$key];
		$optionsListCorrect = $option->{'val3'};
		$optionsListArr = explode(",", $option->{'val2'});

		foreach ($optionsListArr as $opt) {
		    if (trim($optionsListCorrect) == trim($opt)) {
			$extendedTextInteraction .= '<inlineChoice identifier="' . $chVal . '">' . $opt . '</inlineChoice>';
						$chCount++;
			$chVal = 'G' . $chCount;
		    } else {
			$extendedTextInteraction .= '<inlineChoice identifier="' . $chVal_q . '">' . $opt . '</inlineChoice>';
						$chCount_q++;
			$chVal_q = 'W' . $chCount_q;
				}
		}
				$extendedTextInteraction .= '</inlineChoiceInteraction>';
				//$extendedTextInteraction_arr[$patternCnt] = $extendedTextInteraction;

		if ($fibCnt == '') {
		    $fibCnt = 0;
			}
		$fibCnt++;
		$textwith_blanks = str_replace($value, $extendedTextInteraction, $textwith_blanks);
	    }
	}
		return $textwith_blanks;
		exit();
	}

    /*
     * * PAI02 :: sprint 4 ::  QUADPS-106
     * * For add  <![CDATA[test]]> in XML 
     */

    function addCdataInText($textDocument) {

	if (strpos($textDocument, 'img') !== false || (strpos($textDocument, 'object') !== false)) {

	    $regex = '/(<img*[^\>](.+)[^\/\>]\/\>|<object*[^\<\/object\>](.+)<\/object\>)/Ui';
	    if (preg_match_all($regex, $textDocument, $QuestTextNew)) {

		if ($QuestTextNew) {
		    foreach ($QuestTextNew as $val) {
			$textDocument = str_replace($val, '$$$', $textDocument);
		    }
		}
	    }
	    $newQuestionText = explode("$$$", $textDocument);
	    $p = 0;
	    $textDocument = '';
	    foreach ($newQuestionText as $val) {


		if ($val) {
		    $valnew = '<![CDATA[' . $val . ']]>';
		} else {
		    $valnew = '';
		}
		$valnew = $this->formatJson($valnew, 0, 0);
		$valnew = $valnew . '' . $QuestTextNew[0][$p] . '';
		$textDocument.=$valnew;
		$p++;
	    }
	} else {
	    $textDocument = $this->formatJson($textDocument, 1, 0);
	}
	return $textDocument;
    }
    
     /**
     * * PAI02 :: sprint 5::  QUADPS- 
     * Question exported in QTI2.1 for Realize format in zip file [On preview time.]
     * 
     *
     * @access   Public
     * @return   stirng
     *
     */
    public function exportQuestionWithQti2_1_Zip_Json($input) {
	$objJSONtmp = new Services_JSON();
	$auth = new Authoring();
	$quest = new Question();
	$imsManifestArray=array();
	$this->input = $input;
	$instID = $this->session->getValue('instID');
	$asmtid = $this->input['entityID'];
	$id = isset($this->input['QTypeID']) ? $this->input['QTypeID'] : false;

	$sql = 'SELECT DISTINCT `qt`.`TemplateFile`,`qt`.`ID`,`qtc`.`CategoryCode`  FROM `QuestionTemplates` AS `qt`
        INNER JOIN `TemplateCategories` AS `qtc` ON `qtc`.`ID`=`qt`.`TemplateCategoryID`
        WHERE  `qt`.`TemplateFile` IS NOT NULL ' . ($id ? 'AND `qt`.`ID` = ' . $id : '');
	$row = $this->db->getSingleRow($sql);

	$imsManifestArray['totalquestions']=1;
	$Exportid = uniqid();
	$questlist['ID']=$Exportid;
	$temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $instID . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
	$temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $instID . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1_Realize . $Exportid;
	mkdir($temp_path_root, 0777, true);
	mkdir($temp_path_root . "/images", 0777);

	$TemplateFile = $row['TemplateFile'];
	$sJson = $this->input["questJson"];
	$sJson = $quest->addMediaPlaceHolder($sJson);
	$sJson = $this->removeMediaPlaceHolderExport($sJson);
	
	$objJson = $objJSONtmp->decode($sJson);
	if ($objJson->{'choices'}) {
	    foreach ($objJson->{'choices'} as $choice) {
		// Alter Image src from question text 
		$choiceArray = array('textDocument' => $this->formatCdata($choice->val2, 0), 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $this->formatCdata($choice->val2, 0));
		$val2 = $this->changeImageSRC($choiceArray);		
		$choices[] = (object)array(
		    'val1' => ($choice->val1),
		    'val2' => $choice->val2,
		    'val3' => strip_tags($choice->val3, 0),
		    'val4' => $choice->val4,
		    'val5'=>'',
		);
	    }
	}
	$optionsList = $choices;
	
	$Quest_title = $this->formatJson($objJson->{'question_title'}, 0, 0);
	$Quest_Inst_text = $this->formatJson($objJson->{'introduction_text'}, 0, 0);
	$Quest_text = $objJson->{'question_text'}[0]->{'val1'};  //Question Text
	$QuesttextAltTagVal = $objJson->{'question_text'}[0]->{'val2'}; //Alt Tag

	// Alter Image src from question text 
	if (strpos($Quest_text, 'img') !== false || (strpos($Quest_text, 'object') !== false)) {
	    $textDocumentArray = array('textDocument' => $Quest_text,
					'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root,
					'temp_path_web' => $temp_path_web,
					'assetinfo_question' => $objJson->{'question_text'}[0]->{'val1'});
	    $Quest_text = $this->changeImageSRC($textDocumentArray);
	    $Quest_text = $this->addCdataInText($Quest_text);
	} else {
	    $Quest_text = $this->formatJson($Quest_text, 1, 0);
	}
	$correctFeedback = $objJson->{'correct_feedback'}[0]->{'val1'};  //Correct Feedback text
	$corrFedAltTagVal = $objJson->{'correct_feedback'}[0]->{'val2'}; //Alt Tag

	$incorrectFeedback = $objJson->{'incorrect_feedback'}[0]->{'val1'};  //Incorrect Feedback text
	$incorrFedAltTagVal = $objJson->{'incorrect_feedback'}[0]->{'val2'}; //Alt Tag
	
	// For Hint 
	//$hint = $this->formatJson($objJson->{'hint'});
	$hint = $objJson->{'hint'}[0]->{'val1'};  //Hint text
	$hintAltTagVal = $objJson->{'hint'}[0]->{'val2'}; //Alt Tag
	//For Eassy
	$essayText = $this->formatJson($objJson->{'essay'});

	//For LTD
	$imageSrc = $this->formatJson($objJson->{'image'});

	$ind_quesScore = $objJson->{'metadata'}[0]->{'val'};
	$ind_quesdifficulty = $objJson->{'metadata'}[1]->{'val'};
	//$qusetionScore = $this->qtiGetQuesScore($Entity_score_flag, $Entity_score, $totalquestions, $ind_quesScore);
	
	
	
	$templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/" . $TemplateFile . ".php";
    
	ob_start();
	if (file_exists($templateFilePath)) {
	    //commented isExport condition as we are supporting all templates for QTI2.1 
	    include($templateFilePath);

	    $xmlStr = ob_get_contents();
	    ob_end_clean();

	    /* create multiple xml files with each question */

	    $myFile = "{$temp_path_root}/QUE_{$Exportid}.xml";
	    $fh2 = fopen($myFile, 'w');
	    $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
	    fwrite($fh2, $xmlStr);
	    fclose($fh2);
	}
	
	
	$imsManifestArray[1]['question_title_identifier'] = $objJson->{'question_title'};
	$imsManifestArray[1]['question_id_identifier'] = "QUE_" . $Exportid;
	$imsManifestArray[1]['question_text'] = $objJson->{'question_text'}[0]->{'val1'};



	$questionTextImagesArray[1] = $this->getAssetsFromJSONdata($imsManifestArray[1]['question_text']);
	
	if (!empty($Quest_Inst_text)) { // For Static Page.
	    $questionTextImagesArray[1] = array_merge($questionTextImagesArray[1], $this->getAssetsFromJSONdata($Quest_Inst_text));
	}
	
	if (!empty($objJson->{'image'}) && $TemplateFile == 'LabelDiagram') { // for LabelDiagram
	    $questionTextImagesArray[1] = array_merge($questionTextImagesArray[1], $this->getAssetsFromJSONdata($objJson->{'image'}));
	}
	
	if (!empty($objJson->{'choices'}[0]->{'val4'}) && $TemplateFile == 'FIBDropDown') {
	    // for FIBDropDown
	    $questionTextImagesArray[1] = array_merge($questionTextImagesArray[1], $this->getAssetsFromJSONdata($objJson->{'choices'}[0]->{'val4'}));
	}

	if (!empty($optionsList)) {
	    foreach ($optionsList as $key => $value) {
		$questionTextImagesArray[1] = array_merge($questionTextImagesArray[1], $this->getAssetsFromJSONdata($value->val2));
	    }
	}

	/* for manifest file */
	ob_start();
	include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/ImsManifest.php");
	$xmlmaniStr = ob_get_contents();
	ob_end_clean();
	$fh2 = fopen($temp_path_root . "/imsmanifest.xml", 'w');
	fwrite($fh2, $xmlmaniStr);
	fclose($fh2);

	include($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Realize/AssesmentExamView.php");
	$xmlmaniStr = ob_get_contents();
	ob_end_clean();
	$fh2 = fopen($temp_path_root . "/assesmentExamView.xml", 'w');
	fwrite($fh2, $xmlmaniStr);
	fclose($fh2);

	/* end of creating manifest */

	$webpath = $temp_path_web . ".zip";
	$zipfile = $temp_path_root . ".zip";
	$srczippath = $temp_path_root;

	$auth->makeZip($srczippath, $zipfile);

	return $zipfile;
    }
    /** *
     *  PAI02 : sprint 5 : QUAD- : Revel Brix Export : Remove Title Tag 
     * 
     * @param type $text
     * @return type
     */
     function remove_img_titles($text) {

            // Get all title="..." tags from the html.
            $result = array();
            preg_match_all('|title="[^"]*"|U', $text, $result);
            if (empty($result[0])) {
                preg_match_all("|title='[^']*'|U", $text, $result);
            }
            // Replace all occurances with an empty string.
            foreach ($result[0] as $img_tag) {
                $text = str_replace($img_tag, '', $text);
            }
            return $text;
       }

	   function remove_img_metadata($text) {

            // Get all title="..." tags from the html.
            $result = array();
            preg_match_all('|data-metadata="[^"]*"|U', $text, $result);
            if (empty($result[0])) {
                preg_match_all("|data-metadata='[^']*'|U", $text, $result);
            }
            // Replace all occurances with an empty string.
            foreach ($result[0] as $img_tag) {
                $text = str_replace($img_tag, '', $text);
            }
            return $text;
       }

    /**
	 * question exported in QTI2.1 Fad format in zip file
	 *
	 *
	 * @access   private
	 * @abstract
	 * @static
	 * @global   $Total_Img
	 * @param    array $input
	 * @param    interger $asmtID
	 * @return   stirng
	 *
	 */
	function exportQuestionWithQti2_1_Fad(array $input) {
		$metadata = new Metadata();
		$qst = new Question();
		$objJSONtmp = new Services_JSON();
		$imsManifestArray = array();
		$metadataArray = array();
		$entityMetaArray = array();
		$entityTypeId = $input['EntityTypeID'];
		$EntityID = $input['EntityID'];
		if (!$this->registry->site->checkRight('QuestExport', $entityTypeId, $EntityID)) {
			$this->registry->site->scriptRedirect($this->cfg->wwwroot . '/index/message/');
		}

		$auth = new Authoring();
		if ($entityTypeId == 2) {
			$Assessment = new Assessment();
			$AssessmentSettings = $this->db->executeStoreProcedure('AssessmentDetails', array(
				$EntityID,
				$this->session->getValue('userID'),
				$this->session->getValue('isAdmin'),
				$this->session->getValue('instID')
			), 'nocount');
			$qshuffle = ($this->getAssociateValue($AssessmentSettings, 'ShuffleOptions') == "1" ) ? "Yes" : "No";
			$Entity_score_flag = ($this->getAssociateValue($AssessmentSettings, 'Score') == "1" ) ? "yes" : "no";
			$Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
			$Entity_name = $this->getValueArray($AssessmentSettings, "Name");
			$Entity_score = $this->getAssociateValue($AssessmentSettings, 'TotalScore');
			$settingTimer = $this->getAssociateValue($AssessmentSettings, 'Minutes'); //maxTime
			$attempts = $this->getAssociateValue($AssessmentSettings, 'Tries'); // maxAttempts 
		} else if ($entityTypeId == 1) {
			$Bank = new Bank();
			$BankSettings = $Bank->bankDetail($EntityID);
			$qshuffle = "yes";
			$Entity_score_flag = "yes";
			$Entity_score = "";
			$Entity_name = $this->getValueArray($BankSettings, "BankName");
		} else {
			$qshuffle = "yes";
			$Entity_score_flag = "yes";
			$Entity_score = "";
		}
		$ExportName = ($input['exportname'] != "") ? $input['exportname'] : "test" . $this->currentDate();
		$i = $i > 0 ? $i : 0;
		if ($input['action'] == "exportq") {
			$data = array(
				'ExportTitle' => $input['exportname'],
				'ExportType' => $input['exporttype'],
				'EntityTypeID' => $entityTypeId,
				'EntityID' => $EntityID,
				'ExportBy' => $this->session->getValue('userID'),
				'ExportDate' => $this->currentDate(),
				'QuestCount' => $i,
				'isEnabled' => '1'
			);
			$Exportid = $this->db->insert("ExportHistory", $data);
		} else {
			$guid = uniqid();
			$temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
			$temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->tempDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $guid;
			mkdir($temp_path_root, 0755, true);
			mkdir($temp_path_root . "/media", 0755);
			$qtifol = "{$temp_path_root}/temp.xml";
			$menifest_resources = "temp.xml";
		}

		if ($input['selectall'] != "true") {
			$questids = $input['questids'];
			$questids = str_replace("||", ",", $questids);
			$questids = trim($questids, "|");		
			$filter = ($entityTypeId == "-1") ? "mrq.QuestionID in ({$questids}) AND " : "mrq.ID in ({$questids}) AND ";
			$filter .= " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
			
		} else {
			$filter = " ( mrq.SectionName = ''  OR   mrq.SectionName is null) ";
			
		}	
		$displayField = " mrq.ParentID, mrq.SectionName , qst.advJSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode , qst.XMLData , qtp.isExport ";
		
		$questions = $this->db->executeStoreProcedure('DeliveryQuestionList', array("-1", "-1", "-1", "-1", $filter, $EntityID, $entityTypeId, "0", $displayField), 'nocount');
		$rootSecInc = 1;
		$sec = "";
		$total_quest = 0;
		$totalquestions = count($questions);
		$temp_path_root = $this->cfg->rootPath . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
		$temp_path_web = $this->cfg->wwwroot . "/" . $this->cfgApp->PersistDataPath . $this->session->getValue('instID') . '/' . $this->cfgApp->exportData . "/" . $this->cfgApp->exportQti_v_2_1 . $Exportid;
		mkdir($temp_path_root, 0777, true);
		

            if (!empty($questions)) {
                $i = 0;
                foreach ($questions as $questlist) {
                    $TemplateFile = $questlist['TemplateFile'];
                    $isExport = $questlist['isExport'];
                    $sJson = $questlist["advJSONData"];
                    $sJson = $qst->removeMediaPlaceHolder($sJson);
                    $objJsonTemp = $objJSONtmp->decode(stripslashes($sJson));
                    $objJson = $objJsonTemp;


                    $Quest_title = $this->formatJson($this->replaceQuot($objJson->{'question_title'}->{'text'}), 0);
                    $Quest_stem = ( $objJson->{'question_stem'}->{'text'} ) ? $objJson->{'question_stem'}->{'text'} : $objJson->{'question_text'}->{'text'};
                    
                     // Alter Image src from Image text 
                    if (strpos($Quest_stem, 'img') !== false || (strpos($Quest_stem, 'object') !== false)) {
                        $textDocumentArray = array('textDocument' => $Quest_stem, 'imageSRC' => '/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $Quest_stem);
                        $Quest_stem = $this->remove_img_titles($this->changeImageSRC($textDocumentArray));
                    } 
                    
                    $Quest_Inst_text = $this->formatJson($objJson->{'instruction_text'}->{'text'}, 0);
                    $Quest_text = $Quest_title;
                    $incorrectFeedback = $this->formatJson($objJson->{'global_incorrect_feedback'}->{'text'}, 1);
                    $correctFeedback = $this->formatJson($objJson->{'global_correct_feedback'}->{'text'}, 1);
                    $hint = $this->formatJson($objJson->{'hint'}->{'text'}, 0);
                    $notes_editor = $this->formatJson($objJson->{'notes_editor'}->{'text'}, 0);

                    //For Eassy
                    $essayText = $this->formatJson($objJson->{'essay_text'}->{'text'}, 0);
                    
                    $imageText=$this->formatJson($objJson->{'image'}->{'image'},0);
                  
                    // Alter Image src from Image text 
                    if (strpos($imageText, 'img') !== false || (strpos($imageText, 'object') !== false)) {
                        $textDocumentArray = array('textDocument' => $imageText, 'imageSRC' => '/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $objJson->{'image'}->{'image'});
                        $imageText = $this->remove_img_titles($this->changeImageSRC($textDocumentArray));
                    } else {
                        $imageText = $this->formatJson($objJson->{'image'}->{'image'}, 1, 1);
                    }
                //Score
                    $totalScore = $objJson->{'settings'}->{'score'};
                    
                    $question_type = $objJson->{'settings'}->{'question_type'};
                    
                    if($objJson->{'templatetype'}->{'text'})
                    {
                        $TemplateFile=$objJson->{'templatetype'}->{'text'};
                    }
                    
                   $templateFilePath = $this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Fad/" . $TemplateFile . ".php";


                    ob_start();
                    if (file_exists($templateFilePath)) {
                        require($templateFilePath);
                        $xmlStr = ob_get_contents();
                        ob_end_clean();

                        $this->myDebug('templateFilePath');
                        $this->myDebug($templateFilePath);
                        $this->myDebug('xmlStr');
                        $this->myDebug($xmlStr);
                        /* create multiple xml files with each question */
                        $myFile = "{$temp_path_root}/QUE_{$questlist['ID']}.xml";
                        $menifest_resources = "{$Exportid}.xml";
                         /*
                        $fh2 = fopen($myFile, 'w');
                        $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                        fwrite($fh2, $xmlStr);
                        fclose($fh2);
                         */
                        $xmlStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlStr);
                        $domxml = new DOMDocument('1.0');
                        $domxml->preserveWhiteSpace = false;
                        $domxml->formatOutput = true;
                        $domxml->loadXML($xmlStr);
                        $domxml->save($myFile);
                        
                        //Question level Metadata code starts
                        //  Get Assigned Metadata for the question
                        $metadataArray=array();
                        $metadataArray=array("Content Area"=>"Adult Health: Developmental Needs","Integrated Processes"=>"Nursing Process",
                            "Client Need"=>"Health Promotion and Maintenance","Cognitive Level"=>"Analysis [Analyzing]",
                            "Question Type"=>"","Concept"=>"Stress","Original Source"=>"Fundamentals Success, 3e (CD)",
                            "Original Chapter Number and Title"=>"Exam 01","Original Chapter Section"=>" ",
                            "Nursing Curriculum"=>"Fundamentals","Reference"=>" ","Course Topic"=>"Psychosocial and Cultural Nursing","Database Id"=>"");
                        $metadataArray['Database Id']=$this->getGUID();
                        
                        $arrInputMetadata = array("EntityID" => $questlist['ID'], "EntityTypeID" => 3);
                        $QuestAssignedMetadata = $metadata->metaDataAssignedList($arrInputMetadata, "assign");
                        if (!empty($QuestAssignedMetadata['RS'])) {
                            foreach ($QuestAssignedMetadata['RS'] as $arrMetadata) {
                                $arrMetadataValues = @explode($this->cfgApp->metaDataKeyValSeparator, $arrMetadata['KeyValues']);
                                if ($arrMetadataValues) {
                                    foreach ($arrMetadataValues as $metadataValues) {
                                        $arrValue = @explode($this->cfgApp->metaDataValSeparator, $metadataValues);
                                        if ($arrValue['4'] >= 1) {
                                            $mkeyname = $arrMetadata['KeyName'];
                                            $metadataArray[$mkeyname] = $arrValue['2'];
                                        }
                                    }
                                }
                            }
                        }
                        
                        
                         
                        if($TemplateFile=="MCSSText"){
                               $metadataArray['Question Type']="Multiple Choice"; 
                            if($question_type=="exhibit"){
                               $metadataArray['Question Type']="Exhibit"; 
                            } else if($question_type=="graphic"){
                               $metadataArray['Question Type']="Graphic";  
                            } else if($cardinality=="multiple"){
                                $metadataArray['Question Type']="Multiple Response"; 
                            }
                        } else if(in_array($TemplateFile,array("textentry","inline","dragndrop"))) {
                            $metadataArray['Question Type']="Fill in the Blank";
                        }else if($TemplateFile=="Hotspot") {
                            $metadataArray['Question Type']="Hot Spot";
                        }else if($TemplateFile=="OrderingText") {
                            $metadataArray['Question Type']="Ordered Response";
                        }
                        //Question level Metadata code ends here
                        //Question Level Taxonomy Code starts
                        $site = & $this->registry->site;

                        $entityTaxoListSql = "SELECT t.Taxonomy,t.ID,t.ParentID  FROM Classification c LEFT JOIN Taxonomies t ON t.ID=c.ClassificationID AND t.isEnabled=1 WHERE c.isEnabled=1 and c.EntityID=" . $questlist['ID'];
                        $entityTaxoList = $site->db->getRows($entityTaxoListSql);
                        $taxonomyArray = array();
                        foreach ($entityTaxoList as $taxKey => $taxValue) {
                            $this->idAllStr = '';
                            $taxonomyArray[$taxKey]['Taxonomy'] = "skill";
                            $taxonomyArray[$taxKey]['taxonomyPath'] = $this->getAllParentTaxonomyNode($taxValue['ParentID']) . '//' . $taxValue['Taxonomy'];
                        }
                        //Question Level Taxonomy code ends here

                        $imsManifestArray[$i]['question_title_identifier'] = strip_tags($Quest_text);
                        $imsManifestArray[$i]['question_id_identifier'] = "QUE_" . $questlist['ID'];
                        $imsManifestArray[$i]['question_text'] = strip_tags($Quest_text);
                        $imsManifestArray[$i]['entity_metadata_array'] = '';  // Assessment/Bank level metadata
                        $imsManifestArray[$i]['metadata_array'] = $metadataArray;           // Question level metadata
                        $imsManifestArray[$i]['entity_taxonomy_string'] = '';  //Assessment/Bank level taxonomy
                        $imsManifestArray[$i]['taxonomy_string'] = $taxonomyArray;  // Question level taxonomy
                        $i++;
                    }
                }
            }

        /* for manifest file */
		ob_start();


		require($this->cfg->rootPath . $this->cfgApp->exportStrGen . "qti2_1_Fad/ImsManifest.php");
		$xmlmaniStr = ob_get_contents();
		ob_end_clean();
		$myFileI = "{$temp_path_root}/imsmanifest.xml";
		$xmlmaniStr = preg_replace(array("/(\\t|\\r|\\n)/"), array(""), $xmlmaniStr);
		$domxml = new DOMDocument('1.0');
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;
		$domxml->loadXML($xmlmaniStr);
		$domxml->save($myFileI);


		/* end of creating manifest */

		if (!isset($input['opt'])) {
			if ($input['action'] == "exportq") {
				$condition1 = $this->db->getCondition('and', array("ID = {$Exportid}", "isEnabled = '1'"));
				$dbdata1 = array(
					'QuestCount' => $total_quest
				);
				$this->db->update('ExportHistory', $dbdata1, $condition1);
			}

			$webpath = $temp_path_web . ".zip";
			$zipfile = $temp_path_root . ".zip";
			$srczippath = $temp_path_root;
			$auth->makeZip($srczippath, $zipfile);
			return "{$webpath}";
		} else {
			return $guid;
		}
	}
        
        function getGUID(){
            if (function_exists('com_create_guid')){
                return com_create_guid();
            }else{
                mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
                $charid = strtoupper(md5(uniqid(rand(), true)));
                $hyphen = chr(45);// "-"
                $uuid = chr(123)// "{"
                    .substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12)
                    .chr(125);// "}"
                return $uuid;
            }
        }
		
	function choiceCorrectAnswer($correctText,$temp_path_root,$temp_path_web){
            $valueHTML = '';
            $keyHTML = '';
            $fibCnt = 0;
            foreach ($correctText as $text) {
                $fibCnt++;
                if ($fibCnt == 1) {
                    
//                    $correctTextNew         =   $this->formatJson(str_replace(array("<p>", "</p>"), "" ,$text->{'text'}),0); // global_correct_feedback
//                    $textDocumentArray      =   array('textDocument' => $correctTextNew, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' =>$temp_path_web, 'assetinfo_question' => str_replace(array("<p>", "</p>"), "" ,$text->{'text'}));
//                    $correctTextNew         =   $this->changeImageSRC($textDocumentArray);
//                    $correctTextNew         =   $this->remove_img_titles($correctTextNew);
//                    $correctTextNew         =   $this->remove_img_metadata($correctTextNew);
//                    $correctTextNew         =   $this->addCdataInText($correctTextNew); 
                    $correctTextNew = $this->cleanData($text->{'text'},$temp_path_root,$temp_path_web);
                    break;
                }
            }
            return $correctTextNew;
        }
        function cleanData( $inputData , $temp_path_root , $temp_path_web, $filter=1 ){
            
            $dataText           = $inputData;
			$data_text          =($filter)?$this->formatJson(str_replace(array("<p>", "</p>"), "" ,$dataText),0):$dataText;
            $textDocumentArray  = array('textDocument' => $data_text, 'imageSRC' => 'images/', 'temp_path_root' => $temp_path_root, 'temp_path_web' => $temp_path_web, 'assetinfo_question' => $inputData);
            $data_text          = $this->changeImageSRC($textDocumentArray);
            $data_text          = $this->remove_img_titles($data_text);
            $data_text          = $this->remove_img_metadata($data_text);
            $data_text          = $this->addCdataInText($data_text);
            return $data_text;
            
        }
        function getHints($hintsList,$temp_path_root , $temp_path_web){
            //$hintsList  = $objJson->{'hints'}; 
            $hintsFeedback = '';
            if (!empty($hintsList)) {
                foreach ($hintsList as $hint) {
                    $hint = $this->cleanData( $hint->{'text'}, $temp_path_root, $temp_path_web );
                    $hintsFeedback .= '<modalFeedback outcomeIdentifier="GENERAL_FEEDBACK" identifier="HINT" showHide="show">' . $hint . '</modalFeedback>';
                }
            }
            return $hintsFeedback;
        }
        
		
}





?>