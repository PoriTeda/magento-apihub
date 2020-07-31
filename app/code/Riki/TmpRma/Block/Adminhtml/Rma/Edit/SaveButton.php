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
namespace Riki\TmpRma\Block\Adminhtml\Rma\Edit;

/**
 * Class SaveButton
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Block
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class SaveButton extends DeleteButton
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if (
            $this->authorization->isAllowed('Riki_TmpRma::rma_actions_create') ||
            $this->authorization->isAllowed('Riki_TmpRma::rma_actions_edit')
        ) {
            $data = [
                'label' => __('Save Return'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save']],
                    'form-role' => 'save',
                ],
                'sort_order' => 90,
            ];
        }

        return $data;
    }
}
