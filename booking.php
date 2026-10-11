<?php

/* =========================================================
   START SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   LOGIN PROTECTION
========================================================= */

/*
   Customer must be logged in before accessing the
   booking page.
*/

if (!isset($_SESSION['user_id'])) {

    /*
       Save the current booking page so the customer
       can return here after successful login.
    */

    $movie_id_for_redirect = isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;

    if ($movie_id_for_redirect > 0) {

        $redirect = "booking.php?id=" . $movie_id_for_redirect;

    } else {

        $redirect = "index.php";

    }


    /*
       Send customer to login page.
    */

    header(
        "Location: login.php?redirect=" .
        urlencode($redirect)
    );

    exit;
}


/* =========================================================
   DATABASE
========================================================= */

require_once "includes/db.php";


/* =========================================================
   GET MOVIE ID
========================================================= */

$movie_id = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

    $movie_id = (int) $_GET['id'];

}


/* =========================================================
   INVALID MOVIE ID
========================================================= */

if ($movie_id <= 0) {

    header("Location: index.php");

    exit;

}


/* =========================================================
   GET MOVIE INFORMATION
========================================================= */

$sql = "SELECT * FROM movies WHERE id = ?";

$stmt = $conn->prepare($sql);


if (!$stmt) {

    die("Database error.");

}


$stmt->bind_param("i", $movie_id);

$stmt->execute();

$result = $stmt->get_result();

$movie = $result->fetch_assoc();


/* =========================================================
   MOVIE NOT FOUND
========================================================= */

if (!$movie) {

    header("Location: index.php");

    exit;

}


/* =========================================================
   ONLY NOW SHOW BOOKING PAGE
========================================================= */

?>


<?php include "includes/header.php"; ?>

<?php include "includes/navbar.php"; ?>


<!-- =========================================================
     BOOKING PAGE
========================================================= -->

<section class="booking-page">

    <div class="booking-container">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="booking-page-header">

            <div class="booking-label">

                <i class="fa-solid fa-ticket"></i>

                BOOK YOUR TICKET

            </div>


            <h1>

                <?php
                echo htmlspecialchars($movie['title']);
                ?>

            </h1>


            <p>

                Select your cinema, date, showtime,
                and number of tickets.

            </p>

        </div>



        <!-- =================================================
             MAIN BOOKING LAYOUT
        ================================================== -->

        <div class="booking-layout">


            <!-- =================================================
                 LEFT COLUMN
            ================================================== -->

            <div class="booking-movie-column">


                <div class="booking-movie-card">


                    <!-- =================================================
                         POSTER
                    ================================================== -->

                    <div class="booking-poster-wrapper">

                        <img
                            src="assets/posters/<?php
                            echo htmlspecialchars(
                                $movie['poster']
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $movie['title']
                            );
                            ?>"
                        >

                    </div>



                    <!-- =================================================
                         MOVIE INFORMATION
                    ================================================== -->

                    <div class="booking-movie-info">


                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $movie['title']
                            );
                            ?>

                        </h2>



                        <!-- GENRE -->

                        <div class="movie-info-item">

                            <i class="fa-solid fa-film"></i>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $movie['genre']
                                );
                                ?>

                            </span>

                        </div>



                        <!-- DURATION -->

                        <div class="movie-info-item">

                            <i class="fa-regular fa-clock"></i>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $movie['duration']
                                );
                                ?>

                                minutes

                            </span>

                        </div>



                        <!-- PRICE -->

                        <div class="movie-price">

                            ₱<?php
                            echo number_format(
                                $movie['price'],
                                0
                            );
                            ?>

                            <small>
                                per ticket
                            </small>

                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 RIGHT COLUMN
            ================================================== -->

            <div class="booking-form-column">


                <form
                    action="seat-selection.php"
                    method="POST"
                    id="bookingForm"
                >


                    <!-- =================================================
                         MOVIE ID
                    ================================================== -->

                    <input
                        type="hidden"
                        name="movie_id"
                        value="<?php
                        echo $movie['id'];
                        ?>"
                    >



                    <!-- =================================================
                         CINEMA
                    ================================================== -->

                    <div class="form-group">


                        <label for="cinema">

                            <i class="fa-solid fa-building"></i>

                            <span>
                                Select Cinema
                            </span>

                        </label>


                        <select
                            name="cinema"
                            id="cinema"
                            required
                        >

                            <option value="">

                                Choose a cinema

                            </option>


                            <option value="SM Manila">

                                SM Manila

                            </option>


                            <option value="Robinsons Manila">

                                Robinsons Manila

                            </option>


                            <option value="Ayala Malls Manila Bay">

                                Ayala Malls Manila Bay

                            </option>

                        </select>


                    </div>



                    <!-- =================================================
                         DATE
                    ================================================== -->

                    <div class="form-group">


                        <label for="date">

                            <i class="fa-regular fa-calendar"></i>

                            <span>
                                Select Date
                            </span>

                        </label>


                        <input
                            type="date"
                            name="date"
                            id="date"
                            required
                        >


                    </div>



                    <!-- =================================================
                         SHOWTIME
                    ================================================== -->

                    <div class="form-group">


                        <label for="showtime">

                            <i class="fa-regular fa-clock"></i>

                            <span>
                                Select Showtime
                            </span>

                        </label>


                        <select
                            name="showtime"
                            id="showtime"
                            required
                        >


                            <option value="">

                                Choose a showtime

                            </option>


                            <option value="10:00 AM">

                                10:00 AM

                            </option>


                            <option value="1:00 PM">

                                1:00 PM

                            </option>


                            <option value="4:00 PM">

                                4:00 PM

                            </option>


                            <option value="7:00 PM">

                                7:00 PM

                            </option>


                            <option value="10:00 PM">

                                10:00 PM

                            </option>


                        </select>


                    </div>



                    <!-- =================================================
                         NUMBER OF TICKETS
                    ================================================== -->

                    <div class="form-group">


                        <label for="tickets">

                            <i class="fa-solid fa-users"></i>

                            <span>
                                Number of Tickets
                            </span>

                        </label>


                        <select
                            name="tickets"
                            id="tickets"
                            required
                            onchange="calculateTotal()"
                        >


                            <option value="1">

                                1 Ticket

                            </option>


                            <option value="2">

                                2 Tickets

                            </option>


                            <option value="3">

                                3 Tickets

                            </option>


                            <option value="4">

                                4 Tickets

                            </option>


                            <option value="5">

                                5 Tickets

                            </option>


                            <option value="6">

                                6 Tickets

                            </option>


                        </select>


                    </div>



                    <!-- =================================================
                         TOTAL
                    ================================================== -->

                    <div class="booking-total-box">


                        <span>

                            Total Amount

                        </span>


                        <strong id="totalPrice">

                            ₱<?php
                            echo number_format(
                                $movie['price'],
                                0
                            );
                            ?>

                        </strong>


                    </div>



                    <!-- =================================================
                         CONTINUE BOOKING
                    ================================================== -->

                    <button
                        type="submit"
                        class="continue-booking-btn"
                    >

                        <i class="fa-solid fa-ticket"></i>

                        <span>
                            Continue Booking
                        </span>

                    </button>


                </form>


            </div>


        </div>


    </div>

