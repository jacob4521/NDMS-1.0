<?php
include 'config.php';

// Test the career suggestion with a mock student
session_start();

// Create a test scenario
$_SESSION['student_id'] = 999; // Non-existent student for testing

// Mock student data
$test_grades = [
    'Mathematics' => 'A',
    'Science' => 'B',
    'English' => 'A',
    'Information & Communication Technology' => 'A',
    'Geography' => 'B',
    'History' => 'C',
    'Sinhala' => 'B',
    'Buddhism' => 'C',
    'Commerce' => 'B'
];

$test_interests = ['ICT & Computing', 'Engineering & Technology'];

// Test the career suggestion engine
class TestCareerSuggestionEngine {
    private $conn;
    
    // Copy the same arrays from the main class
    private $subjectWeights = [
        'Engineering & Technology' => ['Mathematics' => 2.0, 'Science' => 1.8, 'English' => 1.2],
        'ICT & Computing' => ['Mathematics' => 1.8, 'Information & Communication Technology' => 2.0, 'English' => 1.5],
        'Science & Medical' => ['Science' => 2.0, 'Mathematics' => 1.5, 'English' => 1.2],
        'Arts & Humanities' => ['English' => 2.0, 'History' => 1.5, 'Sinhala' => 1.3, 'Tamil' => 1.3],
        'Commerce & Business' => ['Mathematics' => 1.5, 'English' => 1.3, 'Commerce' => 1.8],
        'Law & Social Sciences' => ['English' => 1.8, 'History' => 1.5, 'Geography' => 1.3],
        'Vocational / Skilled Trades' => ['Engineering Technology' => 1.8, 'Mathematics' => 1.3, 'Science' => 1.3],
    ];
    
    private $marketDemand = [
        'Information Technology' => 0.95,
        'Information Technology (Vocational)' => 0.75,
        'Computer Science' => 0.98,
        'Cybersecurity' => 0.95,
    ];
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function testITCareers($grades, $interests) {
        $it_query = $this->conn->query("SELECT * FROM career_paths WHERE PathName LIKE '%Information Technology%' ORDER BY PathName");
        
        echo "Testing IT careers with student grades:\n";
        foreach ($grades as $subject => $grade) {
            echo "$subject: $grade\n";
        }
        echo "\nInterests: " . implode(', ', $interests) . "\n\n";
        
        while ($career = $it_query->fetch_assoc()) {
            echo "Career: " . $career['PathName'] . "\n";
            echo "Interest Area: " . $career['InterestArea'] . "\n";
            
            $market_factor = $this->marketDemand[$career['PathName']] ?? 0.80;
            echo "Market Demand: " . ($market_factor * 100) . "%\n";
            
            // Calculate basic score
            $score = $this->calculateBasicScore($career, $grades, $interests);
            echo "Calculated Score: " . $score . "\n";
            echo "---\n\n";
        }
    }
    
    private function calculateBasicScore($career, $grades, $interests) {
        $score = 0;
        
        // Interest match
        $interest_match = in_array($career['InterestArea'], $interests) ? 100 : 50;
        $score += $interest_match;
        
        // Market demand
        $market_factor = $this->marketDemand[$career['PathName']] ?? 0.80;
        $score *= $market_factor;
        
        return round($score, 1);
    }
}

$engine = new TestCareerSuggestionEngine($conn);
$engine->testITCareers($test_grades, $test_interests);
?>
