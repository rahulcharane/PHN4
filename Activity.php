<?php

class Activity extends Site
{
    public $homessn;

    /**
    * Construct new activity instance
    *
    * @access   public
    * @return   void
    *
    */

    public function __construct()
    {
        parent::Site();
        $this->homessn = array("ssn"=>1);
    }
    
    /**
    * search for activities by specified criteria.
    *
    *
    * @access   public
    * @param    array   $input      list of all input values.
    * @return   array   $activity   list of searched activities.
    *
    */

    public function searchActivities(array $input)
    {
        global $DBCONFIG;
        $title                  = ($input['flag']=='Action')?'Recent Actions':'Last Accessed';
        $input['entityTypeID']  = (isset($input['entityTypeID']))?$input['entityTypeID']:"-1";
        $input['accessedBy']    = (isset($input['accessedBy']))?$input['accessedBy']:"-1";
        $input['startDate']     = (isset($input['startDate']))?$input['startDate']:"-1";
        $input['endDate']       = (isset($input['endDate']))?$input['endDate']:"-1";
        $input['WithIn']        = (isset($input['WithIn']))?$input['WithIn']:"-1";
        $input['EntityName']    = (isset($input['EntityName']))?$input['EntityName']:"-1";

        $title  .= '';
        $col = ($DBCONFIG->dbType == 'Oracle' ) ? ' "UserName" ' : ' UserName ';
        $dataArr = array($input['pgnob'],
                        $input['pgnot'],
                        $input['pgnstart'],
                        $input['pgnstop'],
                        $this->session->getValue('userID'),
                        $this->session->getValue('instID'),
                        $col,
                        $input['activityType'],
                        $input['WithIn'],
                        $input['EntityName'],
                        $input['entityTypeID'],
                        $input['accessedBy'],
                        $input['startDate'],
                        $input['endDate']
                    );

        $activities     = (array)$this->db->executeStoreProcedure('ActivitySearchList', $dataArr);
        $activitiesCnt  = $activities["TC"];
        $activities     = $activities["RS"];
        $activity       = array(
                            'title' => $title,
                            'list'  => $activities,
                            'count' => $activitiesCnt
                            );
        return $activity;
    }
    public function activityList(array $input,$filter)
    {
        $dataArr =  array($input['pgnob'],
                        $input['pgnot'],
                        $input['pgnstart'],
                        $input['pgnstop'],
                        $filter,
                        $this->session->getValue('userID'),
                        $this->session->getValue('instID'),'UserName',"All");
        $activities = (array)$this->registry->site->db->executeStoreProcedure('ActivityList', $dataArr);
        return $activities["RS"];
    }
}
?>