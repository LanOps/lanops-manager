<?php

return [

    // Set in AppServiceProvider: the gateways whose keys are configured
    'available_gateways' => [],

    // How each gateway is shown on the checkout page
    'gateways' => [
        'stripe' => [
            'displayName' => 'Debit/Credit Card',
            'note'        => 'You may be redirected offsite for this payment.',
        ],
    ],

];
