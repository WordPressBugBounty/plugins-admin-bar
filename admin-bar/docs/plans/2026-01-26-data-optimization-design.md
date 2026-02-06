# Admin Bar Editor - Data Optimization Design

## Problem

The plugin stores excessive data under `_jltadminbar_settings`:
- Full `$wp_admin_bar` objects on every page load
- Duplicate `*_default` fields for every property
- `existing_admin_bar` reconstructed but still stored
- `title_encoded`, `title_clean` variants that can be computed
- Everything bundled in one massive option

**Current storage:** ~50-100KB per site
**Target storage:** ~5-10KB per site (~90% reduction)

---

## New Data Structure

### Option: `_jltadminbar_menus`

```php
[
    'backend' => [
        'wp-logo' => [
            'menu_status' => false,
            'hidden_for' => ['editor'],
            'title' => 'Custom Title',    // only if changed
            'icon' => 'custom-icon.png',  // only if changed
            'href' => '/custom-url',      // only if changed/custom
            'parent' => 'top-secondary',  // only if changed
            'newly_created' => 1          // only for custom items
        ],
        'my-account' => [
            'menu_status' => true,
            'hidden_for' => []
        ]
    ],
    'frontend' => [
        // Same structure
    ],
    'order' => [
        'backend' => ['wp-logo', 'site-name', 'my-account'],
        'frontend' => ['wp-logo', 'my-account']
    ]
]
```

**Rules:**
- Every item has: `menu_status`, `hidden_for`
- Optional fields only present if set: `title`, `icon`, `href`, `parent`, `newly_created`
- Order is a simple array of IDs

### Option: `_jltadminbar_styles`

```php
[
    'position' => 'top',
    'bg_type' => 'color',
    'bg_color' => '#1d2327',
    'bg_gradient' => [
        'first' => '#1d2327',
        'second' => '#2c3338',
        'direction' => '90deg'
    ],
    'text_color' => '#f0f0f1',
    'text_hover' => '#72aee6',
    'text_bg_hover' => '#2c3338',
    'dropdown_bg' => '#2c3338',
    'dropdown_link' => '#f0f0f1',
    'dropdown_hover' => '#72aee6',
    'user_offcanvas_menu' => true
]
```

### Option: `_jltadminbar_disable`

```php
[
    'backend' => false,
    'backend_conditions' => [],
    'frontend' => false,
    'frontend_conditions' => [],
    'frontend_all_users' => false,
    'frontend_guest_users' => false
]
```

### Option: `_jltadminbar_hash`

```php
[
    'backend' => 'a1b2c3d4...',   // md5 of current WP menu IDs
    'frontend' => 'e5f6g7h8...'
]
```

---

## What Gets Removed

| Currently Stored | Action | Reason |
|------------------|--------|--------|
| `existing_admin_bar` | DELETE | Generate fresh via API |
| `existing_admin_bar_frontend` | DELETE | Generate fresh via API |
| `previous_admin_bar_backend` | REPLACE | Use hash instead |
| `previous_admin_bar_frontend` | REPLACE | Use hash instead |
| `*_default` fields | DELETE | Compute from WP live |
| `title_encoded`, `title_clean` | DELETE | Compute at render time |
| `id_default`, `parent_default`, `group_default` | DELETE | Not needed |
| `meta`, `meta_default` | DELETE | Not used by frontend |
| `menu_level` | DELETE | Compute from parent chain |
| `user_roles` in settings | DELETE | Fetch fresh via API |

---

## API Changes

### GET `get-adminbar-menu-items`

```php
[
    'existing' => [
        'backend' => [...],    // Generated FRESH from $wp_admin_bar
        'frontend' => [...]
    ],
    'saved' => get_option('_jltadminbar_menus'),
    'styles' => get_option('_jltadminbar_styles'),
    'disable' => get_option('_jltadminbar_disable'),
    'user_roles' => $this->get_roles_fresh(),
    'is_pro_user' => ...
]
```

### POST `save-adminbar-menu-items`

```php
// Receives:
[
    'menus' => [...],
    'styles' => [...],
    'disable' => [...]
]

// Saves to separate options
update_option('_jltadminbar_menus', $menus);
update_option('_jltadminbar_styles', $styles);
update_option('_jltadminbar_disable', $disable);
```

---

## Core.php Render Flow

