<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CHECK RESET SESSION
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['reset_email']) ||
    !isset($_SESSION['reset_code_hash']) ||
    !isset($_SESSION['reset_code_expires'])
) {
    header('Location: forgot-password.php');
    exit;
}


$error = "";


/*
|--------------------------------------------------------------------------
| CHECK EXPIRATION
|--------------------------------------------------------------------------
*/

if (time() > $_SESSION['reset_code_expires']) {

    unset(
        $_SESSION['reset_email'],
        $_SESSION['reset_user_id'],
        $_SESSION['reset_code_hash'],
        $_SESSION['reset_code_expires'],
        $_SESSION['reset_verified']
    );

    header('Location: forgot-password.php?expired=1');
    exit;
}


/*
|--------------------------------------------------------------------------
| HANDLE VERIFICATION
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = trim($_POST['code'] ?? "");


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CODE FORMAT
    |--------------------------------------------------------------------------
    */

    if ($code === "") {

        $error = "Please enter the verification code.";

    } elseif (!preg_match('/^[0-9]{6}$/', $code)) {

        $error = "Verification code must be exactly 6 digits.";

    } elseif (
        !password_verify(
            $code,
            $_SESSION['reset_code_hash']
        )
    ) {

        $error = "Invalid verification code.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | CODE VERIFIED
        |--------------------------------------------------------------------------
        */

        $_SESSION['reset_verified'] = true;

        /*
        |--------------------------------------------------------------------------
        | REMOVE CODE SO IT CANNOT BE REUSED
        |--------------------------------------------------------------------------
        */

        unset(
            $_SESSION['reset_code_hash'],
            $_SESSION['reset_code_expires']
        );


        /*
        |--------------------------------------------------------------------------
        | GO TO PASSWORD RESET
        |--------------------------------------------------------------------------
        */

        header('Location: reset-password.php');
        exit;
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

    <title>Verify Code - CineBook</title>


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


        .verify-section {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 30px 15px;
        }


        .verify-card {

            width: 100%;

            max-width: 460px;

            background: #151515;

            border: 1px solid #292929;

            border-radius: 18px;

            padding: 40px;

            box-shadow:
                0 25px 70px rgba(0, 0, 0, 0.5);

            text-align: center;
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


        h1 {

            font-size: 30px;

            font-weight: 700;

            margin-bottom: 10px;
        }


        .description {

            color: #888;

            font-size: 14px;

            line-height: 1.7;

            margin-bottom: 25px;
        }


        .email-display {

            color: #ffffff;

            font-weight: 600;

            word-break: break-word;

            margin-bottom: 25px;
        }


        .code-input {

            width: 100%;

            background: #101010;

            border: 1px solid #333;

            color: #ffffff;

            border-radius: 10px;

            padding: 15px;

            text-align: center;

            font-size: 27px;

            font-weight: 700;

            letter-spacing: 10px;
        }


        .code-input:focus {

            background: #101010;

            color: #ffffff;

            border-color: #dc3545;

            box-shadow:
                0 0 0 0.2rem rgba(220, 53, 69, 0.15);

            outline: none;
        }


        .code-input::placeholder {

            color: #555;

            letter-spacing: 6px;
        }


        .verify-btn {

            width: 100%;

            border: none;

            background: #dc3545;

            color: #ffffff;

            padding: 13px;

            border-radius: 9px;

            font-weight: 600;

            margin-top: 20px;
        }


        .verify-btn:hover {

            background: #bb2d3b;

        }


        .back-link {

            display: block;

            margin-top: 20px;

            color: #888;

            text-decoration: none;

            font-size: 14px;
        }


        .back-link:hover {

            color: #ffffff;
        }


        @media (max-width: 500px) {

            .verify-card {

                padding: 30px 22px;
            }


            .code-input {

                font-size: 22px;

                letter-spacing: 7px;
            }

        }

    </style>

</head>


<body>


<section class="verify-section">


    <div class="verify-card">


        <div class="icon-circle">

            <i class="fa-solid fa-envelope-circle-check"></i>

        </div>


        <h1>
            Verify Your Email
        </h1>


        <p class="description">

            We sent a 6-digit verification code to:

        </p>


        <div class="email-display">

            <?php echo htmlspecialchars(
                $_SESSION['reset_email']
            ); ?>

        </div>


        <?php if (!empty($error)): ?>

            <div class="alert alert-danger text-start">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="verify-code.php"
        >


            <input
                type="text"
                name="code"
                class="code-input"
                placeholder="000000"
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                autocomplete="one-time-code"
                required
            >


            <button
                type="submit"
                class="verify-btn"
            >

                <i class="fa-solid fa-shield-check me-2"></i>

                Verify Code

            </button>


        </form>


        <a
            href="forgot-password.php"
            class="back-link"
        >

            <i class="fa-solid fa-arrow-left me-1"></i>

            Request a New Code

        </a>


    </div>


</section>


</body>

</html>