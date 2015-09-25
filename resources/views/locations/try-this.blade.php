@extends('app')

@section('content')
<div style="max-width: 62em; margin: 0 auto">
    <h1 style="margin-top: 1em; font-family: sans-serif; font-weight: 200; font-size: 10em; line-height: 1; letter-spacing: -5px; color: #8f8f8f; text-align: center;">{{ $location['city'] }}, {{ $location['state'] }} <small style="font-size: 45%; color: #cacaca; letter-spacing: -5px;">{{ $location['zip'] }}</small></h1>
</div>
@stop

