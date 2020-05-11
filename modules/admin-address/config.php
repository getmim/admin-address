<?php

return [
    '__name' => 'admin-address',
    '__version' => '0.0.1',
    '__git' => 'git@github.com:getmim/admin-address.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'http://iqbalfn.com/'
    ],
    '__files' => [
        'modules/admin-address' => ['install','update','remove'],
        'theme/admin/address' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'admin' => NULL
            ],
            [
                'lib-address' => NULL
            ],
            [
                'lib-form' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'AdminAddress\\Controller' => [
                'type' => 'file',
                'base' => 'modules/admin-address/controller'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'admin' => [
            'adminAddressSingle' => [
                'path' => [
                    'value' => '/address/(:type)',
                    'params' => [
                        'type' => 'any'
                    ]
                ],
                'method' => 'GET',
                'handler' => 'AdminAddress\\Controller\\Address::single'
            ],
            'adminAddressEdit' => [
                'path' => [
                    'value' => '/address/(:type)/(:id)',
                    'params' => [
                        'type' => 'any',
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET|POST',
                'handler' => 'AdminAddress\\Controller\\Address::edit'
            ],
            'adminAddressRemove' => [
                'path' => [
                    'value' => '/address/(:type)/(:id)/remove',
                    'params' => [
                        'type' => 'any',
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET',
                'handler' => 'AdminAddress\\Controller\\Address::remove'
            ]
        ]
    ],
    'adminSetting' => [
        'menus' => [
            'admin-address' => [
                'label' => 'Address',
                'icon' => '<i class="fas fa-map"></i>',
                'info' => 'Create or modify address list item',
                'perm' => 'manage_address',
                'index' => 1000,
                'options' => [
                    'admin-address' => [
                        'label' => 'Update items',
                        'route' => ['adminAddressSingle', ['type'=>'country']]
                    ]
                ]
            ]
        ]
    ],
    'libForm' => [
        'forms' => [
            'admin.addr-remove' => [
                'noop' => [
                    'rules' => [
                        'required' => true
                    ]
                ]
            ],
            'admin.addr-create' => [
                'name' => [
                    'label' => 'Name',
                    'type' => 'text',
                    'rules' => [
                        'empty' => false,
                        'required' => true 
                    ]
                ]
            ]
        ]
    ]
];