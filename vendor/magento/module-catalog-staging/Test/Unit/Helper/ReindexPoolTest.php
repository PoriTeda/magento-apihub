<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Helper;

use Magento\CatalogStaging\Helper\ReindexPool;

class ReindexPoolTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Indexer\AbstractProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerProcessor;

    /**
     * @var ReindexPool
     */
    private $helper;

    protected function setUp()
    {
        $this->indexerProcessor = $this->getMockBuilder(\Magento\Framework\Indexer\AbstractProcessor::class)
            ->disableOriginalConstructor()
            ->setMethods(['reindexList'])
            ->getMockForAbstractClass();

        $reindexPool = [
            $this->indexerProcessor
        ];

        $this->helper = new ReindexPool($reindexPool);
    }

    public function testReindexList()
    {
        $ids = [1];

        $this->indexerProcessor->expects($this->once())
            ->method('reindexList')
            ->with($ids, true)
            ->willReturnSelf();

        $this->helper->reindexList($ids);
    }
}
