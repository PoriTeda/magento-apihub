<?php
namespace Riki\Rma\Block\Adminhtml\Button;

class Delete extends Generic
{
    /**
     * {@inheritdoc}
     *
     * @return bool|mixed
     */
    public function canRender()
    {
        $model = $this->getModel();
        if (!$model || !$model->getId()) {
            return false;
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     */
    public function getData()
    {
        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __(
                    'Are you sure you want to do this?'
                ) . '\', \'' . $this->getDeleteUrl() . '\')',
            'sort_order' => 20
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        $model = $this->canRender();
        if (!$model) {
            return 'javascript:void(0)';
        }

        return $this->getUrl('*/*/delete', [
            self::REQUEST_ID_KEY => $model->getId()
        ]);
    }
}