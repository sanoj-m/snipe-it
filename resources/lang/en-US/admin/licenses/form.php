<?php

return [

    'asset' => 'Asset',
    'checkin' => 'Checkin',
    'create' => 'Create License',
    'expiration' => 'Expiration Date',
    'license_key' => 'Product Key',
    'maintained' => 'Maintained',
    'name' => 'Software Name',
    'no_depreciation' => 'Do Not Depreciate',
    'perpetual' => 'Perpetual License',
    'perpetual_help' => 'Perpetual licenses never expire: the expiration date is ignored and cleared on save.',
    'purchase_order' => 'Purchase Order Number',
    'reassignable' => 'Reassignable',
    'remaining_seats' => 'Remaining Seats',
    'seats' => 'Seats',
    'termination_date' => 'Termination Date',
    // [killa-v2] KCP-075 BEGIN: relabel "Licensed to Email" -> "Admin Portal
    // Email" (subscription products are managed under an admin portal account).
    // DB column and form field name (license_email) unchanged.
    'to_email' => 'Admin Portal Email',
    // [killa-v2] KCP-075 END (upstream value: 'Licensed to Email')
    'to_name' => 'Licensed To',
    'update' => 'Update License',
    'checkout_help' => 'You must check a license out to a hardware asset or a person. You can select both, but the owner of the asset must match the person you\'re checking the asset out to.',
];
