
@extends('errors.layout',['purchase_verify'=>'purchase_verify'])
@section('title', __('WemaxDevs Product Activation')) 
@section('message-headline', __('This domain is inactive.'))
@section('message-title', __('WemaxDevs Product Activation.')) 
@section('logo')
<img src="{{ static_asset('wemaxdevs.png') }}" width="200px"/>
@endsection
@section('message') 
    <h4>Instruction for activating purchase code</h4>
    <ol class="d-inline-block">
        <li class="text-left">Make sure you have valid purchase code.</li>
        <li class="text-left">Make sure your CodeCanyon username is correct.</li>
        <li class="text-left">Make sure your domain is correct. example:https://yourdomain.com</li>
    </ol> 
@endsection
