<?php
// ============================================================
//  api.php — REST-style API for the Clinic System
//  Handles: patients, doctors, appointments (GET/POST/DELETE)
// ============================================================

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config.php';

$action   = $_GET['action']   ?? '';
$resource = $_GET['resource'] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $db = getDB();

    // ── DASHBOARD STATS ───────────────────────────────────────
    if ($action === 'stats') {
        $stats = [
            'total_patients'     => $db->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
            'total_doctors'      => $db->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
            'today_appointments' => $db->query("SELECT COUNT(*) FROM appointments WHERE appt_date = CURDATE()")->fetchColumn(),
            'completed_today'    => $db->query("SELECT COUNT(*) FROM appointments WHERE appt_date = CURDATE() AND status='Completed'")->fetchColumn(),
        ];
        jsonResponse(true, 'OK', $stats);
    }

    // ── PATIENTS ──────────────────────────────────────────────
    if ($resource === 'patients') {

        if ($method === 'GET') {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $stmt = $db->prepare(
                "SELECT * FROM patients WHERE name LIKE ? OR phone LIKE ? ORDER BY created_at DESC"
            );
            $stmt->execute([$search, $search]);
            jsonResponse(true, 'OK', $stmt->fetchAll());
        }

        if ($method === 'POST' && $action === 'add') {
            $stmt = $db->prepare(
                "INSERT INTO patients (name, age, gender, phone, address, blood_group)
                 VALUES (:name, :age, :gender, :phone, :address, :blood_group)"
            );
            $stmt->execute([
                ':name'        => trim($body['name']        ?? ''),
                ':age'         => intval($body['age']        ?? 0),
                ':gender'      => $body['gender']            ?? 'Male',
                ':phone'       => trim($body['phone']        ?? ''),
                ':address'     => trim($body['address']      ?? ''),
                ':blood_group' => trim($body['blood_group']  ?? ''),
            ]);
            jsonResponse(true, 'Patient added successfully.', ['id' => $db->lastInsertId()]);
        }

        if ($method === 'POST' && $action === 'delete') {
            $stmt = $db->prepare("DELETE FROM patients WHERE id = ?");
            $stmt->execute([$body['id'] ?? 0]);
            jsonResponse(true, 'Patient deleted.');
        }

        if ($method === 'POST' && $action === 'update') {
            $stmt = $db->prepare(
                "UPDATE patients SET name=:name, age=:age, gender=:gender,
                 phone=:phone, address=:address, blood_group=:blood_group WHERE id=:id"
            );
            $stmt->execute([
                ':name'        => trim($body['name']        ?? ''),
                ':age'         => intval($body['age']        ?? 0),
                ':gender'      => $body['gender']            ?? 'Male',
                ':phone'       => trim($body['phone']        ?? ''),
                ':address'     => trim($body['address']      ?? ''),
                ':blood_group' => trim($body['blood_group']  ?? ''),
                ':id'          => intval($body['id']         ?? 0),
            ]);
            jsonResponse(true, 'Patient updated.');
        }
    }

    // ── DOCTORS ───────────────────────────────────────────────
    if ($resource === 'doctors') {

        if ($method === 'GET') {
            $stmt = $db->query("SELECT * FROM doctors ORDER BY name ASC");
            jsonResponse(true, 'OK', $stmt->fetchAll());
        }

        if ($method === 'POST' && $action === 'add') {
            $stmt = $db->prepare(
                "INSERT INTO doctors (name, specialty, phone, email, fee)
                 VALUES (:name, :specialty, :phone, :email, :fee)"
            );
            $stmt->execute([
                ':name'      => trim($body['name']      ?? ''),
                ':specialty' => trim($body['specialty'] ?? ''),
                ':phone'     => trim($body['phone']     ?? ''),
                ':email'     => trim($body['email']     ?? ''),
                ':fee'       => floatval($body['fee']   ?? 0),
            ]);
            jsonResponse(true, 'Doctor added successfully.', ['id' => $db->lastInsertId()]);
        }

        if ($method === 'POST' && $action === 'delete') {
            $stmt = $db->prepare("DELETE FROM doctors WHERE id = ?");
            $stmt->execute([$body['id'] ?? 0]);
            jsonResponse(true, 'Doctor deleted.');
        }
    }

    // ── APPOINTMENTS ─────────────────────────────────────────
    if ($resource === 'appointments') {

        if ($method === 'GET') {
            $filter = isset($_GET['date']) ? $_GET['date'] : null;
            if ($filter) {
                $stmt = $db->prepare(
                    "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, d.specialty
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.id
                     JOIN doctors  d ON a.doctor_id  = d.id
                     WHERE a.appt_date = ?
                     ORDER BY a.appt_time ASC"
                );
                $stmt->execute([$filter]);
            } else {
                $stmt = $db->query(
                    "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, d.specialty
                     FROM appointments a
                     JOIN patients p ON a.patient_id = p.id
                     JOIN doctors  d ON a.doctor_id  = d.id
                     ORDER BY a.appt_date DESC, a.appt_time ASC"
                );
            }
            jsonResponse(true, 'OK', $stmt->fetchAll());
        }

        if ($method === 'POST' && $action === 'add') {
            $stmt = $db->prepare(
                "INSERT INTO appointments (patient_id, doctor_id, appt_date, appt_time, reason)
                 VALUES (:pid, :did, :date, :time, :reason)"
            );
            $stmt->execute([
                ':pid'    => intval($body['patient_id'] ?? 0),
                ':did'    => intval($body['doctor_id']  ?? 0),
                ':date'   => $body['appt_date']          ?? '',
                ':time'   => $body['appt_time']          ?? '',
                ':reason' => trim($body['reason']        ?? ''),
            ]);
            jsonResponse(true, 'Appointment booked.', ['id' => $db->lastInsertId()]);
        }

        if ($method === 'POST' && $action === 'status') {
            $stmt = $db->prepare("UPDATE appointments SET status=:s, notes=:n WHERE id=:id");
            $stmt->execute([
                ':s'  => $body['status'] ?? 'Scheduled',
                ':n'  => trim($body['notes'] ?? ''),
                ':id' => intval($body['id'] ?? 0),
            ]);
            jsonResponse(true, 'Status updated.');
        }

        if ($method === 'POST' && $action === 'delete') {
            $stmt = $db->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->execute([$body['id'] ?? 0]);
            jsonResponse(true, 'Appointment deleted.');
        }
    }

    jsonResponse(false, 'Unknown action or resource.');

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
