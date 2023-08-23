<?php

namespace App\Http\Repositories;
use App\Model\CurrencyDeposit;

class CurrencyDepositRepository extends CommonRepository
{
    function __construct($model) {
        parent::__construct($model);
    }

    public function getPendingDepositList()
    {
        return CurrencyDeposit::where('status',0)->get();
    }

    public function getDepositHistory($userId,$paginate = null)
    {
        $lists = CurrencyDeposit::where('user_id',$userId)->orderBy('id', 'DESC')->paginate($paginate ?? 200);
        if (isset($lists[0])) {
            foreach ($lists as $list) {
                $list->coin_type = $list->wallet->coin_type;
            }
        }
        return $lists;    }
}
