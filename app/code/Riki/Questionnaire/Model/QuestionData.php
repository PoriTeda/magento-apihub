<?php
namespace  Riki\Questionnaire\Model;

use Riki\Questionnaire\Api\Data\QuestionDataInterface;
use Riki\Questionnaire\Api\Data\AnswerDataInterface;

class QuestionData implements QuestionDataInterface
{
    protected $_answers;
    protected $_questionId;
    
    public function setQuestionId($questionId)
    {
        return $this->_questionId = $questionId;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuestionId()
    {
        return $this->_questionId;
    }

    public function setAnswers(array $answers)
    {
        return $this->_answers = $answers;
    }

    /**
     * {@inheritDoc}
     */
    public function getAnswers()
    {
        return $this->_answers;
    }
}