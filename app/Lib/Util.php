<?php

namespace App\Lib;

class Util
{
    public static function e(?string $str): string
    {
        return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    public static function money($amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function consumeFlash(): array
    {
        $msgs = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $msgs;
    }

    public static function rateLimit(string $key, int $max, int $windowSeconds): bool
    {
        $config = require __DIR__ . '/../../config/config.php';
        $dir = $config['rate_limit_dir'];
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
        $now = time();
        $bucket = ['start' => $now, 'count' => 0];
        if (file_exists($file)) {
            $data = json_decode((string)@file_get_contents($file), true);
            if (is_array($data) && isset($data['start'], $data['count'])) {
                $bucket = $data;
            }
        }
        if ($now - $bucket['start'] > $windowSeconds) {
            $bucket = ['start' => $now, 'count' => 0];
        }
        if ($bucket['count'] >= $max) {
            return false;
        }
        $bucket['count']++;
        @file_put_contents($file, json_encode($bucket));
        return true;
    }

    public static function generateInviteCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return 'BM-' . $code;
    }

    public static function simplifyDebts(array $balances): array
    {
        // $balances: [user_id => net_amount], positive means others owe them
        $creditors = [];
        $debtors = [];
        foreach ($balances as $uid => $amt) {
            $amt = round($amt, 2);
            if ($amt > 0.009) $creditors[$uid] = $amt;
            elseif ($amt < -0.009) $debtors[$uid] = -$amt; // store as positive debt
        }
        arsort($creditors);
        arsort($debtors);
        $transfers = [];
        while (!empty($creditors) && !empty($debtors)) {
            $cid = array_key_first($creditors);
            $did = array_key_first($debtors);
            $cAmt = $creditors[$cid];
            $dAmt = $debtors[$did];
            $x = min($cAmt, $dAmt);
            $transfers[] = ['from' => $did, 'to' => $cid, 'amount' => round($x, 2)];
            $creditors[$cid] -= $x;
            $debtors[$did] -= $x;
            if ($creditors[$cid] <= 0.009) unset($creditors[$cid]); else { arsort($creditors); }
            if ($debtors[$did] <= 0.009) unset($debtors[$did]); else { arsort($debtors); }
        }
        return $transfers;
    }
}
