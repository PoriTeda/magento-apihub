<?php
namespace Riki\Questionnaire\Block\Adminhtml\Order\View;

use Riki\Questionnaire\Model\ChoiceFactory;
use Riki\Questionnaire\Model\QuestionFactory;

class Questionnaire extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory
     */
    protected $_answersCollectionFactory;

    /**
     * @var ChoiceFactory
     */
    protected $_choiceFactory;

    /**
     * @var QuestionFactory
     */
    protected $_questionFactory;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory $answersCollectionFactory,
        \Riki\Questionnaire\Model\QuestionFactory $questionFactory,
        \Riki\Questionnaire\Model\ChoiceFactory $choiceFactory,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
        $this->_answersCollectionFactory = $answersCollectionFactory;
        $this->_questionFactory = $questionFactory;
        $this->_choiceFactory = $choiceFactory;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getQuestionnaireAnswer()
    {
        $answers = [];
        $order = $this->getOrder();
        $answerCollection = $this->_answersCollectionFactory->create()
            ->addFieldToFilter('entity_id', $order->getId())
            ->load();
        $count = $answerCollection->getSize();
        if ($count > 0) {
            foreach ($answerCollection as $item) {
                /** @var $item \Riki\Questionnaire\Model\Answers */
                $answers[$item->getId()] = $this->getAnswerValues($item);

            }
        }
        return $answers;
    }

    /**
     * Get list replies data of answer in admin
     *
     * @param \Riki\Questionnaire\Model\Answers $answers
     *
     * @return array|\Magento\Framework\DataObject[]
     */
    public function getAnswerValues($answers)
    {
        $answerArr = $answers->getReply();
        $questionModel = $this->_questionFactory->create();
        $choiceModel = $this->_choiceFactory->create();

        if ($answerArr == null) {
            $answerArr = [];
        }
        $valuesOutput = [];

        if (!empty($answerArr)) {
            $values = [];
            foreach ($answerArr as $reply) {
                /** @var \Riki\Questionnaire\Model\Reply $reply */

                $choiceLabel = $questionTitle = $content = '';

                $choiceId = $reply->getChoiceId();
                if (!is_null($choiceId)) {
                    $choiceLabel = $choiceModel->load($choiceId)->getLabel();
                }
                $questionId = $reply->getQuestionId();

                if (!is_null($questionId)) {
                    $questionTitle = $questionModel->load($questionId)->getTitle();
                }

                $content = $reply->getContent();
                $values[$reply->getQuestionId()]['question'] = $questionTitle;
                $values[$reply->getQuestionId()]['replies'][$reply->getReplyId()]['id'] = $reply->getReplyId();
                $values[$reply->getQuestionId()]['replies'][$reply->getReplyId()]['choice'] = $choiceLabel;
                $values[$reply->getQuestionId()]['replies'][$reply->getReplyId()]['content'] = $content;

            }
            $valuesOutput = $values;
        }
        return $valuesOutput;

    }
    
    
    /**
     * Render Reply Data
     *
     * @param $data
     *
     * @return string
     */
    public function renderReply($data)
    {
        $htmlReply = '';
        $level = 1;
        $total = count($data);
        foreach ($data as $reply) {
            if ($total === 1) {
                if (isset($reply['choice']) && $reply['choice'] != '') {
                    $htmlReply .= '<p>' . __('Answer: ') . $reply['choice'] . '</p>';
                }
                if (isset($reply['content']) && $reply['content'] != '') {
                    $htmlReply .= '<p>' . __('Answer: ') . $reply['content'] . '</p>';
                }
            } elseif ($level === 1) {
                if (isset($reply['choice']) && $reply['choice'] != '') {
                    $htmlReply .= '<p>' . __('%1st answer: ', $level) . $reply['choice'] . '</p>';
                }
                if (isset($reply['content']) && $reply['content'] != '') {
                    $htmlReply .= '<p>' . __('%1st answer: ', $level) . $reply['content'] . '</p>';
                }
            } else {
                if (isset($reply['choice']) && $reply['choice'] != '') {
                    $htmlReply .= '<p>' . __('%1nd answer: ', $level) . $reply['choice'] . '</p>';
                }
                if (isset($reply['content']) && $reply['content'] != '') {
                    $htmlReply .= '<p>' . __('%1nd answer: ', $level) . $reply['content'] . '</p>';
                }
            }
            $level++;
        }
        return $htmlReply;
    }
}
