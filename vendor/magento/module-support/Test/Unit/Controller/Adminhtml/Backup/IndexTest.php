<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Controller\Adminhtml\Backup;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\Framework\Controller\ResultFactory;

class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Controller\Adminhtml\Backup\Index
     */
    protected $indexAction;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);

        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            ['resultFactory' => $this->resultFactoryMock]
        );
        $this->indexAction = $this->objectManagerHelper->getObject(
            \Magento\Support\Controller\Adminhtml\Backup\Index::class,
            ['context' => $this->context]
        );
    }

    /**
     * @return void
     */
    public function testExecuteReturnsPage()
    {
        /** @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject $title */
        $title = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $title->expects($this->once())
            ->method('prepend')
            ->with(__('Data Collector'));

        /** @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject $pageConfig */
        $pageConfig = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $pageConfig->expects($this->once())
            ->method('getTitle')
            ->willReturn($title);

        /** @var \Magento\Backend\Model\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject $resultPage */
        $resultPage = $this->createMock(\Magento\Backend\Model\View\Result\Page::class);
        $resultPage->expects($this->once())
            ->method('getConfig')
            ->willReturn($pageConfig);
        $resultPage->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_backup')
            ->willReturnSelf();
        $resultPage->expects($this->at(1))
            ->method('addBreadcrumb')
            ->with(__('Support'), __('Support'))
            ->willReturnSelf();
        $resultPage->expects($this->at(2))
            ->method('addBreadcrumb')
            ->with(__('Data Collector'), __('Data Collector'))
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($resultPage);

        $this->assertSame($resultPage, $this->indexAction->execute());
    }
}
