<?php
http_response_code(404);

function get_home_url()
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Page Not Found — BookStack</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100 mx-2">
            <div class="col-12 col-md-6">
                <div class="error-container">
                    <div class="text-center">
                        <div class="mb-5">
                            <video
                                autoplay
                                loop
                                muted
                                class="error-gif"
                                style="max-width: 280px; width: 100%;">
                                <source src="assets/404.mp4" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>

                        <h1 class="error-title mb-3">Oops! Page Not Found</h1>

                        <p class="error-text mb-5">
                            The page you're looking for might have been moved, deleted, or never existed.
                        </p>

                        <a href="<?php echo get_home_url() . '/BookStack/'; ?>"
                            class="btn btn-green btn-lg d-inline-flex align-items-center gap-2 px-5 py-2 error-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Return to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>