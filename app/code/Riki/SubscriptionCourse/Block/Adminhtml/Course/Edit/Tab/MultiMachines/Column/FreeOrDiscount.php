<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\MultiMachines\Column;

class FreeOrDiscount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Riki\MachineApi\Model\B2cMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Riki\MachineApi\Model\B2cMachineSkusFactory $machineTypeFactory,
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Riki\MachineApi\Model\B2cMachineSkusFactory $machineTypeFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->machineTypeFactory = $machineTypeFactory;
        $this->request = $context->getRequest();
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData('entity_id');
        $typeId = (int)$this->request->getParam('type_id');
        $machine = $this->machineTypeFactory->create()->getMachine($typeId, $productId);
        $isFree = $machine && array_key_exists('is_free', $machine) ? $machine['is_free'] : 0;

        $data = [
            ['value' => 1, 'text' => __('Free')],
            ['value' => 0, 'text' => __('Discount')]
        ];

        $html = $this->getOptions($productId, $data, $isFree);

        return $html;
    }

    protected function getOptions($id, $data, $active)
    {
        $html = '<select name="product_machine" class="input-text admin__control-text is-free-select">';

        foreach ($data as $item) {
            $isSelected = $item['value'] == $active ? 'selected' : '';
            $html .= '<option '.$isSelected.' value="'.$item['value'].'" >'.$item['text'].'</option>';
        }

        $html .= '</select>';

        return $html;
    }
}
