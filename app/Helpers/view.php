<?php

if (!function_exists('view')) {
    function view($view, $data = [])
    {
        extract($data);
        $viewFile = __DIR__ . '/../../resources/views/' . str_replace('.', '/', $view) . '.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "View not found: " . htmlspecialchars($view);
        }
    }
}

if (!function_exists('getSiteLogo')) {
    function getSiteLogo()
    {
        $conn = getDBConnection();
        // Attempt to fetch from DB. If fails, return default.
        // Using @ to suppress errors if table doesn't exist yet
        $sql = "SELECT setting_value FROM payment_settings WHERE setting_name = 'SITE_LOGO' LIMIT 1";
        try {
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                if (!empty($row['setting_value'])) {
                    // Ensure the path is correct relative to asset base
                    return asset($row['setting_value']);
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return asset('assets/image/w2p.png');
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        // Detect base path dynamically
        $script_name = dirname($_SERVER['SCRIPT_NAME']); // e.g., /wish2padel/public OR /wish2padel
        $script_name = str_replace('\\', '/', $script_name);
        
        // If we are in the public folder physically in URL (not rewritten cleanly yet)
        if (substr($script_name, -7) === '/public') {
             $base_url = substr($script_name, 0, -7);
        } else {
             $base_url = $script_name;
        }
        
        // Remove trailing slashes
        $base_url = rtrim($base_url, '/');
        $path = ltrim($path, '/');
        
        return $base_url . '/' . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        if (!preg_match('/^https?:\/\//', $url)) {
             $url = asset($url);
        }

        header("Location: " . $url);
        
        if (defined('TESTING') && TESTING) {
            return;
        }
        exit;
    }
}
