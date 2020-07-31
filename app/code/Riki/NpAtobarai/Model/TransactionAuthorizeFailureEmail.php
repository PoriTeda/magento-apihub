<?php

namespace Riki\NpAtobarai\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\AreaInterface;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Mail\Template\TransportBuilder;
use Psr\Log\LoggerInterface;

class TransactionAuthorizeFailureEmail
{
    const XML_PATH_NP_ATOBARAI_AUTHORIZATION_FAILURE_EMAIL_ENABLE = 'npatobarai/authorize_failure_email/enable_send';
    const XML_PATH_NP_ATOBARAI_AUTHORIZATION_FAILURE_EMAIL_TEMPLATE = 'npatobarai/authorize_failure_email/template';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var AreaList
     */
    protected $areaList;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var \Riki\EmailMarketing\Helper\Order
     */
    protected $helperEmail;

    /**
     * TransactionAuthorizeFailureEmail constructor.
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param TransportBuilder $transportBuilder
     * @param AreaList $areaList
     * @param State $state
     * @param \Riki\EmailMarketing\Helper\Order $helperEmail
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        AreaList $areaList,
        State $state,
        \Riki\EmailMarketing\Helper\Order $helperEmail
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->areaList = $areaList;
        $this->state = $state;
        $this->helperEmail = $helperEmail;
    }

    /**
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendEmailAuthorizationFailure($order)
    {
        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(AreaInterface::PART_TRANSLATE);

        $customerEmail = $order->getCustomerEmail();
        $storeId = $order->getStoreId();

        $enableSendEmail = $this->scopeConfig->getValue(
            self::XML_PATH_NP_ATOBARAI_AUTHORIZATION_FAILURE_EMAIL_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$enableSendEmail) {
            return false;
        }
        $emailTemplateVars = $this->helperEmail->getOrderVariables($order);

        $recipientsEmail[] = $customerEmail;

        $emailTemplate = $this->scopeConfig->getValue(
            self::XML_PATH_NP_ATOBARAI_AUTHORIZATION_FAILURE_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        try {
            $transport = $this->transportBuilder->setTemplateIdentifier(
                $emailTemplate
            )->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ]
            )->setTemplateVars(
                $emailTemplateVars
            )->setFrom(
                $this->getAuthorizeFailureEmailIdentity()
            )->addTo(
                array_map(function ($recipientEmail) {
                    return trim($recipientEmail);
                }, $recipientsEmail)
            )->getTransport();

            $transport->sendMessage();
            $this->logger->info(
                'Order Id : #' . $order->getIncrementId() . ' Send authorize failure mail successfully'
            );
        } catch (\Exception $e) {
            $this->logger->critical(
                'Order Id : #' . $order->getIncrementId() .
                'Send authorize failure mail unsuccessfully. Error message: ' . $e->getMessage()
            );
        }
    }

    /**
     * @return array
     */
    protected function getAuthorizeFailureEmailIdentity()
    {
        return [
            'email' => $this->scopeConfig->getValue('trans_email/ident_support/email'),
            'name' => $this->scopeConfig->getValue('trans_email/ident_support/name')
        ];
    }
}
