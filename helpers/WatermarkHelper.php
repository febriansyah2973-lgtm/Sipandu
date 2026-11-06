<?php

if (!function_exists('add_photo_watermark')) {
    /**
     * Add permanent watermark overlay onto uploaded JPEG/PNG photos.
     *
     * @param string $filePath Full absolute/relative file path to the image
     * @param string $petugasNama Name of security personnel
     * @param string $koordinat Coordinates string (e.g. "-6.2000, 106.8166")
     * @param string $locationName Optional location name
     * @return boolean True on success, false on failure
     */
    function add_photo_watermark($filePath, $petugasNama = 'Petugas SIPANDU', $koordinat = '-', $locationName = '') {
        if (!file_exists($filePath) || !function_exists('imagecreatefromstring')) {
            return false;
        }

        $imageContent = @file_get_contents($filePath);
        if ($imageContent === false) {
            return false;
        }

        $im = @imagecreatefromstring($imageContent);
        if (!$im) {
            return false;
        }

        $width = imagesx($im);
        $height = imagesy($im);

        // Watermark Banner Background (Semi-transparent black overlay at bottom)
        $bannerHeight = max(50, (int)($height * 0.12));
        $overlayColor = imagecolorallocatealpha($im, 15, 23, 42, 40); // Dark Navy 84% opacity
        imagefilledrectangle($im, 0, $height - $bannerHeight, $width, $height, $overlayColor);

        // Text Colors
        $textColor = imagecolorallocate($im, 255, 255, 255);
        $accentColor = imagecolorallocate($im, 245, 158, 11); // Amber accent

        $timestamp = date('d M Y - H:i:s') . ' WIB';
        $line1 = "SIPANDU SECURITY SYSTEM | " . $timestamp;
        $line2 = "PETUGAS: " . strtoupper($petugasNama) . ($koordinat !== '-' ? " | GPS: " . $koordinat : '');
        if (!empty($locationName)) {
            $line2 .= " | LOKASI: " . $locationName;
        }

        // Draw Text Lines using imagestring
        $font = 3; // Built-in GD font
        if ($width > 800) {
            $font = 5;
        } elseif ($width > 400) {
            $font = 4;
        }

        $y1 = $height - $bannerHeight + 8;
        $y2 = $y1 + 20;

        imagestring($im, $font, 12, $y1, $line1, $accentColor);
        imagestring($im, $font, 12, $y2, $line2, $textColor);

        // Save back to file
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $success = false;
        if ($ext === 'png') {
            $success = imagepng($im, $filePath, 8);
        } else {
            $success = imagejpeg($im, $filePath, 88);
        }

        imagedestroy($im);
        return $success;
    }
}
