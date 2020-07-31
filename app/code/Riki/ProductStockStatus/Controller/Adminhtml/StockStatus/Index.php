<?php
/**
 * Schedules Controller
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductStockStatus\Controller\Adminhtml\StockStatus;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 *
 * @category  RIKI
 * @package   Riki\ProductStockStatus\Controller\Adminhtml\StockStatus
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends StockStatusAbstract
{

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Product stock status management'));
        return $resultPage;
    }

}
