<?php
/**
 * Copyright � 2020 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Brightmansukhsoft\Scuolapayment\Model\Adminhtml\Source;

/**
 * Class PaymentAction
 */
class Environment implements \Magento\Framework\Option\ArrayInterface {

    /**
     * {@inheritdoc}
     */
    public function toOptionArray() {

        return array(
            array('value' => 'Integration', 'label' => 'Sandbox'),
            array('value' => 'Production', 'label' => 'Production'),
        );
    }

}