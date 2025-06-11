<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'CosmicHub') ?></title>
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="/">CosmicHub</a>
            </div>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/reports">My Reports</a></li>
                    <li><a href="/profile">Profile</a></li>
                    <li><a href="/logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login">Login</a></li>
                    <li><a href="/register" class="btn">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> CosmicHub. All rights reserved.</p>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
</body>
</html>
