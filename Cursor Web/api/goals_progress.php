<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

try {
    $goals_progress = getGoalsProgress($user_id);
    
    echo json_encode([
        'success' => true,
        'goals_progress' => $goals_progress
    ]);
    
} catch (Exception $e) {
    error_log("Error getting goals progress: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load goals progress'
    ]);
}

function getGoalsProgress($user_id) {
    global $conn;
    
    // Get all active goals
    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            target_amount,
            current_amount,
            target_date,
            created_at,
            category,
            priority
        FROM goals 
        WHERE user_id = ? 
        AND status = 'active'
        ORDER BY priority DESC, target_date ASC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $goals_analysis = [];
    $summary_stats = [
        'total_goals' => 0,
        'on_track' => 0,
        'behind_schedule' => 0,
        'ahead_schedule' => 0,
        'total_target_amount' => 0,
        'total_saved_amount' => 0,
        'overall_progress' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $goal_analysis = analyzeGoalProgress($row);
        $goals_analysis[] = $goal_analysis;
        
        // Update summary stats
        $summary_stats['total_goals']++;
        $summary_stats['total_target_amount'] += (float)$row['target_amount'];
        $summary_stats['total_saved_amount'] += (float)$row['current_amount'];
        
        switch ($goal_analysis['status']) {
            case 'on_track':
                $summary_stats['on_track']++;
                break;
            case 'behind_schedule':
                $summary_stats['behind_schedule']++;
                break;
            case 'ahead_schedule':
                $summary_stats['ahead_schedule']++;
                break;
        }
    }
    
    // Calculate overall progress
    if ($summary_stats['total_target_amount'] > 0) {
        $summary_stats['overall_progress'] = ($summary_stats['total_saved_amount'] / $summary_stats['total_target_amount']) * 100;
    }
    
    // Get savings rate analysis
    $savings_analysis = getSavingsRateAnalysis($user_id);
    
    // Get goal achievement predictions
    $achievement_predictions = getGoalAchievementPredictions($user_id, $goals_analysis);
    
    // Get recommended actions
    $recommendations = getGoalRecommendations($goals_analysis, $savings_analysis);
    
    return [
        'summary' => $summary_stats,
        'goals' => $goals_analysis,
        'savings_analysis' => $savings_analysis,
        'achievement_predictions' => $achievement_predictions,
        'recommendations' => $recommendations
    ];
}

function analyzeGoalProgress($goal) {
    $target_amount = (float)$goal['target_amount'];
    $current_amount = (float)$goal['current_amount'];
    $target_date = new DateTime($goal['target_date']);
    $created_date = new DateTime($goal['created_at']);
    $today = new DateTime();
    
    // Calculate progress percentage
    $progress_percentage = $target_amount > 0 ? ($current_amount / $target_amount) * 100 : 0;
    
    // Calculate time metrics
    $total_days = $created_date->diff($target_date)->days;
    $elapsed_days = $created_date->diff($today)->days;
    $remaining_days = max(0, $today->diff($target_date)->days);
    
    $time_progress = $total_days > 0 ? ($elapsed_days / $total_days) * 100 : 0;
    
    // Determine status
    $status = 'on_track';
    if ($progress_percentage >= 100) {
        $status = 'completed';
    } elseif ($today > $target_date) {
        $status = 'overdue';
    } elseif ($progress_percentage < $time_progress - 10) {
        $status = 'behind_schedule';
    } elseif ($progress_percentage > $time_progress + 10) {
        $status = 'ahead_schedule';
    }
    
    // Calculate required monthly savings
    $remaining_amount = max(0, $target_amount - $current_amount);
    $remaining_months = max(1, ceil($remaining_days / 30));
    $required_monthly_savings = $remaining_amount / $remaining_months;
    
    // Calculate velocity (savings rate)
    $velocity = calculateGoalVelocity($goal['id'], $elapsed_days);
    
    // Estimate completion date based on current velocity
    $estimated_completion = null;
    if ($velocity > 0 && $remaining_amount > 0) {
        $days_to_completion = ceil($remaining_amount / ($velocity / 30)); // Convert to daily rate
        $estimated_completion = $today->add(new DateInterval("P{$days_to_completion}D"))->format('Y-m-d');
    }
    
    return [
        'id' => $goal['id'],
        'title' => $goal['title'],
        'target_amount' => $target_amount,
        'current_amount' => $current_amount,
        'target_date' => $goal['target_date'],
        'progress_percentage' => round($progress_percentage, 1),
        'time_progress' => round($time_progress, 1),
        'status' => $status,
        'remaining_amount' => $remaining_amount,
        'remaining_days' => $remaining_days,
        'required_monthly_savings' => round($required_monthly_savings, 2),
        'velocity' => round($velocity, 2),
        'estimated_completion' => $estimated_completion,
        'category' => $goal['category'],
        'priority' => $goal['priority']
    ];
}

