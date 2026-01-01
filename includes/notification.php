<?php

// Auto-detect notification from common variable names
$notifications = [];

if (isset($success_message) && !empty($success_message)) {
    $notifications[] = [
        'type' => 'success',
        'message' => $success_message,
        'icon' => 'bi-check-circle-fill'
    ];
}

if (isset($error_message) && !empty($error_message)) {
    $notifications[] = [
        'type' => 'danger',
        'message' => $error_message,
        'icon' => 'bi-exclamation-triangle-fill'
    ];
}

if (isset($warning_message) && !empty($warning_message)) {
    $notifications[] = [
        'type' => 'warning',
        'message' => $warning_message,
        'icon' => 'bi-exclamation-circle-fill'
    ];
}

if (isset($info_message) && !empty($info_message)) {
    $notifications[] = [
        'type' => 'info',
        'message' => $info_message,
        'icon' => 'bi-info-circle-fill'
    ];
}

// Also support single notification array
if (isset($notification) && !empty($notification)) {
    $notifications[] = $notification;
}

// Also support message and messageType (common in existing code)
if (isset($message) && !empty($message) && isset($messageType)) {
    $icon_map = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill'
    ];

    $notifications[] = [
        'type' => $messageType,
        'message' => $message,
        'icon' => $icon_map[$messageType] ?? ''
    ];
}
?>

<!-- Notification Container -->
<div id="notification-container" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 500px; width: 90%;"></div>

<?php if (!empty($notifications)): ?>
    <style>
        .floating-alert {
            position: relative;
            animation: slideInDown 0.3s ease-out;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
        }

        .floating-alert.fade-out {
            animation: fadeOutUp 0.5s ease-out forwards;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeOutUp {
            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notifications = <?php echo json_encode($notifications); ?>;
            const container = document.getElementById('notification-container');

            notifications.forEach((notification, index) => {
                setTimeout(() => {
                    showNotification(notification.type, notification.message, notification.icon);
                }, index * 150); // Stagger multiple notifications
            });

            function showNotification(type, message, icon) {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show floating-alert`;
                alert.setAttribute('role', 'alert');

                const iconHTML = icon ? `<i class="bi ${icon} me-2"></i>` : '';

                alert.innerHTML = `
            ${iconHTML}${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

                container.appendChild(alert);

                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        if (alert.parentElement) {
                            alert.remove();
                        }
                    }, 500); // Wait for animation to complete
                }, 5000);

                // Also handle manual close
                alert.querySelector('.btn-close').addEventListener('click', function() {
                    alert.classList.add('fade-out');
                    setTimeout(() => {
                        if (alert.parentElement) {
                            alert.remove();
                        }
                    }, 500);
                });
            }
        });
    </script>
<?php endif; ?>