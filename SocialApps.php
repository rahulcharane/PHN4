<?php
class SocialApps extends Site
{
    /**
      * constructs a new classname instance
      */    
    function __construct()
    {
        parent::Site();
    }

    /**
      * Manage(Add/Edit/Delete) "Published Assessment" assigned to User
      *
      *
      * @access     public
      * @abstract
      * @static
      * @global     array  	$input
      * @return     array
      *
      */
    
    function mapAssessmentSocialAppManage(array $input)
    {
        if(!isset($input["ID"]))
        {
            $dataArray = array(
                                0,
                                $input["PublishedID"],
                                $this->session->getValue('userID'),
                                $input["SAHostID"],
                                $input["SAHostName"],
                                trim($input["SAConsumerID"], ","),
                                trim($input["SAConsumerName"], ","),
                                $input["SAType"],
                                date("Y-m-d H:i:s"),
                                "Y",
                                "1"
                            );
        }
        else
        {
              $dataArray = array(
                                    $input["ID"],
                                    -1,
                                    -1,
                                    -1,
                                    -1,
                                    -1,
                                    -1,
                                    -1,
                                    date("Y-m-d H:i:s"),
                                    $input["isActive"],
                                    $input["isEnabled"]
                            );
        }
        $AssessmentDetail = $this->db->executeStoreProcedure('MapAssessmentSocialAppMange', $dataArray, "nocount");
        return $this->getValueArray($AssessmentDetail, "MapIDs");
    }

    /**
      * Get Quad Plus Gradebook Attempt List
      *
      * @access     public
      * @abstract
      * @static
      * @global     array  	$input
      * @return     array
      *
      */
    
