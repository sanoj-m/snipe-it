{{-- Floating-licenses addon section (master switch in Admin > Settings > General).
     Included from the core licenses/edit.blade.php form; expects $snipeSettings
     and $item. --}}
@if (($snipeSettings->floating_licenses_enabled ?? '0') == '1')
    @php
        $floatingConfig = $item->id
            ? \SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig::where('license_id', $item->id)->first()
            : null;
    @endphp

    <x-form.checkbox-row
        name="floating_enabled"
        :section_label="trans('floating-licenses::floating.section')"
        :label="trans('floating-licenses::floating.enable_on_license')"
        :checked="(bool) old('floating_enabled', $floatingConfig ? 1 : 0)"
        :help_text="trans('floating-licenses::floating.enable_help')"
    />

    <x-form.row
        :label="trans('floating-licenses::floating.cost_mode')"
        name="floating_cost_mode"
        input_div_class="col-md-7"
    >
        <x-slot:input>
            <x-input.select
                name="floating_cost_mode"
                id="floating_cost_mode"
                :options="[
                    'pool_slot' => trans('floating-licenses::floating.cost_mode_pool_slot'),
                    'active_user' => trans('floating-licenses::floating.cost_mode_active_user'),
                ]"
                :selected="old('floating_cost_mode', $floatingConfig?->cost_mode ?? 'active_user')"
                style="width:350px;"
                aria-label="floating_cost_mode"
            />
        </x-slot:input>
    </x-form.row>

    {{-- Hidden 0 + checkbox 1 so the field always posts and an
         explicit uncheck is distinguishable from a non-form caller. --}}
    <input type="hidden" name="floating_allow_over_allocation" value="0">
    <x-form.checkbox-row
        name="floating_allow_over_allocation"
        :label="trans('floating-licenses::floating.allow_over_allocation')"
        :checked="(bool) old('floating_allow_over_allocation', $floatingConfig?->allow_over_allocation ?? 1)"
    />
@endif
