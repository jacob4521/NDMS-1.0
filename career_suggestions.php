<?php
include "config.php";

if (!isset($_GET['student_id'])) {
    header("Location: career_guidance_form.php");
    exit();
}

$student_id = intval($_GET['student_id']);

// Get student information
$student_query = $conn->prepare("SELECT * FROM career_students WHERE StudentID = ?");
$student_query->bind_param("i", $student_id);
$student_query->execute();
$student = $student_query->get_result()->fetch_assoc();

if (!$student) {
    header("Location: career_guidance_form.php");
    exit();
}

// Get student's O/L results
$results_query = $conn->prepare("SELECT SubjectName, Grade FROM ol_results WHERE StudentID = ?");
$results_query->bind_param("i", $student_id);
$results_query->execute();
$results_data = $results_query->get_result();

$student_grades = [];
while ($row = $results_data->fetch_assoc()) {
    $student_grades[$row['SubjectName']] = $row['Grade'];
}

// Get student's interests
$interests_query = $conn->prepare("SELECT InterestArea FROM career_interests WHERE StudentID = ? ORDER BY Priority");
$interests_query->bind_param("i", $student_id);
$interests_query->execute();
$interests_data = $interests_query->get_result();

$student_interests = [];
while ($row = $interests_data->fetch_assoc()) {
    $student_interests[] = $row['InterestArea'];
}

// Enhanced Career Suggestion Algorithm
class CareerSuggestionEngine {
    private $conn;
    private $gradeValues = ['A' => 5, 'B' => 4, 'C' => 3, 'S' => 2, 'W' => 1];
    
    // Enhanced subject weights for different career areas
    private $subjectWeights = [
        'Science & Medical' => ['Science' => 2.0, 'Mathematics' => 1.5, 'English' => 1.2],
        'Engineering & Technology' => ['Mathematics' => 2.0, 'Science' => 1.8, 'English' => 1.2],
        'ICT & Computing' => ['Mathematics' => 1.8, 'Information & Communication Technology' => 2.0, 'English' => 1.5],
        'Commerce & Business' => ['Mathematics' => 1.5, 'Economics' => 1.8, 'English' => 1.5, 'Business & Accounting Studies' => 1.8],
        'Arts & Humanities' => ['English' => 2.0, 'History' => 1.5, 'Sinhala' => 1.3, 'Tamil' => 1.3],
        'Law & Social Sciences' => ['English' => 2.0, 'History' => 1.5, 'Civic Education' => 1.3],
        'Creative Arts & Design' => ['Art' => 2.0, 'Music' => 1.5, 'Dancing' => 1.5, 'Drama & Theatre' => 1.5],
        'Vocational / Skilled Trades' => ['Engineering Technology' => 1.8, 'Mathematics' => 1.3, 'Science' => 1.3],
        'Sports & Physical Education' => ['Health & Physical Education' => 2.0, 'Science' => 1.2]
    ];
    
    // Market demand factors (can be updated without database changes)
    private $marketDemand = [
        'Medicine' => 0.95,
        'Engineering' => 0.90,
        'Information Technology' => 0.95,
        'Information Technology (Vocational)' => 0.75, // Lower demand for vocational IT
        'Computer Science' => 0.98,
        'Software Engineering' => 0.95,
        'Data Science' => 0.98,
        'Artificial Intelligence & Machine Learning' => 1.0,
        'Cybersecurity' => 0.95,
        'Digital Marketing Specialist' => 0.88,
        'Financial Technology (FinTech)' => 0.92,
        'Game Development' => 0.85,
        'User Experience (UX) Design' => 0.90,
        'Business Administration' => 0.80,
        'Accounting' => 0.75,
        'Banking' => 0.70,
        'Teaching' => 0.60,
        'Law' => 0.75,
        'Journalism & Mass Communication' => 0.65,
        'Agriculture' => 0.70,
        'Tourism Management' => 0.60
    ];
    
