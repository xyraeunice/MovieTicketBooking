<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/db.php";


/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


/* =========================================================
   ONLY POST REQUEST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: movies.php");
    exit;
}


/* =========================================================
   GET POST DATA
========================================================= */

$movie_id = isset($_POST['movie_id'])
    ? (int) $_POST['movie_id']
    : 0;

$cinema = isset($_POST['cinema'])
    ? trim($_POST['cinema'])
    : '';

$booking_date = isset($_POST['date'])
    ? trim($_POST['date'])
    : '';

$showtime = isset($_POST['showtime'])
    ? trim($_POST['showtime'])
    : '';

$tickets = isset($_POST['tickets'])
    ? (int) $_POST['tickets']
    : 0;

$seat_numbers_raw = isset($_POST['seat_numbers'])
    ? trim($_POST['seat_numbers'])
    : '';


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $movie_id <= 0 ||
    empty($cinema) ||
    empty($booking_date) ||
    empty($showtime) ||
    $tickets < 1 ||
    $tickets > 6 ||
    empty($seat_numbers_raw)
) {

    die("Invalid booking information.");
}


/* =========================================================
   ALLOWED CINEMAS
========================================================= */

$allowed_cinemas = [
    "SM Manila",
    "Robinsons Manila",
    "Ayala Malls Manila Bay"
];

if (!in_array($cinema, $allowed_cinemas, true)) {

    die("Invalid cinema selected.");
}


/* =========================================================
   ALLOWED SHOWTIMES
========================================================= */

$allowed_showtimes = [
    "10:00 AM",
    "1:00 PM",
    "4:00 PM",
    "7:00 PM",
    "10:00 PM"
];

if (!in_array($showtime, $allowed_showtimes, true)) {

    die("Invalid showtime selected.");
}


/* =========================================================
   DATE VALIDATION
========================================================= */

$date_object = DateTime::createFromFormat(
    'Y-m-d',
    $booking_date
);

if (
    !$date_object ||
    $date_object->format('Y-m-d') !== $booking_date
) {

    die("Invalid booking date.");
}


/* =========================================================
   PREVENT PAST DATES
========================================================= */

if ($booking_date < date('Y-m-d')) {

    die("You cannot book a date that has already passed.");
}


/* =========================================================
   GET MOVIE
========================================================= */

$sql_movie = "
    SELECT
        id,
        title,
        genre,
        duration,
        rating,
        description,
        price,
        poster,
        status
    FROM movies
    WHERE id = ?
    LIMIT 1
";

$stmt_movie = $conn->prepare($sql_movie);

if (!$stmt_movie) {
    die("Database error: " . $conn->error);
}

$stmt_movie->bind_param(
    "i",
    $movie_id
);

$stmt_movie->execute();

$result_movie = $stmt_movie->get_result();

$movie = $result_movie->fetch_assoc();

$stmt_movie->close();


/* =========================================================
   MOVIE CHECK
========================================================= */

if (!$movie) {

    header("Location: movies.php");
    exit;
}


/* =========================================================
   ONLY NOW SHOWING
========================================================= */

if ($movie['status'] !== 'now_showing') {

    header(
        "Location: movie-details.php?id=" . $movie_id
    );

    exit;
}


/* =========================================================
   PROCESS SELECTED SEATS
========================================================= */

$seat_numbers = explode(
    ',',
    $seat_numbers_raw
);


/*
   Remove spaces and empty values
*/

$seat_numbers = array_map(
    'trim',
    $seat_numbers
);

$seat_numbers = array_filter(
    $seat_numbers,
    function ($seat) {
        return $seat !== '';
    }
);


/*
   Re-index array
*/

$seat_numbers = array_values(
    $seat_numbers
);


/*
   Remove duplicate seats
*/

$seat_numbers = array_values(
    array_unique($seat_numbers)
);


/* =========================================================
   CHECK NUMBER OF SEATS
========================================================= */

if (count($seat_numbers) !== $tickets) {

    die(
        "The number of selected seats does not match " .
        "the number of tickets."
    );
}


/* =========================================================
   VALID SEAT FORMAT
========================================================= */

foreach ($seat_numbers as $seat) {

    /*
       Valid seats:

       A1 - A15
       B1 - B15
       ...
       J1 - J15
    */

    if (
        !preg_match(
            '/^[A-J](?:[1-9]|1[0-5])$/',
            $seat
        )
    ) {

        die(
            "Invalid seat selected: " .
            htmlspecialchars($seat)
        );
    }
}


