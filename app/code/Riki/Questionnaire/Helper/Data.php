<?php

namespace Riki\Questionnaire\Helper;

use Riki\Questionnaire\Model\AnswersFactory;
use Riki\Questionnaire\Model\Questionnaire;
use Riki\Questionnaire\Model\QuestionnaireFactory;

/**
 * Class Data
 * @package Riki\Questionnaire\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_CONFIG_DEFAULT_QUESTIONNAIRE = 'riki_questionnaire/questionnaire/questionnaire_default';
    const XML_CONFIG_QUESTIONNAIRE_SAVE = 'riki_questionnaire/questionnaire/questionnaire_log';

    const FILE_TYPE_QUESTIONNAIRE = 'questionnaire';
    const FILE_TYPE_QUESTION = 'question';
    const FILE_TYPE_CHOICE = 'choice';

    /**
     * @var AnswersFactory
     */
    protected $_answersFactory;

    /**
     * @var QuestionnaireFactory
     */
    protected $_questionnaireFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $course;

    /** @var \Riki\Questionnaire\Model\ReplyFactory  */
    protected $_replyFactory;
    protected $_loggerSave;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param QuestionnaireFactory $questionnaireFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param AnswersFactory $answersFactory
     * @param \Riki\Questionnaire\Model\ReplyFactory $replyFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $course
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        QuestionnaireFactory $questionnaireFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        AnswersFactory $answersFactory,
        \Riki\Questionnaire\Model\ReplyFactory $replyFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $course,
        \Riki\Questionnaire\Logger\LoggerSave $loggerSave
    ) {
        $this->storeManager = $storeManager;
        $this->_questionnaireFactory = $questionnaireFactory;
        $this->_datetime = $datetime;
        $this->_answersFactory = $answersFactory;
        $this->_replyFactory = $replyFactory;
        $this->_timezone = $timezone;
        $this->course = $course;
        $this->_loggerSave = $loggerSave;
        parent::__construct($context);
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get current store
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * Get Default questionnaire Id
     *
     * @return mixed
     */
    public function getDefaultQuestionnaireId()
    {
        return $this->getConfigValue(
            self::XML_CONFIG_DEFAULT_QUESTIONNAIRE,
            $this->getStore()->getStoreId()
        );
    }
    public function getDefaultQuestionnaireSave()
    {
        return $this->getConfigValue(
            self::XML_CONFIG_QUESTIONNAIRE_SAVE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get array questionnaire By SKUs
     *
     * @param $skuArr
     * @param int $visible
     *
     * @return array
     */
    public function getQuestionnaireBySKUs($skuArr, $visible = 0)
    {
        $originDate =  $this->_timezone->formatDateTime($this->_datetime->gmtDate(), 2);
        $currentDate = $this->_datetime->gmtDate('Y-m-d', $originDate);

        $result = [];

        $collection = $this->_questionnaireFactory->create()
            ->getCollection()
            ->addFieldToFilter(['linked_product_sku'],
                [
                    ['in' => $skuArr]
                ]
            )
            ->addFieldToFilter('start_date', ['lteq' => $currentDate])
            ->addFieldToFilter('end_date', ['gteq' => $currentDate])
            ->addFieldToFilter('is_enabled', ['eq' => Questionnaire::STATUS_ENABLED]);

        switch ($visible) {
            case Questionnaire::VISIBILITY_CHECKOUT:
                $collection->addFieldToFilter('visible_on_checkout', ['eq' => Questionnaire::STATUS_ENABLED]);
                break;
            case Questionnaire::VISIBILITY_ON_SUCCESS_PAGE:
                $collection->addFieldToFilter('visible_on_order_success_page', ['eq' => Questionnaire::STATUS_ENABLED]);
                break;
            default:
                break;
        }

        $count = $collection->getSize();
        if ($count) {
            $result = $this->getQuestionnaireDefault($visible);
            foreach ($collection as $item) {
                $checkDefault = $this->checkQuestionExist($result, $item->getEnqueteId());
                if(!$checkDefault){
                    $result[] = $this->renderDataQuestionnaire($item);
                }

            }
        }

        return $result;
    }

    /**
     * Check default question in list
     *
     * @param $questionDefault
     * @param $idQuestion
     * @return bool
     */
public function checkQuestionExist($questionDefault,$idQuestion){
    if($questionDefault){
        foreach($questionDefault as $question){
            if($question['enquete_id'] == $idQuestion){
                return true;
            }
        }
    }
    return false;

}
    /**
     * Get questionnaire default
     *
     * @param int $visible
     *
     * @return array
     */
    public function getQuestionnaireDefault($visible = 0)
    {
        $questionnaireId = $this->getDefaultQuestionnaireId();
        $result = [];
        if ($questionnaireId) {
            $questionnaireModel = $this->_questionnaireFactory->create();
            $questionnaire = $questionnaireModel->load($questionnaireId);

            $originDate =  $this->_timezone->formatDateTime($this->_datetime->gmtDate(),2);
            $currentDate = $this->_datetime->gmtDate('Ymd', $originDate);

            $startQuestionnaire = $questionnaire->getStartDate();
            $startDate = $this->_datetime->gmtDate('Ymd', $startQuestionnaire);

            $endQuestionnaire = $questionnaire->getEndDate();
            $endDate = $this->_datetime->gmtDate('Ymd', $endQuestionnaire);

            if ($questionnaire->getId()
                && $startDate <= $currentDate
                && $currentDate <= $endDate) {
                switch ($visible) {
                    case Questionnaire::VISIBILITY_CHECKOUT:
                        if ($questionnaire->getVisibleOnCheckout() && !$questionnaire->getIsAvailableBackendOnly())
                            $result[] = $this->renderDataQuestionnaire($questionnaire);
                        break;
                    case Questionnaire::VISIBILITY_ON_SUCCESS_PAGE:
                        if ($questionnaire->getVisibleOnOrderSuccessPage() && !$questionnaire->getIsAvailableBackendOnly())
                            $result[] = $this->renderDataQuestionnaire($questionnaire);
                        break;
                    default:
                        $result[] = $this->renderDataQuestionnaire($questionnaire);
                        break;
                }
            }
        }
        return $result;
    }

    /**
     * Render array Questionnaire data
     *
     * @param Questionnaire $questionnaire
     *
     * @return array|mixed
     */
    public function renderDataQuestionnaire(Questionnaire $questionnaire)
    {
        $values = [];

        $data = $questionnaire->getData();

        $questionArr = $questionnaire->getQuestions();

        if ($questionArr == null) {
            $questionArr = [];
        }

        if (!empty($questionArr)) {
            foreach ($questionArr as $question) {
                /** @var \Riki\Questionnaire\Model\Question $question */
                $value = [];

                $value['id'] = $question->getQuestionId();
                $value['question_id'] = $question->getQuestionId();
                $value['title'] = $question->getTitle();
                $value['type'] = $question->getType();
                $value['is_required'] = $question->getIsRequired();
                $value['sort_order'] = $question->getSortOrder();
                $value['hasSecond'] = "0";

                $i = $level = 0;

                $choices = $question->getChoices();

                if (!empty($choices)) {
                    foreach ($choices as $choice) {
                        /** @var \Riki\Questionnaire\Model\Choice $choice */
                        if(!$choice->getHideDelete()){
                            $value['optionChoices'][$i] = [
                                'question_id' => $choice->getQuestionId(),
                                'choice_id' => $choice->getChoiceId(),
                                'label' => $choice->getLabel(),
                                'sort_order' => $choice->getSortOrder(),
                                'parent_choice_id' => $choice->getParentChoiceId(),
                            ];
                            if ($choice->getParentChoiceId() > 0 ) {
                                $level++;
                            }
                            $i++;
                        }

                    }
                }

                if ($level > 0) {
                    $value['hasSecond'] = "1";
                }

                $values[] = $value;

            }
            $data['optionQuestions'] = $values;
        }

        return $data;
    }

    /**
     * Check validate date time
     *
     * @param $date
     * @param string $format
     *
     * @return bool
     */
    public function isDateTime($date, $format = 'Y/m/d H:i:s')
    {
        $d = $this->_datetime->gmtDate($format, $date);

        return $d && $d == $date;
    }

    /**
     * Check value is datetime in range between start and end
     *
     * @param $dateFrom
     * @param $dateTo
     * @param string $format
     *
     * @return bool
     */
    public function isDateTimeRange($dateFrom, $dateTo, $format = 'Y/m/d H:i:s')
    {
        if ( !$this->isDateTime($dateTo)
            || !$this->isDateTime($dateFrom)
        ) {
            return false;
        }

        $dateStart = $this->_datetime->gmtDate($format, $dateFrom);
        $dateEnd = $this->_datetime->gmtDate($format, $dateTo);

        return $dateStart <= $dateEnd;
    }

    /**
     * Check value is string or number boolean
     *
     * @param $value
     *
     * @return bool
     */
    public function isBooleanValueImport($value)
    {
        if (empty($value)) {
            return false;
        }
        if ($value == '0' || $value == '1' || $value == 1 || $value == 0) {
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function getRepliesByAnswerOrder($orderId){
        if($orderId instanceof \Magento\Sales\Model\Order){
            $orderId = $orderId->getId();
        }

        /** @var \Riki\Questionnaire\Model\Reply $reply */
        $reply = $this->_replyFactory->create();

        return  $reply->getResource()->getListByAnswerOrder($orderId);
    }

    public function getRepliesByAnswerId($idReply){
        /** @var \Riki\Questionnaire\Model\Reply $reply */
        $reply = $this->_replyFactory->create();

        return  $reply->getResource()->getByChoice($idReply);
    }
    public  function logQuestionOrder($area,$question = []){
        if($this->getDefaultQuestionnaireSave()){
            $this->_loggerSave->addInfo($area.' Data: '.json_encode($question));
        }


    }
}