    // Geographic availability (districts where careers are more available)
    private $locationAvailability = [
        'Information Technology' => ['Colombo', 'Gampaha', 'Kandy', 'Galle'],
        'Banking' => ['Colombo', 'Gampaha', 'Kandy', 'Galle', 'Kurunegala'],
        'Tourism Management' => ['Colombo', 'Galle', 'Kandy', 'Nuwara Eliya', 'Hambantota'],
        'Agriculture' => ['Anuradhapura', 'Polonnaruwa', 'Kurunegala', 'Badulla', 'Ampara'],
        'Medicine' => ['Colombo', 'Gampaha', 'Kandy', 'Galle', 'Jaffna'],
        'Engineering' => ['Colombo', 'Gampaha', 'Kandy', 'Kurunegala']
    ];
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function generateSuggestions($student_grades, $student_interests, $student_district = null, $student_age = null) {
        // Get all career paths
        $paths_query = $this->conn->query("SELECT * FROM career_paths WHERE IsActive = TRUE");
        $career_paths = [];
        while ($row = $paths_query->fetch_assoc()) {
            $career_paths[] = $row;
        }
        
        $suggestions = [];
        
        foreach ($career_paths as $path) {
            $base_score = $this->calculateEnhancedMatchScore($path, $student_grades, $student_interests);
            
            if ($base_score > 0) {
                // Apply enhancement factors
                $market_factor = $this->getMarketDemandFactor($path['PathName']);
                $location_factor = $this->getLocationFactor($path['PathName'], $student_district);
                $age_factor = $this->getAgeFactor($path['PathName'], $student_age);
                $consistency_factor = $this->getAcademicConsistencyFactor($student_grades);
                
                // Calculate final score with all factors
                $final_score = $base_score * $market_factor * $location_factor * $age_factor * $consistency_factor;
                
                $suggestions[] = [
                    'path' => $path,
                    'score' => round($final_score, 2),
                    'base_score' => round($base_score, 2),
                    'eligible' => $this->checkEligibility($path, $student_grades),
                    'interest_match' => in_array($path['InterestArea'], $student_interests),
                    'market_demand' => $market_factor,
                    'location_suitability' => $location_factor,
                    'score_breakdown' => [
                        'subject_performance' => $this->getLastSubjectScore(),
                        'interest_alignment' => $this->getLastInterestScore(),
                        'academic_consistency' => $consistency_factor,
                        'market_demand' => $market_factor,
                        'location_advantage' => $location_factor
                    ]
                ];
            }
        }
        
        // Enhanced sorting with diversity consideration
        usort($suggestions, function($a, $b) {
            // Primary sort by final score
            if ($a['score'] != $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            // Secondary sort by eligibility
            if ($a['eligible'] != $b['eligible']) {
                return $b['eligible'] <=> $a['eligible'];
            }
            // Tertiary sort by interest match
            return $b['interest_match'] <=> $a['interest_match'];
        });
        
        // Apply diversity boost - prevent too many similar careers at top
        $this->applyDiversityBoost($suggestions);
        
        // Remove careers with very similar names to avoid confusion
        // $suggestions = $this->removeSimilarCareers($suggestions);
        
        return $suggestions;
    }
    
    private $lastSubjectScore = 0;
    private $lastInterestScore = 0;
    
    private function calculateEnhancedMatchScore($path, $student_grades, $student_interests) {
        $score = 0;
        
        // Parse required subjects and minimum grades
        $required_subjects = json_decode($path['RequiredSubjects'], true);
        $minimum_grades = json_decode($path['MinimumGrades'], true);
        
        if (!$required_subjects || !$minimum_grades) {
            return 0;
        }
        
        // Enhanced subject requirements scoring (35% of score)
        $subject_score = $this->calculateWeightedSubjectScore($path, $required_subjects, $minimum_grades, $student_grades);
        $score += $subject_score * 0.35;
        $this->lastSubjectScore = $subject_score;
        
        // Enhanced interest matching (30% of score)
        $interest_score = $this->calculateAdvancedInterestScore($path, $student_interests);
        $score += $interest_score * 0.30;
        $this->lastInterestScore = $interest_score;
        
        // Academic performance with grade distribution (20% of score)
        $performance_score = $this->calculatePerformanceScore($student_grades);
        $score += $performance_score * 0.20;
        
        // Enhanced optional subjects bonus (15% of score)
        $optional_bonus = $this->calculateEnhancedOptionalBonus($path, $student_grades);
        $score += $optional_bonus * 0.15;
        
        return $score;
    }
    
    private function calculateWeightedSubjectScore($path, $required_subjects, $minimum_grades, $student_grades) {
        $total_weighted_score = 0;
        $total_weight = 0;
        
        foreach ($required_subjects as $subject) {
            $weight = $this->getSubjectWeight($path['InterestArea'], $subject);
            $total_weight += $weight;
            
            if (isset($student_grades[$subject])) {
                $student_grade = $student_grades[$subject];
                $min_grade = $minimum_grades[$subject] ?? 'S';
                
                if ($this->gradeValues[$student_grade] >= $this->gradeValues[$min_grade]) {
                    // Base score for meeting requirement
                    $base_score = 15;
                    
                    // Bonus for exceeding requirements (progressive)
                    $excess = $this->gradeValues[$student_grade] - $this->gradeValues[$min_grade];
                    $bonus = $excess * 4; // Higher bonus for exceeding
                    
                    // Apply subject weight
                    $subject_score = ($base_score + $bonus) * $weight;
                    $total_weighted_score += $subject_score;
                } else {
                    // Penalty for not meeting requirements
                    $deficit = $this->gradeValues[$min_grade] - $this->gradeValues[$student_grade];
                    $penalty = $deficit * 5 * $weight;
                    $total_weighted_score -= $penalty;
                }
            } else {
                // Heavy penalty for missing required subjects
                $total_weighted_score -= 20 * $weight;
            }
        }
        
        return $total_weight > 0 ? $total_weighted_score / $total_weight : 0;
    }
    
    private function calculateAdvancedInterestScore($path, $student_interests) {
        $score = 0;
        
        // Direct interest match
        if (in_array($path['InterestArea'], $student_interests)) {
            $interest_position = array_search($path['InterestArea'], $student_interests);
            $base_score = 35 - ($interest_position * 7); // 35, 28, 21 for positions 0, 1, 2
            $score += $base_score;
        }
        
        // Related interest areas matching
        $related_interests = $this->getRelatedInterests($path['InterestArea']);
        foreach ($student_interests as $interest) {
            if (in_array($interest, $related_interests)) {
                $score += 10; // Bonus for related interests
            }
        }
        
        // Career versatility bonus (careers that match multiple interests)
        $versatile_careers = [
            'Business Administration', 'Psychology', 'Information Technology', 
            'Journalism & Mass Communication', 'Project Management'
        ];
        if (in_array($path['PathName'], $versatile_careers) && count($student_interests) > 1) {
            $score += 5;
        }
        
        return min($score, 40); // Cap at 40 points
    }
    
    private function calculatePerformanceScore($student_grades) {
        if (empty($student_grades)) return 0;
        
        $grade_values = array_map(function($grade) {
            return $this->gradeValues[$grade];
        }, $student_grades);
        
        $average = array_sum($grade_values) / count($grade_values);
        $median = $this->calculateMedian($grade_values);
        
        // Favor consistency - blend of average and median
        $performance = ($average * 0.7) + ($median * 0.3);
        
        // Count excellent grades (A's and B's)
        $excellent_count = count(array_filter($student_grades, function($grade) {
            return in_array($grade, ['A', 'B']);
        }));
        
        $excellence_bonus = ($excellent_count / count($student_grades)) * 5;
        
        return (($performance / 5) * 15) + $excellence_bonus;
    }
    
    private function calculateEnhancedOptionalBonus($path, $student_grades) {
        $bonus = 0;
        
        // Enhanced subject mapping with more detailed categories
        $detailed_subject_mapping = [
            'Science & Medical' => [
                'high_relevance' => ['Agriculture & Food Technology', 'Health & Physical Education', 'Home Economics'],
                'medium_relevance' => ['Geography', 'Information & Communication Technology']
            ],
            'Engineering & Technology' => [
                'high_relevance' => ['Engineering Technology', 'Information & Communication Technology'],
                'medium_relevance' => ['Art', 'Economics']
            ],
            'ICT & Computing' => [
                'high_relevance' => ['Information & Communication Technology'],
                'medium_relevance' => ['Mathematics', 'Economics', 'Art']
            ],
            'Commerce & Business' => [
                'high_relevance' => ['Business & Accounting Studies', 'Economics', 'Entrepreneurship Studies'],
                'medium_relevance' => ['Geography', 'Information & Communication Technology']
            ],
            'Arts & Humanities' => [
                'high_relevance' => ['Sinhala Literature', 'Tamil Literature', 'English Literature', 'Art', 'Music', 'Drama & Theatre'],
                'medium_relevance' => ['Geography', 'Civic Education']
            ],
            'Creative Arts & Design' => [
                'high_relevance' => ['Art', 'Music', 'Drama & Theatre', 'Dancing'],
                'medium_relevance' => ['Information & Communication Technology', 'English Literature']
            ]
        ];
        
        $interest_area = $path['InterestArea'];
        if (isset($detailed_subject_mapping[$interest_area])) {
            $mapping = $detailed_subject_mapping[$interest_area];
            
            // High relevance subjects
            foreach ($mapping['high_relevance'] ?? [] as $subject) {
                if (isset($student_grades[$subject])) {
                    $bonus += $this->gradeValues[$student_grades[$subject]] * 1.5;
                }
            }
            
            // Medium relevance subjects
            foreach ($mapping['medium_relevance'] ?? [] as $subject) {
                if (isset($student_grades[$subject])) {
                    $bonus += $this->gradeValues[$student_grades[$subject]] * 0.8;
                }
            }
        }
        
        return min($bonus, 15); // Cap bonus at 15 points
    }
    
    // Helper methods for enhancement factors
    private function getSubjectWeight($interest_area, $subject) {
        return $this->subjectWeights[$interest_area][$subject] ?? 1.0;
    }
    
    private function getMarketDemandFactor($career_name) {
        return $this->marketDemand[$career_name] ?? 0.80; // Default moderate demand
    }
    
    private function getLocationFactor($career_name, $district) {
        if (!$district || !isset($this->locationAvailability[$career_name])) {
            return 1.0; // Neutral if no data
        }
        
        return in_array($district, $this->locationAvailability[$career_name]) ? 1.1 : 0.95;
    }
    
    private function getAgeFactor($career_name, $age) {
        if (!$age) return 1.0;
        
        // Some careers favor younger or older students
        $age_preferences = [
            'Artificial Intelligence & Machine Learning' => [16, 18], // Favor younger
            'Game Development' => [16, 18],
            'Teaching' => [17, 20], // Slightly older
            'Medicine' => [16, 18], // Younger for long study period
            'Entrepreneurship' => [18, 20] // Slightly older
        ];
        
        if (isset($age_preferences[$career_name])) {
            $preferred_range = $age_preferences[$career_name];
            if ($age >= $preferred_range[0] && $age <= $preferred_range[1]) {
                return 1.05;
            }
        }
        
        return 1.0;
    }
    
    private function getAcademicConsistencyFactor($student_grades) {
        if (empty($student_grades)) return 1.0;
        
        $grade_values = array_map(function($grade) {
            return $this->gradeValues[$grade];
        }, $student_grades);
        
        $average = array_sum($grade_values) / count($grade_values);
        $variance = 0;
        
        foreach ($grade_values as $value) {
            $variance += pow($value - $average, 2);
        }
        $variance /= count($grade_values);
        $std_dev = sqrt($variance);
        
        // Lower standard deviation = more consistent = slight bonus
        $consistency_factor = 1.0 + (0.1 * (2 - $std_dev)); // Boost for consistency
        return max(0.9, min(1.1, $consistency_factor)); // Keep within reasonable bounds
    }
    
    private function getRelatedInterests($primary_interest) {
        $related_map = [
            'Science & Medical' => ['Engineering & Technology', 'ICT & Computing'],
            'Engineering & Technology' => ['Science & Medical', 'ICT & Computing'],
            'ICT & Computing' => ['Engineering & Technology', 'Commerce & Business'],
            'Commerce & Business' => ['ICT & Computing', 'Law & Social Sciences'],
            'Arts & Humanities' => ['Creative Arts & Design', 'Law & Social Sciences'],
            'Creative Arts & Design' => ['Arts & Humanities'],
            'Law & Social Sciences' => ['Arts & Humanities', 'Commerce & Business'],
            'Vocational / Skilled Trades' => ['Engineering & Technology'],
            'Sports & Physical Education' => ['Science & Medical']
        ];
        
        return $related_map[$primary_interest] ?? [];
    }
    
    private function calculateMedian($numbers) {
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        } else {
            return $numbers[$middle];
        }
    }
    
