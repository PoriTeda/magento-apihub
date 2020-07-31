<?php
namespace Riki\Questionnaire\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Riki\Questionnaire\Model\Config\Source\Questions\Options\Type as QuestionType;

/**
 * Class Question
 * @package Riki\Questionnaire\Model
 *
 * @method \Riki\Questionnaire\Model\ResourceModel\Question _getResource()
 * @method \Riki\Questionnaire\Model\ResourceModel\Question getResource()
 */
class Question extends \Magento\Framework\Model\AbstractModel
{
    const KEY_QUESTION_ID = 'question_id';
    const KEY_TITLE = 'title';
    const KEY_TYPE = 'type';
    const KEY_SORT_ORDER = 'sort_order';
    const KEY_IS_REQUIRED = 'is_required';
    const KEY_ENQUETE_QUESTION_NO = 'legacy_enquete_question_no';

    /**
     * @var array
     */
    protected $question = [];

    /**
     * @var array|null
     */
    protected $choices = null;

    /**
     * @var Questionnaire
     */
    protected $_questionnaire;

    /**
     * @var QuestionnaireFactory
     */
    protected $_questionnaireFactory;

    /**
     * @var Choice
     */
    protected $questionChoice;
    
    /**
     * @var \Magento\Framework\Reflection\DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var QuestionFactory
     */
    protected $_questionFactory;

    /**
     * Question constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Questionnaire $questionnaire
     * @param QuestionnaireFactory $questionnaireFactory
     * @param Choice $questionChoice
     * @param QuestionFactory $questionFactory
     * @param AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        Questionnaire $questionnaire,
        QuestionnaireFactory $questionnaireFactory,
        Choice $questionChoice,
        \Riki\Questionnaire\Model\QuestionFactory $questionFactory,
        AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_questionnaireFactory = $questionnaireFactory;
        $this->_questionnaire = $questionnaire;
        $this->questionChoice = $questionChoice;
        $this->_questionFactory = $questionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Questionnaire\Model\ResourceModel\Question');
    }

    /**
     * @return Questionnaire
     */
    public function getQuestionnaire()
    {
        return $this->_questionnaire;
    }

    /**
     * @param Questionnaire|null $questionnaire
     * @return $this
     */
    public function setQuestionnaire(Questionnaire $questionnaire = null)
    {
        $this->_questionnaire = $questionnaire;
        return $this;
    }

    /**
     * Retrieve question choice instance
     *
     * @return Choice
     */
    public function getChoiceInstance()
    {
        return $this->questionChoice;
    }

    /**
     * Add choice of question to values array
     *
     * @param Choice $choice
     * @return $this
     */
    public function addChoice(Choice $choice)
    {
        $this->choices[$choice->getId()] = $choice;
        return $this;
    }

    /**
     * Get choice of question by given Id
     *
     * @param $choiceId
     * @return mixed|null
     */
    public function getChoiceById($choiceId)
    {
        if (isset($this->choices[$choiceId])) {
            return $this->choices[$choiceId];
        }
        return null;
    }

    /**
     * Retrieve choice of question instance
     *
     * @return Choice array|null
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * Get All Question
     *
     * @return array
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * Set question for array
     *
     * @param $question
     *
     * @return $this
     */
    public function setQuestion($question)
    {
        $this->question = $question;
        return $this;
    }

    /**
     * Add option for save it
     *
     * @param $question
     * @return $this
     */
    public function addQuestion($question)
    {
        $this->question[] = $question;
        return $this;
    }

    /**
     * Remove question
     *
     * @return $this
     */
    public function unsetQuestion()
    {
        $this->question = [];
        return $this;
    }

    /**
     * Save question
     *
     * @return $this
     */
    public function saveQuestion()
    {

        foreach ($this->getQuestion() as $question) {
            /** @var Question $questionModel */
            $questionModel = $this->_questionFactory->create();
            $questionModel->_validatorBeforeSave = null;
            $questionModel->setData($question)
                ->setData(
                    'enquete_id',
                    $this->getQuestionnaire()->getId()
                );
            /** Reset is delete flag from the previous iteration */
            $questionModel->isDeleted(false);
            if ($questionModel->getData('question_id') == '0') {
                $questionModel->unsetData('question_id');
            } else {
                $questionModel->setId($questionModel->getData('question_id'));
            }
            
            $isEdit = (bool)$questionModel->getId() ? true : false;

            if ($questionModel->getData('is_delete') == '1') {
                if ($isEdit) {
                    $questionModel->delete();
                }
            } else {
                if ($questionModel->getData('previous_type') != '') {
                    $previousType = $questionModel->getData('previous_type');
                    $currentType = $questionModel->getData('type');
                    if ($previousType != $currentType
                        && $currentType == QuestionType::TYPE_TEXT) {
                        switch ($previousType) {
                            case QuestionType::TYPE_DROP_DOWN:
                                $questionModel->unsetData('choices');
                                if ($isEdit) {
                                    $questionModel->getChoiceInstance()->deleteChoice($questionModel->getId());
                                }
                                break;
                            case QuestionType::TYPE_RADIO:
                                $questionModel->unsetData('choices');
                                if ($isEdit) {
                                    $questionModel->getChoiceInstance()->deleteChoice($questionModel->getId());
                                }
                                break;
                        }
                    }
                }
                $questionModel->save();
            }
        }
        return $questionModel;
    }

