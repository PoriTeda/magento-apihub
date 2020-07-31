<?php

namespace Riki\Subscription\Api\Data;

interface ProfileEmulatorInterface
{
    /**
     *
     *  Emulate cart data for customer from subscription profile
     *
     * @param ProfileInterface $profile
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return \Riki\Subscription\Model\Emulator\Cart $cart
     */
    public function emulateProfileCart(
        \Riki\Subscription\Api\Data\ProfileInterface $profile
    );
}