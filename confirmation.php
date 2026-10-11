<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET BOOKING ID
|--------------------------------------------------------------------------
*/
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($booking_id <= 0) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET BOOKING DETAILS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        b.id AS booking_id,
        b.user_id,
        b.movie_id,
        b.cinema,
        b.booking_date,
        b.showtime,
        b.tickets,
        b.total_amount,
        b.payment_status,
        b.booking_status,
        b.created_at,

        m.title,
        m.genre,
        m.duration,
        m.rating,
        m.poster,

        u.given_name,
        u.surname,
        u.email,
        u.phone_number

    FROM bookings b

    INNER JOIN movies m
        ON b.movie_id = m.id

    INNER JOIN users u
        ON b.user_id = u.id

    WHERE b.id = ?
      AND b.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $booking_id,
    $_SESSION['user_id']
);

$stmt->execute();

$result = $stmt->get_result();

$booking = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| BOOKING NOT FOUND
|--------------------------------------------------------------------------
*/
if (!$booking) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET SELECTED SEATS
|--------------------------------------------------------------------------
*/
$seat_sql = "
    SELECT seat_number
    FROM booking_seats
    WHERE booking_id = ?
    ORDER BY seat_number ASC
";

$seat_stmt = $conn->prepare($seat_sql);

if (!$seat_stmt) {
    die("Database error: " . $conn->error);
}

$seat_stmt->bind_param("i", $booking_id);
$seat_stmt->execute();

$seat_result = $seat_stmt->get_result();

$seats = [];

while ($seat = $seat_result->fetch_assoc()) {
    $seats[] = $seat['seat_number'];
}

$seat_stmt->close();

/*
|--------------------------------------------------------------------------
| FORMAT VALUES
|--------------------------------------------------------------------------
*/
$formatted_date = date(
    'F d, Y',
    strtotime($booking['booking_date'])
);

$booking_date_short = date(
    'M d, Y',
    strtotime($booking['booking_date'])
);

$booking_time = date(
    'h:i A',
    strtotime($booking['created_at'])
);

$created_date = date(
    'F d, Y',
    strtotime($booking['created_at'])
);

$total_amount = number_format(
    (float)$booking['total_amount'],
    2
);

$seat_list = !empty($seats)
    ? implode(', ', $seats)
    : 'No seats recorded';

/*
|--------------------------------------------------------------------------
| BOOKING REFERENCE
|--------------------------------------------------------------------------
*/
$booking_reference = 'CB-' . str_pad(
    $booking['booking_id'],
    6,
    '0',
    STR_PAD_LEFT
);

?>

<?php require_once 'includes/header.php'; ?>

