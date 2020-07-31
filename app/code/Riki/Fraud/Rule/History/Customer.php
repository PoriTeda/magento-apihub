<?php

namespace Riki\Fraud\Rule\History;

class Customer extends \Mirasvit\FraudCheck\Rule\History\Customer
{
    /**
     * {@inheritdoc}
     */
    public function collect()
    {
        $collection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_email', $this->context->getEmail())
            ->addFieldToFilter('entity_id', ['neq' => $this->context->getOrderId()])
            ->setPageSize(20);

        $this->collectForCollection($collection, 'Customer');
    }
}
