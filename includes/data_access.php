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
 * Check if current user is admin
 */
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
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
        // JOIN categories to get name and color
        $sql = "SELECT n.*, p.text, c.name as category_name, c.color as category_color 
                FROM notes n 
                LEFT JOIN pages p ON n.id = p.note_id AND p.page_number = 1 
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE n.user_id = $uid AND n.is_archived = $archived";

        if ($category) {
            // Filter by ID if numeric, otherwise strict check? 
            // We assume filters now send ID based on plan.
            $cat_id_esc = intval($category);
            $sql .= " AND n.category_id = $cat_id_esc";
        }
        if ($search) {
            $q_esc = mysqli_real_escape_string($conn, $search);
            $sql .= " AND (n.title LIKE '%$q_esc%' OR p.text LIKE '%$q_esc%')";
        }

        $sql .= " ORDER BY n.is_pinned DESC, n.date_last DESC";

        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            // Map back for compatibility if needed, or prefer frontend update
            $row['category'] = $row['category_name'] ? $row['category_name'] : 'General';
            $notes[] = $row;
        }
    } else {
        // --- SESSION FETCH (GUEST) ---
        if (!isset($_SESSION['guest_notes'])) {
            $_SESSION['guest_notes'] = [];
        }
        $all_guest = $_SESSION['guest_notes'];

        // Build Map for IDs
        $cats = get_categories();
        $cat_map = [];
        foreach ($cats as $c) {
            $cat_map[$c['id']] = $c;
        }

        foreach ($all_guest as $n) {
            // Apply Filters
            if (isset($n['is_archived']) && $n['is_archived'] != $archived)
                continue;

            // Resolve Category Data
            $cat_id = $n['category'];
            $cat_data = isset($cat_map[$cat_id]) ? $cat_map[$cat_id] : ['id' => 1, 'name' => 'General', 'color' => '#ffffff'];

            // Inject resolved data into note row for display
            $n['category'] = $cat_data['name'];
            $n['category_name'] = $cat_data['name'];
            $n['category_color'] = $cat_data['color'];
            $n['category_id'] = $cat_data['id']; // useful for edit

            if ($category && $n['category_id'] != $category)
                continue;

            if ($search) {
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
        $sql = "SELECT n.*, p.text, c.name as category_name, c.id as category_id_val 
                FROM notes n 
                LEFT JOIN pages p ON n.id = p.note_id 
                LEFT JOIN categories c ON n.category_id = c.id
                WHERE n.id = $id_esc AND n.user_id = $uid 
                LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        if ($row) {
            $row['category'] = $row['category_name']; // Back-compat
            $row['category_id'] = $row['category_id_val'];
        }
        return $row;
    } else {
        // Session
        if (isset($_SESSION['guest_notes'][$id])) {
            $note = $_SESSION['guest_notes'][$id];
            // Resolve Category
            $cats = get_categories();
            foreach ($cats as $c) {
                if ($c['id'] == $note['category']) { // Match ID
                    $note['category'] = $c['name']; // Back-compat name
                    $note['category_id'] = $c['id'];
                    break;
                }
            }
            return $note;
        }
        return null;
    }
}

/**
 * Save a note (Insert or Update)
 */
function save_note($data)
{
    global $conn;
    $uid = get_current_user_id();

    // Strip emojis from Title
    $title = preg_replace('/[\x{1F300}-\x{1F5FF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{1F191}-\x{1F251}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{3030}\x{2B50}\x{2B55}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}]/u', '', $data['title']);

    // $category is now an ID for users
    $category_val = $data['category'];
    $text = $data['text'];
    $is_pinned = !empty($data['is_pinned']) ? 1 : 0;
    $is_archived = !empty($data['is_archived']) ? 1 : 0;
    $reminder_date = !empty($data['reminder_date']) ? $data['reminder_date'] : null;

    if (is_logged_in()) {
        $cat_id = intval($category_val);

        if (!empty($data['id'])) {
            // UPDATE
            $id = intval($data['id']);
            $check = mysqli_query($conn, "SELECT id FROM notes WHERE id=$id AND user_id=$uid");
            if (mysqli_num_rows($check) == 0)
                return false;

            $stmt = $conn->prepare("UPDATE notes SET title=?, category_id=?, is_pinned=?, is_archived=?, reminder_date=?, date_last=NOW() WHERE id=?");
            $stmt->bind_param("siiisi", $title, $cat_id, $is_pinned, $is_archived, $reminder_date, $id);
            $stmt->execute();

            $stmt_p = $conn->prepare("UPDATE pages SET text=? WHERE note_id=?");
            $stmt_p->bind_param("si", $text, $id);
            $stmt_p->execute();

            return $id;
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, category_id, is_pinned, is_archived, reminder_date, date_created, date_last) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("isiiis", $uid, $title, $cat_id, $is_pinned, $is_archived, $reminder_date);
            $stmt->execute();
            $new_id = $stmt->insert_id;

            $stmt_p = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
            $stmt_p->bind_param("is", $new_id, $text);
            $stmt_p->execute();

            return $new_id;
        }

    } else {
        // --- SESSION SAVE (GUEST) ---
        if (!isset($_SESSION['guest_notes']))
            $_SESSION['guest_notes'] = [];

        $id = !empty($data['id']) ? $data['id'] : 'guest_' . uniqid();
        $created = date('Y-m-d H:i:s');
        if (isset($_SESSION['guest_notes'][$id])) {
            $created = $_SESSION['guest_notes'][$id]['date_created'];
        }

        $note_obj = [
            'id' => $id,
            'user_id' => 0,
            'title' => $title,
            'category' => $category_val, // For guest, keeping as string name
            'text' => $text,
            'is_pinned' => $is_pinned,
            'is_archived' => $is_archived,
            'reminder_date' => $reminder_date,
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

        // 1. Migrate Custom Categories First
        $cat_map = []; // Maps guest_id (e.g., 'g_0') => new db_id

        if (isset($_SESSION['guest_cats']) && !empty($_SESSION['guest_cats'])) {
            foreach ($_SESSION['guest_cats'] as $idx => $gc) {
                $name = mysqli_real_escape_string($conn, $gc['name']);
                $color = mysqli_real_escape_string($conn, $gc['color']);

                // Check duplicate to avoid error, though user just created logic guarantees unique in session
                // We check against DB just in case they have same name as existing DB category
                $check = mysqli_query($conn, "SELECT id FROM categories WHERE user_id=$user_id AND name='$name'");
                if ($row = mysqli_fetch_assoc($check)) {
                    // Use existing
                    $cat_map['g_' . $idx] = $row['id'];
                } else {
                    // Create new
                    $sql = "INSERT INTO categories (user_id, name, color) VALUES ($user_id, '$name', '$color')";
                    if (mysqli_query($conn, $sql)) {
                        $cat_map['g_' . $idx] = mysqli_insert_id($conn);
                    }
                }
            }
            // Clear guest cats
            unset($_SESSION['guest_cats']);
        }

        // 2. Migrate Notes
        foreach ($_SESSION['guest_notes'] as $note) {
            // Mapping Guest Category ID to valid DB Category ID
            $guest_cat = $note['category'];
            $final_cat_id = 1; // Default to General

            if (is_numeric($guest_cat) && $guest_cat <= 5) {
                // It's a default category, safe to use directly
                $final_cat_id = $guest_cat;
            } elseif (isset($cat_map[$guest_cat])) {
                // It's a mapped custom category
                $final_cat_id = $cat_map[$guest_cat];
            } else {
                // Fallback to General if mapping failed or unknown
                $final_cat_id = 1;
            }

            // Insert into DB
            $reminder_date = isset($note['reminder_date']) ? $note['reminder_date'] : null;

            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, category_id, is_pinned, is_archived, reminder_date, date_created, date_last) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isiiisss", $user_id, $note['title'], $final_cat_id, $note['is_pinned'], $note['is_archived'], $reminder_date, $note['date_created'], $note['date_last']);
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

    // Hardcoded defaults for fallback/consistency (With IDs now)
    $defaults = [
        ['id' => 1, 'name' => 'General', 'color' => '#fff9c4'],
        ['id' => 2, 'name' => 'Personal', 'color' => '#e8f5e9'],
        ['id' => 3, 'name' => 'Work', 'color' => '#e3f2fd'],
        ['id' => 4, 'name' => 'Study', 'color' => '#fce4ec'],
        ['id' => 5, 'name' => 'Ideas', 'color' => '#f3e5f5']
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

        // Basic session support for guest categories
        if (isset($_SESSION['guest_cats'])) {
            foreach ($_SESSION['guest_cats'] as $i => $c) {
                // Assign a temp ID for guest custom cats
                $c['id'] = 'g_' . $i;
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

    $MAX_CATS = 20;

    if (is_logged_in()) {
        $name = strip_tags($name);
        // Strip Emojis from Category Name
        $name = preg_replace('/[\x{1F300}-\x{1F5FF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{1F191}-\x{1F251}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{3030}\x{2B50}\x{2B55}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}]/u', '', $name);

        // Enforce 30 char limit
        if (mb_strlen($name) > 30) {
            $name = mb_substr($name, 0, 30);
        }

        $name_esc = mysqli_real_escape_string($conn, $name);
        $color_esc = mysqli_real_escape_string($conn, $color);

        // Check Count
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as c FROM categories WHERE user_id=$uid");
        $row = mysqli_fetch_assoc($count_res);
        if ($row['c'] >= $MAX_CATS) {
            return -1; // Limit Reached
        }

        // Check duplicate
        $check = mysqli_query($conn, "SELECT id FROM categories WHERE user_id=$uid AND name='$name_esc'");
        if (mysqli_num_rows($check) > 0)
            return 0; // Duplicate

        $sql = "INSERT INTO categories (user_id, name, color) VALUES ($uid, '$name_esc', '$color_esc')";
        return mysqli_query($conn, $sql) ? 1 : 0;
    } else {
        // Guest
        if (!isset($_SESSION['guest_cats']))
            $_SESSION['guest_cats'] = [];

        if (count($_SESSION['guest_cats']) >= $MAX_CATS) {
            return -1; // Limit Reached
        }

        foreach ($_SESSION['guest_cats'] as $c) {
            if ($c['name'] == $name)
                return 0; // Duplicate
        }
        $_SESSION['guest_cats'][] = ['name' => $name, 'color' => $color, 'user_id' => 0];
        return 1;
    }
}
/**
 * Delete a custom category
 */
function delete_category($id)
{
    global $conn;
    $uid = get_current_user_id();

    // PROTECTION: Never delete default categories (1-5)
    if (is_numeric($id) && intval($id) <= 5) {
        return false;
    }

    if (is_logged_in()) {
        $id = intval($id);

        // MIGRATION: Move notes to General (1) before deleting
        mysqli_query($conn, "UPDATE notes SET category_id = 1 WHERE category_id = $id AND user_id = $uid");

        // Only delete if it belongs to this user
        $sql = "DELETE FROM categories WHERE user_id=$uid AND id=$id";
        return mysqli_query($conn, $sql);
    } else {
        // Guest
        if (isset($_SESSION['guest_cats'])) {
            // guest_id is like 'g_0'
            $idx = str_replace('g_', '', $id);
            if (isset($_SESSION['guest_cats'][$idx])) {
                $cname = $_SESSION['guest_cats'][$idx]['name'];

                // MIGRATION: Move guest notes to General (1)
                if (isset($_SESSION['guest_notes'])) {
                    foreach ($_SESSION['guest_notes'] as &$n) {
                        if ($n['category'] == $id) {
                            $n['category'] = 1;
                        }
                    }
                }

                unset($_SESSION['guest_cats'][$idx]);
                // Re-index to keep IDs consistent if they are based on index
                $_SESSION['guest_cats'] = array_values($_SESSION['guest_cats']);
                return true;
            }
        }
        return false;
    }
}

/**
 * Update a custom category
 */
function update_category($id, $name, $color)
{
    global $conn;
    $uid = get_current_user_id();

    // PROTECTION: Never edit default categories (1-5)
    if (is_numeric($id) && intval($id) <= 5) {
        return false;
    }

    $name = strip_tags($name);
    // Strip Emojis
    $name = preg_replace('/[\x{1F300}-\x{1F5FF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F1E6}-\x{1F1FF}\x{1F191}-\x{1F251}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{3030}\x{2B50}\x{2B55}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{3297}\x{3299}]/u', '', $name);

    // Limit name to 30 (User Request)
    if (mb_strlen($name) > 30) {
        $name = mb_substr($name, 0, 30);
    }

    if (is_logged_in()) {
        $id = intval($id);
        $name_esc = mysqli_real_escape_string($conn, $name);
        $color_esc = mysqli_real_escape_string($conn, $color);

        // Check if name taken by another category of same user
        $check = mysqli_query($conn, "SELECT id FROM categories WHERE user_id=$uid AND name='$name_esc' AND id != $id");
        if (mysqli_num_rows($check) > 0)
            return 0; // Duplicate

        $sql = "UPDATE categories SET name='$name_esc', color='$color_esc' WHERE user_id=$uid AND id=$id";
        return mysqli_query($conn, $sql);
    } else {
        // Guest
        if (isset($_SESSION['guest_cats'])) {
            $idx = str_replace('g_', '', $id);
            if (isset($_SESSION['guest_cats'][$idx])) {
                // Check duplicate
                foreach ($_SESSION['guest_cats'] as $i => $c) {
                    if ($i != $idx && $c['name'] == $name)
                        return 0;
                }
                $_SESSION['guest_cats'][$idx]['name'] = $name;
                $_SESSION['guest_cats'][$idx]['color'] = $color;
                return true;
            }
        }
        return false;
    }
}

/**
 * Get All Users (Admin)
 */
function get_all_users()
{
    global $conn;
    if (!is_admin())
        return [];

    $sql = "SELECT u.id, u.username, u.role, u.is_active, u.date_created, 
            (SELECT COUNT(*) FROM notes n WHERE n.user_id = u.id) as note_count 
            FROM users u 
            ORDER BY u.role ASC, u.id ASC";

    $result = mysqli_query($conn, $sql);
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}

/**
 * Toggle User Status (Admin)
 */
function toggle_user_status($user_id, $current_status)
{
    global $conn;
    if (!is_admin())
        return false;

    $uid = intval($user_id);
    // Prevent self-deactivation if ID matches current session
    if ($uid == get_current_user_id())
        return false;

    // Invert status
    $new_status = ($current_status == 1) ? 0 : 1;

    $sql = "UPDATE users SET is_active = $new_status WHERE id = $uid";
    return mysqli_query($conn, $sql);
}

/**
 * Set Security Word
 */
function set_security_word($user_id, $word)
{
    global $conn;
    $uid = intval($user_id);
    $word = trim($word);

    // Store as plain text (or hashed if desired, but user request implies simple matching)
    // We will store case-insensitive match (e.f. lowercase) or just as is? 
    // Plan says "case-insensitive for better UX", so we can store it as is but compare lower.

    $word_esc = mysqli_real_escape_string($conn, $word);
    $sql = "UPDATE users SET security_word = '$word_esc', security_word_set = 1 WHERE id = $uid";
    return mysqli_query($conn, $sql);
}

/**
 * Check Security Word (Forgot Password)
 */
function check_security_word($username, $word)
{
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    $word = trim($word);

    $sql = "SELECT security_word FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        // Case-insensitive comparison
        if (strtolower(trim($row['security_word'])) === strtolower($word)) {
            return true;
        }
    }
    return false;
}

/**
 * Update Password
 */
function update_password($username, $new_password)
{
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    // Plain text as requested
    $password = mysqli_real_escape_string($conn, $new_password);

    $sql = "UPDATE users SET password = '$password' WHERE username = '$username'";
    return mysqli_query($conn, $sql);
}

/**
 * Check if user has security word set (for Dashboard)
 */
function has_security_word_set($user_id)
{
    global $conn;
    $uid = intval($user_id);
    $result = mysqli_query($conn, "SELECT security_word_set FROM users WHERE id=$uid");
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['security_word_set'] == 1;
    }
    return false;
}
?>