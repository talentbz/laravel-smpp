<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StartSmpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smpp:start-receiver';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start SMPP Receiver';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $receiver = new \App\Services\Smpp\SmppReceiver();
        $receiver->start();
    }
}
