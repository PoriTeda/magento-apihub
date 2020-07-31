<?php
namespace Riki\Questionnaire\Controller\Adminhtml\Import;
use Magento\Framework\File\Csv;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\Questionnaire\Helper\Data as HelperDataQuestionnaire;
use Riki\Questionnaire\Helper\Data;
use Riki\Questionnaire\Model\Questionnaire;

/**
 * Class Save
 * @package Riki\Questionnaire\Controller\Adminhtml\Import
 */
class Save extends \Magento\Backend\App\Action
{
    const ENQUETE_TYPE = 'ENQUETE_TYPE';
    const ENQUETE_CODE = 'ENQUETE_CODE';
    const ENQUETE_DATE_START = 'ENQUETE_START_DATE';
    const ENQUETE_DATE_END = 'ENQUETE_END_DATE';
    const ENQUETE_SKU = 'SKU_CODE';
    const QUESTION_CODE = 'LEGACY_ENQUETE_QUESTION_NO';
    const QUESTION_ENQUETE_CODE = 'ENQUETE_CODE';
    const CHOICE_CODE = 'LEGACY_ENQUETE_CHOICES_NO';
    const CHOICE_PARENT_CODE = 'ENQUETE_PARAGRAPH_NO';
    const CHOICE_QUESTION_CODE = 'LEGACY_ENQUETE_QUESTION_NO';

    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory ;

    /**
     * @var Csv
     */
    protected $_csvReader;

    /**
     * @var DateTime
     */
    protected $_datetime;

    /**
     * @var Questionnaire
     */
    protected $_questionnaire;

    /**
     * @var \Riki\Questionnaire\Model\Question
     */
    protected $_question;

    /**
     * @var \Riki\Questionnaire\Model\Choice
     */
    protected $_choice;

    /**
     * @var \Riki\Questionnaire\Logger\LoggerCSV
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $_alreadyExistsQuestionnaires = [];

    /**
     * @var array
     */
    protected $_alreadyExistsQuestions = [];

    /**
     * @var array
     */
    protected $_alreadyExistsChoices = [];

    /**
     * @var \Riki\Questionnaire\Model\Config\Source\Questions\Options\Type
     */
    protected $_optionType;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    protected $_productResourceModel;

    /**
     * @var HelperDataQuestionnaire
     */
    protected $_helperQuestionnaire;

