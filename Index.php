<?php

class Index extends Layout
{
    public $errorMsg;
    
    /**
    * Construct new index instance
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
    
    function __construct()
    {
        parent::Site();
        $this->errorMsg = "";
    }

    /**
    * process the queryString variables and redirect to correct URL.
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
    public function processAction()
    {
        //========Global Query Values===============
        $this->queryString  = $this->collect();
        $this->page         = "";
        $this->task         = "";
        $this->recordID     = "";

        /// format acceptable: pagename/taskname/recordid/ example: bank/edit/101

        if(!empty($this->queryString[0]))
        {
             $this->page        = $this->queryString[0];
        }

        if(!empty($this->queryString[1]))
        {
             $this->task        = $this->queryString[1];
        }

        if(!empty($this->queryString[2]))
        {
             $this->recordID    = $this->queryString[2];
        }

        if(!empty($this->task))
        {
            if($this->task=="login")
            {
                $usrID    =   $this->getInput('username');
                $usrPwd   =   $this->getInput('password');
                $remPwd   =   $this->getInput('remember');

                $stat = $this->login($usrID, $usrPwd);
                if($stat)
                {
                    $url = $this->cfg->wwwroot ."/home";
                }
                else
                {
                    $url = $this->cfg->wwwroot ."/index/error/1";
                }
                //die;
                $this->scriptRedirect($url);
            }
            elseif($this->task=="logout")
            {
                $url = $this->cfg->wwwroot ."/index/";
                $this->scriptRedirect($url);
            }
            elseif($this->task=="error")
            {
                if($this->recordID==1)
                {
                    $this->errorMsg = "Invalid Login details. Please try again.";
                }
            }
        }
    }

    /**
    * gets the HTML for site logo.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   string   $retStr     HTML of site logo placement.
    *
    */
    
    public function indexSiteLogo()
    {
        $steLgo  = "";
        $steLgo .= $this->divOpen("IdxSiteLogo");
        $steLgo .= $this->divClose("IdxSiteLogo");

        $retStr ="";
        $retStr .= $this->divOpen("IdxHeader");
        $retStr .= $this->anchor("index", $steLgo,"LearningMate QuAD");
        $retStr .= $this->divClose("IdxHeader");
        return $retStr;
    }

    /**
    * gets the HTML for FlashBanner.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   string   $retStr     HTML of Flash banner placement.
    *
    */
    
    public function indexFlashBanner()
    {
        $steLgo  = "";
        $steLgo .= $this->divOpen("FlashBanner");
        $steLgo .= $this->divClose("FlashBanner");

        $retStr  ="";
        $retStr .= $this->anchor("index", $steLgo, "LearningMate QuAD");
        return $retStr;
    }

    /**
    * checks for session exist or not.
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
    
    public function checkSession()
    {
        if($this->session->getValue('exists'))
        {
            $this->redirect("/home");
        }
    }

    /**
    * gets the cookies variables if cookies are set.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   array   $cookies     list of cookies variables.
    *
    */
    
    public function getCookies()
    {
        if($this->registry->cookie->exists)
        {
             $remInfo = 1;
             $cookies = $this->registry->cookie->fetch(0); ///0 for client user | 1 for site admin
        }
        else
        {
            $remInfo = 0;
            $cookies = array("usr"=>"", "pwd"=>"" );
        }
        return $cookies;
    }

    /**
    * generate password.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   boolean   $result     list of media rows return from DB.
    * @deprecated
    */
    
    public function generatePassword()
    {
        $this->myDebug("Password generated...");
        return true;
    }

    /**
    * Check for specified quadplus user mapping exist or not.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    int   $quadPlusUserId   Quadplus userId
    * @return   array                   list of mapping details fo rspecified Quadplus userID.
    *
    */
    