</section>



<!-- =========================================================
     BOOKING PAGE CSS
========================================================= -->

<style>


/* =========================================================
   RESET
========================================================= */

.booking-page,
.booking-page * {

    box-sizing: border-box;

}



/* =========================================================
   MAIN PAGE
========================================================= */

.booking-page {

    width: 100%;

    min-height: calc(100vh - 80px);

    background: #0d0d0d;

    color: #ffffff;

    padding: 55px 20px 90px;

}



/* =========================================================
   CONTAINER
========================================================= */

.booking-container {

    width: 100%;

    max-width: 1200px;

    margin: 0 auto;

}



/* =========================================================
   PAGE HEADER
========================================================= */

.booking-page-header {

    text-align: center;

    margin-bottom: 45px;

}



.booking-label {

    color: #ffffff;

    font-size: 15px;

    font-weight: 500;

    margin-bottom: 14px;

}



.booking-label i {

    color: #e52f45;

    margin-right: 6px;

}



.booking-page-header h1 {

    margin: 0 0 12px;

    color: #ffffff;

    font-size: clamp(32px, 4vw, 48px);

    line-height: 1.2;

    font-weight: 600;

}



.booking-page-header p {

    margin: 0;

    color: #bdbdbd;

    font-size: 16px;

}



/* =========================================================
   MAIN LAYOUT
========================================================= */

.booking-layout {

    width: 100%;

    display: grid;

    grid-template-columns: 420px minmax(0, 1fr);

    gap: 55px;

    align-items: start;

}



/* =========================================================
   LEFT COLUMN
========================================================= */

.booking-movie-column {

    width: 100%;

    min-width: 0;

}



/* =========================================================
   MOVIE CARD
========================================================= */

.booking-movie-card {

    width: 100%;

    max-width: 420px;

    margin: 0 auto;

    overflow: hidden;

    background: #181818;

    border-radius: 16px;

    box-shadow:
        0 15px 40px rgba(0, 0, 0, 0.45);

}



/* =========================================================
   POSTER
========================================================= */

.booking-poster-wrapper {

    width: 100%;

    height: 590px;

    overflow: hidden;

}



.booking-poster-wrapper img {

    display: block;

    width: 100%;

    height: 100%;

    max-width: 100%;

    object-fit: cover;

}



/* =========================================================
   MOVIE INFO
========================================================= */

.booking-movie-info {

    width: 100%;

    padding: 25px;

}



.booking-movie-info h2 {

    margin: 0 0 18px;

    color: #ffffff;

    font-size: 24px;

    line-height: 1.3;

    font-weight: 600;

}



.movie-info-item {

    display: flex;

    align-items: center;

    gap: 10px;

    margin-bottom: 12px;

    color: #bdbdbd;

    font-size: 15px;

}



.movie-info-item i {

    width: 20px;

    color: #e52f45;

    text-align: center;

}



