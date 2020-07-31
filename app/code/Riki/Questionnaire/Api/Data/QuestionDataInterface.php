<?php

namespace Riki\Questionnaire\Api\Data;

interface QuestionDataInterface{

    public function setQuestionId($questionId);

    /**
     * @return int
     */
    public function getQuestionId();
    
    public function setAnswers(array $answers);

    /**
     * @return \Riki\Questionnaire\Api\Data\AnswerDataInterface[] $answerItem
     */
    public function getAnswers();
    

}