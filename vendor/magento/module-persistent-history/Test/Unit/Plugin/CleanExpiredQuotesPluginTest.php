<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PersistentHistory\Test\Unit\Plugin;

class CleanExpiredQuotesPluginTest extends \PHPUnit\Framework\TestCase
{
    public function testBeforeExecute()
    {
        $plugin = new \Magento\PersistentHistory\Plugin\CleanExpiredQuotesPlugin();
        $subjectMock = $this->createPartialMock(
            \Magento\Sales\Cron\CleanExpiredQuotes::class,
            ['setExpireQuotesAdditionalFilterFields']
        );

        $subjectMock->expects($this->once())
            ->method('setExpireQuotesAdditionalFilterFields')
            ->with(['is_persistent' => 0])
            ->willReturn(null);

        $this->assertNull($plugin->beforeExecute($subjectMock));
    }
}
