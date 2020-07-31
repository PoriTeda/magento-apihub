<?php
/**
 * Email Marketing
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Controller\Adminhtml\Email\Template
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Controller\Adminhtml\Email\Template;
use Magento\Framework\App\TemplateTypesInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
/**
 * Class Save
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Controller\Adminhtml\Email\Template
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Magento\Email\Controller\Adminhtml\Email\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @var DateTime
     */
    protected $_dateTime;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        DateTime $dateTime
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_dateTime = $dateTime;
        parent::__construct($context,$coreRegistry);
    }
    /**
     * Save transactional email action
     *
     * @return void
     */
    public function execute()
    {
        $request = $this->getRequest();
        $id = $this->getRequest()->getParam('id');

        $template = $this->_initTemplate('id');
        if (!$template->getId() && $id) {
            $this->messageManager->addErrorMessage(__('This email template no longer exists.'));
            $this->_redirect('adminhtml/*/');
            return;
        }

        try {
            $template->setTemplateSubject(
                $request->getParam('template_subject')
            )->setTemplateCode(
                $request->getParam('template_code')
            )->setTemplateText(
                $request->getParam('template_text')
            )->setTemplateStyles(
                $request->getParam('template_styles')
            )->setModifiedAt(
                $this->_dateTime->gmtDate()
            )->setOrigTemplateCode(
                $request->getParam('orig_template_code')
            )->setOrigTemplateVariables(
                $request->getParam('orig_template_variables')
            )->setSendMidnight(
                $request->getParam('send_midnight')
            )->setEnableSent(
                $request->getParam('enable_sent')
            );

            if (!$template->getId()) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_HTML);
            }

            if ($request->getParam('_change_type_flag')) {
                $template->setTemplateType(TemplateTypesInterface::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            $template->save();
            $this->_session->setFormData(false);
            $this->messageManager->addSuccessMessage(__('You saved the email template.'));
            $this->_redirect('adminhtml/*');
        } catch (\Exception $e) {
            $this->_session->setData(
                'email_template_form_data',
                $request->getParams()
            );
            $this->messageManager->addError($e->getMessage());
            $this->_forward('new');
        }
    }
}
