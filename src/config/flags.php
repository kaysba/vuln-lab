<?php
// Flags are computed on the fly from FLAG_SECRET. They are never stored anywhere.
// No vulnerable code here.
const MODULES = [
    'login-sqli'    => 'Login (SQL injection)',
    'idor'          => 'Profiles & messages (IDOR)',
    'xss-reflected' => 'Search (reflected XSS)',
    'xss-stored'    => 'Posts (stored XSS)',
    'admin-access'  => 'Admin (broken access control)',
];

function flag_secret(): string
{
    $secret = getenv('FLAG_SECRET');
    if (!is_string($secret) || $secret === '') {
        http_response_code(500);
        exit('Server configuration error');
    }
    return $secret;
}

function flag_for(string $module, string $level): string
{
    if (!array_key_exists($module, MODULES) || !in_array($level, LEVELS, true)) {
        throw new InvalidArgumentException('Unknown module or level');
    }
    return 'FLAG{' . substr(hash_hmac('sha256', $module . ':' . $level, flag_secret()), 0, 24) . '}';
}

function find_flag(string $submitted): ?array
{
    $submitted = trim($submitted);
    $found = null;
    // No early exit: every flag is compared.
    foreach (array_keys(MODULES) as $module) {
        foreach (LEVELS as $level) {
            if (hash_equals(flag_for($module, $level), $submitted) && $found === null) {
                $found = ['module' => $module, 'level' => $level];
            }
        }
    }
    return $found;
}

function solved_map(): array
{
    $map = [];
    $res = db()->query('SELECT module, level, solved_at FROM progress');
    if ($res) {
        foreach ($res->fetch_all(MYSQLI_ASSOC) as $row) {
            $map[$row['module']][$row['level']] = $row['solved_at'];
        }
    }
    return $map;
}

function mark_solved(string $module, string $level): bool
{
    if (!array_key_exists($module, MODULES) || !in_array($level, LEVELS, true)) {
        return false;
    }
    $stmt = db()->prepare('INSERT IGNORE INTO progress (module, level) VALUES (?, ?)');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $module, $level);
    $stmt->execute();
    $new = $stmt->affected_rows === 1;
    $stmt->close();
    return $new;
}

function flag_secret_is_default(): bool
{
    return strncmp((string) getenv('FLAG_SECRET'), 'change_me', 9) === 0;
}
