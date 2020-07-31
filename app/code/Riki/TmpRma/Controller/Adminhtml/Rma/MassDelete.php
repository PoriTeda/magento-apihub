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
 * Class MassDelete
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class MassDelete extends \Riki\TmpRma\Controller\Adminhtml\Rma
{

    const ADMIN_RESOURCE = 'Riki_TmpRma::rma_actions_delete';

    /**
     * Delete one or more rma reason
     *
     * @return void
     */
    public function execute()
    {
        $rmaIds = $this->getRequest()->getParam('rma');
        if (!is_array($rmaIds)) {
            $this->messageManager->addError(__('Please select one or more return.'));
        } else {
            try {
                foreach ($rmaIds as $rmaId) {
                    $rma = $this->rmaFactory->create()->load(
                        $rmaId
                    );
                    $rma->delete();
                }
                $this->messageManager->addSuccess(
                    __('Total of %1 record(s) were deleted.', count($rmaIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*');
    }
}
