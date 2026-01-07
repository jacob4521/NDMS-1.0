<?php
// Simple QR Code generator using Google Charts API as fallback
// This is a simplified version for the birth certificate system

class QRcode {
    
    /**
     * Generate QR Code PNG using Google Charts API
     * 
     * @param string $text Text to encode
     * @param string $outfile Output file path
     * @param string $level Error correction level (ignored in this implementation)
     * @param int $size Size of the QR code
     * @param int $margin Margin around QR code (ignored in this implementation)
     */
    public static function png($text, $outfile, $level = 'L', $size = 3, $margin = 4) {
        try {
            // Use Google Charts API to generate QR code
            $encodedText = urlencode($text);
            $qrSize = $size * 50; // Convert size to pixels
            $url = "https://chart.googleapis.com/chart?chs={$qrSize}x{$qrSize}&cht=qr&chl={$encodedText}&choe=UTF-8";
            
            // Get the QR code image from Google Charts
            $imageData = @file_get_contents($url);
            
            if ($imageData === false) {
                throw new Exception("Failed to generate QR code from Google Charts API");
            }
            
            // Save the image to file
            $result = file_put_contents($outfile, $imageData);
            
            if ($result === false) {
                throw new Exception("Failed to save QR code image to file");
            }
            
            return true;
            
        } catch (Exception $e) {
            // Fallback: Create a simple placeholder image
            return self::createPlaceholderQR($outfile, $text, $size);
        }
    }
    
    /**
     * Create a placeholder QR code image when API fails
     */
    private static function createPlaceholderQR($outfile, $text, $size) {
        try {
            $width = $size * 50;
            $height = $size * 50;
            
            // Create a simple image
            $image = imagecreate($width, $height);
            
            // Colors
            $white = imagecolorallocate($image, 255, 255, 255);
            $black = imagecolorallocate($image, 0, 0, 0);
            $gray = imagecolorallocate($image, 128, 128, 128);
            
            // Fill background
            imagefill($image, 0, 0, $white);
            
            // Draw border
            imagerectangle($image, 0, 0, $width-1, $height-1, $black);
            
            // Add text indicating this is a QR placeholder
            $font = 2; // Built-in font
            $text1 = "QR Code";
            $text2 = "Verification";
            $text3 = substr($text, -20); // Last 20 chars of the URL
            
            // Center the text
            $x1 = ($width - strlen($text1) * 10) / 2;
            $y1 = $height / 2 - 30;
            $x2 = ($width - strlen($text2) * 10) / 2;
            $y2 = $height / 2 - 10;
            $x3 = ($width - strlen($text3) * 6) / 2;
            $y3 = $height / 2 + 15;
            
            imagestring($image, $font, $x1, $y1, $text1, $black);
            imagestring($image, $font, $x2, $y2, $text2, $black);
            imagestring($image, 1, $x3, $y3, $text3, $gray);
            
            // Draw some QR-like patterns
            for ($i = 10; $i < $width - 10; $i += 20) {
                for ($j = 10; $j < $height - 10; $j += 20) {
                    if (($i + $j) % 40 == 0) {
                        imagefilledrectangle($image, $i, $j, $i + 8, $j + 8, $black);
                    }
                }
            }
            
            // Save as PNG
            $result = imagepng($image, $outfile);
            imagedestroy($image);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("QR placeholder creation failed: " . $e->getMessage());
            return false;
        }
    }
}

// Define QR error correction level constants
if (!defined('QR_ECLEVEL_L')) define('QR_ECLEVEL_L', 'L');
if (!defined('QR_ECLEVEL_M')) define('QR_ECLEVEL_M', 'M');
if (!defined('QR_ECLEVEL_Q')) define('QR_ECLEVEL_Q', 'Q');
if (!defined('QR_ECLEVEL_H')) define('QR_ECLEVEL_H', 'H');
?>
