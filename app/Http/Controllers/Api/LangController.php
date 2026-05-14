<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LangController extends Controller
{
    private const SUPPORTED_LOCALES = ['en', 'nl', 'fr', 'de'];
    private const FILES = ['public', 'auth', 'user'];

    public function show(string $locale): JsonResponse
    {
        if (!in_array($locale, self::SUPPORTED_LOCALES)) {
            $locale = 'en';
        }

        $translations = [];

        foreach (self::FILES as $file) {
            $path = lang_path("{$locale}/{$file}.php");
            if (file_exists($path)) {
                $data = require $path;
                foreach ($data as $key => $value) {
                    $translations["{$file}.{$key}"] = $value;
                }
            }
        }

        return response()->json($translations);
    }
}
