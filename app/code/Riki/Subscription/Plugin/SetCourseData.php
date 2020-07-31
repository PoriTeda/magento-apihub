<?php

namespace Riki\Subscription\Plugin;

class SetCourseData
{
    /**
     * @var \Magento\Quote\Api\Data\CartExtensionFactory
     */
    protected $cartExtensionFactory;

    /**
     * SetCourseData constructor.
     *
     * @param \Magento\Quote\Api\Data\CartExtensionFactory $cartExtensionFactory
     */
    public function __construct(
        \Magento\Quote\Api\Data\CartExtensionFactory $cartExtensionFactory
    ) {
        $this->cartExtensionFactory = $cartExtensionFactory;
    }

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $subject
     * @param \Closure $proceed
     * @param int $cartId
     * @param array $sharedStoreIds
     *
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function aroundGet($subject, \Closure $proceed, $cartId, array $sharedStoreIds = [])
    {
        $cart = $proceed($cartId, $sharedStoreIds);

        $extensionAttributes = $cart->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->cartExtensionFactory->create();
        }

        $extensionAttributes->setRikiCourseId($cart->getData('riki_course_id'));
        $extensionAttributes->setNthDelivery($cart->getData('n_delivery'));

        $cart->setExtensionAttributes($extensionAttributes);

        return $cart;
    }
}
