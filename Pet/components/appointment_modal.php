<!-- ✅ Appointment Modal -->
<div id="timeSlotModal" class="modal" style="display:none;">
  <div class="modal-content">
    <!-- Close -->
    <span class="close">&times;</span>

    <!-- Header -->
    <h3>🐾 Book Your Appointment</h3>

    <!-- Form -->
    <form id="modalForm">
      <input type="hidden" name="date" id="modal_date">
      <input type="hidden" name="time_slot" id="selectedSlotInput">

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Left: Service Preview + Selector -->
        <div class="modal-left">
          <div id="selectedServiceBox">
            <img id="selectedServiceImg" src="" alt="Service Image" style="display:none;">
          </div>
          <div class="service-selector">
            <label for="serviceSelect">Choose Service</label>
            <select id="serviceSelect" name="service_id" required></select>
          </div>
        </div>

        <!-- Right: Time Slots -->
        <div class="modal-right">
          <label>Available Time Slots</label>
          <div id="timeSlotsContainer" class="time-slots"></div>
          <p id="selectedSlotDisplay" class="selected-slot">No time slot selected</p>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="confirm-btn">Confirm Appointment</button>
      </div>
    </form>
  </div>
</div>


<style>
/* Modal overlay */
.modal {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

/* Modal content */
.modal-content {
  background: #fff;
  border-radius: 16px;
  width: 800px;
  max-width: 95%;
  display: flex;
  flex-direction: column;
  padding: 25px;
  position: relative;
  animation: fadeInUp 0.3s ease;
  box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

/* Close button */
.close {
  position: absolute;
  top: 15px;
  right: 15px;
  font-size: 24px;
  cursor: pointer;
  color: #888;
}
.close:hover { color:#000; }

/* Title */
.modal-content h3 {
  text-align: center;
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 15px;
}

/* Modal Body */
.modal-body {
  display: flex;
  gap: 25px;
}

/* Left & Right */
.modal-left, .modal-right { flex:1; display:flex; flex-direction:column; gap:10px; }
.modal-left { max-width: 300px; }

/* Selected Service Box - image only, big */
#selectedServiceBox {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  padding: 10px;
  background: linear-gradient(135deg, #f5f5f5 0%, #f5f5f5 100%);
  margin-bottom: 10px;
  height: 300px; /* fixed height */
}
#selectedServiceBox img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 12px;
  border: 3px solid rgba(0,0,0,0.1);
}
/* Service Selector */
.service-selector { display:flex; flex-direction:column; gap:8px; }
.service-selector select {
  padding: 10px;
  border-radius: 8px;
  border:1px solid #ddd;
  font-size: 14px;
}

/* Time slots */
#timeSlotsContainer {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px,1fr));
  gap: 12px;
  max-height: 350px;
  overflow-y:auto;
}
.slot {
  padding: 12px;
  border:1px solid #ddd;
  border-radius:8px;
  text-align:center;
  cursor:pointer;
  font-weight:600;
  transition: all 0.2s;
  background:#f9f9f9;
}
.slot.available:hover { background:#ecf0ff; border-color:#667eea; }
.slot.available.selected { background:#667eea; color:#fff; border-color:#764ba2; }

/* Selected slot info */
.selected-slot {
  margin-top: 12px;
  padding: 8px;
  border-radius: 6px;
  background:#e8f5e9;
  font-weight:600;
  color:#27ae60;
  text-align:center;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top:20px;
}
.modal-footer button {
  padding:10px 22px;
  border:none;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
  transition:0.2s;
}
.cancel { background:#bbb; color:#fff; }
.cancel:hover { background:#999; }
.confirm-btn { background:#667eea; color:#fff; }
.confirm-btn:hover { background:#764ba2; }

/* Animation */
@keyframes fadeInUp { from {opacity:0; transform: translateY(30px);} to {opacity:1; transform: translateY(0);} }

/* Responsive */
@media (max-width:768px) {
  .modal-body { flex-direction: column; }
  .modal-left { max-width:100%; }
  #timeSlotsContainer { max-height:250px; }
  .modal-footer { flex-direction: column-reverse; }
  .modal-footer button { width:100%; }
}
</style>


<script>

// Select slot
function selectSlot(el,time){
  document.querySelectorAll('.slot.available').forEach(s=>s.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('selectedSlotInput').value=time;
  document.getElementById('selectedSlotDisplay').textContent=`Selected: ${time}`;
}

// Update service
function updateService(){
  const sel=document.getElementById('serviceSelect');
  const id=sel.value;
  if(id && services[id]){
    document.getElementById('selectedServiceName').textContent=services[id].name;
    document.getElementById('selectedServiceImg').src=services[id].img;
  }
}

// Open/Close
function openModal(){
  document.getElementById('timeSlotModal').style.display='flex';
  generateTimeSlots();
}
function closeModal(){
  document.getElementById('timeSlotModal').style.display='none';
  document.getElementById('modalForm').reset();
  document.getElementById('selectedServiceName').textContent='Select a service';
  document.getElementById('selectedSlotDisplay').textContent='No time slot selected';
}


// Close outside click
document.getElementById('timeSlotModal').addEventListener('click',e=>{
  if(e.target===e.currentTarget) closeModal();
});
</script>
