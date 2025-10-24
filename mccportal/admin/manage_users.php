<?php
session_start();
include_once '../includes/config.php';
include_once 'admin_header.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
header("Location: ../login.php");
exit();
}

// Fetch all users
$sql = "SELECT user_id, full_name, username, email, phone, account_number, stand_number, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Users</h1>

<div class="bg-white rounded-lg shadow-md overflow-hidden">

<table class="min-w-full divide-y divide-gray-200">

<thead class="bg-gray-50">

<tr>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

User ID

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Full Name

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Username

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Role

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Email

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Phone

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Account #

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Stand #

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Created At

</th>

<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

Actions

</th>

</tr>

</thead>

<tbody class="bg-white divide-y divide-gray-200">

<?php if (!empty($users)): ?>

<?php foreach ($users as $user): ?>

<tr>

<td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-800">

<?= htmlspecialchars($user['user_id']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">

<?= htmlspecialchars($user['full_name']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= htmlspecialchars($user['username']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm">

<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full

<?= $user['role'] === 'admin' ? 'bg-blue-100 text-blue-800' : '' ?>

<?= $user['role'] === 'staff' ? 'bg-green-100 text-green-800' : '' ?>

<?= $user['role'] === 'it_help_desk' ? 'bg-indigo-100 text-indigo-800' : '' ?>

<?= $user['role'] === 'auditor' ? 'bg-purple-100 text-purple-800' : '' ?>

<?= $user['role'] === 'citizen' ? 'bg-gray-100 text-gray-800' : '' ?>">

<?= ucwords(str_replace('_', ' ', $user['role'])) ?>

</span>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= htmlspecialchars($user['email']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= htmlspecialchars($user['phone']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= htmlspecialchars($user['account_number']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= htmlspecialchars($user['stand_number']) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">

<?= date('Y-m-d', strtotime($user['created_at'])) ?>

</td>

<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">

<a href="edit_user.php?id=<?= $user['user_id'] ?>" class="text-blue-600 hover:text-blue-900 transition-colors duration-200">Edit</a>

<a href="delete_user.php?id=<?= $user['user_id'] ?>" class="text-red-600 hover:text-red-900 transition-colors duration-200 ml-4" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>

</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="10" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">

No users found.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<?php

include_once 'admin_footer.php';
?>