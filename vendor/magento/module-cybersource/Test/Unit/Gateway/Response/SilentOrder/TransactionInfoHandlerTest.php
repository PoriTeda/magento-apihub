<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Response\SilentOrder;

use Magento\Cybersource\Gateway\Response\SilentOrder\TransactionInfoHandler;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Model\InfoInterface;

class TransactionInfoHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | ConfigInterface
     */
    private $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | PaymentDataObjectInterface
     */
    private $paymentDO;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | InfoInterface
     */
    private $paymentInfo;

    /**
     * @var TransactionInfoHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $this->paymentInfo = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();

        $this->handler = new TransactionInfoHandler($this->configMock);
    }

    public function testHandle()
    {
        $this->paymentDO->expects(static::atLeastOnce())
            ->method('getPayment')
            ->willReturn($this->paymentInfo);
        $this->configMock->expects(static::once())
            ->method('getValue')
            ->with('paymentInfoKeys', null)
            ->willReturn($this->getPaymentInfoKeys());
        $this->paymentInfo->expects(static::exactly(2))
            ->method('setAdditionalInformation')
            ->willReturnMap($this->getExpectedMap());

        $this->handler->handle(['payment' => $this->paymentDO], $this->getResponse());
    }

    /**
     * @return string
     */
    private function getPaymentInfoKeys()
    {
        return 'info_key,info_key_2,info_key_3';
    }

    /**
     * @return array
     */
    private function getResponse()
    {
        return [
            TransactionInfoHandler::REQUEST_SUFFIX . 'info_key' => 'info_data',
            'other_key' => 'other_data',
            'info_key_3' => 'info_data'
        ];
    }

    /**
     * @return array
     */
    private function getExpectedMap()
    {
        return [
            ['info_key', 'info_data', null],
            ['info_key_3', 'info_data', null]
        ];
    }
}
