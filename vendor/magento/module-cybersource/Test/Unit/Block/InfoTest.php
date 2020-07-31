<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Block;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Payment\Gateway\ConfigInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Magento\Payment\Model\InfoInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentInfoModel;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->paymentInfoModel = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetSpecificationInformation()
    {
        $this->config->expects(static::once())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['paymentInfoKeys', null, $this->getPaymentInfoKeys()]
                ]
            );
        $this->paymentInfoModel->expects(static::atLeastOnce())
            ->method('getAdditionalInformation')
            ->willReturnMap(
                $this->getAdditionalFields()
            );

        $info = new \Magento\Cybersource\Block\Info(
            $this->context,
            $this->config,
            [
                'is_secure_mode' => 0,
                'info' => $this->paymentInfoModel
            ]
        );

        static::assertSame($this->getExpectedResult(), $info->getSpecificInformation());
    }

    public function testGetSpecificationInformationSecure()
    {
        $this->config->expects(static::exactly(2))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['paymentInfoKeys', null, $this->getPaymentInfoKeys()],
                    ['privateInfoKeys', null, $this->getPrivateInfoKeys()]
                ]
            );
        $this->paymentInfoModel->expects(static::atLeastOnce())
            ->method('getAdditionalInformation')
            ->willReturnMap(
                $this->getAdditionalFields()
            );

        $info = new \Magento\Cybersource\Block\Info(
            $this->context,
            $this->config,
            [
                'is_secure_mode' => 1,
                'info' => $this->paymentInfoModel
            ]
        );

        static::assertSame($this->getSecureExpectedResult(), $info->getSpecificInformation());
    }

    /**
     * @return array
     */
    private function getAdditionalFields()
    {
        return [
            ['auth_avs_code', 'X'],
            ['auth_cv_result', 'X'],
            ['card_number', '411111111111'],
            ['card_expiry_date', '02-1990'],
            ['decision', 'ACCEPT'],
            ['transaction_id', '1111111111'],
            ['risk_factors', 'Q^V'],
            ['some_other_data', 'not interested in']
        ];
    }

    /**
     * @return string
     */
    private function getPaymentInfoKeys()
    {
        return 'auth_avs_code,auth_cv_result,card_number,card_expiry_date,decision,transaction_id,risk_factors';
    }

    /**
     * @return string
     */
    private function getPrivateInfoKeys()
    {
        return 'transaction_id,reference_number,risk_factors';
    }

    /**
     * @return array
     */
    private function getExpectedResult()
    {
        return [
            (string)__('AVS result code') => 'X',
            (string)__('CVN result code') => 'X',
            (string)__('Card number') => '411111111111',
            (string)__('Card expiry date') => '02-1990',
            (string)__('Decision') => 'ACCEPT',
            (string)__('Transaction ID') => '1111111111',
            (string)__('Risk Factor Q') => 'Phone inconsistencies. The customer’s phone number is suspicious',
            (string)__('Risk Factor V') => 'Velocity. The account number was use  many times in the past 15 minutes'
        ];
    }

    /**
     * @return array
     */
    private function getSecureExpectedResult()
    {
        return [
            (string)__('AVS result code') => 'X',
            (string)__('CVN result code') => 'X',
            (string)__('Card number') => '411111111111',
            (string)__('Card expiry date') => '02-1990',
            (string)__('Decision') => 'ACCEPT'
        ];
    }
}
