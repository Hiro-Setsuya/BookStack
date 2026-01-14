<div class="container mt-5 pt-5">
    <div class="row mb-4 align-items-end">
        <div class="col-md-7">
            <h2 class="fw-bold"><?php echo isset($page_title) ? $page_title : 'E-Books'; ?></h2>
            <p class="text-muted mb-md-0"><?php echo isset($page_description) ? $page_description : 'Browse our collection'; ?></p>
        </div>

        <div class="col-md-5"> <?php if (basename($_SERVER['PHP_SELF']) === 'ebooks.php'): ?>
                <form method="GET" action="ebooks.php" class="row g-2 align-items-stretch">
                    <div class="col-10">
                        <input class="form-control h-100" type="text" name="q" placeholder="Search books, authors..."
                            value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" />
                    </div>
                    <div class="col-2">
                        <button class="btn btn-green w-100 h-100" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <hr class="mb-4">