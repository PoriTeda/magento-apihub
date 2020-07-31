<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Controller\Adminhtml\Rma;

/**
 * Class Index
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends \Riki\TmpRma\Controller\Adminhtml\Rma
{
    const ADMIN_RESOURCE = 'Riki_TmpRma::rma';

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /**
         * Type Hinting
         *
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Riki_TmpRma::tmprma');
        $resultPage->addBreadcrumb(__('Temp Returns'), __('Temp Returns'));
        $resultPage->addBreadcrumb(
            __('Manage Temp Returns'),
            __('Manage Temp Returns')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Temp Returns'));
        return $resultPage;
    }
}