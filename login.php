<?php
session_start();
error_log("Session at start: " . print_r($_SESSION, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['api_key'])) {
    $userType = isset($_POST['user_type']) ? trim($_POST['user_type']) : 'Customer';
    
    $_SESSION['user'] = [
        'username' => htmlspecialchars($_POST['username']),
        'api_key' => $_POST['api_key'],
        'user_type' => $userType,
        'logged_in' => true,
        'login_time' => time()
    ];
    
    error_log("Session after POST: " . print_r($_SESSION['user'], true));
    
    setcookie('apiKey', $_POST['api_key'], [
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    if (strcasecmp($userType, 'Admin') === 0) {
        header("Location: http://localhost/COS221/html/admin_dashboard.php");
    } else {
        header("Location: http://localhost/COS221/html/index.php");
    }
    exit();
}

if (isset($_SESSION['user']) && $_SESSION['user']['logged_in']) {
    if (strcasecmp($_SESSION['user']['user_type'], 'Admin') === 0) {
        header("Location: http://localhost/COS221/html/admin_dashboard.php");
    } else {
        header("Location: http://localhost/COS221/html/index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="container">
        <h2>Welcome Back</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit" id="submitBtn">Sign In</button>
            <div id="loginMessage"></div>
        </form>
        
        <div class="signup-link">
            <p>Don't have an account? <a href="http://localhost/COS221/signup.php">Create one here</a></p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const messageEl = document.getElementById('loginMessage');
            const submitBtn = document.getElementById('submitBtn');
            
            messageEl.innerHTML = '';
            messageEl.className = '';
            
            if (!email) {
                showMessage('Please enter your email address', 'error');
                return;
            }
            
            if (!email.includes('@') || !email.includes('.')) {
                showMessage('Please enter a valid email address', 'error');
                return;
            }
            
            if (!password) {
                showMessage('Please enter your password', 'error');
                return;
            }
            
            if (password.length < 6) {
                showMessage('Password must be at least 6 characters long', 'error');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span>Signing in...';
            
            try {
                const response = await fetch('http://localhost/COS221/api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: "Login",
                        email: email,
                        password: password
                    })
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log("API Response:", data);

                if (data.status === "success" && data.data && data.data[0]) {
                    const userData = data.data[0];
                    const userType = userData.user_type ? userData.user_type.trim() : 'Customer';
                    
                    console.log("Form values:", {
                        username: userData.name || email.split('@')[0],
                        api_key: userData.apikey,
                        user_type: userType
                    });
                    
                    localStorage.setItem('apiKey', userData.apikey);
                    localStorage.setItem('userType', userType);
                    localStorage.setItem('userName', userData.name);
                    
                    document.cookie = `apiKey=${userData.apikey}; path=/; max-age=86400; Secure; SameSite=Strict`;
                    
                    showMessage('Login successful! Redirecting...', 'success');
                    
                    const hiddenForm = document.createElement('form');
                    hiddenForm.method = 'POST';
                    hiddenForm.action = 'http://localhost/COS221/login.php';
                    hiddenForm.style.display = 'none';
                    
                    hiddenForm.innerHTML = `
                        <input type="hidden" name="username" value="${userData.name || email.split('@')[0]}">
                        <input type="hidden" name="api_key" value="${userData.apikey}">
                        <input type="hidden" name="user_type" value="${userType}">
                    `;
                    
                    document.body.appendChild(hiddenForm);
                    
                    setTimeout(() => {
                        hiddenForm.submit();
                    }, 1500);
                    
                } else {
                    const errorMessage = data.message || 'Login failed. Please check your credentials and try again.';
                    showMessage(errorMessage, 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                showMessage('Network error. Please check your connection and try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Sign In';
            }
        });

        function showMessage(message, type) {
            const messageEl = document.getElementById('loginMessage');
            messageEl.innerHTML = message;
            messageEl.className = type;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>    
</html>