<?php

namespace Riki\Rma\Controller\Adminhtml\Reason;

use Magento\Framework\Exception\LocalizedException;

class Save extends \Riki\Rma\Controller\Adminhtml\Reason
{
    const SAP_CODE_DEFAULT = 'CD';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $result = $this->initRedirectResult();
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $result->setUrl($this->getUrl('*/reason'));
            return $result;
        }

        $result->setUrl($this->getUrl('*/reason/new'));
        try {
            $post = $request->getPostValue();
            if (isset($post['id']) && $post['id']) {
                $model = $this->reasonRepository->getById($post['id']);
            } elseif (isset($post['code']) && $post['code']) {
                $model = $this->searchHelper
                    ->getByCode($post['code'])
                    ->getOne()
                    ->execute($this->reasonRepository);
                if (!$model) {
                    $model = $this->reasonRepository->createFromArray();
                }
            } else {
                $model = $this->reasonRepository->createFromArray();
            }
            $model->addData(array_merge([
                'deleted' => 0
            ], $post));

            if (empty($post['sap_code'])) {
                $model->addData(['sap_code' => self::SAP_CODE_DEFAULT], $post);
            }

            $this->reasonRepository->save($model);

            $this->messageManager->addSuccess(__('The rma reason has been saved.'));
            $this->_getSession()->setFormData(false);

            if ($request->getParam('back') == 'edit') {
                $result->setUrl($this->getUrl('*/reason/edit', ['id' => $model->getId()]));
            }else{
                $result->setUrl($this->getUrl('*/*'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError(nl2br($e->getMessage()));
            $this->_getSession()->setData('riki_rma_reason_form_data', $request->getParams());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred when processing, please try again!'));
            $this->_getSession()->setData('riki_rma_reason_form_data', $request->getParams());
        }


        return $result;
    }
}
