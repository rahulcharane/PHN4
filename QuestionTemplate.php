<?php

class QuestionTemplate extends Site
{
    public $id;

    /**
     * constructs a new questiontemplate instance
     */
    function __construct()
    {
        parent::Site();
    }

    /**
     * gets the list of question template
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    string $filter
     * @param    integer $displayfields
     * @param    integer $resultType
     * @param    integer $entityID
     * @param    integer $entityTypeID
     * @return   array
     *
     */

    public function questionTemplate($filter = "-1",$displayfields = "-1",$resultType = 'list',$entityID='-1',$entityTypeID='-1')
    {
        global $DBCONFIG;
        if($DBCONFIG->dbType == 'Oracle')
        {
           if($entityID == '-1' && $entityTypeID =='-1')
            {
               /*  IF( $displayfields == '-1') {
                    $displayfields = '  ';
                } ELSE{
           
           $displayfields = str_replace('qt."HTMLStructure"','dbms_lob.substr(qt."HTMLStructure",4000,1) as "HTMLStructure" , dbms_lob.substr(qt."HTMLStructure",4000,4001) as "HTMLStructure1" ,dbms_lob.substr(qt."HTMLStructure",4000,8001) as "HTMLStructure2" ',$displayfields);
           $displayfields = str_replace('qt."FlashStructure"','dbms_lob.substr(qt."FlashStructure",4000,1) as "FlashStructure" , dbms_lob.substr(qt."FlashStructure",4000,4001) as "FlashStructure1" ,dbms_lob.substr(qt."FlashStructure",4000,8001) as "FlashStructure2" ',$displayfields);
           $displayfields = str_replace("''", "'", $displayfields);
           $displayfields = $displayfields. ' , ';
                }
        IF ($filter == '-1')
           $filter = ' ';
        ELSE{
           $filter = (' AND ' .$filter. ' ');
$filter = str_replace("''", "'", $filter);
        }
                $result = " SELECT {$displayfields}  mqt.\"ID\", qt.\"TemplateTitle\", tc.\"CategoryName\"
                  FROM  MapClientQuestionTemplates mqt
                  INNER JOIN  QuestionTemplates qt ON qt.\"ID\" = mqt.\"QuestionTemplateID\" AND qt.\"isEnabled\" = 1
                  LEFT JOIN TemplateCategories tc ON tc.\"ID\" = qt.\"TemplateCategoryID\" AND tc.\"isEnabled\" = 1
                  WHERE     mqt.\"isEnabled\" = 1 AND mqt.\"isActive\" = 'Y'  and mqt.\"ClientID\" = {$this->session->getValue('instID')} {$filter}";
                  if($resultType == 'details'){
                  $result =  $this->db->getSingleRow($result);
                  $result['HTMLStructure'] = $result['HTMLStructure'].$result['HTMLStructure1'].$result['HTMLStructure2'];
            $result['FlashStructure'] = $result['FlashStructure'].$result['FlashStructure1'].$result['FlashStructure2'];
            return $result;
                  }
                  else
                  {
                  $this->myDebug('$result');
                  $this->myDebug($result);
                  $result = $this->db->getRows($result);
                  $this->myDebug('$result');
                  $this->myDebug($result);
                  return $result;
                  }*/
                $result = $this->db->executeStoreProcedure('QuestionTemplateList',
                            array($this->session->getValue('instID'),$this->session->getValue('userID'),$filter ,$displayfields),$resultType);
            }
            else
            {
                $result = $this->db->executeStoreProcedure('QuestionTemplateListWithQCount',
                            array($this->session->getValue('instID'),$this->session->getValue('userID'),$entityID,$entityTypeID,$filter ,$displayfields),$resultType);
            }
        }
        else
        {
            if($entityID == '-1' && $entityTypeID =='-1')
            {
                $result = $this->db->executeStoreProcedure('QuestionTemplateList',
                array($this->session->getValue('instID'),$this->session->getValue('userID'),$filter ,$displayfields),$resultType);
            }
            else
            {
                $result = $this->db->executeStoreProcedure('QuestionTemplateListWithQCount',
                array($this->session->getValue('instID'),$this->session->getValue('userID'),$entityID,$entityTypeID,$filter ,$displayfields),$resultType);
            }
        }
       
        return $result;
    }
    
    
    /* @ advanceSearchTemplate
     * @Manish - 17-08-15
     * calling from UserController advanceSearchTemplate
     * return question Template with select options 
     * for showing teplate in advance seaarch
     */
    public function advanceSearchTemplate($filter = "-1",$displayfields = "-1",$resultType = 'list',$entityID='-1',$entityTypeID='-1')
    {
        global $DBCONFIG;
        if($DBCONFIG->dbType == 'Oracle')
        {
           if($entityID == '-1' && $entityTypeID =='-1')
            {
               
                $result = $this->db->executeStoreProcedure('QuestionTemplateList',
                            array($this->session->getValue('instID'),$this->session->getValue('userID'),$filter ,$displayfields),$resultType);
            }
            else
            {
                $result = $this->db->executeStoreProcedure('QuestionTemplateListWithQCount',
                            array($this->session->getValue('instID'),$this->session->getValue('userID'),$entityID,$entityTypeID,$filter ,$displayfields),$resultType);
            }
        }
        else
        {
            if($entityID == '-1' && $entityTypeID =='-1')
            {   
                $result = $this->db->executeStoreProcedure('QuestionTemplateList',
                array($this->session->getValue('instID'),$this->session->getValue('userID'),$filter ,$displayfields),$resultType);
            }
            else
            { 
                $result = $this->db->executeStoreProcedure('QuestionTemplateListWithQCount',
                array($this->session->getValue('instID'),$this->session->getValue('userID'),$entityID,$entityTypeID,$filter ,$displayfields),$resultType);
            }
        }
        
            //return $result;
            // Preparing question template list for advance search page
            $templateResult=$result['RS'];
            $templateStr  = "";
            $templateStr .= '<select class="e1" multiple="multiple" id="template1">';
            foreach($templateResult as $QuestionTemplate)
            {
              $templateStr .= '<option value='.$QuestionTemplate['ID'].'>'.$QuestionTemplate['CategoryName'].'</option>';
            }
            return $templateStr;
    }
    
    
    

