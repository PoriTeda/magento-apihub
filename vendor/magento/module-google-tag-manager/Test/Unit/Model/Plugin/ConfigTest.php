<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Model\Plugin\Config */
    protected $config;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\PageCache\Model\Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $pageCacheConfig;

    /** @var \Magento\Framework\App\Cache\TypeListInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $typeListInterface;

    protected function setUp()
    {
        $this->pageCacheConfig = $this->createMock(\Magento\PageCache\Model\Config::class);
        $this->typeListInterface = $this->createMock(\Magento\Framework\App\Cache\TypeListInterface::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->config = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Model\Plugin\Config::class,
            [
                'config' => $this->pageCacheConfig,
                'typeList' => $this->typeListInterface
            ]
        );
    }

    /**
     * @param bool $enabled
     * @param mixed $expects
     *
     * @dataProvider afterSaveDataProvider
     */
    public function testAfterSave($enabled, $expects)
    {
        $config = $this->createMock(\Magento\Config\Model\Config::class);

        $this->pageCacheConfig->expects($this->atLeastOnce())->method('isEnabled')->willReturn($enabled);
        $this->typeListInterface->expects($expects)->method('invalidate')->with('full_page');
        $this->assertSame($config, $this->config->afterSave($config, $config));
    }

    public function afterSaveDataProvider()
    {
        return [
            [true, $this->atLeastOnce()],
            [false, $this->never()],
        ];
    }
}
