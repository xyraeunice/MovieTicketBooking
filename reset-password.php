<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/db.php";


/*
|--------------------------------------------------------------------------
| REQUIRE VERIFIED CODE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['reset_user_id']) ||
    !isset($_SESSION['reset_email']) ||
    !isset($_SESSION['reset_verified']) ||
    $_SESSION['reset_verified'] !== true
) {
    header('Location: forgot-password.php');
    exit;
}


$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| HANDLE PASSWORD RESET
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST['password'] ?? "";
    $confirm_password = $_POST['confirm_password'] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($password === "" || $confirm_password === "") {

        $error = "Please fill in both password fields.";

    } elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    } elseif (!preg_match('/[A-Z]/', $password)) {

        $error =
            "Password must contain at least one uppercase letter.";

    } elseif (!preg_match('/[a-z]/', $password)) {

        $error =
            "Password must contain at least one lowercase letter.";

    } elseif (!preg_match('/[0-9]/', $password)) {

        $error =
            "Password must contain at least one number.";

    } elseif ($password !== $confirm_password) {

        $error = "Passwords do not match.";

    } else {


        /*
        |--------------------------------------------------------------------------
        | HASH NEW PASSWORD
        |--------------------------------------------------------------------------
        */

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );


        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE
        |--------------------------------------------------------------------------
        */

        $sql = "
            UPDATE users
            SET password = ?
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {

            $error =
                "Unable to update your password. Please try again.";

        } else {

            $stmt->bind_param(
                "si",
                $hashed_password,
                $_SESSION['reset_user_id']
            );


            if ($stmt->execute()) {

                /*
                |--------------------------------------------------------------------------
                | CLEAR RESET SESSION
                |--------------------------------------------------------------------------
                */

                unset(
                    $_SESSION['reset_email'],
                    $_SESSION['reset_user_id'],
                    $_SESSION['reset_verified']
                );


                $success =
                    "Your password has been successfully changed.";

            } else {

                $error =
                    "Unable to update your password. Please try again.";
            }


            $stmt->close();
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

    <title>Reset Password - CineBook</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    >


    <!-- Google Font -->

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

            font-family: 'Poppins', sans-serif;

            background:
                radial-gradient(
                    circle at top right,
                    rgba(220, 53, 69, 0.14),
                    transparent 35%
                ),
                #0d0d0d;

            color: #ffffff;

            min-height: 100vh;
        }


        .reset-section {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;
        }


        .reset-card {

            width: 100%;

            max-width: 460px;

            background: #151515;

            border: 1px solid #292929;

            border-radius: 18px;

            padding: 40px;

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.5);
        }


        .reset-header {

            text-align: center;

            margin-bottom: 30px;
        }


        .icon-circle {

            width: 75px;

            height: 75px;

            margin: 0 auto 20px;

            border-radius: 50%;

            background: rgba(220, 53, 69, 0.1);

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .icon-circle i {

            font-size: 30px;

            color: #dc3545;
        }


        .reset-header h1 {

            font-size: 30px;

            font-weight: 700;

            margin-bottom: 8px;
        }


        .reset-header p {

            color: #888;

            font-size: 14px;

            margin: 0;
        }


        .form-label {

            color: #ddd;

            font-weight: 500;
        }


        .form-label i {

            color: #dc3545;

            margin-right: 5px;
        }


        .password-wrapper {

            position: relative;
        }


        .password-wrapper .form-control {

            padding-right: 48px;
        }


        .form-control {

            background: #101010;

            border: 1px solid #333;

            color: #ffffff;

            padding: 12px 14px;

            border-radius: 9px;
        }


        .form-control:focus {

            background: #101010;

            color: #ffffff;

            border-color: #dc3545;

            box-shadow:
                0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }


        .form-control::placeholder {

            color: #666;
        }


        .toggle-password {

            position: absolute;

            right: 14px;

            top: 50%;

            transform: translateY(-50%);

            border: none;

            background: transparent;

            color: #777;

            cursor: pointer;

            z-index: 5;
        }


        .toggle-password:hover {

            color: #ffffff;
        }


        .requirements {

            background: #101010;

            border: 1px solid #292929;

            border-radius: 10px;

            padding: 15px;

            margin-top: 15px;

            margin-bottom: 20px;
        }


        .requirements-title {

            color: #aaa;

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 10px;

            text-transform: uppercase;
        }


        .requirement {

            color: #777;

            font-size: 12px;

            margin-bottom: 5px;
        }


        .requirement i {

            width: 18px;
        }


        .requirement.valid {

            color: #49d18d;
        }


        .requirement.valid i {

            color: #49d18d;
        }


        .reset-btn {

            width: 100%;

            border: none;

            background: #dc3545;

            color: #ffffff;

            padding: 13px;

            border-radius: 9px;

            font-weight: 600;

            margin-top: 10px;
        }


        .reset-btn:hover {

            background: #bb2d3b;
        }


        .back-login {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #888;

            text-decoration: none;

            font-size: 14px;
        }


        .back-login:hover {

            color: #ffffff;
        }


        @media (max-width: 500px) {

            .reset-card {

                padding: 30px 22px;
            }

        }

    </style>

</head>


<body>


<section class="reset-section">


    <div class="reset-card">


        <div class="reset-header">


            <?php if (empty($success)): ?>

                <div class="icon-circle">

                    <i class="fa-solid fa-lock-open"></i>

                </div>

            <?php else: ?>

                <div
                    class="icon-circle"
                    style="background:rgba(25,135,84,0.12);"
                >

                    <i
                        class="fa-solid fa-circle-check"
                        style="color:#198754;"
                    ></i>

                </div>

            <?php endif; ?>


            <h1>

                <?php if (empty($success)): ?>

                    Create New Password

                <?php else: ?>

                    Password Updated!

                <?php endif; ?>

            </h1>


            <p>

                <?php if (empty($success)): ?>

                    Create a new secure password for your CineBook account.

                <?php else: ?>

                    Your CineBook password has been changed successfully.

                <?php endif; ?>

            </p>

        </div>


        <?php if (!empty($error)): ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($success)): ?>


            <div class="text-center">

                <p class="text-secondary">

                    You can now use your new password
                    to log in to your CineBook account.

                </p>


                <a
                    href="login.php"
                    class="btn btn-danger w-100 mt-3"
                >

                    <i class="fa-solid fa-right-to-bracket me-2"></i>

                    Go to Login

                </a>

            </div>


        <?php else: ?>


            <form
                method="POST"
                action="reset-password.php"
                id="resetForm"
            >


                <!-- NEW PASSWORD -->

                <div class="mb-3">

                    <label class="form-label">

                        <i class="fa-solid fa-lock"></i>

                        New Password

                    </label>


                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            placeholder="Enter your new password"
                            autocomplete="new-password"
                            required
                        >


                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword(
                                'password',
                                'passwordIcon'
                            )"
                        >

                            <i
                                class="fa-solid fa-eye"
                                id="passwordIcon"
                            ></i>

                        </button>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->

                <div class="mb-3">

                    <label class="form-label">

                        <i class="fa-solid fa-lock"></i>

                        Confirm New Password

                    </label>


                    <div class="password-wrapper">

                        <input
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            class="form-control"
                            placeholder="Confirm your new password"
                            autocomplete="new-password"
                            required
                        >


                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword(
                                'confirm_password',
                                'confirmPasswordIcon'
                            )"
                        >

                            <i
                                class="fa-solid fa-eye"
                                id="confirmPasswordIcon"
                            ></i>

                        </button>

                    </div>

                </div>


                <!-- PASSWORD REQUIREMENTS -->

                <div class="requirements">

                    <div class="requirements-title">

                        Password Requirements

                    </div>


                    <div
                        class="requirement"
                        id="lengthRequirement"
                    >

                        <i class="fa-solid fa-circle"></i>

                        At least 8 characters

                    </div>


                    <div
                        class="requirement"
                        id="uppercaseRequirement"
                    >

                        <i class="fa-solid fa-circle"></i>

                        At least one uppercase letter

                    </div>


                    <div
                        class="requirement"
                        id="lowercaseRequirement"
                    >

                        <i class="fa-solid fa-circle"></i>

                        At least one lowercase letter

                    </div>


                    <div
                        class="requirement"
                        id="numberRequirement"
                    >

                        <i class="fa-solid fa-circle"></i>

                        At least one number

                    </div>


                    <div
                        class="requirement"
                        id="matchRequirement"
                    >

                        <i class="fa-solid fa-circle"></i>

                        Passwords match

                    </div>

                </div>


                <button
                    type="submit"
                    class="reset-btn"
                >

                    <i class="fa-solid fa-key me-2"></i>

                    Reset Password

                </button>


            </form>


        <?php endif; ?>


    </div>


