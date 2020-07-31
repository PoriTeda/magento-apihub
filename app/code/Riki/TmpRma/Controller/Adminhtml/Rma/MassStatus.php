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

use Riki\TmpRma\Helper\Status;

/**
 * Class MassStatus
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Controller
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class MassStatus extends \Riki\TmpRma\Controller\Adminhtml\Rma
{
    const ADMIN_RESOURCE = 'Riki_TmpRma::rma_actions';

    protected $status;
    /**
     * RmaFactory
     *
     * @var \Riki\TmpRma\Model\RmaFactory
     */
    protected $rmaFactory;
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * StatusHelper
     *
     * @var Status
     */
    protected $statusHelper;

    /**
     * MassStatus constructor.
     *
     * @param \Riki\TmpRma\Model\RmaFactory              $rmaFactory        factory
     * @param \Psr\Log\LoggerInterface                   $logger            logger
     * @param Status                                     $statusHelper      helper
     * @param \Magento\Backend\App\Action\Context        $context           context
     * @param \Magento\Framework\Registry                $coreRegistry      registry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory factory
     */
    public function __construct(
        \Riki\TmpRma\Model\RmaFactory $rmaFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\TmpRma\Helper\Status $statusHelper,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->statusHelper = $statusHelper;
        $this->rmaFactory = $rmaFactory;
        $this->logger = $logger;
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $rmaFactory
        );
    }


    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function execute()
    {
        $ids = $this->getRequest()->getPost('rma');
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        try {
            $model = $this->rmaFactory->create();
            $count = 0;
            foreach ($ids as $id) {
                $model->load($id);
                if (!$model->getId()) {
                    continue;
                }

                $updatable = $this->statusHelper
                    ->isUpdatable($model->getStatus(), $this->getStatus());
                if (!$updatable) {
                    $statusLabel = $this->statusHelper->getLabel($this->getStatus());
                    $beforeStatusLabels = $this->statusHelper
                        ->getDepBeforeLabel($this->getStatus());
                    $msg = 'Item width ID = %1 unable to change status to “%2”. '
                        .'Item must be status [%3] before change status to “%4”';
                    $this->messageManager->addError(
                        __(
                            $msg,
                            $id,
                            $statusLabel,
                            implode(',', $beforeStatusLabels),
                            $statusLabel
                        )
                    );
                    continue;
                }

                $model->setStatus($this->getStatus());
                $model->save();
                $count++;
            }
            $this->messageManager
                ->addSuccess(__('Total of %1 record(s) were updated.', $count));
        } catch (\Exception $e) {
            $this->messageManager
                ->addError(__('An error has occurred.'));
            $this->logger->error($e);
        }

        $this->_redirect('*/*');
    }

    /**
     * Get status
     *
     * @return mixed
     */
    protected function getStatus()
    {
        return $this->status;
    }
}

