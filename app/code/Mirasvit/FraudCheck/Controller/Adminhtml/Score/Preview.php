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


namespace Mirasvit\FraudCheck\Controller\Adminhtml\Score;

use Mirasvit\FraudCheck\Controller\Adminhtml\Score;

class Preview extends Score
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $html = '';

        if ($post = $this->getRequest()->getPostValue('data')) {
            parse_str(urldecode($post), $data);

            $model = $this->initModel();
            $model->setData($data);

            /** @var \Mirasvit\FraudCheck\Block\Adminhtml\Score\Preview $block */
            $block = $this->context->getView()->getLayout()
                ->createBlock('Mirasvit\FraudCheck\Block\Adminhtml\Score\Preview');

            $html = $block->setScore($model)
                ->toHtml();
        }

        /** @var \Magento\Framework\App\Response\Http\Interceptor $response */
        $response = $this->getResponse();

        return $response
            ->setBody($html);
    }

    /**
     * @return bool
     */
    public function _processUrlKeys()
    {
        return true;
    }
}
