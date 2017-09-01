<?php

class ItemComments extends Site
{
    public $entityTypeID;
    public $entityID;
    public $bankID;
    public $asmtID;
    public $bankList;
    public $asmtList;
    public $questTypesList;
    public $sectionList;

    /**
      * constructs a new classname instance
      */

    function __construct()
    {
        parent::Site();
    }
    function getCommentsUserIdAccess( $AsmtID ){
        $query = "SELECT us.ID, us.UserName from RepositoryMembers as rm LEFT JOIN Users as us ON rm.UserID=us.ID where rm.Entityid='".$AsmtID."' AND rm.isEnabled=1 AND rm.UserID!='".$this->registry->site->session->getValue('userID')."'";
        $result = $this->db->getRows($query);
        return $result;
    }
    
    function getComments($repoID){
        $query = "select ic.ID, ic.comments, ic.UserID , ic.assignUserID , CONCAT(u.FirstName, ' ', u.LastName) as uname, CONCAT(assu.FirstName, ' ', assu.LastName) as assUname, ics.status, ics.ID as statusID, icse.severity, icse.ID as severityID, ic.AddDate
                    from ItemComments ic left join Users u on u.ID = ic.UserID
                    left join Users assu on assu.ID = ic.assignUserID
                    left join ItemCommentsStatus ics on ic.StatusID = ics.ID
                    left join ItemCommentsSeverity icse on ic.SeverityID = icse.ID
                    where ic.MapRepositoryQuestionsID = ".$repoID." and ic.isEnabled=1 and ic.ParentID=0 Order by ic.AddDate desc";
        $result = $this->db->getRows($query);
        return $result;
    } 
	
	function getRlyComments($repoID,$parentID){
        $query = "select ic.ID, ic.comments, ic.UserID , ic.assignUserID , CONCAT(u.FirstName, ' ', u.LastName) as uname, CONCAT(assu.FirstName, ' ', assu.LastName) as assUname, ics.status, ics.ID as statusID, icse.severity, icse.ID as severityID, ic.AddDate
                    from ItemComments ic left join Users u on u.ID = ic.UserID
                    left join Users assu on assu.ID = ic.assignUserID
                    left join ItemCommentsStatus ics on ic.StatusID = ics.ID
                    left join ItemCommentsSeverity icse on ic.SeverityID = icse.ID
                    where ic.MapRepositoryQuestionsID = ".$repoID." and ic.isEnabled=1 and ic.ParentID=".$parentID." Order by ic.AddDate desc";
        $result = $this->db->getRows($query);
        return $result;
    }
    
    function getStatus(){
        $query = "select * from ItemCommentsStatus ics where ics.isEnabled=1 ORDER BY ID ASC";
        $result = $this->db->getRows($query);
        return $result;
    } 
	
	function getSeverity(){
        $query = "select * from ItemCommentsSeverity ics where ics.isEnabled=1 ORDER BY ID ASC";
        $result = $this->db->getRows($query);
        return $result;
    }
    
    function saveComments($data){
        $comment_id = $this->db->insert("ItemComments", $data);
        return $comment_id;
    }
    
    function deleteComments($id){
		$chkIssueComment	=	"select ParentID from ItemComments where ID = ".$id;
		$result = $this->db->getRows($chkIssueComment);
		
		if($result[0]['ParentID']){
			$sqlUpdate = "UPDATE ItemComments SET isEnabled = 0, ModBy = ".$this->session->getValue('userID').", ModDate = '".$this->currentDate()."' WHERE ID = ".$id;
			$this->db->execute($sqlUpdate);
			
		}else{
			$sqlUpdate = "UPDATE ItemComments SET isEnabled = 0, ModBy = ".$this->session->getValue('userID').", ModDate = '".$this->currentDate()."' WHERE ParentID = ".$id;
			$this->db->execute($sqlUpdate);
			
			$sqlUpdate = "UPDATE ItemComments SET isEnabled = 0, ModBy = ".$this->session->getValue('userID').", ModDate = '".$this->currentDate()."' WHERE ID = ".$id;
			$this->db->execute($sqlUpdate);
		}
       
       
        $this->db->execute($sqlUpdate);
    }
}