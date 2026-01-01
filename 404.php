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

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0 error-container">
                        <div class="card-body text-center p-4 p-md-5">
                            <span class="badge error-badge mb-4">404 Error</span>
                            <!-- Add src for gif -->
                            <div class="mb-4">
                                <img
                                    alt="404 Error Animation"
                                    class="img-fluid error-gif rounded"
                                    style="max-width: 220px;" />
                            </div>

                            <h1 class="error-title display-5 fw-bold mb-3">Page Not Found</h1>

                            <p class="error-text text-muted mb-4 px-3">
                                We couldn't find the page you're looking for. It might have been moved or deleted.
                            </p>

                            <a href="<?php echo get_home_url() . '/BookStack/'; ?>"
                                class="btn btn-green btn-lg d-inline-flex align-items-center gap-2 px-4 py-3">
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