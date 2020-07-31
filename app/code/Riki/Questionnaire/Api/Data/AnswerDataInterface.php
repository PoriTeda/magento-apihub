<?php

namespace Riki\Questionnaire\Api\Data;

interface AnswerDataInterface{
    
    public function setChoices(array $choices);

    /**
     * @return int[]
     */
    public function getChoices();

    public function setContent($content);
    /**
     * @return string 
     */
    public function getContent();


}