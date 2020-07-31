<?php
/**
 * Test \Magento\Logging\Model\Config
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Model\Handler;

class ControllersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Logging\Model\Handler\Controllers
     */
    protected $object;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Logging\Model\Event\ChangesFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventChangesFactory;

    /**
     * @var \Magento\Logging\Model\Event\Changes|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventChanges;

    /**
     * @var \Magento\Config\Model\Config\Structure|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configStructure;

    /**
     * @var \Magento\Logging\Model\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->request->expects($this->any())->method('getParams')->will($this->returnValue([]));

        $this->eventChanges = new \Magento\Framework\DataObject();
        $this->eventChangesFactory = $this->createPartialMock(
            \Magento\Logging\Model\Event\ChangesFactory::class,
            ['create']
        );
        $this->eventChangesFactory->expects(
            $this->any()
        )->method(
            'create'
        )->will(
            $this->returnValue($this->eventChanges)
        );

        $this->configStructure = $this->createPartialMock(
            \Magento\Config\Model\Config\Structure::class,
            ['getFieldPathsByAttribute']
        );
        $this->configStructure->expects(
            $this->any()
        )->method(
            'getFieldPathsByAttribute'
        )->will(
            $this->returnValue([])
        );

        $this->object = $objectManager->getObject(
            \Magento\Logging\Model\Handler\Controllers::class,
            [
                'request' => $this->request,
                'eventChangesFactory' => $this->eventChangesFactory,
                'structureConfig' => $this->configStructure
            ]
        );

        $this->processor = $this->createMock(\Magento\Logging\Model\Processor::class);
    }

    /**
     * @dataProvider postDispatchReportDataProvider
     */
    public function testPostDispatchReport($config, $expectedInfo)
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $eventModel = $helper->getObject(\Magento\Logging\Model\Event::class);
        $processor = $this->getMockBuilder(
            \Magento\Logging\Model\Processor::class
        )->disableOriginalConstructor()->getMock();

        $result = $this->object->postDispatchReport($config, $eventModel, $processor);
        if (is_object($result)) {
            $result = $result->getInfo();
        }
        $this->assertEquals($expectedInfo, $result);
    }

    /**
     * @return array
     */
    public function postDispatchReportDataProvider()
    {
        return [
            [['controller_action' => 'reports_report_shopcart_product'], 'shopcart_product'],
            [['controller_action' => 'some_another_value'], false]
        ];
    }

    /**
     * Assure that method works when post data contains group without ['fields'] key
     */
    public function testPostDispatchConfigSaveGroupWithoutFieldsKey()
    {
        $this->request->expects(
            $this->once()
        )->method(
            'getPostValue'
        )->will(
            $this->returnValue(['groups' => ['name' => []]])
        );

        $this->assertEquals(
            ['info' => 'general'],
            $this->object->postDispatchConfigSave([], new \Magento\Framework\DataObject(), $this->processor)->getData()
        );
    }
}
