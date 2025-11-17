setInterval(async () => {
    const res = await fetch("/ajax/fetch_notifications.php");
    const data = await res.json();
    if (data.success) {
        // Update UI badge and list
    }
}, 15000);
