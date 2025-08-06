<?php

declare(strict_types=1);

namespace App\Service;

class PasswordGenerator
{
    public static function generate(int $length = 16): string
    {
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $specials = '!@#$%^&*()-_=+[]{}<>?';

        $password = [
            $lowercase[\random_int(0, \strlen($lowercase) - 1)],
            $uppercase[\random_int(0, \strlen($uppercase) - 1)],
            $digits[\random_int(0, \strlen($digits) - 1)],
            $specials[\random_int(0, \strlen($specials) - 1)],
        ];

        $all = \sprintf('%s%s%s%s', $lowercase, $uppercase, $digits, $specials);

        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[\random_int(0, \strlen($all) - 1)];
        }

        \shuffle($password);

        return \implode('', $password);
    }
}