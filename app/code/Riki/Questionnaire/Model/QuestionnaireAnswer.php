<?php
namespace Riki\Questionnaire\Model;

use Riki\Questionnaire\Model\Config\Source\Questions\Options\Type as QuestionType;

/**
 * Class QuestionnaireAnswer
 * @package Riki\Questionnaire\Model
 */
class QuestionnaireAnswer extends \Magento\Framework\Model\AbstractModel     implements \Riki\Questionnaire\Api\QuestionnaireAnswerInterface
{
    const FIELD_ENTITY_TYPE_ORDER = 0;
    const FIELD_ENTITY_TYPE_PROFILE = 1;

    /**
     * @var AnswersFactory
     */
    protected $_answersFactory;

    /**
     * @var Questionnaire
     */
    protected $_questionnaire;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * QuestionnaireAnswer constructor.
     * @param AnswersFactory $answersFactory
     * @param Questionnaire $questionnaireModel
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Psr\Log\LoggerInterface $loggerInterface
     */
    public function __construct(
        \Riki\Questionnaire\Model\AnswersFactory $answersFactory,
        \Riki\Questionnaire\Model\Questionnaire $questionnaireModel,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $loggerInterface
    ) {
        $this->_quoteFactory = $quoteFactory;
        $this->_answersFactory = $answersFactory;
        $this->_questionnaire = $questionnaireModel;
        $this->_orderFactory = $orderFactory;
        $this->logger = $loggerInterface;
    }

    /**
     * {@inheritDoc}
     */
    public function saveAnswerQuestionnaireAfterPlaceOrder(
        $customerId,
        $quoteId,
        array $submittedData
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->_quoteFactory->create()->load($quoteId);
        $orderIncrementId = $quote->getReservedOrderId();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_orderFactory->create()->loadByIncrementIdAndStoreId($orderIncrementId, $quote->getStoreId());
        $orderId = $order->getId();
        if (!empty($submittedData) && $orderId) {
            foreach ($submittedData as $item) {
                /** @var \Riki\Questionnaire\Model\Answers $answerModel */
                $answerModel = $this->_answersFactory->create();
                $replyArr = [];
                $answerModel->setEnqueteId($item->getEnqueteId());
                $answerModel->setCustomerId($customerId);
                $answerModel->setEntityId($orderId);
                $answerModel->setEntityType(Questionnaire::CHECKOUT_QUESTIONNAIRE);
                /** @var \Riki\Questionnaire\Api\Data\SubmittedAnswerDataInterface $item */
                $questions = $item->getQuestions();
                if (!empty($questions)) {
                    foreach ($questions as $question) {
                        /** @var \Riki\Questionnaire\Api\Data\QuestionDataInterface $question*/
                        $answers = $question->getAnswers();
                        $questionId = $question->getQuestionId();
                        if (!empty($answers)) {
                            foreach ($answers as $answer) {
                                /** @var \Riki\Questionnaire\Api\Data\AnswerDataInterface $answer*/
                                $content = $answer->getContent();
                                $choices = $answer->getChoices();
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
                        $this->logger->critical($e);
                    }
                }
            }
        } else {
            $this->logger->critical('Answers data not found!');
        }

        return true;
    }

    /**
     * @param $questions
     * @param $postData
     * @param $profileId
     * @param $customerId
     * @throws \Exception
     */
    public function saveAnswerQuestionnaireAfterCancelProfile($questions, $postData, $profileId, $customerId)
    {
        if ($profileId) {
            if ($questions) {
                foreach ($questions as $enqueteId => $questionItems) {
                    $answer = $this->_answersFactory->create();
                    $answerData = [
                        'enquete_id' => $enqueteId,
                        'customer_id' => $customerId,
                        'entity_id' => $profileId,
                        'entity_type' => self::FIELD_ENTITY_TYPE_PROFILE
                    ];
                    $answer->setData($answerData);
                    //bind Reply data
                    $replyData = [];
                    foreach ($questionItems as $question) {
                        $parentReplyData = $this->getReplyData($postData, $question);
                        if (!empty($parentReplyData)) {
                            $replyData[] = $parentReplyData;
                        }
                        if ($question->getType() != QuestionType::TYPE_TEXT) {
                            $childReplyData = $this->getReplyData($postData, $question, true);
                            if (!empty($childReplyData)) {
                                $replyData[] = $childReplyData;
                            }
                        }
                    }
                    if (!empty($replyData)) {
                        $answer->setAnswersReplys($replyData);
                        $answer->save();
                    }
                }
            }
        }
    }

    /**
     * @param $postData
     * @param $question
     * @param bool $child
     * @return array
     */
    private function getReplyData($postData, $question, $child = false)
    {
        $questionId = $question->getQuestionId();
        $questionnaireId = $question->getEnqueteId();
        $replyKey = 'questionnaire_reply_'.$questionnaireId.'_'.$questionId;
        $replyData = [];
        switch ($question->getData('type')) {
            case QuestionType::TYPE_TEXT:
                if (array_key_exists($replyKey, $postData) && !$child) {
                    $replyData = $this->_filterReplyData(
                        $questionId,
                        $postData[$replyKey],
                        false
                    );
                }
                break;
            case QuestionType::TYPE_DROP_DOWN:
            case QuestionType::TYPE_RADIO:
            default:
                //parent choice
                if (!$child) {
                    if (array_key_exists($replyKey, $postData)) {
                        $replyData = $this->_filterReplyData(
                            $questionId,
                            $postData[$replyKey]
                        );
                    }
                } else {
                    if (array_key_exists($replyKey, $postData)) {
                        $childrenChoiceKey = $replyKey . '_child_' . $postData[$replyKey];
                        if (array_key_exists($childrenChoiceKey, $postData)) {
                            if ($postData[$childrenChoiceKey]) {
                                $replyData = $this->_filterReplyData(
                                    $questionId,
                                    $postData[$childrenChoiceKey]
                                );
                            }
                        }
                    }
                }
                break;
        }
        return $replyData;
    }

    /**
     * Make sure that reply answer has value
     *
     * @param $questionId
     * @param $answerData
     * @param bool $choice
     * @return null|array
     */
    private function _filterReplyData($questionId, $answerData, $choice = true)
    {
        if (trim($answerData)) {
            if ($choice) {
                return [
                    'question_id' => $questionId,
                    'choice_id' => $answerData
                ];
            } else {
                return [
                    'question_id' => $questionId,
                    'content' => $answerData
                ];
            }
        }
        return null;
    }

    /**
     * @param $entityId
     * @param int $type
     * @return \Magento\Framework\DataObject[]
     */
    public function getQuestionnaireAnswerByEntity($entityId, $type = self::FIELD_ENTITY_TYPE_ORDER)
    {
        $questionCollection = $this->getCollection();
        $questionCollection->addFieldToFilter('entity_id', $entityId);
        $questionCollection->addFieldToFilter('type', $type);
        return $questionCollection->getItems();
    }
}
