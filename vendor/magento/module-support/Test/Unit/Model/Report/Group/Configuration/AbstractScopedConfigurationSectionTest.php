<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Configuration;

use Magento\Framework\App\Config;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractScopedConfigurationSectionTest extends AbstractConfigurationSectionTest
{
    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->configMock = $this->createMock(\Magento\Framework\App\Config::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->storeManagerMock->expects($this->any())->method('getStores')->willReturn([]);
    }

    /**
     * @return void
     */
    abstract public function testGetConfigDataItem();
}
