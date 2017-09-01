<?php
class TemplateBuilder extends Site
{
    function __construct()
    {
        parent::Site();
    }
    
    function getTemplateCategory()
    {
        $query = "SELECT ID, CategoryCode, CategoryName FROM `TemplateCategories` WHERE isEnabled = 1";
        $result = $this->db->getRows($query);
        return $result;
    }
}
?>