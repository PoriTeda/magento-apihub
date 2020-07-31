<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Controller\SilentOrder;

use Magento\Checkout\Model\Session;
use Magento\Cybersource\Gateway\Command\SilentOrder\Token\ResponseProcessCommand;
use Magento\Cybersource\Gateway\Request\SilentOrder\MerchantSecureDataBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Block\Transparent\Iframe;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;

/**
 * Class TokenResponse
 * @package Magento\Cybersource\Controller\SilentOrder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TokenResponse extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    const TOKEN_COMMAND_NAME = 'TokenProcessCommand';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * @var LayoutFactory
     */
    private $layoutFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param Context $context
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param LayoutFactory $layoutFactory
     * @param Registry $registry
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        Context $context,
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        LayoutFactory $layoutFactory,
        Registry $registry,
        PaymentMethodManagementInterface $paymentMethodManagement,
        CartRepositoryInterface $cartRepository
    ) {
        parent::__construct($context);
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->layoutFactory = $layoutFactory;
        $this->registry = $registry;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @return \Magento\Framework\View\Result\Layout
     * @throws \Exception
     */
    public function execute()
    {
        $result = [];
        $arguments = [];

        /** @var Http $request */
        $request = $this->getRequest();

        try {
            $arguments['response'] = $request->getPostValue();
            if (!$this->getRequestField(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA1)) {
                throw new \Exception;
            }

            $activeCart = $this->cartRepository->get(
                (int)$this->getRequestField(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA1)
            );

            $payment = $this->paymentMethodManagement->get($activeCart->getId());

            /** @var ResponseProcessCommand $command */
            $command = $this->commandPool->get(self::TOKEN_COMMAND_NAME);
            $arguments['payment'] = $this->paymentDataObjectFactory->create($payment);

            $command->execute($arguments);
            if ((bool)$activeCart->getIsMultiShipping()) {
                $result['multishipping'] = true;
            }
            $result['success'] = true;
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $result['error'] = true;
            $result['error_msg'] = __('Your payment has been declined. Please try again.');
        }

        $this->registry->register(Iframe::REGISTRY_KEY, $result);

        $resultLayout = $this->layoutFactory->create();
        $resultLayout->addDefaultHandle();
        switch ($this->getRequestField(MerchantSecureDataBuilder::MERCHANT_SECURE_DATA3)) {
            case 'adminhtml':
                $resultLayout
                    ->getLayout()
                    ->getUpdate()
                    ->load(['cybersource_silentorder_tokenresponse_adminhtml']);
                break;
            default:
                $resultLayout
                    ->getLayout()
                    ->getUpdate()
                    ->load(['cybersource_silentorder_tokenresponse']);
                break;
        }

        return $resultLayout;
    }

    /**
     * Returns Cybersource-related request field
     *
     * @param string $field
     * @return mixed
     */
    private function getRequestField($field)
    {
        /** @var Http $request */
        $request = $this->getRequest();
        return $request->getPostValue($field)
            ?: $request->getPostValue('req_' . $field);
    }
}
