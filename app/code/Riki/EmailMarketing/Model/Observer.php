<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Model;

use \Magento\ProductAlert\Model\ProductSalability;

/**
 *
 */
class Observer
{
    /**
     * Error email template configuration
     */
    const XML_PATH_ERROR_TEMPLATE = 'catalog/productalert_cron/error_email_template';

    /**
     * Error email identity configuration
     */
    const XML_PATH_ERROR_IDENTITY = 'catalog/productalert_cron/error_email_identity';

    /**
     * 'Send error emails to' configuration
     */
    const XML_PATH_ERROR_RECIPIENT = 'catalog/productalert_cron/error_email';

    /**
     * Allow price alert
     *
     */
    const XML_PATH_PRICE_ALLOW = 'catalog/productalert/allow_price';

    /**
     * Allow stock alert
     *
     */
    const XML_PATH_STOCK_ALLOW = 'catalog/productalert/allow_stock';

    /**
     * Website collection array
     *
     * @var array
     */
    protected $_websites;

    /**
     * Warning (exception) errors array
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Catalog data
     *
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogData = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory
     */
    protected $_priceColFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $_dateFactory;

    /**
     * @var \Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory
     */
    protected $_stockColFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\ProductAlert\Model\EmailFactory
     */
    protected $_emailFactory;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var ProductSalability
     */
    protected $productSalability;

    /**
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory $priceColFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory
     * @param \Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory $stockColFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\ProductAlert\Model\EmailFactory $emailFactory
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param ProductSalability|null $productSalability
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory $priceColFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
        \Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory $stockColFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\ProductAlert\Model\EmailFactory $emailFactory,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\ProductAlert\Model\ProductSalability $productSalability = null
    ) {
        $this->_catalogData = $catalogData;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_priceColFactory = $priceColFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->_dateFactory = $dateFactory;
        $this->_stockColFactory = $stockColFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->_emailFactory = $emailFactory;
        $this->inlineTranslation = $inlineTranslation;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->productSalability = $productSalability ?: $objectManager->get(ProductSalability::class);
    }

    /**
     * Retrieve website collection array
     *
     * @return array
     * @throws \Exception
     */
    protected function _getWebsites()
    {
        if ($this->_websites === null) {
            try {
                $this->_websites = $this->_storeManager->getWebsites();
            } catch (\Exception $e) {
                $this->_errors[] = $e->getMessage();
                throw $e;
            }
        }
        return $this->_websites;
    }

    /**
     * Process price emails
     *
     * @param \Magento\ProductAlert\Model\Email $email
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processPrice(\Riki\EmailMarketing\Model\Email $email)
    {
        $email->setType('price');
        foreach ($this->_getWebsites() as $website) {
            /* @var $website \Magento\Store\Model\Website */

