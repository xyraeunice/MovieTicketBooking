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
   GET BOOKING INFORMATION
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


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $movie_id <= 0 ||
    empty($cinema) ||
    empty($booking_date) ||
    empty($showtime) ||
    $tickets < 1 ||
    $tickets > 6
) {

    header("Location: movies.php");
    exit;
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

$sql = "
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

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $movie_id
);

$stmt->execute();

$result = $stmt->get_result();

$movie = $result->fetch_assoc();

$stmt->close();


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
        "Location: movie-details.php?id=" .
        $movie_id
    );

    exit;
}


/* =========================================================
   GET OCCUPIED SEATS
=========================================================

   booking_seats only contains:

   id
   booking_id
   seat_number

   So we JOIN it with bookings.

========================================================= */

$occupied_seats = [];

$sql_occupied = "
    SELECT bs.seat_number

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

$stmt_occupied = $conn->prepare($sql_occupied);

$stmt_occupied->bind_param(
    "isss",
    $movie_id,
    $cinema,
    $booking_date,
    $showtime
);

$stmt_occupied->execute();

$result_occupied = $stmt_occupied->get_result();

while ($row = $result_occupied->fetch_assoc()) {

    $occupied_seats[] =
        $row['seat_number'];
}

$stmt_occupied->close();


/* =========================================================
   TICKET PRICE
========================================================= */

$ticket_price = (float) $movie['price'];

$total_amount =
    $ticket_price * $tickets;


/* =========================================================
   FORMATTED DATE
========================================================= */

$formatted_date = date(
    "F d, Y",
    strtotime($booking_date)
);

?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>


<style>

/* =========================================================
   PAGE
========================================================= */

