<?php

namespace Riki\User\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;
    /**
     * @var \Riki\GiftWrapping\Logger\Logger
     */
    protected $loggerImport;
    /**
     * @var \Riki\User\Model\PasswordFactory
     */
    protected $_passwordModel;
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;
    /**
     * @var \Riki\User\Model\Password\Import
     */
    protected $_import;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\GiftWrapping\Logger\Logger $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry,
        \Riki\User\Model\PasswordFactory  $passwordModel,
        \Riki\User\Model\Password\Import $import
    ) {
        parent::__construct($context);
        $this->_uploaderFactory = $uploaderFactory;
        $this->_csvReader = $csv;
        $this->_datetime = $dateTime;
        $this->loggerImport = $logger;
        $this->loggerImport->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_passwordModel = $passwordModel;
        $this->_coreRegistry = $registry;
        $this->_import = $import;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_User::import_password');
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            $resultBlock = $resultLayout->getLayout()->getBlock('user.import.frame.result');

            $resultBlock
                ->addAction('show', 'import_validation_container')
                ->addAction('innerHTML', 'import_validation_container_header', __('Status'))
                ->addAction('hide', ['edit_form', 'upload_button', 'messages']);

            $importResult = $this->_import->doImport('csv_import_password');
            $resultBlock->addSuccess(__('Import successfully done: %1 records',$importResult));

            return $resultLayout;
        }

        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('*/*/');
    }



    protected $_resultMessage = '';


    public function importPassWord($file)
    {
        $errors = 0;
        $tmpName = $file['tmp_name'];
        $dataFile = $this->_csvReader->getData($tmpName);
        $row = 0;

        $this->loggerImport->info(__('=================== START ====================='));
        /* @var \Riki\User\Model\Password $insertPassword */
        foreach ($dataFile as $val) {
            $insertPassword = $this->_passwordModel->create();
            if ($row == 0) {
                ++$row;
                continue;
            }
            if (!isset($val[1])) {
                $this->loggerImport->info(sprintf('NG WORD null, Import unsuccessful row %s', $row));
                $this->messageManager->addError(sprintf('NG WORD null, Import unsuccessful row %s', $row));
                ++$row;
                ++$errors;
                continue;
            } else {
                $insertPassword->setData('ng_word', $val[0])
                    ->setData('created_datetime', $val[1]);
                $insertPassword->save();
            }
        }

        if ($errors > 0) {
            $this->loggerImport->info(sprintf('TOTAL ERROR : %s', $errors));
            $this->messageManager->addError(sprintf('TOTAL ERROR : %s', $errors));
        }
        $this->loggerImport->info(sprintf('TOTAL ROWS READED: %s', $row - 1));
        $this->loggerImport->info(__('==================== END ===================='));

        $this->messageManager->addSuccess(sprintf('TOTAL ROWS READED: %s', $row - 1));
        $this->messageManager->addSuccess(__('VIEW MORE DETAIL RESULT FOR IMPORT IN THE LOG FILE : var/importGiftWrapping.log'));
    }

}
