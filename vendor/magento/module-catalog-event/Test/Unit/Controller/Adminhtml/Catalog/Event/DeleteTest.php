<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\Delete;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class DeleteTest extends \Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest
{
    /**
     * @var \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\Delete
     */
    protected $delete;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->delete = new Delete(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @param int $categoryId
     * @param \PHPUnit\Framework\MockObject_Stub $deleteResult
     * @param \PHPUnit\Framework\MockObject_Matcher_Invocation $successCalls
     * @param \PHPUnit\Framework\MockObject_Matcher_Invocation $errorCalls
     * @param string $redirectPath
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute($categoryId, $deleteResult, $successCalls, $errorCalls, $redirectPath)
    {
        $eventMock = $this->getMockBuilder(\Magento\CatalogEvent\Model\Event::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->will(
                $this->returnValueMap(
                    [
                        ['id', false, 321],
                        ['category', null, $categoryId]
                    ]
                )
            );

        $eventMock
            ->expects($this->once())
            ->method('load')
            ->with(321);
        $eventMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(321);
        $eventMock
            ->expects($this->once())
            ->method('delete')
            ->will($deleteResult);

        $this->messageManagerMock
            ->expects($successCalls)
            ->method('addSuccess')
            ->with(new Phrase('You deleted the event.'));
        $this->messageManagerMock
            ->expects($errorCalls)
            ->method('addError');

        $this->eventFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($eventMock);

        $this->backendHelperMock
            ->expects($this->once())
            ->method('getUrl')
            ->with($redirectPath);

        $this->delete->execute();
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        $exception = new \Exception('expected exception');

        return [
            [999, $this->returnValue(true), $this->once(), $this->never(), 'adminhtml/category/edit'],
            [null, $this->returnValue(true), $this->once(), $this->never(), 'adminhtml/*/'],
            [999, $this->throwException($exception), $this->never(), $this->once(), 'adminhtml/*/edit']
        ];
    }
}
