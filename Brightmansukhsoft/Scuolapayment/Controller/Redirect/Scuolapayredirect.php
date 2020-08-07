<?php
/**
 * Copyright � 2020 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Brightmansukhsoft\Scuolapayment\Controller\Redirect;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
class Scuolapayredirect extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    protected $_checkoutSession;
    protected $orderRepository;
    protected $customerSession;
    private $timezone;
    protected $_resource;
    protected $helper;
    protected $_coreRegistry = null;
    public function __construct(Context $context, PageFactory $pageFactory,
	\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
	\Brightmansukhsoft\Scuolapayment\Helper\Data $helperData,
	\Magento\Framework\App\ResourceConnection $resource,
	\Magento\Store\Model\StoreManagerInterface $storemanager,
	\Magento\Checkout\Model\Session $checkoutSession, 
	\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
	\Magento\Customer\Model\Session $customerSession, 
	\Magento\Framework\Registry $registry,
	\Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone) {
        $this->pageFactory = $pageFactory;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helperData;
        $this->_resource = $resource;
        $this->storemanager = $storemanager;
        $this->_checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
		$this->_coreRegistry     = $registry;

        parent::__construct($context);
    }
	public function getStoreCurrency() {
        $currency_code = $this->storemanager->getStore()->getCurrentCurrency()->getCode();
        $currency_array = array(
            'MYR' => '458','PLN' => '985','NOK' => '578','RUB' => '643','AED' => '784','CNY' => '156','KRW' => '410','ILS' => '376','SAR' => '682','TRY' => '949',
            'HKD' => '344','KWD' => '414','INR' => '356','RON' => '946','SGD' => '702','MXN' => '484','NZD' => '554','EEK' => '233','LTL' => '440','USD' => '840',
            'ZAR' => '710','CAD' => '124','JPY' => '392','SEK' => '752','CZK' => '203','DKK' => '208','EUR' => '978','GBP' => '826','CHF' => '756','HRK' => '191',
            'BHD' => '048','HUF' => '348','AUD' => '036','BRL' => '986','NGN' => '566','QAR' => '634','THB' => '764','BND' => '096','MAD' => '504','TND' => '788',
            'BIF' => '108','BYN' => '933','ARS' => '032','CLP' => '152','BDT' => '050','KES' => '404','MUR' => '480','NPR' => '524','BYR' => '974','MDL' => '498',
            'BOB' => '068','PYG' => '600','NAD' => '516','DZD' => '012','BBD' => '052','OMR' => '512','BSD' => '044','BZD' => '084','KYD' => '136','DOP' => '214',
            'GYD' => '328','JMD' => '388','ANG' => '532','AWG' => '533','TTD' => '780','XCD' => '951','SRD' => '968','UGX' => '800','MMK' => '104','UYU' => '858',
            'COP' => '170','EGP' => '818','FJD' => '242','IDR' => '360','IQD' => '368','IRR' => '364','ISK' => '352','LAK' => '418','LKR' => '144','MOP' => '446',
            'PHP' => '608','PKR' => '586','SCR' => '690','TWD' => '901','VND' => '704','PEN' => '604','RSD' => '941','KZT' => '398','BWP' => '072','BGN' => '975',
            'AZN' => '944','UAH' => '980','LBP' => '422','TZS' => '834','BMD' => '060','JOD' => '400','AFN' => '971','CRC' => '188','XOF' => '952','GTQ' => '320',
        );

        return isset($currency_array[$currency_code]) ? $currency_array[$currency_code] : "";
    }
	public function execute()
    {
		$resultPage = $this->pageFactory->create();
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        // Order details
       //$orderNoData=$this->getOrderno();
       //$orderId=$orderNoData[0]['entity_id'];
	   $orderId = $this->_checkoutSession->getLastRealOrder()->getId();
	 	if($orderId){
	 	      $orderNo =$this->_checkoutSession->getLastRealOrder()->getIncrementId();
              $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($orderId);
              $total_amt = number_format($order->getGrandTotal(), 2, '.', '');
              $amt=$total_amt*100;
              $total_amt= number_format($amt, 2,'.', '');
              
	 	    /* Api Keys Secret, Bussiness Key, Url */
	 	    
	 	   $helper = $this->helper;
            $environment=$helper->getConfig('payment/scuolapayment/environment');
            if($environment=='Production'){
                  $redirectUrl='https://webpayments-api.scuolapay.it';
                  $webhook=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/production_webhook');
                  $secret=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/production_secret');
                  $business=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/production_bussiness');
                  
            } else {
                    $redirectUrl='https://webpayments-api-dev.scuolapay.it';
                    $webhook=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_webhook');
                    $secret=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_secret');
                    $business=$helper->getConfig('payment/scuolapaymentsection/scuolapayment/integrate_bussiness');
            }
             // $secret = "thisisaverybadsecret";
              $responseUrl= $this->storemanager->getStore()->getBaseUrl().'scuolapay/response';
              if($webhook){
	          $orderData=array(
	                               'secret'=> $secret,
	                               'business'=> $business,
	                               'redirectUrl'=> $redirectUrl,
	                               'amount' =>$total_amt,
	                               'orderId' =>  $orderNo,
	                               'responseURL' => $responseUrl,
                                   'webhookURL' => $webhook
	                            ); 
              } else {
                                    	          $orderData=array(
	                               'secret'=> $secret,
	                               'business'=> $business,
	                               'redirectUrl'=> $redirectUrl,
	                               'amount' =>$total_amt,
	                               'orderId' =>  $orderNo,
	                               'responseURL' => $responseUrl,
                                   'webhookURL' => ''
	                            );
              }
	     	  $this->_coreRegistry->register('orderdata', $orderData);
		       $page = $this->pageFactory->create();
             return $page;
        } else {
            die('.......');
            $this->getResponse()->setRedirect($this->getUrl());
       }
     
	}
}