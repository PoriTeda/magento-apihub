<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomAttributeManagement\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomAttributeManagement\Helper\Data
     */
    protected $_helper;

    /**
     * Set up
     */
    protected function setUp()
    {
        $contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterManagerMock = $this->createPartialMock(\Magento\Framework\Filter\FilterManager::class, ['stripTags']);

        $filterManagerMock->expects($this->any())
            ->method('stripTags')
            ->will($this->returnValue('stripTags'));

        $this->_helper = new \Magento\CustomAttributeManagement\Helper\Data(
            $contextMock,
            $this->createMock(\Magento\Eav\Model\Config::class),
            $this->getMockForAbstractClass(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class),
            $filterManagerMock
        );
    }

    /**
     * @param string $frontendInput
     * @param array $validateRules
     * @param array $result
     * @dataProvider checkValidateRulesDataProvider
     */
    public function testCheckValidateRules($frontendInput, $validateRules, $result)
    {
        $this->assertEquals($result, $this->_helper->checkValidateRules($frontendInput, $validateRules));
    }

    /**
     * @return array
     */
    public function checkValidateRulesDataProvider()
    {
        return [
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2],
                [],
            ],
            [
                'text',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'textarea',
                ['min_text_length' => 1, 'max_text_length' => 2],
                []
            ],
            [
                'textarea',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'multiline',
                ['min_text_length' => 1, 'max_text_length' => 2],
                []
            ],
            [
                'multiline',
                ['min_text_length' => 3, 'max_text_length' => 2],
                [__('Please correct the values for minimum and maximum text length validation rules.')]
            ],
            [
                'date',
                ['date_range_min' => '1', 'date_range_max' => '2'],
                []
            ],
            [
                'date',
                ['date_range_min' => '3', 'date_range_max' => '2'],
                [__('Please correct the values for minimum and maximum date validation rules.')]
            ],
            [
                'empty',
                ['date_range_min' => '3', 'date_range_max' => '2'],
                []
            ]
        ];
    }

    public function testGetAttributeInputTypes()
    {
        $inputTypes = [
            'text' => [
                'label' => __('Text Field'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => ['alphanumeric', 'numeric', 'alpha', 'url', 'email'],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'varchar',
                'default_value' => 'text',
            ],
            'textarea' => [
                'label' => __('Text Area'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => [],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'text',
                'default_value' => 'textarea',
            ],
            'multiline' => [
                'label' => __('Multiple Line'),
                'manage_options' => false,
                'validate_types' => ['min_text_length', 'max_text_length'],
                'validate_filters' => ['alphanumeric', 'numeric', 'alpha', 'url', 'email'],
                'filter_types' => ['striptags', 'escapehtml'],
                'backend_type' => 'text',
                'default_value' => 'text',
            ],
            'date' => [
                'label' => __('Date'),
                'manage_options' => false,
                'validate_types' => ['date_range_min', 'date_range_max'],
                'validate_filters' => ['date'],
                'filter_types' => ['date'],
                'backend_model' => \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
                'backend_type' => 'datetime',
                'default_value' => 'date',
            ],
            'select' => [
                'label' => __('Dropdown'),
                'manage_options' => true,
                'option_default' => 'radio',
                'validate_types' => [],
                'validate_filters' => [],
                'filter_types' => [],
                'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'backend_type' => 'int',
                'default_value' => false,
            ],
            'multiselect' => [
                'label' => __('Multiple Select'),
                'manage_options' => true,
                'option_default' => 'checkbox',
                'validate_types' => [],
                'filter_types' => [],
                'validate_filters' => [],
                'backend_model' => \Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend::class,
                'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
            'boolean' => [
                'label' => __('Yes/No'),
                'manage_options' => false,
                'validate_types' => [],
                'validate_filters' => [],
                'filter_types' => [],
                'source_model' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                'backend_type' => 'int',
                'default_value' => 'yesno',
            ],
            'file' => [
                'label' => __('File (attachment)'),
                'manage_options' => false,
                'validate_types' => ['max_file_size', 'file_extensions'],
                'validate_filters' => [],
                'filter_types' => [],
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
            'image' => [
                'label' => __('Image File'),
                'manage_options' => false,
                'validate_types' => ['max_file_size', 'max_image_width', 'max_image_heght'],
                'validate_filters' => [],
                'filter_types' => [],
                'backend_type' => 'varchar',
                'default_value' => false,
            ],
        ];

        $this->assertEquals($inputTypes, $this->_helper->getAttributeInputTypes());
        foreach ($inputTypes as $key => $value) {
            $this->assertEquals($value, $this->_helper->getAttributeInputTypes($key));
        }
        $this->assertEquals([], $this->_helper->getAttributeInputTypes('empty'));
    }

    public function testGetFrontendInputOptions()
    {
        $result = [
            [
                'value' => 'text',
                'label' => __('Text Field'),
            ],
            [
                'value' => 'textarea',
                'label' => __('Text Area'),
            ],
            [
                'value' => 'multiline',
                'label' => __('Multiple Line'),
            ],
            [
                'value' => 'date',
                'label' => __('Date'),
            ],
            [
                'value' => 'select',
                'label' => __('Dropdown'),
            ],
            [
                'value' => 'multiselect',
                'label' => __('Multiple Select'),
            ],
            [
                'value' => 'boolean',
                'label' => __('Yes/No'),
            ],
            [
                'value' => 'file',
                'label' => __('File (attachment)'),
            ],
            [
                'value' => 'image',
                'label' => __('Image File'),
            ],
        ];

        $this->assertEquals($result, $this->_helper->getFrontendInputOptions());
    }

    public function testGetAttributeValidateFilters()
    {
        $result = [
            'alphanumeric' => __('Alphanumeric'),
            'numeric' => __('Numeric Only'),
            'alpha' => __('Alpha Only'),
            'url' => __('URL'),
            'email' => __('Email'),
            'date' => __('Date'),
        ];
        $this->assertEquals($result, $this->_helper->getAttributeValidateFilters());
    }

    public function testGetAttributeFilterTypes()
    {
        $result = [
            'striptags' => __('Strip HTML Tags'),
            'escapehtml' => __('Escape HTML Entities'),
            'date' => __('Normalize Date'),
        ];
        $this->assertEquals($result, $this->_helper->getAttributeFilterTypes());
    }

    public function testGetAttributeElementScopes()
    {
        $result = [
            'is_required' => 'website',
            'is_visible' => 'website',
            'multiline_count' => 'website',
            'default_value_text' => 'website',
            'default_value_yesno' => 'website',
            'default_value_date' => 'website',
            'default_value_textarea' => 'website',
            'date_range_min' => 'website',
            'date_range_max' => 'website',
        ];
        $this->assertEquals($result, $this->_helper->getAttributeElementScopes());
    }

    /**
     * @test
     * @param string $inputType
     * @param string|false $result
     * @dataProvider getAttributeDefaultValueByInputDataProvider
     */
    public function testGetAttributeDefaultValueByInput($inputType, $result)
    {
        $this->assertEquals($result, $this->_helper->getAttributeDefaultValueByInput($inputType));
    }

    /**
     * @return array
     */
    public function getAttributeDefaultValueByInputDataProvider()
    {
        return [
            [
                'text',
                'default_value_text',
            ],
            [
                'textarea',
                'default_value_textarea',
            ],
            [
                'multiline',
                'default_value_text',
            ],
            [
                'date',
                'default_value_date',
            ],
            [
                'select',
                false,
            ],
            [
                'multiselect',
                false,
            ],
            [
                'boolean',
                'default_value_yesno',
            ],
            [
                'file',
                false,
            ],
            [
                'image',
                false,
            ],
            [
                'empty',
                false,
            ]
        ];
    }

    /**
     * @test
     * @param string $inputType
     * @param array $data
     * @param array $result
     * @dataProvider getAttributeValidateRulesDataProvider
     */
    public function testGetAttributeValidateRules($inputType, $data, $result)
    {
        $this->assertEquals($result, $this->_helper->getAttributeValidateRules($inputType, $data));
    }

    /**
     * @return array
     */
    public function getAttributeValidateRulesDataProvider()
    {
        return [
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'numeric'],
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'numeric'],
            ],
            [
                'text',
                ['min_text_length' => 1, 'max_text_length' => 2, 'input_validation' => 'test'],
                ['min_text_length' => 1, 'max_text_length' => 2],
            ],
            [
                'text',
                ['min_text_length' => 1],
                ['min_text_length' => 1],
            ],
            [
                'date',
                ['date_range_max' => '01/01/2014'],
                ['date_range_max' => 1388563200],
            ]
        ];
    }

    public function testGetAttributeBackendModelByInputType()
    {
        $this->assertEquals(null, $this->_helper->getAttributeBackendModelByInputType('empty'));
        $this->assertEquals(
            \Magento\Eav\Model\Entity\Attribute\Backend\Datetime::class,
            $this->_helper->getAttributeBackendModelByInputType('date')
        );
    }

    public function testGetAttributeSourceModelByInputType()
    {
        $this->assertEquals(null, $this->_helper->getAttributeSourceModelByInputType('empty'));
        $this->assertEquals(
            \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
            $this->_helper->getAttributeSourceModelByInputType('multiselect')
        );
    }

    public function testGetAttributeBackendTypeByInputType()
    {
        $this->assertEquals(null, $this->_helper->getAttributeBackendTypeByInputType('empty'));
        $this->assertEquals('varchar', $this->_helper->getAttributeBackendTypeByInputType('text'));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage Use helper with defined EAV entity.
     */
    public function testGetUserDefinedAttributeCodes()
    {
        $this->_helper->getUserDefinedAttributeCodes();
    }

    public function testFilterPostData()
    {
        $data = ['frontend_label' => ['Label'], 'attribute_code' => 'code'];
        $result = ['frontend_label' => ['stripTags'], 'attribute_code' => 'code'];
        $this->assertEquals($result, $this->_helper->filterPostData($data));
    }

    public function testFilterPostDataWithException()
    {
        $exceptionMessage = 'The attribute code is invalid.';
        $exceptionMessage .= ' Please use only letters (a-z), numbers (0-9) or underscores (_) in this field.';
        $exceptionMessage .= ' The first character should be a letter.';
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $data = ['frontend_label' => ['Label'], 'attribute_code' => 'Code'];
        $this->_helper->filterPostData($data);
    }

    public function testGetAttributeFormOptions()
    {
        $this->assertEquals(
            [['label' => __('Default EAV Form'), 'value' => 'default']],
            $this->_helper->getAttributeFormOptions()
        );
    }
}
