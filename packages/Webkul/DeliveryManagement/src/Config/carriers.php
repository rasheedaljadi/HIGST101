<?php

return [
    'homedelivery' => [
        'code' => 'homedelivery',
        'title' => 'Home Delivery',
        'description' => 'Home Delivery Shipping',
        'active' => true,
        'default_rate' => '5',
        'type' => 'per_order',
        'class' => 'Webkul\DeliveryManagement\Carriers\HomeDelivery',
    ],

    'deliverypoint' => [
        'code' => 'deliverypoint',
        'title' => 'Delivery Point Pickup',
        'description' => 'Pickup from Hayest Delivery Point',
        'active' => true,
        'default_rate' => '5',
        'type' => 'per_order',
        'class' => 'Webkul\DeliveryManagement\Carriers\DeliveryPoint',
    ],
];
