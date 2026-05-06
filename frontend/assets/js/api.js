// frontend/assets/js/api.js
// Central API calls for Super Admin Dashboard

const API_BASE = 'http://localhost/geotraverse/backend/api';

// ==================== AUTHENTICATION ====================
async function checkAuth() {
    try {
        const response = await fetch(`${API_BASE}/check_auth.php`);
        const data = await response.json();
        if (!data.logged_in) {
            window.location.href = 'login_system.html';
        }
        return data;
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = 'login_system.html';
    }
}

async function logout() {
    if (confirm('Are you sure you want to logout?')) {
        try {
            await fetch(`${API_BASE}/logout.php`);
            localStorage.clear();
            window.location.href = 'login_system.html';
        } catch (error) {
            console.error('Logout failed:', error);
            window.location.href = 'login_system.html';
        }
    }
}

// ==================== EMPLOYEES ====================
async function getEmployees() {
    const response = await fetch(`${API_BASE}/get_employees.php`);
    return await response.json();
}

async function addEmployee(employeeData) {
    const response = await fetch(`${API_BASE}/add_employee.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(employeeData)
    });
    return await response.json();
}

async function updateEmployee(employeeId, employeeData) {
    const response = await fetch(`${API_BASE}/update_employee.php?id=${employeeId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(employeeData)
    });
    return await response.json();
}

async function deleteEmployee(employeeId) {
    const response = await fetch(`${API_BASE}/delete_employee.php?id=${employeeId}`, {
        method: 'DELETE'
    });
    return await response.json();
}

async function resetEmployeePassword(employeeId, newPassword) {
    const response = await fetch(`${API_BASE}/reset_password.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: employeeId, password: newPassword })
    });
    return await response.json();
}

// ==================== PROJECTS ====================
async function getProjects() {
    const response = await fetch(`${API_BASE}/get_projects.php`);
    return await response.json();
}

async function addProject(projectData) {
    const response = await fetch(`${API_BASE}/add_project.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(projectData)
    });
    return await response.json();
}

async function updateProject(projectId, projectData) {
    const response = await fetch(`${API_BASE}/update_project.php?id=${projectId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(projectData)
    });
    return await response.json();
}

async function deleteProject(projectId) {
    const response = await fetch(`${API_BASE}/delete_project.php?id=${projectId}`, {
        method: 'DELETE'
    });
    return await response.json();
}

async function sendProjectToDepartment(projectId, departmentId, message) {
    const response = await fetch(`${API_BASE}/send_project.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ project_id: projectId, department_id: departmentId, message: message })
    });
    return await response.json();
}

// ==================== TRANSACTIONS ====================
async function getTransactions() {
    const response = await fetch(`${API_BASE}/get_transactions.php`);
    return await response.json();
}

async function addTransaction(transactionData) {
    const response = await fetch(`${API_BASE}/add_transaction.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(transactionData)
    });
    return await response.json();
}

async function updateTransaction(transactionId, transactionData) {
    const response = await fetch(`${API_BASE}/update_transaction.php?id=${transactionId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(transactionData)
    });
    return await response.json();
}

async function deleteTransaction(transactionId) {
    const response = await fetch(`${API_BASE}/delete_transaction.php?id=${transactionId}`, {
        method: 'DELETE'
    });
    return await response.json();
}

// ==================== MESSAGES ====================
async function getMessages() {
    const response = await fetch(`${API_BASE}/get_messages.php`);
    return await response.json();
}

async function sendMessage(messageData) {
    const response = await fetch(`${API_BASE}/send_message.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(messageData)
    });
    return await response.json();
}

async function deleteMessage(messageId) {
    const response = await fetch(`${API_BASE}/delete_message.php?id=${messageId}`, {
        method: 'DELETE'
    });
    return await response.json();
}

async function markMessageAsRead(messageId) {
    const response = await fetch(`${API_BASE}/mark_message_read.php?id=${messageId}`, {
        method: 'PUT'
    });
    return await response.json();
}

// ==================== REPORTS ====================
async function getReports() {
    const response = await fetch(`${API_BASE}/get_reports.php`);
    return await response.json();
}

async function addReport(reportData) {
    const response = await fetch(`${API_BASE}/add_report.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reportData)
    });
    return await response.json();
}

async function updateReport(reportId, reportData) {
    const response = await fetch(`${API_BASE}/update_report.php?id=${reportId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(reportData)
    });
    return await response.json();
}

async function deleteReport(reportId) {
    const response = await fetch(`${API_BASE}/delete_report.php?id=${reportId}`, {
        method: 'DELETE'
    });
    return await response.json();
}

async function sendReportToAdmin(reportId, message) {
    const response = await fetch(`${API_BASE}/send_report.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ report_id: reportId, message: message })
    });
    return await response.json();
}

// ==================== DASHBOARD STATS ====================
async function getDashboardStats() {
    const response = await fetch(`${API_BASE}/get_dashboard_stats.php`);
    return await response.json();
}

// ==================== BACKUP & RESTORE ====================
async function backupData() {
    const response = await fetch(`${API_BASE}/backup_data.php`);
    return await response.json();
}

async function restoreData(backupFile) {
    const formData = new FormData();
    formData.append('backup_file', backupFile);
    const response = await fetch(`${API_BASE}/restore_data.php`, {
        method: 'POST',
        body: formData
    });
    return await response.json();
}

// ==================== ACTIVITY LOGS ====================
async function getActivityLogs() {
    const response = await fetch(`${API_BASE}/get_activity_logs.php`);
    return await response.json();
}

async function clearActivityLogs() {
    const response = await fetch(`${API_BASE}/clear_logs.php`, {
        method: 'DELETE'
    });
    return await response.json();
}

// ==================== CHANGE PASSWORD ====================
async function changePassword(currentPassword, newPassword) {
    const response = await fetch(`${API_BASE}/change_password.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
    });
    return await response.json();
}