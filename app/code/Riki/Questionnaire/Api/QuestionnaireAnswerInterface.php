<?php

namespace Riki\Questionnaire\Api;

use Riki\Questionnaire\Api\Data\SubmittedAnswerDataInterface;

/**
 * Interface QuestionnaireAnswerInterface
 * @package Riki\Questionnaire\Api
 */
interface QuestionnaireAnswerInterface{

    /**
     * Save questionnaire after place order
     * 
     * @param int $customerId
     * @param int $quoteId
     * @param \Riki\Questionnaire\Api\Data\SubmittedAnswerDataInterface[] $submittedData
     * @return bool
     */
    public function saveAnswerQuestionnaireAfterPlaceOrder(
        $customerId,
        $quoteId ,
        array $submittedData
    );
}