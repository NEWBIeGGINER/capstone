document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const modal = document.getElementById('timeSlotModal');
    const modalDateInput = document.getElementById('modal_date');
    const slotsContainer = document.getElementById('timeSlotsContainer');
    const selectedSlotDisplay = document.getElementById('selectedSlotDisplay');
    const selectedSlotInput = document.getElementById('selectedSlotInput');
    const modalForm = document.getElementById('modalForm');
    const serviceSelect = document.getElementById('serviceSelect');
    const selectedServiceImg = document.getElementById('selectedServiceImg');

    // Base API URL
    const FETCH_APPOINTMENT_URL = window.FETCH_APPOINTMENT_URL || "components/fetch_appointments.php";

    console.log('API Base URL:', FETCH_APPOINTMENT_URL);
    console.log('Current User ID:', window.currentUserId);

    // Validate user setup
    if (!window.currentUserId) {
        console.warn('⚠️ window.currentUserId is not set. User may not be logged in.');
    }

    // Populate services
    if (window.servicesList && serviceSelect) {
        serviceSelect.innerHTML = '<option value="">Select Service</option>';
        window.servicesList.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            serviceSelect.appendChild(opt);
        });
    }

    // Show service image when selected
    serviceSelect.addEventListener('change', () => {
        const id = serviceSelect.value;
        if (id && window.servicesList) {
            const svc = window.servicesList.find(s => s.id == id);
            if (svc && svc.img) {
                selectedServiceImg.src = svc.img;
                selectedServiceImg.style.display = 'block';
            }
        } else {
            selectedServiceImg.src = '';
            selectedServiceImg.style.display = 'none';
        }
    });

    // ===== FullCalendar setup =====
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: window.innerWidth <= 480 ? 'auto' : 650, // ✅ auto height for mobile
        aspectRatio: 1.1, // keeps cells square-like
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: ''
        },
        events: window.calendarEvents,
        displayEventTime: false,
        validRange: { start: new Date() },

        // ✅ Responsive event rendering
        eventContent: function(arg) {
            const isMobile = window.innerWidth <= 480;
            const fontSize = isMobile ? '9px' : '12px';
            const padding = isMobile ? '1px 2px' : '2px 4px';
            const borderRadius = '5px';
            const text = arg.event.title;
            const slots = arg.event.extendedProps.slots;

            // 🟣 Holiday
            if (arg.event.extendedProps.isHoliday) {
                return {
                    html: `
                        <div style="
                            text-align:center;
                            font-weight:bold;
                            font-size:${fontSize};
                            background:#9b59b6;
                            border-radius:${borderRadius};
                            padding:${padding};
                            color:#fff;
                            line-height:1.2;
                        ">
                            ${text}
                        </div>`
                };
            }

            // 🔴 Fully Booked
            if (slots <= 0) {
                return {
                    html: `
                        <div style="
                            text-align:center;
                            font-weight:bold;
                            font-size:${fontSize};
                            background:#e74c3c;
                            color:#fff;
                            border-radius:${borderRadius};
                            padding:${padding};
                            line-height:1.2;
                        ">
                            Fully&nbsp;Book
                        </div>`
                };
            }

            // 🟢 Available
            return {
                html: `
                    <div style="
                        text-align:center;
                        font-weight:bold;
                        font-size:${fontSize};
                        line-height:1.2;
                    ">
                        <span class="slot-circle">${slots}</span>
                        Available
                    </div>`
            };
        },

        eventClick: function(info) {
            const ev = info.event;
            if (ev.extendedProps.isHoliday || ev.extendedProps.slots <= 0) {
                Swal.fire('Not available for booking!');
                return;
            }

            modal.style.display = 'flex';
            modalDateInput.value = ev.startStr.split("T")[0];
            selectedSlotDisplay.textContent = 'Selected: None';
            selectedSlotInput.value = '';
            slotsContainer.innerHTML = 'Loading slots...';
            selectedServiceImg.style.display = 'none';
            serviceSelect.value = '';

            loadSlots(modalDateInput.value);
        }
    });

    calendar.render();

    // ===== Modal Control =====
    document.querySelector('.close').addEventListener('click', () => modal.style.display = 'none');
    modal.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

    // ===== Load Slots =====
    async function loadSlots(date) {
        if (!date) return;
        slotsContainer.innerHTML = 'Loading...';

        try {
            const params = new URLSearchParams({
                action: 'slots',
                date: date,
                user_id: window.currentUserId
            });

            const url = `${FETCH_APPOINTMENT_URL}?${params}`;
            console.log('Fetching from:', url);

            const res = await fetch(url);
            if (!res.ok) throw new Error(`Server error: ${res.status}`);

            const text = await res.text();
            const data = JSON.parse(text);

            if (!data.allSlots || !Array.isArray(data.allSlots)) throw new Error('Malformed data');

            const slotsData = data.allSlots.map(slot => {
                let status = 'available';
                if (data.userBookedSlots?.includes(slot)) status = 'userBooked';
                else if (data.bookedSlots?.includes(slot)) status = 'booked';
                return { time: slot, status };
            });

            renderSlots(slotsData);
        } catch (err) {
            console.error('LoadSlots error:', err);
            slotsContainer.innerHTML = '';
            Swal.fire('Error', err.message || 'Failed to load time slots', 'error');
        }
    }

    // ===== Render Slots =====
    function renderSlots(slots) {
        slotsContainer.innerHTML = '';
        slots.forEach(slot => {
            const div = document.createElement('div');
            div.classList.add('slot');

            if (slot.status === 'booked') {
                div.classList.add('booked');
                div.textContent = slot.time + ' (Booked)';
                div.style.pointerEvents = 'none';
                div.style.opacity = 0.5;
            } else if (slot.status === 'userBooked') {
                div.classList.add('user-booked');
                div.textContent = slot.time + ' (Your Booking)';
                div.style.pointerEvents = 'none';
                div.style.opacity = 0.7;
            } else {
                div.classList.add('available');
                div.textContent = slot.time;
                div.addEventListener('click', () => {
                    slotsContainer.querySelectorAll('.slot').forEach(s => s.classList.remove('selected'));
                    div.classList.add('selected');
                    selectedSlotInput.value = slot.time;
                    selectedSlotDisplay.textContent = 'Selected: ' + slot.time;
                });
            }
            slotsContainer.appendChild(div);
        });
    }

    // ===== Submit Booking =====
    modalForm.addEventListener('submit', async e => {
        e.preventDefault();
        const dateVal = modalDateInput.value;
        const slotVal = selectedSlotInput.value;
        const serviceVal = serviceSelect.value || 0;

        if (!dateVal || !slotVal) {
            Swal.fire('Missing Data', 'Please select a date and time slot', 'warning');
            return;
        }

        const fd = new FormData();
        fd.set('action', 'book_appointment');
        fd.set('date', dateVal);
        fd.set('time_slot', slotVal);
        fd.set('service_id', serviceVal);

        try {
            const res = await fetch('book_appointment.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.success) return Swal.fire('Booking Failed', data.message, 'error');

            Swal.fire({
                icon: 'success',
                title: 'Appointment Confirmed',
                html: `Transaction No: <b>${data.transaction_no}</b>`,
                confirmButtonText: 'View Details'
            }).then(() => window.location.href = "transactions.php?transaction_no=" + encodeURIComponent(data.transaction_no));

            modal.style.display = 'none';
            calendar.refetchEvents();
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Could not reach server', 'error');
        }
    });

    // ===== Cancel Appointment =====
    window.cancelAppointment = async function(appointmentId) {
        try {
            const fd = new FormData();
            fd.set('action', 'cancel');
            fd.set('appointment_id', appointmentId);

            const res = await fetch(FETCH_APPOINTMENT_URL, { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.success) return Swal.fire('Error', data.message, 'error');

            Swal.fire('Cancelled', data.message, 'success');
            calendar.refetchEvents();

            if (modalDateInput.value) await loadSlots(modalDateInput.value);

        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Could not reach server', 'error');
        }
    };

    window.closeModal = () => { modal.style.display = 'none'; };
});
