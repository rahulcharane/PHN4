<?php
/**
 * This class handles all css/archive module related queries/requests
 * This class handles the business logic of listing/add/edit/delete/search/questionlist and other requests of css and archive.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */

class Css extends Site
{
    public $id;
    public $questCnt;

    /**
    * constructs a new classname instance
    */

    function __construct()
    {
        parent::Site();
        $this->id = "";
        $this->questCnt = "";
    }
        
    /**
    * Saves the css details.
    *
    *
    * @access   public
    * @param    array  $input
    * @return   int  $asmtID
    *
    */

	function saveDuplicate(array $input){
		$data	=	array();
		$refid	=	$input['cssID'];
		$query	=	"SELECT * FROM Css cs WHERE cs.ID='$refid' AND cs.isEnabled = '1'";
		
		$result	=	$this->db->getRows($query);
		if($this->db->getCount($query) > 0)
		{
			$final_file_destination = $this->getDataPath( array('mainDirPath' => 'persistent', 'subDirPath' => 'assets/css' ) );
			$filename			=	$result[0]['CssName'];
			//$new_css_filename	=	basename($filename,".css").'_'.date("Y-m-d_H-i-s").'.css';
			$new_css_filename	=	$input['CssName'];
			$ret				=	copy($final_file_destination.$filename, $final_file_destination.$new_css_filename);
			//echo "original name: ".$this->cfg->rootPath.'/data/persistent/css/'.$filename.' '."new_css_filename name: ".$this->cfg->rootPath.'/data/persistent/css/'.$new_css_filename.' '.'ret : '.$ret; die();
			
			if($ret){
				$data['pCssID']		=	0;
				$data['pCssName']	=	$new_css_filename;
				$data['pCssTitle']	=	'Duplicate '.$result[0]['CssTitle'];
				$data['pUserID']		=	$this->session->getValue('userID');
				$data['pClientID']	=	$this->session->getValue('instID');
				$data['pAddDate']	=	$this->currentDate();
				$data['pisDefault']	=	'0';
				$data['pVersion']	=	'1';
				$data['pVersionRefID']	=	'1';
				$data['pCssContent']	=	file_get_contents($final_file_destination.$filename);
				$data['prefID']		=	$refid;
				$data['pAction']	=	'duplicate';
				$css_id				=	$this->db->executeStoreProcedure('MapRepositoryCssManage', $data);
			}else{
				$css_id				=	-1;
			}
			
		}else{
			$css_id				= 0;
		}
		
		return $css_id;
	}
	
		/**
    * Get css List of an institute  
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array  	$input
    * @param    string  	$condition
    * @return   array         css List
    *
    */
	function cssList(array $input,$condition='')
    {
        global $DBCONFIG;
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $input['pgnob'] = ($input['pgnob']!="-1")?$input['pgnob']:'"isEditable" ';
        }
        else
        {     
          $input['pgnob'] = ($input['pgnob']!="-1")?$input['pgnob']:"isEditable";
        }        
        
