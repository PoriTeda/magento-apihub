<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Controller\Adminhtml\Order;

use Riki\CvsPayment\Api\ConstantInterface;

/**
 * Class Index
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends \Magento\Sales\Controller\Adminhtml\Order\Index
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CvsPayment::cvs_order');
    }

    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $flag = $this->_coreRegistry
            ->registry(ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS);
        if (!$flag) {
            $this->_coreRegistry
                ->register(ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS, true);

        }
        return parent::execute();
    }
}
