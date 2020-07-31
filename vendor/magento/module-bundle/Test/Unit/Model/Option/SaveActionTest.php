<?php

namespace Magento\Bundle\Test\Unit\Model\Option;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class SaveActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Bundle\Model\Option\SaveAction
     */
    protected $object;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $optionResource;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $metadataPool;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $linkManagement;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $dataObjectFactory;

    protected function setUp()
    {
        $this->optionResource = $this->getMockBuilder('Magento\Bundle\Model\ResourceModel\Option')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataPool = $this->getMockBuilder('Magento\Framework\EntityManager\MetadataPool')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = $this->getMockBuilder('Magento\Bundle\Model\Product\Type')
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkManagement = $this->getMockBuilder('Magento\Bundle\Api\ProductLinkManagementInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectFactory = $this->getMockBuilder('Magento\Framework\Validator\DataObjectFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $this->objectManager->getObject(\Magento\Bundle\Model\Option\SaveAction::class);
    }

    public function testSaveActionWhenOptionIsEmpty()
    {
        $optionMock = [];
        $productLinkMock = [];
        $eMessageMock = 'Hello world';

        $result = $this->object->checkOptionProduct($optionMock, $productLinkMock, $eMessageMock);
        $this->assertEquals('', $result);
    }

    public function testSaveActionWhenOptionIsNotEmpty()
    {
        $optionMock = ['option_id' => 3120, 'parent_id' => 5675, 'required' => 1];
        $productLinkMock = [];
        $eMessageMock = 'Hello world';

        $result = $this->object->checkOptionProduct($optionMock, $productLinkMock, $eMessageMock);
        $this->assertEquals('', $result);
    }

    public function testProductLinksIsNotEmpty()
    {
        $optionMock = ['option_id' => 3120, 'parent_id' => 5675, 'required' => 1];
        $productLinkMock = [
            0 => ['entity_id' => 123123, 'sku' => 'sku123', 'option_id' => 123, 'position' => 1]
        ];
        $optionObjectMock = $this->objectManager->getObject(\Magento\Bundle\Model\Option::class, []);
        $linkMock[] = $optionObjectMock->setProductLinks($productLinkMock);
        $eMessageMock = 'Hello world';
        $result = $this->object->checkOptionProduct($optionMock, $linkMock, $eMessageMock);
        $this->assertEquals('', $result);
    }

}