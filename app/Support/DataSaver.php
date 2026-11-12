<?php

namespace App\Support;

/**
 * "Data saver" (lite) mode for slow or expensive connections.
 *
 * On when, in this order:
 *   1. the visitor turned it on in the footer (cookie data_saver=1; data_saver=0 turns it off), or
 *   2. the browser sends "Save-Data: on" (Chrome/Android data saver, Opera Mini, …), or
 *   3. the page detected a 2G / slow-3G connection (cookie data_saver_auto=1, set by JS).
 *
 * In lite mode the storefront sends smaller photos, skips decorative images and animations,
 * and uses system fonts.
 */
class DataSaver
{
    public const COOKIE = 'data_saver';
    public const AUTO_COOKIE = 'data_saver_auto';

    private static ?int $requestId = null;
    private static ?bool $on = null;

    public static function on(): bool
    {
        $request = request();
        if (self::$requestId === spl_object_id($request) && self::$on !== null) {
            return self::$on;
        }
        self::$requestId = spl_object_id($request);

        $choice = $request->cookie(self::COOKIE);
        if ($choice === '1' || $choice === '0') {
            return self::$on = $choice === '1';
        }

        return self::$on = strtolower((string) $request->header('Save-Data')) === 'on'
            || $request->cookie(self::AUTO_COOKIE) === '1';
    }

    /**
     * The visitor's own choice: true, false, or null when automatic.
     */
    public static function choice(): ?bool
    {
        $choice = request()->cookie(self::COOKIE);

        return $choice === '1' ? true : ($choice === '0' ? false : null);
    }

    public static function forget(): void
    {
        self::$requestId = null;
        self::$on = null;
    }
}
