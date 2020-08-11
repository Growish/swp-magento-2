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
    public function __construct(Context $context, Session $checkoutSession, RequestInterface $request, \Magento\Store\Model\StoreManagerInterface $storeManager, OrderFactory $orderFactory, ScopeConfigInterface $scopeConfig, Http $response, TransactionBuilder $tb, \Brightmansukhsoft\Scuolapayment\Helper\Data $helperData, \Magento\Checkout\Model\Cart $cart, \Magento\AdminNotification\Model\Inbox $inbox, \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository, StockRegistryInterface $stockRegistry, \Magento\Sales\Model\Service\InvoiceService $invoiceService)
    {
        $this->_storeManager         = $storeManager;
        $this->checkoutSession       = $checkoutSession;
        $this->orderFactory          = $orderFactory;
        $this->response              = $response;
        $this->scopeConfig           = $scopeConfig;
        $this->transactionBuilder    = $tb;
        $this->cart                  = $cart;
        $this->inbox                 = $inbox;
        $this->helper                = $helperData;
        $this->transactionRepository = $transactionRepository;
        $this->urlBuilder            = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\UrlInterface');
        $this->stockRegistry         = $stockRegistry;
        $this->_invoiceService       = $invoiceService;
        $this->request               = $request;
        parent::__construct($context);
    }
    public function execute()
    {
        $sessionId   = $this->request->getParam('sessionId');
        $helper      = $this->helper;
        $environment = $helper->getConfig('payment/scuolapayment/environment');
        if ($environment == 'Production') {
            $scuolaPayreponseurl = 'https://webpayments-api.scuolapay.it/session/' . $sessionId;
        } else {
            $scuolaPayreponseurl = 'https://webpayments-api-dev.scuolapay.it/session/' . $sessionId;
        }
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );
        $json              = file_get_contents($scuolaPayreponseurl, false, stream_context_create($arrContextOptions));
        $obj               = json_decode($json, true);
        $status            = $obj['status'];
        $amt               = $obj['amount'];
        $orderId           = $obj['orderId'];
        if ($sessionId) {
            $isnotify      = true;
            $orderid       = $orderId;
            $order         = $this->orderFactory->create()->loadByIncrementId($orderid);
            $orderEmail    = $order->getCustomerEmail();
            $order_status  = $order->getStatus();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $storeManager  = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
            $store         = $storeManager->getStore();
            $baseUrl       = $store->getBaseUrl();            
            if (preg_match("/^$order_status/i", Order::STATE_PENDING_PAYMENT)) {
                $approval_code = $status;
                if ($approval_code == '1') {
                    $order->setStatus(Order::STATE_PROCESSING, true);
                    $order->setTotalDue(0);
                    $order->save();
                    $order->setCustomerEmail($orderEmail);
                    $objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
                    header("Location:" . $baseUrl . 'checkout/onepage/success', true, 301);
                    exit;
                } else if ($approval_code == '0') {
                    $order->setState(Order::STATE_CANCELED, true);
                    $this->updateInventory($orderid);
                    $order->cancel()->save();
                    header("Location:" . $baseUrl . 'checkout/onepage/failure', true, 301);
                    exit;
                } else {
                    $order->setState(Order::STATE_CANCELED, true);
                    $this->updateInventory($orderid);
                    $order->cancel()->save();
                    header("Location:" . $baseUrl . 'checkout/onepage/failure', true, 301);
                    exit;
                }
            } else {
                echo 'Already ScuolaPay response updated for order';
            }
        } else {
            $order->setState(Order::STATE_CANCELED, true);
            $this->updateInventory($orderid);
            $order->cancel()->save();
            header("Location:" . $baseUrl . 'checkout/onepage/failure', true, 301);
            exit;
        }
    }
    public function updateInventory($orderId)
    {
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        $items = $order->getAllItems();
        foreach ($items as $itemId => $item) {
            $ordered_quantity = $item->getQtyToInvoice();
            $sku              = $item->getSku();
            $stockItem        = $this->stockRegistry->getStockItemBySku($sku);
            $stockItem->setQtyCorrection($ordered_quantity);
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
        }
    }
}