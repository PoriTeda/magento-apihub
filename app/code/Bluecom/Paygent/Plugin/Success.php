<?php

namespace Bluecom\Paygent\Plugin;

class Success
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var \Bluecom\Paygent\Model\PaygentOption
     */
    protected $paygentOption;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory RedirectFactory
     * @param \Magento\Customer\Model\Session $customerSession Session
     * @param \Bluecom\Paygent\Model\PaygentOption $paygentOption PaygentOption
     * @param \Psr\Log\LoggerInterface $logger Logger
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Bluecom\Paygent\Model\PaygentOption $paygentOption,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->customerSession = $customerSession;
        $this->paygentOption = $paygentOption;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->logger = $logger;
    }

    /**
     * Redirect to Paygent
     *
     * @param \Magento\Checkout\Controller\Onepage\Success $subject Subject
     * @param \Closure $proceed Closure
     *
     * @return $this
     */
    public function aroundExecute(\Magento\Checkout\Controller\Onepage\Success $subject, \Closure $proceed)
    {
        $customerId = $this->customerSession->getCustomerId();
        $currentOption = $this->paygentOption->loadByAttribute('customer_id', $customerId);

        //checkout success with paygent and redirect to paygent page
        $redirecUrl = $currentOption->getLinkRedirect();

        if ($redirecUrl) {
            $currentOption->setOptionCheckout(0);
            $currentOption->setLinkRedirect('');
            try {
                $currentOption->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
            return $this->resultRedirectFactory->create()->setUrl($redirecUrl);
        }

        // call the original execute function
        $returnValue = $proceed();

        return $returnValue;
    }

}
