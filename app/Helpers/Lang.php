<?php

namespace App\Helpers;

class Lang
{
    private static $language = 'tr'; // Varsayılan dil
    private static $messagesCache = []; // Dil dosyalarının cache'i

    public static function setLanguage($lang)
    {
        self::$language = $lang;
    }

    public static function importLang($langGroup)
    {
        if (!isset(self::$messagesCache[self::$language])) {
            self::loadLanguageFile();
        }

        return self::$messagesCache[self::$language][$langGroup] ?? [];
    }

    private static function loadLanguageFile()
    {
        $filePath = __DIR__ . "/../Lang/" . self::$language . ".php";

        if (!file_exists($filePath)) {
            throw new \Exception("Language file not found for: " . self::$language);
        }

        self::$messagesCache[self::$language] = include $filePath;
    }
}
