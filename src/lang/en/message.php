<?php

return [
    'no_token_provided' => 'No token provided',
    'invalid_or_expired_token' => 'Invalid or expired token',
    'account_locked' => "Your account has been locked due to multiple failed login attempts.\n"
        .'Please try again later or ask the facility administrator to unlock your account.',
    'login_success' => 'Login successful',
    'login_failed' => 'Incorrect email address or password',
    'login_failed_multiple_times' => 'If you have forgotten your password, '
        . 'please reset it using the Forgot Password button.',
    'user_not_found' => 'User not found',
    'user_id_or_email_incorrect' => 'Your user ID or email address is incorrect.',
    'invalid_role' => 'Error! Invalid role',
    'invalid_change_password' => 'Error! Invalid change password',
    'current_password_incorrect' => 'Your current password is incorrect.',
    'unauthorized' => 'User is not authorized to perform this action',
    'email_invited' => 'The email address you are inviting',
    'shift' => [
        'invalid_user' => 'Invalid user',
        'invalid_contract' => 'User does not have a valid contract',
        'both_shift_type_and_status' => 'cannot select shift_type_id and status at the same time.',
        'duplicate_user_shift' => 'User already has a shift on this date',
    ],
    'model_not_found' => 'The model not found',
    'notification_dashboard' => [
        'accepted' => 'You have accepted the work request for :date.',
        'canceled_by_manager' => 'The work on :date has been canceled.',
        'day_off' => ':date has been marked as a requested day off.',
        'paid_leave' => ':date has been marked as paid leave.',
        'request_received' => 'You have received a work request for :date.',
        'request_rejected' => 'You have rejected the work request for :date.',
        'request_cancelled' => 'The work request for :date has been canceled.',
        'sent_request' => 'You have sent a work request to :name for :date.',
        'cancelled_request' => 'You have canceled the work request to :name for :date.',
        'declined_by_staff' => ':name declined the work request for :date.',
    ],
    'must_include_all_weekday' => 'must include all weekdays (Mon-Sun)',
    'overlap_time' => 'Care plan times overlap. Please adjust the times.',
];
