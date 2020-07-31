<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Test\Unit\Model\Order\Grid\Massaction;

class ItemsUpdaterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_cfgSalesArchiveMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_authorizationMock;

    /**
     * @var \Magento\SalesArchive\Model\Order\Grid\Massaction\ItemsUpdater
     */
    protected $_model;

    /**
     * @var array
     */
    protected $_updateArgs;

    protected function setUp()
    {
        $this->_cfgSalesArchiveMock = $this->getMockBuilder(
            \Magento\SalesArchive\Model\Config::class
        )->disableOriginalConstructor()->getMock();

        $this->_authorizationMock = $this->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)->getMock();

        $this->_model = new \Magento\SalesArchive\Model\Order\Grid\Massaction\ItemsUpdater(
            $this->_cfgSalesArchiveMock,
            $this->_authorizationMock
        );

        $this->_updateArgs = [
            'add_order_to_archive' => ['label' => 'Move to Archive', 'url' => '*/sales_archive/massAdd'],
            'cancel_order' => ['label' => 'Cancel', 'url' => '*/sales_archive/massCancel'],
        ];
    }

    public function testConfigActive()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->will($this->returnValue(true));

        $this->assertEquals($this->_updateArgs, $this->_model->update($this->_updateArgs));
    }

    public function testConfigNotActive()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->will($this->returnValue(false));

        $this->assertArrayNotHasKey('add_order_to_archive', $this->_model->update($this->_updateArgs));
    }

    public function testAuthAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->will($this->returnValue(true));

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->with(
            'Magento_SalesArchive::add',
            null
        )->will(
            $this->returnValue(true)
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        $this->assertArrayHasKey('add_order_to_archive', $updatedArgs);
    }

    public function testAuthNotAllowed()
    {
        $this->_cfgSalesArchiveMock->expects($this->any())->method('isArchiveActive')->will($this->returnValue(true));

        $this->_authorizationMock->expects(
            $this->any()
        )->method(
            'isAllowed'
        )->with(
            'Magento_SalesArchive::add',
            null
        )->will(
            $this->returnValue(false)
        );

        $updatedArgs = $this->_model->update($this->_updateArgs);
        $this->assertArrayNotHasKey('add_order_to_archive', $updatedArgs);
    }
}
