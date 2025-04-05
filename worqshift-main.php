<?php
/*
Plugin Name: Worqshift
Description: PHP Error Handling Plugin for WordPress
Version: 1.5
Author: https://github.com/codelyfe/Worqshift
*/

function get_server_disk_info() {
    $total = disk_total_space("/");
    $free = disk_free_space("/");
    $used = $total - $free;
    return [
        'total' => size_format($total),
        'used'  => size_format($used),
        'free'  => size_format($free)
    ];
}

add_action('admin_menu', function() {
    add_menu_page('PHP Error Feed', 'PHP Error Feed', 'manage_options', 'php-error-feed', function() {
        $disk = get_server_disk_info();
        $errorLogPath = WP_CONTENT_DIR . '/php-error-log.json';
        $errors = file_exists($errorLogPath) ? json_decode(file_get_contents($errorLogPath), true) : [];
        $formatted = '';
        foreach ($errors as $key => $item) {
            $formatted .= "Error: {$item['error']}\n";
            $formatted .= "Count: {$item['count']}\n";
            $formatted .= "Last Seen: {$item['time']}\n";
            $formatted .= "--------------------------\n";
        }
        ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.10/codemirror.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.10/codemirror.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.10/mode/javascript/javascript.min.js"></script>

        <div class="wrap container-fluid mt-4">
            <h1 class="mb-4">
                🐘 PHP Error Feed <br/>
                <small class="text-muted fs-6">
                    <?php
                        echo 'PHP: ' . phpversion() . ' | ';
                        echo 'WP: ' . get_bloginfo('version') . ' | ';
                        global $wpdb;
                        echo 'MySQL: ' . $wpdb->db_version() . ' | ';
                        echo "Disk Used: {$disk['used']} / {$disk['total']} (Free: {$disk['free']})";
                    ?>
                </small>
            </h1>

            <?php
            function show_admin_editor_status() {
                if (current_user_can('administrator')) {
                    $excluded_usernames = array('tsa', 'proposals', 'casey');
                    $excluded_ids = array_map(function($username) {
                        $user = get_user_by('login', $username);
                        return $user ? $user->ID : 0;
                    }, $excluded_usernames);

                    $users = get_users(array(
                        'role__in' => array('Administrator', 'Editor'),
                        'orderby'  => 'display_name',
                        'order'    => 'ASC',
                        'exclude'  => $excluded_ids
                    ));

                    if (!empty($users)) {
                        //echo '<div class="admin-editor-status" style="display:flex;justify-content:center;gap:15px;flex-wrap:wrap;margin-top:20px;">';
                        echo '<div class="admin-editor-status" style="gap:15px;flex-wrap:wrap;margin-top:20px;">';
                        foreach ($users as $user) {
                            $last_login = get_user_meta($user->ID, 'session_tokens', true);
                            $online = false;

                            if (!empty($last_login) && is_array($last_login)) {
                                foreach ($last_login as $token) {
                                    if (isset($token['expiration']) && $token['expiration'] > time()) {
                                        $online = true;
                                        break;
                                    }
                                }
                            }

                            echo '<div>' . ($online ? '🟢' : '🔴') . ' ' . esc_html($user->display_name) . '</div>';
                        }
                        echo '</div>';
                    }
                }
            }

            // call the function after it is defined
            show_admin_editor_status();
            echo '<br/>';
            ?>


            <div class="row">
                <div class="col-md-4">
                    <div id="php-error-feed" class="overflow-auto" style="max-height:80vh;"></div>
                </div>
                <div class="col-md-8">
                    <h5 class="mb-2">📝 Prepared Error Statement</h5>
                    <textarea id="error-editor"><?= esc_textarea($formatted); ?></textarea>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-dark" id="copy-editor">📋 Copy</button>
                        <button class="btn btn-secondary" id="download-editor">⬇️ Download TXT</button>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-outline-secondary" id="export-json">Export JSON</button>
                        <button class="btn btn-outline-secondary" id="export-csv">Export CSV</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        let editor;
        document.addEventListener('DOMContentLoaded', function() {
            editor = CodeMirror.fromTextArea(document.getElementById("error-editor"), {
                lineNumbers: true,
                mode: "javascript",
                theme: "default"
            });

            document.getElementById('copy-editor').addEventListener('click', () => {
                navigator.clipboard.writeText(editor.getValue());
                alert("Editor content copied!");
            });

            document.getElementById('download-editor').addEventListener('click', () => {
                const blob = new Blob([editor.getValue()], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'php-errors.txt';
                a.click();
                URL.revokeObjectURL(url);
            });

            function fetchFeed() {
                fetch(ajaxurl + '?action=fetch_php_errors')
                .then(res => res.json())
                .then(data => {
                    if (typeof data === 'object' && data !== null) {
                        let out = '';
                        Object.entries(data).forEach(([key, item]) => {
                            out += `<div class="card mb-3 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title text-danger fw-bold">${item.error}</h6>
                                    <p class="card-text mb-1"><small class="text-muted">Last seen: ${item.time}</small></p>
                                    <p class="card-text mb-2"><span class="badge bg-dark">❤️ ${item.count}</span></p>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-log="${item.error}" class="btn btn-sm btn-outline-primary copy-error-button">📋 Copy</button>
                                        <button data-log="${item.error}" class="btn btn-sm btn-outline-success google-error-button">🔍 Google</button>
                                        <button data-log="${item.error}" class="btn btn-sm btn-outline-dark chatgpt-error-button">🤖 ChatGPT</button>
                                        <button data-key="${key}" class="btn btn-sm btn-outline-danger delete-error">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>`;
                        });
                        document.getElementById('php-error-feed').innerHTML = out;

                        document.querySelectorAll('.delete-error').forEach(btn => {
                            btn.addEventListener('click', () => {
                                fetch(ajaxurl + '?action=delete_php_error&key=' + btn.dataset.key)
                                .then(() => fetchFeed());
                            });
                        });

                        document.querySelectorAll('.copy-error-button').forEach(button => {
                            button.addEventListener('click', function() {
                                navigator.clipboard.writeText(this.dataset.log).then(() => {
                                    alert('Error copied to clipboard.');
                                });
                            });
                        });

                        document.querySelectorAll('.google-error-button').forEach(button => {
                            button.addEventListener('click', function() {
                                const url = 'https://www.google.com/search?q=' + encodeURIComponent(this.dataset.log);
                                window.open(url, '_blank');
                            });
                        });

                        document.querySelectorAll('.chatgpt-error-button').forEach(button => {
                            button.addEventListener('click', function() {
                                const url = 'https://chat.openai.com/?q=' + encodeURIComponent(this.dataset.log);
                                window.open(url, '_blank');
                            });
                        });
                    }
                });
            }

            setInterval(fetchFeed, 5000);
            fetchFeed();
        });

        const clearBtn = document.createElement('button');
        clearBtn.className = 'btn btn-danger';
        clearBtn.textContent = '🧹 Clear All';
        clearBtn.id = 'clear-all-errors';
        document.querySelector('.mt-3.d-flex').appendChild(clearBtn);

        document.getElementById('clear-all-errors').addEventListener('click', () => {
            if (confirm('Are you sure you want to clear all errors?')) {
                fetch(ajaxurl + '?action=clear_all_php_errors').then(() => {
                    editor.setValue('');
                    fetchFeed();
                });
            }
        });


            document.getElementById('export-json').addEventListener('click', () => {
                fetch(ajaxurl + '?action=fetch_php_errors')
                .then(res => res.text())
                .then(json => {
                    const blob = new Blob([json], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'php-errors.json';
                    a.click();
                    URL.revokeObjectURL(url);
                });
            });

            document.getElementById('export-csv').addEventListener('click', () => {
                fetch(ajaxurl + '?action=fetch_php_errors')
                .then(res => res.json())
                .then(data => {
                    let csv = 'Error,Count,Last Seen\n';
                    Object.values(data).forEach(item => {
                        csv += `"${item.error.replace(/"/g, '""')}",${item.count},${item.time}\n`;
                    });
                    const blob = new Blob([csv], { type: 'text/csv' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'php-errors.csv';
                    a.click();
                    URL.revokeObjectURL(url);
                });
            });


        </script>
        <?php
    });
});

register_activation_hook(__FILE__, function() {
    file_put_contents(WP_CONTENT_DIR . '/php-error-log.json', json_encode([]));
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $path = WP_CONTENT_DIR . '/php-error-log.json';
    $errors = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
    $key = md5($errstr . $errfile . $errline);
    if (isset($errors[$key])) {
        $errors[$key]['count']++;
        $errors[$key]['time'] = date('Y-m-d H:i:s');
    } else {
        $errors[$key] = [
            'error' => "$errstr in $errfile on line $errline",
            'count' => 1,
            'time' => date('Y-m-d H:i:s')
        ];
    }
    file_put_contents($path, json_encode($errors));
});

add_action('wp_ajax_fetch_php_errors', function() {
    $path = WP_CONTENT_DIR . '/php-error-log.json';
    echo file_exists($path) ? file_get_contents($path) : '[]';
    wp_die();
});

add_action('wp_ajax_delete_php_error', function() {
    $path = WP_CONTENT_DIR . '/php-error-log.json';
    if (file_exists($path) && isset($_GET['key'])) {
        $errors = json_decode(file_get_contents($path), true);
        unset($errors[$_GET['key']]);
        file_put_contents($path, json_encode($errors));
    }
    wp_die();
});

function get_php_error_count() {
    $path = WP_CONTENT_DIR . '/php-error-log.json';
    if (!file_exists($path)) return 0;
    $errors = json_decode(file_get_contents($path), true);
    $total = 0;
    foreach ($errors as $err) $total += $err['count'];
    return $total;
}

add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('php_error_dashboard_widget', '🐘 PHP Error Feed', function() {
        $count = get_php_error_count();
        $url = admin_url('admin.php?page=php-error-feed');

        if ($count > 0) {
            echo "<div style='padding:10px; background-color:#f8d7da; color:#721c24; border-left:5px solid #f5c6cb; margin-bottom:10px;'>
                    <strong>⚠️ $count PHP Errors Found</strong>";
            echo "</div>";
            echo "<b class='text-muted fs-6'>";
            echo 'PHP: ' . phpversion() . ' | ';
            echo 'WP: ' . get_bloginfo('version') . ' | ';
            global $wpdb;
            echo 'MySQL: ' . $wpdb->db_version() . '';
            echo "</b><br/><br/>";
        } else {
            echo "<p>No PHP errors found.</p>";
        }

        echo "<p><a class='button button-primary' href='$url'>View Error Feed</a></p>";
    });
});

add_filter('add_menu_classes', function($menu) {
    $count = get_php_error_count();
    if ($count > 0) {
        foreach ($menu as &$item) {
            if (isset($item[2]) && $item[2] === 'php-error-feed') {
                $item[0] .= " <span class='update-plugins count-$count'><span class='plugin-count' style='background:red !important;'>$count</span></span>";
                break;
            }
        }
    }
    return $menu;
});

add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!is_admin()) return;
    $count = get_php_error_count();
    if ($count > 0) {
        $wp_admin_bar->add_node([
            'id'    => 'php-error-feed',
            'title' => 'PHP Errors <span class="ab-label ab-orange">' . $count . '</span>',
            'href'  => admin_url('admin.php?page=php-error-feed'),
            'meta'  => ['class' => 'php-error-feed-badge']
        ]);
    }
}, 100);

add_action('wp_ajax_clear_all_php_errors', function() {
    $path = WP_CONTENT_DIR . '/php-error-log.json';
    file_put_contents($path, json_encode([]));
    wp_die();
});
