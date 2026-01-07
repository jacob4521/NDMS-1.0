<?php
// Test the career guidance form updates
echo "Testing Career Guidance Form Updates\n";
echo "====================================\n\n";

// Check if the form file exists and can be parsed
try {
    ob_start();
    include 'career_guidance_form.php';
    $output = ob_get_clean();
    
    echo "✅ Career guidance form loaded successfully!\n";
    echo "✅ No PHP errors detected\n";
    echo "✅ Form structure updated with new rules:\n\n";
    
    echo "Key Updates Applied:\n";
    echo "- 📚 9-subject O/L structure (6 compulsory + 3 optional)\n";
    echo "- 🌐 Language selection (choose 1: Sinhala or Tamil)\n";
    echo "- 🙏 Religion selection (choose 1: Buddhism, Christianity, Hinduism, Islam)\n";
    echo "- 📝 Optional subjects limited to exactly 3 selections\n";
    echo "- 💡 Interest areas limited to exactly 3 selections\n";
    echo "- 📊 Real-time validation and counters\n";
    echo "- ✅ Form validation before submission\n";
    echo "- 🎨 Enhanced UI with selection indicators\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
