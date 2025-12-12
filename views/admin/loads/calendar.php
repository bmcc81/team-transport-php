<?php
$pageTitle = "Load Calendar";
require __DIR__ . '/../../layout/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js"></script>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- MAIN -->
        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Load Calendar</h2>

                <a href="/admin/loads/create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus"></i> New Load
                </a>
            </div>

            <div id="calendar"></div>

        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: "auto",
        selectable: true,
        editable: true,
        eventResizableFromStart: true,

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },

        events: '/admin/loads/calendar/events',

        dateClick(info) {
            window.location = `/admin/loads/calendar/create?date=${info.dateStr}`;
        },

        eventDrop(info) {
            updateEvent(info.event);
        },

        eventResize(info) {
            updateEvent(info.event);
        }
    });

    calendar.render();

    function updateEvent(event) {
        fetch('/admin/loads/calendar/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                id: event.id,
                start: event.start.toISOString().slice(0, 19).replace('T', ' '),
                end: event.end.toISOString().slice(0, 19).replace('T', ' ')
            })
        });
    }
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
