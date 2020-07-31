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
 * Class Edit
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Edit extends \Riki\TmpRma\Controller\Adminhtml\Rma
{
    const ADMIN_RESOURCE = 'Riki_TmpRma::rma_actions_view';

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function execute()
    {
        $model = $this->rmaFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $model->load($id);
        }

        $this->registry->register('_current_rma', $model);
        $this->registry->register('_current_rma_id', $id);

        $this->_view->loadLayout();
        $this->_setActiveMenu('Riki_Rma::tmprma');

        if ($model->getId()) {
            $breadcrumbTitle = __('Edit Temp Return  #%1', $id);
            $breadcrumbLabel = $breadcrumbTitle;
        } else {
            $breadcrumbTitle = __('New Temp Return');
            $breadcrumbLabel = __('Create Temp Return');
        }
        $this->_view->getPage()
            ->getConfig()
            ->getTitle()
            ->prepend(__('Temp Return'));
        $this->_view->getPage()
            ->getConfig()
            ->getTitle()
            ->prepend(
                $model->getId()
                    ? __('Edit Temp Return  #%1', $id)
                    : __('New Temp Return')
            );

        $this->_addBreadcrumb($breadcrumbLabel, $breadcrumbTitle);

        // restore data
        $values = $this->_getSession()->getData('riki_tmprma_rma_form_data', true);
        if ($values) {
            $model->addData($values);
        }

        $this->_view->renderLayout();
    }
}