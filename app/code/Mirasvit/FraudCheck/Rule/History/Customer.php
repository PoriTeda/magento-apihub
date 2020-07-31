<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Rule\History;

use Mirasvit\FraudCheck\Rule\AbstractRule;
use Mirasvit\FraudCheck\Model\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Order;

class Customer extends AbstractRule
{
    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        Context $context,
        OrderCollectionFactory $orderCollectionFactory
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;

        parent::__construct($context);
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 5;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Customer History');
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_email', $this->context->getEmail())
            ->addFieldToFilter('entity_id', ['neq' => $this->context->getOrderId()]);

        $this->collectForCollection($collection, 'Customer');
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @param string                                              $prefix
     * @return void
     */
    public function collectForCollection($collection, $prefix)
    {
        if ($collection->count() == 0) {
            $this->addIndicator(0,
                __("$prefix has not placed orders before"));
        } else {
            $complete = $refunded = $canceled = 0;
            /** @var Order $order */
            foreach ($collection as $order) {
                if ($order->getTotalCanceled() > 0) {
                    $canceled++;
                } elseif ($order->getTotalRefunded() > 0) {
                    $refunded++;
                } else {
                    $complete++;
                }
            }

            if ($complete) {
                $this->addIndicator(1,
                    __("$prefix placed %1 completed order(s)", $complete));
            }

            if ($refunded) {
                $this->addIndicator(-1,
                    __("$prefix placed %1 refunded order(s)", $refunded));
            }

            if ($canceled) {
                $this->addIndicator(-1,
                    __("$prefix placed %1 canceled order(s)", $canceled));
            }
        }
    }
}