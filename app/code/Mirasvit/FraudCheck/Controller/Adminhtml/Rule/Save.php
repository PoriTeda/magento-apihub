<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Controller\Adminhtml\Rule;

use Mirasvit\FraudCheck\Controller\Adminhtml\Rule;

class Save extends Rule
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data = $this->getRequest()->getParams()) {
            $model = $this->initModel();

            $data['data']['send_email_to'] = str_replace(' ', '', $data['data']['send_email_to']);

            if (!empty($data['data']['send_email_to'])) {
                foreach (explode(';', $data['data']['send_email_to']) as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && isset($data['data']['rule_id'])) {
                        $this->messageManager->addErrorMessage('Please check email format or separated by a semicolon ";"');
                        return $resultRedirect->setPath('*/*/edit', ['id' => $data['data']['rule_id']]);
                    }
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !isset($data['data']['rule_id'])) {
                        $this->messageManager->addErrorMessage('Please check email format or separated by a semicolon ";"');
                        return $resultRedirect->setPath('*/*/new');
                    }
                }
            }

            $model->addData($data['data']);

            if (isset($data['rule'])) {
                $model->loadPost($data['rule']);
            }

            try {
                $model->save();
                $this->messageManager->addSuccess(__('Item was successfully saved'));

                if ($this->getRequest()->getParam('back') == 'edit') {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
            }
        } else {
            $this->messageManager->addError(__('Unable to find item to save'));

            return $resultRedirect->setPath('*/*/');
        }
    }
}
