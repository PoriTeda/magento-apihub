<?php

namespace Riki\Customer\Plugin\Checkout\Controller\Index\Multiple;

class ValidateCustomerInformation extends \Riki\Customer\Plugin\Checkout\Controller\Index\ValidateCustomerInformation
{
    /**
     * @param \Riki\Checkout\Controller\Index\Index $subject
     * @param \Closure $proceed
     * @return $this|mixed
     */
    public function aroundExecute(
        \Riki\Checkout\Controller\Index\Index $subject,
        \Closure $proceed
    ) {
        if ($redirectUrl = $this->getRedirectUrl()) {
            return $this->redirectFactory->create()
                ->setUrl($redirectUrl . $this->url->getUrl('multicheckout'));
        }

        return $proceed();
    }
}
