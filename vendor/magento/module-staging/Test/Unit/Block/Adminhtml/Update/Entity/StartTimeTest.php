<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Block\Adminhtml\Update\Entity;

class StartTimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionHistoryMock;

    /**
     * @var \Magento\Staging\Block\Adminhtml\Update\Entity\StartTime
     */
    private $model;

    protected function setUp()
    {

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\ContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $uiComponentMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uiComponentMock->expects($this->any())
            ->method('getData')
            ->withAnyParameters()
            ->willReturn(['extends' => ['test']]);

        $uiComponentFactoryMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $uiComponentFactoryMock->expects($this->any())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($uiComponentMock);

        $this->updateRepositoryMock = $this->getMockBuilder(\Magento\Staging\Api\UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionHistoryMock = $this->getMockBuilder(\Magento\Staging\Model\VersionHistoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            \Magento\Staging\Block\Adminhtml\Update\Entity\StartTime::class,
            [
                'context' => $this->contextMock,
                'uiComponentFactory' => $uiComponentFactoryMock,
                'updateRepository' => $this->updateRepositoryMock,
                'versionHistory' => $this->versionHistoryMock
            ]
        );
    }

    public function testPrepareActiveCompany()
    {
        $id = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);

        $processorMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->expects($this->any())
            ->method('getRequestParam')
            ->willReturn($id);
        $dataProvider = \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class;
        $dataProviderMock = $this->getMockBuilder($dataProvider)
            ->disableOriginalConstructor()
            ->getMock();
        $dataProviderMock->expects($this->once())
            ->method('getRequestFieldName')
            ->willReturn('id');
        $this->contextMock->expects($this->any())
            ->method('getDataProvider')
            ->willReturn($dataProviderMock);

        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($id);

        $this->model->prepare();
        $data = $this->model->getData();
        $this->assertEquals(1, $data['config']['disabled']);
    }

    public function testPrepareUpcomingCompany()
    {
        $id = 123;
        $data['config']['formElement'] = 'testStartTime';
        $this->model->setData($data);

        $processorMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getProcessor')
            ->willReturn($processorMock);
        $this->contextMock->expects($this->any())
            ->method('getRequestParam')
            ->willReturn($id);
        $dataProvider = \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class;
        $dataProviderMock = $this->getMockBuilder($dataProvider)
            ->disableOriginalConstructor()
            ->getMock();
        $dataProviderMock->expects($this->once())
            ->method('getRequestFieldName')
            ->willReturn('id');
        $this->contextMock->expects($this->any())
            ->method('getDataProvider')
            ->willReturn($dataProviderMock);

        $this->versionHistoryMock->expects($this->once())
            ->method('getCurrentId')
            ->willReturn($id + 1);

        $this->model->prepare();
        $data = $this->model->getData();
        $this->assertNotContains('disabled', $data['config']);
    }
}
