<?php

namespace Riki\SubscriptionMachine\Model\Data;

use Magento\Framework\DataObject;
use Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileResultInterface;

class MonthlyFeeProfileResult extends DataObject implements MonthlyFeeProfileResultInterface
{
    /**
     * {@inheritdoc}
     */
    public function setReferenceProfileId($referenceProfileId)
    {
        return $this->setData(self::REFERENCE_PROFILE_ID, $referenceProfileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceProfileId()
    {
        return $this->getData(self::REFERENCE_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedProfileId($profileId)
    {
        return $this->setData(self::CREATED_PROFILE_ID, $profileId);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedProfileId()
    {
        return $this->getData(self::CREATED_PROFILE_ID);
    }
}
