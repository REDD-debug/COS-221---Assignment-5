<?php include 'config.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dainty Gems - Signup</title>
    <link rel="stylesheet" href="CSS/signup.css">
    <script src="sneaker-api-loader/validation.js"></script>
</head>
<body>
    <div class="container">
        <h2>Signup</h2>
        <form id="signupForm" method="POST" action="api.php" onsubmit="return validateForm()" autocomplete="off">
            <label for="name">First Name:</label>
            <input type="text" id="name" name="name" required autocomplete="off"><br><br>

            <label for="surname">Last Name:</label>
            <input type="text" id="surname" name="surname" required autocomplete="off"><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required autocomplete="off"><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <small>Password must be at least 8 characters long and include uppercase, lowercase, a number, and a special symbol.</small><br><br>

            <label for="type">Type:</label>
            <select id="type" name="type" required autocomplete="off">
                <option value="Customer">Customer</option>
            </select><br><br>

            <div id="error-message" style="color: red; margin: 10px 0;"></div>
            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>