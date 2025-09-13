<?php
// Start session for user authentication
session_start();

include 'config.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// Define constants
define('SKILL_LEVELS', ['Beginner', 'Intermediate', 'Advanced']);

// Core functions for the system
/**
 * Add a new gymnastics program
 */
function addProgram($pdo, $name, $description, $coachName, $contact, $duration, $skillLevel) {
    // Input validation
    $errors = [];
    if (empty($name)) $errors[] = "Program name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($coachName)) $errors[] = "Coach name is required";
    if (empty($contact)) $errors[] = "Contact information is required";
    if (!is_numeric($duration) || $duration <= 0) $errors[] = "Duration must be a positive number";
    if (!in_array($skillLevel, SKILL_LEVELS)) $errors[] = "Invalid skill level";
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Create program
    $stmt = $pdo->prepare("INSERT INTO programs (name, description, coachName, contact, duration, skillLevel) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $coachName, $contact, $duration, $skillLevel]);
    
    return ['success' => true, 'message' => "Program added successfully", 'id' => $pdo->lastInsertId()];
}

/**
 * Edit an existing program
 */
function editProgram($pdo, $programId, $name, $description, $coachName, $contact, $duration, $skillLevel) {
    // Check if program exists
    $stmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'errors' => ["Program not found"]];
    }
    
    // Input validation
    $errors = [];
    if (empty($name)) $errors[] = "Program name is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($coachName)) $errors[] = "Coach name is required";
    if (empty($contact)) $errors[] = "Contact information is required";
    if (!is_numeric($duration) || $duration <= 0) $errors[] = "Duration must be a positive number";
    if (!in_array($skillLevel, SKILL_LEVELS)) $errors[] = "Invalid skill level";
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Update program
    $stmt = $pdo->prepare("UPDATE programs SET name = ?, description = ?, coachName = ?, 
                          contact = ?, duration = ?, skillLevel = ? WHERE id = ?");
    $stmt->execute([$name, $description, $coachName, $contact, $duration, $skillLevel, $programId]);
    
    return ['success' => true, 'message' => "Program updated successfully"];
}

/**
 * Delete a program
 */
function deleteProgram($pdo, $programId) {
    // Check if program exists
    $stmt = $pdo->prepare("SELECT id FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'errors' => ["Program not found"]];
    }
    
    // Delete the program (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    
    return ['success' => true, 'message' => "Program deleted successfully"];
}

/**
 * Enrol a gymnast in a program
 */
