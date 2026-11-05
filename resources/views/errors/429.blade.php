@extends('errors.layout')

@section('title', 'Too Many Requests')
@section('code', '429')
@section('message', 'You have made too many requests in a short period. Please wait a few minutes before trying again.')
