<?php
namespace Riki\Questionnaire\Model\ResourceModel\Choice;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 * @package Riki\Questionnaire\Model\ResourceModel\Choice
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'choice_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Questionnaire\Model\Choice',
            'Riki\Questionnaire\Model\ResourceModel\Choice'
        );
    }

    /**
     * Get Choices Collection
     *
     * @return $this
     */
    public function getChoices()
    {
        return $this;
    }

    /**
     * Add question filter
     *
     * @param $questionIds
     * @return $this
     */
    public function getChoicesByQuestion($questionIds)
    {
        if (!is_array($questionIds)) {
            $questionIds = [$questionIds];
        }
        
        return $this->addFieldToFilter('main_table.choice_id', ['in' => $questionIds]);
    }

    /**
     * Add question to filter
     *
     * @param $question
     * @return $this
     */
    public function addQuestionToFilter($question)
    {
        if (empty($question)) {
            $this->addFieldToFilter('question_id', '');
        } elseif (is_array($question)) {
            $this->addFieldToFilter('question_id', ['in' => $question]);
        } elseif ($question instanceof \Riki\Questionnaire\Model\Question ) {
            $this->addFieldToFilter('question_id', $question->getId());
        } else {
            $this->addFieldToFilter('question_id', $question);
        }
        return $this;
    }
    
}