<?php
require_once "config.php";

// Test data for career guidance system
echo "<h2>🧪 Career Guidance Test Data Generator</h2>";
echo "<p>This script will populate the database with sample student data for testing.</p>";

// First, let's check if the tables exist
$tables_to_check = ['career_students', 'ol_results', 'career_interests', 'career_suggestions', 'career_paths', 'ol_subjects'];
$missing_tables = [];

foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<div style='color: red; background: #ffebee; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<strong>❌ Error:</strong> Missing database tables: " . implode(', ', $missing_tables);
    echo "<br>Please run the career_guidance_schema.sql file first.";
    echo "</div>";
    exit();
}

// Sample students data
$sample_students = [
    [
        'FullName' => 'Kamal Perera',
        'Age' => 17,
        'Email' => 'kamal.perera@email.com',
        'Phone' => '0771234567',
        'District' => 'Colombo',
        'School' => 'Royal College',
        'ol_results' => [
            ['subject' => 'Mathematics', 'grade' => 'A'],
            ['subject' => 'Science', 'grade' => 'A'],
            ['subject' => 'English', 'grade' => 'B'],
            ['subject' => 'Sinhala', 'grade' => 'A'],
            ['subject' => 'History', 'grade' => 'B'],
            ['subject' => 'Geography', 'grade' => 'C'],
            ['subject' => 'Information Technology', 'grade' => 'A'],
            ['subject' => 'Commerce', 'grade' => 'B'],
            ['subject' => 'Health & Physical Education', 'grade' => 'C']
        ],
        'interests' => ['Technology', 'Engineering', 'Innovation']
    ],
    [
        'FullName' => 'Nimali Silva',
        'Age' => 16,
        'Email' => 'nimali.silva@email.com',
        'Phone' => '0772345678',
        'District' => 'Kandy',
        'School' => 'Trinity College',
        'ol_results' => [
            ['subject' => 'Mathematics', 'grade' => 'A'],
            ['subject' => 'Science', 'grade' => 'A'],
            ['subject' => 'English', 'grade' => 'A'],
            ['subject' => 'Sinhala', 'grade' => 'A'],
            ['subject' => 'History', 'grade' => 'A'],
            ['subject' => 'Geography', 'grade' => 'B'],
            ['subject' => 'Art', 'grade' => 'A'],
            ['subject' => 'Information Technology', 'grade' => 'B'],
            ['subject' => 'Health & Physical Education', 'grade' => 'B']
        ],
        'interests' => ['Medicine', 'Healthcare', 'Research']
    ],
    [
        'FullName' => 'Rajitha Fernando',
        'Age' => 17,
        'Email' => 'rajitha.fernando@email.com',
        'Phone' => '0773456789',
        'District' => 'Galle',
        'School' => 'Richmond College',
        'ol_results' => [
            ['subject' => 'Mathematics', 'grade' => 'B'],
            ['subject' => 'Science', 'grade' => 'C'],
            ['subject' => 'English', 'grade' => 'A'],
            ['subject' => 'Sinhala', 'grade' => 'B'],
            ['subject' => 'History', 'grade' => 'A'],
            ['subject' => 'Geography', 'grade' => 'A'],
            ['subject' => 'Commerce', 'grade' => 'A'],
            ['subject' => 'Accounting', 'grade' => 'A'],
            ['subject' => 'Economics', 'grade' => 'B']
        ],
        'interests' => ['Business', 'Management', 'Finance']
    ],
    [
        'FullName' => 'Sanduni Wickramasinghe',
        'Age' => 16,
        'Email' => 'sanduni.w@email.com',
        'Phone' => '0774567890',
        'District' => 'Kurunegala',
        'School' => 'Maliyadeva College',
        'ol_results' => [
            ['subject' => 'Mathematics', 'grade' => 'C'],
            ['subject' => 'Science', 'grade' => 'S'],
            ['subject' => 'English', 'grade' => 'B'],
            ['subject' => 'Sinhala', 'grade' => 'A'],
            ['subject' => 'History', 'grade' => 'A'],
            ['subject' => 'Geography', 'grade' => 'B'],
            ['subject' => 'Art', 'grade' => 'A'],
            ['subject' => 'Dancing', 'grade' => 'A'],
            ['subject' => 'Music', 'grade' => 'A']
        ],
        'interests' => ['Arts', 'Creative Design', 'Teaching']
    ],
    [
        'FullName' => 'Tharindu Jayawardena',
        'Age' => 17,
        'Email' => 'tharindu.j@email.com',
        'Phone' => '0775678901',
        'District' => 'Matara',
        'School' => 'Rahula College',
        'ol_results' => [
            ['subject' => 'Mathematics', 'grade' => 'A'],
            ['subject' => 'Science', 'grade' => 'B'],
            ['subject' => 'English', 'grade' => 'B'],
            ['subject' => 'Sinhala', 'grade' => 'C'],
            ['subject' => 'History', 'grade' => 'C'],
            ['subject' => 'Geography', 'grade' => 'B'],
            ['subject' => 'Information Technology', 'grade' => 'A'],
            ['subject' => 'Art', 'grade' => 'B'],
            ['subject' => 'Health & Physical Education', 'grade' => 'A']
        ],
        'interests' => ['Technology', 'Gaming', 'Software Development']
    ]
];

