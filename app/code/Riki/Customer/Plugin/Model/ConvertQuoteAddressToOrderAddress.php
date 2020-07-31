<?php

namespace Riki\Customer\Plugin\Model;

class ConvertQuoteAddressToOrderAddress
{
    /**
     * Copy Riki's attributes from quote address to order address
     *
     * @param \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Api\Data\AddressInterface $quoteAddress
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderAddressInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Address\ToOrderAddress $subject,
        \Closure $proceed,
        \Magento\Quote\Api\Data\AddressInterface $quoteAddress,
        $data = []
    )
    {
        $orderAddress = $proceed($quoteAddress, $data);
        $attributes = ['firstnamekana', 'lastnamekana', 'riki_nickname','riki_type_address','apartment'];
        foreach ($attributes as $attribute) {
            $orderAddress->setData($attribute, $quoteAddress->getData($attribute));
        }
        return $orderAddress;
    }
}
