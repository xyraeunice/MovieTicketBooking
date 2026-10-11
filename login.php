<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

$error = '';
$email = '';

/*
|--------------------------------------------------------------------------
| Redirect destination
|--------------------------------------------------------------------------
*/
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';

/*
|--------------------------------------------------------------------------
| Login process
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    /*
    |--------------------------------------------------------------------------
    | EMAIL VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $errors[] = "Email is required.";

    } elseif (strlen($email) > 25) {

        $errors[] = "Email must not exceed 25 characters.";

    } elseif (
        !str_contains($email, '@') ||
        !str_contains($email, '.')
    ) {

        $errors[] = "Email must contain @ and .";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";
    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($password === '') {

        $errors[] = "Password is required.";

    } elseif (strlen($password) > 50) {

        $errors[] = "Password must not exceed 50 characters.";

    } elseif (!preg_match('/^[A-Za-z0-9]+$/', $password)) {

        $errors[] = "Password must contain letters and numbers only. Symbols are not allowed.";
    }


    /*
    |--------------------------------------------------------------------------
    | IF VALIDATION PASSES
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $conn->prepare("
            SELECT id, email, given_name, surname, password
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if ($stmt) {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            /*
            |--------------------------------------------------------------------------
            | CHECK ACCOUNT
            |--------------------------------------------------------------------------
            */

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();

                /*
                |--------------------------------------------------------------------------
                | CHECK PASSWORD
                |--------------------------------------------------------------------------
                */

                if (password_verify($password, $user['password'])) {

                    /*
                    |--------------------------------------------------------------------------
                    | REGENERATE SESSION ID
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    /*
                    |--------------------------------------------------------------------------
                    | STORE USER SESSION
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['given_name'] = $user['given_name'];
                    $_SESSION['surname'] = $user['surname'];

                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT
                    |--------------------------------------------------------------------------
                    */

                    if (!empty($redirect)) {
                        header('Location: ' . $redirect);
                    } else {
                        header('Location: index.php');
                    }

                    exit;

                } else {

                    $error = "Incorrect email or password.";
                }

            } else {

                $error = "Incorrect email or password.";
            }

            $stmt->close();

        } else {

            $error = "Something went wrong. Please try again.";
        }

    } else {

        /*
        |--------------------------------------------------------------------------
        | DISPLAY VALIDATION ERRORS
        |--------------------------------------------------------------------------
        */

        $error = implode('<br>', $errors);
    }
}

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>


<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card bg-dark text-white border-0 shadow-lg">

                <div class="card-body p-4 p-md-5">

                    <!-- TITLE -->
                    <div class="text-center mb-4">

                        <div class="mb-3">
                            <i
                                class="fa-solid fa-right-to-bracket text-danger"
                                style="font-size: 45px;"
                            ></i>
                        </div>

                        <h2 class="fw-bold">
                            Welcome Back
                        </h2>

                        <p class="text-secondary">
                            Login to your CineBook account
                        </p>

                    </div>


                    <!-- ERROR MESSAGE -->
                    <?php if (!empty($error)): ?>

                        <div class="alert alert-danger">

                            <i class="fa-solid fa-circle-exclamation"></i>

                            <?php echo $error; ?>

                        </div>

                    <?php endif; ?>


                    <!-- LOGIN FORM -->
                    <form method="POST" action="login.php">

                        <input
                            type="hidden"
                            name="redirect"
                            value="<?php echo htmlspecialchars($redirect); ?>"
                        >


                        <!-- EMAIL -->
                        <div class="mb-3">

                            <label
                                for="email"
                                class="form-label"
                            >
                                Email Address
                            </label>

                            <div class="input-group">

                                <span class="input-group-text bg-secondary text-white border-0">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>

                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="form-control"
                                    placeholder="Enter your email"
                                    maxlength="25"
                                    value="<?php echo htmlspecialchars($email); ?>"
                                    required
                                >

                            </div>

                            <small class="text-secondary">
                                Maximum 25 characters. Must contain @ and .
                            </small>

                        </div>


                        <!-- PASSWORD -->
                        <div class="mb-3">

                            <label
                                for="password"
                                class="form-label"
                            >
                                Password
                            </label>

                            <div class="input-group">

                                <span class="input-group-text bg-secondary text-white border-0">
                                    <i class="fa-solid fa-lock"></i>
                                </span>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    maxlength="50"
                                    pattern="[A-Za-z0-9]+"
                                    title="Password must contain letters and numbers only. Symbols are not allowed."
                                    required
                                >

                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    id="togglePassword"
                                >
                                    <i
                                        class="fa-solid fa-eye"
                                        id="togglePasswordIcon"
                                    ></i>
                                </button>

                            </div>

                            <small class="text-secondary">
                                Maximum 50 characters. Letters and numbers only.
                            </small>

                        </div>


                        <!-- FORGOT PASSWORD -->
                        <div class="text-end mb-4">

                            <a
                                href="forgot-password.php"
                                class="text-danger text-decoration-none"
                            >
                                Forgot Password?
                            </a>

                        </div>


                        <!-- LOGIN BUTTON -->
                        <button
                            type="submit"
                            class="btn btn-danger w-100 py-2 fw-bold"
                        >
                            <i class="fa-solid fa-right-to-bracket"></i>
                            Login
                        </button>

                    </form>


                    <!-- REGISTER -->
                    <div class="text-center mt-4">

                        <p class="text-secondary mb-0">
                            Don't have an account?
                        </p>

                        <a
                            href="register.php"
                            class="text-danger fw-bold text-decoration-none"
                        >
                            Create an Account
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');


    /*
    |--------------------------------------------------------------------------
    | SHOW / HIDE PASSWORD
    |--------------------------------------------------------------------------
    */

    togglePassword.addEventListener('click', function () {

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            togglePasswordIcon.classList.remove('fa-eye');
            togglePasswordIcon.classList.add('fa-eye-slash');

        } else {

            passwordInput.type = 'password';

            togglePasswordIcon.classList.remove('fa-eye-slash');
            togglePasswordIcon.classList.add('fa-eye');
        }

    });


    /*
    |--------------------------------------------------------------------------
    | PREVENT SYMBOLS IN PASSWORD
    |--------------------------------------------------------------------------
    */

    passwordInput.addEventListener('input', function () {

        this.value = this.value.replace(/[^A-Za-z0-9]/g, '');

    });


    /*
    |--------------------------------------------------------------------------
    | LIMIT EMAIL TO 25 CHARACTERS
    |--------------------------------------------------------------------------
    */

    const emailInput = document.getElementById('email');

    emailInput.addEventListener('input', function () {

        if (this.value.length > 25) {
            this.value = this.value.substring(0, 25);
        }

    });


    /*
    |--------------------------------------------------------------------------
    | LIMIT PASSWORD TO 50 CHARACTERS
    |--------------------------------------------------------------------------
    */

    passwordInput.addEventListener('input', function () {

        if (this.value.length > 50) {
            this.value = this.value.substring(0, 50);
        }

    });

});

</script>


<?php include 'includes/footer.php'; ?>