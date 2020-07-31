<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

use Magento\Framework\ObjectManagerInterface;

class PoolTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $actions = [
        \Magento\SalesRule\Api\Data\RuleInterface::class => [
            'save' => [
                'save' => 'ruleUpdateSaveSaveAction',
                'assign' => 'ruleUpdateSaveAssignAction',
            ]
        ]
    ];

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $transactionPool;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    private $objectManager;

    /**
     * @var \Magento\Staging\Model\Entity\Update\Action\Pool
     */
    protected $pool;

    public function setUp()
    {
        $this->transactionPool = $this->getMockBuilder(
            \Magento\Staging\Model\Entity\Update\Action\TransactionPool::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pool = new \Magento\Staging\Model\Entity\Update\Action\Pool(
            $this->transactionPool,
            $this->objectManager,
            $this->actions
        );
    }

    public function testGetExecutorNotExistsInPool()
    {
        $action = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Action\ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assertEquals($action, $this->pool->getExecutor($action));
    }

    public function testGetExecutor()
    {
        $action = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Action\ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $executor = $this->getMockBuilder(
            \Magento\Staging\Model\Entity\Update\Action\TransactionExecutorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $executor->expects($this->once())
            ->method('setAction');
        $this->transactionPool->expects($this->once())
            ->method('getExecutor')
            ->willReturn($executor);
        $this->assertEquals($executor, $this->pool->getExecutor($action));
    }

    public function testGetAction()
    {
        $entityType = \Magento\SalesRule\Api\Data\RuleInterface::class;
        $namespace = 'save';
        $actionType = 'assign';
        $action = $this->getMockBuilder(\Magento\Staging\Model\Entity\Update\Action\ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with($this->actions[$entityType][$namespace][$actionType])->willReturn($action);
        $this->assertEquals($action, $this->pool->getAction($entityType, $namespace, $actionType));
    }
}
