<?php

namespace Riki\Customer\Api\CustomerSegment;

interface CustomerEventListInterface
{
    /**
     * @param \Riki\Customer\Api\CustomerSegment\CustomerEventInterface[] $items
     *
     * @return $this
     */
    public function setItems($items);

    /**
     * @return \Riki\Customer\Api\CustomerSegment\CustomerEventInterface[]
     */
    public function getItems();
}