/* =========================================================
   SORT SEATS
========================================================= */

usort(
    $seat_numbers,
    function ($a, $b) {

        $rowA = $a[0];
        $rowB = $b[0];

        if ($rowA !== $rowB) {

            return strcmp(
                $rowA,
                $rowB
            );
        }

        return
            ((int) substr($a, 1))
            -
            ((int) substr($b, 1));
    }
);


/* =========================================================
   TICKET PRICE
========================================================= */

$ticket_price = (float) $movie['price'];


/*
   IMPORTANT:

   Total is calculated from the
   database price.

   We DO NOT trust the browser.
*/

$total_amount =
    $ticket_price * $tickets;


/* =========================================================
   FORMATTED DATE
========================================================= */

$formatted_date = date(
    "F d, Y",
    strtotime($booking_date)
);


/* =========================================================
   SEAT LIST FOR DISPLAY
========================================================= */

$seat_display = implode(
    ', ',
    $seat_numbers
);


/* =========================================================
   PAYMENT PROCESSING
========================================================= */

$payment_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {

    $payment_method = isset($_POST['payment_method'])
        ? trim($_POST['payment_method'])
        : '';


    /* =====================================================
       PAYMENT METHOD VALIDATION
    ===================================================== */

    $allowed_payment_methods = [
        "GCash",
        "Maya",
        "Credit/Debit Card"
    ];

    if (
        !in_array(
            $payment_method,
            $allowed_payment_methods,
            true
        )
    ) {

        $payment_error =
            "Please select a valid payment method.";
    }


    /* =====================================================
       CHECK SEATS AGAIN
    ===================================================== */

    if (empty($payment_error)) {

        $occupied_seats = [];

        $sql_occupied = "
            SELECT
                bs.seat_number

            FROM booking_seats bs

            INNER JOIN bookings b
                ON b.id = bs.booking_id

            WHERE
                b.movie_id = ?
                AND b.cinema = ?
                AND b.booking_date = ?
                AND b.showtime = ?

                AND b.payment_status = 'paid'
                AND b.booking_status = 'confirmed'
        ";

        $stmt_occupied =
            $conn->prepare(
                $sql_occupied
            );

        if (!$stmt_occupied) {

            $payment_error =
                "Database error while checking seats.";
        }

        else {

            $stmt_occupied->bind_param(
                "isss",
                $movie_id,
                $cinema,
                $booking_date,
                $showtime
            );

            $stmt_occupied->execute();

            $result_occupied =
                $stmt_occupied->get_result();

            while (
                $row =
                $result_occupied->fetch_assoc()
            ) {

                $occupied_seats[] =
                    $row['seat_number'];
            }

            $stmt_occupied->close();


            /* =============================================
               CHECK SELECTED SEATS
            ============================================= */

            $already_taken = array_intersect(
                $seat_numbers,
                $occupied_seats
            );

            if (!empty($already_taken)) {

                $payment_error =
                    "Sorry. The following seat(s) are already " .
                    "occupied: " .
                    implode(
                        ', ',
                        $already_taken
                    ) .
                    ". Please go back and select another seat.";
            }
        }
    }


    /* =====================================================
       CREATE BOOKING
    ===================================================== */

    if (empty($payment_error)) {

        /*
           Start database transaction.

           This makes sure the booking and
           seats are saved together.
        */

        $conn->begin_transaction();

        try {

            /* =============================================
               INSERT BOOKING
            ============================================= */

            $sql_booking = "
                INSERT INTO bookings (
                    user_id,
                    movie_id,
                    cinema,
                    booking_date,
                    showtime,
                    tickets,
                    total_amount,
                    payment_status,
                    booking_status
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'paid',
                    'confirmed'
                )
            ";

            $stmt_booking =
                $conn->prepare(
                    $sql_booking
                );

            if (!$stmt_booking) {

                throw new Exception(
                    "Unable to prepare booking."
                );
            }


            $user_id =
                (int) $_SESSION['user_id'];


            $stmt_booking->bind_param(
                "iisssid",
                $user_id,
                $movie_id,
                $cinema,
                $booking_date,
                $showtime,
                $tickets,
                $total_amount
            );


            if (!$stmt_booking->execute()) {

                throw new Exception(
                    "Unable to create booking."
                );
            }


            /* =============================================
               GET BOOKING ID
            ============================================= */

            $booking_id =
                $conn->insert_id;


            $stmt_booking->close();


            /* =============================================
               INSERT SEATS
            ============================================= */

            $sql_seat = "
                INSERT INTO booking_seats (
                    booking_id,
                    seat_number
                )
                VALUES (?, ?)
            ";

            $stmt_seat =
                $conn->prepare(
                    $sql_seat
                );

            if (!$stmt_seat) {

                throw new Exception(
                    "Unable to prepare seat booking."
                );
            }


            foreach (
                $seat_numbers
                as $seat_number
            ) {

                $stmt_seat->bind_param(
                    "is",
                    $booking_id,
                    $seat_number
                );


                if (!$stmt_seat->execute()) {

                    throw new Exception(
                        "Unable to save seat " .
                        $seat_number
                    );
                }
            }


            $stmt_seat->close();


            /* =============================================
               COMMIT
            ============================================= */

            $conn->commit();


            /* =============================================
               SAVE BOOKING ID TO SESSION
            ============================================= */

            $_SESSION['booking_id'] =
                $booking_id;


            /* =============================================
               REDIRECT TO CONFIRMATION
            ============================================= */

            header(
                "Location: confirmation.php?id=" .
                $booking_id
            );

            exit;

        }

        catch (Exception $e) {

            /*
               Undo database changes
               if anything fails.
            */

            $conn->rollback();


            $payment_error =
                "Payment could not be completed. " .
                "Please try again.";
        }
    }
}

