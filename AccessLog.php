<?php
/**
 * This class handles all User access and navigation related queries/requests
 * This class handles the business logic of listing requests of user access and navigation 
 *
 * @access   public
 * @abstract
 * @static
 * @global
 */
class AccessLog extends Site
{
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
        parent::Site();
    }

   /**
    * gets Access log of a logged in user
    *
    *
    * @access   public
    * @param    array   $input          list of all input values
    * @return   array   $accessLogList  Access Log List
    *
    */
    public function getAccessLog($input)
    {
        global $DBCONFIG;
        Site::myDebug("--------getAccessLog");
        if ( $DBCONFIG->dbType == 'Oracle' )
        $filter = ' acl."UserID" = '.$this->session->getValue('mapUserID');
        else
        $filter = ' acl.UserID = '.$this->session->getValue('mapUserID');
        Site::myDebug( $input );

        if ( $DBCONFIG->dbType == 'Oracle' ){
        $accessLogList  = $this->db->executeStoreProcedure('AccessLogList',
                                        array( $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $this->session->getValue('mapUserID'), $input['pgndc'] ) );
        }else{
          $accessLogList  = $this->db->executeStoreProcedure('AccessLog',
                                        array( $input['pgnob'], $input['pgnot'], $input['pgnstart'], $input['pgnstop'], $filter, $this->session->getValue('mapUserID'), $input['pgndc'] ) );

        }
        return $accessLogList;
    }

   /**
    * gets Navigation log for the selected Access log 
    *
    *
    * @access   public
    * @param    array   $input       list of all input values
    * @return   array   $navLogList  list of Navigation log
    *
    */
    public function getNavLog($input)
    {
        global $DBCONFIG;
        if ( $DBCONFIG->dbType == 'Oracle' )
        $filter = ' nvl."AccessLogID" = '.$input['accessID'];
        else
        $filter = ' nvl.AccessLogID = '.$input['accessID'];

        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $navLogList  = $this->db->executeStoreProcedure('NavigationLogDetails',
                                        array( $input['pgnob'], $input['pgnot'], $input['pgnstart'],$input['pgnstop'], $filter, $this->session->getValue('mapUserID'), $input['pgndc'] ) );
        }
        else
        {
            $navLogList  = $this->db->executeStoreProcedure('NavigationLog',
                                        array( $input['pgnob'], $input['pgnot'], $input['pgnstart'],$input['pgnstop'], $filter, $this->session->getValue('mapUserID'), $input['pgndc'] ) );
        }
        return $navLogList;
    }    
}
?>