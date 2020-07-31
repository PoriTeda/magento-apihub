<?php

namespace Nestle\Catalog\Plugin\Form\Edit\Button;

use Riki\Catalog\Plugin\Catalog\Block\Adminhtml\Product\Edit;

class Save
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    private $authorization;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Edit constructor.
     * @param \Magento\Framework\AuthorizationInterface $authorization
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->authorization = $authorization;
        $this->request = $request;
    }

    public function afterGetButtonData($subject, $result)
    {
        if ($this->request->getActionName() == Edit::CREATE_PROUDUCT_ACTION) {
            if (!$this->authorization->isAllowed('Magento_Catalog::actions_create')) {
                return [];
            }
        }

        if (!$this->authorization->isAllowed('Magento_Catalog::actions_edit')) {
            return [];
        } else {
            $options = $result['options'];

            if (!$this->authorization->isAllowed('Magento_Catalog::actions_create') && is_array($options)) {
                foreach ($options as $index => $option) {
                    if (in_array($option['id_hard'], ['save_and_new', 'save_and_duplicate'])) {
                        unset($options[$index]);
                    }
                }
                $result['options'] = $options;
            }
        }

        return $result;

    }
}