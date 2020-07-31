<?php

namespace Riki\Questionnaire\Model;

use Magento\Framework\Model\Context;

/**
 * Class Questionnaire
 * @package Riki\Questionnaire\Model
 *
 * @method \Riki\Questionnaire\Model\ResourceModel\Questionnaire _getResource()
 * @method \Riki\Questionnaire\Model\ResourceModel\Questionnaire getResource()
 */
class Questionnaire extends \Magento\Framework\Model\AbstractModel
{

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    const CHECKOUT_QUESTIONNAIRE = 0;
    const DISENGAGEMENT_QUESTIONNAIRE = 1;

    const VISIBILITY_NONE = 0; // only show on admin
    const VISIBILITY_CHECKOUT = 1; // show on checkout
    const VISIBILITY_ON_SUCCESS_PAGE = 2; // show on order success page

    const AVAILABLE_BACKEND = 1;
    const AVAILABLE_ALL = 0;

    const NAME = 'name';
    const CODE = 'code';
    const START_DATE = 'start_date';
    const END_DATE = 'end_date';
    const PRIORITY = 'priority';
    const IS_ENABLED = 'is_enabled';
    const LINKED_PRODUCT_SKU = 'linked_product_sku';
    const VISIBLE_ON_CHECKOUT = 'visible_on_checkout';
    const VISIBLE_ON_ORDER_SUCCESS_PAGE = 'visible_on_order_success_page';
    const IS_AVAILABLE_BACKEND_ONLY = 'is_available_backend_only';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @var array
     */
    protected $_questions = [];

    /**
     * @var QuestionFactory
     */
    protected $questionFactory;

    /**
     * @var Question
     */
    protected $questionInstance;

    /**
     * @var bool
     */
    protected $questionInitialized = false;


