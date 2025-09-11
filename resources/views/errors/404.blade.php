@extends('layouts.app')

@section('title', __('Page Not Found'))

@section('content')
<div class="container text-center py-5">
    <h1 class="display-4">404</h1>
    <p class="lead">{{ $exception->getMessage() ?: __('Page Not Found') }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">{{ __('Go Home') }}</a>
</div>
@endsection