<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF Token if function exists
    if (function_exists('verify_csrf_token')) {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            header('Location: profile.php');
            exit();
        }
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if user wants to change password
    $changePassword = false;
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
        $changePassword = true;
        
        // Validate password change
        if (empty($currentPassword)) {
            $_SESSION['error'] = 'Please enter your current password to change it';
            header('Location: profile.php');
            exit();
        }
        
        if (empty($newPassword)) {
            $_SESSION['error'] = 'Please enter a new password';
            header('Location: profile.php');
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'New passwords do not match';
            header('Location: profile.php');
            exit();
        }
        
        // Validate password strength
        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'New password must be at least 8 characters long';
            header('Location: profile.php');
            exit();
        }
        
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $_SESSION['error'] = 'New password must contain at least one uppercase letter';
            header('Location: profile.php');
            exit();
        }
        
        if (!preg_match('/[a-z]/', $newPassword)) {
            $_SESSION['error'] = 'New password must contain at least one lowercase letter';
            header('Location: profile.php');
            exit();
        }
        
        if (!preg_match('/[0-9]/', $newPassword)) {
            $_SESSION['error'] = 'New password must contain at least one number';
            header('Location: profile.php');
            exit();
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
            $_SESSION['error'] = 'New password must contain at least one special character';
            header('Location: profile.php');
            exit();
        }
        
        // Verify current password
        try {
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentUser || !password_verify($currentPassword, $currentUser['password'])) {
                $_SESSION['error'] = 'Current password is incorrect';
                header('Location: profile.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error verifying password';
            header('Location: profile.php');
            exit();
        }
    }
    
    // Sanitize all form data
    $sanitizeFunc = function_exists('sanitize_input') ? 'sanitize_input' : 'trim';
    
    $first_name = $sanitizeFunc($_POST['first_name'] ?? '');
    $middle_name = $sanitizeFunc($_POST['middle_name'] ?? '');
    $last_name = $sanitizeFunc($_POST['last_name'] ?? '');
    $country = $sanitizeFunc($_POST['country'] ?? '');
    $region = $sanitizeFunc($_POST['region'] ?? '');
    $city = $sanitizeFunc($_POST['city'] ?? '');
    $suffix = $sanitizeFunc($_POST['suffix'] ?? '');
    $permanent_address = $sanitizeFunc($_POST['permanent_address'] ?? '');
    $sex = $sanitizeFunc($_POST['sex'] ?? '');
    $id_type = $sanitizeFunc($_POST['id_type'] ?? '');
    $phone = $sanitizeFunc($_POST['phone'] ?? '');
    
    // Check which columns exist in the database
    try {
        $columnsStmt = $conn->query("DESCRIBE users");
        $allColumns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $allColumns = [];
    }
    
    // Date of Birth validation (only if column exists)
    $date_of_birth = null;
    if (in_array('date_of_birth', $allColumns) && !empty($_POST['date_of_birth'])) {
        $date_of_birth = $_POST['date_of_birth'];
        
        $dob = DateTime::createFromFormat('Y-m-d', $date_of_birth);
        if ($dob) {
            // Check if user is at least 18 years old
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            
            if ($age < 18) {
                $_SESSION['error'] = 'You must be at least 18 years old to use this platform';
                header('Location: profile.php');
                exit();
            }
            
            if ($age > 120) {
                $_SESSION['error'] = 'Invalid date of birth';
                header('Location: profile.php');
                exit();
            }
        }
    }
    
    // Validate sex if provided and column exists
    if (in_array('sex', $allColumns)) {
        $validSex = ['Male', 'Female', 'Other', 'Prefer not to say'];
        if (!empty($sex) && !in_array($sex, $validSex)) {
            // If sex field exists but value is invalid, reset it
            $sex = '';
        }
    }
    
    try {
        // Handle Profile Image Upload (only if upload directory is configured)
        $profileImagePath = null;
        
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Check if validate_image function exists
            if (function_exists('validate_image')) {
                $validation = validate_image($_FILES['profile_image']);
                
                if (!$validation['success']) {
                    $_SESSION['error'] = $validation['message'];
                    header('Location: profile.php');
                    exit();
                }
            } else {
                // Basic validation if function doesn't exist
                if ($_FILES['profile_image']['size'] > 5242880) {
                    $_SESSION['error'] = 'File size exceeds 5MB limit';
                    header('Location: profile.php');
                    exit();
                }
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
                    $_SESSION['error'] = 'Invalid file type. Only JPG, PNG, GIF, and WEBP allowed';
                    header('Location: profile.php');
                    exit();
                }
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = defined('UPLOAD_DIR') ? '../' . UPLOAD_DIR : '../uploads/profile_images/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $newFilename = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
            $targetPath = $uploadDir . $newFilename;
            $dbPath = (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'uploads/profile_images/') . $newFilename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                // Get old profile image to delete
                try {
                    $oldImageStmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
                    $oldImageStmt->execute([$userId]);
                    $oldImage = $oldImageStmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Delete old profile image if exists
                    if ($oldImage && isset($oldImage['profile_image']) && $oldImage['profile_image'] && file_exists('../' . $oldImage['profile_image'])) {
                        unlink('../' . $oldImage['profile_image']);
                    }
                } catch (PDOException $e) {
                    // Ignore error if column doesn't exist
                }
                
                // Add to image history if table exists
                try {
                    $historyStmt = $conn->prepare("UPDATE profile_image_history SET is_current = FALSE WHERE user_id = ?");
                    $historyStmt->execute([$userId]);
                    
                    $historyStmt = $conn->prepare("INSERT INTO profile_image_history 
                                                  (user_id, image_path, original_filename, file_size, mime_type, is_current) 
                                                  VALUES (?, ?, ?, ?, ?, TRUE)");
                    $historyStmt->execute([
                        $userId,
                        $dbPath,
                        $_FILES['profile_image']['name'],
                        $_FILES['profile_image']['size'],
                        $_FILES['profile_image']['type']
                    ]);
                } catch (PDOException $e) {
                    // Table doesn't exist, that's OK
                }
                
                $profileImagePath = $dbPath;
            } else {
                $_SESSION['error'] = 'Failed to upload image. Please try again.';
                header('Location: profile.php');
                exit();
            }
        }
        
        // Build UPDATE query based on available columns
        $updateFields = [];
        $updateValues = [];
        
        // Always available fields
        $updateFields[] = 'first_name = ?';
        $updateValues[] = $first_name;
        
        $updateFields[] = 'middle_name = ?';
        $updateValues[] = $middle_name;
        
        $updateFields[] = 'last_name = ?';
        $updateValues[] = $last_name;
        
        $updateFields[] = 'country = ?';
        $updateValues[] = $country;
        
        $updateFields[] = 'region = ?';
        $updateValues[] = $region;
        
        $updateFields[] = 'city = ?';
        $updateValues[] = $city;
        
        $updateFields[] = 'suffix = ?';
        $updateValues[] = $suffix;
        
        $updateFields[] = 'permanent_address = ?';
        $updateValues[] = $permanent_address;
        
        $updateFields[] = 'sex = ?';
        $updateValues[] = $sex;
        
        $updateFields[] = 'id_type = ?';
        $updateValues[] = $id_type;
        
        $updateFields[] = 'phone = ?';
        $updateValues[] = $phone;
        
        // Optional fields (only if column exists)
        if (in_array('date_of_birth', $allColumns)) {
            $updateFields[] = 'date_of_birth = ?';
            $updateValues[] = $date_of_birth;
        }
        
        if ($profileImagePath && in_array('profile_image', $allColumns)) {
            $updateFields[] = 'profile_image = ?';
            $updateValues[] = $profileImagePath;
        }
        
        if (in_array('last_profile_update', $allColumns)) {
            $updateFields[] = 'last_profile_update = NOW()';
        }
        
        // Update password if requested
        if ($changePassword) {
            $hashedPassword = password_hash($newPassword, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
            $updateFields[] = 'password = ?';
            $updateValues[] = $hashedPassword;
            
            // Log security event if table exists
            try {
                $secStmt = $conn->prepare("INSERT INTO security_events (user_id, event_type, description, ip_address) 
                                          VALUES (?, 'password_change', 'User changed their password', ?)");
                $secStmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            } catch (PDOException $e) {
                // Table doesn't exist, that's OK
            }
        }
        
        // Add user ID at the end
        $updateValues[] = $userId;
        
        // Build and execute query
        $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->execute($updateValues);
        
        if ($changePassword) {
            $_SESSION['success'] = 'Profile and password updated successfully!';
        } else {
            $_SESSION['success'] = 'Profile updated successfully!';
        }
        header('Location: profile.php');
        exit();
        
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        $_SESSION['error'] = 'Update failed. Please try again later.';
        header('Location: profile.php');
        exit();
    }
} else {
    header('Location: profile.php');
    exit();
}
?>