<script>
    window.DeviceTracker = {
        config: @json([
            'fingerprint' => $fingerprint
        ])
    };
</script>
<script>
    if (window.DeviceTracker.config.fingerprint === null) {
        const fpPromise = import('https://openfpcdn.io/fingerprintjs/v4')
            .then(FingerprintJS => FingerprintJS.load())

        fpPromise
            .then(fp => fp.get())
            .then(result => {
                window.DeviceTracker.config.fingerprint = result.visitorId
                document.cookie = `fingerprint=${result.visitorId}; SameSite=Strict; Secure`
            })
            .catch(error => console.error(error))
    }
</script>