    /**
     * gets the list of template category for entity if $entityID,$entityID is given otherwise
     * it gives list of all template category 
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param    integer $entityID
     * @param    integer $entityTypeID
     * @return   array
     *
     */
     
    public function templateCategoryList($entityID ='-1',$entityTypeID ='-1')
    {
        $result                            = $this->registry->site->db->executeStoreProcedure('TEMPLATECATEGORYLIST',array($entityID,$entityTypeID,$this->session->getValue('instID')),'nocount');
        $this->myDebug("This is Result");
        $this->myDebug($result);
//        if($entityID =='-1' && $entityTypeID =='-1')
//        {
//            $cacheCode = "var_templateCategoryList";
//            $result  =  $this->getCache($cacheCode);
//            if(empty($result)){                
//                $result = $this->db->getRows("select tpc.ID ,tpc.CategoryCode,tpc.CategoryName from TemplateCategories tpc
//                    inner join QuestionTemplates qt On qt.TemplateCategoryID= tpc.ID  and qt.isEnabled = '1'
//                    INNER JOIN MapClientQuestionTemplates mcqt ON mcqt.QuestionTemplateID = qt.ID  AND mcqt.isEnabled = '1' AND mcqt.isActive = 'Y' and  mcqt.ClientID = {$this->session->getValue('instID')}
//                    where tpc.isEnabled = '1' GROUP BY tpc.ID");
//                $this->setCache($cacheCode,$result);
//            }
//        }
//        else{            
//            $result = $this->db->getRows("select tpc.ID ,tpc.CategoryCode,tpc.CategoryName from TemplateCategories tpc
//                        inner join QuestionTemplates qt On qt.TemplateCategoryID= tpc.ID  and qt.isEnabled = '1'
//                        INNER JOIN MapClientQuestionTemplates mcqt ON mcqt.QuestionTemplateID = qt.ID  AND mcqt.isEnabled = '1' AND mcqt.isActive = 'Y' and  mcqt.ClientID = {$this->session->getValue('instID')}
//                        inner join MapQuestionTemplates mqt ON mqt.QuestionTemplateID = mcqt.ID and mqt.EntityID={$entityID} and mqt.EntityTypeID={$entityTypeID} and mqt.isEnabled = '1'
//                        where tpc.isEnabled = '1'  group by tpc.ID");
//        }        
        return $result;
    }

