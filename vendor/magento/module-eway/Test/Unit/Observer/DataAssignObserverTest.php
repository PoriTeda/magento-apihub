<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Payment\Model\Info;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Eway\Observer\DataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{
    const CC_NUMBER = '1111';
    const CC_CID = '123';
    const CC_TYPE = 'VI';
    const CC_EXP_MONTH = '01';
    const CC_EXP_YEAR = '20';

    public function testExecute()
    {
        $observerContainer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentInfoModel = $this->getMockBuilder(Info::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $dataObject = new DataObject(
            [
                PaymentInterface::KEY_ADDITIONAL_DATA =>
                    [
                        'cc_number' => self::CC_NUMBER,
                        'cc_cid' => self::CC_CID,
                        'cc_type' => self::CC_TYPE,
                        'cc_exp_month' => self::CC_EXP_MONTH,
                        'cc_exp_year' => self::CC_EXP_YEAR,
                    ]
            ]
        );
        $observerContainer->expects(static::atLeastOnce())
            ->method('getEvent')
            ->willReturn($event);
        $event->expects(static::exactly(2))
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [AbstractDataAssignObserver::MODEL_CODE, $paymentInfoModel],
                    [AbstractDataAssignObserver::DATA_CODE, $dataObject]
                ]
            );
        $paymentInfoModel->expects(static::exactly(5))
            ->method('setAdditionalInformation')
            ->willReturnMap(
                [
                    ['cc_number', self::CC_NUMBER],
                    ['cc_cid', self::CC_CID],
                    ['cc_type', self::CC_TYPE],
                    ['cc_exp_month', self::CC_EXP_MONTH],
                    ['cc_exp_year', self::CC_EXP_YEAR]
                ]
            );

        $observer = new DataAssignObserver();
        $observer->execute($observerContainer);
    }
}
