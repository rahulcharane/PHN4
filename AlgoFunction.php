<?php

/* 
 * Description about algorithmic functions
 * 
 * @author Balaram.sahu
 */
class AlgoFunction extends Site 
{
    
    function __construct() {
        parent::Site();
    }
    
    function save() {
        global $DBCONFIG;
         
    }
    
    function getFunctions() {
        global $DBCONFIG;
        
        $qry = "SELECT * FROM AlgoFunctions WHERE isEnabled = '1'";
        
        $returnData = $this->db->getRows($qry);
        return $returnData;
    }
}
?>

