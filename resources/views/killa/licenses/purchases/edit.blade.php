@extends('layouts/default')

{{-- [license-spend addon] Edit a purchase/renewal --}}

@section('title')
    {{ trans('killa.license_spend.edit_purchase') }} — {{ $license->name }}
@parent
@stop

@section('content')
    <x-container class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('killa.license_spend.edit_purchase') }} — {{ $license->name }}</h2>
            </div>
            <form method="POST" action="{{ route('licenses.purchases.update', [$license->id, $purchase->id]) }}"
                  enctype="multipart/form-data" class="form-horizontal">
                @method('PUT')
                <div class="box-body">
                    @include('killa/licenses/purchases/_form')
                </div>
                <div class="box-footer text-right">
                    <a href="{{ route('licenses.show', $license->id) }}" class="btn btn-link">{{ trans('general.cancel') }}</a>
                    <button type="submit" class="btn btn-primary"><x-icon type="checkmark" /> {{ trans('general.save') }}</button>
                </div>
            </form>
        </div>
    </x-container>
@stop
