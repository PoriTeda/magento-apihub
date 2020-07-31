<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Backup\Source;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Backup\Source\Status
     */
    protected $status;

    /**
     * @var \Magento\Support\Model\Backup|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backupMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->backupMock = $this->createMock(\Magento\Support\Model\Backup::class);

        $this->status = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Backup\Source\Status::class,
            ['backup' => $this->backupMock]
        );
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        $expectedResult = [
            ['label' => '', 'value' => ''],
            ['label' => 'titleStatusOne', 'value' => 'statusOne'],
            ['label' => 'titleStatusTwo', 'value' => 'statusTwo']
        ];

        $this->backupMock->expects($this->once())
            ->method('getAvailableStatuses')
            ->willReturn([
                'statusOne' => 'titleStatusOne',
                'statusTwo' => 'titleStatusTwo'
            ]);

        $this->assertSame($expectedResult, $this->status->toOptionArray());
    }
}
