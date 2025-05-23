<?php
session_start();
include 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $_SESSION['user'] = [
        'username' => htmlspecialchars($_POST['username']),
        'api_key' => $_POST['api_key'],
        'logged_in' => true
    ];
    
    setcookie('apiKey', $_POST['api_key'], [
        'expires' => time() + 86400,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    header("Location: index.php");
    exit();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Dainty Gems</title>
    <style>
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        #loginMessage {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .error {
            background-color: #ffdddd;
            color: #d8000c;
        }
        .success {
            background-color: #ddffdd;
            color: #4F8A10;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
            <div id="loginMessage"></div>
        </form>
        <p>Don't have an account? <a href="signup.php">Sign up here</a>.</p>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const messageEl = document.getElementById('loginMessage');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            messageEl.textContent = '';
            messageEl.className = '';
            
            if (!email.includes('@') || !email.includes('.')) {
                messageEl.textContent = 'Please enter a valid email address';
                messageEl.className = 'error';
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in soon...';
            
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

                const data = await response.json();
                console.log("API Response:", data);

                if (data.status === "success") {
                    localStorage.setItem('apiKey', data.data[0].apikey);
                    
                    document.cookie = `apiKey=${data.data[0].apikey}; path=/; max-age=86400; Secure; SameSite=Strict`;
                    
                    const hiddenForm = document.createElement('form');
                    hiddenForm.method = 'POST';
                    hiddenForm.action = 'login.php';
                    
                    hiddenForm.innerHTML = `
                        <input type="hidden" name="username" value="${data.data[0].username || email.split('@')[0]}">
                        <input type="hidden" name="api_key" value="${data.data[0].apikey}">
                    `;
                    document.body.appendChild(hiddenForm);
                    hiddenForm.submit();
                    
                } else {
                    messageEl.textContent = data.message || 'You have failed to login. Please try again.';
                    messageEl.className = 'error';
                }
            } catch (error) {
                messageEl.textContent = 'There is a network error. Please try again.';
                messageEl.className = 'error';
                console.error('There is a login error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
            }
        });
    </script>

</body>    
</html>