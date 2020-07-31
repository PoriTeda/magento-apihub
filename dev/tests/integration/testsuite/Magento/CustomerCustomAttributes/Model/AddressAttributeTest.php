<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Model;

use Magento\Checkout\Model\Cart\ImageProvider;
use Magento\Checkout\Model\DefaultConfigProvider;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Session;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoAppArea frontend
 */
class AddressAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DefaultConfigProvider
     */
    private $checkoutConfigProvider;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $imageProvider = $this->getMockBuilder(ImageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager->addSharedInstance($imageProvider, ImageProvider::class);

        $this->checkoutConfigProvider = $this->objectManager->create(
            DefaultConfigProvider::class
        );
    }

    /**
     * Tests that custom address attributes with 'is_visible' option 0 are filtered
     * from checkout config provider and not visible on Storefront.
     *
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoDataFixture Magento/CustomerCustomAttributes/_files/customer_with_address_custom_attributes.php
     */
    public function testVisibilityOnStorefront()
    {
        $customerId = 1;

        /** @var Session $customerSession */
        $customerSession = $this->objectManager->get(Session::class);
        $customerSession->setCustomerId($customerId);

        /** @var HttpContext $httpContext */
        $httpContext =  $this->objectManager->get(HttpContext::class);
        $httpContext->setValue(CustomerContext::CONTEXT_AUTH, 1, 1);

        $data = $this->checkoutConfigProvider->getConfig();

        $this->performAssertions($data['customerData']['addresses']);
    }

    /**
     * @param array $addresses
     * @return void
     */
    private function performAssertions(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->assertArrayHasKey('custom_attributes', $address);
            $this->assertEmpty($address['custom_attributes']);
        }
    }
}
