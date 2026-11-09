<?php

return [
    // এই role-গুলো মোছা বা rename করা যাবে না
    'protected_roles' => ['super-admin', 'student'],

    'groups' => [
        'users' => 'User Management',
        'roles' => 'Roles',
        'settings' => 'Settings',
        'activity' => 'Activity Log',
    ],

    // নতুন permission লাগলে শুধু এখানে যোগ করে PermissionSeeder চালাবেন
    'permissions' => [
        'users.view' => 'User তালিকা দেখা',
        'users.create' => 'নতুন User খোলা / Invite পাঠানো',
        'users.edit' => 'User edit ও Activate/Deactivate',
        'users.delete' => 'User delete ও restore',
        'roles.manage' => 'Role ও permission ম্যানেজ',
        'settings.manage' => 'Settings (registration চালু/বন্ধ)',
        'activity.view' => 'Activity log দেখা',
    ],
];
