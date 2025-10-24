<?php
// Get the room name from the URL, or create a fallback room
$room_name = isset($_GET['room']) ? $_GET['room'] : "PetConsultation_" . uniqid();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Virtual Consultation</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f4f4f9;
    }
    header {
      background: #0e54ae;
      color: white;
      text-align: center;
      padding: 15px;
    }
    #jitsi-container {
      width: 100%;
      height: 90vh; /* take most of the screen */
    }
  </style>
</head>
<body>

<header>
  <h2>🐾 PetCare Virtual Consultation</h2>
</header>

<!-- Video Call Container -->
<div id="jitsi-container"></div>

<!-- Load Jitsi Meet API -->
<script src="https://meet.jit.si/external_api.js"></script>
<script>
  const domain = "meet.jit.si";
  const options = {
      roomName: "<?php echo $room_name; ?>", // room from URL or generated
      width: "100%",
      height: "100%",
      parentNode: document.querySelector('#jitsi-container'),
      interfaceConfigOverwrite: {
          TOOLBAR_BUTTONS: [
              'microphone', 'camera', 'desktop', 'chat', 'hangup'
          ]
      },
      configOverwrite: {
          startWithAudioMuted: false,
          startWithVideoMuted: false
      }
  };

  // Create Jitsi object
  const api = new JitsiMeetExternalAPI(domain, options);

  // Example: log when user joins
  api.addEventListener('videoConferenceJoined', () => {
      console.log("Joined consultation room: <?php echo $room_name; ?>");
  });
</script>

</body>
</html>
