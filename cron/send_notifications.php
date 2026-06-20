<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\MailService;

$pdo = Database::connection();
if (!$pdo) {
    fwrite(STDERR, "MySQL is not available.\n");
    exit(1);
}

$statement = $pdo->query(
    'SELECT n.id, n.title, n.message, u.email
     FROM notifications n JOIN users u ON u.id = n.user_id
     WHERE n.sent_at IS NULL ORDER BY n.created_at ASC LIMIT 100'
);
$update = $pdo->prepare('UPDATE notifications SET sent_at = NOW() WHERE id = :id');
$mailer = new MailService();
$sent = 0;
foreach ($statement->fetchAll() as $notification) {
    if ($mailer->send($notification['email'], $notification['title'], $notification['message'])) {
        $update->execute(['id' => $notification['id']]);
        $sent++;
    }
}
fwrite(STDOUT, sprintf("[%s] Sent %d notification(s).\n", date(DATE_ATOM), $sent));
