<?php
/**
 * Created by PhpStorm.
 * User: bacchu
 * Date: 1/25/22
 * Time: 5:19 PM
 */

namespace App\Http\Repositories;

use App\Http\Services\ERC20TokenApi;
use App\Jobs\AdminTokenReceiveJob;
use App\Model\AdminReceiveTokenTransactionHistory;
use App\Model\Coin;
use App\Model\DepositeTransaction;
use App\Model\EstimateGasFeesTransactionHistory;
use App\Model\Wallet;
use App\Model\WalletAddressHistory;
use App\User;
use Illuminate\Support\Facades\DB;
use VisualNumberFormat;

use function Amp\call;

class CustomTokenRepository
{

    public function depositCustomToken()
    {
        try {
            $bep20Tokens = Coin::join('coin_settings', 'coin_settings.coin_id', '=', 'coins.id')
                ->where(['coins.network' => BEP20_TOKEN])
                ->where(['coins.status' => STATUS_ACTIVE])
                ->get();
            if (isset($bep20Tokens[0])) {
                foreach ($bep20Tokens as $bep20Token){
                    $this->bep20TokenDeposit($bep20Token);
                }
            }
        } catch (\Exception $e) {
            storeException('depositCustomToken ex', $e->getMessage());
        }
    }
    public function depositCustomERC20Token()
    {
        storeBotException('depositCustomERC20Token st', 'start');
        try {
            $trc20Tokens = Coin::join('coin_settings', 'coin_settings.coin_id', '=', 'coins.id')
                ->where(['coins.network' => TRC20_TOKEN])
                ->where(['coins.status' => STATUS_ACTIVE])
                ->get();
            if (isset($trc20Tokens[0])) {
                foreach ($trc20Tokens as $trc20Token) {
                    $this->ecr20TokenDeposit($trc20Token);
                }
            }
            $erc20Tokens = Coin::join('coin_settings', 'coin_settings.coin_id', '=', 'coins.id')
                ->where(['coins.network' => ERC20_TOKEN])
                ->where(['coins.status' => STATUS_ACTIVE])
                ->get();
            if (isset($erc20Tokens[0])) {
                foreach($erc20Tokens as $erc20Token) {
                    $this->ecr20TokenDeposit($erc20Token);
                }
            }
        } catch (\Exception $e) {
            storeException('depositCustomERC20Token ex', $e->getMessage());
        }
    }

    public function ecr20TokenDeposit($coin)
    {
        try {
            storeBotException('ecr20TokenDeposit', 'called -> '. $coin->coin_type);
            $latestTransactions = $this->getLatestTransactionFromBlock($coin);
            storeBotException('$latestTransactions',$latestTransactions);
            if ($latestTransactions['success'] == true) {
                foreach($latestTransactions['data'] as $transaction) {
                    $this->updateCoinBlockNumber($coin->coin_type, $transaction->block_number, $transaction->block_timestamp);
                    storeBotException('depositCustomToken single transaction', json_encode($transaction));
                    $this->checkAddressAndDeposit($transaction->to_address,$transaction->tx_hash,$transaction->amount,$transaction->from_address);
                }
            } else {
               storeBotException('depositCustomToken', $latestTransactions['message']);
            }
            return $latestTransactions;
        } catch (\Exception $e) {
            storeException('ecr20TokenDeposit ex', $e->getMessage());
        }
    }

