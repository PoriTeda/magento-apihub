<?php

namespace Riki\SubscriptionMachine\Helper\MonthlyFee;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Riki\Subscription\Helper\Order\Data;

class PaygentHelper extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslate;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->inlineTranslate = $inlineTranslate;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }



    public function sendPaygenUrlEmailToCustomer($customerEmail, $paygentUrl, $storeId)
    {
        try {
            $this->inlineTranslate->suspend();

            $senderInfo = $this->getSenderEmail();

            $emailTemplate = $this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_PAYGENT_AUTHORIZE_FAIL_EMAIL_TEMPLATE);

            $this->transportBuilder->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ]
            )->setTemplateIdentifier($emailTemplate)
                ->setTemplateVars(['paygent_url' => $paygentUrl])
                ->setFrom($senderInfo)
                ->addTo($customerEmail);


            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslate->resume();
        } catch (\Magento\Framework\Exception\MailException $mailException) {
            //In case we encounter issue has work around in NED-6855, don't keep throw exception.
        } catch (\Exception $exception) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info('Send paygent reauthorize link to customer error: ' . $customerEmail);
            $logger->info($exception->getMessage());
        }
    }

    public function getSenderEmail()
    {
        return $this->scopeConfig->getValue(Data::CONFIG_FREE_MACHINE_PAYGENT_AUTHORIZE_FAIL_SENDER);
    }
}