```php
// 1. Get saved menus (small, fast)
$menus = get_option('_jltadminbar_menus');
$disable = get_option('_jltadminbar_disable');

// 2. Check for new WP items via hash
$current_ids = array_keys($wp_admin_bar->get_nodes());
$current_hash = md5(json_encode($current_ids));
$stored_hash = get_option('_jltadminbar_hash');

if ($current_hash !== $stored_hash['backend']) {
    update_option('_jltadminbar_hash', [
        'backend' => $current_hash,
        'frontend' => $stored_hash['frontend']
    ]);
}

// 3. Apply customizations
foreach ($menus['backend'] as $id => $settings) {
    if (!$settings['menu_status']) {
        $wp_admin_bar->remove_node($id);
        continue;
    }

    // Check hidden_for
    if ($this->is_hidden_for_current_user($settings['hidden_for'])) {
        $wp_admin_bar->remove_node($id);
        continue;
    }

    // Apply custom properties
    $node = $wp_admin_bar->get_node($id);
    if ($node) {
        if (isset($settings['title'])) $node->title = $settings['title'];
        if (isset($settings['icon'])) $node->title = $this->add_icon($settings['icon'], $node->title);
        if (isset($settings['href'])) $node->href = $settings['href'];
        $wp_admin_bar->add_node($node);
    }
}

// 4. Apply order
$this->reorder_nodes($menus['order']['backend']);
```

---

## Files to Modify

### Backend (PHP)

| File | Changes |
|------|---------|
| `Inc/Classes/Core.php` | Remove `existing_admin_bar` storage, add hash-based detection, simplify render |
| `Inc/Classes/AdminBarEditorApiEndPoints.php` | Split save/get into separate options, generate `existing` fresh |
| `Inc/Classes/AdminBarEditorOptions.php` | Update to read from new option keys |

### Frontend (React)

| File | Changes |
|------|---------|
| `dev/admin-bar-editor/context.js` | Update state to match new API response |
| `dev/admin-bar-editor/components/SaveSettings.js` | Send split payload |

---

## Migration Strategy

```php
// In Inc/Upgrades/upgrade-x.x.x.php
function migrate_to_optimized_structure() {
    $old = get_option('_jltadminbar_settings');
    if (!$old) return;

    // Transform menu data
    $menus = [
        'backend' => [],
        'frontend' => [],
        'order' => ['backend' => [], 'frontend' => []]
    ];

    // Extract saved items, strip defaults
    if (!empty($old['saved_admin_bar'])) {
        foreach ($old['saved_admin_bar'] as $item) {
            $menus['backend'][$item['id']] = [
                'menu_status' => $item['menu_status'] ?? true,
                'hidden_for' => $item['hidden_for'] ?? []
            ];
            // Add optional fields only if set
            if (!empty($item['newly_created'])) {
                $menus['backend'][$item['id']]['newly_created'] = 1;
                $menus['backend'][$item['id']]['title'] = $item['title'];
                $menus['backend'][$item['id']]['href'] = $item['href'];
            }
            if (!empty($item['icon']) && $item['icon'] !== ($item['icon_default'] ?? '')) {
                $menus['backend'][$item['id']]['icon'] = $item['icon'];
            }
            $menus['order']['backend'][] = $item['id'];
        }
    }

    // Same for frontend...

    // Extract styles
    $styles = $old['admin_bar_settings'] ?? [];

    // Extract disable options
    $disable = [
        'backend' => $old['disable_backend_admin_bar'] ?? false,
        'backend_conditions' => $old['disable_backend_conditions'] ?? [],
        'frontend' => $old['disable_frontend_admin_bar'] ?? false,
        'frontend_conditions' => $old['disable_frontend_conditions'] ?? [],
        'frontend_all_users' => $old['disable_frontend_all_users'] ?? false,
        'frontend_guest_users' => $old['disable_frontend_guest_users'] ?? false
    ];

    // Save new options
    update_option('_jltadminbar_menus', $menus);
    update_option('_jltadminbar_styles', $styles);
    update_option('_jltadminbar_disable', $disable);

    // Backup old, then delete
    update_option('_jltadminbar_settings_backup', $old);
    delete_option('_jltadminbar_settings');

    // Clean up related options
    delete_option('previous_admin_bar_backend');
    delete_option('previous_admin_bar_frontend');
    delete_option('adminbar_frontend_items');
}
```

---

## Summary

| Aspect | Before | After |
|--------|--------|-------|
| Options | 1 massive option | 4 focused options |
| Storage size | ~50-100KB | ~5-10KB |
| Page load DB writes | Every page | Only on hash change |
| Default values | Stored duplicates | Computed from WP |
| Existing menus | Stored | Generated on API call |

## Reset Functionality

Reset to defaults works by:
1. Remove the item's entry from `_jltadminbar_menus`
2. WordPress's live `$wp_admin_bar` provides the default automatically
3. No stored defaults needed
