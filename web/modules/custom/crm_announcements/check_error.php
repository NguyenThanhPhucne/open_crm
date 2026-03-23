<?php
$query = \Drupal::database()->select('watchdog', 'w');
$query->fields('w', ['message', 'variables', 'severity', 'timestamp']);
$query->condition('severity', 3, '<=');
$query->orderBy('wid', 'DESC');
$query->range(0, 5);
$results = $query->execute()->fetchAll();

foreach ($results as $log) {
    $variables = unserialize($log->variables);
    $message = strtr($log->message, (array)$variables);
    echo "--- ERROR ---\n";
    echo "Time: " . date('Y-m-d H:i:s', $log->timestamp) . "\n";
    echo "Message: " . strip_tags($message) . "\n\n";
}
