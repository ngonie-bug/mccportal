<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if a user is logged in and is an admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_class = '';
$editing_department = null;

// The predefined list of categories from the citizen's dropdown
$categories = [
        'Roads and Infrastructure',
        'Public Transport',
        'Water Supply',
        'Sewerage',
        'pipe bursts/leakes(water fitting)',
        'Fire',
        'Garbage/Refuse Collection',
        'Parks',
        'Community Centers',
        'Sports and Recreational',
        'Libraries',
        'Education',
        'Vocational Training',
        'Public Health and Social Wellfare',
        'Prepaid Parking',
        'Corruption/Crime',
        'General Enquiries',
        'Sewer Blockages',
        'Water Leakages',
        'Replacement of Meter',
        'Street Lighting Issues',
        'Illegal Dumping',
        'Pest Control',
];

// --- Handle DELETE request for a department ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $department_id = intval($_GET['id']);
    
    // Check if department is associated with any staff
    $stmt_staff = $conn->prepare("SELECT COUNT(*) FROM staff WHERE department_id = ?");
    $stmt_staff->bind_param("i", $department_id);
    $stmt_staff->execute();
    $stmt_staff->bind_result($staff_count);
    $stmt_staff->fetch();
    $stmt_staff->close();
    
    // Check if department is associated with any category
    $stmt_category = $conn->prepare("SELECT COUNT(*) FROM category_department_mapping WHERE department_id = ?");
    $stmt_category->bind_param("i", $department_id);
    $stmt_category->execute();
    $stmt_category->bind_result($category_count);
    $stmt_category->fetch();
    $stmt_category->close();

    if ($staff_count > 0) {
        $message = "Cannot delete department. It is assigned to " . $staff_count . " staff member(s).";
        $message_class = 'bg-red-100 text-red-700';
    } else if ($category_count > 0) {
        $message = "Cannot delete department. It is linked to " . $category_count . " report category(s). Please unlink it first.";
        $message_class = 'bg-red-100 text-red-700';
    } else {
        $stmt_delete = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt_delete->bind_param("i", $department_id);
        if ($stmt_delete->execute()) {
            $message = "Department deleted successfully!";
            $message_class = 'bg-green-100 text-green-700';
        } else {
            $message = "Error deleting department: " . $conn->error;
            $message_class = 'bg-red-100 text-red-700';
        }
        $stmt_delete->close();
    }
}

// --- Handle DELETE request for a category mapping ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_mapping' && isset($_GET['id'])) {
    $mapping_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM category_department_mapping WHERE id = ?");
    $stmt->bind_param("i", $mapping_id);
    if ($stmt->execute()) {
        $message = "Category mapping deleted successfully!";
        $message_class = 'bg-green-100 text-green-700';
    } else {
        $message = "Error deleting mapping: " . $conn->error;
        $message_class = 'bg-red-100 text-red-700';
    }
    $stmt->close();
}

// --- Handle POST requests (Add/Edit Department & Add Mapping) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_department' || $_POST['action'] === 'edit_department') {
        $department_name = trim($_POST['department_name']);
        $description = trim($_POST['description']);
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;

        if (!empty($department_name)) {
            if ($_POST['action'] === 'add_department') {
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM departments WHERE department_name = ?");
                $stmt_check->bind_param("s", $department_name);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $message = "Error: A department with this name already exists.";
                    $message_class = 'bg-red-100 text-red-700';
                } else {
                    $stmt = $conn->prepare("INSERT INTO departments (department_name, description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $department_name, $description);
                    if ($stmt->execute()) {
                        $message = "Department added successfully!";
                        $message_class = 'bg-green-100 text-green-700';
                    } else {
                        $message = "Error adding department: " . $conn->error;
                        $message_class = 'bg-red-100 text-red-700';
                    }
                    $stmt->close();
                }
            } elseif ($_POST['action'] === 'edit_department' && $department_id > 0) {
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM departments WHERE department_name = ? AND department_id != ?");
                $stmt_check->bind_param("si", $department_name, $department_id);
                $stmt_check->execute();
                $stmt_check->bind_result($count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($count > 0) {
                    $message = "Error: A department with this name already exists.";
                    $message_class = 'bg-red-100 text-red-700';
                } else {
                    $stmt = $conn->prepare("UPDATE departments SET department_name = ?, description = ? WHERE department_id = ?");
                    $stmt->bind_param("ssi", $department_name, $description, $department_id);
                    if ($stmt->execute()) {
                        $message = "Department updated successfully!";
                        $message_class = 'bg-green-100 text-green-700';
                    } else {
                        $message = "Error updating department: " . $conn->error;
                        $message_class = 'bg-red-100 text-red-700';
                    }
                    $stmt->close();
                }
            }
        } else {
            $message = "Department name cannot be empty.";
            $message_class = 'bg-red-100 text-red-700';
        }
    } elseif ($_POST['action'] === 'add_mapping') {
        $category = $_POST['category'];
        $department_id = intval($_POST['department_id']);
        
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM category_department_mapping WHERE category_name = ?");
        $stmt_check->bind_param("s", $category);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            $message = "Error: This category is already mapped to a department.";
            $message_class = 'bg-red-100 text-red-700';
        } else {
            $stmt = $conn->prepare("INSERT INTO category_department_mapping (category_name, department_id) VALUES (?, ?)");
            $stmt->bind_param("si", $category, $department_id);
            if ($stmt->execute()) {
                $message = "Category '{$category}' mapped successfully!";
                $message_class = 'bg-green-100 text-green-700';
            } else {
                $message = "Error adding mapping: " . $conn->error;
                $message_class = 'bg-red-100 text-red-700';
            }
            $stmt->close();
        }
    }
}

