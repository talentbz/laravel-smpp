<?php

namespace App\Services\Smpp;
use GsmEncoder;
use Illuminate\Support\Facades\Log;
use SMPP;
use SmppAddress;
use SmppClient;
use SocketTransport;

class SmppTransmitter
{
   protected $transport, $client, $credentialTransmitter;

   public function __construct()
   {
       $this->connect();
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

    // Bind transmitter
    $this->client->bindTransmitter(config('smpp.smpp_transmitter_id'), config('smpp.smpp_transmitter_password'));
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

   public function keepAlive() {
    $this->client->enquireLink();
    $this->client->respondEnquireLink();
   }

   public function respond() {}

   public function sendSms($message, $from, $to) {
        // Check if all parameters present
    if (!isset($message) || !isset($from) || !isset($to)) {
        // Handle missing parameters
        }

        // Encode parameters
        $encodedMessage = GsmEncoder::utf8_to_gsm0338($message);
        $fromAddress = new SmppAddress($from, SMPP::TON_ALPHANUMERIC);
        $toAddress = new SmppAddress($to, SMPP::TON_INTERNATIONAL, SMPP::NPI_E164);

        // Try to send message and catch exception
        try {
            $this->client->sendSMS($fromAddress, $toAddress, $encodedMessage);
            return;
        } catch (\Exception $e) {
            Log::error($e);
            Log::error($e->getMessage());
            // Handle failed message send
        }
   }
}