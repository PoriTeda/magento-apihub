<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Helper;

class EavTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Helper\Eav
     */
    protected $_model;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $collectionFactory = $this->createPartialMock(
            \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory::class,
            ['create']
        );
        $attributeConfig = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Config::class);
        $this->_model = $helper->getObject(
            \Magento\Rma\Helper\Eav::class,
            [
                'collectionFactory' => $collectionFactory,
                'attributeConfig' => $attributeConfig,
                'context' => $this->createMock(\Magento\Framework\App\Helper\Context::class)
            ]
        );
    }

    /**
     * @param $validateRules
     * @param array $additionalClasses
     * @internal param array $attributeValidateRules
     * @dataProvider getAdditionalTextElementClassesDataProvider
     */
    public function testGetAdditionalTextElementClasses($validateRules, $additionalClasses)
    {
        $attributeMock = new \Magento\Framework\DataObject(['validate_rules' => $validateRules]);
        $this->assertEquals($this->_model->getAdditionalTextElementClasses($attributeMock), $additionalClasses);
    }

    /**
     * @return array
     */
    public function getAdditionalTextElementClassesDataProvider()
    {
        return [
            [[], []],
            [['min_text_length' => 10], ['validate-length', 'minimum-length-10']],
            [['max_text_length' => 20], ['validate-length', 'maximum-length-20']],
            [
                ['min_text_length' => 10, 'max_text_length' => 20],
                ['validate-length', 'minimum-length-10', 'maximum-length-20']
            ]
        ];
    }
}
