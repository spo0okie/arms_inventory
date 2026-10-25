<?php return array (
  'notify.enable' => true,
  'integrations' => 
  array (
    'sms' => 
    array (
      'class' => 'app\\components\\integrations\\providers\\SmsProvider',
      'url' => 'data://text/plain,TEST-OK',
    ),
  ),
  // Обычные тесты работают без RBAC; проверки авторизации временно включают его сами.
  'useRBAC' => false,
  'authorizedView' => false,
);