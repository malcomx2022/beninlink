
@extends('errors.layout')
@section('title', __('Too Many Requests '))
@section('code', '429')
@section('message-headline', __('Opps ! Something went wrong.'))
@section('message-title', __('Too Many Requests received.'))
@section('message', __('Please try again later.'))
