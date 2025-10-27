<?php

return [
    'auth' => [
        'max_login_attempts_for_manager' => env('MAX_LOGIN_ATTEMPTS_FOR_MANAGER', 5),
        'max_login_attempts_for_non_manager' => env('MAX_LOGIN_ATTEMPTS_FOR_NON_MANAGER', 10),
        'login_cache_time_to_live' => 30,
        'cache_key_prefix' => 'user_login_report_'
    ],
    'notification_dashboard_months' => env('NOTIFICATION_DASHBOARD_MONTHS', 2),
    'maximum_working_hours' => env('MAXIMUM_WORKING_HOURS', 160),
    'sunday_in_mysql' => '1',
    'saturday_in_mysql' => '7',
    'email_send_error_log' => explode(',', env('EMAIL_SEND_ERROR_LOG', 'sisuta1@example.com')),
    'chunk_log' => env('CHUNK_LOG', 1000),
    'title_mail_error_log' => env('TITLE_MAIL_ERROR_LOG', '[Error Report] System Exception Detected'),
    'maximum_attachments_for_care_plan_comment' => env('MAXIMUM_ATTACHMENTS_FOR_CARE_PLAN_COMMENT', 8),
    'maximum_length_for_care_plan_comment' => env('MAXIMUM_LENGTH_FOR_CARE_PLAN_COMMENT', 350),
    'maximum_string_length' => 255,
    'maximum_integer_value' => 2147483647,
    'reset_password_screen_path' => env('RESET_PASSWORD_SCREEN_PATH', 'reset-password'),
    'system_email_domain' => env('SYSTEM_EMAIL_DOMAIN', '@sisuta-system.com'),
];
