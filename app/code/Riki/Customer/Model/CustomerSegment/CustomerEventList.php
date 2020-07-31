<?php

namespace Riki\Customer\Model\CustomerSegment;

class CustomerEventList implements \Riki\Customer\Api\CustomerSegment\CustomerEventListInterface
{
    /**
     * @var \Riki\Customer\Model\CustomerSegment\CustomerEvent[]
     */
    private $items;

    /**
     * @param \Riki\Customer\Model\CustomerSegment\CustomerEvent[] $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return \Riki\Customer\Model\CustomerSegment\CustomerEvent[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
