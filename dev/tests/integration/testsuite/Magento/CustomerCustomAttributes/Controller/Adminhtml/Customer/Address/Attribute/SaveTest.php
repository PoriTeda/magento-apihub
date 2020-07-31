<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Controller\Adminhtml\Customer\Address\Attribute;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Magento\Framework\Data\Form\FormKey;

/**
 * @magentoAppArea adminhtml
 */
class SaveTest extends AbstractBackendController
{
    private static $regionFrontendLabel = 'New region label';

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->attributeRepository = $this->_objectManager->get(AttributeRepositoryInterface::class);
    }

    /**
     * Tests that RegionId frontend label equal to Region frontend label.
     *
     * RegionId is hidden frontend input attribute and isn't available for updating via admin panel,
     * but frontend label of this attribute is visible in address forms as Region label.
     * So frontend label for RegionId should be synced with frontend label for Region attribute, which is
     * available for updating.
     *
     * @return void
     */
    public function testRegionFrontendLabelUpdate(): void
    {
        $params = $this->getRequestData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        /**
         * Check that errors was generated and set to session
         */
        self::assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);

        $regionIdAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::REGION_ID
        );

        self::assertEquals(self::$regionFrontendLabel, $regionIdAttribute->getDefaultFrontendLabel());
    }

    /**
     * Tests that controller validate file extensions.
     *
     * @return void
     */
    public function testFileExtensions(): void
    {
        $params = $this->getRequestNewAttributeData();
        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setPostValue($params);

        $this->dispatch('backend/admin/customer_address_attribute/save');

        $this->assertSessionMessages(
            $this->equalTo(['Please correct the value for file extensions.'])
        );
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestData(): array
    {
        $regionAttribute = $this->attributeRepository->get(
            'customer_address',
            AddressInterface::REGION
        );

        return [
            'attribute_id' => $regionAttribute->getAttributeId(),
            'frontend_label' => [self::$regionFrontendLabel],
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }

    /**
     * Gets request params.
     *
     * @return array
     */
    private function getRequestNewAttributeData(): array
    {
        return [
            'attribute_code' => 'new_file',
            'frontend_label' => ['new_file'],
            'frontend_input' => 'file',
            'file_extensions' => 'php',
            'sort_order' => 1,
            'form_key' => $this->_objectManager->get(FormKey::class)->getFormKey(),
        ];
    }
}
