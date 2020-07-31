<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\SynchronizeEntityPeriod;
use Magento\Framework\Api\SearchCriteriaBuilder;

class SynchronizeEntityPeriodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SynchronizeEntityPeriod
     */
    private $model;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;

    public function setUp()
    {
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->model = (new ObjectManager($this))->getObject(
            SynchronizeEntityPeriod::class,
            ['searchCriteriaBuilder' => $this->searchCriteriaBuilderMock]
        );
    }

    /**
     * @expectedException \Exception
     */
    public function testExecuteThrowExceptionShouldnotBeCaught()
    {
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('addFilters')
            ->willThrowException(new \Exception());
        $this->model->execute();
    }
}
