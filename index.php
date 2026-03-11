<?php
session_start();
include("db/config.php");

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    // Get user info including role
    $query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn,$query);

    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_assoc($result);

        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role']; // store the role in session

        // Redirect based on role
        if($row['role'] == 'admin'){
            header("Location: admin/dashboard.php");
            exit();
        } else if($row['role'] == 'user'){
            header("Location: farmer/dashboard.php");
            exit();
        } else {
            echo "<script>alert('Role not recognized');</script>";
        }

    } else {
        $error = "Invalid Username or Password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Chicken Grazing Monitoring</title>
<style>
/* --- General Styles --- */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #007bff, #00c6ff);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* --- Login Card --- */
.login-card {
    background: white;
    width: 350px;
    padding: 40px 30px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
}

.login-card h2 {
    margin-bottom: 25px;
    color: #007bff;
    font-size: 28px;
}

.login-card input {
    width: 90%;
    padding: 12px;
    margin: 10px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 16px;
}

.login-card button {
    width: 95%;
    padding: 12px;
    margin-top: 15px;
    background-color: #28a745;
    border: none;
    border-radius: 5px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
}

.login-card button:hover {
    background-color: #218838;
}

.error {
    color: red;
    margin-top: 10px;
    font-size: 14px;
}

/* --- Responsive --- */
@media (max-width: 400px){
    .login-card { width: 90%; padding: 30px 20px; }
    .login-card input, .login-card button { font-size: 14px; }
}
</style>
</head>
<body>

<div class="login-card">
<h2>Login</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" name="login">Login</button>
</form>
<?php if(isset($error)){ echo "<p class='error'>$error</p>"; } ?>
</div>

</body>
</html>