?>


<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>


<style>

/* =========================================================
   PAGE
========================================================= */

.payment-page {

    min-height: 100vh;

    background:
        radial-gradient(
            circle at top,
            rgba(220, 38, 38, 0.12),
            transparent 45%
        ),
        #080808;

    color: #ffffff;

    padding-bottom: 80px;
}


/* =========================================================
   HEADER
========================================================= */

.payment-header {

    padding: 45px 0 30px;

    border-bottom:
        1px solid rgba(255,255,255,0.08);
}

.payment-header h1 {

    font-size: 34px;

    font-weight: 800;

    margin-bottom: 8px;
}

.payment-header p {

    color: #888;

    margin: 0;
}


/* =========================================================
   BOOKING STEPS
========================================================= */

.booking-steps {

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 35px auto 45px;

    max-width: 750px;
}

.booking-step {

    display: flex;

    align-items: center;

    gap: 10px;

    color: #666;

    font-size: 14px;

    font-weight: 600;

    white-space: nowrap;
}

.booking-step-number {

    width: 38px;
    height: 38px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #252525;

    color: #888;

    font-weight: 700;
}

.booking-step.active {

    color: #ffffff;
}

.booking-step.active
.booking-step-number {

    background: #dc3545;

    color: #ffffff;

    box-shadow:
        0 0 0 5px
        rgba(220,53,69,0.12);
}

.booking-step.completed
.booking-step-number {

    background: #198754;

    color: #ffffff;
}

.booking-step-line {

    height: 2px;

    width: 100px;

    background: #292929;

    margin: 0 15px;
}

.booking-step-line.active {

    background: #dc3545;
}


/* =========================================================
   MAIN LAYOUT
========================================================= */

.payment-wrapper {

    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        380px;

    gap: 25px;

    max-width: 1150px;

    margin: auto;
}


/* =========================================================
   PAYMENT PANEL
========================================================= */

.payment-panel {

    background: #111111;

    border:
        1px solid #242424;

    border-radius: 18px;

    padding: 30px;

    box-shadow:
        0 20px 50px
        rgba(0,0,0,0.35);
}

.payment-panel h2 {

    font-size: 24px;

    font-weight: 800;

    margin-bottom: 8px;
}

.payment-panel-subtitle {

    color: #777;

    margin-bottom: 30px;

    font-size: 14px;
}


/* =========================================================
   ERROR
========================================================= */

.payment-error {

    background:
        rgba(220,53,69,0.12);

    border:
        1px solid
        rgba(220,53,69,0.35);

    color: #ff6875;

    border-radius: 10px;

    padding: 14px 16px;

    margin-bottom: 25px;

    font-size: 13px;
}


/* =========================================================
   PAYMENT METHODS
========================================================= */

