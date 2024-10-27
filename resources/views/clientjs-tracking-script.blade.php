<script>
    window.DeviceTracker = {
        config: @json([
            'current' => $current,
            'transport' => $transport,
            'library' => $library
        ])
    };
</script>
<script>
    if (window.DeviceTracker.config.current === null) {
        const scriptPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = window.DeviceTracker.config.library.url;
            script.onload = () => resolve(window.ClientJS);
            script.onerror = reject;
            document.head.appendChild(script);
        });

        scriptPromise
            .then(ClientJS => {
                const client = new ClientJS();
                return {
                    visitorId: client.getFingerprint()
                };
            })
            .then(result => {
                const transport = window.DeviceTracker.config.transport;

                window.DeviceTracker.config.current = result.visitorId;
                document.cookie = `${transport.key}=${result.visitorId}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
            })
            .catch(error => console.error(error));
    } else {
        const transport = window.DeviceTracker.config.transport;
        document.cookie = `${transport.key}=${window.DeviceTracker.config.current}; expires=Fri, 31 Dec 9999 23:59:59 GMT; domain=${location.hostname}; SameSite=Lax;`
    }
</script>