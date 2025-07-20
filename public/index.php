<?php
$config = require __DIR__ . '/../config.php';
$APP_USER = $config['APP_USER'] ?? 'admin';
$APP_PASS = $config['APP_PASS'] ?? 'secret';
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $APP_USER ||
    $_SERVER['PHP_AUTH_PW']   !== $APP_PASS) {
    header('WWW-Authenticate: Basic realm="Band Manager"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Band Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-ENjdO4Dr2bkBIFxQpeoU0UldvO0p8rH51D5sP+b5eP3pXtHGWk+y3Pch44CewZRL" crossorigin="anonymous">
</head>
<body class="container py-4">
    <h1 class="mb-4">Band Manager</h1>
    <ul class="nav nav-tabs" id="tabs">
        <li class="nav-item"><button class="nav-link active" data-target="contacts">Contacts</button></li>
        <li class="nav-item"><button class="nav-link" data-target="setlists">Setlists</button></li>
        <li class="nav-item"><button class="nav-link" data-target="bookings">Bookings</button></li>
        <li class="nav-item"><button class="nav-link" data-target="musicians">Musicians</button></li>
        <li class="nav-item"><button class="nav-link" data-target="invoices">Invoices</button></li>
        <li class="nav-item"><button class="nav-link" data-target="settings">Settings</button></li>
    </ul>
    <div id="content" class="mt-4"></div>

<script>
const tabs = document.querySelectorAll('#tabs button');
const content = document.getElementById('content');

tabs.forEach(btn => {
    btn.addEventListener('click', () => {
        tabs.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadSection(btn.dataset.target);
    });
});

function loadSection(section) {
    if (section === 'contacts') {
        content.innerHTML = `<div class="mb-3">
            <h2>Contacts</h2>
            <form id="contactForm" class="row g-2 mb-3">
                <div class="col-sm-3"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-sm-3"><input name="email" class="form-control" placeholder="Email"></div>
                <div class="col-sm-2"><input name="phone" class="form-control" placeholder="Phone"></div>
                <div class="col-sm-3"><input name="address" class="form-control" placeholder="Address"></div>
                <div class="col-sm-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
            </form>
            <ul id="contactList" class="list-group"></ul>
        </div>`;
        document.getElementById('contactForm').addEventListener('submit', createContact);
        fetchContacts();
    } else if (section === 'setlists') {
        content.innerHTML = `<div class="mb-3">
            <h2>Setlists</h2>
            <form id="setlistForm" class="row g-2 mb-3">
                <div class="col-sm-5"><input name="title" class="form-control" placeholder="Title" required></div>
                <div class="col-sm-5"><input name="songs" class="form-control" placeholder="Songs (comma separated)"></div>
                <div class="col-sm-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
            </form>
            <ul id="setlistList" class="list-group"></ul>
        </div>`;
        document.getElementById('setlistForm').addEventListener('submit', createSetlist);
        fetchSetlists();
    } else if (section === 'bookings') {
        content.innerHTML = `<div class="mb-3">
            <h2>Bookings</h2>
            <form id="bookingForm" class="row g-2 mb-3">
                <div class="col-sm-2"><input name="contact_id" class="form-control" placeholder="Contact ID" required></div>
                <div class="col-sm-2"><input name="event_date" type="date" class="form-control"></div>
                <div class="col-sm-2"><input name="location" class="form-control" placeholder="Location"></div>
                <div class="col-sm-2"><input name="description" class="form-control" placeholder="Description"></div>
                <div class="col-sm-2"><select name="status" class="form-select"><option value="inquiry">Inquiry</option><option value="confirmed">Confirmed</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                <div class="col-sm-2"><select name="musician_ids[]" id="bookingMusicians" class="form-select" multiple></select></div>
                <div class="col-sm-12"><button class="btn btn-primary mt-2" type="submit">Add</button></div>
            </form>
            <ul id="bookingList" class="list-group"></ul>
        </div>`;
        document.getElementById('bookingForm').addEventListener('submit', createBooking);
        populateBookingMusicians();
        fetchBookings();
    } else if (section === 'musicians') {
        content.innerHTML = `<div class="mb-3">
            <h2>Musicians</h2>
            <form id="musicianForm" class="row g-2 mb-3">
                <div class="col-sm-5"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-sm-5"><input name="instrument" class="form-control" placeholder="Instrument"></div>
                <div class="col-sm-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>
            </form>
            <ul id="musicianList" class="list-group"></ul>
        </div>`;
        document.getElementById('musicianForm').addEventListener('submit', createMusician);
        fetchMusicians();
    } else if (section === 'invoices') {
        content.innerHTML = `<div class="mb-3">
            <h2>Invoices</h2>
            <form id="invoiceForm" class="row g-2 mb-3">
                <div class="col-sm-2"><select name="booking_id" id="invoiceBooking" class="form-select" required></select></div>
                <div class="col-sm-2"><input name="amount_net" class="form-control" placeholder="Net" type="number" step="0.01"></div>
                <div class="col-sm-2"><input name="tax_rate" class="form-control" placeholder="Tax %" type="number" step="0.01"></div>
                <div class="col-sm-2"><input name="amount_gross" class="form-control" placeholder="Gross" type="number" step="0.01"></div>
                <div class="col-sm-2"><select name="payment_method" class="form-select"><option value="transfer">Transfer</option><option value="cash">Cash</option></select></div>
                <div class="col-sm-2"><input name="date" type="date" class="form-control"></div>
                <div class="col-sm-12"><button class="btn btn-primary mt-2" type="submit">Add</button></div>
            </form>
            <ul id="invoiceList" class="list-group"></ul>
        </div>`;
        document.getElementById('invoiceForm').addEventListener('submit', createInvoice);
        populateInvoiceBookings();
        fetchInvoices();
    } else if (section === 'settings') {
        content.innerHTML = `<div class="mb-3">
            <h2>Settings</h2>
            <form id="settingsForm" class="row g-2 mb-3">
                <div class="col-sm-6"><input name="bank" class="form-control" placeholder="Bank details"></div>
                <div class="col-sm-6"><input name="address" class="form-control" placeholder="Sender address"></div>
                <div class="col-sm-12"><button class="btn btn-primary" type="submit">Save</button></div>
            </form>
        </div>`;
        document.getElementById('settingsForm').addEventListener('submit', saveSettings);
        fetchSettings();
    }
}

async function createContact(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('api.php?entity=contacts', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) e.target.reset();
    fetchContacts();
}

async function fetchContacts() {
    const res = await fetch('api.php?entity=contacts');
    const list = document.getElementById('contactList');
    list.innerHTML = '';
    if(res.ok) {
        const items = await res.json();
        items.forEach(c => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${c.id}: ${c.name} - ${c.address || ''}`;
            list.appendChild(li);
        });
    }
}

async function createSetlist(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('api.php?entity=setlists', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) e.target.reset();
    fetchSetlists();
}

async function createBooking(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd);
    data.musician_ids = fd.getAll('musician_ids[]');
    const res = await fetch('api.php?entity=bookings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) e.target.reset();
    fetchBookings();
}

async function fetchBookings() {
    const res = await fetch('api.php?entity=bookings');
    const list = document.getElementById('bookingList');
    list.innerHTML = '';
    if(res.ok) {
        const items = await res.json();
        items.forEach(b => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            const musNames = b.musician_ids.map(id => musicians.find(m => m.id == id)?.name).filter(Boolean).join(', ');
            li.textContent = `${b.id}: ${b.event_date || ''} - ${b.location || ''} [${b.status}] (${musNames})`;
            list.appendChild(li);
        });
    }
}

async function fetchSetlists() {
    const res = await fetch('api.php?entity=setlists');
    const list = document.getElementById('setlistList');
    list.innerHTML = '';
    if(res.ok) {
        const items = await res.json();
        items.forEach(s => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${s.id}: ${s.title}`;
            list.appendChild(li);
        });
    }
}

async function createInvoice(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('api.php?entity=invoices', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) e.target.reset();
    fetchInvoices();
}

async function fetchInvoices() {
    const res = await fetch('api.php?entity=invoices');
    const list = document.getElementById('invoiceList');
    list.innerHTML = '';
    if(res.ok) {
        const items = await res.json();
        items.forEach(inv => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${inv.id}: ${inv.amount_gross || ''} ${inv.payment_method || ''}`;
            list.appendChild(li);
        });
    }
}

async function saveSettings(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('api.php?entity=settings', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) fetchSettings();
}

async function fetchSettings() {
    const res = await fetch('api.php?entity=settings');
    if(res.ok) {
        const items = await res.json();
        const form = document.getElementById('settingsForm');
        items.forEach(it => {
            if(form[it.name]) form[it.name].value = it.value;
        });
    }
}

let musicians = [];

async function createMusician(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    const res = await fetch('api.php?entity=musicians', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    if(res.ok) e.target.reset();
    fetchMusicians();
}

async function fetchMusicians() {
    const res = await fetch('api.php?entity=musicians');
    const list = document.getElementById('musicianList');
    if(list) list.innerHTML = '';
    if(res.ok) {
        musicians = await res.json();
        if(list) musicians.forEach(m => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${m.id}: ${m.name} (${m.instrument || ''})`;
            list.appendChild(li);
        });
    }
    populateBookingMusicians();
}

function populateBookingMusicians() {
    const select = document.getElementById('bookingMusicians');
    if(!select) return;
    select.innerHTML = '';
    musicians.forEach(m => {
        const opt = document.createElement('option');
        opt.value = m.id;
        opt.textContent = m.name;
        select.appendChild(opt);
    });
}

function populateInvoiceBookings() {
    const select = document.getElementById('invoiceBooking');
    if(!select) return;
    select.innerHTML = '';
    fetch('api.php?entity=bookings').then(r => r.json()).then(items => {
        items.filter(b => b.status === 'completed').forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = `${b.id} - ${b.location}`;
            select.appendChild(opt);
        });
    });
}

// Initialize first tab
fetchMusicians();
loadSection('contacts');
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-QDY3GvGEb9N8tr1M2kO1WKHWmsYHTS3zMoIXTwvThYNreYJHjcJKuoSB4ol6D6jk" crossorigin="anonymous"></script>
</body>
</html>
