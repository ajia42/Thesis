<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ລະບົບນັດໝາຍ</title>
    <style>
        :root {
            --primary-color: #4EADBE;
            --text-color: #333;
            --bg-color: #F5F5F5;
            --card-color: #F1F7F8;
            --white: #FFFFFF;
            --border-radius: 16px;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Noto Sans Lao', Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        .container {
            max-width: 768px;
            margin: 0 auto;
            padding: 0 16px;
        }

        /* Header Styles */
        header {
            background-color: var(--white);
            padding: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu-icon {
            font-size: 24px;
            cursor: pointer;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 500;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Page Title */
        .page-title {
            margin: 30px 0;
            font-size: 28px;
            font-weight: 600;
        }

        /* Appointment Card Styles */
        .appointment-card {
            background-color: var(--card-color);
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        }

        .date-box {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 20px;
            text-align: center;
            width: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .date-day {
            font-size: 36px;
            font-weight: bold;
            line-height: 1;
        }

        .date-month {
            font-size: 18px;
            margin-top: 5px;
        }

        .appointment-details {
            padding: 20px;
            flex-grow: 1;
        }

        .appointment-type {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .appointment-person {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .appointment-position {
            color: #666;
            margin-bottom: 12px;
        }

        .appointment-time {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .appointment-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .appointment-status.confirmed {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .appointment-status.pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .appointment-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-details {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-reschedule {
            background-color: var(--bg-color);
            color: var(--text-color);
            border: 1px solid #ddd;
        }
        
        .btn-cancel {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* New Appointment Button */
        .new-appointment-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: var(--primary-color);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            border: none;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 24px;
            width: 90%;
            max-width: 500px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close-modal {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .time-slots {
            margin-top: 20px;
        }
        
        .date-selector {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .current-date {
            font-size: 18px;
            font-weight: 500;
        }
        
        .date-nav {
            background: none;
            border: 1px solid #ddd;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        /* Appointment Type Selector */
        .appointment-type-selector {
            margin-top: 24px;
            margin-bottom: 24px;
        }
        
        .appointment-type-selector h3 {
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .appointment-types {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .type-option {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: var(--bg-color);
        }
        
        .type-option:hover {
            background-color: #e9e9e9;
        }
        
        /* Time Selection */
        .time-selection {
            margin-bottom: 24px;
        }
        
        .time-selection h3 {
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .time-inputs {
            display: flex;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .hour-select, .minute-select {
            flex: 1;
        }
        
        .time-dropdown, .duration-dropdown {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: var(--bg-color);
            font-size: 16px;
        }
        
        /* Available Time Slots */
        .available-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .time-slot {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            text-align: center;
            cursor: pointer;
            background-color: var(--bg-color);
        }
        
        .time-slot.selected {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* Duration Selection */
        .duration-select {
            margin-bottom: 24px;
        }
        
        .duration-select h3 {
            margin-bottom: 12px;
            font-size: 16px;
        }
        
        .btn-confirm-time {
            width: 100%;
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .page-title {
                font-size: 24px;
                margin: 20px 0;
            }
            
            .date-box {
                width: 80px;
                padding: 15px;
            }
            
            .date-day {
                font-size: 30px;
            }
            
            .appointment-details {
                padding: 15px;
            }
            
            .appointment-type {
                font-size: 18px;
            }
            
            .appointment-actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
            }
            
            .available-slots, .appointment-types {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .time-inputs {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content container">
            <div class="menu-icon">&#9776;</div>
            <div class="profile">
                <div class="profile-name">ໂຈນາທານ ຈາລເລ</div>
                <div class="avatar">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <h1 class="page-title">ການນັດໝາຍ</h1>
        
        <div class="appointments-list">
            <!-- First Appointment -->
            <div class="appointment-card">
                <div class="date-box">
                    <div class="date-day">30</div>
                    <div class="date-month">ທັນວາ</div>
                </div>
                <div class="appointment-details">
                    <div class="appointment-type">ກວດທົ່ວໄປ</div>
                    <div class="appointment-person">ຄຣິສຕິນາ ຢາງ</div>
                    <div class="appointment-position">ທີ່ປຶກສາອາວຸໂສ</div>
                    <div class="appointment-time">14:30</div>
                    <div class="appointment-status confirmed">ຢືນຢັນແລ້ວ</div>
                    <div class="appointment-actions">
                        <button class="btn btn-details">ລາຍລະອຽດ</button>
                        <button class="btn btn-reschedule">ປ່ຽນເວລາ</button>
                        <button class="btn btn-cancel">ຍົກເລີກ</button>
                    </div>
                </div>
            </div>
            
            <!-- Second Appointment -->
            <div class="appointment-card">
                <div class="date-box">
                    <div class="date-day">22</div>
                    <div class="date-month">ພະຈິກ</div>
                </div>
                <div class="appointment-details">
                    <div class="appointment-type">ວັດແທກສາຍຕາ</div>
                    <div class="appointment-person">ຄຣິສຕິນາ ຢາງ</div>
                    <div class="appointment-position">ທີ່ປຶກສາອາວຸໂສ</div>
                    <div class="appointment-time">9:15</div>
                    <div class="appointment-status pending">ລໍຖ້າການຢືນຢັນ</div>
                    <div class="appointment-actions">
                        <button class="btn btn-details">ລາຍລະອຽດ</button>
                        <button class="btn btn-reschedule">ປ່ຽນເວລາ</button>
                        <button class="btn btn-cancel">ຍົກເລີກ</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- New Appointment Button -->
        <button class="new-appointment-btn">+</button>
        
        <!-- Booking Time Slots Modal -->
        <div id="timeSlotModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>ເລືອກເວລາການນັດໝາຍ</h2>
                
                <!-- Appointment Type Selection -->
                <div class="appointment-type-selector">
                    <h3>ປະເພດການນັດໝາຍ</h3>
                    <div class="appointment-types">
                        <label class="type-option">
                            <input type="radio" name="appointmentType" value="general" checked>
                            <span class="type-label">ກວດທົ່ວໄປ</span>
                        </label>
                        <label class="type-option">
                            <input type="radio" name="appointmentType" value="eyetest">
                            <span class="type-label">ວັດແທກສາຍຕາ</span>
                        </label>
                    </div>
                </div>
                
                <div class="time-slots">
                    <div class="date-selector">
                        <button class="date-nav prev">&#8592;</button>
                        <div class="current-date">16 ພຶດສະພາ 2025</div>
                        <button class="date-nav next">&#8594;</button>
                    </div>
                    
                    <!-- Detailed Time Selection -->
                    <div class="time-selection">
                        <h3>ເລືອກເວລາ</h3>
                        <div class="time-inputs">
                            <div class="hour-select">
                                <label>ຊົ່ວໂມງ</label>
                                <select id="hourSelect" class="time-dropdown">
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="13">13</option>
                                    <option value="14">14</option>
                                    <option value="15">15</option>
                                    <option value="16">16</option>
                                    <option value="17">17</option>
                                </select>
                            </div>
                            <div class="minute-select">
                                <label>ນາທີ</label>
                                <select id="minuteSelect" class="time-dropdown">
                                    <option value="00">00</option>
                                    <option value="05">05</option>
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="20">20</option>
                                    <option value="25">25</option>
                                    <option value="30">30</option>
                                    <option value="35">35</option>
                                    <option value="40">40</option>
                                    <option value="45">45</option>
                                    <option value="50">50</option>
                                    <option value="55">55</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <h3>ຫຼື ເລືອກຈາກຊ່ວງເວລາທີ່ວ່າງ</h3>
                    <div class="available-slots">
                        <button class="time-slot">8:00</button>
                        <button class="time-slot">9:15</button>
                        <button class="time-slot">10:30</button>
                        <button class="time-slot">13:00</button>
                        <button class="time-slot">14:30</button>
                        <button class="time-slot">16:00</button>
                    </div>
                    
                    <div class="duration-select">
                        <h3>ໄລຍະເວລາ</h3>
                        <select id="durationSelect" class="duration-dropdown">
                            <option value="30">30 ນາທີ</option>
                            <option value="45">45 ນາທີ</option>
                            <option value="60" selected>1 ຊົ່ວໂມງ</option>
                            <option value="90">1 ຊົ່ວໂມງ 30 ນາທີ</option>
                            <option value="120">2 ຊົ່ວໂມງ</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-confirm-time">ຢືນຢັນເວລາ</button>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Basic interactivity for demo purposes
        document.querySelector('.new-appointment-btn').addEventListener('click', function() {
            document.getElementById('timeSlotModal').style.display = 'flex';
        });
        
        document.querySelector('.menu-icon').addEventListener('click', function() {
            alert('ເປີດເມນູ');
        });
        
        // Detail buttons functionality
        const detailButtons = document.querySelectorAll('.btn-details');
        detailButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                alert('ເບິ່ງລາຍລະອຽດການນັດໝາຍ');
            });
        });
        
        // Reschedule buttons functionality
        const rescheduleButtons = document.querySelectorAll('.btn-reschedule');
        rescheduleButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                document.getElementById('timeSlotModal').style.display = 'flex';
            });
        });
        
        // Cancel buttons functionality
        const cancelButtons = document.querySelectorAll('.btn-cancel');
        cancelButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                if(confirm('ທ່ານແນ່ໃຈບໍ່ທີ່ຈະຍົກເລີກການນັດໝາຍນີ້?')) {
                    alert('ຍົກເລີກການນັດໝາຍສຳເລັດ');
                }
            });
        });
        
        // Close modal
        document.querySelector('.close-modal').addEventListener('click', function() {
            document.getElementById('timeSlotModal').style.display = 'none';
        });
        
        // Time slot selection
        const timeSlots = document.querySelectorAll('.time-slot');
        timeSlots.forEach(slot => {
            slot.addEventListener('click', function() {
                // Remove selection from all slots
                timeSlots.forEach(s => s.classList.remove('selected'));
                // Add selection to clicked slot
                this.classList.add('selected');
                
                // Update hour and minute selects to match the selected slot
                const timeText = this.textContent;
                const [hour, minute] = timeText.split(':');
                document.getElementById('hourSelect').value = hour;
                document.getElementById('minuteSelect').value = minute;
            });
        });
        
        // Hour and minute select synchronization
        document.getElementById('hourSelect').addEventListener('change', unselectTimeSlots);
        document.getElementById('minuteSelect').addEventListener('change', unselectTimeSlots);
        
        function unselectTimeSlots() {
            const timeSlots = document.querySelectorAll('.time-slot');
            timeSlots.forEach(s => s.classList.remove('selected'));
        }
        
        // Date navigation
        let currentDate = new Date();
        updateDateDisplay();
        
        document.querySelector('.date-nav.prev').addEventListener('click', function() {
            currentDate.setDate(currentDate.getDate() - 1);
            updateDateDisplay();
        });
        
        document.querySelector('.date-nav.next').addEventListener('click', function() {
            currentDate.setDate(currentDate.getDate() + 1);
            updateDateDisplay();
        });
        
        function updateDateDisplay() {
            const day = currentDate.getDate();
            const monthNames = ['ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 
                               'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'];
            const month = monthNames[currentDate.getMonth()];
            const year = currentDate.getFullYear();
            document.querySelector('.current-date').textContent = `${day} ${month} ${year}`;
        }
        
        // Confirm time button
        document.querySelector('.btn-confirm-time').addEventListener('click', function() {
            const selectedType = document.querySelector('input[name="appointmentType"]:checked').value;
            const typeLabels = {
                'general': 'ກວດທົ່ວໄປ',
                'eyetest': 'ວັດແທກສາຍຕາ'
            };
            
            const hour = document.getElementById('hourSelect').value;
            const minute = document.getElementById('minuteSelect').value;
            const duration = document.getElementById('durationSelect').value;
            
            const selectedDate = document.querySelector('.current-date').textContent;
            
            alert(`ຈອງສຳເລັດ:\nປະເພດ: ${typeLabels[selectedType]}\nວັນທີ: ${selectedDate}\nເວລາ: ${hour}:${minute}\nໄລຍະເວລາ: ${duration} ນາທີ`);
            
            document.getElementById('timeSlotModal').style.display = 'none';
        });
    </script>
</body>
</html>