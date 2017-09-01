<?php
/**
 * This class handles all user/admin module related queries/requests
 * This class handles the business logic of listing/add/edit/delete/search and other requests of users/admin/roles/institution codes.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */

class User extends Site
{
    public $search          = '';
    public $isPageActive    = false;
    public $clients         = array();
    public $clientInfo      = array();
    public $existingUsers   = array();
    public $otherInstExistingUsers   = array();

  /**
    * constructs a new user instance
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   void
    */

    function __construct()
    {
        set_time_limit(0);
        parent::Site();
    }

  /**
    * gets the preferences list of the current user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   array
    *
    */

    public function settingsInfo()
    {
        return $this->db->executeStoreProcedure('PreferencesList', array($this->session->getValue('userID')),'nocount');
    }

  /**
    * gets the users list of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function usersList(array $input,$condition='')
    {
        global $DBCONFIG;
        $userName = ($input['username'] == '-1')?'':$this->session->getValue('userName');
        $input['pgnot'] = ($input['pgnot']!="-1")?$input['pgnot']:"desc";
		if(trim($this->getInput('orderBy')) == '') { $this->setInput('orderBy', 'mcu.ModDate'); }
        $condition      = ($condition != '')?$condition:'-1';
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            //$input['pgnob'] = '"Count"';
            return $this->db->executeStoreProcedure('UsersList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'),$userName , '-1',$condition));
        }
        else
        {
            return $this->db->executeStoreProcedure('UsersList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'),$userName , 'ur.isAdmin,u.isActive,mcu.isVerified',$condition));
        }
    }

  /**
    * gets the roles list of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function roleList(array $input, $search='')
    {
        global  $DBCONFIG;
        if($input['rolelisttype']=='all')
        {
           $input['pgnob']=$input['pgnot']  = $input['pgnstart']=$input['pgnstop'] ='-1';
        }
        //$userID = ($this->session->getValue('isAdmin') == 1) ? 0 : $this->session->getValue('userID'); // for right
        $userID = 0;
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
           $input['search'] = strtolower($input['search']);
            $search = (trim($input['search']) != '')?" LOWER(ur.\"RoleName\") LIKE ''%{$input['search']}%'' ":'-1';
           // $input['pgnob'] = '"Count"';
            return $this->db->executeStoreProcedure('RolesList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'), $userID, $input['pgndc'],$search));
        }
        else
        {
            $search = ($search != '')?$search:'-1';
            $data =  $this->db->executeStoreProcedure('RolesList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'), $userID, $input['pgndc'],$search));
            
            return $data;
        }
    }

  /**
    * gets the default role of the user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function getDefaultUserRole(array $input)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $search = " ur.\"isDefault\" = ''Y'' ";
        }
        else
        {
            $search = " ur.isDefault = 'Y' ";
        }
        
        if($input['rolelisttype']=='all')
        {
           $input['pgnob']=$input['pgnot']=$input['pgnstart']=$input['pgnstop'] ='-1';
        }
        $userID = ($this->session->getValue('isAdmin') == 1)?0:$this->session->getValue('userID');
        return $this->db->executeStoreProcedure('RolesList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'), $userID, $input['pgndc'],$search));
    }

  /**
    * gets the inactive users list of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function inactiveUsersList(array $input,$condition='')
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $input['pgndc'] = ' mcu.ID as "mapID", mcu."VerificationCode" as "code" ';
        }
        else
        {
            $input['pgndc'] = ' mcu.ID as mapID, mcu.VerificationCode as code ';
        }
        $condition      = ($condition != '')?$condition:'-1';
        return $this->db->executeStoreProcedure('InactiveUsersList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'), $input['pgndc'], $condition));
    }

  /**
    * adds new user gtom admin panel
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   integer
    *
    */

    public function save(array $input)
    { 
        if($input['Password'] != ''){
            $input['Password'] = Site::getSecureString($input['Password']);
        }
        
        if($input['SecurityAnswer'] != '' && !(Site::isValidSecureString($input['SecurityAnswer']))){
            //$input['SecurityAnswer'] = Site::getSecureString($input['SecurityAnswer']);
            $input['SecurityAnswer'] = $input['SecurityAnswer'];
        }

        if($input['EmailVerifyType'] == 0 && $input['UserName'] != ""){
            if($this->verifyUserName($input['UserName']) )
            {
                return USERNAMEFAIL;
            }
        }
        $isUserExists = false;

        if($this->verifyEmail($input['PrimaryEmailID']))
        {
            $isUserExists   = true;
            $userInfo       = $this->getInstInfoByEmail($input['PrimaryEmailID']);
            $userID         = $userInfo[0]['userID'];
            $clients        = $this->getValueArray($userInfo,'instID', 'multiple', 'array');
            $clients        = array_keys($clients);
            $clients        = $this->removeBlankElements($clients);

            if(in_array($this->session->getValue('instID'),$clients))
            {
                return ALREADYINSTMEMBER;
            }
            elseif(empty($userInfo))
            {
                $details    = $this->getUserInfoByEmail($input['PrimaryEmailID']);
                $userID     = $details['ID'];
            }

            $isVerified     = $this->getValueArray($userInfo,'isVerified', 'multiple', 'array');
            $isVerified     = array_keys($isVerified);
            $isVerified     = $this->removeBlankElements($isVerified);
            return EMAILFAIL;
        }

        if($isUserExists === false)
        {  
            $userDetails    = $this->getUserArr($input,0);
            $sql = 'insert into Users(UserName, Password, PrimaryEmailID) values(?, ?, ?)';
            if($input['EmailVerifyType'] == 1){
                $password = $this->randomPassword();
                $userDetails[7] = md5($password);
                $this->registry->password = $password;
            }
            $paramArr = Array($userDetails[8], $userDetails[7], $userDetails[8]);
            $user_id = $this->db->userAddExecutePrepareStatement($sql, $paramArr);
            
            //$sql = "UPDATE Users SET isActive='0' WHERE ID=" . $user_id;
            //$this->db->execute($sql);

            $userDetails[0] = $user_id;
            $lastIndex = count($userDetails)-1;
            $userDetails[$lastIndex] = $user_id;
            
            $userDetails    = $this->db->executeStoreProcedure('UserManage', $userDetails,'nocount');
            site::myDebug("USer Details");
            site::myDebug($userDetails);
            
            $updated_data = array(
                'FirstName' => $input['UserFirstName'],
                'LastName' => $input['UserLastName'],
                'isActive' => '0',
                'ModDate' => $this->currentDate(),
                'LanguageID'=>$this->session->getValue('userLanguage')
            );
            
            $query = "UPDATE VerboseLanguage SET UsageCount = (UsageCount+1) WHERE ID = ".$this->session->getValue('userLanguage');
            $this->db->execute($query);
            
            //echo "<pre>";print_r($updated_data);
            $where = " ID='" . $user_id . "' AND isEnabled='1'";
            //echo "<pre>";echo $where;die;
            $status = $this->db->update("Users",$updated_data,$where);
            $userID         = $this->getValueArray($userDetails, 'UserID');
            site::myDebug('$userID');
            site::myDebug($userID);
        }
        /*else if($isUserExists === true)
        {
            //$status         = (in_array('Y',$isVerified)?'Y':'N');
            $status         = 'N';
            $userDetails    = array(0,$this->session->getValue('instID'),$userID,$this->currentDate(),$this->session->getValue('userID'),$this->currentDate(),'Y','1','Y',$status);
        }*/
        $Everifyverify =($input['EmailVerifyType']== 1) ? "N":"Y";
        $userDetails    = array(0,$this->session->getValue('instID'),$userID,$this->currentDate(),$this->session->getValue('userID'),$this->currentDate(),'Y','1','Y',$Everifyverify);

        $user           = $this->db->executeStoreProcedure('MapClientUserManage', $userDetails,'nocount');
        site::myDebug("USer Details12312");
		site::myDebug($user);
        //$user = $user['RS'];
        $mapID          = $verification['id']   = $this->getValueArray($user, 'mapID');
        $code           = $verification['code'] = $this->getValueArray($user, 'verificationCode');
	$userID         = $this->getValueArray($user, 'UserID') ? $this->getValueArray($user, 'UserID') : $userID;
		site::myDebug("USer ID 1111");
		site::myDebug($userID);
        $array2         = array(0,$mapID,$input['RoleID'],$this->currentDate(),$this->session->getValue('instID'),'1');
        $this->db->executeStoreProcedure('MapUserRoleManage', $array2,'nocount');

        site::myDebug('$verification');
        site::myDebug($verification);

        if($verification['code'] != '' && $verification['id'] != '')
        $this->registry->verificationurl    = $this->userVerificationUrl($verification);
        else
        $this->registry->verificationurl    = '';
		site::myDebug("USer ID 22222");
		site::myDebug($userID);
        return $userID;
    }

  /**
    * verifies whether the password already exists
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $password
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyPassword($password,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';

        if($password != ''){
            $password = Site::getSecureString($password);
        }

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Users  WHERE \"Password\"='{$password}' $condition ";
        }
        else
        {
            $query      = "SELECT * FROM Users  WHERE Password='{$password}' $condition ";
        }
        
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies whether the username already exists
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $userName
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyUserName($userName,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Users WHERE \"UserName\"='$userName' $condition and isEnabled=1";			
        }
        else
        {
           $query      = "SELECT * FROM Users WHERE UserName='$userName' $condition and isEnabled=1";			
        }
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies whether the user email already exists
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyEmail($email,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Users WHERE \"PrimaryEmailID\"='$email' $condition and isEnabled=1";
        }
        else
        {
            $query      = "SELECT * FROM Users WHERE PrimaryEmailID ='$email' $condition and isEnabled=1";
        }
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * gets the user information by email
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @param    string  $condition
    * @return   array
    *
    */

    public function getUserInfoByEmail($email,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Users WHERE \"PrimaryEmailID\" ='$email' $condition ";
        }
        else
        {
            $query      = "SELECT * FROM Users WHERE PrimaryEmailID='$email' $condition ";
        }
        return $this->db->getSingleRow($query);
    }

  /**
    * verifies whether the institution email already exists
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyOfficialEmail($email)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Clients WHERE \"OfficialEmailID\"='$email' AND \"isEnabled\"= 1 ";
        }
        else
        {
            $query      = "SELECT * FROM Clients WHERE OfficialEmailID='$email' AND isEnabled= 1 ";
        }
        /*$this->db->select('Clients','*',
                array("OfficialEmailID" => $email,
                    "isEnabled" => "1"
            ));*/
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies whether the institution code exists
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $promocode
    * @return   boolean
    *
    */

    function isPromoCodeExist($promocode)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = " SELECT * FROM PromoCodes WHERE \"PromoCode\" = '$promocode' ";
        }
        else
        {
            $query      = " SELECT * FROM PromoCodes WHERE PromoCode = '$promocode' ";
        }
        
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * gets the user information by userid
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @param    string  $isEnable   options(Y/N)
    * @param    string  $resultType options(list/nocount/details/object)
    * @return   array
    *
    */

    public function userDetails($userID,$isEnable='1',$resultType = 'nocount')
    {

        $userDetails = (array)$this->db->executeStoreProcedure('UserDetails', array($userID,$isEnable, $this->session->getValue('instID') ),$resultType);
        $this->setCache($cacheCode,$userDetails);
        return $userDetails;

    }
    
    public function getDefaultLanguage($languageID)
    {

        $query = "Select * from VerboseLanguage WHERE ID=".$languageID;
        return $this->db->getSingleRow($query);

    }

