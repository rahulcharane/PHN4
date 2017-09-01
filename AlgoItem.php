<?php

/* 
 * Description about algorithmic items
 * 
 * @author balaram.sahu
 */

class AlgoItem extends Site
{
    function __construct() {
        parent::Site();
    }
    
    function save(array $data) {
        global $DBCONFIG;
        
        $data = array(
                        'Name' => $data['varname'],
                        'Value' => $data['varvalue'],
                        'Tolerance'=> $data['vartolerance'],
                        'AddBy'=>$this->session->getValue('userID'),
                        'ModBy'=>$this->session->getValue('userID'),
                        'AddDate'=>$this->currentDate(),
                        'ModDate'=> $this->currentDate(),
                        'isEnabled' => '1'
                    );
        
        $status = $this->db->insert('AlgoItems',$data);
        echo $status;
    }
    
    function update(array $data, $id) {
        global $DBCONFIG;
        
        $updated_data = array(
                        'Name' => $data['varname'],
                        'Value' => $data['varvalue'],
                        'Tolerance'=> $data['vartolerance'],
                        'ModDate'=> $this->currentDate(),
                    );
        
        $where = " ID = " . $id . "";
        $returnData = $this->db->update("AlgoItems",$updated_data,$where);
        echo $returnData;
    }
    
    function getItems() {
        global $DBCONFIG;
        
        $qry = "SELECT * FROM AlgoItems WHERE isEnabled = '1'";
        
        $returnData = $this->db->getRows($qry);
        return $returnData;
    }
    
    function getItem($id) {
        global $DBCONFIG;
        
        $qry = "SELECT * FROM AlgoItems WHERE isEnabled = '1' AND ID=" . $id;
        
        $returnData = $this->db->getSingleRow($qry);
        return $returnData;
    }
    
    function remove($id) {
        global $DBCONFIG;
        
        $updated_data = array('ModDate' => $this->currentDate(),
                              'isEnabled' => '0');
        $where = " ID = " . $id . "";
        $returnData = $this->db->update("AlgoItems",$updated_data,$where);
        return $returnData;
    }
}

?>