    public function checkMapUserExists($quadPlusUserId)
    {
       $query = "SELECT mqqp.ID as mapID, qpi.QPName, qpi.QPClientID, qpi.QPUserID, qppc.PromoCode, mcu.UserID
                        FROM QuadPlusInfo qpi, MapQuadQuadPlus mqqp, MapClientUser mcu, QPPromoCodes qppc
                    WHERE  mcu.isEnabled = '1'
                        AND qpi.QPUserID = {$quadPlusUserId}
                        AND qpi.ID = mqqp.QuadPlusID
                        AND mqqp.QuadID = mcu.ID
                        AND mqqp.QuadPlusID =  qpi.ID
                        AND qppc.mapID = mqqp.ID
                        AND qppc.isEnabled = '1'
                        AND qpi.isEnabled = '1' ";
        return $this->db->getSingleRow($query);
    }

    /**
    * gets quadplus info for specified client Id
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    int   $clientID     Client ID
    * @return   array   $result     list of media rows return from DB.
    *
    */
    
    public function getQuadPlusClientInfo($clientID)
    {
        global $DBCONFIG;
        $data = array();
        $dataResult = $this->db->executeStoreProcedure('QuadPlusClientDetails', array($clientID, $DBCONFIG->quadliteDB), 'nocount');
        if ( ! empty($dataResult) )
        {
            $data = $dataResult['0'];
        }
        return $data;
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
    
    public function getQuadPlusUserInfo($userID)
    {
        global $DBCONFIG;
        $data       = array();
        $dataResult = $this->db->executeStoreProcedure('QuadPlusUserDetails', array($userID, $DBCONFIG->quadliteDB), 'nocount');
        if (!empty($dataResult))
        {    
            $data = $dataResult['0'];
        }
        return $data;
    }

    /**
    * check for user's verification code is correct or not.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input  list of all input values.
    * @return   mixed           .
    *
    */
    
    public function verifyUser(array $input)
    {
        global $DBCONFIG;
        $id     = base64_decode($input[0]);
        $code   = rtrim($input[1], '#');
        if($DBCONFIG->dbType=='Oracle')
        {
            $query  = " select mcu.\"ID\", mcu.\"UserID\",c.\"OrganizationName\" from MapClientUser mcu, Clients c where mcu.\"ID\" = $id and mcu.\"VerificationCode\" = '$code' and mcu.\"isEnabled\" = 1 and c.\"ID\" = mcu.\"ClientID\" ";      
        }else{
            $query  = " select mcu.ID, mcu.UserID,c.OrganizationName from MapClientUser mcu, Clients c where mcu.ID = $id and mcu.VerificationCode = '$code' and mcu.isEnabled = '1' and c.ID = mcu.ClientID ";
        }
        $result = $this->db->getSingleRow($query);

        return ($result['UserID'] > 0 )?$this->setVerificationStatus($result):INVALIDURL;
    }

    /**
    * set the verfication status as data return from DB.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $result     list of user's mapping info.
    * @return   array   $data       list of user's information.
    *
    */
    
    private function setVerificationStatus($result)
    {
        global $DBCONFIG;
        if($DBCONFIG->dbType=="Oracle"){
            $query  = " select \"UserName\", \"PrimaryEmailID\" as \"email\" from Users where \"ID\" = {$result['UserID']} and \"isEnabled\" = 1 ";
        }else{
            $query  = " select UserName, PrimaryEmailID as email from Users where ID = {$result['UserID']} and isEnabled = '1' ";
        }
        $data   = $this->db->getSingleRow($query);

        if($data['UserName'] == '' || $data['UserName'] == null)
        {
            $this->session->load('verified_userID', $result['UserID']);
        }
        else
        {
            if($DBCONFIG->dbType=="Oracle"){
                $query  = " update MapClientUser set \"isVerified\" = 'Y' where \"ID\" = {$result['ID']} ";
            }else{
                $query  = " update MapClientUser set isVerified = 'Y' where ID = {$result['ID']} ";
            }
            $this->db->execute($query);
        }
        $this->registry->instName = $result['OrganizationName'];
        return $data;
    }

    public function assignmentList($input)
    {
        global $DBCONFIG;
        $data       = array();
        $dataResult = $this->db->executeStoreProcedure('GetAssignments', array($input['userID'], $DBCONFIG->quadliteDB));
        
        if (!empty($dataResult) && is_array($dataResult['RS'][0]))
        {
            $data = $dataResult;
        }
        return $data;
    }

    public function assessmentManifest($input)
    {
        global $DBCONFIG;
        $data       = array();
        $dataResult = $this->db->executeStoreProcedure('GetAssignments', array($input['userID'], $DBCONFIG->quadliteDB));
        
        if (!empty($dataResult) && is_array($dataResult['RS'][0]))
        {  
                // $data = $dataResult;                
                $dataResultXML =  "<manifest identifier='_298b3784-d875-4407-87de-70783fa72629' xmlns='http://www.imsglobal.org/xsd/imscc/imscp_v1p1'  xmlns:lomimscc='http://ltsc.ieee.org/xsd/imscc/LOM' xmlns:lom='http://ltsc.ieee.org/xsd/LOM'    xmlns:cc='http://www.imsglobal.org/xsd/imsccauth_v1p0'  xmlns:voc='http://ltsc.ieee.org/xsd/LOM/vocab'  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 	xsi:schemaLocation='http://ltsc.ieee.org/xsd/LOM/vocab http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_2/vocab/loose.xsd http://www.imsglobal.org/xsd/imscc/imscp_v1p1 http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/imscp_v1p2_localised.xsd http://ltsc.ieee.org/xsd/LOM http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_2/lomLoose_localised.xsd http://ltsc.ieee.org/xsd/imscc/LOM http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_1/lomLoose_localised.xsd http://www.imsglobal.org/xsd/imsccauth_v1p0 http://www.imsglobal.org/profile/cc/ccv1p0/derived_schema/domainProfile_0/imsccauth_v1p0_localised.xsd'>";
                $dataResultXML .= "<metadata>  
                                    <schema>IMS Common Cartridge</schema>
                                    <schemaversion>1.0.0</schemaversion>
                                    <lomimscc:lom>
                                        <lomimscc:general>
                                                    <lomimscc:title>
                                                            <lomimscc:string language='en-US'>Root</lomimscc:string>
                                                    </lomimscc:title>
                                                    <lomimscc:description>
                                                            <lomimscc:string language='en-US'>Root</lomimscc:string>
                                                    </lomimscc:description>
                                                    <lomimscc:keyword>
                                                            <lomimscc:string language='en-US'>Root</lomimscc:string>
                                                    </lomimscc:keyword>
                                        </lomimscc:general>
                                   </lomimscc:lom>
                                 </metadata>";
                $dataResultXML .= "<organizations>
                                        <organization identifier='ORG1' structure='rooted-hierarchy'>
                                        <title>Root</title>";                
                
                
                foreach ( $dataResult['RS'] as $recData   )
                {
                    $assignmentStatus = ( $recData['AttemptStatus'] == 'NotAttempted' ) ? 'False'  : 'True';
                    
                    $dataResultXML .= "<item identifier='{$recData['AssignmentID']}' identifierref='{$recData['AssignmentID']}_R'>
                                            <title>{$recData['AsgmtName']}</title>
                                            <lom:metadata>
                                            <key>HasAttempted</key>
                                            <value>{$assignmentStatus}</value>
                                            </lom:metadata>
                                        </item>";
                }
                $dataResultXML .= "</organization>
                                   </organizations>
                                   <resources>";                
                foreach ( $dataResult['RS'] as $recData )
                {
                    $dataResultXML .= "<resource identifier='{$recData['AssignmentID']}_R' type='imsqti_xmlv1p2/imscc_xmlv1p0/assessment' >
                                            <file href='{$recData['AssignmentID']}\assessment.xml' />
                                       </resource>";
                }
                $dataResultXML .= " </resources>
                                    </manifest>";
        }
        Site::myDebug( $dataResultXML );
        return $dataResultXML;
    }

    

    public function getMobilePublishDetails($assignmentID)
    {        
        $query  = " select mpa.QuadClientID, mpa.PublishID from MapPublishAssignments mpa where mpa.AssignmentID in($assignmentID) and mpa.isEnabled = '1' ";
        $result = $this->db->getRows($query);

        return ($result[0]['QuadClientID'] > 0 )?$result:false;
    }

    public function updateGradebook($input)
    {
        $query  = " update UserReports set AttemptStatus = 'Completed' where AssignmentID = {$input['assignmentID']} and UserID = {$input['userID']}";
        $this->db->execute($query);
        return $this->db->getAffectedRows();
    }

    public function addGradebookDetails(array $input)
    {
        $dataArray  = array($input['userID'], $input['accessToken'], $input['assignmentID'], $input['totalScore'], $input['userScore'], $input['timeTaken'], $this->formattedDate($input['startDate']), $this->formattedDate($input['finishDate']), $input['totalQuestions'], $input['skippedQuestions'], $input['attemptedQuestions'], $input['incorrectAnswers'], $input['correctAnswers'], $input['partialCorrectAnswers'], $input['comment']);

        $gbDetails  = $this->db->executeStoreProcedure('AddGradebook',$dataArray,"nocount");
        return $this->getValueArray($gbDetails, 'reportID');
    }

    public function getGradebook($input)
    {
        $data = array();
        $dataResult = $this->db->executeStoreProcedure('GetGradebookDetails', array($input['assignmentID']));

        if (!empty($dataResult) && is_array($dataResult['RS'][0]))
        {
            $data = $dataResult;
        }
        return $data;
    }

    public function hesiLogin($input)
    {
        $response   = $this->hesiAuthenticate($input);
        $xml        = @simplexml_load_string($response);

        if(is_object($xml))
        {
            if($xml->code == 200)
            {
                $role = $xml->data;
                $rec    = $this->db->executeStoreProcedure('Login', array("{$input['username']}","{$input['password']}"));
                $userID = $this->getValueArray($rec['RS'],'ID');
                $this->loadUserDetails($usrName, $usrPwd, $remember, $userID);
                $this->session->load('isHesiPoc', 1);
                //return json_encode(array('status' => 'success', 'data' => (string)$xml->Roles->Role));
            }
            else
            {
                //return json_encode(array('status' => 'failure', 'data' => (string)$xml->msg[0]));
            }
        }
        else
        {
            //return json_encode(array('status' => 'failure', 'data' => 'There was some problem while authenticating'));
        }
        return $response;
    }

    private function hesiAuthenticate($input)
    {
        $param = array();
        $param['url']       = $this->cfg->hesiAuthUrl;
        $param['fields']    = "username={$input['username']}&password={$input['password']}";
        return $this->curlCall($param);
    }

    public function getHesiLoginResponse($input)
    {
        $response   = $this->hesiAuthenticate($input);
        header('Content-type:text/xml');
        return $response;
    }

    private function hesiPublishItem($input)
    {
        $param = array();
        $param['url']       = $this->cfg->hesiPublishUrl;
        //$param['fields']    = "username={$input['username']}&password={$input['password']}";
        return $this->curlCall($param);
    }

    public function hesiPublish($input)
    {
        $response   = $this->hesiPublishItem($input);
        header('Content-type:text/xml');
        return $response;
    }
	
	/**
    * assign the verbose info in not loggedin state.
    *
    *
    * @access   public        
    */
	public function verboseInfo()
    {
        $query  = " select * from VerboseInfo vbinf where  vbinf.isEnabled = '1' and vbinf.CategoryID = '7' ";
        $result = $this->db->getRows($query);
        return $result;
    }

    
    
}
?>