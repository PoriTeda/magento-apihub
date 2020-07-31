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


namespace Mirasvit\FraudCheck\Rule\Order;

use Mirasvit\FraudCheck\Rule\AbstractRule;
use Mirasvit\FraudCheck\Model\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class GrandTotal extends AbstractRule
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
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('Order amount');
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $total = $this->context->getGrandTotal();

        $gt = $this->orderCollectionFactory->create()
            ->addFieldToFilter('base_grand_total', ['gteq' => $total])
            ->getSize();

        $lt = $this->orderCollectionFactory->create()
            ->addFieldToFilter('base_grand_total', ['lteq' => $total])
            ->getSize();

        if ($gt > 0 && $lt > 0) {
            $var = $lt / ($lt + $gt);

            if (abs(0.5 - $var) * 100 < 30) {
                $this->addIndicator(1,
                    __('Order total within the normal range of orders for this store'));
            } else {
                $this->addIndicator(-1,
                    __('Order total outside the normal range of orders for this store'));
            }
        } else {
            $this->addIndicator(-2,
                __('Order total outside the normal range of orders for this store'));
        }
    }
}