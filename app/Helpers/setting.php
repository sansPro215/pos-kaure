<?php

use App\Repositories\SettingRepository;

if (!function_exists('shop_setting')) {
    /**
     * Get a setting value or all settings
     */
    function shop_setting(?string $key = null, mixed $default = null): mixed
    {
        $settings = SettingRepository::get();
        if ($key === null) {
            return $settings;
        }
        return $settings[$key] ?? $default;
    }
}

if (!function_exists('setting')) {
    /**
     * Alias for shop_setting()
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        return shop_setting($key, $default);
    }
}

if (!function_exists('shop_name')) {
    /**
     * Get the dynamic shop name configured in settings
     */
    function shop_name(): string
    {
        $name = shop_setting('shop_name');
        return !empty($name) ? (string)$name : 'Warung Kaure';
    }
}

if (!function_exists('get_theme_palettes')) {
    /**
     * Get list of predefined curated color palettes with full system tokens
     */
    function get_theme_palettes(): array
    {
        return [
            'coffee' => [
                'id' => 'coffee',
                'name' => 'Cokelat Kopi (Barista)',
                'description' => 'Nuansa barista klasik, hangat, dan ramah',
                'primary' => '#6F4E37',
                'primary_rgb' => '111, 78, 55',
                'primary_dark' => '#533A29',
                'primary_light' => '#EBDED5',
                'bg' => '#F8F5F2',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#FCFAF8',
                'border' => '#E8E1DC',
                'text' => '#2A211C',
                'text_muted' => '#73665E',
                'dark_primary' => '#B98A68',
                'dark_primary_dark' => '#8F6547',
                'dark_primary_light' => '#3D322A',
                'dark_bg' => '#171411',
                'dark_surface' => '#211C18',
                'dark_surface_elevated' => '#2A231E',
                'dark_border' => '#3B312A',
                'dark_text' => '#F5EFEA',
                'dark_text_muted' => '#B9AAA0',
            ],
            'forest' => [
                'id' => 'forest',
                'name' => 'Emerald Sage (Hijau Hutan)',
                'description' => 'Nuansa alam, segar, herbal, dan tenang',
                'primary' => '#2E6F40',
                'primary_rgb' => '46, 111, 64',
                'primary_dark' => '#1E4E2B',
                'primary_light' => '#D8ECD9',
                'bg' => '#F2F7F3',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#F7FAF8',
                'border' => '#D4E5D8',
                'text' => '#162E1D',
                'text_muted' => '#4E7055',
                'dark_primary' => '#52A669',
                'dark_primary_dark' => '#3A7C4C',
                'dark_primary_light' => '#1C3322',
                'dark_bg' => '#0E1711',
                'dark_surface' => '#142218',
                'dark_surface_elevated' => '#1C2E22',
                'dark_border' => '#263D2E',
                'dark_text' => '#F0F7F2',
                'dark_text_muted' => '#93B89C',
            ],
            'ocean' => [
                'id' => 'ocean',
                'name' => 'Classic Navy (Biru Samudera)',
                'description' => 'Profesional, modern, bersih, dan terpercaya',
                'primary' => '#1D5288',
                'primary_rgb' => '29, 82, 136',
                'primary_dark' => '#123860',
                'primary_light' => '#D9E7F6',
                'bg' => '#F1F5F9',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#F7FAFC',
                'border' => '#D0DFEE',
                'text' => '#102235',
                'text_muted' => '#4A6987',
                'dark_primary' => '#5C94CC',
                'dark_primary_dark' => '#3C6D9D',
                'dark_primary_light' => '#192A3E',
                'dark_bg' => '#0C141F',
                'dark_surface' => '#121C2A',
                'dark_surface_elevated' => '#19273A',
                'dark_border' => '#22384F',
                'dark_text' => '#F0F5FA',
                'dark_text_muted' => '#8EADC9',
            ],
            'burgundy' => [
                'id' => 'burgundy',
                'name' => 'Velvet Crimson (Merah Burgundy)',
                'description' => 'Mewah, hangat, berani, dan berkelas',
                'primary' => '#882434',
                'primary_rgb' => '136, 36, 52',
                'primary_dark' => '#611522',
                'primary_light' => '#F7DDE2',
                'bg' => '#FAF3F4',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#FCF7F8',
                'border' => '#ECCFD5',
                'text' => '#331016',
                'text_muted' => '#784D55',
                'dark_primary' => '#BD5969',
                'dark_primary_dark' => '#8E3745',
                'dark_primary_light' => '#38191E',
                'dark_bg' => '#170D10',
                'dark_surface' => '#221216',
                'dark_surface_elevated' => '#2D191F',
                'dark_border' => '#3E232A',
                'dark_text' => '#FAF1F3',
                'dark_text_muted' => '#BA929A',
            ],
            'charcoal' => [
                'id' => 'charcoal',
                'name' => 'Slate Charcoal (Abu Modern)',
                'description' => 'Monokrom modern, bersih, minimalis, dan industrial',
                'primary' => '#3A4150',
                'primary_rgb' => '58, 65, 80',
                'primary_dark' => '#252B37',
                'primary_light' => '#E1E4EA',
                'bg' => '#F3F4F6',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#F9FAFB',
                'border' => '#D9DCE3',
                'text' => '#1B1E24',
                'text_muted' => '#5C6472',
                'dark_primary' => '#8E99AF',
                'dark_primary_dark' => '#667085',
                'dark_primary_light' => '#262B35',
                'dark_bg' => '#111318',
                'dark_surface' => '#181B22',
                'dark_surface_elevated' => '#222630',
                'dark_border' => '#313745',
                'dark_text' => '#F2F4F7',
                'dark_text_muted' => '#9AA4B2',
            ],
            'purple' => [
                'id' => 'purple',
                'name' => 'Royal Violet (Ungu Elegan)',
                'description' => 'Kreatif, premium, eksklusif, dan menawan',
                'primary' => '#5C3E8A',
                'primary_rgb' => '92, 62, 138',
                'primary_dark' => '#412965',
                'primary_light' => '#E9E0F6',
                'bg' => '#F5F2F9',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#FAF7FC',
                'border' => '#DFD5EC',
                'text' => '#241639',
                'text_muted' => '#68557F',
                'dark_primary' => '#997DC4',
                'dark_primary_dark' => '#7756A1',
                'dark_primary_light' => '#2D2040',
                'dark_bg' => '#130D1D',
                'dark_surface' => '#1B132A',
                'dark_surface_elevated' => '#251B38',
                'dark_border' => '#362750',
                'dark_text' => '#F5F0FA',
                'dark_text_muted' => '#A997C2',
            ],
            'terracotta' => [
                'id' => 'terracotta',
                'name' => 'Warm Terracotta (Oranye Bata)',
                'description' => 'Nuansa senja, bersahabat, estetik, dan ceria',
                'primary' => '#A34C24',
                'primary_rgb' => '163, 76, 36',
                'primary_dark' => '#7A3414',
                'primary_light' => '#FBE2D5',
                'bg' => '#FAF4F0',
                'surface' => '#FFFFFF',
                'surface_elevated' => '#FCF8F5',
                'border' => '#EFDFD5',
                'text' => '#38190B',
                'text_muted' => '#825741',
                'dark_primary' => '#D47E56',
                'dark_primary_dark' => '#A95832',
                'dark_primary_light' => '#3B2217',
                'dark_bg' => '#18100B',
                'dark_surface' => '#231710',
                'dark_surface_elevated' => '#2F1F17',
                'dark_border' => '#422C21',
                'dark_text' => '#FAF2ED',
                'dark_text_muted' => '#C49E8A',
            ],
        ];
    }
}

