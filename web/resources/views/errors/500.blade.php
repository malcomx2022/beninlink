
@extends('errors.layout')
@section('title', __('Internal Server Error'))
@section('code', '500')
@section('message-headline', __('Opps ! Something went wrong.'))
@section('message-title', __('500 Internal Server Error.'))
@section('message', __("Try to refresh this page or feel free to contact us if the problem persists."))