    function qpGrdeBookAttemptList(array $input)
    {
        $query              = "SELECT qud.* FROM QPAsmtUserDetails qud
                                WHERE
                                    qud.isEnabled='1' AND
                                    qud.PublishedID='{$input["PubID"]}' AND
                                    qud.SecureCode='{$input["SCode"]}' ";
        Site::myDebug("----qpgrdebookattemptlist");
        Site::myDebug($query);
        $QPUserDetails      = $this->db->getSingleRow($query);
        
        $input["asgnmtID"]  = $QPUserDetails["AssignmentID"];
        $input["clsID"]     = $QPUserDetails["ClassID"];
        $input["usrID"]     = $QPUserDetails["UserID"];
        $input["token"]     = $QPUserDetails["ClientToken"];
        $UserDetails        = json_decode($QPUserDetails["UserInfo"]);

        $params             = '{"usrID":"'.$input["usrID"].'","asgnmtID":"'.$input["asgnmtID"].'","clsID":"'.$input["clsID"].'","token":"'.$input["token"].'"}';
        $attemptlist        = $this->process($this->cfg->wwwroot."/webservice/index/usratmlist", $params,$input["token"]);


        $params             = '{"usrID":"'.$input["usrID"].'","asgnmtID":"'.$input["asgnmtID"].'","token":"'.$input["token"].'"}';
        $assignmentdetails  = $this->process($this->cfg->wwwroot."/webservice/index/usrasmtrpt", $params,$input["token"]);
        $usercount          = $this->db->getSingleRow("select count(distinct ur.UserID) as UsrCnt  from UserReports ur
                                                    where
                                                        ur.AssignmentID='".$input["asgnmtID"]."' and
                                                        ur.isEnabled='1' ");
        $report             = array(
                                      'report'      => $attemptlist,
                                      'score'       => $assignmentdetails,
                                      'learnercount'=> $usercount['UsrCnt']
                                );
        $details            = array(
                                    "AssignmentID"      => $QPUserDetails["AssignmentID"],
                                    "SAConsumerName"    => $UserDetails->{"learner_name"},
                                    "PubID"             => $MapAsmtSADetails["PublishedID"],
                                    "SAConsumerID"      => $QPUserDetails["UserID"],
                                    "AsgmtName"         => $UserDetails->{"assignment_name"},
                                    "Owner"             => $UserDetails->{"instructor_name"},
                                    "ClassName"         => $UserDetails->{"class_name"},
                                    "InstituteName"     => $UserDetails->{"institute_name"}
                                );        

        return array(  "report" => $report,  "details" => $details );
    }

    /**
      * Get Social Apps Gradebook Attempt List
      *
      *
      * @access     public
      * @abstract
      * @static
      * @global     array  	$input
      * @return     mixed
      *
      */
    
    function grdeBookAttemptList(array $input)
    {
        $MapAsmtSADetails   =   $this->db->getSingleRow("SELECT mas.* ,pa.PublishedTitle, usr.FirstName , usr.LastName, clt.OrganizationName , mcu.ClientID
                                                        FROM MapAssessmentSocialApps mas
                                                        left join PublishAssessments pa on pa.ID = mas.PublishedID and pa.isActive = 'Y' and  pa.isEnabled = '1'
                                                        left join MapClientUser mcu on mcu.UserID = mas.UserID and mcu.isActive = 'Y' and  mcu.isEnabled = '1'
                                                        left join Clients clt on clt.ID = mcu.ClientID and clt.isEnabled = '1'
                                                        left join Users usr on usr.ID = mas.UserID and usr.isEnabled = '1'
                                                        WHERE mas.isEnabled='1' and mas.ID='{$input["AssignmentID"]}' and mas.SAConsumerID='{$input["SAConsumerID"]}' ");

        if($MapAsmtSADetails)
        {
            $details        =   array(
                                    "SAConsumerName" => $MapAsmtSADetails["SAConsumerName"],
                                    "PubID"          => $MapAsmtSADetails["PublishedID"],
                                    "SAConsumerID"   => $MapAsmtSADetails["SAConsumerID"],
                                    "AsgmtName"      => $MapAsmtSADetails["PublishedTitle"],
                                    "Owner"          => $MapAsmtSADetails["FirstName"]." ".$MapAsmtSADetails["LastName"],
                                    "ClassName"      => strtoupper($MapAsmtSADetails["SAType"]),
                                    "InstituteName"  => $MapAsmtSADetails["OrganizationName"]
                                );
            $params        = '{"usrID":"'.$MapAsmtSADetails["SAConsumerID"].'","asgnmtID":"'.$input["AssignmentID"].'","clsID":"-1","token":"90dfdb7f"}';
            $attempts      = $this->process($this->cfg->wwwroot."/webservice/index/usratmlist", $params, "90dfdb7f");
            $attemptsCount = count($attempts);
            if($attemptsCount > 0)
            {
               $status = $attempts[$attemptsCount-1]->{"AttemptStatus"};
               if($status != "Completed")
               {
                    return "Assignment is in {$attempts[$attemptsCount-1]['AttemptStatus']} status, you can not view gradebook";
               }
               elseif($status == "Completed")
               {
                    $params             = '{"usrID":"'.$MapAsmtSADetails["SAConsumerID"].'","asgnmtID":"'.$input["AssignmentID"].'","token":"90dfdb7f"}';
                    $assignmentsummary  = $this->process($this->cfg->wwwroot."/webservice/index/usrasmtrpt", $params,"90dfdb7f");
                    $usercount          =  $this->db->getSingleRow("select count(distinct ur.UserID) as UsrCnt  from UserReports ur
                                                                    where
                                                                        ur.AssignmentID='".$input["AssignmentID"]."' and
                                                                        ur.ClassID='-1' and
                                                                        ur.isEnabled='1'
                                                                    ");

                    $report             = array(
                                                  'report'      => $attempts,
                                                  'score'       => $assignmentsummary,
                                                  'learnercount'=> $usercount['UsrCnt']
                                            );
                return array(   "report"    => $report,
                                "details"   => $details
                            );
               }
            }
            else
            {
               return "Yet, You have not attempted Assignment.";
            }
        }
        else
        {
            return "You have no access for mentioned assignment";
        }
    }
    
    /**
      * Get User details of the "Published Assessment"
      *
      *
      * @access     public
      * @abstract
      * @static
      * @global     array  	$input
      * @return     array       
      *
      */
    
    function getUserDetailGradeBook(array $input)
    {
        if (isset($input['target']) && $input['target'] == "QP" )
        {
            $query          = "SELECT qud.* FROM QPAsmtUserDetails qud
                                WHERE
                                    qud.isEnabled='1' AND
                                    qud.UserID='{$input["usrID"]}' AND
                                    qud.AssignmentID='{$input["asgnmtID"]}' ";
             $QPUserDetails = $this->db->getSingleRow($query);
             $UserDetails   = json_decode($QPUserDetails["UserInfo"]);

             $details       = array(
                                    "AssignmentID"      => $QPUserDetails["AssignmentID"],
                                    "SAConsumerName"    => $UserDetails->{"learner_name"},
                                    "PubID"             => $MapAsmtSADetails["PublishedID"],
                                    "SAConsumerID"      => $QPUserDetails["UserID"],
                                    "AsgmtName"         => $UserDetails->{"assignment_name"},
                                    "Owner"             => $UserDetails->{"instructor_name"},
                                    "ClassName"         => $UserDetails->{"class_name"},
                                    "InstituteName"     => $UserDetails->{"institute_name"}
                                );
        }
        else
        {
            $MapAsmtSADetails   = $this->db->getSingleRow("SELECT mas.* ,pa.PublishedTitle, usr.FirstName , usr.LastName, clt.OrganizationName , mcu.ClientID  FROM MapAssessmentSocialApps mas
                                                                left join PublishAssessments pa on pa.ID = mas.PublishedID and pa.isActive = 'Y' and  pa.isEnabled = '1'
                                                                left join MapClientUser mcu on mcu.UserID = mas.UserID and mcu.isActive = 'Y' and  mcu.isEnabled = '1'
                                                                left join Clients clt on clt.ID = mcu.ClientID and clt.isEnabled = '1'
                                                                left join Users usr on usr.ID = mas.UserID and usr.isEnabled = '1'
                                                                WHERE mas.isEnabled='1' and mas.ID='{$input["asgnmtID"]}' ");
            if($MapAsmtSADetails)
            {
                $details        = array(
                                        "SAConsumerID"      => $MapAsmtSADetails["SAConsumerID"],
                                        "SAConsumerName"    => $MapAsmtSADetails["SAConsumerName"],
                                        "AsgmtName"         => $MapAsmtSADetails["PublishedTitle"],
                                        "Owner"             => $MapAsmtSADetails["FirstName"]." ".$MapAsmtSADetails["LastName"],
                                        "ClassName"         => strtoupper($MapAsmtSADetails["SAType"]),
                                        "InstituteName"     => $MapAsmtSADetails["OrganizationName"]
                                    );
            }
        }
        $body = '{"usrID":"'.$input["usrID"].'","asgnmtID":"'.$input["asgnmtID"].'","reportID":"'.$input["reportID"].'","token":"90dfdb7f"}';
        return array(   "details"       => $details,
                        "reportDetails" => $this->process($this->cfg->wwwroot."/webservice/index/detailgrdbook", $body, "90dfdb7f")
                    );
    }

    /**
      * Get Social Apps Gradebook details of the "Published Assessment"
      *
      *
      * @access     public
      * @abstract
      * @static
      * @global     array  	$input
      * @return     string(XML) $sasharedlist
      *
      */
    
    function pubAsmtGradebook(array $input)
    {
        $MapAsmtSAList  = $this->db->getRows("SELECT mas.* , pa.PublishedTitle, if(ur.AttemptStatus is null,'Pending',
                                                ur.AttemptStatus) as AttemptStatus, count(ur.ID) as TotalAttempts ,  max(ur.UserScore) as Highest ,
                                                ur.TotalScore , min(ur.UserScore) as Lowest , avg(ur.TimeTaken) as AvgTime , ur.FinishDate , ur.StartDate
                                                FROM MapAssessmentSocialApps mas
                                                left join PublishAssessments pa on pa.ID = mas.PublishedID and  pa.isEnabled = '1'
                                                left join UserReports ur on  ur.AssignmentID = mas.ID and ur.PublishedID = mas.PublishedID and ur.UserID = mas.SAConsumerID  and ur.isEnabled = '1' and ur.ClassID = '-1'
                                                left join MapClientUser mcu on mcu.UserID = mas.UserID and mcu.isActive = 'Y' and  mcu.isEnabled = '1'
                                                left join Users usr on usr.ID = mas.UserID and usr.isEnabled = '1'
                                                WHERE mas.isEnabled='1' and mas.PublishedID='{$input["PublishedID"]}' and mas.SAType = '{$input["SAType"]}'
                                                group by mas.ID order by mas.ID asc, ur.FinishDate desc, ur.StartDate desc");
        //$sasharedlist   = "<socialapps>";
		$cnt = sizeof($MapAsmtSAList);
		$json = "[";
        if($MapAsmtSAList)
        {
            $i=0;
            foreach($MapAsmtSAList as $perma)
            {
                $i++;
                $lattemptdate = "";
                if($perma["AttemptStatus"] == "Completed")
                {
                    $lattemptdate = date("F j, Y, g:i a", strtotime($perma["FinishDate"]));
                }
                elseif($perma["AttemptStatus"] == "Incomplete")
                {
                    $lattemptdate = date("F j, Y, g:i a", strtotime($perma["StartDate"]));
                }
				/*
                $sasharedlist .= "<socialapp><srno>{$i}</srno>
                                    <said>{$perma["ID"]}</said>
                                    <saconsumerid>{$perma["SAConsumerID"]}</saconsumerid>
                                    <satype>{$perma["SAType"]}</satype>
                                    <saconsumername>{$perma["SAConsumerName"]}</saconsumername>
                                    <date>".date("F j, Y, g:i a", strtotime( $perma["ModDate"] ) )."</date>
                                    <isactive>{$perma["isActive"]}</isactive>
                                    <attemptstatus>{$perma["AttemptStatus"]}</attemptstatus>
                                    <totalattempts>{$perma["TotalAttempts"]}</totalattempts>
                                    <highest>".(($perma["Highest"])?$perma["Highest"]:0)."</highest>
                                    <lowest>".(($perma["Lowest"])?$perma["Lowest"]:0)."</lowest>
                                    <avgtime>{$this->sec2hms($perma["AvgTime"])}</avgtime>
                                    <lattemptdate>{$lattemptdate}</lattemptdate>
                                    <totalscore>{$perma["TotalScore"]}</totalscore>
                                  </socialapp>";*/
				$json .= "{
                              \"item\":{
                                    \"srno\":\"{$i}\",
                                    \"said\":\"{$perma["ID"]}\",
                                    \"saconsumerid\":\"{$perma["SAConsumerID"]}\",
                                    \"satype\":\"{$perma["SAType"]}\",
                                    \"saconsumername\":\"{$perma['SAConsumerName']}\",
                                    \"date\":\"".date("F j, Y, g:i a", strtotime( $perma["ModDate"] ) )."\",
                                    \"isactive\":\"{$perma['isActive']}\",
                                    \"attemptstatus\":\"{$perma['AttemptStatus']}\",
                                    \"totalattempts\":\"{$perma['TotalAttempts']}\",
                                    \"highest\":\"".(($perma["Highest"])?$perma["Highest"]:0)."\",
                                    \"lowest\":\"".(($perma["Lowest"])?$perma["Lowest"]:0)."\",
                                    \"avgtime\":\"{$this->sec2hms($perma["AvgTime"])}\",
                                    \"lattemptdate\":\"{$lattemptdate}\",
                                    \"totalscore\":\"{$perma['TotalScore']}\"
                              }
                        }";
                if($i < $cnt) { $json   .= ','; }
            }
        }
		 else {
            $json .= "{
                            \"item\":{
                                \"srno\":\"rrr\",
                                \"said\":\"rrrrrr\",
                                \"saconsumerid\":\"tttt\",
                                \"satype\":\"ewew\",
                                \"saconsumername\":\"ghgh\",
                                \"date\":\"aswdasd\",
                                \"isactive\":\"sdasd\",
                                \"attemptstatus\":\"sadasd\",
                                \"totalattempts\":\"sdasd\",
                                \"highest\":\"sdasd\",
                                \"lowest\":\"sdasd\",
                                \"avgtime\":\"sdasd\",
                                \"lattemptdate\":\"sdgsdfg\",
                                \"totalscore\":\"kjljkl\",
                                \"date\":\"kl;jkl\"
                            }
                    }";
		}
        //$sasharedlist    .= "</socialapps>";
        //return $sasharedlist;
		$json   .= ']';
		$jsonresponse = "{\"results\":{$json}, \"count\":1}";
		return $jsonresponse;
    }
}
?>