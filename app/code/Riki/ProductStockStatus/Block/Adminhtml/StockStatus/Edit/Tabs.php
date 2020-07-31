<?php
/**
 * ProductStockStatus Edit Tab
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit;
/**
 * Class Tabs
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Block\Adminhtml\StockStatus\Edit
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('stockstatus_tab');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Stock Status'));
    }
}
