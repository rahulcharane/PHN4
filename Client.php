<?php
/**
 * Description of client
 *
 * @author yogesh.nikam
 */
class client extends Site
{
    /**
      * constructs a new classname instance
      */
    
    function  __construct()
    {
        parent::Site();
    }

    /**
    * Verifies promo code
    *
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $promocode
    * @return     array       $promocode
    *
    */
    
    function verifyPromoCode($promocode)
    {
        global $DBCONFIG;
                       
if($DBCONFIG->dbType=='Oracle'){
        $query      = " Select Pc.\"ClientID\", Pc.\"StartDate\",Pc.\"EndDate\" ,
                        Case When  DATEDIFF(pc.\"StartDate\",'{$this->currentDate()}') Is Null Then 1 Else  DATEDIFF(pc.\"StartDate\",'{$this->currentDate()}') End   As \"Startdiff\" ,
                        Case When  DATEDIFF('{$this->currentDate()}',Pc.\"EndDate\") Is Null Then 1 Else  DATEDIFF('{$this->currentDate()}',Pc.\"EndDate\") End  As \"EndDiff\"
                        FROM PromoCodes pc WHERE \"PromoCode\" = '$promocode' AND \"isEnabled\" = 1";
    
}else{
        $query      = " SELECT ClientID, StartDate,EndDate  ,
                        IF(DATEDIFF('{$this->currentDate()}',StartDate) IS NULL, 1, DATEDIFF('{$this->currentDate()}',StartDate))  as StartDiff ,
                        IF(DATEDIFF(EndDate,'{$this->currentDate()}') IS NULL, 1,DATEDIFF(EndDate,'{$this->currentDate()}'))  as EndDiff
                        FROM PromoCodes WHERE PromoCode = '$promocode' AND isEnabled = '1' LIMIT 1";
}     

       
        $promocode  = $this->db->getSingleRow($query);
        return $promocode;
    }

    /**
    * Get Client details
    *
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $promocode
    * @param      string      $isDefault
    * @return     array       $result
    *
    */
    
    public function details($promocode, $isDefault='N')
    {
        $user       = new User();
        $client     = $this->verifyPromoCode($promocode);
        if($client['ClientID'] == $user->getDefaultClientID() && $isDefault == 'N')
        {
            return false;
        }

        if((strtotime($client['StartDate']) != '' && time() < strtotime($client['StartDate'])) || (strtotime($client['EndDate']) != '' && time() > strtotime($client['EndDate'])))
        {
            return false;
        }
        
        $result     =  $this->info($client['ClientID']);
        $this->myDebug($result);
        return $result;
    }

    /**
    * Get Client List
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $condition
    * @return     array
    * @deprecated
    *
    */
    
    function getList($condition='')
    {
        $condition  = ($condition != '')?$condition:'';

        $query      = "  SELECT c.*, COUNT(mcu.ClientID) AS userCount FROM Clients c
                            LEFT JOIN MapClientUser mcu on c.ID = mcu.ClientID
                            WHERE c.isEnabled = '1' $condition
                            AND  c.OrganizationName != ''
                            GROUP BY c.ID
                            ORDER BY c.OrganizationName ASC
                        ";
        return $this->db->getRows($query);
    }

    /**
    * Get Client Count
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $condition
    * @return     integer
    * @deprecated
    *
    */
    
    function count($condition='')
    {
        $condition  = ($condition != '')?$condition:'';
        $query      = "  SELECT * FROM Clients
                            WHERE  isEnabled = '1'  $condition ";
        return $this->db->getCount($query);
    }

    /**
    * Get Client Count per Month
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     string      $condition
    * @deprecated
    *
    */
    
    function monthsCount()
    {
        $condition  = ' AND Month(AddDate)  = Month(CURDATE())
                        AND Year(AddDate)   = Year(CURDATE()) ';
        return $this->count($condition);
    }

    /**
    * Get yesterday's Client Count
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     string      $condition
    * @deprecated
    *
    */
    
    function yesterdaysCount()
    {
        $condition = ' and Day(AddDate)     = Day(CURDATE())-1
                       and Month(AddDate)   = Month(CURDATE())
                       and Year(AddDate)    = Year(CURDATE()) ';
        return $this->count($condition);
    }

    /**
    * Get Client List per month
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     array
    * @deprecated
    *
    */
    
    function monthsList()
    {
        $condition = '  and Day(AddDate)    = Day(CURDATE())-1
                        and Month(AddDate)  = Month(CURDATE())
                        and Year(AddDate)   = Year(CURDATE()) ';
        return $this->clientsList($condition);
    }

    /**
    * Get Yesterday Client List
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     array
    * @deprecated
    *
    */
    
    function yesterdaysList()
    {
        $condition = '  and Month(AddDate)  = Month(CURDATE())
                        and Year(AddDate)   = Year(CURDATE()) ';
        return $this->clientsList($condition);
    }

    /**
    * Get Average User Count
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $condition
    * @return     integer     $avgCnt
    * @deprecated
    *
    */
    
    function avgUsersCount($condition = '')
    {
        $query      = "  SELECT count(UserID) as UsrCnt,
                        (select count(distinct(ClientID))
                        FROM MapClientUser WHERE isEnabled = '1' $condition) as InsCnt
                        FROM MapClientUser WHERE isEnabled = '1' $condition
                        ";
        $avgRecs    = $this->db->getSingleRow($query);

        $userCnt    = $avgRecs['UsrCnt'];
        $isntCnt    = $avgRecs['InsCnt'];

        if( $userCnt > 0 && $isntCnt > 0 )
        {
            $avgCnt = ceil($userCnt / $isntCnt);
        }
        else
        {
            $avgCnt = 0;
        }
        return $avgCnt;
    }

    /**
    * Get Average Learner Count
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $condition
    * @return     integer     $avgCnt
    * @deprecated
    *
    */
    
    function avgLearnersCount($condition = '')
    {
     $condition     = ($condition != '')?$condition:'';
     $query         = " SELECT count(UserID) as UsrCnt,
                            (select count(distinct(ClientID))
                            FROM MapClientUser WHERE isEnabled = '1' $condition) as InsCnt
                            FROM MapClientUser WHERE isEnabled = '1' $condition
                       ";
        $avgRecs    = $this->db->getSingleRow($query);

        $userCnt    = $avgRecs['UsrCnt'];
        $isntCnt    = $avgRecs['InsCnt'];

        if($userCnt > 0 && $isntCnt > 0)
        {
            $avgCnt = ceil($userCnt / $isntCnt);
        }
        else
        {
            $avgCnt = 0;
        }
        return $avgCnt;
    }

    /**
    * Get Average Assessments Count
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      string      $condition
    * @return     integer     $avgCnt
    * @deprecated
    *
    */
    
    function avgAssessmentsCount($condition='')
    {
        $condition  = ($condition != '')?$condition:'';
        $query      = " SELECT COUNT(DISTINCT(a.ID)) as QuizCnt, COUNT(DISTINCT(c.ID)) as InstCnt
                        FROM Assessments a
                        INNER JOIN Users u on  a.UserID = u.ID
                        INNER JOIN MapClientUser mcu on u.ID = mcu.UserID
                        INNER JOIN Clients c on mcu.ClientID = c.ID
                        WHERE u.isEnabled = '1' AND a.isEnabled = '1' AND c.isEnabled = '1'  $condition";
        $avgRecs    = $this->db->getSingleRow($query);

        $quizCnt    = $avgRecs['QuizCnt'];
        $isntCnt    = $avgRecs['InstCnt'];

        if($quizCnt > 0 && $isntCnt > 0)
        {
            $avgCnt = ceil($quizCnt / $isntCnt);
        }
        else
        {
            $avgCnt = 0;
        }
        return $avgCnt;
    }

    /**
    * Generate Token
    *
    * @access     public
    * @abstract
    * @static
    * @global     object      $APPCONFIG
    * @param
    * @return     string
    * @deprecated
    *
    */

    function generateToken()
    {
        global $APPCONFIG;
        return uniqid($APPCONFIG->TokenPrefix);
    }    

    /**
    * Activate Client
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instID
    * @return     boolean
    * @deprecated
    *
    */
    
    function activate($instID)
    {
        $query      = " UPDATE Clients SET isEnabled = '1' WHERE ID IN ($instID)";
        $rslt       = $this->db->execute($query);

        if(!empty($rslt))
        {
            $users = $this->users($instID, 'UserID');
            if(!empty($users))
            {
                $usr = new User();
                foreach($users as $user)
                {
                    $usr->enableUser($user->UserID);
                }
            }
        }
        return true;
    }

    /**
    * DeActivate Client
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instID
    * @return     boolean
    * @deprecated
    *
    */
    
    function deactivate($instID)
    {
        $query      = "  UPDATE Clients SET isEnabled = '0' WHERE ID IN ($instID)";
        $rslt       = $this->db->execute($query);

        if(!empty($rslt))
        {
            $users = $this->users($instID, 'UserID');
            if(!empty($users))
            {
                $usr = new User();
                foreach($users as $user)
                {
                    $usr->deleteUser($user->UserID);
                }
            }
        }
        return true;
    }

    /**
    * get User List as per client
    *
    * @access     public
    * @abstract
    * @static
    * @global     object      $APPCONFIG
    * @param      integer     $clientID
    * @param      string      $orderColumn
    * @return     array
    * @deprecated
    *
    */
    
    function users($clientID, $orderColumn)
    {
        global $APPCONFIG;
        $this->getQueryParam(10);
        $query = "  SELECT SQL_CALC_FOUND_ROWS c.OrganizationName,mcu.UserID,CONCAT_WS(' ',FirstName,LastName) as userName,u.AddDate,RoleName,u.isEnabled
                    FROM Clients c, MapClientUser mcu, Users u, MapUserRole mur, UserRoles ur
                    WHERE c.ID=$clientID AND c.ID = mcu.ClientID AND mcu.UserID = u.ID
                    AND mcu.ID = mur.MapClientUserID AND mur.RoleID = ur.ID
                    {$this->orderBy} {$this->limit}
                 ";
        return $this->db->getRows($query);
    }

     /**
      * get Users count
      *      
    * @access     public
    * @abstract
    * @static
    * @global     object      $APPCONFIG
    * @param      integer     $clientID
    * @return     integer     $result['cnt']
    * @deprecated
    *
    */
    
    function usersCount($clientID)
    {
        global $APPCONFIG;
        $query  = "  SELECT FOUND_ROWS() as cnt ";
        $result = $this->db->getSingleRow($query);
        return  $result['cnt'];
    }

    /**
    * Map Client and User
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instID
    * @param      integer     $instID
    * @return     integer
    * @deprecated
    *
    */
    
    function mapUser($instID, $instID)
    {
        $query   = "INSERT INTO MapClientUser
                    (ClientID, UserID, isEnabled)
                    VALUES
                  ";
        $query .= "('$instID','$userID','1'),";

        $query  = substr_replace($query, '', -1, 1);
        return $this->db->insert($query);
    }

    /**
    * Map Client Role
    *
    * @access     public
    * @abstract
    * @static
    * @global     object      $DBCONFIG
    * @param      integer     $instID
    * @param      integer     $instID
    * @return     integer
    * @deprecated
    *
    */
    
    function mapRole($instID, $roleID)
    {
            global $DBCONFIG;
            $query  = "INSERT INTO {$DBCONFIG->prefix}instrolelist
                        (InstID, RoleID, isEnabled)
                        VALUES ";
            $query .= "('$instID','$roleID','1'),";

            $query  = substr_replace($query, '', -1, 1);
            return $this->db->insert($query);
    }

    /**
    * Map Client Role
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instituteID
    * @return     array
    * @deprecated
    *
    */
    
    function info($instituteID)
    {        
        global $DBCONFIG;
        if($DBCONFIG->dbType=='Oracle'){
                $query = "  SELECT  c.* , ins.\"Institution\" FROM Clients c
                    left join Institutions ins on ins.\"ID\" = c.\"BusinessTypeID\" and ins.\"isEnabled\" = 1 WHERE c.\"ID\" = $instituteID ";
        }else{
               $query = "  SELECT  c.* , ins.Institution FROM Clients c
                    left join Institutions ins on ins.ID = c.BusinessTypeID and ins.isEnabled = '1' WHERE c.ID = $instituteID LIMIT 1 ";
        }     
        return $this->db->getSingleRow($query);
    }

    /**
    * Disable Client
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instituteID
    * @return     boolean
    * @deprecated
    *
    */
    
    function disable($instituteID)
    {
        $query = "  UPDATE Clients set isEnabled = '0' where ID = $instituteID ";
        return $this->db->execute($query);
    }

    /**
    * Enbale Client
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param      integer     $instituteID
    * @return     boolean
    * @deprecated
    *
    */
    
    function enable($instituteID)
    {
        $query = "  UPDATE Clients set isEnabled = '1' where ID = $instituteID ";
        return $this->db->execute($query);
    }

    /**
    * Get All Clients
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     array
    * @deprecated
    *
    */
    
    function all()
    {
       $query = "  SELECT ID,OrganizationName as Name FROM Clients WHERE isEnabled = '1' AND OrganizationName != '' ORDER BY Name";
       return $this->db->getRows($query);
    }

    /**
    * Get Client Report as per criteria (yearly, monthly, etc)
    *
    * @access     public
    * @abstract
    * @static
    * @global
    * @param
    * @return     array
    * @deprecated
    *
    */
    
    function getReports()
    {
        $condition  = ($this->getInput('filter') != 'all' || $this->getInput('filter') > 0)?" AND ID = {$this->getInput('filter')}":'';
        switch($this->getInput('periodicity'))
        {
           case 'yearly':
               $dateFormat  = '%Y';
           break;

           case 'monthly':
               $dateFormat  = '%b %Y';
           break;

           default:
               $dateFormat  = '%d %b %Y';
           break;
        }
        $query = "  SELECT count(ID) as count,DATE_FORMAT(AddDate,'$dateFormat') as addedDate FROM Clients WHERE isEnabled = '1'
                        AND AddDate between DATE_FORMAT(STR_TO_DATE('{$this->getInput('mbrstartdate')}','%d %M, %Y'),'%Y-%m-%d')
                        AND DATE_FORMAT(STR_TO_DATE('{$this->getInput('mbrenddate')}','%d %M, %Y'),'%Y-%m-%d') GROUP BY addedDate ORDER BY Adddate
                    ";
        return $this->db->getRows($query);
    }

  /**
    * Get last week Client count
    *
    * @access       public
    * @abstract
    * @static
    * @global       object      $DBCONFIG
    * @param
    * @return       integer
    * @deprecated
    *
    */
    
    function lastWeekCount()
    {
        global $DBCONFIG;
        $query = "  SELECT count(distinct ID) as count FROM Clients WHERE isEnabled = '1'
                        AND AddDate BETWEEN DATE_SUB(CURDATE(),INTERVAL 7 DAY) AND CURDATE()
                    ";
        return $this->db->getSingleRow($query)->count;
    }

    /**
    * Get Client List as per condition
    *
    * @access       public
    * @abstract
    * @static
    * @global
    * @param        string      $condition
    * @return       array
    * @deprecated
    *
    */
    
    function clientsList($condition='')
    {
        $condition  = ($condition != '')?$condition:'';
        $this->getQueryParam(10);
        $query      = "  SELECT SQL_CALC_FOUND_ROWS c.*, COUNT(mcu.ClientID) AS userCount FROM Clients c
                            LEFT JOIN MapClientUser mcu on c.ID = mcu.ClientID
                            WHERE   
                                c.OrganizationName != ''  $condition
                                GROUP BY c.ID
                                {$this->orderBy} {$this->limit}
                         ";
        return $this->db->getRows($query);
    }
}
?>