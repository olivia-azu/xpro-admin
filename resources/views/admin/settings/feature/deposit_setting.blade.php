<div class="header-bar">
    <div class="table-title">
        <h3>{{__('Fiat deposit enable disable')}}</h3>
    </div>
</div>
<div class="profile-info-form">
    <form action="{{route('adminCookieSettingsSave')}}" method="post"
          enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-lg-6 col-12 mt-20">
                <div class="form-group">
                    <label>{{__('Enable Fiat Deposit')}}</label>
                    <div class="cp-select-area">
                        <select name="currency_deposit_status" class="form-control">
                            <option @if(isset($settings['currency_deposit_status']) && $settings['currency_deposit_status'] == STATUS_REJECTED) selected @endif value="{{STATUS_REJECTED}}">{{__("No")}}</option>
                            <option @if(isset($settings['currency_deposit_status']) && $settings['currency_deposit_status'] == STATUS_ACTIVE) selected @endif value="{{STATUS_ACTIVE}}">{{__("Yes")}}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-12 mt-20">
                <div class="form-group">
                    <label>{{__('Enable Fiat Deposit 2FA ')}}</label>
                    <div class="cp-select-area">
                        <select name="currency_deposit_2fa_status" class="form-control">
                            <option @if(isset($settings['currency_deposit_2fa_status']) && $settings['currency_deposit_2fa_status'] == STATUS_ACTIVE) selected @endif value="{{STATUS_ACTIVE}}">{{__("Yes")}}</option>
                            <option @if(isset($settings['currency_deposit_2fa_status']) && $settings['currency_deposit_2fa_status'] == STATUS_REJECTED) selected @endif value="{{STATUS_REJECTED}}">{{__("No")}}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2 col-12 mt-20">
                <button class="button-primary theme-btn">{{__('Update')}}</button>
            </div>
        </div>
    </form>
</div>
