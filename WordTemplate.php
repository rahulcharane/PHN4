<?php
class WordTemplate extends Site
{
    /**
      * constructs a new classname instance
      */
    function __construct()
    {
        parent::Site();
    }
     function WTLogin($input)
    {
        return $this->layout->login($input['username'],$input['password']);
    }

    function questionList($input)
    {
       global $DBCONFIG;
       $filter    .= '-1';
       $tokenId         = $input['accessToken'];
       $tokenCode       = $input['accessLogID'];
       $transactionID   = $input['transactionID'];
        if($DBCONFIG->dbType=='Oracle')
        {
            $questions  = $this->db->executeStoreProcedure('WTQuestionList',array('-1','-1','-1','-1',$filter,$tokenId,$transactionID,' wtq."Title",wtq."XMLData" , wtq."JSONData" , qtp."HTMLTemplate" , qtp."RenditionMode", qtp."isStatic" , tpc."CategoryCode" '));
        }else{
            $questions  = $this->db->executeStoreProcedure('WTQuestionList',array('-1','-1','-1','-1',$filter,$tokenId,$transactionID,' wtq.Title,wtq.XMLData , wtq.JSONData , qtp.HTMLTemplate , qtp.RenditionMode, qtp.isStatic , tpc.CategoryCode '));
        }
       return $questions;
    }
    
     public function processQuestions($input)
    {
        try
        {
            $input['XMLInput'] = (!empty($input['XMLInput']))?$input['XMLInput']:'<root><screen template_id=\"MCSSText\" screen_id=\"90524c5c\"  question_id=\"111\" repository_id=\"123\"  ><table rows=\"12\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">90524c5c</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">mcss_text</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Multiple choice question, single answer â€“ text with no image</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is trial title</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is trial text</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer Choices</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"3\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Choices</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer (Type 1 for correct and 0 for incorrect) One correct answer</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Specific Reason Feedback (Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>One</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Two</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Three</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Four</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Good answer</span><br/></para></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Bad answer</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Ok </span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>asdasdadasd</span><br/></para></col></row></table_details></table>
	</screen><screen template_id=\"MCMSText\" screen_id=\"cc54fad1\"><table rows=\"13\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">cc54fad1</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">mcms_text</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Multiple choice question, multiple answers â€“ text with no image</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is MCMS Question</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is MCMS Question text</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer Choices</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"3\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Choices</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer (Type 1 for correct and 0 for incorrect) Minimum of two correct</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Specific Reason Feedback (Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>Choice 1</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Choice 2</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Choice 3</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Choice 4</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Great</span><br/></para></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Partially Correct Feedback (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Good</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Bad </span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Hint</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"13\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sadasd ad</span><br/></para></col></row></table_details></table>
	</screen><screen template_id=\"SortingContainers\" screen_id=\"25f50856\"><table rows=\"14\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">25f50856</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">sorting_container</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Sorting a stack of options into containers </span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sorting container Question</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sorting container Title</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Heading</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"2\" cols=\"4\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container One</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Two</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Three</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col><col position=\"4\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Four</span><span>
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>State</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>capital</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Text</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"4\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container One</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Two</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Three</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col><col position=\"4\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Four</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>Maharashtra</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Mumbai</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Karnataka</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Chenai</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Gujarat</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Surat</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Panjab</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Chindighadh</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Well done</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Partially Correct Feedback (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Ok good effort</span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Great</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Check maps</span><br/></para></col></row><row position=\"13\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"14\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Check all correct.</span><br/></para></col></row></table_details></table>
	</screen></root>';
            $input['XMLInput'] = str_replace("\r\n",'',  $input['XMLInput']);
            global $APPCONFIG,$CONFIG, $DBCONFIG;
            $this->myDebug("Input values");
            $this->myDebug($input);
            $qtp                = new QuestionTemplate();
            $qst                = new Question();
            $auth               = new Authoring();
            $tokenId            = $input['accessLogID'];
            $tokenCode          = $input['accessToken'];
            $this->myDebug("Token ID1");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code1");
            $this->myDebug($tokenCode);
            $transactionID      = ($input['transactionID']!="")?$input['transactionID']:rand(0, 999999);
            if($DBCONFIG->dbType=='Oracle')
            {
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
            }else{
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
            }
         
            $this->user_info    = json_decode($tokenInfo['UserInfo']);
            $sXMLInput          = ($input['XMLInput']!="")?$input['XMLInput']:"";
            $this->myDebug($this->user_info);
            $this->myDebug('::Processing Start::');
            $this->myDebug("Input XML");
            $this->myDebug($sXMLInput);
            if($sXMLInput!="")
            {
                $sXMLInput = ($this->validateXml($sXMLInput))? $sXMLInput : stripslashes($sXMLInput);
                $this->myDebug($sXMLInput);

                $ipDoc  = simplexml_load_string($sXMLInput);
                $i      = 1;
                $this->myDebug($ipDoc);
                $checkinlog = '';
                if($ipDoc)
                {
                    foreach($ipDoc->{'screen'} as $objQuestion)
                    {
                        $questtitle = '';
                        list($node) = $objQuestion->xpath("table/table_details/row[@position='4']/col[@position='2']/para");
                        // commeneted as getting &quote as &amp;quote
                        // $questtitle = $auth->cleanQuestionTitle($auth->getRowTextFromWord($node,true)); 
                        $questtitle = $auth->getRowTextFromWord($node,true);
                        $this->myDebug("------Question Title");
                        $this->myDebug($questtitle);
                        try
                        {
                            // $RepositoryID = $QuestionID =  0;

                            list($node) = $objQuestion->xpath("table/table_details/row[@position='4']/col[@position='2']/para");
                            $this->myDebug('This is');
                            $this->myDebug($node);
                            $questtitle = $auth->getRowTextFromWord($node,true);

                            //Get Question Type by Shortname
                            $sLayoutID          = $this->getAttribute($objQuestion,'template_id');
                            $this->myDEbug("--------------This is Layout ID---");
                            $this->myDEbug($questtitle);
                            $this->myDEbug($sLayoutID);
                            if ( $sLayoutID )
                            {
                                $this->myDEbug("This is Layout ID---");
                                $this->myDEbug($questtitle);
                                $this->myDEbug($sLayoutID);

                                if($DBCONFIG->dbType=='Oracle'){
                                    $QuestionTemplate   =  $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$sLayoutID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"EditMode\" , qt.\"isStatic\" ",'details');
                                }else{
                                    $QuestionTemplate   =  $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$sLayoutID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.EditMode , qt.isStatic ",'details');
                                }
                              $this->myDEbug("This is Question templates---");
                                $this->myDEbug($QuestionTemplate);
                                $EditMode           = $QuestionTemplate['EditMode'];
                                $QTypeShortName     = $QuestionTemplate['CategoryCode'];
                                $isStatic           = $QuestionTemplate['isStatic'];
                                $QuestionTemplateID = $QuestionTemplate['ID'];

                                $questIDfromXml     = $this->getAttribute($objQuestion,'screen_id');

                                $QuestionID         = ( ctype_digit($questIDfromXml) ) ? $questIDfromXml : 0;

                                $this->myDebug('---------------Testing---------------'.$questIDfromXmlnew);
                                $xml_to_insert = $objQuestion->asXML();
                                //Also Remove Tag PerNode First and then do the Below Remove Span Mass Level as the XSLT does not look for span anymore
                                $this->mydebug("New XMl");
                                $this->mydebug($xml_to_insert);
                                $xml_to_insert = $auth->spanRemoveEachNode($xml_to_insert, ' ');
                                //for Static Page offline without title
                                if($isStatic == 'Y')
                                {
                                    $questtitle = 'Static Page offline - '.date('F j, Y, g:i a');
                                }
                                 $this->myDebug("-------------XML----------");
                                 $this->myDebug($xml_to_insert);
                                 $this->myDebug("------------End------------");

                                //for Updating Question Content

                                $xml_to_insert = $qst->addMediaPlaceHolder($xml_to_insert);
                                $json_to_insert     = $auth->getXmlToJson(0,$qst->removeMediaPlaceHolder($xml_to_insert),$QuestionTemplateID);
                                    Site::myDebug('--Word Rashmita12');
                             Site::myDebug($xml_to_insert);
                                $questiontxml   = $xml_to_insert;
                                $questionjson   = $qst->addMediaPlaceHolder(html_entity_decode($json_to_insert));

                                if($DBCONFIG->dbType=='Oracle')
                                {
                                    $questionjson = str_replace("\'", "''", addslashes(stripslashes($questionjson))) ;
                                    $questiontxml = str_replace("\'", "''", addslashes(stripslashes($questiontxml))) ;
                                }
                                // html_entity_decode added to support DQ and SQ
                                $data = array(
                                            'Title'              => html_entity_decode($questtitle),
                                            'JSONData'           => $questionjson,
                                            'XMLData'            => $questiontxml,
                                            'TokenID'            => $tokenId,
                                            'transactionID'      => $transactionID,
                                            'QuestionTemplateID' => $QuestionTemplateID,
                                            'AddDate'            => $this->currentDate(),
                                            'ModDate'            => $this->currentDate(),
                                            'QuestionTemplateID' => $QuestionTemplateID,
                                            'QuestionID'         => $QuestionID,
                                            'UserID'             => $this->user_info->userID,
                                            'ClientID'           => $this->user_info->instId
                                    );
                                 $this->Mydebug("JSON Conversionrash");
                                    $this->Mydebug($data);

                                                                       $res        = $this->db->executeClobProcedure('AddWordTemplateQuest',$data,'nocount');
                                if($DBCONFIG->dbType=='Oracle'){

                                            $QuestionList   =   $this->db->getRows("SELECT \"ID\" as \"id\" ,\"Title\" as \"title\" FROM WordTemplateQuestions WHERE \"TransactionID\" = ".$transactionID);
                                }else{
    //                                    $this->db->insert('WordTemplateQuestions',	$data);
                                            $QuestionList   =   $this->db->getRows("SELECT ID as id ,Title as title FROM WordTemplateQuestions WHERE transactionID = ".$transactionID);
                                }
                            }
                            else
                            {
                               if($DBCONFIG->dbType=='Oracle'){
								$QuestionTemplate   =  $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$sLayoutID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"EditMode\" , qt.\"isStatic\" ",'details');
							}else{
								$QuestionTemplate   =  $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$sLayoutID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.EditMode , qt.isStatic ",'details');
							}
						  $this->myDEbug("This is Question templates---");
							$this->myDEbug($QuestionTemplate);
							$EditMode           = $QuestionTemplate['EditMode'];
							$QTypeShortName     = $QuestionTemplate['CategoryCode'];
							$isStatic           = $QuestionTemplate['isStatic'];
							$QuestionTemplateID = $QuestionTemplate['ID'];

							$questIDfromXml     = $this->getAttribute($objQuestion,'screen_id');

							$QuestionID         = ( ctype_digit($questIDfromXml) ) ? $questIDfromXml : 0;

							$this->myDebug('---------------Testing---------------'.$questIDfromXmlnew);
							$xml_to_insert = $objQuestion->asXML();
							//Also Remove Tag PerNode First and then do the Below Remove Span Mass Level as the XSLT does not look for span anymore
							$this->mydebug("New XMl");
							$this->mydebug($xml_to_insert);
							$xml_to_insert = $auth->spanRemoveEachNode($xml_to_insert, ' ');
							//for Static Page offline without title
							if($isStatic == 'Y')
							{
								$questtitle = 'Static Page offline - '.date('F j, Y, g:i a');
							}
							 $this->myDebug("-------------XML----------");
							 $this->myDebug($xml_to_insert);
							 $this->myDebug("------------End------------");

							//for Updating Question Content

							$xml_to_insert = $qst->addMediaPlaceHolder($xml_to_insert);

							$json_to_insert     = $auth->getXmlToJsonForPegasus(0,$qst->removeMediaPlaceHolder($xml_to_insert),$QuestionTemplateID);

							Site::myDebug('--Word Rashmita1212');
					 Site::myDebug($xml_to_insert);
							$questiontxml   = $xml_to_insert;
							$questionjson   = $qst->addMediaPlaceHolder(html_entity_decode($json_to_insert));
							if($DBCONFIG->dbType=='Oracle')
							{
								$questionjson = str_replace("\'", "''", addslashes(stripslashes($questionjson))) ;
								$questiontxml = str_replace("\'", "''", addslashes(stripslashes($questiontxml))) ;
							}
				//$questionjson=json_encode($questionjson);
							$data = array(
										'Title'              => html_entity_decode($questtitle),
										'JSONData'           => $questionjson,
										'XMLData'            => $questiontxml,
										'TokenID'            => $tokenId,
										'transactionID'      => $transactionID,
										'QuestionTemplateID' => $QuestionTemplateID,
										'AddDate'            => $this->currentDate(),
										'ModDate'            => $this->currentDate(),
										'QuestionTemplateID' => $QuestionTemplateID,
										'QuestionID'         => $QuestionID,
										'UserID'             => $this->user_info->userID,
										'ClientID'           => $this->user_info->instId
								);


							 $this->Mydebug("JSON Conversionpegras");
								$this->Mydebug($data);

								   $res        = $this->db->executeClobProcedure('AddWordTemplateQuest',$data,'nocount');
							if($DBCONFIG->dbType=='Oracle'){

										$QuestionList   =   $this->db->getRows("SELECT \"ID\" as \"id\" ,\"Title\" as \"title\" FROM WordTemplateQuestions WHERE \"TransactionID\" = ".$transactionID);
							}else{
//                                    $this->db->insert('WordTemplateQuestions',	$data);
										$QuestionList   =   $this->db->getRows("SELECT ID as id ,Title as title FROM WordTemplateQuestions WHERE transactionID = ".$transactionID);
							}

//                            $questions = array();
//                            foreach($QuestionList as $key=>$row)
//                            {
//                                $question['question'] =  $row;
//                                array_push($questions, $question);
//                            }
                            }




//                            $questions = array();
//                            foreach($QuestionList as $key=>$row)
//                            {
//                                $question['question'] =  $row;
//                                array_push($questions, $question);
//                            }
                        }
                        catch(exception $ex)
                        {
                            $this->myDebug('---------------Exception start---------------');
                            $this->myDebug($ex);
                            $this->myDebug('---------------Exception end---------------');
                            $i++;
                            continue;
                        }
                    }
                }
            }else
            {
                $errorMsg = "Please Add Questions";
            }
            $this->myDebug("Token ID2");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code2");
            $this->myDebug($tokenCode);
            $this->myDebug($questions);
           return   array('accessLogID'=>$tokenId,'accessToken'=>$tokenCode ,'transactionID'=>$transactionID,'questions'=>$QuestionList,'errorMsg'=>$errorMsg);
        }
        catch(exception $ex)
        {
            $this->myDebug("---------------Exception start---------------");
            $this->myDebug($ex);
            $this->myDebug('---------------Exception end---------------');
        }
    }
           
