function showError(message) {
    document.getElementById("error-message").textContent = message;
}

function validateForm() {
    showError("");

    const name = document.getElementById('name').value.trim();
    const surname = document.getElementById('surname').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const userType = document.getElementById('type').value;

    if (!name || !surname || !email || !password || !userType) {
        showError("All of the fields are required.");
        return false;
    }

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) {
        showError("Please enter an email address that is valid.");
        return false;
    }

    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/;
    if (!passwordRegex.test(password)) {
        showError("The password must be at least 8 characters with a uppercase, lowercase, number and symbol.");
        return false;
    }

    checkEmailAvailability(email, name, surname, password, userType);
    return false;
}

function checkEmailAvailability(email, name, surname, password, userType) {
    fetch("http://localhost/COS221/api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            type: "CheckEmail",
            email: email
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "error" && data.message === "Email already exists") {
            showError("This email above is already registered. Please use another email to register.");
        } else {
            registerUser(name, surname, email, password, userType);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        showError("An error occurred while checking the email.");
    });
}



function registerUser(name, surname, email, password, userType) {
    fetch("http://localhost/COS221/api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            type: "Register",
            name: name,
            surname: surname,
            email: email,
            password: password,
            user_type: userType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            if (confirm("You have successfully registered.")) {
                window.location.href = "http://localhost/COS221/html/index.php";
            }            
        } else {
            showError("Registration failed: " + (data.message || "Unknown error"));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        showError("An error has occurred during registration.");
    });
}

