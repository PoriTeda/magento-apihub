<?php
namespace Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab\Options;

use Magento\Backend\Block\Widget;
use Riki\Questionnaire\Model\Questionnaire;

/**
 * Class Option
 * @package Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab\Options
 */
class Option extends Widget
{
    /**
     * @var string
     */
    protected $_template = 'questions/edit/options/option.phtml';

    /**
     * @var \Magento\Framework\DataObject[]
     */
    protected $_values = [];

    /**
     * @var Questionnaire
     */
    protected $_questionnaireInstance;

    /**
     * @var Questionnaire
     */
    protected $_questionnaire;

    /**
     * @var int
     */
    protected $_itemCount = 1;
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    
    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_configYesNo;
    
    /**
     * @var \Riki\Questionnaire\Model\Config\Source\Questions\Options\Type
     */
    protected $_optionType;
    protected  $_helperData;

   
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Config\Model\Config\Source\Yesno $configYesNo,
        \Riki\Questionnaire\Model\Config\Source\Questions\Options\Type $optionType,
        Questionnaire $questionnaire,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Helper\Data  $helperData,
        array $data = []
    ) {
        $this->_configYesNo = $configYesNo;
        $this->_coreRegistry = $registry;
        $this->_optionType = $optionType;
        $this->_questionnaire = $questionnaire;
        $this->_helperData = $helperData;
        parent::__construct($context, $data);
    }
    
    /**
     * Retrieve options field name prefix
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'questionnaire[questions]';
    }

    /**
     * Retrieve options field id prefix
     *
     * @return string
     */
    public function getFieldId()
    {
        return 'question_option';
    }

    /**
     * Question Option Url
     *
     * @return string
     */
    public function getQuestionOptionsUrl()
    {
        return $this->getUrl('questionnaire/*/questionOptions');
    }

    /**
     * Check block is readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return null;
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        return $this->_itemCount;
    }

    /**
     * @param int $itemCount
     * @return $this
     */
    public function setItemCount($itemCount)
    {
        $this->_itemCount = max($this->_itemCount, $itemCount);
        return $this;
    }

    /**     
     * Get data Question     
     * 
     * @return mixed|Questionnaire
     */
    public function getQuestionnaire()                   
    {
        if (!$this->_questionnaireInstance) {
            $questionnaire = $this->_coreRegistry->registry('current_questionnaire');
            if ($questionnaire) {
                $this->_questionnaireInstance = $questionnaire;
            } else {
                $this->_questionnaireInstance = $this->_questionnaire;
            }
        }
        return $this->_questionnaireInstance;
    }

    /**
     * Set Question
     *
     * @param $questionnaire
     * @return $this
     */
    public function setQuestionnaire($questionnaire)
    {
        $this->_questionnaireInstance = $questionnaire;

        return $this;
    }

    /**
     * Get Current Question Id
     * 
     * @return mixed
     */
    public function getCurrentQuestionnaireId()
    {
        return $this->getQuestionnaire()->getId();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'select_option_type',
            'Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab\Options\Type\Select'
        );

        return parent::_prepareLayout();
    }
    
    /**
     * @return mixed
     */
    public function getTypeSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setData(
            [
                'id' => $this->getFieldId() . '_<%- data.id %>_type',
                'class' => 'select select-question-option-type required-option-select',
            ]
        )->setName(
            $this->getFieldName() . '[<%- data.id %>][type]'
        )->setOptions(
            $this->_optionType->toOptionArray()
        );

        return $select->getHtml();
    }

    /**
     * @return mixed
     */
    public function getRequireSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            'Magento\Framework\View\Element\Html\Select'
        )->setData(
            ['id' => $this->getFieldId() . '_<%- data.id %>_is_required', 'class' => 'select']
        )->setName(
            $this->getFieldName() . '[<%- data.id %>][is_required]'
        )->setOptions(
            $this->_configYesNo->toOptionArray()
        );

        return $select->getHtml();
    }
    
    /**
     * Retrieve html templates for different types of product custom options
     *
     * @return string
     */
    public function getTemplatesHtml()
    {
        $this->getChildBlock('select_option_type');

        $templates = $this->getChildHtml(
            'select_option_type'
        ) ;

        return $templates;
    }

    /**
     * Get List Question data
     *
     * @return array|\Magento\Framework\DataObject[]
     */
    public function getQuestionValues()
    {
        $questionArr = $this->getQuestionnaire()->getQuestions();

        if ($questionArr == null) {
            $questionArr = [];
        }

        if (!$this->_values) {
            $values = [];

            if (!empty($questionArr)) {
                foreach ($questionArr as $question) {
                    /** @var \Riki\Questionnaire\Model\Question $question */
                    $this->setItemCount($question->getQuestionId());

                    $value = [];

                    $value['id'] = $question->getQuestionId();
                    $value['question_id'] = $question->getQuestionId();
                    $value['title'] = $question->getTitle();
                    $value['type'] = $question->getType();
                    $value['is_required'] = $question->getIsRequired();
                    $value['sort_order'] = $question->getSortOrder();
                    $value['item_count'] = $this->getItemCount();

                    $i = 0;
                    $itemCount = 0;

                    $choices = $question->getChoices();

                    if (!empty($choices)) {
                        foreach ($choices as $choice) {
                            $exist = 0;
                            /** @var \Riki\Questionnaire\Model\Choice $choice */
                            //$rePlyExist = $this->_helperData->getRepliesByAnswerId( $choice->getChoiceId());
                            //if($rePlyExist){
                                $exist = 1;
                            //}
                            $value['optionChoices'][$i] = [
                                'item_count' => max($itemCount, $choice->getChoiceId()),
                                'question_id' => $choice->getQuestionId(),
                                'choice_id' => $choice->getChoiceId(),
                                'label' => $choice->getLabel(),
                                'sort_order' => $choice->getSortOrder(),
                                'parent_choice_id' => $choice->getParentChoiceId(),
                                'hide_delete' => $choice->getHideDelete(),
                                'exist' => $exist
                            ];
                            $i++;
                        }
                    }

                    $values[] = new \Magento\Framework\DataObject($value);

                }
                $this->_values = $values;
            }
        }

        return $this->_values;
    }
    
}