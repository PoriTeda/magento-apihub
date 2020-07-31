<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Model\Config\Source\ReAssignation\Status;

class Delete extends \Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation
{
    /**
     * @var \Riki\AdvancedInventory\Model\ReAssignationFactory
     */
    protected $reAssignationFactory;

    /**
     * Delete constructor.
     * @param Action\Context $context
     * @param \Riki\AdvancedInventory\Model\ReAssignationFactory $reAssignationFactory
     */
    public function __construct(
        Action\Context $context,
        \Riki\AdvancedInventory\Model\ReAssignationFactory $reAssignationFactory
    )
    {
        $this->reAssignationFactory = $reAssignationFactory;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $request = $this->getRequest();

        /** @var \Magento\Framework\Controller\Result\Redirect $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $result->setUrl($this->getUrl('*/*/'));

        $id = $request->getParam('id');
        if (!$id) {
            return $result;
        }

        /** @var \Riki\AdvancedInventory\Model\ReAssignation $model */
        $model = $this->reAssignationFactory->create();

        $model->load($id);

        try {

            if (!$model || !$model->getId() || $model->getData('status') != Status::STATUS_WAITING) {
                throw new LocalizedException(__('Request data is invalid.'));
            }

            $model->delete();
            $this->messageManager->addSuccess(__('Item has been deleted successfully.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred when deleting this item, please try again!'));
        }

        return $result;
    }
}
