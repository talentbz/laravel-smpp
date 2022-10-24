<?php

namespace App\Services\Smpp;
use SmppClient;
use SmppDeliveryReceipt;
use SocketTransport;

class SmppReceiver
{
   protected $transport, $client, $transmitter;

   public function start() {
    $this->connect();
    $this->readSms();
   }
   protected function connect() {
        // Create transport
        $this->transport = new SocketTransport([config('smpp.smpp_service')], config('smpp.smpp_port'));
        $this->transport->setRecvTimeout(30000);
        $this->transport->setSendTimeout(30000);

        // Create client
        $this->client = new SmppClient($this->transport);

        // Activate binary hex-output of server interaction
        $this->client->debug = true;
        $this->transport->debug = true;

        // Open the connection
        $this->transport->open();

        // Bind receiver
        $this->client->bindReceiver(config('smpp.smpp_receiver_id'), config('smpp.smpp_receiver_id'));
   }
   protected function disconnect() {
    if (isset($this->transport) && $this->transport->isOpen()) {
        if (isset($this->client)) {
            try {
               $this->client->close();
            } catch (\Exception $e) {
               $this->transport->close();
            }
        } else {
            $this->transport->close();
        }
     }
   }
   protected function keepAlive() {
    $this->client->enquireLink();
    $this->client->respondEnquireLink();
   }
   protected function readSms() {
    $time_start = microtime(true);
    $endtime = $time_start + 120;    //  2 m
    $lastTime = 0;

    do {
        $res = $this->client->readSMS();
        if ($res) {
            try {
                if ($res instanceof SmppDeliveryReceipt) {
                    // If enabled sms provider will send us a delivery report of the sms we sent to client with its status
                    /**0    SCHEDULED
                         1    ENROUTE
                        2    DELIVERED
                        3    EXPIRED
                        4    DELETED
                        5    UNDELIVERABLE
                        6    ACCEPTED
                        7    UNKNOWN
                        8    REJECTED
                        9    SKIPPED
                        */

                } else {
                    $from = $res->source->value;     // Number from which the number was sent
                    $to = $res->destination->value;  // Receiving number
                    $message = $res->message;        // Content of the message

                    dd($from, $message, $to);
                }

            } catch (\Exception $e) {
                // Something went wrong while reading message
                Log::error($e);
            }
        // }

        // // Keep connection alive every 30 secondsif (time()-$lastTime >= 30) {$this->keepAlive();
        //     $lastTime = time();
        } else {
            $this->client->respondEnquireLink();
        }
    } while ($endtime > microtime(true));
   }
}