// Fetch all existing departments for the dropdown and table
$departments = [];
$sql_departments = "SELECT department_id, department_name, description FROM departments ORDER BY department_name";
$result_departments = $conn->query($sql_departments);
if ($result_departments) {
    while ($row = $result_departments->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch all existing mappings for the table
$mappings = [];
$sql_mappings = "SELECT cdm.id, cdm.category_name, d.department_name
                 FROM category_department_mapping AS cdm
                 JOIN departments AS d ON cdm.department_id = d.department_id
                 ORDER BY cdm.category_name";
$result_mappings = $conn->query($sql_mappings);
if ($result_mappings) {
    while ($row = $result_mappings->fetch_assoc()) {
        $mappings[] = $row;
    }
}

$conn->close();
?>

<div class="container-fluid mx-auto px-4 py-8">
    <h2 class="text-3xl font-bold text-gray-800 mb-6">Manage Departments & Categories</h2>

    <?php if ($message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?= htmlspecialchars($message_class) ?>" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Form to Add/Edit a Department -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 id="form-title" class="text-xl font-bold text-gray-800 mb-4">Add or Edit a Department</h3>
        <form action="manage_departments.php" method="POST" class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <input type="hidden" name="action" id="department-action-input" value="add_department">
            <input type="hidden" name="department_id" id="department-id-input" value="">
            <input type="text" name="department_name" id="department-name-input" placeholder="Enter department name" required class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="text" name="description" id="department-description-input" placeholder="Enter description (optional)" class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" id="submit-button" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                <i class="fas fa-plus-circle mr-2"></i>Add Department
            </button>
            <button type="button" id="cancel-button" onclick="resetForm()" class="hidden bg-gray-400 hover:bg-gray-500 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                Cancel
            </button>
        </form>
    </div>
    
    <!-- Form to Map Categories to Departments -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-8">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Map Citizen Categories to Departments</h3>
        <form action="manage_departments.php" method="POST" class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
            <input type="hidden" name="action" value="add_mapping">
            <select name="category" required class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select a Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="department_id" required class="flex-grow p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Select a Department</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= htmlspecialchars($dept['department_id']) ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                <i class="fas fa-link mr-2"></i>Create Mapping
            </button>
        </form>
    </div>

    <!-- Tables to View Existing Data -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Table to View Existing Departments -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <h3 class="text-xl font-bold text-gray-800 p-6">Existing Departments</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No departments found. Add one above.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $department): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($department['department_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($department['department_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editDepartment(<?= htmlspecialchars($department['department_id']) ?>, '<?= htmlspecialchars(addslashes($department['department_name'])) ?>', '<?= htmlspecialchars(addslashes($department['description'])) ?>')" class="text-blue-600 hover:text-blue-900 transition-colors duration-200">Edit</button>
                                    <a href="manage_departments.php?action=delete&id=<?= htmlspecialchars($department['department_id']) ?>" class="text-red-600 hover:text-red-900 transition-colors duration-200 ml-4">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table to View Existing Category Mappings -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <h3 class="text-xl font-bold text-gray-800 p-6">Existing Category Mappings</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mapped Department
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($mappings)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No categories are currently mapped.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mappings as $mapping): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($mapping['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($mapping['department_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="manage_departments.php?action=delete_mapping&id=<?= htmlspecialchars($mapping['id']) ?>" class="text-red-600 hover:text-red-900 transition-colors duration-200">Unlink</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editDepartment(id, name, description) {
        document.getElementById('form-title').innerText = 'Edit Department';
        document.getElementById('department-action-input').value = 'edit_department';
        document.getElementById('department-id-input').value = id;
        document.getElementById('department-name-input').value = name;
        document.getElementById('department-description-input').value = description;
        document.getElementById('submit-button').innerHTML = '<i class="fas fa-edit mr-2"></i>Update Department';
        document.getElementById('submit-button').classList.remove('bg-blue-600', 'hover:bg-blue-700');
        document.getElementById('submit-button').classList.add('bg-green-600', 'hover:bg-green-700');
        document.getElementById('cancel-button').classList.remove('hidden');
    }

    function resetForm() {
        document.getElementById('form-title').innerText = 'Add or Edit a Department';
        document.getElementById('department-action-input').value = 'add_department';
        document.getElementById('department-id-input').value = '';
        document.getElementById('department-name-input').value = '';
        document.getElementById('department-description-input').value = '';
        document.getElementById('submit-button').innerHTML = '<i class="fas fa-plus-circle mr-2"></i>Add Department';
        document.getElementById('submit-button').classList.remove('bg-green-600', 'hover:bg-green-700');
        document.getElementById('submit-button').classList.add('bg-blue-600', 'hover:bg-blue-700');
        document.getElementById('cancel-button').classList.add('hidden');
    }
</script>

<?php include_once 'admin_footer.php'; ?>
