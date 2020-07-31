<?php
namespace Riki\Rma\Controller\Adminhtml\ReviewCc;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;

/**
 * Class Render
 */
class DownloadLog extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    protected $reviewCcFactory;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        \Riki\Rma\Model\ReviewCcFactory $reviewCcFactory
    ) {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->reviewCcFactory = $reviewCcFactory;
    }

    /**
     * Export data provider to CSV
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $reviewCc = $this->initReviewCc();

        if ($reviewCc) {

            $logFile = $reviewCc->getLogFile();

            if ($logFile->fileExists()) {
                try {
                    return $this->fileFactory->create($logFile->getHtmlFileName(), $logFile->getFileContent(), \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
                } catch (\Exception $e) {
                    $this->messageManager->addError($e->getMessage());
                }

            } else {
                $this->messageManager->addError(__('This file not found'));
            }
        } else {
            $this->messageManager->addError(__('Requested review not found'));
        }

        /** @var \Magento\Framework\Controller\Result\Redirect $result */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $result->setUrl($this->getUrl('riki_rma/reviewCc/'));
        return $result;
    }

    /**
     * @return null|\Riki\Rma\Model\ReviewCc
     */
    protected function initReviewCc()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id) {
            /** @var \Riki\Rma\Model\ReviewCc $reviewCc */
            $reviewCc = $this->reviewCcFactory->create();

            $reviewCc->load($id);

            if ($reviewCc && $reviewCc->getId()) {
                return $reviewCc;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(\Riki\Rma\Controller\Adminhtml\ReviewCc\Create::ACL_RESOURCE_NAME);
    }
}