    protected $_columns = [
        HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE => [
            'enquete_type'  =>  ['column'   =>  self::ENQUETE_TYPE, 'type' =>  'int', 'required' =>  true],
            'code'  =>  ['column'   =>  self::ENQUETE_CODE, 'type' =>  'text', 'update' =>  false],
            'name'    =>  ['column'   =>  'ENQUETE_NAME', 'type' =>  'text'],
            'linked_product_sku'    =>  ['column'   =>  self::ENQUETE_SKU, 'type' =>  'text', 'required'   =>  false],
            'start_date'      =>  ['column'   =>  self::ENQUETE_DATE_START, 'type' =>  'date'],
            'end_date'    =>  ['column'   =>  self::ENQUETE_DATE_END, 'type' =>  'date'],
            'priority'    =>  ['column'   =>  'priority', 'type' =>  'int', 'default'   =>  0, 'required'   =>  false],
            'visible_on_checkout'    =>  ['column'   =>  'visible_on_checkout', 'type' =>  'select', 'options' =>  [0, 1], 'default'   =>  0, 'required'   =>  false],
            'visible_on_order_success_page'    =>  ['column'   =>  'visible_on_order_success_page', 'type' =>  'select', 'options' =>  [0, 1], 'default'   =>  0, 'required'   =>  false],
            'is_available_backend_only'    =>  ['column'   =>  'is_available_backend_only', 'type' =>  'select', 'options' =>  [0, 1], 'default'   =>  0, 'required'   =>  false],
            'is_enabled'    =>  ['column'   =>  'is_enabled', 'type' =>  'select', 'options' =>  [0, 1], 'default'   =>  1, 'required'   =>  false]
        ],
        HelperDataQuestionnaire::FILE_TYPE_QUESTION => [
            'legacy_enquete_question_no'  =>  ['column'   =>  self::QUESTION_CODE, 'type' =>  'text', 'update' =>  false],
            'enquete_id'  =>  ['column'   =>  self::QUESTION_ENQUETE_CODE, 'type' =>  'text'],
            'type'  =>  ['column'   =>  'ENQUETE_QUESTION_TYPE', 'type' =>  'select'],
            'title'  =>  ['column'   =>  'ENQUETE_QUESTION_CONTENT', 'type' =>  'text'],
            'sort_order'    =>  ['column'   =>  'DISPLAY_ORDER', 'type' =>  'int', 'default'   =>  0, 'required'   =>  false],
            'is_required'    =>  ['column'   =>  'is_required', 'type' =>  'int', 'default'   =>  0, 'required'   =>  false]
        ],
        HelperDataQuestionnaire::FILE_TYPE_CHOICE => [
            'question_id'  =>  ['column'   =>  self::CHOICE_QUESTION_CODE, 'type' =>  'text'],
            'legacy_enquete_choices_no'  =>  ['column'   =>  self::CHOICE_CODE, 'type' =>  'text', 'update' =>  false],
            'label'  =>  ['column'   =>  'ENQUETE_CHOICES', 'type' =>  'text'],
            'parent_choice_id'  =>  ['column'   =>  'ENQUETE_PARAGRAPH_NO', 'type' =>  'text'],
            'sort_order'    =>  ['column'   =>  'DISPLAY_ORDER', 'type' =>  'int', 'default'   =>  0, 'required'   =>  false]
        ]
    ];

    protected $_filesUploadName = [
        HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE    =>  'csv_file_enquete',
        HelperDataQuestionnaire::FILE_TYPE_QUESTION    =>  'csv_file_question',
        HelperDataQuestionnaire::FILE_TYPE_CHOICE    =>  'csv_file_question_choice'
    ];

    protected $_headerIndex = [];

    protected $_uploadData = [];

    protected $_questionnaireCodeImported = [];
    protected $_questionCodeImported = [];
    protected $_questionGroupImported = [];

    protected $_courseFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param DateTime $dateTime
     * @param Questionnaire $questionnaire
     * @param \Riki\Questionnaire\Model\Question $question
     * @param \Riki\Questionnaire\Model\Choice $choice
     * @param \Riki\Questionnaire\Logger\LoggerCSV $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResourceModel
     * @param \Riki\Questionnaire\Model\Config\Source\Questions\Options\Type $optionType
     * @param HelperDataQuestionnaire $dataHelper
     * @param Csv $csv
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        DateTime $dateTime,
        Questionnaire $questionnaire,
        \Riki\Questionnaire\Model\Question $question,
        \Riki\Questionnaire\Model\Choice $choice,
        \Riki\Questionnaire\Logger\LoggerCSV $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Model\ResourceModel\Product $productResourceModel,
        \Riki\Questionnaire\Model\Config\Source\Questions\Options\Type $optionType,
        HelperDataQuestionnaire $dataHelper,
        Csv $csv,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {
        $this->_logger = $logger;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_uploaderFactory = $uploaderFactory;
        $this->_csvReader = $csv;
        $this->_datetime = $dateTime;
        $this->_questionnaire = $questionnaire;
        $this->_question = $question;
        $this->_choice = $choice;
        $this->_productResourceModel = $productResourceModel;
        $this->_optionType = $optionType;
        $this->_helperQuestionnaire = $dataHelper;
        $this->_courseFactory = $courseFactory;

        $this->_columns[HelperDataQuestionnaire::FILE_TYPE_QUESTION]['type']['options'] = $this->_optionType->getOptionValueArray();
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Questionnaire::save');
    }