</section>


<script>

    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE PASSWORD
    |--------------------------------------------------------------------------
    */

    function togglePassword(inputId, iconId) {

        const input =
            document.getElementById(inputId);

        const icon =
            document.getElementById(iconId);


        if (input.type === "password") {

            input.type = "text";

            icon.classList.remove("fa-eye");

            icon.classList.add("fa-eye-slash");

        } else {

            input.type = "password";

            icon.classList.remove("fa-eye-slash");

            icon.classList.add("fa-eye");

        }
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD VALIDATION
    |--------------------------------------------------------------------------
    */

    const password =
        document.getElementById("password");

    const confirmPassword =
        document.getElementById("confirm_password");


    function updateRequirement(
        elementId,
        valid
    ) {

        const element =
            document.getElementById(elementId);

        const icon =
            element.querySelector("i");


        if (valid) {

            element.classList.add("valid");

            icon.classList.remove("fa-circle");

            icon.classList.add("fa-circle-check");

        } else {

            element.classList.remove("valid");

            icon.classList.remove("fa-circle-check");

            icon.classList.add("fa-circle");

        }
    }


    function validatePassword() {

        const value =
            password.value;

        updateRequirement(
            "lengthRequirement",
            value.length >= 8
        );

        updateRequirement(
            "uppercaseRequirement",
            /[A-Z]/.test(value)
        );

        updateRequirement(
            "lowercaseRequirement",
            /[a-z]/.test(value)
        );

        updateRequirement(
            "numberRequirement",
            /[0-9]/.test(value)
        );

        updateRequirement(
            "matchRequirement",
            value !== "" &&
            value === confirmPassword.value
        );
    }


    password.addEventListener(
        "input",
        validatePassword
    );


    confirmPassword.addEventListener(
        "input",
        validatePassword
    );

</script>


</body>

</html>