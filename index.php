<?php

require_once "includes/db.php";

// =====================================================
// NOW SHOWING
// =====================================================

$sql = "SELECT * FROM movies WHERE status = 'now_showing' ORDER BY id ASC";
$result = $conn->query($sql);


// =====================================================
// COMING SOON
// =====================================================

$sql_coming = "SELECT * FROM movies WHERE status = 'coming_soon' ORDER BY id ASC";
$result_coming = $conn->query($sql_coming);

?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>


<!-- =====================================================
     HERO SECTION
===================================================== -->

<section class="hero-section">

    <div class="container text-center">

        <p class="hero-subtitle">
            <i class="fa-solid fa-ticket"></i>
            YOUR MOVIE, YOUR SEAT
        </p>

        <h1 class="hero-title">
            Book Your Movie Tickets Online
        </h1>

        <p class="hero-description">
            Reserve your favorite movie and choose your seat anytime, anywhere.
        </p>

        <a href="movies.php" class="btn btn-danger btn-lg hero-button">
            <i class="fa-solid fa-ticket"></i>
            Browse Movies
        </a>

    </div>

</section>



<!-- =====================================================
     NOW SHOWING
===================================================== -->

<section class="movies-section">

    <div class="container">

        <div class="section-heading">

            <div>

                <p class="section-subtitle">
                    WHAT'S PLAYING
                </p>

                <h2>
                    Now Showing
                </h2>

            </div>


            <a href="movies.php" class="view-all">

                View All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>



        <div class="row g-4">

            <?php if ($result && $result->num_rows > 0): ?>

                <?php while ($movie = $result->fetch_assoc()): ?>

                    <div class="col-lg-3 col-md-6">

                        <div class="movie-card">

                            <!-- MOVIE POSTER -->

                            <div class="movie-poster-wrapper">

                                <img
                                    src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                                    alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                    class="movie-poster"
                                >


                                <!-- RATING -->

                                <div class="movie-rating">

                                    <i class="fa-solid fa-star"></i>

                                    <?php echo htmlspecialchars($movie['rating']); ?>

                                </div>

                            </div>



                            <!-- MOVIE INFORMATION -->

                            <div class="movie-info">

                                <h3>
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </h3>


                                <p class="movie-genre">

                                    <i class="fa-solid fa-film"></i>

                                    <?php echo htmlspecialchars($movie['genre']); ?>

                                </p>


                                <div class="movie-bottom">

                                    <strong>
                                        ₱<?php echo number_format($movie['price'], 0); ?>
                                    </strong>


                                    <a
                                        href="movie-details.php?id=<?php echo $movie['id']; ?>"
                                        class="btn btn-danger movie-book-button"
                                    >
                                        Book Now
                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="col-12">

                    <div class="empty-movies">

                        <i class="fa-solid fa-film"></i>

                        <p>
                            No movies are currently showing.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>



<!-- =====================================================
     COMING SOON
===================================================== -->

<section class="movies-section coming-soon-section">

    <div class="container">


        <div class="section-heading">

            <div>

                <p class="section-subtitle">
                    UPCOMING MOVIES
                </p>

                <h2>
                    Coming Soon
                </h2>

            </div>


            <a href="movies.php" class="view-all">

                View All

                <i class="fa-solid fa-arrow-right"></i>

            </a>

        </div>



        <div class="row g-4">

            <?php if ($result_coming && $result_coming->num_rows > 0): ?>

                <?php while ($movie = $result_coming->fetch_assoc()): ?>

                    <div class="col-lg-3 col-md-6">

                        <div class="movie-card">

                            <!-- MOVIE POSTER -->

                            <div class="movie-poster-wrapper">

                                <img
                                    src="assets/posters/<?php echo htmlspecialchars($movie['poster']); ?>"
                                    alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                    class="movie-poster"
                                >

                            </div>



                            <!-- MOVIE INFORMATION -->

                            <div class="movie-info">

                                <h3>
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </h3>


                                <p class="movie-genre">

                                    <i class="fa-solid fa-film"></i>

                                    <?php echo htmlspecialchars($movie['genre']); ?>

                                </p>


                                <p class="release-date">

                                    <i class="fa-regular fa-calendar"></i>

                                    Coming Soon

                                </p>

                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>


            <?php else: ?>

                <div class="col-12">

                    <div class="empty-movies">

                        <i class="fa-solid fa-calendar-xmark"></i>

                        <p>
                            No upcoming movies.
                        </p>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<?php include "includes/footer.php"; ?>