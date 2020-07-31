<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

class LogFilesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var \Magento\Support\Model\Report\Group\Logs\LogFilesSection
     */
    protected $logsFileSection;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->logsFileSection = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Logs\LogFilesSection::class,
            [
                'logFilesData' => $this->logFilesDataMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGenerate()
    {
        $modifiedDate = date('r', 1735689600);
        $formattedSize = '42 kB';
        $logFilesData = [
            \Magento\Support\Model\Report\Group\Logs\LogFilesData::LOG_FILES => [
                ['debug.log', $formattedSize, 10, $modifiedDate],
                ['exception.log', $formattedSize, 5, $modifiedDate],
                ['system.log', $formattedSize, 12, $modifiedDate]
            ]
        ];
        $expectedData = [
            (string)__('Log Files') => [
                'headers' => [__('File'), __('Size'), __('Log Entries'), __('Last Update')],
                'data' => [
                    ['debug.log', $formattedSize, 10, $modifiedDate],
                    ['exception.log', $formattedSize, 5, $modifiedDate],
                    ['system.log', $formattedSize, 12, $modifiedDate]
                ]
            ]
        ];

        $this->logFilesDataMock->expects($this->once())->method('getLogFilesData')->willReturn($logFilesData);

        $this->assertEquals($expectedData, $this->logsFileSection->generate());
    }
}
