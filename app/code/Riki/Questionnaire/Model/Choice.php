<?php
namespace Riki\Questionnaire\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory;

/**
 * Class Choice
 * @package Riki\Questionnaire\Model
 *
 * @method \Riki\Questionnaire\Model\ResourceModel\Choice _getResource()
 * @method \Riki\Questionnaire\Model\ResourceModel\Choice getResource()
 */
class Choice extends \Magento\Framework\Model\AbstractModel
{
    const KEY_CHOICE_ID = 'choice_id';
    const KEY_LABEL = 'label';
    const KEY_SORT_ORDER = 'sort_order';
    const KEY_PARENT_CHOICE_ID = 'parent_choice_id';
    const KEY_QUESTION_ID = 'question_id';
    const KEY_ENQUETE_QUESTION_NO = 'legacy_enquete_question_no';

    /**
     * @var array
     */
    protected $_choices = [];

    /**
     * @var Question
     */
    protected $_question;

    /**
     * @var CollectionFactory
     */
    protected $_choiceCollectionFactory;

    protected $_choiceFactory;
    
    /**
     * Choice constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CollectionFactory $choiceCollectionFactory
     * @param AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Model\ChoiceFactory $choiceFactory,
        CollectionFactory $choiceCollectionFactory,
        AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_choiceFactory = $choiceFactory;
        $this->_choiceCollectionFactory = $choiceCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Questionnaire\Model\ResourceModel\Choice');
    }

    /**
     * Unset Choice
     *
     * @return $this
     */
    public function unsetChoices()
    {
        $this->_choices = [];
        return $this;
    }

    /**
     * Add Choice
     *
     * @param $choice
     * @return $this
     */
    public function addChoice($choice)
    {
        $this->_choices[] = $choice;
        return $this;
    }

    /**
     * Get Choice
     *
     * @return array
     */
    public function getChoices()
    {
        return $this->_choices;
    }

    /**
     * Set Choice
     *
     * @param $choices
     *
     * @return $this
     */
    public function setChoices($choices)
    {
        $this->_choices = $choices;

        return $this;
    }

    /**
     * Set Question
     *
     * @param Question $question
     *
     * @return $this
     */
    public function setQuestion(Question $question)
    {
        $this->_question = $question;
        return $this;
    }

    /**
     * Unset Question
     *
     * @return $this
     */
    public function unsetQuestion()
    {
        $this->_question = null;
        return $this;
    }

    /**
     * Get Question
     *
     * @return mixed
     */
    public function getQuestion()
    {
        return $this->_question;
    }

    /**
     * Save Choices
     *
     * @return $this
     */
    public function saveChoices()
    {
        $choices = $this->getChoices();
        $parentChoices = $childChoice = $mapParentId = [] ;

        if (!empty($choices)) {
            foreach ($choices as $choice) {
                if (empty($choice['parent_choice_id'])) {
                    $parentChoices[] = $choice;
                } else {
                    $childChoice[] = $choice;
                }
            }
        }
        if (!empty($parentChoices)) {
            foreach ($parentChoices as $choice) {
                $index = trim($choice['parent_id']);
                $mapParentId[$index] = $this->saveChoice($choice);
            }
        }

        if (!empty($childChoice)) {
            foreach ($childChoice as $choice) {
                if (strpos(trim($choice['parent_choice_id']), 'New') !== false
                    && isset($mapParentId[trim($choice['parent_choice_id'])])) {
                    $choice['parent_choice_id'] = $mapParentId[trim($choice['parent_choice_id'])];
                }
                $this->saveChoice($choice);
            }
        }
        return $this;
    }

    /**
     * Save single choice
     *
     * @param $choice
     * @return mixed|null
     */
    public function saveChoice($choice)
    {
        $choiceModel = $this->_choiceFactory->create();
        $choiceModel->addData(
            $choice
        )->setData(
            'question_id',
            $this->getQuestion()->getId()
        );

        if ($choiceModel->getData('choice_id') == '-1') {
            $choiceModel->unsetData('choice_id');
        } else {
            $choiceModel->setId($choiceModel->getData('choice_id'));
        }

        if ($choiceModel->getData('is_delete') == '1') {
            if ($choiceModel->getId()) {
                $this->deleteChoices($choiceModel->getId());
                $this->deleteChildChoices($choiceModel->getId());
            }
            return null;
        } else {
            $choiceModel->save();
            return $choiceModel->getId();
        }
    }