.movie-price {

    margin-top: 25px;

    color: #ffffff;

    font-size: 25px;

    font-weight: 700;

}



.movie-price small {

    margin-left: 5px;

    color: #999999;

    font-size: 13px;

    font-weight: 400;

}



/* =========================================================
   RIGHT COLUMN
========================================================= */

.booking-form-column {

    width: 100%;

    min-width: 0;

}



/* =========================================================
   FORM CARD
========================================================= */

#bookingForm {

    width: 100%;

    margin: 0;

    padding: 35px;

    background: #181818;

    border-radius: 16px;

    box-shadow:
        0 15px 40px rgba(0, 0, 0, 0.35);

}



/* =========================================================
   FORM GROUP
========================================================= */

.form-group {

    width: 100%;

    margin: 0 0 25px;

}



.form-group label {

    display: flex;

    align-items: center;

    gap: 8px;

    width: 100%;

    margin: 0 0 10px;

    padding: 0;

    color: #ffffff;

    font-size: 16px;

    line-height: 1.4;

    font-weight: 500;

}



.form-group label i {

    flex: 0 0 auto;

    color: #e52f45;

    width: 20px;

    text-align: center;

}



/* =========================================================
   INPUTS
========================================================= */

.form-group select,
.form-group input {

    display: block;

    width: 100%;

    height: 54px;

    margin: 0;

    padding: 0 16px;

    border: 1px solid #3a3a3a;

    border-radius: 8px;

    outline: none;

    background: #ffffff;

    color: #222222;

    font-family: 'Poppins', sans-serif;

    font-size: 16px;

}



.form-group select:focus,
.form-group input:focus {

    border-color: #e52f45;

    box-shadow:
        0 0 0 3px rgba(229, 47, 69, 0.15);

}



/* =========================================================
   TOTAL
========================================================= */

.booking-total-box {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin: 5px 0 25px;

    padding: 18px 0;

    border-top: 1px solid #333333;

    border-bottom: 1px solid #333333;

}



.booking-total-box span {

    color: #dddddd;

    font-size: 17px;

}



.booking-total-box strong {

    color: #ffffff;

    font-size: 24px;

    font-weight: 700;

}



/* =========================================================
   BUTTON
========================================================= */

.continue-booking-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    width: 100%;

    height: 55px;

    margin: 0;

    padding: 0 20px;

    border: none;

    border-radius: 8px;

    background: #e52f45;

    color: #ffffff;

    font-family: 'Poppins', sans-serif;

    font-size: 17px;

    font-weight: 500;

    cursor: pointer;

    transition: 0.25s ease;

}



.continue-booking-btn:hover {

    background: #c9253a;

    transform: translateY(-1px);

}



.continue-booking-btn i {

    font-size: 16px;

}



/* =========================================================
   TABLET
========================================================= */

@media (max-width: 991px) {

    .booking-layout {

        grid-template-columns: 1fr;

        gap: 35px;

    }


    .booking-movie-card {

        max-width: 500px;

    }


    .booking-poster-wrapper {

        height: 650px;

    }


    .booking-form-column {

        width: 100%;

        max-width: 700px;

        margin: 0 auto;

    }

}



/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 576px) {

    .booking-page {

        padding: 35px 15px 60px;

    }


    .booking-page-header {

        margin-bottom: 30px;

    }


    .booking-page-header h1 {

        font-size: 30px;

    }


    .booking-page-header p {

        font-size: 14px;

    }


    .booking-poster-wrapper {

        height: auto;

        aspect-ratio: 2 / 3;

    }


    #bookingForm {

        padding: 22px;

        border-radius: 12px;

    }


    .booking-movie-info {

        padding: 20px;

    }


    .booking-movie-info h2 {

        font-size: 21px;

    }


    .form-group {

        margin-bottom: 20px;

    }


    .form-group label {

        font-size: 14px;

    }


    .form-group select,
    .form-group input {

        height: 50px;

        font-size: 14px;

    }


    .booking-total-box strong {

        font-size: 20px;

    }


    .continue-booking-btn {

        height: 52px;

        font-size: 15px;

    }

}

</style>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   CALCULATE TOTAL
========================================================= */

function calculateTotal() {

    const ticketPrice =
        <?php echo (float) $movie['price']; ?>;


    const ticketsElement =
        document.getElementById("tickets");


    const totalElement =
        document.getElementById("totalPrice");


    const tickets =
        parseInt(ticketsElement.value) || 1;


    const total =
        ticketPrice * tickets;


    totalElement.textContent =
        "₱" + total.toLocaleString("en-PH");

}



/* =========================================================
   MINIMUM DATE = TODAY
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const dateInput =
            document.getElementById("date");


        if (dateInput) {

            const today =
                new Date();


            const year =
                today.getFullYear();


            const month =
                String(
                    today.getMonth() + 1
                ).padStart(2, "0");


            const day =
                String(
                    today.getDate()
                ).padStart(2, "0");


            dateInput.min =
                `${year}-${month}-${day}`;

        }


        calculateTotal();

    }
);

</script>


<?php include "includes/footer.php"; ?>