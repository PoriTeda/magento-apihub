<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Sales\Quote;

class AddressTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\Sales\Quote\Address
     */
    protected $address;

    /**
     * @var \Magento\Framework\Model\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\Model\Context::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->resourceMock = $this->createMock(
            \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote\Address::class
        );

        $this->address = new \Magento\CustomerCustomAttributes\Model\Sales\Quote\Address(
            $this->contextMock,
            $this->registryMock,
            $this->resourceMock
        );
    }

    public function testAttachDataToEntities()
    {
        $entities = ['entity' => 'value'];

        $this->resourceMock->expects($this->once())
            ->method('attachDataToEntities')
            ->with($entities);

        $this->assertEquals($this->address, $this->address->attachDataToEntities($entities));
    }
}
