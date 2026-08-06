
@extends('errors.layout')
@section('title', __('An Error Occurred:Method not allowed'))
@section('code', '405')
@section('message-headline', __('Oops! An Error Occurred.'))
@section('message-title', __('The server returned a "405 Method Not Allowed".'))
@section('message', __('Something is broken. Please let us know what you were doing when this error occurred. We will fix it as soon as possible. Sorry for any inconvenience caused.'))
