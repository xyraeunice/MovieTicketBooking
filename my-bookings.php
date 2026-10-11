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
    header('Location: login.php?redirect=my-bookings.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET USER BOOKINGS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        b.id AS booking_id,
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

        GROUP_CONCAT(
            bs.seat_number
            ORDER BY bs.seat_number ASC
            SEPARATOR ', '
        ) AS seats

    FROM bookings b

    INNER JOIN movies m
        ON b.movie_id = m.id

    LEFT JOIN booking_seats bs
        ON b.id = bs.booking_id

    WHERE b.user_id = ?

    GROUP BY
        b.id,
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
        m.poster

    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

?>

<?php require_once 'includes/header.php'; ?>

<style>

    body {
        background: #080808;
        color: #ffffff;
    }

    .my-bookings-page {
        min-height: calc(100vh - 70px);
        padding: 55px 0 80px;

        background:
            radial-gradient(
                circle at top right,
                rgba(220, 0, 0, 0.13),
                transparent 35%
            ),
            #080808;
    }

    /*
    |--------------------------------------------------------------------------
    | PAGE HEADER
    |--------------------------------------------------------------------------
    */

    .page-header {
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-size: 38px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .page-header h1 i {
        color: #dc3545;
        margin-right: 10px;
    }

    .page-header p {
        color: #999;
        margin: 0;
    }

    /*
    |--------------------------------------------------------------------------
    | BOOKING CARD
    |--------------------------------------------------------------------------
    */

    .booking-card {
        background: #151515;
        border: 1px solid #292929;
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 25px;
        transition: 0.3s ease;
    }

    .booking-card:hover {
        border-color: #444;
        transform: translateY(-2px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.35);
    }

    /*
    |--------------------------------------------------------------------------
    | CARD TOP
    |--------------------------------------------------------------------------
    */

    .booking-card-header {
        padding: 17px 22px;
        border-bottom: 1px solid #292929;

        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }

    .booking-reference {
        color: #aaa;
        font-size: 13px;
    }

    .booking-reference strong {
        color: #ffffff;
        font-size: 14px;
        letter-spacing: 1px;
    }

    .status-badge {
        padding: 6px 13px;
        border-radius: 50px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-confirmed {
        background: rgba(25, 135, 84, 0.15);
        color: #49d18d;
        border: 1px solid rgba(25, 135, 84, 0.35);
    }

    .status-pending {
        background: rgba(255, 193, 7, 0.12);
        color: #ffc107;
        border: 1px solid rgba(255, 193, 7, 0.3);
    }

    .status-cancelled {
        background: rgba(220, 53, 69, 0.12);
        color: #ff6b7a;
        border: 1px solid rgba(220, 53, 69, 0.3);
    }

    /*
    |--------------------------------------------------------------------------
    | CARD BODY
    |--------------------------------------------------------------------------
    */

    .booking-card-body {
        padding: 25px;
    }

    .booking-content {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 25px;
    }

    /*
    |--------------------------------------------------------------------------
    | POSTER
    |--------------------------------------------------------------------------
    */

    .booking-poster {
        width: 130px;
        height: 185px;
        object-fit: cover;
        border-radius: 10px;
        background: #222;
    }

    /*
    |--------------------------------------------------------------------------
    | MOVIE INFORMATION
    |--------------------------------------------------------------------------
    */

    .movie-info h2 {
        font-size: 23px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .movie-meta {
        color: #777;
        font-size: 13px;
        margin-bottom: 20px;
    }

    .movie-meta span {
        margin-right: 15px;
    }

    .movie-meta i {
        color: #dc3545;
        margin-right: 5px;
    }

    /*
    |--------------------------------------------------------------------------
    | DETAILS
    |--------------------------------------------------------------------------
    */

    .details-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .detail {
        background: #101010;
        border: 1px solid #242424;
        border-radius: 9px;
        padding: 12px;
    }

    .detail-label {
        display: block;
        color: #777;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .detail-value {
        display: block;
        color: #eeeeee;
        font-size: 14px;
        font-weight: 600;
    }

    .detail-value i {
        color: #dc3545;
        margin-right: 5px;
    }

    /*
    |--------------------------------------------------------------------------
    | SEATS
    |--------------------------------------------------------------------------
    */

    .seats-display {
        margin-top: 15px;
        padding: 13px 15px;
        background: rgba(220, 53, 69, 0.07);
        border: 1px solid rgba(220, 53, 69, 0.18);
        border-radius: 9px;
    }

    .seats-label {
        color: #777;
        font-size: 11px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }

    .seats-value {
        color: #ffffff;
        font-weight: 600;
    }

    /*
    |--------------------------------------------------------------------------
    | CARD FOOTER
    |--------------------------------------------------------------------------
    */

    .booking-card-footer {
        padding: 18px 25px;
        border-top: 1px solid #292929;

        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }

    .payment-info {
        color: #888;
        font-size: 13px;
    }

    .payment-info strong {
        color: #ffffff;
    }

    .total-amount {
        color: #dc3545;
        font-size: 21px;
        font-weight: 700;
        margin-left: 8px;
    }

    .view-ticket-btn {
        min-width: 145px;
        font-weight: 600;
    }

    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    .empty-bookings {
        max-width: 600px;
        margin: 60px auto;
        text-align: center;
        padding: 55px 30px;
        background: #151515;
        border: 1px solid #292929;
        border-radius: 18px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: rgba(220, 53, 69, 0.1);

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .empty-icon i {
        font-size: 32px;
        color: #dc3545;
    }

    .empty-bookings h3 {
        font-weight: 700;
        margin-bottom: 10px;
    }

    .empty-bookings p {
        color: #888;
        margin-bottom: 25px;
    }

    /*
    |--------------------------------------------------------------------------
    | RESPONSIVE
    |--------------------------------------------------------------------------
    */

    @media (max-width: 768px) {

        .my-bookings-page {
            padding: 40px 15px 60px;
        }

        .page-header h1 {
            font-size: 30px;
        }

        .booking-content {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .booking-poster {
            margin: auto;
        }

        .details-grid {
            grid-template-columns: 1fr;
            text-align: left;
        }

        .booking-card-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .booking-card-footer {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .view-ticket-btn {
            width: 100%;
        }

        .movie-meta span {
            display: block;
            margin: 5px 0;
        }
    }

</style>


<div class="my-bookings-page">

    <div class="container">

        <!-- PAGE HEADER -->

        <div class="page-header">

            <h1>
                <i class="fa-solid fa-ticket"></i>
                My Bookings
            </h1>

            <p>
                View and manage your movie ticket bookings.
            </p>

        </div>


        <?php if (empty($bookings)): ?>

            <!-- EMPTY BOOKINGS -->

            <div class="empty-bookings">

                <div class="empty-icon">
                    <i class="fa-solid fa-ticket"></i>
                </div>

                <h3>
                    No Bookings Yet
                </h3>

                <p>
                    You don't have any movie bookings yet.
                    Find a movie and book your seats now!
                </p>

                <a
                    href="movies.php"
                    class="btn btn-danger px-4"
                >
                    <i class="fa-solid fa-film me-2"></i>
                    Browse Movies
                </a>

            </div>

        <?php else: ?>


            <!-- BOOKINGS -->

            <?php foreach ($bookings as $booking): ?>

                <?php

                $booking_reference = 'CB-' . str_pad(
                    $booking['booking_id'],
                    6,
                    '0',
                    STR_PAD_LEFT
                );

                $formatted_date = date(
                    'M d, Y',
                    strtotime($booking['booking_date'])
                );

                $total = number_format(
                    (float)$booking['total_amount'],
                    2
                );

                $seats = !empty($booking['seats'])
                    ? $booking['seats']
                    : 'No seats recorded';

                $booking_status = strtolower(
                    $booking['booking_status']
                );

                if ($booking_status === 'confirmed') {
                    $status_class = 'status-confirmed';
                    $status_icon = 'fa-circle-check';
                } elseif ($booking_status === 'cancelled') {
                    $status_class = 'status-cancelled';
                    $status_icon = 'fa-circle-xmark';
                } else {
                    $status_class = 'status-pending';
                    $status_icon = 'fa-clock';
                }

                ?>


                <div class="booking-card">


                    <!-- CARD HEADER -->

                    <div class="booking-card-header">

                        <div class="booking-reference">

                            Booking Reference:

                            <strong>
                                <?php echo htmlspecialchars($booking_reference); ?>
                            </strong>

                        </div>


                        <span class="status-badge <?php echo $status_class; ?>">

                            <i class="fa-solid <?php echo $status_icon; ?> me-1"></i>

                            <?php echo htmlspecialchars(
                                ucfirst($booking['booking_status'])
                            ); ?>

                        </span>

                    </div>


                    <!-- CARD BODY -->

                    <div class="booking-card-body">

                        <div class="booking-content">


                            <!-- POSTER -->

                            <div>

                                <?php
                                $poster_path =
                                    'assets/posters/' . $booking['poster'];
                                ?>

                                <?php if (
                                    !empty($booking['poster']) &&
                                    file_exists($poster_path)
                                ): ?>

                                    <img
                                        src="<?php echo htmlspecialchars($poster_path); ?>"
                                        alt="<?php echo htmlspecialchars($booking['title']); ?>"
                                        class="booking-poster"
                                    >

                                <?php else: ?>

                                    <div
                                        class="booking-poster d-flex align-items-center justify-content-center bg-dark text-secondary"
                                    >

                                        <i class="fa-solid fa-film fa-3x"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- MOVIE INFORMATION -->

                            <div class="movie-info">

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


                                <!-- DETAILS -->

                                <div class="details-grid">


                                    <div class="detail">

                                        <span class="detail-label">
                                            Cinema
                                        </span>

                                        <span class="detail-value">

                                            <i class="fa-solid fa-building"></i>

                                            <?php echo htmlspecialchars(
                                                $booking['cinema']
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Date
                                        </span>

                                        <span class="detail-value">

                                            <i class="fa-regular fa-calendar"></i>

                                            <?php echo htmlspecialchars(
                                                $formatted_date
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Showtime
                                        </span>

                                        <span class="detail-value">

                                            <i class="fa-regular fa-clock"></i>

                                            <?php echo htmlspecialchars(
                                                $booking['showtime']
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Tickets
                                        </span>

                                        <span class="detail-value">

                                            <i class="fa-solid fa-ticket"></i>

                                            <?php echo htmlspecialchars(
                                                $booking['tickets']
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Payment
                                        </span>

                                        <span class="detail-value">

                                            <i class="fa-solid fa-credit-card"></i>

                                            <?php echo htmlspecialchars(
                                                ucfirst($booking['payment_status'])
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="detail">

                                        <span class="detail-label">
                                            Booking ID
                                        </span>

                                        <span class="detail-value">

                                            #<?php echo htmlspecialchars(
                                                $booking['booking_id']
                                            ); ?>

                                        </span>

                                    </div>

                                </div>


                                <!-- SEATS -->

                                <div class="seats-display">

                                    <span class="seats-label">
                                        <i class="fa-solid fa-chair"></i>
                                        Selected Seats
                                    </span>

                                    <span class="seats-value">

                                        <?php echo htmlspecialchars($seats); ?>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- CARD FOOTER -->

                    <div class="booking-card-footer">


                        <div class="payment-info">

                            Total Amount:

                            <span class="total-amount">
                                ₱<?php echo $total; ?>
                            </span>

                        </div>


                        <a
                            href="confirmation.php?id=<?php echo $booking['booking_id']; ?>"
                            class="btn btn-danger view-ticket-btn"
                        >

                            <i class="fa-solid fa-ticket me-2"></i>

                            View Ticket

                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>


<?php require_once 'includes/footer.php'; ?>