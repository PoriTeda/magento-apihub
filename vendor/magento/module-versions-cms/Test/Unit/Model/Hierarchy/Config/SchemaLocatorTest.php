<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy\Config;

class SchemaLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_modulesReaderMock;

    protected function setUp()
    {
        $this->_modulesReaderMock = $this->createMock(\Magento\Framework\Module\Dir\Reader::class);

        $this->_modulesReaderMock->expects(
            $this->once()
        )->method(
            'getModuleDir'
        )->with(
            'etc',
            'Magento_VersionsCms'
        )->will(
            $this->returnValue('some_path')
        );

        $this->_model = new \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator($this->_modulesReaderMock);
    }

    /**
     * @covers \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator::getSchema
     */
    public function testGetSchema()
    {
        $expectedSchemaPath = 'some_path/menu_hierarchy_merged.xsd';
        $this->assertEquals($expectedSchemaPath, $this->_model->getSchema());
    }

    /**
     * @covers \Magento\VersionsCms\Model\Hierarchy\Config\SchemaLocator::getPerFileSchema
     */
    public function testGetPerFileSchema()
    {
        $expectedSchemaPath = 'some_path/menu_hierarchy.xsd';
        $this->assertEquals($expectedSchemaPath, $this->_model->getPerFileSchema());
    }
}
