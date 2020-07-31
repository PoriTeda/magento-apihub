<?php

namespace Riki\Customer\Plugin\Controller;

class FormPostPlugin
{
    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    protected $_customerSession;

    /**
     * FormPostPlugin constructor.
     *
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session\Proxy $customerSession
    ) {
        $this->_customerSession = $customerSession;
    }

    /**
     * Clear form data in session
     *
     * @param $subject
     * @param $result
     *
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        $this->_customerSession->setAddressFormData(null);

        return $result;
    }
}
