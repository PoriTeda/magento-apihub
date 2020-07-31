<?php

namespace Riki\Customer\Api\GridIndexer;

interface ItemInterface
{
    /**
     * @param int $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * @return int
     */
    public function getCustomerId();
}
