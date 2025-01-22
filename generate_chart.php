<?php
header('Content-Type: image/png');

// Get the counts from the URL parameters, default to 0 if not provided
$ontime_count = isset($_GET['ontime']) ? intval($_GET['ontime']) : 0;
$late_count = isset($_GET['late']) ? intval($_GET['late']) : 0;
$absent_count = isset($_GET['absent']) ? intval($_GET['absent']) : 0;

// Create image
$width = 250;  // Reduced size
$height = 250; // Reduced size
$image = imagecreatetruecolor($width, $height);

// Set background to transparent
imagealphablending($image, true);
imagesavealpha($image, true);
$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
imagefill($image, 0, 0, $transparent);

// Colors for the pie chart
$colors = [
    'ontime' => imagecolorallocate($image, 20, 174, 92),     // UI Green color (#14AE5C)
    'late' => imagecolorallocate($image, 252, 238, 28),      // UI Yellow color (#FCEE1C)
    'absent' => imagecolorallocate($image, 186, 0, 71),      // UI Red color (#BA0047)
    'empty' => imagecolorallocate($image, 186, 0, 71)     // Light gray
];

// Calculate total
$total = $ontime_count + $late_count + $absent_count;

// Center coordinates and radius
$center_x = $width / 2;
$center_y = $height / 2;
$radius = min($width, $height) * 0.48; // Slightly larger radius

// Draw background circle (always visible)
imagefilledarc(
    $image,
    $center_x,
    $center_y,
    $radius * 2,
    $radius * 2,
    0,
    360,
    $colors['empty'],
    IMG_ARC_PIE
);

if ($total > 0) {
    // Draw pie slices
    $start = 0;
    $data = [
        ['count' => $ontime_count, 'color' => $colors['ontime']],
        ['count' => $late_count, 'color' => $colors['late']],
        ['count' => $absent_count, 'color' => $colors['absent']]
    ];

    foreach ($data as $slice) {
        if ($slice['count'] > 0) {
            $slice_angle = ($slice['count'] / $total) * 360;
            imagefilledarc(
                $image,
                $center_x,
                $center_y,
                $radius * 2,
                $radius * 2,
                $start,
                $start + $slice_angle,
                $slice['color'],
                IMG_ARC_PIE
            );
            $start += $slice_angle;
        }
    }
}

// Draw a slightly smaller inner circle to create a "donut" effect
$inner_radius = $radius * 0.6;
imagefilledarc(
    $image,
    $center_x,
    $center_y,
    $inner_radius * 2,
    $inner_radius * 2,
    0,
    360,
    $transparent,
    IMG_ARC_PIE
);

// Output image
imagepng($image);
imagedestroy($image);
?>