    public function bep20TokenDeposit($coin)
    {
        try {
            storeBotException('bep20TokenDeposit', 'called -> ' . $coin->coin_type);
            $latestTransactions = $this->getLatestTransactionFromBlock($coin);
            storeBotException('$latestTransactions',$latestTransactions);
            if ($latestTransactions['success'] == true) {
                foreach($latestTransactions['data'] as $transaction) {
                    storeBotException('bep20TokenDeposit single transaction', json_encode($transaction));
                    $checkDeposit = $this->checkAddressAndDeposit($transaction->to_address,$transaction->tx_hash,$transaction->amount,$transaction->from_address,$transaction->block_number, $transaction->block_timestamp);
                    if ($checkDeposit['success'] == true) {
//                        $deposit = $checkDeposit['data'];
//                        $userPk = get_wallet_personal_add($transaction->to_address,$checkDeposit['pk']);
//                        $checkGasFees = $this->checkEstimateGasFees($coin,$transaction->to_address,$transaction->amount);
//                        if($checkGasFees['success'] == true) {
//
//                            $estimateFees = $checkGasFees['data']->estimateGasFees;
//                            $gas = bcadd($estimateFees , (bcdiv(bcmul($estimateFees, 30,8),100,8)),8);
//                            storeException('Gas',$gas);
//
//                            $checkAddressBalance = $this->checkWalletAddressBalance($coin,$transaction->to_address);
//                            if ($checkAddressBalance['success'] == true) {
//                                $walletNetBalance = $checkAddressBalance['data']->net_balance;
//                                storeException('$walletNetBalance',$walletNetBalance);
//                                if ($walletNetBalance >= $gas) {
//                                    $estimateGas = 0;
//                                    storeException('$estimateGas 0 ',$estimateGas);
//                                } else {
//                                    $estimateGas = bcsub($gas, $walletNetBalance,8);
//                                    storeException('$estimateGas have ',$estimateGas);
//                                }
//                                if ($estimateGas > 0) {
//                                    storeException('sendFeesToUserAddress ',$estimateGas);
//                                    $sendFees = $this->sendFeesToUserAddress($coin,$transaction->to_address,$estimateGas,$deposit->receiver_wallet_id,$deposit->id);
//                                    if ($sendFees['success'] == true) {
//
//                                        storeException('depositCustomToken -> ', 'sendFeesToUserAddress success . the next process will held on getDepositBalanceFromUserJob');
//                                    } else {
//                                        storeException('depositCustomToken', 'send fees process failed');
//                                    }
//                                } else {
//                                    storeException('sendFeesToUserAddress ', 'no gas needed');
//                                    $checkAddressBalanceAgain2 = $this->checkWalletAddressBalance($coin,$transaction->to_address);
//                                    storeException('depositCustomToken  $checkAddressBalanceAgain2',$checkAddressBalanceAgain2);
//                                    if ($checkAddressBalanceAgain2['success'] == true) {
//                                        $receiveToken = $this->receiveTokenFromUserAddress($coin,$transaction->to_address, $transaction->amount, $userPk, $deposit->id);
//                                        if ($receiveToken['success'] == true) {
//                                            $this->updateUserWallet($deposit, $receiveToken['data']->hash);
//                                        } else {
//                                            storeException('depositCustomToken', 'token received process failed');
//                                        }
//                                    } else {
//                                        storeException('depositCustomToken', 'again 2 get balance failed');
//                                    }
//                                }
//                            } else {
//                                storeException('depositCustomToken', 'get balance failed');
//                            }
//                        } else {
//                            storeException('depositCustomToken', 'check gas fees calculate failed');
//                        }
                    }else {
                        storeBotException('trc20DepositFailed', $checkDeposit['message'] ?? __("Trc20 Deposit Failed") );
                    }
                }
            } else {
               storeBotException('depositCustomToken', $latestTransactions['message']);
            }
            return $latestTransactions;
        } catch (\Exception $e) {
            storeException('bep20TokenDeposit ex', $e->getMessage());
        }
    }
    // update wallet
    public function updateUserWallet($deposit,$hash)
    {
        try {
            DepositeTransaction::where(['id' => $deposit->id])
                ->update([
                    'status' => STATUS_SUCCESS,
//                    'transaction_id' => $hash
                ]);
            $userWallet = $deposit->receiverWallet;
            storeException('depositCustomToken', 'before update wallet balance => '. $userWallet->balance);
            $userWallet->increment('balance',$deposit->amount);
            storeException('depositCustomToken', 'after update wallet balance => '. $userWallet->balance);
            storeException('depositCustomToken', 'update one wallet id => '. $deposit->receiver_wallet_id);
            storeException('depositCustomToken', 'Deposit process success');
        } catch (\Exception $e) {
            storeException('updateUserWallet ex', $e->getMessage());
        }
    }