    public function processQuestionForPegasus($input)
    {        
        try
        {                                                                                                    
            
            $input['XMLInput'] = str_replace("\r\n",'',  $input['XMLInput']);
            global $APPCONFIG,$CONFIG, $DBCONFIG;
            $this->myDebug("Input values");
            $this->myDebug($input);
            $qtp                = new QuestionTemplate();
            $qst                = new Question();
            $auth               = new Authoring();
            $tokenId            = $input['accessLogID'];
            $tokenCode          = $input['accessToken'];
            $this->myDebug("Token ID1");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code1");
            $this->myDebug($tokenCode);
            $transactionID      = ($input['transactionID']!="")?$input['transactionID']:rand(0, 999999);
            if($DBCONFIG->dbType=='Oracle')
            {
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
            }else{
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
            }
         
            $this->user_info    = json_decode($tokenInfo['UserInfo']);
            $sXMLInput          = ($input['XMLInput']!="")?$input['XMLInput']:"";
            $this->myDebug($this->user_info);
            $this->myDebug('::Processing Start::');
            $this->myDebug("Input XML");
            $this->myDebug($sXMLInput);
            if($sXMLInput!="")
            {
                $sXMLInput = ($this->validateXml($sXMLInput))? $sXMLInput : stripslashes($sXMLInput);
                $this->myDebug($sXMLInput);

                $ipDoc  = simplexml_load_string($sXMLInput);
                $i      = 1;
                $this->myDebug($ipDoc);
                $checkinlog = '';
            
                {
                    foreach($ipDoc->{'screen'} as $objQuestion)
                    {
                      
                        $questtitle = '';
                        list($node) = $objQuestion->xpath("table/table_details/row[@position='2']/col[@position='2']/para");
                        $questtitle = $auth->cleanQuetionTitle($auth->getRowTextFromWord($node,true));
                       
                        $this->myDebug("Question Title");
                        $this->myDebug($questtitle);
						if(trim($questtitle) != '')
						{
							try
							{
								
								// $RepositoryID = $QuestionID =  0;
	
								list($node) = $objQuestion->xpath("table/table_details/row[@position='2']/col[@position='2']/para");
								$this->myDebug('This is');
								$this->myDebug($node);
								$questtitle = $auth->getRowTextFromWord($node,true);
	
								//Get Question Type by Shortname
								$sLayoutID          = $this->getAttribute($objQuestion,'template_id');                                   
								
								$this->myDEbug("This is Layout ID---");
								$this->myDEbug($questtitle);
								$this->myDEbug($sLayoutID);
								
								if($DBCONFIG->dbType=='Oracle'){
									$QuestionTemplate   =  $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$sLayoutID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"EditMode\" , qt.\"isStatic\" ",'details');
								}else{
									$QuestionTemplate   =  $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$sLayoutID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.EditMode , qt.isStatic ",'details');
								}
							  $this->myDEbug("This is Question templates---");
								$this->myDEbug($QuestionTemplate);
								$EditMode           = $QuestionTemplate['EditMode'];
								$QTypeShortName     = $QuestionTemplate['CategoryCode'];
								$isStatic           = $QuestionTemplate['isStatic'];
								$QuestionTemplateID = $QuestionTemplate['ID'];
	
								$questIDfromXml     = $this->getAttribute($objQuestion,'screen_id');
	
								$QuestionID         = ( ctype_digit($questIDfromXml) ) ? $questIDfromXml : 0;   
								
								$this->myDebug('---------------Testing---------------'.$questIDfromXmlnew);                            
								$xml_to_insert = $objQuestion->asXML();
								//Also Remove Tag PerNode First and then do the Below Remove Span Mass Level as the XSLT does not look for span anymore
								$this->mydebug("New XMl");
								$this->mydebug($xml_to_insert);
								$xml_to_insert = $auth->spanRemoveEachNode($xml_to_insert, ' ');
								//for Static Page offline without title
								if($isStatic == 'Y')
								{
									$questtitle = 'Static Page offline - '.date('F j, Y, g:i a');
								}
								 $this->myDebug("-------------XML----------");
								 $this->myDebug($xml_to_insert);
								 $this->myDebug("------------End------------");
	
								//for Updating Question Content
								
								$xml_to_insert = $qst->addMediaPlaceHolder($xml_to_insert);
								
								$json_to_insert     = $auth->getXmlToJsonForPegasus(0,$qst->removeMediaPlaceHolder($xml_to_insert),$QuestionTemplateID);
								
								Site::myDebug('--Word Rashmita1212');
                         Site::myDebug($xml_to_insert);
								$questiontxml   = $xml_to_insert;
								$questionjson   = $qst->addMediaPlaceHolder(html_entity_decode($json_to_insert));
								if($DBCONFIG->dbType=='Oracle')
								{
									$questionjson = str_replace("\'", "''", addslashes(stripslashes($questionjson))) ;
									$questiontxml = str_replace("\'", "''", addslashes(stripslashes($questiontxml))) ;
								}
					//$questionjson=json_encode($questionjson);
								$data = array(
											'Title'              => $questtitle,    
											'JSONData'           => $questionjson,  
											'XMLData'            => $questiontxml,  
											'TokenID'            => $tokenId,       
											'transactionID'      => $transactionID, 
											'QuestionTemplateID' => $QuestionTemplateID,
											'AddDate'            => $this->currentDate(),
											'ModDate'            => $this->currentDate(),
											'QuestionTemplateID' => $QuestionTemplateID,    
											'QuestionID'         => $QuestionID, 
											'UserID'             => $this->user_info->userID,
											'ClientID'           => $this->user_info->instId
									);
								
									
								 $this->Mydebug("JSON Conversionpegras");
									$this->Mydebug($data);
									
									   $res        = $this->db->executeClobProcedure('AddWordTemplateQuest',$data,'nocount');
								if($DBCONFIG->dbType=='Oracle'){
								  
											$QuestionList   =   $this->db->getRows("SELECT \"ID\" as \"id\" ,\"Title\" as \"title\" FROM WordTemplateQuestions WHERE \"TransactionID\" = ".$transactionID);
								}else{
	//                                    $this->db->insert('WordTemplateQuestions',	$data);
											$QuestionList   =   $this->db->getRows("SELECT ID as id ,Title as title FROM WordTemplateQuestions WHERE transactionID = ".$transactionID);
								}
	
	//                            $questions = array();
	//                            foreach($QuestionList as $key=>$row)
	//                            {
	//                                $question['question'] =  $row;
	//                                array_push($questions, $question);
	//                            }
							}
                        	catch(exception $ex)
							{
								$this->myDebug('---------------Exception start---------------');
								$this->myDebug($ex);
								$this->myDebug('---------------Exception end---------------');
								$i++;
								continue;
							}
						}
                        
                    }
                }
            }else
            {
                $errorMsg = "Please Add Questions";
            }
            $this->myDebug("Token ID2");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code2");
            $this->myDebug($tokenCode);
            $this->myDebug($questions);
           return   array('accessLogID'=>$tokenId,'accessToken'=>$tokenCode ,'transactionID'=>$transactionID,'questions'=>$QuestionList,'errorMsg'=>$errorMsg);
        }
        catch(exception $ex)
        {
            $this->myDebug("---------------Exception start---------------");
            $this->myDebug($ex);
            $this->myDebug('---------------Exception end---------------');
        }
    }
    
    
    /* public function processQuestions($input)
    {
        
        try
        {
            /*$input['XMLInput'] = (!empty($input['XMLInput']))?$input['XMLInput']:'<root><screen template_id=\"MCSSText\" screen_id=\"90524c5c\"  question_id=\"111\" repository_id=\"123\"  ><table rows=\"12\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">90524c5c</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">mcss_text</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Multiple choice question, single answer â€“ text with no image</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is trial title</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is trial text</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer Choices</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"3\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Choices</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer (Type 1 for correct and 0 for incorrect) One correct answer</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Specific Reason Feedback (Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>One</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Two</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Three</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Four</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Good answer</span><br/></para></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Bad answer</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Ok </span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>asdasdadasd</span><br/></para></col></row></table_details></table>
	</screen><screen template_id=\"MCMSText\" screen_id=\"cc54fad1\"><table rows=\"13\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">cc54fad1</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">mcms_text</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Multiple choice question, multiple answers â€“ text with no image</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is MCMS Question</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>This is MCMS Question text</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer Choices</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"3\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Choices</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Answer (Type 1 for correct and 0 for incorrect) Minimum of two correct</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Specific Reason Feedback (Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>Choice 1</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Choice 2</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Choice 3</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>1</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Choice 4</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>0</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Great</span><br/></para></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Partially Correct Feedback (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Good</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Bad </span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Hint</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"13\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sadasd ad</span><br/></para></col></row></table_details></table>
	</screen><screen template_id=\"SortingContainers\" screen_id=\"25f50856\"><table rows=\"14\" cols=\"2\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Screen ID</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span style=\"color: #000000;font-family: Tahoma;font-size:  9pt\">25f50856</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Template ID</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">sorting_container</span><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Note to Author</span><br/></para></col><col position=\"2\" editable=\"0\"><para><ol><li style=\"LIST-STYLE-TYPE: disc\"><span>Sorting a stack of options into containers </span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>Fill in the white cells, some are optional. Do not modify the shaded cells.</span></li><li style=\"LIST-STYLE-TYPE: disc\"><span>The Question Title doesn\'t appear on screen. It\'s a shortened version of the question, used for search purposes.</span></li></ol><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Title</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sorting container Question</span><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Question Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Sorting container Title</span><br/></para></col></row><row position=\"6\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Instruction Text</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Instruction text</span><br/></para></col></row><row position=\"7\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Heading</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"2\" cols=\"4\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container One</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Two</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Three</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col><col position=\"4\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Four</span><span>
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>State</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>capital</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"8\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Text</span><br/></para></col><col position=\"2\" editable=\"1\"><table rows=\"5\" cols=\"4\"><table_details><row position=\"1\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container One</span><br/></para></col><col position=\"2\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Two</span><br/></para></col><col position=\"3\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Three</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col><col position=\"4\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Container Four</span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">
        </span><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">(Optional)</span><br/></para></col></row><row position=\"2\"><col position=\"1\" editable=\"1\"><para><span>Maharashtra</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Mumbai</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"3\"><col position=\"1\" editable=\"1\"><para><span>Karnataka</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Chenai</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"4\"><col position=\"1\" editable=\"1\"><para><span>Gujarat</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Surat</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row><row position=\"5\"><col position=\"1\" editable=\"1\"><para><span>Panjab</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Chindighadh</span><br/></para></col><col position=\"3\" editable=\"1\"><para><br/></para></col><col position=\"4\" editable=\"1\"><para><br/></para></col></row></table_details></table></col></row><row position=\"9\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Correct Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Well done</span><br/></para></col></row><row position=\"10\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Partially Correct Feedback (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Ok good effort</span><br/></para></col></row><row position=\"11\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">General Incorrect Feedback</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Great</span><br/></para></col></row><row position=\"12\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Hint (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Check maps</span><br/></para></col></row><row position=\"13\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Score (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>10</span><br/></para></col></row><row position=\"14\"><col position=\"1\" editable=\"0\"><para><span style=\"color: #000080;font-family: Tahoma;font-size:  9pt\">Notes to Editor (Optional)</span><br/></para></col><col position=\"2\" editable=\"1\"><para><span>Check all correct.</span><br/></para></col></row></table_details></table>
	</screen></root>';*/
            /* $input['XMLInput'] = (!empty($input['XMLInput']))?$input['XMLInput']:'<root><screen template_id="New_Layout_MCSS" screen_id="90524c5c"><table rows="17" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Screen ID</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">90524c5c</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Title</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(must be unique)</span></i><br/></para></col><col position="2" editable="0"><para><span style="color: #000000;font-family: Tahoma;font-size:  9pt">CE.1-1</span><br/></para></col></row><row position="3"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question Type</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Multiple Choice</span><br/></para></col></row><row position="4"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Instructions</span><br/></para></col><col position="2" editable="0"><para><ol><li style="LIST-STYLE-TYPE: disc"><span>Do not modify the shaded cells. </span></li><li style="LIST-STYLE-TYPE: disc"><b/><span>Click </span><a href="http://plotinus.learningmate.com/icev2/MCSS.htm"><b><span style="color: #000000;font-family: Tahoma;font-size:  9pt">here</span></b><span></span></a><b><span> for detailed authoring instructions and examples.</span></b><br/></li><li style="LIST-STYLE-TYPE: disc"><span>To add more Choices, select the last row and use the Word Menu option </span><b><span>Table -&gt; Insert -&gt; Rows Below</span></b><span style="color: #000000;font-family: Tahoma;font-size:  9pt">.</span><br/></li><li style="LIST-STYLE-TYPE: disc"><span>A score of 0 indicates an incorrect answer. A positive Score indicates a correct answer.</span></li><li style="LIST-STYLE-TYPE: disc"><span>To Shuffle the order of choices, place an X or x next to the desired selection.</span></li><li style="LIST-STYLE-TYPE: disc"><span>To specify Blooms Taxonomy, place an X or x next to the desired selection.</span></li><li style="LIST-STYLE-TYPE: disc"><span>Fields in </span><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Red</span><span style="color: #000000;font-family: Tahoma;font-size:  9pt"> are mandatory</span><br/></li></ol><br/></para></col></row><row position="5"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Text</span><br/></para></col><col position="2" editable="0"><para><span style="font-size:  10pt">Of the following, the one that is NOT an example of collecting </span><i>empirical</i><span style="font-size:  10pt"> evidence is</span><br/></para></col></row><row position="6"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Choices</span><br/></para></col><col position="2" editable="0"><table rows="6" cols="4"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Pin Answer</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(Mark X where desired)</span></i><br/></para></col><col position="2" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Answer</span><br/></para></col><col position="3" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Score</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(Numeric value - 0 or higher. 0 stands for incorrect)</span></i><br/></para></col><col position="4" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Feedback</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><br/></para></col><col position="2" editable="0"><para><span>watching and taking notes on a child\'s behavior during recess</span><br/></para></col><col position="3" editable="0"><para><span>0</span><br/></para></col><col position="4" editable="0"><para><br/></para></col></row><row position="3"><col position="1" editable="0"><para><br/></para></col><col position="2" editable="0"><para><span>conducting an experiment on the effectiveness of exercise for reducing symptoms of asthma.</span><br/></para></col><col position="3" editable="0"><para><span>0</span><br/></para></col><col position="4" editable="0"><para><br/></para></col></row><row position="4"><col position="1" editable="0"><para><br/></para></col><col position="2" editable="0"><para><span>conducting an experiment on the effectiveness of exercise for reducing symptoms of asthma</span><br/></para></col><col position="3" editable="0"><para><span>0</span><br/></para></col><col position="4" editable="0"><para><br/></para></col></row><row position="5"><col position="1" editable="0"><para><br/></para></col><col position="2" editable="0"><para><span>relying on past experience to guide your decisions.</span><br/></para></col><col position="3" editable="0"><para><span>1</span><br/></para></col><col position="4" editable="0"><para><br/>
                </para></col></row><row position="6"><col position="1" editable="0"><para><br/></para></col>
                <col position="2" editable="0"><para><br/></para></col><col position="3" editable="0"><para><br/></para></col>
                <col position="4" editable="0"><para><br/></para></col></row></table_details></table></col></row>
                <row position="7"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">
                Shuffle Choices </span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="0"><table rows="2" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span>Yes</span><br/></para></col><col position="2" editable="0"><para><span>X</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span>No</span><br/></para></col><col position="2" editable="0"><para><br/></para></col></row></table_details></table></col></row><row position="8"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question ID</span><br/></para></col><col position="2" editable="0"><para><span>asfsaf</span><br/></para></col></row><row position="9"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Difficulty</span><br/></para></col><col position="2" editable="0"><para><span>asfasf</span><br/></para></col></row><row position="10"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Page Reference</span><br/></para></col><col position="2" editable="0"><para><span>asfsaf</span><br/></para></col></row><row position="11"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Topic</span><br/></para></col><col position="2" editable="0"><para><span>asfsaf</span><br/></para></col></row><row position="12"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Skill</span><br/></para></col><col position="2" editable="0"><para><span>sa</span><br/></para></col></row><row position="13"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Objective</span><br/></para></col><col position="2" editable="0"><para><br/></para></col></row><row position="14"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 1</span><br/></para></col><col position="2" editable="0"><para><br/></para></col></row><row position="15"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 2</span><br/></para></col><col position="2" editable="0"><para><br/></para></col></row><row position="16"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 3</span><br/></para></col><col position="2" editable="0"><para><br/></para></col></row><row position="17"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Bloom\'s Taxonomy</span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="0"><table rows="2" cols="6"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Knowledge</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Comprehension</span><br/></para></col><col position="3" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Application</span><br/></para></col><col position="4" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Analysis</span><br/></para></col><col position="5" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Synthesis</span><br/></para></col><col position="6" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Evaluation</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><br/></para></col><col position="2" editable="0"><para><br/></para></col><col position="3" editable="0"><para><br/></para></col><col position="4" editable="0"><para><br/></para></col><col position="5" editable="0"><para><br/></para></col><col position="6" editable="0"><para><br/></para></col></row></table_details></table></col></row></table_details></table>	</screen></root>';
;*/
            
            
          // $input['XMLInput'] = ' <root><screen template_id="New_Layout_TF" screen_id="c4c2cbe4"><table rows="17" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Screen ID</span><br/></para></col><col position="2" editable="1"><para><span style="color: #000000;font-family: Tahoma;font-size:  9pt">c4c2cbe4</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Title</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(must be unique)</span></i><br/></para></col><col position="2" editable="1"><para><span style="color: #000000;font-family: Tahoma;font-size:  9pt">Test Question</span><br/></para></col></row><row position="3"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question Type</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">True/False</span><br/></para></col></row><row position="4"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Instructions</span><br/></para></col><col position="2" editable="0"><para><ol><li style="LIST-STYLE-TYPE: disc"><span>Do not modify the shaded cells.</span></li><li style="LIST-STYLE-TYPE: disc"><b/><span>Click </span><a href="http://plotinus.learningmate.com/icev2/TF.htm"><b><span style="color: #000000;font-family: Tahoma;font-size:  9pt">here</span></b><span></span></a><b><span> for detailed authoring instructions and examples.</span></b><br/></li><li style="LIST-STYLE-TYPE: disc"><span>To specify Blooms Taxonomy, place an \'X\' or \'x\' next to the desired selection.</span></li><li style="LIST-STYLE-TYPE: disc"><span>Fields in </span><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Red</span><span style="color: #000000;font-family: Tahoma;font-size:  9pt"> are mandatory</span><br/></li></ol><br/></para></col></row><row position="5"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Text</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="6"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Choices</span><br/></para></col><col position="2" editable="1"><table rows="3" cols="3"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Options</span><br/></para></col><col position="2" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Score</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(</span></i><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">Numeric value - </span></i><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">0 or higher)</span></i><br/></para></col><col position="3" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Feedback</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">True</span><br/></para></col><col position="2" editable="1"><para><br/></para></col><col position="3" editable="1"><para><span>t</span><br/></para></col></row><row position="3"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">False</span><br/></para></col><col position="2" editable="1"><para><span>f</span></para></col><col position="3" editable="1"><para><span>f</span><br/></para></col></row></table_details></table></col></row><row position="7"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Choice presentation layout</span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="1"><table rows="2" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Horizontal</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Vertical</span><br/></para></col></row><row position="2"><col position="1" editable="1"><para><br/></para></col><col position="2" editable="1"><para><br/></para></col></row></table_details></table></col></row><row position="8"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question ID</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="9"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Difficulty</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="10"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Page Reference</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="11"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Topic</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="12"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Skill</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="13"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Objective</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="14"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 1</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="15"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 2</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="16"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 3</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="17"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Bloom\'s Taxonomy</span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="1"><table rows="2" cols="6"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Knowledge</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Comprehension</span><br/></para></col><col position="3" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Application</span><br/></para></col><col position="4" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Analysis</span><br/></para></col><col position="5" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Synthesis</span><br/></para></col><col position="6" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Evaluation</span><br/></para></col></row><row position="2"><col position="1" editable="1"><para><br/></para></col><col position="2" editable="1"><para><br/></para></col><col position="3" editable="1"><para><br/></para></col><col position="4" editable="1"><para><br/></para></col><col position="5" editable="1"><para><br/></para></col><col position="6" editable="1"><para><br/></para></col></row></table_details></table></col></row></table_details></table>	</screen></root>';

       /*   $input['XMLInput'] = ' <root><screen template_id="TrueFalse" screen_id="c4c2cbe4"><table rows="18" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Screen ID</span><br/></para></col><col position="2" editable="1"><para><span style="color: #000000;font-family: Tahoma;font-size:  9pt">c4c2cbe4</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Title</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(must be unique)</span></i><br/></para></col><col position="2" editable="1"><para><span style="color: #000000;font-family: Tahoma;font-size:  9pt">Test Question</span><br/></para></col></row><row position="3"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question Type</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">True/False</span><br/></para></col></row><row position="4"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Instructions</span><br/></para></col><col position="2" editable="0"><para><ol><li style="LIST-STYLE-TYPE: disc"><span>Do not modify the shaded cells.</span></li><li style="LIST-STYLE-TYPE: disc"><b/><span>Click </span><a href="http://plotinus.learningmate.com/icev2/TF.htm"><b><span style="color: #000000;font-family: Tahoma;font-size:  9pt">here</span></b><span></span></a><b><span> for detailed authoring instructions and examples.</span></b><br/></li><li style="LIST-STYLE-TYPE: disc"><span>To specify Bloom\'s Taxonomy, place an \'X\' or \'x\' next to the desired selection.</span></li><li style="LIST-STYLE-TYPE: disc"><span>Fields in </span><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Red</span><span style="color: #000000;font-family: Tahoma;font-size:  9pt"> are mandatory</span><br/></li></ol><br/></para></col></row><row position="5"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Question Text</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="6"><col position="1" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Choices</span><br/></para></col><col position="2" editable="1"><table rows="3" cols="3"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Options</span><br/></para></col><col position="2" editable="0"><para><span style="color: #FF0000;font-family: Tahoma;font-size:  9pt">Score</span><br/><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">(</span></i><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">Numeric value - </span></i><i><span style="color: #FF0000;font-family: Tahoma;font-size:  8pt">0 or higher)</span></i><br/></para></col><col position="3" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Feedback</span><br/></para></col></row><row position="2"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">True</span><br/></para></col><col position="2" editable="1"><para><br/></para></col><col position="3" editable="1"><para><span>t</span><br/></para></col></row><row position="3"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">False</span><br/></para></col><col position="2" editable="1"><para><br/></para></col><col position="3" editable="1"><para><span>f</span><br/></para></col></row></table_details></table></col></row><row position="7"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Choice presentation layout</span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="1"><table rows="2" cols="2"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Horizontal</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Vertical</span><br/></para></col></row><row position="2"><col position="1" editable="1"><para><br/></para></col><col position="2" editable="1"><para><br/></para></col></row></table_details></table></col></row><row position="8"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Question ID</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="9"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Difficulty</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="10"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Page Reference</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="11"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Topic</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="12"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Skill</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="13"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Objective</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="14"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 1</span><br/></para></col><col position="2" editable="1"><para><span>hint</span><br/></para></col></row><row position="15"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 2</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="16"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Hint 3</span><br/></para></col><col position="2" editable="1"><para><br/></para></col></row><row position="17"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Bloom\'s Taxonomy</span><br/><i><span style="color: #000080;font-family: Tahoma;font-size:  8pt">(Mark X or x where applicable)</span></i><br/></para></col><col position="2" editable="1"><table rows="2" cols="6"><table_details><row position="1"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Knowledge</span><br/></para></col><col position="2" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Comprehension</span><br/></para></col><col position="3" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Application</span><br/></para></col><col position="4" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Analysis</span><br/></para></col><col position="5" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Synthesis</span><br/></para></col><col position="6" editable="0"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">Evaluation</span><br/></para></col></row><row position="2"><col position="1" editable="1"><para><br/></para></col><col position="2" editable="1"><para><br/></para></col><col position="3" editable="1"><para><br/></para></col><col position="4" editable="1"><para><br/></para></col><col position="5" editable="1"><para><br/></para></col><col position="6" editable="1"><para><br/></para></col></row></table_details></table></col></row><row position="18"><col position="1" editable="0"><para><span style="color: #000080;font-family: Tahoma">id</span><br/></para></col><col position="2" editable="1"><para><span style="color: #000080;font-family: Tahoma;font-size:  9pt">TrueFalse</span><br/></para></col></row></table_details></table>	</screen></root>';
              
           
           
           
            $input['XMLInput'] = str_replace("\r\n",'',  $input['XMLInput']);
            global $APPCONFIG,$CONFIG, $DBCONFIG;
            $this->myDebug("Input values");
            $this->myDebug($input);
            $qtp                = new QuestionTemplate();
            $qst                = new Question();
            $auth               = new Authoring();
            $tokenId            = $input['accessLogID'];
            $tokenCode          = $input['accessToken'];
            $this->myDebug("Token ID1");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code1");
            $this->myDebug($tokenCode);
            $transactionID      = ($input['transactionID']!="")?$input['transactionID']:rand(0, 999999);
            if($DBCONFIG->dbType=='Oracle')
            {
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
            }else{
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
            }
         
            $this->user_info    = json_decode($tokenInfo['UserInfo']);
            $sXMLInput          = ($input['XMLInput']!="")?$input['XMLInput']:"";
            $this->myDebug($this->user_info);
            $this->myDebug('::Processing Start::');
            $this->myDebug("Input XML");
            $this->myDebug($sXMLInput);
            if($sXMLInput!="")
            {
                $sXMLInput = ($this->validateXml($sXMLInput))? $sXMLInput : stripslashes($sXMLInput);
                $this->myDebug($sXMLInput);

                $ipDoc  = simplexml_load_string($sXMLInput);
                $i      = 1;
                $this->myDebug($ipDoc);
                $checkinlog = '';
            
                {
                    foreach($ipDoc->{'screen'} as $objQuestion)
                    {
                      
                        $questtitle = '';
                        list($node) = $objQuestion->xpath("table/table_details/row[@position='2']/col[@position='2']/para");
                        $questtitle = $auth->cleanQuestionTitle($auth->getRowTextFromWord($node,true));
                       
                        $this->myDebug("Question Title");
                        $this->myDebug($questtitle);
                        try
                        {
                            
                            // $RepositoryID = $QuestionID =  0;

                            list($node) = $objQuestion->xpath("table/table_details/row[@position='2']/col[@position='2']/para");
                            $this->myDebug('This is');
                            $this->myDebug($node);
                            $questtitle = $auth->getRowTextFromWord($node,true);
 echo 'questtitleuuuuuuuuu---------------------'.$questtitle;
                            //Get Question Type by Shortname
                            $sLayoutID          = $this->getAttribute($objQuestion,'template_id');
                            
                            
                            
                            
                            $this->myDEbug("This is Layout ID---");
                            $this->myDEbug($questtitle);
                            $this->myDEbug($sLayoutID);
                            
                            if($DBCONFIG->dbType=='Oracle'){
                                $QuestionTemplate   =  $qtp->questionTemplate(" qt.\"isDefault\" = ''Y'' and qt.\"TemplateFile\" = ''{$sLayoutID}'' ", "qt.\"HTMLStructure\" , qt.\"FlashStructure\" ,qt.\"HTMLTemplate\" , qt.\"EditMode\" , qt.\"isStatic\" ",'details');
                            }else{
                                $QuestionTemplate   =  $qtp->questionTemplate(" qt.isDefault = 'Y' and qt.TemplateFile = '{$sLayoutID}' ", "qt.HTMLStructure , qt.FlashStructure ,qt.HTMLTemplate , qt.EditMode , qt.isStatic ",'details');
                            }
                          $this->myDEbug("This is Question templates---");
                            $this->myDEbug($QuestionTemplate);
                            $EditMode           = $QuestionTemplate['EditMode'];
                            $QTypeShortName     = $QuestionTemplate['CategoryCode'];
                            $isStatic           = $QuestionTemplate['isStatic'];
                            $QuestionTemplateID = $QuestionTemplate['ID'];

                            $questIDfromXml     = $this->getAttribute($objQuestion,'screen_id');

                            $QuestionID         = ( ctype_digit($questIDfromXml) ) ? $questIDfromXml : 0;   
                            
                            $this->myDebug('---------------Testing---------------'.$questIDfromXmlnew);                            
                            $xml_to_insert = $objQuestion->asXML();
                            //Also Remove Tag PerNode First and then do the Below Remove Span Mass Level as the XSLT does not look for span anymore
                            $this->mydebug("New XMl");
                            $this->mydebug($xml_to_insert);
                            $xml_to_insert = $auth->spanRemoveEachNode($xml_to_insert, ' ');
                            //for Static Page offline without title
                            if($isStatic == 'Y')
                            {
                                $questtitle = 'Static Page offline - '.date('F j, Y, g:i a');
                            }
                             $this->myDebug("-------------XML----------");
                             $this->myDebug($xml_to_insert);
                             $this->myDebug("------------End------------");

                            //for Updating Question Content
                            
                            $xml_to_insert = $qst->addMediaPlaceHolder($xml_to_insert);
                            $json_to_insert     = $auth->getXmlToJson(0,$qst->removeMediaPlaceHolder($xml_to_insert),$QuestionTemplateID);
                                $this->Mydebug("JSON Conversion");
                                $this->Mydebug($json_to_insert);
                            $questiontxml   = $xml_to_insert;
                            $questionjson   = $qst->addMediaPlaceHolder(html_entity_decode($json_to_insert));
                            if($DBCONFIG->dbType=='Oracle')
                            {
                                $questionjson = str_replace("\'", "''", addslashes(stripslashes($questionjson))) ;
                                $questiontxml = str_replace("\'", "''", addslashes(stripslashes($questiontxml))) ;
                            }
                            $data = array(
                                        'Title'              => $questtitle,    
                                        'JSONData'           => $questionjson,  
                                        'XMLData'            => $questiontxml,  
                                        'TokenID'            => $tokenId,       
                                        'transactionID'      => $transactionID, 
                                        'QuestionTemplateID' => $QuestionTemplateID,
                                        'AddDate'            => $this->currentDate(),
                                        'ModDate'            => $this->currentDate(),
                                        'QuestionTemplateID' => $QuestionTemplateID,    
                                        'QuestionID'         => $QuestionID, 
                                        'UserID'             => $this->user_info->userID,
                                        'ClientID'           => $this->user_info->instId
                                );
                             print_R($data);
                                
                             $this->Mydebug("JSON Conversion");
                                $this->Mydebug($json_to_insert);
								
								   $res        = $this->db->executeClobProcedure('AddWordTemplateQuest',$data,'nocount');
                            if($DBCONFIG->dbType=='Oracle'){
                              
                                        $QuestionList   =   $this->db->getRows("SELECT \"ID\" as \"id\" ,\"Title\" as \"title\" FROM WordTemplateQuestions WHERE \"TransactionID\" = ".$transactionID);
                            }else{
//                                    $this->db->insert('WordTemplateQuestions',	$data);
                                        $QuestionList   =   $this->db->getRows("SELECT ID as id ,Title as title FROM WordTemplateQuestions WHERE transactionID = ".$transactionID);
                            }

//                            $questions = array();
//                            foreach($QuestionList as $key=>$row)
//                            {
//                                $question['question'] =  $row;
//                                array_push($questions, $question);
//                            }
                        }
                        catch(exception $ex)
                        {
                            $this->myDebug('---------------Exception start---------------');
                            $this->myDebug($ex);
                            $this->myDebug('---------------Exception end---------------');
                            $i++;
                            continue;
                        }
                    }
                }
            }else
            {
                $errorMsg = "Please Add Questions";
            }
            $this->myDebug("Token ID2");
            $this->myDebug($tokenId);
            $this->myDebug("Token Code2");
            $this->myDebug($tokenCode);
            $this->myDebug($questions);
           return   array('accessLogID'=>$tokenId,'accessToken'=>$tokenCode ,'transactionID'=>$transactionID,'questions'=>$QuestionList,'errorMsg'=>$errorMsg);
        }
        catch(exception $ex)
        {
            $this->myDebug("---------------Exception start---------------");
            $this->myDebug($ex);
            $this->myDebug('---------------Exception end---------------');
        }
    }*/
    
    

    function saveWTQuestions($input)
    {
        //select * from WT questions where
        global $DBCONFIG;
        $tokenId            = $input['accessLogID'];
        $tokenCode          = $input['accessToken'];
        $transactionID      = $input['transactionID'];
        
         if($DBCONFIG->dbType=='Oracle')
            {
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
            }else{
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
            }
        $this->user_info    = json_decode($tokenInfo['UserInfo']);  
        $questids           = $input["questids"];
        $questids           = str_replace('||', ',', $questids);
        $questids           = trim($questids,'|');
        $repositoryIds      = $input["repositoryIds"];
        $repositoryIds      = str_replace('||',',',$repositoryIds);
        $repositoryIds      = trim($repositoryIds,'|');
        $EntityTypeID       = $input['EntityType'];
        $dataArray          = array( 
                                    $questids,  
                                    $repositoryIds,
                                    $this->user_info->userID,                                    
                                    $this->currentDate(),
                                    $EntityTypeID,
                                    1
                                );

       $QuestDetail= $this->db->executeStoreProcedure('WordTemplateQuestionManage',$dataArray,"nocount");
       $qst                = new Question();
       foreach($QuestDetail as $quest)
       {
            $map[$quest['ID']]   = $qst->updateMediaCount($qst->addMediaPlaceHolder($quest['JsonData']),'json');
       }
        // $map[$questid]   = $this->updateMediaCount($this->addMediaPlaceHolder($questjson),'json');
       $qst->mapQuestionMedia($map);
    }
    
    function saveWTQuestionsForPegasus($input)
    {       
        global $DBCONFIG;
        
        
        
        
       $tokenId            = $input['accessLogID'];
        $tokenCode          = $input['accessToken'];
        $transactionID      = $input['transactionID'];
       /* $tokenId            = '1431';
        $tokenCode          = '570d81acd840f3ceee647a120f392af6';
        $transactionID      = '610048';
        $input['EntityType']=2;
        $input["questids"]='|11016|';
        $input["repositoryIds"]='|260|';*/
         if($DBCONFIG->dbType=='Oracle')
            {
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
            }else{
                $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
            }
        
            $this->user_info    = json_decode($tokenInfo['UserInfo']);  
            $questids           = $input["questids"];
            $questids           = str_replace('||', ',', $questids);
            $questids           = trim($questids,'|');
            $repositoryIds      = $input["repositoryIds"];
            $repositoryIds      = str_replace('||',',',$repositoryIds);
            $repositoryIds      = trim($repositoryIds,'|');
            $EntityTypeID       = $input['EntityType'];
            $dataArray          = array( 
                                        $questids,  
                                        $repositoryIds,
                                        $this->user_info->userID,                                    
                                        $this->currentDate(),
                                        $EntityTypeID,
                                        1
                                    );
       $QuestDetail= $this->db->executeStoreProcedure('WordTemplateQuestionManage',$dataArray,"nocount");     
       
       $qst = new Question();
       foreach($QuestDetail as $quest)
       {                   
           if(isset($quest['ID'])&& isset($quest['JsonData']))           
           {
                $repositoryQuesId    = $this->db->getSingleRow("SELECT ID FROM MapRepositoryQuestions WHERE QuestionID= '".$quest['ID']."' and EntityTypeID= '".$EntityTypeID."' ");                               
                $saveTemplateJSON =$quest['JsonData'];
                //$saveTemplateJSON      =htmlentities($saveTemplateJSON,ENT_QUOTES);
            
            $objJSONtmp = new Services_JSON();
                $decodedJSON = $objJSONtmp->decode($saveTemplateJSON);
             
                //Insert Meta Data & Keys
                $metaDataJSON = $decodedJSON->metadata;   
                $metaDataClass = new Metadata();
                $str='';
           
                for($i=0 ; $i<count($metaDataJSON) ; $i++)
                {
                   
               
                    if($metaDataJSON[$i]->text!='' && $metaDataJSON[$i]->val!='' && $metaDataJSON[$i]->text!='Score')
                    {   
                       
                       // $metaDataKeyCheck   = $this->db->getSingleRow("SELECT * FROM MetaDataKeys WHERE MetaDataName= '{$metaDataJSON[$i]->text}' and UserID = '{$this->session->getValue('userID')}' ");                                                    
                         $metaDataKeyCheck   = $this->db->getSingleRow(" SELECT mdk.ID,mdk.MetaDataName FROM MetaDataKeys mdk
                        inner join MapClientUser mcu on ((mdk.UserID = mcu.UserID  ) AND mcu.ClientID = '{$this->session->getValue('instID')}' AND mcu.isEnabled = '1')
                        WHERE mdk.MetaDataName = '{$metaDataJSON[$i]->text}' AND  mdk.isEnabled = '1'");
                  
              
                        $metaDataVal = $metaDataJSON[$i]->text;	
                        $metaDataKeyValues = $metaDataJSON[$i]->val;
                        
                        if($metaDataKeyCheck == NULL || $metaDataKeyCheck == '')
                        {
                             $metaKeyID = '';  
                             $metaKeyName = '';
                        }
                        else
                        {
                             $metaKeyID = $metaDataKeyCheck['ID'];  
                             $metaKeyName = $metaDataKeyCheck['MetaDataName'];  
                        }

                       // $checkKeyName = $metaDataClass->checkMetadaDataKeyName($metaDataJSON[$i]->text,'' );                            
                        
                        $this->input=array(
                            'pgncp' => '1',
                            'pgnob' => '-1',
                            'pgnot' => '-1',
                            'pgndc' => '-1',
                            'pgnstart' => '0',
                            'pgnstop' => '-1',
                            'rt' => 'metadata/metadata-save',
                            'metaDataKeyName' => $metaDataJSON[$i]->text, 
                            'metaDataKeyId' => '',
                            'metaDataKeyType' => 'text_entry',
                            'metaDataKeyValues' => '',
                            'metaDataKeyValueDeletedList' => '',
                            '' => ''
                            );
                        //if( $checkKeyName != true )
                        if($metaDataKeyCheck == NULL || $metaDataKeyCheck == '')
                        {
                            $MetaDataDetail   = $metaDataClass->metadataSaveForPegasus($this->input);
                            $ID = $MetaDataDetail['ID'];
                            if($ID)
                            {
                                $keyName = $MetaDataDetail['KeyName'];
                                $keyValues = $MetaDataDetail['KeyValues'];
                                $useCount = $MetaDataDetail['UseCount'];
                                $status = $MetaDataDetail['Status'];
                                $modDate = $MetaDataDetail['ModDate'];
                                $metaDataType = $MetaDataDetail['MetaDataType'];
                            }
                        }
                        else
                        {
                            $ID = $metaKeyID;
                            $keyName = $metaKeyName;
                        }
                        
                        if($ID!='' && $keyName!='')
                        {   
                            //if the metadata value contains comma, then metadata value will get inserted but it wont show up
                            //in classification pop up so comma is replaced by space
                            if(strstr($metaDataJSON[$i]->val, ','))
                            {        
                                $metaDataJsonVal = str_replace(',',' ', $metaDataJSON[$i]->val);
                            }
                            else
                            {
                                $metaDataJsonVal = $metaDataJSON[$i]->val;
                            }    
                            $str.=$ID.'|'.$keyName.'|||'.$metaDataJsonVal.'#';
                        }
                        
                    } 
                }     
                $assignMetaKVArray = array();                
                $assignMetaKVArray['manualKeysValues'] = $str;
              
                $metaDataClass->assignedMetadataForPegasus($assignMetaKVArray,$repositoryQuesId['ID'], 3);
                //Insert Taxonomies		
                $taxnonmyDataJSON = $decodedJSON->taxonomy;
                $classificationClass = new Classification();	
                $retTaxs=array();
                for($j=0 ; $j<count($taxnonmyDataJSON) ; $j++)
                {
                    if($taxnonmyDataJSON[$j]->text!='' && $taxnonmyDataJSON[$j]->val!='' )
                    {
                        //check if that taxonomy is already present in the database
                        $taxonomyKeyCheck   = $this->db->getSingleRow("SELECT * FROM Taxonomies WHERE Taxonomy= '{$taxnonmyDataJSON[$j]->text}' and UserID = '{$this->session->getValue('userID')}' ");
                        if($taxonomyKeyCheck == NULL)
                        {
                                $taxText = $taxnonmyDataJSON[$j]->text;	
                                $taxArray = array(
                                                'pgncp' => '1',
                                                'pgnob' => '-1',
                                                'pgnot' => '-1',
                                                'pgndc' => '-1',
                                                'pgnstart' => '0',
                                                'pgnstop' => '10',
                                                'rt' => 'classification/manage-taxonomy',
                                                'act' => 'ADD',
                                                'parentID' => '1',
                                                'taxonomyID' => '',
                                                'taxonomy' => $taxText,
                                                'accessMode' => 'Private',
                                                'belowTaxoID' => '',
                                                '' => ''
                                        );					
                                $retTaxs[] = $classificationClass->manageTaxonomy($taxArray);	
                        }
                        else
                        {
                            $retTaxs[]=$taxonomyKeyCheck['ID'];
                        }

                    }
                } 
                
                $retTaxs=implode(',',$retTaxs);              
                $classificationClass->manageClassification($repositoryQuesId['ID'],3,'',$retTaxs);   
               
                $newJsonData=$objJSONtmp->decode($quest['JsonData']);
                unset($newJsonData->taxonomy);
             
                           $metaarray=array();
                if(is_array($newJsonData->metadata))
                {
                    $k=0;
                    foreach($newJsonData->metadata as $metajson)
                    //for($i=0;$i<count($newJsonData['metadata']);$i++)
                    {
                        if($metajson->text=='Score')
                        {
                            $metaarray[$k]['text']=$metajson->text;
                            $metaarray[$k]['val']=$metajson->val;
                            $k++;
                        }   
                    }
                    
                }    
                
                if(is_array($metaarray)&&count($metaarray)>0)
                {
                    unset($newJsonData->metadata);
                    $newJsonData->metadata=$metaarray;
                }
                
                
                
                
                /*$metaarray=array();
                if(is_array($newJsonData['metadata']))
                {
                    $k=0;
                    for($i=0;$i<count($newJsonData['metadata']);$i++)
                    {
                        if($newJsonData['metadata'][$i]['text']=='Score')
                        {
                            $metaarray[$k]['text']=$newJsonData['metadata'][$i]['text'];
                            $metaarray[$k]['val']=$newJsonData['metadata'][$i]['val'];
                            $k++;
                        }   
                    }
                    
                }    
                
                if(is_array($metaarray)&&count($metaarray)>0)
                {
                    unset($newJsonData['metadata']);
                    $newJsonData['metadata']=$metaarray;
                }    
                */
        //$newJsonData=json_encode($newJsonData);
    
               $newJsonData= $objJSONtmp->encode($newJsonData);
        $questId    = $quest['ID'];
        if ( $DBCONFIG->dbType == 'Oracle' )
        {
            $query      = "UPDATE Questions SET \"JSONData\" = '$newJsonData' WHERE \"ID\" = ($questId)  ";
        }
        else
        {
            $query      = "UPDATE Questions SET JSONData = '$newJsonData' WHERE ID = ($questId) ";
        }
      
        $this->db->execute($query);
                //$quest['ID']
            }	        
        }
    }

    function getCheckedOutQuestions(Array $input)
    {
        global $DBCONFIG;
        $tokenId            = $input['accessLogID'];
        $tokenCode          = $input['accessToken'];
        $transactionID      = $input['transactionID'];
        $whereCondition     = "";
        if( $input['repositoryIDs'] != '' )
        {
            $whereCondition = " mrq.id IN ({$input['repositoryIDs']})";
            if($DBCONFIG->dbType=='Oracle')
            {
                $displayFields  = "qt.\"XMLData\" as \"xmlData\"";
            }else{
                $displayFields  = ",qt.xmlData";
            }
        }
        
       

         if($DBCONFIG->dbType=='Oracle')
        {
            $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE \"AccessToken\"= '{$tokenCode}' and \"AccessLogID\" = '{$tokenId}' ");
        }else{
            $tokenInfo          = $this->db->getSingleRow("SELECT * FROM AccessTokens WHERE AccessToken= '{$tokenCode}' and AccessLogID = '{$tokenId}' ");
        }
        $this->user_info    = json_decode($tokenInfo['UserInfo']);
        $this->myDebug($this->user_info);
        /*
        $questions  =   $this->db->getRows("SELECT concat(usr.firstName,' ',usr.LastName) as UserName ,qt.Version,qt.Title,qt.id AS QuestionID,mrq.id AS RepositoryID,
                                    mrq.EntityTypeID,mrq.EntityID,mrq.QuestionID ,
                                    IF(mrq.EntityTypeID=1,'Bank','Assessment') AS RepositoryType,
                                    IF(mrq.EntityTypeID=1,bnk.BankName,ast.AssessmentName) AS RepositoryName {$displayFields}
                                    FROM Questions qt
                                    INNER JOIN MapRepositoryQuestions  AS mrq ON qt.id = mrq.QuestionID AND mrq.isEnabled=1 AND mrq.EditStatus=1
                                    LEFT JOIN Banks  AS bnk ON bnk.ID = mrq.EntityID AND mrq.EntityTypeID=1 AND bnk.isEnabled=1
                                    LEFT JOIN Assessments  AS ast ON ast.id = mrq.EntityID AND mrq.EntityTypeID=2 AND ast.isEnabled=1 AND ast.status != 'Archive'
                                    LEFT JOIN Users usr ON usr.ID = qt.UserID AND  usr.isEnabled = '1'
                                    LEFT JOIN MapClientUser mcu ON usr.ID = mcu.UserID AND mcu.isEnabled = '1' AND mcu.ClientID = '{$this->user_info->instId}'
                                    WHERE qt.isEnabled=1 AND qt.AuthoringStatus = '1' {$whereCondition} ");
        */
      /* */
        if($DBCONFIG->dbType=='Oracle')
        {
            $whereCondition = $whereCondition!=''?$whereCondition:"-1";
             $displayFields = $displayFields!=''?$displayFields:"-1";
            $questions  = $this->db->executeStoreProcedure('GETCHECKEDOUTQUESTIONS',array($whereCondition,$this->user_info->userID,$this->user_info->instId,$displayFields),'nocount');
        }else
        {
            $whereCondition = $whereCondition!=''?' AND '.$whereCondition:"";
            $questions  =   $this->db->getRows("SELECT concat(usr.firstName,' ',usr.LastName) as UserName, qt.Version,qt.Title,qt.id AS QuestionID,mrq.id AS RepositoryID,
                                    mrq.EntityTypeID,mrq.EntityID,mrq.QuestionID ,
                                    IF(mrq.EntityTypeID=1,'Bank','Assessment') AS RepositoryType,
                                    IF(mrq.EntityTypeID=1,bnk.BankName,ast.AssessmentName) AS RepositoryName {$displayFields}
                                    FROM Questions qt
                                    INNER JOIN MapRepositoryQuestions  AS mrq ON qt.id = mrq.QuestionID AND mrq.isEnabled=1 AND mrq.EditStatus=1
                                    LEFT JOIN Banks  AS bnk ON bnk.ID = mrq.EntityID AND mrq.EntityTypeID=1 AND bnk.isEnabled=1
                                    LEFT JOIN Assessments  AS ast ON ast.id = mrq.EntityID AND mrq.EntityTypeID=2 AND ast.isEnabled=1 AND ast.status != 'Archive'
                                    LEFT JOIN Users usr ON usr.ID = qt.UserID AND  usr.isEnabled = '1'
                                    LEFT JOIN MapClientUser mcu ON usr.ID = mcu.UserID AND mcu.isEnabled = '1' 
                                    WHERE qt.isEnabled=1 AND qt.AuthoringStatus = '1' AND mcu.ClientID = '{$this->user_info->instId}' {$whereCondition} ");
        }
        $this->myDebug($questions);
        if( $input['repositoryIDs'] != '' )
        {
            foreach($questions as $key=>$question)
            {
                $question['UserName'] = $question['firstName']. ' '.  $question['LastName'];
                $question['RepositoryType'] = ($question['EntityTypeID'] == 1 ) ? 'Bank' : 'Asssessment';
                $question['RepositoryName'] =  ($question['EntityTypeID'] == 1 ) ? $question['BankName']  : $question['AssessmentName'] ;

                $qst            = new Question();
                $XMLtmp         = $qst->removeMediaPlaceHolder($question['xmlData']) ;
                $this->myDebug("This is Old Xml Data");
                $this->myDebug($XMLtmp);
                $pattern        = '/screen_id=\"[^"]*\"/i';
                $replacements   = "screen_id=\"{$question["QuestionID"]}\"";
                $this->myDebug("This is New Xml Data");
                $this->myDebug($XMLtmp);
                $XMLtmp         = preg_replace($pattern, $replacements, $XMLtmp);
                $XMLtmp1        = substr($XMLtmp , 0 ,  strpos($XMLtmp,"<row position=\"2\">"));
                $XMLtmp2        = str_replace("editable=\"1\"","editable=\"0\"",$XMLtmp1);
                $XMLtmp         = str_replace($XMLtmp1,$XMLtmp2,$XMLtmp);
                $XMLtmp         = preg_replace('[\r\t\n]','',$XMLtmp);
                
                $XMLtmp = str_replace("&amp;","&",$XMLtmp);
                $XMLtmp = str_replace("&amp;lt;","&lt;",$XMLtmp);
		$XMLtmp = str_replace("&amp;gt;","&gt;",$XMLtmp);
                $XMLtmp = str_replace("&lt;","<",$XMLtmp);
		$XMLtmp = str_replace("&gt;",">",$XMLtmp);

                $question['xmlData'] = $XMLtmp;
                $newQuestions[$key]  = $question;
                 if($DBCONFIG->dbType=='Oracle')
            {
                $query	= "Update Questions SET \"AuthoringStatus\" = '3' WHERE \"ID\" = ". $question['QuestionID'];
            }else{
                $query	= "Update Questions SET AuthoringStatus = '3' WHERE ID = ". $question['QuestionID'];
            }
                
                $this->db->execute($query);
            }
             return $newQuestions;
        }else{
             return $questions;
        }
       
    }
}
?>
