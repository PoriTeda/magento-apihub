<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel;

/**
 * Resource model for customer and customer segment relation model.
 *
 * @api
 * @since 100.0.2
 */
class Customer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $connectionName = null
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $connectionName);
    }

    /**
     * Intialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_customersegment_customer', 'customer_id');
    }

    /**
     * Save relations between customer id and segment ids with specific website id
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        $now = $this->dateTime->formatDate(time(), true);
        foreach ($segmentIds as $segmentId) {
            $data = [
                'segment_id' => $segmentId,
                'customer_id' => $customerId,
                'added_date' => $now,
                'updated_date' => $now,
                'website_id' => $websiteId,
            ];
            $this->getConnection()->insertOnDuplicate($this->getMainTable(), $data, ['updated_date']);
        }
        return $this;
    }

    /**
     * Remove relations between customer id and segment ids on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @param array $segmentIds
     * @return $this
     */
    public function removeCustomerFromWebsiteSegments($customerId, $websiteId, $segmentIds)
    {
        if (!empty($segmentIds)) {
            $this->getConnection()->delete(
                $this->getMainTable(),
                ['customer_id=?' => $customerId, 'website_id=?' => $websiteId, 'segment_id IN(?)' => $segmentIds]
            );
        }
        return $this;
    }

    /**
     * Get segment ids assigned to customer id on specific website
     *
     * @param int $customerId
     * @param int $websiteId
     * @return array
     */
    public function getCustomerWebsiteSegments($customerId, $websiteId)
    {
        $select = $this->getConnection()->select()->from(
            ['c' => $this->getMainTable()],
            'segment_id'
        )->join(
            ['s' => $this->getTable('magento_customersegment_segment')],
            'c.segment_id = s.segment_id'
        )->where(
            'is_active = 1'
        )->where(
            'customer_id = :customer_id'
        )->where(
            'website_id = :website_id'
        );
        $bind = [':customer_id' => $customerId, ':website_id' => $websiteId];
        return $this->getConnection()->fetchCol($select, $bind);
    }
}
