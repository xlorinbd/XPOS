@php
    $decimal = config('decimal');
    $currency = config('currency');
    $currency_position = config('currency_position');
    
    if (!$currency) $currency = config('currency');
@endphp

@props([
    'amount',
    'currency_symbol' => $currency,
    'currency_position' => $currency_position,
    'decimal' => $decimal,
])

@if (mb_strlen($currency_symbol) > 1)
    @if ($currency_position == 'prefix')
        <span>{{ $currency_symbol }}&nbsp;{{ amount_format((float) $amount) }}</span>
    @else
        <span>{{ amount_format((float) $amount) }}&nbsp;{{ $currency_symbol }}</span>
    @endif
@else
    @if ($currency_position == 'prefix')
        <span>{{ $currency_symbol }}{{ amount_format((float) $amount) }}</span>
    @else
        <span>{{ amount_format((float) $amount) }}{{ $currency_symbol }}</span>
    @endif
@endif