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
 * Class Delete
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends \Riki\TmpRma\Controller\Adminhtml\Rma
{
    const ADMIN_RESOURCE = 'Riki_TmpRma::rma_actions_delete';

    /**
     * Delete rma reason
     *
     * @return void
     */
    public function execute()
    {
        $rma = $this->rmaFactory->create()->load(
            $this->getRequest()->getParam('id')
        );
        if ($rma->getId()) {
            try {
                $rma->delete();
                $this->messageManager
                    ->addSuccess(__('The return has been deleted.'));
                $this->_getSession()->setFormData(false);
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager
                    ->addException(
                        $e, __('We can\'t delete this return right now.')
                    );
            }
        }
        $this->_redirect('*/*');
    }
}
