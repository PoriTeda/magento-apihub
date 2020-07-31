<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Update\Grid;

use Magento\Staging\Model\Update\Grid\ActionsDataProvider;

/**
 * Class ActionsDataProviderTest
 * @package Magento\Staging\Test\Unit\Model\Update\Grid
 */
class ActionsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ActionsDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $action;

    public function testGetActionData()
    {
        $actionsList = $this->getActionDataDataProvider();
        $model = new ActionsDataProvider($actionsList);

        $this->action->expects($this->exactly(count($actionsList)))
            ->method('getActionData')
            ->willReturn(['']);

        $model->getActionData([]);
    }

    /**
     * @return array
     */
    public function getActionDataDataProvider()
    {
        return [
            'deleteAction' => $this->getActionStub(),
            'editAction' => $this->getActionStub()
        ];
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testGetActionDataWithException()
    {
        $actionsList = $this->getActionDataWithExceptionDataProvider();
        $model = new ActionsDataProvider($actionsList);

        $this->action->expects($this->once())
            ->method('getActionData')
            ->withAnyParameters()
            ->willReturn([]);

        $model->getActionData();
    }

    /**
     * @return array
     */
    public function getActionDataWithExceptionDataProvider()
    {
        return [
            'deleteAction' => $this->getActionStub(),
            'dummyWrongAction' => 'just some dummy action'
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getActionStub()
    {
        if ($this->action === null) {
            $this->action = $this->getMockBuilder(\Magento\Staging\Model\Update\Grid\ActionsDataProvider::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->action;
    }
}
