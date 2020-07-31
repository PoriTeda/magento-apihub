<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Controller\SilentOrder;

use Magento\Cybersource\Gateway\Command\SilentOrder\Token\CreateCommand;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManager;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\PaymentMethodManagementInterface;

/**
 * Class TokenRequest
 * @package Magento\Cybersource\Controller\SilentOrder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TokenRequest extends \Magento\Framework\App\Action\Action
{
    const TOKEN_COMMAND_NAME = 'TokenCreateCommand';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var SessionManager
     */
    private $checkoutSession;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @param Context $context
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param ConfigInterface $config
     * @param SessionManager $checkoutSession
     * @param JsonFactory $jsonFactory
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     */
    public function __construct(
        Context $context,
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        ConfigInterface $config,
        SessionManager $checkoutSession,
        JsonFactory $jsonFactory,
        PaymentMethodManagementInterface $paymentMethodManagement
    ) {
        parent::__construct($context);
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->paymentMethodManagement = $paymentMethodManagement;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $arguments = [
            'amount' => 0,
            'cc_type' => (string)$this->getRequest()->getParam('cc_type')
        ];

        $result = [];
        try {
            /** @var CreateCommand $command */
            $command = $this->commandPool->get(self::TOKEN_COMMAND_NAME);

            if (!$this->checkoutSession->getQuote()) {
                throw new \Exception;
            }

            $payment = $this->paymentMethodManagement->get(
                $this->checkoutSession->getQuote()->getId()
            );

            $arguments['payment'] = $this->paymentDataObjectFactory->create($payment);

            $commandResult = $command->execute($arguments);

            $result[$this->config->getValue('code')]['fields'] = $commandResult->get();
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['success'] = false;
            $result['error_messages'] = __('Payment Token Build Error.');
        }

        $jsonResult = $this->jsonFactory->create();
        $jsonResult->setData($result);

        return $jsonResult;
    }
}
