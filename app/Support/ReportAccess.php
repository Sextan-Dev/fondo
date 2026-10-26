<?php

namespace App\Support;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReportAccess
{
    public static function token(string $type, int $id): string
    {
        $key = (string) config('app.report_signing_key');
        if (strlen($key) < 32) {
            throw new \LogicException('Report signing key is missing.');
        }
        $separator = $type === 'donor' ? 'he5' : 'ca68';
        return hash_hmac('sha256', 'fondo-report-'.$type.':'.$id, $key).$separator.$id;
    }

    public static function resolve(Request $request, string $type): ?int
    {
        $token = (string) $request->input('id', '');
        if (strlen($token) > 200) {
            return null;
        }
        $separator = $type === 'donor' ? 'he5' : 'ca68';
        if (preg_match('/^(.*)'.$separator.'([1-9][0-9]*)$/D', $token, $matches)) {
            $id = (int) $matches[2];
        } elseif (ctype_digit($token) && (int) $token > 0) {
            $id = (int) $token;
        } else {
            return null;
        }
        if (hash_equals(self::token($type, $id), $token)) {
            return $id;
        }
        // Existing internal links remain usable by authenticated operators.
        try {
            $user = JWTAuth::setRequest($request)->parseToken()->authenticate();
            if (!$user || !$user->active || !\App\Contact::where('id', $id)->where('company_id', $user->company_id)->exists()) {
                return null;
            }
            if ($type === 'donor' && idDonorsAvailabe() !== 'all'
                && !in_array($id, array_map('intval', explode(',', (string) idDonorsAvailabe())), true)) {
                return null;
            }
            return $id;
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $error) {
            return null;
        }
    }
}
