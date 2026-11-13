<?php

return [

    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
    ],

    // These roles cannot be deleted or renamed.
    'protected_roles' => ['super-admin', 'admin', 'teacher', 'clerk', 'student', 'guardian', 'staff'],

    'groups' => [
        'users' => 'User Management',
        'roles' => 'Roles',
        'settings' => 'Settings',
        'admission' => 'Admissions',
        'activity' => 'Activity Log',
    ],

    // To add new permissions, define them here and run PermissionSeeder.
    'permissions' => [
        'users.verify' => 'Verify User Contact Details',
        'users.view' => 'View User List',
        'users.create' => 'Create Users and Issue Temporary Passwords',
        'users.edit' => 'Edit Users and Activate/Deactivate Accounts',
        'users.delete' => 'Delete and Restore Users',
        'roles.manage' => 'Manage Roles and Permissions',
        'settings.site.manage' => 'Manage Site Settings',
        'admission.manage' => 'Enable or Disable Admissions',
        'activity.view' => 'View Activity Logs',
    ],
];
