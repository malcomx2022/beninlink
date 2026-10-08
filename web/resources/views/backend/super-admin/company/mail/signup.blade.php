<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre }}</title>
    <style>
            body{
                background-color: aliceblue;
                color:#8094ae;
            }
            a{
                color:#12503A;
            }
            ul {
                display: inline-block;
                text-align: center;
                overflow: hidden;
                padding-left: 0px;
                margin-bottom: 0px!important;
            }
            ul li{
                float: left;
                list-style: none;
                padding:5px;
            }
            ul li a{
                cursor: pointer;
                display: block;
            }
    </style>
</head>
    <body style="margin: 10;">
        <table style="width:100%;height:50px;max-width:650px;margin: auto;">
                <tr>
                    <td style="text-align: center;padding:30px 10px">
                        <a href="{{ url('/') }}"><img alt="{{ $companyName }}" src="{{ static_asset($companyLogo) }}" style="height: 50px;"/></a>
                    </td>
                </tr>
        </table>
        <table style="width:100%;height:50px;max-width:650px;margin: auto;background-color: white;">
                <tr>
                    <td style="padding:30px;line-height: 1.5;" colspan="2">
                        <p>{{ __('Hi') }} <b style="font-style: italic;">{{ @$data['user']->company->name }}</b>,</p>
                        <p>{{ __('Thank you for registering your company on :name.', ['name' => $companyName]) }}</p>
                        <p>{{ __('Your login is') }} <b style="font-style: italic;" >{{ @$data['user']->email }}</b></p>
                        <div style="text-align: center;">
                            {{ __('Your OTP Code:') }} <b>{{ @$data['otp'] }}</b><br><br> 
                         </div>
                        <p style="color: #12503A;font-weight: bold;">{{ __('Your company Information :') }}</p>
                         <div style="display: flex;">
                             <div style="width: 20%;display: inline-block;"><b >{{ __('Company Name') }}</b></div>
                             <div>: {{ @$data['user']->company->name }}</div>
                         </div>
                         <div style="display: flex;">
                             <div style="width: 20%;display: inline-block;"><b >{{ __('Company Email') }}</b></div>
                             <div>: {{ @$data['user']->company->email }}</div>
                         </div>
                         <div style="display: flex;">
                             <div style="width: 20%;display: inline-block;"><b >{{ __('Phone') }}</b></div>
                             <div>: {{ @$data['user']->company->phone }}</div>
                         </div>
                         <div style="display: flex;">
                             <div style="width: 20%;display: inline-block;"><b>{{ __('Address') }}</b></div>
                             <div>: {{ @$data['user']->company->address }}</div>
                         </div>
                       
                        <p>{{ __('Any question? Write to us at') }} <a href="mailto:{{ $courriel }}" >{{ $courriel }}</a> {{ __('or call') }} {{ $telephone }}.</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p style="text-align: center;text-transform:uppercase">
                            {{ __('Download our mobile applications') }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%; text-align: right;padding: 10px 10px 30px 10px;">
                       <a href="#"> <img alt="{{ __('Download on Google Play') }}" src="{{ static_asset('backend/images/social-media') }}/play butttom.png" style="width:200px;"/></a>
                    </td>
                    <td style="width: 50%;text-align: left;padding: 10px 10px 30px 10px;">
                        <a href="#"><img alt="{{ __('Download on the App Store') }}" src="{{ static_asset('backend/images/social-media') }}/istore.png" style="width:200px" /></a>
                    </td>
                </tr>
        </table>
        <table style="width:100%;height:50px;max-width:650px;margin: auto; ">
                <tr>
                    <td style="text-align: center;">
                        <ul>
                            <li> <a> <img alt="" src="{{ static_asset('backend/images/social-media') }}/brand-b.png" style="width: 30px;" />  </a> </li>
                            <li> <a> <img alt="" src="{{ static_asset('backend/images/social-media') }}/brand-c.png" style="width: 30px;" />  </a> </li>
                            <li> <a> <img alt="" src="{{ static_asset('backend/images/social-media') }}/brand-d.png" style="width: 30px;" />  </a> </li>
                            <li> <a> <img alt="" src="{{ static_asset('backend/images/social-media') }}/brand-e.png" style="width: 30px;" />  </a> </li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0px 30px;text-align: center;">
                        <p style="font-size: 13px;">
                            {{ $mentions }}
                        </p>
                    </td>
                </tr>
        </table>
    </body>
</html>