if (!function_exists('get_active_theme')) {
    /**
     * Resolve active theme colors (preset or custom hex) with complete harmonious system tokens
     */
    function get_active_theme(): array
    {
        $themeKey = shop_setting('theme_color', 'coffee');
        $palettes = get_theme_palettes();

        if (isset($palettes[$themeKey])) {
            return $palettes[$themeKey];
        }

        // Custom hex color support (#rrggbb)
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $themeKey)) {
            $hex = ltrim($themeKey, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));

            $darkR = (int)max(0, $r * 0.75);
            $darkG = (int)max(0, $g * 0.75);
            $darkB = (int)max(0, $b * 0.75);

            $lightR = (int)min(255, $r + (255 - $r) * 0.85);
            $lightG = (int)min(255, $g + (255 - $g) * 0.85);
            $lightB = (int)min(255, $b + (255 - $b) * 0.85);

            $bgR = (int)min(255, 255 * 0.95 + $r * 0.05);
            $bgG = (int)min(255, 255 * 0.95 + $g * 0.05);
            $bgB = (int)min(255, 255 * 0.95 + $b * 0.05);

            $borderR = (int)min(255, 255 * 0.83 + $r * 0.17);
            $borderG = (int)min(255, 255 * 0.83 + $g * 0.17);
            $borderB = (int)min(255, 255 * 0.83 + $b * 0.17);

            return [
                'id' => 'custom',
                'name' => 'Kustom (' . strtoupper($themeKey) . ')',
                'description' => 'Palet warna kustom pilihan Anda',
                'primary' => $themeKey,
                'primary_rgb' => "{$r}, {$g}, {$b}",
                'primary_dark' => sprintf("#%02x%02x%02x", $darkR, $darkG, $darkB),
                'primary_light' => sprintf("#%02x%02x%02x", $lightR, $lightG, $lightB),
                'bg' => sprintf("#%02x%02x%02x", $bgR, $bgG, $bgB),
                'surface' => '#FFFFFF',
                'surface_elevated' => '#FAF8F7',
                'border' => sprintf("#%02x%02x%02x", $borderR, $borderG, $borderB),
                'text' => sprintf("#%02x%02x%02x", (int)($r * 0.2), (int)($g * 0.2), (int)($b * 0.2)),
                'text_muted' => sprintf("#%02x%02x%02x", (int)($r * 0.5), (int)($g * 0.5), (int)($b * 0.5)),
                'dark_primary' => sprintf("#%02x%02x%02x", min(255, (int)($r * 1.25)), min(255, (int)($g * 1.25)), min(255, (int)($b * 1.25))),
                'dark_primary_dark' => $themeKey,
                'dark_primary_light' => sprintf("#%02x%02x%02x", (int)($r * 0.25), (int)($g * 0.25), (int)($b * 0.25)),
                'dark_bg' => sprintf("#%02x%02x%02x", (int)($r * 0.08), (int)($g * 0.08), (int)($b * 0.08)),
                'dark_surface' => sprintf("#%02x%02x%02x", (int)($r * 0.13), (int)($g * 0.13), (int)($b * 0.13)),
                'dark_surface_elevated' => sprintf("#%02x%02x%02x", (int)($r * 0.18), (int)($g * 0.18), (int)($b * 0.18)),
                'dark_border' => sprintf("#%02x%02x%02x", (int)($r * 0.26), (int)($g * 0.26), (int)($b * 0.26)),
                'dark_text' => '#F5F5F5',
                'dark_text_muted' => '#B0B0B0',
            ];
        }

        return $palettes['coffee'];
    }
}

