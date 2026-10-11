<?php
require_once "includes/db.php";

/* =========================================================
   GET DATA FROM SEAT SELECTION
========================================================= */

$movie_id = isset($_POST['movie_id']) ? (int) $_POST['movie_id'] : 0;
$cinema = isset($_POST['cinema']) ? trim($_POST['cinema']) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$showtime = isset($_POST['showtime']) ? trim($_POST['showtime']) : '';
$tickets = isset($_POST['tickets']) ? (int) $_POST['tickets'] : 0;
$selected_seats = isset($_POST['selected_seats']) ? trim($_POST['selected_seats']) : '';

/* =========================================================
   VALIDATE DATA
========================================================= */

if (
    $movie_id <= 0 ||
    empty($cinema) ||
    empty($date) ||
    empty($showtime) ||
    $tickets <= 0 ||
    empty($selected_seats)
) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   GET MOVIE
========================================================= */

$sql = "SELECT * FROM movies WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();

$result = $stmt->get_result();
$movie = $result->fetch_assoc();

if (!$movie) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   CHECK SEAT COUNT
========================================================= */

$seat_list = array_filter(
    array_map('trim', explode(',', $selected_seats))
);

if (count($seat_list) !== $tickets) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   TOTAL
========================================================= */

$total = $movie['price'] * $tickets;

?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>


<section class="customer-page">

    <div class="customer-container">

        <!-- HEADER -->

        <div class="customer-header">

            <div class="customer-label">
                <i class="fa-solid fa-user"></i>
                CUSTOMER DETAILS
            </div>

            <h1>
                Almost There!
            </h1>

            <p>
                Enter your information to complete your booking.
            </p>

        </div>


        <!-- MAIN LAYOUT -->

        <div class="customer-layout">


            <!-- BOOKING SUMMARY -->

            <div class="customer-summary">

                <div class="summary-poster">

                    <img
                        src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                        alt="<?php echo htmlspecialchars($movie['title']); ?>"
                    >

                </div>


                <div class="summary-content">

                    <h2>
                        <?php echo htmlspecialchars($movie['title']); ?>
                    </h2>


                    <div class="summary-item">

                        <i class="fa-solid fa-building"></i>

                        <span>
                            <?php echo htmlspecialchars($cinema); ?>
                        </span>

                    </div>


                    <div class="summary-item">

                        <i class="fa-regular fa-calendar"></i>

                        <span>
                            <?php echo htmlspecialchars($date); ?>
                        </span>

                    </div>


                    <div class="summary-item">

                        <i class="fa-regular fa-clock"></i>

                        <span>
                            <?php echo htmlspecialchars($showtime); ?>
                        </span>

                    </div>


                    <div class="summary-item">

                        <i class="fa-solid fa-couch"></i>

                        <span>
                            Seats:
                            <?php echo htmlspecialchars(implode(', ', $seat_list)); ?>
                        </span>

                    </div>


                    <div class="summary-item">

                        <i class="fa-solid fa-ticket"></i>

                        <span>
                            <?php echo $tickets; ?>
                            <?php echo $tickets == 1 ? 'Ticket' : 'Tickets'; ?>
                        </span>

                    </div>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>

                        <strong>
                            ₱<?php echo number_format($total, 0); ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- CUSTOMER FORM -->

            <div class="customer-form-card">

                <form
                    action="confirmation.php"
                    method="POST"
                    id="customerForm"
                >

                    <!-- HIDDEN DATA -->

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
                        value="<?php echo htmlspecialchars($date); ?>"
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
                        name="selected_seats"
                        value="<?php echo htmlspecialchars($selected_seats); ?>"
                    >


                    <!-- FULL NAME -->

                    <div class="customer-field">

                        <label for="fullname">

                            <i class="fa-solid fa-user"></i>

                            Full Name

                        </label>

                        <input
                            type="text"
                            name="fullname"
                            id="fullname"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="customer-field">

                        <label for="email">

                            <i class="fa-solid fa-envelope"></i>

                            Email Address

                        </label>

                        <input
                            type="email"
                            name="email"
                            id="email"
                            placeholder="Enter your email address"
                            required
                        >

                    </div>


                    <!-- CONTACT -->

                    <div class="customer-field">

                        <label for="contact">

                            <i class="fa-solid fa-phone"></i>

                            Contact Number

                        </label>

                        <input
                            type="tel"
                            name="contact"
                            id="contact"
                            placeholder="09XXXXXXXXX"
                            maxlength="11"
                            required
                        >

                    </div>


                    <!-- NOTICE -->

                    <div class="customer-notice">

                        <i class="fa-solid fa-circle-info"></i>

                        <span>
                            Please make sure your information is correct
                            before confirming your booking.
                        </span>

                    </div>


                    <!-- CONFIRM -->

                    <button
                        type="submit"
                        class="confirm-booking-btn"
                    >

                        <i class="fa-solid fa-check"></i>

                        Confirm Booking

                    </button>

                </form>

            </div>

        </div>

    </div>

