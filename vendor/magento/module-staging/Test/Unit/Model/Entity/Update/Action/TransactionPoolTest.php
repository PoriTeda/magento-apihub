<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

class TransactionPoolTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $transactionFactory;

    /**
     * @var \Magento\Staging\Model\Entity\Update\Action\TransactionPool
     */
    private $transactionPool;

    public function setUp()
    {
        $transactionFactory = \Magento\Staging\Model\Entity\Update\Action\TransactionExecutorFactory::class;
        $this->transactionFactory = $this->getMockBuilder($transactionFactory)
            ->disableOriginalConstructor()
            ->getMock();
        $poolData = ['item1' => 'ActionObject'];
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->transactionPool = $objectManager->getObject(
            \Magento\Staging\Model\Entity\Update\Action\TransactionPool::class,
            [
                'transactionExecutorFactory' => $this->transactionFactory,
                'transactionPool' => $poolData
            ]
        );
    }

    public function testGetExecutor()
    {
        $namespace = 'ActionObject';
        $executor = \Magento\Staging\Model\Entity\Update\Action\TransactionExecutorInterface::class;
        $transactionExecutor = $this->getMockBuilder($executor)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionFactory->expects($this->once())
            ->method('create')
            ->willReturn($transactionExecutor);
        $this->assertInstanceOf($executor, $this->transactionPool->getExecutor($namespace));
    }
}
