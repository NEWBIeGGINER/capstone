<?php 
require_once '../components/connect.php';
require_once '../components/auth_admin.php';

if (!$is_admin_logged_in) {
    header("Location: ../signin.php");
    exit;
}

// === TOTAL COUNTS ===
$total_services     = (int) $conn->query("SELECT COUNT(*) FROM services")->fetchColumn();
$total_products     = (int) $conn->query("SELECT COUNT(*) FROM product")->fetchColumn();
$total_promotions   = (int) $conn->query("SELECT COUNT(*) FROM promotion")->fetchColumn();
$total_orders       = (int) $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_posts        = (int) $conn->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
$total_users        = (int) $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_appointments = (int) $conn->query("SELECT COUNT(*) FROM appointments")->fetchColumn();

// === ORDERS PER MONTH ===
$orders_per_month = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total
    FROM orders
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

$months = array_column($orders_per_month, 'month');
$order_totals = array_map('intval', array_column($orders_per_month, 'total'));

// === REVENUE PER MONTH ===
$revenue_per_month = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue
    FROM orders
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

$rev_months = array_column($revenue_per_month, 'month');
$revenues   = array_map('floatval', array_column($revenue_per_month, 'revenue'));

// === TOP SERVICES ===
$top_services = $conn->query("
    SELECT s.name, COUNT(a.id) as total
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    GROUP BY s.name
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$service_names  = array_column($top_services, 'name');
$service_totals = array_map('intval', array_column($top_services, 'total'));

// === RECENT ORDERS (joined with users for display) ===
$recent_orders = $conn->query("
    SELECT o.*, u.name AS user_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// === RECENT APPOINTMENTS ===
$recent_appointments = $conn->query("
    SELECT a.*, s.name AS service_name, u.name AS user_name 
    FROM appointments a
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
 <link rel="stylesheet" href="./../assets/css/admin/dashboard.css?v=<?php echo time(); ?>">
 <link rel="stylesheet" href="./../assets/css/admin/admin_header.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="sidebar-overlay" id="overlay"></div>

<div class="admin-container">
  <?php include '../components/admin_header.php' ?>

  <div class="admin-main">
<!-- Dashboard Header -->
<div class="header-row">
  <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
  <h1>📊 Dashboard</h1>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
  <a href="manage_service.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_services ?></span>
      <span class="stat-label">Services</span>
    </div>
  </a>

  <a href="manage_products.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-box"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_products ?></span>
      <span class="stat-label">Products</span>
    </div>
  </a>

  <a href="manage_promotion.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-tags"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_promotions ?></span>
      <span class="stat-label">Promotions</span>
    </div>
  </a>

  <a href="manage_orders.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_orders ?></span>
      <span class="stat-label">Orders</span>
    </div>
  </a>

  <a href="manage_post.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_posts ?></span>
      <span class="stat-label">Posts</span>
    </div>
  </a>

  <a href="manage_users.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-users"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_users ?></span>
      <span class="stat-label">Users</span>
    </div>
  </a>

  <a href="manage_appointments.php" class="stat-card">
    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
    <div class="stat-info">
      <span class="stat-value"><?= $total_appointments ?></span>
      <span class="stat-label">Appointments</span>
    </div>
  </a>
</div>

<!-- Recent Orders -->
<div class="card">
  <h2>🆕 Recent Orders</h2>
  <table class="styled-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Total</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($recent_orders): ?>
        <?php foreach ($recent_orders as $order): ?>
          <tr>
            <td>#<?= $order['id'] ?></td>
            <td><?= htmlspecialchars($order['user_id']) ?></td>
            <td>₱<?= number_format($order['total'], 2) ?></td>
            <td><span class="status <?= strtolower($order['status']) ?>"><?= ucfirst($order['status']) ?></span></td>
            <td><?= $order['created_at'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No recent orders.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Recent Appointments -->
<div class="card">
  <h2>📅 Recent Appointments</h2>
  <table class="styled-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Service</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($recent_appointments): ?>
        <?php foreach ($recent_appointments as $appt): ?>
          <tr>
            <td>#<?= $appt['id'] ?></td>
            <td><?= htmlspecialchars($appt['user_name'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($appt['service_name'] ?? 'N/A') ?></td>
            <td><span class="status <?= strtolower($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
            <td><?= $appt['created_at'] ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No recent appointments.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Charts Section -->
<div class="charts-grid">
  <div class="chart-card">
    <h2>📦 Orders Per Month</h2>
    <canvas id="ordersChart"></canvas>
  </div>

  <div class="chart-card">
    <h2>🐾 Top Services</h2>
    <canvas id="servicesChart"></canvas>
  </div>

  <div class="chart-card full-width">
    <h2>💰 Sales Revenue (₱) per Month</h2>
    <canvas id="revenueChart"></canvas>
  </div>
</div>



  <script src="./../assets/js/dashboard.js"></script>
  <script>
    // Orders per month chart
    new Chart(document.getElementById('ordersChart'), {
      type: 'line',
      data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
          label: 'Orders',
          data: <?= json_encode($order_totals) ?>,
          borderColor: 'rgba(75, 192, 192, 1)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          fill: true,
          tension: 0.3
        }]
      },
      options: { responsive: true }
    });

    // Top services chart
    new Chart(document.getElementById('servicesChart'), {
      type: 'pie',
      data: {
        labels: <?= json_encode($service_names) ?>,
        datasets: [{
          data: <?= json_encode($service_totals) ?>,
          backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff']
        }]
      },
      options: { responsive: true }
    });

    // Revenue chart
    new Chart(document.getElementById('revenueChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($rev_months) ?>,
        datasets: [{
          label: 'Revenue (₱)',
          data: <?= json_encode($revenues) ?>,
          backgroundColor: 'rgba(75, 192, 192, 0.7)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true, position: 'top' }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return "₱" + value.toLocaleString();
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>