    /**
     * gets the all question template IDs with comma saparated 
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param
     * @return   string
     *
     */
     
    public function questionTemplateIdString()
    {
        $cacheCode = "var_questionTemplateIdString";
        $TemplateIds  =  $this->getCache($cacheCode);
        if(empty($TemplateIds)){
            $Templates  = $this->questionTemplate(-1,-1,'nocount');
            if(!empty($Templates))
            {
                foreach($Templates as $Template)
                {
                    $TemplateIds .= $Template["ID"].",";
                }
            }
            $TemplateIds    = trim($TemplateIds,",");
            $this->setCache($cacheCode,$TemplateIds);
        }


        return $TemplateIds;
    }

    /**
     * gets the list of available layout for question template
     *
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param   integer $QuestionCategoryID
     * @return   array
     *
     */
     
    public function templateLayout($QuestionCategoryID = "")
    {
        global $DBCONFIG;        
        if($QuestionCategoryID == "")
        {
            $cacheCode = "var_templateLayout";
            $result  =  $this->getCache($cacheCode);
            if(empty($result))
            {
                if ( $DBCONFIG->dbType == 'Oracle' )
                {
                    $query = "SELECT qt.\"ID\" as \"SettingName\", 
                                (SELECT count(qt1.\"TemplateGroup\") as \"LayoutCount\" from QuestionTemplates qt1 where qt1.\"TemplateGroup\" = qt.\"TemplateGroup\" ) as \"SettingValue\"
                                    FROM QuestionTemplates qt  order by \"ID\" ";
                }
                else
                {
                    $query = "SELECT qt.ID as SettingName , (select count(qt1.TemplateGroup) as LayoutCount from QuestionTemplates as qt1 where qt1.TemplateGroup = qt.TemplateGroup ) as SettingValue FROM QuestionTemplates as qt  order by ID";
                }
                $result = $this->db->getRows($query);
                $this->setCache($cacheCode,$result);
            }            
        }
        return $result;
    }
    public function MapQuestionTemplateList($ID,$eID)
    {
        $result = $this->db->executeStoreProcedure('MapQuestionTemplateList',array($ID,$eID),'nocount');
        return $result;
    }
    public function GetClientQuestionTemplates(array $input, $filter = "-1" )
    {    
		$input['pgnob'] = ($input['pgnob']!="")?$input['pgnob']:"qcount";
		$input['pgnot'] = ($input['pgnot']!="")?$input['pgnot']:"desc";
		
		$result = $this->db->executeStoreProcedure('GetClientQuestionTemplates',
                        array($input['pgnob'], $input['pgnot'],$input['pgnstart'],$input['pgnstop'],$filter,$this->session->getValue('userID'),$this->session->getValue('instID') , $input['pgndc']));
         return $result;
    }
	/**    
     *
     * @access   public
     * @abstract
     * @static
     * @global
     * @param   integer $quesTempID   //ID of MapClientQuestionTemplates table
     * @return   array
     *
     */
	public function GetQuestionTemplateID($quesTempID)
	{		
		$getTemplateQ = "SELECT qt.ID as qID, qt.* 
						FROM QuestionTemplates AS qt
						LEFT JOIN MapClientQuestionTemplates AS mcqt ON mcqt.QuestionTemplateID = qt.ID
						LEFT JOIN TemplateCategories tc ON tc.ID = qt.TemplateCategoryID AND tc.isEnabled = '1'
						WHERE qt.isEnabled =1 AND mcqt.ClientID = '".$this->session->getValue('instID')."' AND mcqt.ID = '".$quesTempID."'";
		$getTemplateR = $this->db->getSingleRow($getTemplateQ);
		return $getTemplateR;		
	}
        
        
    // Function to get template category details by template category id
    function getTemplateCatDetById($templateCatId)
    {
        $getTemplateDetQ = "SELECT * FROM TemplateCategories WHERE ID = '".$templateCatId."' AND isEnabled = 1";
        $getTemplateDetR = $this->db->getSingleRow($getTemplateDetQ);
        return $getTemplateDetR;
    }
}

?>