    // check address and deposit
    public function checkAddressAndDeposit($address,$hash,$amount,$fromAddress)
    {
        try {
            storeBotException('deposit address',$address);
            storeBotException('deposit hash',$hash);
            storeBotException('deposit amount',$amount);
            storeBotException('deposit from address',$fromAddress);
            $checkAddress = WalletAddressHistory::where(['address' => $address])->first();
            if(!empty($checkAddress)) {

                $checkDeposit = DepositeTransaction::where(['address' => $address, 'transaction_id' => $hash])->first();
                if ($checkDeposit) {
                   storeBotException('checkAddressAndDeposit', 'deposit already in db '.$hash);
                    $response = ['success' => false, 'message' => __('This hash already in db'), 'data' => []];
                } else {
                    storeException('deposit request amount', $amount);
                    $amount = floatval($amount);
                    storeException('deposit request amount float', $amount);
                    $createDeposit = DepositeTransaction::create([
                        'address' => $address,
                        'from_address' => $fromAddress,
                        'receiver_wallet_id' => $checkAddress->wallet_id,
                        'address_type' => ADDRESS_TYPE_EXTERNAL,
                        'coin_type' => $checkAddress->coin_type,
                        'amount' => $amount,
                        'transaction_id' => $hash,
                    ]);
                    if ($createDeposit) {
                        storeException('deposit', $createDeposit);
                        $wallet = Wallet::where(['id' => $createDeposit->receiver_wallet_id])->first();
                        if ($wallet) {
                            storeException('deposit amount', ($amount));
                            storeException('balance before', $wallet->balance);
                            $wallet->increment('balance', $amount);
                            $createDeposit->status = STATUS_ACTIVE;
                            $createDeposit->save();
                            storeException('balance after', $wallet->balance);
                        }
                        $response = ['success' => true, 'message' => __('New deposit'), 'data' => $createDeposit,'pk' => $checkAddress->wallet_key];
                    } else {
                        $response = ['success' => false, 'message' => 'deposit credited failed', 'data' => []];
                    }
                }
            } else {
                $response = ['success' => false, 'message' => __('This address not found in db'), 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('checkAddressAndDeposit ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // get latest transaction block data
    public function getLatestTransactionFromBlock($coin)
    {
        $response = ['success' => false, 'message' => 'failed', 'data' => []];
        try {
            $tokenApi = new ERC20TokenApi($coin);
            $result = $tokenApi->getContractTransferEvent();
            if ($result['success'] == true) {
                $response = ['success' => $result['success'], 'message' => $result['message'], 'data' => $result['data']->result];
            } else {
                $response = ['success' => false, 'message' => __('No transaction found'), 'data' => []];
            }

        } catch (\Exception $e) {
            storeException('getLatestTransactionFromBlock ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // check estimate gas for sending token
    public function checkEstimateGasFees($coin,$address,$amount)
    {
        $response = ['success' => false, 'message' => 'failed', 'data' => []];
        try {
            $requestData = [
                "amount_value" => $amount,
                "from_address" => $address,
                "to_address" => $coin->wallet_address
            ];
            $tokenApi = new ERC20TokenApi($coin);
            $check = $tokenApi->checkEstimateGas($requestData);
            storeException('checkEstimateGasFees', $check);
            if ($check['success'] == true) {
                $response = ['success' => true, 'message' => $check['message'], 'data' => $check['data']];
            } else {
                $response = ['success' => false, 'message' => $check['message'], 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('checkEstimateGasFees ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }

        return $response;
    }

    // send estimate gas fees to address
    public function sendFeesToUserAddress($coin,$address,$amount,$wallet_id,$depositId,$type=null)
    {
        try {
            $requestData = [
                "amount_value" => $amount,
                "from_address" => $coin->wallet_address,
                "to_address" => $address,
                "contracts" => decryptId($coin->wallet_key)
            ];
            $tokenApi = new ERC20TokenApi($coin);
            $result = $tokenApi->sendEth($requestData);
            storeException('sendFeesToUserAddress result ', $result);
            if ($result['success'] == true) {
                $this->saveEstimateGasFeesTransaction($wallet_id,$result['data']->hash,$amount,$coin->wallet_address,$address,$depositId,$coin->contract_coin_name,$type);
                $response = ['success' => true, 'message' => __('Fess send successfully'), 'data' => []];
            } else {
                $response = ['success' => false, 'message' => $result['message'], 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('sendFeesToUserAddress ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // save estimate gas fees transaction
    public function saveEstimateGasFeesTransaction($wallet_id,$hash,$amount,$adminAddress,$userAddress,$depositId,$contractCoinName,$type=null)
    {
        try {
            $data = EstimateGasFeesTransactionHistory::create([
                'unique_code' => uniqid().date('').time(),
                'wallet_id' => $wallet_id,
                'deposit_id' => $depositId,
                'amount' => $amount,
                'coin_type' => $contractCoinName,
                'admin_address' => $adminAddress,
                'user_address' => $userAddress,
                'transaction_hash' => $hash,
                'status' => STATUS_PENDING,
                'type' => $type ?? TYPE_DEPOSIT
            ]);
//            storeException('saveEstimateGasFeesTransaction', json_encode($data));
        } catch (\Exception $e) {
            storeException('saveEstimateGasFeesTransaction ex', $e->getMessage());
        }
    }

    // receive token from user address
    public function receiveTokenFromUserAddress($coin,$address,$amount,$userPk,$depositId)
    {
        try {
            $requestData = [
                "amount_value" => $amount,
                "from_address" => $address,
                "to_address" => $coin->wallet_address,
                "contracts" => $userPk
            ];

            $checkAddressBalanceAgain = $this->checkWalletAddressAllBalance($coin,$address);
//            storeException('receiveTokenFromUserAddress  $check Address All Balance ',$checkAddressBalanceAgain);
            $tokenApi = new ERC20TokenApi($coin);
            $result = $tokenApi->sendCustomToken($requestData);
            storeException('receiveTokenFromUserAddress $result', $result);
            if ($result['success'] == true) {
                $this->saveReceiveTransaction($result['data']->used_gas,$result['data']->hash,$amount,$coin->wallet_address,$address,$depositId);
                $response = ['success' => true, 'message' => __('Token received successfully'), 'data' => $result['data']];
            } else {
                $response = ['success' => false, 'message' => $result['message'], 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('receiveTokenFromUserAddress ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // save receive token transaction
    public function saveReceiveTransaction($fees,$hash,$amount,$adminAddress,$userAddress,$depositId,$type=null)
    {
        try {
            $data = AdminReceiveTokenTransactionHistory::create([
                'unique_code' => uniqid().date('').time(),
                'amount' => $amount,
                'deposit_id' => $depositId,
                'fees' => $fees,
                'to_address' => $adminAddress,
                'from_address' => $userAddress,
                'transaction_hash' => $hash,
                'status' => STATUS_SUCCESS,
                'type' => $type ?? TYPE_DEPOSIT
            ]);
//            storeException('saveReceiveTransaction', json_encode($data));
        } catch (\Exception $e) {
            storeException('saveReceiveTransaction', $e->getMessage());
        }
    }

    // check wallet balance
    public function checkWalletAddressBalance($coin,$address,$type=1)
    {
        try {
            $requestData = array(
                "type" => $type,
                "address" => $address,
            );
            $tokenApi = new ERC20TokenApi($coin);
            $result = $tokenApi->checkWalletBalance($requestData);
            if ($result['success'] == true) {
                $response = ['success' => true, 'message' => __('Get balance'), 'data' => $result['data'] ];
            } else {
                storeException('sendFeesToUserAddress', $result['message']);
                $response = ['success' => false, 'message' => $result['message'], 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('checkWalletAddressBalance ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // check wallet balance
    public function checkWalletAddressAllBalance($coin,$address)
    {
        try {
            $requestData = array(
                "type" => 3,
                "address" => $address,
            );
            $tokenApi = new ERC20TokenApi($coin);
            $result = $tokenApi->checkWalletBalance($requestData);
            storeException('checkWalletAddressAllBalance check',$result);
            if ($result['success'] == true) {
                $response = ['success' => true, 'message' => __('Get balance'), 'data' => $result['data'] ];
            } else {
                storeException('sendFeesToUserAddress', $result['message']);
                $response = ['success' => false, 'message' => $result['message'], 'data' => []];
            }
        } catch (\Exception $e) {
            storeException('checkWalletAddressBalance ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // token receive manually by admin
    public function tokenReceiveManuallyByAdmin($transaction,$adminId)
    {
        storeBotException('tokenReceiveManuallyByAdmin', 'called');
        try {
            $this->tokenReceiveManuallyByAdminProcess($transaction,$adminId);
        } catch (\Exception $e) {
            storeException('tokenReceiveManuallyByAdmin ex', $e->getMessage());
        }
    }

    public function getTronEstimateGas($transaction, $coin){
        try {
            if(isset($coin->gas_limit) && $coin->gas_limit !== 0) return TRC20ESTFEE;
            storeBotException('TronEstimateGas', 'start');
            $tokenApi = new ERC20TokenApi($coin);
            $requestData = [
                "to_wallet"   => $transaction->address,
                "amount"      => $transaction->amount * 1000000,
                "sun"         => ENERGYSUN
            ];
            $response = $tokenApi->getTrxEstimatedGas($requestData);
            if(isset($response['success']) && $response['success']){
                storeBotException('getTronEstimateGas result', json_encode($response['data']));
                if(isset($response['data']) && isset($response['data']->gas))
                return $response['data']->gas;
            }
            return TRC20ESTFEE;
        } catch (\Exception $e) {
            storeBotException('tokenReceiveManuallyByAdminProcess getTronEstimateGas', $e->getMessage());
            return TRC20ESTFEE;
        }
    }

    // token Receive Manually By Admin process
    public function tokenReceiveManuallyByAdminProcess($transaction,$adminId)
    {
        storeBotException('tokenReceiveManuallyByAdminProcess', 'start process');
        try {
            if ($transaction->is_admin_receive == STATUS_PENDING) {
                $coin = Coin::join('coin_settings', 'coin_settings.coin_id', '=', 'coins.id')
                    ->where(['coins.coin_type' => $transaction->coin_type])
                    ->first();
                $sendAmount = (float)$transaction->amount;
                $checkAddress = $this->checkAddress($transaction->address);
                $userPk = get_wallet_personal_add($transaction->address,$checkAddress->wallet_key);
                if ($coin->network == TRC20_TOKEN) {
                    $checkGasFees['success'] = true;
                    $gas = $this->getTronEstimateGas($transaction,$coin);
                } else {
                    $checkGasFees = $this->checkEstimateGasFees($coin, $transaction->address, $sendAmount);
                }
                if ($checkGasFees['success'] == true) {
                    if ($coin->network != TRC20_TOKEN) {
                        storeException('Estimate gas ', $checkGasFees['data']->estimateGasFees);
                        $estimateFees = $checkGasFees['data']->estimateGasFees;
                        $gas = bcadd($estimateFees, (bcdiv(bcmul($estimateFees, 10, 8), 100, 8)), 8);
                        storeException('Gas', $gas);

                    }
                    $checkAddressBalance = $this->checkWalletAddressBalance($coin, $transaction->address,1);
                    if ($checkAddressBalance['success'] == true) {
                        $walletNetBalance = $checkAddressBalance['data']->net_balance;
                        storeException('$walletNetBalance', $walletNetBalance);
                        if ($walletNetBalance >= $gas) {
                            $estimateGas = 0;
                            storeException('$estimateGas 0 ', $estimateGas);
                        } else {
                            storeException('$estimateGas bcsub gas ', $gas);
                            storeException('$estimateGas bcsub walletNetBalance ', number_format($walletNetBalance,18));
                            $estimateGas = bcsub($gas, number_format($walletNetBalance,18), 8);
                            storeException('$estimateGas have ', $estimateGas);
                        }
                        if ($estimateGas > 0) {
                            storeException('sendFeesToUserAddress ', $estimateGas);
                            $sendFees = $this->sendFeesToUserAddress($coin, $transaction->address, $estimateGas, $checkAddress->wallet_id, $transaction->id,TYPE_DEPOSIT);
                            if ($sendFees['success'] == true) {
                                storeException('tokenReceiveManuallyByAdminProcess -> ', 'sendFeesToUserAddress success . the next process will held on getDepositBalanceFromUserJob');
                            } else {
                                storeException('tokenReceiveManuallyByAdminProcess', 'send fees process failed');
                            }
                        } else {
                            storeException('sendFeesToUserAddress ', 'no gas needed');
                            $checkAddressBalanceAgain2 = $this->checkWalletAddressBalance($coin, $transaction->address);
                            storeException('tokenReceiveManuallyByAdminProcess  $checkAddressBalanceAgain2', $checkAddressBalanceAgain2);
                            if ($checkAddressBalanceAgain2['success'] == true) {
                                storeException('tokenReceiveManuallyByAdminProcess', 'next process goes to AdminTokenReceiveJob queue');
                            } else {
                                storeException('tokenReceiveManuallyByAdminProcess', 'again 2 get balance failed');
                            }
                        }
                        storeException('tokenReceiveManuallyByAdminProcess', 'next process receiveTokenFromUserAddressByAdminPanel');

                        $receiveToken = $this->receiveTokenFromUserAddressByAdminPanel($gas,$coin, $transaction->address, $sendAmount, $userPk, $transaction->id, TYPE_DEPOSIT);
                        if ($receiveToken['success'] == true) {
                            $this->updateUserWalletByAdmin($transaction, $adminId);
                        } else {
                            storeException('tokenReceiveManuallyByAdminProcess', 'token received process failed');
                        }
                        storeException('tokenReceiveManuallyByAdminProcess', 'token received process executed');
                    } else {
                        storeException('tokenReceiveManuallyByAdminProcess', 'get balance failed');
                    }
                } else {
                    storeException('tokenReceiveManuallyByAdminProcess', 'check gas fees calculate failed');
                }
            } else {
                storeException('tokenReceiveManuallyByAdminProcess', 'transaction is already received by admin');
            }
        } catch (\Exception $e) {
            storeException('tokenReceiveManuallyByAdminProcess', $e->getMessage());
        }
    }

    // check address
    public function checkAddress($address)
    {
        return WalletAddressHistory::where(['address' => $address])->first();
    }

    // receive token from user address by admin
    public function receiveTokenFromUserAddressByAdminPanel($gas,$coin,$address,$amount,$userPk,$depositId,$type=null)
    {
        try {
            storeException('type',$type);
            $requestData = [
                "amount_value" => $amount,
                "from_address" => $address,
                "to_address" => $coin->wallet_address,
                "contracts" => $userPk
            ];

            $checkAddressBalanceAgain = $this->checkWalletAddressAllBalance($coin,$address);
            storeException('receiveTokenFromUserAddressByAdminPanel  $check Address All Balance ',$checkAddressBalanceAgain);
            if ($checkAddressBalanceAgain['success'] == true) {
                $netGasBalance = bcadd($checkAddressBalanceAgain['data']->net_balance,0,8);
                storeException('receiveTokenFromUserAddressByAdminPanel netGasBalance', $netGasBalance);

                // if ($gas > $netGasBalance) {
                //     storeException('receiveTokenFromUserAddressByAdminPanel need gas', $gas);
                //     storeException('receiveTokenFromUserAddressByAdminPanel need gas', 'Do not have enough gas');
                //     $response = ['success' => false, 'message' => __('Do not have enough gas balance'), 'data' => []];
                // } else {
                    if ($amount > $checkAddressBalanceAgain['data']->token_balance) {
                        storeException('receiveTokenFromUserAddressByAdminPanel need token', $amount);
                        storeException('receiveTokenFromUserAddressByAdminPanel need token', 'Do not have enough token balance');
                        $response = ['success' => false, 'message' => __('Do not have enough token balance'), 'data' => []];
                    } else {
                        storeException('send token','ready to go');
                        $tokenApi = new ERC20TokenApi($coin);
                        $result = $tokenApi->sendCustomToken($requestData);
                        storeException('receiveTokenFromUserAddressByAdminPanel $result', $result);
                        if ($result['success'] == true) {
                            $this->saveReceiveTransaction($result['data']->used_gas,$result['data']->hash,$amount,$coin->wallet_address,$address,$depositId,$type);
                            storeException('receiveTokenFromUserAddressByAdminPanel', 'Token receive success');
                            $response = ['success' => true, 'message' => __('Token received successfully'), 'data' => $result['data']];
                        } else {
                            storeException('receiveTokenFromUserAddressByAdminPanel', $result['message']);
                            $response = ['success' => false, 'message' => $result['message'], 'data' => []];
                        }
                    }
                // }

            } else {
                storeException('receiveTokenFromUserAddressByAdminPanel', $checkAddressBalanceAgain['message']);
                $response = ['success' => false, 'message' => $checkAddressBalanceAgain['message'], 'data' => []];
            }

        } catch (\Exception $e) {
            storeException('receiveTokenFromUserAddressByAdminPanel ex', $e->getMessage());
            $response = ['success' => false, 'message' => $e->getMessage(), 'data' => []];
        }
        return $response;
    }

    // update wallet
    public function updateUserWalletByAdmin($deposit,$adminId)
    {
        try {
            DepositeTransaction::where(['id' => $deposit->id])
                ->update([
                    'is_admin_receive' => STATUS_SUCCESS,
                    'received_amount' => $deposit->amount,
                    'updated_by' => $adminId
                ]);

            storeException('updateUserWalletByAdmin', 'Deposit process success');
        } catch (\Exception $e) {
            storeException('updateUserWalletByAdmin', $e->getMessage());
        }
    }


    // get deposit token balance from user
    public function getDepositTokenFromUser()
    {
        storeBotException('getDepositTokenFromUser command','called');
        try {
            $adminId = 1;
            $admin = User::where(['role' => USER_ROLE_ADMIN])->orderBy('id', 'asc')->first();
            if ($admin) {
                $adminId = $admin->id;
            }
            $transactions = DepositeTransaction::join('coins', 'coins.coin_type', '=', 'deposite_transactions.coin_type')
                ->where(['deposite_transactions.address_type' => ADDRESS_TYPE_EXTERNAL])
                ->where('deposite_transactions.is_admin_receive', STATUS_PENDING)
                ->select('deposite_transactions.*')
                ->whereIn('coins.network', [ERC20_TOKEN, BEP20_TOKEN, TRC20_TOKEN])
                ->get();
            if (isset($transactions[0])) {
                foreach($transactions as $transaction) {
                    $this->tokenReceiveManuallyByAdmin($transaction, $adminId);
                }
            }
        } catch (\Exception $e) {
            storeException('getDepositTokenFromUser ex', $e->getMessage());
        }
    }


    // token Receive Manually By Admin process
    public function tokenReceiveManuallyByAdminFromBuyToken($transaction,$adminId)
    {
        storeBotException('tokenReceiveManuallyByAdminFromBuyToken', 'start process');
        try {
            if ($transaction->is_admin_receive == STATUS_PENDING) {
                $coin = Coin::join('coin_settings', 'coin_settings.coin_id', '=', 'coins.id')
                    ->where(['coins.id' => $transaction->coin_id])
                    ->first();
                $sendAmount = (float)$transaction->amount;
                $checkAddress = $this->checkAddress($transaction->address);
                $userPk = get_wallet_personal_add($transaction->address,$checkAddress->wallet_key);
                if ($coin->network == TRC20_TOKEN) {
                    $checkGasFees['success'] = true;
                    $gas = TRC20ESTFEE;
                } else {
                    $checkGasFees = $this->checkEstimateGasFees($coin, $transaction->address, $sendAmount);
                }
                storeBotException('$checkGasFees',$checkGasFees);
                if ($checkGasFees['success'] == true) {
                    if ($coin->network != TRC20_TOKEN) {
                        storeException('Estimate gas ', $checkGasFees['data']->estimateGasFees);
                        $estimateFees = $checkGasFees['data']->estimateGasFees;
                        $gas = bcadd($estimateFees, (bcdiv(bcmul($estimateFees, 10, 8), 100, 8)), 8);
                        storeException('Gas', $gas);

                    }
                    $checkAddressBalance = $this->checkWalletAddressBalance($coin, $transaction->address,1);
                    if ($checkAddressBalance['success'] == true) {
                        $walletNetBalance = $checkAddressBalance['data']->net_balance;
                        storeException('$walletNetBalance', $walletNetBalance);
                        if ($walletNetBalance >= $gas) {
                            $estimateGas = 0;
                            storeException('$estimateGas 0 ', $estimateGas);
                        } else {
                            storeException('$estimateGas bcsub gas ', $gas);
                            storeException('$estimateGas bcsub walletNetBalance ', number_format($walletNetBalance,18));
                            $estimateGas = bcsub($gas, number_format($walletNetBalance,18), 8);
                            storeException('$estimateGas have ', $estimateGas);
                        }
                        if ($estimateGas > 0) {
                            storeException('sendFeesToUserAddress ', $estimateGas);
                            $sendFees = $this->sendFeesToUserAddress($coin, $transaction->address, $estimateGas, $checkAddress->wallet_id, $transaction->id,TYPE_BUY);
                            if ($sendFees['success'] == true) {
                                storeException('tokenReceiveManuallyByAdminFromBuyToken -> ', 'sendFeesToUserAddress success . the next process will held on getDepositBalanceFromUserJob');
                            } else {
                                storeException('tokenReceiveManuallyByAdminFromBuyToken', 'send fees process failed');
                            }
                        } else {
                            storeException('sendFeesToUserAddress ', 'no gas needed');
                            $checkAddressBalanceAgain2 = $this->checkWalletAddressBalance($coin, $transaction->address);
                            storeException('tokenReceiveManuallyByAdminFromBuyToken  $checkAddressBalanceAgain2', $checkAddressBalanceAgain2);
                            if ($checkAddressBalanceAgain2['success'] == true) {
                                storeException('tokenReceiveManuallyByAdminFromBuyToken', 'next process goes to AdminTokenReceiveJob queue');
                            } else {
                                storeException('tokenReceiveManuallyByAdminFromBuyToken', 'again 2 get balance failed');
                            }
                        }
                        storeException('tokenReceiveManuallyByAdminFromBuyToken', 'next process receiveTokenFromUserAddressByAdminPanel');

                        $receiveToken = $this->receiveTokenFromUserAddressByAdminPanel($gas,$coin, $transaction->address, $sendAmount, $userPk, $transaction->id,TYPE_BUY);
                        if ($receiveToken['success'] == true) {
                            DB::table('token_buy_histories')->where(['id' => $transaction->id])
                                ->update(['is_admin_receive' => STATUS_ACTIVE]);
                        } else {
                            storeException('tokenReceiveManuallyByAdminFromBuyToken', 'token received process failed');
                        }
                        storeException('tokenReceiveManuallyByAdminFromBuyToken', 'token received process executed');
                    } else {
                        storeException('tokenReceiveManuallyByAdminFromBuyToken', 'get balance failed');
                    }
                } else {
                    storeException('tokenReceiveManuallyByAdminFromBuyToken', 'check gas fees calculate failed');
                }
            } else {
                storeException('tokenReceiveManuallyByAdminFromBuyToken', 'transaction is already received by admin');
            }
        } catch (\Exception $e) {
            storeException('tokenReceiveManuallyByAdminFromBuyToken', $e->getMessage());
        }
    }

    public function updateCoinBlockNumber($coin_type,$block_number = 0, $block_timestamp = 0 )
    {
        try{
            $coin = Coin::where('coin_type', $coin_type)->first();
            if(isset($coin))
            {
                storeBotException('last timestamp', $coin->last_timestamp);
                storeBotException('last block number', $coin->last_block_number);
                $coin->last_block_number = $block_number;
                $coin->last_timestamp = $block_timestamp;
                $coin->save();
                storeBotException('last timestamp', $coin->last_timestamp);
                storeBotException('last block number', $coin->last_block_number);
            }
        } catch (\Exception $e) {
            storeException('updateCoinBlockNumber', $e->getMessage());
        }
    }
}