    /**
     * After save
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->getChoiceInstance()->unsetChoices();
        $choices = $this->getData('choices');
        if (is_array($choices)) {
            foreach ($choices as $choice) {
                if (!isset($choice['hide_delete'])) {
                    $choice['hide_delete'] = 0;
                }
                // Check true-false
                if ($choice['hide_delete']) {
                    $choice['hide_delete'] = 1;
                } else {
                    $choice['hide_delete'] = 0;
                }
                $this->getChoiceInstance()->addChoice($choice);
            }
            
            $this->getChoiceInstance()->setQuestion($this)->saveChoices();
        }
        return parent::afterSave();
    }

    /**
     * Set question id
     *
     * @param $questionId
     * @return $this
     */
    public function setQuestionId($questionId)
    {
        return $this->setData(self::KEY_QUESTION_ID, $questionId);
    }
    
    /**
     * Set question title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        return $this->setData(self::KEY_TITLE, $title);
    }
    
    /**
     * Set question type
     *
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setData(self::KEY_TYPE, $type);
    }
    
    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::KEY_SORT_ORDER, $sortOrder);
    }
    
    /**
     * Set is require
     *
     * @param bool $isRequired
     * @return $this
     */
    public function setIsRequired($isRequired)
    {
        return $this->setData(self::KEY_IS_REQUIRED, $isRequired);
    }

    /**
     * Set enquete question No
     *
     * @param $enqueteQuestionNo
     *
     * @return $this
     */
    public function setEnqueteQuestionNo($enqueteQuestionNo)
    {
        return $this->setData(self::KEY_ENQUETE_QUESTION_NO, $enqueteQuestionNo);
    }


    /**
     * Set Choices
     *
     * @param array|null $choices
     * @return $this
     */
    public function setChoices(array $choices = null)
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * Get Questionnaire questions collection
     *
     * @param Questionnaire $questionnaire
     * @return \Riki\Questionnaire\Model\ResourceModel\Question\Collection
     */
    public function getQuestionnaireQuestionsCollection(Questionnaire $questionnaire)
    {
        /** @var \Riki\Questionnaire\Model\ResourceModel\Question\Collection $collection */
        $collection = clone $this->getCollection();
        $collection->addFieldToFilter(
            'enquete_id',
            $questionnaire->getId()
        )->setOrder(
            'sort_order',
            'asc'
        )->setOrder(
            'title',
            'asc'
        );
        
        $collection->addChoicesToResult();
        
        return $collection;
    }

    /**
     * Get collection of choices for current question
     *
     * @return mixed
     */
    public function getChoiceCollection()
    {
        $collection = $this->getChoiceInstance()->getChoicesCollection($this);

        return $collection;
    }

    /**
     * Clearing object's data
     *
     * @return $this
     */
    protected function _clearData()
    {
        $this->_data = [];
        $this->choices = null;
        return $this;
    }

    /**
     * Clearing cyclic references
     *
     * @return $this
     */
    protected function _clearReferences()
    {
        $choices = $this->choices;
        if (!empty($choices)) {
            foreach ($choices as $choice) {
                $choice->unsetQuestion();
            }
        }
        return $this;
    }

    /**
     * Get Question Id
     *
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->_getData(self::KEY_QUESTION_ID);
    }

    /**
     * Get question title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_getData(self::KEY_TITLE);
    }

    /**
     * Get question type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_getData(self::KEY_TYPE);
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder()
    {
        return $this->_getData(self::KEY_SORT_ORDER);
    }

    /**
     * Get is required
     *
     * @return mixed
     */
    public function getIsRequired()
    {
        return $this->_getData(self::KEY_IS_REQUIRED);
    }

    /**
     * Get enquente question no
     *
     * @return mixed
     */
    public function getEnqueteQuestionNo()
    {
        return $this->_getData(self::KEY_ENQUETE_QUESTION_NO);
    }

    /**
     * @param array $data
     */
    public function insertArrayQuestion($data = [])
    {
        $this->getResource()->insertArrayQuestion($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function updateQuestion($data = [])
    {
        return $this->getResource()->updateQuestion($data);
    }

    /**
     * @param $questionNo
     * @return string|void
     */
    public function getQuestionIdByNo($questionNo)
    {
        if (!$questionNo) {
            return;
        }
        return $this->getResource()->findQuestionbyNo($questionNo);
    }

    /**
     * get array of question id to no
     *
     * @return array
     */
    public function getAllIdsToNo()
    {
        return $this->getResource()->getAllIdsToNo();
    }

    /**
     * Delete Question By Enquete Question No array
     *
     * @param $arrNoId
     *
     * @return mixed
     */
    public function deleteQuestionByNoIdArr($arrNoId)
    {
        return $this->getResource()->deleteQuestionByNoIdArr($arrNoId);
    }

    /**
     * @param $enqueteIds
     * @return \Magento\Framework\DataObject[]
     */
    public function getQuestionsByEnqueteIds($enqueteIds)
    {
        $questionCollection = $this->getCollection();
        $questionCollection->addFieldToFilter('enquete_id', ['in'=> $enqueteIds]);
        $questionCollection->setOrder('sort_order', \Magento\Framework\Api\SortOrder::SORT_ASC);
        return $questionCollection->getItems();
    }
}
