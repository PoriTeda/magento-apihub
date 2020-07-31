<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

class ImportTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Object manager helper mock
     *
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Config mock
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * Plugin subject mock
     *
     * @var \Magento\ImportExport\Model\Import|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->configMock = $this->getMockBuilder(\Magento\CatalogPermissions\App\ConfigInterface::class)->getMock();
        $this->subject = $this->getMockBuilder(\Magento\ImportExport\Model\Import::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAfterImportSourceWhenCatalogPermissionsEnabled()
    {
        $this->configMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));

        $indexer = $this->getMockBuilder(
            \Magento\Indexer\Model\Indexer::class
        )->disableOriginalConstructor()->getMock();
        $indexer->expects($this->exactly(2))->method('invalidate');

        $indexerRegistryMock = $this->createPartialMock(\Magento\Framework\Indexer\IndexerRegistry::class, ['get']);
        $indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap([
                [\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID, $indexer],
                [\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID, $indexer],
            ]));

        /**
         * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Import $import
         */
        $import = $this->objectManager->getObject(
            \Magento\CatalogPermissions\Model\Indexer\Plugin\Import::class,
            [
                'config' => $this->configMock,
                'indexerRegistry' => $indexerRegistryMock
            ]
        );
        $this->assertEquals('import', $import->afterImportSource($this->subject, 'import'));
    }

    public function testAfterImportSourceWhenCatalogPermissionsDisabled()
    {
        $this->configMock->expects($this->once())->method('isEnabled')->will($this->returnValue(false));

        /**
         * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\Import $import
         */
        $import = $this->objectManager->getObject(
            \Magento\CatalogPermissions\Model\Indexer\Plugin\Import::class,
            [
                'config' => $this->configMock,
            ]
        );
        $this->assertEquals('import', $import->afterImportSource($this->subject, 'import'));
    }
}
