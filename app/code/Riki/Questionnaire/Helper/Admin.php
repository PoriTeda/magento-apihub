<?php
namespace Riki\Questionnaire\Helper;

use \Riki\Questionnaire\Model\Questionnaire;

/**
 * Class Admin
 * @package Riki\Questionnaire\Helper
 */
class Admin extends Data
{

    /**
     * Save answer in create order admin
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $dataAnswers
     *
     * @return array
     */
    public function saveAnswersCreatedOrderAdmin($order, $dataAnswers)
    {
        
        $customerId = $order->getCustomerId();
        $orderId = $order->getId();
        $this->logQuestionOrder('Begin save  at BO for order; '.$orderId,$dataAnswers);
        foreach ($dataAnswers as $key => $item) {
            if (is_array($item) && !empty($item)) {
                try {
                    /** @var \Riki\Questionnaire\Model\Answers $answerModel */
                    $answerModel = $this->_answersFactory->create();
                    $replyArr = [];
                    $enqueteId = $item['enquete_id'];
                    $answerModel->setEnqueteId($enqueteId);
                    $answerModel->setCustomerId($customerId);
                    $answerModel->setEntityId($orderId);
                    $answerModel->setEntityType(Questionnaire::CHECKOUT_QUESTIONNAIRE);

                    if (!empty($item['questions'])) {
                        foreach ($item['questions'] as $questionKey => $question) {
                            $questionId = $questionKey;
                            // add content answer
                            if (isset($question['content']) && $question['content']!= "") {
                                $reply = [];
                                $reply['question_id'] = $questionId;
                                $reply['choice_id'] = null;
                                $reply['content'] = $question['content']; // null
                                $replyArr[] = $reply;
                            }
                            // end add content answer

                            // add choice answer
                            if (isset($question['choice_id']) && !empty($question['choice_id'])) {
                                $choices = $question['choice_id'];
                                if (isset($choices['id']) && $choices['id']!='' && $choices['id']!=0) {
                                    $reply = [];
                                    $reply['question_id'] = $questionId;
                                    $reply['choice_id'] = $choices['id'];
                                    $reply['content'] = null; // null
                                    $replyArr[] = $reply;
                                }
                                // add sub choice answer
                                if (isset($choices['sub']) && !empty($choices['sub'])) {
                                    $choicesSub = $choices['sub'];
                                    foreach ($choicesSub as $answer) {
                                        $reply = [];
                                        if ($answer !== '' && $answer !== 0) {
                                            $reply['question_id'] = $questionId;
                                            $reply['choice_id'] = $answer;
                                            $reply['content'] = null; // null
                                            $replyArr[] = $reply;
                                        }
                                    }
                                }
                                //end add sub choice answer

                            }
                            // end add choice answer


                        }
                    }
                    if (!empty($replyArr)) {
                        $answerModel->setAnswersReplys($replyArr);
                        try {
                            $answerModel->save();
                        } catch (\Exception $e) {
                            $this->logQuestionOrder('Save  at BO for order error; '.$e->getMessage());
                            $this->_logger->critical($e->getMessage());
                        }
                    }

                } catch (\Exception $e) {
                    $this->logQuestionOrder('Save  at BO for order error; '.$e->getMessage());
                    $this->_logger->critical($e->getMessage());
                }
            }
        }
        $this->logQuestionOrder('Completed  save  at BO for order; '.$orderId,$dataAnswers);
        return true;
    }
    public function saveAnswersCreatedOrderFrontEnd($order, $submittedData)
    {

        $customerId = $order->getCustomerId();
        $orderId = $order->getId();
        $this->logQuestionOrder('Begin save  at FO for order; '.$orderId,$submittedData);
        if (!empty($submittedData) && $orderId) {
            foreach ($submittedData as $item) {
                $answerModel = $this->_answersFactory->create();
                /** @var \Riki\Questionnaire\Api\Data\SubmittedAnswerDataInterface $item */
                $replyArr = [];
                $answerModel->setEnqueteId($item['enquete_id']);
                $answerModel->setCustomerId($customerId);
                $answerModel->setEntityId($orderId);
                $answerModel->setEntityType(Questionnaire::CHECKOUT_QUESTIONNAIRE);

                $questions = $item['questions'];
                if (!empty($questions)) {
                    foreach ($questions as $question) {
                        /** @var \Riki\Questionnaire\Api\Data\QuestionDataInterface $question*/
                        $answers = $question['answers'];
                        $questionId = $question['question_id'];
                        if (!empty($answers)) {
                            foreach ($answers as $answer) {
                                /** @var \Riki\Questionnaire\Api\Data\AnswerDataInterface $answer*/
                                $content = $answer['content'];
                                $choices = $answer['choices'];
                                if ($content == null || $content == '') {
                                    foreach ($choices as $choice) {
                                        $reply = [];
                                        if ($choice !== 0 && $choice !== null) {
                                            $reply['question_id'] = $questionId;
                                            $reply['choice_id'] = $choice;
                                            $reply['content'] = $content; // null
                                            $replyArr[] = $reply;
                                        }
                                    }
                                } else {
                                    $reply = [];
                                    $reply['question_id'] = $questionId;
                                    $reply['choice_id'] = null;
                                    $reply['content'] = $content;
                                    $replyArr[] = $reply;
                                }

                            }
                        }
                    }
                }
                if (!empty($replyArr)) {
                    $answerModel->setAnswersReplys($replyArr);
                    try {
                        $answerModel->save();
                    } catch (\Exception $e) {
                        $this->logQuestionOrder('Save  at FO for order error; '.$e->getMessage());
                        $this->_logger->critical($e);
                    }
                }
            }
        } else {
            $this->_logger->critical('Answers data not found!');
        }
        $this->logQuestionOrder('Completed  save  at FO for order; '.$orderId,$submittedData);
        return true;
    }
}
