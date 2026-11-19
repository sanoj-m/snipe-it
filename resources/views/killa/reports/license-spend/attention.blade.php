@extends('layouts/default')

{{-- [license-spend addon] Attention Needed: data-quality and renewal-risk flags --}}

@section('title')
    {{ trans('killa.license_spend.attention_title') }}
@parent
@stop

@section('content')
    <x-container>
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('killa.license_spend.attention_title') }}</h2>
            </div>
            <div class="box-body">

                @include('killa/reports/license-spend/_nav')

                @if ($items->isEmpty())
                    <p class="text-muted">{{ trans('killa.all_clear') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>{{ trans('general.license') }}</th>
                                <th>{{ trans('general.category') }}</th>
                                <th>{{ trans('killa.license_spend.reasons') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($items as $item)
                                <tr>
                                    <td><a href="{{ route('licenses.show', $item['license']->id) }}">{{ $item['license']->name }}</a></td>
                                    <td>{{ $item['license']->category?->name }}</td>
                                    <td>
                                        @foreach ($item['reasons'] as $reason)
                                            <span class="label label-warning">{{ trans('killa.license_spend.reason_'.$reason) }}</span>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </x-container>
@stop