echo "<h3>📊 Inserting Sample Students...</h3>";

$success_count = 0;
$error_count = 0;

foreach ($sample_students as $student_data) {
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Insert student
        $student_query = "INSERT INTO career_students (FullName, Age, Email, Phone, District, School, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("sissss", 
            $student_data['FullName'],
            $student_data['Age'],
            $student_data['Email'],
            $student_data['Phone'],
            $student_data['District'],
            $student_data['School']
        );
        
        if (!$student_stmt->execute()) {
            throw new Exception("Failed to insert student: " . $student_stmt->error);
        }
        
        $student_id = $conn->insert_id;
        
        // Insert O/L results
        foreach ($student_data['ol_results'] as $result) {
            $result_query = "INSERT INTO ol_results (StudentID, SubjectName, Grade, ResultYear) VALUES (?, ?, ?, 2024)";
            $result_stmt = $conn->prepare($result_query);
            $result_stmt->bind_param("iss", $student_id, $result['subject'], $result['grade']);
            
            if (!$result_stmt->execute()) {
                throw new Exception("Failed to insert O/L result: " . $result_stmt->error);
            }
        }
        
        // Insert interests
        $priority = 1;
        foreach ($student_data['interests'] as $interest) {
            $interest_query = "INSERT INTO career_interests (StudentID, InterestArea, Priority) VALUES (?, ?, ?)";
            $interest_stmt = $conn->prepare($interest_query);
            $interest_stmt->bind_param("isi", $student_id, $interest, $priority);
            
            if (!$interest_stmt->execute()) {
                throw new Exception("Failed to insert interest: " . $interest_stmt->error);
            }
            $priority++;
        }
        
        // Commit transaction
        $conn->commit();
        
        echo "<div style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
        echo "✅ Successfully added: " . htmlspecialchars($student_data['FullName']) . " (ID: $student_id)";
        echo "</div>";
        
        $success_count++;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        
        echo "<div style='color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 5px 0;'>";
        echo "❌ Failed to add " . htmlspecialchars($student_data['FullName']) . ": " . $e->getMessage();
        echo "</div>";
        
        $error_count++;
    }
}

echo "<h3>📈 Summary</h3>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<p><strong>✅ Successful inserts:</strong> $success_count</p>";
echo "<p><strong>❌ Failed inserts:</strong> $error_count</p>";
echo "<p><strong>📊 Total attempts:</strong> " . count($sample_students) . "</p>";
echo "</div>";