function calculateGoalVelocity($goal_id, $elapsed_days) {
    global $conn;
    
    // Get goal contribution history (simplified - in real app, you'd track this separately)
    $stmt = $conn->prepare("
        SELECT current_amount, created_at
        FROM goals 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $goal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $goal = $result->fetch_assoc();
    
    if (!$goal || $elapsed_days <= 0) return 0;
    
    // Simplified velocity calculation
    // In a real app, you'd track individual contributions with timestamps
    $current_amount = (float)$goal['current_amount'];
    $velocity_per_day = $current_amount / max(1, $elapsed_days);
    
    return $velocity_per_day * 30; // Monthly velocity
}

function getSavingsRateAnalysis($user_id) {
    global $conn;
    
    // Get last 6 months of financial data
    $start_date = date('Y-m-d', strtotime('-6 months'));
    $end_date = date('Y-m-d');
    
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(t.date, '%Y-%m') as month,
            SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
            SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expenses
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND t.date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(t.date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $monthly_data = [];
    $total_income = 0;
    $total_expenses = 0;
    
    while ($row = $result->fetch_assoc()) {
        $income = (float)$row['income'];
        $expenses = (float)$row['expenses'];
        $savings = $income - $expenses;
        $savings_rate = $income > 0 ? ($savings / $income) * 100 : 0;
        
        $monthly_data[] = [
            'month' => $row['month'],
            'income' => $income,
            'expenses' => $expenses,
            'savings' => $savings,
            'savings_rate' => round($savings_rate, 1)
        ];
        
        $total_income += $income;
        $total_expenses += $expenses;
    }
    
    $total_savings = $total_income - $total_expenses;
    $average_savings_rate = $total_income > 0 ? ($total_savings / $total_income) * 100 : 0;
    $average_monthly_savings = count($monthly_data) > 0 ? $total_savings / count($monthly_data) : 0;
    
    // Calculate trend
    $savings_rates = array_column($monthly_data, 'savings_rate');
    $trend = calculateTrend($savings_rates);
    
    return [
        'average_savings_rate' => round($average_savings_rate, 1),
        'average_monthly_savings' => round($average_monthly_savings, 2),
        'monthly_data' => $monthly_data,
        'trend' => $trend,
        'assessment' => assessSavingsRate($average_savings_rate)
    ];
}

function getGoalAchievementPredictions($user_id, $goals_analysis) {
    $predictions = [];
    
    foreach ($goals_analysis as $goal) {
        if ($goal['status'] === 'completed') {
            $predictions[] = [
                'goal_id' => $goal['id'],
                'title' => $goal['title'],
                'prediction' => 'completed',
                'confidence' => 'high',
                'estimated_date' => 'Already achieved'
            ];
            continue;
        }
        
        $confidence = 'medium';
        $prediction = 'achievable';
        
        // Assess based on current velocity and remaining time
        if ($goal['velocity'] <= 0) {
            $prediction = 'at_risk';
            $confidence = 'high';
        } elseif ($goal['estimated_completion'] && $goal['estimated_completion'] > $goal['target_date']) {
            $prediction = 'likely_delayed';
            $confidence = 'medium';
        } elseif ($goal['status'] === 'ahead_schedule') {
            $prediction = 'ahead_of_schedule';
            $confidence = 'high';
        }
        
        // Adjust confidence based on data quality
        if ($goal['remaining_days'] < 30) {
            $confidence = 'high';
        } elseif ($goal['remaining_days'] > 365) {
            $confidence = 'low';
        }
        
        $predictions[] = [
            'goal_id' => $goal['id'],
            'title' => $goal['title'],
            'prediction' => $prediction,
            'confidence' => $confidence,
            'estimated_date' => $goal['estimated_completion'] ?? 'Unknown'
        ];
    }
    
    return $predictions;
}

function getGoalRecommendations($goals_analysis, $savings_analysis) {
    $recommendations = [];
    
    // Check overall savings rate
    if ($savings_analysis['average_savings_rate'] < 10) {
        $recommendations[] = [
            'type' => 'increase_savings_rate',
            'priority' => 'high',
            'title' => 'Increase Your Savings Rate',
            'description' => 'Your current savings rate is below the recommended 10-20%. Consider reducing expenses or increasing income.',
            'action' => 'Aim for at least 15% savings rate'
        ];
    }
    
    // Check for overdue goals
    $overdue_goals = array_filter($goals_analysis, function($g) { return $g['status'] === 'overdue'; });
    if (!empty($overdue_goals)) {
        $recommendations[] = [
            'type' => 'overdue_goals',
            'priority' => 'high',
            'title' => 'Address Overdue Goals',
            'description' => 'You have ' . count($overdue_goals) . ' overdue goal(s). Consider revising targets or increasing contributions.',
            'action' => 'Review and update overdue goals'
        ];
    }
    
    // Check for behind schedule goals
    $behind_goals = array_filter($goals_analysis, function($g) { return $g['status'] === 'behind_schedule'; });
    if (!empty($behind_goals)) {
        $recommendations[] = [
            'type' => 'behind_schedule',
            'priority' => 'medium',
            'title' => 'Goals Behind Schedule',
            'description' => count($behind_goals) . ' goal(s) are behind schedule. Increase monthly contributions.',
            'action' => 'Increase savings allocation to these goals'
        ];
    }
    
    // Check for unrealistic monthly requirements
    $unrealistic_goals = array_filter($goals_analysis, function($g) { 
        return $g['required_monthly_savings'] > $savings_analysis['average_monthly_savings'] * 0.8; 
    });
    if (!empty($unrealistic_goals)) {
        $recommendations[] = [
            'type' => 'unrealistic_targets',
            'priority' => 'medium',
            'title' => 'Adjust Goal Timelines',
            'description' => 'Some goals require very high monthly savings. Consider extending timelines.',
            'action' => 'Review and adjust goal target dates'
        ];
    }
    
    // Positive reinforcement for good performance
    $ahead_goals = array_filter($goals_analysis, function($g) { return $g['status'] === 'ahead_schedule'; });
    if (!empty($ahead_goals)) {
        $recommendations[] = [
            'type' => 'good_progress',
            'priority' => 'low',
            'title' => 'Excellent Progress!',
            'description' => 'You\'re ahead of schedule on ' . count($ahead_goals) . ' goal(s). Keep up the great work!',
            'action' => 'Consider setting additional stretch goals'
        ];
    }
    
    // Savings trend recommendations
    if ($savings_analysis['trend'] === 'decreasing') {
        $recommendations[] = [
            'type' => 'declining_savings',
            'priority' => 'medium',
            'title' => 'Declining Savings Trend',
            'description' => 'Your savings rate has been declining. Review recent expenses.',
            'action' => 'Identify and reduce unnecessary expenses'
        ];
    }
    
    return $recommendations;
}

function calculateTrend($values) {
    if (count($values) < 3) return 'stable';
    
    $increases = 0;
    $decreases = 0;
    
    for ($i = 1; $i < count($values); $i++) {
        if ($values[$i] > $values[$i-1]) $increases++;
        elseif ($values[$i] < $values[$i-1]) $decreases++;
    }
    
    if ($increases > $decreases) return 'increasing';
    if ($decreases > $increases) return 'decreasing';
    return 'stable';
}

function assessSavingsRate($rate) {
    if ($rate >= 20) return 'excellent';
    if ($rate >= 15) return 'good';
    if ($rate >= 10) return 'fair';
    return 'needs_improvement';
}
?>