<?php
 
namespace Brightmansukhsoft\Scuolapayment\Model;
 
/**
 * Pay In Store payment method model
 */
class Paymentmethod extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'scuolapayment';
}