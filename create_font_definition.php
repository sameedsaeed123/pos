<?php
// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required TCPDF files
require_once(__DIR__ . '/tcpdf/tcpdf.php');
require_once(__DIR__ . '/tcpdf/include/tcpdf_fonts.php');

// Font file path - make sure this path is correct
$fontFile = __DIR__ . '/tcpdf/fonts/NotoNastaliqUrdu-Regular.ttf';

// Check if the font file exists
if (!file_exists($fontFile)) {
    die("<div style='color:red; font-weight:bold;'>Error: Font file not found at $fontFile</div>
         <p>Please make sure you have uploaded the Noto Nastaliq Urdu font to the specified location.</p>");
} 

echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h2>Font Conversion Process</h2>";
echo "<p>Starting font conversion process for Urdu text support...</p>";

try {
    // Create the font definition file with proper encoding and embedding options
    $fontname = TCPDF_FONTS::addTTFfont(
        $fontFile,         // font file
        'TrueTypeUnicode', // font type
        '',                // encoding (empty for Unicode)
        32,                // embedding options (32 for Unicode)
        '',                // path where to store the font (empty for default)
        true               // regenerate if exists
    );

    if ($fontname) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3>Success!</h3>";
        echo "<p>Font definition file created: <strong>$fontname</strong></p>";
        echo "<p>The font is now ready to be used in your PDF documents.</p>";
        echo "</div>";
        
        // Test the font by creating a small PDF
        echo "<h3>Testing Font...</h3>";
        
        // Create a new PDF document
        $pdf = new TCPDF();
        $pdf->SetFont($fontname, '', 14);
        $pdf->AddPage();
        $pdf->Write(0, 'Testing Urdu Font: بِسْمِ اللهِ الرَّحْمٰنِ الرَّحِيْمِ');
        
        // Save the test PDF
        $testPdfPath = __DIR__ . '/font_test.pdf';
        $pdf->Output($testPdfPath, 'F');
        
        echo "<p>A test PDF has been created at: $testPdfPath</p>";
        echo "<p>Please check this PDF to verify that Urdu text is displaying correctly.</p>";
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3>Error</h3>";
        echo "<p>Failed to create font definition file.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

// Additional information
echo "<h3>System Information</h3>";
echo "<ul>";
echo "<li>Font file: $fontFile</li>";
echo "<li>PHP version: " . phpversion() . "</li>";
echo "<li>TCPDF version: " . TCPDF_STATIC::getTCPDFVersion() . "</li>";
echo "</ul>";

// Instructions for using the font
echo "<h3>How to Use This Font in Your PDFs</h3>";
echo "<p>Add the following code to your PDF generation scripts:</p>";
echo "<pre style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "\$pdf = new TCPDF();\n";
echo "\$pdf->SetFont('$fontname', '', 12); // Replace 12 with your desired font size\n";
echo "// Now you can write Urdu text\n";
echo "\$pdf->Write(0, 'Your Urdu text here');\n";
echo "</pre>";

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;'>Return to Home Page</a></p>";

echo "</div>";
?>
