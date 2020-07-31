<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Block\Adminhtml\Banner\Edit\Tab;

use Magento\Backend\Test\Block\Widget\Tab;
use Magento\Banner\Test\Block\Adminhtml\Promo\CartPriceRulesGrid;
use Magento\Banner\Test\Block\Adminhtml\Promo\CatalogPriceRulesGrid;
use Magento\Mtf\Client\Element;
use Magento\Mtf\Client\Locator;

/**
 * Class RelatedPromotions
 * Banner related promotions per store view edit page
 */
class RelatedPromotions extends Tab
{
    /**
     * Locator for Sales Rule Grid
     *
     * @var string
     */
    protected $salesRuleGrid = '//div[@data-bind="scope: \'sales_rule_listing.sales_rule_listing\'"]';

    //@codingStandardsIgnoreStart
    /**
     * Locator for Catalog Rule Grid
     *
     * @var string
     */
    protected $catalogRuleGrid = '//div[@data-bind="scope: \'banner_catalog_rule_listing.banner_catalog_rule_listing\'"]';
    //@codingStandardsIgnoreEnd

    /**
     * @var string
     */
    private $addSalesRuleButton ='//button/span[contains(text(), "Add Cart Price Rules")]';

    /**
     * @var string
     */
    private $addCatPriceButton = '//button/span[contains(text(), "Add Catalog Price Rules")]';

    /**
     * @var string
     */
    private $addSelected = '.action-primary';

    /**
     * @var string
     */
    private $salesRuleModal = '.banner_form_banner_form_promotions_sales_rules_sales_rule_modal';

    /**
     * @var string
     */
    private $catalogRuleModal = '.banner_form_banner_form_promotions_catalog_price_rules_modal';

    /**
     * Get Cart Price Rules grid on the Banner New page
     *
     * @return CartPriceRulesGrid
     */
    public function getCartPriceRulesGrid()
    {
        $this->_rootElement->find($this->addSalesRuleButton, Locator::SELECTOR_XPATH)->click();
        $this->waitForElementVisible($this->salesRuleGrid, Locator::SELECTOR_XPATH);
        return $this->blockFactory->create(
            \Magento\Banner\Test\Block\Adminhtml\Promo\CartPriceRulesGrid::class,
            [
                'element' => $this->browser->find($this->salesRuleGrid, Locator::SELECTOR_XPATH)
            ]
        );
    }

    /**
     * Get Catalog Price Rules grid on the Banner New page
     *
     * @return CatalogPriceRulesGrid
     */
    public function getCatalogPriceRulesGrid()
    {
        $this->_rootElement->find($this->addCatPriceButton, Locator::SELECTOR_XPATH)->click();
        $this->waitForElementVisible($this->catalogRuleGrid, Locator::SELECTOR_XPATH);
        return $this->blockFactory->create(
            \Magento\Banner\Test\Block\Adminhtml\Promo\CatalogPriceRulesGrid::class,
            [
                'element' => $this->_rootElement->find($this->catalogRuleGrid, Locator::SELECTOR_XPATH)
            ]
        );
    }

    /**
     * Select catalog price rule in the modal window
     *
     * @param array $filter
     * @throws \Exception
     * @return void
     */
    public function selectRelatedCatalogPriceRule($filter)
    {
        $context = $this->browser->find($this->catalogRuleModal);
        $grid  = $this->getCatalogPriceRulesGrid();
        $grid->searchAndSelect($filter);
        $context->find($this->addSelected)->click();
    }

    /**
     * Select cart price rule in the modal window
     *
     * @param array $filter
     * @throws \Exception
     * @return void
     */
    public function selectRelatedCartPriceRule($filter)
    {
        $context = $this->browser->find($this->salesRuleModal);
        $grid  = $this->getCartPriceRulesGrid();
        $grid->searchAndSelect($filter);
        $context->find($this->addSelected)->click();
    }
}
