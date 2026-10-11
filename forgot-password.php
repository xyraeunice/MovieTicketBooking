<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'vendor/autoload.php';
require_once 'config/mail.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');

    // =========================
    // VALIDATE EMAIL
    // =========================
    if ($email === '') {

        $error = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } else {

        // =========================
        // FIND USER
        // =========================
        $stmt = $conn->prepare("
            SELECT id, email, given_name, surname
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            $error = 'Database error. Please try again.';

        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            $stmt->close();

            if (!$user) {

                $error = 'No account was found with that email address.';

            } else {

                // =========================
                // GENERATE 6-DIGIT CODE
                // =========================
                $verification_code = str_pad(
                    random_int(0, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                // =========================
                // STORE RESET INFORMATION
                // =========================
                $_SESSION['reset_email'] = $user['email'];

                $_SESSION['reset_user_id'] = $user['id'];

                $_SESSION['reset_code_hash'] = password_hash(
                    $verification_code,
                    PASSWORD_DEFAULT
                );

                // Code expires after 10 minutes
                $_SESSION['reset_code_expires'] = time() + 600;

                // Code not verified yet
                $_SESSION['reset_verified'] = false;


                // =========================
                // SEND VERIFICATION EMAIL
                // =========================
                $mailResult = sendVerificationCode(
                    $user['email'],
                    $user['given_name'] . ' ' . $user['surname'],
                    $verification_code
                );


                // =========================
                // EMAIL SUCCESS
                // =========================
                if ($mailResult['success']) {

                    header('Location: verify-code.php');
                    exit;

                } else {

                    // Clear reset information if email failed
                    unset(
                        $_SESSION['reset_email'],
                        $_SESSION['reset_user_id'],
                        $_SESSION['reset_code_hash'],
                        $_SESSION['reset_code_expires'],
                        $_SESSION['reset_verified']
                    );

                    // Show actual PHPMailer error temporarily
                    $error = 'EMAIL ERROR: ' . $mailResult['error'];
                }
            }
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

    <title>Forgot Password - CineBook</title>

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
            min-height: 100vh;

            background:
                radial-gradient(
                    circle at top,
                    #2a0b0b 0%,
                    #111111 45%,
                    #080808 100%
                );

            color: #ffffff;

            font-family: 'Poppins', sans-serif;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px 15px;
        }

        .forgot-wrapper {
            width: 100%;
            max-width: 460px;
        }

        .brand {
            text-align: center;
            margin-bottom: 25px;
        }

        .brand-icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #dc3545;
            border-radius: 50%;

            font-size: 28px;

            box-shadow:
                0 10px 30px rgba(220, 53, 69, 0.35);
        }

        .brand h1 {
            margin: 0;

            font-size: 30px;
            font-weight: 700;
        }

        .brand p {
            margin-top: 8px;

            color: #999999;
            font-size: 14px;
        }

        .forgot-card {
            background: rgba(25, 25, 25, 0.96);

            border: 1px solid #333333;

            border-radius: 18px;

            padding: 35px;

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .forgot-card h2 {
            font-size: 23px;
            font-weight: 600;

            margin-bottom: 10px;
        }

        .description {
            color: #999999;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 25px;
        }

        .form-label {
            color: #dddddd;

            font-size: 14px;
            font-weight: 500;
        }

        .input-group-text {
            background: #111111;

            border: 1px solid #444444;
            border-right: 0;

            color: #888888;
        }

        .form-control {
            background: #111111;

            border: 1px solid #444444;

            color: #ffffff;

            height: 50px;
        }

        .form-control:focus {
            background: #111111;

            color: #ffffff;

            border-color: #dc3545;

            box-shadow:
                0 0 0 0.2rem rgba(220, 53, 69, 0.15);
        }

        .form-control::placeholder {
            color: #666666;
        }

        .btn-send {
            width: 100%;
            height: 50px;

            border: none;
            border-radius: 8px;

            background: #dc3545;
            color: #ffffff;

            font-weight: 600;

            transition: 0.2s;
        }

        .btn-send:hover {
            background: #bb2d3b;

            transform: translateY(-1px);
        }

        .back-login {
            text-align: center;

            margin-top: 22px;
        }

        .back-login a {
            color: #dc3545;

            text-decoration: none;

            font-size: 14px;
        }

        .back-login a:hover {
            text-decoration: underline;
        }

        .info-box {
            margin-top: 20px;

            padding: 15px;

            border-radius: 8px;

            background: #111111;

            border: 1px solid #2d2d2d;

            color: #888888;

            font-size: 12px;

            line-height: 1.6;
        }

        @media (max-width: 500px) {

            .forgot-card {
                padding: 25px 20px;
            }

            .brand h1 {
                font-size: 26px;
            }

        }

    </style>

</head>

<body>

<div class="forgot-wrapper">

    <!-- BRAND -->
    <div class="brand">

        <div class="brand-icon">
            <i class="fa-solid fa-film"></i>
        </div>

        <h1>CineBook</h1>

        <p>Movie Ticket Booking System</p>

    </div>


    <!-- CARD -->
    <div class="forgot-card">

        <h2>
            <i class="fa-solid fa-key text-danger me-2"></i>
            Forgot Password?
        </h2>

        <p class="description">
            Enter the email address associated with your CineBook
            account. We'll send you a 6-digit verification code
            to reset your password.
        </p>


        <!-- ERROR -->
        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger"
                role="alert"
            >

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- FORM -->
        <form method="POST">

            <div class="mb-4">

                <label
                    for="email"
                    class="form-label"
                >
                    Email Address
                </label>

                <div class="input-group">

                    <span class="input-group-text">

                        <i class="fa-solid fa-envelope"></i>

                    </span>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        placeholder="Enter your registered email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required
                        autocomplete="email"
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn-send"
            >

                <i class="fa-solid fa-paper-plane me-2"></i>

                Send Verification Code

            </button>

        </form>


        <!-- INFO -->
        <div class="info-box">

            <i class="fa-solid fa-shield-halved text-danger me-1"></i>

            Your verification code is valid for
            <strong>10 minutes</strong> only.

        </div>


        <!-- BACK TO LOGIN -->
        <div class="back-login">

            <a href="login.php">

                <i class="fa-solid fa-arrow-left me-1"></i>

                Back to Login

            </a>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>