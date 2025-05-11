<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'momms_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

// Sanitize and validate input
function sanitize($data) {
    return htmlspecialchars(trim($data));
}

$firstname = sanitize($_POST['firstname'] ?? '');
$middlename = sanitize($_POST['middlename'] ?? '');
$lastname = sanitize($_POST['lastname'] ?? '');
$sex = sanitize($_POST['sex'] ?? '');
$contact = sanitize($_POST['contact'] ?? '');
$dob = sanitize($_POST['dob'] ?? '');
$email = sanitize($_POST['email'] ?? '');
$relationship = sanitize($_POST['relationship'] ?? '');
$nationality = sanitize($_POST['nationality'] ?? '');
$role = sanitize($_POST['role'] ?? '');
$employment_type = sanitize($_POST['employment_type'] ?? '');
$address = sanitize($_POST['address'] ?? '');
$branch = sanitize($_POST['branch'] ?? '');
$date_of_employment = sanitize($_POST['date_of_employment'] ?? '');

// Required fields check
if (empty($firstname) || empty($lastname) || empty($email)) {
    die(json_encode(['status' => 'error', 'message' => 'First name, last name, and email are required.']));
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid email format.']));
}

// Handle image upload
$imagePath = '';
if (isset($_FILES['employee_image']) && $_FILES['employee_image']['error'] === UPLOAD_ERR_OK) {
    $imageTmp = $_FILES['employee_image']['tmp_name'];
    $imageName = uniqid() . '_' . basename($_FILES['employee_image']['name']);
    $uploadDir = 'uploads/';

    // Validate image file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($imageTmp);
    if (!in_array($fileType, $allowedTypes)) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid image format. Only JPG, PNG, and GIF are allowed.']));
    }

    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imagePath = $uploadDir . $imageName;
    move_uploaded_file($imageTmp, $imagePath);
}

// Prepare and execute query
$stmt = $conn->prepare("INSERT INTO employees 
(firstname, middlename, lastname, sex, contact, dob, email, relationship, nationality, role, employment_type, address, branch, date_of_employment, photo) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssssssssssss", 
    $firstname, $middlename, $lastname, $sex, $contact, $dob, $email, 
    $relationship, $nationality, $role, $employment_type, $address, $branch, 
    $date_of_employment, $imagePath
);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Employee saved successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
