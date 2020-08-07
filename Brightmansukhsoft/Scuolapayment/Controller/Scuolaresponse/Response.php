<?php

namespace Brightmansukhsoft\Scuolapayment\Controller\Scuolaresponse;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use \Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\RequestInterface;
//use Magento\Framework\Controller\ResultFactory; 

class Response extends \Magento\Framework\App\Action\Action

{
    protected $_objectmanager;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $urlBuilder;
    private $logger;
    protected $response;
    protected $config;
    protected $messageManager;
    protected $transactionRepository;
    protected $cart;
    protected $inbox;
    protected $stockRegistry;
    protected $savetoken;
    protected $_invoiceService;
    protected $helper;
    protected $_storeManager;
    public function __construct(Context $context,
        Session $checkoutSession,RequestInterface $request, \Magento\Store\Model\StoreManagerInterface $storeManager, OrderFactory $orderFactory, ScopeConfigInterface $scopeConfig, Http $response, TransactionBuilder $tb,\Brightmansukhsoft\Scuolapayment\Helper\Data $helperData, \Magento\Checkout\Model\Cart $cart, \Magento\AdminNotification\Model\Inbox $inbox, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, StockRegistryInterface $stockRegistry,\Magento\Sales\Model\Service\InvoiceService $invoiceService) {
        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->scopeConfig = $scopeConfig;
        $this->transactionBuilder = $tb;
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->helper = $helperData;
        $this->transactionRepository = $transactionRepository;
        $this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Framework\UrlInterface');
        $this->stockRegistry = $stockRegistry;
        $this->_invoiceService = $invoiceService;
        $this->request = $request;

        parent::__construct($context);
    }
	public function execute()

    {

       $sessionId=$this->request->getParam('sessionId');
       $helper = $this->helper;
       $environment=$helper->getConfig('payment/scuolapayment/environment');
       if($environment=='Production'){
         $scuolaPayreponseurl='https://webpayments-api.scuolapay.it/session/'.$sessionId;  
        } else {
         $scuolaPayreponseurl='https://webpayments-api-dev.scuolapay.it/session/'.$sessionId;       
        }
       $json = file_get_contents($scuolaPayreponseurl);
       $obj = json_decode($json,true);
        $status=$obj['status'];
        $amt=$obj['amount'];
        $orderId=$obj['orderId'];
       
        if ($sessionId) {
            $isnotify = true;
            // Order ID
            $orderid =$orderId;
            # get order object
            $order = $this->orderFactory->create()->loadByIncrementId($orderid);
			$orderEmail=$order->getCustomerEmail();
            $order_status = $order->getStatus(); 
            
            if (preg_match("/^$order_status/i", Order::STATE_PENDING_PAYMENT)){
                if ($sessionId) {
                    $approval_code = $status;
                    if ($approval_code == '1') {
                        // Needed response values                    
                        //$amount = $response['chargetotal'];
                        $order->setStatus(Order::STATE_PROCESSING, true);
                        //$order->setTotalPaid($amount);
                        $order->setTotalDue(0);
                        $order->save();
                        //Store details based on IPG response
                        //$this->storeData($response);
						$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
						$order->setCustomerEmail($orderEmail);
						$objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
						//die('success1');
                        //$this->_redirect($this->urlBuilder->getUrl('checkout/onepage/success', array('_secure' => true)));
                       // $resultRedirect->setUrl('checkout/onepage/success');
                             $baseUrl= $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                       //$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                      // $this->messageManager->addSuccess($result['message']);
				    //	$this->checkoutSession->clearQuote();
                      // $this->_redirect('checkout/onepage/success');
                       header("Location:".$baseUrl.'checkout/onepage/success');
                    } else if ($approval_code == '0') {
                        $order->setState(Order::STATE_CANCELED, true);
                        // Inventory updated 
                        $this->updateInventory($orderid);
                        $order->cancel()->save();
                        //die('fail');
                       // $this->checkoutSession->clearQuote();
                        header("Location:".$baseUrl.'checkout/onepage/failure');
                        //$resultRedirect->setUrl('checkout/onepage/failure');
                        //return $resultRedirect;
                        //$this->_redirect($this->urlBuilder->getUrl('checkout/onepage/failure', array('_secure' => true)));
                    } else {
                        $order->setState(Order::STATE_CANCELED, true);
                        // Inventory updated                 
                        $this->updateInventory($orderid);
                        $order->cancel()->save();
						//die('fail');
									//		$this->checkoutSession->clearQuote();
						 header("Location:".$baseUrl.'checkout/onepage/failure');
						//$resultRedirect->setUrl('checkout/onepage/failure');
						 //return $resultRedirect;
                        //$this->_redirect($this->urlBuilder->getUrl('checkout/onepage/failure', array('_secure' => true)));
                    }
                } else {
                    
                    $order->setState(Order::STATE_CANCELED, true);
                    // Inventory updated 
                    $this->updateInventory($orderid);
                    $order->cancel()->save();
                   // $this->checkoutSession->clearQuote();
					header("Location:".$baseUrl.'checkout/onepage/failure');
					//$resultRedirect->setUrl('checkout/onepage/failure');
				    //return $resultRedirect;
                   //$this->_redirect($this->urlBuilder->getUrl('checkout/onepage/failure', array('_secure' => true)));
                }
            } else{
				die('Already ScuolaPay response updated for order');
            }
        }
       

     

	}
	 public function getCustomerId() {
        //return current customer ID
        return $this->_checkoutSession->getId();
    }

    public function updateInventory($orderId) {

        # get order object
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $items = $order->getAllItems();
        foreach ($items as $itemId => $item) {
            $ordered_quantity = $item->getQtyToInvoice();
            $sku = $item->getSku();
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);
            //$qtyStock = $stockItem->getQty();
            //$this->logger->info("sku:".$sku.", qtyStock: ".$qtyStock.", ordered_quantity: ".$ordered_quantity);
            //$updated_inventory = $qtyStock + $ordered_quantity;
            $stockItem->setQtyCorrection($ordered_quantity);
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
        }		
    }

}