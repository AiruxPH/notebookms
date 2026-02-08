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
        // Subquery for page count
        $sql = "SELECT n.*, p.text, c.name as category_name, c.color as category_color, 
                (SELECT COUNT(*) FROM pages WHERE note_id = n.note_id) as page_count
                FROM notes n 
                LEFT JOIN pages p ON n.note_id = p.note_id AND p.page_number = 1 
                LEFT JOIN categories c ON n.category_id = c.category_id
                WHERE n.user_id = $uid AND n.is_archived = $archived";

        if ($category) {
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
            // Map NEW ID to OLD ID key for compatibility if frontend uses 'id'
            // BUT requirement 2 says: "Update all PHP code ... change $row['id'] to $row['note_id']"
            // So we will stick to returning the raw row, and update frontend files to use note_id.
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

            // Guest Page Count
            $n['page_count'] = isset($n['pages']) ? count($n['pages']) : 1;

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
        $sql = "SELECT n.*, p.text, c.name as category_name, c.category_id as category_id_val 
                FROM notes n 
                LEFT JOIN pages p ON n.note_id = p.note_id 
                LEFT JOIN categories c ON n.category_id = c.category_id
                WHERE n.note_id = $id_esc AND n.user_id = $uid 
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
    $page_number = !empty($data['page_number']) ? intval($data['page_number']) : 1;

    if (is_logged_in()) {
        $cat_id = intval($category_val);

        if (!empty($data['note_id'])) {
            // UPDATE
            $id = intval($data['note_id']);
            $check = mysqli_query($conn, "SELECT note_id FROM notes WHERE note_id=$id AND user_id=$uid");
            if (mysqli_num_rows($check) == 0)
                return false;

            $stmt = $conn->prepare("UPDATE notes SET title=?, category_id=?, is_pinned=?, is_archived=?, reminder_date=?, date_last=NOW() WHERE note_id=?");
            $stmt->bind_param("siiisi", $title, $cat_id, $is_pinned, $is_archived, $reminder_date, $id);
            $stmt->execute();

            // Save Pages (Bulk or Single)
            if (isset($data['pages_json']) && is_string($data['pages_json'])) {
                $pages_array = json_decode($data['pages_json'], true);
                if (is_array($pages_array)) {
                    // DELETE EXISTING PAGES FIRST (to support deletion/re-indexing)
                    mysqli_query($conn, "DELETE FROM pages WHERE note_id = $id");

                    foreach ($pages_array as $p_num => $p_text) {
                        save_note_page($id, intval($p_num), $p_text);
                    }
                }
            } else {
                save_note_page($id, $page_number, $text);
            }

            return $id;
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, category_id, is_pinned, is_archived, reminder_date, date_created, date_last) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("isiiis", $uid, $title, $cat_id, $is_pinned, $is_archived, $reminder_date);
            $stmt->execute();
            $new_id = $stmt->insert_id;

            // Save Pages (Bulk or Single)
            if (isset($data['pages_json']) && is_string($data['pages_json'])) {
                $pages_array = json_decode($data['pages_json'], true);
                if (is_array($pages_array)) {
                    // No need to delete yet as it's a new insert, but good practice if we ever change flow
                    foreach ($pages_array as $p_num => $p_text) {
                        save_note_page($new_id, intval($p_num), $p_text);
                    }
                }
            } else {
                save_note_page($new_id, 1, $text);
            }

            return $new_id;
        }

    } else {
        // --- SESSION SAVE (GUEST) ---
        if (!isset($_SESSION['guest_notes']))
            $_SESSION['guest_notes'] = [];

        $id = !empty($data['id']) ? $data['id'] : 'guest_' . uniqid();
        $created = date('Y-m-d H:i:s');

        // Initialize or Retrieve Existing Pages
        $existing_pages = [];
        $existing_text = "";

        if (isset($_SESSION['guest_notes'][$id])) {
            $created = $_SESSION['guest_notes'][$id]['date_created'];
            $existing_pages = $_SESSION['guest_notes'][$id]['pages'] ?? [];
            $existing_text = $_SESSION['guest_notes'][$id]['text'] ?? "";

            // Migration: if pages empty but text exists, put text in page 1
            if (empty($existing_pages) && !empty($existing_text)) {
                $existing_pages[1] = $existing_text;
            }
        }

        // Update Pages (Bulk or Single)
        if (isset($data['pages_json']) && is_string($data['pages_json'])) {
            $pages_array = json_decode($data['pages_json'], true);
            if (is_array($pages_array)) {
                // For Guests: Replace array entirely to support deletion/re-indexing
                $existing_pages = [];
                foreach ($pages_array as $p_num => $p_text) {
                    $existing_pages[intval($p_num)] = $p_text;
                }
            }
        } else {
            $existing_pages[$page_number] = $text;
        }

        // Update Text (Page 1) for compatibility
        if (isset($existing_pages[1])) {
            $existing_text = $existing_pages[1];
        } else if (!empty($text) && $page_number == 1) {
            $existing_text = $text;
        }

        $note_obj = [
            'id' => $id,
            'user_id' => 0,
            'title' => $title,
            'category' => $category_val,
            'text' => $existing_text,
            'pages' => $existing_pages,
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
        $sql = "DELETE FROM notes WHERE note_id=$id AND user_id=$uid";
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
                $check = mysqli_query($conn, "SELECT category_id FROM categories WHERE user_id=$user_id AND name='$name'");
                if ($row = mysqli_fetch_assoc($check)) {
                    // Use existing
                    $cat_map['g_' . $idx] = $row['category_id'];
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

            // Migrate Pages
            if (isset($note['pages']) && is_array($note['pages']) && !empty($note['pages'])) {
                // Multi-page migration
                $stmt_p = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, ?, ?)");
                foreach ($note['pages'] as $p_num => $p_text) {
                    $p_num_int = intval($p_num);
                    $stmt_p->bind_param("iis", $new_id, $p_num_int, $p_text);
                    $stmt_p->execute();
                }
            } else {
                // Fallback: Single page from 'text' field
                $stmt_p = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, 1, ?)");
                $stmt_p->bind_param("is", $new_id, $note['text']);
                $stmt_p->execute();
            }
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
    // Hardcoded defaults for fallback/consistency (With IDs now)
    $defaults = [
        ['category_id' => 1, 'name' => 'General', 'color' => '#fff9c4'],
        ['category_id' => 2, 'name' => 'Personal', 'color' => '#e8f5e9'],
        ['category_id' => 3, 'name' => 'Work', 'color' => '#e3f2fd'],
        ['category_id' => 4, 'name' => 'Study', 'color' => '#fce4ec'],
        ['category_id' => 5, 'name' => 'Ideas', 'color' => '#f3e5f5']
    ];

    if (is_logged_in()) {
        // Fetch Defaults (user_id=0) AND User's (user_id=$uid)
        $sql = "SELECT * FROM categories WHERE user_id = 0 OR user_id = $uid ORDER BY user_id ASC, category_id ASC";
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
        $check = mysqli_query($conn, "SELECT category_id FROM categories WHERE user_id=$uid AND name='$name_esc'");
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
        $sql = "DELETE FROM categories WHERE user_id=$uid AND category_id=$id";
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
 * Helper: Save Page (Upsert)
 */
function save_note_page($note_id, $page_number, $text)
{
    global $conn;
    $nid = intval($note_id);
    $p = intval($page_number);

    if (is_logged_in()) {
        // Check availability
        $check = mysqli_query($conn, "SELECT note_id FROM pages WHERE note_id=$nid AND page_number=$p");
        if (mysqli_num_rows($check) > 0) {
            $stmt = $conn->prepare("UPDATE pages SET text=? WHERE note_id=? AND page_number=?");
            $stmt->bind_param("sii", $text, $nid, $p);
            return $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO pages (note_id, page_number, text) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $nid, $p, $text);
            return $stmt->execute();
        }
    } else {
        // Guest
        if (!isset($_SESSION['guest_notes'])) {
            $_SESSION['guest_notes'] = [];
        }

        // Guest notes use string IDs, so $note_id is already the correct key
        if (isset($_SESSION['guest_notes'][$note_id])) {
            $note = &$_SESSION['guest_notes'][$note_id];

            // Initialize 'pages' if it doesn't exist or is empty
            if (!isset($note['pages']) || !is_array($note['pages'])) {
                $note['pages'] = [];
                // If 'text' exists, migrate it to page 1
                if (isset($note['text']) && !empty($note['text'])) {
                    $note['pages'][1] = $note['text'];
                }
            }

            // Update the specific page
            $note['pages'][$p] = $text;

            // Keep 'text' field in sync with page 1 for backward compatibility
            if ($p == 1) {
                $note['text'] = $text;
            }

            $note['date_last'] = date('Y-m-d H:i:s');
            return true;
        }
        return false; // Note not found
    }
}

/**
 * Permanently Delete Note (Archived Only)
 */
function delete_note_permanently($note_id)
{
    global $conn;
    $uid = get_current_user_id();
    $nid = intval($note_id);

    if (is_logged_in()) {
        // Verify ownership and verify it is archived (safety check)
        $check = mysqli_query($conn, "SELECT note_id FROM notes WHERE note_id = $nid AND user_id = $uid AND is_archived = 1");
        if (mysqli_num_rows($check) == 0)
            return false;

        // Delete Pages
        mysqli_query($conn, "DELETE FROM pages WHERE note_id = $nid");

        // Delete Note
        return mysqli_query($conn, "DELETE FROM notes WHERE note_id = $nid");
    } else {
        // Guest
        if (isset($_SESSION['guest_notes'][$note_id])) { // Use string ID
            // Check archived status
            if (isset($_SESSION['guest_notes'][$note_id]['is_archived']) && $_SESSION['guest_notes'][$note_id]['is_archived'] == 1) {
                unset($_SESSION['guest_notes'][$note_id]);
                return true;
            }
        }
        return false;
    }
}

/**
 * Get Total Pages for a Note
 */
function get_note_page_count($note_id)
{
    global $conn;
    $nid = intval($note_id);
    if (is_logged_in()) {
        $res = mysqli_query($conn, "SELECT MAX(page_number) as max_p FROM pages WHERE note_id = $nid");
        $row = mysqli_fetch_assoc($res);
        return $row['max_p'] ? intval($row['max_p']) : 1;
    } else {
        // Guest
        if (isset($_SESSION['guest_notes'][$note_id])) { // Use original ID (string)
            $pages = $_SESSION['guest_notes'][$note_id]['pages'] ?? [];
            if (empty($pages))
                return 1;
            return max(array_keys($pages));
        }
        return 1;
    }
}

/**
 * Get Specific Page Content
 */
function get_note_page($note_id, $page_num)
{
    global $conn;
    $nid = intval($note_id);
    $p = intval($page_num);

    if (is_logged_in()) {
        $res = mysqli_query($conn, "SELECT text FROM pages WHERE note_id = $nid AND page_number = $p");
        if ($row = mysqli_fetch_assoc($res)) {
            return $row['text'];
        }
        return ""; // Empty if page doesn't exist yet (or error)
    } else {
        // Guest
        if (isset($_SESSION['guest_notes'][$note_id])) { // Use original ID
            $pages = $_SESSION['guest_notes'][$note_id]['pages'] ?? [];
            if (isset($pages[$p]))
                return $pages[$p];
            // Fallback to text if page 1
            if ($p == 1)
                return $_SESSION['guest_notes'][$note_id]['text'] ?? "";
        }
        return "";
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
        $check = mysqli_query($conn, "SELECT category_id FROM categories WHERE user_id=$uid AND name='$name_esc' AND category_id != $id");
        if (mysqli_num_rows($check) > 0)
            return 0; // Duplicate

        $sql = "UPDATE categories SET name='$name_esc', color='$color_esc' WHERE user_id=$uid AND category_id=$id";
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
/**
 * Get All Users (Admin) - Enhanced
 */
function get_all_users($search = '', $role = '', $status = '', $sort = 'id', $order = 'ASC', $page = 1, $limit = 10)
{
    global $conn;
    if (!is_admin())
        return ['users' => [], 'total' => 0];

    // Defaults / Sanitization
    $page = max(1, intval($page));
    $limit = max(1, intval($limit));
    $offset = ($page - 1) * $limit;
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

    // Allowed Sort Columns
    $allowed_sorts = ['user_id', 'username', 'role', 'date_created', 'is_active'];
    if (!in_array($sort, $allowed_sorts))
        $sort = 'user_id';

    // Base Header
    $sql = "SELECT u.user_id, u.username, u.role, u.is_active, u.date_created, 
            (SELECT COUNT(*) FROM notes n WHERE n.user_id = u.user_id) as note_count 
            FROM users u WHERE 1=1";

    // Filters
    if (!empty($search)) {
        $q = mysqli_real_escape_string($conn, $search);
        $sql .= " AND u.username LIKE '%$q%'";
    }
    if ($role !== '') {
        $r = mysqli_real_escape_string($conn, $role);
        $sql .= " AND u.role = '$r'";
    }
    if ($status !== '') {
        $s = intval($status);
        $sql .= " AND u.is_active = $s";
    }

    // Count Total (before limit)
    $count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as sub"; // Wrap simple approach or regex replace
    // Better: Regex replace SELECT ... FROM with SELECT COUNT(*) FROM
    // But since subquery for note_count is there, simpler to just count rows of result or optimized query
    // Let's use a separate count query for performance
    $count_sql_main = "SELECT COUNT(*) as total FROM users u WHERE 1=1";
    if (!empty($search)) {
        $q = mysqli_real_escape_string($conn, $search);
        $count_sql_main .= " AND u.username LIKE '%$q%'";
    }
    if ($role !== '') {
        $r = mysqli_real_escape_string($conn, $role);
        $count_sql_main .= " AND u.role = '$r'";
    }
    if ($status !== '') {
        $s = intval($status);
        $count_sql_main .= " AND u.is_active = $s";
    }

    $count_res = mysqli_query($conn, $count_sql_main);
    $total_row = mysqli_fetch_assoc($count_res);
    $total = $total_row['total'];

    // Add Order and Limit
    $sql .= " ORDER BY u.$sort $order, u.user_id ASC LIMIT $limit OFFSET $offset";

    $result = mysqli_query($conn, $sql);
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    return ['users' => $users, 'total' => $total];
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

    $sql = "UPDATE users SET is_active = $new_status WHERE user_id = $uid";
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
    $sql = "UPDATE users SET security_word = '$word_esc', security_word_set = 1 WHERE user_id = $uid";
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
        $db_word = isset($row['security_word']) ? trim($row['security_word']) : '';
        if ($db_word !== '' && strtolower($db_word) === strtolower($word)) {
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
 * Update Username
 */
function update_username($user_id, $new_username)
{
    global $conn;
    $uid = intval($user_id);
    $username = mysqli_real_escape_string($conn, trim($new_username));

    // Check availability first (redundant safe-check)
    $check = mysqli_query($conn, "SELECT user_id FROM users WHERE username='$username' AND user_id != $uid");
    if (mysqli_num_rows($check) > 0) {
        return false; // Already taken
    }

    $sql = "UPDATE users SET username = '$username' WHERE user_id = $uid";
    if (mysqli_query($conn, $sql)) {
        // Update session
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        $_SESSION['username'] = $username;
        return true;
    }
    return false;
}

/**
 * Check if user has security word set (for Dashboard)
 */
function has_security_word_set($user_id)
{
    global $conn;
    $uid = intval($user_id);
    $result = mysqli_query($conn, "SELECT security_word_set FROM users WHERE user_id=$uid");
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['security_word_set'] == 1;
    }
    return false;
}

/**
 * Get ALL pages for a specific note (for JSON payload)
 */
function get_all_note_pages($note_id)
{
    global $conn;
    $nid = intval($note_id);
    $pages = [];

    if (is_logged_in()) {
        $result = mysqli_query($conn, "SELECT page_number, text FROM pages WHERE note_id=$nid ORDER BY page_number ASC");
        while ($row = mysqli_fetch_assoc($result)) {
            $pages[intval($row['page_number'])] = $row['text'];
        }
    } else {
        // Guest
        if (isset($_SESSION['guest_notes'][$note_id])) {
            $n = $_SESSION['guest_notes'][$note_id];
            $pages = isset($n['pages']) ? $n['pages'] : [];

            // Backup text field check
            if (empty($pages) && !empty($n['text'])) {
                $pages[1] = $n['text'];
            }
        }
    }

    // Ensure Page 1 exists
    if (empty($pages)) {
        $pages[1] = "";
    }

    return $pages;
}

/**
 * Get user data by username
 */
function get_user_by_username($username)
{
    global $conn;
    $username = mysqli_real_escape_string($conn, $username);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' LIMIT 1");
    return mysqli_fetch_assoc($result);
}

/**
 * Migrate all data (notes, categories) from source user to target user
 */
function migrate_user_data($source_uid, $target_uid)
{
    global $conn;
    $source_uid = intval($source_uid);
    $target_uid = intval($target_uid);

    if ($source_uid === $target_uid)
        return false;

    mysqli_begin_transaction($conn);
    try {
        // 1. Migrate Categories (excluding defaults which are user_id=0)
        mysqli_query($conn, "UPDATE categories SET user_id = $target_uid WHERE user_id = $source_uid");

        // 2. Migrate Notes
        // We also need to update category_id in notes if we want to be super precise, 
        // but since we moved categories, their IDs remain the same, just owned by target user now.
        mysqli_query($conn, "UPDATE notes SET user_id = $target_uid WHERE user_id = $source_uid");

        mysqli_commit($conn);
        return true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Get a summary of user data for migration preview
 */
function get_user_migration_summary($uid)
{
    global $conn;
    $uid = intval($uid);

    // Get Notes
    $notes_res = mysqli_query($conn, "SELECT title FROM notes WHERE user_id = $uid");
    $notes = [];
    while ($row = mysqli_fetch_assoc($notes_res)) {
        $notes[] = $row['title'];
    }

    // Get Category Count
    $cats_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM categories WHERE user_id = $uid");
    $cats_row = mysqli_fetch_assoc($cats_res);
    $cat_count = $cats_row['total'];

    return [
        'notes' => $notes,
        'note_count' => count($notes),
        'category_count' => $cat_count
    ];
}
?>