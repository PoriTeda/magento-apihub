<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

class FrontendTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\PlaceOrder\Restriction\Frontend
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->_model = new \Magento\Reward\Observer\PlaceOrder\Restriction\Frontend($this->_helper);
    }

    public function testIsAllowed()
    {
        $this->_helper->expects($this->once())->method('isEnabledOnFront');
        $this->_model->isAllowed();
    }
}
