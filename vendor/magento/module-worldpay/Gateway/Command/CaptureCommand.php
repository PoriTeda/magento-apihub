<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Gateway\Command;

use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class CaptureCommand extends GatewayCommand
{
    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null
     * @throws \Exception
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $payment = $paymentDO->getPayment();
        if (!$payment instanceof Payment) {
            return null;
        }

        if (!$payment->getAuthorizationTransaction()) {
            return null;
        }

        return parent::execute($commandSubject);
    }
}
