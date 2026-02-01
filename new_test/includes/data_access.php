<?php
// includes/data_access.php
// Abstracts Database vs Session storage for Guests

// Ensure DB is included
require_once 'db.php';

/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Get current user ID (0 for guest)
 */
function get_current_user_id()
{
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
}

/**
 * Get all notes for current user (or guest session)
 * @param array $filters ['category' => '...', 'search' => '...', 'archived' => 0/1]
 */
function get_notes($filters = [])
{
    global $conn;
    $uid = get_current_user_id();
    $notes = [];

    // Defaults
    $archived = isset($filters['archived']) ? intval($filters['archived']) : 0;
    $category = isset($filters['category']) ? $filters['category'] : null;
    $search = isset($filters['search']) ? trim($filters['search']) : null;

    if (is_logged_in()) {
        // --- DATABASE FETCH ---
        $sql = "SELECT n.*, p.text FROM notes n 
                LEFT JOIN pages p ON n.id = p.note_id AND p.page_number = 1 
                WHERE n.user_id = $uid AND n.is_archived = $archived";

        if ($category) {
            $cat_esc = mysqli_real_escape_string($conn, $category);
            $sql .= " AND n.category = '$cat_esc'";
        }
        if ($search) {
            $q_esc = mysqli_real_escape_string($conn, $search);
            $sql .= " AND (n.title LIKE '%$q_esc%' OR p.text LIKE '%$q_esc%')";
        }

        $sql .= " ORDER BY n.is_pinned DESC, n.date_last DESC";

        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $notes[] = $row;
        }
    } else {
        // --- SESSION FETCH (GUEST) ---
        if (!isset($_SESSION['guest_notes'])) {
            $_SESSION['guest_notes'] = [];
        }
        $all_guest = $_SESSION['guest_notes'];

        foreach ($all_guest as $n) {
            // Apply Filters
            if (isset($n['is_archived']) && $n['is_archived'] != $archived)
                continue;

            if ($category && $n['category'] !== $category)
                continue;

            if ($search) {
                // Simple case-insensitive search
                if (stripos($n['title'], $search) === false && stripos($n['text'], $search) === false) {
                    continue;
                }
            }
            $notes[] = $n;
        }

        // Sort: Pinned DESC, Date Last DESC
        usort($notes, function ($a, $b) {
            if ($b['is_pinned'] != $a['is_pinned']) {
                return $b['is_pinned'] - $a['is_pinned'];
            }
            return strtotime($b['date_last']) - strtotime($a['date_last']);
        });
    }

    return $notes;
}

/**
 * Get a single note by ID
 */
function get_note($id)
{
    global $conn;
    $uid = get_current_user_id();

    if (is_logged_in()) {
        // DB
        $id_esc = intval($id);
        $sql = "SELECT n.*, p.text FROM notes n 
                LEFT JOIN pages p ON n.id = p.note_id 
                WHERE n.id = $id_esc AND n.user_id = $uid 
                LIMIT 1";
        $result = mysqli_query($conn, $sql);
        return mysqli_fetch_assoc($result);
    } else {
        // Session
        // Guest IDs are strings like "guest_123"
        if (isset($_SESSION['guest_notes'][$id])) {
            return $_SESSION['guest_notes'][$id];
        }
        return null;
    }
}

/**
 * Save a note (Insert or Update)
 * @param array $data ['id' => ..., 'title' => ..., 'category' => ..., 'text' => ..., 'is_pinned' => ..., 'is_archived' => ...]
 * @return mixed New ID or True/False
 */
function save_note($data)
{
    global $conn;
    $uid = get_current_user_id();

    $title = $data['title'];
    $category = $data['category'];
    $text = $data['text']; // Rich Text HTML
    $is_pinned = isset($data['is_pinned']) ? 1 : 0;
    $is_archived = isset($data['is_archived']) ? 1 : 0;

    if (is_logged_in()) {
        // --- DATABASE SAVE ---
        if (!empty($data['id'])) {
            // UPDATE
            $id = intval($data['id']);
            // Security check: ensure user owns note
            $check = mysqli_query($conn, "SELECT id FROM notes WHERE id=$id AND user_id=$uid");
            if (mysqli_num_rows($check) == 0)
                return false;

            $stmt = $conn->prepare("UPDATE notes SET title=?, category=?, is_pinned=?, is_archived=?, date_last=NOW() WHERE id=?");
            $stmt->bind_param("ssiii", $title, $category, $is_pinned, $is_archived, $id);
            $stmt->execute();

            // Update Page Content
            $stmt_p = $conn->prepare("UPDATE pages SET text=? WHERE note_id=?");
            $stmt_p->bind_param("si", $text, $id);
            $stmt_p->execute();

            return $id;
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, category, is_pinned, is_archived, date_created, date_last) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("issii", $uid, $title, $category, $is_pinned, $is_archived);
            $stmt->execute();
            $new_id = $stmt->insert_id;

            $stmt_p = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
            $stmt_p->bind_param("is", $new_id, $text);
            $stmt_p->execute();

            return $new_id;
        }

    } else {
        // --- SESSION SAVE ---
        if (!isset($_SESSION['guest_notes']))
            $_SESSION['guest_notes'] = [];

        $id = !empty($data['id']) ? $data['id'] : 'guest_' . uniqid();

        // If updating, merge with existing created_date
        $created = date('Y-m-d H:i:s');
        if (isset($_SESSION['guest_notes'][$id])) {
            $created = $_SESSION['guest_notes'][$id]['date_created'];
        }

        $note_obj = [
            'id' => $id,
            'user_id' => 0,
            'title' => $title,
            'category' => $category,
            'text' => $text,
            'is_pinned' => $is_pinned,
            'is_archived' => $is_archived,
            'date_created' => $created,
            'date_last' => date('Y-m-d H:i:s')
        ];

        $_SESSION['guest_notes'][$id] = $note_obj;
        return $id;
    }
}

