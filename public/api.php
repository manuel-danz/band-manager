<?php
header('Content-Type: application/json');

$config = require __DIR__ . '/../config.php';
$APP_USER = $config['APP_USER'] ?? 'admin';
$APP_PASS = $config['APP_PASS'] ?? 'secret';
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $APP_USER ||
    $_SERVER['PHP_AUTH_PW']   !== $APP_PASS) {
    header('WWW-Authenticate: Basic realm="Band Manager"');
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$DB_HOST = $config['DB_HOST'] ?? 'localhost';
$DB_USER = $config['DB_USER'] ?? 'root';
$DB_PASS = $config['DB_PASS'] ?? '';
$DB_NAME = $config['DB_NAME'] ?? 'bandmanager';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

$entity = $_GET['entity'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($entity) {
    case 'contacts':
        handleContacts($mysqli, $method);
        break;
    case 'setlists':
        handleSetlists($mysqli, $method);
        break;
    case 'bookings':
        handleBookings($mysqli, $method);
        break;
    case 'musicians':
        handleMusicians($mysqli, $method);
        break;
    case 'invoices':
        handleInvoices($mysqli, $method);
        break;
    case 'settings':
        handleSettings($mysqli, $method);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown entity']);
}

function handleContacts($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM contacts');
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO contacts (name, email, phone, address, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $data['name'], $data['email'], $data['phone'], $data['address'], $data['notes']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        parse_str($_SERVER['QUERY_STRING'], $params);
        $id = $params['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE contacts SET name=?, email=?, phone=?, address=?, notes=? WHERE id=?');
        $stmt->bind_param('sssssi', $data['name'], $data['email'], $data['phone'], $data['address'], $data['notes'], $id);
        $stmt->execute();
        $data['id'] = $id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM contacts WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}

function handleSetlists($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM setlists');
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO setlists (title, songs) VALUES (?, ?)');
        $stmt->bind_param('ss', $data['title'], $data['songs']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE setlists SET title=?, songs=? WHERE id=?');
        $stmt->bind_param('ssi', $data['title'], $data['songs'], $id);
        $stmt->execute();
        $data['id'] = $id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM setlists WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}

function handleBookings($db, $method) {
    if ($method === 'GET') {
        $sql = 'SELECT b.*, GROUP_CONCAT(bm.musician_id) AS musician_ids '
             .'FROM bookings b '
             .'LEFT JOIN booking_musicians bm ON b.id=bm.booking_id '
             .'GROUP BY b.id';
        $res = $db->query($sql);
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $row['musician_ids'] = $row['musician_ids'] ? array_map('intval', explode(',', $row['musician_ids'])) : [];
            $rows[] = $row;
        }
        echo json_encode($rows);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO bookings (contact_id, event_date, location, description, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issss', $data['contact_id'], $data['event_date'], $data['location'], $data['description'], $data['status']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        if (isset($data['musician_ids']) && is_array($data['musician_ids'])) {
            foreach ($data['musician_ids'] as $mid) {
                $link = $db->prepare('INSERT INTO booking_musicians (booking_id, musician_id) VALUES (?, ?)');
                $link->bind_param('ii', $data['id'], $mid);
                $link->execute();
            }
        }
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE bookings SET contact_id=?, event_date=?, location=?, description=?, status=? WHERE id=?');
        $stmt->bind_param('issssi', $data['contact_id'], $data['event_date'], $data['location'], $data['description'], $data['status'], $id);
        $stmt->execute();
        $db->query('DELETE FROM booking_musicians WHERE booking_id='.(int)$id);
        if (isset($data['musician_ids']) && is_array($data['musician_ids'])) {
            foreach ($data['musician_ids'] as $mid) {
                $link = $db->prepare('INSERT INTO booking_musicians (booking_id, musician_id) VALUES (?, ?)');
                $link->bind_param('ii', $id, $mid);
                $link->execute();
            }
        }
        $data['id'] = $id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM bookings WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}

function handleInvoices($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM invoices');
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO invoices (booking_id, amount_net, tax_rate, amount_gross, payment_method, date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('iddsss', $data['booking_id'], $data['amount_net'], $data['tax_rate'], $data['amount_gross'], $data['payment_method'], $data['date']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE invoices SET booking_id=?, amount_net=?, tax_rate=?, amount_gross=?, payment_method=?, date=? WHERE id=?');
        $stmt->bind_param('iddsssi', $data['booking_id'], $data['amount_net'], $data['tax_rate'], $data['amount_gross'], $data['payment_method'], $data['date'], $id);
        $stmt->execute();
        $data['id'] = $id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM invoices WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}

function handleSettings($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT name, value FROM settings');
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        echo json_encode($rows);
    } elseif ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data as $name => $value) {
            $stmt = $db->prepare('INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
            $stmt->bind_param('ss', $name, $value);
            $stmt->execute();
        }
        echo json_encode(['success' => true]);
    }
}

function handleMusicians($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM musicians');
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO musicians (name, instrument) VALUES (?, ?)');
        $stmt->bind_param('ss', $data['name'], $data['instrument']);
        $stmt->execute();
        $data['id'] = $stmt->insert_id;
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE musicians SET name=?, instrument=? WHERE id=?');
        $stmt->bind_param('ssi', $data['name'], $data['instrument'], $id);
        $stmt->execute();
        $data['id'] = $id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM musicians WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }
}