    /**
     * Get Choice collection
     *
     * @param Question $question
     *
     * @return mixed
     */
    public function getChoicesCollection(Question $question)
    {
        $collection = $this->_choiceCollectionFactory->create()->addFieldToFilter(
            'question_id',
            $question->getId()
        )->getChoices();

        return $collection;
    }

    /**
     * Delete Choices
     *
     * @param $choiceId
     *
     * @return $this
     */
    public function deleteChoices($choiceId)
    {
        $this->getResource()->deleteChoices($choiceId);
        return $this;
    }

    /**
     * Delete Child Choices
     *
     * @param $choiceId
     * @return $this
     */
    public function deleteChildChoices($choiceId)
    {
        $this->getResource()->deleteChildChoices($choiceId);
        return $this;
    }

    /**
     * Delete choice
     *
     * @param $questionId
     *
     * @return $this
     */
    public function deleteChoice($questionId)
    {
        $this->getResource()->deleteChoice($questionId);
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
     * Get Label
     *
     * @return mixed
     */
    public function getLabel()
    {
        return $this->_getData(self::KEY_LABEL);
    }

    /**
     * Get parent choice id
     *
     * @return mixed
     */
    public function getParentChoiceId()
    {
        return $this->_getData(self::KEY_PARENT_CHOICE_ID);
    }

    /**
     * Get Choice Id
     *
     * @return mixed
     */
    public function getChoiceId()
    {
        return $this->_getData(self::KEY_CHOICE_ID);
    }

    /**
     * Set choice id
     *
     * @param $choiceId
     * @return $this
     */
    public function setChoiceId($choiceId)
    {
        return $this->setData(self::KEY_CHOICE_ID, $choiceId);
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
     * Get enquente question no
     *
     * @return mixed
     */
    public function getEnqueteQuestionNo()
    {
        return $this->_getData(self::KEY_ENQUETE_QUESTION_NO);
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
     * Set label
     *
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        return $this->setData(self::KEY_LABEL, $label);
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
     * Set parent choice Id
     *
     * @param $parentChoiceId
     * @return $this
     */
    public function setParentChoiceId($parentChoiceId)
    {
        return $this->setData(self::KEY_PARENT_CHOICE_ID, $parentChoiceId);
    }

    /**
     * @param array $data
     */
    public function insertChoiceByArray($data = []){
        $this->getResource()->insertArrayChoice($data);
    }

    /**
     * @param array $data
     * @return array
     */
    public function updateChoices($data = [])
    {
        return $this->getResource()->updateChoices($data);
    }

    /**
     * @param $choiceNo
     * @return string
     */
    public function getChoiceIdByChoiceNo($choiceNo){
        return $this->getResource()->findChoiceIdByChoiceNo($choiceNo);
    }

    /**
     * get array of choice id to no
     *
     * @return array
     */
    public function getAllIdsToNo(){
        return $this->getResource()->getAllIdsToNo();
    }

    /**
     * @param $questionIds
     * @return array
     */
    public function getChoicesByQuestionIds($questionIds)
    {
        $choiceCollection = $this->getCollection();
        $choiceCollection->addFieldToFilter('question_id', ['in'=>$questionIds]);
        $choiceCollection->setOrder('sort_order', \Magento\Framework\Api\SortOrder::SORT_ASC);
        $choiceCollection->setOrder('parent_choice_id', \Magento\Framework\Api\SortOrder::SORT_ASC);
        $choiceData = [];
        foreach ($choiceCollection->getItems() as $choice) {
            if (!$choice->getData('parent_choice_id')) {
                $choiceData[$choice->getData('question_id')][$choice->getData('choice_id')] = [
                    'choice' => $choice,
                    'children' => []
                ];
            } else {
                $choiceData[$choice->getData('question_id')][$choice->getData('parent_choice_id')]['children'][] = $choice;
            }
        }
        return $choiceData;
    }
}
