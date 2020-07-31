<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\MultipleWishlist\Test\Unit\Controller\Adminhtml\Report\Customer\Wishlist;

use Magento\MultipleWishlist\Controller\Adminhtml\Report\Customer\Wishlist\ExportCsv;
use Magento\Framework\App\Filesystem\DirectoryList;

class ExportCsvTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Framework\App\Response\Http\FileFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $fileFactory;

    /** @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultFactory;

    /** @var \Magento\Framework\View\Result\Layout $resultLayout|\PHPUnit_Framework_MockObject_MockObject */
    protected $resultLayout;

    /** @var \Magento\Framework\View\Layout|\PHPUnit_Framework_MockObject_MockObject */
    protected $layout;

    /** @var \Magento\Backend\Block\Widget\Grid\ExportInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $exportGridBlock;

    /** @var ExportCsv */
    protected $controller;

    /** @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $response;

    protected function setUp()
    {
        $this->fileFactory = $this->createPartialMock(
            \Magento\Framework\App\Response\Http\FileFactory::class,
            ['create']
        );
        $this->resultLayout = $this->createMock(\Magento\Framework\View\Result\Layout::class);
        $this->layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->exportGridBlock = $this->getMockForAbstractClass(
            \Magento\Backend\Block\Widget\Grid\ExportInterface::class,
            [],
            '',
            false
        );
        $this->resultFactory = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->response = $this->getMockForAbstractClass(
            \Magento\Framework\App\ResponseInterface::class,
            [],
            '',
            false
        );

        $objectHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->context = $objectHelper->getObject(
            \Magento\Backend\App\Action\Context::class,
            [
               'resultFactory' => $this->resultFactory
            ]
        );
        $this->controller = new ExportCsv($this->context, $this->fileFactory);
    }

    public function testExecute()
    {
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->resultLayout);
        $this->resultLayout->expects($this->once())
            ->method('getLayout')
            ->willReturn($this->layout);
        $this->layout->expects($this->once())
            ->method('getChildBlock')
            ->with('adminhtml.block.report.customer.wishlist.grid', 'grid.export')
            ->willReturn($this->exportGridBlock);
        $this->exportGridBlock->expects($this->once())
            ->method('getCsvFile')
            ->willReturn('csvFile');
        $this->fileFactory->expects($this->once())
            ->method('create')
            ->with('customer_wishlists.csv', 'csvFile', DirectoryList::VAR_DIR)
            ->willReturn($this->response);

        $this->controller->execute();
    }
}
