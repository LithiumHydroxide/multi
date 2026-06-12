<?php
/**
 * Adaptive School Branding Engine
 * Guarantees readability & consistency for ANY chosen color
 */
function getBrandingCSS($schoolId) {
    static $cachedCSS = null;
    if ($cachedCSS !== null) return $cachedCSS;
    if (!$schoolId) return '';

    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT primary_color, secondary_color FROM school_settings WHERE school_id = ?");
    $stmt->execute([$schoolId]);
    $row = $stmt->fetch();

    $primary = !empty($row['primary_color']) ? $row['primary_color'] : '#1e40af';
    $secondary = !empty($row['secondary_color']) ? $row['secondary_color'] : '#f8fafc';

    // Adaptive derivatives
    $primaryDark = adjustHexBrightness($primary, -25);
    $primaryLight = adjustHexBrightness($primary, 35);
    $primaryBg = $primary . '1A';      // ~10% opacity
    $primaryBgHover = $primary . '29'; // ~16% opacity
    $sidebarBg = adjustHexBrightness($primary, -75);
    $textOnPrimary = getContrastColor($primary);

    // Build CSS string (we will inject via JS so it is appended after page styles)
    $css = ":root {\n" .
        "    --primary: {$primary};\n" .
        "    --primary-dark: {$primaryDark};\n" .
        "    --primary-light: {$primaryLight};\n" .
        "    --primary-bg: {$primaryBg};\n" .
        "    --primary-bg-hover: {$primaryBgHover};\n" .
        "    --text-on-primary: {$textOnPrimary};\n" .
        "    --sidebar-bg: {$sidebarBg};\n" .
        "    --bg: {$secondary};\n" .
        "    --surface: #ffffff;\n" .
        "    --text: #0f172a;\n" .
        "    --text-muted: #64748b;\n" .
        "    --border: #e2e8f0;\n" .
        "}\n" .
        "/* Force consistent readability */\n" .
        ".stat-card h3 { color: var(--text-muted) !important; }\n" .
        ".quick-actions .icon-wrap { background: var(--primary-bg) !important; color: var(--primary) !important; }\n" .
        ".sidebar a.active { background: var(--primary) !important; color: var(--text-on-primary) !important; }\n" .
        ".sidebar a:hover { background: var(--primary-bg-hover) !important; color: var(--primary) !important; }\n" .
        ".badge-premium { background: #f59e0b; color: #000 !important; }\n" .
        ".avatar { background: linear-gradient(135deg, var(--primary), var(--primary-light)) !important; }\n" .
        ".stat-icon { background: var(--primary-bg) !important; color: var(--primary) !important; }\n" .
        "button, .btn, [type='button'], [type='submit'] { background: var(--primary) !important; color: var(--text-on-primary) !important; }\n" .
        "button:hover, .btn:hover { background: var(--primary-dark) !important; }\n";

    // Use JSON encoding to safely escape the CSS for embedding in JS
    $cachedCSS = "<script>(function(){var css = " . json_encode($css) . "; var s = document.createElement('style'); s.type = 'text/css'; s.appendChild(document.createTextNode(css)); document.head.appendChild(s); })();</script>";
    return $cachedCSS;
}

function adjustHexBrightness($hex, $amount) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) return $hex;
    $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + $amount));
    $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + $amount));
    $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + $amount));
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function getContrastColor($hex) {
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luma > 160 ? '#0f172a' : '#ffffff';
}
?>