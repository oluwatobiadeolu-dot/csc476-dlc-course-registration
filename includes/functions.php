<?php
require_once __DIR__ . '/../config/db.php';

// create an empty file named debug.on in the project folder to show PHP errors; delete it when done
if (file_exists(__DIR__ . '/../debug.on')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// escape output
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function redirect_by_role(): void
{
    $user = current_user();
    if (!$user) {
        redirect('/login.php');
    }
    redirect($user['role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php');
}

// redirect guests to login and block the wrong role
function require_login(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        flash('warning', 'Please log in to continue.');
        redirect('/login.php');
    }
    if ($role !== null && $user['role'] !== $role) {
        http_response_code(403);
        exit('You do not have access to this page.');
    }
    return $user;
}

// CSRF
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Invalid form submission. Go back, refresh the page and try again.');
    }
}

// flash messages
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

// sessions and deadlines
function current_session(): ?array
{
    $row = db()->query('SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1')->fetch();
    return $row ?: null;
}

function require_current_session(): array
{
    $session = current_session();
    if (!$session) {
        exit('No current academic session is set. Ask the administrator to set one.');
    }
    return $session;
}

function session_label(array $s): string
{
    return $s['session_name'] . ' ' . $s['semester'] . ' Semester';
}

function get_deadlines(int $sessionId): ?array
{
    $stmt = db()->prepare('SELECT * FROM deadlines WHERE session_id = ?');
    $stmt->execute([$sessionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// open until the deadline passes; no deadline set means open
function deadline_open(?string $deadline): bool
{
    return $deadline === null || time() <= strtotime($deadline);
}

function deadline_text(?string $deadline): string
{
    if ($deadline === null) {
        return 'not set yet';
    }
    $ts = strtotime($deadline);
    $label = date('D, j M Y \a\t g:i A', $ts);
    if (time() > $ts) {
        return $label . ' (closed)';
    }
    $secs  = $ts - time();
    $days  = (int) floor($secs / 86400);
    $hours = (int) floor(($secs % 86400) / 3600);
    if ($days > 0) {
        $left = $days . ($days === 1 ? ' day' : ' days') . ' left';
    } elseif ($hours > 0) {
        $left = $hours . ($hours === 1 ? ' hour' : ' hours') . ' left';
    } else {
        $left = 'less than an hour left';
    }
    return $label . ' (' . $left . ')';
}


// student helpers
function cart_ids(): array
{
    return array_map('intval', $_SESSION['cart'] ?? []);
}

// a course the student can register: published, this semester, own department, own level or lower (carryovers)
function find_eligible_course(array $user, array $session, int $courseId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM courses
         WHERE id = ? AND status = "published" AND level <= ? AND semester = ? AND department_id = ?'
    );
    $stmt->execute([$courseId, (int) $user['level'], $session['semester'], (int) $user['department_id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function registered_course_ids(int $studentId, int $sessionId): array
{
    $stmt = db()->prepare(
        'SELECT course_id FROM registrations WHERE student_id = ? AND session_id = ? AND status = "active"'
    );
    $stmt->execute([$studentId, $sessionId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'course_id'));
}

function report_badge(string $status): string
{
    $map = ['open' => 'secondary', 'under_review' => 'warning', 'resolved' => 'success'];
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="pill pill-' . ($map[$status] ?? 'secondary') . '">' . e($label) . '</span>';
}

// course types and unit limits
function course_type_badge(string $type): string
{
    return $type === 'elective'
        ? '<span class="pill pill-secondary">Elective</span>'
        : '<span class="pill pill-primary">Compulsory</span>';
}

// unit limits set by the admin, 0 means no limit
function unit_limits(): array
{
    $limits = ['min' => 0, 'max' => 0];
    $rows = db()->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('min_units','max_units')")->fetchAll();
    foreach ($rows as $r) {
        if ($r['setting_key'] === 'min_units') {
            $limits['min'] = (int) $r['setting_value'];
        } else {
            $limits['max'] = (int) $r['setting_value'];
        }
    }
    return $limits;
}

function active_units(int $studentId, int $sessionId): int
{
    $stmt = db()->prepare(
        'SELECT COALESCE(SUM(c.credit_units), 0)
         FROM registrations r JOIN courses c ON c.id = r.course_id
         WHERE r.student_id = ? AND r.session_id = ? AND r.status = "active"'
    );
    $stmt->execute([$studentId, $sessionId]);
    return (int) $stmt->fetchColumn();
}

function cart_units(array $user, array $session): int
{
    $sum = 0;
    foreach (cart_ids() as $id) {
        $c = find_eligible_course($user, $session, $id);
        if ($c) {
            $sum += (int) $c['credit_units'];
        }
    }
    return $sum;
}

function limits_text(array $limits): string
{
    $min = $limits['min'] > 0 ? 'minimum ' . $limits['min'] . ' units' : 'no minimum';
    $max = $limits['max'] > 0 ? 'maximum ' . $limits['max'] . ' units' : 'no maximum';
    return 'Unit limits: ' . $min . ', ' . $max . '. Set by the administrator, not official University policy.';
}

// ui helpers
function icon(string $name): string
{
    return '<i class="bi bi-' . e($name) . '" aria-hidden="true"></i>';
}

function page_header(string $title, string $subtitle = '', string $actions = ''): string
{
    $html = '<div class="page-header"><div><h1 class="page-title">' . e($title) . '</h1>';
    if ($subtitle !== '') {
        $html .= '<p class="page-sub">' . e($subtitle) . '</p>';
    }
    $html .= '</div>';
    if ($actions !== '') {
        $html .= '<div class="page-actions">' . $actions . '</div>';
    }
    return $html . '</div>';
}

function empty_state(string $iconName, string $title, string $text, string $actions = ''): string
{
    return '<div class="empty-state">' . icon($iconName) . '<h2>' . e($title) . '</h2><p>' . e($text) . '</p>'
        . ($actions !== '' ? '<div>' . $actions . '</div>' : '') . '</div>';
}

function stat_card(string $label, $value, string $iconName): string
{
    return '<div class="stat"><span class="stat-icon">' . icon($iconName) . '</span><div><div class="stat-value">'
        . e($value) . '</div><div class="stat-label">' . e($label) . '</div></div></div>';
}

function render_flashes(): string
{
    $icons = ['success' => 'check-circle-fill', 'danger' => 'exclamation-octagon-fill',
              'warning' => 'exclamation-triangle-fill', 'info' => 'info-circle-fill'];
    $html = '';
    foreach (take_flashes() as $f) {
        $type = $f['type'];
        $html .= '<div class="alert alert-' . e($type) . ' alert-dismissible fade show d-print-none" role="alert">'
            . icon($icons[$type] ?? 'info-circle-fill') . '<div>' . e($f['message']) . '</div>'
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return $html;
}

function is_current_page(string $path): bool
{
    return substr($_SERVER['SCRIPT_NAME'], -strlen($path)) === $path;
}

// deadline split into date text, a short note and a state (open, soon, closed, none)
function deadline_parts(?string $deadline): array
{
    if ($deadline === null) {
        return ['not set yet', 'No limit applies yet', 'none'];
    }
    $ts = strtotime($deadline);
    $label = date('D, j M Y \a\t g:i A', $ts);
    if (time() > $ts) {
        return [$label, 'Closed', 'closed'];
    }
    $secs  = $ts - time();
    $days  = (int) floor($secs / 86400);
    $hours = (int) floor(($secs % 86400) / 3600);
    if ($days > 0) {
        $note = $days . ($days === 1 ? ' day' : ' days') . ' left';
    } elseif ($hours > 0) {
        $note = $hours . ($hours === 1 ? ' hour' : ' hours') . ' left';
    } else {
        $note = 'less than an hour left';
    }
    return [$label, $note, $secs <= 3 * 86400 ? 'soon' : 'open'];
}

// key dates panel shown to students
function deadline_banner(array $session): string
{
    $d = get_deadlines((int) $session['id']);
    $items = [
        ['Registration closes', $d['registration_deadline'] ?? null],
        ['Course drop closes', $d['drop_deadline'] ?? null],
    ];
    $html = '<section class="keydates" aria-label="Key dates"><div class="keydates-head">' . icon('calendar-event')
        . '<div><span class="eyebrow">Current session</span><strong>' . e(session_label($session)) . '</strong></div></div>'
        . '<div class="keydates-grid">';
    foreach ($items as [$label, $when]) {
        [$date, $note, $state] = deadline_parts($when);
        $html .= '<div class="keydate keydate-' . $state . '"><span class="kd-label">' . e($label)
            . '</span><span class="kd-date">' . e($date) . '</span><span class="kd-note">' . e($note) . '</span></div>';
    }
    return $html . '</div></section>';
}

function error_list(array $errors): string
{
    if (!$errors) {
        return '';
    }
    $items = '';
    foreach ($errors as $err) {
        $items .= '<li>' . e($err) . '</li>';
    }
    return '<div class="alert alert-danger" role="alert">' . icon('exclamation-octagon-fill')
        . '<div><strong>Please fix the following:</strong><ul class="mb-0 mt-1">' . $items . '</ul></div></div>';
}
