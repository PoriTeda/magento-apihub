<?php
namespace Riki\Questionnaire\Block\Adminhtml\Answers\View\Tab;
use Magento\Backend\Block\Widget;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Riki\Questionnaire\Model\ChoiceFactory;
use Riki\Questionnaire\Model\QuestionFactory;

/**
 * Class Answer
 * @package Riki\Questionnaire\Block\Adminhtml\Answers\View\Tab
 */
class Answer extends Widget implements TabInterface
{
    /**
     * @var string
     */
    protected $_template = 'answers/view/options.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var ChoiceFactory
     */
    protected $_choiceFactory;

    /**
     * @var QuestionFactory
     */
    protected $_questionFactory;

    /**
     * @var \Magento\Framework\DataObject[]
     */
    protected $_values = [];

    /**
     * @var \Riki\Questionnaire\Model\Answers
     */
    protected $_answerInstance;

    /**
     * @var \Riki\Questionnaire\Model\Answers
     */
    protected $_answers;

    /**
     * Answer constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Questionnaire\Model\Answers $answers
     * @param QuestionFactory $questionFactory
     * @param ChoiceFactory $choiceFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Model\Answers $answers,
        \Riki\Questionnaire\Model\QuestionFactory $questionFactory,
        \Riki\Questionnaire\Model\ChoiceFactory $choiceFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_answers = $answers;
        $this->_questionFactory = $questionFactory;
        $this->_choiceFactory = $choiceFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get Answer current
     *
     * @return mixed|\Riki\Questionnaire\Model\Answers
     */
    public function getAnswers()
    {
        if (!$this->_answerInstance) {
            $answer = $this->_coreRegistry->registry('current_answers');
            if ($answer) {
                $this->_answerInstance = $answer;
            } else {
                $this->_answerInstance = $this->_answers;
            }
        }
        return $this->_answerInstance;
    }

    /**
     * Set answer
     *
     * @param $answers
     *
     * @return $this
     */
    public function setAnswers($answers)
    {
        $this->_answerInstance = $answers;

        return $this;
    }

    /**
     * Get Current answer id
     *
     * @return mixed
     */
    public function getCurrentAnswerId()
    {
        return $this->getAnswers()->getId();
    }

    /**
     * Get list answer reply data
     *
     * @return array|\Magento\Framework\DataObject[]
     */
    public function getAnswerValues()
    {
        $answerArr = $this->getAnswers()->getReply();
        $questionModel = $this->_questionFactory->create();
        $choiceModel = $this->_choiceFactory->create();

        if ($answerArr == null) {
            $answerArr = [];
        }

        if (!$this->_values) {

            if (!empty($answerArr)) {
                $values= [];
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
                $this->_values = $values;
            }

        }
        return $this->_values;
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


    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('List Reply');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('List Reply');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}