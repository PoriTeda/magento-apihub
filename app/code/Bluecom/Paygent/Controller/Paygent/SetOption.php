<?php

namespace Bluecom\Paygent\Controller\Paygent;

class SetOption extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;
    /**
     * @var \Bluecom\Paygent\Model\PaygentOption
     */
    protected $paygentOption;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * SetOption constructor.
     *
     * @param \Magento\Framework\App\Action\Context            $context         Context
     * @param \Magento\Customer\Model\Session                  $customerSession Session
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson      JsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Bluecom\Paygent\Model\PaygentOption $paygentOption,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJson = $resultJson;
        $this->paygentOption = $paygentOption;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @return mixed
     */
    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();
        $postOption = $this->getRequest()->getParam('option');

        $currentOption = $this->paygentOption->loadByAttribute('customer_id', $customerId);

        if(!$currentOption->getId()) {
            //Save paygent option
            $data = [
                'customer_id' => $customerId,
                'option_checkout' => $postOption
            ];
            try {
                $this->paygentOption->setData($data)->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        } else {

            if($currentOption->getOptionCheckout() != $postOption) {
                $currentOption->setOptionCheckout($postOption);
                try {
                    $currentOption->save();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
        
        $json = true;
        $resultJson = $this->resultJson->create();
        return $resultJson->setData($json);
    }

}