if (!function_exists('render_theme_css')) {
    /**
     * Render dynamic CSS variables for active theme to transform the entire system
     */
    function render_theme_css(): string
    {
        $theme = get_active_theme();
        return "<style id=\"wk-dynamic-theme\">
    :root {
        --wk-primary: {$theme['primary']};
        --wk-primary-rgb: {$theme['primary_rgb']};
        --wk-primary-dark: {$theme['primary_dark']};
        --wk-primary-light: {$theme['primary_light']};
        --wk-bg: {$theme['bg']};
        --wk-surface: {$theme['surface']};
        --wk-surface-elevated: {$theme['surface_elevated']};
        --wk-border: {$theme['border']};
        --wk-text: {$theme['text']};
        --wk-text-muted: {$theme['text_muted']};

        /* Bootstrap dynamic overrides */
        --bs-primary: {$theme['primary']};
        --bs-primary-rgb: {$theme['primary_rgb']};
        --bs-primary-bg-subtle: {$theme['primary_light']};
        --bs-primary-border-subtle: {$theme['primary_light']};
        --bs-primary-text-emphasis: {$theme['primary_dark']};
        --bs-link-color: {$theme['primary']};
        --bs-link-hover-color: {$theme['primary_dark']};
        --bs-focus-ring-color: rgba({$theme['primary_rgb']}, 0.25);
        --bs-body-bg: {$theme['bg']};
        --bs-body-color: {$theme['text']};
        --bs-border-color: {$theme['border']};
    }
    [data-bs-theme=\"dark\"] {
        --wk-primary: {$theme['dark_primary']};
        --wk-primary-rgb: {$theme['primary_rgb']};
        --wk-primary-dark: {$theme['dark_primary_dark']};
        --wk-primary-light: {$theme['dark_primary_light']};
        --wk-bg: {$theme['dark_bg']};
        --wk-surface: {$theme['dark_surface']};
        --wk-surface-elevated: {$theme['dark_surface_elevated']};
        --wk-border: {$theme['dark_border']};
        --wk-text: {$theme['dark_text']};
        --wk-text-muted: {$theme['dark_text_muted']};

        /* Bootstrap dynamic overrides for dark mode */
        --bs-primary: {$theme['dark_primary']};
        --bs-primary-rgb: {$theme['primary_rgb']};
        --bs-primary-bg-subtle: {$theme['dark_primary_light']};
        --bs-primary-border-subtle: {$theme['dark_border']};
        --bs-primary-text-emphasis: {$theme['dark_primary']};
        --bs-link-color: {$theme['dark_primary']};
        --bs-link-hover-color: {$theme['primary_light']};
        --bs-focus-ring-color: rgba({$theme['primary_rgb']}, 0.35);
        --bs-body-bg: {$theme['dark_bg']};
        --bs-body-color: {$theme['dark_text']};
        --bs-border-color: {$theme['dark_border']};
    }
</style>\n";
    }
}
