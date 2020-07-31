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

class Ip extends Customer
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return __('IP History');
    }

    /**
     * @return int
     */
    public function getDefaultImportance()
    {
        return 6;
    }

    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('remote_ip', $this->context->getIp())
            ->addFieldToFilter('entity_id', ['neq' => $this->context->getOrderId()])
            ->setOrder('created_at', 'asc')
            ->setPageSize(20);

        $this->collectForCollection($collection, 'IP');
    }
}