<?php

namespace App\Lib;

class Validator
{
    public array $errors = [];

    public function required(array $fields, array $data): void
    {
        foreach ($fields as $f) {
            if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
                $this->errors[$f] = 'Required';
            }
        }
    }

    public function email(string $field, string $value): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email';
        }
    }

    public function decimal(string $field, $value, int $scale = 2): void
    {
        if (!is_numeric($value)) {
            $this->errors[$field] = 'Invalid number';
            return;
        }
        $v = number_format((float)$value, $scale, '.', '');
        if (!preg_match('/^-?\d+\.\d{' . $scale . '}$/', $v)) {
            $this->errors[$field] = 'Too many decimals';
        }
    }

    public function maxLen(string $field, string $value, int $len): void
    {
        if (mb_strlen($value) > $len) {
            $this->errors[$field] = 'Max length ' . $len;
        }
    }

    public function url(string $field, string $value): void
    {
        if ($value === '') return;
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = 'Invalid URL';
            return;
        }
        $parts = parse_url($value);
        $scheme = $parts['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            $this->errors[$field] = 'URL must be http/https';
        }
    }

    public function ok(): bool
    {
        return empty($this->errors);
    }
}
