<?php

return [

    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

    // These roles cannot be deleted or renamed.
    'protected_roles' => ['super-admin', 'student'],

    'groups' => [
        'users' => 'User Management',
        'roles' => 'Roles',
        'settings' => 'Settings',
        'activity' => 'Activity Log',
    ],

    // To add new permissions, define them here and run PermissionSeeder.
    'permissions' => [
        'users.view' => 'View User List',
        'users.create' => 'Create New Users / Send Invitations',
        'users.edit' => 'Edit Users and Activate/Deactivate Accounts',
        'users.delete' => 'Delete and Restore Users',
        'roles.manage' => 'Manage Roles and Permissions',
        'settings.manage' => 'Manage Settings (Enable/Disable Registration)',
        'activity.view' => 'View Activity Logs',
    ],
];
