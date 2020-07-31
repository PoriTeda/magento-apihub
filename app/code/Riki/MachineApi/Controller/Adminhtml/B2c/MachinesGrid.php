<?php
namespace Riki\MachineApi\Controller\Adminhtml\B2c;

class MachinesGrid extends Machines
{
    public function execute()
    {
        $resultLayout = $this->resultLayoutFactory->create();
        $listMachines = $this->getRequest()->getPost('machine_type_product', null);
        $resultLayout->getLayout()->getBlock('machine.b2c.edit.tab.machines')
            ->setMachineTypeMachines($listMachines);
        return $resultLayout;
    }
}
