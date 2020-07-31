<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

abstract class AbstractLogsSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\Logs\LogFilesData|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logFilesDataMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->logFilesDataMock = $this->getMockBuilder(\Magento\Support\Model\Report\Group\Logs\LogFilesData::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
