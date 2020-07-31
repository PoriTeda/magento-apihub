<?php
namespace Riki\Questionnaire\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Answers
 * @package Riki\Questionnaire\Model\ResourceModel
 */
class Answers extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_enquete_answer', 'answer_id');
    }

    /**
     * Get List Answer by customer Id
     *
     * @param $customerId
     *
     * @return array
     */
    public function getAnswersByCustomerId($customerId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete_answer'),
            ['answer_id', 'customer_id']
        )->where(
            'customer_id = ?',
            $customerId
        );
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get List answer by Order Id
     *
     * @param $orderId
     *
     * @return array
     */
    public function getAnswersByOrderId($orderId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete_answer'),
            ['answer_id', 'order_id']
        )->where(
            'order_id = ?',
            $orderId
        );
        return $this->getConnection()->fetchAll($select);
    }

    /**
     * Get list answer by enquete id
     *
     * @param $enqueteId
     *
     * @return array
     */
    public function getAnswersByEnqueteId($enqueteId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riki_enquete_answer'),
            ['answer_id', 'enquete_id']
        )->where(
            'enquete_id = ?',
            $enqueteId
        );
        return $this->getConnection()->fetchAll($select);
    }

}