<style>
    body {
        background: #080808;
        color: #ffffff;
    }

    .confirmation-page {
        min-height: calc(100vh - 70px);
        padding: 60px 0 80px;
        background:
            radial-gradient(
                circle at top right,
                rgba(220, 0, 0, 0.15),
                transparent 35%
            ),
            #080808;
    }

    .success-icon {
        width: 85px;
        height: 85px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 35px rgba(220, 53, 69, 0.35);
    }

    .success-icon i {
        font-size: 40px;
        color: #ffffff;
    }

    .confirmation-title {
        font-size: 38px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .confirmation-subtitle {
        color: #aaa;
        font-size: 16px;
        margin-bottom: 40px;
    }

    /*
    |--------------------------------------------------------------------------
    | TICKET
    |--------------------------------------------------------------------------
    */

    .ticket-wrapper {
        max-width: 1000px;
        margin: auto;
    }

    .ticket {
        background: #ffffff;
        color: #171717;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.55);
    }

    .ticket-top {
        padding: 28px 32px;
        background: #151515;
        color: #ffffff;
        border-bottom: 1px solid #2c2c2c;
    }

    .ticket-brand {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .ticket-brand h3 {
        margin: 0;
        font-weight: 700;
    }

    .ticket-brand h3 i {
        color: #dc3545;
        margin-right: 8px;
    }

    .confirmed-badge {
        background: #198754;
        color: #ffffff;
        padding: 7px 14px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /*
    |--------------------------------------------------------------------------
    | MOVIE SECTION
    |--------------------------------------------------------------------------
    */

    .ticket-movie {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 28px;
        padding: 32px;
    }

    .ticket-poster {
        width: 180px;
        height: 260px;
        object-fit: cover;
        border-radius: 12px;
        display: block;
        background: #222;
    }

    .movie-information h2 {
        font-size: 30px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .movie-meta {
        color: #777;
        margin-bottom: 25px;
    }

    .movie-meta span {
        margin-right: 15px;
    }

    .movie-meta i {
        margin-right: 5px;
        color: #dc3545;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAILS GRID
    |--------------------------------------------------------------------------
    */

    .booking-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 18px;
    }

    .detail-box {
        background: #f6f6f6;
        border-radius: 10px;
        padding: 16px;
    }

    .detail-label {
        display: block;
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .detail-value {
        display: block;
        font-size: 16px;
        font-weight: 600;
        color: #222;
    }

    .detail-value i {
        color: #dc3545;
        margin-right: 6px;
    }

    /*
    |--------------------------------------------------------------------------
    | SEATS
    |--------------------------------------------------------------------------
    */

    .seat-section {
        padding: 0 32px 30px;
    }

    .seat-box {
        background: #111111;
        color: #ffffff;
        border-radius: 12px;
        padding: 22px;
    }

    .seat-box-title {
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #aaa;
        margin-bottom: 10px;
    }

    .seat-numbers {
        font-size: 22px;
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 1px;
    }

    /*
    |--------------------------------------------------------------------------
    | PRICE SECTION
    |--------------------------------------------------------------------------
    */

    .ticket-total {
        margin: 0 32px 32px;
        padding: 22px;
        border-radius: 12px;
        background: #f8f8f8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .ticket-total-label {
        color: #777;
        font-size: 14px;
    }

    .ticket-total-label strong {
        display: block;
        color: #222;
        font-size: 18px;
        margin-top: 4px;
    }

    .ticket-total-amount {
        color: #dc3545;
        font-size: 30px;
        font-weight: 700;
    }

    /*
    |--------------------------------------------------------------------------
    | BOOKING REFERENCE
    |--------------------------------------------------------------------------
    */

    .ticket-reference {
        border-top: 2px dashed #cccccc;
        padding: 25px 32px;
        text-align: center;
    }

    .reference-label {
        color: #888;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }

    .reference-number {
        font-size: 26px;
        font-weight: 700;
        letter-spacing: 2px;
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER INFORMATION
    |--------------------------------------------------------------------------
    */

    .customer-section {
        max-width: 1000px;
        margin: 25px auto 0;
        background: #151515;
        border: 1px solid #292929;
        border-radius: 15px;
        padding: 25px;
    }

    .customer-section h5 {
        font-weight: 600;
        margin-bottom: 20px;
    }

    .customer-section h5 i {
        color: #dc3545;
        margin-right: 8px;
    }

    .customer-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .customer-item small {
        display: block;
        color: #777;
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .customer-item span {
        color: #eeeeee;
        font-size: 14px;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION BUTTONS
    |--------------------------------------------------------------------------
    */

    .confirmation-actions {
        max-width: 1000px;
        margin: 30px auto 0;
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .confirmation-actions .btn {
        min-width: 190px;
        padding: 12px 22px;
        font-weight: 600;
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT NOTE
    |--------------------------------------------------------------------------
    */

    .important-note {
        max-width: 1000px;
        margin: 25px auto 0;
        background: rgba(220, 53, 69, 0.08);
        border: 1px solid rgba(220, 53, 69, 0.25);
        border-radius: 12px;
        padding: 18px 20px;
        color: #cccccc;
    }

    .important-note i {
        color: #dc3545;
        margin-right: 8px;
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 768px) {

        .confirmation-page {
            padding: 40px 15px 60px;
        }

        .confirmation-title {
            font-size: 30px;
        }

        .ticket-movie {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .ticket-poster {
            margin: auto;
        }

        .booking-details {
            grid-template-columns: 1fr;
            text-align: left;
        }

        .customer-grid {
            grid-template-columns: 1fr;
        }

        .ticket-total {
            flex-direction: column;
            text-align: center;
        }

        .ticket-top,
        .ticket-movie,
        .seat-section {
            padding-left: 20px;
            padding-right: 20px;
        }

        .ticket-total {
            margin-left: 20px;
            margin-right: 20px;
        }

        .ticket-reference {
            padding-left: 20px;
            padding-right: 20px;
        }

        .ticket-brand {
            flex-direction: column;
            text-align: center;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRINT
    |--------------------------------------------------------------------------
    */

    @media print {

        body {
            background: #ffffff !important;
            color: #000000 !important;
        }

        .navbar,
        .confirmation-actions,
        .important-note,
        footer,
        .customer-section {
            display: none !important;
        }

        .confirmation-page {
            padding: 0 !important;
            background: #ffffff !important;
        }

        .confirmation-title,
        .confirmation-subtitle,
        .success-icon {
            display: none !important;
        }

        .ticket-wrapper {
            max-width: 100%;
        }

        .ticket {
            box-shadow: none !important;
            border: 1px solid #cccccc;
        }

        .ticket-top {
            background: #151515 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .seat-box {
            background: #111111 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

<div class="confirmation-page">

    <div class="container">

        <!-- SUCCESS HEADER -->
        <div class="text-center">

            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>

            <h1 class="confirmation-title">
                Booking Confirmed!
            </h1>

            <p class="confirmation-subtitle">
                Your movie ticket has been successfully booked.
                Please keep this confirmation for your reference.
            </p>

        </div>


        <!-- TICKET -->
        <div class="ticket-wrapper">

            <div class="ticket">

                <!-- TICKET HEADER -->
                <div class="ticket-top">

                    <div class="ticket-brand">

                        <h3>
                            <i class="fa-solid fa-film"></i>
                            CineBook
                        </h3>

                        <span class="confirmed-badge">
                            <i class="fa-solid fa-circle-check"></i>
                            Confirmed
                        </span>

                    </div>

                </div>


                <!-- MOVIE INFORMATION -->
                <div class="ticket-movie">

                    <?php
                    $poster_path = 'assets/posters/' . $booking['poster'];
                    ?>

                    <div>

                        <?php if (!empty($booking['poster']) && file_exists($poster_path)): ?>

                            <img
                                src="<?php echo htmlspecialchars($poster_path); ?>"
                                alt="<?php echo htmlspecialchars($booking['title']); ?>"
                                class="ticket-poster"
                            >

                        <?php else: ?>

                            <div
                                class="ticket-poster d-flex align-items-center justify-content-center bg-dark text-secondary"
                            >
                                <i class="fa-solid fa-film fa-3x"></i>
                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="movie-information">

                        <h2>
                            <?php echo htmlspecialchars($booking['title']); ?>
                        </h2>

                        <div class="movie-meta">

                            <?php if (!empty($booking['genre'])): ?>
                                <span>
                                    <i class="fa-solid fa-tags"></i>
                                    <?php echo htmlspecialchars($booking['genre']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($booking['duration'])): ?>
                                <span>
                                    <i class="fa-regular fa-clock"></i>
                                    <?php echo htmlspecialchars($booking['duration']); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($booking['rating'])): ?>
                                <span>
                                    <i class="fa-solid fa-star"></i>
                                    <?php echo htmlspecialchars($booking['rating']); ?>
                                </span>
                            <?php endif; ?>

                        </div>


                        <!-- BOOKING DETAILS -->
                        <div class="booking-details">

                            <div class="detail-box">

                                <span class="detail-label">
                                    <i class="fa-solid fa-building"></i>
                                    Cinema
                                </span>

                                <span class="detail-value">
                                    <?php echo htmlspecialchars($booking['cinema']); ?>
                                </span>

                            </div>


                            <div class="detail-box">

                                <span class="detail-label">
                                    <i class="fa-regular fa-calendar"></i>
                                    Date
                                </span>

                                <span class="detail-value">
                                    <?php echo htmlspecialchars($formatted_date); ?>
                                </span>

                            </div>


                            <div class="detail-box">

                                <span class="detail-label">
                                    <i class="fa-regular fa-clock"></i>
                                    Showtime
                                </span>

                                <span class="detail-value">
                                    <?php echo htmlspecialchars($booking['showtime']); ?>
                                </span>

                            </div>


                            <div class="detail-box">

                                <span class="detail-label">
                                    <i class="fa-solid fa-ticket"></i>
                                    Tickets
                                </span>

                                <span class="detail-value">
                                    <?php echo htmlspecialchars($booking['tickets']); ?>
                                    <?php echo ((int)$booking['tickets'] === 1) ? 'Ticket' : 'Tickets'; ?>
                                </span>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SEATS -->
                <div class="seat-section">

                    <div class="seat-box">

                        <div class="seat-box-title">
                            <i class="fa-solid fa-chair"></i>
                            Selected Seats
                        </div>

                        <div class="seat-numbers">
                            <?php echo htmlspecialchars($seat_list); ?>
                        </div>

                    </div>

                </div>


                <!-- TOTAL -->
                <div class="ticket-total">

                    <div class="ticket-total-label">

                        Payment Status

                        <strong>
                            <i class="fa-solid fa-circle-check text-success"></i>
                            <?php echo ucfirst(htmlspecialchars($booking['payment_status'])); ?>
                        </strong>

                    </div>

                    <div class="text-end">

                        <div class="ticket-total-label">
                            Total Amount
                        </div>

                        <div class="ticket-total-amount">
                            ₱<?php echo $total_amount; ?>
                        </div>

                    </div>

                </div>


                <!-- BOOKING REFERENCE -->
                <div class="ticket-reference">

                    <div class="reference-label">
                        Booking Reference
                    </div>

                    <div class="reference-number">
                        <?php echo htmlspecialchars($booking_reference); ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- CUSTOMER INFORMATION -->
        <div class="customer-section">

            <h5>
                <i class="fa-solid fa-user"></i>
                Customer Information
            </h5>

            <div class="customer-grid">

                <div class="customer-item">

                    <small>Name</small>

                    <span>
                        <?php echo htmlspecialchars($booking['given_name']); ?>
                        <?php echo htmlspecialchars($booking['surname']); ?>
                    </span>

                </div>


                <div class="customer-item">

                    <small>Email Address</small>

                    <span>
                        <?php echo htmlspecialchars($booking['email']); ?>
                    </span>

                </div>


                <div class="customer-item">

                    <small>Phone Number</small>

                    <span>
                        <?php echo htmlspecialchars($booking['phone_number']); ?>
                    </span>

                </div>


                <div class="customer-item">

                    <small>Booking Date</small>

                    <span>
                        <?php echo htmlspecialchars($created_date); ?>
                    </span>

                </div>


                <div class="customer-item">

                    <small>Booking Time</small>

                    <span>
                        <?php echo htmlspecialchars($booking_time); ?>
                    </span>

                </div>


                <div class="customer-item">

                    <small>Booking ID</small>

                    <span>
                        #<?php echo htmlspecialchars($booking['booking_id']); ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- ACTION BUTTONS -->
        <div class="confirmation-actions">

            <button
                type="button"
                class="btn btn-danger"
                onclick="window.print()"
            >
                <i class="fa-solid fa-print me-2"></i>
                Print Ticket
            </button>


            <a
                href="movies.php"
                class="btn btn-outline-light"
            >
                <i class="fa-solid fa-film me-2"></i>
                Browse Movies
            </a>


            <a
                href="index.php"
                class="btn btn-outline-secondary"
            >
                <i class="fa-solid fa-house me-2"></i>
                Back to Home
            </a>

        </div>


        <!-- IMPORTANT NOTE -->
        <div class="important-note">

            <i class="fa-solid fa-circle-info"></i>

            <strong>Important:</strong>

            Please save or print this ticket and present your
            <strong>Booking Reference</strong> at the cinema.
            Make sure to arrive before your scheduled showtime.

        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>