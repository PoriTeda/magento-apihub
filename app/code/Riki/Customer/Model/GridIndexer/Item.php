<?php

namespace Riki\Customer\Model\GridIndexer;

use Riki\Customer\Api\GridIndexer\ItemInterface;

class Item implements ItemInterface
{
    /**
     * @var int
     */
    private $customerId;

    /**
     * {@inheritdoc}
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }
}
