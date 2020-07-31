<?php
namespace Riki\Questionnaire\Api\Data;


/**
 * Interface SubmittedAnswerDataInterface
 * @package Riki\Questionnaire\Api\Data
 */
interface SubmittedAnswerDataInterface{

    public function setEnqueteId($value);

    /**
     * @return int enquete id
     */
    public function getEnqueteId();
    
    
    public function setQuestions(array $questions);

    /**
     * @return \Riki\Questionnaire\Api\Data\QuestionDataInterface[] $questionItem
     */
    public function getQuestions();
}