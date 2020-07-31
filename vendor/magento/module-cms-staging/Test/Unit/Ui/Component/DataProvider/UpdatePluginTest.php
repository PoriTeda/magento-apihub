<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Ui\Component\DataProvider;

use Magento\CmsStaging\Ui\Component\DataProvider\UpdatePlugin;

class UpdatePluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdatePlugin
     */
    private $plugin;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \Magento\Framework\Api\FilterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilderMock;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);
        $this->filterBuilderMock = $this->getMockBuilder(\Magento\Framework\Api\FilterBuilder::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = new UpdatePlugin(
            $this->requestMock,
            $this->updateRepositoryMock,
            $this->filterBuilderMock
        );
    }

    /**
     * @param int|null $updateId
     * @param int $requestUpdateId
     * @param bool $isUpdateExists
     * @dataProvider getUpdateDataProvider
     */
    public function testBeforeGetSearchResult($updateId, $requestUpdateId, $isUpdateExists)
    {
        /** @var \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface $dataProviderMock */
        $dataProviderMock = $this->getMockBuilder(
            \Magento\Framework\View\Element\UiComponent\DataProvider\DataProviderInterface::class
        )->getMockForAbstractClass();

        $filterMock = $this->getMockBuilder(\Magento\Framework\Api\Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filterBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($filterMock);
        $updateMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $updateMock->expects($this->any())->method('getId')->willReturn($isUpdateExists ? $updateId : false);

        $this->requestMock->expects($this->any())->method('getParam')->willReturn($requestUpdateId);
        $this->updateRepositoryMock->expects($this->any())->method('get')->with($updateId)->willReturn($updateMock);

        if ($isUpdateExists) {
            $dataProviderMock->expects($this->once())->method('addFilter')->with($filterMock);
        } else {
            $dataProviderMock->expects($this->never())->method('addFilter');
        }

        $this->plugin->beforeGetSearchResult($dataProviderMock);
    }

    /**
     * Update data provider
     *
     * @return array
     */
    public function getUpdateDataProvider()
    {
        return [
            [1, 1, true],//update exists
            [123, 123, false],//update does not exist
        ];
    }
}
