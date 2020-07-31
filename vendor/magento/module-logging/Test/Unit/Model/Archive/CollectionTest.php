<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Model\Archive;

/**
 * Test \Magento\Logging\Model\Archive\Collection
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Logging\Model\Archive\Collection
     */
    protected $collection;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $filesystem = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $directoryWrite = $this->getMockBuilder(
            \Magento\Framework\Filesystem\Directory\WriteInterface::class
        )->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->any())->method('getDirectoryWrite')->will($this->returnValue($directoryWrite));

        $backupData = $this->getMockBuilder(\Magento\Backup\Helper\Data::class)->disableOriginalConstructor()
            ->getMock();
        $backupData->expects($this->any())->method('getExtensions')->will($this->returnValue([]));

        $archive = $this->getMockBuilder(\Magento\Logging\Model\Archive::class)->disableOriginalConstructor()
            ->getMock();
        $archive->expects($this->any())->method('getBasePath')->will($this->returnValue(__DIR__ . '/_files'));

        $this->collection = $this->objectManager->getObject(
            \Magento\Logging\Model\Archive\Collection::class,
            ['filesystem' => $filesystem, 'backupData' => $backupData, 'archive' => $archive]
        );
    }

    /**
     * Test generateRow()
     *
     * Calls loadData() which will cause generateRow function to be called, which updates the collection's
     * '_collectedFiles' attribute. It should be just one file and dates should be based on filename
     */
    public function testGenerateRow()
    {
        $this->collection->loadData();
        $actualCollectedFiles = $this->getObjectAttribute($this->collection, '_collectedFiles');
        $this->assertEquals(__DIR__ . '/_files/2016031415.csv', $actualCollectedFiles[0]['filename']);
        $this->assertEquals('2016031415.csv', $actualCollectedFiles[0]['basename']);
        $this->assertInstanceOf('DateTime', $actualCollectedFiles[0]['time']);
        $this->assertEquals('2016-03-14', $actualCollectedFiles[0]['timestamp']);

        /** @var \DateTime $date */
        $date = $actualCollectedFiles[0]['time'];
        $this->assertEquals('2016-03-14 15:00:00', $date->format('Y-m-d H:i:s'));
    }
}