.seat-page {
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
   MOVIE BANNER
========================================================= */

.seat-movie-banner {
    padding: 45px 0 30px;

    border-bottom:
        1px solid rgba(255,255,255,0.08);
}

.seat-movie-banner-inner {
    display: flex;

    align-items: center;

    gap: 25px;
}

.seat-movie-poster {
    width: 105px;
    height: 145px;

    object-fit: cover;

    border-radius: 12px;

    box-shadow:
        0 15px 40px rgba(0,0,0,0.6);
}

.seat-movie-info h1 {
    font-size: 34px;

    font-weight: 800;

    margin-bottom: 10px;
}

.seat-movie-meta {
    display: flex;

    flex-wrap: wrap;

    gap: 18px;

    color: #bdbdbd;
}

.seat-movie-meta span {
    display: flex;

    align-items: center;

    gap: 7px;
}

.seat-movie-meta i {
    color: #dc3545;
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

.booking-step.active .booking-step-number {
    background: #dc3545;

    color: #ffffff;

    box-shadow:
        0 0 0 5px rgba(220,53,69,0.12);
}

.booking-step-line {
    height: 2px;

    width: 100px;

    background: #292929;

    margin: 0 15px;
}


/* =========================================================
   MAIN LAYOUT
========================================================= */

.seat-selection-wrapper {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        350px;

    gap: 25px;

    max-width: 1250px;

    margin: auto;
}


/* =========================================================
   SEAT PANEL
========================================================= */

.seat-panel {
    background: #111111;

    border: 1px solid #242424;

    border-radius: 18px;

    padding: 30px;

    box-shadow:
        0 20px 50px rgba(0,0,0,0.35);
}

.seat-panel-header {
    text-align: center;

    margin-bottom: 30px;
}

.seat-panel-header h2 {
    font-size: 28px;

    font-weight: 800;

    margin-bottom: 8px;
}

.seat-panel-header p {
    color: #888;

    margin: 0;
}


/* =========================================================
   SCREEN
========================================================= */

.screen-area {
    width: 75%;

    margin:
        10px auto 45px;

    text-align: center;
}

.screen {
    height: 18px;

    border-radius:
        50% 50% 0 0 /
        100% 100% 0 0;

    background:
        linear-gradient(
            to bottom,
            #ffffff,
            #8f8f8f
        );

    box-shadow:
        0 -8px 35px
        rgba(255,255,255,0.18);

    transform:
        perspective(200px)
        rotateX(-4deg);
}

.screen-label {
    margin-top: 12px;

    font-size: 11px;

    letter-spacing: 5px;

    color: #777;

    font-weight: 700;
}


/* =========================================================
   SEAT AREA
========================================================= */

.seat-area {
    width: 100%;

    overflow-x: auto;

    padding-bottom: 10px;
}

.seat-layout {
    min-width: 650px;

    display: flex;

    flex-direction: column;

    gap: 12px;
}

.seat-row {
    display: grid;

    grid-template-columns:
        35px
        repeat(5, 1fr)
        28px
        repeat(5, 1fr)
        28px
        repeat(5, 1fr)
        35px;

    align-items: center;

    gap: 7px;
}

.row-label {
    color: #777;

    font-size: 12px;

    font-weight: 700;

    text-align: center;
}


/* =========================================================
   SEAT
========================================================= */

.seat {
    height: 35px;

    border: none;

    border-radius:
        8px 8px 5px 5px;

    background: #c72d3b;

    color: #ffffff;

    font-size: 11px;

    font-weight: 700;

    cursor: pointer;

    transition: 0.15s ease;
}

.seat:hover:not(:disabled) {
    transform:
        translateY(-3px);

    background: #ed4352;

    box-shadow:
        0 8px 15px
        rgba(220,53,69,0.25);
}

.seat.selected {
    background: #ff5968;

    box-shadow:
        0 0 0 3px rgba(255,255,255,0.7),
        0 8px 20px
        rgba(220,53,69,0.4);

    transform:
        translateY(-3px);
}

.seat.occupied {
    background: #383838;

    color: #777;

    cursor: not-allowed;
}

.seat.occupied:hover {
    transform: none;
}


/* =========================================================
   AISLE
========================================================= */

.aisle {
    width: 28px;
}


/* =========================================================
   LEGEND
========================================================= */

.seat-legend {
    display: flex;

    justify-content: center;

    flex-wrap: wrap;

    gap: 25px;

    margin-top: 35px;

    padding-top: 25px;

    border-top:
        1px solid #252525;
}

.legend-item {
    display: flex;

    align-items: center;

    gap: 8px;

    color: #999;

    font-size: 13px;
}

.legend-seat {
    width: 20px;
    height: 20px;

    border-radius: 5px;
}

.legend-available {
    background: #c72d3b;
}

.legend-selected {
    background: #ff5968;

    box-shadow:
        0 0 0 2px #ffffff;
}

.legend-occupied {
    background: #383838;
}


/* =========================================================
   SUMMARY
========================================================= */

.booking-summary {
    position: sticky;

    top: 25px;

    align-self: start;

    background: #111111;

    border: 1px solid #242424;

    border-radius: 18px;

    padding: 25px;

    box-shadow:
        0 20px 50px rgba(0,0,0,0.35);
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
   SUMMARY DETAILS
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
   SELECTED SEATS
========================================================= */

.selected-seats-box {
    margin-top: 20px;
}

.selected-seats-label {
    color: #777;

    font-size: 13px;

    margin-bottom: 10px;
}

.selected-seats-list {
    min-height: 42px;

    display: flex;

    flex-wrap: wrap;

    gap: 7px;
}

.selected-seat-tag {
    background:
        rgba(220,53,69,0.15);

    color: #ff6674;

    border:
        1px solid rgba(220,53,69,0.4);

    border-radius: 6px;

    padding: 6px 9px;

    font-size: 12px;

    font-weight: 700;
}

.no-seats {
    color: #555;

    font-size: 12px;
}


/* =========================================================
   TOTAL
========================================================= */

.summary-total {
    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 20px 0;
}

.summary-total span {
    color: #aaa;
}

.summary-total strong {
    color: #ffffff;

    font-size: 26px;
}


/* =========================================================
   BUTTON
========================================================= */

.continue-btn {
    width: 100%;

    border: none;

    border-radius: 10px;

    padding: 14px;

    background: #dc3545;

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

    transition: 0.2s ease;
}

.continue-btn:hover:not(:disabled) {
    background: #bb2d3b;

    transform:
        translateY(-2px);

    box-shadow:
        0 10px 25px
        rgba(220,53,69,0.25);
}

.continue-btn:disabled {
    background: #303030;

    color: #666;

    cursor: not-allowed;
}


/* =========================================================
   WARNING
========================================================= */

.selection-warning {
    display: none;

    text-align: center;

    color: #ff6674;

    font-size: 12px;

    margin-top: 10px;
}


/* =========================================================
   TIPS
========================================================= */

.booking-tips {
    margin-top: 20px;

    padding: 18px;

    background: #181818;

    border-radius: 12px;

    border: 1px solid #242424;
}

.booking-tips h4 {
    font-size: 14px;

    font-weight: 700;

    margin-bottom: 10px;
}

.booking-tips ul {
    padding-left: 18px;

    margin: 0;
}

.booking-tips li {
    color: #777;

    font-size: 12px;

    margin-bottom: 6px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 991px) {

    .seat-selection-wrapper {
        grid-template-columns: 1fr;
    }

    .booking-summary {
        position: static;
    }
}


@media (max-width: 767px) {

    .seat-movie-banner-inner {
        align-items: flex-start;
    }

    .seat-movie-poster {
        width: 80px;
        height: 110px;
    }

    .seat-movie-info h1 {
        font-size: 23px;
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

    .seat-panel {
        padding: 20px 12px;
    }

    .seat-panel-header h2 {
        font-size: 23px;
    }

    .screen-area {
        width: 85%;
    }
}

</style>


<div class="seat-page">


    <!-- =====================================================
         MOVIE INFORMATION
    ====================================================== -->

    <section class="seat-movie-banner">

        <div class="container">

            <div class="seat-movie-banner-inner">

                <img
                    src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                    alt="<?php echo htmlspecialchars($movie['title']); ?>"
                    class="seat-movie-poster"
                >

                <div class="seat-movie-info">

                    <h1>
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </h1>

                    <div class="seat-movie-meta">

                        <span>
                            <i class="fa-solid fa-film"></i>

                            <?php echo htmlspecialchars($movie['genre']); ?>
                        </span>

                        <span>
                            <i class="fa-regular fa-clock"></i>

                            <?php echo htmlspecialchars($movie['duration']); ?>
                            minutes
                        </span>

                        <span>
                            <i class="fa-solid fa-star"></i>

                            <?php echo htmlspecialchars($movie['rating']); ?>
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <div class="container">


        <!-- =================================================
             BOOKING STEPS
        ================================================== -->

        <div class="booking-steps">

            <div class="booking-step active">

                <div class="booking-step-number">
                    1
                </div>

                <span>
                    Select Seats
                </span>

            </div>


            <div class="booking-step-line"></div>


            <div class="booking-step">

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
             SEAT + SUMMARY
        ================================================== -->

        <div class="seat-selection-wrapper">


            <!-- =================================================
                 SEAT SELECTION
            ================================================== -->

            <div class="seat-panel">

                <div class="seat-panel-header">

                    <h2>
                        Select Your Seats
                    </h2>

                    <p>
                        Choose
                        <?php echo $tickets; ?>
                        seat<?php echo $tickets > 1 ? 's' : ''; ?>
                    </p>

                </div>


                <!-- SCREEN -->

                <div class="screen-area">

                    <div class="screen"></div>

                    <div class="screen-label">
                        SCREEN
                    </div>

                </div>


                <!-- SEATS -->

                <div class="seat-area">

                    <div class="seat-layout">


                        <?php

                        $rows = range('A', 'J');

                        foreach ($rows as $row):

                        ?>

                            <div class="seat-row">


                                <div class="row-label">
                                    <?php echo $row; ?>
                                </div>


                                <!-- SEATS 1-5 -->

                                <?php
                                for (
                                    $seat_num = 1;
                                    $seat_num <= 5;
                                    $seat_num++
                                ):
                                ?>

                                    <?php

                                    $seat_number =
                                        $row . $seat_num;

                                    $is_occupied =
                                        in_array(
                                            $seat_number,
                                            $occupied_seats,
                                            true
                                        );

                                    ?>

                                    <button
                                        type="button"
                                        class="seat <?php echo $is_occupied ? 'occupied' : ''; ?>"
                                        data-seat="<?php echo $seat_number; ?>"
                                        <?php echo $is_occupied ? 'disabled' : ''; ?>
                                    >
                                        <?php echo $seat_num; ?>
                                    </button>

                                <?php endfor; ?>


                                <div class="aisle"></div>


                                <!-- SEATS 6-10 -->

                                <?php
                                for (
                                    $seat_num = 6;
                                    $seat_num <= 10;
                                    $seat_num++
                                ):
                                ?>

                                    <?php

                                    $seat_number =
                                        $row . $seat_num;

                                    $is_occupied =
                                        in_array(
                                            $seat_number,
                                            $occupied_seats,
                                            true
                                        );

                                    ?>

                                    <button
                                        type="button"
                                        class="seat <?php echo $is_occupied ? 'occupied' : ''; ?>"
                                        data-seat="<?php echo $seat_number; ?>"
                                        <?php echo $is_occupied ? 'disabled' : ''; ?>
                                    >
                                        <?php echo $seat_num; ?>
                                    </button>

                                <?php endfor; ?>


                                <div class="aisle"></div>


                                <!-- SEATS 11-15 -->

                                <?php
                                for (
                                    $seat_num = 11;
                                    $seat_num <= 15;
                                    $seat_num++
                                ):
                                ?>

                                    <?php

                                    $seat_number =
                                        $row . $seat_num;

                                    $is_occupied =
                                        in_array(
                                            $seat_number,
                                            $occupied_seats,
                                            true
                                        );

                                    ?>

                                    <button
                                        type="button"
                                        class="seat <?php echo $is_occupied ? 'occupied' : ''; ?>"
                                        data-seat="<?php echo $seat_number; ?>"
                                        <?php echo $is_occupied ? 'disabled' : ''; ?>
                                    >
                                        <?php echo $seat_num; ?>
                                    </button>

                                <?php endfor; ?>


                                <div class="row-label">
                                    <?php echo $row; ?>
                                </div>


                            </div>

                        <?php endforeach; ?>


                    </div>

                </div>


                <!-- LEGEND -->

                <div class="seat-legend">

                    <div class="legend-item">

                        <div class="legend-seat legend-available"></div>

                        <span>
                            Available
                        </span>

                    </div>


                    <div class="legend-item">

                        <div class="legend-seat legend-selected"></div>

                        <span>
                            Selected
                        </span>

                    </div>


                    <div class="legend-item">

                        <div class="legend-seat legend-occupied"></div>

                        <span>
                            Occupied
                        </span>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 BOOKING SUMMARY
            ================================================== -->

            <div class="booking-summary">

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
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </h3>

                        <p>
                            <?php echo htmlspecialchars($movie['genre']); ?>
                        </p>

                        <p>
                            ₱<?php echo number_format($ticket_price, 2); ?>
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
                            <?php echo htmlspecialchars($cinema); ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Date
                        </span>

                        <span>
                            <?php echo htmlspecialchars($formatted_date); ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Showtime
                        </span>

                        <span>
                            <?php echo htmlspecialchars($showtime); ?>
                        </span>

                    </div>


                    <div class="summary-detail">

                        <span>
                            Tickets
                        </span>

                        <span id="ticketCount">
                            0 / <?php echo $tickets; ?>
                        </span>

                    </div>

                </div>


                <!-- SELECTED SEATS -->

                <div class="selected-seats-box">

                    <div class="selected-seats-label">
                        Selected Seats
                    </div>

                    <div
                        class="selected-seats-list"
                        id="selectedSeatsList"
                    >

                        <span class="no-seats">
                            No seats selected
                        </span>

                    </div>

                </div>


                <!-- TOTAL -->

                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <strong id="totalAmount">
                        ₱0.00
                    </strong>

                </div>


                <!-- PAYMENT FORM -->

                <form
                    method="POST"
                    action="payment.php"
                    id="seatForm"
                >

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
                        id="seatNumbersInput"
                        value=""
                    >


                    <button
                        type="submit"
                        class="continue-btn"
                        id="continueButton"
                        disabled
                    >

                        Continue to Payment

                        <i class="fa-solid fa-arrow-right ms-2"></i>

                    </button>


                    <div
                        class="selection-warning"
                        id="selectionWarning"
                    >
                        Please select exactly
                        <?php echo $tickets; ?>
                        seat<?php echo $tickets > 1 ? 's' : ''; ?>.
                    </div>

                </form>


                <!-- TIPS -->

                <div class="booking-tips">

                    <h4>
                        <i class="fa-solid fa-circle-info text-danger"></i>
                        Seat Selection Tips
                    </h4>

                    <ul>

                        <li>
                            Click an available seat to select it.
                        </li>

                        <li>
                            Gray seats are already occupied.
                        </li>

                        <li>
                            Select exactly
                            <?php echo $tickets; ?>
                            seat<?php echo $tickets > 1 ? 's' : ''; ?>.
                        </li>

                        <li>
                            Seats are confirmed after payment.
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/* =========================================================
   VARIABLES
========================================================= */

const ticketLimit =
    <?php echo $tickets; ?>;

const ticketPrice =
    <?php echo $ticket_price; ?>;

const seats =
    document.querySelectorAll(
        '.seat:not(.occupied)'
    );

const selectedSeatsList =
    document.getElementById(
        'selectedSeatsList'
    );

const ticketCount =
    document.getElementById(
        'ticketCount'
    );

const totalAmount =
    document.getElementById(
        'totalAmount'
    );

const continueButton =
    document.getElementById(
        'continueButton'
    );

const seatNumbersInput =
    document.getElementById(
        'seatNumbersInput'
    );

const selectionWarning =
    document.getElementById(
        'selectionWarning'
    );


let selectedSeats = [];


/* =========================================================
   CLICK SEAT
========================================================= */

seats.forEach(function(seat) {

    seat.addEventListener(
        'click',
        function() {

            const seatNumber =
                this.dataset.seat;


            /* REMOVE */

            if (
                selectedSeats.includes(
                    seatNumber
                )
            ) {

                selectedSeats =
                    selectedSeats.filter(
                        function(seat) {

                            return seat !==
                                seatNumber;

                        }
                    );

                this.classList.remove(
                    'selected'
                );

            }


            /* ADD */

            else {

                if (
                    selectedSeats.length >=
                    ticketLimit
                ) {

                    selectionWarning.style.display =
                        'block';

                    selectionWarning.textContent =
                        'You can only select ' +
                        ticketLimit +
                        ' seat' +
                        (
                            ticketLimit > 1
                                ? 's.'
                                : '.'
                        );

                    return;
                }


                selectedSeats.push(
                    seatNumber
                );

                this.classList.add(
                    'selected'
                );

            }


            updateSummary();

        }
    );

});


/* =========================================================
   UPDATE SUMMARY
========================================================= */

function updateSummary() {


    /* SORT */

    selectedSeats.sort(
        function(a, b) {

            const rowA =
                a.charAt(0);

            const rowB =
                b.charAt(0);

            if (
                rowA !== rowB
            ) {

                return rowA.localeCompare(
                    rowB
                );
            }

            return (
                parseInt(
                    a.substring(1)
                )
                -
                parseInt(
                    b.substring(1)
                )
            );

        }
    );


    const count =
        selectedSeats.length;


    /* COUNT */

    ticketCount.textContent =
        count +
        ' / ' +
        ticketLimit;


    /* SELECTED SEATS */

    selectedSeatsList.innerHTML =
        '';


    if (count === 0) {

        selectedSeatsList.innerHTML =
            '<span class="no-seats">' +
            'No seats selected' +
            '</span>';

    }

    else {

        selectedSeats.forEach(
            function(seat) {

                const tag =
                    document.createElement(
                        'span'
                    );

                tag.className =
                    'selected-seat-tag';

                tag.textContent =
                    seat;

                selectedSeatsList.appendChild(
                    tag
                );

            }
        );

    }


    /* TOTAL */

    const total =
        count *
        ticketPrice;


    totalAmount.textContent =
        '₱' +
        total.toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );


    /* HIDDEN INPUT */

    seatNumbersInput.value =
        selectedSeats.join(',');


    /* BUTTON */

    if (
        count === ticketLimit
    ) {

        continueButton.disabled =
            false;

        selectionWarning.style.display =
            'none';

    }

    else {

        continueButton.disabled =
            true;

    }

}


/* =========================================================
   FORM VALIDATION
========================================================= */

document
    .getElementById('seatForm')
    .addEventListener(
        'submit',
        function(event) {

            if (
                selectedSeats.length !==
                ticketLimit
            ) {

                event.preventDefault();

                selectionWarning.style.display =
                    'block';

                selectionWarning.textContent =
                    'Please select exactly ' +
                    ticketLimit +
                    ' seat' +
                    (
                        ticketLimit > 1
                            ? 's.'
                            : '.'
                    );

            }

        }
    );


/* =========================================================
   INITIALIZE
========================================================= */

updateSummary();

</script>


<?php include "includes/footer.php"; ?>