            if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }
            if (!$this->_scopeConfig->getValue(
                self::XML_PATH_PRICE_ALLOW,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $website->getDefaultGroup()->getDefaultStore()->getId()
            )
            ) {
                continue;
            }
            try {
                $collection = $this->_priceColFactory->create()->addWebsiteFilter(
                    $website->getId()
                )->setCustomerOrder();
            } catch (\Exception $e) {
                $this->_errors[] = $e->getMessage();
                throw $e;
            }

            $previousCustomer = null;
            $email->setWebsite($website);
            foreach ($collection as $alert) {
                try {
                    if (!$previousCustomer || $previousCustomer->getId() != $alert->getCustomerId()) {
                        $customer = $this->customerRepository->getById($alert->getCustomerId());
                        if ($previousCustomer) {
                            $email->send();
                        }
                        if (!$customer) {
                            continue;
                        }
                        $previousCustomer = $customer;
                        $email->clean();
                        $email->setCustomerData($customer);
                    } else {
                        $customer = $previousCustomer;
                    }

                    $product = $this->productRepository->getById(
                        $alert->getProductId(),
                        false,
                        $website->getDefaultStore()->getId()
                    );

                    $product->setCustomerGroupId($customer->getGroupId());
                    if ($alert->getPrice() > $product->getFinalPrice()) {
                        $productPrice = $product->getFinalPrice();
                        $product->setFinalPrice($this->_catalogData->getTaxPrice($product, $productPrice));
                        $product->setPrice($this->_catalogData->getTaxPrice($product, $product->getPrice()));
                        $email->addPriceProduct($product);

                        $alert->setPrice($productPrice);
                        $alert->setLastSendDate($this->_dateFactory->create()->gmtDate());
                        $alert->setSendCount($alert->getSendCount() + 1);
                        $alert->setStatus(1);
                        $alert->save();
                    }
                } catch (\Exception $e) {
                    $this->_errors[] = $e->getMessage();
                    throw $e;
                }
            }
            if ($previousCustomer) {
                try {
                    $email->send();
                } catch (\Exception $e) {
                    $this->_errors[] = $e->getMessage();
                    throw $e;
                }
            }
        }
        return $this;
    }

    /**
     * Process stock emails
     *
     * @param \Magento\ProductAlert\Model\Email $email
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _processStock(\Riki\EmailMarketing\Model\Email $email)
    {
        $email->setType('stock');

        foreach ($this->_getWebsites() as $website) {
            /* @var $website \Magento\Store\Model\Website */

            if (!$website->getDefaultGroup() || !$website->getDefaultGroup()->getDefaultStore()) {
                continue;
            }
            if (!$this->_scopeConfig->getValue(
                self::XML_PATH_STOCK_ALLOW,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $website->getDefaultGroup()->getDefaultStore()->getId()
            )
            ) {
                continue;
            }
            try {
                $collection = $this->_stockColFactory->create()->addWebsiteFilter(
                    $website->getId()
                )->addStatusFilter(
                    0
                )->setCustomerOrder();
            } catch (\Exception $e) {
                $this->_errors[] = $e->getMessage();
                throw $e;
            }

            $previousCustomer = null;
            $email->setWebsite($website);
            foreach ($collection as $alert) {
                try {
                    if (!$previousCustomer || $previousCustomer->getId() != $alert->getCustomerId()) {
                        $customer = $this->customerRepository->getById($alert->getCustomerId());
                        if ($previousCustomer) {
                            $email->send();
                        }
                        if (!$customer) {
                            continue;
                        }
                        $previousCustomer = $customer;
                        $email->clean();
                        $email->setCustomerData($customer);
                    } else {
                        $customer = $previousCustomer;
                    }

                    $product = $this->productRepository->getById(
                        $alert->getProductId(),
                        false,
                        $website->getDefaultStore()->getId()
                    );

                    $product->setCustomerGroupId($customer->getGroupId());

                    // Only send mail to inform customer when product is in stock and allow spot order is true
                    if ($this->productSalability->isSalable($product, $website) && $product->getData('allow_spot_order')) {
                        $email->addStockProduct($product);

                        $alert->setSendDate($this->_dateFactory->create()->gmtDate());
                        $alert->setSendCount($alert->getSendCount() + 1);
                        $alert->setStatus(1);
                        $alert->save();
                    }
                } catch (\Exception $e) {
                    $this->_errors[] = $e->getMessage();
                    throw $e;
                }
            }
            if ($previousCustomer) {
                try {
                    $email->send();
                } catch (\Exception $e) {
                    $this->_errors[] = $e->getMessage();
                    throw $e;
                }
            }
        }

        return $this;
    }

    /**
     * Send email to administrator if error
     *
     * @return $this
     */
    protected function _sendErrorEmail()
    {
        if (count($this->_errors)) {
            if (!$this->_scopeConfig->getValue(
                self::XML_PATH_ERROR_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
            ) {
                return $this;
            }

            $this->inlineTranslation->suspend();

            $transport = $this->_transportBuilder->setTemplateIdentifier(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setTemplateOptions(
                [
                    'area' => \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
                    'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                ]
            )->setTemplateVars(
                ['warnings' => join("\n", $this->_errors)]
            )->setFrom(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_IDENTITY,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->addTo(
                $this->_scopeConfig->getValue(
                    self::XML_PATH_ERROR_RECIPIENT,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();
            $this->_errors[] = [];
        }
        return $this;
    }

    /**
     * Run process send product alerts
     *
     * @return $this
     */
    public function process()
    {
        /* @var $email \Magento\ProductAlert\Model\Email */
        $email = $this->_emailFactory->create();
        $this->_processPrice($email);
        $this->_processStock($email);
        $this->_sendErrorEmail();

        return $this;
    }
}