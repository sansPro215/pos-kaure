<?php

declare(strict_types=1);

if (!function_exists('get_client_ip')) {
    /**
     * Get the real client IP address even when hosted behind Cloudflare,
     * reverse proxies (Nginx, Apache, LiteSpeed), cPanel, or load balancers.
     */
    function get_client_ip(): string
    {
        // 1. Cloudflare real visitor IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // 2. X-Real-IP (Nginx / OpenResty proxy)
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $realIp = trim((string)$_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
        }

        // 3. X-Forwarded-For (Can be multiple comma-separated IPs: client, proxy1, proxy2)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $rawList = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
            // Check for first valid public client IP
            foreach ($rawList as $rawIp) {
                $rawIp = trim($rawIp);
                // Strip port if exists (e.g. 192.0.2.1:8080)
                if (preg_match('/^([0-9.]+):[0-9]+$/', $rawIp, $m)) {
                    $rawIp = $m[1];
                }
                if (filter_var($rawIp, FILTER_VALIDATE_IP)) {
                    // Prioritize public IP
                    if (filter_var($rawIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $rawIp;
                    }
                }
            }
            // If all were private or local proxies, return first valid IP
            foreach ($rawList as $rawIp) {
                $rawIp = trim($rawIp);
                if (preg_match('/^([0-9.]+):[0-9]+$/', $rawIp, $m)) {
                    $rawIp = $m[1];
                }
                if (filter_var($rawIp, FILTER_VALIDATE_IP)) {
                    return $rawIp;
                }
            }
        }

        // 4. Client-IP header
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = trim((string)$_SERVER['HTTP_CLIENT_IP']);
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // 5. Fallback: REMOTE_ADDR
        $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($remote === '::1' || $remote === '0.0.0.0') {
            return '127.0.0.1';
        }

        return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '127.0.0.1';
    }
}

if (!function_exists('get_client_device')) {
    /**
     * Parse User-Agent into clean human-readable device and browser description
     */
    function get_client_device(?string $ua = null): string
    {
        $ua = $ua ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($ua)) {
            return 'Perangkat Tidak Dikenal';
        }

        $os = 'OS Lainnya';
        if (preg_match('/windows nt 10/i', $ua)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $os = 'Windows 7';
        } elseif (preg_match('/windows/i', $ua)) {
            $os = 'Windows';
        } elseif (preg_match('/android ([0-9.]+)/i', $ua, $m)) {
            $os = 'Android ' . $m[1];
        } elseif (preg_match('/iphone/i', $ua)) {
            $os = 'iPhone (iOS)';
        } elseif (preg_match('/ipad/i', $ua)) {
            $os = 'iPad (iPadOS)';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
        }

        $browser = 'Browser';
        if (preg_match('/edg\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'MS Edge';
        } elseif (preg_match('/chrome\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox\/([0-9.]+)/i', $ua, $m)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari\/([0-9.]+)/i', $ua, $m) && !preg_match('/chrome/i', $ua)) {
            $browser = 'Safari';
        } elseif (preg_match('/opera|opr\//i', $ua)) {
            $browser = 'Opera';
        }

        return "{$browser} ({$os})";
    }
}

if (!function_exists('get_device_type')) {
    /**
     * Determine device category: desktop, mobile, tablet
     */
    function get_device_type(?string $ua = null): string
    {
        $ua = strtolower($ua ?: ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (strpos($ua, 'ipad') !== false || strpos($ua, 'tablet') !== false) {
            return 'tablet';
        }
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            return 'mobile';
        }
        return 'desktop';
    }
}

if (!function_exists('time_ago_id')) {
    /**
     * Relative time string in Indonesian (e.g. "baru saja", "2 menit lalu")
     */
    function time_ago_id(string|int $datetime): string
    {
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        $diff = time() - $timestamp;

        if ($diff < 10) {
            return 'baru saja';
        }
        if ($diff < 60) {
            return $diff . ' detik lalu';
        }
        $minutes = round($diff / 60);
        if ($minutes < 60) {
            return $minutes . ' menit lalu';
        }
        $hours = round($diff / 3600);
        if ($hours < 24) {
            return $hours . ' jam lalu';
        }
        $days = round($diff / 86400);
        if ($days < 7) {
            return $days . ' hari lalu';
        }
        return date('d/m/Y H:i', $timestamp);
    }
}
