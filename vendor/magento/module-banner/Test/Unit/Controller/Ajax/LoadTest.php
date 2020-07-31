<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Controller\Ajax;

use Magento\Backend\App\Action\Context;
use Magento\Banner\Controller\Ajax\Load;
use Magento\Banner\Model\Banner\DataFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Load
     */
    protected $object;

    /**
     * @var Context
     */
    protected $contextMock;

    /**
     * @var Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var RawFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rawFactoryMock;

    /**
     * @var DataFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataFactoryMock;

    /**
     * @var Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonMock;

    /**
     * @var Raw|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rawMock;

    /**
     * @var \Magento\Banner\Model\Banner\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawFactoryMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\RawFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock = $this->getMockBuilder(\Magento\Banner\Model\Banner\DataFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataMock = $this->getMockBuilder(\Magento\Banner\Model\Banner\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSectionData'])
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Json::class)
            ->setMethods(['setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rawMock = $this->getMockBuilder(\Magento\Framework\Controller\Result\Raw::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getObjectManager', 'getRequest'])
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->dataMock);
        $this->rawFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->rawMock);
        $this->jsonFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->jsonMock);

        $this->object = $this->objectManager->getObject(
            \Magento\Banner\Controller\Ajax\Load::class,
            [
                'context' => $this->contextMock,
                'jsonFactory' => $this->jsonFactoryMock,
                'rawFactory' => $this->rawFactoryMock,
                'dataFactory' => $this->dataFactoryMock,
            ]
        );
    }

    /**
     * @param array $sectionData
     * @param array $expectedResult
     * @param string $expectedJson
     * @dataProvider getDataProvider
     */
    public function testExecute(array $sectionData, array $expectedResult, $expectedJson)
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('GET');
        $this->requestMock
            ->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(true);
        $this->dataMock
            ->expects($this->once())
            ->method('getSectionData')
            ->willReturn($sectionData);
        $this->jsonMock
            ->expects($this->once())
            ->method('setData')
            ->with($expectedResult)
            ->willReturn($expectedJson);

        $this->assertSame($expectedJson, $this->object->execute());
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            [
                $sectionData = [],
                ['data' => $sectionData],
                '{"data":[]}'
            ],
            [
                $sectionData = [
                    'items' => [
                        'salesrule' => [],
                        'catalogrule' => [],
                        'fixed' => [
                            1 => [
                                'content' => 'Test',
                                'types' => [],
                                'id' => 1,
                            ],
                        ],
                    ],
                ],
                ['data' => $sectionData],
                '{"data":{"items":{"salesrule":[],"catalogrule":[],'
                . '"fixed":{"1":{"content":"Test","types":[],"id":1}}}}}'
            ],
        ];
    }

    /**
     * @return void
     */
    public function testNonAjaxRequest()
    {
        $this->requestMock
            ->expects($this->any())
            ->method('getMethod')
            ->willReturn('GET');
        $this->requestMock
            ->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $this->rawMock->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(400)
            ->willReturnSelf();
        $this->dataMock
            ->expects($this->any())
            ->method('getSectionData')
            ->willReturn([]);
        $this->jsonMock
            ->expects($this->any())
            ->method('setData')
            ->with(['data' => []])
            ->willReturn('');

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Raw::class, $this->object->execute());
    }
}
