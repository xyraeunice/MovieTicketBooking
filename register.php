<?php

/* =========================================================
   START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/db.php";


/* =========================================================
   VARIABLES
========================================================= */

$error = "";
$success = "";


/* =========================================================
   FORM SUBMISSION
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get form data
    $email = trim($_POST["email"] ?? "");
    $given_name = trim($_POST["given_name"] ?? "");
    $surname = trim($_POST["surname"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";
    $phone_number = trim($_POST["phone_number"] ?? "");
    $date_of_birth = $_POST["date_of_birth"] ?? "";


    /* =====================================================
       VALIDATION
    ===================================================== */

    if (
        $email === "" ||
        $given_name === "" ||
        $surname === "" ||
        $password === "" ||
        $confirm_password === "" ||
        $phone_number === "" ||
        $date_of_birth === ""
    ) {

        $error = "Please fill in all fields.";

    }


    /* =====================================================
       EMAIL VALIDATION
    ===================================================== */

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }


    /* =====================================================
       PASSWORD LENGTH
    ===================================================== */

    elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    }


    /* =====================================================
       PASSWORD UPPERCASE
    ===================================================== */

    elseif (!preg_match('/[A-Z]/', $password)) {

        $error = "Password must contain at least one uppercase letter.";

    }


    /* =====================================================
       PASSWORD LOWERCASE
    ===================================================== */

    elseif (!preg_match('/[a-z]/', $password)) {

        $error = "Password must contain at least one lowercase letter.";

    }


    /* =====================================================
       PASSWORD NUMBER
    ===================================================== */

    elseif (!preg_match('/[0-9]/', $password)) {

        $error = "Password must contain at least one number.";

    }


    /* =====================================================
       CONFIRM PASSWORD
    ===================================================== */

    elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    }


    /* =====================================================
       CHECK EMAIL
    ===================================================== */

    else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );


        if (!$check) {

            $error = "Database error: " . $conn->error;

        } else {

            $check->bind_param("s", $email);

            $check->execute();

            $result = $check->get_result();


            /* =================================================
               EMAIL ALREADY EXISTS
            ================================================= */

            if ($result->num_rows > 0) {

                $error = "An account with this email already exists.";

            } else {

                /* =============================================
                   HASH PASSWORD
                ============================================= */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /* =============================================
                   INSERT USER
                ============================================= */

                $sql = "
                    INSERT INTO users
                    (
                        email,
                        given_name,
                        surname,
                        password,
                        phone_number,
                        date_of_birth
                    )
                    VALUES (?, ?, ?, ?, ?, ?)
                ";


                $stmt = $conn->prepare($sql);


                if (!$stmt) {

                    $error = "Database error: " . $conn->error;

                } else {

                    $stmt->bind_param(
                        "ssssss",
                        $email,
                        $given_name,
                        $surname,
                        $hashed_password,
                        $phone_number,
                        $date_of_birth
                    );


                    if ($stmt->execute()) {

                        $success = "Account created successfully! You can now login.";

                    } else {

                        $error = "Registration failed: " . $stmt->error;

                    }


                    $stmt->close();
                }
            }


            $check->close();
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Create Account - CineBook</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {
            margin: 0;

            font-family: "Poppins", sans-serif;

            background: #0d0d0d;

            color: #ffffff;

            min-height: 100vh;
        }


        /* =====================================================
           REGISTER SECTION
        ===================================================== */

        .register-section {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 50px 20px;
        }


        /* =====================================================
           REGISTER CARD
        ===================================================== */

        .register-card {

            width: 100%;

            max-width: 650px;

            background: #181818;

            border-radius: 18px;

            padding: 40px;

            box-shadow:
                0 15px 50px rgba(0, 0, 0, 0.5);
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .register-logo {

            text-align: center;

            margin-bottom: 30px;
        }


        .register-logo i {

            color: #dc3545;

            margin-right: 8px;
        }


        .register-logo h1 {

            font-size: 32px;

            font-weight: 700;

            margin-bottom: 8px;
        }


        .register-logo p {

            color: #999;

            margin: 0;
        }


        /* =====================================================
           LABEL
        ===================================================== */

        .form-label {

            color: #ffffff;

            font-weight: 500;

            margin-bottom: 8px;
        }


        .form-label i {

            color: #dc3545;

            width: 20px;
        }


        /* =====================================================
           INPUT
        ===================================================== */

        .form-control {

            background: #ffffff;

            color: #222;

            border: 1px solid #444;

            border-radius: 8px;

            min-height: 50px;

            padding: 12px 15px;
        }


        .form-control:focus {

            background: #ffffff;

            color: #222;

            border-color: #dc3545;

            box-shadow:
                0 0 0 0.2rem rgba(220, 53, 69, 0.2);
        }


        /* =====================================================
           PASSWORD WRAPPER
        ===================================================== */

        .password-wrapper {

            position: relative;
        }


        .password-wrapper .form-control {

            padding-right: 50px;
        }


        .toggle-password {

            position: absolute;

            top: 50%;

            right: 15px;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #777;

            cursor: pointer;

            font-size: 16px;

            z-index: 5;
        }


        .toggle-password:hover {

            color: #dc3545;
        }


        /* =====================================================
           PASSWORD REQUIREMENTS
        ===================================================== */

        .password-requirements {

            margin-top: 10px;

            padding: 12px 14px;

            background: #111111;

            border-radius: 8px;

            border: 1px solid #2b2b2b;

            font-size: 12px;

            color: #999;
        }


        .password-requirements-title {

            color: #ffffff;

            font-weight: 600;

            margin-bottom: 6px;
        }


        .password-requirements div {

            margin: 3px 0;
        }


        .requirement {

            color: #777;
        }


        .requirement.valid {

            color: #28a745;
        }


        .requirement i {

            width: 16px;
        }


        /* =====================================================
           REGISTER BUTTON
        ===================================================== */

        .register-btn {

            width: 100%;

            min-height: 52px;

            background: #dc3545;

            border: none;

            border-radius: 8px;

            color: #ffffff;

            font-size: 16px;

            font-weight: 600;

            margin-top: 10px;

            cursor: pointer;

            transition: 0.3s ease;
        }


        .register-btn:hover {

            background: #bb2d3b;
        }


        /* =====================================================
           LOGIN LINK
        ===================================================== */

        .login-link {

            text-align: center;

            margin-top: 25px;

            color: #999;
        }


        .login-link a {

            color: #dc3545;

            text-decoration: none;

            font-weight: 600;
        }


        .login-link a:hover {

            color: #ff5668;
        }


        /* =====================================================
           ALERT
        ===================================================== */

        .alert {

            border-radius: 8px;

            font-size: 14px;
        }


        /* =====================================================
           BACK HOME
        ===================================================== */

        .back-home {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #aaa;

            text-decoration: none;
        }


        .back-home:hover {

            color: #ffffff;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 576px) {

            .register-section {

                padding: 25px 15px;
            }


            .register-card {

                padding: 25px 20px;
            }


            .register-logo h1 {

                font-size: 27px;
            }

        }

    </style>

</head>


<body>


<section class="register-section">


    <div class="register-card">


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="register-logo">

            <h1>

                <i class="fa-solid fa-film"></i>

                CineBook

            </h1>

            <p>
                Create your account
            </p>

        </div>



        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             SUCCESS
        ================================================== -->

        <?php if (!empty($success)): ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                <?php echo htmlspecialchars($success); ?>

            </div>

        <?php endif; ?>



        <!-- =================================================
             REGISTER FORM
        ================================================== -->

        <form
            method="POST"
            action="register.php"
            id="registerForm"
        >


            <!-- EMAIL -->

            <div class="mb-3">

                <label
                    for="email"
                    class="form-label"
                >

                    <i class="fa-solid fa-envelope"></i>

                    Email Address

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your email address"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    autocomplete="email"
                    required
                >

            </div>



            <!-- NAME -->

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label
                        for="given_name"
                        class="form-label"
                    >

                        <i class="fa-solid fa-user"></i>

                        Given Name

                    </label>


                    <input
                        type="text"
                        id="given_name"
                        name="given_name"
                        class="form-control"
                        placeholder="Enter your given name"
                        value="<?php echo htmlspecialchars($_POST['given_name'] ?? ''); ?>"
                        autocomplete="given-name"
                        required
                    >

                </div>


                <div class="col-md-6 mb-3">

                    <label
                        for="surname"
                        class="form-label"
                    >

                        <i class="fa-solid fa-user"></i>

                        Surname

                    </label>


                    <input
                        type="text"
                        id="surname"
                        name="surname"
                        class="form-control"
                        placeholder="Enter your surname"
                        value="<?php echo htmlspecialchars($_POST['surname'] ?? ''); ?>"
                        autocomplete="family-name"
                        required
                    >

                </div>

            </div>



            <!-- PHONE -->

            <div class="mb-3">

                <label
                    for="phone_number"
                    class="form-label"
                >

                    <i class="fa-solid fa-phone"></i>

                    Phone Number

                </label>


                <input
                    type="tel"
                    id="phone_number"
                    name="phone_number"
                    class="form-control"
                    placeholder="09XXXXXXXXX"
                    value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>"
                    autocomplete="tel"
                    required
                >

            </div>



            <!-- DATE OF BIRTH -->

            <div class="mb-3">

                <label
                    for="date_of_birth"
                    class="form-label"
                >

                    <i class="fa-solid fa-calendar"></i>

                    Date of Birth

                </label>


                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>"
                    required
                >

            </div>



            <!-- PASSWORD -->

            <div class="mb-3">

                <label
                    for="password"
                    class="form-label"
                >

                    <i class="fa-solid fa-lock"></i>

                    Password

                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Create a password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password"
                        aria-label="Show password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>


                <!-- PASSWORD REQUIREMENTS -->

                <div class="password-requirements">

                    <div class="password-requirements-title">
                        Password must contain:
                    </div>


                    <div
                        id="reqLength"
                        class="requirement"
                    >
                        <i class="fa-solid fa-circle-xmark"></i>
                        At least 8 characters
                    </div>


                    <div
                        id="reqUpper"
                        class="requirement"
                    >
                        <i class="fa-solid fa-circle-xmark"></i>
                        At least 1 uppercase letter
                    </div>


                    <div
                        id="reqLower"
                        class="requirement"
                    >
                        <i class="fa-solid fa-circle-xmark"></i>
                        At least 1 lowercase letter
                    </div>


                    <div
                        id="reqNumber"
                        class="requirement"
                    >
                        <i class="fa-solid fa-circle-xmark"></i>
                        At least 1 number
                    </div>

                </div>

            </div>



            <!-- CONFIRM PASSWORD -->

            <div class="mb-3">

                <label
                    for="confirm_password"
                    class="form-label"
                >

                    <i class="fa-solid fa-lock"></i>

                    Confirm Password

                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                        aria-label="Show password"
                    >

                        <i class="fa-solid fa-eye"></i>

                    </button>

                </div>

            </div>



            <!-- SUBMIT -->

            <button
                type="submit"
                class="register-btn"
            >

                <i class="fa-solid fa-user-plus"></i>

                Create Account

            </button>


        </form>



        <!-- LOGIN -->

        <div class="login-link">

            Already have an account?

            <a href="login.php">
                Login
            </a>

        </div>



        <!-- BACK -->

        <a
            href="index.php"
            class="back-home"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Home

        </a>


    </div>

</section>



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =====================================================
   SHOW / HIDE PASSWORD
===================================================== */

document.querySelectorAll(".toggle-password").forEach(function(button) {

    button.addEventListener("click", function() {

        const targetId = this.getAttribute("data-target");

        const passwordInput = document.getElementById(targetId);

        const icon = this.querySelector("i");


        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            icon.classList.remove("fa-eye");

            icon.classList.add("fa-eye-slash");

            this.setAttribute("aria-label", "Hide password");

        } else {

            passwordInput.type = "password";

            icon.classList.remove("fa-eye-slash");

            icon.classList.add("fa-eye");

            this.setAttribute("aria-label", "Show password");
        }

    });

});



/* =====================================================
   PASSWORD REQUIREMENTS
===================================================== */

const passwordInput = document.getElementById("password");

const reqLength = document.getElementById("reqLength");
const reqUpper = document.getElementById("reqUpper");
const reqLower = document.getElementById("reqLower");
const reqNumber = document.getElementById("reqNumber");


function updateRequirement(element, valid) {

    const icon = element.querySelector("i");


    if (valid) {

        element.classList.add("valid");

        icon.classList.remove("fa-circle-xmark");

        icon.classList.add("fa-circle-check");

    } else {

        element.classList.remove("valid");

        icon.classList.remove("fa-circle-check");

        icon.classList.add("fa-circle-xmark");

    }

}


passwordInput.addEventListener("input", function() {

    const password = this.value;


    updateRequirement(
        reqLength,
        password.length >= 8
    );


    updateRequirement(
        reqUpper,
        /[A-Z]/.test(password)
    );


    updateRequirement(
        reqLower,
        /[a-z]/.test(password)
    );


    updateRequirement(
        reqNumber,
        /[0-9]/.test(password)
    );

});



/* =====================================================
   FORM VALIDATION
===================================================== */

document.getElementById("registerForm").addEventListener(
    "submit",
    function(event) {

        const password =
            document.getElementById("password").value;

        const confirmPassword =
            document.getElementById("confirm_password").value;

        const email =
            document.getElementById("email").value.trim();


        /* EMAIL */

        if (!email) {

            event.preventDefault();

            alert("Please enter your email address.");

            return;
        }


        /* PASSWORD LENGTH */

        if (password.length < 8) {

            event.preventDefault();

            alert("Password must be at least 8 characters.");

            return;
        }


        /* UPPERCASE */

        if (!/[A-Z]/.test(password)) {

            event.preventDefault();

            alert(
                "Password must contain at least one uppercase letter."
            );

            return;
        }


        /* LOWERCASE */

        if (!/[a-z]/.test(password)) {

            event.preventDefault();

            alert(
                "Password must contain at least one lowercase letter."
            );

            return;
        }


        /* NUMBER */

        if (!/[0-9]/.test(password)) {

            event.preventDefault();

            alert(
                "Password must contain at least one number."
            );

            return;
        }


        /* CONFIRM PASSWORD */

        if (password !== confirmPassword) {

            event.preventDefault();

            alert("Passwords do not match.");

            return;
        }

    }
);

</script>


</body>

</html>