<?php
namespace Riki\Questionnaire\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;

/**
 * Class Answers
 * @package Riki\Questionnaire\Model
 */
class Answers extends AbstractModel
{
    const KEY_ANSWER_ID = 'answer_id';
    const KEY_ENQUETE_ID = 'enquete_id';
    const KEY_CUSTOMER_ID = 'customer_id';
    const KEY_ENTITY_ID = 'entity_id';
    const KEY_ENTITY_TYPE = 'entity_type';
    const KEY_CREATED_AT = 'created_at';
    const KEY_UPDATED_AT = 'updated_at';
    const QUESTIONNAIRE_ANSWER_TYPE_ORDER = 0;
    const QUESTIONNAIRE_ANSWER_TYPE_PROFILE = 1;

    /**
     * @var array
     */
    protected $_reply = [];

    /**
     * @var ReplyFactory
     */
    protected $replyFactory;

    /**
     * @var Reply
     */
    protected $replyInstance;

    /**
     * @var bool
     */
    protected $replyInitialized = false;

    /**
     * Answers constructor.
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ReplyFactory $replyFactory
     * @param ResourceModel\Answers|null $resource
     * @param ResourceModel\Answers\Collection|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Questionnaire\Model\ReplyFactory $replyFactory,
        \Riki\Questionnaire\Model\ResourceModel\Answers $resource = null,
        \Riki\Questionnaire\Model\ResourceModel\Answers\Collection $resourceCollection = null,
        array $data = []
    ) {
        $this->replyFactory = $replyFactory;
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('Riki\Questionnaire\Model\ResourceModel\Answers');
    }

    /**
     * Get enquete id
     *
     * @return mixed
     */
    public function getEnqueteId()
    {
        return $this->_getData(self::KEY_ENQUETE_ID);
    }

    /**
     * Get customer id
     *
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->_getData(self::KEY_CUSTOMER_ID);
    }

    /**
     * Get order id
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->_getData(self::KEY_ENTITY_ID);
    }

    /**
     * Get created at
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->_getData(self::KEY_CREATED_AT);
    }

    /**
     * Get updated at
     *
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->_getData(self::KEY_UPDATED_AT);
    }

    /**
     * Retrieve answers reply instance
     *
     * @return Reply
     */
    public function getReplyInstance()
    {
        if (!isset($this->replyInstance)) {
            $this->replyInstance = $this->replyFactory->create();
            $this->replyInstance->setAnswers($this);
        }
        return $this->replyInstance;
    }

    /**
     * Get list reply of answers
     *
     * @return ResourceModel\Reply\Collection
     */
    public function getAnswersReplyCollection()
    {
        $collection = $this->getReplyInstance()->getAnswersReplyCollection($this);

        return $collection;
    }

    /**
     * Before Save. Add reply for save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->getReplyInstance()->unsetReply();
        // $reply set in save controller
        $replies = $this->getAnswersReplys();

        if (!empty($replies)) {
            foreach ($replies as $reply) {
                $this->getReplyInstance()->addReply($reply);
            }
        }

        return parent::beforeSave();
    }

    /**
     * After save. Save reply of answer
     *
     * @return $this
     */
    public function afterSave()
    {
        if (!empty($this->getReplyInstance()->getReply())) {
            $this->replyInstance->setAnswers($this)->saveReply();
        }

        return parent::afterSave();
    }

    /**
     * Add reply to array of answer reply
     *
     * @param Reply $reply
     *
     * @return $this
     */
    public function addReply(Reply $reply)
    {
        $this->_reply[$reply->getId()] = $reply;
        return $this;
    }

    /**
     * Get reply from reply array of answer by given reply id
     *
     * @param $replyId
     *
     * @return mixed|null
     */
    public function getReplyById($replyId)
    {
        if (isset($this->_reply[$replyId])) {
            return $this->_reply[$replyId];
        }

        return null;
    }

    /**
     * Get All reply of answer
     *
     * @return array
     */
    public function getReply()
    {
        if (empty($this->_reply) && !$this->replyInitialized) {
            $collection = $this->getAnswersReplyCollection();
            foreach ($collection as $item) {
                $item->setAnswers($this);
                $this->addReply($item);
            }
            $this->replyInitialized = true;
        }
        return $this->_reply;
    }

    /**
     * Set reply
     *
     * @param array|null $reply
     *
     * @return $this
     */
    public function setReply(array $reply = null)
    {
        $this->_reply = $reply;

        return $this;
    }

    /**
     * Clearing references on answers
     *
     * @return $this
     */
    protected function _clearReferences()
    {
        $this->_clearOptionReferences();
        return $this;
    }

    /**
     * Clearing references to answers from answer's reply
     *
     * @return $this
     */
    protected function _clearOptionReferences()
    {
        /**
         * unload questionnaire questions
         */
        if (!empty($this->_reply)) {
            foreach ($this->_reply as $reply) {
                $reply->setAnswers();
                $reply->clearInstance();
            }
        }

        return $this;
    }

    /**
     * Clearing questionnaire's data
     *
     * @return $this
     */
    protected function _clearData()
    {
        foreach ($this->_data as $data) {
            if ($data instanceof \Magento\Framework\DataObject && method_exists($data, 'reset') && is_callable([$data, 'reset'])) {
                $data->reset();
            }
        }
        $this->setData([]);
        $this->setOrigData();
        $this->_reply = [];

        return parent::_clearData();
    }

    /**
     * Set enquete id
     *
     * @param $enqueteId
     *
     * @return $this
     */
    public function setEnqueteId($enqueteId)
    {
        return $this->setData(self::KEY_ENQUETE_ID, $enqueteId);
    }

    /**
     * Set customer id
     *
     * @param $customId
     *
     * @return $this
     */
    public function setCustomerId($customId)
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customId);
    }

    /**
     * Set entity id
     *
     * @param $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::KEY_ENTITY_ID, $entityId);
    }

    /**
     * Set entity type
     * @param $entityType
     * @return Answers
     */
    public function setEntityType($entityType)
    {
        return $this->setData(self::KEY_ENTITY_TYPE, $entityType);
    }

    /**
     * Set created at
     *
     * @param $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    /**
     * Set updated at
     *
     * @param $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }
}
