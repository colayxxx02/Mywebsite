<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'member') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_borrowed = $conn->query("
    SELECT COUNT(*) as c 
    FROM transactions 
    WHERE user_id = $user_id AND return_date IS NULL
")->fetch_assoc()['c'];

$overdue_count = $conn->query("
    SELECT COUNT(*) as c 
    FROM transactions 
    WHERE user_id = $user_id AND due_date < CURDATE() AND return_date IS NULL
")->fetch_assoc()['c'];

$current_bookings = $conn->query("
    SELECT b.title, b.author, t.due_date, t.transaction_id
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    WHERE t.user_id = $user_id AND t.return_date IS NULL
    ORDER BY t.borrow_date DESC
")->fetch_all(MYSQLI_ASSOC);

// GET MOST BORROWED BOOKS (All time)
$most_borrowed = $conn->query("
    SELECT b.book_id, b.title, b.author, b.category, COUNT(t.transaction_id) as borrow_count
    FROM books b
    LEFT JOIN transactions t ON b.book_id = t.book_id
    GROUP BY b.book_id
    ORDER BY borrow_count DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Dashboard - BookShare</title>
    <link rel="stylesheet" href="../member/style.css">
</head>
<body class="dashboard-page">

    <!-- TITLEBAR -->
    <div class="titlebar">
        <div class="dots">
            <span class="dot-red"></span>
            <span class="dot-yellow"></span>
            <span class="dot-green"></span>
        </div>
        <span class="title">BookShare Library System</span>
    </div>

    <!-- APP SHELL -->
    <div class="app-shell">

        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="logo">📚 BookShare</div>
            <nav>
                <a href="dashboard.php" class="nav-link active">
                    <span class="nav-icon">📊</span> Dashboard
                </a>
                <a href="catalog.php" class="nav-link">
                    <span class="nav-icon">📖</span> Browse Books
                </a>
                <a href="history.php" class="nav-link">
                    <span class="nav-icon">📜</span> My History
                </a>
                <a href="profile.php" class="nav-link">
                    <span class="nav-icon">👤</span> My Profile
                </a>
            </nav>
            <div class="sidebar-footer">
                <form method="POST" action="../logout.php">
                    <button type="submit">🚪 Logout</button>
                </form>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">

            <!-- WELCOME -->
            <div class="welcome-bar">
                <h2>Welcome, <?= $_SESSION['fullname'] ?>!</h2>
                <p>Here's your library overview for today.</p>
            </div>

            <!-- STAT CARDS -->
            <div class="dash-stat-grid">
                <div class="dash-stat-card">
                    <div class="stat-icon">📖</div>
                    <div>
                        <div class="stat-number"><?= $current_borrowed ?></div>
                        <div class="stat-label">Currently Borrowed</div>
                    </div>
                </div>
                <div class="dash-stat-card <?= $overdue_count > 0 ? 'overdue-card' : '' ?>">
                    <div class="stat-icon <?= $overdue_count > 0 ? 'overdue-icon' : '' ?>">⚠️</div>
                    <div>
                        <div class="stat-number <?= $overdue_count > 0 ? 'overdue-number' : '' ?>">
                            <?= $overdue_count ?>
                        </div>
                        <div class="stat-label">Overdue</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">📚</div>
                    <div>
                        <div class="stat-number">0</div>
                        <div class="stat-label">Reservations</div>
                    </div>
                </div>
            </div>

            <!-- QUICK NAV -->
            <div class="dash-section-title">Quick Navigation</div>
            <div class="quick-grid">
                <a href="catalog.php" class="quick-card">
                    <span class="quick-icon">📚</span>
                    <div class="quick-label">Browse Catalog</div>
                </a>
                <a href="catalog.php" class="quick-card">
                    <span class="quick-icon">📖</span>
                    <div class="quick-label">My Current Books</div>
                </a>
                <a href="history.php" class="quick-card">
                    <span class="quick-icon">📋</span>
                    <div class="quick-label">Borrowing History</div>
                </a>
                <a href="profile.php" class="quick-card">
                    <span class="quick-icon">👤</span>
                    <div class="quick-label">My Profile</div>
                </a>
            </div>

            <!-- MOST BORROWED BOOKS SLIDESHOW -->
            <?php if (!empty($most_borrowed)): ?>
                <div class="dash-section-title">⭐ Most Borrowed Books</div>
                <div class="slideshow-container">
                    <div class="slides-wrapper">
                        <?php foreach ($most_borrowed as $index => $book): ?>
                            <div class="slide fade" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                                <div class="slide-content">
                                    <div class="slide-book-icon">📕</div>
                                    <div class="slide-book-info">
                                        <h3 class="slide-title"><?= htmlspecialchars($book['title']) ?></h3>
                                        <p class="slide-author">by <?= htmlspecialchars($book['author']) ?></p>
                                        <p class="slide-category">Category: <?= htmlspecialchars($book['category']) ?></p>
                                        <p class="slide-stats">Borrowed <strong><?= $book['borrow_count'] ?></strong> times</p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Slide Controls -->
                    <div class="slide-controls">
                        <button class="slide-btn slide-prev" onclick="changeSlide(-1)">❮</button>
                        <div class="slide-dots">
                            <?php foreach ($most_borrowed as $index => $book): ?>
                                <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $index ?>)"></span>
                            <?php endforeach; ?>
                        </div>
                        <button class="slide-btn slide-next" onclick="changeSlide(1)">❯</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CURRENT BOOKS -->
            <div class="dash-section-title">
                📚 Currently Borrowed Books
                <?php if ($current_borrowed > 0): ?>
                    <span class="section-badge"><?= $current_borrowed ?> book<?= $current_borrowed > 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($current_bookings)): ?>
                <div class="no-data">
                    <span class="no-data-icon">📭</span>
                    <div class="no-data-text">No books borrowed right now.</div>
                    <a href="catalog.php" class="no-data-action">Browse Catalog →</a>
                </div>
            <?php else: ?>
                <div class="books-grid">
                    <?php foreach ($current_bookings as $booking): ?>
                        <div class="book-card current-book">
                            <div class="book-icon">📖</div>
                            <div class="book-info">
                                <div class="book-title"><?= htmlspecialchars($booking['title']) ?></div>
                                <div class="book-author">by <?= htmlspecialchars($booking['author']) ?></div>
                            </div>
                            <div class="book-due">
                                <div class="due-date">Due: <?= date('M j', strtotime($booking['due_date'])) ?></div>
                                <a href="return.php?id=<?= $booking['transaction_id'] ?>" class="return-btn">Return</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- SLIDESHOW JAVASCRIPT -->
    <script>
        let slideIndex = 0;
        let slideTimer;

        function showSlides() {
            const slides = document.querySelectorAll('.slide');
            if (slides.length === 0) return;

            slideIndex = (slideIndex) % slides.length;
            if (slideIndex < 0) slideIndex = slides.length - 1;

            // Hide all slides
            slides.forEach(slide => slide.style.display = 'none');

            // Show current slide
            slides[slideIndex].style.display = 'block';

            // Update dots
            const dots = document.querySelectorAll('.dot');
            dots.forEach(dot => dot.classList.remove('active'));
            dots[slideIndex].classList.add('active');
        }

        function changeSlide(n) {
            clearTimeout(slideTimer);
            slideIndex += n;
            showSlides();
            autoSlide();
        }

        function currentSlide(n) {
            clearTimeout(slideTimer);
            slideIndex = n;
            showSlides();
            autoSlide();
        }

        function autoSlide() {
            slideTimer = setTimeout(() => {
                slideIndex++;
                showSlides();
                autoSlide();
            }, 5000); // Change slide every 5 seconds
        }

        // Initialize slideshow
        document.addEventListener('DOMContentLoaded', () => {
            showSlides();
            autoSlide();
        });
    </script>
</body>
</html>