</section>


<style>

/* =========================================================
   CUSTOMER PAGE
========================================================= */

.customer-page {

    min-height: calc(100vh - 80px);

    background: #0d0d0d;

    color: #ffffff;

    padding: 55px 20px 90px;

}


.customer-container {

    width: 100%;

    max-width: 1100px;

    margin: 0 auto;

}


/* =========================================================
   HEADER
========================================================= */

.customer-header {

    text-align: center;

    margin-bottom: 45px;

}


.customer-label {

    color: #e52f45;

    font-size: 14px;

    font-weight: 600;

    margin-bottom: 12px;

}


.customer-label i {

    margin-right: 6px;

}


.customer-header h1 {

    margin: 0 0 10px;

    font-size: clamp(32px, 4vw, 48px);

    font-weight: 600;

}


.customer-header p {

    margin: 0;

    color: #aaa;

    font-size: 15px;

}


/* =========================================================
   LAYOUT
========================================================= */

.customer-layout {

    display: grid;

    grid-template-columns: 420px minmax(0, 1fr);

    gap: 45px;

    align-items: start;

}


/* =========================================================
   SUMMARY
========================================================= */

.customer-summary {

    overflow: hidden;

    background: #181818;

    border-radius: 16px;

    box-shadow: 0 15px 40px rgba(0,0,0,0.35);

}


.summary-poster {

    width: 100%;

    height: 450px;

}


.summary-poster img {

    width: 100%;

    height: 100%;

    object-fit: cover;

    display: block;

}


.summary-content {

    padding: 25px;

}


.summary-content h2 {

    margin: 0 0 20px;

    font-size: 24px;

    font-weight: 600;

}


.summary-item {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 13px;

    color: #bbb;

    font-size: 14px;

}


.summary-item i {

    width: 20px;

    color: #e52f45;

    text-align: center;

}


.summary-total {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-top: 22px;

    padding-top: 18px;

    border-top: 1px solid #333;

}


.summary-total span {

    color: #aaa;

    font-size: 15px;

}


.summary-total strong {

    color: #fff;

    font-size: 24px;

}


/* =========================================================
   FORM CARD
========================================================= */

.customer-form-card {

    width: 100%;

    background: #181818;

    padding: 35px;

    border-radius: 16px;

    box-shadow: 0 15px 40px rgba(0,0,0,0.35);

}


.customer-field {

    width: 100%;

    margin-bottom: 24px;

}


.customer-field label {

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 10px;

    color: #fff;

    font-size: 15px;

    font-weight: 500;

}


.customer-field label i {

    color: #e52f45;

    width: 18px;

    text-align: center;

}


.customer-field input {

    display: block;

    width: 100%;

    height: 54px;

    padding: 0 15px;

    border: 1px solid #3a3a3a;

    border-radius: 8px;

    background: #ffffff;

    color: #222;

    font-family: 'Poppins', sans-serif;

    font-size: 15px;

    outline: none;

}


.customer-field input:focus {

    border-color: #e52f45;

    box-shadow: 0 0 0 3px rgba(229,47,69,0.15);

}


.customer-field input::placeholder {

    color: #888;

}


/* =========================================================
   NOTICE
========================================================= */

.customer-notice {

    display: flex;

    gap: 10px;

    align-items: flex-start;

    padding: 15px;

    margin: 5px 0 25px;

    border-radius: 8px;

    background: #242424;

    color: #aaa;

    font-size: 13px;

    line-height: 1.5;

}


.customer-notice i {

    color: #e52f45;

    margin-top: 2px;

}


/* =========================================================
   CONFIRM BUTTON
========================================================= */

.confirm-booking-btn {

    display: flex;

    justify-content: center;

    align-items: center;

    gap: 9px;

    width: 100%;

    height: 55px;

    border: none;

    border-radius: 8px;

    background: #e52f45;

    color: #fff;

    font-family: 'Poppins', sans-serif;

    font-size: 17px;

    cursor: pointer;

    transition: 0.2s ease;

}


.confirm-booking-btn:hover {

    background: #c9253a;

    transform: translateY(-1px);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .customer-layout {

        grid-template-columns: 1fr;

        gap: 30px;

    }

    .customer-summary {

        max-width: 500px;

        margin: 0 auto;

    }

}


@media (max-width: 576px) {

    .customer-page {

        padding: 35px 15px 60px;

    }

    .customer-header {

        margin-bottom: 30px;

    }

    .customer-header h1 {

        font-size: 30px;

    }

    .summary-poster {

        height: auto;

        aspect-ratio: 2 / 3;

    }

    .summary-content {

        padding: 20px;

    }

    .customer-form-card {

        padding: 22px;

        border-radius: 12px;

    }

    .customer-field input {

        height: 50px;

        font-size: 14px;

    }

    .confirm-booking-btn {

        height: 52px;

        font-size: 15px;

    }

}

</style>


<?php include "includes/footer.php"; ?>