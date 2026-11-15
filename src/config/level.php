<?php
// Difficulty level: stored in session only. No vulnerable code here.
const LEVELS = ['easy', 'medium', 'hard'];

function get_level(): string
{
    $level = $_SESSION['level'] ?? 'easy';
    return (is_string($level) && in_array($level, LEVELS, true)) ? $level : 'easy';
}

function set_level(string $level): bool
{
    if (!in_array($level, LEVELS, true)) {
        return false;
    }
    $_SESSION['level'] = $level;
    return true;
}
