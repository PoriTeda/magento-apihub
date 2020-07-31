<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ResourceConnections\Test\Unit\DB;

use Magento\ResourceConnections\DB\Select;

class SelectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\ResourceConnections\DB\Select
     */
    protected $select;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mysqlMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->mysqlMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->setMethods(['setUseMasterConnection'])
            ->getMock();
        $selectRenderer = $this->createMock(\Magento\Framework\DB\Select\SelectRenderer::class);
        $this->select = new Select(
            $this->mysqlMock,
            $selectRenderer
        );
    }

    /**
     * @return void
     */
    public function testForUpdate()
    {
        $this->mysqlMock->expects(
            $this->once()
        )->method(
            'setUseMasterConnection'
        );
        $this->select->forUpdate();
    }
}
