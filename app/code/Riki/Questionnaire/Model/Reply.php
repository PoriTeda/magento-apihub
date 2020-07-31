<?php
namespace Riki\Questionnaire\Model;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;

/**
 * Class Reply
 * @package Riki\Questionnaire\Model
 *
 * @method \Riki\Questionnaire\Model\ResourceModel\Reply _getResource()
 * @method \Riki\Questionnaire\Model\ResourceModel\Reply getResource()
 */
class Reply extends \Magento\Framework\Model\AbstractModel
{
    const KEY_REPLY_ID = 'reply_id';
    const KEY_ANSWER_ID = 'answer_id';
    const KEY_QUESTION_ID = 'question_id';
    const KEY_CHOICE_ID = 'choice_id';
    const KEY_CONTENT = 'content';

    /**
     * @var array
     */
    protected $reply = [];

    /**
     * @var Answers
     */
    protected $_answers;

    /**
     * @var AnswersFactory
     */
    protected $_answersFactory;

    /**
     * Reply constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        Answers $answers,
        AnswersFactory $answersFactory,
        AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_answersFactory = $answersFactory;
        $this->_answers = $answers;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Questionnaire\Model\ResourceModel\Reply');
    }

    /**
     * Get answers
     *
     * @return Answers
     */
    public function getAnswers()
    {
        return $this->_answers;
    }

    /**
     * Set Answers
     *
     * @param Answers|null $answers
     *
     * @return $this
     */
    public function setAnswers(Answers $answers = null)
    {
        $this->_answers = $answers;
        return $this;
    }

    /**
     * Get Reply
     *
     * @return array
     */
    public function getReply()
    {
        return $this->reply;
    }

    /**
     * Set Reply
     *
     * @param $reply
     *
     * @return $this
     */
    public function setReply($reply)
    {
        $this->reply = $reply;
        return $this;
    }

    /**
     * Add reply for save it
     *
     * @param $reply
     *
     * @return $this
     */
    public function addReply($reply)
    {
        $this->reply[] = $reply;
        return $this;
    }

    /**
     * Unset Reply
     *
     * @return $this
     */
    public function unsetReply()
    {
        $this->reply = [];
        return $this;
    }

    /**
     * Save Reply
     *
     * @return $this
     */
    public function saveReply()
    {
        foreach ($this->getReply() as $reply) {
            $this->_validatorBeforeSave = null;
            $this->setData($reply)
                ->setData(
                    'answer_id',
                    $this->getAnswers()->getId()
                );
            if ($this->getData('reply_id') == '0') {
                $this->unsetData('reply_id');
            } else {
                $this->setId($this->getData('reply_id'));
            }

            $this->save();

        }
        return $this;
    }

    /**
     * Set reply Id
     *
     * @param $replyId
     *
     * @return $this
     */
    public function setReplyId($replyId)
    {
        return $this->setData(self::KEY_REPLY_ID, $replyId);
    }

    /**
     * Set answer id
     *
     * @param $answerId
     *
     * @return $this
     */
    public function setAnswerId($answerId)
    {
        return $this->setData(self::KEY_ANSWER_ID, $answerId);
    }

    /**
     * Set Question Id
     *
     * @param $questionId
     *
     * @return $this
     */
    public function setQuestionId($questionId)
    {
        return $this->setData(self::KEY_QUESTION_ID, $questionId);
    }

    /**
     * Set Choice Id
     *
     * @param $choiceId
     *
     * @return $this
     */
    public function setChoiceId($choiceId)
    {
        return $this->setData(self::KEY_CHOICE_ID, $choiceId);
    }

    /**
     * Set content
     *
     * @param $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        return $this->setData(self::KEY_CONTENT, $content);
    }

    /**
     * Get list reply of answer
     *
     * @param Answers $answers
     *
     * @return ResourceModel\Reply\Collection
     */
    public function getAnswersReplyCollection(Answers $answers)
    {
        /** @var \Riki\Questionnaire\Model\ResourceModel\Reply\Collection $collection */
        $collection = clone $this->getCollection();
        $collection->addFieldToFilter(
            'answer_id',
            $answers->getId()
        )->setOrder(
            'reply_id',
            'asc'
        );

        return $collection;
    }

     /**
     * Clearing object's data
     *
     * @return $this
     */
    protected function _clearData()
    {
        $this->_data = [];
        return $this;
    }

    /**
     * Clearing cyclic references
     *
     * @return $this
     */
    protected function _clearReferences()
    {
        return parent::_clearReferences();
    }

    /**
     * Get Reply id
     *
     * @return mixed
     */
    public function getReplyId()
    {
        return $this->getData(self::KEY_REPLY_ID);
    }

    /**
     * Get answer id
     *
     * @return mixed
     */
    public function getAnswerId()
    {
        return $this->getData(self::KEY_ANSWER_ID);
    }

    /**
     * Get Question Id
     *
     * @return mixed
     */
    public function getQuestionId()
    {
        return $this->getData(self::KEY_QUESTION_ID);
    }

    /**
     * Get Choice Id
     *
     * @return mixed
     */
    public function getChoiceId()
    {
        return $this->getData(self::KEY_CHOICE_ID);
    }

    /**
     * Get content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->getData(self::KEY_CONTENT);
    }
}