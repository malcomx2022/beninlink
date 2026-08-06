
@extends('errors.layout')
@section('title', __('Unauthorized'))
@section('code', '401')
@section('message-headline', __('Opps ! Something went wrong.'))
@section('message-title', __('Your authorization failed.'))
@section('message', __('Please refresh in the page and fill in the login correct information.'))