function enrolGymnast($pdo, $programId, $name, $age, $experienceLevel) {
    // Input validation
    $errors = [];
    if (empty($name)) $errors[] = "Gymnast name is required";
    if (!is_numeric($age) || $age < 5 || $age > 100) $errors[] = "Age must be a number between 5 and 100";
    if (!in_array($experienceLevel, SKILL_LEVELS)) $errors[] = "Invalid experience level";
    
    // Check if program exists and get its skill level
    $stmt = $pdo->prepare("SELECT id, skillLevel FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        $errors[] = "Program not found";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check if program skill level matches gymnast experience
    if ($program['skillLevel'] !== $experienceLevel) {
        $errors[] = "Gymnast experience level does not match program requirements";
        return ['success' => false, 'errors' => $errors];
    }
    
    // Create enrolment
    $stmt = $pdo->prepare("INSERT INTO enrolments (programId, gymnastName, age, experienceLevel) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$programId, $name, $age, $experienceLevel]);
    
    // Get program name for notification
    $stmt = $pdo->prepare("SELECT name FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Notify coach (in a real system, this would send an email)
    $coachNotification = "New enrolment: $name has enrolled in {$program['name']}";
    
    return ['success' => true, 'message' => "Enrolment successful. $coachNotification", 'id' => $pdo->lastInsertId()];
}

/**
 * Delete an enrolment and related records
 */
function deleteEnrolment($pdo, $enrolmentId) {
    // Check if enrolment exists
    $stmt = $pdo->prepare("SELECT id FROM enrolments WHERE id = ?");
    $stmt->execute([$enrolmentId]);
    if ($stmt->rowCount() === 0) {
        return ['success' => false, 'errors' => ["Enrolment not found"]];
    }
    
    // Delete the enrolment (cascade will handle related records)
    $stmt = $pdo->prepare("DELETE FROM enrolments WHERE id = ?");
    $stmt->execute([$enrolmentId]);
    
    return ['success' => true, 'message' => "Enrolment deleted successfully"];
}

/**
 * Mark attendance for a session
 */
function markAttendance($pdo, $enrolmentId, $sessionDate, $attended) {
    // Input validation
    $errors = [];
    if (empty($sessionDate)) $errors[] = "Session date is required";
    
    // Check if enrolment exists
    $stmt = $pdo->prepare("SELECT id FROM enrolments WHERE id = ?");
    $stmt->execute([$enrolmentId]);
    if ($stmt->rowCount() === 0) {
        $errors[] = "Enrolment not found";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Record attendance
    $stmt = $pdo->prepare("INSERT INTO attendance (enrolmentId, sessionDate, attended) VALUES (?, ?, ?)");
    $stmt->execute([$enrolmentId, $sessionDate, $attended ? 1 : 0]);
    
    return ['success' => true, 'message' => "Attendance recorded successfully"];
}

/**
 * Record progress for a gymnast
 */
function recordProgress($pdo, $enrolmentId, $sessionDate, $notes, $score) {
    // Input validation
    $errors = [];
    if (empty($sessionDate)) $errors[] = "Session date is required";
    if (empty($notes)) $errors[] = "Progress notes are required";
    if (!is_numeric($score) || $score < 0 || $score > 100) $errors[] = "Score must be a number between 0 and 100";
    
    // Check if enrolment exists
    $stmt = $pdo->prepare("SELECT id FROM enrolments WHERE id = ?");
    $stmt->execute([$enrolmentId]);
    if ($stmt->rowCount() === 0) {
        $errors[] = "Enrolment not found";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Record progress
    $stmt = $pdo->prepare("INSERT INTO progress (enrolmentId, sessionDate, notes, score) VALUES (?, ?, ?, ?)");
    $stmt->execute([$enrolmentId, $sessionDate, $notes, $score]);
    
    return ['success' => true, 'message' => "Progress recorded successfully"];
}

/**
 * Calculate progress percentage for a gymnast in a program
 */
function calculateProgress($pdo, $enrolmentId) {
    // Get enrolment and program details
    $stmt = $pdo->prepare("SELECT e.*, p.duration FROM enrolments e 
                          JOIN programs p ON e.programId = p.id WHERE e.id = ?");
    $stmt->execute([$enrolmentId]);
    $enrolment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrolment) {
        return 0;
    }
    
    // Count completed sessions (based on attendance)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance 
                          WHERE enrolmentId = ? AND attended = 1");
    $stmt->execute([$enrolmentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $completedSessions = $result['count'];
    
    // Calculate percentage
    if ($enrolment['duration'] > 0) {
        return min(100, round(($completedSessions / $enrolment['duration']) * 100));
    }
    
    return 0;
}

/**
 * Get all enrolments for a program
 */
function getProgramEnrolments($pdo, $programId) {
    $stmt = $pdo->prepare("SELECT * FROM enrolments WHERE programId = ?");
    $stmt->execute([$programId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Filter programs based on search criteria
 */
function filterPrograms($pdo, $searchTerm = '', $skillLevel = '') {
    $query = "SELECT p.*, COUNT(e.id) as enrolmentCount FROM programs p 
              LEFT JOIN enrolments e ON p.id = e.programId";
    
    $conditions = [];
    $params = [];
    
    if (!empty($searchTerm)) {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.coachName LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($skillLevel)) {
        $conditions[] = "p.skillLevel = ?";
        $params[] = $skillLevel;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $query .= " GROUP BY p.id";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get attendance records for a specific enrolment
 */
function getAttendanceRecords($pdo, $enrolmentId) {
    $stmt = $pdo->prepare("SELECT * FROM attendance WHERE enrolmentId = ? ORDER BY sessionDate DESC");
    $stmt->execute([$enrolmentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all programs
 */
function getAllPrograms($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM programs");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all enrolments
 */
function getAllEnrolments($pdo) {
    $stmt = $pdo->prepare("SELECT e.*, p.name as programName FROM enrolments e 
                          JOIN programs p ON e.programId = p.id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all progress records
 */
function getAllProgress($pdo) {
    $stmt = $pdo->prepare("SELECT pr.*, e.gymnastName, p.name as programName 
                          FROM progress pr 
                          JOIN enrolments e ON pr.enrolmentId = e.id 
                          JOIN programs p ON e.programId = p.id 
                          ORDER BY pr.sessionDate DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get attendance statistics
 */
function getAttendanceStats($pdo) {
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as totalSessions,
                          SUM(attended) as attendedSessions
                          FROM attendance");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Process form submissions
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_program':
                $result = addProgram(
                    $pdo,
                    $_POST['name'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['coachName'] ?? '',
                    $_POST['contact'] ?? '',
                    $_POST['duration'] ?? 0,
                    $_POST['skillLevel'] ?? ''
                );
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;
                
            case 'edit_program':
                $result = editProgram(
                    $pdo,
                    $_POST['programId'] ?? '',
                    $_POST['name'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['coachName'] ?? '',
                    $_POST['contact'] ?? '',
                    $_POST['duration'] ?? 0,
                    $_POST['skillLevel'] ?? ''
                );
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;
                
            case 'delete_program':
                $result = deleteProgram($pdo, $_POST['programId'] ?? '');
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;
                
            case 'enrol_gymnast':
                $result = enrolGymnast(
                    $pdo,
                    $_POST['programId'] ?? '',
                    $_POST['name'] ?? '',
                    $_POST['age'] ?? 0,
                    $_POST['experienceLevel'] ?? ''
                );
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;

            case 'delete_enrolment':
                $result = deleteEnrolment($pdo, $_POST['enrolmentId'] ?? '');
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;

            case 'mark_attendance':
                $result = markAttendance(
                    $pdo,
                    $_POST['enrolmentId'] ?? '',
                    $_POST['sessionDate'] ?? '',
                    $_POST['attended'] ?? false
                );
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;
                
            case 'record_progress':
                $result = recordProgress(
                    $pdo,
                    $_POST['enrolmentId'] ?? '',
                    $_POST['sessionDate'] ?? '',
                    $_POST['notes'] ?? '',
                    $_POST['score'] ?? 0
                );
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $errors = $result['errors'];
                }
                break;
        }
    }
}

// Get search/filter parameters
$searchTerm = $_GET['search'] ?? '';
$filterLevel = $_GET['skillLevel'] ?? '';
$filteredPrograms = filterPrograms($pdo, $searchTerm, $filterLevel);

// Get all programs for dropdowns
$allPrograms = getAllPrograms($pdo);

// Get all enrolments for display
$allEnrolments = getAllEnrolments($pdo);

// Get all progress records
$allProgress = getAllProgress($pdo);

// Get attendance stats
$attendanceStats = getAttendanceStats($pdo);

// Check if we're editing a program
$editingProgram = null;
if (isset($_GET['edit'])) {
    $programId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM programs WHERE id = ?");
    $stmt->execute([$programId]);
    $editingProgram = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if we're viewing attendance for a specific enrolment
$viewingAttendance = null;
if (isset($_GET['view_attendance'])) {
    $enrolmentId = $_GET['view_attendance'];
    $stmt = $pdo->prepare("SELECT e.*, p.name as programName FROM enrolments e 
                          JOIN programs p ON e.programId = p.id WHERE e.id = ?");
    $stmt->execute([$enrolmentId]);
    $viewingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewingAttendance) {
        $viewingAttendance['records'] = getAttendanceRecords($pdo, $enrolmentId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vitalize Gymnastics Management System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- ===== NAVBAR (Visible only when logged in) ===== -->
    <nav class="navbar">
        <div class="navbar-left">
            <img src="acrobat.png" alt="Acrobat Logo" class="acrobat_logo"> Vitalize Gymnastics
        </div>
        <div class="navbar-right">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>
    
    <header>
        <div class="container">
            <h1><i class="fas fa-medal"></i> Vitalize Gymnastics</h1>
            <p>Comprehensive Management System for Coaches and Athletes</p>
        </div>
    </header>
    
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-label">Total Programs</div>
                <div class="stat-number"><?php echo count($allPrograms); ?></div>
                <div class="stat-desc">Active training programs</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Enrolments</div>
                <div class="stat-number"><?php echo count($allEnrolments); ?></div>
                <div class="stat-desc">Gymnast registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Attendance Rate</div>
                <div class="stat-number">
                    <?php
                    $totalSessions = $attendanceStats['totalSessions'] ?? 0;
                    $attendedSessions = $attendanceStats['attendedSessions'] ?? 0;
                    echo $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100) : 0;
                    ?>%
                </div>
                <div class="stat-desc">Overall session attendance</div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="switchTab('programs')">
                <i class="fas fa-dumbbell"></i> Programs
            </div>
            <div class="tab" onclick="switchTab('enrolment')">
                <i class="fas fa-user-plus"></i> Enrol Gymnasts
            </div>
            <div class="tab" onclick="switchTab('attendance')">
                <i class="fas fa-clipboard-check"></i> Attendance & Progress
            </div>
        </div>
        
        <!-- Programs Tab -->
        <div id="programs" class="tab-content active">
            <div class="card">
                <h2><i class="fas fa-plus-circle"></i> <?php echo $editingProgram ? 'Edit Program' : 'Add New Program'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editingProgram ? 'edit_program' : 'add_program'; ?>">
                    <?php if ($editingProgram): ?>
                        <input type="hidden" name="programId" value="<?php echo $editingProgram['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name"><i class="fas fa-tag"></i> Program Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $editingProgram ? htmlspecialchars($editingProgram['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description"><i class="fas fa-align-left"></i> Description</label>
                        <textarea id="description" name="description" required><?php echo $editingProgram ? htmlspecialchars($editingProgram['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="coachName"><i class="fas fa-user-tie"></i> Coach Name</label>
                        <input type="text" id="coachName" name="coachName" value="<?php echo $editingProgram ? htmlspecialchars($editingProgram['coachName']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact"><i class="fas fa-phone"></i> Contact Information</label>
                        <input type="text" id="contact" name="contact" value="<?php echo $editingProgram ? htmlspecialchars($editingProgram['contact']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration"><i class="fas fa-calendar-week"></i> Duration (weeks)</label>
                        <input type="number" id="duration" name="duration" min="1" value="<?php echo $editingProgram ? $editingProgram['duration'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="skillLevel"><i class="fas fa-signal"></i> Skill Level</label>
                        <select id="skillLevel" name="skillLevel" required>
                            <option value="">Select Level</option>
                            <?php foreach (SKILL_LEVELS as $level): ?>
                                <option value="<?php echo $level; ?>" <?php echo ($editingProgram && $editingProgram['skillLevel'] === $level) ? 'selected' : ''; ?>>
                                    <?php echo $level; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit"><i class="fas fa-save"></i> <?php echo $editingProgram ? 'Update Program' : 'Add Program'; ?></button>
                    
                    <?php if ($editingProgram): ?>
                        <a href="index.php"><button type="button"><i class="fas fa-times"></i> Cancel</button></a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-list"></i> Program List</h2>
                
                <div class="search-filter">
                    <input type="text" placeholder="Search programs..." id="searchInput" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <select id="levelFilter">
                        <option value="">All Levels</option>
                        <?php foreach (SKILL_LEVELS as $level): ?>
                            <option value="<?php echo $level; ?>" <?php echo $filterLevel === $level ? 'selected' : ''; ?>>
                                <?php echo $level; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="applyFilters()"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Program Name</th>
                            <th>Coach</th>
                            <th>Duration</th>
                            <th>Skill Level</th>
                            <th>Enrolled Gymnasts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredPrograms)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No programs found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredPrograms as $program): ?>
                                <tr>
                                    <td><div class="gymnast-icon"><i class="fas fa-dumbbell"></i></div> <?php echo htmlspecialchars($program['name']); ?></td>
                                    <td><div class="coach-icon"><i class="fas fa-user-tie"></i></div> <?php echo htmlspecialchars($program['coachName']); ?></td>
                                    <td><?php echo $program['duration']; ?> weeks</td>
                                    <td><span class="badge"><?php echo $program['skillLevel']; ?></span></td>
                                    <td><?php echo $program['enrolmentCount']; ?> enrolled</td>
                                    <td class="action-buttons">
                                        <a href="?edit=<?php echo $program['id']; ?>"><button><i class="fas fa-edit"></i> Edit</button></a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_program">
                                            <input type="hidden" name="programId" value="<?php echo $program['id']; ?>">
                                            <button type="submit" class="delete" onclick="return confirm('Are you sure you want to delete this program?')"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                        <a href="?view=<?php echo $program['id']; ?>#enrolment"><button><i class="fas fa-users"></i> Enrolments</button></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Enrolment Tab -->
        <div id="enrolment" class="tab-content">
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Enrol Gymnast in Program</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="enrol_gymnast">
                    
                    <div class="form-group">
                        <label for="enrolProgram"><i class="fas fa-dumbbell"></i> Select Program</label>
                        <select id="enrolProgram" name="programId" required>
                            <option value="">Select Program</option>
                            <?php foreach ($allPrograms as $program): ?>
                                <option value="<?php echo $program['id']; ?>">
                                    <?php echo htmlspecialchars($program['name']); ?> (<?php echo $program['skillLevel']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="gymnastName"><i class="fas fa-user"></i> Gymnast Name</label>
                        <input type="text" id="gymnastName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="age"><i class="fas fa-birthday-cake"></i> Age</label>
                        <input type="number" id="age" name="age" min="5" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="experienceLevel"><i class="fas fa-signal"></i> Experience Level</label>
                        <select id="experienceLevel" name="experienceLevel" required>
                            <option value="">Select Level</option>
                            <?php foreach (SKILL_LEVELS as $level): ?>
                                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit"><i class="fas fa-user-plus"></i> Enrol Gymnast</button>
                </form>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-users"></i> Enrolment Management</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Gymnast Name</th>
                            <th>Age</th>
                            <th>Program</th>
                            <th>Experience Level</th>
                            <th>Progress</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allEnrolments)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No enrolments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allEnrolments as $enrolment): 
                                $progress = calculateProgress($pdo, $enrolment['id']);
                            ?>
                                <tr>
                                    <td><div class="gymnast-icon"><i class="fas fa-user"></i></div> <?php echo htmlspecialchars($enrolment['gymnastName']); ?></td>
                                    <td><?php echo $enrolment['age']; ?></td>
                                    <td><?php echo htmlspecialchars($enrolment['programName']); ?></td>
                                    <td><?php echo $enrolment['experienceLevel']; ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress; ?>%;">
                                                <?php echo $progress; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="?enrolment=<?php echo $enrolment['id']; ?>#attendance">
                                            <button><i class="fas fa-clipboard-check"></i> Attendance</button>
                                        </a>
                                        <a href="?view_attendance=<?php echo $enrolment['id']; ?>#attendance">
                                            <button><i class="fas fa-history"></i> View Records</button>
                                        </a>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_enrolment">
                                            <input type="hidden" name="enrolmentId" value="<?php echo $enrolment['id']; ?>">
                                            <button type="submit" class="delete" onclick="return confirm('Are you sure you want to delete this enrolment? All related attendance and progress records will also be removed.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Attendance Tab -->
        <div id="attendance" class="tab-content">
            <?php if ($viewingAttendance): ?>
                <div class="card">
                    <h2><i class="fas fa-history"></i> Attendance History for <?php echo htmlspecialchars($viewingAttendance['gymnastName']); ?></h2>
                    <p><strong>Program:</strong> <?php echo htmlspecialchars($viewingAttendance['programName']); ?></p>
                    
                    <?php if (empty($viewingAttendance['records'])): ?>
                        <p style="margin-top: 20px; text-align: center;">No attendance records found for this gymnast.</p>
                    <?php else: ?>
                        <div class="attendance-list">
                            <?php foreach ($viewingAttendance['records'] as $record): ?>
                                <div class="attendance-item">
                                    <div class="attendance-info">
                                        <span class="attendance-date"><?php echo $record['sessionDate']; ?></span>
                                        <span class="attendance-status">
                                            <?php if ($record['attended']): ?>
                                                <span class="attendance-badge attendance-present">
                                                    <i class="fas fa-check-circle"></i> Present
                                                </span>
                                            <?php else: ?>
                                                <span class="attendance-badge attendance-absent">
                                                    <i class="fas fa-times-circle"></i> Absent
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="attendance-actions">
                                        <small>Recorded: <?php echo date('M j, Y', strtotime($record['recordedAt'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px;">
                        <a href="index.php#attendance"><button><i class="fas fa-arrow-left"></i> Back to Attendance</button></a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <h2><i class="fas fa-clipboard-check"></i> Mark Attendance</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="mark_attendance">
                        
                        <div class="form-group">
                            <label for="attendanceEnrolment"><i class="fas fa-user"></i> Select Enrolment</label>
                            <select id="attendanceEnrolment" name="enrolmentId" required>
                                <option value="">Select Enrolment</option>
                                <?php foreach ($allEnrolments as $enrolment): ?>
                                    <option value="<?php echo $enrolment['id']; ?>">
                                        <?php echo htmlspecialchars($enrolment['gymnastName']); ?> - <?php echo htmlspecialchars($enrolment['programName']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sessionDate"><i class="fas fa-calendar-day"></i> Session Date</label>
                            <input type="date" id="sessionDate" name="sessionDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="attended" value="1" checked>
                                Attended
                            </label>
                        </div>
                        
                        <button type="submit"><i class="fas fa-check-circle"></i> Record Attendance</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2><i class="fas fa-chart-line"></i> Record Progress</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="record_progress">
                        
                        <div class="form-group">
                            <label for="progressEnrolment"><i class="fas fa-user"></i> Select Enrolment</label>
                            <select id="progressEnrolment" name="enrolmentId" required>
                                <option value="">Select Enrolment</option>
                                <?php foreach ($allEnrolments as $enrolment): ?>
                                    <option value="<?php echo $enrolment['id']; ?>">
                                        <?php echo htmlspecialchars($enrolment['gymnastName']); ?> - <?php echo htmlspecialchars($enrolment['programName']); ?>
                                    </option>
                                <?php endforeach; ?>
                                </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="progressDate"><i class="fas fa-calendar-day"></i> Session Date</label>
                            <input type="date" id="progressDate" name="sessionDate" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="score"><i class="fas fa-star"></i> Score (0-100)</label>
                            <input type="number" id="score" name="score" min="0" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes"><i class="fas fa-sticky-note"></i> Progress Notes</label>
                            <textarea id="notes" name="notes" required></textarea>
                        </div>
                        
                        <button type="submit"><i class="fas fa-save"></i> Record Progress</button>
                    </form>
                </div>
                
                <div class="card">
                    <h2><i class="fas fa-history"></i> Progress Tracking</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Gymnast</th>
                                <th>Program</th>
                                <th>Session Date</th>
                                <th>Score</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allProgress)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No progress records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allProgress as $progress): ?>
                                    <tr>
                                        <td><div class="gymnast-icon"><i class="fas fa-user"></i></div> <?php echo htmlspecialchars($progress['gymnastName']); ?></td>
                                        <td><?php echo htmlspecialchars($progress['programName']); ?></td>
                                        <td><?php echo $progress['sessionDate']; ?></td>
                                        <td><strong><?php echo $progress['score']; ?>%</strong></td>
                                        <td><?php echo htmlspecialchars($progress['notes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Update active tab
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Find and activate the clicked tab
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.textContent.includes('Programs') && tabName === 'programs') {
                    tab.classList.add('active');
                } else if (tab.textContent.includes('Enrol Gymnasts') && tabName === 'enrolment') {
                    tab.classList.add('active');
                } else if (tab.textContent.includes('Attendance') && tabName === 'attendance') {
                    tab.classList.add('active');
                }
            });
        }
        
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value;
            const skillLevel = document.getElementById('levelFilter').value;
            
            let url = 'index.php?';
            if (searchTerm) url += `search=${encodeURIComponent(searchTerm)}&`;
            if (skillLevel) url += `skillLevel=${encodeURIComponent(skillLevel)}`;
            
            window.location.href = url;
        }
        
        // Handle URL hash to switch to specific tabs
        window.addEventListener('load', function() {
            const hash = window.location.hash.substring(1);
            if (hash === 'enrolment' || hash === 'attendance') {
                switchTab(hash);
            }
            
            // Check if we have a specific enrolment or program to view
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('view')) {
                switchTab('enrolment');
            } else if (urlParams.has('enrolment') || urlParams.has('view_attendance')) {
                switchTab('attendance');
            }
            
            // Set today's date as default for date fields
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('sessionDate').value = today;
            document.getElementById('progressDate').value = today;
        });
    </script>
</body>
</html>