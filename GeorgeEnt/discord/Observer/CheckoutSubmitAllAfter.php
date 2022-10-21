<?php
    namespace GeorgeEnt\discord\Observer;

    use Magento\Framework\Event\Observer as EventObserver;
    use Magento\Framework\Event\ObserverInterface;
    use Psr\Log\LoggerInterface;

    class CheckoutSubmitAllAfter implements ObserverInterface
    {
        protected $helper;

        public function __construct(
            \GeorgeEnt\discord\Helper\Data $helper,
            array $data = []
        ) {
            $this->helper = $helper;
        }
        public function execute(EventObserver $observer)
        {
            $order = $observer->getOrder();
            $data = $order->getData();
            $storeName = $order->getStoreName();

            $customeremail = $order->getCustomerEmail();

            $grandtotal = $order->getGrandTotal();
            $grandtotalcurrency = $order->formatPriceTxt($grandtotal);
            $customername = $order->getCustomerName();

            $orderID = $order->getRealOrderId();

            $webhookurl = $this->helper->getConfig('discord/discord_hook/disc_auth_url');

            $timestamp = date("c", strtotime("now"));

            $json_data = json_encode([
                "content" => "",
                "username" => "Magento",
                "tts" => false,

                // Embeds Array
                "embeds" => [
                    [
                        // Embed Title
                        "title" => "New Magento Order (ID:". $orderID . ")",

                        // Embed Type
                        "type" => "rich",

                        // URL of title link
                        "description" => "A new order has been placed in the store.",

                        // Timestamp of embed must be formatted as ISO8601
                        "timestamp" => $timestamp,

                        // Embed left border color in HEX
                        "color" => hexdec( "0xe76a2b" ),

                        // Thumbnail
                        "thumbnail" => [
                            "url" => "https://connect.adfab.fr/wp-content/uploads/2016/12/magento-logo1.png",
                            "width" => 0,
                            "height" => 0
                        ],

                        // Footer
                        "footer" => [
                            "text" => "Magento Report to Discord - github.com/georgesgithubaccount/M2-Report-to-Discord",
                        ],

                        "fields" => [
                            // Field 1
                            [
                                "name" => "Customer Email",
                                "value" => $customeremail,
                                "inline" => true
                            ],
                            [
                                "name" => "Customer Name",
                                "value" => $customername,
                                "inline" => true
                            ],
                            [
                                "name" => "Order Details",
                                "value" => "Grand Total: " . $grandtotalcurrency . "\nOrder Status: " . $order->getStatus(),
                                "inline" => false
                            ]
                        ]
                    ]
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

            $ch = curl_init( $webhookurl );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
            curl_setopt( $ch, CURLOPT_POST, 1);
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt( $ch, CURLOPT_HEADER, 0);
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

            $response = curl_exec( $ch );

            curl_close( $ch );

            return $this;
        }
    }
