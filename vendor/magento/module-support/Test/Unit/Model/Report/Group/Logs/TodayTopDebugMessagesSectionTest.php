<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Logs;

class TodayTopDebugMessagesSectionTest extends AbstractLogsSectionTest
{
    /**
     * @var \Magento\Support\Model\Report\Group\Logs\TodayTopDebugMessagesSection
     */
    protected $todayTopDebugMessagesSection;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->todayTopDebugMessagesSection = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Logs\TodayTopDebugMessagesSection::class,
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
        $currentDate = (new \DateTime())->format('Y-m-d');
        // @codingStandardsIgnoreStart
        $todayTopDebugMessagesData = [
            \Magento\Support\Model\Report\Group\Logs\LogFilesData::CURRENT_DEBUG_MESSAGES => [
                [
                    4,
                    'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '' . $currentDate . ', 14:18:33'
                ],
                [
                    1,
                    'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                    '' . $currentDate . ', 10:38:27'
                ]
            ],
        ];
        $expectedData = [
            (string)__('Today\'s Top Debug Messages') => [
                'headers' => [__('Count'), __('Message'), __('Last Occurrence')],
                'data' => [
                    [
                        4,
                        'cache_invalidate:  {"method":"GET","url":"http://magento2.loc/index.php/admin/support/report/create/","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '' . $currentDate . ', 14:18:33'
                    ],
                    [
                        1,
                        'cache_invalidate:  {"method":"POST","url":"http://magento2.loc/index.php/admin/support/report/create/?isAjax=true","invalidateInfo":{"tags":["interception","CONFIG"],"mode":"matchingTag"},"is_exception":false} []',
                        '' . $currentDate . ', 10:38:27'
                    ]
                ]
            ]
        ];
        // @codingStandardsIgnoreEnd
        $this->logFilesDataMock->expects($this->once())
            ->method('getLogFilesData')
            ->willReturn($todayTopDebugMessagesData);

        $this->assertEquals($expectedData, $this->todayTopDebugMessagesSection->generate());
    }
}
