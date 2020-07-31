<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Vault\PaymentTokenService;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Class PaymentTokenBuilder
 */
class PaymentTokenBuilder implements BuilderInterface
{
    const PAYMENT_TOKEN = 'payment_token';

    /**
     * @var PaymentTokenService
     */
    private $paymentTokenService;

    /**
     * @param PaymentTokenService $paymentTokenService
     */
    public function __construct(PaymentTokenService $paymentTokenService)
    {
        $this->paymentTokenService = $paymentTokenService;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     * @throws \BadMethodCallException
     * @throws CommandException
     * @throws NotFoundException
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        $paymentToken = $this->paymentTokenService->getToken($paymentDO);
        if ($paymentToken === null) {
            throw new \BadMethodCallException('Vault Payment Token should be defined.');
        }

        return [
            self::PAYMENT_TOKEN => $paymentToken->getGatewayToken()
        ];
    }
}
