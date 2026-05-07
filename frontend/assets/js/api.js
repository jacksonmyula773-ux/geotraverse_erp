// API Base URL
const API_BASE = '../../backend/api/';

// Generic API request function
async function apiRequest(endpoint, method = 'GET', data = null) {
    const url = API_BASE + endpoint;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        
        if (response.status === 401) {
            localStorage.clear();
            window.location.href = '../frontend/login_system.html';
            throw new Error('Session expired. Please login again.');
        }
        
        if (response.status === 403) {
            const result = await response.json();
            throw new Error(result.message || 'Access denied. You don\'t have permission for this action.');
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Login function
async function login(email, password) {
    const result = await apiRequest('login.php', 'POST', { email, password });
    if (result.success) {
        localStorage.setItem('user_id', result.user_id);
        localStorage.setItem('user_name', result.name);
        localStorage.setItem('user_role', result.role);
        localStorage.setItem('department_id', result.department_id);
        localStorage.setItem('department_name', result.department_name);
    }
    return result;
}

// Logout function
async function logout() {
    try {
        await apiRequest('logout.php', 'POST');
    } catch (error) {
        console.error('Logout error:', error);
    }
    localStorage.clear();
    window.location.href = '../frontend/login_system.html';
}

// Get current user info
function getCurrentUser() {
    return {
        id: localStorage.getItem('user_id'),
        name: localStorage.getItem('user_name'),
        role: localStorage.getItem('user_role'),
        department_id: localStorage.getItem('department_id'),
        department_name: localStorage.getItem('department_name')
    };
}

// Check if user is logged in
function isLoggedIn() {
    return !!localStorage.getItem('user_id');
}

// Check if user has specific department access
function hasDepartmentAccess(allowedDepartments) {
    const deptId = parseInt(localStorage.getItem('department_id'));
    return allowedDepartments.includes(deptId);
}

// Check if user is Super Admin
function isSuperAdmin() {
    return parseInt(localStorage.getItem('department_id')) === 1;
}

// Export functions for use in HTML
window.apiRequest = apiRequest;
window.login = login;
window.logout = logout;
window.getCurrentUser = getCurrentUser;
window.isLoggedIn = isLoggedIn;
window.hasDepartmentAccess = hasDepartmentAccess;
window.isSuperAdmin = isSuperAdmin;