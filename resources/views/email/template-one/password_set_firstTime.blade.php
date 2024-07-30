@include('email.template-one.header')
    <h3>{{__('Hello')}}, {{(!empty(\App\User::where('email',$email)->first())) ? \App\User::where('email',$email)->first()->name : ''}},</h3>
    <p>We need to verify your account. Insert the following code in your Application.</p>
    <p style="text-align:center;font-size:30px;font-weight:bold">{{$token}}</p>
    <p style="text-align:center;font-size:30px;font-weight:bold">
        <a href="{{route('resetPasswordPage')}}"><strong>Verify</strong>
        </a>
    </p>
@include('email.template-one.footer')
