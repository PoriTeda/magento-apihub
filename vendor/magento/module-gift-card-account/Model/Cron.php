<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model;

class Cron
{
    /**
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $_giftCAFactory = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_coreDate = null;

    /**
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     */
    public function __construct(
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
    ) {
        $this->_giftCAFactory = $giftCAFactory;
        $this->_coreDate = $coreDate;
    }

    /**
     * Update Gift Card Account states by cron
     *
     * @return $this
     */
    public function updateStates()
    {
        // update to expired
        $model = $this->_giftCAFactory->create();

        $now = $this->_coreDate->date('Y-m-d');

        $collection = $model->getCollection()->addFieldToFilter(
            'state',
            \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_AVAILABLE
        )->addFieldToFilter(
            'date_expires',
            ['notnull' => true]
        )->addFieldToFilter(
            'date_expires',
            ['lt' => $now]
        );

        $ids = $collection->getAllIds();
        if ($ids) {
            $state = \Magento\GiftCardAccount\Model\Giftcardaccount::STATE_EXPIRED;
            $model->updateState($ids, $state);
        }
        return $this;
    }
}
