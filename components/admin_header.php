<div class="admin-sidebar" id="sidebar">
    <a href="dashboard.php" class="sidebar-header">
        <i class="fas fa-paw fa-2x" style="color: #fff;"></i>
        <h2>Administrator</h2>
    </a>

<nav>
    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
    </a>

    <a href="manage_service.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_service.php' ? 'active' : '' ?>">
        <i class="fas fa-stethoscope"></i>
        <span>Manage Service</span>
    </a>

    <a href="manage_products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : '' ?>">
        <i class="fas fa-bone"></i>
        <span>Manage Products</span>
    </a>

    <a href="manage_promotion.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_promotion.php' ? 'active' : '' ?>">
        <i class="fas fa-bullhorn"></i>
        <span>Promotion</span>
    </a>

    <a href="manage_orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-basket"></i>
        <span>Manage Orders</span>
    </a>

    <a href="manage_post.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_post.php' ? 'active' : '' ?>">
        <i class="fas fa-newspaper"></i>
        <span>Manage Posts</span>
    </a>

    <a href="manage_users.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
        <i class="fas fa-user-friends"></i>
        <span>Manage Users</span>
    </a>

    <!-- 🔹 Appointment Management -->
    <a href="manage_appointments.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i>
        <span>Manage Appointments</span>
    </a>

    <a href="manage_schedule.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_schedule.php' ? 'active' : '' ?>">
        <i class="fas fa-clock"></i>
        <span>Manage Schedule</span>
    </a>

    <a href="manage_holidays.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_holidays.php' ? 'active' : '' ?>">
        <i class="fas fa-umbrella-beach"></i>
        <span>Manage Holidays</span>
    </a>

    <!-- 🔹 Archive for Completed Appointments -->
    <a href="archive.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'archive_appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-archive"></i>
        <span>Archive</span>
    </a>

    <a href="../components/admin_logout.php" class="nav-link" onclick="return confirm('Logout from this website?');">
        <i class="fas fa-sign-out-alt"></i>
        <span>Log Out</span>
    </a>
</nav>

</div>
