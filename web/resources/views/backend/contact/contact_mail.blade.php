<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
            body{
                background-color: aliceblue;
                color:#8094ae;
            }
            a{
                color:#7e0095;
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
            ul li a img{

            }
    </style>
</head>
    <body style="margin: 10;">
        <table style="width:100%;height:50px;max-width:650px;margin: auto;">
            <tr> 
                <td style="text-align: center;padding:30px 10px">
                    <a href="{{ url('/') }}"><img src="{{ $logoImage }}" style="height: 50px;width:250px;object-fit:contain"/></a>
                </td>
            </tr>
        </table>
        <table style="width:100%;height:50px;max-width:650px;margin: auto;background-color: white;">
            <tr>
                <td style="padding:30px;" colspan="2">
                    <p>Name  :   <b style="font-style: italic;">{{ @$data['name'] }}</b></p>
                    <p>Email :   <b style="font-style: italic;">{{ @$data['email'] }}</b></p>
                    <p>Subject : <b style="font-style: italic;">{{ @$data['subject'] }}</b></p>
                    <span>
                        <b>Message:</b>
                    </span>
                    <p style="line-height: 1.5;">{!! @$data['message'] !!}</p>
                </td>
            </tr>
        </table>
        <table style="width:100%;height:50px;max-width:650px;margin: auto; "> 
                <tr>
                    <td style="padding:0px 30px;text-align: center;">
                        <p style="font-size: 13px;">
                         {{ @settings()->copyright }}
                        </p>
                    </td>
                </tr>
        </table>
    </body>
</html>