.payment-methods {

    display: grid;

    gap: 12px;

    margin-bottom: 30px;
}

.payment-method {

    position: relative;
}

.payment-method input {

    position: absolute;

    opacity: 0;

    pointer-events: none;
}

.payment-method label {

    display: flex;

    align-items: center;

    gap: 15px;

    padding: 17px;

    border:
        1px solid #2a2a2a;

    background: #181818;

    border-radius: 12px;

    cursor: pointer;

    transition: 0.2s ease;
}

.payment-method label:hover {

    border-color: #555;

    transform:
        translateY(-1px);
}

.payment-method input:checked
+ label {

    border-color: #dc3545;

    background:
        rgba(220,53,69,0.10);

    box-shadow:
        0 0 0 1px
        rgba(220,53,69,0.25);
}

.payment-method-icon {

    width: 45px;
    height: 45px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #242424;

    color: #ffffff;

    font-size: 20px;
}

.payment-method input:checked
+ label
.payment-method-icon {

    background: #dc3545;
}

.payment-method-info {

    flex: 1;
}

.payment-method-title {

    display: block;

    font-weight: 700;

    margin-bottom: 3px;
}

.payment-method-description {

    display: block;

    color: #777;

    font-size: 12px;
}

.payment-method-check {

    color: #dc3545;

    opacity: 0;

    font-size: 18px;
}

.payment-method input:checked
+ label
.payment-method-check {

    opacity: 1;
}


/* =========================================================
   DEMO PAYMENT NOTE
========================================================= */

.demo-payment-note {

    padding: 15px;

    border-radius: 10px;

    background: #181818;

    border:
        1px solid #282828;

    color: #777;

    font-size: 12px;

    margin-bottom: 25px;
}

.demo-payment-note i {

    color: #dc3545;

    margin-right: 7px;
}


/* =========================================================
   PAY BUTTON
========================================================= */

.pay-button {

    width: 100%;

    border: none;

    border-radius: 11px;

    padding: 15px;

    background: #dc3545;

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

    transition: 0.2s ease;
}

.pay-button:hover {

    background: #bb2d3b;

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(220,53,69,0.25);
}


/* =========================================================
   SUMMARY
========================================================= */

.payment-summary {

    position: sticky;

    top: 25px;

    align-self: start;

    background: #111111;

    border:
        1px solid #242424;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 20px 50px
        rgba(0,0,0,0.35);
}

.summary-title {

    font-size: 21px;

    font-weight: 800;

    margin-bottom: 20px;
}

.summary-movie {

    display: flex;

    gap: 15px;

    padding-bottom: 20px;

    border-bottom:
        1px solid #252525;
}

.summary-movie img {

    width: 75px;
    height: 100px;

    object-fit: cover;

    border-radius: 8px;
}

.summary-movie-info h3 {

    font-size: 16px;

    font-weight: 700;

    margin-bottom: 7px;
}

.summary-movie-info p {

    color: #888;

    font-size: 12px;

    margin: 3px 0;
}


/* =========================================================
   DETAILS
========================================================= */

.summary-details {

    padding: 20px 0;

    border-bottom:
        1px solid #252525;
}

.summary-detail {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 14px;

    font-size: 13px;
}

.summary-detail:last-child {

    margin-bottom: 0;
}

.summary-detail span:first-child {

    color: #777;
}

.summary-detail span:last-child {

    color: #ffffff;

    font-weight: 600;

    text-align: right;
}


/* =========================================================
   SEATS
========================================================= */

.summary-seats {

    padding: 20px 0;

    border-bottom:
        1px solid #252525;
}

.summary-seats-label {

    color: #777;

    font-size: 13px;

    margin-bottom: 10px;
}

.summary-seat-tags {

    display: flex;

    flex-wrap: wrap;

    gap: 7px;
}

.summary-seat-tag {

    background:
        rgba(220,53,69,0.15);

    color: #ff6674;

    border:
        1px solid
        rgba(220,53,69,0.4);

    border-radius: 6px;

    padding: 6px 9px;

    font-size: 12px;

    font-weight: 700;
}


/* =========================================================
   TOTAL
========================================================= */

.summary-total {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding-top: 20px;
}

.summary-total span {

    color: #aaa;
}

.summary-total strong {

    font-size: 25px;

    font-weight: 800;
}


