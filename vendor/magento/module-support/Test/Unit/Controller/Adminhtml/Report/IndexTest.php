<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\Controller\ResultFactory;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleMock;

    /**
     * @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Backend\Model\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultPageMock;

    /**
     * @var ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Support\Controller\Adminhtml\Report\Index
     */
    protected $indexAction;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->titleMock = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $this->configMock = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $this->resultPageMock = $this->createMock(\Magento\Backend\Model\View\Result\Page::class);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);

        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->indexAction = $this->objectManagerHelper->getObject(
            \Magento\Support\Controller\Adminhtml\Report\Index::class,
            ['context' => $this->contextMock]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->titleMock->expects($this->once())
            ->method('prepend')
            ->with(__('System Reports'));

        $this->configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($this->titleMock);

        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_report')
            ->willReturnSelf();
        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configMock);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($this->resultPageMock);

        $this->assertSame($this->resultPageMock, $this->indexAction->execute());
    }
}
