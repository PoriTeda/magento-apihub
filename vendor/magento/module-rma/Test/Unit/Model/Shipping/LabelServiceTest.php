<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\Shipping;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class LabelServiceTest
 */
class LabelServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var \Magento\Rma\Model\Shipping\LabelService
     */
    private $labelServiceModel;

    /**
     * @var \Magento\Rma\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaHelper;

    /**
     * @var \Magento\Rma\Model\ShippingFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\ShippingFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingResourceFactory;

    /**
     * @var \Magento\Framework\Filesystem|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $json;

    protected function setUp()
    {
        $this->rmaHelper = $this->getMockBuilder(\Magento\Rma\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingFactory = $this->getMockBuilder(\Magento\Rma\Model\ShippingFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingResourceFactory = $this->getMockBuilder(\Magento\Rma\Model\ResourceModel\ShippingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->json = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->labelServiceModel = $this->objectManagerHelper->getObject(
            \Magento\Rma\Model\Shipping\LabelService::class,
            [
                'rmaHelper' => $this->rmaHelper,
                'shippingFactory' => $this->shippingFactory,
                'shippingResourceFactory' => $this->shippingResourceFactory,
                'filesystem' => $this->filesystem,
                'json' => $this->json
            ]
        );
    }

    public function testCreateShippingLabel()
    {
        $packages = [
            [
                "params" => [
                    "weight" => 10,
                    "height" => 10,
                    "width" => 10
                ]
            ],
            [
                "params" => [
                    "weight" => 20,
                    "height" => 20,
                    "width" => 20
                ]
            ]
        ];

        $data = [
            "carrier_title" => "SuperCarrier",
            "method_title" => "SuperMethod",
            "price" => 100,
            "code" => "EE_RR_OO",
            "packages" => $packages
        ];

        $rmaModel = $this->getMockBuilder(\Magento\Rma\Model\Rma::class)
            ->disableOriginalConstructor()
            ->getMock();

        $abstractCarrier = $this->getMockBuilder(\Magento\Shipping\Model\Carrier\AbstractCarrierOnline::class)
            ->disableOriginalConstructor()
            ->getMock();
        $abstractCarrier->expects($this->any())
            ->method('isShippingLabelsAvailable')
            ->willReturn(true);
        $this->rmaHelper->expects($this->any())
            ->method('getCarrier')
            ->willReturn($abstractCarrier);

        $shipping = $this->getMockBuilder(\Magento\Rma\Model\Shipping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipping->expects($this->any())
            ->method('getShippingLabelByRma')
            ->willReturnSelf();
        $this->shippingFactory->expects($this->any())
            ->method('create')
            ->willReturn($shipping);
        $response = new \Magento\Framework\DataObject(['info'=> ['data']]);
        $shipping->expects($this->any())
            ->method('requestToShipment')
            ->willReturn($response);

        $this->json->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->assertTrue($this->labelServiceModel->createShippingLabel($rmaModel, $data));
    }
}
