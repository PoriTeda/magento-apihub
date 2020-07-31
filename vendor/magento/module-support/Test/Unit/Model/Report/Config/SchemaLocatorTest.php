<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class SchemaLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Config\SchemaLocator
     */
    protected $schemaLocator;

    protected function setUp()
    {
        /** @var $objectManagerHelper */
        $objectManagerHelper = new ObjectManagerHelper($this);

        /** @var \Magento\Framework\Module\Dir\Reader|\PHPUnit_Framework_MockObject_MockObject $moduleReaderMock */
        $moduleReaderMock = $this->createMock(\Magento\Framework\Module\Dir\Reader::class);
        $moduleReaderMock->expects($this->once())
            ->method('getModuleDir')
            ->with('etc', 'Magento_Support')
            ->willReturn('schema_dir');

        $this->schemaLocator = $objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Config\SchemaLocator::class,
            [
                'moduleReader' => $moduleReaderMock
            ]
        );
    }

    public function testGetSchema()
    {
        $this->assertEquals('schema_dir/report.xsd', $this->schemaLocator->getSchema());
    }

    public function testGetPerFileSchema()
    {
        $this->assertEquals(null, $this->schemaLocator->getPerFileSchema());
    }
}
