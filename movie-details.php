<?php

/* =========================================================
   START SESSION
========================================================= */

session_start();


/* =========================================================
   DATABASE CONNECTION
========================================================= */

require_once "includes/db.php";


/* =========================================================
   GET MOVIE ID
========================================================= */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$movie_id = (int) $_GET['id'];


/* =========================================================
   GET MOVIE INFORMATION
========================================================= */

$sql = "SELECT * FROM movies WHERE id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $movie_id);

$stmt->execute();

$result = $stmt->get_result();

$movie = $result->fetch_assoc();

$stmt->close();


/* =========================================================
   MOVIE NOT FOUND
========================================================= */

if (!$movie) {
    header("Location: index.php");
    exit;
}

?>


<?php include "includes/header.php"; ?>

<?php include "includes/navbar.php"; ?>


<!-- ========================================================
     MOVIE DETAILS
========================================================= -->

<section class="movie-details-section">

    <div class="container">

        <div class="row align-items-center g-5">


            <!-- =================================================
                 POSTER
            ================================================= -->

            <div class="col-lg-4">

                <div class="movie-details-poster">

                    <img
                        src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                        alt="<?php echo htmlspecialchars($movie['title']); ?>"
                    >

                </div>

            </div>



            <!-- =================================================
                 MOVIE INFORMATION
            ================================================= -->

            <div class="col-lg-8">


                <!-- MOVIE DETAILS LABEL -->

                <p class="section-subtitle">

                    MOVIE DETAILS

                </p>



                <!-- MOVIE TITLE -->

                <h1 class="movie-details-title">

                    <?php echo htmlspecialchars($movie['title']); ?>

                </h1>



                <!-- RATING -->

                <div class="movie-details-rating">

                    <i class="fa-solid fa-star"></i>

                    <?php echo htmlspecialchars($movie['rating']); ?>

                </div>



                <!-- GENRE -->

                <p class="movie-details-info">

                    <i class="fa-solid fa-film"></i>

                    <?php echo htmlspecialchars($movie['genre']); ?>

                </p>



                <!-- DURATION -->

                <p class="movie-details-info">

                    <i class="fa-regular fa-clock"></i>

                    <?php echo htmlspecialchars($movie['duration']); ?>

                    minutes

                </p>



                <!-- DESCRIPTION -->

                <div class="movie-description">

                    <h3>

                        About the Movie

                    </h3>

                    <p>

                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $movie['description']
                            )
                        );
                        ?>

                    </p>

                </div>



                <!-- PRICE -->

                <div class="movie-details-price">

                    <span>

                        Ticket Price

                    </span>

                    <strong>

                        ₱<?php
                        echo number_format(
                            $movie['price'],
                            0
                        );
                        ?>

                    </strong>

                </div>



                <!-- =================================================
                     BOOK NOW / LOGIN
                ================================================= -->

                <?php if ($movie['status'] === 'now_showing'): ?>


                    <?php if (isset($_SESSION['user_id'])): ?>

                        <!-- =========================================
                             USER IS LOGGED IN
                        ========================================== -->

                        <a
                            href="booking.php?id=<?php echo $movie['id']; ?>"
                            class="btn btn-danger btn-lg"
                        >

                            <i class="fa-solid fa-ticket"></i>

                            Book Now

                        </a>


                    <?php else: ?>

                        <!-- =========================================
                             USER IS NOT LOGGED IN
                        ========================================== -->

                        <a
                            href="login.php?redirect=<?php echo urlencode('booking.php?id=' . $movie['id']); ?>"
                            class="btn btn-danger btn-lg"
                        >

                            <i class="fa-solid fa-right-to-bracket"></i>

                            Login to Book

                        </a>


                    <?php endif; ?>


                <?php else: ?>

                    <!-- =============================================
                         COMING SOON
                    ============================================== -->

                    <button
                        class="btn btn-secondary btn-lg"
                        disabled
                    >

                        <i class="fa-solid fa-clock"></i>

                        Coming Soon

                    </button>

                <?php endif; ?>



                <!-- =================================================
                     BACK BUTTON
                ================================================= -->

                <a
                    href="index.php"
                    class="btn btn-outline-light btn-lg ms-2"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Back

                </a>


            </div>

        </div>

    </div>

</section>


<?php include "includes/footer.php"; ?>