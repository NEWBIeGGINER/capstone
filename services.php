<?php
require_once 'components/connect.php';
require_once 'components/auth.php'; // For $user_id session

// === FETCH SERVICES (active only) ===
$select_services = $conn->prepare("SELECT * FROM `services` WHERE status = 'active' ORDER BY id DESC");
$select_services->execute();
$services = $select_services->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Petcare | Services</title>
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/services.css?v=<?= time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'components/user_header.php'; ?>

    <!-- Service Header -->
    <div class="service-header">
      <h1><i class="fas fa-paw"></i> Premium PetCare Services</h1>
      <p>Book grooming, check-ups, and vaccinations for your pets with ease 🐾</p>
    </div> 

    <section class="services-section">
      <div class="services-container">
          <?php if(count($services) > 0): ?>
              <?php foreach($services as $service): ?>
                  <div class="service-card">
                      <div class="card-image">
                          <img src="uploaded_files/<?= htmlspecialchars($service['image']); ?>" alt="<?= htmlspecialchars($service['name']); ?>">
                      </div>
                      <div class="card-content">
                          <h3><?= htmlspecialchars($service['name']); ?></h3>
                          <p><?= htmlspecialchars($service['service_detail']); ?></p>
                          <a href="appointment.php?service_id=<?= $service['id']; ?>" class="btn">
                              <i class="fas fa-calendar-plus"></i> Book Appointment
                          </a>
                      </div>
                  </div>
              <?php endforeach; ?>
          <?php else: ?>
              <p>No active services available.</p>
          <?php endif; ?>
      </div>
    </section>

    <script src="assets/js/script.js"></script>
</body>
</html>