    private function getLastSubjectScore() {
        return round($this->lastSubjectScore, 1);
    }
    
    private function getLastInterestScore() {
        return round($this->lastInterestScore, 1);
    }
    
    private function applyDiversityBoost(&$suggestions) {
        // Track interest areas already seen in top positions
        $topInterestAreas = [];
        $maxTopPositions = 3; // Maximum positions to check for diversity
        
        for ($i = 0; $i < min($maxTopPositions, count($suggestions)); $i++) {
            $interestArea = $suggestions[$i]['path']['InterestArea'];
            $topInterestAreas[] = $interestArea;
        }
        
        // If we have too many similar careers in top positions, apply diversity boost
        $areaCount = array_count_values($topInterestAreas);
        foreach ($areaCount as $area => $count) {
            if ($count > 1) { // If more than 1 career from same interest area in top 3
                // Find careers from different areas that are close in score
                for ($i = $maxTopPositions; $i < min(10, count($suggestions)); $i++) {
                    $suggestion = &$suggestions[$i];
                    
                    // If this career is from a different area and close in score
                    if (!in_array($suggestion['path']['InterestArea'], $topInterestAreas) && 
                        $suggestion['score'] >= $suggestions[$maxTopPositions - 1]['score'] - 5) {
                        
                        // Apply small diversity boost
                        $suggestion['score'] += 2;
                        $suggestion['diversity_boost'] = true;
                    }
                }
            }
        }
        
        // Re-sort after applying diversity boost
        usort($suggestions, function($a, $b) {
            if ($a['score'] != $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            if ($a['eligible'] != $b['eligible']) {
                return $b['eligible'] <=> $a['eligible'];
            }
            return $b['interest_match'] <=> $a['interest_match'];
        });
    }
    
    private function removeSimilarCareers($suggestions) {
        $filtered = [];
        $seenNames = [];
        
        foreach ($suggestions as $suggestion) {
            $careerName = $suggestion['path']['PathName'];
            $baseName = $this->getBaseName($careerName);
            
            // If we haven't seen this base name, or if this is a significantly better match
            if (!isset($seenNames[$baseName])) {
                $seenNames[$baseName] = $suggestion;
                $filtered[] = $suggestion;
            } else {
                // If current suggestion is significantly better, replace the previous one
                $existing = $seenNames[$baseName];
                if ($suggestion['score'] > $existing['score'] + 10) {
                    // Remove the previous one and add this one
                    $filtered = array_filter($filtered, function($item) use ($existing) {
                        return $item['path']['PathName'] !== $existing['path']['PathName'];
                    });
                    $seenNames[$baseName] = $suggestion;
                    $filtered[] = $suggestion;
                }
                // Otherwise, skip this similar career
            }
        }
        
        return array_values($filtered); // Re-index the array
    }
    
    private function getBaseName($careerName) {
        // Remove common suffixes/prefixes to identify similar careers
        $baseName = preg_replace('/\s*\(.*?\)\s*/', '', $careerName); // Remove parentheses content
        $baseName = preg_replace('/\s*(Vocational|Advanced|Specialist|Technology)\s*/', '', $baseName);
        return trim($baseName);
    }
    
    private function checkEligibility($path, $student_grades) {
        $required_subjects = json_decode($path['RequiredSubjects'], true);
        $minimum_grades = json_decode($path['MinimumGrades'], true);
        
        if (!$required_subjects || !$minimum_grades) {
            return false;
        }
        
        foreach ($required_subjects as $subject) {
            if (!isset($student_grades[$subject])) {
                return false;
            }
            
            $student_grade = $student_grades[$subject];
            $min_grade = $minimum_grades[$subject] ?? 'S';
            
            if ($this->gradeValues[$student_grade] < $this->gradeValues[$min_grade]) {
                return false;
            }
        }
        
        return true;
    }
    
    // Legacy method for backward compatibility
    private function calculateOptionalSubjectBonus($path, $student_grades) {
        return $this->calculateEnhancedOptionalBonus($path, $student_grades);
    }
}

// Generate enhanced suggestions
$engine = new CareerSuggestionEngine($conn);
$suggestions = $engine->generateSuggestions($student_grades, $student_interests, $student['District'], $student['Age']);

// Get top 3 suggestions for detailed display
$top_suggestions = array_slice($suggestions, 0, 3);
$alternative_suggestions = array_slice($suggestions, 3, 5);

// Save suggestions to database
if (!empty($suggestions)) {
    $primary = $top_suggestions[0]['path'];
    $alternatives = array_map(function($s) { return $s['path']['PathName']; }, array_slice($suggestions, 1, 4));
    $additional_recommendations = "Based on your results, consider improving weak subjects for better opportunities.";
    
    // Check if suggestions already exist
    $existing_check = $conn->prepare("SELECT SuggestionID FROM career_suggestions WHERE StudentID = ?");
    $existing_check->bind_param("i", $student_id);
    $existing_check->execute();
    $existing = $existing_check->get_result();
    
    if ($existing->num_rows == 0) {
        $match_score = $top_suggestions[0]['score'];
        $alternatives_json = json_encode($alternatives);
        
        $stmt = $conn->prepare("INSERT INTO career_suggestions (StudentID, PrimarySuggestion, PrimaryDescription, AlternativeSuggestions, AdditionalRecommendations, MatchScore) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssd", 
            $student_id, 
            $primary['PathName'], 
            $primary['Description'],
            $alternatives_json,
            $additional_recommendations,
            $match_score
        );
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Suggestions - NDMS</title>
    <style>
        /* Use same NDMS styling as form */
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .student-info {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .suggestions-grid {
            display: grid;
            gap: 30px;
            margin-bottom: 40px;
        }

        .suggestion-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .suggestion-card.primary {
            border-color: var(--accent-color);
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
        }

        .suggestion-card.alternative {
            border-color: var(--secondary-color);
        }

        .suggestion-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .career-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .match-score {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .career-description {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 25px;
        }

        .career-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-section {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 10px;
        }

        .detail-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-content {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .grades-summary {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .grades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .grade-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: var(--light-bg);
            border-radius: 8px;
            font-size: 14px;
        }

        .grade-value {
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 6px;
            color: white;
        }

        .grade-A { background: #10b981; }
        .grade-B { background: #3b82f6; }
        .grade-C { background: #f59e0b; }
        .grade-S { background: #6b7280; }
        .grade-W { background: #ef4444; }

        .interests-display {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .interest-tag {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--gradient-bg);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .alternatives-section {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .alternatives-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .alternative-item {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid var(--secondary-color);
        }

        .no-suggestions {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: 20px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 28px;
            }

            .suggestion-card {
                padding: 20px;
            }

            .career-details {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                align-items: center;
            }
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 60px;
        }

        .container {
            min-height: 100vh;
            /* Remove margin since body now has padding */
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar {
                padding-left: 0 !important;
                padding-top: 80px; /* Space for mobile menu button */
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>🎯 Your Career Path Recommendations</h1>
            <p>Personalized suggestions based on your O/L results and interests</p>
        </div>

        <!-- Student Information Summary -->
        <div class="student-info">
            <h3 style="color: var(--primary-color); margin-bottom: 15px;">👤 Student Profile</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div><strong>Name:</strong> <?= htmlspecialchars($student['FullName']) ?></div>
                <div><strong>Age:</strong> <?= htmlspecialchars($student['Age']) ?></div>
                <?php if ($student['District']): ?>
                <div><strong>District:</strong> <?= htmlspecialchars($student['District']) ?></div>
                <?php endif; ?>
                <div><strong>Assessment Date:</strong> <?= date('M j, Y', strtotime($student['CreatedAt'])) ?></div>
            </div>
            
            <?php if (!empty($student_interests)): ?>
            <div style="margin-top: 15px;">
                <strong>Career Interests:</strong>
                <div class="interests-display">
                    <?php foreach ($student_interests as $interest): ?>
                        <span class="interest-tag"><?= htmlspecialchars($interest) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- O/L Results Summary -->
        <div class="grades-summary">
            <h3 style="color: var(--primary-color); margin-bottom: 15px;">📊 Your O/L Results</h3>
            <div class="grades-grid">
                <?php foreach ($student_grades as $subject => $grade): ?>
                    <div class="grade-item">
                        <span><?= htmlspecialchars($subject) ?></span>
                        <span class="grade-value grade-<?= $grade ?>"><?= $grade ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($suggestions)): ?>
            <div class="no-suggestions">
                <h2>🤔 No Suitable Career Paths Found</h2>
                <p>Based on your current results, we couldn't find matching career paths. Consider retaking some subjects or exploring foundation courses.</p>
            </div>
        <?php else: ?>
            <!-- Primary Recommendations -->
            <div class="suggestions-grid">
                <?php foreach ($top_suggestions as $index => $suggestion): ?>
                    <div class="suggestion-card <?= $index === 0 ? 'primary' : 'alternative' ?>">
                        <div class="suggestion-header">
                            <div>
                                <?php if ($index === 0): ?>
                                    <div style="color: var(--accent-color); font-weight: 600; font-size: 14px; margin-bottom: 5px;">🌟 TOP RECOMMENDATION</div>
                                <?php else: ?>
                                    <div style="color: var(--secondary-color); font-weight: 600; font-size: 14px; margin-bottom: 5px;">💡 ALTERNATIVE OPTION <?= $index ?></div>
                                <?php endif; ?>
                                <div class="career-title"><?= htmlspecialchars($suggestion['path']['PathName']) ?></div>
                            </div>
                            <div class="match-score"><?= $suggestion['score'] ?>% Match</div>
                        </div>
                        
                        <div class="career-description">
                            <?= htmlspecialchars($suggestion['path']['Description']) ?>
                        </div>
                        
                        <div class="career-details">
                            <div class="detail-section">
                                <div class="detail-title">🎓 A/L Stream</div>
                                <div class="detail-content"><?= htmlspecialchars($suggestion['path']['ALStreamSuggestion']) ?></div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-title">🏛️ University Programs</div>
                                <div class="detail-content"><?= htmlspecialchars($suggestion['path']['UniversityPrograms']) ?></div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-title">🛤️ Alternative Routes</div>
                                <div class="detail-content"><?= htmlspecialchars($suggestion['path']['AlternativeRoutes']) ?></div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-title">💼 Career Opportunities</div>
                                <div class="detail-content"><?= htmlspecialchars($suggestion['path']['JobOpportunities']) ?></div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-title">💰 Salary Range</div>
                                <div class="detail-content"><?= htmlspecialchars($suggestion['path']['SalaryRange']) ?></div>
                            </div>
                            
                            <div class="detail-section">
                                <div class="detail-title">✅ Eligibility</div>
                                <div class="detail-content">
                                    <?php if ($suggestion['eligible']): ?>
                                        <span style="color: var(--accent-color); font-weight: 600;">✓ You meet the requirements</span>
                                    <?php else: ?>
                                        <span style="color: var(--danger-color); font-weight: 600;">⚠ Some requirements not met</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (isset($suggestion['score_breakdown'])): ?>
                            <div class="detail-section" style="grid-column: span 2;">
                                <div class="detail-title">📊 Detailed Score Analysis</div>
                                <div class="detail-content">
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 8px;">
                                        <div style="background: #f0f9ff; padding: 8px; border-radius: 6px; text-align: center;">
                                            <div style="font-size: 12px; color: #0369a1; font-weight: 600;">Subject Performance</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #1e40af;"><?= $suggestion['score_breakdown']['subject_performance'] ?></div>
                                        </div>
                                        <div style="background: #f0fdf4; padding: 8px; border-radius: 6px; text-align: center;">
                                            <div style="font-size: 12px; color: #166534; font-weight: 600;">Interest Alignment</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #15803d;"><?= $suggestion['score_breakdown']['interest_alignment'] ?></div>
                                        </div>
                                        <div style="background: #fefce8; padding: 8px; border-radius: 6px; text-align: center;">
                                            <div style="font-size: 12px; color: #a16207; font-weight: 600;">Market Demand</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #ca8a04;"><?= number_format($suggestion['score_breakdown']['market_demand'] * 100, 0) ?>%</div>
                                        </div>
                                        <div style="background: #fdf2f8; padding: 8px; border-radius: 6px; text-align: center;">
                                            <div style="font-size: 12px; color: #be185d; font-weight: 600;">Location Advantage</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #e11d48;"><?= number_format($suggestion['score_breakdown']['location_advantage'] * 100, 0) ?>%</div>
                                        </div>
                                        <div style="background: #f8fafc; padding: 8px; border-radius: 6px; text-align: center;">
                                            <div style="font-size: 12px; color: #475569; font-weight: 600;">Consistency</div>
                                            <div style="font-size: 16px; font-weight: 700; color: #64748b;"><?= number_format($suggestion['score_breakdown']['academic_consistency'] * 100, 0) ?>%</div>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($suggestion['market_demand']) || isset($suggestion['location_suitability'])): ?>
                                    <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
                                        <strong>Enhancement Factors:</strong>
                                        <?php if (isset($suggestion['market_demand'])): ?>
                                            Market Demand: <?= number_format($suggestion['market_demand'] * 100, 0) ?>%
                                        <?php endif; ?>
                                        <?php if (isset($suggestion['location_suitability'])): ?>
                                            | Location Match: <?= number_format($suggestion['location_suitability'] * 100, 0) ?>%
                                        <?php endif; ?>
                                        <?php if (isset($suggestion['base_score'])): ?>
                                            | Base Score: <?= $suggestion['base_score'] ?>%
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Additional Alternatives -->
            <?php if (!empty($alternative_suggestions)): ?>
                <div class="alternatives-section">
                    <h3 style="color: var(--primary-color); margin-bottom: 15px;">🔄 Other Career Options to Consider</h3>
                    <div class="alternatives-list">
                        <?php foreach ($alternative_suggestions as $alt): ?>
                            <div class="alternative-item">
                                <h4 style="color: var(--primary-color); margin-bottom: 8px;"><?= htmlspecialchars($alt['path']['PathName']) ?></h4>
                                <p style="font-size: 14px; color: var(--text-secondary);"><?= htmlspecialchars($alt['path']['Description']) ?></p>
                                <div style="margin-top: 8px; font-size: 12px; color: var(--secondary-color); font-weight: 600;">
                                    <?= $alt['score'] ?>% Match
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="actions">
            <a href="career_guidance_form.php" class="btn btn-secondary">🔄 Take Assessment Again</a>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Results</button>
            <a href="dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
        </div>
    </div>

    <script>
        // Add print styles
        const printStyles = `
            @media print {
                .actions { display: none; }
                .btn { display: none; }
                .container { max-width: none; padding: 0; }
                .header { background: #f0f0f0 !important; color: black !important; }
            }
        `;
        
        const styleSheet = document.createElement("style");
        styleSheet.type = "text/css";
        styleSheet.innerText = printStyles;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>
