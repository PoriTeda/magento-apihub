<?php
/**
* PHP version 7
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*
* @category Riki_Stock
* @package  Riki\ProductStockStatus\Block\Adminhtml
* @author   Nestle <support@nestle.co.jp>
* @license  http://nestle.co.jp/policy.html GNU General Public License
* @link     http://shop.nestle.jp
*/

namespace Riki\ProductStockStatus\Block\Adminhtml;

/**
 * Class StockStatus
 *
 * @category    Riki_ProductStockStatus
 * @package     Riki\ProductStockStatus\Block\Adminhtml
 * @author      Nestle <support@nestle.co.jp>
 * @license     http://nestle.co.jp/policy.html GNU General Public License
 * @link        http://shop.nestle.jp
 */
class StockStatus extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Riki_ProductStockStatus';
        $this->_headerText = __('Product stock status');
        $this->resultPage->getConfig()->getTitle()->prepend(__('Product stock status Management'));
        parent::_construct();
    }
}