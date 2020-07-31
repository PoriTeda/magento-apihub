<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Test\Unit\Model\Order\Archive\Grid\Row;

class UrlGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var $_model \Magento\SalesArchive\Model\Order\Archive\Grid\Row\UrlGenerator
     */
    protected $_model;

    /**
     * @var $_authorization \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_authorizationMock;

    /**
     * @var $_urlModel \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_urlModelMock;

    protected function setUp()
    {
        $this->_authorizationMock = $this->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)->getMock();

        $this->_urlModelMock = $this->createMock(\Magento\Backend\Model\Url::class);

        $urlMap = [
            [
                'sales/order/view',
                ['order_id' => null],
                'http://localhost/backend/sales/order/view/order_id/',
            ],
            ['sales/order/view', ['order_id' => 1], 'http://localhost/backend/sales/order/view/order_id/1'],
        ];
        $this->_urlModelMock->expects($this->any())->method('getUrl')->will($this->returnValueMap($urlMap));

        $this->_model = new \Magento\SalesArchive\Model\Order\Archive\Grid\Row\UrlGenerator(
            $this->_urlModelMock,
            $this->_authorizationMock,
            ['path' => 'sales/order/view', 'extraParamsTemplate' => ['order_id' => 'getId']]
        );
    }

    public function testAuthNotAllowed()
    {
        $this->_authorizationMock->expects($this->once())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::orders')
            ->will($this->returnValue(false));

        $this->assertFalse($this->_model->getUrl(new \Magento\Framework\DataObject()));
    }

    /**
     * @param $item
     * @param $expectedUrl
     * @dataProvider itemsDataProvider
     */
    public function testAuthAllowed($item, $expectedUrl)
    {
        $this->_authorizationMock->expects($this->any())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::orders')
            ->will($this->returnValue(true));
        $result = $this->_model->getUrl($item);

        $this->assertEquals($expectedUrl, $result);
    }

    public function itemsDataProvider()
    {
        return [
            [new \Magento\Framework\DataObject(), 'http://localhost/backend/sales/order/view/order_id/'],
            [
                new \Magento\Framework\DataObject(['id' => 1]),
                'http://localhost/backend/sales/order/view/order_id/1'
            ]
        ];
    }
}
