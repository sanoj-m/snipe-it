@extends('layouts/default')

{{-- [killa currency] exchange rates to the default currency, used for per-license
     currencies and the AED conversion shown on the Renewal Tracker. --}}
@section('title')
    {{ trans('killa.currency.title') }}
    @parent
@stop

@section('content')
    <x-container class="col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
        <x-form :route="route('settings.currency.save')">
            <x-box top_submit>
                <x-slot:header>
                    <x-icon type="cost" /> {{ trans('killa.currency.title') }}
                </x-slot:header>

                <div class="col-md-12">
                    <p class="text-muted">{{ trans('killa.currency.help', ['base' => $base]) }}</p>

                    <table class="table" id="k-currency-rates">
                        <thead>
                            <tr>
                                <th scope="col">{{ trans('killa.currency.code') }}</th>
                                <th scope="col">{{ trans('killa.currency.rate_to', ['base' => $base]) }}</th>
                                <th scope="col"><span class="sr-only">{{ trans('general.delete') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($i = 0)
                            @foreach ($rates as $code => $rate)
                                <tr>
                                    <td>
                                        <input type="text" class="form-control input-sm" name="rates[{{ $i }}][code]"
                                               value="{{ old("rates.$i.code", $code) }}" maxlength="3" required style="text-transform: uppercase;">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control input-sm" name="rates[{{ $i }}][rate]"
                                               value="{{ old("rates.$i.rate", $rate) }}" step="0.000001" min="0.000001" required>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger k-rate-remove" aria-label="{{ trans('general.delete') }}">
                                            <x-icon type="delete" />
                                        </button>
                                    </td>
                                </tr>
                                @php($i++)
                            @endforeach
                        </tbody>
                    </table>

                    <button type="button" class="btn btn-default btn-sm" id="k-rate-add">
                        <x-icon type="plus" /> {{ trans('killa.currency.add_row') }}
                    </button>
                </div>
            </x-box>
        </x-form>

        <form method="POST" action="{{ route('settings.currency.fetch') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-default">
                <x-icon type="sync" /> {{ trans('killa.currency.fetch_online') }}
            </button>
            <p class="text-muted" style="margin-top: 8px;">{{ trans('killa.currency.fetch_help', ['base' => $base]) }}</p>
        </form>
    </x-container>
@stop

@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        (function () {
            var tbody = document.querySelector('#k-currency-rates tbody');
            var nextIndex = {{ count($rates) }};

            document.getElementById('k-rate-add').addEventListener('click', function () {
                var row = document.createElement('tr');
                row.innerHTML =
                    '<td><input type="text" class="form-control input-sm" name="rates[' + nextIndex + '][code]" maxlength="3" required style="text-transform: uppercase;"></td>' +
                    '<td><input type="number" class="form-control input-sm" name="rates[' + nextIndex + '][rate]" step="0.000001" min="0.000001" required></td>' +
                    '<td><button type="button" class="btn btn-sm btn-danger k-rate-remove" aria-label="{{ trans('general.delete') }}"><i class="fas fa-times" aria-hidden="true"></i></button></td>';
                tbody.appendChild(row);
                nextIndex++;
            });

            tbody.addEventListener('click', function (event) {
                var button = event.target.closest('.k-rate-remove');
                if (button) {
                    button.closest('tr').remove();
                }
            });
        })();
    </script>

    @if (session('fetched_rates'))
        <script nonce="{{ csrf_token() }}">
            // Pre-fill the form with freshly fetched rates so the admin can
            // review before saving.
            (function () {
                var fetched = @json(session('fetched_rates'));
                var tbody = document.querySelector('#k-currency-rates tbody');
                tbody.innerHTML = '';
                var i = 0;
                Object.keys(fetched).forEach(function (code) {
                    var row = document.createElement('tr');
                    row.innerHTML =
                        '<td><input type="text" class="form-control input-sm" name="rates[' + i + '][code]" value="' + code + '" maxlength="3" required style="text-transform: uppercase;"></td>' +
                        '<td><input type="number" class="form-control input-sm" name="rates[' + i + '][rate]" value="' + fetched[code] + '" step="0.000001" min="0.000001" required></td>' +
                        '<td><button type="button" class="btn btn-sm btn-danger k-rate-remove" aria-label="{{ trans('general.delete') }}"><i class="fas fa-times" aria-hidden="true"></i></button></td>';
                    tbody.appendChild(row);
                    i++;
                });
            })();
        </script>
    @endif
@stop
