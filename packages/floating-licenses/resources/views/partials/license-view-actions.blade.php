{{-- Floating-licenses addon: bulk user actions dropdown + per-license user
     CSV import modal. Included from the core licenses/view.blade.php
     x-info-panel buttons slot; expects $floatingMasterOn and $license. --}}
@if (($floatingMasterOn && (Gate::allows('floating_licenses.allocate') || Gate::allows('floating_licenses.release'))) || Gate::allows('view', $license) || Gate::allows('checkout', $license))
    <div class="dropdown" style="display: inline-block;">
        <button type="button" class="btn btn-primary btn-sm dropdown-toggle hidden-print" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <x-icon type="users" class="fa-fw"/>
            {{ trans('floating-licenses::floating.bulk_actions') }} <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
            @if ($floatingMasterOn)
                @can('floating_licenses.allocate')
                    <li>
                        <a href="{{ route('floating-licenses.license.bulk-add.form', $license) }}">
                            {{ trans('floating-licenses::floating.bulk_add') }}
                        </a>
                    </li>
                @endcan
                @can('floating_licenses.release')
                    <li>
                        <a href="{{ route('floating-licenses.license.bulk-remove.form', $license) }}">
                            {{ trans('floating-licenses::floating.bulk_remove') }}
                        </a>
                    </li>
                @endcan
            @endif
            @can('view', $license)
                <li>
                    <a href="{{ route('floating-licenses.license.users-export', $license) }}">
                        {{ trans('floating-licenses::floating.export_users') }}
                    </a>
                </li>
            @endcan
            @can('checkout', $license)
                <li>
                    <a href="#" data-toggle="modal" data-target="#importLicenseUsersModal">
                        {{ trans('floating-licenses::floating.import_users') }}
                    </a>
                </li>
            @endcan
        </ul>
    </div>
@endif

@can('checkout', $license)
    <div class="modal fade" id="importLicenseUsersModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('floating-licenses.license.users-import', $license) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">{{ trans('floating-licenses::floating.import_users') }}</h4>
                    </div>
                    <div class="modal-body">
                        <p class="help-block">{{ trans('floating-licenses::floating.import_users_help') }}</p>
                        <input type="file" name="user_list" accept=".csv,.txt" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ trans('floating-licenses::floating.import_users') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
