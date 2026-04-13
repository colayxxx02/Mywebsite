<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'librarian') {
    header("Location: ../login.php");
    exit();
}

$total_books   = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
$available     = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='available'")->fetch_assoc()['c'];
$borrowed      = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='borrowed'")->fetch_assoc()['c'];
$damaged       = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='damaged'")->fetch_assoc()['c'];
$overdue       = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE due_date < CURDATE() AND return_date IS NULL")->fetch_assoc()['c'];
$total_members = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='member'")->fetch_assoc()['c'];

$most_borrowed = $conn->query("
    SELECT b.title, b.author, b.category, COUNT(t.book_id) as borrow_count
    FROM transactions t
    JOIN books b ON t.book_id = b.book_id
    GROUP BY t.book_id
    ORDER BY borrow_count DESC
    LIMIT 5
");
$top_books = [];
while ($row = $most_borrowed->fetch_assoc()) {
    $top_books[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Librarian Dashboard - BookShare</title>
    <link rel="stylesheet" href="../librarian/style.css">
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
                <a href="dashboard.php" class="active">
                    <span class="nav-icon">🏠</span> Dashboard
                </a>
                <a href="books.php">
                    <span class="nav-icon">📚</span> Books
                </a>
                <a href="transactions.php">
                    <span class="nav-icon">🔄</span> Transactions
                </a>
                <a href="overdue.php">
                    <span class="nav-icon">⚠️</span> Overdue
                </a>
                <a href="maintenance.php">
                    <span class="nav-icon">🛠️</span> Maintenance
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
                    <div class="stat-icon">📚</div>
                    <div>
                        <div class="stat-number"><?= $total_books ?></div>
                        <div class="stat-label">Total Books</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">✅</div>
                    <div>
                        <div class="stat-number"><?= $available ?></div>
                        <div class="stat-label">Available</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">📖</div>
                    <div>
                        <div class="stat-number"><?= $borrowed ?></div>
                        <div class="stat-label">Borrowed</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">🛠️</div>
                    <div>
                        <div class="stat-number"><?= $damaged ?></div>
                        <div class="stat-label">Damaged</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div>
                        <div class="stat-number" style="color:#c62828;"><?= $overdue ?></div>
                        <div class="stat-label">Overdue</div>
                    </div>
                </div>
                <div class="dash-stat-card">
                    <div class="stat-icon">👥</div>
                    <div>
                        <div class="stat-number"><?= $total_members ?></div>
                        <div class="stat-label">Total Members</div>
                    </div>
                </div>
            </div>

            <!-- QUICK NAV -->
            <div class="dash-section-title">Quick Navigation</div>
            <div class="quick-grid">
                <a href="books.php" class="quick-card">
                    <span class="quick-icon">📚</span>
                    <div class="quick-label">Manage Books</div>
                </a>
                <a href="transactions.php" class="quick-card">
                    <span class="quick-icon">🔄</span>
                    <div class="quick-label">Transactions</div>
                </a>
                <a href="overdue.php" class="quick-card">
                    <span class="quick-icon">⚠️</span>
                    <div class="quick-label">Overdue Books</div>
                </a>
                <a href="maintenance.php" class="quick-card">
                    <span class="quick-icon">🛠️</span>
                    <div class="quick-label">Maintenance Log</div>
                </a>
            </div>

            <!-- MOST BORROWED SLIDESHOW -->
            <div class="dash-section-title">🏆 Most Borrowed Books</div>

            <?php if (empty($top_books)): ?>
                <div class="no-data">📭 No transaction data yet.</div>
            <?php else: ?>
            <div class="slideshow-wrapper">
                <button class="slide-arrow slide-prev" onclick="changeSlide(-1)">&#8249;</button>
                <button class="slide-arrow slide-next" onclick="changeSlide(1)">&#8250;</button>

                <div class="slideshow-track" id="slideshowTrack">
                    <?php
                    $book_icons = ['📗','📘','📙','📕','📔'];
                    foreach ($top_books as $i => $book):
                    ?>
                    <div class="slide">
                        <div class="slide-rank">#<?= $i + 1 ?></div>
                        <div class="slide-icon"><?= $book_icons[$i] ?></div>
                        <div class="slide-info">
                            <div class="slide-title"><?= htmlspecialchars($book['title']) ?></div>
                            <div class="slide-author">by <?= htmlspecialchars($book['author']) ?></div>
                            <span class="slide-category"><?= htmlspecialchars($book['category']) ?></span>
                        </div>
                        <div class="slide-count">
                            <div class="count-num"><?= $book['borrow_count'] ?></div>
                            <div class="count-label">times borrowed</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="slideshow-dots" id="slideshowDots">
                    <?php foreach ($top_books as $i => $book): ?>
                        <div class="dot <?= $i == 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        let currentSlide = 0;
        const total = <?= count($top_books) ?>;
        const track = document.getElementById('slideshowTrack');
        const dots  = document.querySelectorAll('.dot');

        function updateSlide() {
            track.style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach((d, i) => d.classList.toggle('active', i === currentSlide));
        }

        function changeSlide(dir) {
            currentSlide = (currentSlide + dir + total) % total;
            updateSlide();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateSlide();
        }

        setInterval(() => changeSlide(1), 3000);
    </script>

</body>
</html>