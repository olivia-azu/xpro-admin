<div class="header-bar">
    <div class="table-title">
        <h3>{{__('Trading bot enable disable')}}</h3>
    </div>
    <div>
        <button class="btn theme-btn" id="deleteBotOrders">{{__('Delete Bot Orders')}}</button>
    </div>
</div>
<div class="profile-info-form">
    <form action="{{route('adminCookieSettingsSave')}}" method="post"
          enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-6 col-12 mt-20">
                <div class="form-group">
                    <label>{{__('Trading bot')}}</label>
                    <div class="cp-select-area">
                        <select name="enable_bot_trade" class="form-control">
                            <option @if(isset($settings['enable_bot_trade']) && $settings['enable_bot_trade'] == STATUS_ACTIVE) selected @endif value="{{STATUS_ACTIVE}}">{{__("Yes")}}</option>
                            <option @if(isset($settings['enable_bot_trade']) && $settings['enable_bot_trade'] == STATUS_PENDING) selected @endif value="{{STATUS_PENDING}}">{{__("No")}}</option>
                        </select>
                    </div>
                </div>
            </div>
{{--            <div class="col-lg-6 col-12 mt-20">--}}
{{--                <div class="form-group">--}}
{{--                    <label for="#">{{__('Tolerance Percentage')}} </label>--}}
{{--                    <input class="form-control " type="text"--}}
{{--                           name="trading_bot_price_tolerance" placeholder=""--}}
{{--                           value="{{isset($settings['trading_bot_price_tolerance']) ? $settings['trading_bot_price_tolerance'] : '10'}}">--}}
{{--                </div>--}}
{{--            </div>--}}
            <div class="col-lg-6 col-12 mt-20">
                <div class="form-group">
                    <label for="#">{{__('Bot Order Place Interval (Min)')}} </label>
                    <input class="form-control " type="text"
                           name="trading_bot_buy_interval" placeholder=""
                           value="{{isset($settings['trading_bot_buy_interval']) ? $settings['trading_bot_buy_interval'] : '30'}}">
                </div>
            </div>
{{--            <div class="col-lg-6 col-12 mt-20">--}}
{{--                <div class="form-group">--}}
{{--                    <label for="#">{{__('Sell Order Place Interval in seconds')}} </label>--}}
{{--                    <input class="form-control " type="text"--}}
{{--                           name="trading_bot_sell_interval" placeholder=""--}}
{{--                           value="{{isset($settings['trading_bot_sell_interval']) ? $settings['trading_bot_sell_interval'] : '30'}}">--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
        <div class="row">
            <div class="col-lg-2 col-12 mt-20">
                <button class="button-primary theme-btn">{{__('Update')}}</button>
            </div>
        </div>
    </form>
</div>
