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

$driver = $config['DB_DRIVER'] ?? 'mysql';
try {
    if ($driver === 'sqlite') {
        $path = $config['DB_PATH'] ?? __DIR__ . '/../database.sqlite';
        $dsn = 'sqlite:' . $path;
        $pdo = new PDO($dsn);
    } else {
        $host = $config['DB_HOST'] ?? 'localhost';
        $name = $config['DB_NAME'] ?? 'bandmanager';
        $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
        $user = $config['DB_USER'] ?? 'root';
        $pass = $config['DB_PASS'] ?? '';
        $pdo = new PDO($dsn, $user, $pass);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$entity = $_GET['entity'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($entity) {
    case 'contacts':
        handleContacts($pdo, $method);
        break;
    case 'setlists':
        handleSetlists($pdo, $method);
        break;
    case 'bookings':
        handleBookings($pdo, $method);
        break;
    case 'musicians':
        handleMusicians($pdo, $method);
        break;
    case 'invoices':
        handleInvoices($pdo, $method);
        break;
    case 'settings':
        handleSettings($pdo, $method);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown entity']);
}

function handleContacts($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM contacts');
        echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO contacts (name, email, phone, address, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['name'], $data['email'], $data['phone'], $data['address'], $data['notes']
        ]);
        $data['id'] = $db->lastInsertId();
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        parse_str($_SERVER['QUERY_STRING'], $params);
        $id = $params['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE contacts SET name=?, email=?, phone=?, address=?, notes=? WHERE id=?');
        $stmt->execute([
            $data['name'], $data['email'], $data['phone'], $data['address'], $data['notes'], $id
        ]);
        $data['id'] = (int)$id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM contacts WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}

function handleSetlists($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM setlists');
        echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO setlists (title, songs) VALUES (?, ?)');
        $stmt->execute([$data['title'], $data['songs']]);
        $data['id'] = $db->lastInsertId();
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE setlists SET title=?, songs=? WHERE id=?');
        $stmt->execute([$data['title'], $data['songs'], $id]);
        $data['id'] = (int)$id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM setlists WHERE id=?');
        $stmt->execute([$id]);
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
        foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['musician_ids'] = $row['musician_ids'] ? array_map('intval', explode(',', $row['musician_ids'])) : [];
            $rows[] = $row;
        }
        echo json_encode($rows);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO bookings (contact_id, event_date, location, description, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$data['contact_id'], $data['event_date'], $data['location'], $data['description'], $data['status']]);
        $data['id'] = $db->lastInsertId();
        if (isset($data['musician_ids']) && is_array($data['musician_ids'])) {
            $link = $db->prepare('INSERT INTO booking_musicians (booking_id, musician_id) VALUES (?, ?)');
            foreach ($data['musician_ids'] as $mid) {
                $link->execute([$data['id'], $mid]);
            }
        }
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE bookings SET contact_id=?, event_date=?, location=?, description=?, status=? WHERE id=?');
        $stmt->execute([$data['contact_id'], $data['event_date'], $data['location'], $data['description'], $data['status'], $id]);
        $db->prepare('DELETE FROM booking_musicians WHERE booking_id=?')->execute([$id]);
        if (isset($data['musician_ids']) && is_array($data['musician_ids'])) {
            $link = $db->prepare('INSERT INTO booking_musicians (booking_id, musician_id) VALUES (?, ?)');
            foreach ($data['musician_ids'] as $mid) {
                $link->execute([$id, $mid]);
            }
        }
        $data['id'] = (int)$id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM bookings WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}

function handleInvoices($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM invoices');
        echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO invoices (booking_id, amount_net, tax_rate, amount_gross, payment_method, date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['booking_id'], $data['amount_net'], $data['tax_rate'],
            $data['amount_gross'], $data['payment_method'], $data['date']
        ]);
        $data['id'] = $db->lastInsertId();
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE invoices SET booking_id=?, amount_net=?, tax_rate=?, amount_gross=?, payment_method=?, date=? WHERE id=?');
        $stmt->execute([
            $data['booking_id'], $data['amount_net'], $data['tax_rate'],
            $data['amount_gross'], $data['payment_method'], $data['date'], $id
        ]);
        $data['id'] = (int)$id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM invoices WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}

function handleSettings($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT name, value FROM settings');
        echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'), true);
        foreach ($data as $name => $value) {
            $stmt = $db->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)');
            $stmt->execute([$name, $value]);
        }
        echo json_encode(['success' => true]);
    }
}

function handleMusicians($db, $method) {
    if ($method === 'GET') {
        $res = $db->query('SELECT * FROM musicians');
        echo json_encode($res->fetchAll(PDO::FETCH_ASSOC));
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('INSERT INTO musicians (name, instrument) VALUES (?, ?)');
        $stmt->execute([$data['name'], $data['instrument']]);
        $data['id'] = $db->lastInsertId();
        echo json_encode($data);
    } elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare('UPDATE musicians SET name=?, instrument=? WHERE id=?');
        $stmt->execute([$data['name'], $data['instrument'], $id]);
        $data['id'] = (int)$id;
        echo json_encode($data);
    } elseif ($method === 'DELETE') {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare('DELETE FROM musicians WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}
