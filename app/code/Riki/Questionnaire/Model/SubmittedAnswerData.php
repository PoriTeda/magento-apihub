<?php

namespace Riki\Questionnaire\Model;
use Riki\Questionnaire\Api\Data\QuestionDataInterface;

class SubmittedAnswerData
    implements \Riki\Questionnaire\Api\Data\SubmittedAnswerDataInterface
{
    protected $_enqueteId;
    protected $_questions;

    public function setEnqueteId($value)
    {
        return $this->_enqueteId = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnqueteId()
    {
        return $this->_enqueteId;
    }


    public function setQuestions(array $questions)
    {
        return $this->_questions = $questions;
    }

    /**
     * {@inheritDoc}
     */
    public function getQuestions()
    {
        return $this->_questions;
    }
    
}