<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model;

class ShippingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Model\Shipping
     */
    protected $model;

    /**
     * @var \Magento\Shipping\Model\CarrierFactory
     */
    protected $carrierFactory;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $orderFactory = $this->createPartialMock(\Magento\Sales\Model\OrderFactory::class, ['create']);
        $regionFactory = $this->createPartialMock(\Magento\Directory\Model\RegionFactory::class, ['create']);
        $this->carrierFactory = $this->createMock(\Magento\Shipping\Model\CarrierFactory::class);
        $returnFactory = $this->createMock(\Magento\Shipping\Model\Shipment\ReturnShipmentFactory::class, ['create']);
        $rmaFactory = $this->createPartialMock(\Magento\Rma\Model\RmaFactory::class, ['create']);
        $filesystem = $this->createMock(\Magento\Framework\Filesystem::class);

        $this->model = $objectManagerHelper->getObject(
            \Magento\Rma\Model\Shipping::class,
            [
                'orderFactory' => $orderFactory,
                'regionFactory' => $regionFactory,
                'returnFactory' => $returnFactory,
                'carrierFactory' => $this->carrierFactory,
                'rmaFactory' => $rmaFactory,
                'filesystem' => $filesystem
            ]
        );
    }

    /**
     * @dataProvider isCustomDataProvider
     * @param bool $expectedResult
     * @param string $carrierCodeToSet
     */
    public function testIsCustom($expectedResult, $carrierCodeToSet)
    {
        $this->model->setCarrierCode($carrierCodeToSet);
        $this->assertEquals($expectedResult, $this->model->isCustom());
    }

    /**
     * @return array
     */
    public static function isCustomDataProvider()
    {
        return [
            [true, \Magento\Sales\Model\Order\Shipment\Track::CUSTOM_CARRIER_CODE],
            [false, 'not-custom']
        ];
    }

    public function testGetNumberDetailWithoutCarrierInstance()
    {
        $carrierTitle = 'Carrier Title';
        $trackNumber = 'US1111CA';
        $expected = [
            'title' => $carrierTitle,
            'number' => $trackNumber,
        ];
        $this->model->setCarierTitle($carrierTitle);
        $this->model->setTrackNumber($trackNumber);

        $this->assertEquals($expected, $this->model->getNumberDetail());
    }

    /**
     * @dataProvider getNumberDetailDataProvider
     */
    public function testGetNumberDetail($trackingInfo, $trackNumber, $expected)
    {
        $carrierMock = $this->createPartialMock(
            \Magento\OfflineShipping\Model\Carrier\Flatrate::class,
            ['getTrackingInfo', 'setStore']
        );
        $this->carrierFactory->expects($this->once())
            ->method('create')
            ->will($this->returnValue($carrierMock));
        $carrierMock->expects($this->any())
            ->method('getTrackingInfo')
            ->will($this->returnValue($trackingInfo));

        $this->model->setTrackNumber($trackNumber);
        $this->assertEquals($expected, $this->model->getNumberDetail());
    }

    public function getNumberDetailDataProvider()
    {
        $trackNumber = 'US1111CA';
        return [
            'With tracking info' => ['some tracking info', $trackNumber, 'some tracking info'],
            'Without tracking info' => [false, $trackNumber, __('No detail for number "' . $trackNumber . '"')]
        ];
    }
}
