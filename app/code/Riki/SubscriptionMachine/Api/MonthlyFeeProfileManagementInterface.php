<?php

namespace Riki\SubscriptionMachine\Api;

interface MonthlyFeeProfileManagementInterface
{
    /**
     * Create Monthly Fee Profile
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileCreationInterface $monthlyFeeProfile
     * @return \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileResultInterface
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function create($monthlyFeeProfile);

    /**
     * Update Monthly Fee Profile
     * @param \Riki\SubscriptionMachine\Api\Data\MonthlyFeeProfileUpdateInterface $monthlyFeeProfile
     * @return boolean
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function update($monthlyFeeProfile);

    /**
     * Disengage Monthly Fee Profile
     * @param \Riki\SubscriptionMachine\Api\Data\DisengagementProfileInterface $disengagementProfile
     * @return boolean
     * @throws \Riki\SubscriptionMachine\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function disengage($disengagementProfile);
}