/**
 * Delete a note
 */
function delete_note($id)
{
    global $conn;
    $uid = get_current_user_id();

    if (is_logged_in()) {
        $id = intval($id);
        $sql = "DELETE FROM notes WHERE id=$id AND user_id=$uid";
        return mysqli_query($conn, $sql);
    } else {
        if (isset($_SESSION['guest_notes'][$id])) {
            unset($_SESSION['guest_notes'][$id]);
            return true;
        }
        return false;
    }
}

/**
 * Migrate Session Notes to Database (Call after Login/Register)
 */
function migrate_guest_data_to_db($user_id)
{
    global $conn;

    if (isset($_SESSION['guest_notes']) && !empty($_SESSION['guest_notes'])) {
        foreach ($_SESSION['guest_notes'] as $note) {
            // Insert into DB
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, category, is_pinned, is_archived, date_created, date_last) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ississs", $user_id, $note['title'], $note['category'], $note['is_pinned'], $note['is_archived'], $note['date_created'], $note['date_last']);
            $stmt->execute();
            $new_id = $stmt->insert_id;

            $stmt_p = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
            $stmt_p->bind_param("is", $new_id, $note['text']);
            $stmt_p->execute();
        }

        // Clear session data
        unset($_SESSION['guest_notes']);
    }
}

/**
 * Get Categories for current user (Defaults + Custom)
 */
function get_categories()
{
    global $conn;
    $uid = get_current_user_id();
    $categories = [];

    // Hardcoded defaults for fallback/consistency
    $defaults = [
        ['name' => 'General', 'color' => '#fff9c4'],
        ['name' => 'Personal', 'color' => '#e8f5e9'],
        ['name' => 'Work', 'color' => '#e3f2fd'],
        ['name' => 'Study', 'color' => '#fce4ec'],
        ['name' => 'Ideas', 'color' => '#f3e5f5']
    ];

    if (is_logged_in()) {
        // Fetch Defaults (user_id=0) AND User's (user_id=$uid)
        $sql = "SELECT * FROM categories WHERE user_id = 0 OR user_id = $uid ORDER BY user_id ASC, id ASC";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    } else {
        // Guest: Just defaults + Maybe session-based custom?
        $categories = $defaults;

        // Basic session support for guest categories (optional enhancement)
        if (isset($_SESSION['guest_cats'])) {
            foreach ($_SESSION['guest_cats'] as $c) {
                $categories[] = $c;
            }
        }
    }

    // Fallback if DB is empty
    if (empty($categories))
        $categories = $defaults;

    return $categories;
}

/**
 * Add a custom category
 */
function add_category($name, $color)
{
    global $conn;
    $uid = get_current_user_id();

    if (is_logged_in()) {
        $name = strip_tags($name); // Basic Sanitization
        $name_esc = mysqli_real_escape_string($conn, $name);
        $color_esc = mysqli_real_escape_string($conn, $color);

        // Check duplicate name for this user
        $check = mysqli_query($conn, "SELECT id FROM categories WHERE user_id=$uid AND name='$name_esc'");
        if (mysqli_num_rows($check) > 0)
            return false;

        $sql = "INSERT INTO categories (user_id, name, color) VALUES ($uid, '$name_esc', '$color_esc')";
        return mysqli_query($conn, $sql);
    } else {
        // Guest Session Category
        if (!isset($_SESSION['guest_cats']))
            $_SESSION['guest_cats'] = [];
        // Check dup
        foreach ($_SESSION['guest_cats'] as $c) {
            if ($c['name'] == $name)
                return false;
        }
        $_SESSION['guest_cats'][] = ['name' => $name, 'color' => $color, 'user_id' => 0];
        return true;
    }
}
/**
 * Delete a custom category
 */
function delete_category($name)
{
    global $conn;
    $uid = get_current_user_id();

    if (is_logged_in()) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        // Only delete if it belongs to this user
        $sql = "DELETE FROM categories WHERE user_id=$uid AND name='$name_esc'";
        return mysqli_query($conn, $sql);
    } else {
        // Guest
        if (isset($_SESSION['guest_cats'])) {
            foreach ($_SESSION['guest_cats'] as $k => $c) {
                if ($c['name'] == $name) {
                    unset($_SESSION['guest_cats'][$k]);
                    return true;
                }
            }
        }
        return false;
    }
}
?>