/**
    * updates the role of the users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $userID
    * @param    integer $roleID
    * @return   boolean
    *
    */

    public function updateUserRole($userID,$roleID)
    {
        global $DBCONFIG;
        Site::myDebug('----------updateUserRole');
        Site::myDebug($userID);
        
        if(intval($roleID) > 0 ) // intval($roleID) > 0 && $this->session->getValue('isAdmin') // for right
        {
            //though user is Admin, check for his Role Edit right.
            $rights = unserialize($this->session->getValue('rights'));

            if($rights['RoleEdit'] == 'Y')
            {
                // No user can change his own role.
                if($this->session->getValue('userID') != $userID)
                {
                    //Only default admin can assign default admin role to another users.
                    if(($this->getAdminRoleID($this->session->getValue('instID')) != $roleID ) || ($this->session->getValue('isDefault') == 'Y'))
                    {
                        //$query  = "select ID from MapClientUser where UserID IN ($userID) AND ClientID = {$this->session->getValue('instID')} AND isEnabled ='1' ";
                        //change in query for oracle
                        if ( $DBCONFIG->dbType == 'Oracle' )
                        {
                            $query  = "select ID from MapClientUser where \"UserID\" IN ($userID) AND \"ClientID\" = {$this->session->getValue('instID')} AND \"isEnabled\" ='1' ";
                        }
                        else
                        {
                            $query  = "select ID from MapClientUser where UserID IN ($userID) AND ClientID = {$this->session->getValue('instID')} AND isEnabled ='1' ";
                        }
                        
                        $data   = $this->db->getRows($query);
                        $mapClientUserID = $this->getValueArray($data,'ID','multiple');

                        if(trim($userID) != '')
                        {
                            //  $query  = "UPDATE MapUserRole SET RoleID = $roleID WHERE MapClientUserID IN ($userID) ";
                            if ( $DBCONFIG->dbType == 'Oracle' )
                            {
                                $query  = "UPDATE MapUserRole SET \"RoleID\" = $roleID WHERE \"MapClientUserID\" IN ($mapClientUserID) ";
                                // Store Procedure to Update Role Count
                                $updateUserRole = $this->db->executeStoreProcedure('UpdateUserRoleCount', array('ROLECHANGE', $this->session->getValue('instID'), $userID, $roleID ) );
                            }
                            else
                            {
                                $query  = "UPDATE MapUserRole SET RoleID = $roleID WHERE MapClientUserID IN ($mapClientUserID) ";
                            }                             
                            return $this->db->execute($query);
                        }
                    }
                }
            }
        }
    }

  /**
    * updates the user details
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    public function update(array $input)
    {
        global $DBCONFIG;
        $query = "SELECT LanguageID AS prevLang FROM Users WHERE ID = ".$input['UserID'];
        $prevLang = $this->db->getSingleRow($query);
        
        $query2 = "SELECT UsageCount FROM VerboseLanguage WHERE ID = ".$input['LanguageID'];
        $currentCount = $this->db->getSingleRow($query2);
        
        if($input['LanguageID']!=$prevLang['prevLang']){
            $query = "UPDATE VerboseLanguage SET UsageCount = (UsageCount+1) WHERE ID = ".$input['LanguageID'];
            $this->db->execute($query);
            if($currentCount>0){
                $query = "UPDATE VerboseLanguage SET UsageCount = (UsageCount-1) WHERE ID = ".$prevLang['prevLang'];
                $this->db->execute($query);
            }
        }
        if(trim($input['curPassword']) != '')
        {
            if(!$this->verifyPassword($input['curPassword'], " ID = {$input['UserID']} "))
            {
                return WRONGOLDPASS;
            }

            if($this->verifyPassword($input['Password'], " ID = {$input['UserID']} "))
            {
                return WRONGNEWPASS;
            }
            else
            {
                $input['Password'] = $input['Password'];
            }
        }
        else
        {
            $input['Password'] = '-1';
        }

        if($this->verifyEmail($input['PrimaryEmailID'], " ID != {$input['UserID']} "))
        {
            return EMAILEXISTS;
        }

        // Delete All Admin Role related cached pages, so that Multiple Quad Plus is avilable as per new settings to all the admin
        $this->deleteAdminRoleCachedPages($this->session->getValue('instID'));
        
        if($input['SecurityQuestion'] == '' && $input['SecurityAnswer'] == '' ){
            $userDetails = $this->getUserArr($input,$input['UserID']);
            $userUpdates =  $this->db->executeStoreProcedure('UserManage', $userDetails,'nocount');
            $this->registry->site->session->load('userLanguage', $input['LanguageID']);
        }

        if($input['Password'] != '' & $input['Password'] != '-1'){
            $input['Password'] = Site::getSecureString($input['Password']);
        }

        if($input['SecurityAnswer'] != '' && !(Site::isValidSecureString($input['SecurityAnswer']))){
            //$input['SecurityAnswer'] = Site::getSecureString($input['SecurityAnswer']);
            $input['SecurityAnswer'] = $input['SecurityAnswer'];
        }else if(!isset($input['SecurityQuestion']) && !isset($input['SecurityAnswer'])){
            $input['SecurityQuestion']="-1";
            $input['SecurityAnswer']="-1";
        }
            
      
        if( $input['Password'] =='-1' && $input['SecurityQuestion'] == '-1' && $input['SecurityAnswer'] == '-1' ){
            // Nothing else
        }else{
            $userDetails = array($input['UserID'],$input['Password'],$input['SecurityQuestion'],$input['SecurityAnswer'],$DBCONFIG->quadliteDB);      
            $this->db->storeProcedureManage('ManagePassword', $userDetails);
        }
        $this->mydebug('ManagePassword');
        $this->mydebug($input);
        $this->updateUserRole($input['UserID'], $input['RoleID']);
        $this->updateUserPref($input);
        $this->updateClientPref($input);        


        if($this->session->getValue('isDefault') == 'Y')
        {
            if(!$this->checkUrlAvaibility($input['instituteurl']))
            {
                $this->updateClientUrl($input['instituteurl'],$this->session->getValue('instID'));
                $this->session->load('institutelogo',$input['instituteurl']);
            }
        }

        return true;
    }
    
    /**
    * updates active/deactive status of the given users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $userID
    * @return   boolean
    *
    */

    public function updateStatus($userID,$isActive)
    {
        global $DBCONFIG;
        $userID = implode(',',(array)$this->removeBlankElements($userID));//echo $userID;
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = "  UPDATE Users SET \"isActive\" = {$isActive}, \"ModBY\" = {$this->session->getValue('userID')} WHERE \"ID\" IN ($userID) ";            
        }
        else
        {
            $query  = "  UPDATE Users SET isActive = {$isActive}, ModBY={$this->session->getValue('userID')} WHERE ID IN($userID)";
        }
            //echo   $query ;die(); 
        return $this->db->execute($query);
    }

  /**
    * deletes the cached pages of the admin role of the given institute
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $instID
    * @return   void
    *
    */

    public function deleteAdminRoleCachedPages($instID)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = " SELECT ur.ID FROM UserRoles ur, MapClientUser mcu
                        WHERE  ur.\"UserID\" = mcu.\"UserID\" AND mcu.\"isEnabled\" = '1' AND ur.\"isAdmin\" ='Y'
                        AND mcu.\"ClientID\" = {$instID} ";
        }
        else
        {
            $query      = " SELECT ur.ID FROM UserRoles ur, MapClientUser mcu
                        WHERE  ur.UserID = mcu.UserID AND mcu.isEnabled = '1' AND ur.isAdmin ='Y'
                        AND mcu.ClientID = {$instID} ";
        }

        $arrRoleID  = $this->db->getRows($query);

        if ( $arrRoleID )
        {
            foreach( $arrRoleID as $lroleID )
            {
                $roleID = $lroleID['ID'];
                if(intval($roleID) > 0)
                {
                    $cachedPages = glob("{$this->cfgApp->cachedPages}*_$roleID.html");
                    array_map('unlink',$cachedPages);
                }
            }
        }
    }

  /**
    * adds the new role to the current institute
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    public function saveRole(array $input)
    {
        global $DBCONFIG;
        $verifyRole = $this->verifyRole($input['roleName'],0);

        if($verifyRole['roleCount'] > 0)
        {
            return ROLEFAIL;
        }

        $roleDetails    = array(0,$input['roleName'],$this->session->getValue('userID'),$this->session->getValue('userID'),$this->currentDate(),($input['isAdmin'] == '1')?'Y':'N','N','1','N');
        $data           = $this->db->executeStoreProcedure('RoleManage', $roleDetails,'insert');
        $this->myDebug('$data start');
        $this->myDebug($data);
        $this->myDebug('$data end');
        $lastID = $this->getValueArray($data['RS'],'lastID');
        $this->myDebug($lastID);
        $this->myDebug('lastid start');
        $this->myDebug($lastID);
        if($DBCONFIG->dbType == 'Oracle')
        {   
            $result         = $this->updateRoleRights($input,$lastID);
        }
        else
        {
            $result         = $this->updateRoleRights($input, $lastID);
        }
        $this->generateXls("Excel5", "excelnew");
        $this->generateXls("Excel5", "excelold");
        return $result;
    }

  /**
    * verifies whether role already exists in the given institute
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $roleName
    * @param    integer $roleID
    * @return   array
    *
    */

    public function verifyRole($roleName,$roleID)
    {
        return $this->db->executeFunction('ValidateRole', 'roleCount', array($this->session->getValue('instID'),$roleName,$roleID));
    }

  /**
    * updates the rights of the givn role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @param    integer $roleID
    * @return   boolean
    *
    */

    public function updateRoleRights(array $input,$roleID)
    {
        if(!empty($input['rights']))
        {
            $rightIds           = implode(',',array_keys($input['rights']));
            $rightStatus        = implode(",",$input['rights']);
            $rightStatus        = str_replace("1", "Y",$rightStatus);
            $roleRightDetails   = array(1,$roleID,$rightIds,$this->session->getValue('userID'),$this->session->getValue('userID'),$this->currentDate(),$rightStatus,'1');
            return $this->db->storeProcedureManage('MapRoleRightManage', $roleRightDetails);
        }
        return true;
    }

  /**
    * updates the role details of the given role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @param    integer $roleID
    * @return   mixed
    *
    */

    public function updateRole(array $input,$roleID)
    {
        $verifyRole = $this->verifyRole($input['roleName'], $roleID);
        if($verifyRole['roleCount'] > 0)
        {
            return ROLEFAIL;
        }

        $roleDetails = array($roleID,$input['roleName'],$this->session->getValue('userID'),$this->session->getValue('userID'),$this->currentDate(),($input['isAdmin'] == '1')?'Y':'N',$input['isDefault'],'1','N');
        $this->db->executeStoreProcedure('RoleManage', $roleDetails,'update');
        $this->deleteCachedPages($roleID);
        $this->updateRoleRights($input,$roleID);
        $result = $this->updateRights($input,$roleID);
        $this->generateXls("Excel5", "excelnew");
        $this->generateXls("Excel5", "excelold");
        return $result;
    }

  /**
    * enables/disables the rights of the given role occured after role change
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @param    integer $roleID
    * @return   boolean
    *
    */

    public function updateRights(array $input,$roleID)
    {
        global $DBCONFIG;
        if($input['isAdmin'] == '1')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query      = "UPDATE MapRoleRight SET \"isActive\" = 'Y' WHERE \"UserRoleID\" = $roleID";
            }
            else
            {
                $query      = "UPDATE MapRoleRight SET isActive = 'Y' WHERE UserRoleID = $roleID";
            }
        }
        else
        {
            $rightIDs   = implode(',',array_keys($input['rights']));

            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $condition  = ($rightIDs != '')?" AND \"UserRightID\" NOT IN ($rightIDs)":'';
                $query      = "UPDATE MapRoleRight SET \"isActive\" = 'N' WHERE \"UserRoleID\" = $roleID $condition ";
            }
            else
            {
                $condition  = ($rightIDs != '')?" AND UserRightID NOT IN ($rightIDs)":'';
                $query      = "UPDATE MapRoleRight SET isActive = 'N' WHERE UserRoleID = $roleID $condition ";
            }
        }
        return $this->db->execute($query);
    }

  /**
    * deletes the given users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    $userID array
    * @return   boolean
    *
    */

    public function delete($userID)
    {
        global $DBCONFIG;

        $userIDs    = (array)$this->removeBlankElements((array)$userID);
        $userIDs    = (array)$this->removeUnAccessEnities('UserDelete','',$userIDs);
        $userID     =  implode(',',$userIDs);

        if(($this->session->getValue('isDefault')=='N')&& ($this->session->getValue('isAdmin')))
        {
           $userID = $this->removeAdmin($userID);
        }

        if(!empty($userID))
        {
            if(in_array($this->session->getValue('userID'), $userIDs))
            {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    //$query  = " UPDATE MapClientUser mcu, MapUserRole mur, Users u SET mcu.\"isEnabled\" = '0', mur.\"isEnabled\" = '0'  WHERE mcu.\"UserID\" IN($userID)
                            //AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.ID = mur.\"MapClientUserID\" AND mcu.\"UserID\" = u.ID AND u.\"isDefault\" = 'N' AND mcu.\"ClientID\" = {$this->session->getValue('instID')} ";
                    $query = "  update MapClientUser mcu set  mcu.\"isEnabled\" = 0 where mcu.\"UserID\" IN($userID) AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.\"ClientID\" = {$this->session->getValue('instID')} ";
                    $this->db->execute($query);

                    $query = "  update Users u set u.\"isEnabled\" = 0 where u.\"ID\" IN($userID) AND u.\"isDefault\" = ''N'' AND u.\"ID\" NOT IN ({$this->session->getValue('userID')}) ";
                    $this->db->execute($query);

                    $query = "  update MapUserRole mur set mur.\"isEnabled\" = 0 where mur.\"MapClientUserID\"  in (select mcu.ID from MapClientUser mcu where mcu.\"UserID\" IN($userID) AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.\"ClientID\" = {$this->session->getValue('userID')}) ";
                    $this->db->execute($query);

                    $updateUserRole = $this->db->executeStoreProcedure('UpdateUserRoleCount', array('USRDEL', $this->session->getValue('instID'), $userID, '' ) );
                    return true;
                }
                else
                {
                    $query  = " UPDATE MapClientUser mcu, MapUserRole mur, Users u
                                        SET mcu.isEnabled = '0', mur.isEnabled = '0'  WHERE mcu.UserID IN($userID)
                                            AND mcu.UserID NOT IN ({$this->session->getValue('userID')}) AND mcu.ID = mur.MapClientUserID AND mcu.UserID = u.ID AND u.isDefault = 'N' AND mcu.ClientID = {$this->session->getValue('instID')} ";
                            return $this->db->execute($query);
                }
            }
            else
            {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    //$query  = " UPDATE MapClientUser mcu, MapUserRole mur, Users u SET mcu.\"isEnabled\" = '0', mur.\"isEnabled\" = '0'  WHERE mcu.\"UserID\" IN($userID)
                    //        AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.ID = mur.\"MapClientUserID\" AND mcu.\"UserID\" = u.ID AND mcu.\"ClientID\" = {$this->session->getValue('instID')} ";
                    $query = "  update MapClientUser mcu set  mcu.\"isEnabled\" = 0 where mcu.\"UserID\" IN($userID) AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.\"ClientID\" = {$this->session->getValue('instID')} ";
                    $this->db->execute($query);

                    $query = "  update Users u set u.\"isEnabled\" = 0 where u.\"ID\" IN($userID) AND u.\"ID\" NOT IN ({$this->session->getValue('userID')}) ";
                    $this->db->execute($query);

                    $query = "  update MapUserRole mur set mur.\"isEnabled\" = 0 where mur.\"MapClientUserID\"  in (select mcu.ID from MapClientUser mcu where mcu.\"UserID\" IN($userID) AND mcu.\"UserID\" NOT IN ({$this->session->getValue('userID')}) AND mcu.\"ClientID\" = {$this->session->getValue('userID')}) ";
                    $this->db->execute($query);
                    
                    $updateUserRole = $this->db->executeStoreProcedure('UpdateUserRoleCount', array('USRDEL', $this->session->getValue('instID'), $userID, '' ) );
                    return true;
                }
                else
                {
                    $query1 = " UPDATE  Users  SET isEnabled = '0'  WHERE ID IN($userID) ";
                    
                    $this->db->execute($query1);  
                    
                    $query = "SELECT LanguageID AS prevLang FROM Users WHERE ID = ".$userID;
                    $prevLang = $this->db->getSingleRow($query);
                    
                    $languageID = $prevLang['prevLang'];
                    

                    $query2 = "SELECT UsageCount FROM VerboseLanguage WHERE ID = ".$languageID;
                    $currentCount = $this->db->getSingleRow($query2);
                    
                    if($currentCount>0){
                        $query = "UPDATE VerboseLanguage SET UsageCount = (UsageCount-1) WHERE ID = ".$prevLang['prevLang'];
                        $this->db->execute($query);
                    }
					
                    $query  = " UPDATE MapClientUser mcu, MapUserRole mur, Users u SET mcu.isEnabled = '0', mur.isEnabled = '0'  WHERE mcu.UserID IN($userID)
                            AND mcu.UserID NOT IN ({$this->session->getValue('userID')}) AND mcu.ID = mur.MapClientUserID AND mcu.UserID = u.ID AND mcu.ClientID = {$this->session->getValue('instID')} ";
                    return $this->db->execute($query);
                            
                }
            }            
        }
        else
        {
            return false;
        }
    }

  /**
    * enables the given users
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        array   $userID
    * @return       boolean
    *
    */

    function enable($userID)
    {
        $userID = implode(',',(array)$this->removeBlankElements((array)$userID));
        $query = "  UPDATE Users SET isEnabled = '1' WHERE ID IN($userID);UPDATE MapUserRole SET isEnabled = '1' WHERE ID IN($userID); ";
        return $this->db->execute($query);
    }

  /**
    * deletes the given roles
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $roleID
    * @return   boolean
    *
    */

    public function deleteRole($roleID)
    {
        global $DBCONFIG;

        $roleIDs = (array)$this->removeBlankElements((array)$roleID);
        $roleID  = implode(',',(array)$this->removeUnAccessEnities('RoleDelete','',$roleIDs));

        if(!empty($roleID))
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query  = " UPDATE UserRoles SET \"isEnabled\" = '0' WHERE ID IN ($roleID) AND ID NOT IN ({$this->session->getValue('roleID')}) AND \"isDefault\" = 'N'";
                if(($this->session->getValue('isDefault')=='N')&& ($this->session->getValue('isAdmin')))
                {
                   $query .= " AND \"isAdmin\" = 'N'";
                }
            }
            else
            {
                $query  = " UPDATE UserRoles SET isEnabled = '0' WHERE ID IN ($roleID) AND ID NOT IN ({$this->session->getValue('roleID')}) AND isDefault = 'N'";
                if(($this->session->getValue('isDefault')=='N')&& ($this->session->getValue('isAdmin')))
                {
                   $query .= " AND isAdmin = 'N'";
                }
            }
            
            
            $result = $this->db->execute($query);
            $this->generateXls();
            return $result;
        }
        else
        {
            return false;
        }

    }

  /**
    * enables the given roles
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        array   $roleID
    * @return       boolean
    *
    */

    public function enableRole($roleID)
    {
        $roleID = implode(',',(array)$this->removeBlankElements($roleID));
        $query  = "  UPDATE UserRoles SET isEnabled = '1' WHERE ID IN($roleID) ";
        return $this->db->execute($query);
    }

  /**
    * approves the given users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $userID
    * @return   boolean
    *
    */

    public function approve($userID)
    {
        global $DBCONFIG;
        $userID = implode(',',(array)$this->removeBlankElements($userID));
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = "  UPDATE MapClientUser SET \"isApproved\" = 'Y', \"ModBY\" = {$this->session->getValue('userID')} WHERE \"UserID\" IN ($userID) AND \"ClientID\" = {$this->session->getValue('instID')} ";
            
        }
        else
        {
            $query  = "  UPDATE MapClientUser SET isApproved = 'Y', ModBY={$this->session->getValue('userID')} WHERE UserID IN($userID) AND ClientID = {$this->session->getValue('instID')}";
        }
        return $this->db->execute($query);
    }

  /**
    * gets the role details by roleid
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $roleID
    * @return   array
    *
    */

    public function roleInfoByID($roleID)
    {
        $query  = "SELECT * FROM UserRoles  WHERE ID='{$roleID}' ";
        $retval = $this->db->getSingleRow($query);
        return $retval;
    }

  /**
    * gets the rights list of the given role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $roleID
    * @return   array
    *
    */

    public function mapRoleRightIDs($roleID)
    {
        global $DBCONFIG;
        $rightIDs   = array();
        $rights     = array();

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = " SELECT \"UserRightID\" FROM MapRoleRight WHERE \"UserRoleID\"  = $roleID AND \"isEnabled\" = '1' AND \"isActive\" = 'Y'";
        }
        else
        {
            $query      = " SELECT UserRightID FROM MapRoleRight WHERE UserRoleID  = $roleID AND isEnabled = '1' AND isActive = 'Y'";
        }
        $rightIDs   = $this->db->getRows($query);

        if(!empty ($rightIDs))
        {
            foreach($rightIDs as $right)
            {
                array_push($rights, $right['UserRightID']);
            }
        }
        return array_unique($rights);
    }

  /**
    * gets the preferences list of the the user if given
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @return   array
    *
    */

    public function userSettingsInfo($userID='')
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            if(empty($userID)):
                    $query =  " SELECT * FROM Preferences up where  up.\"isEnabled\" = '1' order by up.\"Sequence\" ";
            else:
                    $query =  " select mup.ID,up.\"PreferenceCode\",up.\"PreferenceTitle\", up.\"DefaultValue\",up.\"Sequence\",
                                up.\"isEnabled\", mup.\"PreferenceValue\",  mup.\"isEnabled\" from Preferences up inner join
                                MapUserPreferences mup on mup.\"PreferenceID\" = up.ID where mup.\"UserID\" = '{$userID}' order by mup.ID ";
            endif;
        }
        else
        {
            if(empty($userID)):
                    $query =  " SELECT * FROM Preferences up where  up.isEnabled = '1' order by up.Sequence ";
            else:
                    $query =  " select mup.ID,up.PreferenceCode,up.PreferenceTitle, up.DefaultValue,up.Sequence,
                                up.isEnabled, mup.PreferenceValue,  mup.isEnabled from Preferences up inner join
                                MapUserPreferences mup on mup.PreferenceID = up.ID where mup.UserID = '{$userID}' group by up.PreferenceCode order by mup.ID ";
            endif;
        }
        $settings = $this->db->getRows($query);
        return $settings;
    }

  /**
    * gets the rights list of the given user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @return   array
    *
    */

    public function userRightInfo($userID)
    {
        $this->myDebug("userRightInfo");        
        return $this->db->executeStoreProcedure('UserRightsList', array($this->getRoleID($userID)));
    }
    

  /**
    * gets the rights list of the given user for given entityID, entityTypeID
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @param    integer $EntityTypeId
    * @param    integer $EntityId
    * @return   string
    *
    */

     public function userSpecificRights($userID,$EntityTypeId,$EntityId)
    {
         global $DBCONFIG;

         if ( $DBCONFIG->dbType == 'Oracle' )
         {
            $query      = "  select * from MapEntityRights where \"MemberId\" = '{$userID}' and \"EntityTypeId\" ='{$EntityTypeId}' and \"EntityId\"='{$EntityId}' and  \"isEnabled\"='1'";
         }
         else
         {
             $query      = "  select * from MapEntityRights where MemberId='{$userID}' and EntityTypeId='{$EntityTypeId}' and EntityId='{$EntityId}' and  isEnabled='1'";
         }

        $result     = $this->db->getRows($query);
        $noofrows   = count($result);

        $str        = '';

        for($j=0;$j<$noofrows;$j++)
        {
            $RightId    = $result[$j]['UserRightsId'];
            $Rightval   = $result[$j]['isActive'];
            $str       .= $RightId.':'.$Rightval.',';
        }

        $str = trim($str,',');
        $str = $userID.'-'.$str;
        return $str;
    }

  /**
    * gets the client preferences list of the client if given
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   array
    *
    */

    public function clientSettingsInfo($clientID='')
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            if(empty($clientID)):
                    $query =  "SELECT * FROM ClientPreferences cp where  cp.\"isEnabled\" = '1' order by cp.\"Sequence\"";
            else:
                    $query =  "select mcp.ID,cp.\"PreferenceCode\",cp.\"PreferenceTitle\", cp.\"Sequence\",
                               cp.\"isEnabled\", mcp.\"PreferenceValue\" as \"PreferenceValue\",  mcp.\"isEnabled\" from ClientPreferences cp inner join
                               MapClientPreferences mcp on mcp.\"PreferenceID\" = cp.ID where mcp.\"ClientID\" = '{$clientID}' ";
            endif;
        }
        else
        {
            if(empty($clientID)):
                    $query =  "SELECT * FROM ClientPreferences cp where  cp.isEnabled = '1' order by cp.Sequence";
            else:
                    $query =  "select mcp.ID,cp.PreferenceCode,cp.PreferenceTitle, cp.Sequence,
                               cp.isEnabled, mcp.PreferenceValue as PreferenceValue,  mcp.isEnabled from ClientPreferences cp inner join
                               MapClientPreferences mcp on mcp.PreferenceID = cp.ID where mcp.ClientID = '{$clientID}' group by cp.PreferenceCode ";
            endif;
        }

        $settings = $this->db->getRows($query);
        return $settings;
    }

  /**
    * verifies the given institution code
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    public function verifyPromoCode(array $input)
    {
        $this->clientInfo = array();
        $this->clients    = array();

        if (count(array_unique($input['promocode'])) < count($input['promocode']))
        {
            return DUPLICATEPROMPCODE;
        }

        $input['promocode'] = $this->removeBlankElements($input['promocode']);

        if(!empty($input['promocode']))
        {
            $client = new Client();
            foreach($input['promocode'] as $key=>$value)
            {
                $this->clientInfo = $client->verifyPromoCode(trim($value));
                
                if($this->clientInfo['ClientID'] < 1 || $this->clientInfo['StartDiff'] < 0 || $this->clientInfo['EndDiff'] < 0)
                {
                    return "{$value} is ".INVALIDPROMPCODE;
                }

                $this->clients[] = $this->clientInfo['ClientID'];
            }
        }
        else
        {
            return EMPTYPROMPCODE;
        }

        $this->clients = $this->removeBlankElements($this->clients);
        $this->clients = array_unique($this->clients);

        return true;
    }

  /**
    * completes the user registration
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    arary   $input
    * @return   mixed
    *
    */

    public function completeReg(array $input)
    {
        global $DBCONFIG;

        if(!$this->session->getValue('validcaptcha'))
        {
            return INVALIDCAPTCH;
        }

        if($this->verifyUserName($input['username']))
        {
            return USERNAMEEXISTS;
        }

        list($firstname,$lastname) = explode(' ', $input['firstname']);

        $userID = ($this->session->getValue('verified_userID') > 0)?$this->session->getValue('verified_userID'):$input['verified_userID'];

        if($input['password'] != ''){
            $input['password'] = Site::getSecureString($input['password']);
        }
        
        if($input['SecurityAnswer'] != '' && !(Site::isValidSecureString($input['SecurityAnswer']))){
            $input['SecurityAnswer'] = Site::getSecureString($input['SecurityAnswer']);
        }

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = " update MapClientUser set \"isVerified\" = 'Y' where \"UserID\" = {$userID} ";
            $this->db->execute($query);
            $query  = "  update Users set \"FirstName\" = '{$firstname}', \"LastName\" = '{$lastname}', \"UserName\" = '{$input['username']}', \"Password\" = '{$input['password']}', \"SecurityQuestion\" = '{$input['SecurityQuestion']}', \"SecurityAnswer\" = '{$input['SecurityAnswer']}' where \"ID\" = {$userID} ";
            $this->db->execute($query);
        }
        else
        {
            $query  = " update MapClientUser set isVerified = 'Y' where UserID = {$userID} ";
            $this->db->execute($query);
            $query  = "  update Users set FirstName = '{$firstname}', LastName = '{$lastname}', UserName = '{$input['username']}', Password = '{$input['password']}', SecurityQuestion = '{$input['SecurityQuestion']}', SecurityAnswer = '{$input['SecurityAnswer']}' where ID = {$userID} ";
            $this->db->execute($query);
        }
        return true;
    }

  /**
    * adds the new user from open registration
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    public function register(array $input)
    {
        global $DBCONFIG;

        $isValidPromoCode   = $this->verifyPromoCode($input);

        if($isValidPromoCode !== true)
        {
            return $isValidPromoCode;
        }

        if(!$this->session->getValue("validcaptcha"))
        {
            return INVALIDCAPTCH;
        }

        if($this->verifyUserName($input['username']))
        {
            //return USERNAMEEXISTS;
        }

        if($this->verifyEmail($input['pemail']))
        {
            return EMAILEXISTS;
        }

        $clientID   = ($this->clientInfo['ClientID'] > 0)?$this->clientInfo['ClientID']:$this->getDefaultClientID();
        $userID     = $this->getDefaultUserID($clientID);
        $roleID     = $this->getDefaultRoleID($clientID);
        $emailVRF   = ($this->getEmailVrfPref($clientID) == 'Y')?'N':'Y';
        $clientVRF  = ($this->getClientVrfPref($clientID) == 'Y')?'N':'Y';
        $input['itemID']=($input['itemID']!='')?$input['itemID']:'-1';
        if($DBCONFIG->dbType!='Oracle'){
            $isQuadLite = ($this->verifyQuadLitePref($input,$clientID) > 0)?'Y':'N';
        }
        list($firstname,$lastname,$middlename) = explode(' ', $input['firstname']);

        $userDetails = array(0,
            $input['title'],
            str_replace("'", "\'", ucfirst($firstname)),
            str_replace("'", "\'", $middlename),
            str_replace("'", "\'", ucfirst($lastname)),
            $input['username'],1,
            $input['password'],
            $input['pemail'],
            $input['semail'],
            $this->formattedDate($input['dateofbirth']),
            $input['gender'],
            str_replace("'", "\'", $input['address']),
            $input['city'],
            $input['state'],
            $input['country'],
            $input['zipcode'],
            $input['phone'],
            $input['fax'],
            $input['cellphone'],
            $userID,
            $this->currentDate(),$userID,
            $this->currentDate(),'','',0,
            $emailVRF,$clientVRF,'1','N',
            $clientID,$roleID,'Y',
            $isQuadLite,
            $input['itemID'],'-1','-1',$DBCONFIG->quadliteDB,
            str_replace("'", "\'", $input['SecurityQuestion']),
            str_replace("'", "\'", $input['SecurityAnswer']));

        $user = $this->db->executeStoreProcedure('UserManage', $userDetails,'nocount');
        $user = $this->getValueArray($user,'UserID');
        $this->addPromoCodeUser($this->clients, $user,'N');
        $this->createDir($clientID);

        $this->clientInfo = array();
        $this->clients    = array();

        return true;
    }

  /**
    * adds the existing user for another institute from open registration
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    public function existingUserRegister(array $input)
    {
        $this->myDebug('This is Existing user Register');
        $this->myDebug($input);
        $isValidPromoCode = $this->verifyPromoCode($input);

        if($isValidPromoCode !== true)
        {
            return $isValidPromoCode;
        }

        if(!$this->session->getValue('validcaptcha'))
        {
            return INVALIDCAPTCH;
        }

        if(!$this->verifyEmail($input['pemail']))
        {
            $this->register($input);
            //return EMAILNOTEXISTS;
        }

        $clientInfo = $this->getInstInfoByEmail($input['pemail']);
        $clientInfo = $this->removeBlankElements($clientInfo);

        if(empty($clientInfo))
        {
            //return EMAILNOTEXISTS;
        }

        $instInfo   = $this->getValueArray($clientInfo,'instID', 'multiple', 'array');
        $similar    = array_intersect($this->clients,array_keys($instInfo));
        $similar    = $this->removeBlankElements($similar);
        $isVerified = $this->getValueArray($clientInfo,'isVerified', 'multiple', 'array');
        $isVerified = array_keys($isVerified);
        $isVerified = $this->removeBlankElements($isVerified);


        if(!empty($similar))
        {
            $institutes = $this->getKeyValuesByOtherKey($clientInfo, $similar, 'instName','multiple','string');
            return ALREADYMEMBER." {$institutes}";
        }

        //$status     = (in_array('Y',$isVerified)?'Y':'N');
        $status     = 'N';
        $this->addPromoCodeUser($this->clients, $clientInfo[0]['userID'],$status);

        $this->clientInfo   = array();
        $this->clients      = array();

        return true;
    }

  /**
    * gets the institute information by email
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @return   array
    *
    */

    public function getInstInfoByEmail($email)
    {
        global $DBCONFIG;
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query = "  select c.\"ID\" as \"instID\", c.\"OrganizationName\" as \"instName\", u.\"ID\" as \"userID\", mcu.\"isVerified\" from Clients c, Users u, MapClientUser mcu
                    where u.\"PrimaryEmailID\" = '$email' and u.\"ID\" = mcu.\"UserID\" and mcu.\"ClientID\" = c.\"ID\"
                    and mcu.\"isEnabled\" = '1' and c.\"isEnabled\" = '1' ";
        }
        else
        {
            $query = "  select c.ID as instID, c.OrganizationName as instName, u.ID as userID, mcu.isVerified from Clients c, Users u, MapClientUser mcu
                    where u.PrimaryEmailID = '$email' and u.ID = mcu.UserID and mcu.ClientID = c.ID
                    and mcu.isEnabled = '1' and c.isEnabled = '1' ";
        }
        
        return $this->db->getRows($query);
    }

  /**
    * adds the user for the given institutes having different institution codes
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $clients
    * @param    integer $userID
    * @param    string  $isVerified
    * @return   void
    *
    */

    public function addPromoCodeUser(array $clients,$userID,$isVerified)
    {
        if(!empty($clients))
        {
            $verification       = array();
            $verificationurl    = array();
			$this->myDebug('This is Add Promocode User');
            foreach($clients as $client)
            {
                $clientID   = $this->getDefaultUserID($client);
                $roleID     = $this->getDefaultRoleID($client);
                $emailVRF   = ($this->getEmailVrfPref($client) == 'Y')?'N':'Y';
                $clientVRF  = ($this->getClientVrfPref($client) == 'Y')?'N':'Y';
                $array1     = array(0,$client,$userID,$this->currentDate(),$clientID,$this->currentDate(), 'Y', '1',$clientVRF,$emailVRF);


                $user       = $this->db->executeStoreProcedure('MapClientUserManage',$array1 ,'nocount');
                $mapID      = $this->getValueArray($user, 'mapID');
                $array2     = array(0,$mapID,$roleID,$this->currentDate(),$clientID,'1');
                $this->db->executeStoreProcedure('MapUserRoleManage', $array2,'nocount');

                $verification['code']   = $this->getValueArray($user, 'verificationCode');
                $verification['id']     = $mapID;
                $verificationurl[]      = $this->userVerificationUrl($verification);
            }

            $this->registry->verificationurl = $verificationurl;
        }
    }

  /**
    * gets the default client ID
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param
    * @return       void
    *
    */

    public function getDefaultClientID()
    {
        //check session DefaultClientID , if no then assign from DB and then return else return from Session
        if($this->session->getValue('defaultClientID') == '')
        {
            //$query  = ' SELECT DefaultClientID() as clientID ';
            //$client = $this->db->getSingleRow($query);
            //$this->session->load('defaultClientID',$client['clientID']);
            $this->session->load('defaultClientID',-1);
        }
        return $this->session->getValue('defaultClientID');
    }

  /**
    * gets the default user of the given institute/client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   integer
    *
    */

    public function getDefaultUserID($clientID)
    {
      //  $query  = " SELECT DefaultUserID($clientID) as userID ";
        $user   = $this->db->executeFunction('DefaultUserID', 'userID', array($clientID));
        return $user['userID'];
    }

  /**
    * gets the status of the quad lite setting for the given package/item
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        integer $itemID
    * @return       string
    *
    */

    public function checkQuadLite($itemID)
    {
        $query      = " SELECT isActive FROM Features WHERE PackageID = $itemID AND FeatureCode = 'MNGQL' ";
        $quadlite   = $this->db->getSingleRow($query);
        return $quadlite['isActive'];
    }

  /**
    * gets the default role for the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   integer
    *
    */

    public function getDefaultRoleID($clientID)
    {
        //$query  = " SELECT DefaultClientRoleID($clientID) as roleID ";
        $role   = $this->db->executeFunction('DefaultClientRoleID','roleID',array($clientID));
        return $role['roleID'];
    }

  /**
    * gets the admin role ID for the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   integer
    *
    */

    public function getAdminRoleID($clientID)
    {
       // $query  = " SELECT AdminRoleID($clientID) as roleID ";
        $role   = $this->db->executeFunction('AdminRoleID','roleID',array($clientID));
        $this->myDebug("This is AdminRoleID");
        $this->myDebug($role);
        return $role['roleID'];
    }

  /**
    * gets whether the given role ID is Admin role ID for the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer   $clientID
    * @param    integer   $roleID
    * @return   integer
    *
    */

    public function isAdminRoleID($clientID,$roleID)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = " SELECT ur.ID as \"roleID\" FROM UserRoles ur, MapClientUser mcu
                    WHERE ur.\"UserID\" = mcu.\"UserID\" AND mcu.\"ClientID\" = $clientID AND ur.\"isAdmin\" = 'Y' AND mcu.\"isEnabled\" = '1'
                    AND ur.\"isEnabled\" = '1' AND ur.ID = $roleID ";
        }
        else
        {
            $query  = " SELECT ur.ID as roleID FROM UserRoles ur, MapClientUser mcu
                    WHERE ur.UserID = mcu.UserID AND mcu.ClientID = $clientID AND ur.isAdmin = 'Y' AND mcu.isEnabled = '1'
                    AND ur.isEnabled = '1' AND ur.ID = $roleID ";
        }
        $role   = $this->db->getSingleRow($query);
        return intval($role['roleID']);
    }

  /**
    * gets the role ID of the given user for current institute
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @return   integer
    *
    */

    public function getRoleID($userID)
    {
       // $query  = " SELECT RoleID($userID,{$this->session->getValue('instID')}) as userRoleID ";
        $right  = $this->db->executeFunction('RoleID', 'userRoleID', array($userID, $this->session->getValue('instID')));
        return $right['userRoleID'];
    }

  /**
    * gets the email verification preference setting of the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   string
    *
    */

    public function getEmailVrfPref($clientID)
    {
      //  $query  = " SELECT EmailVerificationRecquire($clientID) as status ";
        $vrf    = $this->db->executeFunction('EmailVerificationRecquire', 'status', array($clientID));
        return $vrf['status'];
    }

  /**
    * gets the user verification preference setting of the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   string
    *
    */

    public function getClientVrfPref($clientID)
    {
       // $query  = " SELECT ClientVerificationRecquire($clientID) as status ";
        $vrf    = $this->db->executeFunction('ClientVerificationRecquire', 'status', array($clientID));
        return $vrf['status'];
    }

  /**
    * verifies the quad lite verification preference setting of the given client for the given item
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @param    integer $clientID
    * @return   string
    *
    */

    public function verifyQuadLitePref($input,$clientID)
    {
       // $query  = " SELECT ValidateQuadLiteAccess($clientID,{$input['itemID']}) as status ";
        $vrf    = $this->db->executeFunction('ValidateQuadLiteAccess', 'status', array($clientID,$input['itemID']));
        return $vrf['status'];
    }

  /**
    * adds the new institute/client from open registration
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    array   $input
    * @param    integer $clientID
    * @return   mixed
    *
    */

    public function registerClient(array $input,$clientID=0)
    {
        global $DBCONFIG;
        $clientID           = ($clientID>0)?$clientID:0;
        $contactEmailCond   = ($clientID > 0)?" AND c.ID<>$clientID ":'';

        if(trim($input['OfficialEmailID']) != '')
        {
            if($this->verifyOfficialEmail($input['OfficialEmailID']))
            {
                return OFFICIALEMAILEXISTS;
            }
        }
        if(!$this->registry->quadTemplate == 'next'){
            if(!$this->session->getValue("validcaptcha"))
            {
                return INVALIDCAPTCH;
            }
        }
        if($this->verifyUserName($input['AdminUserName'],' ID<>'.$input['userID'].' '))
        {
            return USERNAMEEXISTS;
        }

        if(!$this->registry->quadTemplate == 'next'){
            if($this->verifyEmail($input['ContactEmailID'])  && $clientID == 0)
            {
                return CONTACTEMAILEXISTS;
            }
        }

        $organization   = str_replace(' ', '', $input['OrganizationName']);
        $contactPerson  = str_replace(' ', '', $input['ContactPerson']);
//        $userName = $this->generateUserName($organization, $contactPerson);
//        $password = $this->generatePassword(8);
        $userName = $input['AdminUserName'];
        $password = $input['AdminPassword'];

        $promocode  = $this->generatePromoCode(trim(str_replace(' ','',$input['OrganizationName'])));
        $token      = $this->generateToken();
        $isQuadLite = $this->checkQuadLite($input['itemID']);
        $this->myDebug($input);
        $this->myDebug($input);
        $clientDetails = array($clientID,
        str_replace("'", "\'", $input['OrganizationName']),
        str_replace("'", "\'", $input['OrganizationInfo']),1,1,
        str_replace("'", "\'", $input['Address']),
        $input['City'],
        $input['State'],
        $input['Country'],
        $input['Zipcode'],
        $input['Phone'],
        $input['Fax'],
        $input['OfficialEmailID'],
        $input['Website'],
        str_replace("'", "\'",ucwords($input['ContactPerson'])),1,
        $input['ContactEmailID'],
        $input['AlternateEmailID'],
        $input['ContactPhone'],0,
        $this->currentDate(),'Y','Y','1','Y',
        -1,
        $userName,
        md5($password),
        $token, $input['itemID'],'','','',
        $promocode,$isQuadLite,$DBCONFIG->quadliteDB,
        str_replace("'", "\'", $input['SecurityQuestion']),
        str_replace("'", "\'", $input['SecurityAnswer']),
        $input['userID']);
        $data = $this->db->executeStoreProcedure('ClientManage', $clientDetails);

        $verification['code']   = $this->getValueArray($data['RS'], 'verificationCode');
        $verification['id']     = $this->getValueArray($data['RS'], 'mapID');
        $verificationurl        = $this->userVerificationUrl($verification);
		$clientID				= $this->getValueArray($data['RS'], 'cid');
		$this->myDebug("This is Client ID".$clientID);
        $this->createDir($clientID);

        $details = array(
                    'username'  => $userName,
                    'password'  => $password,
                    'promocode' => $promocode,
                    'verifyurl' => $verificationurl
                    );
        return $details;
    }

  /**
    * gets the default package ID/item ID
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param
    * @return   integer
    *
    */

    public function getDefaultPackageID()
    {
       // $query      = " SELECT DefaultPackageID() as packageID ";
        $package    = $this->db->executeFunction('DefaultPackageID', 'packageID');
        return $package['packageID'];
    }

  /**
    * updates the user preferences
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    public function updateUserPref(array $input)
    {
        if(!empty($input['userPrefs']))
        {
            $prefIds     = implode(',',array_keys($input['userPrefs']));
            $prefVals    = implode(',',$input['userPrefs']);

            $preferences = $input['userPrefs'];
            $userPrefs   = array($prefIds,$prefVals,$this->session->getValue('userID'),$this->currentDate());

            $this->db->storeProcedureManage('PreferencesManage', $userPrefs);
            $this->updateSettings();
        }
        return true;
    }

  /**
    * updates the clients preferences
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    public function updateClientPref(array $input)
    {
        if(!empty($input['clientPrefs']))
        {
            $prefIds     = implode(',',array_keys($input['clientPrefs']));
            $prefVals    = implode(",",$input['clientPrefs']);
            $clientPrefs = array($prefIds,$prefVals,$this->session->getValue('userID'),$this->currentDate());
            $this->db->storeProcedureManage('ClientPreferencesManage', $clientPrefs);
        }
        return true;
    }

  /**
    * updates the Organization Url to the given url for the given client ID
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $url
    * @param    integer $clientID
    * @return   boolean
    *
    */

    public function updateClientUrl($url,$clientID)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query  = "  UPDATE Clients SET \"OrganizationUrl\" = '$url' WHERE ID='$clientID' ";
        }
        else
        {
            $query  = "  UPDATE Clients SET OrganizationUrl='$url' WHERE ID='$clientID' ";
        }
        return $this->db->execute($query);
    }

  /**
    * gets the users list of the current institution for the given search condition
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function searchList(array $input)
    {
       return $this->db->executeStoreProcedure('UsersList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$this->session->getValue('instID'),'' , $input['pgndc'], $this->search));

    }

  /**
    * gets the users list for the given entity ID and entity type ID
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function searchMemberQuestionList(array $input)
    {
        global $DBCONFIG;
        $this->myDebug($input);
		$inputData = array($input['EntityID'],$input['EntityTypeID'],$this->session->getValue('instID'));
		$result = $this->db->executeStoreProcedure('SEARCHMEMBERQUESTIONLIST', $inputData,'nocount');
        switch($input['EntityTypeID'])
        {
            case 1:
                if($input['EntityID'] == '-1')
                {
                  /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                mur.RoleID , ur.RoleName
                                FROM Users u
                                inner join MapClientUser mcu on u.ID = mcu.UserID
                                inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                                inner join UserRoles ur on ur.ID = mur.RoleID

                                WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'

                                 ";*/
					
                    $this->entity = 'Bank(s)';
                }
                else if($input['EntityID'] > 0)
                {
                  /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                mur.RoleID , ur.RoleName
                                FROM Users u
                                inner join MapClientUser mcu on u.ID = mcu.UserID
                                inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                                inner join UserRoles ur on ur.ID = mur.RoleID

                                WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'

                                 ";*/
                      $this->entity = 'Question(s)';
                }
            break;

            case 2:

                if($input['EntityID'] == '-1')
                {
                  /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                mur.RoleID , ur.RoleName
                                FROM Users u
                                inner join MapClientUser mcu on u.ID = mcu.UserID
                                inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                                inner join UserRoles ur on ur.ID = mur.RoleID

                                WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'

                                 ";*/
                     $this->entity = 'Assessment(s)';
                }
                else if($input['EntityID'] > 0)
                {
                  /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                mur.RoleID , ur.RoleName
                                FROM Users u
                                inner join MapClientUser mcu on u.ID = mcu.UserID
                                inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                                inner join UserRoles ur on ur.ID = mur.RoleID

                                WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'

									"; */
                      $this->entity = 'Question(s)';
                }
            break;
            case 8:
                if($input['EntityID'] == '-1')
                {
                    if($DBCONFIG->dbType == 'Oracle' )
                {
                  /*  $query = "  SELECT u.\"ID\", u.\"UserName\", u.\"FirstName\", u.\"LastName\", u.\"PrimaryEmailID\", u.\"isDefault\",
                                mur.\"RoleID\" , ur.\"RoleName\", count(DISTINCT q.\"ID\") as queCount
                                FROM Users u
                                inner join MapClientUser mcu on u.\"ID\" = mcu.\"UserID\"
                                inner join MapUserRole mur on mcu.\"ID\" = mur.\"MapClientUserID\"
                                inner join UserRoles ur on ur.\"ID\" = mur.\"RoleID\"
                                left join Assessments q on q.\"UserID\" = u.\"ID\" and q.\"isEnabled\" = '1' AND q.\"ID\" IS NOT NULL AND q.\"Status\" = 'Archive'
                                WHERE u.\"isEnabled\"='1' AND mcu.\"isVerified\" = 'Y' AND mcu.\"isApproved\" = 'Y' AND mcu.\"ClientID\" = {$this->session->getValue('instID')}
                                AND mcu.\"isEnabled\" = '1' AND mur.\"isEnabled\" = '1'
                                AND ur.\"isEnabled\" = '1'
                                group by u.\"ID\", u.\"UserName\", u.\"FirstName\", u.\"LastName\", u.\"PrimaryEmailID\", u.\"isDefault\",
                                mur.\"RoleID\" , ur.\"RoleName\" 
                                ORDER BY queCount desc "; */
                }
                else
                {
                  /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                mur.RoleID , ur.RoleName, count(DISTINCT q.ID) as queCount
                                FROM Users u
                                inner join MapClientUser mcu on u.ID = mcu.UserID
                                inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                                inner join UserRoles ur on ur.ID = mur.RoleID
                                left join Assessments q on q.UserID = u.ID and q.isEnabled = '1' AND q.ID IS NOT NULL AND q.Status = 'Archive'
                                WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'
                                group by u.ID
                                ORDER BY queCount desc "; */
                }
                     $this->entity = 'Archive(s)';
                }
                break;
            case 3:
              /*  $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                            mur.RoleID , ur.RoleName , mcu.QuestionCount as queCount
                            FROM Users u
                            inner join MapClientUser mcu on u.ID = mcu.UserID
                            inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                            inner join UserRoles ur on ur.ID = mur.RoleID
                            WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                            AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                            AND ur.isEnabled = '1'
                            ORDER BY queCount  desc "; */
                $this->entity = 'Question(s)';
                break;

            case 11: //Tags
                if($DBCONFIG->dbType == 'Oracle' )
                {
               /* $query = "  SELECT u.\"ID\", u.\"UserName\", u.\"FirstName\", u.\"LastName\", u.\"PrimaryEmailID\", u.\"isDefault\",
                            mur.\"RoleID\" , ur.\"RoleName\" , count(t.\"UserID\") as queCount
                            FROM Users u
                            inner join MapClientUser mcu on u.\"ID\" = mcu.\"UserID\"
                            inner join MapUserRole mur on mcu.\"ID\" = mur.\"MapClientUserID\"
                            inner join UserRoles ur on ur.\"ID\" = mur.\"RoleID\"
                            left join Tags t on t.\"UserID\" =  u.\"ID\" AND t.\"isEnabled\" = '1'
                            WHERE u.\"isEnabled\" = '1' AND mcu.\"isVerified\" = 'Y' AND mcu.\"isApproved\" = 'Y' AND mcu.\"ClientID\" = {$this->session->getValue('instID')}
                                AND mcu.\"isEnabled\" = '1' AND mur.\"isEnabled\"='1'
                                AND ur.\"isEnabled\" = '1'
                                AND t.\"Count\" > 0
                            GROUP BY u.\"ID\", u.\"UserName\", u.\"FirstName\", u.\"LastName\", u.\"PrimaryEmailID\", u.\"isDefault\",
                            mur.\"RoleID\" , ur.\"RoleName\" ORDER BY queCount desc"; */
                }
                else
                {
                   /* $query = "  SELECT u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                            mur.RoleID , ur.RoleName , count(t.UserID) as queCount
                            FROM Users u
                            inner join MapClientUser mcu on u.ID = mcu.UserID
                            inner join MapUserRole mur on mcu.ID = mur.MapClientUserID
                            inner join UserRoles ur on ur.ID = mur.RoleID
                            left join Tags t on t.UserID =  u.ID AND t.isEnabled = '1'
                            WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                AND ur.isEnabled = '1'
                                AND t.Count > 0
                            GROUP BY u.ID  ORDER BY queCount desc"; */
                }
                $this->entity = 'Tag(s)';
            break;

             case 13: // Metadata
               /* $query = "  SELECT COUNT(DISTINCT mdk.ID) AS queCount, COUNT(DISTINCT mdv.ID) AS valCount, u.ID, u.UserName, u.FirstName, u.LastName, u.PrimaryEmailID, u.isDefault,
                                    mur.RoleID , ur.RoleName
                            FROM Users u
                                INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                                INNER JOIN MapUserRole mur on mcu.ID = mur.MapClientUserID
                                INNER JOIN UserRoles ur on ur.ID = mur.RoleID
                                LEFT JOIN MapMetaDataEntity mde ON mde.UserID = u.ID AND mde.isEnabled = '1'
                                LEFT JOIN MapMetaDataKeyValues mkv ON mkv.ID = mde.KeyValueID AND mkv.isEnabled = '1'
                                LEFT JOIN MetaDataValues mdv ON mdv.ID = mkv.ValueID AND mdv.isEnabled = '1'
                                LEFT JOIN MetaDataKeys mdk ON mdk.ID = mkv.KeyID AND mdk.isEnabled = '1'
                            WHERE u.isEnabled='1' AND mcu.isVerified = 'Y' AND mcu.isApproved = 'Y' AND mcu.ClientID = {$this->session->getValue('instID')}
                                    AND mcu.isEnabled = '1' AND mur.isEnabled='1'
                                    AND ur.isEnabled = '1'
                            GROUP BY u.ID
                            ORDER BY queCount DESC  "; */
                $this->entity = 'Metadata Keys';
                // $this->metadataValues = 'Metadata Values';
        }
		return $result;
       // return $this->db->getRows($query);
    }
    /*
     * For creating User list in advanced seqarch page
     * return user list
     */
    
   public function searchMemberList(array $input)
    {
       //search=&ignoreuserid=&EntityID=-1&EntityTypeID=2
        global $DBCONFIG;
        $this->myDebug($input);
		//$inputData = array($input['EntityID'],$input['EntityTypeID'],$this->session->getValue('instID'));
                $inputData = array('-1','2',$this->session->getValue('instID'));
		$result = $this->db->executeStoreProcedure('SEARCHMEMBERQUESTIONLIST', $inputData,'nocount');
        switch($input['EntityTypeID'])
        {
            case 1:
                if($input['EntityID'] == '-1')
                {			
                    $this->entity = 'Bank(s)';
                }
                else if($input['EntityID'] > 0)
                {
                  
                      $this->entity = 'Question(s)';
                }
            break;

            case 2:

                if($input['EntityID'] == '-1')
                {
                  
                     $this->entity = 'Assessment(s)';
                }
                else if($input['EntityID'] > 0)
                {
                  
                      $this->entity = 'Question(s)';
                }
            break;
            case 8:
                if($input['EntityID'] == '-1')
                {
                    if($DBCONFIG->dbType == 'Oracle' )
                {
                 
                }
                else
                {
                  
                }
                     $this->entity = 'Archive(s)';
                }
                break;
            case 3:
            
                $this->entity = 'Question(s)';
                break;

            case 11: //Tags
                if($DBCONFIG->dbType == 'Oracle' )
                {
              
                }
                else
                {
                   
                }
                $this->entity = 'Tag(s)';
            break;
             case 13: // Metadata             
                $this->entity = 'Metadata Keys';
              
        }
        
                        $userStr  = "";
			$userStr .= '<select class="e1" multiple="multiple" id="user1">';
			foreach($result as $user)
                        {
                            $user_name = (trim($user['FirstName']) !='' || trim($user['LastName']) != '')?$user['FirstName'].' '.$user['LastName']:$user['UserName'];
                            $user_name = (trim($user_name) != '')?$user_name:$user['PrimaryEmailID'];
                            $userStr .= '<option value='.$user["ID"].'>'.$user_name.' ('.$user["RoleName"].')</option>';
			}
        
        
		 return $userStr;
       // return $this->db->getRows($query);
    }
    
    

  /**
    * gets the institution code for the current institution
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param
    * @return       string
    *
    */

    function getPromoCode()
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = " SELECT \"PromoCode\" FROM PromoCodes WHERE \"ClientID\" = {$this->session->getValue('instID')} AND \"isEnabled\" = '1' ";
        }
        else
        {
            $query      = " SELECT PromoCode FROM PromoCodes WHERE ClientID = {$this->session->getValue('instID')} AND isEnabled = '1' ";
        }
        $promocode  = $this->db->getSingleRow($query);
        return $promocode['PromoCode'];
    }

  /**
    * updates the institution/promo code of the current institution
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    array       $input
    * @return   boolean
    *
    */

    function updatePromoCode($input=array())
    {
        global $DBCONFIG;
        site::myDebug(date('Y-m-d',strtotime(str_replace($input['startDate'],'-','/'))));
        site::myDebug(str_replace('-','/',$input['startDate']));
        site::myDebug($input['startDate']);
        site::myDebug('datatatataat');
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $startDate  = (trim($input['startDate']) != '')?$this->getFormatDate(date('Y-m-d',strtotime(str_replace('-','/',$input['startDate'])))):'';
            $endDate    = (trim($input['endDate']) != '')?$this->getFormatDate(date('Y-m-d',strtotime(str_replace('-','/',$input['endDate'])))):'';
            $promoCodePrefs = array($this->session->getValue('instID'),$this->session->getValue('userID'),$this->generatePromoCode('LNM'),$DBCONFIG->quadliteDB,$this->currentDate(),$startDate,$endDate);
        }
        else
        {
            $promoCodePrefs = array($this->session->getValue('instID'),$this->session->getValue('userID'),$this->generatePromoCode('LNM'),$DBCONFIG->quadliteDB,$this->currentDate(),$input['startDate'],$input['endDate']);
        }
        $this->db->executeStoreProcedure('PromoCodeUpdate', $promoCodePrefs);
        return true;
    }

  /**
    * gets the all users list
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param
    * @return       array
    *
    */

    function getAllInstituteUsers()
    {
        $query = "  SELECT distinct c.ID, c.OrganizationName as Name
                    FROM Users u
                    INNER JOIN MapClientUser mcu ON mcu.UserID = u.ID  AND mcu.isEnabled = '1'
                    INNER JOIN Clients c ON mcu.ClientID = c.ID  AND c.isEnabled = '1'
                    WHERE u.isEnabled='1'AND AND c.OrganizationName != ''
                    ORDER BY Name
                 ";
        return $this->db->getRows($query);
    }

  /**
    * gets the reports of the institutes
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        array   $input
    * @return       array
    *
    */

    function getReports(array $input)
    {
        $condition = ($input['filter'] != 'all' || $input['filter'] > 0)?" AND c.ID = {$input['filter']}":'';
        switch($input['periodicity'])
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
        $query = "  SELECT count(mcu.ClientID) as count,DATE_FORMAT(u.AddDate,'$dateFormat'] as addedDate,c.OrganizationName FROM Clients c,MapClientUser mcu,Users u WHERE  c.ID = mcu.ClientID AND mcu.UserID = u.ID and u.isEnabled = '1'
                    and u.AddDate between DATE_FORMAT(STR_TO_DATE('{$input['mbrstartdate']}','%d %M, %Y'],'%Y-%m-%d']
                    AND DATE_FORMAT(STR_TO_DATE('{$input['mbrenddate']}','%d %M, %Y'],'%Y-%m-%d'] AND c.OrganizationName !='' $condition GROUP BY addedDate,mcu.ClientID ORDER BY u.AddDate
                ";
        return $this->db->getRows($query);
    }

  /**
    * gets the count of institutes added in last week
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param
    * @return       integer
    *
    */

    function getLastWeekCount()
    {
        $query  = " SELECT count(mcu.ClientID) as count FROM Clients c,MapClientUser mcu,Users u WHERE  c.ID = mcu.ClientID AND mcu.UserID = u.ID and u.isEnabled = '1'
                    AND u.AddDate BETWEEN DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND CURDATE()
                ";
        $result = $this->db->getSingleRow($query);
        return $result['count'];
    }

  /**
    * gets the institution code list of the given institution and given user ID
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @param    integer $userID
    * @param    array   $input
    * @return   array
    *
    */

    public function promocodeList($clientID,$userID,$input = array())
    {
        $condition  = ($this->session->getValue('isIndUser') == 1)?" AND u.ID = $userID ":'-1';
        $pgnstart   = ($input['pgnstart'] >= 0 ) ? $input['pgnstart'] : '0' ;

        /*
        $query      = " SELECT SQL_CALC_FOUND_ROWS p.ID, p.PromoCode, CONCAT(u.FIrstName,' ',u.LastName) AS Name, p.AddDate, p.isEnabled AS status, p.StartDate, p.EndDate
                        FROM PromoCodes p, Users u
                        WHERE p.ClientID = $clientID AND p.UserID = u.ID AND u.isEnabled = '1' $condition
                        ORDER BY {$input['pgnob']} {$input['pgnot']}  LIMIT {$pgnstart},{$input['pgnstop']}
                      ";
        $result['RS']   = $this->db->getRows($query);
        $result['TC']   = $this->rowsCount();
        */
        $getPromoCodeList =  $this->db->executeStoreProcedure('PromoCodeList', array( $input['pgnob'], $input['pgnot'], $input['pgnstart'],$input['pgnstop'], $this->session->getValue('instID'), '-1', $condition ) );
        Site::myDebug('------promocodeList');
        Site::myDebug($getPromoCodeList);

        if ( ! empty ($getPromoCodeList['RS'])  && ! empty($getPromoCodeList['TC']) )
        {
                $result['RS']   = $getPromoCodeList['RS'];
                $result['TC']   = $getPromoCodeList['TC'];
        }
        
        return $result;
    }

  /**
    * sets the given institution codes as inactive
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    public function setPromocodeInactive(array $input)
    {
        global $DBCONFIG;

        if(trim($input['promoid']) != '')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query = "  UPDATE PromoCodes SET \"isEnabled\" = '0', \"ModDate\" = '{$this->currentDate()}' WHERE ID IN({$input['promoid']}) AND \"ClientID\" = {$this->session->getValue('instID')} AND \"UserID\" = {$this->session->getValue('userID')}  ";
            }
            else
            {
                $query = "  UPDATE PromoCodes SET isEnabled = '0', ModDate = '{$this->currentDate()}' WHERE ID IN({$input['promoid']}) AND ClientID = {$this->session->getValue('instID')} AND UserID = {$this->session->getValue('userID')}  ";
            }
            return $this->db->execute($query);
        }
    }

  /**
    * sets the dates of institution code
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    public function setPromocodeDates(array $input)
    {
        global $DBCONFIG;

        if(trim($input['record']) != '')
        {
            /*
            $query = "  UPDATE PromoCodes
                                SET StartDate = STR_TO_DATE('{$input['startDate']}','%m-%d-%Y'), EndDate = STR_TO_DATE('{$input['endDate']}','%m-%d-%Y') WHERE ID = {$input['record']} AND ClientID = {$this->session->getValue('instID')} AND UserID = {$this->session->getValue('userID')} limit 1 ";
             */
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query = "  UPDATE PromoCodes
					SET \"StartDate\" = '".$this->getFormatDate(str_replace('-','/',$input['startDate']))."',
						\"EndDate\" = '".$this->getFormatDate(str_replace('-','/',$input['endDate'])). "'
					 WHERE ID = {$input['record']} AND \"ClientID\" = {$this->session->getValue('instID')} AND \"UserID\" = {$this->session->getValue('userID')}  ";
            }
            else
            {
                $query = "  UPDATE PromoCodes
					SET StartDate = '".$this->getFormatDate(str_replace('-','/',$input['startDate']))."',
						EndDate = '".$this->getFormatDate(str_replace('-','/',$input['endDate'])). "'
					 WHERE ID = {$input['record']} AND ClientID = {$this->session->getValue('instID')} AND UserID = {$this->session->getValue('userID')}  ";
            }
            return $this->db->execute($query);
        }
    }

  /**
    * resets the password of the given user
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        integer $userID
    * @param        string  $activationLink
    * @return       array
    *
    */

    function resetPassword($userID,$activationLink)
    {
        $userList = $this->db->executeStoreProcedure('ResetPassword', array($this->generatePassword(10),$userID,$activationLink,$this->currentDate()));
        if(array_key_exists('totalcount',(array)$userList))
        {
            $this->registry->template->userCount = array_pop($userList);
        }
        return $userList;
    }

  /**
    * updates the user visited details
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        integer $userID
    * @return       boolean
    *
    */

    function userVisited($userID)
    {
        global $DBCONFIG;
        $date   = date('U');
        $query  = "  UPDATE {$DBCONFIG->prefix}passactivationdetails SET hasVisited='Y',VisitDate='$date' WHERE UserID='$userID'";
        return $this->db->execute($query);
    }

  /**
    * gets the password activation details of the given user
    *
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @deprecated
    * @param        integer $userID
    * @return       array
    *
    */

    function getPassActivationDetails($userID)
    {
         global $DBCONFIG;
         $query = " SELECT * FROM {$DBCONFIG->prefix}passactivationdetails WHERE UserID='$userID' ";
         return $this->db->getSingleRow($query);
    }

  /**
    * changes the user password
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @param    string  $password
    * @return   boolean
    *
    */

    function changePassword($userID,$password)
    {
        global $DBCONFIG;
        $userDetails = array($userID,Site::getSecureString($password),'-1','-1',$DBCONFIG->quadliteDB);
        $this->db->storeProcedureManage('ManagePassword', $userDetails);
        return true;
    }

  /**
    * gets the users list of the given role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $roleID
    * @return   string
    *
    */

    function roleUsers($roleID)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
             $query      = " SELECT u.ID, ur.ID AS \"RoleID\", ur.\"RoleName\", ur.\"Count\" FROM Users u, UserRoles ur, MapClientUser mcu, MapUserRole mur WHERE ur.ID IN($roleID)
                        AND ur.ID = mur.\"RoleID\" AND mur.\"MapClientUserID\" = mcu.ID AND mcu.\"UserID\" = u.ID AND u.\"isEnabled\" = '1' AND mur.\"isEnabled\" = '1' AND mcu.\"isEnabled\" = '1' ";
        }
        else
        {
            $query      = " SELECT u.ID, ur.ID AS RoleID, ur.RoleName, ur.Count FROM Users u, UserRoles ur, MapClientUser mcu, MapUserRole mur WHERE ur.ID IN($roleID)
                        AND ur.ID = mur.RoleID AND mur.MapClientUserID = mcu.ID AND mcu.UserID = u.ID AND u.isEnabled = '1' AND mur.isEnabled = '1' AND mcu.isEnabled = '1' ";
        }
        $users      = $this->db->getRows($query);
        $ids        = '';

        if(!empty ($users))
        {
            foreach($users as $user)
            {
                $ids .= $user['ID'].',';
            }
            $ids = substr_replace($ids,'',-1,1);
        }
        return $ids;
    }

  /**
    * moves the users of the given role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $roleID
    * @return   boolean
    *
    */

    function moveUsers($roleID)
    {
        $clientID   = $this->session->getValue('instID');
        $userID     = $this->roleUsers($roleID);

        if($userID != '')
        {
            $this->updateUserRole($userID,$this->getDefaultRoleID($clientID));
        }

        return true;
    }

  /**
    * retrieves the security question
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $queryString
    * @return   mixed
    *
    */

    public function retrieveQuestion($queryString)
    {
        global $DBCONFIG;

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            if($this->verifyEmail($queryString," \"isEnabled\" ='1' "))
            {
                $condition = " \"PrimaryEmailID\" ='$queryString'";
            }
            else if($this->verifyUserName($queryString," \"isEnabled\" ='1'"))
            {
                $condition = " \"UserName\" ='$queryString'";
            }
            $query      = "SELECT usr.\"ID\" AS \"userID\", dbms_lob.substr(usr.\"SecurityQuestion\", 32000,1) AS \"SecurityQuestion\" ,
                                    mcu.\"isVerified\", mcu.\"isApproved\", usr.\"isEnabled\"
                                FROM MapClientUser mcu
                                INNER JOIN Users usr ON usr.\"ID\" = mcu.\"UserID\" WHERE $condition AND  mcu.\"isEnabled\" = 1  AND usr.\"isEnabled\" = 1 ";
        }
        else
        {
            if($this->verifyEmail($queryString, "isEnabled = '1' "))
            {
                $condition = " PrimaryEmailID  ='$queryString'";
            }
            else if($this->verifyUserName($queryString," isEnabled ='1' "))
            {
                $condition = " UserName ='$queryString'";
            }
            $query      = "SELECT usr.ID AS userID,usr.SecurityQuestion,mcu.isVerified,mcu.isApproved,usr.isEnabled FROM MapClientUser mcu
                                INNER JOIN Users usr ON usr.ID = mcu.UserID WHERE $condition AND  mcu.isEnabled ='1' AND usr.isEnabled ='1' ";

        }
        
        $result     = $this->db->getSingleRow($query);
        if(empty($result))
        {
            return false;
        }
        else
        {
            return $result;
        }
    }

  /**
    * verifies security information
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $question
    * @param    string  $answer
    * @param    string  $question
    * @return   boolean
    *
    */

    public function verifySecurityInfo($question='',$answer='',$condition='')
    {
        global $DBCONFIG;
        if($answer != ''){
            $answer = Site::getSecureString($answer);
        }
        $condition  = ($condition != '')?' and '.$condition:'';

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "SELECT * FROM Users WHERE  to_char(\"SecurityQuestion\")  = '$question'
                                    AND to_char(\"SecurityAnswer\")  = '$answer' $condition ";
        }
        else
        {
            $query      = "SELECT * FROM Users WHERE SecurityQuestion = '$question' AND SecurityAnswer = '$answer' $condition ";
        }
        return ($this->db->getCount($query) > 0)?true:false;
    }

  /**
    * removes the admin users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @return   array
    *
    */

    public function removeAdmin($userID)
    {
        global $DBCONFIG;

        if(!empty($userID))
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query      = "  select u.ID from Users u inner join MapClientUser mcu on  u.ID= mcu.\"UserID\" INNER join  MapUserRole mu on  mcu.ID= mu.\"MapClientUserID\" INNER JOIN  UserRoles
                             ur ON mu.\"roleID\"=ur.ID where u.ID IN($userID) AND ur.\"isAdmin\" = 'N' AND mcu.\"isEnabled\" = '1' AND mu.\"isEnabled\" = '1' ";
            }
            else
            {
                $query      = "  select u.ID from Users u inner join MapClientUser mcu on  u.ID= mcu.UserID INNER join  MapUserRole mu on  mcu.ID= mu.MapClientUserID INNER JOIN  UserRoles
                             ur ON mu.roleID=ur.ID where u.ID IN($userID) AND ur.isAdmin = 'N' AND mcu.isEnabled = '1' AND mu.isEnabled = '1' ";
            }
            $userIDs    = $this->db->getRows($query);
            return $this->getValueArray($userIDs, 'ID', 'multiple');
        }
    }

  /**
    * uploads the user image
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $type
    * @param    string  $username
    * @param    integer $id
    * @return   string
    *
    */

    public function uploadPhoto($type,$username,$id='')
    {
        $msg = '';
        $fileElementName = 'userimage';
        $error = $this->validateUpload($fileElementName);

        if($error == '')
        {
            $image = new SimpleImage();

            if($type != 'upload')
            {
                $imageName = substr_replace($this->fileParam($fileElementName, 'name'), $username.'_'.uniqid(),0, strrpos($this->fileParam($fileElementName, 'name'), '.'));
                list($width, $height, $type, $attr) = getimagesize($this->fileParam($fileElementName, 'tmp_name'));

                $flag = 0;
                if($width < $this->cfgApp->cropMinWidth)
                {
                    $error = "Minimum width should be {$this->cfgApp->cropMinWidth}";
                    $flag = 1;
                }
                elseif($height < $this->cfgApp->cropMinHeight)
                {
                    $error = "Minimum height should be {$this->cfgApp->cropMinHeight}";
                    $flag = 1;
                }

                if($flag == 0)
                {
                    $image->load($this->fileParam($fileElementName, 'tmp_name'));

                    if($height>$this->cfgApp->imgHeightLimit)
                    {
                        $image->resizeToHeight($this->cfgApp->imgHeightLimit);
                    }                    
                    $imgRootTempPath = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserTempThumbImg;                    
                    ///$image->save($this->cfg->rootPath.'/data/media/users/users_tmp/'.$imageName);
                    $image->save($imgRootTempPath.$imageName);
                }
            }

            $msg .= $imageName ;
        }

        return  "{error: '" . $error . "',\n msg: '" . $msg . "'\n }";
    }

  /**
    * uploads the institute logo
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $type
    * @return   string
    *
    */

    public function uploadLogo($type)
    {
        $msg = '';
        $fileElementName = 'logoimage';
        $error = $this->validateUpload($fileElementName);

        if($error == '')
        {
            $image = new SimpleImage();

            if($type != 'upload')
            {
                $imageName  = substr_replace($this->fileParam($fileElementName, 'name'), $this->session->getValue('instName').'_temp_'.uniqid(),0, strrpos($this->fileParam($fileElementName, 'name'), '.'));
                $imageName  = str_replace(' ','_',$imageName);
                list($width, $height, $type, $attr) = getimagesize($this->fileParam($fileElementName, 'tmp_name'));

                $flag = 0;
                if($width != 300)
                {
                    $error = "Width should be 300";
                    $flag = 1;
                }
                elseif($height != 90)
                {
                    $error = "Height should be 90";
                    $flag = 1;
                }

                if($flag == 0)
                {                    
                    $logoPath = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserLogo;                    
                    if(!is_dir($logoPath))
                    {
                        mkdir($logoPath,0777);
                    }                   
                    move_uploaded_file($this->fileParam($fileElementName, 'tmp_name'), $logoPath.$imageName);                    
                }
            }

            $msg .= $imageName ;
        }

        return  "{error: '" . $error . "',\n msg: '" . $msg . "'\n }";
    }

  /**
    * saves use users crop image
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    function saveCropImage(array $input)
    {
        global $DBCONFIG;
        if(strstr($input['UserImage'], 'no-user-image'))
        {
            return true;
        }
        $imgRootPath = $this->cfg->rootPath."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserThumbImg;
        $thumbImgRootPath = $this->cfg->rootPath."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserThumbMiniImg;
        $imgWebPath = $this->cfg->wwwroot."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserThumbImg;
        $imgRootTempPath = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserTempThumbImg;
        $thumbImgRootTempPath = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserThumbImg;
        

        $image = new SimpleImage();
        $targ_w = $this->cfgApp->cropWidth;
        $targ_h = $this->cfgApp->cropHeight;

        $imageName = substr_replace($input['UserImage'], $input['UserName'],0, strrpos($input['UserImage'], '.'));
        ///$imgRootPath = $this->cfg->rootPath.'/data/media/users/thumb/'.$imageName;
        ///list($width, $height, $type, $attr) = getimagesize($imgRootPath);      
        list($width, $height, $type, $attr) = getimagesize($imgRootTempPath.$input['UserImageName']);

        ///$thumbpath=$this->cfg->wwwroot.'/data/media/users/thumb/';
        $thumbpath = $imgWebPath;
        ///if(file_exists($this->cfg->rootPath.'/data/media/users/users_tmp/'.$input['UserImageName']))
        if(file_exists($imgRootTempPath.$input['UserImageName']))
        {
            ///$image->load( $this->cfg->rootPath.'/data/media/users/users_tmp/'.$input['UserImageName']);
            $image->load( $imgRootTempPath.$input['UserImageName']);          
        }
        else
        {
            ///$image->load($this->cfg->rootPath.'/data/media/users/thumb/'.$imageName);
            $image->load($imgRootPath.$imageName);            
        }

        $crop = array(
                'x' => $input['x'],
                'y' => $input['y'],
                'w' => $input['w'],
                'h' => $input['h']
                );

        if($width > $this->cfgApp->cropMinWidth && $height > $this->cfgApp->cropMinHeight)
        {
            if(intval($input['w']) > 0)
            {
                $flag = 0;
                $image->resize($input['w'], $input['h'],$crop);
            }
            elseif(($width > $this->cfgApp->cropWidth &&  $height < $this->cfgApp->cropHeight &&  $input['w']=="" ) ||($width < $this->cfgApp->cropWidth &&  $height > $this->cfgApp->cropHeight &&  $input['h']=="" )){
                $msg = "{msg: 'Please Crop Uploaded Image.'}";
                return $msg;
            }
            else
            {
                $flag = 1;
                if($width > $this->cfgApp->cropWidth && $height > $this->cfgApp->cropHeight)
                {
                    $image->resize($this->cfgApp->cropWidth, $this->cfgApp->cropHeight);
                }
                elseif(($width/$height) > $this->cfgApp->aspectRatio)
                {
                    $image->resizeToWidth($this->cfgApp->cropWidth);
                }
            }
        }
        elseif(($width/$height) > $this->cfgApp->aspectRatio)
        {
            if(intval($input['w']) > 0)
            {
                $flag = 0;
                $image->resize($input['w'], $input['h'],$crop);
            }
            else
            {
                $flag = 1;
                if($width > $this->cfgApp->cropWidth && $height > $this->cfgApp->cropHeight)
                {
                    $image->resize($this->cfgApp->cropWidth, $this->cfgApp->cropHeight);
                }
                else
                {
                    $image->resizeToWidth($this->cfgApp->cropWidth);
                }
            }
        }
        ///$image->save($this->cfg->rootPath.'/data/media/users/thumb/'.$imageName);
        $image->save($imgRootPath.$imageName);


        $crop = array(
                    'x' => 0,
                    'y' => 0,
                    'w' => $this->cfgApp->thumbDimension,
                    'h' => $this->cfgApp->thumbDimension
                    );
        if(($height/$width) > $this->cfgApp->aspectRatio && $flag == 1 )
        {
            ///$image->load($this->cfg->rootPath.'/data/media/users/thumb/'.$imageName);
            $image->load($imgRootPath.$imageName);
            $image->resize($this->cfgApp->thumbDimension, $this->cfgApp->thumbDimension,$crop);
        }
        elseif(($width/$height) > $this->cfgApp->aspectRatio && $flag == 1)
        {
            //$image->load( $this->cfg->rootPath.'/data/media/users/users_tmp/'.$input['UserImageName']);
            $image->load( $imgRootTempPath.$input['UserImageName']);
            $image->resize($this->cfgApp->thumbDimension, $this->cfgApp->thumbDimension,$crop);
        }
        else
        {
            ///$image->load($this->cfg->rootPath.'/data/media/users/thumb/'.$imageName);
            $image->load($imgRootPath.$imageName);
            $image->resizeToWidth($this->cfgApp->thumbDimension);
        }
        ///$image->save($this->cfg->rootPath.'/data/media/users/thumb_mini/'.$imageName);
        $image->save($thumbImgRootPath.$imageName);

        ///$temppath = $this->cfg->rootPath.'/data/media/users/users_tmp/';
        $temppath = $imgRootTempPath;
        $this->removeTempFiles($temppath, $input['UserName']);

        ///$temppath = $this->cfg->rootPath.'/data/media/users/thumb_tmp/';
        $temppath = $thumbImgRootTempPath;
        $this->removeTempFiles($temppath, $input['UserName']);

       /* $temppath = $this->cfg->rootPath.'/data/media/users/';
        $this->removeTempFiles($temppath, $input['UserName']);*/

        if(intval($input['UserID']) > 0)
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            {
                $query = "UPDATE Users SET \"Image\" = '$imageName' WHERE ID={$input['UserID']} ";
            }
            else
            {
                $query = "UPDATE Users SET Image='$imageName' WHERE ID={$input['UserID']} LIMIT 1 ";
            }
            $this->db->execute($query);
        }

        $status = "{";
        $status .= "'imgname':'$imageName',";
        $status .= "'imgpath':'$thumbpath'";
        $status .= "}";
        return $status;
    }

  /**
    * saves the institute logo
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    function saveLogoImage(array $input)
    {
        if(strstr($input['UserImage'], 'logo.jpg'))
        {
            return true;
        }

        $image  = new SimpleImage();
        $targ_w = 300;
        $targ_h = 90;

        //$imageName = substr_replace($input['UserImage'], $input['UserName'],0, strrpos($input['UserImage'], '.'));
        $imageName = str_replace('_temp_',"_{$this->session->getValue('instID')}",$input['UserImageName']);        
        $logoRootPath = $this->cfg->rootPath."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserLogo;
        $logoRootTempPath = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserLogo;
        $logoWebPath = $this->cfg->wwwroot."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserLogo;
        //$imgRootPath =$this->cfg->rootPath.'/data/media/'.$this->session->getValue('instID').'/logo/'.$input['UserImageName'];
        //$imgRootPath =$logoRootTempPath.$input['UserImageName'];
        //list($width, $height, $type, $attr) = getimagesize($imgRootPath);


        if(file_exists($logoRootTempPath.$input['UserImageName']))
        {
            if(copy($logoRootTempPath.$input['UserImageName'],$logoRootPath.$imageName))
                unlink($logoRootTempPath.$input['UserImageName']);
        }
        else
        {
            if(copy($logoRootTempPath.$imageName,$logoRootPath.$imageName))
                unlink($logoRootTempPath.$imageName);
        }
        $temppath = $logoRootPath;
        $this->removeInstituteFiles($temppath, $imageName);
        $this->deleteCachedPages($this->session->getValue('roleID'));
        $imgpath=$logoWebPath.$imageName;
        $status = "{";
        $status .= "'imgpath':'$imgpath'";
        $status .= "}";
        return $status;
    }

  /**
    * resets institute logo to the default one
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    function resetLogoImage(array $input)
    {
        //$logopath=$this->cfg->rootPath.'/data/media/'.$this->session->getValue('instID').'/logo/';
        $logopath=$this->cfg->rootPath."/".$this->cfgApp->PersistDataPath.$this->session->getValue('instID').'/'.$this->cfgApp->UserLogo;         
        $this->rmDirRecurse($logopath);
        $this->deleteCachedPages($this->session->getValue('roleID'));
        $logoPath=$this->getLogoUrl($this->session->getValue('institutelogoinstid'));        
        $status = "{";
        $status .= "'logopath':'$logoPath'";
        $status .= "}";
        return $status;
//        return true;
    }

  /**
    * removes the temporary user uploaded files
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $temppath
    * @param    string  $username
    * @return   void
    *
    */

    function removeTempFiles($temppath,$username)
    {
        if(is_dir($temppath))
        {
            $handle = opendir($temppath);
            for (;false !== ($file = readdir($handle));)
            {
                if($file != "." && $file != ".." && $file != 'vssver.scc')
                {
                    //if(is_file($file))
                    {
                        if(strstr($file, $username))
                        {
                            $fullpath= $temppath.$file;
                            unlink($fullpath);
                        }
                    }
                }
            }
            closedir($handle);
        }
    }

  /**
    * removes the temporary institute uploaded files
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $temppath
    * @param    string  $filename
    * @return   array
    *
    */

    function removeInstituteFiles($temppath,$filename='')
    {
        if(is_dir($temppath))
        {
            $handle = opendir($temppath);
            for (;false !== ($file = readdir($handle));)
            {
                if($file != "." && $file != ".." && $file != 'vssver.scc')
                {
                    if(trim($filename) == '')
                    {
                        if(strstr($file, '_temp_'))
                        {
                            $fullpath= $temppath.$file;
                            unlink($fullpath);
                        }
                    }
                    elseif(!strstr($file, $filename))
                    {
                        $fullpath= $temppath.$file;
                        unlink($fullpath);
                    }
                }
            }
            closedir($handle);
        }
    }

  /**
    * saves the thumb image
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $imageName
    * @return   void
    *
    */

    function thumbImage($imageName)
    {
        $image = new SimpleImage();
        $image->load($this->cfg->rootPath.'/data/media/users/thumb/'.$imageName);
        $image->resizeToWidth($this->cfgApp->thumbDimension);
        $image->save($this->cfg->rootPath.'/data/media/users/thumb_tmp/'.$imageName);
    }

  /**
    * validates the uploaded file
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $fileElementName
    * @return   string
    *
    */

    public function validateUpload($fileElementName)
    {

        $error      = '';
        $fileName   = basename($this->fileParam($fileElementName, 'name'));
        $extensions = $this->cfgApp->imgFormats;

        $file_info  = getimagesize($this->fileParam($fileElementName, 'tmp_name'));
        $file_mime  = $file_info['mime'];

        $error_type = $this->fileParam($fileElementName, 'error');
        $tmp_name   = $this->fileParam($fileElementName, 'tmp_name');

        if(!empty($error_type))
        {
            switch($error_type)
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
                case '999':
                default:
                    $error = 'No error code avaiable';
            }
        }
        elseif(empty($tmp_name) || $tmp_name == 'none')
        {
            $error = 'No file was uploaded..';
        }
        elseif(!$this->filterFileName($fileName,$this->cfgApp->uploadUserImgFormats) || ($file_mime == ""))
        {
            $error = 'Please upload allowed images only.';
        }

        return $error;
    }

  /**
    * completes user profile with security information
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   array
    *
    */

    public function changeProfile(array $input)
    {
        global $DBCONFIG;
        $userID = $this->session->getValue('userID');

        if(($input['Password'] != ''))
        {
            $userDetails = array($userID,$input['Password'],$input['SecurityQuestion'],$input['SecurityAnswer'],$DBCONFIG->quadliteDB);
        }
        else
        {
             $userDetails = array($userID,'-1',$input['SecurityQuestion'],$input['SecurityAnswer'],$DBCONFIG->quadliteDB);
        }
        $this->db->storeProcedureManage('ManagePassword', $userDetails);
        return true;
    }

  /**
    * creates the array of user details
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @param    integer $userID
    * @return   array
    *
    */

    public function getUserArr($input,$userID)
    {
        global $DBCONFIG;
        $Everifyverify=($input['EmailVerifyType']== 1) ? "N":"Y";
        if($userID == 0 && ($this->isVarSet("EmailVerifyType"))){
            if($input['EmailVerifyType'] ==1){
                $input['UserName']='';
                $input['Password']='';
                $input['SecurityQuestion']='';
                $input['SecurityAnswer']='';
            }
            else
            {
                list($input['FirstName'],$input['LastName']) =  explode(' ', $input['FullName']);
            }
        }
        return array(
                        $userID,$input['Title'],
                        str_replace("'", "\'", ucfirst($input['FirstName'])),
                        str_replace("'", "\'",$input['MiddleName']),
                        str_replace("'", "\'", ucfirst($input['LastName'])),
                        $input['UserName'],1,$input['Password'],$input['PrimaryEmailID'],
                        $input['SecondaryEmailID'],$this->formattedDate($input['BirthDate']),$input['Gender'],
                        str_replace("'", "\'",$input['Address']),
                        $input['City'],$input['State'],$input['Country'],$input['Zipcode'],
                        $input['Phone'],$input['Fax'],$input['CellPhone'],$input['LanguageID'],
                        $this->session->getValue('userID'),$this->currentDate(),
                        $this->session->getValue('userID'),$this->currentDate(),
                        '','',0,$Everifyverify,'Y','1','N',$this->session->getValue('instID'),
                        $input['RoleID'],'Y','N','-1','-1','-1',$DBCONFIG->quadliteDB,
                        str_replace("'", "\'",$input['SecurityQuestion']),
                        str_replace("'", "\'",$input['SecurityAnswer']),
                        $userID    // $userID added by Saikat as the SP `UserManage` requires an additional param //
        );
    }

  /**
    * changes the users role
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $userIDs
    * @param    integer $roleID
    * @return   boolean
    *
    */

    public function changeUserRole($userIDs,$roleID)
    {
        global $DBCONFIG;

        $userIdStr  = implode(',',(array)$this->removeBlankElements($userIDs));
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "  UPDATE MapUserRole mur, MapClientUser mcu
                                    SET mur.\"RoleID\" = {$roleID}, mur.\"ModBY\" ={$this->session->getValue('userID')}, mur.\"ModDate\" = '{$this->currentDate()}'
                                       WHERE mcu.\"UserID\" IN ({$userIdStr}) AND mcu.ID = mur.\"MapClientUserID\" AND mcu.\"ClientID\"='{$this->session->getValue('instID')}' ";
                                       
            $updateUserRole = $this->db->executeStoreProcedure('UpdateUserRoleCount', array('ROLECHANGE', $this->session->getValue('instID'), $userIdStr, $roleID ) );
                                       
        }
        else
        {
            $query      = "  UPDATE MapUserRole mur, MapClientUser mcu SET mur.RoleID = {$roleID}, mur.ModBY={$this->session->getValue('userID')},mur.ModDate='{$this->currentDate()}' WHERE mcu.UserID IN({$userIdStr}) AND mcu.ID = mur.MapClientUserID AND mcu.ClientID='{$this->session->getValue('instID')}'";
        }
        return $this->db->execute($query);
    }

  /**
    * generates the institution code
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $name
    * @return   string
    *
    */

    public function generatePromoCode($name)
    {
        $prefix     = strtoupper(substr($name,0,3));
        $promoCode  = $this->generateUniqID(5,$prefix);

        if($this->isPromoCodeExist($promoCode)) { $this->generatePromoCode($name); }
        else{ return $promoCode; }
    }

  /**
    * verifies the captcha
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    function verifyCaptcha(array $input)
    {
		global $DBCONFIG;
        require_once $this->cfg->rootPath.'/plugins/captcha/securimage.php';
		$captcha_options = array('database_host'   => $DBCONFIG->dbHost, 
			 'database_user' => $DBCONFIG->dbUser,
			 'database_pass' => $DBCONFIG->dbPass,
			 'database_name' => $DBCONFIG->dbName
		);
        $img = new Securimage($captcha_options);
        $val = $img->check($input['code']);
        $this->session->load('validcaptcha', $val);
        return $val;
    }

  /**
    * updates the default client of the multiple user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   mixed
    *
    */

    function updateDefaultClient(array $input)
    {
        $query  = "  UPDATE Users SET DefaultClientID = {$input['instid']} WHERE ID = {$this->session->getValue('userID')} AND isEnabled = '1' ";
        if($this->db->execute($query) == true)
        {
            $this->loadMultiInstSession();
        }
        else
        {
            return false;
        }
        return true;
    }

  /**
    * sets the default client of the multiple user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input
    * @return   boolean
    *
    */

    function setDefaultClient(array $input)
    {
        if($input['nottoask'] == 1)
        {
            $condition = ' isDefaultOpted="Y" ';
        }
        else
        {
            $condition = '';
        }
        $userID = ($this->session->getValue('userID') > 0)?$this->session->getValue('userID'):$this->session->getValue('tempuserID');
        $query  = '';
        if($input['option'] == 'savedefaultlogin')
        {
            if($input['cid'] > 0)
            {
                $condition  = ($condition != '')?" , $condition ":'';
                $query      = " UPDATE Users SET DefaultClientID = {$input['cid']} $condition WHERE ID = {$userID} AND isEnabled = '1' ";
            }
        }
        else if($input['nottoask'] == 1)
        {
            $query  = " UPDATE Users SET $condition WHERE ID = {$userID} AND isEnabled = '1' ";
        }

        if($query != '')
        {
            if($this->db->execute($query) != true)
            {
                return false;
            }
        }

        return true;
    }

  /**
    * adds the new quad plus
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    array   $input
    * @param    integer $clientID
    * @return   mixed
    *
    */

    public function registerQuadPlus(array $input,$clientID=0)
    {
        global $DBCONFIG;

        $index = new Index();


        $clientID           = ($clientID>0)?$clientID:0;
        $officialEmailCond  = ($clientID > 0)?" AND c.ID<>$clientID ":'';
        $contactEmailCond   = ($clientID > 0)?" AND c.ID<>$clientID ":'';

        if(trim($input['OfficialEmailID']) != '')
        {
            if($this->verifyOfficialQLEmail($input['OfficialEmailID'], $officialEmailCond))
            {
                return OFFICIALEMAILEXISTS;
            }
        }

        if( $input['AdminUserName'] && $this->verifyQLUserName($input['AdminUserName']))
        {
            return USERNAMEEXISTS;
        }

        if($this->verifyQLEmail($input['ContactEmailID'])  && $clientID == 0)
        {
            return CONTACTEMAILEXISTS;
        }

        $organization       = str_replace(' ', '', $input['OrganizationName']);
        $contactPerson      = str_replace(' ', '', $input['ContactPerson']);
        $userName           = $input['AdminUserName'];
        $password           = $input['AdminPassword'];
        $promocode          = $this->generatePromoCode(trim(str_replace(' ','',$input['OrganizationName'])));
        $token              = $this->generateToken();
        $isQuadLite         = 'Y';
        $isDefaultQuadPlus  = 'N';

        $clientDetails      = array(0, $input['mapID'], $this->session->getValue('instID'), $this->session->getValue('userID'),
                                    $input['clientID'], $input['userID'],
                                    str_replace("'", "\'", $input['OrganizationName']), str_replace("'", "\'", $input['OrganizationInfo']), 1,
                                    1, str_replace("'", "\'", $input['Address']), $input['City'],
                                    $input['State'], $input['Country'], $input['Zipcode'],
                                    $input['Phone'], $input['Fax'],  $input['OfficialEmailID'],
                                    $input['Website'], str_replace("'", "\'",ucwords($input['ContactPerson'])), 1,
                                    $input['ContactEmailID'],	$input['AlternateEmailID'], $input['ContactPhone'],
                                    $this->session->getValue('userID'),	$this->currentDate(), 'Y',
                                    'Y','1', $isDefaultQuadPlus,
                                    $this->getDefaultRoleID($this->getDefaultClientID()), $userName, $password,
                                    $token, intval($input['itemID']),	'',
                                    '','', $promocode,
                                    $isQuadLite,  $DBCONFIG->quadliteDB, str_replace("'", "\'", $input['SecurityQuestion']),
                                    str_replace("'", "\'", $input['SecurityAnswer']) );
        $this->db->executeStoreProcedure('MultipleQuadPlusManage', $clientDetails);

        // Delete all Sidepanel cache
        $this->deleteCachedPages($this->session->getValue('roleID'));

        return true;
    }

  /**
    * verifies the official quad plus email
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    string  $email
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyOfficialQLEmail($email,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';
        $query      = "SELECT * FROM {$DBCONFIG->quadliteDB}.Clients c WHERE OfficialEmailID='$email' $condition ";
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies the quad plus username
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global   $DBCONFIG
    * @param    string  $userName
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyQLUserName($userName,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';
        $query      = "SELECT * FROM {$DBCONFIG->quadliteDB}.Users WHERE UserName='$userName' $condition ";
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies the user quad plus email
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @param    string  $condition
    * @return   boolean
    *
    */

    public function verifyQLEmail($email,$condition='')
    {
        global $DBCONFIG;
        $condition  = ($condition != '')?' and '.$condition:'';
        $query      = "SELECT * FROM {$DBCONFIG->quadliteDB}.Users WHERE PrimaryEmailID='$email' $condition ";
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * deletes the given quad plus
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $QPID
    * @return   boolean
    *
    */

    public function delQuadPlus($QPID)
    {
        global $DBCONFIG;
        $query  = "SELECT * FROM QuadPlusInfo WHERE ID=$QPID ";
        $data   = $this->db->getSingleRow($query);

        if(!empty($data))
        {
            $arrDetails = array($QPID, $data['QPClientID'], $data['QPUserID'], $this->session->getValue('userID'), $this->currentDate(), $DBCONFIG->quadliteDB );
            $this->db->executeStoreProcedure('DeleteMultipleQuadPlusManage', $arrDetails,  'nocount');
            $this->deleteCachedPages($this->session->getValue('roleID'));
            return true;
        }
        else
        {
             return false;
        }
    }

  /**
    * sets the default quad plus
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $QPID
    * @return   boolean
    *
    */

    public function defaultQuadPlus($QPID)
    {
        $query  = "SELECT * FROM QuadPlusInfo WHERE ID=$QPID ";
        $data   = $this->db->getSingleRow($query);

        if( ! empty($data) )
        {
            $arrDetails  = array($QPID, $this->session->getValue('userID') );
            $this->db->executeStoreProcedure('MakeDefaultQuadPlus', $arrDetails, 'nocount');
            $this->deleteCachedPages($this->session->getValue('roleID'));
            return true;
        }
        else
        {
             return false;
        }
    }

  /**
    * gets the quad plus client information of the given client
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $clientID
    * @return   array
    *
    */

    public function getQuadPlusClientInfo($clientID)
    {
        global $DBCONFIG;
        $data       = array();
        $dataResult = $this->db->executeStoreProcedure('QuadPlusClientDetails', array($clientID, $DBCONFIG->quadliteDB), 'nocount');

        if ( ! empty($dataResult) )  $data = $dataResult['0'];
        return $data;
    }

  /**
    * gets the quad plus user information of the given user
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $userID
    * @return   array
    *
    */

    public function getQuadPlusUserInfo($userID)
    {
        global $DBCONFIG;
        $data       = array();
        $dataResult = $this->db->executeStoreProcedure('QuadPlusUserDetails', array($userID, $DBCONFIG->quadliteDB), 'nocount');

        if ( ! empty($dataResult) ) $data = $dataResult['0'];
        return $data;
    }

  /**
    * gets the token of the given quad plus
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    integer $qpID
    * @return   string
    *
    */

    public function getQpTokenByQpID($qpID)
    {
        $query      = " SELECT Token FROM QPTokens as qpt LEFT JOIN MapQuadQuadPlus as mqp ON mqp.ID  = qpt.mapID WHERE mqp.QuadPlusID='{$qpID}' ";
        $qpToken    = $this->db->getSingleRow($query);
        return $qpToken['Token'];
    }

  /**
    * checks the institute url avaibility
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $urlcode
    * @return   boolean
    *
    */

    public function checkUrlAvaibility($urlcode)
    {
        global $DBCONFIG;

         if ( $DBCONFIG->dbType == 'Oracle' )
        $query      = "SELECT ID FROM Clients WHERE \"OrganizationUrl\"='$urlcode' AND ID != '{$this->session->getValue('instID')}' ";
        else
        $query      = "SELECT ID FROM Clients WHERE OrganizationUrl='$urlcode' AND ID != '{$this->session->getValue('instID')}' ";
        return ($this->db->getCount($query) == 1)?true:false;
    }

  /**
    * verifies the institute for the given code
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $urlcode
    * @return   integer
    *
    */

    public function verifyInstituteLogo($urlcode)
    {
//        $query      = "SELECT ID FROM Clients WHERE OrganizationUrl='$urlcode' ";
//        $institute  = $this->db->getSingleRow($query);
        return $this->db->executeFunction('verifyInstituteLogo','', array($urlcode));
    }

  /**
    * gets the email verification flag of the given email
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    string  $email
    * @return   integer
    *
    */

    public function verifyEmailExist($email)
    {
        if($this->verifyEmail($email,"isEnabled = 1"))
        {
            $userInfo       = $this->getInstInfoByEmail($email);
            $userID         = $userInfo[0]['userID'];
            $clients        = $this->getValueArray($userInfo,'instID', 'multiple', 'array');
            $clients        = array_keys($clients);
            $clients        = $this->removeBlankElements($clients);

            if(in_array($this->session->getValue('instID'),$clients))
            {
                $val    = 2;
            }
            else
            {
                $val    = 3;
            }
        }
        else
        {
            $val    = 1;
        }
        return $val;
    }

    public function downloadExcel($input)
    {
        Site::myDEbug("-----downloadExcel");
        Site::myDEbug($input);
        $fileExtType = $input['downloadFileType'];
        if ( $fileExtType == 'excelold' )
        {
            $fileType = "Excel5";
        }
        else if ( $fileExtType == 'excelnew' )
        {
            $fileType = "Excel5";
        }
        else
        { // By default 
            $fileType = "Excel5";
        }
        
        $userUploadXmlPath = $this->cfg->rootPath.'/'.$this->cfgApp->PersistDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload."bulk_user_{$fileExtType}.xls";
       // if(!file_exists($userUploadXmlPath))
       // {
            $this->generateXls($fileType, $fileExtType);
       // }
        $this->downloadXls($fileType, $fileExtType);
    }

    public function generateXls($fileType="Excel5", $fileExtType="excelnew")
    {
            include_once $this->cfg->rootPath.'/plugins/excelclasses/PHPExcel.php';

            $objPHPExcel = new PHPExcel();
            $objPHPExcel->createSheet();
            $objPHPExcel->getProperties()->setCreator('LearningMate')
                                         ->setLastModifiedBy('LearningMate')
                                         ->setTitle('Bulk Institute User Creation')
                                         ->setSubject('Bulk Institute User Creation')
                                         ->setDescription('This document contains excel/scalc for bulk institute user creation');

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
           // $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
            //$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
            //$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
            //$objPHPExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);

            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'First Name');
            $objPHPExcel->getActiveSheet()->setCellValue('B1', 'Last Name');
            $objPHPExcel->getActiveSheet()->setCellValue('C1', 'User Role');
            $objPHPExcel->getActiveSheet()->setCellValue('D1', 'Primary Email');
            //$objPHPExcel->getActiveSheet()->setCellValue('D1', 'Username');
           // $objPHPExcel->getActiveSheet()->setCellValue('E1', 'Password');
            //$objPHPExcel->getActiveSheet()->setCellValue('G1', 'Security Question');
           // $objPHPExcel->getActiveSheet()->setCellValue('H1', 'Security Answer');

            $input['rolelisttype']  = 'all';
            $input['pgndc']         = '-1';
            $roleList               = $this->roleList($input);
            $roleList               = array_keys($this->getValueArray($roleList['RS'],'RoleName','multiple','array'));
            $count                  = count($roleList);
            Site::myDebug("----------roleList");
            Site::myDebug($count);
            

            $objPHPExcel->setActiveSheetIndex(1);
            $objPHPExcel->getActiveSheet()->setTitle('Roles');
            for($i=0;$i<$count;$i++)
            {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $i+1, "{$roleList[$i]}");
            }
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setVisible(false);

            $objPHPExcel->setActiveSheetIndex(0);

            $maxLimit   = 100;

            for($i=2;$i<$maxLimit;$i++)
            {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $i, "");

                $objValidation = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(2, $i)->getDataValidation();
                $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
                $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_INFORMATION );
                $objValidation->setAllowBlank(false);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setShowDropDown(true);
                $objValidation->setErrorTitle('Input error');
                $objValidation->setError('Value is not in list.');
                $objValidation->setPromptTitle('Pick from list');
                $objValidation->setPrompt('Please pick role from the drop-down list.');
                
                if ( $fileExtType == "excelnew") // For excel 2010 and Open Office 
                {
                    $objPHPExcel->setActiveSheetIndex(1)->insertNewRowBefore();
                    $objValidation->setFormula1('Roles!$A'.($maxLimit-$i).':$A'.($maxLimit+$count));
                }
                else
                {   // Office 2003 and 2007
                    $objValidation->setFormula1('Roles!$A1:$A'.($count+1));
                }                
                $objPHPExcel->setActiveSheetIndex(0);                //
                //$objValidation->setFormula1("Roles!\$A2:Roles!\$A".$count);	// Make sure to put the list items between " and "  !!!

                $objValidation = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(0, $i)->getDataValidation();
                $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_NONE );
                $objValidation->setType( PHPExcel_Cell_DataValidation::OPERATOR_EQUAL );
                $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
                $objValidation->setAllowBlank(false);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setErrorTitle('Input error');
                $objValidation->setError('Blank is not allowed!');
                $objValidation->setPromptTitle('Input');
                $objValidation->setPrompt('First name of a user.');
                //$objValidation->setFormula1('A2');

                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $i)->getDataValidation()->setPrompt('Last name of a user.');

                /*
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $i)->getDataValidation()->setPrompt('Username of a user.');

                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(4, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(4, $i)->getDataValidation()->setPrompt('Password of a user.');
                */
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $i)->getDataValidation()->setPrompt('Primary Email of a user.');
                /*
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(6, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(6, $i)->getDataValidation()->setPrompt('Security Question of a user.');

                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(7, $i)->setDataValidation(clone $objValidation);
                $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(7, $i)->getDataValidation()->setPrompt('Security Answer of a user.');
                */
            }

            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->getActiveSheet()->setTitle('Create Users');

            $userUploadXmlPath = $this->cfg->rootPath.'/'.$this->cfgApp->PersistDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload;
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $fileType);

            if(!is_dir($userUploadXmlPath))
            {
                mkdir($userUploadXmlPath, 0777);
            }
            $objWriter->save($userUploadXmlPath."bulk_user_{$fileExtType}.xls");
    }

    function downloadXls($fileType, $fileExtType)
    {
        $xlsFile = "bulk_user_{$fileExtType}.xls";
        $userUploadXmlPath = $this->cfgApp->PersistDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload;
        $urlToDownload = $this->cfg->wwwroot."/authoring/download/f:{$xlsFile}|path:{$userUploadXmlPath}|rand:".uniqid();
        print "{$urlToDownload}";
        
        // Authoring::download($xlsFile,$userUploadXmlPath);
       // Authoring::download($xlsFile,$this->cfgApp->EditorImagesUpload.$this->session->getValue('instID'));
    }

    
    /*
     * @author Manish<manish.kumar@learningmate.com>
     * @01-09-15
     * Bulk user Upload form excel file
     * @readerExcel 
     * @return Array (uploaded user list,...)
     */
    function readerExcel($fileName)
    {
        set_time_limit(0);
        try{
        include $this->cfg->rootPath.'/plugins/excelclasses/PHPExcel.php';
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);
        $userUploadXmlPath    = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload;
        $objPHPExcel    = $objReader->load($userUploadXmlPath.$fileName);
        $objWorksheet   = $objPHPExcel->getActiveSheet();
        $highestRow     = $objWorksheet->getHighestRow();
        $highestRow     = ($highestRow > 100)?100:$highestRow;
        $highestColumnIndex = 3;
        $refArr = array('FirstName','LastName','UserRole','PrimaryEmailID');

        $users  = array();
        for ($row = 2; $row <=$highestRow; ++$row)
        {
            $index = $row-2;
                for ($col = 0; $col <= $highestColumnIndex; ++$col)
                {
                    $val = $refArr[$col];
                    $users[$index][$val] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
        }
        if(!empty($users))
        {
            $this->users      = $users;
            $userNames      = "";
            $onlyEmailval=array_keys($this->getValueArray($users, 'PrimaryEmailID', 'multiple','array'));
            $emails         = "'".str_replace(',',"','",$this->getValueArray($users, 'PrimaryEmailID', 'multiple')."'");
            $existing       = $this->getExistingUsersNew($emails);
            $instExisting   = $this->getInstituteExistingUsersNew($emails);
            $this->existingUsers           = array();
            $this->otherInstExistingUsers  = array();
            $this->invalidUsers            = array();
            $this->sameUsers               = array();
            $this->invalidRoles          =array();

            $input['rolelisttype']  = 'all';
            $input['pgndc']         = '-1';
            $roleList               = $this->roleList($input);
            $roleList               = array_keys($this->getValueArray($roleList['RS'],'RoleName','multiple','array'));
            $roleCount              = count($roleList);

            array_walk($users,array($this,'checkUserRoleExist'),$roleList);
            //$this->invalidUsers      = array_unique($this->invalidUsers);
           
            array_walk($users,array($this,'checkUniqueUsers'), $onlyEmailval);
            if(!empty($existing))
            {
                array_walk($users,array($this,'checkExistingEmail'),$existing);
            }
              // $this->existingUsers      = array_unique($this->existingUsers);

            if(!empty($instExisting))
            {
                array_walk($this->users,array($this,'checkOtherExistingEmail'),$instExisting);
            }
            $this->otherInstExistingUsers      = array_unique($this->otherInstExistingUsers);
            
            if(!empty($this->users) && is_array($this->users))
            {
                $firstName      = $this->getValueArray($this->users, 'FirstName', 'multiple');
                $lastName       = $this->getValueArray($this->users, 'LastName', 'multiple');
                $userRole       = $this->getValueArray($this->users, 'UserRole', 'multiple');
                $userName       = $this->getValueArray($this->users, 'PrimaryEmailID', 'multiple');
                $password       = '';
                $priEmail       = $this->getValueArray($this->users, 'PrimaryEmailID', 'multiple');
                $secQuestion    = '';
                $secAnswer      = '';
                $i=0;
                for ($i=0;$i<=count($users);++$i){
                    $pass[]='NULL';
                    $question[]='NULL';
                    $answer[]='NULL';
                }
                $password=implode(',', $pass);
                $secQuestion=implode(',',$question);
                $secAnswer=implode(',',$answer);
                

                $userArr  = array($firstName, $lastName, $userRole, $userName, $password, $priEmail, $secQuestion, $secAnswer, $this->session->getValue('instID'), $this->session->getValue('userID'), $this->currentDate());
                // $InsertedUserDetails : -> we are getting inserted id and varification code in this.
                 $InsertedUserDetails=$this->db->executeStoreProcedure('BulkUserManage', $userArr);
            }

            if(!empty($this->otherInstExistingUsers) && is_array($this->otherInstExistingUsers))
            {
                $existingUserID = $this->getValueArray($this->otherInstExistingUsers, 'userID', 'multiple');
                $userRole       = $this->getValueArray($this->otherInstExistingUsers, 'UserRole', 'multiple');
                $userArr        = array($existingUserID, $userRole, $this->session->getValue('instID'), $this->session->getValue('userID'), $this->currentDate());

                $this->db->executeStoreProcedure('BulkExistingUserManage', $userArr);
            }
           
            $this->users = array_values($this->users);
            $allUsersCnt        = count($users);
            $existingUsersCnt   = count($this->existingUsers);
            $otherInstUsersCnt  = count($this->otherInstExistingUsers);
            $newUsersCnt        = count($this->users);
            $existingusrStr     = json_encode($this->existingUsers);
            $invalidUsersCnt    = count($this->invalidUsers);
            $invalidusrStr      = json_encode($this->invalidUsers);
            $otherinstusrStr    = json_encode($this->otherInstExistingUsers);
            $newusrStr          = json_encode($this->users);
            $roleListSend       =  json_encode($roleList);
            // Send email to added users
             $this->sendEmailToUser($this->users);
            
            return "{'error':'',
                    'success'               :'Bulk User Upload is Sucessfully done.',
                    'allusers'              : $allUsersCnt,
                    'exisitnguserscnt'      : $existingUsersCnt,
                    'exisitngusers'         : $existingusrStr,
                    'newuserscnt'           : $newUsersCnt,
                    'invaliduserscnt'       : $invalidUsersCnt,
                    'invalidusers'          : $invalidusrStr,
                    'newusers'              : $newusrStr,
                    'roleList'              : $roleListSend,  
                    'otherinstuserscnt'     : $otherInstUsersCnt,
                    'otherinstusers'        : $otherinstusrStr}";
        }else
        {
            return "{'error':'There are no Users in Uploded File.'}";
        }
      }  catch(exception $ex)
        {
            $this->myDebug('::problem in Excel Reading Exception');
            $this->myDebug($ex->getMessage());
            return "{'error':'".$ex.getMessage()."'}";
        }
    }
    /*
     * Manish
     * for email 
     */
    
     function sendEmailToUser($userInfo){
        $subject = 'QuAD email verification request';
        $emailLogo = $this->registry->site->cfg->wwwroot.'/assets/imagesnxt/email-logo.png';
        //$reactivationURL = $this->registry->site->cfg->wwwroot.'/index/password-reset/';
        $reactivationURL = $this->registry->site->cfg->wwwroot.'/index/verify-user/';
		//$url = $this->registry->site->cfg->verificationurl;
        //$url = $this->cfg->wwwroot.'/index/verify-user/'.base64_encode($input['id']).'/'.$input['code'];
		$message = file_get_contents(__SITE_PATH.'/views/templates/next/email/emailverification.php', true); 
        $data = array();
        $form='';
        foreach ($userInfo as $k=>$v){        
			//$data['email_logo'] = $emailLogo;
			//$pEmailId=$v['PrimaryEmailID'];
			$userdetail = $this->getUserDetailByEmail($v['PrimaryEmailID']);
		    // $data['name'] = $v['FirstName'].' '.$v['LastName'];
		    // $data['full_name'] = $v['FirstName'].' '.$v['LastName'];
			//$data['user_name'] = $v['PrimaryEmailID'];
			$encodeUserID=base64_encode($userdetail['mapId']);
			//sendTemplateMail
			//$this->sendTemplateMail($emailSubject,$data, $pEmailId, 'emailverification.php',$form);		
			$address = $v['PrimaryEmailID'];
			$templatename = 'emailverification.php';				
			$data['full_name'] = $v['FirstName'].' '.$v['LastName'];
			$data['user_name'] = $v['PrimaryEmailID'];
			$data['activation_url'] =$reactivationURL .$encodeUserID.'/'. $userdetail['code'];//$url
			$data['email_logo'] = $emailLogo;
			$verInfo =site::getVerboseEmailTemplate('Email Verification', $userdetail['ID']);
			$templateInfo = array_merge($data,$verInfo);		
			$this->sendEmailForNewUser($address,$subject,$templateInfo,$templatename);
       }
   }
    
    
    function excelReader($fileName)
    {
        try{
        include $this->cfg->rootPath.'/plugins/excelclasses/PHPExcel.php';

        $objReader = PHPExcel_IOFactory::createReader('Excel5');
        $objReader->setReadDataOnly(true);

        //$objPHPExcel    = $objReader->load($this->cfg->rootPath.'/'.$this->cfgApp->EditorImagesUpload.$this->session->getValue('instID').'/'.$fileName);
        $userUploadXmlPath    = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload;
        $objPHPExcel    = $objReader->load($userUploadXmlPath.$fileName);
        $objWorksheet   = $objPHPExcel->getActiveSheet();
        $highestRow     = $objWorksheet->getHighestRow();
        $highestRow     = ($highestRow > 100)?100:$highestRow;
        $highestColumnIndex = 7;
       // $refArr = array('FirstName','LastName','UserRole','UserName','Password','PrimaryEmailID','SecurityQuestion','SecurityAnswer');
        $refArr = array('FirstName','LastName','UserRole','UserName','Password','PrimaryEmailID','SecurityQuestion','SecurityAnswer');

        $users  = array();
        for ($row = 2; $row <=$highestRow; ++$row)
        {
            $index = $row-2;
            if(trim($objWorksheet->getCellByColumnAndRow(2, $row)->getValue()) != '' && trim($objWorksheet->getCellByColumnAndRow(3, $row)->getValue()) != '' && trim($objWorksheet->getCellByColumnAndRow(5, $row)->getValue()) != '')
            {
                for ($col = 0; $col <= $highestColumnIndex; ++$col)
                {
                    $val = $refArr[$col];
                    $users[$index][$val] = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
            }
        }
        if(!empty($users))
        {
            //$this->users      = array_unique($users);
            $this->users      = $users;

            $userNames      = "'".str_replace(',',"','",$this->getValueArray($users, 'UserName', 'multiple')."'");
            $emails         = "'".str_replace(',',"','",$this->getValueArray($users, 'PrimaryEmailID', 'multiple')."'");
            $existing       = $this->getExistingUsers($userNames, $emails);
            $instExisting   = $this->getInstituteExistingUsers($userNames, $emails);
            $this->existingUsers           = array();
            $this->otherInstExistingUsers  = array();
            $this->invalidUsers            = array();

            $input['rolelisttype']  = 'all';
            $input['pgndc']         = '-1';
            $roleList               = $this->roleList($input);
            $roleList               = array_keys($this->getValueArray($roleList['RS'],'RoleName','multiple','array'));
            $roleCount              = count($roleList);

            array_walk($users,array($this,'checkUserRoleExist'),$roleList);
            $this->invalidUsers      = array_unique($this->invalidUsers);

            if(!empty($existing))
            {
                array_walk($users,array($this,'checkExistingUserNameAndEmail'),$existing);
            }
            $this->existingUsers      = array_unique($this->existingUsers);

            if(!empty($instExisting))
            {
                array_walk($this->users,array($this,'checkOtherExistingUserNameAndEmail'),$instExisting);
            }
            $this->otherInstExistingUsers      = array_unique($this->otherInstExistingUsers);

            if(!empty($this->users) && is_array($this->users))
            {
                 $firstName      = $this->getValueArray($this->users, 'FirstName', 'multiple');
                $lastName       = $this->getValueArray($this->users, 'LastName', 'multiple');
                $userRole       = $this->getValueArray($this->users, 'UserRole', 'multiple');
                $userName       = $this->getValueArray($this->users, 'UserName', 'multiple');
                $password       = $this->getValueArray($this->users, 'Password', 'multiple');
                $priEmail       = $this->getValueArray($this->users, 'PrimaryEmailID', 'multiple');
                $secQuestion    = $this->getValueArray($this->users, 'SecurityQuestion', 'multiple');
                $secAnswer      = $this->getValueArray($this->users, 'SecurityAnswer', 'multiple');
                
                foreach(explode(',', $password) as $p){
                    @$pass[] = Site::getSecureString($p);
                }
                $password = implode(',', $pass);

                foreach(explode(',', $secAnswer) as $s){
                    @$sec[] = Site::getSecureString($s);
                }
                $secAnswer = implode(',', $sec);
              

                $userArr        = array($firstName, $lastName, $userRole, $userName, $password, $priEmail, $secQuestion, $secAnswer, $this->session->getValue('instID'), $this->session->getValue('userID'), $this->currentDate());

                $this->db->executeStoreProcedure('BulkUserManage', $userArr);
            }

            if(!empty($this->otherInstExistingUsers) && is_array($this->otherInstExistingUsers))
            {
                $existingUserID = $this->getValueArray($this->otherInstExistingUsers, 'userID', 'multiple');
                $userRole       = $this->getValueArray($this->otherInstExistingUsers, 'UserRole', 'multiple');
                $userArr        = array($existingUserID, $userRole, $this->session->getValue('instID'), $this->session->getValue('userID'), $this->currentDate());

                $this->db->executeStoreProcedure('BulkExistingUserManage', $userArr);
            }
            $allUsersCnt        = count($users);
            $existingUsersCnt   = count($this->existingUsers);
            $otherInstUsersCnt  = count($this->otherInstExistingUsers);
            $newUsersCnt        = count($this->users);
            $existingusrStr     = json_encode($this->existingUsers);
            $invalidUsersCnt    = count($this->invalidUsers);
            $invalidusrStr      = json_encode($this->invalidUsers);
            $otherinstusrStr    = json_encode($this->otherInstExistingUsers);
            $newusrStr          = json_encode($this->users);
            return "{'error':'',
                    'success'               :'Bulk User Upload is Sucessfully done.',
                    'allusers'              : $allUsersCnt,
                    'exisitnguserscnt'      : $existingUsersCnt,
                    'exisitngusers'         : $existingusrStr,
                    'newuserscnt'           : $newUsersCnt,
                    'invaliduserscnt'        : $invalidUsersCnt,
                    'invalidusers'         : $invalidusrStr,
                    'newusers'              : $newusrStr,
                    'otherinstuserscnt'     : $otherInstUsersCnt,
                    'otherinstusers'        : $otherinstusrStr}";
        }else
        {
            return "{'error':'There are no Users in Uploded File.'}";
        }
      }  catch(exception $ex)
        {
            $this->myDebug('::problem in Excel Reading Exception');
            $this->myDebug($ex->getMessage());
            return "{'error':'".$ex.getMessage()."'}";
        }
    }
    
    function checkUserRoleExist($userArr,$key,$roleArr)
    { 
        if($userArr['UserRole']==''){
            $userArr['UserRole']='blank';
        }
        if((!(in_array($userArr['UserRole'],$roleArr))) || ($userArr['FirstName']=='') || ($userArr['LastName']=='') || (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $userArr['PrimaryEmailID'])))
        {
            
            if(!empty($this->users[$key]))
            {
                if((!(in_array($userArr['UserRole'],$roleArr)))){
                $this->users[$key]['UserRole']='[[Invalid]]';
            }
                
                array_push($this->invalidUsers,$this->users[$key]);
                unset($this->users[$key]);
            }
           
        }
    }
    /* 
     * @manish(17-09-15)
     * checkUniqueUsers
     * $this->duplicateUsers
     * remove duplicate email id row from active user list
     */
    function checkUniqueUsers($userArr,$key,$emailArr)
    { 
        if((!(in_array($userArr['PrimaryEmailID'],$this->sameUsers))))
        {
            array_push($this->sameUsers,$userArr['PrimaryEmailID']);
        }else{
            
            if(!empty($this->users[$key]))
            {  
                array_push($this->invalidUsers,$this->users[$key]);
                unset($this->users[$key]);
            }
                
        }
        
    }
    function checkExistingUserNameAndEmail($userArr,$key,$existingArr)
    {
        if(is_array($existingArr))
        {
            foreach($existingArr as $arr)
            {
                 if(($arr['UserName']==$userArr['UserName']) || ($arr['PrimaryEmailID']== $userArr['PrimaryEmailID']))
                {
                    if(!empty($this->users[$key]))
                    {
                        array_push($this->existingUsers,$this->users[$key]);
                        unset($this->users[$key]);
                    }
                }
            }
        }
    }
    /*
     * @author Manish<manish.kumar@learningmate.com>
     * @01-09-15
     * @checkExistingEmail
     * @exisitng Institute user check 
     * @return array
     */
     function checkExistingEmail($userArr,$key,$existingArr)
    {
        if(is_array($existingArr))
        {
            foreach($existingArr as $arr)
            {
                 if(($arr['PrimaryEmailID']== $userArr['PrimaryEmailID']))
                {
                    if(!empty($this->users[$key]))
                    {
                        array_push($this->existingUsers,$this->users[$key]);
                        unset($this->users[$key]);
                    }
                }
            }
        }
    }
    function checkOtherExistingUserNameAndEmail($userArr,$key,$existingArr)
    {
        if(is_array($existingArr))
        {
            foreach($existingArr as $arr)
            {
                if(($arr['UserName']==$userArr['UserName']) || ($arr['PrimaryEmailID']== $userArr['PrimaryEmailID']))
                {
                    if(!empty($this->users[$key]))
                    {
                        array_push($this->otherInstExistingUsers,$this->users[$key]);
                        $index = count($this->otherInstExistingUsers)-1;
                        $this->otherInstExistingUsers[$index]['userID'] = $arr['ID'];
                        unset($this->users[$key]);
                    }
                }
            }
        }
    }
     /*
     * @author Manish<manish.kumar@learningmate.com>
     * @01-09-15
     * @checkOtherExistingEmail
     * @exisitng Institute user check 
     * @return array
     */
     function checkOtherExistingEmail($userArr,$key,$existingArr)
    {
        if(is_array($existingArr))
        {
            foreach($existingArr as $arr)
            {
                if(($arr['PrimaryEmailID']== $userArr['PrimaryEmailID']))
                {
                    if(!empty($this->users[$key]))
                    {
                        array_push($this->otherInstExistingUsers,$this->users[$key]);
                        $index = count($this->otherInstExistingUsers)-1;
                        $this->otherInstExistingUsers[$index]['userID'] = $arr['ID'];
                        unset($this->users[$key]);
                    }
                }
            }
        }
    }
    
    function getInstituteExistingUsers($userName, $email)
    {
        global $DBCONFIG;
        $result = array();
        if($userName != '' && $email != '')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            $query  = " select u.ID, u.\"UserName\", u.\"PrimaryEmailID\" from Users u, MapClientUser mcu where mcu.\"UserID\" = u.ID and mcu.\"ClientID\" != {$this->session->getValue('instID')} and (u.\"UserName\" in($userName) or u.\"PrimaryEmailID\" in ($email)) ";
            else
            $query  = " select u.ID, u.UserName, u.PrimaryEmailID from Users u, MapClientUser mcu where mcu.UserID = u.ID and mcu.ClientID != {$this->session->getValue('instID')} and (u.UserName in($userName) or u.PrimaryEmailID in ($email)) ";
            $result = $this->db->getRows($query);
        }
        return $result;
    }

    function getExistingUsers($userName, $email)
    {
        global $DBCONFIG;
        $result = array();
        if($userName != '' && $email != '')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            $query  = " select u.\"UserName\", u.\"PrimaryEmailID\" from Users u, MapClientUser mcu where mcu.\"UserID\" = u.ID and mcu.\"ClientID\" = {$this->session->getValue('instID')} and (u.\"UserName\" in($userName) or u.\"PrimaryEmailID\" in ($email)) ";
            else
            $query  = " select u.UserName, u.PrimaryEmailID from Users u, MapClientUser mcu where mcu.UserID = u.ID and mcu.ClientID = {$this->session->getValue('instID')} and (u.UserName in($userName) or u.PrimaryEmailID in ($email)) ";
            $result = $this->db->getRows($query);
        }
        return $result;
    }
    /*
     * @author Manish<manish.kumar@learningmate.com>
     * @01-09-15
     * @getInstituteExistingUsersNew
     * @exisitng Institute user check 
     * @return array
     */
    function getInstituteExistingUsersNew($email)
    {
        global $DBCONFIG;
        $result = array();
        if($email != '')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            $query  = " select u.ID, u.\"UserName\", u.\"PrimaryEmailID\" from Users u, MapClientUser mcu where mcu.\"UserID\" = u.ID and mcu.\"ClientID\" != {$this->session->getValue('instID')} and (u.\"PrimaryEmailID\" in ($email)) ";
            else
            $query  = " select u.ID, u.UserName, u.PrimaryEmailID from Users u, MapClientUser mcu where mcu.UserID = u.ID and mcu.ClientID != {$this->session->getValue('instID')} and (u.PrimaryEmailID in ($email)) ";
            $result = $this->db->getRows($query);
        }
        return $result;
    }

    
    /*
     * @author Manish<manish.kumar@learningmate.com>
     * @01-09-15
     * getExistingUsersNew
     * @exisitng user check 
     * @return array
     */
    function getExistingUsersNew($email)
    {
        $this->myDebug('IN getExistingUsersNew User section');
        $this->myDebug($email);
        global $DBCONFIG;
        $result = array();
        if($email != '')
        {
            if ( $DBCONFIG->dbType == 'Oracle' )
            $query  = " select u.\"UserName\", u.\"PrimaryEmailID\" from Users u, MapClientUser mcu where mcu.\"UserID\" = u.ID and mcu.\"ClientID\" = {$this->session->getValue('instID')} and (u.\"PrimaryEmailID\" in ($email)) ";
            else
            $query  = " select u.UserName, u.PrimaryEmailID from Users u, MapClientUser mcu where mcu.UserID = u.ID and mcu.ClientID = {$this->session->getValue('instID')} and (u.PrimaryEmailID in ($email)) ";
            $result = $this->db->getRows($query);
        }
         $this->myDebug($result);
        return $result;
    }
    
    function uploadUserList(array $input)
    {
        //upload xls files ->wwwroot.'/'.
        //$target_path    = "{$this->cfg->rootPath}/{$this->cfgApp->EditorImagesUpload}{$this->session->getValue('instID')}/";
        $target_path    = $this->cfg->rootPath."/".$this->cfgApp->tempDataPath.$this->session->getValue('instID')."/".$this->cfgApp->UserDocUpload;
	if(!is_dir($target_path ))
        {
            mkdir($target_path,0777);
        }
        $fileName       = strtotime("now").'.xls';
        try{
            move_uploaded_file($this->fileParam('userlistToUpload', 'tmp_name'), $target_path.$fileName);
            return $this->readerExcel($fileName);
            //return $this->excelReader($fileName);
        }  catch(exception $ex)
            {
                $this->myDebug('::user uplaoding problem Exception');
                $this->myDebug($ex);
            }
    }
    function verboseCategoriesList(array $input,$condition)
    {
      return $this->db->executeStoreProcedure('VerboseCategoriesList', array($input['verboseCat']),'nocount');
    }
    function verboseLanguages(array $input,$condition)
    {
        global $DBCONFIG;
        Site::myDebug('------verboseLanguageModel');
        $this->myDebug($input);
        
        $input['filter']    = ($condition != '')?$condition:'-1';
        $input['pgnob']     = ($input['pgnob'] != '')?$input['pgnob']:"ModDate";
        $input['pgnot']     = ($input['pgnot'] != '')?$input['pgnot']:"DESC";
        $input['pgnstart']  = ($input['pgnstart'] != '')?$input['pgnstart']: '0';
        $input['pgnstop']   = ($input['pgnstop'] != '')?$input['pgnstop']:'-1';
        
        Site::myDebug(json_encode($input));
        
        $verboseLanguageList = $this->db->executeStoreProcedure('VerboseLanguageList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],$input['filter']) ); 
        
        return $verboseLanguageList;
       
    }
    
    function verboseLanguagesDropdown()
    {
        global $DBCONFIG;
        Site::myDebug('------verboseLanguageModel');
        $this->myDebug($input);
        
        $languageSQL = "Select * from VerboseLanguage WHERE isEnabled='1'";
        
        $verboseLanguageList = $this->db->getRows($languageSQL); 
        
        return $verboseLanguageList;
       
    }
    function verboseData($verboseid)
    {
        /*$query = "SELECT vc.ID AS catID, vc.Label AS catLabel, vi.ID AS verboseID, vi.Code AS verboseCode, vi.Label AS verboseLabel,
                    IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS verboseValue, IF(mcv.Tooltip != '', mcv.Tooltip, vi.DefaultTooltip) AS verboseTooltip
                    FROM VerboseCategories vc
                    LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
                    LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
                    AND mcv.isEnabled = '1'
                    WHERE vc.isEnabled = '1' AND vi.ID = ".$verboseid." AND vi.isEnabled = '1'";*/
        
        
        $query = "SELECT vc.ID AS catID, vc.Label AS catLabel, vi.ID AS verboseID, vi.Code AS verboseCode, vi.Label AS verboseLabel, vi.isTooltip AS isTooltip,
                    IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS verboseValue, IF(mcv.isEnabled = '1' , mcv.Tooltip, vi.DefaultTooltip) AS verboseTooltip
                    FROM VerboseCategories vc
                    LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
                    LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
                    AND mcv.isEnabled = '1'
                    WHERE vc.isEnabled = '1' AND vi.ID = ".$verboseid." AND vi.isEnabled = '1'";
        $data = $this->db->getSingleRow($query); 
        return $data;
    }
    
    /**
    * gets the verbose list 
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
    function verboseList(array $input,$condition)
    {       
        global $DBCONFIG;
        Site::myDebug('------verboseListModel');
        $this->myDebug($input);
        
        $input['filter']    = ($condition != '')?$condition:'-1';
        $input['pgnob']     = ($input['pgnob'] != '')?$input['pgnob']:"ModDate";
        $input['pgnot']     = ($input['pgnot'] != '')?$input['pgnot']:"DESC";
        $input['pgnstart']  = ($input['pgnstart'] != '')?$input['pgnstart']: '0';
        $input['pgnstop']   = ($input['pgnstop'] != '')?$input['pgnstop']:'-1';
        
        Site::myDebug(json_encode($input));
        
        $verboseList = $this->db->executeStoreProcedure('VerboseList', array($input['verbcatid'],$this->session->getValue('instID'),$input['verblangid'],$input['filter']) ); 
        
        return $verboseList;
    }
    
    
    function saveVerbose(array $input)
    {
        $this->db->executeStoreProcedure('ManageClientVerboseInfo', array($input['verboseID'],$this->session->getValue('instID'),$input['textName'],$input['tooltipName'],$this->session->getValue('userID'),$this->currentDate(),'1'),'nocount');
        $this->generateVerbseJson();
        return "{'Msg':'Success'}";        
    }
    function resetVerbose(array $input)
    {
        $oldVerbose = $this->db->executeStoreProcedure('ManageClientVerboseInfo', array($input['verboseId'],$this->session->getValue('instID'),'-1','-1',$this->session->getValue('userID'),$this->currentDate(),'0'),'nocount');
        $this->myDebug($oldVerbose);
        $this->generateVerbseJson();
        $verboseStr = json_encode($oldVerbose);
        return "{'Msg':'Success','Verbose':{$verboseStr}}";
    }
    function manageTemplate(array $input,$condition)
    {
        $qtp = new QuestionTemplate();
        return $qtp->GetClientQuestionTemplates($input,$condition);
    }
          /**
    * This function is used to active/deactive question template for institute level
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array           $input

    * @return   void            $status
    *
    */
   function templateStatus($input)
    {
       global $DBCONFIG;
       $id = $input["id"];
       $status = $input["status"];
       //$modDate = date("Y-m-d H:i:s");
       $modDate = $this->getFormatDate(str_replace('-','/',date("Y-m-d H:i:s")));
        if ( $DBCONFIG->dbType == 'Oracle' )
       $query  = "  UPDATE MapClientQuestionTemplates SET \"isActive\" = '$status', \"ModBY\"={$this->session->getValue('userID')}, \"ModDate\" = '$modDate'  WHERE ID IN({$id}) AND \"isEnabled\" = '1'";
       else
       $query  = "  UPDATE MapClientQuestionTemplates SET isActive = '$status', ModBY={$this->session->getValue('userID')}, ModDate = '$modDate'  WHERE ID IN({$id}) AND isEnabled = '1'";
       $status = $this->db->execute($query);
       return $status;

   }

   /**
     * generateVerificationCode 
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    $userID array
     * @return   boolean
     *
     */
    public function generateVerificationCode($userID) {
        global $DBCONFIG;

        $code = md5(uniqid());
        $modDate = $this->getFormatDate(str_replace('-', '/', date("Y-m-d H:i:s")));
        if($this->session->getValue('userID')){
            $modBy = $this->session->getValue('userID');
        }else{
            $modBy = $userID;
        }
        $query = "  UPDATE MapClientUser SET VerificationCode = '$code', "
                . "ModBY=$modBy, ModDate = '$modDate'  "
                . "WHERE UserID = $userID";
        return $this->db->execute($query);

    }

    /**
     * user detail the given user id
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    $userID int
     * @return   boolean
     *
     */
    public function getUserDetail($userID) {
        global $DBCONFIG;

        if (!empty($userID)) {
            $query = "SELECT u.*, mcu.VerificationCode code 
                        FROM Users u  
                        LEFT JOIN MapClientUser mcu ON u.ID = mcu.UserID
                        WHERE u.ID = $userID";
            return $this->db->getSingleRow($query);
        }
    }
    /*
     * get user details via email id 
     * 
     */
  function getUserDetailByEmail($emailID) {
        global $DBCONFIG;

        if (!empty($emailID)) {
            $query = "SELECT u.*, mcu.VerificationCode code ,mcu.ID mapId
                        FROM Users u  
                        LEFT JOIN MapClientUser mcu ON u.ID = mcu.UserID
                        WHERE u.PrimaryEmailID = '".$emailID . "'";
            return $this->db->getSingleRow($query);
        }
    }
                
    
    /**
     * verify code
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    $userID array
     * @return   boolean
     *
     */
    public function verifyCode($code, $expired = false) {
        global $DBCONFIG;

        if (!empty($code)) {
            //also check if expire after 48 hours
            $query = " SELECT mcu.UserID userID, mcu.ModDate from MapClientUser mcu WHERE mcu.VerificationCode = '$code' ";
            $result = $this->db->getSingleRow($query);
            if($expired){
                if($this->expiredHour($result['ModDate']) > 48)
                    return false;
            }
            return $result;
        }
    }

    public function expiredHour($lastDateTime, $format = 'Y-m-d H:i') {
        $todayDate = date($format); //today's datetime
        $diff = strtotime($todayDate) - strtotime($lastDateTime);
        return intval($diff / 3600);
    }

    public function registerClientStep1(array $input,$clientID=0){
        global $DBCONFIG;
        if($this->verifyEmail($input['idpersonalemail'])  && $clientID == 0)
        {
            return CONTACTEMAILEXISTS;
        }
        $userid = $this->db->insert('users', array('UserName'=>$input['idpersonalemail'],
                                                    'Password'=>md5($input['idadminpassword']),
                                                    'PrimaryEmailID'=>$input['idpersonalemail']));
        return $userid;
    }
    
    public function getUserForRegisterStep2($userid){
        $query = "SELECT u.*
                        FROM Users u  
                        WHERE u.ID = $userid";
        $result = $this->db->getSingleRow($query);
        return $result;
    }
    
    public function randomPassword( $length = 8 ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%_";
        $password = substr( str_shuffle( $chars ), 0, $length );
        return $password;
    } 

    /**
     * Update User information on the basis of User Name
     * @access   public
     */
    public function updateWithUname(array $param) {
        global $DBCONFIG;
        $username = $param['UserName'];
        $updated_data = array(
            'Password' => Site::getSecureString($param['Password']),
            'SecurityQuestion' => $param['SecurityQuestion'],
            //'SecurityAnswer' => Site::getSecureString($param['SecurityAnswer']),
            'SecurityAnswer' => $param['SecurityAnswer'],
            'isEnabled' => '1',
            'isActive' => '1',
            'ModDate' => $this->currentDate()
        );
        //echo "<pre>";print_r($updated_data);
        $where = " UserName='" . $username . "' AND isEnabled='1'";
        //echo "<pre>";echo $where;die;
        $status = $this->db->update("Users",$updated_data,$where);
        
        $query = "SELECT ID FROM Users WHERE UserName = '" . $username . "' AND isEnabled='1'";
        $res = $this->db->getSingleRow($query);
        
        site::myDebug("USer Id check");
        site::myDebug($res['ID']);
        
        $updated = array(
            'VerificationCode' => '',
            'ModDate' => $this->currentDate()
        );
        //echo "<pre>";print_r($updated_data);
        
        
        $where_cond = " UserID=" . $res['ID'] . " AND isEnabled='1'";
        //echo "<pre>";echo $where;die;
        $status = $this->db->update("MapClientUser",$updated,$where_cond);
        
        site::myDebug("USer Id status");
        site::myDebug($status);
        return $status;
    }

    public function getUserdetailsFrom($UserName) {
        $query = "SELECT FirstName,LastName FROM Users WHERE UserName = '{$UserName}' AND isEnabled='1'";
        $data = $this->db->getSingleRow($query);
        return $data;
    }
    
    public function sendEmailForNewUser($address,$subject,$data,$templatename){
        //sendTemplateMail
        return $this->sendTemplateMail($subject,$data, $address, $templatename);
   }
    public function getVerificationCode($UserName) {
        $query = "SELECT VerificationCode FROM MapClientUser WHERE UserID = (SELECT ID FROM Users WHERE UserName = '" . $UserName . "' AND isEnabled='1')";     
        $res = $this->db->getSingleRow($query);
        return $res['VerificationCode'];
    }
    
    public function getUserIDFromEmail($email) {
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query = "SELECT \"ID\" FROM Users WHERE (\"PrimaryEmailID\" = '{$email}' OR \"UserName\" = '{$email}') AND \"isEnabled\" = '1'";
        } else {
            $query = "SELECT ID FROM Users WHERE (PrimaryEmailID = '{$email}' OR UserName = '{$email}') AND isEnabled = '1'";
        }
        $result = $this->db->getSingleRow($query);
        return $result['ID'];
    }
    
    public function getUserDetailsFromUID($UID) 
    {
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query = "SELECT \"UserName\",\"SecurityQuestion\",to_char(\"SecurityAnswer\") FROM Users WHERE \"ID\"={$UID} AND \"isEnabled\" = '1' AND \"isActive\" = '1'";
        } else {
            $query = "SELECT UserName,SecurityQuestion,SecurityAnswer FROM Users WHERE ID={$UID} AND isEnabled = '1' AND isActive = '1'";
        }
        $result = $this->db->getSingleRow($query);
        return $result;
    }  
    function manageLanguage($languageID,$languageName)
    {
        global $DBCONFIG;
        
        if(trim($languageID)!=0){
            $query = "SELECT * from VerboseLanguage WHERE Language='".trim($languageName)."' and isEnabled=1";
            if($this->db->getCount($query)==0){
                $updated = array(
                    'Language'  => trim($languageName),
                    'ModDate'   => $this->currentDate(),
                    'ModBY'     => $this->session->getValue('userID')
                );

                $where_cond = " ID=" . $languageID . " AND isEnabled='1'";
                $this->db->update("VerboseLanguage",$updated,$where_cond);
                $this->generateVerbseJson();
                return "success";
            }else{
                return "duplicate";
            }
        }else{            
            $query = "SELECT * from VerboseLanguage WHERE Language='".trim($languageName)."' and isEnabled=1";
            if($this->db->getCount($query)==0){            
                $inserted = array(
                    'Language'   => trim($languageName),
                    'AddDate'    => $this->currentDate(),
                    'UserID'     => $this->session->getValue('userID'),
                    'ModDate'    => $this->currentDate(),
                    'ModBy'      => $this->session->getValue('userID'),
                    'isEnabled'  => 1
                );
                $insertedID = $this->db->insert('VerboseLanguage', $inserted);
                
                $query = "INSERT INTO VerboseInfo(Code,Label,CategoryID,DefaultValue,DefaultTooltip,isEditable,isTooltip,FileType,LanguageID,UserID,AddDate,isEnabled) ";
                $query .= " SELECT Code,Label,CategoryID,DefaultValue,DefaultTooltip,isEditable,isTooltip,FileType,".$insertedID.",".$this->session->getValue('userID').",'".$this->currentDate()."',isEnabled from VerboseInfo WHERE LanguageID=1"; 

                $this->db->execute($query);
                $this->generateVerbseJson();
                return "success";
            }else{
                return "duplicate";
            }
        }
    }
    function checkLanguageAvailInProfile($langID) {
        global $DBCONFIG;
        $query_check_existance = "SELECT UsageCount AS cnt FROM VerboseLanguage WHERE ID=".$langID." AND isEnabled='1'";
        $resultset = $this->db->getSingleRow($query_check_existance);
        return $resultset['cnt'];
    }
    function deleteLanguage($langID)
    {
        global $DBCONFIG;
        $query  = ($DBCONFIG->dbType == 'Oracle' ) ? "  UPDATE VerboseLanguage SET \"isEnabled\" = 0 WHERE \"ID\" = ".$langID : "  UPDATE VerboseLanguage SET isEnabled = '0' WHERE ID ='".$langID."'";
        $status = $this->db->execute($query);
        
        $query  = ($DBCONFIG->dbType == 'Oracle' ) ? "  UPDATE VerboseInfo SET \"isEnabled\" = 0 WHERE \"LanguageID\" = ".$langID  : "  UPDATE VerboseInfo SET isEnabled = '0' WHERE LanguageID ='".$langID."'";
        $status = $this->db->execute($query);
       
        if($status)
        {
            if(!empty($langID))
            {
                $langInfo = $this->getLangInfo($langID);
                if ( $langInfo )
                {
                    if($DBCONFIG->dbType == 'Oracle')
                    {
                        $data[] = array(
                                        "UserID"        => $this->session->getValue('userID'),
                                        "EntityTypeID"  => 15,
                                        "EntityID"      => $langID,
                                        "EntityName"    => $langInfo['Language'],
                                        "Action"        => 'Deleted',
                                        "ActionDate"    => $this->currentDate(),
                                        "isEnabled"     => '1',
                                        "AccessLogID"   => $this->session->getValue('accessLogID')
                                    );
                    }
                    else
                    {
                        $data[] = array(
                                    'UserID'        => $this->session->getValue('userID'),
                                    'EntityTypeID'  => 15,
                                    'EntityID'      => $langID,
                                    'EntityName'    => $langInfo['Language'],
                                    'Action'        => 'Deleted',
                                    'ActionDate'    => $this->currentDate(),
                                    'isEnabled'     => '1',
                                    'AccessLogID'   => $this->session->getValue('accessLogID')
                                );
                    }
                }               
                $this->db->multipleInsert('ActivityTrack',$data);
            }
        }
       // return false;
        return $status;
    }
    public function getLangInfo($langID)
    {
        global $DBCONFIG;
        $query = ($DBCONFIG->dbType == 'Oracle') ? "  SELECT * FROM VerboseLanguage WHERE \"ID\" = $langID " : "  SELECT * FROM VerboseLanguage WHERE ID = '".$langID."'";
        return $this->db->getSingleRow($query);
    }
    
    public function verboseListResponse($LangID)
    {
        $sql = "SELECT vi.DefaultValue,vi.DefaultTooltip,vi.Code,vi.Label,
                IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS DefaultValue, IF(mcv.Tooltip != '', mcv.Tooltip, vi.DefaultTooltip) AS DefaultTooltip
                FROM VerboseCategories vc
                LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
                LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
                AND mcv.isEnabled = '1'
                WHERE LanguageID='".(int)$LangID."' AND vi.isEnabled = '1' AND vi.FileType='json'";
        $result = $this->db->getRows( $sql );
        return $result;
       
    }
    public function verboseListFull($LangID)
    {
       /* $sql = "SELECT vi.DefaultValue,vi.DefaultTooltip,vi.Code,vi.Label,
                IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS DefaultValue, IF(mcv.Tooltip != '', mcv.Tooltip, vi.DefaultTooltip) AS DefaultTooltip
                FROM VerboseCategories vc
                LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
                LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
                AND mcv.isEnabled = '1'
                WHERE LanguageID='".(int)$LangID."' AND vi.isEnabled = '1'";*/
         $sql = "SELECT vi.DefaultValue,vi.DefaultTooltip,vi.Code,vi.Label,
                IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS DefaultValue, IF(mcv.isEnabled = '1', mcv.Tooltip, vi.DefaultTooltip) AS DefaultTooltip
                FROM VerboseCategories vc
                LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
                LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
                AND mcv.isEnabled = '1'
                WHERE LanguageID='".(int)$LangID."' AND vi.isEnabled = '1'";
        
        
        
        $result = $this->db->getRows( $sql );
        return $result;
       
    }
    
    public function generateVerbseJson()
    {
        $languages = $this->verboseLanguages(array(),-1);
        foreach($languages['RS'] as $language){
            if (!file_exists(VERBPATH."/".$language['ID'])) {
                mkdir(VERBPATH."/".$language['ID'], 0777, true);
            }
            
            // generate full JSON
            
            $res = $this->verboseListFull($language['ID']);
            $verb=array();
            foreach( $res as $kkk=>$vvv ){
                $vvv1['info']       = htmlentities($vvv['Label'],ENT_QUOTES, "UTF-8");
                $vvv1['text']       = htmlentities($vvv['DefaultValue'],ENT_QUOTES, "UTF-8");
                $vvv1['tooltip']    = htmlentities($vvv['DefaultTooltip'],ENT_QUOTES, "UTF-8");                
                $verb[$vvv['Code']] = $vvv1;  
            }
            
            $outputJSON = json_encode($verb ,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            
            $file = VERBPATH."/".$language['ID'].'/verb_common.json';
            // Open the file to get existing content
            $current = file_get_contents($file);
            // Write the contents back to the file
            file_put_contents($file, $outputJSON);
            
            // generate response JSON
            
//            $res2 = $this->verboseListResponse($language['ID']);
//            $verb2=array();
//            foreach( $res2 as $kkk=>$vvv ){
//                $vvv1['info']       = htmlentities($vvv['Label'],ENT_QUOTES);
//                $vvv1['text']       = $vvv['DefaultValue'];
//                $vvv1['tooltip']    = htmlentities($vvv['DefaultTooltip'],ENT_QUOTES);                
//                $verb2[$vvv['Code']] = $vvv1;  
//            }
//            
//            $outputJSON2 = json_encode($verb2 ,JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
//            
//            $file2 = VERBPATH."/".$language['ID'].'/verb_response.json';
//            // Open the file to get existing content
//            $current = file_get_contents($file2);
//            // Write the contents back to the file
//            file_put_contents($file2, $outputJSON2);
        }
    }
    public function verboseCategory( $parent_id = 0 ) {
        $categories = array();
        $query      = " SELECT * FROM VerboseCategories vc WHERE vc.ParentID='".$parent_id."' AND vc.isEnabled=1 order by Label asc";      
        $result     = $this->db->getRows($query);
        
        foreach ($result as $mainCategory) {
          $category = array();
          //$category['id'] = $mainCategory['ID'];
          $category['text'] = $mainCategory['Label'];
          //$category['parent_id'] = $mainCategory['ParentID'];
          if($this->verboseCategory($mainCategory['ID'])){
            $category['catID'] = $mainCategory['ID'];
            $category['nodes'] = $this->verboseCategory($mainCategory['ID']);            
          }else{
            //$category['href'] = '/#'.$mainCategory['ID'];
            $category['dataNodeId'] = $mainCategory['ID'];
          }
          
          $categories[] = $category;
        }
        return $categories;
    }
    
    public function exportVerbiageCsv() {
//        $sql = "SELECT vi.DefaultValue,vi.DefaultTooltip,vi.Code,vi.Label,
//                IF(mcv.Value != '', mcv.Value, vi.DefaultValue) AS DefaultValue, IF(mcv.Tooltip != '', mcv.Tooltip, vi.DefaultTooltip) AS DefaultTooltip
//                FROM VerboseCategories vc
//                LEFT JOIN VerboseInfo vi ON vc.ID = vi.CategoryID
//                LEFT JOIN MapClientVerbose mcv ON vi.ID = mcv.VerboseInfoID
//                AND mcv.isEnabled = '1'
//                WHERE LanguageID='1' AND vi.isEnabled = '1'";
        
        $sql = "SELECT DefaultValue,DefaultTooltip,Code,Label
                FROM VerboseInfo WHERE LanguageID='1' AND isEnabled = '1'";
        
        $result = $this->db->getRows( $sql );
        return $result;
    }
	
	 /**
    * Resend activation mail to users
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $userID
    * @return   boolean
    *
    */

    public function resendVerification($userID)
    {
    
		global $DBCONFIG;
        //$userID = $this->removeBlankElements($userID); 
		$ModDate = date("Y-m-d h:m:s");
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
			
            $query  = "UPDATE MapClientUser SET \"ModDate\" = {$ModDate}, \"ModBY\" = {$this->session->getValue('userID')} WHERE \"UserID\" IN ($userID) ";            
        }
        else
        {
            $query  = "UPDATE MapClientUser SET ModDate = '{$ModDate}', ModBY={$this->session->getValue('userID')} WHERE UserID IN($userID)";
        }
		//echo $query; die();
		$this->db->execute($query);
		$sql = "SELECT u.FirstName, u.LastName, u.PrimaryEmailID, mcu.ID, mcu.VerificationCode FROM Users u INNER JOIN MapClientUser mcu ON u.ID = mcu.UserID WHERE u.ID = ($userID)";
        
        return $this->db->getRows($sql);
    }
	
	
	public function updateUserProfile($userID,$imageName)
    {
		global $DBCONFIG;
		$query = "UPDATE Users SET Image='$imageName' WHERE ID={$userID} LIMIT 1 ";
        $this->db->execute($query);
	}		
}
?>