        $input['pgnot'] = ($input['pgnot']!="-1")?$input['pgnot']:"desc";
		if(trim($this->getInput('orderBy')) == '') { $this->setInput('orderBy', 'mrc.ModDate'); }
        $condition      = ($condition != '')?$condition:'-1';
        return  $this->db->executeStoreProcedure('CssList', array($input['pgnob'], $input['pgnot'],$input['pgnstart'],$input['pgnstop'],$condition,$this->session->getValue('userID'),$this->session->getValue('isAdmin') ,$this->session->getValue('instID') , $input['pgndc'])); 
    }

    /**
    * Get css List for the searched criteria
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
    * @return   array         $data           css List
    *
    */
	
	function cssDetails($cssID){
		global $DBCONFIG;        
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Css WHERE \"ID\" = $cssID";
        }
        else
        {
            $query      = "SELECT * FROM Css WHERE ID = $cssID";
        }
       
		if($this->db->getCount($query) > 0)
        {
			$result	=	$this->db->getRows($query);
		
		}else{
			$result	=	array();
		}
	   
       return $result;
	}

	function update($inputArray	= array()){
	//print_r($inputArray['cssContent']); die();
		/* array(
				'ModDate'=> $this->currentDate(),
				);
		$this->db->update("Css",	$updated_data, "ID={$cssID}" ); */
		$cssInfo		=	$this->cssDetails($inputArray['cssID']);		
		$versionInfo	=	$this->getLatestVersionNo($inputArray['cssID']);		
		$version_no		=	$versionInfo[0]['VNumber'];
		$new_version_no	=	$version_no + 1;
		$data			=	array();
		$data['pCssID']		=	$inputArray['cssID'];
		$data['pCssName']	=	$cssInfo[0]['CssName'];
		$data['pCssTitle']	=	$cssInfo[0]['CssTitle'];
		$data['pUserID']		=	$this->session->getValue('userID');
		$data['pClientID']	=	$this->session->getValue('instID');
		$data['pAddDate']	=	$this->currentDate();
		//$data['pisEnabled']	=	1;
		$data['pisDefault']	=	$cssInfo[0]['isDefault'];
		$data['pVersion']	=	$new_version_no;
		$data['pVersionRefID']		=	$cssInfo[0]['VersionRefID'];
		$data['pCssContent']	=	$inputArray['cssContent'];
		$data['prefID']		=	$cssInfo[0]['refID'];
		$data['pAction']	=	'update';
		
	
		return  $this->db->executeStoreProcedure('MapRepositoryCssManage', $data); 
		
		
		
	}
	

	function getLatestVersionNo($cssID){
	
		$cssInfo		=	$this->cssDetails($cssID);
		$version_no		=	$cssInfo[0]['Version'];
		$VersionRefID	=	$cssInfo[0]['VersionRefID'];
		
		global $DBCONFIG;        
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT MAX(\"VERSION\") AS VNumber FROM Css WHERE \"VersionRefID\" = $VersionRefID AND \"isEnabled\" = \"1\"";
        }
        else
        {
            $query      = "SELECT MAX(VERSION) AS VNumber FROM Css WHERE VersionRefID = $VersionRefID AND isEnabled = 1";
        }
       
		if($this->db->getCount($query) > 0)
        {
			$result	=	$this->db->getRows($query);
		
		}else{
			$result	=	array();
		}
	   
       return $result;
	}
	
	function getVersionList($input){
	
		$css = $this->db->executeStoreProcedure('GetCssVersionList', array('-1', '-1', $input['start'], $input['limit'], $input['cssID'], $this->session->getValue('instID')));
		//echo "<pre>"; print_r($css);
        $versionCnt = $css['TC'];
       // $latestQuestVersion = $this->getLatestQuestVersion($input['questionid']);
      //  $css['RS'] = $this->addComment($css['RS']);

        $cnt = sizeof($css['RS']);
   
        if (!empty($css['RS']))
        {
//            include $this->cfg->rootPath.'/views/templates/'.$this->quadtemplate.'/question/VersionList.php';
            $i = 0;
            foreach ($css['RS'] as $value)
            {
                $i++;
                $itemArr = array("srno" => $value['Version'],
                    "cssid" => $value['ID'],
                    "csstitle" => $value['CssTitle'], //htmlentities(trim($value['Title']), ENT_QUOTES, "UTF-8"),
                    "cssname" => $value['CssName'], 
                    "username" => $value['userFullName'],
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
                "cssid" => "",
                "csstitle" => "",
                "username" => "",
                "date" => "");
            $jsonArr["results"][]["item"] = $itemArr;
            $jsonArr["count"] = $cnt;
            //$jsonresponse = json_encode($jsonArr);
            echo $jsonArr;
            die;
        }
	}
	
	function updateRepositoryID($cssID, $repoID)
    {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  UPDATE MapRepositoryCss SET \"EntityID\" = {$cssID} WHERE \"ID\" = $repoID ";
        }
        else
        {
            $query = "  UPDATE MapRepositoryCss SET EntityID = {$cssID} WHERE ID = $repoID ";
        }

        return $this->db->execute($query);
    }
	
	function getAssessmentList($repoID)
    {
        global $DBCONFIG;
        if ($DBCONFIG->dbType == 'Oracle')
        {
            $query = "  SELECT a.\"AssessmentName\", a.\"ID\"
FROM Assessments a, MapDeliverySettings mds
WHERE a.\"isEnabled\" = 1
AND a.\"ID\" = mds.\"EntityID\" AND mds.\"isEnabled\" = 1 AND mds.\"DeliverySettingID\" = 34
AND mds.\"SettingValue\" = '{$repoID}' ";
        }
        else
        {
            $query = "  SELECT a.AssessmentName, a.ID
FROM Assessments a, MapDeliverySettings mds
WHERE a.isEnabled = 1
AND a.ID = mds.EntityID AND mds.isEnabled = 1 AND mds.DeliverySettingID = 34
AND mds.SettingValue = '{$repoID}' ";
        }
		
		if($this->db->getCount($query) > 0)
        {
			$result	=	$this->db->getRows($query);
		
		}else{
			$result	=	array();
		}
        return $result;
    }
    
	function chkUniqueCssName($inputArray){
		global $DBCONFIG;        
		$cssInfo		=	$this->cssDetails($inputArray['cssID']);	
		$versionRefID	=	$cssInfo[0]['VersionRefID'];
		$cssName		=	$inputArray['CssName'];
		
		
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT \"ID\", \"CssName\" FROM Css c
WHERE c.\"CssName\" = '$cssName'";
        }
        else
        {
            $query      = "SELECT ID, CssName FROM Css c
WHERE c.CssName = '$cssName'";
        }
       
		if($this->db->getCount($query) > 0)
        {
			$result	=	$this->db->getRows($query);
		
		}else{
			$result	=	array();
		}
	   
       return $result;
	}
	
}
?>