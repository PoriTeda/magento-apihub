<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Controller\Sku;

class UploadFileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedCheckout\Test\Unit\Controller\Sku\UploadFile
     */
    protected $controller;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $args = [
            'request' => $this->requestMock,
            'objectManager' => $this->objectManagerMock
        ];

        $this->controller = $helper->getObject(\Magento\AdvancedCheckout\Controller\Sku\UploadFile::class, $args);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $isSkuFileUploaded
     * @param int $processSkuFileCall
     * @param array $postItems
     * @param array $expectedResult
     */
    public function testExecute($isSkuFileUploaded, $processSkuFileCall, $postItems, $expectedResult)
    {
        $helperMock = $this->createMock(\Magento\AdvancedCheckout\Helper\Data::class);

        $this->objectManagerMock->expects($this->once())->method('get')
            ->with(\Magento\AdvancedCheckout\Helper\Data::class)->will($this->returnValue($helperMock));

        $helperMock->expects($this->any())->method('isSkuFileUploaded')
            ->with($this->requestMock)->will($this->returnValue($isSkuFileUploaded));
        $helperMock->expects($this->exactly($processSkuFileCall))->method('processSkuFileUploading')
            ->will($this->returnValue(['fileSku']));

        $this->requestMock->expects($this->any())->method('getPost')->with('items')
            ->will($this->returnValue($postItems));
        $this->requestMock->expects($this->once())->method('setParam')->with('items', $expectedResult);

        $this->controller->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'isSkuFileUploaded' => true,
                'processSkuFileCall' => 1,
                'postItems' => ['postSku'],
                'expectedResult' => ['postSku', 'fileSku']
            ],
            [
                'isSkuFileUploaded' => false,
                'processSkuFileCall' => 0,
                'postItems' => ['postSku'],
                'expectedResult' => ['postSku']
            ],
            [
                'isSkuFileUploaded' => false,
                'processSkuFileCall' => 0,
                'postItems' => [],
                'expectedResult' => []
            ],
            [
                'isSkuFileUploaded' => true,
                'processSkuFileCall' => 1,
                'postItems' => [],
                'expectedResult' => ['fileSku']
            ],
        ];
    }
}
