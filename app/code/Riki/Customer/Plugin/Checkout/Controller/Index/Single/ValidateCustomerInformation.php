<?php

namespace Riki\Customer\Plugin\Checkout\Controller\Index\Single;

class ValidateCustomerInformation extends \Riki\Customer\Plugin\Checkout\Controller\Index\ValidateCustomerInformation
{
    /**
     * @param \Riki\Checkout\Controller\Index\Single $subject
     * @param \Closure $proceed
     * @return $this|mixed
     */
    public function aroundExecute(
        \Riki\Checkout\Controller\Index\Single $subject,
        \Closure $proceed
    ) {
        if ($redirectUrl = $this->getRedirectUrl()) {
            return $this->redirectFactory->create()
                ->setUrl($redirectUrl . $this->url->getUrl('checkout'));
        }

        return $proceed();
    }
}
