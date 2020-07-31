<?php
namespace Riki\Rma\Controller\Adminhtml\Returns;

use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;

class Close extends \Riki\Rma\Controller\Adminhtml\Returns
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_return_actions_close';

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $rmaItemRepository;

    /**
     * @param \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Model\RmaManagement $rmaManagement
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Api\RmaRepositoryInterface $rmaRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->rmaItemRepository = $rmaItemRepository;
        $this->dataHelper = $dataHelper;
        parent::__construct($rmaRepository, $searchHelper, $logger, $registry, $rmaManagement, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        $id = $request->getParam('id', 0);
        if ($id) {
            $result->setUrl($this->getUrl('adminhtml/rma/edit', ['id' => $id]));
        } else {
            $result->setUrl($this->getUrl('adminhtml/rma/'));
        }
        $ids = $request->getParam('entity_ids', [$id]);

        $successCount = 0;
        foreach ($ids as $id) {
            try {
                $this->rmaManagement->close($id);
                $successCount++;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $msg = $e->getMessage() . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);
            } catch (\Exception $e) {
                $rma = $this->rmaManagement->getLastProceedRma();
                $msg = __('An error occurred when processing, please try again!') . ' RMA: '
                    . ($rma->getEntityId() == $id ? $rma->getIncrementId() : $id);
                $this->messageManager->addError($msg);

                $this->logger->critical($e);
            }
        }

        if ($successCount) {
            $this->messageManager->addSuccess($successCount == 1
                ? __('You closed the return successfully.')
                : __('You closed %1 return(s) successfully.', $successCount)
            );
        }

        return $result;
    }
}