    /**
     * @return bool
     */
    protected function _validate(){
        foreach($this->_filesUploadName as $type    =>  $fileName){
            $file = $this->_uploaderFactory->create(['fileId' => $fileName]);
            $file->setAllowedExtensions(['csv']);

            try{
                $file = $file->validateFile();
                $this->_csvReader->setLineLength(1000);
                $this->_uploadData[$type] = $this->_csvReader->getData($file['tmp_name']);
                if(!$this->_checkCsvHeaders(array_shift($this->_uploadData[$type]), $type)){
                    $this->messageManager->addError(__('Wrong Format File - %1.', $type));
                    return false;
                }
                // Check if last line is blank line
                $lastItem = end($this->_uploadData[$type]);
                if (count($lastItem) == 1 && is_null($lastItem[0])) {
                    $this->messageManager->addError(__('The last line should not be blank - %1.', $type));
                    return false;
                }
            }catch(\Exception $e){
                $this->messageManager->addError(__($e->getMessage()).' '.__('Please only input the file csv type - %1.', $type));
                return false;
            }
        }

        return true;
    }

    /**
     * Save Questionnaire action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if(!$this->_validate()){
            return $resultRedirect->setPath('*/*/');
        }

        try {
            /**
             * Start import
             */
            $this->importEnquete();

            $this->_alreadyExistsQuestionnaires = $this->_questionnaire->getAllIdsToCodes();

            if(count($this->_alreadyExistsQuestionnaires)){
                $this->importEnqueteQuestion();

                $this->_alreadyExistsQuestions = $this->_question->getAllIdsToNo();

                if(count($this->_alreadyExistsQuestions)){
                    $this->importQuestionChoice();
                }
            }

            $this->messageManager->addSuccess(__('Import complete!!'));

        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
            return $resultRedirect->setPath('*/*/');
        }

        return $resultRedirect->setPath('*/*/');

    }

    /**
     * Import Enquete
     *
     * @return array
     *
     * @throws \Exception
     */
    public function importEnquete()
    {
        $insertData = [];
        $insertedCodes = [];
        $updateData = [];
        $fileData = $this->_uploadData[HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE];
        $row = 2;
        $importedNum = 0;

        $alreadyExitsCodes = $this->_questionnaire->getAllIdsToCodes();

        foreach($fileData as $rowData){
            $isValid = $this->_validateQuestionnaireRowData(HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE, $rowData, $row);

            if($isValid){

                $enqueteCode = $rowData[$this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE][self::ENQUETE_CODE]];;

                if(!in_array($enqueteCode, $insertedCodes)){
                    if (in_array($enqueteCode, $alreadyExitsCodes)) {
                        $preparedData = $this->_prepareRowData($rowData, HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE, true);
                        $preparedData['updated_at'] =  $this->_datetime->date();
                        $updateData[array_search($enqueteCode, $alreadyExitsCodes)] = $preparedData;
                    } else {
                        $preparedData = $this->_prepareRowData($rowData, HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE);
                        $preparedData['created_at'] = $this->_datetime->date();
                        $preparedData['updated_at'] = $this->_datetime->date();
                        $insertData[] = $preparedData;

                        $insertedCodes[] = $enqueteCode;

                        $this->_questionnaireCodeImported[] = $enqueteCode;
                    }
                }else{
                    $this->messageManager->addError(__('[%1][#%2][%3] Duplicate code', HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE, $row, self::ENQUETE_SKU));
                }

                $importedNum++;
            }

            $row++;
        }

        if(count($insertData)){
            $this->_questionnaire->insertQuestionnaire($insertData);
        }

        if(count($updateData)){
            $updateErrors = $this->_questionnaire->updateQuestionnaire($updateData);
            if(count($updateErrors)){
                $this->messageManager->addError(HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE . ' - ' .implode(',', $updateErrors));
                $this->_logger->info(HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE . ' - ' .implode(',', $updateErrors));
            }
        }

        $this->messageManager->addSuccess(__('%1 - Processed %2 row(s)', HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE, $importedNum));

        return $this;

    }

    /**
     * Import Enquete Question
     *
     * @return array
     *
     * @throws \Exception
     */
    public function importEnqueteQuestion()
    {
        $insertedCodes = [];
        $insertedQuestionnaireCodes = [];

        $insertData = [];
        $updateData = [];
        $row = 1;
        $importedNum = 0;

        $alreadyExitsQuestions = $this->_question->getAllIdsToNo();

        $fileData = $this->_uploadData[HelperDataQuestionnaire::FILE_TYPE_QUESTION];

        foreach($fileData as $rowData) {

            $isValid = $this->_validateQuestionRowData(HelperDataQuestionnaire::FILE_TYPE_QUESTION, $rowData, $row);

            if ($isValid) {

                $questionCode = $rowData[$this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_QUESTION][self::QUESTION_CODE]];

                $enqueteCode = $rowData[$this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_QUESTION][self::QUESTION_ENQUETE_CODE]];

                $enqueteId = array_search($enqueteCode, $this->_alreadyExistsQuestionnaires);

                if(!in_array($questionCode, $insertedCodes)){
                    if (in_array($questionCode, $alreadyExitsQuestions)) {

                        $preparedData = $this->_prepareRowData($rowData, HelperDataQuestionnaire::FILE_TYPE_QUESTION, true);
                        $preparedData['enquete_id'] = $enqueteId;

                        $updateData[array_search($questionCode, $alreadyExitsQuestions)] = $preparedData;
                    } else {

                        $preparedData = $this->_prepareRowData($rowData, HelperDataQuestionnaire::FILE_TYPE_QUESTION);
                        $preparedData['enquete_id'] = $enqueteId;

                        $insertData[] = $preparedData;

                        $insertedCodes[] = $questionCode;
                    }

                    $insertedQuestionnaireCodes = array_merge($insertedQuestionnaireCodes, [$enqueteCode]);

                    if(!isset($this->_questionGroupImported[$enqueteCode])){
                        $this->_questionGroupImported[$enqueteCode] = [];
                    }
                    $this->_questionGroupImported[$enqueteCode][] = $questionCode;

                    $this->_questionCodeImported[] = $questionCode;

                }else{
                    $this->messageManager->addError(__('[%1][#%2][%3] Duplicate code', HelperDataQuestionnaire::FILE_TYPE_QUESTION, $row, self::QUESTION_CODE));
                }

                $importedNum++;
            }

            $row++;
        }


        if(count($insertData)){
            $this->_question->insertArrayQuestion($insertData);
        }

        if(count($updateData)){
            $updateErrors = $this->_question->updateQuestion($updateData);
            if(count($updateErrors)){
                $arrIdQuestionError['message_update'] = $updateErrors;
                $this->_logger->info(HelperDataQuestionnaire::FILE_TYPE_QUESTION . ' - ' .implode(',', $updateErrors));
            }
        }

        // remove questionnaire if it do not have any child
        $nonChildrenQuestionnaireCodes = array_diff($this->_questionnaireCodeImported, $insertedQuestionnaireCodes);

        if(count($nonChildrenQuestionnaireCodes)){
            $this->_questionnaire->deleteQuestionnaireByCodeArr($nonChildrenQuestionnaireCodes);
            $this->messageManager->addWarning(__('Removed %1 questionnaire(s) [%2] because them do not have any question', count($nonChildrenQuestionnaireCodes), implode(', ', $nonChildrenQuestionnaireCodes)));
        }

        $this->messageManager->addSuccess(__('%1 - Processed %2 row(s)', HelperDataQuestionnaire::FILE_TYPE_QUESTION, $importedNum));

        return $this;

    }

    /**
     * Import Enquete Question Choice
     *
     * @return array
     *
     * @throws \Exception
     */
    public function importQuestionChoice()
    {
        $importedNum = 0;

        $insertedChoiceQuestionCodes = [];

        $this->_alreadyExistsChoices = $this->_choice->getAllIdsToNo();

        $fileData = $this->_uploadData[HelperDataQuestionnaire::FILE_TYPE_CHOICE];
        $filterArray = $this->filterChoices($fileData);

        //add checkbox parent into database
        $parentData = $this->_processChoiceDataToSave($filterArray['parent'], $insertedChoiceQuestionCodes, true);

        if (count($parentData['insert'])) {
            //insert parent choice
            $this->_choice->insertChoiceByArray($parentData['insert']);
            $importedNum += count($parentData['insert']);
        }

        if (count($parentData['update'])) {
            $this->_choice->updateChoices($parentData['update']);
            $importedNum += count($parentData['update']);
        }

        $insertedChoiceQuestionCodes = $parentData['questions'];

        $this->_alreadyExistsChoices = $this->_choice->getAllIdsToNo();

        /**
         * control insert choice son
         */

        $childrenData = $this->_processChoiceDataToSave($filterArray['son'], $insertedChoiceQuestionCodes);

        if(count($childrenData['insert'])){
            //insert son choice
            $this->_choice->insertChoiceByArray($childrenData['insert']);
            $importedNum += count($childrenData['insert']);
        }

        if($childrenData['update']){
            $this->_choice->updateChoices($childrenData['update']);
            $importedNum += count($childrenData['update']);
        }

        $insertedChoiceQuestionCodes = $childrenData['questions'];

        // remove question if it do not have any choice
        $nonChildrenQuestionCodes = array_diff($this->_questionCodeImported, $insertedChoiceQuestionCodes);

        if(count($nonChildrenQuestionCodes)){
            $this->_question->deleteQuestionByNoIdArr($nonChildrenQuestionCodes);
            $this->messageManager->addWarning(__('Removed %1 question(s) [%2] because them do not have any choice', count($nonChildrenQuestionCodes), implode(', ', $nonChildrenQuestionCodes)));
        }

        // remove questionnaire if it do not have any question

        $needToBeDeletedQuestionnaireCodes = [];
        foreach($this->_questionGroupImported as $questionnaireCode =>  $questionCodes){
            if(count(array_diff($questionCodes, $nonChildrenQuestionCodes)) == 0){
                $needToBeDeletedQuestionnaireCodes[] = $questionnaireCode;
            }
        }

        if(count($needToBeDeletedQuestionnaireCodes)){
            $this->_questionnaire->deleteQuestionnaireByCodeArr($needToBeDeletedQuestionnaireCodes);
            $this->messageManager->addWarning(__('Removed %1 questionnaire(s) [%2] because them do not have any question', count($needToBeDeletedQuestionnaireCodes), implode(', ', $needToBeDeletedQuestionnaireCodes)));
        }


        $this->messageManager->addSuccess(__(HelperDataQuestionnaire::FILE_TYPE_CHOICE . ' - Processed %1 row(s)', $importedNum));

        return $this;
    }

    /**
     * @param $fileData
     * @param $insertedChoiceQuestionCodes
     * @param bool|true $isParent
     * @return array
     */
    protected function _processChoiceDataToSave($fileData, $insertedChoiceQuestionCodes,  $isParent = false){

        $result = [
            'update'    =>  [],
            'insert'    =>  [],
            'questions' =>  $insertedChoiceQuestionCodes
        ];

        foreach ($fileData as $choiceData) {

            $isValid = $this->_validateChoiceRowData(HelperDataQuestionnaire::FILE_TYPE_CHOICE, $choiceData, $choiceData['index']);

            if ($isValid) {

                $helperIndexes = $this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_CHOICE];

                $questionCode = $choiceData[$helperIndexes[self::CHOICE_QUESTION_CODE]];
                $questionId = array_search($questionCode, $this->_alreadyExistsQuestions);

                $choiceCode = $choiceData[$helperIndexes[self::CHOICE_CODE]];
                $parentChoiceCode = $choiceData[$helperIndexes[self::CHOICE_PARENT_CODE]];
                $choiceParentId = $isParent? 0 : array_search($parentChoiceCode, $this->_alreadyExistsChoices);

                if (in_array($choiceCode, $this->_alreadyExistsChoices)) {
                    $preparedData = $this->_prepareRowData($choiceData, HelperDataQuestionnaire::FILE_TYPE_CHOICE, true);
                    $preparedData['question_id'] = $questionId;
                    $preparedData['parent_choice_id'] = (int)$choiceParentId;

                    $result['update'][array_search($choiceCode, $this->_alreadyExistsChoices)] = $preparedData;
                } else {
                    $preparedData = $this->_prepareRowData($choiceData, HelperDataQuestionnaire::FILE_TYPE_CHOICE);
                    $preparedData['question_id'] = $questionId;
                    $preparedData['parent_choice_id'] = (int)$choiceParentId;

                    $result['insert'][] = $preparedData;
                }

                $result['questions'] = array_merge($result['questions'], [$questionCode]);
            }
        }

        return $result;
    }

    /**
     * Filter Choices
     *
     * @param array $data
     *
     * @return array
     */
    public function filterChoices($data = [])
    {
        $importedCodes = [];

        $filterData = [] ;
        $parentCheckbox = [];
        $childrenCheckbox = [];
        $row = 1;
        $headerIndexes = $this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_CHOICE];
        foreach ($data as $key => $choice) {

            if(isset($choice[$headerIndexes[self::CHOICE_CODE]]) && isset($choice[$headerIndexes[self::CHOICE_PARENT_CODE]])){

                if(!in_array($choice[$headerIndexes[self::CHOICE_CODE]], $importedCodes)){
                    $choice['index'] = $row;

                    if($choice[$headerIndexes[self::CHOICE_CODE]] == $choice[$headerIndexes[self::CHOICE_PARENT_CODE]]){
                        $parentCheckbox [] = $choice;
                    }else{
                        $childrenCheckbox[] = $choice;
                    }

                    $importedCodes[] = $choice[$headerIndexes[self::CHOICE_CODE]];
                }else{
                    $this->messageManager->addError(__('[%1][#%2][%3] Duplicate code', HelperDataQuestionnaire::FILE_TYPE_CHOICE, $row, self::CHOICE_CODE));
                }
            }

            $row++;
        }
        // set title
        $filterData['parent'] = $parentCheckbox;
        $filterData['son'] = $childrenCheckbox;
        return $filterData;
    }

    /**
     * @param $headers
     * @param $type
     * @return bool
     */
    protected function _checkCsvHeaders($headers, $type){

        if (empty($headers) || count(array_unique($headers)) != count($headers)) {
            return false;
        }

        foreach($this->_columns[$type] as $name =>  $config){
            if(
                !in_array($config['column'], $headers)
                && (
                    !isset($config['required'])
                    ||
                    (isset($config['required']) && $config['required'])
                )
            )
                return false;
        }

        $this->_headerIndex[$type] = [];

        foreach($headers as $index  =>  $fieldName){
            $this->_headerIndex[$type][$fieldName]  =  $index;
        }

        return true;
    }

    /**
     * @param $rowData
     * @param $type
     * @param bool|false $isUpdate
     * @return array
     */
    protected function _prepareRowData($rowData, $type, $isUpdate = false){
        $result = [];

        $columns = $this->_columns[$type];

        foreach($columns as $name   =>  $config){

            $columnData = '';

            if(isset($this->_headerIndex[$type][$config['column']])){
                if(isset($rowData[$this->_headerIndex[$type][$config['column']]])){
                    $columnData = $rowData[$this->_headerIndex[$type][$config['column']]];
                }else{
                    if(isset($config['default']))
                        $columnData = $config['default'];
                }
            }else{
                if(isset($config['default']))
                    $columnData = $config['default'];
            }

            if(!$isUpdate || !isset($config['update']) || $config['update']){
                $result[$name]  = $columnData;
            }
        }

        return $result;
    }

    /**
     * @param $fileType
     * @param array $data
     * @param $rowNum
     * @return bool
     */
    protected function _validateQuestionRowData($fileType, array $data, $rowNum){
        $isValid = $this->_validateRowData($fileType, $data, $rowNum);

        if($isValid){

            $headerIndexes = $this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_QUESTION];

            if(!in_array($data[$headerIndexes[self::QUESTION_ENQUETE_CODE]], $this->_alreadyExistsQuestionnaires)){
                $isValid = false;
                $this->messageManager->addError( __('[%1][#%2][%3] Enquete code "%4" is not exist', $fileType, $rowNum, self::QUESTION_ENQUETE_CODE, $data[$headerIndexes[self::QUESTION_ENQUETE_CODE]]));
            }
        }

        return $isValid;
    }

    /**
     * @param $fileType
     * @param array $data
     * @param $rowNum
     * @param bool|false $isParent
     * @return bool
     */
    protected function _validateChoiceRowData($fileType, array $data, $rowNum, $isParent = false){
        $isValid = $this->_validateRowData($fileType, $data, $rowNum);

        if($isValid){

            $headerIndexes = $this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_CHOICE];

            if(!in_array($data[$headerIndexes[self::CHOICE_QUESTION_CODE]], $this->_alreadyExistsQuestions)){
                $isValid = false;
                $this->messageManager->addError( __('[%1][#%2][%3] Question code "%4" is not exist', $fileType, $rowNum, self::CHOICE_QUESTION_CODE, $data[$headerIndexes[self::CHOICE_QUESTION_CODE]]));
            }

            if($isParent){
                if(
                    isset($data[$headerIndexes[self::CHOICE_PARENT_CODE]])
                    && !empty($data[$headerIndexes[self::CHOICE_PARENT_CODE]])
                    && !in_array($data[$headerIndexes[self::CHOICE_PARENT_CODE]], $this->_alreadyExistsChoices)
                ){
                    $isValid = false;
                    $this->messageManager->addError( __('[%1][#%2][%3] Choice parent code "%4" is not exist', $fileType, $rowNum, self::CHOICE_PARENT_CODE, $data[$headerIndexes[self::CHOICE_PARENT_CODE]]));
                }
            }
        }

        return $isValid;
    }

    /**
     * @param $fileType
     * @param array $data
     * @param $rowNum
     * @return bool
     */
    protected function _validateQuestionnaireRowData($fileType, array $data, $rowNum){
        $isValid = $this->_validateRowData($fileType, $data, $rowNum);

        if($isValid){

            $headerIndexes = $this->_headerIndex[HelperDataQuestionnaire::FILE_TYPE_QUESTIONNAIRE];

            if(
                isset($data[$headerIndexes[self::ENQUETE_DATE_START]])
                && isset($data[$headerIndexes[self::ENQUETE_DATE_END]])
                && !empty($data[$headerIndexes[self::ENQUETE_DATE_START]])
                && !empty($data[$headerIndexes[self::ENQUETE_DATE_END]])
            ){
                if($this->_datetime->timestamp($data[$headerIndexes[self::ENQUETE_DATE_START]]) > $this->_datetime->timestamp($data[$headerIndexes[self::ENQUETE_DATE_END]])){
                    $isValid = false;
                    $this->messageManager->addError( __('[%1][#%2][%3] Data is must greater or equal Start Date', $fileType, $rowNum, self::ENQUETE_DATE_END));
                }
            }

            if (!in_array($data[$headerIndexes[self::ENQUETE_TYPE]],
                [Questionnaire::CHECKOUT_QUESTIONNAIRE,Questionnaire::DISENGAGEMENT_QUESTIONNAIRE])) {
                $isValid = false;
                $this->messageManager->addError(__(
                    '[%1][#%2][%3] Data must be %4 or %5',
                    $fileType,
                    $rowNum,
                    self::ENQUETE_TYPE,
                    Questionnaire::CHECKOUT_QUESTIONNAIRE,
                    Questionnaire::DISENGAGEMENT_QUESTIONNAIRE
                ));
            }

            // sku
            if ($data[$headerIndexes[self::ENQUETE_TYPE]] == Questionnaire::CHECKOUT_QUESTIONNAIRE) {

                $sku = isset($data[$headerIndexes[self::ENQUETE_SKU]])? $data[$headerIndexes[self::ENQUETE_SKU]] : '';

                $productId = $this->_productResourceModel->getIdBySku($sku);

                $courseModel = $this->_courseFactory->create()->getCollection();

                $courseModel->addFieldToFilter('course_code', $sku);

                if(!$productId && (!$courseModel->getSize())){
                    $isValid = false;
                    $this->messageManager->addError(__('[%1][#%2][%3] Sku or Subscription code "%4" is not exists', $fileType, $rowNum, self::ENQUETE_SKU, $sku));
                }
            }
        }

        return $isValid;
    }

    /**
     * @param string $fileType
     * @param array $data
     * @param $rowNum
     * @return bool
     */
    protected function _validateRowData($fileType, array $data, $rowNum){
        $isValid = true;

        $columns = $this->_columns[$fileType];
        $headerIndex = $this->_headerIndex[$fileType];

        foreach($columns as $name   => $config){

            $value = '';

            if(isset($headerIndex[$config['column']])){
                $index = $headerIndex[$config['column']];
                if(isset($data[$index])){
                    $value = $data[$index];
                }
            }

            if((string)$value == ''){
                // Do not check required for start date && end date in case of disengagement questionnaire
                $check = true;
                if ($fileType == Data::FILE_TYPE_QUESTIONNAIRE
                    && $data[$headerIndex[self::ENQUETE_TYPE]] == Questionnaire::DISENGAGEMENT_QUESTIONNAIRE
                    && in_array($config['column'], [self::ENQUETE_DATE_START, self::ENQUETE_DATE_END])) {
                    $check = false;
                }
                if((!isset($config['required']) || $config['required']) && $check){
                    $isValid = false;
                    $this->messageManager->addError(__('[%1][#%2][%3] Missing data', $fileType, $rowNum, $config['column']));
                }
            }else{
                if(!$this->_validateTypeData($value, $config)){
                    $isValid = false;
                    $this->messageManager->addError(__('[%1][#%2][%3] Data "%4" is invalid, a %5 value is required', $fileType, $rowNum, $config['column'], $value, $config['type']));
                }
            }
        }

        return $isValid;
    }

    /**
     * validate type for value
     *
     * @param $data
     * @param $config
     * @return bool
     */
    protected function _validateTypeData($data, $config)
    {
        switch ($config['type']) {
            case 'select':
                $valid = in_array($data, $config['options']);
                break;
            case 'multiselect':
                $items = explode(';', $data);
                $valid = true;
                foreach($items as $item){
                    if(!in_array(trim($item), $config['options'])){
                        $valid = false;
                    }
                }
                break;
            case 'int':
                $val = trim($data);
                $valid = (string)(int)$val == $val;
                break;
            case 'float':
                $val = trim($data);
                $valid = (string)(float)$val == $val;
                break;
            case 'url':
                $valid = filter_var($data, FILTER_VALIDATE_URL);
                break;
            case 'date':
                $d = \DateTime::createFromFormat('Y/m/d H:i:s', $data);
                $valid = $d && $d->format('Y/m/d H:i:s') == $data;
                break;
            case 'text-required':
                $valid = empty($data) == false;
                break;
            default:
                $valid = true;
                break;
        }

        return $valid;
    }
}