    /**
     * Questionnaire constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param QuestionFactory $questionFactory
     * @param ResourceModel\Questionnaire|null $resource
     * @param ResourceModel\Questionnaire\Collection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Model\QuestionFactory $questionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire $resource = null,
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire\Collection $resourceCollection = null,
        array $data = []
    ) {
        $this->questionFactory = $questionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Questionnaire\Model\ResourceModel\Questionnaire');
    }


    /**
     * Get Available Statuses
     *
     * @return array
     */
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }

    /**
     * Get Questionnaire Type
     *
     * @return array
     */
    public function getQuestionnaireType()
    {
        return [
            self::CHECKOUT_QUESTIONNAIRE => __('Checkout Questionnaire'),
            self::DISENGAGEMENT_QUESTIONNAIRE => __('Disengagement Questionnaire')
        ];
    }

    /**
     * Get Yes No Options
     *
     * @return array
     */
    public function getYesNo()
    {
        return [
            ['value' => self::STATUS_ENABLED, 'label' => __('Yes')],
            ['value' => self::STATUS_DISABLED, 'label' => __('No')]
        ];
    }

    /**
     * Get Code questionnaire
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->_getData(self::CODE);
    }

    /**
     * Get Name questionnaire
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->_getData(self::NAME);
    }

    /**
     * Get Questionnaire Start Date
     *
     * @return mixed
     */
    public function getStartDate()
    {
        return $this->_getData(self::START_DATE);
    }

    /**
     * Get Questionnaire End Date
     *
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->_getData(self::END_DATE);
    }

    /**
     * Get priority Questionnaire
     *
     * @return mixed
     */
    public function getPriority()
    {
        return $this->_getData(self::PRIORITY);
    }

    /**
     * Get Questionnaire is enabled
     *
     * @return mixed
     */
    public function getIsEnabled()
    {
        return $this->_getData(self::IS_ENABLED);
    }

    /**
     * Get Questionnaire Linked Product SKU
     *
     * @return mixed
     */
    public function getLinkedProductSku()
    {
        return $this->_getData(self::LINKED_PRODUCT_SKU);
    }

    /**
     * Get Questionnaire Visible On Checkout
     *
     * @return mixed
     */
    public function getVisibleOnCheckout()
    {
        return $this->_getData(self::VISIBLE_ON_CHECKOUT);
    }

    /**
     * Get Questionnaire Visible On Order Success Page
     *
     * @return mixed
     */
    public function getVisibleOnOrderSuccessPage()
    {
        return $this->_getData(self::VISIBLE_ON_ORDER_SUCCESS_PAGE);
    }

    /**
     * Get Questionnaire Is Available Backend Only
     *
     * @return mixed
     */
    public function getIsAvailableBackendOnly()
    {
        return $this->_getData(self::IS_AVAILABLE_BACKEND_ONLY);
    }

    /**
     * Get questionnaire creation date
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::CREATED_AT);
    }

    /**
     * Get previous questionnaire update date
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::UPDATED_AT);
    }

    /**
     * Before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->getQuestionInstance()->unsetQuestion();
        $questions = $this->getQuestionnaireQuestions();
        if (!empty($questions)) {
            foreach ($questions as $question) {
                $this->getQuestionInstance()->addQuestion($question);
            }
        }
        return parent::beforeSave();
    }

    /**
     * After Save Questionnaire
     * Save question
     *
     * @return $this
     */
    public function afterSave()
    {
        if (!empty($this->getQuestionInstance()->getQuestion())) {
            $this->questionInstance->setQuestionnaire($this)->saveQuestion();
        }
        $result = parent::afterSave();
        return $result;
    }

    /**
     * Check if data was changed
     *
     * @return bool
     */
    public function isDataChanged()
    {
        foreach (array_keys($this->getData()) as $field) {
            if ($this->dataHasChangedFor($field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve question instance
     *
     * @return Question
     */
    public function getQuestionInstance()
    {
        if (!isset($this->questionInstance)) {
            $this->questionInstance = $this->questionFactory->create();
            $this->questionInstance->setQuestionnaire($this);
        }
        return $this->questionInstance;
    }

    /**
     * Retrieve questions collection of questionnaire
     *
     * @return ResourceModel\Question\Collection
     */
    public function getQuestionnaireQuestionCollection()
    {
        $collection = $this->getQuestionInstance()->getQuestionnaireQuestionsCollection($this);

        return $collection;
    }

    /**
     * Add question to array of questionnaire questions
     *
     * @param Question $question
     * @return $this
     */
    public function addQuestion(Question $question)
    {
        $this->_questions[$question->getId()] = $question;
        return $this;
    }

    /**
     * Get question from questions array of questionnaire by given question id
     *
     * @param $questionId
     * @return Question|mixed|null
     */
    public function getQuestionById($questionId)
    {
        if (isset($this->_questions[$questionId])) {
            return $this->_questions[$questionId];
        }
        return null;
    }

    /**
     * Get All Questions of questionnaire
     *
     * @return array
     */
    public function getQuestions()
    {
        if (empty($this->_questions) && !$this->questionInitialized) {
            $collection = $this->getQuestionnaireQuestionCollection();

            foreach ($collection as $question) {
                $question->setQuestionnaire($this);
                $this->addQuestion($question);
            }
            $this->questionInitialized = true;
        }

        return $this->_questions;
    }

    /**
     * Set Questions of Questionnaire
     *
     * @param array|null $questions
     * @return $this
     */
    public function setQuestions(array $questions = null)
    {
        $this->_questions = $questions;

        if (is_array($questions) && empty($questions)) {
            $this->setData('is_delete_questions', true);
        }

        $this->questionInitialized = true;
        return $this;
    }

    /**
     * Clearing references on questionnaire
     *
     * @return $this
     */
    protected function _clearReferences()
    {
        $this->_clearOptionReferences();
        return $this;
    }

    /**
     * Clearing references to questionnaire from questionnaire's questions
     *
     * @return $this
     */
    protected function _clearOptionReferences()
    {
        /**
         * unload questionnaire questions
         */
        if (!empty($this->_questions)) {
            foreach ($this->_questions as $question) {
                $question->setQuestionnaire();
                $question->clearInstance();
            }
        }

        return $this;
    }

    /**
     * Clearing questionnaire's data
     *
     * @return $this
     */
    protected function _clearData()
    {
        foreach ($this->_data as $data) {
            if ($data instanceof \Magento\Framework\DataObject && method_exists($data, 'reset') && is_callable([
                    $data,
                    'reset'
                ])) {
                $data->reset();
            }
        }
        $this->setData([]);
        $this->setOrigData();
        $this->_questions = [];
        $this->_errors = [];

        return parent::_clearData();
    }

    /**
     * Set Code questionnaire
     *
     * @param $code
     * @return $this
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Set Name questionnaire
     *
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Set Questionnaire Start Date
     *
     * @param $startDate
     * @return $this
     */
    public function setStartDate($startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    /**
     * Set Questionnaire End Date
     * @param $endDate
     * @return $this
     */
    public function setEndDate($endDate)
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    /**
     * Set priority Questionnaire
     *
     * @param $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    /**
     * Set Questionnaire is enabled
     *
     * @param $isEnabled
     * @return $this
     */
    public function setIsEnabled($isEnabled)
    {
        return $this->setData(self::IS_ENABLED, $isEnabled);
    }

    /**
     * Set Questionnaire Linked Product SKU
     *
     * @param $linkedProductSku
     * @return $this
     */
    public function setLinkedProductSku($linkedProductSku)
    {
        return $this->setData(self::LINKED_PRODUCT_SKU, $linkedProductSku);
    }

    /**
     * Set Questionnaire Visible On Checkout
     *
     * @param $visibleOnCheckout
     *
     * @return $this
     */
    public function setVisibleOnCheckout($visibleOnCheckout)
    {
        return $this->setData(self::VISIBLE_ON_CHECKOUT, $visibleOnCheckout);
    }

    /**
     * Set Questionnaire Visible On Order Success Page
     *
     * @param $visibleOnOrderSuccessPage
     *
     * @return $this
     */
    public function setVisibleOnOrderSuccessPage($visibleOnOrderSuccessPage)
    {
        return $this->setData(self::VISIBLE_ON_ORDER_SUCCESS_PAGE, $visibleOnOrderSuccessPage);
    }

    /**
     * Set Questionnaire Is Available Backend Only
     *
     * @param $isAvailableBackendOnly
     * @return $this
     */
    public function setIsAvailableBackendOnly($isAvailableBackendOnly)
    {
        return $this->setData(self::IS_AVAILABLE_BACKEND_ONLY, $isAvailableBackendOnly);
    }

    /**
     * Set questionnaire creation date
     *
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set previous questionnaire update date
     *
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @param array $data
     */
    public function insertQuestionnaire($data = [])
    {
        $this->getResource()->insertArrayQuestionnaire($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function updateQuestionnaire($data = [])
    {
        return $this->getResource()->updateArrayQuestionnaire($data);
    }

    /**
     * get array of questionnaire id to code
     *
     * @return array
     */
    public function getAllIdsToCodes()
    {
        return $this->getResource()->getAllIdsToCodes();
    }

    /**
     * Retrieve questionnaire id by sku
     *
     * @param   string $sku
     * @return  integer
     */
    public function getQuestionnaireIdBySku($sku)
    {
        return $this->_getResource()->getQuestionnaireIdBySku($sku);
    }

    /**
     * Get list Id questionnaire by array sku
     *
     * @param $skuArr
     *
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuestionnaireIdsBySKus($skuArr)
    {
        return $this->_getResource()->getQuestionnaireIdsBySkus($skuArr);
    }

    /**
     * @param $code
     * @return mixed
     */
    public function findQuestionnairebyEnqueteCode($code)
    {
        return $this->getResource()->findQuestionnairebyEnqueteCode($code);
    }

    /**
     * Delete questionnaire by array code
     *
     * @param $arrCode
     *
     * @return mixed
     */
    public function deleteQuestionnaireByCodeArr($arrCode)
    {
        return $this->getResource()->deleteQuestionnaireByCodeArr($arrCode);
    }
}
