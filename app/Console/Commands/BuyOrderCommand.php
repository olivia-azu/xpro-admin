<?php

namespace App\Console\Commands;

use App\Http\Services\MarketTradeService;
use App\Jobs\MarketBotBuyOrderJob;
use App\Model\CoinPair;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class BuyOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buy:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'place buy order with coin pair';

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
        if(allsetting('enable_bot_trade') == STATUS_ACTIVE) {
            $admin = User::where(['role' => USER_ROLE_ADMIN])->first();
            $requestData['user_id'] = $admin->id;
            $coinPairs = CoinPair::where(['status' => STATUS_ACTIVE, 'bot_trading' => STATUS_ACTIVE, 'bot_trading_buy' => STATUS_ACTIVE])->get();
            if (isset($coinPairs[0])) {
                foreach ($coinPairs as $pair) {
                    $requestData['pair_id'] = $pair->id;
                    $requestData['bot_order_type'] = 'buy';
                    $request = new Request($requestData);
                    $service = new MarketTradeService();
                    $service->makeMarketOrder($request,$pair);
//                    dispatch(new MarketBotBuyOrderJob($requestData,$pair))->onQueue('market-bot');
                }
            }
        } else {
            storeBotException('BuyOrderCommand deactive', date("Y-m-d H:i:s"));
        }
    }


//    public function handle()
//    {
//        if(allsetting('enable_bot_trade') == STATUS_ACTIVE) {
////            storeException('BuyOrderCommand active', date("Y-m-d H:i:s"));
//            $buyInterval = settings('trading_bot_buy_interval') ?? 20;
//            $buyInterval = intval($buyInterval);
//            $admin = User::where(['role' => USER_ROLE_ADMIN])->first();
//            $requestData['user_id'] = $admin->id;
//            if ($buyInterval >= 60) {
//                $interval = 1;
//            } else {
//                $interval = intval(60 / $buyInterval);
//            }
//            for ($i = 1; $i <= $interval; $i++) {
//                $coinPairs = CoinPair::where(['status' => STATUS_ACTIVE, 'bot_trading' => STATUS_ACTIVE, 'bot_trading_buy' => STATUS_ACTIVE])->get();
//                if (isset($coinPairs[0])) {
//                    foreach ($coinPairs as $pair) {
//                        $requestData['pair_id'] = $pair->id;
//                        $requestData['bot_order_type'] = 'buy';
//                        dispatch(new MarketBotBuyOrderJob($requestData))->onQueue('market-bot');
////                        $request = new Request($requestData);
////                        $service = new MarketTradeService();
////                        $service->makeMarketOrder($request);
//                    }
//                }
//                sleep($buyInterval);
//            }
//        } else {
//            storeException('BuyOrderCommand deactive', date("Y-m-d H:i:s"));
//        }
//    }
}
