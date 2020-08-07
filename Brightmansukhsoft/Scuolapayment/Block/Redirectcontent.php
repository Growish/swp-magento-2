<?php 
namespace Brightmansukhsoft\Scuolapayment\Block; 
use Magento\Framework\View\Element\Template\Context;
class Redirectcontent extends \Magento\Framework\View\Element\Template
{
    protected $_registry;
    public function __construct( \Magento\Framework\View\Element\Template\Context $context, 
    \Magento\Framework\Registry $registry,
    array $data = array())
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }  
    public function getOrderData()
  {
        return $this->_coreRegistry->registry('orderdata');

  }
   public function getCacheLifetime()
    {
        return null;
    }

}