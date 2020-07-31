<?php
namespace Riki\Rma\Controller\Adminhtml\ReviewCc;

use Magento\Framework\Exception\LocalizedException;

class Create extends \Riki\Rma\Controller\Adminhtml\AbstractAction
{
    const ACL_RESOURCE_NAME = 'Riki_Rma::rma_return_actions_review_cc';

    /** @var \Magento\Backend\Model\Auth\Session  */
    protected $authSession;

    /** @var \Riki\Rma\Model\ReviewCcFilter  */
    protected $reviewCcFilter;

    /** @var \Riki\Rma\Model\ReviewCcFactory  */
    protected $reviewCc;

    /**
     * Create constructor.
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Riki\Rma\Model\ReviewCcFilter $reviewCcFilter
     * @param \Riki\Rma\Model\ReviewCcFactory $reviewCc
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Rma\Model\ReviewCcFilter $reviewCcFilter,
        \Riki\Rma\Model\ReviewCcFactory $reviewCc
    ) {
        parent::__construct($registry, $context);
        $this->authSession = $authSession;
        $this->reviewCcFilter = $reviewCcFilter;
        $this->reviewCc = $reviewCc;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();

        $result->setUrl($this->getUrl('adminhtml/rma/'));

        // Get list rma id selected.
        $ids = $this->getRequest()->getParam('entity_ids', []);

        $returns = $this->reviewCcFilter->load($ids);

        $totalReturns = count($returns);
        if ($totalReturns) {
            /** @var \Riki\Rma\Model\ReviewCc $reviewCcModel */
            $reviewCcModel = $this->reviewCc->create();
            $reviewCcModel->addData([
                'total_returns' =>  $totalReturns,
                'executed_by'    =>  $this->authSession->getUser()->getUserName(),
                'status'    =>  \Riki\Rma\Model\Config\Source\ReviewCc\Status::STATUS_NEW,
                'returns_data'  =>  $returns
            ]);

            try {
                $reviewCcModel->save();
                $result->setUrl($this->getUrl('riki_rma/reviewCc/'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We cannot handle your request now, please try again later.'));
            }
        } else {
            $this->messageManager->addError(__('There are not any matched conditions return now.'));
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ACL_RESOURCE_NAME);
    }
}