// Now let's test the career suggestion engine
if ($success_count > 0) {
    echo "<h3>🎯 Testing Career Suggestion Engine</h3>";
    
    // Get the first inserted student
    $test_student_query = "SELECT StudentID, FullName FROM career_students ORDER BY CreatedAt DESC LIMIT 1";
    $test_student_result = $conn->query($test_student_query);
    
    if ($test_student = $test_student_result->fetch_assoc()) {
        echo "<p>Testing with student: <strong>" . htmlspecialchars($test_student['FullName']) . "</strong></p>";
        
        try {
            // Include the career suggestion engine
            require_once 'career_suggestions.php';
            
            $suggestion_engine = new CareerSuggestionEngine($conn);
            $suggestions = $suggestion_engine->generateSuggestions($test_student['StudentID'], true);
            
            echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h4>🎓 Generated Career Suggestions:</h4>";
            
            if (!empty($suggestions)) {
                foreach ($suggestions as $i => $suggestion) {
                    $rank = $i + 1;
                    echo "<div style='margin: 10px 0; padding: 10px; background: white; border-radius: 5px;'>";
                    echo "<strong>#{$rank}. " . htmlspecialchars($suggestion['career_path']) . "</strong><br>";
                    echo "Match Score: <span style='color: green; font-weight: bold;'>" . number_format($suggestion['match_score'], 1) . "%</span><br>";
                    echo "Eligible: " . ($suggestion['eligible'] ? '✅ Yes' : '❌ No') . "<br>";
                    if (!empty($suggestion['missing_requirements'])) {
                        echo "<small style='color: #666;'>Missing: " . implode(', ', $suggestion['missing_requirements']) . "</small>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ No suitable career suggestions generated.</p>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div style='color: red; background: #ffebee; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "❌ Error testing suggestion engine: " . $e->getMessage();
            echo "</div>";
        }
    }
}

// Display next steps
echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><a href='career_guidance_dashboard.php' style='color: #1976d2;'>📊 View Career Guidance Dashboard</a></li>";
echo "<li><a href='admin_career_guidance.php' style='color: #1976d2;'>👥 Manage Student Assessments</a></li>";
echo "<li><a href='career_guidance_form.php' style='color: #1976d2;'>📝 Add New Student Assessment</a></li>";
echo "<li><a href='career_suggestions.php?student_id=" . ($test_student['StudentID'] ?? '1') . "' style='color: #1976d2;'>🎯 View Career Suggestions</a></li>";
echo "</ol>";
echo "</div>";

echo "<h3>🔍 Quick Database Check</h3>";
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM career_students) as total_students,
        (SELECT COUNT(*) FROM ol_results) as total_results,
        (SELECT COUNT(*) FROM career_interests) as total_interests,
        (SELECT COUNT(*) FROM career_paths) as total_career_paths,
        (SELECT COUNT(*) FROM ol_subjects) as total_subjects
";
$stats = $conn->query($stats_query)->fetch_assoc();

echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;'>";
echo "<div style='text-align: center; background: white; padding: 10px; border-radius: 5px;'><strong>👥 Students</strong><br>" . $stats['total_students'] . "</div>";
echo "<div style='text-align: center; background: white; padding: 10px; border-radius: 5px;'><strong>📊 O/L Results</strong><br>" . $stats['total_results'] . "</div>";
echo "<div style='text-align: center; background: white; padding: 10px; border-radius: 5px;'><strong>💭 Interests</strong><br>" . $stats['total_interests'] . "</div>";
echo "<div style='text-align: center; background: white; padding: 10px; border-radius: 5px;'><strong>🎯 Career Paths</strong><br>" . $stats['total_career_paths'] . "</div>";
echo "<div style='text-align: center; background: white; padding: 10px; border-radius: 5px;'><strong>📚 O/L Subjects</strong><br>" . $stats['total_subjects'] . "</div>";
echo "</div>";
echo "</div>";

echo "<div style='margin-top: 30px; padding: 20px; background: #e8f5e8; border-radius: 10px; text-align: center;'>";
echo "<h3 style='color: #2e7d32; margin-top: 0;'>🎉 Career Guidance System Setup Complete!</h3>";
echo "<p style='color: #388e3c;'>The system is now ready for testing and production use.</p>";
echo "</div>";
?>
