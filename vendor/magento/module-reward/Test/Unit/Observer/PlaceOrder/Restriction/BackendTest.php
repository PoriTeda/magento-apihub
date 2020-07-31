<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

class BackendTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\PlaceOrder\Restriction\Backend
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_authorizationMock;

    protected function setUp()
    {
        $this->_helper = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->_authorizationMock = $this->createMock(\Magento\Framework\AuthorizationInterface::class);
        $this->_model = new \Magento\Reward\Observer\PlaceOrder\Restriction\Backend(
            $this->_helper,
            $this->_authorizationMock
        );
    }

    /**
     * @dataProvider isAllowedDataProvider
     * @param $expectedResult
     * @param $isEnabled
     * @param $isAllowed
     */
    public function testIsAllowed($expectedResult, $isEnabled, $isAllowed)
    {
        $this->_helper->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue($isEnabled));
        $this->_authorizationMock->expects($this->any())->method('isAllowed')->will($this->returnValue($isAllowed));
        $this->assertEquals($expectedResult, $this->_model->isAllowed());
    }

    public function isAllowedDataProvider()
    {
        return [[true, true, true], [false, true, false], [false, false, false]];
    }
}
