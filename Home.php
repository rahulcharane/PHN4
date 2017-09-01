<?php
/**
 * This class handles all home module related queries/requests.
 * This class handles business logic of showing the listing of recent activities / recent accessed entities of an institution.
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */

class Home extends Site
{
    public $homessn;

    /**
    * Construct new home instance
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
        $this->homessn = array("ssn"=>1);
        
    }

    /**
    * gets the list users recent activities.
    *
    *
    * @access   public
    * @abstract
    * @static
    * @global
    * @param    array   $input      list of all input values.
    * @return   array   $activity   list user recent activites.
    *
    */
    public function userActivities(array $input)
    {
        ///$flag values = Self | Group
         global $DBCONFIG;
        $title      = ($input['flag']=='Action')?'Recent Actions':'Last Accessed';
        $flag       = (isset($input['flag']))?$input['flag']:"";
        $title     .= '';
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
           $displayField ='"UserName"';
        }
        else
        {
            $displayField ='UserName';
        }
        $input['pgnob'] = ($input['pgnob'] == '-1') ? (( $DBCONFIG->dbType == 'Oracle' ) ? "r.\"ActionDate\"" : "r.ActionDate") : $input['pgnob'];
        $input['pgnot'] = ($input['pgnot'] == '-1') ? 'desc' : $input['pgnot'];
        
        $activities     = (array)$this->db->executeStoreProcedure('ActivityList', array($input['pgnob'],$input['pgnot'],$input['pgnstart'],$input['pgnstop'],"-1",$this->session->getValue('userID'),$this->session->getValue('instID'),$displayField,$flag));
        $activitiesCnt  = $activities["TC"];
        $activities     = $activities["RS"];
        foreach($activities as $key => $valArr)
        {
            if($valArr['EntityTypeID'] == 3)
            {
                $questionUrl = $this->db->executeFunction('GETQUESTIONDETAILS','url',array($valArr['EntityID']));
                $activities[$key]['QuestUrl'] = ($questionUrl['url'] != '') ? $questionUrl['url'] : '';
            }
        }
        $activity = array(
                    'title' => $title,
                    'list'  => $activities,
                    'count' => $activitiesCnt
                    );
        return $activity;
    }
}
?>