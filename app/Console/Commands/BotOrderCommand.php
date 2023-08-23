<?php

namespace App\Console\Commands;

use App\Http\Services\CoinPairService;
use App\Http\Services\MarketTradeService;
use App\Http\Services\PublicService;
use App\Model\CoinPair;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BotOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bot:trading';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Place buy sell order by using trading bot';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $userId;
    public $coinPairs;
    public function __construct()
    {
        parent::__construct();
        $parService = new CoinPairService();
        if(DB::connection()->getDatabaseName()) {
            if (Schema::hasTable('users')) {
                $user = User::where(['role' => USER_ROLE_ADMIN])->orderBy('id', 'asc')->first();
                if ($user) {
                    $this->userId = $user->id;
                }
            }
        }

        $this->coinPairs = $parService->getAllCoinPairsData()['data'];
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        if(allsetting('enable_bot_trade') == STATUS_ACTIVE) {
//            storeBotException('BotOrderCommand running', date('Y-m-d H:i:s'));
            $requestData['user_id'] = $this->userId;
            $coinPairs = $this->coinPairs;
            if (isset($coinPairs[0])) {
                foreach ($coinPairs as $pair) {
                    if ($pair['bot_trading'] == STATUS_ACTIVE) {
                        $requestData['pair_id'] = $pair['id'];
                        $requestData['coin_pair_coin'] = $pair['coin_pair_coin'];
                        if ($pair['is_token'] == STATUS_PENDING) {
                            if ($pair['bot_possible'] == STATUS_ACTIVE) {
                                try {
                                    $callApi = getPriceFromApi($pair['pair_bin']);
                                    if ($callApi['success'] == true) {
                                        updateAdminWalletBalance($this->userId,$pair['parent_coin_id'],$pair['child_coin_id']);
                                        $sellData = $callApi['data']['sellData'];
                                        $buyData = $callApi['data']['buyData'];
//                                storeException('ask data', json_encode($sellData));
//                                storeException('bids data', json_encode($buyData));
                                        $this->createBuyOrder($requestData,$pair,$buyData);
                                        $this->createSellOrder($requestData,$pair,$sellData);
                                    } else {
                                        storeBotException('callAskBidApi '.$pair['pair_bin'],'api has no data');
                                    }
                                } catch (\Exception $e) {
                                    storeBotException('callAskBidApi',$e->getMessage());
                                }
                            }
                        } else {
//                            updateAdminWalletBalance($this->userId,$pair['parent_coin_id'],$pair['child_coin_id']);
//                            $this->placeTokenOrder($requestData,$pair);
                        }
                    }
                }
            }
        }
    }

    // place token order
    public function placeTokenOrder($requestData,$pair)
    {
        try {
            $publicService = new PublicService();
            $req =[
                'base_coin_id' => $pair['parent_coin_id'],
                'trade_coin_id' => $pair['child_coin_id'],
                'order_type' => 'buy_sell',
                'dashboard_type' => 'dashboard',
                'per_page' => 10
            ];
            $request = new Request($req);
            $orderBook = $publicService->getOrderdata($request);
            $aa = $this->makeBuySellData($orderBook['sells'],$orderBook['buys'],$pair);
            $sellData = $aa['sells'];
            $buyData = $aa['buys'];

            $this->createBuyOrder($requestData,$pair,$sellData);
            $this->createSellOrder($requestData,$pair,$buyData);
        } catch (\Exception $e) {
            storeBotException('token bot ex -> ',$e->getMessage());
        }
    }

    public function makeBuySellData($ask,$bids,$pair)
    {
        if (isset($ask[0])) {
            $sellData = $ask;
        } else {
            $sellData = [];
            $amount = $this->getAmount($pair['last_price']);
            if ($pair['last_price'] == $pair['initial_price']) {
                $startPrice = $this->getPrice($pair['last_price'],'small');
            } else {
                $startPrice = $this->getPrice($pair['last_price'],'big');
            }
            $div = pow(10, 8);
            $price = custom_number_format(rand($pair['last_price'] * $div, $startPrice * $div) / $div);

            $sellData[]=[
                'price' => $price,
                'amount' => $amount
            ];
        }
        if (isset($bids[0])) {
            $buyData = $bids;
        } else {
            $buyData = [];
            $buyData[]=[
                'price' => $this->getPrice($pair['last_price'],'small'),
                'amount' => $this->getAmount($pair['last_price'])
            ];
        }
        $data = [
            'buys' => $buyData,
            'sells' => $sellData
        ];
        return $data;
    }

    // get token price
    public function getPrice($lastPrice,$type)
    {
        $div = pow(10, 8);
        $settingTolerance = 0.01;
        $tolerancePrice = bcdiv(bcmul($lastPrice, $settingTolerance), "100");
        if ($type == 'big') {
            $tolerance = bcadd($lastPrice, $tolerancePrice);
            $price = custom_number_format(rand($lastPrice * $div, $tolerance * $div) / $div);
        } else {
            $tolerance = bcsub($lastPrice, $tolerancePrice);
            $price = custom_number_format(rand($tolerance * $div, $lastPrice * $div) / $div);
        }
        return $price;
    }

    public function getAmount($price)
    {
        $div = pow(10, 8);
        if ($price >= 1) {
            $amount = custom_number_format(rand(0.0000001 * $div, 0.00001 * $div) / $div);
        } else {
            $amount = custom_number_format(rand(0.1 * $div, 1 * $div) / $div);
        }
        return $amount;
    }
    // buy order create
    public function createBuyOrder($requestData,$pair,$sellData)
    {
//        storeBotException('createBuyOrder running', date('Y-m-d H:i:s'));
        $request = new Request($requestData);
        $service = new MarketTradeService();
        $request->merge(['bot_order_type' => 'buy']);
        $service->makeMarketOrder($request,$pair,$sellData);
        //dispatch(new MarketBotBuyOrderJob($requestData,$pair))->onQueue('market-bot');
    }

    // sell order create
    public function createSellOrder($requestData,$pair,$buyData)
    {
//        storeBotException('createBuyOrder running', date('Y-m-d H:i:s'));
        $request = new Request($requestData);
        $service = new MarketTradeService();
        $request->merge(['bot_order_type' => 'sell']);
        $service->makeMarketOrder($request,$pair,$buyData);
        //dispatch(new MarketBotBuyOrderJob($requestData,$pair))->onQueue('market-bot');
    }
}