/* =========================================================
   SECURE PAYMENT
========================================================= */

.secure-payment {

    margin-top: 18px;

    padding: 13px;

    border-radius: 10px;

    background: #181818;

    color: #777;

    text-align: center;

    font-size: 11px;
}

.secure-payment i {

    color: #198754;

    margin-right: 5px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .payment-wrapper {

        grid-template-columns: 1fr;
    }

    .payment-summary {

        position: static;

        order: -1;
    }
}


@media (max-width: 767px) {

    .payment-header h1 {

        font-size: 27px;
    }

    .booking-steps {

        overflow-x: auto;

        justify-content: flex-start;

        padding: 0 15px;
    }

    .booking-step-line {

        width: 50px;

        margin: 0 8px;
    }

    .payment-panel {

        padding: 20px;
    }

    .payment-summary {

        padding: 20px;
    }
}

</style>


<div class="payment-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <section class="payment-header">

        <div class="container">

            <h1>
                Complete Your Payment
            </h1>

            <p>
                Review your booking and select your payment method.
            </p>

        </div>

    </section>


    <div class="container">


        <!-- =================================================
             BOOKING STEPS
        ================================================== -->

        <div class="booking-steps">

            <div class="booking-step completed">

                <div class="booking-step-number">
                    <i class="fa-solid fa-check"></i>
                </div>

                <span>
                    Select Seats
                </span>

            </div>


            <div class="booking-step-line active"></div>


            <div class="booking-step active">

                <div class="booking-step-number">
                    2
                </div>

                <span>
                    Payment
                </span>

            </div>


            <div class="booking-step-line"></div>


            <div class="booking-step">

                <div class="booking-step-number">
                    3
                </div>

                <span>
                    Confirmation
                </span>

            </div>

        </div>


        <!-- =================================================
             MAIN
        ================================================== -->

        <div class="payment-wrapper">


            <!-- =================================================
                 PAYMENT PANEL
            ================================================== -->

            <div class="payment-panel">

                <h2>
                    Payment Method
                </h2>

                <p class="payment-panel-subtitle">
                    Choose your preferred online payment method.
                </p>


                <?php if (!empty($payment_error)): ?>

                    <div class="payment-error">

                        <i class="fa-solid fa-circle-exclamation"></i>

                        <?php
                        echo htmlspecialchars(
                            $payment_error
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    action="payment.php"
                    id="paymentForm"
                >


                    <!-- =========================================
                         HIDDEN BOOKING INFORMATION
                    ========================================== -->

                    <input
                        type="hidden"
                        name="movie_id"
                        value="<?php echo $movie_id; ?>"
                    >

                    <input
                        type="hidden"
                        name="cinema"
                        value="<?php echo htmlspecialchars($cinema); ?>"
                    >

                    <input
                        type="hidden"
                        name="date"
                        value="<?php echo htmlspecialchars($booking_date); ?>"
                    >

                    <input
                        type="hidden"
                        name="showtime"
                        value="<?php echo htmlspecialchars($showtime); ?>"
                    >

                    <input
                        type="hidden"
                        name="tickets"
                        value="<?php echo $tickets; ?>"
                    >

                    <input
                        type="hidden"
                        name="seat_numbers"
                        value="<?php echo htmlspecialchars($seat_numbers_raw); ?>"
                    >


                    <!-- =========================================
                         PAYMENT METHODS
                    ========================================== -->

                    <div class="payment-methods">


                        <!-- GCASH -->

                        <div class="payment-method">

                            <input
                                type="radio"
                                name="payment_method"
                                id="gcash"
                                value="GCash"
                                required
                            >

                            <label for="gcash">

                                <div class="payment-method-icon">

                                    <i class="fa-solid fa-mobile-screen-button"></i>

                                </div>

                                <div class="payment-method-info">

                                    <span class="payment-method-title">
                                        GCash
                                    </span>

                                    <span class="payment-method-description">
                                        Pay using your GCash wallet
                                    </span>

                                </div>

                                <div class="payment-method-check">

                                    <i class="fa-solid fa-circle-check"></i>

                                </div>

                            </label>

                        </div>


                        <!-- MAYA -->

                        <div class="payment-method">

                            <input
                                type="radio"
                                name="payment_method"
                                id="maya"
                                value="Maya"
                            >

                            <label for="maya">

                                <div class="payment-method-icon">

                                    <i class="fa-solid fa-wallet"></i>

                                </div>

                                <div class="payment-method-info">

                                    <span class="payment-method-title">
                                        Maya
                                    </span>

                                    <span class="payment-method-description">
                                        Pay using your Maya account
                                    </span>

                                </div>

                                <div class="payment-method-check">

                                    <i class="fa-solid fa-circle-check"></i>

                                </div>

                            </label>

                        </div>


                        <!-- CARD -->

                        <div class="payment-method">

                            <input
                                type="radio"
                                name="payment_method"
                                id="card"
                                value="Credit/Debit Card"
                            >

                            <label for="card">

                                <div class="payment-method-icon">

                                    <i class="fa-solid fa-credit-card"></i>

                                </div>

                                <div class="payment-method-info">

                                    <span class="payment-method-title">
                                        Credit / Debit Card
                                    </span>

                                    <span class="payment-method-description">
                                        Pay using your bank card
                                    </span>

                                </div>

                                <div class="payment-method-check">

                                    <i class="fa-solid fa-circle-check"></i>

                                </div>

                            </label>

                        </div>


                    </div>


                    <!-- =========================================
                         DEMO PAYMENT NOTICE
                    ========================================== -->

                    <div class="demo-payment-note">

                        <i class="fa-solid fa-circle-info"></i>

                        This is a sample online payment system for
                        CineBook. No real payment will be processed.

                    </div>


                    <!-- =========================================
                         PAY BUTTON
                    ========================================== -->

                    <button
                        type="submit"
                        name="pay_now"
                        class="pay-button"
                        id="payButton"
                    >

                        <i class="fa-solid fa-lock me-2"></i>

                        Pay ₱<?php echo number_format($total_amount, 2); ?>

                    </button>


                </form>

            </div>


            <!-- =================================================
                 BOOKING SUMMARY
            ================================================== -->

            <div class="payment-summary">


                <div class="summary-title">
                    Booking Summary
                </div>


                <!-- MOVIE -->

                <div class="summary-movie">

                    <img
                        src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                        alt="<?php echo htmlspecialchars($movie['title']); ?>"
                    >

                    <div class="summary-movie-info">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $movie['title']
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $movie['genre']
                            );
                            ?>
                        </p>

                        <p>
                            ₱<?php
                            echo number_format(
                                $ticket_price,
                                2
                            );
                            ?>
                            / ticket
                        </p>

                    </div>

                </div>


                <!-- DETAILS -->

                <div class="summary-details">


                    <div class="summary-detail">

                        <span>
                            Cinema
                        </span>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $cinema
                            );
                            ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Date
                        </span>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $formatted_date
                            );
                            ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Showtime
                        </span>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $showtime
                            );
                            ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Tickets
                        </span>

                        <span>
                            <?php
                            echo $tickets;
                            ?>
                        </span>

                    </div>


                </div>


                <!-- SEATS -->

                <div class="summary-seats">

                    <div class="summary-seats-label">

                        Selected Seats

                    </div>


                    <div class="summary-seat-tags">

                        <?php foreach ($seat_numbers as $seat): ?>

                            <span class="summary-seat-tag">

                                <?php
                                echo htmlspecialchars(
                                    $seat
                                );
                                ?>

                            </span>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- TOTAL -->

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong>

                        ₱<?php
                        echo number_format(
                            $total_amount,
                            2
                        );
                        ?>

                    </strong>

                </div>


                <!-- SECURE -->

                <div class="secure-payment">

                    <i class="fa-solid fa-shield-halved"></i>

                    Secure demo payment

                </div>


            </div>

        </div>

    </div>

</div>


<script>

/* =========================================================
   PAYMENT FORM
========================================================= */

const paymentForm =
    document.getElementById(
        'paymentForm'
    );

const payButton =
    document.getElementById(
        'payButton'
    );


paymentForm.addEventListener(
    'submit',
    function(event) {

        const selectedPayment =
            document.querySelector(
                'input[name="payment_method"]:checked'
            );


        if (!selectedPayment) {

            event.preventDefault();

            alert(
                'Please select a payment method.'
            );

            return;
        }


        /*
           Prevent accidental double-click
        */

        payButton.disabled = true;

        payButton.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin me-2"></i>' +
            'Processing Payment...';

    }
);

</script>


<?php include "includes/footer.php"; ?>