<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $page = 'home', array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(['page' => $page], $params));
}

function asset(string $path): string
{
    return 'assets/' . ltrim($path, '/');
}

function csrf_field(): string
{
    return Csrf::field();
}

function current_user(): ?array
{
    return Auth::user();
}

function has_role(string $role): bool
{
    return Auth::hasRole($role);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function redirect(string $page, array $params = []): never
{
    header('Location: ' . url($page, $params));
    exit;
}

function money(int|float|string|null $amount): string
{
    return 'NT$ ' . number_format((float) $amount);
}

function risk_label(string $risk): string
{
    return [
        'low' => '低風險',
        'suspicious' => '可疑',
        'dangerous' => '危險',
        'prohibited' => '禁止流通',
    ][$risk] ?? '未分類';
}

function status_label(string $status): string
{
    return [
        'draft' => '草稿',
        'pending_review' => '待審核',
        'approved' => '已核准',
        'active' => '競標中',
        'ended' => '已截標',
        'rejected' => '已退回',
        'cancelled' => '已取消',
        'pending_payment' => '待付款',
        'pending_delivery' => '待交付',
        'completed' => '已完成',
        'disputed' => '爭議中',
    ][$status] ?? $status;
}
